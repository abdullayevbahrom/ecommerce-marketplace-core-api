<?php

namespace app\models\logist;

use Yii;
use yii\web\UploadedFile;

use app\models\user\User;
use app\models\logist\region\LogistRegion;
use app\models\Images;

/**
 * This is the model class for table "logist".
 *
 * @property int $id
 * @property string|null $name_ru
 * @property string|null $name_uz
 * @property string|null $name_en
 * @property string|null $description_ru
 * @property string|null $description_uz
 * @property string|null $description_en
 * @property string|null $contact_user
 * @property string|null $contact_phone
 * @property int $sort
 * @property int $status
 * @property string $date
 *
 * @property LogistRegion[] $logistRegions
 */
class Logist extends \yii\db\ActiveRecord
{
    const LOGIST_CREATE = 'create';
    const LOGIST_UPDATE = 'update';

    public $imageFiles = [];
    public $login, $password;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'logist';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['password', 'login', 'name_ru'], 'required', 'message' => 'Заполните поле', 'on' => self::LOGIST_CREATE],
            [['name_ru'], 'required', 'message' => 'Заполните поле', 'on' => self::LOGIST_UPDATE],

            ['login', 'checkLogin'],
            [['user_id', 'sort', 'status'], 'integer'],
            [['date', 'sub_category_id', 'prices'], 'safe'],
            [['name_ru', 'name_uz', 'name_en', 'description_ru', 'description_uz', 'description_en', 'contact_user', 'contact_phone', 'login', 'password'], 'string', 'max' => 255],
            // [['imageFiles'], 'file', 'skipOnEmpty' => true, 'extensions' => 'png, jpg, jpeg'],
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
            'contact_user' => 'Contact User',
            'contact_phone' => 'Contact Phone',
            'sort' => 'Sort',
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
        $user = new User;
        if ($this->user_id) {
            $user = User::findOne($this->user_id);
            $current_password = $user->password;
        } else {
            $user->token = $user->generateToken();
        }

        $user->password = !$this->password ? $current_password : $user->generatePassword($this->password);

        $user->status = 1;
        $user->role = User::ROLE_LOGIST;
        $user->login = $this->login;
        $user->save();

        return $user;
    }

    public function saveObject()
    {
        if ($this->save()) {
            $user = $this->saveUser();
            $this->user_id = $user->id;
            $this->save(false);

            $image = new Images;
            if ($image->imageFiles = UploadedFile::getInstances($this, 'imageFiles')) {
                if ($this->image) {
                    $this->image->removeImageSize();
                }
                $image->uploadPhoto($this->id, 'logist');
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
            return $this->image->getPhoto('logist', $s);
        }

        return Images::PHOTO_DEFAULT;
    }

    public function fields()
    {
        $headers = Yii::$app->request->headers;
        $language = $headers->has('Content-Language') ? $headers->get('Content-Language') : 'ru';

        $controller = Yii::$app->controller->id;
        $action = Yii::$app->controller->action->id;

        $exception = ['detail'];

        $data = [
            'id',
            'name' => function () {
                return $this->name_ru;
            },
            'description' => function () {
                return $this->description_ru;
            },
            'photo' => function () {
                return $this->getPhoto();
            },
            'status',
            'logistRegions'
        ];

        return $data;
    }

    /**
     * Gets query for [[LogistRegions]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getLogistRegions()
    {
        return $this->hasMany(LogistRegion::className(), ['logist_id' => 'id']);
    }

    public function getImage()
    {
        return $this->hasOne(Images::className(), ['object_id' => 'id'])->andOnCondition(['type' => 'logist', 'main' => 1]);
    }

    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }
}
