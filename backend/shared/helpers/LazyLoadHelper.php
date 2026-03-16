<?php

/**
 * LazyLoadHelper — Хелпер для ленивой загрузки изображений
 * 
 * НАЗНАЧЕНИЕ:
 * Оптимизация производительности страницы за счёт
 * отложенной загрузки изображений (native + JS fallback).
 * 
 * МЕТОДЫ:
 * - img(): создать <img> тег с lazy loading
 * - picture(): создать <picture> тег с lazy loading
 * - placeholder(): получить placeholder URL
 * 
 * КОНСТАНТЫ:
 * - PLACEHOLDER: 1x1 прозрачный пиксель (data URI)
 * - SVG_PLACEHOLDER: SVG placeholder с градиентом
 * 
 * ОСОБЕННОСТИ:
 * - Нативный lazy loading (loading="lazy")
 * - Асинхронное декодирование (decoding="async")
 * - JS fallback для старых браузеров
 * - Placeholder для отложенных изображений
 * 
 * ИСПОЛЬЗОВАНИЕ:
 * ```php
 * echo LazyLoadHelper::img('/uploads/product.jpg', ['alt' => 'Product']);
 * ```
 */
namespace app\backend\shared\helpers;

use yii\helpers\Html;

/**
 * LazyLoadHelper - Хелпер для ленивой загрузки изображений
 * 
 * Улучшает производительность страницы за счет отложенной загрузки изображений
 */
class LazyLoadHelper
{
    /**
     * Placeholder для изображений (1x1 прозрачный пиксель)
     */
    const PLACEHOLDER = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';
    
    /**
     * SVG placeholder с градиентом
     */
    const SVG_PLACEHOLDER = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 400"%3E%3Crect fill="%23f5f5f5" width="400" height="400"/%3E%3C/svg%3E';

    /**
     * Создать img тег с ленивой загрузкой
     * 
     * @param string $src URL изображения
     * @param array $options HTML атрибуты
     * @return string HTML img tag
     */
    public static function img(string $src, array $options = []): string
    {
        // Добавляем loading="lazy" для нативной ленивой загрузки
        $options['loading'] = $options['loading'] ?? 'lazy';
        
        // Добавляем decoding="async" для асинхронного декодирования
        $options['decoding'] = $options['decoding'] ?? 'async';
        
        // Если указан data-src, используем placeholder
        if (isset($options['data-src'])) {
            $options['src'] = self::SVG_PLACEHOLDER;
            $options['class'] = trim(($options['class'] ?? '') . ' lazy-image');
        } else {
            $options['src'] = $src;
        }
        
        // Alt текст обязателен для SEO
        if (!isset($options['alt'])) {
            $options['alt'] = '';
        }
        
        return Html::tag('img', '', $options);
    }

    /**
     * Создать img тег с ленивой загрузкой и placeholder
     * 
     * @param string $src URL изображения
     * @param string|null $alt Alt текст
     * @param array $options HTML атрибуты
     * @return string HTML img tag
     */
    public static function lazyImg(string $src, ?string $alt = '', array $options = []): string
    {
        $options['data-src'] = $src;
        $options['alt'] = $alt;
        $options['src'] = self::SVG_PLACEHOLDER;
        $options['loading'] = 'lazy';
        $options['decoding'] = 'async';
        $options['class'] = trim(($options['class'] ?? '') . ' lazy-image');
        
        return Html::tag('img', '', $options);
    }

