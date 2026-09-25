# CMP-470-Г — Сплошной прогон мутирующих действий: Marketing / SEO / Email / Automation / Notification / Loyalty / SidebarMenu / Feedback

Продолжение методологии CMP-456/CMP-460/CMP-463/CMP-467: живой HTTP-запрос
через реальную admin-сессию (cookies + CSRF из `<meta name="csrf-token">` /
inline-скриптов вьюх, ровно в том формате, в котором его берёт реальный
вызывающий JS-код), со сверкой фактической строки в MySQL
(`cmp410_e2e_clean`) до/после — вердикт по эффекту в БД/файле, а не по
HTTP-коду. Каждый payload сверен с реальным JS (`frontend/web/js/admin-settings.js`,
inline `<script>` во вьюхах) или с формой `ActiveForm`, а не придуман по
сигнатуре контроллера.

Стенд: `php -S 127.0.0.1:8783 -t frontend/web router.php`, MySQL
`cmp410_e2e_clean`. `.env`: `MAIL_USE_FILE_TRANSPORT=true` — письма пишутся в
`runtime/mail/*.eml`, реально никуда не уходят.

## Границы охвата

Все публичные экшены восьми контроллеров:

- `MarketingController` — 7 экшенов (`actionIndex`, `actionAbandonedCarts`,
  `actionSendReminder`, `actionSendBulkReminders`, `actionRecommendations`,
  `actionGetRecommendations`, `actionCampaigns`).
- `SeoController` — 10 экшенов (`actionIndex`, `actionRedirects`,
  `actionRedirectEdit`, `actionRedirectDelete`, `actionBulkMeta`,
  `actionSitemap`, `actionRobots`, `actionUpdateProductMeta`,
  `actionAltTexts`, `actionUpdateImageAlt`).
- `EmailController` — 3 экшена (`actionSend`, `actionTest`, `actionTemplates`).
- `AutomationController` — 6 экшенов (`actionIndex`, `actionCreate`,
  `actionUpdate`, `actionDelete`, `actionToggle`, `actionLog`).
- `NotificationController` — 1 экшен (`actionIndex`).
- `LoyaltyController` — 1 экшен (`actionIndex`).
- `SidebarMenuController` — 7 экшенов (`actionIndex`, `actionView`,
  `actionCreate`, `actionUpdate`, `actionDelete`, `actionToggle`, `actionSort`).
- `FeedbackController` — 3 экшена (`actionIndex`, `actionReply`, `actionDelete`).

Итого 38 экшенов, **все проверены живым прогоном** (кроме честно
задокументированной границы для 2 экшенов `EmailController`, см. «Честная
граница охвата»).

## Реестр

