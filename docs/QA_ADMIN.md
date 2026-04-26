# Admin QA Report — 2026-04-26

Click-through QA of all admin panel pages. Tested via Chrome MCP: HTTP status inferred from page title/content (200 = admin page loads correctly, 404 = public 404 page shown), JS errors via `read_console_messages(onlyErrors:true)`, DOM error count via `.alert-danger / .error-summary / [class*="error"]` selector.

## Summary

- **Total pages checked:** 37
- **200 OK:** 34
- **404:** 2 (`/admin/customer/create`, `/admin/plugin/poizon`)
- **Redirect (301/302):** 1 (`/admin/statistics` → `/admin/analytics`)
- **JS bug fixed:** `ReferenceError: AdminTable is not defined` on `/admin/finance/expense` and `/admin/procurement` — caused by stale `frontend/web/js/admin-table-utils.js` (missing `initColSelector`) + script load order issue. Fixed by syncing backend→frontend and loading the script in `<head>`.

---

## Results Table

| URL | HTTP | JS errors | DOM errors | Visual | Severity | Notes |
|-----|------|-----------|------------|--------|----------|-------|
| /admin | 200 | 0 | 0 | OK | – | Dashboard loads, stats visible |
| /admin/order | 200 | 0 | 0 | OK | – | Order list, status counts visible |
| /admin/order/create | 200 | 0 | 0 | OK | – | New order wizard loads |
| /admin/order/4669 | 200 | 0 | 0 | OK | – | Order detail, status timeline OK |
| /admin/order/12 | 200 | 0 | 0 | OK | – | Older demo order loads fine |
| /admin/order/395 | 200 | 0 | 0 | OK | – | Order detail loads fine |
| /admin/order/return | 200 | 0 | 0 | OK | – | Returns list (empty) loads |
| /admin/customer | 200 | 0 | 0 | OK | – | Customer list, 11948 records |
| /admin/customer/create | 404 | – | – | 404 | minor | No create-customer route; redirected to public 404 |
| /admin/product | 200 | 0 | 0 | OK | – | Product catalog, 2769 products |
| /admin/product/2772 | 200 | 0 | 0 | OK | – | Product view page loads fine |
| /admin/category | 200 | 0 | 1 | OK | – | Flash msg from previous order attempt (stale session); not a page error |
| /admin/brand | 200 | 0 | 0 | OK | – | Brand list, 45 brands |
| /admin/characteristic | 200 | 0 | 0 | OK | – | Characteristics & sizes page OK |
| /admin/finance | 200 | 0 | 0 | OK | – | Payments list, totals visible |
| /admin/finance/pl | 200 | 0 | 0 | OK | – | P&L 2026 loads, revenue/margin figures shown |
| /admin/finance/expense | 200 | 0* | 0 | OK | minor | **Fixed:** `AdminTable is not defined` JS error (stale frontend JS + load order). Resolved by syncing `admin-table-utils.js` and loading it in `<head>`. |
| /admin/finance/margin | 200 | 0 | 0 | OK | – | Margin by product/manager loads |
| /admin/procurement | 200 | 0* | 0 | OK | minor | **Fixed:** same `AdminTable` JS error. Clean after fix. |
| /admin/procurement/buyouts | 200 | 0 | 0 | OK | – | Buyouts page, counters visible |
| /admin/procurement/suppliers | 200 | 0 | 0 | OK | – | Suppliers list loads |
| /admin/coupon | 200 | 0 | 0 | OK | – | Coupons list loads |
| /admin/campaign | 200 | 0 | 0 | OK | – | Marketing campaigns page loads |
| /admin/analytics | 200 | 0 | 0 | OK | – | Analytics & reports load |
| /admin/statistics | 301 | – | – | → analytics | – | Redirects to /admin/analytics (expected) |
| /admin/plugin | 200 | 0 | 0 | OK | – | Plugin/integrations index loads |
| /admin/plugin/amocrm | 200 | 0 | 0 | OK | – | AmoCRM integration page loads |
| /admin/plugin/lamoda | 200 | 0 | 0 | OK | – | Lamoda parser page loads |
| /admin/plugin/poizon | 404 | – | – | 404 | minor | No `actionPoizon` in PluginController. Actual route is `/admin/poizon/index`. |
| /admin/page | 200 | 0 | 0 | OK | – | Page editor loads |
| /admin/review | 200 | 0 | 0 | OK | – | Reviews management loads |
| /admin/feedback | 200 | 0 | 0 | OK | – | Director messages page loads |
| /admin/import | 200 | 0 | 0 | OK | – | Product import page loads |
| /admin/settings | 200 | 0 | 0 | OK | – | Settings index loads |
| /admin/settings/statuses | 200 | 0 | 0 | OK | – | Order statuses page loads |
| /admin/settings/payment | 200 | 0 | 0 | OK | – | Payment methods page loads |
| /admin/settings/delivery | 200 | 0 | 0 | OK | – | Delivery settings page loads |

_* = error existed before fix, 0 after fix_

---

## Bugs Fixed

### 1. `AdminTable is not defined` on `/admin/finance/expense` and `/admin/procurement`

**Root cause (two parts):**
1. `frontend/web/js/admin-table-utils.js` was an older version that lacked `initColSelector`, `toggleColSelector`, and `selectAllCols` methods. The newer version with these methods was only in `backend/web/js/`. The HTTP server (`router.php`) serves from `frontend/web/`, so the wrong file was being loaded.
2. Even with the correct file, `admin-table-utils.js` was last in `AdminAsset::$js`, so it was injected at `POS_END` (end of `<body>`) — after inline view scripts that call `AdminTable.initColSelector()`.

**Fix applied:**
- Copied `backend/web/js/admin-table-utils.js` → `frontend/web/js/admin-table-utils.js` (sync the authoritative version).
- Added `<script src="/js/admin-table-utils.js?v=...">` in `<head>` of `admin.php` layout (before `beginBody()`), so it is available when view inline scripts run.
- Removed `admin-table-utils.js` from `AdminAsset::$js` to avoid double-loading.

**Files changed:**
- `frontend/web/js/admin-table-utils.js` — synced to backend version
- `backend/modules/admin/views/layouts/admin.php` — script tag added in head
- `backend/modules/admin/assets/AdminAsset.php` — entry removed from js array

---

## Known Issues (not fixed)

| Issue | Severity | Notes |
|-------|----------|-------|
| `/admin/customer/create` returns 404 | minor | No admin create-customer route exists. Customers are created via public registration or order flow. If intentional, no action needed. |
| `/admin/plugin/poizon` returns 404 | minor | The task spec references this URL but actual route is `/admin/poizon/index`. If the sidebar links to the wrong URL, it should be corrected. |
