<?php

namespace app\models\office;

use Yii;

/**
 * This is the model class for table "office".
 *
 * @property int $id
 * @property string|null $name
 * @property string $date
 *
 * @property ProductOffice[] $productOffices
 */
class Office extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'office';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['date'], 'safe'],
            [['name', 'office_id'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'date' => 'Date',
        ];
    }

    /**
     * Gets query for [[ProductOffices]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getProductOffices()
    {
        return $this->hasMany(ProductOffice::className(), ['office_id' => 'id']);
    }
}
