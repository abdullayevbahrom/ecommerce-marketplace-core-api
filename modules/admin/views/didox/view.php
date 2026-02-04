<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model app\models\didox\DidoxDocument */

// Set title based on document type
if ($model->isArbitrary()) {
    $this->title = 'Произвольный договор: ' . $model->name;
} else {
    $this->title = 'Счет-фактура: ' . $model->name;
}
$this->params['breadcrumbs'][] = ['label' => 'Документы DIDOX', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<style>
/* E-IMZO Signing Interface Styles */
.eimzo-signing-section {
    background: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 20px;
    margin: 20px 0;
}

.eimzo-step-indicator {
    display: flex;
    justify-content: space-between;
    margin: 20px 0;
    padding: 0;
}

.eimzo-step {
    flex: 1;
    text-align: center;
    padding: 10px 5px;
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    font-size: 11px;
    font-weight: bold;
    color: #6c757d;
    margin-right: 1px;
    position: relative;
}

.eimzo-step:last-child {
    margin-right: 0;
}

.eimzo-step.active {
    background-color: #007bff;
    color: white;
    border-color: #007bff;
}

.eimzo-step.completed {
    background-color: #28a745;
    color: white;
    border-color: #28a745;
}

.eimzo-status {
    padding: 12px;
    margin: 15px 0;
    border-radius: 4px;
    font-weight: bold;
    min-height: 20px;
}

.eimzo-status.info {
    background-color: #d1ecf1;
    color: #0c5460;
    border: 1px solid #bee5eb;
}

.eimzo-status.success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.eimzo-status.error {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.eimzo-status.warning {
    background-color: #fff3cd;
    color: #856404;
    border: 1px solid #ffeaa7;
}

.certificate-info {
    background: #e9ecef;
    padding: 10px;
    border-radius: 4px;
    margin: 10px 0;
    font-size: 12px;
    font-family: monospace;
}

.signature-result {
    background: #d4edda;
    border: 1px solid #c3e6cb;
    color: #155724;
    padding: 15px;
    border-radius: 4px;
    margin: 15px 0;
    font-family: monospace;
    font-size: 11px;
    word-break: break-all;
}

.eimzo-buttons {
    display: flex;
    gap: 10px;
    margin: 15px 0;
    flex-wrap: wrap;
}

.eimzo-form-group {
    margin-bottom: 15px;
}

.eimzo-form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
    color: #555;
}

.eimzo-form-group select,
.eimzo-form-group input {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 13px;
}

.btn-eimzo {
    padding: 8px 16px;
    font-size: 13px;
    border-radius: 4px;
    border: none;
    cursor: pointer;
    font-weight: bold;
    text-decoration: none;
    display: inline-block;
}

.btn-eimzo.primary {
    background-color: #007bff;
    color: white;
}

.btn-eimzo.primary:hover:not(:disabled) {
    background-color: #0056b3;
}

.btn-eimzo.success {
    background-color: #28a745;
    color: white;
}

.btn-eimzo.success:hover:not(:disabled) {
    background-color: #218838;
}

.btn-eimzo.danger {
    background-color: #dc3545;
    color: white;
}

.btn-eimzo.danger:hover:not(:disabled) {
    background-color: #c82333;
}

.btn-eimzo:disabled {
    background-color: #6c757d;
    color: white;
    cursor: not-allowed;
    opacity: 0.6;
}

.text-muted {
    color: #6c757d;
}

.small {
    font-size: 11px;
}
</style>

<div class="content-wrapper">
    <section class="content-header">
        <h1><?= Html::encode($this->title) ?></h1>
        <ol class="breadcrumb">
            <li><a href="<?= Yii::$app->urlManager->createUrl(['/admin/']) ?>"><i class="fa fa-dashboard"></i> Главная</a></li>
            <li><a href="<?= Yii::$app->urlManager->createUrl(['/admin/didox']) ?>">Документы DIDOX</a></li>
            <li class="active"><?= Html::encode($this->title) ?></li>
        </ol>
    </section>
    <section class="content">
        <?php if (Yii::$app->session->hasFlash('success')): ?>
            <div class="alert alert-success alert-dismissible">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                <i class="fa fa-check-circle"></i> <?= Yii::$app->session->getFlash('success') ?>
            </div>
        <?php endif; ?>
        <?php if (Yii::$app->session->hasFlash('error')): ?>
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                <i class="fa fa-exclamation-triangle"></i> <?= Yii::$app->session->getFlash('error') ?>
            </div>
        <?php endif; ?>
        <?php if (Yii::$app->session->hasFlash('warning')): ?>
            <div class="alert alert-warning alert-dismissible">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                <i class="fa fa-exclamation-circle"></i> <?= Yii::$app->session->getFlash('warning') ?>
            </div>
        <?php endif; ?>
        <?php if (Yii::$app->session->hasFlash('info')): ?>
            <div class="alert alert-info alert-dismissible">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                <i class="fa fa-info-circle"></i> <?= Yii::$app->session->getFlash('info') ?>
            </div>
        <?php endif; ?>
        <div class="box box-info color-palette-box">
            <div class="box-header with-border">
                <div class="box-title pull-right" style="font-size: 14px">
                    <?= Html::a('<i class="fa fa-refresh"></i> Sync', ['sync', 'id' => $model->id], [
                        'class' => 'btn btn-sm btn-info',
                        'title' => 'Синхронизировать статус с DIDOX API',
                        'data' => [
                            'toggle' => 'tooltip',
                            'placement' => 'top'
                        ]
                    ]) ?>
                    <?php if ($model->didox_id): ?>
                        <div class="btn-group">
                            <button type="button" class="btn btn-sm btn-success dropdown-toggle" data-toggle="dropdown" title="Скачать PDF">
                                <i class="fa fa-file-pdf-o"></i> PDF <span class="caret"></span>
                            </button>
                            <ul class="dropdown-menu">
                                <li><?= Html::a('<i class="fa fa-eye"></i> Просмотр (UZ)', ['pdf', 'id' => $model->id, 'lang' => 'uz'], ['target' => '_blank']) ?></li>
                                <li><?= Html::a('<i class="fa fa-eye"></i> Просмотр (RU)', ['pdf', 'id' => $model->id, 'lang' => 'ru'], ['target' => '_blank']) ?></li>
                                <li class="divider"></li>
                                <li><?= Html::a('<i class="fa fa-download"></i> Скачать (UZ)', ['download-pdf', 'id' => $model->id, 'lang' => 'uz']) ?></li>
                                <li><?= Html::a('<i class="fa fa-download"></i> Скачать (RU)', ['download-pdf', 'id' => $model->id, 'lang' => 'ru']) ?></li>
                                <li class="divider"></li>
                                <li><?= Html::a('<i class="fa fa-cloud-download"></i> Загрузить с Didox', ['fetch-pdf', 'id' => $model->id], ['title' => 'Загрузить PDF с Didox и сохранить локально']) ?></li>
                            </ul>
                        </div>
                        <?php 
                        // Show PDF status indicators
                        $hasPdfUz = $model->hasPdf('uz');
                        $hasPdfRu = $model->hasPdf('ru');
                        ?>
                        <?php if ($hasPdfUz || $hasPdfRu): ?>
                            <span class="label label-success" style="margin-left: 5px;" title="PDF сохранен локально">
                                <i class="fa fa-check"></i> PDF: <?= $hasPdfUz ? 'UZ' : '' ?><?= ($hasPdfUz && $hasPdfRu) ? '/' : '' ?><?= $hasPdfRu ? 'RU' : '' ?>
                            </span>
                        <?php endif; ?>
                    <?php endif; ?>
                    <?= Html::a('<i class="fa fa-pencil"></i> Редактировать', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
                    <?= Html::a('<i class="fa fa-trash"></i> Удалить', ['delete', 'id' => $model->id], [
                        'class' => 'btn btn-danger',
                        'data' => [
                            'confirm' => 'Вы уверены, что хотите удалить этот элемент?',
                            'method' => 'post',
                        ],
                    ]) ?>
                    <?= Html::a('<i class="fa fa-list"></i> Назад к списку', ['index'], ['class' => 'btn btn-default']) ?>
                </div>
            </div>
            <div class="box-body">
                <div class="row">
                    <div class="col-md-8">
                        <!-- Generic Document Details -->
                        <?= DetailView::widget([
                            'model' => $model,
                            'options' => ['class' => 'table table-striped table-bordered detail-view'],
                            'attributes' => [
                                'id',
                                'name:text:Название документа',
                                [
                                    'attribute' => 'didox_id',
                                    'label' => 'ID документа DIDOX',
                                    'value' => $model->didox_id ?: 'Не подключен к DIDOX',
                                    'format' => 'raw',
                                    'contentOptions' => ['style' => 'font-family: monospace; font-size: 12px;']
                                ],
                                [
                                    'attribute' => 'document_type',
                                    'label' => 'Тип документа',
                                    'value' => $model->getDocumentTypeLabel() . ' (DIDOX: ' . ($model->didox_doc_type ?: '002') . ')',
                                ],
                                [
                                    'attribute' => 'didox_status',
                                    'label' => 'Статус DIDOX',
                                    'format' => 'raw',
                                    'value' => function($model) {
                                        if (!$model->isDidoxDocument()) {
                                            return '<span class="label label-default">Только локально</span>';
                                        }
                                        $label = $model->getDidoxStatusLabel();
                                        $color = str_replace('bg-', 'label-', $model->getDidoxStatusColor());
                                        return '<span class="label ' . $color . '">' . $label . '</span>';
                                    },
                                ],
                                
                                // === ASSIGNED USER ===
                                [
                                    'label' => false,
                                    'format' => 'raw',
                                    'value' => '<h4 style="margin-top: 20px; margin-bottom: 10px; color: #337ab7; border-bottom: 2px solid #337ab7; padding-bottom: 5px;"><i class="fa fa-user-circle"></i> Назначение</h4>'
                                ],
                                [
                                    'attribute' => 'to_user_id',
                                    'label' => 'Назначен пользователю',
                                    'value' => $model->toUser ? $model->toUser->name . ' (' . $model->toUser->eimzo_tax_id . ')' : 'Не назначен',
                                ],
                                
                                // === TIMESTAMPS ===
                                [
                                    'label' => false,
                                    'format' => 'raw',
                                    'value' => '<h4 style="margin-top: 20px; margin-bottom: 10px; color: #337ab7; border-bottom: 2px solid #337ab7; padding-bottom: 5px;"><i class="fa fa-clock-o"></i> Временные метки</h4>'
                                ],
                                'created_at:datetime:Создано',
                                'updated_at:datetime:Обновлено',
                            ],
                        ]) ?>
                        
                        <!-- Document Type Specific Details -->
                        <?php if ($model->isArbitrary()): ?>
                            <?= $this->render('_arbitraryView', ['model' => $model]) ?>
                        <?php else: ?>
                            <?= $this->render('_invoiceView', ['model' => $model]) ?>
                        <?php endif; ?>
                        
                        <!-- DIDOX Response Data Section -->
                        <?php if ($model->didox_data): ?>
                        <?php $didoxData = $model->getDidoxDataArray(); ?>
                        <div class="box box-default collapsed-box" style="margin-top: 20px;">
                            <div class="box-header with-border">
                                <h4 class="box-title"><i class="fa fa-cloud-download"></i> Ответ DIDOX API</h4>
                                <div class="box-tools pull-right">
                                    <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-plus"></i></button>
                                </div>
                            </div>
                            <div class="box-body">
                                <!-- Quick Summary -->
                                <div class="row">
                                    <div class="col-md-6">
                                        <table class="table table-condensed table-striped">
                                            <tr>
                                                <td><strong>Статус документа:</strong></td>
                                                <td>
                                                    <?php
                                                    $statusLabel = $model->getDidoxStatusLabel();
                                                    $statusColor = str_replace('bg-', 'label-', $model->getDidoxStatusColor());
                                                    ?>
                                                    <span class="label <?= $statusColor ?>"><?= Html::encode($statusLabel) ?></span>
                                                    <small class="text-muted">(код: <?= Html::encode($model->didox_status) ?>)</small>
                                                </td>
                                            </tr>
                                            <?php if (isset($didoxData['doc_id'])): ?>
                                            <tr>
                                                <td><strong>ID документа:</strong></td>
                                                <td><code><?= Html::encode($didoxData['doc_id']) ?></code></td>
                                            </tr>
                                            <?php endif; ?>
                                            <?php if (isset($didoxData['doc_status'])): ?>
                                            <tr>
                                                <td><strong>Статус (из API):</strong></td>
                                                <td><code><?= Html::encode($didoxData['doc_status']) ?></code></td>
                                            </tr>
                                            <?php endif; ?>
                                            <?php if (isset($didoxData['created_at']) || isset($didoxData['timestamp'])): ?>
                                            <tr>
                                                <td><strong>Создан в DIDOX:</strong></td>
                                                <td><?= Html::encode($didoxData['created_at'] ?? $didoxData['timestamp'] ?? 'Н/Д') ?></td>
                                            </tr>
                                            <?php endif; ?>
                                            <?php if (isset($didoxData['signed_at'])): ?>
                                            <tr>
                                                <td><strong>Подписан:</strong></td>
                                                <td><?= Html::encode($didoxData['signed_at']) ?></td>
                                            </tr>
                                            <?php endif; ?>
                                            <?php if (isset($didoxData['signer_tax_id'])): ?>
                                            <tr>
                                                <td><strong>ИНН подписанта:</strong></td>
                                                <td><code><?= Html::encode($didoxData['signer_tax_id']) ?></code></td>
                                            </tr>
                                            <?php endif; ?>
                                        </table>
                                    </div>
                                    <div class="col-md-6">
                                        <table class="table table-condensed table-striped">
                                            <?php if (isset($didoxData['owner_tin']) || isset($didoxData['sellerTin'])): ?>
                                            <tr>
                                                <td><strong>ИНН продавца:</strong></td>
                                                <td><code><?= Html::encode($didoxData['owner_tin'] ?? $didoxData['sellerTin'] ?? 'Н/Д') ?></code></td>
                                            </tr>
                                            <?php endif; ?>
                                            <?php if (isset($didoxData['partner_tin']) || isset($didoxData['buyerTin'])): ?>
                                            <tr>
                                                <td><strong>ИНН покупателя:</strong></td>
                                                <td><code><?= Html::encode($didoxData['partner_tin'] ?? $didoxData['buyerTin'] ?? 'Н/Д') ?></code></td>
                                            </tr>
                                            <?php endif; ?>
                                            <?php if (isset($didoxData['total_sum']) || isset($didoxData['totalSum'])): ?>
                                            <tr>
                                                <td><strong>Сумма:</strong></td>
                                                <td><?= number_format($didoxData['total_sum'] ?? $didoxData['totalSum'] ?? 0, 2, '.', ' ') ?> UZS</td>
                                            </tr>
                                            <?php endif; ?>
                                            <?php if ($model->didox_created_at): ?>
                                            <tr>
                                                <td><strong>Синхронизация:</strong></td>
                                                <td><?= Html::encode($model->didox_created_at) ?></td>
                                            </tr>
                                            <?php endif; ?>
                                        </table>
                                    </div>
                                </div>
                                
                                <!-- Full JSON Response (collapsible) -->
                                <div style="margin-top: 15px;">
                                    <button type="button" class="btn btn-sm btn-default" onclick="this.nextElementSibling.style.display = this.nextElementSibling.style.display === 'none' ? 'block' : 'none';">
                                        <i class="fa fa-code"></i> Показать/Скрыть полный JSON ответ
                                    </button>
                                    <pre style="display: none; margin-top: 10px; max-height: 400px; overflow-y: auto; background: #f8f9fa; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 11px;"><?= Html::encode(json_encode($didoxData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($model->didox_error_data): ?>
                        <div class="alert alert-danger" style="margin-top: 20px;">
                            <h4><i class="fa fa-exclamation-triangle"></i> Детали ошибки DIDOX</h4>
                            
                            <?php
                            $errorData = json_decode($model->didox_error_data, true);
                            if (is_array($errorData) && isset($errorData['debug_info'])):
                                $debugInfo = $errorData['debug_info'];
                            ?>
                            
                            <!-- Main Error Message -->
                            <div style="background: #fff; padding: 10px; border-radius: 4px; margin: 10px 0;">
                                <strong>Ошибка:</strong> <?= Html::encode($errorData['error_message'] ?? 'Неизвестная ошибка') ?>
                            </div>
                            
                            <!-- Signature Information -->
                            <?php if (isset($errorData['signature_full'])): ?>
                            <div style="background: #e3f2fd; padding: 10px; border-radius: 4px; margin: 10px 0;">
                                <h5><i class="fa fa-key"></i> Информация о подписи</h5>
                                <strong>Длина подписи:</strong> <code><?= Html::encode($errorData['signature_length'] ?? strlen($errorData['signature_full'])) ?> символов</code><br>
                                <strong>Полная подпись:</strong>
                                <pre style="background: #fff; padding: 8px; border: 1px solid #ddd; border-radius: 4px; margin: 5px 0; max-height: 300px; overflow-y: auto; font-size: 9px; word-wrap: break-word; white-space: pre-wrap;"><?= Html::encode($errorData['signature_full']) ?></pre>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Request Details -->
                            <?php if (isset($debugInfo['request_url'])): ?>
                            <div style="background: #f8f9fa; padding: 10px; border-radius: 4px; margin: 10px 0;">
                                <h5><i class="fa fa-globe"></i> Детали запроса</h5>
                                <strong>URL:</strong> <code><?= Html::encode($debugInfo['request_url']) ?></code><br>
                                <strong>Метод:</strong> <code><?= Html::encode($debugInfo['request_method'] ?? 'POST') ?></code><br>
                                <?php if (isset($debugInfo['request_body'])): ?>
                                <strong>Тело запроса (<?= strlen($debugInfo['request_body']) ?> символов):</strong>
                                <pre style="background: #fff; padding: 8px; border: 1px solid #ddd; border-radius: 4px; margin: 5px 0; max-height: 400px; overflow-y: auto; font-size: 10px; word-wrap: break-word; white-space: pre-wrap;"><?= Html::encode($debugInfo['request_body']) ?></pre>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Response Details -->
                            <?php if (isset($debugInfo['response_raw'])): ?>
                            <div style="background: #fff3cd; padding: 10px; border-radius: 4px; margin: 10px 0;">
                                <h5><i class="fa fa-reply"></i> Детали ответа</h5>
                                <strong>HTTP код:</strong> <code><?= Html::encode($errorData['didox_http_code'] ?? 'Н/Д') ?></code><br>
                                <strong>Сырой ответ (<?= strlen($debugInfo['response_raw']) ?> символов):</strong>
                                <pre style="background: #fff; padding: 8px; border: 1px solid #ddd; border-radius: 4px; margin: 5px 0; max-height: 400px; overflow-y: auto; font-size: 10px; word-wrap: break-word; white-space: pre-wrap;"><?= Html::encode($debugInfo['response_raw']) ?></pre>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Full Error Data (Collapsible) -->
                            <div style="margin-top: 15px;">
                                <button type="button" class="btn btn-sm btn-default" onclick="this.nextElementSibling.style.display = this.nextElementSibling.style.display === 'none' ? 'block' : 'none';">
                                    <i class="fa fa-code"></i> Показать/Скрыть полные данные об ошибке
                                </button>
                                <pre style="display: none; margin-top: 10px; max-height: 300px; overflow-y: auto; background: #f8f9fa; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 10px;"><?= Html::encode($model->didox_error_data) ?></pre>
                            </div>
                            
                            <?php else: ?>
                            <!-- Fallback for non-JSON error data -->
                            <pre style="margin-top: 10px; max-height: 300px; overflow-y: auto;"><?= Html::encode($model->didox_error_data) ?></pre>
                            <?php endif; ?>
                            
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="col-md-4">
                        <!-- Document Type Info -->
                        <div class="alert alert-info" style="margin-bottom: 20px;">
                            <h5><i class="fa fa-info-circle"></i> Тип документа</h5>
                            <p><strong><?= $model->getDocumentTypeLabel() ?></strong></p>
                            <small>DIDOX Doc Type: <?= $model->didox_doc_type ?: '002' ?> 
                                <?php if ($model->isArbitrary()): ?>
                                    (Произвольный документ)
                                <?php else: ?>
                                    (Счет-фактура)
                                <?php endif; ?>
                            </small>
                        </div>
                        
                        <!-- E-IMZO Signing Section -->
                        <?php 
                        // Check if signing is available for current user
                        $canSign = $model->canCurrentUserSign();
                        $statusMessage = $model->getSigningStatusMessage();
                        ?>
                        
                        <?php if ($canSign): ?>
                        <div class="eimzo-signing-section">
                            <h4><i class="fa fa-certificate"></i> E-IMZO Подписание</h4>
                        <?php else: ?>
                        <div class="alert alert-info">
                            <h4><i class="fa fa-info-circle"></i> Статус подписания</h4>
                            <p><?= Html::encode($statusMessage) ?></p>
                            <small class="text-muted">
                                <?php
                                $label = $model->getDidoxStatusLabel();
                                $color = str_replace('bg-', 'label-', $model->getDidoxStatusColor());
                                echo '<span class="label ' . $color . '">' . $label . '</span>';
                                ?>
                            </small>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($canSign): ?>
                            
                            <!-- Status Display -->
                            <div id="eimzo-status" class="eimzo-status info">
                                Инициализация E-IMZO...
                            </div>
                            
                            <!-- Step Indicator -->
            <div id="eimzo-step-indicator" class="eimzo-step-indicator">
                <div class="eimzo-step active" id="eimzo-step1">1. Подключение</div>
                <div class="eimzo-step" id="eimzo-step2">2. Сертификат</div>
                <div class="eimzo-step" id="eimzo-step3">3. Данные DIDOX</div>
                <div class="eimzo-step" id="eimzo-step4">4. Подпись</div>
                <div class="eimzo-step" id="eimzo-step5">5. Отправка</div>
            </div>
                            
                            <!-- Certificate Selection -->
                            <div class="eimzo-form-group">
                                <label for="eimzo-certificates">Выберите сертификат:</label>
                                <select id="eimzo-certificates" class="form-control">
                                    <option value="" disabled selected>Подключение к E-IMZO...</option>
                                </select>
                            </div>
                            
                            <!-- Selected Certificate Info -->
                            <div id="certificate-info" class="certificate-info" style="display: none;">
                                <strong>Выбранный сертификат:</strong>
                                <div id="certificate-details"></div>
                            </div>
                            
                            <!-- TIN Input -->
                            <div class="eimzo-form-group">
                                <label for="eimzo-tin">ИНН:</label>
                                <input type="text" id="eimzo-tin" class="form-control" pattern="[0-9]{9}" 
                                       placeholder="Будет заполнен из сертификата">
                            </div>
                            
                            <!-- DIDOX ID Display -->
                            <div class="eimzo-form-group">
                                <label>ID документа DIDOX:</label>
                                <div style="font-family: monospace; font-size: 11px; background: #f8f9fa; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                                    <?= Html::encode($model->didox_id ?: 'Не подключен к DIDOX') ?>
                                </div>
                            </div>
                            
                            <!-- Action Buttons -->
                            <div class="eimzo-buttons">
                                <button type="button" id="btn-connect-eimzo" class="btn-eimzo primary">
                                    <i class="fa fa-plug"></i> Подключиться
                                </button>
                                <button type="button" id="btn-sign-and-send" class="btn-eimzo success" disabled>
                                    <i class="fa fa-certificate"></i> Подписать и отправить в DIDOX
                                </button>
                            </div>
                            
                            <!-- Signature Result -->
                            <div id="signature-result" class="signature-result" style="display: none;">
                                <strong>Подпись создана:</strong>
                                <div id="signature-data"></div>
                            </div>
                            
                            <div class="text-muted small" style="margin-top: 15px;">
                                <strong>Требования:</strong><br>
                                • E-IMZO должен быть установлен и запущен<br>
                                • Сертификат должен быть действующим<br>
                                • Документ должен содержать все необходимые поля<br>
                                • Процесс включает подписание и автоматическую отправку в DIDOX<br>
                                • Все операции выполняются в браузере без backend эндпоинтов<br>
                                • Прямые вызовы к DIDOX API из frontend
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

    </section>
</div>

<script>
/*
 * DIDOX E-IMZO Document Signing Process - FRONTEND IMPLEMENTATION
 * ===============================================================
 * 
 * This implementation follows the official DIDOX API documentation for 
 * "6. Подписание/Подтверждение исходящего документа" process:
 * 
 * ✅ ALL LOGIC IS HANDLED ON FRONTEND - NO BACKEND PHP ENDPOINTS NEEDED
 * ✅ USES INITIAL DATA FROM BACKEND: didox_id, user_token, partner_token
 * ✅ MAKES DIRECT CALLS TO DIDOX API ENDPOINTS FROM BROWSER
 * 
 * DIDOX API Steps (all executed in frontend JavaScript):
 * Step 1: Получить список ключей (Get list of keys) - E-IMZO WebSocket
 * Step 2: Получить keyId (Get keyId) - E-IMZO WebSocket  
 * Step 3: Получить значение json с респонса GET /v1/documents/{didox_id}?owner=1 - Direct DIDOX API call
 * Step 4: Преобразовать json с 3го шага в base64 (Convert JSON from step 3 to base64) - Frontend
 * Step 5: Создать подпись (Create signature - first argument is base64 from step 4) - E-IMZO WebSocket
 * Step 6: Прикрепить timestamp к подписи (Attach timestamp to signature) - Direct DIDOX API call
 * Step 7: Получить значение timeStampTokenB64 с респонса 6го шага - Frontend
 * Step 8: Отправить значение timeStampTokenB64 на эндпоинт POST /v1/documents/{didox_id}/accept - Direct DIDOX API call
 * 
 * Benefits of Frontend Implementation:
 * ✅ No need for additional PHP backend endpoints
 * ✅ Reduced server complexity and maintenance
 * ✅ Direct DIDOX API communication
 * ✅ Reduced server load and complexity
 * ✅ Real-time status updates
 * ✅ Better error handling and debugging
 * 
 * Required Initial Data (passed from PHP to JavaScript):
 * - documentData: {id, name, didox_id, status}
 * - didoxConfig: {api_base, user_token, partner_token, user_authenticated}
 * 
 * Reference: https://api-docs.einvoice.example.com/ru/integrators-documents
 */

// Browser compatibility polyfill for crypto.randomUUID
if (!crypto.randomUUID) {
    crypto.randomUUID = function() {
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
            var r = Math.random() * 16 | 0;
            var v = c == 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
    };
}

// E-IMZO Constants and Variables
const DIDOX_API_BASE = 'https://stage.goodsign.biz';
const EIMZO_WEBSOCKET = 'wss://127.0.0.1:64443/service/cryptapi';

let ws = null;
let certificates = [];
let selectedCertificate = null;
let loginData = {};
let documentData = <?= json_encode([
    'id' => $model->id,
    'name' => $model->name,
    'document_type' => $model->document_type,
    'status' => $model->didox_status,
    'didox_id' => $model->didox_id
]) ?>;

let didoxConfig = <?= json_encode([
    'api_base' => 'https://stage.goodsign.biz',
    'user_token' => Yii::$app->session->get('didox_token', ''),
    'partner_token' => Yii::$app->params['didoxPartnerToken'] ?? '',
    'user_authenticated' => Yii::$app->session->get('didox_authenticated', false)
]) ?>;

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    setupEventHandlers();
    initializeEIMZO();
});

// Setup event handlers
function setupEventHandlers() {
    document.getElementById('btn-connect-eimzo').addEventListener('click', connectToEIMZO);
    document.getElementById('btn-sign-and-send').addEventListener('click', signAndSendDocument);
    document.getElementById('eimzo-certificates').addEventListener('change', onCertificateSelect);
}

// Initialize EIMZO
function initializeEIMZO() {
    updateStatus("Инициализация E-IMZO...", "info");
    
    // Try to connect automatically
    setTimeout(connectToEIMZO, 1000);
}

// Connect to EIMZO
function connectToEIMZO() {
    updateStatus("Подключение к E-IMZO...", "info");
    updateEIMZOStep(1, false);
    
    if (ws && ws.readyState === WebSocket.OPEN) {
        ws.close();
    }
    
    ws = new WebSocket(EIMZO_WEBSOCKET);
    
    ws.onopen = function() {
        console.log("E-IMZO WebSocket connected");
        updateStatus("Соединение с E-IMZO установлено. Загрузка сертификатов...", "info");
        
        ws.send(JSON.stringify({
            plugin: "pfx",
            name: "list_all_certificates"
        }));
    };
    
    ws.onerror = function(error) {
        console.error("E-IMZO WebSocket error:", error);
        updateStatus("Ошибка подключения к E-IMZO. Убедитесь, что приложение запущено.", "error");
        updateEIMZOStep(1, false);
    };
    
    ws.onclose = function() {
        console.log("E-IMZO WebSocket connection closed");
        updateStatus("Соединение с E-IMZO закрыто", "error");
        updateEIMZOStep(1, false);
    };
    
    ws.onmessage = function(evt) {
        console.log("E-IMZO message received:", evt.data);
        handleEIMZOMessage(JSON.parse(evt.data));
    };
}

// Handle E-IMZO messages
function handleEIMZOMessage(data) {
    console.log("E-IMZO message received:", data);
    
    if (data?.certificates) {
        console.log("✅ Step 1-2: Certificates received");
        handleCertificatesList(data.certificates);
    }
    
    if (data?.keyId) {
        console.log("✅ DIDOX Step 2 COMPLETED: Get keyId");
        console.log("KeyId received:", data.keyId);
        loginData.keyId = data.keyId;
        createEIMZOSignature();
    }
    
    if (data?.pkcs7_64) {
        console.log("✅ DIDOX Step 5 COMPLETED: E-IMZO signature created");
        console.log("PKCS7 signature length:", data.pkcs7_64?.length || 0);
        console.log("Signature hex length:", data.signature_hex?.length || 0);
        console.log("PKCS7 preview:", data.pkcs7_64?.substring(0, 100) + '...');
        
        loginData.pkcs7_64 = data.pkcs7_64;
        loginData.signature_hex = data.signature_hex;
        updateEIMZOStep(4, false);
        
        console.log("Step 6: Adding timestamp to signature...");
        addTimestampToSignature();
    }
    
    if (data?.success === false) {
        console.error("❌ E-IMZO operation failed:", data);
        console.error("Error reason:", data.reason);
        console.error("Error details:", data);
        updateStatus("Ошибка E-IMZO: " + (data.reason || "Неизвестная ошибка"), "error");
    }
}

// Handle certificates list
function handleCertificatesList(certs) {
    console.log("✅ DIDOX Step 1 COMPLETED: Get list of keys (certificates)");
    console.log("Certificates found:", certs);
    certificates = certs;
    
    const select = document.getElementById("eimzo-certificates");
    select.innerHTML = "";
    
    if (certificates.length === 0) {
        const option = document.createElement("option");
        option.value = "";
        option.text = "Сертификаты не найдены";
        option.disabled = true;
        select.appendChild(option);
        updateStatus("Сертификаты не найдены", "error");
        return;
    }
    
    // Default option
    const defaultOption = document.createElement("option");
    defaultOption.value = "";
    defaultOption.text = "Выберите сертификат";
    defaultOption.disabled = true;
    defaultOption.selected = true;
    select.appendChild(defaultOption);
    
    // Add certificates
    certificates.forEach((certificate, index) => {
        const option = document.createElement("option");
        option.value = index;
        option.text = parseCertificateInfo(certificate);
        option.title = certificate.alias;
        select.appendChild(option);
    });
    
    updateStatus(`Найдено сертификатов: ${certificates.length}. Выберите сертификат.`, "success");
    updateEIMZOStep(1, true);
    updateEIMZOStep(2, false);
}

// Extract INN from certificate (complete logic matching login page)
function extractInnFromCertificate(certificate) {
    if (!certificate) return null;
    
    let extractedInn = null;
    
    // Try to get INN from cert.inn property
    if (certificate.inn) {
        extractedInn = certificate.inn;
    }
    // Try to extract from alias
    else if (certificate.alias) {
        extractedInn = extractInnFromAlias(certificate.alias);
    }
    // Try to extract from subject if available
    else if (certificate.subject && certificate.subject['1.2.860.3.16.1.1']) {
        extractedInn = certificate.subject['1.2.860.3.16.1.1'];
    }
    // Try to extract from other certificate properties
    else if (certificate.serialNumber) {
        // Sometimes INN might be in other certificate fields
        const serialStr = certificate.serialNumber.toString();
        if (/^\d{9}$/.test(serialStr)) {
            extractedInn = serialStr;
        }
    }
    
    return extractedInn;
}

// Extract INN from certificate alias (same logic as login page)
function extractInnFromAlias(alias) {
    if (!alias) return null;
    
    // Extract INN from E-IMZO certificate alias
    // Common formats:
    // 1.2.860.3.16.1.1=123456789
    // CN=..., 1.2.860.3.16.1.1=123456789, ...
    // Sometimes it might be in different formats
    
    // Try primary OID format for INN
    let match = alias.match(/1\.2\.860\.3\.16\.1\.1=(\d{9})/);
    if (match) return match[1];
    
    // Try alternative formats
    match = alias.match(/INN[=:](\d{9})/i);
    if (match) return match[1];
    
    // Try to find any 9-digit number in the alias (as last resort)
    match = alias.match(/(\d{9})/);
    if (match) return match[1];
    
    return null;
}

// Parse certificate information
function parseCertificateInfo(certificate) {
    try {
        const alias = certificate.alias;
        const info = {};
        
        const patterns = {
            cn: /cn=([^,]+)/i,
            name: /name=([^,]+)/i,
            surname: /surname=([^,]+)/i,
            o: /o=([^,]+)/i,
            uid: /uid=([^,]+)/i,
            validto: /validto=([^,]+)/i
        };
        
        Object.keys(patterns).forEach(key => {
            const match = alias.match(patterns[key]);
            if (match) {
                info[key] = match[1];
            }
        });
        
        const displayParts = [];
        
        if (info.cn) {
            displayParts.push(info.cn.toUpperCase());
        }
        
        if (info.name && info.surname) {
            displayParts.push(`(${info.name} ${info.surname})`);
        }
        
        if (info.o) {
            displayParts.push(`- ${info.o}`);
        }
        
        if (info.uid) {
            displayParts.push(`ИНН: ${info.uid}`);
        }
        
        if (info.validto) {
            displayParts.push(`до ${info.validto}`);
        }
        
        return displayParts.length > 0 ? displayParts.join(" ") : certificate.name || "Неизвестный сертификат";
        
    } catch (error) {
        console.error("Error parsing certificate:", error);
        return certificate.name || "Неизвестный сертификат";
    }
}



// Handle certificate selection
function onCertificateSelect(event, tin=null) {
    const selectedIndex = event.target.value;
    const tinField = document.getElementById('eimzo-tin');
    const certInfoDiv = document.getElementById('certificate-info');
    const certDetailsDiv = document.getElementById('certificate-details');
    
    if (selectedIndex && certificates[selectedIndex]) {
        selectedCertificate = certificates[selectedIndex];
        const uid = extractInnFromCertificate(selectedCertificate);
        
        if (uid) {
            tinField.value = uid;
            
            // Show certificate details
            const certInfo = parseCertificateInfo(selectedCertificate);
            certDetailsDiv.innerHTML = certInfo;
            certInfoDiv.style.display = 'block';
            
            updateStatus(`Сертификат выбран. ИНН: ${uid}`, "success");
            updateEIMZOStep(2, true);
            
            // Enable signing button
            document.getElementById('btn-sign-and-send').disabled = false;
        } else {
            tinField.value = '';
            certInfoDiv.style.display = 'none';
            updateStatus("Внимание: ИНН не найден в выбранном сертификате", "error");
            document.getElementById('btn-sign-and-send').disabled = true;
        }
    } else {
        selectedCertificate = null;
        tinField.value = tin ?? '';
        certInfoDiv.style.display = 'none';
        updateStatus("Выберите сертификат", "info");
        document.getElementById('btn-sign-and-send').disabled = true;
    }
}

// Sign and send document (combined process)
function signAndSendDocument() {
    if (!selectedCertificate) {
        updateStatus("Выберите сертификат", "error");
        return;
    }
    
    const tin = document.getElementById('eimzo-tin').value.trim();
    if (!tin || !/^\d{9}$/.test(tin)) {
        updateStatus("ИНН не найден в выбранном сертификате или имеет неверный формат", "error");
        return;
    }
    
    loginData = { 
        taxId: tin, 
        certificateIndex: document.getElementById('eimzo-certificates').value,
        documentId: documentData.id
    };
    
    // Disable the button to prevent multiple clicks
    document.getElementById('btn-sign-and-send').disabled = true;
    
    updateStatus(`Получение данных документа из DIDOX API (ID: ${documentData.didox_id})...`, "info");
    updateEIMZOStep(3, false);
    
    // Step 1: Get document data from DIDOX API using didox_id
    getDocumentDataFromDidox();
}

// Get document data from DIDOX for signing (Frontend direct call)
async function getDocumentDataFromDidox() {
    try {
        console.log('=== DIDOX FRONTEND SIGNING PROCESS ===');
        console.log('🔄 DIDOX Step 3: Getting JSON from GET /v1/documents/{didox_id}?owner=1...');
        console.log('Document ID:', documentData.id);
        console.log('DIDOX ID:', documentData.didox_id);
        console.log('User authenticated:', didoxConfig.user_authenticated);
        console.log('User token available:', !!didoxConfig.user_token);
        
        if (!documentData.didox_id) {
            updateStatus("❌ DIDOX ID отсутствует. Документ должен быть подключен к DIDOX.", "error");
            throw new Error('DIDOX ID not found. Document must be connected to DIDOX first.');
        }
        
        if (!didoxConfig.user_authenticated || !didoxConfig.user_token) {
            updateStatus("❌ Пользователь не аутентифицирован в DIDOX. Необходимо войти в систему.", "error");
            throw new Error('User not authenticated with DIDOX. Please login first.');
        }
        
        const didoxUrl = `${didoxConfig.api_base}/v1/documents/${documentData.didox_id}?owner=1`;
        console.log('Making direct DIDOX API call:', didoxUrl);
        
        const response = await fetch(didoxUrl, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'user-key': didoxConfig.user_token,
                'Partner-Authorization': didoxConfig.partner_token
            }
        });
        
        console.log('DIDOX API Response status:', response.status);
        console.log('DIDOX API Response OK:', response.ok);
        
        if (!response.ok) {
            const errorText = await response.text();
            console.error('DIDOX API Error Response:', errorText);
            throw new Error(`DIDOX API returned ${response.status}: ${errorText}`);
        }
        
        const responseJson = await response.json();
        const documentJson = responseJson.data.json;
        console.log('DIDOX API Response data:', documentJson);
        
        // Exclude doctype from the document JSON for signing
        if (documentJson && documentJson.hasOwnProperty('doctype')) {
            delete documentJson.doctype;
            console.log('✅ Doctype excluded from document JSON for signing');
        }
        
        // Store document data from DIDOX API response
        loginData.documentJson = documentJson;
        loginData.documentBase64 = btoa(unescape(encodeURIComponent(JSON.stringify(documentJson))));
        
        console.log('✅ DIDOX Step 3 COMPLETED: JSON retrieved directly from DIDOX API');
        console.log('✅ DIDOX Step 4 COMPLETED: JSON converted to base64');
        console.log('DIDOX API endpoint called:', didoxUrl);
        console.log('DIDOX ID used:', documentData.didox_id);
        console.log('Document JSON from DIDOX (length):', JSON.stringify(documentJson).length);
        console.log('Document JSON from DIDOX:', documentJson);
        console.log('Document Base64 length:', loginData.documentBase64.length);
        console.log('Base64 preview:', loginData.documentBase64);
        
        updateStatus("Данные документа получены из DIDOX API. Загрузка ключа сертификата...", "info");
        
        // Step 3 completed: Document data retrieved
        updateEIMZOStep(3, true);
        
        // Step 4: Load certificate key and create signature
        loadCertificateKey();
        
    } catch (error) {
        console.error("❌ DIDOX Step 3 EXCEPTION: Failed to get document directly from DIDOX:", error);
        console.error("DIDOX ID:", documentData.didox_id);
        console.error("DIDOX Config:", didoxConfig);
        updateStatus("Ошибка получения данных документа из DIDOX API: " + error.message, "error");
        
        // Re-enable button for retry
        document.getElementById('btn-sign-and-send').disabled = false;
    }
}