| Действие | Маршрут | Payload (кратко) | HTTP | Факт в БД/файле | Вердикт |
|---|---|---|---|---|---|
| `Marketing::actionIndex` | `GET /admin/marketing/index` | — | 200 | рендер без ошибок | **ok** |
| `Marketing::actionAbandonedCarts` | `GET /admin/marketing/abandoned-carts` | — | **до фикса: 500**; после: 200 | вьюха `marketing/abandoned-carts.php` физически отсутствовала — страница «Показать все» из index.php вела на гарантированный 500 | **wrong_data → исправлено** |
| `Marketing::actionSendReminder` | `POST /admin/marketing/send-reminder` | form `cart_id` (как шлёт `sendReminder()` из admin-settings.js) | 200 | реальный `.eml` создан с верным адресатом/темой/текстом | **ok** |
| `Marketing::actionSendBulkReminders` | `POST /admin/marketing/send-bulk-reminders` | без тела (как шлёт `sendBulkReminders()`) | 200 | счётчик `count` совпал с фактическим числом отправленных писем (1 письмо → `count:1`, файл создан) | **ok** |
| `Marketing::actionRecommendations` | `GET /admin/marketing/recommendations` | — | 200 | рендер без ошибок | **ok** |
| `Marketing::actionGetRecommendations` | `GET /admin/marketing/get-recommendations?product_id=1&type=cross-sell` | — | 200 | реальные товары-рекомендации, корректная структура | **ok** |
| `Marketing::actionCampaigns` | `GET /admin/marketing/campaigns` | — | 200 | рендер (демо-данные, БД не участвует) | **ok** |
| `Seo::actionIndex` | `GET /admin/seo/index` | — | 200 | статистика редиректов/meta без ошибок | **ok** |
| `Seo::actionRedirects` | `GET /admin/seo/redirects` | — | 200 | листинг без ошибок | **ok** |
| `Seo::actionRedirectEdit` (create) | `POST /admin/seo/redirect-edit` | form `Redirect[from_url/to_url/type/is_active]` | 302 | строка создана в `redirect` с точными значениями | **ok** |
| `Seo::actionRedirectEdit` (update) | `POST /admin/seo/redirect-edit?id=1` | form (изменены все поля) | 302 | строка обновлена, `updated_at` пересчитан | **ok** |
| `Seo::actionRedirectDelete` | `POST /admin/seo/redirect-delete?id=1` | `_csrf` | 302 | строка удалена; `GET` на этот же route — 405 (VerbFilter из CMP-418 жив) | **ok** |
| `Seo::actionBulkMeta` (product) | `POST /admin/seo/bulk-meta` | `SeoMetaBulkForm[entity_type]=product` + 3 шаблона | 200 | все 30 активных товаров получили новые `meta_title/description/keywords` (кириллица цела); `Обновлено: 30` совпало с фактом в БД | **ok** |
| `Seo::actionBulkMeta` (category) | то же, `entity_type=category` | 200 | все 4 активные категории обновлены; счётчик `4` совпал с фактом | **ok** |
| `Seo::actionSitemap` | `POST /admin/seo/sitemap`, `regenerate=1` | — | 200 | `frontend/web/sitemap.xml` перегенерирован (43 URL), флэш теперь виден (см. фикс №5) | **ok** |
| `Seo::actionRobots` | `POST /admin/seo/robots` | `robots_content=...` | 200 | `frontend/web/robots.txt` перезаписан точным содержимым | **ok** |
| `Seo::actionUpdateProductMeta` | `POST /admin/seo/update-product-meta` | form `id/field/value` (как шлёт `updateProductMeta()`) | 200 / 200 (guard) | `meta_title` товара #1 изменён; запрещённое поле (`price`) и несуществующий товар корректно отбиты | **ok** |
| `Seo::actionAltTexts` | `GET /admin/seo/alt-texts` | — | **до фикса: 500**; после: 200 | `product_image.alt_text` не существовал в схеме — страница ALT-текстов была недоступна в принципе | **wrong_data → исправлено (критично)** |
| `Seo::actionUpdateImageAlt` | `POST /admin/seo/update-image-alt` | form `id/alt_text` (как шлёт `updateImageAlt()`) | **до фикса: 500**; после: 200 | тот же дефект — `$image->alt_text = ...` падал на несуществующем атрибуте AR | **wrong_data → исправлено (критично, тот же фикс)** |
| `Email::actionSend` | `POST /admin/email/send?orderId=5&template=confirmed_and_paid` | — | 200, `success:false` | все 5 маппингов шаблонов (`order_confirmed`, `order_paid`, `order_shipped`, `order_local_delivery`, `order_delivered`) указывают на несуществующие файлы в `backend/shared/mail/` (там `order-created.php` и т.п., через дефис, другие имена) — экшен гарантированно 100%-ный no-op | **wrong_data, не исправлено — см. «Найдено, не исправлено»** |
| `Email::actionTest` | `POST /admin/email/test` | form `email/template` | 200 / 200 (guard) | реальный `.eml` создан с верным адресатом; пустой email корректно отбит | **ok** |
| `Email::actionTemplates` | `GET /admin/email/templates` | — | 500 | вьюха `backend/modules/admin/views/email/templates.php` физически отсутствует | **wrong_data, не исправлено — см. «Найдено, не исправлено»** |
| `Automation::actionIndex` | `GET /admin/settings/triggers` | — | 200 | листинг 6 живых триггеров | **ok** |
| `Automation::actionCreate` | `POST /admin/settings/triggers/create` | form `name/event_code/priority/is_active/cond_*/act_*` (как шлёт `_form.php`) | **до фикса: сохранялось, но JSON бился**; после: 302 | строка создана; `conditions`/`actions` — теперь настоящий `JSON_TYPE()=ARRAY`, а не `STRING` | **wrong_data → исправлено (см. дефект №1)** |
| `Automation::actionCreate` (без действий) | то же, без `act_type[]` | 302 (после фикса — корректно отбито) | триггер НЕ создан (`required` теперь реально требует ≥1 действие) | **ok (побочное улучшение, см. дефект №1)** |
| `Automation::actionUpdate` | `POST /admin/settings/triggers/{id}/edit` | form, все поля изменены | 302 | `name/event_code/priority/is_active/conditions/actions` — все обновлены верно, JSON корректен | **ok** |
| `Automation::actionDelete` | `POST /admin/settings/triggers/{id}/delete` | `_csrf` | 302 | строка удалена | **ok** |
| `Automation::actionToggle` | `POST /admin/settings/triggers/{id}/toggle` | `_csrf` | 200 | `is_active` `0→1` подтверждено; `GET` на тот же route — 405 (VerbFilter CMP-418 жив) | **ok** |
| `Automation::actionLog` | `GET /admin/settings/triggers/log` | — | 200 | рендер журнала без ошибок | **ok** |
| `Notification::actionIndex` | `GET /admin/notification/index` (AJAX) | — | 200 | `{"count":3}` — точно совпадает с реальным числом заказов `status='new'` | **ok** (но см. «Найдено, не исправлено» — экшен ничем не вызывается) |
| `Notification::actionIndex` (браузер) | `GET /admin/notification/index` (не-AJAX) | — | 302 → `/admin/activity-log/index` | редирект, не мутирует | **ok** |
| `Loyalty::actionIndex` | `GET /admin/loyalty/index` | — | **до фикса: 500**; после: 200 | `render('//loyalty/index')` резолвился в orphaned клиентскую вьюху `frontend/views/loyalty/index.php` (без нужных переменных), а не в реально существующую админскую `backend/modules/admin/views/loyalty/index.php` | **wrong_data → исправлено (критично)** |
| `SidebarMenu::actionIndex` | `GET /admin/sidebar-menu/index` | — | 200 | листинг 4 живых пунктов | **ok** |
| `SidebarMenu::actionView` | `GET /admin/sidebar-menu/view?id=1` | — | 200 | рендер без ошибок | **ok** |
| `SidebarMenu::actionCreate` | `POST /admin/sidebar-menu/create` | form `SidebarMenuItem[...]` | 302 | строка создана с точными значениями | **ok** |
| `SidebarMenu::actionUpdate` | `POST /admin/sidebar-menu/update?id=N` | form (все поля изменены) | 302 | строка обновлена | **ok** |
| `SidebarMenu::actionToggle` | `POST /admin/sidebar-menu/toggle?id=N` | `_csrf` | 302 | `is_active` `1→0` подтверждено; `GET` — 405 | **ok** |
| `SidebarMenu::actionSort` | `POST /admin/sidebar-menu/sort` | form `items=` + `JSON.stringify([...])` (как шлёт drag&drop в admin-settings.js) | **до фикса: 500 (дважды, два независимых бага)**; после: 200 | до фикса: `sort_order` не менялся НИ РАЗУ ни при одном реальном drag&drop; после: пересчитан верно по новому порядку | **wrong_data → исправлено (критично, 2 бага, см. дефект №2)** |
| `SidebarMenu::actionDelete` | `POST /admin/sidebar-menu/delete?id=N` | `_csrf` | 302 | строка удалена | **ok** |
| `Feedback::actionIndex` | `GET /admin/feedback/index`, `?status=replied` | — | 200 | листинг + фильтр по статусу работают верно | **ok** |
| `Feedback::actionReply` | `POST /admin/feedback/reply` | form `id/reply_text/_csrf` (как шлёт `sendReply()` inline-скрипта) | 200 (и 2 guard-кейса) | `reply_text/replied_at/status/is_read` записаны верно; реальный `.eml` создан; пустой текст и несуществующий id корректно отбиты; отзыв без email клиента — сохраняется с точным предупреждающим сообщением | **ok** |
| `Feedback::actionDelete` | `POST /admin/feedback/delete?id=N` | `_csrf` | 302 | строка удалена | **ok** |

