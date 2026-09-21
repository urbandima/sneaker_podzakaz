# CMP-417 — живой HTTP POST на OrderController/CustomerController (create/update/change-status, update/adjust-points/add-note/update-tags)

Дата: 2026-09-22. Окружение: локальный `php -S 127.0.0.1:8765 -t frontend/web router.php`,
БД `cmp410_e2e_clean` (MySQL 8, strict mode). Родитель: CMP-413 (живой HTTP-прогон нашёл
13 битых GET-маршрутов, но POST-действия с реальными данными по заказам/покупателям не
проверялись — их закрывает этот тикет).

## Метод

`scripts/cmp417-http-client.php` (готовый клиент с ручным cookie-jar). Прогон: логин
`admin/admin123` → `GET /admin/order/create?product_id=&customer_id=` (создание черновика)
→ `POST /admin/order/<id>/update` кириллическими адресом/ФИО/комментарием → `POST
/admin/order/<id>/change-status` (`new → paid`, валидный переход по `OrderStateMachine`) →
`POST /admin/customer/<id>/update` кириллическими ФИО/адресом → `POST
/admin/customer/adjust-points` → `POST /admin/customer/add-note` → `POST
/admin/customer/update-tags`. После каждого шага — `SELECT` в БД
(`--default-character-set=utf8mb4`) на целостность кириллицы («ё», дефисы, апострофы,
эмодзи, длинный текст).

Товарные ID/customer ID для тестов брались реальными из БД (`SELECT id FROM product/
customer LIMIT …`), тестовые заказы/покупатели/заметки/теги/баллы удалены после каждого
прогона; итоговое состояние БД проверено — совпадает с состоянием до начала работы (кроме
применённой миграции, см. ниже).

## Результаты по маршрутам

| Маршрут | Метод | Статус до фикса | Статус после фикса |
|---|---|---|---|
| `/admin/order/create` | GET (создаёт черновик) | 302 (работало) | 302 |
| `/admin/order/<id>/update` | POST | **200 без redirect** (тихая потеря всех изменений — баг №1) | 302 |
| `/admin/order/<id>/change-status` | POST | 302 (работало) | 302 |
| `/admin/customer/<id>/update` | POST | 302 (работало) | 302 |
| `/admin/customer/adjust-points` | POST (JSON, id в теле) | 200 (работало — это единственный экшен баллов, чей контракт совпадает с формой в view.php) | 200 |
| `/admin/customer/add-note` (вызов как в реальном браузере: JSON `{id, text}`, без `?id=` в query) | POST | **400** «Отсутствуют обязательные параметры: id» (баг №2) | 200 |
| `/admin/customer/update-tags` (вызов как в реальном браузере: JSON `{id, action:'add'/'remove', tag}`) | POST | **400** «Отсутствуют обязательные параметры: id»; а после устранения id-бага — тихо **удаляло все теги клиента** вместо добавления одного (баг №3) | 200, инкрементальное добавление/удаление работает |
| `/admin/customer/add-points` / `/admin/customer/deduct-points` (вызов как в реальном браузере: JSON `{id, amount, comment}`) | POST | **400** «Отсутствуют обязательные параметры: id»; а после устранения id-бага — всегда читал 0 баллов (ключ `amount` игнорировался, читался только `points`) (баг №4) | 200, баллы начисляются/списываются верно |
| `/admin/customer/add-tag`, `/admin/customer/remove-tag` (вызов как в дублирующемся inline-скрипте `view.php`) | POST | **404** — метод `actionAddTag`/`actionRemoveTag` не существует | не тронуто, см. «Открытые вопросы» |

Кириллица проверена во всех успешных случаях байт-в-байт: ФИО с дефисами
(«Караткевіч-Ярашэвіч»), адрес с «ё» («посёлак Ёдкавічы»), кв./корп./№, комментарий с
кавычками, апострофами, эмодзи 🚀 и длинным (1024 символа) текстом заметки — везде MySQL 8
strict mode сохранил данные без обрубания и без искажения кодировки.

## Найдено и исправлено 4 живых бага + 1 инфраструктурный (таблицы)

