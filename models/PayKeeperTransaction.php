<?php

namespace app\models;

/**
 * Class PayKeeperTransaction
 * @package app\models
 * @property int $id
 * @property int $transaction_id
 * @property int $order_id
 * @property int $amount
 * @property int $status
 * @property int $currency
 * @property int $request
 * @property int $response
 * @property int $date
 */

class PayKeeperTransaction extends \yii\db\ActiveRecord {

    public static function tableName() {
        return 'pay_keeper_transaction';
    }

    public function rules() {
        return [
            [['transaction_id', 'order_id', 'amount', 'status', 'currency', 'request','response', 'date'], 'safe'],
        ];
    }
    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'transaction_id' => 'transaction_id',
            'order_id' => 'order_id',
            'amount' => 'amount',
            'status' => 'status',
            'currency' => 'currency',
            'request' => 'request',
            'response' => 'response',
            'date' => 'date',
        ];
    }

    public static function create($order_id, $amount, $transaction_id,$request, $response){
        $model = new self();
        $model->transaction_id = $transaction_id;
        $model->order_id = $order_id;
        $model->amount = $amount;
        $model->status = 1;
        $model->currency = 643;
        $model->request = $request;
        $model->response = $response;
        $model->date = time();
        $model->save();
    }


}
