# CMP-470-Б — Сплошной прогон внешних интеграций: МойСклад / Poizon / AmoCRM / Telegram / Plugin

Продолжение методологии CMP-456/460/463/467: живой HTTP через реальную
admin-сессию (`admin/admin123`, cookies + CSRF из `X-CSRF-Token`/`_csrf`, ровно
в том формате, в котором его берёт реальный JS страницы) со сверкой MySQL
(`cmp410_e2e_clean`) до/после — вердикт по факту в БД, а не по HTTP-коду.
Payload каждого действия сверен с реальным вызывающим кодом
(`backend/modules/admin/views/plugin/*.php`, `frontend/web/js/admin-poizon.js`),
а не придуман по сигнатуре контроллера.

**Живые вызовы к боевым внешним API (МойСклад, Poizon, AmoCRM, Telegram) не
делались** — по прямому указанию карточки. Вместо этого для каждого такого
действия: (а) подтверждён admin-only gate живьём (302/403 без cookies);
(б) подтверждена ветка «креды не настроены» (early return до похода во
внешний API — либо через явную проверку, либо потому что низкоуровневый
HTTP-клиент сам бросает исключение до `curl_exec`); (в) статически прочитан
код похода во внешний API (try/catch, обработка `curl_exec()===false`/4xx/5xx).
Локальные действия (сохранение настройки/токена/маппинга в БД) прогнаны
живым циклом с MySQL-диффом как обычно.

Актуальность кредов проверена в `.env` и в БД перед стартом: `AMOCRM_LONG_TOKEN`,
`AMOCRM_API_DOMAIN`, `AMOCRM_INTEGRATION_ID` в `.env` не заданы вовсе (не
«отдают 401», а физически отсутствуют — другое окружение, чем то, где раньше
фиксировался 401 на `AMOCRM_LONG_TOKEN`, см. `project_amocrm_token_state.md`);
`app_setting` для `amocrm` содержит только `pipeline_id`/`status_id_paid`, ни
`domain`, ни `access_token` не заданы; `moysklad`/`telegram`/`poizon` кредов
нет ни в `.env`, ни в БД вовсе.

## Границы охвата

- `backend/modules/admin/controllers/MoyskladController.php` — 21 экшен
  (index, save-status-mapping, save-settings, sync-log, test-connection,
  save-credentials, save-mapping, push-all, periodic-sync, webhook-status,
  pull, push-order, sync-info, webhooks, register-webhook, delete-webhook,
  get-db-columns, save-moysklad-mapping, webhook, ms-images, ms-image).
- `backend/modules/admin/controllers/PoizonController.php` — 6 экшенов
  (index, run, view, view-log, delete, errors).
- `backend/modules/admin/controllers/AmoCrmController.php` — 3 экшена
  (create-deal, update-status, settings).
- `backend/modules/admin/controllers/TelegramBotController.php` — 3 экшена
  (webhook, notify-status, set-webhook).
- `backend/modules/admin/controllers/PluginController.php` — 37 экшенов,
  из них **10 не входят в объём (закрыто CMP-437, коммит `e1f2a72`, не
  перепроверялись)**: `actionCdek`, `actionSaveCdek`, `actionEuropochta`,
  `actionSaveEuropochta`, `actionBelpochta`, `actionSaveBelpochta`,
  `actionTestTracking`, `actionRocketsms`, `actionSaveRocketsms`,
  `actionTestRocketsms`. Остальные **27 проверены полностью** (index, toggle,
  settings, moysklad[редирект], amocrm, amocrm-authorize, amocrm-callback,
  amocrm-save, amocrm-test, amocrm-sync, amocrm-logs, amocrm-stats,
  amocrm-fields, amocrm-fields-save, amocrm-fields-delete, amocrm-pipelines,
  amocrm-status-map-save, amocrm-widget, amocrm-widget-key, telegram,
  currency, dobropost, lamoda, lamoda-parser, lamoda-run, lamoda-status,
  lamoda-save-schedule) **+ 2 экшена, которых физически не существовало и
  которые пришлось дописать** (`actionSaveProxyPhones`, `actionSaveStatusMapping`
  — см. дефект №4).

Не входит в проверку: сама бизнес-логика внешних клиентов
(`MoySkladService`, `AmocrmClient`, `DobroPostService`, `PluginManager`'овские
демо-плагины) проверена статически (как код похода во внешний API), но не
её полное покрытие тестами — это отдельная задача, не «сплошной прогон
мутирующих admin-экшенов».

## Реестр

