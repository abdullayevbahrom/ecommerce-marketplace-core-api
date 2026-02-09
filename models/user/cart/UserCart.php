<?php

namespace app\models\user\cart;

use Yii;
use app\models\product\Product;
use app\models\user\User;
use app\models\delivery\Delivery;
use yii\services\BTS;

/**
 * This is the model class for table "user_cart".
 *
 * @property int $id
 * @property int|null $user_id
 * @property int|null $product_id
 * @property float|null $amount
 * @property float|null $price
 * @property string $date
 *
 * @property Product $product
 * @property User $user
 */
class UserCart extends \yii\db\ActiveRecord
{
    public $filter_value_id = [];
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user_cart';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['product_id'], 'required', 'message'=>'Fill in the field'],
            ['product_id', 'checkProduct'],
            ['product_id', 'checkUserBtsLocation'],
            [['user_id', 'product_id', 'delivery_id'], 'integer'],
            [['amount', 'price'], 'number', 'min'=>1],
            [['delivery_cost'], 'number'],
            ['amount', 'checkAmount'],
            ['amount', 'checkMinOrder'],
            [['date', 'filter_value_id'], 'safe'],
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
            'amount' => 'Amount',
            'price' => 'Price',
            'date' => 'Date',
        ];
    }

    // check product
    public function checkProduct($attribute, $params) {
        if (!$this->hasErrors()) {
            $product = Product::findOne($this->product_id);
            if (!$product) {
                return $this->addError($attribute, 'This product does not exist');
            }
        }

        return false;
    }

    /**
     * Check if user has BTS city set for delivery calculation
     */
    public function checkUserBtsLocation($attribute, $params) {
        if (!$this->hasErrors() && Yii::$app->user->identity) {
            $user = Yii::$app->user->identity;
            if (empty($user->bts_city_id)) {
                return $this->addError($attribute, 'Please set your location in profile to calculate delivery cost');
            }
        }

        return false;
    }

    // check amount
    public function checkAmount($attribute, $params) {
        if (!$this->hasErrors()) {
            $product = Product::findOne($this->product_id);
            if ($product && ($product->amount < $this->amount)) {
                return $this->addError($attribute, 'The quantity of goods in stock is less than you indicated');
            }
        }

        return false;
    }

    // check minimum order quantity
    public function checkMinOrder($attribute, $params) {
        if (!$this->hasErrors()) {
            $product = Product::findOne($this->product_id);
            if ($product && $product->min_order && ($this->amount < $product->min_order)) {
                return $this->addError($attribute, 'Minimum order quantity for this product is ' . $product->min_order);
            }
        }

        return false;
    }

    public function saveObject() {
        $product = Product::find()->with(['stock', 'shop.stock'])->where(['id' => $this->product_id])->one();
        $user = Yii::$app->user->identity;

        $user_cart = UserCart::findOne(['user_id'=>$user->getId(), 'product_id'=>$this->product_id]);
        if ($user_cart) {
            $user_cart->amount += $this->amount;
            
            // Check minimum order quantity for updated amount
            if ($product->min_order && ($user_cart->amount < $product->min_order)) {
                $this->addError('amount', 'Minimum order quantity for this product is ' . $product->min_order);
                return false;
            }
            
            // Calculate price based on quantity and wholesale tiers
            $unit_price = $product->getPriceByQuantity($user_cart->amount);
            $user_cart->price = ($user_cart->amount * $unit_price);
            $user_cart->delivery_id = $this->delivery_id;
            
            // Calculate BTS delivery cost
            $user_cart->delivery_cost = $this->calculateBtsDeliveryCost($product, $user, $user_cart->amount);
            
            $user_cart->save();

            return $user_cart;
        }

        // Set delivery_id if provided
        if ($this->delivery_id) {
            // delivery_id is already set, no need to reassign
        }
        // Calculate price based on quantity and wholesale tiers
        $unit_price = $product->getPriceByQuantity($this->amount);
        $this->price = ($this->amount * $unit_price);
        $this->user_id = $user->getId();
        
        // Calculate BTS delivery cost
        $this->delivery_cost = $this->calculateBtsDeliveryCost($product, $user, $this->amount);
        
        if ($this->save()) {
            if ($this->filter_value_id) {
                $keys = ['user_cart_id', 'product_filter_id'];
                $vals = [];
                foreach ($this->filter_value_id as $value) {
                    $vals[] = [
                        'user_cart_id' => $this->id,
                        'product_filter_id' => $value
                    ];
                }

                Yii::$app->db->createCommand()->batchInsert('user_cart_filter', $keys, $vals)->execute();
            }
        }

        return $this;
    }

    /**
     * Calculate BTS delivery cost from product stock to user location
     * @param Product $product
     * @param User $user
     * @param int $amount
     * @return float|null
     */
    public function calculateBtsDeliveryCost($product, $user, $amount = 1) {
        // Check if user has BTS city set
        if (empty($user->bts_city_id)) {
            Yii::warning('User BTS city not set for delivery calculation', __METHOD__);
            return 0.0; // Return 0 instead of failing
        }

        // Get sender city ID from product's stock location
        $senderCityId = $this->getProductStockCityId($product);
        
        if (!$senderCityId) {
            Yii::warning('Product stock BTS city not configured for delivery calculation', __METHOD__);
            return 0.0; // Return 0 instead of failing
        }

        // Skip calculation if same city (no delivery needed)
        if ($senderCityId == $user->bts_city_id) {
            return 0.0;
        }

        try {
            // Calculate total weight based on quantity in cart
            $totalWeight = $this->calculateTotalWeight($product, $amount);
            
            // Prepare calculation data
            $calculatorData = [
                'senderCityId' => (int)$senderCityId,
                'receiverCityId' => (int)$user->bts_city_id,
                'weight' => max(4.0, $totalWeight), // Use total weight with 4kg minimum
                'senderDelivery' => 2, // Default BTS delivery method
                'receiverDelivery' => 2, // Default BTS delivery method
            ];

            // Calculate total volume based on quantity in cart
            if ($product->length && $product->width && $product->height) {
                $unitVolume = ($product->length * $product->width * $product->height) / 1000000; // Convert to cubic meters
                $calculatorData['volume'] = $unitVolume * $this->amount; // Multiply by quantity
            }

            // Calculate delivery cost using BTS service
            $bts = new BTS();
            $response = $bts->calculateDelivery($calculatorData);

            if ($response && isset($response['success']) && $response['success'] && isset($response['data']['summaryPrice'])) {
                return (float)$response['data']['summaryPrice'];
            }

            // TODO: Remove this mock fallback once BTS service is stable and reliable
            Yii::warning('BTS delivery calculation failed, using mock estimate. Response: ' . json_encode($response), __METHOD__);
            $baseCost = 25000;
            $weightCost = max(1.0, $totalWeight) * 5000;
            return (float)($baseCost + $weightCost);
            // END TODO: Remove mock fallback
            
        } catch (\Exception $e) {
            Yii::error('BTS delivery calculation error: ' . $e->getMessage(), __METHOD__);
            return 0.0;
        }
    }

    /**
     * Get product stock BTS city ID
     * @param Product $product
     * @return int|null
     */
    protected function getProductStockCityId($productWithStock) {
        if (!$productWithStock) {
            return null;
        }

        // Priority: product's direct stock, then shop's stock
        if ($productWithStock->stock && $productWithStock->stock->bts_city_id) {
            return $productWithStock->stock->bts_city_id;
        }
        
        if ($productWithStock->shop && $productWithStock->shop->stock && $productWithStock->shop->stock->bts_city_id) {
            return $productWithStock->shop->stock->bts_city_id;
        }

        return null;
    }

    /**
     * Calculate total weight for the product including current cart quantity
     * @param Product $product
     * @return float
     */
    protected function calculateTotalWeight($product, $amount) {
        // Get product weight (fallback to 1kg if not set)
        $unitWeight = (float)$product->weight ?: 1.0;
        
        // Return total weight (unit weight * total quantity)
        return $unitWeight * $amount;
    }

    public function getProductFilter() {
        $data = [];

        if ($this->cartFilter) {
            foreach ($this->cartFilter as $filter) {
                if ($filter->productFilter) {
                    $data[] = $filter->productFilter;
                }
            }
        }

        return $data;
    }

    public function fields() {
        return [
            'amount', 
            'delivery', 
            'amount_left'=>function(){return $this->product->amount - $this->amount;}, 
            'price',
            'unit_price'=>function(){return $this->product->getPriceByQuantity($this->amount);},
            'delivery_cost',
            'total_with_delivery'=>function(){return $this->price + ($this->delivery_cost ?: 0);},
            'product', 
            'productFilter'
        ];
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

    public function getCartFilter()
    {
        return $this->hasMany(UserCartFilter::className(), ['user_cart_id' => 'id']);
    }

    // delivery
    public function getDelivery()
    {
        return $this->hasOne(Delivery::className(), ['id' => 'delivery_id']);
    }
}
