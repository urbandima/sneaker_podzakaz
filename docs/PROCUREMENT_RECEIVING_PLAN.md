# Procurement Receiving Module — Full Plan

## 1. Бизнес-процесс

### Источники поставок

| Источник | Как попадает в систему |
|---|---|
| Пакет от поставщика | Закупщик создаёт Receiving вручную, указывает Supplier |
| Партия из Poizon/Lamoda | Выкуп (Buyout) переходит в `accepted` → автоматически создаётся Receiving |
| Прямая закупка | Закупщик создаёт Receiving из PurchaseOrder |
| Ручной ввод | Receiving без привязки к источнику |

### Участники и действия

| Роль | Действия |
|---|---|
| **Закупщик** | Создаёт Receiving (`draft`), добавляет ожидаемые товары, устанавливает дату ожидания |
| **Логист** | Меняет статус `draft` → `in_transit` → `arrived`. Добавляет расходы (таможня, доставка). |
| **Кладовщик** | `arrived` → `inspecting`: считает каждую позицию (qty_arrived), отмечает дефекты (qty_defected). Нажимает «Принять» → `accepted`. |
| **Бухгалтер** | Проверяет расходы, загружает документы, закрывает. |

### Что проверяет кладовщик

- Соответствие SKU и размеров заявленным позициям
- Физическую целостность (qty_defected)
- Количество (qty_arrived vs qty_expected)
- Наличие инвойса / таможенной декларации

---

## 2. Модели и схема БД

### `receiving`

```sql
id, number (авто, RCV-YYYYMM-NNNNN), supplier_id (FK → supplier), buyout_id (FK → buyout),
receiving_date, expected_date, arrived_date, accepted_date,
status ENUM(draft, in_transit, arrived, inspecting, accepted, partial, cancelled),
total_items, total_qty_expected, total_qty_arrived, total_qty_defected,
subtotal_byn, expenses_total_byn, total_with_expenses_byn,
receiver_user_id, notes, created_at, updated_at
```

### `receiving_item`

```sql
id, receiving_id (FK), product_id (FK → product), size_id (FK → product_size),
qty_expected, qty_arrived, qty_defected,
unit_cost_source, source_currency, exchange_rate, unit_cost_byn,
allocated_expenses_byn, final_cost_byn, notes
```

### `receiving_expense`

```sql
id, receiving_id (FK), type ENUM(customs, shipping, insurance, packaging, other),
amount, currency, amount_byn, exchange_rate,
distribution_method ENUM(equal, by_qty, by_value, manual), notes
```

### `receiving_document`

```sql
id, receiving_id (FK), type ENUM(invoice, customs_declaration, photo, other),
file_path, original_name, mime_type, size_bytes,
uploaded_at, uploaded_by (FK → user)
```

### Связи

```
Receiving ──< ReceivingItem >── Product
Receiving ──< ReceivingExpense
Receiving ──< ReceivingDocument
Receiving >── Supplier
Receiving >── Buyout (nullable)
ReceivingItem >── ProductSize (nullable)
```

---

## 3. State Machine

```
draft ──► in_transit ──► arrived ──► inspecting ──► accepted
  │                          │              │
  └── cancelled ◄────────────┘              └──► partial (если часть дефект)
```

### Разрешённые переходы

| От | К | Условие |
|---|---|---|
| draft | in_transit | — |
| draft | cancelled | — |
| in_transit | arrived | — |
| in_transit | cancelled | — |
| arrived | inspecting | — |
| arrived | cancelled | — |
| inspecting | accepted | qty_arrived ≥ 1 на хотя бы 1 позиции |
| inspecting | partial | qty_arrived < qty_expected хотя бы на 1 позиции |
| inspecting | cancelled | — |
| accepted | — | финальный |
| partial | — | финальный |
| cancelled | — | финальный |

---

## 4. Workflow для каждой роли

### Закупщик

1. Нажать **+ Новая приёмка** → wizard
2. Шаг 1: выбрать поставщика/источник, указать expected_date
3. Шаг 2: добавить ожидаемые товары (product + size + qty_expected + unit_cost_source + currency)
4. Шаг 3: при необходимости добавить авансовые расходы
5. Сохранить → статус `draft`

### Логист

1. Найти приёмку в статусе `draft`, изменить на `in_transit`
2. Когда товар прибыл — изменить на `arrived`
3. Добавить расходы: таможня, доставка, страховка (с указанием валюты и exchange_rate)
4. Система автоматически пересчитывает `amount_byn` и распределяет по позициям

### Кладовщик

1. Найти приёмку в статусе `arrived`, изменить на `inspecting`
2. Для каждой позиции заполнить `qty_arrived` и `qty_defected` (inline в таблице)
3. Система пересчитывает `final_cost_byn = unit_cost_byn + allocated_expenses_byn`
4. Нажать **«Принять»** → статус `accepted`/`partial`
5. Система: `product.stock += qty_arrived` (минус дефекты)
6. Связанные заказы → `ready_to_ship`

### Бухгалтер

1. Просмотр расходов (expenses_total_byn, итоги)
2. Загрузка документов (инвойс, таможенная декларация)
3. Сверка сумм с загруженными документами
4. При необходимости ручная корректировка `allocated_expenses_byn`

---

## 5. Распределение расходов

### Методы

| Метод | Формула |
|---|---|
| `equal` | сумма / кол-во позиций |
| `by_qty` | (qty_arrived / total_qty) * сумма |
| `by_value` | (unit_cost_byn * qty_arrived / subtotal) * сумма |
| `manual` | вручную по каждой позиции |

### Алгоритм

