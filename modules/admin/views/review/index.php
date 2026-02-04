<?php
use yii\helpers\Html;
use yii\grid\GridView;

$this->title = 'Review';
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
        <?php if (Yii::$app->session->hasFlash('review_removed')) {?>
            <div class="callout callout-success text-center">
                <?=Yii::$app->session->getFlash('review_removed');?>
            </div>
        <?php }?>
        <div class="box box-info color-palette-box">
            <div class="box-header with-border">
                <div id="action-links">
                    <a href="javascript:;" class="btn btn-danger" data-value="remove"><i class="fa fa-trash"></i> Delete</a>
                </div>
            </div>
            <div class="box-body" id="item-block">
                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'summary' => "Page {begin} - {end} of {totalCount} review<br/><br/>",
                    'emptyText' => 'No reviews',
                    'rowOptions' => function ($model, $index, $widget, $grid) {
                        return [
                            'id' => $model['id'],
                            'url' => Yii::$app->urlManager->createUrl('/admin/review/view').'?id='.$model['id']
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
                            'label'=>'<i class="fa fa-sort"></i> Product',
                            'encodeLabel' => false,
                            'format' => 'html',
                            'filter' => Html::activeDropDownList($searchModel, 'product_id', $products, ['class'=>'form-control select2','prompt' => 'Select']),
                            'value' => function ($model, $key, $index, $column) {
                                return ($model->product && $model->product->name_ru) ? '<a href="'.Yii::$app->urlManager->createUrl(['/admin/product/view', 'id'=>$model->product->id]).'">'.$model->product->name_ru.'</a>' : '<small class="label bg-red">No</small>';
                            },
                        ],
                        [
                            'attribute'=>'user_id',
                            'label'=>'<i class="fa fa-sort"></i> User',
                            'encodeLabel' => false,
                            'format' => 'html',
                            'filter' => Html::activeDropDownList($searchModel, 'user_id', $users, ['class'=>'form-control select2','prompt' => 'Select']),
                            'value' => function ($model, $key, $index, $column) {
                                return ($model->user && $model->user->name) ? '<a href="'.Yii::$app->urlManager->createUrl(['/admin/user/view', 'id'=>$model->user->id]).'">'.$model->user->name.'</a>' : '<small class="label bg-red">No</small>';
                            },
                        ],
                        [
                            'attribute'=>'rate',
                            'label'=>'<i class="fa fa-sort"></i> Rating',
                            'encodeLabel' => false,
                        ],
                        [
                            'attribute'=>'status',
                            'label'=>'<i class="fa fa-sort"></i> Статус',
                            'encodeLabel' => false,
                            'format' => 'html',
                            'filter' => Html::activeDropDownList($searchModel, 'status', 
                                app\models\product\review\ProductReview::getStatusLabels(), 
                                ['class'=>'form-control','prompt' => 'Все статусы']
                            ),
                            'value' => function ($model, $key, $index, $column) {
                                return '<span class="label label-'.$model->getStatusColor().'">'.$model->getStatusLabel().'</span>';
                            },
                        ],
                        [
                            'label'=>'<i class="fa fa-edit"></i> Обновить статус',
                            'encodeLabel' => false,
                            'format' => 'html',
                            'value' => function ($model, $key, $index, $column) {
                                $buttons = '<div class="btn-group-vertical" style="width: 100%;">';
                                
                                // Accept button
                                if ($model->status != app\models\product\review\ProductReview::STATUS_ACCEPTED) {
                                    $buttons .= Html::a('<i class="fa fa-check"></i> Принять', 
                                        Yii::$app->urlManager->createUrl(['/admin/review/accept', 'id'=>$model->id]), 
                                        [
                                            'class'=>'btn btn-success btn-xs',
                                            'style' => 'margin-bottom: 2px; width: 100%;',
                                            'title' => 'Принять отзыв',
                                            'data-confirm' => 'Вы уверены, что хотите принять этот отзыв?'
                                        ]
                                    );
                                }
                                
                                // Reject button
                                if ($model->status != app\models\product\review\ProductReview::STATUS_REJECTED) {
                                    $buttons .= Html::a('<i class="fa fa-times"></i> Отклонить', 
                                        'javascript:void(0)', 
                                        [
                                            'class'=>'btn btn-danger btn-xs reject-review',
                                            'style' => 'margin-bottom: 2px; width: 100%;',
                                            'title' => 'Отклонить отзыв',
                                            'data-id' => $model->id
                                        ]
                                    );
                                }
                                
                                // Process button
                                if ($model->status == app\models\product\review\ProductReview::STATUS_ACCEPTED) {
                                    $buttons .= Html::a('<i class="fa fa-cog"></i> Обработать', 
                                        Yii::$app->urlManager->createUrl(['/admin/review/process', 'id'=>$model->id]), 
                                        [
                                            'class'=>'btn btn-info btn-xs',
                                            'style' => 'margin-bottom: 2px; width: 100%;',
                                            'title' => 'Отметить как обработанный',
                                            'data-confirm' => 'Отметить этот отзыв как обработанный?'
                                        ]
                                    );
                                }
                                
                                // Reset to pending button
                                if ($model->status != app\models\product\review\ProductReview::STATUS_PENDING) {
                                    $buttons .= Html::a('<i class="fa fa-refresh"></i> В ожидание', 
                                        'javascript:void(0)', 
                                        [
                                            'class'=>'btn btn-warning btn-xs change-status',
                                            'style' => 'margin-bottom: 2px; width: 100%;',
                                            'title' => 'Вернуть в ожидание',
                                            'data-id' => $model->id,
                                            'data-status' => app\models\product\review\ProductReview::STATUS_PENDING,
                                            'data-confirm' => 'Вернуть отзыв в ожидание?'
                                        ]
                                    );
                                }
                                
                                $buttons .= '</div>';
                                
                                return $buttons;
                            },
                        ],
                        [
                            'attribute'=>'date',
                            'label'=>'<i class="fa fa-sort"></i> Date',
                            'encodeLabel' => false,
                        ],
                        [
                            'class' => 'yii\grid\ActionColumn',
                            'template' => '{update} {delete}',
                            'buttons' => [
                                'update' => function ($url, $model) {
                                    return Html::a('<span class="glyphicon glyphicon-eye-open"></span>', Yii::$app->urlManager->createUrl(['/admin/review/view', 'id'=>$model->id]), ['class'=>'btn btn-info']);
                                },
                                'delete' => function ($url, $model) {
                                    return Html::a('<span class="glyphicon glyphicon-trash"></span>', Yii::$app->urlManager->createUrl(['/admin/review/remove', 'id'=>$model->id]), ['class'=>'btn btn-danger remove-object']);
                                }
                            ],
                        ]
                    ],
                ]); ?>
            </div>
        </div>
    </section>
