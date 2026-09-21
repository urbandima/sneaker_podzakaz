# CMP-417 — живой HTTP POST на CategoryController/BrandController (create/update/upload/delete)

Дата: 2026-09-22. Окружение: локальный `php -S 127.0.0.1:8765 -t frontend/web router.php`,
БД `cmp410_e2e_clean` (MySQL 8, strict mode). Родитель: CMP-413 (живой HTTP-прогон нашёл
битые маршруты, но POST-действия с реальными данными, включая загрузку изображений, не
проверялись). Эта задача закрывает категории и бренды; товары — отдельный отчёт
(`CMP-417-products-report.md`).

## Метод

`scripts/cmp417-http-client.php` (готовый клиент, ручной cookie-jar — `secure`-куки
`_csrf`/`_identity-admin` не переживают curl-cookiejar по `http://`). Прогон: логин
`admin/admin123` → `POST /admin/category/create` кириллицей со спецсимволами → дочерняя
категория (`parent_id`) → `POST /admin/category/update?id=<id>` → multipart `POST
/admin/category/upload-image?id=<id>` (PNG, сгенерированный на лету через
`imagecreatetruecolor`+`imagepng`) → `POST /admin/category/delete?id=<id>` → то же самое
для `BrandController` (`upload-logo` вместо `upload-image`). После каждого шага — `SELECT`
в БД (`--default-character-set=utf8mb4`) на целостность кириллицы и на консистентность
`parent_id`/`lft`/`rgt` дерева категорий. Загруженные тестовые файлы удалены из
`frontend/web/uploads/{categories,brands}/` после проверки; все тестовые категории/бренды
удалены из БД, финальный `COUNT(*)` совпадает с сид-данными (4 категории, 8 брендов).

Мультипарт-загрузка (`upload-image`/`upload-logo`) не покрывается `Cmp417Client::postForm()`
(там `http_build_query`, не multipart) — использован отдельный `curl_init` + `CURLFile` с
Cookie-заголовком, собранным вручную (см. рефлексию на приватное поле `$cookies` клиента в
разведочном скрипте; постоянных изменений в `scripts/cmp417-http-client.php` не потребовалось).

## Результаты по маршрутам

| Маршрут | Метод | Статус до фикса | Статус после фикса |
|---|---|---|---|
| `/admin/category/create` | POST | 302 (кириллица сохранялась) | 302 (+ теперь непустой slug) |
| `/admin/category/update?id=<id>` | POST | 302 (работало) | 302 |
| `/admin/category/upload-image?id=<id>` | POST (multipart) | 200 `{"success":true}` (работало) | 200 |
| `/admin/category/delete?id=<id>` | POST | 302, **но осиротял детей** (баг №2) | 302, блокируется при наличии детей |
| `/admin/brand/create` | POST | 302 (кириллица сохранялась) | 302 (+ теперь непустой slug) |
| `/admin/brand/update?id=<id>` | POST | 302 (работало) | 302 |
| `/admin/brand/upload-logo?id=<id>` | POST (multipart) | 200 `{"success":true}` (работало) | 200 |
| `/admin/brand/delete?id=<id>` | POST | 302 (работало) | 302 |

Проверено дополнительно (без 500 ни в одном случае):
- `POST /admin/category/create` без `name` → 200, форма перерендерена с ошибкой валидации
  (не 500).
- `POST /admin/category/create` с `parent_id=999999` (несуществующий) → валидатор `exist`
  отклоняет, 200 с ошибкой формы.
- `POST /admin/category/delete?id=<несуществующий>` и `GET /admin/category/<несуществующий>`
  → 404 `NotFoundHttpException` (не 500).
- `POST /admin/category/upload-image` без файла (обычная форма, не AJAX) → 302 redirect с
  flash-ошибкой (не 500).

## Найдено и исправлено 2 живых бага

