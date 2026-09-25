# CMP-463 — Сплошной прогон мутирующих действий, Очередь №2 волна 2в

Продолжение методологии CMP-456/CMP-460: живой HTTP-запрос через залогиненную
admin-сессию (реальные cookies + CSRF из `<meta name="csrf-token">`) со
сверкой фактической строки в MySQL (`cmp410_e2e_clean`) до/после — вердикт по
эффекту в БД/файле, а не по HTTP-коду.

## Границы охвата

Ровно 15 экшенов `backend/modules/admin/controllers/ProductController.php`,
оставшихся непроверенными после волны 2б CMP-460 (bulk-операции, экспорт,
клонирование, Poizon-синхронизация, AJAX-точечные обновления полей/цен/
размеров товара).

## Реестр

| Действие | Маршрут | Payload (кратко) | HTTP | Факт в БД/файле | Вердикт |
|---|---|---|---|---|---|
| `actionBulkUpdate` | `POST /admin/product/bulk-update` | `ids=[395,396]`, `field=is_active`, `value=0`, 3-й товар (397) не в списке | 200 | 395/396 → `is_active=0`, 397 не тронут | **ok** |
| `actionBulkDelete` | `POST /admin/product/bulk-delete` | `ids=[398,399]` (398 с размерами+картинкой), 400 не в списке | 200 | 398/399 и их `product_size`/`product_image` удалены, 400 жив | **ok** |
| `actionBulkPrice` | `GET /admin/product/bulk-price?brand=1&category=1` | — (рендер страницы, не мутирует) | 200 | форма `#bulk-price-table` отрендерена, БД не менялась | **ok** |
| `actionBulkUpdatePrice` | `POST /admin/product/bulk-update-price` | JSON body `{"prices":[{"id":401,"price":222.5},{"id":402,"price":333.5}]}` (401 `is_active=1`, 402 `is_active=0`) — точно как реально шлёт `admin-products.js` (`Content-Type: application/json`) | 200 | **до фикса**: `success:true, updated:0`, цена не изменилась ни у одного товара (см. дефект №1); **после фикса**: `updated:1`, цена 401→222.50, 402 осталась 100.00 | **wrong_data → исправлено** |
| `actionExport` (csv) | `GET /admin/product/export?ids=401,402&format=csv` | — | 200 | 2 строки, кириллица (`Активен`/`Неактивен`, названия) цела, BOM есть | **ok** |
| `actionExport` (xlsx) | `GET /admin/product/export?ids=401,402&format=xlsx` | — | 200 | реальный OOXML/zip (`unzip -l` → 11 файлов, `[Content_Types].xml`, `xl/worksheets/...`), не пустышка | **ok** |
| `actionExportCsv` | `GET /admin/product/export-csv` | — (без `ids`, все товары) | 200 | 46 строк данных (весь каталог), кириллица цела (`Кроссовки`, `Активен`) | **ok** |
| `actionClone` | `POST /admin/product/clone?id=403` (товар с 2 размерами + 1 картинкой) | — | 302 → `/admin/product/413/edit` | клон id 413: `name` = `CMP-463 TEST Clone (копия)`, `is_active=0`, `slug` с `-copy-<ts>`; размеры скопированы 2/2; картинки **не** скопированы (0/1) | **ok** (картинки не копируются намеренно — см. «Найдено, не исправлено») |
| `actionSyncPoizon` | `POST /admin/product/sync-poizon` | без cookies: `{"id":412}`; с cookies, товар без `poizon_id`: `{"id":412}` | без cookies 302→`/admin/login`; с cookies 200 | внешний API не вызывался (товар без `poizon_id` отбивается раньше), `{"success":false,"message":"Товар без Poizon ID"}` | **ok** (живой вызов с реальным `poizon_id` не делался — см. границы ниже) |
| `actionUpdatePrice` | `POST /admin/product/update-price` | JSON `{"id":404,"price":456.78}` | 200 | `price` 404: 100.00 → 456.78 | **ok** |
| `actionUpdateField` | `POST /admin/product/update-field` | JSON `{"id":405,"field":"description","value":"Тестовое описание с кириллицей CMP-463"}`; отдельно `{"field":"price",...}` — не в allowlist | 200 / 200 | `description` записан кириллицей без порчи; `field=price` отбит (`Недопустимое поле`), `price` не тронут | **ok** |
| `actionToggleActive` | `POST /admin/product/toggle-active` | JSON `{"id":406}` | 200 | `is_active` 406: 1 → 0 | **ok** |
| `actionSaveSizesData` | `POST /admin/product/save-sizes-data` | JSON `{"id":407,"sizes":{"36":true,"37":false,"38":true}}` | 200 | `sizes_data` = `{"36":true,"37":false,"38":true}` — точное совпадение | **ok** |
| `actionUpdateSizePrice` | `POST /admin/product/update-size-price` | JSON `{"size_id":449,"price_byn":312.34}` | 200 | `product_size.id=449.price_byn`: 100.00 → 312.34 | **ok** |
| `actionSaveField` | `POST /admin/product/save-field` | 4 вызова на товаре 409: `country="Вьетнам"` (текст), `release_year="2023"` (число), `is_active=0` (bool-like), `material="suede"` (enum → `displayValue="Замша"`) | 200 ×4 | все 4 поля записаны верно, кириллица цела, `displayValue` соответствует `displayMap` | **ok** |
| `actionInlineUpdate` | `POST /admin/product/inline-update` | `entity=product`: `{"id":410,"field":"color_description","value":"Чёрно-белый"}`; `entity=size`: `{"id":450,"field":"stock","value":"7"}` | 200 / 200 | `product.color_description` = «Чёрно-белый»; `product_size.id=450.stock` = 7 | **ok** |

