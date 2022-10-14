<?php

namespace app\controllers;

use yii\web\Controller;
use app\models\ConsultaModel;
use app\models\Paper;
use Yii;
use MathPHP\LinearAlgebra\MatrixFactory;

//date_default_timezone_set("america/bahia");

class MainController extends Controller
{

    public function actionHome()
    {
        $this->layout = 'navbar';

        return $this->render('home');
    }

    public function actionPredictResultInterval()
    {
        $this->layout = 'navbar';

        $model = new ConsultaModel;
        $post = $_POST;

        if ($model->load($post) && $model->validate()) {
            $start = $model->inicio;
            $final = $model->final;
            
            //Dia de início do conjunto de treinamento
            $start = \DateTime::createFromFormat('d/m/YH:i:s', $start . '24:00:00'); 
            
            //Dia final do conjunto de treinamento
            $final = \DateTime::createFromFormat('d/m/YH:i:s', $final . '24:00:00'); 

            //Passando para o padrão de datas do banco
            $start = Paper::toIsoDate($start->getTimestamp()); 
            
            //Passando para o padrão de datas do banco
            $final = Paper::toIsoDate($final->getTimestamp()); 

            $stock = $model->nome;

            $cursor_by_price = $model->getData($stock, $start, $final);

            $premin = $model->definePremin($cursor_by_price);
            $premax = $model->definePremax($cursor_by_price);

            //calculo do intervalo
            $interval = abs($premin['preult'] - $premax['preult']) / $model->states_number; 

            //vetor que contem a quantidade de elementos em cada estado
            $states = []; 

            for ($i = 0; $i < $model->states_number; $i++) {
                $states[$i] = 0;
            }

            $cursor_by_price[0]["t_state"] = 2;

            $three_states = [0, 0, 0];

            //atribui um estado a partir do preço de fechamento para cada data no conjunto de treinamento
            foreach ($cursor_by_price as $index => $cursor) {         
                if ($index > 0) {
                    $cursor['t_state'] = $model->getThreeState($cursor['preult'], $cursor_by_price[$index - 1]['preult']);
                }

                $three_states[$cursor['t_state'] - 1] += 1;

                $cursor['state'] = $model->getState($cursor['preult'], $premin['preult'], $interval, $model->states_number);
                if ($cursor['state'] != 0)
                    $states[$cursor['state'] - 1] += 1;
            }
            //função que constrói a matriz de transição
            $matrix = $model->transitionMatrix($cursor_by_price, $states, $model->states_number, "state"); 

            //função que constrói o vetor de predição
            $vector = $model->predictVector($matrix, $cursor_by_price, $model->states_number, "state"); 
            
            
            return $this->render('predict-result-interval', [
                'vector' => $vector,
                'last' => $cursor_by_price[count($cursor_by_price) - 1],
                'last_price' => $cursor_by_price[count($cursor_by_price) - 1]['preult'],
                'model' => $model,
                'stock' => $model->nome,
                'states_number' => $model->states_number,
                'day' => $final,
                'premin' => $premin['preult'],
                'interval' => $interval
            ]);
        }
        return $this->render('predict', [
            'consultaModel' => $model
        ]);
    }

