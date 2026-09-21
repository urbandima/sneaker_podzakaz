# CMP-417 — живой HTTP POST на купоны, отзывы, тарифы и size-grid (admin)

Дата: 2026-09-22. Окружение: локальный `php -S 127.0.0.1:8765 -t frontend/web router.php`,
БД `cmp410_e2e_clean` (MySQL 8, strict SQL mode). Родитель: CMP-413 (живой HTTP-прогон нашёл
13 битых GET-маршрутов, но POST-действия с реальными данными не проверялись).

## Метод

`scripts/cmp417-http-client.php` (готовый клиент из инфраструктуры тикета — curl с ручным
cookie-jar, потому что `secure`-куки `_csrf`/`_identity-admin` не переживают обычный
curl-cookiejar по `http://`). Логин `admin/admin123`, затем реальные `POST` с кириллическими
данными на `CouponController`, `ReviewController`, `TariffController`, `SizeGridController`.
После каждого шага — `SELECT` в БД (`--default-character-set=utf8mb4`) для проверки
целостности кириллицы под strict SQL mode. `product_review` был пуст — тестовый отзыв
создавался прямым `INSERT` перед проверкой `publish`/`unpublish`/`respond`/`delete`, удалён
после (через `actionDelete`).

Побочная находка по инфраструктуре теста (не баг приложения, тот же класс, что уже отмечен
в `CMP-417-products-report.md`): `generate-code` (Coupon) и `add-item` (SizeGrid) не рендерят
HTML со свежим `<meta name="csrf-token">` на GET (JSON-ответ либо редирект), поэтому
автоподхват CSRF в `postForm()` не срабатывает — обходится явной передачей `_csrf`,
снятого с реально отрендеренной страницы.

## Результаты по маршрутам

| Маршрут | Метод | Статус до | Статус после |
|---|---|---|---|
| `/admin/coupon/create` | POST | 302 (работало) | 302 |
| `/admin/coupon/update/<id>` | POST | 302 (работало) | 302 |
| `/admin/coupon/toggle/<id>` | POST | 302 (работало) | 302 |
| `/admin/coupon/delete/<id>` | POST | 302 (работало) | 302 |
| `/admin/coupon/generate-code` | POST (AJAX) | 200 (работало) | 200 |
| `/admin/review/publish/<id>` | POST | **500** (баг №1) | 302 |
| `/admin/review/unpublish/<id>` | POST | **500** (баг №1) | 302 |
| `/admin/review/respond` (HTML fallback) | POST | **500** (баг №1) | 302 |
| `/admin/review/delete/<id>` | POST | 302 (работало) | 302 |
| `/admin/tariff/create` | POST | 302 (работало) | 302 |
| `/admin/tariff/update/<id>` | POST | 302 (работало) | 302 |
| `/admin/tariff/toggle/<id>` | **GET** (не должен работать) | **302 — GET менял `is_active` без CSRF** (баг №3) | 405 |
| `/admin/tariff/toggle/<id>` | POST | 302 (работало) | 302 |
| `/admin/tariff/delete/<id>` | POST | 302 (работало) | 302 |
| `/admin/size-grid/create` | POST | **500** (баг №2) | 302 |
| `/admin/size-grid/update` | POST | **500** (баг №2) | 302 |
| `/admin/size-grid/add-item` | POST | 302 (работало) | 302 |
| `/admin/size-grid/delete-item` | POST | 302 (работало) | 302 |
| `/admin/size-grid/delete` | POST | 302 (работало) | 302 |

Проверено: кириллические название/описание/код купона (`Летняя скидка Тест`,
`ТЕСТ2026КМП417`), кириллический ответ администрации на отзыв (`Спасибо за ваш отзыв! ...`),
кириллическое название тарифа (`Тариф Доставка КМП417`) и сетки размеров (`Тестовая сетка
размеров КМП417`, лейбл размера `42 РУ`) — во всех случаях сохранились в БД байт-в-байт.

## Найдено и исправлено 3 бага

### Баг №1 — `ProductReview::publish()/unpublish()/addAdminResponse()` не существовали + `updated_at` без колонки

`ReviewController::actionPublish($id)`, `actionUnpublish($id)` и HTML-фолбэк
`actionRespond()` вызывают `$model->publish()`, `$model->unpublish()`,
`$model->addAdminResponse($resp)` — этих методов не было в модели `ProductReview`
(единственные методы модели — геттеры связей). **Модерация и ответы администрации на
отзывы были на 100% сломаны** (валился любой из трёх маршрутов).

