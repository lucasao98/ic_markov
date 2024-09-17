<?php

?>

<style>
    .result-acertou {
        color: green;
    }

    .result-errou {
        color: red;
    }
</style>

<body>
    <div class="container">
        <div class="row">
            <h2>Tabela de Heuristica</h2>
            <p>Na tabela de heuristica, consideramos:</p>
            <p>1: Previsão de aumentar</p>
            <p>2: Previsão de permanecer o mesmo valor</p>
            <p>3: Previsão de diminuir</p>
            <h3>Heuristica Pontos antes do ponto de inflexão</h3>
            <p>
                Para realizar a previsão da heuristica para os dias antes do ponto de inflexão,
                nós comparamos a previsão de 3 estados do dia anterior com a previsão do dia atual,
                se forem iguais, a previsão da heuristica será invertida.
                Caso sejam diferentes, permanece a previsão atual.
                Se a previsão de 3 estados do dia anterior e a previsão do dia atual forem iguais,
                então vamos verificar o valor real, ( não o preço da ação, mas se ela aumentou, ficou igual ou diminuiu).
                Se o valor real, for menor ou igual a 2(aumentar, permanecer o mesmo valor), a previsão da heuristica será 3 (diminuir),
                caso o contrario, será 1 (aumentar).
            </p>
            <h3>Heuristica Pontos após o ponto de inflexão</h3>
            <p>
                Nesse caso, iremos verificar caso o valor real seja diferente da previsão atual de 3 estados,
                se forem diferentes, a previsão será o valor real, mas caso sejam iguais a previsão será a previsão atual.
            </p>
        </div>
        <div class="row">
            <div class="col-md-6">
                <h1>Previsão com 3 estados</h1>
                <h3 class="result-acertou"><?= "Acertou: $t_acertou - " . round(($t_acertou / $consultas) * 100, 2) . '%' ?></h3>
                <h3 class="result-errou"><?= "Errou: $t_errou - " . round(($t_errou / $consultas) * 100, 2) . '%' ?></h3>
                <h3><?= "Acompanhou a tendência: $tendencia vezes - " . round(($tendencia / $consultas) * 100, 2) . '%' ?></h3>
                <h3><?= "Compra e venda:" ?></h3>
                <h3><?= "Quantia inicial: R$100" ?></h3>
                <h3><?= "Método 1: Quantia final: R$" . $t_cliente1['cash'] . " e " . $t_cliente1['actions'] . " ações" ?></h3>
                <h3><?= "Método 2: Quantia final: R$" . $t_cliente2['cash'] . " e " . $t_cliente2['actions'] . " ações" ?></h3>
                <h3><?= "Método 3: Quantia final: R$" . $t_cliente3['cash'] . " e " . $t_cliente3['actions'] . " ações" ?></h3>
                <h3><?= "Método 4: Quantia final: R$" . $t_cliente4['cash'] . " e " . $t_cliente4['actions'] . " ações" ?></h3>
                <h3><?= "Método 5: Quantia final: R$" . $t_cliente5['cash'] . " e " . $t_cliente5['actions'] . " ações" ?></h3>
            </div>


            <div class="col-md-6">
                <!-- Resultado -->
                <h1>Previsão da heurística</h1>
                <h3 class="result-acertou"><?= "Acertou: $quantidade_acertos_heuristica - " . $acerto_heuristica . '%' ?></h3>
                <h3 class="result-errou"><?= "Errou: $erros_heuristica - " . round(($erros_heuristica / $consultas) * 100, 2) . '%' ?></h3>
                
            </div>
            
            <div class="col-md-12">
                <!-- Resultado -->
                <h1>Previsão da heurística M3</h1>
                <h3 class="result-acertou"><?= "Acertou: $quantidade_acertos_m3 - " . $percentage_heuristica_m3 . '%' ?></h3>
                <h3 class="result-errou"><?= "Errou: $erro_heuristica_m3 - " . round(($erro_heuristica_m3 / $consultas) * 100, 2) . '%' ?></h3>
                <h3><?= "Compra e venda:" ?></h3>
                <h3><?= "Quantia inicial: R$100" ?></h3>
                <h3><?= "Estratégia 1: Quantia final: R$" . $cliente_heuristica_e1_cash . " e " . $cliente_heuristica_e1_actions . " ações" ?></h3>
                <h3><?= "Estratégia 2: Quantia final: R$" . $cliente_heuristica_e2_cash . " e " . $cliente_heuristica_e2_actions . " ações" ?></h3>
                <h3><?= "Estratégia 3(buy and hold): Quantia final: R$" . $cliente_heuristica_e3_cash . " e " . $cliente_heuristica_e3_actions. " ações" ?></h3>
            </div>

        </div>

        <div class="row mt-5">
            <table class="table">
                <thead>
                    <tr>
                        <h3>
                            Pontos de Mudança
                        </h3>
                    </tr>
                    <tr>
                        <th>Data</th>
                        <th>Heuristica -> Aumentando(1)/Diminuindo(0)</th>
                        <th>Previsão do Dia</th>
                        <th>Valor Real</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data_dots as $data) { ?>
                        <tr>
                            <th><?= $data['date'] ?></th>
                            <th><?= $data['orientation'] ?></th>
                            <th><?= $data['prev_day'] ?></th>
                            <th><?= $data['real_value'] ?></th>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
        <div style="margin-top: 3rem;" class="row">
            <?php

            //Plotagem do gráfico
            $series = [
                [
                    'name' => 'Limite superior previsto',
                    'data' => $supData
                ],
                [
                    'name' => 'Limite inferior previsto',
                    'data' => $infData
                ],
                [
                    'name' => 'Preço de fechamento',
                    'data' => $fechamentoData,
                ]
            ];

            $series2 = [
                [
                    'name' => 'Preço de fechamento',
                    'data' => $fechamentoData
                ],
                [
                    'name' => 'Gráfico de tendência (média)',
                    'data' => $avgData
                ]
            ];

            $series3 = [
                [
                    'name' => 'Rendimento em dinheiro',
                    'data' => $clientCash
                ]
            ];

            $series4 = [
                [
                    'name' => 'Venda e compra de ações',
                    'data' => $clientActions
                ]
            ];

            $series5 = [
                [
                    'name' => 'Preço mínimo',
                    'data' => $t_data,
                ],
                [
                    'name' => 'Preço de fechamento',
                    'data' => $fechamentoData,
                ]
            ];

            echo \onmotion\apexcharts\ApexchartsWidget::widget([
                'type' => 'line', // default area
                'height' => '400', // default 350
                'width' => '1200', // default 100%
                'chartOptions' => [
                    'title' => [
                        'text' => 'Gráfico de previsões',
                        'align' => 'center',
                        'style' => [
                            'fontSize' => '20px',
                            'fontWeight' => 'bold'
                        ]
                    ],
                    'markers' => [
                        'size' => 0
                    ],
                    'chart' => [
                        'toolbar' => [
                            'show' => true,
                            'autoSelected' => 'zoom'
                        ],
                    ],
                    'xaxis' => [
                        'type' => 'datetime',
                        'hideOverlappingLabels' => false,
                        'datetimeFormatter' => [
                            'day' => 'dd MMM yyyy'
                        ]
                        // 'categories' => $categories,
                    ],
                    'yaxis' => [
                        'title' => [
                            'text' => 'Preço (R$)'
                        ]
                    ],
                    'plotOptions' => [
                        'bar' => [
                            'horizontal' => false,
                            'endingShape' => 'rounded'
                        ],
                    ],
                    'dataLabels' => [
                        'enabled' => false
                    ],
                    'stroke' => [
                        'show' => true,
                        'makers' => ['size' => 1]
                    ],
                    'legend' => [
                        'verticalAlign' => 'bottom',
                        'horizontalAlign' => 'center',
                        'labels' => [
                            'useSeruesColors' => true
                        ]
                    ],
                    'makers' => [
                        'size' => 0
                    ],
                    'colors' => ['#0000cc', '#4040ff', '#ffff00']
                ],
                'series' => $series
            ]);

            echo \onmotion\apexcharts\ApexchartsWidget::widget([
                'type' => 'line', // default area
                'height' => '400', // default 350
                'width' => '1200', // default 100%
                'chartOptions' => [
                    'title' => [
                        'text' => 'Gráfico de tendências',
                        'align' => 'center',
                        'style' => [
                            'fontSize' => '20px',
                            'fontWeight' => 'bold'
                        ]
                    ],
                    'markers' => [
                        'size' => 4
                    ],
                    'chart' => [
                        'toolbar' => [
                            'show' => true,
                            'autoSelected' => 'zoom'
                        ],
                    ],
                    'xaxis' => [
                        'type' => 'datetime',
                        'hideOverlappingLabels' => false,
                        'datetimeFormatter' => [
                            'day' => 'dd MMM yyyy'
                        ]
                        // 'categories' => $categories,
                    ],
                    'yaxis' => [
                        'title' => [
                            'text' => 'Preço (R$)'
                        ]
                    ],
                    'plotOptions' => [
                        'bar' => [
                            'horizontal' => false,
                            'endingShape' => 'rounded'
                        ],
                    ],
                    'dataLabels' => [
                        'enabled' => false
                    ],
                    'stroke' => [
                        'show' => true,
                        'makers' => ['size' => 1]
                    ],
                    'legend' => [
                        'verticalAlign' => 'bottom',
                        'horizontalAlign' => 'center',
                        'labels' => [
                            'useSeruesColors' => true
                        ]
                    ],
                    'makers' => [
                        'size' => 0
                    ],
                    'colors' => ['#ffff00', '#0000cc'],
                ],
                'series' => $series2
            ]);
            echo \onmotion\apexcharts\ApexchartsWidget::widget([
                'type' => 'line', // default area
                'height' => '400', // default 350
                'width' => '1200', // default 100%
                'chartOptions' => [
                    'title' => [
                        'text' => 'Quantia adquirida pelo cliente',
                        'align' => 'center',
                        'style' => [
                            'fontSize' => '20px',
                            'fontWeight' => 'bold'
                        ]
                    ],
                    'markers' => [
                        'size' => 4
                    ],
                    'chart' => [
                        'toolbar' => [
                            'show' => true,
                            'autoSelected' => 'zoom'
                        ],
                    ],
                    'xaxis' => [
                        'type' => 'datetime',
                        'hideOverlappingLabels' => false,
                        'datetimeFormatter' => [
                            'day' => 'dd MMM yyyy'
                        ]
                        // 'categories' => $categories,
                    ],
                    'yaxis' => [
                        'title' => [
                            'text' => 'Montante (R$)'
                        ]
                    ],
                    'plotOptions' => [
                        'bar' => [
                            'horizontal' => false,
                            'endingShape' => 'rounded'
                        ],
                    ],
                    'dataLabels' => [
                        'enabled' => false
                    ],
                    'stroke' => [
                        'show' => true,
                        'makers' => ['size' => 1]
                    ],
                    'legend' => [
                        'verticalAlign' => 'bottom',
                        'horizontalAlign' => 'center',
                        'labels' => [
                            'useSeruesColors' => true
                        ]
                    ],
                    'makers' => [
                        'size' => 0
                    ],
                    'colors' => ['#0000cc']
                ],
                'series' => $series3
            ]);

            echo \onmotion\apexcharts\ApexchartsWidget::widget([
                'type' => 'line', // default area
                'height' => '400', // default 350
                'width' => '1200', // default 100%
                'chartOptions' => [
                    'title' => [
                        'text' => 'Quantidade de ações do cliente',
                        'align' => 'center',
                        'style' => [
                            'fontSize' => '20px',
                            'fontWeight' => 'bold'
                        ]
                    ],
                    'markers' => [
                        'size' => 4
                    ],
                    'chart' => [
                        'toolbar' => [
                            'show' => true,
                            'autoSelected' => 'zoom'
                        ],
                    ],
                    'xaxis' => [
                        'type' => 'datetime',
                        'hideOverlappingLabels' => false,
                        'datetimeFormatter' => [
                            'day' => 'dd MMM yyyy'
                        ]
                        // 'categories' => $categories,
                    ],
                    'yaxis' => [
                        'title' => [
                            'text' => 'Quantidade de ações'
                        ]
                    ],
                    'plotOptions' => [
                        'bar' => [
                            'horizontal' => false,
                            'endingShape' => 'rounded'
                        ],
                    ],
                    'dataLabels' => [
                        'enabled' => false
                    ],
                    'stroke' => [
                        'show' => true,
                        'makers' => ['size' => 1]
                    ],
                    'legend' => [
                        'verticalAlign' => 'bottom',
                        'horizontalAlign' => 'center',
                        'labels' => [
                            'useSeruesColors' => true
                        ]
                    ],
                    'makers' => [
                        'size' => 0
                    ],
                    'colors' => ['#0000cc']
                ],
                'series' => $series4
            ]);

            echo \onmotion\apexcharts\ApexchartsWidget::widget([
                'type' => 'line', // default area
                'height' => '400', // default 350
                'width' => '1200', // default 100%
                'chartOptions' => [
                    'title' => [
                        'text' => 'Gráfico de tendências de 3 estados',
                        'align' => 'center',
                        'style' => [
                            'fontSize' => '20px',
                            'fontWeight' => 'bold'
                        ]
                    ],
                    'markers' => [
                        'size' => 0
                    ],
                    'chart' => [
                        'toolbar' => [
                            'show' => true,
                            'autoSelected' => 'zoom'
                        ],
                    ],
                    'xaxis' => [
                        'type' => 'datetime',
                        'hideOverlappingLabels' => false,
                        'datetimeFormatter' => [
                            'day' => 'dd MMM yyyy'
                        ]
                        // 'categories' => $categories,
                    ],
                    'yaxis' => [
                        'title' => [
                            'text' => 'Preço (R$)'
                        ]
                    ],
                    'plotOptions' => [
                        'bar' => [
                            'horizontal' => false,
                            'endingShape' => 'rounded'
                        ],
                    ],
                    'dataLabels' => [
                        'enabled' => false
                    ],
                    'stroke' => [
                        'show' => true,
                        'makers' => ['size' => 1]
                    ],
                    'legend' => [
                        'verticalAlign' => 'bottom',
                        'horizontalAlign' => 'center',
                        'labels' => [
                            'useSeruesColors' => true
                        ]
                    ],
                    'makers' => [
                        'size' => 0
                    ],
                    'colors' => ['#0000cc', '#ffff00']
                ],
                'series' => $series5
            ]);
            ?>
        </div>
    </div>
</body>