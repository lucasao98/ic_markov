<?php

use app\assets\AppAsset;

AppAsset::register($this);
?>
<div class="container">
    <div class="row">
        <h1>Cálculo Média Desvio</h1>
    </div>
    <hr />
    <div class="row">
        <div class="col">
            <h4>Essa tela podemos realizar o cálculo das médias dos resultados de qualquer arquivo. Basta selecionar a pasta</h4>
        </div>
    </div>
    <div class="row">
        <div class="col">
            <div class="dropdown">
                <button class="btn btn-default dropdown-toggle" type="button" id="dropdownMenu1" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
                    Escolha a pasta
                    <span class="caret"></span>
                </button>
                    
                <ul class="dropdown-menu" aria-labelledby="dropdownMenu1">
                <?php foreach ($files as $file) {
                        echo '<li><a>'.$file. "</a></li>";
                    } ?>
                </ul>
            </div>
        </div>
    </div>
</div>