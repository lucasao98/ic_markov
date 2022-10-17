<?php
    use app\assets\AppAsset;

    AppAsset::register($this);
?>
<div class="container">

    <h2>Resultado do Cálculo do Estado Estável</h2>

    <h3>A Matriz convergiu com após <?=  $times ?> iterações com valores de <?= $matrix ?>.</h3>
    <div class="form-group mt-2">
        <div class="col-lg-offset-0">
            <a class="btn btn-warning" href="steady-state-predict">Voltar</a>
        </div>
    </div>
</div>