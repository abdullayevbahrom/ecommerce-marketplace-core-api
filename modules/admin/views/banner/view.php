<?php
use app\widgets\admin_banner_menu\AdminBannerMenu;
use app\widgets\admin_language_tab\AdminLanguageTab;

$this->title = 'Banner';
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
        <?php if (Yii::$app->session->hasFlash('banner_saved')) {?>
            <div class="callout callout-success text-center">
                <?=Yii::$app->session->getFlash('banner_saved');?>
            </div>
        <?php }?>
        <?php if (Yii::$app->session->hasFlash('banner_locked')) {?>
            <div class="callout callout-success text-center">
                <?=Yii::$app->session->getFlash('banner_locked');?>
            </div>
        <?php }?>
        <div class="row">
            <div class="col-sm-3">
                <?=AdminBannerMenu::widget();?>
            </div>
            <div class="col-sm-9">
                <?=AdminLanguageTab::widget();?>
                <br/>
                <div class="box box-info color-palette-box">
                    <div class="box-header">
                        Banner
                    </div>
                    <div class="box-body">
                        <img src="<?=$model->getPhoto()?>" width="100%"/>
                    </div>
                </div> 
                <div class="box box-info color-palette-box">
                    <div class="box-header">
                        Info banner
                    </div>
                    <div class="box-body">
                        <table class="table table-striped">
                            <tr>
                                <td>ID</td>
                                <td><?=$model->id ? $model->id : '-';?></td>
                            </tr>
                            <tr>
                                <td>Status</td>
                                <td>
                                    <?php
                                        if ($model->status == 2) {
                                            echo '<small class="label bg-red">Blocked</small>';
                                        }
                                        if ($model->status == 1) {
                                            echo '<small class="label bg-green">Active</small>';
                                        }
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <td>Sort</td>
                                <td><?=$model->sort;?></td>
                            </tr>
                            <tr>
                                <td>Date</td>
                                <td>
                                    <?=$model->date ? $model->date : '-';?>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div> 
                <div class="box box-info color-palette-box">
                    <div class="box-header">
                        Description
                    </div>
                    <div class="box-body">
                        <div class="lang-block lang-block-ru">
                            <?=$model->description_ru ? $model->description_ru : '<div class="alert alert-warning text-center">No data</div>';?>
                        </div>
                        <div class="lang-block lang-block-uz">
                            <?=$model->description_uz ? $model->description_uz : '<div class="alert alert-warning text-center">No data</div>';?>
                        </div>
                        <div class="lang-block lang-block-en">
                            <?=$model->description_en ? $model->description_en : '<div class="alert alert-warning text-center">No data</div>';?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>