<?php

namespace app\backend\security\sanitizers;

/**
 * Очистка входных данных
 */
class InputSanitizer
{
    /**
     * Очистка строки от XSS
     */
    public static function sanitizeString(string $input): string
    {
        return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Очистка HTML (разрешённые теги)
     */
    public static function sanitizeHtml(string $input, array $allowedTags = ['p', 'b', 'i', 'u', 'br', 'ul', 'ol', 'li']): string
    {
        $allowed = '<' . implode('><', $allowedTags) . '>';
        return strip_tags($input, $allowed);
    }

    /**
     * Очистка числа
     */
    public static function sanitizeInt($input): int
    {
        return (int) filter_var($input, FILTER_SANITIZE_NUMBER_INT);
    }

    /**
     * Очистка float
     */
    public static function sanitizeFloat($input): float
    {
        return (float) filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    }

    /**
     * Очистка email
     */
    public static function sanitizeEmail(string $email): string
    {
        return filter_var($email, FILTER_SANITIZE_EMAIL);
    }

    /**
     * Очистка URL
     */
    public static function sanitizeUrl(string $url): string
    {
        return filter_var($url, FILTER_SANITIZE_URL);
    }

    /**
     * Очистка массива
     */
    public static function sanitizeArray(array $input, string $type = 'string'): array
    {
        return array_map(function($value) use ($type) {
            return match($type) {
                'int' => self::sanitizeInt($value),
                'float' => self::sanitizeFloat($value),
                'email' => self::sanitizeEmail($value),
                'url' => self::sanitizeUrl($value),
                default => self::sanitizeString($value),
            };
        }, $input);
    }

    /**
     * Удаление SQL injection паттернов
     */
    public static function sanitizeSql(string $input): string
    {
        $patterns = [
            '/SELECT\s+/i',
            '/INSERT\s+/i',
            '/UPDATE\s+/i',
            '/DELETE\s+/i',
            '/DROP\s+/i',
            '/UNION\s+/i',
            '/--/',
            '/\/\*/',
            '/\*\//',
        ];

        return preg_replace($patterns, '', $input);
    }
}
