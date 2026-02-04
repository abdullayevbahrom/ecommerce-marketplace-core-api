<?php
use yii\helpers\Html;
use yii\grid\GridView;

$this->title = 'Бренды';
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
        <?php if (Yii::$app->session->hasFlash('brand_removed')) {?>
            <div class="callout callout-success text-center">
                <?=Yii::$app->session->getFlash('brand_removed');?>
            </div>
        <?php }?>
        <div class="box box-info color-palette-box">
            <div class="box-header with-border">
                <div class="box-title pull-right" style="font-size: 14px">
                    <a href="<?=Yii::$app->urlManager->createUrl(['/shop/brand/create'])?>" class="btn btn-primary">
                        <i class="fa fa-plus"></i>
                        Добавить бренд
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
                    'summary' => "Страница {begin} - {end} из {totalCount} брендов<br/><br/>",
                    'emptyText' => 'Брендов нет',
                    'rowOptions' => function ($model, $index, $widget, $grid) {
                        return [
                            'id' => $model['id'],
                            'url' => Yii::$app->urlManager->createUrl('/shop/brand/view').'?id='.$model['id']
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
                            'label' => 'Фото',
                            'format' => 'html',
                            'value' => function($data) { return Html::img($data->getPhoto('250x250/'), ['width'=>'30']); },
                        ],
                        [
                            'attribute'=>'id',
                            'label'=>'<i class="fa fa-sort"></i> ID',
                            'encodeLabel' => false,
                        ],
                        [
                            'attribute'=>'category_id',
                            'label'=>'<i class="fa fa-sort"></i> Категория',
                            'encodeLabel' => false,
                            'format' => 'html',
                            'filter' => Html::activeDropDownList($searchModel, 'category_id', $categories, ['class'=>'form-control select2','prompt' => 'Выбрать']),
                            'value' => function ($model, $key, $index, $column) {
                                return ($model->category) ? $model->category->name_ru : 'Не указано';
                            },
                        ],
                        [
                            'attribute'=>'name_ru',
                            'label'=>'<i class="fa fa-sort"></i> Название',
                            'encodeLabel' => false,
                            'value' => function ($model, $key, $index, $column) {
                                return ($model->{$column->attribute}) ? $model->{$column->attribute} : 'Не указано';
                            },
                        ],
                        [
                            'attribute'=>'status',
                            'label'=>'<i class="fa fa-sort"></i> Статус',
                            'encodeLabel' => false,
                            'format' => 'html',
                            'filter' => Html::activeDropDownList($searchModel, 'status', ['1'=>'Активный', '2'=>'Заблокирован'], ['class'=>'form-control select2','prompt' => 'Выбрать']),
                            'value' => function ($model, $key, $index, $column) {
                                if ($model->status == 2) {
                                    return '<small class="label bg-red">Заблокирован</small>';
                                }
                                if ($model->status == 1) {
                                    return '<small class="label bg-green">Активный</small>';
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
                            'template' => '{update} {delete}',
                            'buttons' => [
                                'update' => function ($url, $model) {
                                    return Html::a('<span class="glyphicon glyphicon-pencil"></span>', Yii::$app->urlManager->createUrl(['/shop/brand/create', 'id'=>$model->id]), ['class'=>'btn btn-warning']);
                                },
                                'delete' => function ($url, $model) {
                                    return Html::a('<span class="glyphicon glyphicon-trash"></span>', Yii::$app->urlManager->createUrl(['/shop/brand/remove', 'id'=>$model->id]), ['class'=>'btn btn-danger remove-object']);
                                }
                            ],
                        ]
                    ],
                ]); ?>
            </div>
        </div>
    </section>
</div>  