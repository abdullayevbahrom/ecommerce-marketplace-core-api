<?php

namespace app\modules\api\controllers;

use Yii;
use yii\rest\Controller;
use yii\web\HttpException;
use yii\web\UploadedFile;
use yii\data\ActiveDataProvider;
use yii\filters\auth\HttpBearerAuth;
use app\models\user\User;
use app\models\user\SmsCode;
use app\models\user\card\UserCard;
use app\models\user\address\UserAddress;
use app\models\Images;
use app\models\Category;
use app\models\session\WebSession;
use app\services\Sms\Sms;
use yii\caching\FileCache;
use app\services\DidoxService;
use app\modules\api\components\ErrorCodes;
use app\modules\api\components\ApiResponseTrait;

class UserController extends Controller
{
    use ApiResponseTrait;

    public function beforeAction($action)
    {
        $this->enableCsrfValidation = false;

        Yii::$app->response->getHeaders()->add('Access-Control-Allow-Origin', '*');
        Yii::$app->response->getHeaders()->add('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, HEAD, OPTIONS');
        Yii::$app->response->getHeaders()->add('Access-Control-Allow-Headers', 'Content-Type, X-Auth-Token, Origin, Authorization');

        if (Yii::$app->request->headers->has('OPTIONS')) {
            throw new HttpException(200, 'OK');
        }

        return parent::beforeAction($action);
    }

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::class,
            'optional' => [
                'sign-up',
                'sign-in',
                'log-out',
                'send-code',
                'send-sms',
                'recover-password',
                'accept-recover-code',
                'send-phone',
                'eimzo-auth',
                'eimzo-register',
                'eimzo-login'
            ]
        ];

        $auth = $behaviors['authenticator'];
        unset($behaviors['authenticator']);

        $behaviors['corsFilter'] = [
            'class' => \yii\filters\Cors::class,
            'cors' => [
                'Access-Control-Allow-Origin' => ['*'],
                'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'],
                'Access-Control-Request-Headers' => ['*'],
                'Access-Control-Allow-Credentials' => true,
                'Access-Control-Max-Age' => 86400,
                'Access-Control-Expose-Headers' => [],
            ]
        ];

        $behaviors['authenticator'] = $auth;
        $behaviors['authenticator']['except'] = ['options'];

        return $behaviors;
    }

    public $serializer = [
        'class' => 'yii\rest\Serializer',
        'collectionEnvelope' => 'data',
    ];

    public function actionSendPhone()
    {
        try {
            $post = Yii::$app->request->post();
            Yii::info('POST data: ' . json_encode($post), 'app');
            $phone = $post['phone'] ? preg_replace('/[^\d]/', '', trim($post['phone'])) : null;

            if (empty($phone) || strlen($phone) !== 12) {
                return $this->sendError(ErrorCodes::ERROR_VALIDATION, 'Заполните поле', ['phone' => ['Заполните поле']]);
            }

            $model = User::find()->where(['phone' => $phone])->one();

            if (!$model) {
                $model = new User();
                $model->role = User::ROLE_USER;
                $model->type = 'fiz';
                Yii::info('Creating new user for phone: ' . $phone, 'app');
            } else {
                Yii::info('Found existing user: ' . $model->id, 'app');
            }

            $model->status = 1;
            $model->phone_code = '123456';
            $model->phone = $phone;
            $model->token = '';
            $model->sms_live = strtotime('+3 minute');

            if (!$model->validate()) {
                Yii::error('Validation errors: ' . json_encode($model->errors), 'app');

                return $this->sendError(ErrorCodes::ERROR_VALIDATION, 'Validation failed', $model->errors);
            }

            if ($model->save()) {
                Yii::info('User saved successfully: ' . $model->id, 'app');

                // SMS service (commented out for now)
                // $service = new Sms;
                // $service->send($phone, $model->phone_code);

                return $this->sendSuccess([
                    'user_id' => $model->id,
                    'message' => 'Код подтверждения отправлен на указанный номер.'
                ], 'Код подтверждения отправлен на указанный номер.');
            } else {
                Yii::error('Save failed: ' . json_encode($model->errors), 'app');

                return $this->sendError(ErrorCodes::ERROR_USER_SAVE_FAILED, 'Не удалось сохранить пользователя: ' . json_encode($model->errors), $model->errors);
            }
        } catch (HttpException $e) {
            throw $e;
        } catch (\Exception $e) {
            Yii::error('Unexpected error in actionSendPhone: ' . $e->getMessage(), 'app');
            Yii::error('Stack trace: ' . $e->getTraceAsString(), 'app');

            return $this->sendError(ErrorCodes::ERROR_SERVER, 'Внутренняя ошибка сервера');
        }
    }

    public function actionSendCode()
    {
        $post = Yii::$app->request->post();
        $code = $post['code'] ? preg_replace('/[^\d]/', '', trim($post['code'])) : null;
        $userId = $post['user_id'] ? preg_replace('/[^\d]/', '', trim($post['user_id'])) : null;

        if (empty($code)) {
            return $this->sendError(ErrorCodes::ERROR_VALIDATION, 'Заполните поле', ['code' => ['Заполните поле']]);
        }

        if (empty($userId)) {
            return $this->sendError(ErrorCodes::ERROR_VALIDATION, 'Заполните поле', ['user_id' => ['Заполните поле']]);
        }

        $user = User::findOne($userId);

        if (!$user) {
            return $this->sendError(ErrorCodes::ERROR_USER_NOT_FOUND, 'Пользователь не найден', ['user' => ['Пользователь не найден']]);
        }

        if ($user->phone_code !== $code || time() > $user->sms_live) {
            return $this->sendError(ErrorCodes::ERROR_INVALID_CODE, 'Неверный или просроченный код подтверждения', ['code' => ['Неверный или просроченный код подтверждения']]);
        }

        $user->token = $user->generateToken();
        $user->status = 1;

        if (empty($user->type)) {
            $user->type = 'fiz';
        }
        $user->phone_code = null;
        $user->sms_live = null;

        if (!$user->save()) {
            return $this->sendError(
                ErrorCodes::ERROR_SERVER,
                'Произошла ошибка при сохранении пользователя.',
                ['server' => ['Ошибка сохранения']]
            );
        }

        $webSession = WebSession::createSession($user);

        $user = User::find()->with('image')->where(['id' => $user->id])->one();
        $userData = $user->toArray();
        $userData['bts_region_id'] = $user->bts_region_id;
        $userData['bts_city_id'] = $user->bts_city_id;
        $userData['bts_region_name'] = \yii\services\BTS::getRegionName($user->bts_region_id, $post['language'] ?? 'ru');
        $userData['bts_city_name'] = \yii\services\BTS::getCityName($user->bts_city_id, $post['language'] ?? 'ru');
        $userData['web_session_token'] = $webSession->access_token;

        return $this->sendSuccess($userData);
    }

    public function actionCheckCard()
    {
        $post = Yii::$app->request->post();
        $cardNumber = Yii::$app->request->post('card_number');

        if (empty($cardNumber)) {
            return ['errors' => ['Number empty']];
        }

        $cache = new FileCache();
        $cardBIN = substr($cardNumber, 0, 6);
        $bankDetails = $cache->get($cardBIN);

        if ($bankDetails === false) {
            try {
                $bankDetails = json_decode(file_get_contents("https://lookup.binlist.net/" . trim($cardBIN)), true);
                $bankDetails['bin'] = $cardBIN;
                $cache->set($cardBIN, $bankDetails, 86400);
            } catch (\Exception $e) {
                Yii::error("Ошибка при получении данных из binlist: " . $e->getMessage(), __METHOD__);
                $bankDetails = ['error' => 'Card check server not working'];
            }
        }
        return $bankDetails;
    }

    public function actionRecoverPassword()
    {
        $post = Yii::$app->request->post();
        $phone = $post['phone'] ? preg_replace('/[^\d]/', '', trim($post['phone'])) : null;

        if (!array_key_exists('phone', $post) || empty($phone)) {
            Yii::$app->response->statusCode = 422;
            return ['errors' => ['phone' => ['Заполните поле']]];
        }

        $check_phone = User::findOne(['phone' => $phone]);

        if (!$check_phone) {
            Yii::$app->response->statusCode = 404;
            return ['errors' => ['phone' => ['Номер телефона не найден']]];
        }

        $check_phone->saveCode($phone);

        return ['data' => ['message' => 'Код для восстановления пароля отправлен на ваш номер.']];
    }

    public function actionAcceptRecoverCode()
    {
        $post = Yii::$app->request->post();

        $phone = $post['phone'] ? preg_replace('/[^\d]/', '', trim($post['phone'])) : null;
        $code = $post['code'] ? preg_replace('/[^\d]/', '', trim($post['code'])) : null;

        if (!array_key_exists('phone', $post) || empty($phone)) {
            Yii::$app->response->statusCode = 422;
            return ['errors' => ['phone' => ['Заполните поле']]];
        }

        if (!array_key_exists('code', $post) || empty($code)) {
            Yii::$app->response->statusCode = 422;
            return ['errors' => ['code' => ['Заполните поле']]];
        }

        $sms_code = SmsCode::findOne(['phone' => $phone, 'code' => $code]);

        if (!$sms_code) {
            Yii::$app->response->statusCode = 422;
            return ['errors' => ['code' => ['Неверный код подтверждения']]];
        }

        $user = User::findOne(['phone' => $phone]);

        if (!$user) {
            Yii::$app->response->statusCode = 404;
            return ['errors' => ['phone' => ['Пользователь не найден']]];
        }

        // Generate a temporary random password (hashed) - user must set a new one
        $tempPassword = Yii::$app->security->generateRandomString(16);
        $user->password = Yii::$app->security->generatePasswordHash($tempPassword);
        $user->status = 1;
        $user->save(false);

        return ['data' => ['phone' => $phone]];
    }

    // public function actionSignUp()
    // {
    //     $post = Yii::$app->request->post();
    //     $phone = $post['phone'] ? preg_replace('/[^\d]/', '', trim($post['phone'])) : null;

    //     $model = User::findOne(['phone' => $phone]);
    //     if (!$model) {
    //         $model = new User;
    //         $model->password = 1;
    //         $model->role = User::ROLE_USER;
    //     }

    //     $model->scenario = User::USER_SIGNUP;
    //     $model->setAttributes($post);
    //     $model->phone = $phone;

    //     if (!$model->validate()) {
    //         Yii::$app->response->statusCode = 422;
    //         return ['errors' => $model->errors];
    //     }

    //     $model->status = 0;

    //     if ($model->save(false)) {
    //         $code = $model->saveCode();
    //         return ['data' => ['token' => $code->token]];
    //     } else {
    //         Yii::$app->response->statusCode = 422;
    //         return ['errors' => $model->errors];
    //     }

    //     return false;
    // }

    // public function actionSignIn()
    // {
    //     $post = Yii::$app->request->post();
    //     $phone = $post['phone'] ? preg_replace('/[^\d]/', '', trim($post['phone'])) : null;
    //     $user = new User;
    //     $user->scenario = User::USER_SIGNIN;
    //     $user->setAttributes($post);

    //     if (!$user->validate()) {
    //         Yii::$app->response->statusCode = 422;
    //         return ['errors' => $user->errors];
    //     }

    //     $model = User::findOne(['phone' => $phone]);

    //     if ($model->save(false)) {
    //         $code = $model->saveCode();
    //         return ['data' => ['token' => $code->token]];
    //     } else {
    //         Yii::$app->response->statusCode = 422;
    //         return ['errors' => $model->errors];
    //     }
    // }

    public function actionLogOut()
    {
        /** @var User $model */
        $model = Yii::$app->user->identity;

        if (!$model) {
            Yii::$app->response->statusCode = 404;
            return ['errors' => ['code' => ['Пользователь не найден']]];
        }
        $model->token = '';
        if ($model->save()) {
            Yii::$app->user->logout();
            Yii::$app->response->statusCode = 200;
            return;
        }

        Yii::$app->response->statusCode = 500;

        return ['errors' => ['server' => ['Не удалось сохранить изменения']]];
    }

    public function actionIndex()
    {
        $query = User::find()->with('image', 'addresses')->where(['role' => User::ROLE_USER]);

        return new ActiveDataProvider([
            'query' => $query,
            'sort' => ['defaultOrder' => ['id' => 'desc']]
        ]);
    }

    public function actionProfile()
    {
        $user = User::find()->with('image', 'addresses')->where(['id' => Yii::$app->user->identity->id])->one();

        // Get base user data
        $userData = $user->toArray();

        // Ensure BTS ID fields are included
        $userData['bts_region_id'] = $user->bts_region_id;
        $userData['bts_city_id'] = $user->bts_city_id;

        // Add BTS region and city names for convenience
        if ($user->bts_region_id) {
            $userData['bts_region_name'] = \yii\services\BTS::getRegionName($user->bts_region_id, 'ru');
        } else {
            $userData['bts_region_name'] = null;
        }

        if ($user->bts_city_id) {
            $userData['bts_city_name'] = \yii\services\BTS::getCityName($user->bts_city_id, 'ru');
        } else {
            $userData['bts_city_name'] = null;
        }

        return ['data' => $userData];
    }

    public function actionUpdate()
    {
        $post = Yii::$app->request->post();
        $user = Yii::$app->user->identity;
        $language = Yii::$app->request->get('language') ?? 'ru';

        $model = User::findOne(['id' => $user->id]);
        $password = $model->password;
        $model->scenario = User::USER_UPDATE;

        $model->setAttributes($post);
        // $model->password = 1;
        if (!$model->validate()) {
            return $this->sendError(ErrorCodes::ERROR_VALIDATION, 'Validation error', $model->errors);
        }

        if ($model->save()) {
            $image = new Images;
            if ($model->image) {
                $image = $model->image;
            }
            if ($image->imageFiles[] = UploadedFile::getInstanceByName('photo')) {
                $image->uploadPhoto($model->id, 'user');
            }

            if ($model->address) {
                UserAddress::deleteAll('user_id = :user_id', ['user_id' => $model->id]);

                $keys = ['user_id', 'address'];
                $vals = [];
                foreach ($model->address as $key => $value) {
                    if ($value) {
                        $vals[] = [
                            'user_id' => $model->id,
                            'address' => $value
                        ];
                    }
                }

                Yii::$app->db->createCommand()->batchInsert('user_address', $keys, $vals)->execute();
            }

            $user = User::find()->with('image', 'addresses')->where(['id' => Yii::$app->user->identity->id])->one();

            // Get base user data
            $userData = $user->toArray();

            // Ensure BTS ID fields are included
            $userData['bts_region_id'] = $user->bts_region_id;
            $userData['bts_city_id'] = $user->bts_city_id;

            // Add BTS region and city names for convenience
            if ($user->bts_region_id) {
                $userData['bts_region_name'] = \yii\services\BTS::getRegionName($user->bts_region_id, $language);
            } else {
                $userData['bts_region_name'] = null;
            }

            if ($user->bts_city_id) {
                $userData['bts_city_name'] = \yii\services\BTS::getCityName($user->bts_city_id, $language);
            } else {
                $userData['bts_city_name'] = null;
            }

            return $this->sendSuccess($userData);
        } else {
            return $this->sendError(ErrorCodes::ERROR_VALIDATION, 'Validation error', $model->errors);
        }
    }

    public function actionChangePassword()
    {
        $user = Yii::$app->user->identity;
        $post = Yii::$app->request->post();

        if (!$post['password_current']) {
            Yii::$app->response->statusCode = 422;
            return ['errors' => ['password_current' => 'Введите текущий пароль']];
        }

        if (!$post['password_new']) {
            Yii::$app->response->statusCode = 422;
            return ['errors' => ['password_new' => 'Введите новый пароль']];
        }

        if (!$post['password_compare']) {
            Yii::$app->response->statusCode = 422;
            return ['errors' => ['password_compare' => 'Подтвердите новый пароль']];
        }

        $user = User::findOne(Yii::$app->user->identity->id);

        if (!Yii::$app->security->validatePassword($post['password_current'], $user->password)) {
            Yii::$app->response->statusCode = 422;
            return ['errors' => ['password_current' => 'Текущий пароль введен не верно']];
        }

        if ($post['password_new'] != $post['password_compare']) {
            Yii::$app->response->statusCode = 422;
            return ['errors' => ['password_compare' => 'Пароли не совпадают']];
        }

        $user->password = Yii::$app->security->generatePasswordHash($post['password_new']);
        $user->save(false);

        return $user;
    }

    public function actionChangePhone()
    {
        /** @var User $user */
        $user = Yii::$app->user->identity;
        $post = Yii::$app->request->post();
        $phone = $post['phone'] ? preg_replace('/[^\d]/', '', trim($post['phone'])) : null;

        if (!array_key_exists('phone', $post) || empty($phone)) {
            Yii::$app->response->statusCode = 422;
            return ['errors' => ['phone' => ['Заполните поле']]];
        }

        if (User::find()->where(['phone' => $phone])->andWhere(['!=', 'id', $user->id])->exists()) {
            Yii::$app->response->statusCode = 422;
            return ['errors' => ['phone' => ['Этот номер телефона уже используется.']]];
        }

        $user->saveCode($phone);

        return ['data' => ['message' => 'Код для подтверждения нового номера отправлен.']];
    }

    public function actionAcceptChangeCode()
    {
        $user = Yii::$app->user->identity;
        $post = Yii::$app->request->post();
        $phone = $post['phone'] ? preg_replace('/[^\d]/', '', trim($post['phone'])) : null;
        $code = $post['code'] ? preg_replace('/[^\d]/', '', trim($post['code'])) : null;

        if (!array_key_exists('phone', $post) || empty($phone)) {
            Yii::$app->response->statusCode = 422;
            return ['errors' => ['phone' => ['Заполните поле']]];
        }

        if (!array_key_exists('code', $post) || empty($code)) {
            Yii::$app->response->statusCode = 422;
            return ['errors' => ['code' => ['Заполните поле']]];
        }

        $sms_code = SmsCode::findOne(['phone' => $phone, 'code' => $code]);

        if (!$sms_code) {
            Yii::$app->response->statusCode = 422;
            return ['errors' => ['code' => ['Неверный код подтверждения']]];
        }

        $user = User::findOne($user->id);

        if (!$user) {
            Yii::$app->response->statusCode = 404;
            return ['errors' => ['phone' => ['Пользователь не найден']]];
        }

        $user->phone = $phone;
        $user->save(false);

        $user = User::findOne($user->id);

        return $user;
    }

    public function actionAddressRemove()
    {
        $user = Yii::$app->user->identity;
        $post = Yii::$app->request->post();

        if (!$post['address_id']) {
            Yii::$app->response->statusCode = 422;
            return ['errors' => ['address_id' => 'Введите ID адреса']];
        }

        $model = UserAddress::findOne(['id' => $post['address_id'], 'user_id' => $user->id]);

        if (!$model) {
            Yii::$app->response->statusCode = 404;
            return ['errors' => ['address_id' => 'Адрес не найден']];
        }

        $model->delete();

        $user = User::find()->with('image', 'addresses')->where(['id' => $user->id])->one();
        return $user;
    }

    public function actionRemovePhoto()
    {
        $model = User::find()->with('image')->where(['id' => Yii::$app->user->identity->id])->one();

        if ($model && $model->image) {
            $model->image->removeImageSize();
        }

        return ['data' => $model];
    }

    public function actionRemoveAccount()
    {
        $model = User::find()->with('image')->where(['id' => Yii::$app->user->identity->id])->one();

        if ($model) {
            if ($model->image) {
                $model->image->removeImageSize();
            }
            $model->delete();
        }

        throw new HttpException(200, 'OK');
    }

    public function actionUploadPhoto()
    {
        /** @var User $user */
        $user = Yii::$app->user->identity ?? null;

        if (!$user) {
            Yii::$app->response->statusCode = 404;
            return ['errors' => ['user' => 'Пользователь не найден']];
        }

        $user = User::find()->with('image')->where(['id' => $user?->id])->one();

        $image = new Images;

        if ($user->image) {
            $image = $user->image;
        }
        if ($image->imageFiles[] = UploadedFile::getInstanceByName('photo')) {
            $image->uploadPhoto($user->id, 'user');
        }

        $user = User::find()->with('image')->where(['id' => Yii::$app->user->identity->id])->one();

        return ['data' => $user];
    }

    public function actionSetRate()
    {
        $model = new Cbu();

        $cbu_uz = $model->getOneByDate('USD', date('Y-m-d'));
        @file_put_contents('cbu_uz.txt', $cbu_uz['rate']);
    }

    public function actionCards()
    {
        $user = Yii::$app->user->identity;

        $query = UserCard::find()->with('cardType')->where(['status' => 1])->andWhere(['user_id' => $user->id]);

        $perPage = Yii::$app->request->get('per-page') ? Yii::$app->request->get('per-page') : 12;

        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => $perPage,
                'validatePage' => false
            ],
            'sort' => ['defaultOrder' => ['id' => 'desc']]
        ]);
    }

    public function actionCardAdd()
    {
        $user = Yii::$app->user->identity;
        $post = Yii::$app->request->post();

        $card = new UserCard;
        $card->setAttributes($post);

        if (!$card->validate()) {
            Yii::$app->response->statusCode = 422;
            return ['errors' => $card->errors];
        }

        $card->saveObject();

        $card = UserCard::find()->with('cardType')->where(['id' => $card->id, 'user_id' => $user->id])->one();

        return ['data' => $card];
    }

    public function actionCardDetail($card_id)
    {
        $user = Yii::$app->user->identity;
        $card = UserCard::find()->with('cardType')->where(['id' => $card_id, 'user_id' => $user->id])->one();

        return ['data' => $card];
    }

    public function actionCardRemove()
    {
        $user = Yii::$app->user->identity;
        $post = Yii::$app->request->post();

        if (!array_key_exists('card_id', $post)) {
            Yii::$app->response->statusCode = 422;
            return ['date' => ['errors' => ['card_id' => 'Введите ID карты']]];
        }

        $card = UserCard::find()->with('cardType')->where(['id' => $post['card_id'], 'user_id' => $user->id])->one();

        if (!$card) {
            Yii::$app->response->statusCode = 404;
            return ['date' => ['errors' => ['card_id' => 'Карта не найдена']]];
        }

        $card->delete();

        $query = UserCard::find()->with('cardType')->where(['status' => 1])->andWhere(['user_id' => $user->id]);

        $perPage = Yii::$app->request->get('per-page') ? Yii::$app->request->get('per-page') : 12;

        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => $perPage,
                'validatePage' => false
            ],
            'sort' => ['defaultOrder' => ['id' => 'desc']]
        ]);
    }

    // E-IMZO authentication methods

    /**
     * Authenticate user with E-IMZO token from Didox
     * This endpoint is for users who are already registered in Didox but may be new to our platform
     * Assumes user has completed Didox registration and has a valid Didox token
     * Supports user type (fiz/yur) - defaults to 'fiz' if not specified
     */
    public function actionEimzoAuth()
    {
        $post = Yii::$app->request->post();

        // Validate required fields
        if (!isset($post['didox_token'])) {
            Yii::$app->response->statusCode = 422;
            return ['errors' => ['didox_token' => 'Didox token is required']];
        }

        if (!isset($post['tax_id'])) {
            Yii::$app->response->statusCode = 422;
            return ['errors' => ['tax_id' => 'Tax ID is required']];
        }

        // Set user type - default to 'fiz' if not provided
        $userType = isset($post['user_type']) && in_array($post['user_type'], ['fiz', 'yur']) ? $post['user_type'] : 'fiz';

        try {
            $didoxService = new DidoxService();

            // Step 1: Authenticate with Didox using the provided token
            $didoxAuth = $didoxService->authenticateWithDidox($post['didox_token'], $post['tax_id']);

            if (!$didoxAuth['valid']) {
                Yii::$app->response->statusCode = 401;
                return ['errors' => ['didox_token' => 'Didox authentication failed: ' . (isset($didoxAuth['error']) ? $didoxAuth['error'] : 'Invalid token')]];
            }

            // Step 2: Get profile data from DIDOX
            $profileResult = $didoxService->getUserProfile($post['didox_token']);
            $profileData = [];
            if ($profileResult['success'] && !empty($profileResult['data'])) {
                $profileData = $profileResult['data'];
            }

            // Step 3: Check if user exists in our platform
            $user = User::findOne(['eimzo_tax_id' => $post['tax_id']]);

            error_log('user123: ' . print_r($user, true));
            error_log('profileData: ' . print_r($profileData, true));

            if (!$user) {
                // User doesn't exist in our platform - create new user
                $user = new User();
                $user->eimzo_tax_id = $post['tax_id'];
                $user->role = User::ROLE_USER;
                $user->status = 1;
                $user->password = Yii::$app->security->generatePasswordHash(
                    Yii::$app->security->generateRandomString(16)
                ); // Random secure password for E-IMZO auth users
                $user->type = $userType; // Set user type (fiz/yur)

                // Set fields from DIDOX profile data
                if (!empty($profileData)) {
                    // For companies (yur) use company name, for individuals (fiz) try to extract name
                    if ($userType === 'yur') {
                        $user->name = $profileData['fullName'] ?? $profileData['name'] ?? $profileData['shortName'] ?? '';
                        $user->organization_name = $profileData['fullName'] ?? $profileData['name'] ?? '';
                    } else {
                        // For individuals, try to split director name or fullName
                        $fullName = $profileData['director'] ?? $profileData['fullName'] ?? '';
                        if (!empty($fullName)) {
                            $nameParts = explode(' ', trim($fullName));
                            $user->lastname = $nameParts[0] ?? '';
                            $user->name = $nameParts[1] ?? '';
                            $user->middlename = trim(($nameParts[2] ?? '') . ' ' . ($nameParts[3] ?? ''));
                        }
                    }

                    // Set contact information
                    if (isset($profileData['email'])) {
                        $user->email = $profileData['email'];
                    }

                    // Set business information
                    if (isset($profileData['address'])) {
                        $user->address = $profileData['address'];
                        $user->address_legal = $profileData['address']; // Legal address same as main address
                    }
                    if (isset($profileData['oked'])) {
                        $user->oked = $profileData['oked'];
                    }
                    if (isset($profileData['account'])) {
                        $user->account = $profileData['account'];
                    }
                    if (isset($profileData['mfo'])) {
                        $user->mfo = $profileData['mfo'];
                    }
                    if (isset($profileData['bankCode'])) {
                        $user->bank = $profileData['bankCode'];
                    }
                    if (isset($profileData['vatRegCode'])) {
                        $user->inn = $profileData['vatRegCode'];
                    }

                    // Set manager information (director or accountant)
                    if (isset($profileData['director']) && !empty($profileData['director'])) {
                        $user->manager = $profileData['director'];
                    } elseif (isset($profileData['accountant']) && !empty($profileData['accountant'])) {
                        $user->manager = $profileData['accountant'];
                    }
                }

                // Override with explicitly provided data if available
                if (isset($post['name'])) {
                    $user->name = $post['name'];
                }
                if (isset($post['lastname'])) {
                    $user->lastname = $post['lastname'];
                }
                if (isset($post['middlename'])) {
                    $user->middlename = $post['middlename'];
                }
                if (isset($post['email'])) {
                    $user->email = $post['email'];
                }
                if (!empty($post['phone']) && $phone = preg_replace('/[^\d]/', '', trim($post['phone'])) && strlen(preg_replace('/[^\d]/', '', trim($post['phone']))) === 12) {
                    $user->phone = $phone;
                }
                if (isset($post['organization_name'])) {
                    $user->organization_name = $post['organization_name'];
                }

                Yii::info('Creating new platform user for existing Didox user with Tax ID: ' . $post['tax_id'], __METHOD__);
            } else {
                // User exists - update with profile data
                Yii::info('Authenticating existing platform user with Tax ID: ' . $post['tax_id'], __METHOD__);

                // Update fields from DIDOX profile data if they're empty
                if (!empty($profileData)) {
                    if (empty($user->email) && isset($profileData['email'])) {
                        $user->email = $profileData['email'];
                    }
                    if (empty($user->phone)) {
                        if (isset($profileData['mobile'])) {
                            $user->phone = $profileData['mobile'];
                        } elseif (isset($profileData['phone'])) {
                            $user->phone = $profileData['phone'];
                        }
                    }
                    if (empty($user->organization_name) && isset($profileData['fullName'])) {
                        $user->organization_name = $profileData['fullName'];
                    }
                    if (empty($user->address) && isset($profileData['address'])) {
                        $user->address = $profileData['address'];
                    }
                    if (empty($user->address_legal) && isset($profileData['address'])) {
                        $user->address_legal = $profileData['address'];
                    }
                    if (empty($user->oked) && isset($profileData['oked'])) {
                        $user->oked = $profileData['oked'];
                    }
                    if (empty($user->account) && isset($profileData['account'])) {
                        $user->account = $profileData['account'];
                    }
                    if (empty($user->mfo) && isset($profileData['mfo'])) {
                        $user->mfo = $profileData['mfo'];
                    }
                    if (empty($user->bank) && isset($profileData['bankCode'])) {
                        $user->bank = $profileData['bankCode'];
                    }
                    if (empty($user->inn) && isset($profileData['vatRegCode'])) {
                        $user->inn = $profileData['vatRegCode'];
                    }
                    if (empty($user->manager)) {
                        if (isset($profileData['director']) && !empty($profileData['director'])) {
                            $user->manager = $profileData['director'];
                        } elseif (isset($profileData['accountant']) && !empty($profileData['accountant'])) {
                            $user->manager = $profileData['accountant'];
                        }
                    }
                }
            }

            // Step 4: Update user with latest E-IMZO data
            $user->eimzo_didox_token = $post['didox_token'];
            $user->eimzo_last_login = date('Y-m-d H:i:s');

            // Store DIDOX profile data
            if (!empty($profileData)) {
                $user->eimzo_certificate_info = json_encode($profileData);
            }

            // Generate or refresh our app token
            $user->token = $user->generateToken();

            // Ensure required fields are set to prevent save errors
            if (empty($user->phone) && isset($post['mobile'])) {
                $user->phone = $post['mobile'];
            }
            if (empty($user->status)) {
                $user->status = 1; // Active status
            }
            if (empty($user->role)) {
                $user->role = User::ROLE_USER;
            }
            if (empty($user->date)) {
                $user->date = date('Y-m-d H:i:s');
            }
            if (empty($user->ip)) {
                $user->ip = Yii::$app->request->getUserIP() ?? '127.0.0.1';
            }

            if ($user->save(false)) {
                // Return user object directly in data field (matching standard API format)
                return ['data' => User::find()->with('image')->where(['id' => $user->id])->one()];
            } else {
                // Log validation errors for debugging
                Yii::error('User save failed with errors: ' . json_encode($user->getErrors()), __METHOD__);
                throw new HttpException(500, 'Failed to save user data: ' . implode(', ', array_map(function ($errors) {
                    return implode(', ', $errors);
                }, $user->getErrors())));
            }
        } catch (HttpException $e) {
            throw $e;
        } catch (\Exception $e) {
            Yii::error('E-IMZO auth error: ' . $e->getMessage(), __METHOD__);
            Yii::$app->response->statusCode = 500;
            return ['errors' => ['service' => 'Authentication service error: ' . $e->getMessage()]];
        }
    }

    /**
     * Register a new user with E-IMZO and Didox following official Didox documentation
     * This endpoint is for new users who haven't registered before
     * Supports user type (fiz/yur) - defaults to 'fiz' if not specified
     * 
     * Required fields according to Didox API:
     * - tax_id: Tax ID (INN) from certificate
     * - email: User email (mandatory)
     * - mobile: Phone number (mandatory) 
     * - password: Password for Didox account (mandatory)
     * - pkcs7_64: PKCS7 signature for timestamp
     * - signature_hex: Signature in hex format for timestamp
     * - final_signature: Final signed INN with timestamp (base64)
     * Optional fields:
     * - user_type: 'fiz' or 'yur' (defaults to 'fiz')
     */
    public function actionEimzoRegister()
    {
        $post = Yii::$app->request->post();

        // Validate required fields according to Didox documentation
        $requiredFields = ['tax_id', 'email', 'mobile', 'password', 'pkcs7_64', 'signature_hex', 'final_signature'];
        foreach ($requiredFields as $field) {
            if (!isset($post[$field]) || empty($post[$field])) {
                Yii::$app->response->statusCode = 422;
                return ['errors' => [$field => ucfirst(str_replace('_', ' ', $field)) . ' is required']];
            }
        }

        // Set user type - default to 'fiz' if not provided
        $userType = isset($post['user_type']) && in_array($post['user_type'], ['fiz', 'yur']) ? $post['user_type'] : 'fiz';

        // Check if user already exists in our system
        $existingUser = User::findOne(['eimzo_tax_id' => $post['tax_id']]);
        if ($existingUser) {
            Yii::$app->response->statusCode = 422;
            return ['errors' => ['tax_id' => 'User already exists. Please use login endpoint instead.']];
        }

        try {
            $didoxService = new DidoxService();

            // Step 1: Create timestamp signature according to Didox documentation
            $timestampResult = $didoxService->createTimestamp($post['pkcs7_64'], $post['signature_hex']);

            if (!$timestampResult['success']) {
                Yii::$app->response->statusCode = 500;
                return ['errors' => ['timestamp' => 'Failed to create timestamp: ' . (isset($timestampResult['error']) ? $timestampResult['error'] : 'Unknown error')]];
            }

            // Step 2: Prepare Didox registration data with mandatory fields
            $didoxRegistrationData = [
                'email' => $post['email'],
                'mobile' => $post['mobile'],
                'password' => $post['password'],
                'accept' => true, // User accepts the terms (mandatory)
                'signature' => $post['final_signature'] // Signed INN with attached timestamp in base64
            ];

            // Step 3: Register user with Didox
            $registrationResult = $didoxService->registerUser($didoxRegistrationData);

            if (!$registrationResult['success']) {
                if ($registrationResult['userExists']) {
                    Yii::$app->response->statusCode = 422;
                    return ['errors' => ['tax_id' => 'User already exists in Didox. Please use login endpoint.']];
                } else {
                    Yii::$app->response->statusCode = 500;
                    return ['errors' => ['didox' => 'Didox registration failed: ' . (isset($registrationResult['error']) ? $registrationResult['error'] : 'Unknown error')]];
                }
            }

            // Step 4: Extract certificate information if provided
            $certificateInfo = [];
            if (isset($post['certificate_info'])) {
                $certificateInfo = $didoxService->extractCertificateInfo($post['certificate_info']);
            }

            // Step 5: Create new user in our system
            $user = new User();
            $user->eimzo_tax_id = $post['tax_id'];
            $user->role = User::ROLE_USER;
            $user->status = 1;
            $user->password = Yii::$app->security->generatePasswordHash(
                Yii::$app->security->generateRandomString(16)
            ); // Random secure password for E-IMZO auth users
            $user->email = $post['email']; // Set from mandatory field
            $user->phone = $post['mobile']; // Set from mandatory field
            $user->type = $userType; // Set user type (fiz/yur)

            // Set fields from certificate info
            if (!empty($certificateInfo)) {
                if (isset($certificateInfo['first_name'])) {
                    $user->name = $certificateInfo['first_name'];
                }
                if (isset($certificateInfo['last_name'])) {
                    $user->lastname = $certificateInfo['last_name'];
                }
                if (isset($certificateInfo['middle_name'])) {
                    $user->middlename = $certificateInfo['middle_name'];
                }
                if (isset($certificateInfo['full_name'])) {
                    $user->name = $certificateInfo['full_name'];
                } elseif (isset($certificateInfo['common_name'])) {
                    $user->name = $certificateInfo['common_name'];
                }

                if (isset($certificateInfo['birth_date'])) {
                    $user->birthday = $certificateInfo['birth_date'];
                }

                if (isset($certificateInfo['gender'])) {
                    $user->gender = $certificateInfo['gender'];
                }

                if (isset($certificateInfo['organization_name'])) {
                    $user->organization_name = $certificateInfo['organization_name'];
                }
            }

            // Override with explicitly provided data if available
            if (isset($post['name'])) {
                $user->name = $post['name'];
            }
            if (isset($post['lastname'])) {
                $user->lastname = $post['lastname'];
            }
            if (isset($post['middlename'])) {
                $user->middlename = $post['middlename'];
            }
            if (isset($post['organization_name'])) {
                $user->organization_name = $post['organization_name'];
            }

            // Step 6: Store registration data
            $user->eimzo_didox_token = isset($registrationResult['data']['token']) ? $registrationResult['data']['token'] : null;
            $user->eimzo_last_login = date('Y-m-d H:i:s');

            // Store certificate info
            if (!empty($certificateInfo)) {
                $user->eimzo_certificate_info = json_encode($certificateInfo);
            }

            // Generate app token
            $user->token = $user->generateToken();

            // Ensure required fields are set to prevent save errors
            if (empty($user->date)) {
                $user->date = date('Y-m-d H:i:s');
            }
            if (empty($user->ip)) {
                $user->ip = Yii::$app->request->getUserIP() ?? '127.0.0.1';
            }

            if ($user->save(false)) {
                // Return user object directly in data field (matching standard API format)
                return ['data' => User::find()->with('image')->where(['id' => $user->id])->one()];
            } else {
                // Log validation errors for debugging
                Yii::error('User save failed with errors: ' . json_encode($user->getErrors()), __METHOD__);
                throw new HttpException(500, 'Failed to save user data: ' . implode(', ', array_map(function ($errors) {
                    return implode(', ', $errors);
                }, $user->getErrors())));
            }
        } catch (HttpException $e) {
            throw $e;
        } catch (\Exception $e) {
            Yii::error('E-IMZO registration error: ' . $e->getMessage(), __METHOD__);
            Yii::$app->response->statusCode = 500;
            return ['errors' => ['service' => 'Registration service error: ' . $e->getMessage()]];
        }
    }

    /**
     * Login existing user with E-IMZO
     * This endpoint is for existing users who have already registered
     */
    public function actionEimzoLogin()
    {
        $post = Yii::$app->request->post();

        // Validate required fields
        if (!isset($post['didox_token'])) {
            Yii::$app->response->statusCode = 422;
            return ['errors' => ['didox_token' => 'Didox token is required']];
        }

        if (!isset($post['tax_id'])) {
            Yii::$app->response->statusCode = 422;
            return ['errors' => ['tax_id' => 'Tax ID is required']];
        }

        try {
            $didoxService = new DidoxService();

            // Validate the token
            $tokenValidation = $didoxService->validateAndExtractTokenInfo($post['didox_token'], $post['tax_id']);

            if (!$tokenValidation['valid']) {
                Yii::$app->response->statusCode = 401;
                return ['errors' => ['token' => 'Invalid or expired Didox token: ' . (isset($tokenValidation['error']) ? $tokenValidation['error'] : 'Unknown error')]];
            }

            // Find existing user
            $user = User::findOne(['eimzo_tax_id' => $post['tax_id']]);

            if (!$user) {
                Yii::$app->response->statusCode = 404;
                return ['errors' => ['tax_id' => 'User not found. Please register first using the registration endpoint.']];
            }

            // Extract and update certificate information if provided
            $certificateInfo = [];
            if (isset($post['certificate_info'])) {
                $certificateInfo = $didoxService->extractCertificateInfo($post['certificate_info']);

                // Update user with latest certificate info
                if (!empty($certificateInfo)) {
                    $user->eimzo_certificate_info = json_encode($certificateInfo);
                }
            }

            // Update login data
            $user->eimzo_didox_token = $post['didox_token'];
            $user->eimzo_last_login = date('Y-m-d H:i:s');

            // Generate new app token
            $user->token = $user->generateToken();

            if ($user->save(false)) {
                // Return user object directly in data field (matching standard API format)
                return ['data' => User::find()->with('image')->where(['id' => $user->id])->one()];
            } else {
                throw new HttpException(500, 'Failed to update user data');
            }
        } catch (HttpException $e) {
            throw $e;
        } catch (\Exception $e) {
            Yii::error('E-IMZO login error: ' . $e->getMessage(), __METHOD__);
            Yii::$app->response->statusCode = 500;
            return ['errors' => ['service' => 'Login service error: ' . $e->getMessage()]];
        }
    }

    /**
     * Get user profile with E-IMZO information
     */
    public function actionEimzoProfile()
    {
        $user = Yii::$app->user->identity;

        if (!$user) {
            Yii::$app->response->statusCode = 404;
            return ['errors' => ['user' => 'User not found']];
        }

        $userData = User::find()->with('image')->where(['id' => $user->id])->one();

        // Add E-IMZO specific information
        $response = [
            'data' => [
                'user' => $userData,
                'eimzo_info' => [
                    'tax_id' => $user->eimzo_tax_id,
                    'last_login' => $user->eimzo_last_login,
                    'certificate_info' => $user->eimzo_certificate_info ? json_decode($user->eimzo_certificate_info, true) : null
                ]
            ]
        ];

        return $response;
    }
    // end E-IMZO integration

    /**
     * Get BTS regions list
     * GET /api/user/bts-regions
     */
    public function actionBtsRegions()
    {
        $language = Yii::$app->request->get('language') ?? 'ru';
        $regions = \yii\services\BTS::getRegions($language);

        return ['data' => [
            'regions' => $regions
        ]];
    }

    /**
     * Get BTS cities by region
     * GET /api/user/bts-cities?region_id=5
     */
    public function actionBtsCities($region_id = null)
    {
        if (!$region_id) {
            Yii::$app->response->statusCode = 422;
            return ['errors' => ['region_id' => 'Region ID is required']];
        }

        $language = Yii::$app->request->get('language') ?? 'ru';
        $cities = \yii\services\BTS::getCities($region_id, $language);
        $cityList = [];

        foreach ($cities as $id => $city) {
            $cityList[$id] = $city['name'];
        }

        return ['data' => [
            'cities' => $cityList,
            'region_id' => (int)$region_id
        ]];
    }
}
