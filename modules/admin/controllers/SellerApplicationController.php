<?php
namespace app\modules\admin\controllers;

use Yii;
use yii\web\Controller;
use yii\web\HttpException;

use app\models\user\User;
use app\models\seller\SellerApplication;
use app\models\seller\SellerApplicationSearch;

class SellerApplicationController extends Controller {
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

            if (!in_array('seller-application', $accesses)) {
                throw new HttpException(403, 'Error access');
            }
        }

        return parent::beforeAction($action);
    }

    /**
     * Lists all SellerApplication models.
     * @return mixed
     */
    public function actionIndex() {
        $searchModel = new SellerApplicationSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider
        ]);
    }

    /**
     * Displays a single SellerApplication model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id) {
        $model = SellerApplication::findOne($id);
        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }

        // Handle status update via POST
        if (Yii::$app->request->isPost) {
            $post = Yii::$app->request->post();
            
            if (isset($post['status'])) {
                $model->status = $post['status'];
                
                if (isset($post['admin_notes'])) {
                    $model->admin_notes = $post['admin_notes'];
                }
                
                if ($model->save(false)) {
                    Yii::$app->session->setFlash('application_updated', 'Статус заявки обновлен');
                    return $this->redirect(['view', 'id' => $model->id]);
                }
            }
        }

        return $this->render('view', [
            'model' => $model
        ]);
    }

    /**
     * Deletes an existing SellerApplication model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionRemove($id) {
        $model = SellerApplication::findOne($id);
        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }
        
        if ($this->user && ($this->user->role != User::ROLE_USER) && $model && $model->delete()) {
            Yii::$app->session->setFlash('application_removed', 'Заявка успешно удалена');
        }

        return $this->redirect(['/admin/seller-application']);
    }

    /**
     * Approve application
     */
    public function actionApprove($id) {
        $model = SellerApplication::findOne($id);
        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }

        $model->status = SellerApplication::STATUS_APPROVED;
        
        if ($model->save(false)) {
            Yii::$app->session->setFlash('application_approved', 'Заявка одобрена');
        }

        return $this->redirect(Yii::$app->request->referrer ?: ['view', 'id' => $model->id]);
    }

    /**
     * Reject application
     */
    public function actionReject($id) {
        $model = SellerApplication::findOne($id);
        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }

        $model->status = SellerApplication::STATUS_REJECTED;
        
        if ($model->save(false)) {
            Yii::$app->session->setFlash('application_rejected', 'Заявка отклонена');
        }

        return $this->redirect(Yii::$app->request->referrer ?: ['view', 'id' => $model->id]);
    }
} 