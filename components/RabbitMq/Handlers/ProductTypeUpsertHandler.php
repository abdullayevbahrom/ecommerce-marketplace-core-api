<?php

namespace app\components\RabbitMq\Handlers;

use app\models\Category;
use app\models\product\ProductType;
use app\models\product\ProductTypeValue;
use Yii;

class ProductTypeUpsertHandler
{
    public function handle(array $message): void
    {
        $payload = $message['payload'];
        $tx = Yii::$app->db->beginTransaction();

        try {
            $productType = $this->findExistingProductType($payload) ?? new ProductType();
            $isNew = $productType->isNewRecord;

            $productType->suppressSyncEvents = true;
            $productType->category_id = $this->resolveCategoryId($payload['category_id'] ?? null);
            $productType->name_ru = $payload['name_ru'] ?? null;
            $productType->name_en = $payload['name_en'] ?? null;
            $productType->name_uz = $payload['name_uz'] ?? null;
            $productType->type = $payload['type'] ?? null;
            $productType->description_ru = $payload['description_ru'] ?? null;
            $productType->description_en = $payload['description_en'] ?? null;
            $productType->description_uz = $payload['description_uz'] ?? null;
            $productType->status = $payload['status'] ?? 1;
            $productType->sort = $payload['sort'] ?? 0;
            $productType->save(false);

            ProductTypeValue::deleteAll(['product_type_id' => $productType->id]);

            $valueMappings = [];
            foreach ((array) ($payload['values'] ?? []) as $value) {
                if (empty($value['value_ru'])) {
                    continue;
                }

                $item = new ProductTypeValue();
                $item->product_type_id = $productType->id;
                $item->value_ru = $value['value_ru'];
                $item->value_en = $value['value_en'] ?? null;
                $item->value_uz = $value['value_uz'] ?? null;
                $item->display_value = $value['display_value'] ?? null;
                $item->description_ru = $value['description_ru'] ?? null;
                $item->description_en = $value['description_en'] ?? null;
                $item->description_uz = $value['description_uz'] ?? null;
                $item->status = $value['status'] ?? 1;
                $item->sort = $value['sort'] ?? 0;
                $item->save(false);

                $valueMappings[] = [
                    'sklad_product_type_value_id' => $value['sklad_product_type_value_id'] ?? null,
                    'yii_product_type_value_id' => $item->id,
                ];
            }

            $productType->suppressSyncEvents = false;
            $tx->commit();

            if ($isNew || empty($payload['yii_product_type_id'])) {
                $this->notifyWarehouse($message['entity_id'], $productType->id, $valueMappings);
            }
        } catch (\Throwable $e) {
            $tx->rollBack();
            throw $e;
        }
    }

    protected function findExistingProductType(array $payload): ?ProductType
    {
        if (!empty($payload['yii_product_type_id'])) {
            $model = ProductType::findOne((int) $payload['yii_product_type_id']);
            if ($model) {
                return $model;
            }
        }

        if (!empty($payload['id'])) {
            return ProductType::findOne((int) $payload['id']);
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

    protected function notifyWarehouse(int $warehouseProductTypeId, int $yiiProductTypeId, array $valueMappings): void
    {
        $token = md5($warehouseProductTypeId . Yii::$app->params['apiSecretKey']);
        $warehouseApiUrl = rtrim(Yii::$app->params['warehouseApiUrl'], '/');

        Yii::$app->httpClient->post(
            $warehouseApiUrl . "/sync-webhook/product-types/{$warehouseProductTypeId}/set-product-type-id",
            [
                'json' => [
                    'id' => $warehouseProductTypeId,
                    'yii_product_type_id' => $yiiProductTypeId,
                    'values' => array_values(array_filter($valueMappings, fn ($item) => !empty($item['sklad_product_type_value_id']))),
                ],
                'headers' => [
                    'X-Api-Token' => $token,
                    'Content-Type' => 'application/json',
                ],
            ]
        );
    }
}
