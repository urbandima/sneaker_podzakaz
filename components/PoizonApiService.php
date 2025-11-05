<?php

namespace app\components;

use Yii;
use yii\base\Component;

/**
 * Сервис для работы с Poizon/Dewu API
 * 
 * Поддерживает:
 * - Получение списка товаров
 * - Проверка наличия размеров
 * - Получение актуальных цен
 * - Конвертация размеров (US/EU/UK/CM)
 */
class PoizonApiService extends Component
{
    /**
     * @var string URL API Poizon (можно использовать сторонние сервисы парсинга)
     */
    public $apiUrl = 'https://api.poizon-parser.com/v1'; // Примерный URL
    
    /**
     * @var string API ключ
     */
    public $apiKey;
    
    /**
     * @var int Таймаут запросов в секундах
     */
    public $timeout = 30;

    /**
     * Инициализация компонента
     */
    public function init()
    {
        parent::init();
        
        // Получаем API ключ из конфига (если есть)
        if (!$this->apiKey && isset(Yii::$app->params['poizonApiKey'])) {
            $this->apiKey = Yii::$app->params['poizonApiKey'];
        }
    }

    /**
     * Получить список товаров обуви по популярности
     * 
     * @param array $params Параметры фильтрации
     * @return array
     */
    public function getPopularShoes($params = [])
    {
        // Параметры по умолчанию
        $defaultParams = [
            'category' => 'sneakers', // Кроссовки
            'sort' => 'popularity',    // По популярности
            'limit' => 100,            // Товаров за запрос
            'offset' => 0,
        ];
        
        $params = array_merge($defaultParams, $params);
        
        try {
            // ВНИМАНИЕ: Это примерная реализация
            // Реальный API Poizon закрыт, используйте сторонние сервисы или парсинг
            
            // Вариант 1: Использование XML фида (если есть доступ)
            if (isset(Yii::$app->params['poizonXmlUrl'])) {
                return $this->parseXmlFeed(Yii::$app->params['poizonXmlUrl']);
            }
            
            // Вариант 2: Использование стороннего API парсера (требует composer require yiisoft/yii2-httpclient)
            // Для использования API раскомментируйте код ниже и установите yii2-httpclient
            /*
            $client = new \yii\httpclient\Client(['baseUrl' => $this->apiUrl]);
            $response = $client->createRequest()
                ->setMethod('GET')
                ->setUrl('/products/search')
                ->setData($params)
                ->setHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->send();
            
            if ($response->isOk) {
                return $response->data;
            }
            
            Yii::error('Poizon API error: ' . $response->statusCode, __METHOD__);
            return ['items' => [], 'error' => 'API request failed'];
            */
            
            return ['items' => [], 'error' => 'API импорт не настроен. Используйте XML фид или JSON файл'];
            
        } catch (\Exception $e) {
            Yii::error('Poizon API exception: ' . $e->getMessage(), __METHOD__);
            return ['items' => [], 'error' => $e->getMessage()];
        }
    }

    /**
     * Парсинг XML фида
     * 
     * @param string $xmlUrl URL XML фида
     * @return array
     */
    public function parseXmlFeed($xmlUrl)
    {
        try {
            $xml = @file_get_contents($xmlUrl);
            if (!$xml) {
                throw new \Exception('Failed to load XML feed');
            }
            
            libxml_use_internal_errors(true);
            $xmlObj = simplexml_load_string($xml);
            if (!$xmlObj) {
                throw new \Exception('Failed to parse XML');
            }
            
            $products = [];
            foreach ($xmlObj->shop->offers->offer as $offer) {
                $products[] = $this->parseXmlOffer($offer);
            }
            
            return ['items' => $products, 'total' => count($products)];
            
        } catch (\Exception $e) {
            Yii::error('XML parsing error: ' . $e->getMessage(), __METHOD__);
            return ['items' => [], 'error' => $e->getMessage()];
        }
    }

