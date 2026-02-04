<?php
use yii\helpers\Html;
use yii\grid\GridView;

$this->title = 'Slider';
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
        <?php if (Yii::$app->session->hasFlash('slider_removed')) {?>
            <div class="callout callout-success text-center">
                <?=Yii::$app->session->getFlash('slider_removed');?>
            </div>
        <?php }?>
        <div class="box box-info color-palette-box">
            <div class="box-header with-border">
                <div class="box-title pull-right" style="font-size: 14px">
                    <?php if ($slides) {?>
                        <a href="javascript:;" class="btn btn-info" data-toggle="modal" data-target="#slider-view">
                            <i class="fa fa-eye"></i>
                            View slider
                        </a>
                    <?php }?>
                    <a href="<?=Yii::$app->urlManager->createUrl(['/admin/slider/create'])?>" class="btn btn-primary">
                        <i class="fa fa-plus"></i>
                        Add slider
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
                    'summary' => "Page {begin} - {end} of {totalCount} slide<br/><br/>",
                    'emptyText' => 'Слайдов нет',
                    'rowOptions' => function ($model, $index, $widget, $grid) {
                        return [
                            'id' => $model['id'],
                            'url' => Yii::$app->urlManager->createUrl('/admin/slider/view').'?id='.$model['id']
                        ];
                    },
                    'tableOptions' => [
                        'class'=>'table table-striped table-bordered'
                    ],
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],
                        [
                            'label' => 'Photo',
                            'format' => 'html',
                            'value' => function($data) { return Html::img($data->getPhoto('original'), ['width'=>'50']); },
                        ],
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
                                return ($model->{$column->attribute}) ? $model->{$column->attribute} : 'No data';
                            },
                        ],
                        [
                            'attribute'=>'type',
                            'label'=>'<i class="fa fa-sort"></i> Type',
                            'encodeLabel' => false,
                            'format' => 'html',
                            'filter' => Html::activeDropDownList($searchModel, 'type', ['site'=>'Site', 'mobile'=>'Mobile'], ['class'=>'form-control select2','prompt' => 'Select']),
                            'value' => function ($model, $key, $index, $column) {
                                if ($model->type == 'site') {
                                    return '<small class="label bg-aqua">Site</small>';
                                }
                                if ($model->type == 'mobile') {
                                    return '<small class="label bg-black">Mobile</small>';
                                }
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
                        ],
                        [
                            'class' => 'yii\grid\ActionColumn',
                            'template' => '{update} {delete}',
                            'buttons' => [
                                'update' => function ($url, $model) {
                                    return Html::a('<span class="glyphicon glyphicon-pencil"></span>', Yii::$app->urlManager->createUrl(['/admin/slider/create', 'id'=>$model->id]), ['class'=>'btn btn-warning']);
                                },
                                'delete' => function ($url, $model) {
                                    return Html::a('<span class="glyphicon glyphicon-trash"></span>', Yii::$app->urlManager->createUrl(['/admin/slider/remove', 'id'=>$model->id]), ['class'=>'btn btn-danger remove-object']);
                                }
                            ],
                        ]
                    ],
                ]); ?>
            </div>
        </div>
    </section>
</div>

<?php if ($slides) {?>
    <div class="modal fade" id="slider-view">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                    <h4 class="modal-title">View slider</h4>
                </div>
                <div class="modal-body">
                    <div id="carousel-example-generic" class="carousel slide" data-ride="carousel">
                        <ol class="carousel-indicators">
                            <?php foreach ($slides as $k => $v) {?>
                                <li data-target="#carousel-example-generic" data-slide-to="<?=$k?>" <?php if ($k == 0) {?>class="active"<?php }?>></li>
                            <?php }?>
                        </ol>
                        <div class="carousel-inner">
                            <?php foreach ($slides as $k => $v) {?>
                                <div class="item <?php if ($k == 0) {?>active<?php }?>">
                                    <img src="<?=$v->getPhoto('original');?>" alt="<?=$v->name_ru;?>">
                                    <div class="carousel-caption">
                                        <?=$v->name_ru;?>
                                    </div>
                                </div>
                            <?php }?>
                        </div>
                        <a class="left carousel-control" href="#carousel-example-generic" data-slide="prev">
                            <span class="fa fa-angle-left"></span>
                        </a>
                        <a class="right carousel-control" href="#carousel-example-generic" data-slide="next">
                            <span class="fa fa-angle-right"></span>
                        </a>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Закрыть</button>
                </div>
            </div>
        </div>
    </div>
<?php }?>