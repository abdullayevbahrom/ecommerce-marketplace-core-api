<?php
namespace app\modules\api\controllers;


use Yii;

use yii\rest\Controller;
use yii\web\HttpException;
use yii\web\UploadedFile;
use yii\data\ActiveDataProvider;
use yii\filters\auth\HttpBearerAuth;
use yii\helpers\ArrayHelper;
use yii\base\Exception;

use app\models\Images;
use app\models\user\User;
use app\models\user\card\UserCard;
use app\models\SmsCode;
use app\models\Category;

use yii\services\Sms;
use yii\services\PaymeSubscribe;

class CardController extends Controller {
    public $user;

    public function beforeAction($action) {
        $this->enableCsrfValidation = false;

        Yii::$app->response->getHeaders()->add('Access-Control-Allow-Origin', '*');
        Yii::$app->response->getHeaders()->add('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, HEAD, OPTIONS');
        Yii::$app->response->getHeaders()->add('Access-Control-Allow-Headers', 'Content-Type, X-Auth-Token, Origin, Authorization');

        if (Yii::$app->request->headers->has('OPTIONS')) {
            throw new HttpException(200, 'OK');
        }

        Yii::$app->session->set('language', 'ru');
        $langs = ['ru', 'en', 'uz'];

        $headers = Yii::$app->request->headers;
        if($headers->has('Content-Language')) {
            $lang = $headers->get('Content-Language');
            if(in_array($lang,$langs)) {
                Yii::$app->session->set('language', $lang);
            }
        }
        
        return parent::beforeAction($action);
    }

    public function behaviors() {
        $behaviors = parent::behaviors();
        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::className(),
            'optional' => []
        ];

        $auth = $behaviors['authenticator'];
        unset($behaviors['authenticator']);

