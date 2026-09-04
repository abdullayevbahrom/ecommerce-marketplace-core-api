<?php

namespace app\services;

use app\models\SmsHistory;
use http\Client;

class SMSCService
{
    const BASE_URL = 'https://api.sms-provider.example.com/rest/send/';
    const LOGIN = 'sample_sms_login';
    const PASSWORD = 'sample_sms_password';

    public function send($phone, $message)
    {
        $ch = curl_init(self::BASE_URL);
        $request =  json_encode([
            'mes' => $message,
            'phones' => $phone,
            'login' => self::LOGIN,
            'psw' => self::PASSWORD,
        ]);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        curl_close($ch);
        SmsHistory::create($phone, $message, $request, $response);
        return  json_decode($response, true);
    }

}