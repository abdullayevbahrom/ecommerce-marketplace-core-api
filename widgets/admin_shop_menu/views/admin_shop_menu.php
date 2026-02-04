<?php
use app\models\user\User;
?>

<?php if ($user->role == User::ROLE_SHOP) {?>
    <div class="box box-info color-palette-box">
        <div class="box-body">
            <img src="<?=$model->getPhoto('250x250');?>" width="100%" class="img-thumbnail"/>
            <ul class="left-menu">
                <li><a href="<?=Yii::$app->urlManager->createUrl(['/shop/product'])?>" class="btn btn-info width-full"><i class="fa fa-shopping-cart"></i> Товары <?php if (count($model->products) > 0) {?><small class="label bg-blue"><?=count($model->products);?></small><?php }?></a></li>
                <li><a href="<?=Yii::$app->urlManager->createUrl(['/shop/shop-advertising'])?>" class="btn btn-info width-full"><i class="fa fa-bullhorn"></i> Реклама <?php if (count($model->shopAdvertisings) > 0) {?><small class="label bg-blue"><?=count($model->shopAdvertisings);?></small><?php }?></a></li>
                <li><a href="<?=Yii::$app->urlManager->createUrl(['/shop/news'])?>" class="btn btn-info width-full"><i class="fa fa-newspaper-o"></i> Новости <?php if (count($model->news) > 0) {?><small class="label bg-blue"><?=count($model->news);?></small><?php }?></a></li>
                <hr/>
                <li><a href="<?=Yii::$app->urlManager->createUrl(['/shop/shop/create', 'id'=>$model->id])?>" class="btn btn-primary width-full"><i class="fa fa-pencil"></i> Редактировать</a></li>
            </ul>
        </div>
    </div>
<?php }?>

<?php if (($user->role == User::ROLE_ADMIN) || ($user->role == User::ROLE_MODERATOR)) {?>
    <div class="box box-info color-palette-box">
        <div class="box-body">
            <img src="<?=$model->getPhoto('250x250');?>" width="100%" class="img-thumbnail"/>
            <ul class="left-menu">
                <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/shop/managers', 'id'=>$model->id])?>" class="btn btn-info width-full"><i class="fa fa-group"></i> Менеджеры </a></li>
                <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/product', 'ProductSearch[shop_id]'=>$model->id])?>" class="btn btn-info width-full"><i class="fa fa-shopping-cart"></i> Товары <?php if (count($model->products) > 0) {?><small class="label bg-blue"><?=count($model->products);?></small><?php }?></a></li>
                <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/shop-advertising', 'ShopAdvertisingSearch[shop_id]'=>$model->id])?>" class="btn btn-info width-full"><i class="fa fa-bullhorn"></i> Реклама <?php if (count($model->shopAdvertisings) > 0) {?><small class="label bg-blue"><?=count($model->shopAdvertisings);?></small><?php }?></a></li>
                <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/news', 'NewsSearch[shop_id]'=>$model->id])?>" class="btn btn-info width-full"><i class="fa fa-newspaper-o"></i> Новости <?php if (count($model->news) > 0) {?><small class="label bg-blue"><?=count($model->news);?></small><?php }?></a></li>
                <hr/>
                <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/shop/lock', 'id'=>$model->id])?>" class="btn btn-warning width-full"><?php if ($model->status == 1) {?><i class="fa fa-lock"></i> Заблокировать<?php } else {?><i class="fa fa-unlock"></i> Разблокировать<?php }?></a></li>
                <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/shop/create', 'id'=>$model->id])?>" class="btn btn-primary width-full"><i class="fa fa-pencil"></i> Редактировать</a></li>
                <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/shop/remove', 'id'=>$model->id])?>" class="btn btn-danger width-full remove-object"><i class="fa fa-trash"></i> Удалить</a></li>
            </ul>
        </div>
    </div>
<?php }?>

<?php if ($user->role == User::ROLE_LOGIST) {?>
    <div class="box box-info color-palette-box">
        <div class="box-body">
            <img src="<?=$model->getPhoto('250x250');?>" width="100%" class="img-thumbnail"/>
        </div>
    </div>
<?php }?>