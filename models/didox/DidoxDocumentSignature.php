<?php

namespace app\models\didox;

use Yii;
use app\models\user\User;

/**
 * Model for table "didox_document_signatures".
 * Stores E-IMZO signatures for documents created/sent by app.
 *
 * @property int $id
 * @property int $didox_document_id
 * @property int $user_id
 * @property string $signer_tax_id
 * @property string|null $signer_name
 * @property string $signature_data PKCS7/timestamp base64
 * @property string $signature_type sign|accept|reject
 * @property string|null $comment Rejection comment
 * @property string $signed_at
 * @property string|null $certificate_serial
 * @property string|null $certificate_valid_from
 * @property string|null $certificate_valid_to
 * @property string|null $didox_response Raw Didox API response
 * @property string|null $created_at
 *
 * @property DidoxDocument $document
 * @property User $user
 */
class DidoxDocumentSignature extends \yii\db\ActiveRecord
{
    const TYPE_SIGN = 'sign';
    const TYPE_ACCEPT = 'accept';
    const TYPE_REJECT = 'reject';

    public static function tableName()
    {
        return 'didox_document_signatures';
    }

    public function rules()
    {
        return [
            [['didox_document_id', 'user_id', 'signer_tax_id', 'signature_data', 'signed_at'], 'required'],
            [['didox_document_id', 'user_id'], 'integer'],
            [['signature_data', 'didox_response', 'comment'], 'string'],
            [['signed_at', 'certificate_valid_from', 'certificate_valid_to', 'created_at'], 'safe'],
            [['signer_tax_id'], 'string', 'max' => 20],
            [['signer_name'], 'string', 'max' => 255],
            [['certificate_serial'], 'string', 'max' => 100],
            [['signature_type'], 'in', 'range' => [self::TYPE_SIGN, self::TYPE_ACCEPT, self::TYPE_REJECT]],
            [['didox_document_id'], 'exist', 'targetClass' => DidoxDocument::class, 'targetAttribute' => 'id'],
            [['user_id'], 'exist', 'targetClass' => User::class, 'targetAttribute' => 'id'],
        ];
    }

    public function getDocument()
    {
        return $this->hasOne(DidoxDocument::class, ['id' => 'didox_document_id']);
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    /**
     * Create a signature record from an accept/sign action.
     */
    public static function createFromAccept(DidoxDocument $document, User $user, string $signatureData, array $didoxResponse = [])
    {
        $signature = new self();
        $signature->didox_document_id = $document->id;
        $signature->user_id = $user->id;
        $signature->signer_tax_id = $user->eimzo_tax_id ?? '';
        $signature->signer_name = trim(($user->lastname ?? '') . ' ' . ($user->name ?? ''));
        $signature->signature_data = $signatureData;
        $signature->signature_type = self::TYPE_ACCEPT;
        $signature->signed_at = date('Y-m-d H:i:s');
        $signature->didox_response = !empty($didoxResponse) ? json_encode($didoxResponse) : null;

        // Extract certificate info if available
        if (!empty($user->eimzo_certificate_info)) {
            $certInfo = json_decode($user->eimzo_certificate_info, true);
            if (is_array($certInfo)) {
                $signature->certificate_serial = $certInfo['serialNumber'] ?? null;
                $signature->certificate_valid_from = $certInfo['validFrom'] ?? null;
                $signature->certificate_valid_to = $certInfo['validTo'] ?? null;
            }
        }

        $signature->save(false);
        return $signature;
    }

    /**
     * Create a signature record from a reject action.
     */
    public static function createFromReject(DidoxDocument $document, User $user, string $comment, array $didoxResponse = [])
    {
        $signature = new self();
        $signature->didox_document_id = $document->id;
        $signature->user_id = $user->id;
        $signature->signer_tax_id = $user->eimzo_tax_id ?? '';
        $signature->signer_name = trim(($user->lastname ?? '') . ' ' . ($user->name ?? ''));
        $signature->signature_data = 'rejected';
        $signature->signature_type = self::TYPE_REJECT;
        $signature->comment = $comment;
        $signature->signed_at = date('Y-m-d H:i:s');
        $signature->didox_response = !empty($didoxResponse) ? json_encode($didoxResponse) : null;

        $signature->save(false);
        return $signature;
    }

    public function fields()
    {
        return [
            'id',
            'didox_document_id',
            'user_id',
            'signer_tax_id',
            'signer_name',
            'signature_type',
            'comment',
            'signed_at',
            'certificate_serial',
        ];
    }
}
