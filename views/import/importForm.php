<?php
use yii\helpers\Html;
?>


<div>
<?= Html::beginForm('import', 'get') /*todos os parâmetros enviados pela 
    actionPredict serão enviados para a actionValidate pela URL e serão consumidos via GET*/ ?>
    <?= Html::label('startDate', 'startDate') ?>
    <?= Html::input('text','startDate', null, ['id' => 'startDate']) ?>
    <?= Html::label('endDate', 'endDate') ?>
    <?= Html::input('text', 'endDate', null, ['id' => 'endDate']) ?>
    <?= Html::dropDownList('type', $selection = null, $items = ['day' => 'day','year' => 'year'], $options = []) ?>
    <?= Html::submitButton('Importar') ?>
    <?= Html::endForm() ?>
</div>