### Баг №0 (инфраструктурный, блокирует №2 и №3): таблиц `customer_notes`/`customer_tags` не было ни в одной миграции

`CustomerController::actionAddNote()`/`actionUpdateTags()` читают/пишут в `{{%customer_notes}}`
и `{{%customer_tags}}` напрямую через `createCommand()->insert()/delete()` — обе таблицы
никогда не создавались ни одной миграцией. Каждый вызов ловился внутренним `try/catch` и
тихо возвращал `success:false` с текстом SQL-ошибки «Table … doesn't exist» — без видимого
500, баг был скрыт catch-блоком. Тот же класс бага, что и `catalog_inquiry` в CMP-410
(`m260921_141500_create_catalog_inquiry_table.php`).

**Фикс:** `infrastructure/migrations/m260922_100000_create_customer_notes_and_tags_tables.php`
— создаёт обе таблицы с FK на `customer(id) ON DELETE CASCADE`, применена на тестовой БД
(`php yii migrate`).

### Баг №1: `OrderController::actionUpdate()` удалял ВСЕ позиции заказа и откатывал всё изменение, если форма не редактировала состав

```php
$items = Yii::$app->request->post('OrderItem', []);   // было
$this->saveOrderItems($model, $items, true);
```

`saveOrderItems($model, [], true)` сначала `OrderItem::deleteAll(['order_id' => …])`
(удаляет существующие позиции), затем находит 0 непустых элементов в пустом массиве и
бросает `Необходимо добавить хотя бы один товар в заказ`. Исключение ловится, транзакция
откатывается целиком — а значит откатывается и уже выполненный `$model->save()` с
кириллическим адресом/ФИО/комментарием. Ответ — обычный `200` (рендер той же формы) с
малозаметным flash-сообщением, не 500 — баг был практически невидим при обычном
ручном тестировании.

Дополнительная находка при разборе: у активной вьюхи `backend/modules/admin/views/order/
view.php` НЕТ полноформенного `<form>`, отправляющего на `/admin/order/<id>/update` —
единственный живой путь редактирования полей в текущем UI — `actionUpdateField()`
(поштучный AJAX inline-edit). Полноформенные шаблоны с `#orderUpdateForm` (`view-new.php`,
`view-wizard.php`, `view-wizard-new.php`) существуют в репозитории, но их **не рендерит ни
один controller action** — то есть `actionUpdate()` в текущем виде маршрутно достижим (URL
существует), но не достижим кликом из активного UI. Баг всё равно реален и стоило
исправить: маршрут открыт (например, для внешних интеграций/старых закладок), а полный
form-POST — единственный контракт, которому он вообще может соответствовать.

**Фикс** (`backend/modules/admin/controllers/OrderController.php`):
```php
$items = Yii::$app->request->post('OrderItem');   // без дефолта []
if ($items !== null) {
    $this->saveOrderItems($model, $items, true);
}
```
Товары трогаются только если ключ `OrderItem` реально присутствует в POST. Также попутно
исправлена утечка транзакции: ранняя проверка `canChangeStatus()` делала `return` без
`$transaction->rollBack()`/`commit()`, оставляя транзакцию висящей до конца запроса —
добавлен явный `rollBack()`.

### Баг №2: `CustomerController::actionAddNote($id)` не мог получить `$id` от реального браузера

У компонента `request` (`infrastructure/config/web.php`) нет `parsers` для
`application/json`, поэтому `Yii::$app->request->post()` для JSON-тела всегда пуст
(без парсера `getBodyParams()` в Yii2 просто возвращает `$_POST`, а PHP не заполняет
`$_POST` для `application/json`). При этом **все** fetch-запросы `backend/web/js/
admin-customers.js` (`SH.fetch` из `utils.js`) шлют `Content-Type: application/json`, и
`addCustomerNote()` кладёт `id` в JSON-тело (`{id, text}`), а не в query-строку. Yii2
биндит параметры action-метода **только** из query (`yii\web\Request::resolve()`), поэтому
до фикса `actionAddNote($id)` падал с `400 «Отсутствуют обязательные параметры: id»` на
каждый реальный клик «Добавить заметку» в браузере.

