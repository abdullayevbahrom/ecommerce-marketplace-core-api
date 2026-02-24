# app -- Database Schema Documentation

**Database:** `app`
**Engine:** MySQL 8.0
**Character Set:** utf8mb4 (utf8mb4_0900_ai_ci)
**Total Tables:** 82

---

## Table of Contents

1. [Overview](#1-overview)
2. [Entity-Relationship Summary](#2-entity-relationship-summary)
3. [Detailed Table Definitions](#3-detailed-table-definitions)
4. [Relationship Diagram](#4-relationship-diagram)

---

## 1. Overview

The **app** database powers a multi-vendor e-commerce marketplace platform targeting the Uzbekistan market. It supports:

- **Multi-language content** (Russian, Uzbek, English) across all user-facing entities
- **Multi-vendor architecture** with shops, sellers, stocks/warehouses, and logistics providers
- **Full order lifecycle** from cart through payment, delivery tracking (BTS integration), and refunds
- **Electronic document management** via DIDOX e-signature integration (invoices, arbitrary documents)
- **Product moderation pipeline** for warehouse-synced products (Sklad integration)
- **Promotional system** with flexible promocodes (fixed/percentage, per-user, per-category, first-order)
- **Payment integrations** with Payme and PayKeeper
- **BTS logistics integration** for delivery cost calculation and shipment tracking

The database consists of **82 tables** organized into the following domains.

---

## 2. Entity-Relationship Summary

### 2.1 Users & Authentication
| Table | Purpose |
|-------|---------|
| `user` | Core user accounts (buyers, sellers, moderators, admins) |
| `sms_code` | SMS verification codes for phone-based authentication |
| `user_address` | Saved delivery addresses per user |
| `user_card` | Saved payment cards (tokenized) per user |
| `moderator_access` | Maps moderator users to the user accounts they can manage |
| `moderator_url` | Defines available admin panel URLs/routes for moderators |

### 2.2 Products & Catalog
| Table | Purpose |
|-------|---------|
| `product` | Master product catalog with pricing, dimensions, and warehouse sync info |
| `category` | Hierarchical product categories (self-referencing via parent_id) |
| `category_brand` | Brands associated with specific categories |
| `category_filter` | Filter values available at the category level |
| `color` | Global color dictionary |
| `product_color` | Many-to-many link between products and colors |
| `product_filter` | Filter attribute values assigned to individual products |
| `product_type` | Configurable attribute types per category (input, select, checkbox, range) |
| `product_type_value` | Predefined values for product_type attributes |
| `product_product_type` | Many-to-many link assigning type values to products |
| `product_property` | Free-form key-value properties for products |
| `product_rating` | Individual user ratings for products |
| `product_review` | User-written product reviews with moderation status |
| `product_view` | Page view tracking per product (by IP) |
| `product_view_recently` | Recently viewed products tracking (by IP) |
| `product_request` | Customer requests for products not yet available |
| `product_office` | Product pricing/availability per office location |
| `product_moderation_comments` | Audit trail for product moderation actions |
| `product_sync_log` | Synchronization log for Sklad warehouse integration |
| `pending_products` | Incoming products from Sklad awaiting moderator approval |
| `ikpu` | IKPU codes (Uzbekistan product classification system) |
| `filter` | Hierarchical filter definitions per category |
| `filter_user` | User-specific filter toggle preferences |

### 2.3 Orders & Payments
| Table | Purpose |
|-------|---------|
| `order` | Customer orders with delivery, payment, and status tracking |
| `order_product` | Individual product line items within an order |
| `order_product_filter` | Selected filter/variant options per order line item |
| `order_product_refund` | Refund requests per order line item |
| `order_receipt` | Fiscal receipts linked to orders |
| `transaction` | Internal transaction records for payments |
| `transaction_payme` | Payme payment gateway transaction details |
| `pay_keeper_transaction` | PayKeeper payment gateway transaction details |
| `promocode` | Promotional discount codes with rules and limits |
| `payme_password` | Payme API authentication credentials |

### 2.4 Shops & Sellers
| Table | Purpose |
|-------|---------|
| `shop` | Vendor/shop profiles on the marketplace |
| `shop_seller` | Legal entity details for shops (INN, bank, OKED, MFO) |
| `shop_advertising` | Advertising content managed per shop |
| `shop_document` | Legal/policy documents per shop |
| `shop_oferta` | Public offer agreements per shop |
| `shop_support` | Support tickets submitted by shop owners |
| `shop_logist` | Many-to-many link between shops and logistics providers |
| `stock` | Warehouse/stock locations per shop with BTS region mapping |
| `seller_application` | Applications from prospective sellers to join the platform |

### 2.5 Delivery & Logistics
| Table | Purpose |
|-------|---------|
| `delivery` | Delivery method definitions with pricing |
| `logist` | Logistics provider companies |
| `logist_region` | Regions served by each logistics provider |
| `logist_region_price` | Pricing tiers per logist region based on unit/weight |
| `regions` | Geographic regions (with BTS system IDs) |
| `cities` | Cities within regions (with BTS system IDs) |
| `office` | Physical office/pickup point locations |

### 2.6 E-Signature / DIDOX
| Table | Purpose |
|-------|---------|
| `didox_document` | Master record for DIDOX electronic documents (invoices, arbitrary) |
| `didox_document_invoice` | Detailed invoice (factura) data for DIDOX submissions |
| `didox_document_arbitrary` | Arbitrary document details for DIDOX submissions |
| `didox_document_included_products` | Line items (products) included in DIDOX documents |

### 2.7 Shopping Cart
| Table | Purpose |
|-------|---------|
| `user_cart` | Shopping cart items per user |
| `user_cart_filter` | Selected filter/variant options per cart item |

### 2.8 Social & Engagement
| Table | Purpose |
|-------|---------|
| `user_favorite` | User product wishlists/favorites |
| `user_shop_favorite` | User favorite shops |
| `user_compare` | Products added to comparison list |
| `feedback` | General feedback/contact form submissions |
| `question` | FAQ entries (question + answer in 3 languages) |
| `product_review` | Product reviews (also listed under Products) |

### 2.9 Content & CMS
| Table | Purpose |
|-------|---------|
| `banner` | Homepage/promotional banners |
| `slider` | Image slider/carousel entries |
| `news` | News articles/blog posts per shop |
| `news_view` | News article view tracking by IP |
| `advantages` | Platform advantages/features for display |
| `partners` | Partner company profiles |
| `words` | Translation dictionary / localized strings |

### 2.10 Communication
| Table | Purpose |
|-------|---------|
| `messages` | Individual messages within message rooms |
| `message_room` | Chat rooms between users (about products) |
| `notification` | Push/in-app notifications per user |
| `support_chat` | Support chat threads between users |

### 2.11 System
| Table | Purpose |
|-------|---------|
| `settings` | Global platform configuration key-value store |
| `logs` | Application event/error logging |
| `migration` | Yii2 framework database migration version tracking |
| `sms_history` | SMS sending history with request/response data |
| `file` | Generic file attachments (polymorphic via object_id + type) |
| `image` | Image assets (polymorphic via object_id + type) |

---

## 3. Detailed Table Definitions

### 3.1 Users & Authentication

---

#### `user`

Core user accounts for all platform participants (buyers, sellers, moderators, admins).

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `role` | int | YES | NULL | User role identifier |
| `type` | varchar(255) | YES | NULL | Account type (e.g. physical/legal) |
| `token` | varchar(255) | YES | NULL | API authentication token |
| `facebook_id` | varchar(255) | YES | NULL | Facebook OAuth ID |
| `google_id` | varchar(255) | YES | NULL | Google OAuth ID |
| `vk_id` | varchar(255) | YES | NULL | VKontakte OAuth ID |
| `device_id` | varchar(255) | YES | NULL | Mobile device identifier |
| `shop_id` | int | YES | NULL | FK to `shop.id` (if seller) |
| `balance` | double | NO | 0 | Wallet balance |
| `name` | varchar(255) | YES | NULL | First name |
| `lastname` | varchar(255) | YES | NULL | Last name |
| `phone` | varchar(255) | YES | NULL | Phone number |
| `email` | varchar(255) | YES | NULL | Email address |
| `login` | varchar(255) | YES | NULL | Login username (UNIQUE) |
| `password` | varchar(255) | YES | NULL | Hashed password |
| `gender` | int | YES | NULL | Gender |
| `birthday` | varchar(255) | YES | NULL | Date of birth |
| `last_address` | text | YES | NULL | Last used delivery address |
| `inn` | varchar(255) | YES | NULL | Tax identification number |
| `account` | varchar(255) | YES | NULL | Bank account number |
| `bank` | varchar(255) | YES | NULL | Bank name |
| `address_legal` | varchar(255) | YES | NULL | Legal address |
| `oked` | varchar(255) | YES | NULL | OKED code |
| `okohx` | varchar(255) | YES | NULL | OKOHX code |
| `mfo` | varchar(255) | YES | NULL | Bank MFO code |
| `manager` | int | YES | NULL | Assigned manager user ID |
| `status` | int | NO | 0 | Account status |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Last updated |
| `ip` | varchar(255) | YES | NULL | Last login IP |
| `eimzo_tax_id` | varchar(20) | YES | NULL | E-IMZO tax ID |
| `eimzo_didox_token` | varchar(255) | YES | NULL | E-IMZO DIDOX API token |
| `eimzo_last_login` | timestamp | YES | NULL | Last E-IMZO login |
| `eimzo_certificate_info` | text | YES | NULL | E-IMZO certificate data |
| `phone_code` | varchar(10) | YES | NULL | Phone country code |
| `sms_live` | int | YES | NULL | SMS code TTL |
| `middlename` | varchar(255) | YES | NULL | Middle name |
| `organization_name` | varchar(255) | YES | NULL | Organization name (legal entities) |
| `bts_region_id` | int | YES | NULL | BTS region ID |
| `bts_city_id` | int | YES | NULL | BTS city ID |

**Primary Key:** `id`
**Unique Keys:** `login`
**Indexes:** `bts_region_id`, `bts_city_id`

---

#### `sms_code`

Temporary SMS verification codes for phone-based user authentication.

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `user_id` | int | YES | NULL | FK to `user.id` |
| `token` | varchar(255) | NO | - | Session token |
| `phone` | varchar(255) | YES | NULL | Phone number |
| `code` | varchar(255) | YES | NULL | Verification code |
| `sms_expire` | int | YES | NULL | Expiration timestamp |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Created/updated |

**Primary Key:** `id`

---

#### `user_address`

Saved delivery addresses for users.

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `user_id` | int | YES | NULL | FK to `user.id` |
| `address` | text | YES | NULL | Full address text |
| `status` | int | NO | 1 | Active/inactive |
| `sort` | int | NO | 0 | Display order |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Last updated |

**Primary Key:** `id`
**Indexes:** `user_id`

---

#### `user_card`

Tokenized payment cards saved by users.

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `user_id` | int | YES | NULL | FK to `user.id` |
| `card_type_id` | int | YES | NULL | Card type |
| `card_token` | text | YES | NULL | Payment token |
| `card_number` | varchar(255) | YES | NULL | Masked card number |
| `card_expire` | varchar(255) | YES | NULL | Expiry date |
| `card_phone_number` | varchar(255) | YES | NULL | Phone linked to card |
| `status` | int | NO | 1 | Active/inactive |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Last updated |

**Primary Key:** `id`
**Indexes:** `user_id`

---

#### `moderator_access`

Maps moderator users to the regular user accounts they are authorized to manage.

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `moderator_id` | int | YES | NULL | FK to `user.id` (moderator) |
| `user_id` | int | YES | NULL | FK to `user.id` (managed user) |

**Primary Key:** `id`
**Indexes:** `moderator_id`, `user_id`

---

#### `moderator_url`

Defines admin panel URL routes and their labels for moderator role-based access.

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `type` | varchar(255) | YES | NULL | Route group type |
| `name` | varchar(255) | YES | NULL | Display name |
| `url` | varchar(255) | YES | NULL | URL path |

**Primary Key:** `id`

---

### 3.2 Products & Catalog

---

#### `product`

Master product catalog containing all marketplace listings.

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `user_id` | int | YES | NULL | FK to `user.id` (product owner) |
| `category_id` | int | YES | NULL | FK to `category.id` |
| `stock_id` | int | YES | NULL | FK to `stock.id` |
| `shop_id` | int | YES | NULL | FK to `shop.id` |
| `category_tree` | varchar(255) | YES | NULL | Denormalized category path |
| `brand_id` | int | YES | NULL | FK to `category_brand.id` |
| `region_id` | int | YES | NULL | FK to `regions.id` |
| `currency_id` | int | YES | NULL | Currency identifier |
| `unit_id` | int | YES | NULL | Measurement unit |
| `color_id` | int | YES | NULL | FK to `color.id` |
| `delivery_id` | int | YES | NULL | FK to `delivery.id` |
| `tag_id` | int | YES | NULL | Tag identifier |
| `name_ru` | varchar(255) | YES | NULL | Name (Russian) |
| `name_en` | varchar(255) | YES | NULL | Name (English) |
| `name_uz` | varchar(255) | YES | NULL | Name (Uzbek) |
| `name_trans_ru` | varchar(255) | YES | NULL | Transliterated name (RU) |
| `name_trans_en` | varchar(255) | YES | NULL | Transliterated name (EN) |
| `description_ru` | text | YES | NULL | Description (Russian) |
| `description_en` | text | YES | NULL | Description (English) |
| `description_uz` | text | YES | NULL | Description (Uzbek) |
| `composition_ru` | text | YES | NULL | Composition (Russian) |
| `composition_uz` | text | YES | NULL | Composition (Uzbek) |
| `composition_en` | text | YES | NULL | Composition (English) |
| `recommendation_ru` | text | YES | NULL | Recommendations (Russian) |
| `recommendation_uz` | text | YES | NULL | Recommendations (Uzbek) |
| `recommendation_en` | text | YES | NULL | Recommendations (English) |
| `price` | double | YES | NULL | Retail price |
| `price_small` | double | YES | NULL | Small wholesale price |
| `price_opt` | double | YES | NULL | Wholesale price |
| `min_order` | int | NO | 0 | Minimum order quantity |
| `weight` | double | YES | NULL | Weight (grams) |
| `height` | double | YES | NULL | Height |
| `width` | double | YES | NULL | Width |
| `length` | double | YES | NULL | Length |
| `discount` | double | YES | NULL | Discount percentage |
| `amount` | double | YES | NULL | Available quantity |
| `credit_label` | varchar(255) | YES | NULL | Credit/installment label |
| `views` | int | NO | 0 | View counter |
| `rating` | double | NO | 0 | Average rating |
| `status` | int | NO | 2 | Product status (0=inactive, 1=active, 2=moderation) |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Last updated |
| `count_price` | int | YES | NULL | Tiered pricing count |
| `count_price1` | int | YES | NULL | Tiered pricing count 1 |
| `count_price2` | int | YES | NULL | Tiered pricing count 2 |
| `discount_small_count` | int | YES | NULL | Small wholesale discount threshold |
| `discount_big_count` | int | YES | NULL | Big wholesale discount threshold |
| `token_key` | varchar(255) | YES | NULL | External token key |
| `billz_id` | varchar(255) | YES | NULL | Billz integration ID |
| `sku` | varchar(255) | YES | NULL | Stock Keeping Unit |
| `barcode` | varchar(255) | YES | NULL | Barcode |
| `qty` | varchar(255) | YES | NULL | Quantity string |
| `button_id` | bigint | NO | 0 | UI button identifier |
| `qty_small_wholesale` | int | YES | NULL | Small wholesale quantity |
| `qty_big_wholesale` | int | YES | NULL | Big wholesale quantity |
| `ikpu_code` | varchar(17) | YES | NULL | IKPU product classification code |
| `ikpu_name` | varchar(500) | YES | NULL | Cached IKPU name |
| `package_code` | varchar(50) | YES | NULL | Package code |
| `package_name` | varchar(100) | YES | NULL | Package name |
| `sklad_product_id` | int | YES | NULL | Sklad warehouse product ID |
| `sync_status` | tinyint | YES | 0 | 0=Pending, 1=Synced, 2=Failed |

**Primary Key:** `id`
**Indexes:** `user_id`, `category_id`, `sklad_product_id`, `sync_status`

---

#### `category`

Hierarchical product categories supporting self-referencing parent-child relationships.

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `parent_id` | int | NO | 0 | Self-FK to `category.id` (0 = root) |
| `type` | varchar(255) | YES | NULL | Category type (e.g. 'product', 'payment') |
| `name_mini` | varchar(255) | YES | NULL | Short name |
| `name_ru` | varchar(255) | YES | NULL | Name (Russian) |
| `name_uz` | varchar(255) | YES | NULL | Name (Uzbek) |
| `name_en` | varchar(255) | YES | NULL | Name (English) |
| `description_ru` | text | YES | NULL | Description (Russian) |
| `description_uz` | text | YES | NULL | Description (Uzbek) |
| `description_en` | text | YES | NULL | Description (English) |
| `option_ru` | text | YES | NULL | Options (Russian) |
| `option_uz` | text | YES | NULL | Options (Uzbek) |
| `option_en` | text | YES | NULL | Options (English) |
| `sort` | int | NO | 0 | Display order |
| `status` | int | NO | 0 | Active/inactive |
| `main` | int | NO | 0 | Featured flag |
| `is_filter` | int | YES | NULL | Has filters flag |
| `popular` | int | YES | NULL | Popular flag |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Last updated |

**Primary Key:** `id`
**Unique Keys:** `id`

---

#### `category_brand`

Brands associated with product categories.

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `category_id` | int | YES | NULL | FK to `category.id` |
| `category_tree` | varchar(255) | YES | NULL | Denormalized category path |
| `name_ru` | varchar(255) | YES | NULL | Name (Russian) |
| `name_en` | varchar(255) | YES | NULL | Name (English) |
| `name_uz` | varchar(255) | YES | NULL | Name (Uzbek) |
| `description_ru` | text | YES | NULL | Description (Russian) |
| `description_en` | text | YES | NULL | Description (English) |
| `description_uz` | text | YES | NULL | Description (Uzbek) |
| `status` | int | NO | 1 | Active/inactive |
| `sort` | int | NO | 0 | Display order |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Last updated |

**Primary Key:** `id`
**Indexes:** `category_id`

---

#### `category_filter`

Filter value options available at the category level.

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `category_id` | int | YES | NULL | FK to `category.id` |
| `filter_id` | int | YES | NULL | FK to `filter.id` |
| `value_id` | int | YES | NULL | Value identifier |
| `value_ru` | varchar(255) | YES | NULL | Value (Russian) |
| `value_en` | varchar(255) | YES | NULL | Value (English) |
| `value_uz` | varchar(255) | YES | NULL | Value (Uzbek) |

**Primary Key:** `id`
**Indexes:** `category_id`
**Foreign Keys:** `category_id` -> `category(id)` ON DELETE CASCADE

---

#### `color`

Global color dictionary used across the product catalog.

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `name_ru` | varchar(255) | YES | NULL | Name (Russian) |
| `name_en` | varchar(255) | YES | NULL | Name (English) |
| `name_uz` | varchar(255) | YES | NULL | Name (Uzbek) |
| `color` | varchar(255) | YES | NULL | Hex color code |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Last updated |

**Primary Key:** `id`
**Unique Keys:** `id`

---

#### `product_color`

Many-to-many relationship between products and available colors.

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `product_id` | int | YES | NULL | FK to `product.id` |
| `color_id` | int | YES | NULL | FK to `color.id` |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Last updated |
| `status` | int | YES | NULL | Active/inactive |

**Primary Key:** `id`
**Indexes:** `product_id`, `color_id`
**Foreign Keys:** `product_id` -> `product(id)` ON DELETE CASCADE, `color_id` -> `color(id)` ON DELETE CASCADE

---

#### `product_filter`

Specific filter attribute values assigned to individual products.

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `product_id` | int | YES | NULL | FK to `product.id` |
| `filter_id` | int | YES | NULL | FK to `filter.id` |
| `value_id` | int | YES | NULL | Value identifier |
| `value_ru` | varchar(255) | YES | NULL | Value (Russian) |
| `value_en` | varchar(255) | YES | NULL | Value (English) |
| `value_uz` | varchar(255) | YES | NULL | Value (Uzbek) |

**Primary Key:** `id`
**Indexes:** `product_id`

---

#### `product_type`

Configurable attribute type definitions per category (like size, material, etc.).

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `category_id` | int | YES | NULL | FK to `category.id` |
| `name_ru` | varchar(255) | YES | NULL | Name (Russian) |
| `name_en` | varchar(255) | YES | NULL | Name (English) |
| `name_uz` | varchar(255) | YES | NULL | Name (Uzbek) |
| `type` | varchar(255) | NO | - | Input type: input, select, checkbox, range |
| `description_ru` | text | YES | NULL | Description (Russian) |
| `description_en` | text | YES | NULL | Description (English) |
| `description_uz` | text | YES | NULL | Description (Uzbek) |
| `status` | int | NO | 1 | 0=inactive, 1=active |
| `sort` | int | NO | 0 | Display order |
| `date` | timestamp | YES | CURRENT_TIMESTAMP | Created |

**Primary Key:** `id`
**Indexes:** `category_id`
**Foreign Keys:** `category_id` -> `category(id)` ON DELETE CASCADE

---

#### `product_type_value`

Predefined selectable values for product_type attributes.

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `product_type_id` | int | NO | - | FK to `product_type.id` |
| `value_ru` | varchar(255) | NO | - | Value (Russian) |
| `value_en` | varchar(255) | YES | NULL | Value (English) |
| `value_uz` | varchar(255) | YES | NULL | Value (Uzbek) |
| `display_value` | varchar(255) | YES | NULL | Formatted display (e.g. "130x160 cm") |
| `description_ru` | text | YES | NULL | Description (Russian) |
| `description_en` | text | YES | NULL | Description (English) |
| `description_uz` | text | YES | NULL | Description (Uzbek) |
| `status` | int | NO | 1 | Active/inactive |
| `sort` | int | NO | 0 | Display order |
| `date` | timestamp | YES | CURRENT_TIMESTAMP | Created |

**Primary Key:** `id`
**Indexes:** `product_type_id`
**Foreign Keys:** `product_type_id` -> `product_type(id)` ON DELETE CASCADE

---

#### `product_product_type`

Many-to-many assignment of product type values to products.

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `product_id` | int | NO | - | FK to `product.id` |
| `product_type_id` | int | NO | - | FK to `product_type.id` |
| `product_type_value_id` | int | YES | NULL | FK to `product_type_value.id` (NULL for custom) |
| `custom_value` | varchar(255) | YES | NULL | Custom value for input types |
| `date` | timestamp | YES | CURRENT_TIMESTAMP | Created |

**Primary Key:** `id`
**Unique Keys:** `(product_id, product_type_id, product_type_value_id)`
**Foreign Keys:** `product_id` -> `product(id)` ON DELETE CASCADE, `product_type_id` -> `product_type(id)` ON DELETE CASCADE, `product_type_value_id` -> `product_type_value(id)` ON DELETE CASCADE

---

#### `product_property`

Free-form key-value properties for products.

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `product_id` | int | YES | NULL | FK to `product.id` |
| `key_name` | varchar(255) | YES | NULL | Property key |
| `value_name` | varchar(255) | YES | NULL | Property value |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Last updated |

**Primary Key:** `id`

---

#### `product_rating`

Individual user rating scores for products.

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `user_id` | int | YES | NULL | FK to `user.id` |
| `product_id` | int | YES | NULL | FK to `product.id` |
| `rate` | double | YES | NULL | Rating value |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Last updated |

**Primary Key:** `id`

---

#### `product_review`

User-written product reviews with moderation workflow.

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `user_id` | int | YES | NULL | FK to `user.id` |
| `product_id` | int | YES | NULL | FK to `product.id` |
| `review` | text | YES | NULL | Review text |
| `rate` | double | YES | NULL | Rating |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Last updated |
| `status` | int | NO | 0 | Moderation status |
| `status_date` | timestamp | YES | NULL | Status change date |
| `status_user_id` | int | YES | NULL | Moderator user ID |
| `status_comment` | varchar(255) | YES | NULL | Moderator comment |

**Primary Key:** `id`
**Indexes:** `user_id`, `product_id`

---

#### `product_view`

Product page view tracking by IP address.

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `product_id` | int | YES | NULL | FK to `product.id` |
| `ip` | varchar(255) | YES | NULL | Visitor IP |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | View timestamp |

**Primary Key:** `id`
**Indexes:** `product_id`

---

#### `product_view_recently`

Recently viewed products per visitor for recommendation features.

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `product_id` | int | YES | NULL | FK to `product.id` |
| `ip` | varchar(255) | YES | NULL | Visitor IP |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | View timestamp |

**Primary Key:** `id`
**Indexes:** `product_id`

---

#### `product_request`

Customer requests for products not currently available on the platform.

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `product_name` | varchar(255) | NO | - | Requested product name |
| `product_photo` | varchar(255) | YES | NULL | Photo URL |
| `quantity` | int | NO | - | Desired quantity |
| `product_link` | text | YES | NULL | External link |
| `phone` | varchar(255) | NO | - | Contact phone |
| `email` | varchar(255) | YES | NULL | Contact email |
| `status` | int | YES | 1 | Request status |
| `admin_notes` | text | YES | NULL | Admin notes |
| `date` | datetime | YES | CURRENT_TIMESTAMP | Submission date |
| `user_id` | int | YES | NULL | FK to `user.id` |
| `admin_id` | int | YES | NULL | FK to `user.id` (admin) |

**Primary Key:** `id`

---

#### `product_office`

Product pricing and availability per office/pickup location.

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `office_id` | int | YES | NULL | FK to `office.id` |
| `product_id` | int | YES | NULL | FK to `product.id` |
| `price` | varchar(255) | YES | NULL | Price at this office |
| `price_usd` | varchar(255) | YES | NULL | USD price |
| `discount` | varchar(255) | YES | NULL | Discount at this office |
| `qty` | varchar(255) | YES | NULL | Quantity available |

**Primary Key:** `id`
**Indexes:** `office_id`, `product_id`
**Foreign Keys:** `office_id` -> `office(id)` ON DELETE CASCADE, `product_id` -> `product(id)` ON DELETE CASCADE

---

#### `product_moderation_comments`

Audit trail for moderation actions on pending product submissions.

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `submission_id` | int | NO | - | FK to `pending_products.id` |
| `moderator_id` | int | YES | NULL | FK to `user.id` |
| `action` | varchar(50) | NO | - | Action: submit, approve, reject, request_changes |
| `comment` | text | YES | NULL | Comment visible to merchant |
| `internal_notes` | text | YES | NULL | Private moderator notes |
| `status_before` | varchar(20) | YES | NULL | Status before action |
| `status_after` | varchar(20) | YES | NULL | Status after action |
| `metadata` | json | YES | NULL | Extra data (e.g. rejected fields) |
| `created_at` | timestamp | NO | CURRENT_TIMESTAMP | Action timestamp |

**Primary Key:** `id`
**Indexes:** `submission_id`, `moderator_id`, `created_at`

---

#### `product_sync_log`

Synchronization log for the Sklad warehouse integration pipeline.

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `submission_id` | int | YES | NULL | FK to `pending_products.id` |
| `direction` | enum('incoming','outgoing') | NO | 'incoming' | Sync direction |
| `action` | varchar(50) | NO | - | Action: submit, webhook_sent, webhook_failed |
| `endpoint` | varchar(500) | YES | NULL | API endpoint URL |
| `request_data` | text | YES | NULL | Request payload |
| `response_data` | text | YES | NULL | Response payload |
| `response_code` | int | YES | NULL | HTTP response code |
| `message` | text | YES | NULL | Log message |
| `created_at` | timestamp | NO | CURRENT_TIMESTAMP | Log timestamp |

**Primary Key:** `id`
**Indexes:** `submission_id`, `action`, `created_at`

---

#### `pending_products`

Incoming products from the Sklad warehouse system awaiting moderator approval.

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `warehouse_product_id` | int | NO | - | Sklad pending_products table ID |
| `branch_id` | int | NO | - | Sklad branch ID |
| `branch_name` | varchar(255) | YES | NULL | Branch name |
| `branch_yii_stock_id` | int | YES | NULL | FK to `stock.id` if synced |
| `merchant_id` | int | YES | NULL | Sklad merchant ID |
| `merchant_name` | varchar(255) | YES | NULL | Merchant name |
| `name` | varchar(255) | YES | NULL | Main product name |
| `name_ru` | varchar(255) | YES | NULL | Name (Russian) |
| `name_en` | varchar(255) | YES | NULL | Name (English) |
| `name_uz` | varchar(255) | YES | NULL | Name (Uzbek) |
| `description_ru` | text | YES | NULL | Description (Russian) |
| `description_en` | text | YES | NULL | Description (English) |
| `description_uz` | text | YES | NULL | Description (Uzbek) |
| `price` | decimal(15,2) | YES | NULL | Regular price |
| `price_small` | decimal(15,2) | YES | NULL | Small wholesale price |
| `price_opt` | decimal(15,2) | YES | NULL | Wholesale price |
| `amount` | decimal(15,2) | YES | NULL | Available quantity |
| `barcode` | varchar(100) | YES | NULL | Barcode |
| `sku` | varchar(100) | YES | NULL | SKU |
| `weight` | decimal(10,2) | YES | NULL | Weight (grams) |
| `discount` | decimal(5,2) | YES | NULL | Discount percentage |
| `data` | json | NO | - | Complete Sklad request payload |
| `status` | enum('pending','approved','rejected') | NO | 'pending' | Moderation status |
| `callback_url` | varchar(500) | YES | NULL | Webhook URL for Sklad |
| `moderator_comment` | text | YES | NULL | Feedback to merchant |
| `moderator_id` | int | YES | NULL | FK to `user.id` |
| `approved_product_id` | int | YES | NULL | FK to `product.id` after approval |
| `created_at` | timestamp | NO | CURRENT_TIMESTAMP | Submitted |
| `updated_at` | timestamp | NO | CURRENT_TIMESTAMP | Last updated |
| `processed_at` | timestamp | YES | NULL | Moderation completion time |

**Primary Key:** `id`
**Unique Keys:** `(branch_id, warehouse_product_id)`
**Indexes:** `status`, `merchant_id`, `approved_product_id`, `created_at`

---

#### `ikpu`

IKPU product classification codes (Uzbekistan national standard).

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `code` | varchar(17) | NO | - | IKPU code (UNIQUE) |
| `name_ru` | varchar(500) | NO | - | Name (Russian) |
| `name_uz` | varchar(500) | YES | NULL | Name (Uzbek) |
| `name_en` | varchar(500) | YES | NULL | Name (English) |
| `parent_code` | varchar(17) | YES | NULL | Self-FK to `ikpu.code` |
| `status` | int | YES | 1 | Active/inactive |
| `created_at` | timestamp | YES | CURRENT_TIMESTAMP | Created |
| `updated_at` | timestamp | YES | CURRENT_TIMESTAMP | Last updated |

**Primary Key:** `id`
**Unique Keys:** `code`
**Foreign Keys:** `parent_code` -> `ikpu(code)` ON DELETE SET NULL ON UPDATE CASCADE

---

#### `filter`

Hierarchical filter attribute definitions tied to product categories.

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `parent_id` | int | NO | 0 | Self-FK (0 = root filter) |
| `category_id` | int | YES | NULL | FK to `category.id` |
| `sub_category_id` | int | YES | NULL | Sub-category reference |
| `category_tree` | varchar(255) | YES | NULL | Denormalized category path |
| `type` | varchar(255) | YES | NULL | Filter type |
| `name_ru` | varchar(255) | YES | NULL | Name (Russian) |
| `name_uz` | varchar(255) | YES | NULL | Name (Uzbek) |
| `name_en` | varchar(255) | YES | NULL | Name (English) |
| `value_ru` | varchar(255) | YES | NULL | Value (Russian) |
| `value_uz` | varchar(255) | YES | NULL | Value (Uzbek) |
| `value_en` | varchar(255) | YES | NULL | Value (English) |
| `is_filter` | int | YES | NULL | Is filterable flag |
| `status` | int | NO | 0 | Active/inactive |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Last updated |

**Primary Key:** `id`
**Indexes:** `category_id`

---

#### `filter_user`

Per-user filter toggle preferences (whether a filter is enabled for their view).

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `user_id` | int | YES | NULL | FK to `user.id` |
| `filter_id` | int | YES | NULL | FK to `filter.id` |
| `enabled` | int | YES | NULL | 1=enabled, 0=disabled |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Last updated |

**Primary Key:** `id`
**Indexes:** `user_id`, `filter_id`
**Foreign Keys:** `user_id` -> `user(id)` ON DELETE CASCADE, `filter_id` -> `filter(id)` ON DELETE CASCADE

---

### 3.3 Orders & Payments

---

#### `order`

Customer orders containing delivery, payment, and status tracking information.

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `user_id` | int | YES | NULL | FK to `user.id` |
| `inn` | varchar(255) | YES | NULL | Buyer INN (for legal entities) |
| `account` | varchar(255) | YES | NULL | Buyer bank account |
| `bank_id` | varchar(255) | YES | NULL | Buyer bank ID |
| `map_location` | varchar(255) | YES | NULL | Delivery map coordinates |
| `payment_id` | int | YES | NULL | FK to payment method |
| `delivery_id` | int | YES | NULL | FK to `delivery.id` |
| `logist_id` | int | YES | NULL | FK to `logist.id` |
| `tariff_id` | int | YES | NULL | Delivery tariff ID |
| `shop_id` | int | YES | NULL | FK to `shop.id` |
| `price` | double | YES | NULL | Total order price |
| `amount` | double | YES | NULL | Total quantity |
| `receiver` | int | NO | 0 | Receiver type flag |
| `name` | varchar(255) | YES | NULL | Receiver first name |
| `lastname` | varchar(255) | YES | NULL | Receiver last name |
| `email` | varchar(255) | YES | NULL | Receiver email |
| `phone` | text | YES | NULL | Receiver phone |
| `address` | text | YES | NULL | Delivery address |
| `comment` | text | YES | NULL | Order comment |
| `status` | int | NO | 0 | Order status |
| `status_payment` | int | NO | 0 | Payment status |
| `status_logist` | int | YES | NULL | Logistics status |
| `status_delivery` | int | YES | NULL | Delivery status |
| `status_review` | int | YES | NULL | Review requested flag |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Last updated |
| `bts_region_id` | int | YES | NULL | BTS region ID for delivery |
| `bts_city_id` | int | YES | NULL | BTS city ID for delivery |
| `delivery_cost` | decimal(10,2) | YES | NULL | Total delivery cost |
| `promocode_id` | int | YES | NULL | FK to `promocode.id` |
| `discount_amount` | decimal(10,2) | YES | 0.00 | Discount amount applied |

**Primary Key:** `id`
**Unique Keys:** `id`
**Indexes:** `user_id`, `payment_id`, `delivery_id`, `bts_region_id`, `bts_city_id`

---

#### `order_product`

Individual product line items within an order.

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `user_id` | int | YES | NULL | FK to `user.id` |
| `order_id` | int | YES | NULL | FK to `order.id` |
| `product_id` | int | YES | NULL | FK to `product.id` |
| `shop_id` | int | YES | NULL | FK to `shop.id` |
| `delivery_id` | int | YES | NULL | FK to `delivery.id` |
| `delivery_cost` | double | YES | NULL | Delivery cost per item |
| `amount` | double | YES | NULL | Quantity |
| `price` | double | YES | NULL | Unit price |
| `status` | int | YES | NULL | Line item status |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Last updated |
| `bts_id` | varchar(255) | YES | NULL | BTS tracking ID |
| `bts_status` | varchar(255) | YES | NULL | BTS delivery status |
| `bts_status_info` | text | YES | NULL | BTS status info |
| `bts_price` | decimal(10,2) | YES | NULL | BTS delivery cost |
| `address` | text | YES | NULL | Custom delivery address |
| `stock_id` | int | YES | NULL | FK to `stock.id` |
| `product_price` | decimal(15,2) | YES | NULL | Total price (unit * amount) |
| `sklad_product_id` | int | YES | NULL | Sklad product ID |

**Primary Key:** `id`
**Indexes:** `order_id`, `product_id`, `sklad_product_id`

---

#### `order_product_filter`

Selected filter/variant options for each order line item.

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `order_product_id` | int | YES | NULL | FK to `order_product.id` |
| `product_filter_id` | int | YES | NULL | FK to `product_filter.id` |

**Primary Key:** `id`
**Indexes:** `order_product_id`, `product_filter_id`

---

#### `order_product_refund`

Refund requests for specific order line items.

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `user_id` | int | YES | NULL | FK to `user.id` |
| `order_product_id` | int | YES | NULL | FK to `order_product.id` |
| `message` | text | YES | NULL | Refund reason |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Request date |

**Primary Key:** `id`
**Indexes:** `order_product_id`
**Foreign Keys:** `order_product_id` -> `order_product(id)` ON DELETE CASCADE

---

#### `order_receipt`

Fiscal receipt records linked to orders.

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `order_id` | int | YES | NULL | FK to `order.id` |
| `receipt_id` | varchar(255) | YES | NULL | External receipt ID |
| `status` | int | YES | NULL | Receipt status |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Created |

**Primary Key:** `id`
**Indexes:** `order_id`
**Foreign Keys:** `order_id` -> `order(id)` ON DELETE CASCADE

---

#### `transaction`

Internal payment transaction records.

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `user_id` | int | YES | NULL | FK to `user.id` |
| `order_id` | int | YES | NULL | FK to `order.id` |
| `shop_id` | int | YES | NULL | FK to `shop.id` |
| `type_transaction` | varchar(255) | YES | NULL | Transaction type |
| `type_payment` | varchar(255) | YES | NULL | Payment method type |
| `amount` | double | YES | NULL | Transaction amount |
| `status` | int | NO | 0 | Transaction status |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Created |

**Primary Key:** `id`

---

#### `transaction_payme`

Payme payment gateway transaction details.

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `user_id` | int | YES | NULL | FK to `user.id` |
| `order_id` | int | YES | NULL | FK to `order.id` |
| `transaction` | varchar(255) | YES | NULL | Payme transaction ID |
| `code` | varchar(255) | YES | NULL | Transaction code |
| `state` | varchar(255) | YES | NULL | Payme state |
| `amount` | double | YES | NULL | Amount in tiyin |
| `reason` | varchar(255) | YES | NULL | Cancel reason |
| `payme_time` | varchar(255) | YES | NULL | Payme timestamp |
| `cancel_time` | varchar(255) | YES | NULL | Cancellation timestamp |
| `create_time` | varchar(255) | YES | NULL | Creation timestamp |
| `perform_time` | varchar(255) | YES | NULL | Perform timestamp |
| `status` | int | NO | 0 | Transaction status |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Created |

**Primary Key:** `id`

---

#### `pay_keeper_transaction`

PayKeeper payment gateway transaction details.

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `transaction_id` | varchar(255) | YES | NULL | PayKeeper transaction ID |
| `order_id` | varchar(255) | YES | NULL | Order reference |
| `amount` | float | YES | NULL | Transaction amount |
| `status` | tinyint | YES | 0 | Transaction status |
| `currency` | int | YES | NULL | Currency code |
| `request` | text | YES | NULL | Raw request data |
| `response` | text | YES | NULL | Raw response data |
| `date` | int | YES | NULL | Unix timestamp |

**Primary Key:** `id`
**Unique Keys:** `id`

---

#### `promocode`

Promotional discount codes with flexible targeting and usage rules.

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `code` | varchar(50) | NO | - | Promo code string (UNIQUE) |
| `type` | int | NO | 1 | 1=Fixed Amount, 2=Percentage |
| `value` | decimal(10,2) | NO | - | Discount value |
| `min_order_amount` | decimal(10,2) | YES | 0.00 | Minimum order for eligibility |
| `max_discount_amount` | decimal(10,2) | YES | NULL | Max discount for percentage type |
| `start_date` | datetime | YES | NULL | Promo start date |
| `end_date` | datetime | YES | NULL | Promo end date |
| `usage_limit` | int | YES | NULL | Total usage limit |
| `usage_limit_per_user` | int | YES | 1 | Per-user usage limit |
| `status` | int | YES | 1 | 0=Inactive, 1=Active |
| `is_first_order` | tinyint(1) | YES | 0 | First order only flag |
| `category_id` | int | YES | NULL | FK to `category.id` (category restriction) |
| `product_id` | int | YES | NULL | FK to `product.id` (product restriction) |
| `user_id` | int | YES | NULL | FK to `user.id` (personal promo) |
| `title_ru` | varchar(255) | YES | NULL | Title (Russian) |
| `title_uz` | varchar(255) | YES | NULL | Title (Uzbek) |
| `title_en` | varchar(255) | YES | NULL | Title (English) |
| `description_ru` | text | YES | NULL | Description (Russian) |
| `description_uz` | text | YES | NULL | Description (Uzbek) |
| `description_en` | text | YES | NULL | Description (English) |
| `created_at` | datetime | YES | CURRENT_TIMESTAMP | Created |
| `updated_at` | datetime | YES | CURRENT_TIMESTAMP | Last updated |

**Primary Key:** `id`
**Unique Keys:** `code`

---

#### `payme_password`

Payme API authentication credentials storage.

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `password` | varchar(255) | NO | - | Hashed password |
| `date` | int | YES | NULL | Unix timestamp |

**Primary Key:** `id`

---

### 3.4 Shops & Sellers

---

#### `shop`

Vendor/shop profiles on the marketplace.

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `user_id` | int | YES | NULL | FK to `user.id` (shop owner) |
| `name_ru` | varchar(255) | YES | NULL | Name (Russian) |
| `name_uz` | varchar(255) | YES | NULL | Name (Uzbek) |
| `name_en` | varchar(255) | YES | NULL | Name (English) |
| `description_ru` | text | YES | NULL | Description (Russian) |
| `description_uz` | text | YES | NULL | Description (Uzbek) |
| `description_en` | text | YES | NULL | Description (English) |
| `map_location` | varchar(255) | YES | NULL | Map coordinates |
| `contact_user` | varchar(255) | YES | NULL | Contact person name |
| `contact_phone` | varchar(255) | YES | NULL | Contact phone |
| `status` | int | NO | 0 | Shop status |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Last updated |

**Primary Key:** `id`
**Unique Keys:** `id`

---

#### `shop_seller`

Legal entity details for marketplace shops (tax, banking, registration info).

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `shop_id` | int | YES | NULL | FK to `shop.id` |
| `inn` | varchar(255) | YES | NULL | Tax identification number |
| `account` | varchar(255) | YES | NULL | Bank account |
| `bank` | varchar(255) | YES | NULL | Bank name |
| `address_legal` | varchar(255) | YES | NULL | Legal address |
| `oked` | varchar(255) | YES | NULL | OKED code |
| `okohx` | varchar(255) | YES | NULL | OKOHX code |
| `mfo` | varchar(255) | YES | NULL | Bank MFO |
| `vat_reg_code` | varchar(32) | YES | NULL | VAT registration code |
| `status` | int | NO | 0 | Verification status |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Last updated |
| `director` | varchar(255) | YES | NULL | Director name |
| `director_pnfl` | varchar(255) | YES | NULL | Director PNFL |
| `organization` | varchar(255) | YES | NULL | Organization name |

**Primary Key:** `id`
**Indexes:** `shop_id`

---

#### `shop_advertising`

Advertising content managed by individual shops.

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `shop_id` | int | YES | NULL | FK to `shop.id` |
| `name_ru` | varchar(255) | YES | NULL | Title (Russian) |
| `name_uz` | varchar(255) | YES | NULL | Title (Uzbek) |
| `name_en` | varchar(255) | YES | NULL | Title (English) |
| `content_ru` | text | YES | NULL | Content (Russian) |
| `content_uz` | text | YES | NULL | Content (Uzbek) |
| `content_en` | text | YES | NULL | Content (English) |
| `link` | varchar(255) | YES | NULL | Advertisement URL |
| `status` | int | NO | 1 | Active/inactive |
| `sort` | int | NO | 0 | Display order |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Last updated |

**Primary Key:** `id`
**Indexes:** `shop_id`

---

#### `shop_document`

Legal and policy documents per shop.

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `shop_id` | int | YES | NULL | FK to `shop.id` |
| `name_ru` | varchar(255) | YES | NULL | Name (Russian) |
| `name_en` | varchar(255) | YES | NULL | Name (English) |
| `name_uz` | varchar(255) | YES | NULL | Name (Uzbek) |
| `content_ru` | text | YES | NULL | Content (Russian) |
| `content_en` | text | YES | NULL | Content (English) |
| `content_uz` | text | YES | NULL | Content (Uzbek) |
| `status` | int | NO | 1 | Active/inactive |
| `sort` | int | NO | 0 | Display order |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Last updated |

**Primary Key:** `id`
**Indexes:** `shop_id`

---

#### `shop_oferta`

Public offer (oferta) agreements per shop.

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `shop_id` | int | YES | NULL | FK to `shop.id` |
| `name_ru` | varchar(255) | YES | NULL | Name (Russian) |
| `name_en` | varchar(255) | YES | NULL | Name (English) |
| `name_uz` | varchar(255) | YES | NULL | Name (Uzbek) |
| `content_ru` | text | YES | NULL | Content (Russian) |
| `content_en` | text | YES | NULL | Content (English) |
| `content_uz` | text | YES | NULL | Content (Uzbek) |
| `status` | int | NO | 1 | Active/inactive |
| `sort` | int | NO | 0 | Display order |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Last updated |

**Primary Key:** `id`
**Indexes:** `shop_id`

---

#### `shop_support`

Support tickets submitted by shop owners to the platform.

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `shop_id` | int | YES | NULL | FK to `shop.id` |
| `user_id` | int | YES | NULL | FK to `user.id` |
| `theme` | varchar(255) | YES | NULL | Ticket subject |
| `message` | text | YES | NULL | Ticket message |
| `status` | int | NO | 0 | Ticket status |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Submitted |

**Primary Key:** `id`
**Indexes:** `shop_id`

---

#### `shop_logist`

Many-to-many link between shops and logistics providers.

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `shop_id` | int | YES | NULL | FK to `shop.id` |
| `logist_id` | int | YES | NULL | FK to `logist.id` |
| `status` | int | NO | 1 | Active/inactive |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Last updated |

**Primary Key:** `id`
**Indexes:** `shop_id`, `logist_id`
**Foreign Keys:** `shop_id` -> `shop(id)` ON DELETE CASCADE, `logist_id` -> `logist(id)` ON DELETE CASCADE

---

#### `stock`

Warehouse/stock locations per shop with BTS delivery integration.

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `shop_id` | int | YES | NULL | FK to `shop.id` |
| `name_ru` | varchar(255) | YES | NULL | Name (Russian) |
| `name_en` | varchar(255) | YES | NULL | Name (English) |
| `name_uz` | varchar(255) | YES | NULL | Name (Uzbek) |
| `description_ru` | text | YES | NULL | Description (Russian) |
| `description_en` | text | YES | NULL | Description (English) |
| `description_uz` | text | YES | NULL | Description (Uzbek) |
| `status` | int | NO | 1 | Active/inactive |
| `sort` | int | NO | 0 | Display order |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Last updated |
| `bts_region_id` | int | YES | NULL | BTS region ID |
| `bts_city_id` | int | YES | NULL | BTS city ID |
| `address` | text | YES | NULL | Warehouse address |

**Primary Key:** `id`
**Indexes:** `shop_id`, `bts_region_id`, `bts_city_id`

---

#### `seller_application`

Applications from prospective sellers to join the marketplace.

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `name` | varchar(255) | NO | - | Applicant name |
| `phone` | varchar(255) | NO | - | Phone number |
| `status` | int | YES | 1 | 1=pending, 2=approved, 3=rejected |
| `admin_notes` | text | YES | NULL | Admin notes |
| `date` | datetime | YES | CURRENT_TIMESTAMP | Submission date |

**Primary Key:** `id`
**Indexes:** `status`, `phone`, `date`

---

### 3.5 Delivery & Logistics

---

#### `delivery`

Available delivery method definitions with pricing.

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `name_ru` | varchar(255) | YES | NULL | Name (Russian) |
| `name_uz` | varchar(255) | YES | NULL | Name (Uzbek) |
| `name_en` | varchar(255) | YES | NULL | Name (English) |
| `description_ru` | text | YES | NULL | Description (Russian) |
| `description_uz` | text | YES | NULL | Description (Uzbek) |
| `description_en` | text | YES | NULL | Description (English) |
| `price` | double | YES | NULL | Base delivery price |
| `status` | int | NO | 0 | Active/inactive |
| `sort` | int | NO | 0 | Display order |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Last updated |

**Primary Key:** `id`
**Unique Keys:** `id`

---

#### `logist`

Logistics provider companies registered on the platform.

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `user_id` | int | YES | NULL | FK to `user.id` (logistics user) |
| `name_ru` | varchar(255) | YES | NULL | Name (Russian) |
| `name_uz` | varchar(255) | YES | NULL | Name (Uzbek) |
| `name_en` | varchar(255) | YES | NULL | Name (English) |
| `description_ru` | varchar(255) | YES | NULL | Description (Russian) |
| `description_uz` | varchar(255) | YES | NULL | Description (Uzbek) |
| `description_en` | varchar(255) | YES | NULL | Description (English) |
| `contact_user` | varchar(255) | YES | NULL | Contact person |
| `contact_phone` | varchar(255) | YES | NULL | Contact phone |
| `sort` | int | NO | 0 | Display order |
| `status` | int | NO | 1 | Active/inactive |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Last updated |

**Primary Key:** `id`
**Unique Keys:** `id`

---

#### `logist_region`

Geographic regions served by each logistics provider.

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `logist_id` | int | YES | NULL | FK to `logist.id` |
| `region_a_id` | int | YES | NULL | Origin region ID |
| `region_id` | int | YES | NULL | Destination region ID |
| `region_tree` | varchar(255) | YES | NULL | Destination region tree |
| `region_a_tree` | varchar(255) | YES | NULL | Origin region tree |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Last updated |

**Primary Key:** `id`
**Unique Keys:** `id`
**Indexes:** `logist_id`, `region_id`
**Foreign Keys:** `logist_id` -> `logist(id)` ON DELETE CASCADE, `region_id` -> `category(id)` ON DELETE CASCADE

---

#### `logist_region_price`

Pricing tiers per logistics region based on unit amounts.

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `logist_region_id` | int | YES | NULL | FK to `logist_region.id` |
| `unit_id` | int | YES | NULL | Measurement unit |
| `unit_amount` | double | YES | NULL | Unit amount threshold |
| `price` | double | YES | NULL | Price for this tier |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Last updated |

**Primary Key:** `id`
**Indexes:** `logist_region_id`
**Foreign Keys:** `logist_region_id` -> `logist_region(id)` ON DELETE CASCADE

---

#### `regions`

Geographic regions of Uzbekistan with BTS system mapping.

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `bts_id` | int | NO | - | BTS system region ID (UNIQUE) |
| `name_ru` | varchar(255) | NO | - | Name (Russian) |
| `name_uz` | varchar(255) | NO | - | Name (Uzbek) |
| `name_en` | varchar(255) | NO | - | Name (English) |
| `status` | int | YES | 1 | Active/inactive |
| `created_at` | timestamp | YES | CURRENT_TIMESTAMP | Created |
| `updated_at` | timestamp | YES | CURRENT_TIMESTAMP | Last updated |

**Primary Key:** `id`
**Unique Keys:** `bts_id`
**Indexes:** `bts_id`, `status`

---

#### `cities`

Cities within regions, mapped to the BTS delivery system.

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `bts_id` | int | NO | - | BTS system city ID (UNIQUE) |
| `region_id` | int | NO | - | FK to `regions.id` |
| `bts_region_id` | int | NO | - | BTS system region ID |
| `name_ru` | varchar(255) | NO | - | Name (Russian) |
| `name_uz` | varchar(255) | NO | - | Name (Uzbek) |
| `name_en` | varchar(255) | NO | - | Name (English) |
| `status` | int | YES | 1 | Active/inactive |
| `created_at` | timestamp | YES | CURRENT_TIMESTAMP | Created |
| `updated_at` | timestamp | YES | CURRENT_TIMESTAMP | Last updated |

**Primary Key:** `id`
**Unique Keys:** `bts_id`
**Indexes:** `bts_id`, `region_id`, `bts_region_id`, `status`
**Foreign Keys:** `region_id` -> `regions(id)` ON DELETE CASCADE

---

#### `office`

Physical office/pickup point locations.

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `office_id` | varchar(255) | YES | NULL | External office ID |
| `name` | varchar(255) | YES | NULL | Office name |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Last updated |

**Primary Key:** `id`
**Unique Keys:** `id`

---

### 3.6 E-Signature / DIDOX

---

#### `didox_document`

Master record for electronic documents submitted to the DIDOX e-signature platform.

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `name` | varchar(255) | NO | - | Document name |
| `document_type` | enum('invoice','arbitrary') | NO | 'invoice' | Document type |
| `didox_id` | varchar(100) | YES | NULL | DIDOX API document ID |
| `didox_status` | int | YES | NULL | DIDOX status (0-190) |
| `didox_data` | text | YES | NULL | JSON-encoded DIDOX API response |
| `didox_error_data` | text | YES | NULL | DIDOX API error data (JSON) |
| `didox_last_attempt` | datetime | YES | NULL | Last API submission attempt |
| `didox_created_at` | datetime | YES | NULL | DIDOX creation timestamp |
| `didox_signed_at` | datetime | YES | NULL | DIDOX signing timestamp |
| `created_by` | int | YES | NULL | FK to `user.id` (creator) |
| `to_user_id` | int | YES | NULL | FK to `user.id` (signer) |
| `status` | int | YES | 1 | 0=inactive, 1=active, 2=blocked |
| `created_at` | timestamp | YES | CURRENT_TIMESTAMP | Created |
| `updated_at` | timestamp | YES | CURRENT_TIMESTAMP | Last updated |
| `didox_user_key` | varchar(255) | YES | NULL | DIDOX user key |
| `didox_doc_type` | varchar(25) | NO | '002' | DIDOX doc type code |
| `order_id` | int | YES | NULL | FK to `order.id` |
| `pdf_paths` | text | YES | NULL | JSON with PDF file paths |

**Primary Key:** `id`
**Indexes:** `didox_id`, `didox_status`, `document_type`, `created_by`, `to_user_id`, `status`, `didox_created_at`, `created_at`, `order_id`
**Foreign Keys:** `created_by` -> `user(id)` ON DELETE SET NULL, `to_user_id` -> `user(id)` ON DELETE SET NULL

---

#### `didox_document_invoice`

Detailed invoice (factura) data for DIDOX electronic invoice submissions.

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `document_id` | int | NO | - | FK to `didox_document.id` |
| `invoice_number` | varchar(100) | YES | NULL | Invoice number |
| `invoice_date` | date | YES | NULL | Invoice date |
| `factura_type` | tinyint | YES | 0 | Invoice type (0-9) |
| `contract_number` | varchar(100) | YES | NULL | Contract number |
| `contract_date` | date | YES | NULL | Contract date |
| `contract_id` | varchar(100) | YES | NULL | my.soliq.uz contract ID |
| `lot_id` | varchar(100) | YES | NULL | Lot ID |
| `has_marking` | tinyint(1) | YES | 0 | Has marked products |
| `has_rent` | tinyint(1) | YES | 0 | Has rental services |
| `has_committent` | tinyint(1) | YES | 0 | Three-party invoice |
| `has_excise` | tinyint(1) | YES | 0 | Has excise tax |
| `has_vat` | tinyint(1) | YES | 1 | Has VAT |
| `has_lgota` | tinyint(1) | YES | 0 | Has tax exemptions |
| `buyer_tin` | varchar(20) | YES | NULL | Buyer TIN |
| `seller_tin` | varchar(20) | YES | NULL | Seller TIN |
| `seller_name` | varchar(255) | YES | NULL | Seller name |
| `seller_branch_code` | varchar(20) | YES | NULL | Seller branch code |
| `seller_branch_name` | varchar(255) | YES | NULL | Seller branch name |
| `seller_vat_reg_code` | varchar(50) | YES | NULL | Seller VAT reg code |
| `seller_account` | varchar(50) | YES | NULL | Seller bank account |
| `seller_bank_id` | varchar(10) | YES | NULL | Seller bank MFO |
| `seller_address` | text | YES | NULL | Seller address |
| `seller_director` | varchar(255) | YES | NULL | Seller director |
| `seller_accountant` | varchar(255) | YES | NULL | Seller accountant |
| `seller_vat_reg_status` | tinyint | YES | 20 | Seller VAT reg status |
| `buyer_name` | varchar(255) | YES | NULL | Buyer name |
| `buyer_branch_code` | varchar(20) | YES | NULL | Buyer branch code |
| `buyer_branch_name` | varchar(255) | YES | NULL | Buyer branch name |
| `buyer_vat_reg_code` | varchar(50) | YES | NULL | Buyer VAT reg code |
| `buyer_account` | varchar(50) | YES | NULL | Buyer bank account |
| `buyer_bank_id` | varchar(10) | YES | NULL | Buyer bank MFO |
| `buyer_address` | text | YES | NULL | Buyer address |
| `buyer_director` | varchar(255) | YES | NULL | Buyer director |
| `buyer_accountant` | varchar(255) | YES | NULL | Buyer accountant |
| `buyer_vat_reg_status` | tinyint | YES | 20 | Buyer VAT reg status |
| `old_factura_date` | date | YES | NULL | Previous invoice date |
| `old_factura_no` | varchar(100) | YES | NULL | Previous invoice number |
| `old_factura_id` | varchar(100) | YES | NULL | Previous invoice ID |
| `item_released_pinfl` | varchar(20) | YES | NULL | Released-by person PINFL |
| `item_released_fio` | varchar(255) | YES | NULL | Released-by person name |
| `investment_object_id` | varchar(100) | YES | NULL | Investment object ID |
| `investment_object_name` | varchar(255) | YES | NULL | Investment object name |
| `empowerment_no` | varchar(100) | YES | NULL | Power of attorney number |
| `empowerment_date_of_issue` | date | YES | NULL | Power of attorney date |
| `agent_fio` | varchar(255) | YES | NULL | Agent name |
| `agent_tin` | varchar(20) | YES | NULL | Agent PINFL |
| `foreign_country_id` | varchar(10) | YES | NULL | Foreign country ID |
| `foreign_company_name` | varchar(255) | YES | NULL | Foreign company name |
| `foreign_company_address` | text | YES | NULL | Foreign company address |
| `foreign_company_bank` | varchar(255) | YES | NULL | Foreign company bank |
| `foreign_company_account` | varchar(100) | YES | NULL | Foreign company account |
| `created_at` | timestamp | YES | CURRENT_TIMESTAMP | Created |
| `updated_at` | timestamp | YES | CURRENT_TIMESTAMP | Last updated |
| `total_sum` | decimal(15,2) | YES | NULL | Total delivery sum |
| `total_vat_sum` | decimal(15,2) | YES | NULL | Total VAT sum |
| `total_delivery_sum_with_vat` | decimal(15,2) | YES | NULL | Total with VAT |

**Primary Key:** `id`
**Indexes:** `document_id`, `buyer_tin`, `seller_tin`, `invoice_number`, `invoice_date`, `contract_number`, `factura_type`
**Foreign Keys:** `document_id` -> `didox_document(id)` ON DELETE CASCADE

---

#### `didox_document_arbitrary`

Arbitrary (non-invoice) document details for DIDOX submissions.

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `document_id` | int | NO | - | FK to `didox_document.id` |
| `document_no` | varchar(100) | YES | NULL | Document number |
| `document_date` | date | YES | NULL | Document date |
| `document_name` | varchar(255) | YES | NULL | Document name |
| `contract_no` | varchar(100) | YES | NULL | Contract number |
| `contract_date` | date | YES | NULL | Contract date |
| `seller_tin` | varchar(20) | NO | - | Seller TIN |
| `seller_name` | varchar(255) | NO | - | Seller name |
| `seller_address` | text | NO | - | Seller address |
| `seller_branch_code` | varchar(50) | YES | NULL | Seller branch code |
| `seller_branch_name` | varchar(255) | YES | NULL | Seller branch name |
| `buyer_tin` | varchar(20) | NO | - | Buyer TIN/PINFL |
| `buyer_name` | varchar(255) | NO | - | Buyer name |
| `buyer_address` | text | NO | - | Buyer address |
| `buyer_branch_code` | varchar(50) | YES | NULL | Buyer branch code |
| `buyer_branch_name` | varchar(255) | YES | NULL | Buyer branch name |
| `pdf_file_content` | longtext | YES | NULL | PDF content (base64) |
| `pdf_file_name` | varchar(255) | YES | NULL | PDF filename |
| `pdf_file_size` | int | YES | NULL | PDF file size (bytes) |
| `created_at` | datetime | YES | NULL | Created |
| `updated_at` | datetime | YES | NULL | Last updated |

**Primary Key:** `id`
**Indexes:** `document_id`, `document_no`, `contract_no`, `seller_tin`, `buyer_tin`
**Foreign Keys:** `document_id` -> `didox_document(id)` ON DELETE CASCADE

---

#### `didox_document_included_products`

Line items (products) included in DIDOX electronic documents.

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `document_id` | int | NO | - | FK to `didox_document.id` |
| `ord_no` | int | YES | 1 | Line item order number |
| `name` | varchar(255) | YES | NULL | Product name |
| `catalog_code` | varchar(17) | YES | NULL | IKPU code |
| `catalog_name` | varchar(255) | YES | NULL | IKPU name |
| `marks` | text | YES | NULL | Marking codes |
| `barcode` | varchar(100) | YES | NULL | Barcode |
| `package_code` | varchar(20) | YES | NULL | Package code |
| `package_name` | varchar(50) | YES | NULL | Package name |
| `count` | decimal(15,3) | YES | 1.000 | Quantity |
| `summa` | decimal(15,2) | YES | NULL | Unit price |
| `delivery_sum` | decimal(15,2) | YES | NULL | Delivery sum |
| `delivery_sum_with_vat` | decimal(15,2) | YES | NULL | Delivery sum with VAT |
| `vat_rate` | decimal(5,2) | YES | 12.00 | VAT rate |
| `vat_sum` | decimal(15,2) | YES | NULL | VAT amount |
| `without_vat` | tinyint(1) | YES | 0 | Without VAT flag |
| `excise_rate` | decimal(5,2) | YES | 0.00 | Excise rate |
| `excise_sum` | decimal(15,2) | YES | 0.00 | Excise amount |
| `without_excise` | tinyint(1) | YES | 1 | Without excise flag |
| `lgota_id` | varchar(20) | YES | NULL | Tax exemption code |
| `lgota_type` | tinyint | YES | NULL | Exemption type: 1=VAT, 2=turnover |
| `lgota_name` | varchar(255) | YES | NULL | Exemption name |
| `lgota_vat_sum` | decimal(15,2) | YES | 0.00 | Exemption VAT sum |
| `committent_name` | varchar(255) | YES | NULL | Committent name |
| `committent_tin` | varchar(20) | YES | NULL | Committent TIN |
| `committent_vat_reg_code` | varchar(50) | YES | NULL | Committent VAT reg code |
| `committent_vat_reg_status` | varchar(20) | YES | NULL | Committent VAT reg status |
| `warehouse_id` | varchar(50) | YES | NULL | Warehouse ID |
| `origin` | int | YES | 4 | Product origin |
| `measure_id` | varchar(20) | YES | NULL | Unused measure ID |
| `created_at` | timestamp | YES | CURRENT_TIMESTAMP | Created |
| `updated_at` | timestamp | YES | CURRENT_TIMESTAMP | Last updated |

**Primary Key:** `id`
**Indexes:** `document_id`, `ord_no`, `catalog_code`, `package_code`
**Foreign Keys:** `document_id` -> `didox_document(id)` ON DELETE CASCADE

---

### 3.7 Shopping Cart

---

#### `user_cart`

Shopping cart items per user.

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `user_id` | int | YES | NULL | FK to `user.id` |
| `product_id` | int | YES | NULL | FK to `product.id` |
| `delivery_id` | int | YES | NULL | FK to `delivery.id` |
| `delivery_cost` | double | YES | NULL | Delivery cost |
| `amount` | double | YES | NULL | Quantity |
| `price` | double | YES | NULL | Unit price |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Last updated |

**Primary Key:** `id`
**Indexes:** `user_id`, `product_id`

---

#### `user_cart_filter`

Selected filter/variant options per cart item.

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `user_cart_id` | int | YES | NULL | FK to `user_cart.id` |
| `product_filter_id` | int | YES | NULL | FK to `product_filter.id` |

**Primary Key:** `id`
**Indexes:** `user_cart_id`, `product_filter_id`

---

### 3.8 Social & Engagement

---

#### `user_favorite`

User product wishlists/favorites.

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `user_id` | int | YES | NULL | FK to `user.id` |
| `product_id` | int | YES | NULL | FK to `product.id` |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Added date |

**Primary Key:** `id`
**Indexes:** `user_id`, `product_id`

---

#### `user_shop_favorite`

User favorite shop subscriptions.

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `user_id` | int | YES | NULL | FK to `user.id` |
| `shop_id` | int | YES | NULL | FK to `shop.id` |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Added date |

**Primary Key:** `id`
**Indexes:** `user_id`, `shop_id`
**Foreign Keys:** `user_id` -> `user(id)` ON DELETE CASCADE, `shop_id` -> `shop(id)` ON DELETE CASCADE

---

#### `user_compare`

Products added to the comparison list by users.

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `user_id` | int | YES | NULL | FK to `user.id` |
| `product_id` | int | YES | NULL | FK to `product.id` |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Added date |

**Primary Key:** `id`
**Indexes:** `user_id`, `product_id`
**Foreign Keys:** `user_id` -> `user(id)` ON DELETE CASCADE, `product_id` -> `product(id)` ON DELETE CASCADE

---

#### `feedback`

General feedback/contact form submissions.

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `user_id` | varchar(255) | YES | NULL | User identifier |
| `shop_id` | int | YES | NULL | FK to `shop.id` |
| `name` | varchar(255) | YES | NULL | Submitter name |
| `email` | varchar(255) | YES | NULL | Submitter email |
| `message` | text | YES | NULL | Feedback message |
| `status` | int | NO | 0 | Processing status |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Submitted |

**Primary Key:** `id`

---

#### `question`

FAQ entries with question and answer in three languages.

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `question_ru` | text | YES | NULL | Question (Russian) |
| `question_en` | text | YES | NULL | Question (English) |
| `question_uz` | text | YES | NULL | Question (Uzbek) |
| `answer_ru` | text | YES | NULL | Answer (Russian) |
| `answer_en` | text | YES | NULL | Answer (English) |
| `answer_uz` | text | YES | NULL | Answer (Uzbek) |
| `status` | int | NO | 0 | Published/draft |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Last updated |

**Primary Key:** `id`

---

### 3.9 Content & CMS

---

#### `banner`

Homepage and promotional banner entries.

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `type` | varchar(255) | YES | NULL | Banner type |
| `description_ru` | text | YES | NULL | Content (Russian) |
| `description_en` | text | YES | NULL | Content (English) |
| `description_uz` | text | YES | NULL | Content (Uzbek) |
| `sort` | int | NO | 0 | Display order |
| `status` | int | NO | 1 | Active/inactive |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Last updated |
| `hash` | text | YES | NULL | Content hash |

**Primary Key:** `id`

---

#### `slider`

Image slider/carousel entries for the storefront.

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `type` | varchar(255) | YES | NULL | Slider type |
| `name_ru` | varchar(255) | YES | NULL | Title (Russian) |
| `name_uz` | varchar(255) | YES | NULL | Title (Uzbek) |
| `name_en` | varchar(255) | YES | NULL | Title (English) |
| `content_ru` | text | YES | NULL | Content (Russian) |
| `content_uz` | text | YES | NULL | Content (Uzbek) |
| `content_en` | text | YES | NULL | Content (English) |
| `link` | varchar(255) | YES | NULL | Destination URL |
| `status` | int | NO | 1 | Active/inactive |
| `sort` | int | NO | 0 | Display order |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Last updated |

**Primary Key:** `id`

---

#### `news`

News articles/blog posts, optionally tied to specific shops.

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `shop_id` | int | YES | NULL | FK to `shop.id` |
| `name_ru` | varchar(255) | YES | NULL | Title (Russian) |
| `name_uz` | varchar(255) | YES | NULL | Title (Uzbek) |
| `name_en` | varchar(255) | YES | NULL | Title (English) |
| `description_mini_ru` | text | YES | NULL | Excerpt (Russian) |
| `description_mini_uz` | text | YES | NULL | Excerpt (Uzbek) |
| `description_mini_en` | text | YES | NULL | Excerpt (English) |
| `description_ru` | text | YES | NULL | Full text (Russian) |
| `description_uz` | text | YES | NULL | Full text (Uzbek) |
| `description_en` | text | YES | NULL | Full text (English) |
| `views` | int | NO | 0 | View counter |
| `status` | int | NO | 0 | Published/draft |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Last updated |

**Primary Key:** `id`

---

#### `news_view`

News article view tracking by IP address.

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `news_id` | int | YES | NULL | FK to `news.id` |
| `ip` | varchar(255) | YES | NULL | Visitor IP |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | View timestamp |

**Primary Key:** `id`
**Indexes:** `news_id`

---

#### `advantages`

Platform advantages/features displayed on the marketing pages.

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `name_ru` | varchar(255) | YES | NULL | Title (Russian) |
| `name_uz` | varchar(255) | YES | NULL | Title (Uzbek) |
| `name_en` | varchar(255) | YES | NULL | Title (English) |
| `description_ru` | text | YES | NULL | Description (Russian) |
| `description_uz` | text | YES | NULL | Description (Uzbek) |
| `description_en` | text | YES | NULL | Description (English) |
| `status` | int | YES | NULL | Active/inactive |
| `sort` | int | YES | NULL | Display order |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Last updated |

**Primary Key:** `id`

---

#### `partners`

Partner company profiles displayed on the platform.

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `name_ru` | varchar(255) | YES | NULL | Name (Russian) |
| `name_uz` | varchar(255) | YES | NULL | Name (Uzbek) |
| `name_en` | varchar(255) | YES | NULL | Name (English) |
| `description_mini_ru` | text | YES | NULL | Short description (Russian) |
| `description_mini_uz` | text | YES | NULL | Short description (Uzbek) |
| `description_mini_en` | text | YES | NULL | Short description (English) |
| `description_ru` | text | YES | NULL | Full description (Russian) |
| `description_uz` | text | YES | NULL | Full description (Uzbek) |
| `description_en` | text | YES | NULL | Full description (English) |
| `status` | int | YES | NULL | Active/inactive |
| `sort` | int | YES | NULL | Display order |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Last updated |

**Primary Key:** `id`

---

#### `words`

Localized translation strings / dictionary for the platform UI.

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `name_ru` | text | YES | NULL | Text (Russian) |
| `name_uz` | text | YES | NULL | Text (Uzbek) |
| `name_en` | text | YES | NULL | Text (English) |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Last updated |

**Primary Key:** `id`

---

### 3.10 Communication

---

#### `message_room`

Chat rooms between platform users, typically regarding a product.

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `type` | varchar(255) | YES | NULL | Room type |
| `product_id` | int | YES | NULL | FK to `product.id` |
| `sender_id` | int | YES | NULL | FK to `user.id` (initiator) |
| `getter_id` | int | YES | NULL | FK to `user.id` (receiver) |
| `status` | int | NO | 0 | Room status |
| `status_archive` | int | NO | 0 | Archived flag |
| `status_important` | int | NO | 0 | Important flag |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Last updated |

**Primary Key:** `id`
**Unique Keys:** `id`
**Indexes:** `sender_id`, `getter_id`

---

#### `messages`

Individual messages within chat rooms.

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `message_room_id` | int | YES | NULL | FK to `message_room.id` |
| `user_id` | int | YES | NULL | FK to `user.id` (author) |
| `message` | text | YES | NULL | Message content |
| `status` | int | NO | 0 | Read status |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Sent timestamp |

**Primary Key:** `id`
**Indexes:** `message_room_id`, `user_id`

---

#### `notification`

Push/in-app notifications for users.

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `user_id` | int | YES | NULL | FK to `user.id` |
| `object_id` | int | YES | NULL | Related object ID |
| `type` | varchar(255) | YES | NULL | Notification type |
| `status` | int | NO | 0 | Read/unread |
| `message` | varchar(255) | YES | NULL | Notification text |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Created |

**Primary Key:** `id`

---

#### `support_chat`

Support chat threads between users and support staff.

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| `id` | bigint | NO | AUTO_INCREMENT | **PK** |
| `sender_id` | bigint | NO | - | FK to `user.id` (requester) |
| `getter_id` | bigint | NO | - | FK to `user.id` (support agent) |
| `subject` | text | NO | - | Chat subject |
| `status` | int | NO | - | Chat status |
| `created_at` | timestamp | NO | CURRENT_TIMESTAMP | Created |
| `updated_at` | timestamp | NO | - | Last updated |

**Primary Key:** `id`

---

### 3.11 System

---

#### `settings`

Global platform configuration key-value store.

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `type` | varchar(255) | YES | NULL | Setting key/type |
| `content` | varchar(255) | YES | NULL | Setting value |
| `main` | int | NO | 0 | Main/default flag |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Last updated |

**Primary Key:** `id`

---

#### `logs`

Application-level event and error logging.

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `category` | varchar(255) | NO | - | Log category |
| `level` | varchar(50) | YES | 'info' | Log level |
| `message` | text | YES | NULL | Log message |
| `data` | longtext | YES | NULL | Additional data payload |
| `created_at` | datetime | YES | CURRENT_TIMESTAMP | Log timestamp |

**Primary Key:** `id`
**Indexes:** `category`, `created_at`

---

#### `migration`

Yii2 framework database migration version tracking.

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| `version` | varchar(180) | NO | - | **PK** - Migration version string |
| `apply_time` | int | YES | NULL | Unix timestamp of application |

**Primary Key:** `version`

---

#### `sms_history`

SMS sending history with full request/response audit trail.

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `text` | text | YES | NULL | SMS text content |
| `phone` | varchar(255) | YES | NULL | Recipient phone |
| `status` | tinyint | YES | 0 | Send status |
| `request` | text | YES | NULL | API request payload |
| `response` | text | YES | NULL | API response payload |
| `date` | int | YES | NULL | Unix timestamp |

**Primary Key:** `id`
**Unique Keys:** `id`

---

#### `file`

Generic file attachments with polymorphic association via object_id + type.

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `object_id` | int | YES | NULL | Related entity ID |
| `type` | varchar(255) | YES | NULL | Entity type discriminator |
| `url` | varchar(255) | YES | NULL | File URL path |
| `main` | int | YES | NULL | Primary file flag |
| `sort` | int | YES | NULL | Display order |

**Primary Key:** `id`

---

#### `image`

Image assets with polymorphic association via object_id + type.

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `object_id` | int | YES | NULL | Related entity ID |
| `type` | varchar(255) | YES | NULL | Entity type discriminator |
| `photo` | varchar(255) | YES | NULL | Image file path |
| `number_image` | varchar(255) | YES | NULL | Image number/sequence |
| `main` | int | YES | NULL | Primary image flag |
| `web` | int | NO | 0 | Web-optimized flag |
| `hash` | text | YES | NULL | Image content hash |
| `sort` | int | NO | 0 | Display order |
| `status` | int | NO | 0 | Active/inactive |

**Primary Key:** `id`

---

## 4. Relationship Diagram

### 4.1 Core Entity Relationships (Explicit Foreign Keys)

```
                            +------------------+
                            |      user        |
                            |  (id) PK         |
                            +--------+---------+
                                     |
           +----------+---------+----+----+----------+----------+----------+
           |          |         |         |          |          |          |
           v          v         v         v          v          v          v
      sms_code   user_address user_card  order   user_cart  user_compare user_favorite
      (user_id)  (user_id)   (user_id)  (user_id) (user_id)  (user_id)   (user_id)
                                          |
                                          |
                      +-------------------+-------------------+
                      |                   |                   |
                      v                   v                   v
                 order_product     order_receipt      didox_document
                 (order_id)        (order_id)         (order_id)
                      |
           +----------+----------+
           |                     |
           v                     v
   order_product_filter  order_product_refund
   (order_product_id)    (order_product_id)
```

### 4.2 Product Domain Relationships

```
      category (id) ──1:N──> product (category_id)
      category (id) ──1:N──> category_brand (category_id)
      category (id) ──1:N──> category_filter (category_id)
      category (id) ──1:N──> filter (category_id)
      category (id) ──1:N──> product_type (category_id)

      product (id) ──1:N──> product_color (product_id)
      product (id) ──1:N──> product_filter (product_id)
      product (id) ──1:N──> product_product_type (product_id)
      product (id) ──1:N──> product_property (product_id)
      product (id) ──1:N──> product_rating (product_id)
      product (id) ──1:N──> product_review (product_id)
      product (id) ──1:N──> product_view (product_id)
      product (id) ──1:N──> product_view_recently (product_id)
      product (id) ──1:N──> product_office (product_id)
      product (id) ──1:N──> user_favorite (product_id)
      product (id) ──1:N──> user_compare (product_id)
      product (id) ──1:N──> user_cart (product_id)
      product (id) ──1:N──> order_product (product_id)

      color (id) ──1:N──> product_color (color_id)
      office (id) ──1:N──> product_office (office_id)

      product_type (id) ──1:N──> product_type_value (product_type_id)
      product_type (id) ──1:N──> product_product_type (product_type_id)
      product_type_value (id) ──1:N──> product_product_type (product_type_value_id)

      filter (id) ──1:N──> filter_user (filter_id)

      ikpu (code) ──1:N──> ikpu (parent_code)  [self-referencing]
```

### 4.3 Shop & Logistics Domain Relationships

```
      user (id) ──1:N──> shop (user_id)

      shop (id) ──1:N──> shop_seller (shop_id)
      shop (id) ──1:N──> shop_advertising (shop_id)
      shop (id) ──1:N──> shop_document (shop_id)
      shop (id) ──1:N──> shop_oferta (shop_id)
      shop (id) ──1:N──> shop_support (shop_id)
      shop (id) ──1:N──> shop_logist (shop_id)
      shop (id) ──1:N──> stock (shop_id)
      shop (id) ──1:N──> product (shop_id)
      shop (id) ──1:N──> order (shop_id)
      shop (id) ──1:N──> user_shop_favorite (shop_id)
      shop (id) ──1:N──> news (shop_id)

      logist (id) ──1:N──> logist_region (logist_id)
      logist (id) ──1:N──> shop_logist (logist_id)

      logist_region (id) ──1:N──> logist_region_price (logist_region_id)

      regions (id) ──1:N──> cities (region_id)
```

### 4.4 Order & Payment Flow

```
      user (id) ──1:N──> order (user_id)
      order (id) ──1:N──> order_product (order_id)
      order (id) ──1:N──> order_receipt (order_id)
      order (id) ──1:N──> transaction (order_id)
      order (id) ──1:N──> transaction_payme (order_id)
      order (id) ──1:N──> didox_document (order_id)

      product (id) ──1:N──> order_product (product_id)
      order_product (id) ──1:N──> order_product_filter (order_product_id)
      order_product (id) ──1:N──> order_product_refund (order_product_id)

      promocode (id) ──1:N──> order (promocode_id)
```

### 4.5 DIDOX E-Signature Domain

```
      didox_document (id) ──1:1──> didox_document_invoice (document_id)
      didox_document (id) ──1:1──> didox_document_arbitrary (document_id)
      didox_document (id) ──1:N──> didox_document_included_products (document_id)

      user (id) ──1:N──> didox_document (created_by)
      user (id) ──1:N──> didox_document (to_user_id)
      order (id) ──1:N──> didox_document (order_id)
```

### 4.6 Product Moderation Pipeline

```
      pending_products (id) ──1:N──> product_moderation_comments (submission_id)
      pending_products (id) ──1:N──> product_sync_log (submission_id)
      pending_products (approved_product_id) ──1:1──> product (id)
```

### 4.7 Communication Domain

```
      message_room (id) ──1:N──> messages (message_room_id)

      user (id) ──1:N──> messages (user_id)
      user (id) ──1:N──> message_room (sender_id)
      user (id) ──1:N──> message_room (getter_id)
      user (id) ──1:N──> notification (user_id)
      user (id) ──1:N──> support_chat (sender_id)
      user (id) ──1:N──> support_chat (getter_id)

      product (id) ──1:N──> message_room (product_id)
```

### 4.8 Full Core Relationship Summary (Text Format)

```
user (id)                    ──1:N──  order (user_id)
user (id)                    ──1:N──  shop (user_id)
user (id)                    ──1:N──  user_address (user_id)
user (id)                    ──1:N──  user_card (user_id)
user (id)                    ──1:N──  user_cart (user_id)
user (id)                    ──1:N──  user_favorite (user_id)
user (id)                    ──1:N──  user_shop_favorite (user_id)
user (id)                    ──1:N──  user_compare (user_id)
user (id)                    ──1:N──  sms_code (user_id)
user (id)                    ──1:N──  filter_user (user_id)
user (id)                    ──1:N──  product_rating (user_id)
user (id)                    ──1:N──  product_review (user_id)
user (id)                    ──1:N──  transaction (user_id)
user (id)                    ──1:N──  transaction_payme (user_id)
user (id)                    ──1:N──  notification (user_id)
user (id)                    ──1:N──  messages (user_id)
user (id)                    ──1:N──  message_room (sender_id)
user (id)                    ──1:N──  message_room (getter_id)
user (id)                    ──1:N──  didox_document (created_by)
user (id)                    ──1:N──  didox_document (to_user_id)
user (id)                    ──1:N──  moderator_access (moderator_id)
user (id)                    ──1:N──  moderator_access (user_id)
user (id)                    ──1:N──  logist (user_id)

order (id)                   ──1:N──  order_product (order_id)
order (id)                   ──1:N──  order_receipt (order_id)
order (id)                   ──1:N──  transaction (order_id)
order (id)                   ──1:N──  transaction_payme (order_id)
order (id)                   ──1:N──  didox_document (order_id)

order_product (id)           ──1:N──  order_product_filter (order_product_id)
order_product (id)           ──1:N──  order_product_refund (order_product_id)

product (id)                 ──1:N──  order_product (product_id)
product (id)                 ──1:N──  product_color (product_id)
product (id)                 ──1:N──  product_filter (product_id)
product (id)                 ──1:N──  product_product_type (product_id)
product (id)                 ──1:N──  product_property (product_id)
product (id)                 ──1:N──  product_rating (product_id)
product (id)                 ──1:N──  product_review (product_id)
product (id)                 ──1:N──  product_view (product_id)
product (id)                 ──1:N──  product_view_recently (product_id)
product (id)                 ──1:N──  product_office (product_id)
product (id)                 ──1:N──  user_favorite (product_id)
product (id)                 ──1:N──  user_compare (product_id)
product (id)                 ──1:N──  user_cart (product_id)
product (id)                 ──1:N──  message_room (product_id)

shop (id)                    ──1:N──  shop_seller (shop_id)
shop (id)                    ──1:N──  shop_advertising (shop_id)
shop (id)                    ──1:N──  shop_document (shop_id)
shop (id)                    ──1:N──  shop_oferta (shop_id)
shop (id)                    ──1:N──  shop_support (shop_id)
shop (id)                    ──1:N──  shop_logist (shop_id)
shop (id)                    ──1:N──  stock (shop_id)
shop (id)                    ──1:N──  product (shop_id)
shop (id)                    ──1:N──  order (shop_id)
shop (id)                    ──1:N──  user_shop_favorite (shop_id)
shop (id)                    ──1:N──  news (shop_id)

category (id)                ──1:N──  product (category_id)
category (id)                ──1:N──  category_brand (category_id)
category (id)                ──1:N──  category_filter (category_id)
category (id)                ──1:N──  filter (category_id)
category (id)                ──1:N──  product_type (category_id)
category (parent_id)         ──N:1──  category (id)          [self-referencing]

color (id)                   ──1:N──  product_color (color_id)
office (id)                  ──1:N──  product_office (office_id)
delivery (id)                ──1:N──  order (delivery_id)
delivery (id)                ──1:N──  user_cart (delivery_id)

logist (id)                  ──1:N──  logist_region (logist_id)
logist (id)                  ──1:N──  shop_logist (logist_id)
logist_region (id)           ──1:N──  logist_region_price (logist_region_id)

regions (id)                 ──1:N──  cities (region_id)
filter (id)                  ──1:N──  filter_user (filter_id)
filter (id)                  ──1:N──  category_filter (filter_id)
filter (parent_id)           ──N:1──  filter (id)            [self-referencing]

product_type (id)            ──1:N──  product_type_value (product_type_id)
product_type (id)            ──1:N──  product_product_type (product_type_id)
product_type_value (id)      ──1:N──  product_product_type (product_type_value_id)

product_filter (id)          ──1:N──  order_product_filter (product_filter_id)
product_filter (id)          ──1:N──  user_cart_filter (product_filter_id)

user_cart (id)               ──1:N──  user_cart_filter (user_cart_id)

didox_document (id)          ──1:1──  didox_document_invoice (document_id)
didox_document (id)          ──1:1──  didox_document_arbitrary (document_id)
didox_document (id)          ──1:N──  didox_document_included_products (document_id)

ikpu (code)                  ──1:N──  ikpu (parent_code)     [self-referencing]

pending_products (id)        ──1:N──  product_moderation_comments (submission_id)
pending_products (id)        ──1:N──  product_sync_log (submission_id)

news (id)                    ──1:N──  news_view (news_id)
message_room (id)            ──1:N──  messages (message_room_id)

promocode (id)               ──1:N──  order (promocode_id)
```

---

*Generated: 2026-02-10 | Source: `app (2).sql` dump file | 82 tables documented*
