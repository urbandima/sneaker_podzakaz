# CMP-470-A — Сплошной прогон мутирующих действий: деньги/склад/закупки (под-волна А)

Продолжение методологии CMP-456/CMP-460/CMP-463/CMP-467: живой HTTP-запрос через
реальную admin-сессию (`admin/admin123`, cookies + CSRF из `<meta name="csrf-token">`)
против `php -S 127.0.0.1:8781` со сверкой фактической строки в MySQL
(`cmp410_e2e_clean`) до/после каждой мутации — вердикт по эффекту в БД/файловой
системе, а не по HTTP-коду. Payload каждого действия сверен с реальным
вызывающим кодом (inline `<script>` во вьюхах `backend/modules/admin/views/...`),
а не придуман по сигнатуре контроллера.

Все 69 заявленных действий (кроме `BuyoutController::actionLinkOrder`, уже
закрытого в CMP-468) прогнаны живьём — код-ревью-фоллбэк не понадобился.

## Границы охвата

- `FinanceController` — 8 экшенов.
- `ExchangeRateController` — 4 экшена.
- `BuyoutController` — 13 экшенов (`actionLinkOrder` не перепроверялся — см. ниже).
- `ProcurementController` — 14 экшенов.
- `ReceivingController` — 19 экшенов.
- `ReturnController` — 12 экшенов.

Итого 70 пунктов реестра (69 проверено живьём + 1 не тронутый по прямому указанию карточки).

## Реестр

### ExchangeRateController

| Действие | Маршрут | Payload | HTTP | Факт в БД/файле | Вердикт |
|---|---|---|---|---|---|
| `actionCurrent` | `GET /admin/exchange-rate/current` | — | 200 | **до фикса**: JSON без поля `updated_at`, которое реально читает виджет `/admin/plugin/currency` (`d.updated_at \|\| '—'`) — плашка «Обновлено» всегда показывала `—`; **после фикса**: `updated_at` = отформатированный `settings.currency.cny_updated` | **wrong_data → исправлено** |
| `actionUpdate` | `POST /admin/exchange-rate/update` | `{}` (JSON, реальный `fetch` из `plugin/currency.php`) | 200 | реальный запрос к `api.nbrb.by` прошёл, `app_setting.currency.{cny_rate,cny_updated}` записаны верно (0.0453 / unix-время) | **ok** |
| `actionHistory` | `GET /admin/exchange-rate/history` | — | 200 | возвращает **синтетические** данные (`rand(-50,50)/10000` вокруг текущего курса), не реальную историю; ни один JS-файл в репо не вызывает этот маршрут | **ok формально, но dead stub — см. «Найдено, не исправлено»** |
| `actionSettings` | `GET /admin/exchange-rate/settings` | — | **500** | `render('settings', …)` — директории `backend/modules/admin/views/exchange-rate/` не существует вовсе; маршрут нигде не используется как ссылка | **wrong_data (dead route) → не исправлено, см. ниже** |

### FinanceController

| Действие | Маршрут | Payload | HTTP | Факт в БД | Вердикт |
|---|---|---|---|---|---|
| `actionIndex` | `GET /admin/finance/index` | — | 302 → `/admin/finance/payments` | редирект, не мутирует | **ok** |
| `actionPayments` | `GET /admin/finance/payments` | — | 200 | рендер без ошибок, totals считаются | **ok** |
| `actionCreatePayment` | `POST /admin/finance/create-payment` | JSON `{order_id,customer_id,amount,currency,payment_method,status,bank_reference,description}` — ровно то, что шлёт `submitCreatePayment()` из `payments.php` | 200 | строка `payment` создана со всеми полями точно | **ok** |
| `actionConfirmPayment` | `POST /admin/finance/confirm-payment` | JSON `{id}` — ровно то, что шлёт `confirmPayment()` | **до фикса: 500** (`UnknownPropertyException: Payment::updated_at`); после фикса: 200 | до фикса: `status`/`confirmed_by`/`confirmed_at` не менялись НИ РАЗУ ни для одного платежа; после фикса: `status→confirmed`, `confirmed_by`/`confirmed_at` записаны верно | **wrong_data → исправлено (критично)** |
| `actionExpenses` | `GET /admin/finance/expenses` | — | 200 | рендер + группировка по категориям без ошибок | **ok** |
| `actionCreateExpense` | `POST /admin/finance/create-expense` | JSON — ровно то, что шлёт `submitAddExpense()` из `expenses.php` | 200 | строка `expense` создана верно, `created_by` проставлен | **ok** |
| `actionPnl` | `GET /admin/finance/pnl` | — | 200 | рендер по годам без ошибок | **ok** |
| `actionMargin` | `GET /admin/finance/margin`, `?tab=manager` | — | 200 / 200 | оба таба рендерятся без ошибок | **ok** |

### BuyoutController

