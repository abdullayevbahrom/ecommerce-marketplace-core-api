<?php
namespace app\modules\logist\controllers;

use Yii;
use yii\web\Controller;
use yii\helpers\ArrayHelper;
use yii\data\Pagination;
use yii\web\UploadedFile;
use yii\web\HttpException;
use yii\data\ActiveDataProvider;

use app\models\Category;
use app\models\order\Order;
use app\models\order\OrderSearch;
use app\models\user\User;
use app\models\user\UserSearch;
use app\models\user\card\UserCard;
use app\models\user\card\UserCardSearch;
use app\models\Images;

class UserController extends Controller {
    public $user;

    public function beforeAction($action) {
        $this->enableCsrfValidation = false;
        if (Yii::$app->user->isGuest) {
            return $this->redirect(['/admin/default']);
        }
        $this->user = User::find()->with('moderatorAccess', 'moderatorAccess.moderator')->where(['id'=>Yii::$app->user->identity->id])->one();

        if (($this->user->role == User::ROLE_MODERATOR)) {
            $accesses = array();

            if ($this->user && $this->user->moderatorAccess) {
                foreach ($this->user->moderatorAccess as $v) {
                    if ($v && $v->moderator) {
                        $accesses[] = $v->moderator->url;
                    }
                }
            }

            if (!in_array('user', $accesses)) {
                return $this->redirect(['/shop/default/profile']);
            }
        }

        return parent::beforeAction($action);
    }

    public function actionIndex() {
        $searchModel = new UserSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $dataProvider->query->with('image')->andWhere(['role'=>User::ROLE_USER])->andWhere(['!=', 'status', 0]);

        $dataProvider->setSort([
            'defaultOrder' => [
                'id' => 'desc'
            ]
        ]);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider
        ]);
    }

    public function actionLock($id) {
        $model = User::findOne($id);

        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }

        if ($model->status == 1) {
            $model->status = 2;
            $model->token = '';
            $msg = 'Пользователь успешно заблокирован';
        } else {
            $model->status = 1;
            $msg = 'Пользователь успешно разблокирован';
        }

        if ($model->save(false)) {
            Yii::$app->session->setFlash('user_locked', $msg);
        }

        return $this->redirect(Yii::$app->request->referrer);
    }

    public function actionCreate() {
        $model = new User;
        $model->scenario = User::SIGNUP_ADMIN_USER;

        if ($id = Yii::$app->request->get('id')) {
            if ($model = User::find()->with('image', 'addresses')->where(['id'=>$id])->one()) {
                $model->scenario = User::UPDATE_ADMIN_USER;
                $current_password = $model->password;
            } else {
                $model = new User;
                $model->scenario = User::SIGNUP_ADMIN_USER;
            }
        }

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            $model->password = !$model->password ? $current_password : $model->generatePassword($model->password);
            if ($model->saveObject(User::ROLE_USER, 1)) {
                Yii::$app->session->setFlash('user_saved', 'Пользователь успешно сохранен');
            }
            return $this->redirect(['/shop/user/view', 'id'=>$model->id]);
        }
        
        return $this->render('create', [
            'model'=>$model
        ]);
    }

    public function actionView($id) {
        $model = User::find()->with('image', 'addresses')->where(['id'=>$id])->andWhere(['!=', 'status', 0])->andWhere(['!=', 'role', User::ROLE_SHOP])->one();

        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }

        if ($model && $model->load(Yii::$app->request->post())) {
            $image = $model->image ? $model->image : new Images;

            if ($image->imageFiles = UploadedFile::getInstances($model, 'imageFiles')) {
                if ($image->uploadPhoto($model->id, 'user')) {
                    Yii::$app->session->setFlash('photo_uploaded', 'Фото успешно загружено');
                }
            }

            return $this->redirect(Yii::$app->request->referrer);
        }

        return $this->render('view', [
            'model'=>$model
        ]);
    }

    public function actionRemove($id) {
        $model = User::find()->with('image')->where(['id'=>$id])->one();
        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }
        
        if ($this->user && ($this->user->role != User::ROLE_USER) && $model && $model->removeObject()) {
            Yii::$app->session->setFlash('user_deleted', 'Пользователь успешно удален');
        }

        return $this->redirect(['/shop/user']);
    }

    // cards
    public function actionCards($id) {
        $searchModel = new UserCardSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $dataProvider->query->with('cardType')->andWhere(['user_id'=>$id]);

        $dataProvider->setSort([
            'defaultOrder' => [
                'id' => 'desc'
            ]
        ]);

        $card_types = ArrayHelper::map(Category::find()->where(['type'=>'card'])->all(), 'id', 'name_ru');

        return $this->render('card/index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'card_types' => $card_types
        ]);
    }

    public function actionCardCreate($id, $card_id = null) {
        $model = new UserCard;

        if ($card_id) {
            $model = UserCard::find()->with('cardType')->where(['id'=>$card_id, 'user_id'=>$id])->one();
            if (!$model) {
                throw new HttpException(404, 'Page not found');
            }
        }

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->saveObject($id)) {
                Yii::$app->session->setFlash('card_saved', 'Карта успешно сохранена');
            }
            return $this->redirect(['/shop/user/card-view', 'id'=>$id, 'card_id'=>$model->id]);
        }
        
        $card_types = ArrayHelper::map(Category::find()->where(['type'=>'card'])->all(), 'id', 'name_ru');

        return $this->render('card/create', [
            'model' => $model,
            'card_types' => $card_types
        ]);
    }

    public function actionCardView($id, $card_id) {
        $model = UserCard::find()->with('cardType')->where(['id'=>$card_id, 'user_id'=>$id])->one();

        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }

        return $this->render('card/view', [
            'model'=>$model
        ]);
    }

    public function actionCardRemove($id, $card_id) {
        $model = UserCard::findOne(['id'=>$card_id, 'user_id'=>$id]);

        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }

        if ($this->user && ($this->user->role != User::ROLE_USER) && $model && $model->delete()) {
            Yii::$app->session->setFlash('card_removed', 'Карта успешно удалена');
        }

        return $this->redirect(['/shop/user/cards', 'id'=>$id]);
    }

    public function actionCardLock($id, $card_id) {
        $model = UserCard::findOne(['id'=>$card_id, 'user_id'=>$id]);

        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }

        if ($model->status == 1) {
            $model->status = 2;
            $msg = 'Карта успешно заблокирована';
        } else {
            $model->status = 1;
            $msg = 'Карта успешно разблокирована';
        }

        if ($model->save(false)) {
            Yii::$app->session->setFlash('card_locked', $msg);
        }

        return $this->redirect(Yii::$app->request->referrer);
    }
    // end cards

    // orders
    public function actionOrders() {
        $searchModel = new OrderSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $dataProvider->query->with('user', 'payment', 'delivery');

        return $this->render('order/index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider
        ]);
    }
    // end orders
}