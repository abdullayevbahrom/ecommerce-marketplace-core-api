<?php
use yii\helpers\Html;
use yii\grid\GridView;
use app\models\user\User;

$this->title = 'Все пользователи';
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
        <?php if (Yii::$app->session->hasFlash('user_deleted')) {?>
            <div class="callout callout-success text-center">
                <?=Yii::$app->session->getFlash('user_deleted');?>
            </div>
        <?php }?>
        <div class="box box-info color-palette-box">
            <div class="box-header with-border">
                <div class="box-title pull-right" style="font-size: 14px">
                    <a href="<?=Yii::$app->urlManager->createUrl(['/admin/user/create'])?>" class="btn btn-primary">
                        <i class="fa fa-plus"></i> Добавить клиента
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
                    'summary' => "Показано {begin} - {end} из {totalCount} пользователей<br/><br/>",
                    'emptyText' => 'Пользователи не найдены',
                    'rowOptions' => function ($model, $index, $widget, $grid) {
                        return [
                            'id' => $model['id'],
                            'url' => Yii::$app->urlManager->createUrl('/admin/user/view').'?id='.$model['id']
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
                            'value' => function($data) { return Html::img($data->getPhoto('50x50'), ['width'=>'30']); },
                        ],
                        [
                            'attribute'=>'id',
                            'label'=>'<i class="fa fa-sort"></i> ID',
                            'encodeLabel' => false,
                        ],
                        [
                            'attribute'=>'role',
                            'label'=>'<i class="fa fa-sort"></i> Роль',
                            'encodeLabel' => false,
                            'format' => 'html',
                            'filter' => Html::activeDropDownList($searchModel, 'role', [
                                User::ROLE_ADMIN => 'Администратор',
                                User::ROLE_MODERATOR => 'Модератор',
                                User::ROLE_USER => 'Клиент',
                                User::ROLE_SHOP => 'Магазин',
                                User::ROLE_LOGIST => 'Логист',
                                User::ROLE_OPERATOR => 'Оператор',
                            ], ['class'=>'form-control select2', 'prompt' => 'Все']),
                            'value' => function ($model) {
                                return $model->getRoleBadge();
                            },
                        ],
                        [
                            'attribute'=>'type',
                            'label'=>'<i class="fa fa-sort"></i> Тип',
                            'encodeLabel' => false,
                            'format' => 'html',
                            'filter' => Html::activeDropDownList($searchModel, 'type', ['fiz'=>'Физ.', 'yur'=>'Юр.'], ['class'=>'form-control select2','prompt' => 'Все']),
                            'value' => function ($model) {
                                if ($model->type == 'fiz') {
                                    return '<small class="label bg-aqua">Физ. лицо</small>';
                                }
                                if ($model->type == 'yur') {
                                    return '<small class="label bg-black">Юр. лицо</small>';
                                }
                            },
                        ],
                        [
                            'attribute'=>'name',
                            'label'=>'<i class="fa fa-sort"></i> Имя',
                            'encodeLabel' => false,
                            'value' => function ($model) {
                                $parts = array_filter([$model->lastname, $model->name, $model->middlename]);
                                return $parts ? implode(' ', $parts) : 'Нет данных';
                            },
                        ],
                        [
                            'attribute'=>'phone',
                            'label'=>'<i class="fa fa-sort"></i> Телефон',
                            'encodeLabel' => false,
                        ],
                        [
                            'attribute'=>'email',
                            'label'=>'<i class="fa fa-sort"></i> E-mail',
                            'encodeLabel' => false,
                            'value' => function ($model) {
                                return $model->email ?: 'Нет данных';
                            },
                        ],
                        [
                            'attribute'=>'login',
                            'label'=>'<i class="fa fa-sort"></i> Логин',
                            'encodeLabel' => false,
                            'value' => function ($model) {
                                return $model->login ?: '-';
                            },
                        ],
                        [
                            'attribute'=>'status',
                            'label'=>'<i class="fa fa-sort"></i> Статус',
                            'encodeLabel' => false,
                            'format' => 'html',
                            'filter' => Html::activeDropDownList($searchModel, 'status', ['0'=>'Ожидает', '1'=>'Активен', '2'=>'Заблокирован'], ['class'=>'form-control select2','prompt' => 'Все']),
                            'value' => function ($model) {
                                if ($model->status == 0) {
                                    return '<small class="label bg-yellow">Ожидает</small>';
                                }
                                if ($model->status == 1) {
                                    return '<small class="label bg-green">Активен</small>';
                                }
                                if ($model->status == 2) {
                                    return '<small class="label bg-red">Заблокирован</small>';
                                }
                            },
                        ],
                        [
                            'attribute'=>'date',
                            'label'=>'<i class="fa fa-sort"></i> Дата',
                            'encodeLabel' => false,
                            'value' => function ($model) {
                                return $model->date ?: 'Нет данных';
                            },
                        ],
                        [
                            'class' => 'yii\grid\ActionColumn',
                            'template' => '{update} {delete}',
                            'buttons' => [
                                'update' => function ($url, $model) {
                                    return Html::a('<span class="glyphicon glyphicon-pencil"></span>', Yii::$app->urlManager->createUrl(['/admin/user/create', 'id'=>$model->id]), ['class'=>'btn btn-warning btn-xs']);
                                },
                                'delete' => function ($url, $model) {
                                    return Html::a('<span class="glyphicon glyphicon-trash"></span>', Yii::$app->urlManager->createUrl(['/admin/user/remove', 'id'=>$model->id]), ['class'=>'btn btn-danger btn-xs remove-object']);
                                }
                            ],
                        ]
                    ],
                ]); ?>
            </div>
        </div>
    </section>
</div>
