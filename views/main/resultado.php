<?php


$prob = round($probabilidade * 100, 2);
?>

<style>
    .result p {
        margin-top: 15px;
        margin-bottom: 15px;
    }
</style>

<h1 class=stats>
    <?=$resultado?>
</h1>

<h3 class=result>
    Valores previstos:
    <p class=result-estado>Estado: <?= $estado ?></p>
    <p class=result-probabilidade>Probabilidade: <?=$prob?>%</p>
    Valores do próximo dia:
    <p class=result-next><?= ($nextDay['date']->toDateTime())->format('d/m/Y') ?> - R$<?= $nextDay['preult'] ?></p>
</h3>

<script>
    const resultado = document.querySelector('.stats')
    if (resultado.innerText === "Acertou!")
        resultado.style.color = "green"
    else
        resultado.style.color = "red"
</script>