/**
 * Catalog JavaScript - AJAX фильтрация и избранное
 * СНИКЕРХЭД
 */

(function () {
    'use strict';

    // Конфигурация
    // ИСПРАВЛЕНО (Проблема #1): Используем явный AJAX endpoint
    const CONFIG = {
        ajaxFilterUrl: '/catalog/filter',  // Явный роут добавлен в config/web.php
        addFavoriteUrl: '/catalog/add-favorite',
        removeFavoriteUrl: '/catalog/remove-favorite',
        searchUrl: '/catalog/search',
        filterDebounceDelay: 200,
    };

    // Состояние фильтров
    let filterState = {
        brands: [],
        categories: [],
        priceFrom: null,
        priceTo: null,
        sizes: [],
        sizeSystem: 'eu',
        colors: [],
        discountAny: false,
        discountRange: [],
        rating: null,
        conditions: [],
        stockStatus: null,
        sortBy: 'popular',
        page: 1,
        perPage: 24,
        characteristics: {}, // Динамические характеристики (формат: char_ID => [value_ids])
    };

    // Кэш DOM элементов для повышения производительности
    let domCache = {
        productsContainer: null,
        skeletonGrid: null,
        counter: null
    };

    // Инициализация кэша DOM
    function initDOMCache() {
        domCache.productsContainer = document.getElementById('products');
        domCache.skeletonGrid = document.getElementById('skeletonGrid');
        domCache.counter = document.getElementById('productsCount');
    }

    // Глобальный обработчик для подавления AbortError в консоли
    window.addEventListener('unhandledrejection', function (event) {
        if (event.reason && (event.reason.name === 'AbortError' || event.reason.message === 'Aborted')) {
            event.preventDefault(); // Подавляем ошибку в консоли
        }
    });

    // Инициализация при загрузке страницы
    document.addEventListener('DOMContentLoaded', function () {
        initDOMCache();
        initializeFilters();
        initializeFavorites();
        initializeViewToggle();
        initProductCardGalleries(document);
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

        // Характеристики (динамически) - все типы: checkbox, radio, text
        document.querySelectorAll('input[name^="char_"], input[name*="char_"]').forEach(input => {
            if (input.type === 'checkbox' || input.type === 'radio') {
                input.addEventListener('change', handleFilterChange);
            } else {
                input.addEventListener('input', debounce(handleFilterChange, CONFIG.filterDebounceDelay));
            }
        });

        // Цвета
        document.querySelectorAll('input[name="colors[]"]').forEach(checkbox => {
            checkbox.addEventListener('change', handleFilterChange);
        });

        // Скидка
        document.querySelectorAll('input[name="discount_any"], input[name="discount_range[]"]').forEach(checkbox => {
            checkbox.addEventListener('change', handleFilterChange);
        });

        // Рейтинг
        document.querySelectorAll('input[name="rating"]').forEach(radio => {
            radio.addEventListener('change', handleFilterChange);
        });

        // Условия
        document.querySelectorAll('input[name="conditions[]"]').forEach(checkbox => {
            checkbox.addEventListener('change', handleFilterChange);
        });
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

        // Размеры
        filterState.sizes = Array.from(document.querySelectorAll('input[name="sizes[]"]:checked'))
            .map(cb => cb.value);

        // Текущая система размеров (проверяем несколько вариантов кнопок)
        const activeSystemBtn = document.querySelector('.size-system-btn.active, .size-system-btn-small.active');
        if (activeSystemBtn) {
            filterState.sizeSystem = activeSystemBtn.dataset.system || 'eu';
        } else {
            // Fallback на localStorage или EU по умолчанию
            filterState.sizeSystem = localStorage.getItem('preferredSizeSystem') || 'eu';
        }

        // Цена
        const priceFrom = document.querySelector('input[name="price_from"]');
        const priceTo = document.querySelector('input[name="price_to"]');
        filterState.priceFrom = priceFrom && priceFrom.value ? parseFloat(priceFrom.value) : null;
        filterState.priceTo = priceTo && priceTo.value ? parseFloat(priceTo.value) : null;

        // Цвета
        filterState.colors = Array.from(document.querySelectorAll('input[name="colors[]"]:checked'))
            .map(cb => cb.value);

        // Скидка
        filterState.discountAny = document.querySelector('input[name="discount_any"]:checked') ? true : false;
        filterState.discountRange = Array.from(document.querySelectorAll('input[name="discount_range[]"]:checked'))
            .map(cb => cb.value);

        // Рейтинг
        const ratingInput = document.querySelector('input[name="rating"]:checked');
        filterState.rating = ratingInput ? ratingInput.value : null;

        // Условия
        filterState.conditions = Array.from(document.querySelectorAll('input[name="conditions[]"]:checked'))
            .map(cb => cb.value);

        // Характеристики (динамические)
        filterState.characteristics = {};
        document.querySelectorAll('input[name^="char_"]:checked, input[name*="char_"]:checked').forEach(input => {
            const match = input.name.match(/^char_(\d+)(\[\])?$/);
            if (match) {
                const charId = 'char_' + match[1];
                if (!filterState.characteristics[charId]) {
                    filterState.characteristics[charId] = [];
                }
                filterState.characteristics[charId].push(parseInt(input.value));
            }
        });

        // Сброс страницы при изменении фильтров
        filterState.page = 1;
    }

    /**
     * Применить фильтры через AJAX
     * ОПТИМИЗИРОВАНО: Отмена предыдущих запросов для повышения производительности
     */
    let currentRequest = null;

    function applyFiltersAjax() {
        // Отменяем предыдущий запрос, если он ещё выполняется
        if (currentRequest) {
            try {
                currentRequest.abort();
            } catch (e) {
                // Игнорируем ошибки при отмене
            }
            currentRequest = null;
        }

        // Минимальный индикатор загрузки (используем кэш)
        if (domCache.productsContainer) {
            domCache.productsContainer.style.opacity = '0.6';
        }

        const formData = new FormData();
        formData.append('brands', JSON.stringify(filterState.brands));
        formData.append('categories', JSON.stringify(filterState.categories));
        formData.append('sizes', JSON.stringify(filterState.sizes));
        formData.append('sizeSystem', filterState.sizeSystem);
        formData.append('price_from', filterState.priceFrom !== null && filterState.priceFrom !== undefined ? filterState.priceFrom : '');
        formData.append('price_to', filterState.priceTo !== null && filterState.priceTo !== undefined ? filterState.priceTo : '');
        formData.append('colors', JSON.stringify(filterState.colors));
        formData.append('discount_any', filterState.discountAny ? '1' : '');
        formData.append('discount_range', JSON.stringify(filterState.discountRange));
        formData.append('rating', filterState.rating || '');
        formData.append('conditions', JSON.stringify(filterState.conditions));
        formData.append('sort', filterState.sortBy);
        formData.append('page', filterState.page);
        formData.append('perPage', filterState.perPage);

        // Характеристики
        for (const charKey in filterState.characteristics) {
            if (filterState.characteristics[charKey].length > 0) {
                formData.append(charKey, JSON.stringify(filterState.characteristics[charKey]));
            }
        }

        // Создаём AbortController для отмены запроса
        const controller = new AbortController();
        currentRequest = controller;

        fetch(CONFIG.ajaxFilterUrl, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': getCsrfToken(),
            },
            body: formData,
            signal: controller.signal
        })
            .then(response => {
                // Проверяем, не был ли запрос отменён
                if (controller.signal.aborted) {
                    return Promise.reject(new DOMException('Aborted', 'AbortError'));
                }

                // ИСПРАВЛЕНО: Проверяем тип контента перед парсингом
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    return response.text().then(text => {
                        throw new Error('Сервер вернул HTML вместо JSON. Проверьте роуты и контроллер.');
                    });
                }

                return response.json();
            })
            .then(data => {
                // Игнорируем, если запрос был отменён
                if (controller.signal.aborted) return;

                // Проверяем структуру ответа
                if (!data || typeof data !== 'object') {
                    throw new Error('Некорректный ответ от сервера');
                }

                // ВРЕМЕННОЕ ЛОГИРОВАНИЕ для отладки

                if (data.success && data.html) {
                    // Используем requestAnimationFrame для плавности
                    requestAnimationFrame(() => {
                        if (domCache.productsContainer) {
                            domCache.productsContainer.innerHTML = data.html;

                            // КРИТИЧНО: Реинициализация lazy loading для новых элементов
                            if (window.LazyLoadUtils) {
                                LazyLoadUtils.observe(domCache.productsContainer);
                            }
                            initProductCardGalleries(domCache.productsContainer);
                        }

                        // Обновляем счетчик товаров
                        if (domCache.counter && data.pagination && data.pagination.total !== undefined) {
                            domCache.counter.textContent = data.pagination.total;
                        }

                        // Обновляем фильтры и пагинацию
                        if (data.filters) {
                            updateFilterCounts(data.filters);
                        }
                        if (data.pagination) {
                            updatePagination(data.pagination, data.paginationHtml);
                        }

                        // Обновляем активные фильтры
                        if (data.activeFiltersHtml !== undefined) {
                            updateActiveFilters(data.activeFiltersHtml, data.activeFilters);
                        }

                        updateURL();
                        saveFilterHistory(data);
                        hideLoadingIndicator();
                    });
                } else {
                    showError('Ошибка загрузки товаров');
                    hideLoadingIndicator();
                }
                currentRequest = null;
            })
            .catch(error => {
                // Тихо игнорируем AbortError - это нормальное поведение при отмене запроса
                if (error.name === 'AbortError' || error.message === 'Aborted') {
                    // Просто возвращаемся без ошибки
                    return;
                }

                // Показываем понятное сообщение пользователю
                if (error.message.includes('JSON')) {
                    showError('Ошибка формата данных. Обновите страницу.');
                } else if (error.message !== 'Failed to fetch') {
                    showError(error.message || 'Ошибка соединения с сервером');
                }

                hideLoadingIndicator();
                currentRequest = null;
            });
    }

    /**
     * Отрисовка товаров
     */
    function renderProducts(products) {
        const container = document.getElementById('products');
        if (!container) {
            return;
        }

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
     * Галереи изображений карточек (стрелочки)
     */
    function initProductCardGalleries(scope) {
        const context = scope instanceof HTMLElement ? scope : document;
        const wrappers = context.querySelectorAll('.product-image-wrapper[data-gallery-size]');

        wrappers.forEach(wrapper => {
            if (wrapper.dataset.galleryInitialized === '1') {
                return;
            }

            const slider = wrapper.querySelector('.product-image-slider');
            if (!slider) {
                return;
            }

            const images = slider.querySelectorAll('.product-image');
            if (images.length <= 1) {
                return;
            }

            wrapper.dataset.galleryInitialized = '1';
            const total = images.length;

            const loadImageIfNeeded = (img) => {
                if (!img || !img.dataset || !img.dataset.src) {
                    return;
                }

                if (window.LazyLoadUtils && typeof window.LazyLoadUtils.forceLoad === 'function') {
                    window.LazyLoadUtils.forceLoad(img);
                } else {
                    img.src = img.dataset.src;
                    delete img.dataset.src;
                }
            };

            const updateActiveImage = (newIndex) => {
                const normalizedIndex = ((newIndex % total) + total) % total;
                slider.dataset.activeIndex = normalizedIndex;

                images.forEach(img => {
                    const imgIndex = parseInt(img.dataset.imageIndex || '0', 10);
                    if (imgIndex === normalizedIndex) {
                        img.classList.add('is-active');
                        loadImageIfNeeded(img);
                    } else {
                        img.classList.remove('is-active');
                    }
                });
            };

            // Загружаем активное изображение (на случай, если лениво)
            const initialIndex = parseInt(slider.dataset.activeIndex || '0', 10);
            updateActiveImage(initialIndex);

            const handleNavClick = (event) => {
                event.preventDefault();
                event.stopPropagation();
                const direction = event.currentTarget.dataset.direction === 'prev' ? -1 : 1;
                const currentIndex = parseInt(slider.dataset.activeIndex || '0', 10);
                updateActiveImage(currentIndex + direction);
            };

            const prevBtn = wrapper.querySelector('.product-image-nav.prev');
            const nextBtn = wrapper.querySelector('.product-image-nav.next');

            if (prevBtn) {
                prevBtn.addEventListener('click', handleNavClick);
            }
            if (nextBtn) {
                nextBtn.addEventListener('click', handleNavClick);
            }
        });
    }

    /**
     * Обновление счетчиков фильтров
     * ИСПРАВЛЕНО: Добавлено обновление счетчиков для характеристик (включая пол)
     */
    function updateFilterCounts(filters) {

        // Обновление счетчиков брендов
        if (filters.brands) {
            filters.brands.forEach(brand => {
                const checkbox = document.querySelector(`input[name="brands[]"][value="${brand.id}"]`);
                if (checkbox) {
                    const countSpan = checkbox.closest('label').querySelector('.count');
                    if (countSpan) {
                        countSpan.textContent = brand.count;
                        // Обновляем состояние disabled
                        const label = checkbox.closest('label');
                        if (brand.count === 0) {
                            checkbox.disabled = true;
                            label.classList.add('disabled');
                        } else {
                            checkbox.disabled = false;
                            label.classList.remove('disabled');
                        }
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
                        countSpan.textContent = category.count;
                        // Обновляем состояние disabled
                        const label = checkbox.closest('label');
                        if (category.count === 0) {
                            checkbox.disabled = true;
                            label.classList.add('disabled');
                        } else {
                            checkbox.disabled = false;
                            label.classList.remove('disabled');
                        }
                    }
                }
            });
        }

        // НОВОЕ: Обновление счетчиков характеристик (пол, сезон и т.д.)
        if (filters.characteristics) {
            filters.characteristics.forEach(characteristic => {
                const charId = characteristic.id;
                const charKey = 'char_' + charId;


                // Обновляем счетчики для каждого значения характеристики
                if (characteristic.values && Array.isArray(characteristic.values)) {
                    characteristic.values.forEach(value => {
                        const checkbox = document.querySelector(`input[name="${charKey}[]"][value="${value.id}"]`);
                        if (checkbox) {
                            const countSpan = checkbox.closest('label').querySelector('.count');
                            if (countSpan) {
                                countSpan.textContent = value.count || 0;
                            }

                            // Обновляем состояние disabled
                            const label = checkbox.closest('label');
                            if (!value.count || value.count === 0) {
                                checkbox.disabled = true;
                                label.classList.add('disabled');
                            } else {
                                checkbox.disabled = false;
                                label.classList.remove('disabled');
                            }
                        }
                    });
                }
            });
        }
    }

    /**
     * Обновление активных фильтров
     */
    function updateActiveFilters(activeFiltersHtml, activeFilters) {
        const activeFiltersContainer = document.querySelector('.active-filters');

        if (!activeFiltersHtml || activeFilters.length === 0) {
            // Скрываем контейнер, если фильтров нет
            if (activeFiltersContainer) {
                activeFiltersContainer.style.display = 'none';
            }
        } else {
            if (activeFiltersContainer) {
                // Обновляем HTML
                activeFiltersContainer.innerHTML = activeFiltersHtml;
                activeFiltersContainer.style.display = 'flex';
            } else {
                // Создаем контейнер, если его нет
                const toolbar = document.querySelector('.catalog-toolbar');
                if (toolbar && toolbar.parentNode) {
                    const newContainer = document.createElement('div');
                    newContainer.className = 'active-filters';
                    newContainer.innerHTML = activeFiltersHtml;
                    newContainer.style.display = 'flex';
                    toolbar.parentNode.insertBefore(newContainer, toolbar.nextSibling);
                }
            }
        }

        // Обновляем бейдж количества фильтров
        const filtersBadge = document.getElementById('filtersCountBadge');
        if (filtersBadge) {
            if (activeFilters && activeFilters.length > 0) {
                filtersBadge.textContent = activeFilters.length;
                filtersBadge.style.display = 'inline-flex';
            } else {
                filtersBadge.style.display = 'none';
            }
        }
    }

    /**
     * Обновление пагинации
     */
    function updatePagination(pagination, paginationHtml) {
        // Обновляем счетчик товаров
        const resultsInfo = document.querySelector('.results-info strong');
        if (resultsInfo) {
            resultsInfo.textContent = pagination.total;
        }

        const productsCount = document.getElementById('productsCount');
        if (productsCount) {
            productsCount.textContent = pagination.total;
        }

        // Обновляем/вставляем HTML пагинации
        let paginationContainer = document.querySelector('.pagination');

        if (paginationHtml) {
            // Если пагинация нужна (pageCount > 1)
            if (!paginationContainer) {
                // Создаем контейнер если его нет
                paginationContainer = document.createElement('div');
                paginationContainer.className = 'pagination';
                const productsContainer = document.getElementById('products');
                if (productsContainer && productsContainer.parentNode) {
                    productsContainer.parentNode.insertBefore(paginationContainer, productsContainer.nextSibling);
                }
            }
            paginationContainer.innerHTML = paginationHtml;
            paginationContainer.style.display = 'block';
        } else {
            // Если пагинация не нужна - скрываем
            if (paginationContainer) {
                paginationContainer.style.display = 'none';
            }
        }
    }

    /**
     * Загрузка страницы пагинации
     */
    function loadPage(url) {
        if (!url) return;

        // Извлекаем номер страницы из URL
        const urlParams = new URLSearchParams(url.split('?')[1] || '');
        const page = parseInt(urlParams.get('page')) || 1;

        // Обновляем состояние фильтров
        filterState.page = page;

        // Применяем фильтры с новой страницей
        applyFiltersAjax();

        // Прокручиваем к началу каталога
        const catalogTop = document.getElementById('products');
        if (catalogTop) {
            catalogTop.scrollIntoView({ behavior: 'smooth', block: 'start' });
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

        // Размеры (добавляем в SEF URL с системой измерения)
        if (filterState.sizes && filterState.sizes.length > 0) {
            const sizeSystem = (filterState.sizeSystem || 'eu').toLowerCase();
            parts.push('size-' + sizeSystem + '-' + filterState.sizes.join('-'));
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

        // Размеры теперь в SEF URL, не дублируем в query параметрах
        // Оставляем только если нет SEF части с размерами
        if (filterState.sizes && filterState.sizes.length > 0 && sefUrl === '/catalog/') {
            params.set('sizes', filterState.sizes.join(','));
            if (filterState.sizeSystem && filterState.sizeSystem !== 'eu') {
                params.set('size_system', filterState.sizeSystem);
            }
        }

        const newUrl = sefUrl + (params.toString() ? '?' + params.toString() : '');
        window.history.pushState({ filters: filterState }, '', newUrl);
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
     * Инициализация переключения вида
     */
    function initializeViewToggle() {
        const viewButtons = document.querySelectorAll('.view-btn');
        viewButtons.forEach(btn => {
            btn.addEventListener('click', function () {
                viewButtons.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                const view = this.dataset.view;
                const container = document.getElementById('products');
                if (container) {
                    container.className = view === 'list' ? 'products-list' : 'products-grid';
                }
            });
        });
    }

    /**
     * Применение сортировки
     */
    window.applySort = function (sortBy) {
        filterState.sortBy = sortBy;
        applyFiltersAjax();
    };

    // resetFilters определяется ниже и экспортируется как window.resetFilters

    /**
     * Применение фильтров (вызывается из HTML)
     */
    window.applyFilters = function () {
        collectFilterState();
        applyFiltersAjax();

        // Закрываем sidebar после применения фильтров (мобильная версия)
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');
        if (sidebar && sidebar.classList.contains('active')) {
            sidebar.classList.remove('active');
            if (overlay) {
                overlay.classList.remove('active');
            }
            document.body.style.overflow = '';
        }
    };

    // Утилиты — debounce/getCsrfToken/escapeHtml/formatPrice provided by SH.* from utils.js

    var debounce = SH.debounce;

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
        if (domCache.productsContainer) {
            domCache.productsContainer.style.opacity = '1';
        }

        if (domCache.skeletonGrid) {
            domCache.skeletonGrid.style.display = 'none';
        }

        if (domCache.counter && domCache.productsContainer) {
            domCache.counter.textContent = domCache.productsContainer.children.length;
        }
    }

    function showError(message) {
        if (!window.NotificationManager) {
            return;
        }

        if (typeof window.NotificationManager.error === 'function') {
            window.NotificationManager.error(message);
        }
    }

    /**
     * AJAX Пагинация
     */
    document.addEventListener('click', function (e) {
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

        // Проверяем клик по кнопке "Добавить в корзину"
        if (e.target.closest('.quickAddToCart')) {
            quickAddToCart(e, e.target.dataset.productId);
        }
    });

    // Делаем функции глобальными для доступа через onclick

    // Wrapper для toggleFavorite (короткое имя для удобства)
    window.toggleFav = function (e, id) {
        e.stopPropagation();
        e.preventDefault();
        // Вызываем глобальную функцию с правильными параметрами
        if (typeof window.toggleFavorite === 'function') {
            window.toggleFavorite(e, id);
        } else {
        }
    };

    window.quickAddToCart = function (e, productId) {
        e.preventDefault();
        e.stopPropagation();

        const button = e.currentTarget;
        const originalText = button.innerHTML;

        // Показываем загрузку
        button.innerHTML = '<i class="bi bi-hourglass-split"></i> <span>Добавляем...</span>';
        button.classList.add('loading');
        button.disabled = true;
        button.setAttribute('aria-busy', 'true');

        const formData = new FormData();
        formData.append('product_id', productId);
        formData.append('quantity', 1);
        formData.append('_csrf', getCsrfToken());

        fetch('/cart/add', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    throw new Error(data.message || 'Ошибка добавления в корзину');
                }

                if (typeof updateCartCount === 'function') {
                    updateCartCount(data.count);
                }

                button.innerHTML = '<i class="bi bi-check-circle"></i> <span>Добавлено!</span>';
                button.classList.remove('loading');
                button.classList.add('success');

                if (window.NotificationManager) {
                    NotificationManager.success('✓ Товар добавлен в корзину');
                }

                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.classList.remove('success');
                    resetButtonState(button);
                }, 1500);
            })
            .catch(error => {
                button.innerHTML = '<i class="bi bi-x-circle"></i> <span>Ошибка</span>';
                button.classList.remove('loading');
                button.classList.add('error');

                if (window.NotificationManager) {
                    NotificationManager.error(error.message || 'Ошибка добавления в корзину');
                }

                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.classList.remove('error');
                    resetButtonState(button);
                }, 1500);
            });
    }

    /**
     * Сброс всех фильтров
     */
    function resetFilters() {
        // Снимаем все чекбоксы и радио
        document.querySelectorAll('.sidebar input[type="checkbox"]:checked, .sidebar input[type="radio"]:checked').forEach(input => {
            input.checked = false;
        });

        // Очищаем текстовые поля
        document.querySelectorAll('.sidebar input[type="text"], .sidebar input[type="number"]').forEach(input => {
            input.value = '';
        });

        // Сбрасываем состояние
        filterState = {
            brands: [],
            categories: [],
            priceFrom: null,
            priceTo: null,
            sizes: [],
            sizeSystem: 'eu',
            colors: [],
            stockStatus: null,
            sortBy: 'popular',
            page: 1,
            perPage: 24,
            characteristics: {},
        };

        // Перезагружаем страницу без параметров
        window.location.href = window.location.pathname;
    }

    // Делаем функцию глобальной
    window.resetFilters = resetFilters;

    function resetButtonState(button) {
        button.disabled = false;
        button.classList.remove('loading');
        button.removeAttribute('aria-busy');
    }

})();

