<?php
use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;

$this->title = 'Модераторы';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="content-wrapper">
    <section class="content-header">
        <h1><?=$this->title;?></h1>

        <ol class="breadcrumb">
            <li><a href="<?=Yii::$app->urlManager->createUrl(['/shop/'])?>"><i class="fa fa-dashboard"></i> Home</a></li>
            <li class="active"><?=$this->title;?></li>
        </ol>
    </section>
    <section class="content">
        <?php if (Yii::$app->session->hasFlash('moderator_removed')) {?>
            <div class="alert alert-success text-center"><?=Yii::$app->session->getFlash('moderator_removed');?></div>
        <?php }?>
        <div class="box box-info color-palette-box">
            <div class="box-header">
                <div class="pull-right">
                    <a href="<?=Yii::$app->urlManager->createUrl(['/shop/moderator/create'])?>" class="btn btn-primary"><i class="fa fa-plus"></i> Добавить модератора</a>
                </div>
                <div id="action-links">
                    <a href="javascript:;" class="btn btn-danger" data-value="remove"><i class="fa fa-trash"></i> Удалить</a>
                </div>
            </div>
            <div class="box-body" id="item-block">
                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'summary' => "Страница {begin} - {end} из {totalCount} модераторов<br/><br/>",
                    'emptyText' => 'Модераторы не найдены',
                    'rowOptions' => function ($model, $index, $widget, $grid) {
                        return [
                            'id' => $model['id'],
                            'url' => Yii::$app->urlManager->createUrl('/shop/moderator/view').'?id='.$model['id']
                        ];
                    },
                    'pager' => [
                        'options'=>['class'=>'pagination'],
                        'pageCssClass' => 'page-item',
                        'prevPageLabel' => 'Назад',
                        'nextPageLabel' => 'Вперед',
                        'maxButtonCount'=>10,
                        'linkOptions' => [
                            'class' => 'page-link'
                        ]
                        ],
                    'tableOptions' => [
                        'class'=>'table table-striped'
                    ],
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],
                        [
                            'class' => 'yii\grid\CheckboxColumn'
                        ],
                        [
                            'label' => 'Фото',
                            'format' => 'html',
                            'value' => function($data) { return Html::img($data->getPhoto('50x50'), ['width'=>'30']); },
                        ],
                        [
                            'attribute'=>'id',
                            'label'=>'<i class="fa fa-sort"></i> ID',
                            'encodeLabel' => false,
                        ],
                        [
                            'attribute'=>'name',
                            'label'=>'<i class="fa fa-sort"></i> Имя',
                            'encodeLabel' => false,
                            'value' => function ($model, $key, $index, $column) {
                                return ($model->{$column->attribute}) ? $model->{$column->attribute} : 'Не указано';
                            },
                        ],
                        [
                            'attribute'=>'phone',
                            'label'=>'<i class="fa fa-sort"></i> Телефон',
                            'encodeLabel' => false,
                            'value' => function ($model, $key, $index, $column) {
                                return ($model->{$column->attribute}) ? $model->{$column->attribute} : 'Не указано';
                            },
                        ],
                        [
                            'attribute'=>'login',
                            'label'=>'<i class="fa fa-sort"></i> Логин',
                            'encodeLabel' => false,
                            'value' => function ($model, $key, $index, $column) {
                                return ($model->{$column->attribute}) ? $model->{$column->attribute} : 'Не указано';
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
                            'attribute'=>'status',
                            'label'=>'<i class="fa fa-sort"></i> Статус',
                            'encodeLabel' => false,
                            'format' => 'html',
                            'value' => function ($model, $key, $index, $column) {
                                if ($model->status == 1) {
                                    return '<small class="label bg-green">Активный</small>';
                                }
                                if ($model->status == 2) {
                                    return '<small class="label bg-red">Заблокирован</small>';
                                }
                            },
                        ],
                        [
                            'class' => 'yii\grid\ActionColumn',
                            'template' => '{update} {delete}',
                            'buttons' => [
                                'update' => function ($url, $model) use ($words) {
                                    return Html::a('<span class="glyphicon glyphicon-pencil"></span>', Yii::$app->urlManager->createUrl(['/shop/moderator/create', 'id'=>$model->id]), ['class'=>'btn btn-warning']);
                                },
                                'delete' => function ($url, $model) use ($words) {
                                    return Html::a('<span class="glyphicon glyphicon-trash"></span>', Yii::$app->urlManager->createUrl(['/shop/moderator/remove', 'id'=>$model->id]), ['class'=>'btn btn-danger remove-object']);
                                }
                            ],
                        ]
                    ],
                ]); ?>
            </div>
        </div>
    </section>
</div>