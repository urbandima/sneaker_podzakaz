<?php

namespace app\backend\modules\admin\services\import;

use Yii;
use yii\base\Component;
use yii\httpclient\Client;

/**
 * CaptchaService — Сервис решения CAPTCHA
 * 
 * Поддержка: 2Captcha, Anti-Captcha, CapMonster
 * Автоматический fallback на резервный сервис
 */
class CaptchaService extends Component
{
    /**
     * API ключи сервисов
     */
    public $apiKey2Captcha;
    public $apiKeyAntiCaptcha;
    public $apiKeyCapMonster;

    /**
     * Приоритет сервисов
     */
    public $priority = ['2captcha', 'anticaptcha', 'capmonster'];

    /**
     * Таймауты (секунды)
     */
    public $solveTimeout = 120;
    public $pollInterval = 5;

    /**
     * HTTP клиенты
     */
    private $clients = [];

    /**
     * Инициализация
     */
    public function init()
    {
        parent::init();
        $this->initClients();
    }

    /**
     * Инициализация HTTP клиентов для сервисов
     */
    protected function initClients()
    {
        $this->clients = [
            '2captcha' => new Client([
                'baseUrl' => 'https://2captcha.com',
                'requestConfig' => ['format' => Client::FORMAT_JSON],
            ]),
            'anticaptcha' => new Client([
                'baseUrl' => 'https://api.anti-captcha.com',
                'requestConfig' => ['format' => Client::FORMAT_JSON],
            ]),
            'capmonster' => new Client([
                'baseUrl' => 'https://api.capmonster.ai',
                'requestConfig' => ['format' => Client::FORMAT_JSON],
            ]),
        ];
    }

    /**
     * Решить CAPTCHA
     * @param string $type Тип CAPTCHA (recaptcha, hcaptcha, turnstile, image)
     * @param string $siteKey Site key
     * @param string $pageUrl URL страницы
     * @param array $options Дополнительные опции
     * @return string|null Токен решения
     */
    public function solve($type, $siteKey, $pageUrl, array $options = [])
    {
        // Пробуем сервисы по приоритету
        foreach ($this->priority as $service) {
            $token = $this->solveWithService($service, $type, $siteKey, $pageUrl, $options);
            
            if ($token) {
                Yii::info("CAPTCHA solved with {$service}", 'import');
                return $token;
            }
        }

        Yii::error("Failed to solve CAPTCHA with all services", 'import');
        return null;
    }

    /**
     * Решить CAPTCHA через конкретный сервис
     * @param string $service
     * @param string $type
     * @param string $siteKey
     * @param string $pageUrl
     * @param array $options
     * @return string|null
     */
    protected function solveWithService($service, $type, $siteKey, $pageUrl, array $options = [])
    {
        $apiKey = $this->getApiKey($service);
        
        if (!$apiKey) {
            return null;
        }

        try {
            // Создаем задачу
            $taskId = $this->createTask($service, $apiKey, $type, $siteKey, $pageUrl, $options);
            
            if (!$taskId) {
                return null;
            }

            // Ждем решения
            return $this->waitForSolution($service, $apiKey, $taskId);
        } catch (\Exception $e) {
            Yii::error("CAPTCHA solve error ({$service}): " . $e->getMessage(), 'import');
            return null;
        }
    }

    /**
     * Получить API ключ для сервиса
     * @param string $service
     * @return string|null
     */
    protected function getApiKey($service)
    {
        return match($service) {
            '2captcha' => $this->apiKey2Captcha,
            'anticaptcha' => $this->apiKeyAntiCaptcha,
            'capmonster' => $this->apiKeyCapMonster,
            default => null,
        };
    }

    /**
     * Создать задачу на решение CAPTCHA
     * @param string $service
     * @param string $apiKey
     * @param string $type
     * @param string $siteKey
     * @param string $pageUrl
     * @param array $options
     * @return string|null Task ID
     */
    protected function createTask($service, $apiKey, $type, $siteKey, $pageUrl, array $options = [])
    {
        $requestData = $this->buildTaskRequest($service, $apiKey, $type, $siteKey, $pageUrl, $options);

        try {
            $response = $this->clients[$service]->post($this->getCreateEndpoint($service), $requestData)->send();
            
            $data = $response->getData();
            
            if ($this->isSuccessResponse($service, $data)) {
                return $this->extractTaskId($service, $data);
            }

            Yii::error("CAPTCHA create task failed ({$service}): " . json_encode($data), 'import');
            return null;
        } catch (\Exception $e) {
            Yii::error("CAPTCHA create task error ({$service}): " . $e->getMessage(), 'import');
            return null;
        }
    }

