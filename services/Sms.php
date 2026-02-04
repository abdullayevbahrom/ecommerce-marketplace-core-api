<?php

namespace yii\services;

class Sms {
    protected $url = 'http://sms-gateway.example.com/broker-api/send';

    protected $login = 'sample_sms_login';

    protected $password = 'sample_sms_password';

    /**
     * Максимальное кол-во сообщений в одном запросе.
     *
     * @var int
     */
    protected $maxMessages = 500;

    /**
     * Сообщения для массовой рассылки.
     *
     * @var array
     */
    protected $messages = [];

    /**
     * Добавить сообщение для массовой рассылки.
     *
     * @param $phone
     * @param $message
     */
    public function add($phone, $message) {
        $this->messages[] = ['phone' => $phone, 'message' => $message];
    }

    /**
     * Отпраляет одно sms сообщение.
     *
     * @param $phone
     * @param $message
     * @return mixed
     */
    public function send($phone, $message)
    {
        $request = $this->makeRequest($phone, $message);
        return $this->request($request);
    }

    /**
     * Отправить все сообщения из массовой рассылки.
     */
    public function sendAll()
    {
        $chunks = array_chunk($this->messages, $this->maxMessages);

        foreach ($chunks as $messages) {
            $request = $this->makeRequest($messages);
            $this->request($request);
        }
    }

    /**
     * Отправить запрос.
     *
     * @param $data
     * @return mixed
     */
    protected function request($data)
    {
        $headers = array(
            'Content-Type: application/json',
            'Authorization: Basic '. base64_encode($this->login.':'.$this->password)
        );
        $ch = curl_init($this->url);

        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "$data");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

    /**
     * Сформировать запрос.
     *
     * @param array $messages
     * @return string
     */
    protected function makeRequest($phone, $message)
    {
        $request = [
            'messages'=>[[
                'recipient'=>$phone,
                'message-id'=>'brm'.rand(1, 99999),
                'sms'=>[
                    'originator'=>'3700',
                    'content'=>[
                        'text'=>$message
                    ]
                ]
            ]]
        ];

        return json_encode($request);
    }
}