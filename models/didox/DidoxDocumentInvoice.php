<?php

namespace app\models\didox;

use Yii;

/**
 * This is the model class for table "didox_document_invoice".
 * Contains invoice (Счёт фактура) specific fields.
 *
 * @property int $id
 * @property int $document_id FK to didox_document
 * @property string|null $invoice_number Номер счета-фактуры
 * @property string|null $invoice_date Дата счета-фактуры
 * @property int|null $factura_type Тип счета-фактуры (0-9)
 * @property string|null $contract_number Номер договора
 * @property string|null $contract_date Дата договора
 * @property string|null $contract_id ID договора в my.soliq.uz
 * @property string|null $lot_id ID лота
 * @property bool|null $has_marking Маркируемая продукция
 * @property bool|null $has_rent Услуги по аренде
 * @property bool|null $has_committent Трехсторонний ЭСФ
 * @property bool|null $has_excise С акцизом
 * @property bool|null $has_vat С НДС
 * @property bool|null $has_lgota Льгота по налогам
 * @property string|null $buyer_tin ИНН покупателя
 * @property string|null $seller_tin ИНН поставщика

 * @property float|null $total_sum Общая стоимость всех товаров
 * @property float|null $total_vat_sum Общая сумма НДС
 * @property float|null $total_delivery_sum_with_vat Общая стоимость с НДС
 * @property string|null $seller_name Наименование поставщика
 * @property string|null $seller_branch_code Код филиала поставщика
 * @property string|null $seller_branch_name Наименование филиала
 * @property string|null $seller_vat_reg_code Рег.код НДС поставщика
 * @property string|null $seller_account Расчётный счёт поставщика
 * @property string|null $seller_bank_id МФО поставщика
 * @property string|null $seller_address Адрес поставщика
 * @property string|null $seller_director Директор поставщика
 * @property string|null $seller_accountant Бухгалтер поставщика
 * @property int|null $seller_vat_reg_status Статус рег.кода НДС поставщика
 * @property string|null $buyer_name Наименование покупателя
 * @property string|null $buyer_branch_code Код филиала покупателя
 * @property string|null $buyer_branch_name Наименование филиала
 * @property string|null $buyer_vat_reg_code Рег.код НДС покупателя
 * @property string|null $buyer_account Расчётный счёт покупателя
 * @property string|null $buyer_bank_id МФО покупателя
 * @property string|null $buyer_address Адрес покупателя
 * @property string|null $buyer_director Директор покупателя
 * @property string|null $buyer_accountant Бухгалтер покупателя
 * @property int|null $buyer_vat_reg_status Статус рег.кода НДС покупателя
 * @property string|null $old_factura_date Дата прошлой счета-фактуры
 * @property string|null $old_factura_no Номер прошлой счета-фактуры
 * @property string|null $old_factura_id ID прошлой счета-фактуры
 * @property string|null $item_released_pinfl ПИНФЛ отпустившего товары
 * @property string|null $item_released_fio ФИО отпустившего товары
 * @property string|null $investment_object_id ID объекта инвестиций
 * @property string|null $investment_object_name Наименование объекта
 * @property string|null $empowerment_no № доверенности
 * @property string|null $empowerment_date_of_issue Дата доверенности
 * @property string|null $agent_fio ФИО доверенного лица
 * @property string|null $agent_tin ПИНФЛ доверенного лица
 * @property string|null $foreign_country_id ID страны
 * @property string|null $foreign_company_name Название организации
 * @property string|null $foreign_company_address Адрес
 * @property string|null $foreign_company_bank Банк
 * @property string|null $foreign_company_account Расчётный счёт
 * @property string|null $created_at
 * @property string|null $updated_at
 *
 * @property DidoxDocument $document
 */
class DidoxDocumentInvoice extends \yii\db\ActiveRecord
{
    // Invoice type constants (FacturaType)
    const FACTURA_TYPE_STANDARD = 0;                    // Стандартный
    const FACTURA_TYPE_ADDITIONAL = 1;                  // Дополнительный
    const FACTURA_TYPE_COST_REIMBURSEMENT = 2;          // Возмещение расходов
    const FACTURA_TYPE_NO_PAYMENT = 3;                  // Без оплаты
    const FACTURA_TYPE_CORRECTED = 4;                   // Исправленный
    const FACTURA_TYPE_CORRECTED_COST_REIMBURSEMENT = 5; // Исправленный (возмещение затрат)
    const FACTURA_TYPE_ADDITIONAL_COST_REIMBURSEMENT = 6; // Дополнительная (возмещение затрат)
    const FACTURA_TYPE_CORRECTED_NO_PAYMENT = 8;        // Исправленный (без оплаты)
    const FACTURA_TYPE_ADDITIONAL_NO_PAYMENT = 9;       // Дополнительный (без оплаты)

