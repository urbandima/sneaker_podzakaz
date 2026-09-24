# CMP-456 — Сплошной прогон мутирующих действий: «200 OK ≠ данные записаны»

Волна 1 — **Приоритет 1 (деньги и заказы)**. Каждый пункт проверен живым HTTP-запросом
с правдоподобным payload (реальные CSRF/сессия/cookies, кириллица в текстовых полях,
существующие id) и сверкой фактической строки в MySQL до/после — не только HTTP-кода.

## Почему именно так

Предыдущие сплошные прогоны (CMP-413/416/417 — маршруты, CMP-420 — console, CMP-421 —
JS↔маршруты, CMP-422 — пост-обработка заказа) отвечали на вопрос «маршрут отвечает?».
Форма отзыва (CMP-452/454) отвечала `200 success` и не писала ничего в `product_review` —
класс бага, который проверка кода ответа не ловит вообще. Этот документ закрывает разрыв
для тех мутирующих действий, что касаются денег и заказов.

## Что уже было покрыто раньше (не дублируется здесь)

| Поверхность | Где проверено |
|---|---|
| `cart/add` + оверселл при нулевом остатке | CMP-449 (живой parallel-race тест) |
| `product_favorite`/`product_review` FK на customer | CMP-446 |
| Купон в живом `/checkout` (apply/recalculate) | CMP-423 |
| `cart.user_id` FK | CMP-433 → CMP-435 |
| `catalog/review/create` (форма отзыва) | CMP-454 (в рамках этой же задачи, коммит `7e07469`) |

## Реестр волны 1 — customer-facing checkout

Охват: `frontend/controllers/OrderController.php` (create, save-passport),
`backend/modules/checkout/controllers/OrderController.php` (create),
`frontend/controllers/CartController.php` (update, remove).

| Действие | Маршрут | Payload (кратко) | HTTP | Факт в БД | Вердикт |
|---|---|---|---|---|---|
| `OrderController::actionCreate` | `POST /order/create` | ФИО кириллица, телефон, email, pickup, комментарий | 200 | `order`+`order_item`+`order_history` созданы, `cart` очищена, кириллица цела | **ok** |
| `OrderController::actionSavePassport` | `POST /order/save-passport` | BY-паспорт (серия+номер), ФИО, ИНН, адрес — синтетические данные | 200 | все поля записались, кириллица цела | **ok** |
| checkout-модуль `OrderController::actionCreate` | `POST /checkout/order/create` | гостевой заказ | 200 | заказ создаётся, но авто-аккаунт гостя не создавался (`Customer::is_active` не существует) | **wrong_data → исправлено, коммит `f781602`** |
| `CartController::actionUpdate` | `POST /cart/update` | `id, quantity=5` | 200 | `cart.quantity` изменилось | **ok** |
| `CartController::actionRemove` | `POST /cart/remove/<id>` | — | 200 | строка удалена | **ok** |

**Важная находка (не «200 без эффекта», а архитектурный риск):** маршрут
`checkout/order/create` — независимо развивавшийся дубль живого чекаута, публично
доступен без авторизации, не имеет защит от оверселла (CMP-438 `FOR UPDATE`, CMP-449
гашение `stock_status`). Вынесено в отдельную карточку **CMP-459**; решение принято и
реализовано параллельным run — контроллер удалён как orphan (коммит `d9b821d`, живых
потребителей в JS/PHP не найдено).

## Реестр волны 1 — admin: заказы и купоны

Охват: `backend/modules/admin/controllers/CouponController.php` (create/update/delete/toggle),
`backend/modules/admin/controllers/OrderController.php` (update-status/update-field/add-note/
add-item/delete-item/update-items/save-item-field/save-buyout/bulk-update-status/
bulk-update-field), `backend/modules/admin/controllers/OrderApiController.php`
(update-field/add-note).