    /**
     * Сформировать запрос для создания задачи
     * @param string $service
     * @param string $apiKey
     * @param string $type
     * @param string $siteKey
     * @param string $pageUrl
     * @param array $options
     * @return array
     */
    protected function buildTaskRequest($service, $apiKey, $type, $siteKey, $pageUrl, array $options = [])
    {
        if ($service === '2captcha') {
            return [
                'key' => $apiKey,
                'method' => 'userrecaptcha',
                'googlekey' => $siteKey,
                'pageurl' => $pageUrl,
                'json' => 1,
            ];
        }

        // Anti-Captcha и CapMonster используют одинаковый API
        $taskType = $this->getTaskType($service, $type);
        
        return [
            'clientKey' => $apiKey,
            'task' => [
                'type' => $taskType,
                'websiteURL' => $pageUrl,
                'websiteKey' => $siteKey,
            ],
        ];
    }

    /**
     * Получить тип задачи для сервиса
     * @param string $service
     * @param string $type
     * @return string
     */
    protected function getTaskType($service, $type)
    {
        return match($type) {
            'recaptcha' => 'RecaptchaV2TaskProxyless',
            'recaptcha_v3' => 'RecaptchaV3TaskProxyless',
            'hcaptcha' => 'HCaptchaTaskProxyless',
            'turnstile' => 'TurnstileTaskProxyless',
            'image' => 'ImageToTextTask',
            default => 'RecaptchaV2TaskProxyless',
        };
    }

    /**
     * Получить endpoint для создания задачи
     * @param string $service
     * @return string
     */
    protected function getCreateEndpoint($service)
    {
        return match($service) {
            '2captcha' => '/in.php',
            'anticaptcha' => '/createTask',
            'capmonster' => '/createTask',
            default => '/createTask',
        };
    }

    /**
     * Получить endpoint для получения результата
     * @param string $service
     * @return string
     */
    protected function getResultEndpoint($service)
    {
        return match($service) {
            '2captcha' => '/res.php',
            'anticaptcha' => '/getTaskResult',
            'capmonster' => '/getTaskResult',
            default => '/getTaskResult',
        };
    }

    /**
     * Проверить успешность ответа
     * @param string $service
     * @param array $data
     * @return bool
     */
    protected function isSuccessResponse($service, $data)
    {
        if ($service === '2captcha') {
            return isset($data['status']) && $data['status'] === 1;
        }

        return isset($data['errorId']) && $data['errorId'] === 0;
    }

    /**
     * Извлечь Task ID из ответа
     * @param string $service
     * @param array $data
     * @return string|null
     */
    protected function extractTaskId($service, $data)
    {
        if ($service === '2captcha') {
            return $data['request'] ?? null;
        }

        return $data['taskId'] ?? null;
    }

    /**
     * Ожидать решения CAPTCHA
     * @param string $service
     * @param string $apiKey
     * @param string $taskId
     * @return string|null
     */
    protected function waitForSolution($service, $apiKey, $taskId)
    {
        $startTime = time();

        while (time() - $startTime < $this->solveTimeout) {
            sleep($this->pollInterval);

            $result = $this->getTaskResult($service, $apiKey, $taskId);
            
            if ($result !== null) {
                return $result;
            }
        }

        Yii::error("CAPTCHA solve timeout ({$service})", 'import');
        return null;
    }

