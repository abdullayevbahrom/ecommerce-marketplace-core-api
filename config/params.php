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
$rabbitMqExchangeAuthOutbox = getenv('RABBITMQ_EXCHANGE_AUTH_OUTBOX') ?: 'auth.outbox';
$rabbitMqQueueSkladSyncMain = getenv('RABBITMQ_QUEUE_SKLAD_SYNC_MAIN') ?: 'sklad.sync.main';
$rabbitMqQueueMarketSyncMain = getenv('RABBITMQ_QUEUE_MARKET_SYNC_MAIN') ?: 'market.sync.main';
$rabbitMqQueueAuthOutboxShop = getenv('RABBITMQ_QUEUE_AUTH_OUTBOX_SHOP') ?: 'auth.outbox.shop';

$botToken = getenv('TELEGRAM_BOT_TOKEN') ?: null;
$chatId = getenv('TELEGRAM_CHAT_ID') ?: null;
$authMode = strtolower(getenv('AUTH_MODE') ?: 'legacy');
$authGatewayJwksUrl = getenv('AUTH_GATEWAY_JWKS_URL') ?: 'http://auth_gateway_app/api/auth/jwks';
$authGatewayIssuer = getenv('AUTH_GATEWAY_ISSUER') ?: 'auth-gateway';
$authGatewayAudience = getenv('AUTH_GATEWAY_AUDIENCE') ?: 'marketplace';
$authGatewayTimeoutMs = (int) (getenv('AUTH_GATEWAY_TIMEOUT_MS') ?: 1500);
$authGatewayJwksCacheTtl = (int) (getenv('AUTH_GATEWAY_JWKS_CACHE_TTL') ?: 3600);
$internalApiToken = getenv('INTERNAL_API_TOKEN') ?: '';

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
    'auth' => [
        'mode' => in_array($authMode, ['legacy', 'gateway', 'hybrid'], true) ? $authMode : 'legacy',
        'gateway' => [
            'jwksUrl' => $authGatewayJwksUrl,
            'issuer' => $authGatewayIssuer,
            'audience' => $authGatewayAudience,
            'timeoutMs' => $authGatewayTimeoutMs > 0 ? $authGatewayTimeoutMs : 1500,
            'jwksCacheTtl' => $authGatewayJwksCacheTtl > 0 ? $authGatewayJwksCacheTtl : 3600,
            'internalToken' => $internalApiToken,
        ],
    ],
    'adminEmail' => 'admin@example.com',
    'senderEmail' => 'noreply@example.com',
    'senderName' => 'Example.com mailer',

    // Didox E-IMZO integration settings
    'didoxPartnerToken' => getenv('DIDOX_PARTNER_TOKEN') ?: 'sample_partner_token',
    'didoxTestTaxId' => getenv('DIDOX_TEST_TAX_ID') ?: '123456789',
    'didoxInvoiceForceSumOne' => filter_var(getenv('DIDOX_INVOICE_FORCE_SUM_ONE') ?: false, FILTER_VALIDATE_BOOL),

    // Logistics / Delivery integration settings
    'bts_token' => getenv('BTS_TOKEN') ?: 'sample_logistics_token',
    'bts_username' => getenv('BTS_LOGIN') ?: null,
    'bts_password' => getenv('BTS_PASSWORD') ?: null,
    'bts_inn' => getenv('BTS_INN') ?: '123456789',

    'bts' => [
        'url' => getenv('BTS_URL') ?: 'https://apitest.logistics.example.com',
        'version' => getenv('BTS_VERSION') ?: 'v1',
        'login' => getenv('BTS_LOGIN') ?: null,
        'password' => getenv('BTS_PASSWORD') ?: null,
    ],
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
        'exchange_auth_outbox' => $rabbitMqExchangeAuthOutbox,
        'queue_sklad_sync_main' => $rabbitMqQueueSkladSyncMain,
        'queue_market_sync_main' => $rabbitMqQueueMarketSyncMain,
        'queue_auth_outbox_shop' => $rabbitMqQueueAuthOutboxShop,
    ],
    // Base URL
    'baseUrl' => getenv('BASE_URL') ?: 'https://api.example.com',
    'operatorApiUrl' => getenv('OPERATOR_API_URL') ?: 'https://api.operator.example.com',
    'warehouseApiUrl' => getenv('WAREHOUSE_API_URL') ?: 'https://api.warehouse.example.com',
    'apiSecretKey' => getenv('API_SECRET_KEY') ?: 'secret_api_key',
    'warehouseSyncEnabled' => true,

    // Identity Verification (MyID) Settings
    'myid' => [
        'client_id' => getenv('MYID_CLIENT_ID') ?: 'sample_client_id',
        'client_secret' => getenv('MYID_CLIENT_SECRET') ?: 'sample_client_secret',
        'client_hash_id' => getenv('MYID_CLIENT_HASH_ID') ?: 'sample_hash_id',
        'redirect_uri' => getenv('MYID_REDIRECT_URI') ?: 'http://localhost/api/myid/callback',
        'sandbox' => true,
        'base_url' => 'https://api.devid.example.com',
        'web_url' => 'https://web.devid.example.com',
    ],

    // Product marking registry integration
    'aslBelgisi' => [
        'apiKey' => getenv('ASL_BELGISI_API_KEY') ?: 'sample_api_key',
        'baseUrl' => 'https://xtrace.marking.example.com',
    ],

    // E-IMZO direct integration (e-imzo-server instance)
    'eimzo' => [
        'serverUrl' => getenv('EIMZO_SERVER_URL') ?: 'http://127.0.0.1:8080',
        'siteId' => getenv('EIMZO_SITE_ID') ?: '1234',
        // Registered upload URL for mobile PKCS#7 delivery
        'uploadUrl' => 'https://api.example.com/v1/integration/eimzo',
        'mobileStatusPollInterval' => 5,
        'mobileStatusTimeout' => 120,
    ],

    'telegram' => [
        'botToken' => $botToken,
        'chatId' => $chatId,
    ],

    // Wallet Configuration
    'walletServiceUrl' => getenv('WALLET_SERVICE_URL') ?: 'https://wallet.example.com',
    'walletPaymentId' => 101,
    'walletDefaultToken' => 'USDT',
    'currencyBackendUrl' => getenv('CURRENCY_BACKEND_URL') ?: 'https://wallet.example.com',
    'skladApiUrl' => getenv('SKLAD_API_URL') ?: 'https://api.warehouse.example.com',
    'uzsToUsdtRate' => (float) (getenv('UZS_TO_USDT_RATE') ?: 1.0),
];

if (YII_ENV_DEV) {
    $config['baseUrl'] = getenv('BASE_URL') ?: 'http://localhost:8002';
    $config['warehouseApiUrl'] = getenv('WAREHOUSE_API_URL') ?: 'http://sklad_app';
    $config['skladApiUrl'] = getenv('SKLAD_API_URL') ?: 'http://sklad_app';
    $config['operatorApiUrl'] = getenv('OPERATOR_API_URL') ?: 'http://operator_app';
}

return $config;
