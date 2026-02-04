<?php

use app\widgets\admin_moderator_menu\AdminModeratorMenu;

$this->title = 'Profile moderator';
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
        <?php if (Yii::$app->session->hasFlash('moderator_saved')) {?>
            <div class="alert alert-success text-center">
                <?=Yii::$app->session->getFlash('moderator_saved');?>
            </div>
        <?php }?>
        <?php if ($model) {?>
            <div class="row">
                <div class="col-sm-3">
                    <?=AdminModeratorMenu::widget();?>
                </div>
                <div class="col-sm-9">
                    <div class="box box-info color-palette-box">
                        <div class="box-header">
                            Info moderator
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
                                    <td>Name</td>
                                    <td><?=$model->name ? $model->name : 'No data';?></td>
                                </tr>
                                <tr>
                                    <td>phone</td>
                                    <td><?=$model->phone ? $model->phone : 'No data';?></td>
                                </tr>
                                <tr>
                                    <td>Login</td>
                                    <td><?=$model->login ? $model->login : 'No data';?></td>
                                </tr>
                                <tr>
                                    <td>Date register</td>
                                    <td><?=$model->date ? $model->date : 'No data';?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    <div class="box box-info color-palette-box">
                        <div class="box-header">
                            Access
                        </div>
                        <div class="box-body">
                            <?php if ($urls) {?>
                                <ul>
                                    <?php foreach ($urls as $k => $v) {?>
                                        <?php if ($v && $v->moderator) {?>
                                            <li><?=$v->moderator->name;?></li>
                                        <?php }?>
                                    <?php }?>
                                </ul>
                            <?php } else {?>
                                <div class="alert alert-warning text-center">No data</div>
                            <?php }?>
                        </div>
                    </div>
                </div>
            </div>
        <?php } else {?>
            <div class="box">
                <div class="box-body">
                    <div class="alert alert-warning text-center">Moderator no data</div>
                </div>
            </div>
        <?php }?>
    </section>
</div>