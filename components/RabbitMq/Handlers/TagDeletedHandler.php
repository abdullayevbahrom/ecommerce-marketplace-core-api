<?php

namespace app\components\RabbitMq\Handlers;

use app\models\Category;
use Yii;

class TagDeletedHandler
{
    public function handle(array $message): void
    {
        $payload = $message['payload'];
        $tx = Yii::$app->db->beginTransaction();

        try {
            $tag = null;

            if (!empty($payload['yii_tag_id'])) {
                $tag = Category::findOne((int) $payload['yii_tag_id']);
            }

            if (!$tag && !empty($payload['id'])) {
                $tag = Category::findOne((int) $payload['id']);
            }

            if (!$tag || $tag->type !== 'tag') {
                return;
            }

            $tag->suppressSyncEvents = true;
            $tag->status = 0;
            $tag->save(false);

            $tx->commit();
        } catch (\Throwable $e) {
            $tx->rollBack();
            throw $e;
        }
    }
}
