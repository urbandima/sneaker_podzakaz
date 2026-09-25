# CMP-467 — Сплошной прогон мутирующих действий: Settings + User (настройки и роли)

Продолжение методологии CMP-456/CMP-460/CMP-463: живой HTTP-запрос через
реальную admin-сессию (cookies + CSRF из `<meta name="csrf-token">` /
`<meta name="csrf-param">`, ровно в том формате, в котором его берёт реальный
JS-код страницы — `getCsrfToken()` / `document.querySelector('meta[name=csrf-token]')`)
со сверкой фактической строки в MySQL (`cmp410_e2e_clean`) до/после — вердикт
по эффекту в БД/файле, а не по HTTP-коду. Payload каждого действия сверен с
реальным вызывающим кодом (`frontend/web/js/admin-settings.js`,
inline `<script>` во вьюхах), а не придуман по сигнатуре контроллера — именно
так в CMP-463 нашли `actionBulkUpdatePrice`, и именно так в этой волне нашли
три похожих (и более серьёзных) дефекта.

## Границы охвата

Все публичные экшены двух контроллеров:

- `backend/modules/admin/controllers/SettingsController.php` — 20 экшенов
  (`actionIndex`, `actionIntegrations`, `actionSave`, `actionSaveLoyalty`,
  `actionSaveCompany`, `actionSaveGaId`, `actionStatuses`, `actionSaveStatuses`,
  `actionEmailTemplates`, `actionSaveEmailTemplate`, `actionTestEmail`,
  `actionTestTelegram`, `actionTestMoysklad`, `actionTestAmocrm`, `actionPayment`,
  `actionSavePayment`, `actionSeo`, `actionSources`, `actionShipping`,
  `actionSaveShipping`).
- `backend/modules/admin/controllers/UserController.php` — 8 экшенов
  (`actionLogists`, `actionIndex`, `actionCreate`, `actionResetPassword`,
  `actionToggleBlock`, `actionEdit`, `actionExport`, `actionDelete`).

Для 4 экшенов внешних интеграций (`test-email`, `test-telegram`, `test-moysklad`,
`test-amocrm`) реальные боевые вызовы намеренно не делались (см. «Честная
граница охвата»).

**Методологическая заметка:** в процессе прогона несколько раз ловил обрыв
admin-сессии между отдельными bash-вызовами (PHP built-in dev-сервер без
явного `session.save_path` резолвит временную директорию через `$TMPDIR`
процесса; на этой машине параллельно крутится десяток чужих dev-серверов от
других агентских сессий). Это артефакт тестового окружения, не баг
приложения — каждый раз перед чувствительным запросом добавлял проверку
`GET /admin/settings/index == 200` и при необходимости перелогинивался в
рамках того же вызова.

## Реестр

