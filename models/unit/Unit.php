<?php

namespace app\models\unit;

use Yii;
use yii\db\ActiveRecord;

/**
 * Class Unit
 *
 * @property int $id
 * @property string $name
 * @property string $code
 * @property string $type
 * @property int $status
 * @property int $created_at
 * @property int|null $updated_at
 */
class Unit extends ActiveRecord
{
    public const TYPE_WEIGHT = 'weight';
    public const TYPE_VOLUME = 'volume';
    public const TYPE_PIECE  = 'piece';

    public static function tableName()
    {
        return '{{%units}}';
    }

    public function rules()
    {
        return [
            [['name', 'code', 'type'], 'required'],
            [['name'], 'string', 'max' => 100],
            [['code'], 'string', 'max' => 20],
            [['type'], 'in', 'range' => [
                self::TYPE_WEIGHT,
                self::TYPE_VOLUME,
                self::TYPE_PIECE,
            ]],
            [['status', 'created_at', 'updated_at'], 'integer'],
        ];
    }

    public function behaviors()
    {
        return [
            'timestamp' => [
                'class' => \yii\behaviors\TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'updated_at',
                'value' => time(),
            ],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Название',
            'code' => 'Код',
            'type' => 'Тип',
            'status' => 'Статус',
            'created_at' => 'Создано',
            'updated_at' => 'Обновлено',
        ];
    }

    /**
     * Связь с продуктами
     */
    public function getProducts()
    {
        return $this->hasMany(\app\models\product\Product::class, ['unit_id' => 'id']);
    }
}
