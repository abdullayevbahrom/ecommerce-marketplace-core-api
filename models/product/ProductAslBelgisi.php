<?php

namespace app\models\product;

use Yii;
use yii\db\ActiveRecord;

/**
 * ProductAslBelgisi — ASL Belgisi registry entries.
 * One GTIN entry → many products (via product.barcode = product_asl_belgisi.gtin).
 *
 * @property int $id
 * @property string $gtin
 * @property string|null $asl_product_id
 * @property string|null $product_name_ru
 * @property string|null $product_name_uz
 * @property string|null $inn
 * @property string|null $product_group
 * @property string|null $status
 * @property string $checked_at
 * @property string $created_at
 *
 * @property Product[] $products
 */
class ProductAslBelgisi extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%product_asl_belgisi}}';
    }

    public function rules()
    {
        return [
            [['gtin', 'checked_at'], 'required'],
            [['checked_at', 'created_at'], 'safe'],
            [['gtin'], 'string', 'max' => 14],
            [['asl_product_id'], 'string', 'max' => 64],
            [['product_name_ru', 'product_name_uz'], 'string', 'max' => 500],
            [['inn'], 'string', 'max' => 20],
            [['product_group'], 'string', 'max' => 100],
            [['status'], 'string', 'max' => 20],
        ];
    }

    /**
     * All products whose barcode matches this GTIN.
     */
    public function getProducts()
    {
        return $this->hasMany(Product::class, ['barcode' => 'gtin']);
    }

    /**
     * Find ASL Belgisi entry by GTIN.
     */
    public static function findByGtin($gtin)
    {
        return static::find()->where(['gtin' => $gtin])->orderBy(['checked_at' => SORT_DESC])->one();
    }

    /**
     * Find ASL Belgisi entry for a product (by its barcode).
     */
    public static function findByProductBarcode($barcode)
    {
        if (empty($barcode)) {
            return null;
        }
        return static::find()->where(['gtin' => $barcode])->orderBy(['checked_at' => SORT_DESC])->one();
    }

    /**
     * Create or update ASL Belgisi entry by GTIN.
     * If entry with same GTIN exists — update it. Otherwise create new.
     *
     * @param string $gtin
     * @param array $parsedData Output of AslBelgisiService::parseProduct()
     * @return static|null
     */
    public static function upsertByGtin($gtin, $parsedData)
    {
        $model = static::findByGtin($gtin);

        if (!$model) {
            $model = new static();
            $model->gtin = $gtin;
        }

        $model->asl_product_id = $parsedData['asl_product_id'] ?? null;
        $model->product_name_ru = $parsedData['product_name_ru'] ?? null;
        $model->product_name_uz = $parsedData['product_name_uz'] ?? null;
        $model->inn = $parsedData['inn'] ?? null;
        $model->product_group = $parsedData['product_group'] ?? null;
        $model->status = $parsedData['status'] ?? null;
        $model->checked_at = date('Y-m-d H:i:s');

        if ($model->save()) {
            return $model;
        }

        Yii::error('Failed to save ProductAslBelgisi: ' . json_encode($model->errors), __METHOD__);
        return null;
    }
}
