<?php

namespace app\controllers;

use yii\web\Controller;
use app\models\ConsultaModel;
use app\models\MetodosModel;
use app\models\Paper;

class ExerciciosController extends Controller
{

    public function actionHome()
    {
        $this->layout = 'home';

        return $this->render('home');
    }

    public function actionPredict()
    {
        $this->layout = 'clean';

        $model = new ConsultaModel;
        $post = $_POST;

        if ($model->load($post) && $model->validate() /*&& $model2->load($post) && $model2->validate()*/) {
            $start = $model->inicio;
            $day = $model->final;
            $start = \DateTime::createFromFormat('d/m/Y', $start); //Dia de início
            $day = \DateTime::createFromFormat('d/m/Y', $day); //Dia a ser previsto

            $start = Paper::toIsoDate($start->getTimestamp()); //Passando para o padrão de datas do banco
            $day = Paper::toIsoDate($day->getTimestamp()); //Passando para o padrão de datas do banco

            $stock = $model->nome;

            $cursor_by_price = $model->PegarDados($stock, $start, $day);

            $premin = $model->DefinirPremin($cursor_by_price);
            $premax = $model->DefinirPremax($cursor_by_price);

            //echo ('Período análisado de ' . (Paper::toDate($start))->format('d/m/Y') . ' até ' . (Paper::toDate($cursor_by_price[count($cursor_by_price) - 1]['date']))->format('d/m/Y') . '<br>');
            //echo ('O menor preço foi: R$' . $premin['preult'] . ' em ' . (Paper::toDate($premin['date']))->format('d/m/Y') . '<br>');
            //echo ('O maior preço foi: R$' . $premax['preult'] . ' em ' . (Paper::toDate($premax['date']))->format('d/m/Y') . '<br>');

            $interval = abs($premin['preult'] - $premax['preult']) / $model->states_number; //calculo do intervalo
            //echo ("<br> Quantidade de intervalos $model->states_number <br>");
            //echo ('O tamanho do intervalo é ' . round($interval, 2) . '<br>');

            //echo ('<br>Intervalos: <br>');

            /*for ($i = 0; $i < $model->states_number; $i++) { //imprime na tela os intervalos
                $price = $premin['preult'] + $interval * ($i);
                echo ('Estado ' . ($i + 1) . ' de ' . round($price, 2) . ' até ' . round(($price + $interval), 2) . '<br>');
            }*/

            $states = []; //vetor que contem a quantidade de elementos em cada estado
            for ($i = 0; $i < $model->states_number; $i++) {
                $states[$i] = 0;
            }

            foreach ($cursor_by_price as $cursor) { //atribui um estado a partir do preço de fechamento para cada data no conjunto de treinamento
                $cursor['state'] = $model->getState($cursor['preult'], $premin['preult'], $interval, $model->states_number);
                if ($cursor['state'] != 0)
                    $states[$cursor['state'] - 1] += 1;

                ////echo($cursor['preult'] . ' -> ' . $cursor['state'] . " " . (Paper::toDate($cursor['date']))->format('d/m/Y') . '<br>');
            }

            //echo ('<br>Estado x Quantidade de elementos:<br>');
            /*foreach ($states as $i => $s) {
                echo ('Estado ' . ($i + 1) . ' tem ' . $s . ' elementos<br>');
            }*/

            //echo '<br>';

            $matrix = $model->transitionMatrix($cursor_by_price, $states, $model->states_number); //função que constrói a matriz de transição

            $vector = $model->predictVector($matrix, $cursor_by_price, $model->states_number); //função que constrói o vetor de predição

            return $this->render('vervetor', [
                'vector' => $vector,
                'model' => $model,
                'stock' => $model->nome, 
                'states_number' => $model->states_number, 
                'day' => $day, 
                'premin' => $premin['preult'], 
                'interval' => $interval
            ]);
        } else {

            return $this->render('predict', [
                'consultaModel' => $model
            ]);
        }
    }

    public function actionMetodos()
    {
        $this->layout = 'clean';

        $metodosModel = new MetodosModel;
        $post = $_POST;

        if ($metodosModel->load($post) && $metodosModel->validate()) {

            if ($metodosModel->metodo == 'CMTD') {

                return $this->redirect(['predict']);
            } else {

                return $this->render('cmo');
            }
        } else {
            return $this->render('metodos', [
                'model' => $metodosModel
            ]);
        }
    }

    //Função que valida se o estado previsto condiz com o preço real
    public function actionValidate(/*$vector, $stock, $states_number, $day, $premin, $premax, $interval*/){
        $this->layout = 'clean';

        //Os valores recebidos através da URL são string, por isso precisam de manipulação
        $stock = $_GET['stock'];
        $vector = substr($_GET['vector'], 1, -1);
        $states_number = intval($_GET['states_number']);
        $day = Paper::toIsoDate((intval($_GET['day'])/1000));
        $premin = floatval($_GET['premin']);
        $interval = floatval($_GET['interval']);
        
        $vector = explode(", ", $vector);
        for($i = 0; $i < count($vector); $i++){
            $vector[$i] = floatval($vector[$i]);
        }

        $nextDay = Paper::find()->where(['=', 'codneg', $stock])->andWhere(['>=', 'date', $day])->one(); //busca o dia seguinte no banco
        $nextDay['state'] = ConsultaModel::getState($nextDay['preult'], $premin, $interval, $states_number); // calcula o estado do dia seguinte

        $max = 0;

        for($i=1; $i < $states_number; $i++){ //calculando o estado com maior probabilidade no vetor de previsão
            if($vector[$i] >= $vector[$max])
                $max = $i;
        }

        if($nextDay['state'] == $max+1)
            $resultado = 'Acertou!';
        
        else
            $resultado = 'Errou!';

        return $this->render('resultado', [
            'resultado' => $resultado,
            'nextDay' => $nextDay,
            'estado' => $max+1,
            'probabilidade' => $vector[$max]
        ]);    
    }

    public function actionLogin()
    {
    }

    public function actionLogout()
    {
    }
}
