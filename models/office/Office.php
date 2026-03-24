<?php

namespace app\models\office;

use app\components\RabbitMq\MessageFactory;
use app\components\RabbitMq\OutboxService;
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
    public bool $suppressSyncEvents = false;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'office';
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

        $eventType = $insert ? 'office.created' : 'office.updated';

        (new OutboxService())->queue(
            exchange: 'market_to_sklad',
            routingKey: $eventType,
            eventType: $eventType,
            entityType: 'office',
            entityId: $this->id,
            source: 'market',
            branchId: null,
            message: MessageFactory::make(
                eventType: $eventType,
                source: 'market',
                entityType: 'office',
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
            routingKey: 'office.deleted',
            eventType: 'office.deleted',
            entityType: 'office',
            entityId: $this->id,
            source: 'market',
            branchId: null,
            message: MessageFactory::make(
                eventType: 'office.deleted',
                source: 'market',
                entityType: 'office',
                entityId: $this->id,
                branchId: null,
                payload: $this->toSyncPayload(),
            )
        );
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

    protected function toSyncPayload(): array
    {
        return [
            'id' => $this->id,
            'yii_office_id' => $this->id,
            'name' => $this->name,
            'address' => $this->office_id ?? null,
            'status' => 1,
        ];
    }
}
