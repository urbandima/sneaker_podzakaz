/**
 * История просмотров товаров
 * Сохраняет последние 20 просмотренных товаров в localStorage
 */

(function() {
    'use strict';

    const MAX_HISTORY = 20;
    const STORAGE_KEY = 'viewHistory';

    /**
     * Добавить товар в историю просмотров
     */
    function trackView(productId) {
        if (!productId) return;

        let history = getHistory();
        
        // Убираем дубликат если есть
        history = history.filter(id => id !== productId);
        
        // Добавляем в начало
        history.unshift(productId);
        
        // Ограничиваем размер
        history = history.slice(0, MAX_HISTORY);
        
        // Сохраняем
        localStorage.setItem(STORAGE_KEY, JSON.stringify(history));
    }

    /**
     * Получить историю просмотров
     */
    function getHistory() {
        try {
            const data = localStorage.getItem(STORAGE_KEY);
            return data ? JSON.parse(data) : [];
        } catch (e) {
            return [];
        }
    }

    /**
     * Очистить историю
     */
    function clearHistory() {
        localStorage.removeItem(STORAGE_KEY);
    }

    /**
     * Показать историю на странице
     */
    function showHistory(containerId = 'viewHistoryContainer') {
        const history = getHistory();
        const container = document.getElementById(containerId);
        
        if (!container || history.length === 0) {
            return;
        }

        // Показываем индикатор загрузки
        container.innerHTML = '<div class="loading-history" style="grid-column:1/-1;text-align:center;padding:3rem;color:#6b7280;"><i class="bi bi-hourglass-split" style="font-size:2rem;display:block;margin-bottom:1rem;"></i> Загрузка...</div>';

        // Загружаем товары
        fetch(`/catalog/products-by-ids?ids=${history.join(',')}`)
            .then(response => response.json())
            .then(products => {
                if (products.length === 0) {
                    container.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:3rem;color:#6b7280;">Товары не найдены</div>';
                    return;
                }

                // Определяем тип рендеринга по containerId
                if (containerId === 'products') {
                    renderHistoryAsCards(products, container);
                } else {
                    renderHistory(products, container);
                }
            })
            .catch(error => {
                console.error('Error loading view history:', error);
                container.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:3rem;color:#ef4444;"><i class="bi bi-exclamation-triangle"></i> Ошибка загрузки</div>';
            });
    }

    /**
     * Отрисовать историю как карточки товаров (для страницы /catalog/history)
     */
    function renderHistoryAsCards(products, container) {
        const lazyPlaceholder = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="10" height="10"%3E%3Crect width="10" height="10" fill="%23f3f4f6"/%3E%3C/svg%3E';
        
        const html = products.map(product => `
            <div class="product product-card modern-card">
                <div class="product-image-wrapper">
                    <a href="${product.url}" class="product-link">
                        <img
                            class="product-image primary lazy-loaded"
                            src="${product.image}"
                            alt="${escapeHtml(product.name)}"
                            loading="lazy"
                        >
                    </a>
                </div>
                <a href="${product.url}" class="product-link">
                    <div class="info product-card-body">
                        <div class="product-card-header">
                            <span class="product-card-brand">${escapeHtml(product.brand)}</span>
                        </div>
                        <h3 class="product-card-title">${escapeHtml(product.name)}</h3>
                        <div class="product-card-price">
                            <span class="current-price">${product.price}</span>
                        </div>
                    </div>
                </a>
            </div>
        `).join('');
        
        container.innerHTML = html;
    }

    /**
     * Отрисовать историю просмотров
     */
    function renderHistory(products, container) {
        const html = `
            <div class="view-history-section">
                <div class="section-header">
                    <h2><i class="bi bi-clock-history"></i> Вы недавно смотрели</h2>
                    <button onclick="viewHistory.clear()" class="btn-clear-history">
                        <i class="bi bi-trash"></i> Очистить
                    </button>
                </div>
                <div class="view-history-grid">
                    ${products.map(product => `
                        <div class="history-product">
                            <a href="${product.url}">
                                <div class="history-img">
                                    <img src="${product.image}" alt="${escapeHtml(product.name)}" loading="lazy">
                                </div>
                                <div class="history-info">
                                    <div class="brand">${escapeHtml(product.brand)}</div>
                                    <h4>${escapeHtml(product.name)}</h4>
                                    <div class="price">${product.price}</div>
                                </div>
                            </a>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
        
        container.innerHTML = html;
    }

    /**
     * Escape HTML
     */
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, m => map[m]);
    }

    function initHistory() {
        // Проверяем если мы на странице товара
        const productMeta = document.querySelector('meta[name="product-id"]');
        if (productMeta) {
            const productId = productMeta.content;
            trackView(productId);
        }

        // Показываем историю если есть контейнер
        const container = document.getElementById('viewHistoryContainer');
        if (container) {
            showHistory();
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initHistory);
    } else {
        initHistory();
    }

    // Экспортируем API
    window.viewHistory = {
        track: trackView,
        get: getHistory,
        clear: function() {
            if (confirm('Очистить историю просмотров?')) {
                clearHistory();
                const container = document.getElementById('viewHistoryContainer');
                if (container) {
                    container.innerHTML = '';
                }
            }
        },
        show: showHistory
    };

})();
