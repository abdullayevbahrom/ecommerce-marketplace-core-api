<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "cities".
 *
 * @property int $id
 * @property int $bts_id BTS system city ID
 * @property int $region_id Reference to regions table
 * @property int $bts_region_id BTS system region ID
 * @property string $name_ru Russian name
 * @property string $name_uz Uzbek name
 * @property string $name_en English name
 * @property int $status Status: 1=active, 0=inactive
 * @property string $created_at
 * @property string $updated_at
 *
 * @property Region $region
 */
class City extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'cities';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['bts_id', 'region_id', 'bts_region_id', 'name_ru', 'name_uz', 'name_en'], 'required'],
            [['bts_id', 'region_id', 'bts_region_id', 'status'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['name_ru', 'name_uz', 'name_en'], 'string', 'max' => 255],
            [['bts_id'], 'unique'],
            [['region_id'], 'exist', 'skipOnError' => true, 'targetClass' => Region::className(), 'targetAttribute' => ['region_id' => 'id']],
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
            'region_id' => 'Region ID',
            'bts_region_id' => 'BTS Region ID',
            'name_ru' => 'Name (Russian)',
            'name_uz' => 'Name (Uzbek)',
            'name_en' => 'Name (English)',
            'status' => 'Status',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * Gets query for [[Region]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRegion()
    {
        return $this->hasOne(Region::className(), ['id' => 'region_id']);
    }

    /**
     * Get city name by language
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
     * Get active cities
     * @return \yii\db\ActiveQuery
     */
    public static function getActive()
    {
        return static::find()->where(['status' => 1]);
    }

    /**
     * Find city by BTS ID
     * @param int $btsId
     * @return static|null
     */
    public static function findByBtsId($btsId)
    {
        return static::findOne(['bts_id' => $btsId]);
    }

    /**
     * Get cities by region
     * @param int $regionId
     * @param string $language
     * @return \yii\db\ActiveQuery
     */
    public static function getByRegion($regionId, $language = 'ru')
    {
        return static::getActive()->where(['region_id' => $regionId]);
    }

    /**
     * Get cities by BTS region ID
     * @param int $btsRegionId
     * @return \yii\db\ActiveQuery
     */
    public static function getByBtsRegion($btsRegionId)
    {
        return static::getActive()->where(['bts_region_id' => $btsRegionId]);
    }

    /**
     * Search cities by name
     * @param string $searchTerm
     * @param string $language
     * @param int|null $regionId
     * @return \yii\db\ActiveQuery
     */
    public static function search($searchTerm, $language = 'ru', $regionId = null)
    {
        $query = static::getActive();
        
        $nameField = 'name_' . $language;
        $query->andWhere(['like', $nameField, $searchTerm]);
        
        if ($regionId) {
            $query->andWhere(['region_id' => $regionId]);
        }
        
        return $query;
    }

    public function fields()
    {
        return [
            'id',
            'bts_id',
            'region_id',
            'bts_region_id',
            'name_ru',
            'name_uz',
            'name_en',
            'status',
            'region'
        ];
    }
}