<?php

namespace app\models\news;

use Yii;
use yii\web\UploadedFile;

use app\models\Images;
use app\models\shop\Shop;
use app\models\user\User;

/**
 * This is the model class for table "news".
 *
 * @property int $id
 * @property string|null $name_ru
 * @property string|null $name_uz
 * @property string|null $name_en
 * @property string|null $description_ru
 * @property string|null $description_uz
 * @property string|null $description_en
 * @property int $status
 * @property string $date
 */
class News extends \yii\db\ActiveRecord
{
    public $imageFiles = [];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'news';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name_ru', 'description_ru', 'description_mini_ru'], 'required', 'message' => 'Заполните поле'],
            [['description_ru', 'description_uz', 'description_en', 'description_mini_ru', 'description_mini_uz', 'description_mini_en'], 'string'],
            [['status', 'views', 'shop_id'], 'integer'],
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
            'status' => 'Status',
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
                $image->uploadPhoto($this->id, 'news');
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
            return $this->image->getPhoto('news', $s);
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
            'description_mini' => function () use ($language) {
                return $this->{'description_mini_' . $language} ? $this->{'description_mini_' . $language} : $this->description_mini_ru;
            },
            'views',
            'photo' => function () {
                return $this->getPhoto();
            },
            'status',
            'date'
        ];

        if (($controller == 'news') && ($action == 'detail')) {
            unset($data['description_mini']);
            $data['description'] = function () use ($language) {
                return $this->{'description_' . $language} ? $this->{'description_' . $language} : $this->description_ru;
            };
        }

        return $data;
    }

    public function generateFileName()
    {
        return time() + uniqid();
    }

    // relations
    public function getImage()
    {
        return $this->hasOne(Images::className(), ['object_id' => 'id'])->andOnCondition(['type' => 'news']);
    }

    public function getShop()
    {
        return $this->hasOne(Shop::className(), ['id' => 'shop_id']);
    }
}
