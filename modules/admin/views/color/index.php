<?php
use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;
use app\models\color\Color;

$this->title = 'Colors';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="content-wrapper">
    <section class="content-header">
        <h1><?=$this->title;?></h1>
        
        <ol class="breadcrumb">
            <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/'])?>"><i class="fa fa-dashboard"></i> Home</a></li>
            <li class="active"><?=$this->title;?></li>
        </ol>
    </section>
    <section class="content">
        <?php if (Yii::$app->session->hasFlash('color_removed')) {?>
            <div class="callout callout-success text-center">
                <?=Yii::$app->session->getFlash('color_removed');?>
            </div>
        <?php }?>
        <?php if (Yii::$app->session->hasFlash('color_locked')) {?>
            <div class="callout callout-success text-center">
                <?=Yii::$app->session->getFlash('color_locked');?>
            </div>
        <?php }?>
        <div class="box box-info color-palette-box">
            <div class="box-header with-border">
                <?php if (Yii::$app->user->identity->role != \app\models\user\User::ROLE_MODERATOR): ?>
                <div class="box-title pull-right" style="font-size: 14px">
                    <a href="<?=Yii::$app->urlManager->createUrl(['/admin/color/create'])?>" class="btn btn-primary">
                        <i class="fa fa-plus"></i>
                        Add color
                    </a>
                </div>
                <?php endif ?>
                <div id="action-links" style="display:none">
                    <a href="javascript:;" class="btn btn-danger" data-value="remove"><i class="fa fa-trash"></i> Delete</a>
                    <a href="javascript:;" class="btn btn-warning" data-value="disable"><i class="fa fa-lock"></i> Block</a>
                    <a href="javascript:;" class="btn btn-success" data-value="enable"><i class="fa fa-unlock"></i> Unblock</a>
                </div>
            </div>
            <div class="box-body" id="item-block">
                <?php Pjax::begin(); ?>
                    <?= GridView::widget([
                        'dataProvider' => $dataProvider,
                        'filterModel' => $searchModel,
                        'summary' => "Page {begin} - {end} of {totalCount} colors<br/><br/>",
                        'emptyText' => 'No colors',
                        'tableOptions' => [
                            'class'=>'table table-striped table-bordered'
                        ],
                        'columns' => [
                            ['class' => 'yii\grid\SerialColumn'],
                            ['class' => 'yii\grid\CheckboxColumn'],
                            [
                                'attribute'=>'id',
                                'label'=>'<i class="fa fa-sort"></i> ID',
                                'encodeLabel' => false,
                            ],
                            [
                                'attribute'=>'name_ru',
                                'label'=>'<i class="fa fa-sort"></i> Name',
                                'encodeLabel' => false,
                                'value' => function ($model, $key, $index, $column) {
                                    return $model->name_ru ? $model->name_ru : 'No data';
                                },
                            ],
                            [
                                'attribute'=>'color',
                                'label'=>'<i class="fa fa-sort"></i> Color',
                                'encodeLabel' => false,
                                'format'=>'html',
                                'value' => function ($model, $key, $index, $column) {
                                    return $model->color ? '<div class="block-color" style="background-color:'.$model->color.'"></div>' : 'No data';
                                },
                            ],
                            [
                                'attribute'=>'date',
                                'label'=>'<i class="fa fa-sort"></i> Date',
                                'encodeLabel' => false,
                            ],
                            [
                                'attribute' => 'status',
                                'label' => 'Status',
                                'value' => function($model) {
                                    if ((int)$model->status === Color::STATUS_ACTIVE) {
                                        return '<small class="label bg-green">Active</small>';
                                    }

                                    return '<small class="label bg-red">Blocked</small>';
                                },
                                'format' => 'raw',
                                'filter' => [
                                    Color::STATUS_ACTIVE => 'Active',
                                    Color::STATUS_INACTIVE => 'Blocked',
                                ],
                                'headerOptions' => ['style' => 'width: 100px;'],
                            ],
                            [
                                'class' => 'yii\grid\ActionColumn',
                                'template' => '{view} {update} {lock} {delete}',
                                'buttons' => [
                                    'view' => function ($url, $model, $key) {
                                        return Html::a('<i class="fa fa-eye"></i>', ['/admin/color/view', 'id' => $model->id], [
                                            'title' => 'View',
                                            'class' => 'btn btn-sm btn-primary',
                                            'data-pjax' => 0,
                                        ]);
                                    },
                                    'update' => function ($url, $model, $key) {
                                        if (Yii::$app->user->identity->role === \app\models\user\User::ROLE_MODERATOR) {
                                            return '';
                                        }

                                        return Html::a('<i class="fa fa-pencil"></i>', ['/admin/color/create', 'id' => $model->id], [
                                            'title' => 'Update',
                                            'class' => 'btn btn-sm btn-info',
                                            'data-pjax' => 0,
                                        ]);
                                    },
                                    'lock' => function ($url, $model, $key) {
                                        $isActive = (int)$model->status === Color::STATUS_ACTIVE;
                                        $icon = $isActive ? 'fa-lock' : 'fa-unlock-alt';
                                        $class = $isActive ? 'btn-warning' : 'btn-success';

                                        return Html::a('<i class="fa ' . $icon . '"></i>', ['/admin/color/lock', 'id' => $model->id], [
                                            'title' => $isActive ? 'Block' : 'Unblock',
                                            'class' => 'btn btn-sm ' . $class,
                                            'data-pjax' => 0,
                                        ]);
                                    },
                                    'delete' => function ($url, $model, $key) {
                                        if (Yii::$app->user->identity->role === \app\models\user\User::ROLE_MODERATOR) {
                                            return '';
                                        }

                                        return Html::a('<i class="fa fa-trash"></i>', ['/admin/color/remove', 'id' => $model->id], [
                                            'title' => 'Delete',
                                            'class' => 'btn btn-sm btn-danger',
                                            'data-confirm' => 'Are you sure you want to delete this item?',
                                            'data-pjax' => 0,
                                        ]);
                                    },
                                ],
                                'headerOptions' => ['style' => 'width: 200px;'],
                            ]
                        ],
                    ]); ?>
                <?php Pjax::end(); ?>
            </div>
        </div>
    </section>
</div>  
