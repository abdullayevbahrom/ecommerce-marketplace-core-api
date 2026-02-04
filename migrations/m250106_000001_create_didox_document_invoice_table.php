<?php

use yii\db\Migration;

/**
 * Create DIDOX invoice document table for Счёт фактура specific fields
 * Based on DIDOX API documentation: https://api-docs.einvoice.example.com/ru/integrators-property-documents
 */
class m250106_000001_create_didox_document_invoice_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Create invoice-specific table
        $this->createTable('{{%didox_document_invoice}}', [
            'id' => $this->primaryKey(),
            'document_id' => $this->integer()->notNull()->comment('FK to didox_document'),
            
            // Basic invoice information (FacturaDoc)
            'invoice_number' => $this->string(100)->null()->comment('Номер счета-фактуры (FacturaNo)'),
            'invoice_date' => $this->date()->null()->comment('Дата счета-фактуры (FacturaDate)'),
            'factura_type' => $this->tinyInteger()->null()->defaultValue(0)->comment('Тип счета-фактуры (0-9)'),
            
            // Contract information (ContractDoc)
            'contract_number' => $this->string(100)->null()->comment('Номер договора (ContractNo)'),
            'contract_date' => $this->date()->null()->comment('Дата договора (ContractDate)'),
            'contract_id' => $this->string(100)->null()->comment('ID договора в my.soliq.uz'),
            'lot_id' => $this->string(100)->null()->comment('ID лота'),
            
            // Document flags
            'has_marking' => $this->boolean()->null()->defaultValue(false)->comment('Маркируемая продукция'),
            'has_rent' => $this->boolean()->null()->defaultValue(false)->comment('Услуги по аренде'),
            'has_committent' => $this->boolean()->null()->defaultValue(false)->comment('Трехсторонний ЭСФ'),
            'has_excise' => $this->boolean()->null()->defaultValue(false)->comment('С акцизом'),
            'has_vat' => $this->boolean()->null()->defaultValue(true)->comment('С НДС'),
            'has_lgota' => $this->boolean()->null()->defaultValue(false)->comment('Льгота по налогам'),
            
            // Buyer/Seller TINs
            'buyer_tin' => $this->string(20)->null()->comment('ИНН покупателя'),
            'seller_tin' => $this->string(20)->null()->comment('ИНН поставщика'),
            
            // Product information (Products array)
            'ikpu_code' => $this->string(17)->null()->comment('Код ИКПУ (CatalogCode)'),
            'ikpu_name' => $this->string(255)->null()->comment('Название ИКПУ (CatalogName)'),
            'package_code' => $this->string(20)->null()->comment('Код упаковки (PackageCode)'),
            'package_name' => $this->string(50)->null()->comment('Название упаковки (PackageName)'),
            'product_quantity' => $this->decimal(15,3)->null()->defaultValue(1)->comment('Количество (Count)'),
            'product_price' => $this->decimal(15,2)->null()->comment('Цена за единицу (Summa)'),
            'product_marks' => $this->text()->null()->comment('Маркировки (Marks)'),
            'product_barcode' => $this->string(100)->null()->comment('Штрих-код (Barcode)'),
            'product_origin' => $this->integer()->null()->defaultValue(4)->comment('Происхождение товара (Origin)'),
            
            // VAT and tax information
            'vat_rate' => $this->decimal(5,2)->null()->defaultValue(12)->comment('Ставка НДС (VatRate)'),
            'total_sum' => $this->decimal(15,2)->null()->comment('Стоимость поставки (DeliverySum)'),
            'total_vat_sum' => $this->decimal(15,2)->null()->comment('Сумма НДС (VatSum)'),
            'total_delivery_sum_with_vat' => $this->decimal(15,2)->null()->comment('Стоимость с НДС (DeliverySumWithVat)'),
            'without_vat' => $this->boolean()->null()->defaultValue(false)->comment('Без НДС (WithoutVat)'),
            'without_excise' => $this->boolean()->null()->defaultValue(true)->comment('Без акциза (WithoutExcise)'),
            'excise_rate' => $this->decimal(5,2)->null()->defaultValue(0)->comment('Ставка акциза (ExciseRate)'),
            'excise_sum' => $this->decimal(15,2)->null()->defaultValue(0)->comment('Сумма акциза (ExciseSum)'),
            
            // Tax benefits (lgota)
            'lgota_id' => $this->string(20)->null()->comment('Код льготы (LgotaId)'),
            'lgota_type' => $this->tinyInteger()->null()->comment('Тип льготы: 1-НДС, 2-налог с оборота (LgotaType)'),
            'lgota_name' => $this->string(255)->null()->comment('Название льготы (LgotaName)'),
            'lgota_vat_sum' => $this->decimal(15,2)->null()->defaultValue(0)->comment('Льготная сумма НДС (LgotaVatSum)'),
            'warehouse_id' => $this->string(50)->null()->comment('ID склада (WarehouseId)'),
            
            // Committent information (for three-party invoices)
            'committent_name' => $this->string(255)->null()->comment('Наименование комитента (CommittentName)'),
            'committent_tin' => $this->string(20)->null()->comment('ИНН комитента (CommittentTin)'),
            'committent_vat_reg_code' => $this->string(50)->null()->comment('Рег.код НДС комитента (CommittentVatRegCode)'),
            'committent_vat_reg_status' => $this->string(20)->null()->comment('Статус рег.кода НДС (CommittentVatRegStatus)'),
            
            // Seller detailed information (Seller object)
            'seller_name' => $this->string(255)->null()->comment('Наименование поставщика (Seller.Name)'),
            'seller_branch_code' => $this->string(20)->null()->comment('Код филиала поставщика (Seller.BranchCode)'),
            'seller_branch_name' => $this->string(255)->null()->comment('Наименование филиала (Seller.BranchName)'),
            'seller_vat_reg_code' => $this->string(50)->null()->comment('Рег.код НДС поставщика (Seller.VatRegCode)'),
            'seller_account' => $this->string(50)->null()->comment('Расчётный счёт поставщика (Seller.Account)'),
            'seller_bank_id' => $this->string(10)->null()->comment('МФО поставщика (Seller.BankId)'),
            'seller_address' => $this->text()->null()->comment('Адрес поставщика (Seller.Address)'),
            'seller_director' => $this->string(255)->null()->comment('Директор поставщика (Seller.Director)'),
            'seller_accountant' => $this->string(255)->null()->comment('Бухгалтер поставщика (Seller.Accountant)'),
            'seller_vat_reg_status' => $this->tinyInteger()->null()->defaultValue(20)->comment('Статус рег.кода НДС поставщика (Seller.VatRegStatus)'),
            
            // Buyer detailed information (Buyer object)
            'buyer_name' => $this->string(255)->null()->comment('Наименование покупателя (Buyer.Name)'),
            'buyer_branch_code' => $this->string(20)->null()->comment('Код филиала покупателя (Buyer.BranchCode)'),
            'buyer_branch_name' => $this->string(255)->null()->comment('Наименование филиала (Buyer.BranchName)'),
            'buyer_vat_reg_code' => $this->string(50)->null()->comment('Рег.код НДС покупателя (Buyer.VatRegCode)'),
            'buyer_account' => $this->string(50)->null()->comment('Расчётный счёт покупателя (Buyer.Account)'),
            'buyer_bank_id' => $this->string(10)->null()->comment('МФО покупателя (Buyer.BankId)'),
            'buyer_address' => $this->text()->null()->comment('Адрес покупателя (Buyer.Address)'),
            'buyer_director' => $this->string(255)->null()->comment('Директор покупателя (Buyer.Director)'),
            'buyer_accountant' => $this->string(255)->null()->comment('Бухгалтер покупателя (Buyer.Accountant)'),
            'buyer_vat_reg_status' => $this->tinyInteger()->null()->defaultValue(20)->comment('Статус рег.кода НДС покупателя (Buyer.VatRegStatus)'),
            
            // Old invoice reference (OldFacturaDoc)
            'old_factura_date' => $this->date()->null()->comment('Дата прошлой счета-фактуры (OldFacturaDate)'),
            'old_factura_no' => $this->string(100)->null()->comment('Номер прошлой счета-фактуры (OldFacturaNo)'),
            'old_factura_id' => $this->string(100)->null()->comment('ID прошлой счета-фактуры (OldFacturaId)'),
            
            // Item release information (ItemReleasedDoc)
            'item_released_pinfl' => $this->string(20)->null()->comment('ПИНФЛ отпустившего товары (ItemReleasedPinfl)'),
            'item_released_fio' => $this->string(255)->null()->comment('ФИО отпустившего товары (ItemReleasedFio)'),
            
            // Investment object (FacturaInvestmentObjectDoc)
            'investment_object_id' => $this->string(100)->null()->comment('ID объекта инвестиций (ObjectId)'),
            'investment_object_name' => $this->string(255)->null()->comment('Наименование объекта (ObjectName)'),
            
            // Empowerment document (FacturaEmpowermentDoc)
            'empowerment_no' => $this->string(100)->null()->comment('№ доверенности (EmpowermentNo)'),
            'empowerment_date_of_issue' => $this->date()->null()->comment('Дата доверенности (EmpowermentDateOfIssue)'),
            'agent_fio' => $this->string(255)->null()->comment('ФИО доверенного лица (AgentFio)'),
            'agent_tin' => $this->string(20)->null()->comment('ПИНФЛ доверенного лица (AgentTin)'),
            
            // Foreign company information (ForeignCompany)
            'foreign_country_id' => $this->string(10)->null()->comment('ID страны (CountryId)'),
            'foreign_company_name' => $this->string(255)->null()->comment('Название организации (Name)'),
            'foreign_company_address' => $this->text()->null()->comment('Адрес (Address)'),
            'foreign_company_bank' => $this->string(255)->null()->comment('Банк (Bank)'),
            'foreign_company_account' => $this->string(100)->null()->comment('Расчётный счёт (Account)'),
            
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ]);
        
        // Create indexes for better performance
        $this->createIndex('idx-didox_document_invoice-document_id', '{{%didox_document_invoice}}', 'document_id');
        $this->createIndex('idx-didox_document_invoice-buyer_tin', '{{%didox_document_invoice}}', 'buyer_tin');
        $this->createIndex('idx-didox_document_invoice-seller_tin', '{{%didox_document_invoice}}', 'seller_tin');
        $this->createIndex('idx-didox_document_invoice-invoice_number', '{{%didox_document_invoice}}', 'invoice_number');
        $this->createIndex('idx-didox_document_invoice-invoice_date', '{{%didox_document_invoice}}', 'invoice_date');
        $this->createIndex('idx-didox_document_invoice-contract_number', '{{%didox_document_invoice}}', 'contract_number');
        $this->createIndex('idx-didox_document_invoice-factura_type', '{{%didox_document_invoice}}', 'factura_type');
        
        // Add foreign key constraint
        $this->addForeignKey(
            'fk-didox_document_invoice-document_id',
            '{{%didox_document_invoice}}',
            'document_id',
            '{{%didox_document}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Drop foreign key
        $this->dropForeignKey('fk-didox_document_invoice-document_id', '{{%didox_document_invoice}}');
        
        // Drop indexes
        $this->dropIndex('idx-didox_document_invoice-factura_type', '{{%didox_document_invoice}}');
        $this->dropIndex('idx-didox_document_invoice-contract_number', '{{%didox_document_invoice}}');
        $this->dropIndex('idx-didox_document_invoice-invoice_date', '{{%didox_document_invoice}}');
        $this->dropIndex('idx-didox_document_invoice-invoice_number', '{{%didox_document_invoice}}');
        $this->dropIndex('idx-didox_document_invoice-seller_tin', '{{%didox_document_invoice}}');
        $this->dropIndex('idx-didox_document_invoice-buyer_tin', '{{%didox_document_invoice}}');
        $this->dropIndex('idx-didox_document_invoice-document_id', '{{%didox_document_invoice}}');
        
        // Drop table
        $this->dropTable('{{%didox_document_invoice}}');
    }
} 