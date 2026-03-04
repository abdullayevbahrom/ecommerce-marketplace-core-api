<?php

namespace app\models\brand;

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
}
