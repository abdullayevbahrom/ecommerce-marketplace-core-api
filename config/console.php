<?php

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

$config = [
    'id' => 'basic-console',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log', 'queue'],
    'controllerNamespace' => 'app\commands',
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
        '@tests' => '@app/tests',
    ],
    'components' => [
        'exceptionNotifier' => [
            'class' => 'app\components\TelegramExceptionNotifier',
            'appName' => 'shop-console',
        ],
        'telegram' => [
            'class' => 'app\components\TelegramComponent',
            'botToken' => $params['telegram']['botToken'],
            'chatId' => $params['telegram']['chatId'],
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'errorHandler' => [
            'class' => 'app\components\ConsoleErrorHandler',
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                // [
                //     'class' => 'yii\log\FileTarget',
                //     'levels' => ['error', 'warning'],
                // ],
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning', 'info'],
                    'logFile' => 'php://stderr',
                    'enableRotation' => false,
                    'exportInterval' => 1,
                    'logVars' => [],
                ],
            ],
        ],
        'httpClient' => [
            'class' => \GuzzleHttp\Client::class,
            '__construct()' => [
                [
                    'timeout' => 20,
                    'connect_timeout' => 10,
                    'verify' => YII_ENV_DEV ? false : true,
                    'headers' => [
                        'Accept' => 'application/json',
                    ],
                    'http_errors' => false,
                ]
            ],
        ],
        'elasticsearch' => [
            'class' => 'yii\elasticsearch\Connection',
            'nodes' => [
                ['http_address' => 'elasticsearch:9200'],
            ],
        ],
        'redis' => [
            'class' => 'yii\redis\Connection',
            'hostname' => 'redis',
            'port' => 6379,
            'database' => 0,
        ],
        'queue' => [
            'class' => 'yii\queue\redis\Queue',
            'redis' => 'redis',
            'channel' => 'es-index',
            'as log' => 'yii\queue\LogBehavior',
            'attempts' => 5,
            'ttr' => 60,
        ],
        'db' => $db,
        's3' => [
            'class' => 'app\components\S3Component',
        ],
    ],
    'params' => $params,

    'controllerMap' => [
        // 'fixture' => [ // Fixture generation command line.
        //     'class' => 'yii\faker\FixtureController',
        // ],
    ],

];


if (YII_ENV_DEV) {
    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
    ];
}

return $config;
