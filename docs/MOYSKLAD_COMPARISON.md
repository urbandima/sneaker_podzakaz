# МойСклад vs СникерХэд — Feature Comparison

**Date:** 2026-04-18  
**МойСклад account:** admin@sneakerculture / Сникеркультура  
**Total orders in МойСклад:** 10,340  
**Source:** Live web interface inspection + codebase analysis

---

## МойСклад Module Map (observed via web interface)

| Module | Sub-sections |
|--------|-------------|
| **Показатели** | Dashboard / KPIs |
| **Закупки** | Заказы поставщикам, Счета поставщиков, Приемки, Возвраты поставщикам, Счета-фактуры полученные, Управление закупками |
| **Продажи** | Заказы покупателей, Счета покупателям, Отгрузки, Отчеты комиссионера, Возвраты покупателей, Счета-фактуры выданные, Прибыльность, Товары на реализации, Воронка продаж, Юнит-экономика |
| **Товары** | Товары и услуги, Прайс-листы, Серийные номера, Коды маркировки, Маркировка |
| **CRM** | Контрагенты, Договоры, Звонки, Скидки, Операции с баллами |
| **Склад** | Оприходования, Списания, Перемещения, Инвентаризации, Волны отбора, Внутренние заказы, Остатки, Обороты, Склады |
| **Деньги** | Платежи (приход/расход/перемещение), Движение денежных средств, Прибыли и убытки, Взаиморасчеты, Начисления зарплаты, Корректировки |
| **Розница** | Розничные продажи, POS |
| **Онлайн-торговля** | Интеграции с маркетплейсами |
| **Производство** | Производственные задания |
| **Задачи** | Task management (26 задач активно) |
| **Решения** | Маркетплейс приложений |

---

## 1. Orders Management

| Feature | МойСклад | СникерХэд | Gap |
|---------|----------|-----------|-----|
| Order list with filters | ✅ Full (payment status, shipping status, return status, date, counterparty, project, channel) | ✅ Basic (status, date, search) | ⚠️ Partial |
| Order status flow | ✅ Configurable states (Оплачено, Новый, Проведено, Резерв + custom) | ✅ Fixed statuses (new/paid/ordered/…/delivered) | ⚠️ No custom statuses |
| Order detail — standard fields | ✅ Org, counterparty, contract, plan ship date, project, sales channel, delivery address, currency | ✅ Most fields present | ✅ |
| Custom order fields | ✅ Yes — Sneaker Culture has: Тип доставки, Ссылка на товар, Ссылка на накладную СДЭК/Европочта, Перенесено в таблицу паспортов, Ссылка на сделку (AmoCRM), Размер, Дата отмены/возврата | ⚠️ Fields hardcoded in DB schema | ❌ No runtime custom fields |
| Line items (positions) | ✅ Multiple products per order with qty, price, discount | ✅ `order_item` table | ✅ |
| Product reservation | ✅ Reserve stock from order | ⚠️ MySkladController::actionReserve() exists but not wired to UI | ❌ Not surfaced |
| Related documents | ✅ Links to shipments, invoices, payments, returns | ⚠️ Only ms_number stored | ❌ No document chain |
| Order PDF/print | ✅ Multiple print templates | ✅ TCPDF/HTML | ⚠️ Single template |
| Order export | ✅ Excel + filters | ✅ CSV + monthly Excel | ⚠️ No date-range Excel |
| Total orders | 10,340 | 472 in our DB | — |

**Custom fields used in МойСклад orders that we don't have in our schema:**
- `Тип доставки` (delivery type — separate from `delivery_method`)
- `Ссылка на накладную СДЭК / Европочта` (we have `local_track_number` but not a full invoice link)
- `Перенесено в таблицу паспортов` (boolean flag for passport export workflow)
- `Ссылка на сделку` (AmoCRM deal link — CRM integration)
- `Дата приезда в магазин за заказом` (pickup appointment date — not in our model)
- `Причина отказа` (cancellation reason — not in our model)
- `Заказ создан через виджет` (order source flag)

