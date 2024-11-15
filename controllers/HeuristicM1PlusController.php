<?php

namespace app\controllers;

use yii\base\Controller;
use app\models\ConsultaModel;
use app\models\Paper;
use app\models\Client;
use app\models\ConsultaHeuristica;

define("prevision_heuristic", True);

class HeuristicM1PlusController extends Controller
{
    public function actionIndex()
    {
        $this->layout = 'navbar';

        $model = new ConsultaHeuristica;
        $post = $_POST;

        $transitions_matrix_fixed = [
            ['metric' => 'months', 'period' => 3],
            ['metric' => 'months', 'period' => 12],
            ['metric' => 'years', 'period' => 3]
        ];

        $total_values_by_period = [
            ['metric' => 'months', 'period' => 3, "total_sum" => 0, "total_sum_heuristic" => 0, "total_years" => 0],
            ['metric' => 'months', 'period' => 12, "total_sum" => 0, "total_sum_heuristic" => 0, "total_years" => 0],
            ['metric' => 'years', 'period' => 3, "total_sum" => 0, "total_sum_heuristic" => 0, "total_years" => 0]
        ];

        $obj_accuracy_rate = [];

        if ($model->load($post) && $model->validate()) {
            $start = \DateTime::createFromFormat('d/m/YH:i:s', $model->final . '24:00:00')->modify('-1 year')->modify('-2 day');
            $final = \DateTime::createFromFormat('d/m/YH:i:s', $model->final . '24:00:00')->modify('-2 day');

            $start_prevision = \DateTime::createFromFormat('d/m/YH:i:s', $model->final . '24:00:00')->modify('-2 day');
            $final_prevision = \DateTime::createFromFormat('d/m/YH:i:s', $model->final . '24:00:00')->modify('+1 year')->modify('-2 day');

            $start_day = $start->format('d/m/Y');
            $prevision_year = intval($final->format('Y'));
            $initial_date_prevision = $start_prevision->format("d/m/Y");
            $final_date_prevision = $final_prevision->format("d/m/Y");


            $action_name = $model->nome;
            $info_predict = [];
            $info_predict_heuristic = [];
            $acertos = 0;
            $erros = 0;
            $percentage = 0;
            $percentage_errors = 0;

            $file_transition_matrix_size = fopen("transition_matrix_size.txt", "w");
            $file_transition_matrix_size_heuristic = fopen("transition_matrix_size_heuristic.txt", "w");
            $file_results = fopen("results_" . ($prevision_year + 1) . ".txt", "a");
            $file_best_period = fopen("best_period_new_periods_" . ($prevision_year + 1) . ".txt", "a");

            fwrite($file_transition_matrix_size, "Ação " . "Taxa de Acertos " . "Ano " . "Metrica " . "Período " . "\n");
            fwrite($file_transition_matrix_size_heuristic, "Ação " . "Taxa de Acertos " . "Ano " . "Metrica " . "Período " . "\n");

            while ($prevision_year >= 2012) {
                $total_years = 0;
                foreach ($transitions_matrix_fixed as $key => $value) {
                    // Coloca a metrica do objeto atual no model->metric
                    $model->metric = $value['metric'];

                    // Coloca o período do objeto atual no model->periodo
                    $model->periodo = $value['period'];

                    $start = $start_day;
                    $final = $model->final;
                    $consultas = 0;
                    $acertou = 0;
                    $errou = 0;
                    $acertou_avg = 0;
                    $errou_avg = 0;
                    $t_acertou = 0;
                    $acertos_heuristica = 0;
                    $acertos_heuristica_m3 = 0;
                    $erro_heuristica_m3 = 0;
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
                    $prev_continuous_up_down = [];
                    $before_inflection = [];
                    $inflection_dots = [];
                    $erros_heuristica = 0;
                    $inter_test = [];
                    $cliente_teste_1 = new Client(0, 100);
                    $cliente_teste_2 = new Client(0, 100);
                    $cliente_teste_3 = new Client(0, 100);
                    $arr_forecast = [];
                    $last_day_inflection_day = false;
                    $cont_total_actions = 0;
                    $array_continuos_growth_date = [
                        'continuos_growth' => 0,
                        'orientation' => -1,
                        'dates' => 0
                    ];
                    $title = "";

                    $final = $start;
                    //Dia de início do conjunto de treinamento
                    $start = \DateTime::createFromFormat('d/m/YH:i:s', $start . '24:00:00');

                    $ano_previsao = intval($start->format('Y'));

                    //O conjunto de treinamento será definido n meses antes do dia a ser previsto
                    $start = $start->modify("-$model->periodo $model->metric");

                    /* -------------------------------------------------------------------- */
                    //Dia final do conjunto de treinamento
                    if ($value['period'] == 15 && $action_name == 'ENGI11') {
                        $final = \DateTime::createFromFormat('d/m/YH:i:s', $model->final . '24:00:00')->modify('-1 day');
                    } else {
                        $final = \DateTime::createFromFormat('d/m/YH:i:s', $final . '24:00:00')->modify('-1 day');
                    }

                    //Passando para o padrão de datas do banco
                    $start = Paper::toIsoDate($start->format('U'));

                    //Passando para o padrão de datas do banco
                    $final = Paper::toIsoDate($final->format('U'));

                    $stock = $model->nome;

                    //Setup inicial do conjunto de treinamento, contém as ações do intervalo passado pelo usuário
                    $cursor_by_price = $model->getData($stock, $start, $final);


                    // Data da predição
                    $predictStart = \DateTime::createFromFormat('d/m/YH:i:s', $start_day . '24:00:00');

                    //Busca no banco os dias que serão previstos
                    $next_days = $model->getData($stock, Paper::toIsoDate($predictStart->format('U')), $aux);
                    $consultas = count($next_days);


                    if (($model->metric == 'year') && (($ano_previsao - $model->periodo) < 2012)) {
                        fwrite($file_transition_matrix_size, $model->nome . " " . "Nenhum Registro encontrado" . " " . $prevision_year . " " . $value['period'] . " " . $value['metric'] . "\n");
                        fwrite($file_transition_matrix_size_heuristic, $model->nome . " " . "Nenhum Registro encontrado" . " " . $prevision_year . " " . $value['period'] . " " . $value['metric'] . "\n");
                        break;
                    } else {
                        $total_years += 1;
                    }

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

                        $three_state_matrix = $model->transitionMatrix($cursor_by_price, $three_states, 3, "t_state");

                        //função que constrói o vetor de predição
                        $three_state_vector = $model->predictVector($three_state_matrix, $cursor_by_price, 3, "t_state");

                        //função que constrói a matriz de transição
                        $matrix = $model->transitionMatrix($cursor_by_price, $states, $model->states_number, "state");

                        //função que constrói o vetor de predição
                        $vector = $model->predictVector($matrix, $cursor_by_price, $model->states_number, "state");

                        /* Validação ----------------------------------------------------------------- */

                        $last_day = $cursor_by_price[count($cursor_by_price) - 1];

                        // Verifica se a quantidade de dias no array next_days é igual ao total de consultas - 1. Ou seja a primeira consulta
                        if (count($next_days) == $consultas - 1) {
                            $cliente_teste_3->buyActions($next_day['preult']);
                        }

                        if (count($next_days) == 1) {
                            $cliente_teste_3->sellActions($next_day['preult']);
                        }

                        // calcula o estado do dia seguinte
                        $next_day['state'] = $model->getState($next_day['preult'], $premin['preult'], $interval, $model->states_number);

                        // calcula o estado do dia seguinte
                        $next_day['t_state'] = $model->getThreeState($next_day['preult'], $last_day['preult']);

                        array_push($next, $next_day);
                        $max = 0;
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

                        // Começa a verificar quando o array intervals tiver mais de uma posição, no caso o intervalo n e n+1, já devem estar dentro do array
                        if (count($intervals) > 1) {

                            $current_interval = $intervals[count($intervals) - 1]; // Intervalo atual
                            $last_interval = $intervals[count($intervals) - 2]; // Intervalo atual

                            // Verifica as continuas subidas ou descidas
                            if (($last_interval[0] < $current_interval[0]) && ($last_interval[1] < $current_interval[1])) {
                                // Caso os intervalos estejam subindo
                                // Se a orientação for -1, não tem nenhum registro de subida ou descida salvo previamente
                                // Então, a orientação 1 é adicionada para informar que tem um registro de subida e o contador é incrementado
                                // Lembrar que: orientação 3 é descida, orientação 1 é subida e -1 significa que ainda não tem uma direção definida
                                if ($array_continuos_growth_date['orientation'] == -1) {
                                    $array_continuos_growth_date['orientation'] = 1;
                                    $array_continuos_growth_date['continuos_growth'] += 1;
                                    $array_continuos_growth_date['date'] = $next_day['date']->toDateTime()->format('d/m/Y');
                                } else if ($array_continuos_growth_date['orientation'] == 1) {
                                    $array_continuos_growth_date['continuos_growth'] += 1;
                                    $array_continuos_growth_date['date'] = $next_day['date']->toDateTime()->format('d/m/Y');


                                    if ($array_continuos_growth_date['continuos_growth'] >= $model->qtde_up_down_constants) {
                                        array_push($prev_continuous_up_down, [
                                            'orientation' => $array_continuos_growth_date['orientation'], // Orientação
                                            'prev_day' => $t_max + 1, // Previsão Do Dia
                                            'date' => $next_day['date']->toDateTime()->format('d/m/Y'), // Dia do Ponto
                                            'real_value' => $next_day['t_state']
                                        ]);
                                        $comparison = $model->compareVerifyWithPrevision($next_day['t_state'], $array_continuos_growth_date['orientation']);
                                        if ($comparison == 1) {
                                            $acertos_heuristica_m3++;
                                        } else {
                                            $erro_heuristica_m3 += 1;
                                        }
                                    }
                                } else {
                                    $array_continuos_growth_date['orientation'] = -1;
                                    if (!$model->verifyContinuosGrowthIsZero($array_continuos_growth_date['orientation'])) {
                                        $array_continuos_growth_date['continuos_growth'] = 0;
                                    }
                                }
                            } else if (($last_interval[0] > $current_interval[0]) && ($last_interval[1] > $current_interval[1])) {
                                // Caso os intervalos estejam descendo
                                if ($array_continuos_growth_date['orientation'] == -1) {
                                    $array_continuos_growth_date['orientation'] = 3;
                                    $array_continuos_growth_date['continuos_growth'] += 1;
                                    $array_continuos_growth_date['date'] = $next_day['date']->toDateTime()->format('d/m/Y');
                                } else if ($array_continuos_growth_date['orientation'] == 3) {
                                    $array_continuos_growth_date['continuos_growth'] += 1;
                                    $array_continuos_growth_date['date'] = $next_day['date']->toDateTime()->format('d/m/Y');

                                    if ($array_continuos_growth_date['continuos_growth'] >= $model->qtde_up_down_constants) {
                                        array_push($prev_continuous_up_down, [
                                            'orientation' => $array_continuos_growth_date['orientation'], // Orientação
                                            'prev_day' => $t_max + 1, // Previsão Do Dia
                                            'date' => $next_day['date']->toDateTime()->format('d/m/Y'), // Dia do Ponto
                                            'real_value' => $next_day['t_state']
                                        ]);
                                        $comparison = $model->compareVerifyWithPrevision($next_day['t_state'], $array_continuos_growth_date['orientation']);
                                        if ($comparison == 1) {
                                            $acertos_heuristica_m3++;
                                        } else {
                                            $erro_heuristica_m3 += 1;
                                        }
                                    }
                                } else {
                                    $array_continuos_growth_date['orientation'] = -1;
                                    if (!$model->verifyContinuosGrowthIsZero($array_continuos_growth_date['continuos_growth'])) {
                                        $array_continuos_growth_date['continuos_growth'] = 0;
                                    }
                                }
                            } else {
                                $array_continuos_growth_date['orientation'] = -1;
                                if (!$model->verifyContinuosGrowthIsZero($array_continuos_growth_date['continuos_growth'])) {
                                    $array_continuos_growth_date['continuos_growth'] = 0;
                                }
                            }
                            // Verifica se a contagem de intervalos se manteve igual. Se for maior ou igual ao valor digitado entra no else if.
                            if ($score_equal_times < $model->qtde_obs) {
                                if ($last_day_inflection_day) {
                                    $inter_test[count($inter_test) - 1]['after_inflection_day'] = $next_day['date']->toDateTime()->format('d/m/Y');
                                    $inter_test[count($inter_test) - 1]['after_inflection_prob'] = $arr_with_prob_by_day[$cont_total_actions - 1]['prob_day'];
                                    $inter_test[count($inter_test) - 1]['prev_heur'] = $model->forecastHeuristicAfterInflection($arr_with_prob_by_day[$cont_total_actions - 1]['prob_day'], $t_max + 1, $arr_with_prob_by_day[$cont_total_actions - 1]['three_state_real_before']);
                                    $inter_test[count($inter_test) - 1]['price_after_inflection'] = $next_day['preult'];

                                    if ($next_day['t_state'] == (end($inter_test)['prev_heur'])) {
                                        $acertos_heuristica++;
                                    } else {
                                        $erros_heuristica += 1;
                                    }

                                    $last_day_inflection_day = false;
                                } else {
                                    if ($next_day['t_state'] == ($t_max + 1)) {
                                        $acertos_heuristica++;
                                    } else {
                                        $erros_heuristica += 1;
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
                                    } else {
                                        $erros_heuristica += 1;
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
                                        'prev_heur' => $model->forecastHeuristicBeforeInflection($arr_with_prob_by_day[$cont_total_actions - 1]['prob_day'], $t_max + 1, $arr_with_prob_by_day[$cont_total_actions - 1]['three_state_real_before'])
                                    ]);

                                    if ($next_day['t_state'] == (end($before_inflection)['prev_heur'])) {
                                        $acertos_heuristica++;
                                    } else {
                                        $erros_heuristica += 1;
                                    }


                                    $score_equal_times = 0;
                                }
                            }
                        } else {
                            if ($next_day['t_state'] == ($t_max + 1)) {
                                $acertos_heuristica++;
                            } else {
                                $erros_heuristica += 1;
                            }
                        }

                        if ($next_day['state'] == $max + 1)
                            $acertou++;
                        $errou++;

                        if ($next_day['t_state'] == $t_max + 1) {
                            $t_acertou++;
                        } else {
                            $t_errou++;
                        }

                        if ($array_continuos_growth_date['orientation'] == 1 || $array_continuos_growth_date['orientation'] == -1) {
                            $cliente_teste_1->buyActions($next_day['preult']);
                            $cliente_teste_2->buyActions($next_day['preult']);
                        } else if ($array_continuos_growth_date['orientation'] == 3 || $array_continuos_growth_date['orientation'] == -1) {
                            if ($cliente_teste_1->getActions() > 0) {
                                $cliente_teste_1->sellActions($next_day['preult']);
                            }
                        }

                        if ($cliente_teste_2->getActions() > 0) {
                            $comparison_price = $next_day['preult'] * $cliente_teste_2->getActions();
                            if ($comparison_price > 100) {
                                $cliente_teste_2->sellActions($next_day['preult']);
                            }
                        }

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
                    }

                    array_push($obj_accuracy_rate, [
                        'accuracy rate' => round(($t_acertou / $consultas) * 100, 2),
                        'stock' => $model->nome,
                        'year' => $prevision_year,
                        'metric' => $model->metric,
                        'period' => $model->periodo,
                        'total_years' => $total_years,
                        'percentage_heuristic' => (($acertos_heuristica + $acertos_heuristica_m3) / $consultas) * 100
                    ]);

                    fwrite($file_transition_matrix_size, $model->nome . " " . round(($t_acertou / $consultas) * 100, 2) . " " . $prevision_year . " " . $value['period'] . " " . $value['metric'] . "\n");
                    fwrite($file_transition_matrix_size_heuristic, $model->nome . " " . round((($acertos_heuristica + $acertos_heuristica_m3) / $consultas) * 100, 2) . " " . $prevision_year . " " . $value['period'] . " " . $value['metric'] . "\n");


                    $percentage_heuristica = ($acertos_heuristica / $consultas) * 100;

                    $percentage_heuristica_m3 = (($acertos_heuristica + $acertos_heuristica_m3) / $consultas) * 100;
                    $acertos_heuristica_m3 = $acertos_heuristica_m3 + $acertos_heuristica;

                    if ($cliente_teste_1->getActions() > 0) {
                        $cliente_teste_1->sellActions($next_day['preult']);
                    }

                    if ($cliente_teste_2->getActions() > 0) {
                        $cliente_teste_2->sellActions($next_day['preult']);
                    }
                }
                $start = \DateTime::createFromFormat('d/m/YH:i:s', $start_day . '24:00:00')->modify('-1 day');
                $final = \DateTime::createFromFormat('d/m/YH:i:s', $model->final . '24:00:00')->modify('-1 day');

                $start = $start->modify("- 1 year");
                $final = $final->modify("- 1 year");

                $prevision_year = intval($final->format('Y') - 1);

                $start_day = $start->format('d/m/Y');
                $model->final = $final->format('d/m/Y');
            }

