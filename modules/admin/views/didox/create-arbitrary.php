<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\didox\DidoxDocument */
/* @var $arbitraryModel app\models\didox\DidoxDocumentArbitrary */
/* @var $order app\models\order\Order */
/* @var $users array */
/* @var $currentAdminTin string */

$this->title = $order ? 'Создать договор для заказа №' . $order->id : 'Создать произвольный договор';
$this->params['breadcrumbs'][] = ['label' => 'Документы DIDOX', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="content-wrapper">
    <section class="content-header">
        <h1><?= Html::encode($this->title) ?></h1>
        <ol class="breadcrumb">
            <li><a href="<?= Yii::$app->urlManager->createUrl(['/admin/']) ?>"><i class="fa fa-dashboard"></i> Главная</a></li>
            <li><a href="<?= Yii::$app->urlManager->createUrl(['/admin/didox']) ?>">Документы DIDOX</a></li>
            <li class="active"><?= Html::encode($this->title) ?></li>
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

        <?php if ($order): ?>
        <div class="callout callout-info">
            <h4><i class="fa fa-info-circle"></i> Создание договора для заказа</h4>
            <p>Данные договора будут автоматически заполнены на основе заказа №<?= $order->id ?>. Договор будет создан в формате PDF и отправлен в DIDOX как произвольный документ.</p>
        </div>
        <?php else: ?>
        <div class="callout callout-info">
            <h4><i class="fa fa-info-circle"></i> Создание произвольного договора</h4>
            <p>Создание произвольного договора автоматически создаст PDF документ и отправит его в DIDOX как документ типа "Произвольный документ". Убедитесь, что вы аутентифицированы с E-IMZO.</p>
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