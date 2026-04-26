# QA Admin Panel — Round 1

Date: 2026-04-26  
Tester: automated curl scan (Claude Code)  
Base URL: http://localhost:8080/admin  
Credentials used: admin / Admin1234! (bcrypt hash verified against DB)  
PHP version: 8.4.13

---

## 1. Route Status Summary

### Routes from task spec

| Route | HTTP Status | Title | Notes |
|-------|-------------|-------|-------|
| /admin/login | 200 | — | Login form renders OK |
| /admin | 200 | Панель управления — Админ | Dashboard OK |
| /admin/catalog/product | **404** | Страница не найдена | Wrong URL; correct is `/admin/product` |
| /admin/catalog/brand | **404** | Страница не найдена | Wrong URL; correct is `/admin/brand` |
| /admin/catalog/category | **404** | Страница не найдена | Wrong URL; correct is `/admin/category` |
| /admin/order | 200 | Управление заказами — Админ | OK |
| /admin/customer | 200 | Покупатели — Админ | OK |
| /admin/settings | 200 | Настройки — Админ | OK |
| /admin/poizon | 200 | ` — Админ` (empty title) | Page renders but title missing |

### Additional routes discovered via nav

| Route | HTTP Status | Title | Notes |
|-------|-------------|-------|-------|
| /admin/product | 200 | Каталог товаров — Админ | Correct product list URL |
| /admin/brand | 200 | Бренды — Админ | Correct brand management URL |
| /admin/category | 200 | Категории — Админ | Correct category management URL |
| /admin/product/create | 200 | Новый товар — Админ | OK |
| /admin/product/2772 | 200 | Товар — Админ | Product detail OK |
| /admin/product/export | **500** | Ошибка сервера | OOM: 128 MB memory exhausted |
| /admin/poizon/errors | **500** | Ошибка сервера | Missing class ImportLog |
| /admin/poizon/run | 200 | Запуск импорта товаров — Админ | OK |
| /admin/order/create | 302 → /admin/order/{id}?mode=create | — | By design (creates draft) |
| /admin/order/393 | 200 | Заказ №00474 — Админ | Order detail OK |
| /admin/statistics | 302 → /admin/analytics | — | Intentional alias redirect |
| /admin/analytics | 200 | Аналитика и отчёты — Админ | OK |
| /admin/analytics/rfm | 200 | RFM Аналитика — Админ | OK |
| /admin/finance | 200 | Платежи — Админ | OK |
| /admin/finance/expense | 200 | Расходы — Админ | OK |
| /admin/finance/margin | 200 | Маржинальность — Админ | OK |
| /admin/finance/pl | 200 | P&L — 2026 — Админ | OK, shows data |
| /admin/finance/create-payment | **500** | — | Undefined array key 'order_id' |
| /admin/finance/create-expense | 302 | — | Redirects (also has 'order_id' bug) |
| /admin/marketing | 200 | Маркетинг — Админ | Broken cart stats (see below) |
| /admin/review | 200 | Управление отзывами — Админ | Shows 0 reviews (no data) |
| /admin/return | 200 | Возвраты — Админ | OK |
| /admin/coupon | 200 | Купоны — Админ | OK |
| /admin/import | 200 | Импорт товаров — Админ | OK |
| /admin/characteristic | 200 | Характеристики и размеры — Админ | OK |
| /admin/procurement/buyouts | 200 | Выкупы товаров — Админ | OK |
| /admin/procurement/receiving | 200 | Приёмка товара — Админ | **PERF: 15 MB page, 3021 orders, no pagination** |
| /admin/procurement/returns | 200 | Возвраты поставщику — Админ | OK |
| /admin/procurement/suppliers | 200 | Поставщики — Админ | OK |
| /admin/delivery/dispatch | 200 | Отправка заказов — Админ | OK |
| /admin/feedback | 200 | Сообщения директору — Админ | OK |
| /admin/page | 200 | Редактор страниц — Админ | OK |
| /admin/plugin | 200 | Плагины и интеграции — Админ | OK |
| /admin/plugin/lamoda | 200 | Lamoda Parser — Админ | OK |
| /admin/settings/delivery | 200 | Настройки доставки — Админ | OK |
| /admin/settings/payment | 200 | Способы оплаты — Админ | OK |
| /admin/settings/seo | 200 | SEO настройки — Админ | OK |
| /admin/settings/triggers | 200 | Триггеры автоматизации — Админ | OK |
| /admin/sidebar-menu | 200 | Боковое меню (Sidebar) — Админ | OK |
| /admin/activity-log | 200 | Журнал действий — Админ | OK |
| /admin/amocrm | 200 | AmoCRM — интеграция — Админ | Shows "Ничего не найдено" (no data) |
| /admin/order-source | 200 | Источники заказов — Админ | OK |
| /admin/order-status | 200 | Статусы заказов — Админ | OK |
| /admin/customer/1 | 200 | Покупатель: Иванов Иван — Админ | OK |

