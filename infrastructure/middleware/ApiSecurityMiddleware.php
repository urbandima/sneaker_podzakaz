<?php

namespace app\infrastructure\middleware;

use Yii;
use yii\base\ActionFilter;
use yii\web\Response;

/**
 * ApiSecurityMiddleware - безопасность API для продакшена
 */
class ApiSecurityMiddleware extends ActionFilter
{
    public $rateLimit = 100; // запросов в минуту
    public $burstLimit = 20;  // пиковых запросов
    
    public function beforeAction($action)
    {
        $request = Yii::$app->request;
        
        // 1. CORS headers
        $this->setCorsHeaders();
        
        // 2. Rate limiting
        if (!$this->checkRateLimit()) {
            $this->rateLimitResponse();
            return false;
        }
        
        // 3. Input validation
        if (!$this->validateInput()) {
            $this->validationErrorResponse();
            return false;
        }
        
        // 4. API Key validation (для защищенных endpoints)
        if ($this->requiresApiKey() && !$this->validateApiKey()) {
            $this->unauthorizedResponse();
            return false;
        }
        
        return parent::beforeAction($action);
    }
    
    /**
     * Установка CORS headers
     */
    private function setCorsHeaders()
    {
        $response = Yii::$app->response;
        $response->headers->set('Access-Control-Allow-Origin', $this->getAllowedOrigin());
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-API-Key');
        $response->headers->set('Access-Control-Max-Age', '86400');
    }
    
    /**
     * Проверка rate limiting
     */
    private function checkRateLimit()
    {
        $key = 'rate_limit:' . $this->getClientId();
        $current = Yii::$app->redis->get($key) ?: 0;
        
        if ($current >= $this->rateLimit) {
            return false;
        }
        
        // Increment counter
        Yii::$app->redis->incr($key);
        Yii::$app->redis->expire($key, 60); // 1 минута
        
        return true;
    }
    
    /**
     * Валидация входных данных
     */
    private function validateInput()
    {
        $request = Yii::$app->request;
        
        // Проверка Content-Type для POST/PUT
        if (in_array($request->method, ['POST', 'PUT']) && !$request->isJson && !$request->isFormData) {
            return false;
        }
        
        // Проверка размера запроса
        if ($request->getContentSize() > 10 * 1024 * 1024) { // 10MB
            return false;
        }
        
        return true;
    }
    
    /**
     * Валидация API Key
     */
    private function validateApiKey()
    {
        $apiKey = Yii::$app->request->headers->get('X-API-Key');
        
        if (!$apiKey) {
            return false;
        }
        
        // Проверка в Redis или базе данных
        $validKeys = Yii::$app->params['api_keys'] ?? [];
        return in_array($apiKey, $validKeys);
    }
    
    /**
     * Требуется ли API Key
     */
    private function requiresApiKey()
    {
        $protectedRoutes = [
            '/api/v1/admin/',
            '/api/v1/orders/create',
            '/api/v1/products/update'
        ];
        
        $path = Yii::$app->request->pathInfo;
        
        foreach ($protectedRoutes as $route) {
            if (strpos($path, $route) === 0) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Получение ID клиента для rate limiting
     */
    private function getClientId()
    {
        $request = Yii::$app->request;
        
        // Используем IP + User-Agent
        return md5($request->userIP . $request->userAgent);
    }
    
    /**
     * Получение разрешенного Origin для CORS
     */
    private function getAllowedOrigin()
    {
        $allowedOrigins = Yii::$app->params['allowed_origins'] ?? [
            'http://localhost:8082',
            'https://sneaker-head.by'
        ];
        
        $origin = Yii::$app->request->headers->get('Origin');
        
        return in_array($origin, $allowedOrigins) ? $origin : $allowedOrigins[0];
    }
    
    /**
     * Ответ при превышении rate limit
     */
    private function rateLimitResponse()
    {
        Yii::$app->response->statusCode = 429;
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = [
            'error' => 'Rate limit exceeded',
            'message' => 'Too many requests. Please try again later.',
            'retry_after' => 60
        ];
    }
    
    /**
     * Ответ при ошибке валидации
     */
    private function validationErrorResponse()
    {
        Yii::$app->response->statusCode = 400;
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = [
            'error' => 'Validation failed',
            'message' => 'Invalid request format or size'
        ];
    }
    
    /**
     * Ответ при неверном API Key
     */
    private function unauthorizedResponse()
    {
        Yii::$app->response->statusCode = 401;
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = [
            'error' => 'Unauthorized',
            'message' => 'Invalid or missing API key'
        ];
    }
}
