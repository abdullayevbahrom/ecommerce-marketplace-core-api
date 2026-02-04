<?php
namespace app\modules\admin\controllers;

use Yii;
use yii\web\Controller;
use yii\web\UploadedFile;
use yii\helpers\ArrayHelper;

use app\models\user\User;
use app\models\Category;
use app\models\brand\CategoryBrand;
use app\models\brand\CategoryBrandSearch;

class BrandController extends Controller{
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

            if (!in_array('brand', $accesses)) {
                throw new HttpException(403, 'Error access');
            }
        }

        if ($action->id == 'upload') {
            $this->enableCsrfValidation = false;
        }

        return parent::beforeAction($action);
    }

    public function actionIndex() {
        $searchModel = new CategoryBrandSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $dataProvider->query->with('image', 'category');

        $categories = ArrayHelper::map(Category::find()->where(['type'=>'product'])->all(), 'id', 'name_ru');

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'categories' => $categories
        ]);
    }

    public function actionCreate($id = null) {
        $model = new CategoryBrand;
        
        $current_categories = [];
        $tree = [0 => ''];

        if ($id) {
            $model = CategoryBrand::find()->with('image', 'category')->where(['id'=>$id])->one();
            if (!$model) {
                throw new HttpException(404, 'Page not found');
            }

            $tree = explode('/', $model->category_tree);

            foreach ($tree as $key => $id) {
                $current_categories[] = ArrayHelper::map(Category::find()->where(['parent_id'=>$id])->all(), 'id', 'name_ru');
            }
        }

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->saveObject()) {
                Yii::$app->session->setFlash('brand_saved', 'Saved');
                return $this->redirect(['/admin/brand/view', 'id'=>$model->id]);
            }
        }

        $categories = ArrayHelper::map(Category::find()->where(['type'=>'product', 'parent_id'=>0])->all(), 'id', 'name_ru');

        return $this->render('create', [
            'model' => $model,
            'categories' => $categories,
            'current_categories' => $current_categories,
            'tree' => $tree
        ]);
    }

    public function actionView($id) {
        $model = CategoryBrand::find()->with('image', 'category')->where(['id'=>$id])->one();
        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }

        return $this->render('view', [
            'model' => $model
        ]);
    }

    public function actionRemove($id) {
        $model = CategoryBrand::find()->with('image')->where(['id'=>$id])->one();
        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }
        
        if ($this->user && ($this->user->role != User::ROLE_USER) && $model && $model->removeObject()) {
            Yii::$app->session->setFlash('brand_removed', 'Deleted');
        }

        return $this->redirect(Yii::$app->request->referrer ?: ['/admin/brand']);
    }

    public function actionLock($id) {
        $model = CategoryBrand::findOne($id);

        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }

        if ($model->status == 1) {
            $model->status = 2;
            $msg = 'Blocked';
        } else {
            $model->status = 1;
            $msg = 'Unblocked';
        }

        if ($model->save(false)) {
            Yii::$app->session->setFlash('brand_locked', $msg);
        }

        return $this->redirect(Yii::$app->request->referrer);
    }
}