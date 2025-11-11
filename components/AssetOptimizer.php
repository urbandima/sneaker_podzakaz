<?php

namespace app\components;

use Yii;
use yii\base\Component;
use yii\web\View;

/**
 * AssetOptimizer - Компонент для оптимизации загрузки CSS/JS
 * 
 * Основные функции:
 * - Извлечение и inline-вставка критического CSS
 * - Отложенная загрузка некритичных CSS
 * - Оптимизация JS с defer/async
 * - Preload/Prefetch стратегии
 * - Управление приоритетом ресурсов
 * 
 * Использование:
 * ```php
 * AssetOptimizer::optimizeCatalogPage($this);
 * ```
 */
class AssetOptimizer extends Component
{
    /**
     * Критический CSS для inline-вставки (выше 4KB - оптимально для First Paint)
     */
    const CRITICAL_CSS_FILE = '@webroot/css/critical.css';
    
    /**
     * Некритичные CSS для отложенной загрузки
     */
    const DEFERRED_CSS = [
        'catalog-card' => '@web/css/catalog-card.css',
        'catalog-inline' => '@web/css/catalog-inline.css',
    ];
    
    /**
     * JS файлы с настройками приоритета
     */
    const SCRIPTS_CONFIG = [
        // Критичные - defer (выполняются после парсинга HTML)
        'critical' => [
            'catalog' => '@web/js/catalog.js',
            'cart' => '@web/js/cart.js',
        ],
        // Некритичные - async + lazy (выполняются независимо)
        'deferred' => [
            'view-history' => '@web/js/view-history.js',
            'ui-enhancements' => '@web/js/ui-enhancements.js',
            'wishlist-share' => '@web/js/wishlist-share.js',
        ],
        // Интерактивные - defer (нужны для UX)
        'interactive' => [
            'price-slider' => '@web/js/price-slider.js',
            'favorites' => '@web/js/favorites.js',
        ],
    ];

    /**
     * Оптимизация страницы каталога
     * 
     * @param View $view
     * @param array $options Дополнительные опции
     */
    public static function optimizeCatalogPage($view, $options = [])
    {
        // 1. Inline критический CSS
        self::inlineCriticalCSS($view);
        
        // 2. Preload критичных ресурсов
        self::preloadCriticalAssets($view, [
            'fonts' => $options['fonts'] ?? [],
            'images' => $options['images'] ?? [],
        ]);
        
        // 3. Отложенная загрузка некритичных CSS
        self::deferNonCriticalCSS($view, [
            'catalog-card',
            'mobile-first',
        ]);
        
        // 4. Оптимизация JS
        self::optimizeScripts($view, [
            'critical' => ['catalog', 'cart'],
            'deferred' => ['view-history', 'ui-enhancements'],
            'interactive' => ['price-slider'],
        ]);
        
        // 5. Prefetch для следующих страниц
        self::prefetchNextPages($view);
    }

    /**
     * Оптимизация страницы товара
     * 
     * @param View $view
     * @param array $options
     */
    public static function optimizeProductPage($view, $options = [])
    {
        // 1. Inline критический CSS
        self::inlineCriticalCSS($view);
        
        // 2. Preload главного изображения и шрифтов
        self::preloadCriticalAssets($view, [
            'fonts' => $options['fonts'] ?? [],
            'images' => $options['mainImage'] ? [$options['mainImage']] : [],
        ]);
        
        // 3. Отложенная загрузка CSS
        self::deferNonCriticalCSS($view, [
            'product',
        ]);
        
        // 4. JS с приоритетами
        self::optimizeScripts($view, [
            'critical' => ['cart'],
            'interactive' => ['favorites'],
            'deferred' => ['view-history', 'wishlist-share'],
        ]);
    }

    /**
     * Вставка критического CSS inline
     * Устраняет render-blocking для первого экрана
     * 
     * @param View $view
     */
    protected static function inlineCriticalCSS($view)
    {
        $criticalPath = Yii::getAlias(self::CRITICAL_CSS_FILE);
        
        if (file_exists($criticalPath)) {
            $criticalCSS = file_get_contents($criticalPath);
            
            // Минификация (простая)
            $criticalCSS = self::minifyCSS($criticalCSS);
            
            // В dev режиме добавляем комментарий с версией для отладки
            if (YII_ENV_DEV) {
                $version = filemtime($criticalPath);
                $criticalCSS = "/* Critical CSS v{$version} */ " . $criticalCSS;
            }
            
            // Вставка в <head> с уникальным ключом на основе версии файла
            $key = 'critical-css';
            if (YII_ENV_DEV) {
                $key .= '-' . filemtime($criticalPath);
            }
            
            $view->registerCss($criticalCSS, [
                'position' => View::POS_HEAD,
            ], $key);
        }
    }

