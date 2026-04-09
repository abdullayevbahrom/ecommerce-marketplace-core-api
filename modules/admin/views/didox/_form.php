<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\ArrayHelper;

/* @var $this yii\web\View */
/* @var $model app\models\didox\DidoxDocument */
/* @var $form yii\widgets\ActiveForm */
/* @var $users array */
/* @var $currentAdminTin string */
/* @var $order app\models\order\Order */
$order = $order ?? null;

$isAuthenticated = Yii::$app->session->has('didox_authenticated') && Yii::$app->session->get('didox_authenticated') === true;

// Get or create invoice model
if ($model->isNewRecord) {
    $invoiceModel = new \app\models\didox\DidoxDocumentInvoice();
} else {
    $invoiceModel = $model->invoice ?: new \app\models\didox\DidoxDocumentInvoice();
    if (!$invoiceModel->document_id) {
        $invoiceModel->document_id = $model->id;
    }
}

// Get recent orders for dropdown
$recentOrders = \app\models\order\Order::find()
    ->orderBy(['date' => SORT_DESC])
    ->limit(100)
    ->all();
$orderOptions = ArrayHelper::map($recentOrders, 'id', function($order) {
    return '#' . $order->id . ' - ' . ($order->user ? $order->user->name : 'Неизвестный') . ' (' . date('d.m.Y', strtotime($order->date)) . ')';
});

// Check for existing DIDOX document connections
$connectedOrders = [];
$existingDocs = \app\models\didox\DidoxDocument::find()
    ->where(['is not', 'order_id', null])
    ->andWhere(['!=', 'id', $model->id]) // Exclude current document
    ->all();
    
foreach ($existingDocs as $doc) {
    if ($doc->order_id) {
        $connectedOrders[$doc->order_id] = $doc->getDocumentTypeLabel() . ': ' . $doc->name;
    }
}
?>

<style>
/* Ensure select2 containers don't overflow */
.select2-container-fixed {
    max-width: 100% !important;
    width: 100% !important;
}

.select2-container-fixed .select2-selection {
    max-width: 100% !important;
    overflow: hidden !important;
    text-overflow: ellipsis !important;
    white-space: nowrap !important;
}

.select2-dropdown-fixed {
    max-width: 100% !important;
    z-index: 9999 !important;
}

.select2-dropdown-fixed .select2-results__option {
    overflow: hidden !important;
    text-overflow: ellipsis !important;
    white-space: nowrap !important;
    max-width: 100% !important;
}

/* Constrain the assigned user select box */
.box.box-info {
    overflow: hidden !important;
}

.box.box-info .box-body {
    overflow: hidden !important;
}

/* Ensure form doesn't overflow horizontally */
.didox-document-form {
    overflow-x: hidden !important;
    max-width: 100% !important;
}

/* Invoice-specific fields are visible when shown */
#invoice-fields.show-invoice {
    display: block !important;
    visibility: visible !important;
}

/* Product management styling */
.product-item {
    margin-bottom: 15px;
    border: 1px solid #ddd;
}

.product-item .panel-heading {
    background-color: #f5f5f5;
    border-bottom: 1px solid #ddd;
}

.product-item .panel-title {
    font-size: 14px;
    font-weight: bold;
}

.product-item .btn-danger {
    background-color: #d9534f;
    border-color: #d43f3a;
}

.product-item .form-group {
    margin-bottom: 10px;
}

.product-item .form-control {
    font-size: 12px;
    height: 30px;
}

.product-item textarea.form-control {
    height: auto;
}

.product-item label {
    font-size: 11px;
    font-weight: bold;
    margin-bottom: 3px;
}

#products-container {
    max-height: 600px;
    overflow-y: auto;
    border: 1px solid #ddd;
    padding: 10px;
    background-color: #fafafa;
}

.panel-heading .btn {
    margin-left: 5px;
}
</style>

