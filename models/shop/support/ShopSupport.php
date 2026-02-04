<?php

namespace app\models\shop\support;

use Yii;
use app\models\user\User;
use app\models\shop\Shop;
use app\models\Notification;

/**
 * This is the model class for table "shop_support".
 *
 * @property int $id
 * @property int|null $shop_id
 * @property string|null $theme
 * @property string|null $message
 * @property int $status
 * @property string $date
 *
 * @property Shop $shop
 */
class ShopSupport extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'shop_support';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['theme', 'message'], 'required', 'message'=>'Заполните поле'],
            [['shop_id', 'user_id', 'status'], 'integer'],
            [['message'], 'string'],
            [['date'], 'safe'],
            [['theme'], 'string', 'max' => 255],
            [['shop_id'], 'exist', 'skipOnError' => true, 'targetClass' => Shop::className(), 'targetAttribute' => ['shop_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'shop_id' => 'Shop ID',
            'theme' => 'Theme',
            'message' => 'Message',
            'status' => 'Status',
            'date' => 'Date',
        ];
    }

    public function saveObject() {
        $shop = Shop::findOne(['user_id'=>Yii::$app->user->identity->id]);

        $this->shop_id = $shop->id;
        $this->status = 0;

        if ($this->save()) {
            $admin = User::findOne(['role'=>User::ROLE_ADMIN]);

            $notification = new Notification;
            $notification->saveObject($admin->id, $this->id, 'product_new', 'Добавлен новый товар');

            return true;
        }

        return false;
    }

    /**
     * Gets query for [[Shop]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getShop()
    {
        return $this->hasOne(Shop::className(), ['id' => 'shop_id']);
    }

    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }
}
