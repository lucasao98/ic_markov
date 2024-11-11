<?php
$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

Yii::setAlias('@web', (stripos($_SERVER['SERVER_PROTOCOL'],'https') === true ? 'https://' : 'http://' . $_SERVER['SERVER_NAME'] . ($_SERVER['SERVER_PORT'] !== '80' ? ':' . $_SERVER['SERVER_PORT'] : '')));
Yii::setAlias('@webroot', dirname(__DIR__) . '/web');
Yii::setAlias('composer', dirname(__FILE__) . '/../../../vendor');

$config = [
    'id' => 'basic',
    'language' => 'pt_BR',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
        '@cmtdUrl' => '@web/index.php?r=exercicios/cmtd',
        '@cmoUrl' => '@web/index.php?r=exercicios/cmo'
    ],
    
    'controllerMap' => [
        'job-queue' => [
            'class' => \yiicod\jobqueue\commands\JobQueueCommand::class,
        ]
     ],
     
    'components' => [
        'request' => [
            // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
            'cookieValidationKey' => 'tmC8OAkIX2onr1DLBAF4IYtAL41Q_eEA',
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'user' => [
            'identityClass' => 'app\models\User',
            'enableAutoLogin' => true,
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'mailer' => [
            'class' => 'yii\swiftmailer\Mailer',
            // send all mails to a file by default. You have to set
            // 'useFileTransport' to false and configure a transport
            // for the mailer to send real emails.
            'useFileTransport' => true,
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        
        'mongodb' => [
            'class' => '\yii\mongodb\Connection',
             'dsn' => 'mongodb://localhost:27017/',
             'defaultDatabaseName' => 'stock'
        ],
        
        'db' => $db,

        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'enableStrictParsing' => true,
            'rules' => [
                '' => 'main/home',
                'home' => 'main/home',
                'predict-result-interval' => 'main/predict-result-interval',
                'predict-three-states' => 'main/predict-three-states',
                'validate' => 'main/validate',
                'teste' => 'main/teste',
                'predict-three-states-test' => 'main/predict-three-states-test',
                'about' => 'main/about',
                'steady-state-predict' => 'main/steady-state-predict',
                'steady-state-test' => 'main/steady-state-test',
                'steady-state-automatic' => 'main/steady-state-automatic',
                'first-passage-time' => 'main/first-passage-time',
                'first-passage-time-result' => 'main/first-passage-time-result',
                'calculo-media-desvio' => 'main/calculo-media-desvio',
                'import' => 'import/import-form',
                'import-data' => 'import/import-data',
                'annual-analysis' => 'main/annual-analysis',
                'join-methods' => 'join/index',
                'heuristic-m3' => 'heuristic-m3/index',
                'heuristic-m1-plus' => 'heuristic-m1-plus/index',
                'automatic-heuristic-m3' => 'automatic-heuristic-m3/index',
                'automatic-heuristic-m1-plus' => 'automatic-heuristic-m1-plus/index',
                'result-automatic-heuristic-m3' => 'automatic-heuristic-m3/result'
            ],
        ],
        
        'queue' => [
            'class' => \yii\queue\file\Queue::class,
            'path' => '@runtime/queue',
        ]
    ],
    
    'params' => $params
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];
}

return $config;
