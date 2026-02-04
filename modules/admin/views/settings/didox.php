<?php
use yii\helpers\Html;
use yii\bootstrap4\ActiveForm;

$this->title = 'Didox & E-IMZO Settings';
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
        <?php if (Yii::$app->session->hasFlash('didox_saved')) {?>
            <div class="callout callout-success text-center">
                <?=Yii::$app->session->getFlash('didox_saved');?>
            </div>
        <?php }?>
        <?php if (Yii::$app->session->hasFlash('error')) {?>
            <div class="callout callout-danger text-center">
                <?=Yii::$app->session->getFlash('error');?>
            </div>
        <?php }?>

        <?php if (Yii::$app->session->hasFlash('pfx_saved')) {?>
            <div class="callout callout-success text-center">
                <?=Yii::$app->session->getFlash('pfx_saved');?>
            </div>
        <?php }?>

        <div class="nav-tabs-custom">
            <ul class="nav nav-tabs">
                <li class="active"><a href="#tab_general" data-toggle="tab" aria-expanded="true">General Settings</a></li>
                <li class=""><a href="#tab_auto_auth" data-toggle="tab" aria-expanded="false">Automated Signing</a></li>
            </ul>
            <div class="tab-content">
                <!-- General Settings Tab -->
                <div class="tab-pane active" id="tab_general">
                    <div class="box-header">
                        <h3 class="box-title">Didox Credentials & Seller Information</h3>
                    </div>

                    <?php $form = ActiveForm::begin(['id' => 'form-general-settings']);?>
                    
                    <div class="box box-primary">
                        <div class="box-header with-border">
                            <h3 class="box-title">Integrated Configuration</h3>
                        </div>
                        
                        <div class="box-body">
                            <!-- Authentication Status & Sync -->
                            <div class="row" style="margin-bottom: 20px;">
                                <div class="col-md-12">
                                    <?php if (Yii::$app->session->get('didox_authenticated')): ?>
                                        <div class="callout callout-info" style="display: flex; justify-content: space-between; align-items: center; background-color: #d9edf7 !important; border-color: #bce8f1 !important; color: #31708f !important;">
                                            <div>
                                                <h4><i class="fa fa-check-circle"></i> Authenticated</h4>
                                                <p>You are currently authenticated in this session as: <strong><?= Yii::$app->session->get('didox_tax_id') ?></strong></p>
                                                <small>This session data can be used to populate your settings.</small>
                                            </div>
                                            <div>
                                                <button type="submit" name="copy_from_session" value="1" class="btn btn-primary">
                                                    <i class="fa fa-refresh"></i> Sync Session to Settings
                                                </button>
                                            </div>
                                        </div>
                                        <p class="text-muted small"><i class="fa fa-info-circle"></i> Clicking "Sync Session to Settings" will update both <b>Seller Information</b> and <b>System Credentials</b> with your current active session details.</p>
                                    <?php else: ?>
                                        <div class="callout callout-warning">
                                            <h4><i class="fa fa-warning"></i> Not Authenticated</h4>
                                            <p>You are NOT authenticated with Didox in this session. 
                                            <a href="<?=Yii::$app->urlManager->createUrl(['/admin/didox/login'])?>" style="text-decoration: underline; font-weight: bold;">Login here</a> 
                                            first to enable session syncing.</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="row">
                                <!-- Left Column: Seller Info -->
                                <div class="col-md-6" style="border-right: 1px solid #eee;">
                                    <h4 style="border-bottom: 2px solid #3c8dbc; padding-bottom: 10px; margin-bottom: 20px;">
                                        <i class="fa fa-building-o"></i> Seller Information (Documents)
                                    </h4>
                                    <p class="text-muted text-sm">These details will appear on invoices and contracts generated by the system.</p>
                                    
                                    <?=$form->field($models['didox_seller_inn'], '[didox_seller_inn]content')->label('Seller TIN (INN)');?>
                                    <?=$form->field($models['didox_seller_name'], '[didox_seller_name]content')->label('Seller Name (Organization)');?>
                                    <?=$form->field($models['didox_seller_address'], '[didox_seller_address]content')->label('Address');?>
                                    <?=$form->field($models['didox_seller_account'], '[didox_seller_account]content')->label('Account Number');?>
                                    <?=$form->field($models['didox_seller_mfo'], '[didox_seller_mfo]content')->label('Bank MFO');?>
                                    <?=$form->field($models['didox_seller_vat_reg_code'], '[didox_seller_vat_reg_code]content')->label('VAT Reg Code (НДС)');?>
                                </div>

                                <!-- Right Column: E-IMZO Credentials -->
                                <div class="col-md-6">
                                    <h4 style="border-bottom: 2px solid #f39c12; padding-bottom: 10px; margin-bottom: 20px;">
                                        <i class="fa fa-key"></i> System Credentials (Private)
                                    </h4>
                                    <p class="text-muted text-sm">These credentials are used for background API operations and token management.</p>
                                    
                                    <?=$form->field($models['didox_eimzo_tax_id'], '[didox_eimzo_tax_id]content')->label('System Tax ID (INN)');?>
                                    <?=$form->field($models['didox_eimzo_token'], '[didox_eimzo_token]content')->textarea(['rows' => 3])->label('System Token');?>
                                    <?=$form->field($models['didox_eimzo_last_login'], '[didox_eimzo_last_login]content')->textInput(['readonly' => true])->label('Last Updated / Login');?>
                                    <?=$form->field($models['didox_eimzo_certificate'], '[didox_eimzo_certificate]content')->textarea(['rows' => 3, 'readonly' => true])->label('Certificate Info (JSON)');?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="box-footer">
                            <div class="text-right">
                                <?=Html::submitButton('<i class="fa fa-save"></i> Save All Settings', ['class'=>'btn btn-primary btn-lg']);?>
                            </div>
                        </div>
                    </div>
                    <?php ActiveForm::end();?>
                </div>
                <!-- /.tab-pane -->

                <!-- Automated Signing Tab -->
                <div class="tab-pane" id="tab_auto_auth">
                    <div class="box-header">
                        <h3 class="box-title">Automated Server-Side Signing</h3>
                        <p class="text-muted">Configure PFX key and Signer Service to allow fully automated document creation without browser interaction.</p>
                        <div class="alert alert-info">
                            <i class="fa fa-info"></i> Requires a local E-IMZO signer service running (e.g., at http://127.0.0.1:8080/generate).
                        </div>
                    </div>
                    <?php $form = ActiveForm::begin(['id' => 'form-auto-auth', 'options' => ['enctype' => 'multipart/form-data']]);?>
                        <div class="box-body">
                            <div class="form-group">
                                <label>Current PFX Key Path</label>
                                <input type="text" class="form-control" disabled value="<?=$models['didox_pfx_path']->content?>">
                            </div>
                            
                            <div class="form-group">
                                <label>Upload New PFX Key (*.pfx)</label>
                                <?=Html::fileInput('didox_pfx_file', null, ['class' => 'form-control']);?>
                            </div>

                            <?=$form->field($models['didox_pfx_password'], '[didox_pfx_password]content')->passwordInput(['value' => $models['didox_pfx_password']->content])->label('PFX Key Password');?>
                            
                            <?=$form->field($models['didox_signer_url'], '[didox_signer_url]content')->textInput()->label('Signer Service URL (e.g. http://127.0.0.1:8080/generate)');?>
                        </div>
                        <div class="box-footer">
                            <div class="text-right">
                                <?=Html::submitButton('<i class="fa fa-check-square-o"></i> Save Auto-Signing Settings', ['class'=>'btn btn-success']);?>
                            </div>
                        </div>
                    <?php ActiveForm::end();?>
                </div>
            </div>
            <!-- /.tab-content -->
        </div>
    </section>
</div>

