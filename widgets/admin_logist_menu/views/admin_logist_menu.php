<?php
use app\models\user\User;
?>

<?php if ($user->role == User::ROLE_SHOP) {?>
    <div class="box box-info color-palette-box">
        <div class="box-body">
            <img src="<?=$model->getPhoto('250x250');?>" width="100%" class="img-thumbnail"/>
            <ul class="left-menu">
                <li><a href="<?=Yii::$app->urlManager->createUrl(['/shop/logist/view', 'id'=>$model->id])?>" class="btn btn-info width-full"><i class="fa fa-truck"></i> Компания</a></li>
                <li><a href="<?=Yii::$app->urlManager->createUrl(['/shop/logist/tariff', 'id'=>$model->id])?>" class="btn btn-info width-full"><i class="fa fa-star"></i> Регионы/Тарифы</a></li>
            </ul>
        </div>
    </div>
<?php }?>

<?php if ($user->role == User::ROLE_LOGIST) {?>
    <div class="box box-info color-palette-box">
        <div class="box-body">
            <img src="<?=$model->getPhoto('250x250');?>" width="100%" class="img-thumbnail"/>
            <ul class="left-menu">
                <li><a href="<?=Yii::$app->urlManager->createUrl(['/logist/logist/view'])?>" class="btn btn-info width-full"><i class="fa fa-truck"></i> Компания</a></li>
                <li><a href="<?=Yii::$app->urlManager->createUrl(['/logist/logist/create'])?>" class="btn btn-info width-full"><i class="fa fa-pencil"></i> Редактировать</a></li>
                <li><a href="<?=Yii::$app->urlManager->createUrl(['/logist/logist/tariff'])?>" class="btn btn-info width-full"><i class="fa fa-star"></i> Регионы/Тарифы</a></li>
            </ul>
        </div>
    </div>
<?php }?>

<?php if (($user->role == User::ROLE_ADMIN) || ($user->role == User::ROLE_MODERATOR)) {?>
    <div class="box box-info color-palette-box">
        <div class="box-body">
            <img src="<?=$model->getPhoto('250x250');?>" width="100%" class="img-thumbnail"/>
            <ul class="left-menu">
                <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/logist/view', 'id'=>$model->id])?>" class="btn btn-info width-full"><i class="fa fa-truck"></i> Компания</a></li>
                <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/logist/tariff', 'id'=>$model->id])?>" class="btn btn-info width-full"><i class="fa fa-star"></i> Регионы/Тарифы</a></li>
                <hr/>
                <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/logist/lock', 'id'=>$model->id])?>" class="btn btn-warning width-full"><?php if ($model->status == 1) {?><i class="fa fa-lock"></i> Заблокировать<?php } else {?><i class="fa fa-unlock"></i> Разблокировать<?php }?></a></li>
                <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/logist/create', 'id'=>$model->id])?>" class="btn btn-primary width-full"><i class="fa fa-pencil"></i> Редактировать</a></li>
                <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/logist/remove', 'id'=>$model->id])?>" class="btn btn-danger width-full remove-object"><i class="fa fa-trash"></i> Удалить</a></li>
            </ul>
        </div>
    </div>
<?php }?>