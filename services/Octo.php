<?php
namespace yii\services;

// use paragraph1\phpFCM\Client;

class Octo {
    protected $url = 'https://secure.example.com';
    protected $shop_id = getenv('OCTO_SHOP_ID') ?: '10001';
    protected $secret = getenv('OCTO_SECRET') ?: '00000000-0000-0000-0000-000000000000';
    protected $notify_url = 'https://api.example.com/payment/octo/notify';

    public function prepare($order) {
        $data = [
            'octo_shop_id' => $this->shop_id,
            'octo_secret' => $this->secret,
            'shop_transaction_id' => $order->id,
            'auto_capture' => true,
            'test' => true,
            'init_time' => date('Y-m-d H:i:s'),
            'total_sum' => $order->price,
            'currency' => 'UZS',
            'description' => 'Опата заказа',
            'language' => 'ru',
            'return_url' => '',
            'notify_url' => $this->notify_url,
            'ttl' => 15
        ];

        $url = $this->url.'/prepare_payment';
        $ch = curl_init($url);
        $payload = json_encode($data);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }
}