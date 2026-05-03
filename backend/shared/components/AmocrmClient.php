<?php

namespace app\backend\shared\components;

use Yii;
use yii\base\Component;

/**
 * AmocrmClient — REST API wrapper for AmoCRM v4.
 *
 * Auth priority:
 *   1. AMOCRM_LONG_TOKEN env var (long-lived token, valid until 2029)
 *   2. access_token from settings table (OAuth flow, with auto-refresh on 401)
 *
 * Domain: AMOCRM_API_DOMAIN env var or settings[amocrm][domain]
 */
class AmocrmClient extends Component
{
    private ?string $domain        = null;
    private ?string $clientId      = null;
    private ?string $clientSecret  = null;
    private ?string $accessToken   = null;
    private ?string $refreshToken  = null;
    private bool    $usingLongToken = false;

    public function init(): void
    {
        parent::init();

        // Prefer env-based long token (valid until 2029, no refresh needed)
        $longToken = getenv('AMOCRM_LONG_TOKEN') ?: null;
        $envDomain = getenv('AMOCRM_API_DOMAIN') ?: null;

        if ($longToken) {
            $this->accessToken    = $longToken;
            $this->usingLongToken = true;
            $this->domain         = $envDomain ?: Yii::$app->settings->get('amocrm', 'domain', '');
        } else {
            $s = Yii::$app->settings;
            $this->domain         = $envDomain ?: $s->get('amocrm', 'domain', '');
            $this->clientId       = getenv('AMOCRM_INTEGRATION_ID') ?: $s->get('amocrm', 'client_id', '');
            $this->clientSecret   = getenv('AMOCRM_SECRET_KEY') ?: $s->get('amocrm', 'client_secret', '');
            $this->accessToken    = $s->get('amocrm', 'access_token', '');
            $this->refreshToken   = $s->get('amocrm', 'refresh_token', '');
        }
    }

    public function isConfigured(): bool
    {
        return !empty($this->domain) && !empty($this->accessToken);
    }

    // ── Leads ──────────────────────────────────────────────────────────

    public function getLeads(array $params = []): array
    {
        return $this->request('GET', '/api/v4/leads', $params) ?? [];
    }

    /**
     * Fetch a single lead. Pass $with to embed related entities.
     * Example: getLead(123, ['contacts', 'custom_fields_values'])
     */
    public function getLead(int $id, array $with = []): ?array
    {
        $query = $with ? ['with' => implode(',', $with)] : [];
        return $this->request('GET', "/api/v4/leads/{$id}", $query);
    }

    public function createLead(array $data): ?array
    {
        $result = $this->request('POST', '/api/v4/leads', [], [$data]);
        return $result['_embedded']['leads'][0] ?? null;
    }

    public function updateLead(int $id, array $data): ?array
    {
        return $this->request('PATCH', "/api/v4/leads/{$id}", [], $data);
    }

    // ── Contacts ───────────────────────────────────────────────────────

    public function getContact(int $id, array $with = []): ?array
    {
        $query = $with ? ['with' => implode(',', $with)] : [];
        return $this->request('GET', "/api/v4/contacts/{$id}", $query);
    }

    public function findContactByPhone(string $phone): ?array
    {
        $result = $this->request('GET', '/api/v4/contacts', ['query' => $phone]);
        return $result['_embedded']['contacts'][0] ?? null;
    }

    public function createContact(array $data): ?array
    {
        $result = $this->request('POST', '/api/v4/contacts', [], [$data]);
        return $result['_embedded']['contacts'][0] ?? null;
    }

    // ── Pipelines ──────────────────────────────────────────────────────

    public function getPipelines(): array
    {
        $result = $this->request('GET', '/api/v4/leads/pipelines');
        return $result['_embedded']['pipelines'] ?? [];
    }

    public function getPipelineStatuses(int $pipelineId): array
    {
        $result = $this->request('GET', "/api/v4/leads/pipelines/{$pipelineId}/statuses");
        return $result['_embedded']['statuses'] ?? [];
    }

    /**
     * Pipelines with their statuses, flattened into a stable shape for UI/admin tools.
     *
     * Returns null when AmoCRM is not configured (no domain/access_token), distinguishing
     * "not set up" from "set up but empty list".
     *
     * @return array<int,array{id:int,name:string,statuses:array<int,array{id:int,name:string}>}>|null
     */
    public function getPipelinesWithStatuses(): ?array
    {
        $raw = $this->request('GET', '/api/v4/leads/pipelines', ['limit' => 50]);
        if ($raw === null) return null;

        $out = [];
        foreach ($raw['_embedded']['pipelines'] ?? [] as $p) {
            $statuses = [];
            foreach ($p['_embedded']['statuses'] ?? [] as $s) {
                $statuses[] = ['id' => (int)$s['id'], 'name' => (string)$s['name']];
            }
            $out[] = [
                'id'       => (int)$p['id'],
                'name'     => (string)$p['name'],
                'statuses' => $statuses,
            ];
        }
        return $out;
    }

