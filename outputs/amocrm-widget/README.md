# SneakerHead Orders — AmoCRM Widget

Shows store order data on the AmoCRM lead card and allows manual synchronisation.

---

## Installation

### 1. Generate an API token in the store admin

1. Open **Admin → Plugins → AmoCRM → Settings tab**.
2. Fill in **Domain**, **Client ID**, **Client Secret** and click **Авторизовать через OAuth**.
3. After successful OAuth, scroll to **API Token** (or note it from the Settings view).

### 2. Upload the widget to AmoCRM

1. Log into your AmoCRM account.
2. Go to **Settings → Integrations → Create integration**.
3. Provide the integration name `SneakerHead — Orders` and upload this ZIP archive.
4. AmoCRM will parse `manifest.json` automatically.

### 3. Configure the widget

After installation the widget settings page will appear:

| Field | Value |
|---|---|
| URL магазина | `https://your-shop.com` (no trailing slash) |
| API токен | Copied from the store admin settings |

### 4. Webhooks (optional, for real-time status sync)

In AmoCRM go to **Settings → Webhooks** and add:

- **URL**: `https://your-shop.com/webhook/amocrm/event`
- **Events**: `leads → Status changed`, `leads → Add`, `leads → Delete`

The store will automatically update the order status when the lead status changes to the
configured "Paid" status.

---

## API Endpoints (used by the widget)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/api/amocrm/order?external_id=<lead_id>` | Get order data by AmoCRM lead ID |
| POST | `/api/amocrm/sync` | Create or update lead from order |
| POST | `/webhook/amocrm/event` | Incoming webhook from AmoCRM |

All endpoints require `Authorization: Bearer <api_token>` header.

---

## Support

Open an issue at the store admin or contact the developer.
