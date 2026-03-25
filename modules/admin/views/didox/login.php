<?php
use yii\helpers\Html;

$this->title = 'DIDOX - E-IMZO Authentication';
$this->params['breadcrumbs'][] = ['label' => 'DIDOX Documents', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

// Register E-IMZO JavaScript modules
$this->registerJsFile('https://test.e-imzo.uz/demo/e-imzo.js', ['position' => \yii\web\View::POS_HEAD]);
$this->registerJsFile('https://test.e-imzo.uz/demo/e-imzo-client.js', ['position' => \yii\web\View::POS_HEAD]);
$this->registerJsFile('/eimzo-auth.js', ['position' => \yii\web\View::POS_HEAD]);
?>

<div class="content-wrapper">
    <section class="content-header">
        <h1><?= Html::encode($this->title) ?></h1>
        <ol class="breadcrumb">
            <li><a href="<?= Yii::$app->urlManager->createUrl(['/admin/']) ?>"><i class="fa fa-dashboard"></i> Home</a></li>
            <li><a href="<?= Yii::$app->urlManager->createUrl(['/admin/didox']) ?>">DIDOX Documents</a></li>
            <li class="active"><?= Html::encode($this->title) ?></li>
        </ol>
    </section>
    <section class="content">
        <?php if (Yii::$app->session->hasFlash('didox_error')): ?>
            <div class="callout callout-danger">
                <i class="icon fa fa-ban"></i> <?= Yii::$app->session->getFlash('didox_error') ?>
            </div>
        <?php endif; ?>

        <?php if (Yii::$app->session->hasFlash('didox_info')): ?>
            <div class="callout callout-info">
                <i class="icon fa fa-info"></i> <?= Yii::$app->session->getFlash('didox_info') ?>
            </div>
        <?php endif; ?>

        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">
                    <i class="fa fa-shield"></i> E-IMZO Authentication
                </h3>
                <div class="box-tools pull-right">
                    <a href="<?= Yii::$app->urlManager->createUrl(['/admin/didox']) ?>" class="btn btn-default btn-sm">
                        <i class="fa fa-arrow-left"></i> Back to Documents
                    </a>
                </div>
            </div>
            <div class="box-body">
                <div class="callout callout-info">
                    <h4><i class="icon fa fa-info"></i> About DIDOX Authentication</h4>
                    You can authenticate into DIDOX with E-IMZO or password, or test direct backend E-IMZO login separately.
                    Choose the required flow and connection type below.
                </div>

                <!-- Authentication Status -->
                <div id="authStatus" class="callout callout-info">
                    <i class="fa fa-clock-o"></i> Ready to authenticate. Please fill in your details and choose authentication method.
                </div>

                <!-- Authentication Form -->
                <div class="row auth-form-container">
                    <!-- User Details -->
                    <div class="col-md-6">
                        <div class="box box-default">
                            <div class="box-header with-border">
                                <h3 class="box-title">User Details</h3>
                            </div>
                            <div class="box-body">
                                <!-- Connection Type -->
                                <div class="form-group">
                                    <label>Connection Type <span class="text-red">*</span></label>
                                    <div class="radio">
                                        <label>
                                            <input type="radio" name="connectionType" value="fiz" checked>
                                            <strong>Физическое лицо (Individual Person)</strong>
                                            <br><small class="text-muted">Connect as individual person with your personal INN</small>
                                        </label>
                                    </div>
                                    <div class="radio">
                                        <label>
                                            <input type="radio" name="connectionType" value="yur">
                                            <strong>Юридическое лицо (Company)</strong>
                                            <br><small class="text-muted">Connect to company account with company INN</small>
                                        </label>
                                    </div>
                                </div>

                                <!-- Dynamic INN Input -->
                                <div class="form-group">
                                    <label for="taxIdInput" id="taxIdLabel">Your INN (Tax ID) <span class="text-red">*</span></label>
                                    <input type="text" id="taxIdInput" class="form-control" placeholder="Enter your 9-digit INN" maxlength="9" pattern="[0-9]{9}">
                                    <p class="help-block" id="taxIdHelpText">
                                        Enter your 9-digit Tax Identification Number (INN)
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Authentication Method -->
                    <div class="col-md-6">
                        <div class="box box-default">
                            <div class="box-header with-border">
                                <h3 class="box-title">Authentication Method</h3>
                            </div>
                            <div class="box-body">
                                <!-- Authentication Method Selection -->
                                <div class="form-group">
                                    <label>Choose Authentication Method <span class="text-red">*</span></label>
                                    <div class="radio">
                                        <label>
                                            <input type="radio" name="authMethod" value="eimzo_didox" checked>
                                            <strong>E-IMZO -> DIDOX</strong>
                                            <br><small class="text-muted">Authenticate in DIDOX using your digital certificate</small>
                                        </label>
                                    </div>
                                    <div class="radio">
                                        <label>
                                            <input type="radio" name="authMethod" value="eimzo_direct">
                                            <strong>Direct E-IMZO</strong>
                                            <br><small class="text-muted">Authenticate in the shop backend without DIDOX token exchange</small>
                                        </label>
                                    </div>
                                    <div class="radio">
                                        <label>
                                            <input type="radio" name="authMethod" value="password">
                                            <strong>Password</strong>
                                            <br><small class="text-muted">Authenticate using your DIDOX password</small>
                                        </label>
                                    </div>
                                </div>

                                <!-- Password Input (shown only for password method) -->
                                <div class="form-group" id="passwordGroup" style="display: none;">
                                    <label for="passwordInput">DIDOX Password <span class="text-red">*</span></label>
                                    <input type="password" id="passwordInput" class="form-control" placeholder="Enter your DIDOX password">
                                    <p class="help-block">
                                        Enter the password you use to login to DIDOX system
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step Progress Indicator -->
                <div class="progress-steps">
                    <div class="row">
                        <div class="col-sm-3">
                            <div class="step" id="step1">
                                <div class="step-number">1</div>
                                <div class="step-text">Validate Details</div>
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <div class="step" id="step2">
                                <div class="step-number">2</div>
                                <div class="step-text">Authenticate</div>
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <div class="step" id="step3">
                                <div class="step-number">3</div>
                                <div class="step-text">Connect</div>
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <div class="step" id="step4">
                                <div class="step-number">4</div>
                                <div class="step-text">Complete</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- E-IMZO Authentication Section (shown only for E-IMZO method) -->
                <div id="eimzoSection">
                <!-- Connection Controls -->
                <div class="form-group">
                    <button id="connectBtn" class="btn btn-primary btn-lg" onclick="connectToEIMZO()">
                        <i class="fa fa-plug"></i> Connect to E-IMZO
                    </button>
                    <button id="disconnectBtn" class="btn btn-default" onclick="disconnectFromEIMZO()" disabled>
                        <i class="fa fa-unlink"></i> Disconnect
                    </button>
                </div>

                <!-- Certificate Selection -->
                <div class="form-group">
                    <label for="certificateSelect">Select Digital Certificate:</label>
                        <div class="input-group">
                    <select id="certificateSelect" class="form-control" disabled>
                        <option>First connect to E-IMZO...</option>
                    </select>
                            <span class="input-group-btn">
                                <button type="button" id="debugCertBtn" class="btn btn-default" onclick="debugSelectedCertificate()" disabled title="Debug certificate data">
                                    <i class="fa fa-bug"></i>
                                </button>
                            </span>
                        </div>
                    <p class="help-block">
                            Choose the digital certificate you want to use for authentication. INN will be auto-filled from the certificate.
                    </p>
                    </div>
                </div>

                <!-- Main Authentication Button -->
                <div class="form-group">
                    <button id="loginBtn" class="btn btn-success btn-lg" onclick="performAuthentication()">
                        <i class="fa fa-sign-in"></i> <span id="loginBtnText">Authenticate</span>
                    </button>
                    <button id="mobileLoginBtn" class="btn btn-info btn-lg" onclick="startMobileAuthentication()" style="display: none;">
                        <i class="fa fa-mobile"></i> Direct Mobile E-IMZO
                    </button>
                </div>

                <!-- Result Display -->
                <div id="resultSection" style="display: none;">
                    <div class="box box-success">
                        <div class="box-header with-border">
                            <h3 class="box-title">Authentication Result</h3>
                        </div>
                        <div class="box-body">
                            <div id="resultContent"></div>
                        </div>
                    </div>
                </div>

                <div id="mobileAuthModal" class="eimzo-mobile-modal" style="display: none;">
                    <div class="eimzo-mobile-backdrop" onclick="closeMobileAuthModal()"></div>
                    <div class="eimzo-mobile-dialog">
                        <div class="eimzo-mobile-header">
                            <h3><i class="fa fa-mobile"></i> Direct Mobile E-IMZO</h3>
                            <button type="button" class="close" onclick="closeMobileAuthModal()">&times;</button>
                        </div>
                        <div class="eimzo-mobile-body">
                            <div class="row">
                                <div class="col-sm-5">
                                    <img id="mobileQrImage" alt="E-IMZO QR" class="img-responsive img-thumbnail" style="width: 100%;">
                                </div>
                                <div class="col-sm-7">
                                    <p>Scan the QR code in the E-IMZO mobile app or open the deeplink on the same device.</p>
                                    <p><strong>Document ID:</strong> <span id="mobileDocumentId">-</span></p>
                                    <p><strong>Site ID:</strong> <span id="mobileSiteId">-</span></p>
                                    <div class="form-group">
                                        <label>Deep link</label>
                                        <input type="text" id="mobileDeepLink" class="form-control" readonly>
                                    </div>
                                    <div class="form-group">
                                        <label>QR payload</label>
                                        <textarea id="mobileQrPayload" class="form-control" rows="4" readonly></textarea>
                                    </div>
                                    <div class="btn-group">
                                        <a id="mobileOpenLink" href="#" class="btn btn-success" target="_blank" rel="noopener">
                                            <i class="fa fa-external-link"></i> Open E-IMZO App
                                        </a>
                                        <button type="button" class="btn btn-default" onclick="copyMobilePayload()">
                                            <i class="fa fa-copy"></i> Copy Payload
                                        </button>
                                    </div>
                                    <div id="mobileAuthStatus" class="callout callout-info" style="margin-top: 15px;">
                                        <i class="fa fa-clock-o"></i> Waiting for mobile confirmation...
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Help Section -->
        <div class="box box-info">
            <div class="box-header with-border">
                <h3 class="box-title">
                    <i class="fa fa-question-circle"></i> Help & Requirements
                </h3>
            </div>
            <div class="box-body">
                <div class="row">
                    <div class="col-md-6">
                        <h4>Requirements:</h4>
                        <ul>
                            <li>E-IMZO application must be installed and running on your computer</li>
                            <li>Valid digital certificate issued by certification authority</li>
                            <li>Certificate must be registered in DIDOX system</li>
                            <li>Modern web browser with WebSocket support</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h4>Troubleshooting:</h4>
                        <ul>
                            <li>If connection fails, ensure E-IMZO application is running</li>
                            <li>Check that your certificate is not expired</li>
                            <li>Make sure you have internet connection for DIDOX API</li>
                            <li>Contact administrator if authentication continues to fail</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<style>
.progress-steps {
    margin: 20px 0;
}

.progress-steps .step {
    text-align: center;
    padding: 10px 5px;
}

.progress-steps .step-number {
    width: 35px;
    height: 35px;
    border-radius: 50%;
    background-color: #f4f4f4;
    border: 2px solid #ddd;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 8px;
    font-weight: bold;
    color: #666;
    transition: all 0.3s ease;
}

.progress-steps .step-text {
    font-size: 11px;
    color: #666;
    font-weight: 500;
}

.progress-steps .step.active .step-number {
    background-color: #3c8dbc;
    color: white;
    border-color: #3c8dbc;
    transform: scale(1.1);
}

.progress-steps .step.completed .step-number {
    background-color: #00a65a;
    color: white;
    border-color: #00a65a;
}

.progress-steps .step.active .step-text,
.progress-steps .step.completed .step-text {
    color: #333;
    font-weight: 600;
}

.btn-lg {
    padding: 10px 20px;
    font-size: 16px;
    margin-right: 10px;
}

#certificateSelect {
    min-height: 40px;
}

