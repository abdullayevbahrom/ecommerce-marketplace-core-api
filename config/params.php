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

return [
    'adminEmail' => 'admin@example.com',
    'senderEmail' => 'noreply@example.com',
    'senderName' => 'Example.com mailer',
    
    // Didox E-IMZO integration settings
    'didoxPartnerToken' => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpZCI6MzAyLCJzdGF0dXMiOiJBQ1RJVkUiLCJuYW1lIjoiXCJNQUlOIFRSQURJTkcgSE9MRElOR1wiIE1DSEoiLCJyb2xlIjoiUEFSVE5FUiIsInRpbiI6IjMwNzg2NTA1OCIsImlhdCI6MTc1MTM0NTA3MywiZXhwIjo0OTA3MTA1MDczfQ.ELZ0nE7sVmYmu2V7TFQAqg68Ut_qCl5Zmehw9cpMAZs', // Add your Didox partner token here
    
    // BTS delivery service integration settings
    'bts_token' => 'sample_logistics_token', // Your current BTS token
    'bts_username' => '8888', // Add your BTS username for token generation
    'bts_password' => '7132', // Add your BTS password for token generation  
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
    'baseUrl' => 'http://localhost:8080',//'http://shop.test',
    'apiSecretKey' => '123',
    'warehouseSyncEnabled' => false,
    'warehouseApiUrl' => 'http://localhost:8000', // Default warehouse API URL
    
    // Sklad uses X-Api-Token: md5(branch_id + apiSecretKey) for authentication
    // Same apiSecretKey is used for both Order sync and Product submissions

    // MyID Integration Settings
    'myid' => [
        'client_id' => '', // Set your MyID Client ID
        'client_secret' => '', // Set your MyID Client Secret
        'redirect_uri' => 'http://shop.test/api/myid/callback', // Your callback URL
        'sandbox' => true, // Set to false for production
        'base_url' => 'https://identity.example.com', // Production URL (used if sandbox is false)
    ],

    // Wallet Configuration
    'walletServiceUrl' => 'https://wallet.example.com', // External Wallet Service URL
];
