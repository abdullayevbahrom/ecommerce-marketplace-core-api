<?php

namespace app\models\shop;

use app\components\RabbitMq\MessageFactory;
use app\components\RabbitMq\OutboxService;
use Yii;
use yii\web\UploadedFile;
use yii\helpers\ArrayHelper;
use app\models\Images;
use app\models\user\User;
use app\models\user\favorite_shop\UserShopFavorite;
use app\models\stock\Stock;
use app\models\shop\seller\ShopSeller;
use app\models\shop\advertising\ShopAdvertising;
use app\models\product\Product;
use app\models\product\review\ProductReview;
use app\models\news\News;
use app\models\shop\oferta\ShopOferta;
use yii\db\Exception as DbException;

/**
 * This is the model class for table "shop".
 *
 * @property int $id
 * @property int $user_id
 * @property string|null $name_ru
 * @property string|null $name_uz
 * @property string|null $name_en
 * @property string|null $description_ru
 * @property string|null $description_uz
 * @property string|null $description_en
 * @property string|null $map_
 * @property int $status
 * @property string $date
 *
 * @property ShopSeller[] $shopSellers
 */
class Shop extends \yii\db\ActiveRecord
{
    const SHOP_CREATE = 'create';
    const SHOP_UPDATE = 'update';

    public bool $suppressSyncEvents = false;
    public $imageFiles = [];
    public $imageFilesBanner = [];

    // user info
    public $login, $password, $name, $phone, $email;

    // seller info
    public $inn, $account, $bank, $address_legal, $oked, $okohx, $mfo, $organization;

    public static function tableName()
    {
        return 'shop';
    }

    public function beforeValidate()
    {
        if (!parent::beforeValidate()) {
            return false;
        }

        $contactDigits = preg_replace('/\D/', '', (string) $this->contact_phone);
        if ($contactDigits !== '') {
            $this->contact_phone = $contactDigits;
        }

        $phoneDigits = preg_replace('/\D/', '', (string) $this->phone);
        if ($phoneDigits !== '') {
            $this->phone = $phoneDigits;
        }

        return true;
    }

    public function rules()
    {
        return [
            [['password', 'login', 'name', 'phone'], 'required', 'message' => 'Заполните поле', 'on' => self::SHOP_CREATE],
            [['name_ru'], 'required', 'message' => 'Заполните поле', 'on' => self::SHOP_UPDATE],

            [['name_ru'], 'required', 'message' => 'Заполните поле'],
            ['login', 'checkLogin'],
            [['description_ru', 'description_uz', 'description_en', 'contact_user', 'contact_phone'], 'string'],
            [['contact_phone', 'phone'], 'filter', 'filter' => function ($value) {
                $digits = preg_replace('/\D/', '', (string) $value);
                return $digits === '' ? null : $digits;
            }],
            [['contact_phone'], 'match', 'pattern' => '/^998\d{9}$/', 'message' => 'Формат телефона должен быть 998XXXXXXXXX'],
            [['status', 'user_id'], 'integer'],
            [['date'], 'safe'],
            [['name_ru', 'name_uz', 'name_en'], 'string', 'max' => 255],
            [['imageFiles'], 'file', 'skipOnEmpty' => true, 'extensions' => 'png, jpg, jpeg'],

            // user
            [['login', 'password', 'name', 'phone', 'email'], 'string', 'max' => 255],

            // seller
            [['inn', 'account', 'bank', 'address_legal', 'oked', 'okohx', 'mfo', 'organization'], 'string', 'max' => 255]
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'name_ru' => 'Name Ru',
            'name_uz' => 'Name Uz',
            'name_en' => 'Name En',
            'description_ru' => 'Description Ru',
            'description_uz' => 'Description Uz',
            'description_en' => 'Description En',
            'status' => 'Status',
            'date' => 'Date',
        ];
    }

    public function checkLogin($attribute, $params)
    {
        $user = new User;

        if (!$user->hasErrors()) {
            $user = $user->findByUsername($this->login);
            if ($user && ($user->id != $this->user_id)) {
                return $this->addError($attribute, 'Логин уже занят');
            }
        }

        return false;
    }

