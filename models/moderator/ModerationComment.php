<?php

namespace app\models\moderator;

use app\components\RabbitMq\MessageFactory;
use app\components\RabbitMq\OutboxService;
use app\models\product\Product;
use yii\db\ActiveRecord;
use app\models\user\User;
use Yii;

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
    public bool $suppressSyncEvents = false;
    public ?string $status_after = null;

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

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        if (!$insert || !$this->shouldPublishSyncEvent()) {
            return;
        }

        $message = MessageFactory::make(
            eventType: 'moderation.created',
            source: 'market',
            entityType: 'moderation',
            entityId: $this->id,
            branchId: null,
            payload: $this->toSyncPayload(),
        );

        (new OutboxService())->queue(
            exchange: 'market_to_sklad',
            routingKey: 'moderation.created',
            eventType: 'moderation.created',
            entityType: 'moderation',
            entityId: $this->id,
            source: 'market',
            branchId: null,
            message: $message
        );
    }

    protected function shouldPublishSyncEvent(): bool
    {
        if ($this->suppressSyncEvents) {
            return false;
        }

        return (bool) (Yii::$app->params['rabbitmq']['enable_moderation_events'] ?? false);
    }

    protected function toSyncPayload(): array
    {
        $targetEntityId = $this->resolveTargetEntityId();

        return [
            'id' => $targetEntityId,
            'entity_type' => $this->entity_type,
            'entity_id' => $targetEntityId,
            'action' => $this->resolveAction(),
            'status_after' => $this->status_after ?? $this->resolveStatusAfter(),
            'comment' => $this->comment,
            'moderator_id' => $this->moderator_id,
            'metadata' => [
                'source' => 'yii2',
                'shop_entity_id' => $this->entity_id,
            ],
        ];
    }

    protected function resolveTargetEntityId(): int
    {
        if ($this->entity_type !== 'product') {
            return (int) $this->entity_id;
        }

        $product = Product::findOne((int) $this->entity_id);
        if ($product && !empty($product->sklad_product_id)) {
            return (int) $product->sklad_product_id;
        }

        return (int) $this->entity_id;
    }

    protected function resolveStatusAfter(): string
    {
        return match ($this->action) {
            'approve' => 'approved',
            'block' => 'pending',
            'reject' => 'rejected',
            default => 'pending',
        };
    }

    protected function resolveAction(): string
    {
        return match ($this->action) {
            'block' => 'reject',
            default => $this->action,
        };
    }
}