<div class="didox-document-form">

    <?php $form = ActiveForm::begin([
        'options' => ['class' => 'form-horizontal'],
        'fieldConfig' => [
            'template' => '<div class="col-sm-3">{label}</div><div class="col-sm-9">{input}{error}{hint}</div>',
            'labelOptions' => ['class' => 'control-label'],
        ],
    ]); ?>

    <!-- Error Summary -->
    <?php if ($model->hasErrors() || ($invoiceModel && $invoiceModel->hasErrors())): ?>
    <div class="callout callout-danger">
        <h4><i class="fa fa-exclamation-triangle"></i> Пожалуйста, исправьте следующие ошибки:</h4>
        <?= \yii\helpers\Html::errorSummary([$model, $invoiceModel], [
            'class' => 'list-unstyled',
            'style' => 'margin-bottom: 0;'
        ]) ?>
    </div>
    <?php endif; ?>

    <!-- DIDOX API Errors -->
    <?php if ($model->hasDidoxErrors()): ?>
    <?php
    $didoxErrors = json_decode($model->didox_error_data, true);
    $didoxErrorMessage = 'Не удалось синхронизировать документ с DIDOX.';
    $didoxErrorOperation = null;
    $didoxErrorTimestamp = $model->didox_last_attempt ? date('d.m.Y H:i:s', strtotime($model->didox_last_attempt)) : null;
    $didoxErrorHttpCode = null;
    $didoxErrorDetails = null;

    if (is_array($didoxErrors)) {
        $didoxErrorMessage = $didoxErrors['error_message']
            ?? $didoxErrors['error']
            ?? ($didoxErrors['didox_response']['data']['message'] ?? null)
            ?? ($didoxErrors['didox_response']['message'] ?? null)
            ?? $didoxErrorMessage;
        $didoxErrorOperation = $didoxErrors['action'] ?? $didoxErrors['operation'] ?? null;
        $didoxErrorTimestamp = $didoxErrors['timestamp'] ?? $didoxErrorTimestamp;
        $didoxErrorHttpCode = $didoxErrors['didox_http_code']
            ?? ($didoxErrors['didox_details']['http_code'] ?? null)
            ?? null;
        $didoxErrorDetails = json_encode($didoxErrors, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    } elseif (is_string($model->didox_error_data) && trim($model->didox_error_data) !== '') {
        $didoxErrorMessage = $model->didox_error_data;
        $didoxErrorDetails = $model->didox_error_data;
    }
    ?>
    <div class="callout callout-warning">
        <h4><i class="fa fa-exclamation-triangle"></i> Ошибки DIDOX API</h4>
        <p style="margin-bottom: 8px;"><?= Html::encode($didoxErrorMessage) ?></p>
        <div class="text-muted" style="font-size: 12px;">
            <?php if ($didoxErrorOperation): ?>
                <div><strong>Операция:</strong> <?= Html::encode($didoxErrorOperation) ?></div>
            <?php endif; ?>
            <?php if ($didoxErrorHttpCode): ?>
                <div><strong>HTTP код:</strong> <?= Html::encode($didoxErrorHttpCode) ?></div>
            <?php endif; ?>
            <?php if ($didoxErrorTimestamp): ?>
                <div><strong>Последняя попытка:</strong> <?= Html::encode($didoxErrorTimestamp) ?></div>
            <?php endif; ?>
        </div>
        <?php if ($didoxErrorDetails): ?>
            <details style="margin-top: 10px;">
                <summary style="cursor: pointer;">Подробности</summary>
                <pre style="margin-top: 10px; max-height: 220px; overflow-y: auto; white-space: pre-wrap; word-break: break-word; background: #fff; border: 1px solid #e5e5e5; border-radius: 4px; padding: 10px; font-size: 11px;"><?= Html::encode($didoxErrorDetails) ?></pre>
            </details>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Order Selection Section -->
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-shopping-cart"></i> Связь с заказом (опционально)</h3>
                </div>
                <div class="box-body">
                    <?php if ($order): ?>
                        <!-- Show connected order info -->
                        <div class="alert alert-success">
                            <h4><i class="fa fa-link"></i> Документ создается для заказа</h4>
                            <p>Этот счет-фактура создается на основе заказа <strong>#<?= $order->id ?></strong>.</p>
                            <p><strong>Клиент:</strong> <?= Html::encode($order->user ? $order->user->name : ($order->name . ' ' . $order->lastname)) ?></p>
                            <p><strong>Дата заказа:</strong> <?= date('d.m.Y', strtotime($order->date)) ?></p>
                        </div>
                        <?= Html::hiddenInput('selected_order_id', $order->id) ?>
                    <?php else: ?>
                        <!-- Order selection dropdown -->
                        <div>
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label class="control-label">Связать с заказом:</label>
                                    <?= Html::dropDownList('selected_order_id', $model->order_id, 
                                        ['' => 'Без привязки к заказу'] + $orderOptions, [
                                        'class' => 'form-control',
                                        'id' => 'order-selector',
                                        'onchange' => 'checkOrderConnection(this.value)'
                                    ]) ?>
                                    <p class="help-block">Выберите заказ для связи с этим счет-фактурой. Данные покупателя будут автоматически заполнены.</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="control-label">&nbsp;</label>
                                    <div>
                                        <button type="button" class="btn btn-info btn-block" onclick="loadOrderData()" id="load-order-btn" style="display: none;">
                                            <i class="fa fa-refresh"></i> Загрузить данные заказа
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Warning for connected orders -->
                        <?php if ($connectedOrders): ?>
                        <div class="alert alert-warning">
                            <h5><i class="fa fa-warning"></i> Уже связанные заказы</h5>
                            <p>Следующие заказы уже связаны с другими документами DIDOX:</p>
                            <ul style="margin-bottom: 0;">
                                <?php foreach ($connectedOrders as $orderId => $docName): ?>
                                    <li>Заказ #<?= Html::encode($orderId) ?> → <?= Html::encode($docName) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Информация о документе</h3>
                </div>
                <div class="box-body">
                    <?= $form->field($model, 'name')->textInput(['maxlength' => true, 'placeholder' => 'Введите название счет-фактуры']) ?>

                    <!-- Document Type - Hidden since we only support invoice -->
                    <?= Html::activeHiddenInput($model, 'document_type', ['value' => 'invoice']) ?>
                    
                    <!-- DIDOX Doc Type - Hidden since we only support invoice (002) -->
                    <?= Html::activeHiddenInput($model, 'didox_doc_type', ['value' => '002']) ?>
                    
                    <div class="alert alert-info">
                        <h4><i class="fa fa-info-circle"></i> Тип документа</h4>
                        <p><strong>Счет-фактура</strong> - Документ DIDOX типа 002</p>
                    </div>

                    <div class="box box-info collapsed-box" style="max-width: 100%; overflow: hidden;">
                        <div class="box-header with-border">
                            <h3 class="box-title">Назначить пользователя для документа</h3>
                            <div class="box-tools pull-right">
                                <button type="button" class="btn btn-box-tool" data-widget="collapse">
                                    <i class="fa fa-plus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="box-body" style="padding: 10px 15px;">
                            <div class="form-group" style="margin-bottom: 10px;">
                                <label class="control-label col-sm-3" style="padding-top: 7px;">Назначить пользователя для ИНН покупателя</label>
                                <div class="col-sm-9">
                                    <select class="form-control select2" id="assigned-user-select" style="width: 100%; max-width: 100%;">
                                        <option value="">Выберите пользователя для назначения...</option>
                                        <?php foreach ($users as $userId => $userLabel): ?>
                                            <option value="<?= $userId ?>"><?= Html::encode($userLabel) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="help-block" style="margin-top: 5px; font-size: 11px;">Выберите пользователя для автоматического заполнения ИНН покупателя из их E-IMZO налогового ID</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <?= $form->field($invoiceModel, 'buyer_tin', [
                                'template' => '<div class="col-sm-6">{label}</div><div class="col-sm-6">{input}{error}{hint}</div>'
                            ])->textInput([
                                'maxlength' => true, 
                                'placeholder' => 'ИНН покупателя',
                                'id' => 'buyer-tin-input',
                                'name' => 'DidoxDocumentInvoice[buyer_tin]'
                            ])->hint('Будет автоматически заполнен от назначенного пользователя, но можно редактировать') ?>
                        </div>
                        <div class="col-md-6">
                            <?= $form->field($invoiceModel, 'seller_tin', [
                                'template' => '<div class="col-sm-6">{label}</div><div class="col-sm-6">{input}{error}{hint}</div>'
                            ])->textInput([
                                'maxlength' => true, 
                                'placeholder' => 'ИНН поставщика',
                                'id' => 'seller-tin-input',
                                'name' => 'DidoxDocumentInvoice[seller_tin]',
                                'value' => $invoiceModel->seller_tin ?: $currentAdminTin
                            ])->hint('Автоматически заполнен от текущего администратора, но можно редактировать') ?>
                        </div>
                    </div>
                    
                    <!-- Invoice-specific fields (shown for invoice type) -->
                    <div id="invoice-fields" style="border: 2px solid #3c8dbc; border-radius: 5px; padding: 15px; background-color: #f9f9f9; margin-top: 20px;">
                        <div class="alert alert-info">
                            <strong>Примечание:</strong> Поля, отмеченные <span style="color: red;">*</span>, обязательны для соответствия требованиям DIDOX при создании счет-фактур.
                        </div>
                        
                        <!-- Basic Invoice Information -->
                        <div class="panel panel-primary">
                            <div class="panel-heading">
                                <h4 class="panel-title">Основная информация о счет-фактуре</h4>
                            </div>
                            <div class="panel-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <?= $form->field($invoiceModel, 'factura_type', [
                                            'template' => '<div class="col-sm-12">{label}</div><div class="col-sm-12">{input}{error}{hint}</div>'
                                        ])->dropDownList([
                                            0 => 'Стандартный (0)',
                                            1 => 'Дополнительный (1)', 
                                            2 => 'Возмещение расходов (2)',
                                            3 => 'Без оплаты (3)',
                                            4 => 'Исправленный (4)',
                                            5 => 'Исправленный (возмещение затрат) (5)',
                                            6 => 'Дополнительная (возмещение затрат) (6)',
                                            8 => 'Исправленный (без оплаты) (8)',
                                            9 => 'Дополнительный (без оплаты) (9)'
                                        ], ['prompt' => 'Выберите тип счет-фактуры', 'name' => 'DidoxDocumentInvoice[factura_type]']) ?>
                                    </div>
                                    <div class="col-md-4">
                                        <?= $form->field($invoiceModel, 'invoice_number', [
                                            'template' => '<div class="col-sm-12">{label} <span class="invoice-required" style="color: red;">*</span></div><div class="col-sm-12">{input}{error}{hint}</div>'
                                        ])->textInput(['maxlength' => true, 'placeholder' => 'Номер счет-фактуры', 'name' => 'DidoxDocumentInvoice[invoice_number]']) ?>
                                    </div>
                                    <div class="col-md-4">
                                        <?= $form->field($invoiceModel, 'invoice_date', [
                                            'template' => '<div class="col-sm-12">{label} <span class="invoice-required" style="color: red;">*</span></div><div class="col-sm-12">{input}{error}{hint}</div>'
                                        ])->textInput(['type' => 'date', 'name' => 'DidoxDocumentInvoice[invoice_date]']) ?>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-3">
                                        <?= $form->field($invoiceModel, 'has_marking', [
                                            'template' => '<div class="col-sm-12">{input} {label}{error}{hint}</div>'
                                        ])->checkbox(['name' => 'DidoxDocumentInvoice[has_marking]'])->label('Маркируемая продукция') ?>
                                    </div>
                                    <div class="col-md-3">
                                        <?= $form->field($invoiceModel, 'has_rent', [
                                            'template' => '<div class="col-sm-12">{input} {label}{error}{hint}</div>'
                                        ])->checkbox(['name' => 'DidoxDocumentInvoice[has_rent]'])->label('Услуги по аренде') ?>
                                    </div>
                                    <div class="col-md-3">
                                        <?= $form->field($invoiceModel, 'has_committent', [
                                            'template' => '<div class="col-sm-12">{input} {label}{error}{hint}</div>'
                                        ])->checkbox(['name' => 'DidoxDocumentInvoice[has_committent]'])->label('Трехсторонний ЭСФ') ?>
                                    </div>
                                    <div class="col-md-3">
                                        <?= $form->field($invoiceModel, 'has_excise', [
                                            'template' => '<div class="col-sm-12">{input} {label}{error}{hint}</div>'
                                        ])->checkbox(['name' => 'DidoxDocumentInvoice[has_excise]'])->label('С акцизом') ?>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <?= $form->field($invoiceModel, 'contract_number', [
                                            'template' => '<div class="col-sm-12">{label}</div><div class="col-sm-12">{input}{error}{hint}</div>'
                                        ])->textInput(['maxlength' => true, 'placeholder' => 'Номер договора', 'name' => 'DidoxDocumentInvoice[contract_number]']) ?>
                                    </div>
                                    <div class="col-md-6">
                                        <?= $form->field($invoiceModel, 'contract_date', [
                                            'template' => '<div class="col-sm-12">{label}</div><div class="col-sm-12">{input}{error}{hint}</div>'
                                        ])->textInput(['type' => 'date', 'name' => 'DidoxDocumentInvoice[contract_date]']) ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Products Information -->
                        <div class="panel panel-info">
                            <div class="panel-heading">
                                <h4 class="panel-title">
                                    Информация о товарах/услугах
                                    <button type="button" class="btn btn-xs btn-success pull-right" onclick="addProduct()">
                                        <i class="fa fa-plus"></i> Добавить товар
                                    </button>
                                </h4>
                            </div>
                            <div class="panel-body">
                                <div id="products-container">
                                    <!-- Products will be added here dynamically -->
                                </div>
                                
                                <!-- Template for new products (hidden) -->
                                <div id="product-template" style="display: none;" data-template="true">
                                    <div class="product-item panel panel-default">
                                        <div class="panel-heading">
                                            <h5 class="panel-title">
                                                Товар #<span class="product-number">1</span>
                                                <button type="button" class="btn btn-xs btn-danger pull-right" onclick="removeProduct(this)">
                                                    <i class="fa fa-trash"></i> Удалить
                                                </button>
                                            </h5>
                            </div>
                            <div class="panel-body">
                                <div>
                                    <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>Наименование товара <span style="color: red;">*</span></label>
                                                        <input type="text" class="form-control product-name" name="products[INDEX][name]" placeholder="Наименование товара/услуги" disabled>
                                                    </div>
                                    </div>
                                    <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>Код ИКПУ <span style="color: red;">*</span></label>
                                                        <input type="text" class="form-control product-catalog-code" name="products[INDEX][catalog_code]" placeholder="08471001001000000" disabled>
                                                    </div>
                                    </div>
                                </div>
                                
                                <div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>Название ИКПУ <span style="color: red;">*</span></label>
                                                        <input type="text" class="form-control product-catalog-name" name="products[INDEX][catalog_name]" placeholder="Название по классификатору ИКПУ" disabled>
                                                    </div>
                                    </div>
                                    <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label>Код упаковки <span style="color: red;">*</span></label>
                                                        <input type="text" class="form-control product-package-code" name="products[INDEX][package_code]" placeholder="1501886" disabled>
                                                    </div>
                                    </div>
                                    <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label>Единица измерения <span style="color: red;">*</span></label>
                                                        <input type="text" class="form-control product-package-name" name="products[INDEX][package_name]" placeholder="шт." disabled>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div>
                                                <div class="col-md-2">
                                                    <div class="form-group">
                                                        <label>Количество <span style="color: red;">*</span></label>
                                                        <input type="number" step="0.001" min="0.001" class="form-control product-count" name="products[INDEX][count]" placeholder="1" onchange="calculateProductTotals(this)" disabled>
                                                    </div>
                                                </div>
                                                <div class="col-md-2">
                                                    <div class="form-group">
                                                        <label>Цена <span style="color: red;">*</span></label>
                                                        <input type="number" step="0.01" min="0" class="form-control product-price" name="products[INDEX][summa]" placeholder="0.00" onchange="calculateProductTotals(this)" disabled>
                                                    </div>
                                                </div>
                                                <div class="col-md-2">
                                                    <div class="form-group">
                                                        <label>Ставка НДС <span style="color: red;">*</span></label>
                                                        <select class="form-control product-vat-rate" name="products[INDEX][vat_rate]" onchange="calculateProductTotals(this)" disabled>
                                                            <option value="0">0% (Без НДС)</option>
                                                            <option value="12">12%</option>
                                                            <option value="15">15%</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-2">
                                                    <div class="form-group">
                                                        <label>Происхождение <span style="color: red;">*</span></label>
                                                        <select class="form-control product-origin" name="products[INDEX][origin]" disabled>
                                                            <option value="1">Импорт</option>
                                                            <option value="2">Экспорт</option>
                                                            <option value="3">Транзит</option>
                                                            <option value="4" selected>Местное</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-2">
                                                    <div class="form-group">
                                                        <label>Стоимость поставки</label>
                                                        <input type="number" step="0.01" class="form-control product-delivery-sum" name="products[INDEX][delivery_sum]" readonly>
                                                    </div>
                                                </div>
                                                <div class="col-md-2">
                                                    <div class="form-group">
                                                        <label>Сумма НДС</label>
                                                        <input type="number" step="0.01" class="form-control product-vat-sum" name="products[INDEX][vat_sum]" readonly>
                                    </div>
                                    </div>
                                </div>
                                
                                <div>
                                    <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>Маркировки</label>
                                                        <textarea class="form-control product-marks" name="products[INDEX][marks]" rows="2" placeholder="Маркировки товара (если есть)" disabled></textarea>
                                                    </div>
                                    </div>
                                    <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>Штрих-код</label>
                                                        <input type="text" class="form-control product-barcode" name="products[INDEX][barcode]" placeholder="Штрих-код товара (если есть)" disabled>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Hidden fields for calculated values -->
                                            <input type="hidden" class="product-delivery-sum-with-vat" name="products[INDEX][delivery_sum_with_vat]">
                                            <input type="hidden" class="product-without-vat" name="products[INDEX][without_vat]">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tax Information -->
                        <div class="panel panel-warning">
                            <div class="panel-heading">
                                <h4 class="panel-title">Налоговая информация</h4>
                            </div>
                            <div class="panel-body">
                                <div class="row">

                                    <div class="col-md-4">
                                        <?= $form->field($invoiceModel, 'total_sum', [
                                            'template' => '<div class="col-sm-12">{label} <span class="invoice-required" style="color: red;">*</span></div><div class="col-sm-12">{input}{error}{hint}</div>'
                                        ])->textInput(['type' => 'number', 'step' => '0.01', 'min' => '0', 'placeholder' => 'Стоимость поставки', 'name' => 'DidoxDocumentInvoice[total_sum]']) ?>
                                    </div>
                                    <div class="col-md-4">
                                        <?= $form->field($invoiceModel, 'total_vat_sum', [
                                            'template' => '<div class="col-sm-12">{label}</div><div class="col-sm-12">{input}{error}{hint}</div>'
                                        ])->textInput(['type' => 'number', 'step' => '0.01', 'min' => '0', 'placeholder' => 'Сумма НДС (автоматически)', 'readonly' => true, 'name' => 'DidoxDocumentInvoice[total_vat_sum]']) ?>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4">
                                        <!-- VAT status calculated from products -->
                                        <div>
                                            <div class="checkbox">
                                                <label>
                                                    <input type="checkbox" id="invoice-without-vat" readonly disabled> Без НДС
                                                </label>
                                            </div>
                                            <small class="help-block">Рассчитывается автоматически</small>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <?= $form->field($invoiceModel, 'has_vat', [
                                            'template' => '<div class="col-sm-12">{input} {label}{error}{hint}</div>'
                                        ])->checkbox(['name' => 'DidoxDocumentInvoice[has_vat]', 'id' => 'invoice-has-vat', 'readonly' => true])->label('С НДС') ?>
                                    </div>
                                    <div class="col-md-4">
                                        <?= $form->field($invoiceModel, 'has_lgota', [
                                            'template' => '<div class="col-sm-12">{input} {label}{error}{hint}</div>'
                                        ])->checkbox(['name' => 'DidoxDocumentInvoice[has_lgota]'])->label('Льгота по налогам') ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Seller Information -->
                        <div class="panel panel-success">
                            <div class="panel-heading">
                                <h4 class="panel-title">Информация о поставщике</h4>
                            </div>
                            <div class="panel-body">
                                <div>
                                    <div class="col-md-6">
                                        <?= $form->field($invoiceModel, 'seller_name', [
                                            'template' => '<div class="col-sm-12">{label} <span class="invoice-required" style="color: red;">*</span></div><div class="col-sm-12">{input}{error}{hint}</div>'
                                        ])->textInput(['maxlength' => true, 'placeholder' => 'Наименование компании поставщика', 'name' => 'DidoxDocumentInvoice[seller_name]']) ?>
                                    </div>
                                    <div class="col-md-6">
                                        <?= $form->field($invoiceModel, 'seller_vat_reg_code', [
                                            'template' => '<div class="col-sm-12">{label}</div><div class="col-sm-12">{input}{error}{hint}</div>'
                                        ])->textInput(['maxlength' => true, 'placeholder' => 'Регистрационный код плательщика НДС', 'name' => 'DidoxDocumentInvoice[seller_vat_reg_code]']) ?>
                                    </div>
                                </div>
                                
                                <div>
                                    <div class="col-md-12">
                                        <?= $form->field($invoiceModel, 'seller_address', [
                                            'template' => '<div class="col-sm-12">{label} <span class="invoice-required" style="color: red;">*</span></div><div class="col-sm-12">{input}{error}{hint}</div>'
                                        ])->textarea(['rows' => 2, 'placeholder' => 'Полный адрес поставщика', 'name' => 'DidoxDocumentInvoice[seller_address]']) ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Buyer Information -->
                        <div class="panel panel-danger">
                            <div class="panel-heading">
                                <h4 class="panel-title">Информация о покупателе</h4>
                            </div>
                            <div class="panel-body">
                                <div>
                                    <div class="col-md-6">
                                        <?= $form->field($invoiceModel, 'buyer_name', [
                                            'template' => '<div class="col-sm-12">{label} <span class="invoice-required" style="color: red;">*</span></div><div class="col-sm-12">{input}{error}{hint}</div>'
                                        ])->textInput(['maxlength' => true, 'placeholder' => 'Наименование компании покупателя', 'name' => 'DidoxDocumentInvoice[buyer_name]']) ?>
                                    </div>
                                    <div class="col-md-6">
                                        <?= $form->field($invoiceModel, 'buyer_vat_reg_code', [
                                            'template' => '<div class="col-sm-12">{label}</div><div class="col-sm-12">{input}{error}{hint}</div>'
                                        ])->textInput(['maxlength' => true, 'placeholder' => 'Регистрационный код плательщика НДС', 'name' => 'DidoxDocumentInvoice[buyer_vat_reg_code]']) ?>
                                    </div>
                                </div>
                                
                                <div>
                                    <div class="col-md-12">
                                        <?= $form->field($invoiceModel, 'buyer_address', [
                                            'template' => '<div class="col-sm-12">{label} <span class="invoice-required" style="color: red;">*</span></div><div class="col-sm-12">{input}{error}{hint}</div>'
                                        ])->textarea(['rows' => 2, 'placeholder' => 'Полный адрес покупателя', 'name' => 'DidoxDocumentInvoice[buyer_address]']) ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Назначение и статус</h3>
                </div>
                <div class="box-body">
                    <?= $form->field($model, 'to_user_id', [
                        'template' => '<div class="col-sm-12">{label}</div><div class="col-sm-12">{input}{error}{hint}</div>'
                    ])->dropDownList($users, [
                        'prompt' => 'Выберите пользователя для назначения подписи...',
                        'class' => 'form-control select2'
                    ])->hint('Выберите пользователя с E-IMZO налоговым ID для назначения этого документа для подписи') ?>

                    <?= $form->field($model, 'status', [
                        'template' => '<div class="col-sm-12">{label}</div><div class="col-sm-12">{input}{error}{hint}</div>'
                    ])->dropDownList([
                        1 => 'Активный',
                        0 => 'Неактивный',
                        2 => 'Заблокированный'
                    ]) ?>

                    <?php if ($model->isDidoxDocument()): ?>
                        <div class="alert alert-info">
                            <strong>DIDOX Status:</strong><br>
                            <?= $model->getDidoxStatusLabel() ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="alert <?= $isAuthenticated ? 'alert-success' : 'alert-warning' ?>">
                        <strong>Аутентификация DIDOX:</strong><br>
                        <?php if ($isAuthenticated): ?>
                            <i class="fa fa-check"></i> Подключен к платформе DIDOX
                        <?php else: ?>
                            <i class="fa fa-warning"></i> Не аутентифицирован в DIDOX<br>
                            <small><a href="<?= Yii::$app->urlManager->createUrl(['/admin/didox/login']) ?>">Войти в DIDOX</a> для отправки документов на платформу</small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Справка по счет-фактуре</h3>
                </div>
                <div class="box-body">
                    <h5>Управление товарами:</h5>
                    <ul style="font-size: 12px;">
                        <li><strong>Добавление:</strong> Нажмите "Добавить товар" для новой позиции</li>
                        <li><strong>Удаление:</strong> Кнопка "Удалить" в заголовке товара</li>
                        <li><strong>Расчет:</strong> НДС и итоги рассчитываются автоматически</li>
                    </ul>

                    <h5>Обязательные поля товара:</h5>
                    <ul style="font-size: 12px;">
                        <li>Наименование товара</li>
                        <li>Код ИКПУ и название ИКПУ</li>
                        <li>Код упаковки и единица измерения</li>
                        <li>Количество и цена</li>
                        <li>Ставка НДС и происхождение</li>
                    </ul>

                    <h5>Автоматические расчеты:</h5>
                    <p style="font-size: 12px;">Система автоматически рассчитывает стоимость поставки, сумму НДС и общие итоги. Флажок "С НДС" устанавливается автоматически при наличии товаров с НДС.</p>

                    <h5>ИНН номера:</h5>
                    <p style="font-size: 12px;">Введите действительные ИНН для покупателя и поставщика. Поставщик заполняется автоматически от текущего администратора.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="box">
        <div class="box-footer">
            <div>
                <div class="col-md-6">
                    <?= Html::a('<i class="fa fa-arrow-left"></i> Назад', ['index'], ['class' => 'btn btn-default']) ?>
                </div>
                <div class="col-md-6 text-right">
                    <?php 
                    if ($model->isNewRecord) {
                        $buttonText = $isAuthenticated ? '<i class="fa fa-save"></i> Создать и отправить в DIDOX' : '<i class="fa fa-save"></i> Сохранить документ локально';
                        $buttonClass = $isAuthenticated ? 'btn btn-success' : 'btn btn-primary';
                    } else {
                        $buttonText = '<i class="fa fa-save"></i> Сохранить изменения';
                        $buttonClass = 'btn btn-primary';
                    }
                    ?>
                    <?= Html::submitButton($buttonText, ['class' => $buttonClass]) ?>
                </div>
            </div>
        </div>
    </div>

    <?php ActiveForm::end(); ?>