/* Authentication form styling */
.auth-form-container {
    margin-bottom: 20px;
}

.auth-form-container .box {
    margin-bottom: 0;
}

.auth-form-container .radio {
    margin-bottom: 10px;
}

.auth-form-container .radio label {
    padding-left: 25px;
    cursor: pointer;
    line-height: 1.4;
}

.auth-form-container .radio input[type="radio"] {
    margin-left: -25px;
    margin-top: 5px;
}

.auth-form-container .help-block {
    margin-bottom: 0;
    font-size: 12px;
}

.text-red {
    color: #dd4b39;
}

/* Input validation styling */
.form-control.error {
    border-color: #dd4b39;
    box-shadow: inset 0 1px 1px rgba(0,0,0,.075), 0 0 6px rgba(221,75,57,.6);
}

.form-control.success {
    border-color: #00a65a;
    box-shadow: inset 0 1px 1px rgba(0,0,0,.075), 0 0 6px rgba(0,166,90,.6);
}

/* E-IMZO section styling */
#eimzoSection {
    transition: opacity 0.3s ease;
}

#eimzoSection.disabled {
    opacity: 0.5;
    pointer-events: none;
}

/* Authentication button styling */
#loginBtn {
    min-width: 200px;
    transition: all 0.3s ease;
}

