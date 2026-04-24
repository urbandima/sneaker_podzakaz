# SYSTEM_MAP — Sneakerhead Backend Architecture

> Last updated: 2026-04-14  
> Framework: Yii2 (PHP), MySQL, Yii2 modules architecture

---

## 1. Module Structure

```
backend/modules/
├── account/       — Личный кабинет покупателя (вход, регистрация, заказы, лояльность)
├── admin/         — Административная панель
├── cart/          — Корзина
├── catalog/       — Каталог, продукты, фильтры, избранное
├── checkout/      — Оформление заказа + службы доставки
├── common/        — Общие компоненты
├── compare/       — Сравнение товаров
├── coupon/        — Промокоды и скидки
├── loyalty/       — Программа лояльности (уровни, баллы)
├── marketing/     — Брошенные корзины, upsell
├── notification/  — Push/SMS/Telegram уведомления
├── returns/       — Возвраты
└── seo/           — SEO-мета, редиректы, sitemap

backend/shared/components/  — Глобальные Yii2-компоненты (Settings, Currency, etc.)
infrastructure/config/      — web.php, params.php, db.php
```

---

## 2. Database Tables → Models

| Таблица | Модель (класс) | Модуль |
|---------|----------------|--------|
| `product` | `catalog\models\Product` | catalog |
| `product_size` | `catalog\models\ProductSize` | catalog |
| `product_image` | `catalog\models\ProductImage` | catalog |
| `product_color` | `catalog\models\ProductColor` | catalog |
| `product_stock` | `catalog\models\ProductStock` | catalog |
| `product_review` | `catalog\models\ProductReview` | catalog |
| `product_favorite` | `catalog\models\ProductFavorite` | catalog |
| `product_characteristic` / `product_characteristic_value` | `catalog\models\ProductCharacteristicValue` | catalog |
| `brand` | `catalog\models\Brand` | catalog |
| `category` | `catalog\models\Category` | catalog |
| `characteristic` / `characteristic_value` | `catalog\models\Characteristic` | catalog |
| `order` | `checkout\models\Order` | checkout |
| `order_item` | (array in Order) | checkout |
| `order_history` | — | checkout |
| `order_status` | — | checkout |
| `delivery_tracking` | `catalog\models\DeliveryTracking` | catalog |
| `delivery_provider` | — | checkout |
| `cart` | `cart\models\Cart` | cart |
| `customer` | `account\models\Customer` | account |
| `customer_social_account` | `account\models\CustomerSocialAccount` | account |
| `customer_loyalty_level` | — | loyalty |
| `loyalty_level` | — | loyalty |
| `loyalty_points` | — | loyalty |
| `coupon` / `coupon_usage` | `coupon\services\CouponService` | coupon |
| `app_setting` | `shared\components\Settings` | shared |
| `user` | `admin\models\User` | admin |
| `admin_log` | `admin\models\AdminLog` | admin |
| `tariff` | `admin\models\Tariff` | admin |
| `analytics_event` | `catalog\models\AnalyticsEvent` | catalog |
| `daily_stats` | — | admin |
| `import_batch` / `import_log` / `import_task` | `catalog\models\ImportBatch` | catalog |
| `return_request` | `returns\services\ReturnService` | returns |
| `redirect` | `seo\components\RedirectMiddleware` | seo |
| `sidebar_menu` | `admin\models\SidebarMenuItem` | admin |
| `size_grid` / `size_grid_item` | `admin\models\SizeGrid` | admin |

---

## 3. product_size Schema

```sql
product_size (
  id             INT PK AUTO_INCREMENT,
  product_id     INT FK → product.id,
  size           VARCHAR(20)    -- основное отображаемое значение (EU)
  eu_size        VARCHAR(20),
  us_size        VARCHAR(20),
  uk_size        VARCHAR(20),
  cm_size        DECIMAL(5,1),
  price_byn      DECIMAL(10,2), -- цена для этого размера (NULL = цена продукта)
  is_available   TINYINT(1),    -- 1 = в наличии
  stock_quantity INT,
  sort_order     INT
)
```

**Flow: размеры:**
1. `ProductSize` записи создаются через импорт (Poizon) или вручную в admin
2. Страница продукта (`catalog/catalog/product`) загружает `ProductSize::findAll(['product_id'=>$id])`
3. Фронтенд рендерит кнопки размеров через `product-page.js`
4. При добавлении в корзину передаётся `size` (строка EU) и `product_size_id`
5. `order_item.size` хранит строку выбранного размера

---

## 4. Delivery Flow

