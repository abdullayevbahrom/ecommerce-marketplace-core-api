<?php
namespace app\modules\logist\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\web\HttpException;

use app\models\user\User;
use app\models\logist\Logist;
use app\models\filter\Filter;
use app\models\Category;

class CategoryController extends Controller{
    public $user;
    public $logist;

    public function beforeAction($action) {
        $this->enableCsrfValidation = false;
        if (Yii::$app->user->isGuest) {
            return $this->redirect(['/logist/default']);
        }
        $this->user = User::find()->with('moderatorAccess', 'moderatorAccess.moderator')->where(['id'=>Yii::$app->user->identity->id])->one();
        $this->logist = Logist::findOne(['user_id'=>$this->user->id]);

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

            if ($data['id']) {
                $categories = Category::find()->with('image')->where(['parent_id'=>$data['id']])->all();
                $filters = Filter::find()->with('childs')->where(['category_id'=>$data['id'], 'parent_id'=>0])->all();
            }
            

            Yii::$app->response->format = Response::FORMAT_JSON;
            return ['categories'=>$categories, 'filters'=>$filters];
        }
    }
}
?>