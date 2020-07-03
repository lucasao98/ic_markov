<?php

use yii\helpers\Html;

?>

<h3>

    <?=
        "Intervalos:<br>";
    for ($i = 0; $i < $states_number; $i++) { //imprime na tela os intervalos
        $price = $premin + $interval * ($i);
        echo ('Estado ' . ($i + 1) . ' de ' . round($price, 2) . ' até ' . round(($price + $interval), 2) . '<br>');
    }

    echo "<br>Vetor de previsão: <br>";
    print($vector);
    echo '<br><br>';

    echo "Previsão de tendências para o dia seguinte: <br>";
    for ($i = 0; $i < $model->states_number; $i++) {
        echo 'Probabilidade de ' . round(($vector[0][$i]) * 100, 2) . '% para o estado ' . ($i + 1) . '<br>';
    }

    /*foreach($db as $cursor){
        echo($cursor['preult'] . ' -> ' . $cursor['state'] . " " . $cursor['date']->toDateTime()->format('d/m/Y') . '<br>');
    }*/
    ?>

</h3>

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
    <?= Html::submitButton('Validar', ['class' => 'btn btn-primary']) ?>
    <?= Html::endForm() ?>
</div>