<?php
use app\models\user\User;
use app\models\Ikpu;
?>

<?php if (($user->role == User::ROLE_ADMIN) || ($user->role == User::ROLE_MODERATOR)) { ?>
    <div class="box box-info color-palette-box">
        <div class="box-header with-border">
            <h3 class="box-title">Управление ИКПУ</h3>
        </div>
        <div class="box-body">
            <?php if ($model) { ?>
                <div class="info-box bg-aqua">
                    <span class="info-box-icon"><i class="fa fa-tag"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Код ИКПУ</span>
                        <span class="info-box-number"><?= $model->code ?></span>
                    </div>
                </div>
                

                
                <?php if ($model->getChildren()->count() > 0) { ?>
                <div class="info-box bg-yellow">
                    <span class="info-box-icon"><i class="fa fa-sitemap"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Дочерние</span>
                        <span class="info-box-number"><?= $model->getChildren()->count() ?></span>
                    </div>
                </div>
                <?php } ?>
                
                <?php if ($model->getProducts()->count() > 0) { ?>
                <div class="info-box bg-red">
                    <span class="info-box-icon"><i class="fa fa-shopping-cart"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Товары</span>
                        <span class="info-box-number"><?= $model->getProducts()->count() ?></span>
                    </div>
                </div>
                <?php } ?>
            <?php } ?>
            
            <ul class="left-menu">
                <?php if ($model) { ?>
                    <li>
                        <a href="<?=Yii::$app->urlManager->createUrl(['/admin/ikpu/update', 'id'=>$model->id])?>" class="btn btn-primary width-full">
                            <i class="fa fa-pencil"></i> Редактировать
                        </a>
                    </li>
                    <li>
                        <a href="<?=Yii::$app->urlManager->createUrl(['/admin/ikpu/toggle-status', 'id'=>$model->id])?>" class="btn btn-warning width-full">
                            <?php if ($model->status == Ikpu::STATUS_ACTIVE) { ?>
                                <i class="fa fa-lock"></i> Деактивировать
                            <?php } else { ?>
                                <i class="fa fa-unlock"></i> Активировать
                            <?php } ?>
                        </a>
                    </li>
                    <?php if ($model->getChildren()->count() == 0 && $model->getProducts()->count() == 0) { ?>
                    <li>
                        <a href="<?=Yii::$app->urlManager->createUrl(['/admin/ikpu/delete', 'id'=>$model->id])?>" 
                           class="btn btn-danger width-full remove-object"
                           data-confirm="Вы уверены, что хотите удалить этот ИКПУ?"
                           data-method="post">
                            <i class="fa fa-trash"></i> Удалить
                        </a>
                    </li>
                    <?php } ?>
                <?php } ?>
                <li>
                    <a href="<?=Yii::$app->urlManager->createUrl(['/admin/ikpu/create'])?>" class="btn btn-success width-full">
                        <i class="fa fa-plus"></i> Добавить ИКПУ
                    </a>
                </li>
                <li>
                    <a href="<?=Yii::$app->urlManager->createUrl(['/admin/ikpu'])?>" class="btn btn-info width-full">
                        <i class="fa fa-list"></i> Все ИКПУ
                    </a>
                </li>
            </ul>
        </div>
    </div>
    
    <?php if ($model && $model->parent) { ?>
    <div class="box box-default">
        <div class="box-header with-border">
            <h3 class="box-title">Родительский элемент</h3>
        </div>
        <div class="box-body">
            <p><strong>Код:</strong> <?= $model->parent->code ?></p>
            <p><strong>Название:</strong> <?= $model->parent->name_ru ?></p>
            <p>
                <a href="<?=Yii::$app->urlManager->createUrl(['/admin/ikpu/view', 'id'=>$model->parent->id])?>" class="btn btn-sm btn-default">
                    <i class="fa fa-eye"></i> Просмотр
                </a>
            </p>
        </div>
    </div>
    <?php } ?>
    
    <?php if ($model && !$model->isLeaf()) { ?>
    <div class="box box-success">
        <div class="box-header with-border">
            <h3 class="box-title">Быстрые действия</h3>
        </div>
        <div class="box-body">
            <p>
                <a href="<?=Yii::$app->urlManager->createUrl(['/admin/ikpu/create', 'parent_code'=>$model->code])?>" class="btn btn-sm btn-success">
                    <i class="fa fa-plus"></i> Добавить дочерний
                </a>
            </p>
        </div>
    </div>
    <?php } ?>
<?php } ?>
