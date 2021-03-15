<?php

use yii\helpers\Html;

?>

<h3>

    <?=

        'Preço do último dia: R$'.$last['preult'].'<br>';
        echo 'Estado do último dia: '.$last['state'].'<br>';

        echo '<br><br>';

        echo "Intervalos:<br>";
        for ($i = 0; $i < $states_number; $i++) { //imprime na tela os intervalos
            $price = $premin + $interval * ($i);
            echo ('Estado ' . ($i + 1) . ' de ' . round($price, 2) . ' até ' . round(($price + $interval), 2) . '<br>');
        }

        echo '<br>';

        echo "Previsão de tendências para o dia seguinte: <br>";
        for ($i = 0; $i < $model->states_number; $i++) {
            echo 'Probabilidade de ' . round(($vector[0][$i]) * 100, 2) . '% para o estado ' . ($i + 1) . '<br>';
        }

        echo "<br>Previsão usando 3 estados: <br>";
        print(round(($t_vector[0][0]) * 100, 2)."% de probabilidade do preço subir<br>");
        print(round(($t_vector[0][1]) * 100, 2)."% de probabilidade do preço se manter<br>");
        print(round(($t_vector[0][2]) * 100, 2)."% de probabilidade do preço diminuir");
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