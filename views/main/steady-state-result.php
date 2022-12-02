<?php
    use app\assets\AppAsset;

    AppAsset::register($this);
?>
<div class="container">

    <h2>Resultado do Cálculo do Estado Estável</h2>

    <h3>A Matriz convergiu em <?=  $times ?> dias.</h3>
    <h3>Vetor Resultante: <?=  $vector ?></h3>
    <h3>Sendo <?=  $up ?> a probabilidade de aumento, <?=  $the_same ?> a probabilidade de permanencer o mesmo preço e <?=  $down ?> a probabilidade do preço cair.</h3>
    
</div>
<div class="container">
<table class="table">
        <thead>
            <tr>
                Tempo de primeira passagem (em dias)
            </tr>
        </thead>
        <tbody>
            <tr>
                <th>Estados</th>
                <th>Up</th>
                <th>Same</th>
                <th>Down</th>
            </tr>
            <tr>
                <th>Up</th>
                <td><?= $first_passage[0][0] ?></td>
                <td><?= $first_passage[0][1] ?></td>
                <td><?= $first_passage[0][2] ?></td>
            </tr>
            <tr>
                <th>Same</th>
                <td><?= $first_passage[1][0] ?></td>
                <td><?= $first_passage[1][1] ?></td>
                <td><?= $first_passage[1][2] ?></td>
            </tr>
            <tr>
                <th>Down</th>
                <td><?= $first_passage[2][0] ?></td>
                <td><?= $first_passage[2][1] ?></td>
                <td><?= $first_passage[2][2] ?></td>
            </tr>
        </tbody>
    </table>
</div>

<div class="form-group mt-2">
        <div class="col-lg-offset-1">
            <a class="btn btn-warning" href="steady-state-predict">Voltar</a>
        </div>
    </div>