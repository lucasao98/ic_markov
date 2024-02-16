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
        return $this->render('predict-interval', [
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

    public function actionPredictThreeStatesTest()
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
            //Setup inicial do conjunto de treinamento
            $cursor_by_price = $model->getData($stock, $start, $final);

            $predictStart = \DateTime::createFromFormat('d/m/YH:i:s', $model->inicio . '24:00:00');
            //Busca no banco os dias que serão previstos\
            $next_days = $model->getData($stock, Paper::toIsoDate($predictStart->format('U')), $aux);
            $consultas = count($next_days);
            Yii::debug("Conjunto de treinamento pronto");

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
                //vetor que contem a quantidade de elementos em cada estado
                $states_avg = [];
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

                /* Validação ----------------------------------------------------------------- */

                $last_day = $cursor_by_price[count($cursor_by_price) - 1];
                // calcula o estado do dia seguinte
                $next_day['t_state'] = $model->getThreeState($next_day['preult'], $last_day['preult']);

                array_push($next, $next_day);
                $max = 0;
                $t_max = 0;
                $t_vector = $three_state_vector[0];

                for ($i = 1; $i < $model->states_number; $i++) { //calculando o estado com maior probabilidade no vetor de previsão
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
            }

            $chart = $model->chartDataThreeStates($next, $t_clientDatas, $t_datas);


            return $this->render('predict-result-three-states', [
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
                't_data' => $chart['t_data'],
                't_cliente1' => $t_client1,
                't_cliente2' => $t_client2,
                't_cliente3' => $t_client3,
                't_cliente4' => $t_client4,
                't_cliente5' => $t_client5,
            ]);
        } else {
            return $this->render('predict-three-states-test');
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

    public function actionSteadyStatePredict()
    {
        $this->layout = 'navbar';

        $model = new ConsultaModel;
        $post = $_POST;

        if ($model->load($post) && $model->validate()) {
            $start = $model->inicio;
            $final = $model->final;

            //Dia de início do conjunto de treinamento
            $start = \DateTime::createFromFormat('d/m/YH:i:s', $start . '24:00:00')->modify('-1 day');

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

            $Matrix = MatrixFactory::create($three_state_matrix);

            $result = $model->getSteadyState($Matrix);

            if ($result === 0) {
                $session = Yii::$app->session;
                $alert = $session->setFlash('error', 'A matriz de probabilidades não possui um limite de distribuição para esse intervalo, ou existem mais de um limite. ' . '<strong> Por favor troque o intervalo ou escolha outra ação.</strong>');
                return $this->render('steady-state-predict');
            }

            $day_after_iteractions = $model->getActionAfterIterations($model->nome, $final, $model->final,  $result[1]);

            return $this->render('steady-state-result', [
                'up' => $result[0][0],
                'the_same' => $result[0][1],
                'down' => $result[0][2],
                'vector' => $result[0],
                'times' => $result[1],
                'initial_date' => $model->inicio,
                'final_date' => $model->final,
                'data_pos_iteracao' => $day_after_iteractions[0]['date']->toDateTime()->format('d/m/Y')
            ]);
        } else {
            return $this->render('steady-state-predict');
        }
    }

    public function actionSteadyStateTest()
    {
        $this->layout = 'navbar';

        $model = new ConsultaModel;
        $post = $_POST;

        if ($model->load($post) && $model->validate() && $model->periodo) {
            $start = $model->inicio;
            $final = $model->final;
            $next_day = new Paper();
            $aux_data_final = Paper::toIsoDate(\DateTime::createFromFormat('d/m/YH:i:s', $model->final . '24:00:00')->format('U'));
            $final = $start;
            //Dia de início do conjunto de treinamento
            $start = \DateTime::createFromFormat('d/m/YH:i:s', $start . '24:00:00')->modify('-1 day');
            //O conjunto de treinamento será definido n meses antes do dia a ser previsto
            $start = $start->modify("-$model->periodo $model->metric");

            $ano_inicio = explode('/', $start->format('d/m/Y'));
            //Dia final do conjunto de treinamento
            $final = \DateTime::createFromFormat('d/m/YH:i:s', $final . '24:00:00')->modify('-1 day');
            //Passando para o padrão de datas do banco
            $start = Paper::toIsoDate($start->format('U'));
            $final = Paper::toIsoDate($final->format('U'));

            $action_name = $model->nome;

            $actions_to_train = $model->getData($action_name, $start, $final);
            $predictStart = \DateTime::createFromFormat('d/m/YH:i:s', $model->inicio . '24:00:00');
            $predictEnd = \DateTime::createFromFormat('d/m/YH:i:s', $model->final . '24:00:00');
            //Busca no banco os dias que serão previstos\
            $next_days = $model->getData($action_name, Paper::toIsoDate($predictStart->format('U')), $aux_data_final);
            $ano_fim = explode('/', $predictStart->format('d/m/Y'));
            $model->createFile("../assets/" . $action_name . " _ " . $ano_inicio[2] . " - " . $ano_fim[2] . ".csv", ",", [
                'Ação',
                'Data de Previsão',
                'Preço na Data de Previsão',
                'Dia Inicial da matriz de transição',
                'Dia Final da matriz de transição',
                'Preço na Data Após n dias',
                'Dia após n iterações',
                'Iterações',
                'Probabilidade de Subir',
                'Probabilidade de Permanecer o Valor',
                'Probabilidade de Cair',
                'Acerto'
            ]);
            // Converter de timestamp no formato do MongoDb para formato d/m/Y string
            //var_dump($actions_to_train[count($actions_to_train)-1]['date']->toDateTime()->format('d/m/Y'));

            while ($actions_to_train[0]['date']->toDateTime()->format('d/m/Y') != $predictEnd) {

                // Guarda o proximo dia que será previsto na varaivel $next_day
                $next_day = array_shift($next_days);

                //Se o dia a ser previsto for maior do que o nosso ultimo dia estipulado o laço ou nulo acaba
                try {
                    if ($next_day['date'] > $aux_data_final || $next_day['date'] == null)
                        break;
                } catch (\Throwable $th) {
                    break;
                }


                //vetor que contem a quantidade de elementos em cada estado
                $states = [];
                //vetor que contem a quantidade de elementos em cada estado
                $states_avg = [];
                for ($i = 0; $i < $model->states_number; $i++) {
                    $states[$i] = 0;
                }

                $actions_to_train[0]["t_state"] = 2;

                $three_states = [0, 0, 0];

                //atribui um estado a partir do preço de fechamento para cada data no conjunto de treinamento
                foreach ($actions_to_train as $index => $cursor) {
                    if ($index > 0) {
                        $cursor['t_state'] = $model->getThreeState($cursor['preult'], $actions_to_train[$index - 1]['preult']);
                    }

                    $three_states[$cursor['t_state'] - 1] += 1;
                }

                $three_state_matrix = $model->transitionMatrix($actions_to_train, $three_states, 3, "t_state");

                $Matrix = MatrixFactory::create($three_state_matrix);

                $result = $model->getSteadyState($Matrix);
                $last_day = $actions_to_train[count($actions_to_train) - 1];
                $day_after_iteractions = $model->getActionAfterIterations(
                    $action_name,
                    $next_day['date'],
                    $next_day['date']->toDateTime()->format('d/m/Y'),
                    $result[1]
                );

                $model->writeInFile(
                    "../assets/" . $action_name . " _ " . $ano_inicio[2] . " - " . $ano_fim[2] . ".csv",
                    [
                        $action_name,
                        $next_day['date']->toDateTime()->format('d/m/Y'),
                        $next_day['preult'],
                        $actions_to_train[0]['date']->toDateTime()->format('d/m/Y'),
                        $actions_to_train[count($actions_to_train) - 1]['date']->toDateTime()->format('d/m/Y'),
                        $day_after_iteractions[0]['preult'],
                        $day_after_iteractions[0]['date']->toDateTime()->format('d/m/Y'),
                        $result[1],
                        $result[0][0],
                        $result[0][1],
                        $result[0][2],
                        $model->hits($next_day['preult'], $day_after_iteractions[0]['preult'], $result[0][0], $result[0][2])
                    ]
                );

                //Preparação para a próxima iteração -----------------------------------------------------------------
                array_shift($actions_to_train);
                array_push($actions_to_train, $next_day);
                $actions_to_train = $model->getData($action_name, $actions_to_train[0]['date'], $next_day['date']);
            }

            $hits = $model->readFile("../assets/" . $action_name . " _ " . $ano_inicio[2] . " - " . $ano_fim[2] . ".csv")[0];
            $errors = $model->readFile("../assets/" . $action_name . " _ " . $ano_inicio[2] . " - " . $ano_fim[2] . ".csv")[1];
            //$total_actions = $model->readFile("../assets/" . $action_name . " _ " . $ano_inicio[2] . " - " . $ano_fim[2] . ".csv")[2];
            $total_actions = $hits + $errors;
            $mean_hits = number_format((($hits / $total_actions) * 100), 2);
            unlink("../assets/" . $action_name . " _ " . $ano_inicio[2] . " - " . $ano_fim[2] . ".csv");

            return $this->render('steady-state-result-test', [
                'hits' => $hits,
                'errors' => $errors,
                'total_actions' => $total_actions,
                'mean_hits' => $mean_hits . "%"
            ]);
        }

        return $this->render('steady-state-test');
    }

    public function actionFirstPassageTime()
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
            $total_days = date_diff($final, $start);
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

            $Matrix = MatrixFactory::create($three_state_matrix);

            $first_passage_vector = $model->firstPassageTime($Matrix);

            if ($first_passage_vector == 0) {
                $session = Yii::$app->session;
                $alert = $session->setFlash('error', 'Alguma probabilidade da conversão foi zero 
                e ocorreu um erro no cálculo de primeira passagem. Por Favor 
                Escolha outro intervalo.');
                return $this->render('first-passage-time');
            }

            return $this->render('first-passage-time-result', [
                'first_passage' => $first_passage_vector
            ]);
        } else {
            return $this->render('first-passage-time');
        }
    }

    public function actionSteadyStateAutomatic()
    {
        $this->layout = 'navbar';

        $model = new ConsultaModel;
        $post = $_POST;
        $actions = [
            "CESP6",
            "CIEL3",
            "CMIG4",
            "COCE5",
            "CPFE3",
            "CPLE6",
            "CSNA3",
            "CYRE3",
            "DIRR3",
            "ENBR3",
            "ENGI11",
            "EQTL3",
            "EVEN3",
            "EZTC3",
            "FLRY3",
            "GGBR4",
            "GOAU4",
            "HYPE3",
            "IGTA3",
            "ITSA4",
            "ITUB3",
            "ITUB4",
            "JBSS3",
            "JHSF3",
            "LAME3",
            "LAME4",
            "LREN3",
            "MRFG3",
            "MRVE3",
            "MULT3",
            "ODPV3",
            "PSSA3",
            "RENT3",
            "SANB11",
            "SMTO3",
            "SULA11",
            "TRIS3",
            "TRPL4",
            "WEGE3",
        ];

        if ($model->load($post) && $model->validate()) {
            $start = $model->inicio;
            $final = $model->final;
            $next_day = new Paper();
            $aux_data_final = Paper::toIsoDate(\DateTime::createFromFormat('d/m/YH:i:s', $model->final . '24:00:00')->format('U'));
            $final = $start;
            //Dia de início do conjunto de treinamento
            $start = \DateTime::createFromFormat('d/m/YH:i:s', $start . '24:00:00');
            //O conjunto de treinamento será definido n meses antes do dia a ser previsto
            $start = $start->modify("-$model->periodo $model->metric");

            $ano_inicio = explode('/', $start->format('d/m/Y'));
            //Dia final do conjunto de treinamento
            $final = \DateTime::createFromFormat('d/m/YH:i:s', $final . '24:00:00')->modify('-1 day');
            //Passando para o padrão de datas do banco
            $start = Paper::toIsoDate($start->format('U'));
            $final = Paper::toIsoDate($final->format('U'));

            $actions_to_train = $model->getData($model->nome, $start, $final);
            $predictStart = \DateTime::createFromFormat('d/m/YH:i:s', $model->inicio . '24:00:00');
            $predictEnd = \DateTime::createFromFormat('d/m/YH:i:s', $model->final . '24:00:00');
            //Busca no banco os dias que serão previstos\
            $next_days = $model->getData($model->nome, Paper::toIsoDate($predictStart->format('U')), $aux_data_final);
            $ano_fim = explode('/', $predictStart->format('d/m/Y'));
            if (!file_exists("../assets/" . $ano_inicio[2] . "-" . $ano_fim[2] . "- 2 meses - 2 dias/")) {
                mkdir("../assets/" . $ano_inicio[2] . "-" . $ano_fim[2] . "- 2 meses - 2 dias/");
            }

            $model->createFile("../assets/" . $ano_inicio[2] . "-" . $ano_fim[2] . "- 2 meses - 2 dias/" . $model->nome . " _ " . $ano_inicio[2] . " - " . $ano_fim[2] . ".csv", ",", [
                'Ação',
                'Data de Previsão',
                'Preço na Data de Previsão',
                'Dia Inicial da matriz de transição',
                'Dia Final da matriz de transição',
                'Preço na Data Após n dias',
                'Dia após n iterações',
                'Iterações',
                'Probabilidade de Subir',
                'Probabilidade de Permanecer o Valor',
                'Probabilidade de Cair',
                'Acerto',
                'Acerto 1 dia depois de n iterações',
                'Acerto 2 dias depois de n iterações',
            ]);
            // Converter de timestamp no formato do MongoDb para formato d/m/Y string
            //var_dump($actions_to_train[count($actions_to_train)-1]['date']->toDateTime()->format('d/m/Y'));

            while ($actions_to_train[0]['date']->toDateTime()->format('d/m/Y') != $predictEnd) {

                // Guarda o proximo dia que será previsto na varaivel $next_day
                $next_day = array_shift($next_days);

                try {
                    //Se o dia a ser previsto for maior do que o nosso ultimo dia estipulado o laço ou nulo acaba
                    if ($next_day['date'] > $aux_data_final || $next_day['date'] == null)
                        break;
                } catch (\Throwable $th) {
                    break;
                }


                //vetor que contem a quantidade de elementos em cada estado
                $states = [];
                //vetor que contem a quantidade de elementos em cada estado
                $states_avg = [];
                for ($i = 0; $i < $model->states_number; $i++) {
                    $states[$i] = 0;
                }

                $actions_to_train[0]["t_state"] = 2;

                $three_states = [0, 0, 0];

                //atribui um estado a partir do preço de fechamento para cada data no conjunto de treinamento
                foreach ($actions_to_train as $index => $cursor) {
                    if ($index > 0) {
                        $cursor['t_state'] = $model->getThreeState($cursor['preult'], $actions_to_train[$index - 1]['preult']);
                    }

                    $three_states[$cursor['t_state'] - 1] += 1;
                }

                $three_state_matrix = $model->transitionMatrix($actions_to_train, $three_states, 3, "t_state");

                $Matrix = MatrixFactory::create($three_state_matrix);

                $result = $model->getSteadyState($Matrix);
                $last_day = $actions_to_train[count($actions_to_train) - 1];

                $day_after_iteractions = $model->getActionAfterIterations(
                    $model->nome,
                    $next_day['date'],
                    $next_day['date']->toDateTime()->format('d/m/Y'),
                    $result[1]
                );

                if ($day_after_iteractions == null) {
                    break;
                }

                $model->writeInFile(
                    "../assets/" . $ano_inicio[2] . "-" . $ano_fim[2] . "- 2 meses - 2 dias/" . $model->nome . " _ " . $ano_inicio[2] . " - " . $ano_fim[2] . ".csv",
                    [
                        $model->nome,
                        $next_day['date']->toDateTime()->format('d/m/Y'),
                        $next_day['preult'],
                        $actions_to_train[0]['date']->toDateTime()->format('d/m/Y'),
                        $actions_to_train[count($actions_to_train) - 1]['date']->toDateTime()->format('d/m/Y'),
                        $day_after_iteractions[0]['preult'],
                        $day_after_iteractions[0]['date']->toDateTime()->format('d/m/Y'),
                        $result[1],
                        $result[0][0],
                        $result[0][1],
                        $result[0][2],
                        $model->hits($next_day['preult'], $day_after_iteractions[0]['preult'], $result[0][0], $result[0][2]),
                        $model->hits($next_day['preult'], $day_after_iteractions[1]['preult'], $result[0][0], $result[0][2]),
                        $model->hits($next_day['preult'], $day_after_iteractions[2]['preult'], $result[0][0], $result[0][2]),
                    ]
                );

                //Preparação para a próxima iteração -----------------------------------------------------------------
                array_shift($actions_to_train);
                array_push($actions_to_train, $next_day);
                $actions_to_train = $model->getData($model->nome, $actions_to_train[0]['date'], $next_day['date']);
            }
        }


        return $this->render('steady-state-automatic');
    }

    public function actionCalculoMediaDesvio()
    {
        $this->layout = 'navbar';
        $model = new ConsultaModel();

        $actions = [
            "ALPA4",
            "BBAS3",
            "BBDC3",
            "BBDC4",
            "BRFS3",
            "BRKM5",
            "BRML3",
            "BTOW3",
            "CESP6",
            "CIEL3",
            "CMIG4",
            "COCE5",
            "CPFE3",
            "CPLE6",
            "CSNA3",
            "CYRE3",
            "DIRR3",
            "ENBR3",
            "ENGI11",
            "EQTL3",
            "EVEN3",
            "EZTC3",
            "FLRY3",
            "GGBR4",
            "GOAU4",
            "HYPE3",
            "IGTA3",
            "ITSA4",
            "ITUB3",
            "ITUB4",
            "JBSS3",
            "JHSF3",
            "LAME3",
            "LAME4",
            "LREN3",
            "MRFG3",
            "MRVE3",
            "MULT3",
            "ODPV3",
            "PSSA3",
            "RENT3",
            "SANB11",
            "SMTO3",
            "SULA11",
            "TRIS3",
            "TRPL4",
            "WEGE3",
        ];
        $path = "../assets/mean/";

        if (!file_exists($path)) {
            mkdir($path);
        }

        foreach ($actions as $action) {
            $total_hits_from_file = $model->readFile("../assets/2020-2021- 2 meses - 2 dias/" . $action . " _ " . "2020 - 2021.csv");
            try {
                $mean = $total_hits_from_file[0] / ($total_hits_from_file[0] + $total_hits_from_file[1]);
            } catch (\ErrorException $th) {
                $mean = 0;
            }

            $model->writeInFile("../assets/mean/mean_2020-2021- 2 meses - 2 dias.csv", [$action, $mean]);
        }

        $stats = $model->statsFromFile("../assets/mean/mean_2020-2021- 2 meses - 2 dias.csv");

        return $this->render('calculo-media-desvio', [
            'mean' => $stats[0] * 100,
            'sd' => $stats[1],
            'actions_informative' => $stats[2],
            'total_actions' => 47,
        ]);
    }

    public function actionAnnualAnalysis()
    {
        $this->layout = 'navbar';

        $model = new ConsultaModel;
        $actions_selected = 0;
        $current_group = 1;

        $post = $_POST;
        if ($model->load($post)) {
            $start = $model->inicio;

            //Dia de início do conjunto de treinamento
            // 31/10/2010
            $start_matrix_transition = \DateTime::createFromFormat('d/m/YH:i:s', $start . '24:00:00')->modify('- 3 months')->modify('last day of this month');
            $start_matrix_transition_string = \DateTime::createFromFormat('d/m/YH:i:s', $start . '24:00:00')->modify('- 3 months')->modify('last day of this month');

            //Dia final do conjunto de treinamento
            // 31/12/2010
            $final_matrix_transition = \DateTime::createFromFormat('d/m/YH:i:s', $start . '24:00:00')->modify('-1 month')->modify('last day of this month');
            $final_matrix_transition_string = \DateTime::createFromFormat('d/m/YH:i:s', $start . '24:00:00')->modify('-1 month')->modify('last day of this month');


            // Dia inicial do mês de previsão
            $start_day_predict = \DateTime::createFromFormat('d/m/YH:i:s', $start . '24:00:00')->modify('- 1 day');
            $start_day_predict_string = \DateTime::createFromFormat('d/m/YH:i:s', $start . '24:00:00')->modify('- 1 day');

            $final_month_predict = \DateTime::createFromFormat('d/m/YH:i:s', $start . '24:00:00')->modify('+1 day')->modify('last day of this month');
            $final_month_predict_string = \DateTime::createFromFormat('d/m/YH:i:s', $start . '24:00:00')->modify('+1 day')->modify('last day of this month');

            $final_next_month = \DateTime::createFromFormat('d/m/YH:i:s', $start . '24:00:00')->modify('+ 1 month')->modify('last day of this month');
            $final_next_month_string = \DateTime::createFromFormat('d/m/YH:i:s', $start . '24:00:00')->modify('+ 1 month')->modify('last day of this month');

            //Passando para o padrão de datas do banco
            $start_matrix_transition = Paper::toIsoDate($start_matrix_transition->getTimestamp());
            $final_matrix_transition = Paper::toIsoDate($final_matrix_transition->getTimestamp());
            $start_day_predict = Paper::toIsoDate($start_day_predict->getTimestamp());
            $final_next_month = Paper::toIsoDate($final_next_month->getTimestamp());
            $final_month_predict = Paper::toIsoDate($final_month_predict->getTimestamp());

            $inicio_jan = \DateTime::createFromFormat('d/m/YH:i:s', $start . '24:00:00')->modify('-1 day')->modify('- 12 months');
            $inicio_fev = \DateTime::createFromFormat('d/m/YH:i:s', $start . '24:00:00')->modify('-1 day')->modify('- 11 months');
            $inicio_mar = \DateTime::createFromFormat('d/m/YH:i:s', $start . '24:00:00')->modify('-1 day')->modify('- 10 months');
            $inicio_abr = \DateTime::createFromFormat('d/m/YH:i:s', $start . '24:00:00')->modify('-1 day')->modify('- 9 months');
            $inicio_mai = \DateTime::createFromFormat('d/m/YH:i:s', $start . '24:00:00')->modify('-1 day')->modify('- 8 months');
            $inicio_jun = \DateTime::createFromFormat('d/m/YH:i:s', $start . '24:00:00')->modify('-1 day')->modify('- 7 months');
            $inicio_jul = \DateTime::createFromFormat('d/m/YH:i:s', $start . '24:00:00')->modify('-1 day')->modify('- 6 months');
            $inicio_ago = \DateTime::createFromFormat('d/m/YH:i:s', $start . '24:00:00')->modify('-1 day')->modify('- 5 months');
            $inicio_set = \DateTime::createFromFormat('d/m/YH:i:s', $start . '24:00:00')->modify('-1 day')->modify('- 4 months');
            $inicio_out = \DateTime::createFromFormat('d/m/YH:i:s', $start . '24:00:00')->modify('-1 day')->modify('- 3 months');
            $inicio_nov = \DateTime::createFromFormat('d/m/YH:i:s', $start . '24:00:00')->modify('-1 day')->modify('- 2 months');
            $inicio_dez = \DateTime::createFromFormat('d/m/YH:i:s', $start . '24:00:00')->modify('-1 day')->modify('- 1 months');
            $inicio_jan_next_year = \DateTime::createFromFormat('d/m/YH:i:s', $start . '24:00:00')->modify('- 1 day');

            $inicio_jan = Paper::toIsoDate($inicio_jan->getTimestamp());
            $inicio_fev = Paper::toIsoDate($inicio_fev->getTimestamp());
            $inicio_mar = Paper::toIsoDate($inicio_mar->getTimestamp());
            $inicio_abr = Paper::toIsoDate($inicio_abr->getTimestamp());
            $inicio_mai = Paper::toIsoDate($inicio_mai->getTimestamp());
            $inicio_jun = Paper::toIsoDate($inicio_jun->getTimestamp());
            $inicio_jul = Paper::toIsoDate($inicio_jul->getTimestamp());
            $inicio_ago = Paper::toIsoDate($inicio_ago->getTimestamp());
            $inicio_set = Paper::toIsoDate($inicio_set->getTimestamp());
            $inicio_out = Paper::toIsoDate($inicio_out->getTimestamp());
            $inicio_nov = Paper::toIsoDate($inicio_nov->getTimestamp());
            $inicio_dez = Paper::toIsoDate($inicio_dez->getTimestamp());
            $inicio_jan_next_year = Paper::toIsoDate($inicio_jan_next_year->getTimestamp());

            $actions = [
                "ALPA4",
                "BBAS3",
                "BBDC3",
                "BBDC4",
                "BRFS3",
                "BRKM5",
                "BRML3",
                "BTOW3",
                "CESP6",
                "CIEL3",
                "CMIG4",
                "COCE5",
                "CPFE3",
                "CPLE6",
                "CSNA3",
                "CYRE3",
                "DIRR3",
                "ENBR3",
                "ENGI11",
                "EQTL3",
                "EVEN3",
                "EZTC3",
                "FLRY3",
                "GGBR4",
                "GOAU4",
                "HYPE3",
                "IGTA3",
                "ITSA4",
                "ITUB3",
                "ITUB4",
                "JBSS3",
                "JHSF3",
                "LAME3",
                "LAME4",
                "LREN3",
                "MRFG3",
                "MRVE3",
                "MULT3",
                "ODPV3",
                "PSSA3",
                "RENT3",
                "SANB11",
                "SMTO3",
                "SULA11",
                "TRIS3",
                "TRPL4",
                "WEGE3",
            ];
            /*
            $model->createFile("../assets/teste_matriz_mensal_12.csv", ",", [
                'Ação',
                'Grupo',
                'Quantidade de Ações Compradas',
                'Preço de Fechamento no último dia da matriz de transição (R$)',
                'Data Inicial Da Matriz de Transição',
                'Data Final Da Matriz de Transição',
                'Data da venda no mês seguinte',
                'Preço mês seguinte (R$)',
            ]);

            $model->createFile("../assets/teste_valores_mensal_12.csv", ",", [
                'Valor Total Disponível',
                'Valor Total Utilizado na Compra',
                'Valor Total na Venda',
            ]);
            */

            $table_actions = [];

            foreach ($actions as $action_name) {
                //$actions_by_date = $model->getData($action_name, $start_matrix_transition, $final_matrix_transition);

                $action_predict =  $model->getDataByMonth($action_name, $final_month_predict);

                $actions_by_date = [];


                $final_jan = $model->getData($action_name, $inicio_jan, $inicio_fev);
                $final_fev = $model->getData($action_name, $inicio_fev, $inicio_mar);
                $final_mar = $model->getData($action_name, $inicio_mar, $inicio_abr);
                $final_abr = $model->getData($action_name, $inicio_abr, $inicio_mai);
                $final_mai = $model->getData($action_name, $inicio_mai, $inicio_jun);
                $final_jun = $model->getData($action_name, $inicio_jun, $inicio_jul);
                $final_jul = $model->getData($action_name, $inicio_jul, $inicio_ago);
                $final_ago = $model->getData($action_name, $inicio_ago, $inicio_set);
                $final_set = $model->getData($action_name, $inicio_set, $inicio_out);
                $final_out = $model->getData($action_name, $inicio_out, $inicio_nov);
                $final_nov = $model->getData($action_name, $inicio_nov, $inicio_dez);
                $final_dez = $model->getData($action_name, $inicio_dez, $inicio_jan_next_year);

                array_push($actions_by_date, $final_jan[count($final_jan) - 1]);
                array_push($actions_by_date, $final_fev[count($final_fev) - 1]);
                array_push($actions_by_date, $final_mar[count($final_mar) - 1]);
                array_push($actions_by_date, $final_abr[count($final_abr) - 1]);
                array_push($actions_by_date, $final_mai[count($final_mai) - 1]);
                array_push($actions_by_date, $final_jun[count($final_jun) - 1]);
                array_push($actions_by_date, $final_jul[count($final_jul) - 1]);
                array_push($actions_by_date, $final_ago[count($final_ago) - 1]);
                array_push($actions_by_date, $final_set[count($final_set) - 1]);
                array_push($actions_by_date, $final_out[count($final_out) - 1]);
                array_push($actions_by_date, $final_nov[count($final_nov) - 1]);
                array_push($actions_by_date, $final_dez[count($final_dez) - 1]);

                $actions_by_date[0]["t_state"] = 2;

                $three_states = [0, 0, 0];

                foreach ($actions_by_date as $index => $cursor) {
                    if ($index > 0) {
                        $cursor['t_state'] = $model->getThreeState($cursor['preult'], $actions_by_date[$index - 1]['preult']);
                    }
                    $three_states[$cursor['t_state'] - 1] += 1;
                }

                $three_state_matrix = $model->transitionMatrix($actions_by_date, $three_states, 3, "t_state");

                $Matrix = MatrixFactory::create($three_state_matrix);

                $result = $model->getSteadyState($Matrix);

                // Verifica se a probabilidade de aumentar é maior que a probabilidade de diminuir
                if ($result[0][0] > $result[0][2]) {
                    $actions_selected += 1;
                    array_push($table_actions, [
                        $action_name,
                        ($actions_selected - 15) <= 0 ? $current_group : $current_group + 1,
                        number_format(100 / $actions_by_date[count($actions_by_date) - 1]['preult'], 0),
                        $actions_by_date[count($actions_by_date) - 1]['preult'],
                        $start_matrix_transition_string->format('Y-M-d'),
                        $final_matrix_transition_string->format('Y-M-d'),
                        $final_next_month_string->format('Y-M-d'),
                        $action_predict['preult'],
                    ]);
                    /*
                    $model->writeInFile("../assets/teste_matriz_mensal_12.csv", [
                        $action_name,
                        ($actions_selected - 15) <= 0 ? $current_group : $current_group+1,
                        number_format(100/$actions_by_date[count($actions_by_date) -1]['preult'], 0),
                        $actions_by_date[count($actions_by_date) -1]['preult'],
                        $start_matrix_transition_string->format('Y-M-d'),
                        $final_matrix_transition_string->format('Y-M-d'),
                        $final_next_month_string->format('Y-M-d'),
                        $action_predict['preult'],
                    ]);
                    */
                } else {
                    continue;
                }
            }

            $total_gasto = 0;
            $total_selled = 0;
            foreach ($table_actions as $result_action) {
                $total_gasto += ($result_action[2] * $result_action[3]);
                $total_selled += ($result_action[7] * $result_action[2]);
            }
            /*
            $model->writeInFile("../assets/teste_valores_mensal_12.csv", [
                100*count($table_actions),
                number_format($total_gasto,2),
                number_format($total_selled,2),             
            ]);
            */

            return $this->render('analysis-result', [
                'table_results' => $table_actions,
                'total_purchased' => number_format($total_gasto, 2),
                'total_selled' => number_format($total_selled, 2)
            ]);
        } else {
            return $this->render('annual-analysis');
        }
    }
}
