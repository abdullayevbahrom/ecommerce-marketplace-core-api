<?php
use yii\helpers\Html;
use yii\grid\GridView;

use app\widgets\admin_user_menu\AdminUserMenu;

$this->title = 'Заказы пользователя';
$this->params['breadcrumbs'][] = $this->title;
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
        <div class="row">
            <div class="col-sm-3">
                <?=AdminUserMenu::widget();?>
            </div>
            <div class="col-sm-9">
                <div class="box box-info color-palette-box">
                    <div class="box-header with-border">
                        <div class="box-title">
                            Список заказов
                        </div>
                        <div id="action-links">
                            <a href="javascript:;" class="btn btn-danger" data-value="remove"><i class="fa fa-trash"></i> Удалить</a>
                        </div>
                    </div>
                    <div class="box-body" id="item-block">
                        <?= GridView::widget([
                            'dataProvider' => $dataProvider,
                            'filterModel' => $searchModel,
                            'summary' => "Страница {begin} - {end} из {totalCount} заказов<br/><br/>",
                            'emptyText' => 'Заказов нет',
                            'rowOptions' => function ($model, $index, $widget, $grid) {
                                return [
                                    'id' => $model['id'],
                                    'url' => Yii::$app->urlManager->createUrl('/shop/order/view').'?id='.$model['id']
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
                                    'label'=>'<i class="fa fa-sort"></i> Стоимость',
                                    'encodeLabel' => false,
                                    'value' => function ($model, $key, $index, $column) {
                                        return ($model->price) ? number_format($model->price) : 'Не указано';
                                    },
                                ],
                                [
                                    'attribute'=>'amount',
                                    'label'=>'<i class="fa fa-sort"></i> Кол-во',
                                    'encodeLabel' => false,
                                    'value' => function ($model, $key, $index, $column) {
                                        return ($model->amount) ? $model->amount : 'Не указано';
                                    },
                                ],
                                [
                                    'attribute'=>'status',
                                    'label'=>'<i class="fa fa-sort"></i> Статус',
                                    'encodeLabel' => false,
                                    'format' => 'html',
                                    'filter' => Html::activeDropDownList($searchModel, 'status', ['0'=>'В ожидании', '1'=>'Принят', 'Отклонен'], ['class'=>'form-control select2','prompt' => 'Выбрать']),
                                    'value' => function ($model, $key, $index, $column) {
                                        if ($model->status == 0) {
                                            return '<small class="label bg-yellow">В ожидании</small>';
                                        }
                                        if ($model->status == 1) {
                                            return '<small class="label bg-green">Принят</small>';
                                        }
                                        if ($model->status == 1) {
                                            return '<small class="label bg-red">Отклонен</small>';
                                        }
                                    },
                                ],
                                [
                                    'attribute'=>'date',
                                    'label'=>'<i class="fa fa-sort"></i> Дата',
                                    'encodeLabel' => false,
                                ],
                                [
                                    'class' => 'yii\grid\ActionColumn',
                                    'template' => '{delete}',
                                    'buttons' => [
                                        'delete' => function ($url, $model) {
                                            return Html::a('<span class="glyphicon glyphicon-trash"></span>', Yii::$app->urlManager->createUrl(['/shop/order/remove', 'id'=>$model->id]), ['class'=>'btn btn-danger remove-object']);
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