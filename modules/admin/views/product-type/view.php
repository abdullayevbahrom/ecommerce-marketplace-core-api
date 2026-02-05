<?php
use yii\helpers\Html;
use app\widgets\admin_language_tab\AdminLanguageTab;

$this->title = 'Product Type: ' . $model->name_ru;
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="content-wrapper">
    <section class="content-header">
        <h1><?=$this->title;?></h1>

        <ol class="breadcrumb">
            <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/'])?>"><i class="fa fa-dashboard"></i> Home</a></li>
            <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/product-type/'])?>">Product Types</a></li>
            <li class="active"><?=$this->title;?></li>
        </ol>
    </section>
    <section class="content">
        <?php if (Yii::$app->session->hasFlash('product_type_saved')) {?>
            <div class="callout callout-success text-center">
                <?=Yii::$app->session->getFlash('product_type_saved');?>
            </div>
        <?php }?>
        <?php if (Yii::$app->session->hasFlash('product_type_locked')) {?>
            <div class="callout callout-success text-center">
                <?=Yii::$app->session->getFlash('product_type_locked');?>
            </div>
        <?php }?>
        
        <div class="row">
            <div class="col-sm-3">
                <div class="box box-info color-palette-box">
                    <div class="box-body">
                        <ul class="left-menu">
                            <?php if (Yii::$app->user->identity->role != \app\models\user\User::ROLE_MODERATOR): ?>
                            <li>
                                <a href="<?=Yii::$app->urlManager->createUrl(['/admin/product-type/create', 'id'=>$model->id])?>" class="btn btn-primary width-full">
                                    <i class="fa fa-pencil"></i> Edit
                                </a>
                            </li>
                            <?php endif; ?>
                            <li>
                                <a href="<?=Yii::$app->urlManager->createUrl(['/admin/product-type/lock', 'id'=>$model->id])?>" class="btn btn-warning width-full">
                                    <?php if ($model->status == 1) {?>
                                        <i class="fa fa-lock"></i> Block
                                    <?php } else {?>
                                        <i class="fa fa-unlock"></i> Unblock
                                    <?php }?>
                                </a>
                            </li>
                            <?php if (Yii::$app->user->identity->role === \app\models\user\User::ROLE_MODERATOR && $model->status == 2): ?>
                                    <hr>
                                    <h4><i class="fa fa-comment"></i> Комментарий модератора</h4>
                                    <form method="post" action="<?=Yii::$app->urlManager->createUrl(['/admin/product-type/comment', 'id'=>$model->id])?>">
                                        <?= Html::csrfMetaTags() ?>
                                        <textarea
                                            name="comment"
                                            class="form-control"
                                            rows="4"
                                            required
                                            placeholder="Укажите причину, почему тип товара остаётся заблокированным"
                                        ></textarea>
                                        <br>
                                        <button type="submit" class="btn btn-warning btn-sm">
                                            <i class="fa fa-paper-plane"></i> Отправить комментарий
                                        </button>
                                    </form>
                            <?php endif; ?>
                            <?php if (Yii::$app->user->identity->role != \app\models\user\User::ROLE_MODERATOR): ?>
                            <li>
                                <a href="<?=Yii::$app->urlManager->createUrl(['/admin/product-type/remove', 'id'=>$model->id])?>" class="btn btn-danger width-full remove-object">
                                    <i class="fa fa-trash"></i> Delete
                                </a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-sm-9">
                <?=AdminLanguageTab::widget();?>
                <br/>
                
                <div class="box box-info color-palette-box">
                    <div class="box-header">General Information</div>
                    <div class="box-body">
                        <table class="table table-striped">
                            <tr>
                                <td style="width: 200px;"><strong>ID</strong></td>
                                <td><?=$model->id;?></td>
                            </tr>
                            <tr>
                                <td><strong>Category</strong></td>
                                <td><?=$model->category ? $model->category->name_ru : '-';?></td>
                            </tr>
                            <tr>
                                <td><strong>Type</strong></td>
                                <td>
                                    <?php
                                    $types = [
                                        'input' => 'Input (Text field)',
                                        'select' => 'Select (Dropdown)',
                                        'checkbox' => 'Checkbox (Multiple choice)',
                                        'range' => 'Range (Min-Max values)'
                                    ];
                                    echo $types[$model->type] ?? $model->type;
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Sort Order</strong></td>
                                <td><?=$model->sort;?></td>
                            </tr>
                            <tr>
                                <td><strong>Status</strong></td>
                                <td>
                                    <?php if ($model->status == 1) {?>
                                        <small class="label bg-green">Active</small>
                                    <?php } else {?>
                                        <small class="label bg-red">Blocked</small>
                                    <?php }?>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Created</strong></td>
                                <td><?=date('Y-m-d H:i:s', strtotime($model->date));?></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div class="box box-info color-palette-box">
                    <div class="box-header">Names</div>
                    <div class="box-body">
                        <table class="table table-striped">
                            <tr>
                                <td style="width: 200px;"><strong>Name (RU)</strong></td>
                                <td><?=$model->name_ru;?></td>
                            </tr>
                            <tr>
                                <td><strong>Name (EN)</strong></td>
                                <td><?=$model->name_en ?: '-';?></td>
                            </tr>
                            <tr>
                                <td><strong>Name (UZ)</strong></td>
                                <td><?=$model->name_uz ?: '-';?></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <?php if ($model->description_ru || $model->description_en || $model->description_uz) { ?>
                <div class="box box-info color-palette-box">
                    <div class="box-header">Descriptions</div>
                    <div class="box-body">
                        <table class="table table-striped">
                            <?php if ($model->description_ru) { ?>
                            <tr>
                                <td style="width: 200px;"><strong>Description (RU)</strong></td>
                                <td><?=$model->description_ru;?></td>
                            </tr>
                            <?php } ?>
                            <?php if ($model->description_en) { ?>
                            <tr>
                                <td><strong>Description (EN)</strong></td>
                                <td><?=$model->description_en;?></td>
                            </tr>
                            <?php } ?>
                            <?php if ($model->description_uz) { ?>
                            <tr>
                                <td><strong>Description (UZ)</strong></td>
                                <td><?=$model->description_uz;?></td>
                            </tr>
                            <?php } ?>
                        </table>
                    </div>
                </div>
                <?php } ?>

                <?php if ($model->productTypeValues && count($model->productTypeValues) > 0) { ?>
                <div class="box box-info color-palette-box">
                    <div class="box-header">Predefined Values (<?=count($model->productTypeValues);?>)</div>
                    <div class="box-body">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Value (RU)</th>
                                    <th>Value (EN)</th>
                                    <th>Value (UZ)</th>
                                    <th>Sort</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($model->productTypeValues as $value) { ?>
                                <tr>
                                    <td><?=$value->id;?></td>
                                    <td><?=$value->value_ru;?></td>
                                    <td><?=$value->value_en ?: '-';?></td>
                                    <td><?=$value->value_uz ?: '-';?></td>
                                    <td><?=$value->sort;?></td>
                                    <td>
                                        <?php if ($value->status == 1) {?>
                                            <small class="label bg-green">Active</small>
                                        <?php } else {?>
                                            <small class="label bg-red">Blocked</small>
                                        <?php }?>
                                    </td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php } ?>
            </div>
        </div>
    </section>
</div> 