/* ============================================================
   GLOBAL CATALOG UI FUNCTIONS
   Called via onclick="" attributes in catalog/index.php
   ============================================================ */

/**
 * Switch size system in the quick-filter bar (EU/US/UK/CM).
 * Shows only the .size-group[data-system=system] and marks the correct button active.
 */
window.switchSizeSystem = function (system) {
    // Update toggle buttons
    document.querySelectorAll('.size-system-btn').forEach(function (btn) {
        btn.classList.toggle('active', btn.dataset.system === system);
    });

    // Show matching size group, hide others
    document.querySelectorAll('.sizes-scroll-container .size-group').forEach(function (group) {
        group.style.display = group.dataset.system === system ? 'flex' : 'none';
    });

    // Persist preference and sync filterState if available
    try { localStorage.setItem('preferredSizeSystem', system); } catch (e) { }
    if (window._catalogFilterState) window._catalogFilterState.sizeSystem = system;
};

/**
 * Switch size system inside the sidebar (small buttons).
 */
window.switchSidebarSizeSystem = function (system) {
    // Update sidebar toggle buttons
    document.querySelectorAll('.size-system-btn-small').forEach(function (btn) {
        btn.classList.toggle('active', btn.dataset.system === system);
    });

    // Update sidebar label
    var label = document.getElementById('sidebarSizeSystem');
    if (label) label.textContent = system.toUpperCase();

    // Show matching sidebar size grid, hide others
    document.querySelectorAll('.sidebar-size-grid').forEach(function (grid) {
        grid.style.display = grid.dataset.system === system ? 'grid' : 'none';
    });

    try { localStorage.setItem('preferredSizeSystem', system); } catch (e) { }
    if (window._catalogFilterState) window._catalogFilterState.sizeSystem = system;
};

