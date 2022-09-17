<?php

use yii\helpers\Html;

?>
<div class="container">

    <h3>
        <?=
        '<br>Previsão usando 3 estados: <br>';
        print(round(($t_vector[0][0]) * 100, 2) . "% de probabilidade do preço subir<br>");
        print(round(($t_vector[0][1]) * 100, 2) . "% de probabilidade do preço se manter<br>");
        print(round(($t_vector[0][2]) * 100, 2) . "% de probabilidade do preço diminuir");
        ?>


    </h3>
    <a class="btn btn-warning" href="predict-three-states">Voltar</a>
</div>

<br>
<div>
    <?= Html::beginForm('validate', 'get') /*todos os parâmetros enviados pela 
    actionPredict serão enviados para a actionValidate pela URL e serão consumidos via GET*/ ?>
    <?= Html::hiddenInput('stock', $stock) ?>
    <?= Html::hiddenInput('day', $day) ?>
    <?= Html::hiddenInput('last_price', $last_price) ?>
    <?= Html::hiddenInput('t_vector', $t_vector) ?>
    <!-- <?= Html::submitButton('Validar', ['class' => 'btn btn-primary']) ?> -->
    <?= Html::endForm() ?>
</div>