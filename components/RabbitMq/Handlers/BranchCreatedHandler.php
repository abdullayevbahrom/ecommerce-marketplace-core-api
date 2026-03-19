<?php

namespace app\components\RabbitMq\Handlers;

use app\models\Stock\Stock;
use Yii;

class BranchCreatedHandler
{
    public function handle(array $message): void
    {
        $payload = $message['payload'];

        $tx = Yii::$app->db->beginTransaction();

        try {
            $stock = Stock::findOne(['id' => $payload['id']]);

            if (!$stock) {
                $stock = new Stock();
            }
            
            $stock->suppressSyncEvents = true;
            $stock->shop_id = $payload['yii_shop_id'];
            $stock->name_uz = $payload['name_uz'] ?? null;
            $stock->name_ru = $payload['name_ru'] ?? null;
            $stock->name_en = $payload['name_en'] ?? null;
            $stock->description_uz = $payload['description_uz'] ?? null;
            $stock->description_ru = $payload['description_ru'] ?? null;
            $stock->description_en = $payload['description_en'] ?? null;
            $stock->status = $payload['status'] ?? null;
            $stock->sort = $payload['sort'] ?? null;
            $stock->deleted_at = $payload['deleted_at'] ?? null;
            $stock->bts_region_id = $payload['bts_region_id'] ?? null;
            $stock->bts_city_id = $payload['bts_city_id'] ?? null;
            $stock->save(false);

            $tx->commit();
        } catch (\Throwable $e) {
            $tx->rollBack();
            throw $e;
        }
    }
}