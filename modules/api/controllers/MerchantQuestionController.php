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

        if (!$merchantId) {
            return [
                'success' => false,
                'message' => 'merchant_id is required'
            ];
        }
        if (!User::find()->where(['id' => $merchantId, 'role' => User::ROLE_SHOP])->exists()) {
            return [
                'success' => false,
                'message' => 'Merchant not found'
            ];
        }   

        $exists = MerchantQuestion::find()
            ->where([
                'client_id' => $user->id,
                'merchant_id' => $merchantId,
                'status' => MerchantQuestion::STATUS_OPEN
            ])
            ->exists();

        if ($exists) {
            return [
                'success' => false,
                'message' => 'Wait for merchant reply before sending new question'
            ];
        }

        $model = new MerchantQuestion();
        $model->client_id   = $user->id;
        $model->merchant_id = Yii::$app->request->post('merchant_id');
        $model->status      = MerchantQuestion::STATUS_OPEN;
        $model->created_at  = time();

        if (!$model->save()) {
            return ['success' => false, 'errors' => $model->errors];
        }

        $msg = new MerchantQuestionMessage();
        $msg->question_id = $model->id;
        $msg->sender_role = MerchantQuestionMessage::ROLE_CLIENT;
        $msg->sender_id   = $user->id;
        $msg->message     = Yii::$app->request->post('message');
        $msg->created_at  = time();
        $msg->save(false);

        NotificationService::notifyMerchantNewQuestion($model);
        NotificationService::notifyModeratorsNewQuestion($model);


        return ['success' => true, 'question_id' => $model->id];
    }


    // POST /merchant/questions/{id}/reply
    public function actionReply($id)
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

        if ((int)$question->merchant_id !== (int)$user->id) {
            throw new HttpException(403, 'Access denied');
        }

        if ($question->status !== MerchantQuestion::STATUS_OPEN) {
            throw new HttpException(422, 'Question already answered');
        }

        $messageText = trim(Yii::$app->request->post('message'));

        if (!$messageText) {
            throw new HttpException(422, 'Message is required');
        }

        $transaction = Yii::$app->db->beginTransaction();

        try {

            $message = new MerchantQuestionMessage();
            $message->question_id = $question->id;
            $message->sender_id   = $user->id;
            $message->sender_role = MerchantQuestionMessage::ROLE_MERCHANT;
            $message->message     = $messageText;
            $message->created_at = time();

            if (!$message->save()) {
                Yii::error($message->errors, 'merchant_question');
                throw new \Exception('Failed to save message: ' . json_encode($message->errors));
            }

            $question->status = MerchantQuestion::STATUS_ANSWERED;
            $question->answered_at = time();

            if (!$question->save(false)) {
                throw new \Exception('Failed to update question');
            }

            NotificationService::notifyClientAnswered($question, $message);
            NotificationService::notifyModeratorsAnswered($question);

            $transaction->commit();

            return [
                'success' => true,
                'message' => 'Reply sent successfully',
            ];
        } catch (\Throwable $e) {
            $transaction->rollBack();
            Yii::error($e->getMessage(), 'merchant_question');

            throw new HttpException(500, 'Internal server error');
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

        // доступ: клиент, мерчант, модератор, админ
        $isClient    = $user->id == $question->client_id;
        $isMerchant  = $user->id == $question->merchant_id;
        $isModerator = in_array($user->role, [
            User::ROLE_MODERATOR,
            User::ROLE_ADMIN,
            User::ROLE_ADMIN,
        ]);

        if (!$isClient && !$isMerchant && !$isModerator) {
            throw new HttpException(403, 'Access denied');
        }

        if ($question->status === MerchantQuestion::STATUS_CLOSED) {
            throw new HttpException(422, 'Question already closed');
        }

        if ($question->status === MerchantQuestion::STATUS_OPEN) {
            throw new HttpException(422, 'Cannot close unanswered question');
        }

        $question->status = MerchantQuestion::STATUS_CLOSED;
        $question->closed_at = time();

        if (!$question->save(false)) {
            throw new HttpException(500, 'Failed to close question');
        }

        NotificationService::notifyQuestionClosed($question, $user);

        return [
            'success' => true,
            'message' => 'Question closed successfully',
        ];
    }


}