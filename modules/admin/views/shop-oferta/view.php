<?php
use app\widgets\admin_shop_oferta_menu\AdminShopOfertaMenu;
use app\widgets\admin_language_tab\AdminLanguageTab;

$this->title = $model ? $model->name_ru : 'Offer';
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
        <?php if (Yii::$app->session->hasFlash('shop_oferta_saved')) {?>
            <div class="callout callout-success text-center">
                <?=Yii::$app->session->getFlash('shop_oferta_saved');?>
            </div>
        <?php }?>
        <?php if (Yii::$app->session->hasFlash('shop_oferta_locked')) {?>
            <div class="callout callout-success text-center">
                <?=Yii::$app->session->getFlash('shop_oferta_locked');?>
            </div>
        <?php }?>
        <div class="row">
            <div class="col-sm-3">
                <?=AdminShopOfertaMenu::widget();?>
            </div>
            <div class="col-sm-9">
                <?=AdminLanguageTab::widget();?>
                <br/>
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
                                        <small class="label bg-green">Active</small>
                                    <?php }?>
                                    <?php if ($model->status == 2) {?>
                                        <small class="label bg-red">Blocked</small>
                                    <?php }?>
                                </td>
                            </tr>
                            <tr>
                                <td>Shops</td>
                                <td>
                                    <?php if ($model->shop) {?>
                                        <a href="<?=Yii::$app->urlManager->createUrl(['/admin/shop/view', 'id'=>$model->shop->id]);?>">
                                            <div class="lang-block lang-block-ru">
                                                <?=$model->shop->name_ru;?>
                                            </div>
                                            <div class="lang-block lang-block-uz">
                                                <?=$model->shop->name_uz;?>
                                            </div>
                                            <div class="lang-block lang-block-en">
                                                <?=$model->shop->name_en;?>
                                            </div>
                                        </a>
                                    <?php } else {?>
                                        <small class="label bg-red">No</small>
                                    <?php }?>
                                </td>
                            </tr>
                            <tr>
                                <td>Name</td>
                                <td>
                                    <div class="lang-block lang-block-ru">
                                        <?=$model->name_ru ? $model->name_ru : '-';?>
                                    </div>
                                    <div class="lang-block lang-block-uz">
                                        <?=$model->name_uz ? $model->name_uz : '-';?>
                                    </div>
                                    <div class="lang-block lang-block-en">
                                        <?=$model->name_en ? $model->name_en : '-';?>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>Date</td>
                                <td><?=$model->date ? $model->date : '-';?></td>
                            </tr>
                            <tr>
                                <td>File</td>
                                <td><?=$model->file ? '<a href="'.$model->getPath().'" download><i class="fa fa-cloud-download"></i> Download</a>' : '-';?></td>
                            </tr>
                        </table>
                    </div>
                </div>
                <div class="box box-info color-palette-box">
                    <div class="box-header">Text</div>
                    <div class="box-body">
                        <div class="lang-block lang-block-ru">
                            <?=$model->content_ru ? $model->content_ru : '<div class="alert alert-warning text-center">Text not found</div>';?>
                        </div>
                        <div class="lang-block lang-block-uz">
                            <?=$model->content_uz ? $model->content_uz : '<div class="alert alert-warning text-center">Text not found</div>';?>
                        </div>
                        <div class="lang-block lang-block-en">
                            <?=$model->content_en ? $model->content_en : '<div class="alert alert-warning text-center">Text not found</div>';?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>