| Действие | Маршрут | Payload (кратко) | HTTP | Факт в БД/файле | Вердикт |
|---|---|---|---|---|---|
| `actionIndex` | `GET /admin/settings/index` | — | 200 | рендер без ошибок | **ok** |
| `actionIntegrations` | `GET /admin/settings/integrations` | — | 302 → `/admin/plugin/index` | редирект, не мутирует | **ok** |
| `actionSave` | `POST /admin/settings/save` | JSON `{"webhook":{"url":...,"secret":...}}`; отдельно `{"some_forbidden_section":{...}}` | 200 / 400 | `webhook.url`/`webhook.secret` записаны в `app_setting`; запрещённая секция отбита whitelist'ом (AUDIT-26) | **ok** |
| `actionSaveLoyalty` | `POST /admin/settings/save-loyalty` | JSON `{"loyalty":{enabled,bronze_min,...}}` (10 полей) | 200 | все 10 ключей записаны в `app_setting` секции `loyalty` с точными значениями | **ok** |
| `actionSaveCompany` | `POST /admin/settings/save-company` | JSON `{"name":"CMP-467 TEST Company",...}` (9 полей, как реально шлёт `saveCompany()` из `admin-settings.js`) | **до фикса: 200, `success:false`**; после фикса: 200, `success:true` | до фикса: `company_settings` не менялась вообще (0 полей записано); после фикса: все 9 полей, включая `work_time`, записаны верно | **wrong_data → исправлено** |
| `actionSaveGaId` | `POST /admin/settings/save-ga-id` | JSON `{"ga_id":"G-CMP467TEST"}`; затем `{"ga_id":"bogus-format"}` | 200 / 200 | валидный ID записан в `app_setting.seo.ga_id` (то, что реально читает `actionSeo`); невалидный формат отбит regex'ом, значение не тронуто | **ok** (см. также «Найдено, не исправлено» — параллельный dead-write в таблицу `settings`) |
| `actionStatuses` | `GET /admin/settings/statuses` | — | 200 | рендер 21 статуса без ошибок | **ok** |
| `actionSaveStatuses` | `POST /admin/settings/save-statuses` | JSON `{"statuses":[...21 объект...]}` — ровно то, что шлёт реальный `saveStatuses()` из inline `<script>` вьюхи `statuses.php` | 200 | **до фикса**: реальный клик по кнопке «Сохранить изменения» в браузере (проверено Playwright) слал `{"statuses":[]}` → controller удалил все статусы кроме `new`/`paid`/`canceled`: **21 строка → 3**; **после фикса**: реальный клик передаёт все 21 статус с верными `is_active`/`logist_available`, 21 строка сохранена | **wrong_data → исправлено (критично)** |
| `actionEmailTemplates` | `GET /admin/settings/email-templates` | — | 200 | рендер без ошибок | **ok** |
| `actionSaveEmailTemplate` | `POST /admin/settings/save-email-template` | JSON `{"key":"confirmed","subject":"CMP-467 тест тема","body":"<p>...кириллица...</p>"}`; отдельно `{"key":"not_a_real_event",...}` | 200 / 200 | valid key → `app_setting.email_template_confirmed.{subject,body}` записаны кириллицей без порчи; invalid key отбит whitelist'ом | **ok** |
| `actionTestEmail` | `POST /admin/settings/test-email` | JSON `{"key":"confirmed"}` | 200 | `MAIL_USE_FILE_TRANSPORT=true` (подтверждено в `.env`) → реальное письмо не улетело, создан `runtime/mail/*.eml` с верной темой/телом (только что сохранённый шаблон) и адресатом — email текущего admin | **ok** |
| `actionTestTelegram` | `POST /admin/settings/test-telegram` | `{}` | без cookies: 302 → `/admin/login`; с cookies, без токена в БД: 200 | без токена — ранний `return` до похода в Telegram API, внешний вызов не делается; код обёрнут в try/catch | **код проверен, живой вызов не делался — нет кредов** |
| `actionTestMoysklad` | `POST /admin/settings/test-moysklad` | `{}` | 302 (guest) / 200 (без ключа) | то же самое: ранний `return` без внешнего HTTP; curl-вызов обёрнут в try/catch, `curl_exec()==false` не уронит 500 | **код проверен, живой вызов не делался — нет кредов** |
| `actionTestAmocrm` | `POST /admin/settings/test-amocrm` | `{}` | 302 (guest) / 200 (без домена/токена) | то же самое | **код проверен, живой вызов не делался — нет кредов** |
| `actionPayment` | `GET /admin/settings/payment` | — | 200 | рендер без ошибок | **ok** |
| `actionSavePayment` | `POST /admin/settings/save-payment` | JSON `{"methods":[{"id":"cmp467_cash",...,"sort_order":2},{"id":"cmp467_card","name":"CMP-467 Карта <script>",...,"sort_order":1}]}` | 200 | `app_setting.checkout.payment_methods` записан; `<script>` вырезан `strip_tags`; `sort_order` пересчитан последовательно | **ok** |
| `actionSeo` | `GET /admin/settings/seo`, `POST /admin/settings/seo` | form-urlencoded (реальная HTML-форма, не JSON): `meta_title_tpl`, `meta_desc_tpl`, `ga_id`, `metrika_id`, `robots_txt`, `sitemap_enabled` | 200 / 302 | все 6 полей записаны в `app_setting.seo.*` верно | **ok** |
| `actionSources` | `POST /admin/settings/sources` | JSON `{"sources":["Сайт CMP-467"," ","Telegram CMP-467"]}` | 200 | `app_setting.order.sources` = `["Сайт CMP-467","Telegram CMP-467"]` — пустая строка после trim корректно отфильтрована | **ok** |
| `actionShipping` | `GET /admin/settings/shipping` | — | 200 | рендер без ошибок | **ok** |
| `actionSaveShipping` | `POST /admin/settings/save-shipping` | JSON с 2 методами: без `plugin` + `status:"active"`, и с `plugin:"cdek"` + `status:"active"` | 200 | метод без plugin принудительно переведён в `inactive` (Z74-защита), метод с plugin остался `active` — сохранено в `app_setting.checkout.shipping_methods` | **ok** |
| `actionLogists` | `GET /admin/user/logists` | — | 200 | **до фикса**: `{"success":true,"logists":[]}` всегда, при живых активных логистах в БД; **после фикса**: реальный список активных логистов | **wrong_data → исправлено** |
| `actionIndex` (User) | `GET /admin/user/index` | — | 200 | рендер списка без ошибок | **ok** |
| `actionCreate` | `POST /admin/user/create` | form: `username`, `email`, `password`, `role=manager` / `role=admin` / `role=superadmin` (невалидная) | 302 / 302 / 200 (ошибка валидации) | `manager` и `admin` роли записаны **точно как переданы** (не дефолт); невалидная роль `superadmin` отбита `in`-валидатором, строка не создана | **ok** |
| `actionResetPassword` | `POST /admin/user/reset-password` | JSON `{"id":4}` — ровно то, что шлёт `resetPassword()` из `admin-settings.js` | **до фикса: 200, `success:false,"ID не указан"`**; после фикса: 200, `success:true` | до фикса: хэш пароля не менялся вообще ни разу; после фикса: новый хэш подтверждён `password_verify()`; self-reset (id=1) корректно отбит | **wrong_data → исправлено** |
| `actionToggleBlock` | `POST /admin/user/toggle-block` | JSON `{"id":4}` — ровно то, что шлёт `toggleBlock()` из `admin-settings.js` | **до фикса: 200, `success:false,"ID не указан"`**; после фикса: 200, `success:true` | до фикса: `status` не менялся вообще ни разу; после фикса: `10→9→10` подтверждено, залогиниться заблокированным пользователем не удаётся («Неверное имя пользователя или пароль»); self-block (id=1) корректно отбит | **wrong_data → исправлено** |
| `actionEdit` (GET) | `GET /admin/user/edit?id=N` | — | **до фикса: 500** (`views/user/update.php` физически отсутствовал); после фикса: 200 | страница редактирования была недоступна вообще | **wrong_data → исправлено (критично)** |
| `actionEdit` (POST) | `POST /admin/user/edit?id=N` | form: `username`, `email`, `role`, `password` (пусто / короткий / валидный) | 302 / 200 (ошибка) / 302 | до фикса (гипотетически, после починки view): `$model->scenario='update'` не был объявлен в `User::scenarios()` → `load()` возвращал `true`, но **не присваивал НИ ОДНОГО поля** (username/email/role/password), при этом флэш «Пользователь обновлён» показывался; после фикса: username/email/role меняются верно, пароль опционален и валидируется по той же длине (8+), что и при создании | **wrong_data → исправлено (критично)** |
| `actionExport` | `GET /admin/user/export` | — | 200 | CSV с BOM, кириллица (`Логист`, `Активен` и т.д.) цела, колонки верные, сортировка по `created_at DESC` | **ok** |
| `actionDelete` | `POST /admin/user/delete?id=N` | form `_csrf` (реальный флоу — динамическая форма из `deleteUser()`) | 302 | self-delete (id=1) корректно отбит («Нельзя удалить самого себя», строка жива); тестовый пользователь удалён из `user`; проверено, что записи `order.created_by`/`order_history.changed_by`, указывавшие на удалённого юзера, остаются «висячими» (без FK) — не роняют `order/view`, но это тот же класс бага, что CMP-433/446 (см. «Найдено, не исправлено») | **ok** (с задокументированным открытым вопросом) |

