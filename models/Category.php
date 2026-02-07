<?php

namespace app\models;

use Yii;
use yii\helpers\Html;
use yii\web\UploadedFile;
use yii\helpers\ArrayHelper;

use app\models\Images;
use app\models\user\UserDiscipline;
use app\models\page\Page;
use app\models\course\Course;
use app\models\brand\CategoryBrand;
use app\models\category\CategoryFilter;
use app\models\filter\Filter;
use app\models\product\ProductType;

/**
 * This is the model class for table "category".
 *
 * @property int $id
 * @property int $parent_id
 * @property string $type
 * @property string $name
 * @property int $sort
 * @property string $date
 */
class Category extends \yii\db\ActiveRecord
{
    public $imageFiles = [];
    public $filters = [];

    const STATUS_ACTIVE = 1;
    const STATUS_INACTIVE = 0;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'category';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name_ru'], 'required', 'message'=>'Заполните поле'],
            [['parent_id', 'sort', 'status', 'main', 'is_filter', 'popular'], 'integer'],
            [['date', 'option_ru', 'option_uz', 'option_en', 'filters'], 'safe'],
            [['type', 'name_mini', 'name_ru', 'name_uz', 'name_en', 'description_ru', 'description_uz', 'description_en'], 'string', 'max' => 255],
            [['imageFiles'], 'file', 'skipOnEmpty' => true, 'extensions' => 'png, jpg, jpeg, svg', 'maxSize' => 2048000],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'parent_id' => 'Parent ID',
            'type' => 'Type',
            'name' => 'Name',
            'sort' => 'Sort',
            'date' => 'Date',
        ];
    }

    public function saveCategory(){
        $model = $this;

        if (array_key_exists('id', Yii::$app->request->post()['Category'])) {
            $model = self::findOne(Yii::$app->request->post()['Category']['id']);
            $model->name_ru = Html::encode($this->name_ru);
            $model->name_uz = Html::encode($this->name_uz);
            $model->name_en = Html::encode($this->name_en);
            $model->description_ru = Html::encode($this->description_ru);
            $model->description_uz = Html::encode($this->description_uz);
            $model->description_en = Html::encode($this->description_en);
            $model->option_ru = Html::encode($this->option_ru);
            $model->option_uz = Html::encode($this->option_uz);
            $model->option_en = Html::encode($this->option_en);

            CategoryFilter::deleteAll(['category_id'=>$model->id]);

            if ($this->filters) {
                $keys = ['category_id', 'filter_id', 'value_ru'];
                $vals = [];
                foreach ($this->filters as $key => $val) {
                    if ($val && ($val != 0)) {
                        if (is_array($val)) {
                            foreach ($val as $k => $v) {
                                if ($v && ($v != 0)) {
                                    $vals[] = [
                                        'category_id' => $model->id,
                                        'filter_id' => $key,
                                        'value_ru' => $v,
                                    ];
                                }
                            }
                        } else {
                            $vals[] = [
                                'category_id' => $model->id,
                                'filter_id' => $key,
                                'value_ru' => $val,
                            ];
                        }
                    }   
                }

                Yii::$app->db->createCommand()->batchInsert('category_filter', $keys, $vals)->execute();
            }
        }

        if ($model->image) {
            $current_image = $model->image;
        }

        if ($model->save()) {
            $image = new Images;

            if ($image->imageFiles = UploadedFile::getInstances($this, 'imageFiles')) {
                if (!empty($current_image)) {
                    $current_image->removeImageSize();
                }
                $image->uploadPhoto($model->id, 'category');
            }
        }

        return true;
    }

    public function getPhoto($s = 'original') {
        if ($this->image && $this->image->photo) {
            $path = Images::PHOTO_CATEGORY_PATH.$this->image->object_id.'/'.$s.'/'.$this->image->photo;
            if (is_file($path)) {
                return '/'.$path;
            }
        }
        return Images::PHOTO_DEFAULT;
    }

    public function getCategories(array $elements, $parentId = 0, $up = false) {
        $branch = array();

        if ($up === false) {
            foreach ($elements as $element) {
                if ($element['parent_id'] == $parentId) {
                    $children = $this->getCategories($elements, $element['id']);
                    if ($children) {
                        $element['children'] = $children;
                    }
                    $branch[] = $element;
                }
            }
        } else {
            foreach ($elements as $element) {
                if ($element['id'] == $parentId) {
                    $parent = $this->getCategories($elements, $element['parent_id'], true);
                    if ($parent) {
                        $element['parent'] = $parent;
                    }
                    $branch[] = $element;
                    $this->ids[] = $element['id'];
                }
            }
        }

        return $branch;
    }

    public function urlLanguage() {
        return Yii::$app->urlManager->createUrl(['/main/change-language', 'id'=>$this->id]);
    }

    public function clearData($data) {
        return strip_tags(html_entity_decode(strip_tags($data)));
    }

    public function getFilters() {
        $data = [];

        if ($this->categoryFilters) {
            foreach ($this->categoryFilters as $key => $filter) {
                if ($filter->filter->type == 'checkbox') {
                    $items = CategoryFilter::find()->where(['category_id'=>$filter->category_id, 'filter_id'=>$filter->filter_id])->all();
                    if ($items) {
                        $data[$key] = [
                            'id' => $filter->filter->id,
                            'type' => $filter->filter->type,
                            'name' => $filter->filter->name_ru,
                        ];
                        foreach ($items as $k => $v) {
                            $data[$key]['items'][] = [
                                'value_id' => $v->id,
                                'filter_value_id' => $v->value_id,
                                'value' => $v->value_ru
                            ];
                        }
                    }
                } else {
                    $data[] = [
                        'id' => $filter->filter->id,
                        'value_id' => $filter->id,
                        'filter_value_id' => $filter->value_id,
                        'type' => $filter->filter->type,
                        'name' => $filter->filter->name_ru,
                        'value' => $filter->value_ru
                    ];
                }
            }
        }

        return $data;
    }

    public function fields() {
        $headers = Yii::$app->request->headers;
        $language = $headers->has('Content-Language') ? $headers->get('Content-Language') : 'ru';

        $action = Yii::$app->controller->action->id;

        $data = [
            'id',
            'name' => function() use($language) {return $this->{'name_'.$language} ? $this->{'name_'.$language} : $this->name_ru;},
            'description' => function() use($language) {return $this->{'description_'.$language} ? strip_tags(html_entity_decode(htmlspecialchars_decode($this->{'description_'.$language}))) : $this->description_ru;},
            'option' => function() use($language) {return $this->{'option_'.$language} ? strip_tags(html_entity_decode(htmlspecialchars_decode($this->{'option_'.$language}))) : $this->option_ru;},
            'photo',
            'filters' => function() {return $this->getFilters();},
            'is_filter' => function() {return $this->categoryFilters ? true : false;},
            'popular' => function(){return $this->popular == 1 ? 1 : 0;},
            'childs'
        ];

        $exception = ['detail', 'by-brand', 'by-category', 'index', 'search', 'favorites', 'set-rate', 'set-review', 'add', 'favorite-categories'];

        // if (!in_array($action, $exception)) {
        //     $childs = ['childs'];
        //     $data = array_merge($data, $childs);
        // }

        return $data; 
    }

    // relations
    public function getImage() {
        return $this->hasOne(Images::className(), ['object_id'=>'id'])->andOnCondition(['type'=>'category']);
    }

    public function getChilds() {
        return $this->hasMany(self::className(), ['parent_id' => 'id']);
    }

    public function getParent() {
        return $this->hasOne(self::className(), ['id' => 'parent_id']);
    }

    public function getFilter() {
        return $this->hasMany(Filter::className(), ['category_id' => 'id'])->andOncondition(['parent_id'=>0]);
    }

    public function getCategoryFilters()
    {
        return $this->hasMany(CategoryFilter::className(), ['category_id' => 'id'])
            ->select([
                'id' => 'MAX(id)', 
                'category_id', 
                'filter_id', 
                'value_id' => 'MAX(value_id)', 
                'value_ru' => 'MAX(value_ru)', 
                'value_en' => 'MAX(value_en)', 
                'value_uz' => 'MAX(value_uz)'
            ])
            ->groupBy(['category_id', 'filter_id']);
    }

    public function getBrands() {
        return $this->hasMany(CategoryBrand::className(), ['category_id' => 'id']);
    }

    public function getProductTypes() {
        return $this->hasMany(ProductType::className(), ['category_id' => 'id']);
    }

    public function getModerationComments()
    {
        return $this->hasMany(\app\models\moderator\ModerationComment::class,
            ['entity_id' => 'id']
        )->andWhere(['entity_type' => 'category']);
    }
}
