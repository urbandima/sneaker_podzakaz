<?php

namespace app\backend\modules\checkout\services;

use yii\base\Component;
use Yii;

/**
 * BelpochtaTrackingService — трекинг отправлений через Белпочту
 *
 * Конфигурация через настройки плагина (settings: plugin_belpochta_config):
 *   active  — bool, включён ли плагин
 *
 * Регистрация в web.php:
 *   'belpochtaTracking' => ['class' => BelpochtaTrackingService::class]
 */
class BelpochtaTrackingService extends Component
{
    public function getStatus(string $trackNumber): array
    {
        $config = $this->getConfig();
        if (empty($config['active'])) {
            return ['status' => 'not_configured', 'message' => 'Белпочта не настроена'];
        }
        try {
            // Belpost public tracking API: POST (no auth required)
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => 'https://api.belpost.by/api/v1/tracking',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 15,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => json_encode(['number' => $trackNumber]),
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'Accept: application/json',
                ],
                CURLOPT_SSL_VERIFYPEER => true,
            ]);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlErr  = curl_error($ch);
            curl_close($ch);

            if ($curlErr) {
                return ['status' => 'error', 'message' => 'cURL ошибка: ' . $curlErr];
            }
            if ($httpCode >= 400) {
                return ['status' => 'error', 'message' => 'Белпочта API вернула HTTP ' . $httpCode];
            }
            $data = json_decode($response, true);
            return $this->parseResponse($data ?: [], $trackNumber);
        } catch (\Exception $e) {
            Yii::warning('Belpochta tracking error: ' . $e->getMessage(), 'tracking');
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    private function parseResponse(array $data, string $trackNumber): array
    {
        if (empty($data)) {
            return ['status' => 'not_found', 'message' => 'Отправление не найдено', 'track_number' => $trackNumber];
        }
        // Real API: { "data": [ { "number": "...", "steps": [ { "code": N, "event": "...", "created_at": "...", "place": "..." }, ... ] } ] }
        // Steps are sorted newest-first
        $steps = $data['data'][0]['steps'] ?? [];

        if (empty($steps)) {
            return ['status' => 'not_found', 'message' => 'Отправление не найдено', 'track_number' => $trackNumber];
        }

        // First step is most recent
        $latest = $steps[0];

        // Normalize history
        $history = [];
        foreach ($steps as $s) {
            $history[] = [
                'status_code' => (string)($s['code'] ?? ''),
                'status_name' => $s['event'] ?? '',
                'date'        => $s['created_at'] ?? '',
                'location'    => $s['place'] ?? '',
            ];
        }

        return [
            'status'       => (string)($latest['code'] ?? 'unknown'),
            'status_name'  => $latest['event'] ?? 'Неизвестно',
            'status_date'  => $latest['created_at'] ?? null,
            'location'     => $latest['place'] ?? null,
            'track_number' => $trackNumber,
            'history'      => $history,
        ];
    }

    public function isConfigured(): bool
    {
        $config = $this->getConfig();
        return !empty($config['active']);
    }

    private function getConfig(): array
    {
        $json = Yii::$app->settings->get('plugin', 'belpochta_config', '{}');
        return is_array($json) ? $json : (json_decode($json, true) ?: []);
    }
}
