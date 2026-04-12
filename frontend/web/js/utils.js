/**
 * UTILS.JS — Централизованные утилиты
 * Единственный источник истины для общих функций.
 * Загружается глобально через core-bundle, ДО всех остальных скриптов.
 *
 * Содержит:
 *  - CSRF-токен
 *  - Fetch-обёртка с CSRF и обработкой ошибок
 *  - Модальные окна (open/close/scroll-lock)
 *  - Уведомления (обёртка над NotificationManager)
 *  - Форматирование валюты
 *  - Debounce / Throttle
 *  - Focus trap для accessibility
 */

(function () {
    'use strict';

    /* ======================================================
       1. CSRF
       ====================================================== */

    /**
     * Получить CSRF-токен из мета-тега
     * @returns {string}
     */
    function getCsrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    /* ======================================================
       2. FETCH
       ====================================================== */

    /**
     * Обёртка над fetch с автоматическим CSRF, JSON-парсингом и обработкой ошибок.
     *
     * @param {string} url
     * @param {Object} [options]
     * @param {string}  [options.method='GET']
     * @param {Object|FormData|string} [options.body]
     * @param {Object}  [options.headers]
     * @param {boolean} [options.json=true]  — автоматически парсить ответ как JSON
     * @returns {Promise<any>}
     */
    function apiFetch(url, options) {
        options = options || {};
        var method = (options.method || 'GET').toUpperCase();
        var headers = Object.assign({
            'X-Requested-With': 'XMLHttpRequest'
        }, options.headers || {});

        // Добавляем CSRF для мутирующих запросов
        if (method !== 'GET' && method !== 'HEAD') {
            headers['X-CSRF-Token'] = getCsrfToken();
        }

        var fetchOpts = {
            method: method,
            headers: headers
        };

        if (options.body !== undefined) {
            // Если передан plain-object — сериализуем в JSON
            if (options.body && typeof options.body === 'object' && !(options.body instanceof FormData)) {
                fetchOpts.body = JSON.stringify(options.body);
                if (!headers['Content-Type']) {
                    headers['Content-Type'] = 'application/json';
                }
            } else {
                // Для FormData дополнительно добавляем _csrf в тело (Yii2 CSRF body-param fallback)
                if (options.body instanceof FormData && method !== 'GET' && method !== 'HEAD') {
                    var csrfToken = getCsrfToken();
                    if (csrfToken) {
                        options.body.append('_csrf', csrfToken);
                    }
                }
                fetchOpts.body = options.body;
            }
        }

        var shouldParseJson = options.json !== false;

        return fetch(url, fetchOpts).then(function (response) {
            if (!response.ok) {
                return response.text().then(function (text) {
                    var err = new Error('HTTP ' + response.status + ': ' + response.statusText);
                    err.status = response.status;
                    err.body = text;
                    throw err;
                });
            }
            return shouldParseJson ? response.json() : response;
        });
    }

    /* ======================================================
       3. УВЕДОМЛЕНИЯ
       ====================================================== */

    /**
     * Показать уведомление через глобальный NotificationManager.
     * Если NotificationManager не загружен — молча игнорирует.
     *
     * @param {string} message
     * @param {string} [type='info']  — 'success' | 'error' | 'warning' | 'info'
     * @param {number} [duration=4000]
     */
    function notify(message, type, duration) {
        type = type || 'info';
        duration = duration || 4000;
        if (window.NotificationManager && typeof window.NotificationManager.show === 'function') {
            window.NotificationManager.show(message, type, duration);
        }
    }

    /* ======================================================
       4. МОДАЛЬНЫЕ ОКНА / SCROLL LOCK
       ====================================================== */

    var _scrollLockCount = 0;

    /**
     * Заблокировать скролл body (поддерживает вложенные вызовы)
     */
    function lockScroll() {
        _scrollLockCount++;
        if (_scrollLockCount === 1) {
            document.body.style.overflow = 'hidden';
        }
    }

    /**
     * Разблокировать скролл body
     */
    function unlockScroll() {
        _scrollLockCount = Math.max(0, _scrollLockCount - 1);
        if (_scrollLockCount === 0) {
            document.body.style.overflow = '';
        }
    }

    /**
     * Открыть модальное окно / drawer / overlay
     * @param {HTMLElement} element
     * @param {string} [activeClass='active']  — класс для открытого состояния
     */
    function openModal(element, activeClass) {
        if (!element) return;
        activeClass = activeClass || 'active';
        element.classList.add(activeClass);
        lockScroll();
    }

    /**
     * Закрыть модальное окно / drawer / overlay
     * @param {HTMLElement} element
     * @param {string} [activeClass='active']
     */
    function closeModal(element, activeClass) {
        if (!element) return;
        activeClass = activeClass || 'active';
        element.classList.remove(activeClass);
        unlockScroll();
    }

    /* ======================================================
       5. ФОРМАТИРОВАНИЕ
       ====================================================== */

    /**
     * Форматировать сумму как белорусскую валюту: «123,45 Br»
     * @param {number|string} amount
     * @returns {string}
     */
    function formatCurrency(amount) {
        var n = parseFloat(amount);
        if (isNaN(n)) return '0,00 Br';
        return n.toFixed(2).replace('.', ',') + ' Br';
    }

    /**
     * Intl-форматтер для BYN (если поддерживается)
     * Используется на страницах товаров для полного формата
     */
    var currencyFormatter = (typeof Intl !== 'undefined' && Intl.NumberFormat)
        ? new Intl.NumberFormat('ru-BY', { style: 'currency', currency: 'BYN', minimumFractionDigits: 2 })
        : null;

    /**
     * Форматировать сумму через Intl (если доступен), иначе — через простой формат
     * @param {number|string} amount
     * @returns {string}
     */
    function formatPrice(amount) {
        var n = parseFloat(amount);
        if (isNaN(n)) return '0,00 BYN';
        if (currencyFormatter) {
            return currencyFormatter.format(n);
        }
        return n.toFixed(2).replace('.', ',') + ' BYN';
    }

    /* ======================================================
       6. ESCAPE HTML
       ====================================================== */

    /**
     * Экранировать HTML-спецсимволы (защита от XSS при вставке в innerHTML)
     * @param {string} str
     * @returns {string}
     */
    function escapeHtml(str) {
        var div = document.createElement('div');
        div.textContent = String(str);
        return div.innerHTML;
    }

    /* ======================================================
       7. DEBOUNCE / THROTTLE
       ====================================================== */

    /**
     * Debounce — вызов функции только после паузы
     * @param {Function} fn
     * @param {number} delay — мс
     * @returns {Function}
     */
    function debounce(fn, delay) {
        var timer;
        return function () {
            var ctx = this, args = arguments;
            clearTimeout(timer);
            timer = setTimeout(function () {
                fn.apply(ctx, args);
            }, delay);
        };
    }

    /**
     * Throttle — вызов функции не чаще одного раза за interval
     * @param {Function} fn
     * @param {number} interval — мс
     * @returns {Function}
     */
    function throttle(fn, interval) {
        var last = 0;
        return function () {
            var now = Date.now();
            if (now - last >= interval) {
                last = now;
                fn.apply(this, arguments);
            }
        };
    }

    /* ======================================================
       8. FOCUS TRAP (Accessibility)
       ====================================================== */

    var _trapHandlers = new WeakMap();

    /**
     * Установить ловушку фокуса внутри элемента (для модалок, меню)
     * @param {HTMLElement} element
     */
    function trapFocus(element) {
        if (!element) return;

        var handler = function (e) {
            if (e.key !== 'Tab') return;

            var focusable = element.querySelectorAll(
                'a[href], button:not([disabled]), input:not([disabled]), textarea:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])'
            );
            if (focusable.length === 0) return;

            var first = focusable[0];
            var last = focusable[focusable.length - 1];

            if (e.shiftKey) {
                if (document.activeElement === first) {
                    e.preventDefault();
                    last.focus();
                }
            } else {
                if (document.activeElement === last) {
                    e.preventDefault();
                    first.focus();
                }
            }
        };

        _trapHandlers.set(element, handler);
        element.addEventListener('keydown', handler);

        // Авто-фокус на первый элемент
        var firstFocusable = element.querySelector(
            'a[href], button:not([disabled]), input:not([disabled]), textarea:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])'
        );
        if (firstFocusable) firstFocusable.focus();
    }

    /**
     * Снять ловушку фокуса
     * @param {HTMLElement} element
     */
    function releaseFocus(element) {
        if (!element) return;
        var handler = _trapHandlers.get(element);
        if (handler) {
            element.removeEventListener('keydown', handler);
            _trapHandlers.delete(element);
        }
    }

    /* ======================================================
       EXPORT
       ====================================================== */

    window.SH = window.SH || {};
    window.SH.getCsrfToken = getCsrfToken;
    window.SH.fetch = apiFetch;
    window.SH.notify = notify;
    window.SH.lockScroll = lockScroll;
    window.SH.unlockScroll = unlockScroll;
    window.SH.openModal = openModal;
    window.SH.closeModal = closeModal;
    window.SH.formatCurrency = formatCurrency;
    window.SH.formatPrice = formatPrice;
    window.SH.escapeHtml = escapeHtml;
    window.SH.debounce = debounce;
    window.SH.throttle = throttle;
    // Global aliases for backward compatibility
    window.debounce = debounce;
    window.throttle = throttle;
    window.SH.trapFocus = trapFocus;
    window.SH.releaseFocus = releaseFocus;

    // Обратная совместимость: глобальные функции, на которые могут ссылаться шаблоны
    window.getCsrfToken = getCsrfToken;
    window.formatCurrency = formatCurrency;

})();
