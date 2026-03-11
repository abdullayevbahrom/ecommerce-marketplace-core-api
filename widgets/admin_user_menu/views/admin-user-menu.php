<?php
use yii\helpers\Html;
use yii\helpers\Url;
use app\models\user\User;
?>

<?php if (($user->role == User::ROLE_ADMIN) || ($user->role == User::ROLE_MODERATOR)) {?>
    <!-- Profile Image & Status -->
    <div class="box box-primary">
        <div class="box-body box-profile">
            <div class="text-center">
                <img src="<?=$model->getPhoto('250x250');?>" width="100%" class="img-thumbnail"/>
            </div>

            <h3 class="profile-username text-center"><?= Html::encode(trim($model->lastname . ' ' . $model->name . ' ' . $model->middlename)) ?: 'Без имени' ?></h3>
            <p class="text-muted text-center"><?= Html::encode($model->organization_name ?: ($model->type == 'yur' ? 'Юридическое лицо' : 'Физическое лицо')) ?></p>

            <!-- Role Badge -->
            <div class="text-center" style="margin-bottom: 10px;">
                <?= $model->getRoleBadge() ?>
            </div>

            <ul class="list-group list-group-unbordered">
                <li class="list-group-item">
                    <b>ID</b> <span class="pull-right text-muted">#<?= $model->id ?></span>
                </li>
                <li class="list-group-item">
                    <b>Телефон</b> <span class="pull-right"><?= Html::encode($model->phone ?: '—') ?></span>
                </li>
                <li class="list-group-item">
                    <b>Тип</b>
                    <span class="pull-right">
                        <?php if ($model->type == 'yur'): ?>
                            <small class="label bg-black">Юр. лицо</small>
                        <?php else: ?>
                            <small class="label bg-aqua">Физ. лицо</small>
                        <?php endif; ?>
                    </span>
                </li>
                <li class="list-group-item">
                    <b>Заказов</b> <a class="pull-right badge bg-blue"><?= $orderCount ?></a>
                </li>
                <li class="list-group-item">
                    <b>Потрачено</b> <a class="pull-right text-green text-bold"><?= number_format($totalSpent, 0, '.', ' ') ?> сум</a>
                </li>
                <li class="list-group-item">
                    <b>Транзакций</b> <a class="pull-right badge bg-yellow"><?= $transactionCount ?></a>
                </li>
                <?php if ($model->eimzo_tax_id): ?>
                    <li class="list-group-item">
                        <b>ИНН (E-IMZO)</b> <span class="pull-right"><small class="label bg-blue"><?= Html::encode($model->eimzo_tax_id) ?></small></span>
                    </li>
                <?php endif; ?>
                <?php if ($model->balance): ?>
                    <li class="list-group-item">
                        <b>Баланс</b> <span class="pull-right text-green text-bold"><?= number_format((float)$model->balance, 0, '.', ' ') ?> сум</span>
                    </li>
                <?php endif; ?>
            </ul>

            <div class="text-center" style="margin-top: 10px;">
                <?php
                switch ($model->status) {
                    case 0:
                        echo '<span class="label label-warning btn-block">На модерации</span>';
                        break;
                    case 1:
                        echo '<span class="label label-success btn-block">Активен</span>';
                        break;
                    case 2:
                        echo '<span class="label label-danger btn-block">Заблокирован</span>';
                        break;
                }
                ?>
            </div>
        </div>
    </div>

    <!-- Role Change (Admin Only) -->
    <?php if ($user->role == User::ROLE_ADMIN && $model->id !== $user->id): ?>
        <div class="box box-warning">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-user-secret"></i> Смена роли</h3>
            </div>
            <div class="box-body">
                <?= Html::beginForm(['/admin/user/change-role', 'id' => $model->id], 'post') ?>
                    <div class="form-group">
                        <select name="role" class="form-control">
                            <?php foreach (User::ROLE_LABELS as $roleId => $roleLabel): ?>
                                <option value="<?= $roleId ?>" <?= $model->role == $roleId ? 'selected' : '' ?>><?= $roleLabel ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?= Html::submitButton('<i class="fa fa-save"></i> Изменить роль', ['class' => 'btn btn-warning btn-block', 'data-confirm' => 'Вы уверены, что хотите изменить роль этого пользователя?']) ?>
                <?= Html::endForm() ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Actions Box -->
    <div class="box box-info color-palette-box">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-cogs"></i> Действия</h3>
        </div>
        <div class="box-body">
            <ul class="list-unstyled" style="padding: 0; margin: 0;">
                <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/user/orders', 'id'=>$model->id])?>" class="btn btn-info btn-block"><i class="fa fa-star"></i> Заказы <?php if ($orders_count > 0) {?><small class="label bg-red"><?=$orders_count;?></small><?php }?></a></li>
                <li style="margin-top: 5px;"><a href="<?=Yii::$app->urlManager->createUrl(['/admin/user/lock', 'id'=>$model->id])?>" class="btn btn-warning btn-block"><?php if ($model->status == 1) {?><i class="fa fa-lock"></i> Заблокировать<?php } else {?><i class="fa fa-unlock"></i> Разблокировать<?php }?></a></li>
                <li style="margin-top: 5px;"><a href="<?=Yii::$app->urlManager->createUrl(['/admin/user/create', 'id'=>$model->id])?>" class="btn btn-primary btn-block"><i class="fa fa-pencil"></i> Редактировать</a></li>
                <li style="margin-top: 5px;"><a href="<?=Yii::$app->urlManager->createUrl(['/admin/user/remove', 'id'=>$model->id])?>" class="btn btn-danger btn-block remove-object"><i class="fa fa-trash"></i> Удалить</a></li>
            </ul>
        </div>
    </div>

    <!-- Quick Stats Card -->
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-bar-chart"></i> Активность</h3>
        </div>
        <div class="box-body no-padding">
            <ul class="nav nav-pills nav-stacked">
                <li><a href="#"><i class="fa fa-shopping-cart text-green"></i> Корзина <span class="pull-right text-green"><?= $cartItemCount ?></span></a></li>
                <li><a href="#"><i class="fa fa-heart text-red"></i> Избранное <span class="pull-right text-red"><?= $favoriteCount ?></span></a></li>
                <li><a href="#"><i class="fa fa-balance-scale text-blue"></i> Сравнение <span class="pull-right text-blue"><?= $compareCount ?></span></a></li>
                <li><a href="#"><i class="fa fa-credit-card text-yellow"></i> Карты <span class="pull-right text-yellow"><?= $cardCount ?></span></a></li>
            </ul>
        </div>
    </div>
<?php }?>

