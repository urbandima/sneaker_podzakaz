/**
 * LAZY LOAD - Ленивая загрузка изображений и контента
 * Использует IntersectionObserver для оптимизации производительности
 * 
 * Использование:
 * 1. Изображения: <img data-src="image.jpg" class="lazy" src="placeholder.jpg">
 * 2. Background: <div data-bg="image.jpg" class="lazy-bg"></div>
 * 3. Iframe: <iframe data-src="embed.html" class="lazy-iframe"></iframe>
 * 
 * @version 2.0
 * @author СНИКЕРХЭД Team
 */

(function() {
    'use strict';

    // Конфигурация
    const CONFIG = {
        rootMargin: '50px 0px', // Начинать загрузку за 50px до viewport
        threshold: 0.01,
        loadingClass: 'lazy-loading',
        loadedClass: 'lazy-loaded',
        errorClass: 'lazy-error',
    };

    /**
     * Проверка поддержки IntersectionObserver
     */
    const supportsIntersectionObserver = 'IntersectionObserver' in window;

    /**
     * Lazy Load для изображений
     */
    class LazyImageLoader {
        constructor() {
            this.images = document.querySelectorAll('img[data-src], img[data-srcset]');
            this.observer = null;
            
            if (this.images.length > 0) {
                this.init();
            }
        }

        init() {
            if (supportsIntersectionObserver) {
                this.observer = new IntersectionObserver(
                    this.onIntersection.bind(this),
                    CONFIG
                );

                this.images.forEach(img => {
                    this.observer.observe(img);
                });
            } else {
                // Fallback для старых браузеров - загружаем все сразу
                this.loadAllImages();
            }
        }

        onIntersection(entries, observer) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    this.loadImage(entry.target);
                    observer.unobserve(entry.target);
                }
            });
        }

        loadImage(img) {
            img.classList.add(CONFIG.loadingClass);

            const src = img.dataset.src;
            const srcset = img.dataset.srcset;

            // Preload изображения
            const tempImage = new Image();
            
            tempImage.onload = () => {
                if (srcset) {
                    img.srcset = srcset;
                }
                if (src) {
                    img.src = src;
                }
                
                img.classList.remove(CONFIG.loadingClass);
                img.classList.add(CONFIG.loadedClass);
                
                // Удаляем data-атрибуты
                delete img.dataset.src;
                delete img.dataset.srcset;
                
                // Триггер события
                img.dispatchEvent(new CustomEvent('lazyloaded', { detail: { src } }));
            };

            tempImage.onerror = () => {
                img.classList.remove(CONFIG.loadingClass);
                img.classList.add(CONFIG.errorClass);
                
                img.dispatchEvent(new CustomEvent('lazyerror', { detail: { src } }));
            };

            // Начинаем загрузку
            if (srcset) {
                tempImage.srcset = srcset;
            }
            if (src) {
                tempImage.src = src;
            }
        }

        loadAllImages() {
            this.images.forEach(img => this.loadImage(img));
        }
    }

    /**
     * Lazy Load для background изображений
     */
    class LazyBackgroundLoader {
        constructor() {
            this.elements = document.querySelectorAll('[data-bg]');
            this.observer = null;
            
            if (this.elements.length > 0) {
                this.init();
            }
        }

        init() {
            if (supportsIntersectionObserver) {
                this.observer = new IntersectionObserver(
                    this.onIntersection.bind(this),
                    CONFIG
                );

                this.elements.forEach(el => {
                    this.observer.observe(el);
                });
            } else {
                this.loadAllBackgrounds();
            }
        }

        onIntersection(entries, observer) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    this.loadBackground(entry.target);
                    observer.unobserve(entry.target);
                }
            });
        }

        loadBackground(el) {
            const bg = el.dataset.bg;
            
            if (bg) {
                el.classList.add(CONFIG.loadingClass);
                
                // Preload изображения
                const img = new Image();
                img.onload = () => {
                    el.style.backgroundImage = `url('${bg}')`;
                    el.classList.remove(CONFIG.loadingClass);
                    el.classList.add(CONFIG.loadedClass);
                    delete el.dataset.bg;
                };
                
                img.onerror = () => {
                    el.classList.remove(CONFIG.loadingClass);
                    el.classList.add(CONFIG.errorClass);
                };
                
                img.src = bg;
            }
        }

        loadAllBackgrounds() {
            this.elements.forEach(el => this.loadBackground(el));
        }
    }

    /**
     * Lazy Load для iframe (YouTube, embeds и т.д.)
     */
    class LazyIframeLoader {
        constructor() {
            this.iframes = document.querySelectorAll('iframe[data-src]');
            this.observer = null;
            
            if (this.iframes.length > 0) {
                this.init();
            }
        }

        init() {
            if (supportsIntersectionObserver) {
                this.observer = new IntersectionObserver(
                    this.onIntersection.bind(this),
                    CONFIG
                );

                this.iframes.forEach(iframe => {
                    this.observer.observe(iframe);
                });
            } else {
                this.loadAllIframes();
            }
        }

        onIntersection(entries, observer) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    this.loadIframe(entry.target);
                    observer.unobserve(entry.target);
                }
            });
        }

        loadIframe(iframe) {
            const src = iframe.dataset.src;
            
            if (src) {
                iframe.classList.add(CONFIG.loadingClass);
                iframe.src = src;
                
                iframe.onload = () => {
                    iframe.classList.remove(CONFIG.loadingClass);
                    iframe.classList.add(CONFIG.loadedClass);
                    delete iframe.dataset.src;
                };
            }
        }

        loadAllIframes() {
            this.iframes.forEach(iframe => this.loadIframe(iframe));
        }
    }

    /**
     * Утилита для динамического добавления lazy элементов
     */
    window.LazyLoadUtils = {
        /**
         * Добавить новые элементы для lazy loading
         * @param {HTMLElement} container Контейнер с новыми элементами
         */
        observe: function(container) {
            // Изображения
            const images = container.querySelectorAll('img[data-src], img[data-srcset]');
            if (images.length > 0) {
                const imageLoader = new LazyImageLoader();
                images.forEach(img => {
                    if (imageLoader.observer) {
                        imageLoader.observer.observe(img);
                    } else {
                        imageLoader.loadImage(img);
                    }
                });
            }

            // Background
            const backgrounds = container.querySelectorAll('[data-bg]');
            if (backgrounds.length > 0) {
                const bgLoader = new LazyBackgroundLoader();
                backgrounds.forEach(el => {
                    if (bgLoader.observer) {
                        bgLoader.observer.observe(el);
                    } else {
                        bgLoader.loadBackground(el);
                    }
                });
            }

            // Iframes
            const iframes = container.querySelectorAll('iframe[data-src]');
            if (iframes.length > 0) {
                const iframeLoader = new LazyIframeLoader();
                iframes.forEach(iframe => {
                    if (iframeLoader.observer) {
                        iframeLoader.observer.observe(iframe);
                    } else {
                        iframeLoader.loadIframe(iframe);
                    }
                });
            }
        },

        /**
         * Проверить загружено ли изображение
         * @param {HTMLImageElement} img
         * @returns {boolean}
         */
        isLoaded: function(img) {
            return img.classList.contains(CONFIG.loadedClass);
        },

        /**
         * Форсировать загрузку элемента
         * @param {HTMLElement} element
         */
        forceLoad: function(element) {
            if (element.tagName === 'IMG') {
                const loader = new LazyImageLoader();
                loader.loadImage(element);
            } else if (element.dataset.bg) {
                const loader = new LazyBackgroundLoader();
                loader.loadBackground(element);
            } else if (element.tagName === 'IFRAME') {
                const loader = new LazyIframeLoader();
                loader.loadIframe(element);
            }
        }
    };

    /**
     * Инициализация при загрузке DOM
     */
    function init() {
        // Запускаем все загрузчики
        new LazyImageLoader();
        new LazyBackgroundLoader();
        new LazyIframeLoader();

        // Логирование в dev режиме
        if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
            console.log('[LazyLoad] Initialized', {
                images: document.querySelectorAll('img[data-src]').length,
                backgrounds: document.querySelectorAll('[data-bg]').length,
                iframes: document.querySelectorAll('iframe[data-src]').length,
                supportsIO: supportsIntersectionObserver
            });
        }
    }

    // Запуск при готовности DOM
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
