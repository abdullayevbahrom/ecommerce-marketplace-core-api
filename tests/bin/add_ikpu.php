<?php
require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../../vendor/yiisoft/yii2/Yii.php';
$config = require __DIR__ . '/../../config/console.php';
$app = new yii\console\Application($config);

$ikpu = new \app\models\Ikpu();
$ikpu->code = '06912001001000000';
$ikpu->name_ru = 'Прочие готовые пищевые продукты';
$ikpu->name_uz = 'Boshqa tayyor oziq-ovqat mahsulotlari';

if ($ikpu->save(false)) {
    echo "IKPU 06912001001000000 qo'shildi\n";
} else {
    echo "Xato: " . json_encode($ikpu->errors) . "\n";
}