    /**
     * Отложенная загрузка некритичных CSS
     * Используем rel="preload" + onload="this.rel='stylesheet'"
     * 
     * @param View $view
     * @param array $cssKeys Ключи CSS из DEFERRED_CSS
     */
    protected static function deferNonCriticalCSS($view, $cssKeys = [])
    {
        foreach ($cssKeys as $key) {
            if (isset(self::DEFERRED_CSS[$key])) {
                // Файлы уже в web/, используем URL напрямую
                $href = Yii::getAlias(self::DEFERRED_CSS[$key]);
                
                // Preload с трансформацией в stylesheet
                $view->registerLinkTag([
                    'rel' => 'preload',
                    'as' => 'style',
                    'href' => $href . '?v=' . self::getFileVersion($key),
                    'onload' => "this.onload=null;this.rel='stylesheet'",
                ], $key . '-preload');
                
                // Noscript fallback
                $view->registerMetaTag([
                    'name' => 'noscript',
                    'content' => "<link rel='stylesheet' href='$href'>",
                ], $key . '-noscript');
            }
        }
        
        // Polyfill для старых браузеров
        $view->registerJs(<<<'JS'
!function(){"use strict";var e=function(e,t,n){var r,o=window.document,i=o.createElement("link");if(t)r=t;else{var a=(o.body||o.getElementsByTagName("head")[0]).childNodes;r=a[a.length-1]}var l=o.styleSheets;if(n)for(var s=0;s<l.length;s++){var d=l[s];if(d.href==i.href)return}i.rel="stylesheet",i.href=e,i.media="only x",function e(t){if(o.body)return t();setTimeout(function(){e(t)})}(function(){r.parentNode.insertBefore(i,t?r:r.nextSibling)});var c=function(e){for(var t=i.href,n=l.length;n--;)if(l[n].href===t)return e();setTimeout(function(){c(e)})};return i.addEventListener&&i.addEventListener("load",c),i.onloadcssdefined=c,c(function(){i.media=n||"all"}),i};"undefined"!=typeof exports?exports.loadCSS=e:window.loadCSS=e}();
JS
        , View::POS_HEAD, 'loadcss-polyfill');
    }

    /**
     * Оптимизация JS загрузки с defer/async
     * 
     * @param View $view
     * @param array $scriptsConfig Конфигурация скриптов
     */
    protected static function optimizeScripts($view, $scriptsConfig)
    {
        // Критичные - defer (блокируют DOMContentLoaded, но не парсинг)
        if (!empty($scriptsConfig['critical'])) {
            foreach ($scriptsConfig['critical'] as $key) {
                if (isset(self::SCRIPTS_CONFIG['critical'][$key])) {
                    $view->registerJsFile(
                        self::SCRIPTS_CONFIG['critical'][$key],
                        [
                            'position' => View::POS_HEAD,
                            'defer' => true,
                        ],
                        $key . '-js'
                    );
                }
            }
        }
        
        // Интерактивные - defer (нужны для UX)
        if (!empty($scriptsConfig['interactive'])) {
            foreach ($scriptsConfig['interactive'] as $key) {
                if (isset(self::SCRIPTS_CONFIG['interactive'][$key])) {
                    $view->registerJsFile(
                        self::SCRIPTS_CONFIG['interactive'][$key],
                        [
                            'position' => View::POS_END,
                            'defer' => true,
                        ],
                        $key . '-js'
                    );
                }
            }
        }
        
        // Некритичные - requestIdleCallback (ленивая загрузка)
        if (!empty($scriptsConfig['deferred'])) {
            $deferredScripts = [];
            foreach ($scriptsConfig['deferred'] as $key) {
                if (isset(self::SCRIPTS_CONFIG['deferred'][$key])) {
                    $deferredScripts[] = self::SCRIPTS_CONFIG['deferred'][$key];
                }
            }
            
            $scriptsJson = json_encode($deferredScripts, JSON_UNESCAPED_SLASHES);
            
            $view->registerJs(<<<JS
(function(){
    var scripts = $scriptsJson;
    var loadScripts = function(){
        scripts.forEach(function(src){
            var s = document.createElement('script');
            s.src = src;
            s.defer = true;
            document.head.appendChild(s);
        });
    };
    
    if ('requestIdleCallback' in window) {
        requestIdleCallback(loadScripts, { timeout: 2000 });
    } else {
        window.addEventListener('load', function(){
            setTimeout(loadScripts, 1000);
        }, { once: true });
    }
})();
JS
            , View::POS_END, 'lazy-scripts');
        }
    }