## Найденный дефект и фикс

### 1. `SettingsController::actionSaveCompany` — реквизиты компании не сохранялись вообще

Код ссылался на **несуществующую переменную `$company`** (мёртвый огрызок
более старой in-memory-реализации, оставшийся после рефакторинга A9 на
`CompanySettings`-модель), и построенная в начале метода `$model` **ни разу
не сохранялась** (`->save()` не вызывался). Живым прогоном подтверждено: POST
с реальным payload формы (`name`, `unp`, `address`, `phone`, `email`,
`work_time`, `bank`, `bic`, `account`) → ответ
`{"success":false,"message":"Ошибка: Undefined variable $company"}`,
`company_settings` не менялась ни на одно поле.

Дополнительно: даже если бы это работало, поле `work_time` всё равно не
доходило бы до витрины — `CompanySettings::getSettings()` не включал
`work_time` в возвращаемый массив, а `frontend\controllers\OrderController`
берёт `$company['work_time'] ?? Settings::get('company','work_time',...)` —
и запись в `settings('company','work_time')` тоже была частью удалённого
мёртвого кода.

**Фикс** (`backend/modules/admin/controllers/SettingsController.php`,
`backend/modules/admin/models/CompanySettings.php`): метод переписан на
единственный рабочий путь — заполнение `$model` (уже существовавший в
верхней части функции) из whitelisted `$fields` и явный `$model->save()`;
мёртвый блок с `$company` удалён. `CompanySettings::getSettings()` теперь
отдаёт `work_time`, чтобы сохранённое в админке значение реально доходило до
чекаута. Подтверждено повторным живым запросом: все 9 полей записались,
`password_verify`-эквивалент для этого случая — прямой `SELECT` — показал
точные значения.

