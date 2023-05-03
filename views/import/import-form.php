<?php
use yii\helpers\Html;
?>


<div class="container">
    <div>
        <h1>Importação De Dados</h1>
        <hr/>
    </div>
    <div class="">
        <?= Html::beginForm('import-data', 'get') /*todos os parâmetros enviados pela 
            actionPredict serão enviados para a actionValidate pela URL e serão consumidos via GET*/ ?>
            <?= Html::label('startDate', 'startDate') ?>
            <?= Html::input('text','startDate', null,['id' => 'startDate']) ?>

            <?= Html::label('endDate', 'endDate') ?>
            <?= Html::input('text', 'endDate', null, ['id' => 'endDate'])?>
            
            <?= Html::dropDownList('type', $selection = null, 
            $items = [
                'day' => 'day',
                'year' => 'year'
            ], $options = [
                'style' => [
                    'margin-left' => '10px'
                ]
            ]) ?>
           
           <?= Html::submitButton('Importar', $options = [
               'style' => [
                    'margin-left' => '10px'
                ],
               'class' => 'btn-primary'
            ]) ?>
        <?= Html::endForm() ?>
    </div>
    
</div>