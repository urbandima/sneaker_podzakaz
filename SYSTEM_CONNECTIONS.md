# System Connections Map

Generated: 2026-04-18. All FK constraints verified in DB.

---

## Core: Order

```
order
  ├── customer_id  ──FK──▶ customer.id           (SET NULL on delete)
  ├── tariff_id    ──FK──▶ tariff.id
  ├── created_by         → user.id               (no FK — soft ref)
  ├── assigned_logist    → user.id               (no FK — soft ref)
  └── moysklad_id        = external UUID in МойСклад

order ──◀── order_item.order_id           FK CASCADE DELETE
order ──◀── order_history.order_id        FK CASCADE DELETE
order ──◀── payment.order_id              FK SET NULL
order ──◀── delivery_tracking.order_id   FK CASCADE DELETE
order ──◀── return_request.order_id      FK RESTRICT
order ──◀── expense.order_id              FK SET NULL
order ──◀── purchase_order.order_id      FK SET NULL  (for preorders only)
order ──◀── loyalty_points.order_id      (no FK — soft ref)
```

**Model:** `backend/modules/checkout/models/Order.php`  
**Relations defined:** `getCustomer()`, `getOrderItems()`, `getHistory()`, `getPayments()`,
`getDeliveryTracking()`, `getReturnRequests()`, `getPurchaseOrders()`

---

## Customer

```
customer
  ├── ◀── order.customer_id               FK (nullable)
  ├── ◀── payment.customer_id             FK SET NULL
  ├── ◀── return_request.customer_id      FK RESTRICT
  ├── ◀── loyalty_points.customer_id      FK CASCADE DELETE
  └── ◀── customer_social_account.customer_id  FK
```

**Model:** `backend/modules/account/models/Customer.php`  
**Relations defined:** `getOrders()`, `getLoyaltyPoints()`, `getPayments()`, `getReturnRequests()`

---

## Product

```
product
  ├── brand_id     ──FK──▶ brand.id
  ├── category_id  ──FK──▶ category.id
  ├── ◀── product_size.product_id          FK
  ├── ◀── product_image.product_id         FK
  ├── ◀── product_stock.product_id         FK
  ├── ◀── product_color.product_id         FK
  ├── ◀── product_characteristic.product_id  FK
  ├── ◀── product_tag_assignment.product_id  FK
  ├── ◀── product_related.product_id        FK
  ├── ◀── order_item.product_id             FK SET NULL
  └── ◀── purchase_order_item.product_id   (no FK — soft ref)
```

**Model:** `backend/modules/catalog/models/Product.php`

---

## Procurement

```
supplier
  ├── ◀── purchase_order.supplier_id       FK RESTRICT
  ├── ◀── supplier_return.supplier_id      FK RESTRICT
  └── ◀── expense.supplier_id              FK SET NULL

purchase_order
  ├── supplier_id  ──FK──▶ supplier.id
  ├── order_id     ──FK──▶ order.id (SET NULL) — links preorder to customer order
  ├── ◀── purchase_order_item.purchase_order_id  FK CASCADE DELETE
  └── ◀── supplier_return.purchase_order_id      FK RESTRICT

purchase_order_item
  ├── purchase_order_id  ──FK──▶ purchase_order.id
  └── ◀── supplier_return_item.purchase_order_item_id  FK SET NULL

supplier_return
  ├── purchase_order_id  ──FK──▶ purchase_order.id
  ├── supplier_id        ──FK──▶ supplier.id
  └── ◀── supplier_return_item.supplier_return_id  FK CASCADE DELETE
```

**Models:**
- `backend/modules/procurement/models/Supplier.php`
- `backend/modules/procurement/models/PurchaseOrder.php`
- `backend/modules/procurement/models/PurchaseOrderItem.php`
- `backend/modules/procurement/models/SupplierReturn.php`
- `backend/modules/procurement/models/SupplierReturnItem.php`

**Controllers:** `backend/modules/admin/controllers/ProcurementController.php`

---

## Finance

```
payment
  ├── order_id     ──FK──▶ order.id (SET NULL)
  └── customer_id  ──FK──▶ customer.id (SET NULL)

expense
  ├── order_id    ──FK──▶ order.id (SET NULL)
  └── supplier_id ──FK──▶ supplier.id (SET NULL)

Categories: purchase | delivery_china | customs | delivery_local | rent | salary | other
```

**Models:**
- `backend/modules/finance/models/Payment.php`
- `backend/modules/finance/models/Expense.php`

**Controller:** `backend/modules/admin/controllers/FinanceController.php`  
**Access:** role = admin OR director (`financeOnly = true`)

---

## Delivery & Tracking

```
delivery_tracking
  └── order_id  ──FK──▶ order.id (CASCADE DELETE)

order (inline fields):
  - china_track_number   — tracking from China supplier
  - dp_track_number      — DobroPost Belarus tracking
  - dp_shipment_id       — DobroPost shipment UUID
  - dp_submission_at     — timestamp when submitted to DobroPost

delivery_provider ──◀── delivery_status_mapping.provider_id  FK
```

