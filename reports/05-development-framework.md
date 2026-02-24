# app — Development Framework Description

## 1. Application Overview

**app** is a comprehensive B2B/B2C e-commerce platform designed for the Uzbekistan market. The platform enables sellers (shops) to list products, manage inventory, and process orders, while customers can browse products, place orders, and pay using multiple payment methods including cryptocurrency wallets.

The platform serves as a complete digital commerce ecosystem integrating:
- Product catalog management with multi-warehouse stock tracking
- Order processing with automated delivery logistics (BTS courier service)
- Multiple payment gateways (Payme, Click, Octo, YooKassa, crypto wallet)
- Electronic document signing and invoicing (Didox E-IMZO)
- National digital identity verification (MyID)
- Warehouse inventory synchronization with external ERP systems

**Target Market**: Uzbekistan and Central Asian region
**Languages Supported**: Russian, Uzbek, English (full multilingual support across all content)

---

## 2. System Architecture

### 2.1. Architectural Pattern

app follows the **Model-View-Controller (MVC)** pattern implemented through the **Yii2 Framework**. The application uses a **modular architecture** with the following modules:

| Module | Purpose | Access |
|--------|---------|--------|
| **api** | RESTful API for mobile/web clients | Bearer Token auth |
| **admin** | Administrative panel | Admin/Moderator roles |
| **shop** | Seller dashboard | Shop owner role |
| **dashboard** | Analytics and reports | Shop owner role |
| **logist** | Logistics management | Logist role |
| **billz** | Billing integration | Internal |

### 2.2. API-First Design

The platform is built with an **API-first architecture**:
- All business logic is exposed through RESTful JSON endpoints
- Mobile applications (iOS/Android) consume the API directly
- Web frontends communicate exclusively via API calls
- The admin panel uses traditional server-rendered Yii2 views
- All API responses follow a consistent data envelope format: `{"data": {...}}`

### 2.3. Module Communication Flow

```
Mobile App / Web Client
        |
        v
   [REST API Module]  <-->  [External Services]
        |                    (BTS, Didox, Wallet,
        v                     Payme, Warehouse)
   [Business Logic]
   (Models + Services)
        |
        v
   [MySQL Database]
```

---

## 3. Technology Stack

### 3.1. Backend
- **Language**: PHP 8.2.25
- **Framework**: Yii2 (Model-View-Controller)
- **ORM**: Yii2 ActiveRecord (database abstraction layer)
- **HTTP Client**: Guzzle HTTP (for external API integrations)
- **Image Processing**: Intervention Image (photo resizing, thumbnails)

### 3.2. Database
- **RDBMS**: MySQL 8.0.30
- **Character Set**: utf8mb4 (full Unicode support including emojis)
- **Tables**: 83 tables organized across 10 domain areas
- **Migrations**: Yii2 migration system for schema version control

### 3.3. External Microservice
- **Wallet Service**: Node.js application hosting Account Abstraction (AA) smart contract wallets on the Ethereum blockchain, supporting USDT and USDC token payments

### 3.4. Development Environment
- **Local Stack**: Laragon (Apache + MySQL + PHP on Windows)
- **Version Control**: Git
- **Database Admin**: phpMyAdmin 5.2.0
- **AI Assistant**: Claude Code (development support)

---

## 4. Key Functionality

### 4.1. User Management
- **5 user roles**: Administrator, Moderator, Customer (physical/legal entity), Shop Owner, Logistics Provider
- **Authentication**: Phone number + SMS code verification, token-based API access
- **Digital Identity**: MyID (Uzbek national eID) integration for identity verification
- **E-Signature**: E-IMZO digital certificate support via Didox platform
- **User Types**: Physical person (`fiz`) and Legal entity (`yur`) with different profile fields (INN, bank account, MFO, OKED)

