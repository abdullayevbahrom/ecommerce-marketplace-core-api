<?php

namespace app\models\chat;

use Yii;
use app\models\user\User;
use app\models\product\Product;

/**
 * This is the model class for table "message_room".
 *
 * @property int $id
 * @property int|null $sender_id
 * @property int|null $getter_id
 * @property int $status
 * @property int $status_archive
 * @property int $status_important
 * @property string $date
 *
 * @property User $getter
 * @property Messages[] $messages
 * @property User $sender
 */
class MessageRoom extends \yii\db\ActiveRecord
{
    public $type_user;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'message_room';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['sender_id', 'getter_id', 'product_id', 'status', 'status_archive', 'status_important'], 'integer'],
            [['type', 'type_user'], 'string'],
            [['date'], 'safe'],
            [['getter_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['getter_id' => 'id']],
            [['sender_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['sender_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'sender_id' => 'Sender ID',
            'getter_id' => 'Getter ID',
            'status' => 'Status',
            'status_archive' => 'Status Archive',
            'status_important' => 'Status Important',
            'date' => 'Date',
        ];
    }

    public function getProductObject() {
        if ($this->product) {
            return [
                'id' => $this->product->id,
                'name' => $this->product->name_ru,
                'color' => $this->product->color ? $this->product->color->name_ru : null,
                'photo' => $this->product->getPhoto()
            ];
        }

        return null;
    }

    /**
     * Gets query for [[Getter]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getGetter()
    {
        return $this->hasOne(User::className(), ['id' => 'getter_id']);
    }

    /**
     * Gets query for [[Messages]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getMessages()
    {
        return $this->hasMany(Messages::className(), ['message_room_id' => 'id']);
    }

    /**
     * Gets query for [[Sender]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSender()
    {
        return $this->hasOne(User::className(), ['id' => 'sender_id']);
    }

    // product
    public function getProduct()
    {
        return $this->hasOne(Product::className(), ['id' => 'product_id']);
    }
}