| Действие | Маршрут | HTTP (без сессии / с сессией) | Факт | Вердикт |
|---|---|---|---|---|
| **MoyskladController** | | | | |
| `actionIndex` | `GET /admin/moysklad/index` | 302 / 200 | рендер без ошибок | ok |
| `actionSaveStatusMapping` | `POST /admin/moysklad/save-status-mapping` | 302 / 200 | `moysklad.status_map_*` + `status_mapping` записаны кириллицей верно | ok |
| `actionSaveSettings` | `POST /admin/moysklad/save-settings` | 302 / 200 | 6 sync-флагов + `auto_sync_interval` записаны | ok |
| `actionSyncLog` | `GET /admin/moysklad/sync-log` | 302 / 200 | отдаёт реальный лог | ok |
| `actionTestConnection` | `POST /admin/moysklad/test-connection` | 302 / 200 | без кредов — `RuntimeException` до `curl_exec`, пойман, `success:false` | ok (код+gate, без кредов) |
| `actionSaveCredentials` | `POST /admin/moysklad/save-credentials` | 302 / 200 | `api_key`/`login`+`password`/`_directions_only` — все 3 ветки верны; пустой payload корректно отбит | ok |
| `actionSaveMapping` | `POST /admin/moysklad/save-mapping` | 302 / 200 | `status_map_*` записан | ok |
| `actionPushAll` | `POST /admin/moysklad/push-all` | 302 / 200 | без кредов: `pushed:0,errors:4,total:4` — счётчик точный, каждый заказ поймал исключение индивидуально | ok (без кредов) |
| `actionPeriodicSync` | `POST /admin/moysklad/periodic-sync` | 302 / 200 | без кредов — `success:false` до похода в API | ok (без кредов) |
| `actionWebhookStatus` | `GET /admin/moysklad/webhook-status` | 302 / 200 | без кредов — `success:false,status:unknown` | ok (без кредов) |
| `actionPull` | `POST /admin/moysklad/pull` | 302 / 200 | без кредов — `success:false` до похода в API | ok (без кредов) |
| `actionPushOrder` | `POST /admin/moysklad/push-order` | 302 / 200 | `id` отсутствует/не найден → `Заказ не найден`; без кредов на реальном заказе — `success:false` до похода в API | ok (без кредов) |
| `actionSyncInfo` | `GET /admin/moysklad/sync-info` | 302 / 200 | без кредов — `success:false` | ok (без кредов) |
| `actionWebhooks` | `GET /admin/moysklad/webhooks` | 302 / 200 | без кредов — `success:false` | ok (без кредов) |
| `actionRegisterWebhook` | `POST /admin/moysklad/register-webhook` | 302 / 200 | без кредов — `success:false` | ok (без кредов) |
| `actionDeleteWebhook` | `POST /admin/moysklad/delete-webhook` | 302 / 200 | без `id` — `success:false` до похода в API | ok |
| `actionGetDbColumns` | `GET /admin/moysklad/get-db-columns?table=` | 302 / 200 | `orders/products/customers` — верные колонки; недопустимая таблица отбита | ok |
| `actionSaveMoyskladMapping` | `POST /admin/moysklad/save-moysklad-mapping` | 302 / 200 | `mapping_orders` записан; недопустимый `entity` отбит | ok |
| `actionWebhook` | `POST /admin/moysklad/webhook` | **до фикса: 302 (гость) → login, 400 (сессия, без CSRF)**; после фикса: 200/200 | инбаунд-приёмник от самого МойСклад никогда не мог сработать | **wrong_access → исправлено (критично)** |
| `actionMsImages` | `GET /admin/moysklad/ms-images?product_id=` | 302 / 200 | нет данных → `[]`, без ошибок | ok |
| `actionMsImage` | `GET /admin/moysklad/ms-image?url=` | 302 / 200 | неверный префикс URL → 400 (SSRF-защита работает); без кредов, верный префикс → 503 до `curl_exec` | ok |
| **PoizonController** | | | | |
| `actionIndex` | `GET /admin/poizon/index` | 302 / 200 | рендер, статистика по 2 реальным батчам | ok |
| `actionRun` (GET) | `GET /admin/poizon/run` | 302 / 200 | рендер формы | ok |
| `actionRun` (POST) | `POST /admin/poizon/run` | 302 / — | код проверен: `exec()` фонового консольного импорта из произвольного JSON URL — реальный запуск не делался (внешний API, исключён по карточке) | код проверен, живой запуск не делался |
| `actionView` | `GET /admin/poizon/view?id=` | 302 / 200 (404 для несуществующего id) | рендер батча + логов | ok |
| `actionViewLog` | `GET /admin/poizon/view-log` | 302 / 200 | список + содержимое лог-файлов | ok |
| `actionDelete` | `POST /admin/poizon/delete?id=` | 302 (гость) / 405 (GET) / 400 (без CSRF) / 302 (с CSRF) | тестовый батч создан и удалён, `import_batch` вернулась к исходным 2 строкам | ok |
| `actionErrors` | `GET /admin/poizon/errors` | 302 / 200 | рендер без ошибок | ok |
| **AmoCrmController** | | | | |
| `actionCreateDeal` | `POST /admin/amo-crm/create-deal?orderId=` | 302 (гость) / 405 (GET) / 200 | без кредов — `AmoCRM не настроена` до похода в API; несуществующий `orderId` — `Заказ не найден` | ok (без кредов) |
| `actionUpdateStatus` | `POST /admin/amo-crm/update-status?orderId=` | **до фикса: 302 (гость) / 405 (GET) / 200 → 500 Ошибка сервера ВСЕГДА**; после фикса: 200 JSON | до фикса: 100% отказ независимо от кредов (response format не выставлялся); после фикса — корректный JSON, без кредов не доходит до `curl_exec` | **wrong_data → исправлено (критично)** |
| `actionSettings` | `GET /admin/amo-crm/settings` | 302 (гость) / 302 (админ, редирект на `/admin/plugin/amocrm`) | не мутирует, дубль-редирект как задокументировано в самом коде | ok |
| **TelegramBotController** | | | | |
| `actionWebhook` | `POST /admin/telegram-bot/webhook` | **до фикса: 302 (гость, куда бы Telegram ни слал) → login**; после фикса: 200 (гость) | реальные апдейты от Telegram никогда не доходили до `handleMessage()`/`handleCallback()` | **wrong_access → исправлено (критично)** |
| `actionNotifyStatus` | `POST /admin/telegram-bot/notify-status` | **до фикса: доступен по голому GET с cookies, без CSRF (CSRF был выключен на весь контроллер)**; после фикса: 405 на GET, 400 без CSRF, 200 с CSRF | без `bot_token` — тихий no-op; вызывающего кода в реальном флоу заказа нет (см. «Найдено, не исправлено», совпадает с CMP-422) | **wrong_access → исправлено** |
| `actionSetWebhook` | `POST /admin/telegram-bot/set-webhook` | то же, что выше | без `bot_token` — `success:false` до похода в API; кнопки в реальном UI нет вообще (`telegram.php` её не вызывает) | **wrong_access → исправлено** |
| **PluginController** | | | | |
| `actionIndex` | `GET /admin/plugin` | 302 / 200 | рендер списка плагинов | ok |
| `actionToggle` | `POST /admin/plugin/toggle` | 302 (гость) / **405 (GET) после фикса** / 200 | активация/деактивация `stripe` через FileCache персистентна между запросами, откачено | ok (+ verb-фикс) |
| `actionSettings` | `GET /admin/plugin/settings?id=` | 302 (гость) / **до фикса: 500 (view не существовал)**; после фикса: 200 для всех 4 плагинов (stripe/yookassa/belpost/livedune) | reachable из реальной кнопки «Настройки» на активном payment-плагине; сохранение произвольного key/value подтверждено live | **wrong_data → исправлено (критично)** |
| `actionMoysklad` | `GET /admin/plugin/moysklad` | — | мёртвый код: urlManager (`infrastructure/config/web.php:558`) перехватывает этот маршрут раньше и отдаёт `admin/moysklad/index` — экшен физически недостижим | не баг (мёртвый код, не трогал) |
| `actionAmocrm` | `GET /admin/plugin/amocrm[?tab=logs]` | 302 / 200 | рендер обеих вкладок | ok |
| `actionAmocrmAuthorize` | `GET /admin/plugin/amocrm/authorize` | 302 / 302+flash | без `client_id`/`domain` — редирект на свою же страницу с ошибкой, никакого похода на `amocrm.ru` | ok (без кредов) |
| `actionAmocrmCallback` | `GET /admin/plugin/amocrm/callback` | 302 / 302+flash | без `code` — ранний return, никакого похода во внешний API | ok |
| `actionAmocrmSave` | `POST /admin/plugin/amocrm-save` | 302 (гость) / 200 | `domain`/`pipeline_id`/`auto_create_lead` записаны в `app_setting`, откачено | ok |
| `actionAmocrmTest` | `POST /admin/plugin/amocrm-test` | 302 (гость) / **405 (GET) после фикса** / 200 | без кредов — `isConfigured()===false`, ранний return | ok (без кредов, + verb-фикс) |
| `actionAmocrmSync` | `POST /admin/plugin/amocrm-sync` | 302 (гость) / **405 (GET) после фикса** / 200 | без кредов — ранний return; **до фикса: GET с теми же cookies проходил (`post($k,$default)` возвращает default и на GET) — реальный bulk-sync до 50 заказов был GET-триггерящимся** | **wrong_access → исправлено (см. дефект №5)** |
| `actionAmocrmLogs` | `GET /admin/plugin/amocrm/logs` | 302 / 200 | 260 реальных строк лога | ok |
| `actionAmocrmStats` | `GET /admin/plugin/amocrm/stats` | 302 / 200 | агрегаты по логу верны | ok |
| `actionAmocrmFields` | `GET /admin/plugin/amocrm/fields` | 302 / 200 | без кредов — `AmocrmClient::request()` возвращает `null` до `curl_exec` (проверка `!$this->domain`), `fields:[]` | ok (без кредов) |
| `actionAmocrmFieldsSave` | `POST /admin/plugin/amocrm/fields-save` | 302 (гость) / 200 | строка записана в `amocrm_field_mapping`, откачено | ok |
| `actionAmocrmFieldsDelete` | `POST /admin/plugin/amocrm/fields-delete` | 302 (гость) / 200 | строка удалена | ok |
| `actionAmocrmPipelines` | `POST /admin/plugin/amocrm/pipelines` | 302 (гость) / **405 (GET) после фикса** / 200 | без кредов — `getPipelinesWithStatuses()===null` до `curl_exec` | ok (без кредов, + verb-фикс) |
| `actionAmocrmStatusMapSave` | `POST /admin/plugin/amocrm/status-map-save` | 302 (гость) / **405 (GET) после фикса** / 200 | пустой массив отбит; неверный `track` отбит без мутации; **валидный `order`-трек до фикса стирал 2 реальные строки `dm`-трека (Instagram DM-бот) — full-table wipe**; после фикса DELETE скопирован по трекам из payload | **wrong_data → исправлено (критично, см. дефект №2)** |
| `actionAmocrmWidget` | `GET /admin/plugin/amocrm-widget` | 302 / 200 | рендер | ok |
| `actionAmocrmWidgetKey` | `POST /admin/plugin/amocrm-widget/key` | 302 (гость) / **405 (GET) после фикса** / 200 | `widget_api_key` сгенерирован и записан, откачено; неверный `action` отбит | ok (+ verb-фикс) |
| `actionTelegram` | `GET /admin/plugin/telegram` | 302 / 200 | рендер (мутирующих кнопок на этой странице нет — все через `/admin/settings/*`) | ok |
| `actionCurrency` | `GET /admin/plugin/currency` | 302 / 200 | рендер (мутации через `/admin/exchange-rate/*` и `/admin/settings/save` — не в объёме) | ok |
| `actionDobropost` | `GET /admin/plugin/dobropost` | 302 / **до фикса: 200 с пустой таблицей маппинга и пустым списком телефонов**; после фикса: 200 с 40 реальными строками маппинга | не передавал `$statusMappings`/`$proxyPhones` в вид, хотя реальные данные существуют (`DeliveryProvider` code=dobropost, 40 строк) | **wrong_data → исправлено (см. дефект №4)** |
| `actionSaveProxyPhones` | `POST /admin/plugin/save-proxy-phones` | — | **до фикса: экшена не существовало вообще, кнопка «Сохранить» на странице ДП 404'ила**; после фикса: 302 (гость) / 405 (GET) / 200, `dobropost.proxy_phones` записан и виден потребителем `DobroPostService::getRandomPhone()`, откачено | **missing_action → реализовано (см. дефект №4)** |
| `actionSaveStatusMapping` (Plugin) | `POST /admin/plugin/save-status-mapping` | — | **до фикса: экшена не существовало вообще, кнопка «Сохранить маппинг» 404'ила**; после фикса: 302 (гость) / 405 (GET) / 200, обновление `delivery_status_mapping` по `id`, скоуп по `provider_id` подтверждён (чужой `id` не трогается), откачено | **missing_action → реализовано (см. дефект №4)** |
| `actionLamoda` | `GET /admin/plugin/lamoda` | 302 / 200 | рендер | ok |
| `actionLamodaParser` | `GET /admin/plugin/lamoda-parser` | 302 / 200 | рендер с реальными последними результатами | ok |
| `actionLamodaRun` | `POST /admin/plugin/lamoda-parser/run` | 302 (гость) / **до фикса: 200 и на голом GET с cookies, без CSRF**; после фикса: 405 (GET) / 400 (без CSRF) / 200 (с CSRF) | POST с локальным dummy-URL — `last_url`/`parse_status`/`parse_started_at` записаны, фоновый процесс отработал и корректно залогировал сетевую ошибку; всё откачено | **wrong_access → исправлено (критично, см. дефект №3)** |
| `actionLamodaStatus` | `GET /admin/plugin/lamoda-parser/status` | 302 / 200 | статус + лог фонового процесса | ok |
| `actionLamodaSaveSchedule` | `POST /admin/plugin/lamoda-parser/schedule` | 302 (гость) / **405 (GET) после фикса** / 200 | `lamoda.schedule=weekly` записан, откачено | ok (+ verb-фикс) |

