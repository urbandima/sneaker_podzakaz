# Service Extraction Plan

> **Status:** Planning  
> **Created:** 2026-04-15  
> **Context:** The admin module controllers currently contain heavy business logic that should live in dedicated service classes. This document describes what to extract, from where, and the proposed service API for each.

---

## Overview

| Service | Source | Priority | Estimated lines extracted |
|---|---|---|---|
| `OrderService` | `OrderController` | **High** | ~600 |
| `LoyaltyService` | `LoyaltyPoints` model | Medium | ~300 |
| `ProductService` | `ProductController` | Medium | ~400 |
| `TrackingService` | Already exists ✅ | Low (document only) | — |

---

## 1. OrderService

**Target path:** `backend/modules/admin/services/OrderService.php`

### Why

`OrderController` at `backend/modules/admin/controllers/OrderController.php` exceeds 1 500 lines. Most of the bulk is business logic (price calculation, status machine, DP API calls) tightly coupled to the HTTP layer. The same logic cannot be reused from console commands, webhooks, or tests without bootstrapping the full controller.

### Methods to extract

| Method | Source action | Notes |
|---|---|---|
| `createOrder(array $data): Order` | `actionCreate` (line 332) | Validates, creates Order + OrderItems, fires events |
| `updateOrder(Order $order, array $data): bool` | `actionUpdate` (line 423) | Handles field updates + recalc |
| `updateStatus(Order $order, string $status, ?string $comment): bool` | `actionUpdateStatus` (line 781) | Status machine validation, history record, Telegram notify |
| `saveItems(Order $order, array $items): bool` | `actionUpdateItems` (line 1540) | Upsert OrderItem rows, recalculate total |
| `sendToDP(Order $order): array` | `actionSendToDp` (line 1416) | Calls DobropostService, stores tracking number |
| `exportCsv(Query $query): string` | `actionExportCsv` (line 960) | Returns CSV string, no HTTP concerns |
| `exportMonthly(int $month, int $year): string` | `actionExport` (line 587) | Same pattern |

### Proposed interface

```php
namespace app\modules\admin\services;

class OrderService
{
    public function createOrder(array $data): Order;
    public function updateOrder(Order $order, array $data): bool;
    public function updateStatus(Order $order, string $status, ?string $comment = null): bool;
    public function saveItems(Order $order, array $items): bool;
    public function sendToDP(Order $order): array;  // ['success'=>bool, 'trackingNumber'=>string|null, 'error'=>string|null]
    public function exportCsv(ActiveQuery $query): string;
    public function exportMonthly(int $month, int $year, ?int $logistId = null): string;
}
```

### Migration steps

1. Create `OrderService` with constructor DI for `DobropostService`, `TelegramService`.
2. Copy business logic from each action into the corresponding service method.
3. Replace action body with: validate input → call service → return response.
4. Add unit tests for `updateStatus()` state machine edge cases.
5. Verify existing integration tests still pass.

---

## 2. LoyaltyService

**Target path:** `backend/modules/admin/services/LoyaltyService.php`

### Why

Business logic for the loyalty programme currently lives as static methods on the `LoyaltyPoints` ActiveRecord model at `backend/modules/loyalty/models/LoyaltyPoints.php`. Static methods on AR models are hard to mock and mix persistence with business rules.

### Methods to extract (wrap, not move)

The static methods are well-named and the logic is mostly correct; the main issue is testability and coupling. The service wraps them and adds orchestration (e.g., notifying the customer after redemption).

| Service method | Delegates to | Notes |
|---|---|---|
| `earn(int $customerId, int $points, string $type, ...): bool` | `LoyaltyPoints::earn()` (line 150) | Add post-earn notification hook |
| `redeem(int $customerId, int $points, ?int $orderId): bool` | `LoyaltyPoints::redeem()` (line 200) | Validate sufficient balance first |
| `getBalance(int $customerId): int` | `LoyaltyPoints::getBalance()` (line 113) | Reads `balance_after` of last record |
| `getTotalEarned(int $customerId): int` | `LoyaltyPoints::getTotalEarned()` (line 131) | For loyalty tier calculation |
| `getHistory(int $customerId, int $limit): array` | `LoyaltyPoints::getHistory()` (line 408) | |
| `earnForPurchase(int $customerId, float $amount, int $orderId, float $multiplier): bool` | `LoyaltyPoints::earnForPurchase()` (line 250) | |
| `expireOldPoints(): int` | `LoyaltyPoints::expireOldPoints()` (line 327) | Called from console |

### Proposed interface

```php
namespace app\modules\admin\services;

class LoyaltyService
{
    public function earn(int $customerId, int $points, string $type, ?int $orderId = null, string $description = ''): bool;
    public function redeem(int $customerId, int $points, ?int $orderId = null, string $description = ''): bool;
    public function getBalance(int $customerId): int;
    public function getTotalEarned(int $customerId): int;
    public function getHistory(int $customerId, int $limit = 50): array;
    public function earnForPurchase(int $customerId, float $orderAmount, int $orderId, float $multiplier = 1.0): bool;
    public function expireOldPoints(): int;
}
```

### Migration steps

1. Create `LoyaltyService`; inject nothing (pure logic, uses AR internally for now).
2. Each method is a thin wrapper calling the existing static method.
3. In `CustomerController`, replace direct `LoyaltyPoints::earn(...)` calls with `$this->loyaltyService->earn(...)`.
4. Register `LoyaltyService` in DI container (`Yii::$container->setSingleton`).
5. Later: replace AR static calls inside service with a `LoyaltyRepository`.

