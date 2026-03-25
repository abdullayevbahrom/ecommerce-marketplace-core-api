<?php
use app\widgets\admin_shop_menu\AdminShopMenu;
use app\widgets\admin_language_tab\AdminLanguageTab;

$this->title = 'Магазин';
$this->params['breadcrumbs'][] = $this->title;

$type = Yii::$app->request->get('type');
$seller = $model->user;
$sellerProfile = $model->shopSeller;
?>

<div class="content-wrapper">
    <section class="content-header">
        <h1><?=$this->title;?></h1>

        <ol class="breadcrumb">
            <li><a href="<?=Yii::$app->urlManager->createUrl(['/shop/'])?>"><i class="fa fa-dashboard"></i> Главная</a></li>
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
                                <td>Статус</td>
                                <td>
                                    <?php if ($model->status == 1) {?>
                                        <small class="label bg-green">Активный</small>
                                    <?php }?>
                                    <?php if ($model->status == 2) {?>
                                        <small class="label bg-red">Заблокирован</small>
                                    <?php }?>
                                </td>
                            </tr>
                            <tr>
                                <td>Название</td>
                                <td><?=$model->name_ru ? $model->name_ru : '-';?></td>
                            </tr>
                            <tr>
                                <td>Контактное лицо</td>
                                <td><?=$model->contact_user ? $model->contact_user : '-';?></td>
                            </tr>
                            <tr>
                                <td>Контактный номер</td>
                                <td><?=$model->contact_phone ? $model->contact_phone : '-';?></td>
                            </tr>
                            <tr>
                                <td>Дата</td>
                                <td><?=$model->date ? $model->date : '-';?></td>
                            </tr>
                        </table>
                    </div>
                </div>
                <?php if ($model->description_ru || $model->description_uz || $model->description_en) {?>
                    <div class="box box-info color-palette-box">
                        <div class="box-header">Описание</div>
                        <div class="box-body">
                            <div class="lang-block lang-block-ru">
                                <?=$model->description_ru ? $model->description_ru : '<div class="alert alert-warning text-center">Описание не найдено</div>';?>
                            </div>
                            <div class="lang-block lang-block-uz">
                                <?=$model->description_uz ? $model->description_ru : '<div class="alert alert-warning text-center">Описание не найдено</div>';?>
                            </div>
                            <div class="lang-block lang-block-en">
                                <?=$model->description_en ? $model->description_ru : '<div class="alert alert-warning text-center">Описание не найдено</div>';?>
                            </div>
                        </div>
                    </div>
                <?php }?>
                <div class="box box-info color-palette-box">
                    <div class="box-header">Данные продавца</div>
                    <div class="box-body">
                        <table class="table table-striped">
                            <tr>
                                <td>Имя</td>
                                <td><?=$seller && $seller->name ? $seller->name : '-';?></td>
                            </tr>
                            <tr>
                                <td>Телефон</td>
                                <td><?=$seller && $seller->phone ? $seller->phone : '-';?></td>
                            </tr>
                            <tr>
                                <td>E-mail</td>
                                <td><?=$seller && $seller->email ? $seller->email : '-';?></td>
                            </tr>
                            <tr>
                                <td>Логин</td>
                                <td><?=$seller && $seller->login ? $seller->login : '-';?></td>
                            </tr>
                        </table>
                    </div>
                </div>
                <div class="box box-info color-palette-box">
                    <div class="box-header">Реквизиты продавца</div>
                    <div class="box-body">
                        <table class="table table-striped">
                            <tr>
                                <td>ИНН</td>
                                <td><?=$sellerProfile && $sellerProfile->inn ? $sellerProfile->inn : '-';?></td>
                            </tr>
                            <tr>
                                <td>Расчетный счет</td>
                                <td><?=$sellerProfile && $sellerProfile->account ? $sellerProfile->account : '-';?></td>
                            </tr>
                            <tr>
                                <td>Банк</td>
                                <td><?=$sellerProfile && $sellerProfile->bank ? $sellerProfile->bank : '-';?></td>
                            </tr>
                            <tr>
                                <td>Юридический адрес</td>
                                <td><?=$sellerProfile && $sellerProfile->address_legal ? $sellerProfile->address_legal : '-';?></td>
                            </tr>
                            <tr>
                                <td>ОКЕД</td>
                                <td><?=$sellerProfile && $sellerProfile->oked ? $sellerProfile->oked : '-';?></td>
                            </tr>
                            <tr>
                                <td>OKOHX</td>
                                <td><?=$sellerProfile && $sellerProfile->okohx ? $sellerProfile->okohx : '-';?></td>
                            </tr>
                            <tr>
                                <td>MFO</td>
                                <td><?=$sellerProfile && $sellerProfile->mfo ? $sellerProfile->mfo : '-';?></td>
                            </tr>
                        </table>
                    </div>
                </div>
                <?php if ($model->gallery) {?>
                    <div class="box box-info color-palette-box">
                        <div class="box-header">Фото-галерея</div>
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
