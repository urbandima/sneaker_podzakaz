# CMP-420 — живой прогон всех console-команд (cron)

Дата: 2026-09-22. Окружение: `php yii <route>` напрямую (не веб-сервер), БД
`cmp410_e2e_clean` (тот же сетап, что в CMP-410/413), MySQL 9.5,
`sql_mode=ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,
ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION` (эквивалент MySQL 8 strict).

## Метод

Console-контроллеры живут в двух физических местах с одним и тем же
`controllerNamespace` (`app\console\controllers`, `infrastructure/config/console.php`):
`console/controllers/` (7 файлов) и `backend/console/controllers/` (13 файлов на
момент старта, 12 после этого прогона — один удалён как мёртвый код, см. ниже).
Реестр собран вручную по `grep "public function action"` по обоим каталогам —
итог 19 контроллеров / 41 action до фиксов, 40 после удаления
`AssetController`.

**Важная предпосылка, которую этот прогон опроверг**: `php yii help`
показывает только команды из `console/controllers/` — Yii резолвит список
для листинга через `getControllerPath()` → `Yii::getAlias('@app/console/controllers')`,
то есть буквальный путь на диске от `controllerNamespace`, а не через
автозагрузку по namespace. Все 12 контроллеров в `backend/console/controllers/`
**физически невидимы для `php yii help`**, хотя при прямом вызове маршрута
(`php yii sitemap/status`) исполняются нормально (кроме случаев ниже, где не
исполнялись вообще). Любой, кто использует `help` для инвентаризации cron-кандидатов
(как в CMP-413 использовался `scripts/route-inventory.php` для HTTP), увидит
только половину реальной поверхности.

**Cron нигде не настроен.** Ни в репозитории, ни в документации нет реального
crontab. `docs/PRODUCTION_READINESS.md` содержит невыполненный пункт
чек-листа деплоя `[ ] Set up cron: php yii tracking/update-all (every 30 min)`
— и такой команды **не существует в кодовой базе вообще** (нет `TrackingController`).
Комментарии вида «Designed to run from cron every 5–15 minutes»
(`MoySkladSyncController`) и «Add to crontab: … currency/update» (`CurrencyController`)
— это задокументированное намерение, не факт. Единственная команда, реально
за что-то автоматически дёргаемая, — `elasticsearch/reindex` внутри
`scripts/deploy-production.sh` (при деплое, не по расписанию).

## Реестр и результаты

