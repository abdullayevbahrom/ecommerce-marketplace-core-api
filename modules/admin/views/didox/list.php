<?php
use yii\helpers\Html;

$this->title = 'DIDOX API Documents';
$this->params['breadcrumbs'][] = ['label' => 'DIDOX Documents', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$statuses = [
    0 => 'Черновик',
    1 => 'Ожидает подписи партнера',
    2 => 'Ожидает вашей подписи',
    3 => 'Подписан',
    4 => 'Отказ от подписи',
    5 => 'Удален',
    6 => 'Ожидает подписи агента',
    8 => 'Подписан доверенным лицом',
    40 => 'Не действительный',
    55 => 'Черновик удален',
    60 => 'Ожидает подписи агента',
    110 => 'Отправлено',
    120 => 'Отменено',
    130 => 'Отказано (отв. лицом)',
    140 => 'Принято (отв. лицом)',
    150 => 'Груз возвращен (отв.лицом)',
    160 => 'Доставлено получателю',
    190 => 'Груз возвращен (отв.лицом)',
];

$statusColors = [
    0 => 'bg-gray',      1 => 'bg-yellow',    2 => 'bg-orange',    3 => 'bg-green',
    4 => 'bg-red',       5 => 'bg-red',       6 => 'bg-blue',      8 => 'bg-green',
    40 => 'bg-red',      55 => 'bg-red',      60 => 'bg-blue',     110 => 'bg-green',
    120 => 'bg-red',     130 => 'bg-red',     140 => 'bg-green',   150 => 'bg-orange',
    160 => 'bg-green',   190 => 'bg-orange',
];
?>

<div class="content-wrapper">
    <section class="content-header">
        <h1><?= Html::encode($this->title) ?></h1>
        <ol class="breadcrumb">
            <li><a href="<?= Yii::$app->urlManager->createUrl(['/admin/']) ?>"><i class="fa fa-dashboard"></i> Home</a></li>
            <li><a href="<?= Yii::$app->urlManager->createUrl(['/admin/didox']) ?>">DIDOX Documents</a></li>
            <li class="active">API Documents</li>
        </ol>
    </section>

    <section class="content">
        <?php if (Yii::$app->session->hasFlash('didox_error')): ?>
            <div class="callout callout-danger">
                <?= Yii::$app->session->getFlash('didox_error') ?>
            </div>
        <?php endif; ?>

        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">DIDOX Documents from API</h3>
                <div class="box-tools pull-right">
                    <a href="<?= Yii::$app->urlManager->createUrl(['/admin/didox']) ?>" class="btn btn-default btn-sm">
                        <i class="fa fa-arrow-left"></i> Back to DIDOX
                    </a>
                </div>
            </div>

            <!-- Filters -->
            <div class="box-body">
                <form method="GET" class="form-inline" style="margin-bottom: 20px;">
                    <div class="form-group">
                        <label>Status:</label>
                        <select name="status" class="form-control">
                            <option value="">All Statuses</option>
                            <?php foreach ($statuses as $code => $label): ?>
                                <option value="<?= $code ?>" <?= $status == $code ? 'selected' : '' ?>>
                                    <?= $label ?> (<?= $code ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Per Page:</label>
                        <select name="limit" class="form-control">
                            <option value="10" <?= $limit == 10 ? 'selected' : '' ?>>10</option>
                            <option value="20" <?= $limit == 20 ? 'selected' : '' ?>>20</option>
                            <option value="50" <?= $limit == 50 ? 'selected' : '' ?>>50</option>
                            <option value="100" <?= $limit == 100 ? 'selected' : '' ?>>100</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-filter"></i> Filter
                    </button>
                </form>

                <?php if (isset($result['success']) && $result['success']): ?>
                    <?php $documents = isset($result['data']['data']) ? $result['data']['data'] : []; ?>
                    <?php $total = isset($result['data']['total']) ? $result['data']['total'] : 0; ?>
                    
                    <div class="info-box">
                        <span class="info-box-icon bg-blue"><i class="fa fa-file-text"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Total Documents</span>
                            <span class="info-box-number"><?= $total ?></span>
                        </div>
                    </div>

                    <?php if (!empty($documents)): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>DIDOX ID</th>
                                        <th>Document Number</th>
                                        <th>Type</th>
                                        <th>Partner</th>
                                        <th>Total Sum</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($documents as $doc): ?>
                                        <tr>
                                            <td>
                                                <code title="<?= Html::encode($doc['doc_id']) ?>">
                                                    <?= Html::encode(substr($doc['doc_id'], 0, 12)) ?>...
                                                </code>
                                            </td>
                                            <td><?= Html::encode(isset($doc['name']) ? $doc['name'] : 'N/A') ?></td>
                                            <td>
                                                <span class="label label-info">
                                                    <?= Html::encode(isset($doc['doctype']) ? $doc['doctype'] : 'N/A') ?>
                                                </span>
                                            </td>
                                            <td>
                                                <strong><?= Html::encode(isset($doc['partnerCompany']) ? $doc['partnerCompany'] : 'N/A') ?></strong><br>
                                                <small>TIN: <?= Html::encode(isset($doc['partnerTin']) ? $doc['partnerTin'] : 'N/A') ?></small>
                                            </td>
                                            <td>
                                                <strong><?= number_format(isset($doc['total_sum']) ? $doc['total_sum'] : 0, 2) ?></strong><br>
                                                <?php if (!empty($doc['total_vat_sum'])): ?>
                                                    <small>VAT: <?= number_format($doc['total_vat_sum'], 2) ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php 
                                                $statusCode = isset($doc['doc_status']) ? $doc['doc_status'] : 0;
                                                $statusLabel = isset($statuses[$statusCode]) ? $statuses[$statusCode] : 'Unknown';
                                                $statusColor = isset($statusColors[$statusCode]) ? $statusColors[$statusCode] : 'bg-gray';
                                                ?>
                                                <small class="label <?= $statusColor ?>">
                                                    <?= $statusLabel ?>
                                                </small>
                                                <br><small><?= $statusCode ?></small>
                                            </td>
                                            <td>
                                                <strong><?= Html::encode(isset($doc['doc_date']) ? $doc['doc_date'] : 'N/A') ?></strong><br>
                                                <small>Updated: <?= Html::encode(isset($doc['updated']) ? $doc['updated'] : 'N/A') ?></small>
                                            </td>
                                            <td>
                                                <div class="btn-group-vertical btn-group-xs">
                                                    <button type="button" class="btn btn-info btn-xs" 
                                                            onclick="viewDidoxDocument('<?= Html::encode($doc['doc_id']) ?>')">
                                                        <i class="fa fa-eye"></i> View
                                                    </button>
                                                    
                                                    <?php if (in_array($statusCode, [1, 2, 6, 60])): ?>
                                                        <button type="button" class="btn btn-success btn-xs" 
                                                                onclick="signDidoxDocumentFromList('<?= Html::encode($doc['doc_id']) ?>')">
                                                            <i class="fa fa-edit"></i> Sign
                                                        </button>
                                                    <?php endif; ?>
                                                    
                                                    <?php if (in_array($statusCode, [0, 1, 2, 6, 60])): ?>
                                                        <button type="button" class="btn btn-danger btn-xs" 
                                                                onclick="cancelDidoxDocumentFromList('<?= Html::encode($doc['doc_id']) ?>')">
                                                            <i class="fa fa-times"></i> Cancel
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total > $limit): ?>
                            <div class="text-center">
                                <?php
                                $totalPages = ceil($total / $limit);
                                $currentPage = $page;
                                ?>
                                <ul class="pagination">
                                    <?php if ($currentPage > 1): ?>
                                        <li>
                                            <a href="?page=<?= $currentPage - 1 ?>&limit=<?= $limit ?>&status=<?= $status ?>">
                                                <i class="fa fa-angle-left"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++): ?>
                                        <li class="<?= $i == $currentPage ? 'active' : '' ?>">
                                            <a href="?page=<?= $i ?>&limit=<?= $limit ?>&status=<?= $status ?>"><?= $i ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($currentPage < $totalPages): ?>
                                        <li>
                                            <a href="?page=<?= $currentPage + 1 ?>&limit=<?= $limit ?>&status=<?= $status ?>">
                                                <i class="fa fa-angle-right"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="callout callout-info text-center">
                            <h4><i class="fa fa-info-circle"></i> No Documents Found</h4>
                            <p>No DIDOX documents match the current criteria.</p>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="callout callout-danger">
                        <h4><i class="fa fa-exclamation-triangle"></i> Error</h4>
                        <p>Failed to load DIDOX documents: <?= Html::encode(isset($result['error']) ? $result['error'] : 'Unknown error') ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
