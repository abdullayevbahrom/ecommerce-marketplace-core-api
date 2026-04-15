<?php
require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../../vendor/yiisoft/yii2/Yii.php';
(new yii\console\Application(require __DIR__ . '/../../config/console.php'));

$rows = Yii::$app->db->createCommand("SELECT type, content FROM settings WHERE type LIKE 'didox_seller%'")->queryAll();
foreach ($rows as $r) {
    echo $r['type'] . ' => ' . $r['content'] . PHP_EOL;
}
