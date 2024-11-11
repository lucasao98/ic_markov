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
            <h2>Tabela de Heuristica</h2>
            <p>Na tabela de heuristica, consideramos:</p>
            <p>1: Previsão de aumentar</p>
            <p>2: Previsão de permanecer o mesmo valor</p>
            <p>3: Previsão de diminuir</p>
            <h3>Heuristica Pontos antes do ponto de inflexão</h3>
            <p>
                Para realizar a previsão da heuristica para os dias antes do ponto de inflexão,
                nós comparamos a previsão de 3 estados do dia anterior com a previsão do dia atual,
                se forem iguais, a previsão da heuristica será invertida.
                Caso sejam diferentes, permanece a previsão atual.
                Se a previsão de 3 estados do dia anterior e a previsão do dia atual forem iguais,
                então vamos verificar o valor real, ( não o preço da ação, mas se ela aumentou, ficou igual ou diminuiu).
                Se o valor real, for menor ou igual a 2(aumentar, permanecer o mesmo valor), a previsão da heuristica será 3 (diminuir),
                caso o contrario, será 1 (aumentar).
            </p>
            <h3>Heuristica Pontos após o ponto de inflexão</h3>
            <p>
                Nesse caso, iremos verificar caso o valor real seja diferente da previsão atual de 3 estados,
                se forem diferentes, a previsão será o valor real, mas caso sejam iguais a previsão será a previsão atual.
            </p>
        </div>
        <div class="row mt-5">
            <table class="table">
                <thead>
                    <tr>
                        <h3>
                            Resultados
                        </h3>
                    </tr>
                    <tr>
                        <th>Ação</th>
                        <th>Acertos</th>
                        <th>Erros</th>
                        <th>Taxa de Acertos</th>
                        <th>Período Selecionado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $data) { ?>
                        <tr>
                            <th><?= $data['action'] ?></th>
                            <th><?= $data['hits'] ?></th>
                            <th><?= $data['errors'] ?></th>
                            <th><?= $data['percentage_hits'] ?></th>
                            <th><?= $data['selected_period'] ?></th>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</body>