    //Função que valida se o estado previsto condiz com o preço real
    public function actionValidate(/*$vector, $stock, $states_number, $day, $premin, $premax, $interval*/)
    {
        $this->layout = 'navbar';

        //Os valores recebidos através da URL são string, por isso precisam de manipulação
        $stock = $_GET['stock'];
        $vector = substr($_GET['vector'], 1, -1); //Removendo o primeiro e último caractere "[ ]"
        $t_vector = substr($_GET['t_vector'], 1, -1); //Removendo o primeiro e último caractere "[ ]"
        $states_number = intval($_GET['states_number']); //Convertendo para inteiro
        $day = Paper::toIsoDate((intval($_GET['day']) / 1000)); //Convertendo pra o padrão IsoDate utilizado pelo Mongo
        $premin = floatval($_GET['premin']); //Conversão para float
        $interval = floatval($_GET['interval']); //Conversão para float
        $last_price = floatval($_GET['last_price']); //Conversão para float

        $vector = explode(", ", $vector); //Construindo um vetor de strings
        for ($i = 0; $i < count($vector); $i++) { //Pegando cada valor do vetor de strings e convertendo para float
            $vector[$i] = floatval($vector[$i]);
        }

        $t_vector = explode(", ", $t_vector); //Construindo um vetor de strings
        for ($i = 0; $i < count($t_vector); $i++) { //Pegando cada valor do vetor de strings e convertendo para float
            $t_vector[$i] = floatval($t_vector[$i]);
        }

        $next_day = Paper::find()->orderBy('date')->where(['=', 'codneg', $stock])->andWhere(['>', 'date', $day])->one(); //busca o dia seguinte no banco
        $next_day['state'] = ConsultaModel::getState($next_day['preult'], $premin, $interval, $states_number); // calcula o estado do dia seguinte
        $max = 0;
        $t_max = 0;

        for ($i = 1; $i < $states_number; $i++) { //calculando o estado com maior probabilidade no vetor de previsão
            if ($vector[$i] >= $vector[$max])
                $max = $i;
        }

        for ($i = 1; $i < 3; $i++) { //calculando o estado com maior probabilidade no vetor de previsão
            if ($t_vector[$i] >= $t_vector[$t_max])
                $t_max = $i;
        }

        if ($next_day['state'] == $max + 1)
            $result = 'Acertou!';

        else
            $result = 'Errou!';

        switch ($t_max) {
            case 0:
                if ($next_day['preult'] > $last_price)
                    $result_three_state = 'Acertou!';
                else
                    $result_three_state = 'Errou!';
                break;

            case 1:
                if ($next_day['preult'] == $last_price)
                    $result_three_state = 'Acertou!';
                else
                    $result_three_state = 'Errou!';
                break;

            case 2:
                if ($next_day['preult'] < $last_price)
                    $result_three_state = 'Acertou!';
                else
                    $result_three_state = 'Errou!';
                break;

            default:
                break;
        }

        return $this->render('resultado', [
            'result' => $result,
            'result_three_state' => $result_three_state,
            'next_day' => $next_day,
            'estado' => $max + 1,
            'probabilidade' => $vector[$max],
            'three_states' => $t_vector[$t_max],
            't_max' => $t_max,
            't_result' => $result_three_state,
            'last_price' => $last_price
        ]);
    }

