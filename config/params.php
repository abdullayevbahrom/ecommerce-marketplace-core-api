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

$config = [
    'adminEmail' => 'admin@example.com',
    'senderEmail' => 'noreply@example.com',
    'senderName' => 'Example.com mailer',

    // Didox E-IMZO integration settings
    'didoxPartnerToken' => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpZCI6MzAyLCJzdGF0dXMiOiJBQ1RJVkUiLCJuYW1lIjoiXCJNQUlOIFRSQURJTkcgSE9MRElOR1wiIE1DSEoiLCJyb2xlIjoiUEFSVE5FUiIsInRpbiI6IjMwNzg2NTA1OCIsImlhdCI6MTc1MTM0NTA3MywiZXhwIjo0OTA3MTA1MDczfQ.ELZ0nE7sVmYmu2V7TFQAqg68Ut_qCl5Zmehw9cpMAZs', // Add your Didox partner token here

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

    // Wallet Configuration
    'walletServiceUrl' => 'https://wallet.example.com', // External Wallet Service URL
    'walletPaymentId' => 2, // Category ID for wallet payment type (set after inserting into `category` table)
    // INSERT INTO `category` (`parent_id`, `type`, `name_ru`, `name_uz`, `name_en`, `status`, `sort`, `date`) VALUES (0, 'payment', 'Кошелёк', 'Hamyon', 'Wallet', 1, 0, NOW());

    'walletDefaultToken' => 'USDT', // Default token symbol for wallet payments

];

if (YII_ENV_DEV) {
    $config['baseUrl'] = 'http://localhost:8001';
    $config['warehouseApiUrl'] = 'http://sklad_app';
    $config['operatorApiUrl'] = 'http://operator_app';
}

return $config;
