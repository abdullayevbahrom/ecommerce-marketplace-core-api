<?php

namespace app\models\product;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "pending_products".
 * Stores product submissions from Sklad for moderation.
 *
 * @property int $id
 * @property int $warehouse_product_id ID from Sklad pending_products table
 * @property int $branch_id Sklad branch ID
 * @property string|null $branch_name
 * @property int|null $branch_yii_stock_id Shop stock_id if synced
 * @property int|null $merchant_id Merchant ID in Sklad
 * @property string|null $merchant_name
 * @property string|null $name Main product name
 * @property string|null $name_ru
 * @property string|null $name_en
 * @property string|null $name_uz
 * @property string|null $description_ru
 * @property string|null $description_en
 * @property string|null $description_uz
 * @property float|null $price
 * @property float|null $price_small
 * @property float|null $price_opt
 * @property float|null $amount
 * @property string|null $barcode
 * @property string|null $sku
 * @property float|null $weight
 * @property float|null $discount
 * @property string $data JSON payload
 * @property string $status pending/approved/rejected
 * @property string|null $callback_url Webhook URL
 * @property string|null $moderator_comment
 * @property int|null $moderator_id
 * @property int|null $approved_product_id
 * @property string $created_at
 * @property string $updated_at
 * @property string|null $processed_at
 */
class PendingProduct extends ActiveRecord
{
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'pending_products';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'value' => new Expression('NOW()'),
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['warehouse_product_id', 'branch_id', 'data'], 'required'],
            [['warehouse_product_id', 'branch_id', 'branch_yii_stock_id', 'merchant_id', 'moderator_id', 'approved_product_id'], 'integer'],
            [['price', 'price_small', 'price_opt', 'amount', 'weight', 'discount'], 'number'],
            [['description_ru', 'description_en', 'description_uz', 'data', 'moderator_comment'], 'safe'],
            [['branch_name', 'merchant_name', 'name', 'name_ru', 'name_en', 'name_uz'], 'string', 'max' => 255],
            [['barcode', 'sku'], 'string', 'max' => 100],
            [['callback_url'], 'string', 'max' => 500],
            [['status'], 'string', 'max' => 20],
            ['status', 'default', 'value' => self::STATUS_PENDING],
            ['status', 'in', 'range' => [self::STATUS_PENDING, self::STATUS_APPROVED, self::STATUS_REJECTED]],
            [['warehouse_product_id', 'branch_id'], 'unique', 'targetAttribute' => ['warehouse_product_id', 'branch_id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'warehouse_product_id' => 'Warehouse Product ID',
            'branch_id' => 'Branch ID',
            'branch_name' => 'Branch Name',
            'branch_yii_stock_id' => 'Shop Stock ID',
            'merchant_id' => 'Merchant ID',
            'merchant_name' => 'Merchant Name',
            'name' => 'Name',
            'name_ru' => 'Name (RU)',
            'name_en' => 'Name (EN)',
            'name_uz' => 'Name (UZ)',
            'description_ru' => 'Description (RU)',
            'description_en' => 'Description (EN)',
            'description_uz' => 'Description (UZ)',
            'price' => 'Price',
            'price_small' => 'Small Wholesale Price',
            'price_opt' => 'Wholesale Price',
            'amount' => 'Quantity',
            'barcode' => 'Barcode',
            'sku' => 'SKU',
            'weight' => 'Weight (g)',
            'discount' => 'Discount %',
            'data' => 'Full Data',
            'status' => 'Status',
            'callback_url' => 'Callback URL',
            'moderator_comment' => 'Moderator Comment',
            'moderator_id' => 'Moderator',
            'approved_product_id' => 'Approved Product',
            'created_at' => 'Submitted At',
            'updated_at' => 'Updated At',
            'processed_at' => 'Processed At',
        ];
    }
    
    /**
     * Decode the stored JSON data
     * @return array
     */
    public function getDecodedData()
    {
        return json_decode($this->data, true) ?: [];
    }

    /**
     * Get the product data from decoded JSON
     * @return array
     */
    public function getProductData()
    {
        $data = $this->getDecodedData();
        return $data['product'] ?? [];
    }

    /**
     * Get references (colors, etc.) from decoded JSON
     * @return array
     */
    public function getReferencesData()
    {
        $data = $this->getDecodedData();
        return $data['references'] ?? [];
    }

    /**
     * Get product types from decoded JSON
     * @return array
     */
    public function getProductTypesData()
    {
        $data = $this->getDecodedData();
        return $data['product_types'] ?? [];
    }

    /**
     * Get properties from decoded JSON
     * @return array
     */
    public function getPropertiesData()
    {
        $data = $this->getDecodedData();
        return $data['properties'] ?? [];
    }

    /**
     * Get filters from decoded JSON
     * @return array
     */
    public function getFiltersData()
    {
        $data = $this->getDecodedData();
        return $data['filters'] ?? [];
    }

    /**
     * Relation to moderator user
     */
    public function getModerator()
    {
        return $this->hasOne(\app\models\user\User::class, ['id' => 'moderator_id']);
    }

    /**
     * Relation to approved product
     */
    public function getApprovedProduct()
    {
        return $this->hasOne(Product::class, ['id' => 'approved_product_id']);
    }

    /**
     * Relation to moderation comments
     */
    public function getModerationComments()
    {
        return $this->hasMany(ProductModerationComment::class, ['submission_id' => 'id'])
            ->orderBy(['created_at' => SORT_DESC]);
    }

    /**
     * Get submission ID in format expected by spec
     * @return string
     */
    public function getSubmissionId()
    {
        return 'shop_sub_' . $this->id;
    }

    /**
     * Status label for display
     * @return string
     */
    public function getStatusLabel()
    {
        $labels = [
            self::STATUS_PENDING => 'На модерации',
            self::STATUS_APPROVED => 'Одобрено',
            self::STATUS_REJECTED => 'Отклонено',
        ];
        return $labels[$this->status] ?? $this->status;
    }

    /**
     * Status badge CSS class
     * @return string
     */
    public function getStatusBadgeClass()
    {
        $classes = [
            self::STATUS_PENDING => 'badge-warning',
            self::STATUS_APPROVED => 'badge-success',
            self::STATUS_REJECTED => 'badge-danger',
        ];
        return $classes[$this->status] ?? 'badge-secondary';
    }
}
