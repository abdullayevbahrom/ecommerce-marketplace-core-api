<?php
use app\models\user\User;
use app\models\File;
?>

<?php if ($user->role == User::ROLE_SHOP) {?>
    <div class="box box-info color-palette-box">
        <div class="box-body">
            <img src="<?=File::FILE_DEFAULT;?>" width="100%" class="img-thumbnail"/>
            <ul class="left-menu">
                <li><a href="<?=Yii::$app->urlManager->createUrl(['/shop/shop-document/lock', 'id'=>$model->id])?>" class="btn btn-warning width-full"><?php if ($model->status == 1) {?><i class="fa fa-lock"></i> Заблокировать<?php } else {?><i class="fa fa-unlock"></i> Разблокировать<?php }?></a></li>
                <li><a href="<?=Yii::$app->urlManager->createUrl(['/shop/shop-document/create', 'id'=>$model->id])?>" class="btn btn-primary width-full"><i class="fa fa-pencil"></i> Редактировать</a></li>
                <li><a href="<?=Yii::$app->urlManager->createUrl(['/shop/shop-document/remove', 'id'=>$model->id])?>" class="btn btn-danger width-full remove-object"><i class="fa fa-trash"></i> Удалить</a></li>
            </ul>
        </div>
    </div>
<?php }?>

<?php if (($user->role == User::ROLE_ADMIN) || ($user->role == User::ROLE_MODERATOR)) {?>
    <div class="box box-info color-palette-box">
        <div class="box-body">
            <img src="<?=File::FILE_DEFAULT;?>" width="100%" class="img-thumbnail"/>
            <ul class="left-menu">
                <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/shop-document/lock', 'id'=>$model->id])?>" class="btn btn-warning width-full"><?php if ($model->status == 1) {?><i class="fa fa-lock"></i> Заблокировать<?php } else {?><i class="fa fa-unlock"></i> Разблокировать<?php }?></a></li>
                <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/shop-document/create', 'id'=>$model->id])?>" class="btn btn-primary width-full"><i class="fa fa-pencil"></i> Редактировать</a></li>
                <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/shop-document/remove', 'id'=>$model->id])?>" class="btn btn-danger width-full remove-object"><i class="fa fa-trash"></i> Удалить</a></li>
            </ul>
        </div>
    </div>
<?php }?>