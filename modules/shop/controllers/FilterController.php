<?php
namespace app\modules\shop\controllers;

use Yii;
use yii\web\Controller;
use yii\data\Pagination;
use yii\helpers\ArrayHelper;

use app\models\filter\Filter;
use app\models\filter\FilterSearch;
use app\models\user\User;
use app\models\Category;

class FilterController extends Controller{
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

            if (!in_array('filter', $accesses)) {
                throw new HttpException(403, 'В доступе отказано');
            }
        }

        return parent::beforeAction($action);
    }

    public function actionIndex() {
        $searchModel = new FilterSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $dataProvider->query->with('childs', 'category')->andWhere(['parent_id'=>0]);

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

    public function actionCreate() {
        $model = new Filter;

        // edit
        if ($id = Yii::$app->request->get('id')) {
            $model = Filter::find()->with('category', 'category.parent', 'category.parent.childs', 'childs')->where(['id'=>$id])->one();
            if (!$model) {
                throw new HttpException(404, 'Page not found');
            }

            $tree = explode('/', $model->category_tree);
            $current_categories = [];

            foreach ($tree as $key => $id) {
                $current_categories[] = ArrayHelper::map(Category::find()->where(['parent_id'=>$id])->all(), 'id', 'name_ru');
            }
        }
        // end edit

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->saveObject()) {
                Yii::$app->session->setFlash('filter_saved', 'Фильтр успешно сохранен');
                return $this->redirect(['/shop/filter/view', 'id'=>$model->id]);
            }
        }

        $categories = ArrayHelper::map(Category::find()->where(['parent_id'=>0, 'type'=>'product'])->all(), 'id', 'name_ru');

        return $this->render('create', [
            'model' => $model,
            'categories' => $categories,
            'current_categories' => $current_categories,
            'tree' => $tree
        ]);
    }

    public function actionView($id) {
        $model = Filter::find()->with('childs', 'category')->where(['id'=>$id])->one();
        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }

        return $this->render('view', [
            'model' => $model
        ]);
    }

    public function actionLock($id) {
        $model = Filter::findOne($id);
        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }

        if ($model->status == 0) {
            $model->status = 1;
            $msg = 'Фильтр успешно разблокирован';
        } else {
            $model->status = 0;
            $msg = 'Фильтр успешно заблокирован';
        }

        if ($model->save(false)) {
            Yii::$app->session->setFlash('filter_locked', $msg);
        }

        return $this->redirect(Yii::$app->request->referrer);
    }

    public function actionRemove($id) {
        $model = Filter::findOne($id);
        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }

        if ($this->user && ($this->user->role != User::ROLE_USER) && $model && $model->delete()) {
            Filter::deleteAll(['parent_id'=>$id]);
            Yii::$app->session->setFlash('filter_removed', 'Фильтр успешно удален');
        }

        return $this->redirect(['/shop/filter']);
    }
}