| Действие | Маршрут | HTTP (до фикса) | Факт в БД | Вердикт |
|---|---|---|---|---|
| Coupon create | `POST /admin/coupon/create` | 302 | строка создана корректно | ok |
| Coupon update | `POST /admin/coupon/update/{id}` | 302 | поля изменились | ok |
| Coupon toggle | `POST /admin/coupon/toggle/{id}` | 302 | `is_active` переключился | ok |
| Coupon delete | `POST /admin/coupon/delete/{id}` | 302 | строка удалена | ok |
| Order update-status | `POST /admin/order/update-status` | 200 | `status` изменился | ok |
| Order update-field | `POST /admin/order/update-field` | 200 | поле изменилось | ok |
| Order add-note | `POST /admin/order/add-note` | **500** | заметка писалась в БД, но ответ 500 (рендер несуществующего партиала `_notes.php`) | **fixed** |
| Order add-item | `POST /admin/order/add-item` | 200 | строка добавлена | ok |
| Order delete-item | `POST /admin/order/delete-item` | 302 | строка удалена, но `order.total_amount` не пересчитывался | **fixed (wrong_data)** |
| Order update-items | `POST /admin/order/update-items` | 302 | позиции заменены, total пересчитан | ok |
| Order save-item-field | `POST /admin/order/save-item-field` | 200 | поле + total обновлены | ok |
| Order save-buyout | `POST /admin/order/save-buyout` | 200 (ложный успех) | Buyout-черновик не создавался вообще (DATETIME на raw timestamp + NOT NULL `order_item_id` не проставлялся, оба исключения глотались `catch`) | **fixed (no_effect, 2 спаренных бага)** |
| Order bulk-update-status | `POST /admin/order/bulk-update-status` | 200 | статус изменился у всех id | ok |
| Order bulk-update-field | `POST /admin/order/bulk-update-field` | 200 | поле изменилось у всех id | ok |
| OrderApi update-field | `POST /admin/order-api/update-field` | **500** | поле Order сохранялось, но лог в `order_history` падал (несуществующие колонки `status`/`created_by`) | **fixed** |
| OrderApi add-note | `POST /admin/order-api/add-note` | **500** | то же — колонки не совпадали со схемой | **fixed** |

Плюс independent finding: живая кнопка «Добавить заметку» в `order/view.php` слала JSON-тело
без `?id=` в URL — `actionAddNote($id)` биндит параметр только из query/route, поэтому кнопка
**никогда не работала** в реальном UI (400 либо пустой `$_POST['text']`). Исправлено вместе с
500-кой на сервере — коммит `98e2144`.

**Найдено, но не исправлено (нужно продуктовое решение, не механический фикс):**
- `OrderApiController::actionUpdateField` — поля `address`/`track_number` в whitelist не
  существуют как записываемые атрибуты `Order` (500 при любом обращении). Живых вызовов
  из UI не найдено (только read-only `order-api/history`) — маршрут де-факто мёртв для
  этих двух полей. Не чинили: непонятно, в какую из двух адресных колонок должен писать
  `address`.
- `OrderApiController::actionChangeStatus` — та же схема-рассинхронизация `status`/`created_by`
  в `order_history` (строка ~188-195), не тестировался живым запросом в этой волне (вне
  явного списка 16 действий) — кандидат на быстрый follow-up по уже установленному образцу.
- `BuyoutController::actionLinkOrder` (~строка 259) — та же NOT NULL/nullable-в-модели
  нестыковка по `order_item_id`, что была в `save-buyout` — вне охвата волны (модуль
  procurement), не трогали.

## Итого

- **21 мутирующее действие** проверено живым запросом с подтверждением эффекта в БД
  (5 customer-facing + 16 admin order/coupon), плюс 5 уже закрыто раньше в других карточках
  той же очереди (см. таблицу выше) — суммарно Приоритет 1 (деньги/заказы) покрыт полностью
  по списку из задания.
- **8 реальных дефектов класса «200 без корректного эффекта»** найдено и исправлено:
  коммиты `7e07469` (CMP-454), `f781602`, `98e2144` (эта задача), `d9b821d` (CMP-459).
- **2 находки задокументированы, не исправлены** — нужно продуктовое решение
  (`OrderApiController::actionUpdateField` address/track_number, `actionChangeStatus`
  схема-рассинхронизация) — см. раздел выше, оставлены как заметка в этом отчёте, не
  требуют отдельной карточки (тривиальные follow-up по уже известному паттерну согласно
  критерию приёмки №4).
- Тестовые данные (customer, order, cart, coupon, buyout) удалены обоими прогонами,
  подтверждено проверочными SELECT после каждой фазы.

## Честная граница охвата этой волны

Проверен только **Приоритет 1 — деньги и заказы** (корзина, оформление заказа, паспортные
данные, купоны, admin-редактирование заказа). **Приоритеты 2–4** — данные покупателя вне
корзины/паспорта/отзыва (регистрация, вход, профиль, заявки), каталог и контент, весь
остальной admin — в этой волне не проверялись. Вынесены в отдельную задачу-продолжение.
