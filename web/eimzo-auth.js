/**
 * E-IMZO Authentication Module
 * Standalone module for E-IMZO digital signature authentication
 * 
 * Usage:
 * const eimzoAuth = new EIMZOAuth('https://your-api-base.com');
 * eimzoAuth.onStatusUpdate = (message, type) => console.log(message);
 * eimzoAuth.onCertificatesLoaded = (certificates) => console.log(certificates);
 * eimzoAuth.onAuthSuccess = (token, userData) => console.log('Success!', token);
 * eimzoAuth.onAuthError = (error) => console.error('Error:', error);
 * eimzoAuth.initialize();
 */

class EIMZOAuth {
    constructor(apiBaseUrl) {
        this.DIDOX_API_BASE = apiBaseUrl;
        this.EIMZO_WEBSOCKET = 'wss://127.0.0.1:64443/service/cryptapi';
        
        // WebSocket connection
        this.ws = null;
        
        // Data storage
        this.certificates = [];
        this.loginData = {};
        
        // Callbacks - set these from outside
        this.onStatusUpdate = null;          // (message, type) => {}
        this.onCertificatesLoaded = null;    // (certificates) => {}
        this.onAuthSuccess = null;           // (token, userData) => {}
        this.onAuthError = null;             // (error) => {}
        this.onStepUpdate = null;            // (stepNumber, completed) => {}
        
        // Bind methods
        this.initialize = this.initialize.bind(this);
        this.loginWithCertificate = this.loginWithCertificate.bind(this);
        this.handleWebSocketMessage = this.handleWebSocketMessage.bind(this);
    }

    // Initialize E-IMZO connection
    initialize() {
        this.updateStatus("Подключение к E-IMZO...", "info");
        
        this.ws = new WebSocket(this.EIMZO_WEBSOCKET);
        
        this.ws.onopen = () => {
            console.log("E-IMZO WebSocket connected");
            this.updateStatus("Соединение с E-IMZO установлено. Загрузка сертификатов...", "info");
            
            // Request certificates list
            this.ws.send(JSON.stringify({
                plugin: "pfx",
                name: "list_all_certificates"
            }));
        };
        
        this.ws.onerror = (error) => {
            console.error("E-IMZO WebSocket error:", error);
            this.updateStatus("Ошибка подключения к E-IMZO. Убедитесь, что приложение запущено.", "danger");
            this.handleError("WebSocket connection failed");
        };
        
        this.ws.onclose = () => {
            console.log("E-IMZO WebSocket connection closed");
            this.updateStatus("Соединение с E-IMZO закрыто", "danger");
        };
        
        this.ws.onmessage = (evt) => {
            console.log("E-IMZO message received:", evt.data);
            this.handleWebSocketMessage(JSON.parse(evt.data));
        };
    }

    // Handle WebSocket messages from E-IMZO
    handleWebSocketMessage(data) {
        if (data?.certificates) {
            this.handleCertificatesList(data.certificates);
        }
        
        if (data?.keyId) {
            console.log("KeyId received:", data.keyId);
            this.loginData.keyId = data.keyId;
            this.createSignature();
        }
        
        if (data?.pkcs7_64) {
            console.log("E-IMZO signature created");
            this.loginData.pkcs7_64 = data.pkcs7_64;
            this.loginData.signature_hex = data.signature_hex;
            this.updateStep(3);
            this.addTimestamp();
        }
        
        if (data?.success === false) {
            console.error("E-IMZO operation failed:", data);
            this.handleError("E-IMZO operation failed: " + (data.reason || "Unknown error"));
        }
    }

    // Handle certificates list
    handleCertificatesList(certs) {
        console.log("Certificates found:", certs);
        this.certificates = certs;
        
        if (this.certificates.length === 0) {
            this.updateStatus("Сертификаты не найдены", "danger");
            this.handleError("No certificates found");
            return;
        }
        
        // Parse certificates for easier use
        const parsedCertificates = this.certificates.map((cert, index) => ({
            index: index,
            disk: cert.disk,
            path: cert.path,
            name: cert.name,
            alias: cert.alias,
            displayName: this.parseCertificateInfo(cert),
            uid: this.extractUIDFromCertificate(cert)
        }));
        
        this.updateStatus(`Найдено сертификатов: ${this.certificates.length}`, "success");
        this.updateStep(1, true);
        
        if (this.onCertificatesLoaded) {
            this.onCertificatesLoaded(parsedCertificates);
        }
    }