</div>

<script>
// Simple, reliable JavaScript without complex dependencies
// Legacy functions (kept for compatibility)
function setDefaultInvoiceValues() {
    // This function is now handled by addProduct()
    console.log('Default values set by product management system');
}

function calculateVAT() {
    // This function is now handled by calculateProductTotals()
    console.log('VAT calculation handled by product management system');
}

function showInvoiceFields() {
    document.getElementById('invoice-fields').style.display = 'block';
    console.log('Invoice fields shown');
}

function hideInvoiceFields() {
    document.getElementById('invoice-fields').style.display = 'none';
    console.log('Invoice fields hidden');
}

function handleDocumentTypeChange() {
    // This function is no longer needed since we only support invoices
    console.log('Document type is fixed to invoice');
}

// Product management variables
var productIndex = 0;

// Add new product
function addProduct() {
    var container = document.getElementById('products-container');
    var template = document.getElementById('product-template');
    var newProduct = template.cloneNode(true);
    
    // Update the product
    newProduct.style.display = 'block';
    newProduct.id = 'product-' + productIndex;
    
    // Update all name attributes and IDs, and add required attributes
    var inputs = newProduct.querySelectorAll('input, select, textarea');
    inputs.forEach(function(input) {
        if (input.name) {
            input.name = input.name.replace('INDEX', productIndex);
        }
        if (input.id) {
            input.id = input.id.replace('INDEX', productIndex);
        }

    // Add required attributes to essential fields and remove disabled
    if (input.classList.contains('product-name') || 
        input.classList.contains('product-catalog-code') || 
        input.classList.contains('product-catalog-name') || 
        input.classList.contains('product-package-code') || 
        input.classList.contains('product-package-name') ||
        input.classList.contains('product-count') ||
        input.classList.contains('product-price') ||
        input.classList.contains('product-vat-rate') ||
        input.classList.contains('product-origin')) {
        input.required = true;
    }
    
    // Remove disabled attribute from cloned fields
    input.disabled = false;
    
    // Add error handling for required fields
    input.addEventListener('invalid', function() {
        var productDiv = this.closest('.product-item');
        var productNumber = productDiv.querySelector('.product-number').textContent;
        var fieldName = this.getAttribute('placeholder') || this.getAttribute('class').split(' ')[1];
        
        // Highlight the product panel
        productDiv.style.borderColor = '#d73925';
        productDiv.style.boxShadow = '0 0 5px rgba(215, 57, 37, 0.5)';
        
        // Show error message
        this.setCustomValidity('Пожалуйста, заполните это поле в товаре #' + productNumber);
    });
    
    input.addEventListener('input', function() {
        if (this.validity.valid) {
            var productDiv = this.closest('.product-item');
            productDiv.style.borderColor = '';
            productDiv.style.boxShadow = '';
            this.setCustomValidity('');
        }
    });
    });
    
    // Update product number
    var productNumber = newProduct.querySelector('.product-number');
    if (productNumber) {
        productNumber.textContent = productIndex + 1;
    }
    
    // Set default values
    var catalogCode = newProduct.querySelector('.product-catalog-code');
    var catalogName = newProduct.querySelector('.product-catalog-name');
    var packageCode = newProduct.querySelector('.product-package-code');
    var packageName = newProduct.querySelector('.product-package-name');
    var count = newProduct.querySelector('.product-count');
    
    if (catalogCode && !catalogCode.value) catalogCode.value = '08471001001000000';
    if (catalogName && !catalogName.value) catalogName.value = 'Услуги';
    if (packageCode && !packageCode.value) packageCode.value = '1501886';
    if (packageName && !packageName.value) packageName.value = 'шт.';
    if (count && !count.value) count.value = '1';
    
    container.appendChild(newProduct);
    productIndex++;
    
    // Update product numbers
    updateProductNumbers();
}

