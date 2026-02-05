<?php
use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;

$this->title = 'Colors';
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
        <?php if (Yii::$app->session->hasFlash('color_removed')) {?>
            <div class="callout callout-success text-center">
                <?=Yii::$app->session->getFlash('color_removed');?>
            </div>
        <?php }?>
        <div class="box box-info color-palette-box">
            <div class="box-header with-border">
                <?php if (Yii::$app->user->identity->role != \app\models\user\User::ROLE_MODERATOR): ?>
                <div class="box-title pull-right" style="font-size: 14px">
                    <a href="<?=Yii::$app->urlManager->createUrl(['/admin/color/create'])?>" class="btn btn-primary">
                        <i class="fa fa-plus"></i>
                        Add color
                    </a>
                </div>
                <?php endif ?>
                <div id="action-links" style="display:none">
                    <a href="javascript:;" class="btn btn-danger" data-value="remove"><i class="fa fa-trash"></i> Delete</a>
                    <a href="javascript:;" class="btn btn-warning" data-value="disable"><i class="fa fa-lock"></i> Block</a>
                    <a href="javascript:;" class="btn btn-success" data-value="enable"><i class="fa fa-unlock"></i> Unblock</a>
                </div>
            </div>
            <div class="box-body" id="item-block">
                <?php Pjax::begin(); ?>
                    <?= GridView::widget([
                        'dataProvider' => $dataProvider,
                        'filterModel' => $searchModel,
                        'summary' => "Page {begin} - {end} of {totalCount} colors<br/><br/>",
                        'emptyText' => 'No colors',
                        'tableOptions' => [
                            'class'=>'table table-striped table-bordered'
                        ],
                        'columns' => [
                            ['class' => 'yii\grid\SerialColumn'],
                            ['class' => 'yii\grid\CheckboxColumn'],
                            [
                                'attribute'=>'id',
                                'label'=>'<i class="fa fa-sort"></i> ID',
                                'encodeLabel' => false,
                            ],
                            [
                                'attribute'=>'name_ru',
                                'label'=>'<i class="fa fa-sort"></i> Name',
                                'encodeLabel' => false,
                                'value' => function ($model, $key, $index, $column) {
                                    return $model->name_ru ? $model->name_ru : 'No data';
                                },
                            ],
                            [
                                'attribute'=>'color',
                                'label'=>'<i class="fa fa-sort"></i> Color',
                                'encodeLabel' => false,
                                'format'=>'html',
                                'value' => function ($model, $key, $index, $column) {
                                    return $model->color ? '<div class="block-color" style="background-color:'.$model->color.'"></div>' : 'No data';
                                },
                            ],
                            [
                                'attribute'=>'date',
                                'label'=>'<i class="fa fa-sort"></i> Date',
                                'encodeLabel' => false,
                            ],
                            [
                                'class' => 'yii\grid\ActionColumn',
                                'template' => '{view}',
                                'buttons' => [
                                    'view' => function ($url, $model) {

                                        $user = Yii::$app->user->identity;
                                        $isModerator = $user->role === \app\models\user\User::ROLE_MODERATOR;
                                            $menu = '
                                                <div class="btn-group">
                                                    <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown">
                                                        <span class="fa fa-cog"></span>
                                                </button>
                                                <ul class="dropdown-menu pull-right">
                                                    <li>
                                                    <a href="' . Yii::$app->urlManager->createUrl(['/admin/color/view', 'id'=>$model->id]) . '">
                                                        View
                                                    </a>
                                                    </li>';
                                            if (!$isModerator) {
                                                $menu .= '
                                                    <li>
                                                        <a href="' . Yii::$app->urlManager->createUrl(['/admin/color/create', 'id'=>$model->id]) . '">
                                                            Edit
                                                        </a>
                                                    </li>';
                                            }

                                            if (!$isModerator) {
                                                $menu .= '
                                                    <li>
                                                        <a href="' . Yii::$app->urlManager->createUrl(['/admin/color/remove', 'id'=>$model->id]) . '" 
                                                           class="remove-object">
                                                            Delete
                                                        </a>
                                                    </li>';
                                            }

                                            $menu .= '
                                                </ul>
                                            </div>';

                                            return $menu;
                                        // return '<div class="btn-group"><button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown">
                                        //             <span class="fa fa-cog"></span>
                                        //         </button>
                                        //         <ul class="dropdown-menu pull-right">
                                        //             <li><a href="'.Yii::$app->urlManager->createUrl(['/admin/color/view', 'id'=>$model->id]).'">View</a></li>
                                        //             <li><a href="'.Yii::$app->urlManager->createUrl(['/admin/color/create', 'id'=>$model->id]).'">Edit</a></li>
                                        //             <li><a href="'.Yii::$app->urlManager->createUrl(['/admin/color/remove', 'id'=>$model->id]).'" class="remove-object">Delete</a></li>
                                        //         </ul></div>';
                                    }
                                ],
                            ]
                        ],
                    ]); ?>
                <?php Pjax::end(); ?>
            </div>
        </div>
    </section>
</div>  