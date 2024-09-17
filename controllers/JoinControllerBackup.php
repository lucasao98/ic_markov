<?php

namespace app\controllers;

use yii\base\Controller;
use app\models\ConsultaModel;
use app\models\Paper;
use Exception;

class JoinController extends Controller
{
    public function actionIndex()
    {
        $this->layout = 'navbar';

        $model = new ConsultaModel;
        $post = $_POST;

        if ($model->load($post) && $model->validate() && $model->periodo) {
            $start = $model->inicio;
            $final = $model->final;
            $consultas = 0;
            $acertou = 0;
            $errou = 0;
            $acertou_avg = 0;
            $errou_avg = 0;
            $t_acertou = 0;
            $acertos_heuristica = 0;
            $erros_heuristica = 0;
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
            $current_interval = 0;
            $score_equal_times = 0;

            $arr_with_prob_by_day = [];
            $inflection_dots = [];
            $before_inflection = [];
            $after_inflection = [];
            $inter_test = [];
            $arr_forecast = [];
            $test_errors = [];
            $last_day_inflection_day = false;
            $cont_total_actions = 0;

            $final = $start;
            //Dia de início do conjunto de treinamento
            $start = \DateTime::createFromFormat('d/m/YH:i:s', $start . '24:00:00');

            //O conjunto de treinamento será definido n meses antes do dia a ser previsto
            $start = $start->modify("-$model->periodo $model->metric");

            /* -------------------------------------------------------------------- */
            //Dia final do conjunto de treinamento
            $final = \DateTime::createFromFormat('d/m/YH:i:s', $final . '24:00:00')->modify('-1 day');

            //Passando para o padrão de datas do banco
            $start = Paper::toIsoDate($start->format('U'));

            //Passando para o padrão de datas do banco
            $final = Paper::toIsoDate($final->format('U'));

            $stock = $model->nome;

            //Setup inicial do conjunto de treinamento, contém as ações do intervalo passado pelo usuário
            $cursor_by_price = $model->getData($stock, $start, $final);

            //Setup inicial do conjunto de treinamento
            // $cursor_by_price_avg_aux = $model->getData($stock, $start, $final);

            //Calculando médias e tirando as diferenças
            // $cursor_by_price_avg = ConsultaModel::handleAverages($cursor_by_price_avg_aux, $base);

            // Data da predição
            $predictStart = \DateTime::createFromFormat('d/m/YH:i:s', $model->inicio . '24:00:00');

            //Busca no banco os dias que serão previstos
            $next_days = $model->getData($stock, Paper::toIsoDate($predictStart->format('U')), $aux);
            $consultas = count($next_days);

                /*
                if($stock){
                    $new_final_date = Paper::toIsoDate(\DateTime::createFromFormat('d/m/YH:i:s', $model->final . '24:00:00')->modify('+5 days')->format('U'));
                    $next_days = $model->getData($stock, Paper::toIsoDate($predictStart->format('U')), $new_final_date);
                    $consultas = count($next_days);
                }
                */

                //$file = fopen($stock . ".txt", "w");
                //$file_real = fopen("arquivo_reais.txt", "w");
                //$file_percentage_hits = fopen("percentage_hits.txt", "a");
                $file_percentage_hits = fopen("verifyPercentages.txt", "a");
                //fwrite($file_real, "Dia " . "Previsão" . "\n");
                //fwrite($file_percentage_hits, "Quantidade de Observações ".$model->qtde_obs. "\n");
                //fwrite($file_percentage_hits, "Ação"."  "."3 estados "." "."Heuristica ". "\n");
                /*
                fwrite(
                    $file,
                    "Dia Antes de Inflexão," .
                        "Previsão Dia Antes," .
                        "Data Inflexão," .
                        "Previsão Inflexão," .
                        "Heuristica Antes Inflexão," .
                        "Dia Após Inflexão," .
                        "Previsão Dia após inflexão," .
                        "Heuristica," .
                        "Real," .
                        "\n"
                );*/

                while (1) {

                    if (count($next_days) == 0)
                        break;

                    //busca no array a ação do dia seguinte
                    $next_day = array_shift($next_days);

                    //Se o dia a ser previsto for maior do que o nosso ultimo dia estipulado o laço ou nulo acaba
                    if ($next_day['date'] > $aux || $next_day['date'] == null)
                        break;

                    // Busca e guarda o menor valor do array e o maior de todas as ações buscadas.
                    $premin = $model->definePremin($cursor_by_price);
                    $premax = $model->definePremax($cursor_by_price);

                    //calculo do intervalo
                    $interval = ($premax['preult'] - $premin['preult']) / $model->states_number;

                    // foreach ($cursor_by_price_avg as $avg) {
                    //     $avg['preult'] += $last_day['preult'];
                    // }

                    // $premin_avg = $model->definePremin($cursor_by_price_avg);
                    // $premax_avg = $model->definePremax($cursor_by_price_avg);

                    //calculo do intervalo
                    // $interval_avg = abs($premin_avg['preult'] - $premax_avg['preult']) / $model->states_number;



                    $states = []; //vetor que contem a quantidade de elementos em cada estado
                    $states_avg = []; //vetor que contem a quantidade de elementos em cada estado
                    for ($i = 0; $i < $model->states_number; $i++) {
                        $states[$i] = 0;
                        // $states_avg[$i] = 0;
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

                    // foreach ($cursor_by_price_avg as $index => $cursor) { //atribui um estado a partir do preço de fechamento para cada data no conjunto de treinamento
                    //     $cursor['state'] = $model->getState($cursor['preult'], $premin_avg['preult'], $interval_avg, $model->states_number);
                    //     if ($cursor['state'] != 0)
                    //         $states_avg[$cursor['state'] - 1] += 1;
                    // }

                    $three_state_matrix = $model->transitionMatrix($cursor_by_price, $three_states, 3, "t_state");

                    //função que constrói o vetor de predição
                    $three_state_vector = $model->predictVector($three_state_matrix, $cursor_by_price, 3, "t_state");

                    //função que constrói a matriz de transição
                    $matrix = $model->transitionMatrix($cursor_by_price, $states, $model->states_number, "state");

                    //função que constrói o vetor de predição
                    $vector = $model->predictVector($matrix, $cursor_by_price, $model->states_number, "state");

                    // $matrix_avg = $model->transitionMatrix($cursor_by_price_avg, $states_avg, $model->states_number, "state"); //função que constrói a matriz de transição
                    // $vector_avg = $model->predictVector($matrix_avg, $cursor_by_price_avg, $model->states_number, "state"); //função que constrói o vetor de predição
                    /* Validação ----------------------------------------------------------------- */

                    $before_day = $cursor_by_price[count($cursor_by_price) - 2];
                    $last_day = $cursor_by_price[count($cursor_by_price) - 1];

                    // calcula o estado do dia seguinte
                    $next_day['state'] = $model->getState($next_day['preult'], $premin['preult'], $interval, $model->states_number);

                    // calcula o estado do dia seguinte
                    $next_day['t_state'] = $model->getThreeState($next_day['preult'], $last_day['preult']);

                    // calcula o estado do dia seguinte
                    // $next_day['state_avg'] = $model->getState($next_day['preult'], $premin_avg['preult'], $interval_avg, $model->states_number);

                    array_push($next, $next_day);
                    $max = 0;
                    // $max_avg = 0;
                    $t_max = 0;
                    $vector = $vector[0];

                    // $vector_avg = $vector_avg[0];
                    $t_vector = $three_state_vector[0];

                    //calculando o estado com maior probabilidade no vetor de previsão
                    for ($i = 1; $i < $model->states_number; $i++) {
                        if ($vector[$i] >= $vector[$max])
                            $max = $i;
                    }

                    //calculando o estado com maior probabilidade no vetor de previsão
                    for ($i = 1; $i < $model->states_number; $i++) {
                        // if ($vector_avg[$i] >= $vector_avg[$max_avg])
                        $max_avg = $i;
                    }

                    //calculando o estado com maior probabilidade no vetor de previsão
                    for ($i = 1; $i < 3; $i++) {
                        if ($t_vector[$i] >= $t_vector[$t_max])
                            $t_max = $i;
                    }

                    array_push($arr_with_prob_by_day, [
                        'day' => $next_day['date']->toDateTime()->format('d/m/Y'),
                        'prob_day' => $t_max + 1,
                        'three_state_real_before' => $next_day['t_state'],
                    ]);

                    //fwrite($file_real, $next_day['date']->toDateTime()->format('d/m/Y') . " " . $next_day['t_state'] . "\n");

                    if ($t_max === 0)
                        array_push($t_datas, ($last_day['preult'] + $premax['preult']) / 2);
                    elseif ($t_max === 1)
                        array_push($t_datas, $last_day['preult']);
                    else
                        array_push($t_datas, ($premin['preult'] + $last_day['preult']) / 2);

                    array_push($intervals, $model->getInterval($premin['preult'], $interval, $max));
                    array_push($arr_forecast, [
                        $t_max
                    ]);

                    // Começa a verificar quando o array intervals tiver mais de uma posição, no caso o intervalo n e n+1.
                    if (count($intervals) > 1) {

                        $current_interval = $intervals[count($intervals) - 1]; // Intervalo atual
                        $last_interval = $intervals[count($intervals) - 2]; // Intervalo atual

                        // Verifica se a contagem de intervalos se manteve igual. Se for maior ou igual ao valor digitado entra no else if.
                        if ($score_equal_times < $model->qtde_obs) {
                            if ($last_day_inflection_day) {
                                $inter_test[count($inter_test) - 1]['after_inflection_day'] = $next_day['date']->toDateTime()->format('d/m/Y');
                                $inter_test[count($inter_test) - 1]['after_inflection_prob'] = $arr_with_prob_by_day[$cont_total_actions - 1]['prob_day'];
                                $inter_test[count($inter_test) - 1]['prev_heur'] = $model->forecastHeuristicAfterInflection($arr_with_prob_by_day[$cont_total_actions - 1]['prob_day'], $t_max + 1,$arr_with_prob_by_day[$cont_total_actions - 1]['three_state_real_before']);
                                $inter_test[count($inter_test) - 1]['price_after_inflection'] = $next_day['preult'];
                                /*
                                fwrite(
                                    $file,
                                    $next_day['date']->toDateTime()->format('d/m/Y') . " " .
                                        $arr_with_prob_by_day[$cont_total_actions - 1]['prob_day'] .
                                        " " .
                                        $inter_test[count($inter_test) - 1]['prev_heur'] . " " . $next_day['t_state'] . "\n"
                                );
                                */

                                if ($next_day['t_state'] == (end($inter_test)['prev_heur'])) {
                                    $acertos_heuristica++;
                                }else{
                                    array_push($test_errors,[
                                        'error_date' => $next_day['date']->toDateTime()->format('d/m/Y'),
                                        'prev_heuristica' => (end($before_inflection)['prev_heur']),
                                        'current_prev' => $next_day['t_state']
                                    ]);
                                    //$erros_heuristica++;
                                }

                                $last_day_inflection_day = false;
                            } else {
                                if ($next_day['t_state'] == ($t_max + 1)) {
                                    $acertos_heuristica++;
                                }
                            }

                            if (($current_interval[0] == $last_interval[0]) && ($current_interval[1] == $last_interval[1])) {
                                $score_equal_times++;
                            } else {
                                $score_equal_times = 0;
                            }
                        } else if ($score_equal_times >= $model->qtde_obs) {
                            // Nessa parte são guardados os pontos de inflexão e os dias antes e depois.
                            // Verifica se o proximo intervalo é igual ao anterior, se for, continua somando até achar intervalos diferentes
                            if ($current_interval[0] == $last_interval[0] && $current_interval[1] == $last_interval[1]) {
                                $score_equal_times++;
                                if ($next_day['t_state'] == ($t_max + 1)) {
                                    $acertos_heuristica++;
                                }
                            } else {
                                // Se os intervalos forem diferentes guardamos os valores do ponto de inflexão, um dia antes e um dia após.
                                array_push($inflection_dots, [
                                    'sup' => $current_interval[1], // Intervalo Superior
                                    'inf' => $current_interval[0], // Intervalo Inferior
                                    'date' => $next_day['date']->toDateTime()->format('d/m/Y'), // Dia do Ponto de Inflexão
                                ]);

                                array_push($inter_test, [
                                    'day' => $next_day['date']->toDateTime()->format('d/m/Y'),
                                    //'prob' => $t_max + 1,
                                    'prob' => $next_day['t_state'],
                                    'price_inflection' => $next_day['preult'],
                                ]);

                                $last_day_inflection_day = true;

                                array_push($before_inflection, [
                                    'day_before_inflection' => $last_day['date']->toDateTime()->format('d/m/Y'), // Dia antes do ponto de inflexão
                                    'prob_day_before_inflection' => $arr_with_prob_by_day[$cont_total_actions - 1]['prob_day'], // Maior probabilidade no vetor de previsão do dia anterior
                                    'day_inflection' => $next_day['date']->toDateTime()->format('d/m/Y'), // Dia do ponto de inflexão
                                    'prob_day_inflection' => $t_max + 1, // Maior probabilidade no vetor de previsão do dia atual,
                                    'prev_heur' => $model->forecastHeuristicBeforeInflection($arr_with_prob_by_day[$cont_total_actions - 1]['prob_day'], $t_max + 1,$arr_with_prob_by_day[$cont_total_actions - 1]['three_state_real_before'])
                                ]);
                                /*
                                fwrite(
                                    $file,
                                    $last_day['date']->toDateTime()->format('d/m/Y') . " " .
                                        $arr_with_prob_by_day[$cont_total_actions - 1]['prob_day'] . " " .
                                        $next_day['date']->toDateTime()->format('d/m/Y') . " " .
                                        ($t_max + 1) . " " . end($before_inflection)['prev_heur'] . " "
                                );
                                */

                                //$heur_state = $model->getThreeState($last_day['preult'], $before_day['preult']);
                                if ($next_day['t_state'] == (end($before_inflection)['prev_heur'])) {
                                    $acertos_heuristica++;
                                }else{
                                    array_push($test_errors,[
                                        'error_date' => $next_day['date']->toDateTime()->format('d/m/Y'),
                                        'prev_heuristica' => (end($before_inflection)['prev_heur']),
                                        'current_prev' => $next_day['t_state']
                                    ]);
                                    //$erros_heuristica++;
                                }
                                $score_equal_times = 0;
                            }
                        }
                    } else {
                        if ($next_day['t_state'] == ($t_max + 1)) {
                            $acertos_heuristica++;
                            //fwrite($file, $next_day['date']->toDateTime()->format('d/m/Y') . "\n");
                        }

                        //var_dump($next_day['date']->toDateTime()->format('d/m/Y'));
                    }

                    if ($next_day['state'] == $max + 1)
                        $acertou++;
                    $errou++;

                    if ($next_day['t_state'] == $t_max + 1) {
                        $t_acertou++;
                    } else {
                        $t_errou++;
                    }


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
                    $cont_total_actions++;
                    // array_shift($cursor_by_price_avg_aux);
                    // array_push($cursor_by_price_avg_aux, clone $next_day);
                    // $cursor_by_price_avg = ConsultaModel::handleAverages($cursor_by_price_avg_aux, $base); //Calculando médias e tirando as diferenças
                }

                $chart = $model->chartData($next, $intervals, $t_clientDatas, $t_datas);
                $percentage_heuristica = ($acertos_heuristica / $consultas) * 100;

                fwrite($file_percentage_hits, $stock . " ".round(($t_acertou / $consultas) * 100, 2)." " . number_format($percentage_heuristica, 2) . "\n");

            return $this->render('result', [
                'data_dots' => $inflection_dots,
                'data_dots_inflection_before' => $before_inflection,
                'data_dots_inflection_after' => $inter_test,
                'acerto_heuristica' => round($percentage_heuristica, 2),
                'erros_heuristica' => ($erros_heuristica),
                'quantidade_acertos_heuristica' => $acertos_heuristica,
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
            return $this->render('index');
        }
}
}