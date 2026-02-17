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
        'telegram' => [
            'class' => 'app\components\TelegramComponent',
            'botToken' => '7436271920:AAEsS2gkJYKnv-gDX-h2DQDTDIGlSuRYJ-Y',
            'chatId' => '-1002232025106',
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
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
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

                // Sync Controller Routes
                'GET api/sync/products/pending' => 'api/sync/pending',
                'POST api/sync/products/confirm' => 'api/sync/confirm',
                'POST api/sync/stock' => 'api/sync/stock',
                'POST api/sync/stock-delete' => 'api/sync/stock-delete',
                'POST api/sync/user' => 'api/sync/user',
                'POST api/sync/user-delete' => 'api/sync/user-delete',

                'POST /api/sync/product' => 'api/sync/product',
                'POST /api/sync/product-delete' => 'api/sync/product-delete',

                'POST /api/sync/category' => 'api/sync/category',
                'POST /api/sync/category-delete' => 'api/sync/category-delete',

                'POST /api/sync/brand' => 'api/sync/brand',
                'POST /api/sync/brand-delete' => 'api/sync/brand-delete',

                'POST /api/sync/ikpu' => 'api/sync/ikpu',
                'POST /api/sync/ikpu-delete' => 'api/sync/ikpu-delete',

                'POST /api/sync/product-type' => 'api/sync/product-type',
                'POST /api/sync/product-type-delete' => 'api/sync/product-type-delete',

                'POST /api/sync/filter' => 'api/sync/filter',
                'POST /api/sync/filter-delete' => 'api/sync/filter-delete',

                'POST /api/sync/color' => 'api/sync/color',
                'POST /api/sync/color-delete' => 'api/sync/color-delete',

                'POST api/sync/moderation' => 'api/sync/moderation',
                'POST api/sync/moderation-comment' => 'api/sync/moderation-comment',

                'POST /api/merchant/question/create' => 'api/merchant-question/create',
                'POST api/warehouse/ticket/reply-from-warehouse' => 'api/warehouse-ticket/reply-from-warehouse',
                'POST api/merchant/question/<id:\d+>/close' => 'api/merchant-question/close',
                'GET /api/merchant/question' => 'api/merchant-question',

                'POST api/warehouse/ticket/close-from-warehouse' => 'api/warehouse-ticket/close-from-warehouse',

                // Wallet API
                'GET api/wallet/balance' => 'api/wallet/balance',
                'POST api/wallet/deploy' => 'api/wallet/deploy',

                'POST /api/session/profile' => 'api/session/profile',
                'POST /api/session/sessions' => 'api/session/sessions',
                'POST /api/session/refresh' => 'api/session/refresh',
                'POST /api/session/logout' => 'api/session/logout',
                'POST /api/session/logout-all' => 'api/session/logout-all'
                
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
