<?php

namespace app\models\didox;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use kartik\mpdf\Pdf;
use app\models\order\Order;

/**
 * This is the model class for table "didox_document_arbitrary".
 * Represents arbitrary contract documents for DIDOX platform.
 *
 * @property int $id
 * @property int $document_id FK to didox_document
 * @property string|null $document_no Номер документа
 * @property string|null $document_date Дата документа
 * @property string|null $document_name Наименование документа
 * @property string|null $contract_no Номер договора
 * @property string|null $contract_date Дата договора
 * @property string $seller_tin ИНН поставщика
 * @property string $seller_name Наименование поставщика
 * @property string $seller_address Адрес поставщика
 * @property string|null $seller_branch_code Код филиала поставщика
 * @property string|null $seller_branch_name Наименование филиала поставщика
 * @property string $buyer_tin ИНН/ПИНФЛ покупателя
 * @property string $buyer_name Наименование покупателя
 * @property string $buyer_address Адрес покупателя
 * @property string|null $buyer_branch_code Код филиала покупателя
 * @property string|null $buyer_branch_name Наименование филиала покупателя
 * @property string|null $pdf_file_content PDF content in base64 format
 * @property string|null $pdf_file_name Original PDF filename
 * @property int|null $pdf_file_size PDF file size in bytes
 * @property string|null $created_at
 * @property string|null $updated_at
 *
 * @property DidoxDocument $document
 * @property Order $order
 */
