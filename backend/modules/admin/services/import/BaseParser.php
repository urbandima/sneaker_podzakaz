<?php

namespace app\backend\modules\admin\services\import;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\DomCrawler\Crawler;
use Yii;

/**
 * BaseParser — Абстрактный класс парсера товаров
 * 
 * Базовый функционал для всех парсеров (Lamoda, Dewu, Zalando, StockX)
 */
abstract class BaseParser
{
    protected $source;
    protected $client;
    protected $proxyService;
    protected $captchaService;
    protected $currentProxy;
    protected $delay = 3;

    /**
     * Конструктор
     * @param \app\backend\modules\admin\models\import\ImportSource $source
     */
    public function __construct($source)
    {
        $this->source = $source;
        $this->delay = $source->getRandomDelay();
        
        $this->initClient();
    }

    /**
     * Инициализация HTTP клиента
     */
    protected function initClient()
    {
        $config = [
            'timeout' => 30,
            'connect_timeout' => 10,
            'headers' => [
                'User-Agent' => $this->getRandomUserAgent(),
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language' => 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
                'Accept-Encoding' => 'gzip, deflate, br',
                'Cache-Control' => 'no-cache',
            ],
        ];

        // Добавляем прокси если включено
        if ($this->source->proxy_enabled && $this->proxyService) {
            $proxy = $this->proxyService->getProxy($this->source->id);
            if ($proxy) {
                $config['proxy'] = $proxy;
                $this->currentProxy = $proxy;
            }
        }

        $this->client = new Client($config);
    }

    /**
     * Получить случайный User-Agent
     * @return string
     */
    protected function getRandomUserAgent()
    {
        $agents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Safari/605.1.15',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:121.0) Gecko/20100101 Firefox/121.0',
        ];

        return $agents[array_rand($agents)];
    }

    /**
     * Задержка между запросами
     */
    protected function delay()
    {
        if ($this->delay > 0) {
            sleep(rand($this->source->parse_delay_min, $this->source->parse_delay_max));
        }
    }

    /**
     * Выполнить HTTP запрос
     * @param string $url
     * @param string $method
     * @param array $options
     * @return string|null
     */
    protected function request($url, $method = 'GET', array $options = [])
    {
        try {
            $response = $this->client->request($method, $url, $options);
            
            if ($response->getStatusCode() === 200) {
                return (string) $response->getBody();
            }
            
            return null;
        } catch (GuzzleException $e) {
            Yii::error("Parser request failed: {$url} - " . $e->getMessage(), 'import');
            
            // Если ошибка связана с прокси - пробуем другой
            if ($this->source->proxy_enabled && $this->proxyService) {
                $this->proxyService->markAsFailed($this->currentProxy);
                $this->initClient(); // Переинициализируем клиент с новым прокси
            }
            
            return null;
        }
    }

    /**
     * Проверить на CAPTCHA
     * @param string $html
     * @return bool
     */
    protected function hasCaptcha($html)
    {
        $captchaPatterns = [
            'captcha',
            'g-recaptcha',
            'h-captcha',
            'cf-turnstile',
            'recaptcha',
            'antibot',
        ];

        $htmlLower = mb_strtolower($html, 'UTF-8');
        
        foreach ($captchaPatterns as $pattern) {
            if (strpos($htmlLower, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Решить CAPTCHA
     * @param string $html
     * @param string $url
     * @return string|null Токен решения
     */
    protected function solveCaptcha($html, $url)
    {
        if (!$this->captchaService) {
            return null;
        }

        // Определяем тип CAPTCHA
        $captchaType = $this->detectCaptchaType($html);
        
        if (!$captchaType) {
            return null;
        }

        // Извлекаем site_key
        $siteKey = $this->extractCaptchaSiteKey($html, $captchaType);
        
        if (!$siteKey) {
            return null;
        }

        // Решаем через сервис
        return $this->captchaService->solve($captchaType, $siteKey, $url);
    }

    /**
     * Определить тип CAPTCHA
     * @param string $html
     * @return string|null
     */
    protected function detectCaptchaType($html)
    {
        if (strpos($html, 'g-recaptcha') !== false || strpos($html, 'recaptcha') !== false) {
            return 'recaptcha';
        }
        
        if (strpos($html, 'h-captcha') !== false) {
            return 'hcaptcha';
        }
        
        if (strpos($html, 'cf-turnstile') !== false) {
            return 'turnstile';
        }

        return null;
    }

    /**
     * Извлечь site_key CAPTCHA
     * @param string $html
     * @param string $type
     * @return string|null
     */
    protected function extractCaptchaSiteKey($html, $type)
    {
        $crawler = new Crawler($html);

        $selector = match($type) {
            'recaptcha' => '.g-recaptcha, [data-sitekey]',
            'hcaptcha' => '.h-captcha, [data-sitekey]',
            'turnstile' => '.cf-turnstile, [data-sitekey]',
            default => '[data-sitekey]',
        };

        try {
            $element = $crawler->filter($selector)->first();
            if ($element->count() > 0) {
                return $element->attr('data-sitekey');
            }
        } catch (\Exception $e) {
            // Игнорируем ошибки
        }

        return null;
    }

    /**
     * Установить сервис прокси
     * @param mixed $service
     */
    public function setProxyService($service)
    {
        $this->proxyService = $service;
    }

    /**
     * Установить сервис CAPTCHA
     * @param mixed $service
     */
    public function setCaptchaService($service)
    {
        $this->captchaService = $service;
    }

    // ==================== АБСТРАКТНЫЕ МЕТОДЫ ====================

    /**
     * Спарсить товар по URL
     * @param string $url
     * @return array|null Данные товара
     */
    abstract public function parseProduct($url);

    /**
     * Спарсить категорию товаров
     * @param string $url URL категории
     * @param int $page Номер страницы
     * @return array Список товаров
     */
    abstract public function parseCategory($url, $page = 1);

    /**
     * Поиск товаров
     * @param string $query Поисковый запрос
     * @param int $limit Лимит результатов
     * @return array Список товаров
     */
    abstract public function searchProducts($query, $limit = 50);

    /**
     * Получить список URL товаров из категории
     * @param string $categoryUrl
     * @return array
     */
    abstract public function getProductUrls($categoryUrl);

    /**
     * Нормализовать данные товара под нашу структуру
     * @param array $rawData Сырые данные с сайта
     * @return array Нормализованные данные
     */
    abstract protected function normalizeProductData(array $rawData);

    /**
     * Получить название источника
     * @return string
     */
    abstract public function getSourceName();
}
