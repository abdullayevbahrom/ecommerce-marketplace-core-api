<?php

namespace app\models\user;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "user_myid".
 *
 * @property int $id
 * @property int|null $user_id Foreign key to user.id
 * @property string $pinfl Personal ID number (unique)
 * @property string|null $passport_series Passport series (e.g., AA)
 * @property string|null $passport_number Passport number
 * @property string|null $first_name First name from passport
 * @property string|null $last_name Last name from passport
 * @property string|null $middle_name Middle name (patronymic)
 * @property string|null $birth_date Date of birth
 * @property int|null $gender 1=Male, 2=Female
 * @property string|null $nationality Nationality
 * @property string|null $birth_place Birth place
 * @property string|null $living_address Current address
 * @property string|null $photo Base64 encoded photo
 * @property string|null $sdk_hash SDK hash for verification
 * @property int $verification_status 0=pending, 1=verified, 2=failed, 3=expired
 * @property string|null $verified_at Verification timestamp
 * @property string|null $myid_response Full JSON response for audit
 * @property string|null $created_at
 * @property string|null $updated_at
 *
 * @property User $user
 */
class UserMyid extends ActiveRecord
{
    // Verification status constants
    const STATUS_PENDING = 0;
    const STATUS_VERIFIED = 1;
    const STATUS_FAILED = 2;
    const STATUS_EXPIRED = 3;

