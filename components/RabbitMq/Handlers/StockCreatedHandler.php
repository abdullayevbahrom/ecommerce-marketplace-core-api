<?php

namespace app\components\RabbitMq\Handlers;

use app\models\stock\Stock;
use Yii;

class StockCreatedHandler
{
    public function handle(array $message): void
    {
        $payload = $message['payload'];
        $warehouseBranchId = $message['entity_id'];

        $tx = Yii::$app->db->beginTransaction();

        try {
            $stock = new Stock();
            $stock->suppressSyncEvents = true;
            $stock->shop_id = $payload['yii_shop_id'] ?? $payload['shop_id'] ?? null;
            $stock->name_uz = $payload['name_uz'] ?? null;
            $stock->name_ru = $payload['name_ru'] ?? null;
            $stock->name_en = $payload['name_en'] ?? null;
            $stock->description_uz = $payload['description_uz'] ?? null;
            $stock->description_ru = $payload['description_ru'] ?? null;
            $stock->description_en = $payload['description_en'] ?? null;
            $stock->status = $payload['status'] ?? null;
            $stock->sort = $payload['sort'] ?? null;
            $stock->bts_region_id = $payload['bts_region_id'] ?? null;
            $stock->bts_city_id = $payload['bts_city_id'] ?? null;
            $stock->save(false);

            $tx->commit();

            $token = md5($stock->id . Yii::$app->params['apiSecretKey']);
            $warehouseApiUrl = rtrim(Yii::$app->params['warehouseApiUrl'], '/');

            /** @var \GuzzleHttp\Client $client */
            $client = Yii::$app->httpClient;
            $response = $client->post(
                $warehouseApiUrl . "/api/sync-webhook/branches/{$warehouseBranchId}/set-stock-id",
                [
                    'json' => ['id' => $warehouseBranchId, 'yii_stock_id' => $stock->id],
                    'headers' => [
                        'X-Api-Token' => $token,
                        'Content-Type' => 'application/json',
                    ],
                ]
            );

            $status = $response->getStatusCode();
            $body = (string) $response->getBody();

            if ($status >= 400) {
                Yii::warning("Warehouse sync failed: HTTP {$status} Body: {$body}", __METHOD__);
            }

        } catch (\Throwable $e) {
            $tx->rollBack();
            throw $e;
        }
    }
}
