<?php

namespace app\modules\api\controllers;

use app\models\merchant\MerchantQuestion;
use app\models\merchant\MerchantQuestionMessage;
use app\models\product\Product;
use app\models\user\User;
use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\Cors;
use yii\filters\VerbFilter;
use yii\rest\Controller;
use yii\web\HttpException;
use yii\web\Response;

class MerchantQuestionController extends Controller
{
    public function beforeAction($action)
    {
        $this->enableCsrfValidation = false;

        return parent::beforeAction($action);
    }

    public function behaviors()
    {
        $behaviors = parent::behaviors();

        unset($behaviors['authenticator']);

        $behaviors['corsFilter'] = [
            'class' => Cors::class,
            'cors' => [
                'Origin' => ['*'],
                'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
                'Access-Control-Request-Headers' => ['Authorization', 'Content-Type'],
                'Access-Control-Allow-Credentials' => false,
                'Access-Control-Max-Age' => 86400,
            ],
        ];

        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::class,
            'except' => ['options'],
        ];

        $behaviors['verbs'] = [
            'class' => VerbFilter::class,
            'actions' => [
                'index' => ['GET'],
                'show' => ['GET'],
                'create' => ['POST'],
                'close' => ['POST'],
                'options' => ['OPTIONS'],
            ],
        ];

