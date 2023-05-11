<?php

/** @var \yii\web\View $this */
/** @var string $content */

use yii\helpers\Html;
use app\assets\TesteAsset;
use yii\bootstrap\ButtonDropdown;

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
    <nav class="navbar bg-primary navigation mt-1">
      <div class="container">
        <!-- Brand and toggle get grouped for better mobile display -->
        <div class="navbar-header">
          <ul class="nav navbar-nav">
            <?php

            echo ButtonDropdown::widget([
              'label' => 'Método com Intervalos',
              'dropdown' => [
                'items' => [
                  ['label' => 'Teste', 'url' => 'teste'],
                  ['label' => 'Predição', 'url' => 'predict-result-interval'],
                ],
              ],
            ]);


            echo ButtonDropdown::widget([
              'label' => 'Método com 3 estados',
              'dropdown' => [
                'items' => [
                  ['label' => 'Teste', 'url' => 'predict-three-states-test'],
                  ['label' => 'Predição', 'url' => 'predict-three-states'],
                ],
              ],
            ]);


            echo ButtonDropdown::widget([
              'label' => 'Estado Estável',
              'dropdown' => [
                'items' => [
                  ['label' => 'Teste', 'url' => 'steady-state-test'],
                  ['label' => 'Predição', 'url' => 'steady-state-predict'],
                ],
              ],
            ]);


            echo ButtonDropdown::widget([
              'label' => 'Tempo de primeira passagem',
              'dropdown' => [
                'items' => [
                  ['label' => 'Predição', 'url' => 'first-passage-time'],
                ],
              ],
            ]);

            ?>
          </ul>

          </ul>
        </div><!-- /.navbar-collapse -->
      </div><!-- /.container-fluid -->
    </nav>
  </div>

  <?= $content ?>

  <?php $this->endBody() ?>
</body>

</html>
<?php $this->endPage() ?>