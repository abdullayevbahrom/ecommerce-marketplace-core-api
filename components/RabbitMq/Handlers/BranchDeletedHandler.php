<?php

namespace app\components\RabbitMq\Handlers;

use app\models\Stock\Stock;
use Yii;

class BranchDeletedHandler
{
    public function handle(array $message): void
    {
        $payload = $message['payload'];

        $tx = Yii::$app->db->beginTransaction();

        try {
            $stock = Stock::findOne(['id' => $payload['id']]);

            if (!$stock) {
                throw new \RuntimeException('Stock not found '. $payload['id']);
            }
            
            $stock->suppressSyncEvents = true;
            
            $stock->removeObject();

            $tx->commit();
        } catch (\Throwable $e) {
            $tx->rollBack();
            throw $e;
        }
    }
}