<?php
use yii\helpers\Html;
use yii\bootstrap4\ActiveForm;
use app\widgets\admin_shop_menu\AdminShopMenu;

$this->title = 'Save manager';
$this->params['breadcrumbs'][] = ['label' => 'Users', 'url' => ['index']];
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
        <?php $form = ActiveForm::begin(); ?>
            <div class="row">
                <div class="col-sm-3">
                    <?=AdminShopMenu::widget();?>
                </div>
                <div class="col-sm-9">
                    <div class="box box-info color-palette-box">
                        <div class="box-header">Main info</div>
                        <div class="box-body">
                            <div class="row">
                                <div class="col-sm-4">
                                    <?=$form->field($manager, 'name')->textInput()->input('text')->label('Name');?>
                                </div>
                                <div class="col-sm-4">
                                    <?=$form->field($manager, 'login')->textInput()->input('text', ['class'=>'form-control'])->label('Login');?>
                                </div>
                                <div class="col-sm-4">
                                    <?=$form->field($manager, 'password')->textInput()->input('password', ['value'=>''])->label('Password');?>
                                </div>
                            </div>
                        </div>
                        <div class="box-footer">
                            <div class="text-right">
                                <?=Html::submitButton('<i class="fa fa-check-square-o"></i> Save', ['class'=>'btn btn-primary']);?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php ActiveForm::end()?>
    </section>
</div>