| Команда | Cron (задокументировано/предполагалось) | Результат живого прогона | Вердикт |
|---|---|---|---|
| `currency/update` | Да (докблок: `crontab: 0 */4 * * *`) | Реальный запрос к внешнему API курсов, успех | ок |
| `amocrm/list-mapped` | Нет (ручной CLI) | Успех (пусто в тестовой БД) | ок |
| `amocrm/check-lead` | Нет | `amocrm`-компонент отсутствовал в `console.php` → `UnknownPropertyException` | **починено** (добавлен компонент) |
| `amocrm/sync-lead` | Нет | То же самое (общий компонент) | починено (тот же фикс) |
| `elasticsearch/create-index` | Нет (ручной) | ES недоступен → корректный fail-closed, без краша | ок |
| `elasticsearch/index-all` | Нет | Индексация 0/N, но всегда `ExitCode::OK` | **починено**: exit code теперь отражает полный провал |
| `elasticsearch/reindex` | Да (в deploy-скрипте) | Fail-closed на `create-index`, корректный exit code | ок |
| `elasticsearch/index-product` | Нет | Fail-closed без краша | ок |
| `lamoda/parse` | Нет (ручной, требует `--url`) | `scripts/parse_lamoda.php` падал на `env()` до сети — 100% крашей | **починено** |
| `order/migrate-statuses-to-tracks` | Нет (одноразовый backfill) | Успех, идемпотентно (0 при повторном запуске) | ок |
| `production/optimize-images` | Нет (ручной) | `yii\imagine\Image` — класс никогда не был установлен → 100% крашей | **починено** (переписано на GD) |
| `production/create-webp` | Нет | То же самое | **починено** (тот же фикс) |
| `production/clear-cache` | Нет | Отсутствие `use Yii;` → `Class app\console\controllers\Yii not found` | **починено** |
| `production/cache-stats` | Нет | Работал (случайно не задет багом с `Yii::`) | ок |
| `production/health-check` | Нет | Крашился (тот же `use Yii;`); при исправлении обнаружен второй баг: `$allOk` всегда `true` | **починено** (оба бага) |
| `rotate-auth-keys` | Нет (security-gated, запуск только по решению совета CMP-352) | dry-run по умолчанию, безопасно, работает | ок |
| `characteristic/import` | Нет | Работает позиционно; докблок с `--file=` был неверным | **починено** (докблок) |
| `characteristic/bulk-assign` | Нет | Аналогично | **починено** (докблок) |
| `clean-sizes/cm` | Нет | Успех, идемпотентно | ок |
| `clean-sizes/stats` | Нет | Успех (read-only) | ок |
| `generator/create` | Нет (dev-инструмент) | Успех | ок |
| `moy-sklad-sync/sync` | Да (докблок: «every 5–15 minutes») | Namespace-баг делал команду **вообще недостижимой** из CLI; после фикса — реальный HTTP 400 от MoySklad API из-за неверного заголовка `Accept` | **починено** (namespace + компоненты + `Accept`-заголовок + advisory-lock) |
| `moy-sklad-sync/push-all` | Предположительно (сопутствует sync) | Аналогично | **починено** (тот же набор фиксов) |
| `moy-sklad-sync/push-order` | Нет (ручной, по ID) | Не гонялся live отдельно (тот же `request()`, что и sync/push-all) | починено тем же фиксом |
| `moy-sklad-sync/setup-webhook` | Нет | Namespace-баг + тот же `Accept`-баг | починено тем же фиксом |
| `moy-sklad-sync/status` | Нет (read-only) | Namespace-баг делал недостижимой; после фикса — успех | **починено** (namespace) |
| `parser/poizon` | Нет (легаси-скрапер poizonshop.ru) | Реальный HTTP-запрос, 0 товаров (селекторы не совпадают с текущим HTML сайта) | не баг этого класса — устаревший скрапер, см. «Непокрытое» |
| `poizon-import/run` | Похоже на предполагаемый cron (доки описывают как основной импорт) | Fail-closed: «API импорт не настроен» (креды не заданы, ожидаемо) | ок (fail-closed) |
| `poizon-import/update-prices` | Похоже на cron-кандидат | Успех (пересчитал 0 записей — в тестовой БД нет `poizon_price_cny`) | ок |
| `poizon-import/update-sizes` | Похоже на cron-кандидат | Отсутствие `use yii\helpers\Console;` → краш; **баг был замаскирован 3 записями в `phpstan-baseline.neon`** | **починено** + baseline очищен |
| `poizon-import/logs` | Нет (read-only) | Успех | ок |
| `poizon-import/test` | Нет | Fail-closed: «Poizon XML URL not configured» | ок |
| `poizon-import/from-file` | Нет | Fail-closed на отсутствующем файле | ок |
| `poizon-import-json/run` | Нет (ручной, требует URL) | Fail-closed на недоступном URL | ок |
| `product/create-test-products` | Нет (dev-инструмент) | Namespace-баг делал недостижимой; после фикса — 3 независимых бага schema drift (materal/season/gender enum, `ProductImage::url` read-only, несуществующие колонки `ProductSize`, NOT NULL `characteristic_id`) | **починено** (все 4) |
| `round-prices/run` | Нет (ручной) | Успех, идемпотентно | ок |
| `sitemap/generate` | Похоже на cron-кандидат (не задокументировано явно) | Namespace ок (уже был правильным), успех | ок |
| `sitemap/status` | Нет (read-only) | Успех | ок |
| `test/create-test-order` | Нет (dev-инструмент, устарел по духу — воспроизводит уже пофикшенный в CMP-410 путь) | Успех (после CMP-410) | ок |
| `test/check-system` | Нет | 4/5 — email-шаблоны всегда «0/4», проверка указывала на несуществующий legacy-путь `@app/mail/*` | **починено**: путь `@backend/shared/mail/*` |
| `webp/convert` | Похоже на cron/deploy-кандидат (единственный action без обязательных аргументов) | Список директорий по умолчанию (`web/uploads`, `web/images`) не существовал (нет префикса `frontend/`) — **всегда обрабатывал 0 файлов**, репортил успех | **починено** |
| `webp/convert-dir` | Нет (ручной, принимает путь) | Успех | ок |
| ~~`asset/minify`, `asset/clear-cache`~~ | — | Навсегда перекрыты встроенной командой Yii `asset` (`yii\console\controllers\AssetController` в `coreCommands()`); функциональность заменена рабочим Gulp-пайплайном | **удалено** как мёртвый код |

