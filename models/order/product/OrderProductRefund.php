<?php

namespace app\models\order\product;

use Yii;
use app\models\user\User;
use app\models\order\Order;

/**
 * This is the model class for table "order_product_refund".
 *
 * @property int $id
 * @property int|null $order_product_id
 * @property string|null $message
 * @property string $date
 *
 * @property OrderProduct $orderProduct
 */
class OrderProductRefund extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'order_product_refund';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['order_product_id'], 'required', 'message'=>'Заполните поле'],
            ['order_product_id', 'checkOrder'],
            [['order_product_id', 'user_id'], 'integer'],
            [['message'], 'string'],
            [['date'], 'safe'],
            [['order_product_id'], 'exist', 'skipOnError' => true, 'targetClass' => OrderProduct::className(), 'targetAttribute' => ['order_product_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'order_product_id' => 'Order Product ID',
            'message' => 'Message',
            'date' => 'Date',
        ];
    }

    // check order
    public function checkOrder($attribute, $params) {
        if (!$this->hasErrors()) {
            $user = Yii::$app->user->identity;

            $refund = self::findOne(['user_id'=>$user->id, 'order_product_id'=>$this->order_product_id]);
            if ($refund) {
                return $this->addError($attribute, 'Вы уже сделал возврат этого товара');
            }

            $order_product = OrderProduct::find()->with('order')->where(['id'=>$this->order_product_id])->one();

            if (!$order_product) {
                return $this->addError($attribute, 'Заказ не найден');
            }

            if (($user->role == User::ROLE_USER) && ($order_product->order->user_id != $user->id)) {
                return $this->addError($attribute, 'Заказ не найден');
            }
        }

        return false;
    }

    public function getProduct() {
        $data = [];

        if ($this->orderProduct && $this->orderProduct->product) {
            $data = [
                'id' => $this->orderProduct->product->id,
                'name' => $this->orderProduct->product->name_ru,
                'order_product_id' => $this->orderProduct->id,
                'photo' => $this->orderProduct->product->getPhoto(),
            ];
        }

        return $data;
    }

    public function fields() {
        return ['id', 'message', 'product', 'date'];
    }

    /**
     * Gets query for [[OrderProduct]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOrderProduct()
    {
        return $this->hasOne(OrderProduct::className(), ['id' => 'order_product_id']);
    }

    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }
}
