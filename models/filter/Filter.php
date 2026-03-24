<?php

namespace app\models\filter;

use Yii;
use app\models\Category;
use app\models\category\CategoryFilter;
use app\models\user\User;
use app\models\filter\FilterUser;

/**
 * This is the model class for table "filter".
 *
 * @property int $id
 * @property int $parent_id
 * @property int $category_id
 * @property string $type
 * @property string $name
 * @property string $date
 *
 * @property Category $category
 */
class Filter extends \yii\db\ActiveRecord
{
    public bool $suppressSyncEvents = false;

    public $property_key_ru = [];
    public $property_value_ru = [];

    public $property_key_uz = [];
    public $property_value_uz = [];

    public $property_key_en = [];
    public $property_value_en = [];

    public $sub_category_id;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'filter';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['category_id', 'name_ru'], 'required', 'message'=>'Заполните поле'],
            [['parent_id', 'category_id', 'status', 'is_filter'], 'integer'],
            [['date', 'property_key_ru', 'property_value_ru', 'property_key_uz', 'property_value_uz', 'property_key_en', 'property_value_en', 'sub_category_id'], 'safe'],
            [['type', 'name_ru', 'name_uz', 'name_en', 'value_ru', 'value_uz', 'value_en', 'category_tree'], 'string', 'max' => 255],
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
            'parent_id' => 'Parent ID',
            'category_id' => 'Category ID',
            'type' => 'Type',
            'name' => 'Name',
            'date' => 'Date',
        ];
    }

    public function saveObject() {
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
        
        $this->status = 1;

        // Get the old type before saving to detect changes
        $oldType = null;
        if (!$this->isNewRecord) {
            $oldType = $this->getOldAttribute('type');
        }

        if ($this->save()) {
            // Only delete child filters if type changed or if we're updating child options
            $shouldDeleteChildren = false;
            $deleteReason = '';
            
            if ($this->isNewRecord) {
                // For new records, always delete any existing children (shouldn't exist, but just in case)
                $shouldDeleteChildren = true;
                $deleteReason = 'new record';
            } elseif ($oldType && $oldType !== $this->type) {
                // Type changed - delete children 
                $shouldDeleteChildren = true;
                $deleteReason = "type changed from '{$oldType}' to '{$this->type}'";
            } elseif ($this->type == 'select' || $this->type == 'checkbox') {
                // Type didn't change, but we're updating select/checkbox options
                $shouldDeleteChildren = true;
                $deleteReason = 'updating options for select/checkbox filter';
            }
            
            if ($shouldDeleteChildren) {
                \Yii::info("Deleting child filters for filter ID {$this->id}: {$deleteReason}", 'application');
                Filter::deleteAll(['parent_id'=>$this->id]);
            } else {
                \Yii::info("Preserving child filters for filter ID {$this->id}: type='{$this->type}', oldType='{$oldType}'", 'application');
            }
            
            // Only create child filters for select and checkbox types
            if (($this->type == 'select' || $this->type == 'checkbox') && 
                !empty($this->property_value_ru) && !empty($this->property_value_ru[0]) && 
                !empty($this->property_key_ru) && !empty($this->property_key_ru[0])) {
                
                $keys = ['parent_id', 'category_id', 'name_ru', 'name_uz', 'name_en', 'value_ru', 'value_uz', 'value_en'];
                $vals = [];
                foreach ($this->property_value_ru as $k => $v) {
                    if ($v) {
                        $vals[] = [
                            'parent_id' => $this->id,
                            'category_id' => $this->category_id,
                            'name_ru' => $this->property_key_ru[$k],
                            'name_uz' => $this->property_key_uz[$k],
                            'name_en' => $this->property_key_en[$k],
                            'value_ru' => $this->property_value_ru[$k],
                            'value_uz' => $this->property_value_uz[$k],
                            'value_en' => $this->property_value_en[$k]
                        ];
                    }
                }

                if (!empty($vals)) {
                    Yii::$app->db->createCommand()->batchInsert('filter', $keys, $vals)->execute();
                }
            }
            return true;
        }

        return false;
    }

    public function fields() {
        $headers = Yii::$app->request->headers;
        $language = $headers->has('Content-Language') ? $headers->get('Content-Language') : 'ru';

        $data = [
            'id',
            'type',
            'name' => function() use($language) {return $this->{'name_'.$language} ? $this->{'name_'.$language} : $this->name_ru;},
            'value' => function() use($language) {return $this->{'value_'.$language} ? $this->{'value_'.$language} : $this->value_ru;},
            'categoryFilter',
            'is_filter' => function() {
                $identity = Yii::$app->user->identity;
                if ($identity && $identity instanceof User && $identity->role == User::ROLE_SHOP) {
                    $user_filter = FilterUser::findOne(['user_id'=>$identity->getId(), 'filter_id'=>$this->id]);
                    if ($user_filter) {
                        return ($user_filter->enabled == 1) ? true : false;
                    } else {
                        return ($this->is_filter == 1) ? true : false;
                    }
                } else {
                    return ($this->is_filter == 1) ? true : false;
                }
            }
        ];

        if ($this->childs) {
            $data[] = 'childs';
        }

        return $data;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCategory()
    {
        return $this->hasOne(Category::className(), ['id' => 'category_id']);
    }

    public function getChilds() {
        return $this->hasMany(self::className(), ['parent_id' => 'id']);
    }

    public function getParent() {
        return $this->hasOne(self::className(), ['id' => 'parent_id']);
    }

    public function getCategoryFilter()
    {
        return $this->hasOne(CategoryFilter::className(), ['filter_id'=>'id']);
    }

    public function getModerationComments()
    {
        return $this->hasMany(\app\models\moderator\ModerationComment::class,
            ['entity_id' => 'id']
        )->andWhere(['entity_type' => 'filter']);
    }
}
