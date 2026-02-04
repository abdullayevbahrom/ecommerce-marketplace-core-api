<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $linkedCodes array */
/* @var $searchResults array */
/* @var $searchQuery string */
/* @var $currentPage int */
/* @var $language string */

$this->title = 'ИКПУ Management';
$this->params['breadcrumbs'][] = ['label' => 'DIDOX', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<style>
.ikpu-section {
    background: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 20px;
    margin: 20px 0;
}

.ikpu-code {
    background: #e9ecef;
    padding: 8px 12px;
    border-radius: 4px;
    margin: 5px;
    display: inline-block;
    border: 1px solid #dee2e6;
}

.ikpu-code .code {
    font-family: monospace;
    font-weight: bold;
    color: #007bff;
}

.ikpu-code .name {
    margin-left: 10px;
    color: #495057;
}

.ikpu-code .remove-btn {
    margin-left: 10px;
    color: #dc3545;
    cursor: pointer;
    font-size: 18px;
}

.ikpu-code .remove-btn:hover {
    color: #a71d2a;
}

.search-result {
    background: #fff;
    border: 1px solid #dee2e6;
    padding: 10px;
    margin: 5px 0;
    border-radius: 4px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.search-result .info {
    flex: 1;
}

.search-result .code {
    font-family: monospace;
    font-weight: bold;
    color: #007bff;
    font-size: 14px;
}

.search-result .name {
    color: #495057;
    margin-top: 3px;
    font-size: 13px;
}

.search-result .add-btn {
    background: #28a745;
    color: white;
    border: none;
    padding: 6px 12px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 12px;
}

.search-result .add-btn:hover {
    background: #218838;
}

.search-result .add-btn:disabled {
    background: #6c757d;
    cursor: not-allowed;
}

.alert-custom {
    padding: 10px 15px;
    margin: 10px 0;
    border-radius: 4px;
    font-size: 13px;
}

.alert-success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-error {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.search-form, .custom-ikpu-form {
    background: #fff;
    padding: 15px;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    margin-bottom: 20px;
}

.custom-ikpu-form input:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.custom-ikpu-form .text-muted {
    font-size: 11px;
    margin-top: 3px;
}

.custom-ikpu-form label span {
    color: #dc3545;
    font-weight: bold;
}

.pagination-simple {
    text-align: center;
    margin: 20px 0;
}

.pagination-simple button {
    margin: 0 5px;
    padding: 8px 16px;
    border: 1px solid #007bff;
    background: #007bff;
    color: white;
    border-radius: 4px;
    cursor: pointer;
}

.pagination-simple button:disabled {
    background: #6c757d;
    border-color: #6c757d;
    cursor: not-allowed;
}

.pagination-simple button:hover:not(:disabled) {
    background: #0056b3;
    border-color: #0056b3;
}

.loading {
    text-align: center;
    padding: 20px;
    color: #6c757d;
}

.empty-state {
    text-align: center;
    padding: 40px;
    color: #6c757d;
}

.empty-state .icon {
    font-size: 48px;
    margin-bottom: 15px;
}
</style>

<div class="content-wrapper">
    <section class="content-header">
        <h1>ИКПУ Management</h1>
        <ol class="breadcrumb">
            <li><a href="<?= Yii::$app->urlManager->createUrl(['/admin/']) ?>"><i class="fa fa-dashboard"></i> Home</a></li>
            <li><a href="<?= Yii::$app->urlManager->createUrl(['/admin/didox']) ?>">DIDOX</a></li>
            <li class="active">ИКПУ Management</li>
        </ol>
    </section>

    <section class="content">
        <div class="box box-info">
            <div class="box-header with-border">
                <h3 class="box-title">Управление ИКПУ кодами</h3>
                <div class="box-tools pull-right">
                    <?= Html::a('<i class="fa fa-arrow-left"></i> Назад к документам', ['index'], ['class' => 'btn btn-default btn-sm']) ?>
                </div>
            </div>
            <div class="box-body">
                <!-- Status Messages -->
                <div id="status-messages"></div>

                <!-- Manual ИКПУ Check Section -->
                <div class="ikpu-section">
                    <h4><i class="fa fa-search-plus"></i> Проверка ИКПУ кода</h4>
                    <p class="text-muted">Введите номер ИКПУ кода для получения детальной информации</p>
                    
                    <div class="search-form">
                        <div class="row">
                            <div class="col-md-5">
                                <label for="ikpu-check-input">Номер ИКПУ кода:</label>
                                <input type="text" id="ikpu-check-input" class="form-control" 
                                       placeholder="Например: 11703002001000000" 
                                       pattern="[0-9]{17}" 
                                       title="ИКПУ код должен содержать 17 цифр">
                            </div>
                            <div class="col-md-3">
                                <label for="check-language-select">Язык:</label>
                                <select id="check-language-select" class="form-control">
                                    <option value="ru">Русский</option>
                                    <option value="uz">Uzbek</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label>&nbsp;</label><br>
                                <button type="button" id="check-ikpu-btn" class="btn btn-success">
                                    <i class="fa fa-info-circle"></i> Проверить статус
                                </button>
                                <button type="button" id="add-checked-ikpu-btn" class="btn btn-primary" style="display: none;">
                                    <i class="fa fa-plus"></i> Добавить к профилю
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- ИКПУ Check Result -->
                    <div id="ikpu-check-result" style="display: none;">
                        <div class="search-result" style="background: #f8f9fa; border: 2px solid #28a745;">
                            <div class="info">
                                <div class="code" id="checked-ikpu-code"></div>
                                <div class="name" id="checked-ikpu-name"></div>
                                <div id="checked-ikpu-details" style="margin-top: 10px; font-size: 12px; color: #6c757d;"></div>
                            </div>
                            <div id="check-result-actions">
                                <!-- Actions will be added dynamically -->
                            </div>
                        </div>
                    </div>

                    <!-- Check Loading indicator -->
                    <div id="check-loading-indicator" class="loading" style="display: none;">
                        <i class="fa fa-spinner fa-spin"></i> Проверка ИКПУ кода...
                    </div>
                </div>

                <!-- Linked ИКПУ Codes Section -->
                <div class="ikpu-section">
                    <h4><i class="fa fa-link"></i> Привязанные ИКПУ коды</h4>
                    <p class="text-muted">ИКПУ коды, привязанные к вашему профилю в DIDOX</p>
                    
                    <div id="linked-codes-container">
                        <?php if (empty($linkedCodes)): ?>
                            <div class="empty-state">
                                <div class="icon"><i class="fa fa-info-circle"></i></div>
                                <p>У вас пока нет привязанных ИКПУ кодов</p>
                                <small>Используйте поиск ниже, чтобы найти и добавить нужные коды</small>
                            </div>
                        <?php else: ?>
                            <?php foreach ($linkedCodes as $code): ?>
                                <?php 
                                $classCode = $code['classCode'] ?? '';
                                $className = $code['className'] ?? $code['className_ru'] ?? $code['name'] ?? '';
                                ?>
                                <div class="ikpu-code" data-code="<?= Html::encode($classCode) ?>">
                                    <span class="code"><?= Html::encode($classCode) ?></span>
                                    <span class="name"><?= Html::encode($className) ?></span>
                                    <span class="remove-btn" onclick="removeIkpuCode('<?= Html::encode($classCode) ?>')" title="Удалить">×</span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Add Custom ИКПУ Code Section -->
                <div class="ikpu-section">
                    <h4><i class="fa fa-plus-circle"></i> Добавить пользовательский ИКПУ код</h4>
                    <p class="text-muted">Добавьте ИКПУ код напрямую, если вы знаете точный код и название</p>
                    
                    <div class="custom-ikpu-form">
                        <div class="row">
                            <div class="col-md-4">
                                <label for="custom-ikpu-code">ИКПУ код: <span style="color: red;">*</span></label>
                                <input type="text" id="custom-ikpu-code" class="form-control" 
                                       placeholder="Например: 11703002001000000" 
                                       pattern="[0-9]{17}" 
                                       maxlength="17"
                                       title="ИКПУ код должен содержать 17 цифр">
                                <small class="text-muted">17-значный цифровой код</small>
                            </div>
                            <div class="col-md-5">
                                <label for="custom-ikpu-name">Название ИКПУ: <span style="color: red;">*</span></label>
                                <input type="text" id="custom-ikpu-name" class="form-control" 
                                       placeholder="Введите название ИКПУ кода"
                                       maxlength="255">
                                <small class="text-muted">Описательное название товарной группы</small>
                            </div>
                            <div class="col-md-3">
                                <label>&nbsp;</label><br>
                                <button type="button" id="add-custom-ikpu-btn" class="btn btn-primary btn-block">
                                    <i class="fa fa-plus"></i> Добавить к профилю
                                </button>
                                <button type="button" id="clear-custom-form-btn" class="btn btn-default btn-sm" style="margin-top: 5px; width: 100%;">
                                    <i class="fa fa-eraser"></i> Очистить
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Custom form loading indicator -->
                    <div id="custom-form-loading" class="loading" style="display: none; margin-top: 10px;">
                        <i class="fa fa-spinner fa-spin"></i> Добавление ИКПУ кода к профилю...
                    </div>
                </div>

                <!-- Search ИКПУ Codes Section -->
                <div class="ikpu-section">
                    <h4><i class="fa fa-search"></i> Поиск ИКПУ кодов</h4>
                    <p class="text-muted">Найдите и добавьте нужные ИКПУ коды к вашему профилю</p>
                    
                    <!-- Search Form -->
                    <div class="search-form">
                        <div class="row">
                            <div class="col-md-4">
                                <label for="search-input">Поиск по коду или названию:</label>
                                <input type="text" id="search-input" class="form-control" 
                                       placeholder="Введите код или название ИКПУ" 
                                       value="<?= Html::encode($searchQuery) ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="language-select">Язык:</label>
                                <select id="language-select" class="form-control">
                                    <option value="ru" <?= $language === 'ru' ? 'selected' : '' ?>>Русский</option>
                                    <option value="uz" <?= $language === 'uz' ? 'selected' : '' ?>>Uzbek</option>
                                </select>
                            </div>
                            <div class="col-md-5">
                                <label>&nbsp;</label><br>
                                <button type="button" id="search-btn" class="btn btn-primary">
                                    <i class="fa fa-search"></i> Поиск
                                </button>
                                <button type="button" id="clear-btn" class="btn btn-default">
                                    <i class="fa fa-times"></i> Очистить
                                </button>
                                <button type="button" id="show-all-btn" class="btn btn-info">
                                    <i class="fa fa-list"></i> Показать все
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Search Results -->
                    <div id="search-results-container">
                        <?php if (!empty($searchResults)): ?>
                            <?php foreach ($searchResults as $result): ?>
                                <div class="search-result">
                                    <div class="info">
                                        <div class="code"><?= Html::encode($result['classCode'] ?? $result['code'] ?? '') ?></div>
                                        <div class="name"><?= Html::encode($result['className'] ?? $result['name'] ?? '') ?></div>
                                    </div>
                                    <button type="button" class="add-btn" 
                                            onclick="addIkpuCode('<?= Html::encode($result['classCode'] ?? $result['code'] ?? '') ?>', '<?= Html::encode($result['className'] ?? $result['name'] ?? '') ?>')">
                                        <i class="fa fa-plus"></i> Добавить
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        <?php elseif (!empty($searchQuery)): ?>
                            <div class="empty-state">
                                <div class="icon"><i class="fa fa-search"></i></div>
                                <p>По запросу "<?= Html::encode($searchQuery) ?>" ничего не найдено</p>
                                <small>Попробуйте изменить поисковый запрос</small>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Loading indicator -->
                    <div id="loading-indicator" class="loading" style="display: none;">
                        <i class="fa fa-spinner fa-spin"></i> Загрузка...
                    </div>

                    <!-- Pagination -->
                    <div id="pagination-container" class="pagination-simple" style="display: none;">
                        <button type="button" id="prev-page" onclick="changePage(currentPage - 1)">
                            <i class="fa fa-chevron-left"></i> Предыдущая
                        </button>
                        <span id="page-info">Страница <span id="current-page-num">1</span></span>
                        <button type="button" id="next-page" onclick="changePage(currentPage + 1)">
                            Следующая <i class="fa fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
let currentPage = <?= $currentPage ?>;
let currentLanguage = '<?= $language ?>';
let currentSearch = '<?= Html::encode($searchQuery) ?>';
let isLoading = false;

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    setupEventHandlers();
});

function setupEventHandlers() {
    // Search button
    document.getElementById('search-btn').addEventListener('click', performSearch);
    
    // Clear button
    document.getElementById('clear-btn').addEventListener('click', clearSearch);
    
    // Show all button
    document.getElementById('show-all-btn').addEventListener('click', showAll);
    
    // Enter key in search input
    document.getElementById('search-input').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            performSearch();
        }
    });
    
    // Language change
    document.getElementById('language-select').addEventListener('change', function() {
        currentLanguage = this.value;
        if (currentSearch || document.getElementById('search-results-container').children.length > 0) {
            performSearch();
        }
    });

    // ИКПУ Check functionality
    document.getElementById('check-ikpu-btn').addEventListener('click', checkIkpuStatus);
    
    // Enter key in ИКПУ check input
    document.getElementById('ikpu-check-input').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            checkIkpuStatus();
        }
    });

    // Add checked ИКПУ to profile
    document.getElementById('add-checked-ikpu-btn').addEventListener('click', addCheckedIkpuToProfile);
    
    // Custom IKPU form handlers
    document.getElementById('add-custom-ikpu-btn').addEventListener('click', addCustomIkpu);
    document.getElementById('clear-custom-form-btn').addEventListener('click', clearCustomForm);
    
    // Enter key in custom IKPU inputs
    document.getElementById('custom-ikpu-code').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            document.getElementById('custom-ikpu-name').focus();
        }
    });
    
    document.getElementById('custom-ikpu-name').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            addCustomIkpu();
        }
    });
    
    // Auto-format ИКПУ code input (only digits)
    document.getElementById('custom-ikpu-code').addEventListener('input', function(e) {
        // Remove non-digits
        let value = e.target.value.replace(/\D/g, '');
        // Limit to 17 digits
        if (value.length > 17) {
            value = value.substring(0, 17);
        }
        e.target.value = value;
    });
}

