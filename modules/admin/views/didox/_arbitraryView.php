<?php
/* @var $this yii\web\View */
/* @var $model app\models\didox\DidoxDocument */

use yii\helpers\Html;
use yii\widgets\DetailView;
?>

<!-- Arbitrary Contract Details -->
<div class="arbitrary-view-section">
    <?= DetailView::widget([
        'model' => $model,
        'options' => ['class' => 'table table-striped table-bordered detail-view'],
        'attributes' => [
            // === BASIC CONTRACT INFORMATION ===
            [
                'label' => false,
                'format' => 'raw',
                'value' => '<h4 style="margin-top: 20px; margin-bottom: 10px; color: #28a745; border-bottom: 2px solid #28a745; padding-bottom: 5px;"><i class="fa fa-file-text-o"></i> Основная информация о договоре</h4>'
            ],
            [
                'label' => 'Номер документа',
                'value' => $model->arbitrary->document_no ?? '',
            ],
            [
                'label' => 'Дата документа',
                'value' => $model->arbitrary->document_date ?? '',
                'format' => 'date',
            ],
            [
                'label' => 'Наименование документа',
                'value' => $model->arbitrary->document_name ?? '',
            ],
            [
                'label' => 'Номер договора',
                'value' => $model->arbitrary->contract_no ?? '',
            ],
            [
                'label' => 'Дата договора',
                'value' => $model->arbitrary->contract_date ?? '',
                'format' => 'date',
            ],
            
            // === SELLER INFORMATION ===
            [
                'label' => false,
                'format' => 'raw',
                'value' => '<h4 style="margin-top: 20px; margin-bottom: 10px; color: #28a745; border-bottom: 2px solid #28a745; padding-bottom: 5px;"><i class="fa fa-building"></i> Информация о поставщике</h4>'
            ],
            [
                'label' => 'ИНН поставщика',
                'value' => $model->arbitrary->seller_tin ?? '',
            ],
            [
                'label' => 'Наименование поставщика',
                'value' => $model->arbitrary->seller_name ?? '',
            ],
            [
                'label' => 'Адрес поставщика',
                'value' => $model->arbitrary->seller_address ?? '',
            ],
            [
                'label' => 'Код филиала поставщика',
                'value' => $model->arbitrary->seller_branch_code ?? '',
            ],
            [
                'label' => 'Наименование филиала поставщика',
                'value' => $model->arbitrary->seller_branch_name ?? '',
            ],
            
            // === BUYER INFORMATION ===
            [
                'label' => false,
                'format' => 'raw',
                'value' => '<h4 style="margin-top: 20px; margin-bottom: 10px; color: #28a745; border-bottom: 2px solid #28a745; padding-bottom: 5px;"><i class="fa fa-user"></i> Информация о покупателе</h4>'
            ],
            [
                'label' => 'ИНН/ПИНФЛ покупателя',
                'value' => $model->arbitrary->buyer_tin ?? '',
            ],
            [
                'label' => 'Наименование покупателя',
                'value' => $model->arbitrary->buyer_name ?? '',
            ],
            [
                'label' => 'Адрес покупателя',
                'value' => $model->arbitrary->buyer_address ?? '',
            ],
            [
                'label' => 'Код филиала покупателя',
                'value' => $model->arbitrary->buyer_branch_code ?? '',
            ],
            [
                'label' => 'Наименование филиала покупателя',
                'value' => $model->arbitrary->buyer_branch_name ?? '',
            ],
        ],
    ]) ?>
</div>

<!-- PDF Information Section -->
<?php if ($model->arbitrary && $model->arbitrary->pdf_file_content): ?>
<div class="row" style="margin-top: 20px;">
    <div class="col-md-12">
        <div class="box box-success">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-file-pdf-o"></i> PDF Договор</h3>
                <div class="box-tools pull-right">
                    <button type="button" class="btn btn-primary btn-sm" onclick="downloadPdf()">
                        <i class="fa fa-download"></i> Скачать PDF
                    </button>
                    <button type="button" class="btn btn-info btn-sm" onclick="showPdfPreview()">
                        <i class="fa fa-eye"></i> Предварительный просмотр
                    </button>
                </div>
            </div>
            <div class="box-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-condensed">
                            <tr>
                                <th>Имя файла:</th>
                                <td><?= Html::encode($model->arbitrary->pdf_file_name ?: 'contract.pdf') ?></td>
                            </tr>
                            <tr>
                                <th>Размер файла:</th>
                                <td><?= Yii::$app->formatter->asShortSize($model->arbitrary->pdf_file_size ?: 0) ?></td>
                            </tr>
                            <tr>
                                <th>Создан:</th>
                                <td><?= Yii::$app->formatter->asDatetime($model->arbitrary->created_at) ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <div class="alert alert-info">
                            <h5><i class="fa fa-info-circle"></i> О PDF документе</h5>
                            <p>PDF файл автоматически генерируется при создании произвольного договора и отправляется в DIDOX как составная часть документа.</p>
                        </div>
                    </div>
                </div>
                
                <!-- PDF Preview Container -->
                <div id="pdf-preview-container" style="display: none; margin-top: 20px;">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <h4>Предварительный просмотр PDF</h4>
                            <button type="button" class="btn btn-xs btn-default pull-right" onclick="hidePdfPreview()">
                                <i class="fa fa-times"></i> Закрыть
                            </button>
                        </div>
                        <div class="panel-body">
                            <div style="text-align: center;">
                                <iframe id="pdf-preview-frame" 
                                        style="width: 100%; height: 600px; border: 1px solid #ddd;"
                                        src="">
                                </iframe>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php else: ?>