## Найдено и исправлено: 15 настоящих багов (класс CMP-410/413 — «код, который никогда не исполнялся» или исполнялся неправильно)

| # | Файл | Баг | Фикс |
|---|---|---|---|
| 1 | `infrastructure/config/console.php` | Компонент `redis` не зарегистрирован (только в `web.php`) → `php yii help` и все `production/*` крашились | добавлен `redis` |
| 2 | `infrastructure/config/console.php` + `web.php` | `'timeout' => 0.5` — несуществующее свойство `yii\redis\Connection` в установленной версии 2.1.2 (переименовано в `connectionTimeout`/`dataTimeout`) | `connectionTimeout` в обоих конфигах |
| 3 | `infrastructure/config/console.php` | Компонент `amocrm` не зарегистрирован | добавлен |
| 4 | `infrastructure/config/console.php` | Компоненты `moysklad`/`moyskladClient` не зарегистрированы | добавлены |
| 5 | `infrastructure/config/console.php` | `mailer.viewPath` = устаревший `@app/mail` (в `web.php` давно `@app/backend/shared/mail`) | синхронизировано |
| 6 | `backend/console/controllers/AssetController.php` | Навсегда перекрыт встроенной командой `asset`, функциональность заменена Gulp | удалён |
| 7 | `backend/console/controllers/MoySkladSyncController.php` | `namespace app\backend\console\controllers` не совпадал с `controllerNamespace` консоли → команда физически не резолвилась (`Unknown command: moy-sklad-sync`) | namespace исправлен на `app\console\controllers`; докблок с неверным `moysklad-sync/...` исправлен на реальный `moy-sklad-sync/...` |
| 8 | `backend/console/controllers/ProductController.php` | Та же namespace-ошибка → `product/create-test-products` недостижим | namespace исправлен |
| 9 | `backend/console/controllers/ProductController.php` | `material`/`season`/`gender` заполнялись русскими словами, а `Product::rules()` требует slug'и (`leather`, `summer`, `male`, …) — 100% провал валидации | значения приведены к реальному enum'у |
| 10 | `backend/console/controllers/ProductController.php` | Запись в read-only `ProductImage::url` (реальная колонка — `image`), плюс несуществующие `alt`/`position` | переписано на `image`/`sort_order` |
| 11 | `backend/console/controllers/ProductController.php` | `ProductSize` заполнялся несуществующими колонками (`size_eu`, `size_us`, `stock_quantity`, `sku`); отсутствовала обязательная `size` | переписано на реальную схему (`size`, `eu_size`, `us_size`, `uk_size`, `stock`) |
| 12 | `backend/console/controllers/ProductController.php` | `ProductCharacteristicValue.characteristic_id` (NOT NULL) выставлялся в `null`; несуществующая колонка `value` (реальная — `value_text`) | find-or-create реальной `Characteristic` + `value_text` |
| 13 | `console/controllers/ElasticsearchController.php` | `index-all`/`reindex` всегда возвращали `ExitCode::OK`, даже при 100% провале индексации | exit code теперь `UNSPECIFIED_ERROR`, если `failed>0 && success===0` |
| 14 | `scripts/parse_lamoda.php` | Не подключал `infrastructure/config/bootstrap.php` → `env()` не определена → падение до первого сетевого вызова | добавлен `require bootstrap.php` |
| 15 | `console/controllers/ProductionController.php` | Отсутствовал `use Yii;` → `Yii::$app` резолвился в несуществующий `app\console\controllers\Yii` (`health-check`, `clear-cache all`) | добавлен `use Yii;` |
| 16 | `console/controllers/ProductionController.php` | `in_array('❌', $checks)` никогда не совпадал (реальные значения — `'❌ Error'`) → `health-check` всегда репортил «всё ок» | `in_array('❌ Error', $checks, true)`; `catch (\Exception)` → `catch (\Throwable)` |
| 17 | `infrastructure/services/ImageOptimizationService.php` | Зависел от `yii\imagine\Image` — класса из `yiisoft/yii2-imagine`, который никогда не добавлялся в `composer.json`/не устанавливался → `optimize-images`/`create-webp` падали на 100% запусков | переписано на встроенный GD (уже используется в `WebpController`), проверено на реальных изображениях |
| 18 | `backend/console/controllers/WebpController.php` | `$directories` по умолчанию — `web/uploads`/`web/images` без префикса `frontend/` (устарело после реструктуризации 2026) → `webp/convert` без аргументов **всегда обрабатывал 0 файлов**, репортил успех | пути исправлены на `frontend/web/uploads`/`frontend/web/images` |
| 19 | `backend/console/controllers/CharacteristicController.php` | Докблоки документировали `--file=`/`--char=`/`--value=`/`--brand=` — Yii2 не поддерживает именованные опции для параметров метода action (только для публичных свойств контроллера); буквальный запуск команды из докблока падал с `Unknown option` | докблоки исправлены на реальный (позиционный) синтаксис |
| 20 | `backend/shared/components/MoyskladClient.php` | Хардкод `Accept: application/json` — MoySklad API требует `application/json;charset=utf-8` и отвечает 400 иначе; ломало **любой** запрос к МойСклад независимо от кредов | заголовок исправлен; баг воспроизведён и подтверждён против реального API MoySklad |
| 21 | `backend/console/controllers/TestController.php` | `checkEmailTemplates()` проверял устаревший путь `@app/mail/*`; реальные шаблоны — `@backend/shared/mail/*` (та же миграция путей, что и баг #5) → `test/check-system` навсегда репортил «0/4 шаблонов» | путь исправлен |
| 22 | `backend/console/controllers/PoizonImportController.php` | `actionUpdateSizes()` использовал `Console::FG_CYAN` без `use yii\helpers\Console;` → `Class app\console\controllers\Console not found`; **баг был замаскирован тремя записями в `phpstan-baseline.neon`, а не исправлен** | добавлен `use yii\helpers\Console;`; 3 мёртвые записи baseline удалены |

Идемпотентность / защита от параллельного запуска: ни у одной пишущей команды
не было `Mutex`/файловой блокировки. Добавлен компонент `mutex`
(`yii\mutex\FileMutex`, zero-dependency, часть ядра Yii2) и advisory-lock
вокруг `moy-sklad-sync/sync` и `moy-sklad-sync/push-all` — единственной пары
команд, явно задокументированной для повторяющегося cron-запуска (5–15 мин);
без лока параллельный запуск мог дважды отправить один и тот же
несинхронизированный заказ в МойСклад. Остальные пишущие команды (`elasticsearch/index-all`,
`poizon-import/run`, `sitemap/generate`, `round-prices/run`) без cron-обвязки
и с естественной идемпотентностью (перезапись тем же результатом) — лока не
получили; риск низкий, отдельная защита не оправдана без реального cron.

## Не баги (ожидаемый fail-closed)

`amocrm/check-lead`, `amocrm/sync-lead` (после фикса компонента),
`elasticsearch/*` без ES-сервера, `poizon-import/run|test|from-file`,
`poizon-import-json/run` — все корректно проваливаются с понятным сообщением
до/на границе сети, без краша, без записи мусора в БД. Креды не заданы
намеренно (плейсхолдеры), это ожидаемое поведение для локального прогона.

## Непокрытое (с причиной)

- **`parser/poizon`** — легаси-скрапер `poizonshop.ru`, XPath-селекторы не
  совпадают с текущей вёрсткой сайта (0 товаров, не краш). Не «код, который
  никогда не исполнялся» в смысле CMP-410 — скорее устаревшая интеграция.
  Решение нужно от бизнеса: чинить селекторы (нужен реальный HTML сайта) или
  удалить как мёртвую функциональность — заводить отдельной задачей, не в
  рамках этого прогона.
- **`poizon-import/update-sizes`** — после фикса краша команда рабочая, но
  внутри — заглушка (`// В реальности здесь должен быть API запрос`), которая
  не делает никакого реального обращения к Poizon API и просто помечает все
  товары как «обработанные». Это ровно тот класс тихого ничегонеделания,
  который ищет CMP-420, но полноценная реализация требует контракта Poizon
  API, которого нет в этой среде — отдельная задача на реализацию, не на
  этот аудит.
- **`moy-sklad-sync/push-order`** не прогонялся живьём индивидуально (тот же
  `MoyskladClient::request()`, что и `sync`/`push-all`, тот же фикс применяется).
- Дальнейшие live-запуски `moy-sklad-sync/*` против реального API MoySklad
  не проводились сознательно — см. критическую находку по безопасности ниже.

## КРИТИЧЕСКАЯ НАХОДКА (эскалация, не в рамках фикса CMP-420)

`backend/shared/components/MoyskladClient.php::getAuth()` содержит захардкоженные
дефолтные креды для Basic-Auth к живому API МойСклад:
```php
$login    = Yii::$app->settings->get('moysklad', 'login', 'admin@sneakerculture');
$password = Yii::$app->settings->get('moysklad', 'password', 'NorTwe1534');
```
Это реальный секрет в исходном коде того же класса, что CMP-400 (утёкшие креды
в старых debug-логах) и CMP-352 (список на ротацию). Требуется: (1) ротация
пароля в самом МойСклад, (2) удаление хардкода из источника (значения только
через `settings`/`.env`, без дефолта), (3) добавление в список ротации CMP-352.
Не тронуто в этой задаче — только заголовок `Accept` (см. баг #20), сама
работа с кредами — решение уровня CEO/SecurityEngineer, не одностороннее
инженерное исправление в рамках cron-аудита. Заведён дочерний issue CMP-431
(без исполнителя: SecurityEngineer в компании нанят, но хайр ещё не утверждён
советом — назначение вернуло 409 «Cannot assign work to pending approval
agents»; требуется решение совета/CEO).

## Побочные находки по покрытию CI

- `console/controllers/*` (7 файлов, корень репозитория) исключены из
  `composer lint` (`phpcs --standard=PSR12 backend/ infrastructure/ commands/`)
  и из `phpstan.neon` (`paths: backend, frontend, api, infrastructure`) —
  ни один статический анализатор их не видел. Как следствие, баг #15/#16
  (`use Yii;`, `in_array('❌', …)`) жил в файле без единой phpcs/phpstan
  проверки с момента создания.
- `composer lint` уже был красным до этой задачи по несвязанной причине:
  `commands/` — путь, указанный в `composer.json`, не существует на диске
  (`ERROR: The file "commands/" does not exist`). Не исправлено (не входит в
  scope CMP-420), но задокументировано.
- `php yii help` не видит `backend/console/controllers/*` (см. «Метод» выше)
  — рекомендация: не полагаться на `help` при следующей инвентаризации,
  использовать `grep "public function action"` по обоим каталогам, как в этом
  прогоне.

## Регрессионные тесты

`tests/unit/Cmp420ConsoleSweepTest.php` (3 теста, 16 assertions):
- конфиг консоли регистрирует `redis`/`amocrm`/`moysklad`/`moyskladClient`/`mutex`
  с правильными классами и `connectionTimeout` (не `timeout`), `mailer.viewPath`
  указывает на `@app/backend/shared/mail`;
- `elasticsearch/index-all` возвращает ненулевой exit code при полном провале;
- `product/create-test-products` создаёт товар с валидными `material`/`season`/`gender`
  и корректным количеством связанных `product_image`/`product_size`/`product_characteristic_value`.

Проверка `production/health-check`-логики (баг #16) регрессионным тестом не
покрыта: контроллер безусловно требует компонент `redis` уже в `init()`
(через `ProductionCacheService`), а `tests/config.php` — общий конфиг для
всего test-suite и не тянет `redis`/`mutex` (в отличие от `console.php`),
расширять его ради одного теста — расширение поверхности тестовой
инфраструктуры непропорционально находке. Логика проверена живым прогоном
(см. таблицу выше, `production/health-check`).

## Проверка

- `vendor/bin/phpunit` (unit suite): 168/168 зелёных, включая новые 3 теста.
- `vendor/bin/phpcs --standard=PSR12` на все изменённые файлы в scope
  `composer lint` (`backend/`, `infrastructure/`): 0 ошибок (только
  пред-существующие warning'и о длине строк, не мои).
- `vendor/bin/phpstan analyse` (полный прогон, level 5): 0 ошибок вне
  несвязанных untracked-файлов `backend/modules/account/views/{wishlist.php,loyalty/,return/}`
  (чужая незакоммиченная работа в рабочей директории, не трогалось).
  `phpstan-baseline.neon`: **-8 мёртвых записей, +1 новая** (`$mutex` — та же
  устоявшаяся конвенция проекта для динамических Yii-компонентов, что уже
  применена к `$amocrm`/`$moysklad`/`$redis`) — baseline **сократился**, не вырос.