            foreach ($obj_accuracy_rate as $key => $value) {
                switch ($value) {
                    case $value['metric'] == 'months' && $value['period'] == 3:
                        $total_values_by_period[0]['total_sum'] += $value['accuracy rate'];
                        $total_values_by_period[0]['total_sum_heuristic'] += $value['percentage_heuristic'];
                        $total_values_by_period[0]['total_years'] += 1;
                        break;
                    case $value['metric'] == 'months' && $value['period'] == 12:
                        $total_values_by_period[1]['total_sum'] += $value['accuracy rate'];
                        $total_values_by_period[1]['total_sum_heuristic'] += $value['percentage_heuristic'];
                        $total_values_by_period[1]['total_years'] += 1;
                        break;
                    case $value['metric'] == 'years' && $value['period'] == 3:
                        $total_values_by_period[2]['total_sum'] += $value['accuracy rate'];
                        $total_values_by_period[2]['total_sum_heuristic'] += $value['percentage_heuristic'];
                        $total_values_by_period[2]['total_years'] += 1;
                        break;
                    default:
                        break;
                }
            }

            $hits_percentages = [];
            // Contem as médias dos periodos dos anos anteriores ao ano da previsão
            array_push($info_predict, [
                'stock' => $obj_accuracy_rate[0]['stock'],
                'three_months' => $total_values_by_period[0]['total_years'] == 0 ? 0 : round($total_values_by_period[0]['total_sum'] / $total_values_by_period[0]['total_years'], 2),
                'twelve_months' => $total_values_by_period[1]['total_years'] == 0 ? 0 : round($total_values_by_period[1]['total_sum'] / $total_values_by_period[1]['total_years'], 2),
                'three_years' => $total_values_by_period[2]['total_years'] == 0 ? 0 : round($total_values_by_period[2]['total_sum'] / $total_values_by_period[2]['total_years'], 2),
            ]);