## Найденный дефект и фикс

`ProductController::actionBulkUpdatePrice` (строка 988, старый код) содержал
**два независимых дефекта того же класса «200 OK ≠ данные записаны»**:

1. **Полный no-op в реальном использовании.** Реальный вызывающий код
   (`backend/web/js/admin-products.js` / `frontend/web/js/admin-products.js`,
   функция `applyPrices()`) шлёт `fetch(..., {headers: {'Content-Type':
   'application/json'}, body: JSON.stringify({prices: prices})})` — тело
   запроса это raw JSON, а не `application/x-www-form-urlencoded`. Приложение
   **не регистрирует** `'application/json'` в `Request::$parsers` (проверено
   `grep -rn "JsonParser\|'parsers'"` — пусто ни в одном конфиге). Старый код
   читал `Yii::$app->request->post('prices', [])`, что для JSON body без
   зарегистрированного парсера у Yii2 уходит в `mb_parse_str($rawBody, ...)`
   — на входе JSON-текст без `=`/`&`, поэтому ключа `prices` там никогда не
   появляется, и `post('prices', [])` всегда возвращал переданный по
   умолчанию `[]`. **Живым прогоном подтверждено**: POST точно как из
   реальной формы (`Content-Type: application/json`, тело
   `{"prices":[{"id":401,"price":222.5},{"id":402,"price":333.5}]}`) → ответ
   `{"success":true,"updated":0}`, цены в БД не изменились ни у одного из
   двух товаров. Соседние экшены того же контроллера (`actionUpdatePrice`,
   `actionSaveField`, `actionInlineUpdate` и др.) все читают тело так:
   `json_decode(Yii::$app->request->getRawBody(), true) ?: Yii::$app->request->post()`
   — именно этого шага не было в `actionBulkUpdatePrice`.
