<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "regions".
 *
 * @property int $id
 * @property int $bts_id BTS system region ID
 * @property string $name_ru Russian name
 * @property string $name_uz Uzbek name
 * @property string $name_en English name
 * @property int $status Status: 1=active, 0=inactive
 * @property string $created_at
 * @property string $updated_at
 *
 * @property City[] $cities
 */
class Region extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'regions';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['bts_id', 'name_ru', 'name_uz', 'name_en'], 'required'],
            [['bts_id', 'status'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['name_ru', 'name_uz', 'name_en'], 'string', 'max' => 255],
            [['bts_id'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'bts_id' => 'BTS ID',
            'name_ru' => 'Name (Russian)',
            'name_uz' => 'Name (Uzbek)',
            'name_en' => 'Name (English)',
            'status' => 'Status',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * Gets query for [[Cities]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCities()
    {
        return $this->hasMany(City::className(), ['region_id' => 'id']);
    }

    /**
     * Get region name by language
     * @param string $language
     * @return string
     */
    public function getName($language = 'ru')
    {
        switch ($language) {
            case 'uz':
                return $this->name_uz;
            case 'en':
                return $this->name_en;
            default:
                return $this->name_ru;
        }
    }

    /**
     * Get active regions
     * @return \yii\db\ActiveQuery
     */
    public static function getActive()
    {
        return static::find()->where(['status' => 1]);
    }

    /**
     * Find region by BTS ID
     * @param int $btsId
     * @return static|null
     */
    public static function findByBtsId($btsId)
    {
        return static::findOne(['bts_id' => $btsId]);
    }

    public function fields()
    {
        return [
            'id',
            'bts_id',
            'name_ru',
            'name_uz', 
            'name_en',
            'status'
        ];
    }
}