### 2. `SettingsController::actionSaveStatuses` — реальный клик «Сохранить» удалял почти все статусы заказов

Самый серьёзный дефект волны. `frontend/web/js/admin-settings.js` содержал
**мёртвый дубликат** `window.saveStatuses`/`window.addStatus` (блок
`/* -- settings/statuses.php -- */`), рассчитанный на разметку
(`.status-config-item`, поле `active` без `logist_available`), которая **не
существует** в реальной вьюхе `backend/modules/admin/views/settings/statuses.php`
(там `.status-row`, поля `is_active`/`logist_available`, и есть свой
корректный inline `<script>` с той же самой функцией). Файл `admin-settings.js`
подключается позже вьюхи через `AdminAsset` и оба блока вешаются на
`DOMContentLoaded` — поэтому именно сломанная версия из `admin-settings.js`
всегда перезаписывала правильную и выигрывала гонку.

Живым прогоном через Playwright (реальный клик по кнопке «Сохранить
изменения» в загруженной странице, без перехвата сети) подтверждено:
запрос уходил как `{"statuses":[]}` (сломанный селектор не находил ни одной
строки), а `actionSaveStatuses` интерпретирует пустой список как «удалить
все нестандартные статусы» (`$systemKeys = ['new','paid','canceled']`) —
**21 строка `order_status` → 3 строки** за один клик, без единого сообщения
об ошибке пользователю (ответ `{"success":true,...}`, "Статусы сохранены").
Восстановлено из `mysqldump`-бэкапа, снятого перед тестом.

**Фикс** (`frontend/web/js/admin-settings.js`): мёртвый блок удалён, оставлен
единственный (корректный) обработчик — inline-скрипт самой вьюхи. Подтверждено
повторным реальным Playwright-кликом: `window.saveStatuses` резолвится в
правильную функцию, POST уходит с полным списком из 21 статуса и верными
`is_active`/`logist_available`, `order_status` не теряет строк.

*(Проверено также, что `backend/web/js/admin-settings.js` содержит тот же
мёртвый блок — но этот файл не обслуживается текущим приложением (единственная
точка входа — `frontend/web/index.php`), поэтому не трогал его, чтобы не
раздувать diff недоказанным изменением.)*

### 3. `UserController::actionResetPassword` и `actionToggleBlock` — обе AJAX-кнопки были 100%-ным no-op

Тот же класс бага, что `actionBulkUpdatePrice` из CMP-463. Реальные вызовы
(`resetPassword()` и `toggleBlock()` в `admin-settings.js`) шлют
`fetch(..., {headers:{'Content-Type':'application/json'}, body: JSON.stringify({id})})`,
а оба экшена читали `Yii::$app->request->post('id')` — приложение нигде не
регистрирует JSON-парсер для `Request::$parsers`, поэтому `post('id')` для
raw-JSON тела всегда возвращал `null`. Живым прогоном подтверждено: оба
экшена **всегда** отвечали `{"success":false,"message":"ID не указан"}`,
независимо от того, какой пользователь выбран — хэш пароля и статус
блокировки не менялись НИ РАЗУ ни для одного пользователя через реальный UI.

