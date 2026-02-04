<?php
namespace app\modules\admin\controllers;

use Yii;
use yii\web\Controller;
use yii\web\UploadedFile;
use yii\web\HttpException;
use yii\helpers\ArrayHelper;

use app\models\user\User;
use app\models\product\Product;
use app\models\product\review\ProductReview;
use app\models\product\review\ProductReviewSearch;

class ReviewController extends Controller{
	public $user;
    
    public function beforeAction($action) {
        // Enable CSRF validation for status change actions for security
        if (in_array($action->id, ['change-status', 'update-status'])) {
            $this->enableCsrfValidation = true;
        } else {
            $this->enableCsrfValidation = false;
        }
        
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

            if (!in_array('review', $accesses)) {
                throw new HttpException(403, 'Error access');
            }
        }

        if ($action->id == 'upload') {
            $this->enableCsrfValidation = false;
        }

        return parent::beforeAction($action);
    }

    public function actionIndex(){
        $searchModel = new ProductReviewSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $dataProvider->query->with('product', 'user');

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
        if (!$model) {
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
            Yii::$app->session->setFlash('product_removed', 'Deleted');
        }

        return $this->redirect(['/admin/product']);
    }

    public function actionAccept($id) {
        $model = ProductReview::find()->with('product', 'user')->where(['id'=>$id])->one();
        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }

        if ($model->changeStatus(ProductReview::STATUS_ACCEPTED, Yii::$app->user->identity->id)) {
            Yii::$app->session->setFlash('success', 'Review accepted successfully');
        } else {
            Yii::$app->session->setFlash('error', 'Failed to accept review');
        }

        return $this->redirect(['index']);
    }

    public function actionReject($id) {
        $model = ProductReview::find()->with('product', 'user')->where(['id'=>$id])->one();
        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }

        $comment = Yii::$app->request->post('comment', '');

        if ($model->changeStatus(ProductReview::STATUS_REJECTED, Yii::$app->user->identity->id, $comment)) {
            Yii::$app->session->setFlash('success', 'Review rejected successfully');
        } else {
            Yii::$app->session->setFlash('error', 'Failed to reject review');
        }

        return $this->redirect(['index']);
    }

    public function actionProcess($id) {
        $model = ProductReview::find()->with('product', 'user')->where(['id'=>$id])->one();
        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }

        if ($model->changeStatus(ProductReview::STATUS_PROCESSED, Yii::$app->user->identity->id)) {
            Yii::$app->session->setFlash('success', 'Review marked as processed');
        } else {
            Yii::$app->session->setFlash('error', 'Failed to process review');
        }

        return $this->redirect(['index']);
    }

    public function actionUpdateStatus($id) {
        $model = ProductReview::find()->with('product', 'user')->where(['id'=>$id])->one();
        if (!$model) {
            throw new HttpException(404, 'Page not found');
        }

        $post = Yii::$app->request->post();
        
        if (isset($post['status']) && $post['status'] !== '') {
            $comment = isset($post['comment']) ? trim($post['comment']) : '';
            
            if ($model->changeStatus($post['status'], Yii::$app->user->identity->id, $comment)) {
                Yii::$app->session->setFlash('success', 'Статус отзыва успешно обновлен');
            } else {
                Yii::$app->session->setFlash('error', 'Ошибка при обновлении статуса отзыва');
            }
        } else {
            Yii::$app->session->setFlash('error', 'Необходимо выбрать новый статус');
        }

        return $this->redirect(['view', 'id' => $id]);
    }

    public function actionChangeStatus() {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        // Log the request method for debugging
        Yii::info('Change status request method: ' . Yii::$app->request->getMethod(), __METHOD__);
        
        // Check if it's a POST request
        if (!Yii::$app->request->isPost) {
            Yii::$app->response->statusCode = 405;
            return ['success' => false, 'message' => 'Method not allowed. Received: ' . Yii::$app->request->getMethod()];
        }
        
        $post = Yii::$app->request->post();
        
        if (!isset($post['id']) || !isset($post['status'])) {
            Yii::$app->response->statusCode = 422;
            return ['success' => false, 'message' => 'Отсутствуют обязательные параметры'];
        }

        $model = ProductReview::findOne($post['id']);
        if (!$model) {
            Yii::$app->response->statusCode = 404;
            return ['success' => false, 'message' => 'Отзыв не найден'];
        }

        $comment = isset($post['comment']) ? $post['comment'] : '';

        if ($model->changeStatus($post['status'], Yii::$app->user->identity->id, $comment)) {
            return ['success' => true, 'message' => 'Статус успешно обновлен'];
        } else {
            return ['success' => false, 'message' => 'Не удалось обновить статус'];
        }
    }
}