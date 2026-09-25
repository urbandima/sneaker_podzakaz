# CMP-465 — Аудит прав доступа: изоляция покупателей, защита админки, роли сотрудников

Дата: 2026-09-25. Прогон живьём на локальном приложении (`cmp410_e2e_clean`), под реальными
созданными аккаунтами. Коммит с фиксами: `aa465b6`.

## Вопрос 1 — изоляция покупателей друг от друга

### Реестр owner-check по каждому action с id (по коду)

| Контроллер | Action | id | Owner-check | Файл:строка |
|---|---|---|---|---|
| `AccountController` | `actionOrderView($id)` | order id | `customer_id = $customer->id` (после фикса — без `OR client_email`) | `backend/modules/account/controllers/AccountController.php:205-214` |
| `AccountController` | `actionRefreshTracking($id)` | order id | тот же критерий | `AccountController.php:233-239` |
| `AccountController` | `actionSavePassport($id)` | order id | тот же критерий | `AccountController.php` (savePassport) |
| `ReturnController` (account) | `actionView($id)` | return id | `findModel($id)` + ручная проверка `customer_id != session('customer_id')` | `backend/modules/account/controllers/ReturnController.php:96-103, 201-208` |
| `ReturnController` (account) | `actionCreate($order_id)` | order id | `order->customer_id != session('customer_id')` | `ReturnController.php:113-124` |
| `ReturnController` (account) | `actionCancel($id)` | return id | тот же критерий | `ReturnController.php:175-184` |
| `FavoriteController` | add/remove/toggle/count/ids/clear | product_id (POST) | scope через `Customer::getCurrentCustomerId()` / `session_id`, id самой записи favorite извне не принимается | `frontend/controllers/FavoriteController.php` |
| `ReviewController` (catalog) | `actionCreate()` | product_id (POST) | дубль отзыва проверяется по `product_id + user_id` | `backend/modules/catalog/controllers/ReviewController.php:109-118` |
| `OrderController` (frontend, гостевой доступ) | success/track/view/upload-payment/save-passport/download-payment | **token**, не id | доступ по знанию токена — см. раздел «Токен заказа» | `frontend/controllers/OrderController.php` |

### Живая проверка (два реально созданных покупателя, подмена id)

1. **Захват чужого заказа при регистрации (P0, зафиксирован и закрыт).**
   Гостевой заказ жертвы (`client_email=cmp465.victim@test.local`, id 252) создан без входа.
   Атакующий зарегистрировал аккаунт на тот же email → `CustomerRegisterForm::linkExistingOrders()`
   привязывала гостевые заказы к новому `customer_id` **без подтверждения владения email**.
   Подтверждено на БД: `order.customer_id` заказа 252 после регистрации атакующего стал равен
   `customer.id` атакующего. Повторено с заказом 253 (жертва B) — то же поведение.
   **Фикс:** `linkExistingOrders()` удалён целиком из `CustomerRegisterForm`
   (`backend/modules/account/models/CustomerRegisterForm.php`). Повторный живой прогон после
   фикса (заказ 255 → тест `CMP465-FIXVERIFY`): регистрация атакующего на тот же email больше
   **не** меняет `order.customer_id` — подтверждено `SELECT`.

2. **Чтение/запись чужого заказа по совпадению email (без привязки customer_id).**
   Отдельно от находки 1: `AccountController` искал заказ через
   `WHERE customer_id=X OR client_email=$customer->email` в пяти местах
   (`actionOrderView`, `actionRefreshTracking`, `actionSavePassport`, плюс два внутренних
   вызова). Это давало доступ на чтение/запись **любому**, кто зарегистрировался на email,
   когда-либо указанный в чужом гостевом заказе — независимо от `customer_id`.
   Живой прогон: до фикса — залогиненный атакующий (email = email жертвы) видел заказ жертвы
   в `/account/orders/{id}`. После фикса (критерий сужен до `customer_id` без `OR client_email`)
   — тот же запрос отдаёт 404/redirect, подтверждено HTTP-ответом и повторной сессией.

3. **Избранное.** Customer A и Customer B (реальные новые аккаунты, `custA@test.local` /
   `custB@test.local`) — A добавил товар в избранное, под сессией B запрос `/catalog/favorites`
   и `/favorite/ids` не показывает товар A. Изоляция подтверждена (scope по
   `Customer::getCurrentCustomerId()`, не по id записи).

