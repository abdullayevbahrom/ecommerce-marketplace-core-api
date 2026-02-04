<?php

namespace app\models\feedback;

use Yii;
use app\models\user\User;
use app\models\shop\Shop;

/**
 * This is the model class for table "feedback".
 *
 * @property int $id
 * @property string|null $name
 * @property string|null $email
 * @property string|null $message
 * @property int $status
 * @property string $date
 */
class Feedback extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'feedback';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'email', 'message'], 'required', 'message' => 'Заполните поле'],
            [['message'], 'string'],
            [['status', 'user_id', 'shop_id'], 'integer'],
            [['date'], 'safe'],
            [['name', 'email'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'email' => 'Email',
            'message' => 'Message',
            'status' => 'Status',
            'date' => 'Date',
        ];
    }

    public function getUser() {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }

    public function getShop() {
        return $this->hasOne(Shop::className(), ['id' => 'shop_id']);
    }
}
