/**
 * ГЛОБАЛЬНЫЕ ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ
 * Централизованное место для общих функций, используемых по всему сайту
 * Загружается везде для избежания дублирования кода
 */

(function() {
    'use strict';

    /**
     * Wrapper для toggleFavorite (короткое имя для удобства)
     * ЕДИНСТВЕННОЕ место определения этой функции!
     * Используется во всех шаблонах для кнопок избранного
     * 
     * @param {Event} e - событие клика
     * @param {number} id - ID товара
     */
    window.toggleFav = function(e, id) {
        if (!e || !e.preventDefault) {
            console.error('toggleFav: первый параметр должен быть Event объектом');
            return;
        }
        
        e.preventDefault();
        e.stopPropagation();
        
        // Вызываем основную функцию из favorites.js
        if (typeof window.toggleFavorite === 'function') {
            window.toggleFavorite(e, id);
        } else {
            console.error('toggleFavorite function not found. Make sure favorites.js is loaded.');
            
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
    window.resetFilters = function() {
        // Редирект на чистый каталог без параметров
        window.location.href = '/catalog/';
    };

    /**
     * Быстрое добавление в корзину
     * Placeholder для будущей функциональности
     * 
     * @param {Event} e - событие клика
     * @param {number} productId - ID товара
     */
    window.quickAddToCart = function(e, productId) {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        console.log('quickAddToCart called for product:', productId);
        
        // TODO: Реализовать добавление в корзину
        if (window.NotificationManager) {
            NotificationManager.info('Функция добавления в корзину в разработке');
        }
        
        // Временно: редирект на страницу товара
        window.location.href = '/catalog/product/' + productId;
    };

    /**
     * Выбор размера из quick size селектора
     * 
     * @param {Event} e - событие клика
     * @param {number} productId - ID товара
     * @param {string} size - выбранный размер
     */
    window.selectQuickSize = function(e, productId, size) {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        console.log('selectQuickSize:', productId, size);
        
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
    window.toggleBrandFilter = function(brandId, brandSlug) {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }
        
        const button = event.currentTarget;
        const isActive = button.classList.contains('active');
        
        // Переключаем визуальное состояние кнопки
        button.classList.toggle('active');
        
        // Синхронизируем с чекбоксом в сайдбаре
        const checkbox = document.querySelector(`input[name="brands[]"][value="${brandId}"]`);
        if (checkbox) {
            checkbox.checked = !isActive;
            // Триггерим событие change для применения фильтров
            checkbox.dispatchEvent(new Event('change', { bubbles: true }));
        } else {
            console.warn('⚠️ Чекбокс бренда не найден:', brandId);
        }
    };

    // Debug info
    console.log('✅ Global Helpers загружены');

})();
