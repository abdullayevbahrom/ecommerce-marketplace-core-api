<?php

namespace app\models\didox;

use Yii;
use app\models\user\User;
use app\models\didox\DidoxDocumentArbitrary;

/**
 * This is the parent model class for table "didox_document".
 * Contains only core fields common to all DIDOX document types.
 * Document type specific fields are in separate tables.
 *
 * @property int $id
 * @property string $name Document name
 * @property string $document_type Document type enum (invoice)
 * @property string|null $didox_doc_type DIDOX document type code (002=invoice, 001=waybill, etc.)
 * @property string|null $didox_id DIDOX document ID (_id from API response)
 * @property int|null $didox_status DIDOX document status (0-190)
 * @property string|null $didox_data JSON encoded DIDOX API response data
 * @property string|null $didox_error_data JSON encoded DIDOX API error responses
 * @property string|null $pdf_paths JSON with paths to locally saved PDFs
 * @property string|null $didox_last_attempt Last DIDOX API submission attempt
 * @property string|null $didox_created_at DIDOX document creation timestamp
 * @property string|null $didox_signed_at DIDOX document signing timestamp
 * @property string|null $didox_user_key User key for DIDOX authentication
 * @property int|null $created_by User ID who created the document
 * @property int|null $to_user_id User ID assigned to sign the document
 * @property int|null $status Local document status (0=inactive, 1=active, 2=blocked)
 * @property string|null $created_at
 * @property string|null $updated_at
 *
 * @property User $createdBy
 * @property User $toUser
 * @property DidoxDocumentInvoice $invoice Invoice details (if document_type = 'invoice')
 * @property DidoxDocumentArbitrary $arbitrary Arbitrary contract details (if document_type = 'arbitrary')
 * @property DidoxDocumentIncludedProducts[] $includedProducts Products included in this document
 * @property \app\models\order\Order $order Associated order (if order_id is set)
 */
class DidoxDocument extends \yii\db\ActiveRecord
{
    /**
     * @var string|null Public property to handle pdf_paths until migration is run
     */
    public $pdf_paths;

    const DOCUMENT_TYPE_INVOICE = 'invoice';
    const DOCUMENT_TYPE_ARBITRARY = 'arbitrary';

    // DIDOX Status Constants
    const STATUS_DRAFT = 0;
    const STATUS_WAITING_PARTNER_SIGNATURE = 1;
    const STATUS_WAITING_YOUR_SIGNATURE = 2;
    const STATUS_SIGNED = 3;
    const STATUS_REJECTED = 4;
    const STATUS_DELETED = 5;
    const STATUS_WAITING_AGENT_SIGNATURE = 6;
    const STATUS_SIGNED_BY_AGENT = 8;
    const STATUS_INVALID = 40;
    const STATUS_DRAFT_DELETED = 55;
    const STATUS_WAITING_AGENT_SIGNATURE_2 = 60;
    const STATUS_SENT = 110;
    const STATUS_CANCELED = 120;
    const STATUS_REJECTED_BY_RESPONSIBLE = 130;
    const STATUS_ACCEPTED_BY_RESPONSIBLE = 140;
    const STATUS_RETURNED_BY_RESPONSIBLE = 150;
    const STATUS_DELIVERED = 160;
    const STATUS_RETURNED_BY_RESPONSIBLE_2 = 190;

