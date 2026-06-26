<?php

namespace app\models;

use Yii;
use yii\db\ActiveQuery;

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
    public const STATUS_ACTIVE = 1;
    public const STATUS_INACTIVE = 0;
    
    public static function tableName()
    {
        return 'cities';
    }

    public function rules()
    {
        return [
            [['bts_id', 'region_id', 'bts_region_id', 'name_ru', 'name_uz', 'name_en'], 'required'],
            [['region_id', 'status'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['bts_id', 'bts_region_id', 'name_ru', 'name_uz', 'name_en'], 'string', 'max' => 255],
            [['bts_id'], 'unique'],
            [['region_id'], 'exist', 'skipOnError' => true, 'targetClass' => Region::class, 'targetAttribute' => ['region_id' => 'id']],
        ];
    }

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

    public function getRegion(): ActiveQuery
    {
        return $this->hasOne(Region::class, ['id' => 'region_id']);
    }

    public function getName($language = 'ru')
    {
        return match ($language) {
            'uz' => $this->name_uz,
            'en' => $this->name_en,
            default => $this->name_ru,
        };
    }

    public static function getActive(): ActiveQuery
    {
        return static::find()->where(['status' => 1]);
    }

    public static function findByBtsId(string $btsId): ?static
    {
        return static::findOne(['bts_id' => $btsId]);
    }

    public static function getByRegion(int $regionId): ActiveQuery
    {
        return static::getActive()->where(['region_id' => $regionId]);
    }

    public static function getByBtsRegion(string $btsRegionId): ActiveQuery
    {
        return static::getActive()->where(['bts_region_id' => $btsRegionId]);
    }

    public static function search(string $searchTerm, string $language = 'ru', ?int $regionId = null): ActiveQuery
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