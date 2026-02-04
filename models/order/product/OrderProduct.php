<?php

namespace app\models\order\product;

use Yii;
use app\models\order\Order;
use app\models\product\Product;
use app\models\delivery\Delivery;

/**
 * This is the model class for table "order_product".
 *
 * @property int $id
 * @property int|null $order_id
 * @property int|null $product_id
 * @property int|null $stock_id
 * @property float|null $amount
 * @property float|null $price
 * @property float|null $product_price
 * @property string|null $bts_id
 * @property string|null $bts_status
 * @property string|null $bts_status_info
 * @property float|null $bts_price
 * @property string|null $address
 * @property string $date
 *
 * @property Order $order
 * @property Product $product
 * @property \app\models\stock\Stock $stock
 */
class OrderProduct extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'order_product';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['order_id', 'product_id', 'delivery_id', 'shop_id', 'user_id', 'status', 'stock_id'], 'integer'],
            [['amount', 'price', 'product_price', 'delivery_cost', 'bts_price'], 'number'],
            [['date'], 'safe'],
            [['bts_id', 'bts_status', 'bts_status_info', 'address'], 'string'],
            [['order_id'], 'exist', 'skipOnError' => true, 'targetClass' => Order::className(), 'targetAttribute' => ['order_id' => 'id']],
            [['product_id'], 'exist', 'skipOnError' => true, 'targetClass' => Product::className(), 'targetAttribute' => ['product_id' => 'id']],
            [['stock_id'], 'exist', 'skipOnError' => true, 'targetClass' => \app\models\stock\Stock::className(), 'targetAttribute' => ['stock_id' => 'id']],
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
            'product_id' => 'Product ID',
            'stock_id' => 'Stock ID',
            'amount' => 'Amount',
            'delivery_cost' => 'Delivery Cost',
            'price' => 'Unit Price',
            'product_price' => 'Total Price',
            'bts_id' => 'BTS ID',
            'bts_status' => 'BTS Status',
            'bts_status_info' => 'BTS Status Info',
            'bts_price' => 'BTS Price',
            'address' => 'Delivery Address',
            'date' => 'Date',
        ];
    }

    public function fields() {
        return [
            'id', 
            'delivery', 
            'price', 
            'product_price',
            'unit_price' => function() { return $this->getUnitPrice(); },
            'total_cost' => function() { return $this->getTotalCost(); },
            'delivery_cost', 
            'amount', 
            'status', 
            'product', 
            'refund', 
            'stock', 
            'bts_id', 
            'bts_status', 
            'bts_status_info', 
            'bts_price', 
            'address'
        ];
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

    /**
     * Gets query for [[Product]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getProduct()
    {
        return $this->hasOne(Product::className(), ['id' => 'product_id']);
    }

    public function getOrderProductFilter()
    {
        return $this->hasMany(OrderProductFilter::className(), ['order_product_id' => 'id']);
    }

    public function getRefund()
    {
        return $this->hasOne(OrderProductRefund::className(), ['order_product_id' => 'id']);
    }

    public function getDelivery()
    {
        return $this->hasOne(Delivery::className(), ['id' => 'delivery_id']);
    }

    public function getProductReview()
    {
        return $this->hasOne(\app\models\product\review\ProductReview::className(), ['product_id' => 'product_id'])
                    ->andOnCondition(['user_id' => $this->order->user_id]);
    }

    public function getProductReviews()
    {
        return $this->hasMany(\app\models\product\review\ProductReview::className(), ['product_id' => 'product_id'])
                    ->with('user');
    }

    public function hasReview()
    {
        return $this->productReview !== null;
    }

    public function hasReviews()
    {
        return count($this->productReviews) > 0;
    }

    /**
     * Gets query for [[Stock]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getStock()
    {
        return $this->hasOne(\app\models\stock\Stock::className(), ['id' => 'stock_id']);
    }

    /**
     * Check if this order product has BTS integration
     * @return bool
     */
    public function hasBtsIntegration()
    {
        return !empty($this->bts_id);
    }

    /**
     * Get BTS status label
     * @return string
     */
    public function getBtsStatusLabel()
    {
        return $this->bts_status_info ?? 'Unknown status';
    }

    /**
     * Get delivery address - custom address or order address
     * @return string
     */
    public function getDeliveryAddress()
    {
        return $this->address ?? $this->order->address;
    }

    /**
     * Get total cost including delivery
     * @return float
     */
    public function getTotalCost()
    {
        return ($this->price ?: 0);
    }

    /**
     * Get unit price (for backwards compatibility and display)
     * @return float
     */
    public function getUnitPrice()
    {
        return $this->amount > 0 ? ($this->product_price ?: 0) / $this->amount : ($this->price ?: 0);
    }
}
