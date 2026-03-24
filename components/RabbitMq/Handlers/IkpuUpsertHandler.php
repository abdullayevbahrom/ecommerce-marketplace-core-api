<?php

namespace app\components\RabbitMq\Handlers;

use app\models\Ikpu;
use Yii;

class IkpuUpsertHandler
{
    public function handle(array $message): void
    {
        $payload = $message['payload'];
        $tx = Yii::$app->db->beginTransaction();

        try {
            $ikpu = $this->findExistingIkpu($payload) ?? new Ikpu();
            $isNew = $ikpu->isNewRecord;

            $ikpu->suppressSyncEvents = true;
            $ikpu->code = $payload['code'] ?? $ikpu->code;
            $ikpu->name_ru = $payload['name_ru'] ?? $ikpu->name_ru;
            $ikpu->name_uz = $payload['name_uz'] ?? $ikpu->name_uz;
            $ikpu->name_en = $payload['name_en'] ?? $ikpu->name_en;
            $ikpu->parent_code = $this->resolveParentCode($payload['parent_code'] ?? null);
            $ikpu->status = $payload['status'] ?? $ikpu->status ?? Ikpu::STATUS_ACTIVE;
            $ikpu->save(false);
            $ikpu->suppressSyncEvents = false;

            $tx->commit();

            if ($isNew || empty($payload['yii_ikpu_id'])) {
                $this->notifyWarehouse($message['entity_id'], $ikpu->id);
            }
        } catch (\Throwable $e) {
            $tx->rollBack();
            throw $e;
        }
    }

    protected function findExistingIkpu(array $payload): ?Ikpu
    {
        if (!empty($payload['yii_ikpu_id'])) {
            $model = Ikpu::findOne((int) $payload['yii_ikpu_id']);
            if ($model) {
                return $model;
            }
        }

        if (!empty($payload['code'])) {
            $model = Ikpu::findOne(['code' => $payload['code']]);
            if ($model) {
                return $model;
            }
        }

        if (!empty($payload['id'])) {
            return Ikpu::findOne((int) $payload['id']);
        }

        return null;
    }

    protected function resolveParentCode(?string $parentCode): ?string
    {
        if (empty($parentCode)) {
            return null;
        }

        return Ikpu::find()->select('code')->where(['code' => $parentCode])->scalar() ?: null;
    }

    protected function notifyWarehouse(int $warehouseIkpuId, int $yiiIkpuId): void
    {
        $token = md5($warehouseIkpuId . Yii::$app->params['apiSecretKey']);
        $warehouseApiUrl = rtrim(Yii::$app->params['warehouseApiUrl'], '/');

        Yii::$app->httpClient->post(
            $warehouseApiUrl . "/api/sync-webhook/ikpu/{$warehouseIkpuId}/set-ikpu-id",
            [
                'json' => ['id' => $warehouseIkpuId, 'yii_ikpu_id' => $yiiIkpuId],
                'headers' => [
                    'X-Api-Token' => $token,
                    'Content-Type' => 'application/json',
                ],
            ]
        );
    }
}
