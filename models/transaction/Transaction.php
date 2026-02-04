<?php

namespace app\models\transaction;

use Yii;
use app\models\user\User;
use app\models\order\Order;

/**
 * This is the model class for table "transaction".
 *
 * @property int $id
 * @property int|null $user_id
 * @property int|null $order_id
 * @property string|null $type_transaction
 * @property string|null $type_payment
 * @property float|null $amount
 * @property int $status
 * @property string $date
 */
class Transaction extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'transaction';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'order_id', 'shop_id', 'status'], 'integer'],
            [['amount'], 'number'],
            [['date'], 'safe'],
            [['type_transaction', 'type_payment'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'order_id' => 'Order ID',
            'type_transaction' => 'Type Transaction',
            'type_payment' => 'Type Payment',
            'amount' => 'Amount',
            'status' => 'Status',
            'date' => 'Date',
        ];
    }

    public function fields() {
        return ['id', 'type_payment', 'type_transaction', 'amount', 'order', 'user'];
    }

    // relations
    public function getUser() {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }

    public function getOrder() {
        return $this->hasOne(Order::className(), ['id' => 'order_id']);
    }
}
