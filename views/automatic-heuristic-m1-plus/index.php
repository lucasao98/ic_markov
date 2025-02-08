<?php

use app\models\ConsultaHeuristica;
use yii\bootstrap\ActiveForm;
use yii\helpers\Html;
use yii\jui\DatePicker;

ini_set('max_execution_time', 0); //300 seconds = 5 minutes
ini_set('memory_limit', '-1');

$consultaModel = new ConsultaHeuristica;

?>

<div class="container">
    <div class="row">
        <h1>Heuristica M1 +</h1>
        <p>O objetivo da heurística M1+ é selecionar o melhor período de tamanho para a matriz de transição (TCC)</p>
        <p>Essa tela tem como retorno 3 taxas de acertos de 3 tamanhos de matriz de transição</p>
        <p>O primeiro período é de 3 meses, o segundo de 12 meses e o terceiro de 3 anos</p>
    </div>
    <hr />
    <div class="row">
        <h2>Previsão de grandes períodos usando CMTD</h2>
        <p>Data Inicial da Previsão: A partir dessa data será coletado todos os valores do ano anterior até o ano de 2012 para a aplicação da heurística
            Além disso, após selecionar o melhor período de construção da matriz de transição, será realizada a previsão para o ano indicado nesse campo.</p>
        <p>Periodo: Número (inteiro) de meses ou anos que formarão o conjunto de treinamento</p>
        <p>Métrica: Métrica para criação do conjunto de treinamento</p>
        <p>Base Média Móvel: Número de elementos usados para o cálculo da média móvel</p>
    </div>
</div>


<hr>

<div class="container">
    <?php $form = ActiveForm::begin(['layout' => 'horizontal']) ?>

    <?= $form->field($consultaModel, 'final')->widget(DatePicker::className(), [
        'language' => 'pt-BR',
        'dateFormat' => 'dd/MM/yyyy'
    ]) ?>

    <?= $form->field($consultaModel, 'states_number')->textInput([
        'style' => ['width' => '190px', 'height' => '30px'],
        'value' => 4,
        'readonly' => true
    ]) ?>

    <?= $form->field($consultaModel, 'qtde_obs')->textInput([
        'style' => ['width' => '190px', 'height' => '30px'],
        'value' => 2,
        'readonly' => true
    ]) ?>
    <?= $form->field($consultaModel, 'qtde_up_down_constants')->textInput([
        'style' => ['width' => '190px', 'height' => '30px'],
        'value' => 2,
        'readonly' => true
    ]) ?>
    <!-- <?= $form->field($consultaModel, 'base')->textInput() ?> -->

    <div class="form-group">
        <div class="col-lg-offset-6">
            <?= Html::submitButton('Enviar', ['class' => 'btn btn-primary']) ?>
        </div>
    </div>

    <?php $form = ActiveForm::end() ?>
</div>