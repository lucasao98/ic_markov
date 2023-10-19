<?php

use app\assets\AppAsset;

AppAsset::register($this);
?>
<div class="container">
    <div class="row">
        <h1>Cálculo Média Desvio</h1>
    </div>
    <div class="row">
        <h2>Total de Ações Informativas: <?= $actions_informative ?></h2>
        <h2>Total de Ações: <?= $total_actions ?></h2>
        <h2>Média: <?= $mean ?></h2>
        <h2>Desvio Padrão: <?= $sd ?></h2>
    </div>
    <div class="row">
        <a class="btn btn-warning" href="calculo-media-desvio   ">Voltar</a>
    </div>
</div>