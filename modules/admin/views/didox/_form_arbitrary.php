<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model app\models\didox\DidoxDocument */
/* @var $arbitraryModel app\models\didox\DidoxDocumentArbitrary */
/* @var $order app\models\order\Order */
/* @var $users array */
/* @var $currentAdminTin string */

$isAuthenticated = Yii::$app->session->has('didox_authenticated') && Yii::$app->session->get('didox_authenticated') === true;

// Get recent orders for dropdown
$recentOrders = \app\models\order\Order::find()
    ->orderBy(['date' => SORT_DESC])
    ->limit(100)
    ->all();
$orderOptions = ArrayHelper::map($recentOrders, 'id', function($order) {
    return '#' . $order->id . ' - ' . ($order->user ? $order->user->name : 'Неизвестный') . ' (' . date('d.m.Y', strtotime($order->date)) . ')';
});
?>

<div class="didox-arbitrary-form">

    <?php $form = ActiveForm::begin([
        'options' => ['class' => 'form-horizontal'],
        'fieldConfig' => [
            'template' => '<div class="col-sm-3">{label}</div><div class="col-sm-9">{input}{error}{hint}</div>',
            'labelOptions' => ['class' => 'control-label'],
        ],
    ]); ?>

    <!-- Error Summary -->
    <?php if ($model->hasErrors() || ($arbitraryModel && $arbitraryModel->hasErrors())): ?>
    <div class="callout callout-danger">
        <h4><i class="fa fa-exclamation-triangle"></i> Пожалуйста, исправьте следующие ошибки:</h4>
        <?= \yii\helpers\Html::errorSummary([$model, $arbitraryModel], [
            'class' => 'list-unstyled',
            'style' => 'margin-bottom: 0;'
        ]) ?>
    </div>
    <?php endif; ?>

    <!-- DIDOX API Errors -->
    <?php if ($model->hasDidoxErrors()): ?>
    <div class="callout callout-warning">
        <h4><i class="fa fa-exclamation-triangle"></i> Ошибки DIDOX API</h4>
        <p>При последней попытке отправки документа в DIDOX произошли ошибки:</p>
        <?php 
        $didoxErrors = json_decode($model->didox_error_data, true);
        if (is_array($didoxErrors)): ?>
            <ul class="list-unstyled" style="margin-bottom: 0;">
            <?php foreach ($didoxErrors as $error): ?>
                <li><i class="fa fa-circle-o"></i> 
                <?php if (is_array($error)): ?>
                    <?= Html::encode($error['message'] ?? $error['error'] ?? json_encode($error)) ?>
                <?php else: ?>
                    <?= Html::encode($error) ?>
                <?php endif; ?>
                </li>
            <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p style="margin-bottom: 0;"><?= Html::encode($model->didox_error_data) ?></p>
        <?php endif; ?>
        <?php if ($model->didox_last_attempt): ?>
            <p><small class="text-muted">Последняя попытка: <?= date('d.m.Y H:i:s', strtotime($model->didox_last_attempt)) ?></small></p>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Order Selection -->
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-shopping-cart"></i> Выбор заказа (опционально)</h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label class="control-label">Связать с заказом:</label>
                                <?= Html::dropDownList('selected_order_id', $order ? $order->id : null, 
                                    ['' => 'Без привязки к заказу'] + $orderOptions, [
                                    'class' => 'form-control',
                                    'id' => 'order-selector',
                                    'onchange' => 'selectOrder(this.value)'
                                ]) ?>
                                <p class="help-block">Выберите заказ для включения в предварительный просмотр договора.</p>
                            </div>
                        </div>
                        <!-- <div class="col-md-4">
                            <div class="form-group">
                                <label class="control-label">&nbsp;</label>
                                <div>
                                    <button type="button" class="btn btn-info btn-block" onclick="togglePreview()" id="preview-toggle" style="display: none;">
                                        <i class="fa fa-eye"></i> Показать предварительный просмотр
                                    </button>
                                </div>
                            </div>
                        </div> -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- PDF Preview Section -->
    <div class="row" id="pdf-preview-section" style="display: none;">
        <div class="col-md-12">
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-file-pdf-o"></i> Предварительный просмотр договора</h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-box-tool" onclick="togglePreview()">
                            <i class="fa fa-times"></i>
                        </button>
                    </div>
                </div>
                <div class="box-body">
                    <div id="contract-preview" style="border: 1px solid #ddd; padding: 20px; background: white; font-family: 'Times New Roman', serif; line-height: 1.4; min-height: 200px;">
                        <div style="text-align: center; padding: 50px; color: #666;">
                            <i class="fa fa-file-text-o" style="font-size: 48px; margin-bottom: 20px;"></i>
                            <p>Нажмите "Показать предварительный просмотр" для генерации договора</p>
                        </div>
                    </div>
                    
                    <!-- Loading indicator for preview -->
                    <div id="preview-loading" style="display: none; text-align: center; padding: 20px;">
                        <i class="fa fa-spinner fa-spin"></i> Генерация предварительного просмотра...
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Document Information -->
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-file-text"></i> Информация о документе</h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-6">
                            <?= $form->field($model, 'name', [
                                'template' => '<div class="col-sm-12">{label}</div><div class="col-sm-12">{input}{error}{hint}</div>'
                            ])->textInput(['maxlength' => true, 'placeholder' => 'Название документа', 'onchange' => 'updatePreview()']) ?>
                        </div>
                        <div class="col-md-6">
                            <?= $form->field($arbitraryModel, 'document_no', [
                                'template' => '<div class="col-sm-12">{label}</div><div class="col-sm-12">{input}{error}{hint}</div>'
                            ])->textInput(['maxlength' => true, 'placeholder' => 'DOC-001-2025', 'onchange' => 'updatePreview()']) ?>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <?= $form->field($arbitraryModel, 'document_name', [
                                'template' => '<div class="col-sm-12">{label}</div><div class="col-sm-12">{input}{error}{hint}</div>'
                            ])->textInput(['maxlength' => true, 'placeholder' => 'Договор оказания услуг', 'onchange' => 'updatePreview()']) ?>
                        </div>
                        <div class="col-md-6">
                            <?= $form->field($arbitraryModel, 'document_date', [
                                'template' => '<div class="col-sm-12">{label}</div><div class="col-sm-12">{input}{error}{hint}</div>'
                            ])->input('date', ['onchange' => 'updatePreview()']) ?>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <?= $form->field($arbitraryModel, 'contract_no', [
                                'template' => '<div class="col-sm-12">{label}</div><div class="col-sm-12">{input}{error}{hint}</div>'
                            ])->textInput(['maxlength' => true, 'placeholder' => 'CONTRACT-001-2025', 'onchange' => 'updatePreview()']) ?>
                        </div>
                        <div class="col-md-6">
                            <?= $form->field($arbitraryModel, 'contract_date', [
                                'template' => '<div class="col-sm-12">{label}</div><div class="col-sm-12">{input}{error}{hint}</div>'
                            ])->input('date', ['onchange' => 'updatePreview()']) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Seller Information -->
        <div class="col-md-6">
            <div class="box box-success">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-building"></i> Поставщик (Ваша компания)</h3>
                </div>
                <div class="box-body">
                    <?= $form->field($arbitraryModel, 'seller_tin', [
                        'template' => '<div class="col-sm-12">{label}</div><div class="col-sm-12">{input}{error}{hint}</div>'
                    ])->textInput(['maxlength' => true, 'placeholder' => 'ИНН поставщика', 'onchange' => 'updatePreview()']) ?>

                    <?= $form->field($arbitraryModel, 'seller_name', [
                        'template' => '<div class="col-sm-12">{label}</div><div class="col-sm-12">{input}{error}{hint}</div>'
                    ])->textInput(['maxlength' => true, 'placeholder' => 'Наименование организации', 'onchange' => 'updatePreview()']) ?>

                    <?= $form->field($arbitraryModel, 'seller_address', [
                        'template' => '<div class="col-sm-12">{label}</div><div class="col-sm-12">{input}{error}{hint}</div>'
                    ])->textarea(['rows' => 3, 'placeholder' => 'Полный адрес организации', 'onchange' => 'updatePreview()']) ?>

                    <div class="row">
                        <div class="col-md-6">
                            <?= $form->field($arbitraryModel, 'seller_branch_code', [
                                'template' => '<div class="col-sm-12">{label}</div><div class="col-sm-12">{input}{error}{hint}</div>'
                            ])->textInput(['maxlength' => true, 'placeholder' => 'Код филиала (опционально)', 'onchange' => 'updatePreview()']) ?>
                        </div>
                        <div class="col-md-6">
                            <?= $form->field($arbitraryModel, 'seller_branch_name', [
                                'template' => '<div class="col-sm-12">{label}</div><div class="col-sm-12">{input}{error}{hint}</div>'
                            ])->textInput(['maxlength' => true, 'placeholder' => 'Название филиала (опционально)', 'onchange' => 'updatePreview()']) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Buyer Information -->
        <div class="col-md-6">
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-user"></i> Покупатель (Заказчик)</h3>
                    <?php if ($order): ?>
                        <div class="box-tools pull-right">
                            <span class="label label-info">Из заказа №<?= $order->id ?></span>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="box-body">
                    <?= $form->field($arbitraryModel, 'buyer_tin', [
                        'template' => '<div class="col-sm-12">{label}</div><div class="col-sm-12">{input}{error}{hint}</div>'
                    ])->textInput(['maxlength' => true, 'placeholder' => 'ИНН/ПИНФЛ покупателя', 'onchange' => 'updatePreview()']) ?>

                    <?= $form->field($arbitraryModel, 'buyer_name', [
                        'template' => '<div class="col-sm-12">{label}</div><div class="col-sm-12">{input}{error}{hint}</div>'
                    ])->textInput(['maxlength' => true, 'placeholder' => 'ФИО или наименование организации', 'onchange' => 'updatePreview()']) ?>

                    <?= $form->field($arbitraryModel, 'buyer_address', [
                        'template' => '<div class="col-sm-12">{label}</div><div class="col-sm-12">{input}{error}{hint}</div>'
                    ])->textarea(['rows' => 3, 'placeholder' => 'Полный адрес покупателя', 'onchange' => 'updatePreview()']) ?>

                    <div class="row">
                        <div class="col-md-6">
                            <?= $form->field($arbitraryModel, 'buyer_branch_code', [
                                'template' => '<div class="col-sm-12">{label}</div><div class="col-sm-12">{input}{error}{hint}</div>'
                            ])->textInput(['maxlength' => true, 'placeholder' => 'Код филиала (опционально)', 'onchange' => 'updatePreview()']) ?>
                        </div>
                        <div class="col-md-6">
                            <?= $form->field($arbitraryModel, 'buyer_branch_name', [
                                'template' => '<div class="col-sm-12">{label}</div><div class="col-sm-12">{input}{error}{hint}</div>'
                            ])->textInput(['maxlength' => true, 'placeholder' => 'Название филиала (опционально)', 'onchange' => 'updatePreview()']) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($order): ?>
    <!-- Order Information (if linked to order) -->
    <div class="row">
        <div class="col-md-12">
            <div class="box box-warning">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-shopping-cart"></i> Информация о заказе</h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-3">
                            <strong>Номер заказа:</strong> #<?= $order->id ?>
                        </div>
                        <div class="col-md-3">
                            <strong>Дата заказа:</strong> <?= date('d.m.Y H:i', strtotime($order->date)) ?>
                        </div>
                        <div class="col-md-3">
                            <strong>Статус:</strong> <?= Html::encode(\app\models\didox\DidoxDocumentArbitrary::getOrderStatusLabel($order)) ?>
                        </div>
                        <div class="col-md-3">
                            <strong>Сумма:</strong> <?= number_format(\app\models\didox\DidoxDocumentArbitrary::getOrderTotalAmount($order), 2) ?> сум
                        </div>
                    </div>
                    
                    <?php if ($order->orderProducts): ?>
                    <div style="margin-top: 15px;">
                        <strong>Товары/услуги:</strong>
                        <ul style="margin-top: 5px;">
                            <?php foreach ($order->orderProducts as $orderProduct): ?>
                                <li><?= Html::encode(\app\models\didox\DidoxDocumentArbitrary::getProductName($orderProduct->product)) ?> 
                                    (<?= $orderProduct->amount ?> шт. × <?= number_format($orderProduct->price, 2) ?> сум)</li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Authentication Status -->
    <div class="row">
        <div class="col-md-12">
            <div class="box <?= $isAuthenticated ? 'box-success' : 'box-warning' ?>">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa <?= $isAuthenticated ? 'fa-check-circle' : 'fa-exclamation-triangle' ?>"></i>
                        Статус DIDOX
                    </h3>
                </div>
                <div class="box-body">
                    <div class="alert <?= $isAuthenticated ? 'alert-success' : 'alert-warning' ?>">
                        <?php if ($isAuthenticated): ?>
                            <i class="fa fa-check"></i> Вы аутентифицированы в DIDOX. Договор будет автоматически отправлен на платформу после создания.
                        <?php else: ?>
                            <i class="fa fa-warning"></i> Вы не аутентифицированы в DIDOX. Договор будет сохранен локально. 
                            <a href="<?= \yii\helpers\Url::to(['login']) ?>">Войти в DIDOX</a> для отправки на платформу.
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Assignment and Status -->
    <div class="row">
        <div class="col-md-12">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-user-circle"></i> Назначение и статус</h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-6">
                            <?= $form->field($model, 'to_user_id', [
                                'template' => '<div class="col-sm-12">{label}</div><div class="col-sm-12">{input}{error}{hint}</div>'
                            ])->dropDownList($users, [
                                'prompt' => 'Выберите пользователя для назначения подписи...',
                                'class' => 'form-control select2'
                            ])->hint('Выберите пользователя с E-IMZO налоговым ID для назначения этого документа для подписи') ?>
                        </div>
                        <div class="col-md-6">
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
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Submit Buttons -->
    <div class="row">
        <div class="col-md-12">
            <div class="box">
                <div class="box-body">
                    <div style="text-align: center;">
                        <?php
                        $buttonText = $model->isNewRecord ? 'Создать договор' : 'Обновить договор';
                        $buttonClass = $model->isNewRecord ? 'btn btn-success btn-lg' : 'btn btn-primary btn-lg';
                        ?>
                        <?= Html::submitButton($buttonText, ['class' => $buttonClass]) ?>
                        <?= Html::a('Отмена', ['index'], ['class' => 'btn btn-default btn-lg']) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php ActiveForm::end(); ?>