## Найденные дефекты и фиксы

### 1. `MoyskladController::actionWebhook` и `TelegramBotController::actionWebhook` — инбаунд-вебхуки МойСклад и Telegram никогда не могли сработать

Оба контроллера наследуют `BaseAdminController::behaviors()`, чей единственный
`AccessControl`-правило — `roles => ['@']` (только залогиненные) — применяется
**ко всем экшенам без исключения**, включая `actionWebhook`. Но
`actionWebhook` — это инбаунд-приёмник, вызываемый серверами самого МойСклад
(URL регистрируется через `actionRegisterWebhook`) и Telegram (URL
регистрируется через `actionSetWebhook`) — их запросы физически не могут
нести admin-сессионную cookie.

Живым прогоном подтверждено: `POST /admin/telegram-bot/webhook` и
`POST /admin/moysklad/webhook` без cookies → **302 → `/admin/login`**, не 200.
То есть реальные апдейты от Telegram (команды `/start`/`/status`/`/support`)
и реальные UPDATE/DELETE-события от МойСклад никогда не доходили до
`handleMessage()`/`handleCallback()`/обработки статуса — оба
инбаунд-интеграционных канала были на 100% неработоспособны с момента
написания, независимо от того, настроены ли credentials.

Для `MoyskladController::actionWebhook` нашёлся и **второй, компаундирующий**
баг: строка `$this->enableCsrfValidation = false;` стояла **внутри тела
метода** — но CSRF проверяется в `yii\web\Controller::beforeAction()`, которая
выполняется до того, как тело экшена вообще начинает работать. Живым прогоном
подтверждено: с валидной admin-сессией, но без CSRF-токена — `400 Bad Request`
(«Некорректный запрос»), то есть даже пройдя гипотетический access-фикс,
запрос всё равно упал бы на CSRF-проверке. Строка была мёртвым кодом.

