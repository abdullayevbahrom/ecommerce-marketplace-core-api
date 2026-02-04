<?php

namespace app\models\color;

use Yii;

/**
 * This is the model class for table "color".
 *
 * @property int $id
 * @property string|null $name_ru
 * @property string|null $name_en
 * @property string|null $name_uz
 * @property string|null $color
 * @property string $date
 *
 * @property ProductColor[] $productColors
 */
class Color extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'color';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name_ru', 'color'], 'required', 'message'=>'Заполните поле'],
            [['date'], 'safe'],
            [['name_ru', 'name_en', 'name_uz', 'color'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name_ru' => 'Name Ru',
            'name_en' => 'Name En',
            'name_uz' => 'Name Uz',
            'color' => 'Color',
            'date' => 'Date',
        ];
    }

    public function fields() {
        $headers = Yii::$app->request->headers;
        $language = $headers->has('Content-Language') ? $headers->get('Content-Language') : 'ru';

        $data = [
            'id',
            'name' => function() use($language) {return $this->{'name_'.$language} ? $this->{'name_'.$language} : $this->name_ru;},
            'color'
        ];

        return $data;
    }

    /**
     * Gets query for [[ProductColors]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getProductColors()
    {
        return $this->hasMany(ProductColor::className(), ['color_id' => 'id']);
    }
}
