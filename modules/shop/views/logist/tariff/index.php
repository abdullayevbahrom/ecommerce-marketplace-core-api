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
            <li><a href="<?=Yii::$app->urlManager->createUrl(['/shop/'])?>"><i class="fa fa-dashboard"></i> Главная</a></li>
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
                    <div class="box-body" id="item-block">
                        <?= GridView::widget([
                            'dataProvider' => $dataProvider,
                            'filterModel' => $searchModel,
                            'summary' => "Страница {begin} - {end} из {totalCount} регионов<br/><br/>",
                            'emptyText' => 'Регионов нет',
                            'rowOptions' => function ($model, $index, $widget, $grid) {
                                return [
                                    'id' => $model['id'],
                                    'url' => Yii::$app->urlManager->createUrl('/shop/logist/tariff-view').'?id='.Yii::$app->request->get('id').'&tariff_id='.$model['id']
                                ];
                            },
                            'tableOptions' => [
                                'class'=>'table table-striped table-bordered'
                            ],
                            'columns' => [
                                ['class' => 'yii\grid\SerialColumn'],
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
                                ]
                            ],
                        ]); ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>