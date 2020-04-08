<?php
namespace app\models;

use yii\base\Model;
use MathPHP\LinearAlgebra\Matrix;
use MathPHP\LinearAlgebra\MatrixFactory;
use MathPHP\LinearAlgebra\Vector;
use yii\helpers\ArrayHelper;
use app\models\Paper;
use yii\console\widgets\Table;
    
class PredictModel extends Model{

    //Função que valida se o estado previsto condiz com o preço real
    public function validatePrediction($vector, $stock, $states_number, $day, $premin, $premax, $interval){
        $nextDay = Paper::find()->where(['=', 'codneg', $stock])->andWhere(['>=', 'date', $day])->one(); //busca o dia seguinte no banco

        $nextDay['state'] = Paper::getState($nextDay['preult'], $premin['preult'], $premax['preult'], $interval, $states_number); // calcula o estado do dia seguinte

        $max = 0;

        for($i=1; $i < $states_number; $i++){ //calculando o estado com maior probabilidade no vetor de previsão
            if($vector[0][$i] >= $vector[0][$max])
                $max = $i;
        }

        if($nextDay['state'] == $max+1)
            echo '<br>Acertou!';
        
        else
            '<br>Errou!';
    }

    //Constroi o vetor de previsão
    public function predictVector($matrix, $paper, $states_number){
        $matrix = MatrixFactory::create($matrix);
        $vector = [[]];

        for($i = 0; $i < $states_number; $i++)
            $vector[0][$i] = 0;
        
        $vector[0][$paper[count($paper)-1]['state']-1] = 1; //declaração do vetor de estado inicial a partir do ultimo dia do conjunto de treinamento
        $vector = MatrixFactory::create($vector);

        $vector = $vector->multiply($matrix); //multiplicando

        return $vector;
    }

    //Constroi a matriz de transição a partir do conjunto de treinamento
    public function transitionMatrix($paper, $states, $states_number){
        $matrix = [[]];
        for($i = 0; $i < $states_number; $i++)
            for($j = 0; $j < $states_number; $j++)
                $matrix[$i][$j] = 0;
             
        for($i = 0; $i < count($paper)-1; $i++){//calculando a quantidade de elementos em cada transição da matriz
            /*if($paper[$i]['state'] != 0){
                $j = $i+1;
                while($paper[$j]['state'] == 0){
                    $j++;
                }
                
            }*/
            
            $j = $i+1;
            $matrix[$paper[$i]['state']-1][$paper[$j]['state']-1] += 1;
        }

        $matrix[$paper[count($paper)-1]['state']-1][$paper[count($paper)-1]['state']-1] += 1;//contagem do ultimo valor do conjunto de treinamento

        for($i = 0; $i < $states_number; $i++) //construção da matriz de transição $states contem a quantidade de elementos em cada estado
            for($j = 0; $j < $states_number; $j++){
                $matrix[$i][$j] /= $states[$i];
            }

        return $matrix;
    }
    
    //Função principal
    public function predict($start, $stock, $day, $states_number){ 

        $start = \DateTime::createFromFormat('d/m/Y', $start);//Dia de início
        $day = \DateTime::createFromFormat('d/m/Y', $day);//Dia  a ser previsto

        $start = Paper::toIsoDate($start->getTimestamp());
        $day = Paper::toIsoDate($day->getTimestamp());
        
        //representação do conjunto de treinamento
        $cursor_by_price = Paper::find()->where(
            ['=', 'codneg', $stock], 
            ['=', 'tpmerc', '010'],
            ['>=', 'date', $start]
            )->andWhere(['<', 'date', $day])->all();

        $premin = $cursor_by_price[0];//array com o menor preço do conjunto

        foreach($cursor_by_price as $cursor){
            if($cursor['preult'] < $premin['preult'])
                $premin = $cursor;
        }    

        $premax = $cursor_by_price[0];//array com o maior preço do conjunto

        foreach($cursor_by_price as $cursor){
            if($cursor['preult'] > $premax['preult'])
                $premax = $cursor;
        }    

        echo('Período análisado de ' . (Paper::toDate($start))->format('d/m/Y') . ' até ' . (Paper::toDate($cursor_by_price[count($cursor_by_price)-1]['date']))->format('d/m/Y') . '<br>');
        echo('O menor preço foi: R$' . $premin['preult'] . ' em ' . (Paper::toDate($premin['date']))->format('d/m/Y') . '<br>');
        echo('O maior preço foi: R$' . $premax['preult'] . ' em ' . (Paper::toDate($premax['date']))->format('d/m/Y') . '<br>');
    
        /*$average = Paper::movingAverage($cursor_by_price, 3);
        asort($average);*/

        $interval = abs($premin['preult'] - $premax['preult'])/$states_number;//calculo do intervalo
        echo("<br> Quantidade de intervalos $states_number <br>");
        echo('O tamanho do intervalo é ' . round($interval, 2) . '<br>');

        echo('<br>Intervalos: <br>');
        
        for($i = 0; $i<$states_number; $i++){//imprime na tela os intervalos
            $price = $premin['preult'] + $interval * ($i);
            echo ('Estado ' . ($i+1) . ' de ' . round($price, 2) . ' até ' . round(($price+$interval), 2) . '<br>');
        }

        $states = [];//vetor que contem a quantidade de elementos em cada estado
        for($i=0; $i<$states_number; $i++){
            $states[$i] = 0;
        }
        
        foreach($cursor_by_price as $cursor){//atribui um estado a partir do preço de fechamento para cada data no conjunto de treinamento
            $cursor['state'] = Paper::getState($cursor['preult'], $premin['preult'], $premax['preult'], $interval, $states_number);
            if($cursor['state'] != 0)
                $states[$cursor['state']-1] += 1;

            //echo($cursor['preult'] . ' -> ' . $cursor['state'] . " " . (Paper::toDate($cursor['date']))->format('d/m/Y') . '<br>');
        }

        echo('<br>Estado x Quantidade de elementos:<br>');
        foreach($states as $i => $s){
            echo('Estado ' . ($i+1) . ' tem ' . $s . ' elementos<br>');
        }

        echo '<br>';

        $matrix = $this->transitionMatrix($cursor_by_price, $states, $states_number);//função que constrói a matriz de transição

        /*foreach($matrix as $m){
            print_r($m);
            echo '<br>';
        }*/

        $vector = $this->predictVector($matrix, $cursor_by_price, $states_number);//função que constrói o vetor de predição
        echo "Previsão de tendências para o dia seguinte: <br>";
        for($i=0; $i < $states_number; $i++){
            echo 'Probabilidade de ' . round(($vector[0][$i])*100, 2) . '% para o estado ' . ($i+1) . '<br>'; 
        }

        $this->validatePrediction($vector, $stock, $states_number, $day, $premin, $premax, $interval);//função de validação
    }

}       