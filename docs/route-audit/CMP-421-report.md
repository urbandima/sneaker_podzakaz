# CMP-421 — Сверка контракта фронтенд-JS ↔ реальные маршруты

Дата: 2026-09-22. Направление: **от клиента** (в отличие от CMP-413, который шёл от сервера — перечислял
маршруты и стучался в каждый). Здесь наоборот: выгребли из фронтенда все исходящие HTTP-вызовы и проверили,
существует ли то, во что они стучатся.

Окружение: `php -S 127.0.0.1:38765 -t frontend/web router.php`, БД `cmp410_e2e_clean` (та же, что в
CMP-410/413/416/417), MySQL 8 strict mode.

## Метод

1. Полное чтение (не только grep) всех `frontend/web/js/*.js` (кроме `dist/`) и инлайн-скриптов/`action=`
   форм в customer-facing `frontend/views/**` — 46 файлов, 59 call site'ов.
2. То же для admin-панели: `admin-*.js`, `dashboard.js`, все `backend/modules/admin/views/**`, плюс
   `backend/modules/account/views/account/{wishlist,find-orders}.php`,
   `backend/modules/compare/views/compare/index.php` — 91+169 call site'ов (≈260 суммарно).
3. Для каждого customer-facing URL — живой HTTP-запрос на локальный сервер (неавторизованная сессия).
4. Для admin-панели: catch-all правило `'admin/<controller>/<action>' => 'admin/<controller>/<action>'`
   означает, что маршрутизация почти всегда резолвится синтаксически — реальный риск не в routing-слое,
   а в том, существует ли `action<Name>()` в целевом контроллере. Поэтому здесь применён комбинированный
   метод: статическая сверка (URL → controller/action → существование метода) + живой неавторизованный
   HTTP-прогон как перекрёстная проверка (404 без авторизации = маршрут не резолвится вообще, 302 = маршрут
   есть, требует логина). Живым HTTP предметно проверены все кандидаты, подсвеченные статическим анализом
   как отличающиеся от рабочих аналогов; для остальной admin-поверхности (несколько сотен call site'ов,
   в основном служебные CRM-экраны) в этом заходе применена только static+302/404-сверка — это меньший объём
   верификации, чем для customer-facing слоя, соответственно смещению риска (staff-facing, внутренний,
   низкий blast radius при ошибке — см. домен-линзу Blast radius в мандате CTO).

## Итог: 8 подтверждённых живых багов на клиентской стороне, 5 — на admin-стороне (починено 6, остальное — в
child issues), плюс системное открытие: у клиентского кода есть целый несуществующий REST-неймспейс `/api/v1/*`.

### Главная находка: `/api/v1/*` — фантомный неймспейс

В `infrastructure/config/web.php` НЕТ ни одного правила `urlManager` под префиксом `api/v1`, и модуль `api`
(`app\api\ApiModule`, `api/controllers/*`) не содержит никакого `v1`-контроллера или вложенного модуля.
Любой вызов `/api/v1/...` резолвится в module=`api`, controller=`v1` → `V1Controller` не существует → 404.

Ровно этот паттерн (уже найденный в CMP-416 на примере `/api/v1/coupon/validate`) оказался системным:
**8 разных клиентских call site'ов** в 4 разных файлах бьют в несуществующий `/api/v1/*`. Часть из них —
код с рабочим сервер-side аналогом под другим URL (контракт разошёлся — чинится), часть — код, который
физически недостижим (удалён), часть — реально нереализованная фича (заводится отдельной задачей).

## Реестр: customer-facing (59 call site'ов, 46 файлов)

