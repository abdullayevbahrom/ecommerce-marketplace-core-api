<?php
namespace yii\services;

use Yii;
use yii\rest\Controller;

use app\models\order\Order;

class PaymeSubscribe
{
    // kassa
    protected $id = 'sample_payme_merchant_id';
    protected $password = 'sample_payme_secret_key';
    protected $url = 'https://checkout.example.com/api';

    public function sendRequest($data, $key = false) {
        $password = ($key === true) ? $this->id.':'.$this->password : $this->id;

        $headers = array(
            'Cache-Control: no-cache',
            'Content-Type: application/json',
            'X-Auth: '.$password
        );

        $ch = curl_init($this->url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

    // cards
    public function createCard($post) {
        $data = [
            "id" => $this->id,
            "method" => "cards.create",
            "params" => [
                "card" => [
                    "number" => $post['card_number'],
                    "expire" => $post['card_expire']
                ],
                "save" => true
            ]
        ];

        return $this->sendRequest($data);
    }

    public function getVerifyCode($token) {
        $data = [
            "id" => $this->id,
            "method" => "cards.get_verify_code",
            "params" => [
                'token' => $token
            ]
        ];

        return $this->sendRequest($data);
    }

    public function verify($token, $code) {
        $data = [
            "id" => $this->id,
            "method" => "cards.verify",
            "params" => [
                'token' => $token,
                'code' => $code
            ]
        ];

        return $this->sendRequest($data);
    }

    public function check($token) {
        $data = [
            "id" => $this->id,
            "method" => "cards.check",
            "params" => [
                'token' => $token
            ]
        ];

        return $this->sendRequest($data);
    }

    public function remove($token) {
        $data = [
            "id" => $this->id,
            "method" => "cards.remove",
            "params" => [
                'token' => $token
            ]
        ];

        return $this->sendRequest($data);
    }
    // end cards

    // receipts
    public function receiptCreate($order_id, $order_price) {
        $data = [
            "id" => $this->id,
            "method" => "receipts.create",
            "params" => [
                'amount' => $order_price*100,
                'account' => [
                    'id' => $order_id
                ]
            ]
        ];

        return $this->sendRequest($data, true);
    }

    public function receiptPay($id, $token, $user) {
        $data = [
            "id" => $this->id,
            "method" => "receipts.pay",
            "params" => [
                'id' => $id,
                'token' => $token,
                'payer' => [
                    'id' => $user->id,
                    'phone' => $user->phone,
                    'email' => $user->email,
                    'name' => $user->name,
                    'ip' => Yii::$app->request->userIP
                ]
            ]
        ];
        
        return $this->sendRequest($data, true);
    }

    public function receiptCancel($id) {
        $data = [
            "id" => $this->id,
            "method" => "receipts.cancel",
            "params" => [
                'id' => $id,
            ]
        ];

        return $this->sendRequest($data, true);
    }

    public function receiptCheck($id) {
        $data = [
            "id" => $this->id,
            "method" => "receipts.check",
            "params" => [
                'id' => $id,
            ]
        ];

        return $this->sendRequest($data, true);
    }
    // end receipts
}