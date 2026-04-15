<?php
/**
 * Product #292 va #294 ning package_code ni 1195749 ga o'zgartirish
 */

defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'dev');

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../../vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/../../config/console.php';
(new yii\console\Application($config));

use app\models\product\Product;

echo "=== PRODUCT PACKAGE_CODE TO'G'RILASH ===\n\n";

// To'g'ridan-to'g'ri SQL orqali yangilash
$db = Yii::$app->db;

// Joriy holat
$rows = $db->createCommand('SELECT id, name_uz, ikpu_code, package_code, package_name FROM product WHERE id IN (292, 294)')->queryAll();

echo "1. JORIY HOLAT:\n";
foreach ($rows as $r) {
    echo "   #{$r['id']} | {$r['name_uz']} | IKPU: {$r['ikpu_code']} | pkg_code: {$r['package_code']} | pkg_name: {$r['package_name']}\n";
}

echo "\n2. PACKAGE_CODE NI 1195749 GA O'ZGARTIRISH...\n";
$result = $db->createCommand(
    "UPDATE product SET package_code = '1195749', package_name = 'шт.' WHERE id IN (292, 294)"
)->execute();
echo "   ✅ {$result} ta product yangilandi\n";

echo "\n3. YAKUNIY HOLAT:\n";
$rows = $db->createCommand('SELECT id, name_uz, ikpu_code, package_code, package_name FROM product WHERE id IN (292, 294)')->queryAll();
foreach ($rows as $r) {
    echo "   #{$r['id']} | {$r['name_uz']} | IKPU: {$r['ikpu_code']} | pkg_code: {$r['package_code']} | pkg_name: {$r['package_name']}\n";
}

echo "\n=== TAMOM ===\n";
