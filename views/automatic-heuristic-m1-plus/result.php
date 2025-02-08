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
        <div class="row mt-5">
            <table class="table">
                <thead>
                    <tr>
                        <h3>
                            Resultados da Heurística de Melhor Período
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