#mobileLoginBtn {
    min-width: 220px;
}

#loginBtn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.eimzo-mobile-modal {
    position: fixed;
    inset: 0;
    z-index: 1050;
}

.eimzo-mobile-backdrop {
    position: absolute;
    inset: 0;
    background: rgba(0, 0, 0, 0.55);
}

.eimzo-mobile-dialog {
    position: relative;
    z-index: 1051;
    width: min(900px, calc(100% - 40px));
    margin: 40px auto;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25);
    overflow: hidden;
}

.eimzo-mobile-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px;
    border-bottom: 1px solid #f0f0f0;
}

.eimzo-mobile-header h3 {
    margin: 0;
    font-size: 22px;
}

.eimzo-mobile-body {
    padding: 20px;
}

/* Progress steps responsive */
@media (max-width: 768px) {
    .progress-steps .step {
        padding: 5px 2px;
    }
    
    .progress-steps .step-number {
        width: 30px;
        height: 30px;
        font-size: 12px;
    }
    
    .progress-steps .step-text {
        font-size: 10px;
    }
}
</style>

<script>
let eimzoAuth = null;
let mobileAuthInFlight = false;
let desktopEimzoConnected = false;
let desktopCertificates = {};

const DESKTOP_EIMZO_API_KEYS = [
    'null', 'E0A205EC4E7B78BBB56AFF83A733A1BB9FD39D562E67978CC5E7D73B0951DB1954595A20672A63332535E13CC6EC1E1FC8857BB09E0855D7E76E411B6FA16E9D',
    'localhost', '96D0C1491615C82B9A54D9989779DF825B690748224C2B04F500F370D51827CE2644D8D4A82C18184D73AB8530BB8ED537269603F61DB0D03D2104ABF789970B',
    '127.0.0.1', 'A7BCFA5D490B351BE0754130DF03A068F855DB4333D43921125B9CF2670EF6A40370C646B90401955E1F7BC9CDBF59CE0B2C5467D820BE189C845D0B79CFC96F',
];

// Initialize page interactions
document.addEventListener('DOMContentLoaded', function() {
    // Connection type change handler
    document.querySelectorAll('input[name="connectionType"]').forEach(radio => {
        radio.addEventListener('change', function() {
            updateTaxIdFieldLabels();
            updateLoginButtonText();
            
            // Re-trigger certificate selection to update the INN field context
            const certificateSelect = document.getElementById('certificateSelect');
            if (certificateSelect && certificateSelect.value) {
                handleCertificateSelection();
            }
        });
    });

    // Authentication method change handler
    document.querySelectorAll('input[name="authMethod"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const eimzoSection = document.getElementById('eimzoSection');
            const passwordGroup = document.getElementById('passwordGroup');
            const loginBtn = document.getElementById('loginBtn');
            const mobileLoginBtn = document.getElementById('mobileLoginBtn');
            
            if (this.value === 'eimzo_didox' || this.value === 'eimzo_direct') {
                eimzoSection.style.display = 'block';
                passwordGroup.style.display = 'none';
                loginBtn.disabled = !desktopEimzoConnected;
                mobileLoginBtn.style.display = this.value === 'eimzo_direct' ? 'inline-block' : 'none';
            } else {
                eimzoSection.style.display = 'none';
                passwordGroup.style.display = 'block';
                loginBtn.disabled = false;
                mobileLoginBtn.style.display = 'none';
            }
            updateLoginButtonText();
        });
    });

    // INN input validation
    document.getElementById('taxIdInput').addEventListener('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '');
        updateLoginButtonText();
    });

    // Initialize labels
    updateTaxIdFieldLabels();
    updateLoginButtonText();
    
    // Check if user is already authenticated
    checkAuthenticationStatus();
});

function updateTaxIdFieldLabels() {
    const connectionType = document.querySelector('input[name="connectionType"]:checked').value;
    const taxIdLabel = document.getElementById('taxIdLabel');
    const taxIdInput = document.getElementById('taxIdInput');
    const taxIdHelpText = document.getElementById('taxIdHelpText');
    
    if (connectionType === 'yur') {
        taxIdLabel.innerHTML = 'Company INN (Tax ID) <span class="text-red">*</span>';
        taxIdInput.placeholder = 'Enter company 9-digit INN';
        taxIdHelpText.textContent = 'Enter the company\'s 9-digit Tax Identification Number';
    } else {
        taxIdLabel.innerHTML = 'Your INN (Tax ID) <span class="text-red">*</span>';
        taxIdInput.placeholder = 'Enter your 9-digit INN';
        taxIdHelpText.textContent = 'Enter your 9-digit Tax Identification Number (INN)';
    }
}

