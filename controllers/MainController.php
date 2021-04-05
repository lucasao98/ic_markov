<?php

namespace app\controllers;

use yii\web\Controller;
use app\models\ConsultaModel;
use app\models\Paper;
use Yii;

//date_default_timezone_set("america/bahia");

class MainController extends Controller
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

        if ($model->load($post) && $model->validate()) {
            $start = $model->inicio;
            $final = $model->final;
            $start = \DateTime::createFromFormat('d/m/YH:i:s', $start . '24:00:00'); //Dia de início do conjunto de treinamento
            $final = \DateTime::createFromFormat('d/m/YH:i:s', $final . '24:00:00'); //Dia final do conjunto de treinamento

            $start = Paper::toIsoDate($start->getTimestamp()); //Passando para o padrão de datas do banco
            $final = Paper::toIsoDate($final->getTimestamp()); //Passando para o padrão de datas do banco
            // echo $start . '<br>' . $final;

            $stock = $model->nome;

            $cursor_by_price = $model->PegarDados($stock, $start, $final);
            // print_r($cursor_by_price);

            $premin = $model->DefinirPremin($cursor_by_price);
            $premax = $model->DefinirPremax($cursor_by_price);

            $interval = abs($premin['preult'] - $premax['preult']) / $model->states_number; //calculo do intervalo

            // echo $premin['preult'] . '<br>';
            // echo $premax['preult'] . '<br>';
            // echo $interval . '<br>';

            $states = []; //vetor que contem a quantidade de elementos em cada estado
            for ($i = 0; $i < $model->states_number; $i++) {
                $states[$i] = 0;
            }


            $cursor_by_price[0]["t_state"] = 2;

            $three_states = [0, 0, 0];

            foreach ($cursor_by_price as $index => $cursor) { //atribui um estado a partir do preço de fechamento para cada data no conjunto de treinamento        
                if ($index > 0) {
                    $cursor['t_state'] = $model->getThreeSate($cursor['preult'], $cursor_by_price[$index - 1]['preult']);
                }

                $three_states[$cursor['t_state'] - 1] += 1;

                $cursor['state'] = $model->getState($cursor['preult'], $premin['preult'], $interval, $model->states_number);

                if ($cursor['state'] != 0)
                    $states[$cursor['state'] - 1] += 1;
            }

            $three_state_matrix = $model->transitionMatrix($cursor_by_price, $three_states, 3, "t_state");
            $three_state_vector = $model->predictVector($three_state_matrix, $cursor_by_price, 3, "t_state"); //função que constrói o vetor de predição

            $matrix = $model->transitionMatrix($cursor_by_price, $states, $model->states_number, "state"); //função que constrói a matriz de transição
            $vector = $model->predictVector($matrix, $cursor_by_price, $model->states_number, "state"); //função que constrói o vetor de predição

            return $this->render('vervetor', [
                //'db' => $cursor_by_price,
                'vector' => $vector,
                't_vector' => $three_state_vector,
                'last' => $cursor_by_price[count($cursor_by_price) - 1],
                'last_price' => $cursor_by_price[count($cursor_by_price) - 1]['preult'],
                'model' => $model,
                'stock' => $model->nome,
                'states_number' => $model->states_number,
                'day' => $final,
                'premin' => $premin['preult'],
                'interval' => $interval
            ]);
        } else {

            return $this->render('predict', [
                'consultaModel' => $model
            ]);
        }
    }

    //Função que valida se o estado previsto condiz com o preço real
    public function actionValidate(/*$vector, $stock, $states_number, $day, $premin, $premax, $interval*/)
    {
        $this->layout = 'clean';

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

        $nextDay = Paper::find()->orderBy('date')->where(['=', 'codneg', $stock])->andWhere(['>', 'date', $day])->one(); //busca o dia seguinte no banco
        $nextDay['state'] = ConsultaModel::getState($nextDay['preult'], $premin, $interval, $states_number); // calcula o estado do dia seguinte
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

        if ($nextDay['state'] == $max + 1)
            $result = 'Acertou!';

        else
            $result = 'Errou!';

        switch ($t_max) {
            case 0:
                if ($nextDay['preult'] > $last_price)
                    $result_three_state = 'Acertou!';
                else
                    $result_three_state = 'Errou!';
                break;

            case 1:
                if ($nextDay['preult'] == $last_price)
                    $result_three_state = 'Acertou!';
                else
                    $result_three_state = 'Errou!';
                break;

            case 2:
                if ($nextDay['preult'] < $last_price)
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
            'nextDay' => $nextDay,
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
        $this->layout = 'clean';

        $model = new ConsultaModel;
        $post = $_POST;

        if ($model->load($post) && $model->validate() && $model->periodo) {
            Yii::debug("Inicio");
            $start = $model->inicio;
            $consultas = 0;
            $acertou = 0;
            $errou = 0;
            $t_acertou = 0;
            $t_errou = 0;
            $next = array();
            $intervals = array();
            $aux = Paper::toIsoDate(\DateTime::createFromFormat('d/m/YH:i:s', $model->final . '24:00:00')->format('U'));
            $nextDay = new Paper();
            // $last_prices = array();
            // $next_prices = array();

            $final = $start;
            $start = \DateTime::createFromFormat('d/m/YH:i:s', $start . '24:00:00'); //Dia de início do conjunto de treinamento
            $start = $start->modify("-$model->periodo month"); //O conjunto de treinamento será definido n meses antes do dia a ser previsto
            /* -------------------------------------------------------------------- */
            $final = \DateTime::createFromFormat('d/m/YH:i:s', $final . '24:00:00')->modify('-1 day'); //Dia final do conjunto de treinamento

            $start = Paper::toIsoDate($start->format('U')); //Passando para o padrão de datas do banco
            $final = Paper::toIsoDate($final->format('U')); //Passando para o padrão de datas do banco

            $stock = $model->nome;
            $cursor_by_price = $model->PegarDados($stock, $start, $final); //Setup inicial do conjunto de treinamento

            $predictStart = \DateTime::createFromFormat('d/m/YH:i:s', $model->inicio . '24:00:00');
            $nextDays = $model->PegarDados($stock, Paper::toIsoDate($predictStart->format('U')), $aux); //Busca no banco os dias que serão previstos
            $consultas = count($nextDays);
            Yii::debug("Conjunto de treinamento pronto");

            while (1) {

                if (count($nextDays) == 0)
                    break;

                $nextDay = $nextDays[0]; //busca o dia seguinte no banco
                array_shift($nextDays);

                //Se o dia a ser previsto for maior do que o nosso ultimo dia estipulado o laço ou nulo acaba
                if ($nextDay['date'] > $aux || $nextDay['date'] == null)
                    break;

                $premin = $model->DefinirPremin($cursor_by_price);
                $premax = $model->DefinirPremax($cursor_by_price);

                $interval = abs($premin['preult'] - $premax['preult']) / $model->states_number; //calculo do intervalo

                $states = []; //vetor que contem a quantidade de elementos em cada estado
                for ($i = 0; $i < $model->states_number; $i++) {
                    $states[$i] = 0;
                }

                $cursor_by_price[0]["t_state"] = 2;

                $three_states = [0, 0, 0];

                foreach ($cursor_by_price as $index => $cursor) { //atribui um estado a partir do preço de fechamento para cada data no conjunto de treinamento
                    if ($index > 0) {
                        $cursor['t_state'] = $model->getThreeSate($cursor['preult'], $cursor_by_price[$index - 1]['preult']);
                    }

                    $three_states[$cursor['t_state'] - 1] += 1;

                    $cursor['state'] = $model->getState($cursor['preult'], $premin['preult'], $interval, $model->states_number);
                    if ($cursor['state'] != 0)
                        $states[$cursor['state'] - 1] += 1;
                }

                $three_state_matrix = $model->transitionMatrix($cursor_by_price, $three_states, 3, "t_state");
                $three_state_vector = $model->predictVector($three_state_matrix, $cursor_by_price, 3, "t_state"); //função que constrói o vetor de predição

                $matrix = $model->transitionMatrix($cursor_by_price, $states, $model->states_number, "state"); //função que constrói a matriz de transição
                $vector = $model->predictVector($matrix, $cursor_by_price, $model->states_number, "state"); //função que constrói o vetor de predição
                /* Validação ----------------------------------------------------------------- */

                $nextDay['state'] = $model->getState($nextDay['preult'], $premin['preult'], $interval, $model->states_number); // calcula o estado do dia seguinte

                array_push($next, $nextDay);
                $max = 0;
                $t_max = 0;
                $vector = $vector[0];
                $t_vector = $three_state_vector[0];

                for ($i = 1; $i < $model->states_number; $i++) { //calculando o estado com maior probabilidade no vetor de previsão
                    if ($vector[$i] >= $vector[$max])
                        $max = $i;
                }

                for ($i = 1; $i < 3; $i++) { //calculando o estado com maior probabilidade no vetor de previsão
                    if ($t_vector[$i] >= $t_vector[$t_max])
                        $t_max = $i;
                }

                array_push($intervals, $model->getInterval($premin['preult'], $interval, $max));
                if ($nextDay['state'] == $max + 1)
                    $acertou++;
                else
                    $errou++;

                $last_price =  $cursor_by_price[count($cursor_by_price) - 1]['preult'];
                // $last_date =  intval(($cursor_by_price[count($cursor_by_price) - 1]['date']->toDateTime())->format('U') . '000');
                // $next_date =  intval(($nextDay['date']->toDateTime())->format('U') . '000');

                // array_push($next_prices, [$next_date, $nextDay['preult']]);
                // array_push($last_prices, [$last_date, $last_price]);

                switch ($t_max) {
                    case 0:
                        if ($nextDay['preult'] > $last_price)
                            $t_acertou++;
                        else
                            $t_errou++;
                        break;

                    case 1:
                        if ($nextDay['preult'] == $last_price)
                            $t_acertou++;
                        else
                            $t_errou++;
                        break;

                    case 2:
                        if ($nextDay['preult'] < $last_price)
                            $t_acertou++;
                        else
                            $t_errou++;
                        break;

                    default:
                        break;
                }
                /* Preparação para a próxima iteração ----------------------------------------------------------------- */

                array_shift($cursor_by_price);
                array_push($cursor_by_price, $nextDay);
            }

            $chart = $model->chartData($next, $intervals);


            return $this->render('resultadoTeste', [
                'acertou' => $acertou,
                'errou' => $errou,
                't_acertou' => $t_acertou,
                't_errou' => $t_errou,
                'consultas' => $consultas,
                'fechamentoData' => $chart['fechamentoData'],
                'avgData' => $chart['avgData'],
                'supData' => $chart['supData'],
                'infData' => $chart['infData'],
                'tendencia' => $chart['tendencia']
                // 'last_prices' => $last_prices,
                // 'next_prices' => $next_prices
                // 'next' => $next,
                // 'intervals' => $intervals
            ]);
        } else {
            return $this->render('teste');
        }
    }

    public function actionAbout()
    {
        $this->layout = 'clean';
        return $this->render('about');
    }

    public function actionLogin()
    {
    }

    public function actionLogout()
    {
    }
}
