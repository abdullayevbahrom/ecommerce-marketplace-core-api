<?php

namespace app\models\shop;

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

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

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

    public $imageFiles = [];
    public $imageFilesBanner = [];

    // user info
    public $login, $password, $name, $phone, $email;

    // seller info
    public $inn, $account, $bank, $address_legal, $oked, $okohx, $mfo, $organization;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'shop';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['password', 'login', 'name', 'phone'], 'required', 'message'=>'Заполните поле', 'on'=>self::SHOP_CREATE],
            [['name_ru'], 'required', 'message'=>'Заполните поле', 'on'=>self::SHOP_UPDATE],

            [['name_ru'], 'required', 'message'=>'Заполните поле'],
            ['login', 'checkLogin'],
            [['description_ru', 'description_uz', 'description_en', 'contact_user', 'contact_phone'], 'string'],
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

    /**
     * {@inheritdoc}
     */
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

    public function checkLogin($attribute, $params) {
        $user = new User;

        if (!$user->hasErrors()) {
            $user = $user->findByUsername($this->login);
            if ($user && ($user->id != $this->user_id)) {
                return $this->addError($attribute, 'Логин уже занят');
            }
        }

        return false;
    }

    public function saveUser() {
        $user = new User;
        if ($this->user_id) {
            $user = User::findOne($this->user_id);
            $current_password = $user->password;
        } else {
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
        $user->save();

        return $user;
    }

    public function saveSeller() {
        $seller = ShopSeller::findOne(['shop_id'=>$this->id]);
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

    public function saveObject() {
        $this->status = 1;

        if ($this->save()) {
            // user
            $user = $this->saveUser();
            $this->user_id = $user->id;
            $this->save(false);

            // seller
            $this->saveSeller();

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

            // Sync to Sklad
            $this->syncToWarehouse();

            return true;
        }

        return false;
    }

    private function syncToWarehouse()
    {
        $baseUrl = Yii::$app->params['warehouseApiUrl'] ?? 'http://warehouse.example.com';
        $apiUrl = $baseUrl . '/api/sync/shop';

        $client = new Client(['timeout' => 5.0]);

        $dataToSend = [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'name' => $this->name_ru,
            'phone' => $this->phone,
            'inn' => $this->inn,
            'address' => $this->address_legal,
        ];

        $secretKey = Yii::$app->params['apiSecretKey'] ?? null;
        if (!$secretKey) {
            return;
        }
        
        $token = md5($this->id . $secretKey);

        try {
            $client->post($apiUrl, [
                'json' => $dataToSend,
                'headers' => [
                    'X-Api-Token' => $token,
                ],
            ]);
        } catch (RequestException $e) {
            Yii::error('Failed to sync shop to warehouse: ' . $e->getMessage(), 'warehouse_sync');
        }
    }

    public function removeObject(){
        if ($this->image && $this->image->delete()){
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

    public function getPhoto($s = 'original') {
        if ($this->image) {
            $path = Images::PHOTO_SHOP_PATH.$this->image->object_id.'/'.$s.'/'.$this->image->photo;
            if (is_file($path)) {
                return '/'.$path;
            }
        }

        return Images::PHOTO_DEFAULT;
    }

    public function getPhotoBanner($s = 'original') {
        if ($this->banner) {
            $path = Images::PHOTO_SHOP_PATH.$this->banner->object_id.'/'.$s.'/'.$this->banner->photo;
            if (is_file($path)) {
                return '/'.$path;
            }
        }

        return Images::PHOTO_DEFAULT;
    }

    public function getPhotos($s = 'original') {
        $data = [];

        if ($this->gallery) {
            foreach ($this->gallery as $photo) {
                $path = Images::PHOTO_SHOP_PATH.$photo->object_id.'/'.$s.'/'.$photo->photo;
                if (is_file($path)) {
                    $data[] = '/'.$path;
                }
            }
        }

        return $data;
    }

    public function getCountReviews() {
        $products = ArrayHelper::map(Product::find()->where(['shop_id'=>$this->id])->all(), 'id', 'id');

        return (int)ProductReview::find()->where(['in', 'product_id', $products])->count();
    }

    public function isFavorite() {
        $favorite = UserShopFavorite::findOne(['shop_id'=>$this->id, 'user_id'=>Yii::$app->user->identity->id]);
        return $favorite ? true : false;
    }

    public function fields() {
        $controller = Yii::$app->controller->id;
        $action = Yii::$app->controller->action->id;

        $headers = Yii::$app->request->headers;
        $language = $headers->has('Content-Language') ? $headers->get('Content-Language') : 'ru';

        $data = [
            'id',
            'name' => function(){return $this->name_ru;},
            'photo',
            'photoBanner',
            'gallery' => function() {return $this->getPhotos();},
            'contact_user',
            'contact_phone',
            'isFavorite' => function(){return $this->isFavorite();},
            'date'
        ];

        $exception = ['detail'];

        if (($controller == 'shop') && in_array($action, $exception) || (Yii::$app->user->identity->role == User::ROLE_SHOP)) {
            $detail = [
                'description' => function() use($language) { return $this->{'description_'.$language} ? $this->{'description_'.$language} : $this->description_ru;},
                'user',
                'shopSeller',
                'review_count' => function() { return $this->getCountReviews(); },
            ];

            $data = array_merge($data, $detail);
        }

        if (Yii::$app->user->identity->role == User::ROLE_SHOP) {
            $detail = [
                'product_count' => function() { return count($this->products); },
                'advertisment_count' => function() { return count($this->shopAdvertisings); },
                'news_count' => function() { return count($this->news); },
                'review_count' => function() { return $this->getCountReviews(); },
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
        return $this->hasMany(ShopSeller::className(), ['shop_id' => 'id']);
    }

    public function getShopSeller()
    {
        return $this->hasOne(ShopSeller::className(), ['shop_id' => 'id']);
    }

    public function getShopAdvertisings()
    {
        return $this->hasMany(ShopAdvertising::className(), ['shop_id' => 'id']);
    }

    public function getProducts()
    {
        return $this->hasMany(Product::className(), ['shop_id' => 'id']);
    }

    public function getUser() {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }

    public function getImage() {
        return $this->hasOne(Images::className(), ['object_id'=>'id'])->andOnCondition(['type'=>'shop', 'main'=>1]);
    }

    public function getBanner() {
        return $this->hasOne(Images::className(), ['object_id'=>'id'])->andOnCondition(['type'=>'shop', 'main'=>3]);
    }

    public function getGallery() {
        return $this->hasMany(Images::className(), ['object_id' => 'id'])->andOnCondition(['type'=>'shop', 'main'=>2]);
    }

    public function getNews()
    {
        return $this->hasMany(News::className(), ['shop_id' => 'id']);
    }

    public function getStocks()
    {
        return $this->hasMany(Stock::className(), ['shop_id' => 'id']);
    }

    public function getStock()
    {
        return $this->hasOne(Stock::className(), ['shop_id' => 'id']);
    }

    public function getShopOfertas()
    {
        return $this->hasMany(ShopOferta::className(), ['shop_id' => 'id']);
    }
}