<div class="row" style="margin-top: 20px;">
    <div class="col-md-12">
        <div class="box box-warning">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-exclamation-triangle"></i> PDF Документ</h3>
            </div>
            <div class="box-body">
                <p class="text-muted">PDF документ не был сгенерирован для этого договора.</p>
                <div class="alert alert-info">
                    <h5><i class="fa fa-lightbulb-o"></i> Примечание</h5>
                    <p>PDF документ автоматически создается при сохранении произвольного договора. Если PDF отсутствует, возможно договор был создан некорректно или произошла ошибка при генерации.</p>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Associated Order Section -->
<?php if ($model->order_id): ?>
<div class="row" style="margin-top: 20px;">
    <div class="col-md-12">
        <div class="box box-info">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-shopping-cart"></i> Связанный заказ</h3>
                <div class="box-tools pull-right">
                    <?= Html::a('<i class="fa fa-external-link"></i> Просмотреть заказ', 
                        ['/admin/order/view', 'id' => $model->order_id], 
                        ['class' => 'btn btn-primary btn-sm', 'target' => '_blank']) ?>
                </div>
            </div>
            <div class="box-body">
                <div class="alert alert-info">
                    <h5><i class="fa fa-link"></i> Договор создан на основе заказа</h5>
                    <p>Этот произвольный договор был автоматически создан на основе заказа <strong>#<?= $model->order_id ?></strong>.</p>
                    <p>Все данные договора (покупатель, продавец, условия) были заполнены автоматически из информации заказа.</p>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
// PDF handling functions for arbitrary contracts
function downloadPdf() {
    <?php if ($model->arbitrary && $model->arbitrary->pdf_file_content): ?>
    try {
        // Create blob from base64 content
        const base64Data = "<?= $model->arbitrary->pdf_file_content ?>";
        const byteCharacters = atob(base64Data);
        const byteNumbers = new Array(byteCharacters.length);
        for (let i = 0; i < byteCharacters.length; i++) {
            byteNumbers[i] = byteCharacters.charCodeAt(i);
        }
        const byteArray = new Uint8Array(byteNumbers);
        const blob = new Blob([byteArray], {type: 'application/pdf'});
        
        // Create download link
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = "<?= Html::encode($model->arbitrary->pdf_file_name ?: 'contract.pdf') ?>";
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(url);
        
        console.log('PDF download initiated');
    } catch (error) {
        console.error('Error downloading PDF:', error);
        alert('Ошибка при скачивании PDF файла. Попробуйте еще раз.');
    }
    <?php else: ?>
    alert('PDF файл недоступен для скачивания.');
    <?php endif; ?>
}

function showPdfPreview() {
    <?php if ($model->arbitrary && $model->arbitrary->pdf_file_content): ?>
    try {
        const base64Data = "<?= $model->arbitrary->pdf_file_content ?>";
        const pdfUrl = "data:application/pdf;base64," + base64Data;
        
        document.getElementById('pdf-preview-frame').src = pdfUrl;
        document.getElementById('pdf-preview-container').style.display = 'block';
        
        // Scroll to preview
        document.getElementById('pdf-preview-container').scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
        
        console.log('PDF preview shown');
    } catch (error) {
        console.error('Error showing PDF preview:', error);
        alert('Ошибка при отображении предварительного просмотра PDF.');
    }
    <?php else: ?>
    alert('PDF файл недоступен для предварительного просмотра.');
    <?php endif; ?>
}

function hidePdfPreview() {
    document.getElementById('pdf-preview-container').style.display = 'none';
    document.getElementById('pdf-preview-frame').src = '';
}
</script> 