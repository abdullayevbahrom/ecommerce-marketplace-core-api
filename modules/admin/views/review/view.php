<?php
use yii\helpers\Html;
use app\models\product\review\ProductReview;

$this->title = 'Детали отзыва #' . $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Отзывы', 'url' => ['/admin/review']];
$this->params['breadcrumbs'][] = $this->title;

// Custom CSS for beautiful design
$this->registerCss("
    .review-header {
        background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
        color: white;
        padding: 30px;
        border-radius: 15px 15px 0 0;
        margin-bottom: 0;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    }
    .review-header h1 {
        margin: 0;
        font-size: 28px;
        font-weight: 600;
    }
    .review-header .subtitle {
        opacity: 0.9;
        margin-top: 5px;
        font-size: 14px;
    }
    .review-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 8px 30px rgba(0,0,0,0.08);
        border: none;
        overflow: hidden;
        margin-bottom: 20px;
        transition: transform 0.2s ease;
    }
    .review-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 40px rgba(0,0,0,0.12);
    }
    .card-section {
        padding: 25px 30px;
    }
    .section-title {
        font-size: 18px;
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #ecf0f1;
        display: flex;
        align-items: center;
    }
    .section-title i {
        margin-right: 10px;
        color: #3498db;
        width: 20px;
    }
    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 25px;
    }
    .info-item {
        background: #f8f9fa;
        padding: 15px 20px;
        border-radius: 10px;
        border-left: 4px solid #3498db;
    }
    .info-label {
        font-size: 12px;
        text-transform: uppercase;
        color: #7f8c8d;
        font-weight: 600;
        letter-spacing: 0.5px;
        margin-bottom: 5px;
    }
    .info-value {
        font-size: 16px;
        color: #2c3e50;
        font-weight: 500;
    }
    .status-form {
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        padding: 25px;
        border-radius: 12px;
        margin-top: 20px;
    }
    .form-group-enhanced {
        margin-bottom: 20px;
    }
    .form-group-enhanced label {
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 8px;
        display: block;
        font-size: 14px;
    }
    .form-control-enhanced {
        border: 2px solid #e9ecef;
        border-radius: 8px;
        padding: 12px 15px;
        font-size: 14px;
        transition: all 0.3s ease;
        background: white;
        color: #2c3e50;
    }
    .form-control-enhanced:focus {
        border-color: #3498db;
        box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        outline: none;
    }
    .form-control-enhanced option {
        color: #2c3e50;
        background: white;
        padding: 8px;
    }
    .btn-save {
        background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
        border: none;
        padding: 12px 30px;
        border-radius: 25px;
        color: white;
        font-weight: 600;
        transition: all 0.3s ease;
        font-size: 14px;
    }
    .btn-save:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(52, 152, 219, 0.3);
        color: white;
    }
    .status-badge {
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .rating-stars {
        color: #f39c12;
        font-size: 18px;
        margin-left: 10px;
    }
    .review-content {
        background: #ffffff;
        padding: 25px;
        border-radius: 12px;
        border: 2px solid #ecf0f1;
        font-size: 15px;
        line-height: 1.6;
        color: #2c3e50;
        margin-top: 15px;
    }
    .metadata-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
        padding-bottom: 15px;
        border-bottom: 1px solid #ecf0f1;
    }
    .user-info, .product-info {
        display: flex;
        align-items: center;
    }
    .user-info i, .product-info i {
        margin-right: 8px;
        color: #3498db;
    }
");
?>

