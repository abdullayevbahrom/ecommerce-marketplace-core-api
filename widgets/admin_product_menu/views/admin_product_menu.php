<?php
use app\models\user\User;
?>

<?php if ($user->role == User::ROLE_SHOP) {?>
    <div class="box box-info color-palette-box">
        <div class="box-body">
            <img src="<?=$model->getPhoto('250x250');?>" width="100%" class="img-thumbnail"/>
            <ul class="left-menu">
                <li><a href="<?=Yii::$app->urlManager->createUrl(['/shop/review', 'ProductReviewSearch[product_id]'=>$model->id])?>" class="btn btn-info width-full"><i class="fa fa-commenting"></i> Отзывы</a></li>
                <?php if ($user->manager != 1) {?>
                    <li><a href="<?=Yii::$app->urlManager->createUrl(['/shop/product/lock', 'id'=>$model->id])?>" class="btn btn-warning width-full"><?php if ($model->status == 1) {?><i class="fa fa-lock"></i> Заблокировать<?php } else {?><i class="fa fa-unlock"></i> Разблокировать<?php }?></a></li>
                    <li><a href="<?=Yii::$app->urlManager->createUrl(['/shop/product/create', 'id'=>$model->id])?>" class="btn btn-primary width-full"><i class="fa fa-pencil"></i> Редактировать</a></li>
                    <li><a href="<?=Yii::$app->urlManager->createUrl(['/shop/product/removes', 'id'=>$model->id])?>" class="btn btn-danger width-full remove-object"><i class="fa fa-trash"></i> Удалить</a></li>
                <?php }?>
            </ul>
        </div>
    </div>
<?php }?>

<?php if (($user->role == User::ROLE_ADMIN) || ($user->role == User::ROLE_MODERATOR)) {
    $page = isset($_GET['page']) ? $_GET['page'] :1;?>
    <div class="box box-info color-palette-box">
        <div class="box-body">
            <img src="<?=$model->getPhoto('250x250');?>" width="100%" class="img-thumbnail"/>
            <ul class="left-menu">
                <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/review', 'ProductReviewSearch[product_id]'=>$model->id])?>" class="btn btn-info width-full"><i class="fa fa-commenting"></i> Отзывы</a></li>
                <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/product/lock', 'id'=>$model->id])?>" class="btn btn-warning width-full"><?php if ($model->status == 1) {?><i class="fa fa-lock"></i> Заблокировать<?php } else {?><i class="fa fa-unlock"></i> Разблокировать<?php }?></a></li>
                <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/product/create', 'id'=>$model->id])?>" class="btn btn-primary width-full"><i class="fa fa-pencil"></i> Редактировать</a></li>
                <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/product/check-asl-belgisi', 'id'=>$model->id])?>" class="btn btn-success width-full"><i class="fa fa-check-circle"></i> Проверить ASL Belgisi</a></li>
                <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/product/removes', 'id'=>$model->id, 'page'=>$page])?>" class="btn btn-danger width-full remove-object"><i class="fa fa-trash"></i> Удалить</a></li>
            </ul>
        </div>
    </div>
<?php }?>