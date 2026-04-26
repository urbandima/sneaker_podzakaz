<?php

namespace app\components;

use Yii;
use yii\base\Exception as YiiException;

/**
 * MaskingErrorHandler — belt-and-suspenders security layer on top of SentryErrorHandler.
 *
 * Guarantees that sensitive environment variables (passwords, keys, tokens) are never
 * rendered in browser error pages even if YII_DEBUG accidentally becomes true.
 *
 * Behaviour:
 * - Scrubs $_SERVER keys matching SECRET|KEY|PASSWORD|TOKEN|DSN|PASS before any rendering.
 * - For guests (non-admin requests) always returns a generic 500 without stack traces.
 * - Logs the full exception (with original context) to runtime/logs/app.log first.
 */
class MaskingErrorHandler extends SentryErrorHandler
{
    /** @var string[] $_SERVER key patterns to mask */
    public array $sensitivePatterns = ['SECRET', 'KEY', 'PASSWORD', 'TOKEN', 'DSN', 'PASS', 'AUTH', 'SALT'];

    public function handleException($exception): void
    {
        // Log full details BEFORE masking so the file log gets the real context
        $this->logFullException($exception);

        // Scrub sensitive vars from $_SERVER so they cannot appear in any rendering path
        $this->maskSensitiveServerVars();

        parent::handleException($exception);
    }

    protected function renderException($exception): void
    {
        // Double-check: scrub again in case something re-populated $_SERVER
        $this->maskSensitiveServerVars();

        // Non-authenticated requests always get a clean generic response, regardless of YII_DEBUG
        if ($this->shouldHideDetails()) {
            $this->renderGenericError($exception);
            return;
        }

        parent::renderException($exception);
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function maskSensitiveServerVars(): void
    {
        foreach (array_keys($_SERVER) as $key) {
            $upper = strtoupper($key);
            foreach ($this->sensitivePatterns as $pattern) {
                if (str_contains($upper, $pattern)) {
                    $_SERVER[$key] = '[MASKED]';
                    break;
                }
            }
        }
    }

    private function shouldHideDetails(): bool
    {
        try {
            if (Yii::$app === null || Yii::$app->user === null) {
                return true;
            }
            if (Yii::$app->user->isGuest) {
                return true;
            }
            // Only admins may see debug output
            $identity = Yii::$app->user->identity;
            if ($identity && method_exists($identity, 'isAdmin')) {
                return !$identity->isAdmin();
            }
            if ($identity && method_exists($identity, 'hasRole')) {
                return !$identity->hasRole('admin');
            }
            return true;
        } catch (\Throwable $e) {
            return true;
        }
    }

    private function renderGenericError(\Throwable $exception): void
    {
        $statusCode = $exception instanceof \yii\web\HttpException ? $exception->statusCode : 500;
        $statusCode = ($statusCode >= 400 && $statusCode < 600) ? $statusCode : 500;

        if (!headers_sent()) {
            http_response_code($statusCode);
            header('Content-Type: text/html; charset=utf-8');
        }

        // Try to use the configured error action for a styled page
        if ($this->errorAction !== null) {
            try {
                $this->exception = $exception;
                Yii::$app->runAction($this->errorAction);
                return;
            } catch (\Throwable $e) {
                // Fall through to bare HTML
            }
        }

        $message = $statusCode === 404 ? 'Страница не найдена' : 'Произошла ошибка. Пожалуйста, попробуйте позже.';
        echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>' . $statusCode . '</title></head>'
            . '<body style="font-family:sans-serif;text-align:center;padding:60px">'
            . '<h1>' . $statusCode . '</h1><p>' . htmlspecialchars($message) . '</p></body></html>';
    }

    private function logFullException(\Throwable $exception): void
    {
        try {
            $message = get_class($exception) . ': ' . $exception->getMessage()
                . "\nFile: " . $exception->getFile() . ':' . $exception->getLine()
                . "\nTrace:\n" . $exception->getTraceAsString();

            Yii::error($message, 'exception');
        } catch (\Throwable $e) {
            // Logging must never cause another error
        }
    }
}
