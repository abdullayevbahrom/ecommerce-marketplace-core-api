<?php
use app\models\user\User;
?>

<?php if ($model) {?>
    <div class="box box-info color-palette-box">
        <div class="box-header with-border">
            <h3 class="box-title">DIDOX Actions</h3>
        </div>
        <div class="box-body">
            <div class="text-center mb-3">
                <i class="fa fa-file-text fa-4x text-info"></i>
                <h4><?= $model->getDocumentTypeLabel() ?></h4>
                <small class="label <?= $model->getDidoxStatusColor() ?>">
                    <?= $model->getDidoxStatusLabel() ?>
                </small>
                <br><small class="text-muted">DIDOX Doc Type: <?= $model->didox_doc_type ?: '002' ?></small>
            </div>
            
            <ul class="left-menu">
                <?php if ($model->canBeSignedInDidox()): ?>
                    <li>
                        <button type="button" class="btn btn-success width-full" onclick="signDidoxDocument(<?= $model->id ?>)">
                            <i class="fa fa-edit"></i> Sign Document
                        </button>
                    </li>
                <?php endif; ?>
                
                <?php if ($model->canBeCanceledInDidox()): ?>
                    <li>
                        <a href="<?=Yii::$app->urlManager->createUrl(['/admin/didox/cancel', 'id'=>$model->id])?>" 
                           class="btn btn-danger width-full" 
                           onclick="return confirm('Are you sure you want to cancel this DIDOX document?')">
                            <i class="fa fa-times"></i> Cancel Document
                        </a>
                    </li>
                <?php endif; ?>
                
                <li>
                    <a href="<?=Yii::$app->urlManager->createUrl(['/admin/didox/sync', 'id'=>$model->id])?>" 
                       class="btn btn-info width-full"
                       title="Синхронизировать статус с DIDOX API и получить актуальные данные">
                        <i class="fa fa-refresh"></i> Sync Status
                    </a>
                </li>

                <?php if ($model->didox_id): ?>
                <li>
                    <div class="btn-group width-full" role="group">
                        <a href="<?=Yii::$app->urlManager->createUrl(['/admin/didox/pdf', 'id'=>$model->id, 'lang'=>'uz'])?>"
                           class="btn btn-success" target="_blank"
                           title="Просмотр PDF документа">
                            <i class="fa fa-file-pdf-o"></i> PDF
                        </a>
                        <button type="button" class="btn btn-success dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/didox/pdf', 'id'=>$model->id, 'lang'=>'uz'])?>" target="_blank"><i class="fa fa-eye"></i> Просмотр (UZ)</a></li>
                            <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/didox/pdf', 'id'=>$model->id, 'lang'=>'ru'])?>" target="_blank"><i class="fa fa-eye"></i> Просмотр (RU)</a></li>
                            <li class="divider"></li>
                            <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/didox/download-pdf', 'id'=>$model->id, 'lang'=>'uz'])?>"><i class="fa fa-download"></i> Скачать (UZ)</a></li>
                            <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/didox/download-pdf', 'id'=>$model->id, 'lang'=>'ru'])?>"><i class="fa fa-download"></i> Скачать (RU)</a></li>
                        </ul>
                    </div>
                </li>
                <?php endif; ?>
                
                <hr/>
                
                <li>
                    <a href="<?=Yii::$app->urlManager->createUrl(['/admin/didox/local'])?>" 
                       class="btn btn-default width-full">
                        <i class="fa fa-list"></i> All DIDOX Documents
                    </a>
                </li>
                
                <li>
                    <div class="btn-group width-full" role="group">
                        <a href="<?=Yii::$app->urlManager->createUrl(['/admin/didox/create'])?>" 
                           class="btn btn-primary">
                            <i class="fa fa-plus"></i> Создать счет-фактуру
                        </a>
                        <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <span class="caret"></span>
                            <span class="sr-only">Toggle Dropdown</span>
                        </button>
                        <ul class="dropdown-menu">
                            <li class="active">
                                <a href="<?=Yii::$app->urlManager->createUrl(['/admin/didox/create', 'type' => 'invoice'])?>">
                                    <i class="fa fa-file-text-o"></i> Счет-фактура (002)
                                </a>
                            </li>
                            <li class="disabled">
                                <a href="#" onclick="return false;" style="color: #ccc;">
                                    <i class="fa fa-file-o"></i> Накладная (001) <small>(скоро)</small>
                                </a>
                            </li>
                            <li class="disabled">
                                <a href="#" onclick="return false;" style="color: #ccc;">
                                    <i class="fa fa-file"></i> Другие типы (003+) <small>(скоро)</small>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
                
                <li>
                    <a href="<?=Yii::$app->urlManager->createUrl(['/admin/didox'])?>" 
                       class="btn btn-default width-full">
                        <i class="fa fa-dashboard"></i> DIDOX Dashboard
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <!-- Document Information -->
    <div class="box box-warning color-palette-box">
        <div class="box-header with-border">
            <h3 class="box-title">Document Info</h3>
        </div>
        <div class="box-body">
            <table class="table table-condensed">
                <tr>
                    <td><strong>DIDOX ID:</strong></td>
                    <td><small><?= $model->didox_id ? substr($model->didox_id, 0, 12) . '...' : 'N/A' ?></small></td>
                </tr>
                <tr>
                    <td><strong>Type:</strong></td>
                    <td>
                        <span class="label label-info"><?= $model->document_type ?></span>
                        <br><small class="text-muted">DIDOX: <?= $model->didox_doc_type ?: '002' ?></small>
                    </td>
                </tr>
                <tr>
                    <td><strong>Total Sum:</strong></td>
                    <td><strong><?= ($model->invoice && $model->invoice->total_sum) ? number_format($model->invoice->total_sum, 2) : '0.00' ?></strong></td>
                </tr>
                <tr>
                    <td><strong>Created:</strong></td>
                    <td><?= $model->didox_created_at ? date('Y-m-d H:i', strtotime($model->didox_created_at)) : 'N/A' ?></td>
                </tr>
                <tr>
                    <td><strong>Signed:</strong></td>
                    <td><?= $model->didox_signed_at ? date('Y-m-d H:i', strtotime($model->didox_signed_at)) : '<em>Not signed</em>' ?></td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Sign Modal for DIDOX -->
    <div class="modal fade" id="didoxSignModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title">Sign DIDOX Document</h4>
                </div>
                <div class="modal-body">
                    <div id="signing-status">
                        <p>Preparing document for signing...</p>
                        <div class="progress">
                            <div class="progress-bar progress-bar-striped active" role="progressbar" style="width: 100%"></div>
                        </div>
                    </div>
                    <div id="signing-form" style="display: none;">
                        <form id="sign-form">
                            <div class="form-group">
                                <label>Document Data (Base64)</label>
                                <textarea id="document-data" class="form-control" rows="4" readonly></textarea>
                            </div>
                            <div class="form-group">
                                <label>Digital Signature (PKCS7)</label>
                                <textarea id="signature-input" name="signature" class="form-control" rows="4" 
                                          placeholder="Paste your E-IMZO digital signature here..." required></textarea>
                                <small class="help-block">
                                    Use your E-IMZO client to sign the document data above, then paste the resulting PKCS7 signature here.
                                </small>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="submit-signature" style="display: none;">
                        <i class="fa fa-edit"></i> Submit Signature
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
    function signDidoxDocument(documentId) {
        $('#didoxSignModal').modal('show');
        
        // Get document data for signing
        $.ajax({
            url: '<?= Yii::$app->urlManager->createUrl(['/admin/didox/get-sign-data']) ?>',
            method: 'GET',
            data: { id: documentId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#document-data').val(btoa(JSON.stringify(response.data)));
                    $('#signing-status').hide();
                    $('#signing-form').show();
                    $('#submit-signature').show();
                } else {
                    alert('Error getting document data: ' + (response.error || 'Unknown error'));
                    $('#didoxSignModal').modal('hide');
                }
            },
            error: function() {
                alert('Failed to get document data for signing');
                $('#didoxSignModal').modal('hide');
            }
        });
        
        // Handle signature submission
        $('#submit-signature').off('click').on('click', function() {
            var signature = $('#signature-input').val().trim();
            
            if (!signature) {
                alert('Please enter the digital signature');
                return;
            }
            
            // Submit signature
            var form = $('<form>', {
                'method': 'POST',
                'action': '<?= Yii::$app->urlManager->createUrl(["/admin/didox/sign"]) ?>/' + documentId
            });
            
            form.append($('<input>', {
                'type': 'hidden',
                'name': 'signature',
                'value': signature
            }));
            
            form.append($('<input>', {
                'type': 'hidden',
                'name': '<?= Yii::$app->request->csrfParam ?>',
                'value': '<?= Yii::$app->request->csrfToken ?>'
            }));
            
            $('body').append(form);
            form.submit();
        });
    }

    // Reset modal when closed
    $('#didoxSignModal').on('hidden.bs.modal', function() {
        $('#signing-status').show();
        $('#signing-form').hide();
        $('#submit-signature').hide();
        $('#signature-input').val('');
        $('#document-data').val('');
    });
    </script>
    
    <style>
    /* Document Type Dropdown Styling */
    .btn-group.width-full {
        width: 100%;
        display: flex;
    }
    
    .btn-group.width-full .btn:first-child {
        flex: 1;
    }
    
    .btn-group.width-full .dropdown-toggle {
        flex: 0 0 auto;
        border-left: 1px solid rgba(255,255,255,0.2);
    }
    
    .dropdown-menu {
        width: 100%;
        left: 0 !important;
    }
    
    .dropdown-menu .disabled a {
        cursor: not-allowed !important;
        opacity: 0.5;
    }
    
    .dropdown-menu .active a {
        background-color: #337ab7;
        color: white;
    }
    
    /* Invoice-focused styling */
    .invoice-highlight {
        border-left: 4px solid #5cb85c;
        padding-left: 10px;
    }
    </style>
<?php } else { ?>
    <div class="box box-warning color-palette-box">
        <div class="box-body text-center">
            <i class="fa fa-exclamation-triangle fa-3x text-warning"></i>
            <h4>DIDOX Document Not Found</h4>
            <p>The requested DIDOX document could not be found.</p>
            <a href="<?=Yii::$app->urlManager->createUrl(['/admin/didox'])?>" class="btn btn-default">
                <i class="fa fa-arrow-left"></i> Back to DIDOX
            </a>
        </div>
    </div>
<?php }?> 