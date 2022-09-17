<?php

use yii\helpers\Html;

?>
<div class="container">

    <h3>
        <?=
        'Preço do último dia: R$' . $last['preult'] . '<br>';
        echo 'Estado do último dia: ' . $last['state'] . '<br>';

        echo '<br><br>';

        echo "Intervalos:<br>";
        
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
    <a class="btn btn-warning" href="predict-result-interval">Voltar</a>
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