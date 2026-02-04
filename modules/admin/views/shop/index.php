<?php
use yii\helpers\Html;
use yii\grid\GridView;

$this->title = 'Shopsы';
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
        <?php if (Yii::$app->session->hasFlash('shop_removed')) {?>
            <div class="callout callout-success text-center">
                <?=Yii::$app->session->getFlash('shop_removed');?>
            </div>
        <?php }?>
        <div class="box box-info color-palette-box">
            <div class="box-header with-border">
                <div class="pull-right">
                    <a href="<?=Yii::$app->urlManager->createUrl(['/admin/shop/create'])?>" class="btn btn-primary">
                        <i class="glyphicon glyphicon-plus"></i> Add shop
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
                    'summary' => "Page {begin} - {end} of {totalCount} shop<br/><br/>",
                    'emptyText' => 'Shopsов нет',
                    'rowOptions' => function ($model, $index, $widget, $grid) {
                        return [
                            'id' => $model['id'],
                            'url' => Yii::$app->urlManager->createUrl('/admin/shop/view').'?id='.$model['id']
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
                            'label' => 'Photo',
                            'format' => 'html',
                            'value' => function($data) { return Html::img($data->getPhoto('50x50'), ['width'=>'50']); },
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
                            'attribute'=>'user_id',
                            'label'=>'<i class="fa fa-sort"></i> Seller ',
                            'encodeLabel' => false,
                            'format' => 'html',
                            'value' => function ($model, $key, $index, $column) {
                                return ($model->user && $model->user->name) ? $model->user->name : 'No data';
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
                                    return Html::a('<span class="glyphicon glyphicon-pencil"></span>', Yii::$app->urlManager->createUrl(['/admin/shop/create', 'id'=>$model->id]), ['class'=>'btn btn-warning']);
                                },
                                'delete' => function ($url, $model) {
                                    return Html::a('<span class="glyphicon glyphicon-trash"></span>', Yii::$app->urlManager->createUrl(['/admin/shop/remove', 'id'=>$model->id]), ['class'=>'btn btn-danger remove-object']);
                                }
                            ],
                        ]
                    ],
                ]); ?>
            </div>
        </div>
    </section>
</div>