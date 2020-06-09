<?php

namespace app\controllers;

use yii\web\Controller;
use app\models\ConsultaModel;
use app\models\MetodosModel;
use app\models\Paper;

//date_default_timezone_set("america/bahia");

class ExerciciosController extends Controller
{

    public function actionHome()
    {
        $this->layout = 'home';

        return $this->render('index');
    }

    public function actionPredict()
    {
        $this->layout = 'clean';

        $model = new ConsultaModel;
        $post = $_POST;

        if ($model->load($post) && $model->validate()) {
            $start = $model->inicio;
            $final = $model->final;
            $start = \DateTime::createFromFormat('d/m/YH:i:s', $start . '24:00:00'); //Dia de início do conjunto de treinamento
            $final = \DateTime::createFromFormat('d/m/YH:i:s', $final . '24:00:00'); //Dia final do conjunto de treinamento

            $start = Paper::toIsoDate($start->format('U')); //Passando para o padrão de datas do banco
            $final = Paper::toIsoDate($final->format('U')); //Passando para o padrão de datas do banco

            $stock = $model->nome;

            $cursor_by_price = $model->PegarDados($stock, $start, $final);

            $premin = $model->DefinirPremin($cursor_by_price);
            $premax = $model->DefinirPremax($cursor_by_price);

            $interval = abs($premin['preult'] - $premax['preult']) / $model->states_number; //calculo do intervalo

            $states = []; //vetor que contem a quantidade de elementos em cada estado
            for ($i = 0; $i < $model->states_number; $i++) {
                $states[$i] = 0;
            }

            foreach ($cursor_by_price as $cursor) { //atribui um estado a partir do preço de fechamento para cada data no conjunto de treinamento
                $cursor['state'] = $model->getState($cursor['preult'], $premin['preult'], $interval, $model->states_number);
                if ($cursor['state'] != 0)
                    $states[$cursor['state'] - 1] += 1;
            }

            $matrix = $model->transitionMatrix($cursor_by_price, $states, $model->states_number); //função que constrói a matriz de transição

            $vector = $model->predictVector($matrix, $cursor_by_price, $model->states_number); //função que constrói o vetor de predição

            return $this->render('vervetor', [
                //'db' => $cursor_by_price,
                'vector' => $vector,
                'model' => $model,
                'stock' => $model->nome,
                'states_number' => $model->states_number,
                'day' => $final,
                'premin' => $premin['preult'],
                'interval' => $interval
            ]);
        } else {

            return $this->render('predict', [
                'consultaModel' => $model
            ]);
        }
    }

    public function actionMetodos()
    {
        $this->layout = 'clean';

        $metodosModel = new MetodosModel;
        $post = $_POST;

        if ($metodosModel->load($post) && $metodosModel->validate()) {

            if ($metodosModel->metodo == 'CMTD') {

                return $this->redirect(['predict']);
            } else {

                return $this->render('cmo');
            }
        } else {
            return $this->render('metodos', [
                'model' => $metodosModel
            ]);
        }
    }

    //Função que valida se o estado previsto condiz com o preço real
    public function actionValidate(/*$vector, $stock, $states_number, $day, $premin, $premax, $interval*/)
    {
        $this->layout = 'clean';

        //Os valores recebidos através da URL são string, por isso precisam de manipulação
        $stock = $_GET['stock'];
        $vector = substr($_GET['vector'], 1, -1); //Removendo o primeiro e último caractere "[ ]"
        $states_number = intval($_GET['states_number']); //Convertendo para inteiro
        $day = Paper::toIsoDate((intval($_GET['day']) / 1000)); //Convertendo pra o padrão IsoDate utilizado pelo Mongo
        $premin = floatval($_GET['premin']); //Conversão para float
        $interval = floatval($_GET['interval']); //Conversão para float

        $vector = explode(", ", $vector); //Construindo um vetor de strings
        for ($i = 0; $i < count($vector); $i++) { //Pegando cada valor do vetor de strings e convertendo para float
            $vector[$i] = floatval($vector[$i]);
        }

        $nextDay = Paper::find()->orderBy('date')->where(['=', 'codneg', $stock])->andWhere(['>', 'date', $day])->one(); //busca o dia seguinte no banco
        $nextDay['state'] = ConsultaModel::getState($nextDay['preult'], $premin, $interval, $states_number); // calcula o estado do dia seguinte
        $max = 0;

        for ($i = 1; $i < $states_number; $i++) { //calculando o estado com maior probabilidade no vetor de previsão
            if ($vector[$i] >= $vector[$max])
                $max = $i;
        }

        if ($nextDay['state'] == $max + 1)
            $resultado = 'Acertou!';

        else
            $resultado = 'Errou!';

        return $this->render('resultado', [
            'resultado' => $resultado,
            'nextDay' => $nextDay,
            'estado' => $max + 1,
            'probabilidade' => $vector[$max]
        ]);
    }

