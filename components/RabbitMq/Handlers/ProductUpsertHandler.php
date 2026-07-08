<?php

namespace app\components\RabbitMq\Handlers;

use app\models\Images;
use app\models\brand\CategoryBrand;
use app\models\color\Color;
use app\models\filter\Filter;
use app\models\product\Product;
use app\models\product\ProductColor;
use app\models\product\ProductFilter;
use app\models\product\ProductProductType;
use app\models\product\ProductTypeValue;
use app\models\stock\Stock;
use app\models\Category;
use Yii;

class ProductUpsertHandler
{
    public function handle(array $message): void
    {
        $payload = $message['payload'];

        $tx = Yii::$app->db->beginTransaction();

        try {
            $product = $this->findExistingProduct($payload) ?? new Product();
            $isNew = $product->isNewRecord;
            $product->suppressSyncEvents = true;

            $product->shop_id = $payload['shop_id'] ?? $product->shop_id;
            $product->user_id = $payload['user_id'] ?? $product->user_id;
            $product->stock_id = $this->resolveStockId($payload['stock_id'] ?? null);
            $product->sklad_product_id = $payload['sklad_product_id'] ?? $product->sklad_product_id;
            $product->token_key = $payload['token_key'] ?? $product->token_key;
            $product->status = $payload['status'] ?? 2;
            $product->sync_status = 1;

            $product->name_ru = $payload['name_ru'] ?? null;
            $product->name_en = $payload['name_en'] ?? null;
            $product->name_uz = $payload['name_uz'] ?? null;
            $product->description_ru = $payload['description_ru'] ?? null;
            $product->description_en = $payload['description_en'] ?? null;
            $product->description_uz = $payload['description_uz'] ?? null;
            $product->price = $payload['price'] ?? 0;
            $product->price_small = $payload['price_small'] ?? null;
            $product->price_opt = $payload['price_opt'] ?? null;
            $product->min_order = $payload['min_order'] ?? null;
            $product->qty_small_wholesale = $payload['qty_small_wholesale'] ?? null;
            $product->qty_big_wholesale = $payload['qty_big_wholesale'] ?? null;
            $product->amount = max(0, (float) ($payload['amount'] ?? 0));
            $product->discount = $payload['discount'] ?? null;
            $product->sku = $payload['sku'] ?? null;
            $product->barcode = $payload['barcode'] ?? null;
            $product->ikpu_code = $payload['ikpu_code'] ?? null;
            $product->category_id = $this->resolveCategoryId($payload['category_id'] ?? null);
            $product->brand_id = $this->resolveBrandId($payload['brand_id'] ?? null);
            $product->color_id = $this->resolveColorId($payload['color_id'] ?? null);

            if (!$product->save(false)) {
                throw new \RuntimeException('Failed to save synced product');
            }

            $this->syncColors($product, $payload['colors'] ?? []);
            $this->syncFilters($product, $payload['filters'] ?? []);
            $this->syncProductTypes($product, $payload['product_types'] ?? []);
            $this->syncImages($product, $payload['images'] ?? []);

            $tx->commit();

            if (!empty($payload['sklad_product_id']) && ($isNew || empty($payload['yii_product_id']))) {
                $this->notifyWarehouse((int) $payload['sklad_product_id'], (int) $product->id);
            }
        } catch (\Throwable $e) {
            $tx->rollBack();
            throw $e;
        } finally {
            $product->suppressSyncEvents = false;
        }
    }

    protected function findExistingProduct(array $payload): ?Product
    {
        if (!empty($payload['yii_product_id'])) {
            $product = Product::findOne((int) $payload['yii_product_id']);
            if ($product) {
                return $product;
            }
        }

        if (!empty($payload['sklad_product_id']) && !empty($payload['shop_id'])) {
            $product = Product::findOne([
                'sklad_product_id' => (int) $payload['sklad_product_id'],
                'shop_id' => (int) $payload['shop_id'],
            ]);
            if ($product) {
                return $product;
            }
        }

        if (!empty($payload['token_key']) && !empty($payload['shop_id'])) {
            $query = Product::find()
                ->where([
                    'token_key' => $payload['token_key'],
                    'shop_id' => (int) $payload['shop_id'],
                ]);

            if (!empty($payload['color_id'])) {
                $query->andWhere(['color_id' => $this->resolveColorId($payload['color_id'])]);
            }

            $matches = $query->limit(2)->all();
            return count($matches) === 1 ? $matches[0] : null;
        }

        return null;
    }