    /**
     * Preload критичных ресурсов
     * Улучшает LCP за счет раннего старта загрузки
     * 
     * @param View $view
     * @param array $assets
     */
    protected static function preloadCriticalAssets($view, $assets = [])
    {
        // Шрифты
        if (!empty($assets['fonts'])) {
            foreach ($assets['fonts'] as $font) {
                $view->registerLinkTag([
                    'rel' => 'preload',
                    'as' => 'font',
                    'type' => 'font/woff2',
                    'href' => $font,
                    'crossorigin' => 'anonymous',
                ]);
            }
        }
        
        // Изображения
        if (!empty($assets['images'])) {
            foreach ($assets['images'] as $image) {
                $view->registerLinkTag([
                    'rel' => 'preload',
                    'as' => 'image',
                    'href' => $image,
                    'fetchpriority' => 'high',
                ]);
            }
        }
    }

    /**
     * Prefetch для следующих страниц
     * Загружает ресурсы в фоне для быстрой навигации
     * 
     * @param View $view
     */
    protected static function prefetchNextPages($view)
    {
        // Prefetch DNS для внешних ресурсов
        $view->registerLinkTag(['rel' => 'dns-prefetch', 'href' => '//cdn.jsdelivr.net']);
        
        // Preconnect для критичных внешних доменов
        $view->registerLinkTag([
            'rel' => 'preconnect',
            'href' => 'https://cdn.jsdelivr.net',
            'crossorigin' => 'anonymous',
        ]);
    }

    /**
     * Простая минификация CSS (удаление комментариев, лишних пробелов)
     * 
     * @param string $css
     * @return string
     */
    protected static function minifyCSS($css)
    {
        // Удаление комментариев
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        
        // Удаление лишних пробелов
        $css = str_replace(["\r\n", "\r", "\n", "\t", '  ', '    ', '    '], '', $css);
        $css = preg_replace('/\s+/', ' ', $css);
        $css = preg_replace('/\s*([{}:;,>+])\s*/', '$1', $css);
        
        return trim($css);
    }

    /**
     * Получение версии файла для cache busting
     * 
     * @param string $key
     * @return string
     */
    protected static function getFileVersion($key)
    {
        static $versions = [];
        
        if (!isset($versions[$key])) {
            if (isset(self::DEFERRED_CSS[$key])) {
                $file = Yii::getAlias(self::DEFERRED_CSS[$key]);
                if (strpos($file, '@web') === 0) {
                    $file = str_replace('@web', Yii::getAlias('@webroot'), $file);
                }
                
                $versions[$key] = file_exists($file) ? filemtime($file) : time();
            } else {
                $versions[$key] = time();
            }
        }
        
        return $versions[$key];
    }

    /**
     * Измерение производительности (для дебага)
     * 
     * @param View $view
     */
    public static function measurePerformance($view)
    {
        if (YII_ENV_DEV) {
            $view->registerJs(<<<'JS'
if (window.performance && window.performance.timing) {
    window.addEventListener('load', function() {
        setTimeout(function() {
            var timing = window.performance.timing;
            var metrics = {
                'DNS': timing.domainLookupEnd - timing.domainLookupStart,
                'TCP': timing.connectEnd - timing.connectStart,
                'Request': timing.responseStart - timing.requestStart,
                'Response': timing.responseEnd - timing.responseStart,
                'DOM Processing': timing.domComplete - timing.domLoading,
                'Total Load Time': timing.loadEventEnd - timing.navigationStart,
                'DOM Ready': timing.domContentLoadedEventEnd - timing.navigationStart
            };
            
            console.group('⚡ Performance Metrics');
            Object.keys(metrics).forEach(function(key) {
                console.log(key + ': ' + metrics[key] + 'ms');
            });
            console.groupEnd();
        }, 0);
    });
}
JS
            , View::POS_END);
        }
    }
}
