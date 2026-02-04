<?php 
use app\models\seller\SellerApplication;
?>

<div class="box box-info color-palette-box">
    <div class="box-body">
        <ul class="left-menu">
            <?php if ($model && $model->status != SellerApplication::STATUS_APPROVED): ?>
            <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/seller-application/approve', 'id'=>$model->id])?>" class="btn btn-success width-full"><i class="fa fa-check"></i> Одобрить заявку</a></li>
            <?php endif; ?>
            
            <?php if ($model && $model->status != SellerApplication::STATUS_REJECTED): ?>
            <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/seller-application/reject', 'id'=>$model->id])?>" class="btn btn-warning width-full"><i class="fa fa-times"></i> Отклонить заявку</a></li>
            <?php endif; ?>
            
            <?php if ($model): ?>
            <li><a href="tel:<?= htmlspecialchars($model->phone) ?>" class="btn btn-info width-full"><i class="fa fa-phone"></i> Позвонить</a></li>
            <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/seller-application/remove', 'id'=>$model->id])?>" class="btn btn-danger width-full remove-object"><i class="fa fa-trash"></i> Удалить заявку</a></li>
            <?php endif; ?>
            
            <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/seller-application/'])?>" class="btn btn-default width-full"><i class="fa fa-list"></i> Список заявок</a></li>
        </ul>
    </div>
</div> 