<?php if ($user->role == User::ROLE_SHOP) {?>
    <div class="box box-info color-palette-box">
        <div class="box-body">
            <img src="<?=$model->getPhoto('250x250');?>" width="100%" class="img-thumbnail"/>
            <ul class="list-unstyled" style="padding: 0; margin: 0; margin-top: 15px;">
                <li><a href="<?=Yii::$app->urlManager->createUrl(['/shop/user/orders', 'id'=>$model->id])?>" class="btn btn-info btn-block"><i class="fa fa-star"></i> Заказы <?php if ($orders_count > 0) {?><small class="label bg-red"><?=$orders_count;?></small><?php }?></a></li>
                <li style="margin-top: 5px;"><a href="<?=Yii::$app->urlManager->createUrl(['/shop/user/lock', 'id'=>$model->id])?>" class="btn btn-warning btn-block"><?php if ($model->status == 1) {?><i class="fa fa-lock"></i> Заблокировать<?php } else {?><i class="fa fa-unlock"></i> Разблокировать<?php }?></a></li>
                <li style="margin-top: 5px;"><a href="<?=Yii::$app->urlManager->createUrl(['/shop/user/create', 'id'=>$model->id])?>" class="btn btn-primary btn-block"><i class="fa fa-pencil"></i> Редактировать</a></li>
                <li style="margin-top: 5px;"><a href="<?=Yii::$app->urlManager->createUrl(['/shop/user/remove', 'id'=>$model->id])?>" class="btn btn-danger btn-block remove-object"><i class="fa fa-trash"></i> Удалить</a></li>
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
