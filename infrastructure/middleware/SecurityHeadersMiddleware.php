<?php

/**
 * SecurityHeadersMiddleware — Middleware для добавления security headers
 * 
 * НАЗНАЧЕНИЕ:
 * Добавление заголовков безопасности для защиты от XSS, clickjacking,
 * MIME-sniffing и других атак.
 * 
 * HEADERS:
 * - X-Frame-Options: защита от clickjacking
 * - X-Content-Type-Options: защита от MIME-sniffing
 * - X-XSS-Protection: защита от XSS (для старых браузеров)
 * - Referrer-Policy: контроль передачи referrer
 * - Content-Security-Policy: защита от XSS и injection атак
 */
namespace app\infrastructure\middleware;

use Yii;
use yii\base\ActionFilter;

class SecurityHeadersMiddleware extends ActionFilter
{
    /**
     * @var bool Включить строгий CSP (может сломать некоторый функционал)
     */
    public $strictCsp = false;

    /**
     * @var array Дополнительные CSP директивы
     */
    public $cspDirectives = [];

    /**
     * Выполняется перед действием
     */
    public function beforeAction($action)
    {
        $response = Yii::$app->response;
        $headers = $response->headers;

        // X-Frame-Options: защита от clickjacking
        $headers->set('X-Frame-Options', 'SAMEORIGIN');

        // X-Content-Type-Options: защита от MIME-sniffing
        $headers->set('X-Content-Type-Options', 'nosniff');

        // X-XSS-Protection: защита от XSS (для старых браузеров)
        $headers->set('X-XSS-Protection', '1; mode=block');

        // Referrer-Policy: контроль передачи referrer
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Permissions-Policy: ограничение доступа к API браузера
        $headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');

        // Content-Security-Policy
        $csp = $this->buildCsp();
        if ($csp) {
            $headers->set('Content-Security-Policy', $csp);
        }

        // Strict-Transport-Security (только для HTTPS)
        if (Yii::$app->request->isSecureConnection) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return parent::beforeAction($action);
    }

    /**
     * Построить Content-Security-Policy
     */
    protected function buildCsp(): string
    {
        $defaultDirectives = [
            "default-src" => "'self'",
            "script-src" => "'self' https://cdn.jsdelivr.net https://unpkg.com",
            "style-src" => "'self' https://cdn.jsdelivr.net https://fonts.googleapis.com",
            "img-src" => "'self' data: https: blob:",
            "font-src" => "'self' data: https://fonts.gstatic.com https://cdn.jsdelivr.net",
            "connect-src" => "'self'",
            "frame-ancestors" => "'self'",
            "base-uri" => "'self'",
            "form-action" => "'self'",
        ];

        // Строгий CSP (для production)
        if ($this->strictCsp) {
            $defaultDirectives["script-src"] = "'self'";
            $defaultDirectives["style-src"] = "'self'";
        }

        // Объединяем с пользовательскими директивами
        $directives = array_merge($defaultDirectives, $this->cspDirectives);

        $cspParts = [];
        foreach ($directives as $key => $value) {
            $cspParts[] = "$key $value";
        }

        return implode('; ', $cspParts);
    }
}
