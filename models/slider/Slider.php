<?php

namespace app\models\slider;

use Yii;
use yii\web\UploadedFile;

use app\models\Images;

/**
 * This is the model class for table "slider".
 *
 * @property int $id
 * @property string|null $type
 * @property string|null $name_ru
 * @property string|null $name_uz
 * @property string|null $name_en
 * @property string|null $content_ru
 * @property string|null $content_uz
 * @property string|null $content_en
 * @property string|null $link
 * @property int $status
 * @property int $sort
 * @property string $date
 */
class Slider extends \yii\db\ActiveRecord
{
    public $imageFiles = [];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'slider';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['type'], 'required', 'message'=>'Заполните поле'],
            [['content_ru', 'content_uz', 'content_en'], 'string'],
            [['status', 'sort'], 'integer'],
            [['date'], 'safe'],
            [['type', 'name_ru', 'name_uz', 'name_en', 'link'], 'string', 'max' => 255],
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
            'type' => 'Type',
            'name_ru' => 'Name Ru',
            'name_uz' => 'Name Uz',
            'name_en' => 'Name En',
            'content_ru' => 'Content Ru',
            'content_uz' => 'Content Uz',
            'content_en' => 'Content En',
            'link' => 'Link',
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
                $image->uploadPhoto($this->id, 'slider');
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
            $path = Images::PHOTO_SLIDER_PATH.$this->image->object_id.'/'.$s.'/'.$this->image->photo;
            if (is_file($path)) {
                return '/'.$path;
            }
        }

        return Images::PHOTO_DEFAULT;
    }

    public function fields() {
        $headers = Yii::$app->request->headers;
        $language = $headers->has('Content-Language') ? $headers->get('Content-Language') : 'ru';

        return [
            'id',
            'type',
            'name' => function() use($language) { return $this->{'name_'.$language} ? $this->{'name_'.$language} : $this->name_ru;},
            'content' => function() use($language) { return $this->{'content_'.$language} ? $this->{'content_'.$language} : $this->content_ru;},
            'link',
            'photo',
            'date'
        ];
    }

    // relations
    public function getImage() {
        return $this->hasOne(Images::className(), ['object_id'=>'id'])->andOnCondition(['type'=>'slider']);
    }
}