    public function actionTeste()
    {
        $this->layout = 'navbar';

        $model = new ConsultaModel;
        $post = $_POST;

        if ($model->load($post) && $model->validate() && $model->periodo) {
            Yii::debug("Inicio");
            $start = $model->inicio;
            $consultas = 0;
            $acertou = 0;
            $errou = 0;
            $acertou_avg = 0;
            $errou_avg = 0;
            $t_acertou = 0;
            $t_errou = 0;
            $next = array();
            $intervals = array();
            $aux = Paper::toIsoDate(\DateTime::createFromFormat('d/m/YH:i:s', $model->final . '24:00:00')->format('U'));
            $next_day = new Paper();
            $client1 = ['cash' => 100, 'actions' => 0];
            $client2 = ['cash' => 100, 'actions' => 0];
            $client3 = ['cash' => 100, 'actions' => 0];
            $client4 = ['cash' => 100, 'actions' => 0];
            $clientDatas = [];
            $t_client1 = ['cash' => 100, 'actions' => 0];
            $t_client2 = ['cash' => 100, 'actions' => 0];
            $t_client3 = ['cash' => 100, 'actions' => 0];
            $t_client4 = ['cash' => 100, 'actions' => 0];
            $t_client5 = ['cash' => 100, 'actions' => 0];
            $t_clientDatas = [];
            $t_datas = [];
            $base = $model->base;

            $final = $start;
            $start = \DateTime::createFromFormat('d/m/YH:i:s', $start . '24:00:00'); //Dia de início do conjunto de treinamento
            $start = $start->modify("-$model->periodo $model->metric"); //O conjunto de treinamento será definido n meses antes do dia a ser previsto
            /* -------------------------------------------------------------------- */
            $final = \DateTime::createFromFormat('d/m/YH:i:s', $final . '24:00:00')->modify('-1 day'); //Dia final do conjunto de treinamento

            $start = Paper::toIsoDate($start->format('U')); //Passando para o padrão de datas do banco
            $final = Paper::toIsoDate($final->format('U')); //Passando para o padrão de datas do banco

            $stock = $model->nome;
            $cursor_by_price = $model->getData($stock, $start, $final); //Setup inicial do conjunto de treinamento
            // $cursor_by_price_avg_aux = $model->getData($stock, $start, $final); //Setup inicial do conjunto de treinamento
            // $cursor_by_price_avg = ConsultaModel::handleAverages($cursor_by_price_avg_aux, $base); //Calculando médias e tirando as diferenças

            $predictStart = \DateTime::createFromFormat('d/m/YH:i:s', $model->inicio . '24:00:00');
            $next_days = $model->getData($stock, Paper::toIsoDate($predictStart->format('U')), $aux); //Busca no banco os dias que serão previstos
            $consultas = count($next_days);
            Yii::debug("Conjunto de treinamento pronto");

            while (1) {

                if (count($next_days) == 0)
                    break;

                $next_day = array_shift($next_days); //busca o dia seguinte no banco

                //Se o dia a ser previsto for maior do que o nosso ultimo dia estipulado o laço ou nulo acaba
                if ($next_day['date'] > $aux || $next_day['date'] == null)
                    break;

                $premin = $model->definePremin($cursor_by_price);
                $premax = $model->definePremax($cursor_by_price);

                $interval = ($premax['preult'] - $premin['preult']) / $model->states_number; //calculo do intervalo

                // foreach ($cursor_by_price_avg as $avg) {
                //     $avg['preult'] += $last_day['preult'];
                // }

                // $premin_avg = $model->definePremin($cursor_by_price_avg);
                // $premax_avg = $model->definePremax($cursor_by_price_avg);

                // $interval_avg = abs($premin_avg['preult'] - $premax_avg['preult']) / $model->states_number; //calculo do intervalo



                $states = []; //vetor que contem a quantidade de elementos em cada estado
                $states_avg = []; //vetor que contem a quantidade de elementos em cada estado
                for ($i = 0; $i < $model->states_number; $i++) {
                    $states[$i] = 0;
                    // $states_avg[$i] = 0;
                }

                $cursor_by_price[0]["t_state"] = 2;

                $three_states = [0, 0, 0];

                foreach ($cursor_by_price as $index => $cursor) { //atribui um estado a partir do preço de fechamento para cada data no conjunto de treinamento
                    if ($index > 0) {
                        $cursor['t_state'] = $model->getThreeState($cursor['preult'], $cursor_by_price[$index - 1]['preult']);
                    }

                    $three_states[$cursor['t_state'] - 1] += 1;

                    $cursor['state'] = $model->getState($cursor['preult'], $premin['preult'], $interval, $model->states_number);
                    if ($cursor['state'] != 0)
                        $states[$cursor['state'] - 1] += 1;
                }

                // foreach ($cursor_by_price_avg as $index => $cursor) { //atribui um estado a partir do preço de fechamento para cada data no conjunto de treinamento
                //     $cursor['state'] = $model->getState($cursor['preult'], $premin_avg['preult'], $interval_avg, $model->states_number);
                //     if ($cursor['state'] != 0)
                //         $states_avg[$cursor['state'] - 1] += 1;
                // }

                $three_state_matrix = $model->transitionMatrix($cursor_by_price, $three_states, 3, "t_state");
                $three_state_vector = $model->predictVector($three_state_matrix, $cursor_by_price, 3, "t_state"); //função que constrói o vetor de predição

                $matrix = $model->transitionMatrix($cursor_by_price, $states, $model->states_number, "state"); //função que constrói a matriz de transição
                $vector = $model->predictVector($matrix, $cursor_by_price, $model->states_number, "state"); //função que constrói o vetor de predição

                // $matrix_avg = $model->transitionMatrix($cursor_by_price_avg, $states_avg, $model->states_number, "state"); //função que constrói a matriz de transição
                // $vector_avg = $model->predictVector($matrix_avg, $cursor_by_price_avg, $model->states_number, "state"); //função que constrói o vetor de predição
                /* Validação ----------------------------------------------------------------- */

                $last_day = $cursor_by_price[count($cursor_by_price) - 1];

                $next_day['state'] = $model->getState($next_day['preult'], $premin['preult'], $interval, $model->states_number); // calcula o estado do dia seguinte
                $next_day['t_state'] = $model->getThreeState($next_day['preult'], $last_day['preult']); // calcula o estado do dia seguinte
                // $next_day['state_avg'] = $model->getState($next_day['preult'], $premin_avg['preult'], $interval_avg, $model->states_number); // calcula o estado do dia seguinte

                array_push($next, $next_day);
                $max = 0;
                // $max_avg = 0;
                $t_max = 0;
                $vector = $vector[0];
                // $vector_avg = $vector_avg[0];
                $t_vector = $three_state_vector[0];

                for ($i = 1; $i < $model->states_number; $i++) { //calculando o estado com maior probabilidade no vetor de previsão
                    if ($vector[$i] >= $vector[$max])
                        $max = $i;
                }

                for ($i = 1; $i < $model->states_number; $i++) { //calculando o estado com maior probabilidade no vetor de previsão
                    // if ($vector_avg[$i] >= $vector_avg[$max_avg])
                    $max_avg = $i;
                }

                for ($i = 1; $i < 3; $i++) { //calculando o estado com maior probabilidade no vetor de previsão
                    if ($t_vector[$i] >= $t_vector[$t_max])
                        $t_max = $i;
                }

                if ($t_max === 0)
                    array_push($t_datas, ($last_day['preult'] + $premax['preult']) / 2);
                elseif ($t_max === 1)
                    array_push($t_datas, $last_day['preult']);
                else
                    array_push($t_datas, ($premin['preult'] + $last_day['preult']) / 2);


                array_push($intervals, $model->getInterval($premin['preult'], $interval, $max));
                if ($next_day['state'] == $max + 1)
                    $acertou++;
                else
                    $errou++;

                if ($next_day['t_state'] == $t_max + 1)
                    $t_acertou++;
                else
                    $t_errou++;

                // if ($next_day['state_avg'] == $max_avg + 1)
                //     $acertou_avg++;
                // else
                //     $errou_avg++;

                // Verifica qual dos 3 estados tem maior probabilidade e realiza compra/venda
                switch ($t_max) {
                    case 0:
                        $t_client1 = $model->handleBuy($t_client1, $last_day['preult']);
                        $t_client2 = $model->handleBuy($t_client2, $last_day['preult']);
                        $t_client3 = $model->handleBuy($t_client3, $last_day['preult']);
                        $t_client4 = $model->handleBuy($t_client4, $last_day['preult']);

                        if ($t_client4['actions'] * $last_day['preult'] > 100) {
                            $t_client4 = $model->handleSell($t_client4, $last_day['preult']);
                        }

                        array_push($t_clientDatas, ['date' => $next_day['date'], 'client' => $t_client1]);

                        break;

                    case 1:
                        if ($t_client2['cash'] > 100) {
                            $t_client2 = $model->handleBuy($t_client2, $last_day['preult']);
                        }
                        if ($t_client2['actions'] * $last_day['preult'] > 100) {
                            $t_client2 = $model->handleSell($t_client2, $last_day['preult']);
                        }

                        $t_client3 = $model->handleBuy($t_client3, $last_day['preult']);
                        $t_client3 = $model->handleSell($t_client3, $last_day['preult']);

                        $t_client4 = $model->handleBuy($t_client4, $last_day['preult']);
                        if ($t_client4['actions'] * $last_day['preult'] > 100) {
                            $t_client4 = $model->handleSell($t_client4, $last_day['preult']);
                        }

                        break;

                    case 2:
                        $t_client1 = $model->handleSell($t_client1, $last_day['preult']);
                        $t_client2 = $model->handleSell($t_client2, $last_day['preult']);
                        $t_client3 = $model->handleSell($t_client3, $last_day['preult']);

                        if ($t_client4['actions'] * $last_day['preult'] > 100) {
                            $t_client4 = $model->handleSell($t_client4, $last_day['preult']);
                        }

                        array_push($t_clientDatas, ['date' => $next_day['date'], 'client' => $t_client1]);

                        break;

                    default:
                        break;
                }


                if (($max + 1) > $last_day['state']) {
                    $client1 = $model->handleBuy($client1, $last_day['preult']);
                    $client2 = $model->handleBuy($client2, $last_day['preult']);
                    $client3 = $model->handleBuy($client3, $last_day['preult']);
                    $client4 = $model->handleBuy($client4, $last_day['preult']);

                    if ($client4['actions'] * $last_day['preult'] > 100) {
                        $client4 = $model->handleSell($client4, $last_day['preult']);
                    }

                    array_push($clientDatas, ['date' => $next_day['date'], 'client' => $client1]);
                }

                if (($max + 1) == $last_day['state']) {
                    // $client1 = $model->handleBuy($client1, $last_day['preult']);

                    if ($client2['cash'] > 100) {
                        $client2 = $model->handleBuy($client2, $last_day['preult']);
                    }
                    if ($client2['actions'] * $last_day['preult'] > 100) {
                        $client2 = $model->handleSell($client2, $last_day['preult']);
                    }

                    $client3 = $model->handleBuy($client3, $last_day['preult']);
                    $client3 = $model->handleSell($client3, $last_day['preult']);

                    $client4 = $model->handleBuy($client4, $last_day['preult']);
                    if ($client4['actions'] * $last_day['preult'] > 100) {
                        $client4 = $model->handleSell($client4, $last_day['preult']);
                    }
                }

                if (($max + 1) < $last_day['state']) {
                    $client1 = $model->handleSell($client1, $last_day['preult']);
                    $client2 = $model->handleSell($client2, $last_day['preult']);
                    $client3 = $model->handleSell($client3, $last_day['preult']);

                    if ($client4['actions'] * $last_day['preult'] > 100) {
                        $client4 = $model->handleSell($client4, $last_day['preult']);
                    }

                    array_push($clientDatas, ['date' => $next_day['date'], 'client' => $client1]);
                }

                if (count($next_days) == $consultas - 1) {
                    $t_client5 = $model->handleBuy($t_client5, $last_day['preult']);
                    $t_client5['cash'] = 0;
                } else if (empty($next_days)) {
                    $t_client5 = $model->handleSell($t_client5, $last_day['preult']);
                    $client1 = $model->handleSell($client1, $last_day['preult']);
                    $client2 = $model->handleSell($client2, $last_day['preult']);
                    $client3 = $model->handleSell($client3, $last_day['preult']);
                    $client4 = $model->handleSell($client4, $last_day['preult']);
                    $t_client1 = $model->handleSell($t_client1, $last_day['preult']);
                    $t_client2 = $model->handleSell($t_client2, $last_day['preult']);
                    $t_client3 = $model->handleSell($t_client3, $last_day['preult']);
                    $t_client4 = $model->handleSell($t_client4, $last_day['preult']);
                }

                /* Preparação para a próxima iteração ----------------------------------------------------------------- */
                array_shift($cursor_by_price);
                array_push($cursor_by_price, $next_day);
                // array_shift($cursor_by_price_avg_aux);
                // array_push($cursor_by_price_avg_aux, clone $next_day);
                // $cursor_by_price_avg = ConsultaModel::handleAverages($cursor_by_price_avg_aux, $base); //Calculando médias e tirando as diferenças
            }

            $chart = $model->chartData($next, $intervals, $t_clientDatas, $t_datas);


            return $this->render('resultadoTeste', [
                'acertou' => $acertou,
                'errou' => $errou,
                'acertou_avg' => $acertou_avg,
                'errou_avg' => $errou_avg,
                't_acertou' => $t_acertou,
                't_errou' => $t_errou,
                'consultas' => $consultas,
                'fechamentoData' => $chart['fechamentoData'],
                'avgData' => $chart['avgData'],
                'supData' => $chart['supData'],
                'infData' => $chart['infData'],
                'tendencia' => $chart['tendencia'],
                'clientCash' => $chart['cashData'],
                'clientActions' => $chart['actionsData'],
                't_data' => $chart['t_data'],
                't_cliente1' => $t_client1,
                't_cliente2' => $t_client2,
                't_cliente3' => $t_client3,
                't_cliente4' => $t_client4,
                't_cliente5' => $t_client5,
                'cliente1' => $client1,
                'cliente2' => $client2,
                'cliente3' => $client3,
                'cliente4' => $client4
            ]);
        } else {
            return $this->render('teste');
        }
    }