    // Parse certificate information for display
    parseCertificateInfo(certificate) {
        try {
            const alias = certificate.alias;
            const info = {};
            
            const patterns = {
                cn: /cn=([^,]+)/i,
                name: /name=([^,]+)/i,
                surname: /surname=([^,]+)/i,
                o: /o=([^,]+)/i,
                uid: /uid=([^,]+)/i,
                validto: /validto=([^,]+)/i
            };
            
            Object.keys(patterns).forEach(key => {
                const match = alias.match(patterns[key]);
                if (match) {
                    info[key] = match[1];
                }
            });
            
            const displayParts = [];
            
            if (info.cn) {
                displayParts.push(info.cn.toUpperCase());
            }
            
            if (info.name && info.surname) {
                displayParts.push(`(${info.name} ${info.surname})`);
            }
            
            if (info.o) {
                displayParts.push(`- ${info.o}`);
            }
            
            if (info.uid) {
                displayParts.push(`ИНН: ${info.uid}`);
            }
            
            if (info.validto) {
                displayParts.push(`до ${info.validto}`);
            }
            
            return displayParts.length > 0 ? displayParts.join(" ") : certificate.name || "Неизвестный сертификат";
            
        } catch (error) {
            console.error("Error parsing certificate:", error);
            return certificate.name || "Неизвестный сертификат";
        }
    }

    // Extract UID (INN) from certificate
    extractUIDFromCertificate(certificate) {
        try {
            const alias = certificate.alias;
            const uidMatch = alias.match(/uid=([^,]+)/i);
            return uidMatch ? uidMatch[1] : null;
        } catch (error) {
            console.error("Error extracting UID:", error);
            return null;
        }
    }

    // Login with selected certificate
    async loginWithCertificate(certificateIndex, taxId = null, connectionType=null) {
        try {
            if (!this.certificates[certificateIndex]) {
                throw new Error("Certificate not found");
            }

            const certificate = this.certificates[certificateIndex];
            
            // Extract taxId from certificate if not provided
            if (!taxId) {
                console.log(`extracting taxId from certificate: ${taxId}`);
                taxId = this.extractUIDFromCertificate(certificate);
                if (!taxId) {
                    throw new Error("ИНН не найден в сертификате");
                }
            }

            this.loginData = { taxId, certificateIndex, certificate };
            
            this.updateStatus("Загрузка ключа сертификата...", "info");
            this.updateStep(2);
            
            this.loadCertificateKey();
            
        } catch (error) {
            this.handleError(error.message);
        }
    }

    // Load certificate key
    loadCertificateKey() {
        const certificate = this.loginData.certificate;
        
        this.ws.send(JSON.stringify({
            plugin: "pfx",
            name: "load_key",
            arguments: [certificate.disk, certificate.path, certificate.name, certificate.alias]
        }));
    }

    // Create E-IMZO signature
    createSignature() {
        const dataToSign = btoa(this.loginData.taxId);
        
        this.ws.send(JSON.stringify({
            plugin: "pkcs7",
            name: "create_pkcs7",
            arguments: [dataToSign, this.loginData.keyId, "no"]
        }));
        
        this.updateStatus("Создание цифровой подписи...", "info");
    }