---

## 2. PHP Errors Found

### BUG-1 (CRITICAL): /admin/poizon/errors — HTTP 500

**Error:** `Error: Class "app\backend\modules\catalog\models\ImportLog" not found`  
**File:** `backend/modules/admin/controllers/PoizonController.php:249`  
**Root cause:** Controller imports `use app\backend\modules\catalog\models\ImportLog;` (line 38) but the class lives at `app\backend\modules\admin\models\import\ImportLog` (confirmed: file exists at `backend/modules/admin/models/import/ImportLog.php`).  
**Impact:** The "Ошибки" tab in the Poizon import section is completely broken. Also affects `actionView()` (line 153) which uses the same class.  
**Fix required:** Change the `use` statement in PoizonController.php to the correct namespace.

---

### BUG-2 (CRITICAL): /admin/product/export — HTTP 500

**Error:** `Allowed memory size of 134217728 bytes exhausted (tried to allocate 20480 bytes) in yii\db\BaseActiveRecord.php:467`  
**Root cause:** The export action loads ALL products as ActiveRecord objects without pagination or chunked queries, hitting PHP's 128 MB memory limit on a large catalog.  
**Impact:** Product export is completely non-functional.  
**Fix required:** Use `query()->each()` or chunked processing; or increase `memory_limit` and use raw arrays/asArray().

---

### BUG-3 (HIGH): /admin/finance/create-payment — HTTP 500 ✅ FIXED

**Error:** `yii\base\ErrorException: Undefined array key "order_id"` in `FinanceController.php:70`  
**Root cause:** Line 70: `$payment->order_id = $data['order_id'] ? (int)$data['order_id'] : null;` — uses direct array access without null-coalescing guard (`??`). When `order_id` key is absent from the POST body (e.g. called from finance page without an order context), PHP 8 throws `Undefined array key`.  
**Same issue at line 147** in `actionCreateExpense()`.  
**Impact:** Creating a standalone payment or expense (not linked to an order) triggers a 500 error.  
**Fix applied:** Changed to `($data['order_id'] ?? null) ? (int)$data['order_id'] : null` (null-coalescing operator) for `order_id`, `customer_id`, `supplier_id`, `amount_original`, `exchange_rate`. Also added GET redirect guard in `actionCreateExpense()` so browser visits redirect to expenses list instead of 500.  
**File:** `backend/modules/admin/controllers/FinanceController.php` lines 70–71, 150–156.

---

### BUG-4 (HIGH): cart.items_count column does not exist

**Error (from app.log):**  
```
SQLSTATE[42S22]: Column not found: 1054 Unknown column 'items_count' in 'where clause'
SQL: SELECT COUNT(*) FROM `cart` WHERE (`updated_at` < ...) AND (`items_count` > 0)
```  
**Root cause:** Code queries `cart.items_count` but the `cart` table has no such column (confirmed via `DESCRIBE cart`). The cart table columns are: `id, user_id, session_id, product_id, quantity, price, size, color, created_at, updated_at`.  
**Impact:** The Marketing dashboard shows "Брошенных корзин: 0 — Нет данных" and "Восстановление корзин: 0.0%". The dashboard cart recovery widget is non-functional.  
**Fix required:** Either add `items_count` column via migration and maintain it, or rewrite the query to derive cart item count from existing columns (e.g. `SUM(quantity)` per session).

---

### BUG-5 (MEDIUM): Currency API — HTTP 404 on all currencies