// Remove product
function removeProduct(button) {
    var productItem = button.closest('.product-item');
    if (productItem) {
        productItem.remove();
        updateProductNumbers();
        updateInvoiceTotals();
        updateHasVATCheckbox();
    }
}

// Update product numbers
function updateProductNumbers() {
    var products = document.querySelectorAll('.product-item');
    products.forEach(function(product, index) {
        var numberSpan = product.querySelector('.product-number');
        if (numberSpan) {
            numberSpan.textContent = index + 1;
        }
    });
}

// Calculate product totals
function calculateProductTotals(element) {
    var productItem = element.closest('.product-item');
    if (!productItem) return;
    
    var count = parseFloat(productItem.querySelector('.product-count').value) || 0;
    var price = parseFloat(productItem.querySelector('.product-price').value) || 0;
    var vatRate = parseFloat(productItem.querySelector('.product-vat-rate').value) || 0;
    
    // Calculate delivery sum (count * price)
    var deliverySum = count * price;
    
    // Calculate VAT sum
    var vatSum = 0;
    var withoutVat = vatRate === 0;
    
    if (!withoutVat && vatRate > 0) {
        vatSum = deliverySum * (vatRate / 100);
    }
    
    // Calculate delivery sum with VAT
    var deliverySumWithVat = deliverySum + vatSum;
    
    // Update fields
    productItem.querySelector('.product-delivery-sum').value = deliverySum.toFixed(2);
    productItem.querySelector('.product-vat-sum').value = vatSum.toFixed(2);
    productItem.querySelector('.product-delivery-sum-with-vat').value = deliverySumWithVat.toFixed(2);
    productItem.querySelector('.product-without-vat').value = withoutVat ? '1' : '0';
    
    // Update invoice totals
    updateInvoiceTotals();
    updateHasVATCheckbox();
}

