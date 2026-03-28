<?php

namespace app\backend\modules\admin\services\import;

use Yii;
use yii\base\Component;
use yii\httpclient\Client;

/**
 * NbrbRateService — Сервис получения курсов валют от НБ РБ
 *
 * API Национального банка Республики Беларусь
 * Кэширование курсов в БД
 */
class NbrbRateService extends Component
{
    /**
     * API URL НБ РБ
     */
    const API_URL = 'https://api.nbrb.by/exrates/rates';

    /**
     * Поддерживаемые валюты
     */
    const SUPPORTED_CURRENCIES = ['USD', 'EUR', 'CNY', 'RUB', 'PLN'];

    /**
     * HTTP клиент
     */
    private $client;

    /**
     * Инициализация
     */
    public function init()
    {
        parent::init();
        
        $this->client = new Client([
            'baseUrl' => self::API_URL,
            'requestConfig' => ['format' => Client::FORMAT_JSON],
            'responseConfig' => ['format' => Client::FORMAT_JSON],
        ]);
    }

    /**
     * Получить курс валюты к BYN
     * @param string $currencyCode Код валюты (USD, EUR, CNY)
     * @param bool $useCache Использовать кэш
     * @return float|null
     */
    public function getRate($currencyCode, $useCache = true)
    {
        if ($currencyCode === 'BYN') {
            return 1.0;
        }

        // Проверяем кэш
        if ($useCache) {
            $cached = $this->getCachedRate($currencyCode);
            if ($cached !== null) {
                return $cached;
            }
        }

        // Получаем из API
        $rate = $this->fetchRateFromAPI($currencyCode);
        
        if ($rate !== null) {
            $this->saveRateToCache($currencyCode, $rate);
        }

        return $rate;
    }

    /**
     * Получить курсы всех поддерживаемых валют
     * @param bool $useCache
     * @return array ['USD' => rate, 'EUR' => rate, ...]
     */
    public function getAllRates($useCache = true)
    {
        $rates = ['BYN' => 1.0];

        foreach (self::SUPPORTED_CURRENCIES as $currency) {
            $rate = $this->getRate($currency, $useCache);
            if ($rate !== null) {
                $rates[$currency] = $rate;
            }
        }

        return $rates;
    }

    /**
     * Конвертировать сумму в BYN
     * @param float $amount Сумма в оригинальной валюте
     * @param string $fromCurrency Код исходной валюты
     * @param bool $useCache Использовать кэш
     * @return float|null Сумма в BYN
     */
    public function convertToBYN($amount, $fromCurrency, $useCache = true)
    {
        if ($fromCurrency === 'BYN') {
            return $amount;
        }

        $rate = $this->getRate($fromCurrency, $useCache);
        
        if ($rate === null) {
            return null;
        }

        return $amount * $rate;
    }

    /**
     * Получить курс из API НБ РБ
     * @param string $currencyCode
     * @return float|null
     */
    protected function fetchRateFromAPI($currencyCode)
    {
        try {
            // API НБ РБ возвращает курсы за конкретную дату
            // Формат: /rates/{currency}?parammode=2 (2 = код валюты в формате ISO)
            $response = $this->client->get($currencyCode, ['parammode' => 2])->send();

            if (!$response->isOk) {
                Yii::error("Currency API error: HTTP {$response->statusCode}", 'import');
                return null;
            }

            $data = $response->getData();

            // API возвращает: { Cur_ID, Date, Cur_Abbreviation, Cur_Scale, Cur_Name, Cur_OfficialRate }
            if (isset($data['Cur_OfficialRate']) && isset($data['Cur_Scale'])) {
                // Курс указан за Cur_Scale единиц валюты
                // Например: USD = 100 единиц, курс = 3.24 (за 100 USD)
                // Реальный курс за 1 единицу = Cur_OfficialRate / Cur_Scale
                return (float) $data['Cur_OfficialRate'] / (int) $data['Cur_Scale'];
            }

            Yii::error("Currency API invalid response: " . json_encode($data), 'import');
            return null;
        } catch (\Exception $e) {
            Yii::error("Currency fetch error: " . $e->getMessage(), 'import');
            return null;
        }
    }

    /**
     * Получить курс из кэша БД
     * @param string $currencyCode
     * @return float|null
     */
    protected function getCachedRate($currencyCode)
    {
        try {
            $rate = Yii::$app->db->createCommand(
                "SELECT rate_to_byn FROM {{%currency_rate}} 
                 WHERE currency_code = :code AND rate_date = CURDATE()
                 LIMIT 1"
            )->bindValue(':code', $currencyCode)->queryScalar();

            return $rate !== false ? (float) $rate : null;
        } catch (\Exception $e) {
            // Таблица может не существовать
            return null;
        }
    }

    /**
     * Сохранить курс в кэш БД
     * @param string $currencyCode
     * @param float $rate
     */
    protected function saveRateToCache($currencyCode, $rate)
    {
        try {
            // Удаляем старую запись
            Yii::$app->db->createCommand(
                "DELETE FROM {{%currency_rate}} WHERE currency_code = :code"
            )->bindValue(':code', $currencyCode)->execute();

            // Вставляем новую
            Yii::$app->db->createCommand(
                "INSERT INTO {{%currency_rate}} (currency_code, rate_to_byn, rate_date, created_at, updated_at)
                 VALUES (:code, :rate, CURDATE(), NOW(), NOW())"
            )->bindValues([
                ':code' => $currencyCode,
                ':rate' => $rate,
            ])->execute();
        } catch (\Exception $e) {
            Yii::error("Currency cache save error: " . $e->getMessage(), 'import');
        }
    }

    /**
     * Обновить курсы всех валют
     * @return array Результаты обновления
     */
    public function updateAllRates()
    {
        $results = [];

        foreach (self::SUPPORTED_CURRENCIES as $currency) {
            $rate = $this->fetchRateFromAPI($currency);
            
            if ($rate !== null) {
                $this->saveRateToCache($currency, $rate);
                $results[$currency] = [
                    'success' => true,
                    'rate' => $rate,
                ];
            } else {
                $results[$currency] = [
                    'success' => false,
                    'error' => 'Failed to fetch rate',
                ];
            }
        }

        Yii::info("Currency rates updated: " . json_encode($results), 'import');
        
        return $results;
    }

    /**
     * Получить историю курсов
     * @param string $currencyCode
     * @param int $days Количество дней
     * @return array
     */
    public function getRateHistory($currencyCode, $days = 30)
    {
        try {
            $rates = Yii::$app->db->createCommand(
                "SELECT rate_to_byn, rate_date 
                 FROM {{%currency_rate}} 
                 WHERE currency_code = :code 
                 ORDER BY rate_date DESC 
                 LIMIT :limit"
            )->bindValues([
                ':code' => $currencyCode,
                ':limit' => $days,
            ])->queryAll();

            return $rates;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Получить дату последнего обновления курсов
     * @return string|null
     */
    public function getLastUpdateDate()
    {
        try {
            $date = Yii::$app->db->createCommand(
                "SELECT MAX(updated_at) FROM {{%currency_rate}}"
            )->queryScalar();

            return $date ?: null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Проверить, нужно ли обновлять курсы
     * @return bool
     */
    public function needsUpdate()
    {
        $lastUpdate = $this->getLastUpdateDate();
        
        if (!$lastUpdate) {
            return true;
        }

        // Обновляем если прошло больше 24 часов
        $lastUpdateTime = strtotime($lastUpdate);
        $now = time();

        return ($now - $lastUpdateTime) > 86400; // 24 часа в секундах
    }
}
