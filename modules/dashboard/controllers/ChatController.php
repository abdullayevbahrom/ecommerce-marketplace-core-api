<?php
namespace app\modules\dashboard\controllers;

use Yii;
use yii\web\Response;
use yii\rest\Controller;
use yii\web\HttpException;
use yii\web\UploadedFile;
use yii\services\Sms;
use yii\data\ActiveDataProvider;
use yii\filters\auth\HttpBearerAuth;

use app\models\user\User;
use app\models\chat\MessageRoom;
use app\models\chat\Messages;
use app\models\Images;

use yii\services\Fcm;

class ChatController extends Controller {
    public function beforeAction($action) {
        $this->enableCsrfValidation = false;

        Yii::$app->response->getHeaders()->add('Access-Control-Allow-Origin', '*');
        Yii::$app->response->getHeaders()->add('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, HEAD, OPTIONS');
        Yii::$app->response->getHeaders()->add('Access-Control-Allow-Headers', 'Content-Type, X-Auth-Token, Origin, Authorization');

        if (Yii::$app->request->headers->has('OPTIONS')) {
            throw new HttpException(200, 'OK');
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

    protected function getToken() {
        $header = Yii::$app->request->getHeaders()->get('Authorization');
        if ($header) {
            $header = explode(' ', $header);
            return $header[1];
        }
        return false;
    }

    protected function getPost($ps = array()) {
        $post = Yii::$app->request->post();
        if (!$post) {
            throw new HttpException(422, 'Переданы не все параметры');
        }
        if ($ps) {
            foreach ($ps as $value) {
                if ($value && !array_key_exists($value, $post) || !$post[$value]) {
                    throw new HttpException(422, 'Переданы не все параметры');
                }
            }
        }
        return $post;
    }

    public function actionUsers() {

        $user = Yii::$app->user->identity;

        $type = Yii::$app->request->get('type');

        if ($type == 'archive') {
            $model = MessageRoom::find()->with('sender', 'sender.image', 'getter', 'getter.image', 'messages')->where(['sender_id'=>$user->id, 'archive_status'=>1])->orWhere(['getter_id'=>$user->id, 'archive_status'=>1])->andWhere(['status'=>0])->andWhere(['important_status'=>0])->orderBy('id desc')->all();
        } else if ($type == 'important') {
            $model = MessageRoom::find()->with('sender', 'sender.image', 'getter', 'getter.image', 'messages')->where(['sender_id'=>$user->id, 'important_status'=>1])->orWhere(['getter_id'=>$user->id, 'important_status'=>1])->andWhere(['status'=>0])->andWhere(['archive_status'=>0])->orderBy('id desc')->all();
        } else if ($type == 'block') {
            $model = MessageRoom::find()->with('sender', 'sender.image', 'getter', 'getter.image', 'messages')->where(['sender_id'=>$user->id, 'status'=>1])->orWhere(['getter_id'=>$user->id, 'status'=>1])->andWhere(['archive_status'=>0])->andWhere(['important_status'=>0])->orderBy('id desc')->all();
        } else {
            $model = MessageRoom::find()->with('sender', 'sender.image', 'getter', 'getter.image', 'messages')->where(['sender_id'=>$user->id])->orWhere(['getter_id'=>$user->id])->orderBy('id desc')->all();
        }

        $last_message = [];
        $last_message_id = [];
        $new_messages = [];

        foreach ($model as $k => $v) {
            foreach ($v->messages as $key => $val) {
                if ($val->message) {
                    $last_message[$k] = $val->message;
                } else {
                    $last_message[$k] = $val->getFilePath();
                }
                $last_message_id[$k] = $val->id;
                if (($val->status == 0) && ($user->id != $val->user_id)) {
                    $new_messages[$k]++;
                }
            }
        }

        $data = [];

        if ($model) {
            foreach ($model as $k => $v) {
                if (($type == 'unreaded') && ($new_messages[$k] == 0)) continue;
                if ($v->type != 'user') {
                    continue;
                }
                $data[] = [
                    'id' => $v->id,
                    'user_id' => ($v->sender_id == $user->id) ? $v->getter_id : $v->sender_id,
                    'name' => ($v->sender_id == $user->id) ? $v->getter->name : $v->sender->name,
                    'photo' => ($v->sender_id == $user->id) ? $v->getter->getPhoto() : $v->sender->getPhoto(),
                    'messages' => $new_messages[$k] ? $new_messages[$k] : 0,
                    'last_message' => $last_message[$k],
                    'last_message_id' => $last_message_id[$k],
                    'status' => $v->status,
                    'status_archive' => $v->status_archive,
                    'status_important' => $v->status_important,
                    'date' => $v->date
                ];
            }
        }

        usort($data, function($a, $b){
            return ($a['last_message_id'] - $b['last_message_id']);
        });

        $data = array_reverse($data);

        return ['data'=>$data];
    }

    public function actionShops() {

        $user = Yii::$app->user->identity;

        $type = Yii::$app->request->get('type');

        if ($type == 'archive') {
            $model = MessageRoom::find()->with('sender', 'sender.image', 'getter', 'getter.image', 'messages')->where(['sender_id'=>$user->id, 'archive_status'=>1])->orWhere(['getter_id'=>$user->id, 'archive_status'=>1])->andWhere(['status'=>0])->andWhere(['important_status'=>0])->orderBy('id desc')->all();
        } else if ($type == 'important') {
            $model = MessageRoom::find()->with('sender', 'sender.image', 'getter', 'getter.image', 'messages')->where(['sender_id'=>$user->id, 'important_status'=>1])->orWhere(['getter_id'=>$user->id, 'important_status'=>1])->andWhere(['status'=>0])->andWhere(['archive_status'=>0])->orderBy('id desc')->all();
        } else if ($type == 'block') {
            $model = MessageRoom::find()->with('sender', 'sender.image', 'getter', 'getter.image', 'messages')->where(['sender_id'=>$user->id, 'status'=>1])->orWhere(['getter_id'=>$user->id, 'status'=>1])->andWhere(['archive_status'=>0])->andWhere(['important_status'=>0])->orderBy('id desc')->all();
        } else {
            $model = MessageRoom::find()->with('sender', 'sender.image', 'getter', 'getter.image', 'messages')->where(['sender_id'=>$user->id])->orWhere(['getter_id'=>$user->id])->orderBy('id desc')->all();
        }

        $last_message = [];
        $last_message_id = [];
        $new_messages = [];

        foreach ($model as $k => $v) {
            foreach ($v->messages as $key => $val) {
                if ($val->message) {
                    $last_message[$k] = $val->message;
                } else {
                    $last_message[$k] = $val->getFilePath();
                }
                $last_message_id[$k] = $val->id;
                if (($val->status == 0) && ($user->id != $val->user_id)) {
                    $new_messages[$k]++;
                }
            }
        }

        $data = [];

        if ($model) {
            foreach ($model as $k => $v) {
                if (($type == 'unreaded') && ($new_messages[$k] == 0)) continue;
                if ($v->type != 'shop') {
                    continue;
                }
                $data[] = [
                    'id' => $v->id,
                    'user_id' => ($v->sender_id == $user->id) ? $v->getter_id : $v->sender_id,
                    'name' => ($v->sender_id == $user->id) ? $v->getter->name : $v->sender->name,
                    'photo' => ($v->sender_id == $user->id) ? $v->getter->getPhoto() : $v->sender->getPhoto(),
                    'messages' => $new_messages[$k] ? $new_messages[$k] : 0,
                    'last_message' => $last_message[$k],
                    'last_message_id' => $last_message_id[$k],
                    'status' => $v->status,
                    'status_archive' => $v->status_archive,
                    'status_important' => $v->status_important,
                    'date' => $v->date
                ];
            }
        }

        usort($data, function($a, $b){
            return ($a['last_message_id'] - $b['last_message_id']);
        });

        $data = array_reverse($data);

        return ['data'=>$data];
    }

    public function actionAdmins() {

        $user = Yii::$app->user->identity;

        $type = Yii::$app->request->get('type');

        if ($type == 'archive') {
            $model = MessageRoom::find()->with('sender', 'sender.image', 'getter', 'getter.image', 'messages')->where(['sender_id'=>$user->id, 'archive_status'=>1])->orWhere(['getter_id'=>$user->id, 'archive_status'=>1])->andWhere(['status'=>0])->andWhere(['important_status'=>0])->orderBy('id desc')->all();
        } else if ($type == 'important') {
            $model = MessageRoom::find()->with('sender', 'sender.image', 'getter', 'getter.image', 'messages')->where(['sender_id'=>$user->id, 'important_status'=>1])->orWhere(['getter_id'=>$user->id, 'important_status'=>1])->andWhere(['status'=>0])->andWhere(['archive_status'=>0])->orderBy('id desc')->all();
        } else if ($type == 'block') {
            $model = MessageRoom::find()->with('sender', 'sender.image', 'getter', 'getter.image', 'messages')->where(['sender_id'=>$user->id, 'status'=>1])->orWhere(['getter_id'=>$user->id, 'status'=>1])->andWhere(['archive_status'=>0])->andWhere(['important_status'=>0])->orderBy('id desc')->all();
        } else {
            $model = MessageRoom::find()->with('sender', 'sender.image', 'getter', 'getter.image', 'messages')->where(['sender_id'=>$user->id])->orWhere(['getter_id'=>$user->id])->orderBy('id desc')->all();
        }

        $last_message = [];
        $last_message_id = [];
        $new_messages = [];

        foreach ($model as $k => $v) {
            foreach ($v->messages as $key => $val) {
                if ($val->message) {
                    $last_message[$k] = $val->message;
                } else {
                    $last_message[$k] = $val->getFilePath();
                }
                $last_message_id[$k] = $val->id;
                if (($val->status == 0) && ($user->id != $val->user_id)) {
                    $new_messages[$k]++;
                }
            }
        }

        $data = [];

        if ($model) {
            foreach ($model as $k => $v) {
                if (($type == 'unreaded') && ($new_messages[$k] == 0)) continue;
                if ($v->type != 'admin') {
                    continue;
                }
                $data[] = [
                    'id' => $v->id,
                    'user_id' => ($v->sender_id == $user->id) ? $v->getter_id : $v->sender_id,
                    'name' => ($v->sender_id == $user->id) ? $v->getter->name : $v->sender->name,
                    'photo' => ($v->sender_id == $user->id) ? $v->getter->getPhoto() : $v->sender->getPhoto(),
                    'messages' => $new_messages[$k] ? $new_messages[$k] : 0,
                    'last_message' => $last_message[$k],
                    'last_message_id' => $last_message_id[$k],
                    'status' => $v->status,
                    'status_archive' => $v->status_archive,
                    'status_important' => $v->status_important,
                    'date' => $v->date
                ];
            }
        }

        usort($data, function($a, $b){
            return ($a['last_message_id'] - $b['last_message_id']);
        });

        $data = array_reverse($data);

        return ['data'=>$data];
    }

    public function actionDocuments() {

        $user = Yii::$app->user->identity;

        $type = Yii::$app->request->get('type');

        if ($type == 'archive') {
            $model = MessageRoom::find()->with('sender', 'sender.image', 'getter', 'getter.image', 'messages', 'messages.chatFile')->where(['sender_id'=>$user->id, 'archive_status'=>1])->orWhere(['getter_id'=>$user->id, 'archive_status'=>1])->andWhere(['status'=>0])->andWhere(['important_status'=>0])->orderBy('id desc')->all();
        } else if ($type == 'important') {
            $model = MessageRoom::find()->with('sender', 'sender.image', 'getter', 'getter.image', 'messages', 'messages.chatFile')->where(['sender_id'=>$user->id, 'important_status'=>1])->orWhere(['getter_id'=>$user->id, 'important_status'=>1])->andWhere(['status'=>0])->andWhere(['archive_status'=>0])->orderBy('id desc')->all();
        } else if ($type == 'block') {
            $model = MessageRoom::find()->with('sender', 'sender.image', 'getter', 'getter.image', 'messages', 'messages.chatFile')->where(['sender_id'=>$user->id, 'status'=>1])->orWhere(['getter_id'=>$user->id, 'status'=>1])->andWhere(['archive_status'=>0])->andWhere(['important_status'=>0])->orderBy('id desc')->all();
        } else {
            $model = MessageRoom::find()->with('sender', 'sender.image', 'getter', 'getter.image', 'messages', 'messages.chatFile')->where(['sender_id'=>$user->id])->orWhere(['getter_id'=>$user->id])->orderBy('id desc')->all();
        }

        $last_message = [];
        $last_message_id = [];
        $new_messages = [];

        $flag = false;
        foreach ($model as $k => $v) {
            foreach ($v->messages as $key => $val) {
                if (!$val->chatFile) continue;
                $flag = true;
                if ($val->message) {
                    $last_message[$k] = $val->message;
                } else {
                    $last_message[$k] = $val->getFilePath();
                }
                $last_message_id[$k] = $val->id;
                if (($val->status == 0) && ($user->id != $val->user_id)) {
                    $new_messages[$k]++;
                }
            }
        }

        $data = [];

        if ($model && ($flag === true)) {
            foreach ($model as $k => $v) {
                if (($type == 'unreaded') && ($new_messages[$k] == 0)) continue;
                if ($v->type != 'user') {
                    continue;
                }
                $data[] = [
                    'id' => $v->id,
                    'user_id' => ($v->sender_id == $user->id) ? $v->getter_id : $v->sender_id,
                    'name' => ($v->sender_id == $user->id) ? $v->getter->name : $v->sender->name,
                    'photo' => ($v->sender_id == $user->id) ? $v->getter->getPhoto() : $v->sender->getPhoto(),
                    'messages' => $new_messages[$k] ? $new_messages[$k] : 0,
                    'last_message' => $last_message[$k],
                    'last_message_id' => $last_message_id[$k],
                    'status' => $v->status,
                    'status_archive' => $v->status_archive,
                    'status_important' => $v->status_important,
                    'date' => $v->date
                ];
            }
        }

        usort($data, function($a, $b){
            return ($a['last_message_id'] - $b['last_message_id']);
        });

        $data = array_reverse($data);

        return ['data'=>$data];
    }

    function sortFunction( $a, $b ) {
        return strtotime($a[1]) - strtotime($b[1]);
    }

    public function actionMessages($id) {
        $user = Yii::$app->user->identity;

        $room = MessageRoom::findOne($id);

        if (!$room) {
            Yii::$app->response->statusCode = 404;
            throw new HttpException(404, 'Чат не найден');
        }

        if (($room->sender_id != $user->id) && ($room->getter_id != $user->id)) {
            Yii::$app->response->statusCode = 404;
            throw new HttpException(404, 'Чат не найден');
        }

        $query = Messages::find()->with('messageRoom', 'user')->where(['message_room_id'=>$id]);

        $messages = Messages::find()->with('messageRoom', 'user')->where(['message_room_id'=>$id, 'status'=>0])->all();

        $data = [];

        if ($messages) {
            foreach ($messages as $k => $v) {
                if ($v->user_id != $user->id) {
                     $data[] = $v->id;
                } 
            }
        }
        
        Messages::UpdateAll(['status'=>1], ['in', 'id', $data]);

        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => false,
            'sort' => ['defaultOrder' => ['id'=>'desc']]
        ]);
    }

