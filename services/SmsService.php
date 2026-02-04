<?php

namespace yii\services;

class SmsService {
    protected $url = 'http://sms-gateway.example.com/smsgateway/';

    protected $login = 'sample_sms_login';

    protected $password = 'sample_sms_password';

    /**
     * Отправить запрос.
     *
     * @param $data
     * @return mixed
     */
    public function request($phone, $text)
    {
        $data = [
            'login' => $this->login,
            'password' => $this->password,
            'data' => [[
                "phone"=>$phone,
                "text"=>$text
            ]]
        ];

        $data['data'] = json_encode($data['data']);

        $headers = [];
        $ch = curl_init($this->url);

        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response);
    }
}