**Фикс:** добавлен приватный хелпер `CustomerController::jsonOrPost($key, $default)`,
который декодирует raw JSON body (если валиден) и мерджит с `post()`; `actionAddNote($id = null)`
теперь резолвит `$id` через `jsonOrPost('id') ?: jsonOrPost('customer_id')`, если он не
пришёл в query.

### Баг №3: `CustomerController::actionUpdateTags($id)` — тот же id-баг + контракт не совпадал с реальным JS и **удалял все теги** при точечном добавлении

Тот же id-баг, что в №2. После его устранения выяснилось, что `admin-customers.js::addTag()/
removeTag()` шлёт `{id, action: 'add'|'remove', tag}` (точечная операция на один тег), а
`actionUpdateTags()` поддерживал только полную замену списка через ключ `tags` (массив).
При вызове с реальным контрактом `tags` в теле нет → читался как `[]` → метод удалял ВСЕ
существующие теги клиента и не добавлял ни одного — тихая потеря данных на каждый клик
«добавить тег», если бы id-баг был исправлен без этого.

**Фикс:** `actionUpdateTags($id = null)` теперь поддерживает оба контракта: `{action:
'add'|'remove', tag}` — точечная операция (insert/delete одной строки в `customer_tags`,
без удаления остальных), и `{tags: [...]}` — полная замена (старое поведение, для
пакетного сохранения). `$id` резолвится тем же способом, что и в №2.

### Баг №4: `CustomerController::actionAddPoints($id)`/`actionDeductPoints($id)` — тот же id-баг + читали `points`, а JS шлёт `amount`

`admin-customers.js::submitPoints()` шлёт `{id, amount, comment}` на `/admin/customer/
add-points` или `/deduct-points`. Оба action читали `Yii::$app->request->post('points', 0)` —
ключ не совпадает, поэтому даже после устранения id-бага баллы всегда читались как `0` и
запрос падал на проверке «Количество баллов должно быть больше 0», независимо от суммы,
реально введённой в форме.

**Фикс:** `actionAddPoints($id = null)`/`actionDeductPoints($id = null)` резолвят `$id`
как в №2/№3, и читают `jsonOrPost('points') ?: jsonOrPost('amount', 0)`.

## Открытый вопрос для продукта (не исправлено, требует решения)

В `view.php` покупателя одновременно существуют **два независимых JS-обработчика** для
кнопок тегов/заметок/баллов:

1. Inline `<script>` внизу `backend/modules/admin/views/customer/view.php` — вызывает
   `customer/add-tag`, `customer/remove-tag` (JSON `{customer_id, tag}`) и `customer/
   adjust-points` (JSON `{customer_id, points, comment}`).
2. `backend/web/js/admin-customers.js` (он же `frontend/web/js/admin-customers.js`) —
   вызывает `customer/update-tags` (JSON `{id, action, tag}`) и `customer/add-points`/
   `deduct-points` (JSON `{id, amount, comment}`).

Проверено по факту загрузки страницы (`GET /admin/customer/view?id=1`): скрипт
`admin-customers.js` подключается в разметке **позже** инлайн-скрипта → его `window.addTag
= function(...)`/`window.submitPoints = function(...)` перезаписывают одноимённые
объявления из инлайн-скрипта. Значит **в реальном браузере всегда выигрывает
`admin-customers.js`**, а код в инлайн-скрипте (включая вызовы `customer/add-tag`,
`customer/remove-tag`, `customer/adjust-points`) — мёртвый, никогда не исполняется. Это
тот же класс бага, что и CMP-410 («код, который никогда не исполняется»), но на уровне
JS, а не PHP — вне заявленной для этого тикета области правок (контроллеры/модели). Не
трогал `view.php`/`*.js`, чтобы не выйти за рамки CMP-417.

Продуктовое решение нужно по трём пунктам:
1. Удалить мёртвый инлайн-скрипт в `view.php` (или сознательно оставить как fallback?) —
   сейчас он создаёт впечатление рабочего кода, вызывающего несуществующие
   `actionAddTag`/`actionRemoveTag` (проверено: живой `POST /admin/customer/add-tag` → 404).
