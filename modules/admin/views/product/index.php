<?php
use yii\helpers\Html;
use yii\grid\GridView;

$this->title = 'Products';
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
        <?php if (Yii::$app->session->hasFlash('product_removed')) {?>
            <div class="callout callout-success text-center">
                <?=Yii::$app->session->getFlash('product_removed');?>
            </div>
        <?php }?>
        <?php if (Yii::$app->session->hasFlash('product_downloaded')) {?>
            <div class="callout callout-success text-center">
                <?=Yii::$app->session->getFlash('product_downloaded');?>
            </div>
        <?php }?>
        <?php if (Yii::$app->session->hasFlash('filter_on')) {?>
            <div class="callout callout-success text-center">
                <?=Yii::$app->session->getFlash('filter_on');?>
            </div>
        <?php }?>
        <?php if (Yii::$app->session->hasFlash('filter_off')) {?>
            <div class="callout callout-success text-center">
                <?=Yii::$app->session->getFlash('filter_off');?>
            </div>
        <?php }?>
        <div class="box box-info color-palette-box">
            <div class="box-header with-border">
                <span style="margin-right:10px">
                    <?php if (!$settings) {?>
                        <a href="<?=Yii::$app->urlManager->createUrl(['/admin/product/filter-on']);?>" class="btn btn-warning">Disable characteristics for stores</a>
                    <?php } else {?>
                        <?php if ($settings->content == '1') {?>
                            <a href="<?=Yii::$app->urlManager->createUrl(['/admin/product/filter-on']);?>" class="btn btn-warning">Disable characteristics for stores</a>
                        <?php } else {?>
                            <a href="<?=Yii::$app->urlManager->createUrl(['/admin/product/filter-on']);?>" class="btn btn-success">Enable characteristics for stores</a>
                        <?php }?>
                    <?php }?>
                </span>
                <div class="pull-right">
                    <!-- <a href="<?=Yii::$app->urlManager->createUrl(['/admin/product/import'])?>" class="btn btn-success">
                        <i class="glyphicon glyphicon-download"></i> Импорт products
                    </a> -->
                    <a href="<?=Yii::$app->urlManager->createUrl(['/admin/product/create'])?>" class="btn btn-primary">
                        <i class="glyphicon glyphicon-plus"></i> Add product
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
                    'summary' => "Page {begin} - {end} of {totalCount} products<br/><br/>",
                    'emptyText' => 'No product',
                    'rowOptions' => function ($model, $index, $widget, $grid) use($page) {
                        return [
                            'id' => $model['id'],
                            'url' => Yii::$app->urlManager->createUrl('/admin/product/view').'?id='.$model['id'].'&page='.$page 
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
                                'style' => 'width:60px'
                            ],
                        ],
                        [
                            'attribute'=>'category_id',
                            'label'=>'<i class="fa fa-sort"></i> Category',
                            'encodeLabel' => false,
                            'format' => 'html',
                            'filter' => Html::activeDropDownList($searchModel, 'category_id', $categories, ['class'=>'form-control select2','prompt' => 'Select']),
                            'value' => function ($model, $key, $index, $column) {
                                return $model->category ? $model->category->name_ru : 'No data';
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
                            'attribute'=>'ikpu_code',
                            'label'=>'<i class="fa fa-tags"></i> ИКПУ',
                            'encodeLabel' => false,
                            'format' => 'html',
                            'contentOptions' => [
                                'style' => 'width:120px; font-size:11px;'
                            ],
                            'value' => function ($model, $key, $index, $column) {
                                if ($model->ikpu_code) {
                                    $ikpuName = $model->getIkpuName();
                                    $shortName = $ikpuName ? (strlen($ikpuName) > 30 ? substr($ikpuName, 0, 30) . '...' : $ikpuName) : '';
                                    return '<span class="label label-info" title="' . Html::encode($ikpuName) . '">' . 
                                           Html::encode($model->ikpu_code) . '</span><br/>' .
                                           '<small>' . Html::encode($shortName) . '</small>';
                                }
                                return '<span class="text-muted">Не указан</span>';
                            },
                        ],
                        [
                            'attribute'=>'price',
                            'label'=>'<i class="fa fa-sort"></i> Price',
                            'encodeLabel' => false,
                            'value' => function ($model, $key, $index, $column) {
                                return ($model->{$column->attribute}) ? number_format($model->{$column->attribute}) : 'No data';
                            },
                        ],
                        [
                            'attribute'=>'amount',
                            'label'=>'<i class="fa fa-sort"></i> Amount',
                            'encodeLabel' => false,
                            'contentOptions' => [
                                'style' => 'width:100px'
                            ],
                            'value' => function ($model, $key, $index, $column) {
                                return ($model->{$column->attribute}) ? number_format($model->{$column->attribute}) : 0;
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
                                    return Html::a('<span class="glyphicon glyphicon-pencil"></span>', Yii::$app->urlManager->createUrl(['/admin/product/create', 'id'=>$model->id]), ['class'=>'btn btn-warning']);
                                },
                                'delete' => function ($url, $model) use($page) {
                                    return Html::a('<span class="glyphicon glyphicon-trash"></span>', Yii::$app->urlManager->createUrl(['/admin/product/removes', 'id'=>$model->id, 'page'=>$page]), ['class'=>'btn btn-danger remove-object']);
                                }
                            ],
                        ]
                    ],
                ]); ?>
            </div>
        </div>
    </section>
</div>