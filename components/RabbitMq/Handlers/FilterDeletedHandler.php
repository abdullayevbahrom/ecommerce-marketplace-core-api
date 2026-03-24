<?php

namespace app\components\RabbitMq\Handlers;

use app\models\filter\Filter;
use Yii;

class FilterDeletedHandler
{
    public function handle(array $message): void
    {
        $payload = $message['payload'];
        $tx = Yii::$app->db->beginTransaction();

        try {
            $filter = null;

            if (!empty($payload['yii_filter_id'])) {
                $filter = Filter::findOne((int) $payload['yii_filter_id']);
            }

            if (!$filter && !empty($payload['id'])) {
                $filter = Filter::findOne((int) $payload['id']);
            }

            if (!$filter) {
                throw new \RuntimeException('Filter not found for deletion');
            }

            $filter->suppressSyncEvents = true;
            $filter->status = 0;
            $filter->deleted_at = date('Y-m-d H:i:s');
            $filter->save(false);
            Filter::updateAll(
                ['status' => 0, 'deleted_at' => date('Y-m-d H:i:s')],
                ['parent_id' => $filter->id]
            );

            $tx->commit();
        } catch (\Throwable $e) {
            $tx->rollBack();
            throw $e;
        }
    }
}
