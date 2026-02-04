<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\models\seller\SellerApplication;
use app\widgets\admin_seller_application_menu\AdminSellerApplicationMenu;

$this->title = 'Заявка продавца #' . $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Заявки продавцов', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="content-wrapper">
    <section class="content-header">
        <h1><?=mb_substr($this->title, 0, 50, 'utf-8');?></h1>
        
        <ol class="breadcrumb">
            <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/'])?>"><i class="fa fa-dashboard"></i> Главная</a></li>
            <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/seller-application/'])?>">Заявки продавцов</a></li>
            <li class="active"><?=mb_substr($this->title, 0, 50, 'utf-8');?></li>
        </ol>
    </section>
    <section class="content">
        <?php if (Yii::$app->session->hasFlash('application_updated')) {?>
            <div class="callout callout-success text-center">
                <?=Yii::$app->session->getFlash('application_updated');?>
            </div>
        <?php }?>
        
        <div class="row">
            <div class="col-md-3">
                <?=AdminSellerApplicationMenu::widget();?>
            </div>
            <div class="col-md-5">
                <div class="box box-info color-palette-box">
                    <div class="box-header">
                        <h3 class="box-title">Информация о заявке</h3>
                    </div>
                    <div class="box-body">
                        <table class="table table-striped">
                            <tr>
                                <td><strong>ID:</strong></td>
                                <td><?= Html::encode($model->id) ?></td>
                            </tr>
                            <tr>
                                <td><strong>Имя:</strong></td>
                                <td><?= Html::encode($model->name) ?></td>
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
                                <td><strong>Статус:</strong></td>
                                <td>
                                    <?php
                                    switch ($model->status) {
                                        case SellerApplication::STATUS_PENDING:
                                            echo '<span class="label label-warning">На рассмотрении</span>';
                                            break;
                                        case SellerApplication::STATUS_APPROVED:
                                            echo '<span class="label label-success">Одобрено</span>';
                                            break;
                                        case SellerApplication::STATUS_REJECTED:
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
                            <?php if ($model->admin_notes): ?>
                            <tr>
                                <td><strong>Заметки администратора:</strong></td>
                                <td><?= Html::encode($model->admin_notes) ?></td>
                            </tr>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="box box-warning">
                    <div class="box-header">
                        <h3 class="box-title">Быстрые действия</h3>
                    </div>
                    <div class="box-body">
                        <div class="btn-group" role="group">
                            <?php if ($model->status != SellerApplication::STATUS_APPROVED): ?>
                                <a href="<?= Yii::$app->urlManager->createUrl(['/admin/seller-application/approve', 'id' => $model->id]) ?>" 
                                   class="btn btn-success">
                                    <i class="fa fa-check"></i> Одобрить заявку
                                </a>
                            <?php endif; ?>
                            
                            <?php if ($model->status != SellerApplication::STATUS_REJECTED): ?>
                                <a href="<?= Yii::$app->urlManager->createUrl(['/admin/seller-application/reject', 'id' => $model->id]) ?>" 
                                   class="btn btn-warning">
                                    <i class="fa fa-times"></i> Отклонить заявку
                                </a>
                            <?php endif; ?>
                            
                            <a href="<?= Yii::$app->urlManager->createUrl(['/admin/seller-application/remove', 'id' => $model->id]) ?>" 
                               class="btn btn-danger remove-object">
                                <i class="fa fa-trash"></i> Удалить заявку
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
                            <label>Статус заявки</label>
                            <?= Html::dropDownList('status', $model->status, SellerApplication::getStatusOptions(), [
                                'class' => 'form-control',
                                'id' => 'status-select'
                            ]) ?>
                        </div>
                        
                        <div class="form-group">
                            <label>Заметки администратора</label>
                            <?= Html::textarea('admin_notes', $model->admin_notes, [
                                'class' => 'form-control',
                                'rows' => 5,
                                'placeholder' => 'Добавьте заметки о заявке...'
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
                        <a href="<?= Yii::$app->urlManager->createUrl(['/admin/seller-application/']) ?>" 
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
        if (!confirm('Вы уверены, что хотите удалить эту заявку?')) {
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
</script> 