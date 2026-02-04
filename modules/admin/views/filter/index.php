<?php
use yii\helpers\Html;
use yii\grid\GridView;
use yii\bootstrap4\ActiveForm;

use kartik\select2\Select2;

$this->title = 'Filter';
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
        <?php if (Yii::$app->session->hasFlash('filter_saved')) {?>
            <div class="callout callout-success text-center">
                <?=Yii::$app->session->getFlash('filter_saved');?>
            </div>
        <?php }?>
        <?php if (Yii::$app->session->hasFlash('filter_removed')) {?>
            <div class="callout callout-success text-center">
                <?=Yii::$app->session->getFlash('filter_removed');?>
            </div>
        <?php }?>
        <div class="box box-info color-palette-box">
            <div class="box-header with-border">
                <div class="box-title pull-right" style="font-size: 14px">
                    <a href="<?=Yii::$app->urlManager->createUrl(['/admin/filter/create'])?>" class="btn btn-primary">
                        <i class="fa fa-plus"></i>
                        Add Filter
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
                    'summary' => "Page {begin} - {end} of {totalCount} users<br/><br/>",
                    'emptyText' => 'No users',
                    'rowOptions' => function ($model, $index, $widget, $grid) {
                        return [
                            'id' => $model['id'],
                            'url' => Yii::$app->urlManager->createUrl('/admin/filter/view').'?id='.$model['id']
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
                        ],
                        [
                            'attribute'=>'type',
                            'label'=>'<i class="fa fa-sort"></i> Type',
                            'encodeLabel' => false,
                            'format' => 'html',
                            'value' => function ($model, $key, $index, $column) {
                                if ($model->type == 'select') {
                                    return '<small class="label bg-black">Выбор одного варианта</small>';
                                }
                                if ($model->type == 'checkbox') {
                                    return '<small class="label bg-blue">Чекбокс</small>';
                                }
                                if ($model->type == 'input') {
                                    return '<small class="label bg-aqua">Ввод текста</small>';
                                }
                                if ($model->type == 'file') {
                                    return '<small class="label bg-yellow">File</small>';
                                }
                            },
                        ],
                        [
                            'attribute'=>'name_ru',
                            'label'=>'<i class="fa fa-sort"></i> Name',
                            'encodeLabel' => false,
                            'value' => function ($model, $key, $index, $column) {
                                return ($model->{$column->attribute}) ? $model->{$column->attribute} : 'No data';
                            },
                        ],
                        [
                            'attribute'=>'category_id',
                            'label'=>'<i class="fa fa-sort"></i> Category',
                            'encodeLabel' => false,
                            'value' => function ($model, $key, $index, $column) {
                                return ($model->category) ? $model->category->name_ru : 'No data';
                            },
                        ],
                        [
                            'attribute'=>'status',
                            'label'=>'<i class="fa fa-sort"></i> Status',
                            'encodeLabel' => false,
                            'format' => 'html',
                            'filter' => Html::activeDropDownList($searchModel, 'status', ['1'=>'Active', '2'=>'Blocked'], ['class'=>'form-control select2','prompt' => 'Select']),
                            'value' => function ($model, $key, $index, $column) {
                                if ($model->status == 2) {
                                    return '<small class="label bg-red">Blocked</small>';
                                }
                                if ($model->status == 1) {
                                    return '<small class="label bg-green">Active</small>';
                                }
                            },
                        ],
                        [
                            'attribute'=>'is_filter',
                            'label'=>'<i class="fa fa-sort"></i> Filter',
                            'encodeLabel' => false,
                            'format' => 'html',
                            'filter' => Html::activeDropDownList($searchModel, 'is_filter', ['0'=>'Характеристика', '1'=>'Filter'], ['class'=>'form-control select2','prompt' => 'Select']),
                            'value' => function ($model, $key, $index, $column) {
                                if ($model->is_filter == 1) {
                                    return '<small class="label bg-black">Filter</small>';
                                } else {
                                    return '<small class="label bg-primary">Характеристика</small>';
                                }
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
                                    return Html::a('<span class="glyphicon glyphicon-pencil"></span>', Yii::$app->urlManager->createUrl(['/admin/filter/create', 'id'=>$model->id]), ['class'=>'btn btn-warning']);
                                },
                                'delete' => function ($url, $model) {
                                    return Html::a('<span class="glyphicon glyphicon-trash"></span>', Yii::$app->urlManager->createUrl(['/admin/filter/remove', 'id'=>$model->id]), ['class'=>'btn btn-danger remove-object']);
                                }
                            ],
                        ]
                    ],
                ]); ?>
            </div>
        </div>
    </section>
</div>