```
Заказ создан
    │
    ├─► delivery_provider.code = 'dobropost'
    │       └─► DobroPostService::createShipment()
    │               API: https://api.dobropost.com
    │               Credentials: DP_API_EMAIL, DP_API_PASSWORD (.env)
    │               Вебхуки: /api/webhook/dobropost → ApiController
    │
    ├─► delivery_provider.code = 'europochta'
    │       └─► EuropochtaTrackingService::getStatus($trackNumber)
    │               API: https://evropochta.by/api/track.json/?number=...
    │               Config: app_setting[section=plugin, key=plugin_europochta_config] (JSON)
    │
    ├─► delivery_provider.code = 'belpochta'
    │       └─► BelpochtaTrackingService::getStatus($trackNumber)
    │               API: https://api.belpost.by/api/v1/tracking?number=...
    │               Config: app_setting[section=plugin, key=plugin_belpochta_config] (JSON)
    │
    └─► delivery_provider.code = 'cdek'
            └─► CdekTrackingService::getStatus($trackNumber)
                    API: https://api.cdek.ru/v2 (OAuth2 client_credentials)
                    Config: app_setting[section=plugin, key=plugin_cdek_config]
                              {active, client_id, client_secret}
                    Token cached: Yii::$app->cache key='cdek_oauth_token'

Tracking stored in: delivery_tracking table
    order_id, tracking_number, carrier, status, status_description, tracking_events (JSON)
```

---

## 5. Controllers → Actions → Views

### Frontend (public)

| Controller | Key Actions | View |
|-----------|------------|------|
| `catalog/CatalogController` | `actionIndex`, `actionProduct`, `actionBrand`, `actionCategory`, `actionFavorites` | `catalog/views/catalog/` |
| `catalog/CatalogApiController` | `actionFilter`, `actionLoadMore`, `actionQuickView` | JSON |
| `checkout/OrderController` | `actionIndex` (корзина→заказ), `actionCreate` | `checkout/views/` |
| `account/AccountController` | `actionIndex`, `actionOrders`, `actionSettings`, `actionTracking` | `account/views/` |
| `account/LoyaltyController` | `actionIndex` | `account/views/loyalty/` |
| `account/ReturnController` | `actionCreate`, `actionIndex` | `account/views/return/` |

### Admin

| Controller | Key Actions | Views |
|-----------|------------|-------|
| `admin/DashboardController` | `actionIndex` | `admin/views/dashboard/` |
| `admin/ProductController` | CRUD + `actionSizes` | `admin/views/product/` |
| `admin/OrderController` | `actionIndex`, `actionView`, `actionUpdateStatus` | `admin/views/order/` |
| `admin/PluginController` | `actionIndex`, `actionEuropochta`, `actionSaveEuropochta`, `actionBelpochta`, `actionSaveBelpochta`, `actionCdek`, `actionSaveCdek`, `actionDobropost`, `actionCurrency`, `actionTelegram` | `admin/views/plugin/` |
| `admin/AnalyticsController` | `actionIndex`, `actionRfm` | `admin/views/analytics/` |
| `admin/MarketingController` | `actionIndex`, `actionCampaigns` | `admin/views/marketing/` |
| `admin/ImportController` | `actionIndex`, `actionUpload`, `actionProcess` | `admin/views/import/` |
| `admin/PoizonController` | `actionIndex`, `actionRun`, `actionView`, `actionViewLog` | `admin/views/poizon/` |
| `admin/CustomerController` | CRUD | `admin/views/customer/` |
| `admin/TariffController` | CRUD + `actionCalculate` | `admin/views/tariff/` |
| `admin/SeoController` | `actionIndex`, `actionUpdateProductMeta` | `admin/views/seo/` |
| `admin/TrackingController` | tracking management | `admin/views/tracking/` |

---

## 6. Yii2 Components (web.php)

| Component name | Class | Purpose |
|---------------|-------|---------|
| `settings` | `shared\components\Settings` | Unified key-value store → `app_setting` table |
| `currency` | `shared\components\CurrencyService` | CNY→BYN rate, daily refresh from НБРБ API |
| `dobropost` | `checkout\services\DobroPostService` | Таможня:ДП shipment creation & tracking |
| `europochtaTracking` | `checkout\services\EuropochtaTrackingService` | Европочта tracking |
| `belpochtaTracking` | `checkout\services\BelpochtaTrackingService` | Белпочта tracking |
| `cdekTracking` | `checkout\services\CdekTrackingService` | СДЭК tracking (OAuth2) |
| `poizonApi` | `shared\components\PoizonApiService` | Poizon parser API for price/product import |
| `sitemap` | `shared\components\SitemapAutoGenerator` | Auto sitemap generation |
| `db` | `yii\db\Connection` | MySQL connection |
| `cache` | `yii\caching\FileCache` | File-based cache (token cache, etc.) |
| `session` | `yii\web\Session` | PHP session |
| `user` | `yii\web\User` | Auth for admin |
| `formatter` | `yii\i18n\Formatter` | BYN locale ru-RU, Europe/Minsk TZ |

---

## 7. Settings Keys (app_setting table)

