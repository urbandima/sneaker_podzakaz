# AmoCRM Integration Architecture

_Last updated: 2026-04-26_

---

## 1. Виджет AmoCRM

### Как работает виджет

Виджет AmoCRM — это frontend-приложение (ZIP архив), которое AmoCRM загружает в своём интерфейсе.

**Структура архива:**
```
widget/
├── manifest.json       — метаданные, поля настроек, scope
├── script.js           — основной JS, define(['jquery'], function($){...})
├── settings.js         — скрипт страницы настроек (опционально)
├── templates/
│   ├── card.twig       — шаблон вставки в карточку сделки
│   └── settings.twig   — шаблон страницы настроек
└── i18n/
    ├── ru.json
    └── en.json
```

### manifest.json — ключевые поля

```json
{
  "widget": {
    "name":        {"ru": "...", "en": "..."},
    "description": {"ru": "...", "en": "..."},
    "version":     "1.0.0",
    "category":    "integration",
    "interface": {
      "lead_card":   { "show_button": true },
      "settings":    { "fields": [ { "id": "...", "type": "text", "required": true } ] }
    },
    "scopes":      ["crm"],
    "integrations": ["leads"]
  }
}
```

**Типы настроечных полей:** `text`, `pass`, `custom`, `users`, `users_lp`

### script.js — обязательные callbacks

Виджет должен возвращать объект с блоком `callbacks`:

```js
define(['jquery'], function($) {
  var SELF = {
    // Однократная инициализация виджета
    callbacks: {
      init:          function() { return this; },
      render:        function() { /* рендер в lead_card */ return this; },
      bind_actions:  function() { /* навешивание обработчиков */ return this; },
      settings:      function() { /* страница настроек */ return this; },
      onSave:        function() { /* вызывается при сохранении настроек, false = отмена */ return true; },
      destroy:       function() { /* очистка при уходе с карточки */ return this; },
      dpSettings:    function() { /* Digital Pipeline — опционально */ return this; },
      bindedCallback:function() { /* кастомные интеграции — опционально */ return this; }
    }
  };
  return SELF;
});
```

### SELF.system() — объект текущего контекста

```js
var sys = SELF.system();
sys.lead_id       // ID текущей сделки (в контексте lead_card)
sys.settings      // { shop_url: "...", api_token: "..." } — настройки виджета
sys.account       // { id, subdomain, ... }
sys.user_id       // ID текущего пользователя AmoCRM
sys.domain        // поддомен аккаунта (напр. "mycompany")
```

### Locations — где может рендериться виджет

| Location | Описание |
|----------|---------|
| `lead_card` | Правая колонка карточки сделки |
| `settings` | Страница настроек виджета |
| `detail_tab` | Отдельная вкладка в карточке |
| `list` | Список сделок (header или sidebar) |

Наш виджет использует только `lead_card` и `settings`.

### Шаблоны (Twig)

В `card.twig` — HTML-скелет карточки, который монтируется в DOM. JS-рендер через `$.ajax` заполняет его уже динамически.

### i18n

```js
var lang = SELF.i18n();      // функция-переводчик
lang('loading')              // → "Загрузка данных..." (из ru.json / en.json)
```

---

## 2. AmoCRM API v4 — ключевые endpoints

**Base URL:** `https://api-b.amocrm.ru`  
**Auth:** `Authorization: Bearer <token>`

| Метод | URL | Описание |
|-------|-----|---------|
| GET | `/api/v4/leads/{id}?with=contacts,custom_fields_values` | Лид с контактами и полями |
| POST | `/api/v4/leads` | Создать лид |
| PATCH | `/api/v4/leads/{id}` | Обновить лид (статус, поля) |
| POST | `/api/v4/leads/complex` | Создать лид+контакт+компанию |
| GET | `/api/v4/leads/pipelines` | Список воронок |
| GET | `/api/v4/leads/pipelines/{id}/statuses` | Статусы воронки |
| GET | `/api/v4/leads/custom_fields` | Кастомные поля сделок |
| GET | `/api/v4/contacts/{id}?with=custom_fields_values` | Контакт с полями |
| GET | `/api/v4/contacts?query=<phone>` | Поиск контакта |
| POST | `/api/v4/leads/notes` | Добавить заметку |
| GET | `/api/v4/account` | Инфо об аккаунте |
| GET | `/api/v4/users` | Список пользователей |
| GET | `/api/v4/widgets` | Список виджетов |
| POST | `/api/v4/widgets/{code}` | Установить виджет (admin only) |

