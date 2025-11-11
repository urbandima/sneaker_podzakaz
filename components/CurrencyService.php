<?php

namespace app\components;

use Yii;
use yii\base\Component;
use yii\caching\CacheInterface;

/**
 * Сервис для работы с валютными курсами
 */
class CurrencyService extends Component
{
    /**
     * @var float Курс CNY к BYN (обновляется вручную или через API)
     * По умолчанию примерно 0.45 BYN за 1 CNY (на 2025 год)
     */
    public $cnyToBynRate = 0.45;

    /**
     * @var string Ключ кэша для курса валют
     */
    public $cacheKey = 'currency_cny_to_byn_rate';

    /**
     * @var string Ключ таймштампа обновления курса
     */
    public $cacheTimestampKey = 'currency_cny_to_byn_rate_time';

    /**
     * @var string Ключ источника актуального курса
     */
    public $cacheSourceKey = 'currency_cny_to_byn_rate_source';

    /**
     * @var int Время жизни кэша в секундах (по умолчанию 1 день = 86400 сек)
     */
    public $cacheDuration = 86400;

    /**
     * @var int Количество попыток запроса внешнего API
     */
    public $retryAttempts = 3;

    /**
     * @var int Пауза между попытками (мс)
     */
    public $retryDelayMs = 200;

    /**
     * @var callable|null Кастомный HTTP клиент (callable(string $url, array $options): array)
     */
    private $httpClient;
    
    /**
     * Получить текущий курс CNY к BYN
     * 
     * @return float
     */
    public function getCnyToBynRate()
    {
        $cache = $this->getCache();
        $rate = $cache->get($this->cacheKey);

        if ($rate === false) {
            // Кэш пуст, используем значение по умолчанию
            $rate = $this->cnyToBynRate;
            
            // Пытаемся получить актуальный курс через API
            try {
                $result = $this->fetchCnyToBynRate();
                if ($result && $result['rate'] > 0) {
                    $rate = $result['rate'];
                    $this->storeRateInCache($rate, $result['source']);
                } else {
                    $this->storeRateInCache($rate, 'default');
                }
            } catch (\Throwable $e) {
                Yii::warning("Не удалось получить курс CNY/BYN: " . $e->getMessage(), __METHOD__);
                $this->storeRateInCache($rate, 'fallback');
            }
        } else {
            if ($cache->get($this->cacheTimestampKey) === false) {
                $this->storeRateInCache($rate, $cache->get($this->cacheSourceKey) ?: 'unknown');
            }
        }
        
        return (float)$rate;
    }
    
    /**
     * Установить курс CNY к BYN вручную
     * 
     * @param float $rate Курс
     * @return bool
     */
    public function setCnyToBynRate($rate)
    {
        $rate = (float)$rate;
        
        if ($rate <= 0) {
            return false;
        }
        
        $this->cnyToBynRate = $rate;

        return $this->storeRateInCache($rate, 'manual');
    }
    
    /**
     * Получить актуальный курс CNY к BYN через API Национального банка РБ
     * 
     * @return array{rate: float, source: string}
     */
    protected function fetchCnyToBynRate()
    {
        $sources = [
            [
                'url' => 'https://api.nbrb.by/exrates/rates/CNY?parammode=0',
                'parser' => function (array $data) {
                    if (isset($data['Cur_OfficialRate']) && $data['Cur_OfficialRate'] > 0) {
                        $scale = $data['Cur_Scale'] ?? 1;
                        return $data['Cur_OfficialRate'] / max(1, $scale);
                    }
                    return null;
                },
                'source' => 'nbrb',
            ],
            [
                'url' => 'https://api.exchangerate-api.com/v4/latest/CNY',
                'parser' => function (array $data) {
                    return $data['rates']['BYN'] ?? null;
                },
                'source' => 'exchange-rate-api',
            ],
        ];

        foreach ($sources as $source) {
            try {
                $data = $this->requestJson($source['url']);
                $rate = $source['parser']($data);
                if ($rate > 0) {
                    $rate = (float)$rate;
                    Yii::info("Получен курс CNY/BYN ({$source['source']}): $rate", __METHOD__);
                    return [
                        'rate' => $rate,
                        'source' => $source['source'],
                    ];
                }
            } catch (\Throwable $e) {
                Yii::warning("Источник {$source['source']} недоступен: " . $e->getMessage(), __METHOD__);
            }
        }

        throw new \RuntimeException('Все источники курса CNY/BYN недоступны.');
    }
    
    /**
     * Конвертировать CNY в BYN
     * 
     * @param float $cny Сумма в юанях
     * @return float Сумма в BYN
     */
    public function convertCnyToByn($cny)
    {
        return $cny * $this->getCnyToBynRate();
    }
    
