<?php

namespace app\models\order;

use Yii;

/**
 * This is the model class for table "order_receipt".
 *
 * @property int $id
 * @property int|null $order_id
 * @property string|null $receipt_id
 * @property int|null $status
 * @property string $date
 *
 * @property Order $order
 */
class OrderReceipt extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'order_receipt';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['order_id', 'status'], 'integer'],
            [['date'], 'safe'],
            [['receipt_id'], 'string', 'max' => 255],
            [['order_id'], 'exist', 'skipOnError' => true, 'targetClass' => Order::className(), 'targetAttribute' => ['order_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'order_id' => 'Order ID',
            'receipt_id' => 'Receipt ID',
            'status' => 'Status',
            'date' => 'Date',
        ];
    }

    public function fields() {
        return ['id', 'amount'=>function(){return $this->order->price;}, 'receipt_id', 'status', 'date'];
    }

    /**
     * Gets query for [[Order]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOrder()
    {
        return $this->hasOne(Order::className(), ['id' => 'order_id']);
    }
}
