<?php

namespace app\backend\modules\checkout\services;

use yii\base\Component;
use Yii;

/**
 * CdekTrackingService — трекинг отправлений через СДЭК (API v2)
 *
 * Конфигурация через настройки плагина (settings: plugin_cdek_config):
 *   active        — bool, включён ли плагин
 *   client_id     — Client ID из личного кабинета СДЭК
 *   client_secret — Client Secret из личного кабинета СДЭК
 *
 * Регистрация в web.php:
 *   'cdekTracking' => ['class' => CdekTrackingService::class]
 */
class CdekTrackingService extends Component
{
    private const API_BASE = 'https://api.cdek.ru/v2';
    private const TOKEN_CACHE_KEY = 'cdek_oauth_token';

    public function getStatus(string $trackNumber): array
    {
        $config = $this->getConfig();
        if (empty($config['active'])) {
            return ['status' => 'not_configured', 'message' => 'СДЭК не настроен'];
        }
        try {
            $token = $this->getToken($config);
            if (!$token) {
                return ['status' => 'error', 'message' => 'Не удалось получить токен СДЭК'];
            }
            $url = self::API_BASE . '/orders?im_number=' . urlencode($trackNumber);
            $ctx = stream_context_create(['http' => [
                'timeout' => 10,
                'header'  => "Authorization: Bearer {$token}\r\nContent-Type: application/json\r\n",
            ]]);
            $response = @file_get_contents($url, false, $ctx);
            if ($response === false) {
                return ['status' => 'error', 'message' => 'Не удалось получить данные от СДЭК'];
            }
            $data = json_decode($response, true);
            return $this->parseResponse($data ?: [], $trackNumber);
        } catch (\Exception $e) {
            Yii::warning('CDEK tracking error: ' . $e->getMessage(), 'tracking');
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    private function getToken(array $config): ?string
    {
        $cached = Yii::$app->cache->get(self::TOKEN_CACHE_KEY);
        if ($cached) {
            return $cached;
        }
        $clientId     = $config['client_id'] ?? '';
        $clientSecret = $config['client_secret'] ?? '';
        if (!$clientId || !$clientSecret) {
            return null;
        }
        $postData = http_build_query([
            'grant_type'    => 'client_credentials',
            'client_id'     => $clientId,
            'client_secret' => $clientSecret,
        ]);
        $ctx = stream_context_create(['http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => $postData,
            'timeout' => 10,
        ]]);
        $response = @file_get_contents(self::API_BASE . '/oauth/token', false, $ctx);
        if (!$response) {
            return null;
        }
        $data = json_decode($response, true);
        $token = $data['access_token'] ?? null;
        if ($token) {
            $ttl = ($data['expires_in'] ?? 3600) - 60;
            Yii::$app->cache->set(self::TOKEN_CACHE_KEY, $token, $ttl);
        }
        return $token;
    }

    private function parseResponse(array $data, string $trackNumber): array
    {
        $entity = $data['entity'] ?? null;
        if (!$entity) {
            return ['status' => 'not_found', 'message' => 'Отправление не найдено', 'track_number' => $trackNumber];
        }
        $statuses = $entity['statuses'] ?? [];
        $lastStatus = !empty($statuses) ? end($statuses) : null;
        return [
            'status'      => $lastStatus['code'] ?? 'unknown',
            'status_name' => $lastStatus['name'] ?? 'Неизвестно',
            'status_date' => $lastStatus['date_time'] ?? null,
            'location'    => $lastStatus['city'] ?? null,
            'track_number' => $trackNumber,
            'history'     => $statuses,
        ];
    }

    public function isConfigured(): bool
    {
        $config = $this->getConfig();
        return !empty($config['active']) && !empty($config['client_id']) && !empty($config['client_secret']);
    }

    private function getConfig(): array
    {
        $json = Yii::$app->settings->get('plugin', 'cdek_config', '{}');
        return is_array($json) ? $json : (json_decode($json, true) ?: []);
    }
}
