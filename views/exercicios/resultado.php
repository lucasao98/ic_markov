<?php

use app\models\Paper;

?>

<style>
    .result {
        margin-top: 15px;
        margin-bottom: 15px;
    }
</style>

<h1>
    <?= $resultado ?><br>
</h1>

<h3>
    Valores previstos:
    <p class = result>Estado: <?= $estado ?></p>
    <p class = result>Probabilidade: <?= round($probabilidade*100, 2) ?>%</p>
    Valores do próximo dia:
    <p class = result><?= Paper::toDate($nextDay['date'])->format('d/m/Y') ?> - R$<?=$nextDay['preult']?></p>
</h3>