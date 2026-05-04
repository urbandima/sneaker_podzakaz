<?php

namespace app\backend\shared\services;

use Yii;
use yii\base\Component;
use app\backend\modules\checkout\models\Order;

/**
 * МойСклад API v1.2 bidirectional sync service.
 *
 * Auth: Basic (login:password) or Bearer (api_key from settings).
 * API: https://api.moysklad.ru/api/remap/1.2/
 *
 * Settings keys used:
 *   moysklad.login, moysklad.password  — Basic auth credentials (configured via admin settings)
 *   moysklad.api_key                   — Bearer token (overrides Basic if set)
 *   moysklad.org_id                    — cached organization UUID
 *   moysklad.status_map_*              — status name mapping (our_status → MS state name)
 */
class MoySkladService extends Component
{
    private const BASE = 'https://api.moysklad.ru/api/remap/1.2';

    private ?string $authHeader = null;

    /** @var array<string,array> cached MS state metas keyed by lowercase name */
    private array $stateCache = [];

    /** @var array|null cached org meta */
    private ?array $orgMeta = null;

    // ─── Auth ─────────────────────────────────────────────────────────────────

    private function getAuth(): string
    {
        if ($this->authHeader !== null) {
            return $this->authHeader;
        }

        $apiKey = Yii::$app->settings->get('moysklad', 'api_key', '');
        if (!empty($apiKey)) {
            $this->authHeader = 'Bearer ' . $apiKey;
        } else {
            $login    = Yii::$app->settings->get('moysklad', 'login',    'admin@sneakerculture');
            $password = Yii::$app->settings->get('moysklad', 'password', 'NorTwe1534');
            $this->authHeader = 'Basic ' . base64_encode("{$login}:{$password}");
        }

        return $this->authHeader;
    }

    // ─── HTTP layer ───────────────────────────────────────────────────────────

    /**
     * @param array<string,mixed> $query
     * @param array<string,mixed>|null $body
     * @return array<string,mixed>
     * @throws \RuntimeException on HTTP error
     */
    public function request(string $method, string $path, ?array $body = null, array $query = []): array
    {
        $url = self::BASE . $path;
        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }

