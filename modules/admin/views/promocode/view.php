<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use app\models\Promocode;

/* @var $this yii\web\View */
/* @var $model app\models\Promocode */

$this->title = $model->code;
$this->params['breadcrumbs'][] = ['label' => 'Промокоды', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="content-wrapper">
    <section class="content-header">
        <h1><?= Html::encode($this->title) ?></h1>
    </section>

    <section class="content">
        <div class="row">
            <div class="col-md-6">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title">Основная информация</h3>
                        <div class="box-tools pull-right">
                            <?= Html::a('<i class="fa fa-pencil"></i>', ['update', 'id' => $model->id], ['class' => 'btn btn-box-tool', 'title' => 'Редактировать']) ?>
                        </div>
                    </div>
                    <div class="box-body">
                        <?= DetailView::widget([
                            'model' => $model,
                            'attributes' => [
                                'id',
                                [
                                    'attribute' => 'code',
                                    'format' => 'raw',
                                    'value' => '<code>' . Html::encode($model->code) . '</code>',
                                ],
                                [
                                    'attribute' => 'type',
                                    'value' => $model->type == Promocode::TYPE_FIXED ? 'Фиксированная сумма' : 'Процент',
                                ],
                                [
                                    'attribute' => 'value',
                                    'value' => $model->type == Promocode::TYPE_FIXED 
                                        ? number_format($model->value, 0, '.', ' ') . ' сум' 
                                        : $model->value . '%',
                                ],
                                [
                                    'attribute' => 'status',
                                    'format' => 'raw',
                                    'value' => $model->status == 1 
                                        ? '<span class="label label-success">Активен</span>' 
                                        : '<span class="label label-danger">Неактивен</span>',
                                ],
                                [
                                    'attribute' => 'user_id',
                                    'format' => 'raw',
                                    'value' => function($model) {
                                        if (!$model->user_id) return '<span class="text-muted">Доступен всем</span>';
                                        $user = \app\models\user\User::findOne($model->user_id);
                                        return $user 
                                            ? Html::a('ID: ' . $model->user_id . ' (' . Html::encode($user->name . ' ' . $user->lastname) . ')', ['/admin/user/view', 'id' => $user->id]) 
                                            : 'User #' . $model->user_id;
                                    }
                                ],
                                'created_at:datetime',
                                'updated_at:datetime',
                            ],
                        ]) ?>
                    </div>
                </div>
                
                <div class="box box-warning">
                    <div class="box-header with-border">
                        <h3 class="box-title">Ограничения</h3>
                    </div>
                    <div class="box-body">
                        <?= DetailView::widget([
                            'model' => $model,
                            'attributes' => [
                                [
                                    'attribute' => 'min_order_amount',
                                    'value' => $model->min_order_amount ? number_format($model->min_order_amount, 0, '.', ' ') . ' сум' : 'Нет',
                                ],
                                [
                                    'attribute' => 'max_discount_amount',
                                    'visible' => $model->type == Promocode::TYPE_PERCENT,
                                    'value' => $model->max_discount_amount ? number_format($model->max_discount_amount, 0, '.', ' ') . ' сум' : 'Нет',
                                ],
                                [
                                    'attribute' => 'usage_limit',
                                    'value' => $model->usage_limit ?: 'Безлимитно',
                                ],
                                [
                                    'attribute' => 'usage_limit_per_user',
                                    'value' => $model->usage_limit_per_user ?: 'Безлимитно',
                                ],
                                [
                                    'attribute' => 'is_first_order',
                                    'format' => 'boolean',
                                    'label' => 'Только на первый заказ',
                                ],
                                'start_date:datetime',
                                'end_date:datetime',
                            ],
                        ]) ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="box box-info">
                    <div class="box-header with-border">
                        <h3 class="box-title">Описание (RU)</h3>
                    </div>
                    <div class="box-body">
                        <strong>Заголовок:</strong><br>
                        <?= Html::encode($model->title_ru ?: '-') ?><br><br>
                        <strong>Описание:</strong><br>
                        <?= nl2br(Html::encode($model->description_ru ?: '-')) ?>
                    </div>
                </div>
                
                <div class="box box-info">
                    <div class="box-header with-border">
                        <h3 class="box-title">Описание (UZ)</h3>
                    </div>
                    <div class="box-body">
                        <strong>Заголовок:</strong><br>
                        <?= Html::encode($model->title_uz ?: '-') ?><br><br>
                        <strong>Описание:</strong><br>
                        <?= nl2br(Html::encode($model->description_uz ?: '-')) ?>
                    </div>
                </div>
                
                <div class="box box-info">
                    <div class="box-header with-border">
                        <h3 class="box-title">Описание (EN)</h3>
                    </div>
                    <div class="box-body">
                        <strong>Заголовок:</strong><br>
                        <?= Html::encode($model->title_en ?: '-') ?><br><br>
                        <strong>Описание:</strong><br>
                        <?= nl2br(Html::encode($model->description_en ?: '-')) ?>
                    </div>
                </div>
                
                <div class="box box-danger">
                    <div class="box-header with-border">
                        <h3 class="box-title">Действия</h3>
                    </div>
                    <div class="box-body">
                        <?= Html::a('Обновить', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
                        <?= Html::a('Удалить', ['delete', 'id' => $model->id], [
                            'class' => 'btn btn-danger pull-right',
                            'data' => [
                                'confirm' => 'Вы уверены, что хотите удалить этот промокод?',
                                'method' => 'post',
                            ],
                        ]) ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