**Стандарт:** Content-Type `application/hal+json`, ответ всегда в `_embedded`.

**Фильтрация лидов:** `?page=1&limit=250&filter[status_id]=xxx&with=contacts`

**Системные статусы:** `142` = Успешно закрыта, `143` = Закрыто и не реализовано.

**Кастомные поля контакта:** ищи в `custom_fields_values` по `field_code`:
- `PHONE` — телефон
- `EMAIL` — email

---

## 3. Текущее состояние нашего виджета

### Что сделано (в `outputs/amocrm-widget/`)

✅ `manifest.json` — базовая структура, `lead_card` + настройки (`shop_url`, `api_token`)  
✅ `script.js` — рендер в lead_card: запрос `/api/amocrm/order?external_id=<lead_id>`, показывает таблицу с данными заказа, кнопка "Синхронизировать" → POST `/api/amocrm/sync`  
✅ `settings.js` — валидация формы настроек при onSave  
✅ `templates/card.twig` — контейнер `#sneakerhead-widget-wrap` + CSS  
✅ `templates/settings.twig` — поля `shop_url` + `api_token`  
✅ `i18n/ru.json`, `i18n/en.json` — переводы  

### Что отсутствует в виджете

❌ **Кнопка "Создать заказ"** — нет кнопки для создания нового заказа из AmoCRM (только показ/синхронизация существующего)  
❌ **Показ статуса создания** — нет feedback при создании заказа  
❌ **GET `/api/amocrm/order?external_id=`** — этот endpoint **не существует** в нашем API (нет в routes и контроллерах)  
❌ **POST `/api/amocrm/sync`** — также **отсутствует** (маршрут `api/amocrm/sync` есть, но нет action)  
❌ **Нет endpoint `/api/amocrm/order`** в существующих контроллерах — только `/api/amocrm/create-order` и `/api/amocrm/products`  

### Что сделано на нашей стороне (бэкенд)

✅ **AmocrmClient** — REST-клиент с long-token поддержкой (ENV), getLead, updateLead, getCustomFields, getPipelines  
✅ **AmocrmStatusMapper** — двусторонний маппинг статусов  
✅ **OrderFromLeadService** — создание заказа из лида: fetch → parse → Customer → Order  
✅ **WebhookController::actionAmocrm** — обрабатывает `leads[status]` (с созданием заказа по триггер-статусу), `leads[add]`, `leads[delete]`  
✅ **AmocrmOrderController::actionCreateOrder** — режим `lead_id` (fetch from AMO) + legacy режим  
✅ **OrderController::actionChangeStatus** — push статуса в AmoCRM при смене в нашей системе  
✅ **migration: amocrm_field_mapping** — таблица маппинга полей  
✅ **Admin plugin UI** — 5 вкладок (dashboard, settings, webhooks, logs, stats)  

### Что ещё нужно реализовать

❌ **GET `/api/amocrm/order`** — endpoint для виджета (найти заказ по `external_id = amocrm_lead_id`)  
❌ **POST `/api/amocrm/sync`** — endpoint для синхронизации (виджет нажал "Синхронизировать")  
❌ **Кнопка "Создать заказ"** в виджете → POST `/api/amocrm/create-order` c `{lead_id: ...}`  
❌ **Таб "Поля"** в admin plugin UI — выбор маппинга кастомных полей AmoCRM ↔ наши поля  
❌ **CLI команда** `amocrm/sync-lead {id}` — для ручного тестирования  
❌ **Маршруты** для новых endpoints  

---

## 4. План реализации

### Шаг 1 — Добавить недостающие API endpoints (критично для виджета)

**GET `/api/amocrm/order?external_id=<lead_id>`** → найти Order по `amocrm_lead_id`, вернуть JSON:
```json
{
  "found": true,
  "order_id": 123,
  "order_number": "SH-0042",
  "status": "paid",
  "total": 350.00,
  "recipient_name": "Иван Иванов",
  "phone": "+375291234567",
  "admin_url": "https://..."
}
```

**POST `/api/amocrm/sync`** `{lead_id: 123}` → вызвать `OrderFromLeadService::createFromLeadId` или `updateOrderFromLead` если уже есть.

### Шаг 2 — Обновить виджет script.js

