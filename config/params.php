<?php

$minioUser = getenv('MINIO_USER') ?: 'app';
$minioPassword = getenv('MINIO_PASSWORD') ?: 'password';
$minioPort = getenv('MINIO_PORT') ?: 9000;
$minioUiPort = getenv('MINIO_UI_PORT') ?: 9001;
$minioPublicEndpoint = getenv('MINIO_PUBLIC_ENDPOINT') ?: 'https://files.example.com';
$minioEndpoint = getenv('MINIO_ENDPOINT') ?: 'http://minio:9000';
$minioBucket = getenv('MINIO_BUCKET') ?: 'uploads';
$minioRegion = getenv('MINIO_REGION') ?: 'us-east-1';
$minioSecure = getenv('MINIO_SECURE') ?: true;

$rabbitMqHost = getenv('RABBITMQ_HOST') ?: 'rabbitmq';
$rabbitMqPort = getenv('RABBITMQ_PORT') ?: 5672;
$rabbitMqUser = getenv('RABBITMQ_USER') ?: 'guest';
$rabbitMqPassword = getenv('RABBITMQ_PASSWORD') ?: 'guest';
$rabbitMqExchangeSkladToMarket = getenv('RABBITMQ_EXCHANGE_SKLAD_TO_MARKET') ?: 'sklad_to_market';
$rabbitMqExchangeMarketToSklad = getenv('RABBITMQ_EXCHANGE_MARKET_TO_SKLAD') ?: 'market_to_sklad';
$rabbitMqExchangeSkladToMarketRetry = getenv('RABBITMQ_EXCHANGE_SKLAD_TO_MARKET_RETRY') ?: 'sklad_to_market.retry';
$rabbitMqExchangeMarketToSkladRetry = getenv('RABBITMQ_EXCHANGE_MARKET_TO_SKLAD_RETRY') ?: 'market_to_sklad.retry';
$rabbitMqQueueSkladSyncMain = getenv('RABBITMQ_QUEUE_SKLAD_SYNC_MAIN') ?: 'sklad.sync.main';
$rabbitMqQueueMarketSyncMain = getenv('RABBITMQ_QUEUE_MARKET_SYNC_MAIN') ?: 'market.sync.main';

$botToken = getenv('TELEGRAM_BOT_TOKEN') ?: null;
$chatId = getenv('TELEGRAM_CHAT_ID') ?: null;