**Фикс**: оба метода теперь читают тело так же, как остальные
JSON-эндпоинты этих контроллеров:
`json_decode(Yii::$app->request->getRawBody(), true) ?: Yii::$app->request->post()`.
Подтверждено повторным живым прогоном: `actionResetPassword` возвращает
новый пароль, хэш в БД меняется и проходит `password_verify()`;
`actionToggleBlock` переключает `status` `10↔9`, а заблокированный
пользователь после этого реально не может залогиниться (проверено попыткой
входа под его логином/паролем — «Неверное имя пользователя или пароль»,
что ожидаемо, т.к. `User::findByUsername()` фильтрует по `STATUS_ACTIVE`).
Обе self-защиты (нельзя сбросить/заблокировать самого себя) продолжают
работать.

Заодно исправлен соседний мелкий баг в `actionToggleBlock`: поле ответа
`blocked` считалось как `!$wasActive` — то есть **описывало состояние ДО
переключения, инвертированное**, а не результат. Пользователь, который
только что был заблокирован, получал `"blocked":false`. Сейчас этот флаг
никем не читается (`toggleBlock()` в JS просто перезагружает страницу при
`success`), поэтому живого UI-эффекта не было, но раз уж чинил рядом —
поправил на `'blocked' => $wasActive`.

### 4. `UserController::actionEdit` — страница редактирования пользователя была полностью нерабочей (два независимых дефекта)

1. **View не существовал.** `return $this->render('update', [...])` указывал
   на `backend/modules/admin/views/user/update.php`, которого физически нет
   в репозитории (в директории есть только `index.php` и `create.php`).
   Живым прогоном подтверждено: `GET /admin/user/edit?id=2` → **500 Internal
   Server Error** («Ошибка сервера»). Реальная кнопка «Редактировать» в
   `admin-settings.js` (`editUser()`) ведёт именно на этот маршрут — то есть
   редактирование пользователя через UI было невозможно в принципе.
2. **Даже если бы страница открылась, сохранение ничего бы не поменяло.**
   `$model->scenario = 'update'` — но `'update'` ни разу не объявлен ни в
   `rules()`, ни в переопределённом `UserController`... то есть
   `User::scenarios()`. По умолчанию Yii строит `scenarios()` только из
   `on=>[...]` меток в `rules()` — ни одно правило не использует
   `on=>'update'`. Как следствие, `Model::safeAttributes()`/`activeAttributes()`
   для незарегистрированного сценария возвращают `[]`, и
   `$model->load($_POST)` **не присваивает ни одного поля**, хотя сам
   `load()` всё равно возвращает `true` (проверяет только наличие ключа
   `User` в данных) — контроллер шёл дальше, `$model->save()` проходил
   тривиально (валидировать было нечего менять) и показывал «Пользователь
   обновлён», при том что username/email/role/password оставались прежними.
   Это тот же класс «200 OK ≠ факт в БД», что и остальные находки волны, но
   куда тише — сообщение об успехе даже более убедительное, чем в других
   случаях.

**Фикс**: создана `backend/modules/admin/views/user/update.php` (по образцу
`create.php`, пароль опционален с подсказкой «оставьте пустым»); в
`User::scenarios()` добавлен `'update' => ['username','email','password','role']`
(намеренно без `status` — им управляет отдельный, уже проверенный
`actionToggleBlock` с защитой от self-block); заодно расширено правило
минимальной длины пароля (AUDIT-70, было `on=>'create'`) на `['create','update']`,
чтобы слабый пароль нельзя было проскочить через форму редактирования.
Подтверждено живым прогоном: `GET edit` → 200, рендерится форма; `POST edit`
меняет `username`/`email`/`role` (проверено сменой `manager→logist`);
короткий пароль (5 симв.) отбит валидацией, хэш не тронут; валидный новый
пароль меняет хэш и проходит `password_verify()`.

### 5. `UserController::actionLogists` — список логистов всегда пустой

`->andWhere(['status' => 'active'])` сравнивал **smallint-колонку**
(`User::STATUS_ACTIVE = 10`) со строкой `'active'` — в MySQL это никогда не
матчится (`status = 'active'` неявно приводится к `status = 0`, что вообще-то
`STATUS_DELETED`). Живым прогоном подтверждено: при реально живом логисте в
БД (`role=logist`, `status=10`) эндпоинт стабильно отвечал
`{"success":true,"logists":[]}`, без исключения — то есть без даже
демо-фоллбэка, который сработал бы только при ошибке запроса.

**Фикс**: `'status' => 'active'` → `'status' => User::STATUS_ACTIVE`.
Подтверждено: тот же запрос теперь возвращает реального активного логиста.

