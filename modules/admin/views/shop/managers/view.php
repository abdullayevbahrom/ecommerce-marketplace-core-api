<?php
use app\widgets\admin_shop_menu\AdminShopMenu;
use app\widgets\admin_language_tab\AdminLanguageTab;

$this->title = 'Manager';
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
        <?php if (Yii::$app->session->hasFlash('manager_saved')) {?>
            <div class="callout callout-success text-center">
                <?=Yii::$app->session->getFlash('manager_saved');?>
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
                                <td><?=$manager->id;?></td>
                            </tr>
                            <tr>
                                <td>Name</td>
                                <td><?=$manager->name ? $manager->name : '-';?></td>
                            </tr>
                            <tr>
                                <td>Login</td>
                                <td><?=$manager->login ? $manager->login : '-';?></td>
                            </tr>
                            <tr>
                                <td>Date</td>
                                <td><?=$manager->date ? $manager->date : '-';?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>