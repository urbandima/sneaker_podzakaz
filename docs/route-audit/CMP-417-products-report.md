# CMP-417 — живой HTTP POST на ProductController (create/edit/toggle/размеры/изображения/delete)

Дата: 2026-09-22. Окружение: локальный `php -S 127.0.0.1:8765 -t frontend/web router.php`,
БД `cmp410_e2e_clean` (MySQL 8, `sql_mode=…,STRICT_TRANS_TABLES,…`). Родитель: CMP-413
(живой HTTP-прогон нашёл 13 битых GET-маршрутов, но POST-действия с реальными данными не
проверялись — их закрывает этот тикет).

## Метод

`scripts/cmp417-http-client.php` (готовый клиент, curl с ручным cookie-jar — `secure`-куки
`_csrf`/`_identity-admin` не переживают обычный curl-cookiejar по `http://`). Прогон:
логин `admin/admin123` → `POST /admin/product/create` кириллическими данными → `POST
/admin/product/<id>/edit` → `POST /admin/product/<id>/toggle` ×2 → `POST
/admin/product/<id>/add-size` → `POST /admin/product/<id>/add-image` → `POST
/admin/product/size/<id>/delete` → `POST /admin/product/image/<id>/delete` → `POST
/admin/product/<id>/delete`. После каждого шага — проверка `SELECT` в БД
(`--default-character-set=utf8mb4`) на целостность кириллицы/спецсимволов.

Побочная находка по инфраструктуре теста (не баг приложения): действия `toggle` и
`add-image` никогда не рендерят HTML со свежим `<meta name="csrf-token">` на GET (сразу
редиректят), поэтому автоподхват CSRF-токена клиента (`postForm()` делает GET той же
ссылки) не срабатывает и curl получает `400 Bad Request` (CSRF validation failed).
Обходится получением токена с реально отрендеренной страницы (`GET
/admin/product/<id>` — карточка товара) перед такими запросами.

## Результаты по маршрутам

| Маршрут | Метод | Статус до фикса | Статус после фикса |
|---|---|---|---|
| `/admin/product/create` | POST | 302 (работало) | 302 |
| `/admin/product/<id>/edit` | POST | 302 (работало) | 302 |
| `/admin/product/<id>/toggle` | POST | 302 (работало, после починки CSRF в тест-клиенте) | 302 |
| `/admin/product/<id>/add-size` | POST | 302 (работало) | 302 |
| `/admin/product/<id>/add-image` | POST | **500** (SQL 1364, см. баг №1) | 302 |
| `/admin/product/size/<id>/delete` | POST | 302 (работало) | 302 |
| `/admin/product/image/<id>/delete` | POST | 302 (работало) | 302 |
| `/admin/product/<id>/delete` | POST | 302 (работало) | 302 |

Проверено: кириллическое название `Кроссовки Nike Air Max 90 «Зимняя коллекция» №2`,
описание с амперсандами/апострофами/юникодом (`↑ € ™`), SEO-поля кириллицей — во всех
случаях сохранились в БД байт-в-байт (без обрубания/искажения под strict mode). Данные
после `edit` (`«Обновлённая версия» №3 — весна`) читаются обратно идентично отправленным.

## Найден и исправлен 1 живой 500: `POST /admin/product/<id>/add-image`

| # | Маршрут | Метод | До | Причина | Фикс |
|---|---|---|---|---|---|
| 1 | `POST /admin/product/<id>/add-image` | POST | 500 | `product_image.created_at` — `int NOT NULL` без дефолта (`infrastructure/migrations/m250101_000000_create_base_tables.php:98`), но `ProductImage` не имела `behaviors()` вообще и никогда не заполняла это поле → `ProductController::actionAddImage()` → `ProductImage::save()` падал под strict mode с `SQLSTATE[HY000]: General error: 1364 Field 'created_at' doesn't have a default value` на КАЖДОМ добавлении изображения. Тот же класс бага, что и в CMP-410 (Category/Brand `created_at`), но для `ProductImage` фикс тогда не применили. | Добавлен `TimestampBehavior` (`createdAtAttribute => 'created_at'`, `updatedAtAttribute => false` — колонки `updated_at` в таблице нет) в `backend/modules/catalog/models/ProductImage.php` |

