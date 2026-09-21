# CMP-413 — сплошной HTTP-прогон маршрутов

Дата: 2026-09-22. Окружение: локальный `php -S 127.0.0.1:8765 -t frontend/web router.php`, БД `cmp410_e2e_clean` (тот же сетап, что в CMP-410).

## Метод

1. `scripts/route-inventory.php` — статически перечисляет все `public function action*`
   во всех web-контроллерах (frontend, `backend/modules/*`, `api/controllers`).
   Console-контроллеры (`backend/console`, `console`) исключены — это CLI, не HTTP.
   Результат: **539 action-методов в 72 контроллерах** →
   `docs/route-audit/CMP-413-inventory.csv`.
2. `scripts/route-sweep.php` — живой GET-запрос по каждому маршруту на поднятый
   сервер: admin-сессия (реальный логин `admin/admin123`) для `admin/*`,
   анонимная — для остального. Плейсхолдеры `<id:\d+>` и т.п. заполняются
   реальными ID из БД (order id=5, product id=1) либо `1` по умолчанию.
   Результат → `docs/route-audit/CMP-413-sweep-results.csv`.

Оба скрипта закоммичены в репозиторий и рассчитаны на повторный прогон перед
каждым деплоем (`php scripts/route-inventory.php > docs/route-audit/CMP-413-inventory.csv && php scripts/route-sweep.php`).

## Итоги по статус-кодам (539 маршрутов, GET)

| Модуль | 200 | 3xx | 400/404/405/422 | 401/403 | 500 | 503 |
|---|---|---|---|---|---|---|
| admin | 65 | 313 | 35 | – | 0 | – |
| (default, frontend) | 27 | 10 | 12 | – | 0 | – |
| catalog | 22 | 2 | 6 | – | 0 | – |
| account | 4 | 16 | – | – | 0 | – |
| api | 6 | – | 1 | 5 | 0 | 2* |
| checkout | 3 | – | 4 | – | 0 | – |
| compare | 5 | – | – | – | 0 | – |
| cart | – | 1 | – | – | 0 | – |

\* оба 503 — намеренное fail-closed поведение `WebhookController::actionAmocrm/actionLeadStatusChanged`,
когда `AMOCRM_WEBHOOK_SECRET` не задан в окружении (см. «Не баги» ниже). Не найдено ни одного
настоящего 500 после фиксов.

## Найдено и исправлено: 13 живых 500 (все — «код, который никогда не исполнялся», класс CMP-410)

| # | Маршрут | Метод | До | Причина | Фикс |
|---|---|---|---|---|---|
| 1 | `GET /api/doc` | GET | 500 | `api/views/doc/swagger.php` не существовал (каталог `api/views` отсутствовал целиком) | создан view (Swagger UI) |
| 2 | `GET /admin/activity-log/export-csv` | GET | 500 | `->asArray()` на обычном `yii\db\Query` (не ActiveQuery) — метода нет | убран лишний вызов |
| 3 | `GET /admin/amo-crm/settings` | GET | 500 | `settings.php` никогда не создавался; контроллер нигде не выведен в меню, дублирует `admin/plugin/amocrm` | `actionSettings()` → redirect на рабочую страницу |
| 4 | `GET /admin/analytics/conversion` | GET | 500 | `conversion.php` не существовал (в отличие от `conversions.php`, plural) | создан view |
| 5 | `GET /admin/analytics/sales` | GET | 500 | `sales.php` не существовал | создан view; заодно исправлен `getTopCategories()`: `DATE(o.created_at)` на unix-timestamp давало `NULL` (см. «Побочные находки») |
| 6 | `GET /admin/analytics/export` | GET | 500 | `fputcsv()` без `$escape` — PHP 8.4 deprecation конвертируется Yii ErrorHandler в исключение | добавлен `$escape` во все затронутые `fputcsv()` (5 файлов) |
| 7 | `GET /admin/analytics/export-orders` | GET | 500 | то же (общий `exportCsv()`) | тот же фикс |
| 8 | `GET /admin/analytics/export-customers` | GET | 500 | то же | тот же фикс |
| 9 | `GET /admin/analytics/export-products` | GET | 500 | SQL: `p.status` — колонки нет, у `product` только `is_active` | запрос переписан на `IF(is_active,...)` |
| 10 | `GET /admin/category/create` | GET | 500 | мёртвая строка-заглушка `$model->addRule ? null : null; // no-op` в `_form.php` | строка удалена |
| 11 | `POST /admin/customer/mark-phantoms` | POST | 500 | SQL: `is_active` — колонки нет, у `customer` только `status` | `SET status = :inactiveStatus` (`Customer::STATUS_INACTIVE_DB`) |
| 12 | `GET /catalog/product` (без `slug`) | GET | 500 | `HttpCache`-behavior дёргает `findProduct(null)` ДО биндинга параметров действия → `TypeError` в `ProductRepository::findBySlug()` | guard на `null`/`''` → 400/404 вместо краша |
| 13 | `GET /blog`, `GET /blog/<slug>` | GET | 500 | `$this->params[...]` в контроллере — у `Controller` нет `$params` (это свойство `View`); ничем не читалось (в layout нет виджета Breadcrumbs) | мёртвые строки удалены |

