<?php

namespace app\models\product;

use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "product_moderation_comments".
 * Stores audit trail for all moderation actions on pending products.
 *
 * @property int $id
 * @property int $submission_id FK to pending_products.id
 * @property int|null $moderator_id FK to user table
 * @property string $action submit/approve/reject/request_changes
 * @property string|null $comment Comment visible to merchant
 * @property string|null $internal_notes Private notes for moderators
 * @property string|null $status_before
 * @property string|null $status_after
 * @property string|null $metadata JSON extra data
 * @property string $created_at
 *
 * @property PendingProduct $submission
 * @property \app\models\user\User $moderator
 */
class ProductModerationComment extends ActiveRecord
{
    const ACTION_SUBMIT = 'submit';
    const ACTION_APPROVE = 'approve';
    const ACTION_REJECT = 'reject';
    const ACTION_REQUEST_CHANGES = 'request_changes';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'product_moderation_comments';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['submission_id', 'action'], 'required'],
            [['submission_id', 'moderator_id'], 'integer'],
            [['comment', 'internal_notes', 'metadata', 'created_at'], 'safe'],
            [['action', 'status_before', 'status_after'], 'string', 'max' => 50],
            ['action', 'in', 'range' => [self::ACTION_SUBMIT, self::ACTION_APPROVE, self::ACTION_REJECT, self::ACTION_REQUEST_CHANGES]],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'submission_id' => 'Submission',
            'moderator_id' => 'Moderator',
            'action' => 'Action',
            'comment' => 'Comment',
            'internal_notes' => 'Internal Notes',
            'status_before' => 'Status Before',
            'status_after' => 'Status After',
            'metadata' => 'Metadata',
            'created_at' => 'Created At',
        ];
    }

    /**
     * Gets query for [[Submission]].
     * @return \yii\db\ActiveQuery
     */
    public function getSubmission()
    {
        return $this->hasOne(PendingProduct::class, ['id' => 'submission_id']);
    }

    /**
     * Gets query for [[Moderator]].
     * @return \yii\db\ActiveQuery
     */
    public function getModerator()
    {
        return $this->hasOne(\app\models\user\User::class, ['id' => 'moderator_id']);
    }

    /**
     * Action label for display
     * @return string
     */
    public function getActionLabel()
    {
        $labels = [
            self::ACTION_SUBMIT => 'Отправлено',
            self::ACTION_APPROVE => 'Одобрено',
            self::ACTION_REJECT => 'Отклонено',
            self::ACTION_REQUEST_CHANGES => 'Запрошены изменения',
        ];
        return $labels[$this->action] ?? $this->action;
    }

    /**
     * Get decoded metadata
     * @return array
     */
    public function getDecodedMetadata()
    {
        return json_decode($this->metadata, true) ?: [];
    }
}