    public function saveUser()
    {
        if ($this->user_id) {
            $user = User::findOne($this->user_id);
            $current_password = $user->password;
        } else {
            $user = new User;
            $user->token = $user->generateToken();
        }

        $user->password = !$this->password ? $current_password : $user->generatePassword($this->password);
        $user->status = 1;
        $user->role = User::ROLE_SHOP;
        $user->name = $this->name;
        $user->phone = $this->phone;
        $user->email = $this->email;
        $user->login = $this->login;
        $user->shop_id = $this->id;

        if (!$user->save()) {
            Yii::error([
                'message' => 'Shop user save failed',
                'errors' => $user->errors,
                'shop_id' => $this->id,
            ], 'warehouse_sync');

            throw new DbException('Shop user creation failed');
        }

        // Sklad provisioning
        try {
            Yii::$app->skladProvisioner->ensurePersonalWarehouse($user, 'merchant');
        } catch (\Exception $e) {
            Yii::warning('Sklad provisioning failed for shop user ' . $user->id . ': ' . $e->getMessage());
        }

        return $user;
    }

    public function saveSeller()
    {
        $seller = ShopSeller::findOne(['shop_id' => $this->id]);
        if (!$seller) {
            $seller = new ShopSeller;
        }

        $seller->shop_id = $this->id;
        $seller->inn = $this->inn;
        $seller->account = $this->account;
        $seller->bank = $this->bank;
        $seller->address_legal = $this->address_legal;
        $seller->oked = $this->oked;
        $seller->okohx = $this->okohx;
        $seller->mfo = $this->mfo;
        $seller->organization = $this->organization;

        return $seller->save();
    }

    public function saveObject()
    {
        $db = Yii::$app->db;
        $transaction = $db->beginTransaction();

        try {
            $this->status = 1;

            if (!$this->save()) {
                throw new DbException('Shop save failed');
            }

            $user = $this->saveUser();
            if (!$user) {
                throw new DbException('Shop user creation failed');
            }

            // Sklad provisioning
            try {
                Yii::$app->skladProvisioner->ensurePersonalWarehouse($user, 'merchant');
            } catch (\Exception $e) {
                Yii::warning('Sklad provisioning failed for shop user ' . $user->id . ': ' . $e->getMessage());
            }

            $this->user_id = $user->id;
            $this->save(false);

            if (!$this->saveSeller()) {
                throw new DbException('Shop seller save failed');
            }

            $image = new Images;

            if ($image->imageFiles = UploadedFile::getInstances($this, 'imageFiles')) {
                if ($this->image) {
                    $this->image->removeImageSize();
                }
                $image->uploadPhoto($this->id, 'shop');
            }

            if ($image->imageFiles = UploadedFile::getInstances($this, 'imageFilesBanner')) {
                $image->uploadPhoto($this->id, 'shop', 3);
            }

            $stock = $this->upsertDefaultStock();
            if (!$stock || !$stock->id) {
                throw new DbException('Default stock creation failed');
            }

            // $this->syncShopToWarehouse($user);

            // $this->syncStockToWarehouse($stock, $user);

            $transaction->commit();

            if ($this->scenario === Shop::SHOP_CREATE) {
                $this->suppressSyncEvents = false;
                $this->queueCreatedSyncEvent();
            }

            return true;
        } catch (\Throwable $e) {
            $transaction->rollBack();
            Yii::error(['message' => $e->getMessage(), 'shop_id' => $this->id ?? null, 'trace' => $e->getTraceAsString()], 'warehouse_sync');
            return false;
        }
    }

    public function upsertDefaultStock()
    {
        $stock = Stock::findOne(['shop_id' => $this->id]);
        if (!$stock) {
            $stock = new Stock();
        }

        $stock->name_ru = 'Ваше витрина';
        $stock->description_ru = 'Склад по умолчанию';
        $stock->status = 1;
        $stock->shop_id = $this->id;
        $stock->address = $this->address_legal ?? null;
        $stock->for_marketplace = 1;
        $stock->bts_region_id = '01';
        $stock->bts_city_id = '0110';

        if (!$stock->save()) {
            throw new \RuntimeException('Default stock creation failed');
        }

        return $stock;
    }

    public function removeObject()
    {
        if ($this->image && $this->image->delete()) {
            $this->image->removeImageSize();
        }

        if ($this->gallery) {
            foreach ($this->gallery as $photo) {
                $photo->removeImageSize();
            }
        }

        if ($this->user) {
            $this->user->delete();
        }

        return $this->delete();
    }

    public function getPhoto($s = 'original')
    {
        if ($this->image) {
            return $this->image->getPhoto('shop', $s);
        }

        return Images::PHOTO_DEFAULT;
    }

