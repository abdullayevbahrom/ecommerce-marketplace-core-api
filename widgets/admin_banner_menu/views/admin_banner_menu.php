<div class="box box-info color-palette-box">
    <div class="box-body">
        <ul class="left-menu">
            <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/banner/lock', 'id'=>$model->id])?>" class="btn btn-warning width-full"><?php if ($model->status == 1) {?><i class="fa fa-lock"></i> Заблокировать<?php } else {?><i class="fa fa-unlock"></i> Разблокировать<?php }?></a></li>
            <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/banner/create', 'id'=>$model->id])?>" class="btn btn-primary width-full"><i class="fa fa-pencil"></i> Редактировать</a></li>
            <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/banner/remove', 'id'=>$model->id])?>" class="btn btn-danger width-full remove-object"><i class="fa fa-trash"></i> Удалить</a></li>
        </ul>
    </div>
</div>