// Update invoice totals
function updateInvoiceTotals() {
    var totalSum = 0;
    var totalVatSum = 0;
    
    var products = document.querySelectorAll('.product-item');
    products.forEach(function(product) {
        var deliverySum = parseFloat(product.querySelector('.product-delivery-sum').value) || 0;
        var vatSum = parseFloat(product.querySelector('.product-vat-sum').value) || 0;
        
        totalSum += deliverySum;
        totalVatSum += vatSum;
    });
    
    // Update main invoice fields
    var totalSumField = document.querySelector('input[name="DidoxDocumentInvoice[total_sum]"]');
    var totalVatSumField = document.querySelector('input[name="DidoxDocumentInvoice[total_vat_sum]"]');
    
    if (totalSumField) totalSumField.value = totalSum.toFixed(2);
    if (totalVatSumField) totalVatSumField.value = totalVatSum.toFixed(2);
}

// Update Has VAT checkbox
function updateHasVATCheckbox() {
    var hasVat = false;
    var withoutVat = true;
    
    var products = document.querySelectorAll('.product-item');
    products.forEach(function(product) {
        var vatRate = parseFloat(product.querySelector('.product-vat-rate').value) || 0;
        if (vatRate > 0) {
            hasVat = true;
            withoutVat = false;
        }
    });
    
    // Update checkboxes
    var hasVatCheckbox = document.getElementById('invoice-has-vat');
    var withoutVatCheckbox = document.querySelector('#invoice-without-vat');
    
    if (hasVatCheckbox) hasVatCheckbox.checked = hasVat;
    if (withoutVatCheckbox) withoutVatCheckbox.checked = withoutVat;
}

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    console.log('Page loaded, initializing...');
    
    // Load existing products if editing, or add first product for new records
    <?php 
    $hasExistingProducts = false;
    $existingProducts = [];
    if (!$model->isNewRecord) {
        try {
            $existingProducts = $model->includedProducts;
            $hasExistingProducts = !empty($existingProducts);
        } catch (\Exception $e) {
            // Table doesn't exist yet
            $hasExistingProducts = false;
            $existingProducts = [];
        }
    }
    ?>
    <?php if ($hasExistingProducts): ?>
    // Load existing products
    <?php foreach ($existingProducts as $index => $product): ?>
    addProduct();
    const productDiv = document.querySelector('.product-item:last-child');
    productDiv.querySelector('input[name$="[name]"]').value = '<?= Html::encode($product->name) ?>';
    productDiv.querySelector('input[name$="[catalog_code]"]').value = '<?= Html::encode($product->catalog_code) ?>';
    productDiv.querySelector('input[name$="[catalog_name]"]').value = '<?= Html::encode($product->catalog_name) ?>';
    productDiv.querySelector('input[name$="[package_code]"]').value = '<?= Html::encode($product->package_code) ?>';
    productDiv.querySelector('input[name$="[package_name]"]').value = '<?= Html::encode($product->package_name) ?>';
    productDiv.querySelector('input[name$="[count]"]').value = '<?= $product->count ?>';
    productDiv.querySelector('input[name$="[summa]"]').value = '<?= $product->summa ?>';
    productDiv.querySelector('input[name$="[vat_rate]"]').value = '<?= $product->vat_rate ?>';
    productDiv.querySelector('select[name$="[origin]"]').value = '<?= $product->origin ?>';
    productDiv.querySelector('input[name$="[barcode]"]').value = '<?= Html::encode($product->barcode) ?>';
    productDiv.querySelector('textarea[name$="[marks]"]').value = '<?= Html::encode($product->marks) ?>';
    calculateProductTotals(productDiv.querySelector('input[name$="[count]"]'));
    <?php endforeach; ?>
    <?php else: ?>
    // Add first product automatically for new records
    addProduct();
    <?php endif; ?>
    
    // Set default contract date to today
    var contractDate = document.querySelector('input[name="DidoxDocumentInvoice[contract_date]"]');
    if (contractDate && !contractDate.value) {
        var today = new Date().toISOString().split('T')[0];
        contractDate.value = today;
    }
    
    // Set default invoice date to today
    var invoiceDate = document.querySelector('input[name="DidoxDocumentInvoice[invoice_date]"]');
    if (invoiceDate && !invoiceDate.value) {
        var today = new Date().toISOString().split('T')[0];
        invoiceDate.value = today;
    }
    
    // Auto-populate seller TIN
    var sellerTinField = document.getElementById('seller-tin-input');
    var currentAdminTin = '<?= $currentAdminTin ?>';
    if (sellerTinField && !sellerTinField.value && currentAdminTin) {
        sellerTinField.value = currentAdminTin;
    }
});