    // Add timestamp to signature
    async addTimestamp() {
        try {
            this.updateStatus("Добавление временной метки...", "info");
            
            const response = await fetch(`${this.DIDOX_API_BASE}/v1/dsvs/timestamp`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    pkcs7: this.loginData.pkcs7_64,
                    signatureHex: this.loginData.signature_hex
                })
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.timeStampTokenB64) {
                this.loginData.timestampedSignature = data.timeStampTokenB64;
                this.updateStatus("Временная метка добавлена успешно", "success");
                this.updateStep(4);
                await this.performAuthentication();
            } else {
                throw new Error('Не удалось получить подпись с временной меткой');
            }
            
        } catch (error) {
            console.error("Timestamp error:", error);
            this.handleError("Ошибка добавления временной метки: " + error.message);
        }
    }

    // Perform final authentication
    async performAuthentication() {
        try {
            this.updateStatus("Авторизация через ЭЦП...", "info");

            console.log(`999999999999 this.loginData.taxId: ${this.loginData.taxId} connectionType: ${this.loginData.connectionType}`);
            
            // Ensure we have a signature
            const signature = this.loginData.timestampedSignature || this.loginData.pkcs7_64;
            if (!signature) {
                throw new Error("Подпись не найдена. Повторите процесс подписания.");
            }
            
            // According to API docs, always use "signature" field
            // but put timeStampTokenB64 value when available
            const requestBody = {
                signature: signature
            };
            
            console.log("Sending auth request with body:", {
                signature: requestBody.signature ? `${requestBody.signature.substring(0, 50)}...` : 'null',
                hasTimestamp: !!this.loginData.timestampedSignature,
                taxId: this.loginData.taxId
            });
            
            const response = await fetch(`${this.DIDOX_API_BASE}/v1/auth/${this.loginData.taxId}/token/ru`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(requestBody)
            });
            
            const data = await response.json();
            console.log("E-IMZO login response:", data);
            console.log("Response status:", response.status);
            console.log("Response ok:", response.ok);
            
            // Check if we have a token regardless of HTTP status
            if (data.token) {
                this.updateStatus("Авторизация через ЭЦП успешна!", "success");
                if (this.onAuthSuccess) {
                    this.onAuthSuccess(data.token, data);
                }
            } else {
                this.handleAuthError(data, response.status);
            }
            
        } catch (error) {
            console.error("E-IMZO login error:", error);
            this.handleError("Ошибка входа через ЭЦП: " + error.message);
        }
    }

    // Handle authentication errors
    handleAuthError(data, status) {
        console.log("Handling login error. Status:", status, "Data:", data);
        
        let errorMessage = data.message || 'Ошибка авторизации';
        
        if (status === 422 && data.errors) {
            const errorDetails = Object.values(data.errors).flat().join(', ');
            errorMessage = `${errorMessage}: ${errorDetails}`;
        } else if (status && status !== 200) {
            errorMessage = `${errorMessage} (HTTP ${status})`;
        }
        
        // If we have data but no clear error message, show what we received
        if (!data.message && !data.errors && Object.keys(data).length > 0) {
            errorMessage = `Неожиданный ответ сервера: ${JSON.stringify(data)}`;
        }
        
        this.handleError(errorMessage);
    }

    // Generic error handler
    handleError(error) {
        const errorMessage = typeof error === 'string' ? error : error.message;
        this.updateStatus(errorMessage, "danger");
        if (this.onAuthError) {
            this.onAuthError(errorMessage);
        }
    }

    // Update status with callback
    updateStatus(message, type = 'info') {
        console.log("Status:", message);
        if (this.onStatusUpdate) {
            this.onStatusUpdate(message, type);
        }
    }

    // Update steps with callback
    updateStep(stepNumber, completed = null) {
        if (this.onStepUpdate) {
            this.onStepUpdate(stepNumber, completed);
        }
    }

    // Close WebSocket connection
    disconnect() {
        if (this.ws && this.ws.readyState === WebSocket.OPEN) {
            this.ws.close();
        }
    }

    // Get current certificates
    getCertificates() {
        return this.certificates.map((cert, index) => ({
            index: index,
            disk: cert.disk,
            path: cert.path,
            name: cert.name,
            alias: cert.alias,
            displayName: this.parseCertificateInfo(cert),
            uid: this.extractUIDFromCertificate(cert)
        }));
    }

    // Check if WebSocket is connected
    isConnected() {
        return this.ws && this.ws.readyState === WebSocket.OPEN;
    }

    // Get current login data
    getLoginData() {
        return { ...this.loginData };
    }
}

// Make available globally
if (typeof window !== 'undefined') {
    window.EIMZOAuth = EIMZOAuth;
} 