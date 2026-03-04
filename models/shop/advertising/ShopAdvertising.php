<?php

namespace app\models\shop\advertising;

use Yii;
use yii\web\UploadedFile;

use app\models\Images;
use app\models\shop\Shop;
use app\models\user\User;

/**
 * This is the model class for table "shop_advertising".
 *
 * @property int $id
 * @property int|null $shop_id
 * @property string|null $name_ru
 * @property string|null $name_uz
 * @property string|null $name_en
 * @property string|null $content_ru
 * @property string|null $content_uz
 * @property string|null $content_en
 * @property string|null $link
 * @property int $status
 * @property int $sort
 * @property string $date
 *
 * @property Shop $shop
 */
class ShopAdvertising extends \yii\db\ActiveRecord
{
    public $imageFiles = [];

    const ADMIN = 'admin';
    const SHOP = 'shop';
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'shop_advertising';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['shop_id', 'name_ru'], 'required', 'message' => 'Заполните поле', 'on' => self::ADMIN],
            [['name_ru'], 'required', 'message' => 'Заполните поле', 'on' => self::SHOP],
            [['shop_id', 'status', 'sort'], 'integer'],
            [['content_ru', 'content_uz', 'content_en'], 'string'],
            [['date'], 'safe'],
            [['name_ru', 'name_uz', 'name_en', 'link'], 'string', 'max' => 255],
            [['shop_id'], 'exist', 'skipOnError' => true, 'targetClass' => Shop::className(), 'targetAttribute' => ['shop_id' => 'id']],
            [['imageFiles'], 'file', 'skipOnEmpty' => true, 'extensions' => 'png, jpg, jpeg']
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
            'name_uz' => 'Name Uz',
            'name_en' => 'Name En',
            'content_ru' => 'Content Ru',
            'content_uz' => 'Content Uz',
            'content_en' => 'Content En',
            'link' => 'Link',
            'status' => 'Status',
            'sort' => 'Sort',
            'date' => 'Date',
        ];
    }

    public function saveObject()
    {
        $this->status = 1;

        if (Yii::$app->user->identity->role == User::ROLE_SHOP) {
            $shop = Shop::findOne(['user_id' => Yii::$app->user->identity->id]);
            $this->shop_id = $shop->id;
        }

        if ($this->save()) {
            $image = new Images;
            if ($image->imageFiles = UploadedFile::getInstances($this, 'imageFiles')) {
                if ($this->image) {
                    $this->image->removeImageSize();
                }
                $image->uploadPhoto($this->id, 'shop_advertising');
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
            return $this->image->getPhoto('shop_advertising', $s);
        }

        return Images::PHOTO_DEFAULT;
    }

    public function fields()
    {
        $headers = Yii::$app->request->headers;
        $language = $headers->has('Content-Language') ? $headers->get('Content-Language') : 'ru';

        return [
            'id',
            'shop',
            'name' => function () use ($language) {
                return $this->{'name_' . $language} ? $this->{'name_' . $language} : $this->name_ru;
            },
            'content' => function () use ($language) {
                return $this->{'content_' . $language} ? $this->{'content_' . $language} : $this->content_ru;
            },
            'link',
            'photo' => function () {
                return $this->getPhoto();
            },
            'status',
            'date'
        ];
    }

    // relations
    public function getImage()
    {
        return $this->hasOne(Images::className(), ['object_id' => 'id'])->andOnCondition(['type' => 'shop_advertising']);
    }

    /**
     * Gets query for [[Shop]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getShop()
    {
        return $this->hasOne(Shop::className(), ['id' => 'shop_id']);
    }
}
