<?php
use yii\helpers\Html;
use yii\grid\GridView;
use app\models\product\ProductRequest;

$this->title = 'Запросы на товар';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="content-wrapper">
    <section class="content-header">
        <h1><?=$this->title;?></h1>
        
        <ol class="breadcrumb">
            <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/'])?>"><i class="fa fa-dashboard"></i> Главная</a></li>
            <li class="active"><?=$this->title;?></li>
        </ol>
    </section>
    <section class="content">
        <?php if (Yii::$app->session->hasFlash('request_removed')) {?>
            <div class="callout callout-success text-center">
                <?=Yii::$app->session->getFlash('request_removed');?>
            </div>
        <?php }?>
        <?php if (Yii::$app->session->hasFlash('request_approved')) {?>
            <div class="callout callout-success text-center">
                <?=Yii::$app->session->getFlash('request_approved');?>
            </div>
        <?php }?>
        <?php if (Yii::$app->session->hasFlash('request_rejected')) {?>
            <div class="callout callout-warning text-center">
                <?=Yii::$app->session->getFlash('request_rejected');?>
            </div>
        <?php }?>
        <div class="box box-info color-palette-box">
            <div class="box-header with-border">
                <div id="action-links">
                    <a href="javascript:;" class="btn btn-danger" data-value="remove"><i class="fa fa-trash"></i> Удалить выбранные</a>
                </div>
            </div>
            <div class="box-body" id="item-block">
                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'summary' => "Страница {begin} - {end} из {totalCount} запросов<br/><br/>",
                    'emptyText' => 'Запросы не найдены',
                    'rowOptions' => function ($model, $index, $widget, $grid) {
                        return [
                            'id' => $model['id'],
                            'url' => Yii::$app->urlManager->createUrl('/admin/product-request/view').'?id='.$model['id']
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
                            'label' => 'Фото',
                            'format' => 'html',
                            'value' => function($data) { 
                                return Html::img($data->getPhotoUrl(), ['width'=>'50', 'height'=>'50', 'class'=>'img-thumbnail']); 
                            },
                        ],
                        [
                            'attribute'=>'product_name',
                            'label'=>'<i class="fa fa-sort"></i> Наименование товара',
                            'encodeLabel' => false,
                        ],
                        [
                            'attribute'=>'quantity',
                            'label'=>'<i class="fa fa-sort"></i> Количество',
                            'encodeLabel' => false,
                        ],
                        [
                            'attribute'=>'phone',
                            'label'=>'<i class="fa fa-sort"></i> Телефон',
                            'encodeLabel' => false,
                        ],
                        [
                            'attribute'=>'email',
                            'label'=>'<i class="fa fa-sort"></i> Email',
                            'encodeLabel' => false,
                        ],
                        [
                            'label'=>'<i class="fa fa-user"></i> Пользователь',
                            'encodeLabel' => false,
                            'format' => 'html',
                            'value' => function($model) {
                                if ($model->user) {
                                    $userName = $model->user->name ? Html::encode($model->user->name) : 'Пользователь #' . $model->user->id;
                                    $userLink = Yii::$app->urlManager->createUrl(['/admin/user/view', 'id' => $model->user->id]);
                                    return '<div>' . 
                                           '<a href="' . $userLink . '" class="btn btn-xs btn-info">' .
                                           '<i class="fa fa-user"></i> ' . $userName . '</a>' .
                                           '<br><small class="text-muted">ID: ' . $model->user->id . '</small>' .
                                           '</div>';
                                }
                                return '<small class="text-muted">Не указан</small>';
                            },
                        ],
                        [
                            'attribute'=>'status',
                            'label'=>'<i class="fa fa-sort"></i> Статус',
                            'encodeLabel' => false,
                            'format' => 'html',
                            'filter' => Html::activeDropDownList($searchModel, 'status', ProductRequest::getStatusOptions(), ['class'=>'form-control select2','prompt' => 'Выберите статус']),
                            'value' => function ($model, $key, $index, $column) {
                                switch ($model->status) {
                                    case ProductRequest::STATUS_PENDING:
                                        return '<small class="label bg-yellow">На рассмотрении</small>';
                                    case ProductRequest::STATUS_APPROVED:
                                        return '<small class="label bg-green">Одобрено</small>';
                                    case ProductRequest::STATUS_REJECTED:
                                        return '<small class="label bg-red">Отклонено</small>';
                                    default:
                                        return '<small class="label bg-gray">Неизвестно</small>';
                                }
                            },
                        ],
                        [
                            'attribute'=>'date',
                            'label'=>'<i class="fa fa-sort"></i> Дата подачи',
                            'encodeLabel' => false,
                            'format' => 'datetime',
                        ],
                        [
                            'class' => 'yii\grid\ActionColumn',
                            'template' => '{view} {approve} {reject} {delete}',
                            'buttons' => [
                                'view' => function ($url, $model) {
                                    return Html::a('<span class="glyphicon glyphicon-eye-open"></span>', Yii::$app->urlManager->createUrl(['/admin/product-request/view', 'id'=>$model->id]), ['class'=>'btn btn-info', 'title' => 'Просмотр']);
                                },
                                'approve' => function ($url, $model) {
                                    if ($model->status != ProductRequest::STATUS_APPROVED) {
                                        return Html::a('<span class="glyphicon glyphicon-ok"></span>', Yii::$app->urlManager->createUrl(['/admin/product-request/approve', 'id'=>$model->id]), ['class'=>'btn btn-success', 'title' => 'Одобрить']);
                                    }
                                    return '';
                                },
                                'reject' => function ($url, $model) {
                                    if ($model->status != ProductRequest::STATUS_REJECTED) {
                                        return Html::a('<span class="glyphicon glyphicon-remove"></span>', Yii::$app->urlManager->createUrl(['/admin/product-request/reject', 'id'=>$model->id]), ['class'=>'btn btn-warning', 'title' => 'Отклонить']);
                                    }
                                    return '';
                                },
                                'delete' => function ($url, $model) {
                                    return Html::a('<span class="glyphicon glyphicon-trash"></span>', Yii::$app->urlManager->createUrl(['/admin/product-request/remove', 'id'=>$model->id]), ['class'=>'btn btn-danger remove-object', 'title' => 'Удалить']);
                                }
                            ],
                        ]
                    ],
                ]); ?>
            </div>
        </div>
    </section>
</div>

<script>
$(document).ready(function() {
    // Handle row clicks
    $('#item-block tbody tr').on('click', function(e) {
        if (!$(e.target).closest('a, input').length) {
            var url = $(this).attr('url');
            if (url) {
                window.location.href = url;
            }
        }
    });
    
    // Handle bulk delete
    $('#action-links a[data-value="remove"]').on('click', function() {
        var selected = $('#item-block input[type="checkbox"]:checked').map(function() {
            return $(this).closest('tr').attr('id');
        }).get();
        
        if (selected.length === 0) {
            alert('Выберите запросы для удаления');
            return;
        }
        
        if (confirm('Вы уверены, что хотите удалить выбранные запросы?')) {
            // Here you would implement bulk delete functionality
            console.log('Delete items:', selected);
        }
    });
});
</script> 