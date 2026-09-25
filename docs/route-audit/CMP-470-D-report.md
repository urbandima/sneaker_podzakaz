# CMP-470-Д — Read-only/отчётная волна: Analytics, Dashboard, ActivityLog, Health, Search, Statistics, Tracking, DevTools, Pdf

Продолжение методологии CMP-456/460/463/467/468/469. Родительская карточка
объявила эту под-волну «преимущественно read-only» и разрешила облегчённый
проход — но только для экшенов, реально подтверждённых как read-only. Ниже —
по каждому из 8 контроллеров (HealthController сознательно не в объёме, см.
ниже) явный вердикт: read-only-и-подтверждено, либо разобрано полной
методологией (живой HTTP + MySQL/файловый дифф до/после).

Дев-сервер: `php -d session.save_path=<стабильная директория> -S 127.0.0.1:8784
-t frontend/web router.php` — обычный `php -S ... router.php` без явного
`session.save_path` на этой машине периодически терял сессию между
последовательными `curl`-вызовами (built-in сервер резолвит temp-директорию
сессий через `$TMPDIR` процесса; на машине параллельно крутится несколько
чужих dev-серверов от других агентов этой же волны аудита) — тот же артефакт
окружения, что и в CMP-467. После явного `session.save_path` сессия стала
стабильной для всех живых тестов ниже.

## Итоговый вердикт по контроллерам

| Контроллер | Экшенов | Вердикт |
|---|---|---|
| `AnalyticsController` | 19 | **read-only, подтверждено** (одно уточнение: `actionExportChats` пишет CSV-снимок на диск — см. ниже) |
| `DashboardController` | 7 | **частично мутирующий, разобран полной методологией** (`actionSettings`, `actionUpdateCnyRate`, `actionLogout`, `actionProfile`) |
| `ActivityLogController` | 2 | **read-only, подтверждено** |
| `HealthController` | 1 | **не входит в объём — закрыто CMP-469** (shared-secret токен, commit `40c072e`), не проверял |
| `SearchController` | 2 | **read-only, подтверждено** (1 живой баг найден и исправлен — 500 при совпадении с пользователем) |
| `StatisticsController` | 2 | **read-only, подтверждено** |
| `TrackingController` | 2 | **read-only по намерению, но было полностью мёртвым/битым — 2 бага найдены и исправлены** в `actionPublic`; `actionCheck` read-only подтверждён |
| `DevToolsController` | 4 | **2 read-only подтверждены** (`index`, `export-diagnostics`); **`clear-cache`/`clear-logs` — только код-ревью, живой прогон намеренно не делал** (см. ниже) |
| `PdfController` | 3 | **read-only по намерению, но было полностью битым — 2 бага найдены и исправлены** в `actionPublic`; `actionInvoice`/`actionPrint` read-only подтверждены |

## Реестр

### AnalyticsController (19/19 — read-only, подтверждено)

Доступ: `AccessControl` с `matchCallback` — только `isAdmin() || isManager()`;
живым прогоном подтверждено (`manager` → 200, без cookies → 302). Все 19
экшенов прогнаны живым GET под admin-сессией, без единой fatal-ошибки:

`index`, `conversion`, `conversions`, `sales`, `instagram`, `rfm`, `rfm-api`,
`team`, `export-rfm`, `export`, `chats`, `amocrm`, `moysklad`, `summary`,
`export-chats`, `export-orders`, `export-customers`, `export-deals`,
`export-products` — все 200, без исключений.

- `actionAmocrm`/`actionChats`/`actionMoysklad`/`actionSummary`/`actionExportDeals`/`actionExportChats`
  — как и предполагала карточка, это read-only дашборды статистики: делают
  только `GET`-запросы к AmoCRM/МойСклад API (`getLeads`, `getPipelineStatuses`,
  `getPayments`, `getInvoices` — нет ни одного `POST`/`PATCH` к внешним
  системам). `AMOCRM_LONG_TOKEN` сейчас невалиден (см. память
  `project_amocrm_token_state`) — все AmoCRM-вызовы в проде тоже сейчас падают
  в `catch (\Throwable $e)`, что подтверждено живьём: страницы рендерятся с
  `tokenStatus:'error'`/пустыми данными, ни одного 500. Живых боевых вызовов
  на реальный AmoCRM/МойСклад намеренно не делал сверх того, что уже
  безопасно (GET, читает).
