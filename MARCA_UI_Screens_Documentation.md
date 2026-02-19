# app -- System Screens, Forms & User Roles Documentation

> **Platform:** app E-Commerce
> **Framework:** Yii2 (PHP)
> **Generated:** 2026-02-10
> **Source:** Actual codebase analysis of controllers, views, and API endpoints

---

## Table of Contents

1. [System Overview](#1-system-overview)
2. [Role Definitions](#2-role-definitions)
3. [Admin Panel (role=1)](#3-admin-panel-role1)
4. [Moderator Panel (role=2)](#4-moderator-panel-role2)
5. [Customer / User Screens (role=3)](#5-customer--user-screens-role3)
6. [Shop Owner Panel (role=4)](#6-shop-owner-panel-role4)
7. [Logist Panel (role=5)](#7-logist-panel-role5)
8. [API Routes Reference](#8-api-routes-reference)
9. [Cross-Cutting Integrations](#9-cross-cutting-integrations)

---

## 1. System Overview

The app platform is a multi-role e-commerce system built on the Yii2 framework. It consists of six modules:

| Module | Path | Purpose |
|--------|------|---------|
| `admin` | `/modules/admin/` | Full administrative panel (role=1,2) |
| `shop` | `/modules/shop/` | Seller/Shop Owner dashboard (role=4) |
| `logist` | `/modules/logist/` | Logistics management panel (role=5) |
| `api` | `/modules/api/` | REST API for mobile/web clients (role=3, primarily) |
| `dashboard` | `/modules/dashboard/` | Alternative seller dashboard (API-based, no server-side views) |
| `billz` | `/modules/billz/` | Billz integration module |

**Key Integrations:**
- **BTS** -- Logistics/delivery service for tracking, shipment calculation, and status updates
- **DIDOX** -- Electronic document management system for invoices and contracts
- **E-IMZO** -- Digital signature system for document authentication
- **Payme** -- Payment processing gateway
- **Crypto Wallet** -- Ethereum/Sepolia blockchain wallet with AA (Account Abstraction) support
- **Sklad** -- Warehouse/inventory integration for product creation

**Multi-language Support:** Russian (RU), Uzbek (UZ), English (EN) across all content forms.

---

## 2. Role Definitions

Defined in `models/user/User.php`:

| Constant | Value | Role Name | Module Access |
|----------|-------|-----------|---------------|
| `ROLE_ADMIN` | 1 | Administrator | `/admin/*` -- Full access |
| `ROLE_MODERATOR` | 2 | Moderator | `/admin/*` -- Configurable per-URL access |
| `ROLE_USER` | 3 | Customer | `/api/*` -- Mobile/web API endpoints |
| `ROLE_SHOP` | 4 | Shop Owner | `/shop/*` -- Seller dashboard |
| `ROLE_LOGIST` | 5 | Logist | `/logist/*` -- Logistics panel |

---

## 3. Admin Panel (role=1)

**Module:** `modules/admin/`
**Access:** Full unrestricted access to all admin views
**Layout:** Server-side rendered with Yii2 ActiveForm, GridView, and modal dialogs

### 3.1 Dashboard

- **Screen Name:** Admin Dashboard
- **Purpose:** Overview of platform statistics
- **Route:** `/admin/default/dashboard`
- **Key Fields:** User count, Product count, Order count, Order statistics chart (week/month/half-year/year filter)
- **Actions:** View recent orders list, navigate to detail sections

---

### 3.2 Order Management

#### 3.2.1 Order List

- **Screen Name:** Order Index
- **Purpose:** List and filter all platform orders
- **Route:** `/admin/order/index`
- **Source:** `modules/admin/views/order/index.php`
- **Key Fields/Columns:**
  - ID
  - User (linked)
  - Price (formatted currency)
  - Amount (item count)
  - Status (dropdown filter: Pending=0, Accepted=1, Rejected=2, On Delivery=3, In Transit=4, Delivered=5, Return=10)
  - DIDOX Status (document count + status badges: success/warning/danger)
  - Date
- **Actions:**
  - View order detail
  - Delete single order
  - Bulk delete (checkbox selection)
  - Filter/search by all columns

#### 3.2.2 Order Detail View

- **Screen Name:** Order Detail
- **Purpose:** Complete order management with all integrations
- **Route:** `/admin/order/view?id={id}`
- **Source:** `modules/admin/views/order/view.php` (971 lines)
- **Sections:**

  **Header Section:**
  - Order ID, Date, Status badge, Logist status, Payment status
  - Action buttons: Accept (status=1), Reject (status=2), On Delivery (status=3), In Transit (status=4), Delivered (status=5), Return (status=10)

  **BTS Integration Section:**
  - BTS Order ID, BTS status badge
  - "Update BTS Status" button
  - Tracking History modal (table: date, status, location, details)

  **Receiver Information:**
  - Name, Lastname, Phone, Email
  - Delivery address with Yandex Maps embed (latitude/longitude)

  **Products List:**
  - Product image, name, quantity, unit price, total price
  - Applied filters per product
  - Product review (rating + text)
  - Price breakdown: Subtotal, Delivery cost, Discount, Total

  **DIDOX Documents Section:**
  - Invoice documents table (ID, number, date, status, buyer TIN, amount, actions)
  - Contract documents table (ID, number, date, status, buyer TIN, amount, actions)
  - Create Invoice button, Create Contract button (auto/manual)
  - DIDOX Logs section (collapsible, shows API call history)

- **Related API:** BTS status updates, DIDOX document creation

---

### 3.3 User Management

#### 3.3.1 User Detail View

- **Screen Name:** User Profile (Admin View)
- **Purpose:** Comprehensive user management with all related data
- **Route:** `/admin/user/view?id={id}`
- **Source:** `modules/admin/views/user/view.php` (957 lines)
- **Sections:**

  **Sidebar:**
  - AdminUserMenu widget (navigation links)

  **Main Info:**
  - Full name, Phone, Email, Birthday, Gender, Registration date
  - Avatar display

  **Tabbed Content:**

  | Tab | Fields/Columns | Actions |
  |-----|---------------|---------|
  | **Activity** | Recent orders (ID, date, amount, status), Recent transactions (ID, date, amount, type) | Quick links to order/transaction detail |
  | **Orders** | ID, Date, Amount, Items count, Status | View order detail |
  | **Transactions** | ID, Date, Amount, Type, Method, Status | View transaction detail |
  | **Addresses** | Address list, Last used address highlighted | View/manage addresses |
  | **Promocodes** | Code, Discount type (fixed/percent), Value, Min order amount, Usage limit, Expiry, Status | Create/edit/delete personal promocodes |
  | **Business** (yur type only) | INN, Account number, Bank name, MFO, OKED, OKOHX, Legal address | Edit business details |
  | **Security** | Crypto Wallet section, Account security, Payment cards | See below |

  **Security Tab -- Crypto Wallet Section:**
  - Wallet card (ETH balance, AA address, deployment status)
  - Assets table (Token symbol, Balance, Image for ETH/USDT/USDC/HUMO/app)
  - Recent blockchain transactions table
  - Actions: Deploy wallet, Mint tokens (admin form: amount, token), Pay (form: merchantId, amount, symbol)

  **Security Tab -- Account Info:**
  - User ID, Auth token, Last IP, Device ID, Role

  **Security Tab -- Payment Cards:**
  - Cards list (type, number, expiry, status)

---

### 3.4 Product Management

#### 3.4.1 Product Create/Edit

- **Screen Name:** Product Create / Edit
- **Purpose:** Full product lifecycle management with variants
- **Route:** `/admin/product/create` or `/admin/product/create?id={id}`
- **Source:** `modules/admin/views/product/create.php` (898 lines)
- **Form Sections:**

  **Main Information:**
  | Field | Type | Notes |
  |-------|------|-------|
  | Photo | File upload | Product main image |
  | Name (RU) | Text input | Tab: Russian |
  | Name (UZ) | Text input | Tab: Uzbek |
  | Name (EN) | Text input | Tab: English |
  | Shop | Dropdown | Select from active shops |
  | Brand | Dropdown | Select from active brands |
  | Warehouse (Stock) | Dropdown | Dynamic, filtered by selected shop |
  | Delivery Type | Dropdown | Select delivery method |

  **Pricing:**
  | Field | Type | Notes |
  |-------|------|-------|
  | Price | Number | Regular retail price |
  | Price Small (small wholesale) | Number | Small wholesale price |
  | Price Opt (wholesale) | Number | Bulk/wholesale price |
  | Min Order | Number | Minimum order quantity |
  | Qty Small Wholesale | Number | Threshold for small wholesale pricing |
  | Qty Big Wholesale | Number | Threshold for wholesale pricing |
  | Discount % | Number | Percentage discount |
  | Amount | Number | Stock quantity |

  **Additional:**
  | Field | Type | Notes |
  |-------|------|-------|
  | Tag | Dropdown | Product tag/collection |
  | SKU | Text input | Stock keeping unit |
  | Barcode | Text input | Product barcode |
  | Status | Dropdown | Active(1) / Blocked(2) |

  **Categories:**
  - Main category dropdown (parent_id=0)
  - Dynamic subcategory dropdowns (cascade loaded via AJAX)

  **IKPU (Product Classification):**
  - Toggle: Search mode / Custom mode
  - Search mode: Autocomplete field querying IKPU directory
  - Custom mode: 17-digit code input + name input

  **Filters:**
  - Dynamically loaded based on selected category
  - Three filter types: Input (text), Select (dropdown), Checkbox (multi-select)

  **Dimensions:**
  | Field | Type | Unit |
  |-------|------|------|
  | Weight | Number | grams |
  | Height | Number | cm |
  | Width | Number | cm |
  | Length | Number | cm |

  **Description:**
  | Field | Type | Notes |
  |-------|------|-------|
  | Description (RU) | CKEditor | Rich text, Russian |
  | Description (UZ) | CKEditor | Rich text, Uzbek |
  | Description (EN) | CKEditor | Rich text, English |

  **Characteristics:**
  - Dynamic key-value pair list (Add/Remove buttons)
  - Each row: Key (text) + Value (text)

  **Variants:**
  - Product type combinations container
  - Each variant: Color + Product Types + per-variant pricing overrides

  **Colors:**
  - Checkbox list of available colors

- **Actions:** Save, Save & Continue Editing, Cancel

---

### 3.5 Category Management

- **Screen Name:** Category Tree
- **Purpose:** Hierarchical category management with filters
- **Route:** `/admin/category/index`
- **Source:** `modules/admin/views/category/index.php` (434 lines)

- **Main View:**
  - Nestable drag-and-drop tree (jQuery Nestable plugin)
  - "Save Sort" button to persist tree order
  - Each node shows: Name, Actions (Add subcategory, View, Edit, Delete)

- **Category Form (Modal):**
  | Field | Type | Notes |
  |-------|------|-------|
  | Name (RU) | Text input | Russian name |
  | Name (UZ) | Text input | Uzbek name |
  | Name (EN) | Text input | English name |
  | Description (RU) | CKEditor | Rich text description, Russian |
  | Description (UZ) | CKEditor | Rich text description, Uzbek |
  | Description (EN) | CKEditor | Rich text description, English |
  | Popular | Checkbox | Mark as popular category |
  | Photo | File upload | Category image/icon |

- **Edit Modal Additional Features:**
  - Filter management section (add/edit/remove filters for the category)
  - Filter types: Input, Select, Checkbox
  - Photo management (view/delete existing)

- **View Modal:**
  - Displays name, description, icon in all languages

---

### 3.6 Shop Management

- **Screen Name:** Shop Detail View
- **Purpose:** View and manage shop/seller information
- **Route:** `/admin/shop/view?id={id}`
- **Source:** `modules/admin/views/shop/view.php`

- **Displayed Information:**
  | Section | Fields |
  |---------|--------|
  | Basic Info | ID, Status, Name, Contact person, Contact phone, Date |
  | Banner | Image display |
  | Description | RU / UZ / EN tabs |
  | Seller Info | Name, Phone, Email, Login, Token |
  | Seller Requisites | TIN, Checking account, Bank name, Legal address, OKED, OKOHX, MFO, Organization name |
  | Gallery | Photo gallery display |

- **Actions:** Edit, Block/Unblock, Delete, Remove location

---

### 3.7 Moderator Management

- **Screen Name:** Moderator List
- **Purpose:** Manage moderator accounts and their access permissions
- **Route:** `/admin/moderator/index`
- **Source:** `modules/admin/views/moderator/index.php`

- **GridView Columns:**
  - Photo (avatar)
  - ID
  - Name
  - Phone
  - Login
  - Date
  - Status

- **Actions:** Add moderator, Edit, Delete

---

### 3.8 Settings

#### 3.8.1 Call Center Settings

- **Screen Name:** Call Center Phone
- **Route:** `/admin/settings/call-center`
- **Source:** `modules/admin/views/settings/call_center.php`
- **Fields:** Phone number (text input)
- **Actions:** Save

#### 3.8.2 Logo Settings

- **Screen Name:** Logo Upload
- **Route:** `/admin/settings/logo`
- **Source:** `modules/admin/views/settings/logo.php`
- **Fields:** Logo image (file upload), Current logo preview
- **Actions:** Upload, Delete current logo

#### 3.8.3 DIDOX Settings

- **Screen Name:** DIDOX & E-IMZO Configuration
- **Route:** `/admin/settings/didox`
- **Source:** `modules/admin/views/settings/didox.php`
- **Tabs:**

  **Tab 1 -- General Settings:**
  | Section | Fields |
  |---------|--------|
  | Authentication | Status indicator, Sync button |
  | Seller Information | TIN, Name, Address, Account, MFO, VAT registration code |
  | System Credentials | Tax ID, Token, Last login date, Certificate info |

  **Tab 2 -- Automated Signing:**
  | Field | Type |
  |-------|------|
  | PFX Key | File upload |
  | PFX Password | Password input |
  | Signer Service URL | Text input |

---

### 3.9 Additional Admin Screens

The admin module includes ~35 view directories. Additional screens (based on directory structure):

| Screen Area | Route Prefix | Purpose |
|-------------|-------------|---------|
| Advantages | `/admin/advantage/` | Manage platform advantages/features |
| Banners | `/admin/banner/` | Banner/slider management |
| Brands | `/admin/brand/` | Brand CRUD |
| Chat | `/admin/chat/` | Customer chat management |
| Colors | `/admin/color/` | Color palette management |
| Delivery | `/admin/delivery/` | Delivery method configuration |
| DIDOX | `/admin/didox/` | DIDOX document management |
| Feedback | `/admin/feedback/` | Customer feedback/messages |
| Filters | `/admin/filter/` | Product filter management |
| IKPU | `/admin/ikpu/` | IKPU code directory |
| Logist | `/admin/logist/` | Logist company management |
| News | `/admin/news/` | News/blog management |
| Notifications | `/admin/notification/` | System notifications |
| Offices | `/admin/office/` | Pickup office management |
| Partners | `/admin/partner/` | Partner management |
| Product Requests | `/admin/product-request/` | Product request handling |
| Product Types | `/admin/product-type/` | Product type/variant types |
| Promocodes | `/admin/promocode/` | Promocode CRUD |
| Questions | `/admin/question/` | FAQ management |
| Reviews | `/admin/review/` | Product review moderation |
| Seller Applications | `/admin/seller-application/` | New seller onboarding |
| Shop Advertising | `/admin/shop-advertising/` | Shop advertising management |
| Shop Documents | `/admin/shop-document/` | Shop legal documents |
| Shop Oferta | `/admin/shop-oferta/` | Shop oferta/agreement management |
| Shop Support | `/admin/shop-support/` | Shop support tickets |
| Sliders | `/admin/slider/` | Homepage slider management |
| Stock/Warehouses | `/admin/stock/` | Warehouse management |
| Translations | `/admin/translate/` | Translation management |

---

## 4. Moderator Panel (role=2)

**Module:** Shares `modules/admin/` with configurable per-URL access
**Access Model:** `models/moderator/ModeratorAccess.php`

### 4.1 Access Control Mechanism

Moderators use the same admin panel interface but with URL-based access restrictions:

- Each moderator has entries in `ModeratorAccess` table linking `moderator_id` (from `ModeratorUrl`) to `user_id`
- `ModeratorUrl` defines available URL segments (e.g., `order`, `product`, `stock`, `review`, `analytics`, `shop`, `user`, `notification`, `feedback`, `news`, `shop-document`, `shop-support`, `logist`)
- On every controller `beforeAction()`, the system checks if the moderator's access list includes the required URL segment
- If access is denied, the moderator is either redirected to profile or receives HTTP 403

### 4.2 Configurable Access Areas

| URL Segment | Module Controller | Access Check |
|-------------|-------------------|-------------|
| `order` | Shop/OrderController, Logist/OrderController | `in_array('order', $accesses)` |
| `product` | Shop/ProductController | `in_array('product', $accesses)` |
| `stock` | Shop/StockController | `in_array('stock', $accesses)` |
| `review` | Shop/ReviewController | `in_array('review', $accesses)` |
| `analytics` | Shop/AnalyticsController | `in_array('analytics', $accesses)` |
| `shop` | Shop/ShopController, Logist/ShopController | `in_array('shop', $accesses)` |
| `user` | Logist/UserController | `in_array('user', $accesses)` |
| `notification` | Shop/NotificationController | `in_array('notification', $accesses)` |
| `feedback` | Shop/FeedbackController | `in_array('feedback', $accesses)` |
| `news` | Shop/NewsController | `in_array('news', $accesses)` |
| `shop-document` | Shop/ShopDocumentController | `in_array('shop-document', $accesses)` |
| `shop-support` | Shop/ShopSupportController | `in_array('shop-support', $accesses)` |
| `logist` | Logist/LogistController | `in_array('logist', $accesses)` |

### 4.3 Moderator Screens

When a moderator has access granted, they see the exact same screens as the admin for that section. The views are shared. The only difference is the access restriction layer.

**Moderator Management Screen** (admin only):
- **Route:** `/admin/moderator/index`
- **Columns:** Photo, ID, Name, Phone, Login, Date, Status
- **Actions:** Add/Edit/Delete moderators, Configure per-URL access

---

## 5. Customer / User Screens (role=3)

**Module:** `modules/api/` -- REST API endpoints
**Authentication:** HTTP Bearer Token (`HttpBearerAuth`)
**Response Format:** JSON via `ApiResponseTrait`
**Client:** Mobile application / Web frontend consuming these APIs

### 5.1 Authentication & Registration

#### 5.1.1 Send Phone (OTP Request)

- **Screen Name:** Phone Number Entry
- **Purpose:** Initiate authentication by sending SMS OTP
- **Endpoint:** `POST /api/user/send-phone`
- **Auth Required:** No
- **Input Fields:**
  | Field | Type | Required |
  |-------|------|----------|
  | phone | string | Yes |
- **Output:** Success message confirming SMS sent
- **Related Screen:** OTP Verification

#### 5.1.2 Send Code (OTP Verification)

- **Screen Name:** OTP Verification
- **Purpose:** Verify the SMS code sent to phone
- **Endpoint:** `POST /api/user/send-code`
- **Auth Required:** No
- **Input Fields:**
  | Field | Type | Required |
  |-------|------|----------|
  | phone | string | Yes |
  | code | string | Yes |
- **Output:** Verification result, proceed to sign-up or sign-in

#### 5.1.3 Sign Up

- **Screen Name:** Registration Form
- **Purpose:** Create new user account
- **Endpoint:** `POST /api/user/sign-up`
- **Auth Required:** No
- **Input Fields:**
  | Field | Type | Required |
  |-------|------|----------|
  | name | string | Yes |
  | lastname | string | No |
  | phone | string | Yes |
  | email | string | No |
  | password | string | Yes |
  | gender | string | No |
  | birthday | string | No |
  | type | string | No (fiz/yur) |
- **Output:** User object + auth token

#### 5.1.4 Sign In

- **Screen Name:** Login Form
- **Purpose:** Authenticate existing user
- **Endpoint:** `POST /api/user/sign-in`
- **Auth Required:** No
- **Input Fields:**
  | Field | Type | Required |
  |-------|------|----------|
  | phone | string | Yes |
  | password | string | Yes |
- **Output:** User object + auth token

#### 5.1.5 Logout

- **Screen Name:** N/A (action)
- **Purpose:** Invalidate auth token
- **Endpoint:** `POST /api/user/log-out`
- **Auth Required:** Yes
- **Output:** Success confirmation

---

### 5.2 User Profile

#### 5.2.1 View Profile

- **Screen Name:** Profile Screen
- **Purpose:** Display user information
- **Endpoint:** `GET /api/user/profile`
- **Auth Required:** Yes
- **Output Fields:**
  | Field | Description |
  |-------|-------------|
  | id | User ID |
  | name | First name |
  | lastname | Last name |
  | phone | Phone number |
  | email | Email address |
  | gender | Gender |
  | birthday | Date of birth |
  | type | Account type (fiz/yur) |
  | avatar | Profile photo URL |
  | wallet_address | Crypto wallet address |

#### 5.2.2 Update Profile

- **Screen Name:** Edit Profile Form
- **Purpose:** Update user information
- **Endpoint:** `PUT /api/user/update`
- **Auth Required:** Yes
- **Input Fields:**
  | Field | Type | Required |
  |-------|------|----------|
  | name | string | No |
  | lastname | string | No |
  | email | string | No |
  | gender | string | No |
  | birthday | string | No |
- **Output:** Updated user object

#### 5.2.3 Upload Photo

- **Screen Name:** Avatar Upload
- **Purpose:** Update profile photo
- **Endpoint:** `POST /api/user/upload-photo`
- **Auth Required:** Yes
- **Input Fields:**
  | Field | Type | Required |
  |-------|------|----------|
  | photo | file | Yes |
- **Output:** Updated user object with new avatar URL

#### 5.2.4 Change Password

- **Screen Name:** Change Password Form
- **Purpose:** Update account password
- **Endpoint:** `POST /api/user/change-password`
- **Auth Required:** Yes
- **Input Fields:**
  | Field | Type | Required |
  |-------|------|----------|
  | old_password | string | Yes |
  | new_password | string | Yes |
- **Output:** Success confirmation

#### 5.2.5 Change Phone

- **Screen Name:** Change Phone Number
- **Purpose:** Update phone number (requires OTP)
- **Endpoint:** `POST /api/user/change-phone`
- **Auth Required:** Yes
- **Input Fields:**
  | Field | Type | Required |
  |-------|------|----------|
  | phone | string | Yes |
- **Output:** OTP sent to new phone, requires confirmation via `accept-change-code`

---

### 5.3 Address Management

- **Screen Name:** Delivery Addresses
- **Purpose:** Manage saved delivery addresses
- **Endpoint:** `GET /api/user/index` (addresses in profile), `POST /api/user/address-remove`
- **Auth Required:** Yes
- **Address Fields:**
  | Field | Type |
  |-------|------|
  | address | string |
  | latitude | float |
  | longitude | float |
  | city | string |
  | region | string |
- **Actions:** Add address, Remove address (`POST /api/user/address-remove`)

---

### 5.4 Payment Cards

- **Screen Name:** My Cards
- **Purpose:** Manage payment cards
- **Auth Required:** Yes

| Endpoint | Method | Purpose | Input Fields |
|----------|--------|---------|-------------|
| `/api/user/cards` | GET | List all cards | -- |
| `/api/user/card-add` | POST | Add new card | card_number, expiry, card_type_id |
| `/api/user/card-detail` | GET | Card details | card_id |
| `/api/user/card-remove` | POST | Remove card | card_id |
| `/api/user/check-card` | POST | Verify card | card_number |

---

### 5.5 E-IMZO Digital Signature

- **Screen Name:** E-IMZO Authentication
- **Purpose:** Digital signature integration for legal entities
- **Auth Required:** Varies

| Endpoint | Method | Purpose | Input |
|----------|--------|---------|-------|
| `/api/user/eimzo-auth` | POST | Authenticate with E-IMZO | certificate data |
| `/api/user/eimzo-register` | POST | Register E-IMZO | certificate + user data |
| `/api/user/eimzo-login` | POST | Login with E-IMZO | certificate credentials |
| `/api/user/eimzo-profile` | GET | E-IMZO profile | -- |

---

### 5.6 Product Browsing

#### 5.6.1 Product List / Catalog

- **Screen Name:** Product Catalog
- **Purpose:** Browse and filter products
- **Endpoint:** `GET /api/product/index`
- **Auth Required:** No (optional for personalization)
- **Query Parameters:**
  | Parameter | Type | Description |
  |-----------|------|-------------|
  | page | int | Pagination page |
  | per-page | int | Items per page |
  | sort | string | Sorting: `new`, `recently`, `price_down`, `price_up`, `popular`, `rating` |
  | category_id | int | Filter by category |
  | tag_id | int | Filter by tag |
  | brand_id | int | Filter by brand |
  | shop_id | int | Filter by shop |
  | filter | object | Dynamic filters (AND/OR logic) |
  | price_min | float | Minimum price |
  | price_max | float | Maximum price |
  | color_id | int | Filter by single color |
  | color_ids | array | Filter by multiple colors |
- **Output per product:**
  | Field | Description |
  |-------|-------------|
  | id | Product ID |
  | name | Product name (localized) |
  | price | Retail price |
  | price_small | Small wholesale price |
  | price_opt | Wholesale price |
  | discount | Discount percentage |
  | image | Main product image URL |
  | rating | Average rating |
  | reviews_count | Number of reviews |
  | brand | Brand info |
  | shop | Shop info |
  | is_favorite | Whether user favorited (if authenticated) |

#### 5.6.2 Specialized Product Lists

| Endpoint | Purpose | Key Parameters |
|----------|---------|---------------|
| `GET /api/product/best-products` | Top-rated products | page, per-page |
| `GET /api/product/for-you` | Personalized recommendations | page, per-page |
| `GET /api/product/by-category` | Products by category | category_id |
| `GET /api/product/by-brand` | Products by brand | brand_id |
| `GET /api/product/by-shop` | Products by shop | shop_id |
| `GET /api/product/by-filter` | Products by dynamic filters | filter (object) |
| `GET /api/product/recently-viewed` | Recently viewed products | -- |
| `GET /api/product/related-products` | Related products | product_id |

#### 5.6.3 Product Search

- **Screen Name:** Search
- **Purpose:** Full-text product search with suggestions
- **Endpoints:**
  - `GET /api/product/search` -- Full search with parameters (query, filters)
  - `GET /api/product/search-suggestions` -- Typeahead suggestions (query)
  - `POST /api/product/by-photo` -- Image-based search using DifferenceHash algorithm

  **Photo Search Input:**
  | Field | Type | Required |
  |-------|------|----------|
  | photo | file | Yes |

#### 5.6.4 Product Detail

- **Screen Name:** Product Detail Page
- **Purpose:** Complete product information
- **Endpoint:** `GET /api/product/detail?id={id}`
- **Auth Required:** No (optional)
- **Output Fields:**
  | Field | Description |
  |-------|-------------|
  | id | Product ID |
  | name (RU/UZ/EN) | Localized names |
  | description (RU/UZ/EN) | Localized descriptions |
  | price, price_small, price_opt | Price tiers |
  | discount | Discount % |
  | images | Product gallery |
  | category | Category hierarchy |
  | brand | Brand info |
  | shop | Shop info |
  | filters | Applied product filters |
  | characteristics | Key-value properties |
  | colors | Available colors with images |
  | variants | Product type combinations |
  | dimensions | Weight, height, width, length |
  | rating | Average rating |
  | reviews | Recent reviews |
  | is_favorite | Favorite status |
  | is_compare | Compare status |
  | sku | SKU |
  | barcode | Barcode |

---

### 5.7 Favorites & Comparisons

| Endpoint | Method | Purpose | Input |
|----------|--------|---------|-------|
| `GET /api/product/favorites` | GET | List favorites | page, per-page |
| `POST /api/product/set-favorite` | POST | Toggle favorite | product_id |
| `GET /api/product/favorite-categories` | GET | Favorite category filter | -- |
| `GET /api/product/compares` | GET | List comparisons | page, per-page |
| `POST /api/product/set-compare` | POST | Toggle compare | product_id |
| `GET /api/product/compare-categories` | GET | Compare category filter | -- |

---

### 5.8 Product Reviews

- **Screen Name:** Write Review / View Reviews
- **Endpoints:**

| Endpoint | Method | Purpose | Input Fields |
|----------|--------|---------|-------------|
| `GET /api/product/reviews` | GET | List reviews for product | product_id, page |
| `POST /api/product/set-review` | POST | Submit review | product_id, order_product_id, rating (1-5), text, photos (files) |

---

### 5.9 Shopping Cart

#### 5.9.1 Cart View

- **Screen Name:** Shopping Cart
- **Purpose:** View cart items grouped by shop/stock
- **Endpoint:** `GET /api/cart/index`
- **Auth Required:** Yes
- **Output:** Cart items grouped by `token_key` (product variant), each with:
  | Field | Description |
  |-------|-------------|
  | id | Cart item ID |
  | product | Product details |
  | amount | Quantity |
  | unit_price | Calculated price (wholesale tiers applied) |
  | total_price | Amount x unit_price |
  | color | Selected color |
  | filters | Applied filters |
  | variant | Product type values |

#### 5.9.2 Cart Group View

- **Screen Name:** Cart (Grouped by Shop)
- **Endpoint:** `GET /api/cart/group`
- **Output:** Items grouped by shop with delivery cost per group

#### 5.9.3 Cart Actions

| Endpoint | Method | Purpose | Input Fields |
|----------|--------|---------|-------------|
| `POST /api/cart/add` | POST | Add to cart (single or batch) | product_id, amount, color_id, filter_ids |
| `POST /api/cart/minus` | POST | Decrease quantity | cart_id |
| `POST /api/cart/remove` | POST | Remove item | cart_id |
| `POST /api/cart/clear` | POST | Empty cart | -- |
| `POST /api/cart/calculate` | POST | Calculate totals | address (for delivery), promocode |

**Cart Calculate Output:**
  | Field | Description |
  |-------|-------------|
  | items | Cart items with prices |
  | subtotal | Items total |
  | delivery_cost | BTS-calculated delivery cost per stock group |
  | discount | Promocode discount amount |
  | total | Final amount |

---

### 5.10 Order Management (Customer)

#### 5.10.1 Order List

- **Screen Name:** My Orders
- **Purpose:** View order history
- **Endpoint:** `GET /api/order/index`
- **Auth Required:** Yes
- **Output per order:**
  | Field | Description |
  |-------|-------------|
  | id | Order ID |
  | date | Order date |
  | status | Order status (0-5, 10) |
  | price | Total price |
  | items_count | Number of items |
  | delivery | Delivery info |
  | payment | Payment info |

#### 5.10.2 Order Detail

- **Screen Name:** Order Detail
- **Endpoint:** `GET /api/order/detail?id={id}`
- **Auth Required:** Yes
- **Output:** Full order with products, delivery tracking, payment status

#### 5.10.3 Place Order

- **Screen Name:** Checkout
- **Purpose:** Create order from cart
- **Endpoint:** `POST /api/order/send`
- **Auth Required:** Yes
- **Input Fields:**
  | Field | Type | Required | Description |
  |-------|------|----------|-------------|
  | address | string | Yes | Delivery address |
  | latitude | float | Yes | Delivery latitude |
  | longitude | float | Yes | Delivery longitude |
  | name | string | Yes | Receiver name |
  | lastname | string | No | Receiver lastname |
  | phone | string | Yes | Receiver phone |
  | email | string | No | Receiver email |
  | payment_method | string | Yes | Payment method |
  | promocode | string | No | Promocode to apply |
  | comment | string | No | Order comment |
  | delivery_type | string | No | Delivery type |
  | region_id | int | No | BTS region ID |
  | city_id | int | No | BTS city ID |
- **Process:** Validates cart, applies promocode, calculates BTS delivery, creates DIDOX documents automatically, processes wallet payment if applicable
- **Output:** Created order object with all details

#### 5.10.4 Payment

| Endpoint | Method | Purpose | Input |
|----------|--------|---------|-------|
| `POST /api/order/pay-order` | POST | Initiate Payme payment | order_id |
| `POST /api/order/pay-order-code` | POST | Confirm payment with code | order_id, code |

#### 5.10.5 Refunds

| Endpoint | Method | Purpose | Input |
|----------|--------|---------|-------|
| `GET /api/order/refunds` | GET | List refund requests | -- |
| `POST /api/order/refund-send` | POST | Request refund | order_id, reason |

#### 5.10.6 BTS Delivery Tracking

| Endpoint | Method | Purpose | Input |
|----------|--------|---------|-------|
| `GET /api/order/get-order-tracking` | GET | Track delivery | order_id |
| `GET /api/order/get-order-status` | GET | Get current status | order_id |
| `POST /api/order/calculate-delivery` | POST | Calculate delivery cost | address, products |
| `GET /api/order/get-regions` | GET | List BTS regions | -- |
| `GET /api/order/get-cities` | GET | List cities by region | region_id |
| `GET /api/order/search-cities` | GET | Search cities | query |
| `GET /api/order/get-address-info` | GET | Get address details | address |
| `GET /api/order/get-package-types` | GET | List package types | -- |
| `GET /api/order/get-post-types` | GET | List post types | -- |
| `GET /api/order/get-order-statuses` | GET | List order statuses | -- |

#### 5.10.7 Payme Receipts

| Endpoint | Method | Purpose | Input |
|----------|--------|---------|-------|
| `POST /api/order/set-receipt` | POST | Create payment receipt | order_id, amount |
| `POST /api/order/pay-receipt` | POST | Pay via receipt | receipt_id |
| `POST /api/order/check-receipt` | POST | Check receipt status | receipt_id |
| `POST /api/order/cancel-receipt` | POST | Cancel receipt | receipt_id |

---

### 5.11 Promocodes

#### 5.11.1 My Promocodes

- **Screen Name:** Available Promocodes
- **Purpose:** View available promocodes (personal + universal)
- **Endpoint:** `GET /api/promocode/my`
- **Auth Required:** Yes
- **Validation Checks:** Active status, not expired, usage limit not reached, per-user limit, first-order requirement
- **Output per promocode:**
  | Field | Description |
  |-------|-------------|
  | id | Promocode ID |
  | title | Localized title |
  | description | Localized description |
  | code | Promocode string |
  | end_date | Expiry (formatted dd.MM.yyyy, HH:mm) |
  | min_order_amount | Minimum cart total |
  | value | Discount value |
  | type | `fixed` or `percent` |
  | is_personal | Whether it's user-specific |

#### 5.11.2 Apply Promocode

- **Screen Name:** Apply Promocode (at checkout)
- **Purpose:** Validate and preview discount
- **Endpoint:** `POST /api/promocode/apply`
- **Auth Required:** Yes
- **Input Fields:**
  | Field | Type | Required |
  |-------|------|----------|
  | promocode | string | Yes |
- **Validation:** Checks code existence, status, expiry, user eligibility, cart minimum, usage limits, first-order flag
- **Output:**
  | Field | Description |
  |-------|-------------|
  | promocode | Promocode details (id, code, title, description, type, value) |
  | calculation.original_total | Cart total before discount |
  | calculation.discount_amount | Discount amount |
  | calculation.final_total | Final total after discount |
  | valid | Boolean |
  | message | Success/error message |

---

### 5.12 Crypto Wallet

- **Screen Name:** Wallet
- **Purpose:** Blockchain wallet management
- **Auth Required:** Yes (all endpoints)

#### 5.12.1 Wallet Address

- **Endpoint:** `GET /api/wallet/address`
- **Output:**
  | Field | Description |
  |-------|-------------|
  | user_id | User ID |
  | eoa_address | Externally Owned Account address |
  | aa_address | Account Abstraction wallet address |

#### 5.12.2 Wallet Balance

- **Endpoint:** `GET /api/wallet/balance`
- **Cache:** 60 seconds
- **Output:**
  | Field | Description |
  |-------|-------------|
  | aaAddress | AA wallet address |
  | balance | ETH balance |
  | unit | Currency unit (ETH) |
  | image | Token logo URL |
  | isDeployed | Whether AA wallet is deployed |
  | tokens | Array of token balances (symbol, balance, image) |
  | recentTransactions | Recent blockchain transactions |

  **Supported Tokens:** ETH, USDT, USDC, HUMO, app

#### 5.12.3 Wallet Actions

| Endpoint | Method | Purpose | Input Fields |
|----------|--------|---------|-------------|
| `POST /api/wallet/deploy` | POST | Deploy AA wallet | -- |
| `POST /api/wallet/pay` | POST | Execute payment | merchantId, amount, symbol |
| `POST /api/wallet/approve-payment` | POST | Pre-approve payment | merchantId, amount, symbol |
| `POST /api/wallet/build-batch` | POST | Preview batch payment | merchantId, amount, symbol |
| `POST /api/wallet/mint` | POST | Mint tokens (test/admin) | to (address), amount, token (contract address) |
| `GET /api/wallet/supported-tokens` | GET | List supported tokens | -- |
| `GET /api/wallet/predict` | GET | Predict wallet addresses | -- |

  **Deprecated endpoints (HTTP 410):**
  - `POST /api/wallet/transfer`
  - `POST /api/wallet/transfer-by-name`

---

### 5.13 Product Requests

- **Screen Name:** Request a Product
- **Purpose:** Submit a request for a product not in catalog
- **Endpoint:** `POST /api/product/request`
- **Auth Required:** Yes
- **Input Fields:**
  | Field | Type | Required |
  |-------|------|----------|
  | name | string | Yes |
  | description | string | No |
  | photo | file | No |

---

### 5.14 Ratings

- **Screen Name:** Rate (generic)
- **Endpoint:** `POST /api/user/set-rate`
- **Auth Required:** Yes
- **Input Fields:**
  | Field | Type | Required |
  |-------|------|----------|
  | type | string | Yes |
  | object_id | int | Yes |
  | rating | int | Yes |

---

### 5.15 BTS Region/City Lookup

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `GET /api/user/bts-regions` | GET | List all BTS regions |
| `GET /api/user/bts-cities` | GET | List cities for a region |

---

## 6. Shop Owner Panel (role=4)

**Module:** `modules/shop/`
**Access:** Only users with `role = ROLE_SHOP (4)`. Redirects `ROLE_USER` to home.
**Layout:** Server-side rendered Yii2 views. Each controller scopes data to `shop_id` of the authenticated user.

### 6.1 Dashboard

- **Screen Name:** Shop Dashboard
- **Purpose:** Overview of shop performance metrics
- **Route:** `/shop/default/dashboard`
- **Source:** `modules/shop/controllers/DefaultController.php`
- **Key Fields:**
  | Metric | Description |
  |--------|-------------|
  | User Count | Total active customers |
  | Product Count | Shop's active products |
  | Order Count | Pending orders for this shop |
  | Order Statistics Chart | Date-grouped: order count + revenue |
  | Recent Orders | Last 10 pending orders (GridView) |
- **Time Filters:** Week, Month, Half-year, Year
- **Actions:** Navigate to order detail, view statistics

---

### 6.2 Profile Management

#### 6.2.1 View Profile

- **Screen Name:** Shop Owner Profile
- **Route:** `/shop/default/profile`
- **Fields:** Name, avatar, contact info
- **Actions:** Upload avatar, save profile info

#### 6.2.2 Update Profile

- **Screen Name:** Edit Profile
- **Route:** `/shop/default/update-profile`
- **Input Fields:** All user fields (name, phone, email, etc.), scenario: `UPDATE_ADMIN`
- **Actions:** Save changes

#### 6.2.3 Change Password

- **Screen Name:** Change Password
- **Route:** `/shop/default/change-password`
- **Input Fields:** Current password, New password
- **Actions:** Submit

---

### 6.3 Shop Information

- **Screen Name:** My Shop
- **Purpose:** View own shop details
- **Route:** `/shop/default/shop`
- **Fields:** Shop name, seller info, requisites, banner, description (RU/UZ/EN), gallery

---

### 6.4 Product Management (Shop)

#### 6.4.1 Product List

- **Screen Name:** My Products
- **Route:** `/shop/product/index`
- **Source:** `modules/shop/controllers/ProductController.php`
- **GridView Columns:** Product info (filtered to `shop_id`)
- **Filters:** Search, category filter, stock/warehouse filter
- **Actions:** View, Create, Edit, Lock/Unlock

#### 6.4.2 Product Create/Edit

- **Screen Name:** Add/Edit Product
- **Route:** `/shop/product/create` or `/shop/product/create?id={id}`
- **Form Fields:** Same comprehensive form as admin product create (see Section 3.4.1) but scoped to shop's own warehouses, brands, categories
- **Variant System:**
  - Explicit variant definition: Each variant has color_id, product types, per-variant pricing (price, price_small, price_opt, amount)
  - Fallback: Auto-generate from color x product type combinations
  - Shared `token_key` groups all variants of the same product
- **Actions:** Save, Cancel

#### 6.4.3 Product View

- **Screen Name:** Product Detail
- **Route:** `/shop/product/view?id={id}`
- **Fields:** All product details, delivery info, category, images, stock info, brand, tag, properties, colors with images, product type combinations, gallery
- **Actions:** Edit, Lock/Unlock, Delete (admin only)

---

### 6.5 Order Management (Shop)

#### 6.5.1 Order List

- **Screen Name:** Shop Orders
- **Purpose:** View orders containing this shop's products
- **Route:** `/shop/order/index`
- **Source:** `modules/shop/controllers/OrderController.php`
- **Data Scope:** Only orders containing `OrderProduct` records matching `shop_id`
- **GridView:** ID, User, Price, Amount, Status, Payment, Delivery, Date (sorted desc)
- **Actions:** View detail

#### 6.5.2 Order Detail

- **Screen Name:** Shop Order Detail
- **Route:** `/shop/order/view?id={id}`
- **Fields:**
  - Order info (ID, user, date, status)
  - Products list (scoped to shop's products only) with:
    - Product image, name, quantity, price
    - Applied filters
    - Product reviews (single from order user + all reviews)
    - Delivery info per product
- **Actions:**
  - Accept order (`status=1`)
  - Reject order (`status=2`)
  - Delete order

---

### 6.6 Stock/Warehouse Management

- **Screen Name:** Warehouses
- **Purpose:** Manage shop's warehouses
- **Route:** `/shop/stock/index`
- **Source:** `modules/shop/controllers/StockController.php`

| Screen | Route | Key Fields | Actions |
|--------|-------|-----------|---------|
| Warehouse List | `/shop/stock/index` | ID, Name, Products count, Image, Status | View, Create, Lock/Unlock |
| Warehouse Detail | `/shop/stock/view?id={id}` | All details + products list | Edit, Lock/Unlock, Delete |
| Warehouse Create/Edit | `/shop/stock/create` | Name, description, address, image | Save |

---

### 6.7 Analytics

- **Screen Name:** Sales Analytics
- **Purpose:** View order statistics and revenue data
- **Route:** `/shop/analytics/index`
- **Source:** `modules/shop/controllers/AnalyticsController.php`
- **Data:** Orders grouped by date with amount count and revenue
- **Time Filters:** Week, Month, Half-year, Year
- **Charts:** Date-based order volume and revenue

---

### 6.8 Reviews

- **Screen Name:** Product Reviews
- **Purpose:** View and moderate reviews for shop's products
- **Route:** `/shop/review/index`
- **Source:** `modules/shop/controllers/ReviewController.php`
- **GridView:** Review data with product and user info
- **Actions:** View review detail, Delete review

---

### 6.9 Shop Management (Multi-shop)

| Screen | Route | Purpose |
|--------|-------|---------|
| Shop List | `/shop/shop/index` | List all shops (for multi-shop owners) |
| Shop Detail | `/shop/shop/view?id={id}` | Shop details with seller info, gallery |
| Shop Create/Edit | `/shop/shop/create` | Shop CRUD form |
| Lock/Unlock | `/shop/shop/lock?id={id}` | Toggle shop status (disabled - has `die`) |

---

### 6.10 News Management

- **Screen Name:** Shop News
- **Route:** `/shop/news/index`
- **Source:** `modules/shop/controllers/NewsController.php`
- **Data Scope:** Filtered to `shop_id`

| Screen | Route | Fields | Actions |
|--------|-------|--------|---------|
| News List | `/shop/news/index` | GridView with image | View, Create |
| News Create/Edit | `/shop/news/create` | Title, content (CKEditor with image upload), image, status | Save, Lock/Unlock |
| News Detail | `/shop/news/view?id={id}` | Full article | Edit, Delete, Lock |

---

### 6.11 Feedback

- **Screen Name:** Customer Feedback
- **Route:** `/shop/feedback/index`
- **Source:** `modules/shop/controllers/FeedbackController.php`
- **Actions:** View (auto-marks as read), Delete

---

### 6.12 Shop Documents

- **Screen Name:** Legal Documents
- **Purpose:** Manage shop legal documents/offers
- **Route:** `/shop/shop-document/index`
- **Source:** `modules/shop/controllers/ShopDocumentController.php`

| Screen | Route | Fields | Actions |
|--------|-------|--------|---------|
| Document List | `/shop/shop-document/index` | GridView scoped to shop_id | View, Create |
| Document Create/Edit | `/shop/shop-document/create` | Title, file upload, status | Save |
| Document Detail | `/shop/shop-document/view?id={id}` | Document + file | Edit, Delete, Lock/Unlock |

---

### 6.13 Shop Support

- **Screen Name:** Support Tickets
- **Purpose:** Submit and track support requests
- **Route:** `/shop/shop-support/index`
- **Source:** `modules/shop/controllers/ShopSupportController.php`

| Screen | Route | Fields | Actions |
|--------|-------|--------|---------|
| Ticket List | `/shop/shop-support/index` | GridView scoped to shop_id | View, Create |
| Create Ticket | `/shop/shop-support/create` | Message/description | Submit |
| Ticket Detail | `/shop/shop-support/view?id={id}` | Full message | Delete |

---

### 6.14 Additional Shop Screens

The shop module includes these additional controllers with standard CRUD patterns:

| Controller | Route Prefix | Purpose |
|------------|-------------|---------|
| BrandController | `/shop/brand/` | Brand management |
| CategoryController | `/shop/category/` | Category browsing |
| DeliveryController | `/shop/delivery/` | Delivery configuration |
| FilterController | `/shop/filter/` | Product filter management |
| LogistController | `/shop/logist/` | Logist assignment/management |
| ModeratorController | `/shop/moderator/` | Shop moderator management |
| PaymentController | `/shop/payment/` | Payment processing (YooKassa integration) |
| QuestionController | `/shop/question/` | FAQ management |
| ShopAdvertisingController | `/shop/shop-advertising/` | Advertising management |
| ShopOfertaController | `/shop/shop-oferta/` | Public offer/agreement management |
| SliderController | `/shop/slider/` | Shop slider/carousel management |
| TranslateController | `/shop/translate/` | Translation management |
| UserController | `/shop/user/` | Customer management (scoped) |
| NotificationController | `/shop/notification/` | Notifications (currently disabled) |

---

### 6.15 Customer Management (from Shop)

- **Route:** `/shop/user/` (via `modules/logist/controllers/UserController.php` shared)
- **Screens:**

| Screen | Route | Purpose | Key Actions |
|--------|-------|---------|-------------|
| Customer List | `/shop/user/index` | List customers (role=USER) | View, Lock/Unlock |
| Customer Detail | `/shop/user/view?id={id}` | User profile + avatar upload | Upload photo, Edit |
| Customer Create/Edit | `/shop/user/create` | Create/edit customer account | Save |
| Customer Cards | `/shop/user/cards?id={id}` | View customer payment cards | View, Create, Lock, Delete |
| Customer Orders | `/shop/user/orders` | View all customer orders | Search, View |

---

## 7. Logist Panel (role=5)

**Module:** `modules/logist/`
**Access:** Only users with `role = ROLE_LOGIST (5)`. Redirects non-logists to home.
**Layout:** Server-side rendered Yii2 views.

### 7.1 Dashboard

- **Screen Name:** Logist Dashboard
- **Purpose:** Overview of delivery operations
- **Route:** `/logist/default/dashboard`
- **Source:** `modules/logist/controllers/DefaultController.php`
- **Key Fields:**
  | Metric | Description |
  |--------|-------------|
  | Order Count | Pending orders assigned to this logist |
  | Order Statistics Chart | Date-grouped: delivery count + revenue |
  | Recent Orders | Last 10 pending orders (GridView) |
- **Time Filters:** Week, Month, Half-year, Year

---

### 7.2 Profile Management

| Screen | Route | Purpose | Fields |
|--------|-------|---------|--------|
| View Profile | `/logist/default/profile` | Display logist profile | Name, avatar, contact info |
| Update Profile | `/logist/default/update-profile` | Edit profile fields | All user fields, scenario: UPDATE_ADMIN |
| Change Password | `/logist/default/change-password` | Update password | Current + new password |
| Logist Info | `/logist/default/logist` | View logist company info | Company details, image |

---

### 7.3 Order Management (Logist)

#### 7.3.1 Order List

- **Screen Name:** Delivery Orders
- **Purpose:** View orders assigned to this logist for delivery
- **Route:** `/logist/order/index`
- **Source:** `modules/logist/controllers/OrderController.php`
- **Data Scope:** Orders where `status=1` (Accepted), `status_delivery=1`, AND `logist_id` matches current logist
- **GridView:** ID, User, Price, Amount, Status, Payment, Delivery, Date (sorted desc)

#### 7.3.2 Order Detail

- **Screen Name:** Delivery Order Detail
- **Route:** `/logist/order/view?id={id}`
- **Data Scope:** Only orders assigned to this logist with `status=1, status_delivery=1`
- **Fields:**
  - Order info (ID, user, date, status, shop info)
  - Products list with images, filters
- **Actions:**
  - Set Logist Status:
    - Accept (`status_logist=1`)
    - Reject (`status_logist=2`)
    - On Delivery (`status_logist=3`)
    - In Transit (`status_logist=4`)
    - Delivered (`status_logist=5`)
  - Delete order

---

### 7.4 Logist Company Management

#### 7.4.1 Logist Profile/View

- **Screen Name:** My Logistics Company
- **Route:** `/logist/logist/view`
- **Source:** `modules/logist/controllers/LogistController.php`
- **Fields:** Company details, image, region assignments
- **Actions:** Edit company info

#### 7.4.2 Logist Edit

- **Screen Name:** Edit Logistics Company
- **Route:** `/logist/logist/create`
- **Fields:** Company name, description, image, contact info
- **Actions:** Save

---

### 7.5 Tariff Management

- **Screen Name:** Delivery Tariffs
- **Purpose:** Configure per-region delivery pricing
- **Route:** `/logist/logist/tariff`
- **Source:** `modules/logist/controllers/LogistController.php`

| Screen | Route | Purpose |
|--------|-------|---------|
| Tariff List | `/logist/logist/tariff` | GridView of region-based tariffs |
| Tariff Create/Edit | `/logist/logist/tariff-create` | Region selection (cascading), unit selection, price configuration |
| Tariff Detail | `/logist/logist/tariff-view?tariff_id={id}` | Region, prices per unit |
| Tariff Delete | `/logist/logist/tariff-remove?tariff_id={id}` | Remove tariff |

**Tariff Create/Edit Form Fields:**
| Field | Type | Description |
|-------|------|-------------|
| Region | Cascading dropdowns | Parent region -> child regions (tree) |
| Unit | Dropdown | Measurement unit for pricing |
| Prices | Dynamic list | Price tiers per unit (LogistRegionPrice) |

---

### 7.6 Shop Management (Logist View)

- **Screen Name:** Shops Directory
- **Purpose:** View and manage shops (read-only for logistics coordination)
- **Route:** `/logist/shop/index`
- **Source:** `modules/logist/controllers/ShopController.php`

| Screen | Route | Purpose | Actions |
|--------|-------|---------|---------|
| Shop List | `/logist/shop/index` | Browse all shops | View, Create, Lock/Unlock |
| Shop Detail | `/logist/shop/view?id={id}` | Shop details + seller + gallery | Edit |
| Shop Create/Edit | `/logist/shop/create` | Shop form | Save |

---

### 7.7 User Management (Logist View)

- **Screen Name:** Customers
- **Purpose:** View and manage customer accounts
- **Route:** `/logist/user/index`
- **Source:** `modules/logist/controllers/UserController.php`

| Screen | Route | Purpose | Actions |
|--------|-------|---------|---------|
| User List | `/logist/user/index` | List customers (role=USER) | View, Lock/Unlock |
| User Detail | `/logist/user/view?id={id}` | Customer profile + addresses | Upload photo |
| User Create/Edit | `/logist/user/create` | Create/edit customer | Save |
| User Cards | `/logist/user/cards?id={id}` | Customer payment cards | CRUD |
| User Orders | `/logist/user/orders` | Customer order history | Search, View |

---

### 7.8 Additional Logist Screens

| Controller | Route Prefix | Purpose |
|------------|-------------|---------|
| CategoryController | `/logist/category/` | Category browsing |
| NotificationController | `/logist/notification/` | System notifications |

---

## 8. API Routes Reference

### 8.1 Explicit URL Rules (from `config/web.php`)

```
GET  api/product-attribute/category      -> api/product-attribute/category-list
GET  api/product-attribute/tag           -> api/product-attribute/tag-list
GET  api/product-attribute/brand         -> api/product-attribute/brand-list
GET  api/product-attribute/color         -> api/product-attribute/color-list
GET  api/product-attribute/product-type  -> api/product-attribute/product-type-list
GET  api/product-attribute/filter        -> api/product-attribute/filter-list
GET  api/product-attribute/ikpu          -> api/product-attribute/ikpu-list
GET  api/product-attribute/user          -> api/product-attribute/user-list
GET  api/product-attribute/shop          -> api/product-attribute/shop-list
GET  api/product-attribute/stock         -> api/product-attribute/stock-list
GET  api/product-attribute/region        -> api/product-attribute/region-list
GET  api/product-attribute/delivery      -> api/product-attribute/delivery-list
GET  api/product-attribute/office        -> api/product-attribute/office-list
POST api/product/create                  -> api/product/create
GET  api/sync/products/pending           -> api/sync/pending
POST api/sync/products/confirm           -> api/sync/confirm
GET  api/wallet/balance                  -> api/wallet/balance
POST api/wallet/deploy                   -> api/wallet/deploy
```

### 8.2 Convention-Based API Routes

All other API routes follow Yii2's default convention: `/api/{controller}/{action}`

**User Controller:** `/api/user/{send-phone|send-code|sign-up|sign-in|log-out|profile|update|change-password|change-phone|accept-change-code|address-remove|remove-photo|remove-account|upload-photo|cards|card-add|card-detail|card-remove|eimzo-auth|eimzo-register|eimzo-login|eimzo-profile|bts-regions|bts-cities|check-card|set-rate}`

**Product Controller:** `/api/product/{index|best-products|for-you|by-category|by-brand|by-shop|by-filter|search|search-suggestions|by-photo|detail|favorites|set-favorite|favorite-categories|compares|set-compare|compare-categories|set-review|reviews|recently-viewed|related-products|request|create}`

**Cart Controller:** `/api/cart/{index|group|add|minus|remove|clear|calculate}`

**Order Controller:** `/api/order/{index|detail|send|pay-order|pay-order-code|refunds|refund-send|set-receipt|pay-receipt|check-receipt|cancel-receipt|get-order|get-order-tracking|get-order-status|calculate-delivery|calculate|get-regions|get-cities|search-cities|get-address-info|get-package-types|get-post-types|get-order-statuses|clear}`

**Wallet Controller:** `/api/wallet/{address|balance|deploy|mint|pay|transfer|transfer-by-name|approve-payment|build-batch|supported-tokens|predict}`

**Promocode Controller:** `/api/promocode/{my|apply}`

---

## 9. Cross-Cutting Integrations

### 9.1 BTS (Logistics Service)

- **Purpose:** Delivery calculation, shipment tracking, status updates
- **Used In:** Order creation (API), Admin order view, Logist order management
- **Key Operations:**
  - Calculate delivery cost per stock group
  - Create BTS shipment orders
  - Track shipment status with history
  - Get regions, cities, package types, post types
- **Fallback:** Mock data when BTS service is unavailable

### 9.2 DIDOX (Electronic Document Management)

- **Purpose:** Auto-generation of invoices and contracts for orders
- **Used In:** Admin order detail, Order creation (API), Admin settings
- **Key Operations:**
  - Create invoice documents
  - Create contract documents
  - E-IMZO digital signing (automated via PFX key or manual)
  - Document status tracking
  - API call logging

### 9.3 Payme (Payment Gateway)

- **Purpose:** Online payment processing
- **Used In:** Order payment (API), Shop payment controller
- **Key Operations:**
  - Create payment receipts
  - Process payments
  - Check payment status
  - Cancel receipts

### 9.4 Crypto Wallet (Ethereum/Sepolia)

- **Purpose:** Blockchain-based payments and wallet management
- **Used In:** User wallet (API), Admin user security tab
- **Architecture:** Account Abstraction (AA) wallets with EOA signers
- **Supported Tokens:** ETH, USDT, USDC, HUMO, app
- **Key Operations:**
  - Wallet creation and deployment
  - Balance checking with caching (60s)
  - Token minting (test/admin)
  - Payment execution with approval flow
  - Batch transaction building

### 9.5 Sklad (Warehouse Integration)

- **Purpose:** External warehouse/inventory system integration for product management
- **Used In:** Product creation (API), Sync controller
- **Key Operations:**
  - Submit products from external warehouse system
  - Read-only attribute endpoints (categories, tags, brands, colors, product types, filters, IKPU, users, shops, stocks, regions, deliveries, offices)
  - Sync pending products and confirm sync
  - Product creation via API with callback

### 9.6 Telegram Notifications

- **Purpose:** System alerts and notifications via Telegram bot
- **Configuration:** Bot token and chat ID configured in `config/web.php`
- **Used For:** Order notifications, system alerts

---

*End of Document*
