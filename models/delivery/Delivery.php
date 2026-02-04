<?php

namespace app\models\delivery;

use Yii;
use yii\web\UploadedFile;

use app\models\Images;
use app\models\order\Order;

/**
 * This is the model class for table "delivery".
 *
 * @property int $id
 * @property string|null $name_ru
 * @property string|null $name_uz
 * @property string|null $name_en
 * @property string|null $description_ru
 * @property string|null $description_uz
 * @property string|null $description_en
 * @property float|null $price
 * @property int $status
 * @property int $sort
 * @property string $date
 *
 * @property Order[] $orders
 */
class Delivery extends \yii\db\ActiveRecord
{
    public $imageFiles = [];
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'delivery';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name_ru'], 'required', 'message'=>'Заполните поле'],
            [['description_ru', 'description_uz', 'description_en'], 'string'],
            [['price'], 'number'],
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
            'price' => 'Price',
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
                $image->uploadPhoto($this->id, 'delivery');
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
            $path = Images::PHOTO_DELIVERY_PATH.$this->image->object_id.'/'.$s.'/'.$this->image->photo;
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
            'name' => function() use($language) { return $this->{'name_'.$language} ? $this->{'name_'.$language} : $this->name_ru;},
            'description' => function() use($language) { return $this->{'description_'.$language} ? $this->{'description_'.$language} : $this->description_ru;},
            'photo',
            'price',
            'date'
        ];
    }

    /**
     * Gets query for [[Orders]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOrders()
    {
        return $this->hasMany(Order::className(), ['delivery_id' => 'id']);
    }

    public function getImage() {
        return $this->hasOne(Images::className(), ['object_id'=>'id'])->andOnCondition(['type'=>'delivery']);
    }
}
