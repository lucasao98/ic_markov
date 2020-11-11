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
        <a class="navbar-brand" href="#">Meu Site</a>
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
              <a class="nav-link" href="predict">Previsão</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="teste">Teste</a>
            </li>
          </ul>
        </div>
      </div>
    </nav>
    <aside class="nav-login">
      <a class="nav-link" href="#">Login</a>
    </aside>
  </header>

  <!-- Header -->
  <!--<header class="bg-primary py-5 mb-5">
    <div class="container h-100">
      <div class="row h-100 align-items-center">
        <div class="col-lg-12">
          <h1 class="display-4 text-white mt-5 mb-2">Meu Site</h1>
          <p class="lead mb-5 text-white-50">Lorem ipsum dolor sit amet, consectetur adipisicing elit. Non possimus ab labore provident mollitia. Id assumenda voluptate earum corporis facere quibusdam quisquam iste ipsa cumque unde nisi, totam quas ipsam.</p>
        </div>
      </div>
    </div>
  </header>-->

  <?= $content ?>
  <!-- Page Content -->
  <div class="container">

    <div class="row">
      <div class="col-md-8 mb-5">
        <h2>Sobre a ferramenta</h2>
        <hr>
        <p>Está ferramenta foi criada durante um projeto de iniciação científica financiado pela FAPESB!</p>
        <p>O objetivo desta ferramenta é de prever tendências em ações da BOVESPA usando Cadeias de Markov de Tempo Discreto.</p>
        <!-- <a class="btn btn-primary btn-lg" href="#">Call to Action &raquo;</a> -->
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
    <!-- /.row -->

    <!-- <div class="row">
      <div class="col-md-4 mb-5">
        <div class="card h-100">
          <img class="card-img-top" src="http://placehold.it/300x200" alt="">
          <div class="card-body">
            <h4 class="card-title">Card title</h4>
            <p class="card-text">Lorem ipsum dolor sit amet, consectetur adipisicing elit. Sapiente esse necessitatibus neque sequi doloribus.</p>
          </div>
          <div class="card-footer">
            <a href="#" class="btn btn-primary">Find Out More!</a>
          </div>
        </div>
      </div>
      <div class="col-md-4 mb-5">
        <div class="card h-100">
          <img class="card-img-top" src="http://placehold.it/300x200" alt="">
          <div class="card-body">
            <h4 class="card-title">Card title</h4>
            <p class="card-text">Lorem ipsum dolor sit amet, consectetur adipisicing elit. Sapiente esse necessitatibus neque sequi doloribus totam ut praesentium aut.</p>
          </div>
          <div class="card-footer">
            <a href="#" class="btn btn-primary">Find Out More!</a>
          </div>
        </div>
      </div>
      <div class="col-md-4 mb-5">
        <div class="card h-100">
          <img class="card-img-top" src="http://placehold.it/300x200" alt="">
          <div class="card-body">
            <h4 class="card-title">Card title</h4>
            <p class="card-text">Lorem ipsum dolor sit amet, consectetur adipisicing elit. Sapiente esse necessitatibus neque.</p>
          </div>
          <div class="card-footer">
            <a href="#" class="btn btn-primary">Find Out More!</a>
          </div>
        </div>
      </div>
    </div> -->
    <!-- /.row -->

  </div>
  <!-- /.container -->

  <!-- Footer -->
  <!-- <footer class="py-5 bg-dark">
    <div class="container">
      <p class="m-0 text-center text-white">Copyright &copy; Your Website 2019</p>
    </div>
  </footer> -->

  <?php $this->endBody() ?>
</body>

</html>
<?php $this->endPage() ?>