// jQuery code for select2 and AJAX (if jQuery is available)
if (typeof $ !== 'undefined') {
    $(document).ready(function() {
        // Initialize select2 if available
        if (typeof $.fn.select2 !== 'undefined') {
            $('.select2').select2({
                width: '100%'
            });
        }
        
        // Auto-populate TINs when user is assigned
        $('#assigned-user-select').change(function() {
            var userId = $(this).val();
            if (userId) {
                $.ajax({
                    url: '<?= Yii::$app->urlManager->createUrl(['/admin/didox/get-user-details']) ?>',
                    type: 'GET',
                    data: { id: userId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success && response.data.eimzo_tax_id) {
                            $('#buyer-tin-input').val(response.data.eimzo_tax_id);
                        }
                    }
                });
            }
        });
    });
}

// Form validation enhancement
document.addEventListener('DOMContentLoaded', function() {
    var form = document.getElementById('didox-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            var isValid = true;
            var firstErrorElement = null;
            
            // Validate all required product fields
            var productItems = document.querySelectorAll('.product-item');
            productItems.forEach(function(productDiv, index) {
                if (productDiv.style.display === 'none') return; // Skip template
                
                var requiredFields = productDiv.querySelectorAll('input[required], select[required]');
                requiredFields.forEach(function(field) {
                    if (!field.value.trim()) {
                        isValid = false;
                        
                        // Highlight the product panel
                        productDiv.style.borderColor = '#d73925';
                        productDiv.style.boxShadow = '0 0 5px rgba(215, 57, 37, 0.5)';
                        
                        // Mark first error element for scrolling
                        if (!firstErrorElement) {
                            firstErrorElement = field;
                        }
                        
                        // Add error class to field
                        field.style.borderColor = '#d73925';
                    }
                });
            });
            
            if (!isValid) {
                e.preventDefault();
                
                // Show error message
                var errorMessage = '<div class="callout callout-danger" id="form-validation-error">' +
                    '<h4><i class="fa fa-exclamation-triangle"></i> Ошибка валидации!</h4>' +
                    '<p>Пожалуйста, заполните все обязательные поля в товарах (отмечены красной рамкой).</p>' +
                    '</div>';
                
                // Remove existing error message
                var existingError = document.getElementById('form-validation-error');
                if (existingError) {
                    existingError.remove();
                }
                
                // Add error message at top of form
                var formContainer = document.querySelector('.didox-document-form');
                var firstChild = formContainer.firstElementChild;
                firstChild.insertAdjacentHTML('beforebegin', errorMessage);
                
                // Scroll to first error
                if (firstErrorElement) {
                    firstErrorElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    firstErrorElement.focus();
                }
                
                return false;
            }
        });
    }
});