2. **Подозрение CTO подтверждено отдельно.** Даже если бы парсинг работал,
   `$updated` инкрементировался на каждый элемент массива с непустыми
   `id`/`price`, независимо от того, сколько строк реально обновил
   `Product::updateAll(['price'=>...], ['id'=>..., 'is_active'=>true])` —
   для товара с `is_active=0` условие `WHERE` не матчит ни одной строки, но
   счётчик всё равно рос. Подтверждено живым тестом после фикса (1): при
   корректном парсинге `updated` был бы `2` вместо фактических `1`
   изменённых строк.

**Фикс** (`backend/modules/admin/controllers/ProductController.php`,
`actionBulkUpdatePrice`):

- тело запроса теперь читается тем же паттерном, что и у соседних
  AJAX-экшенов: `json_decode(Yii::$app->request->getRawBody(), true) ?:
  Yii::$app->request->post()`;
- `$updated` теперь суммирует фактическое возвращаемое значение
  `Product::updateAll()` (число реально изменённых строк) по каждому
  вызову, а не инкремент за каждый элемент входного массива.

Проверено живым прогоном после фикса: тот же JSON-запрос → ответ
`{"success":true,"updated":1}`, `price` товара 401 (`is_active=1`) стал
222.50, товара 402 (`is_active=0`) остался 100.00 — счётчик и факт в БД
теперь совпадают.

## Найдено, не исправлено

- **`actionClone` не копирует изображения товара.** Размеры (`product_size`)
  клонируются, изображения (`product_image`) — нет (подтверждено: клон id
  413 получил 2/2 размеров и 0/1 картинок от оригинала 403). Это может быть
  осознанным решением (не плодить физические файлы/URL на диске при клоне),
  либо недоделкой — код никак это не комментирует. **Вопрос совету**: должен
  ли `actionClone` копировать записи `product_image` (без копирования самого
  файла, просто указывая на тот же путь), чтобы копия не требовала полной
  переприкрутки фото вручную?

## Честная граница охвата

- `actionSyncPoizon` проверен только на маршрутизации/доступе (гость → редирект на login) и на ветке «товар без `poizon_id`» (ошибка без похода во внешний API). Живой вызов с реальным `poizon_id` **не делался** — согласно инструкции карточки, поведение внешнего Poizon API и валидность текущих кредов это отдельный вопрос к совету, не входит в эту волну.
- Остальные 14 экшенов проверены полным циклом (живой HTTP + сверка MySQL до/после).

## Итого волны 2в

- **15 действий проверено** живым прогоном с подтверждением факта в БД/файле.
- **1 дефект найден и исправлен** — `actionBulkUpdatePrice` был одновременно
  (а) полным no-op при реальном вызове с фронтенда (JSON body никогда не
  парсился) и (б) отдельно инфлировал счётчик `updated` для неактивных
  товаров, даже если бы парсинг работал. Оба подтверждены живым тестом до и
  после фикса.
- **1 находка без фикса** — `actionClone` не копирует изображения товара;
  вынесено с вопросом совету (осознанный trade-off или недоделка).
- Тестовые данные (18 товаров `CMP-463 TEST *` с id 395–413, включая клон,
  их `product_size`/`product_image`) удалены; подтверждено `SELECT`-ом после
  очистки — 0 строк по всем трём таблицам.

## Проверенные действия (список)

1. `actionBulkUpdate` — ok
2. `actionBulkDelete` — ok
3. `actionBulkPrice` — ok
4. `actionBulkUpdatePrice` — **fixed** (двойной дефект, см. выше)
5. `actionExport` (csv + xlsx) — ok
6. `actionExportCsv` — ok
7. `actionClone` — ok (изображения не копируются — вынесено отдельно)
8. `actionSyncPoizon` — ok (в рамках заявленных границ, внешний вызов не делался)
9. `actionUpdatePrice` — ok
10. `actionUpdateField` — ok
11. `actionToggleActive` — ok
12. `actionSaveSizesData` — ok
13. `actionUpdateSizePrice` — ok
14. `actionSaveField` — ok
15. `actionInlineUpdate` — ok
