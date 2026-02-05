<?php

namespace app\models\moderator;

use yii\db\ActiveRecord;
use app\models\user\User;

/**
 * @property int $id
 * @property string $entity_type
 * @property int $entity_id
 * @property string $action
 * @property string|null $comment
 * @property int $moderator_id
 * @property bool $is_sent_to_warehouse
 * @property string $created_at
 */
class ModerationComment extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%moderation_comments}}';
    }

    public function rules()
    {
        return [
            [['entity_type', 'entity_id', 'action', 'moderator_id'], 'required'],
            [['entity_id', 'moderator_id'], 'integer'],
            [['comment'], 'string'],
            [['is_sent_to_warehouse'], 'boolean'],
            [['entity_type', 'action'], 'string', 'max' => 50],
            [['created_at'], 'safe'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'entity_type' => 'Entity Type',
            'entity_id' => 'Entity ID',
            'action' => 'Action',
            'comment' => 'Comment',
            'moderator_id' => 'Moderator',
            'is_sent_to_warehouse' => 'Sent to Warehouse',
            'created_at' => 'Created At',
        ];
    }

    /**
     * Moderator (user)
     */
    public function getModerator()
    {
        return $this->hasOne(User::class, ['id' => 'moderator_id']);
    }
    
}
