<?php

namespace app\models\product;

use Yii;
use app\models\Images;
use app\models\color\Color;

/**
 * This is the model class for table "product_color".
 *
 * @property int $id
 * @property int|null $product_id
 * @property int|null $color_id
 * @property string $date
 * @property int|null $status
 *
 * @property Color $color
 * @property Product $product
 */
class ProductColor extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'product_color';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['product_id', 'color_id', 'status'], 'integer'],
            [['date'], 'safe'],
            [['color_id'], 'exist', 'skipOnError' => true, 'targetClass' => Color::className(), 'targetAttribute' => ['color_id' => 'id']],
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
            'color_id' => 'Color ID',
            'date' => 'Date',
            'status' => 'Status',
        ];
    }

    public function getPhoto()
    {
        if ($this->image && $this->image->photo) {
            return $this->image->getPhoto('color');
        }

        return Images::PHOTO_DEFAULT;
    }

    public function fields()
    {
        $headers = Yii::$app->request->headers;
        $language = $headers->has('Content-Language') ? $headers->get('Content-Language') : 'ru';

        return [
            'id',
            'name' => function () use ($language) {
                return $this->color && $this->color->{'name_' . $language} ? $this->color->{'name_' . $language} : $this->color->name_ru;
            },
            'color' => function () {
                return $this->color ? $this->color->color : '';
            },
            'photo' => function () {
                return $this->getPhoto();
            },
            'status',
            'date'
        ];
    }

    /**
     * Gets query for [[Color]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getColor()
    {
        return $this->hasOne(Color::className(), ['id' => 'color_id']);
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

    public function getImage()
    {
        return $this->hasOne(Images::className(), ['object_id' => 'id'])->andOnCondition(['type' => 'color']);
    }
}
