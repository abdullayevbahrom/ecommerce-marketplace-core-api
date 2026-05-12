<?php
/**
 * Try signed cancel flow:
 * 1) /tosign action=cancel
 * 2) sign payload with configured PFX signer
 * 3) timestamp
 * 4) POST /v1/documents/{id}/cancel with signature
 *
 * Usage:
 * docker exec shop-app-1 php /var/www/html/tests/bin/test_cancel_signed.php <didox_id>
 */

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../../vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/../../config/console.php';

class DummySession extends \yii\web\Session {
    public function open() {}
    public function get($key, $default = null) { return $default; }
    public function set($key, $value) {}
    public function has($key) { return false; }
    public function remove($key) {}
}

$config['components']['session'] = ['class' => 'DummySession'];
$config['components']['user'] = [
    'class' => 'yii\web\User',
    'identityClass' => 'app\models\user\User',
    'enableAutoLogin' => false,
    'enableSession' => false,
];

new yii\console\Application($config);

$didoxId = trim((string)($argv[1] ?? ''));
if ($didoxId === '') {
    echo "Usage: php /var/www/html/tests/bin/test_cancel_signed.php <didox_id>\n";
    exit(1);
}

$svc = new \app\services\DidoxService();
$userKey = trim((string)\app\models\Settings::find()
    ->select('content')
    ->where(['type' => 'didox_eimzo_token'])
    ->scalar());

echo "=== SIGNED CANCEL TEST ===\n";
echo "didox_id: {$didoxId}\n\n";

$toSign = $svc->getDocumentToSign($didoxId, 'cancel', $userKey);
echo "1) tosign(cancel):\n" . json_encode($toSign, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n\n";

$payload = $toSign['data']['data'] ?? null;
if (!$payload) {
    echo "No payload from tosign(cancel)\n";
    exit(1);
}

$payloadJson = json_encode($payload, JSON_UNESCAPED_UNICODE);
$payloadB64 = base64_encode($payloadJson);

$ref = new ReflectionClass($svc);
$signMethod = $ref->getMethod('signConfiguredPfxPayload');
$signMethod->setAccessible(true);
$signed = $signMethod->invoke($svc, $payloadB64);
echo "2) signer result:\n" . json_encode($signed, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n\n";
if (empty($signed['success'])) {
    exit(1);
}

$ts = $svc->createTimestamp($signed['pkcs7'], $signed['signature']);
echo "3) timestamp result:\n" . json_encode($ts, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n\n";
$finalSig = $ts['data']['timeStampTokenB64'] ?? null;
if (!$finalSig) {
    echo "No timeStampTokenB64\n";
    exit(1);
}

$makeReq = $ref->getMethod('makeRequestWithHeaders');
$makeReq->setAccessible(true);

$partnerTokenProp = $ref->getProperty('partnerToken');
$partnerTokenProp->setAccessible(true);
$partnerToken = (string)$partnerTokenProp->getValue($svc);

$headers = [
    'Content-Type: application/json',
    'Partner-Authorization: ' . $partnerToken,
];
if ($userKey !== '') {
    $headers[] = 'user-key: ' . $userKey;
}

$response = $makeReq->invoke(
    $svc,
    'POST',
    '/v1/documents/' . $didoxId . '/cancel',
    ['signature' => $finalSig],
    $headers
);

echo "4) cancel(sign) response:\n" . json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
echo "\n=== DONE ===\n";