- `actionExportChats` — единственный экшен контроллера, который **пишет файл
  на диск** (`backend/modules/admin/exports/chat_dataset_<date>.csv`, помимо
  стриминга CSV в ответ). Подтверждено живым вызовом: создался
  `chat_dataset_2026-09-25.csv` (3 байта — только BOM, т.к. AmoCRM токен
  невалиден и оба pipeline вернули 0 лидов без исключения). Это осознанное
  поведение («Persist dataset to disk» в комментарии кода), не баг — но раз
  экшен не строго read-only, фиксирую явно. Тестовый файл удалён после
  проверки (в `exports/` остался только досюда существовавший
  `chat_dataset_2026-05-04.csv` — не мой).
- RFM: в контроллере два независимых, самосогласованных механизма сегментации
  (`actionRfm()` — 7 сегментов `champions/loyal/potential/new/at_risk/lost/no_orders`
  по усреднённому R/F/M-скору, используется только вьюхой `rfm.php`; и
  `getRfmSegments()`/`getRfmCustomersForSegment()` — 5 сегментов
  `Champion/Loyal/At Risk/Lost/New` по отдельным порогам, используется
  `actionRfmApi`/`actionExportRfm`/виджетом на `index.php`). Проверил: нигде
  эти два набора ключей не смешиваются (`index.php`'s экспорт-ссылка берёт
  `$seg['segment']` из того же `getRfmSegments()`, что и сам экспорт) — не
  баг, просто две разные модели для разных вьюх. Подтверждено живым запросом:
  `rfm-api` и `export-rfm?segment=Loyal` используют одинаковые метки.
- На странице `rfm.php` кнопки «Экспорт» (at-risk), «Отправить email/SMS»,
  «Создать предложение» — это `alert('...будет здесь...')`-заглушки в
  `frontend/web/js/admin-settings.js` (`exportAtRisk`, `sendEmail`, `sendSms`,
  `createOffer`), не бьют ни в один контроллер. Не баг конкретно этой волны
  (это фронтенд-заглушка, не экшен контроллера), но фиксирую как
  информационную находку — фича на странице выглядит рабочей, но не делает
  ничего.

### DashboardController (7 экшенов — 4 read-only, 3 мутирующих, разобраны полностью)

Доступ по умолчанию (без `adminOnly`) — любой авторизованный сотрудник;
`actionSettings` дополнительно проверяет `isAdmin()` внутри метода (throw
`NotFoundHttpException` иначе — живым прогоном не тестировал под non-admin,
код проверен, стандартный паттерн).

| Действие | Маршрут | Вердикт |
|---|---|---|
| `actionIndex` | `GET /admin/dashboard/index` | read-only, ok (200, без исключений) |
| `actionImageHealth` | `GET /admin/dashboard/image-health` | read-only, ok (только `file_exists()`, ничего не пишет) |
| `actionCnyRate` | `GET /admin/dashboard/cny-rate` | read-only, ok |
| `actionUpdateCnyRate` | `POST /admin/dashboard/update-cny-rate` | мутирует **только кэш** (не БД) — см. дефект/открытый вопрос №1 ниже |
| `actionProfile` | `POST /admin/dashboard/profile` | мутирует `user.password_hash`/`auth_key` — живым прогоном подтверждено, **ok** |
| `actionSettings` | `POST /admin/dashboard/settings` | мутирует `company_settings` + `order_status` — живым прогоном подтверждено, **ok** |
| `actionLogout` | `GET /admin/dashboard/logout` (было) | мутирует сессию — найден и исправлен минорный gap (см. дефект №2) |

