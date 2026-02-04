<?php
use yii\helpers\Html;
use yii\widgets\LinkPager;

$this->title = 'Notification';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="content-wrapper">
    <section class="content-header">
        <h1><?=$this->title;?></h1>
        
        <ol class="breadcrumb">
            <li><a href="<?=Yii::$app->urlManager->createUrl(['/worker/'])?>"><i class="fa fa-dashboard"></i> Home</a></li>
            <li class="active"><?=$this->title;?></li>
        </ol>
    </section>
    <section class="content">
        <?php if (Yii::$app->session->hasFlash('notification_removed')) {?>
            <div class="alert alert-success text-center">
                <?=Yii::$app->session->getFlash('notification_removed');?>
            </div>
        <?php }?>
        <?php if ($alerts) {?>
            <?php foreach ($alerts as $key => $alert) {?>
                <div class="box box box-info color-palette-box">
                    <div class="box-header">
                        <div class="pull-right">
                            <?php if ($alert->status == 0) {?>
                                <small class="label bg-red">Not viewed</small>
                            <?php }?>
                            <?php if ($alert->status == 1) {?>
                                <small class="label bg-green">Viewed</small>
                            <?php }?>
                        </div>
                        <i class="fa fa-clock-o"></i> <?=$alert->date;?>
                    </div>
                    <div class="box-body">
                        <?=$alert->message;?>
                    </div>
                    <div class="box-footer">
                        <?php if ($alert->type == 'ads_new') {?>
                            <a href="<?=Yii::$app->urlManager->createUrl(['/admin/ads/view', 'id'=>$alert->object_id]);?>" class="btn btn-primary"><i class="fa fa-eye"></i> View</a>
                        <?php }?>
                        <a href="<?=Yii::$app->urlManager->createUrl(['/admin/notification/remove', 'id'=>$alert->id]);?>" class="btn btn-danger"><i class="fa fa-remove"></i> Delete</a>
                    </div>
                </div>
            <?php }?>
            <?php if ($pagination) {?>
                <?=LinkPager::widget(['pagination'=>$pagination]);?>
            <?php }?>
        <?php } else {?>
            <div class="alert alert-warning text-center">No notification</div>
        <?php }?>
    </section>
</div>