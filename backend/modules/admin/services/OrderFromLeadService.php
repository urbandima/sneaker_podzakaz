<?php

namespace app\backend\modules\admin\services;

use Yii;
use app\backend\modules\checkout\models\Order;
use app\backend\modules\checkout\models\OrderItem;
use app\backend\modules\checkout\models\OrderHistory;
use app\backend\modules\account\models\Customer;
use app\backend\modules\catalog\models\Product;

/**
 * OrderFromLeadService — create or sync an Order from an AmoCRM lead.
 *
 * Usage:
 *   $service = new OrderFromLeadService();
 *   $order = $service->createFromLeadId(12345);
 */
class OrderFromLeadService
{
    /**
     * Fetch lead from AmoCRM and create (or update) a local Order.
     *
     * @throws \RuntimeException if lead fetch fails or required data is missing
     */
    public function createFromLeadId(int $leadId): Order
    {
        $client = Yii::$app->amocrm;
        if (!$client->isConfigured()) {
            throw new \RuntimeException('AmoCRM client is not configured');
        }

        $lead = $client->getLead($leadId, ['contacts', 'custom_fields_values']);
        if (!$lead || empty($lead['id'])) {
            throw new \RuntimeException("Lead #{$leadId} not found in AmoCRM");
        }

        return $this->createFromLeadData($lead);
    }

    /**
     * Create or update Order from already-fetched lead data array.
     * Idempotent: if an order with amocrm_lead_id already exists, updates it.
     */
    public function createFromLeadData(array $lead): Order
    {
        $leadId = (int)$lead['id'];

        // Check idempotency — don't duplicate
        $existing = Order::findOne(['amocrm_lead_id' => $leadId]);
        if ($existing) {
            $this->updateOrderFromLead($existing, $lead);
            return $existing;
        }

        [$name, $phone, $email] = $this->extractContact($lead);
        $customFields = $this->indexCustomFields($lead['custom_fields_values'] ?? []);
        $fieldMap     = $this->loadFieldMapping();

        $customer = $this->findOrCreateCustomer($name, $phone, $email);

        $order = new Order();
        $order->amocrm_lead_id     = $leadId;
        $order->amocrm_last_sync_at = time();
        $order->amocrm_source      = 'webhook';
        $order->source             = 'amoCRM';
        $order->status             = 'new';
        $order->client_name        = $name ?: 'Клиент AmoCRM #' . $leadId;
        $order->client_phone       = $phone;
        $order->client_email       = $email;
        $order->customer_id        = $customer?->id;

        // Apply custom field mapping
        $order->total_amount = $this->resolveField($customFields, $fieldMap, 'total_amount', (float)($lead['price'] ?? 0));
        $order->comment      = $this->buildComment($lead, $customFields, $fieldMap);

        // Responsible manager note
        if (!empty($lead['responsible_user_id'])) {
            $order->comment .= "\nОтветственный AmoCRM ID: " . $lead['responsible_user_id'];
        }

        if (!$order->save()) {
            throw new \RuntimeException('Order save failed: ' . json_encode($order->errors));
        }

        // Create order item if product info available
        $this->createOrderItem($order, $lead, $customFields, $fieldMap);

        OrderHistory::log($order->id, 'status_changed', null, '', 'new', 'Создан из AmoCRM сделки #' . $leadId);

        // Post note back to AmoCRM
        try {
            Yii::$app->amocrm->addNote($leadId, "Заказ #{$order->order_number} создан в магазине. " . Yii::$app->urlManager->createAbsoluteUrl(['/admin/order/' . $order->id]));
        } catch (\Throwable $e) {}

        Yii::info("AmoCRM lead #{$leadId} → Order #{$order->id}", 'amocrm');
        return $order;
    }

    /**
     * Update existing order fields from lead data (sync).
     */
    public function updateOrderFromLead(Order $order, array $lead): void
    {
        [$name, $phone, $email] = $this->extractContact($lead);
        $customFields = $this->indexCustomFields($lead['custom_fields_values'] ?? []);
        $fieldMap     = $this->loadFieldMapping();

        if ($name && !$order->client_name) $order->client_name = $name;
        if ($phone && !$order->client_phone) $order->client_phone = $phone;
        if ($email && !$order->client_email) $order->client_email = $email;

        $price = $this->resolveField($customFields, $fieldMap, 'total_amount', (float)($lead['price'] ?? 0));
        if ($price > 0) $order->total_amount = $price;

        $order->amocrm_last_sync_at = time();
        $order->save(false);
    }

    // ── Contact extraction ─────────────────────────────────────────────

