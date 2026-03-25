<?php
use app\widgets\admin_shop_menu\AdminShopMenu;
use app\widgets\admin_language_tab\AdminLanguageTab;

$this->title = 'Shops';
$this->params['breadcrumbs'][] = $this->title;

$type = Yii::$app->request->get('type');
?>

<div class="content-wrapper">
    <section class="content-header">
        <h1><?=$this->title;?></h1>

        <ol class="breadcrumb">
            <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/'])?>"><i class="fa fa-dashboard"></i> Home</a></li>
            <li class="active"><?=$this->title;?></li>
        </ol>
    </section>
    <section class="content">
        <?php if (Yii::$app->session->hasFlash('shop_saved')) {?>
            <div class="callout callout-success text-center">
                <?=Yii::$app->session->getFlash('shop_saved');?>
            </div>
        <?php }?>
        <?php if (Yii::$app->session->hasFlash('shop_locked')) {?>
            <div class="callout callout-success text-center">
                <?=Yii::$app->session->getFlash('shop_locked');?>
            </div>
        <?php }?>
        <div class="row">
            <div class="col-sm-3">
                <?=AdminShopMenu::widget();?>
            </div>
            <div class="col-sm-9">
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
                                <td>Name</td>
                                <td><?=$model->name_ru ? $model->name_ru : '-';?></td>
                            </tr>
                            <tr>
                                <td>Contact person</td>
                                <td><?=$model->contact_user ? $model->contact_user : '-';?></td>
                            </tr>
                            <tr>
                                <td>Contact Phone</td>
                                <td><?=$model->contact_phone ? $model->contact_phone : '-';?></td>
                            </tr>
                            <tr>
                                <td>Date</td>
                                <td><?=$model->date ? $model->date : '-';?></td>
                            </tr>
                            <?php if ($model->user) {?>
                            <tr>
                                <td>Пользователь</td>
                                <td>
                                    <a href="<?=Yii::$app->urlManager->createUrl(['/admin/user/view', 'id' => $model->user->id])?>" class="btn btn-sm btn-primary">
                                        <i class="fa fa-user"></i>
                                        <?=\yii\helpers\Html::encode($model->user->name ?: $model->user->phone ?: "User #{$model->user->id}")?>
                                        (ID: <?=$model->user->id?>)
                                    </a>
                                </td>
                            </tr>
                            <?php }?>
                        </table>
                    </div>
                </div>
                <?php if ($model->banner) {?>
                    <div class="box box-info color-palette-box">
                        <div class="box-header">Банер</div>
                        <div class="box-body">
                            <img src="<?=$model->getPhotoBanner();?>" width="300"/>
                        </div>
                    </div>
                <?php }?>
                <?php if ($model->description_ru || $model->description_uz || $model->description_en) {?>
                    <div class="box box-info color-palette-box">
                        <div class="box-header">Description</div>
                        <div class="box-body">
                            <div class="lang-block lang-block-ru">
                                <?=$model->description_ru ? $model->description_ru : '<div class="alert alert-warning text-center">Description not foundо</div>';?>
                            </div>
                            <div class="lang-block lang-block-uz">
                                <?=$model->description_uz ? $model->description_ru : '<div class="alert alert-warning text-center">Description not foundо</div>';?>
                            </div>
                            <div class="lang-block lang-block-en">
                                <?=$model->description_en ? $model->description_ru : '<div class="alert alert-warning text-center">Description not foundо</div>';?>
                            </div>
                        </div>
                    </div>
                <?php }?>
                <div class="box box-info color-palette-box">
                    <div class="box-header">Info продавца</div>
                    <div class="box-body">
                        <table class="table table-striped">
                            <tr>
                                <td>Name</td>
                                <td>
                                    <?php if ($model->user) {?>
                                        <a href="<?=Yii::$app->urlManager->createUrl(['/admin/user/view', 'id' => $model->user->id])?>" class="text-primary">
                                            <i class="fa fa-external-link"></i>
                                            <?=$model->user->name ? $model->user->name : '-';?>
                                        </a>
                                    <?php } else { echo '-'; } ?>
                                </td>
                            </tr>
                            <tr>
                                <td>Phone</td>
                                <td><?=$model->user->phone ? $model->user->phone : '-';?></td>
                            </tr>
                            <tr>
                                <td>E-mail</td>
                                <td><?=$model->user->email ? $model->user->email : '-';?></td>
                            </tr>
                            <tr>
                                <td>Login</td>
                                <td><?=$model->user->login ? $model->user->login : '-';?></td>
                            </tr>
                            <tr>
                                <td>Токен</td>
                                <td><?=$model->user->token ? $model->user->token : '-';?></td>
                            </tr>
                        </table>
                    </div>
                </div>
                <?php if ($model->shopSeller) {?>
                    <div class="box box-info color-palette-box">
                        <div class="box-header">Реквизиты продавца</div>
                        <div class="box-body">
                            <table class="table table-striped">
                                <tr>
                                    <td>TIN</td>
                                    <td><?=$model->shopSeller->inn ? $model->shopSeller->inn : '-';?></td>
                                </tr>
                                <tr>
                                    <td>Checking account</td>
                                    <td><?=$model->shopSeller->account ? $model->shopSeller->account : '-';?></td>
                                </tr>
                                <tr>
                                    <td>Bank</td>
                                    <td><?=$model->shopSeller->bank ? $model->shopSeller->bank : '-';?></td>
                                </tr>
                                <tr>
                                    <td>Legal addres</td>
                                    <td><?=$model->shopSeller->address_legal ? $model->shopSeller->address_legal : '-';?></td>
                                </tr>
                                <tr>
                                    <td>OKED</td>
                                    <td><?=$model->shopSeller->oked ? $model->shopSeller->oked : '-';?></td>
                                </tr>
                                <tr>
                                    <td>OKOHX</td>
                                    <td><?=$model->shopSeller->okohx ? $model->shopSeller->okohx : '-';?></td>
                                </tr>
                                <tr>
                                    <td>MFO</td>
                                    <td><?=$model->shopSeller->mfo ? $model->shopSeller->mfo : '-';?></td>
                                </tr>
                                <tr>
                                    <td>Name organization</td>
                                    <td><?=$model->shopSeller->organization ? $model->shopSeller->organization : '-';?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                <?php }?>
                <?php if ($model->gallery) {?>
                    <div class="box box-info color-palette-box">
                        <div class="box-header">Photo-Galery</div>
                        <div class="box-body">
                            <div class="row mix-grid">
                                <?php foreach ($model->gallery as $photo) {?>
                                    <div class="col-md-4">
                                        <img src="<?=$photo->getPhoto('shop', '250x250');?>" alt="" class="img-responsive"/>
                                    </div>
                                <?php }?>
                            </div>
                        </div>
                    </div>
                <?php }?>
            </div>
        </div>
    </section>
</div>
