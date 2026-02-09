<?php 

namespace app\services;

use app\models\merchant\MerchantQuestion;
use app\models\merchant\MerchantQuestionMessage;
use app\models\Notification;
use app\models\user\User;

class NotificationService
{
    public static function notifyMerchantNewQuestion(MerchantQuestion $q)
    {
        if (!$q->merchant_id) {
            return;
        }

        $notification = new Notification();
        $notification->saveObject(
            $q->merchant_id,
            $q->id,
            'merchant_question_created',
            'Новый вопрос от клиента'
        );
    }

    public static function notifyClientAnswered(MerchantQuestion $q, MerchantQuestionMessage $message)
    {
        $notification = new Notification();
        $notification->saveObject(
            $q->client_id,
            $q->id, // object_id = question_id
            'merchant_question_replied',
            'Мерчант ответил на ваш вопрос: ' . mb_substr($message->message, 0, 150)
        );
    }

    public static function notifyModeratorsNewQuestion(MerchantQuestion $q)
    {
        $mods = User::find()->where(['role' => [User::ROLE_MODERATOR, User::ROLE_ADMIN]])->all();

        foreach ($mods as $mod) {
            $notification = new Notification();
            $notification->saveObject(
                $mod->id,
                $q->id,
                'merchant_question_created',
                'Клиент задал вопрос мерчанту'
            );
        }
    }

    public static function notifyModeratorsAnswered(MerchantQuestion $q)
    {
        $mods = User::find()
            ->where(['role' => [User::ROLE_MODERATOR, User::ROLE_ADMIN]])
            ->all();

        foreach ($mods as $mod) {
            $notification = new Notification();
            $notification->saveObject(
                $mod->id,
                $q->id,
                'merchant_question_answered',
                'Мерчант ответил клиенту'
            );
        }
    }


    public static function notifyQuestionClosed(MerchantQuestion $question, User $actor): void 
    {
        // Клиенту если мерчант закрыл
        if ($actor->id !== $question->client_id) {
            $n = new Notification();
            $n->saveObject(
                $question->client_id,
                $question->id,
                'merchant_question_closed',
                'Ваш вопрос был закрыт'
            );
        }

        // Мерчанту если клиент закрыл
        if ($actor->id !== $question->merchant_id) {
            $n = new Notification();
            $n->saveObject(
                $question->merchant_id,
                $question->id,
                'merchant_question_closed',
                'Диалог с клиентом был закрыт'
            );
        }

        // Модераторам + админам
        $moderators = User::find()
            ->where(['role' => [
                User::ROLE_MODERATOR,
                User::ROLE_ADMIN,
                User::ROLE_ADMIN,
            ]])
            ->all();

        foreach ($moderators as $mod) {
            // чтобы не слать самому себе
            if ($mod->id === $actor->id) {
                continue;
            }

            $n = new Notification();
            $n->saveObject(
                $mod->id,
                $question->id,
                'merchant_question_closed',
                'Вопрос клиента был закрыт'
            );
        }
    }

}
