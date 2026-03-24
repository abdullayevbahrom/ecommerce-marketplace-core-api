<?php

namespace app\models\brand;

use app\components\RabbitMq\MessageFactory;
use app\components\RabbitMq\OutboxService;
use Yii;
use yii\web\UploadedFile;

use app\models\Category;
use app\models\Images;

/**
 * This is the model class for table "category_brand".
 *
 * @property int $id
 * @property int|null $category_id
 * @property string|null $name_ru
 * @property string|null $name_en
 * @property string|null $name_uz
 * @property string|null $description_ru
 * @property string|null $description_en
 * @property string|null $description_uz
 * @property int $status
 * @property int $sort
 * @property string $date
 *
 * @property Category $category
 */
class CategoryBrand extends \yii\db\ActiveRecord
{
    public bool $suppressSyncEvents = false;

    public $imageFiles = [];
    public $sub_category_id = [];

    const STATUS_ACTIVE = 1;
    const STATUS_INACTIVE = 2;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'category_brand';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['category_id', 'status', 'sort'], 'integer'],
            [['description_ru', 'description_en', 'description_uz', 'category_tree'], 'string'],
            [['date', 'sub_category_id'], 'safe'],
            [['name_ru', 'name_en', 'name_uz'], 'string', 'max' => 255],
            [['category_id'], 'exist', 'skipOnError' => true, 'targetClass' => Category::className(), 'targetAttribute' => ['category_id' => 'id']],
            // [['imageFiles'], 'file', 'skipOnEmpty' => true, 'extensions' => 'png, jpg, jpeg']
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
            'name_ru' => 'Name Ru',
            'name_en' => 'Name En',
            'name_uz' => 'Name Uz',
            'description_ru' => 'Description Ru',
            'description_en' => 'Description En',
            'description_uz' => 'Description Uz',
            'status' => 'Status',
            'sort' => 'Sort',
            'date' => 'Date',
        ];
    }

    public function saveObject()
    {
        $this->status = 1;

        $tree = [$this->category_id];

        if ($this->sub_category_id) {
            foreach ($this->sub_category_id as $category) {
                if ($category) {
                    $tree[] = $category;
                    $this->category_id = $category;
                }
            }
        }

        $this->category_tree = implode('/', $tree);

        if ($this->save()) {
            $image = new Images;
            if ($image->imageFiles = UploadedFile::getInstances($this, 'imageFiles')) {
                if ($this->image) {
                    $this->image->removeImageSize();
                }
                $image->uploadPhoto($this->id, 'brand');
            }

            return true;
        }

        return false;
    }

    public function removeObject()
    {
        if ($this->image && $this->image->delete()) {
            $this->image->removeImageSize();
        }

        return $this->delete();
    }

    public function getPhoto($s = 'original')
    {
        if ($this->image) {
            return $this->image->getPhoto('brand', $s);
        }

        return Images::PHOTO_DEFAULT;
    }

    public function fields()
    {
        $headers = Yii::$app->request->headers;
        $language = $headers->has('Content-Language') ? $headers->get('Content-Language') : 'ru';

        $controller = Yii::$app->controller->id;
        $action = Yii::$app->controller->action->id;

        $data = [
            'id',
            'name' => function () use ($language) {
                return $this->{'name_' . $language} ? $this->{'name_' . $language} : $this->name_ru;
            },
            'description' => function () use ($language) {
                return $this->{'description_' . $language} ? $this->{'description_' . $language} : $this->description_ru;
            },
            'photo' => function () {
                return $this->getPhoto();
            },
            'category'
        ];

        return $data;
    }

    public function generateFileName()
    {
        return time() + uniqid();
    }

    // relations
    public function getImage()
    {
        return $this->hasOne(Images::className(), ['object_id' => 'id'])->andOnCondition(['type' => 'brand']);
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

    public function getModerationComments()
    {
        return $this->hasMany(
            \app\models\moderator\ModerationComment::class,
            ['entity_id' => 'id']
        )->andWhere(['entity_type' => 'brand']);
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

        $eventType = $insert ? 'brand.created' : 'brand.updated';

        $message = MessageFactory::make(
            eventType: $eventType,
            source: 'market',
            entityType: 'brand',
            entityId: $this->id,
            branchId: null,
            payload: $this->toSyncPayload(),
        );

        (new OutboxService())->queue(
            exchange: 'market_to_sklad',
            routingKey: $eventType,
            eventType: $eventType,
            entityType: 'brand',
            entityId: $this->id,
            source: 'market',
            branchId: null,
            message: $message
        );
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

        $message = MessageFactory::make(
            eventType: 'brand.deleted',
            source: 'market',
            entityType: 'brand',
            entityId: $this->id,
            branchId: null,
            payload: $this->toSyncPayload(),
        );

        (new OutboxService())->queue(
            exchange: 'market_to_sklad',
            routingKey: 'brand.deleted',
            eventType: 'brand.deleted',
            entityType: 'brand',
            entityId: $this->id,
            source: 'market',
            branchId: null,
            message: $message
        );
    }

    protected function toSyncPayload(): array
    {
        return [
            'id' => $this->id,
            'yii_brand_id' => $this->id,
            'category_id' => $this->category ? ($this->category->yii_category_id ?? $this->category_id) : $this->category_id,
            'category_tree' => $this->category_tree,
            'name_ru' => $this->name_ru,
            'name_en' => $this->name_en,
            'name_uz' => $this->name_uz,
            'description_ru' => $this->description_ru,
            'description_en' => $this->description_en,
            'description_uz' => $this->description_uz,
            'status' => $this->status ?? self::STATUS_ACTIVE,
            'sort' => $this->sort ?? 0,
            'deleted_at' => $this->deleted_at ?? null,
        ];
    }
}
