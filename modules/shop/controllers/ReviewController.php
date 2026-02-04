<?php
namespace app\modules\shop\controllers;

use Yii;
use yii\web\Controller;
use yii\web\UploadedFile;
use yii\web\HttpException;
use yii\helpers\ArrayHelper;

use app\models\user\User;
use app\models\shop\Shop;
use app\models\product\Product;
use app\models\product\review\ProductReview;
use app\models\product\review\ProductReviewSearch;

class ReviewController extends Controller{
	public $user;
    public $shop;
    
    public function beforeAction($action) {
        $this->enableCsrfValidation = false;
        if (Yii::$app->user->isGuest) {
            return $this->redirect(['/shop/default']);
        }
        $this->user = User::find()->with('moderatorAccess', 'moderatorAccess.moderator')->where(['id'=>Yii::$app->user->identity->id])->one();
        $this->shop = Shop::findOne(['user_id'=>$this->user->id]);

        if (($this->user->role == User::ROLE_MODERATOR)) {
            $accesses = array();

            if ($this->user && $this->user->moderatorAccess) {
                foreach ($this->user->moderatorAccess as $v) {
                    if ($v && $v->moderator) {
                        $accesses[] = $v->moderator->url;
                    }
                }
            }

            if (!in_array('review', $accesses)) {
                throw new HttpException(403, 'В доступе отказано');
            }
        }

        if ($action->id == 'upload') {
            $this->enableCsrfValidation = false;
        }

        return parent::beforeAction($action);
    }

    public function actionIndex(){
        $products = ArrayHelper::map(Product::find()->where(['shop_id'=>$this->shop->id])->all(), 'id', 'id');

        $searchModel = new ProductReviewSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $dataProvider->query->with('product', 'user')->andWhere(['in', 'product_id', $products]);

        $products = ArrayHelper::map(Product::find()->where(['status'=>1])->all(), 'id', 'name_ru');
        $users = ArrayHelper::map(User::find()->where(['status'=>1])->all(), 'id', 'name');

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'products' => $products,
            'users' => $users
        ]);
    }

    public function actionView($id) {
        $model = ProductReview::find()->with('product', 'user')->where(['id'=>$id])->one();
        if (!$model || ($model && ($model->product->shop_id != $this->shop->id))) {
            throw new HttpException(404, 'Page not found');
        }

        return $this->render('view', [
            'model' => $model
        ]);
    }

    public function actionRemove($id) {
        $model = ProductReview::find()->with('product', 'user')->where(['id'=>$id])->one();
        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }
        
        if ($this->user && ($this->user->role != User::ROLE_USER) && $model && $model->removeObject()) {
            Yii::$app->session->setFlash('product_removed', 'Отзыв успешно удален');
        }

        return $this->redirect(['/shop/product']);
    }
}