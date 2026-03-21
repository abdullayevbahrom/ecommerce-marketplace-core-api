<?php

namespace app\components\RabbitMq\Handlers;

use app\models\shop\Shop;
use Yii;

class ShopDeletedHandler
{
    public function handle(array $message): void
    {
        $payload = $message['payload'];

        $tx = Yii::$app->db->beginTransaction();

        try {
            $shop = Shop::findOne(['id' => $payload['id']]);

            if (!$shop) {
                $tx->commit();
                return;
            }

            $shop->suppressSyncEvents = true;
            $shop->removeObject();

            $tx->commit();
        } catch (\Throwable $e) {
            $tx->rollBack();
            throw $e;
        }
    }
}