### 4.2. Product Catalog
- **Multilingual content**: Product names, descriptions in 3 languages (RU, UZ, EN)
- **Auto-transliteration**: Automatic Cyrillic-to-Latin transliteration for search optimization
- **Pricing tiers**: Retail price, small wholesale price, big wholesale price with configurable quantity thresholds
- **Product variants**: Color-based variants linked via `token_key` grouping
- **Categorization**: Hierarchical categories, brands, filters, product types
- **IKPU codes**: Uzbekistan product classification system integration for Didox invoicing
- **Stock tracking**: Per-warehouse inventory with real-time decrement on order placement
- **Moderation workflow**: Products go through admin approval before publishing (`pending_products` table)
- **Warehouse sync**: Automatic synchronization with external warehouse ERP system

### 4.3. Order Processing
- **Order lifecycle**: Cart -> Checkout -> Payment -> BTS Delivery -> Completion -> Review
- **Multi-stock grouping**: Orders automatically split by warehouse (stock) for optimal delivery routing
- **Delivery cost calculation**: Real-time BTS API calls for weight/volume-based shipping costs
- **Promocode system**: Percentage and fixed-amount discounts with category/product restrictions, usage limits, and date validity
- **Automated document generation**: Didox Invoice and Contract documents created automatically on order completion
- **Order statuses**: Separate tracking for order status, payment status, delivery status, logistics status, and review status

### 4.4. Payment System

The platform integrates **7 payment gateways** and a **blockchain wallet**:

| Gateway | Type | Region |
|---------|------|--------|
| **Payme** | Mobile money | Uzbekistan |
| **Click** | Payment aggregator | Uzbekistan |
| **Octo** | Payment platform | Uzbekistan |
| **Wallet (USDT/USDC)** | Blockchain (Ethereum AA) | International |
| **YooKassa** | Online payments | Russia/CIS |
| **PayKeeper** | Payment processor | CIS |
| **Cash on Delivery** | Offline | All regions |

**Wallet Payment Features**:
- Account Abstraction smart contract wallets
- Support for USDT and USDC (ERC-20) tokens
- Wallet deployment, balance checking, and batch payments
- Automatic payment processing during order creation

### 4.5. Delivery & Logistics (BTS)
- Integration with **BTS** — Uzbekistan's regional courier delivery service
- Automatic delivery order creation grouped by warehouse location
- Real-time delivery cost calculation based on weight, volume, and distance
- Coverage across all 14 regions of Uzbekistan
- BTS order tracking with status updates
- Support for both courier delivery and self-pickup from BTS offices

### 4.6. E-Invoicing & Digital Signatures (Didox)
- Automated creation of **electronic invoices** (Счёт-фактура) on order completion
- Automated creation of **electronic contracts** (Договор) for each order
- Digital signature using PFX certificates and E-IMZO infrastructure
- IKPU product code mapping for tax compliance
- VAT calculation (12% standard rate)
- PDF document generation in Russian and Uzbek
- Integration with Uzbekistan's national electronic document system

### 4.7. Warehouse Integration
- Synchronization with external warehouse management system (Sklad)
- Automatic order forwarding to warehouse on creation
- Product sync workflow (pending -> confirmed -> synced)
- HMAC-based API authentication for secure communication

---

## 5. Security Architecture

### 5.1. Authentication
- **Bearer Token Authentication**: All API requests require `Authorization: Bearer {token}` header
- **Token Generation**: Cryptographically secure random tokens generated via Yii2 security component
- **SMS Verification**: Phone-based authentication with time-limited SMS codes
- **Session-less API**: Stateless API design — no server-side sessions for API clients
- **Password Hashing**: bcrypt algorithm via Yii2 security (`$2y$13$...` format)

### 5.2. Authorization
- **Role-Based Access Control (RBAC)**: 5 distinct user roles with different permissions
  - **Admin (role=1)**: Full platform access
  - **Moderator (role=2)**: Configurable access via `moderator_access` table — each moderator can be granted access to specific admin sections
  - **User (role=3)**: Customer features (browsing, cart, orders, wallet, reviews)
  - **Shop (role=4)**: Seller features (product management, order fulfillment, shop settings)
  - **Logist (role=5)**: Delivery management features
- **Controller-level auth**: Each controller defines `behaviors()` with authentication filters
- **Optional auth**: Public endpoints (product listing, categories) allow unauthenticated access