## Найденные дефекты и фиксы

### 1. `AutomationController::save()` — двойное JSON-кодирование `conditions`/`actions` во ВСЕХ триггерах без исключения

Колонки `automation_trigger.conditions`/`.actions` — нативный MySQL-тип
`JSON`. Yii2 (`yii\db\mysql\ColumnSchema::dbTypecast()`) сам оборачивает
**любое** присваиваемое значение в `JsonExpression` и кодирует его при записи.
Контроллер же присваивал уже готовую `json_encode()`-строку
(`$model->conditions = json_encode($conditions, JSON_UNESCAPED_UNICODE);`) —
в итоге колонка получала JSON дважды закодированным: `JSON_TYPE(conditions)`
возвращал `'STRING'` вместо `'ARRAY'`, а сохранённое значение было не
массивом, а JSON-строкой, содержащей экранированный JSON.

Живым прогоном подтверждено на всех 6 существовавших триггерах и на тестовой
записи — 100% строк, когда-либо созданных через админку, были повреждены на
этом уровне. Приложение не падало только потому, что
`AutomationTrigger::getConditionsArray()`/`getActionsArray()` и
`AutomationEngine::checkConditions()`/`executeActions()` на всякий случай сами
вызывают `json_decode()` при чтении — это компенсировало ровно один уровень
двойного кодирования (реальный дефолтный `phpTypecast()` JSON-колонки в Yii2
уже сам вызывает `json_decode()` один раз при чтении, а сверху ещё один
decode в бизнес-коде — итоговая цепочка «encode → dbTypecast-encode →
phpTypecast-decode → business-decode» случайно давала правильный результат в
памяти, но НЕ в самой БД). Итог: `JSON_EXTRACT`/`JSON_CONTAINS` по этим
колонкам не работали бы, а любой будущий рефакторинг, который уберёт «лишний»
`json_decode()` в бизнес-коде как избыточный (разумное предположение — колонка
и так `JSON`), тихо сломает систему автоматизации целиком.

