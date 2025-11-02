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
        container.innerHTML = '<div class="loading-history"><i class="bi bi-hourglass-split"></i> Загрузка...</div>';

        // Загружаем товары
        fetch(`/catalog/products-by-ids?ids=${history.join(',')}`)
            .then(response => response.json())
            .then(products => {
                if (products.length === 0) {
                    container.innerHTML = '';
                    return;
                }

                renderHistory(products, container);
            })
            .catch(error => {
                console.error('Error loading view history:', error);
                container.innerHTML = '';
            });
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

    // Автоматическое отслеживание просмотра товара
    // Запускается на странице товара
    document.addEventListener('DOMContentLoaded', () => {
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
    });

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