## Найдено, не исправлено

- **`UserController::actionDelete` не защищён FK от «висячих» ссылок на
  удалённого пользователя** (тот же класс бага, что CMP-433/CMP-446 —
  `cart.user_id`/`favorite`/`product_review`). В схеме есть 5 таблиц с
  правильным `ON DELETE CASCADE`/`SET NULL` на `user.id`
  (`filter_history`, `size_feedback`, `tariff_calculation`, `import_batch`,
  `characteristic_history`), но **пятнадцать** других колонок,
  указывающих на `user.id`, вообще без FK: `order.created_by`,
  `order.passport_issued_by`, `order_history.changed_by`,
  `order_timeline.created_by`, `payment.confirmed_by`,
  `product_review.moderated_by`, `purchase_order.created_by`,
  `receiving_document.uploaded_by`, `receiving_history.changed_by`,
  `expense.created_by`, `page_content.updated_by`, `page_revision.saved_by`,
  `supplier_return.created_by`, `characteristic.updated_by`,
  `product_price_history.changed_by`, `customer.referred_by`. Живым прогоном
  подтверждено: после `actionDelete` эти значения остаются указывать на
  несуществующего пользователя (проверено на `order.created_by` +
  `order_history.changed_by`), но `GET /admin/order/view?id=...` рендерится
  без ошибок — потому что ни одна вьюха сейчас не обращается к relations
  `Order::getCreator()`/`OrderHistory::getChanger()` (проверено grep'ом по
  всему `backend/modules/admin/views` — ноль обращений). То есть баг
  **реален (тихая потеря атрибуции «кто сделал»), но не роняет ничего
  прямо сейчас**. Вопрос совету: расширять ли FK-защиту на эти 15 колонок
  (как уже сделано для 5 других) или это осознанно «мягкая» связь для
  исторических данных?
- **`UserController::actionLogists`, даже после фикса, всё ещё не решает
  проблему целиком** — реальный потребитель, `frontend/web/js/admin-poizon.js`
  (модалка массового назначения логиста в Poizon-заказах), делает
  `fetch('/admin/user/logists').then(r=>r.json()).then(data => data.forEach(...))`,
  а эндпоинт возвращает объект `{"success":true,"logists":[...]}`, а не
  голый массив — `data.forEach` бросит `TypeError` в браузере. Эта фича
  (bulk-назначение логиста в Poizon-заказах) не входит в объявленный объём
  этой волны (`SettingsController`/`UserController`), поэтому JS не трогал —
  но фиксировать нужно оба места вместе, иначе фича так и останется битой
  даже после сегодняшнего фикса контроллера.
- **`SettingsController::actionSaveGaId` дублирует запись в мёртвую таблицу
  `{{%settings}}`** (`Yii::$app->db->createCommand()->upsert('{{%settings}}', ...)`)
  в дополнение к рабочей записи в `app_setting` через `Yii::$app->settings->set()`.
  Ничего в кодовой базе не читает из `{{%settings}}` напрямую (кроме старых
  mojibake-фикс-миграций) — это безобидный, но лишний dead-write. Не
  фиксировал, т.к. функционального эффекта нет и trade-off неочевиден без
  решения совета — трогать ли миграции/удалять таблицу.
- **CSRF отключён для 5 экшенов `SettingsController`** (`save`,
  `save-statuses`, `save-payment`, `save-shipping`, `save-loyalty`) —
  задокументированный существующий TODO в самом коде
  (`beforeAction`: «CSRF отключён для JSON AJAX-эндпоинтов... TODO: передавать
  X-CSRF-Token в заголовках fetch-запросов и убрать это исключение»). Не
  новая находка, оставляю как есть — команда уже знает и явно отложила.

## Честная граница охвата

- `actionTestTelegram`/`actionTestMoysklad`/`actionTestAmocrm` проверены на
  (а) доступ только под admin-сессией (302 без cookies) и (б) ветку «креды не
  настроены» (ранний `return` без похода во внешний API, подтверждено —
  `settings`-секции `telegram`/`moysklad`/`amocrm` пусты в `.env` и в БД).
  Код всех трёх обёрнут в `try/catch` вокруг `curl_exec`; `curl_exec()===false`
  (сетевая ошибка) не бросает исключение и не роняет весь admin — обработано
  явной проверкой `HTTP-кода`. Реальный вызов к внешним сервисам **не
  делался** — ни настоящих сообщений в Telegram, ни ударов по боевому
  МойСклад/AmoCRM.
