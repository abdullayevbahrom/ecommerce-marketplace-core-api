<?php
/**
 * Cancel DIDOX document by didox_id.
 *
 * Usage:
 *   docker exec shop-app-1 php /var/www/html/tests/bin/cancel_didox_document.php <didox_id>
 * Example:
 *   docker exec shop-app-1 php /var/www/html/tests/bin/cancel_didox_document.php 3d5837004d9811f1b0fcfa163ea3fd69
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

$app = new yii\console\Application($config);

$didoxId = trim((string)($argv[1] ?? ''));
if ($didoxId === '') {
    echo "❌ didox_id required.\n";
    echo "Usage: php /var/www/html/tests/bin/cancel_didox_document.php <didox_id>\n";
    exit(1);
}

echo "=== CANCEL DIDOX DOCUMENT ===\n";
echo "didox_id: {$didoxId}\n\n";

$doc = \app\models\didox\DidoxDocument::find()->where(['didox_id' => $didoxId])->one();
if (!$doc) {
    echo "⚠️ Local document not found by didox_id, continue API call only.\n";
} else {
    echo "Local doc: #{$doc->id}, current status={$doc->didox_status}\n";
}

$token = (string)\app\models\Settings::find()
    ->select('content')
    ->where(['type' => 'didox_eimzo_token'])
    ->scalar();
$token = trim($token);

$didoxService = new \app\services\DidoxService();
$result = $didoxService->cancelDocument($didoxId, $token);

echo "\nAPI result:\n";
echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";

if (empty($result['success'])) {
    $err = (string)($result['data']['error'] ?? $result['error'] ?? '');
    if (stripos($err, 'route') !== false && stripos($err, '/cancel') !== false) {
        echo "\n⚠️ Cancel route not found. Trying reject fallback...\n";
        $fallback = $didoxService->rejectDocument($didoxId, 'Canceled by system script fallback', $token);
        echo json_encode($fallback, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";

        if (!empty($fallback['success'])) {
            if ($doc) {
                $doc->didox_status = \app\models\didox\DidoxDocument::STATUS_REJECTED;
                $doc->setDidoxData(array_merge($doc->getDidoxDataArray(), (array)($fallback['data'] ?? [])));
                $doc->save(false);
            }
            echo "\n✅ Fallback applied: document rejected.\n";
            echo "\n=== DONE ===\n";
            exit(0);
        }
    }
}

if (!empty($result['success']) && $doc) {
    $doc->didox_status = \app\models\didox\DidoxDocument::STATUS_CANCELED;
    $doc->setDidoxData(array_merge($doc->getDidoxDataArray(), (array)($result['data'] ?? [])));
    $doc->save(false);
    echo "\n✅ Local document updated: status=120 (Canceled)\n";
}

echo "\n=== DONE ===\n";
