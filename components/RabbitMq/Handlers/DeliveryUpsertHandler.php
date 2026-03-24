<?php

namespace app\components\RabbitMq\Handlers;

use app\models\delivery\Delivery;
use Yii;

class DeliveryUpsertHandler
{
    public function handle(array $message): void
    {
        $payload = $message['payload'];
        $tx = Yii::$app->db->beginTransaction();

        try {
            $delivery = $this->findExistingDelivery($payload) ?? new Delivery();
            $isNew = $delivery->isNewRecord;

            $delivery->suppressSyncEvents = true;
            $delivery->name_ru = $payload['name_ru'] ?? $delivery->name_ru;
            $delivery->name_uz = $payload['name_uz'] ?? $delivery->name_uz;
            $delivery->name_en = $payload['name_en'] ?? $delivery->name_en;
            $delivery->price = $payload['price'] ?? $delivery->price;
            $delivery->status = (int) ($payload['status'] ?? $delivery->status ?? 1);
            $delivery->sort = $delivery->sort ?? 0;
            $delivery->date = $delivery->date ?: date('Y-m-d H:i:s');
            $delivery->save(false);
            $delivery->suppressSyncEvents = false;

            $tx->commit();

            if ($isNew || empty($payload['yii_delivery_id'])) {
                $this->notifyWarehouse($message['entity_id'], $delivery->id);
            }
        } catch (\Throwable $e) {
            $tx->rollBack();
            throw $e;
        }
    }

    protected function findExistingDelivery(array $payload): ?Delivery
    {
        if (!empty($payload['yii_delivery_id'])) {
            $model = Delivery::findOne((int) $payload['yii_delivery_id']);
            if ($model) {
                return $model;
            }
        }

        if (!empty($payload['id'])) {
            return Delivery::findOne((int) $payload['id']);
        }

        if (!empty($payload['name_ru'])) {
            return Delivery::findOne(['name_ru' => $payload['name_ru']]);
        }

        return null;
    }

    protected function notifyWarehouse(int $warehouseDeliveryId, int $yiiDeliveryId): void
    {
        $token = md5($warehouseDeliveryId . Yii::$app->params['apiSecretKey']);
        $warehouseApiUrl = rtrim(Yii::$app->params['warehouseApiUrl'], '/');

        Yii::$app->httpClient->post(
            $warehouseApiUrl . "/api/sync-webhook/deliveries/{$warehouseDeliveryId}/set-delivery-id",
            [
                'json' => ['id' => $warehouseDeliveryId, 'yii_delivery_id' => $yiiDeliveryId],
                'headers' => [
                    'X-Api-Token' => $token,
                    'Content-Type' => 'application/json',
                ],
            ]
        );
    }
}
