<?php

namespace yii\services;

// use paragraph1\phpFCM\Client;

class Google {
    public $url = 'https://accounts.google.com/o/oauth2/auth';
    public $client_id = '';
    public $client_secret = '';
    public $response_type = 'code';
    public $redirect_uri = '';
    public $scope = 'https://www.googleapis.com/auth/userinfo.email https://www.googleapis.com/auth/userinfo.profile';
    public $state = '123';

    public function params() {
        return [
            'client_id' => $this->client_id,
            'redirect_uri' => $this->redirect_uri,
            'response_type' => $this->response_type,
            'scope' => $this->scope,
            'state' => '123'
        ];
    }

    public function getInfo($code) {
    	$params = array(
            'client_id' => $this->client_id,
            'redirect_uri' => $this->redirect_uri,
            'client_secret' => $this->client_secret,
            'grant_type' => 'authorization_code',
            'code' => $code
        );

        $ch = curl_init('https://accounts.google.com/o/oauth2/token');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $data = curl_exec($ch);
        curl_close($ch);
    
        $data = json_decode($data, true);
        if (!empty($data['access_token'])) {
            $params = array(
                'access_token' => $data['access_token'],
                'id_token' => $data['id_token'],
                'token_type' => 'Bearer',
                'expires_in' => 3599
            );
    
            $info = file_get_contents('https://www.googleapis.com/oauth2/v1/userinfo?' . urldecode(http_build_query($params)));
            $info = json_decode($info, true);
            return $info;
        }
    }
}