<?php

namespace yii\services;

use paragraph1\phpFCM\Client;

class Vk {
    public $url = 'http://oauth.vk.com/authorize';
    public $client_id = '';
    public $client_secret = '';
    public $redirect_uri = '';
    public $response_type = 'code';

    public function params() {
        return [
            'client_id'     => $this->client_id,
            'redirect_uri'  => $this->redirect_uri,
            'response_type' => $this->response_type
        ];
    }

    public function getInfo($code) {
    	$params = array(
            'client_id'     => $this->client_id,
            'redirect_uri'  => $this->redirect_uri,
            'client_secret' => $this->client_secret,
            'code' => $code,
        );

        $userInfo = null;

        $token = json_decode(file_get_contents('https://oauth.vk.com/access_token' . '?' . urldecode(http_build_query($params))), true);
        if (isset($token['access_token'])) {
            $params = array(
                'uids'         => $token['user_id'],
                'fields'       => 'uid,first_name,last_name,screen_name,sex,bdate,photo_big',
                'access_token' => $token['access_token'],
                'v' => '6.0'
            );
            $userInfo = json_decode(file_get_contents('https://api.vk.com/method/users.get?' . urldecode(http_build_query($params))), true);
        }


        return $userInfo;
    }
}