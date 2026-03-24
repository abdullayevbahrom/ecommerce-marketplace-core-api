<?php

namespace app\components\RabbitMq\Handlers;

use app\models\Category;
use Yii;

class TagUpsertHandler
{
    public function handle(array $message): void
    {
        $payload = $message['payload'];
        $tx = Yii::$app->db->beginTransaction();

        try {
            $tag = $this->findExistingTag($payload) ?? new Category();
            $isNew = $tag->isNewRecord;

            $tag->suppressSyncEvents = true;
            $tag->type = 'tag';
            $tag->parent_id = 0;
            $tag->name_ru = $payload['name_ru'] ?? $tag->name_ru;
            $tag->name_uz = $payload['name_uz'] ?? $tag->name_uz;
            $tag->name_en = $payload['name_en'] ?? $tag->name_en;
            $tag->status = $payload['status'] ?? $tag->status ?? Category::STATUS_ACTIVE;
            $tag->sort = $payload['sort'] ?? $tag->sort ?? 0;
            $tag->save(false);
            $tag->suppressSyncEvents = false;

            $tx->commit();

            if ($isNew || empty($payload['yii_tag_id'])) {
                $this->notifyWarehouse($message['entity_id'], $tag->id);
            }
        } catch (\Throwable $e) {
            $tx->rollBack();
            throw $e;
        }
    }

    protected function findExistingTag(array $payload): ?Category
    {
        if (!empty($payload['yii_tag_id'])) {
            $model = Category::findOne((int) $payload['yii_tag_id']);
            if ($model && $model->type === 'tag') {
                return $model;
            }
        }

        if (!empty($payload['id'])) {
            $model = Category::findOne((int) $payload['id']);
            if ($model && $model->type === 'tag') {
                return $model;
            }
        }

        if (!empty($payload['name_ru'])) {
            return Category::find()->where(['type' => 'tag', 'name_ru' => $payload['name_ru']])->one();
        }

        return null;
    }

    protected function notifyWarehouse(int $warehouseTagId, int $yiiTagId): void
    {
        $token = md5($warehouseTagId . Yii::$app->params['apiSecretKey']);
        $warehouseApiUrl = rtrim(Yii::$app->params['warehouseApiUrl'], '/');

        Yii::$app->httpClient->post(
            $warehouseApiUrl . "/api/sync-webhook/tags/{$warehouseTagId}/set-tag-id",
            [
                'json' => ['id' => $warehouseTagId, 'yii_tag_id' => $yiiTagId],
                'headers' => [
                    'X-Api-Token' => $token,
                    'Content-Type' => 'application/json',
                ],
            ]
        );
    }
}
