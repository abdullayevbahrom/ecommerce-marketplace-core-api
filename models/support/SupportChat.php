<?php

namespace app\models\support;

use Yii;
use app\models\user\User;

/**
 * This is the model class for table "support_chat".
 *
 * @property int $id
 * @property int|null $sender_id
 * @property int|null $getter_id
 * @property string|null $subject
 * @property int $status
 * @property string $created_at
 * @property string|null $updated_at
 *
 * @property Messages[] $messages
 * @property User $getter
 * @property User $sender
 */
class SupportChat extends \yii\db\ActiveRecord
{
    const STATUS_ACTIVE = 1;
    const STATUS_CLOSED = 2;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'support_chat';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['sender_id', 'getter_id', 'status'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['subject'], 'string', 'max' => 255],
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
            'subject' => 'Subject',
            'status' => 'Status',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
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
     * Gets query for [[Getter]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getGetter()
    {
        return $this->hasOne(User::className(), ['id' => 'getter_id']);
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

    /**
     * Returns the number of unread messages in this chat for the given user.
     *
     * @param int $userId the user ID
     * @return int the number of unread messages
     */
    public function getUnreadMessageCount($userId)
    {
        return Messages::find()
            ->where(['message_room_id' => $this->id, 'status' => Messages::STATUS_UNREAD, 'user_id' => $userId])
            ->count();
    }

    /**
     * Returns the latest message in this chat.
     *
     * @return Messages|null the latest message, or null if there are no messages
     */
    public function getLatestMessage()
    {
        return Messages::find()
            ->where(['message_room_id' => $this->id])
            ->orderBy(['date' => SORT_DESC])
            ->one();
    }

    /**
     * Returns the name of the other user in this chat.
     *
     * @param int $userId the user ID
     * @return string the name of the other user
     */
    public function getOtherUserName($userId)
    {
        return ($this->sender_id == $userId) ? $this->getter->name : $this->sender->name;
    }

    /**
     * Returns the photo of the other user in this chat.
     *
     * @param int $userId the user ID
     * @return string the photo of the other user
     */
    public function getOtherUserPhoto($userId)
    {
        return ($this->sender_id == $userId) ? $this->getter->getPhoto() : $this->sender->getPhoto();
    }
}