---

## 3. ProductService

**Target path:** `backend/modules/admin/services/ProductService.php`

### Why

`ProductController` at `backend/modules/admin/controllers/ProductController.php` mixes import/export file handling, Poizon API calls, price recalculation, and size management — all inside action methods. This makes the bulk-update and import logic impossible to reuse from the console importer.

### Methods to extract

| Method | Source action | Notes |
|---|---|---|
| `updateProduct(Product $product, array $data): bool` | `actionEdit` (line 238) | Saves fields, updates `updated_at` |
| `syncFromPoizon(Product $product): array` | `actionSync` (line 349) | Calls Poizon API, updates images/sizes |
| `syncPoizonBatch(array $productIds): array` | `actionSyncPoizon` (line 977) | Bulk version |
| `addSize(Product $product, array $sizeData): ProductSize` | `actionAddSize` (line 378) | |
| `saveSizesData(Product $product, array $grid): bool` | `actionSaveSizesData` (line 927) | Grid-format size upsert |
| `updateSizePrice(ProductSize $size, float $price): bool` | `actionUpdateSizePrice` (line 959) | Recalc with markup |
| `bulkUpdatePrices(array $productIds, float $markupPercent): int` | `actionBulkUpdatePrice` (line 851) | Returns count of updated |
| `exportCsv(ActiveQuery $query): string` | `actionExportCsv` (line 871) | Returns CSV string |
| `exportXlsx(ActiveQuery $query): string` | `actionExport` (line 634) | Returns file path |

### Proposed interface

```php
namespace app\modules\admin\services;

class ProductService
{
    public function updateProduct(Product $product, array $data): bool;
    public function syncFromPoizon(Product $product): array;  // ['updated'=>bool, 'changes'=>array]
    public function syncPoizonBatch(array $productIds): array; // ['success'=>int, 'failed'=>int]
    public function addSize(Product $product, array $sizeData): ProductSize;
    public function saveSizesData(Product $product, array $grid): bool;
    public function updateSizePrice(ProductSize $size, float $price): bool;
    public function bulkUpdatePrices(array $productIds, float $markupPercent): int;
    public function exportCsv(ActiveQuery $query): string;
    public function exportXlsx(ActiveQuery $query): string;
}
```

### Migration steps

1. Create `ProductService`; inject `PoizonApiClient` (or resolve from DI).
2. Move price-recalculation formulas out of action methods into `ProductService`.
3. Console import command (`backend/console/controllers/ProductController.php`) starts calling `ProductService` instead of duplicating logic.
4. Add tests for `bulkUpdatePrices()` with a fixture product set.

---

## 4. TrackingService (already exists — document connections)

**Existing path:** `backend/modules/checkout/services/TrackingService.php`

### Current API

```php
trackOrder(int $orderId): ?array          // dispatches to carrier-specific tracker
trackByNumber(string $number, ?string $carrier): array
updateTracking(int $trackingId): bool      // updates one OrderTracking record
getTrackingHistory(int $orderId): array
updateAllActive(): int                     // called by console cron
getTrackingUrl(string $number, string $carrier): ?string
```

### Current connections

| Consumer | How it uses TrackingService |
|---|---|
| `OrderController::actionUpdateField` | Reads tracking history for display |
| `ShippingController::actionDispatch` | Calls `trackOrder()` after dispatch |
| `console/controllers/TrackingController` | Calls `updateAllActive()` via cron |
| `EuropochtatrackingComponent` | Provides raw events; `TrackingService` aggregates |
| `BelpochtaTrackingComponent` | Same |
| `CdekTrackingComponent` | Same |
| `DobropostService` | Separate service; `TrackingService` reads its records |

### Gaps / TODO

- `TrackingService` currently resolves carrier components by string name inside a `switch`. Should instead use a `CarrierInterface` registry (already planned in `SYSTEM_MAP.md`).
- No event fired after status changes (`TrackingService::updateTracking` should fire `OrderTrackingUpdatedEvent` so `TelegramService` can subscribe).
- `getTrackingUrl()` is duplicated in `EuropochtatrackingComponent` — consolidate.

---

## Extraction Order

1. **`TrackingService`** — already exists, add the event emission and consolidate `getTrackingUrl`. Low risk.
2. **`LoyaltyService`** — thin wrappers, low risk, immediate testability win.
3. **`OrderService::updateStatus`** — highest value; current implementation has 3 separate Telegram call sites that can be unified.
4. **`ProductService::syncFromPoizon`** + `bulkUpdatePrices` — unblock console reuse.
5. Everything else in `OrderService` and `ProductService` — fill in as controllers are touched.

---

## DI Registration (when ready)

Add to `backend/config/di.php` (or `common/config/di.php`):

```php
\app\modules\admin\services\OrderService::class   => \app\modules\admin\services\OrderService::class,
\app\modules\admin\services\LoyaltyService::class => \app\modules\admin\services\LoyaltyService::class,
\app\modules\admin\services\ProductService::class => \app\modules\admin\services\ProductService::class,
// TrackingService already registered in checkout module config
```

Inject into controllers via constructor or property:

```php
public function __construct(
    $id, $module,
    private readonly OrderService $orderService,
    array $config = []
) {
    parent::__construct($id, $module, $config);
}
```
