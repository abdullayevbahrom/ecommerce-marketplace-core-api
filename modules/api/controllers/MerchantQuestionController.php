<?php 

namespace app\modules\api\controllers;

use app\models\merchant\MerchantQuestion;
use app\models\merchant\MerchantQuestionMessage;
use app\models\Notification;
use app\models\user\User;
use app\services\NotificationService;
use Yii;
use yii\filters\auth\HttpBearerAuth;
use yii\rest\Controller;
use yii\web\HttpException;
use yii\web\Response;

class MerchantQuestionController extends Controller
{
    public function behaviors()
    {
        return [
            // $behaviors = parent::behaviors(),
            'authenticator' => [
                'class' => HttpBearerAuth::class,
            ],
            'contentNegotiator' => [
                'class' => \yii\filters\ContentNegotiator::class,
                'formats' => [
                    'application/json' => Response::FORMAT_JSON,
                ],
            ],
        ];
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
            'client' => function ($q) {
                $q->select(['id', 'phone', 'name']);
            },
            'merchant' => function ($q) {
                $q->select(['id', 'phone', 'name']);
            },
        ])->orderBy(['created_at' => SORT_DESC]);

        if (in_array($user->role, [User::ROLE_ADMIN, User::ROLE_ADMIN, User::ROLE_MODERATOR])) {
          
        }elseif ($user->role === User::ROLE_SHOP) {
            $query->andWhere(['merchant_id' => $user->id]);
        }
        elseif ($user->role === User::ROLE_USER) {
            $query->andWhere(['client_id' => $user->id]);
        }
        else {
            throw new HttpException(403, 'Access denied');
        }

        $questions = $query->all();

        return [
            'success' => true,
            'data' => $questions,
            //'data' => array_map([$this, 'serializeQuestion'], $questions),
        ];
    }

    protected function serializeQuestion(MerchantQuestion $q): array
    {
        return [
            'id' => $q->id,
            'status' => $q->status,
            'created_at' => $q->created_at,
            'answered_at' => $q->answered_at,
            'closed_at' => $q->closed_at,

            'client' => $q->client ? [
                'id' => $q->client->id,
                'name' => $q->client->name,
                'phone' => $q->client->phone,
            ] : null,

            'merchant' => $q->merchant ? [
                'id' => $q->merchant->id,
                'name' => $q->merchant->name,
                'phone' => $q->merchant->phone,
            ] : null,

            'messages' => array_map(function ($m) {
                return [
                    'id' => $m->id,
                    'sender_id' => $m->sender_id,
                    'sender_role' => $m->sender_role,
                    'message' => $m->message,
                    'created_at' => $m->created_at,
                ];
            }, $q->messages),
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
        $message    = trim(Yii::$app->request->post('message'));
    
        if (!$merchantId || !$message) {
            return [
                'success' => false,
                'message' => 'merchant_id and message are required'
            ];
        }

        $merchant = User::find()->where(['id'   => $merchantId, 'role' => User::ROLE_SHOP])->one();

        if (!$merchant) {
            return [
                'success' => false,
                'message' => 'Merchant not found'
            ];
        }

        $exists = MerchantQuestion::find()
            ->where([
                'client_id' => $user->id,
                'merchant_id' => $merchant->id,
                'status' => MerchantQuestion::STATUS_OPEN
            ])
            ->exists();

        if ($exists) {
            return [
                'success' => false,
                'message' => 'Wait for merchant reply before sending new question'
            ];
        }

        $transaction = Yii::$app->db->beginTransaction();

        try {

            $model = new MerchantQuestion();
            $model->client_id   = $user->id;
            $model->merchant_id = $merchant->id;//Yii::$app->request->post('merchant_id');
            $model->status      = MerchantQuestion::STATUS_OPEN;
            $model->created_at  = time();

            if (!$model->save()) {
                return ['success' => false, 'errors' => $model->errors];
            }

            $msg = new MerchantQuestionMessage();
            $msg->question_id = $model->id;
            $msg->sender_role = MerchantQuestionMessage::ROLE_CLIENT;
            $msg->sender_id   = $user->id;
            $msg->message     = $message;//Yii::$app->request->post('message');
            $msg->created_at  = time();
            $msg->save(false);

            // NotificationService::notifyMerchantNewQuestion($model);
            // NotificationService::notifyModeratorsNewQuestion($model);

            $this->sendDataToWarehouse($user, $merchant, $model, $message);

            $transaction->commit();


            return ['success' => true, 'question_id' => $model->id];

        } catch (\Throwable $e) {
            $transaction->rollBack();
            Yii::error($e->getMessage(), 'create_question_for_merchant');

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }


    // POST /merchant/questions/{id}/close
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
        $baseUrl   = Yii::$app->params['warehouseApiUrl'];
        $secretKey = Yii::$app->params['apiSecretKey'];

        $payload = [
            'id' => $user->id,
            'ticket_id' => $question->id,
            'closed_by'     => 'client',
            'closed_at'     => time(),
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


    private function sendDataToWarehouse(User $user, $merchant, MerchantQuestion $ticket, $message)
    {
        $baseUrl   = Yii::$app->params['warehouseApiUrl'] ?? null;
        $secretKey = Yii::$app->params['apiSecretKey'] ?? null;
        
        $payload = [
            'id'    => $merchant->shop_id,
            'client_id' => $user->id,
            'client_name'  => trim($user->name . ' ' . $user->lastname),
            'client_phone' => $user->phone,
            'merchant_id'  => $merchant->id,
            'ticket_id' => $ticket->id,
            'message'      => $message,
        ];

        $token = md5($merchant->shop_id . $secretKey);

        try {
            $client = new \GuzzleHttp\Client([
                'timeout' => 5,
            ]);

            $response = $client->post(rtrim($baseUrl, '/') . '/api/tickets/from-shop',
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