    public function actionAbout()
    {
        $this->layout = 'navbar';
        return $this->render('about');
    }

    public function actionLogin()
    {
    }

    public function actionLogout()
    {
    }

    public function actionTesteEstados()
    {

        $this->layout = 'navbar';

        $model = new ConsultaModel;
        $post = $_POST;

        if ($model->load($post) && $model->validate() && $model->periodo) {
            Yii::debug("Inicio");
            $start = $model->inicio;
            $consultas = 0;
            $t_acertou = 0;
            $t_errou = 0;
            $next = array();
            $aux = Paper::toIsoDate(\DateTime::createFromFormat('d/m/YH:i:s', $model->final . '24:00:00')->format('U'));
            $next_day = new Paper();
            $clientDatas = [];
            $t_client1 = ['cash' => 100, 'actions' => 0];
            $t_client2 = ['cash' => 100, 'actions' => 0];
            $t_client3 = ['cash' => 100, 'actions' => 0];
            $t_client4 = ['cash' => 100, 'actions' => 0];
            $t_client5 = ['cash' => 100, 'actions' => 0];
            $t_clientDatas = [];
            $t_datas = [];

            $model->states_number = 3;

            $final = $start;

            //Dia de início do conjunto de treinamento
            $start = \DateTime::createFromFormat('d/m/YH:i:s', $start . '24:00:00');

            //O conjunto de treinamento será definido n meses antes do dia a ser previsto
            $start = $start->modify("-$model->periodo $model->metric");

            //Dia final do conjunto de treinamento
            $final = \DateTime::createFromFormat('d/m/YH:i:s', $final . '24:00:00')->modify('-1 day');

            //Passando para o padrão de datas do banco
            $start = Paper::toIsoDate($start->format('U'));

            //Passando para o padrão de datas do banco
            $final = Paper::toIsoDate($final->format('U'));

            $stock = $model->nome;

            //Setup inicial do conjunto de treinamento
            $cursor_by_price = $model->getData($stock, $start, $final);

            $predictStart = \DateTime::createFromFormat('d/m/YH:i:s', $model->inicio . '24:00:00');

            //Busca no banco os dias que serão previstos
            $next_days = $model->getData($stock, Paper::toIsoDate($predictStart->format('U')), $aux);

            $consultas = count($next_days);
            Yii::debug("Conjunto de treinamento pronto");
            $next_day = array_shift($next_days);

            while (1) {

                if (count($next_days) == 0)
                    break;

                //busca o dia seguinte no banco
                $next_day = array_shift($next_days);

                //Se o dia a ser previsto for maior do que o nosso ultimo dia estipulado o laço ou nulo acaba
                if ($next_day['date'] > $aux || $next_day['date'] == null)
                    break;

                $premin = $model->definePremin($cursor_by_price);
                $premax = $model->definePremax($cursor_by_price);

                //vetor que contem a quantidade de elementos em cada estado
                $states = [];

                for ($i = 0; $i < $model->states_number; $i++) {
                    $states[$i] = 0;
                }

                $cursor_by_price[0]["t_state"] = 2;
                $three_states = [0, 0, 0];

                //atribui um estado a partir do preço de fechamento para cada data no conjunto de treinamento
                foreach ($cursor_by_price as $index => $cursor) {
                    if ($index > 0) {
                        $cursor['t_state'] = $model->getThreeState($cursor['preult'], $cursor_by_price[$index - 1]['preult']);
                    }

                    $three_states[$cursor['t_state'] - 1] += 1;
                }

                $three_state_matrix = $model->transitionMatrix($cursor_by_price, $three_states, 3, "t_state");

                //função que constrói o vetor de predição
                $three_state_vector = $model->predictVector($three_state_matrix, $cursor_by_price, 3, "t_state");

                $last_day = $cursor_by_price[count($cursor_by_price) - 1];

                // calcula o estado do dia seguinte
                $next_day['t_state'] = $model->getThreeState($next_day['preult'], $last_day['preult']);

                array_push($next, $next_day);
                $max = 0;
                $t_max = 0;
                $t_vector = $three_state_vector[0];

                //calculando o estado com maior probabilidade no vetor de previsão
                for ($i = 1; $i < 3; $i++) {
                    if ($t_vector[$i] >= $t_vector[$t_max])
                        $t_max = $i;
                }

                if ($t_max === 0)
                    array_push($t_datas, ($last_day['preult'] + $premax['preult']) / 2);
                elseif ($t_max === 1)
                    array_push($t_datas, $last_day['preult']);
                else
                    array_push($t_datas, ($premin['preult'] + $last_day['preult']) / 2);


                if ($next_day['t_state'] == $t_max + 1)
                    $t_acertou++;
                else
                    $t_errou++;
                // Verifica qual dos 3 estados tem maior probabilidade e realiza compra/venda
                switch ($t_max) {
                    case 0:
                        $t_client1 = $model->handleBuy($t_client1, $last_day['preult']);
                        $t_client2 = $model->handleBuy($t_client2, $last_day['preult']);
                        $t_client3 = $model->handleBuy($t_client3, $last_day['preult']);
                        $t_client4 = $model->handleBuy($t_client4, $last_day['preult']);

                        if ($t_client4['actions'] * $last_day['preult'] > 100) {
                            $t_client4 = $model->handleSell($t_client4, $last_day['preult']);
                        }

                        array_push($t_clientDatas, ['date' => $next_day['date'], 'client' => $t_client1]);

                        break;

                    case 1:
                        if ($t_client2['cash'] > 100) {
                            $t_client2 = $model->handleBuy($t_client2, $last_day['preult']);
                        }
                        if ($t_client2['actions'] * $last_day['preult'] > 100) {
                            $t_client2 = $model->handleSell($t_client2, $last_day['preult']);
                        }

                        $t_client3 = $model->handleBuy($t_client3, $last_day['preult']);
                        $t_client3 = $model->handleSell($t_client3, $last_day['preult']);

                        $t_client4 = $model->handleBuy($t_client4, $last_day['preult']);
                        if ($t_client4['actions'] * $last_day['preult'] > 100) {
                            $t_client4 = $model->handleSell($t_client4, $last_day['preult']);
                        }

                        break;

                    case 2:
                        $t_client1 = $model->handleSell($t_client1, $last_day['preult']);
                        $t_client2 = $model->handleSell($t_client2, $last_day['preult']);
                        $t_client3 = $model->handleSell($t_client3, $last_day['preult']);

                        if ($t_client4['actions'] * $last_day['preult'] > 100) {
                            $t_client4 = $model->handleSell($t_client4, $last_day['preult']);
                        }

                        array_push($t_clientDatas, ['date' => $next_day['date'], 'client' => $t_client1]);

                        break;

                    default:
                        break;
                }

                if (count($next_days) == $consultas - 1) {
                    $t_client5 = $model->handleBuy($t_client5, $last_day['preult']);
                    $t_client5['cash'] = 0;
                } else if (empty($next_days)) {
                    $t_client5 = $model->handleSell($t_client5, $last_day['preult']);
                    $t_client1 = $model->handleSell($t_client1, $last_day['preult']);
                    $t_client2 = $model->handleSell($t_client2, $last_day['preult']);
                    $t_client3 = $model->handleSell($t_client3, $last_day['preult']);
                    $t_client4 = $model->handleSell($t_client4, $last_day['preult']);
                }

                //Preparação para a próxima iteração ----------------------------------------------------------------- 
                array_shift($cursor_by_price);
                array_push($cursor_by_price, $next_day);
            }
            return $this->render('resultadoTresEstados', [
                't_acertou' => $t_acertou,
                't_errou' => $t_errou,
                'consultas' => $consultas,
                't_cliente1' => $t_client1,
                't_cliente2' => $t_client2,
                't_cliente3' => $t_client3,
                't_cliente4' => $t_client4,
                't_cliente5' => $t_client5,
            ]);
        } else {
            return $this->render('teste-estados');
        }
    }

