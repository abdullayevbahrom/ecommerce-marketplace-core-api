<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>E-IMZO Mobile Auth And Sign Check</title>
    <style>
        :root {
            --bg: #f3efe7;
            --card: #fffdf8;
            --text: #1f2a1f;
            --muted: #6a746a;
            --accent: #1f7a5a;
            --accent-dark: #125741;
            --border: #d8d1c4;
            --warn: #9a6700;
            --err: #9f2d2d;
            --ok: #17633f;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: Georgia, "Times New Roman", serif;
            color: var(--text);
            background:
                radial-gradient(circle at top left, rgba(31, 122, 90, 0.12), transparent 30%),
                radial-gradient(circle at bottom right, rgba(185, 137, 55, 0.14), transparent 25%),
                var(--bg);
        }

        .wrap {
            max-width: 1220px;
            margin: 0 auto;
            padding: 28px 18px 42px;
        }

        .hero h1 {
            margin: 0 0 8px;
            font-size: 34px;
            line-height: 1.1;
        }

        .hero p {
            margin: 0;
            color: var(--muted);
            font-size: 16px;
        }

        .grid {
            margin-top: 18px;
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
            gap: 14px;
        }

        .card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 14px;
            box-shadow: 0 8px 24px rgba(34, 39, 34, 0.06);
        }

        .card h2 {
            margin: 0 0 10px;
            font-size: 19px;
        }

        .card h3 {
            margin: 14px 0 8px;
            font-size: 14px;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: .03em;
        }

        .controls {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 10px;
        }

        button, input, select, textarea {
            font: inherit;
        }

        button {
            border: 0;
            border-radius: 999px;
            padding: 10px 14px;
            cursor: pointer;
            background: var(--accent);
            color: #fff;
        }

        button.secondary {
            background: #e8e0d3;
            color: var(--text);
        }

        button:disabled {
            opacity: .55;
            cursor: not-allowed;
        }

        input, select, textarea {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 9px 10px;
            background: #fff;
            color: var(--text);
        }

        textarea {
            min-height: 82px;
            resize: vertical;
        }

        .row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin-bottom: 8px;
        }

        .status {
            border-radius: 12px;
            padding: 10px 12px;
            background: #eef4ef;
            border: 1px solid #d9e5db;
            margin-bottom: 10px;
        }

        .status.info { background: #eef4ef; border-color: #d9e5db; color: var(--text); }
        .status.warn { background: #fff5df; border-color: #ead7a8; color: var(--warn); }
        .status.error { background: #fff0f0; border-color: #efcaca; color: var(--err); }
        .status.success { background: #edf8f0; border-color: #cfe6d5; color: var(--ok); }

        .mono, pre, textarea.log {
            font-family: "SFMono-Regular", Consolas, "Liberation Mono", Menlo, monospace;
            font-size: 12.5px;
            line-height: 1.42;
        }

        pre {
            margin: 0;
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 10px;
            background: #fcfaf5;
            overflow: auto;
        }

        .qr-wrap {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 190px;
            gap: 10px;
            align-items: start;
        }

        .qr-img {
            width: 190px;
            height: 190px;
            border-radius: 12px;
            border: 1px solid var(--border);
            background: #fff;
            padding: 10px;
            object-fit: contain;
        }

        .small { color: var(--muted); font-size: 12px; }

        .all-log textarea {
            min-height: 260px;
        }

        @media (max-width: 980px) {
            .grid { grid-template-columns: 1fr; }
            .row { grid-template-columns: 1fr; }
            .qr-wrap { grid-template-columns: 1fr; }
            .qr-img { width: 100%; height: auto; aspect-ratio: 1; }
        }
    </style>
</head>
<body>
<div class="wrap">
    <div class="hero">
        <h1>E-IMZO Mobile API Check</h1>
        <p>
            Route: <span class="mono">/main/eimzo-mobile-check</span>. Bitta sahifada
            <span class="mono">auth</span>, <span class="mono">auth-result</span>,
            <span class="mono">sign</span>, <span class="mono">status</span>,
            <span class="mono">verify</span> ni tekshiradi.
        </p>
    </div>

    <div id="globalStatus" class="status info">Ready.</div>

    <div class="grid">
        <div class="card" id="authCard">
            <h2>Mobile Auth Flow</h2>
            <div class="row">
                <div>
                    <label class="small" for="authUserType">user_type</label>
                    <select id="authUserType">
                        <option value="fiz">fiz</option>
                        <option value="yur">yur</option>
                    </select>
                </div>
                <div>
                    <label class="small" for="langHeader">lang header</label>
                    <input id="langHeader" value="uz" />
                </div>
            </div>
            <div class="controls">
                <button id="authStartBtn">1) POST /api/eimzo/mobile/auth</button>
                <button id="authStatusBtn" class="secondary" disabled>2) POST /api/eimzo/mobile/status</button>
                <button id="authResultBtn" class="secondary" disabled>3) POST /api/eimzo/mobile/auth-result</button>
            </div>

            <h3>Session</h3>
            <pre id="authSessionBox">No auth session.</pre>

            <h3>Deep Link / QR</h3>
            <div class="qr-wrap">
                <div>
                    <div class="small">deeplink</div>
                    <pre id="authDeeplinkBox">-</pre>
                    <div class="small" style="margin-top:8px;">qr payload</div>
                    <pre id="authQrPayloadBox">-</pre>
                </div>
                <img id="authQrImg" class="qr-img" alt="Auth QR" />
            </div>

            <h3>Auth Result</h3>
            <pre id="authResultBox">No auth result.</pre>
        </div>

        <div class="card" id="signCard">
            <h2>Mobile Sign + Verify Flow</h2>
            <div class="row">
                <div>
                    <label class="small" for="bearerToken">Bearer token</label>
                    <input id="bearerToken" placeholder="Bearer token" />
                </div>
                <div>
                    <label class="small" for="signUserType">user_type (verify audit uchun)</label>
                    <select id="signUserType">
                        <option value="fiz">fiz</option>
                        <option value="yur">yur</option>
                    </select>
                </div>
            </div>

            <label class="small" for="documentInput">Document (plain text). Sign/verify uchun shu matn base64 ga o'giriladi.</label>
            <textarea id="documentInput">{"docType":"test","docNo":"A-1001","sum":150000}</textarea>

            <label class="small" for="metaInput">Meta JSON (optional)</label>
            <textarea id="metaInput">{"source":"manual-check","note":"/main/eimzo-mobile-check"}</textarea>

            <div class="controls">
                <button id="signStartBtn">1) POST /api/eimzo/mobile/sign</button>
                <button id="signStatusBtn" class="secondary" disabled>2) POST /api/eimzo/mobile/status</button>
                <button id="signVerifyBtn" class="secondary" disabled>3) POST /api/eimzo/mobile/verify</button>
            </div>

            <h3>Session</h3>
            <pre id="signSessionBox">No sign session.</pre>

            <h3>Deep Link / QR</h3>
            <div class="qr-wrap">
                <div>
                    <div class="small">deeplink</div>
                    <pre id="signDeeplinkBox">-</pre>
                    <div class="small" style="margin-top:8px;">qr payload</div>
                    <pre id="signQrPayloadBox">-</pre>
                </div>
                <img id="signQrImg" class="qr-img" alt="Sign QR" />
            </div>

            <h3>Verify Result</h3>
            <pre id="signVerifyBox">No verify result.</pre>
        </div>
    </div>

    <div class="card all-log" style="margin-top: 14px;">
        <h2>Request/Response Log (Errors Included)</h2>
        <textarea id="allLog" class="log" readonly></textarea>
    </div>