        $headers = [
            'Authorization: ' . $this->getAuth(),
            'Accept: application/json',
        ];
        if ($body !== null) {
            $headers[] = 'Content-Type: application/json';
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_CUSTOMREQUEST  => strtoupper($method),
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_ENCODING       => 'gzip',
        ]);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE));
        }

        $response = curl_exec($ch);
        $code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($response === false || $code === 0) {
            throw new \RuntimeException('МойСклад: сетевая ошибка — ' . $curlErr);
        }

        $data = json_decode($response, true) ?? [];

        if ($code >= 400) {
            $err = $data['errors'][0]['error'] ?? mb_substr($response, 0, 300);
            throw new \RuntimeException("МойСклад {$code}: {$err}");
        }

        return $data;
    }

    // ─── Org / State helpers ──────────────────────────────────────────────────

    public function getOrgMeta(): array
    {
        if ($this->orgMeta !== null) {
            return $this->orgMeta;
        }

        $cachedId = Yii::$app->settings->get('moysklad', 'org_id', '');
        if ($cachedId) {
            $this->orgMeta = [
                'href'      => self::BASE . '/entity/organization/' . $cachedId,
                'type'      => 'organization',
                'mediaType' => 'application/json',
            ];
            return $this->orgMeta;
        }

        $data = $this->request('GET', '/entity/organization', null, ['limit' => 1]);
        $org  = $data['rows'][0] ?? null;
        if (!$org) {
            throw new \RuntimeException('МойСклад: организация не найдена');
        }

        $orgId = $org['id'];
        Yii::$app->settings->set('moysklad', 'org_id', $orgId);

        $this->orgMeta = $org['meta'];
        return $this->orgMeta;
    }

    /**
     * Load all customerorder states into cache.
     */
    private function loadStates(): void
    {
        if (!empty($this->stateCache)) {
            return;
        }

        $data = $this->request('GET', '/entity/customerorder/metadata');
        foreach ($data['states'] ?? [] as $state) {
            $this->stateCache[mb_strtolower($state['name'])] = [
                'id'   => $state['id'],
                'name' => $state['name'],
                'meta' => $state['meta'],
            ];
        }
    }

    private function getStateMeta(string $stateName): ?array
    {
        $this->loadStates();
        $key = mb_strtolower($stateName);
        return isset($this->stateCache[$key]) ? $this->stateCache[$key]['meta'] : null;
    }

    // ─── Status mapping ───────────────────────────────────────────────────────

    /** Default mapping: our status → МС state name */
    private const DEFAULT_STATUS_MAP = [
        'new'                    => 'Новый',
        'created'                => 'Новый',
        'paid'                   => 'Оплачен',
        'confirmed_and_paid'     => 'Оплачен',
        'confirmed'              => 'Подтвержден',
        'ordered'                => 'Заказано у поставщика',
        'awaiting_warehouse'     => 'Заказано у поставщика',
        'international_delivery' => 'В международной доставке',
        'at_warehouse'           => 'На складе',
        'local_delivery'         => 'В доставке',
        'delivered'              => 'Выдан',
        'canceled'               => 'Отменен',
        'return'                 => 'Возврат',
    ];

    public function mapStatusToMs(string $ourStatus): ?array
    {
        $custom = Yii::$app->settings->get('moysklad', 'status_map_' . $ourStatus, '');
        $msName = $custom ?: (self::DEFAULT_STATUS_MAP[$ourStatus] ?? 'Новый');
        return $this->getStateMeta($msName);
    }

    /**
     * Reverse map: МС state name → our status.
     */
    public function mapStatusFromMs(string $msStateName): string
    {
        $flipped = array_flip(self::DEFAULT_STATUS_MAP);
        $lower   = mb_strtolower($msStateName);
        foreach ($flipped as $msName => $ourStatus) {
            if (mb_strtolower($msName) === $lower) {
                return $ourStatus;
            }
        }
        return '';
    }

    // ─── Counterparty (customer) ──────────────────────────────────────────────

    /**
     * Find existing counterparty by phone/email, or create a new one.
     */
    public function findOrCreateCounterparty(Order $order): array
    {
        $phone = preg_replace('/\D/', '', $order->client_phone ?? '');
        $email = trim($order->client_email ?? '');
        $name  = trim($order->client_name ?? 'Покупатель');

        // Try find by phone
        if ($phone) {
            $data = $this->request('GET', '/entity/counterparty', null, [
                'filter' => 'phone=' . $order->client_phone,
                'limit'  => 1,
            ]);
            if (!empty($data['rows'])) {
                return $data['rows'][0];
            }
        }

        // Try find by email
        if ($email) {
            $data = $this->request('GET', '/entity/counterparty', null, [
                'filter' => 'email=' . $email,
                'limit'  => 1,
            ]);
            if (!empty($data['rows'])) {
                return $data['rows'][0];
            }
        }

        // Create new counterparty
        $payload = ['name' => $name];
        if ($email) $payload['email'] = $email;
        if ($order->client_phone) $payload['phone'] = $order->client_phone;
        if ($order->delivery_address) $payload['actualAddress'] = $order->delivery_address;

        return $this->request('POST', '/entity/counterparty', $payload);
    }

    // ─── Push: Our order → МС ─────────────────────────────────────────────────

    /**
     * Push one order to МойСклад (create or update).
     */
    public function pushOrder(Order $order): array
    {
        $agent   = $this->findOrCreateCounterparty($order);
        $orgMeta = $this->getOrgMeta();

        $payload = [
            'name'         => $order->order_number ?: ('ORD-' . $order->id),
            'organization' => ['meta' => $orgMeta],
            'agent'        => ['meta' => $agent['meta']],
            'description'  => 'Заказ с сайта. Клиент: ' . ($order->client_name ?? '') . '. Телефон: ' . ($order->client_phone ?? ''),
            'sum'          => (int)(($order->total_amount ?? 0) * 100), // in kopecks
        ];

        // Map status to MS state
        $stateMeta = $this->mapStatusToMs($order->status ?? 'new');
        if ($stateMeta) {
            $payload['state'] = ['meta' => $stateMeta];
        }

        // Delivery address as shipping address
        if ($order->delivery_address) {
            $payload['shipmentAddress'] = $order->delivery_address;
        }

        if ($order->moysklad_id) {
            // UPDATE existing order — fallback to CREATE if not found in МС
            try {
                $result = $this->request('PUT', '/entity/customerorder/' . $order->moysklad_id, $payload);
            } catch (\RuntimeException $e) {
                if (str_contains($e->getMessage(), '404') || str_contains($e->getMessage(), 'Объект не найден') || str_contains($e->getMessage(), 'Not Found')) {
                    // Order was deleted in МС — clear stale ID and create new
                    Yii::warning('МойСклад order ' . $order->moysklad_id . ' not found, creating new. Error: ' . $e->getMessage(), 'moysklad');
                    Order::updateAll(['moysklad_id' => null], ['id' => $order->id]);
                    $order->moysklad_id = null;
                    $result = $this->request('POST', '/entity/customerorder', $payload);
                    if (!empty($result['id'])) {
                        Order::updateAll(['moysklad_id' => $result['id']], ['id' => $order->id]);
                        $order->moysklad_id = $result['id'];
                    }
                } else {
                    throw $e;
                }
            }
        } else {
            // CREATE new order
            $result = $this->request('POST', '/entity/customerorder', $payload);
            if (!empty($result['id'])) {
                Order::updateAll(['moysklad_id' => $result['id']], ['id' => $order->id]);
                $order->moysklad_id = $result['id'];
            }
        }

        return $result;
    }

    /**
     * Push all unsynced orders (no moysklad_id) in batches.
     */
    public function pushAllOrders(int $limit = 50): array
    {
        $orders = Order::find()
            ->where(['moysklad_id' => null])
            ->andWhere(['NOT IN', 'status', ['imported', 'canceled']])
            ->orderBy(['id' => SORT_DESC])
            ->limit($limit)
            ->all();

        $pushed  = 0;
        $errors  = 0;
        $log     = [];

        foreach ($orders as $order) {
            try {
                $this->pushOrder($order);
                $pushed++;
                $log[] = ['id' => $order->id, 'number' => $order->order_number, 'status' => 'pushed'];
            } catch (\Throwable $e) {
                $errors++;
                $log[] = ['id' => $order->id, 'number' => $order->order_number, 'status' => 'error', 'message' => $e->getMessage()];
                Yii::error('MoySklad pushOrder #' . $order->id . ': ' . $e->getMessage(), 'moysklad');
            }
        }

        return ['pushed' => $pushed, 'errors' => $errors, 'total' => count($orders), 'log' => $log];
    }

    // ─── Pull: МС → Our system ────────────────────────────────────────────────

    /**
     * Pull orders from МойСклад and sync status/moysklad_id into our DB.
     */
    public function pullOrders(int $limit = 100, int $offset = 0): array
    {
        $data = $this->request('GET', '/entity/customerorder', null, [
            'limit'  => $limit,
            'offset' => $offset,
            'order'  => 'updated,desc',
            'expand' => 'state,agent',
        ]);

        $total  = $data['meta']['size'] ?? 0;
        $synced = 0;
        $new    = 0;
        $log    = [];

        foreach ($data['rows'] ?? [] as $msOrder) {
            $msId   = $msOrder['id'];
            $msName = $msOrder['name'] ?? '';

            // Find our order by moysklad_id first, then by order_number
            $order = Order::find()->where(['moysklad_id' => $msId])->one()
                  ?: Order::find()->where(['order_number' => $msName])->one();

            if (!$order) {
                $log[] = ['ms_id' => $msId, 'name' => $msName, 'status' => 'no_match'];
                continue;
            }

            $changed = false;

            // Update moysklad_id if missing
            if (!$order->moysklad_id) {
                $order->moysklad_id = $msId;
                $changed = true;
                $new++;
            }

            // Sync status
            $msStateName = $msOrder['state']['name'] ?? '';
            if ($msStateName) {
                $ourStatus = $this->mapStatusFromMs($msStateName);
                if ($ourStatus && $order->status !== $ourStatus) {
                    $order->status = $ourStatus;
                    $changed = true;
                }
            }

            if ($changed) {
                $order->save(false);
                $synced++;
            }

            $log[] = [
                'ms_id'  => $msId,
                'name'   => $msName,
                'status' => $changed ? 'updated' : 'unchanged',
                'our_id' => $order->id,
            ];
        }

        return [
            'ms_total' => $total,
            'processed' => count($data['rows'] ?? []),
            'synced'    => $synced,
            'new_links' => $new,
            'log'       => $log,
        ];
    }

    /**
     * Pull products from МойСклад (for catalog import reference).
     */
    public function pullProducts(int $limit = 100): array
    {
        $data = $this->request('GET', '/entity/product', null, [
            'limit' => $limit,
            'order' => 'updated,desc',
        ]);

        $rows = [];
        foreach ($data['rows'] ?? [] as $p) {
            $rows[] = [
                'id'          => $p['id'],
                'name'        => $p['name'] ?? '',
                'code'        => $p['code'] ?? '',
                'article'     => $p['article'] ?? '',
                'sale_price'  => isset($p['salePrices'][0]['value']) ? $p['salePrices'][0]['value'] / 100 : 0,
                'description' => $p['description'] ?? '',
            ];
        }

        return ['total' => $data['meta']['size'] ?? 0, 'rows' => $rows];
    }

    // ─── Webhook ──────────────────────────────────────────────────────────────

    /**
     * List registered webhooks.
     */
    public function listWebhooks(): array
    {
        $data = $this->request('GET', '/entity/webhook');
        return $data['rows'] ?? [];
    }

    /**
     * Register a webhook for customerorder changes.
     * @param string $url Full URL of our webhook receiver endpoint
     */
    public function registerWebhook(string $url): array
    {
        $payload = [
            'url'        => $url,
            'action'     => 'UPDATE',
            'entityType' => 'customerorder',
        ];
        return $this->request('POST', '/entity/webhook', $payload);
    }

    /**
     * Delete a webhook by ID.
     */
    public function deleteWebhook(string $webhookId): void
    {
        $this->request('DELETE', '/entity/webhook/' . $webhookId);
    }

    // ─── Push by ID ───────────────────────────────────────────────────────────

    /**
     * Push a single order to МойСклад by our DB order ID.
     */
    public function pushOrderToMS(int $orderId): array
    {
        $order = Order::findOne($orderId);
        if (!$order) {
            throw new \RuntimeException("Заказ #{$orderId} не найден");
        }
        return $this->pushOrder($order);
    }

    // ─── Periodic sync ────────────────────────────────────────────────────────

    /**
     * Pull orders from МС that were updated in the last $sinceMinutes minutes.
     * Uses filter=updated>={datetime} to limit the request.
     */
    public function periodicSync(int $sinceMinutes = 60): array
    {
        $since = date('Y-m-d H:i:s', time() - $sinceMinutes * 60);

        $data = $this->request('GET', '/entity/customerorder', null, [
            'limit'  => 1000,
            'order'  => 'updated,desc',
            'expand' => 'state,agent',
            'filter' => 'updated>=' . $since,
        ]);

        $total  = $data['meta']['size'] ?? 0;
        $synced = 0;
        $new    = 0;
        $log    = [];

        foreach ($data['rows'] ?? [] as $msOrder) {
            $msId   = $msOrder['id'];
            $msName = $msOrder['name'] ?? '';

            $order = Order::find()->where(['moysklad_id' => $msId])->one()
                  ?: Order::find()->where(['order_number' => $msName])->one();

            if (!$order) {
                $log[] = ['ms_id' => $msId, 'name' => $msName, 'status' => 'no_match'];
                continue;
            }

            $changed = false;

            if (!$order->moysklad_id) {
                $order->moysklad_id = $msId;
                $changed = true;
                $new++;
            }

            $msStateName = $msOrder['state']['name'] ?? '';
            if ($msStateName) {
                $ourStatus = $this->mapStatusFromMs($msStateName);
                if ($ourStatus && $order->status !== $ourStatus) {
                    $order->status = $ourStatus;
                    $changed = true;
                }
            }

            // Sync payment status from payedSum
            if (isset($msOrder['payedSum']) && $msOrder['payedSum'] > 0) {
                \Yii::$app->db->createCommand(
                    "UPDATE `order` SET ms_payed_sum = :v WHERE id = :id",
                    [':v' => round($msOrder['payedSum'] / 100, 2), ':id' => $order->id]
                )->execute();
            }

            if ($changed) {
                $order->save(false);
                $synced++;
            }

            $log[] = [
                'ms_id'  => $msId,
                'name'   => $msName,
                'status' => $changed ? 'updated' : 'unchanged',
                'our_id' => $order->id,
            ];
        }

        Yii::$app->settings->set('moysklad', 'last_sync_at', date('Y-m-d H:i:s'));

        return [
            'ms_total'  => $total,
            'processed' => count($data['rows'] ?? []),
            'synced'    => $synced,
            'new_links' => $new,
            'since'     => $since,
            'log'       => $log,
        ];
    }

    /**
     * Return current webhook status: 'registered' if at least one webhook for
     * our customerorder UPDATE exists, 'not_registered' otherwise.
     */
    public function getWebhookStatus(): string
    {
        try {
            $hooks = $this->listWebhooks();
            foreach ($hooks as $hook) {
                if (($hook['entityType'] ?? '') === 'customerorder' && ($hook['action'] ?? '') === 'UPDATE') {
                    return 'registered';
                }
            }
            return 'not_registered';
        } catch (\Throwable) {
            return 'unknown';
        }
    }

    /**
     * Sync a single order from МС → our DB using its moysklad_id.
     * Called e.g. from the webhook handler after an UPDATE event.
     */
    public function syncOrderFromMS(string $msId): array
    {
        $data        = $this->request('GET', '/entity/customerorder/' . $msId, null, ['expand' => 'state,agent']);
        $msStateName = $data['state']['name'] ?? '';
        $payedSum    = isset($data['payedSum']) ? round($data['payedSum'] / 100, 2) : null;
        $shippedSum  = isset($data['shippedSum']) ? round($data['shippedSum'] / 100, 2) : null;

        $order = Order::find()->where(['moysklad_id' => $msId])->one();
        if (!$order) {
            return ['status' => 'no_match', 'ms_id' => $msId];
        }

        $changed = false;

        if ($msStateName) {
            $ourStatus = $this->mapStatusFromMs($msStateName);
            if ($ourStatus && $order->status !== $ourStatus) {
                $order->status = $ourStatus;
                $changed = true;
            }
        }

        // Update financial fields
        $updates = [];
        if ($payedSum !== null)   $updates['ms_payed_sum']   = $payedSum;
        if ($shippedSum !== null) $updates['ms_shipped_sum'] = $shippedSum;
        if (!empty($updates)) {
            \Yii::$app->db->createCommand()->update('order', $updates, ['id' => $order->id])->execute();
        }

        if ($changed) {
            $order->save(false);
        }

        return [
            'status'     => $changed ? 'updated' : 'unchanged',
            'ms_id'      => $msId,
            'our_id'     => $order->id,
            'ms_status'  => $msStateName,
            'our_status' => $order->status,
        ];
    }

    // ─── Test / Diagnostics ───────────────────────────────────────────────────

    /**
     * Test connection — returns organization name.
     */
    public function testConnection(): string
    {
        $data = $this->request('GET', '/entity/organization', null, ['limit' => 1]);
        $name = $data['rows'][0]['name'] ?? '?';
        return 'Подключено. Организация: ' . $name;
    }

    /**
     * Return org name, order count and status list for the sync settings page.
     */
    public function getSyncInfo(): array
    {
        $org      = $this->request('GET', '/entity/organization', null, ['limit' => 1]);
        $orders   = $this->request('GET', '/entity/customerorder', null, ['limit' => 1]);
        $this->loadStates();

        return [
            'org_name'    => $org['rows'][0]['name'] ?? '?',
            'order_count' => $orders['meta']['size'] ?? 0,
            'states'      => array_values($this->stateCache),
        ];
    }
}
