<?php
use app\models\user\User;
?>

<?php if (($user->role == User::ROLE_ADMIN) || ($user->role == User::ROLE_MODERATOR)) {?>
    <div class="btn-group">
        <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown">
            <span class="fa fa-cog"></span>
        </button>
        <ul class="dropdown-menu pull-right">
            <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/user/view', 'id'=>$model->id]);?>">Пользователь</a></li>
            <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/user/cards', 'id'=>$model->id]);?>">Карты</a></li>
        </ul>
    </div>
<?php }?>