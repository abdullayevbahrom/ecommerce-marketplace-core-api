/**
 * E-IMZO browser integration.
 *
 * Supported modes:
 * - `didox`  - legacy DIDOX-oriented flow used by admin DIDOX login
 * - `direct` - direct `/api/eimzo/*` flow backed by the local e-imzo-server
 */

class EIMZOAuth {
    constructor(apiBaseUrl, options = {}) {
        this.apiBaseUrl = (apiBaseUrl || window.location.origin || "").replace(/\/+$/, "");
        this.provider = options.provider || "didox";
        this.userType = options.userType || "fiz";
        this.routes = {
            challenge: options.challengePath || "/api/eimzo/challenge",
            timestamp: options.timestampPath || "/api/eimzo/timestamp",
            digest: options.digestPath || "/api/eimzo/digest",
            auth: options.authPath || "/api/eimzo/auth",
            mobileAuth: options.mobileAuthPath || "/api/eimzo/mobile/auth",
            mobileSign: options.mobileSignPath || "/api/eimzo/mobile/sign",
            mobileStatus: options.mobileStatusPath || "/api/eimzo/mobile/status",
            mobileAuthResult: options.mobileAuthResultPath || "/api/eimzo/mobile/auth-result",
            mobileVerify: options.mobileVerifyPath || "/api/eimzo/mobile/verify",
        };
        this.EIMZO_WEBSOCKET = options.websocketUrl || "wss://127.0.0.1:64443/service/cryptapi";

        this.ws = null;
        this.certificates = [];
        this.loginData = {};

        this.onStatusUpdate = null;
        this.onCertificatesLoaded = null;
        this.onAuthSuccess = null;
        this.onAuthError = null;
        this.onStepUpdate = null;
    }

    initialize() {
        this.updateStatus("Подключение к E-IMZO...", "info");

        this.ws = new WebSocket(this.EIMZO_WEBSOCKET);

        this.ws.onopen = () => {
            this.updateStatus("Соединение с E-IMZO установлено. Загрузка сертификатов...", "info");
            this.ws.send(JSON.stringify({
                plugin: "pfx",
                name: "list_all_certificates",
            }));
        };

        this.ws.onerror = () => {
            this.updateStatus("Ошибка подключения к E-IMZO. Убедитесь, что приложение запущено.", "danger");
            this.handleError("WebSocket connection failed");
        };

        this.ws.onclose = () => {
            this.updateStatus("Соединение с E-IMZO закрыто", "danger");
        };

        this.ws.onmessage = (evt) => {
            this.handleWebSocketMessage(JSON.parse(evt.data));
        };
    }

    disconnect() {
        if (this.ws && this.ws.readyState === WebSocket.OPEN) {
            this.ws.close();
        }
    }

    isConnected() {
        return !!this.ws && this.ws.readyState === WebSocket.OPEN;
    }

    getCertificates() {
        return this.certificates.map((cert, index) => this.normalizeCertificate(cert, index));
    }

    getLoginData() {
        return { ...this.loginData };
    }

    async reconnect() {
        return new Promise((resolve, reject) => {
            if (this.ws) {
                try {
                    this.ws.close();
                } catch (error) {
                    console.warn("Failed to close previous websocket", error);
                }
            }

            this.ws = new WebSocket(this.EIMZO_WEBSOCKET);

            const timeout = setTimeout(() => reject(new Error("E-IMZO reconnect timeout")), 10000);

            this.ws.onopen = () => {
                clearTimeout(timeout);
                resolve();
            };

            this.ws.onerror = () => {
                clearTimeout(timeout);
                reject(new Error("Ошибка переподключения к E-IMZO"));
            };

            this.ws.onmessage = (evt) => {
                this.handleWebSocketMessage(JSON.parse(evt.data));
            };
        });
    }

    async loginWithCertificate(certificateIndex, taxId = null, connectionType = null, extra = {}) {
        try {
            if (!this.certificates[certificateIndex]) {
                throw new Error("Certificate not found");
            }

            const certificate = this.certificates[certificateIndex];
            const resolvedTaxId = taxId || this.extractUIDFromCertificate(certificate);
            if (!resolvedTaxId) {
                throw new Error("ИНН не найден в сертификате");
            }

            this.loginData = {
                taxId: resolvedTaxId,
                certificateIndex,
                certificate,
                connectionType,
                extra,
            };

            if (this.provider === "direct") {
                this.updateStatus("Запрос challenge с backend...", "info");
                this.updateStep(2, false);

                const challengeData = await this.fetchDirectChallenge();
                this.loginData.challenge = challengeData.challenge;
                this.loginData.challengeTtl = challengeData.ttl;
                this.loginData.payloadToSign = this.toBase64Utf8(challengeData.challenge);
            } else {
                this.loginData.payloadToSign = this.toBase64Utf8(resolvedTaxId);
            }

            this.updateStatus("Загрузка ключа сертификата...", "info");
            this.updateStep(2, false);

            if (!this.isConnected()) {
                await this.reconnect();
            }

            this.loadCertificateKey();
        } catch (error) {
            this.handleError(error.message || String(error));
        }
    }

