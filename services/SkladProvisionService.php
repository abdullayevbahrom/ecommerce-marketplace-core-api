<?php

namespace app\services;

use GuzzleHttp\Client;
use Yii;
use yii\base\Component;
use yii\helpers\Json;

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
     * @param \app\models\user\User $user
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
            'name' => $user->fio ?? $user->name,
            'phone' => $user->phone,
            'role' => $role,
            'inn' => $user->inn ?? null,
        ];

        $identifier = $payload['global_user_id'] ?: $payload['yii_user_id'];
        $url = rtrim($this->baseUrl, '/') . '/api/internal/users/' . $identifier . '/ensure-personal-warehouse';

        try {
            $client = new Client();
            $response = $client->post($url, [
                'headers' => ['X-Service-Token' => $this->token],
                'json' => $payload,
            ]);

            return Json::decode($response->getBody()->getContents());
        } catch (\Exception $e) {
            Yii::error("Sklad provisioning exception for user {$user->id}: " . $e->getMessage());
            return false;
        }
    }
}
