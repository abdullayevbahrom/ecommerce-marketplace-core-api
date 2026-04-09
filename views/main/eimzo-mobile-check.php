<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>E-IMZO Mobile Check</title>
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

        * {
            box-sizing: border-box;
        }

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
            max-width: 980px;
            margin: 0 auto;
            padding: 32px 20px 48px;
        }

        .hero {
            margin-bottom: 24px;
        }

        .hero h1 {
            margin: 0 0 10px;
            font-size: 38px;
            line-height: 1.05;
        }

        .hero p {
            margin: 0;
            color: var(--muted);
            font-size: 17px;
        }

        .grid {
            display: grid;
            grid-template-columns: minmax(0, 1.7fr) minmax(340px, 0.9fr);
            gap: 20px;
        }

        .mapping {
            margin-top: 18px;
            border-top: 1px solid var(--border);
            padding-top: 16px;
        }

        .mapping-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        .mapping-table th,
        .mapping-table td {
            text-align: left;
            vertical-align: top;
            padding: 9px 10px;
            border-bottom: 1px solid #ece5d8;
        }

        .mapping-table th {
            color: var(--muted);
            font-weight: normal;
        }

        .card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 18px;
            box-shadow: 0 10px 30px rgba(34, 39, 34, 0.06);
        }

        .card h2 {
            margin: 0 0 14px;
            font-size: 20px;
        }

        .controls {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 14px;
        }

        button,
        select {
            font: inherit;
        }

        button {
            border: 0;
            border-radius: 999px;
            padding: 11px 16px;
            cursor: pointer;
            background: var(--accent);
            color: #fff;
        }

        button.secondary {
            background: #e8e0d3;
            color: var(--text);
        }

        button:disabled {
            opacity: 0.55;
            cursor: not-allowed;
        }

        select {
            border: 1px solid var(--border);
            border-radius: 999px;
            padding: 10px 14px;
            background: #fff;
        }

        .status {
            border-radius: 14px;
            padding: 12px 14px;
            background: #eef4ef;
            color: var(--text);
            margin-bottom: 16px;
            border: 1px solid #d9e5db;
        }

        .status.info { background: #eef4ef; border-color: #d9e5db; }
        .status.warn { background: #fff5df; border-color: #ead7a8; color: var(--warn); }
        .status.error { background: #fff0f0; border-color: #efcaca; color: var(--err); }
        .status.success { background: #edf8f0; border-color: #cfe6d5; color: var(--ok); }

        .kv {
            display: grid;
            grid-template-columns: 160px 1fr;
            gap: 8px 14px;
            margin-bottom: 18px;
            font-size: 15px;
        }

        .kv div:nth-child(odd) {
            color: var(--muted);
        }

        .mono,
        textarea,
        pre {
            font-family: "SFMono-Regular", Consolas, "Liberation Mono", Menlo, monospace;
        }

        textarea,
        pre {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 12px;
            background: #fcfaf5;
            color: var(--text);
            font-size: 13px;
            line-height: 1.45;
        }

        textarea {
            min-height: 96px;
            resize: vertical;
        }

        .qr-box {
            display: grid;
            gap: 12px;
            justify-items: center;
        }

        .qr-img {
            width: min(100%, 360px);
            aspect-ratio: 1;
            border-radius: 18px;
            border: 1px solid var(--border);
            background: #fff;
            padding: 14px;
            object-fit: contain;
        }

        .link {
            color: var(--accent-dark);
            word-break: break-all;
            display: block;
            font-size: 15px;
            line-height: 1.5;
        }

        .small {
            color: var(--muted);
            font-size: 13px;
        }

        .qr-box > div {
            width: 100%;
        }

        #qrPayload {
            min-height: 180px;
        }

        @media (max-width: 820px) {
            .grid {
                grid-template-columns: 1fr;
            }

            .hero h1 {
                font-size: 30px;
            }

            .qr-img {
                width: min(100%, 320px);
            }
        }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="hero">
            <h1>E-IMZO Mobile Flow Check</h1>
            <p>`/api/eimzo/mobile/auth -> digest -> deeplink/QR -> status -> auth-result` flow ni brauzerdan tekshirish uchun sahifa.</p>
        </div>

        <div class="grid">
            <div class="card">
                <h2>Flow</h2>
                <div id="status" class="status info">Ready.</div>
                <div class="controls">
                    <select id="userType">
                        <option value="fiz">fiz</option>
                        <option value="yur">yur</option>
                    </select>
                    <button id="startBtn">Start Mobile Auth</button>
                    <button id="pollBtn" class="secondary" disabled>Poll Once</button>
                    <button id="resultBtn" class="secondary" disabled>Get Auth Result</button>
                </div>
                <div class="kv">
                    <div>siteId</div><div id="siteId">-</div>
                    <div>documentId</div><div id="documentId">-</div>
                    <div>challenge</div><div id="challenge" class="mono">-</div>
                    <div>digestHex</div><div id="digestHex" class="mono">-</div>
                    <div>status</div><div id="flowStatus">-</div>
                </div>
                <div>
                    <div class="small">Auth Result</div>
                    <pre id="resultBox">No result yet.</pre>
                </div>

                <div class="mapping">
                    <div class="small" style="margin-bottom: 10px;">Demo endpoint mapping</div>
                    <table class="mapping-table">
                        <thead>
                        <tr>
                            <th>Demo</th>
                            <th>This page</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td class="mono">/frontend/mobile/auth</td>
                            <td class="mono">POST /api/eimzo/mobile/auth</td>
                        </tr>
                        <tr>
                            <td class="mono">/frontend/mobile/status</td>
                            <td class="mono">POST /api/eimzo/mobile/status</td>
                        </tr>
                        <tr>
                            <td class="mono">/backend/mobile/authenticate/{documentId}</td>
                            <td class="mono">POST /api/eimzo/mobile/auth-result</td>
                        </tr>
                        <tr>
                            <td class="mono">/backend/mobile/verify</td>
                            <td class="mono">Not used on this auth check page</td>
                        </tr>
                        <tr>
                            <td class="mono">UPLOAD URL</td>
                            <td class="mono">/v1/integration/eimzo -> /frontend/mobile/upload</td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <h2>QR And Deeplink</h2>
                <div class="qr-box">
                    <img id="qrImg" class="qr-img" alt="QR code" />
                    <div>
                        <div class="small">Deep Link</div>
                        <a id="deepLink" class="link" href="#" target="_blank" rel="noopener">-</a>
                    </div>
                    <div>
                        <div class="small">QR Payload</div>
                        <textarea id="qrPayload" readonly></textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="/eimzo-mobile-pkcs.js"></script>
    <script src="/eimzo-mobile-crc32.js"></script>
    <script src="/eimzo-auth.js"></script>
    <script>
        const auth = new EIMZOYii2Auth(window.location.origin);
        const state = {
            init: null,
            digestHex: null,
            lastStatus: null
        };

        const els = {
            status: document.getElementById("status"),
            startBtn: document.getElementById("startBtn"),
            pollBtn: document.getElementById("pollBtn"),
            resultBtn: document.getElementById("resultBtn"),
            userType: document.getElementById("userType"),
            siteId: document.getElementById("siteId"),
            documentId: document.getElementById("documentId"),
            challenge: document.getElementById("challenge"),
            digestHex: document.getElementById("digestHex"),
            flowStatus: document.getElementById("flowStatus"),
            deepLink: document.getElementById("deepLink"),
            qrPayload: document.getElementById("qrPayload"),
            qrImg: document.getElementById("qrImg"),
            resultBox: document.getElementById("resultBox")
        };

        function setStatus(message, type) {
            els.status.className = "status " + (type || "info");
            els.status.textContent = message;
        }

        function setFlowState(init, digestHex, qr) {
            state.init = init;
            state.digestHex = digestHex;
            els.siteId.textContent = init.siteId || "-";
            els.documentId.textContent = init.documentId || "-";
            els.challenge.textContent = init.challenge || "-";
            els.digestHex.textContent = digestHex || "-";
            els.deepLink.textContent = qr.deepLink || "-";
            els.deepLink.href = qr.deepLink || "#";
            els.qrPayload.value = qr.qrCode || "";
            els.qrImg.src = qr.qrCode
                ? "https://api.qrserver.com/v1/create-qr-code/?size=280x280&data=" + encodeURIComponent(qr.qrCode)
                : "";
            els.pollBtn.disabled = false;
            els.resultBtn.disabled = false;
            els.resultBox.textContent = "No result yet.";
        }

        async function pollOnce() {
            if (!state.init || !state.init.documentId) {
                throw new Error("documentId not available");
            }

            const data = await auth.requestJson(auth.buildUrl("/api/eimzo/mobile/status"), {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ documentId: state.init.documentId })
            });

            const result = data.data || data;
            state.lastStatus = result;
            els.flowStatus.textContent = JSON.stringify(result);

            if (result.status === 1) {
                setStatus("Signature completed. Auth result can be requested.", "success");
            } else if (result.status === 2) {
                setStatus("Still pending. Confirm signature in E-IMZO mobile app.", "warn");
            } else {
                setStatus("Unexpected status: " + JSON.stringify(result), "error");
            }

            return result;
        }

        async function getResult() {
            if (!state.init || !state.init.documentId) {
                throw new Error("documentId not available");
            }

            const response = await auth.requestJson(auth.buildUrl("/api/eimzo/mobile/auth-result"), {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    documentId: state.init.documentId,
                    user_type: els.userType.value
                })
            });

            const data = response.data || response;
            els.resultBox.textContent = JSON.stringify(data, null, 2);
            setStatus("Auth result received.", "success");
            return data;
        }

        els.startBtn.addEventListener("click", async () => {
            try {
                els.startBtn.disabled = true;
                setStatus("Starting mobile auth session...", "info");
                const init = await auth.startMobileAuth();
                const digestHex = auth.hashMobilePayload(init.challenge);
                const qr = auth.buildMobileQrPayload({
                    siteId: init.siteId,
                    documentId: init.documentId,
                    challenge: init.challenge,
                    hashHex: digestHex
                });

                setFlowState(init, digestHex, qr);
                setStatus("Session created. Open deeplink on phone or scan QR in E-IMZO app.", "warn");
            } catch (error) {
                setStatus(error.message || String(error), "error");
            } finally {
                els.startBtn.disabled = false;
            }
        });

        els.pollBtn.addEventListener("click", async () => {
            try {
                setStatus("Checking current mobile status...", "info");
                await pollOnce();
            } catch (error) {
                setStatus(error.message || String(error), "error");
            }
        });

        els.resultBtn.addEventListener("click", async () => {
            try {
                setStatus("Requesting auth result...", "info");
                await getResult();
            } catch (error) {
                els.resultBox.textContent = error.message || String(error);
                setStatus(error.message || String(error), "error");
            }
        });
    </script>
</body>
</html>
