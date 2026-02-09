<?php

namespace app\models\merchant;

use yii\db\ActiveRecord;
use app\models\user\User;
use yii\behaviors\TimestampBehavior;

class MerchantQuestionMessage extends ActiveRecord
{
    const ROLE_CLIENT    = 'client';
    const ROLE_MERCHANT  = 'merchant';
    const ROLE_MODERATOR = 'moderator';

    public static function tableName()
    {
        return '{{%merchant_question_messages}}';
    }

    public function rules()
    {
        return [
            [['question_id', 'sender_id', 'sender_role', 'message', 'created_at'],'required'],
            [['question_id', 'sender_id', 'created_at'], 'integer'],
            ['sender_role', 'in', 'range' => [
                self::ROLE_CLIENT,
                self::ROLE_MERCHANT,
                self::ROLE_MODERATOR
            ]],
            ['message', 'string'],
        ];
    }

    /* ================= RELATIONS ================= */

    public function getQuestion()
    {
        return $this->hasOne(MerchantQuestion::class, ['id' => 'question_id']);
    }

    public function getSender()
    {
        return $this->hasOne(User::class, ['id' => 'sender_id']);
    }

    // public function behaviors()
    // {
    //     return [
    //         [
    //             'class' => TimestampBehavior::class,
    //             'createdAtAttribute' => 'created_at',
    //             'updatedAtAttribute' => false,
    //             'value' => function () {
    //                 return time();
    //             },
    //         ],
    //     ];
    // }
}
