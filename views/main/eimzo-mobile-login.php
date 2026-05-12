<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>E-IMZO Mobile Login</title>
    <style>
        :root {
            --bg: #f4f1ea;
            --card: #fffdf8;
            --text: #1e2a24;
            --muted: #6d746f;
            --accent: #1f7a5a;
            --border: #ddd5c8;
            --ok: #17633f;
            --warn: #8a6200;
            --err: #9b2d2d;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Georgia, "Times New Roman", serif;
            color: var(--text);
            background:
                radial-gradient(circle at top left, rgba(31, 122, 90, .12), transparent 30%),
                radial-gradient(circle at bottom right, rgba(177, 133, 50, .15), transparent 25%),
                var(--bg);
        }
        .wrap { max-width: 980px; margin: 0 auto; padding: 28px 16px 36px; }
        .hero h1 { margin: 0 0 8px; font-size: 34px; }
        .hero p { margin: 0; color: var(--muted); }
        .card {
            margin-top: 16px;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 14px;
            box-shadow: 0 8px 24px rgba(34, 39, 34, .06);
        }
        .status {
            margin-top: 12px;
            border-radius: 12px;
            padding: 10px 12px;
            border: 1px solid #d9e5db;
            background: #eef4ef;
        }
        .status.info { color: var(--text); }
        .status.success { color: var(--ok); background: #edf8f0; border-color: #cfe6d5; }
        .status.warn { color: var(--warn); background: #fff5df; border-color: #ead7a8; }
        .status.error { color: var(--err); background: #fff0f0; border-color: #efcaca; }
        .controls { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 10px; }
        button {
            border: 0;
            border-radius: 999px;
            padding: 10px 14px;
            cursor: pointer;
            background: var(--accent);
            color: #fff;
            font: inherit;
        }
        button.secondary { background: #e8e0d3; color: var(--text); }
        button:disabled { opacity: .55; cursor: not-allowed; }
        .row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin-top: 10px;
        }
        input, select, textarea {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 9px 10px;
            font: inherit;
            color: var(--text);
            background: #fff;
        }
        .small { color: var(--muted); font-size: 12px; }
        .qr-wrap {
            margin-top: 10px;
            display: grid;
            grid-template-columns: minmax(0, 1fr) 220px;
            gap: 10px;
            align-items: start;
        }
        pre, textarea {
            font-family: "SFMono-Regular", Consolas, "Liberation Mono", Menlo, monospace;
            font-size: 12.5px;
            line-height: 1.42;
        }
        pre {
            margin: 0;
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 10px;
            background: #fcfaf5;
            overflow: auto;
        }
        .qr-img {
            width: 220px;
            height: 220px;
            border: 1px solid var(--border);
            border-radius: 12px;
            background: #fff;
            padding: 8px;
            object-fit: contain;
        }
        .log { width: 100%; min-height: 220px; margin-top: 12px; }
        @media (max-width: 860px) {
            .row { grid-template-columns: 1fr; }
            .qr-wrap { grid-template-columns: 1fr; }
            .qr-img { width: 100%; height: auto; aspect-ratio: 1; }
        }
    </style>
</head>
<body>
<div class="wrap">
    <div class="hero">
        <h1>E-IMZO Mobile Login</h1>
        <p>Route: <span class="small">/main/eimzo-mobile-login</span>. API flow: <span class="small">/api/eimzo/mobile/auth → /status → /auth-result</span>.</p>
    </div>

    <div id="globalStatus" class="status info">Ready.</div>

    <div class="card">
        <div class="row">
            <div>
                <label class="small" for="userType">user_type</label>
                <select id="userType">
                    <option value="fiz">fiz</option>
                    <option value="yur">yur</option>
                </select>
            </div>
            <div>
                <label class="small" for="langHeader">lang header</label>
                <input id="langHeader" value="uz">
            </div>
        </div>

        <div class="controls">
            <button id="authStartBtn">1) Start Auth</button>
            <button id="authStatusBtn" class="secondary" disabled>2) Check Status</button>
            <button id="authResultBtn" class="secondary" disabled>3) Get Login Result</button>
        </div>

        <h3 class="small">Auth session</h3>
        <pre id="authSessionBox">No session.</pre>

        <div class="qr-wrap">
            <div>
                <div class="small">deeplink</div>
                <pre id="authDeeplinkBox">-</pre>
                <div class="small" style="margin-top:8px;">qr payload</div>
                <pre id="authQrPayloadBox">-</pre>
            </div>
            <img id="authQrImg" class="qr-img" alt="Auth QR">
        </div>

        <h3 class="small" style="margin-top:12px;">Login result (token + user)</h3>
        <pre id="authResultBox">No auth result.</pre>
    </div>

    <div class="card" style="margin-top: 14px;">
        <div class="small">Request/Response log</div>
        <textarea id="allLog" class="log" readonly></textarea>
    </div>
