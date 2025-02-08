<?php
use yii\helpers\Html;
?>


<div class="container">
    <div>
        <h1>Importação De Dados</h1>
        <hr/>
        <p>1º Passo: Para importar dados basta entrar no site da b3 de series históricas: 
            <a target="_blank" href="https://www.b3.com.br/pt_br/market-data-e-indices/servicos-de-dados/market-data/historico/mercado-a-vista/series-historicas/">Series Historicas B3</a>
            <br/>
            No site existe algumas opções para selecionar as ações. São elas:
            <ul>
                <li>Séries Anuais</li>
                <li>Séries Mensais</li>
                <li>Séries Diárias</li>
            </ul>
        </p>
        <p>
        Selecione uma e baixe. <br/><br/>
        2º Passo: Ao baixar o arquivo que vira zipado com o nome: COTAHIST_A(o ano que foi selecionado).ZIP <br/>
        Descompacte o arquivo e coloque na pasta: <b>assets/dados_historicos/</b> do projeto. <br/>
        3º Passo: Digite o nome do arquivo descompactado no campo de <b>Nome do Arquivo</b> <br/>
        <b>obs: No nome do arquivo deve conter sua extensão. Exemplo COTAHIST_A2025.TXT</b>
        </p>
    </div>
    <div class="">
        <?= Html::beginForm('import-data', 'get') /*todos os parâmetros enviados pela 
            actionPredict serão enviados para a actionValidate pela URL e serão consumidos via GET*/ ?>
            <?= Html::label('Nome do Arquivo') ?>
            <?= Html::input('text','filename', null,['id' => 'filename']) ?>

           <?= Html::submitButton('Importar', $options = [
               'style' => [
                    'margin-left' => '10px'
                ],
               'class' => 'btn-primary'
            ]) ?>
        <?= Html::endForm() ?>
    </div>
    
</div>