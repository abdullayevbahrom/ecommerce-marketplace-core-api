<?php
use yii\helpers\Html;
use yii\grid\GridView;

use app\widgets\admin_shop_menu\AdminShopMenu;

$this->title = 'Manager';
$this->params['breadcrumbs'][] = $this->title;

$type = Yii::$app->request->get('type');
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
        <?php if (Yii::$app->session->hasFlash('manager_removed')) {?>
            <div class="callout callout-success text-center">
                <?=Yii::$app->session->getFlash('manager_removed');?>
            </div>
        <?php }?>
        <div class="row">
            <div class="col-sm-3">
                <?=AdminShopMenu::widget();?>
            </div>
            <div class="col-sm-9">
                <div class="box box-info color-palette-box">
                    <div class="box-header with-border">
                        <div class="pull-right">
                            <a href="<?=Yii::$app->urlManager->createUrl(['/admin/shop/manager-create', 'id'=>$model->id])?>" class="btn btn-primary">
                                <i class="glyphicon glyphicon-plus"></i> Add manager
                            </a>
                        </div>
                        <div id="action-links">
                            <a href="javascript:;" class="btn btn-danger" data-value="remove"><i class="fa fa-trash"></i> Delete</a>
                        </div>
                    </div>
                    <div class="box-body" id="item-block">
                        <?= GridView::widget([
                            'dataProvider' => $dataProvider,
                            'filterModel' => $searchModel,
                            'summary' => "Page {begin} - {end} of {totalCount} manager<br/><br/>",
                            'emptyText' => 'No managers',
                            'rowOptions' => function ($model, $index, $widget, $grid) {
                                return [
                                    'id' => $model['id'],
                                    'url' => Yii::$app->urlManager->createUrl('/admin/shop/manager-view').'?id='.$model['shop_id'].'&manager_id='.$model['id']
                                ];
                            },
                            'tableOptions' => [
                                'class'=>'table table-striped table-bordered'
                            ],
                            'columns' => [
                                ['class' => 'yii\grid\SerialColumn'],
                                [
                                    'class' => 'yii\grid\CheckboxColumn'
                                ],
                                [
                                    'attribute'=>'id',
                                    'label'=>'<i class="fa fa-sort"></i> ID',
                                    'encodeLabel' => false,
                                    'contentOptions' => [
                                        'style' => 'width:70px'
                                    ],
                                ],
                                [
                                    'attribute'=>'name',
                                    'label'=>'<i class="fa fa-sort"></i> Name',
                                    'encodeLabel' => false,
                                    'value' => function ($model, $key, $index, $column) {
                                        return ($model->{$column->attribute}) ? $model->{$column->attribute} : 'No data';
                                    },
                                ],
                                [
                                    'attribute'=>'login',
                                    'label'=>'<i class="fa fa-sort"></i> Login',
                                    'encodeLabel' => false,
                                    'value' => function ($model, $key, $index, $column) {
                                        return ($model->{$column->attribute}) ? $model->{$column->attribute} : 'No data';
                                    },
                                ],
                                [
                                    'attribute'=>'date',
                                    'label'=>'<i class="fa fa-sort"></i> Date',
                                    'encodeLabel' => false,
                                    'value' => function ($model, $key, $index, $column) {
                                        return ($model->{$column->attribute}) ? $model->{$column->attribute} : 'No data';
                                    },
                                ],
                                [
                                    'class' => 'yii\grid\ActionColumn',
                                    'template' => '{update} {delete}',
                                    'buttons' => [
                                        'update' => function ($url, $model) {
                                            return Html::a('<span class="glyphicon glyphicon-pencil"></span>', Yii::$app->urlManager->createUrl(['/admin/shop/manager-create', 'id'=>$model->shop_id, 'manager_id'=>$model->id]), ['class'=>'btn btn-warning']);
                                        },
                                        'delete' => function ($url, $model) {
                                            return Html::a('<span class="glyphicon glyphicon-trash"></span>', Yii::$app->urlManager->createUrl(['/admin/shop/manager-remove', 'id'=>$model->shop_id, 'manager_id'=>$model->id]), ['class'=>'btn btn-danger remove-object']);
                                        }
                                    ],
                                ]
                            ],
                        ]); ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>