    handleWebSocketMessage(data) {
        if (data?.certificates) {
            this.handleCertificatesList(data.certificates);
        }

        if (data?.keyId) {
            this.loginData.keyId = data.keyId;
            this.createSignature();
        }

        if (data?.pkcs7_64) {
            this.loginData.pkcs7_64 = data.pkcs7_64;
            this.loginData.signature_hex = data.signature_hex;
            this.updateStep(3, true);

            if (this.provider === "direct") {
                this.performDirectAuthentication();
            } else {
                this.addDidoxTimestamp();
            }
        }

        if (data?.success === false) {
            this.handleError("E-IMZO operation failed: " + (data.reason || "Unknown error"));
        }
    }

    handleCertificatesList(certs) {
        this.certificates = certs;

        if (this.certificates.length === 0) {
            this.updateStatus("Сертификаты не найдены", "danger");
            this.handleError("No certificates found");
            return;
        }

        const parsedCertificates = this.certificates.map((cert, index) => this.normalizeCertificate(cert, index));
        this.updateStatus(`Найдено сертификатов: ${this.certificates.length}`, "success");
        this.updateStep(1, true);

        if (this.onCertificatesLoaded) {
            this.onCertificatesLoaded(parsedCertificates);
        }
    }

    normalizeCertificate(cert, index) {
        const uid = this.extractUIDFromCertificate(cert);
        const validToMatch = (cert.alias || "").match(/validto=([^,]+)/i);

        return {
            index,
            disk: cert.disk,
            path: cert.path,
            name: cert.name,
            alias: cert.alias,
            uid,
            taxId: uid,
            validTo: validToMatch ? validToMatch[1] : null,
            displayName: this.parseCertificateInfo(cert),
        };
    }

    loadCertificateKey() {
        const certificate = this.loginData.certificate;
        this.ws.send(JSON.stringify({
            plugin: "pfx",
            name: "load_key",
            arguments: [certificate.disk, certificate.path, certificate.name, certificate.alias],
        }));
    }

    async createSignature() {
        if (!this.isConnected()) {
            await this.reconnect();
        }

        this.ws.send(JSON.stringify({
            plugin: "pkcs7",
            name: "create_pkcs7",
            arguments: [this.loginData.payloadToSign, this.loginData.keyId, "no"],
        }));

        this.updateStatus("Создание цифровой подписи...", "info");
    }

    async addDidoxTimestamp() {
        try {
            this.updateStatus("Добавление временной метки...", "info");

            const data = await this.requestJson(this.buildUrl("/v1/dsvs/timestamp"), {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    pkcs7: this.loginData.pkcs7_64,
                    signatureHex: this.loginData.signature_hex,
                }),
            });

            if (!data.timeStampTokenB64) {
                throw new Error("Не удалось получить подпись с временной меткой");
            }

