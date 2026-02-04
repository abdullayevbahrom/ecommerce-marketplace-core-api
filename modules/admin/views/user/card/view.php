<?php
use app\widgets\admin_user_menu\AdminUserMenu;
use app\widgets\admin_user_menu\AdminUserButton;

$this->title = 'Card user';
$this->params['breadcrumbs'][] = $this->title;
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
        <?php if (Yii::$app->session->hasFlash('card_saved')) {?>
            <div class="callout callout-success text-center">
                <?=Yii::$app->session->getFlash('card_saved');?>
            </div>
        <?php }?>
        <?php if (Yii::$app->session->hasFlash('card_locked')) {?>
            <div class="callout callout-success text-center">
                <?=Yii::$app->session->getFlash('card_locked');?>
            </div>
        <?php }?>
        <div class="row">
            <div class="col-sm-3">
                <?=AdminUserMenu::widget();?>
            </div>
            <div class="col-sm-9">
                <div class="box box-info color-palette-box">
                    <div class="box-header">
                        <div class="pull-right">
                            <!-- <a href="<?=Yii::$app->urlManager->createUrl(['/admin/user/card-create', 'id'=>Yii::$app->request->get('id'), 'card_id'=>$model->id]);?>" class="btn btn-info"><i class="fa fa-pencil"></i> Edit</a>
                            <a href="<?=Yii::$app->urlManager->createUrl(['/admin/user/card-lock', 'id'=>Yii::$app->request->get('id'), 'card_id'=>$model->id]);?>" class="btn btn-warning"><?php if ($model->status == 1) {?><i class="fa fa-lock"></i> Block<?php } else {?><i class="fa fa-unlock"></i> Unblock<?php }?></a>
                            <a href="<?=Yii::$app->urlManager->createUrl(['/admin/user/card-remove', 'id'=>Yii::$app->request->get('id'), 'card_id'=>$model->id]);?>" class="btn btn-danger remove-object"><i class="fa fa-remove"></i> Delete</a> -->
                            <?=AdminUserButton::widget();?>
                        </div>
                        Info card
                    </div>
                    <div class="box-body">
                        <table class="table table-striped">
                            <tr>
                                <td>ID</td>
                                <td><?=$model->id ? $model->id : 'No data';?></td>
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
                                <td>Number card</td>
                                <td><?=$model->card_number ? $model->card_number : 'No data';?></td>
                            </tr>
                            <tr>
                                <td>Expire card</td>
                                <td><?=$model->card_expire ? $model->card_expire : 'No data';?></td>
                            </tr>
                            <tr>
                                <td>Number phone</td>
                                <td><?=$model->card_phone_number ? $model->card_phone_number : 'No data';?></td>
                            </tr>
                            <tr>
                                <td>Date</td>
                                <td><?=$model->date ? $model->date : 'No data';?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>