Каждый пункт — регрессионный тест в `tests/unit/Cmp413RouteSweepTest.php` (10 тестов,
формат `Cmp410SchemaDriftTest.php`). Дополнительно превентивно (тот же класс бага,
не наблюдался в сборе т.к. `actionExport` в ProductController по умолчанию формирует
xlsx, а не csv) исправлены `fputcsv()`-вызовы в `ProductController::exportToCsv()`.

## Побочная находка (логическая, не 500)

`AnalyticsController::getTopCategories()` — `WHERE DATE(o.created_at) BETWEEN :from AND :to`
на `created_at` (unix timestamp) молча возвращает 0 строк (`DATE()` от целого числа — не дата).
Исправлено на `DATE(FROM_UNIXTIME(o.created_at))`, как во всех соседних запросах того же файла.

## Не баги (проверено и сознательно оставлено как есть)

- `POST /webhook/amocrm/event`, `POST /webhook/amocrm/lead-status-changed` → 503
  «Webhook endpoint not configured» — намеренный fail-closed при отсутствующем
  `AMOCRM_WEBHOOK_SECRET` в окружении. В проде секрет задан — не воспроизводится.

## Что НЕ покрыто этим прогоном и почему

Это первый **автоматический** прогон: живой GET по каждому маршруту, без входа
под покупателем и без реальных POST-данных. Он целенаправленно ловит класс багов
CMP-410 (код падает при первом же обращении), но НЕ покрывает:

1. **`account/*` под реальной сессией покупателя** — все 16 маршрутов личного
   кабинета вернули 302 (redirect на логин), т.к. тестовый покупатель не
   создавался и не логинился. Сами actions (orders, profile, wishlist,
   loyalty, save-passport) не выполнялись.
2. **POST-формы с кириллицей в MySQL strict mode** — регистрация, вход,
   восстановление пароля, отправка отзыва/вопроса, применение купона,
   полный путь корзина → чекаут → заказ. Именно эти два класса багов
   (schema drift + кириллица/strict mode) дали 2 из 3 находок CMP-405/410 —
   их нужно гонять отдельно, не в рамках одного автоматического GET-прохода.
3. **Admin create/update/delete по основным сущностям** — сканер сделал только
   GET на `*/create` (формы открываются, 500 не было), но не отправлял реальный
   POST с кириллическими данными для create/update/delete товаров, заказов,
   покупателей, категорий, брендов, купонов, отзывов и т.д.
4. **Поиск, фильтры каталога, пагинация** — проверены только базовые GET-страницы
   (`/catalog`, `/search`), не все комбинации фильтров/страниц.

Эти четыре пункта — предметно объёмная ручная/полу-автоматическая работа
(создание тестовых сущностей, стейтфул-сценарии), вынесены в дочерние задачи
[CMP-416](/CMP/issues/CMP-416) (покупательские POST-сценарии) и
[CMP-417](/CMP/issues/CMP-417) (админские CRUD POST-сценарии), а не досказаны
здесь угадыванием.

## Проверка

- `phpunit`: 126/126 зелёных (было 116 в CMP-410, +10 в `Cmp413RouteSweepTest.php`).
- `phpcs` (phpcs.xml): 0 ошибок на всех изменённых файлах.
- `phpstan` (level 5 + baseline): 0 ошибок на всех изменённых файлах; из
  baseline убраны 4 записи, которые перестали совпадать (баги исправлены, а не
  задокументированы очередным ignore-паттерном), взамен добавлены только
  заведомо some-стилевые записи `$this might not be defined` — тот же паттерн,
  что уже стоит в baseline для каждого другого view-файла админки (Yii не
  типизирует `$this` как `View` в контексте `render()`).
