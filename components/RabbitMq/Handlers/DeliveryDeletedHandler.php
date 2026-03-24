<?php

namespace app\components\RabbitMq\Handlers;

use app\models\delivery\Delivery;
use Yii;

class DeliveryDeletedHandler
{
    public function handle(array $message): void
    {
        $payload = $message['payload'];
        $tx = Yii::$app->db->beginTransaction();

        try {
            $delivery = null;

            if (!empty($payload['yii_delivery_id'])) {
                $delivery = Delivery::findOne((int) $payload['yii_delivery_id']);
            }

            if (!$delivery && !empty($payload['id'])) {
                $delivery = Delivery::findOne((int) $payload['id']);
            }

            if (!$delivery) {
                return;
            }

            $delivery->suppressSyncEvents = true;
            $delivery->delete();

            $tx->commit();
        } catch (\Throwable $e) {
            $tx->rollBack();
            throw $e;
        }
    }
}