4. **Возвраты.** Customer A оформил заказ (`CMP465-RETTEST`, id 256) и создал возврат
   (`return_request.id=2`). Под сессией Customer B: `GET /account/returns/2` → доступ
   отклонён (owner-check в `ReturnController::actionView`); `POST /account/returns/2/cancel`
   под B — тот же результат. Подтверждено HTTP-ответом (403/redirect на список) и тем, что
   `return_request.status` не изменился.

5. **Загруженные подтверждения оплаты.** Доступны только через `OrderController` по токену
   заказа (`actionDownloadPayment`) — не по customer_id/id покупателя. См. раздел «Токен».

6. **Профиль/адрес.** Отдельного address book нет — адрес хранится полем на `Order`
   (`delivery_address`/`full_address`), защищён тем же owner-check, что и сам заказ.

### Токен заказа — вердикт: непредсказуем

- Поле `order.token` — `varchar(100) NOT NULL UNIQUE`, индексирован
  (`infrastructure/migrations/m241023_181600_create_orders_table.php:12,46`).
- Генерация: `Yii::$app->security->generateRandomString(32)` (криптографически случайная,
  `random_bytes()` + base64url), длина 32 символа. Не UUID, не последовательный, не производный
  от id. Дублирующая генерация тем же вызовом — `frontend/controllers/OrderController.php:440`.
- Rate-limiting по токену есть только на `actionUploadPayment` (5 попыток / 15 мин, через
  сессию). На `actionView/actionSuccess/actionTrack/actionSavePassport/actionDownloadPayment`
  лимита нет — при длине 32 base64url-символа брутфорс неосуществим, но факт отсутствия лимита
  зафиксирован, не является блокером выпуска.
- `AccountController::actionFindOrders()` не отдаёт токен в JSON-ответе, а высылает ссылку на
  email, зафиксированный в заказе — защита от enumeration соблюдена.

## Вопрос 2 — защита admin/* от анонима и от покупателя

- `enableStrictParsing=false` (`infrastructure/config/web.php:271`) подтверждено — любой
  контроллер/action теоретически достижим по дефолтному роутингу, даже без явного правила в
  `urlManager`. Конкретный инцидент этого класса (CMP-459, `checkout/order/create`) уже закрыт
  отдельно (удаление orphan-контроллера, `d9b821d`).
- `HealthController` (`backend/modules/admin/controllers/HealthController.php`) — **не**
  наследует `BaseAdminController`, `beforeAction()` возвращает `true` без проверки авторизации.
  `/admin/health` анонимно отдаёт статус БД/версию приложения. Информационная утечка низкого
  риска (не PII, не мутирует данные) — зафиксирована, не фикшена в этом прогоне (вне
  критического пути, требует отдельного решения — держать ли health-check публичным для
  внешнего мониторинга). **Заведено в todo как находка сверх объёма, см. ниже.**
- Все остальные `admin/*` контроллеры проверены на достижимость анонимом/покупателем:
  дефолтный `AccessControl` из `BaseAdminController` (`roles=>['@']`) требует залогиненного
  `Yii::$app->user` — единая identity для `/admin` и витрины **не** пересекается с
  customer-сессией (см. вопрос 3 / находку про `getCurrentCustomerId()`), поэтому анонимный
  посетитель и обычный покупатель (без записи в таблице `u***`) не проходят ни один
  `admin/*` AccessControl. Живой прогон: `GET /admin/customer/index` под сессией покупателя
  (Customer A) → redirect на `/admin/login`, не 200.
- Мутирующие admin-действия без scoping (не «анонимный доступ», а «доступ шире заявленного
  внутри персонала») — см. вопрос 3.

## Вопрос 3 — роли сотрудников внутри админки

**Роли есть, но соблюдаются частично.** Строковое поле `u***.role`
(`admin|director|manager|logist`, `backend/modules/admin/models/User.php:55-58`) — основной
механизм. Параллельно существует Yii2 RBAC (`DbManager`, таблицы засеяны миграцией
`m260426_160000_rbac_roles_and_permissions.php`), но реально используется (`requirePermission`)
только в `ProductController` и `PluginController` — в остальных ~30 admin-контроллерах роль
проверяется (если проверяется) через `isAdmin()/isManager()/isLogist()` напрямую или не
проверяется вовсе.

**Найдена и закрыта в этом прогоне:**
- `CustomerController` (admin) — docblock заявлял «доступ: админы и менеджеры», но
  AccessControl этого не обеспечивал: любой залогиненный сотрудник, включая `logist`, мог
  сбросить пароль покупателя (`actionResetPassword`), удалить покупателя (`actionDelete`),
  начислить/списать баллы лояльности (`actionAddPoints/actionDeductPoints/actionAdjustPoints`),
  выгрузить PII (`actionExport`). **Фикс:** добавлено явное `matchCallback`-правило
  (`isAdmin() || isManager()`) в `behaviors()`.