            array_push($info_predict_heuristic, [
                'stock' => $obj_accuracy_rate[0]['stock'],
                'three_months' => $total_values_by_period[0]['total_years'] == 0 ? 0 : round($total_values_by_period[0]['total_sum_heuristic'] / $total_values_by_period[0]['total_years'], 2),
                'twelve_months' => $total_values_by_period[1]['total_years'] == 0 ? 0 : round($total_values_by_period[1]['total_sum_heuristic'] / $total_values_by_period[1]['total_years'], 2),
                'three_years' => $total_values_by_period[2]['total_years'] == 0 ? 0 : round($total_values_by_period[2]['total_sum_heuristic'] / $total_values_by_period[2]['total_years'], 2),
            ]);

            if (prevision_heuristic) {
                //Utilizando os resultados originais de 3 estados
                array_push($hits_percentages, $info_predict_heuristic[0]['three_months']);
                array_push($hits_percentages, $info_predict_heuristic[0]['twelve_months']);
                array_push($hits_percentages, $info_predict_heuristic[0]['three_years']);
                $title = "Heuristica";
            } else {
                /*
                IC - 2023
                Utilizando os resultados da heurística de subidas e descidas constantes.
                */
                array_push($hits_percentages, $info_predict[0]['three_months']);
                array_push($hits_percentages, $info_predict[0]['twelve_months']);
                array_push($hits_percentages, $info_predict[0]['three_years']);
                $title = "3 estados";
            }

