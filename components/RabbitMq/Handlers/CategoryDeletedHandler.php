<?php

namespace app\components\RabbitMq\Handlers;

use app\models\Category;
use Yii;

class CategoryDeletedHandler
{
    public function handle(array $message): void
    {
        $payload = $message['payload'];
        $tx = Yii::$app->db->beginTransaction();

        try {
            $category = null;

            if (!empty($payload['yii_category_id'])) {
                $category = Category::findOne((int) $payload['yii_category_id']);
            }

            if (!$category && !empty($payload['id'])) {
                $category = Category::findOne((int) $payload['id']);
            }

            if (!$category) {
                throw new \RuntimeException('Category not found for deletion');
            }

            $category->suppressSyncEvents = true;
            $category->status = 0;
            $category->save(false);

            $tx->commit();
        } catch (\Throwable $e) {
            $tx->rollBack();
            throw $e;
        }
    }
}
