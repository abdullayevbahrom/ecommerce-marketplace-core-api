<?php

namespace app\components\RabbitMq\Handlers;

use app\models\user\User;
use Yii;

class UserUpsertHandler
{
    public function handle(array $message): void
    {
        $payload = $message['payload'];
        $warehouseUserId = $message['entity_id'];

        $tx = Yii::$app->db->beginTransaction();

        try {
            $user = null;

            if (!empty($payload['id'])) {
                $user = User::findOne(['id' => (int) $payload['id']]);
            }

            if (!$user && !empty($payload['phone'])) {
                $user = User::find()
                    ->where(['phone' => $payload['phone'], 'shop_id' => (int) ($payload['yii_shop_id'] ?? 0)])
                    ->one();
            }

            $isNewRecord = $user === null;

            if (!$user) {
                $user = new User();
                $user->password = '1';
                $user->token = '';
                $user->date = date('Y-m-d H:i:s');
            }

            $user->shop_id = (int) ($payload['yii_shop_id'] ?? $user->shop_id);
            $user->name = $payload['name'] ?? $user->name;
            $user->phone = $payload['phone'] ?? $user->phone;
            $user->status = (int) ($payload['status'] ?? 1);
            $user->role = (int) ($payload['role'] ?? User::ROLE_USER);
            $user->source = User::SOURCE_SKLAD;

            if (!empty($payload['id'])) {
                $user->id = (int) $payload['id'];
            }

            $user->save(false);

            $tx->commit();

            if ($isNewRecord) {
                $token = md5($warehouseUserId . Yii::$app->params['apiSecretKey']);
                $warehouseApiUrl = rtrim(Yii::$app->params['warehouseApiUrl'], '/');

                /** @var \GuzzleHttp\Client $client */
                $client = Yii::$app->httpClient;
                $client->post(
                    $warehouseApiUrl . "/api/sync-webhook/users/{$warehouseUserId}/set-user-id",
                    [
                        'json' => ['id' => $warehouseUserId, 'yii_user_id' => $user->id],
                        'headers' => [
                            'X-Api-Token' => $token,
                            'Content-Type' => 'application/json',
                        ],
                    ]
                );
            }
        } catch (\Throwable $e) {
            $tx->rollBack();
            throw $e;
        }
    }
}
