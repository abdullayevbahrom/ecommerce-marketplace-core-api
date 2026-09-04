# app -- External Services & Web Service Integration Documentation

> **Document version:** 1.0
> **Generated:** 2026-02-10
> **Platform:** Yii2 PHP Framework (e-commerce)
> **Branch:** `product-changed`

---

## Table of Contents

1. [Overview](#1-overview)
2. [BTS Courier Delivery Service](#2-bts-courier-delivery-service)
3. [Didox E-Signature Service](#3-didox-e-signature-service)
4. [Didox Order Service (Auto-Document Generation)](#4-didox-order-service-auto-document-generation)
5. [Blockchain Wallet Service (AA Wallet)](#5-blockchain-wallet-service-aa-wallet)
6. [Click Payment Gateway](#6-click-payment-gateway)
7. [Payme Payment Gateway](#7-payme-payment-gateway)
8. [Payme Subscribe (Card Tokenization)](#8-payme-subscribe-card-tokenization)
9. [Octo Payment Gateway](#9-octo-payment-gateway)
10. [YooKassa Payment Gateway](#10-yookassa-payment-gateway)
11. [PayKeeper Payment Gateway](#11-paykeeper-payment-gateway)
12. [MyID Electronic Identification Service](#12-myid-electronic-identification-service)
13. [Firebase Cloud Messaging (FCM)](#13-firebase-cloud-messaging-fcm)
14. [SMSC SMS Service](#14-smsc-sms-service)
15. [Configuration Reference](#15-configuration-reference)
16. [Service Dependency Matrix](#16-service-dependency-matrix)
17. [Security Considerations](#17-security-considerations)

---

## 1. Overview

The app e-commerce platform integrates with **13 distinct external services** across five functional categories:

| Category | Services | Purpose |
|----------|----------|---------|
| **Logistics** | BTS | Courier delivery across Uzbekistan |
| **Legal / Compliance** | Didox, Didox Order Service | Electronic invoicing and digital signatures |
| **Payments** | Click, Payme, Payme Subscribe, Octo, YooKassa, PayKeeper | Payment processing (UZS and RUB) |
| **Blockchain** | AA Wallet Service | Cryptocurrency wallet and stablecoin payments |
| **Identity** | MyID | Government-issued electronic identity verification |
| **Notifications** | FCM, SMSC | Push notifications and SMS delivery |

All services communicate over HTTPS (or HTTP for local/test environments) using either cURL or the GuzzleHttp client library. Authentication methods vary per service -- from Bearer tokens and Basic Auth to HMAC signatures and API keys.

---

## 2. BTS Courier Delivery Service

**Source file:** `services/BTS.php`
**Namespace:** `yii\services`
**Purpose:** Integration with the BTS national courier/postal delivery network in Uzbekistan for order shipping, delivery cost calculation, and shipment tracking.

### 2.1 Connection Details

| Property | Value |
|----------|-------|
| Base URL | `https://apitest.logistics.example.com:28345` |
| API Version | `v1` (path-based: `/v1/{endpoint}`) |
| Protocol | HTTPS with JSON payloads |
| HTTP Client | cURL (native) |
| Timeout | 30 seconds |
| SSL Verification | Disabled (`CURLOPT_SSL_VERIFYPEER => false`) |

### 2.2 Authentication

BTS uses a **dual-token system** with access and refresh tokens:

| Method | Endpoint | Description |
|--------|----------|-------------|
| Login | `POST /auth/login` | Accepts `login`, `password` in JSON body; returns `access_token` and `refresh_token` |
| Refresh | `POST /auth/refresh` | Accepts `refresh_token` in JSON body; returns new `access_token` |
| Legacy | `POST /v1/auth/get-token` | Accepts `username`, `password`, `inn`; returns a single token |

**Token lifecycle:**
- Access token duration: **86,400 seconds** (1 day), with a 5-minute safety buffer.
- Refresh token duration: **2,592,000 seconds** (30 days).
- Tokens are cached using Yii's cache component with keys `bts_access_token`, `bts_refresh_token`, `bts_token_expires`.
- On HTTP 401, the service automatically attempts one token refresh before failing.

**Credentials** (from `config/params.php`):
- `bts_username`: `8888`
- `bts_password`: `7132`
- `bts_inn`: `123456789`
- `bts_token` (legacy): `sample_logistics_token`

### 2.3 Key Methods

| Method | HTTP | Endpoint | Description |
|--------|------|----------|-------------|
| `authenticate()` | POST | `/auth/login` | Full login with credentials |
| `refreshAccessToken()` | POST | `/auth/refresh` | Refresh expired access token |
| `ensureAuthenticated()` | -- | -- | Auto-authenticates or refreshes as needed |
| `getApiToken()` | POST | `/v1/auth/get-token` | Legacy token retrieval |
| `createOrder($data)` | POST | `/v1/order/add` | Create a new delivery order |
| `editOrder($id, $data)` | POST | `/v1/order/edit/{id}` | Edit an existing order |
| `getOrderInfo($id)` | GET | `/v1/order/detail?id={id}` | Retrieve order details |
| `deleteOrder($id)` | POST | `/v1/order/delete/{id}` | Delete an order |
| `calculateOrder($data)` | POST | `/v1/order-calculator` | Calculate delivery cost (new) |
| `calculateDelivery($data)` | POST | `/v1/order/calculate` | Calculate delivery cost (legacy) |
| `getOrderStatus($id)` | GET | `/v1/order/status?id={id}` | Get current order status |
| `getOrderTracking($id)` | GET | `/v1/order/tracking?id={id}` | Get tracking information |
| `trackOrder($id)` | GET | `/v1/order/track?id={id}` | Track order (returns status ID + name) |
| `getOrderHistory($id)` | GET | `/v1/order/history?id={id}` | Get full shipment history with locations |
| `getStatusList()` | GET | `/v1/status/list` | List all available statuses |
| `validateOrderData($data)` | -- | -- | Client-side field validation |

### 2.4 Data Flow

```
[app Order] --> BTS.createOrder() --> [BTS API /v1/order/add]
                                             |
                                             v
                               [BTS Order ID returned]
                                             |
                                             v
[Admin Panel] --> BTS.trackOrder() --> [BTS API /v1/order/track]
                                             |
                                             v
                          [Status: Draft/Refused/New/Accepted/...]
```

**Order creation payload requires:**
`senderCityId`, `senderAddress`, `senderReal`, `senderPhone`, `weight`, `packageId`, `postTypeId`, `receiver`, `receiverAddress`, `receiverCityId`, `receiverPhone`

### 2.5 Static Reference Data

The service embeds comprehensive static dictionaries:

- **REGIONS**: 14 Uzbekistan regions (viloyatlar) with trilingual names (RU, UZ, EN)
- **CITIES**: 200+ cities/districts mapped to their parent regions
- **PACKAGE_TYPES**: 8 types (Standard, Soft, Hard, BTS, Fragile, Documents, Valuable, Large)
- **POST_TYPES**: 10 types (Regular, Valuable, Notification, Documents, Package, Express, Courier, Internet Order, Cash on Delivery, Return)
- **BTS_STATUSES**: 12 statuses from -1 (Draft) through 10 (Lost), trilingual
- **ORDER_STATUSES**: 10 legacy statuses (1=At Client through 10=Lost)

### 2.6 Error Handling

- HTTP error codes mapped to human-readable messages via `getErrorMessage()`.
- Defined constants for all standard HTTP errors (400, 401, 403, 404, 405, 406, 410, 429, 500, 503).
- cURL errors returned as structured error responses via `formatError()`.
- Automatic retry on 401 with token refresh (single retry to prevent loops).
- All errors logged via `Yii::error()`.

### 2.7 Affected Database Tables

- `order` (stores BTS order ID reference, delivery status)
- Yii cache store (token persistence)

---

## 3. Didox E-Signature Service

**Source file:** `services/DidoxService.php`
**Namespace:** `app\services`
**Purpose:** Integration with the Didox electronic document management system for creating, signing, and exchanging legally-binding e-invoices and contracts in compliance with Uzbekistan fiscal regulations.

### 3.1 Connection Details

| Property | Value |
|----------|-------|
| Production URL | `https://api.einvoice.example.com` |
| Staging URL | `https://stage.goodsign.biz` |
| URL Selection | Automatic: staging in `YII_ENV_DEV`, production otherwise |
| HTTP Client | cURL (native) |
| Timeout | 30 seconds (60 seconds for PDF downloads) |
| SSL Verification | Disabled |

### 3.2 Authentication

Didox uses a **multi-layered authentication** system:

| Auth Layer | Header | Description |
|------------|--------|-------------|
| Partner Token | `Authorization: Bearer {token}` or `Partner-Authorization: {token}` | Long-lived JWT partner token from params |
| User Key | `user-key: {token}` | Per-user session token from E-IMZO signature |

**Authentication flows:**

1. **Automated PFX-based auth** (`getAuthTokenFromPfx()`):
   - Reads PFX file path and password from `Settings` table
   - Calls local Signer Service (default: `http://127.0.0.1:8080/generate`) to generate PKCS7 signature
   - Requests timestamp from Didox: `POST /v1/dsvs/timestamp`
   - Exchanges timestamp token for auth token: `POST /v1/auth/{jshir}/token/ru`

2. **E-IMZO signature auth** (`authenticateWithEimzo()`):
   - Receives signed INN from E-IMZO browser plugin
   - Exchanges for auth token: `POST /v1/auth/{taxId}/token/ru`

3. **Password auth** (`authenticateWithPassword()`):
   - Username/password login: `POST /v1/auth/{taxId}/password/{locale}`

4. **Company login** (`loginToCompany()`):
   - Switch context to company: `POST /v1/auth/company/{companyTaxId}/login/{locale}`

**Partner token** (from `config/params.php`): A JWT token containing partner metadata (ID 302, TIN `123456789`, role `PARTNER`).

### 3.3 Key Methods

| Method | HTTP | Endpoint | Description |
|--------|------|----------|-------------|
| `getAuthTokenFromPfx()` | POST | `/v1/dsvs/timestamp`, `/v1/auth/{inn}/token/ru` | Automated PFX auth |
| `authenticateWithEimzo()` | POST | `/v1/auth/{taxId}/token/ru` | E-IMZO auth |
| `authenticateWithPassword()` | POST | `/v1/auth/{taxId}/password/{locale}` | Password auth |
| `loginToCompany()` | POST | `/v1/auth/company/{taxId}/login/{locale}` | Company context switch |
| `registerUser($data)` | POST | `/v1/auth/signup` | Register new Didox user |
| `getUserProfile()` | GET | `/v1/profile` | Get user profile data |
| `createDocument($data, $userKey)` | POST | `/v1/documents/{docType}/create` | Create a new document |
| `updateDocument($docId, $data)` | POST | `/v1/documents/{docId}/update/{docType}` | Update existing document |
| `getDocument($docId)` | GET | `/v1/documents/{docId}` | Get document details |
| `getDocuments($params)` | GET | `/v2/documents` | List documents (paginated) |
| `sendDocumentToPartner($docId)` | POST | `/v1/documents/{docId}/send` | Send document to partner |
| `getDocumentForSigning($docId)` | GET | `/v1/documents/{docId}?owner=1` | Get document for signing (outgoing) |
| `getIncomingDocumentForSigning($docId)` | GET | `/v1/documents/{docId}?owner=0` | Get incoming document with `toSign` value |
| `signDocument($docId, $signature)` | POST | `/v1/documents/{docId}/sign` | Sign outgoing document |
| `acceptIncomingDocument($docId, $sig)` | POST | `/v1/documents/{docId}/sign` | Accept/sign incoming document |
| `getDocumentToSign($docId, $action)` | POST | `/v1/documents/{docId}/tosign` | Get data needed for signing |
| `cancelDocument($docId)` | POST | `/v1/documents/{docId}/cancel` | Cancel/delete a document |
| `getDocumentPdf($docId, $lang)` | GET | `/v1/documents/view/{docId}/pdf/{lang}` | Download document PDF |
| `createTimestamp($pkcs7, $sig)` | POST | `/v1/dsvs/timestamp` | Create signature timestamp |
| `getProfileProductClassCodes()` | GET | `/v1/profile/productClassCodes` | Get linked IKPU codes |
| `addProfileProductClass($data)` | POST | `/v1/profile/productClasses` | Add IKPU code |
| `removeProfileProductClass($code)` | DELETE | `/v1/profile/productClasses/{code}` | Remove IKPU code |
| `searchProductClasses($page, $lang, $q)` | GET | `/v1/profile/productClasses/` | Search available IKPU codes |
| `checkProductClassByCode($code, $lang)` | GET | `/v1/profile/productClasses/` | Check specific IKPU code |

### 3.4 Data Flow

```
                    +--------------------------+
                    |  Local Signer Service    |
                    |  (127.0.0.1:8080)        |
                    +-----------+--------------+
                                |
                         PKCS7 + Signature
                                |
                                v
+-------------+   timestamp   +----------------+   auth token   +----------------+
| PFX File    | ------------> | Didox API      | -------------> | User Key       |
| (E-IMZO)    |               | /v1/dsvs/      |                | (Session)      |
+-------------+               | timestamp      |                +-------+--------+
                              +----------------+                        |
                                                                        v
+-------------+   createDocument()   +---------------------+   sign()  +-----------+
| Order       | ------------------> | Didox Draft Document | -------> | Signed    |
| (app DB)  |                     | (doctype 002/000)    |          | Document  |
+-------------+                     +---------------------+          +-----------+
                                                                        |
                                                                   send()
                                                                        |
                                                                        v
                                                               +-----------+
                                                               | Buyer     |
                                                               | (Partner) |
                                                               +-----------+
```

### 3.5 Error Handling

- All methods return structured arrays: `['success' => bool, 'data' => ..., 'httpCode' => int, 'error' => string|null]`.
- HTTP 422 responses are parsed for validation-specific errors (`validation.unique`, `validation.exists`).
- cURL errors throw exceptions caught by calling methods.
- All errors logged via `Yii::error()`.

### 3.6 Affected Database Tables

- `settings` (stores PFX path, password, signer URL, seller INN, partner tokens)
- `didox_document` (local document records with Didox IDs and statuses)
- `didox_document_invoice` (invoice-specific fields)
- `didox_document_arbitrary` (contract-specific fields)
- `didox_document_included_products` (line items within invoices)

---

## 4. Didox Order Service (Auto-Document Generation)

**Source file:** `services/DidoxOrderService.php`
**Namespace:** `app\services`
**Purpose:** Orchestration layer that automatically creates Didox Invoice (doctype `002`) and Arbitrary Contract (doctype `000`) documents when orders are placed or processed. Calls `DidoxService` internally.

### 4.1 Key Methods

| Method | Description |
|--------|-------------|
| `createDocuments(Order $order)` | Creates both Invoice and Arbitrary Contract for an order |
| `createInvoice(Order $order)` | Creates only an Invoice document |
| `createArbitrary(Order $order)` | Creates only an Arbitrary Contract |

### 4.2 Document Creation Workflow

1. **Seller info retrieval**: Reads `didox_seller_inn`, `didox_seller_name`, `didox_seller_address`, `didox_seller_account`, `didox_seller_mfo`, `didox_seller_vat_reg_code` from the `settings` table.
2. **Buyer info retrieval**: Extracts buyer TIN from `order.inn`, user's `eimzo_tax_id`, or `user.inn`. Falls back to default TIN `123456789` for guests.
3. **Token resolution**: Checks session for `didox_token` -> tries PFX auto-auth -> falls back to stored system token (`didox_eimzo_token`).
4. **Profile sync**: If token is valid, updates seller info from Didox profile.
5. **Invoice creation** (doctype `002`):
   - Creates `DidoxDocument` with type `DOCUMENT_TYPE_INVOICE`
   - Creates `DidoxDocumentInvoice` with seller/buyer details
   - Iterates `orderProducts` to create `DidoxDocumentIncludedProducts` with IKPU codes, VAT (12%), package codes
   - Uploads via `DidoxService::createDocument()`
   - Auto-downloads PDF in UZ and RU languages
6. **Contract creation** (doctype `000`):
   - Creates `DidoxDocument` with type `DOCUMENT_TYPE_ARBITRARY`
   - Creates `DidoxDocumentArbitrary` populated from order data
   - Generates a PDF contract
   - Uploads via `DidoxService::createDocument()`

### 4.3 Error Handling

- All operations wrapped in database transactions with rollback on failure.
- Extensive logging via both `Yii::error()` and custom `Log::log('didox_order', ...)`.
- Each step logs request/response pairs for debugging.
- PDF download failures are non-blocking (logged as warnings).

### 4.4 Affected Database Tables

- `order`, `order_product` (source data)
- `user` (buyer information)
- `settings` (seller configuration and tokens)
- `didox_document` (parent document records)
- `didox_document_invoice` (invoice details)
- `didox_document_arbitrary` (contract details)
- `didox_document_included_products` (line items)
- `log` (detailed operation logs)

---

## 5. Blockchain Wallet Service (AA Wallet)

**Source file:** `services/WalletService.php`
**Namespace:** `app\services`
**Purpose:** Client for an external Account Abstraction (AA) Wallet backend that manages blockchain wallets, ERC-20 token balances, and stablecoin payments on an EVM-compatible chain.

### 5.1 Connection Details

| Property | Value |
|----------|-------|
| Production URL | `https://wallet.example.com` |
| Default/Fallback URL | `http://localhost:3001` |
| Config Key | `walletServiceUrl` (from `params.php`) |
| HTTP Client | GuzzleHttp\Client |
| Protocol | HTTPS with JSON payloads |

### 5.2 Authentication

The wallet service uses **implicit authentication** -- no API key or bearer token. User identity is established by passing `userId` and `userLogin` (the user's phone number) as request parameters.

### 5.3 Supported Tokens

| Symbol | Contract Address |
|--------|-----------------|
| USDT | `0x7b95CaDaf3Fe1154A7B663f3793856F7e9f21d16` |
| USDC | `0x3f4A04341122360b304C9A896a2Dbfe4cca5B4AE` |

### 5.4 Key Methods

| Method | HTTP | Endpoint | Description |
|--------|------|----------|-------------|
| `ensureWallet($userId)` | GET | `/wallet/address` | Get or create EOA address |
| `getAAAddress($userId)` | GET | `/wallet/address` | Get Account Abstraction address |
| `predictWallet($userId)` | GET | `/wallet/address` | Predict both EOA and AA addresses |
| `getWalletStatus($userId)` | GET | `/wallet/status` | Check if wallet is deployed on-chain |
| `getBalance($userId)` | GET | `/wallet/balance` | Get wallet balance and deployment status |
| `deployWallet($userId)` | POST | `/wallet/deploy` | Deploy AA wallet on-chain (returns txHash) |
| `mintToken($to, $amount, $token)` | POST | `/token/mint` | Mint tokens to an address |
| `pay($payerId, $merchantId, $amount, $symbol)` | POST | `/payment/execute-batch` | Execute a batch payment |
| `approvePayment($payerId, $merchantId, $amount, $symbol)` | POST | `/payment/approve` | Pre-approve a payment |
| `buildBatch($payerId, $merchantId, $amount, $symbol)` | POST | `/payment/build-batch` | Build batch without executing |
| `getSupportedTokens()` | GET | `/payment/supported-tokens` | List supported tokens |
| `transfer()` | -- | -- | **DEPRECATED** -- throws exception; use `pay()` instead |

### 5.5 Data Flow

```
[User places order] --> WalletService.pay(payerId, merchantId, amount, 'USDT')
                               |
                               v
                    POST /payment/execute-batch
                    {payerId, merchantId, amount, symbol}
                               |
                               v
              [AA Wallet Backend executes on-chain batch tx]
                               |
                               v
                    [txHash returned to app]
                               |
                               v
                    [Order marked as paid]
```

### 5.6 User Identity Resolution

The private `getUserLogin($userId)` method resolves user identity in this priority order:
1. `user.phone`
2. `user.login`
3. `user.email`
4. Fallback: `user_{userId}`

### 5.7 Error Handling

- All methods catch `\Exception` and log via `Yii::error()`.
- Wallet status and balance methods return safe defaults on failure: `['isDeployed' => false]` and `['balance' => 0, 'isDeployed' => false]`.
- Payment, deploy, and mint methods re-throw exceptions for caller handling.

### 5.8 Affected Database Tables

- `user` (phone/login/email lookup)
- `order` (payment status updates in calling code)
- `category` (wallet payment type with ID from `walletPaymentId` param)

---

## 6. Click Payment Gateway

**Source file:** `services/Click.php`
**Namespace:** `yii\services`
**Purpose:** Integration with Click.uz, one of the primary electronic payment systems in Uzbekistan. Implements the SHOP-API merchant integration protocol.

### 6.1 Connection Details

Click operates as a **callback-based** system -- Click sends HTTP requests to the merchant (app), not the other way around.

| Property | Value |
|----------|-------|
| Direction | Click --> app (inbound callbacks) |
| Protocol | HTTPS POST with form parameters |

### 6.2 Authentication

Click uses **MD5 HMAC signature verification**:

```
sign_string = MD5(
    click_trans_id +
    service_id +
    secret_key +         // empty string in code
    merchant_trans_id +
    [merchant_prepare_id] +  // only for action=1 (complete)
    amount +
    action +
    sign_time
)
```

The signature is verified on each incoming request. If the signature does not match, error code `-1` (SIGN CHECK FAILED) is returned.

### 6.3 Key Methods (Two-Phase Protocol)

| Method | Phase | Description |
|--------|-------|-------------|
| `prepare()` | 1 - Prepare | Validates signature, checks order existence and amount, creates pending `TransactionClick` record |
| `complete()` | 2 - Complete | Validates signature, confirms transaction, marks order as paid, updates user balance |

### 6.4 Data Flow

```
[Customer pays via Click app]
         |
         v
[Click Server] --POST--> app/prepare
                          |
                          |- Verify sign_string
                          |- Find order by merchant_trans_id
                          |- Validate amount
                          |- Create TransactionClick (status=0)
                          |- Return merchant_prepare_id
                          |
[Click Server] --POST--> app/complete
                          |
                          |- Verify sign_string
                          |- Find transaction by merchant_prepare_id
                          |- Validate amount
                          |- Set transaction status=1
                          |- Set order.status_paid=1
                          |- Add amount to user.balance
                          |- Return merchant_confirm_id
```

### 6.5 Error Codes

| Code | Message |
|------|---------|
| 0 | Success |
| -1 | SIGN CHECK FAILED |
| -2 | Amount not correct |
| -3 | Action not found |
| -4 | Already paid |
| -5 | User does not exist |
| -6 | Transaction does not exist |
| -7 | Failed to update user |
| -8 | Error in request from click |
| -9 | Transaction cancelled |

### 6.6 Error Handling

- Validation failures immediately `exit()` with JSON error responses.
- Click error `-5017` (cancelled by user) is handled specially and persisted.

### 6.7 Affected Database Tables

- `order` (`status_paid` column updated to 1 on success)
- `transaction_click` (transaction log: `status`, `amount`, `account`, `click_trans_id`, `error`)
- `user` (`balance` column incremented on successful payment)

---

## 7. Payme Payment Gateway

**Source files:** `services/Payme.php`, `services/payme/AbstractPayme.php`, `services/payme/PaymeRequest.php`, `services/payme/PaymeResponse.php`, `services/payme/DbTransactionProvider.php`, `controllers/payment/PaymeController.php`
**Namespace:** `yii\services`
**Purpose:** Integration with Payme (paycom.uz), one of the most widely used payment systems in Uzbekistan. Implements the Payme Merchant API (JSON-RPC protocol).

### 7.1 Connection Details

| Property | Value |
|----------|-------|
| Direction | Payme --> app (inbound JSON-RPC callbacks) |
| Protocol | JSON-RPC 2.0 over HTTPS |
| Controller | `PaymeController::actionIndex()` reads raw `php://input` |
| DB Connection | Direct PDO (not Yii ActiveRecord) |

### 7.2 Authentication

Payme uses **HTTP Basic Authentication**:

```
Authorization: Basic base64(login:password)
```

- Login: `Paycom` (constant)
- Password: Stored in `payme_password` database table (ID=1)
- Merchant ID: `sample_payme_merchant_id`

### 7.3 Key Methods (JSON-RPC)

| JSON-RPC Method | Service Method | Description |
|-----------------|---------------|-------------|
| `CheckPerformTransaction` | `checkPerformTransaction()` | Validate order can be paid (check accounts, amount) |
| `CreateTransaction` | `createTransaction()` | Create a pending payment transaction (state=1) |
| `PerformTransaction` | `performTransaction()` | Execute the payment -- mark order paid, set state=2 |
| `CheckTransaction` | `checkTransaction()` | Query transaction status |
| `CancelTransaction` | `cancelTransaction()` | Cancel or refund transaction (state=-1 or -2) |
| `GetStatement` | `getStatement()` | Get transaction report for reconciliation |
| `ChangePassword` | `changePassword()` | Change merchant password (currently disabled) |

### 7.4 Transaction States

| State | Meaning |
|-------|---------|
| 1 | Created (pending) |
| 2 | Performed (completed) |
| -1 | Cancelled before completion |
| -2 | Cancelled after completion (refund) |

### 7.5 Configuration

- `minSum`: 1,000 (UZS, in tiyins)
- `maxSum`: 100,000 (UZS, in tiyins)
- `timeout`: 6,000,000 ms (100 minutes)
- `canCancelSuccessTransaction`: `false` (but code still handles state -2)
- Amounts are divided by 100 (Payme sends amounts in tiyins).

### 7.6 Error Handling

All errors return Payme-standard JSON-RPC error codes:
- `AUTH_ERROR`: Authentication failure
- `JSON_RPC_ERROR`: Malformed request
- `USER_NOT_FOUND`: Order not found
- `WRONG_AMOUNT`: Amount mismatch
- `CANT_PERFORM_TRANS`: Cannot process transaction
- `TRANS_NOT_FOUND`: Transaction not found
- `PENDING_PAYMENT`: Another payment pending
- `SYSTEM_ERROR`: Internal server error
- `CANT_CANCEL_TRANSACTION`: Refund not allowed

### 7.7 Affected Database Tables

- `order` (`status_payment` set to 1 on pay, 2 on cancel)
- `transaction_payme` (Payme transaction log: `transaction`, `payme_time`, `amount`, `state`, timestamps)
- `payme_password` (stores merchant API password)

---

## 8. Payme Subscribe (Card Tokenization)

**Source file:** `services/PaymeSubscribe.php`
**Namespace:** `yii\services`
**Purpose:** Payme Subscribe API for card tokenization and receipt-based payments. Allows saving cards and charging them without redirecting to Payme.

### 8.1 Connection Details

| Property | Value |
|----------|-------|
| Base URL | `https://checkout.paycom.uz/api` |
| HTTP Client | cURL (native) |
| Protocol | HTTPS with JSON-RPC payloads |

### 8.2 Authentication

Uses `X-Auth` header with two modes:
- **Card operations**: `X-Auth: {merchant_id}` (merchant ID only)
- **Receipt operations**: `X-Auth: {merchant_id}:{password}` (merchant ID + password)

Credentials:
- Merchant ID: `sample_payme_merchant_id`
- Password: `sample_payme_secret_key`

### 8.3 Key Methods

**Card Management:**

| Method | JSON-RPC Method | Description |
|--------|----------------|-------------|
| `createCard($post)` | `cards.create` | Tokenize a card (number + expire) |
| `getVerifyCode($token)` | `cards.get_verify_code` | Request SMS verification code |
| `verify($token, $code)` | `cards.verify` | Verify card with SMS code |
| `check($token)` | `cards.check` | Check card validity |
| `remove($token)` | `cards.remove` | Remove saved card |

**Receipt (Payment) Operations:**

| Method | JSON-RPC Method | Description |
|--------|----------------|-------------|
| `receiptCreate($orderId, $price)` | `receipts.create` | Create payment receipt (amount * 100 for tiyins) |
| `receiptPay($id, $token, $user)` | `receipts.pay` | Charge saved card for receipt |
| `receiptCancel($id)` | `receipts.cancel` | Cancel/refund a receipt |
| `receiptCheck($id)` | `receipts.check` | Check receipt status |

### 8.4 Data Flow

```
[User] -> createCard(number, expire)       -> Card token
[User] -> getVerifyCode(token)             -> SMS sent to card owner
[User] -> verify(token, code)              -> Card verified & saved
[Order] -> receiptCreate(orderId, price)   -> Receipt ID
[Order] -> receiptPay(receiptId, token, user) -> Payment executed
```

### 8.5 Affected Database Tables

- `order` (price lookup for receipt creation)
- `user` (payer info: id, phone, email, name)

---

## 9. Octo Payment Gateway

**Source file:** `services/Octo.php`
**Namespace:** `yii\services`
**Purpose:** Integration with Octo.uz payment system for online card payments in Uzbekistan.

### 9.1 Connection Details

| Property | Value |
|----------|-------|
| Base URL | `https://secure.octo.uz` |
| HTTP Client | cURL (native) |
| Protocol | HTTPS with JSON payloads |
| Notify URL | `https://api.example.com/payment/octo/notify` |

### 9.2 Authentication

Authentication via `octo_shop_id` and `octo_secret` sent in the request body (not headers):
- Shop ID: `4874`
- Secret: `00000000-0000-0000-0000-000000000000`

### 9.3 Key Methods

| Method | HTTP | Endpoint | Description |
|--------|------|----------|-------------|
| `prepare($order)` | POST | `/prepare_payment` | Create payment session; returns payment URL |

### 9.4 Payment Parameters

```json
{
    "octo_shop_id": "10001",
    "octo_secret": "{secret}",
    "shop_transaction_id": "<order_id>",
    "auto_capture": true,
    "test": true,
    "init_time": "2026-02-10 12:00:00",
    "total_sum": "<order_price>",
    "currency": "UZS",
    "description": "Payment for order",
    "language": "ru",
    "notify_url": "<callback_url>",
    "ttl": 15
}
```

**Note:** The `test: true` flag indicates this is currently in sandbox mode.

### 9.5 Data Flow

```
[app] --POST /prepare_payment--> [Octo API]
                                        |
                                        v
                              [Payment URL returned]
                                        |
                                        v
                     [User redirected to Octo payment page]
                                        |
                                        v
                     [Octo] --POST notify_url--> [app callback]
```

### 9.6 Affected Database Tables

- `order` (order price lookup, payment status update via callback)

---

## 10. YooKassa Payment Gateway

**Source file:** `services/YooKassa.php`
**Namespace:** `app\services`
**Purpose:** Integration with YooKassa (formerly Yandex.Kassa), a Russian payment gateway supporting multiple payment methods for RUB-denominated transactions.

### 10.1 Connection Details

| Property | Value |
|----------|-------|
| Base URL | Handled by `yoomoney/yookassa-sdk-php` SDK |
| HTTP Client | YooKassa SDK (internal Guzzle client) |
| Currency | RUB (Russian Ruble) |

### 10.2 Authentication

Uses the YooKassa PHP SDK with `setAuth()`:
- Shop ID: `239537`
- Secret Key: `test_oEKmFN2MHOIGsGv0ubYpO70jPToj94bv3xTNDvPVi9U`

**Note:** The `test_` prefix indicates this is a test/sandbox key.

### 10.3 Key Methods

| Method | Description |
|--------|-------------|
| `createPayment($amount, $description, $type)` | Create a payment with redirect confirmation |

### 10.4 Payment Parameters

- **amount**: Payment value in RUB
- **currency**: `RUB`
- **confirmation type**: `redirect` (user redirected to YooKassa)
- **return_url**: `{baseUrl}/payment/success`
- **payment_method_data.type**: Passed by caller (e.g., `bank_card`, `sbp`, `yoo_money`)
- **idempotency key**: `uniqid('', true)`

### 10.5 Data Flow

```
[app] --> YooKassa SDK createPayment() --> [YooKassa API]
                                                   |
                                                   v
                                         [Confirmation URL]
                                                   |
                                                   v
                                [User redirected to YooKassa payment page]
                                                   |
                                                   v
                              [User redirected back to /payment/success]
```

### 10.6 Affected Database Tables

- `order` (payment status updated via webhook/return handler)

---

## 11. PayKeeper Payment Gateway

**Source file:** `services/PayKeeperService.php`
**Namespace:** `app\services`
**Purpose:** Integration with PayKeeper payment processing system for invoice-based payments. Supports both inline payment forms and invoice-based payment links.

### 11.1 Connection Details

| Property | Value |
|----------|-------|
| Base URL | `https://server.paykeeper.example.com` |
| HTTP Client | cURL / `file_get_contents` with stream context |
| Documentation | `https://docs.paykeeper.ru` |

### 11.2 Authentication

**HTTP Basic Authentication** for API calls:
- Login: `admin`
- Password: `sample_password`
- Header: `Authorization: Basic base64(admin:sample_password)`

Callback verification uses a **notify secret key** (currently empty) with MD5 signature:
```
key = MD5(id + formatted_sum + clientid + orderid + NOTIFY_SECRET_KEY)
```

**Note:** Signature verification is currently commented out in the code.

### 11.3 Key Methods

| Method | HTTP | Endpoint | Description |
|--------|------|----------|-------------|
| `get_payform($orderId, $amount)` | POST | `/order/inline/` | Get inline HTML payment form |
| `get_invoice_url($orderId, $amount)` | GET+POST | `/info/settings/token/` then `/change/invoice/preview/` | Create invoice and return payment URL |
| `get_invoice_status($invoiceId)` | GET | `/info/invoice/byid/?id={id}` | Check invoice payment status |
| `notify($id, $sum, $clientid, $orderid, $key)` | POST | (callback) | Handle payment notification from PayKeeper |

### 11.4 Invoice Creation Flow

```
1. GET /info/settings/token/    --> Receive security token
2. POST /change/invoice/preview/ --> Create invoice, get invoice_id
3. Build URL: https://server.paykeeper.example.com/bill/{invoice_id}/
4. Redirect customer to payment URL
```

### 11.5 Invoice Statuses

| Status | Description |
|--------|-------------|
| `created` | Invoice created |
| `sent` | Invoice sent to customer |
| `paid` | Invoice paid |
| `expired` | Invoice expired |

### 11.6 Affected Database Tables

- `order` (`status_payment` set to 1 on successful payment)
- `paykeeper_transaction` (created via `PayKeeperTransaction::create()` with order_id, amount, invoice_id, request, response)

---

## 12. MyID Electronic Identification Service

**Source file:** `services/MyidService.php`
**Namespace:** `app\services`
**Purpose:** Integration with MyID (identity.example.com), Uzbekistan's national electronic identification system. Provides identity verification via government-issued biometric eID for user registration and KYC compliance.

### 12.1 Connection Details

| Property | Value |
|----------|-------|
| Production URL | `https://identity.example.com` |
| Sandbox URL | `https://sandbox.identity.example.com` |
| URL Selection | Configurable via `params['myid']['sandbox']` flag |
| HTTP Client | cURL (native) |
| Timeout | 30 seconds |
| Documentation | `https://docs.identity.example.com/#/en/sdknew` |

### 12.2 Authentication

**OAuth 2.0 Authorization Code Flow:**

- `client_id`: From `params['myid']['client_id']`
- `client_secret`: From `params['myid']['client_secret']`
- `redirect_uri`: From `params['myid']['redirect_uri']` (default: `http://shop.test/api/myid/callback`)
- Scope: `profile`

**Mobile SDK Authentication** uses HMAC hash:
```
hash = SHA256(client_id + timestamp + client_secret)
```

### 12.3 Key Endpoints

| Constant | Path |
|----------|------|
| `OAUTH_AUTHORIZE` | `/api/v1/oauth2/authorization` |
| `OAUTH_ACCESS_TOKEN` | `/api/v1/oauth2/access-token` |
| `USER_INFO` | `/api/v1/users/me` |
| `SDK_INIT` | `/api/v1/sdk/init` |

### 12.4 Key Methods

| Method | Description |
|--------|-------------|
| `generateSdkHash($timestamp)` | Generate SHA-256 hash for mobile SDK initialization |
| `getWebAuthUrl($state, $redirectUri, $scope)` | Build OAuth authorization URL for web redirect |
| `exchangeCodeForToken($code)` | Exchange authorization code for access token |
| `getUserData($accessToken)` | Fetch user profile data using access token |
| `verifyAndSaveUser($code, $userId)` | Full verification flow: exchange code, get data, save |
| `registerWithMyid($code, $phone)` | Register new user from MyID data |
| `getVerificationStatus($userId)` | Check if user has completed MyID verification |
| `validateSdkCallback($data)` | Validate callback data from mobile SDK |
| `isConfigured()` | Check if client_id and client_secret are set |

### 12.5 Verification Flow

```
[Web Flow]
1. getWebAuthUrl() -> redirect user to MyID
2. User authenticates with biometric eID
3. MyID redirects to callback with authorization code
4. exchangeCodeForToken(code) -> access_token
5. getUserData(access_token) -> PINFL, name, DOB, gender, photo
6. UserMyid::createFromMyidData() -> save to DB

[Mobile SDK Flow]
1. generateSdkHash(timestamp) -> {client_id, timestamp, hash}
2. Mobile app initiates MyID SDK with hash
3. SDK returns authorization code
4. Same steps 4-6 as web flow
```

### 12.6 User Data Extracted

The service extracts the following from MyID:
- **PINFL** (Personal Identification Number of Physical Persons)
- First name, last name, middle name (Latin variants)
- Birth date
- Gender (mapped: male=1, female=2)
- Phone number
- Photo (biometric)

### 12.7 Error Handling

- HTTP 400+ responses throw exceptions with error messages.
- Each operation returns step-specific error context: `'step' => 'token_exchange'|'user_data'|'pinfl_check'|'save'|'registration'`.
- PINFL uniqueness enforced -- cannot link one PINFL to multiple accounts.
- All errors logged via `Yii::error()`.

### 12.8 Affected Database Tables

- `user` (new user creation with name, phone, birthday, gender, `myid_verified=1`)
- `user_myid` (PINFL, verification status, verified_at, full MyID profile data)

---

## 13. Firebase Cloud Messaging (FCM)

**Source file:** `services/Fcm.php`
**Namespace:** `yii\services`
**Purpose:** Sending push notifications to mobile app users via Firebase Cloud Messaging (legacy HTTP API).

### 13.1 Connection Details

| Property | Value |
|----------|-------|
| Endpoint | `https://fcm.googleapis.com/fcm/send` |
| HTTP Client | GuzzleHttp\Client (via phpFCM library) |
| Protocol | HTTPS with JSON payloads |

### 13.2 Authentication

**Server API Key** in the Authorization header:
```
Authorization: key={api_key}
```
The `$key` property is currently set to an empty string -- must be configured with the Firebase project's server key.

### 13.3 Key Methods

| Method | Description |
|--------|-------------|
| `setNotification($args)` | Build notification payload (title, body, sound, icon) |
| `setData($args)` | Set custom data payload |
| `setDeviceToken($token)` | Set target device FCM token |
| `setPriority($priority)` | Set message priority |
| `send()` | Send the message via FCM API |
| `pushNotification($notification, $data, $tokens, $priority)` | High-level: send notification to one or many devices |

### 13.4 Notification Structure

```json
{
    "to": "<device_token>",
    "priority": "<high|normal>",
    "notification": {
        "title": "...",
        "body": "...",
        "sound": "call",
        "icon": "ic_app_icon"
    },
    "data": {
        "custom_key": "custom_value"
    }
}
```

### 13.5 Batch Sending

The `pushNotification()` method supports arrays of device tokens, processing them in chunks of **1,000** (FCM limit per request). Each token is sent individually using the `to` field.

### 13.6 Affected Database Tables

- None directly (device tokens are read from user/session data by calling code)

---

## 14. SMSC SMS Service

**Source file:** `services/SMSCService.php`
**Namespace:** `app\services`
**Purpose:** Sending SMS messages to users via the SMSC.ru gateway for OTP codes, order notifications, and other transactional messages.

### 14.1 Connection Details

| Property | Value |
|----------|-------|
| Base URL | `https://smsc.ru/rest/send/` |
| HTTP Client | cURL (native) |
| Protocol | HTTPS with JSON payloads |

### 14.2 Authentication

Credentials passed in the request body:
- Login: `sample_sms_login`
- Password: `sample_sms_password`

### 14.3 Key Methods

| Method | Description |
|--------|-------------|
| `send($phone, $message)` | Send an SMS to the specified phone number |

### 14.4 Request Payload

```json
{
    "mes": "<message_text>",
    "phones": "<phone_number>",
    "login": "sample_sms_login",
    "psw": "<password>"
}
```

### 14.5 Data Flow

```
[app] --> SMSCService.send(phone, message) --> [SMSC.ru API]
                                                       |
                                                       v
                                              [SMS delivered to user]
                                                       |
                                                       v
                            [SmsHistory::create(phone, message, request, response)]
```

### 14.6 Affected Database Tables

- `sms_history` (logs every SMS: phone, message, raw request, raw response)

---

## 15. Configuration Reference

**Source file:** `config/params.php`

### 15.1 All External Service Parameters

| Parameter | Purpose | Current Value |
|-----------|---------|---------------|
| `didoxPartnerToken` | Didox partner JWT token | JWT (TIN: 123456789, role: PARTNER) |
| `bts_token` | BTS legacy API token | `sample_logistics_token` |
| `bts_username` | BTS login username | `8888` |
| `bts_password` | BTS login password | `7132` |
| `bts_inn` | Company INN for BTS | `123456789` |
| `baseUrl` | Application base URL | `http://shop.test` |
| `apiSecretKey` | Internal API secret | `123` |
| `warehouseSyncEnabled` | Warehouse sync toggle | `false` |
| `warehouseApiUrl` | Warehouse API URL | `http://localhost:8080` |
| `myid.client_id` | MyID OAuth client ID | (empty) |
| `myid.client_secret` | MyID OAuth client secret | (empty) |
| `myid.redirect_uri` | MyID OAuth callback URL | `http://shop.test/api/myid/callback` |
| `myid.sandbox` | MyID sandbox mode | `true` |
| `myid.base_url` | MyID production URL | `https://identity.example.com` |
| `walletServiceUrl` | Wallet backend URL | `https://wallet.example.com` |
| `walletPaymentId` | Wallet payment category ID | `2` |
| `walletDefaultToken` | Default crypto token | `USDT` |

### 15.2 Database-Stored Settings (`settings` table)

| Setting Type | Purpose |
|-------------|---------|
| `didox_pfx_path` | PFX certificate file name |
| `didox_pfx_password` | PFX certificate password |
| `didox_signer_url` | Local signer service URL |
| `didox_seller_inn` | Seller TIN for invoices |
| `didox_seller_name` | Seller legal name |
| `didox_seller_address` | Seller address |
| `didox_seller_account` | Seller bank account |
| `didox_seller_mfo` | Seller bank MFO code |
| `didox_seller_vat_reg_code` | Seller VAT registration code |
| `didox_eimzo_token` | Stored E-IMZO session token |
| `didox_eimzo_tax_id` | Tax ID of token owner |

---

## 16. Service Dependency Matrix

```
Service              Depends On              Triggered By
-----------          ----------              ------------
BTS                  config/params.php       Order creation, admin panel
DidoxService         settings table, Signer  DidoxOrderService, admin Didox UI
DidoxOrderService    DidoxService, Order      Order status change, admin action
WalletService        config/params.php       Cart checkout, admin user view
Click                TransactionClick model  Click.uz callback
Payme                payme_password table     Payme callback (JSON-RPC)
PaymeSubscribe       (hardcoded creds)        API card/receipt endpoints
Octo                 (hardcoded creds)        Payment initiation
YooKassa             (hardcoded creds)        Payment initiation
PayKeeper            (hardcoded creds)        Payment initiation, callback
MyidService          config/params.php       User registration, verification
Fcm                  (hardcoded key)          Order status changes, promotions
SMSCService          (hardcoded creds)        OTP, order notifications
```

---

## 17. Security Considerations

### 17.1 Credential Storage

| Risk Level | Issue | Services Affected |
|------------|-------|-------------------|
| **HIGH** | Credentials hardcoded in source code (not in env/config) | Octo, YooKassa, PayKeeper, PaymeSubscribe, SMSC, Payme |
| **MEDIUM** | Credentials stored in `config/params.php` (should be in `.env`) | BTS, Didox partner token, Wallet URL |
| **LOW** | Credentials properly stored in database `settings` table | Didox PFX, Didox seller info |

### 17.2 SSL/TLS

All external cURL connections disable SSL peer verification (`CURLOPT_SSL_VERIFYPEER => false`). This is acceptable for development but should be enabled in production to prevent MITM attacks. Affected services: BTS, Didox, MyID.

### 17.3 Test/Sandbox Mode Indicators

Several services are currently configured with test/sandbox credentials:

- **Octo**: `test: true` flag in payment payload
- **YooKassa**: Secret key prefix `test_`
- **MyID**: `sandbox: true` in config
- **BTS**: Base URL uses `apitest.logistics.example.com` (test subdomain)
- **PayKeeper**: Callback signature verification is commented out

### 17.4 Input Validation

- **Click**: Validates MD5 signature on all incoming requests
- **Payme**: Validates Basic Auth on all incoming JSON-RPC methods
- **PayKeeper**: Signature validation is commented out (vulnerability)
- **BTS**: Client-side validation of city IDs, package types, and post types
- **Didox**: Tax ID format validation (9 digits), token format validation
- **MyID**: OAuth state parameter for CSRF protection

### 17.5 Recommendations

1. Move all hardcoded credentials to environment variables or a secrets manager.
2. Enable SSL peer verification in production.
3. Restore PayKeeper callback signature verification.
4. Replace test/sandbox credentials with production values before deployment.
5. Implement rate limiting on payment callback endpoints.
6. Add request logging for all outbound API calls for audit compliance.
7. Configure the FCM server key (currently empty).
8. Rotate the `apiSecretKey` value `123` to a strong random string.