            this.loginData.timestampedSignature = data.timeStampTokenB64;
            this.updateStatus("Временная метка добавлена успешно", "success");
            this.updateStep(4, true);
            await this.performDidoxAuthentication();
        } catch (error) {
            this.handleError("Ошибка добавления временной метки: " + error.message);
        }
    }

    async performDidoxAuthentication() {
        try {
            this.updateStatus("Авторизация через ЭЦП...", "info");

            const signature = this.loginData.timestampedSignature || this.loginData.pkcs7_64;
            const data = await this.requestJson(this.buildUrl(`/v1/auth/${this.loginData.taxId}/token/ru`), {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ signature }),
            });

            if (!data.token) {
                throw new Error(data.message || "Не удалось получить Didox token");
            }

            this.updateStatus("Авторизация через ЭЦП успешна!", "success");
            if (this.onAuthSuccess) {
                this.onAuthSuccess(data.token, data);
            }
        } catch (error) {
            this.handleError("Ошибка входа через ЭЦП: " + error.message);
        }
    }

    async fetchDirectChallenge() {
        const response = await this.requestJson(this.buildUrl(this.routes.challenge), {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: "{}",
        });

        return response.data || response;
    }

    async addDirectTimestamp() {
        try {
            this.updateStatus("Добавление временной метки...", "info");

            const response = await this.requestJson(this.buildUrl(this.routes.timestamp), {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ pkcs7b64: this.loginData.pkcs7_64 }),
            });

            const data = response.data || response;
            if (!data.pkcs7b64) {
                throw new Error(response.message || "Не удалось получить timestamp от backend");
            }

            this.loginData.timestampedSignature = data.pkcs7b64;
            this.loginData.timestampSigners = data.signers || [];
            this.updateStatus("Временная метка добавлена успешно", "success");
            this.updateStep(4, true);
            await this.performDirectAuthentication();
        } catch (error) {
            this.handleError("Ошибка добавления временной метки: " + error.message);
        }
    }

    async performDirectAuthentication() {
        try {
            this.updateStatus("Авторизация через backend E-IMZO...", "info");

            const userType = this.loginData.connectionType === "yur" ? "yur" : this.userType;
            const response = await this.requestJson(this.buildUrl(this.routes.auth), {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    pkcs7b64: this.loginData.pkcs7_64,
                    user_type: userType,
                }),
            });

            const data = response.data || response;
            if (!data.token) {
                throw new Error(response.message || "Не удалось получить bearer token");
            }

            this.loginData.directAuthResult = data;
            this.updateStatus("Авторизация через E-IMZO успешна!", "success");
            this.updateStep(4, true);
            if (this.onAuthSuccess) {
                this.onAuthSuccess(data.token, data);
            }
        } catch (error) {
            this.handleError("Ошибка входа через E-IMZO: " + error.message);
        }
    }

    async startMobileAuth() {
        const response = await this.requestJson(this.buildUrl(this.routes.mobileAuth), {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: "{}",
        });

        return response.data || response;
    }

    async getDigestHex(text) {
        const response = await this.requestJson(this.buildUrl(this.routes.digest), {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ text }),
        });

        const data = response.data || response;
        return data.digestHex;
    }

    async startMobileSign(token, document = null, meta = null) {
        const body = {};
        if (document !== null && document !== undefined && document !== "") {
            body.document = document;
        }
        if (meta && typeof meta === "object") {
            body.meta = meta;
        }

        const response = await this.requestJson(this.buildUrl(this.routes.mobileSign), {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                Authorization: `Bearer ${token}`,
            },
            body: JSON.stringify(body),
        });

        return response.data || response;
    }

    async pollMobileStatus(documentId, options = {}) {
        const intervalMs = (options.intervalSeconds || 5) * 1000;
        const timeoutMs = (options.timeoutSeconds || 120) * 1000;
        const startedAt = Date.now();

        while ((Date.now() - startedAt) < timeoutMs) {
            const response = await this.requestJson(this.buildUrl(this.routes.mobileStatus), {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ documentId }),
            });

            const data = response.data || response;
            if (data.complete || data.status === 1) {
                return data;
            }

            await this.sleep(intervalMs);
        }

        throw new Error("Истекло время ожидания mobile E-IMZO");
    }

    async getMobileAuthResult(documentId, userType = "fiz") {
        const response = await this.requestJson(this.buildUrl(this.routes.mobileAuthResult), {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ documentId, user_type: userType }),
        });

        return response.data || response;
    }

    async verifyMobileDocument(documentId, documentB64, token) {
        const response = await this.requestJson(this.buildUrl(this.routes.mobileVerify), {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                Authorization: `Bearer ${token}`,
            },
            body: JSON.stringify({ documentId, document: documentB64 }),
        });

        return response.data || response;
    }

    buildMobileQrPayload({ siteId, documentId, challenge, hashHex }) {
        const resolvedHash = hashHex || this.hashMobilePayload(challenge);
        const combined = `${siteId}${documentId}${resolvedHash}`;
        const crc32 = this.calculateCrc32Hex(combined);

        return {
            hashHex: resolvedHash,
            qrCode: `${combined}${crc32}`,
            deepLink: `eimzo://sign?qc=${combined}${crc32}`,
        };
    }

    hashMobilePayload(text) {
        if (typeof window !== "undefined" && typeof window.GostHash === "function") {
            const hasher = new window.GostHash();
            return hasher.gosthash(text);
        }

        throw new Error("GostHash is not loaded. Include the E-IMZO mobile hash helper before building QR payloads.");
    }

    calculateCrc32Hex(input) {
        if (typeof window !== "undefined" && typeof window.CRC32 === "function") {
            const crc = new window.CRC32();
            return crc.calcHex(input);
        }

        let crc = 0 ^ (-1);
        for (let i = 0; i < input.length; i += 1) {
            crc = (crc >>> 8) ^ this.getCrc32Table()[(crc ^ input.charCodeAt(i)) & 0xff];
        }

        return ((crc ^ (-1)) >>> 0).toString(16).toUpperCase().padStart(8, "0");
    }

    getCrc32Table() {
        if (this.crc32Table) {
            return this.crc32Table;
        }

        this.crc32Table = [];
        for (let n = 0; n < 256; n += 1) {
            let c = n;
            for (let k = 0; k < 8; k += 1) {
                c = ((c & 1) ? (0xEDB88320 ^ (c >>> 1)) : (c >>> 1));
            }
            this.crc32Table[n] = c >>> 0;
        }

        return this.crc32Table;
    }

    parseCertificateInfo(certificate) {
        try {
            const alias = certificate.alias || "";
            const info = {};
            const patterns = {
                cn: /cn=([^,]+)/i,
                name: /name=([^,]+)/i,
                surname: /surname=([^,]+)/i,
                o: /o=([^,]+)/i,
                uid: /uid=([^,]+)/i,
                validto: /validto=([^,]+)/i,
            };

            Object.keys(patterns).forEach((key) => {
                const match = alias.match(patterns[key]);
                if (match) {
                    info[key] = match[1];
                }
            });

            const displayParts = [];
            if (info.cn) displayParts.push(info.cn.toUpperCase());
            if (info.name && info.surname) displayParts.push(`(${info.name} ${info.surname})`);
            if (info.o) displayParts.push(`- ${info.o}`);
            if (info.uid) displayParts.push(`ИНН: ${info.uid}`);
            if (info.validto) displayParts.push(`до ${info.validto}`);

            return displayParts.length > 0 ? displayParts.join(" ") : (certificate.name || "Неизвестный сертификат");
        } catch (error) {
            console.error("Error parsing certificate:", error);
            return certificate.name || "Неизвестный сертификат";
        }
    }

    extractUIDFromCertificate(certificate) {
        try {
            const alias = certificate.alias || "";
            const uidMatch = alias.match(/uid=([^,]+)/i);
            return uidMatch ? uidMatch[1] : null;
        } catch (error) {
            console.error("Error extracting UID:", error);
            return null;
        }
    }

    buildUrl(path) {
        if (/^https?:\/\//i.test(path)) {
            return path;
        }

        return `${this.apiBaseUrl}${path.startsWith("/") ? "" : "/"}${path}`;
    }

    async requestJson(url, options = {}) {
        const response = await fetch(url, options);
        const text = await response.text();
        let data = {};

        try {
            data = text ? JSON.parse(text) : {};
        } catch (error) {
            throw new Error(`Invalid JSON from ${url}`);
        }

        if (!response.ok) {
            throw new Error(data.message || `HTTP ${response.status}`);
        }

        if (typeof data.error_code === "number" && data.error_code !== 0) {
            throw new Error(data.message || `API error ${data.error_code}`);
        }

        if (data.success === false) {
            throw new Error(data.message || "Request failed");
        }

        return data;
    }

    toBase64Utf8(value) {
        return btoa(unescape(encodeURIComponent(value)));
    }

    sleep(ms) {
        return new Promise((resolve) => setTimeout(resolve, ms));
    }

    updateStatus(message, type = "info") {
        if (this.onStatusUpdate) {
            this.onStatusUpdate(message, type);
        }
    }

    updateStep(stepNumber, completed = null) {
        if (this.onStepUpdate) {
            this.onStepUpdate(stepNumber, completed);
        }
    }

    handleError(error) {
        const message = typeof error === "string" ? error : (error?.message || "Unknown error");
        this.updateStatus(message, "danger");
        if (this.onAuthError) {
            this.onAuthError(message);
        }
    }
}

class EIMZOYii2Auth extends EIMZOAuth {
    constructor(apiBaseUrl = (typeof window !== "undefined" ? window.location.origin : ""), options = {}) {
        super(apiBaseUrl, { ...options, provider: "direct" });
    }
}

if (typeof window !== "undefined") {
    window.EIMZOAuth = EIMZOAuth;
    window.EIMZOYii2Auth = EIMZOYii2Auth;
}