**Фикс**:
- `AutomationController::save()` (`backend/modules/admin/controllers/AutomationController.php`)
  — теперь присваивает `$model->conditions`/`$model->actions` PHP-массивом
  напрямую, без пред-кодирования; typecast нативной JSON-колонки кодирует его
  ровно один раз.
- `AutomationTrigger::rules()` (`backend/modules/automation/models/AutomationTrigger.php`)
  — `[['conditions', 'actions'], 'string']` заменено на `'safe'` (оба поля
  никогда не грузятся через `load($_POST)`, только присваиваются напрямую в
  контроллере, риска mass-assignment нет).
- Миграция `m260925_081500_fix_double_encoded_automation_trigger_json`
  разово нормализует уже существующие в БД строки:
  `UPDATE automation_trigger SET conditions = CAST(JSON_UNQUOTE(conditions) AS JSON) WHERE JSON_TYPE(conditions) = 'STRING'`
  (аналогично для `actions`). Применена, подтверждено: все 6 строк теперь
  `JSON_TYPE() = 'ARRAY'`.

Побочный эффект фикса (осознанный, задокументирован в реестре): поскольку
`actions` теперь настоящий PHP-массив, а не непустая строка `"[]"`, стандартный
`RequiredValidator` Yii2 теперь корректно отбивает попытку создать триггер без
единого действия (раньше `json_encode([])` = `"[]"` — непустая строка,
`required` пропускал такой триггер, и он тихо ничего не делал при срабатывании
события). Живым прогоном подтверждено: `POST /admin/settings/triggers/create`
без `act_type[]` теперь не создаёт строку (было бы создано раньше).

Подтверждено полным циклом live-прогона: create/update теперь пишут
семантически верный JSON-массив, `actionToggle`/`actionDelete`/`actionLog`
не затронуты и продолжают работать.

### 2. `SidebarMenuController::actionSort` — drag&drop сортировка меню была 100%-ным падением (два независимых наложенных бага)

Реальный вызывающий код — обработчик `dragend` в блоке
`/* -- sidebar-menu/index.php -- */` файла `admin-settings.js` — шлёт:
```js
fetch(sortUrl, {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-Token': getCsrfToken()},
    body: 'items=' + JSON.stringify(items)
});
```
то есть `items` приходит в `$_POST` как **одна JSON-строка**
(`'["3","1","2"]'`), а не как PHP-массив (тот получился бы только при
`items[]=3&items[]=1&...`).

**Баг А**: старый код делал `foreach ($this->request->post('items', []), ...)`
без проверки типа. На PHP 8 `foreach` по строке — не предупреждение (как было
в PHP 7), а фатальный `ErrorException`
(«foreach() argument must be of type array|object, string given»). Живым
прогоном воспроизведено точным JS-payload'ом — гарантированный 500 при каждом
реальном drag&drop, `sort_order` не менялся никогда.

**Баг Б (независимый, вскрылся сразу после фикса бага А)**: сигнатура метода
объявлена как `public function actionSort(): \yii\web\Response`, а тело
всегда `return ['success' => true];` — простой массив. Это не Yii-специфика
(Yii сам умеет превращать возвращённый массив в JSON-ответ через
`Response::format`), а фатальное несовпадение **типов на уровне самого PHP**:
строгий return type языка бросает `TypeError` при возврате массива вместо
объявленного `Response`. То есть даже после починки парсинга `items` экшен
всё равно падал 500 на `return` — баг существовал с момента написания метода,
независимо от бага А.

**Фикс**: `$items = $this->request->post('items', []); if (is_string($items)) { $items = json_decode($items, true) ?: []; }`
перед `foreach`; убран некорректный return type у `actionSort()` (аналогично
соседним JSON-экшенам этого же контроллера/проекта без явного return type).
Подтверждено полным живым циклом с точным JS-payload'ом:
`POST items=["3","1","2","4"]` → `{"success":true}`, `sort_order` пересчитан
верно (`3→0, 1→10, 2→20, 4→30`). Тестовые данные возвращены к исходному
порядку.

