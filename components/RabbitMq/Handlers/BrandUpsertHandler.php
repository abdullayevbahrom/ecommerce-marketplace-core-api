<?php

namespace app\components\RabbitMq\Handlers;

use app\models\brand\CategoryBrand;
use app\models\Category;
use Yii;

class BrandUpsertHandler
{
    public function handle(array $message): void
    {
        $payload = $message['payload'];
        $tx = Yii::$app->db->beginTransaction();

        try {
            $brand = $this->findExistingBrand($payload) ?? new CategoryBrand();
            $isNew = $brand->isNewRecord;

            $brand->suppressSyncEvents = true;
            $brand->category_id = $this->resolveCategoryId($payload['category_id'] ?? null);
            $brand->category_tree = $payload['category_tree'] ?? $brand->category_tree;
            $brand->name_ru = $payload['name_ru'] ?? null;
            $brand->name_en = $payload['name_en'] ?? null;
            $brand->name_uz = $payload['name_uz'] ?? null;
            $brand->description_ru = $payload['description_ru'] ?? null;
            $brand->description_en = $payload['description_en'] ?? null;
            $brand->description_uz = $payload['description_uz'] ?? null;
            $brand->status = $payload['status'] ?? $brand->status ?? CategoryBrand::STATUS_ACTIVE;
            $brand->sort = $payload['sort'] ?? 0;
            $brand->save(false);
            $brand->suppressSyncEvents = false;

            $tx->commit();

            if ($isNew || empty($payload['yii_brand_id'])) {
                $this->notifyWarehouse($message['entity_id'], $brand->id);
            }
        } catch (\Throwable $e) {
            $tx->rollBack();
            throw $e;
        }
    }

    protected function findExistingBrand(array $payload): ?CategoryBrand
    {
        if (!empty($payload['yii_brand_id'])) {
            $model = CategoryBrand::findOne((int) $payload['yii_brand_id']);
            if ($model) {
                return $model;
            }
        }

        if (!empty($payload['id'])) {
            return CategoryBrand::findOne((int) $payload['id']);
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

    protected function notifyWarehouse(int $warehouseBrandId, int $yiiBrandId): void
    {
        $token = md5($warehouseBrandId . Yii::$app->params['apiSecretKey']);
        $warehouseApiUrl = rtrim(Yii::$app->params['warehouseApiUrl'], '/');

        Yii::$app->httpClient->post(
            $warehouseApiUrl . "/sync-webhook/brands/{$warehouseBrandId}/set-brand-id",
            [
                'json' => ['id' => $warehouseBrandId, 'yii_brand_id' => $yiiBrandId],
                'headers' => [
                    'X-Api-Token' => $token,
                    'Content-Type' => 'application/json',
                ],
            ]
        );
    }
}