2. `CustomerController::actionAdjustPoints()` (эндпоинт, который явно назван в тикете
   CMP-417 для тестирования) вызывается только из мёртвого инлайн-скрипта — то есть в
   продакшене никогда не выполняется живым пользователем. Проверено отдельно (прямой
   вызов) — работает корректно, но не достижим кликом. Нужно решить: удалить, вернуть в
   строй (заменить `admin-customers.js`'s `add-points`/`deduct-points` на единый
   `adjust-points`), либо оставить как альтернативный API-путь.
3. `OrderController::actionUpdate()` (полноформенный POST, тоже явно в тикете) не
   достижим кликом из активного `view.php` — единственный живой путь редактирования полей
   заказа сейчас `actionUpdateField()` (поштучный inline-edit). Файлы `view-new.php`/
   `view-wizard.php`/`view-wizard-new.php` с полноформенным `#orderUpdateForm` существуют,
   но их не рендерит ни один controller action — предположительно недоделанный
   рефакторинг. Нужно решить: закончить переключение на wizard-шаблон, либо удалить мёртвые
   файлы, либо явно задокументировать `actionUpdate()`/`actionChangeStatus()` как
   API-only.

Малозначительная косметика (не фиксил, вне скоупа): `preg_replace('/[^а-яёА-ЯЁa-zA-Z0-9\s\-_]/u', ...)`
в `actionUpdateTags()` (код существовал до CMP-417) не пропускает белорусские буквы «і»/«ў»
(только русский алфавит + ё) — тег «VIP-кліент» сохраняется как «VIP-клент». Для
магазина sneaker-head.by (Беларусь) это может быть желательным расширить, но это не
регрессия этого тикета и не относится к заказам/адресам (там фильтрации нет вовсе).

## Изменённые файлы

- `backend/modules/admin/controllers/OrderController.php` — `actionUpdate()`: не трогать
  `OrderItem`, если ключа нет в POST; явный `rollBack()` на ранний `return` (баг №1).
- `backend/modules/admin/controllers/CustomerController.php` — добавлен `jsonOrPost()`;
  `actionAddNote()`, `actionUpdateTags()`, `actionAddPoints()`, `actionDeductPoints()`
  принимают `$id` из body/query, `actionUpdateTags()` поддерживает точечный `action`+`tag`
  (баги №2, №3, №4).
- `infrastructure/migrations/m260922_100000_create_customer_notes_and_tags_tables.php`
  (новый) — создаёт `customer_notes`/`customer_tags` (баг №0), применена на тестовой БД.
- `tests/unit/Cmp417OrderCustomerAdminTest.php` (новый) — 5 регрессионных тестов, все
  через прямой вызов `OrderController`/`CustomerController` (паттерн `Cmp413RouteSweepTest`).

## Тесты и статический анализ

```
vendor/bin/phpunit tests/unit/Cmp417OrderCustomerAdminTest.php   → 5/5 OK (31 assertions)
vendor/bin/phpunit tests/unit                                    → 155/155 OK (455 assertions), без регрессий
vendor/bin/phpcs --standard=PSR12 <изменённые файлы>              → 0 ERRORS (только строки >120 симв. — pre-existing стиль)
vendor/bin/phpstan analyse -l 5 <изменённые файлы>                 → No errors
```
(запуск миграции: `m260921_141500_create_catalog_inquiry_table.php` из CMP-410 — тот же
паттерн ошибок phpcs по имени класса миграции, не специфично для CMP-417.)

## Данные в БД после прогона

Все тестовые заказы/покупатели/заметки/теги/баллы, созданные живыми curl-прогонами,
удалены вручную SQL-ом; `tests/unit/Cmp417OrderCustomerAdminTest.php` чистит свои данные
сам в `tearDown()`. В БД осталась только применённая миграция (создание двух пустых
таблиц) и предсуществовавший заказ `#5` (сид-данные, не трогал).
