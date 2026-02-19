# app — Database Schema Documentation

**Database:** `app`
**Engine:** MySQL 8.0
**Character Set:** utf8mb4 (utf8mb4_0900_ai_ci)
**Total Tables:** 82

---

## Table of Contents

1. [Overview](#1-overview)
2. [Entity Groups](#2-entity-groups)
3. [Table Definitions](#3-table-definitions)
   - 3.1 [Users](#31-users)
   - 3.2 [Products](#32-products)
   - 3.3 [Orders](#33-orders)
   - 3.4 [Shops](#34-shops)
   - 3.5 [Delivery & Logistics](#35-delivery--logistics)
   - 3.6 [Didox (E-Invoicing)](#36-didox-e-invoicing)
   - 3.7 [Cart & Favorites](#37-cart--favorites)
   - 3.8 [Content & CMS](#38-content--cms)
   - 3.9 [Communication](#39-communication)
   - 3.10 [System & Payments](#310-system--payments)
4. [Relationship Diagram](#4-relationship-diagram)

---

## 1. Overview

app is a multi-vendor e-commerce marketplace platform serving the Uzbekistan market. The database supports trilingual content (Russian, Uzbek, English), multiple payment methods (Payme, PayKeeper), BTS logistics integration, DIDOX electronic invoicing (Uzbekistan tax system), warehouse/stock management, and a full-featured product catalog with filters, types, colors, and reviews.

Key characteristics:
- **Multi-language:** Most content tables have `name_ru`, `name_uz`, `name_en` columns
- **Soft timestamps:** Tables use `date` (timestamp) or `created_at`/`updated_at` pairs
- **Status flags:** Integer-based status columns (0=inactive, 1=active, 2=blocked, etc.)
- **BTS integration:** Delivery tracking via BTS system IDs on orders, users, stocks, and cities
- **DIDOX integration:** Electronic invoice/document management for Uzbekistan tax compliance

---

## 2. Entity Groups

| Group | Tables | Description |
|-------|--------|-------------|
| **Users** | `user`, `user_address`, `user_card`, `user_compare`, `user_favorite`, `user_shop_favorite`, `sms_code`, `sms_history`, `seller_application` | User accounts, addresses, payment cards, preferences, SMS auth |
| **Products** | `product`, `product_color`, `product_filter`, `product_office`, `product_product_type`, `product_property`, `product_rating`, `product_request`, `product_review`, `product_sync_log`, `product_moderation_comments`, `product_type`, `product_type_value`, `product_view`, `product_view_recently`, `pending_products`, `category`, `category_brand`, `category_filter`, `color`, `filter`, `filter_user`, `ikpu`, `image`, `file`, `office` | Product catalog, categories, filters, types, reviews, images |
| **Orders** | `order`, `order_product`, `order_product_filter`, `order_product_refund`, `order_receipt`, `promocode` | Order lifecycle, line items, refunds, receipts, promo codes |
| **Shops** | `shop`, `shop_advertising`, `shop_document`, `shop_logist`, `shop_oferta`, `shop_seller`, `shop_support`, `stock` | Vendor shops, legal docs, advertising, warehouses |
| **Delivery & Logistics** | `delivery`, `logist`, `logist_region`, `logist_region_price`, `regions`, `cities` | Delivery methods, logistics providers, regional pricing |
| **Didox (E-Invoicing)** | `didox_document`, `didox_document_arbitrary`, `didox_document_included_products`, `didox_document_invoice` | DIDOX electronic invoicing for Uzbekistan tax compliance |
| **Cart & Favorites** | `user_cart`, `user_cart_filter`, `user_compare`, `user_favorite`, `user_shop_favorite` | Shopping cart, product comparison, wishlists |
| **Content & CMS** | `banner`, `slider`, `news`, `news_view`, `advantages`, `partners`, `question`, `words`, `settings` | CMS pages, banners, sliders, FAQ, localization |
| **Communication** | `messages`, `message_room`, `notification`, `feedback`, `support_chat` | Messaging, notifications, support tickets |
| **System & Payments** | `transaction`, `transaction_payme`, `pay_keeper_transaction`, `payme_password`, `migration`, `moderator_access`, `moderator_url`, `logs` | Payments, transactions, migrations, access control, logging |

---

## 3. Table Definitions

### 3.1 Users

#### `user`
User accounts for buyers, sellers, moderators, and admins.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `role` | int | YES | NULL | User role identifier |
| `type` | varchar(255) | YES | NULL | User type (fiz/yur) |
| `token` | varchar(255) | YES | NULL | Authentication token |
| `facebook_id` | varchar(255) | YES | NULL | Facebook OAuth ID |
| `google_id` | varchar(255) | YES | NULL | Google OAuth ID |
| `vk_id` | varchar(255) | YES | NULL | VKontakte OAuth ID |
| `device_id` | varchar(255) | YES | NULL | Mobile device ID |
| `shop_id` | int | YES | NULL | Associated shop |
| `balance` | double | NO | 0 | Wallet balance |
| `name` | varchar(255) | YES | NULL | First name |
| `lastname` | varchar(255) | YES | NULL | Last name |
| `middlename` | varchar(255) | YES | NULL | Middle name |
| `phone` | varchar(255) | YES | NULL | Phone number |
| `email` | varchar(255) | YES | NULL | Email address |
| `login` | varchar(255) | YES | NULL | Login (unique) |
| `password` | varchar(255) | YES | NULL | Hashed password |
| `gender` | int | YES | NULL | Gender |
| `birthday` | varchar(255) | YES | NULL | Date of birth |
| `last_address` | text | YES | NULL | Last used address |
| `inn` | varchar(255) | YES | NULL | Tax ID (INN) |
| `account` | varchar(255) | YES | NULL | Bank account |
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
| `eimzo_didox_token` | varchar(255) | YES | NULL | DIDOX auth token |
| `eimzo_last_login` | timestamp | YES | NULL | Last E-IMZO login |
| `eimzo_certificate_info` | text | YES | NULL | E-IMZO certificate data |
| `phone_code` | varchar(10) | YES | NULL | Phone country code |
| `sms_live` | int | YES | NULL | SMS code TTL |
| `organization_name` | varchar(255) | YES | NULL | Organization name (legal entities) |
| `bts_region_id` | int | YES | NULL | BTS region ID |
| `bts_city_id` | int | YES | NULL | BTS city ID |

- **PK:** `id`
- **Unique:** `login`
- **Indexes:** `bts_region_id`, `bts_city_id`

---

#### `user_address`
Saved delivery addresses for users.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `user_id` | int | YES | NULL | FK to `user.id` |
| `address` | text | YES | NULL | Address text |
| `status` | int | NO | 1 | Active flag |
| `sort` | int | NO | 0 | Sort order |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Last updated |

- **PK:** `id`
- **Indexes:** `user_id`

---

#### `user_card`
Saved payment cards for users.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `user_id` | int | YES | NULL | FK to `user.id` |
| `card_type_id` | int | YES | NULL | Card type (Visa, Humo, etc.) |
| `card_token` | text | YES | NULL | Tokenized card |
| `card_number` | varchar(255) | YES | NULL | Masked card number |
| `card_expire` | varchar(255) | YES | NULL | Expiry date |
| `card_phone_number` | varchar(255) | YES | NULL | Phone linked to card |
| `status` | int | NO | 1 | Active flag |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Last updated |

- **PK:** `id`
- **Indexes:** `user_id`

---

#### `sms_code`
SMS verification codes for authentication.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `user_id` | int | YES | NULL | FK to `user.id` |
| `token` | varchar(255) | NO | — | Session token |
| `phone` | varchar(255) | YES | NULL | Phone number |
| `code` | varchar(255) | YES | NULL | Verification code |
| `sms_expire` | int | YES | NULL | Expiry timestamp |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Created |

- **PK:** `id`

---

#### `sms_history`
Log of all sent SMS messages.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `text` | text | YES | NULL | SMS content |
| `phone` | varchar(255) | YES | NULL | Recipient phone |
| `status` | tinyint | YES | 0 | Delivery status |
| `request` | text | YES | NULL | API request payload |
| `response` | text | YES | NULL | API response payload |
| `date` | int | YES | NULL | Unix timestamp |

- **PK:** `id`

---

#### `seller_application`
Applications from users requesting to become sellers.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `name` | varchar(255) | NO | — | Applicant name |
| `phone` | varchar(255) | NO | — | Phone number |
| `status` | int | YES | 1 | 1=pending, 2=approved, 3=rejected |
| `admin_notes` | text | YES | NULL | Admin notes |
| `date` | datetime | YES | CURRENT_TIMESTAMP | Submission date |

- **PK:** `id`
- **Indexes:** `status`, `phone`, `date`

---

### 3.2 Products

#### `product`
Core product catalog table.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `user_id` | int | YES | NULL | Seller user FK |
| `category_id` | int | YES | NULL | FK to `category.id` |
| `stock_id` | int | YES | NULL | FK to `stock.id` |
| `shop_id` | int | YES | NULL | FK to `shop.id` |
| `category_tree` | varchar(255) | YES | NULL | Category path |
| `brand_id` | int | YES | NULL | FK to `category_brand.id` |
| `region_id` | int | YES | NULL | Region |
| `currency_id` | int | YES | NULL | Currency |
| `unit_id` | int | YES | NULL | Unit of measure |
| `color_id` | int | YES | NULL | FK to `color.id` |
| `delivery_id` | int | YES | NULL | Default delivery method |
| `tag_id` | int | YES | NULL | Tag |
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
| `weight` | double | YES | NULL | Weight |
| `height` | double | YES | NULL | Height |
| `width` | double | YES | NULL | Width |
| `length` | double | YES | NULL | Length |
| `discount` | double | YES | NULL | Discount percentage |
| `amount` | double | YES | NULL | Available stock quantity |
| `credit_label` | varchar(255) | YES | NULL | Credit/installment label |
| `views` | int | NO | 0 | View counter |
| `rating` | double | NO | 0 | Average rating |
| `status` | int | NO | 2 | 0=inactive, 1=active, 2=moderation |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Last updated |
| `count_price` | int | YES | NULL | Price tier count |
| `count_price1` | int | YES | NULL | Price tier 1 count |
| `count_price2` | int | YES | NULL | Price tier 2 count |
| `discount_small_count` | int | YES | NULL | Small wholesale discount count |
| `discount_big_count` | int | YES | NULL | Big wholesale discount count |
| `token_key` | varchar(255) | YES | NULL | External token |
| `billz_id` | varchar(255) | YES | NULL | Billz integration ID |
| `sku` | varchar(255) | YES | NULL | SKU code |
| `barcode` | varchar(255) | YES | NULL | Barcode |
| `qty` | varchar(255) | YES | NULL | Quantity string |
| `button_id` | bigint | NO | 0 | UI button type |
| `qty_small_wholesale` | int | YES | NULL | Small wholesale quantity |
| `qty_big_wholesale` | int | YES | NULL | Big wholesale quantity |
| `ikpu_code` | varchar(17) | YES | NULL | IKPU code (tax classification) |
| `ikpu_name` | varchar(500) | YES | NULL | IKPU name (cached) |
| `package_code` | varchar(50) | YES | NULL | Package code |
| `package_name` | varchar(100) | YES | NULL | Package name |
| `sklad_product_id` | int | YES | NULL | External warehouse product ID |
| `sync_status` | tinyint | YES | 0 | 0=Pending, 1=Synced, 2=Failed |

- **PK:** `id`
- **Indexes:** `user_id`, `category_id`, `sklad_product_id`, `sync_status`

---

#### `category`
Product categories in a hierarchical tree structure.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `parent_id` | int | NO | 0 | Parent category (0 = root) |
| `type` | varchar(255) | YES | NULL | Category type |
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
| `sort` | int | NO | 0 | Sort order |
| `status` | int | NO | 0 | Active flag |
| `main` | int | NO | 0 | Featured on main page |
| `is_filter` | int | YES | NULL | Has filters |
| `popular` | int | YES | NULL | Popular flag |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Last updated |

- **PK:** `id`
- **Unique:** `id`

---

#### `category_brand`
Brands associated with categories.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `category_id` | int | YES | NULL | FK to `category.id` |
| `category_tree` | varchar(255) | YES | NULL | Category path |
| `name_ru` | varchar(255) | YES | NULL | Brand name (Russian) |
| `name_en` | varchar(255) | YES | NULL | Brand name (English) |
| `name_uz` | varchar(255) | YES | NULL | Brand name (Uzbek) |
| `description_ru` | text | YES | NULL | Description (Russian) |
| `description_en` | text | YES | NULL | Description (English) |
| `description_uz` | text | YES | NULL | Description (Uzbek) |
| `status` | int | NO | 1 | Active flag |
| `sort` | int | NO | 0 | Sort order |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Last updated |

- **PK:** `id`
- **Indexes:** `category_id`

---

#### `category_filter`
Filter values assigned to categories.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `category_id` | int | YES | NULL | FK to `category.id` |
| `filter_id` | int | YES | NULL | FK to `filter.id` |
| `value_id` | int | YES | NULL | Value ID |
| `value_ru` | varchar(255) | YES | NULL | Value (Russian) |
| `value_en` | varchar(255) | YES | NULL | Value (English) |
| `value_uz` | varchar(255) | YES | NULL | Value (Uzbek) |

- **PK:** `id`
- **Indexes:** `category_id`
- **FK:** `category_id` REFERENCES `category(id)` ON DELETE CASCADE

---

#### `color`
Available product colors.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `name_ru` | varchar(255) | YES | NULL | Name (Russian) |
| `name_en` | varchar(255) | YES | NULL | Name (English) |
| `name_uz` | varchar(255) | YES | NULL | Name (Uzbek) |
| `color` | varchar(255) | YES | NULL | Hex color code |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Last updated |

- **PK:** `id`
- **Unique:** `id`

---

#### `filter`
Product filter definitions (hierarchical).

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `parent_id` | int | NO | 0 | Parent filter (0 = root) |
| `category_id` | int | YES | NULL | FK to `category.id` |
| `sub_category_id` | int | YES | NULL | Subcategory |
| `category_tree` | varchar(255) | YES | NULL | Category path |
| `type` | varchar(255) | YES | NULL | Filter type |
| `name_ru` | varchar(255) | YES | NULL | Name (Russian) |
| `name_uz` | varchar(255) | YES | NULL | Name (Uzbek) |
| `name_en` | varchar(255) | YES | NULL | Name (English) |
| `value_ru` | varchar(255) | YES | NULL | Value (Russian) |
| `value_uz` | varchar(255) | YES | NULL | Value (Uzbek) |
| `value_en` | varchar(255) | YES | NULL | Value (English) |
| `is_filter` | int | YES | NULL | Is active filter |
| `status` | int | NO | 0 | Active flag |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Last updated |

- **PK:** `id`
- **Indexes:** `category_id`

---

#### `filter_user`
User-specific filter preferences.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `user_id` | int | YES | NULL | FK to `user.id` |
| `filter_id` | int | YES | NULL | FK to `filter.id` |
| `enabled` | int | YES | NULL | Enabled flag |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Last updated |

- **PK:** `id`
- **FK:** `user_id` REFERENCES `user(id)` ON DELETE CASCADE
- **FK:** `filter_id` REFERENCES `filter(id)` ON DELETE CASCADE

---

#### `product_color`
Product-to-color many-to-many junction.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `product_id` | int | YES | NULL | FK to `product.id` |
| `color_id` | int | YES | NULL | FK to `color.id` |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Last updated |
| `status` | int | YES | NULL | Active flag |

- **PK:** `id`
- **FK:** `product_id` REFERENCES `product(id)` ON DELETE CASCADE
- **FK:** `color_id` REFERENCES `color(id)` ON DELETE CASCADE

---

#### `product_filter`
Product-to-filter value assignments.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `product_id` | int | YES | NULL | FK to `product.id` |
| `filter_id` | int | YES | NULL | FK to `filter.id` |
| `value_id` | int | YES | NULL | Filter value ID |
| `value_ru` | varchar(255) | YES | NULL | Value (Russian) |
| `value_en` | varchar(255) | YES | NULL | Value (English) |
| `value_uz` | varchar(255) | YES | NULL | Value (Uzbek) |

- **PK:** `id`
- **Indexes:** `product_id`

---

#### `product_type`
Dynamic product attribute type definitions per category.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `category_id` | int | YES | NULL | FK to `category.id` |
| `name_ru` | varchar(255) | YES | NULL | Name (Russian) |
| `name_en` | varchar(255) | YES | NULL | Name (English) |
| `name_uz` | varchar(255) | YES | NULL | Name (Uzbek) |
| `type` | varchar(255) | NO | — | input, select, checkbox, range |
| `description_ru` | text | YES | NULL | Description (Russian) |
| `description_en` | text | YES | NULL | Description (English) |
| `description_uz` | text | YES | NULL | Description (Uzbek) |
| `status` | int | NO | 1 | 0=inactive, 1=active |
| `sort` | int | NO | 0 | Sort order |
| `date` | timestamp | YES | CURRENT_TIMESTAMP | Created |

- **PK:** `id`
- **FK:** `category_id` REFERENCES `category(id)` ON DELETE CASCADE

---

#### `product_type_value`
Predefined values for product type attributes.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `product_type_id` | int | NO | — | FK to `product_type.id` |
| `value_ru` | varchar(255) | NO | — | Value (Russian) |
| `value_en` | varchar(255) | YES | NULL | Value (English) |
| `value_uz` | varchar(255) | YES | NULL | Value (Uzbek) |
| `display_value` | varchar(255) | YES | NULL | Formatted display value |
| `description_ru` | text | YES | NULL | Description (Russian) |
| `description_en` | text | YES | NULL | Description (English) |
| `description_uz` | text | YES | NULL | Description (Uzbek) |
| `status` | int | NO | 1 | Active flag |
| `sort` | int | NO | 0 | Sort order |
| `date` | timestamp | YES | CURRENT_TIMESTAMP | Created |

- **PK:** `id`
- **FK:** `product_type_id` REFERENCES `product_type(id)` ON DELETE CASCADE

---

#### `product_product_type`
Product-to-product-type value assignments (EAV pattern).

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `product_id` | int | NO | — | FK to `product.id` |
| `product_type_id` | int | NO | — | FK to `product_type.id` |
| `product_type_value_id` | int | YES | NULL | FK to `product_type_value.id` (NULL for custom) |
| `custom_value` | varchar(255) | YES | NULL | Custom value for input types |
| `date` | timestamp | YES | CURRENT_TIMESTAMP | Created |

- **PK:** `id`
- **Unique:** (`product_id`, `product_type_id`, `product_type_value_id`)
- **FK:** `product_id` REFERENCES `product(id)` ON DELETE CASCADE
- **FK:** `product_type_id` REFERENCES `product_type(id)` ON DELETE CASCADE
- **FK:** `product_type_value_id` REFERENCES `product_type_value(id)` ON DELETE CASCADE

---

#### `product_property`
Free-form key-value product properties.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `product_id` | int | YES | NULL | FK to `product.id` |
| `key_name` | varchar(255) | YES | NULL | Property key |
| `value_name` | varchar(255) | YES | NULL | Property value |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Last updated |

- **PK:** `id`

---

#### `product_rating`
Individual user ratings for products.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `user_id` | int | YES | NULL | FK to `user.id` |
| `product_id` | int | YES | NULL | FK to `product.id` |
| `rate` | double | YES | NULL | Rating value |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Last updated |

- **PK:** `id`

---

#### `product_review`
Product reviews with moderation.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `user_id` | int | YES | NULL | FK to `user.id` |
| `product_id` | int | YES | NULL | FK to `product.id` |
| `review` | text | YES | NULL | Review text |
| `rate` | double | YES | NULL | Rating |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Created |
| `status` | int | NO | 0 | Moderation status |
| `status_date` | timestamp | YES | NULL | Moderation date |
| `status_user_id` | int | YES | NULL | Moderator user ID |
| `status_comment` | varchar(255) | YES | NULL | Moderator comment |

- **PK:** `id`
- **Indexes:** `user_id`, `product_id`

---

#### `product_request`
User requests for products not yet available on the platform.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `product_name` | varchar(255) | NO | — | Requested product name |
| `product_photo` | varchar(255) | YES | NULL | Photo URL |
| `quantity` | int | NO | — | Desired quantity |
| `product_link` | text | YES | NULL | External link |
| `phone` | varchar(255) | NO | — | Requester phone |
| `email` | varchar(255) | YES | NULL | Requester email |
| `status` | int | YES | 1 | Request status |
| `admin_notes` | text | YES | NULL | Admin notes |
| `date` | datetime | YES | CURRENT_TIMESTAMP | Created |
| `user_id` | int | YES | NULL | FK to `user.id` |
| `admin_id` | int | YES | NULL | Admin who processed |

- **PK:** `id`

---

#### `product_view`
Product page view tracking.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `product_id` | int | YES | NULL | FK to `product.id` |
| `ip` | varchar(255) | YES | NULL | Visitor IP |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | View time |

- **PK:** `id`
- **Indexes:** `product_id`

---

#### `product_view_recently`
Recently viewed products tracking.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `product_id` | int | YES | NULL | FK to `product.id` |
| `ip` | varchar(255) | YES | NULL | Visitor IP |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | View time |

- **PK:** `id`
- **Indexes:** `product_id`

---

#### `pending_products`
Products submitted from external warehouse (Sklad) awaiting moderation.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `warehouse_product_id` | int | NO | — | Sklad product ID |
| `branch_id` | int | NO | — | Sklad branch ID |
| `branch_name` | varchar(255) | YES | NULL | Branch display name |
| `branch_yii_stock_id` | int | YES | NULL | Mapped stock.id |
| `merchant_id` | int | YES | NULL | Sklad merchant ID |
| `merchant_name` | varchar(255) | YES | NULL | Merchant display name |
| `name` | varchar(255) | YES | NULL | Product name |
| `name_ru` | varchar(255) | YES | NULL | Name (Russian) |
| `name_en` | varchar(255) | YES | NULL | Name (English) |
| `name_uz` | varchar(255) | YES | NULL | Name (Uzbek) |
| `description_ru` | text | YES | NULL | Description (Russian) |
| `description_en` | text | YES | NULL | Description (English) |
| `description_uz` | text | YES | NULL | Description (Uzbek) |
| `price` | decimal(15,2) | YES | NULL | Retail price |
| `price_small` | decimal(15,2) | YES | NULL | Small wholesale price |
| `price_opt` | decimal(15,2) | YES | NULL | Wholesale price |
| `amount` | decimal(15,2) | YES | NULL | Available quantity |
| `barcode` | varchar(100) | YES | NULL | Barcode |
| `sku` | varchar(100) | YES | NULL | SKU |
| `weight` | decimal(10,2) | YES | NULL | Weight (grams) |
| `discount` | decimal(5,2) | YES | NULL | Discount % |
| `data` | json | NO | — | Complete Sklad payload |
| `status` | enum | NO | 'pending' | pending/approved/rejected |
| `callback_url` | varchar(500) | YES | NULL | Webhook URL for Sklad |
| `moderator_comment` | text | YES | NULL | Moderator feedback |
| `moderator_id` | int | YES | NULL | FK to `user.id` |
| `approved_product_id` | int | YES | NULL | FK to `product.id` |
| `created_at` | timestamp | NO | CURRENT_TIMESTAMP | Created |
| `updated_at` | timestamp | NO | CURRENT_TIMESTAMP | Last updated |
| `processed_at` | timestamp | YES | NULL | Moderation completed |

- **PK:** `id`
- **Unique:** (`branch_id`, `warehouse_product_id`)
- **Indexes:** `status`, `merchant_id`, `approved_product_id`, `created_at`

---

#### `product_moderation_comments`
Audit trail for product moderation actions.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `submission_id` | int | NO | — | FK to `pending_products.id` |
| `moderator_id` | int | YES | NULL | FK to `user.id` |
| `action` | varchar(50) | NO | — | submit/approve/reject/request_changes |
| `comment` | text | YES | NULL | Comment for merchant |
| `internal_notes` | text | YES | NULL | Private moderator notes |
| `status_before` | varchar(20) | YES | NULL | Status before action |
| `status_after` | varchar(20) | YES | NULL | Status after action |
| `metadata` | json | YES | NULL | Extra data |
| `created_at` | timestamp | NO | CURRENT_TIMESTAMP | Created |

- **PK:** `id`
- **Indexes:** `submission_id`, `moderator_id`, `created_at`

---

#### `product_sync_log`
Log of sync operations between Sklad and app.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `submission_id` | int | YES | NULL | FK to `pending_products.id` |
| `direction` | enum | NO | 'incoming' | incoming/outgoing |
| `action` | varchar(50) | NO | — | submit/webhook_sent/webhook_failed |
| `endpoint` | varchar(500) | YES | NULL | API endpoint URL |
| `request_data` | text | YES | NULL | Request payload |
| `response_data` | text | YES | NULL | Response payload |
| `response_code` | int | YES | NULL | HTTP response code |
| `message` | text | YES | NULL | Log message |
| `created_at` | timestamp | NO | CURRENT_TIMESTAMP | Created |

- **PK:** `id`
- **Indexes:** `submission_id`, `action`, `created_at`

---

#### `product_office`
Product pricing per office/branch.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `office_id` | int | YES | NULL | FK to `office.id` |
| `product_id` | int | YES | NULL | FK to `product.id` |
| `price` | varchar(255) | YES | NULL | Price |
| `price_usd` | varchar(255) | YES | NULL | Price in USD |
| `discount` | varchar(255) | YES | NULL | Discount |
| `qty` | varchar(255) | YES | NULL | Quantity |

- **PK:** `id`
- **FK:** `office_id` REFERENCES `office(id)` ON DELETE CASCADE
- **FK:** `product_id` REFERENCES `product(id)` ON DELETE CASCADE

---

#### `office`
Branch/office definitions.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `office_id` | varchar(255) | YES | NULL | External office ID |
| `name` | varchar(255) | YES | NULL | Office name |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Last updated |

- **PK:** `id`
- **Unique:** `id`

---

#### `image`
Images for products, banners, and other entities.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `object_id` | int | YES | NULL | Parent entity ID |
| `type` | varchar(255) | YES | NULL | Entity type (product, banner, etc.) |
| `photo` | varchar(255) | YES | NULL | Image path |
| `number_image` | varchar(255) | YES | NULL | Image number |
| `main` | int | YES | NULL | Main image flag |
| `web` | int | NO | 0 | Web-optimized flag |
| `hash` | text | YES | NULL | Image hash |
| `sort` | int | NO | 0 | Sort order |
| `status` | int | NO | 0 | Active flag |

- **PK:** `id`

---

#### `file`
Generic file attachments for entities.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `object_id` | int | YES | NULL | Parent entity ID |
| `type` | varchar(255) | YES | NULL | Entity type |
| `url` | varchar(255) | YES | NULL | File URL |
| `main` | int | YES | NULL | Main file flag |
| `sort` | int | YES | NULL | Sort order |

- **PK:** `id`

---

#### `ikpu`
IKPU classifier codes (Uzbekistan product classification).

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `code` | varchar(17) | NO | — | IKPU code (unique) |
| `name_ru` | varchar(500) | NO | — | Name (Russian) |
| `name_uz` | varchar(500) | YES | NULL | Name (Uzbek) |
| `name_en` | varchar(500) | YES | NULL | Name (English) |
| `parent_code` | varchar(17) | YES | NULL | Parent IKPU code |
| `status` | int | YES | 1 | 1=active, 0=inactive |
| `created_at` | timestamp | YES | CURRENT_TIMESTAMP | Created |
| `updated_at` | timestamp | YES | CURRENT_TIMESTAMP | Last updated |

- **PK:** `id`
- **Unique:** `code`
- **FK:** `parent_code` REFERENCES `ikpu(code)` ON DELETE SET NULL

---

### 3.3 Orders

#### `order`
Customer orders.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `user_id` | int | YES | NULL | FK to `user.id` |
| `inn` | varchar(255) | YES | NULL | Customer tax ID |
| `account` | varchar(255) | YES | NULL | Bank account |
| `bank_id` | varchar(255) | YES | NULL | Bank ID |
| `map_location` | varchar(255) | YES | NULL | Map coordinates |
| `payment_id` | int | YES | NULL | Payment method ID |
| `delivery_id` | int | YES | NULL | FK to `delivery.id` |
| `logist_id` | int | YES | NULL | FK to `logist.id` |
| `tariff_id` | int | YES | NULL | Logistics tariff ID |
| `shop_id` | int | YES | NULL | FK to `shop.id` |
| `price` | double | YES | NULL | Total price |
| `amount` | double | YES | NULL | Total amount |
| `receiver` | int | NO | 0 | Different receiver flag |
| `name` | varchar(255) | YES | NULL | Receiver name |
| `lastname` | varchar(255) | YES | NULL | Receiver lastname |
| `email` | varchar(255) | YES | NULL | Contact email |
| `phone` | text | YES | NULL | Contact phone |
| `address` | text | YES | NULL | Delivery address |
| `comment` | text | YES | NULL | Order comment |
| `status` | int | NO | 0 | Order status |
| `status_payment` | int | NO | 0 | Payment status |
| `status_logist` | int | YES | NULL | Logistics status |
| `status_delivery` | int | YES | NULL | Delivery status |
| `status_review` | int | YES | NULL | Review status |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Last updated |
| `bts_region_id` | int | YES | NULL | BTS region for delivery |
| `bts_city_id` | int | YES | NULL | BTS city for delivery |
| `delivery_cost` | decimal(10,2) | YES | NULL | Total delivery cost |
| `promocode_id` | int | YES | NULL | FK to `promocode.id` |
| `discount_amount` | decimal(10,2) | YES | 0.00 | Discount amount |

- **PK:** `id`
- **Unique:** `id`
- **Indexes:** `user_id`, `payment_id`, `delivery_id`, `bts_region_id`, `bts_city_id`

---

#### `order_product`
Order line items (products within an order).

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `user_id` | int | YES | NULL | FK to `user.id` |
| `order_id` | int | YES | NULL | FK to `order.id` |
| `product_id` | int | YES | NULL | FK to `product.id` |
| `shop_id` | int | YES | NULL | FK to `shop.id` |
| `delivery_id` | int | YES | NULL | Delivery method |
| `delivery_cost` | double | YES | NULL | Delivery cost for item |
| `amount` | double | YES | NULL | Quantity |
| `price` | double | YES | NULL | Unit price |
| `status` | int | YES | NULL | Line item status |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Last updated |
| `bts_id` | varchar(255) | YES | NULL | BTS tracking ID |
| `bts_status` | varchar(255) | YES | NULL | BTS delivery status |
| `bts_status_info` | text | YES | NULL | BTS status details |
| `bts_price` | decimal(10,2) | YES | NULL | BTS delivery cost |
| `address` | text | YES | NULL | Custom delivery address |
| `stock_id` | int | YES | NULL | FK to `stock.id` |
| `product_price` | decimal(15,2) | YES | NULL | Total price (unit * amount) |
| `sklad_product_id` | int | YES | NULL | External warehouse product ID |

- **PK:** `id`
- **Indexes:** `order_id`, `product_id`, `sklad_product_id`

---

#### `order_product_filter`
Filter selections for order line items.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `order_product_id` | int | YES | NULL | FK to `order_product.id` |
| `product_filter_id` | int | YES | NULL | FK to `product_filter.id` |

- **PK:** `id`
- **Indexes:** `order_product_id`, `product_filter_id`

---

#### `order_product_refund`
Refund requests for order line items.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `user_id` | int | YES | NULL | FK to `user.id` |
| `order_product_id` | int | YES | NULL | FK to `order_product.id` |
| `message` | text | YES | NULL | Refund reason |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Created |

- **PK:** `id`
- **FK:** `order_product_id` REFERENCES `order_product(id)` ON DELETE CASCADE

---

#### `order_receipt`
Fiscal receipts for orders.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `order_id` | int | YES | NULL | FK to `order.id` |
| `receipt_id` | varchar(255) | YES | NULL | External receipt ID |
| `status` | int | YES | NULL | Receipt status |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Created |

- **PK:** `id`
- **FK:** `order_id` REFERENCES `order(id)` ON DELETE CASCADE

---

#### `promocode`
Promotional codes for discounts.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `code` | varchar(50) | NO | — | Promo code (unique) |
| `type` | int | NO | 1 | 1=Fixed Amount, 2=Percentage |
| `value` | decimal(10,2) | NO | — | Discount value |
| `min_order_amount` | decimal(10,2) | YES | 0.00 | Minimum order |
| `max_discount_amount` | decimal(10,2) | YES | NULL | Max discount (for %) |
| `start_date` | datetime | YES | NULL | Valid from |
| `end_date` | datetime | YES | NULL | Valid until |
| `usage_limit` | int | YES | NULL | Total usage limit |
| `usage_limit_per_user` | int | YES | 1 | Per-user limit |
| `status` | int | YES | 1 | 0=Inactive, 1=Active |
| `is_first_order` | tinyint(1) | YES | 0 | First order only |
| `category_id` | int | YES | NULL | Restricted to category |
| `product_id` | int | YES | NULL | Restricted to product |
| `user_id` | int | YES | NULL | Personal user promo |
| `title_ru` | varchar(255) | YES | NULL | Title (Russian) |
| `title_uz` | varchar(255) | YES | NULL | Title (Uzbek) |
| `title_en` | varchar(255) | YES | NULL | Title (English) |
| `description_ru` | text | YES | NULL | Description (Russian) |
| `description_uz` | text | YES | NULL | Description (Uzbek) |
| `description_en` | text | YES | NULL | Description (English) |
| `created_at` | datetime | YES | CURRENT_TIMESTAMP | Created |
| `updated_at` | datetime | YES | CURRENT_TIMESTAMP | Last updated |

- **PK:** `id`
- **Unique:** `code`

---

### 3.4 Shops

#### `shop`
Vendor/seller shop profiles.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `user_id` | int | YES | NULL | FK to `user.id` (owner) |
| `name_ru` | varchar(255) | YES | NULL | Name (Russian) |
| `name_uz` | varchar(255) | YES | NULL | Name (Uzbek) |
| `name_en` | varchar(255) | YES | NULL | Name (English) |
| `description_ru` | text | YES | NULL | Description (Russian) |
| `description_uz` | text | YES | NULL | Description (Uzbek) |
| `description_en` | text | YES | NULL | Description (English) |
| `map_location` | varchar(255) | YES | NULL | Map coordinates |
| `contact_user` | varchar(255) | YES | NULL | Contact person |
| `contact_phone` | varchar(255) | YES | NULL | Contact phone |
| `status` | int | NO | 0 | Active flag |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Last updated |

- **PK:** `id`
- **Unique:** `id`

---

#### `shop_seller`
Shop legal/business entity details.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `shop_id` | int | YES | NULL | FK to `shop.id` |
| `inn` | varchar(255) | YES | NULL | Tax ID (INN) |
| `account` | varchar(255) | YES | NULL | Bank account |
| `bank` | varchar(255) | YES | NULL | Bank name |
| `address_legal` | varchar(255) | YES | NULL | Legal address |
| `oked` | varchar(255) | YES | NULL | OKED code |
| `okohx` | varchar(255) | YES | NULL | OKOHX code |
| `mfo` | varchar(255) | YES | NULL | MFO (bank code) |
| `vat_reg_code` | varchar(32) | YES | NULL | VAT registration code |
| `status` | int | NO | 0 | Active flag |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Last updated |
| `director` | varchar(255) | YES | NULL | Director name |
| `director_pnfl` | varchar(255) | YES | NULL | Director PNFL (personal ID) |
| `organization` | varchar(255) | YES | NULL | Organization name |

- **PK:** `id`
- **Indexes:** `shop_id`

---

#### `shop_advertising`
Advertising content per shop.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `shop_id` | int | YES | NULL | FK to `shop.id` |
| `name_ru` | varchar(255) | YES | NULL | Name (Russian) |
| `name_uz` | varchar(255) | YES | NULL | Name (Uzbek) |
| `name_en` | varchar(255) | YES | NULL | Name (English) |
| `content_ru` | text | YES | NULL | Content (Russian) |
| `content_uz` | text | YES | NULL | Content (Uzbek) |
| `content_en` | text | YES | NULL | Content (English) |
| `link` | varchar(255) | YES | NULL | Target URL |
| `status` | int | NO | 1 | Active flag |
| `sort` | int | NO | 0 | Sort order |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Last updated |

- **PK:** `id`
- **Indexes:** `shop_id`

---

#### `shop_document`
Legal documents associated with shops.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `shop_id` | int | YES | NULL | FK to `shop.id` |
| `name_ru` | varchar(255) | YES | NULL | Name (Russian) |
| `name_en` | varchar(255) | YES | NULL | Name (English) |
| `name_uz` | varchar(255) | YES | NULL | Name (Uzbek) |
| `content_ru` | text | YES | NULL | Content (Russian) |
| `content_en` | text | YES | NULL | Content (English) |
| `content_uz` | text | YES | NULL | Content (Uzbek) |
| `status` | int | NO | 1 | Active flag |
| `sort` | int | NO | 0 | Sort order |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Last updated |

- **PK:** `id`
- **Indexes:** `shop_id`

---

#### `shop_oferta`
Public offer agreements per shop.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `shop_id` | int | YES | NULL | FK to `shop.id` |
| `name_ru` | varchar(255) | YES | NULL | Name (Russian) |
| `name_en` | varchar(255) | YES | NULL | Name (English) |
| `name_uz` | varchar(255) | YES | NULL | Name (Uzbek) |
| `content_ru` | text | YES | NULL | Content (Russian) |
| `content_en` | text | YES | NULL | Content (English) |
| `content_uz` | text | YES | NULL | Content (Uzbek) |
| `status` | int | NO | 1 | Active flag |
| `sort` | int | NO | 0 | Sort order |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Last updated |

- **PK:** `id`
- **Indexes:** `shop_id`

---

#### `shop_logist`
Shop-to-logist provider assignments.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `shop_id` | int | YES | NULL | FK to `shop.id` |
| `logist_id` | int | YES | NULL | FK to `logist.id` |
| `status` | int | NO | 1 | Active flag |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Last updated |

- **PK:** `id`
- **FK:** `shop_id` REFERENCES `shop(id)` ON DELETE CASCADE
- **FK:** `logist_id` REFERENCES `logist(id)` ON DELETE CASCADE

---

#### `shop_support`
Support tickets from shops.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `shop_id` | int | YES | NULL | FK to `shop.id` |
| `user_id` | int | YES | NULL | FK to `user.id` |
| `theme` | varchar(255) | YES | NULL | Ticket subject |
| `message` | text | YES | NULL | Ticket message |
| `status` | int | NO | 0 | Ticket status |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Created |

- **PK:** `id`
- **Indexes:** `shop_id`

---

#### `stock`
Warehouse/stock locations for shops.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `shop_id` | int | YES | NULL | FK to `shop.id` |
| `name_ru` | varchar(255) | YES | NULL | Name (Russian) |
| `name_en` | varchar(255) | YES | NULL | Name (English) |
| `name_uz` | varchar(255) | YES | NULL | Name (Uzbek) |
| `description_ru` | text | YES | NULL | Description (Russian) |
| `description_en` | text | YES | NULL | Description (English) |
| `description_uz` | text | YES | NULL | Description (Uzbek) |
| `status` | int | NO | 1 | Active flag |
| `sort` | int | NO | 0 | Sort order |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Last updated |
| `bts_region_id` | int | YES | NULL | BTS region for delivery |
| `bts_city_id` | int | YES | NULL | BTS city for delivery |
| `address` | text | YES | NULL | Warehouse address |

- **PK:** `id`
- **Indexes:** `shop_id`, `bts_region_id`, `bts_city_id`

---

### 3.5 Delivery & Logistics

#### `delivery`
Delivery method definitions.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `name_ru` | varchar(255) | YES | NULL | Name (Russian) |
| `name_uz` | varchar(255) | YES | NULL | Name (Uzbek) |
| `name_en` | varchar(255) | YES | NULL | Name (English) |
| `description_ru` | text | YES | NULL | Description (Russian) |
| `description_uz` | text | YES | NULL | Description (Uzbek) |
| `description_en` | text | YES | NULL | Description (English) |
| `price` | double | YES | NULL | Base delivery price |
| `status` | int | NO | 0 | Active flag |
| `sort` | int | NO | 0 | Sort order |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Last updated |

- **PK:** `id`
- **Unique:** `id`

---

#### `logist`
Logistics provider companies.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `user_id` | int | YES | NULL | FK to `user.id` (owner) |
| `name_ru` | varchar(255) | YES | NULL | Name (Russian) |
| `name_uz` | varchar(255) | YES | NULL | Name (Uzbek) |
| `name_en` | varchar(255) | YES | NULL | Name (English) |
| `description_ru` | varchar(255) | YES | NULL | Description (Russian) |
| `description_uz` | varchar(255) | YES | NULL | Description (Uzbek) |
| `description_en` | varchar(255) | YES | NULL | Description (English) |
| `contact_user` | varchar(255) | YES | NULL | Contact person |
| `contact_phone` | varchar(255) | YES | NULL | Contact phone |
| `sort` | int | NO | 0 | Sort order |
| `status` | int | NO | 1 | Active flag |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Last updated |

- **PK:** `id`
- **Unique:** `id`

---

#### `logist_region`
Logist provider regional service areas.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `logist_id` | int | YES | NULL | FK to `logist.id` |
| `region_a_id` | int | YES | NULL | Origin region |
| `region_id` | int | YES | NULL | Destination region |
| `region_tree` | varchar(255) | YES | NULL | Destination region path |
| `region_a_tree` | varchar(255) | YES | NULL | Origin region path |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Last updated |

- **PK:** `id`
- **Unique:** `id`
- **FK:** `logist_id` REFERENCES `logist(id)` ON DELETE CASCADE
- **FK:** `region_id` REFERENCES `category(id)` ON DELETE CASCADE

---

#### `logist_region_price`
Pricing tiers for logist regional routes.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `logist_region_id` | int | YES | NULL | FK to `logist_region.id` |
| `unit_id` | int | YES | NULL | Unit of measure |
| `unit_amount` | double | YES | NULL | Amount per unit |
| `price` | double | YES | NULL | Price |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Last updated |

- **PK:** `id`
- **FK:** `logist_region_id` REFERENCES `logist_region(id)` ON DELETE CASCADE

---

#### `regions`
Geographic regions (for BTS delivery system).

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `bts_id` | int | NO | — | BTS system region ID (unique) |
| `name_ru` | varchar(255) | NO | — | Name (Russian) |
| `name_uz` | varchar(255) | NO | — | Name (Uzbek) |
| `name_en` | varchar(255) | NO | — | Name (English) |
| `status` | int | YES | 1 | 1=active, 0=inactive |
| `created_at` | timestamp | YES | CURRENT_TIMESTAMP | Created |
| `updated_at` | timestamp | YES | CURRENT_TIMESTAMP | Last updated |

- **PK:** `id`
- **Unique:** `bts_id`
- **Indexes:** `bts_id`, `status`

---

#### `cities`
Cities within regions (for BTS delivery system).

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `bts_id` | int | NO | — | BTS city ID (unique) |
| `region_id` | int | NO | — | FK to `regions.id` |
| `bts_region_id` | int | NO | — | BTS region ID |
| `name_ru` | varchar(255) | NO | — | Name (Russian) |
| `name_uz` | varchar(255) | NO | — | Name (Uzbek) |
| `name_en` | varchar(255) | NO | — | Name (English) |
| `status` | int | YES | 1 | 1=active, 0=inactive |
| `created_at` | timestamp | YES | CURRENT_TIMESTAMP | Created |
| `updated_at` | timestamp | YES | CURRENT_TIMESTAMP | Last updated |

- **PK:** `id`
- **Unique:** `bts_id`
- **FK:** `region_id` REFERENCES `regions(id)` ON DELETE CASCADE
- **Indexes:** `bts_id`, `region_id`, `bts_region_id`, `status`

---

### 3.6 Didox (E-Invoicing)

#### `didox_document`
Master table for DIDOX electronic documents (invoices, arbitrary documents).

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `name` | varchar(255) | NO | — | Document name |
| `document_type` | enum('invoice','arbitrary') | NO | 'invoice' | Document type |
| `didox_id` | varchar(100) | YES | NULL | DIDOX API document ID |
| `didox_status` | int | YES | NULL | DIDOX status (0-190) |
| `didox_data` | text | YES | NULL | JSON API response |
| `didox_error_data` | text | YES | NULL | JSON API error |
| `didox_last_attempt` | datetime | YES | NULL | Last API attempt |
| `didox_created_at` | datetime | YES | NULL | DIDOX creation time |
| `didox_signed_at` | datetime | YES | NULL | DIDOX signing time |
| `created_by` | int | YES | NULL | FK to `user.id` |
| `to_user_id` | int | YES | NULL | FK to `user.id` (signer) |
| `status` | int | YES | 1 | 0=inactive, 1=active, 2=blocked |
| `created_at` | timestamp | YES | CURRENT_TIMESTAMP | Created |
| `updated_at` | timestamp | YES | CURRENT_TIMESTAMP | Last updated |
| `didox_user_key` | varchar(255) | YES | NULL | DIDOX user key |
| `didox_doc_type` | varchar(25) | NO | '002' | DIDOX type code |
| `order_id` | int | YES | NULL | FK to `order.id` |
| `pdf_paths` | text | YES | NULL | JSON with PDF file paths |

- **PK:** `id`
- **FK:** `created_by` REFERENCES `user(id)` ON DELETE SET NULL
- **FK:** `to_user_id` REFERENCES `user(id)` ON DELETE SET NULL
- **Indexes:** `didox_id`, `didox_status`, `document_type`, `created_by`, `to_user_id`, `status`, `didox_created_at`, `created_at`, `order_id`

---

#### `didox_document_arbitrary`
Arbitrary (non-invoice) DIDOX document details.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `document_id` | int | NO | — | FK to `didox_document.id` |
| `document_no` | varchar(100) | YES | NULL | Document number |
| `document_date` | date | YES | NULL | Document date |
| `document_name` | varchar(255) | YES | NULL | Document name |
| `contract_no` | varchar(100) | YES | NULL | Contract number |
| `contract_date` | date | YES | NULL | Contract date |
| `seller_tin` | varchar(20) | NO | — | Seller tax ID |
| `seller_name` | varchar(255) | NO | — | Seller name |
| `seller_address` | text | NO | — | Seller address |
| `seller_branch_code` | varchar(50) | YES | NULL | Seller branch code |
| `seller_branch_name` | varchar(255) | YES | NULL | Seller branch name |
| `buyer_tin` | varchar(20) | NO | — | Buyer tax ID |
| `buyer_name` | varchar(255) | NO | — | Buyer name |
| `buyer_address` | text | NO | — | Buyer address |
| `buyer_branch_code` | varchar(50) | YES | NULL | Buyer branch code |
| `buyer_branch_name` | varchar(255) | YES | NULL | Buyer branch name |
| `pdf_file_content` | longtext | YES | NULL | PDF base64 content |
| `pdf_file_name` | varchar(255) | YES | NULL | PDF filename |
| `pdf_file_size` | int | YES | NULL | PDF size (bytes) |
| `created_at` | datetime | YES | NULL | Created |
| `updated_at` | datetime | YES | NULL | Last updated |

- **PK:** `id`
- **FK:** `document_id` REFERENCES `didox_document(id)` ON DELETE CASCADE
- **Indexes:** `document_id`, `document_no`, `contract_no`, `seller_tin`, `buyer_tin`

---

#### `didox_document_invoice`
Invoice-specific DIDOX document details (detailed seller/buyer info, factura fields).

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `document_id` | int | NO | — | FK to `didox_document.id` |
| `invoice_number` | varchar(100) | YES | NULL | Invoice number |
| `invoice_date` | date | YES | NULL | Invoice date |
| `factura_type` | tinyint | YES | 0 | Factura type (0-9) |
| `contract_number` | varchar(100) | YES | NULL | Contract number |
| `contract_date` | date | YES | NULL | Contract date |
| `contract_id` | varchar(100) | YES | NULL | Contract ID (soliq.uz) |
| `lot_id` | varchar(100) | YES | NULL | Lot ID |
| `has_marking` | tinyint(1) | YES | 0 | Has product marking |
| `has_rent` | tinyint(1) | YES | 0 | Rent services |
| `has_committent` | tinyint(1) | YES | 0 | Three-party invoice |
| `has_excise` | tinyint(1) | YES | 0 | Has excise tax |
| `has_vat` | tinyint(1) | YES | 1 | Has VAT |
| `has_lgota` | tinyint(1) | YES | 0 | Tax exemption |
| `buyer_tin` | varchar(20) | YES | NULL | Buyer tax ID |
| `seller_tin` | varchar(20) | YES | NULL | Seller tax ID |
| `seller_name` | varchar(255) | YES | NULL | Seller name |
| `seller_branch_code` | varchar(20) | YES | NULL | Seller branch code |
| `seller_branch_name` | varchar(255) | YES | NULL | Seller branch name |
| `seller_vat_reg_code` | varchar(50) | YES | NULL | Seller VAT reg code |
| `seller_account` | varchar(50) | YES | NULL | Seller bank account |
| `seller_bank_id` | varchar(10) | YES | NULL | Seller MFO |
| `seller_address` | text | YES | NULL | Seller address |
| `seller_director` | varchar(255) | YES | NULL | Seller director |
| `seller_accountant` | varchar(255) | YES | NULL | Seller accountant |
| `seller_vat_reg_status` | tinyint | YES | 20 | Seller VAT reg status |
| `buyer_name` | varchar(255) | YES | NULL | Buyer name |
| `buyer_branch_code` | varchar(20) | YES | NULL | Buyer branch code |
| `buyer_branch_name` | varchar(255) | YES | NULL | Buyer branch name |
| `buyer_vat_reg_code` | varchar(50) | YES | NULL | Buyer VAT reg code |
| `buyer_account` | varchar(50) | YES | NULL | Buyer bank account |
| `buyer_bank_id` | varchar(10) | YES | NULL | Buyer MFO |
| `buyer_address` | text | YES | NULL | Buyer address |
| `buyer_director` | varchar(255) | YES | NULL | Buyer director |
| `buyer_accountant` | varchar(255) | YES | NULL | Buyer accountant |
| `buyer_vat_reg_status` | tinyint | YES | 20 | Buyer VAT reg status |
| `old_factura_date` | date | YES | NULL | Previous factura date |
| `old_factura_no` | varchar(100) | YES | NULL | Previous factura number |
| `old_factura_id` | varchar(100) | YES | NULL | Previous factura ID |
| `item_released_pinfl` | varchar(20) | YES | NULL | Releaser PINFL |
| `item_released_fio` | varchar(255) | YES | NULL | Releaser name |
| `investment_object_id` | varchar(100) | YES | NULL | Investment object ID |
| `investment_object_name` | varchar(255) | YES | NULL | Investment object name |
| `empowerment_no` | varchar(100) | YES | NULL | Power of attorney no |
| `empowerment_date_of_issue` | date | YES | NULL | PoA issue date |
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

- **PK:** `id`
- **FK:** `document_id` REFERENCES `didox_document(id)` ON DELETE CASCADE
- **Indexes:** `document_id`, `buyer_tin`, `seller_tin`, `invoice_number`, `invoice_date`, `contract_number`, `factura_type`

---

#### `didox_document_included_products`
Line items (products) within DIDOX documents.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `document_id` | int | NO | — | FK to `didox_document.id` |
| `ord_no` | int | YES | 1 | Line item number |
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
| `lgota_type` | tinyint | YES | NULL | 1=VAT, 2=turnover tax |
| `lgota_name` | varchar(255) | YES | NULL | Exemption name |
| `lgota_vat_sum` | decimal(15,2) | YES | 0.00 | Exemption VAT sum |
| `committent_name` | varchar(255) | YES | NULL | Committent name |
| `committent_tin` | varchar(20) | YES | NULL | Committent tax ID |
| `committent_vat_reg_code` | varchar(50) | YES | NULL | Committent VAT code |
| `committent_vat_reg_status` | varchar(20) | YES | NULL | Committent VAT status |
| `warehouse_id` | varchar(50) | YES | NULL | Warehouse ID |
| `origin` | int | YES | 4 | Product origin |
| `measure_id` | varchar(20) | YES | NULL | Measure ID (unused) |
| `created_at` | timestamp | YES | CURRENT_TIMESTAMP | Created |
| `updated_at` | timestamp | YES | CURRENT_TIMESTAMP | Last updated |

- **PK:** `id`
- **FK:** `document_id` REFERENCES `didox_document(id)` ON DELETE CASCADE
- **Indexes:** `document_id`, `ord_no`, `catalog_code`, `package_code`

---

### 3.7 Cart & Favorites

#### `user_cart`
Shopping cart items.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `user_id` | int | YES | NULL | FK to `user.id` |
| `product_id` | int | YES | NULL | FK to `product.id` |
| `delivery_id` | int | YES | NULL | Selected delivery method |
| `delivery_cost` | double | YES | NULL | Delivery cost |
| `amount` | double | YES | NULL | Quantity |
| `price` | double | YES | NULL | Unit price |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Last updated |

- **PK:** `id`
- **Indexes:** `user_id`, `product_id`

---

#### `user_cart_filter`
Filter selections for cart items.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `user_cart_id` | int | YES | NULL | FK to `user_cart.id` |
| `product_filter_id` | int | YES | NULL | FK to `product_filter.id` |

- **PK:** `id`
- **Indexes:** `user_cart_id`, `product_filter_id`

---

#### `user_compare`
Product comparison lists per user.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `user_id` | int | YES | NULL | FK to `user.id` |
| `product_id` | int | YES | NULL | FK to `product.id` |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Added date |

- **PK:** `id`
- **FK:** `user_id` REFERENCES `user(id)` ON DELETE CASCADE
- **FK:** `product_id` REFERENCES `product(id)` ON DELETE CASCADE

---

#### `user_favorite`
Product wishlists per user.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `user_id` | int | YES | NULL | FK to `user.id` |
| `product_id` | int | YES | NULL | FK to `product.id` |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Added date |

- **PK:** `id`
- **Indexes:** `user_id`, `product_id`

---

#### `user_shop_favorite`
Favorite shops per user.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `user_id` | int | YES | NULL | FK to `user.id` |
| `shop_id` | int | YES | NULL | FK to `shop.id` |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Added date |

- **PK:** `id`
- **FK:** `user_id` REFERENCES `user(id)` ON DELETE CASCADE
- **FK:** `shop_id` REFERENCES `shop(id)` ON DELETE CASCADE

---

### 3.8 Content & CMS

#### `banner`
Homepage banners.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `type` | varchar(255) | YES | NULL | Banner type |
| `description_ru` | text | YES | NULL | Description (Russian) |
| `description_en` | text | YES | NULL | Description (English) |
| `description_uz` | text | YES | NULL | Description (Uzbek) |
| `sort` | int | NO | 0 | Sort order |
| `status` | int | NO | 1 | Active flag |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Last updated |
| `hash` | text | YES | NULL | Image hash |

- **PK:** `id`

---

#### `slider`
Homepage slider items.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `type` | varchar(255) | YES | NULL | Slider type |
| `name_ru` | varchar(255) | YES | NULL | Name (Russian) |
| `name_uz` | varchar(255) | YES | NULL | Name (Uzbek) |
| `name_en` | varchar(255) | YES | NULL | Name (English) |
| `content_ru` | text | YES | NULL | Content (Russian) |
| `content_uz` | text | YES | NULL | Content (Uzbek) |
| `content_en` | text | YES | NULL | Content (English) |
| `link` | varchar(255) | YES | NULL | Target URL |
| `status` | int | NO | 1 | Active flag |
| `sort` | int | NO | 0 | Sort order |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Last updated |

- **PK:** `id`

---

#### `news`
News articles.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `shop_id` | int | YES | NULL | FK to `shop.id` |
| `name_ru` | varchar(255) | YES | NULL | Title (Russian) |
| `name_uz` | varchar(255) | YES | NULL | Title (Uzbek) |
| `name_en` | varchar(255) | YES | NULL | Title (English) |
| `description_mini_ru` | text | YES | NULL | Preview (Russian) |
| `description_mini_uz` | text | YES | NULL | Preview (Uzbek) |
| `description_mini_en` | text | YES | NULL | Preview (English) |
| `description_ru` | text | YES | NULL | Full text (Russian) |
| `description_uz` | text | YES | NULL | Full text (Uzbek) |
| `description_en` | text | YES | NULL | Full text (English) |
| `views` | int | NO | 0 | View counter |
| `status` | int | NO | 0 | Published flag |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Published date |

- **PK:** `id`

---

#### `news_view`
News article view tracking.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `news_id` | int | YES | NULL | FK to `news.id` |
| `ip` | varchar(255) | YES | NULL | Visitor IP |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | View time |

- **PK:** `id`
- **Indexes:** `news_id`

---

#### `advantages`
Platform advantages/features displayed on the site.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `name_ru` | varchar(255) | YES | NULL | Name (Russian) |
| `name_uz` | varchar(255) | YES | NULL | Name (Uzbek) |
| `name_en` | varchar(255) | YES | NULL | Name (English) |
| `description_ru` | text | YES | NULL | Description (Russian) |
| `description_uz` | text | YES | NULL | Description (Uzbek) |
| `description_en` | text | YES | NULL | Description (English) |
| `status` | int | YES | NULL | Active flag |
| `sort` | int | YES | NULL | Sort order |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Last updated |

- **PK:** `id`

---

#### `partners`
Partner companies displayed on the site.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
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
| `status` | int | YES | NULL | Active flag |
| `sort` | int | YES | NULL | Sort order |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Last updated |

- **PK:** `id`

---

#### `question`
FAQ questions and answers.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `question_ru` | text | YES | NULL | Question (Russian) |
| `question_en` | text | YES | NULL | Question (English) |
| `question_uz` | text | YES | NULL | Question (Uzbek) |
| `answer_ru` | text | YES | NULL | Answer (Russian) |
| `answer_en` | text | YES | NULL | Answer (English) |
| `answer_uz` | text | YES | NULL | Answer (Uzbek) |
| `status` | int | NO | 0 | Published flag |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Last updated |

- **PK:** `id`

---

#### `words`
Localization/translation strings.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `name_ru` | text | YES | NULL | Text (Russian) |
| `name_uz` | text | YES | NULL | Text (Uzbek) |
| `name_en` | text | YES | NULL | Text (English) |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Last updated |

- **PK:** `id`

---

#### `settings`
Global platform settings (key-value).

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `type` | varchar(255) | YES | NULL | Setting type/key |
| `content` | varchar(255) | YES | NULL | Setting value |
| `main` | int | NO | 0 | Main flag |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Last updated |

- **PK:** `id`

---

### 3.9 Communication

#### `message_room`
Chat rooms between users (buyer-seller messaging).

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `type` | varchar(255) | YES | NULL | Room type |
| `product_id` | int | YES | NULL | FK to `product.id` |
| `sender_id` | int | YES | NULL | FK to `user.id` |
| `getter_id` | int | YES | NULL | FK to `user.id` |
| `status` | int | NO | 0 | Read status |
| `status_archive` | int | NO | 0 | Archived flag |
| `status_important` | int | NO | 0 | Important flag |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Last activity |

- **PK:** `id`
- **Unique:** `id`
- **Indexes:** `sender_id`, `getter_id`

---

#### `messages`
Individual chat messages within a room.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `message_room_id` | int | YES | NULL | FK to `message_room.id` |
| `user_id` | int | YES | NULL | FK to `user.id` (sender) |
| `message` | text | YES | NULL | Message text |
| `status` | int | NO | 0 | Read status |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Sent time |

- **PK:** `id`
- **Indexes:** `message_room_id`, `user_id`

---

#### `notification`
Push/in-app notifications for users.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `user_id` | int | YES | NULL | FK to `user.id` |
| `object_id` | int | YES | NULL | Related entity ID |
| `type` | varchar(255) | YES | NULL | Notification type |
| `status` | int | NO | 0 | Read status |
| `message` | varchar(255) | YES | NULL | Notification text |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Created |

- **PK:** `id`

---

#### `feedback`
Contact form submissions.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `user_id` | varchar(255) | YES | NULL | User ID or identifier |
| `shop_id` | int | YES | NULL | Related shop |
| `name` | varchar(255) | YES | NULL | Sender name |
| `email` | varchar(255) | YES | NULL | Sender email |
| `message` | text | YES | NULL | Message content |
| `status` | int | NO | 0 | Processing status |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Created |

- **PK:** `id`

---

#### `support_chat`
Admin support chat threads.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | bigint | NO | AUTO_INCREMENT | **PK** |
| `sender_id` | bigint | NO | — | Sender user ID |
| `getter_id` | bigint | NO | — | Receiver user ID |
| `subject` | text | NO | — | Chat subject |
| `status` | int | NO | — | Chat status |
| `created_at` | timestamp | NO | CURRENT_TIMESTAMP | Created |
| `updated_at` | timestamp | NO | — | Last updated |

- **PK:** `id`

---

### 3.10 System & Payments

#### `transaction`
Internal transaction records.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `user_id` | int | YES | NULL | FK to `user.id` |
| `order_id` | int | YES | NULL | FK to `order.id` |
| `shop_id` | int | YES | NULL | FK to `shop.id` |
| `type_transaction` | varchar(255) | YES | NULL | Transaction type |
| `type_payment` | varchar(255) | YES | NULL | Payment method |
| `amount` | double | YES | NULL | Amount |
| `status` | int | NO | 0 | Transaction status |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Created |

- **PK:** `id`

---

#### `transaction_payme`
Payme payment gateway transactions.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `user_id` | int | YES | NULL | FK to `user.id` |
| `order_id` | int | YES | NULL | FK to `order.id` |
| `transaction` | varchar(255) | YES | NULL | Payme transaction ID |
| `code` | varchar(255) | YES | NULL | Transaction code |
| `state` | varchar(255) | YES | NULL | Payme state |
| `amount` | double | YES | NULL | Amount |
| `reason` | varchar(255) | YES | NULL | Cancellation reason |
| `payme_time` | varchar(255) | YES | NULL | Payme timestamp |
| `cancel_time` | varchar(255) | YES | NULL | Cancel timestamp |
| `create_time` | varchar(255) | YES | NULL | Create timestamp |
| `perform_time` | varchar(255) | YES | NULL | Perform timestamp |
| `status` | int | NO | 0 | Transaction status |
| `date` | timestamp | NO | CURRENT_TIMESTAMP | Created |

- **PK:** `id`

---

#### `pay_keeper_transaction`
PayKeeper payment gateway transactions.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `transaction_id` | varchar(255) | YES | NULL | PayKeeper transaction ID |
| `order_id` | varchar(255) | YES | NULL | Order ID |
| `amount` | float | YES | NULL | Amount |
| `status` | tinyint | YES | 0 | Transaction status |
| `currency` | int | YES | NULL | Currency code |
| `request` | text | YES | NULL | API request |
| `response` | text | YES | NULL | API response |
| `date` | int | YES | NULL | Unix timestamp |

- **PK:** `id`
- **Unique:** `id`

---

#### `payme_password`
Payme API credentials.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `password` | varchar(255) | NO | — | API password |
| `date` | int | YES | NULL | Unix timestamp |

- **PK:** `id`

---

#### `migration`
Yii2 framework database migration tracking.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `version` | varchar(180) | NO | — | **PK** Migration version |
| `apply_time` | int | YES | NULL | Applied timestamp |

- **PK:** `version`

---

#### `moderator_access`
Moderator-to-user access assignments.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `moderator_id` | int | YES | NULL | FK to `user.id` |
| `user_id` | int | YES | NULL | FK to `user.id` |

- **PK:** `id`
- **Indexes:** `moderator_id`, `user_id`

---

#### `moderator_url`
Admin/moderator URL access control definitions.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `type` | varchar(255) | YES | NULL | URL type |
| `name` | varchar(255) | YES | NULL | URL name |
| `url` | varchar(255) | YES | NULL | URL path |

- **PK:** `id`
- **Unique:** `id`

---

#### `logs`
Application event/error logs.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | int | NO | AUTO_INCREMENT | **PK** |
| `category` | varchar(255) | NO | — | Log category |
| `level` | varchar(50) | YES | 'info' | Log level |
| `message` | text | YES | NULL | Log message |
| `data` | longtext | YES | NULL | Structured log data |
| `created_at` | datetime | YES | CURRENT_TIMESTAMP | Created |

- **PK:** `id`
- **Indexes:** `category`, `created_at`

---

## 4. Relationship Diagram

### Core Entity Relationships

```
┌─────────────────────────────────────────────────────────────────────┐
│                        USER & SHOP DOMAIN                           │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  user (id) ──1:N──> shop (user_id)                                 │
│  user (id) ──1:N──> user_address (user_id)                         │
│  user (id) ──1:N──> user_card (user_id)                            │
│  user (id) ──1:N──> user_cart (user_id)                            │
│  user (id) ──1:N──> user_compare (user_id)         [FK CASCADE]    │
│  user (id) ──1:N──> user_favorite (user_id)                        │
│  user (id) ──1:N──> user_shop_favorite (user_id)   [FK CASCADE]    │
│  user (id) ──1:N──> sms_code (user_id)                             │
│  user (id) ──1:N──> filter_user (user_id)           [FK CASCADE]   │
│  user (id) ──1:N──> notification (user_id)                         │
│                                                                     │
│  shop (id) ──1:N──> shop_seller (shop_id)                          │
│  shop (id) ──1:N──> shop_advertising (shop_id)                     │
│  shop (id) ──1:N──> shop_document (shop_id)                        │
│  shop (id) ──1:N──> shop_oferta (shop_id)                          │
│  shop (id) ──1:N──> shop_logist (shop_id)           [FK CASCADE]   │
│  shop (id) ──1:N──> shop_support (shop_id)                         │
│  shop (id) ──1:N──> stock (shop_id)                                │
│  shop (id) ──1:N──> user_shop_favorite (shop_id)    [FK CASCADE]   │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────┐
│                       PRODUCT DOMAIN                                │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  category (id) ──1:N──> product (category_id)                      │
│  category (id) ──1:N──> category_brand (category_id)               │
│  category (id) ──1:N──> category_filter (category_id) [FK CASCADE] │
│  category (id) ──1:N──> filter (category_id)                       │
│  category (id) ──1:N──> product_type (category_id)    [FK CASCADE] │
│  category (parent_id) ──self-ref──> category (id)                  │
│                                                                     │
│  product (id) ──1:N──> product_color (product_id)     [FK CASCADE] │
│  product (id) ──1:N──> product_filter (product_id)                 │
│  product (id) ──1:N──> product_product_type (product_id) [FK CASC] │
│  product (id) ──1:N──> product_property (product_id)               │
│  product (id) ──1:N──> product_rating (product_id)                 │
│  product (id) ──1:N──> product_review (product_id)                 │
│  product (id) ──1:N──> product_view (product_id)                   │
│  product (id) ──1:N──> product_view_recently (product_id)          │
│  product (id) ──1:N──> product_office (product_id)    [FK CASCADE] │
│  product (id) ──1:N──> user_cart (product_id)                      │
│  product (id) ──1:N──> user_compare (product_id)      [FK CASCADE] │
│  product (id) ──1:N──> user_favorite (product_id)                  │
│  product (id) ──1:N──> order_product (product_id)                  │
│                                                                     │
│  user (id) ──1:N──> product (user_id)                              │
│  shop (id) ──1:N──> product (shop_id)                              │
│  stock (id) ──1:N──> product (stock_id)                            │
│  color (id) ──1:N──> product_color (color_id)         [FK CASCADE] │
│                                                                     │
│  product_type (id) ──1:N──> product_type_value (product_type_id)   │
│                                                      [FK CASCADE]   │
│  product_type (id) ──1:N──> product_product_type (product_type_id) │
│                                                      [FK CASCADE]   │
│  product_type_value (id) ──1:N──> product_product_type              │
│                              (product_type_value_id)  [FK CASCADE]  │
│                                                                     │
│  filter (id) ──1:N──> filter_user (filter_id)         [FK CASCADE] │
│  office (id) ──1:N──> product_office (office_id)      [FK CASCADE] │
│  ikpu (code) ──self-ref──> ikpu (parent_code)         [FK SET NULL]│
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────┐
│                        ORDER DOMAIN                                 │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  user (id) ──1:N──> order (user_id)                                │
│  order (id) ──1:N──> order_product (order_id)                      │
│  order (id) ──1:N──> order_receipt (order_id)         [FK CASCADE] │
│  order (id) ──1:N──> transaction (order_id)                        │
│  order (id) ──1:N──> transaction_payme (order_id)                  │
│  order (id) ──1:N──> didox_document (order_id)                     │
│                                                                     │
│  order_product (id) ──1:N──> order_product_filter (order_product_id)│
│  order_product (id) ──1:N──> order_product_refund (order_product_id)│
│                                                      [FK CASCADE]   │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────┐
│                     DELIVERY & LOGISTICS                            │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  logist (id) ──1:N──> logist_region (logist_id)       [FK CASCADE] │
│  logist (id) ──1:N──> shop_logist (logist_id)         [FK CASCADE] │
│  logist_region (id) ──1:N──> logist_region_price                   │
│                               (logist_region_id)      [FK CASCADE]  │
│  regions (id) ──1:N──> cities (region_id)             [FK CASCADE] │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────┐
│                      DIDOX E-INVOICING                              │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  didox_document (id) ──1:1──> didox_document_invoice (document_id) │
│                                                      [FK CASCADE]   │
│  didox_document (id) ──1:1──> didox_document_arbitrary (document_id)│
│                                                      [FK CASCADE]   │
│  didox_document (id) ──1:N──> didox_document_included_products     │
│                                (document_id)          [FK CASCADE]  │
│  user (id) ──1:N──> didox_document (created_by)      [FK SET NULL] │
│  user (id) ──1:N──> didox_document (to_user_id)      [FK SET NULL] │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────┐
│                  PRODUCT MODERATION (SKLAD)                         │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  pending_products (id) ──1:N──> product_moderation_comments        │
│                                  (submission_id)                    │
│  pending_products (id) ──1:N──> product_sync_log (submission_id)   │
│  pending_products (approved_product_id) ──N:1──> product (id)      │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────┐
│                       MESSAGING                                     │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  message_room (id) ──1:N──> messages (message_room_id)             │
│  user (id) ──1:N──> message_room (sender_id)                       │
│  user (id) ──1:N──> message_room (getter_id)                       │
│  user (id) ──1:N──> messages (user_id)                             │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────┐
│                        CART DOMAIN                                  │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  user_cart (id) ──1:N──> user_cart_filter (user_cart_id)           │
│  product_filter (id) ──1:N──> user_cart_filter (product_filter_id) │
│  product_filter (id) ──1:N──> order_product_filter                 │
│                                (product_filter_id)                  │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘
```

### Simplified Core Flow

```
                    ┌──────────┐
                    │   user   │
                    └────┬─────┘
           ┌─────────────┼──────────────┐
           v             v              v
      ┌─────────┐  ┌──────────┐   ┌──────────┐
      │  shop   │  │  order   │   │ user_cart │
      └────┬────┘  └────┬─────┘   └────┬─────┘
           |             |              |
           v             v              v
      ┌─────────┐  ┌────────────┐ ┌────────────────┐
      │  stock  │  │order_product│ │user_cart_filter │
      └────┬────┘  └─────┬──────┘ └────────────────┘
           |              |
           v              v
      ┌─────────┐  ┌──────────┐
      │ product │  │ product  │
      └────┬────┘  └──────────┘
           |
     ┌─────┼──────┬───────────┐
     v     v      v           v
  ┌──────┐┌────────┐┌──────────┐┌───────────────────┐
  │image ││product ││ product  ││product_product_type│
  │      ││_color  ││_filter   ││                    │
  └──────┘└────────┘└──────────┘└───────────────────┘
```

---

## Summary Statistics

| Metric | Count |
|--------|-------|
| Total tables | 82 |
| Tables with explicit FK constraints | 22 |
| Self-referencing tables | 2 (category, ikpu) |
| Tables with trilingual columns | ~45 |
| Tables with BTS integration | 5 (user, order, stock, cities, order_product) |
| DIDOX-related tables | 4 |
| Payment-related tables | 4 |

---

*Document generated: 2026-02-10*
*Source: `app (2).sql` database dump*