</div>

<!-- Document Details Modal -->
<div class="modal fade" id="documentDetailsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">DIDOX Document Details</h4>
            </div>
            <div class="modal-body">
                <div id="document-details-content">
                    <p>Loading document details...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function viewDidoxDocument(docId) {
    $('#documentDetailsModal').modal('show');
    $('#document-details-content').html('<p>Loading document details...</p>');
    
    // Here you would typically make an AJAX call to get document details
    // For now, just show the document ID
    $('#document-details-content').html(
        '<div class="form-group">' +
        '<label>Document ID:</label>' +
        '<p><code>' + docId + '</code></p>' +
        '</div>' +
        '<div class="callout callout-info">' +
        '<strong>Note:</strong> Detailed document viewing functionality can be implemented based on DIDOX API endpoints.' +
        '</div>'
    );
}

function signDidoxDocumentFromList(docId) {
    if (confirm('Do you want to sign this DIDOX document?')) {
        // Redirect to signing process or implement AJAX signing
        alert('Signing functionality for document: ' + docId + '\n\nThis would typically integrate with E-IMZO for digital signatures.');
    }
}

function cancelDidoxDocumentFromList(docId) {
    if (confirm('Are you sure you want to cancel this DIDOX document?')) {
        // Implement cancellation logic
        alert('Cancel functionality for document: ' + docId + '\n\nThis would call the DIDOX API to cancel the document.');
    }
}
</script> 