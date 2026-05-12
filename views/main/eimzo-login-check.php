<?php
use yii\helpers\Html;

$this->title = 'E-IMZO Doc Style Check';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= Html::encode($this->title) ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f3f5f7; }
        .wrap { max-width: 980px; margin: 20px auto; padding: 0 16px; }
        .card { background: #fff; border: 1px solid #ddd; border-radius: 10px; padding: 16px; margin-bottom: 16px; }
        .row { display: flex; gap: 12px; flex-wrap: wrap; }
        .col { flex: 1; min-width: 220px; }
        h1 { margin: 0 0 8px; font-size: 22px; }
        .muted { color: #666; font-size: 13px; }
        label { display:block; margin:8px 0 4px; font-size:13px; }
        input, select, button, textarea { width:100%; box-sizing:border-box; padding:10px; border:1px solid #ccc; border-radius:8px; font-size:14px; }
        button { cursor:pointer; background:#0b74de; color:#fff; border-color:#0b74de; }
        button.secondary { background:#fff; color:#222; }
        button:disabled { opacity: .6; cursor:not-allowed; }
        pre { margin: 0; background:#101114; color:#ddd; padding:10px; border-radius:8px; font-size:12px; overflow:auto; }
        textarea { min-height: 140px; font-family: monospace; }
        #status.ok { color: #0a7a28; }
        #status.err { color: #b10000; }
    </style>

    <script src="https://test.e-imzo.uz/demo/e-imzo.js" type="text/javascript"></script>
    <script src="https://test.e-imzo.uz/demo/e-imzo-client.js" type="text/javascript"></script>
</head>
<body>
<div class="wrap">
    <div class="card">
        <h1>E-IMZO Login (Doc Example Style)</h1>
        <div class="muted">Route: <b>/main/eimzo-login-check</b>. Flow (Didox pfx): <b>list_all_certificates -> load_key -> create_pkcs7(base64(TIN)) -> /api/didox/timestamp -> /api/didox/authenticate-eimzo</b>.</div>
    </div>

    <div class="card">
        <div class="row">
            <div class="col">
                <button id="initBtn">1) AppLoad (Check version + API key + load certs)</button>
            </div>
            <div class="col">
                <select id="keySelect"><option value="">First run AppLoad...</option></select>
            </div>
            <div class="col">
                <select id="userType">
                    <option value="fiz">fiz</option>
                    <option value="yur">yur</option>
                </select>
            </div>
        </div>
        <div class="row" style="margin-top:12px;">
            <div class="col"><button id="signinPfxBtn" class="secondary" disabled>2) Signin PFX</button></div>
            <div class="col"><button id="signinTokenBtn" class="secondary" disabled>3) Signin Token (ckc)</button></div>
        </div>
    </div>

    <div class="card">
        <h3 style="margin:0 0 8px;">Optional: register/login API tests</h3>
        <div class="row">
            <div class="col">
                <label>Email</label>
                <input id="email" placeholder="user@example.com">
            </div>
            <div class="col">
                <label>Mobile</label>
                <input id="mobile" placeholder="998901234567">
            </div>
            <div class="col">
                <label>Password</label>
                <input id="password" type="password" placeholder="******">
            </div>
        </div>
        <div class="row" style="margin-top:12px;">
            <div class="col"><button id="registerBtn" class="secondary" disabled>4) /api/user/eimzo-register</button></div>
            <div class="col"><button id="loginBtn" class="secondary" disabled>5) /api/user/eimzo-login</button></div>
        </div>
    </div>

    <div class="card">
        <h3 style="margin:0 0 8px;">After Login: Order + Incoming</h3>
        <div class="row">
            <div class="col">
                <label>App user token</label>
                <input id="appToken" placeholder="auto-filled after /api/user/eimzo-login">
            </div>
            <div class="col">
                <label>Incoming didox_id</label>
                <input id="incomingDidoxId" placeholder="paste incoming didox_id">
            </div>
            <div class="col">
                <label>toSign (base64)</label>
                <input id="incomingToSign" placeholder="auto-filled by get incoming">
            </div>
        </div>
        <div class="row" style="margin-top:12px;">
            <div class="col"><button id="createOrderAutosignBtn" class="secondary" disabled>6) Create 1 so'm Order + Invoice Autosign</button></div>
            <div class="col"><button id="getIncomingListBtn" class="secondary" disabled>7) Get Incoming Documents</button></div>
            <div class="col"><button id="getIncomingToSignBtn" class="secondary" disabled>8) Get Incoming toSign</button></div>
            <div class="col"><button id="acceptIncomingBtn" class="secondary" disabled>9) Accept Incoming</button></div>
        </div>
    </div>

    <div class="card">
        <label>Status</label>
        <div id="status" class="muted">Ready.</div>
        <label style="margin-top:12px;">Result</label>
        <pre id="result">-</pre>
        <label style="margin-top:12px;">Debug</label>
        <textarea id="debug" readonly></textarea>
    </div>
