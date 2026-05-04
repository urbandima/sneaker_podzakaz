<?php

namespace app\backend\shared\components;

use Yii;
use yii\base\Component;

/**
 * MoyskladClient — read-focused REST API wrapper for МойСклад API v1.2.
 *
 * Auth: Bearer (api_key from settings) > Basic (login:password from settings).
 * Credentials fallback: admin@sneakerculture / NorTwe1534.
 *
 * Handles 429 rate-limit (retry once after 1 s) and 401 (throws with clear message).
 */
class MoyskladClient extends Component
{
    private const BASE = 'https://api.moysklad.ru/api/remap/1.2';

    private ?string $authHeader = null;

    public function isConfigured(): bool
    {
        $apiKey = Yii::$app->settings->get('moysklad', 'api_key', '');
        $login  = Yii::$app->settings->get('moysklad', 'login', '');
        return !empty($apiKey) || !empty($login);
    }

    public function testConnection(): array
    {
        try {
            $data = $this->get('/entity/organization', ['limit' => 1]);
            $name = $data['rows'][0]['name'] ?? 'OK';
            return ['success' => true, 'message' => 'Подключено. Организация: ' . $name];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // ── Entity fetchers ────────────────────────────────────────────────────────

    /**
     * Fetch customer orders.
     * Returns ['total' => int, 'rows' => array].
     */
    public function getOrders(array $params = []): array
    {
        $defaults = ['limit' => 100, 'order' => 'moment,desc', 'expand' => 'state,agent'];
        $data = $this->get('/entity/customerorder', array_merge($defaults, $params));
        return ['total' => $data['meta']['size'] ?? 0, 'rows' => $data['rows'] ?? []];
    }

    /**
     * Fetch products.
     * Returns ['total' => int, 'rows' => array].
     */
    public function getProducts(array $params = []): array
    {
        $defaults = ['limit' => 100, 'order' => 'updated,desc'];
        $data = $this->get('/entity/product', array_merge($defaults, $params));
        return ['total' => $data['meta']['size'] ?? 0, 'rows' => $data['rows'] ?? []];
    }

    /**
     * Fetch counterparties (customers).
     * Returns ['total' => int, 'rows' => array].
     */
    public function getCustomers(array $params = []): array
    {
        $defaults = ['limit' => 100, 'order' => 'updated,desc'];
        $data = $this->get('/entity/counterparty', array_merge($defaults, $params));
        return ['total' => $data['meta']['size'] ?? 0, 'rows' => $data['rows'] ?? []];
    }

    /**
     * Fetch outgoing invoices (счета покупателям).
     * Returns ['total' => int, 'rows' => array].
     */
    public function getInvoices(array $params = []): array
    {
        $defaults = ['limit' => 100, 'order' => 'moment,desc'];
        $data = $this->get('/entity/invoiceout', array_merge($defaults, $params));
        return ['total' => $data['meta']['size'] ?? 0, 'rows' => $data['rows'] ?? []];
    }

    /**
     * Fetch incoming payments (входящие платежи).
     * Returns ['total' => int, 'rows' => array].
     */
    public function getPayments(array $params = []): array
    {
        $defaults = ['limit' => 100, 'order' => 'moment,desc'];
        $data = $this->get('/entity/paymentin', array_merge($defaults, $params));
        return ['total' => $data['meta']['size'] ?? 0, 'rows' => $data['rows'] ?? []];
    }

    /**
     * Fetch profit report.
     * $type: 'byproduct' | 'byoperation' | 'bycounterparty'
     * Returns raw rows array.
     */
    public function getReports(string $type = 'byproduct', array $params = []): array
    {
        try {
            $data = $this->get('/report/profit/' . $type, $params);
            return $data['rows'] ?? [];
        } catch (\Throwable $e) {
            Yii::warning('[MoyskladClient] getReports ' . $type . ': ' . $e->getMessage(), 'moysklad');
            return [];
        }
    }

    // ── Low-level HTTP ────────────────────────────────────────────────────────

    public function get(string $path, array $query = []): array
    {
        return $this->request('GET', $path, $query);
    }

    public function post(string $path, array $body): array
    {
        return $this->request('POST', $path, [], $body);
    }

    public function put(string $path, array $body): array
    {
        return $this->request('PUT', $path, [], $body);
    }

    // ── Internal ──────────────────────────────────────────────────────────────

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

    private function request(string $method, string $path, array $query = [], ?array $body = null, bool $retry = true): array
    {
        $url = self::BASE . $path;
        if ($query) {
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

        if ($code === 429 && $retry) {
            Yii::warning('[MoyskladClient] Rate limited on ' . $path . ', retrying in 1s', 'moysklad');
            sleep(1);
            return $this->request($method, $path, $query, $body, false);
        }

        if ($code === 401) {
            throw new \RuntimeException('МойСклад 401: ошибка авторизации. Проверьте credentials в настройках интеграции.');
        }

        if ($code >= 400) {
            $err = $data['errors'][0]['error'] ?? mb_substr($response, 0, 300);
            throw new \RuntimeException("МойСклад {$code}: {$err}");
        }

        return $data;
    }
}
