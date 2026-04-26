# Refactoring Priorities

*Generated 2026-04-15. Based on static analysis + live DB audit.*

---

## 1. Dead Order View Files — 5 competing templates

**Files:** `order/view.php`, `view-new.php`, `view-new-style.php`, `view-wizard.php`, `view-wizard-new.php`

Five separate full-page templates for the same order detail screen. Production uses `view.php`; the others appear to be iterations that were never deleted. Each is 600–1 000 lines of duplicated PHP/HTML, meaning bug fixes must be applied 5× or quietly diverge.

**Action:** Delete `view-new.php`, `view-new-style.php`, `view-wizard.php`, `view-wizard-new.php` after confirming no route points to them.

---

## 2. Fat Controllers — extract a Service Layer

**Files:** `admin/controllers/OrderController.php` (1 566 lines), `admin/controllers/ProductController.php` (992 lines), `checkout/controllers/OrderController.php` (853 lines)

Business logic (tariff calculation, auto-create customer, loyalty point math, DobroPost API calls) lives directly in controllers. This makes unit testing impossible and makes both controllers fragile to change.

**Action:** Extract `OrderService`, `ProductService`, `LoyaltyService` classes under `backend/services/`. Controllers become thin: validate input → call service → return response.

---

## 3. Missing DB Indexes on Hot Query Paths

Current gaps (verified against live schema):

| Table | Missing index | Used in |
|-------|--------------|---------|
| `order` | `(client_email)`, `(client_phone)` | Admin customer search |
| `order_item` | `(product_id)` | Product sales reports |
| `product_size` | `(is_available, stock)` | Catalog availability filter |

```sql
ALTER TABLE `order` ADD INDEX idx_order_client_email (client_email(64));
ALTER TABLE `order` ADD INDEX idx_order_client_phone (client_phone(20));
ALTER TABLE order_item ADD INDEX idx_oi_product_id (product_id);
ALTER TABLE product_size ADD INDEX idx_ps_avail_stock (is_available, stock);
```

---

## 4. N+1 Queries in Order Listing

`admin/controllers/OrderController::actionIndex()` loads orders via `ActiveDataProvider`, then each order row in the view accesses `$order->customer`, `$order->orderItems`, and `$order->tariff` as lazy relations. With 385 orders this is ~4 queries/row = ~1 540 queries per page load.

**Action:** Add `->with(['customer', 'orderItems', 'tariff'])` to the base query in `actionIndex()`.

---

## 5. CSRF Disabled for AJAX Without Token Enforcement

**Files:** `CartController`, `CustomerController`, `SettingsController`

These controllers set `$this->enableCsrfValidation = false` for AJAX actions. The cart comment claims the token is checked via `X-CSRF-Token` header through `SH.fetch`, but Yii2's built-in validation is off and there is no explicit `validateCsrfToken()` call in these actions. An attacker on the same origin can trigger state-changing cart/customer mutations with a forged form.

**Action:** Remove `enableCsrfValidation = false` and ensure `SH.fetch` always sends `X-CSRF-Token`. Yii2 reads this header automatically when CSRF validation is enabled. Verified: `SH.fetch` in `utils.js` already attaches the header — so enabling Yii2 validation is safe.
