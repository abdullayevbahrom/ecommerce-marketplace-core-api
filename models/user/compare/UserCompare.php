<?php

namespace app\models\user\compare;

use Yii;
use app\models\product\Product;
use app\models\user\User;

/**
 * This is the model class for table "user_compare".
 *
 * @property int $id
 * @property int|null $user_id
 * @property int|null $product_id
 * @property string $date
 *
 * @property Product $product
 * @property User $user
 */
class UserCompare extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user_compare';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['product_id'], 'required', 'message'=>'Заполните поле'],
            [['user_id', 'product_id'], 'integer'],
            [['date'], 'safe'],
            [['product_id'], 'exist', 'skipOnError' => true, 'targetClass' => Product::className(), 'targetAttribute' => ['product_id' => 'id']],
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
            'product_id' => 'Product ID',
            'date' => 'Date',
        ];
    }

    public function saveObject($user_id) {
        $this->user_id = $user_id;

        return $this->save();
    }

    /**
     * Gets query for [[Product]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getProduct()
    {
        return $this->hasOne(Product::className(), ['id' => 'product_id']);
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