- `actionTestEmail` протестирован полным живым циклом, но безопасно:
  `MAIL_USE_FILE_TRANSPORT=true` в `.env`, письмо не покидает сервер, реально
  проверен файл `runtime/mail/*.eml`.
- Остальные 24 экшена проверены полным циклом (живой HTTP + сверка MySQL/файла
  до/после).

## Итого волны

- **28 действий проверено** живым прогоном (20 `SettingsController` + 8
  `UserController`); для 4 из них (внешние интеграции) — только код +
  auth-gate, по явному указанию карточки не бить по боевым API.
- **5 дефектов найдено и исправлено**, три из них — критичные:
  1. `SettingsController::actionSaveCompany` — реквизиты компании не
     сохранялись вообще (undefined variable + отсутствующий `save()`).
  2. `SettingsController::actionSaveStatuses` — реальный клик «Сохранить»
     удалял 18 из 21 статуса заказа (мёртвый конфликтующий JS-дубликат).
  3. `UserController::actionResetPassword` + `actionToggleBlock` — обе
     кнопки были 100%-ным no-op в реальном UI (JSON body никогда не
     парсился) + попутно исправлен инвертированный флаг `blocked`.
  4. `UserController::actionEdit` — страница редактирования пользователя не
     открывалась (500, отсутствующая вьюха) и, даже если бы открылась,
     ничего не сохраняла (незарегистрированный сценарий `update`).
  5. `UserController::actionLogists` — список логистов всегда был пустым
     (int-колонка сравнивалась со строкой).
- **4 находки без фикса** — вынесены с открытыми вопросами: отсутствие FK-
  защиты для 15 колонок `*_by`/`referred_by`, ссылающихся на `user.id`
  (класс бага CMP-433/446, подтверждено «не роняет», но теряет атрибуцию);
  несовместимый по форме JS-потребитель `actionLogists` в другом модуле
  (Poizon bulk-assign); мёртвый dead-write в orphaned-таблицу `settings` из
  `actionSaveGaId`; уже задокументированный TODO по CSRF-исключениям.
- Тестовые данные полностью откачены и подтверждены `SELECT`-ами: `user`
  вернулась к исходным 3 строкам (`admin`/`manager`/`logist`, id 1-3);
  `company_settings` восстановлена к исходным значениям; `app_setting`
  вернулась к исходным 13 строкам; `settings` — к 0 строкам; `order_status`
  — к исходным 21 строке (после аварийного восстановления из
  `mysqldump`-бэкапа, снятого перед разрушительным тестом
  `actionSaveStatuses`); тестовая строка `order_history` удалена,
  `order.created_by` для затронутого заказа возвращён в `NULL`;
  `runtime/mail/*.eml` от `actionTestEmail` удалён.

## Проверенные действия (список)

**SettingsController:**
1. `actionIndex` — ok
2. `actionIntegrations` — ok
3. `actionSave` — ok
4. `actionSaveLoyalty` — ok
5. `actionSaveCompany` — **fixed** (см. дефект №1)
6. `actionSaveGaId` — ok (dead-write в мёртвую таблицу — см. «не исправлено»)
7. `actionStatuses` — ok
8. `actionSaveStatuses` — **fixed** (см. дефект №2, критично)
9. `actionEmailTemplates` — ok
10. `actionSaveEmailTemplate` — ok
11. `actionTestEmail` — ok
12. `actionTestTelegram` — ok (код проверен, нет кредов)
13. `actionTestMoysklad` — ok (код проверен, нет кредов)
14. `actionTestAmocrm` — ok (код проверен, нет кредов)
15. `actionPayment` — ok
16. `actionSavePayment` — ok
17. `actionSeo` — ok
18. `actionSources` — ok
19. `actionShipping` — ok
20. `actionSaveShipping` — ok

**UserController:**
21. `actionLogists` — **fixed** (см. дефект №5)
22. `actionIndex` — ok
23. `actionCreate` — ok
24. `actionResetPassword` — **fixed** (см. дефект №3)
25. `actionToggleBlock` — **fixed** (см. дефект №3)
26. `actionEdit` — **fixed** (см. дефект №4, критично)
27. `actionExport` — ok
28. `actionDelete` — ok (с открытым вопросом по FK — см. «не исправлено»)
