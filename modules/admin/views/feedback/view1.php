<?php
$this->title = 'Message';
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
        <div class="box box-info color-palette-box">
            <div class="box-header">
                <div class="pull-right">
                    <a href="<?=Yii::$app->urlManager->createUrl(['/admin/feedback/remove', 'id'=>$model->id]);?>" class="btn btn-danger remove-object"><i class="fa fa-remove"></i> Delete</a>
                </div>
                Info
            </div>
            <div class="box-body">
                <table class="table table-striped">
                    <tr>
                        <td>ID сообщения:</td>
                        <td><?=$model->id ? $model->id : 'No data';?></td>
                    </tr>
                    <tr>
                        <td>Status:</td>
                        <td>
                            <?php
                                if ($model->status == 0) {
                                    echo '<small class="label bg-red">Не просмотрен</small>';
                                }
                                if ($model->status == 1) {
                                    echo '<small class="label bg-green">Viewed</small>';
                                }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td>Name:</td>
                        <td><?=$model->name ? $model->name : 'No data';?></td>
                    </tr>
                    <tr>
                        <td>E-mail:</td>
                        <td><?=$model->email ? '<a href="mailto: '.$model->email.'">'.$model->email.'</a>' : 'No data';?></td>
                    </tr>
                    <tr>
                        <td>Date:</td>
                        <td><?=$model->date ? $model->date : 'No data';?></td>
                    </tr>
                </table>
            </div>
        </div>
        <div class="box box-info color-palette-box">
            <div class="box-header">
                Message
            </div>
            <div class="box-body">
                <?php if ($model->message) {?>
                    <?=$model->message;?>
                <?php } else {?>
                    <div class="alert alert-warning text-center">Message Not found</div>
                <?php }?>
            </div>
        </div>
    </section>
</div>