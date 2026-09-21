<?php

namespace app\backend\shared\components;

/**
 * Валидация ID сторонних систем веб-аналитики (GA4, Яндекс.Метрика, Meta Pixel)
 * перед рендером скриптов в <head>.
 *
 * Пустое или ещё не заменённое placeholder-значение — валидное состояние:
 * соответствующий скрипт просто не рендерится, без ошибок и артефактов.
 */
class AnalyticsSettings
{
    public static function isValidGa4Id(?string $id): bool
    {
        return !empty($id) && $id !== 'G-XXXXXXXXXX' && strlen($id) > 5;
    }

    public static function isValidMetrikaId(?string $id): bool
    {
        return !empty($id) && $id !== '12345678' && strlen($id) >= 6 && ctype_digit($id);
    }

    public static function isValidMetaPixelId(?string $id): bool
    {
        return !empty($id)
            && $id !== 'META_PIXEL_ID_PLACEHOLDER'
            && $id !== 'XXXXXXXXXXXXXXX'
            && ctype_digit($id)
            && strlen($id) >= 13
            && strlen($id) <= 16;
    }
}
