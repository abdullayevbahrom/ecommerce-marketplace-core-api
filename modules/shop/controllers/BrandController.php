<?php
namespace app\modules\shop\controllers;

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
            return $this->redirect(['/shop/default']);
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
                throw new HttpException(403, 'В доступе отказано');
            }
        }

        if ($action->id == 'upload') {
            $this->enableCsrfValidation = false;
        }

        return parent::beforeAction($action);
    }

    public function actionIndex(){
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

        if ($id) {
            $model = CategoryBrand::find()->with('image', 'category')->where(['id'=>$id])->one();
            if (!$model) {
                throw new HttpException(404, 'Page not found');
            }

            $tree = explode('/', $model->category_tree);
            $current_categories = [];

            foreach ($tree as $key => $id) {
                $current_categories[] = ArrayHelper::map(Category::find()->where(['parent_id'=>$id])->all(), 'id', 'name_ru');
            }
        }

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->saveObject()) {
                Yii::$app->session->setFlash('brand_saved', 'Бренд успешно сохранен');
                return $this->redirect(['/shop/brand/view', 'id'=>$model->id]);
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
            Yii::$app->session->setFlash('brand_removed', 'Бренд успешно удален');
        }

        return $this->redirect(['/shop/brand']);
    }

    public function actionLock($id) {
        $model = CategoryBrand::findOne($id);

        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }

        if ($model->status == 1) {
            $model->status = 2;
            $msg = 'Бренд успешно заблокирован';
        } else {
            $model->status = 1;
            $msg = 'Бренд успешно разблокирован';
        }

        if ($model->save(false)) {
            Yii::$app->session->setFlash('brand_locked', $msg);
        }

        return $this->redirect(Yii::$app->request->referrer);
    }

    public function actionParse() {
        $str = unserialize('a:14:{s:10:"agent_code";a:2:{s:10:"attributes";a:1:{s:8:"xsi:type";s:10:"xsd:string";}s:6:"$value";s:32:"eDAqEtDceko9tXmCJ5KRveB5XUg-xDDT";}s:7:"user_id";a:2:{s:10:"attributes";a:1:{s:8:"xsi:type";s:10:"xsd:string";}s:6:"$value";s:9:"agro_bank";}s:15:"agent_reference";a:2:{s:10:"attributes";a:1:{s:8:"xsi:type";s:10:"xsd:string";}s:6:"$value";s:25:"00811QT220403713065450978";}s:19:"receiver_account_no";a:2:{s:10:"attributes";a:1:{s:8:"xsi:type";s:10:"xsd:string";}s:6:"$value";s:33:"ban:9860000000000000;bic=PAKHUZ22";}s:17:"rec_currency_code";a:2:{s:10:"attributes";a:1:{s:8:"xsi:type";s:10:"xsd:string";}s:6:"$value";s:3:"UZS";}s:16:"receiving_amount";a:2:{s:10:"attributes";a:1:{s:8:"xsi:type";s:10:"xsd:string";}s:6:"$value";s:8:"463352.4";}s:16:"transaction_type";a:2:{s:10:"attributes";a:1:{s:8:"xsi:type";s:10:"xsd:string";}s:6:"$value";s:1:"B";}s:17:"sender_account_no";a:2:{s:10:"attributes";a:1:{s:8:"xsi:type";s:10:"xsd:string";}s:6:"$value";s:31:"ban:69191044434507;bic=KOEXKRSE";}s:22:"beneficiary_first_name";a:2:{s:10:"attributes";a:1:{s:8:"xsi:type";s:10:"xsd:string";}s:6:"$value";s:9:"SAMPLE";}s:21:"beneficiary_last_name";a:2:{s:10:"attributes";a:1:{s:8:"xsi:type";s:10:"xsd:string";}s:6:"$value";s:12:"CLIENT";}s:19:"beneficiary_address";a:2:{s:10:"attributes";a:1:{s:8:"xsi:type";s:10:"xsd:string";}s:6:"$value";s:8:"Hamangan";}s:20:"beneficiary_identity";a:2:{s:10:"attributes";a:1:{s:8:"xsi:type";s:10:"xsd:string";}s:6:"$value";s:3:"NIC";}s:26:"beneficiary_contact_number";a:2:{s:10:"attributes";a:1:{s:8:"xsi:type";s:10:"xsd:string";}s:6:"$value";s:12:"998901234567";}s:9:"signature";a:2:{s:10:"attributes";a:1:{s:8:"xsi:type";s:10:"xsd:string";}s:6:"$value";s:64:"e8c0846fa1d0b2e24d870933623a4f9aeb1f897dbecda0f4b9924dce01dd4f39";}}');
        // echo '<pre>';
        // print_r($str);
        // die;

        foreach ($str as $k => $v) {
            echo $k.' : '.$v['$value'].'<br/>';
        }
    }
}