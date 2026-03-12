<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $searchModel app\models\didox\DidoxDocumentSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Документы DIDOX';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="content-wrapper">
    <section class="content-header">
        <h1><?= Html::encode($this->title) ?></h1>
        <ol class="breadcrumb">
            <li><a href="<?= Yii::$app->urlManager->createUrl(['/admin/']) ?>"><i class="fa fa-dashboard"></i> Главная</a></li>
            <li class="active"><?= Html::encode($this->title) ?></li>
        </ol>
    </section>
    <section class="content">
        <?php
        $isAuthenticated = Yii::$app->session->has('didox_authenticated') && Yii::$app->session->get('didox_authenticated') === true;
        $sessionTaxId = Yii::$app->session->get('didox_tax_id');
        $sessionConnectionType = Yii::$app->session->get('didox_connection_type');
        $sessionAuthMethod = Yii::$app->session->get('didox_auth_method');
        $sessionCertInfo = Yii::$app->session->get('didox_certificate_info');
        $sessionUserData = Yii::$app->session->get('didox_user_data', []);

        if (!$isAuthenticated):
        ?>
            <div class="callout callout-warning">
                <h4><i class="fa fa-warning"></i> Требуется аутентификация DIDOX</h4>
                <p>Вы не аутентифицированы на платформе DIDOX. Некоторые функции, такие как подписание, синхронизация и создание документов в DIDOX, будут недоступны.</p>
                <p><a href="<?= Url::to(['login']) ?>" class="btn btn-sm btn-primary"><i class="fa fa-sign-in"></i> Войти в DIDOX</a></p>
            </div>
        <?php else: ?>
            <!-- Authenticated user info -->
            <div class="box box-success">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-shield"></i> E-IMZO / DIDOX — Авторизован</h3>
                    <div class="box-tools pull-right">
                        <?= Html::a('<i class="fa fa-sign-out"></i> Выйти', ['eimzo-logout'], [
                            'class' => 'btn btn-sm btn-warning',
                            'data' => [
                                'confirm' => 'Вы уверены, что хотите выйти из DIDOX?',
                                'method' => 'post',
                            ],
                        ]) ?>
                    </div>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-condensed">
                                <tr>
                                    <td width="180"><strong><i class="fa fa-id-card"></i> ИНН (Tax ID):</strong></td>
                                    <td><code style="font-size: 14px;"><?= Html::encode($sessionTaxId) ?></code></td>
                                </tr>
                                <?php if ($sessionCertInfo && is_array($sessionCertInfo)): ?>
                                    <?php if (!empty($sessionCertInfo['displayName'])): ?>
                                    <tr>
                                        <td><strong><i class="fa fa-certificate"></i> Сертификат:</strong></td>
                                        <td><?= Html::encode($sessionCertInfo['displayName']) ?></td>
                                    </tr>
                                    <?php endif; ?>
                                    <?php if (!empty($sessionCertInfo['alias'])): ?>
                                    <tr>
                                        <td><strong><i class="fa fa-key"></i> Alias:</strong></td>
                                        <td><small class="text-muted"><?= Html::encode($sessionCertInfo['alias']) ?></small></td>
                                    </tr>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <tr>
                                    <td><strong><i class="fa fa-plug"></i> Тип подключения:</strong></td>
                                    <td>
                                        <?php if ($sessionConnectionType === 'yur'): ?>
                                            <span class="label label-primary">Юр. лицо</span>
                                        <?php else: ?>
                                            <span class="label label-info">Физ. лицо</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-condensed">
                                <?php if ($sessionAuthMethod): ?>
                                <tr>
                                    <td width="180"><strong><i class="fa fa-lock"></i> Метод входа:</strong></td>
                                    <td>
                                        <?php if ($sessionAuthMethod === 'eimzo'): ?>
                                            <span class="label label-success">E-IMZO</span>
                                        <?php elseif ($sessionAuthMethod === 'password'): ?>
                                            <span class="label label-default">Логин/Пароль</span>
                                        <?php else: ?>
                                            <span class="label label-default"><?= Html::encode($sessionAuthMethod) ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endif; ?>
                                <?php if (!empty($sessionUserData['original_individual_taxid'])): ?>
                                <tr>
                                    <td><strong><i class="fa fa-user"></i> Личный ИНН:</strong></td>
                                    <td><code><?= Html::encode($sessionUserData['original_individual_taxid']) ?></code></td>
                                </tr>
                                <?php endif; ?>
                                <?php
                                $currentUser = Yii::$app->controller->user ?? null;
                                if ($currentUser && $currentUser->eimzo_last_login): ?>
                                <tr>
                                    <td><strong><i class="fa fa-clock-o"></i> Последний вход:</strong></td>
                                    <td><?= Html::encode($currentUser->eimzo_last_login) ?></td>
                                </tr>
                                <?php endif; ?>
                                <tr>
                                    <td><strong><i class="fa fa-user-circle"></i> Пользователь:</strong></td>
                                    <td><?= Html::encode(Yii::$app->user->identity->name ?? '-') ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="box box-info color-palette-box">
            <div class="box-header with-border">
                <div class="box-title pull-right" style="font-size: 14px">
                    <?= Html::a('<i class="fa fa-file-text"></i> Создать счет-фактуру', ['create'], ['class' => 'btn btn-primary']) ?>
                    <?= Html::a('<i class="fa fa-file-contract"></i> Создать произвольный договор', ['create-arbitrary'], ['class' => 'btn btn-success', 'style' => 'margin-left: 5px;']) ?>
                    <?php if ($isAuthenticated): ?>
                        <?= Html::a('<i class="fa fa-sign-out"></i> Выйти из DIDOX', ['eimzo-logout'], [
                            'class' => 'btn btn-warning',
                            'data' => [
                                'confirm' => 'Вы уверены, что хотите выйти из DIDOX?',
                                'method' => 'post',
                            ],
                        ]) ?>
                    <?php endif; ?>
                </div>
                <div id="action-links">
                    <a href="javascript:;" class="btn btn-danger" data-value="remove"><i class="fa fa-trash"></i> Delete</a>
                </div>
            </div>
            <div class="box-body">
                <?php Pjax::begin(); ?>
                
                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'tableOptions' => ['class' => 'table table-striped table-bordered'],
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],

                        [
                            'attribute' => 'name',
                            'format' => 'raw',
                            'value' => function($model) {
                                $name = Html::encode($model->name);
                                if ($model->isDidoxDocument()) {
                                    $name .= ' <small class="label label-info">DIDOX</small>';
                                } elseif ($model->hasDidoxErrors()) {
                                    $name .= ' <small class="label label-danger">ERROR</small>';
                                }
                                return $name;
                            }
                        ],
                        
                        [
                            'attribute' => 'document_type',
                            'label' => 'Type',
                            'value' => function($model) {
                                return $model->getDocumentTypeLabel();
                            },
                            'filter' => [
                                'invoice' => 'Счет-фактура',
                            ]
                        ],

                        [
                            'attribute' => 'didox_status',
                            'label' => 'DIDOX Status',
                            'format' => 'raw',
                            'value' => function($model) {
                                if (!$model->isDidoxDocument()) {
                                    return '<span class="label label-default">Только локально</span>';
                                }
                                $label = $model->getDidoxStatusLabel();
                                $color = str_replace('bg-', 'label-', $model->getDidoxStatusColor());
                                return '<span class="label ' . $color . '">' . $label . '</span>';
                            },
                            'filter' =>                                 [
                                0 => 'Черновик',
                                1 => 'Ожидает партнера',
                                2 => 'Ожидает вашей подписи', 
                                3 => 'Подписан',
                                4 => 'Отклонен',
                                120 => 'Отменен'
                            ]
                        ],

                        [
                            'attribute' => 'buyer_tin',
                            'label' => 'Buyer TIN',
                            'value' => function($model) {
                                return $model->invoice ? $model->invoice->buyer_tin : '';
                            }
                        ],

                        [
                            'attribute' => 'seller_tin', 
                            'label' => 'Seller TIN',
                            'value' => function($model) {
                                return $model->invoice ? $model->invoice->seller_tin : '';
                            }
                        ],

                        [
                            'attribute' => 'total_sum',
                            'format' => 'currency',
                            'label' => 'Total Sum',
                            'value' => function($model) {
                                return $model->invoice ? $model->invoice->total_sum : 0;
                            }
                        ],

                        [
                            'attribute' => 'to_user_id',
                            'label' => 'Assigned To',
                            'value' => function($model) {
                                return $model->toUser ? $model->toUser->name . ' (' . $model->toUser->eimzo_tax_id . ')' : '';
                            },
                            'filter' => \yii\helpers\ArrayHelper::map(
                                \app\models\user\User::find()
                                    ->where(['!=', 'eimzo_tax_id', ''])
                                    ->andWhere(['is not', 'eimzo_tax_id', null])
                                    ->all(),
                                'id',
                                function($model) {
                                    return $model->name . ' (' . $model->eimzo_tax_id . ')';
                                }
                            )
                        ],

                        [
                            'attribute' => 'created_at',
                            'format' => 'datetime',
                            'label' => 'Created'
                        ],

                        [
                            'class' => 'yii\grid\ActionColumn',
                            'template' => '{view} {update} {sign} {send} {sync} {retry} {delete}',
                            'buttons' => [
                                'sign' => function ($url, $model, $key) {
                                    if ($model->isDidoxDocument() && $model->canBeSignedInDidox()) {
                                        return Html::a('<i class="fa fa-pencil-square-o"></i>', 
                                            Url::to(['sign', 'id' => $model->id]), 
                                            [
                                                'title' => 'Sign Document',
                                                'class' => 'btn btn-xs btn-warning',
                                                'data-method' => 'post',
                                                'data-confirm' => 'Are you sure you want to sign this document?'
                                            ]
                                        );
                                    }
                                    return '';
                                },
                                'send' => function ($url, $model, $key) {
                                    if ($model->canBeSentToPartner()) {
                                        return Html::a('<i class="fa fa-send"></i>', 
                                            Url::to(['send-to-partner', 'id' => $model->id]), 
                                            [
                                                'title' => 'Send to Partner',
                                                'class' => 'btn btn-xs btn-info',
                                                'data-method' => 'post',
                                                'data-confirm' => 'Are you sure you want to send this document to the partner?'
                                            ]
                                        );
                                    }
                                    return '';
                                },
                                'sync' => function ($url, $model, $key) {
                                    if ($model->isDidoxDocument()) {
                                        return Html::a('<i class="fa fa-refresh"></i>', 
                                            Url::to(['sync', 'id' => $model->id]), 
                                            [
                                                'title' => 'Sync Status',
                                                'class' => 'btn btn-xs btn-info'
                                            ]
                                        );
                                    }
                                    return '';
                                },
                                'retry' => function ($url, $model, $key) {
                                    if ($model->canRetryDidox()) {
                                        return Html::a('<i class="fa fa-repeat"></i>', 
                                            Url::to(['retry-didox', 'id' => $model->id]), 
                                            [
                                                'title' => 'Retry DIDOX Submission',
                                                'class' => 'btn btn-xs btn-warning',
                                                'data-method' => 'post',
                                                'data-confirm' => 'Are you sure you want to retry submitting this document to DIDOX?'
                                            ]
                                        );
                                    }
                                    return '';
                                },
                                'view' => function ($url, $model, $key) {
                                    return Html::a('<i class="fa fa-eye"></i>', $url, [
                                        'title' => 'View',
                                        'class' => 'btn btn-xs btn-primary'
                                    ]);
                                },
                                'update' => function ($url, $model, $key) {
                                    return Html::a('<i class="fa fa-pencil"></i>', $url, [
                                        'title' => 'Update',
                                        'class' => 'btn btn-xs btn-default'
                                    ]);
                                },
                                'delete' => function ($url, $model, $key) {
                                    return Html::a('<i class="fa fa-trash"></i>', $url, [
                                        'title' => 'Delete',
                                        'class' => 'btn btn-xs btn-danger',
                                        'data' => [
                                            'confirm' => 'Are you sure you want to delete this item?',
                                            'method' => 'post',
                                        ],
                                    ]);
                                },
                            ],
                        ],
                    ],
                ]); ?>
                
                <?php Pjax::end(); ?>
            </div>
        </div>
    </section>
</div>

<style>
.grid-view table {
    font-size: 12px;
}
.grid-view .action-column {
    width: 120px;
}
.grid-view .action-column .btn {
    margin-right: 2px;
    margin-bottom: 2px;
}
</style> 