    /**
     * Рассчитать цену в BYN по формуле для Poizon
     * Формула: (price_cny * курс * 1.5) + 40 BYN
     * С округлением до красивых значений (309, 419, 529 и т.д.)
     * 
     * @param float $priceCny Цена в юанях
     * @param float $markup Наценка (по умолчанию 1.5)
     * @param float $fixedFee Фиксированная комиссия (по умолчанию 40 BYN)
     * @return float Цена в BYN (округленная до xx9)
     */
    public function calculatePoizonPriceByn($priceCny, $markup = 1.5, $fixedFee = 40)
    {
        $rate = $this->getCnyToBynRate();
        $priceByn = ($priceCny * $rate * $markup) + $fixedFee;
        
        // Округляем до красивого значения, заканчивающегося на 9
        return $this->roundToPrettyPrice($priceByn);
    }
    
    /**
     * Округлить цену до "красивого" значения, заканчивающегося на 9
     * Примеры: 454.21 → 449, 523.36 → 519, 661.66 → 659
     * 
     * @param float $price Исходная цена
     * @return int Округленная цена (всегда заканчивается на 9)
     */
    private function roundToPrettyPrice($price)
    {
        if ($price <= 0) {
            return 0;
        }
        
        // Округляем вниз до целого числа
        $floored = floor($price);
        
        // Находим ближайшее число, заканчивающееся на 9 (вниз)
        $lastDigit = $floored % 10;
        
        if ($lastDigit == 9) {
            // Уже заканчивается на 9
            return (int) $floored;
        } else {
            // Округляем вниз до xx9
            return (int) ($floored - $lastDigit - 1);
        }
    }
    
    /**
     * Очистить кэш курсов
     * 
     * @return bool
     */
    public function clearCache()
    {
        $cache = $this->getCache();
        $deleted = $cache->delete($this->cacheKey);
        $cache->delete($this->cacheTimestampKey);
        $cache->delete($this->cacheSourceKey);

        return $deleted;
    }
    
    /**
     * Получить информацию о текущем курсе
     * 
     * @return array
     */
    public function getCurrencyInfo()
    {
        $rate = $this->getCnyToBynRate();
        $cache = $this->getCache();
        $cacheTime = $cache->get($this->cacheTimestampKey);
        $source = $cache->get($this->cacheSourceKey) ?: 'unknown';

        return [
            'rate' => $rate,
            'updated_at' => $cacheTime ? date('Y-m-d H:i:s', $cacheTime) : null,
            'source' => $source,
        ];
    }

    /**
     * Установить кастомный HTTP клиент для тестов или альтернативных транспортов
     */
    public function setHttpClient($client): void
    {
        if ($client !== null && !is_callable($client)) {
            throw new \InvalidArgumentException('HTTP клиент должен быть callable.');
        }

        $this->httpClient = $client;
    }

    protected function getCache(): CacheInterface
    {
        $cache = Yii::$app->cache;
        if (!$cache instanceof CacheInterface) {
            throw new \RuntimeException('Компонент cache не реализует CacheInterface.');
        }

        return $cache;
    }

    protected function storeRateInCache(float $rate, string $source): bool
    {
        $cache = $this->getCache();
        $timestamp = time();

        $stored = $cache->set($this->cacheKey, $rate, $this->cacheDuration);
        $cache->set($this->cacheTimestampKey, $timestamp, $this->cacheDuration);
        $cache->set($this->cacheSourceKey, $source, $this->cacheDuration);

        return $stored;
    }

    protected function requestJson(string $url): array
    {
        $attempt = 0;
        $lastException = null;
        $attempts = max(1, (int)$this->retryAttempts);

        while ($attempt < $attempts) {
            $attempt++;
            try {
                $response = $this->performHttpRequest($url);
                if (($response['status'] ?? 0) !== 200) {
                    throw new \RuntimeException('HTTP status ' . ($response['status'] ?? 'unknown'));
                }

                $body = $response['body'] ?? null;
                if ($body === null || $body === '') {
                    throw new \RuntimeException('Пустой ответ от сервиса.');
                }

                $data = json_decode($body, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \RuntimeException('Некорректный JSON: ' . json_last_error_msg());
                }

                return $data;
            } catch (\Throwable $e) {
                $lastException = $e;
                Yii::warning("Попытка {$attempt} запроса {$url} завершилась ошибкой: " . $e->getMessage(), __METHOD__);
                if ($attempt < $attempts) {
                    usleep(max(1, $this->retryDelayMs) * 1000);
                }
            }
        }

        throw new \RuntimeException('Не удалось получить данные по URL ' . $url, 0, $lastException);
    }

    protected function performHttpRequest(string $url): array
    {
        if ($this->httpClient) {
            return call_user_func($this->httpClient, $url, [
                'timeout' => 5,
            ]);
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'CurrencyService/1.0');

        $body = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($body === false) {
            throw new \RuntimeException('cURL error: ' . $error);
        }

        return [
            'status' => $status,
            'body' => $body,
        ];
    }
}