    public function actionTeste()
    {
        $this->layout = 'clean';

        $model = new ConsultaModel;
        $post = $_POST;

        if ($model->load($post) && $model->validate() && $model->periodo) {
            $start = $model->inicio;
            $consultas = 0;
            $acertou = 0;
            $errou = 0;
            $next = array();
            $intervals = array();
            $aux = Paper::toIsoDate(\DateTime::createFromFormat('d/m/YH:i:s', $model->final . '24:00:00')->getTimestamp());
            $nextDay = new Paper();

            while (1) {
                $final = $start;
                $start = \DateTime::createFromFormat('d/m/YH:i:s', $start . '24:00:00'); //Dia de início do conjunto de treinamento
                $start = $start->modify("-$model->periodo month"); //O conjunto de treinamento será definido n meses antes do dia a ser previsto
                /* -------------------------------------------------------------------- */
                $final = \DateTime::createFromFormat('d/m/YH:i:s', $final . '24:00:00')->modify('-1 day'); //Dia final do conjunto de treinamento

                $start = Paper::toIsoDate($start->format('U')); //Passando para o padrão de datas do banco
                $final = Paper::toIsoDate($final->format('U')); //Passando para o padrão de datas do banco

                $stock = $model->nome;

                $nextDay = Paper::find()->orderBy('date')->where(['=', 'codneg', $stock])->andWhere(['>', 'date', $final])->one(); //busca o dia seguinte no banco
                //Se o dia a ser previsto for maior do que o nosso ultimo dia estipulado o laço acaba
                if ($nextDay['date'] > $aux)
                    break;

                $cursor_by_price = $model->PegarDados($stock, $start, $final);

                $premin = $model->DefinirPremin($cursor_by_price);
                $premax = $model->DefinirPremax($cursor_by_price);

                $interval = abs($premin['preult'] - $premax['preult']) / $model->states_number; //calculo do intervalo

                $states = []; //vetor que contem a quantidade de elementos em cada estado
                for ($i = 0; $i < $model->states_number; $i++) {
                    $states[$i] = 0;
                }

                foreach ($cursor_by_price as $cursor) { //atribui um estado a partir do preço de fechamento para cada data no conjunto de treinamento
                    $cursor['state'] = $model->getState($cursor['preult'], $premin['preult'], $interval, $model->states_number);
                    if ($cursor['state'] != 0)
                        $states[$cursor['state'] - 1] += 1;
                }

                $matrix = $model->transitionMatrix($cursor_by_price, $states, $model->states_number); //função que constrói a matriz de transição

                $vector = $model->predictVector($matrix, $cursor_by_price, $model->states_number); //função que constrói o vetor de predição
                /* Validação ----------------------------------------------------------------- */

                $nextDay['state'] = $model->getState($nextDay['preult'], $premin['preult'], $interval, $model->states_number); // calcula o estado do dia seguinte


                array_push($next, $nextDay['date']);
                $max = 0;
                $vector = $vector[0];

                for ($i = 1; $i < $model->states_number; $i++) { //calculando o estado com maior probabilidade no vetor de previsão
                    if ($vector[$i] >= $vector[$max])
                        $max = $i;
                }

                array_push($intervals, $nextDay['preult'] . ', ' . $model->getInterval($premin['preult'], $interval, $max));
                if ($nextDay['state'] == $max + 1)
                    $acertou++;
                else
                    $errou++;
                /* Preparação para a próxima iteração ----------------------------------------------------------------- */

                //Se o dia previsto foi um mais a frente que o próximo o programa continua a partir do dia previsto
                if ($nextDay['date'] != Paper::toIsoDate(($final->toDateTime()->modify('+1 day')->format('U')))) {
                    $nextAux = $nextDay['date']->toDateTime()->modify("-$model->periodo month");
                    $start = Paper::toIsoDate($nextAux->getTimestamp());
                }

                $start = $start->toDateTime()->modify("+$model->periodo month");
                $start = $start->modify('+1 day');
                $start = $start->format('d/m/Y');
                $consultas++;
            }

            return $this->render('resultadoTeste', [
                'acertou' => $acertou,
                'errou' => $errou,
                'consultas' => $consultas,
                'dates' => $next,
                'intervals' => $intervals
            ]);
        } else {
            return $this->render('teste');
        }
    }

    public function actionLogin()
    {
    }

    public function actionLogout()
    {
    }
}
