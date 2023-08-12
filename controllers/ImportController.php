<?php

namespace app\controllers;

use app\models\Paper;
use DateTime;
use Exception;
use yii\base\Controller;
use yii\httpclient\Client;
use ZipArchive;
use Yii;

class ImportController extends Controller
{

    // import&startDate=02012020&endDate=02012020&type=year
    public function actionImportForm()
    {
        $this->layout = 'navbar';
        return $this->render('import-form');
    }

    public function actionImportData(/*$startDate, $endDate, $type*/)
    {
        $this->layout = 'navbar';


        $startDate = $_GET['startDate'];
        $endDate = $_GET['endDate'];
        $type = $_GET['type'];

        ini_set('max_execution_time', 0); //300 seconds = 5 minutes
        ini_set('memory_limit', '-1');

        // Yii::debug("[IMPORT] start");

        $begin =  DateTime::createFromFormat('Y', $startDate);
        $end =  DateTime::createFromFormat('j-M-Y', $endDate);

        if ($type == 'day') {
            $format = 'dmY';
            $typeFromDownload = 'D';
        } else {
            $format = 'Y';
            $typeFromDownload = 'A';
        }

        $begin = $begin->format($format);

        try {
            //$this->downloadData($begin, $typeFromDownload);
            //$this->extractData($startDate);
            $this->parseDataAndSaveInDatabase($begin, $typeFromDownload);

            Yii::debug("[IMPORT]sucesso na data " . $begin);
        } catch (\Exception $e) {
            return ("[IMPORT]falha na data " . $begin . " " . $e->getMessage());
        }
        /*
        for ($i = $begin; $i <= $end; $i->modify('+1 ' . $type)) {
            
            $dateFormatted = $i->format($format);

            try {
                //$this->downloadData($dateFormatted, $typeFromDownload);
                //$this->extractData($dateFormatted);
                //$this->parseDataAndSaveInDatabase($dateFormatted, $typeFromDownload);

                Yii::debug("[IMPORT]sucesso na data " . $dateFormatted);
            } catch (\Exception $e) {
                return ("[IMPORT]falha na data " . $dateFormatted . " " . $e->getMessage());
            }
        }
            return $dateFormatted . " importado";*/
        }
        

    public function downloadData($date, $type)
    {
        Yii::debug("[IMPORT] start download data from " . $date);


        $file_path = '../assets/COTAHIST/ZIP/' . $date . '.zip';
        touch($file_path);
        $fh = fopen($file_path, 'w');
        $client = new Client([
            'transport' => 'yii\httpclient\CurlTransport'
        ]);

        $response = $client->createRequest()
            ->setMethod('GET')
            ->setUrl('https://bvmf.bmfbovespa.com.br/pt-br/cotacoes-historicas/FormConsultaValida.asp?arq=COTAHIST_' . $type . $date . '.ZIP')
            ->setOutputFile($fh)
            ->send();

        fclose($fh);

        Yii::debug("[IMPORT] end download data from " . $date);
    }
    
    public function extractData($date)
    {
        Yii::debug("[IMPORT] start extractData data from " . $date);

        $file_path = '../assets/COTAHIST_A' . $date . '.ZIP';
        $zip = new ZipArchive;
        $res = $zip->open($file_path);
        if ($res === TRUE) {
            echo 'ok';

            $zip->extractTo('../assets/COTAHIST');
            unlink($file_path);
        } else {
            echo 'failed, code:' . $res;
        }
        $zip->deleteName($file_path);
        $zip->close();
    }

