<?php

namespace app\components;

use Yii;
use yii\web\ErrorHandler;

/**
 * SentryErrorHandler - Интеграция с Sentry для отслеживания ошибок
 * 
 * Использование: добавить в config/web.php:
 * 'errorHandler' => [
 *     'class' => 'app\components\SentryErrorHandler',
 *     'errorAction' => 'site/error',
 * ],
 */
class SentryErrorHandler extends ErrorHandler
{
    /**
     * @var bool Включена ли отправка в Sentry
     */
    private bool $sentryEnabled = false;

    /**
     * Инициализация Sentry
     */
    public function init(): void
    {
        parent::init();
        
        $dsn = env('SENTRY_DSN');
        
        if ($dsn && !YII_ENV_DEV && class_exists('\Sentry\SentrySdk')) {
            \Sentry\init([
                'dsn' => $dsn,
                'environment' => env('SENTRY_ENVIRONMENT', YII_ENV),
                'release' => $this->getAppVersion(),
                'traces_sample_rate' => (float) env('SENTRY_TRACES_SAMPLE_RATE', 0.1),
                'send_default_pii' => false,
                'before_send' => function (\Sentry\Event $event): ?\Sentry\Event {
                    // Фильтруем некритичные ошибки
                    $exceptions = $event->getExceptions();
                    foreach ($exceptions as $exception) {
                        $type = $exception->getType();
                        // Не отправляем 404 и подобные
                        if (in_array($type, [
                            'yii\web\NotFoundHttpException',
                            'yii\web\ForbiddenHttpException',
                            'yii\web\UnauthorizedHttpException',
                        ])) {
                            return null;
                        }
                    }
                    return $event;
                },
            ]);
            
            $this->sentryEnabled = true;
            
            // Добавляем контекст пользователя
            if (!Yii::$app->user->isGuest) {
                \Sentry\configureScope(function (\Sentry\State\Scope $scope): void {
                    $user = Yii::$app->user->identity;
                    $scope->setUser([
                        'id' => $user->id,
                        'username' => $user->username,
                        'email' => $user->email ?? null,
                    ]);
                });
            }
        }
    }

    /**
     * Обработка исключения с отправкой в Sentry
     */
    public function handleException($exception): void
    {
        if ($this->sentryEnabled) {
            \Sentry\captureException($exception);
        }
        
        parent::handleException($exception);
    }

    /**
     * Обработка фатальной ошибки
     */
    public function handleFatalError(): void
    {
        if ($this->sentryEnabled) {
            $error = error_get_last();
            if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
                \Sentry\captureLastError();
            }
        }
        
        parent::handleFatalError();
    }

    /**
     * Получить версию приложения
     */
    private function getAppVersion(): string
    {
        // Пробуем получить версию из git
        $gitVersion = @shell_exec('git rev-parse --short HEAD 2>/dev/null');
        if ($gitVersion) {
            return trim($gitVersion);
        }
        
        // Fallback на версию из composer.json
        $composerFile = Yii::getAlias('@app/composer.json');
        if (file_exists($composerFile)) {
            $composer = json_decode(file_get_contents($composerFile), true);
            return $composer['version'] ?? '1.0.0';
        }
        
        return '1.0.0';
    }
}