**Note:** 377 orders have `dp_track_number` set. `delivery_tracking` table holds 
carrier status events from polling; currently empty (no polling service running).

---

## Returns (Customer → Us)

```
return_request
  ├── order_id     ──FK──▶ order.id    RESTRICT
  ├── customer_id  ──FK──▶ customer.id RESTRICT
  └── items_json   — JSON array of returned items (no separate table)
```

**Model:** `backend/modules/returns/models/ReturnRequest.php`  
**Controller:** `backend/modules/admin/controllers/ReturnController.php`

---

## Loyalty

```
loyalty_points
  ├── customer_id         ──FK──▶ customer.id (CASCADE DELETE)
  ├── order_id            (soft ref, no FK)
  └── related_customer_id (soft ref, referral program)

customer_loyalty_level — tier thresholds
loyalty_level          — tier definitions
```

**Model:** `backend/modules/loyalty/models/LoyaltyPoints.php`

---

## Automation

```
automation_trigger ──◀── automation_log.trigger_id  FK
sms_template — used by automation 'send_sms' action

Action types:
  send_sms        → SmsTemplate + RocketSMS API
  send_telegram   → Telegram Bot API
  change_status   → Order.status update
  assign_logist   → Order.assigned_logist update
  send_dobropost  → DobroPost API
  sync_moysklad   → MoySkladService.pushOrder()
```

**Models:**
- `backend/modules/automation/models/AutomationTrigger.php`
- `backend/modules/automation/models/AutomationLog.php`
- `backend/modules/automation/models/SmsTemplate.php`

**Service:** `backend/modules/automation/services/AutomationEngine.php`

---

## MoySklad Integration

```
order.moysklad_id  — UUID of corresponding customerorder in МС
app_setting:
  moysklad.login / password / api_key
  moysklad.org_id
  moysklad.status_map  — JSON: our_status → МС state name
  moysklad.sync_log    — JSON array of last 20 sync events

Sync flows:
  Push:    Our changes → МС (on order save / bulk action)
  Pull:    МС changes → Us (via cron / manual trigger)
  Webhook: МС notifies us → admin/moysklad/webhook
```

**Service:** `backend/shared/services/MoySkladService.php`  
**Controller:** `backend/modules/admin/controllers/MoyskladController.php`

---

## Admin Logging

```
admin_log
  - entity_type / entity_id / entity_name
  - action (create/update/delete/status_change/export/import/bulk/view)
  - user_id / username / ip_address
  - old_values / new_values (JSON)

order_history
  - order_id  ──FK──▶ order.id (CASCADE)
  - action (status_changed / field_changed / note_added / item_added / etc.)
  - old_value / new_value / field_name
  - user_name / ip_address
```

**Services:**
- `backend/modules/admin/services/AdminLogService.php`
- Static helper: `OrderHistory::log($orderId, $action, $field, $old, $new)`

---

## Role-Based Access

```
user.role values:
  admin    — full access to everything
  director — same as admin for finance + procurement; cannot manage users
  manager  — orders, products, customers
  logist   — assigned orders, procurement/receiving

Access guards in BaseAdminController:
  $adminOnly   = true  → role IN (admin, director)    [via isAdmin()]
  $financeOnly = true  → role IN (admin, director)    [via canAccessFinance()]
  $procureOnly = true  → role IN (admin, director, logist) [via canAccessProcurement()]
```

**Model:** `backend/modules/admin/models/User.php`  
**Base:** `backend/modules/admin/controllers/BaseAdminController.php`

---

## Data Quality Notes (2026-04-18)

| Issue | Count | Cause | Action |
|-------|-------|-------|--------|
| Orders without `customer_id` | 384/385 | All are `status=imported`; importer doesn't create Customer rows | Acceptable — importer creates orders without customer accounts |
| Orders without `order_item` | 375/385 | Same imported orders — CSV import doesn't populate items | Acceptable — imported orders are reference data |
| `delivery_tracking` empty | 0 rows | No polling service running | Tracking events populated manually or by future cron |
| `order_history` sparse | 6 rows | System recently deployed; history accumulates from now | OK |

---

## URL Routing Summary

| Section | Base URL | Controller |
|---------|----------|------------|
| Finance | `/admin/finance/*` | `FinanceController` |
| Procurement | `/admin/procurement/*` | `ProcurementController` |
| MoySklad | `/admin/plugin/moysklad/*` | `MoyskladController` |
| Activity Log | `/admin/activity-log` | `ActivityLogController` |
| Automation | `/admin/settings/triggers/*` | `AutomationController` |
| Returns (customer) | `/admin/return/*` | `ReturnController` |
| Orders | `/admin/order/*` | `OrderController` |
| Products | `/admin/product/*` | `ProductController` |
| Customers | `/admin/customer/*` | `CustomerController` |