// Load certificate key
function loadCertificateKey() {
    const certificate = selectedCertificate;
    
    if (!certificate) {
        updateStatus("Сертификат не найден", "error");
        return;
    }
    
    ws.send(JSON.stringify({
        plugin: "pfx",
        name: "load_key",
        arguments: [certificate.disk, certificate.path, certificate.name, certificate.alias]
    }));
}

// Create E-IMZO signature using DIDOX document data
function createEIMZOSignature() {
    console.log('🔄 DIDOX Step 5: Creating signature (first argument: base64 from step 4)...');
    console.log('Document Base64 available:', !!loginData.documentBase64);
    console.log('Document Base64 length:', loginData.documentBase64?.length || 0);
    console.log('KeyId available:', !!loginData.keyId);
    console.log('KeyId:', loginData.keyId);
    
    if (!loginData.documentBase64) {
        console.error('❌ Step 5 FAILED: No document base64 data');
        updateStatus("Ошибка: Данные документа не получены из DIDOX", "error");
        return;
    }
    
    console.log('✅ Step 5: Sending E-IMZO signature request...');
    console.log('E-IMZO arguments:', [
        loginData.documentBase64.substring(0, 100) + '... (base64)',
        loginData.keyId,
        "no"
    ]);
    
    
    updateStatus("Создание цифровой подписи для DIDOX документа...", "info");
    

    // Sign the base64 document data from DIDOX
    ws.send(JSON.stringify({
        plugin: "pkcs7",
        name: "create_pkcs7",
        arguments: [loginData.documentBase64, loginData.keyId, "no"]
    }));
}