<div class="content-wrapper">
    <section class="content-header">
        <h1>Отзывы</h1>
        <ol class="breadcrumb">
            <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/'])?>"><i class="fa fa-dashboard"></i> Главная</a></li>
            <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/review/'])?>">Отзывы</a></li>
            <li class="active">Детали отзыва #<?=$model->id?></li>
        </ol>
    </section>

    <section class="content">
        <?php if (Yii::$app->session->hasFlash('success')): ?>
            <div class="alert alert-success alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                <h4><i class="icon fa fa-check"></i> Успешно!</h4>
                <?= Yii::$app->session->getFlash('success') ?>
            </div>
        <?php endif; ?>
        
        <?php if (Yii::$app->session->hasFlash('error')): ?>
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                <h4><i class="icon fa fa-ban"></i> Ошибка!</h4>
                <?= Yii::$app->session->getFlash('error') ?>
            </div>
        <?php endif; ?>
        
        <!-- Review Overview Card -->
        <div class="review-card">
            <div class="card-section">
                <div class="section-title">
                    <i class="fa fa-info-circle"></i>
                    Информация об отзыве
                </div>
                
                <div class="metadata-row">
                    <div class="user-info">
                        <i class="fa fa-user"></i>
                        <strong>Пользователь:</strong>
                        <?=($model->user && $model->user->name) ? '<a href="'.Yii::$app->urlManager->createUrl(['/admin/user/view', 'id'=>$model->user->id]).'" style="margin-left: 8px; color: #3498db;">'.$model->user->name.'</a>' : '<span style="color: #e74c3c; margin-left: 8px;">Пользователь удален</span>';?>
                    </div>
                    <div class="product-info">
                        <i class="fa fa-shopping-box"></i>
                        <strong>Товар:</strong>
                        <?=($model->product && $model->product->name_ru) ? '<a href="'.Yii::$app->urlManager->createUrl(['/admin/product/view', 'id'=>$model->product->id]).'" style="margin-left: 8px; color: #3498db;">'.$model->product->name_ru.'</a>' : '<span style="color: #e74c3c; margin-left: 8px;">Товар удален</span>';?>
                    </div>
                </div>
                
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">ID Отзыва</div>
                        <div class="info-value">#<?=$model->id;?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Рейтинг</div>
                        <div class="info-value">
                            <?php if ($model->rate): ?>
                                <?=$model->rate;?> / 5
                                <span class="rating-stars">
                                    <?php for($i = 1; $i <= 5; $i++): ?>
                                        <i class="fa fa-star<?= $i <= $model->rate ? '' : '-o' ?>"></i>
                                    <?php endfor; ?>
                                </span>
                            <?php else: ?>
                                Не указан
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Текущий статус</div>
                        <div class="info-value">
                            <span class="status-badge label-<?=$model->getStatusColor();?>"><?=$model->getStatusLabel();?></span>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Дата создания</div>
                        <div class="info-value"><?=$model->date ? date('d.m.Y H:i', strtotime($model->date)) : 'Не указана';?></div>
                    </div>
                    
                    <?php if ($model->status_date): ?>
                    <div class="info-item">
                        <div class="info-label">Дата изменения статуса</div>
                        <div class="info-value"><?=date('d.m.Y H:i', strtotime($model->status_date));?></div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($model->statusUser): ?>
                    <div class="info-item">
                        <div class="info-label">Статус изменен</div>
                        <div class="info-value">
                            <a href="<?=Yii::$app->urlManager->createUrl(['/admin/user/view', 'id'=>$model->statusUser->id]);?>" style="color: #3498db;">
                                <?=$model->statusUser->name;?>
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Status Management Form -->
                <div class="status-form">
                    <h4 style="color: #2c3e50; margin-bottom: 20px; font-weight: 600;">
                        <i class="fa fa-cogs" style="margin-right: 10px; color: #3498db;"></i>
                        Управление статусом
                    </h4>
                    
                    <?= Html::beginForm(['/admin/review/update-status', 'id' => $model->id], 'post') ?>
                        <div class="form-group-enhanced">
                            <label>Изменить статус:</label>
                            <?= Html::dropDownList('status', $model->status, ProductReview::getStatusLabels(), [
                                'class' => 'form-control form-control-enhanced',
                                'style' => 'width: 100%; max-width: 300px; height: 48px;'
                            ]) ?>
                        </div>
                        
                        <div class="form-group-enhanced">
                            <label>Комментарий администратора:</label>
                            <?= Html::textarea('comment', $model->status_comment, [
                                'class' => 'form-control form-control-enhanced',
                                'rows' => 4,
                                'placeholder' => 'Введите комментарий к изменению статуса...',
                                'style' => 'width: 100%; resize: vertical;'
                            ]) ?>
                            <?php if ($model->status_comment): ?>
                                <small class="text-muted" style="margin-top: 5px; display: block;">
                                    <i class="fa fa-info-circle"></i> Текущий комментарий отображается в поле выше
                                </small>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group-enhanced">
                            <?= Html::submitButton('<i class="fa fa-save"></i> Сохранить изменения', [
                                'class' => 'btn btn-save',
                                'confirm' => 'Вы уверены, что хотите изменить статус этого отзыва?'
                            ]) ?>
                        </div>
                    <?= Html::endForm() ?>
                </div>
            </div>
        </div>
        
        <!-- Review Content Card -->
        <div class="review-card">
            <div class="card-section">
                <div class="section-title">
                    <i class="fa fa-comment"></i>
                    Содержание отзыва
                </div>
                
                <?php if ($model->review): ?>
                    <div class="review-content">
                        <i class="fa fa-quote-left" style="color: #bdc3c7; font-size: 24px; margin-bottom: 15px;"></i>
                        <div style="margin: 15px 0;">
                            <?=nl2br(Html::encode($model->review));?>
                        </div>
                        <i class="fa fa-quote-right" style="color: #bdc3c7; font-size: 24px; float: right; margin-top: 15px;"></i>
                        <div style="clear: both;"></div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning" style="margin-top: 15px;">
                        <i class="fa fa-exclamation-triangle"></i>
                        <strong>Внимание!</strong> Текст отзыва отсутствует или был удален.
                    </div>
                <?php endif; ?>
                
                <?php if ($model->status_comment): ?>
                <div style="margin-top: 25px;">
                    <h5 style="color: #2c3e50; font-weight: 600; margin-bottom: 15px;">
                        <i class="fa fa-user-shield" style="margin-right: 8px; color: #3498db;"></i>
                        Комментарий администратора
                    </h5>
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; border-left: 4px solid #3498db;">
                        <?=nl2br(Html::encode($model->status_comment));?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
</div>