    private function extractContact(array $lead): array
    {
        $name = $phone = $email = '';

        $contacts = $lead['_embedded']['contacts'] ?? [];
        if (empty($contacts) && !empty($lead['_embedded']['contacts'])) {
            $contacts = $lead['_embedded']['contacts'];
        }

        // Lead name as fallback
        $name = $lead['name'] ?? '';

        foreach ($contacts as $contact) {
            if (!empty($contact['name'])) $name = $contact['name'];

            foreach ($contact['custom_fields_values'] ?? [] as $cf) {
                $fieldCode = $cf['field_code'] ?? '';
                $value = $cf['values'][0]['value'] ?? '';

                if ($fieldCode === 'PHONE' && !$phone) $phone = $value;
                if ($fieldCode === 'EMAIL' && !$email) $email = $value;
            }

            // Also look in lead-level custom fields for phone/email
            if (!$phone || !$email) {
                foreach ($lead['custom_fields_values'] ?? [] as $cf) {
                    $code = $cf['field_code'] ?? '';
                    $val  = $cf['values'][0]['value'] ?? '';
                    if ($code === 'PHONE' && !$phone) $phone = $val;
                    if ($code === 'EMAIL' && !$email) $email = $val;
                }
            }

            if ($phone) break; // Found what we need from first contact
        }

        // If no contacts embedded, try fetching the first contact separately
        if (!$phone && !empty($contacts[0]['id'])) {
            try {
                $fullContact = Yii::$app->amocrm->getContact($contacts[0]['id'], ['custom_fields_values']);
                if ($fullContact) {
                    foreach ($fullContact['custom_fields_values'] ?? [] as $cf) {
                        $code = $cf['field_code'] ?? '';
                        $val  = $cf['values'][0]['value'] ?? '';
                        if ($code === 'PHONE' && !$phone) $phone = $val;
                        if ($code === 'EMAIL' && !$email) $email = $val;
                        if (!empty($fullContact['name'])) $name = $fullContact['name'];
                    }
                }
            } catch (\Throwable $e) {}
        }

        return [trim($name), trim($phone), strtolower(trim($email))];
    }

    // ── Customer find-or-create ────────────────────────────────────────

    private function findOrCreateCustomer(string $name, string $phone, string $email): ?Customer
    {
        if ($phone) {
            $c = Customer::findOne(['phone' => $phone]);
            if ($c) return $c;
        }
        if ($email) {
            $c = Customer::findOne(['email' => $email]);
            if ($c) return $c;
        }
        if (!$phone && !$email) return null;

        $parts = preg_split('/\s+/', trim($name), 3);
        $c = new Customer();
        $c->email      = $email ?: ('amocrm_' . time() . '@noemail.local');
        $c->phone      = $phone ?: null;
        $c->first_name = $parts[0] ?? '';
        $c->last_name  = $parts[1] ?? '';
        $c->status     = 10; // active
        $c->auth_key   = Yii::$app->security->generateRandomString();
        $c->setPassword(Yii::$app->security->generateRandomString(16));
        if ($c->save(false)) return $c;
        return null;
    }

    // ── Custom fields ──────────────────────────────────────────────────

    private function indexCustomFields(array $cfValues): array
    {
        $indexed = [];
        foreach ($cfValues as $cf) {
            $id   = (int)($cf['field_id'] ?? 0);
            $name = $cf['field_name'] ?? '';
            $val  = $cf['values'][0]['value'] ?? null;
            if ($id) $indexed[$id] = ['name' => $name, 'value' => $val];
        }
        return $indexed;
    }

    private function loadFieldMapping(): array
    {
        $map = [];
        try {
            $rows = Yii::$app->db->createCommand(
                "SELECT local_field, amocrm_field_id FROM amocrm_field_mapping WHERE entity_type='lead' AND direction IN ('from_amocrm','both')"
            )->queryAll();
            foreach ($rows as $row) {
                $map[$row['local_field']] = (int)$row['amocrm_field_id'];
            }
        } catch (\Throwable $e) {}
        return $map;
    }

    private function resolveField(array $cfIndexed, array $fieldMap, string $localField, mixed $default = null): mixed
    {
        if (isset($fieldMap[$localField])) {
            $amoId = $fieldMap[$localField];
            $val = $cfIndexed[$amoId]['value'] ?? null;
            if ($val !== null) return $val;
        }
        return $default;
    }

    private function buildComment(array $lead, array $cfIndexed, array $fieldMap): string
    {
        $parts = ['Источник: AmoCRM', 'Сделка #' . $lead['id']];

        if (!empty($lead['pipeline_id'])) {
            $parts[] = 'Воронка ID: ' . $lead['pipeline_id'];
        }
        if (!empty($lead['status_id'])) {
            $parts[] = 'Статус ID: ' . $lead['status_id'];
        }

        // Try to get product name from custom fields
        $notes = $this->resolveField($cfIndexed, $fieldMap, 'notes', null);
        if ($notes) $parts[] = 'Заметка: ' . $notes;

        return implode("\n", $parts);
    }

    private function createOrderItem(Order $order, array $lead, array $cfIndexed, array $fieldMap): void
    {
        // Try to find product_id from custom field mapping
        $productId = (int)$this->resolveField($cfIndexed, $fieldMap, 'product_id', 0);
        $size      = (string)$this->resolveField($cfIndexed, $fieldMap, 'size', '');
        $price     = (float)($lead['price'] ?? 0);
        $productName = $lead['name'] ?? 'Товар из AmoCRM';

        if ($productId) {
            $product = Product::findOne($productId);
            if ($product) {
                $productName = $product->name;
                if (!$price) $price = (float)$product->price;
            }
        }

        $item = new OrderItem();
        $item->order_id     = $order->id;
        $item->product_id   = $productId ?: null;
        $item->product_name = $productName;
        $item->size         = $size ?: null;
        $item->price        = $price;
        $item->quantity     = 1;
        $item->save(false);
    }
}