Добавить кнопку **"Создать заказ"** (когда `!data.found`):
```js
$wrap.html('<p>' + lang('order_not_found') + '</p>' +
  '<button id="sh-create-btn">' + lang('create_order') + '</button>');

$('#sh-create-btn').on('click', function() {
  $.ajax({
    url: shopUrl + '/api/amocrm/create-order',
    method: 'POST',
    headers: { Authorization: 'Bearer ' + apiToken, 'Content-Type': 'application/json' },
    data: JSON.stringify({ lead_id: leadId }),
    success: function(r) {
      if (r.success) {
        $wrap.html('<a href="' + r.order_url + '" target="_blank">' + lang('order_created') + ' #' + r.order_number + '</a>');
      }
    }
  });
});
```

### Шаг 3 — Таб "Поля" в admin plugin

- `GET /admin/plugin/amocrm/fields` → fetch `/api/v4/leads/custom_fields` → показать таблицу с dropdown
- Форма: наши поля (order.total_amount, order.product_name и т.д.) ↔ AmoCRM field_id
- Сохранить в `amocrm_field_mapping`

### Шаг 4 — CLI команда

```
php yii amocrm/sync-lead 12345
```
Вызывает `OrderFromLeadService::createFromLeadId(12345)`, выводит результат.

### Шаг 5 — Webhook в AmoCRM (настройка)

В AmoCRM: Settings → Webhooks:
- URL: `https://your-domain.by/webhook/amocrm/event`
- Events: `leads → Status changed`, `leads → Add`

Для автоматического создания заказа: установить в нашей админке `create_order_status_id` = ID статуса "Купили / Готов к выкупу" из нужной воронки.

---

## 5. Open Questions — нужно уточнить у пользователя

1. **Pipeline ID** — какую воронку использовать? Какой ID?
2. **Trigger status** — какой статус сделки в AmoCRM означает "создать заказ"? ("Купили"? "Готов к выкупу"?) Какой status_id?
3. **Custom fields** — какие кастомные поля AmoCRM нужно маппить на наши поля заказа? (товар, размер, цена, адрес?)
4. **Contact fields** — телефон и email будут в контакте или прямо в сделке?
5. **Create on add vs create on status** — создавать заказ при добавлении сделки или только при смене статуса на триггерный?
6. **Обратная синхронизация** — при смене статуса у нас, на какой статус AmoCRM переводить? (нужна таблица маппинга)
7. **Дубликаты** — если сделка уже имеет заказ и снова меняет статус, обновлять заказ или игнорировать?
8. **Виджет** — нужно ли перепаковать ZIP и задеплоить в AmoCRM после правок? Или это делается вручную?
9. **`api_key` для виджета** — где хранится ключ виджета, который используется в `X-Api-Key`? Сейчас в settings[amocrm][api_key] или settings[amocrm][widget_api_key]?
10. **Long token срок** — токен действителен до 2029, но нужна ли логика обновления на будущее?

---

## 6. ENV переменные

```bash
AMOCRM_INTEGRATION_ID=...    # OAuth integration ID
AMOCRM_SECRET_KEY=...        # OAuth secret / HMAC webhook verification
AMOCRM_LONG_TOKEN=eyJ...     # JWT long-lived token (until 2029)
AMOCRM_API_DOMAIN=api-b.amocrm.ru
AMOCRM_BASE_DOMAIN=amocrm.ru
AMOCRM_ACCOUNT_ID=29176798
```

Используются через `getenv()` в `AmocrmClient::init()`. **Не коммитить `.env`**.

---

## 7. Текущие API endpoints (наши)

| Метод | URL | Обработчик | Статус |
|-------|-----|-----------|--------|
| POST | `/api/amocrm/create-order` | `AmocrmOrderController::actionCreateOrder` | ✅ готов (режимы: lead_id + legacy) |
| GET | `/api/amocrm/products` | `AmocrmOrderController::actionProducts` | ✅ готов |
| GET | `/api/amocrm/order` | — | ❌ нет endpoint |
| POST | `/api/amocrm/sync` | — | ❌ нет endpoint |
| POST | `/webhook/amocrm/event` | `WebhookController::actionAmocrm` | ✅ готов |
| POST | `/webhook/amocrm/lead-status-changed` | `WebhookController::actionLeadStatusChanged` | ✅ (alias) |
| GET | `/admin/plugin/amocrm` | `PluginController::actionAmocrm` | ✅ 5 вкладок |
| GET | `/admin/plugin/amocrm/fields` | — | ❌ нет таба "Поля" |
