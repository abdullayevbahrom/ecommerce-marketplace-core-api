<?php

namespace app\models\logist\region;

use Yii;
use app\models\Category;

/**
 * This is the model class for table "logist_region_price".
 *
 * @property int $id
 * @property int|null $logist_region_id
 * @property int|null $unit_id
 * @property float|null $unit_amount
 * @property float|null $price
 * @property string $date
 *
 * @property LogistRegion $logistRegion
 */
class LogistRegionPrice extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'logist_region_price';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['logist_region_id', 'unit_id'], 'integer'],
            [['unit_amount', 'price'], 'number'],
            [['date'], 'safe'],
            [['logist_region_id'], 'exist', 'skipOnError' => true, 'targetClass' => LogistRegion::className(), 'targetAttribute' => ['logist_region_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'logist_region_id' => 'Logist Region ID',
            'unit_id' => 'Unit ID',
            'unit_amount' => 'Unit Amount',
            'price' => 'Price',
            'date' => 'Date',
        ];
    }

    /**
     * Gets query for [[LogistRegion]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getLogistRegion()
    {
        return $this->hasOne(LogistRegion::className(), ['id' => 'logist_region_id']);
    }

    public function getUnit()
    {
        return $this->hasOne(Category::className(), ['id' => 'unit_id']);
    }
}
