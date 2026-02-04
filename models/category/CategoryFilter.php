<?php

namespace app\models\category;

use Yii;
use app\models\Category;
use app\models\filter\Filter;

/**
 * This is the model class for table "category_filter".
 *
 * @property int $id
 * @property int|null $category_id
 * @property int|null $filter_id
 * @property int|null $value_id
 * @property string|null $value_ru
 * @property string|null $value_en
 * @property string|null $value_uz
 *
 * @property Category $category
 */
class CategoryFilter extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'category_filter';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['category_id', 'filter_id', 'value_id'], 'integer'],
            [['value_ru', 'value_en', 'value_uz'], 'string', 'max' => 255],
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
            'filter_id' => 'Filter ID',
            'value_id' => 'Value ID',
            'value_ru' => 'Value Ru',
            'value_en' => 'Value En',
            'value_uz' => 'Value Uz',
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

    // filter
    public function getFilter()
    {
        return $this->hasOne(Filter::className(), ['id' => 'filter_id']);
    }
}
