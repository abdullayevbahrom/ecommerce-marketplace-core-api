<?php

namespace app\components\RabbitMq\Handlers;

use app\models\office\Office;
use Yii;

class OfficeUpsertHandler
{
    public function handle(array $message): void
    {
        $payload = $message['payload'];
        $tx = Yii::$app->db->beginTransaction();

        try {
            $office = $this->findExistingOffice($payload) ?? new Office();
            $isNew = $office->isNewRecord;

            $office->suppressSyncEvents = true;
            $office->name = $payload['name'] ?? $office->name;
            $office->office_id = $payload['address'] ?? $office->office_id;
            $office->date = $office->date ?: date('Y-m-d H:i:s');
            $office->save(false);
            $office->suppressSyncEvents = false;

            $tx->commit();

            if ($isNew || empty($payload['yii_office_id'])) {
                $this->notifyWarehouse($message['entity_id'], $office->id);
            }
        } catch (\Throwable $e) {
            $tx->rollBack();
            throw $e;
        }
    }

    protected function findExistingOffice(array $payload): ?Office
    {
        if (!empty($payload['yii_office_id'])) {
            $model = Office::findOne((int) $payload['yii_office_id']);
            if ($model) {
                return $model;
            }
        }

        if (!empty($payload['id'])) {
            return Office::findOne((int) $payload['id']);
        }

        if (!empty($payload['name'])) {
            return Office::findOne(['name' => $payload['name']]);
        }

        return null;
    }

    protected function notifyWarehouse(int $warehouseOfficeId, int $yiiOfficeId): void
    {
        $token = md5($warehouseOfficeId . Yii::$app->params['apiSecretKey']);
        $warehouseApiUrl = rtrim(Yii::$app->params['warehouseApiUrl'], '/');

        Yii::$app->httpClient->post(
            $warehouseApiUrl . "/api/sync-webhook/offices/{$warehouseOfficeId}/set-office-id",
            [
                'json' => ['id' => $warehouseOfficeId, 'yii_office_id' => $yiiOfficeId],
                'headers' => [
                    'X-Api-Token' => $token,
                    'Content-Type' => 'application/json',
                ],
            ]
        );
    }
}
