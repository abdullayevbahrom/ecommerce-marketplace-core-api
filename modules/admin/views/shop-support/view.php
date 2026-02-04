<?php
use app\widgets\admin_language_tab\AdminLanguageTab;

$this->title = 'Message';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="content-wrapper">
    <section class="content-header">
        <h1>
            <?=mb_substr($this->title, 0, 30, 'utf-8');?>
            <?=(mb_strlen($this->title) >= 30) ? '...' : '';?>        
        </h1>
        
        <ol class="breadcrumb">
            <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/'])?>"><i class="fa fa-dashboard"></i> Home</a></li>
            <li class="active"><?=$this->title;?></li>
        </ol>
    </section>
    <section class="content">
        <?php if (Yii::$app->session->hasFlash('shop_support_saved')) {?>
            <div class="callout callout-success text-center">
                <?=Yii::$app->session->getFlash('shop_support_saved');?>
            </div>
        <?php }?>
        <div class="box box-info color-palette-box">
            <div class="box-body">
                <table class="table table-striped">
                    <tr>
                        <td>ID</td>
                        <td><?=$model->id;?></td>
                    </tr>
                    <tr>
                        <td>Status</td>
                        <td>
                            <?php if ($model->status == 1) {?>
                                <small class="label bg-green">Viewed</small>
                            <?php }?>
                            <?php if ($model->status == 0) {?>
                                <small class="label bg-yellow">Pending</small>
                            <?php }?>
                        </td>
                    </tr>
                    <tr>
                        <td>Theme</td>
                        <td>
                            <?=$model->theme ? $model->theme : '-';?>
                        </td>
                    </tr>
                    <tr>
                        <td>Date</td>
                        <td><?=$model->date ? $model->date : '-';?></td>
                    </tr>
                </table>
            </div>
        </div>
        <div class="box box-info color-palette-box">
            <div class="box-header">Message</div>
            <div class="box-body">
                <?=$model->message ? $model->message : '<div class="alert alert-warning text-center">Message not found</div>';?>
            </div>
        </div>
    </section>
</div>