<?php

use app\assets\AppAsset;

AppAsset::register($this);
?>
<div class="container">
    <div class="row">
        <div class="col">
            <h2>Resultado do Cálculo do Estado Estável para Intervalos</h2>
        </div>

    </div>
    <div class="row">
        <div class="col">
            <h3>Total de Ações Coletadas na previsão: <?= $total_actions ?></h3>
            <h3>Acertos: <?= $hits ?></h3>
            <h3>Erros: <?= $errors ?></h3>
            <h3>Média de acertos: <?= $mean_hits ?></h3>
        </div>
    </div>
    <div class="row">
        <div class="col">
            <a class="btn btn-warning" href="steady-state-test">Voltar</a>
        </div>
    </div>
</div>