| section | key | Usage |
|---------|-----|-------|
| `checkout` | `payment_methods` | JSON array — методы оплаты (bank_transfer, card_online, cash_pickup) |
| `checkout` | `free_delivery_threshold` | Порог бесплатной доставки (BYN) |
| `checkout` | `pickup_address` | Адрес самовывоза |
| `shipping` | `methods` | JSON array — методы доставки (pickup, courier, europochta, belpochta, cdek) |
| `shipping` | `europochta_points` | JSON — список точек выдачи Европочты |
| `plugin` | `plugin_europochta_config` | JSON: `{active, ...}` для EuropochtaTrackingService |
| `plugin` | `plugin_belpochta_config` | JSON: `{active, ...}` для BelpochtaTrackingService |
| `plugin` | `plugin_cdek_config` | JSON: `{active, client_id, client_secret}` для CdekTrackingService |
| `telegram` | `bot_token` | Токен Telegram-бота |

Settings accessed via: `Yii::$app->settings->get('key')` or `Yii::$app->settings->get('section', 'key', 'default')`

---

## 8. Plugin Architecture

Plugins live in `infrastructure/plugins/` and implement interfaces:
- `PaymentGatewayInterface` — платёжные шлюзы
- `ShippingProviderInterface` — провайдеры доставки

**Delivery integrations** (NOT plugin interface, registered as Yii2 components):
- DobroPostService — полноценная интеграция (создание, трекинг, вебхуки)
- EuropochtaTrackingService — только трекинг по номеру
- BelpochtaTrackingService — только трекинг по номеру
- CdekTrackingService — трекинг + OAuth2 токен

Admin plugin pages: `/admin/plugin/{name}` → `PluginController::action{Name}()`  
Save actions: `/admin/plugin/{name}/save` → `PluginController::actionSave{Name}()`

---

## 9. Frontend JS → Backend Endpoints

| JS file | Endpoints called |
|---------|-----------------|
| `catalog.js`, `catalog-filter.js` | `GET /api/catalog/filter`, `GET /api/catalog/load-more` |
| `product-page.js` | `GET /api/catalog/quick-view/{id}` |
| `cart.js`, `cart-mobile.js` | `POST /cart/add`, `POST /cart/remove`, `POST /cart/update` |
| `cart-promo-loyalty.js` | `POST /cart/apply-coupon`, `POST /cart/remove-coupon` |
| `checkout.js` | `POST /order/create` |
| `favorites.js`, `wishlist-share.js` | `POST /catalog/add-favorite`, `POST /catalog/remove-favorite` |
| `search.js` | `GET /api/search` |
| `admin-orders.js` | `POST /admin/order/update-status`, `GET /admin/order/api` |
| `admin-products.js` | `POST /admin/product/save`, `GET /admin/import/ajax` |
| `admin-customers.js` | `GET /admin/customer/api` |
| `admin-poizon.js` | `POST /admin/poizon/run` |
| `dashboard.js` | `GET /admin/statistics/data` |
| `dark-mode.js` | localStorage only |
| `notifications.js` | `GET /api/notifications` |

---

## 10. Order Flow (End-to-End)

```
1. Catalog → ProductSize buttons → cart.js → POST /cart/add
       cart table: {session_id, product_id, size, quantity, price}

2. Cart page → cart-promo-loyalty.js → coupon/loyalty applied
       coupon table checked; loyalty_points balance read

3. Checkout → checkout.js → POST /order/create
       Creates: order record, order_item records
       Applies: coupon_usage, loyalty_points deduction
       Fires: NotificationService (Telegram + SMS)

4. Admin processes order:
       OrderController::actionUpdateStatus()
       → order_history recorded
       → delivery_tracking created with tracking_number + carrier

5. Tracking refresh:
       TrackingController or cron → {Carrier}TrackingService::getStatus()
       → delivery_tracking.status updated
       → customer notified (NotificationService)

6. Delivery complete:
       delivery_tracking.status = 'delivered'
       loyalty_points credited (LoyaltyService)
```

---

## 11. Import / Poizon Flow

```
Poizon Parser API (PoizonApiService)
    │
    ├── PoizonController::actionRun() → queues ImportTask
    ├── ImportController → processes batch
    │       creates/updates: product, product_image, product_size, brand, category
    └── ImportBatch → tracks progress, logs errors to import_log
```

---

## 12. Key Environment Variables (.env)

| Variable | Used by |
|----------|---------|
| `DB_DSN`, `DB_USER`, `DB_PASSWORD` | db.php |
| `DP_API_URL`, `DP_API_EMAIL`, `DP_API_PASSWORD`, `DP_DEFAULT_TARIFF` | DobroPostService |
| `POIZON_API_URL`, `POIZON_API_KEY` | PoizonApiService |
| `COOKIE_VALIDATION_KEY` | Yii2 web security |
| `APP_ENV` | YII_ENV (dev/prod) |
| `SENTRY_DSN` | SentryErrorHandler |
