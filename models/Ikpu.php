<?php

namespace app\models;

use app\components\RabbitMq\MessageFactory;
use app\components\RabbitMq\OutboxService;
use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "ikpu".
 * IKPU - Единый классификатор продукции Узбекистана
 *
 * @property int $id
 * @property string $code Код ИКПУ
 * @property string $name_ru Название ИКПУ на русском
 * @property string|null $name_uz Название ИКПУ на узбекском
 * @property string|null $name_en Название ИКПУ на английском
 * @property string|null $parent_code Родительский код ИКПУ

 * @property int $status Статус (1=активный, 0=неактивный)
 * @property string $created_at
 * @property string $updated_at
 *
 * @property Ikpu $parent Родительский элемент ИКПУ
 * @property Ikpu[] $children Дочерние элементы ИКПУ
 * @property \app\models\product\Product[] $products Продукты с данным кодом ИКПУ
 */
class Ikpu extends ActiveRecord
{
    public bool $suppressSyncEvents = false;

    const STATUS_INACTIVE = 0;
    const STATUS_ACTIVE = 1;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'ikpu';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'updated_at',
                'value' => function() {
                    return date('Y-m-d H:i:s');
                },
            ],
        ];
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

        $this->sendEvent($insert ? 'ikpu.created' : 'ikpu.updated');
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

        $this->sendEvent('ikpu.deleted');
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['code', 'name_ru'], 'required'],
            [['status'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['code', 'parent_code'], 'string', 'max' => 17],
            [['name_ru', 'name_uz', 'name_en'], 'string', 'max' => 500],
            [['code'], 'unique'],
            [['code'], 'match', 'pattern' => '/^[0-9]{17}$/', 'message' => 'Код ИКПУ должен содержать ровно 17 цифр'],
            [['parent_code'], 'match', 'pattern' => '/^[0-9]{17}$/', 'message' => 'Родительский код должен содержать ровно 17 цифр', 'skipOnEmpty' => true],
            [['status'], 'in', 'range' => [self::STATUS_INACTIVE, self::STATUS_ACTIVE]],

            [['parent_code'], 'exist', 'skipOnError' => true, 'targetClass' => self::class, 'targetAttribute' => ['parent_code' => 'code']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'code' => 'Код ИКПУ',
            'name_ru' => 'Название (Русский)',
            'name_uz' => 'Название (Узбекский)',
            'name_en' => 'Название (Английский)',
            'parent_code' => 'Родительский код',

            'status' => 'Статус',
            'created_at' => 'Создано',
            'updated_at' => 'Обновлено',
        ];
    }

    /**
     * Gets query for [[Parent]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getParent()
    {
        return $this->hasOne(self::class, ['code' => 'parent_code']);
    }

    /**
     * Gets query for [[Children]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getChildren()
    {
        return $this->hasMany(self::class, ['parent_code' => 'code'])
            ->where(['status' => self::STATUS_ACTIVE])
            ->orderBy(['code' => SORT_ASC]);
    }

    /**
     * Gets query for [[Products]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getProducts()
    {
        return $this->hasMany(\app\models\product\Product::class, ['ikpu_code' => 'code']);
    }

    /**
     * Get localized name based on language
     *
     * @param string $language Language code (ru, uz, en)
     * @return string
     */
    public function getName($language = 'ru')
    {
        $attribute = 'name_' . $language;
        return $this->hasAttribute($attribute) && !empty($this->$attribute) 
            ? $this->$attribute 
            : $this->name_ru;
    }

    /**
     * Get full hierarchical path as string
     *
     * @param string $separator Path separator
     * @param string $language Language for names
     * @return string
     */
    public function getFullPath($separator = ' > ', $language = 'ru')
    {
        $path = [];
        $current = $this;
        
        while ($current) {
            array_unshift($path, $current->getName($language));
            $current = $current->parent;
        }
        
        return implode($separator, $path);
    }

    /**
     * Get all ancestors (parent hierarchy)
     *
     * @return Ikpu[]
     */
    public function getAncestors()
    {
        $ancestors = [];
        $current = $this->parent;
        
        while ($current) {
            $ancestors[] = $current;
            $current = $current->parent;
        }
        
        return array_reverse($ancestors);
    }

    /**
     * Check if this IKPU is a leaf node (has no children)
     *
     * @return bool
     */
    public function isLeaf()
    {
        return $this->getChildren()->count() === 0;
    }

    /**
     * Get status label
     *
     * @return string
     */
    public function getStatusLabel()
    {
        return $this->status === self::STATUS_ACTIVE ? 'Активный' : 'Неактивный';
    }

    /**
     * Get available status options
     *
     * @return array
     */
    public static function getStatusOptions()
    {
        return [
            self::STATUS_INACTIVE => 'Неактивный',
            self::STATUS_ACTIVE => 'Активный',
        ];
    }

    /**
     * Find active IKPU records
     *
     * @return \yii\db\ActiveQuery
     */
    public static function findActive()
    {
        return static::find()->where(['status' => self::STATUS_ACTIVE]);
    }

    /**
     * Find root level IKPU records (level 1)
     *
     * @return \yii\db\ActiveQuery
     */
    public static function findRoots()
    {
        return static::findActive()
            ->where(['parent_code' => null])
            ->orderBy(['code' => SORT_ASC]);
    }

    /**
     * Search IKPU by code or name
     *
     * @param string $query Search query
     * @param string $language Language for name search
     * @return \yii\db\ActiveQuery
     */
    public static function search($query, $language = 'ru')
    {
        $nameField = 'name_' . $language;
        
        return static::findActive()
            ->where(['or',
                ['like', 'code', $query],
                ['like', $nameField, $query]
            ])
            ->orderBy(['code' => SORT_ASC]);
    }

    /**
     * Get formatted display text for dropdowns
     *
     * @param string $language Language code
     * @return string
     */
    public function getDisplayText($language = 'ru')
    {
        return $this->code . ' - ' . $this->getName($language);
    }

    /**
     * Before save event - handle empty parent_code
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            // Convert empty parent_code to NULL
            if (empty($this->parent_code)) {
                $this->parent_code = null;
            }
            return true;
        }
        return false;
    }

    protected function toSyncPayload(): array
    {
        return [
            'id' => $this->id,
            'yii_ikpu_id' => $this->id,
            'code' => $this->code,
            'name_ru' => $this->name_ru,
            'name_uz' => $this->name_uz,
            'name_en' => $this->name_en,
            'parent_code' => $this->parent_code,
            'status' => $this->status ?? self::STATUS_ACTIVE,
        ];
    }

    protected function sendEvent(string $eventType): void
    {
        $message = MessageFactory::make(
            eventType: $eventType,
            source: 'market',
            entityType: 'ikpu',
            entityId: $this->id,
            branchId: null,
            payload: $this->toSyncPayload(),
        );

        (new OutboxService())->queue(
            exchange: 'market_to_sklad',
            routingKey: $eventType,
            eventType: $eventType,
            entityType: 'ikpu',
            entityId: $this->id,
            source: 'market',
            branchId: null,
            message: $message
        );
    }
}