/**
 * Toggle a brand chip in the quick-filters bar.
 */
window.toggleBrandFilter = function (brandId, brandSlug) {
    var chip = document.querySelector('.brand-chip[data-brand="' + brandId + '"]');
    if (!chip) return;

    var isActive = chip.classList.toggle('active');
    var checkbox = document.querySelector('.filter-group input[value="' + brandId + '"][name="brands[]"]');
    if (checkbox) checkbox.checked = isActive;

    // Trigger AJAX reload if available
    if (typeof window.applyFilters === 'function') window.applyFilters();
};

/**
 * Toggle a size chip in the quick-filter bar.
 */
window.toggleSizeFilter = function (size, system) {
    var chip = document.querySelector('.size-chip[data-size="' + size + '"][data-system="' + system + '"]');
    if (!chip) return;

    var isActive = chip.classList.toggle('active');
    var checkbox = document.querySelector('input[name="sizes[]"][value="' + size + '"][data-system="' + system + '"]');
    if (checkbox) checkbox.checked = isActive;

    if (typeof window.applyFilters === 'function') window.applyFilters();
};

/**
 * Scroll the sizes container left or right.
 */
window.scrollSizes = function (direction) {
    var container = document.getElementById('sizesScrollContainer');
    if (!container) return;
    var amount = 200;
    container.scrollBy({ left: direction === 'left' ? -amount : amount, behavior: 'smooth' });
};

