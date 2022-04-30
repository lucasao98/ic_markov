<?php

/** @var \yii\web\View $this */
/** @var string $content */

use yii\helpers\Html;
use app\assets\TesteAsset;

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

  <!-- Navigation -->
  <header class="navigation">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
      <div class="container">
        <a class="navbar-brand" href="/home">Meu Site</a>
        <div class="collapse navbar-collapse" id="navbarResponsive">
          <ul class="navbar-nav ml-auto">
            <li class="nav-item active">
              <a class="nav-link" href="home">Home
                <span class="sr-only">(current)</span>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="about">Sobre</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="predict">Previsão do dia seguinte</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="teste">Previsão de grandes intervalos</a>
            </li>
          </ul>
        </div>
      </div>
    </nav>
  </header>

	
	<?= $content ?>

<?php $this->endBody() ?>
</body>

</html>
<?php $this->endPage() ?>

