# QA Frontend — Итоговый отчёт
**Дата:** 2026-04-26  
**Исполнитель:** Claude Sonnet 4.6  
**Ветка:** main (серия коммитов a6888a2–c4f4667)

---

## Выполненные задачи

### 1. Корзина — «Ошибка добавления» (fix)
**Файл:** `backend/shared/helpers/ProductCardHelper.php`  
**Коммит:** `a6888a2`

**Проблема:** `getAvailableSizes()` возвращал фейковые размеры 36–46 когда `eu_size` = NULL (все товары в БД). Пользователь кликал «36», `Cart::add()` не находил совпадение в `product_size` → «Ошибка добавления».

**Решение:** В цикле foreach: если `$sizeField` пуст, парсим EU-размер из полной строки (`"40 EU / 9 US / 6.5 UK / 25.5 CM"`) через regex `/^(\d+(?:\.\d+)?)\s+EU/i`. `Cart::add()` уже содержал `LIKE "40 %"` условие, которое корректно матчит такой формат — дополнительных правок не потребовалось.

**Проверено:** Товар Puma Mayze Mid → кнопка «40» → «Товар добавлен в корзину!» ✓

---

### 2. Кнопка «Копировать» на /order/\<token\> (done — уже было)
**Файл:** `frontend/views/order/view.php` (функция `orderCopyReq`)

Чекмарк (зелёный ✓) показывается только при `ok === true` от `navigator.clipboard.writeText`. При ошибке — красный ✕. Было реализовано ранее, дополнительных правок не потребовалось.

---

### 3. Загрузка чека оплаты — /order/\<token\>/upload (verified)
**Файл:** `frontend/controllers/OrderController.php::actionUploadPayment()`

Проверена multipart-загрузка через fetch: статус 302→200, файл сохранён в `frontend/runtime/uploads/payments/`, запись в БД обновлена (payment_proof, status→paid, offer_accepted). Работает корректно, доп. правок нет.

---

### 4. Европочта ПВЗ — автокомплит (verified)
**Файл:** `frontend/views/checkout/index.php`

Данные (323 точки, корректная кириллица) загружены из `app_setting[section=shipping, key=europochta_points]`. При выборе доставки «Европочта» — блок `#europochtaPvz` показывается, поиск «Минск» → 50 результатов с правильными адресами. Работает корректно, доп. правок нет.

**Примечание:** Данные в `settings` (пустой) vs `app_setting` (30 строк) — `Yii::$app->settings` читает из `app_setting`, кириллица в порядке (ошибка отображения была только в терминале без `--default-character-set=utf8mb4`).

---

### 5. Серия паспорта — только лат. буквы (fix)
**Файлы:**  
- `frontend/views/order/_passport_form.php`  
- `frontend/views/account/_passport_form.php`  
**Коммит:** `c4f4667`

**Добавлено:**
- `pattern="[A-Z]{2,4}"` (BY) / `pattern="[0-9]{4}"` (RU) на `<input>`
- `inputmode="text"` / `inputmode="numeric"` динамически
- В `switchCitizenship()` — обновление `pattern` и `inputMode` при переключении гражданства
- В форме аккаунта — `oninput` маска `replace(/[^A-Za-z]/g,'').toUpperCase().slice(0,4)`

JS-маска (auto-uppercase + фильтр нелат. символов) уже существовала в форме заказа, добавлены атрибуты HTML5-валидации.

---

### 6. Статусы EN → RU (done — уже было)
**Файл:** `backend/modules/checkout/models/Order.php::statusLabel()`

`$fallbackMap` содержит все используемые статусы на русском. В БД используется 10 уникальных статусов — все присутствуют в карте. Доп. правок не потребовалось.

---

### 7. Скрытие колонок ТРЕК ДП/РБ + кнопка «Столбцы» (done — уже было)
**Файл:** `backend/modules/admin/views/order/index.php`

- Кнопка «Столбцы» (строка 427) присутствует с dropdown-меню
- `DEFAULT_HIDDEN = ['china_track', 'dp_track', 'local_track', 'dp_status']` — колонки скрыты по умолчанию
- 14 тогглов: email, item, status, amount, payment, delivery, china_track, dp_track, local_track, dp_status, city, logist, source, comment

---

### 8. Бейдж просрочки на карточке канбана (feat)
**Файлы:**  
- `backend/modules/admin/views/order/index.php`  
- `backend/web/css/admin-pages.css`  
**Коммит:** `c4f4667`

Красный бейдж `⚠ N дн.` появляется на карточке если `$daysSince >= 7`. Стиль: красный фон `#fef2f2`, обводка `#fca5a5`, шрифт 0.7rem bold. Проверено: заказ #00472 (9 дней) показывает бейдж ✓.

---

## Страницы без замечаний (проверено Chrome MCP)

| Страница | Статус |
|----------|--------|
| `/` | ✓ Без ошибок |
| `/catalog` | ✓ |
| `/catalog?q=nike` | ✓ |
| `/catalog/product/...` | ✓ Размеры из БД |
| `/checkout` | ✓ Европочта PVZ работает |
| `/tracking` | ✓ HTML-форма, поиск по email/телефону |
| `/order/<token>` | ✓ Загрузка чека, копирование реквизитов |
| `/account/login` | ✓ |
| `/account/register` | ✓ |
| `/about`, `/contacts` | ✓ |
| `/admin/order` (kanban) | ✓ Бейджи, скрытые колонки |

---

## Не фиксировалось (out of scope / не воспроизвелось)

- Горизонтальный скролл: исследовано в предыдущей сессии — не баг (`overflow-x: hidden` в `responsive-fixes.css` работает)
- Overflow при 1440px: `scrollWidth < vW` — ложная тревога (окно было 2833px)
