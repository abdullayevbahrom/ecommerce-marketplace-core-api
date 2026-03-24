<?php

namespace app\components\RabbitMq\Handlers;

use app\models\Region;
use Yii;

class RegionUpsertHandler
{
    public function handle(array $message): void
    {
        $payload = $message['payload'];
        $tx = Yii::$app->db->beginTransaction();

        try {
            $region = $this->findExistingRegion($payload) ?? new Region();
            $isNew = $region->isNewRecord;

            $region->suppressSyncEvents = true;
            $region->bts_id = $payload['bts_id'] ?? $payload['yii_region_id'] ?? $region->bts_id ?? 0;
            $region->name_ru = $payload['name_ru'] ?? $payload['name'] ?? $region->name_ru;
            $region->name_uz = $payload['name_uz'] ?? $payload['name'] ?? $region->name_uz ?? $region->name_ru;
            $region->name_en = $payload['name_en'] ?? $payload['name'] ?? $region->name_en ?? $region->name_ru;
            $region->status = (int) ($payload['status'] ?? $region->status ?? 1);
            $region->save(false);
            $region->suppressSyncEvents = false;

            $tx->commit();

            if ($isNew || empty($payload['yii_region_id'])) {
                $this->notifyWarehouse($message['entity_id'], $region->id);
            }
        } catch (\Throwable $e) {
            $tx->rollBack();
            throw $e;
        }
    }

    protected function findExistingRegion(array $payload): ?Region
    {
        if (!empty($payload['yii_region_id'])) {
            $model = Region::findOne((int) $payload['yii_region_id']);
            if ($model) {
                return $model;
            }
        }

        if (!empty($payload['bts_id'])) {
            $model = Region::findOne(['bts_id' => (int) $payload['bts_id']]);
            if ($model) {
                return $model;
            }
        }

        if (!empty($payload['name'])) {
            return Region::findOne(['name_ru' => $payload['name']]);
        }

        return null;
    }

    protected function notifyWarehouse(int $warehouseRegionId, int $yiiRegionId): void
    {
        $token = md5($warehouseRegionId . Yii::$app->params['apiSecretKey']);
        $warehouseApiUrl = rtrim(Yii::$app->params['warehouseApiUrl'], '/');

        Yii::$app->httpClient->post(
            $warehouseApiUrl . "/api/sync-webhook/regions/{$warehouseRegionId}/set-region-id",
            [
                'json' => ['id' => $warehouseRegionId, 'yii_region_id' => $yiiRegionId],
                'headers' => [
                    'X-Api-Token' => $token,
                    'Content-Type' => 'application/json',
                ],
            ]
        );
    }
}