Установленный в этой же кодовой базе прецедент для настоящих инбаунд-вебхуков
— отдельный `api\controllers\WebhookController extends yii\web\Controller`
(без `AccessControl` вообще, с shared-secret проверкой для AmoCRM) — то есть
AmoCRM-вебхук (`POST /api/webhook/amocrm`) устроен правильно, а
МойСклад/Telegram — нет.

**Фикс:**
- `MoyskladController::behaviors()` — добавлено явное `allow`-правило
  `actions => ['webhook'], roles => ['?', '@']` перед унаследованными
  правилами; добавлен `beforeAction()`, отключающий CSRF для `webhook` **до**
  вызова `parent::beforeAction()`; мёртвая строка внутри `actionWebhook()`
  удалена.
- `TelegramBotController::behaviors()` (не существовал — добавлен) — то же
  самое allow-правило для `webhook`.

Подтверждено повторным живым прогоном: гостевой `POST` (без cookies, без
CSRF) на оба маршрута теперь возвращает `200` и реально доходит до логики
обработки (`{"success":true,"processed":0}` для МойСклад,
`OK` + запись в лог для Telegram `/start`).

### 2. `PluginController::actionAmocrmStatusMapSave` → `AmocrmStatusMapper::saveMappings()` — сохранение маппинга статусов по одному треку стирало ВСЕ остальные треки, включая треки без интерфейса в UI

`saveMappings()` делал `DELETE FROM amocrm_status_mapping` **без всякого
условия**, затем вставлял только переданные строки. Страница
`/admin/plugin/amocrm` (вкладка «Статусы») знает только про треки
`order`/`payment`/`logistics`/`delivery` (`$trackLabels` в
`views/plugin/amocrm.php`) и при каждом «Сохранить» отправляет **все** видимые
на странице строки разом — то есть нормальный, штатный клик по «Сохранить»
безусловно уничтожал бы любые строки с другими значениями `our_track`.

Живым прогоном подтверждено: в БД реально существовали 2 строки с
`our_track = 'dm'` (маппинг Instagram DM-бота → AmoCRM, `pipeline_id=4453963`,
statuses `41258857`/`85588210`) — единственный трек, для которого в
`AmocrmStatusMapper::ourStatusList()`/UI вообще нет представления. Один валидный
POST с одной строкой трека `order` (`{"success":true,"count":1}`) полностью
уничтожил обе строки `dm` без единого предупреждения. Данные восстановлены
из зафиксированных до теста значений.

