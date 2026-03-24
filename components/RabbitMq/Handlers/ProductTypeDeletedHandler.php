<?php

namespace app\components\RabbitMq\Handlers;

use app\models\product\ProductType;
use Yii;

class ProductTypeDeletedHandler
{
    public function handle(array $message): void
    {
        $payload = $message['payload'];
        $tx = Yii::$app->db->beginTransaction();

        try {
            $productType = null;

            if (!empty($payload['yii_product_type_id'])) {
                $productType = ProductType::findOne((int) $payload['yii_product_type_id']);
            }

            if (!$productType && !empty($payload['id'])) {
                $productType = ProductType::findOne((int) $payload['id']);
            }

            if (!$productType) {
                throw new \RuntimeException('Product type not found for deletion');
            }

            $productType->suppressSyncEvents = true;
            $productType->status = 0;
            $productType->save(false);

            $tx->commit();
        } catch (\Throwable $e) {
            $tx->rollBack();
            throw $e;
        }
    }
}