**Живой прогон `actionUpdateCnyRate`:** `POST` с `X-CSRF-Token` из
`meta[name=csrf-token]` (тот же паттерн, что реальный вызывающий JS) →
`{"success":true,"rate":0.452,"source":"exchange-rate-api"}`. CSRF реально
проверяется (без токена — `400 Bad Request`, "Не удалось проверить переданные
данные"). Подтверждено: состояние живёт только в `Yii::$app->cache`
(`currency_cny_to_byn_rate*` ключи) — **ничего не пишется ни в `app_setting`,
ни в `settings`**. См. открытый вопрос №1.

**Живой прогон `actionSettings`:** два независимых `<form>` на одной
странице (реквизиты компании — `ActiveForm` на `CompanySettings`; статусы —
чистый `<form>` с полями `statuses[<key>][...]`), оба POST'ят на тот же URL,
обработчик — один метод. Проверено оба сценария реального использования:
- POST только с полями `CompanySettings[...]` (как при клике «Сохранить
  реквизиты») → `company_settings` обновилась (9→9 полей), `order_status`
  **не тронут** (21 строка, `$post['statuses'] ?? []` → пустой массив →
  `foreach` не выполняется ни разу — в отличие от бага CMP-467
  `SettingsController::actionSaveStatuses`, здесь пустой список НЕ
  интерпретируется как «удалить всё», а просто ничего не делает).
- POST только с полями `statuses[<key>][...]` (реальная форма — 21 строка,
  как их шлёт реальная вёрстка) → все 21 статус сохранены с точными
  значениями (проверено флипом `is_active` для `return`: 1→0→обратно 1),
  `company_settings` **не тронут** (`$settings->load($post)` возвращает
  `false` — нет ключа `CompanySettings` в данных).
Дефектов не найдено — реализация корректна и без гонки форм (в отличие от
JS-дубликата в CMP-467).

**Живой прогон `actionProfile`:** создан одноразовый тестовый пользователь
(`cmp470d_test`, role=manager, id=6, через уже проверенный в CMP-467
`UserController::actionCreate`), вошёл под ним, отправил реальный payload
формы (`ChangePasswordForm[old_password/new_password/new_password_repeat]`).
`password_hash` в БД изменился (`$2y$13$Cey...` → `$2y$13$AK0...`); вход под
старым паролем — «Неверное имя пользователя или пароль»; вход под новым —
успешен. Тестовый пользователь удалён после проверки.

### Дефекты DashboardController

**№1 (открытый вопрос, не исправлено): два независимых источника истины для
курса CNY.** `DashboardController::actionUpdateCnyRate` +
`CurrencyService::getCnyToBynRate()`/`clearCache()` хранят курс **только в
`Yii::$app->cache`** (`currency_cny_to_byn_rate*`, TTL 86400с, при `flush()`
или падении кэша откатывается на дефолт 0.45). Этот курс используется
`PoizonApiService::calculatePoizonPriceByn()` и `PoizonImportController` —
то есть **реальным ценообразованием закупки**. Параллельно
`ExchangeRateController::actionUpdate` + `getCurrentRate()` хранят **свой
отдельный** курс в `app_setting`/`settings` (`Yii::$app->settings->get/set('currency','cny_rate')`)
— переживает рестарт и flush кэша, используется виджетом
`backend/modules/admin/views/plugin/currency.php`. Обновление курса через
дашборд НЕ влияет на то, что видит виджет в `/admin/plugin/currency`, и
наоборот — обновление там не обновляет то, что реально видит
`PoizonApiService`. Это тот же класс проблемы, что и найденные ранее
дубли-источники истины (ср. `CMP-467`'s dead-write в `{{%settings}}`).
**Вопрос совету:** объединить в один источник (какой — `app_setting`
переживает рестарт, значит логичнее) или сознательно оставить два разных
курса для разных целей? Не чинил сам — архитектурное решение, требует
выбора владельца.

**№2 (исправлено): `actionLogout` — GET без VerbFilter/CSRF-защиты.**
`Yii::$app->user->logout()` разрушает сессию по простому `GET`, без проверки
метода и CSRF — в отличие от родственного `/admin/logout`
(`AdminController::actionLogout`, уже ограничен `'logout' => ['post']`).
Живым прогоном подтверждено до фикса: `GET /admin/dashboard/logout` от
авторизованного пользователя → мгновенный редирект + разлогин. Ничего в
текущей вёрстке не ссылается на этот дублирующий маршрут (везде используется
`/admin/logout`), поэтому это не активный баг с потерей данных, а
hardening — сторонний `<img src="...">`/ссылка на скрытой странице могла бы
принудительно разлогинить любого залогиненного сотрудника. **Фикс**:
добавлен `VerbFilter` (`'logout' => ['POST']`), по образцу
`AdminController`. Подтверждено живым прогоном: `GET` → `405`, сессия
осталась жива; `POST` с валидным CSRF → `302`, реальный разлогин
подтверждён (`GET /admin/dashboard/index` после → `302` на логин).

### ActivityLogController (2/2 — read-only, подтверждено)

`adminOnly = true` — подтверждено живьём (`manager` → `403`, без cookies →
`302`). `actionIndex` — 200 с фильтрами (`period/target_type/action/source/search`)
и без. `actionExportCsv` — 200, реальный CSV с BOM, кириллица цела, счётчик
записей не сбрасывается (чистый `SELECT` без побочных эффектов).

### HealthController — не в объёме

Закрыт shared-secret токеном в CMP-469 (commit `40c072e`, fail-closed 503 без
токена). Не проверял по прямому указанию карточки этой волны.

### SearchController (2/2 — read-only, подтверждено; 1 живой баг найден и исправлен)

Доступ: любой авторизованный (`roles => ['@']`) — подтверждено (`manager` →
200, без cookies → 302).

**Дефект (исправлено): `actionGlobal` — 500 при совпадении поиска с
пользователем.** Строка `'url' => Url::to([...])` использовала класс `Url`,
который **нигде не импортирован** в файле (`use`-секция содержит только
`Yii, Response, AccessControl, Order, Product, User`). Живым прогоном
подтверждено: `GET /admin/search/global?q=admin` (совпадает с логином
администратора) → `HTTP 500` (`Class 'app\...\controllers\Url' not found`).
Это `\Error`, а не `\Exception` — `catch (\Exception $e)` вокруг блока
пользователей его не ловит. Баг живой и тривиально воспроизводимый для
ЛЮБОГО поискового запроса, совпадающего с username/email хоть одного
пользователя, для любого админа/менеджера, использующего глобальный поиск.

**Фикс**: заменил `Url::to([...])` (строка) на голый array-формат
`['/admin/user/edit', 'id' => $userModel->id]` — ровно так же, как уже
возвращают записи `order`/`product` в этом же методе. Проверил
JS-потребитель (`frontend/web/js/admin.js:renderSearchResults`) — он **уже**
умеет резолвить оба формата (`urlFromYiiArray()` для array/object,
raw-строка иначе), так что array-формат — не только более безопасный, но и
единственно консистентный с остальным ответом. Подтверждено живым прогоном:
`GET .../global?q=admin` → `200`, корректный `url: {"0":"/admin/user/edit","id":1}`;
`actionOrders` (не менял) — тоже подтверждён живьём, `200`.

### StatisticsController (2/2 — read-only, подтверждено)

`actionIndex` — чистый редирект на `/admin/analytics` (подтверждено, `302`).
`actionExportCsv` — 200, CSV с BOM, без побочных эффектов (чистые `SELECT`).

### TrackingController (2 экшена — 1 read-only ok, 1 был полностью мёртвым/битым, исправлен)

`actionCheck($orderId)` — read-only, подтверждено. Использует **заглушку**
(`$mockStatuses` — захардкоженные ответы), никакого реального похода к API
Белпочты/Европочты/СДЭК нет — комментарий в коде честно называет это
"Заглушка - в реальности интеграция с API". Живым прогоном: заказ без
`track_number` → `{"success":false,"message":"Трек-номер не указан"}`, без
исключений.

**Дефекты `actionPublic($token)` — оба исправлены:**

1. **Несуществующая колонка.** `Order::find()->where(['public_token' => $token])`
   — колонки `public_token` **нет** в таблице `order` вообще (проверено
   `SHOW COLUMNS`); реальная колонка — `token` (varchar(100), UNIQUE,
   генерируется `Yii::$app->security->generateRandomString(32)` в
   `frontend\controllers\OrderController::actionCreate` — криптографически
   случайный, уже безопасный). Каждый вызов кидал SQL-ошибку "Unknown
   column" → 500.
2. **Несуществующая вьюха.** После фикса колонки — `The view file does not
   exist: .../frontend/views/order/tracker.php`. Реальная (уже рабочая)
   вьюха называется `track.php` и уже используется идентичным по смыслу
   `frontend\controllers\OrderController::actionTrack($token)`.

**Фикс**: колонка `public_token` → `token`; вьюха `.../order/tracker` →
`.../order/track` (переиспользована существующая, а не придумана новая).
Подтверждено живым прогоном: `GET /admin/tracking/public?token=<реальный>`
→ `200`, рендерится «Статус заказа»; несуществующий токен → `404` (не 500).

**Открытый вопрос (не исправлено): `actionPublic` недоступен своей
целевой аудитории.** И после обоих фиксов действие всё ещё наследует
`BaseAdminController`'s `AccessControl` (`roles => ['@']`), а
`Yii::$app->user->identityClass` во всём приложении — единственный,
`admin\models\User` (см. `infrastructure/config/web.php:159`, один и тот же
компонент `user` для всего приложения, не только для admin-модуля).
Подтверждено живьём: без admin-cookie → `302` на `/admin/login`. То есть
клиент (не сотрудник) физически не может открыть «публичную» ссылку на
трекер по токену — она требует активной сессии сотрудника админки. При этом
ничего в репозитории вообще не ссылается на `/admin/tracking/public/...`
(грепнул все вьюхи и JS) — это полностью неиспользуемый, ранее вдобавок
битый дубликат уже рабочего и уже гостевого
`frontend\controllers\OrderController::actionTrack($token)`. Не менял модель
доступа сам (это решение о том, нужен ли вообще второй, admin-модульный
«публичный» вход в трекер, и если да — как гостю попадать в этот модуль без
сессии сотрудника) — вопрос совету: удалить как мёртвый дубликат, или
осознанно открыть гостям (с ревью безопасности такого шага)?

### DevToolsController (4 экшена — 2 read-only подтверждены, 2 — только код-ревью)

`adminOnly = true` — подтверждено живьём (`manager` → `403`, без cookies →
`302`).

- `actionIndex` — read-only, подтверждено (`200`, полная диагностика БД/кэша/логов/маршрутов/файлов/производительности, без исключений).
- `actionExportDiagnostics` — read-only, подтверждено (`200`, тот же набор данных в JSON).
- `actionClearCache` / `actionClearLogs` — **живьём не запускал**, по явному
  предупреждению карточки: на машине параллельно работает несколько других
  агентов этой же аудиторской волны поверх **того же чекаута репозитория**
  (подтверждено — `git status` в начале и в процессе работы показывал живые
  несохранённые изменения от других контроллеров: `AmoCrmController`,
  `BuyoutController`, `ExchangeRateController`, `FinanceController`,
  `MoyskladController`, `TelegramBotController` и др. — значит и `@runtime`
  один физический путь на всех). Код-ревью:
  - `actionClearCache` → `Yii::$app->cache->flush()` — сбрасывает **весь**
    сконфигурированный кэш-компонент приложения целиком (не только dev-tools
    тестовый ключ), включая, например, `currency_cny_to_byn_rate*` из
    дефекта №1 выше. Обёрнуто в try/catch, разумный blast radius (кэш по
    определению одноразовый/регенерируемый), но не изолирован по агенту/сессии.
  - `actionClearLogs` → `FileHelper::findFiles('@runtime/logs', ['only' => ['*.log']])`
    + `file_put_contents($file, '')` — обнуляет **все** `*.log` в
    `runtime/logs`, включая общий `app.log` (на момент проверки — 8+ МБ
    накопленной истории, в т.ч. от параллельных агентов). НЕ трогает
    `runtime/mail/*.eml` и другие поддиректории `runtime/` — в этом смысле
    аккуратно заскоуплен на `*.log`, но `app.log` — общий на всех, кто сейчас
    работает с этим чекаутом.
  - Оба экшена подтверждены как `POST`-only через `VerbFilter`
    (`$behaviors['verbs']['actions']['clear-cache'/'clear-logs'] = ['POST']`)
    — проверено живьём безопасным способом: `GET` на оба маршрута отбивается
    до выполнения кода экшена (`clear-cache` → `405` под живой admin-сессией,
    подтверждающей, что access-control уже пройден и именно verb-фильтр
    блокирует). Мутирующий код физически не мог выполниться при этой
    проверке.
  - **Рекомендация**: безопасно гонять `clear-cache`/`clear-logs` живьём
    только когда точно известно, что никакой другой параллельный
    агент/дев-сервер сейчас не работает с этим же чекаутом.

### PdfController (3 экшена — 1 read-only ok, 1 read-only ok, 1 был полностью битым, исправлен)

`actionInvoice($id)` и `actionPrint($id)` — read-only, подтверждены живьём
(`200`, HTML-накладная, идентичный контент — 6060 байт на тестовом заказе).
TCPDF не установлен в проекте (`class_exists('TCPDF')` → `false`, `tcpdf` не
в `composer.json`) — оба действия корректно уходят в HTML-fallback
(`$this->render('invoice', ...)`), это не баг, ожидаемое поведение при
отсутствии библиотеки.

**Дефекты `actionPublic($token)` — оба исправлены (тот же класс багов, что и
`TrackingController::actionPublic` выше):**

1. **Несуществующая колонка** `public_token` → `token` (тот же корень, что
   и в `TrackingController` — см. подробности там).
2. **Несуществующая вьюха.** `generateHtmlInvoice()` (единственный код-путь,
   доступный `actionPublic()`, поскольку TCPDF отсутствует ВСЕГДА, не только
   локально) рендерил `renderPartial('invoice-html', ...)` —
   такого файла нет нигде в репозитории. **Это означает, что
   `actionPublic()` гарантированно кидал 500 в любом окружении, включая
   прод** (не артефакт локального дев-сервера — TCPDF не установлен в
   принципе).

**Фикс**: колонка `public_token` → `token`; `renderPartial('invoice-html', ...)`
→ `renderPartial('invoice', ...)` (переиспользована уже существующая,
уже рабочая вьюха — та же самая, что `actionInvoice`/`actionPrint` уже
успешно рендерят в идентичном «TCPDF недоступен» случае). Подтверждено
живым прогоном: `GET /admin/pdf/public?token=<реальный>` → `200`, HTML
идентичен `actionInvoice`/`actionPrint` (6060 байт); несуществующий токен →
`404`.

**Тот же открытый вопрос про доступность**, что и для
`TrackingController::actionPublic` (см. выше) — `actionPublic` здесь тоже
наследует admin-only `AccessControl`, тоже нигде не используется во вьюхах
(только `pdf/invoice` реально линкуется из `shipping/dispatch.php`), и тоже
физически недоступен клиенту без сессии сотрудника. Тот же вопрос совету:
удалить или дать реальный guest-доступ.

## Итого волны

- **~42 экшена проверено** across 8 контроллеров в объёме (HealthController
  сознательно пропущен).
- **AnalyticsController, ActivityLogController, StatisticsController** —
  полностью read-only, подтверждено, без багов.
- **SearchController** — read-only, 1 живой баг найден и исправлен
  (500 при поиске, совпадающем с пользователем — отсутствующий `use Url`).
- **TrackingController, PdfController** — `actionPublic` в обоих был
  **на 100% гарантированно битым в любом окружении** (несуществующая
  колонка + несуществующая вьюха в каждом), оба фикса применены и
  подтверждены живым прогоном; в обоих же остался задокументированный
  открытый вопрос о недостижимости для реальных клиентов (admin-only gate).
- **DashboardController** — не полностью read-only, как и предполагала
  карточка; `actionSettings`/`actionProfile` разобраны полной методологией
  (живой HTTP + MySQL-дифф) — багов не найдено, обе реализации корректны;
  `actionUpdateCnyRate` мутирует только кэш (не БД) — работает, но вскрыл
  архитектурный вопрос о двух независимых источниках истины для курса CNY
  (не чинил, вопрос совету); `actionLogout` — минорный hardening-фикс
  (добавлен `VerbFilter`, GET-триггер разлогина закрыт).
- **DevToolsController** — 2 из 4 экшенов read-only-подтверждены;
  `clear-cache`/`clear-logs` намеренно не гонял живьём (общий чекаут с
  параллельными агентами), только код-ревью — оба корректно `POST`-only и
  `adminOnly`, но `app.log`/весь кэш-компонент общие на все параллельные
  сессии.
- **6 живых фиксов применено** (не закоммичено, как договорено):
  `SearchController::actionGlobal` (missing `Url` import → 500),
  `TrackingController::actionPublic` (×2: колонка + вьюха),
  `PdfController::actionPublic`+`generateHtmlInvoice` (×2: колонка + вьюха),
  `DashboardController::actionLogout` (VerbFilter hardening).
- **2 открытых архитектурных вопроса** для совета (не чинил — требуют
  продуктового решения): дублирующиеся источники истины для курса CNY
  (`DashboardController`/`CurrencyService` vs `ExchangeRateController`); и
  недостижимость `actionPublic` (Tracking + Pdf) для реальных клиентов из-за
  admin-only access control — удалить мёртвый дубликат или дать гостевой
  доступ.
- Тестовые данные полностью откачены и подтверждены `SELECT`: `order_status`
  — 21 строка, `is_active` для `return` вернули к `1`; `company_settings`
  восстановлена к исходным значениям; тестовый пользователь `cmp470d_test`
  (id=6) удалён, `user` вернулась к исходным 3 строкам; тестовый файл
  `exports/chat_dataset_2026-09-25.csv` удалён.
