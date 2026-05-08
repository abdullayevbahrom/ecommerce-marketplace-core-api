<?php
use yii\helpers\Html;
use yii\bootstrap4\ActiveForm;

/* @var $pfxValidation array */

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
        <?php if (Yii::$app->session->hasFlash('pfx_error')) {?>
            <div class="callout callout-danger text-center">
                <?=Yii::$app->session->getFlash('pfx_error');?>
            </div>
        <?php }?>

        <div class="nav-tabs-custom">
            <ul class="nav nav-tabs">
                <li class="active"><a href="#tab_general" data-toggle="tab" aria-expanded="true">General Settings</a></li>
                <li class=""><a href="#tab_auto_auth" data-toggle="tab" aria-expanded="false">Automated Signing</a></li>
                <li class=""><a href="#tab_token_status" data-toggle="tab" aria-expanded="false">Token Status</a></li>
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
                            <i class="fa fa-info"></i> Requires a Docker-accessible E-IMZO signer service running (e.g., at http://eimzo-signer:8080/generate).
                        </div>
                        <?php if (!$pfxValidation['success']): ?>
                            <div class="alert alert-warning" style="margin-bottom: 0;">
                                <i class="fa fa-warning"></i> Auto-refresh blocked: <?= Html::encode($pfxValidation['error']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php $form = ActiveForm::begin(['id' => 'form-auto-auth', 'options' => ['enctype' => 'multipart/form-data']]);?>
                        <div class="box-body">
                            <div class="form-group">
                                <label>Current PFX File Name</label>
                                <input type="text" class="form-control" disabled value="<?=$models['didox_pfx_path']->content?>">
                            </div>
                            
                            <div class="form-group">
                                <label>Upload New PFX Key (*.pfx)</label>
                                <?=Html::fileInput('didox_pfx_file', null, ['class' => 'form-control']);?>
                            </div>

                            <?=$form->field($models['didox_pfx_password'], '[didox_pfx_password]content')->passwordInput(['value' => $models['didox_pfx_password']->content])->label('PFX Key Password');?>
                            
                            <?=$form->field($models['didox_signer_url'], '[didox_signer_url]content')->textInput()->label('Signer Service URL (e.g. http://eimzo-signer:8080/generate)');?>
                        </div>
                        <div class="box-footer">
                            <div class="text-right">
                                <?=Html::submitButton('<i class="fa fa-check-square-o"></i> Save Auto-Signing Settings', ['class'=>'btn btn-success']);?>
                            </div>
                        </div>
                    <?php ActiveForm::end();?>
                </div>
                
                <!-- Token Status Tab -->
                <div class="tab-pane" id="tab_token_status">
                    <div class="box-header">
                        <h3 class="box-title">Automatic Token Management</h3>
                        <p class="text-muted">Monitor and manage the automated Didox token refresh system.</p>
                    </div>
                    
                    <div class="box-body">
                        <!-- Token Status Card -->
                        <?php
                        $tokenStatus = (new \app\services\DidoxService())->getTokenStatus();
                        $statusClass = $tokenStatus['is_expired'] ? 'danger' : ($tokenStatus['expiring_soon'] ? 'warning' : 'success');
                        $statusIcon = $tokenStatus['is_expired'] ? 'times-circle' : ($tokenStatus['expiring_soon'] ? 'exclamation-triangle' : 'check-circle');
                        $statusText = $tokenStatus['is_expired'] ? 'Expired' : ($tokenStatus['expiring_soon'] ? 'Expiring Soon' : 'Active');
                        $autoRefreshBlocked = !$pfxValidation['success'];
                        ?>
                        
                        <div class="callout callout-<?=$statusClass;?>">
                            <h4><i class="fa fa-<?=$statusIcon;?>"></i> Token Status: <?=$statusText;?></h4>
                            <div class="row" style="margin-top: 15px;">
                                <div class="col-md-3">
                                    <strong>Has Token:</strong><br>
                                    <span class="badge bg-<?=$tokenStatus['has_token'] ? 'green' : 'red';?>">
                                        <?= $tokenStatus['has_token'] ? 'Yes' : 'No'; ?>
                                    </span>
                                </div>
                                <div class="col-md-3">
                                    <strong>Auto Refresh:</strong><br>
                                    <?php if ($tokenStatus['status'] === 'active'): ?>
                                        <span class="badge bg-green">Active</span>
                                    <?php elseif ($tokenStatus['status'] === 'disabled'): ?>
                                        <span class="badge bg-gray">Disabled</span>
                                    <?php elseif ($tokenStatus['status'] === 'failed'): ?>
                                        <span class="badge bg-red">Failed</span>
                                    <?php else: ?>
                                        <span class="badge bg-yellow">Manual</span>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-3">
                                    <strong>Time Remaining:</strong><br>
                                    <?php if ($tokenStatus['expires_in']): ?>
                                        <code style="font-size: 14px;"><?=$tokenStatus['expires_in'];?></code>
                                    <?php else: ?>
                                        <span class="text-muted">N/A</span>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-3">
                                    <strong>Seller INN:</strong><br>
                                    <code><?=$tokenStatus['seller_inn'] ?: 'Not set';?></code>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Detailed Info -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="box box-solid box-default">
                                    <div class="box-header with-border">
                                        <h3 class="box-title">Token Details</h3>
                                    </div>
                                    <div class="box-body">
                                        <table class="table table-bordered table-striped">
                                            <tr>
                                                <td><strong>Expires At:</strong></td>
                                                <td><?=$tokenStatus['expires_at'] ?: '-';?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Last Login:</strong></td>
                                                <td><?=$tokenStatus['last_login'] ?: '-';?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Last Attempt:</strong></td>
                                                <td><?=$tokenStatus['last_attempt'] ?: '-';?></td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <?php if ($tokenStatus['last_error']): ?>
                                <div class="box box-solid box-danger">
                                    <div class="box-header with-border">
                                        <h3 class="box-title">Last Error</h3>
                                    </div>
                                    <div class="box-body">
                                        <div class="alert alert-danger" style="margin: 0;">
                                            <pre style="margin:0; white-space:pre-wrap; word-break:break-word; max-height:360px; overflow:auto; border:0; background:transparent;"><?=Html::encode($tokenStatus['last_error']);?></pre>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <div class="box box-solid box-info">
                                    <div class="box-header with-border">
                                        <h3 class="box-title">Actions</h3>
                                    </div>
                                    <div class="box-body">
                                        <?=Html::beginForm(['/admin/settings/didox'], 'post', ['style' => 'display: inline;']);?>
                                            <?=Html::hiddenInput('refresh_token', '1');?>
                                            <button type="submit" class="btn btn-primary" 
                                                <?= $autoRefreshBlocked ? 'disabled title="' . Html::encode($pfxValidation['error']) . '"' : ''; ?>
                                                onclick="return confirm('Are you sure you want to refresh the token now?');">
                                                <i class="fa fa-refresh"></i> Refresh Token Now
                                            </button>
                                        <?=Html::endForm();?>
                                        
                                        &nbsp;
                                        
                                        <?php if ($tokenStatus['status'] === 'active'): ?>
                                            <?=Html::beginForm(['/admin/settings/didox'], 'post', ['style' => 'display: inline;']);?>
                                                <?=Html::hiddenInput('toggle_auto', 'disable');?>
                                                <button type="submit" class="btn btn-warning">
                                                    <i class="fa fa-pause"></i> Disable Auto-Refresh
                                                </button>
                                            <?=Html::endForm();?>
                                        <?php else: ?>
                                            <?=Html::beginForm(['/admin/settings/didox'], 'post', ['style' => 'display: inline;']);?>
                                                <?=Html::hiddenInput('toggle_auto', 'enable');?>
                                                <button type="submit" class="btn btn-success" <?= $autoRefreshBlocked ? 'disabled title="' . Html::encode($pfxValidation['error']) . '"' : ''; ?>>
                                                    <i class="fa fa-play"></i> Enable Auto-Refresh
                                                </button>
                                            <?=Html::endForm();?>
                                        <?php endif; ?>
                                        <?php if ($autoRefreshBlocked): ?>
                                            <p class="text-muted" style="margin-top: 10px; margin-bottom: 0;">
                                                Auto-refresh va token refresh faqat PFX fayl yuklangandan keyin ishlaydi.
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /.tab-content -->
        </div>
    </section>
</div>