Дополнительно: даже после добавления методов первая попытка фикса упала повторно —
`ProductReview::behaviors()` использовал дефолтный `TimestampBehavior` (пишет и
`created_at`, и `updated_at`), но в схеме `product_review` колонки `updated_at` нет
(только `created_at`, `int`). Это ронятло **любой** `save()` модели — и insert, и update —
с `UnknownPropertyException`, независимо от добавленных методов.

Репро (из `runtime/logs/app.log`, до фикса):
```
yii\base\UnknownMethodException: Calling unknown method: app\backend\modules\catalog\models\ProductReview::publish()
#0 backend/modules/admin/controllers/ReviewController.php(147): yii\base\Component->__call('publish', Array)
```
```
yii\base\UnknownPropertyException: Setting unknown property: app\backend\modules\catalog\models\ProductReview::updated_at
#0 vendor/yiisoft/yii2/behaviors/AttributeBehavior.php(133): yii\db\BaseActiveRecord->__set('updated_at', ...)
#8 backend/modules/catalog/models/ProductReview.php(133): yii\db\BaseActiveRecord->save(false, Array)
#9 backend/modules/admin/controllers/ReviewController.php(159): app\backend\modules\catalog\models\ProductReview->unpublish()
```

Фикс (`backend/modules/catalog/models/ProductReview.php`):
- `behaviors()`: `TimestampBehavior` теперь с `'updatedAtAttribute' => false`.
- Добавлены методы `publish()`, `unpublish()`, `addAdminResponse(?string $response)`,
  зеркалящие уже существующую логику `actionModerate()` (переключение `is_published`,
  запись `admin_response`/`admin_response_at`).

Тест: `testProductReviewPublishTogglesIsPublishedAndPersists`,
`testProductReviewUnpublishTogglesIsPublishedAndPersists`,
`testProductReviewAddAdminResponsePersistsCyrillicText`,
`testProductReviewPlainUpdateDoesNotCrashOnMissingUpdatedAtColumn` в
`tests/unit/Cmp417CouponReviewTariffSizeGridAdminTest.php`.

### Баг №2 — `SizeGrid::beforeSave()` писал несуществующую колонку `slug`

`SizeGrid::beforeSave()` выставлял `$this->slug = Inflector::slug(...)`, но в таблице
`size_grid` колонки `slug` никогда не было (проверено по
`infrastructure/migrations/m251104_230000_create_size_grid_tables.php` — там её нет, и по
живому `DESCRIBE size_grid`). **`actionCreate` и `actionUpdate` для размерных сеток были
на 100% сломаны** — любое сохранение падало.

Репро (из `runtime/logs/app.log`, до фикса):
```
yii\base\UnknownPropertyException: Setting unknown property: app\backend\modules\catalog\models\SizeGrid::slug
#1 backend/modules/catalog/models/SizeGrid.php(70): yii\db\BaseActiveRecord->__set('slug', '417')
#2 vendor/yiisoft/yii2/db/ActiveRecord.php(623): app\backend\modules\catalog\models\SizeGrid->beforeSave(true)
#6 backend/modules/admin/controllers/SizeGridController.php(115): yii\db\BaseActiveRecord->save()
```

Фикс (`backend/modules/catalog/models/SizeGrid.php`): удалён весь `beforeSave()` (мёртвый
код на несуществующую колонку) и неиспользуемый `use yii\helpers\Inflector;`. Удалена
устаревшая запись `phpstan-baseline.neon` для `Access to an undefined property
SizeGrid::$slug` — она маскировала именно эту ошибку и стала неактуальной после фикса
(phpstan иначе ругается на непарный ignore).

Тест: `testSizeGridCreateAndUpdateDoNotCrashOnMissingSlugColumn` (insert + update) в
`tests/unit/Cmp417CouponReviewTariffSizeGridAdminTest.php`.

### Баг №3 — `TariffController::actionToggle` менял `is_active` через голый GET без CSRF

`TariffController::behaviors()['verbs']` ограничивал методом `POST` только `delete` — в
отличие от `CouponController`, где `toggle` тоже защищён `['POST']`. Yii2 не проверяет CSRF
для `GET`/`HEAD`/`OPTIONS` (`csrfTokenSafeMethods`), поэтому простой `GET
/admin/tariff/toggle/<id>` (без токена, например через `<img src>` на стороннем сайте,
пока залогинен админ) реально переключал `is_active` и отдавал `302`.

