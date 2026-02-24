# app -- External Services & Web Service Integration Documentation

> **Version:** 1.0
> **Last Updated:** 2026-02-10
> **Platform:** Yii2 PHP (Backend), Mobile & Web (Frontend)
> **Target Markets:** Uzbekistan (primary), Russia (secondary)

---

## Table of Contents

1. [Service Summary Table](#1-service-summary-table)
2. [High-Level Architecture](#2-high-level-architecture)
3. [BTS Courier Delivery Service](#3-bts-courier-delivery-service)
4. [Didox E-Signature Platform](#4-didox-e-signature-platform)
5. [Didox Order Service (Automated Document Creation)](#5-didox-order-service)
6. [Wallet Service (Blockchain / Account Abstraction)](#6-wallet-service)
7. [Click Payment Gateway](#7-click-payment-gateway)
8. [Octo Payment Platform](#8-octo-payment-platform)
9. [YooKassa (Yandex) Payment](#9-yookassa-payment)
10. [PayKeeper Payment Processor](#10-paykeeper-payment-processor)
11. [Payme Payment Gateway](#11-payme-payment-gateway)
12. [MyID National eID Verification](#12-myid-national-eid-verification)
13. [Firebase Cloud Messaging (FCM)](#13-firebase-cloud-messaging)
14. [SMS Service (Broker API)](#14-sms-service-broker-api)
15. [SMSC SMS Service](#15-smsc-sms-service)
16. [Warehouse / Sklad Integration](#16-warehouse--sklad-integration)
17. [Configuration Reference](#17-configuration-reference)

---

## 1. Service Summary Table

| # | Service | Category | Base URL | Auth Method | File |
|---|---------|----------|----------|-------------|------|
| 1 | **BTS** | Logistics / Delivery | `https://apitest.logistics.example.com:28345` | Bearer Token (login/password + refresh) | `services/BTS.php` |
| 2 | **Didox** | E-Signature / Documents | `https://api.einvoice.example.com` (prod) / `https://stage.goodsign.biz` (dev) | Partner Token + User Key (E-IMZO PFX) | `services/DidoxService.php` |
| 3 | **Didox Order** | Auto Document Creation | (uses DidoxService) | (uses DidoxService) | `services/DidoxOrderService.php` |
| 4 | **Wallet** | Blockchain Payments | `https://wallet.example.com` | None (internal service) | `services/WalletService.php` |
| 5 | **Click** | Payment Gateway | Webhook-based (inbound) | MD5 Signature Verification | `services/Click.php` |
| 6 | **Octo** | Payment Gateway | `https://secure.octo.uz` | Shop ID + Secret | `services/Octo.php` |
| 7 | **YooKassa** | Payment Gateway | YooKassa SDK | Shop ID + API Key (Basic Auth) | `services/YooKassa.php` |
| 8 | **PayKeeper** | Payment Gateway | `https://server.paykeeper.example.com` | Basic Auth (login/password) | `services/PayKeeperService.php` |
| 9 | **Payme** | Payment Gateway | Webhook-based (inbound, JSON-RPC) | Basic Auth (Paycom login + key) | `services/Payme.php` |
| 10 | **MyID** | Identity Verification | `https://identity.example.com` (prod) / `https://sandbox.identity.example.com` (dev) | OAuth 2.0 (client_id/secret) | `services/MyidService.php` |
| 11 | **FCM** | Push Notifications | `https://fcm.googleapis.com/fcm/send` | Server API Key | `services/Fcm.php` |
| 12 | **SMS (Broker)** | SMS Notifications | `http://sms-gateway.example.com/broker-api/send` | Basic Auth | `services/Sms.php` |
| 13 | **SMSC** | SMS Notifications | `https://smsc.ru/rest/send/` | Login + Password (JSON body) | `services/SMSCService.php` |
| 14 | **Warehouse** | Inventory Sync | Configurable (`warehouseApiUrl` param) | HMAC Token (`X-Api-Token`) | `models/order/Order.php` |

---

## 2. High-Level Architecture

```
+-------------------+         +--------------------+
|  Mobile App (UZ)  |-------->|    Yii2 REST API    |
+-------------------+         |  /modules/api/      |
                              +--------+-----------+
+-------------------+                  |
|  Web Frontend     |----------------->|
+-------------------+                  |
                                       |
          +----------------------------+---------------------------+
          |                            |                           |
          v                            v                           v
  +-------+--------+      +-----------+----------+     +----------+---------+
  | PAYMENT LAYER  |      | LOGISTICS & DOCS     |     | IDENTITY & COMMS   |
  |                |      |                      |     |                    |
  | - Click        |      | - BTS Courier        |     | - MyID eID (UZ)    |
  | - Payme        |      | - Didox E-Signature  |     | - FCM Push         |
  | - Octo         |      | - Didox Order (auto) |     | - SMS Broker       |
  | - YooKassa     |      | - Warehouse/Sklad    |     | - SMSC             |
  | - PayKeeper    |      |                      |     |                    |
  | - Wallet (AA)  |      +----------------------+     +--------------------+
  +----------------+

                 +------------------------------------------+
                 |            DATABASE (MySQL)                |
                 |                                            |
                 | order, user, transaction_click,            |
                 | transaction_payme, order_product,          |
                 | didox_document, didox_document_invoice,    |
                 | didox_document_arbitrary,                  |
                 | didox_document_included_products,          |
                 | user_myid, sms_history, settings,          |
                 | paykeeper_transaction, category            |
                 +--------------------------------------------+
```

---

## 3. BTS Courier Delivery Service

**File:** `c:\laragon\www\shop\services\BTS.php`
**Purpose:** Integration with BTS (Business Transport Service), an Uzbekistan national courier/logistics platform. Handles delivery cost calculation, order creation, tracking, and regional geography data.

### Authentication

| Property | Value |
|----------|-------|
| Method | **JWT Bearer Token** (login/password, with refresh) |
| Login Endpoint | `POST /auth/login` |
| Refresh Endpoint | `POST /auth/refresh` |
| Token Lifetime | Access: 24 hours; Refresh: 30 days |
| Cache | Tokens cached via Yii cache (`bts_access_token`, `bts_refresh_token`, `bts_token_expires`) |
| Credentials Source | `Yii::$app->params['bts_username']`, `bts_password`, `bts_inn` |

### Base URL & Endpoints

| Environment | URL |
|-------------|-----|
| Test | `https://apitest.logistics.example.com:28345` |
| API Version | `v1` |
| Full pattern | `{baseUrl}/v1/{endpoint}` |

### Key API Methods

| Method | HTTP | Endpoint | Purpose |
|--------|------|----------|---------|
| `authenticate()` | POST | `/auth/login` | Obtain access + refresh tokens |
| `refreshAccessToken()` | POST | `/auth/refresh` | Renew expired access token |
| `createOrder()` | POST | `/v1/order/add` | Create a delivery order |
| `editOrder()` | POST | `/v1/order/edit/{id}` | Modify existing order |
| `getOrderInfo()` | GET | `/v1/order/detail?id=` | Get order details |
| `deleteOrder()` | POST | `/v1/order/delete/{id}` | Cancel/delete order |
| `calculateOrder()` | POST | `/v1/order-calculator` | Calculate delivery cost (new endpoint) |
| `calculateDelivery()` | POST | `/v1/order/calculate` | Calculate delivery cost (legacy) |
| `getOrderStatus()` | GET | `/v1/order/status?id=` | Get current status |
| `trackOrder()` | GET | `/v1/order/track?id=` | Track order in transit |
| `getOrderHistory()` | GET | `/v1/order/history?id=` | Full tracking history |
| `getRegions()` | -- | (local constants) | List of 14 Uzbekistan regions |
| `getCities()` | -- | (local constants) | List of 200+ cities/districts |

### Data Flow

```
Order Placement:
  [app App] --> calculateDelivery(region, city, weight)
                      --> BTS API returns delivery cost
  [app App] --> createOrder(sender, receiver, items)
                      --> BTS API returns orderId
                      --> Stored in Order model (bts_order_id)

Tracking:
  [app App] --> trackOrder(btsOrderId)
                      --> BTS returns: { orderId, status: {id, name} }
  [app App] --> getOrderHistory(btsOrderId)
                      --> BTS returns: [ {message, timestamp, status_id, location, trackingLink} ]
```

### Data Sent to BTS
- Sender/receiver addresses (region_id, city_id, address)
- Package weight, dimensions
- Order items and declared value
- Company INN for authentication

### Data Received from BTS
- Delivery cost calculation
- BTS order ID
- Tracking status and history with timestamps and locations

### Database Tables Affected
- `order` -- stores `bts_order_id`, `bts_region_id`, `bts_city_id`, `delivery_cost`
- `user` -- stores `bts_region_id`, `bts_city_id` for default address

### Error Handling
- cURL errors caught and logged via `Yii::error()`
- HTTP 401 triggers automatic token refresh; if refresh fails, full re-authentication
- `isRetry` flag prevents infinite retry loops
- Comprehensive error codes defined as class constants (400-503)

---

## 4. Didox E-Signature Platform

**File:** `c:\laragon\www\shop\services\DidoxService.php`
**Purpose:** Integration with Didox (einvoice.example.com), an Uzbekistan government-certified electronic document and e-signature platform. Enables creation, signing, and management of legally binding e-invoices and contracts using E-IMZO digital signatures.

### Authentication

The Didox integration uses a **multi-layered authentication model**:

| Layer | Header | Purpose |
|-------|--------|---------|
| Partner Token | `Authorization: Bearer {token}` or `Partner-Authorization: {token}` | Identifies the platform partner (app) |
| User Key | `user-key: {token}` | Identifies the specific Didox user (seller/signer) |

**Token Acquisition Flow:**
```
1. PFX Certificate + Signer Service --> PKCS7 Signature
2. PKCS7 --> Didox /v1/dsvs/timestamp --> timeStampTokenB64
3. timeStampTokenB64 --> POST /v1/auth/{taxId}/token/ru --> User Token
```

Alternative authentication:
- **Password auth:** `POST /v1/auth/{taxId}/password/{locale}`
- **Company login:** `POST /v1/auth/company/{companyTaxId}/login/{locale}` (requires individual token first)

### Base URLs

| Environment | URL |
|-------------|-----|
| Production | `https://api.einvoice.example.com` |
| Staging | `https://stage.goodsign.biz` |
| Selection | Automatic based on `YII_ENV_DEV` |

### Key API Methods

| Method | HTTP | Endpoint | Purpose |
|--------|------|----------|---------|
| `getAuthTokenFromPfx()` | POST | Local Signer + `/v1/dsvs/timestamp` + `/v1/auth/{taxId}/token/ru` | Automated authentication via PFX |
| `authenticateWithEimzo()` | POST | `/v1/auth/{taxId}/token/ru` | E-IMZO signature auth |
| `authenticateWithPassword()` | POST | `/v1/auth/{taxId}/password/{locale}` | Password auth |
| `loginToCompany()` | POST | `/v1/auth/company/{companyTaxId}/login/{locale}` | Company login |
| `registerUser()` | POST | `/v1/auth/signup` | Register new Didox user |
| `getUserProfile()` | GET | `/v1/profile` | Get user profile |
| `getDocuments()` | GET | `/v2/documents` | List documents (paginated) |
| `createDocument()` | POST | `/v1/documents/{docType}/create` | Create new document |
| `updateDocument()` | POST | `/v1/documents/{docId}/update/{docType}` | Update existing document |
| `getDocument()` | GET | `/v1/documents/{docId}` | Get document details |
| `getDocumentPdf()` | GET | `/v1/documents/view/{docId}/pdf/{lang}` | Download PDF |
| `signDocument()` | POST | `/v1/documents/{docId}/sign` | Sign document |
| `sendDocumentToPartner()` | POST | `/v1/documents/{docId}/send` | Send document to counterparty |
| `cancelDocument()` | POST | `/v1/documents/{docId}/cancel` | Cancel document |
| `getDocumentForSigning()` | GET | `/v1/documents/{docId}?owner=1` | Get outgoing doc for signing |
| `getIncomingDocumentForSigning()` | GET | `/v1/documents/{docId}?owner=0` | Get incoming doc for signing |
| `acceptIncomingDocument()` | POST | `/v1/documents/{docId}/sign` | Accept/sign incoming document |
| `searchProductClasses()` | GET | `/v1/profile/productClasses/` | Search IKPU codes |
| `addProfileProductClass()` | POST | `/v1/profile/productClasses` | Add IKPU code to profile |
| `removeProfileProductClass()` | DELETE | `/v1/profile/productClasses/{code}` | Remove IKPU code |
| `createTimestamp()` | POST | `/v1/dsvs/timestamp` | Create PKCS7 timestamp |

### Document Types

| Code | Type |
|------|------|
| `000` | Arbitrary Contract |
| `002` | Invoice (Schyot-Faktura) |

### Data Flow -- Document Signing

```
Step 1: Get Data to Sign
  GET /v1/documents/{docId}?owner=1 --> returns document with toSign field

Step 2: Sign with E-IMZO
  toSign value --> Local PFX Signer --> PKCS7 + signature
  PKCS7 --> POST /v1/dsvs/timestamp --> timeStampTokenB64

Step 3: Submit Signature
  POST /v1/documents/{docId}/sign  { signature: timeStampTokenB64 }
```

### Database Tables Affected
- `didox_document` -- main document record (order_id, didox_id, didox_status, document_type)
- `didox_document_invoice` -- invoice-specific data (seller/buyer TIN, amounts, VAT)
- `didox_document_arbitrary` -- contract-specific data
- `didox_document_included_products` -- line items for invoices
- `settings` -- stores Didox configuration (didox_seller_inn, didox_pfx_path, didox_signer_url, etc.)

### Error Handling
- cURL errors throw exceptions
- HTTP 4xx/5xx responses parsed for `error`, `message`, or `errors` fields
- HTTP 422 specifically checked for `validation.unique` (user already exists) and `validation.exists` (user not found)
- All errors logged via `Yii::error()` with `__METHOD__` context
- 30-second timeout on all requests

---

## 5. Didox Order Service

**File:** `c:\laragon\www\shop\services\DidoxOrderService.php`
**Purpose:** Automated creation of Didox e-documents (Invoice and Arbitrary Contract) when an order is placed or confirmed. Orchestrates the full flow: data collection, local record creation, API upload, and PDF generation.

### Key Methods

| Method | Purpose |
|--------|---------|
| `createDocuments(Order)` | Create both Invoice (002) and Contract (000) for an order |
| `createInvoice(Order)` | Create only the Invoice document |
| `createArbitrary(Order)` | Create only the Arbitrary Contract |

### Automated Document Creation Flow

```
Order Confirmed
    |
    v
DidoxOrderService::createDocuments($order)
    |
    +---> Load Seller Info from `settings` table
    |       (didox_seller_inn, didox_seller_name, etc.)
    |
    +---> Load Buyer Info from Order + User
    |       (user.eimzo_tax_id, order.inn, order.account)
    |
    +---> Acquire Didox Token:
    |       1. Try session token
    |       2. Try auto-auth via PFX (getAuthTokenFromPfx)
    |       3. Fallback to stored system token (settings: didox_eimzo_token)
    |
    +---> Verify seller info against Didox profile
    |
    +---> Create Invoice (doctype 002):
    |       - Save DidoxDocument + DidoxDocumentInvoice + IncludedProducts
    |       - Upload to Didox API via createDocument()
    |       - Auto-download PDF in uz/ru languages
    |
    +---> Create Contract (doctype 000):
            - Save DidoxDocument + DidoxDocumentArbitrary
            - Generate contract PDF locally
            - Upload to Didox API via createDocument()
            - Auto-download PDF
```

### Data Sent to Didox
- **Invoice:** Seller TIN/name/address/account/MFO/VAT code, Buyer TIN/name/address, product list with IKPU codes, quantities, prices, 12% VAT calculations
- **Contract:** Seller and buyer details, contract terms, attached PDF document (base64)

### Database Tables Affected
- `didox_document` -- document metadata
- `didox_document_invoice` -- invoice details
- `didox_document_arbitrary` -- contract details
- `didox_document_included_products` -- line items
- `settings` -- configuration source
- `order` -- source order data
- `order_product` -- source product line items
- `log` -- detailed logging of all operations

### Error Handling
- Database transactions with rollback on failure
- Duplicate prevention: checks if document already exists before creation
- Extensive logging via `Log::log('didox_order', ...)` at every step
- Token owner vs. seller TIN mismatch detection with warning
- PDF download failures are non-blocking (logged as warning)

---

## 6. Wallet Service

**File:** `c:\laragon\www\shop\services\WalletService.php`
**Purpose:** Integration with an external Account Abstraction (AA) wallet backend for blockchain-based payments using USDT and USDC tokens on an EVM-compatible chain.

### Authentication

| Property | Value |
|----------|-------|
| Method | **None** (internal microservice, no API key) |
| Transport | HTTP via GuzzleHttp Client |

### Base URL

| Environment | URL |
|-------------|-----|
| Production | `https://wallet.example.com` |
| Default | `http://localhost:3001` |
| Config Key | `Yii::$app->params['walletServiceUrl']` |

### Supported Tokens

| Symbol | Contract Address |
|--------|-----------------|
| USDT | `0x7b95CaDaf3Fe1154A7B663f3793856F7e9f21d16` |
| USDC | `0x3f4A04341122360b304C9A896a2Dbfe4cca5B4AE` |

### Key API Methods

| Method | HTTP | Endpoint | Purpose |
|--------|------|----------|---------|
| `ensureWallet()` | GET | `/wallet/address` | Get or create wallet, return EOA address |
| `getAAAddress()` | GET | `/wallet/address` | Get Account Abstraction address |
| `getWalletStatus()` | GET | `/wallet/status` | Check if wallet is deployed on-chain |
| `getBalance()` | GET | `/wallet/balance` | Get wallet balance |
| `deployWallet()` | POST | `/wallet/deploy` | Deploy AA wallet on-chain |
| `mintToken()` | POST | `/token/mint` | Mint tokens to an address |
| `pay()` | POST | `/payment/execute-batch` | Execute batched payment (payer -> merchant) |
| `approvePayment()` | POST | `/payment/approve` | Pre-approve a payment |
| `buildBatch()` | POST | `/payment/build-batch` | Build batch without executing |
| `getSupportedTokens()` | GET | `/payment/supported-tokens` | List supported tokens |
| `transfer()` | -- | -- | **DEPRECATED** -- throws exception, use `pay()` |

### Data Flow -- Payment

```
Buyer initiates crypto payment:
    |
    v
approvePayment(payerId, merchantId, amount, 'USDT')
    --> Wallet Backend approves token transfer
    |
    v
pay(payerId, merchantId, amount, 'USDT')
    --> Wallet Backend executes batch:
        1. Transfer USDT from payer AA wallet to merchant AA wallet
    --> Returns: { txHash, status }
```

### Data Sent
- `userId` + `userLogin` (phone/email) for wallet operations
- `payerId`, `merchantId`, `amount`, `symbol` for payments
- `token` (contract address), `to`, `amount` for minting

### Data Received
- `eoaAddress`, `aaAddress` -- wallet addresses
- `balance`, `isDeployed` -- wallet status
- `txHash` -- transaction hashes

### Database Tables Affected
- `user` -- wallet addresses cached on user model
- `category` -- wallet payment type registered as payment category

### Error Handling
- All methods wrapped in try/catch
- Errors logged via `Yii::error()`
- Critical operations (`deployWallet`, `mintToken`, `pay`) re-throw exceptions
- Read-only operations return safe defaults (empty arrays, `false`, `0`)

---

## 7. Click Payment Gateway

**File:** `c:\laragon\www\shop\services\Click.php`
**Purpose:** Integration with Click.uz, an Uzbekistan payment platform. Implements the standard Click merchant API with prepare/complete two-phase flow.

### Authentication

| Property | Value |
|----------|-------|
| Method | **MD5 Signature Verification** |
| Signature Formula | `md5(click_trans_id + service_id + '' + merchant_trans_id + [merchant_prepare_id] + amount + action + sign_time)` |
| Direction | **Inbound** -- Click calls app's webhook |

### API Methods (Webhook Handlers)

| Method | Action | Purpose |
|--------|--------|---------|
| `prepare()` | action=0 | Phase 1: Validate order, create transaction record |
| `complete()` | action=1 | Phase 2: Confirm payment, mark order as paid |

### Data Flow

```
Click System --> POST /payment/click (prepare, action=0)
    app validates:
      - sign_string matches computed MD5
      - Order exists and is unpaid
      - Amount matches order total (product_total)
    app creates TransactionClick record
    Returns: { click_trans_id, merchant_trans_id, merchant_prepare_id }

Click System --> POST /payment/click (complete, action=1)
    app validates:
      - sign_string, order exists, amounts match
      - Transaction exists and is not already completed
    app sets:
      - TransactionClick.status = 1
      - Order.status_paid = 1
      - User.balance += order.product_total
    Returns: { click_trans_id, merchant_trans_id, merchant_confirm_id }
```

### Error Codes

| Code | Meaning |
|------|---------|
| 0 | Success |
| -1 | Sign check failed |
| -2 | Incorrect amount |
| -4 | Already paid |
| -5 | User/order not found |
| -6 | Transaction not found |
| -7 | Failed to update user |
| -9 | Transaction cancelled |

### Database Tables Affected
- `transaction_click` -- transaction records (click_trans_id, amount, account, status, error)
- `order` -- `status_paid` updated to 1 on success
- `user` -- `balance` incremented on successful payment

### Error Handling
- Signature validation on every request
- Error codes returned as JSON responses with `exit(json_encode(...))`
- Specific handling for Click error code `-5017` (transaction cancelled)

---

## 8. Octo Payment Platform

**File:** `c:\laragon\www\shop\services\Octo.php`
**Purpose:** Integration with Octo.uz payment platform for Uzbekistan. Handles payment preparation (checkout URL generation).

### Authentication

| Property | Value |
|----------|-------|
| Method | **Shop ID + Secret** (in request body) |
| Shop ID | `4874` |
| Notify URL | `http://checkout.example.com/payment/octo/notify` |

### Base URL

`https://secure.octo.uz`

### Key API Methods

| Method | HTTP | Endpoint | Purpose |
|--------|------|----------|---------|
| `prepare()` | POST | `/prepare_payment` | Create payment session, get checkout URL |

### Data Sent
```json
{
    "octo_shop_id": "4874",
    "octo_secret": "{secret}",
    "shop_transaction_id": "{order_id}",
    "auto_capture": true,
    "test": true,
    "init_time": "2026-02-10 12:00:00",
    "total_sum": 150000,
    "currency": "UZS",
    "description": "Order payment",
    "language": "ru",
    "notify_url": "http://checkout.example.com/payment/octo/notify",
    "ttl": 15
}
```

### Data Received
- Payment URL for redirect
- Payment status notifications via `notify_url`

### Database Tables Affected
- `order` -- order lookup for payment

### Error Handling
- Raw cURL response returned without explicit error handling
- Currently in **test mode** (`"test": true`)

---

## 9. YooKassa Payment

**File:** `c:\laragon\www\shop\services\YooKassa.php`
**Purpose:** Integration with YooKassa (formerly Yandex.Checkout) for Russian market payments. Uses the official YooKassa PHP SDK.

### Authentication

| Property | Value |
|----------|-------|
| Method | **Shop ID + API Key** (SDK handles Basic Auth) |
| Shop ID | `239537` |
| SDK | `yoomoney/yookassa-sdk-php` |

### Key API Methods

| Method | Purpose |
|--------|---------|
| `createPayment($amount, $description, $type)` | Create payment and return confirmation URL |

### Data Sent
```json
{
    "amount": { "value": "1500.00", "currency": "RUB" },
    "description": "Order description",
    "confirmation": {
        "type": "redirect",
        "return_url": "https://shop.test/payment/success"
    },
    "payment_method_data": { "type": "bank_card" }
}
```

### Data Received
- Confirmation URL for redirect to YooKassa checkout page

### Database Tables Affected
- `order` -- indirectly through payment success callback

### Error Handling
- SDK handles HTTP communication and error parsing
- Idempotency key generated via `uniqid('', true)`

---

## 10. PayKeeper Payment Processor

**File:** `c:\laragon\www\shop\services\PayKeeperService.php`
**Purpose:** Integration with PayKeeper payment processing platform. Supports inline payment forms, invoice generation, and payment status checking.

### Authentication

| Property | Value |
|----------|-------|
| Method | **HTTP Basic Auth** (base64 encoded) |
| Login | `admin` |
| Base URL | `https://server.paykeeper.example.com` |

### Key API Methods

| Method | HTTP | Endpoint | Purpose |
|--------|------|----------|---------|
| `get_payform()` | POST | `/order/inline/` | Get inline HTML payment form |
| `get_invoice_url()` | GET+POST | `/info/settings/token/` then `/change/invoice/preview/` | Two-step: get security token, then create invoice |
| `get_invoice_status()` | GET | `/info/invoice/byid/?id=` | Check invoice payment status |
| `notify()` | -- | Inbound callback | Handle payment notification |

### Invoice Creation Flow

```
Step 1: GET /info/settings/token/
    --> Returns: { token: "abc123" }

Step 2: POST /change/invoice/preview/
    Body: pay_amount={amount}&orderid={id}&token={token}
    --> Returns: { invoice_id: 456 }

Step 3: Construct URL:
    https://server.paykeeper.example.com/bill/456/
```

### Invoice Statuses

| Status | Meaning |
|--------|---------|
| `created` | Invoice created |
| `sent` | Invoice sent to customer |
| `paid` | Payment received |
| `expired` | Invoice expired |

### Database Tables Affected
- `paykeeper_transaction` -- stores order_id, amount, invoice_id, request/response data
- `order` -- `status_payment` set to 1 on successful notify callback

### Error Handling
- Returns `false` on missing token or invoice_id in responses
- Notify callback signature verification is present but commented out
- cURL-based HTTP communication

---

## 11. Payme Payment Gateway

**Files:**
- `c:\laragon\www\shop\services\Payme.php` -- Core Payme logic
- `c:\laragon\www\shop\controllers\payment\PaymeController.php` -- Webhook entry point
- `c:\laragon\www\shop\services\payme\AbstractPayme.php` -- Base class
- `c:\laragon\www\shop\services\payme\PaymeResponse.php` -- Response builder

**Purpose:** Integration with Payme.uz, Uzbekistan's largest payment platform. Implements the Payme Merchant API (JSON-RPC 2.0) with full transaction lifecycle.

### Authentication

| Property | Value |
|----------|-------|
| Method | **HTTP Basic Auth** |
| Login | `Paycom` |
| Password | Stored in `payme_password` DB table |
| Direction | **Inbound** -- Payme calls app's endpoint |

### JSON-RPC Methods

| Method | Purpose |
|--------|---------|
| `CheckPerformTransaction` | Validate if payment can be processed for order |
| `CreateTransaction` | Create a pending Payme transaction |
| `PerformTransaction` | Execute the payment (mark as completed) |
| `CheckTransaction` | Query transaction state |
| `CancelTransaction` | Cancel or refund a transaction |
| `GetStatement` | Get list of transactions for reconciliation |
| `ChangePassword` | Update merchant password |

### Transaction States

| State | Meaning |
|-------|---------|
| 1 | Pending (created) |
| 2 | Performed (completed) |
| -1 | Cancelled before completion |
| -2 | Cancelled after completion (refund) |

### Data Flow

```
Payme Server --> POST /payment/payme (JSON-RPC)
    {
        "method": "CreateTransaction",
        "params": {
            "id": "payme_trans_id",
            "time": 1700000000000,
            "amount": 150000,       // in tiyin (100 = 1 sum)
            "account": { "id": "order_123" }
        }
    }

app validates:
    - Basic Auth credentials
    - Order exists
    - Amount matches order price
    - No conflicting transactions

On PerformTransaction:
    - Order.status_payment = 1
    - Transaction state = 2
    - Inserts into transaction_payme
```

### Amount Handling
- Payme sends amounts in **tiyin** (1/100 of UZS sum)
- Conversion: `$amount / 100`

### Database Tables Affected
- `transaction_payme` -- transaction records (transaction, payme_time, amount, state, user_id, order_id)
- `order` -- `status_payment` updated (1 = paid, 2 = cancelled/refunded)
- `payme_password` -- stores current merchant password

### Error Handling
- Standard JSON-RPC error responses via `PaymeResponse`
- Transaction timeout check (6000 seconds)
- State machine validation prevents invalid transitions
- Error codes: AUTH_ERROR, JSON_RPC_ERROR, USER_NOT_FOUND, WRONG_AMOUNT, CANT_PERFORM_TRANS, TRANS_NOT_FOUND, PENDING_PAYMENT, CANT_CANCEL_TRANSACTION, SYSTEM_ERROR

---

## 12. MyID National eID Verification

**File:** `c:\laragon\www\shop\services\MyidService.php`
**Purpose:** Integration with identity.example.com, Uzbekistan's national electronic identity verification system. Supports OAuth 2.0 web flow and mobile SDK for biometric identity verification using national ID documents.

### Authentication

| Property | Value |
|----------|-------|
| Method | **OAuth 2.0 Authorization Code Flow** |
| Client ID | Configured in `params['myid']['client_id']` |
| Client Secret | Configured in `params['myid']['client_secret']` |

### Base URLs

| Environment | URL |
|-------------|-----|
| Production | `https://identity.example.com` |
| Sandbox | `https://sandbox.identity.example.com` |
| Config | `params['myid']['sandbox']` (boolean toggle) |

### OAuth Endpoints

| Endpoint | Path | Purpose |
|----------|------|---------|
| Authorization | `/api/v1/oauth2/authorization` | Redirect user for consent |
| Access Token | `/api/v1/oauth2/access-token` | Exchange code for token |
| User Info | `/api/v1/users/me` | Get verified identity data |
| SDK Init | `/api/v1/sdk/init` | Initialize mobile SDK |

### Key Methods

| Method | Purpose |
|--------|---------|
| `generateSdkHash()` | Generate SHA-256 hash for mobile SDK init |
| `getWebAuthUrl()` | Build OAuth authorization URL with CSRF state |
| `exchangeCodeForToken()` | Exchange auth code for access token |
| `getUserData()` | Fetch verified user profile from MyID |
| `verifyAndSaveUser()` | Full flow: code -> token -> user data -> save to DB |
| `registerWithMyid()` | Register new user using MyID-verified identity |
| `getVerificationStatus()` | Check user's verification status |

### SDK Hash Generation (Mobile)
```
payload = client_id + timestamp_ms + client_secret
hash = SHA-256(payload)
```

### Data Flow -- Verification

```
User clicks "Verify with MyID"
    |
    v
getWebAuthUrl() --> Redirect to MyID
    |
    v
User completes biometric verification at MyID
    |
    v
Callback: /api/myid/callback?code=XXX&state=YYY
    |
    v
verifyAndSaveUser(code, userId):
    1. exchangeCodeForToken(code) --> access_token
    2. getUserData(access_token) --> { pinfl, first_name, last_name, birth_date, gender, ... }
    3. Check if PINFL already linked to another user
    4. Save to UserMyid model
    5. Update User (myid_verified = 1)
```

### Data Received from MyID
- PINFL (Personal Identification Number of Physical Persons)
- First/last/middle name (Latin)
- Birth date
- Gender
- Phone number
- Photo (in some flows)

### Database Tables Affected
- `user_myid` -- verification data (pinfl, user_id, verification_status, names, birth_date, verified_at)
- `user` -- `myid_verified` flag, name, lastname, middlename, phone, birthday, gender

### Error Handling
- OAuth errors return structured `{ success: false, error, step }` responses
- PINFL duplication check prevents one ID from linking to multiple accounts
- Database transaction wraps user + MyID record creation with rollback
- cURL timeout: 30 seconds, SSL verification disabled
- All errors logged via `Yii::error()` with method context

---

## 13. Firebase Cloud Messaging

**File:** `c:\laragon\www\shop\services\Fcm.php`
**Purpose:** Send push notifications to mobile app users via Google's Firebase Cloud Messaging legacy HTTP API.

### Authentication

| Property | Value |
|----------|-------|
| Method | **Server API Key** (`Authorization: key={key}`) |
| Key | Stored in class property (currently empty) |

### Base URL

`https://fcm.googleapis.com/fcm/send`

### Key Methods

| Method | Purpose |
|--------|---------|
| `pushNotification()` | Send push to one or many device tokens |
| `setNotification()` | Set notification title, body, sound, icon |
| `setData()` | Set custom data payload |
| `setDeviceToken()` | Set target device |
| `send()` | Execute the HTTP request |

### Data Sent
```json
{
    "to": "device_token_here",
    "priority": "high",
    "notification": {
        "title": "Notification Title",
        "body": "Notification body text",
        "sound": "call",
        "icon": "ic_app_icon"
    },
    "data": {
        "custom_key": "custom_value"
    }
}
```

### Batch Processing
- Device tokens split into chunks of 1000
- Each token receives an individual request (no topic/group messaging)

### Database Tables Affected
- None directly (reads device tokens from user records)

### Error Handling
- Uses GuzzleHttp Client internally via `phpFCM\Client`
- Output buffering started before send (`ob_start()`)
- No explicit error handling on failed sends

---

## 14. SMS Service (Broker API)

**File:** `c:\laragon\www\shop\services\Sms.php`
**Purpose:** Send SMS messages via a broker gateway API (Uzbekistan local SMS aggregator). Supports single and bulk messaging.

### Authentication

| Property | Value |
|----------|-------|
| Method | **HTTP Basic Auth** (base64 encoded) |
| Login | `sample_sms_login` |
| Originator | `3700` |

### Base URL

`http://sms-gateway.example.com/broker-api/send`

### Key Methods

| Method | Purpose |
|--------|---------|
| `send($phone, $message)` | Send single SMS |
| `add($phone, $message)` | Queue message for bulk send |
| `sendAll()` | Send all queued messages (max 500 per batch) |

### Data Sent
```json
{
    "messages": [{
        "recipient": "+998901234567",
        "message-id": "brm12345",
        "sms": {
            "originator": "3700",
            "content": {
                "text": "Your verification code: 1234"
            }
        }
    }]
}
```

### Database Tables Affected
- None directly (SMS sending is fire-and-forget)

### Error Handling
- Raw cURL response returned
- No error checking on HTTP response

---

## 15. SMSC SMS Service

**File:** `c:\laragon\www\shop\services\SMSCService.php`
**Purpose:** Send SMS messages via SMSC.ru gateway. Used as an alternative or supplementary SMS delivery channel.

### Authentication

| Property | Value |
|----------|-------|
| Method | **Login + Password** (in JSON body) |
| Base URL | `https://smsc.ru/rest/send/` |
| Login | `sample_sms_login` |

### Key Methods

| Method | Purpose |
|--------|---------|
| `send($phone, $message)` | Send single SMS |

### Data Sent
```json
{
    "mes": "Your code: 1234",
    "phones": "+998901234567",
    "login": "sample_sms_login",
    "psw": "***"
}
```

### Database Tables Affected
- `sms_history` -- logs every SMS sent (phone, message, request, response) via `SmsHistory::create()`

### Error Handling
- Response decoded from JSON and returned to caller
- All sends logged to `sms_history` table for audit

---

## 16. Warehouse / Sklad Integration

**File:** `c:\laragon\www\shop\models\order\Order.php` (method `sendOrderToWarehouse()`)
**Purpose:** Synchronize new orders with an external warehouse/inventory management system (Sklad) for fulfillment processing.

### Authentication

| Property | Value |
|----------|-------|
| Method | **HMAC Token** via `X-Api-Token` header |
| Token Formula | `md5(order_id + apiSecretKey)` |
| Config | `Yii::$app->params['apiSecretKey']` |

### Base URL

| Property | Value |
|----------|-------|
| Config Key | `Yii::$app->params['warehouseApiUrl']` |
| Default | `http://warehouse.example.com` |
| Dev Default | `http://localhost:8080` |

### Endpoint

`POST /api/sales/create-from-ecommerce`

### Feature Toggle
```php
// Config: params['warehouseSyncEnabled']
// true  = sync enabled (default)
// false = sync disabled (development)
```

### Data Sent
```json
{
    "id": 123,
    "yii_order_id": 123,
    "items": [
        {
            "product_id": 45,
            "quantity": 2,
            "price": 50000
        }
    ]
}
```

### Data Received
- HTTP 201 with `{ "success": true }` on success
- Error message on failure

### Database Tables Affected
- `order` -- source data (triggers on order creation)
- `order_product` -- items sent to warehouse

### Error Handling
- GuzzleHttp Client with 10-second timeout
- HTTP response status checked (expects 201)
- `RequestException` caught with detailed error parsing:
  - JSON response body parsed for `message` field
  - Non-JSON responses logged as-is
  - Connection failures logged and re-thrown with user-friendly message
- Exceptions propagate to order creation flow (can prevent order completion)
- All errors logged via `Yii::error()` under `warehouse_sync` category

---

## 17. Configuration Reference

**File:** `c:\laragon\www\shop\config\params.php`

```php
return [
    // General
    'baseUrl'               => 'http://shop.test',
    'apiSecretKey'          => '123',      // Used by Warehouse sync

    // BTS Delivery
    'bts_token'             => '3d0c...833',
    'bts_username'          => '8888',
    'bts_password'          => '7132',
    'bts_inn'               => '123456789',

    // Didox
    'didoxPartnerToken'     => 'eyJhbGci...MAZS',  // JWT Partner Token

    // Warehouse / Sklad
    'warehouseSyncEnabled'  => false,       // Toggle warehouse sync
    'warehouseApiUrl'       => 'http://localhost:8080',

    // MyID
    'myid' => [
        'client_id'     => '',
        'client_secret' => '',
        'redirect_uri'  => 'http://shop.test/api/myid/callback',
        'sandbox'       => true,
        'base_url'      => 'https://identity.example.com',
    ],

    // Wallet (Blockchain)
    'walletServiceUrl'      => 'https://wallet.example.com',
    'walletPaymentId'       => 2,           // Category ID for wallet payment type
    'walletDefaultToken'    => 'USDT',
];
```

### Additional Configuration (Database `settings` table)

| Setting Key | Purpose |
|-------------|---------|
| `didox_seller_inn` | Seller Tax ID for Didox documents |
| `didox_seller_name` | Seller company name |
| `didox_seller_address` | Seller address |
| `didox_seller_account` | Seller bank account |
| `didox_seller_mfo` | Seller bank MFO code |
| `didox_seller_vat_reg_code` | Seller VAT registration code |
| `didox_pfx_path` | Path to E-IMZO PFX certificate file |
| `didox_pfx_password` | PFX certificate password |
| `didox_signer_url` | Local signer service URL (default: `http://127.0.0.1:8080/generate`) |
| `didox_eimzo_token` | Stored system Didox token (fallback) |
| `didox_eimzo_tax_id` | Tax ID of the token owner |

---

## Appendix: Payment Gateway Comparison

| Feature | Click | Payme | Octo | YooKassa | PayKeeper | Wallet |
|---------|-------|-------|------|----------|-----------|--------|
| **Country** | UZ | UZ | UZ | RU | RU | Global |
| **Currency** | UZS | UZS (tiyin) | UZS | RUB | RUB | USDT/USDC |
| **Direction** | Inbound webhook | Inbound JSON-RPC | Outbound API | Outbound SDK | Outbound API | Outbound API |
| **Auth** | MD5 signature | Basic Auth | Shop secret | Shop ID + Key | Basic Auth | None |
| **Two-phase** | Yes (prepare/complete) | Yes (create/perform) | No | No | Yes (token/invoice) | Yes (approve/pay) |
| **Cancellation** | Error code -5017 | CancelTransaction | N/A | N/A | N/A | N/A |
| **Transaction Table** | `transaction_click` | `transaction_payme` | None | None | `paykeeper_transaction` | None |
| **Test Mode** | N/A | Password-based | `test: true` flag | Test API key | N/A | Testnet tokens |