    // Gender constants
    const GENDER_MALE = 1;
    const GENDER_FEMALE = 2;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user_myid';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'updated_at',
                'value' => new Expression('NOW()'),
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['pinfl'], 'required'],
            [['user_id', 'gender', 'verification_status'], 'integer'],
            [['birth_date', 'verified_at', 'created_at', 'updated_at'], 'safe'],
            [['photo', 'myid_response'], 'string'],
            [['pinfl'], 'string', 'max' => 14],
            [['passport_series'], 'string', 'max' => 2],
            [['passport_number'], 'string', 'max' => 7],
            [['first_name', 'last_name', 'middle_name'], 'string', 'max' => 100],
            [['nationality'], 'string', 'max' => 50],
            [['birth_place', 'living_address'], 'string', 'max' => 255],
            [['sdk_hash'], 'string', 'max' => 64],
            [['pinfl'], 'unique'],
            [['verification_status'], 'default', 'value' => self::STATUS_PENDING],
            [['verification_status'], 'in', 'range' => [self::STATUS_PENDING, self::STATUS_VERIFIED, self::STATUS_FAILED, self::STATUS_EXPIRED]],
            [['gender'], 'in', 'range' => [self::GENDER_MALE, self::GENDER_FEMALE]],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'Пользователь',
            'pinfl' => 'ПИНФЛ',
            'passport_series' => 'Серия паспорта',
            'passport_number' => 'Номер паспорта',
            'first_name' => 'Имя',
            'last_name' => 'Фамилия',
            'middle_name' => 'Отчество',
            'birth_date' => 'Дата рождения',
            'gender' => 'Пол',
            'nationality' => 'Национальность',
            'birth_place' => 'Место рождения',
            'living_address' => 'Адрес проживания',
            'photo' => 'Фото',
            'sdk_hash' => 'SDK Hash',
            'verification_status' => 'Статус верификации',
            'verified_at' => 'Дата верификации',
            'myid_response' => 'Ответ MyID',
            'created_at' => 'Создано',
            'updated_at' => 'Обновлено',
        ];
    }

    /**
     * Gets query for [[User]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    /**
     * Get full name
     * @return string
     */
    public function getFullName()
    {
        $parts = array_filter([$this->last_name, $this->first_name, $this->middle_name]);
        return implode(' ', $parts);
    }

    /**
     * Get passport full number
     * @return string
     */
    public function getPassportFull()
    {
        if ($this->passport_series && $this->passport_number) {
            return $this->passport_series . $this->passport_number;
        }
        return '';
    }

    /**
     * Get gender label
     * @return string
     */
    public function getGenderLabel()
    {
        $labels = [
            self::GENDER_MALE => 'Мужской',
            self::GENDER_FEMALE => 'Женский',
        ];
        return isset($labels[$this->gender]) ? $labels[$this->gender] : 'Не указан';
    }

    /**
     * Get verification status label
     * @return string
     */
    public function getVerificationStatusLabel()
    {
        $labels = [
            self::STATUS_PENDING => 'Ожидает',
            self::STATUS_VERIFIED => 'Верифицирован',
            self::STATUS_FAILED => 'Ошибка',
            self::STATUS_EXPIRED => 'Истёк',
        ];
        return isset($labels[$this->verification_status]) ? $labels[$this->verification_status] : 'Неизвестно';
    }

    /**
     * Get verification status CSS class
     * @return string
     */
    public function getVerificationStatusClass()
    {
        $classes = [
            self::STATUS_PENDING => 'warning',
            self::STATUS_VERIFIED => 'success',
            self::STATUS_FAILED => 'danger',
            self::STATUS_EXPIRED => 'default',
        ];
        return isset($classes[$this->verification_status]) ? $classes[$this->verification_status] : 'default';
    }

    /**
     * Check if verified
     * @return bool
     */
    public function isVerified()
    {
        return $this->verification_status === self::STATUS_VERIFIED;
    }

    /**
     * Get MyID response as array
     * @return array
     */
    public function getMyidResponseArray()
    {
        return $this->myid_response ? json_decode($this->myid_response, true) : [];
    }

    /**
     * Set MyID response from array
     * @param array $data
     */
    public function setMyidResponseArray($data)
    {
        $this->myid_response = json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Find by PINFL
     * @param string $pinfl
     * @return static|null
     */
    public static function findByPinfl($pinfl)
    {
        return static::findOne(['pinfl' => $pinfl]);
    }

    /**
     * Find by user ID
     * @param int $userId
     * @return static|null
     */
    public static function findByUserId($userId)
    {
        return static::findOne(['user_id' => $userId]);
    }

    /**
     * Create or update verification from MyID data
     * @param array $myidData - Data from MyID API
     * @param int|null $userId - User ID to link (optional)
     * @return static|null
     */
    public static function createFromMyidData($myidData, $userId = null)
    {
        if (empty($myidData['pinfl'])) {
            return null;
        }

        // Find existing or create new
        $model = static::findByPinfl($myidData['pinfl']);
        if (!$model) {
            $model = new static();
            $model->pinfl = $myidData['pinfl'];
        }

        // Update user_id if provided and not already set
        if ($userId && !$model->user_id) {
            $model->user_id = $userId;
        }

        // Map MyID data to model attributes
        $model->first_name = $myidData['first_name'] ?? $myidData['firstNameLatin'] ?? null;
        $model->last_name = $myidData['last_name'] ?? $myidData['lastNameLatin'] ?? null;
        $model->middle_name = $myidData['middle_name'] ?? $myidData['middleNameLatin'] ?? null;
        $model->birth_date = $myidData['birth_date'] ?? $myidData['birthDate'] ?? null;
        
        // Gender mapping
        if (isset($myidData['gender'])) {
            $gender = strtolower($myidData['gender']);
            if ($gender === 'male' || $gender === '1' || $gender === 1) {
                $model->gender = self::GENDER_MALE;
            } elseif ($gender === 'female' || $gender === '2' || $gender === 2) {
                $model->gender = self::GENDER_FEMALE;
            }
        }

        $model->nationality = $myidData['nationality'] ?? null;
        $model->birth_place = $myidData['birth_place'] ?? $myidData['birthPlace'] ?? null;
        
        // Address
        if (isset($myidData['address'])) {
            $model->living_address = is_array($myidData['address']) 
                ? ($myidData['address']['living'] ?? $myidData['address']['permanent'] ?? json_encode($myidData['address']))
                : $myidData['address'];
        } elseif (isset($myidData['livingAddress'])) {
            $model->living_address = $myidData['livingAddress'];
        }

        // Passport
        if (isset($myidData['passport'])) {
            $model->passport_series = $myidData['passport']['series'] ?? null;
            $model->passport_number = $myidData['passport']['number'] ?? null;
        } elseif (isset($myidData['passportSerial'])) {
            $model->passport_series = $myidData['passportSerial'];
            $model->passport_number = $myidData['passportNumber'] ?? null;
        }

        // Photo
        $model->photo = $myidData['photo'] ?? $myidData['userPhoto'] ?? null;

        // SDK hash
        $model->sdk_hash = $myidData['sdk_hash'] ?? $myidData['sdkHash'] ?? null;

        // Store full response
        $model->setMyidResponseArray($myidData);

        // Set verified status
        $model->verification_status = self::STATUS_VERIFIED;
        $model->verified_at = date('Y-m-d H:i:s');

        if ($model->save()) {
            // Update user's myid_verified flag
            if ($model->user_id) {
                User::updateAll(['myid_verified' => 1], ['id' => $model->user_id]);
            }
            return $model;
        }

        Yii::error('Failed to save UserMyid: ' . json_encode($model->errors), __METHOD__);
        return null;
    }

    /**
     * Get data for API response (without sensitive info)
     * @return array
     */
    public function toApiArray()
    {
        return [
            'id' => $this->id,
            'pinfl' => $this->pinfl,
            'full_name' => $this->getFullName(),
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'middle_name' => $this->middle_name,
            'birth_date' => $this->birth_date,
            'gender' => $this->gender,
            'gender_label' => $this->getGenderLabel(),
            'passport' => $this->getPassportFull(),
            'verification_status' => $this->verification_status,
            'verification_status_label' => $this->getVerificationStatusLabel(),
            'verified_at' => $this->verified_at,
        ];
    }
}