</div>

<script src="/eimzo-mobile-pkcs.js"></script>
<script src="/eimzo-mobile-crc32.js"></script>
<script src="/eimzo-auth.js"></script>
<script>
    const authClient = new EIMZOYii2Auth(window.location.origin);

    const state = {
        auth: {
            init: null,
            status: null,
            result: null,
            qr: null
        },
        sign: {
            init: null,
            status: null,
            verify: null,
            qr: null,
            documentB64: ""
        }
    };

    const els = {
        globalStatus: document.getElementById("globalStatus"),
        allLog: document.getElementById("allLog"),

        authUserType: document.getElementById("authUserType"),
        langHeader: document.getElementById("langHeader"),
        authStartBtn: document.getElementById("authStartBtn"),
        authStatusBtn: document.getElementById("authStatusBtn"),
        authResultBtn: document.getElementById("authResultBtn"),
        authSessionBox: document.getElementById("authSessionBox"),
        authDeeplinkBox: document.getElementById("authDeeplinkBox"),
        authQrPayloadBox: document.getElementById("authQrPayloadBox"),
        authQrImg: document.getElementById("authQrImg"),
        authResultBox: document.getElementById("authResultBox"),

        bearerToken: document.getElementById("bearerToken"),
        signUserType: document.getElementById("signUserType"),
        documentInput: document.getElementById("documentInput"),
        metaInput: document.getElementById("metaInput"),
        signStartBtn: document.getElementById("signStartBtn"),
        signStatusBtn: document.getElementById("signStatusBtn"),
        signVerifyBtn: document.getElementById("signVerifyBtn"),
        signSessionBox: document.getElementById("signSessionBox"),
        signDeeplinkBox: document.getElementById("signDeeplinkBox"),
        signQrPayloadBox: document.getElementById("signQrPayloadBox"),
        signQrImg: document.getElementById("signQrImg"),
        signVerifyBox: document.getElementById("signVerifyBox")
    };

    function setGlobalStatus(message, type) {
        els.globalStatus.className = "status " + (type || "info");
        els.globalStatus.textContent = message;
    }

    function jsonPretty(value) {
        try {
            return JSON.stringify(value, null, 2);
        } catch (error) {
            return String(value);
        }
    }

    function toBase64Utf8(value) {
        return btoa(unescape(encodeURIComponent(value)));
    }

    function appendLog(logObject) {
        const line = [
            "\n[" + new Date().toISOString() + "]",
            logObject.name,
            logObject.method + " " + logObject.url,
            "Request headers: " + jsonPretty(logObject.requestHeaders || {}),
            "Request body: " + (logObject.requestBody || "-"),
            "Response status: " + logObject.status,
            "Response body: " + (logObject.responseBody || "")
        ].join("\n");

        els.allLog.value = (line + "\n" + els.allLog.value).trim();
    }

    function extractMessage(details) {
        if (!details) return "Unknown error";
        if (details.parsed && details.parsed.message) return details.parsed.message;
        if (details.parsed && details.parsed.data && typeof details.parsed.data === "string") return details.parsed.data;
        return details.responseBody || ("HTTP " + details.status);
    }

    async function requestJson(name, path, options = {}) {
        const url = authClient.buildUrl(path);
        const response = await fetch(url, options);
        const responseBody = await response.text();
        let parsed = null;

        try {
            parsed = responseBody ? JSON.parse(responseBody) : null;
        } catch (error) {
            parsed = null;
        }

        const logObject = {
            name,
            method: options.method || "GET",
            url,
            requestHeaders: options.headers || {},
            requestBody: options.body || "",
            status: response.status,
            responseBody
        };
        appendLog(logObject);

        if (!response.ok) {
            const error = new Error(name + " failed: " + extractMessage({ status: response.status, responseBody, parsed }));
            error.details = { status: response.status, responseBody, parsed, url, name };
            throw error;
        }

        if (parsed && parsed.success === false) {
            const error = new Error(name + " failed: " + (parsed.message || "success=false"));
            error.details = { status: response.status, responseBody, parsed, url, name };
            throw error;
        }

        if (parsed && typeof parsed.error_code === "number" && parsed.error_code !== 0) {
            const error = new Error(name + " failed: " + (parsed.message || ("error_code=" + parsed.error_code)));
            error.details = { status: response.status, responseBody, parsed, url, name };
            throw error;
        }

        return parsed || {};
    }

    function setQr(boxDeeplink, boxPayload, img, qrData) {
        boxDeeplink.textContent = qrData ? (qrData.deepLink || "-") : "-";
        boxPayload.textContent = qrData ? (qrData.qrCode || "-") : "-";
        img.src = qrData && qrData.qrCode
            ? "https://api.qrserver.com/v1/create-qr-code/?size=280x280&data=" + encodeURIComponent(qrData.qrCode)
            : "";
    }

    function readMeta() {
        const raw = (els.metaInput.value || "").trim();
        if (!raw) return null;
        try {
            return JSON.parse(raw);
        } catch (error) {
            throw new Error("Meta JSON noto'g'ri formatda");
        }
    }

    function getBearerToken() {
        return (els.bearerToken.value || "").trim();
    }

    function showError(error) {
        const lines = [error.message || String(error)];
        if (error.details) {
            lines.push("HTTP: " + error.details.status);
            lines.push("URL: " + error.details.url);
            lines.push("Body: " + (error.details.responseBody || ""));
        }

        setGlobalStatus(lines.join(" | "), "error");
    }

    async function startAuth() {
        setGlobalStatus("/api/eimzo/mobile/auth yuborilmoqda...", "info");

        const data = await requestJson("mobile/auth", "/api/eimzo/mobile/auth", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "lang": (els.langHeader.value || "uz").trim()
            },
            body: "{}"
        });

        const payload = data.data || data;
        state.auth.init = payload;

        if (!payload.documentId || !payload.challenge || !payload.siteId) {
            throw new Error("auth javobida siteId/documentId/challenge topilmadi");
        }

        const digestHex = authClient.hashMobilePayload(payload.challenge);
        state.auth.qr = authClient.buildMobileQrPayload({
            siteId: payload.siteId,
            documentId: payload.documentId,
            challenge: payload.challenge,
            hashHex: digestHex
        });

        els.authSessionBox.textContent = jsonPretty({ ...payload, digestHex });
        setQr(els.authDeeplinkBox, els.authQrPayloadBox, els.authQrImg, state.auth.qr);
        els.authStatusBtn.disabled = false;
        els.authResultBtn.disabled = false;

        setGlobalStatus("Auth session yaratildi. Mobil ilovada deeplink/QR ni oching.", "warn");
    }

    async function checkAuthStatus() {
        if (!state.auth.init || !state.auth.init.documentId) {
            throw new Error("Auth documentId topilmadi. Avval auth boshlang.");
        }

        setGlobalStatus("/api/eimzo/mobile/status tekshirilmoqda...", "info");

        const data = await requestJson("mobile/status(auth)", "/api/eimzo/mobile/status", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ documentId: state.auth.init.documentId })
        });

        const payload = data.data || data;
        state.auth.status = payload;
        els.authSessionBox.textContent = jsonPretty({ ...state.auth.init, statusResponse: payload });

        if (payload.status === 1 || payload.complete) {
            setGlobalStatus("Auth status: imzo yakunlangan.", "success");
        } else if (payload.status === 2) {
            setGlobalStatus("Auth status: pending (mobil ilovada tasdiqlang).", "warn");
        } else {
            setGlobalStatus("Auth status: kutilmagan javob.", "warn");
        }
    }

    async function getAuthResult() {
        if (!state.auth.init || !state.auth.init.documentId) {
            throw new Error("Auth documentId topilmadi. Avval auth boshlang.");
        }

        setGlobalStatus("/api/eimzo/mobile/auth-result yuborilmoqda...", "info");

        const data = await requestJson("mobile/auth-result", "/api/eimzo/mobile/auth-result", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                documentId: state.auth.init.documentId,
                user_type: els.authUserType.value
            })
        });

        const payload = data.data || data;
        state.auth.result = payload;
        els.authResultBox.textContent = jsonPretty(payload);

        if (payload.token) {
            els.bearerToken.value = payload.token;
        }

        setGlobalStatus("Auth result olindi.", "success");
    }

    async function startSign() {
        const token = getBearerToken();
        if (!token) {
            throw new Error("Bearer token kiriting (auth-result dan ham avtomatik tushadi)");
        }

        const documentRaw = els.documentInput.value || "";
        if (!documentRaw.trim()) {
            throw new Error("Document bo'sh");
        }

        const meta = readMeta();
        const documentB64 = toBase64Utf8(documentRaw);
        state.sign.documentB64 = documentB64;

        const body = { document: documentB64 };
        if (meta) body.meta = meta;

        setGlobalStatus("/api/eimzo/mobile/sign yuborilmoqda...", "info");

        const data = await requestJson("mobile/sign", "/api/eimzo/mobile/sign", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "Authorization": "Bearer " + token,
                "lang": (els.langHeader.value || "uz").trim()
            },
            body: JSON.stringify(body)
        });

        const payload = data.data || data;
        state.sign.init = payload;

        if (!payload.documentId || !payload.challenge || !payload.siteId) {
            throw new Error("sign javobida siteId/documentId/challenge topilmadi");
        }

        const digestHex = authClient.hashMobilePayload(payload.challenge);
        state.sign.qr = authClient.buildMobileQrPayload({
            siteId: payload.siteId,
            documentId: payload.documentId,
            challenge: payload.challenge,
            hashHex: digestHex
        });

        els.signSessionBox.textContent = jsonPretty({ ...payload, digestHex });
        setQr(els.signDeeplinkBox, els.signQrPayloadBox, els.signQrImg, state.sign.qr);

        els.signStatusBtn.disabled = false;
        els.signVerifyBtn.disabled = false;

        setGlobalStatus("Sign session yaratildi. Mobil ilovada deeplink/QR ni imzolang.", "warn");
    }

    async function checkSignStatus() {
        if (!state.sign.init || !state.sign.init.documentId) {
            throw new Error("Sign documentId topilmadi. Avval sign boshlang.");
        }

        setGlobalStatus("/api/eimzo/mobile/status (sign) tekshirilmoqda...", "info");

        const data = await requestJson("mobile/status(sign)", "/api/eimzo/mobile/status", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ documentId: state.sign.init.documentId })
        });

        const payload = data.data || data;
        state.sign.status = payload;
        els.signSessionBox.textContent = jsonPretty({ ...state.sign.init, statusResponse: payload });

        if (payload.status === 1 || payload.complete) {
            setGlobalStatus("Sign status: imzo yakunlangan.", "success");
        } else if (payload.status === 2) {
            setGlobalStatus("Sign status: pending (mobil ilovada tasdiqlang).", "warn");
        } else {
            setGlobalStatus("Sign status: kutilmagan javob.", "warn");
        }
    }

    async function verifySign() {
        const token = getBearerToken();
        if (!token) {
            throw new Error("Bearer token kerak");
        }
        if (!state.sign.init || !state.sign.init.documentId) {
            throw new Error("Sign documentId topilmadi. Avval sign boshlang.");
        }
        if (!state.sign.documentB64) {
            throw new Error("Sign document topilmadi. Avval sign boshlang.");
        }

        setGlobalStatus("/api/eimzo/mobile/verify yuborilmoqda...", "info");

        const data = await requestJson("mobile/verify", "/api/eimzo/mobile/verify", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "Authorization": "Bearer " + token,
                "lang": (els.langHeader.value || "uz").trim()
            },
            body: JSON.stringify({
                documentId: state.sign.init.documentId,
                document: state.sign.documentB64,
                user_type: els.signUserType.value
            })
        });

        const payload = data.data || data;
        state.sign.verify = payload;
        els.signVerifyBox.textContent = jsonPretty(payload);
        setGlobalStatus("Verify result olindi.", "success");
    }

    els.authStartBtn.addEventListener("click", async () => {
        els.authStartBtn.disabled = true;
        try {
            await startAuth();
        } catch (error) {
            showError(error);
        } finally {
            els.authStartBtn.disabled = false;
        }
    });

    els.authStatusBtn.addEventListener("click", async () => {
        try {
            await checkAuthStatus();
        } catch (error) {
            showError(error);
        }
    });

    els.authResultBtn.addEventListener("click", async () => {
        try {
            await getAuthResult();
        } catch (error) {
            showError(error);
            els.authResultBox.textContent = error.message || String(error);
        }
    });

    els.signStartBtn.addEventListener("click", async () => {
        els.signStartBtn.disabled = true;
        try {
            await startSign();
        } catch (error) {
            showError(error);
        } finally {
            els.signStartBtn.disabled = false;
        }
    });

    els.signStatusBtn.addEventListener("click", async () => {
        try {
            await checkSignStatus();
        } catch (error) {
            showError(error);
        }
    });

    els.signVerifyBtn.addEventListener("click", async () => {
        try {
            await verifySign();
        } catch (error) {
            showError(error);
            els.signVerifyBox.textContent = error.message || String(error);
        }
    });
</script>
</body>
</html>
