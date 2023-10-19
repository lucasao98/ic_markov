<?php

use app\assets\AppAsset;

$formatter = \Yii::$app->formatter;

AppAsset::register($this);
?>
<div class="container">
    <div class="row">
        <h2>Resultado da Análise</h2>
    </div>
    <div class="row">
        <table class="table table-striped">
            <tbody>
                <tr>
                    <th>Ação</th>
                    <th>Quantidade De Ações Compradas</th>
                    <th>Preço de Fechamento no dia</th>
                    <th>Data Inicial Da Matriz de Transição</th>
                    <th>Data Final Da Matriz de Transição</th>
                </tr>
                <?php
                foreach ($table_results as $result_action) {
                    echo '<tr>' .
                        '<td>' . $result_action[0] . '</td>' .
                        '<td>' . $result_action[1] . '</td>' .
                        '<td>' . $result_action[2] . '</td>' .
                        '<td>' . $formatter->asDate($result_action[3]) . '</td>' .
                        '<td>' . $formatter->asDate($result_action[4]) . '</td>' .
                        '</tr>';
                }
                ?>
            </tbody>
        </table>
        <h4>Valor Total Disponível: R$<?= number_format(100*count($table_results),2) ?></h4>
        <h4>Valor Total Utilizado na compra: R$<?= $total_purchased ?>
        </h4>
    </div>
    <div class="row">
        <div class="col">
            <a class="btn btn-warning" href="annual-analysis">Voltar</a>
        </div>
    </div>
</div>