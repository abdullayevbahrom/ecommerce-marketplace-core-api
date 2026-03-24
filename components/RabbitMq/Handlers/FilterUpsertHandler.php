<?php

namespace app\components\RabbitMq\Handlers;

use app\models\Category;
use app\models\filter\Filter;
use Yii;

class FilterUpsertHandler
{
    public function handle(array $message): void
    {
        $payload = $message['payload'];
        $tx = Yii::$app->db->beginTransaction();

        try {
            $filter = $this->findExistingFilter($payload) ?? new Filter();
            $isNew = $filter->isNewRecord;

            $filter->suppressSyncEvents = true;
            $filter->parent_id = 0;
            $filter->category_id = $this->resolveCategoryId($payload['category_id'] ?? null);
            $filter->type = $payload['type'] ?? null;
            $filter->name_ru = $payload['name_ru'] ?? null;
            $filter->name_uz = $payload['name_uz'] ?? null;
            $filter->name_en = $payload['name_en'] ?? null;
            $filter->status = $payload['status'] ?? 1;
            $filter->save(false);

            Filter::deleteAll(['parent_id' => $filter->id]);

            foreach ((array) ($payload['values'] ?? []) as $value) {
                if (empty($value['name_ru'])) {
                    continue;
                }

                $child = new Filter();
                $child->suppressSyncEvents = true;
                $child->parent_id = $filter->id;
                $child->category_id = $filter->category_id;
                $child->type = $filter->type;
                $child->status = 1;
                $child->name_ru = $value['name_ru'];
                $child->name_uz = $value['name_uz'] ?? null;
                $child->name_en = $value['name_en'] ?? null;
                $child->save(false);
            }

            $filter->suppressSyncEvents = false;
            $tx->commit();

            if ($isNew || empty($payload['yii_filter_id'])) {
                $this->notifyWarehouse($message['entity_id'], $filter->id);
            }
        } catch (\Throwable $e) {
            $tx->rollBack();
            throw $e;
        }
    }

    protected function findExistingFilter(array $payload): ?Filter
    {
        if (!empty($payload['yii_filter_id'])) {
            $model = Filter::findOne((int) $payload['yii_filter_id']);
            if ($model) {
                return $model;
            }
        }

        if (!empty($payload['id'])) {
            return Filter::findOne((int) $payload['id']);
        }

        return null;
    }

    protected function resolveCategoryId($categoryId): ?int
    {
        if (!$categoryId) {
            return null;
        }

        return Category::find()->select('id')->where(['id' => (int) $categoryId])->scalar() ?: null;
    }

    protected function notifyWarehouse(int $warehouseFilterId, int $yiiFilterId): void
    {
        $token = md5($warehouseFilterId . Yii::$app->params['apiSecretKey']);
        $warehouseApiUrl = rtrim(Yii::$app->params['warehouseApiUrl'], '/');

        Yii::$app->httpClient->post(
            $warehouseApiUrl . "/api/sync-webhook/filters/{$warehouseFilterId}/set-filter-id",
            [
                'json' => ['id' => $warehouseFilterId, 'yii_filter_id' => $yiiFilterId],
                'headers' => [
                    'X-Api-Token' => $token,
                    'Content-Type' => 'application/json',
                ],
            ]
        );
    }
}
