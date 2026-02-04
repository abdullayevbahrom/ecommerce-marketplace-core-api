<?php

namespace app\services\Sms;

class Sms {
    /**
     * Отправляет SMS-сообщение через API smsxabar.uz.
     *
     * @param string $phone Номер телефона получателя.
     * @param string $text Текст сообщения.
     * @return bool|string Ответ от сервера SMS-шлюза или false в случае ошибки.
     */
    public function send($phone, $text) {
        $url = 'https://send.smsxabar.uz/broker-api/send';

        $login = Yii::$app->params['sms_login'];
        $password = Yii::$app->params['sms_password'];
        $password = 'HS#4';

        $full_text = "app. " . $text;

        $request = [
            'messages' => [
                [
                    'recipient' => $phone,
                    'message-id' => 'slf' . uniqid(),
                    'sms' => [
                        'originator' => '3700',
                        'content' => [
                            'text' => $full_text
                        ]
                    ]
                ]
            ]
        ];
        $data = json_encode($request);

        $headers = [
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode($login . ':' . $password)
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code >= 200 && $http_code < 300) {
            return $response;
        } else {
            Yii::error("SMS sending failed. HTTP code: {$http_code}, Response: {$response}", 'sms');
            return false;
        }
    }
}