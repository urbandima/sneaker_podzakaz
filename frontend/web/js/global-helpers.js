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
        if (e && e.preventDefault) {
            e.preventDefault();
            e.stopPropagation();
        }

        // Получаем кнопку из event — ищем closest button
        const button = (e && (e.currentTarget || e.target))
            ? (e.currentTarget || e.target).closest('button') || (e.currentTarget || e.target)
            : null;

        // Вызываем основную функцию из favorites.js
        if (typeof window.toggleFavorite === 'function') {
            window.toggleFavorite(button, id);
        } else {
            // Прямой AJAX fallback
            if (!id) return;
            const isActive = button && button.classList.contains('active');
            const url = isActive ? '/catalog/remove-favorite' : '/catalog/add-favorite';
            const csrf = document.querySelector('meta[name="csrf-token"]');
            const csrfToken = csrf ? csrf.getAttribute('content') : '';

            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': csrfToken
                },
                body: 'product_id=' + id + '&_csrf=' + encodeURIComponent(csrfToken)
            })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.success && button) {
                        button.classList.toggle('active');
                        const icon = button.querySelector('i');
                        if (icon) {
                            icon.className = isActive ? 'bi bi-heart' : 'bi bi-heart-fill';
                        }
                        const badge = document.getElementById('favCount');
                        if (badge && data.count !== undefined) {
                            badge.textContent = data.count;
                            badge.style.display = data.count > 0 ? 'flex' : 'none';
                        }
                    }
                })
                .catch(function () { });
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