---

## 2. Products / Inventory

| Feature | МойСклад | СникерХэд | Gap |
|---------|----------|-----------|-----|
| Product catalog | ✅ Товары, Услуги, Комплекты, Группы | ✅ Products + categories | ✅ |
| Product variants (sizes) | ✅ Via modifications (Бренд, Размерная сетка fields) | ✅ `product_size` table (EU/US/UK/CM) | ✅ |
| Multiple price lists | ✅ Полная цена, Цена продажи, Цена распродажи (для своих), custom price types | ⚠️ Single `price` field + `old_price` | ❌ No role-based pricing |
| Serial numbers | ✅ Full tracking | ❌ Not implemented | ❌ |
| Marking / labelling | ✅ Коды маркировки, Маркировка (ЕГАИС/Честный знак) | ❌ Not relevant for BY | — |
| Stock levels | ✅ Real-time остатки by warehouse | ⚠️ `product_size.stock` column but not auto-decremented | ❌ Manual only |
| Stock movements | ✅ Оприходования, Списания, Перемещения, Инвентаризации | ❌ No movement tracking | ❌ |
| Multi-warehouse | ✅ Основной склад + Склад Б/У (observed) | ❌ No warehouses | ❌ |
| Import/Export | ✅ Full Excel import/export | ✅ Import from Excel/CSV (ImportController) | ✅ |

---

## 3. CRM / Customers

| Feature | МойСклад | СникерХэд | Gap |
|---------|----------|-----------|-----|
| Customer list | ✅ With balance, profit, avg check, sales count, return count, discount amount | ✅ Basic list with search | ⚠️ Missing financial metrics |
| Customer fields | ✅ ИНН, КПП, дата рождения, пол, дисконтная карта, баллы, цены, тип, группа | ✅ Phone, email, address, loyalty | ⚠️ Missing: ИНН, дата рождения, discount card |
| Customer balance tracking | ✅ Real-time (we owe: 469 BYN on order 00472) | ❌ No debt tracking | ❌ |
| Discount system | ✅ Скидки section, rule-based | ✅ `coupon` table + `customer_loyalty_level` | ⚠️ No auto discounts by customer group |
| Loyalty points | ✅ Операции с баллами, баллы на карте | ✅ `loyalty_points` table | ✅ |
| Contracts (Договоры) | ✅ Per-customer contracts with payment terms | ❌ Not implemented | ❌ |
| Calls log | ✅ Звонки section (call records per customer) | ❌ No call logging | ❌ |
| Customer groups | ✅ Группа контрагента, owner assignment | ⚠️ No groups (just loyalty levels) | ❌ |
| Tasks per customer | ✅ Задачи (26 active tasks visible) | ❌ No task system | ❌ |
| Communication history | ✅ События (events log per order/customer) | ⚠️ Order status log only | ❌ No global customer timeline |

---

## 4. Finance

| Feature | МойСклад | СникерХэд | Gap |
|---------|----------|-----------|-----|
| Incoming payments | ✅ Full payment tracking with counterparty, purpose, account | ⚠️ Payment screenshot upload only (manual confirmation) | ❌ No payment ledger |
| Outgoing payments | ✅ Расход payments to suppliers | ❌ Not tracked | ❌ |
| Cash flow report | ✅ Движение денежных средств | ❌ Not implemented | ❌ |
| P&L report | ✅ Прибыли и убытки | ❌ Not implemented | ❌ |
| Mutual settlements | ✅ Взаиморасчеты (debt tracking per counterparty) | ❌ Not implemented | ❌ |
| Payroll | ✅ Начисления зарплаты | ❌ Not implemented | ❌ |
| Multi-account | ✅ Основной счет, Счет МТБАНК, separate incoming filters | ❌ Single payment flow | ❌ |
| Invoices to customers | ✅ Счета покупателям (formal invoices) | ❌ PDF only, no formal invoice entity | ❌ |
| VAT / Счета-фактуры | ✅ Full VAT document management | ❌ Not relevant for current setup | — |