**Фикс** (`backend/modules/admin/services/AmocrmStatusMapper.php`): `DELETE`
теперь скопирован условием `WHERE our_track IN (...)`, где список — только
треки, реально присутствующие в текущем payload. Подтверждено повторным живым
прогоном: сохранение строки трека `order` больше не трогает строки трека `dm`
(id/значения `dm.started`/`dm.handed_off` остались нетронуты), при этом
поведение «замены» внутри самого редактируемого трека сохранено.

### 3. `PluginController::actionLamodaRun` и `actionAmocrmSync` — GET с cookies без CSRF триггерил реальные внешние мутации (тот же класс, что CMP-418)

`PluginController` — единственный из проверенных в этой волне контроллеров,
у которого **не было вообще никакого `behaviors()`-переопределения** (ни
verb-ограничений, ни явных access-правил), хотя внутри — десятки мутирующих
JSON-экшенов. Два из них не защищены даже собственной проверкой `isPost`:

- `actionLamodaRun`: `Yii::$app->request->post('url', '')` на GET-запросе
  возвращает `''` **независимо от метода** — код просто откатывается на
  сохранённый `lamoda.last_url` и всё равно спавнит фоновый `exec()` парсера.
  Живым прогоном подтверждено: `GET /admin/plugin/lamoda-parser/run` с
  admin-cookie, **без единого CSRF-токена** (именно то, что уйдёт из
  `<img src=...>`/голой ссылки под `SameSite=Lax`) → `200 OK`,
  `{"success":true,"message":"Парсинг запущен в фоне"}`, реально
  переиспользованный `last_url` ушёл в фоновый процесс.
- `actionAmocrmSync`: `Yii::$app->request->post($name, $default)` возвращает
  `$default` на GET так же, как и отсутствующий ключ на POST — то есть GET
  доходит до той же ветки массовой синхронизации (до 50 заказов) с AmoCRM,
  что и настоящий POST. Без кредов сейчас безопасно (`isConfigured()===false`
  раньше), но при сконфигурированном AmoCRM это была бы реальная,
  GET-триггерящаяся запись во внешнюю CRM.

Ровно тот же класс бага уже находили и фиксили в этой цепочке для
`MoyskladController`/`AmoCrmController` (см. их собственные комментарии
«CMP-418» в коде).

**Фикс**: `PluginController::behaviors()` (добавлен) — POST-only для
`toggle`, `amocrm-save`, `amocrm-test`, `amocrm-sync`, `amocrm-fields-save`,
`amocrm-fields-delete`, `amocrm-pipelines`, `amocrm-status-map-save`,
`amocrm-widget-key`, `lamoda-run`, `lamoda-save-schedule`,
`save-proxy-phones`, `save-status-mapping`. Все соответствующие реальные
JS-вызовы (`views/plugin/amocrm.php`, `views/plugin/lamoda-parser.php`,
`admin-settings.js::togglePlugin()`) уже используют `method:'POST'` — фикс не
меняет поведение ни для одного легитимного кейса, подтверждено повторным
прогоном каждого из перечисленных экшенов через POST.

### 4. `PluginController::actionDobropost`, `actionSaveProxyPhones`, `actionSaveStatusMapping` (Plugin-scope) — вся страница настроек DobroPost была наполовину мёртвой

Три независимых, но связанных дефекта на одной странице
(`/admin/plugin/dobropost`):

1. **`actionSaveProxyPhones` и `actionSaveStatusMapping` не существовали
   вообще.** Инлайн-скрипт `views/plugin/dobropost.php` (функции
   `saveProxyPhones()` и `saveStatusMapping()`) шлёт реальные `POST` на
   `/admin/plugin/save-proxy-phones` и `/admin/plugin/save-status-mapping` —
   живым прогоном подтверждено: оба маршрута отдавали **404** для любого
   запроса. Справочник «прокси-телефонов» (используется
   `DobroPostService::getRandomPhone()` для подстановки НЕ реального телефона
   клиента в посылку — часть PII-защиты из CMP-359) вообще не имел рабочего
   способа заполнения через админку. Маппинг статусов DobroPost → внутренний
   статус заказа (таблица `delivery_status_mapping`, 40 реальных строк,
   `provider_id` для `dobropost`) не имел рабочего способа редактирования,
   хотя точно такой же механизм для CDEK/Европочты/Белпочты уже работает в
   этом же контроллере.
2. **`actionDobropost()` не передавал данные в вид вообще**
   (`return $this->render('dobropost');` без второго аргумента) — вид
   защищался `$statusMappings = $statusMappings ?? [];` /
   `$proxyPhones = $proxyPhones ?? [];`, поэтому не падал, но таблица
   маппинга и список телефонов **всегда** рендерились пустыми, даже если бы
   первые два экшена существовали и что-то сохранили.

**Фикс**: `actionDobropost()` переписан по образцу уже существующих
`actionCdek`/`actionEuropochta`/`actionBelpochta` в этом же файле — грузит
`DeliveryProvider::findOne(['code'=>'dobropost'])->statusMappings` и
`settings('dobropost','proxy_phones')`. Реализованы `actionSaveProxyPhones()`
(валидация массива `{phone,label}`, запись в
`settings('dobropost','proxy_phones')`) и `actionSaveStatusMapping()`
(обновление `internal_status`/`estimated_days`/`is_final` по `id`, **со
скоупом по `provider_id`** — живым прогоном подтверждено, что `id` строки
другого провайдера не редактируется этим экшеном). Оба — с
`requirePermission('manageSettings')`, POST-only.

