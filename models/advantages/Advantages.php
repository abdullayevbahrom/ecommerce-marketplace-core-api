<?php

namespace app\models\advantages;

use Yii;
use yii\web\UploadedFile;

use app\models\Images;

/**
 * This is the model class for table "advantages".
 *
 * @property int $id
 * @property string|null $name_ru
 * @property string|null $name_uz
 * @property string|null $name_en
 * @property string|null $description_ru
 * @property string|null $description_uz
 * @property string|null $description_en
 * @property int|null $status
 * @property int|null $sort
 * @property string $date
 */
class Advantages extends \yii\db\ActiveRecord
{
    public $imageFiles = [];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'advantages';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name_ru'], 'required', 'message'=>'Заполните поле'],
            [['description_ru', 'description_uz', 'description_en'], 'string'],
            [['status', 'sort'], 'integer'],
            [['date'], 'safe'],
            [['name_ru', 'name_uz', 'name_en'], 'string', 'max' => 255],
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
            'name_ru' => 'Name Ru',
            'name_uz' => 'Name Uz',
            'name_en' => 'Name En',
            'description_ru' => 'Description Ru',
            'description_uz' => 'Description Uz',
            'description_en' => 'Description En',
            'status' => 'Status',
            'sort' => 'Sort',
            'date' => 'Date',
        ];
    }

    public function saveObject() {
        $this->status = 1;

        if ($this->save()) {
            $image = new Images;
            if ($image->imageFiles = UploadedFile::getInstances($this, 'imageFiles')) {
                if ($this->image) {
                    $this->image->removeImageSize();
                }
                $image->uploadPhoto($this->id, 'advantage');
            }

            return true;
        }

        return false;
    }

    public function removeObject(){
        if ($this->image && $this->image->delete()){
            $this->image->removeImageSize();
        }
        
        return $this->delete();
    }

    public function getPhoto($s = 'original') {
        if ($this->image) {
            $path = Images::PHOTO_ADVANTAGE_PATH.$this->image->object_id.'/'.$s.'/'.$this->image->photo;
            if (is_file($path)) {
                return '/'.$path;
            }
        }

        return Images::PHOTO_DEFAULT;
    }

    public function fields() {
        $headers = Yii::$app->request->headers;
        $language = $headers->has('Content-Language') ? $headers->get('Content-Language') : 'ru';

        $controller = Yii::$app->controller->id;
        $action = Yii::$app->controller->action->id;

        $data = [
            'id',
            'name' => function() use($language) { return $this->{'name_'.$language} ? $this->{'name_'.$language} : $this->name_ru;},
            'description' => function() use($language) { return $this->{'description_'.$language} ? $this->{'description_'.$language} : $this->description_ru;},
            'photo',
            'status',
            'date'
        ];

        return $data;
    }

    public function generateFileName() {
        return time()+uniqid();
    }

    // relations
    public function getImage() {
        return $this->hasOne(Images::className(), ['object_id'=>'id'])->andOnCondition(['type'=>'advantage']);
    }
}