    // ── Custom Fields ──────────────────────────────────────────────────

    /**
     * Get all custom fields for an entity type.
     * $entityType: 'leads' | 'contacts' | 'companies'
     */
    public function getCustomFields(string $entityType = 'leads'): array
    {
        $result = $this->request('GET', "/api/v4/{$entityType}/custom_fields");
        return $result['_embedded']['custom_fields'] ?? [];
    }

    // ── Account ────────────────────────────────────────────────────────

    public function getAccount(): ?array
    {
        return $this->request('GET', '/api/v4/account');
    }

    // ── Users ──────────────────────────────────────────────────────────

    public function getUsers(): array
    {
        $result = $this->request('GET', '/api/v4/users');
        return $result['_embedded']['users'] ?? [];
    }

    // ── Notes ──────────────────────────────────────────────────────────

    public function addNote(int $leadId, string $text): void
    {
        $this->request('POST', '/api/v4/leads/notes', [], [[
            'entity_id' => $leadId,
            'note_type' => 'common',
            'params'    => ['text' => $text],
        ]]);
    }

    // ── OAuth Token Refresh ────────────────────────────────────────────

    public function refreshAccessToken(): bool
    {
        if ($this->usingLongToken) {
            return false; // long tokens don't refresh
        }
        if (!$this->refreshToken || !$this->clientId || !$this->clientSecret || !$this->domain) {
            return false;
        }
        $url = 'https://' . $this->domain . '/oauth2/access_token';
        $payload = json_encode([
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'grant_type'    => 'refresh_token',
            'refresh_token' => $this->refreshToken,
            'redirect_uri'  => Yii::$app->settings->get('amocrm', 'redirect_uri', ''),
        ]);
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
        ]);
        $body = curl_exec($ch);
        curl_close($ch);
        $data = json_decode($body, true);
        if (!empty($data['access_token'])) {
            $this->accessToken  = $data['access_token'];
            $this->refreshToken = $data['refresh_token'];
            $s = Yii::$app->settings;
            $s->set('amocrm', 'access_token',  $data['access_token']);
            $s->set('amocrm', 'refresh_token', $data['refresh_token']);
            $expAt = time() + ((int)($data['expires_in'] ?? 86400));
            $s->set('amocrm', 'token_expires_at', (string)$expAt);
            return true;
        }
        return false;
    }

    // ── Internal HTTP ──────────────────────────────────────────────────

    private function request(string $method, string $path, array $query = [], $body = null, bool $retry = true): ?array
    {
        if (!$this->domain) return null;
        $url = 'https://' . $this->domain . $path;
        if ($query) $url .= '?' . http_build_query($query);

        $t0 = microtime(true);
        $ch = curl_init($url);
        $headers = [
            'Authorization: Bearer ' . $this->accessToken,
            'Content-Type: application/json',
        ];
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
        ]);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }
        $responseBody = curl_exec($ch);
        $curlErrno = curl_errno($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $ms        = (int)((microtime(true) - $t0) * 1000);
        curl_close($ch);

        if ($curlErrno) {
            Yii::warning('[AmoCRM] curl error ' . $curlErrno . ' for ' . $method . ' ' . $path, 'amocrm');
            $this->logRequest($method . ' ' . $path, 'fail', $body ? json_encode($body) : null, 'curl_errno=' . $curlErrno, $ms);
            return null;
        }

        $this->logRequest($method . ' ' . $path, $httpCode < 400 ? 'ok' : 'fail', $body ? json_encode($body) : null, $responseBody, $ms);

        // Rate limit: back off and retry once
        if ($httpCode === 429 && $retry) {
            Yii::warning('[AmoCRM] Rate limited on ' . $method . ' ' . $path . ', retrying in 1s', 'amocrm');
            sleep(1);
            return $this->request($method, $path, $query, $body, false);
        }

        if ($httpCode === 401 && $retry && !$this->usingLongToken) {
            if ($this->refreshAccessToken()) {
                return $this->request($method, $path, $query, $body, false);
            }
            return null;
        }

        if ($httpCode >= 500) {
            Yii::warning('[AmoCRM] Server error ' . $httpCode . ' for ' . $method . ' ' . $path, 'amocrm');
            return null;
        }

        return json_decode($responseBody, true);
    }

    private function logRequest(string $event, string $status, ?string $payload, ?string $response, int $ms): void
    {
        try {
            Yii::$app->db->createCommand()->insert('{{%amocrm_log}}', [
                'direction'   => 'outgoing',
                'event'       => $event,
                'status'      => $status,
                'payload'     => mb_substr($payload ?? '', 0, 4096),
                'response'    => mb_substr($response ?? '', 0, 4096),
                'response_ms' => $ms,
                'created_at'  => time(),
            ])->execute();
        } catch (\Exception $e) {
            // Table may not exist yet during migration
        }
    }
}
