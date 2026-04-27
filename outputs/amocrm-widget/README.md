# СНИКЕРХЭД — AmoCRM Widget

Displays store order data on the AmoCRM lead card, lets managers create and synchronise orders without leaving AmoCRM.

---

## Installation

### 1. Generate an API key in the store admin

1. Open **Admin → Plugins → AmoCRM → Settings tab**.
2. Copy the value shown in the **API-ключ виджета** field.  
   (If the field is empty, click **Сгенерировать** to create one.)

### 2. Upload the widget to AmoCRM

1. Log into your AmoCRM account.
2. Go to **Settings → Integrations → Create integration**.
3. Enter integration name `СНИКЕРХЭД — Заказы` and upload this ZIP archive.
4. AmoCRM parses `manifest.json` automatically and shows the settings form.

### 3. Configure the widget

| Field | Value |
|---|---|
| URL магазина | `https://sneakerhead.by` (no trailing slash) |
| API-ключ | Copied from the store admin settings |

### 4. Webhooks (optional — real-time status sync)

In AmoCRM go to **Settings → Webhooks** and add:

- **URL**: `https://sneakerhead.by/webhook/amocrm/event`
- **Events**: `leads → Status changed`, `leads → Add`, `leads → Delete`

The store updates the order status automatically when the lead status changes.

---

## API Endpoints

| Method | Path | Description |
|--------|------|-------------|
| GET | `/api/amocrm/order?external_id=<lead_id>` | Fetch order by AmoCRM lead ID |
| POST | `/api/amocrm/create-order` | Create a new order from lead data |
| POST | `/api/amocrm/sync` | Sync existing order from AmoCRM |

All endpoints require `Authorization: Bearer <api_key>` header.

---

## Widget architecture

- `manifest.json` — AmoCRM integration metadata and settings schema
- `script.js` — AMD module (constructor function), AmoCRM lifecycle callbacks:
  `render → init → bind_actions → settings → onSave → destroy`
- `i18n/ru.json`, `i18n/en.json` — translations loaded via `self.i18n('widget')`
- `templates/card.twig` — initial sidebar placeholder (script.js overwrites it via AJAX)
- `templates/settings.twig` — settings modal template (field names match manifest.json keys)
