<?php
// error_reporting(1);
// ini_set('display_errors', 'stderr');
ini_set('display_errors', '1');
ini_set('log_errors', '1');
ini_set('error_log', 'php://stderr');
error_reporting(E_ALL);

// ── Global CORS ──
$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
header("Access-Control-Allow-Origin: $origin");
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, HEAD, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Auth-Token, Origin, Authorization, Accept, Content-Language');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Max-Age: 86400');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

// comment out the following two lines when deployed to production
defined('YII_ENV') or define('YII_ENV', getenv('YII_ENV') ?: 'prod');
defined('YII_DEBUG') or define('YII_DEBUG', getenv('YII_DEBUG') === 'true');

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';

// services
require(__DIR__ . '/../services/Sms.php');
require(__DIR__ . '/../services/SmsService.php');
require(__DIR__ . '/../services/Translate.php');
require __DIR__ . '/../services/Facebook.php';
require __DIR__ . '/../services/Google.php';
require __DIR__ . '/../services/Vk.php';
require __DIR__ . '/../services/Click.php';
require __DIR__ . '/../services/Payme.php';
require __DIR__ . '/../services/PaymeSubscribe.php';
require __DIR__ . '/../services/Octo.php';
require __DIR__ . '/../services/Transliterate.php';
require __DIR__ . '/../services/Fcm.php';
require __DIR__ . '/../services/Cbu.php';
require __DIR__ . '/../services/Billz.php';
require __DIR__ . '/../services/ImageHash.php';
require __DIR__ . '/../services/BTS.php';

$config = require __DIR__ . '/../config/web.php';

(new yii\web\Application($config))->run();
