<?php
namespace yii\services;

class Billz
{
    public $url = 'https://api.pos-integration.example.com/v1/';
    public $token = 'sample_jwt_token';

    public function request($data)
    {
        $headers = array(
            'Content-Type: application/json',
            'Authorization: Bearer '.$this->token
        );

        $ch = curl_init($this->url);

        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "$data");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response);
    }
}