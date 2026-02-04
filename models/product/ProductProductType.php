<?php

namespace app\models\product;

use Yii;

/**
 * This is the model class for table "product_product_type".
 *
 * @property int $id
 * @property int|null $product_id
 * @property int|null $product_type_id
 * @property int|null $product_type_value_id
 * @property string|null $custom_value
 * @property string $date
 *
 * @property Product $product
 * @property ProductType $productType
 * @property ProductTypeValue $productTypeValue
 */
class ProductProductType extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'product_product_type';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['product_id', 'product_type_id'], 'required', 'message'=>'Заполните поле'],
            [['product_id', 'product_type_id', 'product_type_value_id'], 'integer'],
            [['date'], 'safe'],
            [['custom_value'], 'string', 'max' => 255],
            [['product_id'], 'exist', 'skipOnError' => true, 'targetClass' => Product::className(), 'targetAttribute' => ['product_id' => 'id']],
            [['product_type_id'], 'exist', 'skipOnError' => true, 'targetClass' => ProductType::className(), 'targetAttribute' => ['product_type_id' => 'id']],
            [['product_type_value_id'], 'exist', 'skipOnError' => true, 'targetClass' => ProductTypeValue::className(), 'targetAttribute' => ['product_type_value_id' => 'id']],
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
            'product_type_id' => 'Product Type ID',
            'product_type_value_id' => 'Product Type Value ID',
            'custom_value' => 'Custom Value',
            'date' => 'Date',
        ];
    }

    public function fields() {
        return [
            'id',
            'name' => function() {
                $headers = Yii::$app->request->headers;
                $language = $headers->has('Content-Language') ? $headers->get('Content-Language') : 'ru';
                
                if ($this->productType) {
                    return $this->productType->{'name_'.$language} ?: $this->productType->name_ru;
                }
                return null;
            },
            'display_value' => function() {
                if ($this->custom_value) {
                    return $this->custom_value;
                } elseif ($this->productTypeValue) {
                    return $this->productTypeValue->display_value ?: $this->productTypeValue->value_ru;
                }
                return null;
            }
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
     * Gets query for [[ProductType]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getProductType()
    {
        return $this->hasOne(ProductType::className(), ['id' => 'product_type_id']);
    }

    /**
     * Gets query for [[ProductTypeValue]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getProductTypeValue()
    {
        return $this->hasOne(ProductTypeValue::className(), ['id' => 'product_type_value_id']);
    }
} 