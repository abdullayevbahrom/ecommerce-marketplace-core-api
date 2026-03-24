<?php

namespace app\components\RabbitMq\Handlers;

use app\models\Ikpu;
use Yii;

class IkpuDeletedHandler
{
    public function handle(array $message): void
    {
        $payload = $message['payload'];
        $tx = Yii::$app->db->beginTransaction();

        try {
            $ikpu = $this->findExistingIkpu($payload);

            if ($ikpu) {
                $ikpu->suppressSyncEvents = true;
                $ikpu->delete();
            }

            $tx->commit();
        } catch (\Throwable $e) {
            $tx->rollBack();
            throw $e;
        }
    }

    protected function findExistingIkpu(array $payload): ?Ikpu
    {
        if (!empty($payload['yii_ikpu_id'])) {
            $model = Ikpu::findOne((int) $payload['yii_ikpu_id']);
            if ($model) {
                return $model;
            }
        }

        if (!empty($payload['code'])) {
            return Ikpu::findOne(['code' => $payload['code']]);
        }

        if (!empty($payload['id'])) {
            return Ikpu::findOne((int) $payload['id']);
        }

        return null;
    }
}
