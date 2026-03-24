<?php

namespace app\components\RabbitMq\Handlers;

use app\models\office\Office;
use Yii;

class OfficeDeletedHandler
{
    public function handle(array $message): void
    {
        $payload = $message['payload'];
        $tx = Yii::$app->db->beginTransaction();

        try {
            $office = null;

            if (!empty($payload['yii_office_id'])) {
                $office = Office::findOne((int) $payload['yii_office_id']);
            }

            if (!$office && !empty($payload['id'])) {
                $office = Office::findOne((int) $payload['id']);
            }

            if (!$office) {
                return;
            }

            $office->suppressSyncEvents = true;
            $office->delete();

            $tx->commit();
        } catch (\Throwable $e) {
            $tx->rollBack();
            throw $e;
        }
    }
}
