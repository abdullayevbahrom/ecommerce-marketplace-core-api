<?php

namespace app\components\RabbitMq\Handlers;

use app\models\user\User;
use Yii;

class UserDeletedHandler
{
    public function handle(array $message): void
    {
        $payload = $message['payload'];

        $tx = Yii::$app->db->beginTransaction();

        try {
            $user = null;

            if (!empty($payload['id'])) {
                $user = User::findOne(['id' => (int) $payload['id']]);
            }

            if (!$user) {
                $tx->commit();
                return;
            }

            $user->delete();

            $tx->commit();
        } catch (\Throwable $e) {
            $tx->rollBack();
            throw $e;
        }
    }
}