            $percentage_max = 0;
            $index_max = -1;
            $metric = '';
            $period = 0;

            foreach ($hits_percentages as $key => $value) {
                if ($value > $percentage_max) {
                    $percentage_max = $value;
                    $index_max = $key;
                }
            }

            $greater_value_found = $model->previsionQuality($hits_percentages[2], $hits_percentages[$index_max]);

            $index_greater_value_founded = array_search($greater_value_found, $hits_percentages);

            switch ($index_greater_value_founded) {
                case 0:
                    $period = 3;
                    $metric = 'months';
                    break;
                case 1:
                    $period = 12;
                    $metric = 'months';
                    break;
                case 2:
                    $period = 3;
                    $metric = 'years';
                    break;
                default:
                    break;
            }

            $prevision_return = $this->actionPredict($initial_date_prevision, $final_date_prevision, $period, $metric, $action_name);

            /*
            Para escrever no arquivo best_period, com as médias do período selecionado
            */
            fwrite(
                $file_best_period,
                $info_predict[0]['stock'] . " " .
                    " 3 meses: " . $hits_percentages[0] .
                    " 12 meses: " . $hits_percentages[1] .
                    " 3 anos: " . $hits_percentages[2] .
                    " Período Escolhido: " . $period . " " . $metric . "\n"
            );

