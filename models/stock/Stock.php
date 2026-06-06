<?php

namespace app\models\stock;

use Yii;
use yii\web\UploadedFile;
use app\components\RabbitMq\MessageFactory;
use app\components\RabbitMq\OutboxService;
use app\models\user\User;
use app\models\product\Product;
use app\models\shop\Shop;
use app\models\Images;
use app\models\Region;
use app\models\City;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * This is the model class for table "stock".
 *
 * @property int $id
 * @property int|null $shop_id
 * @property string|null $name_ru
 * @property string|null $name_en
 * @property string|null $name_uz
 * @property string|null $description_ru
 * @property string|null $description_en
 * @property string|null $description_uz
 * @property int $status
 * @property int $sort
 * @property string $date
 * @property int|null $bts_region_id
 * @property int|null $bts_city_id
 * @property string|null $address
 * @property int $for_marketplace
 *
 * @property Shop $shop
 * @property Region $btsRegion
 * @property City $btsCity
 */
class Stock extends \yii\db\ActiveRecord
{

    public bool $suppressSyncEvents = false;
    public $imageFiles = [];
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'stock';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name_ru'], 'required', 'message' => 'Заполните поле'],
            [['shop_id', 'status', 'sort', 'for_marketplace'], 'integer'],
            [['bts_region_id', 'bts_city_id'], 'string', 'max' => 10],
            [['description_ru', 'description_en', 'description_uz', 'address'], 'string'],
            [['date'], 'safe'],
            [['name_ru', 'name_en', 'name_uz'], 'string', 'max' => 255],
            [['shop_id'], 'exist', 'skipOnError' => true, 'targetClass' => Shop::class, 'targetAttribute' => ['shop_id' => 'id']],
            [['bts_region_id'], 'validateBtsRegion'],
            [['bts_city_id'], 'validateBtsCity'],
            [['bts_city_id'], 'validateBtsLocation'],
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
            'shop_id' => 'Shop ID',
            'name_ru' => 'Name Ru',
            'name_en' => 'Name En',
            'name_uz' => 'Name Uz',
            'description_ru' => 'Description Ru',
            'description_en' => 'Description En',
            'description_uz' => 'Description Uz',
            'status' => 'Status',
            'sort' => 'Sort',
            'date' => 'Date',
            'bts_region_id' => 'Регион',
            'bts_city_id' => 'Город',
            'address' => 'Адрес',
            'for_marketplace' => 'Для маркетплейса',
        ];
    }

    public function saveObject()
    {
        $this->status = 1;

        /** @var User|null $user */
        $user = Yii::$app->user->identity;
        if ($user && $user->role == User::ROLE_SHOP) {
            $shop = Shop::findOne(['user_id' => $user->id]);
            $this->shop_id = $shop->id;
        }

        if ($this->save()) {
            $image = new Images;
            if ($image->imageFiles = UploadedFile::getInstances($this, 'imageFiles')) {
                if ($this->image) {
                    $this->image->removeImageSize();
                }
                $image->uploadPhoto($this->id, 'stock');
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
            return $this->image->getPhoto('stock', $s);
        }

        return Images::PHOTO_DEFAULT;
    }

    public function fields()
    {
        $headers = Yii::$app->request->headers;
        $language = $headers->has('Content-Language') ? $headers->get('Content-Language') : 'ru';

        $controller = Yii::$app->controller->id;
        $action = Yii::$app->controller->action->id;

        $data = [
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
            'product_count' => function () {
                return count($this->products);
            },
            'status',
            'date',
            'region_name' => function () use ($language) {
                return $this->getRegionName($language);
            },
            'city_name' => function () use ($language) {
                return $this->getCityName($language);
            },
            'full_address' => function () use ($language) {
                return $this->getFullAddress($language);
            },
            'bts_region_id',
            'bts_city_id'
        ];

        return $data;
    }

    /**
     * Gets query for [[Shop]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getShop()
    {
        return $this->hasOne(Shop::class, ['id' => 'shop_id']);
    }

    public function getProducts()
    {
        return $this->hasMany(Product::class, ['stock_id' => 'id']);
    }

    public function isAvailableForMarketplace(): bool
    {
        return (int) $this->for_marketplace === 1;
    }

    // location relationships using BTS constants
    public function getBtsRegion()
    {
        if ($this->bts_region_id) {
            $regions = \yii\services\BTS::getRegionsDetailed();
            return isset($regions[$this->bts_region_id]) ? $regions[$this->bts_region_id] : null;
        }
        return null;
    }

    public function getBtsCity()
    {
        if ($this->bts_city_id) {
            $cities = \yii\services\BTS::getCitiesDetailed();
            return isset($cities[$this->bts_city_id]) ? $cities[$this->bts_city_id] : null;
        }
        return null;
    }

    /**
     * Get region name
     * @param string $language
     * @return string|null
     */
    public function getRegionName($language = 'ru')
    {
        if ($this->bts_region_id) {
            return \yii\services\BTS::getRegionName($this->bts_region_id, $language);
        }
        return null;
    }

    /**
     * Get city name
     * @param string $language
     * @return string|null
     */
    public function getCityName($language = 'ru')
    {
        if ($this->bts_city_id) {
            return \yii\services\BTS::getCityName($this->bts_city_id, $language);
        }
        return null;
    }

    /**
     * Get full address with region and city
     * @param string $language
     * @return string
     */
    public function getFullAddress($language = 'ru')
    {
        $parts = [];

        if ($regionName = $this->getRegionName($language)) {
            $parts[] = $regionName;
        }

        if ($cityName = $this->getCityName($language)) {
            $parts[] = $cityName;
        }

        if ($this->address) {
            $parts[] = $this->address;
        }

        return implode(', ', $parts);
    }

    /**
     * Validate BTS region ID
     * @return bool
     */
    public function validateBtsRegion()
    {
        if ($this->bts_region_id) {
            $regions = \yii\services\BTS::getRegions();
            if (!isset($regions[$this->bts_region_id])) {
                $this->addError('bts_region_id', 'Invalid BTS region ID.');
                return false;
            }
        }
        return true;
    }

    /**
     * Validate BTS city ID
     * @return bool
     */
    public function validateBtsCity()
    {
        if ($this->bts_city_id) {
            $cities = \yii\services\BTS::getCitiesDetailed();
            if (!isset($cities[$this->bts_city_id])) {
                $this->addError('bts_city_id', 'Invalid BTS city ID.');
                return false;
            }
        }
        return true;
    }

    /**
     * Validate BTS region and city relationship
     * @return bool
     */
    public function validateBtsLocation()
    {
        if ($this->bts_region_id && $this->bts_city_id) {
            $cities = \yii\services\BTS::getCitiesDetailed();
            if (isset($cities[$this->bts_city_id]) && $cities[$this->bts_city_id]['region_id'] != $this->bts_region_id) {
                $this->addError('bts_city_id', 'Selected city does not belong to the selected region.');
                return false;
            }
        }
        return true;
    }

    // images
    public function getImage()
    {
        return $this->hasOne(Images::class, ['object_id' => 'id'])->andOnCondition(['type' => 'stock', 'main' => 1]);
    }

    public function syncPayloadToWarehouse(): array
    {
        return [
            'id' => $this->id,
            'name_ru' => $this->name_ru,
            'name_uz' => $this->name_uz,
            'name_en' => $this->name_en,
            'shop_id' => $this->shop_id,
            'description_ru' => $this->description_ru,
            'description_uz' => $this->description_uz,
            'description_en' => $this->description_en,
            'status' => $this->status,
            'date' => $this->date,
            'sort' => $this->sort,
            'deleted_at' => $this->deleted_at,
            'bts_region_id' => $this->bts_region_id,
            'bts_city_id' => $this->bts_city_id,
            'address' => $this->getFullAddress(),
            'responsible_person' => $this->shop->user->getFullName(),
            'phone' => $this->shop->user->phone,
            'for_marketplace' => (int) ($this->for_marketplace ?? 0),
        ];
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        if ($this->suppressSyncEvents) {
            return;
        }

        $eventType = $insert ? 'stock.created' : 'stock.updated';

        $message = MessageFactory::make(
            eventType: $eventType,
            source: 'market',
            entityType: 'stock',
            entityId: $this->id,
            branchId: $this->id,
            payload: $this->syncPayloadToWarehouse(),
        );

        (new OutboxService)->queue(
            exchange: 'market_to_sklad',
            routingKey: $eventType,
            eventType: $eventType,
            entityType: 'stock',
            entityId: $this->id,
            source: 'market',
            branchId: $this->id,
            message: $message
        );
    }

    public function afterDelete()
    {
        parent::afterDelete();

        if ($this->suppressSyncEvents) {
            return;
        }

        $message = MessageFactory::make(
            eventType: 'stock.deleted',
            source: 'market',
            entityType: 'stock',
            entityId: $this->id,
            branchId: $this->id,
            payload: ['id' => $this->id],
        );

        (new OutboxService)->queue(
            exchange: 'market_to_sklad',
            routingKey: 'stock.deleted',
            eventType: 'stock.deleted',
            entityType: 'stock',
            entityId: $this->id,
            source: 'market',
            branchId: $this->id,
            message: $message
        );
    }
}
