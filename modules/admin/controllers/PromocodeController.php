<?php

namespace app\modules\admin\controllers;

use Yii;
use app\models\Promocode;
use app\models\PromocodeSearch;
use app\models\user\User;
use app\models\product\review\ProductReview;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * PromocodeController implements the CRUD actions for Promocode model.
 */
class PromocodeController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    public function beforeAction($action) {
        if (Yii::$app->user->isGuest) {
            return $this->redirect(['/admin/default']);
        }
        
        $user = User::findOne(Yii::$app->user->id);
        if (!$user || $user->role != User::ROLE_ADMIN) {
             throw new \yii\web\ForbiddenHttpException('You are not allowed to perform this action.');
        }

        return parent::beforeAction($action);
    }

    /**
     * Lists all Promocode models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new PromocodeSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Promocode model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new Promocode model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Promocode();
        $model->status = 1; // Default active
        $model->code = $this->generateRandomCode(8); // Auto-generate code
        
        // Pre-fill user_id if provided (e.g. from user view page)
        if ($userId = Yii::$app->request->get('user_id')) {
            $model->user_id = (int)$userId;
            $model->usage_limit = 1; // Default for personal
            $model->usage_limit_per_user = 1;
        }

        if ($model->load(Yii::$app->request->post())) {
            // Check if generator mode is active
            if ($model->is_generator) {
                if ($model->validate(['target_group', 'generator_start_date', 'generator_end_date', 'type', 'value', 'min_order_amount', 'title_ru', 'description_ru'])) {
                    $promocodeData = [
                        'type' => $model->type,
                        'value' => $model->value,
                        'min_order_amount' => $model->min_order_amount,
                        'title_ru' => $model->title_ru,
                        'description_ru' => $model->description_ru,
                        'start_date' => date('Y-m-d H:i:s'), // Valid from now
                        // Inherit other settings like usage limit from form if needed, or default
                    ];
                    
                    $count = 0;
                    if ($model->target_group === 'registered') {
                        $count = $this->generateForRegisteredUsers($model->generator_start_date, $model->generator_end_date, $promocodeData);
                    } elseif ($model->target_group === 'reviewers') {
                        $count = $this->generateForReviewers($model->generator_start_date, $model->generator_end_date, $promocodeData);
                    }
                    
                    Yii::$app->session->setFlash('success', "Успешно сгенерировано {$count} промокодов.");
                    return $this->redirect(['index']);
                }
            } else {
                // Normal creation
                if ($model->save()) {
                    if ($model->user_id) {
                        // If personal, logic
                    }
                    return $this->redirect(['view', 'id' => $model->id]);
                }
            }
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing Promocode model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing Promocode model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the Promocode model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Promocode the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Promocode::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }

    /**
     * Generates a random alphanumeric code
     * @param int $length
     * @return string
     */
    protected function generateRandomCode($length = 10)
    {
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $randomString;
    }

    /**
     * Generate promocodes for users registered in a date range
     * @param string $startDate
     * @param string $endDate
     * @param array $promocodeData Data for the promocode (value, type, etc)
     * @return int Number of promocodes generated
     */
    public function generateForRegisteredUsers($startDate, $endDate, $promocodeData)
    {
        $users = User::find()
            ->where(['>=', 'date', $startDate])
            ->andWhere(['<=', 'date', $endDate])
            ->andWhere(['status' => 1]) // Active users only
            ->all();
            
        return $this->generateForUsers($users, $promocodeData);
    }

    /**
     * Generate promocodes for users who left a review in a date range
     * @param string $startDate
     * @param string $endDate
     * @param array $promocodeData
     * @return int Number of promocodes generated
     */
    public function generateForReviewers($startDate, $endDate, $promocodeData)
    {
        // Find users who have at least one review in the date range
        // Using distinct to avoid generating multiple codes for same user
        $userIds = ProductReview::find()
            ->select('user_id')
            ->distinct()
            ->where(['>=', 'date', $startDate])
            ->andWhere(['<=', 'date', $endDate])
            ->column();
            
        $users = User::find()
            ->where(['id' => $userIds])
            ->andWhere(['status' => 1])
            ->all();
            
        return $this->generateForUsers($users, $promocodeData);
    }

    /**
     * Helper to generate unique codes for a list of users
     */
    protected function generateForUsers($users, $promocodeData)
    {
        $count = 0;
        foreach ($users as $user) {
            $model = new Promocode();
            $model->attributes = $promocodeData;
            $model->user_id = $user->id;
            $model->code = $this->generateRandomCode(8);
            $model->status = 1;
            $model->usage_limit = 1;
            $model->usage_limit_per_user = 1;
            
            // Ensure code uniqueness
            while (Promocode::findOne(['code' => $model->code])) {
                $model->code = $this->generateRandomCode(8);
            }
            
            if ($model->save()) {
                $count++;
                
                // Optional: Send notification/email to user about new promocode
                // $this->notifyUser($user, $model);
            }
        }
        return $count;
    }

    /**
     * Search users for Select2
     * @param string $q
     * @param int $id
     * @return array
     */
    public function actionUserList($q = null, $id = null)
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $out = ['results' => ['id' => '', 'text' => '']];
        
        if (!is_null($q)) {
            $query = new \yii\db\Query;
            $query->select('id, name, lastname, phone')
                ->from('user')
                ->where(['like', 'name', $q])
                ->orWhere(['like', 'lastname', $q])
                ->orWhere(['like', 'phone', $q])
                ->orWhere(['like', 'email', $q])
                ->limit(20);
            
            $command = $query->createCommand();
            $data = $command->queryAll();
            $out['results'] = array_values($data);
            
            // Format for Select2
            $results = [];
            foreach ($data as $user) {
                $text = $user['name'];
                if (!empty($user['lastname'])) $text .= ' ' . $user['lastname'];
                // Phone number removed to avoid confusion
                // if (!empty($user->phone)) $text .= ' (' . $user->phone . ')';
                
                $results[] = [
                    'id' => $user['id'],
                    'text' => $text . ' (ID: ' . $user['id'] . ')'
                ];
            }
            $out['results'] = $results;
        } elseif ($id > 0) {
            $user = User::findOne($id);
            if ($user) {
                $text = $user->name;
                if (!empty($user->lastname)) $text .= ' ' . $user->lastname;
                
                $out['results'] = ['id' => $id, 'text' => $text . ' (ID: ' . $id . ')'];
            } else {
                $out['results'] = ['id' => $id, 'text' => 'User #' . $id];
            }
        }
        
        return $out;
    }
}