    public function getPhotoBanner($s = 'original')
    {
        if ($this->banner) {
            return $this->banner->getPhoto('shop', $s);
        }

        return Images::PHOTO_DEFAULT;
    }

    public function getPhotos($s = 'original')
    {
        $data = [];

        if ($this->gallery) {
            foreach ($this->gallery as $photo) {
                $data[] = $photo->getPhoto('shop', $s);
            }
        }

        return $data;
    }

    public function getCountReviews()
    {
        $products = ArrayHelper::map(Product::find()->where(['shop_id' => $this->id])->all(), 'id', 'id');

        return (int) ProductReview::find()->where(['in', 'product_id', $products])->count();
    }

    public function getAverageRating(): float
    {
        $productIds = ArrayHelper::map(
            Product::find()->select('id')->where(['shop_id' => $this->id])->asArray()->all(),
            'id',
            'id'
        );

        if (empty($productIds)) {
            return 0.0;
        }

        $avg = ProductReview::find()
            ->where(['in', 'product_id', $productIds])
            ->andWhere(['status' => [ProductReview::STATUS_ACCEPTED, ProductReview::STATUS_PROCESSED]])
            ->average('rate');

        return $avg !== null ? round((float) $avg, 1) : 0.0;
    }

    public function isFavorite()
    {
        $favorite = UserShopFavorite::findOne(['shop_id' => $this->id, 'user_id' => Yii::$app->user->identity?->id]);

        return $favorite ? true : false;
    }

    public function fields()
    {
        $controller = Yii::$app->controller->id;
        $action = Yii::$app->controller->action->id;

        $headers = Yii::$app->request->headers;
        $language = $headers->has('Content-Language') ? $headers->get('Content-Language') : 'ru';

        $data = [
            'id',
            'merchant_id' => function () {
                return $this->user_id;
            },
            'name' => function () {
                return $this->name_ru;
            },
            'photo' => function () {
                return $this->getPhoto();
            },
            'photoBanner' => function () {
                return $this->getPhotoBanner();
            },
            'gallery' => function () {
                return $this->getPhotos();
            },
            'contact_user',
            'contact_phone',
            'isFavorite' => function () {
                return $this->isFavorite();
            },
            'review_count' => function () {
                return $this->getCountReviews();
            },
            'rating' => function () {
                return $this->getAverageRating();
            },
            'date'
        ];

        $exception = ['detail'];

        if (($controller == 'shop') && in_array($action, $exception) || (Yii::$app->user->identity?->role == User::ROLE_SHOP)) {
            $detail = [
                'description' => function () use ($language) {
                    return $this->{'description_' . $language} ? $this->{'description_' . $language} : $this->description_ru;
                },
                'user',
                'shopSeller',
                'review_count' => function () {
                    return $this->getCountReviews();
                },
            ];

            $data = array_merge($data, $detail);
        }

        if (Yii::$app->user->identity?->role == User::ROLE_SHOP) {
            $detail = [
                'product_count' => function () {
                    return count($this->products);
                },
                'advertisment_count' => function () {
                    return count($this->shopAdvertisings);
                },
                'news_count' => function () {
                    return count($this->news);
                },
                'review_count' => function () {
                    return $this->getCountReviews();
                },
            ];

            $data = array_merge($data, $detail);
        }

        if (($controller == 'cart')) {
            $detail = [
                'user'
            ];

            $data = array_merge($data, $detail);
        }

        return $data;
    }

