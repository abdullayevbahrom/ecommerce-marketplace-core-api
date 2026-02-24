# app -- System Screens, Forms & User Roles Documentation

> **Platform:** app E-Commerce
> **Framework:** Yii2 (PHP)
> **Architecture:** Server-rendered Admin Panels + RESTful JSON API for Mobile/SPA Clients
> **Generated:** 2026-02-10

---

## Table of Contents

1. [User Roles Overview](#1-user-roles-overview)
2. [Admin Panel (Role: Admin)](#2-admin-panel-role-admin)
3. [Moderator Panel (Role: Moderator)](#3-moderator-panel-role-moderator)
4. [Customer Application (Role: User)](#4-customer-application-role-user)
5. [Seller Dashboard (Role: Shop Owner)](#5-seller-dashboard-role-shop-owner)
6. [Logistics Panel (Role: Logist)](#6-logistics-panel-role-logist)

---

## 1. User Roles Overview

The app platform defines five distinct user roles, each with its own interface, permissions, and access scope. The roles are defined as constants in the `User` model (`app\models\user\User`):

| Role ID | Constant           | Name          | Interface Type            | Primary Purpose                              |
|---------|-------------------|---------------|---------------------------|----------------------------------------------|
| 1       | `ROLE_ADMIN`      | Admin         | Web Panel (`/admin/`)     | Full platform management                     |
| 2       | `ROLE_MODERATOR`  | Moderator     | Web Panel (`/admin/`)     | Subset of admin, controlled via access table  |
| 3       | `ROLE_USER`       | User/Customer | Mobile App / API (`/api/`)| Shopping, ordering, profile management        |
| 4       | `ROLE_SHOP`       | Shop Owner    | Web Panel (`/shop/`) + Dashboard API (`/dashboard/`) | Manage own shop, products, orders |
| 5       | `ROLE_LOGIST`     | Logist        | Web Panel (`/logist/`)    | Delivery management and order fulfillment     |

### Authentication Methods

- **Admin / Moderator / Shop Owner / Logist:** Session-based login via web forms (login/password)
- **Customer (fiz -- Individual):** Phone + SMS OTP verification via API
- **Customer (yur -- Legal Entity):** E-IMZO digital signature authentication via Didox integration

### User Types (Sub-classification for Role 3)

| Type  | Description                | Additional Fields                                                   |
|-------|---------------------------|---------------------------------------------------------------------|
| `fiz` | Physical person (individual) | name, lastname, middlename, phone, email, birthday, gender        |
| `yur` | Juridical person (company)   | + inn, account, bank, mfo, oked, okohx, address_legal, organization_name |

---

## 2. Admin Panel (Role: Admin)

**Base URL:** `/admin/`
**Access:** Full unrestricted access to all admin panel sections
**View Template Engine:** Yii2 PHP views with AdminLTE (Bootstrap 3) layout
**Path:** `modules/admin/views/`

### 2.1 Admin View Directories (Complete Inventory)

The admin panel contains **36 management sections**, each with its own view directory:

| Directory             | Section               | Description                                      |
|-----------------------|-----------------------|--------------------------------------------------|
| `advantage/`          | Advantages            | Platform advantage/feature management             |
| `banner/`             | Banners               | Promotional banner management                     |
| `brand/`              | Brands                | Brand catalog management                          |
| `category/`           | Categories            | Product category tree management                  |
| `chat/`               | Chat                  | Support chat management                           |
| `color/`              | Colors                | Color catalog for products                        |
| `default/`            | Dashboard             | Admin home / dashboard                            |
| `delivery/`           | Delivery Methods      | Delivery type configuration                       |
| `didox/`              | Didox Documents       | E-invoicing and digital document management       |
| `feedback/`           | Feedback              | Customer feedback management                      |
| `filter/`             | Filters               | Product filter/attribute management               |
| `ikpu/`               | IKPU                  | Product classification codes (Uzbekistan)         |
| `logist/`             | Logist Management     | Logistics user management within admin            |
| `moderator/`          | Moderator Management  | Moderator user and access management              |
| `news/`               | News                  | News/article management                           |
| `notification/`       | Notifications         | Push notification management                      |
| `office/`             | Offices               | Physical office/pickup point management           |
| `order/`              | Orders                | Order management and processing                   |
| `partner/`            | Partners              | Partner/affiliate management                      |
| `product/`            | Products              | Product catalog management                        |
| `product-request/`    | Product Requests      | Customer product request management               |
| `product-type/`       | Product Types         | Product type/variant configuration                |
| `promocode/`          | Promocodes            | Promotional code management                       |
| `question/`           | Questions             | FAQ / Q&A management                              |
| `review/`             | Reviews               | Product review moderation                         |
| `seller-application/` | Seller Applications   | New seller registration request handling          |
| `settings/`           | Settings              | Platform settings configuration                   |
| `shop/`               | Shops                 | Shop/seller management                            |
| `shop-advertising/`   | Shop Advertising      | Shop advertising/promotion management             |
| `shop-document/`      | Shop Documents        | Seller document management                        |
| `shop-oferta/`        | Shop Oferta           | Shop offer/contract management                    |
| `shop-support/`       | Shop Support          | Seller support ticket management                  |
| `slider/`             | Sliders               | Homepage slider management                        |
| `stock/`              | Warehouses (Stocks)   | Warehouse/stock location management               |
| `translate/`          | Translations          | Multilingual content translation management       |
| `user/`               | Users                 | Customer/user management                          |

### 2.2 Order View Screen (`order/view.php`)

**Purpose:** Detailed view of a single customer order with full management capabilities.

**Screen Layout:**

1. **Breadcrumb Navigation:** Home > Order #{id}
2. **Status Action Buttons (top toolbar):**
   - "On Delivery / Awaiting Payment" (status=3)
   - "In Transit / Shipped" (status=4)
   - "Delivered" (status=5)
   - "Return" (status=10)
   - "Accept" (status=1, shown only for pending orders)
   - "Reject" (status=2, shown only for pending orders)
3. **Order Information Table:**

| Field                 | Description                                    |
|-----------------------|------------------------------------------------|
| Order ID              | Unique order identifier                        |
| Status                | Pending / Accepted / Rejected / On Delivery / In Transit / Delivered / Return |
| Logist Status         | Pending / Accepted / Rejected / Sent to Delivery / In Transit / Delivered |
| Payment Status        | Not Paid / Paid                                |
| BTS Information       | BTS delivery tracking with status labels, refresh and history buttons |
| Order Creator         | Link to user profile                           |
| Address               | Delivery address text                          |
| Payment Type          | Related payment method name                    |
| Delivery Type         | Related delivery method name                   |
| Product Count         | Total number of products                       |
| Total Price           | Order amount with delivery (UZS)               |
| Receiver              | Whether the customer will receive (Yes/No)     |
| Comment               | Customer comment                               |
| Date                  | Order creation date                            |

4. **Alternate Receiver Block** (shown when receiver != customer):
   - First Name, Last Name, Phone, Email

5. **Map Location Block:** Yandex Maps embed showing delivery coordinates

6. **Products Block:** For each order product:
   - Product image with link to product view
   - Product name, Status (Active / Return)
   - Delivery method, Unit price, Quantity, Delivery cost
   - Calculated total (unit_price x quantity + delivery_cost)
   - Customer reviews section with ratings, review text, moderator comments
   - Product filter attributes

7. **DIDOX Documents Section:**
   - Table of linked Didox documents (ID, Name, Document Type, Didox Status, Didox ID, Created Date, Actions)
   - Auto-creation buttons: Auto Invoice, Auto Contract, Create Both Documents
   - Manual creation: Invoice form, Arbitrary Contract form
   - Document actions: View, Edit, Sign

8. **DIDOX Logs Section** (collapsible):
   - Date, Level (info/warning/error), Message with data

### 2.3 User View Screen (`user/view.php`)

**Purpose:** Comprehensive user profile management with tabbed interface.

**Screen Layout:**

1. **Header:** User profile breadcrumb and flash messages
2. **Sidebar:** Admin user menu widget (`AdminUserMenu`)
3. **Main Content Area:**

**Basic Information Card:**
- Full Name (lastname + name + middlename)
- Phone (with call button)
- Email (with mailto button)
- Date of Birth
- Gender (Male / Female)
- Registration Date

**Tabbed Content:**

| Tab             | Content                                                                 |
|-----------------|-------------------------------------------------------------------------|
| Activity        | Recent orders (last 5) + Recent transactions (last 5) as card lists    |
| Orders          | Table: ID, Date, Amount, Products Count, Status with link to all orders|
| Transactions    | Table: ID, Date, Amount, Type, Method, Status                          |
| Addresses       | Cards with delivery addresses + last used address                      |
| Promocodes      | Table: Code, Discount (fixed/percent), Min Order, Usage Limit, Expiry, Status; Create new button |
| Business (yur only) | INN, Account Number, Bank, MFO, OKED, OKOHX, Legal Address        |
| Security        | Crypto Wallet section + Account Security + Payment Cards               |

**Crypto Wallet Section (within Security tab):**
- Wallet card visualization (ETH balance, AA address, deployment status)
- Deploy Smart Wallet button (if undeployed)
- Mint Token modal form (To Address, Token selection, Amount)
- Pay modal form (Payer info, Merchant User ID, Token, Amount)
- Assets table: ETH + USDT + USDC + HUMO + app token balances
- Recent Transactions table: Type (IN/OUT), Asset, Amount, Time, Tx Hash
- EOA Address and Network info

**Account Security Sub-section:**
- User ID, Token (truncated), IP Address, Device ID, Role display

**Payment Cards Sub-section:**
- Count of linked cards with management link

### 2.4 Product Create/Edit Screen (`product/create.php`)

**Purpose:** Multi-section product creation and editing form with multilingual support.

**Form Sections:**

**Section 1: Basic Product Information** (box-primary)
- Product Photo upload (JPEG, PNG, GIF, SVG, WebP; recommended 500x500px)
- Product Name (tabbed: RU / UZ / EN)
- Shop selection (dropdown)
- Brand selection (dropdown with Select2)
- Warehouse/Stock selection (dynamic based on selected shop)
- Delivery Type selection (dropdown)

**Section 2: Pricing Information** (box-success)

| Field                   | Type      | Description                        |
|-------------------------|-----------|-------------------------------------|
| Price                   | number    | Main retail price                   |
| Small Wholesale Price   | number    | Price for small wholesale quantity  |
| Wholesale Price         | number    | Bulk wholesale price                |
| Min Order               | number    | Minimum order quantity              |
| Qty Small Wholesale     | number    | Threshold for small wholesale pricing |
| Qty Big Wholesale       | number    | Threshold for big wholesale pricing |
| Discount %              | number    | Discount percentage (0-100)         |
| Amount                  | number    | Stock quantity                      |

**Section 3: Additional Information** (box-info)
- Tag selection (dropdown)
- SKU (text input)
- Barcode (text input)
- Status (Active / Blocked)

**Section 4: Categories** (box-warning)
- Main Category (dropdown with hierarchical categories)
- Dynamic subcategory dropdowns (loaded based on parent selection)

**Section 5: IKPU Classification Code** (box-default)
- Two modes: "Search from reference" (autocomplete search) or "Enter custom code"
- IKPU Code (17-digit code)
- IKPU Name

**Section 6: Product Filters** (box-success, dynamic)
- Rendered based on category selection
- Supports: text input, dropdown select, checkbox group filter types

**Section 7: Dimensions** (box-default)
- Weight (grams), Height (cm), Width (cm), Length (cm)

**Section 8: Product Description** (box-primary)
- Rich text editor (CKEditor) with tabs: RU / UZ / EN

**Section 9: Product Characteristics** (box-info)
- Dynamic key-value pairs: Characteristic Name + Characteristic Value
- Add/Remove characteristic buttons

**Section 10: Product Variants** (box-primary)
- Dynamic variants container (loaded based on product type/category)

**Section 11: Product Colors** (box-warning)
- Checkbox grid of available colors

### 2.5 Settings Screens

#### 2.5.1 Call Center Settings (`settings/call_center.php`)
**Purpose:** Configure the platform call center phone number.
- **Fields:** Phone number (text input)
- **Action:** Save

#### 2.5.2 Logo Upload (`settings/logo.php`)
**Purpose:** Upload/manage the platform logo image.
- **Fields:** Image file upload (multiple files)
- **Display:** Current logo preview with delete option
- **Action:** Save

#### 2.5.3 Didox & E-IMZO Settings (`settings/didox.php`)
**Purpose:** Configure Didox e-invoicing and E-IMZO digital signature credentials.

**Tab 1: General Settings**

| Column Left (Seller Information)   | Column Right (System Credentials)   |
|-------------------------------------|--------------------------------------|
| Seller TIN (INN)                   | System Tax ID (INN)                  |
| Seller Name (Organization)          | System Token                         |
| Address                             | Last Updated / Login (read-only)     |
| Account Number                      | Certificate Info JSON (read-only)    |
| Bank MFO                            |                                      |
| VAT Reg Code                        |                                      |

- Authentication status indicator (Authenticated / Not Authenticated)
- "Sync Session to Settings" button

**Tab 2: Automated Signing**
- Current PFX Key Path (read-only)
- Upload New PFX Key file
- PFX Key Password
- Signer Service URL

### 2.6 Other Admin Screens (Summary)

| Screen                    | Views                  | Key Operations                                    |
|---------------------------|------------------------|---------------------------------------------------|
| Product List              | `product/index.php`    | Grid with search, filter, pagination              |
| Product View              | `product/view.php`     | Detailed product display with gallery              |
| Product Edit              | `product/update.php`   | Same form as create, pre-populated                |
| Category Management       | `category/`            | Tree-based CRUD for categories                    |
| Brand Management          | `brand/`               | Brand CRUD with logo upload                       |
| Banner Management         | `banner/`              | Banner image + link CRUD                          |
| Slider Management         | `slider/`              | Homepage slider CRUD                              |
| News Management           | `news/`                | Article CRUD with rich editor                     |
| Review Moderation         | `review/`              | Review list with accept/reject/process actions    |
| Feedback Management       | `feedback/`            | Customer feedback list with status management     |
| Promocode Management      | `promocode/`           | Promo code CRUD with type/value/limits/expiry     |
| Notification Management   | `notification/`        | Push notification sending interface               |
| Color Management          | `color/`               | Color catalog CRUD (name + hex code)              |
| Filter Management         | `filter/`              | Filter/attribute definition CRUD                  |
| Delivery Methods          | `delivery/`            | Delivery type configuration CRUD                  |
| Didox Documents           | `didox/`               | Invoice/contract creation and management          |
| Shop Management           | `shop/`                | Shop CRUD with owner assignment                   |
| Stock/Warehouse           | `stock/`               | Warehouse location management with BTS city IDs   |
| Office Management         | `office/`              | Pickup point CRUD                                 |
| Seller Applications       | `seller-application/`  | Review and approve new seller registrations        |
| Shop Documents            | `shop-document/`       | Seller legal document management                  |
| Shop Oferta               | `shop-oferta/`         | Shop offer/contract templates                     |
| Shop Support              | `shop-support/`        | Seller support ticket management                  |
| Shop Advertising          | `shop-advertising/`    | Shop promotion and advertising management         |
| Product Types             | `product-type/`        | Size/type variant definition                      |
| Product Requests          | `product-request/`     | Customer product search requests                  |
| Translation Management    | `translate/`           | Multilingual string management                    |
| IKPU Management           | `ikpu/`                | Product classification code management            |
| Q&A Management            | `question/`            | FAQ management                                    |
| Partner Management        | `partner/`             | Affiliate/partner management                      |
| Advantage Management      | `advantage/`           | Platform feature/advantage management             |

---

## 3. Moderator Panel (Role: Moderator)

**Base URL:** `/admin/` (same as admin, with restricted access)
**Access Control:** Configurable per-moderator via `moderator_access` table

### 3.1 Access Control Architecture

Moderators use the same admin panel as administrators but with restricted access. The system uses a URL-based access control mechanism:

- **`ModeratorUrl`** model: Defines all available admin panel URLs/sections
- **`ModeratorAccess`** model: Maps which URLs each moderator can access
- Access is configured per-moderator via the Moderator Management screen

### 3.2 Moderator Management Screens (Admin-Side)

#### Moderator List (`moderator/index.php`)
**Purpose:** List all moderator accounts with search and status management.
- Grid view with moderator data
- Lock/Unlock actions per moderator

#### Moderator Create/Edit (`moderator/create.php`)
**Purpose:** Create or edit a moderator account and configure access permissions.

**Form Fields:**
- Photo upload
- Name (required)
- Phone
- Login (required)
- Password (required for new moderators)

**Access Configuration Section:**
- Checkbox list of available admin panel sections organized by type:
  - **Pages** (single): Individual page access (e.g., Orders, Products, Users)
  - **Directories** (category): Category-grouped access sections
- Each section's access can be toggled independently per moderator

#### Moderator View (`moderator/view.php`)
**Purpose:** View moderator profile details and current access permissions.
- Profile info: ID, Status (Active/Blocked), Name, Phone, Login, Registration Date
- Access list: All currently granted admin panel sections
- Sidebar menu widget for navigation

### 3.3 Available Admin Sections for Moderator Access

Based on the `ModeratorUrl` entries, moderators can be granted access to any combination of these admin sections:

- Orders management
- Products management
- Users management
- Categories management
- Brands management
- Reviews moderation
- Feedback management
- News management
- Notifications
- Promocodes
- Shops management
- Delivery settings
- Warehouses/Stock
- Offices
- Seller applications
- And other sections as defined in the `moderator_url` table

---

## 4. Customer Application (Role: User)

**Base URL:** `/api/`
**Interface Type:** RESTful JSON API consumed by mobile application or SPA
**Authentication:** Bearer Token (HTTP Authorization header)

### 4.1 Authentication & Registration Screens

#### 4.1.1 Phone Login -- Send Phone (`POST /api/user/send-phone`)

**Purpose:** Initiate phone-based authentication. Sends SMS verification code.

| Input Field | Type   | Required | Description                  |
|-------------|--------|----------|------------------------------|
| phone       | string | Yes      | Phone number (digits only)   |

**Output:**
- `user_id` -- Created/found user ID
- `message` -- Confirmation message

**Behavior:** Creates new user if phone not found; generates 6-digit SMS code with 3-minute TTL.

#### 4.1.2 Verify SMS Code (`POST /api/user/send-code`)

**Purpose:** Verify the SMS code and complete authentication.

| Input Field | Type   | Required | Description              |
|-------------|--------|----------|--------------------------|
| user_id     | int    | Yes      | User ID from send-phone  |
| code        | string | Yes      | 6-digit verification code|
| language    | string | No       | Preferred language (ru/uz/en) |

**Output:** Full user profile with token, including BTS region/city names.

#### 4.1.3 Sign Up (`POST /api/user/sign-up`)

**Purpose:** Register a new user with phone.

| Input Field | Type   | Required | Description         |
|-------------|--------|----------|---------------------|
| phone       | string | Yes      | Phone number        |

**Output:** `token` for verification.

#### 4.1.4 Sign In (`POST /api/user/sign-in`)

**Purpose:** Authenticate existing user by phone.

| Input Field | Type   | Required | Description         |
|-------------|--------|----------|---------------------|
| phone       | string | Yes      | Phone number        |

**Output:** `token` for verification.

#### 4.1.5 Log Out (`POST /api/user/log-out`)

**Purpose:** Invalidate user session token.
**Auth Required:** Yes (Bearer Token)
**Input:** None
**Output:** HTTP 200 OK

#### 4.1.6 Password Recovery -- Send Code (`POST /api/user/recover-password`)

| Input Field | Type   | Required | Description         |
|-------------|--------|----------|---------------------|
| phone       | string | Yes      | Phone number        |

**Output:** Recovery code sent message.

#### 4.1.7 Password Recovery -- Accept Code (`POST /api/user/accept-recover-code`)

| Input Field | Type   | Required | Description         |
|-------------|--------|----------|---------------------|
| phone       | string | Yes      | Phone number        |
| code        | string | Yes      | Recovery code       |

**Output:** Confirmed phone number.

#### 4.1.8 E-IMZO Authentication (`POST /api/user/eimzo-auth`)

**Purpose:** Authenticate via Didox e-signature for legal entities.

| Input Field     | Type   | Required | Description                      |
|-----------------|--------|----------|----------------------------------|
| didox_token     | string | Yes      | Didox authentication token       |
| tax_id          | string | Yes      | Tax Identification Number (INN)  |
| user_type       | string | No       | "fiz" or "yur" (default: "fiz")  |
| name            | string | No       | Override name                    |
| lastname        | string | No       | Override last name               |
| email           | string | No       | Override email                   |
| phone           | string | No       | Override phone                   |
| organization_name | string | No    | Override organization name       |

**Output:** Full user object with token.

#### 4.1.9 E-IMZO Registration (`POST /api/user/eimzo-register`)

**Purpose:** Register new user via E-IMZO digital signature with Didox.

| Input Field       | Type   | Required | Description                          |
|-------------------|--------|----------|--------------------------------------|
| tax_id            | string | Yes      | Tax ID from certificate              |
| email             | string | Yes      | User email                           |
| mobile            | string | Yes      | Phone number                         |
| password          | string | Yes      | Didox account password               |
| pkcs7_64          | string | Yes      | PKCS7 signature for timestamp        |
| signature_hex     | string | Yes      | Hex signature for timestamp          |
| final_signature   | string | Yes      | Final signed INN with timestamp (base64) |
| user_type         | string | No       | "fiz" or "yur" (default: "fiz")      |
| certificate_info  | string | No       | Certificate info JSON                |

**Output:** Full user object with token.

#### 4.1.10 E-IMZO Login (`POST /api/user/eimzo-login`)

**Purpose:** Login existing E-IMZO user.

| Input Field     | Type   | Required | Description                      |
|-----------------|--------|----------|----------------------------------|
| didox_token     | string | Yes      | Didox authentication token       |
| tax_id          | string | Yes      | Tax Identification Number        |
| certificate_info | string | No     | Updated certificate information  |

**Output:** Full user object with refreshed token.

### 4.2 Profile Management Screens

#### 4.2.1 View Profile (`GET /api/user/profile`)

**Auth Required:** Yes
**Output:** User data with image, addresses, BTS region/city names.

#### 4.2.2 Update Profile (`POST /api/user/update`)

**Auth Required:** Yes

| Input Field     | Type    | Required | Description                  |
|-----------------|---------|----------|------------------------------|
| name            | string  | No       | First name                   |
| lastname        | string  | No       | Last name                    |
| middlename      | string  | No       | Middle name                  |
| phone           | string  | No       | Phone number                 |
| email           | string  | No       | Email address                |
| birthday        | string  | No       | Date of birth                |
| gender          | int     | No       | 1=Male, 2=Female             |
| bts_region_id   | int     | No       | BTS region for delivery      |
| bts_city_id     | int     | No       | BTS city for delivery        |
| photo           | file    | No       | Profile photo upload         |
| address[]       | array   | No       | Delivery addresses           |

**Output:** Updated user profile data with BTS location names.

#### 4.2.3 Change Password (`POST /api/user/change-password`)

| Input Field      | Type   | Required | Description         |
|------------------|--------|----------|---------------------|
| password_current | string | Yes      | Current password    |
| password_new     | string | Yes      | New password        |
| password_compare | string | Yes      | Confirm new password|

#### 4.2.4 Change Phone (`POST /api/user/change-phone`)

| Input Field | Type   | Required | Description              |
|-------------|--------|----------|--------------------------|
| phone       | string | Yes      | New phone number         |

#### 4.2.5 Upload Photo (`POST /api/user/upload-photo`)

| Input Field | Type | Required | Description       |
|-------------|------|----------|-------------------|
| photo       | file | Yes      | Profile photo     |

#### 4.2.6 Remove Photo (`POST /api/user/remove-photo`)

**Auth Required:** Yes
**Input:** None

#### 4.2.7 Remove Account (`POST /api/user/remove-account`)

**Auth Required:** Yes
**Input:** None
**Effect:** Deletes user account and associated image.

#### 4.2.8 Remove Address (`POST /api/user/address-remove`)

| Input Field | Type | Required | Description         |
|-------------|------|----------|---------------------|
| address_id  | int  | Yes      | Address ID to remove|

#### 4.2.9 E-IMZO Profile (`GET /api/user/eimzo-profile`)

**Auth Required:** Yes
**Output:** User data + E-IMZO specific info (tax_id, last_login, certificate_info).

#### 4.2.10 BTS Regions (`GET /api/user/bts-regions`)

| Query Param | Type   | Required | Description               |
|-------------|--------|----------|---------------------------|
| language    | string | No       | Language (ru/uz/en)        |

**Output:** List of BTS delivery regions.

#### 4.2.11 BTS Cities (`GET /api/user/bts-cities`)

| Query Param | Type   | Required | Description               |
|-------------|--------|----------|---------------------------|
| region_id   | int    | Yes      | BTS region ID             |
| language    | string | No       | Language (ru/uz/en)        |

**Output:** List of cities within the region.

### 4.3 Payment Card Management

#### 4.3.1 List Cards (`GET /api/user/cards`)

**Auth Required:** Yes
**Output:** Paginated list of user's payment cards with card type info.

#### 4.3.2 Add Card (`POST /api/user/card-add`)

| Input Field | Type   | Required | Description           |
|-------------|--------|----------|-----------------------|
| (card attrs)| various| Yes      | Card attributes       |

**Output:** Created card object.

#### 4.3.3 Card Detail (`GET /api/user/card-detail?card_id={id}`)

**Output:** Single card object with type info.

#### 4.3.4 Remove Card (`POST /api/user/card-remove`)

| Input Field | Type | Required | Description       |
|-------------|------|----------|-------------------|
| card_id     | int  | Yes      | Card ID to remove |

#### 4.3.5 Check Card BIN (`POST /api/user/check-card`)

| Input Field | Type   | Required | Description        |
|-------------|--------|----------|--------------------|
| card_number | string | Yes      | Card number (BIN)  |

**Output:** Bank/card type information from BIN lookup service.

### 4.4 Product Browsing Screens

#### 4.4.1 Product Listing (`GET /api/product/index`)

**Purpose:** Browse all active products with extensive filtering.
**Auth Required:** No

| Query Param   | Type   | Description                                              |
|---------------|--------|----------------------------------------------------------|
| sort           | string | new, recently, price_down, price_up, popular            |
| category_id    | int    | Filter by category (includes subcategories)             |
| tag_id         | int    | Filter by tag                                            |
| brand_id       | int    | Filter by brand                                          |
| shop_id        | int    | Filter by shop                                           |
| filter[id]     | mixed  | Filter by product attributes (AND/OR logic)             |
| filter_logic   | string | "and" or "or" for multi-filter logic                    |
| price_min      | float  | Minimum price filter                                     |
| price_max      | float  | Maximum price filter                                     |
| per-page       | int    | Items per page (default: 12)                             |

**Output:** Paginated product list with `_meta.price_min` and `_meta.price_max` bounds.

#### 4.4.2 Best Products (`GET /api/product/best-products`)

**Purpose:** Top-rated and most-ordered products.
**Auth Required:** No

Supports same filters as index, plus sort options: `rating`, `popular`, `orders`, `price_down`, `price_up`.

#### 4.4.3 For You (Personalized) (`GET /api/product/for-you`)

**Purpose:** AI-driven personalized product recommendations.
**Auth Required:** Optional (enhanced when authenticated)

**Algorithm:**
- Analyzes user's recent category views (30 days)
- Considers recent search queries
- Excludes already-viewed products
- Falls back to popular products for guest users

#### 4.4.4 Products by Category (`GET /api/product/by-category?id={category_id}`)

**Purpose:** Products within a specific category and its subcategories.
Supports: sort, price_min, price_max, per-page.

#### 4.4.5 Products by Brand (`GET /api/product/by-brand?id={brand_id}`)

**Purpose:** Products filtered by brand.

#### 4.4.6 Products by Shop (`GET /api/product/by-shop?id={shop_id}`)

**Purpose:** Products from a specific shop.

#### 4.4.7 Products by Filter (`GET /api/product/by-filter`)

**Purpose:** Products filtered by product attributes/filters.
Supports: filter[id]=value, filter_logic, category_id, price_min, price_max, sort.

#### 4.4.8 Product Search (`GET /api/product/search`)

**Purpose:** Full-text keyword search across products.
**Auth Required:** No

| Query Param   | Type   | Description                                              |
|---------------|--------|----------------------------------------------------------|
| query          | string | Search keywords                                         |
| category_id    | int    | Filter by category                                       |
| brand_id       | int    | Filter by brand                                          |
| shop_id        | int    | Filter by shop                                           |
| filter[id]     | mixed  | Filter by attributes                                     |
| color_id       | int    | Filter by color                                          |
| color_ids[]    | array  | Filter by multiple colors                                |
| price_min      | float  | Minimum price                                            |
| price_max      | float  | Maximum price                                            |
| sort           | string | new, price_down, price_up, popular, rating               |
| per-page       | int    | Items per page                                           |

**Search Scope:** name (RU/UZ/EN), transliterated names, descriptions, compositions, recommendations.
Tracks user search activity for personalization.

#### 4.4.9 Search Suggestions (`GET /api/product/search-suggestions`)

**Purpose:** Autocomplete suggestions while typing.

| Query Param | Type   | Description                    |
|-------------|--------|--------------------------------|
| query       | string | Search prefix (min 2 chars)    |
| limit       | int    | Max suggestions (default: 10)  |

**Output:** Array of suggestions with type (product/category/brand) and language.

#### 4.4.10 Search by Photo (`POST /api/product/by-photo`)

**Purpose:** Visual/image-based product search using perceptual hashing.

| Input Field | Type | Required | Description          |
|-------------|------|----------|----------------------|
| photo       | file | Yes      | Product photo to search |

**Algorithm:** Uses DifferenceHash for image similarity (threshold < 15).

#### 4.4.11 Product Detail (`GET /api/product/detail?id={product_id}`)

**Purpose:** Full product detail view.
**Auth Required:** No

**Output (comprehensive):**
- Product info (all multilingual fields)
- Main image + gallery images
- Category and brand info
- Product filters with filter definitions
- Product reviews
- Product properties (key-value characteristics)
- Product colors with color details
- Product types and type values
- Related product variants (same token_key)
- Each variant's image, color, and product types

**Side effects:** Records product view and recently-viewed tracking.

#### 4.4.12 Related Products (`GET /api/product/related-products?product_id={id}`)

**Purpose:** Products related to a given product.
**Algorithm:** Matches by keywords from name/description, same category/subcategories, same brand.

#### 4.4.13 Recently Viewed (`GET /api/product/recently-viewed`)

**Purpose:** Products recently viewed by the user (based on IP tracking).

### 4.5 Favorites & Comparison Screens

#### 4.5.1 Favorites List (`GET /api/product/favorites`)

**Auth Required:** Yes
Supports: sort, category_id, price_min, price_max, per-page.

#### 4.5.2 Toggle Favorite (`POST /api/product/set-favorite`)

| Input Field | Type | Required | Description         |
|-------------|------|----------|---------------------|
| product_id  | int  | Yes      | Product to toggle   |

**Output:** Product data with `is_favorite` boolean.

#### 4.5.3 Favorite Categories (`GET /api/product/favorite-categories`)

**Output:** Categories present in user's favorites.

#### 4.5.4 Comparison List (`GET /api/product/compares`)

**Auth Required:** Yes

#### 4.5.5 Toggle Comparison (`POST /api/product/set-compare`)

| Input Field | Type | Required | Description         |
|-------------|------|----------|---------------------|
| product_id  | int  | Yes      | Product to toggle   |

**Output:** Product data with `is_compared` boolean.

#### 4.5.6 Comparison Categories (`GET /api/product/compare-categories`)

**Output:** Categories present in user's comparison list.

### 4.6 Product Reviews

#### 4.6.1 Submit Review (`POST /api/product/set-review`)

**Auth Required:** Yes

| Input Field | Type   | Required | Description          |
|-------------|--------|----------|----------------------|
| product_id  | int    | Yes      | Product ID           |
| rate        | int    | Yes      | Rating (1-5)         |
| review      | string | No       | Review text          |

**Constraint:** One review per user per product.

#### 4.6.2 Product Reviews (`GET /api/product/reviews?product_id={id}`)

| Query Param | Type   | Description                    |
|-------------|--------|--------------------------------|
| sort_date   | string | asc or desc                    |
| sort_rating | string | asc or desc                    |
| per-page    | int    | Items per page                 |

**Output:** Reviews with user info, user image, order product reference. Only shows accepted/processed reviews.

### 4.7 Product Request

#### 4.7.1 Submit Product Request (`POST /api/product/request`)

**Auth Required:** Yes

| Input Field   | Type   | Required | Description                |
|---------------|--------|----------|----------------------------|
| product_name  | string | Yes      | Name of requested product  |
| quantity      | int    | Yes      | Desired quantity           |
| phone         | string | Yes      | Contact phone              |
| product_photo | file   | No       | Photo of desired product   |

### 4.8 Shopping Cart Screens

#### 4.8.1 View Cart (`GET /api/cart/index`)

**Auth Required:** Yes
**Output:** Cart items grouped by `token_key` (product variants), with:
- Group info: product name (RU/UZ/EN), main image
- Per-variant: cart ID, product ID, amount, price, unit price, delivery cost, stock amount
- Color info (id, name, code)
- Product types info (type name, value)
- Applied filters

#### 4.8.2 Cart Groups (`GET /api/cart/group`)

**Auth Required:** Yes
**Output:** Alternative grouped view with `is_variant_group` flag.

#### 4.8.3 Add to Cart (`POST /api/cart/add`)

**Auth Required:** Yes
**Prerequisite:** User must have `bts_city_id` set in profile.

**Single Product:**

| Input Field      | Type  | Required | Description              |
|------------------|-------|----------|--------------------------|
| product_id       | int   | Yes      | Product ID               |
| amount           | int   | No       | Quantity (default: 1)    |
| delivery_id      | int   | No       | Delivery method ID       |
| filter_value_id[]| array | No       | Product filter values    |

**Batch Adding:**

| Input Field            | Type  | Required | Description                    |
|------------------------|-------|----------|--------------------------------|
| products[].product_id  | int   | Yes      | Product ID per item            |
| products[].amount      | int   | No       | Quantity per item              |
| products[].filter_value_id[] | array | No | Filter values per item     |

**Validations:** Stock availability, minimum order quantity, wholesale tier pricing.
**Auto-calculation:** BTS delivery cost based on user's city.

#### 4.8.4 Decrease Cart Quantity (`POST /api/cart/minus`)

| Input Field | Type | Required | Description                      |
|-------------|------|----------|----------------------------------|
| product_id  | int  | Yes      | Product ID                       |
| amount      | int  | No       | Quantity to subtract (default: 1)|

**Behavior:** Removes item entirely if quantity reaches zero. Enforces minimum order quantity.

#### 4.8.5 Remove from Cart (`POST /api/cart/remove`)

| Input Field | Type | Required | Description           |
|-------------|------|---------|-----------------------|
| product_id  | int  | Yes     | Product ID to remove  |

#### 4.8.6 Clear Cart (`POST /api/cart/clear`)

**Auth Required:** Yes
**Effect:** Removes all items from user's cart.

#### 4.8.7 Calculate Delivery (`GET/POST /api/cart/calculate`)

**Purpose:** Preview delivery costs for entire cart grouped by warehouse.

| Input Field  | Type | Required | Description                      |
|--------------|------|----------|----------------------------------|
| bts_city_id  | int  | No       | Custom receiver city (overrides profile) |

**Output (comprehensive):**
- Per warehouse group: stock info, sender/receiver city, delivery cost, BTS response data, product details, weight/volume
- Summary: total product cost, total delivery cost, grand total, currency (UZS), stock groups count, total items
- User info: BTS city/region IDs, custom city flag

### 4.9 Order Management Screens

#### 4.9.1 Order List (`GET /api/order/index`)

**Auth Required:** Yes

| Query Param | Type | Description                                           |
|-------------|------|-------------------------------------------------------|
| status      | int  | Filter: 0=Pending, 1=Accepted, 2=Rejected, 3=Delivery pending, 4=In transit, 5=Delivered, 6=Unpaid, 7=Paid, 8=Status 4, 9=Delivered+No review |

**Output:** Paginated orders with products and product images.

#### 4.9.2 Order Detail (`GET /api/order/detail?id={order_id}`)

**Auth Required:** Yes
**Output:** Full order with products, product images, shop info.

#### 4.9.3 Place Order (`POST /api/order/send`)

**Auth Required:** Yes
**Prerequisite:** Cart must not be empty.

| Input Field    | Type   | Required | Description                       |
|----------------|--------|----------|-----------------------------------|
| address        | string | Yes*     | Delivery address                  |
| map_location   | string | No       | GPS coordinates "lat, lng"        |
| payment_id     | int    | Yes      | Payment method ID                 |
| delivery_id    | int    | Yes      | Delivery method ID                |
| receiver       | int    | No       | 1=Customer receives, 0=Other      |
| name           | string | No*      | Alternate receiver first name     |
| lastname       | string | No*      | Alternate receiver last name      |
| phone          | string | No*      | Alternate receiver phone          |
| email          | string | No*      | Alternate receiver email          |
| comment        | string | No       | Order comment                     |
| promocode      | string | No       | Promocode string to apply         |
| wallet_token   | string | No       | Token symbol for wallet payment   |

**Post-order processing:**
1. Order saved with products from cart
2. Auto-creation of Didox documents (invoice + contract)
3. Wallet payment processing (if wallet payment method selected)
4. Cart cleared on success

### 4.10 Promocode Screens

#### 4.10.1 My Promocodes (`GET /api/promocode/my`)

**Auth Required:** Yes
**Output:** List of available promocodes (personal + universal), checking:
- Active status and expiry
- Global and per-user usage limits
- First-order requirement

**Per Promocode:**
- id, title, description, code
- end_date (formatted), min_order_amount
- value, type (fixed/percent), is_personal

#### 4.10.2 Apply Promocode (`POST /api/promocode/apply`)

**Auth Required:** Yes

| Input Field | Type   | Required | Description      |
|-------------|--------|----------|------------------|
| promocode   | string | Yes      | Promocode string |

**Output:** Promocode info + calculation (original_total, discount_amount, final_total).

### 4.11 Crypto Wallet Screens

#### 4.11.1 Wallet Address (`GET /api/wallet/address`)

**Auth Required:** Yes
**Output:** `eoa_address`, `aa_address`

#### 4.11.2 Wallet Balance (`GET /api/wallet/balance`)

**Auth Required:** Yes
**Cached:** 60 seconds
**Output:**
- `aaAddress`, `balance` (ETH), `isDeployed`
- `tokens[]` -- Array of token balances (symbol, address, balance, image)
- `recentTransactions[]` -- Recent on-chain transactions

#### 4.11.3 Deploy Wallet (`POST /api/wallet/deploy`)

**Auth Required:** Yes
**Output:** `status`, `txHash`

#### 4.11.4 Predict Wallet (`GET /api/wallet/predict`)

**Auth Required:** Yes
**Output:** Predicted `eoa_address` and `aa_address` without creation.

#### 4.11.5 Mint Tokens (`POST /api/wallet/mint`)

**Auth Required:** Yes (Admin/Test only)

| Input Field | Type   | Required | Description                      |
|-------------|--------|----------|----------------------------------|
| to          | string | Yes      | Recipient wallet address          |
| amount      | number | Yes      | Amount to mint                    |
| token       | string | No       | Token contract address (default: USDT) |

#### 4.11.6 Execute Payment (`POST /api/wallet/pay`)

**Auth Required:** Yes

| Input Field | Type   | Required | Description                |
|-------------|--------|----------|----------------------------|
| merchantId  | int    | Yes      | Merchant's user ID          |
| amount      | string | Yes      | Payment amount              |
| symbol      | string | Yes      | Token symbol (USDT, USDC)  |

#### 4.11.7 Approve Payment (`POST /api/wallet/approve-payment`)

**Auth Required:** Yes
Same input fields as Execute Payment. Pre-approval step.

#### 4.11.8 Build Payment Batch (`POST /api/wallet/build-batch`)

**Auth Required:** Yes
Same input fields as Execute Payment. Preview mode, no execution.

#### 4.11.9 Supported Tokens (`GET /api/wallet/supported-tokens`)

**Auth Required:** Yes
**Output:** List of supported token contracts.

---

## 5. Seller Dashboard (Role: Shop Owner)

**Base URL:** `/shop/` (Web Panel) and `/dashboard/` (API-based Dashboard)
**Access:** Restricted to users with `role = 4` (ROLE_SHOP)

### 5.1 Shop Owner Web Panel (`/shop/`)

The shop owner web panel mirrors a subset of the admin panel functionality, scoped to the owner's shop. It shares the same AdminLTE layout.

#### 5.1.1 Available Sections

| View Directory       | Controller                    | Description                                |
|----------------------|-------------------------------|--------------------------------------------|
| `analytics/`         | AnalyticsController           | Shop sales analytics and statistics        |
| `brand/`             | BrandController               | Manage brands within shop context          |
| `category/`          | CategoryController            | Browse/manage product categories           |
| `default/`           | DefaultController             | Shop dashboard home page                   |
| `delivery/`          | DeliveryController            | Configure delivery options for shop        |
| `feedback/`          | FeedbackController            | View customer feedback for shop            |
| `filter/`            | FilterController              | Manage product filters/attributes          |
| `logist/`            | LogistController              | Logist management for shop's orders        |
| `moderator/`         | ModeratorController           | Shop moderator management                  |
| `news/`              | NewsController                | Shop news management                       |
| `notification/`      | NotificationController        | Shop notifications                         |
| `order/`             | OrderController               | Order management (shop-scoped)             |
| `payment/`           | PaymentController             | Payment method management                  |
| `product/`           | ProductController             | Product CRUD (create, view, update, list)  |
| `question/`          | QuestionController            | Q&A management for shop                    |
| `review/`            | ReviewController              | Product review management                  |
| `shop/`              | ShopController                | Shop profile/settings                      |
| `shop-advertising/`  | ShopAdvertisingController     | Shop advertising campaigns                 |
| `shop-document/`     | ShopDocumentController        | Upload/manage legal documents              |
| `shop-oferta/`       | ShopOfertaController          | Shop offer/contract management             |
| `shop-support/`      | ShopSupportController         | Support tickets to platform admin          |
| `slider/`            | SliderController              | Shop-specific sliders                      |
| `stock/`             | StockController               | Warehouse management (create, edit, view, list) |
| `translate/`         | TranslateController           | Content translation management             |
| `user/`              | UserController                | Shop customer management                   |

#### 5.1.2 Key Shop Owner Screens

**Shop Dashboard (`default/`)**
- Overview statistics: orders count, revenue, products count
- Recent orders summary
- Quick access to main sections

**Product Management (`product/`)**
- **Product List** (`index.php`): Grid/table of shop's products with search and filter
- **Product Create** (`create.php`): Same comprehensive form as admin product create, scoped to shop
- **Product View** (`view.php`): Detailed product information view
- **Product Edit** (`update.php`): Edit existing product

**Order Management (`order/`)**
- **Order List** (`index.php`): All orders for shop's products
- **Order View** (`view.php`): Order detail with status management actions

**Warehouse/Stock Management (`stock/`)**
- **Stock List** (`index.php`): List of shop's warehouses
- **Stock Create** (`create.php`): Create new warehouse with BTS city configuration
- **Stock Edit** (`update.php`): Update warehouse settings
- **Stock View** (`view.php`): Warehouse detail
- **Stock Form** (`_form.php`): Reusable form partial

**Analytics (`analytics/`)**
- Sales reports, revenue charts, product performance

### 5.2 Dashboard API Module (`/dashboard/`)

The Dashboard module provides an API-based interface for shop owners, with the following controllers:

| Controller               | Description                                |
|--------------------------|--------------------------------------------|
| AdvertismentController   | Advertising campaign management            |
| AuthController           | Shop owner authentication                  |
| BrandController          | Brand management API                       |
| CategoryController       | Category browsing API                      |
| ChatController           | Customer chat API                          |
| ColorController          | Color management API                       |
| DeliveryController       | Delivery configuration API                 |
| DocumentController       | Legal document management API              |
| FeedbackController       | Feedback viewing API                       |
| LogistController         | Logistics management API                   |
| NewsController           | News management API                        |
| OfertaController         | Offer/contract management API              |
| OrderController          | Order management API                       |
| ProductController        | Product CRUD API                           |
| ProfileController        | Shop profile management API                |
| ReviewController         | Review management API                      |
| ShopController           | Shop settings API                          |
| StockController          | Warehouse management API                   |
| SupportController        | Support ticket API                         |
| TransactionController    | Transaction/payment history API            |

---

## 6. Logistics Panel (Role: Logist)

**Base URL:** `/logist/`
**Access:** Restricted to users with `role = 5` (ROLE_LOGIST)
**Layout:** AdminLTE (same base template as admin and shop panels)

### 6.1 Available Sections

| View Directory    | Controller                | Description                              |
|-------------------|---------------------------|------------------------------------------|
| `category/`       | CategoryController        | Browse product categories                |
| `default/`        | DefaultController         | Logist dashboard home                    |
| `logist/`         | LogistController          | Logist account management                |
| `notification/`   | NotificationController    | Notifications for logist                 |
| `order/`          | OrderController           | Order delivery management                |
| `shop/`           | ShopController            | Browse shop information                  |
| `user/`           | UserController            | Browse customer information              |

### 6.2 Key Logist Screens

#### 6.2.1 Order List (`order/index.php`)

**Purpose:** View and manage all orders assigned for delivery.
- Filterable grid of orders
- Status indicators for both shop status and logist status

#### 6.2.2 Order View (`order/view.php`)

**Purpose:** Detailed order view with delivery status management.

**Action Buttons:**
- **Accept** (status=1): Accept the delivery assignment
- **Reject** (status=2): Reject the delivery assignment
- **In Transit** (status=4): Mark as in transit
- **Delivered** (status=5): Mark as delivered

**Order Information Displayed:**

| Field           | Description                                                     |
|-----------------|-----------------------------------------------------------------|
| Order ID        | Unique order identifier                                         |
| Shop Status     | Pending (yellow) / Accepted (green) / Rejected (red)           |
| Logist Status   | Pending / Accepted / Rejected / Sent to Delivery / In Transit / Delivered |
| Payment Status  | Not Paid (red) / Paid (green)                                   |
| Customer        | Link to customer profile                                        |
| Address         | Delivery address                                                |
| Payment Type    | Payment method name                                             |
| Delivery Type   | Delivery method name                                            |
| Product Count   | Number of products                                              |
| Total Amount    | Order total in UZS                                              |
| Receiver Info   | Whether customer receives or alternate receiver details         |
| Comment         | Customer's order comment                                        |
| Date            | Order date                                                      |

**Products Section:**
- For each product: image, name, delivery method, unit price, quantity, delivery cost, total

**Map Section:** Yandex Maps showing delivery location (if coordinates provided).

### 6.3 Logist Status Flow

```
Pending (0) --> Accepted (1) --> Sent to Delivery (3) --> In Transit (4) --> Delivered (5)
     |
     +--> Rejected (2)
```

---

## Appendix A: API Route Configuration

### Explicit Routes (from `config/web.php`)

```
GET  api/product-attribute/category    -> api/product-attribute/category-list
GET  api/product-attribute/tag         -> api/product-attribute/tag-list
GET  api/product-attribute/brand       -> api/product-attribute/brand-list
GET  api/product-attribute/color       -> api/product-attribute/color-list
GET  api/product-attribute/product-type -> api/product-attribute/product-type-list
GET  api/product-attribute/filter      -> api/product-attribute/filter-list
GET  api/product-attribute/ikpu        -> api/product-attribute/ikpu-list
GET  api/product-attribute/user        -> api/product-attribute/user-list
GET  api/product-attribute/shop        -> api/product-attribute/shop-list
GET  api/product-attribute/stock       -> api/product-attribute/stock-list
GET  api/product-attribute/region      -> api/product-attribute/region-list
GET  api/product-attribute/delivery    -> api/product-attribute/delivery-list
GET  api/product-attribute/office      -> api/product-attribute/office-list
POST api/product/create                -> api/product/create (Sklad Integration)
GET  api/sync/products/pending         -> api/sync/pending
POST api/sync/products/confirm         -> api/sync/confirm
GET  api/wallet/balance                -> api/wallet/balance
POST api/wallet/deploy                 -> api/wallet/deploy
```

### Convention-Based Routes (Yii2 REST)

All other API endpoints follow Yii2's conventional routing pattern:
```
/api/{controller}/{action}
```

---

## Appendix B: Order Status Reference

### Order Status (`status` field)

| Value | Label (RU)     | Label (EN)       | Color   |
|-------|---------------|------------------|---------|
| 0     | В ожидании    | Pending          | Yellow  |
| 1     | Принят        | Accepted         | Green   |
| 2     | Отклонен      | Rejected         | Red     |
| 3     | На доставке   | On Delivery      | Green   |
| 4     | В пути        | In Transit       | Green   |
| 5     | Доставлен     | Delivered        | Green   |
| 10    | Возврат       | Return           | Red     |

### Logist Status (`status_logist` field)

| Value | Label (RU)            | Label (EN)          | Color  |
|-------|-----------------------|---------------------|--------|
| 0     | В ожидании           | Pending             | Yellow |
| 1     | Принят               | Accepted            | Green  |
| 2     | Отклонен             | Rejected            | Red    |
| 3     | Отправлен на доставку | Sent to Delivery   | Aqua   |
| 4     | В пути               | In Transit          | Aqua   |
| 5     | Доставлен            | Delivered           | Green  |

### Payment Status (`status_payment` field)

| Value | Label (RU)   | Label (EN) | Color |
|-------|-------------|------------|-------|
| 0     | Не оплачен  | Not Paid   | Red   |
| 1     | Оплачен     | Paid       | Green |

### BTS Delivery Status Codes

| Value | Description (EN)        | Color Class     |
|-------|------------------------|-----------------|
| -1    | Draft                  | Warning (yellow)|
| 0     | Refused                | Danger (red)    |
| 1     | At Sender              | Info (blue)     |
| 2     | In Transit (stage 1)   | Primary         |
| 3     | In Transit (stage 2)   | Primary         |
| 4     | At Delivery Office     | Warning         |
| 5     | Out for Delivery       | Warning         |
| 6     | Delivered              | Success (green) |
| 7     | Return                 | Danger (red)    |
| 8+    | Various processing     | Info            |

---

## Appendix C: Multilingual Support

The platform supports three languages across all content fields:

| Code | Language   | Field Suffix |
|------|-----------|-------------|
| ru   | Russian    | `_ru`       |
| uz   | Uzbek      | `_uz`       |
| en   | English    | `_en`       |

**Affected entities:** Product names, descriptions, compositions, recommendations; Category names; Brand names; Filter names/values; News content; Delivery names; Color names; and all other content-bearing models.

All admin/shop forms provide tabbed multilingual input (RU / UZ / EN tabs).

---

## Appendix D: Didox Document Status Reference

| Value | Label (RU)              | Label (EN)                | Color      |
|-------|------------------------|---------------------------|------------|
| 0     | Черновик               | Draft                     | Warning    |
| 1     | Ожидает партнера       | Waiting Partner            | Info       |
| 2     | Ожидает вашу подпись   | Waiting Your Signature     | Primary    |
| 3     | Подписан               | Signed                     | Success    |
| 4     | Отклонен               | Rejected                   | Danger     |
| 120   | Отменен                | Canceled                   | Default    |

**Document Types:**
- **Invoice (Счет-фактура):** Standard tax invoice, Didox doc type "002"
- **Arbitrary Contract (Произвольный договор):** Custom contract/agreement document

---

*End of Document*
