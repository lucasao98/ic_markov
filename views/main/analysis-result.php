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
        <h4>Valor Total Disponível: R$<?= number_format(100 * count($table_results), 2) ?></h4>
        <h4>Valor Total Utilizado na compra: R$<?= $total_purchased ?> </h4>
        <h4>Valor Total da venda: R$ <?= $total_selled ?> </h4>
    </div>
    <div style="margin-bottom: 5px;" class="row">
        <div class="col">
            <a class="btn btn-warning" href="annual-analysis">Voltar</a>
        </div>
    </div>
    <div class="row">
        <table class="table table-striped">
            <tbody>
                <tr>
                    <th>Ação</th>
                    <th>Grupo</th>
                    <th>Quantidade De Ações Compradas</th>
                    <th>Preço de Fechamento no último dia da matriz de transição (R$)</th>
                    <th>Data Inicial Da Matriz de Transição</th>
                    <th>Data Final Da Matriz de Transição</th>
                    <th>Preço mês seguinte (R$)</th>
                    <th>Data da venda no mês seguinte</th>
                </tr>
                <?php
                foreach ($table_results as $result_action) {
                    echo '<tr>' .
                        '<td>' . $result_action[0] . '</td>' .
                        '<td>' . $result_action[1] . '</td>' .
                        '<td>' . $result_action[2] . '</td>' .
                        '<td>' . $result_action[3] . '</td>' .
                        '<td>' . $formatter->asDate($result_action[4]) . '</td>' .
                        '<td>' . $result_action[5] . '</td>' .
                        '<td>' . $formatter->asDate($result_action[6]) . '</td>' .
                        '<td>' . $result_action[7] . '</td>' .
                        '</tr>';
                }
                ?>
            </tbody>
        </table>

        </h4>
    </div>

</div>