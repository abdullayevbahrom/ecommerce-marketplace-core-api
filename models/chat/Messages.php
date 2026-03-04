<?php

namespace app\models\chat;

use Yii;
use app\models\user\User;
use app\models\Images;

/**
 * This is the model class for table "messages".
 *
 * @property int $id
 * @property int|null $message_room_id
 * @property int|null $user_id
 * @property string|null $message
 * @property int $status
 * @property string $date
 *
 * @property MessageRoom $messageRoom
 * @property User $user
 */
class Messages extends \yii\db\ActiveRecord
{
    const STATUS_UNREAD = 1;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'messages';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['message_room_id', 'user_id', 'status'], 'integer'],
            [['message'], 'string'],
            [['date'], 'safe'],
            [['message_room_id'], 'exist', 'skipOnError' => true, 'targetClass' => MessageRoom::className(), 'targetAttribute' => ['message_room_id' => 'id']],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['user_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'message_room_id' => 'Message Room ID',
            'user_id' => 'User ID',
            'message' => 'Message',
            'status' => 'Status',
            'date' => 'Date',
        ];
    }

    public function getFilePath($s = 'original')
    {
        if ($this->chatFile && $this->chatFile->photo) {
            return $this->chatFile->getPhoto('chat', $s);
        }
    }

    public function getSender()
    {
        $data = [];

        $data['name'] = $this->user->name;
        $data['photo'] = $this->user->getPhoto();

        return $data;
    }

    public function getGetter()
    {
        $data = [];

        $data['name'] = ($this->user_id == $this->messageRoom->sender_id) ? $this->messageRoom->getter->name : $this->messageRoom->sender->name;
        $data['photo'] = ($this->user_id == $this->messageRoom->sender_id) ? $this->messageRoom->getter->getPhoto() : $this->messageRoom->sender->getPhoto();

        return $data;
    }

    // fields
    public function fields()
    {
        return ['id', 'type_user' => function () {
            return $this->messageRoom->type;
        }, 'sender', 'getter', 'message', 'messageRoom', 'filePath', 'product' => function () {
            return $this->messageRoom->getProductObject();
        }, 'status', 'date'];
    }

    /**
     * Gets query for [[MessageRoom]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getMessageRoom()
    {
        return $this->hasOne(MessageRoom::className(), ['id' => 'message_room_id']);
    }

    /**
     * Gets query for [[User]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }

    public function getChatFile()
    {
        return $this->hasOne(Images::className(), ['object_id' => 'id'])->andOnCondition(['type' => 'chat']);
    }
}