// Order handling functions
var connectedOrders = <?= json_encode($connectedOrders) ?>;

function checkOrderConnection(orderId) {
    var loadBtn = document.getElementById('load-order-btn');
    
    if (orderId && connectedOrders[orderId]) {
        // Order is already connected
        alert('Внимание: Заказ #' + orderId + ' уже связан с документом "' + connectedOrders[orderId] + '".\n\nОдин заказ может быть связан только с одним документом DIDOX.');
        document.getElementById('order-selector').value = '';
        loadBtn.style.display = 'none';
        return false;
    }
    
    if (orderId) {
        loadBtn.style.display = 'block';
    } else {
        loadBtn.style.display = 'none';
    }
    
    return true;
}

function loadOrderData() {
    var orderId = document.getElementById('order-selector').value;
    if (!orderId) {
        alert('Пожалуйста, выберите заказ');
        return;
    }
    
    // Show loading state
    var loadBtn = document.getElementById('load-order-btn');
    var originalText = loadBtn.innerHTML;
    loadBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Загрузка...';
    loadBtn.disabled = true;
    
    // Make AJAX request to get order data
    var xhr = new XMLHttpRequest();
    xhr.open('GET', '/admin/order/get-order-data?id=' + orderId, true);
    xhr.setRequestHeader('Content-Type', 'application/json');
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            loadBtn.innerHTML = originalText;
            loadBtn.disabled = false;
            
            if (xhr.status === 200) {
                try {
                    var response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        fillOrderData(response.data);
                        alert('Данные заказа загружены успешно!');
                    } else {
                        alert('Ошибка: ' + (response.error || 'Не удалось загрузить данные заказа'));
                    }
                } catch (e) {
                    console.error('Response parsing error:', e);
                    alert('Ошибка обработки ответа сервера');
                }
            } else {
                alert('Ошибка сервера: ' + xhr.status);
            }
        }
    };
    
    xhr.send();
}

