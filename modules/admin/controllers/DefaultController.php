<?php

namespace app\modules\admin\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\web\UploadedFile;
use yii\data\Pagination;
use yii\helpers\ArrayHelper;

use app\models\user\User;
use app\models\product\Product;
use app\models\shop\Shop;
use app\models\order\Order;
use app\models\order\OrderSearch;
use app\models\Images;
use app\models\File;
use app\models\Notification;
use app\models\product\review\ProductReview;
use app\models\product\review\ProductReviewSearch;

class DefaultController extends Controller
{
    public $user;

    public function beforeAction($action)
    {
        if (!Yii::$app->user->isGuest) {
            $this->user = Yii::$app->user->identity;
            if ($this->user->role == User::ROLE_USER) {
                return $this->redirect(['/']);
            }
        }

        return parent::beforeAction($action);
    }

    public function actionIndex()
    {
        if ($this->user && ($this->user->role == User::ROLE_ADMIN)) {
            return $this->redirect(['/admin/default/dashboard']);
        }
        $model = new User;
        $model->scenario = User::SIGNIN_ADMIN;
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->login() && ($user = $model->findByUsername($model->login))) {
                if ($user->role == User::ROLE_ADMIN) {
                    return $this->redirect("/admin/default/dashboard");
                }
                if ($user->role == User::ROLE_SHOP) {
                    return $this->redirect("/shop/default/dashboard");
                }
                if ($user->role == User::ROLE_LOGIST) {
                    return $this->redirect("/logist/default/dashboard");
                }
                if ($user->role == User::ROLE_MODERATOR) {
                    return $this->redirect("/admin/default/profile");
                }
                return $this->redirect("https://example.com");
            } else {
                // Login method returned false - authentication failed
                Yii::$app->session->setFlash('error', 'Неверный логин или пароль. Проверьте правильность введенных данных.');
            }
        } else if ($model->hasErrors()) {
            // Model validation failed - show validation errors
            $errors = [];
            foreach ($model->getErrors() as $field => $fieldErrors) {
                foreach ($fieldErrors as $error) {
                    $errors[] = $error;
                }
            }
            if (!empty($errors)) {
                Yii::$app->session->setFlash('error', implode('<br>', $errors));
            }
        }

        return $this->render('index', [
            'model' => $model
        ]);
    }

    public function actionProfile()
    {
        if ($this->user->load(Yii::$app->request->post()) && $this->user->validate()) {

            $avatar = $this->user->avatar;
            if (!$avatar) {
                $avatar = new Images;
            }
            if ($avatar->imageFiles = UploadedFile::getInstances($this->user, 'imageFiles')) {
                $avatar->uploadPhoto($this->user->id, 'user');
            }

            if ($this->user->save()) {
                Yii::$app->session->setFlash('user_success', 'Saved');
                return $this->redirect(Yii::$app->request->referrer);
            }
        }

        return $this->render('profile', [
            'model' => $this->user
        ]);
    }

    public function actionUpdateProfile()
    {
        $model = User::find()->with('image')->where(['id' => $this->user->id])->one();
        $model->scenario = User::UPDATE_ADMIN;

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->saveObject(User::ROLE_ADMIN)) {
                Yii::$app->session->setFlash('admin_saved', 'Saved');
            }
            return $this->redirect(['/admin/default/profile', 'id' => $model->id]);
        }

        return $this->render('update-profile', [
            'model' => $model
        ]);
    }

    public function actionRemovePhoto($id)
    {
        $model = Images::findOne($id);

        if ($model && $model->removeImageSize()) {
            Yii::$app->session->setFlash('photo_remove', 'Photo deleted');
        }

        return $this->redirect(Yii::$app->request->referrer);
    }

    public function actionRemoveFile($id)
    {
        $model = File::findOne($id);

        if ($model && $model->remove()) {
            Yii::$app->session->setFlash('file_remove', 'File deleted');
        }

        return $this->redirect(Yii::$app->request->referrer);
    }

    public function actionChangePassword()
    {
        $model = User::findOne($this->user->id);

        $model->scenario = User::ADMIN_CHANGE_PASSWORD;

        if ($model->load(Yii::$app->request->post())) {
            if ($model->changePassword()) {
                Yii::$app->session->setFlash('password_changed', 'Password changed');
            }
            return $this->redirect(Yii::$app->request->referrer);
        }

        return $this->render('change-password', [
            'model' => $model,
        ]);
    }

    public function actionDashboard($type = null)
    {
        $user_count = User::find()->where(['role' => User::ROLE_USER, 'status' => 1])->count();
        $product_count = Product::find()->where(['status' => 1])->count();
        $order_count = Order::find()->where(['status' => 0])->count();
        $shop_count = Shop::find()->count();

        if ($type = Yii::$app->request->get('type')) {
            $type_data = ['week' => '7 DAY', 'month' => '1 MONTH', 'hyear' => '6 MONTH', 'year' => '12 MONTH'];
            $order_statistic = Order::find()->where('date >= DATE_SUB(CURRENT_DATE, INTERVAL ' . $type_data[$type] . ')')->all();
        } else {
            $order_statistic = Order::find()->where('date >= DATE_SUB(CURRENT_DATE, INTERVAL 12 MONTH)')->all();
        }

        $data = [];

        foreach ($order_statistic as $k => $v) {
            $date = explode(' ', $v->date);
            if (!empty($data[$date[0]])) {
                $data[$date[0]]['amount'] += 1;
                $data[$date[0]]['price'] += $v->price;
            }
        }

        $products = ArrayHelper::map(Product::find()->where(['status' => 1])->all(), 'id', 'name_ru');
        $users = ArrayHelper::map(User::find()->where(['status' => 1])->all(), 'id', 'name');

        $searchModel = new OrderSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $dataProvider->query->with('user', 'payment', 'delivery')->andWhere(['status' => 0])->limit(10);
        $dataProvider->pagination = false;

        $searchModelReview = new ProductReviewSearch();
        $dataProviderReview = $searchModelReview->search(Yii::$app->request->queryParams);
        $dataProviderReview->query->with('user', 'product')->limit(10);
        $dataProviderReview->pagination = false;

        return $this->render('dashboard', [
            'user_count' => $user_count,
            'product_count' => $product_count,
            'order_count' => $order_count,
            'shop_count' => $shop_count,
            'products' => $products,
            'users' => $users,
            'data' => $data,
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'searchModelReview' => $searchModelReview,
            'dataProviderReview' => $dataProviderReview
        ]);
    }
}