---

## 5. Warehouse Operations

| Feature | МойСклад | СникерХэд | Gap |
|---------|----------|-----------|-----|
| Stock receipts (Оприходования) | ✅ Record incoming stock with documents | ❌ | ❌ |
| Write-offs (Списания) | ✅ Write off damaged/lost stock | ❌ | ❌ |
| Stock transfers (Перемещения) | ✅ Between warehouses (Основной → Склад Б/У observed) | ❌ | ❌ |
| Inventory counts | ✅ Инвентаризации (full reconciliation) | ❌ | ❌ |
| Pick waves (Волны отбора) | ✅ Batch picking for fulfillment | ❌ | ❌ |
| Internal orders | ✅ Внутренние заказы | ❌ | ❌ |
| Stock by warehouse report | ✅ Остатки, Обороты | ⚠️ Per-product stock column only | ❌ |

---

## 6. Reports & Analytics

| Feature | МойСклад | СникерХэд | Gap |
|---------|----------|-----------|-----|
| Sales funnel | ✅ Воронка продаж | ❌ | ❌ |
| Unit economics | ✅ Юнит-экономика | ❌ | ❌ |
| Profitability | ✅ Прибыльность (margin per product/order/period) | ❌ No margin tracking | ❌ |
| Dashboard KPIs | ✅ Показатели (revenue, orders, avg check, etc.) | ✅ Admin dashboard with charts | ⚠️ Less detail |
| RFM analysis | ❌ Not native (needs integration) | ✅ `/admin/analytics/rfm` | ✅ We're ahead |
| Customer analytics | ✅ Avg check, sales count, return count per customer | ⚠️ Basic order history | ⚠️ |
| Mutual settlements report | ✅ Debt aging | ❌ | ❌ |
| Cash flow statement | ✅ | ❌ | ❌ |

---

## 7. Procurement (Закупки)

| Feature | МойСклад | СникерХэд | Gap |
|---------|----------|-----------|-----|
| Purchase orders to suppliers | ✅ Full PO lifecycle with custom fields (Ссылка на товар, Размер, Не принятый товар) | ❌ No PO system | ❌ |
| Supplier invoices | ✅ Счета поставщиков | ❌ | ❌ |
| Goods receipt | ✅ Приемки (goods receipt against PO) | ❌ | ❌ |
| Supplier returns | ✅ Возвраты поставщикам | ❌ | ❌ |
| Purchase management dashboard | ✅ Управление закупками | ❌ | ❌ |

---

## 8. Integrations

| Feature | МойСклад | СникерХэд | Gap |
|---------|----------|-----------|-----|
| AmoCRM | ✅ Deal link in order (observed: `https://dalmatinets102.amocrm.ru/leads/detail/...`) | ❌ | ❌ |
| Маркетплейсы (OZON, WB, etc.) | ✅ Онлайн-торговля module | ❌ | ❌ |
| ЭДО (Electronic doc exchange) | ✅ Native ЭДО button in purchases | ❌ | ❌ |
| Firebase push | ✅ (fcm_token_details_db found) | ❌ | ❌ |
| Retail POS | ✅ Розница module | ❌ | ❌ |
| Таможня:ДП | ❌ Not in МС | ✅ Full integration | ✅ We're ahead |
| СДЭК / Европочта | ⚠️ Link stored in order field, no native tracking | ✅ Local track number + estimated delivery | ✅ We're ahead |
| МойСклад ↔ СникерХэд | ⚠️ Partial: stock sync + order push only | ⚠️ 4 actions: syncStock, reserve, ajaxSync, settings | ❌ One-way, incomplete |

---

## 9. User Roles & Permissions

| Feature | МойСклад | СникерХэд | Gap |
|---------|----------|-----------|-----|
| Role-based access | ✅ Employee owners, department owners, shared access per document | ✅ admin / logist roles | ⚠️ Only 2 roles |
| Per-document ownership | ✅ Владелец-сотрудник, Владелец-отдел | ❌ | ❌ |
| Per-employee order assignment | ✅ Арешкин А. visible on orders | ✅ assigned_logist field | ✅ |
| Audit log | ✅ Кто изменил, Когда изменен on every entity | ✅ Admin log table | ✅ |

