<?php
use yii\helpers\Html;

use app\models\user\User;
?>

<?php if (($model->role == User::ROLE_ADMIN) || ($model->role == User::ROLE_MODERATOR)) {?>
    <div class="box box-info color-palette-box">
        <div class="box-body">
            <img src="<?=$model->getPhoto('250x250');?>" width="100%" class="img-thumbnail"/>
            <ul class="left-menu">
                <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/default/update-profile'])?>" class="btn btn-primary width-full">Редактировать</a></li>
                <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/default/change-password'])?>" class="btn btn-primary width-full">Сменить пароль</a></li>
            </ul>
        </div>
    </div>
<?php }?>

<?php if ($model->role == User::ROLE_SHOP) {?>
    <div class="box box-info color-palette-box">
        <div class="box-body">
            <img src="<?=$model->getPhoto('250x250');?>" width="100%" class="img-thumbnail"/>
            <ul class="left-menu">
                <li><a href="<?=Yii::$app->urlManager->createUrl(['/shop/default/update-profile'])?>" class="btn btn-primary width-full">Редактировать</a></li>
                <li><a href="<?=Yii::$app->urlManager->createUrl(['/shop/default/change-password'])?>" class="btn btn-primary width-full">Сменить пароль</a></li>
            </ul>
        </div>
    </div>
<?php }?>

<?php if ($model->role == User::ROLE_LOGIST) {?>
    <div class="box box-info color-palette-box">
        <div class="box-body">
            <img src="<?=$model->getPhoto('250x250');?>" width="100%" class="img-thumbnail"/>
            <ul class="left-menu">
                <li><a href="<?=Yii::$app->urlManager->createUrl(['/logist/default/update-profile'])?>" class="btn btn-primary width-full">Редактировать</a></li>
                <li><a href="<?=Yii::$app->urlManager->createUrl(['/logist/default/change-password'])?>" class="btn btn-primary width-full">Сменить пароль</a></li>
            </ul>
        </div>
    </div>
<?php }?>