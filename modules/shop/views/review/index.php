<?php
use yii\helpers\Html;
use yii\grid\GridView;

$this->title = 'Отзывы';
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
        <?php if (Yii::$app->session->hasFlash('review_removed')) {?>
            <div class="callout callout-success text-center">
                <?=Yii::$app->session->getFlash('review_removed');?>
            </div>
        <?php }?>
        <div class="box box-info color-palette-box">
            <div class="box-header with-border">
                <div id="action-links">
                    <a href="javascript:;" class="btn btn-danger" data-value="remove"><i class="fa fa-trash"></i> Удалить</a>
                </div>
            </div>
            <div class="box-body" id="item-block">
                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'summary' => "Страница {begin} - {end} из {totalCount} отзывов<br/><br/>",
                    'emptyText' => 'Отзывов нет',
                    'rowOptions' => function ($model, $index, $widget, $grid) {
                        return [
                            'id' => $model['id'],
                            'url' => Yii::$app->urlManager->createUrl('/shop/review/view').'?id='.$model['id']
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
                            'attribute'=>'product_id',
                            'label'=>'<i class="fa fa-sort"></i> Товар',
                            'encodeLabel' => false,
                            'format' => 'html',
                            'filter' => Html::activeDropDownList($searchModel, 'product_id', $products, ['class'=>'form-control select2','prompt' => 'Выбрать']),
                            'value' => function ($model, $key, $index, $column) {
                                return ($model->product && $model->product->name_ru) ? '<a href="'.Yii::$app->urlManager->createUrl(['/shop/product/view', 'id'=>$model->product->id]).'">'.$model->product->name_ru.'</a>' : '<small class="label bg-red">Нет</small>';
                            },
                        ],
                        [
                            'attribute'=>'user_id',
                            'label'=>'<i class="fa fa-sort"></i> Пользователь',
                            'encodeLabel' => false,
                            'format' => 'html',
                            'filter' => Html::activeDropDownList($searchModel, 'user_id', $users, ['class'=>'form-control select2','prompt' => 'Выбрать']),
                            'value' => function ($model, $key, $index, $column) {
                                return ($model->user && $model->user->name) ? '<a href="'.Yii::$app->urlManager->createUrl(['/shop/user/view', 'id'=>$model->user->id]).'">'.$model->user->name.'</a>' : '<small class="label bg-red">Нет</small>';
                            },
                        ],
                        [
                            'attribute'=>'rate',
                            'label'=>'<i class="fa fa-sort"></i> Оценка',
                            'encodeLabel' => false,
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
                                    return Html::a('<span class="glyphicon glyphicon-pencil"></span>', Yii::$app->urlManager->createUrl(['/shop/review/view', 'id'=>$model->id]), ['class'=>'btn btn-warning']);
                                },
                                'delete' => function ($url, $model) {
                                    return Html::a('<span class="glyphicon glyphicon-trash"></span>', Yii::$app->urlManager->createUrl(['/shop/review/remove', 'id'=>$model->id]), ['class'=>'btn btn-danger remove-object']);
                                }
                            ],
                        ]
                    ],
                ]); ?>
            </div>
        </div>
    </section>
</div>  