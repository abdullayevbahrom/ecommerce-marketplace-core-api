<?php

namespace app\models\product;

use app\components\RabbitMq\MessageFactory;
use app\components\RabbitMq\OutboxService;
use Yii;
use app\models\Category;

/**
 * This is the model class for table "product_type".
 *
 * @property int $id
 * @property int|null $category_id
 * @property string|null $name_ru
 * @property string|null $name_en
 * @property string|null $name_uz
 * @property string|null $type
 * @property string|null $description_ru
 * @property string|null $description_en
 * @property string|null $description_uz
 * @property int $status
 * @property int $sort
 * @property string $date
 *
 * @property Category $category
 * @property ProductTypeValue[] $productTypeValues
 * @property ProductProductType[] $productProductTypes
 */
class ProductType extends \yii\db\ActiveRecord
{
    public bool $suppressSyncEvents = false;

    const TYPE_INPUT = 'input';
    const TYPE_SELECT = 'select';
    const TYPE_CHECKBOX = 'checkbox';
    const TYPE_RANGE = 'range';

    const STATUS_ACTIVE = 1;
    const STATUS_INACTIVE = 2;
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'product_type';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name_ru', 'type'], 'required', 'message'=>'Заполните поле'],
            [['category_id', 'status', 'sort'], 'integer'],
            [['description_ru', 'description_en', 'description_uz'], 'string'],
            [['date'], 'safe'],
            [['name_ru', 'name_en', 'name_uz', 'type'], 'string', 'max' => 255],
            [['type'], 'in', 'range' => [self::TYPE_INPUT, self::TYPE_SELECT, self::TYPE_CHECKBOX, self::TYPE_RANGE]],
            [['category_id'], 'exist', 'skipOnError' => true, 'targetClass' => Category::className(), 'targetAttribute' => ['category_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'category_id' => 'Category ID',
            'name_ru' => 'Name (RU)',
            'name_en' => 'Name (EN)',
            'name_uz' => 'Name (UZ)',
            'type' => 'Type',
            'description_ru' => 'Description (RU)',
            'description_en' => 'Description (EN)',
            'description_uz' => 'Description (UZ)',
            'status' => 'Status',
            'sort' => 'Sort Order',
            'date' => 'Date',
        ];
    }

    /**
     * Get type options for dropdowns
     */
    public static function getTypeOptions()
    {
        return [
            self::TYPE_INPUT => 'Text Input',
            self::TYPE_SELECT => 'Dropdown Select',
            self::TYPE_CHECKBOX => 'Multiple Choice',
            self::TYPE_RANGE => 'Range (Min-Max)',
        ];
    }

    public function fields() {
        $headers = Yii::$app->request->headers;
        $language = $headers->has('Content-Language') ? $headers->get('Content-Language') : 'ru';

        return [
            'id',
            'name' => function() use($language) {
                return $this->{'name_'.$language} ? $this->{'name_'.$language} : $this->name_ru;
            },
            'type',
            'description' => function() use($language) {
                return $this->{'description_'.$language} ? $this->{'description_'.$language} : $this->description_ru;
            },
            'values' => function() {
                return $this->productTypeValues;
            },
            'category'
        ];
    }

    /**
     * Gets query for [[Category]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCategory()
    {
        return $this->hasOne(Category::className(), ['id' => 'category_id']);
    }

    /**
     * Gets query for [[ProductTypeValues]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getProductTypeValues()
    {
        return $this->hasMany(ProductTypeValue::className(), ['product_type_id' => 'id'])->orderBy('sort ASC');
    }

    /**
     * Gets query for [[ProductProductTypes]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getProductProductTypes()
    {
        return $this->hasMany(ProductProductType::className(), ['product_type_id' => 'id']);
    }

    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        if ($this->sort === null || $this->sort === '') {
            $this->sort = 0;
        }

        return true;
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        if ($this->suppressSyncEvents) {
            return;
        }

        if (!(bool) (Yii::$app->params['rabbitmq']['enable_reference_events'] ?? false)) {
            return;
        }

        $eventType = $insert ? 'product_type.created' : 'product_type.updated';

        $this->sendEvent($eventType);
    }

    public function afterDelete()
    {
        parent::afterDelete();

        if ($this->suppressSyncEvents) {
            return;
        }

        if (!(bool) (Yii::$app->params['rabbitmq']['enable_reference_events'] ?? false)) {
            return;
        }

        $this->sendEvent('product_type.deleted');
    }

    protected function toSyncPayload(): array
    {
        return [
            'id' => $this->id,
            'yii_product_type_id' => $this->id,
            'category_id' => $this->category_id,
            'name_ru' => $this->name_ru,
            'name_en' => $this->name_en,
            'name_uz' => $this->name_uz,
            'type' => $this->type,
            'description_ru' => $this->description_ru,
            'description_en' => $this->description_en,
            'description_uz' => $this->description_uz,
            'status' => $this->status ?? self::STATUS_ACTIVE,
            'sort' => $this->sort ?? 0,
            'values' => array_map(
                static fn ($value) => [
                    'sklad_product_type_value_id' => null,
                    'yii_product_type_value_id' => $value->id,
                    'value_ru' => $value->value_ru,
                    'value_en' => $value->value_en,
                    'value_uz' => $value->value_uz,
                    'display_value' => $value->display_value,
                    'description_ru' => $value->description_ru,
                    'description_en' => $value->description_en,
                    'description_uz' => $value->description_uz,
                    'status' => $value->status ?? 1,
                    'sort' => $value->sort ?? 0,
                ],
                $this->productTypeValues ? $this->productTypeValues : []
            ),
        ];
    }

    public function sendEvent(string $eventType): void
    {
        $message = MessageFactory::make(
            eventType: $eventType,
            source: 'market',
            entityType: 'product_type',
            entityId: $this->id,
            branchId: null,
            payload: $this->toSyncPayload(),
        );

        (new OutboxService())->queue(
            exchange: 'market_to_sklad',
            routingKey: $eventType,
            eventType: $eventType,
            entityType: 'product_type',
            entityId: $this->id,
            source: 'market',
            branchId: null,
            message: $message
        );
    }
} 