/**
 * Toggle the advanced filters wrapper visibility.
 */
window.toggleAdvancedFilters = function () {
    var wrapper = document.getElementById('advancedFiltersWrapper');
    var btn = document.getElementById('showAdvancedBtn');
    if (!wrapper) return;

    var isOpen = wrapper.style.display !== 'none';
    wrapper.style.display = isOpen ? 'none' : 'block';

    if (btn) {
        var icon = btn.querySelector('.toggle-icon');
        if (icon) icon.style.transform = isOpen ? '' : 'rotate(180deg)';
        btn.classList.toggle('active', !isOpen);
    }
};

/**
 * Open/close the filter sidebar (desktop toggle button).
 */
window.toggleFilters = function () {
    var sidebar = document.getElementById('sidebar');
    var overlay = document.getElementById('overlay');
    if (!sidebar) return;

    var isOpen = sidebar.classList.toggle('active');
    if (overlay) overlay.classList.toggle('active', isOpen);
    document.body.style.overflow = isOpen ? 'hidden' : '';
};

/**
 * Accordion toggle for individual filter groups in the sidebar.
 */
window.toggleFilterGroup = function (titleEl) {
    var group = titleEl.closest ? titleEl.closest('.filter-group') : titleEl.parentElement;
    if (!group) return;

    var content = group.querySelector('.filter-content');
    var icon = titleEl.querySelector('.bi-chevron-down, .bi-chevron-up');
    var isOpen = group.classList.toggle('open');

    if (content) {
        if (isOpen) {
            content.classList.add('filter-content--open');
            content.style.maxHeight = content.scrollHeight + 'px';
        } else {
            content.classList.remove('filter-content--open');
            content.style.maxHeight = '0';
        }
    }

    if (icon) {
        icon.classList.toggle('bi-chevron-down', !isOpen);
        icon.classList.toggle('bi-chevron-up', isOpen);
    }
};