Подтверждено живым прогоном: `GET /admin/plugin/dobropost` теперь рендерит
все 40 реальных строк маппинга; сохранение телефона `+375440001122` реально
появляется в `settings('dobropost','proxy_phones')` и отражается при
повторном `GET`; сохранение `internal_status` для строки `id=1` меняет её в
БД; попытка отредактировать строку `id=41` (принадлежит Европочте) через
dobropost-эндпоинт корректно игнорируется (`updated:0`, строка не тронута).
Все тестовые изменения откачены.

`actionImportDobropost` (кнопка «Импортировать шипменты», реально бьёт по
живому DobroPost API через `DobroPostService`) **не реализовывался** — это
не «мелкий недостающий глю-код» как два экшена выше, а полноценная бизнес-логика
(какие посылки импортировать, что считать конфликтом и т.д.), реализация
вслепую внутри аудита рискует внести другой класс бага; вынесено в «Найдено,
не исправлено».

### 5. `AmoCrmController::actionUpdateStatus` — экшен гарантированно 500'ил при любом вызове, независимо от кредов

`Yii::$app->response->format` никогда не выставлялся в `Response::FORMAT_JSON`
(в отличие от соседнего `actionCreateDeal` в том же файле) — метод в любом
случае `return`-ил PHP-массив, а дефолтный HTML-форматтер Yii не умеет
рендерить массив как контент. Живым прогоном подтверждено:
`POST /admin/amo-crm/update-status?orderId=5` (сделка не найдена, самая
безобидная ветка, до какого-либо кода похода в AmoCRM) → **`500 Internal
Server Error`**, страница «Ошибка сервера», в логе —
`yii\base\ErrorHandler` поймал попытку рендера массива.

Дополнительно (уже после фикса формата): не было ни проверки
`empty($accessToken)` перед `curl_init()` (в отличие от `actionCreateDeal`),
ни обработки `curl_exec()===false`/4xx-5xx — `curl_exec()===false` и HTTP-ошибка
одинаково превращались в `['success'=>true,'response'=>null]`
(тот же класс «200 + success:true, но по факту ничего не произошло», что уже
несколько раз находили в этой цепочке).

**Фикс**: добавлен `Yii::$app->response->format = Response::FORMAT_JSON;` в
начале метода; добавлена проверка `empty($accessToken)` с ранним возвратом
(как в `actionCreateDeal`); весь `curl`-блок обёрнут в `try/catch`, добавлена
проверка `$response === false` (сетевая ошибка) и `$httpCode >= 400`.
Подтверждено живым прогоном: `orderId=5` без `amocrm_deal_id` → `200 JSON
{"success":false,"message":"Сделка не найдена"}`; тот же заказ с временно
проставленным `amocrm_deal_id` и без кредов → `200 JSON
{"success":false,"message":"AmoCRM не настроена"}`, без единого похода в
`curl_exec` (проверено кодом — ранний return срабатывает до `curl_init()`).
`order.amocrm_deal_id` возвращён в `NULL` после теста.

### 6. `frontend/web/js/admin-poizon.js` — `bulkAssignLogist()` бросал бы `TypeError` в браузере

Побочная находка из CMP-467: `UserController::actionLogists` (закреплённый
формат ответа `{"success":true,"logists":[...]}`) вызывается здесь через
`fetch('/admin/user/logists').then(r=>r.json()).then(data => data.forEach(...))`
— `data` это объект, а не массив, `data.forEach` бросил бы
`TypeError: data.forEach is not a function` в консоли браузера, и выпадающий
список логистов в модалке массового назначения не заполнился бы никогда.

**Фикс**: `data.forEach(...)` → `(data.logists || []).forEach(...)`.

**Не протестировано открытием модалки в браузере**: живьём проверено, что
`UserController::actionLogists` действительно отдаёт `{"success":true,"logists":[...]}`
(см. реестр `MoyskladController`/предыдущий прогон CMP-467), и что новый код
`(data.logists || []).forEach(...)` синтаксически и логически корректен для
этой формы ответа. Саму функцию `bulkAssignLogist()` в живом UI открыть не
удалось: единственный вызывающий код — `backend/modules/admin/views/poizon/order/index.php`,
но это отдельное дерево видов (`views/poizon/order|tariff|user|product|customer|...`),
не связанное с проверяемым в этой карточке `PoizonController` (импорт
Poizon/Dewu) — похоже на осиротевший альтернативный admin-скин; в
`infrastructure/config/web.php` нет ни одного модуля/маршрута с id `poizon`,
который бы на него указывал. `frontend/web/js/admin-poizon.js` подключается
глобально через `AdminAsset`, поэтому фикс корректен и на будущее (если это
дерево когда-нибудь будет подключено или появится другой вызывающий код), но
живой Playwright-клик по кнопке сейчас невозможен — страницы, открывающей
эту модалку, не существует в маршрутах.

## Найдено, не исправлено

- **`actionImportDobropost` не реализован** (см. дефект №4) — кнопка
  «Импортировать шипменты» на `/admin/plugin/dobropost` по-прежнему 404'ит.
  Реализация требует решения о семантике импорта (какие посылки, как
  разрешать конфликты статусов) и живого похода в DobroPost API — вне
  безопасного объёма этого аудита.
- **`AmoCrmController` и `TelegramBotController` не имеют вообще никакой
  ролевой дифференциации** (`$adminOnly` не выставлен ни в одном из двух) —
  любой залогиненный сотрудник, включая `logist`, может создавать сделки в
  AmoCRM, ротировать Widget API Key, менять статусы AmoCRM-сделок, слать
  сообщения клиентам в Telegram и т.д. `TelegramBotController` уже входит в
  каталогизированный CMP-465 список 17 контроллеров без ролевой
  дифференциации (решение отложено на совет) — фактически подтверждено этим
  прогоном. **`AmoCrmController` в тот список не попал** (пропуск в CMP-465)
  — стоит добавить.
