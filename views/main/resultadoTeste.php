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
        <!-- Resultado -->
        <h1>Resultado da previsão com estados fixos</h1>
        <h3 class="result-acertou"><?= "Acertou: $acertou - " . round(($acertou / $consultas) * 100, 2) . '%' ?></h3>
        <h3 class="result-errou"><?= "Errou: $errou - " . round(($errou / $consultas) * 100, 2) . '%' ?></h3>
        <h3><?= "Compra e venda:" ?></h3>
        <h3><?= "Quantia inicial: R$100" ?></h3>
        <h3><?= "Método 1: Quantia final: R$" . $cliente1['cash'] . " e " . $cliente1['actions'] . " ações" ?></h3>
        <h3><?= "Método 2: Quantia final: R$" . $cliente2['cash'] . " e " . $cliente2['actions'] . " ações" ?></h3>
        <h3><?= "Método 3: Quantia final: R$" . $cliente3['cash'] . " e " . $cliente3['actions'] . " ações" ?></h3>
        <h3><?= "Método 4: Quantia final: R$" . $cliente4['cash'] . " e " . $cliente4['actions'] . " ações" ?></h3>
        <br />
        
        <!-- <h1>Previsão com médias móveis</h1>
        <h3 class="result-acertou"><?= "Acertou: $acertou_avg - " . round(($acertou_avg / $consultas) * 100, 2) . '%' ?></h3>
        <h3 class="result-errou"><?= "Errou: $errou_avg - " . round(($errou_avg / $consultas) * 100, 2) . '%' ?></h3>
        <br /> -->

        <a class="btn btn-warning" href="teste">Voltar</a>

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
</body>