### 5.3. Data Protection
- **Input Validation**: All model attributes validated via Yii2 rules (type checking, string length, existence validation, required fields)
- **SQL Injection Prevention**: ActiveRecord ORM with parameterized queries
- **CORS Configuration**: Cross-Origin Resource Sharing enabled for API access from web clients
- **CSRF Protection**: Enabled for web forms (admin panel), disabled for stateless API
- **Foreign Key Constraints**: Database-level referential integrity (CASCADE delete/update)
- **HMAC Authentication**: Warehouse API uses MD5-based HMAC tokens for request verification

### 5.4. External Service Security
- **OAuth 2.0**: Used for BTS delivery service and MyID identity provider
- **PFX Certificates**: Digital certificate-based authentication for Didox e-signature
- **HTTP Basic Auth**: Used for Payme payment gateway webhook verification
- **Token Caching**: BTS access tokens cached for 1 day, refresh tokens for 30 days
- **HTTPS**: All external API communications over encrypted HTTPS connections

---

## 6. Multilingual Support

The platform provides full trilingual support:

| Language | Code | Usage |
|----------|------|-------|
| **Russian** | `ru` | Primary language, all content fields (`name_ru`, `description_ru`) |
| **Uzbek** | `uz` | Secondary language, all content fields (`name_uz`, `description_uz`) |
| **English** | `en` | International support, all content fields (`name_en`, `description_en`) |

- Language selection via `Content-Language` HTTP header
- Automatic fallback to Russian if requested language content is empty
- All database entities (products, categories, brands, delivery methods, regions, cities) store content in all 3 languages
- BTS delivery service status labels available in all 3 languages
- Auto-transliteration service for generating search-optimized text (`name_trans_ru`, `name_trans_en`)

---

## 7. Database Architecture

The MySQL database `app` contains **83 tables** organized into the following domains:

| Domain | Table Count | Key Tables |
|--------|-------------|------------|
| Users & Authentication | 8 | `user`, `sms_code`, `user_address`, `user_card` |
| Products & Catalog | 22 | `product`, `category`, `category_brand`, `product_filter` |
| Orders & Payments | 10 | `order`, `order_product`, `transaction`, `promocode` |
| Shops & Sellers | 9 | `shop`, `shop_seller`, `stock`, `seller_application` |
| Delivery & Logistics | 7 | `delivery`, `logist`, `regions`, `cities` |
| E-Signature (Didox) | 4 | `didox_document`, `didox_document_invoice` |
| Content & CMS | 7 | `banner`, `slider`, `news`, `advantages` |
| Communication | 5 | `messages`, `notification`, `support_chat` |
| Shopping Cart | 3 | `user_cart`, `user_cart_filter`, `user_compare` |
| System | 8 | `settings`, `logs`, `migration`, `image` |

---

## 8. AI Usage in Development

The app platform development was assisted by **Claude Code** — an AI-powered development tool by Anthropic. Claude Code was used for:

- Code generation and implementation of complex business logic
- Integration with external services (BTS, Didox, Wallet)
- Database migration creation and schema design
- Code review and optimization suggestions
- Documentation generation
- Bug identification and resolution

All AI-generated code was reviewed and validated by human developers before deployment. The AI assistant enhanced development productivity while maintaining code quality and security standards.

---

## 9. Deployment Architecture

```
[Production Server]
    |
    +-- Apache Web Server (HTTPS)
    |       |
    |       +-- PHP 8.2 (Yii2 Application)
    |               |
    |               +-- MySQL 8.0 (app database)
    |
    +-- External Services
            |
            +-- BTS API (delivery.logistics.example.com)
            +-- Didox API (api.einvoice.example.com)
            +-- Wallet Service (Railway.app)
            +-- Warehouse API (warehouse.example.com)
            +-- Payme API (checkout.paycom.uz)
            +-- MyID API (identity.example.com)
            +-- Firebase (fcm.googleapis.com)
            +-- SMSC (smsc.ru)
```

---

*Document prepared for app software registration.*
*Date: February 2026*
