<?php

namespace app\components\RabbitMq\Handlers;

use app\models\product\Product;

class ProductDeletedHandler
{
    public function handle(array $message): void
    {
        $payload = $message['payload'] ?? [];

        $product = null;

        if (!empty($payload['yii_product_id'])) {
            $product = Product::findOne((int) $payload['yii_product_id']);
        }

        if (!$product && !empty($payload['sklad_product_id']) && !empty($payload['shop_id'])) {
            $product = Product::findOne([
                'sklad_product_id' => (int) $payload['sklad_product_id'],
                'shop_id' => (int) $payload['shop_id'],
            ]);
        }

        if (!$product && !empty($payload['token_key']) && !empty($payload['shop_id'])) {
            $product = Product::find()
                ->where([
                    'token_key' => $payload['token_key'],
                    'shop_id' => (int) $payload['shop_id'],
                ])
                ->one();
        }

        if ($product) {
            $product->suppressSyncEvents = true;
            $product->softDelete();
            $product->suppressSyncEvents = false;
        }
    }
}