// Add timestamp to signature
async function addTimestampToSignature() {
    try {
        console.log('🔄 DIDOX Step 6: Attaching timestamp to signature...');
        console.log('PKCS7 available:', !!loginData.pkcs7_64);
        console.log('PKCS7 length:', loginData.pkcs7_64?.length || 0);
        console.log('Signature hex available:', !!loginData.signature_hex);
        console.log('Signature hex length:', loginData.signature_hex?.length || 0);
        
        updateStatus("Добавление временной метки...", "info");
        
        const requestBody = {
            pkcs7: loginData.pkcs7_64,
            signatureHex: loginData.signature_hex
        };
        
        console.log('Timestamp request body:', {
            pkcs7: loginData.pkcs7_64?.substring(0, 100) + '... (length: ' + (loginData.pkcs7_64?.length || 0) + ')',
            signatureHex: loginData.signature_hex?.substring(0, 100) + '... (length: ' + (loginData.signature_hex?.length || 0) + ')'
        });
        
        const response = await fetch(`${DIDOX_API_BASE}/v1/dsvs/timestamp`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(requestBody)
        });
        
        console.log('Timestamp response status:', response.status);
        console.log('Timestamp response OK:', response.ok);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        console.log('Timestamp response data:', data);
        
        if (data.timeStampTokenB64) {
            console.log('✅ DIDOX Step 6 COMPLETED: Timestamp attached to signature');
            console.log('✅ DIDOX Step 7 COMPLETED: timeStampTokenB64 value obtained');
            console.log('timeStampTokenB64 length:', data.timeStampTokenB64.length);
            console.log('timeStampTokenB64 preview:', data.timeStampTokenB64);
            
            loginData.timestampedSignature = data.timeStampTokenB64;
            updateStatus("Временная метка добавлена. Автоматическая отправка в DIDOX...", "success");
            updateEIMZOStep(4, true);
            
            // Show signature result
            showSignatureResult();
            
            // Automatically proceed to send to DIDOX
            setTimeout(() => {
                sendToDidox();
            }, 1000);
        } else {
            console.error('❌ Step 6 FAILED: No timeStampTokenB64 in response');
            console.error('Response data:', data);
            throw new Error('Не удалось получить подпись с временной меткой');
        }
        
    } catch (error) {
        console.error("❌ Step 6 EXCEPTION:", error);
        updateStatus("Ошибка добавления временной метки: " + error.message, "error");
    }
}

