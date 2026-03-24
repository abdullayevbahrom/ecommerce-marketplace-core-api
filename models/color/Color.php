<?php

namespace app\models\color;

use app\components\RabbitMq\MessageFactory;
use app\components\RabbitMq\OutboxService;
use app\models\product\ProductColor;
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
 * @property int $status
 * 
 * @property ProductColor[] $productColors
 */
class Color extends \yii\db\ActiveRecord
{
    public bool $suppressSyncEvents = false;

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

    const STATUS_ACTIVE = 1;
    const STATUS_INACTIVE = 2;

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
            'status' => 'Status',
            'date' => 'Date',

        ];
    }

    public function fields() {
        $headers = Yii::$app->request->headers;
        $language = $headers->has('Content-Language') ? $headers->get('Content-Language') : 'ru';

        $data = [
            'id',
            'name' => function() use($language) {return $this->{'name_'.$language} ? $this->{'name_'.$language} : $this->name_ru;},
            'color',
            'status'
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

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        if ($this->suppressSyncEvents) {
            return;
        }

        if (!(bool) (Yii::$app->params['rabbitmq']['enable_reference_events'] ?? false)) {
            return;
        }

        $eventType = $insert ? 'color.created' : 'color.updated';

        $message = MessageFactory::make(
            eventType: $eventType,
            source: 'market',
            entityType: 'color',
            entityId: $this->id,
            branchId: null,
            payload: $this->toSyncPayload(),
        );

        (new OutboxService())->queue(
            exchange: 'market_to_sklad',
            routingKey: $eventType,
            eventType: $eventType,
            entityType: 'color',
            entityId: $this->id,
            source: 'market',
            branchId: null,
            message: $message
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

        $message = MessageFactory::make(
            eventType: 'color.deleted',
            source: 'market',
            entityType: 'color',
            entityId: $this->id,
            branchId: null,
            payload: $this->toSyncPayload(),
        );

        (new OutboxService())->queue(
            exchange: 'market_to_sklad',
            routingKey: 'color.deleted',
            eventType: 'color.deleted',
            entityType: 'color',
            entityId: $this->id,
            source: 'market',
            branchId: null,
            message: $message
        );
    }

    protected function toSyncPayload(): array
    {
        return [
            'id' => $this->id,
            'yii_color_id' => $this->id,
            'name_ru' => $this->name_ru,
            'name_en' => $this->name_en,
            'name_uz' => $this->name_uz,
            'color' => $this->color,
            'status' => $this->status ?? self::STATUS_ACTIVE,
            'deleted_at' => $this->deleted_at ?? null,
        ];
    }
}