    /**
     * Получить результат решения
     * @param string $service
     * @param string $apiKey
     * @param string $taskId
     * @return string|null
     */
    protected function getTaskResult($service, $apiKey, $taskId)
    {
        $requestData = $this->buildResultRequest($service, $apiKey, $taskId);

        try {
            $response = $this->clients[$service]->post($this->getResultEndpoint($service), $requestData)->send();
            
            $data = $response->getData();

            if ($service === '2captcha') {
                // 2captcha возвращает готовый ответ или CAPCHA_NOT_READY
                if (isset($data['status']) && $data['status'] === 1) {
                    return $data['request'];
                }
                
                // Если еще не готово
                if (isset($data['request']) && $data['request'] === 'CAPCHA_NOT_READY') {
                    return null;
                }
                
                return null;
            }

            // Anti-Captcha и CapMonster
            if (isset($data['errorId']) && $data['errorId'] === 0) {
                if (isset($data['status']) && $data['status'] === 'ready') {
                    return $data['solution']['gRecaptchaResponse'] ?? $data['solution']['text'] ?? null;
                }
            }

            return null;
        } catch (\Exception $e) {
            Yii::error("CAPTCHA get result error ({$service}): " . $e->getMessage(), 'import');
            return null;
        }
    }

    /**
     * Сформировать запрос для получения результата
     * @param string $service
     * @param string $apiKey
     * @param string $taskId
     * @return array
     */
    protected function buildResultRequest($service, $apiKey, $taskId)
    {
        if ($service === '2captcha') {
            return [
                'key' => $apiKey,
                'action' => 'get',
                'id' => $taskId,
                'json' => 1,
            ];
        }

        return [
            'clientKey' => $apiKey,
            'taskId' => $taskId,
        ];
    }

    /**
     * Получить баланс сервисов
     * @return array
     */
    public function getBalances()
    {
        $balances = [];

        foreach (['2captcha', 'anticaptcha', 'capmonster'] as $service) {
            $apiKey = $this->getApiKey($service);
            
            if (!$apiKey) {
                continue;
            }

            $balance = $this->getBalance($service, $apiKey);
            
            if ($balance !== null) {
                $balances[$service] = $balance;
            }
        }

        return $balances;
    }

    /**
     * Получить баланс конкретного сервиса
     * @param string $service
     * @param string $apiKey
     * @return float|null
     */
    protected function getBalance($service, $apiKey)
    {
        try {
            $endpoint = match($service) {
                '2captcha' => '/res.php?action=getbalance&json=1&key=' . $apiKey,
                'anticaptcha' => '/getBalance',
                'capmonster' => '/getBalance',
                default => '/getBalance',
            };

            $requestData = $service === '2captcha' 
                ? [] 
                : ['clientKey' => $apiKey];

            $response = $this->clients[$service]->post($endpoint, $requestData)->send();
            
            $data = $response->getData();

            if ($service === '2captcha') {
                return isset($data['request']) ? (float) $data['request'] : null;
            }

            return isset($data['balance']) ? (float) $data['balance'] : null;
        } catch (\Exception $e) {
            Yii::error("Get balance error ({$service}): " . $e->getMessage(), 'import');
            return null;
        }
    }

    /**
     * Установить API ключи из источника
     * @param \app\backend\modules\admin\models\import\ImportSource $source
     */
    public function configureFromSource($source)
    {
        // Основной сервис
        if ($source->captcha_service && $source->captcha_api_key) {
            $this->setApiKey($source->captcha_service, $source->captcha_api_key);
        }

        // Резервный сервис
        if ($source->captcha_fallback_service && $source->captcha_fallback_api_key) {
            $this->setApiKey($source->captcha_fallback_service, $source->captcha_fallback_api_key);
            
            // Добавляем в приоритет после основного
            if (!in_array($source->captcha_fallback_service, $this->priority)) {
                $this->priority[] = $source->captcha_fallback_service;
            }
        }

        // Устанавливаем приоритет: основной -> резервный -> остальные
        $mainService = $source->captcha_service;
        $fallbackService = $source->captcha_fallback_service;
        
        $this->priority = array_filter([
            $mainService,
            $fallbackService,
            ...array_diff(['2captcha', 'anticaptcha', 'capmonster'], [$mainService, $fallbackService]),
        ]);
    }

    /**
     * Установить API ключ для сервиса
     * @param string $service
     * @param string $apiKey
     */
    public function setApiKey($service, $apiKey)
    {
        switch ($service) {
            case '2captcha':
                $this->apiKey2Captcha = $apiKey;
                break;
            case 'anticaptcha':
                $this->apiKeyAntiCaptcha = $apiKey;
                break;
            case 'capmonster':
                $this->apiKeyCapMonster = $apiKey;
                break;
        }
    }
}