function updateLoginButtonText() {
    const connectionType = document.querySelector('input[name="connectionType"]:checked').value;
    const authMethod = document.querySelector('input[name="authMethod"]:checked').value;
    const loginBtnText = document.getElementById('loginBtnText');
    
    let text = 'Authenticate';
    if (authMethod === 'eimzo_didox') {
        text = connectionType === 'yur' ? 'Authenticate in DIDOX for Company' : 'Authenticate in DIDOX';
    } else if (authMethod === 'eimzo_direct') {
        text = connectionType === 'yur' ? 'Direct E-IMZO Login (Company Type)' : 'Direct E-IMZO Login';
    } else {
        text = connectionType === 'yur' ? 'Login to Company with Password' : 'Login with Password';
    }
    
    loginBtnText.textContent = text;
}

function createAuthClient(authMethod) {
    const isDirectFlow = authMethod === 'eimzo_direct';
    const apiBaseUrl = isDirectFlow
        ? window.location.origin
        : <?= json_encode(Yii::$app->params['didoxApiUrl'] ?? 'https://stage.goodsign.biz') ?>;

    const client = isDirectFlow
        ? new EIMZOYii2Auth(apiBaseUrl)
        : new EIMZOAuth(apiBaseUrl, { provider: 'didox' });

    client.onStatusUpdate = (message, type) => {
        updateStatus(message, type);
    };

    client.onAuthSuccess = (token, userData) => {
        const currentAuthMethod = document.querySelector('input[name="authMethod"]:checked').value;

        if (currentAuthMethod === 'eimzo_direct') {
            handleDirectAuthSuccess(token, userData);
            return;
        }

        console.log('=== E-IMZO AUTH SUCCESS ===');
        console.log('DIDOX token received:', token ? token.substring(0, 20) + '...' : 'null');
        console.log('User data from E-IMZO:', userData);

        const connectionType = document.querySelector('input[name="connectionType"]:checked').value;
        const taxIdFromField = document.getElementById('taxIdInput').value;

        console.log('Connection type:', connectionType);
        console.log('TaxId from field:', taxIdFromField);

        if (connectionType === 'yur') {
            const selectedOption = document.getElementById('certificateSelect').options[document.getElementById('certificateSelect').selectedIndex];
            const individualInn = selectedOption.getAttribute('data-inn') ||
                extractInnFromAlias(selectedOption.getAttribute('data-alias'));

            if (!individualInn) {
                updateStatus('Could not extract individual INN from certificate. Please check certificate data.', 'danger');
                return;
            }

            updateStatus(`Company login: Step 2 - Logging individual into company (${taxIdFromField})...`, 'info');
            authenticateWithServerForCompany(individualInn, token, taxIdFromField);
        } else {
            authenticateWithServer(taxIdFromField, token);
        }
    };

    client.onAuthError = (error) => {
        updateStatus('Error: ' + error, 'danger');
    };

    client.onStepUpdate = (stepNumber, completed) => {
        updateStep(stepNumber, completed);
    };

    return client;
}

function installDesktopApiKeys() {
    return new Promise((resolve, reject) => {
        if (typeof EIMZOClient === 'undefined') {
            reject(new Error('Official E-IMZO client script is not loaded'));
            return;
        }

        EIMZOClient.API_KEYS = DESKTOP_EIMZO_API_KEYS.slice();
        EIMZOClient.checkVersion(function () {
            EIMZOClient.installApiKeys(function () {
                resolve();
            }, function (e, reason) {
                reject(new Error(reason || ('API key install failed: ' + (e || 'unknown error'))));
            });
        }, function (e, reason) {
            reject(new Error(reason || ('Version check failed: ' + (e || 'unknown error'))));
        });
    });
}

function loadDesktopCertificates() {
    return new Promise((resolve, reject) => {
        EIMZOClient.listAllUserKeys(
            function (cert, idx) {
                return `itm-${cert.serialNumber || 'cert'}-${idx}`;
            },
            function (itemId, cert) {
                return {
                    id: itemId,
                    type: cert.type,
                    disk: cert.disk,
                    path: cert.path,
                    name: cert.name,
                    alias: cert.alias,
                    cardUID: cert.cardUID,
                    serialNumber: cert.serialNumber,
                    validFrom: cert.validFrom,
                    validTo: cert.validTo,
                    CN: cert.CN,
                    TIN: cert.TIN,
                    UID: cert.UID,
                    PINFL: cert.PINFL,
                    O: cert.O,
                    T: cert.T,
                };
            },
            function (items) {
                resolve(items);
            },
            function (e, reason) {
                reject(new Error(reason || ('Certificate list failed: ' + (e || 'unknown error'))));
            }
        );
    });
}

function normalizeDesktopCertificate(cert) {
    const inn = cert.TIN || cert.UID || extractInnFromAlias(cert.alias);
    const validTo = cert.validTo instanceof Date
        ? cert.validTo.toISOString().slice(0, 19).replace('T', ' ')
        : cert.validTo;

    return {
        id: cert.id,
        index: cert.id,
        type: cert.type,
        disk: cert.disk,
        path: cert.path,
        name: cert.name,
        alias: cert.alias,
        cardUID: cert.cardUID,
        serialNumber: cert.serialNumber,
        inn,
        taxId: inn,
        uid: cert.UID || inn,
        validTo,
        displayName: `${cert.CN || cert.O || 'E-IMZO Certificate'}${validTo ? ` - ${validTo}` : ''}${inn ? ` ИНН: ${inn}` : ''}`,
        raw: cert,
    };
}

async function connectToEIMZO() {
    const authMethod = document.querySelector('input[name="authMethod"]:checked').value;
    eimzoAuth = createAuthClient(authMethod);

    try {
        updateStatus('Checking E-IMZO desktop client...', 'info');
        await installDesktopApiKeys();
        updateStatus('Loading certificates from E-IMZO...', 'info');

        const certificates = (await loadDesktopCertificates()).map(normalizeDesktopCertificate);
        desktopCertificates = {};
        certificates.forEach((cert) => {
            desktopCertificates[String(cert.id)] = cert;
        });

        populateCertificates(certificates);
        desktopEimzoConnected = true;
        document.getElementById('connectBtn').disabled = true;
        document.getElementById('disconnectBtn').disabled = false;
        document.getElementById('loginBtn').disabled = false;
        updateStatus(`Найдено сертификатов: ${certificates.length}`, 'success');
        updateStep(1, true);
    } catch (error) {
        desktopEimzoConnected = false;
        updateStatus('Error: ' + error.message, 'danger');
        document.getElementById('connectBtn').disabled = false;
        document.getElementById('disconnectBtn').disabled = true;
        document.getElementById('loginBtn').disabled = true;
    }
}

