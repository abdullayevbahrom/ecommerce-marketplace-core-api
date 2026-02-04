<?php

namespace app\models\user\cart;

use Yii;
use app\models\product\ProductFilter;

/**
 * This is the model class for table "user_cart_filter".
 *
 * @property int $id
 * @property int|null $user_cart_id
 * @property int|null $product_filter_id
 *
 * @property ProductFilter $productFilter
 * @property UserCart $userCart
 */
class UserCartFilter extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user_cart_filter';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_cart_id', 'product_filter_id'], 'integer'],
            [['product_filter_id'], 'exist', 'skipOnError' => true, 'targetClass' => ProductFilter::className(), 'targetAttribute' => ['product_filter_id' => 'id']],
            [['user_cart_id'], 'exist', 'skipOnError' => true, 'targetClass' => UserCart::className(), 'targetAttribute' => ['user_cart_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_cart_id' => 'User Cart ID',
            'product_filter_id' => 'Product Filter ID',
        ];
    }

    /**
     * Gets query for [[ProductFilter]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getProductFilter()
    {
        return $this->hasOne(ProductFilter::className(), ['id' => 'product_filter_id']);
    }

    /**
     * Gets query for [[UserCart]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUserCart()
    {
        return $this->hasOne(UserCart::className(), ['id' => 'user_cart_id']);
    }
}
