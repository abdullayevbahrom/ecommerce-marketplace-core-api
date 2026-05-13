<?php

namespace app\components\RabbitMq\Handlers;

use app\models\product\Product;
use app\models\product\ProductAslBelgisi;
use Yii;

class AslBelgisiUpdatedHandler
{
    public function handle(array $message): void
    {
        $payload = $message['payload'] ?? [];
        $product = $this->resolveProduct($payload);

        if (!$product) {
            Yii::error([
                'message' => 'ASL Belgisi sync failed: product not found',
                'event_type' => $message['event_type'] ?? null,
                'event_id' => $message['event_id'] ?? null,
                'yii_product_id' => $payload['yii_product_id'] ?? null,
                'sklad_product_id' => $payload['sklad_product_id'] ?? null,
            ]);
            throw new \RuntimeException('ASL Belgisi sync failed: product not found');
        }

        $gtin = (string) ($product->barcode ?? '');
        if ($gtin === '') {
            Yii::error([
                'message' => 'ASL Belgisi sync failed: product has empty barcode',
                'event_type' => $message['event_type'] ?? null,
                'event_id' => $message['event_id'] ?? null,
                'product_id' => $product->id,
            ]);
            throw new \RuntimeException('ASL Belgisi sync failed: product has empty barcode');
        }

        $parsedData = $this->normalizeParsedData($payload, $gtin);
        $entry = ProductAslBelgisi::findByGtin($gtin);
        if (!$entry) {
            $entry = new ProductAslBelgisi();
            $entry->gtin = $gtin;
        }

        $entry->suppressSyncEvents = true;
        $entry->asl_product_id = $parsedData['asl_product_id'] ?? null;
        $entry->product_name_ru = $parsedData['product_name_ru'] ?? null;
        $entry->product_name_uz = $parsedData['product_name_uz'] ?? null;
        $entry->inn = $parsedData['inn'] ?? null;
        $entry->product_group = $parsedData['product_group'] ?? null;
        $entry->status = $parsedData['status'] ?? null;
        $entry->checked_at = $parsedData['checked_at'] ?? date('Y-m-d H:i:s');

        if (!$entry->save()) {
            throw new \RuntimeException('Failed to save ASL Belgisi sync entry: ' . json_encode($entry->errors));
        }
    }

    protected function resolveProduct(array $payload): ?Product
    {
        if (!empty($payload['yii_product_id'])) {
            $product = Product::findOne((int) $payload['yii_product_id']);
            if ($product) {
                return $product;
            }
        }

        return null;
    }

    protected function normalizeParsedData(array $payload, string $gtin): array
    {
        $rawData = $payload['asl_belgisi_data'] ?? null;
        $data = is_array($rawData) ? $rawData : [];
        $checkedAt = $payload['asl_belgisi_checked_at'] ?? null;

        return [
            'asl_product_id' => $data['asl_product_id'] ?? $data['id'] ?? $data['product_id'] ?? null,
            'product_name_ru' => $data['product_name_ru'] ?? $data['name_ru'] ?? null,
            'product_name_uz' => $data['product_name_uz'] ?? $data['name_uz'] ?? null,
            'inn' => $data['inn'] ?? null,
            'product_group' => $data['product_group'] ?? $data['group'] ?? null,
            'status' => $payload['asl_belgisi_status'] ?? $data['status'] ?? null,
            'gtin' => $data['gtin'] ?? $gtin,
            'checked_at' => $this->normalizeDateTime($checkedAt),
        ];
    }

    protected function normalizeDateTime(?string $value): string
    {
        if (empty($value)) {
            return date('Y-m-d H:i:s');
        }

        $timestamp = strtotime($value);
        if ($timestamp === false) {
            return date('Y-m-d H:i:s');
        }

        return date('Y-m-d H:i:s', $timestamp);
    }
}