function disconnectFromEIMZO() {
    eimzoAuth = null;
    desktopEimzoConnected = false;
    desktopCertificates = {};

    // Reset UI
    document.getElementById('connectBtn').disabled = false;
    document.getElementById('disconnectBtn').disabled = true;
    document.getElementById('certificateSelect').disabled = true;
    document.getElementById('loginBtn').disabled = true;
    document.getElementById('certificateSelect').innerHTML = '<option>First connect to E-IMZO...</option>';
    
    // Reset steps
    for (let i = 1; i <= 4; i++) {
        const step = document.getElementById(`step${i}`);
        step.classList.remove('active', 'completed');
    }

    updateStatus('Disconnected from E-IMZO', 'info');
    hideResult();
}

function populateCertificates(certificates) {
    const select = document.getElementById('certificateSelect');
    select.innerHTML = '<option value="">Choose a certificate...</option>';
    
    certificates.forEach(cert => {
        const option = document.createElement('option');
        option.value = cert.id || cert.index;
        option.textContent = cert.displayName;
        
        // Store certificate data for auto-fill - extract INN from various sources
        let extractedInn = null;
        
        // Try to get INN from cert.inn property
        if (cert.inn || cert.taxId || cert.TIN) {
            extractedInn = cert.inn || cert.taxId || cert.TIN;
        }
        // Try to extract from alias
        else if (cert.alias) {
            extractedInn = extractInnFromAlias(cert.alias);
        }
        // Try to extract from subject if available
        else if (cert.subject && cert.subject['1.2.860.3.16.1.1']) {
            extractedInn = cert.subject['1.2.860.3.16.1.1'];
        }
        // Try to extract from other certificate properties
        else if (cert.serialNumber) {
            // Sometimes INN might be in other certificate fields
            const serialStr = cert.serialNumber.toString();
            if (/^\d{9}$/.test(serialStr)) {
                extractedInn = serialStr;
            }
        }
        
        if (extractedInn) {
            option.setAttribute('data-inn', extractedInn);
        }
        if (cert.alias) {
            option.setAttribute('data-alias', cert.alias);
        }
        
        // Store full certificate data for debugging
        option.setAttribute('data-cert', JSON.stringify(cert));
        
        select.appendChild(option);
    });

    // Remove any existing event listener to avoid duplicates
    select.removeEventListener('change', handleCertificateSelection);
    // Add change event listener to auto-fill INN
    select.addEventListener('change', handleCertificateSelection);

    select.disabled = false;
    document.getElementById('debugCertBtn').disabled = false;
}

function handleCertificateSelection() {
    const select = document.getElementById('certificateSelect');
    const taxIdInput = document.getElementById('taxIdInput');
    const connectionType = document.querySelector('input[name="connectionType"]:checked').value;
    
    if (select.value) {
        const selectedOption = select.options[select.selectedIndex];
        let inn;

        if(connectionType === 'yur'){
            inn = selectedOption.getAttribute('data-inn');
        }else{
            const alias = selectedOption.getAttribute('data-alias');
            inn = alias.match(/uid=([^,]+)/i)?.[1] || null;
        }

        console.log('Selected certificate data:', selectedOption.getAttribute('data-cert'));
        console.log('Extracted INN:', inn);
        
        if (inn && /^\d{9}$/.test(inn)) {
            taxIdInput.value = inn;
            
            // Provide contextual feedback based on connection type
            if (connectionType === 'yur') {
                updateStatus(`Personal INN (${inn}) detected from certificate. For company login, enter company INN instead if different.`, 'info');
                // Highlight the field to indicate user should verify
                taxIdInput.classList.add('success');
                setTimeout(() => {
                    taxIdInput.classList.remove('success');
                }, 3000);
            } else {
                updateStatus(`INN auto-filled from certificate: ${inn}`, 'success');
                taxIdInput.classList.add('success');
                setTimeout(() => {
                    taxIdInput.classList.remove('success');
                }, 2000);
            }
        } else {
            // If no INN found, try to extract from alias as fallback
            const alias = selectedOption.getAttribute('data-alias');
            const extractedInn = extractInnFromAlias(alias);
            
            if (extractedInn && /^\d{9}$/.test(extractedInn)) {
                taxIdInput.value = extractedInn;
                
                if (connectionType === 'yur') {
                    updateStatus(`Personal INN (${extractedInn}) extracted from certificate. For company login, enter company INN if different.`, 'info');
                } else {
                    updateStatus(`INN extracted from certificate: ${extractedInn}`, 'success');
                }
                
                taxIdInput.classList.add('success');
                setTimeout(() => {
                    taxIdInput.classList.remove('success');
                }, 2000);
            } else {
                updateStatus('Could not extract INN from certificate. Please enter manually.', 'warning');
                console.warn('Certificate data:', selectedOption.getAttribute('data-cert'));
            }
        }
    } else {
        // Clear the field when no certificate is selected
        taxIdInput.value = '';
    }
}

function extractInnFromAlias(alias) {
    if (!alias) return null;
    
    // Extract INN from E-IMZO certificate alias
    // Common formats:
    // 1.2.860.3.16.1.1=123456789
    // CN=..., 1.2.860.3.16.1.1=123456789, ...
    // Sometimes it might be in different formats
    
    // Try primary OID format for INN
    let match = alias.match(/1\.2\.860\.3\.16\.1\.1=(\d{9})/);
    if (match) return match[1];
    
    // Try alternative formats
    match = alias.match(/INN[=:](\d{9})/i);
    if (match) return match[1];
    
    // Try to find any 9-digit number in the alias (as last resort)
    match = alias.match(/(\d{9})/);
    if (match) return match[1];
    
    return null;
}

