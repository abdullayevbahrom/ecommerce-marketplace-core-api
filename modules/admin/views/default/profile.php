<?php
use app\models\user\User;

use app\widgets\admin_menu\AdminMenu;

$this->title = 'Profile';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="content-wrapper">
    <?php if (Yii::$app->session->hasFlash('moderator_deleted')) {?>
        <div class="alert alert-success text-center"><?=Yii::$app->session->getFlash('moderator_deleted');?></div>
    <?php }?>
    <?php if (Yii::$app->session->hasFlash('moderators_deleted')) {?>
        <div class="alert alert-success text-center"><?=Yii::$app->session->getFlash('moderators_deleted');?></div>
    <?php }?>
    <section class="content-header">
        <h1><?=$this->title;?></h1>

        <ol class="breadcrumb">
            <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/'])?>"><i class="fa fa-dashboard"></i> Home</a></li>
            <li class="active"><?=$this->title;?></li>
        </ol>
    </section>
    <section class="content">
        <?php if ($model) {?>
            <div class="row">
                <div class="col-sm-3">
                    <?=AdminMenu::widget();?>
                </div>
                <div class="col-sm-9">
                    <div class="box box-info color-palette-box">
                        <div class="box-header">
                            Info user
                        </div>
                        <div class="box-body">
                            <table class="table table-striped">
                                <tr>
                                    <td>ID</td>
                                    <td><?=$model->id ? $model->id : 'No date';?></td>
                                </tr>
                                <tr>
                                    <td>Role</td>
                                    <td>
                                        <?php if ($model->role == User::ROLE_ADMIN) {?>
                                            <small class="label bg-green">Admin</small>
                                        <?php }?>
                                        <?php if ($model->role == User::ROLE_MODERATOR) {?>
                                            <small class="label bg-green">Moderator</small>
                                        <?php }?>
                                    </td>
                                </tr> 
                                <tr>
                                    <td>Login</td>
                                    <td><?=$model->login ? $model->login : 'No date';?></td>
                                </tr>    
                                <tr>
                                    <td>Name</td>
                                    <td><?=$model->name ? $model->name : 'No date';?></td>
                                </tr>
                                <tr>
                                    <td>Phone</td>
                                    <td><?=$model->phone ? $model->phone : 'No date';?></td>
                                </tr>
                                <tr>
                                    <td>E-mail</td>
                                    <td><?=$model->email ? $model->email : 'No date';?></td>
                                </tr>
                                <tr>
                                    <td>Registration date</td>
                                    <td><?=$model->date ? $model->date : 'No date';?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        <?php } else {?>
            <div class="card">
                <div class="card-body">
                    <div class="alert alert-warning text-center">Profile does not exist</div>
                </div>
            </div>
        <?php }?>
    </section>
</div>