class DidoxDocumentArbitrary extends ActiveRecord
{
    const MAX_PDF_SIZE = 10485760; // 10MB in bytes
    
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
                'value' => function() {
                    return date('Y-m-d H:i:s');
                },
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'didox_document_arbitrary';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['document_id', 'seller_tin', 'seller_name', 'seller_address', 'buyer_tin', 'buyer_name', 'buyer_address'], 'required'],
            [['document_id', 'pdf_file_size'], 'integer'],
            [['document_date', 'contract_date', 'created_at', 'updated_at'], 'safe'],
            [['seller_address', 'buyer_address', 'pdf_file_content'], 'string'],
            [['document_no', 'contract_no'], 'string', 'max' => 100],
            [['document_name', 'seller_name', 'seller_branch_name', 'buyer_name', 'buyer_branch_name', 'pdf_file_name'], 'string', 'max' => 255],
            [['seller_tin', 'buyer_tin'], 'string', 'max' => 20],
            [['seller_branch_code', 'buyer_branch_code'], 'string', 'max' => 50],
            [['document_id'], 'exist', 'skipOnError' => true, 'targetClass' => DidoxDocument::class, 'targetAttribute' => ['document_id' => 'id']],
            
            // PDF validation
            [['pdf_file_size'], 'compare', 'compareValue' => self::MAX_PDF_SIZE, 'operator' => '<=', 'message' => 'PDF файл не должен превышать 10 МБ'],
            [['pdf_file_content'], 'validatePdfContent'],
            
            // Date validations
            [['document_date', 'contract_date'], 'date', 'format' => 'php:Y-m-d'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'document_id' => 'ID документа',
            'document_no' => 'Номер документа',
            'document_date' => 'Дата документа',
            'document_name' => 'Наименование документа',
            'contract_no' => 'Номер договора',
            'contract_date' => 'Дата договора',
            'seller_tin' => 'ИНН поставщика',
            'seller_name' => 'Наименование поставщика',
            'seller_address' => 'Адрес поставщика',
            'seller_branch_code' => 'Код филиала поставщика',
            'seller_branch_name' => 'Наименование филиала поставщика',
            'buyer_tin' => 'ИНН/ПИНФЛ покупателя',
            'buyer_name' => 'Наименование покупателя',
            'buyer_address' => 'Адрес покупателя',
            'buyer_branch_code' => 'Код филиала покупателя',
            'buyer_branch_name' => 'Наименование филиала покупателя',
            'pdf_file_content' => 'PDF файл',
            'pdf_file_name' => 'Имя PDF файла',
            'pdf_file_size' => 'Размер PDF файла',
            'created_at' => 'Создано',
            'updated_at' => 'Обновлено',
        ];
    }

    /**
     * Validate PDF content
     */
    public function validatePdfContent($attribute, $params)
    {
        if (!empty($this->$attribute)) {
            // Check if it's valid base64
            $decoded = base64_decode($this->$attribute, true);
            if ($decoded === false) {
                $this->addError($attribute, 'Некорректный формат PDF файла');
                return;
            }
            
            // Check if it starts with PDF signature
            if (!str_starts_with($decoded, '%PDF-')) {
                $this->addError($attribute, 'Файл должен быть в формате PDF');
            }
        }
    }

    /**
     * Gets query for [[Document]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getDocument()
    {
        return $this->hasOne(DidoxDocument::class, ['id' => 'document_id']);
    }

    /**
     * Gets query for the associated order through document.
     *
     * @return \yii\db\ActiveQuery
     */
    public function getOrder()
    {
        return $this->hasOne(Order::class, ['id' => 'order_id'])
            ->via('document');
    }

    /**
     * Generate contract PDF from order data
     * @param Order|null $order
     * @return string Base64 encoded PDF content
     * @throws \Exception
     */
    public function generateContractPdf($order = null)
    {
        if (!$order && $this->document && $this->document->order_id) {
            $order = $this->document->order;
        }

        // Prepare data for contract template
        $contractData = [
            'arbitrary' => $this,
            'order' => $order,
            'document' => $this->document,
        ];

        // Render contract template
        $content = Yii::$app->controller->renderPartial('@app/modules/admin/views/didox/_contract_template', $contractData);
        
        try {
            $pdf = new Pdf([
                'mode' => Pdf::MODE_UTF8,
                'format' => Pdf::FORMAT_A4,
                'orientation' => Pdf::ORIENT_PORTRAIT,
                'destination' => Pdf::DEST_STRING,
                'content' => $content,
                'cssFile' => '@vendor/kartik-v/yii2-mpdf/src/assets/kv-mpdf-bootstrap.min.css',
                'cssInline' => '
                    .contract-header { text-align: center; margin-bottom: 30px; }
                    .contract-title { font-size: 18px; font-weight: bold; }
                    .contract-section { margin: 20px 0; }
                    .contract-table { width: 100%; border-collapse: collapse; }
                    .contract-table td, .contract-table th { border: 1px solid #333; padding: 8px; }
                    .signature-section { margin-top: 50px; }
                    .signature-box { width: 200px; height: 80px; border: 1px solid #333; display: inline-block; margin: 10px; }
                ',
                'options' => [
                    'title' => $this->document_name ?: 'Произвольный договор',
                    'author' => $this->seller_name,
                    'subject' => 'Договор № ' . ($this->contract_no ?: $this->document_no),
                ],
                'methods' => [
                    'SetHeader' => [$this->seller_name],
                    'SetFooter' => ['Страница {PAGENO} из {nb}'],
                ]
            ]);

            $pdfContent = $pdf->render();
            $base64Content = base64_encode($pdfContent);
            
            // Update model with PDF data
            $this->pdf_file_content = $base64Content;
            $this->pdf_file_size = strlen($pdfContent);
            $this->pdf_file_name = ($this->document_name ?: 'contract') . '.pdf';

            return $base64Content;
            
        } catch (\Exception $e) {
            Yii::error("PDF generation failed: " . $e->getMessage(), __METHOD__);
            throw new \Exception('Не удалось сгенерировать PDF: ' . $e->getMessage());
        }
    }

    /**
     * Auto-populate data from order
     * @param Order $order
     * @param array $adminData Current admin/company data
     */
    public function populateFromOrder($order, $adminData = [])
    {
        // Document info
        $this->document_no = 'DOC-' . $order->id . '-' . date('Y');
        $this->document_date = date('Y-m-d');
        $this->document_name = 'Договор по заказу №' . $order->id;
        
        // Contract info
        $this->contract_no = 'CONTRACT-' . $order->id;
        $this->contract_date = date('Y-m-d', strtotime($order->date));
        
        // Seller info (from admin/company data)
        $this->seller_tin = $adminData['tin'] ?? '';
        $this->seller_name = $adminData['name'] ?? '';
        $this->seller_address = $adminData['address'] ?? '';
        $this->seller_branch_code = $adminData['branch_code'] ?? '';
        $this->seller_branch_name = $adminData['branch_name'] ?? '';
        
        // Buyer info
        
        // 1. TIN
        if (!empty($adminData['buyer_tin'])) {
            $this->buyer_tin = $adminData['buyer_tin'];
        } elseif ($order->inn) {
            $this->buyer_tin = $order->inn;
        } elseif ($order->user) {
            $this->buyer_tin = $order->user->inn ?? $order->user->phone ?? '';
        } else {
            $this->buyer_tin = $order->phone ?? '';
        }

        // 2. Name
        if (!empty($adminData['buyer_name'])) {
            $this->buyer_name = $adminData['buyer_name'];
        } else {
            if ($order->user) {
            $fullName = array_filter([
                $order->user->name,
                $order->user->lastname, 
                $order->user->middlename
            ]);
            $this->buyer_name = implode(' ', $fullName) ?: $order->user->organization_name ?: 'Клиент';
            } else {
                $this->buyer_name = trim(($order->name ?? '') . ' ' . ($order->lastname ?? '')) ?: 'Клиент';
            }
        }
            
        // 3. Address
        if (!empty($adminData['buyer_address'])) {
            $this->buyer_address = $adminData['buyer_address'];
        } else {
            $address = '';
            if ($order->user && $order->user->addresses) {
                $defaultAddress = $order->user->addresses[0] ?? null;
                if ($defaultAddress) {
                    $address = $defaultAddress->address;
                }
            }
            if (empty($address) && $order->user) {
                $address = $order->user->last_address;
            }
            if (empty($address)) {
                $address = $order->address ?: 'Не указан';
            }
            $this->buyer_address = $address;
        }
    }

    /**
     * Generate DIDOX API JSON structure for arbitrary document
     * @return array
     */
    public function generateDidoxApiStructure()
    {
        // Ensure PDF is generated
        if (empty($this->pdf_file_content)) {
            $this->generateContractPdf();
        }

        return [
            'data' => [
                'Document' => [
                    'DocumentNo' => $this->document_no,
                    'DocumentDate' => $this->document_date,
                    'DocumentName' => $this->document_name,
                ],
                'ContractDoc' => [
                    'ContractNo' => $this->contract_no,
                    'ContractDate' => $this->contract_date,
                ],
                'SellerTin' => $this->seller_tin,
                'Seller' => [
                    'Name' => $this->seller_name,
                    'BranchCode' => $this->seller_branch_code ?: '',
                    'BranchName' => $this->seller_branch_name ?: '',
                    'Address' => $this->seller_address,
                ],
                'BuyerTin' => $this->buyer_tin,
                'Buyer' => [
                    'Name' => $this->buyer_name,
                    'Address' => $this->buyer_address,
                    'BranchCode' => $this->buyer_branch_code ?: '',
                    'BranchName' => $this->buyer_branch_name ?: '',
                ],
            ],
            'document' => 'data:application/pdf;base64,' . $this->pdf_file_content,
        ];
    }

    /**
     * Before save event
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            // Set default dates
            if (empty($this->document_date)) {
                $this->document_date = date('Y-m-d');
            }
            if (empty($this->contract_date)) {
                $this->contract_date = $this->document_date;
            }
            
            return true;
        }
        return false;
    }
    
    /**
     * Helper method to get product name (prioritizes Russian, then English, then Uzbek)
     * @param Product|null $product
     * @return string
     */
    public static function getProductName($product)
    {
        if (!$product) {
            return 'Товар';
        }
        
        return $product->name_ru ?: $product->name_en ?: $product->name_uz ?: 'Товар';
    }
    
    /**
     * Helper method to get order status label
     * @param \app\models\order\Order|null $order
     * @return string
     */
    public static function getOrderStatusLabel($order)
    {
        if (!$order) {
            return 'Неизвестно';
        }
        
        // Map order status values to labels
        $statusLabels = [
            0 => 'Новый',
            1 => 'Подтвержден',
            2 => 'В обработке',
            3 => 'Отправлен',
            4 => 'Доставлен',
            5 => 'Завершен',
            6 => 'Отменен',
            7 => 'Возврат',
        ];
        
        return $statusLabels[$order->status] ?? 'Статус ' . $order->status;
    }
    
    /**
     * Helper method to get order payment status label
     * @param \app\models\order\Order|null $order
     * @return string
     */
    public static function getOrderPaymentStatusLabel($order)
    {
        if (!$order) {
            return 'Неизвестно';
        }
        
        // Map payment status values to labels
        $paymentStatusLabels = [
            0 => 'Ожидает оплаты',
            1 => 'Не оплачен',
            2 => 'Оплачен',
            3 => 'Частично оплачен',
            4 => 'Возврат',
        ];
        
        return $paymentStatusLabels[$order->status_payment] ?? 'Статус оплаты ' . $order->status_payment;
    }
    
    /**
     * Helper method to get order total amount
     * @param \app\models\order\Order|null $order
     * @return float
     */
    public static function getOrderTotalAmount($order)
    {
        if (!$order) {
            return 0;
        }
        
        // Use amount if available (includes delivery), otherwise use price
        return $order->amount ?: $order->price ?: 0;
    }
    
    /**
     * Helper method to get delivery name/label
     * @param \app\models\delivery\Delivery|null $delivery
     * @return string
     */
    public static function getDeliveryName($delivery)
    {
        if (!$delivery) {
            return 'Доставка BTS';
        }
        
        // Try common property names for delivery name
        if (isset($delivery->name)) {
            return $delivery->name;
        } elseif (isset($delivery->title)) {
            return $delivery->title;
        } elseif (isset($delivery->name_ru)) {
            return $delivery->name_ru;
        } elseif (isset($delivery->label)) {
            return $delivery->label;
        }
        
        // Default to BTS delivery
        return 'Доставка BTS';
    }
} 