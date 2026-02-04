<?php
use app\widgets\admin_language_tab\AdminLanguageTab;

$this->title = 'Question';
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
        <?php if (Yii::$app->session->hasFlash('question_saved')) {?>
            <div class="callout callout-success text-center">
                <?=Yii::$app->session->getFlash('question_saved');?>
            </div>
        <?php }?>
        <?php if (Yii::$app->session->hasFlash('question_locked')) {?>
            <div class="callout callout-success text-center">
                <?=Yii::$app->session->getFlash('question_locked');?>
            </div>
        <?php }?>
        <?=AdminLanguageTab::widget();?>
        <br/>
        <div class="box box-info color-palette-box">
            <div class="box-header">
                <div class="pull-right">
                    <a href="<?=Yii::$app->urlManager->createUrl(['/admin/question/create']);?>" class="btn btn-primary"><i class="fa fa-plus"></i> Add</a>
                    <a href="<?=Yii::$app->urlManager->createUrl(['/admin/question/create', 'id'=>$model->id]);?>" class="btn btn-success"><i class="fa fa-pencil"></i> Edit</a>
                    <a href="<?=Yii::$app->urlManager->createUrl(['/admin/question/lock', 'id'=>$model->id])?>" class="btn btn-warning"><?php if ($model->status == 1) {?><i class="fa fa-lock"></i> Block<?php } else {?><i class="fa fa-unlock"></i> Unblock<?php }?></a>
                    <a href="<?=Yii::$app->urlManager->createUrl(['/admin/question/remove', 'id'=>$model->id]);?>" class="btn btn-danger remove-object"><i class="fa fa-remove"></i> Delete</a>
                </div>
                <strong>Question</strong> <?=$model->date;?>
                <?php
                    if ($model->status == 2) {
                        echo '<small class="label bg-red">Blocked</small>';
                    }
                    if ($model->status == 1) {
                        echo '<small class="label bg-green">Active</small>';
                    }
                ?>
            </div>
            <div class="box-body">
                <div class="lang-block lang-block-ru">
                    <?=$model->question_ru ? $model->question_ru : '';?>
                </div>
                <div class="lang-block lang-block-uz">
                    <?=$model->question_uz ? $model->question_uz : '';?>
                </div>
                <div class="lang-block lang-block-en">
                    <?=$model->question_en ? $model->question_en : '';?>
                </div>
            </div>
        </div> 
        <div class="box box-info color-palette-box">
            <div class="box-header">
                <strong>Answer</strong>
            </div>
            <div class="box-body">
                <div class="lang-block lang-block-ru">
                    <?=$model->answer_ru ? $model->answer_ru : '';?>
                </div>
                <div class="lang-block lang-block-uz">
                    <?=$model->answer_uz ? $model->answer_uz : '';?>
                </div>
                <div class="lang-block lang-block-en">
                    <?=$model->answer_en ? $model->answer_en : '';?>
                </div>
            </div>
        </div>
    </section>
</div>