</div>

<script>
(() => {
    const API_KEYS = [
        'localhost', '96D0C1491615C82B9A54D9989779DF825B690748224C2B04F500F370D51827CE2644D8D4A82C18184D73AB8530BB8ED537269603F61DB0D03D2104ABF789970B',
        '127.0.0.1', 'A7BCFA5D490B351BE0754130DF03A068F855DB4333D43921125B9CF2670EF6A40370C646B90401955E1F7BC9CDBF59CE0B2C5467D820BE189C845D0B79CFC96F'
    ];

    const els = {
        initBtn: document.getElementById('initBtn'),
        keySelect: document.getElementById('keySelect'),
        userType: document.getElementById('userType'),
        signinPfxBtn: document.getElementById('signinPfxBtn'),
        signinTokenBtn: document.getElementById('signinTokenBtn'),
        registerBtn: document.getElementById('registerBtn'),
        loginBtn: document.getElementById('loginBtn'),
        email: document.getElementById('email'),
        mobile: document.getElementById('mobile'),
        password: document.getElementById('password'),
        appToken: document.getElementById('appToken'),
        incomingDidoxId: document.getElementById('incomingDidoxId'),
        incomingToSign: document.getElementById('incomingToSign'),
        createOrderAutosignBtn: document.getElementById('createOrderAutosignBtn'),
        getIncomingListBtn: document.getElementById('getIncomingListBtn'),
        getIncomingToSignBtn: document.getElementById('getIncomingToSignBtn'),
        acceptIncomingBtn: document.getElementById('acceptIncomingBtn'),
        status: document.getElementById('status'),
        result: document.getElementById('result'),
        debug: document.getElementById('debug')
    };

    const state = {
        certs: {},
        selectedCert: null,
        pkcs7: null,
        signatureHex: null,
        taxId: null,
        didoxToken: null,
        authPayload: null,
        appToken: null,
        incomingSignPayloads: [],
        incomingDocumentBase64: ''
    };

    function setStatus(msg, type) {
        els.status.textContent = msg;
        els.status.className = type === 'error' ? 'err' : (type === 'success' ? 'ok' : 'muted');
    }

    function setResult(obj) {
        els.result.textContent = typeof obj === 'string' ? obj : JSON.stringify(obj, null, 2);
    }

    function log(title, data) {
        const m = `[${new Date().toISOString()}] ${title}` + (data ? `\n${JSON.stringify(data, null, 2)}` : '');
        els.debug.value = `${m}\n\n${els.debug.value}`;
    }

    function parseTaxId(cert) {
        if (!cert) return null;
        if (cert.TIN) return cert.TIN;
        if (cert.UID && /^\d{9}$/.test(cert.UID)) return cert.UID;
        if (cert.alias) {
            let m = cert.alias.match(/1\.2\.860\.3\.16\.1\.1=(\d{9})/);
            if (m) return m[1];
            m = cert.alias.match(/uid=(\d{9})/i);
            if (m) return m[1];
        }
        return null;
    }

    async function postJson(url, payload) {
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload || {})
        });
        const text = await res.text();
        let data = {};
        try { data = text ? JSON.parse(text) : {}; } catch (e) {
            if (text && /<html|<!doctype/i.test(text)) {
                throw new Error('Endpoint JSON qaytarmadi (HTML qaytdi). URL yoki auth middleware tekshiring.');
            }
            throw new Error(`Invalid JSON: ${text}`);
        }
        if (!res.ok || data.success === false) {
            throw new Error(data.message || JSON.stringify(data.errors || data));
        }
        return data;
    }

    async function requestJson(url, options = {}) {
        const res = await fetch(url, options);
        const text = await res.text();
        let data = {};
        try { data = text ? JSON.parse(text) : {}; } catch (e) {
            throw new Error(`Invalid JSON: ${text}`);
        }
        if (!res.ok || data.success === false) {
            const msg = data.message || JSON.stringify(data.errors || data);
            const details = data.data || data.debug || data;
            throw new Error(`${msg} | details: ${JSON.stringify(details)}`);
        }
        return data;
    }

    function getAppToken() {
        return (els.appToken.value || state.appToken || '').trim();
    }

    function authHeaders() {
        const t = getAppToken();
        if (!t) throw new Error('App token topilmadi. Avval /api/user/eimzo-login qiling.');
        return { 'Authorization': `Bearer ${t}` };
    }

    function appLoad() {
        return new Promise((resolve, reject) => {
            if (typeof EIMZOClient === 'undefined') {
                reject(new Error('EIMZOClient is not loaded'));
                return;
            }

            EIMZOClient.API_KEYS = API_KEYS.slice();
            setStatus('Checking E-IMZO version...', 'info');

            EIMZOClient.checkVersion(function () {
                setStatus('Installing API keys...', 'info');

                EIMZOClient.installApiKeys(function () {
                    setStatus('Loading certificates...', 'info');

                    EIMZOClient.listAllUserKeys(
                        function(o, i) { return `itm-${o.serialNumber || 'cert'}-${i}`; },
                        function(itemId, cert) {
                            return {
                                id: itemId,
                                type: cert.type,
                                disk: cert.disk,
                                path: cert.path,
                                name: cert.name,
                                alias: cert.alias,
                                serialNumber: cert.serialNumber,
                                validTo: cert.validTo,
                                CN: cert.CN,
                                TIN: cert.TIN,
                                UID: cert.UID,
                                raw: cert
                            };
                        },
                        function(items) { resolve(items); },
                        function(e, reason) { reject(new Error(reason || e || 'listAllUserKeys failed')); }
                    );
                }, function(e, reason) { reject(new Error(reason || e || 'installApiKeys failed')); });
            }, function(e, reason) { reject(new Error(reason || e || 'checkVersion failed')); });
        });
    }

    function toBase64Utf8(value) {
        return btoa(unescape(encodeURIComponent(String(value))));
    }

    function authWithKeyId(keyId, data64, attached = 'no') {
        return new Promise((resolve, reject) => {
            if (typeof CAPIWS === 'undefined' || typeof CAPIWS.callFunction !== 'function') {
                reject(new Error('CAPIWS not available'));
                return;
            }

            CAPIWS.callFunction({
                plugin: 'pkcs7',
                name: 'create_pkcs7',
                arguments: [data64, keyId, attached]
            }, function (event, data) {
                if (!data || data.success !== true) {
                    reject(new Error((data && data.reason) || 'create_pkcs7 failed'));
                    return;
                }

                const pkcs7 = data.pkcs7_64 || null;
                const signatureHex = data.signature_hex || null;
                const signerSerialNumber = data.signer_serial_number || null;

                if (!pkcs7) {
                    reject(new Error('pkcs7_64 topilmadi'));
                    return;
                }

                resolve({ pkcs7, signatureHex, signerSerialNumber });
            }, function (error) {
                reject(new Error(error || 'WebSocket error while create_pkcs7'));
            });
        });
    }

    function attachTimestampTokenToPkcs7(pkcs7b64, signerSerialNumber, timestampTokenB64) {
        return new Promise((resolve, reject) => {
            if (typeof CAPIWS === 'undefined' || typeof CAPIWS.callFunction !== 'function') {
                reject(new Error('CAPIWS not available'));
                return;
            }
            if (!pkcs7b64 || !signerSerialNumber || !timestampTokenB64) {
                reject(new Error('attach_timestamp_token_pkcs7: required params missing'));
                return;
            }

            CAPIWS.callFunction({
                plugin: 'pkcs7',
                name: 'attach_timestamp_token_pkcs7',
                arguments: [pkcs7b64, signerSerialNumber, timestampTokenB64]
            }, function (event, data) {
                if (!data || data.success !== true || !data.pkcs7_64) {
                    reject(new Error((data && data.reason) || 'attach_timestamp_token_pkcs7 failed'));
                    return;
                }
                resolve(data.pkcs7_64);
            }, function (error) {
                reject(new Error(error || 'WebSocket error while attach_timestamp_token_pkcs7'));
            });
        });
    }

    async function createDidoxTimestamp(pkcs7, signatureHex) {
        const r = await postJson('/api/didox/timestamp', {
            pkcs7: pkcs7,
            signature_hex: signatureHex
        });
        const data = r.data || {};
        const nested = data.data || {};
        const didoxTsSuccess = (typeof data.success === 'boolean') ? data.success : true;
        if (!didoxTsSuccess) {
            throw new Error(`Didox timestamp failed: ${JSON.stringify(data)}`);
        }
        const timeStampTokenB64 =
            data.timeStampTokenB64 ||
            nested.timeStampTokenB64 ||
            null;
        const pkcs7b64 =
            data.pkcs7b64 ||
            nested.pkcs7b64 ||
            null;
        if (!timeStampTokenB64 && !pkcs7b64) {
            throw new Error(`timeStampTokenB64 not returned. Response: ${JSON.stringify(r)}`);
        }
        return { timeStampTokenB64, pkcs7b64, raw: r };
    }

    function collectSignatureCandidatesFromObject(obj) {
        const out = [];
        const seen = new Set();
        const keyHints = ['timestamp', 'token', 'pkcs7', 'signature'];

        function maybePush(v) {
            if (typeof v !== 'string') return;
            const s = v.trim();
            if (!s || s.length < 500) return;
            if (seen.has(s)) return;
            // Heuristic: likely base64-ish payload
            if (!/^[A-Za-z0-9+/=]+$/.test(s)) return;
            seen.add(s);
            out.push(s);
        }

        function walk(node, parentKey = '') {
            if (node == null) return;
            if (Array.isArray(node)) {
                node.forEach((v) => walk(v, parentKey));
                return;
            }
            if (typeof node === 'object') {
                Object.entries(node).forEach(([k, v]) => {
                    const lk = String(k).toLowerCase();
                    if (typeof v === 'string') {
                        if (keyHints.some((h) => lk.includes(h))) {
                            maybePush(v);
                        } else {
                            // keep as fallback too
                            maybePush(v);
                        }
                    } else {
                        walk(v, lk);
                    }
                });
                return;
            }
            if (typeof node === 'string') {
                maybePush(node);
            }
        }

        walk(obj);
        return out;
    }

    function tryExtractJsonBase64FromToSign(toSignB64) {
        try {
            const raw = atob(String(toSignB64 || '').trim());
            const start = raw.indexOf('{');
            const end = raw.lastIndexOf('}');
            if (start < 0 || end <= start) return null;
            const jsonCandidate = raw.slice(start, end + 1);
            JSON.parse(jsonCandidate);
            return btoa(unescape(encodeURIComponent(jsonCandidate)));
        } catch (e) {
            return null;
        }
    }

    async function backendAuthDidox(taxId, signature) {
        const res = await postJson('/api/didox/authenticate-eimzo', {
            taxId: taxId,
            signature: signature
        });
        // Keep backward-compatible shape for existing UI code.
        return {
            success: true,
            token: res.token,
            data: res.data || null,
            message: res.message || 'DIDOX auth success'
        };
    }

    function fillCerts(items) {
        state.certs = {};
        els.keySelect.innerHTML = '<option value="">Choose a certificate...</option>';

        items.forEach((c) => {
            state.certs[c.id] = c;
            const tax = parseTaxId(c);
            const opt = document.createElement('option');
            opt.value = c.id;
            opt.textContent = `${c.CN || c.name || c.id}${tax ? ` | INN: ${tax}` : ''}`;
            els.keySelect.appendChild(opt);
        });

        els.signinPfxBtn.disabled = items.length === 0;
        els.signinTokenBtn.disabled = false;
    }

    async function signinPFX() {
        const certId = els.keySelect.value;
        if (!certId) throw new Error('Certificate tanlanmagan');

        const cert = state.certs[certId];
        if (!cert) throw new Error('Certificate not found');

        state.selectedCert = cert;
        state.taxId = parseTaxId(cert);

        if (!state.taxId || !/^\d{9}$/.test(state.taxId)) {
            throw new Error('Certificate dan yaroqli TIN (9 xonali) topilmadi');
        }
        const payloadToSign = toBase64Utf8(state.taxId);
        setStatus('TIN prepared (base64), loading key...', 'info');

        const keyId = await new Promise((resolve, reject) => {
            EIMZOClient.loadKey(cert.raw || cert, function(id) { resolve(id); }, function(e, reason) {
                reject(new Error(reason || e || 'loadKey failed'));
            }, false);
        });

        setStatus('Signing base64(TIN)...', 'info');
        const signed = await authWithKeyId(keyId, payloadToSign);
        state.pkcs7 = signed.pkcs7;
        state.signatureHex = signed.signatureHex;
        if (!state.signatureHex) throw new Error('signature_hex topilmadi (create_pkcs7 response)');

        setStatus('Attaching Didox timestamp...', 'info');
        const timestampedSignature = await createDidoxTimestamp(state.pkcs7, state.signatureHex);
        const authSignature = timestampedSignature.timeStampTokenB64;
        if (!authSignature) throw new Error('Didox timestamp token (timeStampTokenB64) topilmadi');

        setStatus('Authenticating via DIDOX...', 'info');
        const auth = await backendAuthDidox(state.taxId, authSignature);

        state.didoxToken = auth.token || null;
        state.authPayload = auth;

        els.registerBtn.disabled = false;
        els.loginBtn.disabled = false;

        setStatus('DIDOX auth success', 'success');
        setResult(auth);
        log('signinPFX success', { auth, taxId: state.taxId });
    }

    async function signinToken() {
        if (!state.taxId || !/^\d{9}$/.test(state.taxId)) {
            throw new Error('Token login uchun TIN kerak. PFX sertifikat tanlang yoki TIN ni aniqlang.');
        }
        const payloadToSign = toBase64Utf8(state.taxId);
        setStatus('Signing with token ckc (base64(TIN))...', 'info');

        const signed = await authWithKeyId('ckc', payloadToSign);
        state.pkcs7 = signed.pkcs7;
        state.signatureHex = signed.signatureHex;
        if (!state.signatureHex) throw new Error('signature_hex topilmadi (create_pkcs7 response)');

        setStatus('Attaching Didox timestamp...', 'info');
        const timestampedSignature = await createDidoxTimestamp(state.pkcs7, state.signatureHex);
        const authSignature = timestampedSignature.timeStampTokenB64;
        if (!authSignature) throw new Error('Didox timestamp token (timeStampTokenB64) topilmadi');

        const taxIdFromAuth = state.taxId || null;
        setStatus('Authenticating via DIDOX...', 'info');
        const auth = await backendAuthDidox(taxIdFromAuth, authSignature);

        state.didoxToken = auth.token || null;
        state.authPayload = auth;

        els.registerBtn.disabled = false;
        els.loginBtn.disabled = false;

        setStatus('Token auth success', 'success');
        setResult(auth);
        log('signinToken success', auth);
    }

    async function registerViaApi() {
        if (!state.pkcs7) throw new Error('Avval Signin qiling');
        if (!els.email.value.trim() || !els.mobile.value.trim() || !els.password.value.trim()) {
            throw new Error('Email/mobile/password kiriting');
        }

        const ts = await postJson('/api/eimzo/timestamp', { pkcs7b64: state.pkcs7 });
        const finalSignature = (ts.data && ts.data.pkcs7b64) ? ts.data.pkcs7b64 : null;
        if (!finalSignature) throw new Error('Timestamp failed');

        const taxId = state.taxId || (state.authPayload && state.authPayload.user && state.authPayload.user.eimzo_tax_id);
        if (!taxId) throw new Error('Tax ID topilmadi');

        const payload = {
            tax_id: taxId,
            email: els.email.value.trim(),
            mobile: els.mobile.value.trim(),
            password: els.password.value.trim(),
            user_type: els.userType.value,
            pkcs7_64: state.pkcs7,
            signature_hex: state.signatureHex || '00',
            final_signature: finalSignature,
            certificate_info: state.selectedCert || {}
        };

        const res = await postJson('/api/user/eimzo-register', payload);
        setStatus('eimzo-register success', 'success');
        setResult(res);
        log('eimzo-register', res);
    }

    async function loginViaApi() {
        const taxId = state.taxId || (state.authPayload && state.authPayload.user && state.authPayload.user.eimzo_tax_id);
        if (!taxId) throw new Error('Tax ID topilmadi');
        if (!state.didoxToken) throw new Error('didox_token topilmadi. authenticate-eimzo javobini tekshiring.');

        const payload = {
            didox_token: state.didoxToken,
            tax_id: taxId,
            certificate_info: state.selectedCert || {}
        };

        const res = await postJson('/api/user/eimzo-login', payload);
        state.appToken = res?.data?.token || null;
        els.appToken.value = state.appToken || '';
        const enabled = !!state.appToken;
        els.createOrderAutosignBtn.disabled = !enabled;
        els.getIncomingListBtn.disabled = !enabled;
        els.getIncomingToSignBtn.disabled = !enabled;
        els.acceptIncomingBtn.disabled = !enabled;
        setStatus('eimzo-login success', 'success');
        setResult(res);
        log('eimzo-login', res);
    }

    async function createOrderAutosign() {
        const res = await requestJson('/api/didox/create-test-order-autosign', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                ...authHeaders()
            },
            body: JSON.stringify({})
        });
        setStatus('create order + autosign success', 'success');
        setResult(res);
        log('createOrderAutosign', res);
    }

    async function getIncomingDocuments() {
        // Main didox flow: status=1 (Ожидает подписи партнера)
        const res = await requestJson('/api/didox/my-documents?status=1', {
            method: 'GET',
            headers: { ...authHeaders() }
        });
        const docs = Array.isArray(res.data) ? res.data : [];

        const preferred = docs[0];

        if (preferred && preferred.didox_id) {
            els.incomingDidoxId.value = preferred.didox_id;
        }
        setStatus(`incoming docs: ${docs.length}`, 'success');
        setResult(res);
        log('getIncomingDocuments', res);
    }

    async function getIncomingToSign() {
        const didoxId = (els.incomingDidoxId.value || '').trim();
        if (!didoxId) throw new Error('incoming didox_id kiriting');
        const res = await requestJson('/api/didox/get-incoming-document-for-signing', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                ...authHeaders()
            },
            body: JSON.stringify({
                didox_id: didoxId
            })
        });
        const toSign = res?.data?.to_sign_value || '';
        state.incomingDocumentBase64 = String(res?.data?.document_base64 || '').trim();
        if (!state.incomingDocumentBase64 && toSign) {
            const extracted = tryExtractJsonBase64FromToSign(toSign);
            if (extracted) {
                state.incomingDocumentBase64 = extracted;
                log('incoming document_base64 fallback from toSign', {
                    toSignLength: toSign.length,
                    extractedLength: extracted.length
                });
            }
        }
        state.incomingSignPayloads = toSign ? [toSign] : [];
        // Show the actual first payload that will be signed.
        els.incomingToSign.value = toSign;
        log('incoming sign payload', { length: toSign ? toSign.length : 0 });
        log('incoming document_base64', {
            found: !!state.incomingDocumentBase64,
            length: state.incomingDocumentBase64 ? state.incomingDocumentBase64.length : 0
        });
        setStatus('incoming toSign loaded', 'success');
        setResult(res);
        log('getIncomingToSign', res);
    }

    async function acceptIncoming() {
        const didoxId = (els.incomingDidoxId.value || '').trim();
        const toSignValue = (els.incomingToSign.value || '').trim();
        if (!didoxId) throw new Error('incoming didox_id kiriting');
        if (!toSignValue && !state.incomingSignPayloads.length) {
            throw new Error('Sign payload bo`sh. Avval Get Incoming toSign qiling.');
        }

        const certId = els.keySelect.value;
        if (!certId) throw new Error('Certificate tanlanmagan');
        const cert = state.certs[certId];
        if (!cert) throw new Error('Certificate topilmadi');

        const keyId = await new Promise((resolve, reject) => {
            EIMZOClient.loadKey(cert.raw || cert, function(id) { resolve(id); }, function(e, reason) {
                reject(new Error(reason || e || 'loadKey failed'));
            }, false);
        });

        const payloadsBase = (state.incomingSignPayloads.length ? state.incomingSignPayloads : [toSignValue]).map(v => String(v || '').trim()).filter(Boolean);
        const toSignPayload = payloadsBase[0];
        if (!toSignPayload) throw new Error('toSign payload topilmadi');
        if (!state.incomingDocumentBase64 && toSignPayload) {
            const extracted = tryExtractJsonBase64FromToSign(toSignPayload);
            if (extracted) {
                state.incomingDocumentBase64 = extracted;
                log('acceptIncoming document_base64 fallback from payload', {
                    toSignLength: toSignPayload.length,
                    extractedLength: extracted.length
                });
            }
        }
        if (!state.incomingDocumentBase64) throw new Error('documentBase64 topilmadi. Avval 8 ni qayta bosing.');

        // signature1: signed toSign payload (step 4 signature)
        const signature1Candidates = [];
        const pushSig1 = (v) => {
            const s = String(v || '').trim();
            if (!s) return;
            if (!signature1Candidates.includes(s)) signature1Candidates.push(s);
        };
        // Important: DIDOX /dsvs/signature/join may expect raw toSign payload as signature1.
        pushSig1(toSignPayload);
        setStatus(`Signing toSign payload (len=${toSignPayload.length})...`, 'info');
        const signedToSignNo = await authWithKeyId(keyId, toSignPayload, 'no');
        pushSig1(signedToSignNo.pkcs7);
        try {
            const ts1 = await createDidoxTimestamp(signedToSignNo.pkcs7, signedToSignNo.signatureHex);
            pushSig1(ts1.pkcs7b64 || '');
            pushSig1(ts1.timeStampTokenB64 || '');
        } catch (e) {
            log('toSign didox timestamp warning', { error: e.message || String(e) });
        }
        try {
            const signedToSignYes = await authWithKeyId(keyId, toSignPayload, 'yes');
            pushSig1(signedToSignYes.pkcs7);
        } catch (e) {
            log('toSign attached=yes warning', { error: e.message || String(e) });
        }
        const signature1 = signature1Candidates[0] || '';
        if (!signature1) throw new Error('signature1 (toSign signature) topilmadi');

        // signature2: signed documentBase64 + timestamp (step 3 signature)
        setStatus(`Signing documentBase64 payload (len=${state.incomingDocumentBase64.length})...`, 'info');
        const signedDoc = await authWithKeyId(keyId, state.incomingDocumentBase64, 'no');
        if (!signedDoc.pkcs7) throw new Error('documentBase64 PKCS7 topilmadi');

        let signature2 = '';
        const signature2Candidates = [];
        const pushSig2 = (v) => {
            const s = String(v || '').trim();
            if (!s) return;
            if (!signature2Candidates.includes(s)) signature2Candidates.push(s);
        };
        const didoxTs = await createDidoxTimestamp(signedDoc.pkcs7, signedDoc.signatureHex);
        const token = String(didoxTs.timeStampTokenB64 || '').trim();
        const tsPkcs7 = String(didoxTs.pkcs7b64 || '').trim();
        log('doc didox timestamp payload', {
            hasToken: !!token,
            tokenLength: token ? token.length : 0,
            hasPkcs7b64: !!tsPkcs7,
            pkcs7b64Length: tsPkcs7 ? tsPkcs7.length : 0
        });

        // Stable/default path: use Didox-provided timestamp token as signature2.
        if (token) {
            signature2 = token;
            pushSig2(signature2);
            log('doc signature2 primary', {
                mode: 'didox_timeStampTokenB64',
                length: signature2.length
            });
        }

        // Optional attach path (best-effort): if E-IMZO attach works, keep as higher-priority candidate.
        if (token && signedDoc.signerSerialNumber) {
            try {
                const attachedPkcs7 = String(await attachTimestampTokenToPkcs7(signedDoc.pkcs7, signedDoc.signerSerialNumber, token) || '').trim();
                if (attachedPkcs7) {
                    signature2 = attachedPkcs7;
                    pushSig2(attachedPkcs7);
                }
            } catch (attachError) {
                log('doc attach timestamp warning', {
                    error: attachError.message || String(attachError),
                    fallback: 'use_didox_pkcs7b64'
                });
            }
        }

        // Fallback path: local timestamp endpoint (when token-based path is unavailable).
        if (!signature2) {
            try {
                const eimzoTs = await postJson('/api/eimzo/timestamp', { pkcs7b64: signedDoc.pkcs7 });
                const eimzoPkcs7 = String(eimzoTs?.data?.pkcs7b64 || '').trim();
                if (eimzoPkcs7) {
                    signature2 = eimzoPkcs7;
                    pushSig2(eimzoPkcs7);
                }
            } catch (eimzoTsErr) {
                log('doc timestamp via /api/eimzo/timestamp warning', { error: eimzoTsErr.message || String(eimzoTsErr) });
            }
        }

        if (!signature2 && tsPkcs7) signature2 = tsPkcs7;
        pushSig2(tsPkcs7);
        // Also try plain signed document PKCS7 as candidate for join digest compatibility.
        pushSig2(signedDoc.pkcs7);
        if (!signature2) throw new Error('signature2 (document+timestamp) topilmadi');

        log('acceptIncoming join inputs', {
            signature1_length: signature1.length,
            signature1_candidates: signature1Candidates.map((s, i) => ({ index: i, length: s.length })),
            signature2_length: signature2.length,
            signature2_candidates: signature2Candidates.map((s, i) => ({ index: i, length: s.length }))
        });

        const res = await requestJson('/api/didox/accept-incoming-document', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                ...authHeaders()
            },
            body: JSON.stringify({
                didox_id: didoxId,
                signature1: signature1,
                signature2: signature2
            })
        });
        setStatus('accept incoming success', 'success');
        setResult(res);
        log('acceptIncoming', res);
    }

    els.initBtn.addEventListener('click', async () => {
        try {
            const items = await appLoad();
            fillCerts(items);
            setStatus(`Certificates loaded: ${items.length}`, 'success');
            log('AppLoad success', { count: items.length });
        } catch (e) {
            setStatus(e.message || String(e), 'error');
            log('AppLoad error', { error: e.message || String(e) });
        }
    });

    els.signinPfxBtn.addEventListener('click', async () => {
        try { await signinPFX(); } catch (e) { setStatus(e.message || String(e), 'error'); log('signinPFX error', { error: e.message || String(e) }); }
    });

    els.signinTokenBtn.addEventListener('click', async () => {
        try { await signinToken(); } catch (e) { setStatus(e.message || String(e), 'error'); log('signinToken error', { error: e.message || String(e) }); }
    });

    els.registerBtn.addEventListener('click', async () => {
        try { await registerViaApi(); } catch (e) { setStatus(e.message || String(e), 'error'); log('register error', { error: e.message || String(e) }); }
    });

    els.loginBtn.addEventListener('click', async () => {
        try { await loginViaApi(); } catch (e) { setStatus(e.message || String(e), 'error'); log('login error', { error: e.message || String(e) }); }
    });

    els.createOrderAutosignBtn.addEventListener('click', async () => {
        try { await createOrderAutosign(); } catch (e) { setStatus(e.message || String(e), 'error'); log('createOrderAutosign error', { error: e.message || String(e) }); }
    });

    els.getIncomingListBtn.addEventListener('click', async () => {
        try { await getIncomingDocuments(); } catch (e) { setStatus(e.message || String(e), 'error'); log('getIncomingDocuments error', { error: e.message || String(e) }); }
    });

    els.getIncomingToSignBtn.addEventListener('click', async () => {
        try { await getIncomingToSign(); } catch (e) { setStatus(e.message || String(e), 'error'); log('getIncomingToSign error', { error: e.message || String(e) }); }
    });

    els.acceptIncomingBtn.addEventListener('click', async () => {
        try { await acceptIncoming(); } catch (e) { setStatus(e.message || String(e), 'error'); log('acceptIncoming error', { error: e.message || String(e) }); }
    });
})();
</script>
</body>
</html>