$config = [
    'jwt' => [
        'issuer' => 'marketplace',
        'audience' => ['marketplace', 'warehouse', 'operator'],
        'privateKeyPath' => '@app/storage/jwt/private.pem',
        'publicKeys' => [
            'marketplace' => '@app/storage/jwt/marketplace_public.pem',
        ],
        'accessTtl' => 900,
        'refreshTtl' => 60 * 60 * 24 * 30,
    ],
    'adminEmail' => 'admin@example.com',
    'senderEmail' => 'noreply@example.com',
    'senderName' => 'Example.com mailer',

    // Didox E-IMZO integration settings
    'didoxPartnerToken' => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpZCI6MjM4LCJzdGF0dXMiOiJBQ1RJVkUiLCJuYW1lIjoiXCJNQVJDQSBDQVBJVEFMIFRBU0hLRU5UXCIgTUNISiIsInJvbGUiOiJQQVJUTkVSIiwidGluIjoiMzEyNDYzMDk4IiwiaWF0IjoxNzYwOTM2NzIwfQ.Dr8fBTTwJ2O5KRh98tOtBeF4vfc8w4hxTye0hJ-qPSc', // Add your Didox partner token here
    'didoxTestTaxId' => '123456789', // TIN for testing Didox integration for https://testapi.einvoice.example.com (should be registered in Didox system)

    // BTS delivery service integration settings
    'bts_token' => 'sample_logistics_token', // Your current BTS token
    'bts_username' => '8198', // Add your BTS username for token generation
    'bts_password' => '4?Fp&e-Icz', // Add your BTS password for token generation  
    'bts_inn' => '123456789', // Add your company INN for BTS authentication

    // MinIO settings
    'minio' => [
        'user' => $minioUser,
        'password' => $minioPassword,
        'port' => $minioPort,
        'uiPort' => $minioUiPort,
        'publicEndpoint' => $minioPublicEndpoint,
        'endpoint' => $minioEndpoint,
        'bucket' => $minioBucket,
        'region' => $minioRegion,
        'secure' => $minioSecure
    ],
    'rabbitmq' => [
        'host' => $rabbitMqHost,
        'port' => $rabbitMqPort,
        'user' => $rabbitMqUser,
        'password' => $rabbitMqPassword,
        'vhost' => '/',
        'enable_reference_events' => filter_var(getenv('RABBITMQ_ENABLE_REFERENCE_EVENTS') ?: false, FILTER_VALIDATE_BOOL),
        'enable_moderation_events' => filter_var(getenv('RABBITMQ_ENABLE_MODERATION_EVENTS') ?: false, FILTER_VALIDATE_BOOL),
        'enable_product_events' => filter_var(getenv('RABBITMQ_ENABLE_PRODUCT_EVENTS') ?: false, FILTER_VALIDATE_BOOL),
        'enable_asl_belgisi_events' => filter_var(getenv('RABBITMQ_ENABLE_ASL_BELGISI_EVENTS') ?: false, FILTER_VALIDATE_BOOL),
        'exchange_sklad_to_market' => $rabbitMqExchangeSkladToMarket,
        'exchange_market_to_sklad' => $rabbitMqExchangeMarketToSklad,
        'exchange_sklad_to_market_retry' => $rabbitMqExchangeSkladToMarketRetry,
        'exchange_market_to_sklad_retry' => $rabbitMqExchangeMarketToSkladRetry,
        'queue_sklad_sync_main' => $rabbitMqQueueSkladSyncMain,
        'queue_market_sync_main' => $rabbitMqQueueMarketSyncMain,
    ],
    // Base URL
    'baseUrl' => 'https://api.example.com',
    'operatorApiUrl' => 'https://api.operator.example.com', // Default operator API URL
    'warehouseApiUrl' => 'https://api.warehouse.example.com', // Default warehouse API URL
    'apiSecretKey' => '123',
    'warehouseSyncEnabled' => false,

    // Sklad uses X-Api-Token: md5(branch_id + apiSecretKey) for authentication
    // Same apiSecretKey is used for both Order sync and Product submissions

    // MyID Integration Settings
    'myid' => [
        'client_id' => 'sample_client_id',
        'client_secret' => 'X5wRGK18FguxdCqUlvDrVEljezRUU5KkDJgBRKMjVDhMU1bXXk3wiiU9bQwSFTo9BHZzwcnnjWolBGVSQQ6rRSmr7ebhBg6i04ri',
        'client_hash_id' => '26854a43-bb59-43fb-a454-79492a745f96',
        'redirect_uri' => 'http://shop.test/api/myid/callback',
        'sandbox' => true,
        'base_url' => 'https://api.devid.example.com',
        'web_url' => 'https://web.devid.example.com',
    ],

    // ASL Belgisi product registry integration
    'aslBelgisi' => [
        'apiKey' => getenv('ASL_BELGISI_API_KEY') ?: 'fdf30890-20f9-4d0e-9ba1-3e2e0968a1e8',
        'baseUrl' => 'https://xtrace.marking.example.com',
    ],

    // E-IMZO direct integration (e-imzo-server instance)
    'eimzo' => [
        'serverUrl' => getenv('EIMZO_SERVER_URL') ?: 'http://127.0.0.1:8080',
        'siteId' => getenv('EIMZO_SITE_ID') ?: '3383',
        // Registered upload URL for mobile PKCS#7 delivery
        'uploadUrl' => 'https://api.example.com/v1/integration/eimzo',
        // Status polling interval hint for mobile clients (seconds)
        'mobileStatusPollInterval' => 5,
        // Status polling timeout (seconds)
        'mobileStatusTimeout' => 120,
    ],
    'telegram' => [
        'botToken' => $botToken,
        'chatId' => $chatId,
    ],

    // Wallet Configuration
    'walletServiceUrl' => 'https://wallet.example.com', // External Wallet Service URL
    'walletPaymentId' => 101, // Category ID for Marco crypto pay

    'walletDefaultToken' => 'USDT', // Default token symbol for wallet payments

    // Currency backend URL — hosts the /api/app/pay/order/{id} endpoint for crypto payments.
    // Falls back to walletServiceUrl if not set.
    'currencyBackendUrl' => getenv('CURRENCY_BACKEND_URL') ?: 'https://wallet.example.com',

    // Sklad API URL — for POS QR payment session resolution
    'skladApiUrl' => getenv('SKLAD_API_URL') ?: 'https://api.warehouse.example.com',

    // UZS to USDT exchange rate (default 1:1 for testing)
    'uzsToUsdtRate' => (float) (getenv('UZS_TO_USDT_RATE') ?: 1.0),

];

if (YII_ENV_DEV) {
    $config['baseUrl'] = getenv('BASE_URL') ?: 'http://localhost:8002';
    // In local docker network warehouse service is reachable as "sklad_app"
    $config['warehouseApiUrl'] = getenv('WAREHOUSE_API_URL') ?: 'http://sklad_app';
    $config['skladApiUrl'] = getenv('SKLAD_API_URL') ?: 'http://sklad_app';
    $config['operatorApiUrl'] = getenv('OPERATOR_API_URL') ?: 'http://operator_app';
}

return $config;
