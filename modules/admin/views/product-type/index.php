<?php
use yii\helpers\Html;
use yii\grid\GridView;

$this->title = 'Product Types';
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
        <?php if (Yii::$app->session->hasFlash('product_type_removed')) {?>
            <div class="callout callout-success text-center">
                <?=Yii::$app->session->getFlash('product_type_removed');?>
            </div>
        <?php }?>
        <?php if (Yii::$app->session->hasFlash('product_type_locked')) {?>
            <div class="callout callout-success text-center">
                <?=Yii::$app->session->getFlash('product_type_locked');?>
            </div>
        <?php }?>
        
        <div class="box box-info color-palette-box">
            <div class="box-header with-border">
                <div class="pull-right">
                    <a href="<?=Yii::$app->urlManager->createUrl(['/admin/product-type/create'])?>" class="btn btn-primary">
                        <i class="glyphicon glyphicon-plus"></i> Add Product Type
                    </a>
                </div>
            </div>
            <div class="box-body">
                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],
                        [
                            'attribute' => 'id',
                            'headerOptions' => ['style' => 'width: 80px;'],
                        ],
                        [
                            'attribute' => 'name_ru',
                            'label' => 'Name (RU)',
                            'value' => function($model) {
                                return Html::a($model->name_ru, ['/admin/product-type/view', 'id' => $model->id]);
                            },
                            'format' => 'raw'
                        ],
                        [
                            'attribute' => 'name_en',
                            'label' => 'Name (EN)',
                        ],
                        [
                            'attribute' => 'category_id',
                            'label' => 'Category',
                            'value' => function($model) {
                                return $model->category ? $model->category->name_ru : '-';
                            },
                            'filter' => $categories
                        ],
                        [
                            'attribute' => 'type',
                            'label' => 'Type',
                            'filter' => [
                                'input' => 'Input',
                                'select' => 'Select',
                                'checkbox' => 'Checkbox',
                                'range' => 'Range'
                            ]
                        ],
                        [
                            'attribute' => 'sort',
                            'label' => 'Sort',
                            'headerOptions' => ['style' => 'width: 80px;'],
                        ],
                        [
                            'attribute' => 'status',
                            'label' => 'Status',
                            'value' => function($model) {
                                if ($model->status == 1) {
                                    return '<small class="label bg-green">Active</small>';
                                } else {
                                    return '<small class="label bg-red">Blocked</small>';
                                }
                            },
                            'format' => 'raw',
                            'filter' => [1 => 'Active', 0 => 'Blocked'],
                            'headerOptions' => ['style' => 'width: 100px;'],
                        ],
                        [
                            'class' => 'yii\grid\ActionColumn',
                            'template' => '{view} {update} {lock} {delete}',
                            'buttons' => [
                                'view' => function ($url, $model, $key) {
                                    return Html::a('<i class="fa fa-eye"></i>', ['/admin/product-type/view', 'id' => $model->id], [
                                        'title' => 'View',
                                        'class' => 'btn btn-sm btn-primary'
                                    ]);
                                },
                                'update' => function ($url, $model, $key) {
                                    return Html::a('<i class="fa fa-pencil"></i>', ['/admin/product-type/create', 'id' => $model->id], [
                                        'title' => 'Update',
                                        'class' => 'btn btn-sm btn-info'
                                    ]);
                                },
                                'lock' => function ($url, $model, $key) {
                                    $icon = $model->status == 1 ? 'fa-lock' : 'fa-unlock';
                                    $class = $model->status == 1 ? 'btn-warning' : 'btn-success';
                                    return Html::a('<i class="fa ' . $icon . '"></i>', ['/admin/product-type/lock', 'id' => $model->id], [
                                        'title' => $model->status == 1 ? 'Block' : 'Unblock',
                                        'class' => 'btn btn-sm ' . $class
                                    ]);
                                },
                                'delete' => function ($url, $model, $key) {
                                    return Html::a('<i class="fa fa-trash"></i>', ['/admin/product-type/remove', 'id' => $model->id], [
                                        'title' => 'Delete',
                                        'class' => 'btn btn-sm btn-danger',
                                        'data-confirm' => 'Are you sure you want to delete this item?'
                                    ]);
                                },
                            ],
                            'headerOptions' => ['style' => 'width: 200px;'],
                        ],
                    ],
                    'tableOptions' => ['class' => 'table table-striped table-bordered'],
                    'options' => ['class' => 'grid-view table-responsive'],
                ]); ?>
            </div>
        </div>
    </section>
</div> 