    protected function notifyWarehouse(int $warehouseProductId, int $yiiProductId): void
    {
        $token = md5($warehouseProductId . Yii::$app->params['apiSecretKey']);
        $warehouseApiUrl = rtrim(Yii::$app->params['warehouseApiUrl'], '/');

        Yii::$app->httpClient->post(
            $warehouseApiUrl . "/api/sync-webhook/products/{$warehouseProductId}/set-product-id",
            [
                'json' => ['id' => $warehouseProductId, 'yii_product_id' => $yiiProductId],
                'headers' => [
                    'X-Api-Token' => $token,
                    'Content-Type' => 'application/json',
                ],
            ]
        );
    }

    protected function syncColors(Product $product, array $colors): void
    {
        ProductColor::deleteAll(['product_id' => $product->id]);

        foreach ($colors as $colorId) {
            $resolvedColorId = $this->resolveColorId($colorId);
            if (!$resolvedColorId) {
                continue;
            }

            $productColor = new ProductColor();
            $productColor->product_id = $product->id;
            $productColor->color_id = $resolvedColorId;
            $productColor->save(false);
        }
    }

    protected function syncFilters(Product $product, array $filters): void
    {
        ProductFilter::deleteAll(['product_id' => $product->id]);

        foreach ($filters as $filterId => $value) {
            $resolvedFilterId = $this->resolveFilterId($filterId);
            if (!$resolvedFilterId) {
                continue;
            }

            $productFilter = new ProductFilter();
            $productFilter->product_id = $product->id;
            $productFilter->filter_id = $resolvedFilterId;

            if (is_numeric($value)) {
                $productFilter->value_id = (int) $value;
            } else {
                $productFilter->value_ru = (string) $value;
            }

            $productFilter->save(false);
        }
    }

    protected function syncProductTypes(Product $product, array $productTypes): void
    {
        ProductProductType::deleteAll(['product_id' => $product->id]);

        foreach ($productTypes as $typeId => $values) {
            if (!is_array($values)) {
                continue;
            }

            foreach ($values as $value) {
                if (!is_numeric($value)) {
                    Yii::warning('Skipping non-numeric product type value from sync', json_encode([
                        'type_id' => $typeId,
                        'value' => $value,
                        'product_id' => $product->id,
                    ], JSON_UNESCAPED_UNICODE));
                    continue;
                }

                $typeValue = ProductTypeValue::findOne((int) $value);
                if (!$typeValue) {
                    Yii::warning('Product type value not found during sync', json_encode([
                        'value_id' => $value,
                        'product_id' => $product->id,
                    ], JSON_UNESCAPED_UNICODE));
                    continue;
                }

                $productType = new ProductProductType();
                $productType->product_id = $product->id;
                $productType->product_type_id = $typeValue->product_type_id;
                $productType->product_type_value_id = $typeValue->id;
                $productType->save(false);
            }
        }
    }

    protected function syncImages(Product $product, array $images): void
    {
        $existingImages = Images::find()
            ->where(['object_id' => $product->id, 'type' => 'product'])
            ->all();

        foreach ($existingImages as $existingImage) {
            $existingImage->removeImageSize();
        }

        Images::deleteAll(['object_id' => $product->id, 'type' => 'product']);

        foreach ($images as $index => $image) {
            if (empty($image['photo'])) {
                continue;
            }

            $item = new Images();
            $item->object_id = $product->id;
            $item->type = 'product';
            $item->photo = $image['photo'];
            $item->main = !empty($image['main']) ? 1 : 2;
            $item->sort = $index;
            $item->status = 1;
            $item->web = 1;
            $item->token_key = $image['token_key'] ?? $product->token_key;
            $item->save(false);
        }
    }

    protected function resolveStockId($stockId): ?int
    {
        if (!$stockId) {
            return null;
        }

        // Incoming product payload from sklad must already contain shop.stock.id.
        $resolvedId = Stock::find()
            ->select('id')
            ->where(['id' => (int) $stockId])
            ->scalar();

        if (!$resolvedId) {
            Yii::warning(
                sprintf('Stock not resolved for incoming product payload stock_id=%s', (string) $stockId),
                __METHOD__
            );
        }

        return $resolvedId ? (int) $resolvedId : null;
    }

    protected function resolveCategoryId($categoryId): ?int
    {
        if (!$categoryId) {
            return null;
        }

        return Category::find()->select('id')->where(['id' => (int) $categoryId])->scalar() ?: null;
    }

    protected function resolveBrandId($brandId): ?int
    {
        if (!$brandId) {
            return null;
        }

        return CategoryBrand::find()->select('id')->where(['id' => (int) $brandId])->scalar() ?: null;
    }

    protected function resolveColorId($colorId): ?int
    {
        if (!$colorId) {
            return null;
        }

        return Color::find()->select('id')->where(['id' => (int) $colorId])->scalar() ?: null;
    }

    protected function resolveFilterId($filterId): ?int
    {
        if (!$filterId) {
            return null;
        }

        return Filter::find()->select('id')->where(['id' => (int) $filterId])->scalar() ?: null;
    }
}
