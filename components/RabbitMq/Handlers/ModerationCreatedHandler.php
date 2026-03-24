<?php

namespace app\components\RabbitMq\Handlers;

use app\models\brand\CategoryBrand;
use app\models\Category;
use app\models\color\Color;
use app\models\filter\Filter;
use app\models\moderator\ModerationComment;
use app\models\product\Product;
use app\models\product\ProductType;
use Yii;

class ModerationCreatedHandler
{
    public function handle(array $message): void
    {
        $payload = $message['payload'] ?? [];
        $model = $this->resolveEntity(
            (string) ($payload['entity_type'] ?? ''),
            (int) ($payload['entity_id'] ?? $payload['id'] ?? 0)
        );

        if (!$model) {
            throw new \RuntimeException('Moderation entity not found');
        }

        $tx = Yii::$app->db->beginTransaction();

        try {
            $comment = new ModerationComment();
            $comment->suppressSyncEvents = true;
            $comment->entity_type = $payload['entity_type'];
            $comment->entity_id = (int) $model->id;
            $comment->action = $payload['action'] ?? 'reject';
            $comment->comment = $payload['comment'] ?? null;
            $comment->moderator_id = (int) ($payload['moderator_id'] ?? 0);
            $comment->is_sent_to_warehouse = 1;
            $comment->status_after = $payload['status_after'] ?? null;
            $comment->save(false);

            $this->applyStatus($model, (string) ($payload['status_after'] ?? 'pending'));

            $tx->commit();
        } catch (\Throwable $e) {
            $tx->rollBack();
            throw $e;
        }
    }

    protected function resolveEntity(string $type, int $id)
    {
        if ($id <= 0) {
            return null;
        }

        return match ($type) {
            'product' => Product::findOne($id),
            'category' => Category::findOne($id),
            'brand' => CategoryBrand::findOne($id),
            'color' => Color::findOne($id),
            'filter' => Filter::findOne($id),
            'product-type' => ProductType::findOne($id),
            default => null,
        };
    }

    protected function applyStatus($model, string $status): void
    {
        if ($model instanceof Product) {
            $model->status = $status === 'approved' ? 1 : 2;
            $model->save(false);
            return;
        }

        if ($model instanceof Category) {
            $model->status = $status === 'approved' ? 1 : 0;
            $model->save(false);
            return;
        }

        if ($model instanceof CategoryBrand || $model instanceof Color || $model instanceof Filter || $model instanceof ProductType) {
            $model->status = $status === 'approved' ? 1 : 2;
            $model->save(false);
        }
    }
}