// Show signature result
function showSignatureResult() {
    const resultDiv = document.getElementById('signature-result');
    const dataDiv = document.getElementById('signature-data');
    
    const signature = loginData.timestampedSignature || loginData.pkcs7_64;
    
    dataDiv.innerHTML = `
        <strong>Документ:</strong> ${documentData.name}<br>
        <strong>ИНН:</strong> ${loginData.taxId}<br>
        <strong>Сертификат:</strong> ${parseCertificateInfo(selectedCertificate)}<br>
        <strong>Подпись:</strong> ${signature.substring(0, 50)}...<br>
        <strong>Размер:</strong> ${signature.length} символов
    `;
    
    resultDiv.style.display = 'block';
}

// Send to DIDOX (Frontend direct call)
async function sendToDidox() {
    try {
        console.log('🔄 DIDOX Step 8: Sending timeStampTokenB64 directly to DIDOX endpoint...');
        console.log('Timestamped signature available:', !!loginData.timestampedSignature);
        console.log('Fallback PKCS7 available:', !!loginData.pkcs7_64);
        
        updateStatus("Отправка подписи в DIDOX API...", "info");
        updateEIMZOStep(5, false);
        
        const signature = loginData.timestampedSignature || loginData.pkcs7_64;
        const isTimestamped = !!loginData.timestampedSignature;
        
        console.log('Using signature type:', isTimestamped ? 'timeStampTokenB64' : 'pkcs7_64 (fallback)');
        console.log('Signature length:', signature?.length || 0);
        console.log('Signature preview:', signature?.substring(0, 100) + '...');
        console.log('Tax ID:', loginData.taxId);
        console.log('DIDOX ID:', documentData.didox_id);
        
        if (!signature) {
            throw new Error('No signature available to send');
        }
        
        // Direct call to DIDOX API for signing
        const didoxSignUrl = `${didoxConfig.api_base}/v1/documents/${documentData.didox_id}/sign`;
        console.log('Making direct DIDOX API signing call:', didoxSignUrl);
        
        const requestBody = {
            signature: signature
        };
        
        console.log('DIDOX API signing request:', {
            url: didoxSignUrl,
            signature_length: signature.length,
            signature_preview: signature.substring(0, 100) + '...',
            user_token: didoxConfig.user_token ? 'provided' : 'missing',
            partner_token: didoxConfig.partner_token ? 'provided' : 'missing'
        });
        
        const response = await fetch(didoxSignUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'user-key': didoxConfig.user_token,
                'Partner-Authorization': didoxConfig.partner_token
            },
            body: JSON.stringify(requestBody)
        });
        
        console.log('DIDOX API signing response status:', response.status);
        console.log('DIDOX API signing response OK:', response.ok);
        
        if (!response.ok) {
            const errorText = await response.text();
            console.error('DIDOX API signing error response:', errorText);
            throw new Error(`DIDOX API signing failed with status ${response.status}: ${errorText}`);
        }
        
        const result = await response.json();
        console.log('DIDOX API signing response data:', result);
        
        console.log('✅ DIDOX Step 8 COMPLETED: timeStampTokenB64 successfully sent to DIDOX API!');
        console.log('Final DIDOX API result:', result);
        
        updateStatus("Документ успешно подписан и отправлен в DIDOX API!", "success");
        updateEIMZOStep(5, true);
        
        // Refresh page to show updated status
        setTimeout(() => {
            window.location.reload();
        }, 2000);
        
    } catch (error) {
        console.error("❌ DIDOX Step 8 EXCEPTION: Direct DIDOX API signing error:", error);
        console.error("DIDOX Config:", didoxConfig);
        console.error("Signature data:", {
            signature_available: !!loginData.timestampedSignature,
            pkcs7_available: !!loginData.pkcs7_64,
            signature_length: (loginData.timestampedSignature || loginData.pkcs7_64)?.length || 0
        });
        
        updateStatus("Ошибка отправки в DIDOX API: " + error.message, "error");
        
        // Re-enable button to allow retry
        document.getElementById('btn-sign-and-send').disabled = false;
    }
}

// Update status
function updateStatus(message, type = 'info') {
    console.log("Status:", message);
    
    const statusDiv = document.getElementById("eimzo-status");
    if (statusDiv) {
        statusDiv.textContent = message;
        statusDiv.className = `eimzo-status ${type}`;
    }
}

// Update E-IMZO steps
function updateEIMZOStep(stepNumber, completed = null) {
    for (let i = 1; i <= 5; i++) {
        const stepDiv = document.getElementById(`eimzo-step${i}`);
        if (stepDiv) {
            stepDiv.classList.remove('active', 'completed');
            
            if (i < stepNumber || (i === stepNumber && completed === true)) {
                stepDiv.classList.add('completed');
            } else if (i === stepNumber) {
                stepDiv.classList.add('active');
            }
        }
    }
}
</script>