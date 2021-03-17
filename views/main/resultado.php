<?php


$prob = round($probabilidade * 100, 2);
$t_prob = round($three_states * 100, 2);

switch($t_max) {
    case 0:
        $t_state = 'aumentar';
        break;

    case 1:
        $t_state = 'permanecer igual';
        break;

    case 2:
        $t_state = 'diminuir';
        break;

    default:
        break;
}
?>

<style>
    .result p {
        margin-top: 15px;
        margin-bottom: 15px;
    }
</style>

<h2>
    Validação da previsão:
</h2>
<h3 class="result">
    <?=$result?>
</h3>

<h2>
    Validação da previsão usando 3 estados:
</h2>
<h3 class="t-result">
    <?=$t_result?>
</h3>

<h3>
    Valores previstos:
    <p class=result-estado>Estado: <?= $estado ?></p>
    <p class=result-probabilidade>Probabilidade: <?=$prob?>%</p>
    <p>Probabilidade 3 estados: <?=$t_prob?>% para o preço <?=$t_state?></p>
    Preço do último dia:
    <p>R$<?=$last_price?></p>
    Valores do próximo dia:
    <p class=result-next><?= ($nextDay['date']->toDateTime())->format('d/m/Y') ?> - R$<?= $nextDay['preult'] ?></p>
</h3>

<script>
    const result = document.querySelector('.result')
    if (result.innerText === "Acertou!")
        result.style.color = "green"
    else
        result.style.color = "red"

    const result = document.querySelector('.t-result')
    if (result.innerText === "Acertou!")
        result.style.color = "green"
    else
        result.style.color = "red"
</script>