            fwrite(
                $file_results,
                $info_predict[0]['stock'] . " " . $percentage . " " . $prevision_return['cliente_heuristica_e1_cash'] . " " . $prevision_return['cliente_heuristica_e2_cash'] . " " . $prevision_return['cliente_heuristica_e3_cash'] . "\n"
            );
            
            if(prevision_heuristic){
                $acertos = $prevision_return['quantidade_acertos_m3'];
                $erros = $prevision_return['erro_heuristica_m3'];
                $percentage = $prevision_return['percentage_heuristica_m3'];
                $percentage_errors = round(($prevision_return['erro_heuristica_m3']/$prevision_return['consultas'])*100,2);
                $client_1_cash_remaining = $prevision_return['cliente_heuristica_e1_cash'];
                $client_1_actions_remaining = $prevision_return['cliente_heuristica_e1_actions']; 
                $client_2_cash_remaining = $prevision_return['cliente_heuristica_e2_cash'];
                $client_2_actions_remaining = $prevision_return['cliente_heuristica_e2_actions']; 
                $client_3_cash_remaining = $prevision_return['cliente_heuristica_e3_cash'];
                $client_3_actions_remaining = $prevision_return['cliente_heuristica_e3_actions']; 
            }else{
                $acertos = $prevision_return['t_acertou'];
                $erros = $prevision_return['t_errou'];
                $percentage = round(($prevision_return['t_acertou']/$prevision_return['consultas'])*100,2);
                $percentage_errors = round(($prevision_return['t_errou']/$prevision_return['consultas'])*100,2);
                $client_1_cash_remaining = $cliente_teste_1->getCash();
                $client_1_actions_remaining = $cliente_teste_1->getActions(); 
                $client_2_cash_remaining = $cliente_teste_2->getCash();
                $client_2_actions_remaining = $cliente_teste_2->getActions(); 
                $client_3_cash_remaining = $cliente_teste_3->getCash();
                $client_3_actions_remaining = $cliente_teste_3->getActions();
            }
            /*
            * Para retornar o resultado da previsão de 3 estados original basta alterar a chave para t_acertou e t_errou
                Já, para retornar os valores da heurística, basta adicionar quantidade_acertos_m3 e erro_heuristica_m3
            */
            return $this->render('result-predict', [
                'title' => $title,
                'acertos' => $acertos,
                'erros' => $erros,
                'percentage' => $percentage,
                'percentage_errors' => $percentage_errors,
                'cliente_heuristica_e1_cash' => $client_1_cash_remaining,
                'cliente_heuristica_e1_actions' => $client_1_actions_remaining,
                'cliente_heuristica_e2_cash' => $client_2_cash_remaining,
                'cliente_heuristica_e2_actions' => $client_2_actions_remaining,
                'cliente_heuristica_e3_cash' => $client_3_cash_remaining,
                'cliente_heuristica_e3_actions' => $client_3_actions_remaining,
            ]);

