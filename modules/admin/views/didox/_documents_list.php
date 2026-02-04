<?php
use yii\helpers\Html;
use yii\helpers\Url;

// Check if there's an error
if (isset($error)): ?>
    <div class="callout callout-danger">
        <i class="fa fa-exclamation-triangle"></i> Error loading documents: <?= Html::encode($error) ?>
    </div>
<?php return; endif;

// Check if documents array is empty
if (empty($documents)): ?>
    <div class="callout callout-info">
        <i class="fa fa-info-circle"></i> No documents found.
        <?php if ($source === 'api'): ?>
            Try checking your DIDOX API connection or create some documents first.
        <?php elseif ($source === 'local'): ?>
            No local documents found. Create your first document using the Create tab.
        <?php else: ?>
            Try creating your first document using the Create tab or check your API connection.
        <?php endif; ?>
    </div>
<?php return; endif; ?>

<!-- Documents Table -->
<div class="table-responsive">
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th width="80">Source</th>
                <th>Document Name</th>
                <th width="80">Type</th>
                <th width="120">Status</th>
                <th width="100">Buyer TIN</th>
                <th width="100">Seller TIN</th>
                <th width="100">Total Sum</th>
                <th width="100">Created</th>
                <th width="150">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($documents as $doc): ?>
                <tr>
                    <!-- Source -->
                    <td>
                        <?php if ($doc['source'] === 'local'): ?>
                            <span class="label label-primary">
                                <i class="fa fa-database"></i> Local
                            </span>
                        <?php else: ?>
                            <span class="label label-info">
                                <i class="fa fa-cloud"></i> API
                            </span>
                        <?php endif; ?>
                    </td>
                    
                    <!-- Document Name -->
                    <td>
                        <strong><?= Html::encode($doc['name']) ?></strong>
                        <?php if ($doc['didox_id']): ?>
                            <br><small class="text-muted">ID: <?= Html::encode($doc['didox_id']) ?></small>
                        <?php endif; ?>
                    </td>
                    
                    <!-- Type -->
                    <td>
                        <span class="label label-default"><?= Html::encode($doc['type']) ?></span>
                    </td>
                    
                    <!-- Status -->
                    <td>
                        <span class="label label-<?= Html::encode($doc['status_color']) ?>">
                            <?= Html::encode($doc['status_label']) ?>
                        </span>
                    </td>
                    
                    <!-- Buyer TIN -->
                    <td><?= Html::encode($doc['buyer_tin']) ?></td>
                    
                    <!-- Seller TIN -->
                    <td><?= Html::encode($doc['seller_tin']) ?></td>
                    
                    <!-- Total Sum -->
                    <td>
                        <?php if ($doc['total_sum']): ?>
                            <?= number_format($doc['total_sum'], 2) ?>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    
                    <!-- Created Date -->
                    <td>
                        <?php if ($doc['created_at']): ?>
                            <small><?= date('Y-m-d H:i', strtotime($doc['created_at'])) ?></small>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    
                    <!-- Actions -->
                    <td>
                        <?php if ($doc['source'] === 'local'): ?>
                            <!-- Local document actions -->
                            <div class="btn-group">
                                <a href="<?= $doc['view_url'] ?>" class="btn btn-xs btn-primary" title="View">
                                    <i class="fa fa-eye"></i>
                                </a>
                                
                                <?php if ($doc['can_sign']): ?>
                                    <button type="button" class="btn btn-xs btn-success" 
                                            onclick="openSignModal(<?= $doc['id'] ?>)" title="Sign">
                                        <i class="fa fa-edit"></i>
                                    </button>
                                <?php endif; ?>
                                
                                <button type="button" class="btn btn-xs btn-info" 
                                        onclick="syncDocument(<?= $doc['id'] ?>)" title="Sync">
                                    <i class="fa fa-refresh"></i>
                                </button>
                                
                                <?php if ($doc['can_cancel']): ?>
                                    <a href="<?= Url::to(['cancel', 'id' => $doc['id']]) ?>" 
                                       class="btn btn-xs btn-danger" title="Cancel"
                                       onclick="return confirm('Are you sure you want to cancel this document?')">
                                        <i class="fa fa-times"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <!-- API document actions -->
                            <div class="btn-group">
                                <button type="button" class="btn btn-xs btn-primary" 
                                        onclick="viewApiDocument('<?= Html::encode($doc['didox_id']) ?>')" title="View">
                                    <i class="fa fa-eye"></i>
                                </button>
                                <button type="button" class="btn btn-xs btn-info" 
                                        onclick="importDocument('<?= Html::encode($doc['didox_id']) ?>')" title="Import">
                                    <i class="fa fa-download"></i>
                                </button>
                            </div>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Statistics -->