    /**
     * Парсинг одного товара из XML
     */
    private function parseXmlOffer($offer)
    {
        // Извлекаем размеры из параметров (если есть)
        $sizes = [];
        if (isset($offer->param)) {
            foreach ($offer->param as $param) {
                $name = (string) $param['name'];
                $value = (string) $param;
                
                if (stripos($name, 'size') !== false || stripos($name, 'размер') !== false) {
                    $sizes[] = $value;
                }
            }
        }
        
        return [
            'poizon_id' => (string) $offer['id'],
            'name' => (string) $offer->name,
            'vendor_code' => (string) $offer->vendorCode,
            'price_cny' => (float) $offer->price,
            'description' => strip_tags((string) $offer->description),
            'images' => $this->extractImages($offer),
            'url' => (string) $offer->url,
            'brand' => $this->extractBrand((string) $offer->name),
            'category' => (string) $offer->categoryId,
            'sizes' => $sizes,
            'params' => $this->extractParams($offer),
        ];
    }

    /**
     * Извлечь изображения
     */
    private function extractImages($offer)
    {
        $images = [];
        if (isset($offer->picture)) {
            foreach ($offer->picture as $picture) {
                $images[] = (string) $picture;
            }
        }
        return $images;
    }

    /**
     * Извлечь параметры товара
     */
    private function extractParams($offer)
    {
        $params = [];
        if (isset($offer->param)) {
            foreach ($offer->param as $param) {
                $name = (string) $param['name'];
                $value = (string) $param;
                $params[$name] = $value;
            }
        }
        return $params;
    }

    /**
     * Извлечь бренд из названия
     */
    private function extractBrand($name)
    {
        $brands = [
            'Nike', 'Adidas', 'New Balance', 'Puma', 'Reebok', 
            'Converse', 'Vans', 'Asics', 'Jordan', 'Yeezy',
            'Under Armour', 'Saucony', 'Mizuno', 'Salomon',
            'Hoka', 'Brooks', 'On Running', 'Balenciaga',
            'Alexander McQueen', 'Common Projects', 'Rick Owens'
        ];
        
        foreach ($brands as $brand) {
            if (stripos($name, $brand) !== false) {
                return $brand;
            }
        }
        
        return 'Unknown';
    }

    /**
     * Проверить наличие размера товара в реальном времени
     * 
     * @param string $poizonSkuId SKU ID размера в Poizon
     * @return array ['available' => bool, 'stock' => int, 'price' => float]
     */
    public function checkSizeAvailability($poizonSkuId)
    {
        try {
            // MOCK для разработки
            // В продакшене заменить на реальный API запрос
            return [
                'available' => true,
                'stock' => rand(1, 10),
                'price_cny' => rand(300, 2000),
            ];
            
            // Реальная реализация (пример):
            /*
            $response = $this->_client->createRequest()
                ->setMethod('GET')
                ->setUrl('/sku/check')
                ->setData(['sku_id' => $poizonSkuId])
                ->setHeaders(['Authorization' => 'Bearer ' . $this->apiKey])
                ->send();
            
            if ($response->isOk) {
                return $response->data;
            }
            
            return ['available' => false, 'stock' => 0];
            */
        } catch (\Exception $e) {
            Yii::error('Size availability check error: ' . $e->getMessage(), __METHOD__);
            return ['available' => false, 'stock' => 0, 'error' => $e->getMessage()];
        }
    }

