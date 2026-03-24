<?php

namespace app\components\RabbitMq\Handlers;

use app\models\Category;
use Yii;

class CategoryUpsertHandler
{
    public function handle(array $message): void
    {
        $payload = $message['payload'];
        $tx = Yii::$app->db->beginTransaction();

        try {
            $category = $this->findExistingCategory($payload) ?? new Category();
            $isNew = $category->isNewRecord;

            $category->suppressSyncEvents = true;
            $category->parent_id = $this->resolveParentId($payload['parent_id'] ?? 0);
            $category->type = $payload['type'] ?? $category->type ?? 'category';
            $category->name_mini = $payload['name_mini'] ?? null;
            $category->name_ru = $payload['name_ru'] ?? null;
            $category->name_uz = $payload['name_uz'] ?? null;
            $category->name_en = $payload['name_en'] ?? null;
            $category->description_ru = $payload['description_ru'] ?? null;
            $category->description_uz = $payload['description_uz'] ?? null;
            $category->description_en = $payload['description_en'] ?? null;
            $category->option_ru = $payload['option_ru'] ?? null;
            $category->option_uz = $payload['option_uz'] ?? null;
            $category->option_en = $payload['option_en'] ?? null;
            $category->sort = $payload['sort'] ?? 0;
            $category->status = $payload['status'] ?? Category::STATUS_ACTIVE;
            $category->main = $payload['main'] ?? 0;
            $category->is_filter = $payload['is_filter'] ?? 0;
            $category->popular = $payload['popular'] ?? 0;
            $category->save(false);
            $category->suppressSyncEvents = false;

            $tx->commit();

            if ($isNew || empty($payload['yii_category_id'])) {
                $this->notifyWarehouse($message['entity_id'], $category->id);
            }
        } catch (\Throwable $e) {
            $tx->rollBack();
            throw $e;
        }
    }

    protected function findExistingCategory(array $payload): ?Category
    {
        if (!empty($payload['yii_category_id'])) {
            $model = Category::findOne((int) $payload['yii_category_id']);
            if ($model) {
                return $model;
            }
        }

        if (!empty($payload['id'])) {
            return Category::findOne((int) $payload['id']);
        }

        return null;
    }

    protected function resolveParentId($parentId): int
    {
        if (empty($parentId)) {
            return 0;
        }

        return (int) (Category::find()->select('id')->where(['id' => (int) $parentId])->scalar() ?: 0);
    }

    protected function notifyWarehouse(int $warehouseCategoryId, int $yiiCategoryId): void
    {
        $token = md5($warehouseCategoryId . Yii::$app->params['apiSecretKey']);
        $warehouseApiUrl = rtrim(Yii::$app->params['warehouseApiUrl'], '/');

        Yii::$app->httpClient->post(
            $warehouseApiUrl . "/sync-webhook/categories/{$warehouseCategoryId}/set-category-id",
            [
                'json' => ['id' => $warehouseCategoryId, 'yii_category_id' => $yiiCategoryId],
                'headers' => [
                    'X-Api-Token' => $token,
                    'Content-Type' => 'application/json',
                ],
            ]
        );
    }
}