При добавлении/изменении/удалении расхода:
1. Рассчитать `expenses_total_byn` = сумма всех `amount_byn`
2. Распределить по позициям через `redistributeExpenses()`
3. `allocated_expenses_byn[i] = sum(expense.distribute(item_i))`
4. `final_cost_byn[i] = unit_cost_byn[i] * qty_arrived[i] + allocated_expenses_byn[i]`
5. `total_with_expenses_byn = sum(final_cost_byn)`

---

## 6. UI

### `index.php` — список приёмок

- **Фильтры:** статус, поставщик, период (expected/arrived), наличие расходов
- **KPI-плашки:** В пути, Прибыло (сегодня/эта неделя), Принято за месяц, Сумма с расходами
- **Таблица:** №, поставщик/источник, статус-бейдж, товаров, ожидается/прибыло, сумма, дата
- **Действия:** быстрый переход по статусу (dropdown)

### `view.php` — карточка приёмки (CRM-стиль)

```
┌─ Sticky Header ──────────────────────────────────────────────────────────┐
│ ← Назад   RCV-202604-00001 · [ПРИБЫЛА]   [Принять] [Изменить] [⋯]        │
└──────────────────────────────────────────────────────────────────────────┘
┌─ Left (8/12) ───────────────────────┐  ┌─ Right (4/12) ──────────────────┐
│                                     │  │                                  │
│  ТОВАРЫ                             │  │  ПОСТАВКА                        │
│  ┌────────────────────────────────┐ │  │  Поставщик / Выкуп              │
│  │ товар│размер│ожид│прибыло│деф  │ │  │  Ожидалась / Прибыла            │
│  │ ... inline-edit qty_arrived    │ │  │                                  │
│  └────────────────────────────────┘ │  │  СТАТУС                          │
│  [+ Добавить позицию]               │  │  [toggle buttons]                │
│                                     │  │                                  │
│  РАСХОДЫ                            │  │  ИТОГИ                           │
│  ┌───────────────────────────────┐  │  │  Товары:    1 234.56 BYN        │
│  │ таможня │ 100 BYN │ equal     │  │  │  Расходы:    150.00 BYN        │
│  │ доставка│  50 BYN │ by_qty    │  │  │  Итого:     1 384.56 BYN       │
│  └───────────────────────────────┘  │  │                                  │
│  [+ Добавить расход]                │  │  ДОКУМЕНТЫ                       │
│                                     │  │  [drag-drop upload zone]         │
└─────────────────────────────────────┘  │  📄 invoice.pdf                  │
                                         └──────────────────────────────────┘
┌─ Timeline ───────────────────────────────────────────────────────────────┐
│ 14:32  Иван  →  in_transit                                                │
│ 15:10  Иван  Добавлен расход: таможня 100 BYN                            │
└──────────────────────────────────────────────────────────────────────────┘
```

### `create.php` — создание (3-step wizard)

- Шаг 1: поставщик/источник + даты
- Шаг 2: товары (поиск по SKU/названию, size picker)
- Шаг 3: расходы (опционально)

---

## 7. API Endpoints

| Method | URL | Action |
|---|---|---|
| GET | /admin/receiving | index |
| GET | /admin/receiving/create | create form |
| POST | /admin/receiving/create | save new |
| GET | /admin/receiving/{id} | view |
| POST | /admin/receiving/update/{id} | update fields |
| POST | /admin/receiving/set-status | change status |
| POST | /admin/receiving/add-item | add item |
| POST | /admin/receiving/update-item | update item qty/cost |
| POST | /admin/receiving/remove-item | remove item |
| POST | /admin/receiving/add-expense | add expense |
| POST | /admin/receiving/update-expense | update expense |
| POST | /admin/receiving/remove-expense | remove expense |
| POST | /admin/receiving/redistribute | recalculate allocations |
| POST | /admin/receiving/upload-document | upload file |
| POST | /admin/receiving/delete-document | delete file |
| POST | /admin/receiving/accept | accept (оприходование) |
| POST | /admin/receiving/cancel | cancel |
| POST | /admin/receiving/from-buyout/{buyout_id} | create from buyout |

---

## 8. Связь с другими модулями

### Buyout → Receiving

```php
// В BuyoutController::actionSetStatus (когда accepted)
if ($newStatus === 'accepted' && $oldStatus !== 'accepted') {
    ReceivingService::createFromBuyout($buyout);
}
```

Автоматически создаётся Receiving в статусе `arrived` со всеми позициями из Buyout.

### Receiving accept → Product.stock

```php
foreach ($receiving->items as $item) {
    $actualQty = $item->qty_arrived - $item->qty_defected;
    ProductSize::updateAllCounters(['stock' => $actualQty], ['id' => $item->size_id]);
}
```

### Receiving accept → Order status

```php
// Связанные заказы через buyout_order_link или напрямую
foreach ($relatedOrders as $order) {
    if (allItemsReady($order)) {
        $order->updateStatus('ready_to_ship');
    }
}
```

---

## 9. Файловая структура

```
backend/modules/procurement/models/
  Receiving.php
  ReceivingItem.php
  ReceivingExpense.php
  ReceivingDocument.php
  ReceivingHistory.php

backend/modules/procurement/services/
  ReceivingService.php        # createFromBuyout, accept, redistribute

backend/modules/admin/controllers/
  ReceivingController.php

backend/modules/admin/views/receiving/
  index.php
  view.php
  create.php
  _item_row.php               # AJAX partial
  _expense_row.php            # AJAX partial
  _document_row.php           # AJAX partial

console/migrations/
  m260426_200300_create_receiving_tables.php

frontend/web/js/
  receiving.js                # inline edit, ajax, drag-drop

docs/
  PROCUREMENT_RECEIVING_PLAN.md   (этот файл)
  PROCUREMENT_RECEIVING_GUIDE.md  (guide для ролей)
```
