<?php

/**
 * SitemapNotifier — Компонент планирования регенерации sitemap
 * 
 * НАЗНАЧЕНИЕ:
 * Планирование регенерации sitemap.xml при изменении контента.
 * Использует кэш для хранения флага необходимости обновления.
 * 
 * ФУНКЦИИ:
 * - scheduleRegeneration(): пометить sitemap как требующий обновления
 * - isPending(): проверить, требуется ли регенерация
 * - reset(): сбросить флаг после генерации
 * - getLastRun(): получить время последней генерации
 * 
 * КОНСТАНТЫ:
 * - CACHE_KEY_PENDING: ключ флага ожидания
 * - CACHE_KEY_LAST_RUN: ключ времени последнего запуска
 * - TTL_PENDING: время жизни флага (24 часа)
 * 
 * ИСПОЛЬЗОВАНИЕ:
 * ```php
 * // После изменения товара
 * SitemapNotifier::scheduleRegeneration();
 * 
 * // Проверка в SitemapAutoGenerator
 * if (SitemapNotifier::isPending()) {
 *     // генерация sitemap
 *     SitemapNotifier::reset();
 * }
 * ```
 * 
 * ОСОБЕННОСТИ:
 * - Лёгкий флаг в кэше
 * - Не блокирует основной запрос
 * - Интеграция с моделями через behaviors
 */
namespace app\backend\shared\components;

use Yii;

/**
 * Планировщик регенерации sitemap.
 * Использует кэш (Redis/FileCache) для хранения флага необходимости обновления.
 */
class SitemapNotifier
{
    private const CACHE_KEY_PENDING = 'sitemap:pending';
    private const CACHE_KEY_LAST_RUN = 'sitemap:lastRun';
    private const CACHE_KEY_PENDING_SINCE = 'sitemap:pendingSince';
    private const TTL_PENDING = 86400; // 24 часа

    /**
     * Пометить sitemap как требующий регенерации.
     */
    public static function scheduleRegeneration(): void
    {
        $cache = Yii::$app->cache;
        if (!$cache) {
            return;
        }

        $now = time();
        $cache->set(self::CACHE_KEY_PENDING, $now, self::TTL_PENDING);
        $cache->set(self::CACHE_KEY_PENDING_SINCE, $now, self::TTL_PENDING);
    }

    /**
     * Проверить, требуется ли регенерация.
     */
    public static function isPending(): bool
    {
        $cache = Yii::$app->cache;
        if (!$cache) {
            return false;
        }

        return (bool) $cache->get(self::CACHE_KEY_PENDING);
    }

    /**
     * Сбросить флаг ожидания и записать время последнего обновления.
     */
    public static function reset(): void
    {
        $cache = Yii::$app->cache;
        if (!$cache) {
            return;
        }

        $cache->delete(self::CACHE_KEY_PENDING);
        $cache->set(self::CACHE_KEY_LAST_RUN, time(), self::TTL_PENDING);
        $cache->delete(self::CACHE_KEY_PENDING_SINCE);
    }

    /**
     * Получить timestamp последней успешной генерации.
     */
    public static function getLastRun(): ?int
    {
        $cache = Yii::$app->cache;
        if (!$cache) {
            return null;
        }

        return $cache->get(self::CACHE_KEY_LAST_RUN) ?: null;
    }

    public static function getPendingTimestamp(): ?int
    {
        $cache = Yii::$app->cache;
        if (!$cache) {
            return null;
        }

        return $cache->get(self::CACHE_KEY_PENDING_SINCE) ?: null;
    }
}