- **`PluginController` применяет `requirePermission('manageSettings')`
  непоследовательно**: `actionSaveCdek/SaveEuropochta/SaveBelpochta/SaveRocketsms`,
  `actionAmocrmSave/AmocrmPipelines/AmocrmStatusMapSave/AmocrmWidgetKey` и обе
  новые `actionSaveProxyPhones/actionSaveStatusMapping` — защищены; но
  `actionToggle`, `actionAmocrmTest`, `actionAmocrmSync`,
  `actionAmocrmFieldsSave/FieldsDelete`, `actionSettings` (generic
  plugin-settings) — не защищены вообще, хотя равно или более чувствительны
  (`actionAmocrmSync` пишет `order.amocrm_lead_id` пачками). CMP-465 отметил
  `PluginController` как «уже использует RBAC» без замечания об этой
  неполноте — стоит уточнить объём охвата RBAC в этом контроллере отдельной
  карточкой, не делал сам (тот же класс решения, что уже отложен советом для
  17 контроллеров).
- **`PluginController::actionMoysklad` — мёртвый код**, перехвачен
  `urlManager`-правилом (`admin/plugin/moysklad` → `admin/moysklad/index`,
  `infrastructure/config/web.php:558`) раньше, чем маршрутизация вообще
  дошла бы до `PluginController`. Не трогал — доказано, что реального эффекта
  нет, удаление/оставление — вопрос гигиены кода, не бага.
- **`actionPushAll` (МойСклад) обновляет `last_sync_at` даже при 0 успешных
  пушах** (`pushed:0, errors:4` из живого теста) — UI честно показывает
  реальные счётчики ошибок, но метка времени синхронизации формально не
  отличает «синхронизация прошла успешно» от «синхронизация полностью
  провалилась». Низкий приоритет — эффект чисто косметический (нет потери
  данных), не фиксил.
- **Мёртвая функция `TelegramBotController::actionNotifyStatus`** — не вызывается
  нигде в реальном флоу смены статуса заказа; совпадает с уже
  задокументированным в CMP-422 выводом «AmoCRM/Telegram/DobroPost/stock/
  loyalty никогда не подключены к живому чекауту» — не новая находка, только
  подтверждение.
- **`scripts/parse_lamoda.php` считает `skipped` в минус** (наблюдалось живым
  тестом: `"skipped":-10,"errors":10` при 10 неудачных страницах) — вне
  объёма этой карточки (файл не входит в проверяемые контроллеры), но
  зафиксировано на будущее.

## Честная граница охвата

- `actionTestConnection`/`actionPushAll`/`actionPeriodicSync`/`actionPull`/
  `actionPushOrder`/`actionSyncInfo`/`actionWebhooks`/`actionRegisterWebhook`/
  `actionDeleteWebhook` (МойСклад) — код проверен полностью
  (`MoySkladService::getAuth()` бросает `RuntimeException` до любого
  `curl_exec`, если ни `api_key`, ни `login`+`password` не заданы — что и
  подтверждено во всех тестах); реальный вызов к api.moysklad.ru не делался.
- `actionCreateDeal`/`actionUpdateStatus` (AmoCRM), `actionAmocrmTest`/
  `actionAmocrmSync`/`actionAmocrmFields`/`actionAmocrmPipelines` (Plugin) —
  код проверен (`AmocrmClient::request()`/`isConfigured()` возвращают
  `null`/`false` до `curl_exec`, если `domain`/`access_token` не заданы);
  реальный вызов к amocrm.ru не делался.
- `actionNotifyStatus`/`actionSetWebhook` (Telegram) — код проверен
  (`sendMessage()` возвращает `false` до `curl_exec`, если `bot_token` не
  задан); реальное сообщение в Telegram не отправлялось.
- `actionRun` (Poizon, с `import_url`) — код проверен (консольная команда
  `poizon-import-json/run`, реальный HTTP-запрос к произвольному JSON-URL);
  реальный запуск импорта не делался — явно исключённый внешний API по
  карточке.
- `actionLamodaRun` — единственное отступление от «не хитить внешние URL» в
  этой волне сделано намеренно безопасно: тестировался с локальным
  недоступным адресом (`http://127.0.0.1:1/...`), а не с `lamoda.by`, чтобы
  подтвердить механику (фоновый `exec()`, запись `last_url`/`parse_status`,
  обработку сетевой ошибки в самом парсере) без единого реального обращения
  к стороннему сайту.
- `actionImportDobropost` — код (`DobroPostService`) прочитан, кредов в
  `.env`/БД нет (`DP_API_EMAIL`/`DP_API_PASSWORD` пусты); экшен не
  реализован (см. «Найдено, не исправлено»), поэтому живой тест невозможен в
  принципе.

## Итого волны

- **61 экшен проверено** (21 МойСклад + 6 Poizon + 3 AmoCRM + 3 Telegram +
  28 Plugin, включая 2 экшена, которых не существовало и которые пришлось
  дописать); ещё 10 экшенов Plugin (CDEK/Европочта/Белпочта/RocketSMS)
  сознательно не перепроверялись — закрыты CMP-437.
- Из них **~20 действий, бьющих во внешние API** (МойСклад/AmoCRM/Telegram/
  Poizon), проверены только кодом + auth-gate, без реального удара по
  боевым сервисам — по прямому указанию карточки; credentials для всех трёх
  подтверждённо отсутствуют в `.env`/БД в этом окружении.