        return $behaviors;
    }

    public function actions()
    {
        $actions = parent::actions();
        $actions['options'] = [
            'class' => \yii\rest\OptionsAction::class,
        ];

        return $actions;
    }

    public function actionIndex()
    {
        $user = Yii::$app->user->identity;

        if (!$user) {
            throw new HttpException(401, 'Unauthorized');
        }

        $query = MerchantQuestion::find()
            ->with([
                'messages',
                'product' => function ($q) {
                    $q->select(['id', 'name_ru']);
                },
                'client' => function ($q) {
                    $q->select(['id', 'phone', 'name']);
                },
                'merchant' => function ($q) {
                    $q->select(['id', 'phone', 'name']);
                },
            ])->orderBy(['created_at' => SORT_DESC]);

        if (in_array($user->role, [User::ROLE_ADMIN, User::ROLE_MODERATOR])) {
        } elseif ($user->role === User::ROLE_SHOP) {
            $query->andWhere(['merchant_id' => $user->id]);
        } elseif ($user->role === User::ROLE_USER) {
            $query->andWhere(['client_id' => $user->id]);
        } else {
            throw new HttpException(403, 'Access denied');
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => Yii::$app->request->get('per_page', 15),
                'validatePage' => false,
            ],
        ]);

        return [
            'success' => true,
            'data' => $dataProvider->getModels(),
            'meta' => [
                'total' => $dataProvider->getTotalCount(),
                'page' => $dataProvider->pagination->page + 1,
                'per_page' => $dataProvider->pagination->pageSize,
                'page_count' => $dataProvider->pagination->getPageCount(),
            ],
        ];
    }

    public function actionShow($id)
    {
        $user = Yii::$app->user->identity;

        if (!$user) {
            throw new HttpException(401, 'Unauthorized');
        }

        /** @var MerchantQuestion|null $question */
        $question = MerchantQuestion::find()
            ->with([
                'messages',
                'product' => function ($q) {
                    $q->select(['id', 'name_ru']);
                },
                'client' => function ($q) {
                    $q->select(['id', 'phone', 'name']);
                },
                'merchant' => function ($q) {
                    $q->select(['id', 'phone', 'name']);
                },
            ])
            ->where(['id' => (int) $id])
            ->one();

        if (!$question) {
            throw new HttpException(404, 'Question not found');
        }

        if (!in_array($user->role, [User::ROLE_ADMIN, User::ROLE_MODERATOR], true)) {
            if ($user->role === User::ROLE_SHOP && (int) $question->merchant_id !== (int) $user->id) {
                throw new HttpException(403, 'Access denied');
            }

            if ($user->role === User::ROLE_USER && (int) $question->client_id !== (int) $user->id) {
                throw new HttpException(403, 'Access denied');
            }

            if (!in_array($user->role, [User::ROLE_SHOP, User::ROLE_USER], true)) {
                throw new HttpException(403, 'Access denied');
            }
        }

        return [
            'success' => true,
            'data' => $question,
        ];
    }

    public function actionCreate()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $user = Yii::$app->user->identity;

        if ($user->role !== User::ROLE_USER) {
            return ['success' => false, 'message' => 'Only client can create question'];
        }

        $merchantId = (int) Yii::$app->request->post('merchant_id');
        $productId = (int) Yii::$app->request->post('product_id');
        $message = trim(Yii::$app->request->post('message'));

        if (!$merchantId || !$message) {
            return [
                'success' => false,
                'message' => 'merchant_id and message are required'
            ];
        }

        $uploadedFiles = \yii\web\UploadedFile::getInstancesByName('files');
        $merchant = User::find()->where(['id' => $merchantId, 'role' => User::ROLE_SHOP])->one();

        if (!$merchant) {
            return [
                'success' => false,
                'message' => 'Merchant not found'
            ];
        }

        if ($productId && !Product::find()->where(['id' => $productId])->exists()) {
            return ['success' => false, 'message' => 'Product not found'];
        }

        $model = MerchantQuestion::find()
            ->where([
                'client_id' => $user->id,
                'merchant_id' => $merchant->id,
                'entity_id' => $productId ?: null,
                'entity_type' => MerchantQuestion::ENTITY_TYPE_PRODUCT
            ])
            ->andWhere(['!=', 'status', MerchantQuestion::STATUS_CLOSED])
            ->one();

        if ($model) {
            $lastMessage = MerchantQuestionMessage::find()
                ->where(['question_id' => $model->id])
                ->orderBy(['id' => SORT_DESC])
                ->one();

            if ($lastMessage && $lastMessage->sender_role === MerchantQuestionMessage::ROLE_CLIENT && $lastMessage->sender_id == $user->id) {
                return [
                    'success' => false,
                    'message' => 'You have already replied to this question. Please wait'
                ];
            }
        } else {
            $model = new MerchantQuestion();
            $model->client_id = $user->id;
            $model->merchant_id = $merchant->id;
            $model->entity_type = $productId ? MerchantQuestion::ENTITY_TYPE_PRODUCT : null;
            $model->entity_id = $productId ?: null;
            $model->created_at = time();
        }

        $model->status = MerchantQuestion::STATUS_OPEN;

        $transaction = Yii::$app->db->beginTransaction();

        try {
            if (!$model->save()) {
                $transaction->rollBack();
                return ['success' => false, 'errors' => $model->errors];
            }

            $msg = new MerchantQuestionMessage();
            $msg->question_id = $model->id;
            $msg->sender_role = MerchantQuestionMessage::ROLE_CLIENT;
            $msg->sender_id = $user->id;
            $msg->message = $message;
            $msg->created_at = time();
            $msg->rawFiles = $uploadedFiles;

            if (!$msg->save()) {
                $transaction->rollBack();
                return ['success' => false, 'errors' => $msg->errors];
            }

            $transaction->commit();

            try {
                $this->sendDataToWarehouse($user, $merchant, $model, $msg, $productId);
            } catch (\Throwable $e) {
                Yii::error("Warehouse error: " . $e->getMessage(), 'warehouse_log');
            }

            return ['success' => true, 'question_id' => $model->id];
        } catch (\Throwable $e) {
            if ($transaction->isActive) {
                $transaction->rollBack();
            }
            Yii::error($e->getMessage(), 'create_question_for_merchant');

            return ['success' => false, 'message' => 'Internal Server Error'];
        }
    }

    public function actionClose($id)
    {
        $user = Yii::$app->user->identity;

        if (!$user) {
            throw new HttpException(401, 'Unauthorized');
        }

        /** @var MerchantQuestion $question */
        $question = MerchantQuestion::findOne($id);

        if (!$question) {
            throw new HttpException(404, 'Question not found');
        }

        if ($user->id !== $question->client_id) {
            throw new HttpException(403, 'Only client can close this ticket');
        }

        $transaction = Yii::$app->db->beginTransaction();

        try {

            if ($question->status === MerchantQuestion::STATUS_CLOSED) {
                throw new HttpException(422, 'Question already closed');
            }

            if ($question->status === MerchantQuestion::STATUS_OPEN) {
                throw new HttpException(422, 'Cannot close unanswered question');
            }

            $question->status = MerchantQuestion::STATUS_CLOSED;
            $question->closed_at = time();
            $question->save(false);

            $this->syncCloseToWarehouse($user, $question);

            $transaction->commit();

            // NotificationService::notifyQuestionClosed($question, $user);

            return [
                'success' => true,
                'message' => 'Question closed successfully',
            ];
        } catch (\Throwable $e) {
            $transaction->rollBack();
            Yii::error($e->getMessage(), 'close_question_for_merchant');

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function syncCloseToWarehouse(User $user, MerchantQuestion $question)
    {
        $baseUrl = Yii::$app->params['warehouseApiUrl'];
        $secretKey = Yii::$app->params['apiSecretKey'];

        $payload = [
            'id' => $user->id,
            'ticket_id' => $question->id,
            'closed_by' => 'client',
            'closed_at' => time(),
        ];

        $token = md5($user->id . $secretKey);

        try {
            $client = new \GuzzleHttp\Client([
                'timeout' => 5,
            ]);

            $response = $client->post(
                rtrim($baseUrl, '/') . '/api/tickets/close-from-shop',
                [
                    'json' => $payload,
                    'headers' => [
                        'X-Api-Token' => $token,
                        'Accept' => 'application/json',
                    ],
                ]
            );

            return json_decode($response->getBody()->getContents(), true);
        } catch (\Throwable $e) {
            Yii::error($e->getMessage(), 'merchant_ticket_closed');
        }
    }

    private function sendDataToWarehouse(User $user, User $merchant, MerchantQuestion $ticket, MerchantQuestionMessage $mqm, $productId = null)
    {
        $baseUrl = Yii::$app->params['warehouseApiUrl'] ?? null;
        $secretKey = Yii::$app->params['apiSecretKey'] ?? null;

        $payload = [
            'id' => $merchant->shop_id,
            'client_id' => $user->id,
            'client_name' => trim($user->name . ' ' . $user->lastname),
            'client_phone' => $user->phone,
            'merchant_id' => $merchant->id,
            'ticket_id' => $ticket->id,
            'product_id' => $productId ?: null,
            'message' => $mqm->message,
            'files' => $mqm->files,
        ];

        $token = md5($merchant->shop_id . $secretKey);

        try {
            $client = new \GuzzleHttp\Client([
                'timeout' => 5,
            ]);

            $response = $client->post(
                rtrim($baseUrl, '/') . '/api/tickets/from-shop',
                [
                    'json' => $payload,
                    'headers' => [
                        'X-Api-Token' => $token,
                        'Accept' => 'application/json',
                    ],
                ]
            );

            return json_decode($response->getBody()->getContents(), true);
        } catch (\Throwable $e) {
            Yii::error($e->getMessage(), 'merchant_ticket');

            return [
                'success' => false,
                'message' => 'Failed to send ticket to warehouse',
            ];
        }
    }
}
