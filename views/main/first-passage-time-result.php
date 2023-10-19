<?php

use app\assets\AppAsset;

AppAsset::register($this);
?>
<div class="container">
    <div class="row">
        <h2>Resultado do Cálculo de Tempo da Primeira Passagem</h2>
    </div>
    <div class="row">
        <table class="table">
            <thead>
                <tr>
                    Tempo de primeira passagem (em dias)
                </tr>
            </thead>
            <tbody>
                <tr>
                    <th>Estados</th>
                    <th>Up</th>
                    <th>Same</th>
                    <th>Down</th>
                </tr>
                <tr>
                    <th>Up</th>
                    <td><?= $first_passage[0][0] ?></td>
                    <td><?= $first_passage[0][1] ?></td>
                    <td><?= $first_passage[0][2] ?></td>
                </tr>
                <tr>
                    <th>Same</th>
                    <td><?= $first_passage[1][0] ?></td>
                    <td><?= $first_passage[1][1] ?></td>
                    <td><?= $first_passage[1][2] ?></td>
                </tr>
                <tr>
                    <th>Down</th>
                    <td><?= $first_passage[2][0] ?></td>
                    <td><?= $first_passage[2][1] ?></td>
                    <td><?= $first_passage[2][2] ?></td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="row">
        <div class="col">
            <a class="btn btn-warning" href="steady-state-predict">Voltar</a>
        </div>
    </div>
</div>