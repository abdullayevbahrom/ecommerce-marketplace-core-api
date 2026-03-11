<?php

namespace app\models\user;

use Yii;
use yii\db\ActiveRecord;
use yii\helpers\Html;
use yii\web\IdentityInterface;
use yii\web\UploadedFile;

use yii\services\Sms;
use yii\services\SmsService;
use app\services\SMSCService;
use app\models\Images;
use app\models\order\OrderReview;
use app\models\shop\Shop;
use app\models\moderator\ModeratorAccess;
use app\models\user\address\UserAddress;

class User extends ActiveRecord implements IdentityInterface
{
    // user
    const USER_SIGNUP = 'signup';
    const USER_SIGNIN = 'signin';
    const USER_UPDATE = 'update';

    // admin
    const SIGNIN_ADMIN = 'signin_admin';
    const UPDATE_ADMIN = 'update_admin';

    const SIGNUP_ADMIN_USER = 'singup_admin_user';
    const UPDATE_ADMIN_USER = 'update_admin_user';
    const ADMIN_CHANGE_PASSWORD = 'admin_change_password';

    // moderator
    const SIGNUP_MODERATOR = 'signup_moderator';
    const UPDATE_MODERATOR = 'update_moderator';

    const RECOVER_PASSWORD = 'recover_password';

    const USER_SHOP_SIGNIN = 'user_shop_signin';

    const ROLE_ADMIN = 1;
    const ROLE_MODERATOR = 2;
    const ROLE_USER = 3;
    const ROLE_SHOP = 4;
    const ROLE_LOGIST = 5;
    const ROLE_OPERATOR = 6;

    const ROLE_LABELS = [
        self::ROLE_ADMIN => 'Администратор',
        self::ROLE_MODERATOR => 'Модератор',
        self::ROLE_USER => 'Клиент',
        self::ROLE_SHOP => 'Магазин',
        self::ROLE_LOGIST => 'Логист',
        self::ROLE_OPERATOR => 'Оператор',
    ];

    const ROLE_COLORS = [
        self::ROLE_ADMIN => 'bg-red',
        self::ROLE_MODERATOR => 'bg-purple',
        self::ROLE_USER => 'bg-aqua',
        self::ROLE_SHOP => 'bg-green',
        self::ROLE_LOGIST => 'bg-orange',
        self::ROLE_OPERATOR => 'bg-blue',
    ];

    const PHOTO_PATH = 'uploads/user/';
    const PHOTO_DEFAULT = '/assets_files/images/user.png';

    const SOURCE_YII = 'yii';
    const SOURCE_SKLAD = 'sklad';

    const STATUS_ACTIVE = 1;
    const STATUS_INACTIVE = 0;

    public $authKey;
    public $imageFiles = [];
    public $moderator_access = [];

    public $remember;
    public $password_repeat;
    public $address = [];

    public static function tableName()
    {
        return 'user';
    }