        $behaviors['corsFilter'] = [
            'class' => \yii\filters\Cors::className(),
            'cors' => [
                'Access-Control-Allow-Origin' => ['*'],
                'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'],
                'Access-Control-Request-Headers' => ['*'],
                'Access-Control-Allow-Credentials' => true,
                'Access-Control-Max-Age' => 86400,
                'Access-Control-Expose-Headers' => [],
            ]
        ];

        $behaviors['authenticator']['except'] = ['options'];

        $behaviors['authenticator'] = $auth;
        
        return $behaviors;
    }

    public $serializer = [
        'class' => 'yii\rest\Serializer',
        'collectionEnvelope' => 'data',
    ];

    // add card
    public function actionSend() {
        $user = Yii::$app->user->identity;
        $post = Yii::$app->request->post();

        $model = new UserCard;
        $model->setAttributes($post);

        if (!$model->validate()) {
            Yii::$app->response->statusCode = 422;
            return ['errors'=>$model->errors];
        }

        $subscribe = new PaymeSubscribe;
        $response = json_decode($subscribe->createCard($post));

        if ($response->error) {
            return ['data'=>$response->error];
        }

        // $type = Category::findOne(['id'=>$post['type_id'], 'type'=>'payment']);

        // if (!$type) {
        //     Yii::$app->response->statusCode = 401;
        //     return ['errors'=>['type_id'=>'Тип карты не найден']];
        // }

        $check_card = UserCard::findOne(['user_id'=>$user->id, 'card_number'=>$response->result->card->number, 'status'=>1]);
        if ($check_card) {
            Yii::$app->response->statusCode = 404;
            return ['errors'=>['id'=>'Карта уже создана']];
        }

        $model->user_id = $user->id;
        $model->card_number = $response->result->card->number;
        $model->card_token = $response->result->card->token;
        $model->status = 0;
        $model->save();

        $verify = json_decode($subscribe->getVerifyCode($model->card_token));

        $card = UserCard::findOne(['id'=>$model->id, 'user_id'=>$user->id]);

        return ['data'=>$card, 'verify'=>$verify];
    }

    public function actionSendVerifyCode() {
        $user = Yii::$app->user->identity;
        $post = Yii::$app->request->post();

        if (!$post['card_id']) {
            Yii::$app->response->statusCode = 422;
            return ['errors'=>['card_id'=>'Введите ID карты']];
        }

        if (!$post['code']) {
            Yii::$app->response->statusCode = 422;
            return ['errors'=>['code'=>'Введите код']];
        }

        $card = UserCard::findOne(['id'=>$post['card_id'], 'user_id'=>$user->id]);
        if (!$card) {
            Yii::$app->response->statusCode = 404;
            return ['errors'=>['card_id'=>'Карта не найдена']];
        }

        $subscribe = new PaymeSubscribe;
        $response = json_decode($subscribe->verify($card->card_token, $post['code']));

        if ($response->result) {
            $card->status = 1;
            $card->save(false);

            return ['data'=>$card];
        }

        return ['data'=>$response];
    }

    public function actionResendVerify() {
        $user = Yii::$app->user->identity;
        $post = Yii::$app->request->post();

        if (!$post['card_id']) {
            Yii::$app->response->statusCode = 422;
            return ['errors'=>['card_id'=>'Введите ID карты']];
        }

        $card = UserCard::findOne(['id'=>$post['card_id'], 'user_id'=>$user->id]);

        if (!$card) {
            Yii::$app->response->statusCode = 404;
            return ['errors'=>['card_id'=>'Карта не найдена']];
        }
        
        $subscribe = new PaymeSubscribe;
        $verify = json_decode($subscribe->getVerifyCode($card->card_token));

        return ['data'=>$card, 'verify'=>$verify];
    }

    public function actionCheck() {
        $user = Yii::$app->user->identity;
        $post = Yii::$app->request->post();

        if (!$post['card_id']) {
            Yii::$app->response->statusCode = 422;
            return ['errors'=>['card_id'=>'Введите ID карты']];
        }

        $card = UserCard::findOne(['id'=>$post['card_id'], 'user_id'=>$user->id]);

        if (!$card) {
            Yii::$app->response->statusCode = 404;
            return ['errors'=>['card_id'=>'Карта не найдена']];
        }
        
        $subscribe = new PaymeSubscribe;
        $check = json_decode($subscribe->check($card->card_token));

        return ['data'=>$check];
    }
    // end add card

    public function actionIndex($type_id = null) {
        $user = Yii::$app->user->identity;
        
        $query = UserCard::find()->where(['user_id'=>$user->id, 'status'=>1]);

        if ($type_id) {
            $query->andWhere(['type_id'=>$type_id]);
        }

        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => false
        ]);
    }

    public function actionView($id) {
        $user = Yii::$app->user->identity;

        $model = UserCard::findOne(['user_id'=>$user->id, 'id'=>$id, 'status'=>1]);
        if (!$model) {
            Yii::$app->response->statusCode = 404;
            return ['errors'=>['id'=>'Карта не найдена']];
        }

        return ['data'=>$model];
    }

    public function actionUpdate() {
        $user = Yii::$app->user->identity;
        $post = Yii::$app->request->post();

        $model = new UserCard;
        $model->setAttributes($post);

        if (!$model->validate()) {
            Yii::$app->response->statusCode = 422;
            return ['errors'=>$model->errors];
        }

        $model = UserCard::findOne(['user_id'=>$user->id, 'id'=>$post['id']]);
        if (!$model) {
            Yii::$app->response->statusCode = 404;
            return ['errors'=>['id'=>'Карта не найдена']];
        }

        $model->setAttributes($post);
        $model->save();
        $card = UserCard::findOne(['id'=>$model->id, 'user_id'=>$user->id]);

        return ['data'=>$card];
    }

    public function actionRemove() {
        $user = Yii::$app->user->identity;
        $post = Yii::$app->request->post();

        if (!$post['card_id']) {
            Yii::$app->response->statusCode = 422;
            return ['errors'=>['card_id'=>'Введите ID карты']];
        }

        $model = UserCard::findOne(['user_id'=>$user->id, 'id'=>$post['card_id']]);
        if (!$model) {
            Yii::$app->response->statusCode = 404;
            return ['errors'=>['id'=>'Карта не найдена']];
        }

        if ($model->delete()) {
            $subscribe = new PaymeSubscribe;
            $check = json_decode($subscribe->remove($model->card_token));
        }

        $query = UserCard::find()->where(['user_id'=>$user->id]);

        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => false
        ]);
    }
}
