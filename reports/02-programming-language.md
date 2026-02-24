# app — Programming Languages & Technology Stack

## 1. Primary Programming Language

**PHP 8.2** is the primary programming language used for the development of the app e-commerce platform.

## 2. Framework

**Yii2 Framework (version 2.x)** — a high-performance PHP framework for developing web applications using the MVC (Model-View-Controller) architectural pattern. Yii2 provides built-in support for:
- RESTful API development
- ActiveRecord ORM for database interaction
- Role-based authentication and authorization
- Input validation and data sanitization
- URL routing and pretty URLs

## 3. Database

**MySQL 8.0** — relational database management system used for persistent data storage. The database `app` contains 83 tables managing users, products, orders, transactions, shops, delivery logistics, and more. Character set: `utf8mb4` for full Unicode support.

## 4. API Architecture

The platform follows a **REST API-first architecture**, delivering all data as JSON responses. The API is consumed by:
- Mobile applications (iOS/Android)
- Web frontend clients
- Third-party integrations

API authentication uses **Bearer Token** (`Authorization: Bearer {token}`) for stateless request handling.

## 5. External Service — Blockchain Wallet

**Node.js** runtime powers the external Wallet Service — an **Account Abstraction (AA)** backend built on **Ethereum blockchain** technology. This service handles:
- Crypto wallet creation (EOA + Smart Contract wallets)
- Token payments (USDT, USDC — ERC-20 tokens)
- Batch payment execution

Service URL: Hosted externally as a standalone microservice.

## 6. Key Libraries & Dependencies

| Library | Purpose |
|---------|---------|
| **Guzzle HTTP** | HTTP client for external API calls (BTS, Didox, Warehouse, Wallet) |
| **Intervention Image** | Image processing and manipulation (product photos, thumbnails) |
| **Yii2 REST Extensions** | RESTful API controllers, serializers, authentication filters |
| **yii2-httpclient** | HTTP client integration within Yii2 framework |

## 7. External Services & Protocols

| Protocol | Used For |
|----------|----------|
| **REST API (JSON)** | BTS delivery, Wallet service, Warehouse sync, Click, Octo, YooKassa |
| **JSON-RPC 2.0** | Payme payment gateway (webhook handler) |
| **OAuth 2.0** | BTS authentication, MyID national eID |
| **HTTPS + PFX Certificates** | Didox e-signature service (digital document signing) |
| **Firebase Cloud Messaging** | Push notifications to mobile devices |
| **Telegram Bot API** | Administrative notifications |
| **SMSC REST API** | SMS delivery for verification codes |

## 8. Server Environment

| Component | Version/Details |
|-----------|----------------|
| **PHP** | 8.2.25 |
| **MySQL** | 8.0.30 |
| **Web Server** | Apache (via Laragon local development stack) |
| **phpMyAdmin** | 5.2.0 (database administration) |
| **Operating System** | Windows (development), Linux (production) |

## 9. Development Tools

- **Laragon** — Local development environment (Apache + MySQL + PHP)
- **Git** — Version control system
- **Claude Code** — AI-assisted development tool
- **phpMyAdmin** — Database management interface