| # | Место | Симптом | Причина | Фикс |
|---|---|---|---|---|
| 1 | `Category::behaviors()` / `Brand::behaviors()` (`SluggableBehavior`) | Категория/бренд с **чисто кириллическим** названием (без единого ASCII-символа) получал `slug = ''`; следующая такая же запись — `slug = '-2'` и т.д. `/catalog/category/{slug}` и `/catalog/brand/{slug}` (реальные публичные маршруты, `backend/modules/catalog/controllers/CatalogController::actionCategory($slug)`/`actionBrand($slug)`) становятся недостижимы для такой записи. | `yii\helpers\Inflector::transliterate()` без php-intl (расширение не установлено в этом окружении — `extension_loaded('intl') === false`, и не гарантировано на проде) молча падает на fallback-карту `Inflector::$transliteration`, которая покрывает только латинские диакритики, не кириллицу. `Inflector::slug()` после этого вырезает регэкспом `[^a-zA-Z0-9=\s—–-]+` **все** кириллические буквы — от строки остаётся пустой хвост. | В `Category.php`/`Brand.php` добавлен виртуальный атрибут `getSlugSource()` с ручной картой кириллица→латиница (стандартная транслитерация + белорусские `і`/`ў`/`ґ`, сайт — `.by`); `SluggableBehavior::attribute` переключён с `'name'` на `'slugSource'`. `Inflector::slug()` всё равно вызывается штатно (внутри `SluggableBehavior::generateSlug()`) — уже над транслитерированной латиницей, поэтому регэксп больше не режет исходный текст. Названия без кириллицы (Nike, Adidas...) транслитерируются как раньше — регрессии нет (см. тест `testBrandSaveWithLatinNameKeepsPlainSlug`). |
| 2 | `CategoryController::actionDelete()` | Удаление родительской категории с дочерними (`parent_id`) оставляло дочерние записи с `parent_id`, указывающим на **несуществующую** категорию (осиротевшее поддерево). В `information_schema.REFERENTIAL_CONSTRAINTS` для `category.parent_id` нет ни одного FK/`ON DELETE` — целостность дерева не защищена на уровне БД. | Контроллер проверял только количество товаров в категории (`Product::find()->where(['category_id' => $id])`), но не дочерние категории, перед `$model->delete()`. | Добавлена симметричная проверка `Category::find()->where(['parent_id' => $id])->count()` перед удалением — при наличии детей удаление блокируется с тем же паттерном flash-ошибки, что уже использовался для товаров (`"Нельзя удалить категорию: у неё N дочерних категорий"`). |

Репро бага №1 (до фикса, живой HTTP):
```
POST /admin/category/create   Category[name]=Женская обувь Осень Зима
→ 302 /admin/category/14
SELECT id,name,slug FROM category WHERE id=14;
14 | Женская обувь Осень Зима | ""        ← пустой slug

POST /admin/category/create   Category[name]=Мужская обувь Осень Зима
→ 302 /admin/category/15
SELECT id,name,slug FROM category WHERE id=15;
15 | Мужская обувь Осень Зима | "-2"      ← SluggableBehavior::ensureUnique добавил
                                             суффикс к пустой базе
```

Репро бага №2 (до фикса, живой HTTP):
```
POST /admin/category/create   Category[name]=Родитель        → id=12
POST /admin/category/create   Category[name]=Дочка, parent_id=12  → id=13
POST /admin/category/delete?id=12  → 302 (успех, категория 12 удалена)
SELECT id,parent_id,name FROM category WHERE id=13;
13 | 12 | Дочка   ← parent_id=12 больше не существует, поддерево осиротело
```

## lft/rgt (nested set)

`category.lft`/`rgt`/`depth`/`tree` — колонки от заброшенной попытки Nested Set Model
(`infrastructure/migrations/m260330_223000_add_nested_set_to_category.php`), но `Category`
не использует `NestedSetsBehavior` и ни один запрос в коде не читает/пишет `lft`/`rgt` — это
уже задокументировано как известная (не наша) находка CMP-410
(`infrastructure/migrations/m260921_144500_default_category_lft_rgt.php`, `tests/unit/Cmp410SchemaDriftTest.php`).
Живой прогон подтверждает: все новые категории (create) получают `lft=0, rgt=0` (дефолт из
миграции CMP-410) и остаются такими после update/delete — никакого "порченного" дерева
формально нет, потому что дерево как структура сейчас не поддерживается вообще (значения
всегда 0, а не рассинхронизированные ненулевые интервалы). Реальное дерево категорий сейчас
держится только на `parent_id` (проверено выше, баг №2 про него).

**Открытый вопрос (продуктовое решение, не наше):** нужен ли рабочий Nested Set вообще, или
`lft`/`rgt`/`tree`/`depth` можно выпилить как мёртвый код — оставляем как есть, чтобы не
трогать схему без консультации.

## Тесты

