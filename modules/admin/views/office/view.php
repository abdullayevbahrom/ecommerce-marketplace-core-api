<?php
use app\widgets\admin_office_menu\AdminOfficeMenu;

$this->title = $model ? $model->name : 'Офис';
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
        <?php if (Yii::$app->session->hasFlash('office_saved')) {?>
            <div class="callout callout-success text-center">
                <?=Yii::$app->session->getFlash('office_saved');?>
            </div>
        <?php }?>
        <div class="row">
            <div class="col-sm-3">
                <?=AdminOfficeMenu::widget();?>
            </div>
            <div class="col-sm-9">
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
                                <td>Name</td>
                                <td><?=$model->name ? $model->name : '-';?></td>
                            </tr>
                            <tr>
                                <td>Date</td>
                                <td><?=$model->date ? $model->date : '-';?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>