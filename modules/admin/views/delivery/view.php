<?php
use app\widgets\admin_delivery_menu\AdminDeliveryMenu;
use app\widgets\admin_language_tab\AdminLanguageTab;

$this->title = $model ? $model->name_ru : 'Delivery';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="content-wrapper">
    <section class="content-header">
        <h1><?=mb_substr($this->title, 0, 50, 'utf-8');?></h1>
        
        <ol class="breadcrumb">
            <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/'])?>"><i class="fa fa-dashboard"></i> Home</a></li>
            <li class="active"><?=mb_substr($this->title, 0, 50, 'utf-8');?></li>
        </ol>
    </section>
    <section class="content">
        <?php if (Yii::$app->session->hasFlash('delivery_saved')) {?>
            <div class="callout callout-success text-center">
                <?=Yii::$app->session->getFlash('delivery_saved');?>
            </div>
        <?php }?>
        <?php if (Yii::$app->session->hasFlash('delivery_locked')) {?>
            <div class="callout callout-success text-center">
                <?=Yii::$app->session->getFlash('delivery_locked');?>
            </div>
        <?php }?>
        <div class="row">
            <div class="col-sm-3">
                <?=AdminDeliveryMenu::widget();?>
            </div>
            <div class="col-sm-9">
                <?=AdminLanguageTab::widget();?>
                <br/>
                <div class="box box-info color-palette-box">
                    <div class="box-header">
                        Delivery method details
                    </div>
                    <div class="box-body">
                        <table class="table table-striped">
                            <tr>
                                <td>ID:</td>
                                <td><?=$model->id ? $model->id : 'No data';?></td>
                            </tr>
                            <tr>
                                <td>Status:</td>
                                <td>
                                    <?php if ($model->status == 1) {?>
                                        <small class="label bg-green">Active</small>
                                    <?php }?>
                                    <?php if ($model->status == 2) {?>
                                        <small class="label bg-red">Blocked</small>
                                    <?php }?>
                                </td>
                            </tr>
                            <tr>
                                <td>Name:</td>
                                <td>
                                    <div class="lang-block lang-block-ru">
                                        <?=$model->name_ru ? $model->name_ru : '';?>
                                    </div>
                                    <div class="lang-block lang-block-uz">
                                        <?=$model->name_uz ? $model->name_uz : '';?>
                                    </div>
                                    <div class="lang-block lang-block-en">
                                        <?=$model->name_en ? $model->name_en : '';?>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>Price:</td>
                                <td><?=$model->price ? number_format($model->price) : 'No data';?></td>
                            </tr>
                            <tr>
                                <td>Description:</td>
                                <td>
                                    <div class="lang-block lang-block-ru">
                                        <?=$model->name_ru ? $model->name_ru : '';?>
                                    </div>
                                    <div class="lang-block lang-block-uz">
                                        <?=$model->name_uz ? $model->name_uz : '';?>
                                    </div>
                                    <div class="lang-block lang-block-en">
                                        <?=$model->name_en ? $model->name_en : '';?>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>Date:</td>
                                <td><?=$model->date ? $model->date : 'No data';?></td>
                            </tr>
                        </table>
                    </div>
                </div> 
            </div>
        </div>
    </section>
</div>