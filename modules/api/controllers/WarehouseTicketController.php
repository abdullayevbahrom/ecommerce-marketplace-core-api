<?php

namespace app\modules\api\controllers;

use app\models\merchant\MerchantQuestion;
use app\models\merchant\MerchantQuestionMessage;
use app\models\user\User;
use app\services\NotificationService;
use Yii;
use yii\rest\Controller;
use yii\web\HttpException;

class WarehouseTicketController extends Controller
{
    public function actionReplyFromWarehouse()
    {
        $secretKey = Yii::$app->params['apiSecretKey'];
        $data = Yii::$app->request->post();
    
        $expectedToken = md5($data['shop_id'] . $secretKey);
    
        if (Yii::$app->request->headers->get('X-Api-Token') !== $expectedToken) {
            throw new HttpException(401, 'Invalid token');
        }
    
        $question = MerchantQuestion::findOne($data['ticket_id']);
        $merchant = User::findOne($data['merchant_id']);
    
        if (!$question) {
            throw new HttpException(404, 'Question not found');
        }
    
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $message = new MerchantQuestionMessage();
            $message->question_id = $question->id;
            $message->sender_role = 'merchant';
            $message->sender_id   = $merchant->id;
            $message->message     = $data['message'];
            $message->created_at  = time();
            $message->files       = $data['files'] ?? [];
            $message->save(false);

            $question->status = MerchantQuestion::STATUS_ANSWERED;
            $question->answered_at = $data['answered_at'];
            $question->save(false);
            
            $transaction->commit();

            // уведомление клиенту
            NotificationService::notifyClientAnswered($question, $message);

            return ['success' => true];

        } catch (\Throwable $e) {
             $transaction->rollBack();
             Yii::error($e->getMessage(), 'merchant_question');

             throw new HttpException(500, 'Internal server error');
        }
        
    }

    public function actionCloseFromWarehouse()
    {
        $data = Yii::$app->request->post();

        $question = MerchantQuestion::findOne([
            'id' => $data['yii_ticket_id']
        ]);

        if (!$question) {
            return ['success' => false, 'message' => 'ticket not found'];
        }

        $question->status = MerchantQuestion::STATUS_CLOSED;
        $question->closed_at = $data['closed_at'] ?? time();
        $question->save(false);

        return ['success' => true, 'message' => 'ticket successfully closed'];
    }


}