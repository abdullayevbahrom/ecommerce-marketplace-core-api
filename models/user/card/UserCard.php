<?php

namespace app\models\user\card;

use Yii;
use app\models\user\User;
use app\models\Category;

/**
 * This is the model class for table "user_card".
 *
 * @property int $id
 * @property int|null $user_id
 * @property string|null $card_token
 * @property string|null $card_number
 * @property string|null $card_expire
 * @property string|null $card_phone_number
 * @property int $status
 * @property string $date
 *
 * @property User $user
 */
class UserCard extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user_card';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['card_number', 'card_expire', 'card_phone_number'], 'required', 'message'=>'Fill in the field'],
            ['card_number', 'checkCard'],
            ['card_type_id', 'checkCardType'],
            [['user_id', 'card_type_id', 'status'], 'integer'],
            [['date'], 'safe'],
            [['card_number', 'card_expire', 'card_phone_number'], 'string', 'max' => 255],
            [['card_token'], 'string'],
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
            'user_id' => 'User ID',
            'card_token' => 'Card Token',
            'card_number' => 'Card Number',
            'card_expire' => 'Card Expire',
            'card_phone_number' => 'Card Phone Number',
            'status' => 'Status',
            'date' => 'Date',
        ];
    }

    // check card
    public function checkCard($attribute, $params) {
        if (!$this->hasErrors()) {
            $card_number = substr_replace($this->card_number, '******', 6, -4);
            $card = self::findOne(['card_number'=>$card_number, 'card_phone_number'=>$this->card_phone_number]);
            if ($card) {
                return $this->addError($attribute, 'Card already exists');
            }
        }

        return false;
    }

    // check card type
    public function checkCardType($attribute, $params) {
        if (!$this->hasErrors()) {
            $category = Category::findOne(['id'=>$this->card_type_id, 'type'=>'card']);
            if (!$category) {
                return $this->addError($attribute, 'This type of card does not exist');
            }
        }

        return false;
    }

    public function encodeRSA($card_number) {
        $file = file_get_contents('../services/key/public.pem');
        $publick_key = openssl_get_publickey($file);
        openssl_public_encrypt($data, $encrypted, $publick_key);

        return base64_encode($encrypted);
    }

    public function saveObject($user_id = null) {
        $this->user_id = $user_id ? $user_id : Yii::$app->user->identity->id;
        $this->card_token = $this->encodeRSA($this->card_number);
        $this->card_number = substr_replace($this->card_number, '******', 4, -4);;

        return $this->save();
    }

    public function fields() {
        return ['id', 'cardType', 'card_number', 'card_expire', 'card_phone_number', 'status', 'date'];
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

    public function getCardType()
    {
        return $this->hasOne(Category::className(), ['id' => 'card_type_id']);
    }
}