| # | URL (как вызывает JS) | Метод | Откуда | Живой статус (до) | Вердикт |
|---|---|---|---|---|---|
| 1 | `/catalog/search?q=` | GET | app.js, search.js, cart/layouts/public.php | 200 | OK |
| 2 | `/cart/drawer-items`, `/cart/add`, `/cart/update`, `/cart/remove/<id>`, `/cart/count`, `/cart/has-product` | GET/POST | cart.js, catalog.js, product-page.js, account/wishlist.php, catalog/_size_selector.php, catalog/index.php, catalog/product.php | 200 | OK |
| 3 | `/api/v1/loyalty/balance` | GET | cart-promo-loyalty.js:33 | **404** | **Исправлено** → `/account/loyalty/balance` (контракт `{success,balance,level}` совпадает; для гостя молча падает в catch, как и раньше — некритично) |
| 4 | `/api/v1/coupon/validate` | POST | cart-promo-loyalty.js:89 | **404** | Известный баг из CMP-416, продуктовое решение по купонам отдельно — не трогать (см. отдельную задачу про end-to-end купон) |
| 5 | `/catalog/filter`, `/catalog/quick-order`, `/catalog/create-inquiry`, `/catalog/submit-review`, `/catalog/submit-question` | POST | catalog.js, product-modals.js, catalog/_inquiry_modal.php, catalog/product.php | 200 | OK |
| 6 | `/catalog/get-brands` (без `/api` префикса) | GET | public-layout.js:40 | **404** | **Исправлено** → `/api/catalog/get-brands` (уже рабочий, использовался в других местах) |
| 7 | `/catalog/products-by-ids` (без `/api` префикса) | GET | view-history.js:67 | **404** | **Исправлено** → `/api/catalog/products-by-ids` (уже рабочий — тот же URL правильно использует favorites.php) |
| 8 | `/api/catalog/products-by-ids`, `/api/catalog/quick-view/<id>`, `/api/catalog/get-brands` | GET | favorites.php, index.php (частично) | 200 | OK — эталонные вызовы, с них и взят правильный URL для фиксов выше |
| 9 | `/api/v1/product/<id>/quick-view`, `/api/v1/cart/add`, `/api/v1/wishlist/toggle` | GET/POST | quick-view.js (весь файл) | **404** ×3 | **Мёртвый JS — файл удалён.** Не подключён ни в один реальный Yii2 `AssetBundle` (`CatalogAsset::$js` его не содержит — только в orphaned `gulpfile.js`, который сам никуда не собирается), а `openQuickView()`/`closeQuickView()` дополнительно затенены одноимёнными функциями в `catalog/index.php`. Код был физически недостижим в браузере. |
| 10 | `/catalog/product-quick/<id>` | GET | catalog/index.php:1298 (локальный Quick View каталога) | **404** | **Реальный баг, требует реализации** — см. child issue A. Реально вызывается кнопкой «Быстрый просмотр» / quick-size на странице каталога. Существующие `catalog/quick-view/<id>` и `api/catalog/quick-view/<id>` возвращают только `{success, html}` (готовый HTML-партиал), а этот JS ждёт плоский JSON (`image, brand, name, price, url, images[], sizes[]`) — контракт несовместим, не однострочный фикс. |
| 11 | `/cart/add` (5 разных call site'ов: catalog.js quickAddToCart, _size_selector.php, catalog/index.php ×2 варианта, product.php, account/wishlist.php) | POST | — | 200 | OK, но замечена рассинхронизация имён полей между вызовами (`product_id` vs `productId`, `quantity` vs `qty`) — cart/cart/add явно принимает оба варианта (проверено живым 200 для каждого), баг не подтверждён, но зафиксировано для будущего рефакторинга |
| 12 | `/favorite/toggle`, `/favorite/add`, `/favorite/remove`, `/favorite/clear`, `/favorite/merge-guest`, `/favorite/count` | GET/POST | favorites.js, global-helpers.js, account/wishlist.php | 200 | OK |
| 13 | `/order/create`, `/order/save-passport`, `/account/save-passport` | POST | checkout/index.php, cart/index.php, site/cart.php, order/_passport_form.php, account/_passport_form.php | 200 | OK (passport-контракт уже починен в CMP-416) |
| 14 | `/account/find-orders` | POST | account/find-orders.php, account/index.php | 200 | OK |
| 15 | `/api/v1/newsletter/subscribe` | POST | landing/index.php:405 | **404** | **Нет бэкенда вовсе** — см. child issue B |
| 16 | `/api/v1/tracking/refresh/<id>` | GET | account/tracking.php:505 | **404** | **Нет бэкенда вовсе** — см. child issue C |
| 17 | `/api/v1/returns/<id>/cancel` | POST | account/returns.php:439 | **404** | **Нет бэкенда вовсе** (в `ReturnRequest` даже нет статуса "отменено") — см. child issue D |
| 18 | `/feedback/submit` | POST | feedback/index.php | 200 | OK |

## Реестр: admin-панель (≈260 call site'ов, 14 JS + 55 view-файлов)

Полная построчная таблица (URL → file:line → метод → вердикт) — в рабочем логе прогона; здесь сведены
только подтверждённые расхождения (все остальные ≈250 call site'ов резолвятся в существующий `action*`
и живым `curl` подтверждены как 302/200, не 404).

| URL (как вызывает JS/href) | Откуда | Живой статус | Вердикт |
|---|---|---|---|
| `Url::to(['/admin/product/duplicate', ...])` | product/view.php:268, кнопка «Дублировать» | **404** | **Исправлено** → `/admin/product/clone` (реальный `ProductController::actionClone($id)`; `actionDuplicate` никогда не существовал) |
| `$targetUrls['Buyout'] = '/admin/procurement/buyout/view'` | activity-log/index.php | **404** | **Исправлено** → `/admin/buyout/view` (лишний сегмент `procurement/` — реальный контроллер `BuyoutController` подключён напрямую в модуль `admin`, не вложен под `procurement`) |
| `$targetUrls['Receiving'] = '/admin/procurement/receiving/view'` | activity-log/index.php | **404** | **Исправлено** → `/admin/receiving/view` (та же причина) |
| `$targetUrls['User'] = '/admin/user/view'` | activity-log/index.php | **404** | **Исправлено** → `/admin/user/edit` (`UserController` вообще не имеет `actionView`, есть только `actionEdit($id)`) |
| `/admin/plugin/cdek`, `/admin/plugin/europochta`, `/admin/plugin/belpochta`, `/admin/plugin/rocketsms` (+ их `save-*`/`test-*` действия) | admin-settings.js, urlManager rules | **404** (все 4 страницы) | **Требует реализации** — см. child issue E. View-файлы (`backend/modules/admin/views/plugin/{cdek,europochta,belpochta,rocketsms}.php`) существуют и содержат полнофункциональный JS для сохранения/теста, но в `PluginController` нет ни `actionCdek()`, ни `actionEuropochta()`, ни `actionBelpochta()`, ни `actionRocketsms()`, ни соответствующих `save-*`/`test-*` действий — 4 целые страницы настроек логистических интеграций недоступны из админки. |
| `/admin/pos/*` (6 эндпоинтов), `URLS.*` в admin-settings.js "POS module pages" (строки 1433–1770) | admin-settings.js | **404** (все) | **Мёртвый JS** — нет `PosController` вообще, и нет ни одного `#pos-config` элемента ни в одном view-файле (JS-блок гейтится `if (!cfg) return`, cfg всегда `null`). "POS TERMINAL" секция (1771–1777) — пустой listener-заглушка. Рекомендация: удалить весь блок 1433–1777 отдельным маленьким PR (не включено в этот коммит — требует аккуратного вычленения границ в файле на 1800+ строк, см. child issue F). |
| `/admin/order/history`, `/admin/order/notifications`, `/admin/order/dp-test` | admin-order-edit.js, admin.js, settings/integrations.php | **404** | **Требует реализации** — см. child issue H. `OrderController` не имеет `actionHistory`/`actionDpTest`, `NotificationController` — только `actionIndex` (без AJAX-эндпоинта для бейджа уведомлений). |
| `/admin/import-ajax/progress`, `/admin/import-ajax/stop`, `/admin/import-ajax/logs`, `/admin/import-ajax/mark-notification-read`, `/admin/import-ajax/mark-all-notifications-read` | admin-settings.js "IMPORT pages"/"IMPORT LOGS" | **404** | **Мёртвый JS** — все триггер-элементы (`.running-task`, `.btn-stop-task`, `.notification-item`, `#mark-all-read`, `#logs-config`) отсутствуют во всех view-файлах; реальный импорт работает через `AdminImportController` (`frontend/controllers/AdminImportController.php`, маршрут `/admin-import/*`), который эти вызовы не используют вовсе. Рекомендация — тот же child issue F. |
| `/admin/update-characteristic`, `/admin/delete-characteristic`, `/admin/create-characteristic`, `/admin/create-characteristic-value`, `/admin/add-characteristic`, `/admin/get-characteristics` | admin-products.js | **404** (все, проверено статически: `CharacteristicController` содержит `actionCreate/Update/Delete/DeleteValue/SizeCreate/...`, но не бары `update-characteristic` и т.п.) | **Контракт разошёлся полностью, не однострочный фикс** — см. child issue I |
| `/admin/user/export` | admin-settings.js `bulkExport()`, реально вызывается с `user/index.php` | **404** | **Требует реализации** (CSV-экспорт пользователей) — см. child issue J |
| `/admin/customer/create` | admin-search.js (быстрое действие в глобальном поиске Ctrl+K) | **404** | **Требует реализации** — см. child issue K (`CustomerController` имеет только `actionCreateFromOrder`, отдельной формы создания «с нуля» нет) |

## Резюме по категориям (как требует ТЗ)

- **404 / маршрута нет, починено примонтированием существующей логики (контракт совпал):** 5
  (`/account/loyalty/balance`, `/api/catalog/get-brands`, `/api/catalog/products-by-ids`,
  `/admin/product/clone`, `/admin/{buyout,receiving}/view` + `/admin/user/edit`).
- **Мёртвый JS, удалён:** `quick-view.js` (customer-facing, 3 эндпоинта). Найден, но НЕ удалён в этом
  коммите (нужна аккуратная точечная правка большого файла) — POS module pages и IMPORT pages/LOGS блоки
  в `admin-settings.js` (child issue F).
- **Требует реализации с нуля — заведены отдельные задачи, без заглушек:** child issues A–E, H–K (см. ниже).
- **Известный баг, не в этом скоупе:** `/api/v1/coupon/validate` (продуктовое решение отдельно, CMP-416).

## Child issues, заведённые по итогам аудита

| Issue | Тема | Оценка |
|---|---|---|
| [CMP-426](/CMP/issues/CMP-426) | Customer: Quick View на странице каталога (`/catalog/product-quick/<id>`) не работает — контракт JSON расходится с готовыми `html`-эндпоинтами | S–M |
| [CMP-427](/CMP/issues/CMP-427) | Customer: 3 фичи без бэкенда — newsletter, обновление трекинга, отмена возврата | S (×3) |
| [CMP-428](/CMP/issues/CMP-428) | Admin: 4 страницы плагинов доставки (CDEK/Европочта/Белпочта/RocketSMS) полностью недоступны (404) | M–L |
| [CMP-429](/CMP/issues/CMP-429) | Admin: удалить мёртвый JS в `admin-settings.js` (POS module pages, IMPORT pages/LOGS — ни один триггер-элемент не существует ни на одной странице) | S |
| [CMP-430](/CMP/issues/CMP-430) | Admin: история/уведомления/dp-test заказа, инлайн-характеристики товара, экспорт юзеров, quick-create покупателя | M (×4 подпункта) |

## Проверка

- `tests/unit/Cmp421RouteContractTest.php` — 6 тестов, 25 assertions, все зелёные: по каждому починенному
  URL — что JS больше не ссылается на битый путь и ссылается на рабочий, и что целевой `action*` существует.
- `tests/unit/Cmp413RouteSweepTest.php` — не сломан (10/10 зелёных), регрессии на предыдущий аудит нет.
- `phpstan analyse` по изменённым PHP-файлам — 0 ошибок, `phpstan-baseline.neon` не трогался.
- `phpcs` по изменённым PHP-файлам — 0 нарушений.
- Живой HTTP «до»/«после» — см. таблицы выше.
