<?php

namespace app\models\product;

use Yii;
use app\models\filter\Filter;

/**
 * This is the model class for table "product_filter".
 *
 * @property int $id
 * @property int|null $product_id
 * @property int|null $filter_id
 * @property string|null $value_ru
 * @property string|null $value_en
 * @property string|null $value_uz
 *
 * @property Product $product
 */
class ProductFilter extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'product_filter';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['product_id', 'filter_id'], 'integer'],
            [['value_ru', 'value_en', 'value_uz'], 'string', 'max' => 255],
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
            'filter_id' => 'Filter ID',
            'value_ru' => 'Value Ru',
            'value_en' => 'Value En',
            'value_uz' => 'Value Uz',
        ];
    }

    public function fields() {
        return ['id', 'name'=>function(){return $this->filter->name_ru;}, 'value_ru'];
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

    // filter
    public function getFilter()
    {
        return $this->hasOne(Filter::className(), ['id' => 'filter_id']);
    }
}
