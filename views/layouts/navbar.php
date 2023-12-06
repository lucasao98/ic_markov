<?php

/** @var \yii\web\View $this */
/** @var string $content */

use yii\helpers\Html;
use app\assets\TesteAsset;
use yii\bootstrap\ButtonDropdown;
use yii\bootstrap\Button;

TesteAsset::register($this);

?>

<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">

<head>

  <meta charset="<?= Yii::$app->charset ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <meta name="description" content="">
  <meta name="author" content="">
  <?= Html::csrfMetaTags() ?>

  <!--  <title>Business Frontpage - Start Bootstrap Template</title> -->
  <title><?= Html::encode($this->title) ?></title>
  <?php $this->head() ?>

</head>

<body>
  <?php $this->beginBody() ?>
  <div class="container">
    <div class="row">
      <ul class="navigation">
        <li>
          <a href="home">Home</a>
        </li>
        <li>
          <?php
          echo ButtonDropdown::widget([
            'label' => 'Estado Estável',
            'options' => [
              'class' => 'btn btn-primary',
            ],
            'dropdown' => [
              'items' => [
                ['label' => 'Previsão do dia seguinte', 'url' => 'steady-state-predict'],
                ['label' => 'Previsão de maiores intervalos', 'url' => 'steady-state-test'],
              ],
            ],
          ]);
          ?>
        </li>
        <li>
          <?php
          echo ButtonDropdown::widget([
            'label' => 'Método com 3 estados',
            'options' => [
              'class' => 'btn btn-primary',
            ],
            'dropdown' => [
              'items' => [
                ['label' => 'Previsão de Grandes Intervalos', 'url' => 'predict-three-states-test'],
              ],
            ],
          ]);
          ?>
        </li>
        <li>
          <a href="first-passage-time">Tempo de primeira passagem</a>
        </li>
        <!--
        <li>
          <a href="steady-state-automatic">Estado Estável Automatizado</a>
        </li>
        -->
        <!--
        <li>
          <a href="calculo-media-desvio">Cálculo das Médias</a>
        </li>
        -->
        <!--
        <li>
          <a href="annual-analysis">Análise Estado Estável</a>
        </li>
        -->
        <li>
          <a href="join-methods">Intervalos Fixos e Método de 3 estados</a>
        </li>
      </ul>
    </div>
  </div>
  <?= $content ?>

  <?php $this->endBody() ?>
</body>

</html>
<?php $this->endPage() ?>