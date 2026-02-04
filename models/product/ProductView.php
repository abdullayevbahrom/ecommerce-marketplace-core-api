<?php

namespace app\models\product;

use Yii;

/**
 * This is the model class for table "product_view".
 *
 * @property int $id
 * @property int|null $product_id
 * @property string|null $ip
 * @property string $date
 *
 * @property Product $product
 */
class ProductView extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'product_view';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['product_id'], 'integer'],
            [['date'], 'safe'],
            [['ip'], 'string', 'max' => 255],
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
            'ip' => 'Ip',
            'date' => 'Date',
        ];
    }

    public function saveObject($product) {
        $this->product_id = $product->id;
        $this->ip = Yii::$app->request->userIP;
        if ($this->save()) {
            $product->views += 1;
            return $product->save(false);
        }

        return false;
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