### 3. `SeoController::actionAltTexts`/`actionUpdateImageAlt` — управление ALT-текстами изображений было недоступно в принципе (схема-дрифт)

`product_image` никогда не имел колонки `alt_text` (в миграции создания базовых
таблиц её нет), хотя `ProductImage`-документация, `SeoController::actionAltTexts()`
и `actionUpdateImageAlt()`, а также вьюха `seo/alt-texts.php` и JS
`updateImageAlt()` в `admin-settings.js` — всё написано в расчёте на этот
атрибут. Живым прогоном подтверждено: `GET /admin/seo/alt-texts` и
`POST /admin/seo/update-image-alt` оба стабильно падали 500
(`Unknown column 'alt_text' in field list` / `UnknownPropertyException` — у AR
нет такого атрибута, раз нет колонки). Тот же класс бага, что и в CMP-410/417
(«код написан под колонку, которая никогда не создавалась»).

**Фикс**: миграция `m260925_080000_add_alt_text_to_product_image` добавляет
`product_image.alt_text VARCHAR(255) NULL`. Применена. Подтверждено: обе
страницы теперь 200, `updateImageAlt()` реально пишет значение (кириллица
цела), несуществующее изображение и пустой `id` корректно отбиты.

### 4. `MarketingController::actionAbandonedCarts` — «Показать все» на живой странице маркетинга вела на гарантированный 500

`marketing/index.php` рендерит ссылку `Url::to(['marketing/abandoned-carts'])`,
как только брошенных корзин ≥ 10 — но вьюхи
`backend/modules/admin/views/marketing/abandoned-carts.php` физически не
существовало (в директории только `index.php`, `campaigns.php`,
`recommendations.php`). Живым прогоном подтверждено: `ViewNotFoundException`
→ 500.

**Фикс**: создана `backend/modules/admin/views/marketing/abandoned-carts.php`
по образцу блока «Брошенные корзины» из `marketing/index.php` (те же KPI,
список карточек, кнопки `sendReminder()`/`sendBulkReminders()` — уже
существующий и рабочий JS переиспользован без изменений). Подтверждено:
`GET /admin/marketing/abandoned-carts` → 200.

### 5. `LoyaltyController::actionIndex` — страница программы лояльности в админке рендерила чужую (клиентскую, orphaned) вьюху и падала 500

```php
public function actionIndex()
{
    return $this->render('//loyalty/index');
}
```
Синтаксис `//viewname` в Yii2 — «абсолютный путь от корня view-приложения»,
резолвится в `frontend/views/loyalty/index.php` — это клиентская страница
программы лояльности, которую при этом **не рендерит ни один контроллер
фронтенда** (grep по всему `frontend/controllers` не нашёл ни одного вызова
`'loyalty/index'`, `frontend/controllers/LoyaltyController.php` не существует)
— то есть сама эта вьюха orphaned и ожидает переменную `$info`, которую никто
никогда не передаёт. Обычный относительный `render('index')` резолвился бы в
уже существующую и полностью рабочую
`backend/modules/admin/views/loyalty/index.php` (7 КБ, реальная страница
настроек программы лояльности — под неё написан весь JS `saveLoyaltySettings()`
в `admin-settings.js`, уже проверенный в CMP-467 через
`SettingsController::actionSaveLoyalty`).

Живым прогоном подтверждено: `GET /admin/loyalty/index` → **500**
(`Undefined variable $info` в `frontend/views/loyalty/index.php:18`) — админская
страница программы лояльности была недоступна на 100%.

**Фикс**: `render('//loyalty/index')` → `render('index')`. Подтверждено:
200, рендерится реальная админская страница с формой `saveLoyaltyBtn`.

### 6. Layout `admin.php` — session flash нигде не рендерился (~75+ мест по всем admin-контроллерам, включая экшены этой волны)

При проверке `actionSitemap`/`actionRobots` обнаружилось: оба экшена
корректно пишут `Yii::$app->session->setFlash('success', ...)`
(«Sitemap сгенерирован...», «robots.txt обновлён»), но ни на одной странице
это сообщение не появлялось. Проверка показала: `backend/modules/admin/views/layouts/admin.php`
(единственный реально используемый layout — `BaseAdminController::$layout = 'admin'`,
`main.php` нигде не подключается ни одним контроллером) **вообще не содержит
кода рендера flash** — ни `getFlash`, ни `hasFlash`, ни `getAllFlashes`. При
этом `grep -rn "setFlash" backend/modules/admin/controllers` находит **75
вызовов**. Локальный рендер flash есть только в 4 отдельных вьюхах
(`category/index.php`, `brand/index.php`, `procurement/create.php`,
`procurement/create-return.php`) — остальные ~70 вызовов, включая
`SeoController::actionRedirectEdit/actionRedirectDelete/actionSitemap/actionRobots`,
`AutomationController::save()/actionDelete()`,
`SidebarMenuController::actionCreate/actionUpdate/actionDelete`, теряются
молча. Тот же класс бага, что и остальные находки этой волны: экшен реально
отрабатывает (факт в БД/файле верный), но админ не получает никакой обратной
связи об успехе/ошибке — неотличимо от «ничего не произошло».