function performSearch() {
    if (isLoading) return;
    
    const searchInput = document.getElementById('search-input');
    const query = searchInput.value.trim();
    
    currentSearch = query;
    currentPage = 1;
    
    if (query === '') {
        showMessage('Введите поисковый запрос', 'error');
        return;
    }
    
    searchIkpuCodes(query, currentPage, currentLanguage);
}

function clearSearch() {
    document.getElementById('search-input').value = '';
    document.getElementById('search-results-container').innerHTML = '';
    document.getElementById('pagination-container').style.display = 'none';
    currentSearch = '';
    currentPage = 1;
}

function showAll() {
    if (isLoading) return;
    
    document.getElementById('search-input').value = '';
    currentSearch = '';
    currentPage = 1;
    
    searchIkpuCodes('', currentPage, currentLanguage, true);
}

function searchIkpuCodes(query, page, lang, showAll = false) {
    if (isLoading) return;
    
    isLoading = true;
    showLoading(true);
    
    const params = new URLSearchParams({
        search: query,
        page: page,
        lang: lang
    });
    
    if (showAll) {
        params.set('show_all', '1');
    }
    
    fetch(`<?= Yii::$app->urlManager->createUrl(['/admin/didox/ikpu-search']) ?>?${params.toString()}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displaySearchResults(data.data);
                updatePagination(data.data);
            } else {
                showMessage(data.error || 'Ошибка поиска', 'error');
                document.getElementById('search-results-container').innerHTML = getEmptyState('Ошибка при выполнении поиска');
            }
        })
        .catch(error => {
            console.error('Search error:', error);
            showMessage('Ошибка сети при поиске ИКПУ кодов', 'error');
            document.getElementById('search-results-container').innerHTML = getEmptyState('Ошибка сети');
        })
        .finally(() => {
            isLoading = false;
            showLoading(false);
        });
}

function displaySearchResults(results) {
    const container = document.getElementById('search-results-container');
    
    if (!results || results.length === 0) {
        container.innerHTML = getEmptyState('По вашему запросу ничего не найдено');
        return;
    }
    
    let html = '';
    results.forEach(result => {
        const code = result.classCode || result.code || '';
        const name = result.className || result.className_ru || result.name || result.name_ru || '';
        
        html += `
            <div class="search-result">
                <div class="info">
                    <div class="code">${escapeHtml(code)}</div>
                    <div class="name">${escapeHtml(name)}</div>
                </div>
                <button type="button" class="add-btn" 
                        onclick="addIkpuCode('${escapeHtml(code)}', '${escapeHtml(name)}')">
                    <i class="fa fa-plus"></i> Добавить
                </button>
            </div>
        `;
    });
    
    container.innerHTML = html;
}

function updatePagination(results) {
    const paginationContainer = document.getElementById('pagination-container');
    const hasResults = results && results.length > 0;
    
    if (hasResults) {
        paginationContainer.style.display = 'block';
        document.getElementById('current-page-num').textContent = currentPage;
        
        // Enable/disable pagination buttons based on results
        document.getElementById('prev-page').disabled = currentPage <= 1;
        document.getElementById('next-page').disabled = results.length < 20; // Assuming 20 per page
    } else {
        paginationContainer.style.display = 'none';
    }
}

function changePage(newPage) {
    if (newPage < 1 || isLoading) return;
    
    currentPage = newPage;
    searchIkpuCodes(currentSearch, currentPage, currentLanguage, !currentSearch);
}

function addIkpuCode(classCode, className) {
    if (isLoading) return;
    
    isLoading = true;
    
    const formData = new FormData();
    formData.append('classCode', classCode);
    formData.append('className', className);
    
    fetch(`<?= Yii::$app->urlManager->createUrl(['/admin/didox/ikpu-add']) ?>`, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showMessage(data.message || 'ИКПУ код успешно добавлен', 'success');
            addCodeToLinkedList(classCode, className);
        } else {
            showMessage(data.error || 'Ошибка добавления ИКПУ кода', 'error');
        }
    })
    .catch(error => {
        console.error('Add error:', error);
        showMessage('Ошибка сети при добавлении ИКПУ кода', 'error');
    })
    .finally(() => {
        isLoading = false;
    });
}

function removeIkpuCode(classCode) {
    if (isLoading) return;
    
    if (!confirm(`Удалить ИКПУ код ${classCode}?`)) {
        return;
    }
    
    isLoading = true;
    
    const formData = new FormData();
    
    fetch(`<?= Yii::$app->urlManager->createUrl(['/admin/didox/ikpu-remove']) ?>?classCode=${encodeURIComponent(classCode)}`, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showMessage(data.message || 'ИКПУ код успешно удален', 'success');
            removeCodeFromLinkedList(classCode);
        } else {
            showMessage(data.error || 'Ошибка удаления ИКПУ кода', 'error');
        }
    })
    .catch(error => {
        console.error('Remove error:', error);
        showMessage('Ошибка сети при удалении ИКПУ кода', 'error');
    })
    .finally(() => {
        isLoading = false;
    });
}

function addCodeToLinkedList(classCode, className) {
    const container = document.getElementById('linked-codes-container');
    
    // Remove empty state if exists
    const emptyState = container.querySelector('.empty-state');
    if (emptyState) {
        emptyState.remove();
    }
    
    // Create new code element
    const codeElement = document.createElement('div');
    codeElement.className = 'ikpu-code';
    codeElement.setAttribute('data-code', classCode);
    codeElement.innerHTML = `
        <span class="code">${escapeHtml(classCode)}</span>
        <span class="name">${escapeHtml(className)}</span>
        <span class="remove-btn" onclick="removeIkpuCode('${escapeHtml(classCode)}')" title="Удалить">×</span>
    `;
    
    container.appendChild(codeElement);
}

function removeCodeFromLinkedList(classCode) {
    const container = document.getElementById('linked-codes-container');
    const codeElement = container.querySelector(`[data-code="${classCode}"]`);
    
    if (codeElement) {
        codeElement.remove();
    }
    
    // Show empty state if no codes left
    if (container.children.length === 0) {
        container.innerHTML = getEmptyState('У вас пока нет привязанных ИКПУ кодов', 'Используйте поиск ниже, чтобы найти и добавить нужные коды');
    }
}

function showLoading(show) {
    document.getElementById('loading-indicator').style.display = show ? 'block' : 'none';
}

function showMessage(message, type) {
    const container = document.getElementById('status-messages');
    const alertClass = type === 'success' ? 'alert-success' : 'alert-error';
    
    const alertElement = document.createElement('div');
    alertElement.className = `alert-custom ${alertClass}`;
    alertElement.innerHTML = `
        <i class="fa fa-${type === 'success' ? 'check' : 'exclamation-triangle'}"></i> ${escapeHtml(message)}
        <button type="button" style="float: right; background: none; border: none; font-size: 18px; cursor: pointer;" onclick="this.parentElement.remove()">×</button>
    `;
    
    container.appendChild(alertElement);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (alertElement.parentElement) {
            alertElement.remove();
        }
    }, 5000);
}

function getEmptyState(message, subMessage = '') {
    return `
        <div class="empty-state">
            <div class="icon"><i class="fa fa-info-circle"></i></div>
            <p>${escapeHtml(message)}</p>
            ${subMessage ? `<small>${escapeHtml(subMessage)}</small>` : ''}
        </div>
    `;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// ИКПУ Check Functions
let checkedIkpuData = null;

function checkIkpuStatus() {
    if (isLoading) return;
    
    const ikpuInput = document.getElementById('ikpu-check-input');
    const ikpuCode = ikpuInput.value.trim();
    const language = document.getElementById('check-language-select').value;
    
    // Validate ИКПУ code format
    if (!ikpuCode) {
        showMessage('Введите номер ИКПУ кода', 'error');
        return;
    }
    
    if (!/^\d{17}$/.test(ikpuCode)) {
        showMessage('ИКПУ код должен содержать 17 цифр', 'error');
        return;
    }
    
    isLoading = true;
    showCheckLoading(true);
    hideCheckResult();
    
    const params = new URLSearchParams({
        ikpuCode: ikpuCode,
        lang: language
    });
    
    fetch(`<?= Yii::$app->urlManager->createUrl(['/admin/didox/ikpu-check']) ?>?${params.toString()}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayCheckResult(data.data);
            } else {
                showMessage(data.error || 'Ошибка проверки ИКПУ кода', 'error');
                hideCheckResult();
            }
        })
        .catch(error => {
            console.error('Check error:', error);
            showMessage('Ошибка сети при проверке ИКПУ кода', 'error');
            hideCheckResult();
        })
        .finally(() => {
            isLoading = false;
            showCheckLoading(false);
        });
}

