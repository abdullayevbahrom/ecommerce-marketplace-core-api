<?php

namespace app\components\RabbitMq\Handlers;

use app\models\brand\CategoryBrand;
use Yii;

class BrandDeletedHandler
{
    public function handle(array $message): void
    {
        $payload = $message['payload'];
        $tx = Yii::$app->db->beginTransaction();

        try {
            $brand = null;

            if (!empty($payload['yii_brand_id'])) {
                $brand = CategoryBrand::findOne((int) $payload['yii_brand_id']);
            }

            if (!$brand && !empty($payload['id'])) {
                $brand = CategoryBrand::findOne((int) $payload['id']);
            }

            if (!$brand) {
                throw new \RuntimeException('Brand not found for deletion');
            }

            $brand->suppressSyncEvents = true;
            $brand->status = 3;
            $brand->deleted_at = date('Y-m-d H:i:s');
            $brand->save(false);

            $tx->commit();
        } catch (\Throwable $e) {
            $tx->rollBack();
            throw $e;
        }
    }
}
