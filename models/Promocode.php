<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use app\models\order\Order;
use app\models\user\User;

/**
 * This is the model class for table "promocode".
 *
 * @property int $id
 * @property string $code
 * @property int $type 1: Fixed Amount, 2: Percentage
 * @property float $value
 * @property float|null $min_order_amount
 * @property float|null $max_discount_amount
 * @property string|null $start_date
 * @property string|null $end_date
 * @property int|null $usage_limit
 * @property int $usage_limit_per_user
 * @property int $status
 * @property bool $is_first_order
 * @property int|null $category_id
 * @property int|null $product_id
 * @property int|null $user_id
 * @property string|null $title_ru
 * @property string|null $title_uz
 * @property string|null $title_en
 * @property string|null $description_ru
 * @property string|null $description_uz
 * @property string|null $description_en
 * @property string $created_at
 * @property string $updated_at
 */
class Promocode extends ActiveRecord
{
    const TYPE_FIXED = 1;
    const TYPE_PERCENT = 2;

    const STATUS_INACTIVE = 0;
    const STATUS_ACTIVE = 1;

    public $is_generator = false;
    public $target_group;
    public $generator_start_date;
    public $generator_end_date;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'promocode';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['code', 'value'], 'required'],
            [['type', 'usage_limit', 'usage_limit_per_user', 'status', 'category_id', 'product_id', 'user_id'], 'integer'],
            [['value', 'min_order_amount', 'max_discount_amount'], 'number'],
            [['start_date', 'end_date', 'created_at', 'updated_at'], 'safe'],
            [['is_first_order'], 'boolean'],
            [['description_ru', 'description_uz', 'description_en'], 'string'],
            [['code'], 'string', 'max' => 50],
            [['title_ru', 'title_uz', 'title_en'], 'string', 'max' => 255],
            [['code'], 'unique'],
            ['type', 'in', 'range' => [self::TYPE_FIXED, self::TYPE_PERCENT]],
            ['status', 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_INACTIVE]],
            
            // Generator fields
            [['is_generator'], 'boolean'],
            [['target_group'], 'string'],
            [['generator_start_date', 'generator_end_date'], 'safe'],
            
            // Validate generator fields if is_generator is true
            [['target_group', 'generator_start_date', 'generator_end_date'], 'required', 'when' => function($model) {
                return $model->is_generator == true;
            }, 'whenClient' => "function (attribute, value) {
                return $('#is-generator-checkbox').is(':checked');
            }"],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'code' => 'Code',
            'type' => 'Type',
            'value' => 'Value',
            'min_order_amount' => 'Min Order Amount',
            'max_discount_amount' => 'Max Discount Amount',
            'start_date' => 'Start Date',
            'end_date' => 'End Date',
            'usage_limit' => 'Usage Limit',
            'usage_limit_per_user' => 'Usage Limit Per User',
            'status' => 'Status',
            'is_first_order' => 'Is First Order',
            'category_id' => 'Category ID',
            'product_id' => 'Product ID',
            'user_id' => 'User ID',
            'title_ru' => 'Title Ru',
            'title_uz' => 'Title Uz',
            'title_en' => 'Title En',
            'description_ru' => 'Description Ru',
            'description_uz' => 'Description Uz',
            'description_en' => 'Description En',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * Check if the promo code is valid for the given user and order amount
     * 
     * @param User|null $user
     * @param float $orderAmount
     * @param array $cartItems Optional: array of cart items to check category/product restrictions
     * @return array [bool $isValid, string|null $error]
     */
    public function checkValidity($user, $orderAmount, $cartItems = [])
    {
        // 1. Check Status
        if ($this->status !== self::STATUS_ACTIVE) {
            return [false, 'Promocode is inactive'];
        }

        // 2. Check Date Range
        $now = date('Y-m-d H:i:s');
        if ($this->start_date && $this->start_date > $now) {
            return [false, 'Promocode is not active yet'];
        }
        if ($this->end_date && $this->end_date < $now) {
            return [false, 'Promocode has expired'];
        }

        // 3. Check Global Usage Limit
        if ($this->usage_limit !== null) {
            $usedCount = Order::find()->where(['promocode_id' => $this->id])->count();
            if ($usedCount >= $this->usage_limit) {
                return [false, 'Promocode usage limit reached'];
            }
        }

        // 4. Check Minimum Order Amount
        if ($this->min_order_amount > 0 && $orderAmount < $this->min_order_amount) {
            return [false, "Minimum order amount is {$this->min_order_amount}"];
        }

        // User-specific checks
        if ($user) {
            // 4.5 Check Personal Promocode Owner
            if ($this->user_id !== null && $this->user_id != $user->id) {
                return [false, 'This promocode is not valid for your account'];
            }

            // 5. Check Usage Limit Per User
            if ($this->usage_limit_per_user > 0) {
                $userUsedCount = Order::find()
                    ->where(['promocode_id' => $this->id, 'user_id' => $user->id])
                    ->count();
                
                if ($userUsedCount >= $this->usage_limit_per_user) {
                    return [false, 'You have already used this promocode'];
                }
            }

            // 6. Check First Order Requirement
            if ($this->is_first_order) {
                $hasOrders = Order::find()
                    ->where(['user_id' => $user->id])
                    ->exists();
                
                if ($hasOrders) {
                    return [false, 'This promocode is only for the first order'];
                }
            }
        }

        // 7. Check Category/Product Restrictions (if cart items provided)
        if (!empty($cartItems) && ($this->category_id || $this->product_id)) {
            $hasValidItem = false;
            foreach ($cartItems as $item) {
                // Assuming $item has 'product' relation
                $product = $item->product;
                if (!$product) continue;

                if ($this->product_id && $product->id == $this->product_id) {
                    $hasValidItem = true;
                    break;
                }
                
                if ($this->category_id && $product->category_id == $this->category_id) {
                    $hasValidItem = true;
                    break;
                }
            }
            
            if (!$hasValidItem) {
                return [false, 'Promocode is not applicable to items in your cart'];
            }
        }

        return [true, null];
    }

    /**
     * Calculate discount amount
     * 
     * @param float $orderAmount
     * @return float
     */
    public function calculateDiscount($orderAmount)
    {
        $discount = 0;

        if ($this->type == self::TYPE_FIXED) {
            $discount = $this->value;
        } elseif ($this->type == self::TYPE_PERCENT) {
            $discount = ($orderAmount * $this->value) / 100;
            
            // Apply max discount cap if set
            if ($this->max_discount_amount > 0 && $discount > $this->max_discount_amount) {
                $discount = $this->max_discount_amount;
            }
        }

        // Ensure discount doesn't exceed order amount
        return min($discount, $orderAmount);
    }

    public function getTitle()
    {
        $lang = Yii::$app->language; // e.g., 'ru', 'en', 'uz'
        // Fallback to 'ru' if current language field is empty
        $field = 'title_' . $lang;
        return $this->$field ?: $this->title_ru;
    }

    public function getDescription()
    {
        $lang = Yii::$app->language;
        $field = 'description_' . $lang;
        return $this->$field ?: $this->description_ru;
    }
}
