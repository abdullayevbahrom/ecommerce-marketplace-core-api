<?php
namespace app\modules\shop\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\web\HttpException;

use app\models\user\User;
use app\models\filter\Filter;
use app\models\Category;

class CategoryController extends Controller{
    public $user;

    public function beforeAction($action) {
        $this->enableCsrfValidation = false;
        if (Yii::$app->user->isGuest) {
            return $this->redirect(['/admin/default']);
        }
        $this->user = User::find()->with('moderatorAccess', 'moderatorAccess.moderator')->where(['id'=>Yii::$app->user->identity->getId()])->one();

        if (($this->user->role == User::ROLE_MODERATOR)) {
            $accesses = array();

            if ($this->user && $this->user->moderatorAccess) {
                foreach ($this->user->moderatorAccess as $v) {
                    if ($v && $v->moderator) {
                        $accesses[] = $v->moderator->url;
                    }
                }
            }

            if (!in_array('category', $accesses)) {
                throw new HttpException(403, 'В доступе отказано');
            }
        }

        return parent::beforeAction($action);
    }

    public function actionIndex($type = null) {
        $model = new Category;

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            $model->type = Yii::$app->request->get('type');
            if ($model->saveCategory()) {
                Yii::$app->session->setFlash('category_saved', 'Категория успешно сохранена');
            }
            return $this->redirect(Yii::$app->request->referrer);
        }

        $categories = $model->getCategories($model->find()->orderBy('sort')->asArray()->where(['type'=>$type])->all());

        return $this->render('index', [
            'model' => $model,
            'categories' => $categories
        ]);
    }

    public function actionRemove($id) {
        $model = Category::findOne($id);

        if (!$model) {
            throw new HttpException(404, 'Категория не найдена');
        }

        if ($model && $model->delete()) {
            Yii::$app->session->setFlash('category_deleted', 'Категория успешно удалена');
        }

        return $this->redirect(Yii::$app->request->referrer);
    }

    public function actionSaveSort(){
        if (Yii::$app->request->isAjax) {
            $data = Yii::$app->request->post();
            $top = explode(',', $data['top']);
            foreach ($top as $k => $v) {
                $id = explode('-',$v);
                $cat = Category::findOne($id[1]);
                $cat->sort = $id[0];
                $cat->save(false);
            }
            $save = true;
            \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            return ['save'=>$save];
        }
    }

    public function actionGetCategory() {
        if (Yii::$app->request->isAjax && ($data = Yii::$app->request->post())) {
            $category = $data['id'] ? Category::find()->with('image')->where(['id'=>$data['id']])->one() : null;

            $data = [
                'id' => $category->id,
                'name_ru' => $category->name_ru,
                'name_uz' => $category->name_uz,
                'name_en' => $category->name_en,
                'description_ru' => $category->description_ru,
                'description_uz' => $category->description_uz,
                'description_en' => $category->description_en,
                'photo' => $category->getPhoto(),
                'photo_id' => $category->image ? $category->image->id : ''
            ];

            Yii::$app->response->format = Response::FORMAT_JSON;
            return ['category'=>$data];
        }
    }

    public function actionGetCategories() {
        if (Yii::$app->request->isAjax && ($data = Yii::$app->request->post())) {

            $cat = null;
            $categories = [];
            $filters = [];
            $product_types = [];
            $parent_id = 0;
            
            if ($data['id']) {
                $cat = Category::find()->with('parent')->where(['id'=>$data['id']])->one();
                $categories = Category::find()->with('image')->where(['parent_id'=>$data['id']])->all();

                if ($cat && $cat->parent) {
                    $filters = Filter::find()->with('childs', 'categoryFilter')->where(['parent_id'=>0])->andWhere(['category_id'=>$data['id']])->orWhere(['category_id'=>$cat->parent->id])->all();
                    $parent_id = $cat->parent->id;
                } else {
                    $filters = Filter::find()->with('childs', 'categoryFilter')->where(['category_id'=>$data['id'], 'parent_id'=>0])->all();
                }
                
                // Get product types for this category or global types
                $product_types = \app\models\product\ProductType::find()
                    ->with('productTypeValues')
                    ->where(['status' => 1])
                    ->andWhere([
                        'or', 
                        ['category_id' => $data['id']]
                    ])
                    ->orderBy('sort ASC')
                    ->all();
            }
            

            Yii::$app->response->format = Response::FORMAT_JSON;
            
            // Serialize filters to include computed fields like is_filter
            $serializedFilters = [];
            foreach ($filters as $filter) {
                $filterData = $filter->toArray();
                // Add computed fields
                $filterData['is_filter'] = ($filter->is_filter == 1) ? true : false;
                $filterData['name'] = $filter->name_ru ?: $filter->name_en ?: $filter->name_uz;
                $filterData['value'] = $filter->value_ru ?: $filter->value_en ?: $filter->value_uz;
                
                // Add childs if they exist
                if ($filter->childs) {
                    $filterData['childs'] = [];
                    foreach ($filter->childs as $child) {
                        $childData = $child->toArray();
                        $childData['value_ru'] = $child->value_ru;
                        $childData['value_en'] = $child->value_en;
                        $childData['value_uz'] = $child->value_uz;
                        $filterData['childs'][] = $childData;
                    }
                }
                
                $serializedFilters[] = $filterData;
            }
            
            return [
                'categories' => $categories, 
                'filters' => $serializedFilters, 
                'product_types' => $product_types,
                'parent_id' => $parent_id
            ];
        }
    }
}
?>