function displayCheckResult(ikpuData) {
    if (!ikpuData) {
        showMessage('ИКПУ код не найден', 'error');
        return;
    }
    
    checkedIkpuData = ikpuData;
    
    const code = ikpuData.classCode || ikpuData.code || '';
    const name = ikpuData.className || ikpuData.className_ru || ikpuData.name || ikpuData.name_ru || '';
    
    // Update result display
    document.getElementById('checked-ikpu-code').textContent = code;
    document.getElementById('checked-ikpu-name').textContent = name;
    
    // Build details section
    let details = '';
    if (ikpuData.internationalCode) {
        details += `<strong>Международный код:</strong> ${ikpuData.internationalCode}<br>`;
    }
    if (ikpuData.origin) {
        details += `<strong>Происхождение:</strong> ${ikpuData.origin.name || ikpuData.origin}<br>`;
    }
    if (ikpuData.usePackage) {
        details += `<strong>Использует упаковки:</strong> Да<br>`;
        if (ikpuData.packages && ikpuData.packages.length > 0) {
            details += `<strong>Доступные упаковки:</strong> `;
            const packageNames = ikpuData.packages.map(pkg => pkg.name_ru || pkg.name || pkg.code).join(', ');
            details += packageNames + '<br>';
        }
    } else {
        details += `<strong>Использует упаковки:</strong> Нет<br>`;
    }
    
    document.getElementById('checked-ikpu-details').innerHTML = details;
    
    // Check if already linked to profile
    const isLinked = isIkpuLinkedToProfile(code);
    
    // Update action buttons
    const actionsDiv = document.getElementById('check-result-actions');
    if (isLinked) {
        actionsDiv.innerHTML = `
            <button type="button" class="btn btn-warning btn-sm" onclick="removeIkpuCode('${escapeHtml(code)}')">
                <i class="fa fa-unlink"></i> Отвязать от профиля
            </button>
        `;
        document.getElementById('add-checked-ikpu-btn').style.display = 'none';
    } else {
        actionsDiv.innerHTML = '';
        document.getElementById('add-checked-ikpu-btn').style.display = 'inline-block';
    }
    
    // Show result
    document.getElementById('ikpu-check-result').style.display = 'block';
    
    showMessage('ИКПУ код найден и проверен', 'success');
}