Подтверждено живым запросом: `GET /admin/tariff/toggle/6` без CSRF → `302`, `is_active`
сменился `1 -> 0` в БД.

Фикс (`backend/modules/admin/controllers/TariffController.php`): добавлено
`'toggle' => ['POST']` в `VerbFilter`, выравнено с `CouponController`. После фикса
`GET` на этот маршрут отдаёт `405 Method Not Allowed`.

Тест: `testTariffControllerToggleActionIsRestrictedToPost` (проверяет конфигурацию
`VerbFilter` контроллера) в `tests/unit/Cmp417CouponReviewTariffSizeGridAdminTest.php`.

## Изменённые файлы

- `backend/modules/catalog/models/ProductReview.php` — `updatedAtAttribute => false` +
  методы `publish()`/`unpublish()`/`addAdminResponse()`.
- `backend/modules/catalog/models/SizeGrid.php` — удалён `beforeSave()` с обращением к
  несуществующей колонке `slug`, убран неиспользуемый импорт `Inflector`.
- `backend/modules/admin/controllers/TariffController.php` — `toggle` ограничен `POST`.
- `phpstan-baseline.neon` — удалена ставшая неактуальной запись для
  `SizeGrid::$slug` (маскировала баг №2).
- `tests/unit/Cmp417CouponReviewTariffSizeGridAdminTest.php` — новый регрессионный тест
  (6 тестов, все на реальном `save()`/DB, не только `validate()`).

`CouponController` не потребовал изменений — все проверенные маршруты (`create`, `update`,
`delete`, `toggle`, `generate-code`) уже работали корректно (see CMP-410 fix history).

## Результат прогона тестов

```
vendor/bin/phpunit tests/unit/Cmp417CouponReviewTariffSizeGridAdminTest.php
...... 6 / 6 (100%)
Tests: 6, Assertions: 18, OK
```

Полный набор `tests/unit` (150 тестов) прогнан для проверки регрессий: 4 ошибки, все в
`tests/unit/Cmp416PostScenariosTest.php` (не мой файл, не тронутые мной контроллеры/views —
это результат параллельной работы другого агента на CMP-416, `views/partials/footer.php` не
существует). Мои файлы и `Cmp410SchemaDriftTest`/`CouponTest` — зелёные, без изменений в
их результатах.

`phpcs --standard=PSR12` и `phpstan analyse -l 5` — 0 ошибок на всех 3 изменённых
production-файлах (были только 4 предсуществующих warning "line exceeds 120 characters",
не в изменённых мной строках).

## Открытые вопросы (продуктовые решения, не угадывал)

1. **`ReviewController::actionToggleFeatured` и `actionModerate`** имеют тот же паттерн
   отсутствия `VerbFilter`-ограничения на `POST`, что и найденный баг №3 в
   `TariffController::actionToggle` (GET реально исполняет запись). Они не входили в
   список тестируемых в этом тикете маршрутов, поэтому не тронуты — но стоит завести
   отдельный тикет на аудит всех `admin/*` контроллеров на этот паттерн (GET, у которого
   VerbFilter не ограничивает метод, а само действие безусловно вызывает `save()`).
2. **`SizeGridController::actionAddItem`** также не ограничен `VerbFilter` на `POST` (в
   отличие от `delete-item`), но это не эксплуатируемо: `$item->load(Yii::$app->request->post())`
   на `GET`-запросе всегда возвращает `false` (нет `$_POST`), поэтому `save()` не
   вызывается — GET безопасен по факту, просто непоследовательно с остальными действиями.
   Не фиксил, т.к. не баг, а стилистическая непоследовательность вне списка тикета.
3. **`ProductReview.status`** (`'pending'`/`'rejected'`/…) и `is_published` — два
   параллельных источника истины для статуса модерации: `actionModerate()`/`publish()`/
   `unpublish()` пишут только `is_published`, `status` остаётся `'pending'` навсегда для
   любого отзыва, кроме созданных явно с другим значением. Фильтр `index` по
   `status='rejected'` в `ReviewController::actionIndex()` (админка) в реальности никогда
   не находит отклонённые отзывы. Не факт, что это баг в scope CMP-417 (не входил в список
   тестируемых действий), но стоит решить на продуктовом уровне: либо `status` синхронно
   обновлять из `publish()`/`unpublish()`, либо убрать фильтр «отклонённые» из UI как
   мёртвый.
