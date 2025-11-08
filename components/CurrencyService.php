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
     * @var int Время жизни кэша в секундах (по умолчанию 1 день = 86400 сек)
     */
    public $cacheDuration = 86400;
    
    /**
     * Получить текущий курс CNY к BYN
     * 
     * @return float
     */
    public function getCnyToBynRate()
    {
        // Пытаемся получить из кэша
        $rate = Yii::$app->cache->get($this->cacheKey);
        
        if ($rate === false) {
            // Кэш пуст, используем значение по умолчанию
            $rate = $this->cnyToBynRate;
            
            // Пытаемся получить актуальный курс через API
            try {
                $actualRate = $this->fetchCnyToBynRate();
                if ($actualRate > 0) {
                    $rate = $actualRate;
                }
            } catch (\Exception $e) {
                Yii::warning("Не удалось получить курс CNY/BYN: " . $e->getMessage(), __METHOD__);
            }
            
            // Сохраняем в кэш
            Yii::$app->cache->set($this->cacheKey, $rate, $this->cacheDuration);
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
        
        // Сохраняем в кэш
        return Yii::$app->cache->set($this->cacheKey, $rate, $this->cacheDuration);
    }
    
    /**
     * Получить актуальный курс CNY к BYN через API Национального банка РБ
     * 
     * @return float|null
     */
    protected function fetchCnyToBynRate()
    {
        // API Нацбанка РБ
        // Получаем курс USD/BYN и CNY/USD, затем вычисляем CNY/BYN
        
        try {
            // 1. Получаем курс CNY к BYN напрямую от НБРБ
            $url = 'https://api.nbrb.by/exrates/rates/CNY?parammode=0';
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200 && $response) {
                $data = json_decode($response, true);
                
                if (isset($data['Cur_OfficialRate']) && $data['Cur_OfficialRate'] > 0) {
                    // Курс НБРБ указывает за сколько BYN можно купить 1 CNY (или 10/100 CNY)
                    $scale = $data['Cur_Scale'] ?? 1;
                    $rate = $data['Cur_OfficialRate'] / $scale;
                    
                    Yii::info("Получен курс CNY/BYN от НБРБ: $rate", __METHOD__);
                    return $rate;
                }
            }
            
        } catch (\Exception $e) {
            Yii::warning("Ошибка при получении курса от НБРБ: " . $e->getMessage(), __METHOD__);
        }
        
        // Если не удалось получить, пытаемся через альтернативный источник (ExchangeRate-API)
        try {
            $url = 'https://api.exchangerate-api.com/v4/latest/CNY';
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200 && $response) {
                $data = json_decode($response, true);
                
                if (isset($data['rates']['BYN']) && $data['rates']['BYN'] > 0) {
                    $rate = $data['rates']['BYN'];
                    Yii::info("Получен курс CNY/BYN через ExchangeRate-API: $rate", __METHOD__);
                    return $rate;
                }
            }
            
        } catch (\Exception $e) {
            Yii::warning("Ошибка при получении курса через ExchangeRate-API: " . $e->getMessage(), __METHOD__);
        }
        
        return null;
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
        return Yii::$app->cache->delete($this->cacheKey);
    }
    
    /**
     * Получить информацию о текущем курсе
     * 
     * @return array
     */
    public function getCurrencyInfo()
    {
        $rate = $this->getCnyToBynRate();
        $cacheTime = Yii::$app->cache->get($this->cacheKey . '_time');
        
        return [
            'rate' => $rate,
            'updated_at' => $cacheTime ? date('Y-m-d H:i:s', $cacheTime) : null,
            'source' => 'cache',
        ];
    }
}
