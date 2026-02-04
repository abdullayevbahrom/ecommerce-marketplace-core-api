<?php

namespace app\models\order\product;

use Yii;
use app\models\product\ProductFilter;

/**
 * This is the model class for table "order_product_filter".
 *
 * @property int $id
 * @property int|null $order_product_id
 * @property int|null $product_filter_id
 *
 * @property OrderProduct $orderProduct
 * @property ProductFilter $productFilter
 */
class OrderProductFilter extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'order_product_filter';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['order_product_id', 'product_filter_id'], 'integer'],
            [['order_product_id'], 'exist', 'skipOnError' => true, 'targetClass' => OrderProduct::className(), 'targetAttribute' => ['order_product_id' => 'id']],
            [['product_filter_id'], 'exist', 'skipOnError' => true, 'targetClass' => ProductFilter::className(), 'targetAttribute' => ['product_filter_id' => 'id']],
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
            'product_filter_id' => 'Product Filter ID',
        ];
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

    /**
     * Gets query for [[ProductFilter]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getProductFilter()
    {
        return $this->hasOne(ProductFilter::className(), ['id' => 'product_filter_id']);
    }
}