</div>

<script>
// Global variables
var selectedOrderId = null;
var previewVisible = false;

// Auto-fill current date if empty
document.addEventListener('DOMContentLoaded', function() {
    var documentDate = document.querySelector('input[name="DidoxDocumentArbitrary[document_date]"]');
    var contractDate = document.querySelector('input[name="DidoxDocumentArbitrary[contract_date]"]');
    
    if (documentDate && !documentDate.value) {
        documentDate.value = new Date().toISOString().split('T')[0];
    }
    
    if (contractDate && !contractDate.value) {
        contractDate.value = new Date().toISOString().split('T')[0];
    }
    
    // Auto-populate admin TIN
    var sellerTin = document.querySelector('input[name="DidoxDocumentArbitrary[seller_tin]"]');
    if (sellerTin && !sellerTin.value && '<?= $currentAdminTin ?>') {
        sellerTin.value = '<?= $currentAdminTin ?>';
    }
    
    // If order is already selected, show preview button
    var orderSelector = document.getElementById('order-selector');
    if (orderSelector && orderSelector.value) {
        selectedOrderId = orderSelector.value;
        document.getElementById('preview-toggle').style.display = 'block';
    }
});

// Select order (no loading, just for preview)
function selectOrder(orderId) {
    selectedOrderId = orderId;
    
    if (orderId) {
        document.getElementById('preview-toggle').style.display = 'block';
    } else {
        document.getElementById('preview-toggle').style.display = 'none';
        if (previewVisible) {
            // Close preview if open
            togglePreview();
        }
    }
}

