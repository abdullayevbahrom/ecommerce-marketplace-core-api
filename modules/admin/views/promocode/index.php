<?php

use yii\helpers\Html;
use yii\grid\GridView;
use app\models\Promocode;

/* @var $this yii\web\View */
/* @var $searchModel app\models\PromocodeSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Промокоды';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="content-wrapper">
    <section class="content-header">
        <h1>
            <?= Html::encode($this->title) ?>
        </h1>
    </section>

    <section class="content">
        <div class="box box-primary">
            <div class="box-header with-border">
                <?= Html::a('Создать промокод', ['create'], ['class' => 'btn btn-success']) ?>
            </div>
            <div class="box-body table-responsive">
                <style>
                    .grid-view tbody tr {
                        cursor: pointer;
                    }
                    .grid-view tbody tr:hover {
                        background-color: #f5f5f5;
                    }
                </style>
                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'rowOptions' => function($model) {
                        return ['data-href' => \yii\helpers\Url::to(['view', 'id' => $model->id])];
                    },
                    'columns' => [
                        [
                            'attribute' => 'id',
                            'headerOptions' => ['width' => '60px'],
                        ],
                        [
                            'attribute' => 'code',
                            'format' => 'raw',
                            'value' => function($model) {
                                return '<code>' . Html::encode($model->code) . '</code>';
                            }
                        ],
                        [
                            'attribute' => 'type',
                            'value' => function($model) {
                                return $model->type == Promocode::TYPE_FIXED ? 'Фикс. сумма' : 'Процент';
                            },
                            'filter' => [Promocode::TYPE_FIXED => 'Фикс. сумма', Promocode::TYPE_PERCENT => 'Процент'],
                        ],
                        [
                            'attribute' => 'value',
                            'value' => function($model) {
                                return $model->type == Promocode::TYPE_FIXED 
                                    ? number_format($model->value, 0, '.', ' ') . ' сум' 
                                    : $model->value . '%';
                            }
                        ],
                        [
                            'attribute' => 'status',
                            'format' => 'raw',
                            'value' => function($model) {
                                return $model->status == 1 
                                    ? '<span class="label label-success">Активен</span>' 
                                    : '<span class="label label-danger">Неактивен</span>';
                            },
                            'filter' => [1 => 'Активен', 0 => 'Неактивен'],
                        ],
                        [
                            'attribute' => 'end_date',
                            'format' => ['datetime', 'php:d.m.Y H:i'],
                            'value' => function($model) {
                                return $model->end_date ? strtotime($model->end_date) : null;
                            }
                        ],
                        [
                            'attribute' => 'user_id',
                            'format' => 'raw',
                            'value' => function($model) {
                                if (!$model->user_id) return '<span class="text-muted">Для всех</span>';
                                $user = \app\models\user\User::findOne($model->user_id);
                                return $user ? Html::a('ID: ' . $model->user_id . ' (' . Html::encode($user->name) . ')', ['/admin/user/view', 'id' => $user->id], ['data-pjax' => 0]) : 'User #' . $model->user_id;
                            }
                        ],
                        [
                            'attribute' => 'usage_limit',
                            'value' => function($model) {
                                return $model->usage_limit ?: '∞';
                            }
                        ],
                        
                        [
                            'class' => 'yii\grid\ActionColumn',
                            'header' => 'Действия',
                            'headerOptions' => ['width' => '80px'],
                            'template' => '{view} {update} {delete}',
                            'buttons' => [
                                'view' => function ($url, $model) {
                                    return Html::a('<span class="glyphicon glyphicon-eye-open"></span>', $url, [
                                        'class' => 'btn btn-default btn-xs',
                                        'title' => 'Просмотр',
                                        'data-pjax' => '0',
                                    ]);
                                },
                                'update' => function ($url, $model) {
                                    return Html::a('<span class="glyphicon glyphicon-pencil"></span>', $url, [
                                        'class' => 'btn btn-primary btn-xs',
                                        'title' => 'Редактировать',
                                        'data-pjax' => '0',
                                    ]);
                                },
                                'delete' => function ($url, $model) {
                                    return Html::a('<span class="glyphicon glyphicon-trash"></span>', $url, [
                                        'class' => 'btn btn-danger btn-xs',
                                        'title' => 'Удалить',
                                        'data-confirm' => 'Вы уверены, что хотите удалить этот элемент?',
                                        'data-method' => 'post',
                                        'data-pjax' => '0',
                                    ]);
                                },
                            ],
                        ],
                    ],
                ]); ?>
            </div>
        </div>
    </section>
</div>

<?php
$script = <<< JS
$('body').on('click', '.grid-view tbody tr', function(e) {
    // Don't trigger if clicked on a link or button
    if ($(e.target).closest('a, button, input').length > 0) {
        return;
    }
    
    var url = $(this).data('href');
    if (url) {
        window.location.href = url;
    }
});
JS;
$this->registerJs($script);
?>
