<?php
namespace yii\services;

class Billz
{
    public $url = 'https://api.billz.uz/v1/';
    public $token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJub3ZleS5pdC1tYWtlci51eiIsImlhdCI6MTY2NTU0MDA1MiwiZXhwIjoxOTE4MDAwODUyLCJzdWIiOiJub3ZleS5lY29tbWVyY2UifQ.K00Eb53UF-YgFogjLXu8EijfbesIFtgaovx7SNcXDR4';

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