function performAuthentication() {
    const taxId = document.getElementById('taxIdInput').value;
    const connectionType = document.querySelector('input[name="connectionType"]:checked').value;
    const authMethod = document.querySelector('input[name="authMethod"]:checked').value;

    console.log('=== AUTHENTICATION START ===');
    console.log('TaxId from field:', taxId);
    console.log('Connection type:', connectionType);
    console.log('Auth method:', authMethod);

    updateStep(1, true); // Validate Details step

    if (authMethod === 'password') {
        const password = document.getElementById('passwordInput').value;
        if (!password) {
            updateStatus('Please enter your DIDOX password', 'warning');
            return;
        }
        
        // For password authentication with company, we need individual credentials first
        if (connectionType === 'yur') {
            // Show additional input for individual INN if needed
            authenticatePasswordForCompany(taxId, password);
        } else {
            // Direct individual authentication
            authenticateWithPassword(taxId, password, connectionType);
        }
    } else if (authMethod === 'eimzo_didox' || authMethod === 'eimzo_direct') {
    if (!desktopEimzoConnected) {
        updateStatus('Please connect to E-IMZO first', 'danger');
        return;
    }

    const selectedIndex = document.getElementById('certificateSelect').value;
    if (!selectedIndex) {
        updateStatus('Please select a certificate', 'warning');
        return;
    }

        const selectedCertificate = desktopCertificates[String(selectedIndex)];
        if (!selectedCertificate) {
            updateStatus('Selected certificate is no longer available. Please reconnect to E-IMZO.', 'danger');
            return;
        }

        console.log('Selected certificate index:', selectedIndex);
        
        // Get individual INN from selected certificate for debugging
        const selectedOption = document.getElementById('certificateSelect').options[document.getElementById('certificateSelect').selectedIndex];
        const individualInnFromCert = selectedOption.getAttribute('data-inn') || 
                                     extractInnFromAlias(selectedOption.getAttribute('data-alias'));
        
        console.log('Individual INN from certificate:', individualInnFromCert);
        
        if (authMethod === 'eimzo_didox' && connectionType === 'yur') {
            console.log('Company flow: Will auth individual first, then login to company');
            console.log('Company INN (from field):', taxId);
            console.log('Individual INN (from cert):', individualInnFromCert);
            
            updateStatus(`Company login: Step 1 - Authenticating individual (${individualInnFromCert}) via E-IMZO...`, 'info');
        } else if (authMethod === 'eimzo_direct' && connectionType === 'yur') {
            updateStatus('Direct E-IMZO will create/login a backend user with company type, without DIDOX company session.', 'warning');
        } else {
            console.log('Individual flow: Direct authentication');
            updateStatus(`Individual login: Authenticating via E-IMZO...`, 'info');
        }

        // For E-IMZO authentication, always use individual INN from certificate
        // The eimzoAuth.loginWithCertificate will extract and use individual INN from certificate
        // Company INN (if applicable) will be handled in the success callback

        // Save selected certificate info for later use
        const certOption = document.getElementById('certificateSelect').options[document.getElementById('certificateSelect').selectedIndex];
        const certificateInfo = {
            index: selectedIndex,
            displayName: certOption.textContent,
            inn: certOption.getAttribute('data-inn'),
            alias: certOption.getAttribute('data-alias')
        };
        
        console.log('Saving certificate info:', certificateInfo);
        console.log(`connectionType: ${connectionType} taxId: ${taxId}`);
        
        // Store in localStorage for cross-page access
        localStorage.setItem('didox_selected_certificate', JSON.stringify(certificateInfo));
        
        eimzoAuth = createAuthClient(authMethod);
        eimzoAuth.loginData = {
            taxId,
            certificateIndex: selectedIndex,
            certificate: selectedCertificate,
            connectionType,
            extra: {},
        };

        const payloadPromise = authMethod === 'eimzo_direct'
            ? eimzoAuth.fetchDirectChallenge().then((challengeData) => {
                eimzoAuth.loginData.challenge = challengeData.challenge;
                eimzoAuth.loginData.challengeTtl = challengeData.ttl;
                return challengeData.challenge;
            })
            : Promise.resolve(taxId);

        payloadPromise.then((payloadToSign) => {
            updateStatus('Loading certificate key from E-IMZO...', 'info');
            updateStep(2, false);

            EIMZOClient.loadKey(selectedCertificate.raw || selectedCertificate, function (keyId) {
                eimzoAuth.loginData.keyId = keyId;
                updateStatus('Создание цифровой подписи...', 'info');

                EIMZOClient.createPkcs7(keyId, payloadToSign, null, async function (pkcs7) {
                    eimzoAuth.loginData.pkcs7_64 = pkcs7;
                    eimzoAuth.updateStep(3, true);

                    if (authMethod === 'eimzo_direct') {
                        await eimzoAuth.performDirectAuthentication();
                    } else {
                        await eimzoAuth.addDidoxTimestamp();
                    }
                }, function (e, reason) {
                    eimzoAuth.handleError(reason || ('PKCS#7 creation failed: ' + (e || 'unknown error')));
                }, false, false);
            }, function (e, reason) {
                eimzoAuth.handleError(reason || ('Key load failed: ' + (e || 'unknown error')));
            }, false);
        }).catch((error) => {
            eimzoAuth.handleError(error.message || String(error));
        });
    }
}

function handleDirectAuthSuccess(token, authData) {
    const payload = authData && authData.user ? authData : { user: authData };
    const user = payload.user || {};

    localStorage.setItem('shop_direct_eimzo_token', token);
    localStorage.setItem('shop_direct_eimzo_user', JSON.stringify(payload));

    updateStep(3, true);
    updateStep(4, true);
    updateStatus('Direct E-IMZO login successful. Backend bearer token stored in localStorage.', 'success');

    const resultSection = document.getElementById('resultSection');
    const resultContent = document.getElementById('resultContent');

    resultContent.innerHTML = `
        <div class="callout callout-success">
            <h4><i class="fa fa-check-circle"></i> Direct E-IMZO Login Successful</h4>
            <p><strong>User ID:</strong> ${user.id || '-'}</p>
            <p><strong>INN:</strong> ${user.eimzo_tax_id || '-'}</p>
            <p><strong>Type:</strong> ${user.type || '-'}</p>
            <p><strong>Bearer Token:</strong><br><code style="word-break: break-all;">${token || '-'}</code></p>
        </div>
    `;

    resultSection.style.display = 'block';
}

