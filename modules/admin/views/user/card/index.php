<?php
use yii\helpers\Html;
use yii\grid\GridView;

use app\widgets\admin_user_menu\AdminUserMenu;
use app\widgets\admin_user_menu\AdminUserButton;

$this->title = 'Card user';
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
        <?php if (Yii::$app->session->hasFlash('card_removed')) {?>
            <div class="callout callout-success text-center">
                <?=Yii::$app->session->getFlash('card_removed');?>
            </div>
        <?php }?>
        <div class="row">
            <div class="col-sm-3">
                <?=AdminUserMenu::widget();?>
            </div>
            <div class="col-sm-9">
                <div class="box box-info color-palette-box">
                    <div class="box-header with-border">
                        <div class="box-title pull-right" style="font-size: 14px">
                            <!-- <a href="<?=Yii::$app->urlManager->createUrl(['/admin/user/card-create', 'id'=>Yii::$app->request->get('id')])?>" class="btn btn-success">
                                <i class="fa fa-plus"></i>
                                Add card
                            </a> -->
                            <?=AdminUserButton::widget();?>
                        </div>
                        <div id="action-links">
                            <a href="javascript:;" class="btn btn-danger" data-value="remove"><i class="fa fa-trash"></i> Delete</a>
                        </div>
                    </div>
                    <div class="box-body" id="item-block">
                        <?= GridView::widget([
                            'dataProvider' => $dataProvider,
                            'filterModel' => $searchModel,
                            'summary' => "Page {begin} - {end} of {totalCount} card<br/><br/>",
                            'emptyText' => 'No cards',
                            'rowOptions' => function ($model, $index, $widget, $grid) {
                                return [
                                    'id' => $model['id'],
                                    'user_id' => $model['user_id'],
                                    'url' => Yii::$app->urlManager->createUrl('/admin/user/card-view').'?id='.$model['user_id'].'&card_id='.$model['id']
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
                                    'attribute'=>'card_type_id',
                                    'label'=>'<i class="fa fa-sort"></i> Type',
                                    'encodeLabel' => false,
                                    'filter' => Html::activeDropDownList($searchModel, 'card_type_id', $card_types, ['class'=>'form-control select2','prompt' => 'Select']),
                                    'value' => function ($model, $key, $index, $column) {
                                        return $model->cardType ? $model->cardType->name_ru : 'No data';
                                    },
                                ],
                                [
                                    'attribute'=>'card_number',
                                    'label'=>'<i class="fa fa-sort"></i> Number card',
                                    'encodeLabel' => false,
                                ],
                                [
                                    'attribute'=>'card_expire',
                                    'label'=>'<i class="fa fa-sort"></i> Expire',
                                    'encodeLabel' => false,
                                ],
                                [
                                    'attribute'=>'card_phone_number',
                                    'label'=>'<i class="fa fa-sort"></i> Phone',
                                    'encodeLabel' => false
                                ],
                                [
                                    'attribute'=>'status',
                                    'label'=>'<i class="fa fa-sort"></i> Status',
                                    'encodeLabel' => false,
                                    'format' => 'html',
                                    'filter' => Html::activeDropDownList($searchModel, 'status', ['1'=>'Active', '2'=>'Blocked'], ['class'=>'form-control select2','prompt' => 'Select']),
                                    'value' => function ($model, $key, $index, $column) {
                                        if ($model->status == 1) {
                                            return '<small class="label bg-green">Active</small>';
                                        }
                                        if ($model->status == 2) {
                                            return '<small class="label bg-red">Blocked</small>';
                                        }
                                    },
                                ],
                                [
                                    'class' => 'yii\grid\ActionColumn',
                                    'template' => '{delete}',
                                    'buttons' => [
                                        // 'update' => function ($url, $model) {
                                        //     return Html::a('<span class="glyphicon glyphicon-pencil"></span>', Yii::$app->urlManager->createUrl(['/admin/user/card-create', 'id'=>$model->user_id, 'card_id'=>$model->id]), ['class'=>'btn btn-warning']);
                                        // },
                                        'delete' => function ($url, $model) {
                                            return Html::a('<span class="glyphicon glyphicon-trash"></span>', Yii::$app->urlManager->createUrl(['/admin/user/card-remove', 'id'=>$model->user_id, 'card_id'=>$model->id]), ['class'=>'btn btn-danger remove-object']);
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