<div class="row">
    <div class="col-md-12">
        <div class="info-box">
            <span class="info-box-icon bg-aqua">
                <i class="fa fa-files-o"></i>
            </span>
            <div class="info-box-content">
                <span class="info-box-text">Total Documents</span>
                <span class="info-box-number"><?= count($documents) ?></span>
                <div class="progress">
                    <div class="progress-bar" style="width: 100%"></div>
                </div>
                <span class="progress-description">
                    <?php
                    $localCount = count(array_filter($documents, function($doc) { return $doc['source'] === 'local'; }));
                    $apiCount = count($documents) - $localCount;
                    ?>
                    <?= $localCount ?> local, <?= $apiCount ?> from API
                </span>
            </div>
        </div>
    </div>
</div>

<script>
// Document actions
function openSignModal(documentId) {
    // This would open a modal for E-IMZO signing
    if (confirm('Do you want to sign this document with E-IMZO?')) {
        // Implementation for signing modal
        alert('Signing modal will be implemented');
    }
}

function syncDocument(documentId) {
    if (confirm('Sync this document with DIDOX API?')) {
        fetch(<?= json_encode(Url::to(['/admin/didox/sync'])) ?> + '/' + documentId, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.text())
        .then(data => {
            // Reload the documents list
            loadDocuments();
            alert('Document synchronized successfully');
        })
        .catch(error => {
            console.error('Sync error:', error);
            alert('Error synchronizing document: ' + error.message);
        });
    }
}

function viewApiDocument(didoxId) {
    // Open API document in new window or modal
    window.open('https://einvoice.example.com/document/' + didoxId, '_blank');
}

function importDocument(didoxId) {
    if (confirm('Import this document from DIDOX API to local database?')) {
        fetch(<?= json_encode(Url::to(['/admin/didox/import'])) ?>, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: new URLSearchParams({
                didox_id: didoxId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadDocuments();
                alert('Document imported successfully');
            } else {
                alert('Import failed: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Import error:', error);
            alert('Error importing document: ' + error.message);
        });
    }
}
</script>

<style>
.table th {
    background-color: #f4f4f4;
    font-weight: bold;
}

.btn-group .btn {
    margin-right: 2px;
}

.btn-group .btn:last-child {
    margin-right: 0;
}

.info-box {
    display: block;
    min-height: 90px;
    background: #fff;
    width: 100%;
    box-shadow: 0 1px 3px rgba(0,0,0,0.12), 0 1px 2px rgba(0,0,0,0.24);
    border-radius: 2px;
    margin-bottom: 15px;
}

.info-box-icon {
    border-top-left-radius: 2px;
    border-top-right-radius: 0;
    border-bottom-right-radius: 0;
    border-bottom-left-radius: 2px;
    display: block;
    float: left;
    height: 90px;
    width: 90px;
    text-align: center;
    font-size: 45px;
    line-height: 90px;
    background: rgba(0,0,0,0.2);
}

.info-box-icon > i {
    color: #fff;
}

.info-box-content {
    padding: 5px 10px;
    margin-left: 90px;
}

.info-box-text {
    text-transform: uppercase;
    font-weight: 700;
    font-size: 13px;
}

.info-box-number {
    display: block;
    font-weight: 700;
    font-size: 18px;
}

.progress {
    background: rgba(0,0,0,0.2);
    margin: 5px -10px 5px -10px;
    height: 2px;
}

.progress-description {
    font-size: 12px;
    color: #999;
}

.bg-aqua {
    background-color: #00c0ef !important;
}
</style> 