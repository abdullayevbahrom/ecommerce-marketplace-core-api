<?php

namespace app\models;

use Yii;
use app\models\order\Order;
use app\models\product\Product;

/**
 * This is the model class for table "notification".
 *
 * @property int $id
 * @property int $user_id
 * @property int $object_id
 * @property string $type
 * @property int $status
 * @property string $message
 * @property string $date
 */
class Notification extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'notification';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'object_id', 'status'], 'integer'],
            [['date'], 'safe'],
            [['type', 'message'], 'string', 'max' => 255],
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
            'object_id' => 'Object ID',
            'type' => 'Type',
            'status' => 'Status',
            'message' => 'Message',
            'date' => 'Date',
        ];
    }

    public function saveObject($user_id, $object_id, $type, $message) {
        $this->user_id = $user_id;
        $this->object_id = $object_id;
        $this->type = $type;
        $this->message = $message;
        $this->status = 0;

        return $this->save();
    }

    public function getData() {
        if (($this->type == 'sended_to_delivery') || ($this->type == 'sended_to_return') || ($this->type == 'order_accepted') || ($this->type == 'order_declined')) {
            return $this->order;
        }

        return null;
    }

    public function fields() {
        return ['id', 'type', 'status', 'message', 'date', 'data'=>function() {return $this->getData();}];
    }

    public function urlAdmin() {
        return Yii::$app->urlManager->createUrl(['/admin/notification/view', 'id'=>$this->id]);
    }

    // relations
    public function getOrder() {
        return $this->hasOne(Order::className(), ['id' => 'object_id']);
    }

    public function getProduct() {
        return $this->hasOne(Product::className(), ['id' => 'object_id']);
    }
}