async function startMobileAuthentication() {
    const authMethod = document.querySelector('input[name="authMethod"]:checked').value;
    if (authMethod !== 'eimzo_direct') {
        updateStatus('Mobile flow is available only for direct E-IMZO mode.', 'warning');
        return;
    }

    if (mobileAuthInFlight) {
        updateStatus('Mobile authentication is already running.', 'warning');
        return;
    }

    try {
        mobileAuthInFlight = true;
        updateStatus('Starting mobile E-IMZO session...', 'info');

        if (!eimzoAuth || !(eimzoAuth instanceof EIMZOYii2Auth)) {
            eimzoAuth = new EIMZOYii2Auth(window.location.origin);
        }

        const init = await eimzoAuth.startMobileAuth();
        const digestHex = await eimzoAuth.getDigestHex(init.challenge);
        const qr = eimzoAuth.buildMobileQrPayload({
            siteId: init.siteId,
            documentId: init.documentId,
            challenge: init.challenge,
            hashHex: digestHex,
        });

        openMobileAuthModal(init, qr);
        await pollMobileAuthentication(init.documentId, init.pollInterval || 5, init.timeout || 120);
    } catch (error) {
        updateStatus('Mobile E-IMZO error: ' + error.message, 'danger');
        setMobileStatus('Mobile flow failed: ' + error.message, 'danger');
    } finally {
        mobileAuthInFlight = false;
    }
}

function openMobileAuthModal(init, qr) {
    document.getElementById('mobileAuthModal').style.display = 'block';
    document.getElementById('mobileDocumentId').textContent = init.documentId || '-';
    document.getElementById('mobileSiteId').textContent = init.siteId || '-';
    document.getElementById('mobileDeepLink').value = qr.deepLink || '';
    document.getElementById('mobileQrPayload').value = qr.qrCode || '';
    document.getElementById('mobileOpenLink').href = qr.deepLink || '#';
    document.getElementById('mobileQrImage').src =
        'https://api.qrserver.com/v1/create-qr-code/?size=280x280&data=' + encodeURIComponent(qr.qrCode || '');
    setMobileStatus('Scan the QR code or open the deeplink, then confirm in the mobile app.', 'info');
}

function closeMobileAuthModal() {
    document.getElementById('mobileAuthModal').style.display = 'none';
}

function setMobileStatus(message, type) {
    const status = document.getElementById('mobileAuthStatus');
    status.className = 'callout callout-' + (type || 'info');
    status.innerHTML = `<i class="fa fa-${type === 'success' ? 'check-circle' : (type === 'danger' ? 'warning' : 'clock-o')}"></i> ${message}`;
}

async function pollMobileAuthentication(documentId, intervalSeconds, timeoutSeconds) {
    setMobileStatus('Waiting for mobile confirmation...', 'info');
    const status = await eimzoAuth.pollMobileStatus(documentId, {
        intervalSeconds,
        timeoutSeconds,
    });

    if (!status || status.status !== 1) {
        throw new Error('Mobile confirmation did not complete');
    }

    setMobileStatus('Mobile signature received. Finalizing login...', 'info');
    const connectionType = document.querySelector('input[name="connectionType"]:checked').value;
    const authData = await eimzoAuth.getMobileAuthResult(documentId, connectionType === 'yur' ? 'yur' : 'fiz');

    setMobileStatus('Direct mobile E-IMZO login completed.', 'success');
    handleDirectAuthSuccess(authData.token, authData);
}

function copyMobilePayload() {
    const value = document.getElementById('mobileQrPayload').value;
    if (!value) {
        return;
    }

    if (navigator.clipboard) {
        navigator.clipboard.writeText(value).then(() => {
            setMobileStatus('QR payload copied to clipboard.', 'success');
        }).catch(() => {
            setMobileStatus('Unable to copy payload automatically.', 'warning');
        });
    }
}

function authenticateWithPassword(taxId, password, connectionType) {
    updateStatus('Authenticating with password...', 'info');
    updateStep(2, false); // Authenticate step in progress
    
    fetch(<?= json_encode(\yii\helpers\Url::to(['/admin/didox/authenticate-password'])) ?>, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: new URLSearchParams({
            taxId: taxId,
            password: password,
            connectionType: connectionType
        })
    })
    .then(response => response.json())
    .then(data => {
        updateStep(2, true); // Authenticate step completed
        
        if (data.success) {
            updateStep(3, true); // Connect step completed
            updateStep(4, true); // Complete step
            showSuccess(data.message || 'Authentication successful!');
            if (data.redirect) {
                setTimeout(() => {
                    window.location.href = data.redirect;
                }, 2000);
            }
        } else {
            updateStatus('Authentication failed: ' + (data.error || 'Unknown error'), 'danger');
        }
    })
    .catch(error => {
        console.error('Password authentication error:', error);
        updateStatus('Authentication error: ' + error.message, 'danger');
    });
}

function authenticatePasswordForCompany(companyTaxId, password) {
    updateStatus('Company authentication requires individual credentials...', 'info');
    
    // Show prompt for individual INN
    const individualInn = prompt('Enter your personal INN (required for company login):');
    if (!individualInn || !/^\d{9}$/.test(individualInn)) {
        updateStatus('Valid individual INN is required for company login', 'warning');
        return;
    }
    
    updateStatus('Authenticating individual and logging into company...', 'info');
    updateStep(2, false); // Authenticate step in progress
    
    fetch(<?= json_encode(\yii\helpers\Url::to(['/admin/didox/authenticate-password'])) ?>, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: new URLSearchParams({
            taxId: individualInn,
            password: password,
            connectionType: 'yur',
            companyTaxId: companyTaxId
        })
    })
    .then(response => response.json())
    .then(data => {
        updateStep(2, true); // Authenticate step completed
        
        if (data.success) {
            updateStep(3, true); // Connect step completed
            updateStep(4, true); // Complete step
            showSuccess(data.message || 'Company authentication successful!');
            if (data.redirect) {
                setTimeout(() => {
                    window.location.href = data.redirect;
                }, 2000);
            }
        } else {
            updateStatus('Company authentication failed: ' + (data.error || 'Unknown error'), 'danger');
        }
    })
    .catch(error => {
        console.error('Company password authentication error:', error);
        updateStatus('Company authentication error: ' + error.message, 'danger');
    });
}

