<?php

namespace app\components\RabbitMq\Handlers;

use app\models\color\Color;
use Yii;

class ColorDeletedHandler
{
    public function handle(array $message): void
    {
        $payload = $message['payload'];
        $tx = Yii::$app->db->beginTransaction();

        try {
            $color = null;

            if (!empty($payload['yii_color_id'])) {
                $color = Color::findOne((int) $payload['yii_color_id']);
            }

            if (!$color && !empty($payload['id'])) {
                $color = Color::findOne((int) $payload['id']);
            }

            if (!$color) {
                throw new \RuntimeException('Color not found for deletion');
            }

            $color->suppressSyncEvents = true;
            $color->deleted_at = date('Y-m-d H:i:s');
            $color->status = 0;
            $color->save(false);

            $tx->commit();
        } catch (\Throwable $e) {
            $tx->rollBack();
            throw $e;
        }
    }
}