    public function parseDataAndSaveInDatabase($date, $type)
    {
        Yii::debug("[IMPORT] start parseDataAndSaveInDatabase data from " . $date);

        if ($type == 'D') {
            $extension = '.TXT';
            $typeWithSeparator = "_D";
        } else {
            $extension = '.TXT';
            $typeWithSeparator = "_A";
        }

        $file = fopen("../assets/COTAHIST/ZIP/COTAHIST" . $typeWithSeparator . $date . $extension, "r");

        if ($file) {
            $header = fgets($file);
            while (($line = fgets($file)) !== false) {
                if (substr($line, 0, 10) == "99COTAHIST") {
                    return "FIM";
                }
                try {
                    $line = mb_convert_encoding($line, 'US-ASCII', 'UTF-8');
                    // 2 parametro do substr - posicao inicial a ser lida na linha
                    //(1 numero a menos do que especidicado no manual)
                    // 3 parametro do substr - qtd caracteres a ser lido a partir da posicao inicial

                    //DATA DO PREGÃO
                    /*$dateTime = \DateTime::createFromFormat('Ymd', substr($line,2,8));
                    
                    
                    $paper->date = $dateTime->format("Y-m-d");*/
                    $actions = [
                        'ABCB4',
                        'ABCP11',
                        'AFLT3',
                        'AGRO3',
                        'ALPA3',
                        'ALPA4',
                        'AMAR3',
                        'ANCR11B',
                        'BAHI3',
                        'BALM4',
                        'BAUH4',
                        'BAZA3',
                        'BBAS3',
                        'BBDC3',
                        'BBDC4',
                        'BBFI11B',
                        'BBRK3',
                        'BDLL4',
                        'BEEF3',
                        'BEES3',
                        'BEES4',
                        'BGIP3',
                        'BGIP4',
                        'BIOM3',
                        'BMEB3',
                        'BMEB4',
                        'BMIN3',
                        'BMIN4',
                        'BMKS3',
                        'BNBR3',
                        'BOBR4',
                        'BOVA11',
                        'BRAP3',
                        'BRAP4',
                        'BRAX11',
                        'BRFS3',
                        'BRGE11',
                        'BRGE12',
                        'BRGE3',
                        'BRGE6',
                        'BRGE8',
                        'BRIV3',
                        'BRIV4',
                        'BRKM3',
                        'BRKM5',
                        'BRKM6',
                        'BRML3',
                        'BRPR3',
                        'BRSR3',
                        'BRSR5',
                        'BRSR6',
                        'BSLI4',
                        'BTOW3',
                        'BTTL3',
                        'CARD3',
                        'CBEE3',
                        'CCPR3',
                        'CCRO3',
                        'CEBR3',
                        'CEBR5',
                        'CEBR6',
                        'CEDO3',
                        'CEDO4',
                        'CEEB3',
                        'CEEB5',
                        'CEED3',
                        'CEED4',
                        'CEGR3',
                        'CEPE5',
                        'CEPE6',
                        'CESP3',
                        'CESP5',
                        'CESP6',
                        'CGAS3',
                        'CGAS5',
                        'CGRA3',
                        'CGRA4',
                        'CIEL3',
                        'CLSC3',
                        'CMIG3',
                        'CMIG4',
                        'COCE3',
                        'COCE5',
                        'CPFE3',
                        'CPLE3',
                        'CPLE6',
                        'CRDE3',
                        'CRIV3',
                        'CRIV4',
                        'CSAB3',
                        'CSAB4',
                        'CSAN3',
                        'CSMG3',
                        'CSNA3',
                        'CSRN3',
                        'CSRN5',
                        'CSRN6',
                        'CTKA3',
                        'CTKA4',
                        'CTNM3',
                        'CTNM4',
                        'CTSA3',
                        'CTSA4',
                        'CXCE11B',
                        'CYRE3',
                        'DASA3',
                        'DIRR3',
                        'DOHL3',
                        'DOHL4',
                        'DTCY3',
                        'DTEX3',
                        'EALT4',
                        'ECOR3',
                        'ECPR3',
                        'EDFO11B',
                        'EEEL3',
                        'EEEL4',
                        'EKTR4',
                        'ELET3',
                        'ELET5',
                        'ELET6',
                        'EMAE4',
                        'EMBR3',
                        'ENBR3',
                        'ENGI11',
                        'ENGI3',
                        'ENGI4',
                        'EQTL3',
                        'ESTR4',
                        'ETER3',
                        'EUCA4',
                        'EURO11',
                        'EVEN3',
                        'EZTC3',
                        'FAMB11B',
                        'FESA3',
                        'FESA4',
                        'FHER3',
                        'FIIP11B',
                        'FLMA11',
                        'FLRY3',
                        'FMOF11',
                        'FNAM11',
                        'FNOR11',
                        'FPAB11',
                        'FRAS3',
                        'FRIO3',
                        'FSPE11',
                        'FSRF11',
                        'FSTU11',
                        'GEPA3',
                        'GEPA4',
                        'GFSA3',
                        'GGBR3',
                        'GGBR4',
                        'GOAU3',
                        'GOAU4',
                        'GOLL4',
                        'GPAR3',
                        'GPCP3',
                        'GRND3',
                        'GSHP3',
                        'GUAR3',
                        'HAGA3',
                        'HAGA4',
                        'HBOR3',
                        'HBTS5',
                        'HETA4',
                        'HGBS11',
                        'HGRE11',
                        'HGTX3',
                        'HOOT4',
                        'HYPE3',
                        'IBOV11',
                        'IGBR3',
                        'IGTA3',
                        'INEP3',
                        'INEP4',
                        'ITSA3',
                        'ITSA4',
                        'ITUB3',
                        'ITUB4',
                        'JBDU3',
                        'JBDU4',
                        'JBSS3',
                        'JFEN3',
                        'JHSF3',
                        'JOPA3',
                        'JOPA4',
                        'JSLG3',
                        'KEPL3',
                        'KLBN3',
                        'KLBN4',
                        'KNRI11',
                        'LAME3',
                        'LAME4',
                        'LIGT3',
                        'LIPR3',
                        'LLIS3',
                        'LOGN3',
                        'LPSB3',
                        'LREN3',
                        'LUPA3',
                        'MAPT4',
                        'MDIA3',
                        'MERC3',
                        'MERC4',
                        'MGEL4',
                        'MILS3',
                        'MMXM3',
                        'MNDL3',
                        'MNPR3',
                        'MOAR3',
                        'MRFG3',
                        'MRVE3',
                        'MSPA3',
                        'MSPA4',
                        'MTIG4',
                        'MTSA4',
                        'MULT3',
                        'MWET4',
                        'MYPK3',
                        'ODPV3',
                        'OSXB3',
                        'PABY11',
                        'PATI3',
                        'PATI4',
                        'PDGR3',
                        'PEAB3',
                        'PEAB4',
                        'PETR3',
                        'PETR4',
                        'PFRM3',
                        'PIBB11',
                        'PINE4',
                        'PLAS3',
                        'PMAM3',
                        'PNVL3',
                        'PNVL4',
                        'POMO3',
                        'POMO4',
                        'POSI3',
                        'PQDP11',
                        'PRSV11',
                        'PSSA3',
                        'PTBL3',
                        'PTNT3',
                        'PTNT4',
                        'RANI3',
                        'RAPT3',
                        'RAPT4',
                        'RBDS11',
                        'RBRD11',
                        'RCSL3',
                        'RCSL4',
                        'RDNI3',
                        'REDE3',
                        'RENT3',
                        'RNEW11',
                        'ROMI3',
                        'RPAD3',
                        'RPAD5',
                        'RPAD6',
                        'RPMG3',
                        'RSID3',
                        'SANB11',
                        'SANB3',
                        'SANB4',
                        'SAPR4',
                        'SBSP3',
                        'SCAR3',
                        'SGPS3',
                        'SHPH11',
                        'SHUL4',
                        'SLCE3',
                        'SLED3',
                        'SLED4',
                        'SMAL11',
                        'SMTO3',
                        'SNSY5',
                        'SOND5',
                        'SOND6',
                        'SULA11',
                        'TCNO3',
                        'TCNO4',
                        'TCSA3',
                        'TEKA3',
                        'TEKA4',
                        'TELB3',
                        'TELB4',
                        'TGMA3',
                        'TKNO4',
                        'TOTS3',
                        'TPIS3',
                        'TRIS3',
                        'TRPL3',
                        'TRPL4',
                        'TUPY3',
                        'TXRX3',
                        'TXRX4',
                        'UGPA3',
                        'UNIP3',
                        'UNIP5',
                        'UNIP6',
                        'USIM3',
                        'USIM5',
                        'USIM6',
                        'VALE3',
                        'VLID3',
                        'VULC3',
                        'WEGE3',
                        'WHRL3',
                        'WHRL4',
                    ];

                    if (in_array(trim(substr($line, 12, 12)), $actions)) {
                        $paper = new Paper();

                        $dateTime = \DateTime::createFromFormat('YmdHis', substr($line, 2, 8) . '000000')->modify('+1 day');



                        $paper->date = new \MongoDB\BSON\UTCDateTime($dateTime);

                        //CODBDI - CÓDIGO BDI
                        //UTILIZADO PARA CLASSIFICAR OS PAPÉIS NA EMISSÃO DO BOLETIM DIÁRIO DE INFORMAÇÕES
                        $paper->codbdi = substr($line, 10, 2);
                        //CODNEG - CÓDIGO DE NEGOCIAÇÃO DO PAPEL
                        $paper->codneg = trim(substr($line, 12, 12));
                        //TPMERC - TIPO DE MERCADO
                        //CÓD. DO MERCADO EM QUE O PAPEL ESTÁ CADASTRADO
                        $paper->tpmerc = substr($line, 24, 03);
                        //NOMRES - NOME RESUMIDO DA EMPRESA EMISSORA DO PAPEL
                        $paper->nomres = trim(substr($line, 27, 12));
                        //ESPECI - ESPECIFICAÇÃO DO PAPEL
                        $paper->especi = substr($line, 39, 10);
                        //PRAZOT - PRAZO EM DIAS DO MERCADO A TERMO
                        $paper->prazot = substr($line, 49, 3);
                        //MODREF - MOEDA DE REFERÊNCIA
                        $paper->modref = trim(substr($line, 52, 4));
                        //PREABE - PREÇO DE ABERTURA DO PAPEL- MERCADO NO PREGÃO
                        $paper->preab = (float) substr_replace(substr($line, 56, 13), ".", 11, 0);
                        //PREMAX - PREÇO MÁXIMO DO PAPEL- MERCADO NO PREGÃO
                        $paper->premax = (float) substr_replace(substr($line, 69, 13), ".", 11, 0);
                        //PREMIN - PREÇO MÍNIMO DO PAPEL- MERCADO NO PREGÃO
                        $paper->premin = (float) substr_replace(substr($line, 82, 13), ".", 11, 0);
                        //PREMED - PREÇO MÉDIO DO PAPEL- MERCADO NO PREGÃO
                        $paper->premed = (float) substr_replace(substr($line, 95, 13), ".", 11, 0);
                        //PREULT - PREÇO DO ÚLTIMO NEGÓCIO DO PAPEL-MERCADO NO PREGÃO
                        $paper->preult = (float) substr_replace(substr($line, 108, 13), ".", 11, 0);
                        //PREOFC - PREÇO DA MELHOR OFERTA DE COMPRA DO PAPEL- MERCADO
                        $paper->preofc = (float) substr_replace(substr($line, 121, 13), ".", 11, 0);
                        //PREOFV - PREÇO DA MELHOR OFERTA DE VENDA DO PAPEL- MERCADO
                        $paper->preofv = (float) substr_replace(substr($line, 134, 13), ".", 11, 0);
                        //TOTNEG - NEG. -NÚMERO DE NEGÓCIOS EFETUADOS COM O PAPEL- MERCADO NO PREGÃO
                        $paper->totneg = substr($line, 147, 05);
                        //QUATOT -QUANTIDADE TOTAL DE TÍTULOS NEGOCIADOS NESTE PAPEL- MERCADO
                        $paper->quatot = substr($line, 152, 18);
                        //VOLTOT - VOLUME TOTAL DE TÍTULOS NEGOCIADOS NESTE PAPEL- MERCADO
                        $paper->voltot = substr($line, 170, 16);
                        //PREEXE - PREÇO DE EXERCÍCIO PARA O MERCADO DE OPÇÕES OU VALOR DO CONTRATO PARA O MERCADO DE TERMO SECUNDÁRIO
                        $paper->preexe = substr($line, 188, 11);
                        //INDOPC - INDICADOR DE CORREÇÃO DE PREÇOS DE EXERCÍCIOS OU VALORES  E CONTRATO PARA OS MERCADOS DE OPÇÕES OU TERMO SECUNDÁRIO
                        $paper->indopc = substr($line, 201, 1);
                        //DATVEN - DATA DO VENCIMENTO PARA OS MERCADOS DE OPÇÕES OU TERMO SECUNDÁRIO
                        $paper->datven = substr($line, 202, 8);
                        //FATCOT - FATOR DE COTAÇÃO DO PAPEL
                        $paper->fatcot = substr($line, 210, 7);
                        //PTOEXE - PREÇO DE EXERCÍCIO EM PONTOS PARA OPÇÕES REFERENCIADAS EM DÓLAR OU VALOR DE CONTRATO EM PONTOS PARA TERMO SECUNDÁRIO
                        $paper->ptoexe = substr($line, 217, 7);
                        //CODISI - CÓDIGO DO PAPEL NO SISTEMA ISIN OU CÓDIGO INTERNO DO PAPEL
                        $paper->codisi = substr($line, 230, 12);
                        //DISMES - NÚMERO DE DISTRIBUIÇÃO DO PAPEL
                        $paper->dismes = substr($line, 242, 3);
                        $paper->created_at = date("Y-m-d H:i:s");

                        $paper->save();
                    }
                } catch (Exception $e) {

                    Yii::debug("[IMPORT] error");
                }
            }
            fclose($file);
        } else {

            Yii::debug("[IMPORT] file not found " . $date);
        }

        return "OK";
    }
}
