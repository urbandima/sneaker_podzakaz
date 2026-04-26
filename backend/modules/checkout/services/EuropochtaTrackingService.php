<?php

namespace app\backend\modules\checkout\services;

use yii\base\Component;
use Yii;

/**
 * EuropochtaTrackingService — трекинг отправлений через Европочту Беларуси
 *
 * Конфигурация через настройки плагина (settings: plugin_europochta_config):
 *   active  — bool, включён ли плагин
 *
 * Регистрация в web.php:
 *   'europochtaTracking' => ['class' => EuropochtaTrackingService::class]
 */
class EuropochtaTrackingService extends Component
{
    public function getStatus(string $trackNumber): array
    {
        $config = $this->getConfig();
        if (empty($config['active'])) {
            return ['status' => 'not_configured', 'message' => 'Европочта не настроена'];
        }
        try {
            $url = 'https://evropochta.by/api/track.json/?number=' . urlencode($trackNumber);
            $ctx = stream_context_create(['http' => ['timeout' => 10]]);
            $response = @file_get_contents($url, false, $ctx);
            if ($response === false) {
                return ['status' => 'error', 'message' => 'Не удалось получить данные от Европочты'];
            }
            $data = json_decode($response, true);
            return $this->parseResponse($data ?: [], $trackNumber);
        } catch (\Exception $e) {
            Yii::warning('Europochta tracking error: ' . $e->getMessage(), 'tracking');
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    private function parseResponse(array $data, string $trackNumber): array
    {
        if (empty($data)) {
            return ['status' => 'not_found', 'message' => 'Отправление не найдено', 'track_number' => $trackNumber];
        }
        // Real API: { "data": [ { "Timex": "...", "InfoTrack": "...", "CheckxFrom": "N", "CheckxTo": "N", "Info": "..." }, ... ] }
        // Events are sorted newest-first
        $events = $data['data'] ?? [];

        // API-level error
        if (is_array($events) && isset($events[0]['Error'])) {
            return [
                'status'       => 'not_found',
                'message'      => $events[0]['ErrorDescription'] ?? 'Трек-номер не найден',
                'track_number' => $trackNumber,
            ];
        }

        if (empty($events)) {
            return ['status' => 'not_found', 'message' => 'Отправление не найдено', 'track_number' => $trackNumber];
        }

        // First event is most recent
        $latest = $events[0];
        $statusCode = (string)($latest['CheckxTo'] ?? '0');

        // Normalize history to a consistent shape
        $history = [];
        foreach ($events as $e) {
            $history[] = [
                'status_code' => (string)($e['CheckxTo'] ?? ''),
                'status_name' => $e['InfoTrack'] ?? '',
                'date'        => $e['Timex'] ?? '',
                'info'        => $e['Info'] ?? '',
            ];
        }

        return [
            'status'       => $statusCode,
            'status_name'  => $latest['InfoTrack'] ?? 'Неизвестно',
            'status_date'  => $latest['Timex'] ?? null,
            'location'     => null,
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
        $json = Yii::$app->settings->get('plugin', 'europochta_config', '{}');
        return is_array($json) ? $json : (json_decode($json, true) ?: []);
    }
}
