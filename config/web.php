<?php

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

$config = [
    'id' => 'basic',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'defaultRoute' => '/main/index',
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'modules' => [
        'admin' => [
            'class' => 'app\modules\admin\Module'
        ],
        'shop' => [
            'class' => 'app\modules\shop\Module'
        ],
        'logist' => [
            'class' => 'app\modules\logist\Module'
        ],
        'api' => [
            'class' => 'app\modules\api\Module'
        ],
        'dashboard' => [
            'class' => 'app\modules\dashboard\Module'
        ],
        'billz' => [
            'class' => 'app\modules\billz\Module'
        ],
    ],
    'components' => [
        'exceptionNotifier' => [
            'class' => 'app\components\TelegramExceptionNotifier',
            'appName' => 'shop',
        ],
        'telegram' => [
            'class' => 'app\components\TelegramComponent',
            'botToken' => $params['telegram']['botToken'],
            'chatId' => $params['telegram']['chatId'],
        ],
        'assetManager' => [
            'bundles' => [
                'yii\bootstrap4\BootstrapAsset' => [
                    'css' => [],
                ],
                'yii\web\JqueryAsset' => [
                    'sourcePath' => null,
                    'js' => [
                        '/assets_files/js/jquery-3.4.1.min.js',
                    ]
                ],
            ],
        ],
        'request' => [
            // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
            'cookieValidationKey' => 'sample_cookie_validation_secret_key',
            'baseUrl' => '',
            'csrfParam' => '_csrf-app',
            'parsers' => [
                'application/json' => 'yii\web\JsonParser',
            ]
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'user' => [
            'identityClass' => 'app\models\user\User',
            'enableAutoLogin' => true,
        ],
        'errorHandler' => [
            'class' => 'app\components\WebErrorHandler',
            'errorAction' => '/main/error',
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
            'attempts' => 5,
            'ttr' => 60,
        ],
        'db' => $db,
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                // Sklad API endpoints (DEACTIVATED: Pending Product Logic)
                // 'POST api/warehouse-products/submit' => 'api/warehouse-products/submit',
                // 'GET api/warehouse-products/status' => 'api/warehouse-products/status',

                // Attribute CRUD endpoints (Sklad Integration) - READ ONLY
                'GET api/product-attribute/category' => 'api/product-attribute/category-list',
                'GET api/product-attribute/tag' => 'api/product-attribute/tag-list',
                'GET api/product-attribute/brand' => 'api/product-attribute/brand-list',
                'GET api/product-attribute/color' => 'api/product-attribute/color-list',
                'GET api/product-attribute/product-type' => 'api/product-attribute/product-type-list',
                'GET api/product-attribute/filter' => 'api/product-attribute/filter-list',
                'GET api/product-attribute/ikpu' => 'api/product-attribute/ikpu-list',
                'GET api/product-attribute/user' => 'api/product-attribute/user-list',
                'GET api/product-attribute/shop' => 'api/product-attribute/shop-list',
                'GET api/product-attribute/stock' => 'api/product-attribute/stock-list',
                'GET api/product-attribute/region' => 'api/product-attribute/region-list',
                'GET api/product-attribute/delivery' => 'api/product-attribute/delivery-list',
                'GET api/product-attribute/office' => 'api/product-attribute/office-list',
                'GET api/product-attribute/unit' => 'api/product-attribute/unit-list',
                'GET api/product-attribute/currency' => 'api/product-attribute/currency-list',

                // Product Management (Sklad Integration)
                'GET api/filters' => 'api/product/filters',
                'GET api/products' => 'api/product/index',
                'POST api/product/create' => 'api/product/create',
                'PUT api/product/update' => 'api/product/update',
                'POST api/product/delete' => 'api/product/delete',
                'OPTIONS api/product/update' => 'product/update',

                'POST /api/category/create' => 'api/category/create',
                'PUT  /api/category/update' => 'api/category/update',
                'POST /api/category/delete' => 'api/category/delete',

                'POST /api/brand-category/create' => 'api/brand-category/create',
                'PUT  /api/brand-category/update' => 'api/brand-category/update',
                'POST /api/brand-category/delete' => 'api/brand-category/delete',

                'POST /api/merchant/question/create' => 'api/merchant-question/create',
                'POST api/warehouse/ticket/reply-from-warehouse' => 'api/warehouse-ticket/reply-from-warehouse',
                'POST api/merchant/question/<id:\d+>/close' => 'api/merchant-question/close',
                'GET /api/merchant/question' => 'api/merchant-question',

                'POST api/warehouse/ticket/close-from-warehouse' => 'api/warehouse-ticket/close-from-warehouse',

                // MyID verification endpoints
                'POST api/myid/init-web' => 'api/myid/init-web',
                'POST api/myid/callback' => 'api/myid/callback',
                'POST api/myid/verify' => 'api/myid/verify',
                'POST api/myid/register' => 'api/myid/register',
                'GET api/myid/status' => 'api/myid/status',
                'GET api/myid/sdk-config' => 'api/myid/sdk-config',
                'GET api/myid/session-result' => 'api/myid/session-result',
                'POST api/myid/create-session' => 'api/myid/create-session',
                'GET api/myid/session-status' => 'api/myid/session-status',

                // E-IMZO direct integration endpoints
                'POST api/eimzo/challenge' => 'api/eimzo/challenge',
                'POST api/eimzo/timestamp' => 'api/eimzo/timestamp',
                'POST api/eimzo/digest' => 'api/eimzo/digest',
                'POST api/eimzo/auth' => 'api/eimzo/auth',
                'POST api/eimzo/verify' => 'api/eimzo/verify',
                'POST api/eimzo/sign' => 'api/eimzo/sign',
                'GET api/eimzo/ping' => 'api/eimzo/ping',
                'GET api/eimzo/info' => 'api/eimzo/info',

                // E-IMZO mobile deeplink flow
                'POST api/eimzo/mobile/auth' => 'api/eimzo/mobile-auth',
                'POST api/eimzo/mobile/sign' => 'api/eimzo/mobile-sign',
                'POST api/eimzo/mobile/status' => 'api/eimzo/mobile-status',
                'POST api/eimzo/mobile/auth-result' => 'api/eimzo/mobile-auth-result',
                'POST api/eimzo/mobile/verify' => 'api/eimzo/mobile-verify',

                // Wallet API
                'GET api/wallet/balance' => 'api/wallet/balance',
                'POST api/wallet/deploy' => 'api/wallet/deploy',
                'POST api/wallet/send' => 'api/wallet/send',
                'POST api/wallet/mint-to-user' => 'api/wallet/mint-to-user',

                'POST /api/session/profile' => 'api/session/profile',
                'POST /api/session/sessions' => 'api/session/sessions',
                'POST /api/session/refresh' => 'api/session/refresh',
                'POST /api/session/logout' => 'api/session/logout',
                'POST /api/session/logout-all' => 'api/session/logout-all',

                'GET api/promocode/my' => 'api/promocode/my',
                'POST api/promocode/check' => 'api/promocode/check',
                'POST api/promocode/apply' => 'api/promocode/apply',

                // Crypto payment execution
                'POST api/app/pay/order/<orderId:\d+>' => 'api/payment/pay-order',

            ],
        ],
        'image' => [
            'class' => 'Intervention\Image\ImageManager',
        ],
        's3' => [
            'class' => 'app\components\S3Component',
        ],
    ],
    'params' => $params,
];

if (YII_ENV_DEV) {
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
    ];
    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
    ];
}

return $config;