    public function rules()
    {
        return [
            // user reg
            [['phone'], 'required', 'message' => 'Заполните поле', 'on' => self::USER_SIGNUP],
            ['phone', 'checkPhone', 'on' => self::USER_SIGNUP],

            // user auth
            // ['password', 'checkPassword', 'on'=>self::USER_SIGNIN],
            [['phone'], 'required', 'message' => 'Заполните поле', 'on' => self::USER_SIGNIN],

            // user update
            [['name', 'lastname', 'middlename', 'phone', 'email', 'gender', 'birthday', 'type', 'inn', 'account', 'bank', 'oked', 'okohx', 'mfo', 'device_id', 'last_address', 'address', 'organization_name', 'bts_region_id', 'bts_city_id'], 'safe', 'on' => self::USER_UPDATE],
            ['phone', 'checkPhone', 'on' => self::USER_UPDATE],
            ['email', 'email', 'message' => 'Не верный формат e-mail', 'on' => self::USER_UPDATE],
            [['gender'], 'integer', 'min' => 1, 'max' => 2, 'on' => self::USER_UPDATE],
            ['type', 'in', 'range' => ['yur', 'fiz'], 'message' => 'Тип должен быть yur или fiz', 'on' => self::USER_UPDATE],

            // save user by admin
            [['password', 'name', 'phone'], 'required', 'message' => 'Заполните поле', 'on' => self::SIGNUP_ADMIN_USER],
            [['lastname', 'middlename', 'email', 'gender', 'birthday', 'type', 'inn', 'account', 'bank', 'oked', 'okohx', 'mfo', 'address_legal', 'address', 'organization_name', 'bts_region_id', 'bts_city_id'], 'safe', 'on' => self::SIGNUP_ADMIN_USER],
            [['name', 'phone'], 'required', 'message' => 'Заполните поле', 'on' => self::UPDATE_ADMIN_USER],
            [['lastname', 'middlename', 'email', 'gender', 'birthday', 'type', 'inn', 'account', 'bank', 'oked', 'okohx', 'mfo', 'address_legal', 'address', 'organization_name', 'bts_region_id', 'bts_city_id'], 'safe', 'on' => self::UPDATE_ADMIN_USER],
            ['phone', 'checkPhone', 'on' => self::SIGNUP_ADMIN_USER],
            ['email', 'checkEmail', 'on' => self::SIGNUP_ADMIN_USER],
            ['phone', 'checkPhoneAdmin', 'on' => self::UPDATE_ADMIN_USER],
            ['email', 'checkEmailAdmin', 'on' => self::UPDATE_ADMIN_USER],

            // moderator
            // sign-up
            [['name', 'login', 'password'], 'required', 'message' => 'Заполните поле', 'on' => self::SIGNUP_MODERATOR],
            ['login', 'checkLogin', 'on' => self::SIGNUP_MODERATOR],

            // update
            [['name', 'login'], 'required', 'message' => 'Заполните поле', 'on' => self::UPDATE_MODERATOR],
            ['login', 'checkLogin', 'on' => self::UPDATE_MODERATOR],
            ['phone', 'checkPhoneModerator', 'on' => self::UPDATE_MODERATOR],

            // admin auth
            [['login', 'password'], 'required', 'message' => 'Заполните поле', 'on' => self::SIGNIN_ADMIN],
            ['password', 'checkPassword', 'on' => self::SIGNIN_ADMIN],

            // admin update profile
            [['name', 'login'], 'required', 'message' => 'Заполните поле', 'on' => self::UPDATE_ADMIN],
            ['phone', 'checkPhoneAdmin', 'on' => self::UPDATE_ADMIN],
            ['email', 'checkEmail', 'on' => self::UPDATE_ADMIN],
            ['login', 'checkLogin', 'on' => self::UPDATE_ADMIN],

            // admin password
            [['password'], 'required', 'message' => 'Введите пароль', 'on' => self::ADMIN_CHANGE_PASSWORD],

            // user shop
            [['name', 'login', 'password'], 'required', 'message' => 'Заполните поле', 'on' => self::USER_SHOP_SIGNIN],
            ['login', 'checkLogin', 'on' => self::USER_SHOP_SIGNIN],

            // default validation
            // [['password', 'password_repeat'], 'required', 'message'=>'Заполните поле', 'on'=>self::RECOVER_PASSWORD],
            // ['password', 'compare', 'compareAttribute'=>'password_repeat', 'message'=>"Пароли не совпадают", 'on'=>self::RECOVER_PASSWORD],
            // [['password', 'password_repeat'], 'string', 'min'=>6, 'message'=>'Пароль не может быть менее 6 символов'],
            // ['password_repeat', 'compare', 'compareAttribute'=>'password', 'message'=>"Пароли не совпадают"],

            // [['email'], 'email', 'message'=>'Не верный формат e-mail'],
            // [['phone', 'email', 'name', 'lastname', 'ip', 'device_id', 'password', 'birthday', 'facebook_id', 'google_id', 'vk_id', 'last_address', 'type', 'inn', 'account', 'bank', 'address_legal', 'oked', 'okohx', 'mfo'], 'string'],
            // [['role', 'status', 'shop_id', 'manager'], 'integer'],
            // [['gender'], 'integer', 'min'=>1, 'max'=>2],
            // [['balance'], 'number'],
            // [['moderator_access', 'remember', 'address'], 'safe'],
            // [['imageFiles'], 'file', 'skipOnEmpty' => true, 'extensions' => 'png, jpg, jpeg', 'maxSize' => 3072000]
            [['phone', 'phone_code', 'status', 'token', 'role', 'sms_live'], 'safe'],

            [['bts_region_id', 'bts_city_id'], 'string', 'max' => 10],

            // BTS region and city validation
            // ['bts_city_id', 'validateCityRegion'],

            ['source', 'in', 'range' => [self::SOURCE_YII, self::SOURCE_SKLAD]],
        ];
    }