    /**
     * Gets query for [[ShopSellers]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getShopSellers()
    {
        return $this->hasMany(ShopSeller::class, ['shop_id' => 'id']);
    }

    public function getShopSeller()
    {
        return $this->hasOne(ShopSeller::class, ['shop_id' => 'id']);
    }

    public function getShopAdvertisings()
    {
        return $this->hasMany(ShopAdvertising::class, ['shop_id' => 'id']);
    }

    public function getProducts()
    {
        return $this->hasMany(Product::class, ['shop_id' => 'id']);
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public function getImage()
    {
        return $this->hasOne(Images::class, ['object_id' => 'id'])->andOnCondition(['type' => 'shop', 'main' => 1]);
    }

    public function getBanner()
    {
        return $this->hasOne(Images::class, ['object_id' => 'id'])->andOnCondition(['type' => 'shop', 'main' => 3]);
    }

    public function getGallery()
    {
        return $this->hasMany(Images::class, ['object_id' => 'id'])->andOnCondition(['type' => 'shop', 'main' => 2]);
    }

    public function getNews()
    {
        return $this->hasMany(News::class, ['shop_id' => 'id']);
    }

    public function getStocks()
    {
        return $this->hasMany(Stock::class, ['shop_id' => 'id']);
    }

    public function getStock()
    {
        return $this->hasOne(Stock::class, ['shop_id' => 'id']);
    }

    public function getShopOfertas()
    {
        return $this->hasMany(ShopOferta::class, ['shop_id' => 'id']);
    }

    public function syncCreatedPayloadToWarehouse(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'name_ru' => $this->name_ru,
            'name_uz' => $this->name_uz,
            'name_en' => $this->name_en,
            'description_ru' => $this->description_ru,
            'description_uz' => $this->description_uz,
            'description_en' => $this->description_en,
            'map_location' => $this->map_location,
            'contact_user' => $this->contact_user,
            'contact_phone' => $this->contact_phone,
            'status' => $this->status,
            'date' => $this->date,
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->getFullName(),
                'phone' => $this->user->phone,
                'role' => $this->user->role,
                'is_active' => $this->user->status === User::STATUS_ACTIVE,
            ],
            'stock' => [
                'id' => $this->stock->id,
                'name_ru' => $this->stock->name_ru,
                'name_uz' => $this->stock->name_uz,
                'name_en' => $this->stock->name_en,
                'description_ru' => $this->stock->description_ru,
                'description_uz' => $this->stock->description_uz,
                'description_en' => $this->stock->description_en,
                'status' => $this->stock->status,
                'date' => $this->stock->date,
                'sort' => $this->stock->sort,
                'deleted_at' => $this->stock->deleted_at,
                'bts_region_id' => $this->stock->bts_region_id,
                'bts_city_id' => $this->stock->bts_city_id,
                'address' => $this->stock->getFullAddress(),
                'responsible_person' => $this->contact_user,
                'phone' => $this->contact_phone,
                'for_marketplace' => (int) ($this->stock->for_marketplace ?: 1),
            ]
        ];
    }

    public function syncUpdatedPayloadToWarehouse(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'name_ru' => $this->name_ru,
            'name_uz' => $this->name_uz,
            'name_en' => $this->name_en,
            'description_ru' => $this->description_ru,
            'description_uz' => $this->description_uz,
            'description_en' => $this->description_en,
            'map_location' => $this->map_location,
            'contact_user' => $this->contact_user,
            'contact_phone' => $this->contact_phone,
            'status' => $this->status,
            'date' => $this->date,
        ];
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        if ($this->suppressSyncEvents) {
            return;
        }

        if ($insert) {
            return;
        }

        $eventType = 'shop.updated';

        $message = MessageFactory::make(
            eventType: $eventType,
            source: 'market',
            entityType: 'shop',
            entityId: $this->id,
            branchId: null,
            payload: $insert ? $this->syncCreatedPayloadToWarehouse() : $this->syncUpdatedPayloadToWarehouse(),
        );

        (new OutboxService)->queue(
            exchange: 'market_to_sklad',
            routingKey: $eventType,
            eventType: $eventType,
            entityType: 'shop',
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

        $message = MessageFactory::make(
            eventType: 'shop.deleted',
            source: 'market',
            entityType: 'shop',
            entityId: $this->id,
            branchId: null,
            payload: ['id' => $this->id],
        );

        (new OutboxService)->queue(
            exchange: 'market_to_sklad',
            routingKey: 'shop.deleted',
            eventType: 'shop.deleted',
            entityType: 'shop',
            entityId: $this->id,
            source: 'market',
            branchId: null,
            message: $message
        );
    }

    private function queueCreatedSyncEvent(): void
    {
        $this->refresh();

        $message = MessageFactory::make(
            eventType: 'shop.created',
            source: 'market',
            entityType: 'shop',
            entityId: $this->id,
            branchId: null,
            payload: $this->syncCreatedPayloadToWarehouse(),
        );

        (new OutboxService)->queue(
            exchange: 'market_to_sklad',
            routingKey: 'shop.created',
            eventType: 'shop.created',
            entityType: 'shop',
            entityId: $this->id,
            source: 'market',
            branchId: null,
            message: $message
        );
    }
}
