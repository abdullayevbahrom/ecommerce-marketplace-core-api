<?php

namespace app\models;

use app\components\RabbitMq\MessageFactory;
use app\components\RabbitMq\OutboxService;
use Yii;
use yii\db\ActiveQuery;

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
    public const STATUS_ACTIVE = 1;
    public const STATUS_INACTIVE = 0;
    public bool $suppressSyncEvents = false;

    public static function tableName()
    {
        return 'regions';
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        if ($this->suppressSyncEvents) {
            return;
        }

        if (!(bool) (Yii::$app->params['rabbitmq']['enable_reference_events'] ?? false)) {
            return;
        }

        $eventType = $insert ? 'region.created' : 'region.updated';

        (new OutboxService())->queue(
            exchange: 'market_to_sklad',
            routingKey: $eventType,
            eventType: $eventType,
            entityType: 'region',
            entityId: $this->id,
            source: 'market',
            branchId: null,
            message: MessageFactory::make(
                eventType: $eventType,
                source: 'market',
                entityType: 'region',
                entityId: $this->id,
                branchId: null,
                payload: $this->toSyncPayload(),
            )
        );
    }

    public function afterDelete()
    {
        parent::afterDelete();

        if ($this->suppressSyncEvents) {
            return;
        }

        if (!(bool) (Yii::$app->params['rabbitmq']['enable_reference_events'] ?? false)) {
            return;
        }

        (new OutboxService())->queue(
            exchange: 'market_to_sklad',
            routingKey: 'region.deleted',
            eventType: 'region.deleted',
            entityType: 'region',
            entityId: $this->id,
            source: 'market',
            branchId: null,
            message: MessageFactory::make(
                eventType: 'region.deleted',
                source: 'market',
                entityType: 'region',
                entityId: $this->id,
                branchId: null,
                payload: $this->toSyncPayload(),
            )
        );
    }

    public function rules()
    {
        return [
            [['bts_id', 'name_ru', 'name_uz', 'name_en'], 'required'],
            [['status'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['bts_id', 'name_ru', 'name_uz', 'name_en'], 'string', 'max' => 255],
            [['bts_id'], 'unique'],
        ];
    }

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

    public function getCities(): ActiveQuery
    {
        return $this->hasMany(City::class, ['region_id' => 'id']);
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

    protected function toSyncPayload(): array
    {
        return [
            'id' => $this->id,
            'yii_region_id' => $this->id,
            'name' => $this->name_ru,
            'name_ru' => $this->name_ru,
            'name_uz' => $this->name_uz,
            'name_en' => $this->name_en,
            'status' => $this->status,
            'selecting' => 0,
            'img' => null,
            'bts_id' => $this->bts_id,
        ];
    }
}