**Фикс**: в `admin.php` перед `<?= $content ?>` добавлен централизованный
рендер `Yii::$app->session->getAllFlashes()` (Bootstrap-алерты с
`Html::encode()`, dismiss-кнопка, маппинг `success/error/danger/warning/info`
на классы `alert-*`) — по той же схеме, что уже используется локально в
`category/index.php`. Проверено на регрессию: `category/index.php` (со своим
локальным рендером) не показывает дублей, т.к. `getFlash($key)` там вызывается
раньше (при рендере `$content`) и по умолчанию удаляет флэш из сессии до того,
как отработает layout; `getAllFlashes()` по умолчанию НЕ удаляет флэши
(`$delete=false`) — это штатное поведение Yii2 для layout-уровневого рендера,
т.к. счётчик авто-протухания флэша не зависит от того, читали его или нет.
Подтверждено живым прогоном: `actionSitemap`/`actionRobots` теперь реально
показывают «Sitemap сгенерирован: 43 URL...» / «robots.txt обновлён».

### 7 (бонус, тот же файл). Колокольчик уведомлений в шапке — фильтр по несуществующему статусу заказа

Тот же `admin.php`, соседний блок (глобальный «колокольчик» в шапке,
единственный реально работающий индикатор новых заказов для админа — см.
«Найдено, не исправлено» про `NotificationController`) фильтровал заказы по
`status = 'created'`. Проверка `order_status`-справочника и реального кода
создания заказа (`OrderService.php:62` — `$order->status = Order::STATUS_NEW;`,
`STATUS_NEW = 'new'`) показала: статус `'created'` **никогда не присваивается
ни одному заказу** — существует только как неиспользуемая строка в
справочнике. Живым прогоном подтверждено: при 3 реальных необработанных
заказах (`status='new'`) счётчик и список в колокольчике были стабильно
пустыми на каждой странице админки.

**Фикс**: `'created'` → `'new'` в обоих запросах (счётчик и список из 5
последних). Подтверждено: дропдаун теперь показывает 3 реальных новых заказа.
(Бейдж-счётчик на самой иконке остался `0`, т.к. у него отдельный фильтр
«младше 24 часов» — все 3 заказа старше; это отдельное, самостоятельное
поведение по дизайну, не трогал.)

## Найдено, не исправлено

- **`EmailController::actionSend` — все 5 маппингов шаблонов битые, экшен
  недостижим из UI.** `$templates = ['confirmed_and_paid' => 'order_confirmed', 'paid' => 'order_paid', 'international_delivery' => 'order_shipped', 'local_delivery' => 'order_local_delivery', 'delivered' => 'order_delivered']`
  — ни один из этих 5 файлов (`order_confirmed.php`, `order_paid.php` и т.д.)
  не существует в `backend/shared/mail/` (там реально лежат
  `order-created.php`, `order-created-manager.php`, `order-created-text.php`,
  `order-tracking-link.php`, `tracking-update.php`, `return-approved.php`,
  `return-rejected.php`, `return-completed.php`, `password-reset.php`,
  `abandoned-cart.php`, `catalog-inquiry-*.php`, `payment-uploaded*.php` — ни
  подчёркивания вместо дефисов, ни этих смысловых имён). Живым прогоном
  подтверждено: `POST /admin/email/send?orderId=5&template=confirmed_and_paid`
  →
  `{"success":false,"message":"The view file does not exist: .../order_confirmed.php"}`.
  Дополнительно: ни один JS-файл и ни одна вьюха во всём проекте не вызывают
  `/admin/email/send` (grep по `frontend/web/js` и `backend/modules/admin/views`
  — ноль совпадений) — экшен полностью недостижим из живого UI. Не чинил,
  т.к. правильный фикс требует продуктового решения: либо переписать маппинг
  под реально существующие 10 mail-шаблонов (но ни один из них семантически
  не соответствует «paid»/«delivered» — под них пришлось бы **писать новый
  контент писем**, это не техническая правка), либо решить, что фича
  дублирует уже рабочий `SettingsController::actionTestEmail`
  (проверен в CMP-467) и `EmailController` целиком — мёртвый легаси-код,
  подлежащий удалению.