**Error (from app.log):**  
```
GET https://api.nbrb.by/exrates/rates/USD  {"parammode":2}  → HTTP 404
GET https://api.nbrb.by/exrates/rates/EUR  {"parammode":2}  → HTTP 404
GET https://api.nbrb.by/exrates/rates/CNY  {"parammode":2}  → HTTP 404
GET https://api.nbrb.by/exrates/rates/RUB  {"parammode":2}  → HTTP 404
```  
**Root cause:** The NBRB API requires `parammode=2` as a **URL query parameter**, not a POST body. The request is `GET /exrates/rates/USD` but sends `{"parammode":2}` as JSON body — which the API ignores, returning 404 because it expects `?parammode=2`.  
**Impact:** Currency rates are never updated automatically. Import pricing (USD/EUR/CNY/RUB to BYN conversion) relies on stale cached rates or defaults.  
**Fix required:** Append `?parammode=2` to the URL: `GET /exrates/rates/USD?parammode=2`.

---

### BUG-6 (MEDIUM): SQL RANDOM() vs RAND() in CLI script

**Error (from app.log):**  
```
SQLSTATE[42000]: Syntax error or access violation: 1064 ...near '() LIMIT 5' at line 1
SQL: SELECT slug, name FROM product WHERE is_active=1 ORDER BY RANDOM() LIMIT 5
```  
**Root cause:** MySQL uses `RAND()`, not `RANDOM()` (which is PostgreSQL/SQLite). This is in a CLI/console command (not a web route).  
**Impact:** Any CLI command using random product selection will crash.  
**Fix required:** Change `RANDOM()` to `RAND()` in the relevant query.

---

## 3. Performance Issues

### PERF-1 (HIGH): /admin/procurement/receiving — 15 MB HTML response

- **Page size:** 14,990,831 bytes (~15 MB)
- **Table count:** 3,022 `<table>` elements (one per purchase order)
- **Root cause:** `ProcurementController::actionReceiving()` calls `PurchaseOrder::find()->with(['supplier', 'items'])->where(['IN', 'status', [STATUS_ORDERED, STATUS_TRANSIT]])->all()` — no pagination. There are currently **3,021** purchase orders in `ordered`/`transit` status.
- **Impact:** Page takes seconds to generate server-side, browsers struggle to render 15 MB of HTML, the page is effectively unusable at this scale.
- **Fix required:** Add pagination (e.g. 20 orders per page), or filter by date range, or add a search/filter by supplier before rendering.

---

## 4. Navigation / URL Mapping Issues

The task spec lists routes under `/admin/catalog/product`, `/admin/catalog/brand`, `/admin/catalog/category` — all return **404**. The actual working URLs are:

| Task-spec URL | Actual working URL |
|---|---|
| /admin/catalog/product | **/admin/product** |
| /admin/catalog/brand | **/admin/brand** |
| /admin/catalog/category | **/admin/category** |

These routes were never registered. The catalog module uses `/admin/catalog` as the product list (with filters), while brands and categories have their own flat controllers.

---

## 5. Minor Issues

### MINOR-1: /admin/poizon — page `<title>` is empty

- **Rendered title:** ` — Админ` (space before dash, no page name)
- **Root cause:** `PoizonController::actionIndex()` does not set `$this->title`. The view uses `$this->title` in `<h1>` and breadcrumb, but the controller renders without assigning it.
- **Fix:** Add `$this->view->title = 'Импорт Poizon';` in `actionIndex()`.

### MINOR-2: Admin brand/category images returning 404

From app.log, requests for `/admin/images/brands/adidas.png`, `/admin/images/brands/jordan.svg`, `/admin/images/categories/sneakers.jpg` all return 404. These are static assets referenced with an `/admin/` prefix, but the admin module does not serve static files from that path — images should be under `/images/brands/...` instead.

### MINOR-3: AmoCRM integration shows no data

`/admin/amocrm` loads (HTTP 200) but shows "Ничего не найдено" and "Invalid API key" is in the log (`yii\web\UnauthorizedHttpException: Invalid API key` in `api/controllers/AmocrmController.php:65`). This is a configuration issue (API key not set), not a code bug.

