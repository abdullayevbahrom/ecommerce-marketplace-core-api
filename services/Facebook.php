<?php

namespace yii\services;

// use paragraph1\phpFCM\Client;

class Facebook {
    public $url = 'https://www.facebook.com/dialog/oauth';
    public $url_token = 'https://graph.facebook.com/oauth/access_token';
    public $client_id = '';
    public $client_secret = '';
    public $redirect_uri = '';

    public function params() {
        return [
            'client_id'     => $this->client_id,
            'redirect_uri'  => $this->redirect_uri,
            'response_type' => 'code',
            'scope'         => 'email'
        ];
    }

    public function getInfo($code) {
    	$params = array(
            'client_id'     => $this->client_id,
            'redirect_uri'  => $this->redirect_uri,
            'client_secret' => $this->client_secret,
            'code'          => $code
        );

        $userInfo = null;

        $tokenInfo = json_decode(file_get_contents($this->url_token.'?'.http_build_query($params)));

        if ($tokenInfo->access_token) {
            $params = ['access_token' => $tokenInfo->access_token];

            $userInfo = json_decode(file_get_contents('https://graph.facebook.com/me?'.urldecode(http_build_query($params))), true);
        }

        return $userInfo;
    }
}