    public function actionSend() {
        $user = Yii::$app->user->identity;

        $post = Yii::$app->request->post();

        $room = new MessageRoom;
        $room->setAttributes($post);

        if (!$room->validate()) {
            Yii::$app->response->statusCode = 422;
            return ['errors'=>$room->errors];
        }

        $get_user = User::findOne($post['getter_id']);

        if (!$get_user) {
            Yii::$app->response->statusCode = 404;
            throw new HttpException(404, 'Пользователь не найден');
        }

        $model = MessageRoom::find()
            ->where(['sender_id'=>$user->id, 'getter_id'=>$post['getter_id']])
            ->orWhere(['getter_id'=>$user->id, 'sender_id'=>$post['getter_id']])
            ->one();

        $date = date('Y-m-d H:i:s');
        
        if (!$model) {
            $model = new MessageRoom;
            $model->sender_id = $user->id;
            $model->getter_id = $post['getter_id'];
            $model->date = $date;
            $model->save(false);
        }

        if ($model->status == 1) {
            Yii::$app->response->statusCode = 422;
            return ['errors'=>['message'=>'Пользователь Вас заблокировал']];
        }

        $msg = new Messages;

        $msg->message_room_id = $model->id;
        $msg->user_id = $user->id;
        $msg->message = $post['message'];
        $msg->status = 0;
        $msg->date = $date;

        if ($msg->save()) {
            $image = new Images;
            if ($image->imageFiles[] = UploadedFile::getInstanceByName('file')) {
                $image->uploadPhoto($msg->id, 'chat');
            }
            $query = Messages::find()->with('messageRoom', 'user')->where(['message_room_id'=>$model->id]);

            if ($get_user->device_id) {
                $fcm = new Fcm;
                $data = array(
                    'title' => 'Новое сообщение',
                    'body' => $msg->message,
                    'type' => 'message',
                    'chat_id' => $model->id,
                    'user_id' => $user->id,
                    'user_photo' => $user->getPhoto(),
                    'date' => time()
                );

                // $fcm->pushNotification($data, $data, $get_user->device_id, 'high');
            }
            
            return new ActiveDataProvider([
                'query' => $query,
                'pagination' => false,
                'sort' => ['defaultOrder' => ['id'=>'desc']]
            ]);
        }

        Yii::$app->response->statusCode = 422;
        return ['errors'=>$msg->errors];
    }

