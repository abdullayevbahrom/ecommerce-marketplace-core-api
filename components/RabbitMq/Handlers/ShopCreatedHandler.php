<?php

namespace app\components\RabbitMq\Handlers;

use app\models\shop\Shop;
use Yii;

class ShopCreatedHandler
{
    public function handle(array $message): void
    {
        $payload = $message['payload'];

        $tx = Yii::$app->db->beginTransaction();

        try {
            $shop = Shop::findOne(['id' => $payload['id']]);

            if (!$shop) {
                Yii::warning('Shop not found for sync-back, id: ' . $payload['id'], 'sync');
                $tx->commit();
                return;
            }

            $shop->suppressSyncEvents = true;
            $shop->name_uz = $payload['name_uz'] ?? $shop->name_uz;
            $shop->name_ru = $payload['name_ru'] ?? $shop->name_ru;
            $shop->name_en = $payload['name_en'] ?? $shop->name_en;
            $shop->description_uz = $payload['description_uz'] ?? $shop->description_uz;
            $shop->description_ru = $payload['description_ru'] ?? $shop->description_ru;
            $shop->description_en = $payload['description_en'] ?? $shop->description_en;
            $shop->status = $payload['status'] ?? $shop->status;
            $shop->save(false);

            $tx->commit();
        } catch (\Throwable $e) {
            $tx->rollBack();
            throw $e;
        }
    }
}
