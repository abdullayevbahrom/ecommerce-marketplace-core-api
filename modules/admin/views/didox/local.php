<?php
use yii\helpers\Html;
use yii\grid\GridView;

$this->title = 'Local DIDOX Documents';
$this->params['breadcrumbs'][] = ['label' => 'DIDOX Documents', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="content-wrapper">
    <section class="content-header">
        <h1><?= Html::encode($this->title) ?></h1>
        <ol class="breadcrumb">
            <li><a href="<?= Yii::$app->urlManager->createUrl(['/admin/']) ?>"><i class="fa fa-dashboard"></i> Home</a></li>
            <li><a href="<?= Yii::$app->urlManager->createUrl(['/admin/didox']) ?>">DIDOX Documents</a></li>
            <li class="active">Local Documents</li>
        </ol>
    </section>

    <section class="content">
        <?php if (Yii::$app->session->hasFlash('didox_success')): ?>
            <div class="callout callout-success">
                <?= Yii::$app->session->getFlash('didox_success') ?>
            </div>
        <?php endif; ?>
        
        <?php if (Yii::$app->session->hasFlash('didox_error')): ?>
            <div class="callout callout-danger">
                <?= Yii::$app->session->getFlash('didox_error') ?>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-12">
                <div class="box box-info color-palette-box">
            <div class="box-header with-border">
                <div class="box-title pull-right" style="font-size: 14px">
                    <a href="<?= Yii::$app->urlManager->createUrl(['/admin/didox/create']) ?>" class="btn btn-primary">
                        <i class="fa fa-plus"></i>
                        Create Document
                    </a>
                    <a href="<?= Yii::$app->urlManager->createUrl(['/admin/didox']) ?>" class="btn btn-default">
                        <i class="fa fa-arrow-left"></i>
                        Back to DIDOX
                    </a>
                </div>
                <div id="action-links">
                    <a href="javascript:;" class="btn btn-danger" data-value="remove"><i class="fa fa-trash"></i> Delete</a>
                </div>
            </div>
            <div class="box-body">
                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'layout' => '{items}{pager}',
                    'tableOptions' => ['class' => 'table table-striped'],
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],
                        [
                            'attribute' => 'id',
                            'label' => '<i class="fa fa-sort"></i> ID',
                            'encodeLabel' => false,
                        ],
                                                 [
                             'attribute' => 'name',
                             'label' => '<i class="fa fa-sort"></i> Document Name',
                             'encodeLabel' => false,
                             'value' => function ($model, $key, $index, $column) {
                                 return $model->name ? $model->name : 'Unnamed Document';
                             },
                         ],
                        [
                            'attribute' => 'didox_id',
                            'label' => '<i class="fa fa-sort"></i> DIDOX ID',
                            'encodeLabel' => false,
                            'format' => 'html',
                            'value' => function ($model, $key, $index, $column) {
                                if ($model->didox_id) {
                                    return '<code title="' . Html::encode($model->didox_id) . '">' . 
                                           Html::encode(substr($model->didox_id, 0, 12)) . '...</code>';
                                } else {
                                    return '<small class="text-muted">Not connected</small>';
                                }
                            },
                        ],
                        [
                            'attribute' => 'document_type',
                            'label' => '<i class="fa fa-sort"></i> Type',
                            'encodeLabel' => false,
                            'format' => 'html',
                            'value' => function ($model, $key, $index, $column) {
                                if ($model->document_type) {
                                    return '<span class="label label-info">' . $model->document_type . '</span><br>' .
                                           '<small>' . $model->getDocumentTypeLabel() . '</small>';
                                } else {
                                    return '<small class="text-muted">No type</small>';
                                }
                            },
                        ],
                        [
                            'attribute' => 'didox_status',
                            'label' => '<i class="fa fa-sort"></i> DIDOX Status',
                            'encodeLabel' => false,
                            'format' => 'html',
                            'value' => function ($model, $key, $index, $column) {
                                if ($model->isDidoxDocument()) {
                                    return '<small class="label ' . $model->getDidoxStatusColor() . '">' . 
                                           $model->getDidoxStatusLabel() . '</small><br>' .
                                           '<small class="text-muted">Code: ' . $model->didox_status . '</small>';
                                } else {
                                    return '<small class="label bg-gray">No DIDOX</small>';
                                }
                            },
                        ],
                        [
                            'attribute' => 'status',
                            'label' => '<i class="fa fa-sort"></i> Local Status',
                            'encodeLabel' => false,
                            'format' => 'html',
                            'value' => function ($model, $key, $index, $column) {
                                if ($model->status == 2) {
                                    return '<small class="label bg-red">Blocked</small>';
                                }
                                if ($model->status == 1) {
                                    return '<small class="label bg-green">Active</small>';
                                }
                                return '<small class="label bg-gray">Unknown</small>';
                            },
                        ],
                        [
                            'attribute' => 'didox_created_at',
                            'label' => '<i class="fa fa-sort"></i> Created',
                            'encodeLabel' => false,
                            'format' => 'html',
                            'value' => function ($model, $key, $index, $column) {
                                if ($model->didox_created_at) {
                                    return date('Y-m-d H:i', strtotime($model->didox_created_at));
                                }
                                return '<small class="text-muted">Not created</small>';
                            },
                        ],
                        [
                            'attribute' => 'didox_signed_at',
                            'label' => '<i class="fa fa-sort"></i> Signed',
                            'encodeLabel' => false,
                            'format' => 'html',
                            'value' => function ($model, $key, $index, $column) {
                                if ($model->didox_signed_at) {
                                    return '<span class="text-success">' . date('Y-m-d H:i', strtotime($model->didox_signed_at)) . '</span>';
                                }
                                return '<small class="text-muted">Not signed</small>';
                            },
                        ],
                        [
                            'class' => 'yii\grid\ActionColumn',
                            'template' => '{view} {sync} {sign} {cancel}',
                            'buttons' => [
                                'view' => function ($url, $model, $key) {
                                    return Html::a('<i class="fa fa-eye"></i>', 
                                        ['/admin/didox/view', 'id' => $model->id], [
                                        'title' => 'View Document',
                                        'class' => 'btn btn-xs btn-info',
                                        'data-pjax' => '0'
                                    ]);
                                },
                                'sync' => function ($url, $model, $key) {
                                    if ($model->isDidoxDocument()) {
                                        return Html::a('<i class="fa fa-refresh"></i>', 
                                            ['/admin/didox/sync', 'id' => $model->id], [
                                            'title' => 'Sync Status',
                                            'class' => 'btn btn-xs btn-warning',
                                            'data-pjax' => '0'
                                        ]);
                                    }
                                    return '';
                                },
                                'sign' => function ($url, $model, $key) {
                                    if ($model->canBeSignedInDidox()) {
                                        return Html::button('<i class="fa fa-edit"></i>', [
                                            'title' => 'Sign Document',
                                            'class' => 'btn btn-xs btn-success',
                                            'onclick' => 'signDocumentFromGrid(' . $model->id . ')'
                                        ]);
                                    }
                                    return '';
                                },
                                'cancel' => function ($url, $model, $key) {
                                    if ($model->canBeCanceledInDidox()) {
                                        return Html::a('<i class="fa fa-times"></i>', 
                                            ['/admin/didox/cancel', 'id' => $model->id], [
                                            'title' => 'Cancel Document',
                                            'class' => 'btn btn-xs btn-danger',
                                            'data-pjax' => '0',
                                            'data-confirm' => 'Are you sure you want to cancel this DIDOX document?'
                                        ]);
                                    }
                                    return '';
                                }
                            ],
                        ],
                    ],
                ]);?>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
function signDocumentFromGrid(documentId) {
    // Redirect to the view page where the signing modal can be opened
    window.location.href = '<?= Yii::$app->urlManager->createUrl(["/admin/didox/view"]) ?>/' + documentId;
}

// Handle bulk delete action
$(document).ready(function() {
    $('[data-value="remove"]').click(function() {
        var selected = [];
        $('input[name="selection[]"]:checked').each(function() {
            selected.push($(this).val());
        });
        
        if (selected.length === 0) {
            alert('Please select documents to delete');
            return;
        }
        
        if (confirm('Are you sure you want to delete ' + selected.length + ' selected documents?')) {
            // Implement bulk delete logic here
            alert('Bulk delete functionality would be implemented here');
        }
    });
});
</script> 