- **`EmailController::actionTemplates` — 500, вьюха отсутствует, экшен
  недостижим из UI.** `backend/modules/admin/views/email/` не существует
  вообще (нет `templates.php`). Ни одна вьюха/меню/JS не ссылаются на
  `/admin/email/templates` (только совпадения по строке `'confirmed_and_paid'`
  как значению статуса заказа в других контроллерах — не вызовы этого route).
  Функционально дублирует уже рабочий и проверенный в CMP-467
  `SettingsController::actionEmailTemplates`/`actionSaveEmailTemplate`. Не
  чинил — то же продуктовое решение, что и выше (легаси-контроллер к
  удалению, или отдельная фича с собственным UI — решает совет).
- **`NotificationController::actionIndex` реализован верно, но ничем не
  вызывается — мёртвый код.** Собственный докблок контроллера утверждает:
  «Опрашивается каждые 30 секунд через AJAX (admin.js)». Проверка `admin.js`
  показала обратное: реальный поллинг (`setInterval(fetchNotifications, 30000)`)
  бьёт в **другой** маршрут — `/admin/order/notifications` (принадлежит
  `OrderController`, вне периметра этой волны) — и ищет элемент
  `document.getElementById('notif-badge')`, которого не существует ни в одном
  layout (есть только `<span class="admin-notif-badge">`, без `id`) — то есть
  `initNotifications()` сама выходит по `if (!notifBadge) return;` до
  `setInterval`, и клиентский поллинг вообще никогда не стартует. Реальный
  счётчик/список в шапке — полностью отдельный, захардкоженный в
  `admin.php`-layout PHP-блок (см. фикс №7 выше, тот же файл, который я уже
  правил в рамках этой волны). Итог: **три параллельные, независимо
  реализованные системы «новых заказов для админа»** — рабочая, но мёртвая
  (`NotificationController`, моя зона), рабочая после фикса №7, но без
  поллинга (layout-блок), и мёртвая из-за id-рассинхрона (`OrderController` +
  JS, вне зоны). Не трогал `OrderController`/`admin.js` — за пределами
  периметра этой волны (Marketing/SEO/Email/Automation/Notification/Loyalty/
  SidebarMenu/Feedback) и, вероятно, уже покрыт другой параллельной под-волной
  аудита по `Order*`. Вопрос совету: какую из трёх систем оставить, остальные
  удалить, чтобы не путать будущих разработчиков.
- **CSRF отключён по шаблону `beforeAction` в нескольких контроллерах** — та
  же уже задокументированная в CMP-467 существующая заметка/TODO по
  JSON-AJAX-эндпоинтам; не новая находка, не трогал.

## Честная граница охвата

- `EmailController::actionSend`/`actionTemplates` проверены живым HTTP
  (реальные запросы к работающему серверу, реальная БД), но **не
  «исправлены»** — оба документированы выше как находки с открытым
  продуктовым вопросом, а не «код проверен без вызова» (в отличие от
  честной границы CMP-467 по `test-telegram`/`test-moysklad`/`test-amocrm` —
  там решение было не бить по боевым внешним API; здесь же оба экшена и так
  никуда не стучатся — сама попытка их вызвать безопасна и была выполнена).
- Остальные 36 экшенов проверены полным циклом: живой HTTP + сверка MySQL/
  файла до/после, включая явное воспроизведение точных payload'ов из
  реального вызывающего JS/HTML-формы.

## Итого волны

- **38 действий проверено живым прогоном** (7 Marketing + 10 Seo + 3 Email +
  6 Automation + 1 Notification + 1 Loyalty + 7 SidebarMenu + 3 Feedback).
