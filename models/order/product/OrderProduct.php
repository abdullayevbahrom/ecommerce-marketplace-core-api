<?php

namespace app\models\order\product;

use Yii;
use app\models\order\Order;
use app\models\product\Product;
use app\models\delivery\Delivery;
use \app\models\stock\Stock;
use yii\db\ActiveQuery;

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
 * @property Stock $stock
 */
class OrderProduct extends \yii\db\ActiveRecord
{
    public static function tableName()
    {
        return 'order_product';
    }

    public function rules()
    {
        return [
            [['order_id', 'product_id', 'delivery_id', 'shop_id', 'user_id', 'status', 'stock_id'], 'integer'],
            [['amount', 'price', 'product_price', 'delivery_cost', 'bts_price'], 'number'],
            [['date'], 'safe'],
            [['bts_id', 'bts_status', 'bts_status_info', 'address'], 'string'],
            [['order_id'], 'exist', 'skipOnError' => true, 'targetClass' => Order::class, 'targetAttribute' => ['order_id' => 'id']],
            [['product_id'], 'exist', 'skipOnError' => true, 'targetClass' => Product::class, 'targetAttribute' => ['product_id' => 'id']],
            [['stock_id'], 'exist', 'skipOnError' => true, 'targetClass' => Stock::class, 'targetAttribute' => ['stock_id' => 'id']],
        ];
    }

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

    public function fields()
    {
        return [
            'id',
            'delivery',
            'price',
            'product_price',
            'unit_price' => function () {
                return $this->getUnitPrice(); },
            'total_cost' => function () {
                return $this->getTotalCost(); },
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

    public function getOrder(): ActiveQuery
    {
        return $this->hasOne(Order::class, ['id' => 'order_id']);
    }

    public function getProduct(): ActiveQuery
    {
        return $this->hasOne(Product::class, ['id' => 'product_id']);
    }

    public function getOrderProductFilter(): ActiveQuery
    {
        return $this->hasMany(OrderProductFilter::class, ['order_product_id' => 'id']);
    }

    public function getRefund(): ActiveQuery
    {
        return $this->hasOne(OrderProductRefund::class, ['order_product_id' => 'id']);
    }

    public function getDelivery(): ActiveQuery
    {
        return $this->hasOne(Delivery::class, ['id' => 'delivery_id']);
    }

    public function getProductReview(): ActiveQuery
    {
        return $this->hasOne(\app\models\product\review\ProductReview::class, ['product_id' => 'product_id'])
            ->andOnCondition(['user_id' => $this->order->user_id]);
    }

    public function getProductReviews(): ActiveQuery
    {
        return $this->hasMany(\app\models\product\review\ProductReview::class, ['product_id' => 'product_id'])
            ->with('user');
    }

    public function hasReview(): bool
    {
        return $this->productReview !== null;
    }

    public function hasReviews(): bool
    {
        return \count($this->productReviews) > 0;
    }

    public function getStock(): ActiveQuery
    {
        return $this->hasOne(Stock::class, ['id' => 'stock_id']);
    }

    public function hasBtsIntegration(): bool
    {
        return !empty($this->bts_id);
    }

    public function getBtsStatusLabel(): string
    {
        return $this->bts_status_info ?? 'Unknown status';
    }

    public function getDeliveryAddress(): string
    {
        return $this->address ?? $this->order->address;
    }

    public function getTotalCost(): float
    {
        return ($this->price ?: 0);
    }

    public function getUnitPrice(): float
    {
        return $this->amount > 0 ? ($this->product_price ?: 0) / $this->amount : ($this->price ?: 0);
    }
}