- `OrderApiController::actionAddNote/actionHistory` — не имели logist-scoping, в отличие от
  остальных admin/OrderController-экшенов (там logist ограничен `assigned_logist == id`).
  **Фикс:** добавлен тот же scoping.

**Не фиксилось в этом прогоне (вне объёма по прямому указанию CEO — решение о ролевой модели
принимается отдельно):** значительная часть admin-контроллеров без `$adminOnly/$financeOnly/
$procureOnly` и без собственной ролевой `AccessControl` — `DashboardController`,
`EmailController`, `FeedbackController`, `LoyaltyController`, `MarketingController`,
`NotificationController`, `PdfController`, `ShippingController`, `TelegramBotController`,
`TrackingController`, `StatisticsController`, `CouponController`, `ReceivingController`,
`BuyoutController`, `ExchangeRateController`, `PageController`, `ProductTagController`,
`SeoController` — доступны **любому залогиненному сотруднику**, включая `logist`, без
дифференциации. Для части из них (например, `LoyaltyController`, `CouponController` — прямое
финансовое воздействие) это тот же класс риска, что был закрыт для `CustomerController`.

**Оценка объёма на ролевую модель (если её решат вводить полноценно):** 17 контроллеров без
дифференциации выше — приведение каждого к явному `matchCallback`/RBAC-правилу того же вида,
что уже сделано для `CustomerController`, ~0.5-1 день на контроллер с живой проверкой (найти
чувствительные actions, определить целевую роль у CEO/board, применить, прогнать). Полный
список кандидатов и разбивка по чувствительности — отдельная карточка, не в этом прогоне
(см. «Находки сверх объёма» ниже).

## Найденные дыры — фиксы и доказательства

Все 4 фикса в коммите `aa465b6` (main):

1. `CustomerRegisterForm::linkExistingOrders()` удалён — захват чужого заказа при регистрации.
   Доказательство: живой повтор после фикса, `order.customer_id` не меняется (заказ 255,
   удалён после проверки).
2. `AccountController` — снят `OR client_email` критерий в 5 местах (orderView,
   refreshTracking, savePassport + 2 внутренних). Доказательство: HTTP-запрос атакующего
   (тот же email, другой customer_id) к `/account/orders/{id}` жертвы после фикса не отдаёт
   заказ.
3. `Admin/CustomerController::behaviors()` — явное ролевое правило. Доказательство: до фикса
   logist проходил AccessControl на мутирующие actions (по коду, `roles=>['@']` без доп.
   ограничения); после — `matchCallback` требует `isAdmin()||isManager()`.
4. `Admin/OrderApiController::actionAddNote/actionHistory` — добавлен logist-scoping по
   `assigned_logist`, аналогично остальным admin/OrderController-экшенам.

## Тестовые данные — очистка подтверждена

Живые проверки создавали реальные записи в `cmp410_e2e_clean`. Удалены после использования:

- `customer.id` IN (250, 251, 252, 254, 255, 256) — 6 тестовых покупателей (victim, attacker,
  victimB, custA, custB, victimguest).
- `order.id` IN (252, 253, 255, 256, 257) — 5 тестовых заказов, вместе с `order_item` и
  `order_history` на эти заказы.
- `return_request.id = 2` — тестовый возврат под Customer A.
- `product_favorite.id = 7` — тестовая запись избранного под Customer A.

Подтверждено `SELECT COUNT(*)` по всем таблицам после `DELETE` — 0 строк по всем условиям.

## Находки сверх объёма — в todo, с исполнителем

1. **`HealthController` анонимно доступен на `/admin/health`.** Отдаёт статус БД/версию без
   авторизации. Низкий риск (не PII, не мутация), но не решение этого прогона — требует
   позиции: должен ли health-check быть публичным для внешнего мониторинга. → CMP-469,
   self-assigned, todo.
2. **17 admin-контроллеров без ролевой дифференциации** (список выше) — оценка объёма дана,
   решение о ролевой модели за CEO. → включено в 30-дневный техроадмап, не заведено отдельной
   карточкой по прямому указанию CEO («не изобретать роли в этой карточке»).
3. **Отсутствие rate-limit на токен-based actions заказа** (кроме upload-payment) — риск
   низкий при текущей длине токена (32 base64url), не блокер. Зафиксировано как наблюдение,
   не заводится отдельной карточкой (не нашли эксплуатируемого сценария при текущей энтропии
   токена).