function hideCheckResult() {
    document.getElementById('ikpu-check-result').style.display = 'none';
    document.getElementById('add-checked-ikpu-btn').style.display = 'none';
    checkedIkpuData = null;
}

function showCheckLoading(show) {
    document.getElementById('check-loading-indicator').style.display = show ? 'block' : 'none';
}

function addCheckedIkpuToProfile() {
    if (!checkedIkpuData) {
        showMessage('Нет данных для добавления', 'error');
        return;
    }
    
    const code = checkedIkpuData.classCode || checkedIkpuData.code || '';
    const name = checkedIkpuData.className || checkedIkpuData.className_ru || checkedIkpuData.name || checkedIkpuData.name_ru || '';
    
    addIkpuCode(code, name);
}

function isIkpuLinkedToProfile(ikpuCode) {
    const linkedContainer = document.getElementById('linked-codes-container');
    const linkedElement = linkedContainer.querySelector(`[data-code="${ikpuCode}"]`);
    return linkedElement !== null;
}

// Custom IKPU form functions
function addCustomIkpu() {
    const codeInput = document.getElementById('custom-ikpu-code');
    const nameInput = document.getElementById('custom-ikpu-name');
    const loadingDiv = document.getElementById('custom-form-loading');
    
    const code = codeInput.value.trim();
    const name = nameInput.value.trim();
    
    // Validation
    if (!code) {
        showMessage('Введите ИКПУ код', 'error');
        codeInput.focus();
        return;
    }
    
    if (code.length !== 17) {
        showMessage('ИКПУ код должен содержать 17 цифр', 'error');
        codeInput.focus();
        return;
    }
    
    if (!/^\d{17}$/.test(code)) {
        showMessage('ИКПУ код должен содержать только цифры', 'error');
        codeInput.focus();
        return;
    }
    
    if (!name) {
        showMessage('Введите название ИКПУ кода', 'error');
        nameInput.focus();
        return;
    }
    
    // Check if already linked
    if (isIkpuLinkedToProfile(code)) {
        showMessage('Этот ИКПУ код уже привязан к профилю', 'warning');
        return;
    }
    
    // Show loading
    loadingDiv.style.display = 'block';
    document.getElementById('add-custom-ikpu-btn').disabled = true;
    
    // Add to profile
    addIkpuCodeToProfile(code, name, function(success) {
        loadingDiv.style.display = 'none';
        document.getElementById('add-custom-ikpu-btn').disabled = false;
        
        if (success) {
            clearCustomForm();
            showMessage('ИКПУ код успешно добавлен к профилю!', 'success');
            
            // Refresh linked codes if needed
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        }
    });
}

function clearCustomForm() {
    document.getElementById('custom-ikpu-code').value = '';
    document.getElementById('custom-ikpu-name').value = '';
    document.getElementById('custom-ikpu-code').focus();
}

function addIkpuCodeToProfile(classCode, className, callback) {
    const formData = new FormData();
    formData.append('classCode', classCode);
    formData.append('className', className);
    
    fetch('<?= Yii::$app->urlManager->createUrl(['/admin/didox/ikpu-add']) ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (callback) callback(true);
        } else {
            showMessage(data.error || 'Ошибка добавления ИКПУ кода', 'error');
            if (callback) callback(false);
        }
    })
    .catch(error => {
        console.error('Error adding IKPU code:', error);
        showMessage('Ошибка сети при добавлении ИКПУ кода', 'error');
        if (callback) callback(false);
    });
}
</script> 