</div>

<script src="/eimzo-mobile-pkcs.js"></script>
<script src="/eimzo-mobile-crc32.js"></script>
<script src="/eimzo-auth.js"></script>
<script>
    const authClient = new EIMZOYii2Auth(window.location.origin);
    const state = { init: null, status: null, result: null, qr: null };

    const els = {
        globalStatus: document.getElementById("globalStatus"),
        userType: document.getElementById("userType"),
        langHeader: document.getElementById("langHeader"),
        authStartBtn: document.getElementById("authStartBtn"),
        authStatusBtn: document.getElementById("authStatusBtn"),
        authResultBtn: document.getElementById("authResultBtn"),
        authSessionBox: document.getElementById("authSessionBox"),
        authDeeplinkBox: document.getElementById("authDeeplinkBox"),
        authQrPayloadBox: document.getElementById("authQrPayloadBox"),
        authQrImg: document.getElementById("authQrImg"),
        authResultBox: document.getElementById("authResultBox"),
        allLog: document.getElementById("allLog")
    };

    function setGlobalStatus(message, type) {
        els.globalStatus.className = "status " + (type || "info");
        els.globalStatus.textContent = message;
    }

    function jsonPretty(v) {
        try { return JSON.stringify(v, null, 2); } catch (e) { return String(v); }
    }

    function appendLog(item) {
        const line = [
            "\n[" + new Date().toISOString() + "]",
            item.name,
            item.method + " " + item.url,
            "Request headers: " + jsonPretty(item.requestHeaders || {}),
            "Request body: " + (item.requestBody || "-"),
            "Response status: " + item.status,
            "Response body: " + (item.responseBody || "")
        ].join("\n");
        els.allLog.value = (line + "\n" + els.allLog.value).trim();
    }

    function extractMessage(details) {
        if (!details) return "Unknown error";
        if (details.parsed && details.parsed.message) return details.parsed.message;
        return details.responseBody || ("HTTP " + details.status);
    }

    async function requestJson(name, path, options = {}) {
        const url = authClient.buildUrl(path);
        const response = await fetch(url, options);
        const responseBody = await response.text();
        let parsed = null;
        try { parsed = responseBody ? JSON.parse(responseBody) : null; } catch (e) {}

        appendLog({
            name,
            method: options.method || "GET",
            url,
            requestHeaders: options.headers || {},
            requestBody: options.body || "",
            status: response.status,
            responseBody
        });

        if (!response.ok) {
            const err = new Error(name + " failed: " + extractMessage({status: response.status, responseBody, parsed}));
            err.details = {status: response.status, responseBody, parsed, url, name};
            throw err;
        }
        if (parsed && parsed.success === false) {
            const err = new Error(name + " failed: " + (parsed.message || "success=false"));
            err.details = {status: response.status, responseBody, parsed, url, name};
            throw err;
        }
        if (parsed && typeof parsed.error_code === "number" && parsed.error_code !== 0) {
            const err = new Error(name + " failed: " + (parsed.message || ("error_code=" + parsed.error_code)));
            err.details = {status: response.status, responseBody, parsed, url, name};
            throw err;
        }
        return parsed || {};
    }

    function setQr(qrData) {
        els.authDeeplinkBox.textContent = qrData ? (qrData.deepLink || "-") : "-";
        els.authQrPayloadBox.textContent = qrData ? (qrData.qrCode || "-") : "-";
        els.authQrImg.src = qrData && qrData.qrCode
            ? "https://api.qrserver.com/v1/create-qr-code/?size=280x280&data=" + encodeURIComponent(qrData.qrCode)
            : "";
    }

    function showError(error) {
        const lines = [error.message || String(error)];
        if (error.details) {
            lines.push("HTTP: " + error.details.status);
            lines.push("URL: " + error.details.url);
        }
        setGlobalStatus(lines.join(" | "), "error");
    }

    async function startAuth() {
        setGlobalStatus("POST /api/eimzo/mobile/auth yuborilmoqda...", "info");
        const data = await requestJson("mobile/auth", "/api/eimzo/mobile/auth", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "lang": (els.langHeader.value || "uz").trim()
            },
            body: "{}"
        });

        const payload = data.data || data;
        state.init = payload;
        if (!payload.documentId || !payload.challenge || !payload.siteId) {
            throw new Error("auth javobida siteId/documentId/challenge topilmadi");
        }

        const digestHex = authClient.hashMobilePayload(payload.challenge);
        state.qr = authClient.buildMobileQrPayload({
            siteId: payload.siteId,
            documentId: payload.documentId,
            challenge: payload.challenge,
            hashHex: digestHex
        });

        els.authSessionBox.textContent = jsonPretty({...payload, digestHex});
        setQr(state.qr);
        els.authStatusBtn.disabled = false;
        els.authResultBtn.disabled = false;
        setGlobalStatus("Auth session yaratildi. Mobil ilovada QR/deeplink ni tasdiqlang.", "warn");
    }

    async function checkStatus() {
        if (!state.init || !state.init.documentId) throw new Error("Avval Start Auth qiling.");
        setGlobalStatus("POST /api/eimzo/mobile/status tekshirilmoqda...", "info");
        const data = await requestJson("mobile/status", "/api/eimzo/mobile/status", {
            method: "POST",
            headers: {"Content-Type": "application/json"},
            body: JSON.stringify({documentId: state.init.documentId})
        });

        const payload = data.data || data;
        state.status = payload;
        els.authSessionBox.textContent = jsonPretty({...state.init, statusResponse: payload});
        if (payload.status === 1 || payload.complete) {
            setGlobalStatus("Status: imzo yakunланган.", "success");
        } else if (payload.status === 2) {
            setGlobalStatus("Status: pending (mobil ilovada tasdiqlang).", "warn");
        } else {
            setGlobalStatus("Status: kutilmagan javob.", "warn");
        }
    }

    async function getResult() {
        if (!state.init || !state.init.documentId) throw new Error("Avval Start Auth qiling.");
        setGlobalStatus("POST /api/eimzo/mobile/auth-result yuborilmoqda...", "info");
        const data = await requestJson("mobile/auth-result", "/api/eimzo/mobile/auth-result", {
            method: "POST",
            headers: {"Content-Type": "application/json"},
            body: JSON.stringify({
                documentId: state.init.documentId,
                user_type: els.userType.value
            })
        });
        state.result = data.data || data;
        els.authResultBox.textContent = jsonPretty(state.result);
        setGlobalStatus("Login result olindi (token + user).", "success");
    }

    els.authStartBtn.addEventListener("click", async () => {
        els.authStartBtn.disabled = true;
        try { await startAuth(); } catch (e) { showError(e); } finally { els.authStartBtn.disabled = false; }
    });

    els.authStatusBtn.addEventListener("click", async () => {
        try { await checkStatus(); } catch (e) { showError(e); }
    });

    els.authResultBtn.addEventListener("click", async () => {
        try { await getResult(); } catch (e) {
            showError(e);
            els.authResultBox.textContent = e.message || String(e);
        }
    });
</script>
</body>
</html>