    public function actionPredictThreeStates()
    {
        $this->layout = 'navbar';

        $model = new ConsultaModel;
        $post = $_POST;

        if ($model->load($post) && $model->validate()) {
            $start = $model->inicio;
            $final = $model->final;
            $model->states_number = 3;

            //Dia de início do conjunto de treinamento
            $start = \DateTime::createFromFormat('d/m/YH:i:s', $start . '24:00:00'); 

            //Dia final do conjunto de treinamento
            $final = \DateTime::createFromFormat('d/m/YH:i:s', $final . '24:00:00'); 

            //Passando para o padrão de datas do banco
            $start = Paper::toIsoDate($start->getTimestamp()); 
            $final = Paper::toIsoDate($final->getTimestamp()); 

            $stock = $model->nome;

            $cursor_by_price = $model->getData($stock, $start, $final);

            //vetor que contem a quantidade de elementos em cada estado
            $states = []; 

            for ($i = 0; $i < $model->states_number; $i++) {
                $states[$i] = 0;
            }

            $cursor_by_price[0]["t_state"] = 2;

            $three_states = [0, 0, 0];

            //atribui um estado a partir do preço de fechamento para cada data no conjunto de treinamento
            foreach ($cursor_by_price as $index => $cursor) {         
                if ($index > 0) {
                    $cursor['t_state'] = $model->getThreeState($cursor['preult'], $cursor_by_price[$index - 1]['preult']);
                }

                $three_states[$cursor['t_state'] - 1] += 1;
            }

            $three_state_matrix = $model->transitionMatrix($cursor_by_price, $three_states, 3, "t_state");

            //função que constrói o vetor de predição
            $three_state_vector = $model->predictVector($three_state_matrix, $cursor_by_price, 3, "t_state"); 
            
            return $this->render('predict-result-three-states', [
                't_vector' => $three_state_vector,
                'last_price' => $cursor_by_price[count($cursor_by_price) - 1]['preult'],
                'model' => $model,
                'stock' => $model->nome,
                'day' => $final,
            ]);
        }
        return $this->render('predict-three-states', [
            'consultaModel' => $model
        ]);
    }

