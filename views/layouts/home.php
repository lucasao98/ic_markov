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
  <title>Home</title>
  <?= Html::csrfMetaTags() ?>

  <title><?= Html::encode($this->title) ?></title>
  <?php $this->head() ?>

</head>

<body>
  <?php $this->beginBody() ?>
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
  <!-- Page Content -->
  <div class="container">

    <div class="row">
      <div class="col-md-8 mb-5">
        <h2>Sobre a ferramenta</h2>
        <hr>
        <p>Está ferramenta foi criada durante um projeto de iniciação científica financiado pela FAPESB!</p>
        <p>O objetivo desta ferramenta é de prever tendências em ações da BOVESPA usando Cadeias de Markov de Tempo Discreto.</p>

      </div>
      <div class="col-md-4 mb-5">
        <h2>Sobre os desenvolvedores</h2>
        <hr>
        <address>
          <strong>Bolsista</strong>
          <br>João Henrique dos Santos Queiroz
          <br>Bacharelando em Ciência da Computação
          <br>
        </address>
        <address>
          <abbr title="Email">Email:</abbr>
          <a href="mailto:jhsqueiroz.cic@uesc.br">jhsqueiroz.cic@uesc.br</a>
        </address>
        <address>
          <strong>Orientadora</strong>
          <br>Martha Torres
          <br>Professora adjunta da UESC
          <br>
        </address>
      </div>
    </div>


  </div>

  <?php $this->endBody() ?>
</body>

</html>
<?php $this->endPage() ?>