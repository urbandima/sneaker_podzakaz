# Модуль «Выкупы товаров» (Procurement / Buyout)

## Назначение

Выкуп — единица закупки одного товара у внешнего источника (Poizon, Lamoda, AliExpress, поставщик).  
Один выкуп может быть привязан к 0..N заказам клиентов.  
При смене статуса выкупа статусы связанных заказов обновляются автоматически.

---

## Workflow (жизненный цикл)

```
[draft] ──→ [ordered] ──→ [in_transit] ──→ [arrived] ──→ [accepted] ──→ (closed)
              │                                │
              └──→ [cancelled] ──→ [refunded]  └──→ [cancelled]
```

| Этап | Действие | Статус выкупа |
|------|----------|--------------|
| Создание | Менеджер создаёт выкуп (URL/вручную) | `draft` |
| Заказ | Подтверждение оплаты на источнике | `ordered` |
| В пути | Трек-номер получен, товар отправлен | `in_transit` |
| Прибыл | Товар на складе | `arrived` |
| Принят | Приёмка создана, остатки зачислены | `accepted` |
| Закрыт | Все заказы выполнены | (через приёмку) |

---

## Схема БД

### `buyout`

| Колонка | Тип | Описание |
|---------|-----|----------|
| `id` | PK | — |
| `source` | ENUM | poizon / lamoda / aliexpress / supplier / manual |
| `source_url` | VARCHAR(1024) | URL страницы товара |
| `external_id` | VARCHAR(255) | ID товара у источника |
| `product_id` | FK NULL | Ссылка на product (если сопоставлен) |
| `product_snapshot` | JSON | Снапшот: name, brand, image, images |
| `size` | VARCHAR(30) | Размер |
| `qty` | INT | Количество |
| `unit_cost_source` | DECIMAL(12,2) | Цена ед. в валюте источника |
| `source_currency` | CHAR(3) | Валюта (CNY, USD, …) |
| `exchange_rate` | DECIMAL(10,4) | Курс к BYN |
| `unit_cost_byn` | DECIMAL(12,2) | Цена ед. в BYN |
| `shipping_cost` | DECIMAL(12,2) | Стоимость доставки (BYN) |
| `fees` | DECIMAL(12,2) | Комиссии / пошлины (BYN) |
| `total_cost_byn` | DECIMAL(12,2) | Итого: unit_cost_byn×qty + shipping + fees |
| `status` | ENUM | Текущий статус |
| `ordered_at` | DATETIME | Дата заказа |
| `arrived_at` | DATETIME | Дата прибытия |
| `accepted_at` | DATETIME | Дата приёмки |
| `buyer_user_id` | FK | Закупщик |
| `notes` | TEXT | Внутренние заметки |
| `receipt_url` | VARCHAR(1024) | Чек / инвойс |
| `tracking_number` | VARCHAR(100) | Трек-номер |
| `carrier` | VARCHAR(100) | Перевозчик |
| `receiving_id` | FK NULL | Ссылка на purchase_order (приёмка) |
| `created_at` | DATETIME | — |
| `updated_at` | DATETIME | — |

### `buyout_order_link`

| Колонка | Тип | Описание |
|---------|-----|----------|
| `buyout_id` | FK | Выкуп |
| `order_id` | FK | Заказ |
| `order_item_id` | FK NULL | Конкретная позиция заказа (NULL = весь заказ) |
| `qty` | INT | Привязанное кол-во |

PK: `(buyout_id, order_id, order_item_id)`

### `buyout_history`

| Колонка | Тип | Описание |
|---------|-----|----------|
| `id` | PK | — |
| `buyout_id` | FK | Выкуп |
| `user_id` | FK NULL | Пользователь |
| `action` | VARCHAR(100) | Тип события |
| `old_value` | JSON | Старое значение |
| `new_value` | JSON | Новое значение |
| `created_at` | DATETIME | — |

Типы событий: `status_changed`, `order_linked`, `order_unlinked`, `note_added`, `receipt_added`, `tracking_added`, `receiving_created`

---

## Mapping статусов: выкуп → заказ

| Статус выкупа | Новый статус заказа |
|---------------|---------------------|
| `ordered` | `bought_at_source` — «Выкуплен на источнике» |
| `in_transit` | `in_transit_from_source` — «В пути от источника» |
| `arrived` | `arrived_at_warehouse` — «Прибыл на склад» |
| `accepted` | `ready_to_ship` — «Готов к отправке» |
| `cancelled` | `awaiting_buyout` — «Ожидает выкупа» |

---

## API Endpoints (Admin)

