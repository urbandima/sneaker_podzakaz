/**
 * ГЛОБАЛЬНЫЕ ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ
 * Централизованное место для общих функций, используемых по всему сайту
 * Загружается везде для избежания дублирования кода
 */

(function () {
    'use strict';

    /**
     * Wrapper для toggleFavorite (короткое имя для удобства)
     * ЕДИНСТВЕННОЕ место определения этой функции!
     * Используется во всех шаблонах для кнопок избранного
     * 
     * @param {Event} e - событие клика
     * @param {number} id - ID товара
     */
    window.toggleFav = function (e, id) {
        if (!e || !e.preventDefault) {
            return;
        }

        e.preventDefault();
        e.stopPropagation();

        // Получаем кнопку из event
        const button = e.currentTarget || e.target;

        // Вызываем основную функцию из favorites.js
        if (typeof window.toggleFavorite === 'function') {
            window.toggleFavorite(button, id);
        } else {
            // Fallback: показываем уведомление пользователю
            if (window.NotificationManager) {
                NotificationManager.error('Ошибка загрузки функционала избранного. Обновите страницу.');
            }
        }
    };

    /**
     * Сброс всех фильтров в каталоге
     * Используется на странице каталога и в empty state
     */
    window.resetFilters = function () {
        // Редирект на чистый каталог без параметров
        window.location.href = '/catalog/';
    };

    /**
     * Быстрое добавление в корзину
     * 
     * @param {Event} e - событие клика
     * @param {number} productId - ID товара
     */
    window.quickAddToCart = function (e, productId) {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }

        // Вызываем реальную функцию добавления в корзину
        if (typeof addToCart === 'function') {
            addToCart(productId, 1, null, null);
        } else {
            // Fallback: редирект на страницу товара
            window.location.href = '/catalog/product/' + productId;
        }
    };

    /**
     * Выбор размера из quick size селектора
     * 
     * @param {Event} e - событие клика
     * @param {number} productId - ID товара
     * @param {string} size - выбранный размер
     */
    window.selectQuickSize = function (e, productId, size) {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }


        // Перенаправляем на страницу товара с предвыбранным размером
        window.location.href = '/catalog/product/' + productId + '?size=' + encodeURIComponent(size);
    };

    /**
     * Переключение фильтра по брендам (quick filter)
     * Синхронизирует быструю кнопку с чекбоксом в сайдбаре
     * 
     * @param {number} brandId - ID бренда
     * @param {string} brandSlug - slug бренда (не используется)
     */
    window.toggleBrandFilter = function (brandId, brandSlug) {
        var e = window.event; // Совместимость с inline onclick
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }

        var button = e ? (e.currentTarget || e.target) : null;
        if (!button) return;
        var isActive = button.classList.contains('active');

        // Переключаем визуальное состояние кнопки
        button.classList.toggle('active');

        // Синхронизируем с чекбоксом в сайдбаре
        const checkbox = document.querySelector(`input[name="brands[]"][value="${brandId}"]`);
        if (checkbox) {
            checkbox.checked = !isActive;
            // Триггерим событие change для применения фильтров
            checkbox.dispatchEvent(new Event('change', { bubbles: true }));
        } else {
        }
    };

    // Debug info

})();
