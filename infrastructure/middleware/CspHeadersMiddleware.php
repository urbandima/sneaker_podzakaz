<?php

namespace app\infrastructure\middleware;

use yii\base\BootstrapInterface;
use yii\web\Application;

/**
 * Content Security Policy Headers Middleware
 * 
 * Добавляет CSP заголовки для защиты от XSS атак
 */
class CspHeadersMiddleware implements BootstrapInterface
{
    public function bootstrap($app)
    {
        if ($app instanceof Application) {
            $app->on(Application::EVENT_BEFORE_REQUEST, function () use ($app) {
                $response = $app->response;
                $headers = $response->headers;
                
                // Content Security Policy
                $csp = [
                    "default-src 'self'",
                    "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://ajax.googleapis.com",
                    "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com",
                    "img-src 'self' data: https: http:",
                    "font-src 'self' data: https://fonts.gstatic.com",
                    "connect-src 'self'",
                    "frame-ancestors 'none'",
                    "base-uri 'self'",
                    "form-action 'self'",
                ];
                
                $headers->set('Content-Security-Policy', implode('; ', $csp));
                
                // X-Content-Type-Options
                $headers->set('X-Content-Type-Options', 'nosniff');
                
                // X-Frame-Options
                $headers->set('X-Frame-Options', 'DENY');
                
                // X-XSS-Protection
                $headers->set('X-XSS-Protection', '1; mode=block');
                
                // Referrer-Policy
                $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
                
                // Permissions-Policy
                $headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');
            });
        }
    }
}
