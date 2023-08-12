<?php

use yii\helpers\Html;

?>
<div class="container">
    <h2>Resultado da previsão com intervalos</h2>
    <?php
    echo '<h3>Preço do último dia: R$' . $last['preult'] . '<br>';
    echo 'Estado do último dia: ' . $last['state'] . '<br></h3>';
    ?>
</div>


<div class="container">
    <table class="table">
        <thead>
            <tr>
                <th>Intervalos</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <th>Estados</th>
                <th>De</th>
                <th>Até</th>
                <th>Previsão Tendência dia seguinte</th>
            </tr>
            <?php
            for ($i = 0; $i < $states_number; $i++) {
                $price = $premin + $interval * ($i);
                echo "<tr><td>Estado " . ($i + 1) . "</td>";
                echo "<td>" . round($price, 2) . "</td>";
                echo "<td>" . round(($price + $interval), 2) . "</td>";
                echo "<td>" . round(($vector[0][$i]) * 100, 2) . "%</td></tr>";
            }
            ?>
        </tbody>
    </table>

    <h3>

        <?=
        "Intervalos:<br>";

        //imprime na tela os intervalos

        for ($i = 0; $i < $states_number; $i++) {
            $price = $premin + $interval * ($i);
            echo ('Estado ' . ($i + 1) . ' de ' . round($price, 2) . ' até ' . round(($price + $interval), 2) . '<br>');
        }

        echo '<br>';

        echo "Previsão de tendências para o dia seguinte: <br>";
        for ($i = 0; $i < $model->states_number; $i++) {
            echo 'Probabilidade de ' . round(($vector[0][$i]) * 100, 2) . '% para o estado ' . ($i + 1) . '<br>';
        }
        ?>
    </h3>
    <div class="container">
        <a class="btn btn-warning" href="predict-result-interval">Voltar</a>
    </div>
</div>

<br>
<div>
    <?= Html::beginForm('validate', 'get') /*todos os parâmetros enviados pela 
    actionPredict serão enviados para a actionValidate pela URL e serão consumidos via GET*/ ?>
    <?= Html::hiddenInput('stock', $stock) ?>
    <?= Html::hiddenInput('vector', $vector) ?>
    <?= Html::hiddenInput('states_number', $states_number) ?>
    <?= Html::hiddenInput('day', $day) ?>
    <?= Html::hiddenInput('premin', $premin) ?>
    <?= Html::hiddenInput('interval', $interval) ?>
    <?= Html::hiddenInput('last_price', $last_price) ?>
    <!-- <?= Html::submitButton('Validar', ['class' => 'btn btn-primary']) ?> -->
    <?= Html::endForm() ?>
</div>