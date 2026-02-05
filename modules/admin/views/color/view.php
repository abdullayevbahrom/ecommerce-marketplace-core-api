<?php
use app\widgets\admin_language_tab\AdminLanguageTab;
use yii\helpers\Html;

$this->title = 'Color';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="content-wrapper">
    <section class="content-header">
        <h1>
            <?=mb_substr($this->title, 0, 30, 'utf-8');?>
            <?=(mb_strlen($this->title) >= 30) ? '...' : '';?>        
        </h1>
        
        <ol class="breadcrumb">
            <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/'])?>"><i class="fa fa-dashboard"></i> Home</a></li>
            <li class="active"><?=$this->title;?></li>
        </ol>
    </section>
    <section class="content">
        <?php if (Yii::$app->session->hasFlash('color_saved')) {?>
            <div class="callout callout-success text-center">
                <?=Yii::$app->session->getFlash('color_saved');?>
            </div>
        <?php }?>
        <?php if ($model) {?>
            <?=AdminLanguageTab::widget();?>
            <br/>
            <div class="box box-info color-palette-box">
                <div class="box-header">
                    <i class="fa fa-clock-o"></i> <?=$model->date;?>
                    <div class="pull-right">
                        <div class="btn-group">
                            <button type="button" class="btn btn-danger dropdown-toggle" data-toggle="dropdown">
                                <span class="fa fa-cog"></span>
                            </button>
                            <ul class="dropdown-menu pull-right">
                                <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/color/']);?>">Список цветов</a></li>
                                <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/color/lock', 'id'=>$model->id]);?>"><?=($model->status == 1) ? 'Заблокировать' : 'Разблокировать';?></a></li>
                                <?php if (Yii::$app->user->identity->role !== \app\models\user\User::ROLE_MODERATOR): ?>    
                                <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/color/create']);?>">Add color</a></li>
                                <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/color/create', 'id'=>$model->id]);?>">Edit</a></li>
                                <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/color/remove', 'id'=>$model->id]);?>" class="remove-object">Delete</a></li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="box-body">
                    <div class="lang-block lang-block-ru">
                        <table class="table table-striped">
                            <tr>
                                <td><strong>Name</strong></td>
                                <td><?=$model->name_ru ? $model->name_ru : '-';?></td>
                            </tr>
                            <tr>
                                <td><strong>Color</strong></td>
                                <td><div class="block-color" style="border:1px solid #000; background-color:<?=$model->color;?>"></div></td>
                            </tr>
                        </table>
                        <?php if (Yii::$app->user->identity->role === \app\models\user\User::ROLE_MODERATOR && $model->status == 2): ?>
                                    <hr>
                                    <h4><i class="fa fa-comment"></i> Комментарий модератора</h4>
                                    <form method="post" action="<?=Yii::$app->urlManager->createUrl(['/admin/color/comment', 'id'=>$model->id])?>">
                                        <?= Html::csrfMetaTags() ?>
                                        <textarea
                                            name="comment"
                                            class="form-control"
                                            rows="4"
                                            required
                                            placeholder="Укажите причину, почему товар остаётся заблокированным"
                                        ></textarea>
                                        <br>
                                        <button type="submit" class="btn btn-warning btn-sm">
                                            <i class="fa fa-paper-plane"></i> Отправить комментарий
                                        </button>
                                    </form>
                        <?php endif; ?>
                    </div>
                    <div class="lang-block lang-block-uz">
                        <table class="table table-striped">
                            <tr>
                                <td><strong>Name</strong></td>
                                <td><?=$model->name_uz ? $model->name_uz : '-';?></td>
                            </tr>
                            <tr>
                                <td><strong>Color</strong></td>
                                <td><div class="block-color" style="border:1px solid #000; background-color:<?=$model->color;?>"></div></td>
                            </tr>
                        </table>
                    </div>
                    <div class="lang-block lang-block-en">
                        <table class="table table-striped">
                            <tr>
                                <td><strong>Name</strong></td>
                                <td><?=$model->name_en ? $model->name_en : '-';?></td>
                            </tr>
                            <tr>
                                <td><strong>Color</strong></td>
                                <td><div class="block-color" style="border:1px solid #000; background-color:<?=$model->color;?>"></div></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        <?php } else {?>
            <div class="box box-info color-palette-box">
                <div class="box-body">
                    <div class="alert alert-warning text-center">No color</div>
                </div>
            </div>
        <?php }?>
    </section>
</div>