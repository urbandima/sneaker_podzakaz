# Procurement Buyout Module — Implementation Plan

## Overview

The Buyout module tracks orders placed on external marketplaces (Poizon, Таобао, Yupoo, etc.) on behalf of customers. It sits between `ProcurementOrder` (China supply) and customer `Order` (final delivery), bridging the sourcing and fulfillment phases.

---

## Database Schema

### `buyout` table

| Column              | Type              | Notes                                              |
|---------------------|-------------------|----------------------------------------------------|
| id                  | INT PK AI         |                                                    |
| number              | VARCHAR(32)       | Auto-generated, format: BUY-{YYYYMM}-{seq5}       |
| source              | VARCHAR(64)       | poizon / taobao / yupoo / manual / other           |
| source_url          | TEXT NULL         | Original listing URL                               |
| status              | VARCHAR(32)       | See status workflow below                          |
| total_cny           | DECIMAL(10,2)     | Total in CNY                                       |
| total_byn           | DECIMAL(10,2)     | Converted to BYN at rate snapshot                  |
| rate_snapshot       | DECIMAL(10,4)     | CNY/BYN rate used at creation                      |
| markup_pct          | DECIMAL(5,2)      | Markup percentage applied                          |
| weight_kg           | DECIMAL(8,3) NULL | Estimated weight in kg                             |
| delivery_cost       | DECIMAL(10,2)     | Delivery cost estimate                             |
| comment             | TEXT NULL         | Internal notes                                     |
| created_by          | INT NULL          | admin user id                                      |
| created_at          | INT               | Unix timestamp                                     |
| updated_at          | INT               | Unix timestamp                                     |

### `buyout_order_link` table

Links a Buyout to one or more customer Orders.

| Column      | Type   | Notes                   |
|-------------|--------|-------------------------|
| id          | INT PK |                         |
| buyout_id   | INT FK | → buyout.id             |
| order_id    | INT FK | → order.id              |
| linked_at   | INT    | Unix timestamp          |
| linked_by   | INT    | admin user id           |

### `buyout_history` table

Audit log for status transitions.

| Column      | Type         | Notes                         |
|-------------|--------------|-------------------------------|
| id          | INT PK       |                               |
| buyout_id   | INT FK       | → buyout.id                   |
| from_status | VARCHAR(32)  | Previous status                |
| to_status   | VARCHAR(32)  | New status                     |
| comment     | TEXT NULL    | Reason or note                 |
| changed_by  | INT NULL     | admin user id                  |
| changed_at  | INT          | Unix timestamp                 |

---

## Status Workflow

```
draft ──→ ordered ──→ in_transit ──→ arrived ──→ accepted
  │           │                                      │
  └──cancel──-┘◄──────────────────────────────cancel─┘
```

| Status      | Label              | Meaning                                     |
|-------------|-------------------|---------------------------------------------|
| draft       | Черновик           | Created but not yet ordered                 |
| ordered     | Заказан            | Order placed on external marketplace        |
| in_transit  | В пути             | Shipped from China, in transit              |
| arrived     | На складе          | Arrived at warehouse, pending receiving     |
| accepted    | Принят             | Received, linked to procurement/order       |
| cancelled   | Отменён            | Cancelled at any stage                      |

---

## Controller Routes

```
GET  /admin/buyout                → BuyoutController::actionIndex     (list with filters)
GET  /admin/buyout/create         → BuyoutController::actionCreate    (create form)
POST /admin/buyout/create         → BuyoutController::actionCreate    (save new)
GET  /admin/buyout/{id}           → BuyoutController::actionView      (detail view)
GET  /admin/buyout/{id}/edit      → BuyoutController::actionEdit      (edit form)
POST /admin/buyout/{id}/edit      → BuyoutController::actionEdit      (save changes)
POST /admin/buyout/{id}/accept    → BuyoutController::actionAccept    (status→accepted)
POST /admin/buyout/{id}/cancel    → BuyoutController::actionCancel    (status→cancelled)
POST /admin/buyout/parse-url      → BuyoutController::actionParseUrl  (AJAX: extract product info)
POST /admin/buyout/{id}/link-order    → BuyoutController::actionLinkOrder   (link customer order)
POST /admin/buyout/{id}/unlink-order  → BuyoutController::actionUnlinkOrder (unlink)
POST /admin/buyout/bulk-status    → BuyoutController::actionBulkStatus (batch status change)
GET  /admin/buyout/{id}/history   → BuyoutController::actionHistory   (status log)
```

---

## UI Spec

### List page (`/admin/buyout`)

- CRM-style table with columns: №, Источник, Сумма CNY, Сумма BYN, Статус, Заказы (count), Создан
- Filter bar: status, source, date range
- Status pills using admin-badge color tokens
- Quick actions: "Изменить статус", "Привязать заказ"

### View page (`/admin/buyout/{id}`)

Two-column layout (60/40):
- **Left (60%)**: Item details, source URL, price breakdown, status timeline
- **Right (40%)**: Linked customer orders, history log, actions

### Create page

Form fields: source (dropdown), source_url (with Parse button), total_cny, markup_pct, weight_kg, comment.
On save → status=draft.

---

## Integration with Receiving

When a Buyout reaches `arrived` status and is accepted (`actionAccept`):
1. Optionally create a `PurchaseOrder` record from buyout items
2. Link via `buyout_order_link` to the associated customer orders
3. Trigger status update on linked orders (e.g., `arrived_at_warehouse`)

---

## Parser Services

| Source   | Parser class              | Method                       |
|----------|--------------------------|------------------------------|
| Poizon   | `PoizonParser`            | `parse(string $url): array`  |
| Taobao   | `TaobaoParser`            | `parse(string $url): array`  |
| Yupoo    | `YupooParser`             | `parse(string $url): array`  |

Each returns: `['title' => ..., 'price_cny' => ..., 'images' => [...], 'source' => ...]`

---

## Implementation Phases

### Phase 1 (scaffold — this commit)
- DB migration draft
- Stub controller with empty actions
- View stubs with "В разработке" placeholder
- URL rules in web.php

### Phase 2 (models + basic CRUD)
- `Buyout`, `BuyoutOrderLink`, `BuyoutHistory` ActiveRecord models
- `BuyoutController::actionIndex` / `actionCreate` / `actionView`
- Status transitions with history logging

### Phase 3 (full UI + parsers)
- CRM-style list with filters and pagination
- Detail view with timeline, linked orders
- Parser integration (Poizon first)
- Bulk status operations

### Phase 4 (Receiving integration)
- Accept flow → create PurchaseOrder
- Trigger customer order status updates
- Warehouse receiving checklist
