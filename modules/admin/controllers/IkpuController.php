<?php

namespace app\modules\admin\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\web\HttpException;
use yii\web\Response;

use app\models\user\User;
use app\models\Ikpu;
use app\models\IkpuSearch;

/**
 * IkpuController implements the CRUD actions for Ikpu model.
 */
class IkpuController extends Controller
{
    public $user;

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    public function beforeAction($action) 
    {
        $this->enableCsrfValidation = false;
        if (Yii::$app->user->isGuest) {
            return $this->redirect(['/admin/default']);
        }
        $this->user = User::find()->with('moderatorAccess', 'moderatorAccess.moderator')->where(['id'=>Yii::$app->user->identity->id])->one();

        if (!$this->user) {
            Yii::$app->user->logout(false);
            return $this->redirect(["/admin/default"]);
        }

        if (($this->user->role == User::ROLE_MODERATOR)) {
            $accesses = array();

            if ($this->user && $this->user->moderatorAccess) {
                foreach ($this->user->moderatorAccess as $v) {
                    if ($v && $v->moderator) {
                        $accesses[] = $v->moderator->url;
                    }
                }
            }

            if (!in_array('ikpu', $accesses)) {
                throw new HttpException(403, 'Error access');
            }
        }

        return parent::beforeAction($action);
    }

    /**
     * Lists all Ikpu models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new IkpuSearch();
        $dataProvider = $searchModel->searchDataProvider(Yii::$app->request->queryParams);
        
        $dataProvider->setSort([
            'defaultOrder' => [
                'code' => SORT_ASC
            ]
        ]);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Ikpu model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);
        
        return $this->render('view', [
            'model' => $model,
        ]);
    }

    /**
     * Creates a new Ikpu model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Ikpu();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('ikpu_saved', 'ИКПУ успешно создан');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        // Get parent options for dropdown
        $parentOptions = $this->getParentOptions();

        return $this->render('create', [
            'model' => $model,
            'parentOptions' => $parentOptions,
        ]);
    }

    /**
     * Updates an existing Ikpu model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('ikpu_saved', 'ИКПУ успешно обновлен');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        // Get parent options for dropdown
        $parentOptions = $this->getParentOptions($model->code);

        return $this->render('update', [
            'model' => $model,
            'parentOptions' => $parentOptions,
        ]);
    }

    /**
     * Deletes an existing Ikpu model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        
        // Check if IKPU has children
        if ($model->getChildren()->count() > 0) {
            Yii::$app->session->setFlash('ikpu_error', 'Нельзя удалить ИКПУ, который имеет дочерние элементы');
            return $this->redirect(['view', 'id' => $id]);
        }
        
        // Check if IKPU is used by products
        if ($model->getProducts()->count() > 0) {
            Yii::$app->session->setFlash('ikpu_error', 'Нельзя удалить ИКПУ, который используется в товарах');
            return $this->redirect(['view', 'id' => $id]);
        }

        $model->delete();
        Yii::$app->session->setFlash('ikpu_removed', 'ИКПУ успешно удален');

        return $this->redirect(['index']);
    }

    /**
     * Toggle status of an existing Ikpu model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionToggleStatus($id)
    {
        $model = $this->findModel($id);
        
        $model->status = $model->status == Ikpu::STATUS_ACTIVE ? Ikpu::STATUS_INACTIVE : Ikpu::STATUS_ACTIVE;
        
        if ($model->save(false)) {
            $statusText = $model->status == Ikpu::STATUS_ACTIVE ? 'активирован' : 'деактивирован';
            Yii::$app->session->setFlash('ikpu_status', "ИКПУ успешно {$statusText}");
        }

        return $this->redirect(Yii::$app->request->referrer ?: ['index']);
    }

    /**
     * AJAX action to get children of a parent IKPU
     * @param string $parent_code
     * @return Response
     */
    public function actionGetChildren($parent_code = null)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $children = Ikpu::find()
            ->where(['parent_code' => $parent_code, 'status' => Ikpu::STATUS_ACTIVE])
            ->orderBy(['code' => SORT_ASC])
            ->all();
        
        $result = [];
        foreach ($children as $child) {
            $result[] = [
                'id' => $child->code,
                'text' => $child->getDisplayText()
            ];
        }
        
        return $result;
    }

    /**
     * AJAX action to search IKPU by query
     * @param string $q
     * @return Response
     */
    public function actionSearch($q = '')
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $ikpuList = Ikpu::search($q)->limit(20)->all();
        
        $result = [];
        foreach ($ikpuList as $ikpu) {
            $result[] = [
                'id' => $ikpu->code,
                'text' => $ikpu->getDisplayText()
            ];
        }
        
        return $result;
    }

    /**
     * Finds the Ikpu model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Ikpu the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Ikpu::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }

    /**
     * Get parent options for dropdown
     * @param string $excludeCode Code to exclude from options (for self-reference prevention)
     * @return array
     */
    protected function getParentOptions($excludeCode = null)
    {
        $query = Ikpu::findActive()->orderBy(['code' => SORT_ASC]);
        
        if ($excludeCode) {
            $query->andWhere(['!=', 'code', $excludeCode]);
        }
        
        $ikpuList = $query->all();
        $options = ['' => 'Корневой элемент'];
        
        foreach ($ikpuList as $ikpu) {
            $options[$ikpu->code] = $ikpu->getDisplayText();
        }
        
        return $options;
    }
}