// Toggle preview visibility
function togglePreview() {
    var previewSection = document.getElementById('pdf-preview-section');
    var toggleButton = document.getElementById('preview-toggle');
    
    // if (previewVisible) {
    //     previewSection.style.display = 'none';
    //     toggleButton.innerHTML = '<i class="fa fa-eye"></i> Показать предварительный просмотр';
    //     previewVisible = false;
    // } else {
    //     previewSection.style.display = 'block';
    //     toggleButton.innerHTML = '<i class="fa fa-eye-slash"></i> Скрыть предварительный просмотр';
    //     previewVisible = true;
    //     generatePreview();
    // }
}

// Generate preview using the same template as PDF
function generatePreview() {
    if (!previewVisible) return;
    
    var previewDiv = document.getElementById('contract-preview');
    var loadingDiv = document.getElementById('preview-loading');
    
    // Show loading
    previewDiv.style.display = 'none';
    loadingDiv.style.display = 'block';
    
    // Collect all form data
    var formData = new FormData();
    
    // Add all form fields
    var form = document.querySelector('form');
    var formDataObj = new FormData(form);
    
    // Add the selected order ID
    formDataObj.append('selected_order_id', selectedOrderId || '');
    
    // Send AJAX request to generate preview
    fetch('<?= Url::to(['preview-contract']) ?>', {
        method: 'POST',
        body: formDataObj
    })
    .then(response => response.json())
    .then(data => {
        loadingDiv.style.display = 'none';
        previewDiv.style.display = 'block';
        
        if (data.success) {
            previewDiv.innerHTML = data.html;
        } else {
            previewDiv.innerHTML = `
                <div style="text-align: center; padding: 20px; color: #d9534f;">
                    <i class="fa fa-exclamation-triangle" style="font-size: 24px; margin-bottom: 10px;"></i>
                    <p><strong>Ошибка генерации предварительного просмотра</strong></p>
                    <p style="font-size: 12px;">${data.error || 'Неизвестная ошибка'}</p>
                </div>`;
        }
    })
    .catch(error => {
        loadingDiv.style.display = 'none';
        previewDiv.style.display = 'block';
        previewDiv.innerHTML = `
            <div style="text-align: center; padding: 20px; color: #d9534f;">
                <i class="fa fa-exclamation-triangle" style="font-size: 24px; margin-bottom: 10px;"></i>
                <p><strong>Ошибка соединения</strong></p>
                <p style="font-size: 12px;">Не удалось загрузить предварительный просмотр</p>
            </div>`;
        console.error('Preview generation error:', error);
    });
}

// Update preview when form fields change (debounced)
var updateTimeout;
function updatePreview() {
    if (!previewVisible) return;
    
    clearTimeout(updateTimeout);
    updateTimeout = setTimeout(generatePreview, 500); // 500ms delay
}
</script> 