---

## 10. Automation & Tasks

| Feature | МойСклад | СникерХэд | Gap |
|---------|----------|-----------|-----|
| Task system | ✅ Задачи (26 active tasks) | ❌ | ❌ |
| Automation triggers | ⚠️ Via Решения (app marketplace) | ✅ `automation_trigger` + `automation_log` | ✅ We're ahead for custom flows |
| SMS templates | ⚠️ Via integrations | ✅ `sms_template` table (5 templates) | ✅ |
| Event notifications | ✅ Firebase push (fcm) | ❌ No push notifications | ❌ |

---

## Summary: Priority Gaps to Close

### Critical — blocks daily operations

| # | Feature | Why critical |
|---|---------|-------------|
| 1 | **Payment ledger** | Currently no record of money received vs. orders. МС tracks every payment to counterparty with bank account. We only store screenshots. |
| 2 | **Stock auto-decrement on order** | МС reserves stock on order creation. Our `product_size.stock` drifts immediately. |
| 3 | **Order cancellation reason** | МС has `Причина отказа` field. We have no structured reason — blocks refund/returns analysis. |

### High — needed within 3 months

| # | Feature | Effort |
|---|---------|--------|
| 4 | **Customer balance / debt tracking** | Medium — need payments table linked to orders |
| 5 | **Pickup appointment date** | Low — add `pickup_date` field to Order model |
| 6 | **AmoCRM deal link** | Low — add `amocrm_deal_url` field (МС already stores this per order) |
| 7 | **Multi-price support** | Medium — price per customer group/loyalty level |
| 8 | **Stock receipts UI** | High — full warehouse receipt flow |
| 9 | **Purchase orders to suppliers** | High — МС has full PO lifecycle, we have nothing |

### Medium — competitive differentiation

| # | Feature | Notes |
|---|---------|-------|
| 10 | **Voronka prodazh (sales funnel)** | МС has it natively; we have basic analytics |
| 11 | **P&L / Cash flow reports** | МС has full finance module |
| 12 | **Task management per order/customer** | МС has 26 active tasks visible |
| 13 | **Supplier return flow** | МС full flow; we have `/admin/return` stub |

### Where СникерХэд is AHEAD of МойСклад

| Feature | Notes |
|---------|-------|
| **Таможня:ДП full integration** | Auth, create/delete shipment, import statuses, proxy phones — МС has no DP module |
| **RFM analysis** | Native `/admin/analytics/rfm` — МС doesn't have RFM built-in |
| **Client-facing order tracking** | Public tracking page with estimated delivery, DP sub-status, passport form |
| **Custom automation engine** | `automation_trigger` with event-driven SMS/status flows |
| **Passport data workflow** | Full passport collection + DP customs compliance flow — unique to our domain |

---

## Current МойСклад ↔ СникерХэд Sync State

Our integration (`MoySkladService.php` + `MySkladController.php`) currently does:

| Action | Direction | Status |
|--------|-----------|--------|
| `pushOrder()` | СникерХэд → МС | ✅ Pushes order on create |
| `syncStock()` | МС → СникерХэд | ✅ Pulls stock levels |
| `reserveInMoysklad()` | СникерХэд → МС | ✅ Reserves product qty |
| `getOrders()` | МС → СникерХэд | ⚠️ Read only, not syncing back |
| `getProducts()` | МС → СникерХэд | ⚠️ Read only |
| Status sync МС → нас | МС → СникерХэд | ❌ Missing — МС status changes don't update our orders |
| Payment sync | МС → СникерХэд | ❌ Missing — 427 payments in МС not reflected in our system |

**Key missing sync:** When a payment is registered in МойСклад (427 payments tracked), our system doesn't know about it. Orders marked paid in МС still show as unpaid in our admin unless manually updated.