/**
 * Close the filter sidebar (from close button inside sidebar).
 */
window.closeFilters = function () {
    var sidebar = document.getElementById('sidebar');
    var overlay = document.getElementById('overlay');
    if (sidebar) sidebar.classList.remove('active');
    if (overlay) overlay.classList.remove('active');
    document.body.style.overflow = '';
};

/* Initialize size display on DOMContentLoaded */
document.addEventListener('DOMContentLoaded', function () {
    var preferred = 'eu';
    try { preferred = localStorage.getItem('preferredSizeSystem') || 'eu'; } catch (e) { }

    // Init quick-filter sizes
    var groups = document.querySelectorAll('.sizes-scroll-container .size-group');
    if (groups.length > 0) {
        groups.forEach(function (g) {
            g.style.display = g.dataset.system === preferred ? 'flex' : 'none';
        });
        document.querySelectorAll('.size-system-btn').forEach(function (b) {
            b.classList.toggle('active', b.dataset.system === preferred);
        });
    }

    // Init sidebar sizes
    var sidebarGroups = document.querySelectorAll('.sidebar-size-grid');
    if (sidebarGroups.length > 0) {
        sidebarGroups.forEach(function (g) {
            g.style.display = g.dataset.system === preferred ? 'grid' : 'none';
        });
        document.querySelectorAll('.size-system-btn-small').forEach(function (b) {
            b.classList.toggle('active', b.dataset.system === preferred);
        });
        var label = document.getElementById('sidebarSizeSystem');
        if (label) label.textContent = preferred.toUpperCase();
    }

    // Init filter group accordion open states
    document.querySelectorAll('.filter-group.open .filter-content').forEach(function (content) {
        content.classList.add('filter-content--open');
        content.style.maxHeight = content.scrollHeight + 'px';
    });
});
