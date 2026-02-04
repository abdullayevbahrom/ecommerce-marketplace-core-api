<?php
use yii\helpers\Html;
use yii\grid\GridView;

$this->title = 'Товары';
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
        <?php if (Yii::$app->session->hasFlash('product_removed')) {?>
            <div class="callout callout-success text-center">
                <?=Yii::$app->session->getFlash('product_removed');?>
            </div>
        <?php }?>
        <div class="box box-info color-palette-box">
            <div class="box-header with-border">
                <div class="pull-right">
                    <a href="<?=Yii::$app->urlManager->createUrl(['/shop/product/create'])?>" class="btn btn-primary">
                        <i class="glyphicon glyphicon-plus"></i> Добавить товар
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
                    'summary' => "Страница {begin} - {end} из {totalCount} товаров<br/><br/>",
                    'emptyText' => 'Товаров нет',
                    'rowOptions' => function ($model, $index, $widget, $grid) {
                        return [
                            'id' => $model['id'],
                            'url' => Yii::$app->urlManager->createUrl('/shop/product/view').'?id='.$model['id']
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
                            'label'=>'<i class="fa fa-sort"></i> Пользователь',
                            'encodeLabel' => false,
                            'format' => 'html',
                            'contentOptions' => [
                                'style' => 'width:130px'
                            ],
                            'filter' => Html::activeDropDownList($searchModel, 'user_id', $users, ['class'=>'form-control select2','prompt' => 'Выбрать']),
                            'value' => function ($model, $key, $index, $column) {
                                return ($model->user && $model->user->name) ? '<a href="'.Yii::$app->urlManager->createUrl(['/shop/user/view', 'id'=>$model->user->id]).'">'.$model->user->name.'</a>' : 'Не указано';
                            },
                        ],
                        [
                            'attribute'=>'stock_id',
                            'label'=>'<i class="fa fa-sort"></i> Склад',
                            'encodeLabel' => false,
                            'format' => 'html',
                            'filter' => Html::activeDropDownList($searchModel, 'stock_id', $stocks, ['class'=>'form-control select2','prompt' => 'Выбрать']),
                            'value' => function ($model, $key, $index, $column) {
                                return $model->stock ? '<a href="'.Yii::$app->urlManager->createUrl(['/admin/stock/view', 'id'=>$model->shop->id]).'">'.$model->stock->name_ru.'</a>' : 'Не указано';
                            },
                        ],
                        [
                            'attribute'=>'category_id',
                            'label'=>'<i class="fa fa-sort"></i> Категория',
                            'encodeLabel' => false,
                            'format' => 'html',
                            'filter' => Html::activeDropDownList($searchModel, 'category_id', $categories, ['class'=>'form-control select2','prompt' => 'Выбрать']),
                            'value' => function ($model, $key, $index, $column) {
                                return $model->category ? $model->category->name_ru : 'Не указано';
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
                            'attribute'=>'price',
                            'label'=>'<i class="fa fa-sort"></i> Цена',
                            'encodeLabel' => false,
                            'value' => function ($model, $key, $index, $column) {
                                return ($model->{$column->attribute}) ? number_format($model->{$column->attribute}) : 'Не указано';
                            },
                        ],
                        [
                            'attribute'=>'amount',
                            'label'=>'<i class="fa fa-sort"></i> Кол-во',
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
                            'value' => function ($model, $key, $index, $column) {
                                return ($model->{$column->attribute}) ? $model->{$column->attribute} : 'Не указано';
                            },
                        ],
                        [
                            'class' => 'yii\grid\ActionColumn',
                            'template' => '{update} {delete}',
                            'buttons' => [
                                'update' => function ($url, $model) use ($user) {
                                    if ($user->manager != 1) {
                                        return Html::a('<span class="glyphicon glyphicon-pencil"></span>', Yii::$app->urlManager->createUrl(['/shop/product/create', 'id'=>$model->id]), ['class'=>'btn btn-warning']);
                                    }
                                },
                                'delete' => function ($url, $model) use ($user) {
                                    if ($user->manager != 1) {
                                        return Html::a('<span class="glyphicon glyphicon-trash"></span>', Yii::$app->urlManager->createUrl(['/shop/product/remove', 'id'=>$model->id]), ['class'=>'btn btn-danger remove-object']);
                                    }
                                }
                            ],
                        ]
                    ],
                ]); ?>
            </div>
        </div>
    </section>
</div>