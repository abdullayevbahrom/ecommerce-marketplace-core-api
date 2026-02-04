<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\didox\DidoxDocument */
/* @var $arbitraryModel app\models\didox\DidoxDocumentArbitrary */
/* @var $order app\models\order\Order */
/* @var $users array */
/* @var $currentAdminTin string */

$this->title = 'Обновить произвольный договор: ' . $model->name;
$this->params['breadcrumbs'][] = ['label' => 'Документы DIDOX', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Обновить';
?>

<div class="content-wrapper">
    <section class="content-header">
        <h1><?= Html::encode($this->title) ?></h1>
        <ol class="breadcrumb">
            <li><a href="<?= Yii::$app->urlManager->createUrl(['/admin/']) ?>"><i class="fa fa-dashboard"></i> Главная</a></li>
            <li><a href="<?= Yii::$app->urlManager->createUrl(['/admin/didox']) ?>">Документы DIDOX</a></li>
            <li><a href="<?= Yii::$app->urlManager->createUrl(['/admin/didox/view', 'id' => $model->id]) ?>"><?= Html::encode($model->name) ?></a></li>
            <li class="active">Обновить</li>
        </ol>
    </section>
    <section class="content">
        <!-- Flash Messages -->
        <?php if (Yii::$app->session->hasFlash('error')): ?>
            <div class="callout callout-danger">
                <h4><i class="fa fa-ban"></i> Ошибка!</h4>
                <p><?= Yii::$app->session->getFlash('error') ?></p>
            </div>
        <?php endif; ?>
        
        <?php if (Yii::$app->session->hasFlash('warning')): ?>
            <div class="callout callout-warning">
                <h4><i class="fa fa-warning"></i> Предупреждение!</h4>
                <p><?= Yii::$app->session->getFlash('warning') ?></p>
            </div>
        <?php endif; ?>
        
        <?php if (Yii::$app->session->hasFlash('success')): ?>
            <div class="callout callout-success">
                <h4><i class="fa fa-check"></i> Успешно!</h4>
                <p><?= Yii::$app->session->getFlash('success') ?></p>
            </div>
        <?php endif; ?>
        
        <?php if (Yii::$app->session->hasFlash('info')): ?>
            <div class="callout callout-info">
                <h4><i class="fa fa-info"></i> Информация</h4>
                <p><?= Yii::$app->session->getFlash('info') ?></p>
            </div>
        <?php endif; ?>

        <?php if ($model->isDidoxDocument()): ?>
            <div class="callout callout-warning">
                <h4><i class="fa fa-warning"></i> Редактирование произвольного договора DIDOX</h4>
                <p>Этот произвольный договор подключен к платформе DIDOX. Изменения будут синхронизированы с DIDOX при возможности. Некоторые подписанные документы DIDOX нельзя изменить после создания.</p>
                <small class="text-muted">Тип документа: <?= $model->getDocumentTypeLabel() ?> (DIDOX: <?= $model->didox_doc_type ?: '000' ?>)</small>
            </div>
        <?php else: ?>
            <div class="callout callout-info">
                <h4><i class="fa fa-info-circle"></i> Локальный произвольный договор</h4>
                <p>Этот произвольный договор существует только локально. При заполнении всех обязательных полей DIDOX документ будет создан на платформе при сохранении.</p>
                <small class="text-muted">Тип документа: Произвольный договор (DIDOX тип 000)</small>
            </div>
        <?php endif; ?>

        <?php if ($order): ?>
        <div class="callout callout-success">
            <h4><i class="fa fa-shopping-cart"></i> Связанный заказ</h4>
            <p>Этот договор связан с заказом №<?= $order->id ?> от <?= date('d.m.Y', strtotime($order->date)) ?>. 
            <a href="<?= Yii::$app->urlManager->createUrl(['/admin/order/view', 'id' => $order->id]) ?>" class="btn btn-xs btn-primary">
                <i class="fa fa-eye"></i> Просмотреть заказ
            </a></p>
        </div>
        <?php endif; ?>

        <?= $this->render('_form_arbitrary', [
            'model' => $model,
            'arbitraryModel' => $arbitraryModel,
            'order' => $order,
            'users' => $users,
            'currentAdminTin' => $currentAdminTin,
        ]) ?>
    </section>
</div> 