`tests/unit/Cmp417CategoryBrandAdminTest.php` — 6 тестов, оба бага воспроизведены на уровне
`save()`/`actionDelete()` напрямую на реальной БД (`cmp410_e2e_clean`):

- `testCategorySaveWithCyrillicNameProducesNonEmptyLatinSlug`
- `testBrandSaveWithCyrillicNameProducesNonEmptyLatinSlug`
- `testBrandSaveWithLatinNameKeepsPlainSlug` (no-regression для латинских названий)
- `testCategorySavesCyrillicSpecialCharsIntactInDescriptionAndSeoFields`
- `testActionDeleteBlocksParentWithChildCategories`
- `testBrandActionDeleteRemovesBrandWithoutProducts` (sanity, что Brand не задет проверкой)

```
$ vendor/bin/phpunit tests/unit/Cmp417CategoryBrandAdminTest.php
...... 6 / 6 (100%)
Tests: 6, Assertions: 21, PHPUnit Warnings: 1, PHPUnit Deprecations: 1.
```

Совместный прогон с соседними CMP-410/413/417(Product) наборами — без взаимных помех:
```
$ vendor/bin/phpunit tests/unit/Cmp417CategoryBrandAdminTest.php tests/unit/Cmp410SchemaDriftTest.php \
    tests/unit/Cmp413RouteSweepTest.php tests/unit/Cmp417ProductAdminTest.php
........................................... 35 / 35 (100%)
Tests: 35, Assertions: 105, PHPUnit Warnings: 1, PHPUnit Deprecations: 1.
```

(`Cmp416PostScenariosTest.php` в этом же прогоне независимо ломается на Account/Loyalty/Return —
подтверждено `git stash`, что это уже было так до наших правок; не относится к
Category/Brand и не наша зона.)

## Статика

```
$ vendor/bin/phpcs --standard=PSR12 backend/modules/catalog/models/Category.php \
    backend/modules/catalog/models/Brand.php \
    backend/modules/admin/controllers/CategoryController.php \
    backend/modules/admin/controllers/BrandController.php \
    tests/unit/Cmp417CategoryBrandAdminTest.php
FOUND 0 ERRORS AND 1 WARNING (Category.php, строка 319 — pre-existing, вне диффа этой задачи)
FOUND 0 ERRORS AND 1 WARNING (Brand.php, строка 280 — pre-existing, вне диффа этой задачи)

$ vendor/bin/phpstan analyse -l 5 --memory-limit=1G <те же 4 файла models/controllers>
[OK] No errors
```
(Дефолтный memory_limit 128M не хватает phpstan для параллельного воркера — не относится к
нашему коду, использован `--memory-limit=1G` без изменения baseline/конфига.)

## Изменённые файлы

- `backend/modules/catalog/models/Category.php` — `getSlugSource()` + карта транслитерации,
  `SluggableBehavior::attribute` → `'slugSource'`.
- `backend/modules/catalog/models/Brand.php` — то же самое (аналогичный дублирующийся блок,
  без общей зависимости между моделями).
- `backend/modules/admin/controllers/CategoryController.php` — `actionDelete()`: проверка
  дочерних категорий перед удалением.
- `tests/unit/Cmp417CategoryBrandAdminTest.php` — новый файл, регрессионные тесты (см. выше).

`BrandController.php` не менялся — баг №2 специфичен для дерева категорий (`parent_id`),
у брендов иерархии нет.

## Открытые вопросы

1. **php-intl** не установлен в этом окружении (`extension_loaded('intl') === false`).
   Ручная транслитерация в `getSlugSource()` устраняет конкретный найденный баг (пустой slug
   для Category/Brand), но `yii\helpers\Inflector`/`Formatter` используются и в других
   местах кода (например, `Cmp416PostScenariosTest::testLoyaltyProgramRendersWithoutUnknownPropertyException`
   падает именно из-за отсутствия intl — не в нашей зоне, но тот же корень). Стоит решить на
   уровне инфраструктуры/деплоя, ставить ли `php-intl` на прод, независимо от точечного фикса
   здесь.
2. **Nested Set (`lft`/`rgt`/`tree`/`depth`) в `category`** — мёртвый код, уже отмечен CMP-410.
   Не трогали схему/поведение по инструкции; если бизнесу нужно реальное дерево с быстрым
   поддеревом-запросом — это отдельная задача на внедрение `NestedSetsBehavior` с полной
   переинициализацией дерева, не точечный фикс.