| Действие | Маршрут (реальный, после фикса роутинга) | Payload | HTTP | Факт в БД | Вердикт |
|---|---|---|---|---|---|
| `actionIndex` | `GET /admin/procurement/buyouts` | — | 200 | рендер, KPI считаются | **ok** |
| `actionView` | `GET /admin/buyout/<id>` | — | **до фикса вью: 404** (вьюхи ссылались на `/admin/procurement/buyout/<id>`, такого маршрута не существует) | после фикса вью — 200, реальная страница | **wrong_data → исправлено (см. дефект №1)** |
| `actionCreate` | `GET/POST /admin/buyout/create` | form: `source,qty,source_currency,...` — реальная HTML-форма `create.php` | **до фикса кнопки: 404** («Добавить выкуп» вела на несуществующий `/admin/procurement/buyout/create»); после фикса: 200/302, редирект **тоже был битым** (`/admin/procurement/buyout/<id>` → 404) до отдельного фикса в контроллере | после обоих фиксов: строка `buyout` создаётся, редирект открывает реальную карточку | **wrong_data → исправлено (см. дефект №1, критично)** |
| `actionUpdate` | `GET/POST /admin/buyout/<id>/edit` | form (те же поля) | **до фикса: 404** (URL-правило `admin/buyout/<id>/edit` вело на несуществующий `actionEdit`) | после фикса `infrastructure/config/web.php`: 200/302, `qty`/`notes`/... меняются верно | **wrong_data → исправлено (см. дефект №1, критично)** |
| `actionDelete` | `POST /admin/buyout/delete?id=N` (только query-string форма — path-форма `/admin/buyout/<id>/delete` не зарегистрирована) | `_csrf` | 302 | строка `buyout` реально удалена (после перевода в `cancelled`); guard «нельзя удалить не-draft/не-cancelled» проверен | **ok, но полностью недостижим из UI — см. «Найдено, не исправлено»** |
| `actionParseUrl` | `POST /admin/buyout/parse-url` | `x-www-form-urlencoded: url=...` — ровно то, что шлёт `parseUrl()` из `create.php` | **до фикса кнопки: 404**; после фикса: 200 | парсинг `example.com` вернул валидный JSON (`name`,`source:"manual"` и т.д.) | **wrong_data → исправлено (см. дефект №1)** |
| `actionLinkOrder` | `POST /admin/buyout/link-order` | — | — | — | **не проверялось — уже исправлено в CMP-468 (коммит 539b7b8), код не трогал** |
| `actionUnlinkOrder` | `POST /admin/buyout/unlink-order` | JSON `{buyout_id,order_id}` — ровно то, что шлёт `unlinkOrder()` | 200 | строка `buyout_order_link` удалена, `buyout_history` запись `order_unlinked` создана | **ok** |
| `actionAccept` | `POST /admin/buyout/<id>/accept` | `{}` | **до фикса кнопки: 404**; после фикса — 200 | `purchase_order` создаётся (`BY-RCV-<id>`), `buyout.status→accepted`, `receiving_id` проставлен; **до фикса supplier**: `supplier_id=1` — несуществующий поставщик (см. дефект №2); после фикса: реальный find-or-create supplier | **wrong_data → исправлено дважды (см. дефекты №1 и №2)** |
| `actionCancel` | `POST /admin/buyout/<id>/cancel` | form `notes` | 200 (прямой вызов) | `status→cancelled`, `notes` с префиксом `[Отмена]` записаны верно | **ok, но недостижим из UI — см. «Найдено, не исправлено»** |
| `actionBulkStatus` | `POST /admin/buyout/bulk-status` | JSON `{ids,status}` — ровно то, что шлёт `bulkChangeStatus()` из `index.php` | **до фикса кнопки: 404**; после фикса: 200 | счётчик `updated` точно совпал с фактически изменёнными строками (1 из 2 — второй id корректно отбит `canTransitionTo`) | **wrong_data → исправлено (роутинг); сам счётчик и так был корректен** |
| `actionHistory` | `GET /admin/buyout/<id>/history` | — | 200 | JSON с реальными транзишенами статуса (автологируется моделью) | **ok** |
| `actionUpdateStatus` | `POST /admin/buyout/update-status` | JSON `{id,status}` — ровно то, что шлёт `changeStatus()` из `view.php` | **до фикса кнопки: 404**; после фикса: 200 | статус меняется, `ordered_at`/`arrived_at`/`accepted_at` проставляются по месту | **wrong_data → исправлено (см. дефект №1)** |

### ProcurementController

| Действие | Маршрут | Payload | HTTP | Факт в БД | Вердикт |
|---|---|---|---|---|---|
| `actionSuppliers` | `GET /admin/procurement/suppliers` | — | 200 | рендер списка | **ok** |
| `actionSupplier` | `GET /admin/procurement/supplier/<id>` | — | **до фикса: 500** (`Supplier::getPurchaseStats()` — `Unknown column 'total_byn'`); после фикса: 200 | — | **wrong_data → исправлено (см. дефект №4)** |
| `actionSupplierSave` | `POST /admin/procurement/supplier-save` | JSON (create + partial update) — ровно то, что шлёт `suppliers.php`/`supplier-view.php` | 200 / 200 | создание: все поля записаны; partial update (только `phone`) — остальные поля не тронуты | **ok** |
| `actionSupplierDelete` | `POST /admin/procurement/supplier-delete` | JSON `{id}` | 200 | строка `supplier` реально удалена | **ok** |
| `actionIndex` | `GET /admin/procurement` | — | 200 | рендер списка закупок | **ok** |
| `actionCreate` | `POST /admin/procurement/create` | form (реальные поля + `item_name[]`/`item_qty[]`/...) | 200/302 | `purchase_order` + 2×`purchase_order_item` созданы, `total_amount_byn` пересчитан верно (10×100+5×75=1375) | **ok** |
| `actionView` | `GET /admin/procurement/view/<id>` | — | 200 | рендер карточки закупки | **ok** |
| `actionUpdateStatus` | `POST /admin/procurement/update-status` | JSON `{id,status}` | 200 | `status→received`, `received_at` проставлен | **ok** |
| `actionReceiving` | `GET /admin/procurement/receiving` | — | 200 | список закупок в статусах ordered/transit | **ok** |
| `actionReceiveItems` | `POST /admin/procurement/receive-items` | JSON `{purchase_order_id, items:[{id,received_qty}]}` — ровно то, что шлёт `saveReceiving()` из `receiving.php` | 200 / 200 | частичная приёмка (67% = 10/15) и полная (100%, `status→received`) — оба раза `received_quantity` и `%` совпали с фактом в БД **точно** | **ok — счётчик НЕ разъехался с фактом, вопреки ожиданию карточки** |
| `actionReturns` | `GET /admin/procurement/returns` | — | 200 | список возвратов поставщикам | **ok** |
| `actionCreateReturn` | `GET/POST /admin/procurement/create-return/<poId>` | form + `item_*[]` | 200/302 | `supplier_return` + `supplier_return_item` созданы, `total_amount` = 200 (2×100) верно | **ok** |
| `actionViewReturn` | `GET /admin/procurement/view-return/<id>` | — | 200 | рендер карточки возврата | **ok** |
| `actionUpdateReturnStatus` | `POST /admin/procurement/update-return-status` | JSON `{id,status:"refunded"}` | 200 | `supplier_return.status→refunded`; побочный эффект — `expense` создан с `amount=-200` (возврат денег как доход) верно | **ok** |

### ReceivingController

| Действие | Маршрут | Payload | HTTP | Факт в БД/файле | Вердикт |
|---|---|---|---|---|---|
| `actionIndex` | `GET /admin/receiving` | — | 200 | рендер, KPI считаются | **ok** |
| `actionView` | `GET /admin/receiving/<id>` | — | 200 | рендер карточки | **ok** |
| `actionCreate` | `GET/POST /admin/receiving/create` | — (создаёт пустой черновик) | 302 | `receiving` (status=draft, номер сгенерирован) создан | **ok** |
| `actionUpdate` | `POST /admin/receiving/<id>/update` | JSON `{notes}` | 200 | `notes` обновлены | **ok, но не вызывается ни из одной вьюхи (дублирует save-field) — не мешает, не трогал** |
| `actionSaveField` | `POST /admin/receiving/save-field` | JSON `{id,field,value}` | 200 | `notes` записаны верно; whitelist полей соблюдён | **ok** |
| `actionSetStatus` | `POST /admin/receiving/set-status` | JSON `{id,status,comment}` | 200×3 | `draft→in_transit→arrived→inspecting`, все переходы валидны и записаны | **ok** |
| `actionAddItem` | `POST /admin/receiving/add-item` | JSON `{receiving_id,product_id,size_id,qty_expected,unit_cost_source,source_currency,exchange_rate}` | 200×2 | `receiving_item` создан, `unit_cost_byn` пересчитан верно (50×25=1250) | **ok** |
| `actionUpdateItem` | `POST /admin/receiving/update-item` | JSON `{id,qty_arrived}` | 200 | **до фикса**: ответ без ключа `item` → JS не обновлял ячейки «Расходы»/«Итого» в строке (хотя в БД всё считалось верно); после фикса: `item.{allocated_expenses_byn,final_cost_byn}` возвращаются | **wrong_data (UI staleness) → исправлено** |
| `actionRemoveItem` | `POST /admin/receiving/remove-item` | JSON `{id}` | 200 | строка `receiving_item` удалена, totals пересчитаны | **ok** |
| `actionAddExpense` | `POST /admin/receiving/add-expense` | JSON `{receiving_id,type,amount,currency,exchange_rate,distribution_method,notes}` | 200 | `receiving_expense` создан, `amount_byn` посчитан через `beforeSave()`; распределение `by_qty` (9/14 и 5/14 от 140 = 90/50) точно совпало с БД | **ok** |
| `actionUpdateExpense` | `POST /admin/receiving/update-expense` | JSON `{id,amount}` | 200 | `amount`/`amount_byn` обновлены, `expenses_total_byn` пересчитан | **ok** |
| `actionRemoveExpense` | `POST /admin/receiving/remove-expense` | JSON `{id}` | 200 | строка удалена, totals пересчитаны | **ok** |
| `actionRedistribute` | `POST /admin/receiving/redistribute` | JSON `{id}` | 200 | пересчёт запущен вручную, totals верны | **ok** |
| `actionUploadDocument` | `POST /admin/receiving/upload-document` (multipart) | `receiving_id,type,file` — ровно то, что шлёт форма загрузки | 200 | **до фикса**: файл физически сохранялся ОДНИМ УРОВНЕМ ВЫШЕ репозитория (`@app/../frontend/web/...` при `@app`=корень репо) — вне реально обслуживаемого `frontend/web/`, возвращаемый `url` был мёртвой ссылкой; после фикса (`@webroot`): файл лежит в `frontend/web/uploads/receiving/<id>/`, URL отдаёт 200 | **wrong_data → исправлено (см. дефект №6)** |
| `actionDeleteDocument` | `POST /admin/receiving/delete-document` | JSON `{id}` | 200 | после фикса пути — файл реально удаляется с диска, не только из БД | **wrong_data → исправлено (тот же дефект №6)** |
| `actionAccept` | `POST /admin/receiving/accept` | JSON `{id}` | 200 | склад обновлён верно: `product_size.stock` +9 и +5 по двум позициям; `status→partial` (т.к. одна позиция принята не полностью — 9 из 10) | **ok** |
| `actionCancel` | `POST /admin/receiving/cancel` | JSON `{id,comment}` | 200 | `status→cancelled` | **ok** |
| `actionFromBuyout` | `POST /admin/receiving/from-buyout/<buyoutId>` | — | **до фикса: 200, `success:false`** (`UnknownPropertyException: Buyout::number`); после фикса: `success:true` | до фикса: **`receiving` строка ВСЁ РАВНО создавалась** (status=arrived, 0 позиций, без истории) несмотря на заявленный `success:false` — классический баг «ответ врёт», только в обратную сторону; после фикса — консистентно создаётся полностью (запись истории тоже проходит) | **wrong_data → исправлено (см. дефект №7); маршрут не имеет UI-вызова — см. границы** |
| `actionProducts` | `GET /admin/receiving/products?q=...` | — | 200 | автокомплит возвращает реальные товары/размеры/цены | **ok** |

### ReturnController

| Действие | Маршрут | Payload | HTTP | Факт в БД | Вердикт |
|---|---|---|---|---|---|
| `actionIndex` | `GET /admin/return/index` | — | 200 | рендер списка + «неявные» возвраты (`order.status='return'`) | **ok** |
| `actionCreate` | `POST /admin/return/create` | form `ReturnRequest[order_id,reason,refund_amount,refund_method,comment]` — реальная `ActiveForm` | **до фикса: 500** (`SQLSTATE[HY000] 1364: Field 'items_json' doesn't have a default value`); после фикса: 302 | до фикса: **строка НИ РАЗУ не создавалась** — админ не мог оформить возврат вручную вообще; после фикса: `return_request` создаётся с `items_json='[]'`, все поля верны | **wrong_data → исправлено (критично)** |
| `actionView` | `GET /admin/return/view/<id>` | — | 200 | рендер карточки | **ok** |
| `actionApprove` | `POST /admin/return/approve/<id>` | form `comment` | 302 | `status→approved`, `processed_at` проставлен, email клиенту создан в `runtime/mail/` (файл-транспорт) | **ok** |
| `actionReject` | `POST /admin/return/reject/<id>` | form `comment` | 302 (пусто — блокировано) / 302 (с комментарием) | без комментария — flash-ошибка, статус не менялся; с комментарием — `status→rejected`, `admin_comment` записан | **ok** |
| `actionProcess` | `POST /admin/return/process/<id>` | — | 302 | `status→processing` | **ok** |
| `actionComplete` | `POST /admin/return/complete/<id>` | — | 302 | `status→refund_pending_manual`, `refund_transaction` записан (честная модель — реальный перевод денег не подтверждён платёжным шлюзом, что и заявлено в коде) | **ok** |
| `actionCompleteStep` (алиас `actionUpdateStep`) | `POST /admin/return/complete-step` | form `id,step` | 200, `success:false` | `checklist_data` — колонки **не существует** в `return_request` вовсе → `UnknownPropertyException`, поймано и превращено в `success:false`; шаг никогда не сохраняется | **wrong_data → не исправлено (нужна миграция + продуктовое решение), см. ниже** |
| `actionPdf` (алиас `actionContract`) | `GET /admin/return/pdf/<id>` | — | 200 (после фикса `_contract.php`) | — | **wrong_data → исправлено (тот же дефект №8, что и `actionContract`)** |
| `actionUpdateStep` | `POST /admin/return/update-step` | form `id,step` — ровно то, что шлёт `markStepDone()` | 200, `success:false` | тот же дефект, что `actionCompleteStep` | **wrong_data → не исправлено, см. ниже** |
| `actionContract` | `GET /admin/return/contract/<id>` | — | **до фикса: 500** (`TypeError: date(): Argument #2 must be of type ?int, string given`); после фикса: 200 | — | **wrong_data → исправлено (см. дефект №8)** |
| `actionStatistics` | `GET /admin/return/statistics` | — | **500** (`ViewNotFoundException: return/statistics.php` не существует) | — | **wrong_data (dead route) → не исправлено, см. ниже** |

## Найденный дефект и фикс

### 1. `BuyoutController` — практически весь UI выкупов был мёртв из-за рассинхрона URL (самая серьёзная находка волны)

Живым прогоном обнаружено: клик по «Добавить выкуп», по любой строке списка
(«Открыть»), по «Редактировать», по кнопке смены статуса, по «Принять
(создать приёмку)», по кнопке парсинга URL на форме создания и по кнопке
массового изменения статуса — **все они вели на 404**.

Корень: `backend/modules/admin/views/buyout/{index,create,view}.php` были
написаны с захардкоженными путями вида `/admin/procurement/buyout/<id>`,
`/admin/procurement/buyout/create`, `/admin/procurement/buyout/update-status`
и т.д. Но `infrastructure/config/web.php` с самого первого коммита фичи
(`dec88f4d1`, 2026-04-26) регистрировал только короткие маршруты
`/admin/buyout/<id>`, `/admin/buyout/create`, `/admin/buyout/update-status` и
т.п. — префикс `/admin/procurement/buyouts` (во множественном числе)
существовал только как алиас для списка (`actionIndex`). Ни один из
остальных «procurement-префиксных» путей никогда не был валиден. Общий
generic-роут `admin/<controller>/<action>` (для 2-сегментных путей) и
`admin/<controller>/<action>/<id>` (для 3-сегментных с числовым `id`
последним) не спасал 3–4-сегментные пути с «лишним» словом `procurement` —
поэтому 404 был стабильным и системным, а не редким крайним случаем.

Отдельно обнаружено: даже правило `admin/buyout/<id>/edit` (которое
формально существовало) указывало на несуществующий `BuyoutController::
actionEdit()` — в контроллере есть только `actionUpdate()`. То есть даже
если бы вьюха ссылалась на правильный короткий URL, страница редактирования
всё равно бы не открылась.

Отдельно обнаружено: сам `BuyoutController::actionCreate()`/`actionUpdate()`/
guard в `actionDelete()` после успешного сохранения делали `redirect(['/admin/
procurement/buyout/' . $id])` — то есть даже если бы форму создания открыли
напрямую по правильному URL, редирект после сохранения ВСЁ РАВНО вёл в 404,
маскируя тот факт, что запись реально сохранилась.

Отдельно обнаружено: `backend/modules/admin/views/receiving/view.php` (карточка
приёмки) ссылалась на связанный выкуп через `Url::to(['/admin/procurement/
buyouts', 'id' => $receiving->buyout_id])` — раз маршрут `admin/procurement/
buyouts` не принимает параметров, Yii просто дописывал `id` как query-string к
списку (`/admin/procurement/buyouts?id=123`), т.е. клик по ссылке открывал
общий список вместо конкретного выкупа.

**Фикс**:
- `backend/modules/admin/views/buyout/index.php`, `create.php`, `view.php` —
  все хардкод-пути `/admin/procurement/buyout/...` заменены на реально
  зарегистрированные `/admin/buyout/...`.
- `backend/modules/admin/views/receiving/view.php` — ссылка на выкуп
  переписана на `Url::to(['/admin/buyout/view', 'id' => ...])`, что через
  обратное сопоставление правила `admin/buyout/<id:\d+> => admin/buyout/view`
  корректно генерирует `/admin/buyout/<id>`.
- `infrastructure/config/web.php` — правило `admin/buyout/<id>/edit` теперь
  указывает на `admin/buyout/update` (реально существующий экшен) вместо
  `admin/buyout/edit`.
- `backend/modules/admin/controllers/BuyoutController.php` — три редиректа
  (`actionCreate`, `actionUpdate`, `actionDelete`-guard) переписаны на
  `/admin/buyout/<id>` вместо мёртвого `/admin/procurement/buyout/<id>`.

Подтверждено живым прогоном: создание, открытие, редактирование, парсинг URL,
массовая смена статуса, точечная смена статуса и «Принять» — все теперь
реально работают и видны в БД (реестр выше).

*(`actionLinkOrder`/`actionUnlinkOrder` уже использовали правильный короткий
URL `/admin/buyout/link-order` — поэтому именно они единственные и работали
до этого фикса, что и позволило CMP-468 закрыть только сам баг 500 внутри
`actionLinkOrder`, не заметив, что весь остальной модуль вокруг него мёртв.)*

### 2. `BuyoutController::actionAccept` — приёмка выкупа создавала «Закупку» с несуществующим поставщиком

Код хардкодил `$po->supplier_id = 1` с комментарием «will be created if
needed; admin can edit» — но ничего это не создавало. Живым прогоном
подтверждено: в этой БД таблица `supplier` пуста (0 строк, включая id=1), у
`purchase_order.supplier_id` нет FK-ограничения — значит созданная запись
молча указывала на несуществующего поставщика, и любая страница, которая
присоединяет `supplier` к такой закупке, показала бы пустое/`null` поле
вместо осмысленного значения (тот же класс бага, что CMP-433/446/467 —
висячая ссылка без FK).

**Фикс**: добавлен приватный метод
`BuyoutController::getBuyoutPlaceholderSupplierId()` — ищет (или создаёт при
первом вызове) реального поставщика с именем «Выкуп (авто)» и использует его
`id`. Подтверждено: повторный `accept` не плодит дубликаты (повторно находит
ту же запись), поставщик реально существует и корректно отображается.

### 3. `Supplier::getPurchaseStats()` — карточка поставщика 500'ила всегда

`SELECT ... SUM(total_byn) ... FROM purchase_order` — колонки `total_byn` не
существует, реальная колонка — `total_amount_byn`. Живым прогоном
подтверждено: `GET /admin/procurement/supplier/<id>` кидал 500 для АБСОЛЮТНО
любого поставщика, то есть карточка поставщика (куда ведёт клик по строке в
`/admin/procurement/suppliers`) была недоступна в принципе.

**Фикс**: `SUM(total_byn)`/`AVG(total_byn)` → `SUM(total_amount_byn)`/
`AVG(total_amount_byn)`. Подтверждено: страница открывается (200), статистика
считается.

### 4. `FinanceController::actionConfirmPayment` — кнопка «Подтвердить платёж» была 100%-ным no-op

Тот же класс бага, что `UserController::actionResetPassword` в CMP-467, но
другая причина: код присваивал `$p->updated_at = date(...)`, а у модели
`Payment`/таблицы `payment` **нет колонки `updated_at`** вообще (только
`created_at`). Присваивание несуществующего атрибута ActiveRecord бросает
`yii\base\UnknownPropertyException` — необработанное исключение превращается
в 500 ДО вызова `save()`. Живым прогоном подтверждено: каждый клик по
«Подтвердить платёж» отвечал 500, `status`/`confirmed_by`/`confirmed_at` не
менялись ни разу ни для одного платежа.

**Фикс**: строка `$p->updated_at = ...` удалена. Подтверждено: `confirm-payment`
возвращает `success:true`, `status→confirmed`, `confirmed_by`/`confirmed_at`
записаны верно.

### 5. `ExchangeRateController::actionCurrent` — виджет курса вечно показывал «Обновлено: —»

`backend/modules/admin/views/plugin/currency.php` (реальная страница «Курс
валют — настройки») и мёртвый дублирующий блок в `admin-settings.js` оба
читают `data.updated_at` для отображения времени последнего обновления —
но `actionCurrent()` никогда не возвращал такое поле (только `date` — текущее
время сервера, не время последнего реального обновления курса).

**Фикс**: в ответ добавлено поле `updated_at`, отформатированное из
`settings.currency.cny_updated`. Подтверждено живым прогоном.

### 6. `ReceivingController::actionUploadDocument`/`actionDeleteDocument` — загруженные документы приёмки сохранялись ВНЕ реального веб-корня

`Yii::getAlias('@app') . '/../frontend/web/uploads/...'` — но в этом
приложении `@app` уже равен корню репозитория (`infrastructure/config/web.php`:
`'basePath' => dirname(__DIR__, 2)`), а не директории `frontend/`. Поэтому
`'@app' . '/../frontend/web/...'` поднимался на один уровень ВЫШЕ репозитория
и писал в директорию-соседа (`.../Сайты_магазины/frontend/web/uploads/...`),
которую этот сайт никогда не отдаёт (реально обслуживаемая директория —
`splitwise/frontend/web/uploads/`). Живым прогоном подтверждено: после
успешной загрузки (`success:true`, `document_id` создан в БД) файл физически
не появлялся в `splitwise/frontend/web/uploads/receiving/`, а появлялся
уровнем выше; ссылка `url`, отданная в ответе и показанная в UI, вела на
несуществующий на сервере файл.

**Фикс**: путь переписан на `Yii::getAlias('@webroot')` — тот же алиас, что
уже используется в `BrandController`/`CategoryController`/`OrderController`
для точно такой же задачи. Подтверждено: файл создаётся в правильной
директории, публичный URL реально отдаёт 200; `actionDeleteDocument`
аналогично исправлен и подтверждён (файл реально удаляется с диска).

### 7. `ReceivingService::createFromBuyout()` — «создание приёмки из выкупа» создавало фантомную запись, ДАЖЕ ЕСЛИ ответ говорил `success:false`

Код обращался к `$buyout->number` — у модели `Buyout` нет такого атрибута
вообще (нет такой колонки в таблице). Обращение бросает
`UnknownPropertyException` **после** того, как `$receiving->save()` уже
закоммитил строку `receiving` (status=arrived) — но раньше, чем добавлялась
позиция и запись истории. `ReceivingController::actionFromBuyout()` ловит
исключение через `catch (\Throwable $e)` и отвечает `{"success":false,...}`.

Живым прогоном подтверждено: каждый вызов реально создавал в БД «фантомную»
запись `receiving` (статус arrived, 0 позиций, без записи в истории) — при
этом ответ клиенту утверждал, что ничего не создано. Это тот же класс бага
«HTTP-ответ не соответствует факту в БД», что уже несколько раз находили в
этой цепочке аудитов, только инвертированный (обычно бывает
`success:true` + no-op; здесь `success:false` + реальная, но повреждённая
запись).

**Фикс**: строка лога переписана на реально существующие атрибуты
(`$buyout->id`, `$buyout->getProductName()`). Подтверждено: `success:true`,
запись `receiving` создаётся полностью и консистентно (включая историю).

*(У маршрута `/admin/receiving/from-buyout/<id>` нет ни одной ссылки ни в одной
вьюхе — баг реален, но недостижим из текущего UI; воспроизведён прямым
вызовом API.)*

### 8. `ReturnController::actionCreate` — «Создать возврат» из админки была полностью нерабочей

`return_request.items_json` — `TEXT NOT NULL` без DEFAULT в схеме БД. Модель
`ReturnRequest` действительно умеет корректно заполнять это поле — но только
через свою фабрику `ReturnRequest::create($orderId, $items, ...)`,
используемую клиентским (frontend) флоу подачи заявки на возврат. Админская
форма `backend/modules/admin/views/return/create.php` — упрощённая, без
выбора конкретных позиций заказа, и `ReturnController::actionCreate()`
никогда не устанавливал `items_json`. Живым прогоном подтверждено: **любая**
попытка администратора вручную оформить заявку на возврат заканчивалась
необработанным `SQLSTATE[HY000] 1364` и HTTP 500 — фича была на 100%
нерабочей, ни одной заявки нельзя было создать вручную из админки.

**Фикс**: перед `save()` — `items_json` дефолтится в `'[]'`, если не
установлен (эта форма изначально не собирает разбивку по позициям, поэтому
пустой список семантически корректен для «возврата по заказу целиком»).
Подтверждено: заявка реально создаётся со всеми полями.

### 9. `backend/modules/admin/views/return/_contract.php` — печать «Договора» для комиссионных возвратов 500'ила

`date('«d» F Y г.', $model->created_at)` — `created_at` в `return_request`
хранится как `DATETIME`-строка (`"2026-09-25 07:42:37"`), а не unix-timestamp;
в PHP 8+ второй аргумент `date()` должен быть `?int`, передача строки кидает
`TypeError`. Живым прогоном подтверждено: `GET /admin/return/contract/<id>`
(и алиас `actionPdf`) отвечал 500 для абсолютно любой заявки.

**Фикс**: `strtotime($model->created_at)`. Подтверждено: страница рендерится
(200), название компании/номер договора/дата — все поля на месте.

## Найдено, не исправлено

- **`ExchangeRateController::actionSettings` — мёртвый маршрут, 500 при прямом
  обращении.** `render('settings', ...)`, а директории `views/exchange-rate/`
  не существует в репозитории вовсе. Ни один JS/PHP-файл в кодовой базе не
  ссылается на `/admin/exchange-rate/settings` — страница функционально
  заменена виджетом `/admin/plugin/currency` (курс + наценка + ручное
  обновление уже там). Не чинил: непонятно, нужна ли эта страница вообще как
  отдельная сущность, или это исторический остаток, который надо удалить —
  вопрос совету.
- **`ExchangeRateController::actionHistory` — честно задокументированная
  заглушка**, отдающая случайные колебания вокруг текущего курса вместо
  реальной 30-дневной истории (комментарий в коде: «в реальности читать из
  таблицы rate_history» — такой таблицы нет). Сейчас никто из UI её не
  вызывает, реального вреда нет, но если планируется дашборд с историей
  курса — нужна отдельная задача на таблицу + запись при каждом
  `actionUpdate`.
- **`BuyoutController::actionDelete` — работает, но полностью недостижим из
  UI.** Ни одна вьюха модуля не содержит кнопку/ссылку на удаление выкупа;
  «дружественный» URL `/admin/buyout/<id>/delete` даже не зарегистрирован как
  правило (работает только вызов через query-string
  `/admin/buyout/delete?id=N` благодаря общему catch-all правилу). Вопрос
  продукту: должна ли админка вообще позволять удалять выкупы (риск потери
  истории закупок), и если да — где именно должна быть кнопка.
- **`BuyoutController::actionCancel` — работает, но не используется UI.**
  Единственный элемент управления статусом на карточке выкупа
  (`changeStatus()`) идёт через `actionUpdateStatus`, который уже покрывает
  переход в `cancelled` (и сам пишет историю). Единственная уникальная
  возможность `actionCancel` — свободный текстовый комментарий причины
  отмены (`notes`) — нигде не собирается в UI. Либо добавить отдельную форму
  «Отменить с комментарием», либо считать `actionCancel` историческим
  дублем и убрать.
- **`ProcurementController` — 5 мутирующих экшенов без явного `VerbFilter`
  (`actionSupplierSave`, `actionSupplierDelete`, `actionUpdateStatus`,
  `actionReceiveItems`, `actionUpdateReturnStatus`)**, в отличие от соседних
  контроллеров, уже прошедших через явное POST-хардининг под CMP-418.
  Проверено предметно: ни один из них не читает данные из `$_GET`/route-
  параметров — все читают исключительно JSON body / `$_POST`, которые пусты
  при обычном GET-запросе независимо от query-string, поэтому классическая
  атака «GET-CSRF через `<img src>`» здесь не работает. Фиксировать не стал
  (нет реальной уязвимости), но для консистентности со стилем остальной
  кодовой базы (после CMP-418) стоит когда-нибудь добавить `VerbFilter` и
  сюда — чисто гигиенически.
- **`ReturnController` — вся ветка «комиссионный возврат» (чек-лист
  договора + кнопка «PDF договор») недостижима из UI и не может сохранить
  прогресс, даже если её вызвать напрямую.** Два независимых пробела в схеме:
  1. `$isCommission = ($model->return_type ?? '') === 'commission'` в
     `view.php` — колонки `return_type` в `return_request` не существует
     вовсе, поэтому `$isCommission` всегда `false`, и ни кнопка «PDF
     договор», ни блок чек-листа никогда не показываются ни для одной
     заявки.
  2. Даже если бы `$isCommission` стал `true` (например, при прямом вызове
     API), `actionUpdateStep`/`actionCompleteStep` пишут в
     `$model->checklist_data` — колонки тоже не существует; живым прогоном
     подтверждено `success:false` с `UnknownPropertyException` при каждой
     попытке отметить шаг выполненным.
  Это выглядит как наполовину реализованная фича (комиссионная торговля —
  приём товара на реализацию с последующим договором), для которой нужны
  минимум 2 миграции (`return_type`, `checklist_data`) и продуктовое решение
  о том, кто и когда проставляет `return_type='commission'`. Не фиксировал
  схему/бизнес-логику — только соседний `_contract.php` (дефект №9), чтобы
  strftime-баг не мешал, если/когда фичу всё-таки достроят.
- **`ReturnController::actionStatistics` — мёртвый маршрут, 500 при прямом
  обращении.** `render('statistics', ...)`, а `views/return/statistics.php`
  не существует. Ни одна вьюха не ссылается на `/admin/return/statistics`.
  Тот же паттерн, что `ExchangeRateController::actionSettings` — нужно
  решение, строить ли дашборд статистики возвратов или удалить маршрут.

## Честная граница охвата

Все 69 заявленных действий (кроме `BuyoutController::actionLinkOrder`,
намеренно не тронутого по прямому указанию карточки) прогнаны полным циклом:
живой HTTP через реальную admin-сессию + сверка MySQL/файловой системы
до/после. Код-ревью-фоллбэк не потребовался — пустых ячеек в реестре нет.

Два уточнения:
- `actionApprove`/`actionReject` в `ReturnController` реально отправляют
  письмо клиенту через `ReturnService::sendApprovalEmail`/
  `sendRejectionEmail` — проверено полным циклом безопасно
  (`MAIL_USE_FILE_TRANSPORT=true`, письма ушли в `runtime/mail/*.eml` и были
  удалены после проверки, реально никуда не отправлялись).
- `ExchangeRateController::actionUpdate` бьёт по реальному внешнему API
  (`api.nbrb.by`) — сеть в этом окружении доступна, запрос прошёл и вернул
  реальный курс ЦБ РБ; это не мок и не заглушка.

## Итого волны

- **69 действий проверено живым прогоном** (100% заявленного объёма, кроме
  `actionLinkOrder`, ранее закрытого в CMP-468 и не тронутого по прямому
  указанию карточки).
- **10 дефектов найдено и исправлено**, из них 3 критичных для бизнеса:
  1. **`BuyoutController` + 3 вьюхи + `web.php`** — системный рассинхрон URL
     ломал создание/просмотр/редактирование/парсинг/массовую смену
     статуса/приём выкупов через UI (самая крупная находка волны).
  2. **`BuyoutController::actionAccept`** — хардкод несуществующего
     `supplier_id=1` → find-or-create реального placeholder-поставщика.
  3. **`Supplier::getPurchaseStats()`** — 500 на карточке любого поставщика
     из-за неверного имени колонки (`total_byn` → `total_amount_byn`).
  4. **`FinanceController::actionConfirmPayment`** — кнопка подтверждения
     платежа была 100%-ным no-op (обращение к несуществующей колонке
     `updated_at`).
  5. **`ExchangeRateController::actionCurrent`** — виджет курса вечно
     показывал «Обновлено: —» (отсутствующее поле `updated_at` в ответе).
  6. **`ReceivingController::actionUploadDocument`/`actionDeleteDocument`** —
     документы приёмки сохранялись за пределами реального веб-корня
     (неверная резолюция алиаса `@app`).
  7. **`ReceivingService::createFromBuyout`** — фантомная запись `receiving`
     создавалась в БД даже когда ответ утверждал `success:false`
     (обращение к несуществующему `Buyout::number`).
  8. **`ReturnController::actionCreate`** — ручное создание заявки на
     возврат из админки было на 100% нерабочим (NOT NULL колонка
     `items_json` без дефолта и без заполнения).
  9. **`return/_contract.php`** — печать договора для комиссионных возвратов
     500'ила (передача DATETIME-строки в `date()` вместо unix-timestamp).
  10. **`ReceivingController::actionUpdateItem`** — построчные ячейки
      «Расходы»/«Итого» не обновлялись в интерфейсе после редактирования
      позиции (ответ не содержал пересчитанный `item`), хотя в БД всё
      считалось верно.
- **6 находок без фикса** — вынесены с открытыми вопросами: мёртвый
  `actionSettings` в `ExchangeRateController` (500, нет UI-вызовов); заглушка
  `actionHistory` там же (синтетические данные вместо реальной истории);
  недостижимые из UI, но рабочие `actionDelete`/`actionCancel` в
  `BuyoutController`; отсутствие `VerbFilter` на 5 экшенах
  `ProcurementController` (проверено — не эксплуатируется, чисто
  гигиенический вопрос); недостроенная фича «комиссionный возврат» в
  `ReturnController` (2 отсутствующие колонки, `return_type`+
  `checklist_data`, нужно продуктовое решение); мёртвый `actionStatistics`
  там же (500, нет UI-вызовов).
- **Тестовые данные полностью откачены и подтверждены финальным `SELECT`**:
  `payment`, `expense`, `app_setting` (секция `currency`), `buyout`,
  `buyout_order_link`, `buyout_history`, `supplier`, `purchase_order`,
  `purchase_order_item`, `supplier_return`, `supplier_return_item`,
  `receiving`, `receiving_item`, `receiving_expense`, `receiving_document`,
  `return_request` — все вернулись к исходным 0 строкам; `product_size.stock`
  для двух тестовых позиций (id 46, 292), изменённый через
  `ReceivingController::actionAccept`, возвращён к исходным значениям (0 и 1
  соответственно); тестовые файлы (`runtime/mail/*.eml`, загруженный
  тестовый документ приёмки и вспомогательная директория-сосед
  `.../Сайты_магазины/frontend/web/uploads/receiving/`, созданная старым
  багом) удалены.
- Изменения оставлены незакоммиченными в рабочем дереве (централизованный
  коммит — за CTO после сведения всех параллельных под-волн CMP-470).

## Проверенные действия (список)

**ExchangeRateController:** 1. `actionCurrent` — **fixed**. 2. `actionUpdate` — ok.
3. `actionHistory` — ok (dead stub, см. «не исправлено»). 4. `actionSettings` — dead route, 500 (не исправлено).

**FinanceController:** 5. `actionIndex` — ok. 6. `actionPayments` — ok.
7. `actionCreatePayment` — ok. 8. `actionConfirmPayment` — **fixed (критично)**.
9. `actionExpenses` — ok. 10. `actionCreateExpense` — ok. 11. `actionPnl` — ok.
12. `actionMargin` — ok.

**BuyoutController:** 13. `actionIndex` — ok. 14. `actionView` — **fixed** (роутинг).
15. `actionCreate` — **fixed (критично)**. 16. `actionUpdate` — **fixed (критично)**.
17. `actionDelete` — ok (недостижим из UI). 18. `actionParseUrl` — **fixed** (роутинг).
19. `actionLinkOrder` — не проверялось (CMP-468). 20. `actionUnlinkOrder` — ok.
21. `actionAccept` — **fixed дважды** (роутинг + supplier). 22. `actionCancel` — ok (недостижим из UI).
23. `actionBulkStatus` — **fixed** (роутинг). 24. `actionHistory` — ok.
25. `actionUpdateStatus` — **fixed** (роутинг).

**ProcurementController:** 26. `actionSuppliers` — ok. 27. `actionSupplier` — **fixed**.
28. `actionSupplierSave` — ok. 29. `actionSupplierDelete` — ok. 30. `actionIndex` — ok.
31. `actionCreate` — ok. 32. `actionView` — ok. 33. `actionUpdateStatus` — ok.
34. `actionReceiving` — ok. 35. `actionReceiveItems` — ok. 36. `actionReturns` — ok.
37. `actionCreateReturn` — ok. 38. `actionViewReturn` — ok. 39. `actionUpdateReturnStatus` — ok.

**ReceivingController:** 40. `actionIndex` — ok. 41. `actionView` — ok. 42. `actionCreate` — ok.
43. `actionUpdate` — ok. 44. `actionSaveField` — ok. 45. `actionSetStatus` — ok.
46. `actionAddItem` — ok. 47. `actionUpdateItem` — **fixed**. 48. `actionRemoveItem` — ok.
49. `actionAddExpense` — ok. 50. `actionUpdateExpense` — ok. 51. `actionRemoveExpense` — ok.
52. `actionRedistribute` — ok. 53. `actionUploadDocument` — **fixed**. 54. `actionDeleteDocument` — **fixed**.
55. `actionAccept` — ok. 56. `actionCancel` — ok. 57. `actionFromBuyout` — **fixed** (недостижим из UI).
58. `actionProducts` — ok.

**ReturnController:** 59. `actionIndex` — ok. 60. `actionCreate` — **fixed (критично)**.
61. `actionView` — ok. 62. `actionApprove` — ok. 63. `actionReject` — ok. 64. `actionProcess` — ok.
65. `actionComplete` — ok. 66. `actionCompleteStep` — не исправлено (нужна миграция). 67. `actionPdf` — **fixed**.
68. `actionUpdateStep` — не исправлено (нужна миграция). 69. `actionContract` — **fixed**.
70. `actionStatistics` — dead route, 500 (не исправлено).