function authenticateWithServer(taxId, token) {
    updateStatus('Saving authentication token...', 'info');
    updateStep(3, false); // Connect step in progress
    
    fetch(<?= json_encode(\yii\helpers\Url::to(['/admin/didox/save-token'])) ?>, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: new URLSearchParams({
            taxId: taxId,
            token: token,
            connectionType: document.querySelector('input[name="connectionType"]:checked').value,
            certificateInfo: JSON.stringify(JSON.parse(localStorage.getItem('didox_selected_certificate') || '{}'))
        })
    })
    .then(response => response.json())
    .then(data => {
        updateStep(3, true); // Connect step completed
        
        if (data.success) {
            updateStep(4, true); // Complete step
            showSuccess(data.message || 'Authentication successful!');
            if (data.redirect) {
                setTimeout(() => {
                    window.location.href = data.redirect;
                }, 2000);
            }
        } else {
            updateStatus('Failed to save token: ' + (data.error || 'Unknown error'), 'danger');
        }
    })
    .catch(error => {
        console.error('Token save error:', error);
        updateStatus('Server communication error: ' + error.message, 'danger');
    });
}

function authenticateWithServerForCompany(individualTaxId, individualToken, companyTaxId) {
    updateStatus('Authenticating individual and logging into company...', 'info');
    updateStep(3, false); // Connect step in progress
    
    fetch(<?= json_encode(\yii\helpers\Url::to(['/admin/didox/login-to-company'])) ?>, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: new URLSearchParams({
            individualTaxId: individualTaxId,
            individualToken: individualToken,
            companyTaxId: companyTaxId,
            certificateInfo: JSON.stringify(JSON.parse(localStorage.getItem('didox_selected_certificate') || '{}'))
        })
    })
    .then(response => response.json())
    .then(data => {
        updateStep(3, true); // Connect step completed
        
        if (data.success) {
            updateStep(4, true); // Complete step
            showSuccess(data.message || 'Company authentication successful!');
            if (data.redirect) {
                setTimeout(() => {
                    window.location.href = data.redirect;
                }, 2000);
            }
        } else {
            updateStatus('Failed to authenticate with company: ' + (data.error || 'Unknown error'), 'danger');
        }
    })
    .catch(error => {
        console.error('Company authentication error:', error);
        updateStatus('Company authentication error: ' + error.message, 'danger');
    });
}

function updateStatus(message, type) {
    const statusDiv = document.getElementById('authStatus');
    let alertClass = 'callout-info';
    let icon = 'fa-info-circle';
    
    if (type === 'danger') {
        alertClass = 'callout-danger';
        icon = 'fa-exclamation-triangle';
    } else if (type === 'success') {
        alertClass = 'callout-success';
        icon = 'fa-check-circle';
    } else if (type === 'warning') {
        alertClass = 'callout-warning';
        icon = 'fa-exclamation-circle';
    }
    
    statusDiv.className = `callout ${alertClass}`;
    statusDiv.innerHTML = `<i class="fa ${icon}"></i> ${message}`;
}

function updateStep(stepNumber, completed) {
    // Reset all steps
    for (let i = 1; i <= 4; i++) {
        const step = document.getElementById(`step${i}`);
        step.classList.remove('active', 'completed');
        
        if (i < stepNumber || (i === stepNumber && completed === true)) {
            step.classList.add('completed');
        } else if (i === stepNumber) {
            step.classList.add('active');
        }
    }
}

function showSuccess(message) {
    updateStatus(message, 'success');
    
    // Show result section
    const resultSection = document.getElementById('resultSection');
    const resultContent = document.getElementById('resultContent');
    
    resultContent.innerHTML = `
        <div class="callout callout-success">
            <h4><i class="fa fa-check-circle"></i> Authentication Successful!</h4>
            <p>${message}</p>
            <p>You will be redirected to the DIDOX dashboard in a moment...</p>
        </div>
    `;
    
    resultSection.style.display = 'block';
}

function hideResult() {
    document.getElementById('resultSection').style.display = 'none';
}

function checkAuthenticationStatus() {
    fetch(<?= json_encode(\yii\helpers\Url::to(['/admin/didox/auth-status'])) ?>, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.authenticated) {
            updateStatus('Already authenticated. Redirecting...', 'success');
            setTimeout(() => {
                window.location.href = <?= json_encode(\yii\helpers\Url::to(['/admin/didox/index'])) ?>;
            }, 1000);
        }
    })
    .catch(error => {
        console.log('Auth status check failed:', error);
    });
}

function debugSelectedCertificate() {
    const select = document.getElementById('certificateSelect');
    if (!select.value) {
        alert('Please select a certificate first');
        return;
    }
    
    const selectedOption = select.options[select.selectedIndex];
    const certData = selectedOption.getAttribute('data-cert');
    const extractedInn = selectedOption.getAttribute('data-inn');
    const alias = selectedOption.getAttribute('data-alias');
    
    let debugInfo = 'Certificate Debug Information:\n\n';
    debugInfo += `Display Name: ${selectedOption.textContent}\n`;
    debugInfo += `Extracted INN: ${extractedInn || 'Not found'}\n`;
    debugInfo += `Alias: ${alias || 'Not available'}\n\n`;
    
    if (certData) {
        try {
            const cert = JSON.parse(certData);
            debugInfo += 'Full Certificate Data:\n';
            debugInfo += JSON.stringify(cert, null, 2);
        } catch (e) {
            debugInfo += 'Error parsing certificate data: ' + e.message;
        }
    } else {
        debugInfo += 'No certificate data available';
    }
    
    // Show in a modal or alert
    if (confirm('Certificate debug info will be logged to console. Click OK to also copy to clipboard.')) {
        console.log(debugInfo);
        
        // Try to copy to clipboard
        if (navigator.clipboard) {
            navigator.clipboard.writeText(debugInfo).then(() => {
                updateStatus('Certificate debug info copied to clipboard', 'info');
            }).catch(() => {
                updateStatus('Debug info logged to console', 'info');
            });
        } else {
            updateStatus('Debug info logged to console', 'info');
        }
    }
}
</script> 
