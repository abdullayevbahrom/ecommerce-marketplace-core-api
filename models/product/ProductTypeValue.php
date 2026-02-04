<?php

namespace app\models\product;

use Yii;

/**
 * This is the model class for table "product_type_value".
 *
 * @property int $id
 * @property int|null $product_type_id
 * @property string|null $value_ru
 * @property string|null $value_en
 * @property string|null $value_uz
 * @property string|null $display_value
 * @property string|null $description_ru
 * @property string|null $description_en
 * @property string|null $description_uz
 * @property int $status
 * @property int $sort
 * @property string $date
 *
 * @property ProductType $productType
 * @property ProductProductType[] $productProductTypes
 */
class ProductTypeValue extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'product_type_value';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['product_type_id', 'value_ru'], 'required', 'message'=>'Заполните поле'],
            [['product_type_id', 'status', 'sort'], 'integer'],
            [['description_ru', 'description_en', 'description_uz'], 'string'],
            [['date'], 'safe'],
            [['value_ru', 'value_en', 'value_uz', 'display_value'], 'string', 'max' => 255],
            [['product_type_id'], 'exist', 'skipOnError' => true, 'targetClass' => ProductType::className(), 'targetAttribute' => ['product_type_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'product_type_id' => 'Product Type ID',
            'value_ru' => 'Value (RU)',
            'value_en' => 'Value (EN)',
            'value_uz' => 'Value (UZ)',
            'display_value' => 'Display Value',
            'description_ru' => 'Description (RU)',
            'description_en' => 'Description (EN)',
            'description_uz' => 'Description (UZ)',
            'status' => 'Status',
            'sort' => 'Sort Order',
            'date' => 'Date',
        ];
    }

    public function fields() {
        $headers = Yii::$app->request->headers;
        $language = $headers->has('Content-Language') ? $headers->get('Content-Language') : 'ru';

        return [
            'id',
            'value' => function() use($language) {
                return $this->{'value_'.$language} ? $this->{'value_'.$language} : $this->value_ru;
            },
            'display_value' => function() {
                return $this->display_value ? $this->display_value : $this->value_ru;
            },
            'description' => function() use($language) {
                return $this->{'description_'.$language} ? $this->{'description_'.$language} : $this->description_ru;
            },
            'sort'
        ];
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
     * Gets query for [[ProductProductTypes]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getProductProductTypes()
    {
        return $this->hasMany(ProductProductType::className(), ['product_type_value_id' => 'id']);
    }
} 