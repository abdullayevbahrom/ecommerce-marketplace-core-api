<?php

namespace app\services;

use Yii;
use yii\base\Component;
use yii\helpers\Json;
use yii\httpclient\Client;

class SkladProvisionService extends Component
{
    public $baseUrl;
    public $token;

    public function init()
    {
        parent::init();
        $this->baseUrl = Yii::$app->params['skladInternalUrl'] ?? 'http://api.warehouse.example.com';
        $this->token = Yii::$app->params['auth']['gateway']['internalToken'] ?? '';
    }

    /**
     * Ensure a personal warehouse for a user in Sklad service.
     * 
     * @param \app\models\User $user
     * @param string $role
     * @return array|false
     */
    public function ensurePersonalWarehouse($user, $role = 'user')
    {
        if (!$this->baseUrl) {
            Yii::warning("Sklad base URL not configured, skipping provisioning.");
            return false;
        }

        $payload = [
            'global_user_id' => $user->global_user_id ?? null,
            'yii_user_id' => $user->id,
            'yii_shop_id' => $user->shop_id ?? null,
            'name' => $user->fio ?? $user->username,
            'phone' => $user->phone,
            'role' => $role,
            'inn' => $user->inn ?? null,
        ];

        try {
            $client = new Client();
            $response = $client->createRequest()
                ->setMethod('POST')
                ->setUrl($this->baseUrl . '/api/internal/users/' . ($payload['global_user_id'] ?: $payload['yii_user_id']) . '/ensure-personal-warehouse')
                ->setHeaders(['X-Service-Token' => $this->token])
                ->setData($payload)
                ->send();

            if (!$response->isOk) {
                Yii::error("Sklad provisioning failed for user {$user->id}: " . $response->content);
                return false;
            }

            return Json::decode($response->content);
        } catch (\Exception $e) {
            Yii::error("Sklad provisioning exception for user {$user->id}: " . $e->getMessage());
            return false;
        }
    }
}
