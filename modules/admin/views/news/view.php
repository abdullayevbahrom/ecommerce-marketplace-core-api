<?php
use app\widgets\admin_news_menu\AdminNewsMenu;
use app\widgets\admin_language_tab\AdminLanguageTab;

$this->title = $model ? $model->name_ru : 'News';
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
        <?php if (Yii::$app->session->hasFlash('news_saved')) {?>
            <div class="callout callout-success text-center">
                <?=Yii::$app->session->getFlash('news_saved');?>
            </div>
        <?php }?>
        <?php if (Yii::$app->session->hasFlash('news_locked')) {?>
            <div class="callout callout-success text-center">
                <?=Yii::$app->session->getFlash('news_locked');?>
            </div>
        <?php }?>
        <div class="row">
            <div class="col-sm-3">
                <?=AdminNewsMenu::widget();?>
            </div>
            <div class="col-sm-9">
                <?=AdminLanguageTab::widget();?>
                <br/>
                <div class="box box-info color-palette-box">
                    <div class="box-header">
                        Main info
                    </div>
                    <div class="box-body">
                        <table class="table table-striped">
                            <tr>
                                <td>ID</td>
                                <td><?=$model->id ? $model->id : '-';?></td>
                            </tr>
                            <tr>
                                <td>Shops</td>
                                <td><?=$model->shop ? '<a href="'.Yii::$app->urlManager->createUrl(['/admin/shop/view', 'id'=>$model->shop->id]).'">'.$model->shop->name_ru.'</a>' : '-';?></td>
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
                                <td>Date</td>
                                <td><?=$model->date ? $model->date : '-';?></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div class="box box-info color-palette-box">
                    <div class="box-header">
                        Name / Short description
                    </div>
                    <div class="box-body">
                        <div class="lang-block lang-block-ru">
                            <h3><?=$model->name_ru ? $model->name_ru : '';?></h3>
                            <?=$model->description_mini_ru ? $model->description_mini_ru : '';?>
                        </div>
                        <div class="lang-block lang-block-uz">
                            <h3><?=$model->name_uz ? $model->name_uz : '';?></h3>
                            <?=$model->description_mini_uz ? $model->description_mini_uz : '';?>
                        </div>
                        <div class="lang-block lang-block-en">
                            <h3><?=$model->name_en ? $model->name_en : '';?></h3>
                            <?=$model->description_mini_en ? $model->description_mini_en : '';?>
                        </div>
                    </div>
                </div> 
                <div class="box box-info color-palette-box">
                    <div class="box-header">
                        Text news
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