            //Retorno para porcentagens de seleção de períodos para montagem da matriz de transição
            // return $this->render('result_mean', [
            //     'info_mean' => $info_predict,
            //     'period' => $period,
            //     'metric' => $metric
            // ]);
        } else {
            return $this->render('index', [
                'flag_error' => false
            ]);
        }
    }

    public function actionPredict($initial_date_prevision, $final_date_prevision, $period, $metric, $action_name)
    {
        $model = new ConsultaModel;
        $start = $initial_date_prevision;
        $final = $final_date_prevision;
        $model->states_number = 4;
        $consultas = 0;
        $acertou = 0;
        $errou = 0;
        $acertou_avg = 0;
        $errou_avg = 0;
        $t_acertou = 0;
        $acertos_heuristica = 0;
        $acertos_heuristica_m3 = 0;
        $erro_heuristica_m3 = 0;
        $t_errou = 0;
        $next = array();
        $intervals = array();
        $aux = Paper::toIsoDate(\DateTime::createFromFormat('d/m/YH:i:s', $final_date_prevision . '24:00:00')->format('U'));
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
        $prev_continuous_up_down = [];
        $before_inflection = [];
        $inflection_dots = [];
        $erros_heuristica = 0;
        $inter_test = [];
        $cliente_teste_1 = new Client(0, 100);
        $cliente_teste_2 = new Client(0, 100);
        $cliente_teste_3 = new Client(0, 100);
        $arr_forecast = [];
        $last_day_inflection_day = false;
        $cont_total_actions = 0;
        $array_continuos_growth_date = [
            'continuos_growth' => 0,
            'orientation' => -1,
            'dates' => 0
        ];
        $model->qtde_up_down_constants = 2;
        $model->qtde_obs = 2;

        $final = $start;
        //Dia de início do conjunto de treinamento
        $start = \DateTime::createFromFormat('d/m/YH:i:s', $start . '24:00:00');

        //O conjunto de treinamento será definido n meses antes do dia a ser previsto
        $start = $start->modify("-$period $metric");

        /* -------------------------------------------------------------------- */
        //Dia final do conjunto de treinamento
        $final = \DateTime::createFromFormat('d/m/YH:i:s', $final . '24:00:00')->modify('-1 day');


        //Passando para o padrão de datas do banco
        $start = Paper::toIsoDate($start->format('U'));

        //Passando para o padrão de datas do banco
        $final = Paper::toIsoDate($final->format('U'));

        $stock = $action_name;

        //Setup inicial do conjunto de treinamento, contém as ações do intervalo passado pelo usuário
        $cursor_by_price = $model->getData($stock, $start, $final);

        // Data da predição
        $predictStart = \DateTime::createFromFormat('d/m/YH:i:s', $initial_date_prevision . '24:00:00');

        //Busca no banco os dias que serão previstos
        $next_days = $model->getData($stock, Paper::toIsoDate($predictStart->format('U')), $aux);
        $consultas = count($next_days);

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

            $three_state_matrix = $model->transitionMatrix($cursor_by_price, $three_states, 3, "t_state");

            //função que constrói o vetor de predição
            $three_state_vector = $model->predictVector($three_state_matrix, $cursor_by_price, 3, "t_state");

            //função que constrói a matriz de transição
            $matrix = $model->transitionMatrix($cursor_by_price, $states, $model->states_number, "state");

            //função que constrói o vetor de predição
            $vector = $model->predictVector($matrix, $cursor_by_price, $model->states_number, "state");

            /* Validação ----------------------------------------------------------------- */

            $last_day = $cursor_by_price[count($cursor_by_price) - 1];

            // Verifica se a quantidade de dias no array next_days é igual ao total de consultas - 1. Ou seja a primeira consulta
            if (count($next_days) == $consultas - 1) {
                $cliente_teste_3->buyActions($next_day['preult']);
            }

            if (count($next_days) == 1) {
                $cliente_teste_3->sellActions($next_day['preult']);
            }

            // calcula o estado do dia seguinte
            $next_day['state'] = $model->getState($next_day['preult'], $premin['preult'], $interval, $model->states_number);

            // calcula o estado do dia seguinte
            $next_day['t_state'] = $model->getThreeState($next_day['preult'], $last_day['preult']);

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

            // Começa a verificar quando o array intervals tiver mais de uma posição, no caso o intervalo n e n+1, já devem estar dentro do array
            if (count($intervals) > 1) {

                $current_interval = $intervals[count($intervals) - 1]; // Intervalo atual
                $last_interval = $intervals[count($intervals) - 2]; // Intervalo atual

                // Verifica as continuas subidas ou descidas
                if (($last_interval[0] < $current_interval[0]) && ($last_interval[1] < $current_interval[1])) {
                    // Caso os intervalos estejam subindo
                    // Se a orientação for -1, não tem nenhum registro de subida ou descida salvo previamente
                    // Então, a orientação 1 é adicionada para informar que tem um registro de subida e o contador é incrementado
                    // Lembrar que: orientação 3 é descida, orientação 1 é subida e -1 significa que ainda não tem uma direção definida
                    if ($array_continuos_growth_date['orientation'] == -1) {
                        $array_continuos_growth_date['orientation'] = 1;
                        $array_continuos_growth_date['continuos_growth'] += 1;
                        $array_continuos_growth_date['date'] = $next_day['date']->toDateTime()->format('d/m/Y');
                    } else if ($array_continuos_growth_date['orientation'] == 1) {
                        $array_continuos_growth_date['continuos_growth'] += 1;
                        $array_continuos_growth_date['date'] = $next_day['date']->toDateTime()->format('d/m/Y');
                        if ($array_continuos_growth_date['continuos_growth'] >= $model->qtde_up_down_constants) {
                            array_push($prev_continuous_up_down, [
                                'orientation' => $array_continuos_growth_date['orientation'], // Orientação
                                'prev_day' => $t_max + 1, // Previsão Do Dia
                                'date' => $next_day['date']->toDateTime()->format('d/m/Y'), // Dia do Ponto
                                'real_value' => $next_day['t_state']
                            ]);
                            $comparison = $model->compareVerifyWithPrevision($next_day['t_state'], $array_continuos_growth_date['orientation']);
                            if ($comparison == 1) {
                                $acertos_heuristica_m3++;
                            } else {
                                $erro_heuristica_m3 += 1;
                            }
                        }
                    } else {
                        $array_continuos_growth_date['orientation'] = -1;
                        if (!$model->verifyContinuosGrowthIsZero($array_continuos_growth_date['orientation'])) {
                            $array_continuos_growth_date['continuos_growth'] = 0;
                        }
                    }
                } else if (($last_interval[0] > $current_interval[0]) && ($last_interval[1] > $current_interval[1])) {
                    // Caso os intervalos estejam descendo
                    if ($array_continuos_growth_date['orientation'] == -1) {
                        $array_continuos_growth_date['orientation'] = 3;
                        $array_continuos_growth_date['continuos_growth'] += 1;
                        $array_continuos_growth_date['date'] = $next_day['date']->toDateTime()->format('d/m/Y');
                    } else if ($array_continuos_growth_date['orientation'] == 3) {
                        $array_continuos_growth_date['continuos_growth'] += 1;
                        $array_continuos_growth_date['date'] = $next_day['date']->toDateTime()->format('d/m/Y');
                        if ($array_continuos_growth_date['continuos_growth'] >= $model->qtde_up_down_constants) {
                            array_push($prev_continuous_up_down, [
                                'orientation' => $array_continuos_growth_date['orientation'], // Orientação
                                'prev_day' => $t_max + 1, // Previsão Do Dia
                                'date' => $next_day['date']->toDateTime()->format('d/m/Y'), // Dia do Ponto
                                'real_value' => $next_day['t_state']
                            ]);
                            $comparison = $model->compareVerifyWithPrevision($next_day['t_state'], $array_continuos_growth_date['orientation']);
                            if ($comparison == 1) {
                                $acertos_heuristica_m3++;
                            } else {
                                $erro_heuristica_m3 += 1;
                            }
                        }
                    } else {
                        $array_continuos_growth_date['orientation'] = -1;
                        if (!$model->verifyContinuosGrowthIsZero($array_continuos_growth_date['continuos_growth'])) {
                            $array_continuos_growth_date['continuos_growth'] = 0;
                        }
                    }
                } else {
                    $array_continuos_growth_date['orientation'] = -1;
                    if (!$model->verifyContinuosGrowthIsZero($array_continuos_growth_date['continuos_growth'])) {
                        $array_continuos_growth_date['continuos_growth'] = 0;
                    }
                }
                // Verifica se a contagem de intervalos se manteve igual. Se for maior ou igual ao valor digitado entra no else if.
                // Verifica se a contagem de intervalos se manteve igual. Se for maior ou igual ao valor digitado entra no else if.
                if ($score_equal_times < $model->qtde_obs) {
                    if ($last_day_inflection_day) {
                        $inter_test[count($inter_test) - 1]['after_inflection_day'] = $next_day['date']->toDateTime()->format('d/m/Y');
                        $inter_test[count($inter_test) - 1]['after_inflection_prob'] = $arr_with_prob_by_day[$cont_total_actions - 1]['prob_day'];
                        $inter_test[count($inter_test) - 1]['prev_heur'] = $model->forecastHeuristicAfterInflection($arr_with_prob_by_day[$cont_total_actions - 1]['prob_day'], $t_max + 1, $arr_with_prob_by_day[$cont_total_actions - 1]['three_state_real_before']);
                        $inter_test[count($inter_test) - 1]['price_after_inflection'] = $next_day['preult'];

                        if ($next_day['t_state'] == (end($inter_test)['prev_heur'])) {
                            $acertos_heuristica++;
                        } else {
                            $erros_heuristica += 1;
                        }

                        $last_day_inflection_day = false;
                    } else {
                        if ($next_day['t_state'] == ($t_max + 1)) {
                            $acertos_heuristica++;
                        } else {
                            $erros_heuristica += 1;
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
                        } else {
                            $erros_heuristica += 1;
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
                            'prev_heur' => $model->forecastHeuristicBeforeInflection($arr_with_prob_by_day[$cont_total_actions - 1]['prob_day'], $t_max + 1, $arr_with_prob_by_day[$cont_total_actions - 1]['three_state_real_before'])
                        ]);

                        if ($next_day['t_state'] == (end($before_inflection)['prev_heur'])) {
                            $acertos_heuristica++;
                        } else {
                            $erros_heuristica += 1;
                        }


                        $score_equal_times = 0;
                    }
                }
            } else {
                if ($next_day['t_state'] == ($t_max + 1)) {
                    $acertos_heuristica++;
                } else {
                    $erros_heuristica += 1;
                }
            }

            if ($next_day['state'] == $max + 1)
                $acertou++;
            $errou++;

            if ($next_day['t_state'] == $t_max + 1) {
                $t_acertou++;
            } else {
                $t_errou++;
            }

            if ($array_continuos_growth_date['orientation'] == 1 || $array_continuos_growth_date['orientation'] == -1) {
                $cliente_teste_1->buyActions($next_day['preult']);
                $cliente_teste_2->buyActions($next_day['preult']);
            } else if ($array_continuos_growth_date['orientation'] == 3 || $array_continuos_growth_date['orientation'] == -1) {
                if ($cliente_teste_1->getActions() > 0) {
                    $cliente_teste_1->sellActions($next_day['preult']);
                }
            }

            if ($cliente_teste_2->getActions() > 0) {
                $comparison_price = $next_day['preult'] * $cliente_teste_2->getActions();
                if ($comparison_price > 100) {
                    $cliente_teste_2->sellActions($next_day['preult']);
                }
            }

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
        }

        $chart = $model->chartData($next, $intervals, $t_clientDatas, $t_datas);
        $percentage_heuristica = ($acertos_heuristica / $consultas) * 100;

        $percentage_heuristica_m3 = (($acertos_heuristica + $acertos_heuristica_m3) / $consultas) * 100;
        $acertos_heuristica_m3 = $acertos_heuristica_m3 + $acertos_heuristica;

        if ($cliente_teste_1->getActions() > 0) {
            $cliente_teste_1->sellActions($next_day['preult']);
        }

        if ($cliente_teste_2->getActions() > 0) {
            $cliente_teste_2->sellActions($next_day['preult']);
        }

        return [
            'cliente_heuristica_e1_cash' => $cliente_teste_1->getCash(),
            'cliente_heuristica_e1_actions' => $cliente_teste_1->getActions(),
            'cliente_heuristica_e2_cash' => $cliente_teste_2->getCash(),
            'cliente_heuristica_e2_actions' => $cliente_teste_2->getActions(),
            'cliente_heuristica_e3_cash' => $cliente_teste_3->getCash(),
            'cliente_heuristica_e3_actions' => $cliente_teste_3->getActions(),
            'quantidade_acertos_m3' => $acertos_heuristica_m3,
            'erro_heuristica_m3' => ($erros_heuristica - $erro_heuristica_m3),
            'percentage_heuristica_m3' => round($percentage_heuristica_m3, 2),
            'erros_heuristica' => $erros_heuristica,
            'data_dots' => $prev_continuous_up_down,
            'data_dots_inflection_before' => $before_inflection,
            'data_dots_inflection_after' => $inter_test,
            'acerto_heuristica' => round($percentage_heuristica, 2),
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
        ];
    }
}
