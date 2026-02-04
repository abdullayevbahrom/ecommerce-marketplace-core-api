<?php
use yii\helpers\Html;
use yii\grid\GridView;

use app\widgets\admin_logist_menu\AdminLogistMenu;

$this->title = 'Регионы компании';
$this->params['breadcrumbs'][] = $this->title;

$type = Yii::$app->request->get('type');
?>

<div class="content-wrapper">
    <section class="content-header">
        <h1><?=$this->title;?></h1>

        <ol class="breadcrumb">
            <li><a href="<?=Yii::$app->urlManager->createUrl(['/logist/'])?>"><i class="fa fa-dashboard"></i> Главная</a></li>
            <li class="active"><?=$this->title;?></li>
        </ol>
    </section>
    <section class="content">
        <?php if (Yii::$app->session->hasFlash('logist_tariff_removed')) {?>
            <div class="callout callout-success text-center">
                <?=Yii::$app->session->getFlash('logist_tariff_removed');?>
            </div>
        <?php }?>
        <div class="row">
            <div class="col-sm-3">
                <?=AdminLogistMenu::widget();?>
            </div>
            <div class="col-sm-9">
                <div class="box box-info color-palette-box">
                    <div class="box-header with-border">
                        <div class="pull-right">
                            <a href="<?=Yii::$app->urlManager->createUrl(['/logist/logist/tariff-create', 'id'=>Yii::$app->request->get('id')])?>" class="btn btn-primary">
                                <i class="glyphicon glyphicon-plus"></i> Добавить регион/тариф
                            </a>
                        </div>
                        <div id="action-links">
                            <a href="javascript:;" class="btn btn-danger" data-value="remove"><i class="fa fa-trash"></i> Удалить</a>
                        </div>
                    </div>
                    <div class="box-body" id="item-block">
                        <?= GridView::widget([
                            'dataProvider' => $dataProvider,
                            'filterModel' => $searchModel,
                            'summary' => "Страница {begin} - {end} из {totalCount} регионов<br/><br/>",
                            'emptyText' => 'Регионов нет',
                            'rowOptions' => function ($model, $index, $widget, $grid) {
                                return [
                                    'id' => $model['id'],
                                    'url' => Yii::$app->urlManager->createUrl('/logist/logist/tariff-view').'?id='.Yii::$app->request->get('id').'&tariff_id='.$model['id']
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
                                    'attribute'=>'region_id',
                                    'label'=>'<i class="fa fa-sort"></i> Название',
                                    'encodeLabel' => false,
                                    'value' => function ($model, $key, $index, $column) {
                                        return ($model->region) ? $model->region->name_ru : 'Не указано';
                                    },
                                ],
                                [
                                    'attribute'=>'date',
                                    'label'=>'<i class="fa fa-sort"></i> Дата',
                                    'encodeLabel' => false,
                                    'value' => function ($model, $key, $index, $column) {
                                        return ($model->{$column->attribute}) ? $model->{$column->attribute} : 'Не указано';
                                    },
                                ],
                                [
                                    'class' => 'yii\grid\ActionColumn',
                                    'template' => '{update} {delete}',
                                    'buttons' => [
                                        'update' => function ($url, $model) {
                                            return Html::a('<span class="glyphicon glyphicon-pencil"></span>', Yii::$app->urlManager->createUrl(['/logist/logist/tariff-create', 'id'=>Yii::$app->request->get('id'), 'tariff_id'=>$model->id]), ['class'=>'btn btn-warning']);
                                        },
                                        'delete' => function ($url, $model) {
                                            return Html::a('<span class="glyphicon glyphicon-trash"></span>', Yii::$app->urlManager->createUrl(['/logist/logist/tariff-remove', 'id'=>Yii::$app->request->get('id'), 'tariff_id'=>$model->id]), ['class'=>'btn btn-danger remove-object']);
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