    /**
     * Конвертировать размер между системами
     * 
     * @param float $size Размер
     * @param string $from Из системы (us, eu, uk, cm)
     * @param string $to В систему (us, eu, uk, cm)
     * @param string $gender Пол (male, female, unisex)
     * @return float|null
     */
    public function convertSize($size, $from, $to, $gender = 'male')
    {
        // Таблица конвертации размеров (мужские)
        $maleConversion = [
            // US => [EU, UK, CM]
            6 => [38, 5.5, 24],
            6.5 => [38.5, 6, 24.5],
            7 => [39, 6, 25],
            7.5 => [40, 6.5, 25.5],
            8 => [41, 7, 26],
            8.5 => [41.5, 7.5, 26.5],
            9 => [42, 8, 27],
            9.5 => [42.5, 8.5, 27.5],
            10 => [43, 9, 28],
            10.5 => [44, 9.5, 28.5],
            11 => [44.5, 10, 29],
            11.5 => [45, 10.5, 29.5],
            12 => [46, 11, 30],
            12.5 => [46.5, 11.5, 30.5],
            13 => [47, 12, 31],
        ];
        
        // Таблица конвертации размеров (женские)
        $femaleConversion = [
            // US => [EU, UK, CM]
            5 => [35.5, 2.5, 22],
            5.5 => [36, 3, 22.5],
            6 => [36.5, 3.5, 23],
            6.5 => [37, 4, 23.5],
            7 => [37.5, 4.5, 24],
            7.5 => [38, 5, 24.5],
            8 => [38.5, 5.5, 25],
            8.5 => [39, 6, 25.5],
            9 => [39.5, 6.5, 26],
            9.5 => [40, 7, 26.5],
            10 => [40.5, 7.5, 27],
            10.5 => [41, 8, 27.5],
            11 => [41.5, 8.5, 28],
        ];
        
        $table = ($gender === 'female') ? $femaleConversion : $maleConversion;
        
        // Конвертация
        $from = strtolower($from);
        $to = strtolower($to);
        
        if ($from === $to) {
            return $size;
        }
        
        // Индексы в массиве
        $indexes = ['us' => 0, 'eu' => 1, 'uk' => 2, 'cm' => 3];
        
        // Если конвертируем из US
        if ($from === 'us' && isset($table[$size])) {
            $toIndex = $indexes[$to] ?? null;
            if ($toIndex !== null && $toIndex > 0) {
                return $table[$size][$toIndex - 1];
            }
        }
        
        // Для других конвертаций - сначала найти US размер
        if ($from !== 'us') {
            $fromIndex = $indexes[$from] ?? null;
            if ($fromIndex !== null && $fromIndex > 0) {
                foreach ($table as $usSize => $values) {
                    if ($values[$fromIndex - 1] == $size) {
                        // Нашли US размер, теперь конвертируем в нужный
                        if ($to === 'us') {
                            return $usSize;
                        }
                        $toIndex = $indexes[$to] ?? null;
                        if ($toIndex !== null && $toIndex > 0) {
                            return $values[$toIndex - 1];
                        }
                    }
                }
            }
        }
        
        return null; // Не смогли конвертировать
    }

    /**
     * Рассчитать цену в BYN по формуле: CNY * курс * 1.5 + 40 BYN
     * 
     * @param float $priceCny Цена в юанях
     * @return float Цена в BYN
     */
    public function calculatePriceBYN($priceCny)
    {
        // Курс CNY -> BYN (примерный, обновляется вручную или через API)
        $cnyToByn = Yii::$app->params['cnyToBynRate'] ?? 0.45; // 1 CNY ≈ 0.45 BYN
        
        // Формула: CNY * курс * 1.5 + 40
        $priceByn = ($priceCny * $cnyToByn * 1.5) + 40;
        
        return round($priceByn, 2);
    }

    /**
     * Тестовое подключение к API
     */
    public function testConnection()
    {
        try {
            // Попытка загрузить XML фид (если настроен)
            if (isset(Yii::$app->params['poizonXmlUrl'])) {
                $result = $this->parseXmlFeed(Yii::$app->params['poizonXmlUrl']);
                return [
                    'success' => !isset($result['error']),
                    'message' => isset($result['error']) ? $result['error'] : 'XML feed loaded successfully',
                    'items_found' => count($result['items'] ?? []),
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Poizon XML URL not configured in params',
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}
