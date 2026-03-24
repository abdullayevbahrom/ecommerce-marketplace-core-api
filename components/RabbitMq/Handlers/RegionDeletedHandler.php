<?php

namespace app\components\RabbitMq\Handlers;

use app\models\Region;
use Yii;

class RegionDeletedHandler
{
    public function handle(array $message): void
    {
        $payload = $message['payload'];
        $tx = Yii::$app->db->beginTransaction();

        try {
            $region = null;

            if (!empty($payload['yii_region_id'])) {
                $region = Region::findOne((int) $payload['yii_region_id']);
            }

            if (!$region && !empty($payload['bts_id'])) {
                $region = Region::findOne(['bts_id' => (int) $payload['bts_id']]);
            }

            if (!$region) {
                return;
            }

            $region->suppressSyncEvents = true;
            $region->delete();

            $tx->commit();
        } catch (\Throwable $e) {
            $tx->rollBack();
            throw $e;
        }
    }
}
