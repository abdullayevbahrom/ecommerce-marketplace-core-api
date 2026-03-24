<?php

namespace app\components\RabbitMq\Handlers;

use app\models\color\Color;
use Yii;

class ColorUpsertHandler
{
    public function handle(array $message): void
    {
        $payload = $message['payload'];
        $tx = Yii::$app->db->beginTransaction();

        try {
            $color = $this->findExistingColor($payload) ?? new Color();
            $isNew = $color->isNewRecord;

            $color->suppressSyncEvents = true;
            $color->name_ru = $payload['name_ru'] ?? null;
            $color->name_en = $payload['name_en'] ?? null;
            $color->name_uz = $payload['name_uz'] ?? null;
            $color->color = $payload['color'] ?? null;
            $color->save(false);
            $color->suppressSyncEvents = false;

            $tx->commit();

            if ($isNew || empty($payload['yii_color_id'])) {
                $this->notifyWarehouse($message['entity_id'], $color->id);
            }
        } catch (\Throwable $e) {
            $tx->rollBack();
            throw $e;
        }
    }

    protected function findExistingColor(array $payload): ?Color
    {
        if (!empty($payload['yii_color_id'])) {
            $model = Color::findOne((int) $payload['yii_color_id']);
            if ($model) {
                return $model;
            }
        }

        if (!empty($payload['id'])) {
            return Color::findOne((int) $payload['id']);
        }

        return null;
    }

    protected function notifyWarehouse(int $warehouseColorId, int $yiiColorId): void
    {
        $token = md5($warehouseColorId . Yii::$app->params['apiSecretKey']);
        $warehouseApiUrl = rtrim(Yii::$app->params['warehouseApiUrl'], '/');

        Yii::$app->httpClient->post(
            $warehouseApiUrl . "/api/sync-webhook/colors/{$warehouseColorId}/set-color-id",
            [
                'json' => ['id' => $warehouseColorId, 'yii_color_id' => $yiiColorId],
                'headers' => [
                    'X-Api-Token' => $token,
                    'Content-Type' => 'application/json',
                ],
            ]
        );
    }
}
