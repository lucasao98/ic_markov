<?php

?>

<style>
    .result-acertou {
        color: green;
    }

    .result-errou {
        color: red;
    }
</style>

<body>
    <div class="container">
        <div class="row">
            <div class="col-md-8">
                <h1>Previsão <?= $title ?></h1>
                <h3 class="result-acertou"><?= "Acertou: $acertos - " . $percentage . '%' ?></h3>
                <h3 class="result-errou"><?= "Errou: $erros - " . $percentage_errors . '%' ?></h3>
            </div>
        </div>
        <div class="col-md-12">
                <h3><?= "Compra e venda:" ?></h3>
                <h3><?= "Quantia inicial: R$100" ?></h3>
                <h3><?= "Estratégia 1: Quantia final: R$" . $cliente_heuristica_e1_cash . " e " . $cliente_heuristica_e1_actions . " ações" ?></h3>
                <h3><?= "Estratégia 2: Quantia final: R$" . $cliente_heuristica_e2_cash . " e " . $cliente_heuristica_e2_actions . " ações" ?></h3>
                <h3><?= "Estratégia 3(buy and hold): Quantia final: R$" . $cliente_heuristica_e3_cash . " e " . $cliente_heuristica_e3_actions. " ações" ?></h3>
            </div>
    </div>
</body>