    public function actionSetImportant() {
        $post = Yii::$app->request->post();
        $user = Yii::$app->user->identity;

        $model = MessageRoom::findOne($post['id']);

        if (!$model) {
            Yii::$app->response->statusCode = 404;
            throw new HttpException(404, 'Чат не найден');
        }

        if ($model->status_important == 1) {
            $model->status_important = 0;
            $message = 'С чата снята пометка ВАЖНОЕ';
        } else {
            $model->status_important = 1;
            $message = 'Чат помечен как ВАЖНОЕ';
        }
        
        $model->save(false);

        return ['data'=>['error'=>$message]];
    }

    public function actionSetArchive() {
        $post = Yii::$app->request->post();
        $user = Yii::$app->user->identity;

        $model = MessageRoom::findOne($post['id']);

        if (!$model) {
            Yii::$app->response->statusCode = 404;
            throw new HttpException(404, 'Чат не найден');
        }

        if ($model->status_archive == 1) {
            $model->status_archive = 0;
            $message = 'Чат разархивирован';
        } else {
            $model->status_archive = 1;
            $message = 'Чат архивирован';
        }
        
        $model->save(false);

        return ['data'=>['error'=>$message]];
    }

    public function actionSetBlock() {
        $post = Yii::$app->request->post();
        $user = Yii::$app->user->identity;

        $model = MessageRoom::findOne($post['id']);

        if (!$model) {
            Yii::$app->response->statusCode = 404;
            throw new HttpException(404, 'Чат не найден');
        }

        if ($model->status == 1) {
            $model->status = 0;
            $message = 'Пользователь разблокирован';
        } else {
            $model->status = 1;
            $message = 'Пользователь заблокирован';
        }
        
        $model->save(false);

        return ['data'=>['error'=>$message]];
    }

    public function actionRemove() {
        $post = Yii::$app->request->post();
        $user = Yii::$app->user->identity;

        Messages::deleteAll(['message_room_id'=>$post['id']]);

        if ($room = MessageRoom::findOne($post['id'])) {
            $room->delete();
        }

        throw new HttpException(200, 'Чат удален');
    }
}
?>