    // VAT rates
    const VAT_RATE_NONE = 0;
    const VAT_RATE_STANDARD = 12;
    const VAT_RATE_INCREASED = 15;

    // Product origin constants
    const PRODUCT_ORIGIN_IMPORT = 1;
    const PRODUCT_ORIGIN_EXPORT = 2;
    const PRODUCT_ORIGIN_REEXPORT = 3;
    const PRODUCT_ORIGIN_DOMESTIC = 4;

    // Tax benefit types
    const LGOTA_TYPE_VAT = 1;           // Льгота по НДС
    const LGOTA_TYPE_TURNOVER_TAX = 2;  // Льгота по налогу с оборота

    // VAT registration status
    const VAT_REG_STATUS_NONE = 0;            // Not VAT registered
    const VAT_REG_STATUS_TEMPORARY = 10;
    const VAT_REG_STATUS_PREVIOUS = 20;       // Previously was VAT registered
    const VAT_REG_STATUS_CURRENT = 21;        // Current VAT registered
    const VAT_REG_STATUS_PERMANENT = 21;      // Alias for CURRENT (compatibility)
    const VAT_REG_STATUS_SPECIAL = 30;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'didox_document_invoice';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['document_id'], 'required'],
            [['document_id', 'factura_type', 'seller_vat_reg_status', 'buyer_vat_reg_status'], 'integer'],
            [['invoice_date', 'contract_date', 'old_factura_date', 'empowerment_date_of_issue', 'created_at', 'updated_at'], 'safe'],
            [['has_marking', 'has_rent', 'has_committent', 'has_excise', 'has_vat', 'has_lgota'], 'boolean'],
            [['total_sum', 'total_vat_sum', 'total_delivery_sum_with_vat'], 'number'],
            [['seller_address', 'buyer_address', 'foreign_company_address'], 'string'],
            [['invoice_number', 'contract_number', 'contract_id', 'lot_id', 'old_factura_no', 'old_factura_id', 'investment_object_id', 'empowerment_no', 'foreign_company_account'], 'string', 'max' => 100],
            [['seller_name', 'seller_branch_name', 'seller_director', 'seller_accountant', 'buyer_name', 'buyer_branch_name', 'buyer_director', 'buyer_accountant', 'item_released_fio', 'investment_object_name', 'agent_fio', 'foreign_company_name', 'foreign_company_bank'], 'string', 'max' => 255],
            [['buyer_tin', 'seller_tin', 'seller_branch_code', 'buyer_branch_code', 'item_released_pinfl', 'agent_tin'], 'string', 'max' => 20],
            [['seller_vat_reg_code', 'buyer_vat_reg_code', 'seller_account', 'buyer_account'], 'string', 'max' => 50],
            [['seller_bank_id', 'buyer_bank_id', 'foreign_country_id'], 'string', 'max' => 10],
            [['document_id'], 'exist', 'skipOnError' => true, 'targetClass' => DidoxDocument::class, 'targetAttribute' => ['document_id' => 'id']],

            // Invoice specific validations
            [['buyer_tin', 'seller_tin'], 'required'],
            [['total_sum'], 'required'],
            [['total_sum'], 'number', 'min' => 0.01],
            [['invoice_number', 'invoice_date'], 'required'],
            [['seller_name', 'buyer_name'], 'required'],
            [['seller_vat_reg_code', 'buyer_vat_reg_code'], 'default', 'value' => ''],
            [['seller_vat_reg_code', 'buyer_vat_reg_code'], 'string', 'max' => 50],
            [['seller_address', 'buyer_address'], 'required'],

            // DIDOX API specific validations
            [
                ['factura_type'],
                'in',
                'range' => [
                    self::FACTURA_TYPE_STANDARD,
                    self::FACTURA_TYPE_ADDITIONAL,
                    self::FACTURA_TYPE_COST_REIMBURSEMENT,
                    self::FACTURA_TYPE_NO_PAYMENT,
                    self::FACTURA_TYPE_CORRECTED,
                    self::FACTURA_TYPE_CORRECTED_COST_REIMBURSEMENT,
                    self::FACTURA_TYPE_ADDITIONAL_COST_REIMBURSEMENT,
                    self::FACTURA_TYPE_CORRECTED_NO_PAYMENT,
                    self::FACTURA_TYPE_ADDITIONAL_NO_PAYMENT
                ]
            ],

            [
                ['seller_vat_reg_status', 'buyer_vat_reg_status'],
                'in',
                'range' => [
                    self::VAT_REG_STATUS_NONE,
                    self::VAT_REG_STATUS_TEMPORARY,
                    self::VAT_REG_STATUS_CURRENT,
                    self::VAT_REG_STATUS_SPECIAL
                ]
            ],
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
            'invoice_number' => 'Номер счет-фактуры',
            'invoice_date' => 'Дата счет-фактуры',
            'factura_type' => 'Тип счет-фактуры',
            'contract_number' => 'Номер договора',
            'contract_date' => 'Дата договора',
            'contract_id' => 'ID договора (my.soliq.uz)',
            'lot_id' => 'ID лота',
            'has_marking' => 'Есть маркированные товары',
            'has_rent' => 'Арендные услуги',
            'has_committent' => 'Трехсторонняя сделка',
            'has_excise' => 'Есть акцизный налог',
            'has_vat' => 'С НДС',
            'has_lgota' => 'Есть налоговые льготы',
            'buyer_tin' => 'ИНН покупателя',
            'seller_tin' => 'ИНН продавца',
            'total_sum' => 'Общая сумма всех товаров',
            'total_vat_sum' => 'Общая сумма НДС всех товаров',
            'total_delivery_sum_with_vat' => 'Общая стоимость с НДС всех товаров',
            'seller_name' => 'Наименование продавца',
            'seller_branch_code' => 'Код филиала продавца',
            'seller_branch_name' => 'Название филиала продавца',
            'seller_vat_reg_code' => 'Рег. код НДС продавца',
            'seller_account' => 'Расчетный счет продавца',
            'seller_bank_id' => 'МФО банка продавца',
            'seller_address' => 'Адрес продавца',
            'seller_director' => 'Директор продавца',
            'seller_accountant' => 'Главный бухгалтер продавца',
            'seller_vat_reg_status' => 'Статус НДС продавца',
            'buyer_name' => 'Наименование покупателя',
            'buyer_branch_code' => 'Код филиала покупателя',
            'buyer_branch_name' => 'Название филиала покупателя',
            'buyer_vat_reg_code' => 'Рег. код НДС покупателя',
            'buyer_account' => 'Расчетный счет покупателя',
            'buyer_bank_id' => 'МФО банка покупателя',
            'buyer_address' => 'Адрес покупателя',
            'buyer_director' => 'Директор покупателя',
            'buyer_accountant' => 'Главный бухгалтер покупателя',
            'buyer_vat_reg_status' => 'Статус НДС покупателя',
            'old_factura_date' => 'Дата предыдущей счет-фактуры',
            'old_factura_no' => 'Номер предыдущей счет-фактуры',
            'old_factura_id' => 'ID предыдущей счет-фактуры',
            'item_released_pinfl' => 'ПИНФЛ отпустившего',
            'item_released_fio' => 'ФИО отпустившего',
            'investment_object_id' => 'ID инвестиционного объекта',
            'investment_object_name' => 'Название инвестиционного объекта',
            'empowerment_no' => 'Номер доверенности',
            'empowerment_date_of_issue' => 'Дата выдачи доверенности',
            'agent_fio' => 'ФИО агента',
            'agent_tin' => 'ИНН/ПИНФЛ агента',
            'foreign_country_id' => 'ID зарубежной страны',
            'foreign_company_name' => 'Название зарубежной компании',
            'foreign_company_address' => 'Адрес зарубежной компании',
            'foreign_company_bank' => 'Банк зарубежной компании',
            'foreign_company_account' => 'Счет зарубежной компании',
            'created_at' => 'Создано',
            'updated_at' => 'Обновлено',
        ];
    }

    /**
     * Get invoice type label
     * @return string
     */
    public function getFacturaTypeLabel()
    {
        $types = [
            self::FACTURA_TYPE_STANDARD => 'Стандартный (0)',
            self::FACTURA_TYPE_ADDITIONAL => 'Дополнительный (1)',
            self::FACTURA_TYPE_COST_REIMBURSEMENT => 'Возмещение расходов (2)',
            self::FACTURA_TYPE_NO_PAYMENT => 'Без оплаты (3)',
            self::FACTURA_TYPE_CORRECTED => 'Исправленный (4)',
            self::FACTURA_TYPE_CORRECTED_COST_REIMBURSEMENT => 'Исправленный (возмещение затрат) (5)',
            self::FACTURA_TYPE_ADDITIONAL_COST_REIMBURSEMENT => 'Дополнительная (возмещение затрат) (6)',
            self::FACTURA_TYPE_CORRECTED_NO_PAYMENT => 'Исправленный (без оплаты) (8)',
            self::FACTURA_TYPE_ADDITIONAL_NO_PAYMENT => 'Дополнительный (без оплаты) (9)',
        ];

        return isset($types[$this->factura_type]) ? $types[$this->factura_type] : 'Неизвестно';
    }

    /**
     * Get product origin label
     * @return string
     */
    public function getProductOriginLabel()
    {
        $origins = [
            self::PRODUCT_ORIGIN_IMPORT => 'Импорт',
            self::PRODUCT_ORIGIN_EXPORT => 'Экспорт',
            self::PRODUCT_ORIGIN_REEXPORT => 'Реэкспорт',
            self::PRODUCT_ORIGIN_DOMESTIC => 'Внутренний',
        ];

        return isset($origins[$this->product_origin]) ? $origins[$this->product_origin] : 'Неизвестно';
    }

    /**
     * Auto-calculate VAT sum based on total sum and products
     * This is now handled by calculateTotals() method which sums from products
     */
    public function calculateVatSum()
    {
        // This method is deprecated - totals are now calculated from products
        // Use calculateTotals() instead
        $this->calculateTotals();
    }

    /**
     * Generate DIDOX API JSON structure for invoice
     * This method is deprecated - use DidoxDocument::generateDidoxApiStructure() instead
     * @return array
     */
    public function generateDidoxJson()
    {
        // Use the new method in DidoxDocument that handles multiple products
        if ($this->document) {
            return $this->document->generateDidoxApiStructure();
        }

        // Fallback for legacy compatibility (basic structure without products)
        return [
            'Version' => 1,
            'WaybillLocalIds' => [],
            'HasMarking' => (bool) $this->has_marking,
            'HasRent' => (bool) $this->has_rent,
            'FacturaRentDoc' => null,
            'FacturaType' => (int) $this->factura_type,
            'ProductList' => [
                'HasCommittent' => (bool) $this->has_committent,
                'HasLgota' => (bool) $this->has_lgota,
                'Tin' => $this->seller_tin,
                'HasExcise' => (bool) $this->has_excise,
                'HasVat' => (bool) $this->has_vat,
                'Products' => [] // Empty products array - will be populated by DidoxDocument
            ],
            'FacturaDoc' => [
                'FacturaNo' => $this->invoice_number,
                'FacturaDate' => $this->invoice_date,
            ],
            'ContractDoc' => [
                'ContractNo' => $this->contract_number,
                'ContractDate' => $this->contract_date,
            ],
            'ContractId' => $this->contract_id ?: '',
            'LotId' => $this->lot_id ?: '',
            'OldFacturaDoc' => [
                'OldFacturaDate' => $this->old_factura_date ?: '',
                'OldFacturaNo' => $this->old_factura_no ?: '',
                'OldFacturaId' => $this->old_factura_id ?: '',
            ],
            'SellerTin' => $this->seller_tin,
            'Seller' => [
                'Name' => $this->seller_name,
                'BranchCode' => $this->seller_branch_code ?: '',
                'BranchName' => $this->seller_branch_name ?: '',
                'VatRegCode' => $this->seller_vat_reg_code ?: null,
                'Account' => $this->seller_account ?: '',
                'BankId' => $this->seller_bank_id ?: '',
                'Address' => $this->seller_address,
                'Director' => $this->seller_director ?: '',
                'Accountant' => $this->seller_accountant ?: '',
                'VatRegStatus' => !empty($this->seller_vat_reg_code) ? (int) $this->seller_vat_reg_status : null,
            ],
            'ItemReleasedDoc' => [
                'ItemReleasedPinfl' => $this->item_released_pinfl ?: '',
                'ItemReleasedFio' => $this->item_released_fio ?: '',
            ],
            'BuyerTin' => $this->buyer_tin,
            'Buyer' => [
                'Name' => $this->buyer_name,
                'BranchCode' => $this->buyer_branch_code ?: '',
                'BranchName' => $this->buyer_branch_name ?: '',
                'VatRegCode' => $this->buyer_vat_reg_code ?: null,
                'Account' => $this->buyer_account ?: '',
                'BankId' => $this->buyer_bank_id ?: '',
                'Address' => $this->buyer_address,
                'Director' => $this->buyer_director ?: '',
                'Accountant' => $this->buyer_accountant ?: '',
                'VatRegStatus' => !empty($this->buyer_vat_reg_code) ? (int) $this->buyer_vat_reg_status : null,
            ],
            'FacturaInvestmentObjectDoc' => [
                'ObjectId' => $this->investment_object_id ?: '',
                'ObjectName' => $this->investment_object_name ?: '',
            ],
            'FacturaEmpowermentDoc' => [
                'EmpowermentNo' => $this->empowerment_no ?: '',
                'EmpowermentDateOfIssue' => $this->empowerment_date_of_issue ?: '',
                'AgentFio' => $this->agent_fio ?: '',
                'AgentTin' => $this->agent_tin ?: '',
            ],
            'ForeignCompany' => [
                'CountryId' => $this->foreign_country_id ?: '',
                'Name' => $this->foreign_company_name ?: '',
                'Address' => $this->foreign_company_address ?: '',
                'Bank' => $this->foreign_company_bank ?: '',
                'Account' => $this->foreign_company_account ?: '',
            ]
        ];
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
     * Gets query for the associated products through document.
     *
     * @return \yii\db\ActiveQuery
     */
    public function getProducts()
    {
        return $this->hasMany(DidoxDocumentIncludedProducts::class, ['document_id' => 'document_id'])
            ->orderBy(['ord_no' => SORT_ASC]);
    }

    /**
     * @inheritdoc
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            // Auto-calculate VAT sum
            $this->calculateVatSum();

            if ($insert) {
                $this->created_at = date('Y-m-d H:i:s');
            }
            $this->updated_at = date('Y-m-d H:i:s');

            // Set default values for required fields if not set
            if (!$this->factura_type) {
                $this->factura_type = self::FACTURA_TYPE_STANDARD;
            }

            if (!$this->seller_vat_reg_status) {
                $this->seller_vat_reg_status = self::VAT_REG_STATUS_NONE;
            }
            if (!$this->buyer_vat_reg_status) {
                $this->buyer_vat_reg_status = self::VAT_REG_STATUS_NONE;
            }

            return true;
        }
        return false;
    }

    /**
     * Calculate invoice totals from all products
     */
    public function calculateTotals()
    {
        $products = $this->products;

        $totalSum = 0;
        $totalVatSum = 0;
        $totalDeliverySumWithVat = 0;

        foreach ($products as $product) {
            $totalSum += $product->delivery_sum ?: 0;
            $totalVatSum += $product->vat_sum ?: 0;
            $totalDeliverySumWithVat += $product->delivery_sum_with_vat ?: 0;
        }

        $this->total_sum = $totalSum;
        $this->total_vat_sum = $totalVatSum;
        $this->total_delivery_sum_with_vat = $totalDeliverySumWithVat;
    }

    /**
     * Fields for API output
     * @return array
     */
    public function fields()
    {
        return [
            'id',
            'document_id',
            'invoice_number',
            'invoice_date',
            'factura_type',
            'contract_number',
            'contract_date',
            'buyer_tin',
            'seller_tin',
            'total_sum',
            'total_vat_sum',
            'total_delivery_sum_with_vat',
            'vat_rate',
            'ikpu_code',
            'ikpu_name',
            'package_code',
            'package_name',
            'product_quantity',
            'product_price',
            'seller_name',
            'buyer_name',
            'seller_vat_reg_code',
            'buyer_vat_reg_code',
            'seller_address',
            'buyer_address',
            'has_vat',
            'has_lgota',
            'has_marking',
            'has_rent',
            'has_committent',
            'has_excise',
            'has_vat',
            'has_lgota',
            'created_at',
            'updated_at',
            'factura_type_label' => function () {
                return $this->getFacturaTypeLabel();
            },
            'product_origin_label' => function () {
                return $this->getProductOriginLabel();
            },
        ];
    }
}