---

## 6. Pages with No Errors — Quick Confirmation

The following pages returned HTTP 200 with no PHP errors and correct page structure:

Dashboard, Orders list, Orders detail, Customer list, Customer detail, Catalog (product list), Brand management, Category management, Characteristic management, Coupon management, Returns, Review management (0 data), Marketing, Analytics, RFM Analytics, Finance (payments, expenses, margin, P&L), Delivery dispatch, Procurement (buyouts, suppliers, returns), Import, Plugins, Lamoda Parser, Settings (general, delivery, payment, SEO, triggers), Sidebar menu, Activity log, Feedback, Page editor, Order sources, Order statuses.

---

## 7. Score

| Category | Max | Score | Notes |
|---|---|---|---|
| HTTP availability (routes load) | 25 | 19 | 3 routes are 404 (wrong spec URLs), 2 are 500 |
| No PHP critical errors | 25 | 16 | 3 critical errors in production code paths |
| Silent backend errors (logs) | 20 | 12 | 4 logged errors (cart column, currency API, order_id, RANDOM) |
| Performance | 15 | 9 | Receiving page 15 MB; export OOM |
| UI completeness / navigation | 15 | 13 | Good coverage, minor title/image issues |
| **TOTAL** | **100** | **69** | |

---

## 8. Priority Fix List

1. **[P0]** ~~Fix `$data['order_id'] ?? null` in `FinanceController.php` lines 70 and 147~~ ✅ FIXED (see BUG-3)
2. **[P0]** Fix `use` import for `ImportLog` in `PoizonController.php` → fixes /admin/poizon/errors 500
3. **[P0]** Fix product export OOM — chunk with `->each()` or `->asArray()`
4. **[P1]** Add `items_count` migration for `cart` table OR rewrite query → fixes marketing cart widget
5. **[P1]** Fix NBRB currency API call — `?parammode=2` as query param → fixes currency rates
6. **[P1]** Add pagination to `/admin/procurement/receiving` → fixes 15 MB page issue
7. **[P2]** Fix `RANDOM()` → `RAND()` in CLI product random selection script
8. **[P2]** Set `$this->title = 'Импорт Poizon'` in `PoizonController::actionIndex()`
9. **[P3]** Fix admin brand/category image URLs (remove `/admin/` prefix)
10. **[P3]** Register `/admin/catalog/product`, `/admin/catalog/brand`, `/admin/catalog/category` as URL aliases if backward compatibility is needed

---

## 9. Additional Fixes Applied This Session

The following bugs were discovered and fixed during the browser-based sweep (ADM-R1 series) before this report was written.

| ID | Description | File | Status |
|----|-------------|------|--------|
| ADM-R1-03 | `actionCreateExpense()` 500 on GET + PHP8 undefined array keys | `FinanceController.php` | ✅ FIXED |
| ADM-R1-04 | `/admin/notification` always returned JSON `{"count":N}` in browser | `NotificationController.php` | ✅ FIXED |
| ADM-R1-05 | `/admin/export` returned 404 (route not registered) | `infrastructure/config/web.php` | ✅ FIXED |
| ADM-R1-06 | Payment methods in order view showed mojibake (double-encoded CP1252→UTF-8) | DB: `app_setting` id=1 | ✅ FIXED |
| ADM-R1-07 | Automation trigger name "МойСклад" was garbled (`ÐœÐ¾Ð¹ÑÐºÐ»Ð°Ð´...`) | DB: `automation_trigger` id=7 | ✅ FIXED |
| ADM-R1-08 | `/admin/order-history` returned 404 (route not registered) | `infrastructure/config/web.php` | ✅ FIXED |

**DB encoding notes (ADM-R1-06, ADM-R1-07):** Root cause was double-encoding — UTF-8 bytes stored as if they were CP1252 codepoints and then re-encoded as UTF-8. Fixed by reading with `character_set_results=binary`, reversing the encoding map byte-by-byte, writing back as proper UTF-8. Rows id=2, 3, 4, 10, 21, 35 in `app_setting` had pre-existing `?` placeholder corruption (data already lost before this audit). Row id=26 (company data) manually corrected to "ООАО «Белинвестбанк»".
