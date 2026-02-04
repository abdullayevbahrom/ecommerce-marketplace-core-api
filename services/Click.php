<?php

namespace yii\services;

use Yii;
use yii\rest\Controller;

use app\models\User;
use app\models\Order;
use app\models\transaction_click\TransactionClick;

class Click
{
    public $params = [];

    public function prepare() {
        // check sign key
        if(!isset($this->params['sign_string']) || $this->params['sign_string'] !== $this->getSing()) {
            exit(json_encode(self::clickMessages()[1]));
        }

        // check order
        $order = Order::findOne($this->params['merchant_trans_id']);
        if(!$order) {
            exit(json_encode(self::clickMessages()[5]));
        }

        if ($order->status_paid == 1) {
            exit(json_encode(self::clickMessages()[5]));
        }

        if ($order->product_total != $this->params['amount']) {
            exit(json_encode(self::clickMessages()[2]));
        }

        $transaction = TransactionClick::find()->where([
            'status' => 0,
            'click_trans_id' => $this->params['click_trans_id']
        ])->one();

        if (!$transaction) {
            $transaction = new TransactionClick;
            $transaction->status = "0";
            $transaction->amount = $this->params['amount'];
            $transaction->account = $this->params['merchant_trans_id'];
            $transaction->date = time();
            $transaction->click_trans_id = $this->params['click_trans_id'];
            $transaction->error = $this->params['error'];
            $transaction->save();
        }

        $return = array(
            'click_trans_id' => $transaction->click_trans_id,
            'merchant_trans_id' => $transaction->account,
            'merchant_prepare_id' => $transaction->id,
        );

        exit(json_encode(array_merge(self::clickMessages()[0], $return)));
    }

    public function complete() {
        // check sign key
        if(!isset($this->params['sign_string']) || $this->params['sign_string'] !== $this->getSing()) {
            exit(json_encode(self::clickMessages()[1]));
        }

        $order = Order::findOne($this->params['merchant_trans_id']);

        if(!$order) {
            exit(json_encode(self::clickMessages()[5]));
        }

        if ($order->status_paid == 1) {
            exit(json_encode(self::clickMessages()[5]));
        }

        if ($order->product_total != $this->params['amount']) {
            exit(json_encode(self::clickMessages()[2]));
        }

        $transaction = TransactionClick::findOne($this->params['merchant_prepare_id']);

        if($transaction) {
            if($this->params['error'] == "-1") {
                exit(json_encode(self::clickMessages()[4]));
            }

            if($transaction->status == "1") {
                exit(json_encode(self::clickMessages()[4]));
            }

            if($transaction->amount != $this->params['amount']) {
                exit(json_encode(self::clickMessages()[2]));
            }

            if($this->params['error'] == "-5017") {
                $transaction->error = "-5017";
                $transaction->save();
                exit(json_encode(self::clickMessages()[9]));
            }

            if($transaction->error == "-5017") {
                exit(json_encode(self::clickMessages()[9]));
            }
            $transaction->status = "1";
            if($transaction->save()) {
                $order = Order::findOne($transaction->account);
                $order->status_paid = 1;
                $order->save(false);
                $return = array(
                    'click_trans_id'=> $transaction->click_trans_id,
                    'merchant_trans_id' => $transaction->account,
                    'merchant_confirm_id' => $transaction->id
                );
                $user = User::findOne($order->user_id);
                $user->balance = $user->balance + $order->product_total;
                $user->save(false);
                
                exit(json_encode(array_merge(self::clickMessages()[0], $return)));
            }else{
                exit(json_encode(self::clickMessages()[7]));
            }

            exit(json_encode(self::clickMessages()[9]));
        } else {
            exit(json_encode(self::clickMessages()[6]));
        }
    }

    private function getSing() {
        return md5(
            $this->params['click_trans_id'] .
            $this->params["service_id"] .
            '' .
            $this->params['merchant_trans_id'] .
            ($this->params['action'] == 1 ? $this->params['merchant_prepare_id'] : '').
            $this->params['amount'] .
            $this->params['action'] .
            $this->params['sign_time']
        );
    }

    public static function clickMessages() {
        return [
            ["error" => "0", "error_note" => "Success"],
            ["error" => "-1", "error_note" => "SIGN CHECK FAILED"],
            ["error" => "-2", "error_note" => "Amount not correct"],
            ["error" => "-3", "error_note" => "Action not found"],
            ["error" => "-4", "error_note" => "Already paid"],
            ["error" => "-5", "error_note" => "User does not exist"],
            ["error" => "-6", "error_note" => "Transaction does not exist"],
            ["error" => "-7", "error_note" => "Failed to update user"],
            ["error" => "-8", "error_note" => "Error in request from click"],
            ["error" => "-9", "error_note" => "Transaction cancelled"]
        ];
    }
}