    public function getOrderReviews()
    {
        return $this->hasMany(OrderReview::className(), ['user_id' => 'id']);
    }

    // validate check password
    public function checkPassword($attribute, $params)
    {
        if (!$this->hasErrors()) {
            if ($this->login) {
                $user = $this->findByUsername($this->login);
            }
            if ($this->phone) {
                $user = $this->findByUsername($this->phone);
            }
            if ($this->email) {
                $user = $this->findByUsername($this->email);
            }

            $error_login = 'Не верный логин и/или пароль';
            if (!$user || !$user->validatePassword($this->password)) {
                return $this->addError($attribute, $error_login);
            }
        }

        return false;
    }

    // check exist login
    public function checkLogin($attribute, $params)
    {
        if (!$this->hasErrors()) {
            $user = $this->findByUsername($this->login);
            if ($user && ($user->id != $this->id)) {
                return $this->addError($attribute, 'Логин уже занят');
            }
        }

        return false;
    }

    // check exist email
    public function checkEmail($attribute, $params)
    {
        if (!$this->hasErrors()) {
            if ($this->email) {
                $user = $this->findByUsername($this->email);
                if ($user && ($user->id != $this->id)) {
                    return $this->addError($attribute, 'E-mail уже занят');
                }
            }
        }

        return false;
    }

    // check exist phone
    public function checkPhone($attribute, $params)
    {
        if (!$this->hasErrors()) {
            if (!preg_match("/^[\d+]+$/", $this->phone)) {
                return $this->addError($attribute, 'Вводите только цифры');
            }

            // Extract only digits from phone number (remove + and any other non-digit characters)
            $digitsOnly = preg_replace('/\D/', '', $this->phone);

            if (strlen($digitsOnly) != 12) {
                return $this->addError($attribute, 'Количество цифр должно быть 12');
            }

            $user = $this->findByUsername($this->phone);

            // if ($user && ($user->id != $this->id) && ($user->status != 0)) {
            //     return $this->addError($attribute, 'Номер телефона уже занят');
            // }
        }

        return false;
    }

    public function checkPhoneAdmin($attribute, $params)
    {
        if (!$this->hasErrors()) {
            if (!preg_match("/^[\d+]+$/", $this->phone)) {
                return $this->addError($attribute, 'Вводите только цифры');
            }
            // if ((mb_strlen($this->phone) < 13) || (mb_strlen($this->phone) > 13)) {
            //     return $this->addError($attribute, 'Количество цифр должно быть 12');
            // }

            $user = self::findOne(['phone' => $this->phone, 'role' => Yii::$app->user->identity->role]);

            if ($user && ($user->id != $this->id) && ($user->status == 1)) {
                return $this->addError($attribute, 'Номер телефона уже занят');
            }
        }

        return false;
    }

    public function checkEmailAdmin($attribute, $params)
    {
        if (!$this->hasErrors()) {
            if ($this->email) {
                $user = $this->findByUsername($this->email);
                if ($user && ($user->id != $this->id) && ($user->status == 1)) {
                    return $this->addError($attribute, 'E-mail уже занят');
                }
            }
        }

        return false;
    }

    public function checkPhoneModerator($attribute, $params)
    {
        if (!$this->hasErrors()) {
            if (!preg_match("/^[\d+]+$/", $this->phone)) {
                return $this->addError($attribute, 'Вводите только цифры');
            }
            // if ((mb_strlen($this->phone) < 13) || (mb_strlen($this->phone) > 13)) {
            //     return $this->addError($attribute, 'Количество цифр должно быть 12');
            // }

            $user = self::findOne(['phone' => $this->phone, 'role' => User::ROLE_MODERATOR]);

            if ($user && ($user->id != $this->id) && ($user->status == 1)) {
                return $this->addError($attribute, 'Номер телефона уже занят');
            }
        }

        return false;
    }

    public function checkPhoneCode($attribute, $params)
    {
        if (!$this->hasErrors()) {
            $user = SmsCode::findOne(['sms_code' => $this->phone_code]);

            if (!$user) {
                return $this->addError($attribute, 'Неверный код подтверждения');
            }
        }

        return false;
    }

