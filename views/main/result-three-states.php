<div class="container">
    <h1>Previsão com 3 estados</h1>
    <h3 class="result-acertou"><?= "Acertou: $t_acertou - " . round(($t_acertou / $consultas) * 100, 2) . '%' ?></h3>
    <h3 class="result-errou"><?= "Errou: $t_errou - " . round(($t_errou / $consultas) * 100, 2) . '%' ?></h3>
    <h3><?= "Compra e venda:" ?></h3>
    <h3><?= "Quantia inicial: R$100" ?></h3>
    <h3><?= "Método 1: Quantia final: R$" . $t_cliente1['cash'] . " e " . $t_cliente1['actions'] . " ações" ?></h3>
    <h3><?= "Método 2: Quantia final: R$" . $t_cliente2['cash'] . " e " . $t_cliente2['actions'] . " ações" ?></h3>
    <h3><?= "Método 3: Quantia final: R$" . $t_cliente3['cash'] . " e " . $t_cliente3['actions'] . " ações" ?></h3>
    <h3><?= "Método 4: Quantia final: R$" . $t_cliente4['cash'] . " e " . $t_cliente4['actions'] . " ações" ?></h3>
    <h3><?= "Método 5: Quantia final: R$" . $t_cliente5['cash'] . " e " . $t_cliente5['actions'] . " ações" ?></h3>
    <a class="btn btn-warning" href="predict-three-states-test">Voltar</a>
</div>