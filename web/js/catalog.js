/**
 * Catalog JavaScript - AJAX фильтрация и избранное
 * СНИКЕРХЭД
 */

(function() {
    'use strict';

    // Конфигурация
    const CONFIG = {
        ajaxFilterUrl: '/catalog/filter',
        addFavoriteUrl: '/catalog/add-favorite',
        removeFavoriteUrl: '/catalog/remove-favorite',
        filterDebounceDelay: 200, // УЛУЧШЕНО: 300ms → 200ms для быстрого отклика
    };

    // Состояние фильтров
    let filterState = {
        brands: [],
        categories: [],
        priceFrom: null,
        priceTo: null,
        sizes: [],
        colors: [],
        stockStatus: null,
        sortBy: 'popular',
        page: 1,
        perPage: 24,
    };

    // Инициализация при загрузке страницы
    document.addEventListener('DOMContentLoaded', function() {
        initializeFilters();
        initializeFavorites();
        initializeViewToggle();
        updateFavoritesCount();
        loadFilterStateFromURL();
    });

    /**
     * Инициализация фильтров
     */
    function initializeFilters() {
        // Чекбоксы брендов
        document.querySelectorAll('input[name="brands[]"]').forEach(checkbox => {
            checkbox.addEventListener('change', handleFilterChange);
        });

        // Чекбоксы категорий
        document.querySelectorAll('input[name="categories[]"]').forEach(checkbox => {
            checkbox.addEventListener('change', handleFilterChange);
        });

        // Поля цены
        const priceFromInput = document.querySelector('input[name="price_from"]');
        const priceToInput = document.querySelector('input[name="price_to"]');
        
        if (priceFromInput) {
            priceFromInput.addEventListener('input', debounce(handleFilterChange, CONFIG.filterDebounceDelay));
        }
        if (priceToInput) {
            priceToInput.addEventListener('input', debounce(handleFilterChange, CONFIG.filterDebounceDelay));
        }
    }

    /**
     * Обработчик изменения фильтров
     */
    function handleFilterChange() {
        collectFilterState();
        applyFiltersAjax();
    }

    /**
     * Собрать состояние всех фильтров
     */
    function collectFilterState() {
        // Бренды
        filterState.brands = Array.from(document.querySelectorAll('input[name="brands[]"]:checked'))
            .map(cb => parseInt(cb.value));

        // Категории
        filterState.categories = Array.from(document.querySelectorAll('input[name="categories[]"]:checked'))
            .map(cb => parseInt(cb.value));

        // Цена
        const priceFrom = document.querySelector('input[name="price_from"]');
        const priceTo = document.querySelector('input[name="price_to"]');
        filterState.priceFrom = priceFrom && priceFrom.value ? parseFloat(priceFrom.value) : null;
        filterState.priceTo = priceTo && priceTo.value ? parseFloat(priceTo.value) : null;

        // Сброс страницы при изменении фильтров
        filterState.page = 1;
    }

    /**
     * Применить фильтры через AJAX
     */
    function applyFiltersAjax() {
        // НОВОЕ: Показываем skeleton loading
        showSkeletonGrid();
        
        // НОВОЕ: Индикатор "Применяется..."
        showFilteringIndicator();
        
        showLoadingIndicator();

        const formData = new FormData();
        formData.append('brands', JSON.stringify(filterState.brands));
        formData.append('categories', JSON.stringify(filterState.categories));
        formData.append('price_from', filterState.priceFrom || '');
        formData.append('price_to', filterState.priceTo || '');
        formData.append('sort', filterState.sortBy);
        formData.append('page', filterState.page);
        formData.append('perPage', filterState.perPage);

        fetch(CONFIG.ajaxFilterUrl, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': getCsrfToken(),
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderProducts(data.products);
                updateFilterCounts(data.filters);
                updatePagination(data.pagination);
                updateURL();
                saveFilterHistory(data);
            } else {
                showError('Ошибка загрузки товаров');
            }
            hideLoadingIndicator();
        })
        .catch(error => {
            console.error('Ошибка AJAX:', error);
            showError('Ошибка соединения с сервером');
            hideLoadingIndicator();
        });
    }

    /**
     * Отрисовка товаров
     */
    function renderProducts(products) {
        const container = document.getElementById('products-container');
        if (!container) return;

        if (products.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <i class="bi bi-inbox"></i>
                    <p>Товары не найдены</p>
                    <button class="btn" onclick="resetFilters()">Сбросить фильтры</button>
                </div>
            `;
            return;
        }

        container.innerHTML = products.map(product => renderProductCard(product)).join('');
    }

    /**
     * Отрисовка карточки товара
     */
    function renderProductCard(product) {
        const discount = product.discount || 0;
        const hasDiscount = discount > 0;
        
        return `
            <div class="product-card">
                <a href="${product.url}" class="product-link">
                    <div class="product-image">
                        ${hasDiscount ? `<span class="badge badge-sale">-${discount}%</span>` : ''}
                        ${product.isFeatured ? `<span class="badge badge-hit">HIT</span>` : ''}
                        <img src="${product.mainImage}" alt="${escapeHtml(product.name)}" loading="lazy">
                        <button class="btn-favorite" onclick="toggleFavorite(event, ${product.id})" data-product-id="${product.id}">
                            <i class="bi bi-heart"></i>
                        </button>
                    </div>
                    <div class="product-info">
                        <div class="product-brand">${escapeHtml(product.brand.name)}</div>
                        <h3 class="product-name">${escapeHtml(product.name)}</h3>
                        <div class="product-price">
                            ${hasDiscount ? `<span class="old-price">${formatPrice(product.oldPrice)}</span>` : ''}
                            <span class="current-price">${formatPrice(product.price)}</span>
                        </div>
                        <div class="product-status ${product.stockStatus === 'in_stock' ? 'in-stock' : 'out-of-stock'}">
                            ${product.stockStatus === 'in_stock' ? 'В наличии' : 'Нет в наличии'}
                        </div>
                    </div>
                </a>
            </div>
        `;
    }

    /**
     * Обновление счетчиков фильтров
     */
    function updateFilterCounts(filters) {
        // Обновление счетчиков брендов
        if (filters.brands) {
            filters.brands.forEach(brand => {
                const checkbox = document.querySelector(`input[name="brands[]"][value="${brand.id}"]`);
                if (checkbox) {
                    const countSpan = checkbox.closest('label').querySelector('.count');
                    if (countSpan) {
                        countSpan.textContent = `(${brand.count})`;
                    }
                }
            });
        }

        // Обновление счетчиков категорий
        if (filters.categories) {
            filters.categories.forEach(category => {
                const checkbox = document.querySelector(`input[name="categories[]"][value="${category.id}"]`);
                if (checkbox) {
                    const countSpan = checkbox.closest('label').querySelector('.count');
                    if (countSpan) {
                        countSpan.textContent = `(${category.count})`;
                    }
                }
            });
        }
    }

    /**
     * Обновление пагинации
     */
    function updatePagination(pagination) {
        const resultsInfo = document.querySelector('.results-info strong');
        if (resultsInfo) {
            resultsInfo.textContent = pagination.total;
        }
    }

    /**
     * Генерация SEF URL на клиенте (аналог SmartFilter::generateSefUrl)
     */
    function generateSefUrl() {
        if (filterState.brands.length === 0 && 
            filterState.categories.length === 0 && 
            !filterState.priceFrom && 
            !filterState.priceTo) {
            return '/catalog/';
        }

        const parts = [];

        // Бренды - получаем slug из DOM
        if (filterState.brands.length > 0) {
            const slugs = [];
            filterState.brands.forEach(brandId => {
                const checkbox = document.querySelector(`input[name="brands[]"][value="${brandId}"]`);
                if (checkbox && checkbox.dataset.slug) {
                    slugs.push(checkbox.dataset.slug);
                }
            });
            if (slugs.length > 0) {
                parts.push('brand-' + slugs.sort().join('-'));
            }
        }

        // Категории
        if (filterState.categories.length > 0) {
            const slugs = [];
            filterState.categories.forEach(catId => {
                const checkbox = document.querySelector(`input[name="categories[]"][value="${catId}"]`);
                if (checkbox && checkbox.dataset.slug) {
                    slugs.push(checkbox.dataset.slug);
                }
            });
            if (slugs.length > 0) {
                parts.push('category-' + slugs.sort().join('-'));
            }
        }

        // Цена
        if (filterState.priceFrom || filterState.priceTo) {
            const from = filterState.priceFrom || 'min';
            const to = filterState.priceTo || 'max';
            parts.push(`price-${from}-${to}`);
        }

        return parts.length > 0 ? '/catalog/filter/' + parts.join('/') + '/' : '/catalog/';
    }

    /**
     * Обновление URL без перезагрузки страницы (с SEF)
     */
    function updateURL() {
        const sefUrl = generateSefUrl();
        const params = new URLSearchParams();
        
        if (filterState.page > 1) {
            params.set('page', filterState.page);
        }
        if (filterState.sortBy !== 'popular') {
            params.set('sort', filterState.sortBy);
        }

        const newUrl = sefUrl + (params.toString() ? '?' + params.toString() : '');
        window.history.pushState({filters: filterState}, '', newUrl);
    }

    /**
     * Загрузка состояния фильтров из URL
     */
    function loadFilterStateFromURL() {
        const params = new URLSearchParams(window.location.search);
        
        if (params.has('brands')) {
            const brandIds = params.get('brands').split(',').map(id => parseInt(id));
            brandIds.forEach(id => {
                const checkbox = document.querySelector(`input[name="brands[]"][value="${id}"]`);
                if (checkbox) checkbox.checked = true;
            });
        }
        
        if (params.has('categories')) {
            const categoryIds = params.get('categories').split(',').map(id => parseInt(id));
            categoryIds.forEach(id => {
                const checkbox = document.querySelector(`input[name="categories[]"][value="${id}"]`);
                if (checkbox) checkbox.checked = true;
            });
        }
        
        if (params.has('price_from')) {
            const priceFrom = document.querySelector('input[name="price_from"]');
            if (priceFrom) priceFrom.value = params.get('price_from');
        }
        
        if (params.has('price_to')) {
            const priceTo = document.querySelector('input[name="price_to"]');
            if (priceTo) priceTo.value = params.get('price_to');
        }
        
        if (params.has('sort')) {
            filterState.sortBy = params.get('sort');
            const sortSelect = document.querySelector('.sort-select');
            if (sortSelect) sortSelect.value = filterState.sortBy;
        }
    }

    /**
     * Сохранение истории фильтрации
     */
    function saveFilterHistory(data) {
        const history = {
            filters: filterState,
            timestamp: Date.now(),
            resultsCount: data.pagination.total
        };
        localStorage.setItem('catalogFilterHistory', JSON.stringify(history));
    }

    /**
     * Инициализация избранного
     */
    function initializeFavorites() {
        // Обработчики кнопок избранного уже в HTML через onclick
        // Здесь можно добавить дополнительную логику
    }

    /**
     * Переключение избранного
     */
    window.toggleFavorite = function(event, productId) {
        event.preventDefault();
        event.stopPropagation();

        const button = event.currentTarget;
        const isActive = button.classList.contains('active');

        const url = isActive ? CONFIG.removeFavoriteUrl : CONFIG.addFavoriteUrl;
        const formData = new FormData();
        formData.append('productId', productId);

        fetch(url, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': getCsrfToken(),
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                button.classList.toggle('active');
                updateFavoritesCount();
                showNotification(data.message);
            } else {
                showError(data.message || 'Ошибка');
            }
        })
        .catch(error => {
            console.error('Ошибка избранного:', error);
            showError('Ошибка при обновлении избранного');
        });
    };

    /**
     * Обновление счетчика избранного
     */
    function updateFavoritesCount() {
        // TODO: AJAX запрос для получения актуального счетчика
        // Пока используем количество активных кнопок
        const activeCount = document.querySelectorAll('.btn-favorite.active').length;
        const badges = document.querySelectorAll('#favorites-count');
        badges.forEach(badge => {
            badge.textContent = activeCount;
            badge.style.display = activeCount > 0 ? 'flex' : 'none';
        });
    }

    /**
     * Инициализация переключения вида
     */
    function initializeViewToggle() {
        const viewButtons = document.querySelectorAll('.view-btn');
        viewButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                viewButtons.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                const view = this.dataset.view;
                const container = document.getElementById('products-container');
                if (container) {
                    container.className = view === 'list' ? 'products-list' : 'products-grid';
                }
            });
        });
    }

    /**
     * Применение сортировки
     */
    window.applySort = function(sortBy) {
        filterState.sortBy = sortBy;
        applyFiltersAjax();
    };

    /**
     * Сброс всех фильтров
     */
    window.resetFilters = function() {
        // Сброс чекбоксов
        document.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = false);
        
        // Сброс полей цены
        const priceFrom = document.querySelector('input[name="price_from"]');
        const priceTo = document.querySelector('input[name="price_to"]');
        if (priceFrom) priceFrom.value = '';
        if (priceTo) priceTo.value = '';
        
        // Перезагрузка страницы
        window.location.href = window.location.pathname;
    };

    /**
     * Применение фильтров (вызывается из HTML)
     */
    window.applyFilters = function() {
        collectFilterState();
        applyFiltersAjax();
    };

    // Утилиты

    function debounce(func, delay) {
        let timeoutId;
        return function(...args) {
            clearTimeout(timeoutId);
            timeoutId = setTimeout(() => func.apply(this, args), delay);
        };
    }

    function getCsrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.content : '';
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function formatPrice(price) {
        return new Intl.NumberFormat('ru-RU', {
            style: 'currency',
            currency: 'BYN',
            minimumFractionDigits: 2
        }).format(price);
    }

    // НОВОЕ: Universal Skeleton Grid
    function showSkeletonGrid() {
        const productsContainer = document.getElementById('products');
        if (!productsContainer) return;
        
        // Определяем количество skeleton по viewport
        const isMobile = window.innerWidth < 768;
        const isTablet = window.innerWidth >= 768 && window.innerWidth < 1024;
        const skeletonCount = isMobile ? 4 : (isTablet ? 6 : 12);
        
        const skeletonHTML = Array(skeletonCount).fill(0).map(() => `
            <div class="product-skeleton">
                <div class="skeleton-img"></div>
                <div class="skeleton-info">
                    <div class="skeleton-line short"></div>
                    <div class="skeleton-line"></div>
                    <div class="skeleton-line medium"></div>
                    <div class="skeleton-line short"></div>
                </div>
            </div>
        `).join('');
        
        productsContainer.innerHTML = skeletonHTML;
        
        // Показываем skeleton grid
        const skeletonGrid = document.getElementById('skeletonGrid');
        if (skeletonGrid) {
            skeletonGrid.style.display = 'grid';
        }
    }
    
    // НОВОЕ: Индикатор "Применяется фильтр..."
    function showFilteringIndicator() {
        const counter = document.getElementById('productsCount');
        if (counter) {
            counter.innerHTML = '<span class="loading-dots">Загрузка<span class="dot">.</span><span class="dot">.</span><span class="dot">.</span></span>';
        }
    }
    
    function showLoadingIndicator() {
        // Старая функция - теперь используем showSkeletonGrid
    }

    function hideLoadingIndicator() {
        // Skeleton заменится на реальные продукты в renderProducts
    }

    function showNotification(message) {
        // Простое уведомление (можно заменить на toast)
        const notification = document.createElement('div');
        notification.className = 'notification';
        notification.textContent = message;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: #10b981;
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 9999;
            animation: slideIn 0.3s ease-out;
        `;
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.animation = 'slideOut 0.3s ease-out';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }

    function showError(message) {
        const notification = document.createElement('div');
        notification.className = 'notification error';
        notification.textContent = message;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: #ef4444;
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 9999;
        `;
        document.body.appendChild(notification);
        
        setTimeout(() => notification.remove(), 3000);
    }

    // Анимации для уведомлений
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
    `;
    document.head.appendChild(style);

    /**
     * AJAX Пагинация
     */
    document.addEventListener('click', function(e) {
        // Проверяем клик по ссылке пагинации
        if (e.target.closest('.pagination a')) {
            e.preventDefault();
            const link = e.target.closest('.pagination a');
            const url = link.getAttribute('href');
            
            if (!url || link.classList.contains('disabled')) {
                return;
            }
            
            loadPage(url);
        }
    });
    
    function loadPage(url) {
        // Показываем skeleton loading
        showSkeletonGrid();
        
        // Прокручиваем к началу товаров
        const productsContainer = document.getElementById('products');
        if (productsContainer) {
            const offset = productsContainer.getBoundingClientRect().top + window.pageYOffset - 100;
            window.scrollTo({
                top: offset,
                behavior: 'smooth'
            });
        }
        
        // Загружаем страницу через AJAX
        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            
            // Обновляем товары
            const newProducts = doc.getElementById('products');
            if (newProducts && productsContainer) {
                productsContainer.innerHTML = newProducts.innerHTML;
            }
            
            // Обновляем пагинацию
            const newPagination = doc.querySelector('.pagination');
            const currentPagination = document.querySelector('.pagination');
            if (newPagination && currentPagination) {
                currentPagination.innerHTML = newPagination.innerHTML;
            }
            
            // Обновляем URL без перезагрузки
            history.pushState({}, '', url);
            
            // Переинициализируем обработчики избранного
            initializeFavorites();
        })
        .catch(error => {
            console.error('Error loading page:', error);
            showError('Ошибка загрузки страницы');
        });
    }
    
    function showSkeletonGrid() {
        const productsContainer = document.getElementById('products');
        if (!productsContainer) return;
        
        const skeletonHTML = Array(12).fill(0).map(() => `
            <div class="skeleton-card">
                <div class="skeleton-image"></div>
                <div class="skeleton-brand"></div>
                <div class="skeleton-title"></div>
                <div class="skeleton-price"></div>
            </div>
        `).join('');
        
        productsContainer.innerHTML = skeletonHTML;
    }

})();