    /**
     * Validate BTS city and region relationship
     * Ensures that if both bts_region_id and bts_city_id are set,
     * the city belongs to the specified region
     */
    public function validateCityRegion($attribute, $params)
    {
        if (!$this->hasErrors()) {
            // Only validate if both region and city are set
            if (!empty($this->bts_region_id) && !empty($this->bts_city_id)) {
                // Import BTS service to access city data
                $cityData = \yii\services\BTS::getCitiesDetailed($this->bts_region_id);

                // Check if the city exists in the specified region
                if (!isset($cityData[$this->bts_city_id])) {
                    return $this->addError($attribute, 'Выбранный город не принадлежит указанному региону');
                }
            }
        }

        return false;
    }

    public static function findIdentity($id)
    {
        return static::findOne($id);
    }

    public static function findIdentityByAccessToken($token, $type = null)
    {
        return static::findOne(['token' => $token]);
    }

    public static function findByUsername($username)
    {
        $user = static::findOne(['login' => $username]);
        if (!$user) {
            $user = static::findOne(['email' => $username]);
        }
        if (!$user) {
            $user = static::findOne(['phone' => $username]);
        }

        if (!$user) {
            $user = static::findOne(['facebook_id' => $username]);
        }

        if (!$user) {
            $user = static::findOne(['vk_id' => $username]);
        }

        if (!$user) {
            $user = static::findOne(['google_id' => $username]);
        }

        return $user;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getAuthKey()
    {
        return $this->authKey;
    }

    public function validateAuthKey($authKey)
    {
        return $this->authKey === $authKey;
    }

    //helpers
    public function setPassword($password)
    {
        $this->password = Yii::$app->security->generatePasswordHash($password);
    }

    public function generatePassword($password)
    {
        return Yii::$app->security->generatePasswordHash($password);
    }

    public function generatePasswordResetToken()
    {
        $this->password_reset_token = Yii::$app->security->generateRandomString() . '_' . time();
    }

    public function generateAuthKey()
    {
        $this->auth_key = Yii::$app->security->generateRandomString();
    }

    public function changePassword()
    {
        $this->password ? $this->setPassword(Html::encode($this->password)) : $this->password = $this->getOldAttributes()['password'];
        return $this->save(false) ? true : false;
    }

    public function validatePassword($password)
    {
        try {
            return Yii::$app->security->validatePassword($password, $this->password);
        } catch (\yii\base\InvalidArgumentException $e) {
            return false;
        }
    }

    public function checkOldPassword($attribute, $params)
    {
        if (!$this->hasErrors()) {
            if (!$this->validatePassword($this->current_password)) {
                return $this->addError($attribute, 'Не верный текущий пароль');
            }
        }

        return false;
    }

    public function sendEmail()
    {
        $body = "Для восстановления пароля, пожалуйста перейдите по ссылке: https://birmakon.qwertyuz.ru/main/reset-new-password?reset_key=" . $this->reset_key . "&email=" . $this->email;

        Yii::$app->mailer->compose()
            ->setFrom(Yii::$app->params['supportEmail'])
            ->setTo($this->email)
            ->setSubject('Восстановление пароля')
            ->setTextBody($body)
            ->send();

        return true;
    }

    public function generateCode($phone = false)
    {
        if (isset($phone) && $phone == '+71112223344') {
            return 1234567;
        }
        return 1234567;
        // return mt_rand(100000, 999999);
    }

    public function saveObject($type = self::ROLE_USER)
    {
        $this->ip = $_SERVER['REMOTE_ADDR'];
        // Only set role for new users; preserve existing role on update
        if ($this->isNewRecord) {
            $this->role = $type;
        }

        if (!Yii::$app->request->get('id')) {
            $this->token = $this->generateToken();
        }

        $this->status = 1;

        $this->date = date('Y-m-d H:i:s');

        if ($this->save(false)) {
            if ($this->moderator_access) {
                ModeratorAccess::deleteAll('user_id = :user_id', ['user_id' => $this->id]);

                $keys = ['user_id', 'moderator_id'];
                $vals = [];
                foreach ($this->moderator_access as $key => $access) {
                    $vals[$key]['user_id'] = $this->id;
                    $vals[$key]['moderator_id'] = $access;
                }
                Yii::$app->db->createCommand()->batchInsert('moderator_access', $keys, $vals)->execute();
            }

            if ($this->address) {
                UserAddress::deleteAll('user_id = :user_id', ['user_id' => $this->id]);

                $keys = ['user_id', 'address'];
                $vals = [];
                foreach ($this->address as $key => $value) {
                    $vals[$key]['user_id'] = $this->id;
                    $vals[$key]['address'] = $value;
                }
                Yii::$app->db->createCommand()->batchInsert('user_address', $keys, $vals)->execute();
            }

            $image = new Images;
            if ($image->imageFiles = UploadedFile::getInstances($this, 'imageFiles')) {
                if ($this->image) {
                    $this->image->removeImageSize();
                }
                $image->uploadPhoto($this->id, 'user');
            }
            return $this;
        }

        return false;
    }

    public function saveCode($phone = null)
    {
        $code = new SmsCode;

        $current_phone = $phone ? $phone : $this->phone;
        $model = SmsCode::find()->where(['phone' => $current_phone]);

        if ($this->id) {
            $model->andWhere(['user_id' => $this->id]);
            $code->user_id = $this->id;
        }

        $model = $model->one();

        if ($model) {
            $model->delete();
        }

        $code->phone = $current_phone;
        $code->code = (string)$this->generateCode($current_phone);
        // $code->code = '000000';
        $code->sms_expire = strtotime('+30 seconds');
        $code->token = $this->generateToken();

        if ($code->save()) {
            $phone = mb_substr($code->phone, 1);

            $service = new SMSCService();
            $service->send($phone, "Ваш код: " . $code->code . "\nНе передавайте ваш код другим лицам!");

            // $service = new SmsService;
            //  $service->request($phone, "Miss Lighting\nВаш код: ".$code->code."\nНе передавайте ваш код другим лицам!");
            return $code;
        }

        return false;
    }

    public function generateFileName()
    {
        return time() + mt_rand(0, 1000000);
    }

    public function removeObject()
    {
        if ($this->image && $this->image->delete()) {
            $this->image->removeImageSize();
        }

        return $this->delete();
    }

    public function generateToken()
    {
        return Yii::$app->security->generateRandomString();
    }

    public function login()
    {
        if ($this->email) {
            $user = $this->findByUsername($this->email);
        }

        if ($this->login) {
            $user = $this->findByUsername($this->login);
        }

        if ($this->phone) {
            $user = $this->findByUsername($this->phone);
        }

        if ($this->facebook_id) {
            $user = $this->findByUsername($this->facebook_id);
        }

        if ($this->vk_id) {
            $user = $this->findByUsername($this->vk_id);
        }

        if ($this->google_id) {
            $user = $this->findByUsername($this->google_id);
        }

        if ($user && Yii::$app->user->login($user, $this->remember ? 3600 * 24 * 30 : 0)) {
            return $user;
        }
        return false;
    }

    public function getRoleLabel()
    {
        return self::ROLE_LABELS[$this->role] ?? 'Неизвестно';
    }

    public function getRoleColor()
    {
        return self::ROLE_COLORS[$this->role] ?? 'bg-gray';
    }

    public function getRoleBadge()
    {
        return '<small class="label ' . $this->getRoleColor() . '">' . $this->getRoleLabel() . '</small>';
    }

    public function getPhoto($size = 'original')
    {
        return $this->image?->getPhoto('user', $size) ?? self::PHOTO_DEFAULT;
    }

    public function existPhoto()
    {
        return $this->image ? $this->image : false;
    }

    public function fields()
    {
        $controller = Yii::$app->controller->id;
        $action = Yii::$app->controller->action->id;

        return ['id', 'device_id', 'token', 'name', 'lastname', 'middlename', 'phone', 'email', 'gender', 'birthday', 'photo', 'type', 'inn', 'account', 'bank', 'oked', 'okohx', 'mfo', 'addresses', 'date', 'last_address', 'organization_name'];
    }

    // relations
    public function getImage()
    {
        return $this->hasOne(Images::className(), ['object_id' => 'id'])->andOnCondition(['type' => 'user', 'main' => 1]);
    }

    public function getModeratorAccess()
    {
        return $this->hasMany(ModeratorAccess::className(), ['user_id' => 'id']);
    }

    public function getAddresses()
    {
        return $this->hasMany(UserAddress::className(), ['user_id' => 'id']);
    }

    public function getShop()
    {
        return $this->hasOne(Shop::className(), ['user_id' => 'id']);
    }
}
