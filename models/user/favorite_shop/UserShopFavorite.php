<?php

namespace app\models\user\favorite_shop;

use Yii;
use app\models\user\User;
use app\models\shop\Shop;

/**
 * This is the model class for table "user_shop_favorite".
 *
 * @property int $id
 * @property int|null $user_id
 * @property int|null $shop_id
 * @property string $date
 *
 * @property Shop $shop
 * @property User $user
 */
class UserShopFavorite extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user_shop_favorite';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['shop_id'], 'required', 'message'=>'Заполните поле'],
            [['user_id', 'shop_id'], 'integer'],
            [['date'], 'safe'],
            [['shop_id'], 'exist', 'skipOnError' => true, 'targetClass' => Shop::className(), 'targetAttribute' => ['shop_id' => 'id']],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['user_id' => 'id']],
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
            'shop_id' => 'Shop ID',
            'date' => 'Date',
        ];
    }

    public function saveObject($user_id) {
        $this->user_id = $user_id;

        return $this->save();
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

    /**
     * Gets query for [[User]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }
}
