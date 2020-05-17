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
    <h3 class="result-acertou"><?= "Acertou: $acertou - " . round(($acertou / $consultas) * 100, 2) . '%' ?></h3>
    <h3 class="result-errou"><?= "Errou: $errou - " . round(($errou / $consultas) * 100, 2) . '%' ?></h3>

    <h4>
        <?php
        foreach ($dates as $date) {
            echo $date->toDateTime()->format('d/m/Y') . '<br>';
        }
        ?>
    </h4>
</body>