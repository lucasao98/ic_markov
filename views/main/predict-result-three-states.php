<?php

use yii\helpers\Html;

?>
<div class="container">

    <h3>
        <?=
        '<h1>Previsão usando 3 estados: <h1>';
        ?>
        <h3 class="result-acertou"><?= "Acertou: $t_acertou - " . round(($t_acertou / $consultas) * 100, 2) . '%' ?></h3>
        <h3 class="result-errou"><?= "Errou: $t_errou - " . round(($t_errou / $consultas) * 100, 2) . '%' ?></h3>
        <h3><?= "Acompanhou a tendência: $tendencia vezes - " . round(($tendencia / $consultas) * 100, 2) . '%' ?></h3>
    </h3>
    
    <?php
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
    <a class="btn btn-warning" href="predict-three-states">Voltar</a>
</div>