- **7 дефектов найдено и исправлено**, четыре из них — критичные (страница/
  функция была на 100% недоступна для реального пользователя):
  1. `AutomationController::save()` — двойное JSON-кодирование `conditions`/
     `actions` во всех когда-либо созданных триггерах (данные в БД
     структурно неверны, работало только благодаря случайно компенсирующим
     друг друга decode-слоям); плюс попутно исправлен смежный баг, из-за
     которого можно было создать полностью бездействующий триггер без единого
     действия.
  2. `SidebarMenuController::actionSort` — drag&drop сортировка бокового меню
     на 100% падала 500 при каждом реальном использовании (два независимых
     наложенных бага: `foreach` по JSON-строке вместо массива + невозможный
     `return` массива при объявленном `: \yii\web\Response`).
  3. `SeoController::actionAltTexts`/`actionUpdateImageAlt` — управление
     ALT-текстами изображений было недоступно в принципе (схема-дрифт,
     колонка `product_image.alt_text` никогда не существовала).
  4. `MarketingController::actionAbandonedCarts` — реальная ссылка «Показать
     все» на живой странице маркетинга вела на гарантированный 500
     (отсутствующая вьюха).
  5. `LoyaltyController::actionIndex` — страница программы лояльности в
     админке была на 100% недоступна: рендерила чужую orphaned клиентскую
     вьюху вместо существующей рабочей админской (500, `Undefined variable`).
  6. Layout `admin.php` — session flash не рендерился НИГДЕ (~75 мест по всем
     admin-контроллерам, включая минимум 5 экшенов этой волны) — экшены
     реально отрабатывали, но админ не получал никакой обратной связи.
  7. (бонус, тот же файл) Колокольчик уведомлений в шапке фильтровал по
     несуществующему статусу заказа `'created'` вместо `'new'` — реальные
     новые заказы никогда не попадали в список.
- **3 находки без фикса** — вынесены с открытыми продуктовыми вопросами:
  `EmailController::actionSend` (все 5 маппингов шаблонов битые, экшен
  недостижим из UI, чинить нечем без нового контента писем или решения
  снести контроллер), `EmailController::actionTemplates` (500, вьюха
  отсутствует, дублирует уже рабочий `SettingsController`), а также
  системная избыточность трёх параллельных реализаций «уведомлений о новых
  заказах» (одна из них — `NotificationController`, моя зона, но
  единственная реально вызывающая её сторона лежит за пределами периметра
  этой волны).
- Тестовые данные полностью откачены и подтверждены `SELECT`/`diff`:
  `cart` → 0 строк (было 0), `redirect` → 0 строк (было 0), `feedback` → 0
  строк (было 0), `sidebar_menu` → исходные 4 строки (побайтово идентичны
  снятому до теста бэкапу), `product`/`category` meta-поля — побайтово
  идентичны бэкапу для всех 30+4 активных записей, `product_image.alt_text`
  тестовой строки — обратно `NULL`, `frontend/web/sitemap.xml`/`robots.txt` —
  побайтово (md5) идентичны состоянию на момент старта волны, `runtime/mail/*.eml`
  — удалены. `automation_trigger` намеренно **не** откачен к «повреждённому»
  состоянию — миграция-фикс нормализации JSON оставлена применённой
  (это и есть исправление бага, а не тестовый мусор), итоговое состояние —
  исходные 6 строк с семантически верным содержимым.

## Проверенные действия (список)

**MarketingController:**
1. `actionIndex` — ok
2. `actionAbandonedCarts` — **fixed** (см. дефект №4)
3. `actionSendReminder` — ok
4. `actionSendBulkReminders` — ok
5. `actionRecommendations` — ok
6. `actionGetRecommendations` — ok
7. `actionCampaigns` — ok

**SeoController:**
8. `actionIndex` — ok
9. `actionRedirects` — ok
10. `actionRedirectEdit` (create + update) — ok
11. `actionRedirectDelete` — ok
12. `actionBulkMeta` (product + category) — ok
13. `actionSitemap` — ok (флэш теперь виден, см. дефект №6)
14. `actionRobots` — ok (флэш теперь виден, см. дефект №6)
15. `actionUpdateProductMeta` — ok
16. `actionAltTexts` — **fixed** (см. дефект №3, критично)
17. `actionUpdateImageAlt` — **fixed** (см. дефект №3, критично)

**EmailController:**
18. `actionSend` — **не исправлено** (см. «Найдено, не исправлено»)
19. `actionTest` — ok
20. `actionTemplates` — **не исправлено** (см. «Найдено, не исправлено»)

**AutomationController:**
21. `actionIndex` — ok
22. `actionCreate` — **fixed** (см. дефект №1)
23. `actionUpdate` — **fixed** (см. дефект №1)
24. `actionDelete` — ok
25. `actionToggle` — ok
26. `actionLog` — ok

**NotificationController:**
27. `actionIndex` — ok (код верный, но мёртвый — см. «Найдено, не исправлено»)

**LoyaltyController:**
28. `actionIndex` — **fixed** (см. дефект №5, критично)

**SidebarMenuController:**
29. `actionIndex` — ok
30. `actionView` — ok
31. `actionCreate` — ok
32. `actionUpdate` — ok
33. `actionDelete` — ok
34. `actionToggle` — ok
35. `actionSort` — **fixed** (см. дефект №2, критично, 2 бага)

**FeedbackController:**
36. `actionIndex` — ok
37. `actionReply` — ok
38. `actionDelete` — ok
