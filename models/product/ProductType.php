<?php

namespace app\models\product;

use Yii;
use app\models\Category;

/**
 * This is the model class for table "product_type".
 *
 * @property int $id
 * @property int|null $category_id
 * @property string|null $name_ru
 * @property string|null $name_en
 * @property string|null $name_uz
 * @property string|null $type
 * @property string|null $description_ru
 * @property string|null $description_en
 * @property string|null $description_uz
 * @property int $status
 * @property int $sort
 * @property string $date
 *
 * @property Category $category
 * @property ProductTypeValue[] $productTypeValues
 * @property ProductProductType[] $productProductTypes
 */
class ProductType extends \yii\db\ActiveRecord
{
    public bool $suppressSyncEvents = false;

    const TYPE_INPUT = 'input';
    const TYPE_SELECT = 'select';
    const TYPE_CHECKBOX = 'checkbox';
    const TYPE_RANGE = 'range';

    const STATUS_ACTIVE = 1;
    const STATUS_INACTIVE = 2;
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'product_type';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name_ru', 'type'], 'required', 'message'=>'Заполните поле'],
            [['category_id', 'status', 'sort'], 'integer'],
            [['description_ru', 'description_en', 'description_uz'], 'string'],
            [['date'], 'safe'],
            [['name_ru', 'name_en', 'name_uz', 'type'], 'string', 'max' => 255],
            [['type'], 'in', 'range' => [self::TYPE_INPUT, self::TYPE_SELECT, self::TYPE_CHECKBOX, self::TYPE_RANGE]],
            [['category_id'], 'exist', 'skipOnError' => true, 'targetClass' => Category::className(), 'targetAttribute' => ['category_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'category_id' => 'Category ID',
            'name_ru' => 'Name (RU)',
            'name_en' => 'Name (EN)',
            'name_uz' => 'Name (UZ)',
            'type' => 'Type',
            'description_ru' => 'Description (RU)',
            'description_en' => 'Description (EN)',
            'description_uz' => 'Description (UZ)',
            'status' => 'Status',
            'sort' => 'Sort Order',
            'date' => 'Date',
        ];
    }

    /**
     * Get type options for dropdowns
     */
    public static function getTypeOptions()
    {
        return [
            self::TYPE_INPUT => 'Text Input',
            self::TYPE_SELECT => 'Dropdown Select',
            self::TYPE_CHECKBOX => 'Multiple Choice',
            self::TYPE_RANGE => 'Range (Min-Max)',
        ];
    }

    public function fields() {
        $headers = Yii::$app->request->headers;
        $language = $headers->has('Content-Language') ? $headers->get('Content-Language') : 'ru';

        return [
            'id',
            'name' => function() use($language) {
                return $this->{'name_'.$language} ? $this->{'name_'.$language} : $this->name_ru;
            },
            'type',
            'description' => function() use($language) {
                return $this->{'description_'.$language} ? $this->{'description_'.$language} : $this->description_ru;
            },
            'values' => function() {
                return $this->productTypeValues;
            },
            'category'
        ];
    }

    /**
     * Gets query for [[Category]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCategory()
    {
        return $this->hasOne(Category::className(), ['id' => 'category_id']);
    }

    /**
     * Gets query for [[ProductTypeValues]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getProductTypeValues()
    {
        return $this->hasMany(ProductTypeValue::className(), ['product_type_id' => 'id'])->orderBy('sort ASC');
    }

    /**
     * Gets query for [[ProductProductTypes]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getProductProductTypes()
    {
        return $this->hasMany(ProductProductType::className(), ['product_type_id' => 'id']);
    }
} 