    /**
     * Создать picture элемент с WebP и fallback
     * 
     * @param string $src URL изображения (jpg/png)
     * @param string|null $webpSrc URL WebP версии (опционально)
     * @param string|null $alt Alt текст
     * @param array $options HTML атрибуты для img
     * @return string HTML picture tag
     */
    public static function picture(string $src, ?string $webpSrc = null, ?string $alt = '', array $options = []): string
    {
        $sources = [];
        
        // WebP source если есть
        if ($webpSrc) {
            $sources[] = Html::tag('source', '', [
                'srcset' => $webpSrc,
                'type' => 'image/webp',
            ]);
        } else {
            // Пробуем найти WebP версию автоматически
            $webpPath = preg_replace('/\.(jpe?g|png)$/i', '.webp', $src);
            if ($webpPath !== $src) {
                $sources[] = Html::tag('source', '', [
                    'data-srcset' => $webpPath,
                    'type' => 'image/webp',
                ]);
            }
        }
        
        // Fallback img
        $imgOptions = array_merge($options, [
            'loading' => 'lazy',
            'decoding' => 'async',
            'alt' => $alt,
        ]);
        
        $img = Html::img($src, $imgOptions);
        
        return Html::tag('picture', implode("\n", $sources) . "\n" . $img);
    }

    /**
     * Создать srcset для responsive изображений
     * 
     * @param string $baseSrc Базовый URL изображения
     * @param array $sizes Массив размеров [width => url]
     * @return string srcset строка
     */
    public static function srcset(string $baseSrc, array $sizes = []): string
    {
        if (empty($sizes)) {
            // Генерируем размеры автоматически
            $sizes = [
                320 => self::getResizedUrl($baseSrc, 320),
                640 => self::getResizedUrl($baseSrc, 640),
                960 => self::getResizedUrl($baseSrc, 960),
                1280 => self::getResizedUrl($baseSrc, 1280),
            ];
        }
        
        $srcset = [];
        foreach ($sizes as $width => $url) {
            $srcset[] = "{$url} {$width}w";
        }
        
        return implode(', ', $srcset);
    }

    /**
     * Получить URL для изменённого размера (placeholder)
     * В production используйте CDN с resize-on-the-fly
     * 
     * @param string $src Оригинальный URL
     * @param int $width Желаемая ширина
     * @return string URL
     */
    private static function getResizedUrl(string $src, int $width): string
    {
        // Для production можно интегрировать с CDN:
        // return "https://cdn.example.com/resize/{$width}/{$src}";
        
        // По умолчанию возвращаем оригинал
        return $src;
    }

    /**
     * Создать background lazy load div
     * 
     * @param string $bgUrl URL фонового изображения
     * @param array $options HTML атрибуты
     * @return string HTML div tag
     */
    public static function lazyBackground(string $bgUrl, array $options = []): string
    {
        $options['data-bg'] = $bgUrl;
        $options['class'] = trim(($options['class'] ?? '') . ' lazy-bg');
        
        return Html::tag('div', $options['content'] ?? '', $options);
    }

    /**
     * Получить JavaScript для инициализации ленивой загрузки
     * 
     * @return string JavaScript код
     */
    public static function getInitScript(): string
    {
        return <<<JS
(function() {
    // Native lazy loading fallback
    if ('loading' in HTMLImageElement.prototype) {
        document.querySelectorAll('img.lazy-image[data-src]').forEach(img => {
            img.src = img.dataset.src;
            img.classList.remove('lazy-image');
        });
    } else {
        // Intersection Observer fallback
        const lazyImages = document.querySelectorAll('.lazy-image[data-src]');
        
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src;
                        img.classList.remove('lazy-image');
                        observer.unobserve(img);
                    }
                });
            }, {
                rootMargin: '50px 0px',
                threshold: 0.01
            });
            
            lazyImages.forEach(img => imageObserver.observe(img));
        } else {
            // Fallback: load all images
            lazyImages.forEach(img => {
                img.src = img.dataset.src;
                img.classList.remove('lazy-image');
            });
        }
    }
    
    // Background images lazy load
    const lazyBgs = document.querySelectorAll('.lazy-bg[data-bg]');
    if ('IntersectionObserver' in window) {
        const bgObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const el = entry.target;
                    el.style.backgroundImage = 'url(' + el.dataset.bg + ')';
                    el.classList.remove('lazy-bg');
                    observer.unobserve(el);
                }
            });
        });
        
        lazyBgs.forEach(el => bgObserver.observe(el));
    }
})();
JS;
    }
}
