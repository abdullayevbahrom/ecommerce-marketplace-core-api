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
 * @property string $pinfl Personal ID number (unique, 14 digits)
 * @property string|null $first_name First name (Cyrillic)
 * @property string|null $last_name Last name (Cyrillic)
 * @property string|null $middle_name Middle name (Cyrillic)
 * @property string|null $first_name_en First name (Latin)
 * @property string|null $last_name_en Last name (Latin)
 * @property string|null $birth_date Date of birth (YYYY-MM-DD)
 * @property string|null $birth_place Birth place
 * @property int|null $gender 1=Male, 2=Female
 * @property string|null $nationality Nationality
 * @property string|null $citizenship Citizenship
 * @property string|null $passport_series Passport series (e.g., AA)
 * @property string|null $passport_number Passport number
 * @property string|null $passport_issued_by Passport issued by
 * @property string|null $passport_issued_date Passport issued date
 * @property string|null $passport_expiry_date Passport expiry date
 * @property string|null $doc_type Document type
 * @property string|null $living_address Current address (legacy)
 * @property string|null $permanent_address Permanent address
 * @property string|null $temporary_address Temporary address
 * @property string|null $phone Phone from MyID
 * @property string|null $email Email from MyID
 * @property float|null $comparison_value Face comparison confidence (0.0-1.0)
 * @property string|null $job_id MyID job UUID
 * @property string|null $sdk_hash SDK tracking hash
 * @property string|null $reuid Reusable unique ID for secondary requests
 * @property int|null $reuid_expires_at Reuid expiration timestamp
 * @property string|null $photo Base64 encoded photo
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
    const STATUS_PENDING = 0;
    const STATUS_VERIFIED = 1;
    const STATUS_FAILED = 2;
    const STATUS_EXPIRED = 3;

    const GENDER_MALE = 1;
    const GENDER_FEMALE = 2;

    public static function tableName()
    {
        return 'user_myid';
    }

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

    public function rules()
    {
        return [
            [['pinfl'], 'required'],
            [['user_id', 'gender', 'verification_status', 'reuid_expires_at'], 'integer'],
            [['comparison_value'], 'number'],
            [['birth_date', 'verified_at', 'created_at', 'updated_at', 'passport_issued_date', 'passport_expiry_date'], 'safe'],
            [['photo', 'myid_response'], 'string'],
            [['pinfl'], 'string', 'max' => 14],
            [['passport_series'], 'string', 'max' => 2],
            [['passport_number'], 'string', 'max' => 7],
            [['first_name', 'last_name', 'middle_name', 'first_name_en', 'last_name_en'], 'string', 'max' => 100],
            [['nationality', 'citizenship', 'doc_type'], 'string', 'max' => 50],
            [['birth_place', 'permanent_address', 'temporary_address', 'passport_issued_by'], 'string', 'max' => 500],
            [['living_address'], 'string', 'max' => 255],
            [['phone'], 'string', 'max' => 20],
            [['email'], 'string', 'max' => 100],
            [['sdk_hash', 'job_id', 'reuid'], 'string', 'max' => 255],
            [['pinfl'], 'unique'],
            [['verification_status'], 'default', 'value' => self::STATUS_PENDING],
            [['verification_status'], 'in', 'range' => [self::STATUS_PENDING, self::STATUS_VERIFIED, self::STATUS_FAILED, self::STATUS_EXPIRED]],
            [['gender'], 'in', 'range' => [self::GENDER_MALE, self::GENDER_FEMALE]],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User',
            'pinfl' => 'PINFL',
            'first_name' => 'First Name',
            'last_name' => 'Last Name',
            'middle_name' => 'Middle Name',
            'first_name_en' => 'First Name (EN)',
            'last_name_en' => 'Last Name (EN)',
            'birth_date' => 'Birth Date',
            'birth_place' => 'Birth Place',
            'gender' => 'Gender',
            'nationality' => 'Nationality',
            'citizenship' => 'Citizenship',
            'passport_series' => 'Passport Series',
            'passport_number' => 'Passport Number',
            'passport_issued_by' => 'Passport Issued By',
            'passport_issued_date' => 'Passport Issued Date',
            'passport_expiry_date' => 'Passport Expiry Date',
            'doc_type' => 'Document Type',
            'living_address' => 'Living Address',
            'permanent_address' => 'Permanent Address',
            'temporary_address' => 'Temporary Address',
            'phone' => 'Phone',
            'email' => 'Email',
            'comparison_value' => 'Comparison Score',
            'job_id' => 'Job ID',
            'sdk_hash' => 'SDK Hash',
            'reuid' => 'Reusable UID',
            'reuid_expires_at' => 'Reuid Expires At',
            'verification_status' => 'Verification Status',
            'verified_at' => 'Verified At',
            'myid_response' => 'MyID Response',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public function getFullName()
    {
        $parts = array_filter([$this->last_name, $this->first_name, $this->middle_name]);
        return implode(' ', $parts);
    }

    public function getFullNameEn()
    {
        $parts = array_filter([$this->last_name_en, $this->first_name_en]);
        return implode(' ', $parts);
    }

    public function getPassportFull()
    {
        if ($this->passport_series && $this->passport_number) {
            return $this->passport_series . $this->passport_number;
        }
        return '';
    }

    public function getGenderLabel()
    {
        $labels = [self::GENDER_MALE => 'Male', self::GENDER_FEMALE => 'Female'];
        return $labels[$this->gender] ?? 'Unknown';
    }

    public function getVerificationStatusLabel()
    {
        $labels = [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_VERIFIED => 'Verified',
            self::STATUS_FAILED => 'Failed',
            self::STATUS_EXPIRED => 'Expired',
        ];
        return $labels[$this->verification_status] ?? 'Unknown';
    }

    public function getVerificationStatusClass()
    {
        $classes = [
            self::STATUS_PENDING => 'warning',
            self::STATUS_VERIFIED => 'success',
            self::STATUS_FAILED => 'danger',
            self::STATUS_EXPIRED => 'default',
        ];
        return $classes[$this->verification_status] ?? 'default';
    }

    public function isVerified()
    {
        return $this->verification_status === self::STATUS_VERIFIED;
    }

    public function hasValidReuid()
    {
        return !empty($this->reuid) && $this->reuid_expires_at > time();
    }

    public function getMyidResponseArray()
    {
        return $this->myid_response ? json_decode($this->myid_response, true) : [];
    }

    public function setMyidResponseArray($data)
    {
        $this->myid_response = json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    public static function findByPinfl($pinfl)
    {
        return static::findOne(['pinfl' => $pinfl]);
    }

    public static function findByUserId($userId)
    {
        return static::findOne(['user_id' => $userId]);
    }

    /**
     * Create or update from new SDK flow response
     *
     * @param array $sdkData The 'data' object from GET /api/v1/sdk/data response
     * @param array|null $reuid The 'reuid' object from response
     * @param int|null $userId User ID to link
     * @return static|null
     */
    public static function createFromSdkData($sdkData, $reuid = null, $userId = null)
    {
        $profile = $sdkData['profile'] ?? null;
        if (!$profile) {
            Yii::error('MyID SDK data missing profile', __METHOD__);
            return null;
        }

        $commonData = $profile['common_data'] ?? [];
        $docData = $profile['doc_data'] ?? [];
        $contacts = $profile['contacts'] ?? [];
        $address = $profile['address'] ?? [];

        $pinfl = $commonData['pinfl'] ?? null;
        if (empty($pinfl)) {
            Yii::error('MyID SDK data missing PINFL', __METHOD__);
            return null;
        }

        $model = static::findByPinfl($pinfl);
        if (!$model) {
            $model = new static();
            $model->pinfl = $pinfl;
        }

        if ($userId && !$model->user_id) {
            $model->user_id = $userId;
        }

        // Common data
        $model->first_name = $commonData['first_name'] ?? null;
        $model->last_name = $commonData['last_name'] ?? null;
        $model->middle_name = $commonData['middle_name'] ?? null;
        $model->first_name_en = $commonData['first_name_en'] ?? null;
        $model->last_name_en = $commonData['last_name_en'] ?? null;
        $model->birth_date = $commonData['birth_date'] ?? null;
        $model->birth_place = isset($commonData['birth_place']) ? mb_substr((string)$commonData['birth_place'], 0, 500) : null;
        $model->nationality = isset($commonData['nationality']) ? mb_substr((string)$commonData['nationality'], 0, 50) : null;
        $model->citizenship = isset($commonData['citizenship']) ? mb_substr((string)$commonData['citizenship'], 0, 50) : null;
        $model->sdk_hash = $commonData['sdk_hash'] ?? null;

        // Gender mapping
        if (isset($commonData['gender'])) {
            $gender = strtolower((string)$commonData['gender']);
            if (in_array($gender, ['male', '1', 'м', 'm'])) {
                $model->gender = self::GENDER_MALE;
            } elseif (in_array($gender, ['female', '2', 'ж', 'f'])) {
                $model->gender = self::GENDER_FEMALE;
            }
        }

        // Document data
        if (!empty($docData['pass_data'])) {
            $passData = $docData['pass_data'];
            if (strlen($passData) >= 9) {
                $model->passport_series = substr($passData, 0, 2);
                $model->passport_number = mb_substr(substr($passData, 2), 0, 7);
            }
        }
        $model->passport_issued_by = $docData['issued_by'] ?? null;
        $model->passport_issued_date = $docData['issued_date'] ?? null;
        $model->passport_expiry_date = $docData['expiry_date'] ?? null;
        $model->doc_type = isset($docData['doc_type']) ? mb_substr((string)$docData['doc_type'], 0, 50) : null;

        // Contacts
        $model->phone = $contacts['phone'] ?? null;
        $model->email = $contacts['email'] ?? null;

        // Address — permanent_address and temporary_address are strings per MyID docs,
        // but truncate to column limit to handle edge cases
        $permanentAddr = $address['permanent_address'] ?? null;
        $temporaryAddr = $address['temporary_address'] ?? null;
        $model->permanent_address = is_string($permanentAddr) ? mb_substr($permanentAddr, 0, 500) : null;
        $model->temporary_address = is_string($temporaryAddr) ? mb_substr($temporaryAddr, 0, 500) : null;

        // Comparison and job
        $model->comparison_value = $sdkData['comparison_value'] ?? null;
        $model->job_id = $sdkData['job_id'] ?? null;

        // Reuid for secondary requests
        if ($reuid && isset($reuid['value'])) {
            $model->reuid = $reuid['value'];
            $model->reuid_expires_at = $reuid['expires_at'] ?? null;
        }

        // Store full response for audit
        $model->myid_response = json_encode([
            'data' => $sdkData,
            'reuid' => $reuid,
        ], JSON_UNESCAPED_UNICODE);

        // Mark as verified
        $model->verification_status = self::STATUS_VERIFIED;
        $model->verified_at = date('Y-m-d H:i:s');

        if ($model->save()) {
            if ($model->user_id) {
                User::updateAll(['myid_verified' => 1], ['id' => $model->user_id]);
            }
            return $model;
        }

        $errorDetail = json_encode($model->errors, JSON_UNESCAPED_UNICODE);
        Yii::error('Failed to save UserMyid: ' . $errorDetail, __METHOD__);
        $model->addError('_save', $errorDetail);
        return null;
    }

    /**
     * Create or update verification from normalized MyID data (WebSDK flow).
     * Kept for backward compatibility with WebSDK flow.
     *
     * @param array $myidData Normalized flat data from MyidService
     * @param int|null $userId User ID to link (optional)
     * @return static|null
     */
    public static function createFromMyidData($myidData, $userId = null)
    {
        // If the data is already in the new format (has 'profile' key)
        if (isset($myidData['profile'])) {
            return static::createFromSdkData($myidData, null, $userId);
        }

        if (empty($myidData['pinfl'])) {
            return null;
        }

        $model = static::findByPinfl($myidData['pinfl']);
        if (!$model) {
            $model = new static();
            $model->pinfl = $myidData['pinfl'];
        }

        if ($userId && !$model->user_id) {
            $model->user_id = $userId;
        }

        // Map normalized flat fields
        $model->first_name = $myidData['first_name'] ?? null;
        $model->last_name = $myidData['last_name'] ?? null;
        $model->middle_name = $myidData['middle_name'] ?? null;
        $model->birth_date = $myidData['birth_date'] ?? null;
        $model->birth_place = $myidData['birth_place'] ?? null;
        $model->nationality = $myidData['nationality'] ?? null;
        $model->citizenship = $myidData['citizenship'] ?? null;
        $livingAddr = $myidData['living_address'] ?? null;
        $model->living_address = is_string($livingAddr) ? mb_substr($livingAddr, 0, 255) : null;
        $model->passport_series = $myidData['passport_series'] ?? null;
        $passNum = $myidData['passport_number'] ?? null;
        $model->passport_number = is_string($passNum) ? mb_substr($passNum, 0, 7) : null;
        $model->passport_issued_by = $myidData['passport_issued_by'] ?? null;
        $model->passport_issued_date = $myidData['passport_issued_date'] ?? null;
        $model->passport_expiry_date = $myidData['passport_expiry_date'] ?? null;
        $model->phone = $myidData['phone'] ?? null;
        $model->email = $myidData['email'] ?? null;
        $model->photo = $myidData['photo'] ?? null;
        $model->sdk_hash = $myidData['sdk_hash'] ?? null;

        // Gender mapping
        if (isset($myidData['gender'])) {
            $gender = strtolower((string)$myidData['gender']);
            if (in_array($gender, ['male', '1', 'м', 'm'])) {
                $model->gender = self::GENDER_MALE;
            } elseif (in_array($gender, ['female', '2', 'ж', 'f'])) {
                $model->gender = self::GENDER_FEMALE;
            }
        }

        // Store full response for audit
        $model->setMyidResponseArray($myidData);

        // Set verified status
        $model->verification_status = self::STATUS_VERIFIED;
        $model->verified_at = date('Y-m-d H:i:s');

        if ($model->save()) {
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
     */
    public function toApiArray()
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'pinfl' => $this->pinfl,
            'full_name' => $this->getFullName(),
            'full_name_en' => $this->getFullNameEn(),
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'middle_name' => $this->middle_name,
            'birth_date' => $this->birth_date,
            'gender' => $this->gender,
            'gender_label' => $this->getGenderLabel(),
            'nationality' => $this->nationality,
            'passport' => $this->getPassportFull(),
            'comparison_value' => $this->comparison_value,
            'verification_status' => $this->verification_status,
            'verification_status_label' => $this->getVerificationStatusLabel(),
            'verified_at' => $this->verified_at,
            'has_reuid' => $this->hasValidReuid(),
        ];
    }
}
