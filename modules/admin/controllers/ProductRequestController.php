<?php
namespace app\modules\admin\controllers;

use Yii;
use yii\web\Controller;
use yii\web\HttpException;

use app\models\user\User;
use app\models\product\ProductRequest;
use app\models\product\ProductRequestSearch;

class ProductRequestController extends Controller {
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

            if (!in_array('product-request', $accesses)) {
                throw new HttpException(403, 'Error access');
            }
        }

        return parent::beforeAction($action);
    }

    /**
     * Lists all ProductRequest models.
     * @return mixed
     */
    public function actionIndex() {
        $searchModel = new ProductRequestSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider
        ]);
    }

    /**
     * Displays a single ProductRequest model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id) {
        $model = ProductRequest::findOne($id);
        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }

        // Handle status update via POST
        if (Yii::$app->request->isPost) {
            $post = Yii::$app->request->post();
            
            if (isset($post['status'])) {
                $model->status = $post['status'];
                
                // Set admin who responded to the request
                $model->admin_id = Yii::$app->user->id;
                
                if (isset($post['admin_notes'])) {
                    $model->admin_notes = $post['admin_notes'];
                }
                
                if ($model->save(false)) {
                    Yii::$app->session->setFlash('request_updated', 'Статус запроса обновлен');
                    return $this->redirect(['view', 'id' => $model->id]);
                }
            }
        }

        return $this->render('view', [
            'model' => $model
        ]);
    }

    /**
     * Deletes an existing ProductRequest model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionRemove($id) {
        $model = ProductRequest::findOne($id);
        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }
        
        if ($this->user && ($this->user->role != User::ROLE_USER) && $model && $model->removeRequest()) {
            Yii::$app->session->setFlash('request_removed', 'Запрос успешно удален');
        }

        return $this->redirect(['/admin/product-request']);
    }

    /**
     * Approve request
     */
    public function actionApprove($id) {
        $model = ProductRequest::findOne($id);
        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }

        $model->status = ProductRequest::STATUS_APPROVED;
        $model->admin_id = Yii::$app->user->id; // Track which admin approved the request
        
        if ($model->save(false)) {
            Yii::$app->session->setFlash('request_approved', 'Запрос одобрен');
        }

        return $this->redirect(Yii::$app->request->referrer ?: ['view', 'id' => $model->id]);
    }

    /**
     * Reject request
     */
    public function actionReject($id) {
        $model = ProductRequest::findOne($id);
        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }

        $model->status = ProductRequest::STATUS_REJECTED;
        $model->admin_id = Yii::$app->user->id; // Track which admin rejected the request
        
        if ($model->save(false)) {
            Yii::$app->session->setFlash('request_rejected', 'Запрос отклонен');
        }

        return $this->redirect(Yii::$app->request->referrer ?: ['view', 'id' => $model->id]);
    }
} 