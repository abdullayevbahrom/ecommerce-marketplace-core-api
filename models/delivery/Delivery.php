<?php

namespace app\models\delivery;

use app\components\RabbitMq\MessageFactory;
use app\components\RabbitMq\OutboxService;
use Yii;
use yii\web\UploadedFile;

use app\models\Images;
use app\models\order\Order;

/**
 * This is the model class for table "delivery".
 *
 * @property int $id
 * @property string|null $name_ru
 * @property string|null $name_uz
 * @property string|null $name_en
 * @property string|null $description_ru
 * @property string|null $description_uz
 * @property string|null $description_en
 * @property float|null $price
 * @property int $status
 * @property int $sort
 * @property string $date
 *
 * @property Order[] $orders
 */
class Delivery extends \yii\db\ActiveRecord
{
    public bool $suppressSyncEvents = false;
    public $imageFiles = [];
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'delivery';
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

        $eventType = $insert ? 'delivery.created' : 'delivery.updated';

        (new OutboxService())->queue(
            exchange: 'market_to_sklad',
            routingKey: $eventType,
            eventType: $eventType,
            entityType: 'delivery',
            entityId: $this->id,
            source: 'market',
            branchId: null,
            message: MessageFactory::make(
                eventType: $eventType,
                source: 'market',
                entityType: 'delivery',
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
            routingKey: 'delivery.deleted',
            eventType: 'delivery.deleted',
            entityType: 'delivery',
            entityId: $this->id,
            source: 'market',
            branchId: null,
            message: MessageFactory::make(
                eventType: 'delivery.deleted',
                source: 'market',
                entityType: 'delivery',
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
            [['name_ru'], 'required', 'message' => 'Заполните поле'],
            [['description_ru', 'description_uz', 'description_en'], 'string'],
            [['price'], 'number'],
            [['status', 'sort'], 'integer'],
            [['date'], 'safe'],
            [['name_ru', 'name_uz', 'name_en'], 'string', 'max' => 255],
            // [['imageFiles'], 'file', 'skipOnEmpty' => true, 'extensions' => 'png, jpg, jpeg']
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
            'name_uz' => 'Name Uz',
            'name_en' => 'Name En',
            'description_ru' => 'Description Ru',
            'description_uz' => 'Description Uz',
            'description_en' => 'Description En',
            'price' => 'Price',
            'status' => 'Status',
            'sort' => 'Sort',
            'date' => 'Date',
        ];
    }

    public function saveObject()
    {
        $this->status = 1;

        if ($this->save()) {
            $image = new Images;
            if ($image->imageFiles = UploadedFile::getInstances($this, 'imageFiles')) {
                if ($this->image) {
                    $this->image->removeImageSize();
                }
                $image->uploadPhoto($this->id, 'delivery');
            }

            return true;
        }

        return false;
    }

    public function removeObject()
    {
        if ($this->image && $this->image->delete()) {
            $this->image->removeImageSize();
        }

        return $this->delete();
    }

    public function getPhoto($s = 'original')
    {
        if ($this->image) {
            return $this->image->getPhoto('delivery', $s);
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
                return $this->{'name_' . $language} ? $this->{'name_' . $language} : $this->name_ru;
            },
            'description' => function () use ($language) {
                return $this->{'description_' . $language} ? $this->{'description_' . $language} : $this->description_ru;
            },
            'photo' => function () {
                return $this->getPhoto();
            },
            'price',
            'date'
        ];
    }

    /**
     * Gets query for [[Orders]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOrders()
    {
        return $this->hasMany(Order::className(), ['delivery_id' => 'id']);
    }

    public function getImage()
    {
        return $this->hasOne(Images::className(), ['object_id' => 'id'])->andOnCondition(['type' => 'delivery']);
    }

    protected function toSyncPayload(): array
    {
        return [
            'id' => $this->id,
            'yii_delivery_id' => $this->id,
            'name_ru' => $this->name_ru,
            'name_uz' => $this->name_uz,
            'name_en' => $this->name_en,
            'price' => $this->price ?? 0,
            'status' => $this->status ?? 1,
        ];
    }
}
