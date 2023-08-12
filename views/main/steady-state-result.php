<?php

use app\assets\AppAsset;

AppAsset::register($this);
?>
<div class="container">
    <div class="row">
        <div class="col">
            <h2>Resultado do Cálculo do Estado Estável do Dia Seguinte</h2>
        </div>

    </div>
    <div class="row">
        <div class="col">
            <h3>Período para a construção da matriz de transição: <?= $initial_date ?>  à <?= $final_date ?> </h3>
            <h3>A Matriz convergiu em <?= $times ?> dias. No dia <?= $data_pos_iteracao ?></h3>
            <h3>Vetor Resultante: <?= $vector ?></h3>
            <h3>Sendo <?= $up ?> a probabilidade de aumento, <?= $the_same ?> a probabilidade de permanencer o mesmo preço e <?= $down ?> a probabilidade do preço cair.</h3>
        </div>
    </div>
    <div class="row">
        <div class="col">
            <a class="btn btn-warning" href="steady-state-predict">Voltar</a>
        </div>
    </div>
</div>