- **6 дефектов найдено, 6 исправлено**, четыре из них критичные:
  1. `MoyskladController::actionWebhook` + `TelegramBotController::actionWebhook`
     — оба инбаунд-вебхука были полностью неработоспособны (302 на любой
     реальный внешний вызов + у МойСклад ещё и мёртвая CSRF-отключалка).
  2. `AmocrmStatusMapper::saveMappings()` — обычное сохранение маппинга по
     одному треку стирало все остальные треки целиком (реально уничтожило 2
     живые строки Instagram DM-бота в ходе теста, восстановлены).
  3. `PluginController::actionLamodaRun`/`actionAmocrmSync` — GET без CSRF
     триггерил реальный внешний парсинг/синхронизацию (тот же класс, что
     CMP-418); фикс распространён на все 13 JSON-мутирующих экшенов
     контроллера разом.
  4. `PluginController::actionDobropost` + 2 отсутствовавших экшена
     (`actionSaveProxyPhones`, `actionSaveStatusMapping`) — вся страница
     настроек DobroPost (телефонная приватность из CMP-359 + маппинг
     таможенных статусов) была наполовину мёртвой: два кликабельных
     «Сохранить»-кнопки 404'или, а третья секция всегда рендерилась пустой
     даже при рабочих кнопках.
  5. `AmoCrmController::actionUpdateStatus` — 100% отказ (500) при любом
     вызове независимо от кредов (незаданный response format).
  6. `frontend/web/js/admin-poizon.js::bulkAssignLogist()` — побочный баг из
     CMP-467, `TypeError` при реальном ответе `actionLogists`; фикс применён,
     живой клик недоступен (осиротевшее дерево видов без маршрута).
- **7 находок без фикса** — см. «Найдено, не исправлено»: отсутствующий
  `actionImportDobropost`; отсутствие ролевой дифференциации в
  `AmoCrmController`/`TelegramBotController` (второй — уже в списке CMP-465,
  первый — пропуск в том списке); непоследовательный `requirePermission` в
  `PluginController`; мёртвый код `PluginController::actionMoysklad`;
  косметический `last_sync_at` при 0 успехах; мёртвая
  `actionNotifyStatus` (совпадает с CMP-422); минус в `skipped` у
  `parse_lamoda.php` (вне объёма).
- Тестовые данные полностью откачены и подтверждены `SELECT`-ами:
  `app_setting` для секций `moysklad`/`dobropost` вернулись к 0 строк,
  `amocrm` — к исходным 2 строкам, `lamoda` — к исходным 4 строкам;
  `amocrm_field_mapping` — к 0 строкам; `amocrm_status_mapping` — к исходным
  2 строкам (`dm.started`/`dm.handed_off`, те же значения, новые
  auto-increment id — контент идентичен); `delivery_status_mapping.id=1` —
  к исходным `internal_status='waiting', estimated_days=NULL, is_final=0`;
  `import_batch` — к исходным 2 строкам (id 7, 8); `order.id=5.amocrm_deal_id`
  — к `NULL`; кэш-ключ `plugin_settings_stripe` — удалён (плагин обратно
  неактивен); `/tmp/lamoda_parse.log` — удалён.

## Проверенные действия (список)

**MoyskladController:** actionIndex — ok · actionSaveStatusMapping — ok ·
actionSaveSettings — ok · actionSyncLog — ok · actionTestConnection — ok (без
кредов) · actionSaveCredentials — ok · actionSaveMapping — ok · actionPushAll
— ok (без кредов) · actionPeriodicSync — ok (без кредов) · actionWebhookStatus
— ok (без кредов) · actionPull — ok (без кредов) · actionPushOrder — ok (без
кредов) · actionSyncInfo — ok (без кредов) · actionWebhooks — ok (без кредов)
· actionRegisterWebhook — ok (без кредов) · actionDeleteWebhook — ok ·
actionGetDbColumns — ok · actionSaveMoyskladMapping — ok · **actionWebhook —
fixed (критично)** · actionMsImages — ok · actionMsImage — ok.

**PoizonController:** actionIndex — ok · actionRun — ok (POST-ветка: код
проверен, внешний API исключён) · actionView — ok · actionViewLog — ok ·
actionDelete — ok · actionErrors — ok.

**AmoCrmController:** actionCreateDeal — ok (без кредов) · **actionUpdateStatus
— fixed (критично)** · actionSettings — ok.

**TelegramBotController:** **actionWebhook — fixed (критично)** ·
**actionNotifyStatus — fixed** (verb+CSRF) · **actionSetWebhook — fixed**
(verb+CSRF).

**PluginController:** actionIndex — ok · actionToggle — ok (+verb-фикс) ·
**actionSettings — fixed (критично)** · actionMoysklad — не баг, мёртвый код
· actionAmocrm — ok · actionAmocrmAuthorize — ok · actionAmocrmCallback — ok
· actionAmocrmSave — ok · actionAmocrmTest — ok (+verb-фикс) ·
**actionAmocrmSync — fixed** (verb-фикс) · actionAmocrmLogs — ok ·
actionAmocrmStats — ok · actionAmocrmFields — ok · actionAmocrmFieldsSave —
ok · actionAmocrmFieldsDelete — ok · actionAmocrmPipelines — ok (+verb-фикс)
· **actionAmocrmStatusMapSave — fixed (критично, через
AmocrmStatusMapper::saveMappings)** · actionAmocrmWidget — ok ·
actionAmocrmWidgetKey — ok (+verb-фикс) · actionTelegram — ok · actionCurrency
— ok · **actionDobropost — fixed (критично)** · **actionSaveProxyPhones —
реализовано** · **actionSaveStatusMapping — реализовано** · actionLamoda —
ok · actionLamodaParser — ok · **actionLamodaRun — fixed (критично)** ·
actionLamodaStatus — ok · actionLamodaSaveSchedule — ok (+verb-фикс).

**Вне объёма (CMP-437):** actionCdek, actionSaveCdek, actionEuropochta,
actionSaveEuropochta, actionBelpochta, actionSaveBelpochta,
actionTestTracking, actionRocketsms, actionSaveRocketsms, actionTestRocketsms.
