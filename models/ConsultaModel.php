<?php

namespace app\models;

use yii\base\Model;
use app\models\Paper;
use MathPHP\LinearAlgebra\MatrixFactory;

class ConsultaModel extends Model
{
    public $nome;
    public $inicio;
    public $final;
    public $states_number;
    public $periodo;

    public function rules()
    {
        return [
            [['nome', 'inicio', 'final', 'states_number'], 'required'],
            [['states_number', 'periodo'], 'integer'],
            [['inicio', 'final'], 'date', 'format' => 'dd/mm/yyyy']
            //['final', 'compare', 'compareValue' => 'inicio', 'operator' => '>']
        ];
    }

    public function attributeLabels()
    {
        return [
            'nome' => 'Nome',
            'inicio' => 'Data Inicial',
            'final' => 'Data Final',
            'states_number' => 'Quantidade de intervalos'
        ];
    }

    public function PegarDados($stock, $start, $final)
    {
        return Paper::find()->orderBy('date')->where(
            ['=', 'codneg', $stock],
            ['=', 'tpmerc', '010']
        )->andWhere(['>=', 'date', $start])->andWhere(['<=', 'date', $final])->all();
    }

    public function DefinirPremin($cursor_by_price)
    {
        $premin = $cursor_by_price[0]; //array com o menor preço do conjunto

        foreach ($cursor_by_price as $cursor) {
            if ($cursor['preult'] < $premin['preult'])
                $premin = $cursor;
        }

        return $premin;
    }

    public function DefinirPremax($cursor_by_price)
    {
        $premax = $cursor_by_price[0]; //array com o maior preço do conjunto

        foreach ($cursor_by_price as $cursor) {
            if ($cursor['preult'] > $premax['preult'])
                $premax = $cursor;
        }

        return $premax;
    }

    public static function getState($price, $premin, $interval, $states_number)
    {
        for ($i = 0; $i < $states_number; $i++) {
            if ($price >= ($premin + ($interval * $i)) && $price-0.00001 <= ($premin + ($interval * ($i + 1)))) {
                return $i + 1;
            }
        }
        
        return 0;
    }

    //Constroi a matriz de transição a partir do conjunto de treinamento
    public function transitionMatrix($paper, $states, $states_number)
    {
        $matrix = [[]];
        for ($i = 0; $i < $states_number; $i++)
            for ($j = 0; $j < $states_number; $j++)
                $matrix[$i][$j] = 0;

        for ($i = 0; $i < count($paper) - 1; $i++) { //calculando a quantidade de elementos em cada transição da matriz
            $j = $i + 1;
            $matrix[$paper[$i]['state'] - 1][$paper[$j]['state'] - 1] += 1;
        }

        $matrix[$paper[count($paper) - 1]['state'] - 1][$paper[count($paper) - 1]['state'] - 1] += 1; //contagem do ultimo valor do conjunto de treinamento

        for ($i = 0; $i < $states_number; $i++) //construção da matriz de transição $states contem a quantidade de elementos em cada estado
            for ($j = 0; $j < $states_number; $j++) {
                if($states[$i] == 0)
                    $matrix[$i][$j] = 0;
                else
                    $matrix[$i][$j] /= $states[$i];
            }

        return $matrix;
    }

    //Constroi o vetor de previsão
    public function predictVector($matrix, $paper, $states_number)
    {
        $matrix = MatrixFactory::create($matrix);
        $vector = [[]];

        for ($i = 0; $i < $states_number; $i++)
            $vector[0][$i] = 0;

        $vector[0][$paper[count($paper) - 1]['state'] - 1] = 1; //declaração do vetor de estado inicial a partir do ultimo dia do conjunto de treinamento
        $vector = MatrixFactory::create($vector);

        $vector = $vector->multiply($matrix); //multiplicando

        return $vector;
    }

    public function getInterval($premin, $interval, $i) {
        $min = $premin + ($interval * $i);
        $max = $premin + ($interval * ($i + 1));

        return [$min, $max];
    }

    public function chartData($next, $intervals) {
        //Dados para construção do gráfico
        $fechamentoData = array();
        $infData = array();
        $supData = array();
        $avgData = array();
        $tendencia = 0;

        //Dados dos preço de fechamento para o gráfico
        foreach ($next as $date) {
            $formattedDate = intval(($date['date']->toDateTime())->format('U') . '000');
            array_push($fechamentoData, [$formattedDate, $date['preult']]);
        }

        //Dados dos preços dos intervalos para o gráfico
        foreach ($intervals as $i => $interval) {
            $formattedDate = intval(($next[$i]['date']->toDateTime())->format('U') . '000');
            array_push($infData, [$formattedDate, $interval[0]]);
            array_push($supData, [$formattedDate, $interval[1]]);
        }

        //Dados do valor médio para o gráfico
        foreach ($intervals as $i => $interval) {
            $formattedDate = intval(($next[$i]['date']->toDateTime())->format('U') . '000');
            array_push($avgData, [$formattedDate, ($interval[0] + $interval[1]) / 2]);
        }

        for($i = 0; $i < count($avgData)-1; $i++) {
            $avgAux = $avgData[$i+1][1] - $avgData[$i][1];
            $fechamentoAux = $fechamentoData[$i+1][1] - $fechamentoData[$i][1];

            if($avgAux > 0 && $fechamentoAux > 0)
                $tendencia++;

            else if($avgAux < 0 && $fechamentoAux < 0)
                $tendencia++;

            else if($avgAux == 0 && $fechamentoAux == 0)
                $tendencia++;
        }

        return ([
            'fechamentoData' => $fechamentoData,
            'infData' => $infData,
            'supData' => $supData,
            'avgData' => $avgData,
            'tendencia' => $tendencia
        ]);
    }
}
