<?php

/**
 * SecurityHeadersMiddleware — Добавление security headers
 * 
 * НАЗНАЧЕНИЕ:
 * Автоматическое добавление HTTP заголовков безопасности ко всем ответам.
 * 
 * HEADERS:
 * - X-Frame-Options: защита от clickjacking
 * - X-Content-Type-Options: защита от MIME sniffing
 * - X-XSS-Protection: XSS фильтр (устарел, но добавлен для старых браузеров)
 * - Referrer-Policy: контроль Referer
 * - Permissions-Policy: контроль API доступа
 * - Content-Security-Policy: защита от XSS и инъекций
 * - Strict-Transport-Security: принудительный HTTPS
 * 
 * ИСПОЛЬЗОВАНИЕ:
 * Подключить в конфиге как bootstrap компонент или в контроллере.
 */
namespace app\backend\shared\middleware;

use Yii;
use yii\base\Behavior;
use yii\web\Controller;

class SecurityHeadersMiddleware extends Behavior
{
    /**
     * Включить CSP header
     * @var bool
     */
    public bool $enableCsp = true;
    
    /**
     * Включить HSTS header
     * @var bool
     */
    public bool $enableHsts = true;
    
    /**
     * CSP директивы
     * @var array
     */
    public array $cspDirectives = [
        'default-src' => "'self'",
        'script-src' => "'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://code.jquery.com",
        'style-src' => "'self' 'unsafe-inline' https://fonts.googleapis.com",
        'img-src' => "'self' data: https: blob:",
        'font-src' => "'self' https://fonts.gstatic.com data:",
        'connect-src' => "'self' https:",
        'frame-ancestors' => "'self'",
        'base-uri' => "'self'",
        'form-action' => "'self'",
        'object-src' => "'none'",
    ];
    
    /**
     * Разрешённые домены для CSP (расширяется через конфиг)
     * @var array
     */
    public array $allowedDomains = [];
    
    public function events(): array
    {
        return [
            Controller::EVENT_BEFORE_ACTION => 'addSecurityHeaders',
        ];
    }
    
    /**
     * Добавить security headers к ответу
     */
    public function addSecurityHeaders($event): void
    {
        $response = Yii::$app->response;
        $headers = $response->headers;
        
        // X-Frame-Options: защита от clickjacking
        $headers->set('X-Frame-Options', 'SAMEORIGIN');
        
        // X-Content-Type-Options: защита от MIME sniffing
        $headers->set('X-Content-Type-Options', 'nosniff');
        
        // X-XSS-Protection: XSS фильтр (для старых браузеров)
        $headers->set('X-XSS-Protection', '1; mode=block');
        
        // Referrer-Policy: контроль Referer
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        
        // Permissions-Policy: контроль API доступа
        $permissions = [
            'geolocation=()',
            'microphone=()',
            'camera=()',
            'payment=()',
            'usb=()',
            'magnetometer=()',
            'gyroscope=()',
            'accelerometer=()',
        ];
        $headers->set('Permissions-Policy', implode(', ', $permissions));
        
        // Content-Security-Policy: защита от XSS и инъекций
        if ($this->enableCsp) {
            $csp = $this->buildCsp();
            $headers->set('Content-Security-Policy', $csp);
        }
        
        // Strict-Transport-Security: принудительный HTTPS (только в проде)
        if ($this->enableHsts && YII_ENV_PROD) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }
        
        // Cache-Control для API endpoints
        if (Yii::$app->request->isAjax || strpos(Yii::$app->request->pathInfo, 'api/') === 0) {
            $headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
            $headers->set('Pragma', 'no-cache');
            $headers->set('Expires', '0');
        }
    }
    
    /**
     * Построить CSP header
     */
    private function buildCsp(): string
    {
        $directives = [];
        
        foreach ($this->cspDirectives as $directive => $value) {
            $directives[] = "{$directive} {$value}";
        }
        
        // Добавляем разрешённые домены из конфига
        foreach ($this->allowedDomains as $directive => $domains) {
            if (is_array($domains)) {
                $domains = implode(' ', $domains);
            }
            $directives[] = "{$directive} {$domains}";
        }
        
        return implode('; ', $directives);
    }
    
    /**
     * Получить nonce для inline скриптов
     * Использовать: <script nonce="<?= SecurityHeadersMiddleware::getNonce() ?>">
     */
    public static function getNonce(): string
    {
        if (!isset(Yii::$app->params['cspNonce'])) {
            Yii::$app->params['cspNonce'] = base64_encode(random_bytes(16));
        }
        return Yii::$app->params['cspNonce'];
    }
    
    /**
     * Добавить домен в whitelist CSP
     */
    public function addAllowedDomain(string $directive, string $domain): void
    {
        if (!isset($this->allowedDomains[$directive])) {
            $this->allowedDomains[$directive] = [];
        }
        $this->allowedDomains[$directive][] = $domain;
    }
}
