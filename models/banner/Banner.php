<?php

namespace app\models\banner;

use Yii;
use yii\web\UploadedFile;

use app\models\Images;

use Jenssegers\ImageHash\ImageHash;
use Jenssegers\ImageHash\Implementations\DifferenceHash;

/**
 * This is the model class for table "banner".
 *
 * @property int $id
 * @property string|null $type
 * @property string|null $description_ru
 * @property string|null $description_en
 * @property string|null $description_uz
 * @property int $sort
 * @property int $status
 * @property string $date
 */
class Banner extends \yii\db\ActiveRecord
{
    public $imageFiles = [];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'banner';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['description_ru', 'description_en', 'description_uz'], 'string'],
            [['sort', 'status'], 'integer'],
            [['date'], 'safe'],
            [['type'], 'string', 'max' => 255],
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
            'description_ru' => 'Description Ru',
            'description_en' => 'Description En',
            'description_uz' => 'Description Uz',
            'sort' => 'Sort',
            'status' => 'Status',
            'date' => 'Date',
        ];
    }

    public function saveObject() {
        $this->status = 1;
        $this->sort = 0;

        if ($this->save(false)) {
            $image = new Images;
            if ($image->imageFiles = UploadedFile::getInstances($this, 'imageFiles')) {
                if ($this->image) {
                    $this->image->removeImageSize();
                }
                $image->uploadPhoto($this->id, 'banner');
            }

            // $data = self::find()->with('image')->where(['id' => $this->id])->one();

            // $hasher = new ImageHash(new DifferenceHash());
            // $hash = $hasher->hash('http://cdn.example.com/'.$data->getPhoto());

            // echo $hash;
            // die;

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
            $path = Images::PHOTO_BANNER_PATH.$this->image->object_id.'/'.$s.'/'.$this->image->photo;
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
            'description' => function() use($language) { return $this->{'description_'.$language} ? $this->{'description_'.$language} : $this->description_ru;},
            'photo'
        ];

        return $data;
    }

    public function generateFileName() {
        return time()+uniqid();
    }

    // relations
    public function getImage() {
        return $this->hasOne(Images::className(), ['object_id'=>'id'])->andOnCondition(['type'=>'banner']);
    }
}