    // Local Status Constants
    const LOCAL_STATUS_INACTIVE = 0;
    const LOCAL_STATUS_ACTIVE = 1;
    const LOCAL_STATUS_BLOCKED = 2;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'didox_document';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'document_type'], 'required'],
            [['didox_status', 'created_by', 'to_user_id', 'status', 'order_id'], 'integer'],
            [['didox_data', 'didox_error_data'], 'string'],
            [['didox_last_attempt', 'didox_created_at', 'didox_signed_at', 'created_at', 'updated_at'], 'safe'],
            [['name'], 'string', 'max' => 255],
            [['didox_doc_type'], 'string', 'max' => 25],
            [['didox_id'], 'string', 'max' => 100],
            [['didox_user_key'], 'string', 'max' => 255],
            [['document_type'], 'in', 'range' => [self::DOCUMENT_TYPE_INVOICE, self::DOCUMENT_TYPE_ARBITRARY]],
            [['status'], 'in', 'range' => [self::LOCAL_STATUS_INACTIVE, self::LOCAL_STATUS_ACTIVE, self::LOCAL_STATUS_BLOCKED]],
            [['created_by'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['created_by' => 'id']],
            [['to_user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['to_user_id' => 'id']],
            [['order_id'], 'exist', 'skipOnError' => true, 'targetClass' => \app\models\order\Order::class, 'targetAttribute' => ['order_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Название документа',
            'document_type' => 'Тип документа',
            'didox_doc_type' => 'Тип документа DIDOX',
            'didox_id' => 'ID в DIDOX',
            'didox_status' => 'Статус DIDOX',
            'didox_data' => 'Данные DIDOX',
            'didox_error_data' => 'Данные об ошибке DIDOX',
            'didox_last_attempt' => 'Последняя попытка DIDOX',
            'didox_created_at' => 'Создан в DIDOX',
            'didox_signed_at' => 'Подписан в DIDOX',
            'didox_user_key' => 'Ключ пользователя DIDOX',
            'created_by' => 'Создал',
            'to_user_id' => 'Назначен пользователю',
            'order_id' => 'ID заказа',
            'status' => 'Статус',
            'created_at' => 'Создано',
            'updated_at' => 'Обновлено',
        ];
    }

    /**
     * Check if document is connected to DIDOX
     * @return bool
     */
    public function isDidoxDocument()
    {
        return !empty($this->didox_id);
    }

    /**
     * Get DIDOX status label
     * @return string
     */
    public function getDidoxStatusLabel()
    {
        $statuses = [
            self::STATUS_DRAFT => 'Черновик',
            self::STATUS_WAITING_PARTNER_SIGNATURE => 'Ожидает подписи партнера',
            self::STATUS_WAITING_YOUR_SIGNATURE => 'Ожидает вашей подписи',
            self::STATUS_SIGNED => 'Подписан',
            self::STATUS_REJECTED => 'Отказ от подписи',
            self::STATUS_DELETED => 'Удален',
            self::STATUS_WAITING_AGENT_SIGNATURE => 'Ожидает подписи агента',
            self::STATUS_SIGNED_BY_AGENT => 'Подписан доверенным лицом',
            self::STATUS_INVALID => 'Не действительный',
            self::STATUS_DRAFT_DELETED => 'Черновик удален',
            self::STATUS_WAITING_AGENT_SIGNATURE_2 => 'Ожидает подписи агента',
            self::STATUS_SENT => 'Отправлено',
            self::STATUS_CANCELED => 'Отменено',
            self::STATUS_REJECTED_BY_RESPONSIBLE => 'Отказано (отв. лицом)',
            self::STATUS_ACCEPTED_BY_RESPONSIBLE => 'Принято (отв. лицом)',
            self::STATUS_RETURNED_BY_RESPONSIBLE => 'Груз возвращен (отв.лицом)',
            self::STATUS_DELIVERED => 'Доставлено получателю',
            self::STATUS_RETURNED_BY_RESPONSIBLE_2 => 'Груз возвращен (отв.лицом)',
        ];

        return isset($statuses[$this->didox_status]) ? $statuses[$this->didox_status] : 'Неизвестно';
    }

    /**
     * Get DIDOX status color class
     * @return string
     */
    public function getDidoxStatusColor()
    {
        $colors = [
            self::STATUS_DRAFT => 'bg-gray',
            self::STATUS_WAITING_PARTNER_SIGNATURE => 'bg-yellow',
            self::STATUS_WAITING_YOUR_SIGNATURE => 'bg-orange',
            self::STATUS_SIGNED => 'bg-green',
            self::STATUS_REJECTED => 'bg-red',
            self::STATUS_DELETED => 'bg-red',
            self::STATUS_WAITING_AGENT_SIGNATURE => 'bg-blue',
            self::STATUS_SIGNED_BY_AGENT => 'bg-green',
            self::STATUS_INVALID => 'bg-red',
            self::STATUS_DRAFT_DELETED => 'bg-red',
            self::STATUS_WAITING_AGENT_SIGNATURE_2 => 'bg-blue',
            self::STATUS_SENT => 'bg-green',
            self::STATUS_CANCELED => 'bg-red',
            self::STATUS_REJECTED_BY_RESPONSIBLE => 'bg-red',
            self::STATUS_ACCEPTED_BY_RESPONSIBLE => 'bg-green',
            self::STATUS_RETURNED_BY_RESPONSIBLE => 'bg-orange',
            self::STATUS_DELIVERED => 'bg-green',
            self::STATUS_RETURNED_BY_RESPONSIBLE_2 => 'bg-orange',
        ];

        return isset($colors[$this->didox_status]) ? $colors[$this->didox_status] : 'bg-gray';
    }

    /**
     * Get document type label
     * @return string
     */
    public function getDocumentTypeLabel()
    {
        $types = [
            self::DOCUMENT_TYPE_INVOICE => 'Счет-фактура',
            self::DOCUMENT_TYPE_ARBITRARY => 'Договор',
        ];

        return isset($types[$this->document_type]) ? $types[$this->document_type] : $this->document_type;
    }

    /**
     * Check if document is an invoice
     * @return bool
     */
    public function isInvoice()
    {
        return $this->document_type === self::DOCUMENT_TYPE_INVOICE;
    }

    /**
     * Check if this is an arbitrary document
     * @return bool
     */
    public function isArbitrary()
    {
        return $this->document_type === self::DOCUMENT_TYPE_ARBITRARY;
    }

    /**
     * Can document be signed in DIDOX
     * @return bool
     */
    public function canBeSignedInDidox()
    {
        return $this->isDidoxDocument() && in_array($this->didox_status, [
            self::STATUS_DRAFT,
            self::STATUS_WAITING_YOUR_SIGNATURE,
            self::STATUS_WAITING_AGENT_SIGNATURE,
            self::STATUS_WAITING_AGENT_SIGNATURE_2
        ]);
    }

    /**
     * Can document be canceled in DIDOX
     * @return bool
     */
    public function canBeCanceledInDidox()
    {
        return $this->isDidoxDocument() && in_array($this->didox_status, [
            self::STATUS_DRAFT,
            self::STATUS_WAITING_PARTNER_SIGNATURE,
            self::STATUS_WAITING_YOUR_SIGNATURE,
            self::STATUS_WAITING_AGENT_SIGNATURE,
            self::STATUS_WAITING_AGENT_SIGNATURE_2
        ]);
    }

    /**
     * Get parsed DIDOX data
     * @return array
     */
    public function getDidoxDataArray()
    {
        return $this->didox_data ? json_decode($this->didox_data, true) : [];
    }

    /**
     * Set DIDOX data
     * @param array $data
     */
    public function setDidoxData($data)
    {
        $this->didox_data = json_encode($data);
    }

    /**
     * Get parsed DIDOX error data
     * @return array
     */
    public function getDidoxErrorDataArray()
    {
        return $this->didox_error_data ? json_decode($this->didox_error_data, true) : [];
    }

    /**
     * Set DIDOX error data
     * @param array $data
     */
    public function setDidoxErrorData($data)
    {
        $this->didox_error_data = json_encode($data);
        $this->didox_last_attempt = date('Y-m-d H:i:s');
    }

    /**
     * Check if document has DIDOX errors
     * @return bool
     */
    public function hasDidoxErrors()
    {
        return !empty($this->didox_error_data);
    }

    /**
     * Clear DIDOX error data
     */
    public function clearDidoxErrors()
    {
        $this->didox_error_data = null;
        $this->didox_last_attempt = null;
    }

    /**
     * Get parsed PDF paths
     * @return array - e.g. ['uz' => 'uploads/didox/abc123_uz.pdf', 'ru' => 'uploads/didox/abc123_ru.pdf']
     */
    public function getPdfPathsArray()
    {
        // Check if attribute exists (column may not be created yet)
        if (!$this->hasAttribute('pdf_paths')) {
            return [];
        }
        return $this->pdf_paths ? json_decode($this->pdf_paths, true) : [];
    }

    /**
     * Set PDF paths
     * @param array $paths - e.g. ['uz' => 'uploads/didox/abc123_uz.pdf']
     */
    public function setPdfPaths($paths)
    {
        // Check if attribute exists (column may not be created yet)
        if (!$this->hasAttribute('pdf_paths')) {
            return;
        }
        $this->pdf_paths = json_encode($paths);
    }

    /**
     * Get PDF path for specific language
     * @param string $lang - Language code (uz, ru, en)
     * @return string|null - Path to PDF file or null
     */
    public function getPdfPath($lang = 'uz')
    {
        $paths = $this->getPdfPathsArray();
        return isset($paths[$lang]) ? $paths[$lang] : null;
    }

    /**
     * Set PDF path for specific language
     * @param string $lang - Language code
     * @param string $path - Relative path to PDF file
     */
    public function setPdfPath($lang, $path)
    {
        $paths = $this->getPdfPathsArray();
        $paths[$lang] = $path;
        $this->setPdfPaths($paths);
    }

    /**
     * Get full filesystem path to PDF
     * @param string $lang - Language code
     * @return string|null - Full path or null
     */
    public function getPdfFullPath($lang = 'uz')
    {
        $path = $this->getPdfPath($lang);
        if ($path) {
            return Yii::getAlias('@webroot') . DIRECTORY_SEPARATOR . $path;
        }

        // Fallback: return standard path if didox_id exists
        if ($this->didox_id) {
            $standardPath = Yii::getAlias('@webroot') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'didox' . DIRECTORY_SEPARATOR . $this->didox_id . '_' . $lang . '.pdf';
            if (file_exists($standardPath)) {
                return $standardPath;
            }
        }

        return null;
    }

    /**
     * Get URL to PDF
     * @param string $lang - Language code
     * @return string|null - URL or null
     */
    public function getPdfUrl($lang = 'uz')
    {
        $path = $this->getPdfPath($lang);
        if ($path) {
            return Yii::$app->request->hostInfo . '/' . $path;
        }
        return null;
    }

    /**
     * Get URL to PDF
     * @param string $lang - Language code
     * @return string|null - URL or null
     */
    public function getNewPdfUrl($lang = 'uz')
    {
        $baseUrl = Yii::$app->request->hostInfo;
        if ($this->didox_id) {
            $return = [
                'uz' => $baseUrl . '/api/didox/get-document-pdf?didox_id=' . $this->didox_id . '&lang=uz',
                'ru' => $baseUrl . '/api/didox/get-document-pdf?didox_id=' . $this->didox_id . '&lang=ru',
            ];
            $docData['pdf_cached'] = [
                'uz' => $this->hasPdf('uz'),
                'ru' => $this->hasPdf('ru'),
            ];
        } else {
            $docData['pdf_urls'] = null;
            $docData['pdf_cached'] = null;
        }
    }

    /**
     * Check if PDF exists for language
     * @param string $lang - Language code
     * @return bool
     */
    public function hasPdf($lang = 'uz')
    {
        // First check from database path
        $fullPath = $this->getPdfFullPath($lang);
        if ($fullPath && file_exists($fullPath)) {
            return true;
        }

        // Fallback: check standard path even if column doesn't exist
        if ($this->didox_id) {
            $standardPath = Yii::getAlias('@webroot') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'didox' . DIRECTORY_SEPARATOR . $this->didox_id . '_' . $lang . '.pdf';
            return file_exists($standardPath);
        }

        return false;
    }

    /**
     * Save PDF content to local file
     * @param string $content - Binary PDF content
     * @param string $lang - Language code (uz, ru, en)
     * @return bool - Success
     */
    public function savePdfLocally($content, $lang = 'uz')
    {
        if (empty($this->didox_id)) {
            return false;
        }

        // Create directory if not exists
        $uploadDir = Yii::getAlias('@webroot') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'didox';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Generate filename
        $filename = $this->didox_id . '_' . $lang . '.pdf';
        $fullPath = $uploadDir . DIRECTORY_SEPARATOR . $filename;
        $relativePath = 'uploads/didox/' . $filename;

        // Save file
        if (file_put_contents($fullPath, $content) !== false) {
            // Only update database if pdf_paths column exists
            if ($this->hasAttribute('pdf_paths')) {
                $this->setPdfPath($lang, $relativePath);
                return $this->save(false, ['pdf_paths']);
            }
            return true; // File saved but db column doesn't exist yet
        }

        return false;
    }

    /**
     * Delete local PDF file
     * @param string $lang - Language code
     * @return bool
     */
    public function deletePdfLocally($lang = 'uz')
    {
        $fullPath = $this->getPdfFullPath($lang);
        if ($fullPath && file_exists($fullPath)) {
            if (unlink($fullPath)) {
                // Only update database if pdf_paths column exists
                if ($this->hasAttribute('pdf_paths')) {
                    $paths = $this->getPdfPathsArray();
                    unset($paths[$lang]);
                    $this->setPdfPaths($paths);
                    return $this->save(false, ['pdf_paths']);
                }
                return true; // File deleted but db column doesn't exist yet
            }
        }
        return false;
    }

    /**
     * Automatically download and save PDF from Didox (silent - logs errors, doesn't throw)
     * @param array $languages - Languages to download (default: ['uz', 'ru'])
     * @return array - Result with success status per language
     */
    public function downloadPdfFromDidox($languages = ['uz', 'ru'])
    {
        $result = [];

        if (empty($this->didox_id)) {
            \app\models\Log::log('didox_pdf', "Cannot download PDF: No didox_id for document #{$this->id}", null, 'warning');
            return ['success' => false, 'error' => 'No didox_id'];
        }

        try {
            $didoxService = new \app\services\DidoxService();

            // Get user-key from DB setting
            $userKey = '';
            $sysSettings = \app\models\Settings::find()
                ->where(['type' => 'didox_eimzo_token'])
                ->one();

            if ($sysSettings && !empty($sysSettings->content)) {
                $userKey = $sysSettings->content;
            }

            foreach ($languages as $lang) {
                try {
                    // Skip if already downloaded
                    if ($this->hasPdf($lang)) {
                        $result[$lang] = ['success' => true, 'cached' => true];
                        continue;
                    }

                    $pdfResult = $didoxService->getDocumentPdf($this->didox_id, $userKey, $lang);

                    if ($pdfResult['success']) {
                        $saved = $this->savePdfLocally($pdfResult['data'], $lang);
                        $result[$lang] = ['success' => $saved];

                        if ($saved) {
                            \app\models\Log::log('didox_pdf', "PDF downloaded successfully for document #{$this->id} ({$lang})", [
                                'didox_id' => $this->didox_id,
                                'lang' => $lang
                            ], 'info');
                        }
                    } else {
                        $error = isset($pdfResult['error']) ? $pdfResult['error'] : 'Unknown error';
                        $result[$lang] = ['success' => false, 'error' => $error];

                        \app\models\Log::log('didox_pdf', "Failed to download PDF for document #{$this->id} ({$lang})", [
                            'didox_id' => $this->didox_id,
                            'lang' => $lang,
                            'error' => $error,
                            'http_code' => $pdfResult['httpCode'] ?? null
                        ], 'warning');
                    }
                } catch (\Exception $e) {
                    $result[$lang] = ['success' => false, 'error' => $e->getMessage()];
                    \app\models\Log::log('didox_pdf', "Exception downloading PDF for document #{$this->id} ({$lang})", [
                        'didox_id' => $this->didox_id,
                        'lang' => $lang,
                        'exception' => $e->getMessage()
                    ], 'error');
                }
            }
        } catch (\Exception $e) {
            \app\models\Log::log('didox_pdf', "Exception in downloadPdfFromDidox for document #{$this->id}", $e->getMessage(), 'error');
            return ['success' => false, 'error' => $e->getMessage()];
        }

        return $result;
    }

    /**
     * Extract and set DIDOX document ID from API response
     * @param array $responseData DIDOX API response data
     * @return bool Whether the document ID was extracted and set
     */
    public function extractAndSetDidoxDocumentId($responseData)
    {
        if (!is_array($responseData)) {
            return false;
        }

        // Try different possible locations for the document ID
        $documentId = null;

        // First priority: _id field (MongoDB-style ID from DIDOX)
        if (isset($responseData['_id'])) {
            $documentId = $responseData['_id'];
        }
        // Second priority: documentid field 
        elseif (isset($responseData['documentid'])) {
            $documentId = $responseData['documentid'];
        }
        // Third priority: doc_id field (sometimes returned on creation)
        elseif (isset($responseData['doc_id'])) {
            $documentId = $responseData['doc_id'];
        }
        // Fourth priority: facturaid in various nested locations
        elseif (isset($responseData['pending_document']['document_json']['facturaid'])) {
            $documentId = $responseData['pending_document']['document_json']['facturaid'];
        } elseif (isset($responseData['facturaid'])) {
            $documentId = $responseData['facturaid'];
        } elseif (isset($responseData['document_json']['facturaid'])) {
            $documentId = $responseData['document_json']['facturaid'];
        }

        if ($documentId && (!$this->didox_id || empty($this->didox_id))) {
            $this->didox_id = $documentId;
            return true;
        }

        return false;
    }

    /**
     * Can document be retried in DIDOX
     * @return bool
     */
    public function canRetryDidox()
    {
        return $this->hasDidoxErrors() && !$this->isDidoxDocument();
    }

    /**
     * Can document be sent to partner
     * @return bool
     */
    public function canBeSentToPartner()
    {
        return $this->isDidoxDocument() && $this->didox_status == self::STATUS_SIGNED;
    }

    /**
     * Get status label
     * @return string
     */
    public function getStatusLabel()
    {
        switch ($this->status) {
            case self::LOCAL_STATUS_ACTIVE:
                return 'Active';
            case self::LOCAL_STATUS_BLOCKED:
                return 'Blocked';
            case self::LOCAL_STATUS_INACTIVE:
                return 'Inactive';
            default:
                return 'Unknown';
        }
    }

    /**
     * Get status color class
     * @return string
     */
    public function getStatusColor()
    {
        switch ($this->status) {
            case self::LOCAL_STATUS_ACTIVE:
                return 'bg-green';
            case self::LOCAL_STATUS_BLOCKED:
                return 'bg-red';
            case self::LOCAL_STATUS_INACTIVE:
                return 'bg-gray';
            default:
                return 'bg-gray';
        }
    }

    /**
     * Gets query for [[CreatedBy]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCreatedBy()
    {
        return $this->hasOne(User::class, ['id' => 'created_by']);
    }

    /**
     * Gets query for [[ToUser]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getToUser()
    {
        return $this->hasOne(User::class, ['id' => 'to_user_id']);
    }

    /**
     * Gets query for [[Invoice]] details.
     *
     * @return \yii\db\ActiveQuery
     */
    public function getInvoice()
    {
        return $this->hasOne(DidoxDocumentInvoice::class, ['document_id' => 'id']);
    }

    /**
     * Gets query for [[Arbitrary]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getArbitrary()
    {
        return $this->hasOne(DidoxDocumentArbitrary::class, ['document_id' => 'id']);
    }

    /**
     * Gets query for [[Order]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOrder()
    {
        return $this->hasOne(\app\models\order\Order::class, ['id' => 'order_id']);
    }

    /**
     * Gets query for the associated included products.
     *
     * @return \yii\db\ActiveQuery
     */
    public function getIncludedProducts()
    {
        return $this->hasMany(DidoxDocumentIncludedProducts::class, ['document_id' => 'id'])
            ->orderBy(['ord_no' => SORT_ASC]);
    }

    /**
     * Gets query for [[Signatures]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getSignatures()
    {
        return $this->hasMany(DidoxDocumentSignature::class, ['didox_document_id' => 'id'])
            ->orderBy(['signed_at' => SORT_DESC]);
    }

    /**
     * @inheritdoc
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($insert) {
                $this->created_by = Yii::$app->user->id;
                $this->created_at = date('Y-m-d H:i:s');
            }
            $this->updated_at = date('Y-m-d H:i:s');
            return true;
        }
        return false;
    }

    /**
     * @inheritdoc
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        // Auto-create invoice record for invoice documents if it doesn't exist
        if ($this->isInvoice() && !$this->invoice) {
            $invoice = new DidoxDocumentInvoice();
            $invoice->document_id = $this->id;
            $invoice->save();
        }
    }

    /**
     * Fields for API output
     * @return array
     */
    public function fields()
    {
        return [
            'id',
            'name',
            'document_type',
            'didox_doc_type',
            'didox_id',
            'didox_status',
            'status',
            'order_id',
            'created_at',
            'updated_at',
            'didox_status_label' => function () {
                return $this->getDidoxStatusLabel();
            },
            'document_type_label' => function () {
                return $this->getDocumentTypeLabel();
            },
            'pdf_urls' => function () {
                if ($this->didox_id) {
                    $baseUrl = Yii::$app->request->hostInfo;
                    return [
                        'uz' => $baseUrl . '/api/didox/get-document-pdf?didox_id=' . $this->didox_id . '&lang=uz',
                        'ru' => $baseUrl . '/api/didox/get-document-pdf?didox_id=' . $this->didox_id . '&lang=ru',
                    ];
                }
                return null;
            },
            'pdf_cached' => function () {
                if ($this->didox_id) {
                    return [
                        'uz' => $this->hasPdf('uz'),
                        'ru' => $this->hasPdf('ru'),
                    ];
                }
                return null;
            },
        ];
    }

    /**
     * Check if an order is already connected to any DIDOX document
     * @param int $orderId Order ID to check
     * @param int|null $excludeDocumentId Document ID to exclude from check (useful for updates)
     * @return array|null Returns document info if connected, null if not connected
     */
    public static function isOrderConnectedToDidox($orderId, $excludeDocumentId = null)
    {
        if (!$orderId) {
            return null;
        }

        $query = self::find()
            ->where(['order_id' => $orderId]);

        if ($excludeDocumentId) {
            $query->andWhere(['!=', 'id', $excludeDocumentId]);
        }

        $document = $query->one();

        if ($document) {
            return [
                'id' => $document->id,
                'name' => $document->name,
                'document_type' => $document->document_type,
                'document_type_label' => $document->getDocumentTypeLabel(),
                'didox_id' => $document->didox_id,
                'didox_status' => $document->didox_status,
                'didox_status_label' => $document->getDidoxStatusLabel(),
                'created_at' => $document->created_at,
            ];
        }

        return null;
    }

    /**
     * Get all orders that are connected to DIDOX documents
     * @param int|null $excludeDocumentId Document ID to exclude from results
     * @return array Array of order_id => document_info
     */
    public static function getConnectedOrders($excludeDocumentId = null)
    {
        $query = self::find()
            ->where(['is not', 'order_id', null]);

        if ($excludeDocumentId) {
            $query->andWhere(['!=', 'id', $excludeDocumentId]);
        }

        $documents = $query->all();
        $connectedOrders = [];

        foreach ($documents as $document) {
            if ($document->order_id) {
                $connectedOrders[$document->order_id] = [
                    'document_id' => $document->id,
                    'document_name' => $document->name,
                    'document_type' => $document->document_type,
                    'document_type_label' => $document->getDocumentTypeLabel(),
                    'didox_id' => $document->didox_id,
                    'didox_status' => $document->didox_status,
                    'didox_status_label' => $document->getDidoxStatusLabel(),
                    'created_at' => $document->created_at,
                ];
            }
        }

        return $connectedOrders;
    }

    /**
     * Get connection status message for an order
     * @param int $orderId Order ID to check
     * @return string Status message
     */
    public static function getOrderConnectionStatus($orderId)
    {
        $connection = self::isOrderConnectedToDidox($orderId);

        if (!$connection) {
            return 'Заказ не связан с документами DIDOX';
        }

        $statusIcon = '🟡'; // Default yellow circle
        switch ($connection['didox_status']) {
            case self::STATUS_SIGNED:
                $statusIcon = '🟢'; // Green circle for signed
                break;
            case self::STATUS_REJECTED:
            case self::STATUS_CANCELED:
                $statusIcon = '🔴'; // Red circle for rejected/canceled
                break;
            case self::STATUS_WAITING_PARTNER_SIGNATURE:
            case self::STATUS_WAITING_YOUR_SIGNATURE:
                $statusIcon = '🟠'; // Orange circle for waiting
                break;
        }

        return "{$statusIcon} Связан с документом \"{$connection['name']}\" ({$connection['document_type_label']}) - {$connection['didox_status_label']}";
    }

    /**
     * Check if current user can sign this document
     * @return bool
     */
    public function canCurrentUserSign()
    {
        // Document must be connected to DIDOX
        if (!$this->isDidoxDocument()) {
            return false;
        }

        // Check status - only allow signing for specific statuses
        $allowedStatuses = [
            self::STATUS_DRAFT,
            self::STATUS_WAITING_YOUR_SIGNATURE,
            self::STATUS_WAITING_AGENT_SIGNATURE,
            self::STATUS_WAITING_AGENT_SIGNATURE_2,
        ];

        return in_array($this->didox_status, $allowedStatuses);
    }

    /**
     * Get signing status message for display
     * @return string
     */
    public function getSigningStatusMessage()
    {
        if (!$this->isDidoxDocument()) {
            return 'Документ не подключен к системе DIDOX. Подписание недоступно.';
        }

        switch ($this->didox_status) {
            case self::STATUS_WAITING_PARTNER_SIGNATURE:
                return 'Документ ожидает подписи партнера. Вы не можете подписать документ в данный момент.';

            case self::STATUS_SIGNED:
                return 'Документ уже подписан всеми сторонами.';

            case self::STATUS_REJECTED:
                return 'Документ был отклонен. Подписание недоступно.';

            case self::STATUS_DELETED:
                return 'Документ был удален из системы DIDOX.';

            case self::STATUS_CANCELED:
                return 'Документ был отменен.';

            case self::STATUS_INVALID:
                return 'Документ имеет недействительный статус.';

            case self::STATUS_SENT:
                return 'Документ уже отправлен и обработан.';

            case self::STATUS_DELIVERED:
                return 'Документ доставлен получателю.';

            case self::STATUS_SIGNED_BY_AGENT:
                return 'Документ подписан агентом. Дальнейшие подписи не требуются.';

            case self::STATUS_ACCEPTED_BY_RESPONSIBLE:
                return 'Документ принят ответственным лицом.';

            case self::STATUS_REJECTED_BY_RESPONSIBLE:
                return 'Документ отклонен ответственным лицом.';

            case self::STATUS_RETURNED_BY_RESPONSIBLE:
            case self::STATUS_RETURNED_BY_RESPONSIBLE_2:
                return 'Документ возвращен ответственным лицом для доработки.';

            case self::STATUS_DRAFT:
                return 'Документ в статусе черновика. Вы можете подписать и отправить документ.';

            case self::STATUS_WAITING_YOUR_SIGNATURE:
                return 'Документ ожидает вашей подписи. Вы можете подписать документ.';

            case self::STATUS_WAITING_AGENT_SIGNATURE:
            case self::STATUS_WAITING_AGENT_SIGNATURE_2:
                return 'Документ ожидает подписи агента. Вы можете подписать документ как агент.';

            default:
                return 'Неизвестный статус документа. Подписание может быть недоступно.';
        }
    }

    /**
     * Generate DIDOX API structure for documents
     * 
     * @return array
     */
    public function generateDidoxApiStructure()
    {
        if ($this->isInvoice() && $this->invoice) {
            return $this->generateInvoiceApiStructure();
        } elseif ($this->isArbitrary() && $this->arbitrary) {
            return $this->arbitrary->generateDidoxApiStructure();
        } else {
            return [];
        }
    }

    /**
     * Generate DIDOX API structure specifically for invoice documents
     * 
     * @return array
     */
    protected function generateInvoiceApiStructure()
    {
        if (!$this->isInvoice() || !$this->invoice) {
            return [];
        }

        $invoice = $this->invoice;
        $products = $this->includedProducts;

        // Calculate ProductList properties
        $hasCommittent = false;
        $hasLgota = false;
        $hasExcise = false;
        $hasVat = false;

        $productsArray = [];
        foreach ($products as $product) {
            if ($product->committent_tin || $product->committent_name) {
                $hasCommittent = true;
            }
            if ($product->lgota_id || $product->lgota_type) {
                $hasLgota = true;
            }
            if (!$product->without_excise && $product->excise_rate > 0) {
                $hasExcise = true;
            }
            if (!$product->without_vat && $product->vat_rate > 0) {
                $hasVat = true;
            }

            $productsArray[] = [
                'OrdNo' => $product->ord_no ?: 1,
                'LgotaId' => $product->lgota_id,
                'CommittentName' => $product->committent_name ?: '',
                'CommittentTin' => $product->committent_tin ?: '',
                'CommittentVatRegCode' => $product->committent_vat_reg_code ?: '',
                'CommittentVatRegStatus' => $product->committent_vat_reg_status ?: '',
                'Name' => $product->name ?: '',
                'CatalogCode' => $product->catalog_code ?: '',
                'CatalogName' => $product->catalog_name ?: '',
                'Marks' => $product->marks ?: '',
                'Barcode' => $product->barcode ?: '',
                'MeasureId' => $product->measure_id,
                'PackageCode' => $product->package_code ?: '',
                'PackageName' => $product->package_name ?: '',
                'Count' => (string) ($product->count ?: 1),
                'Summa' => (string) ($product->summa ?: 0),
                'DeliverySum' => number_format($product->delivery_sum ?: 0, 2, '.', ''),
                'VatRate' => (string) ($product->vat_rate ?: 12),
                'VatSum' => number_format($product->vat_sum ?: 0, 2, '.', ''),
                'ExciseRate' => $product->excise_rate ?: 0,
                'ExciseSum' => $product->excise_sum ?: 0,
                'DeliverySumWithVat' => number_format($product->delivery_sum_with_vat ?: 0, 2, '.', ''),
                'WithoutVat' => (bool) $product->without_vat,
                'WithoutExcise' => (bool) $product->without_excise,
                'LgotaType' => $product->lgota_type,
                'LgotaName' => $product->lgota_name,
                'LgotaVatSum' => $product->lgota_vat_sum ?: 0,
                'WarehouseId' => $product->warehouse_id,
                'Origin' => $product->origin ?: 4,
            ];
        }

        return [
            'Version' => 1,
            'WaybillLocalIds' => [],
            'HasMarking' => (bool) $invoice->has_marking,
            'HasRent' => (bool) $invoice->has_rent,
            'FacturaRentDoc' => null,
            'FacturaType' => $invoice->factura_type ?: 0,
            'ProductList' => [
                'HasCommittent' => $hasCommittent,
                'HasLgota' => $hasLgota,
                'Tin' => $invoice->seller_tin ?: '',
                'HasExcise' => $hasExcise,
                'HasVat' => $hasVat,
                'Products' => $productsArray,
            ],
            'FacturaDoc' => [
                'FacturaNo' => $invoice->invoice_number ?: '',
                'FacturaDate' => $invoice->invoice_date ?: '',
            ],
            'ContractDoc' => [
                'ContractNo' => $invoice->contract_number ?: '',
                'ContractDate' => $invoice->contract_date ?: '',
            ],
            'ContractId' => $invoice->contract_id ?: '',
            'LotId' => $invoice->lot_id ?: '',
            'OldFacturaDoc' => [
                'OldFacturaDate' => $invoice->old_factura_date ?: '',
                'OldFacturaNo' => $invoice->old_factura_no ?: '',
                'OldFacturaId' => $invoice->old_factura_id ?: '',
            ],
            'SellerTin' => $invoice->seller_tin ?: '',
            'Seller' => [
                'Name' => $invoice->seller_name ?: '',
                'BranchCode' => $invoice->seller_branch_code ?: '',
                'BranchName' => $invoice->seller_branch_name ?: '',
                'VatRegCode' => $invoice->seller_vat_reg_code ?: '',
                'Account' => $invoice->seller_account ?: '',
                'BankId' => $invoice->seller_bank_id ?: '',
                'Address' => $invoice->seller_address ?: '',
                'Director' => $invoice->seller_director ?: '',
                'Accountant' => $invoice->seller_accountant ?: '',
                'VatRegStatus' => $invoice->seller_vat_reg_status ?: 20,
            ],
            'ItemReleasedDoc' => [
                'ItemReleasedPinfl' => $invoice->item_released_pinfl ?: '',
                'ItemReleasedFio' => $invoice->item_released_fio ?: '',
            ],
            'BuyerTin' => $invoice->buyer_tin ?: '',
            'Buyer' => [
                'Name' => $invoice->buyer_name ?: '',
                'BranchCode' => $invoice->buyer_branch_code ?: '',
                'BranchName' => $invoice->buyer_branch_name ?: '',
                'VatRegCode' => $invoice->buyer_vat_reg_code ?: '',
                'Account' => $invoice->buyer_account ?: '',
                'BankId' => $invoice->buyer_bank_id ?: '',
                'Address' => $invoice->buyer_address ?: '',
                'Director' => $invoice->buyer_director ?: '',
                'Accountant' => $invoice->buyer_accountant ?: '',
                'VatRegStatus' => $invoice->buyer_vat_reg_status ?: 20,
            ],
            'FacturaInvestmentObjectDoc' => [
                'ObjectId' => $invoice->investment_object_id ?: '',
                'ObjectName' => $invoice->investment_object_name ?: '',
            ],
            'FacturaEmpowermentDoc' => [
                'EmpowermentNo' => $invoice->empowerment_no ?: '',
                'EmpowermentDateOfIssue' => $invoice->empowerment_date_of_issue ?: '',
                'AgentFio' => $invoice->agent_fio ?: '',
                'AgentTin' => $invoice->agent_tin ?: '',
            ],
            'ForeignCompany' => [
                'CountryId' => $invoice->foreign_country_id ?: '',
                'Name' => $invoice->foreign_company_name ?: '',
                'Address' => $invoice->foreign_company_address ?: '',
                'Bank' => $invoice->foreign_company_bank ?: '',
                'Account' => $invoice->foreign_company_account ?: '',
            ],
        ];
    }
}