function fillOrderData(orderData) {
    // Fill buyer information
    if (orderData.user) {
        var buyerTinField = document.querySelector('input[name="DidoxDocumentInvoice[buyer_tin]"]');
        var buyerNameField = document.querySelector('input[name="DidoxDocumentInvoice[buyer_name]"]');
        var buyerAddressField = document.querySelector('input[name="DidoxDocumentInvoice[buyer_address]"]');
        
        if (buyerTinField && orderData.user.inn) {
            buyerTinField.value = orderData.user.inn;
        }
        
        if (buyerNameField) {
            var fullName = (orderData.user.name || '') + ' ' + (orderData.user.lastname || '');
            buyerNameField.value = fullName.trim();
        }
        
        if (buyerAddressField) {
            var address = '';
            if (orderData.user.addresses && orderData.user.addresses.length > 0) {
                address = orderData.user.addresses[0].address;
            } else {
                address = orderData.user.last_address || orderData.address || '';
            }
            buyerAddressField.value = address;
        }
    }
    
    // Set contract information
    var contractNumberField = document.querySelector('input[name="DidoxDocumentInvoice[contract_number]"]');
    if (contractNumberField) {
        contractNumberField.value = 'Заказ #' + orderData.id;
    }
    
    var contractDateField = document.querySelector('input[name="DidoxDocumentInvoice[contract_date]"]');
    if (contractDateField && orderData.date) {
        contractDateField.value = orderData.date.split(' ')[0]; // Get date part only
    }
    
    // Clear existing products and populate from order
    if (orderData.products && orderData.products.length > 0) {
        // Clear existing products
        var productsContainer = document.getElementById('products-container');
        productsContainer.innerHTML = '';
        productIndex = 0;
        
        // Add products from order
        orderData.products.forEach(function(orderProduct, index) {
            addProduct();
            var productDiv = document.querySelector('.product-item:last-child');
            
            // Fill product data
            productDiv.querySelector('input[name$="[name]"]').value = orderProduct.name || '';
            productDiv.querySelector('input[name$="[catalog_code]"]').value = orderProduct.ikpu_code || '08471001001000000';
            productDiv.querySelector('input[name$="[catalog_name]"]').value = orderProduct.ikpu_name || 'Услуги';
            productDiv.querySelector('input[name$="[package_code]"]').value = orderProduct.package_code || '1501886';
            productDiv.querySelector('input[name$="[package_name]"]').value = orderProduct.package_name || 'шт.';
            productDiv.querySelector('input[name$="[count]"]').value = orderProduct.amount || 1;
            productDiv.querySelector('input[name$="[summa]"]').value = orderProduct.unit_price || 0;
            productDiv.querySelector('select[name$="[vat_rate]"]').value = orderProduct.vat_rate || 0;
            productDiv.querySelector('select[name$="[origin]"]').value = orderProduct.origin || 4;
            productDiv.querySelector('input[name$="[barcode]"]').value = orderProduct.barcode || '';
            productDiv.querySelector('textarea[name$="[marks]"]').value = orderProduct.marks || '';
            
            // Calculate totals for this product
            calculateProductTotals(productDiv.querySelector('input[name$="[count]"]'));
        });
        
        // Update overall invoice totals
        updateInvoiceTotals();
        updateHasVATCheckbox();
        
        console.log('Loaded ' + orderData.products.length + ' products from order #' + orderData.id);
    }
}

// Initialize order selector on page load
document.addEventListener('DOMContentLoaded', function() {
    var orderSelector = document.getElementById('order-selector');
    if (orderSelector && orderSelector.value) {
        checkOrderConnection(orderSelector.value);
    }
});
</script> 