</div>

<!-- Reject Review Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Отклонить отзыв</h4>
            </div>
            <div class="modal-body">
                <form id="rejectForm">
                    <input type="hidden" id="rejectReviewId" name="id">
                    <div class="form-group">
                        <label for="rejectComment">Причина отклонения:</label>
                        <textarea class="form-control" id="rejectComment" name="comment" rows="3" placeholder="Укажите причину отклонения отзыва..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-danger" id="confirmReject">Отклонить</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Handle reject button click
    $('.reject-review').on('click', function() {
        var reviewId = $(this).data('id');
        $('#rejectReviewId').val(reviewId);
        $('#rejectComment').val('');
        $('#rejectModal').modal('show');
    });
    
    // Handle change status button click  
    $('.change-status').on('click', function(e) {
        e.preventDefault();
        var reviewId = $(this).data('id');
        var status = $(this).data('status');
        var confirmed = $(this).data('confirm');
        
        if (confirm(confirmed)) {
            var url = '<?= Yii::$app->urlManager->createUrl(['/admin/review/change-status']) ?>';
            var postData = {
                id: reviewId,
                status: status,
                '<?= Yii::$app->request->csrfParam ?>': '<?= Yii::$app->request->csrfToken ?>'
            };
            
            console.log('Sending POST to:', url);
            console.log('Data:', postData);
            
            $.ajax({
                url: url,
                type: 'POST',
                data: postData,
                dataType: 'json',
                success: function(data) {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Ошибка: ' + data.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.log('AJAX Error:', xhr.responseText);
                    console.log('Status:', status);
                    console.log('Error:', error);
                    alert('Произошла ошибка сети: ' + error);
                }
            });
        }
    });
    
    // Handle confirm reject
    $('#confirmReject').on('click', function() {
        var formData = {
            id: $('#rejectReviewId').val(),
            status: <?= app\models\product\review\ProductReview::STATUS_REJECTED ?>,
            comment: $('#rejectComment').val()
        };
        
        formData['<?= Yii::$app->request->csrfParam ?>'] = '<?= Yii::$app->request->csrfToken ?>';
        
        $.post('<?= Yii::$app->urlManager->createUrl(['/admin/review/change-status']) ?>', formData, function(data) {
            if (data.success) {
                $('#rejectModal').modal('hide');
                location.reload();
            } else {
                alert('Ошибка: ' + data.message);
            }
        }).fail(function(xhr, status, error) {
            console.log('AJAX Error:', xhr.responseText);
            alert('Произошла ошибка сети: ' + error);
        });
    });
});
</script>  