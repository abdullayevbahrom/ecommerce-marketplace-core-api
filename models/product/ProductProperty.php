<?php

namespace app\models\product;

use Yii;

/**
 * This is the model class for table "product_property".
 *
 * @property int $id
 * @property int|null $product_id
 * @property string|null $key_name
 * @property string|null $value_name
 * @property string $date
 *
 * @property Product $product
 */
class ProductProperty extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'product_property';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['product_id'], 'integer'],
            [['date'], 'safe'],
            [['key_name', 'value_name'], 'string', 'max' => 255],
            [['product_id'], 'exist', 'skipOnError' => true, 'targetClass' => Product::className(), 'targetAttribute' => ['product_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'product_id' => 'Product ID',
            'key_name' => 'Key Name',
            'value_name' => 'Value Name',
            'date' => 'Date',
        ];
    }

    public function fields() {
        return ['key_name', 'value_name'];
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
}
