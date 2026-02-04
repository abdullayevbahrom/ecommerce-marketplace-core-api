<?php

namespace app\models\product;

use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "product_sync_request".
 *
 * @property int $id
 * @property int $sklad_id
 * @property int|null $sklad_shop_id
 * @property string $data JSON data
 * @property int $status 0=Pending, 1=Approved, 2=Rejected
 * @property string|null $moderator_comment
 * @property string $created_at
 * @property string $updated_at
 * @property string|null $processed_at
 * @property int|null $product_id
 */
class ProductSyncRequest extends ActiveRecord
{
    const STATUS_PENDING = 0;
    const STATUS_APPROVED = 1;
    const STATUS_REJECTED = 2;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'product_sync_request';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['sklad_id', 'data'], 'required'],
            [['sklad_id', 'sklad_shop_id', 'status', 'product_id'], 'integer'],
            [['data', 'moderator_comment', 'created_at', 'updated_at', 'processed_at'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'sklad_id' => 'Sklad ID',
            'sklad_shop_id' => 'Sklad Shop ID',
            'data' => 'Data',
            'status' => 'Status',
            'moderator_comment' => 'Moderator Comment',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'processed_at' => 'Processed At',
            'product_id' => 'Product ID',
        ];
    }
    
    public function getDecodedData()
    {
        return json_decode($this->data, true);
    }
}