    public function actionSteadyStatePredict(){
        $this->layout = 'navbar';

        $model = new ConsultaModel;
        $post = $_POST;

        if ($model->load($post) && $model->validate()){
            $start = $model->inicio;
            $final = $model->final;

            //Dia de início do conjunto de treinamento
            $start = \DateTime::createFromFormat('d/m/YH:i:s', $start . '24:00:00'); 

            //Dia final do conjunto de treinamento
            $final = \DateTime::createFromFormat('d/m/YH:i:s', $final . '24:00:00'); 

            //Passando para o padrão de datas do banco
            $start = Paper::toIsoDate($start->getTimestamp()); 
            $final = Paper::toIsoDate($final->getTimestamp()); 

            $action_name = $model->nome;

            $actions_by_date = $model->getData($action_name, $start, $final);

            $actions_by_date[0]["t_state"] = 2;

            $three_states = [0, 0, 0];

            //atribui um estado a partir do preço de fechamento para cada data no conjunto de treinamento
            foreach ($actions_by_date as $index => $cursor) {         
                if ($index > 0) {
                    $cursor['t_state'] = $model->getThreeState($cursor['preult'], $actions_by_date[$index - 1]['preult']);
                }

                $three_states[$cursor['t_state'] - 1] += 1;
            }

            $three_state_matrix = $model->transitionMatrix($actions_by_date, $three_states, 3, "t_state");

            /*$matrix = [
                [0.20,0.30,0.50],
                [0.10,0.00,0.90],
                [0.55,0.00,0.45],
            ];*/

            /*
            Criar um laço e verificando de 5 em 5 se a matriz convergiu, caso tenha convergido
            se verifica para valores menores, caso não se verifica para valores maiores até que se
            encontre um valor em que a matriz convergiu.
            */

            $Matrix = MatrixFactory::create($three_state_matrix);


            echo $model->getSteadyState($Matrix);
        }else{
            return $this->render('steady-state-predict');
        }
    }
}
