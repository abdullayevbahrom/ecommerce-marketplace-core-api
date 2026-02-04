<?php
use yii\helpers\Html;
use yii\grid\GridView;

use app\widgets\admin_user_menu\AdminUserMenu;

$this->title = 'Заказы user';
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
        <div class="row">
            <div class="col-sm-3">
                <?=AdminUserMenu::widget();?>
            </div>
            <div class="col-sm-9">
                <div class="box box-info color-palette-box">
                    <div class="box-header with-border">
                        <div class="box-title">
                            List orders
                        </div>
                        <div id="action-links">
                            <a href="javascript:;" class="btn btn-danger" data-value="remove"><i class="fa fa-trash"></i> Delete</a>
                        </div>
                    </div>
                    <div class="box-body" id="item-block">
                        <?= GridView::widget([
                            'dataProvider' => $dataProvider,
                            'filterModel' => $searchModel,
                            'summary' => "Page {begin} - {end} of {totalCount} orders<br/><br/>",
                            'emptyText' => 'Заказов нет',
                            'rowOptions' => function ($model, $index, $widget, $grid) {
                                return [
                                    'id' => $model['id'],
                                    'url' => Yii::$app->urlManager->createUrl('/admin/order/view').'?id='.$model['id']
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
                                    'attribute'=>'price',
                                    'label'=>'<i class="fa fa-sort"></i> Price',
                                    'encodeLabel' => false,
                                    'value' => function ($model, $key, $index, $column) {
                                        return ($model->price) ? number_format($model->price) : 'No data';
                                    },
                                ],
                                [
                                    'attribute'=>'amount',
                                    'label'=>'<i class="fa fa-sort"></i> Amount',
                                    'encodeLabel' => false,
                                    'value' => function ($model, $key, $index, $column) {
                                        return ($model->amount) ? $model->amount : 'No data';
                                    },
                                ],
                                [
                                    'attribute'=>'status',
                                    'label'=>'<i class="fa fa-sort"></i> Status',
                                    'encodeLabel' => false,
                                    'format' => 'html',
                                    'filter' => Html::activeDropDownList($searchModel, 'status', ['0'=>'Pending', '1'=>'Accepted', 'Rejected'], ['class'=>'form-control select2','prompt' => 'Select']),
                                    'value' => function ($model, $key, $index, $column) {
                                        if ($model->status == 0) {
                                            return '<small class="label bg-yellow">Pending</small>';
                                        }
                                        if ($model->status == 1) {
                                            return '<small class="label bg-green">Accepted</small>';
                                        }
                                        if ($model->status == 2) {
                                            return '<small class="label bg-red">Rejected</small>';
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
                                    'template' => '{delete}',
                                    'buttons' => [
                                        'delete' => function ($url, $model) {
                                            return Html::a('<span class="glyphicon glyphicon-trash"></span>', Yii::$app->urlManager->createUrl(['/admin/order/remove', 'id'=>$model->id]), ['class'=>'btn btn-danger remove-object']);
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