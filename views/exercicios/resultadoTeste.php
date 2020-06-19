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
    <h3 class="result-acertou"><?= "Acertou: $acertou - " . round(($acertou / $consultas) * 100, 2) . '%' ?></h3>
    <h3 class="result-errou"><?= "Errou: $errou - " . round(($errou / $consultas) * 100, 2) . '%' ?></h3>

    <h4>
        <?php
        foreach ($dates as $date) {
            echo $date['date']->toDateTime()->format('d/m/Y') . '<br>';
        }

        print_r($intervals);
        ?>
    </h4>

    <?php
        $fechamentoData = array();
        $infData = array();
        $supData = array();
        
        foreach($dates as $date) {
            $formattedDate = intval(($date['date']->toDateTime())->format('U') . '000');
            array_push($fechamentoData, [$formattedDate, $date['preult']]);
        }
        
        foreach($intervals as $i => $interval) {
            $formattedDate = intval(($dates[$i]['date']->toDateTime())->format('U') . '000');
            array_push($infData, [$formattedDate, $interval[0]]);
            array_push($supData, [$formattedDate, $interval[1]]);
        }

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
        
        echo \onmotion\apexcharts\ApexchartsWidget::widget([
            'type' => 'line', // default area
            'height' => '400', // default 350
            'width' => '1200', // default 100%
            'chartOptions' => [
                'markers' => [
                    'size' => 5
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
                ]
            ],
            'series' => $series
        ]);
    ?>
</body>