Репро (до фикса, из `runtime/logs/app.log`):
```
INSERT INTO `product_image` (`product_id`, `image`, `sort_order`, `is_main`)
VALUES (12, 'https://example.com/test-image-cmp417.jpg', 1, 0)
yii\db\Exception: SQLSTATE[HY000]: General error: 1364 Field 'created_at' doesn't have a default value
#6 .../ProductController.php(613): yii\db\BaseActiveRecord->save()
#7 ...ProductController->actionAddImage('12')
```

## Изменённые файлы

- `backend/modules/catalog/models/ProductImage.php` — фикс (добавлен `TimestampBehavior`
  для `created_at`, обновлён PHPDoc `@property`).
- `tests/unit/Cmp417ProductAdminTest.php` — новый регрессионный тест (6 тестов,
  save()/validate() напрямую на реальной БД + `tearDown()` cleanup, по образцу
  `Cmp410SchemaDriftTest.php`): create с кириллицей, edit, toggle, add-size, add-image
  (регрессия на баг №1), delete-size/delete-image/delete.

Тест-скрипт живого HTTP-прогона временный, не коммитился в репозиторий (лежал в
`/tmp/cmp417-product-test.php`) — постоянное покрытие того же сценария обеспечивает
`tests/unit/Cmp417ProductAdminTest.php`.

## Прогон тестов

```
$ vendor/bin/phpunit tests/unit/Cmp417ProductAdminTest.php
Tests: 6, Assertions: 31, PHPUnit Warnings: 1 (no coverage driver), PHPUnit Deprecations: 1.
OK, but there were issues!
```

Также прогнан весь unit-набор для проверки отсутствия регрессий:
`vendor/bin/phpunit --testsuite Unit` → **132/132 зелёных** (361 assertions), включая
`Cmp410SchemaDriftTest.php` (21/21) без изменений в поведении.

`vendor/bin/phpcs --standard=PSR12 backend/modules/catalog/models/ProductImage.php` — без
замечаний. `vendor/bin/phpstan analyse -l 5 backend/modules/catalog/models/ProductImage.php`
— без ошибок, baseline не менялся.

Тестовые товары/размеры/изображения, созданные во время живого HTTP-прогона (id 11–14 в
`cmp410_e2e_clean`), удалены после прогона — в БД не осталось артефактов. Файлы не
загружались в `frontend/web/uploads/` (см. открытый вопрос №1 ниже) — вебрут не трогался.

## Открытые вопросы (не мои решения, для продукта/CTO)

1. **`actionAddImage` не поддерживает реальную загрузку файла (multipart), только
   текстовый `image_url`.** Задание тикета предполагало «сгенерируй PNG и отправь
   multipart», но в контроллере нет `UploadedFile::getInstance(...)` — только
   `Yii::$app->request->post('image_url')`, и обе формы в админке (`view.php:951`,
   `edit.php:1155`) используют `<input type="url" name="image_url">`. Для сравнения,
   `BrandController` и `CategoryController` поддерживают `UploadedFile` для
   логотипа/картинки категории. Не поправил это как «баг», потому что не очевидно,
   является ли отсутствие локальной загрузки фото товара осознанным решением (товары
   заводятся в основном через Poizon-синхронизацию, где `image` — внешний URL) или
   пропущенной фичей. Если нужна реальная загрузка файла для товаров — отдельная
   задача с явным продуктовым решением (валидация типов/размеров, куда класть файлы,
   нужны ли миниатюры и т.д., см. комментарий класса `ProductImage`: «Автоматическое
   создание миниатюр» — судя по коду, не реализовано).
2. Регрессионный тест на баг №1 (`ProductImage.created_at`) проверяет только
   `created_at`; в этой же таблице отсутствует `updated_at` вовсе — если в будущем её
   добавят миграцией, `TimestampBehavior` нужно будет обновить (`updatedAtAttribute`).
   Не действие сейчас, просто на будущее.
