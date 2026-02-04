<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\bootstrap4\ActiveForm;
use app\models\Ikpu;

$this->title = 'ИКПУ (Классификатор продукции)';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="content-wrapper">
    <section class="content-header">
        <h1><?= Html::encode($this->title) ?></h1>
        
        <ol class="breadcrumb">
            <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/'])?>"><i class="fa fa-dashboard"></i> Home</a></li>
            <li class="active"><?= Html::encode($this->title) ?></li>
        </ol>
    </section>
    
    <section class="content">
        <?php if (Yii::$app->session->hasFlash('ikpu_saved')) {?>
            <div class="callout callout-success text-center">
                <?=Yii::$app->session->getFlash('ikpu_saved');?>
            </div>
        <?php }?>
        <?php if (Yii::$app->session->hasFlash('ikpu_removed')) {?>
            <div class="callout callout-success text-center">
                <?=Yii::$app->session->getFlash('ikpu_removed');?>
            </div>
        <?php }?>
        <?php if (Yii::$app->session->hasFlash('ikpu_status')) {?>
            <div class="callout callout-success text-center">
                <?=Yii::$app->session->getFlash('ikpu_status');?>
            </div>
        <?php }?>
        <?php if (Yii::$app->session->hasFlash('ikpu_error')) {?>
            <div class="callout callout-danger text-center">
                <?=Yii::$app->session->getFlash('ikpu_error');?>
            </div>
        <?php }?>
        
        <div class="box box-info color-palette-box">
            <div class="box-header with-border">
                <h3 class="box-title">
                    <?= Html::a('<i class="fa fa-plus"></i> Добавить ИКПУ', ['create'], ['class' => 'btn btn-success']) ?>
                </h3>
            </div>
            <div class="box-body">
                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'tableOptions' => ['class' => 'table table-striped table-bordered'],
                    'columns' => [
                        [
                            'attribute' => 'code',
                            'label' => 'Код ИКПУ',
                            'format' => 'text',
                            'headerOptions' => ['style' => 'width: 150px;'],
                        ],
                        [
                            'attribute' => 'name_ru',
                            'label' => 'Название',
                            'format' => 'text',
                        ],
                        [
                            'attribute' => 'parent_code',
                            'label' => 'Родительский код',
                            'format' => 'text',
                            'headerOptions' => ['style' => 'width: 150px;'],
                            'value' => function($model) {
                                return $model->parent ? $model->parent->code : 'Корневой';
                            }
                        ],

                        [
                            'attribute' => 'status',
                            'label' => 'Статус',
                            'format' => 'html',
                            'headerOptions' => ['style' => 'width: 100px;'],
                            'filter' => Ikpu::getStatusOptions(),
                            'value' => function($model) {
                                $class = $model->status == Ikpu::STATUS_ACTIVE ? 'label-success' : 'label-default';
                                return '<span class="label ' . $class . '">' . $model->getStatusLabel() . '</span>';
                            }
                        ],
                        [
                            'attribute' => 'created_at',
                            'label' => 'Создано',
                            'format' => 'datetime',
                            'headerOptions' => ['style' => 'width: 150px;'],
                        ],
                        [
                            'class' => 'yii\grid\ActionColumn',
                            'header' => 'Действия',
                            'headerOptions' => ['style' => 'width: 120px;'],
                            'template' => '{view} {update} {toggle-status} {delete}',
                            'buttons' => [
                                'view' => function ($url, $model, $key) {
                                    return Html::a('<i class="fa fa-eye"></i>', $url, [
                                        'title' => 'Просмотр',
                                        'class' => 'btn btn-xs btn-info',
                                        'style' => 'margin-right: 2px;'
                                    ]);
                                },
                                'update' => function ($url, $model, $key) {
                                    return Html::a('<i class="fa fa-pencil"></i>', $url, [
                                        'title' => 'Редактировать',
                                        'class' => 'btn btn-xs btn-primary',
                                        'style' => 'margin-right: 2px;'
                                    ]);
                                },
                                'toggle-status' => function ($url, $model, $key) {
                                    $icon = $model->status == Ikpu::STATUS_ACTIVE ? 'fa-lock' : 'fa-unlock';
                                    $class = $model->status == Ikpu::STATUS_ACTIVE ? 'btn-warning' : 'btn-success';
                                    $title = $model->status == Ikpu::STATUS_ACTIVE ? 'Деактивировать' : 'Активировать';
                                    
                                    return Html::a('<i class="fa ' . $icon . '"></i>', $url, [
                                        'title' => $title,
                                        'class' => 'btn btn-xs ' . $class,
                                        'style' => 'margin-right: 2px;'
                                    ]);
                                },
                                'delete' => function ($url, $model, $key) {
                                    return Html::a('<i class="fa fa-trash"></i>', $url, [
                                        'title' => 'Удалить',
                                        'class' => 'btn btn-xs btn-danger remove-object',
                                        'data-confirm' => 'Вы уверены, что хотите удалить этот ИКПУ?',
                                        'data-method' => 'post',
                                    ]);
                                },
                            ],
                            'urlCreator' => function ($action, $model, $key, $index) {
                                if ($action === 'toggle-status') {
                                    return ['toggle-status', 'id' => $model->id];
                                }
                                return [$action, 'id' => $model->id];
                            }
                        ],
                    ],
                    'pager' => [
                        'class' => 'yii\bootstrap4\LinkPager',
                    ],
                ]); ?>
            </div>
        </div>
    </section>
</div>

<style>
.grid-view th {
    background-color: #f4f4f4;
    font-weight: bold;
}
.grid-view .filters input,
.grid-view .filters select {
    width: 100%;
}
</style>
