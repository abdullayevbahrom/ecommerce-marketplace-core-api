<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\models\product\ProductRequest;

$this->title = 'Запрос на товар #' . $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Запросы на товар', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="content-wrapper">
    <section class="content-header">
        <h1><?=mb_substr($this->title, 0, 50, 'utf-8');?></h1>
        
        <ol class="breadcrumb">
            <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/'])?>"><i class="fa fa-dashboard"></i> Главная</a></li>
            <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/product-request/'])?>">Запросы на товар</a></li>
            <li class="active"><?=mb_substr($this->title, 0, 50, 'utf-8');?></li>
        </ol>
    </section>
    <section class="content">
        <?php if (Yii::$app->session->hasFlash('request_updated')) {?>
            <div class="callout callout-success text-center">
                <?=Yii::$app->session->getFlash('request_updated');?>
            </div>
        <?php }?>
        
        <div class="row">
            <div class="col-md-8">
                <div class="box box-info color-palette-box">
                    <div class="box-header">
                        <h3 class="box-title">Информация о запросе</h3>
                    </div>
                    <div class="box-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="text-center">
                                    <img src="<?= $model->getPhotoUrl() ?>" alt="Product Photo" class="img-responsive img-thumbnail" style="max-width: 200px;">
                                    <?php if (!$model->existPhoto()): ?>
                                        <br><small class="text-muted"><i class="fa fa-info-circle"></i> Фотография не прикреплена</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <table class="table table-striped">
                                    <tr>
                                        <td><strong>ID:</strong></td>
                                        <td><?= Html::encode($model->id) ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Наименование товара:</strong></td>
                                        <td><?= Html::encode($model->product_name) ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Количество:</strong></td>
                                        <td><?= Html::encode($model->quantity) ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Ссылка на товар:</strong></td>
                                        <td>
                                            <?php if ($model->product_link): ?>
                                                <div class="alert alert-warning" style="margin-bottom: 5px; padding: 8px;">
                                                    <i class="fa fa-warning text-danger"></i> 
                                                    <strong class="text-danger">ВНИМАНИЕ:</strong> Проверьте ссылку перед переходом!
                                                </div>
                                                <div class="well well-sm" style="background-color: #f5f5f5; word-break: break-all; font-family: monospace; font-size: 12px; margin-bottom: 10px;">
                                                    <i class="fa fa-link text-muted"></i> 
                                                    <strong>RAW URL:</strong><br>
                                                    <span style="color: #d9534f; background-color: #fff; padding: 2px 4px; border: 1px solid #ddd; display: inline-block; width: 100%; margin-top: 5px;">
                                                        <?= Html::encode($model->product_link) ?>
                                                    </span>
                                                </div>
                                                <div class="btn-group">
                                                    <button type="button" class="btn btn-xs btn-warning" onclick="copyToClipboard('<?= Html::encode($model->product_link) ?>')">
                                                        <i class="fa fa-copy"></i> Копировать ссылку
                                                    </button>
                                                    <a href="<?= Html::encode($model->product_link) ?>" target="_blank" class="btn btn-xs btn-danger" onclick="return confirm('⚠️ Вы уверены, что хотите перейти по этой ссылке? ⚠️ \nURL: <?= Html::encode($model->product_link) ?>')">
                                                        <i class="fa fa-external-link"></i> Открыть (проверено)
                                                    </a>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Номер телефона:</strong></td>
                                        <td>
                                            <?= Html::encode($model->phone) ?>
                                            <a href="tel:<?= Html::encode($model->phone) ?>" class="btn btn-xs btn-primary" style="margin-left: 10px;">
                                                <i class="fa fa-phone"></i> Позвонить
                                            </a>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Email:</strong></td>
                                        <td>
                                            <?php if ($model->email): ?>
                                                <?= Html::encode($model->email) ?>
                                                <a href="mailto:<?= Html::encode($model->email) ?>" class="btn btn-xs btn-primary" style="margin-left: 10px;">
                                                    <i class="fa fa-envelope"></i> Написать
                                                </a>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Статус:</strong></td>
                                        <td>
                                            <?php
                                            switch ($model->status) {
                                                case ProductRequest::STATUS_PENDING:
                                                    echo '<span class="label label-warning">На рассмотрении</span>';
                                                    break;
                                                case ProductRequest::STATUS_APPROVED:
                                                    echo '<span class="label label-success">Одобрено</span>';
                                                    break;
                                                case ProductRequest::STATUS_REJECTED:
                                                    echo '<span class="label label-danger">Отклонено</span>';
                                                    break;
                                                default:
                                                    echo '<span class="label label-default">Неизвестно</span>';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Дата подачи:</strong></td>
                                        <td><?= Yii::$app->formatter->asDatetime($model->date) ?></td>
                                    </tr>
                                    <?php if ($model->user): ?>
                                    <tr>
                                        <td><strong>Пользователь:</strong></td>
                                        <td>
                                            <div>
                                                <strong>
                                                    <a href="<?= Yii::$app->urlManager->createUrl(['/admin/user/view', 'id' => $model->user->id]) ?>" class="btn btn-sm btn-info" style="margin-right: 10px;">
                                                        <i class="fa fa-user"></i> <?= Html::encode($model->user->name ? $model->user->name : 'Пользователь #' . $model->user->id) ?>
                                                    </a>
                                                </strong>
                                                <br>
                                                <small class="text-muted">
                                                    ID: <?= $model->user->id ?> | 
                                                    Телефон: <?= Html::encode($model->user->phone) ?> |
                                                    Email: <?= Html::encode($model->user->email) ?>
                                                </small>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                    <?php if ($model->admin): ?>
                                    <tr>
                                        <td><strong>Администратор:</strong></td>
                                        <td>
                                            <div>
                                                <strong>
                                                    <a href="<?= Yii::$app->urlManager->createUrl(['/admin/user/view', 'id' => $model->admin->id]) ?>" class="btn btn-sm btn-success" style="margin-right: 10px;">
                                                        <i class="fa fa-user-secret"></i> <?= Html::encode($model->admin->name ? $model->admin->name : 'Администратор #' . $model->admin->id) ?>
                                                    </a>
                                                </strong>
                                                <br>
                                                <small class="text-muted">ID: <?= $model->admin->id ?></small>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                    <?php if ($model->admin_notes): ?>
                                    <tr>
                                        <td><strong>Заметки администратора:</strong></td>
                                        <td><?= Html::encode($model->admin_notes) ?></td>
                                    </tr>
                                    <?php endif; ?>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="box box-warning">
                    <div class="box-header">
                        <h3 class="box-title">Быстрые действия</h3>
                    </div>
                    <div class="box-body">
                        <div class="btn-group" role="group">
                            <?php if ($model->status != ProductRequest::STATUS_APPROVED): ?>
                                <a href="<?= Yii::$app->urlManager->createUrl(['/admin/product-request/approve', 'id' => $model->id]) ?>" 
                                   class="btn btn-success">
                                    <i class="fa fa-check"></i> Одобрить запрос
                                </a>
                            <?php endif; ?>
                            
                            <?php if ($model->status != ProductRequest::STATUS_REJECTED): ?>
                                <a href="<?= Yii::$app->urlManager->createUrl(['/admin/product-request/reject', 'id' => $model->id]) ?>" 
                                   class="btn btn-warning">
                                    <i class="fa fa-times"></i> Отклонить запрос
                                </a>
                            <?php endif; ?>
                            
                            <a href="<?= Yii::$app->urlManager->createUrl(['/admin/product-request/remove', 'id' => $model->id]) ?>" 
                               class="btn btn-danger remove-object">
                                <i class="fa fa-trash"></i> Удалить запрос
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <!-- Status Management -->
                <div class="box box-primary">
                    <div class="box-header">
                        <h3 class="box-title">Управление статусом</h3>
                    </div>
                    <?php $form = ActiveForm::begin(['method' => 'post']); ?>
                    <div class="box-body">
                        <div class="form-group">
                            <label>Статус запроса</label>
                            <?= Html::dropDownList('status', $model->status, ProductRequest::getStatusOptions(), [
                                'class' => 'form-control',
                                'id' => 'status-select'
                            ]) ?>
                        </div>
                        
                        <div class="form-group">
                            <label>Заметки администратора</label>
                            <?= Html::textarea('admin_notes', $model->admin_notes, [
                                'class' => 'form-control',
                                'rows' => 5,
                                'placeholder' => 'Добавьте заметки о запросе...'
                            ]) ?>
                        </div>
                        
                        <div class="form-group">
                            <?= Html::submitButton('Обновить статус', [
                                'class' => 'btn btn-primary btn-block'
                            ]) ?>
                        </div>
                    </div>
                    <?php ActiveForm::end(); ?>
                </div>
                
                <!-- Navigation -->
                <div class="box box-info">
                    <div class="box-header">
                        <h3 class="box-title">Навигация</h3>
                    </div>
                    <div class="box-body">
                        <a href="<?= Yii::$app->urlManager->createUrl(['/admin/product-request/']) ?>" 
                           class="btn btn-default btn-block">
                            <i class="fa fa-list"></i> Вернуться к списку
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
$(document).ready(function() {
    // Confirmation for dangerous actions
    $('.remove-object').on('click', function(e) {
        if (!confirm('Вы уверены, что хотите удалить этот запрос?')) {
            e.preventDefault();
        }
    });
    
    // Status change confirmation
    $('#status-select').on('change', function() {
        var status = $(this).val();
        var statusText = $(this).find('option:selected').text();
        console.log('Status changed to:', statusText);
    });
});

// Function to copy URL to clipboard
function copyToClipboard(text) {
    // Create a temporary textarea element
    var textarea = document.createElement('textarea');
    textarea.value = text;
    document.body.appendChild(textarea);
    
    // Select and copy the text
    textarea.select();
    textarea.setSelectionRange(0, 99999); // For mobile devices
    
    try {
        var successful = document.execCommand('copy');
        if (successful) {
            // Show success message
            alert('✅ Ссылка скопирована в буфер обмена!');
        } else {
            // Fallback for older browsers
            prompt('Скопируйте ссылку вручную:', text);
        }
    } catch (err) {
        // Fallback for browsers that don't support execCommand
        prompt('Скопируйте ссылку вручную:', text);
    }
    
    // Remove the temporary element
    document.body.removeChild(textarea);
}
</script> 