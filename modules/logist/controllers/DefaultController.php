<?php
namespace app\modules\logist\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\web\UploadedFile;
use yii\data\Pagination;
use yii\helpers\ArrayHelper;

use app\models\user\User;
use app\models\product\Product;
use app\models\order\Order;
use app\models\order\product\OrderProduct;
use app\models\order\OrderSearch;
use app\models\Images;
use app\models\File;
use app\models\Notification;
use app\models\logist\Logist;

class DefaultController extends Controller{
    public $user;
    public $logist;

    public function beforeAction($action){
        if (!Yii::$app->user->isGuest) {
            $this->user = Yii::$app->user->identity;
            $this->logist = Logist::findOne(['user_id'=>$this->user->id]);

            if ($this->user->role != User::ROLE_LOGIST) {
                return $this->redirect(['/']);
            }
        }

        return parent::beforeAction($action);
    }

    public function actionProfile() {
        if ($this->user->load(Yii::$app->request->post()) && $this->user->validate()){

            $avatar = $this->user->avatar;
            if (!$avatar) {
                $avatar = new Images;
            }
            if ($avatar->imageFiles = UploadedFile::getInstances($this->user, 'imageFiles')) {
                $avatar->uploadPhoto($this->user->id, 'user');
            }

            if ($this->user->save()) {
                Yii::$app->session->setFlash('user_success', 'Информация успешно сохранена');
                return $this->redirect(Yii::$app->request->referrer);
            }
        }

        return $this->render('profile', [
            'model'=>$this->user
        ]);
    }

    public function actionUpdateProfile() {
        $model = User::find()->with('image')->where(['id'=>$this->user->id])->one();
        $model->scenario = User::UPDATE_ADMIN;

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->saveObject(User::ROLE_LOGIST)) {
                Yii::$app->session->setFlash('admin_saved', 'Данные успешно изменены');
            }
            return $this->redirect(['/logist/default/profile', 'id'=>$model->id]);
        }
        
        return $this->render('update-profile', [
            'model'=>$model
        ]);
    }

    public function actionRemovePhoto($id) {
        $model = Images::findOne($id);

        if ($model && $model->removeImageSize()) {
            Yii::$app->session->setFlash('photo_remove', 'Фото успешно удалено');
        }

        return $this->redirect(Yii::$app->request->referrer);
    }

    public function actionRemoveFile($id) {
        $model = File::findOne($id);

        if ($model && $model->remove()) {
            Yii::$app->session->setFlash('file_remove', 'Файл успешно удален');
        }

        return $this->redirect(Yii::$app->request->referrer);
    }

    public function actionChangePassword(){
    	$model = User::findOne($this->user->id);

        $model->scenario = User::ADMIN_CHANGE_PASSWORD;

        if ($model->load(Yii::$app->request->post())) {
            if ($model->changePassword()) {
                Yii::$app->session->setFlash('password_changed', 'Пароль успешно изменен');
            }
            return $this->redirect(Yii::$app->request->referrer);
        }

    	return $this->render('change-password', [
    		'model'=>$model,
    	]);
    }

    public function actionDashboard($type = null) {
        $order_count = Order::find()->where(['status'=>0])->andWhere(['logist_id'=>$this->logist->id])->count();

        if ($type = Yii::$app->request->get('type')) {
            $type_data = ['week'=>'7 DAY', 'month'=>'1 MONTH', 'hyear'=>'6 MONTH', 'year'=>'12 MONTH'];
            $order_statistic = Order::find()->where('date >= DATE_SUB(CURRENT_DATE, INTERVAL '.$type_data[$type].')')->andWhere(['logist_id'=>$this->logist->id])->all();
        } else {
            $order_statistic = Order::find()->where('date >= DATE_SUB(CURRENT_DATE, INTERVAL 12 MONTH)')->andWhere(['logist_id'=>$this->logist->id])->all();
        }

        $data = [];

        foreach ($order_statistic as $k => $v) {
            $date = explode(' ', $v->date);
            $data[$date[0]]['amount'] += 1;
            $data[$date[0]]['price'] += $v->price;
        }

        $searchModel = new OrderSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $dataProvider->query->with('user', 'payment', 'delivery')->andWhere(['status'=>0])->andWhere(['logist_id'=>$this->logist->id])->limit(10);

        return $this->render('dashboard', [
            'order_count' => $order_count,
            'data' => $data,
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider
        ]);
    }

    // shop information
    public function actionLogist() {
        $model = Logist::find()->with('user', 'image')->where(['id'=>$this->logist->id])->one();

        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }

        return $this->render('logist/index', [
            'model' => $model
        ]);
    }
}
?>