| Метод | URL | Действие |
|-------|-----|----------|
| GET | `/admin/procurement/buyouts` | Список + KPI |
| GET | `/admin/procurement/buyout/create` | Форма создания |
| POST | `/admin/procurement/buyout/create` | Создать выкуп |
| GET | `/admin/procurement/buyout/{id}` | Карточка выкупа |
| POST | `/admin/procurement/buyout/{id}/edit` | Обновить |
| POST | `/admin/procurement/buyout/{id}/delete` | Удалить |
| POST | `/admin/procurement/buyout/parse-url` | Распарсить URL → данные товара |
| POST | `/admin/procurement/buyout/update-status` | Сменить статус (один) |
| POST | `/admin/procurement/buyout/bulk-status` | Bulk смена статуса |
| POST | `/admin/procurement/buyout/link-order` | Привязать заказ |
| POST | `/admin/procurement/buyout/unlink-order` | Отвязать заказ |
| POST | `/admin/procurement/buyout/{id}/accept` | Принять → создать приёмку |
| POST | `/admin/procurement/buyout/{id}/cancel` | Отменить |
| GET | `/admin/procurement/buyout/{id}/history` | JSON история событий |

---

## Файловая структура

```
backend/modules/
├── procurement/
│   ├── models/
│   │   ├── Buyout.php                        # Основная модель
│   │   ├── BuyoutOrderLink.php               # Связь выкуп↔заказ
│   │   └── BuyoutHistory.php                 # История событий
│   └── services/
│       ├── BuyoutUrlParserService.php         # Диспетчер парсеров
│       ├── BuyoutStatusSyncService.php        # Синхронизация статусов
│       └── parsers/
│           ├── BuyoutParserInterface.php      # Интерфейс
│           ├── PoizonParser.php               # Poizon/Dewu
│           ├── LamodaParser.php               # Lamoda (через LamodaParser)
│           ├── AliexpressParser.php           # Заглушка AliExpress
│           └── GenericParser.php              # Open Graph fallback
└── admin/
    ├── controllers/
    │   └── BuyoutController.php              # Все actions
    └── views/
        └── buyout/
            ├── index.php                      # Список + KPI + bulk actions
            ├── view.php                       # CRM-карточка
            └── create.php                     # Форма создания/редактирования

migrations/
├── m260426_140000_create_buyout_tables.php   # buyout, buyout_order_link, buyout_history
└── m260426_140001_add_order_buyout_statuses.php # 5 новых статусов в order_status
```

---

## Сценарии тестирования

### 1. Создание выкупа вручную
1. Перейти `/admin/procurement/buyouts` → «Новый выкуп»
2. Вставить URL Poizon → «Распарсить» → поля автозаполняются
3. Указать размер, кол-во, курс → итог пересчитывается
4. Привязать существующий заказ по ID
5. Нажать «Создать»
6. **Ожидаем**: выкуп создан со статусом `draft`, история содержит событие `status_changed`

### 2. Смена статуса → автоматическое обновление заказа
1. Открыть созданный выкуп
2. Нажать «→ Заказан»
3. **Ожидаем**: статус выкупа `ordered`, связанный заказ получил статус `bought_at_source`
4. Нажать «→ В пути»
5. **Ожидаем**: статус выкупа `in_transit`, заказ → `in_transit_from_source`

### 3. Принятие выкупа (Receiving)
1. Поставить статус «Прибыл»
2. Нажать «Принять (создать приёмку)»
3. **Ожидаем**: `BuyoutController::actionAccept` создаёт `PurchaseOrder` с `status=received`,
   заполняет `buyout.receiving_id`, устанавливает `accepted_at`, статус выкупа → `accepted`,
   связанные заказы → `ready_to_ship`

### 4. Bulk action
1. На индексе отметить несколько выкупов-черновиков
2. Выбрать статус «Заказан» → «Применить»
3. **Ожидаем**: у каждого выкупа, допускающего переход, статус сменился

### 5. Отмена
1. Выкуп в статусе `ordered` → «→ Отменён»
2. **Ожидаем**: статус `cancelled`, заказы → `awaiting_buyout`

---

## Финансовая формула

```
total_cost_byn = unit_cost_byn × qty + shipping_cost + fees
unit_cost_byn  = unit_cost_source × exchange_rate   (если не задана напрямую)
```

При `actionAccept` shipping_cost и fees равномерно распределяются по qty единиц через отдельный метод `PurchaseOrder` (равномерно по штукам).

---

## Примечания

- Парсеры Poizon и Lamoda используют существующие сервисы проекта.
- AliExpress и Generic — заглушки, расширяются по мере готовности реального парсинга.
- Статусы заказа, добавленные миграцией, являются system-статусами (`is_system=1`) и видны в CRM, но не доступны логистам по умолчанию.
- Sidebar-меню: Закупки → Выкупы (`/admin/procurement/buyouts`).
