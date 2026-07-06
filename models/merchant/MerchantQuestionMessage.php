<?php

namespace app\models\merchant;

use yii\db\ActiveRecord;
use app\models\user\User;
use yii\helpers\Json;
use Yii;

class MerchantQuestionMessage extends ActiveRecord
{
    public const ROLE_CLIENT = 'client';
    public const ROLE_MERCHANT = 'merchant';
    public const ROLE_MODERATOR = 'moderator';
    public const PHOTO_PATH = 'merchant_question_messages/';

    public static function tableName()
    {
        return '{{%merchant_question_messages}}';
    }

    public function rules()
    {
        return [
            [['question_id', 'sender_id', 'sender_role', 'message', 'created_at'], 'required'],
            [['question_id', 'sender_id', 'created_at'], 'integer'],
            [
                'sender_role',
                'in',
                'range' => [
                    self::ROLE_CLIENT,
                    self::ROLE_MERCHANT,
                    self::ROLE_MODERATOR
                ]
            ],
            ['message', 'string'],
            ['files', 'safe'],
        ];
    }

    public function getQuestion()
    {
        return $this->hasOne(MerchantQuestion::class, ['id' => 'question_id']);
    }

    public function getSender()
    {
        return $this->hasOne(User::class, ['id' => 'sender_id']);
    }

    public function afterFind()
    {
        parent::afterFind();
        if (\is_string($this->files) && !empty($this->files)) {
            try {
                $this->files = Json::decode($this->files);
            } catch (\Exception $e) {
                $this->files = [];
            }
        } else {
            $this->files = is_array($this->files) ? $this->files : [];
        }
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        if (\is_string($this->files) && !empty($this->files)) {
            try {
                $this->files = Json::decode($this->files);
            } catch (\Exception $e) {
                $this->files = [];
            }
        }
    }

    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        if (is_array($this->files)) {
            $this->files = !empty($this->files)
                ? Json::encode(array_values($this->files))
                : null;
        }

        return true;
    }
}
