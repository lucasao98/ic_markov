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
    <!-- Resultado -->
    <h3 class="result-acertou"><?= "Acertou: $acertou - " . round(($acertou / $consultas) * 100, 2) . '%' ?></h3>
    <h3 class="result-errou"><?= "Errou: $errou - " . round(($errou / $consultas) * 100, 2) . '%' ?></h3>
    <h3><?= "Acompanhou a tendência: $tendencia vezes - " . round(($tendencia / $consultas) * 100, 2) . '%' ?></h3>

    <!-- <h4>
        
        // //Datas que foram previstas
        // foreach ($dates as $date) {
        //     echo $date['date']->toDateTime()->format('d/m/Y') . '<br>';
        // }

        // //Intervalo onde os preços previstos se encaixam
        // print_r($intervals);
        
    </h4> -->

    <?php

        //Plotagem do gráfico
        $series = [
            [
                'name' => 'Preço de fechamento',
                'data' => $fechamentoData
            ],
            [
                'name' => 'Limite superior previsto',
                'data' => $supData
            ],
            [
                'name' => 'Limite inferior previsto',
                'data' => $infData
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
        
        echo \onmotion\apexcharts\ApexchartsWidget::widget([
            'type' => 'line', // default area
            'height' => '400', // default 350
            'width' => '1200', // default 100%
            'chartOptions' => [
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
                'colors' => ['#ffff00', '#0000cc', '#4040ff']
            ],
            'series' => $series
        ]);

        echo \onmotion\apexcharts\ApexchartsWidget::widget([
            'type' => 'line', // default area
            'height' => '400', // default 350
            'width' => '1200', // default 100%
            'chartOptions' => [
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
                'colors' => ['#ffff00', '#0000cc']
            ],
            'series' => $series2
        ]);
    ?>
</body>