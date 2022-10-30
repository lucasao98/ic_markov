<?php
    use app\assets\AppAsset;

    AppAsset::register($this);
?>
<div class="container">

    <h2>Resultado do Cálculo do Estado Estável</h2>

    <h3>A Matriz convergiu em <?=  $times ?> dias.</h3>
    <h3>Vetor Resultante: <?=  $vector ?></h3>
    <h3>Sendo <?=  $up ?> a probabilidade de aumento, <?=  $the_same ?> a proabilidade de permanencer o mesmo preço e <?=  $down ?> a probabilidade do preço cair.</h3>
    <div class="form-group mt-2">
        <div class="col-lg-offset-0">
            <a class="btn btn-warning" href="steady-state-predict">Voltar</a>
        </div>
    </div>
</div>