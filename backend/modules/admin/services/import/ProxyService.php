<?php

namespace app\backend\modules\admin\services\import;

use Yii;
use yii\base\Component;

/**
 * ProxyService — Сервис управления прокси
 * 
 * Ротация, проверка, мониторинг прокси
 */
class ProxyService extends Component
{
    /**
     * Кэш рабочих прокси
     * @var array
     */
    private $workingProxies = [];

    /**
     * Индекс текущего прокси для последовательной ротации
     * @var int
     */
    private $currentIndex = 0;

    /**
     * Получить прокси для источника
     * @param int $sourceId
     * @return string|null
     */
    public function getProxy($sourceId)
    {
        $source = \app\backend\modules\admin\models\import\ImportSource::findOne($sourceId);
        
        if (!$source || !$source->proxy_enabled) {
            return null;
        }

        $proxyList = $source->getProxyListArray();
        
        if (empty($proxyList)) {
            return null;
        }

        // Фильтруем только рабочие прокси
        $workingList = $this->filterWorkingProxies($proxyList, $sourceId);
        
        if (empty($workingList)) {
            // Если нет рабочих - пробуем все
            $workingList = $proxyList;
        }

        // Выбираем прокси по типу ротации
        return match($source->proxy_rotation) {
            \app\backend\modules\admin\models\import\ImportSource::PROXY_ROTATION_RANDOM => $this->selectRandom($workingList),
            \app\backend\modules\admin\models\import\ImportSource::PROXY_ROTATION_SEQUENTIAL => $this->selectSequential($workingList, $sourceId),
            default => $this->selectRandom($workingList),
        };
    }

    /**
     * Случайный выбор прокси
     * @param array $proxyList
     * @return string
     */
    protected function selectRandom(array $proxyList)
    {
        return $proxyList[array_rand($proxyList)];
    }

    /**
     * Последовательный выбор прокси
     * @param array $proxyList
     * @param int $sourceId
     * @return string
     */
    protected function selectSequential(array $proxyList, $sourceId)
    {
        $key = "proxy_index_{$sourceId}";
        
        if (!isset($this->currentIndex[$key])) {
            $this->currentIndex[$key] = 0;
        }

        $index = $this->currentIndex[$key] % count($proxyList);
        $this->currentIndex[$key]++;

        return $proxyList[$index];
    }

    /**
     * Фильтровать только рабочие прокси
     * @param array $proxyList
     * @param int $sourceId
     * @return array
     */
    protected function filterWorkingProxies(array $proxyList, $sourceId)
    {
        $key = "working_proxies_{$sourceId}";
        
        if (!isset($this->workingProxies[$key])) {
            $this->workingProxies[$key] = [];
            
            foreach ($proxyList as $proxy) {
                if ($this->checkProxy($proxy)) {
                    $this->workingProxies[$key][] = $proxy;
                }
            }
        }

        return $this->workingProxies[$key];
    }

    /**
     * Проверить работоспособность прокси
     * @param string $proxy
     * @param int $timeout Таймаут в секундах
     * @return bool
     */
    public function checkProxy($proxy, $timeout = 10)
    {
        try {
            $client = new \GuzzleHttp\Client([
                'timeout' => $timeout,
                'connect_timeout' => $timeout,
                'proxy' => $proxy,
                'verify' => false,
            ]);

            // Проверяем через простой запрос
            $response = $client->get('https://api.ipify.org?format=json');
            
            return $response->getStatusCode() === 200;
        } catch (\Exception $e) {
            Yii::warning("Proxy check failed: {$proxy} - " . $e->getMessage(), 'import');
            return false;
        }
    }

    /**
     * Отметить прокси как нерабочий
     * @param string $proxy
     */
    public function markAsFailed($proxy)
    {
        if (!$proxy) {
            return;
        }

        // Удаляем из кэша рабочих прокси
        foreach ($this->workingProxies as $key => &$proxies) {
            $index = array_search($proxy, $proxies);
            if ($index !== false) {
                unset($proxies[$index]);
                $proxies = array_values($proxies);
            }
        }

        Yii::warning("Proxy marked as failed: {$proxy}", 'import');
    }

    /**
     * Отметить прокси как рабочий
     * @param string $proxy
     * @param int $sourceId
     */
    public function markAsWorking($proxy, $sourceId)
    {
        if (!$proxy) {
            return;
        }

        $key = "working_proxies_{$sourceId}";
        
        if (!isset($this->workingProxies[$key])) {
            $this->workingProxies[$key] = [];
        }

        if (!in_array($proxy, $this->workingProxies[$key])) {
            $this->workingProxies[$key][] = $proxy;
        }
    }

    /**
     * Проверить все прокси источника
     * @param int $sourceId
     * @return array Результаты проверки
     */
    public function checkAllProxies($sourceId)
    {
        $source = \app\backend\modules\admin\models\import\ImportSource::findOne($sourceId);
        
        if (!$source) {
            return [];
        }

        $proxyList = $source->getProxyListArray();
        $results = [];

        foreach ($proxyList as $proxy) {
            $results[$proxy] = [
                'proxy' => $proxy,
                'working' => $this->checkProxy($proxy),
                'checked_at' => date('Y-m-d H:i:s'),
            ];
        }

        return $results;
    }

    /**
     * Получить статистику прокси
     * @param int $sourceId
     * @return array
     */
    public function getStats($sourceId)
    {
        $source = \app\backend\modules\admin\models\import\ImportSource::findOne($sourceId);
        
        if (!$source) {
            return [];
        }

        $proxyList = $source->getProxyListArray();
        $key = "working_proxies_{$sourceId}";
        $workingList = $this->workingProxies[$key] ?? [];

        return [
            'total' => count($proxyList),
            'working' => count($workingList),
            'failed' => count($proxyList) - count($workingList),
            'success_rate' => count($proxyList) > 0 
                ? round((count($workingList) / count($proxyList)) * 100, 1) 
                : 0,
        ];
    }

    /**
     * Сбросить кэш рабочих прокси
     * @param int|null $sourceId Если null - сбросить все
     */
    public function resetCache($sourceId = null)
    {
        if ($sourceId === null) {
            $this->workingProxies = [];
            $this->currentIndex = [];
        } else {
            $key = "working_proxies_{$sourceId}";
            unset($this->workingProxies[$key]);
            unset($this->currentIndex["proxy_index_{$sourceId}"]);
        }
    }
}
