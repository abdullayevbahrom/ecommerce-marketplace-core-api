<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use app\widgets\admin_ikpu_menu\AdminIkpuMenu;

$this->title = 'ИКПУ: ' . $model->code;
$this->params['breadcrumbs'][] = ['label' => 'ИКПУ', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="content-wrapper">
    <section class="content-header">
        <h1><?= Html::encode($this->title) ?></h1>
        
        <ol class="breadcrumb">
            <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/'])?>"><i class="fa fa-dashboard"></i> Home</a></li>
            <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/ikpu'])?>">ИКПУ</a></li>
            <li class="active"><?= Html::encode($model->code) ?></li>
        </ol>
    </section>
    
    <section class="content">
        <?php if (Yii::$app->session->hasFlash('ikpu_saved')) {?>
            <div class="callout callout-success text-center">
                <?=Yii::$app->session->getFlash('ikpu_saved');?>
            </div>
        <?php }?>
        <?php if (Yii::$app->session->hasFlash('ikpu_error')) {?>
            <div class="callout callout-danger text-center">
                <?=Yii::$app->session->getFlash('ikpu_error');?>
            </div>
        <?php }?>
        
        <div class="row">
            <div class="col-md-9">
                <div class="box box-info color-palette-box">
                    <div class="box-header with-border">
                        <h3 class="box-title">Информация об ИКПУ</h3>
                    </div>
                    <div class="box-body">
                        <?= DetailView::widget([
                            'model' => $model,
                            'options' => ['class' => 'table table-striped table-bordered detail-view'],
                            'attributes' => [
                                'code:text:Код ИКПУ',
                                'name_ru:text:Название (Русский)',
                                'name_uz:text:Название (Узбекский)',
                                'name_en:text:Название (Английский)',
                                [
                                    'attribute' => 'parent_code',
                                    'label' => 'Родительский код',
                                    'value' => function($model) {
                                        if ($model->parent) {
                                            return Html::a($model->parent->code, ['view', 'id' => $model->parent->id]);
                                        }
                                        return 'Корневой элемент';
                                    },
                                    'format' => 'html'
                                ],

                                [
                                    'attribute' => 'status',
                                    'label' => 'Статус',
                                    'value' => function($model) {
                                        $class = $model->status == \app\models\Ikpu::STATUS_ACTIVE ? 'label-success' : 'label-default';
                                        return '<span class="label ' . $class . '">' . $model->getStatusLabel() . '</span>';
                                    },
                                    'format' => 'html'
                                ],
                                'created_at:datetime:Создано',
                                'updated_at:datetime:Обновлено',
                            ],
                        ]) ?>
                    </div>
                </div>
                
                <!-- IKPU Code Structure Visualization -->
                <?php if (strlen($model->code) === 17) { ?>
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title">Структура кода ИКПУ</h3>
                    </div>
                    <div class="box-body">
                        <div style="font-family: monospace; font-size: 16px; text-align: center; padding: 15px; background: #f9f9f9; border-radius: 4px;">
                            <strong>
                                <span style="color: #d73925; background: #ffeaea; padding: 3px 5px; margin: 1px; border-radius: 3px;" title="Группа"><?= substr($model->code, 0, 2) ?></span>
                                <span style="color: #00a65a; background: #eafaf1; padding: 3px 5px; margin: 1px; border-radius: 3px;" title="Класс"><?= substr($model->code, 2, 2) ?></span>
                                <span style="color: #3c8dbc; background: #eaf4fd; padding: 3px 5px; margin: 1px; border-radius: 3px;" title="Позиция"><?= substr($model->code, 4, 3) ?></span>
                                <span style="color: #f39c12; background: #fef9e7; padding: 3px 5px; margin: 1px; border-radius: 3px;" title="Субпозиция"><?= substr($model->code, 7, 3) ?></span>
                                <span style="color: #605ca8; background: #f0eeff; padding: 3px 5px; margin: 1px; border-radius: 3px;" title="Бренд"><?= substr($model->code, 10, 3) ?></span>
                                <span style="color: #dd4b39; background: #fdeaea; padding: 3px 5px; margin: 1px; border-radius: 3px;" title="Атрибут"><?= substr($model->code, 13, 4) ?></span>
                            </strong>
                        </div>
                        <div class="row" style="margin-top: 15px; font-size: 12px;">
                            <div class="col-md-2 text-center">
                                <span style="color: #d73925;">■</span> <strong>Группа</strong><br>
                                <small><?= substr($model->code, 0, 2) ?></small>
                            </div>
                            <div class="col-md-2 text-center">
                                <span style="color: #00a65a;">■</span> <strong>Класс</strong><br>
                                <small><?= substr($model->code, 2, 2) ?></small>
                            </div>
                            <div class="col-md-2 text-center">
                                <span style="color: #3c8dbc;">■</span> <strong>Позиция</strong><br>
                                <small><?= substr($model->code, 4, 3) ?></small>
                            </div>
                            <div class="col-md-2 text-center">
                                <span style="color: #f39c12;">■</span> <strong>Субпозиция</strong><br>
                                <small><?= substr($model->code, 7, 3) ?></small>
                            </div>
                            <div class="col-md-2 text-center">
                                <span style="color: #605ca8;">■</span> <strong>Бренд</strong><br>
                                <small><?= substr($model->code, 10, 3) ?></small>
                            </div>
                            <div class="col-md-2 text-center">
                                <span style="color: #dd4b39;">■</span> <strong>Атрибут</strong><br>
                                <small><?= substr($model->code, 13, 4) ?></small>
                            </div>
                        </div>
                    </div>
                </div>
                <?php } ?>
                
                <?php if ($model->getFullPath() !== $model->getName()) { ?>
                <div class="box box-default">
                    <div class="box-header with-border">
                        <h3 class="box-title">Полный путь в иерархии</h3>
                    </div>
                    <div class="box-body">
                        <p class="lead"><?= Html::encode($model->getFullPath()) ?></p>
                    </div>
                </div>
                <?php } ?>
                
                <?php if ($model->getChildren()->count() > 0) { ?>
                <div class="box box-success">
                    <div class="box-header with-border">
                        <h3 class="box-title">Дочерние элементы (<?= $model->getChildren()->count() ?>)</h3>
                    </div>
                    <div class="box-body">
                        <div class="row">
                            <?php foreach ($model->children as $child) { ?>
                                <div class="col-md-6">
                                    <div class="info-box">
                                        <span class="info-box-icon bg-aqua"><i class="fa fa-tag"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text"><?= Html::encode($child->code) ?></span>
                                            <span class="info-box-number"><?= Html::encode($child->name_ru) ?></span>
                                            <div class="progress">
                                                <div class="progress-bar" style="width: <?= $child->status == \app\models\Ikpu::STATUS_ACTIVE ? '100' : '0' ?>%"></div>
                                            </div>
                                            <span class="progress-description">
                                                <?= Html::a('Просмотр', ['view', 'id' => $child->id], ['class' => 'btn btn-xs btn-info']) ?>
                                                <?= Html::a('Редактировать', ['update', 'id' => $child->id], ['class' => 'btn btn-xs btn-primary']) ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
                <?php } ?>
                
                <?php if ($model->getProducts()->count() > 0) { ?>
                <div class="box box-warning">
                    <div class="box-header with-border">
                        <h3 class="box-title">Товары с данным ИКПУ (<?= $model->getProducts()->count() ?>)</h3>
                    </div>
                    <div class="box-body">
                        <p>Этот ИКПУ используется в <strong><?= $model->getProducts()->count() ?></strong> товарах.</p>
                        <p>
                            <?= Html::a('Просмотреть товары', ['/admin/product', 'ProductSearch[ikpu_code]' => $model->code], ['class' => 'btn btn-warning']) ?>
                        </p>
                    </div>
                </div>
                <?php } ?>
            </div>
            
            <div class="col-md-3">
                <?= AdminIkpuMenu::widget() ?>
            </div>
        </div>
    </section>
</div>
