<?php

use yii\bootstrap\ActiveForm;
use yii\jui\DatePicker;
use yii\helpers\Html;
use app\assets\AppAsset;
use app\models\ConsultaModel;

$consultaModel = new ConsultaModel;

AppAsset::register($this);
?>
<div class="container">
    <h2>Análise com Estado Estável</h2>
    <p>Nesse módulo será feita a análise das ações já pré-selecionadas utilizando o estado estável</p>
    <p>
        1º Passo: Será feita a previsão de estado estável das ações,
        sempre no início de cada mês do ano selecionado.
        A matriz de transição sempre será construída com 2 meses
    </p>
    <p>2º Passo: Após isso será realizada a seleção apenas das ações que obtiveram a probabilidade de aumentar maior que de diminuir</p>
    <p>3º Passo: Em seguida será feita a compra dessas ações com o valor de no máximo R$100,00</p>
    <p>4º Passo: Essas ações serão vendidas no 1º dia do mês seguinte</p>
    <p>Data Inicial: Será a data de previsão</p>
    <p class="text-warning">obs: A partir da escolha do mês não é necessário selecionar a ação, pois o processo será utilizado para todas as ações pré-selecionadas</p>
    <p></p>
    <hr>

    <?php
    $session = Yii::$app->session;
    if ($session->hasFlash('error')) :
    ?>

        <div class="alert alert-danger alert-dismissible" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <?= $session->getFlash('error'); ?>
        </div>

    <?php endif; ?>

    <div class="container m-3">

        <?php $form = ActiveForm::begin(['layout' => 'horizontal']) ?>
        <div class="row">
            <div class="col">
                <?= $form->field($consultaModel, 'inicio')->widget(DatePicker::className(), [
                    'language' => 'pt-BR',
                    'dateFormat' => 'dd/MM/yyyy'
                ]) ?>
            </div>
        </div>
        <hr>
        <div class="form-group">
            <div class="col-lg-offset-3">
                <?= Html::submitButton('Enviar', ['class' => 'btn btn-primary']) ?>
            </div>
        </div>

        <?php $form = ActiveForm::end() ?>
    </div>


</div>