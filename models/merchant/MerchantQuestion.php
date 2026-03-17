<?php

namespace app\models\merchant;

use yii\db\ActiveRecord;
use app\models\user\User;
use Yii;

/**
 * @property int $id
 * @property int $client_id
 * @property int $merchant_id
 * @property string|null $entity_type
 * @property int|null $entity_id
 * @property int $status
 * @property int $created_at
 * @property int|null $answered_at
 * @property int|null $closed_at 
 */
class MerchantQuestion extends ActiveRecord
{
    const STATUS_OPEN     = 0;
    const STATUS_ANSWERED = 1;
    const STATUS_CLOSED   = 2;

    public static function tableName()
    {
        return '{{%merchant_questions}}';
    }

    public function rules()
    {
        return [
            [['client_id', 'merchant_id', 'created_at'], 'required'],
            [['client_id', 'merchant_id', /* 'entity_id', */ 'status', 'created_at', 'answered_at'], 'integer'],
            // [['entity_type'], 'string', 'max' => 50],
            ['status', 'default', 'value' => self::STATUS_OPEN],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'client_id' => 'Client',
            'merchant_id' => 'Merchant',
            // 'entity_type' => 'Entity Type',
            // 'entity_id' => 'Entity ID',
            'status' => 'Status',
            'created_at' => 'Created At',
            'answered_at' => 'Answered At',
        ];
    }

    public function fields()
    {
        return [
            'id',
            'status',
            'created_at' => function () {
                return Yii::$app->formatter->asDatetime($this->created_at, 'php:Y-m-d H:i:s');
            },
            'answered_at' => function () {
                return $this->answered_at
                    ? Yii::$app->formatter->asDatetime($this->answered_at, 'php:Y-m-d H:i:s')
                    : null;
            },
            'closed_at' => function () {
                return $this->closed_at
                    ? Yii::$app->formatter->asDatetime($this->closed_at, 'php:Y-m-d H:i:s')
                    : null;
            },
            'client',
            'merchant',
            'messages',
        ];
    }


    /* ================= RELATIONS ================= */

    public function getClient()
    {
        return $this->hasOne(User::class, ['id' => 'client_id']);
    }

    public function getMerchant()
    {
        return $this->hasOne(User::class, ['id' => 'merchant_id']);
    }

    public function getIsOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function getMessages()
    {
        return $this->hasMany(MerchantQuestionMessage::class, ['question_id' => 'id'])
            ->orderBy(['created_at' => SORT_ASC]);
    }

}
