<?php

namespace app\infrastructure\plugins\analytics;

use Yii;
use app\infrastructure\plugins\base\BasePlugin;

class LiveDunePlugin extends BasePlugin
{
    protected $id = 'livedune';
    protected $name = 'LiveDune Instagram Analytics';
    protected $description = 'Метрики Instagram (reach, profile views, stories) через LiveDune API';
    protected $version = '1.0.0';
    protected $author = 'Sneakerhead Team';

    const BASE_URL = 'https://api.livedune.com';
    const ACCOUNT_ID = '2298447';
    const CACHE_TTL = 86400;

    public function getToken(): string
    {
        return Yii::$app->settings->get('integrations', 'livedune_api_token', '');
    }

    /**
     * История метрик по дням (reach, profile_views, stories_views)
     */
    public function getHistory(string $dateFrom, string $dateTo): ?array
    {
        $cacheKey = "livedune_history_{$dateFrom}_{$dateTo}";
        $cached = Yii::$app->cache->get($cacheKey);
        if ($cached !== false) {
            return $cached;
        }

        $token = $this->getToken();
        if (empty($token)) {
            return null;
        }

        $url = self::BASE_URL . '/accounts/' . self::ACCOUNT_ID . '/history'
            . '?access_token=' . urlencode($token)
            . '&date_from=' . urlencode($dateFrom)
            . '&date_to=' . urlencode($dateTo);

        $data = $this->fetchJson($url, 3);
        if ($data !== null) {
            Yii::$app->cache->set($cacheKey, $data, self::CACHE_TTL);
        }

        return $data;
    }

    /**
     * Посты за период, отсортированные по reach (топ сначала)
     */
    public function getPosts(string $dateFrom, string $dateTo): ?array
    {
        $cacheKey = "livedune_posts_{$dateFrom}_{$dateTo}";
        $cached = Yii::$app->cache->get($cacheKey);
        if ($cached !== false) {
            return $cached;
        }

        $token = $this->getToken();
        if (empty($token)) {
            return null;
        }

        $url = self::BASE_URL . '/accounts/' . self::ACCOUNT_ID . '/posts'
            . '?access_token=' . urlencode($token)
            . '&date_from=' . urlencode($dateFrom)
            . '&date_to=' . urlencode($dateTo);

        $data = $this->fetchJson($url, 3);
        if ($data !== null) {
            Yii::$app->cache->set($cacheKey, $data, self::CACHE_TTL);
        }

        return $data;
    }

    /**
     * Агрегировать history за период в сводные метрики недели
     */
    public function aggregateMetrics(array $history): array
    {
        $reach = 0;
        $profileViews = 0;
        $storiesTotal = 0;
        $storiesDays = 0;

        foreach ($history as $day) {
            $reach += (int)($day['reach'] ?? 0);
            $profileViews += (int)($day['profile_views'] ?? 0);
            if (isset($day['stories_views'])) {
                $storiesTotal += (int)$day['stories_views'];
                $storiesDays++;
            }
        }

        return [
            'reach' => $reach,
            'profile_views' => $profileViews,
            'stories_avg' => $storiesDays > 0 ? (int)round($storiesTotal / $storiesDays) : 0,
        ];
    }

    private function fetchJson(string $url, int $timeoutSec): ?array
    {
        $ctx = stream_context_create([
            'http' => [
                'timeout' => $timeoutSec,
                'method' => 'GET',
                'header' => "Accept: application/json\r\n",
                'ignore_errors' => true,
            ],
        ]);

        $raw = @file_get_contents($url, false, $ctx);
        if ($raw === false) {
            return null;
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : null;
    }
}
