/**
 * UI Enhancements - СНИКЕРХЭД
 * Accordion, Skeleton, Infinite Scroll, Sticky Filters
 */

(function() {
    'use strict';

    // ============================================
    // 1. ACCORDION ХАРАКТЕРИСТИК
    // ============================================

    class SpecsAccordion {
        constructor(element) {
            this.element = element;
            this.header = element.querySelector('.specs-header');
            this.content = element.querySelector('.specs-content');
            this.isOpen = element.classList.contains('open');
            
            this.init();
        }

        init() {
            if (!this.header || !this.content) return;

            this.header.addEventListener('click', () => {
                this.toggle();
            });

            // Установить правильную высоту при загрузке
            if (this.isOpen) {
                this.content.style.maxHeight = this.content.scrollHeight + 'px';
            }
        }

        toggle() {
            this.isOpen = !this.isOpen;
            this.element.classList.toggle('open', this.isOpen);

            if (this.isOpen) {
                this.content.style.maxHeight = this.content.scrollHeight + 'px';
            } else {
                this.content.style.maxHeight = '0';
            }
        }

        open() {
            if (!this.isOpen) this.toggle();
        }

        close() {
            if (this.isOpen) this.toggle();
        }
    }

    // ============================================
    // 2. SKELETON LOADER
    // ============================================

    const SkeletonLoader = {
        // Генерация HTML skeleton карточки
        generateCardSkeleton() {
            return `
                <div class="product-card-skeleton">
                    <div class="skeleton skeleton-image"></div>
                    <div class="skeleton-body">
                        <div class="skeleton skeleton-line short"></div>
                        <div class="skeleton skeleton-line long"></div>
                        <div class="skeleton skeleton-line long"></div>
                    </div>
                </div>
            `;
        },

        // Показать skeleton в контейнере
        show(container, count = 8) {
            const skeletons = Array.from({ length: count }, () => 
                this.generateCardSkeleton()
            ).join('');

            container.innerHTML = skeletons;
            container.classList.add('loading');
        },

        // Скрыть skeleton
        hide(container) {
            container.classList.remove('loading');
        }
    };

    // ============================================
    // 3. INFINITE SCROLL
    // ============================================

    class InfiniteScroll {
        constructor(options = {}) {
            this.container = options.container || document.querySelector('.products-grid');
            this.loadMoreUrl = options.loadMoreUrl || '/catalog/load-more';
            this.threshold = options.threshold || 300; // px от низа
            this.page = options.initialPage || 1;
            this.isLoading = false;
            this.hasMore = true;
            this.totalPages = options.totalPages || Infinity;
            
            this.init();
        }

        init() {
            if (!this.container) return;

            // Создать loader элемент
            this.createLoader();

            // Scroll listener
            window.addEventListener('scroll', this.handleScroll.bind(this), { passive: true });

            // Intersection Observer (более производительный вариант)
            if ('IntersectionObserver' in window) {
                this.observer = new IntersectionObserver(
                    entries => {
                        entries.forEach(entry => {
                            if (entry.isIntersecting && !this.isLoading && this.hasMore) {
                                this.loadMore();
                            }
                        });
                    },
                    { rootMargin: '200px' }
                );

                this.observer.observe(this.loader);
            }
        }

        createLoader() {
            this.loader = document.createElement('div');
            this.loader.className = 'infinite-scroll-loading';
            this.loader.innerHTML = '<div class="spinner"></div>';
            this.loader.style.display = 'none';
            
            // Вставить после контейнера
            this.container.parentNode.insertBefore(
                this.loader,
                this.container.nextSibling
            );
        }

        handleScroll() {
            if (this.isLoading || !this.hasMore) return;

            const scrollHeight = document.documentElement.scrollHeight;
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            const clientHeight = window.innerHeight;

            if (scrollHeight - (scrollTop + clientHeight) < this.threshold) {
                this.loadMore();
            }
        }

        async loadMore() {
            if (this.isLoading || !this.hasMore) return;

            this.isLoading = true;
            this.loader.style.display = 'flex';

            try {
                const nextPage = this.page + 1;
                
                // Получить текущие параметры фильтров
                const params = new URLSearchParams(window.location.search);
                params.set('page', nextPage);

                const response = await fetch(`${this.loadMoreUrl}?${params.toString()}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!response.ok) throw new Error('Network error');

                const data = await response.json();

                if (data.html && data.html.trim()) {
                    // Создать временный контейнер
                    const temp = document.createElement('div');
                    temp.innerHTML = data.html;

                    // Добавить товары
                    const products = temp.querySelectorAll('.product-card');
                    products.forEach(product => {
                        this.container.appendChild(product);
                    });

                    // Переинициализировать свайп для новых карточек
                    if (window.initProductSwipe) {
                        window.initProductSwipe();
                    }

                    this.page = nextPage;

                    // Проверить, есть ли ещё страницы
                    if (nextPage >= this.totalPages || !data.hasMore) {
                        this.hasMore = false;
                        this.showEndMessage();
                    }
                } else {
                    this.hasMore = false;
                    this.showEndMessage();
                }

            } catch (error) {
                console.error('Infinite scroll error:', error);
                this.showError();
            } finally {
                this.isLoading = false;
                this.loader.style.display = 'none';
            }
        }

        showEndMessage() {
            this.loader.innerHTML = '<p style="text-align:center;color:var(--gray);padding:20px;">Все товары загружены</p>';
            this.loader.style.display = 'block';
        }

        showError() {
            this.loader.innerHTML = '<p style="text-align:center;color:var(--danger);padding:20px;">Ошибка загрузки. Попробуйте обновить страницу.</p>';
            this.loader.style.display = 'block';
        }

        reset() {
            this.page = 1;
            this.hasMore = true;
            this.isLoading = false;
            this.loader.innerHTML = '<div class="spinner"></div>';
            this.loader.style.display = 'none';
        }
    }

    // ============================================
    // 4. STICKY FILTERS (Mobile)
    // ============================================

    class StickyFilters {
        constructor() {
            this.sidebar = document.getElementById('sidebar');
            this.overlay = document.querySelector('.sidebar-overlay') || document.getElementById('overlay');
            this.openBtn = document.querySelector('.filters-sticky-btn') || document.querySelector('.filter-toggle-btn');
            this.closeBtn = document.querySelector('.sidebar-header .close-btn');
            this.activeFiltersCount = 0;
            
            // Debug
            console.log('StickyFilters init:', {
                sidebar: !!this.sidebar,
                overlay: !!this.overlay,
                openBtn: !!this.openBtn,
                closeBtn: !!this.closeBtn
            });
            
            this.init();
        }

        init() {
            if (!this.sidebar) {
                console.warn('StickyFilters: sidebar не найден');
                return;
            }

            // Создать overlay если нет
            if (!this.overlay) {
                this.overlay = document.createElement('div');
                this.overlay.className = 'sidebar-overlay';
                document.body.appendChild(this.overlay);
            }

            // Создать кнопку если нет (только mobile)
            if (!this.openBtn && window.innerWidth < 768) {
                this.createStickyButton();
            }

            // События
            if (this.openBtn) {
                console.log('Добавляем listener для openBtn');
                this.openBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    console.log('Filter button clicked!');
                    this.open();
                });
            } else {
                console.warn('StickyFilters: openBtn не найдена');
            }

            if (this.closeBtn) {
                this.closeBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.close();
                });
            }

            this.overlay.addEventListener('click', () => this.close());

            // Подсчёт активных фильтров
            this.updateActiveFiltersCount();
            
            // Отслеживать изменения фильтров
            this.sidebar.addEventListener('change', () => {
                this.updateActiveFiltersCount();
            });
        }

        createStickyButton() {
            this.openBtn = document.createElement('button');
            this.openBtn.className = 'filters-sticky-btn show-mobile-only';
            this.openBtn.innerHTML = `
                <i class="bi bi-funnel"></i>
                <span class="badge" style="display:none;">0</span>
            `;
            document.body.appendChild(this.openBtn);
            
            this.openBtn.addEventListener('click', () => this.open());
        }

        open() {
            console.log('Opening filters sidebar');
            this.sidebar.classList.add('open');
            this.sidebar.classList.add('active'); // Для совместимости
            this.overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        close() {
            console.log('Closing filters sidebar');
            this.sidebar.classList.remove('open');
            this.sidebar.classList.remove('active'); // Для совместимости
            this.overlay.classList.remove('active');
            document.body.style.overflow = '';
        }

        updateActiveFiltersCount() {
            const checkedFilters = this.sidebar.querySelectorAll('input[type="checkbox"]:checked');
            this.activeFiltersCount = checkedFilters.length;

            const badge = this.openBtn?.querySelector('.badge');
            if (badge) {
                if (this.activeFiltersCount > 0) {
                    badge.textContent = this.activeFiltersCount;
                    badge.style.display = 'block';
                } else {
                    badge.style.display = 'none';
                }
            }
        }
    }

    // ============================================
    // 5. SMART SORTING
    // ============================================

    class SmartSorting {
        constructor(selectElement) {
            this.select = selectElement;
            this.init();
        }

        init() {
            if (!this.select) return;

            this.select.addEventListener('change', (e) => {
                const sortValue = e.target.value;
                this.applySorting(sortValue);
            });
        }

        applySorting(sortValue) {
            // Получить текущие параметры
            const url = new URL(window.location.href);
            url.searchParams.set('sort', sortValue);
            url.searchParams.delete('page'); // Сбросить страницу

            // Перезагрузить с новой сортировкой
            window.location.href = url.toString();
        }

        // Для AJAX версии (опционально)
        async applySortingAjax(sortValue) {
            const params = new URLSearchParams(window.location.search);
            params.set('sort', sortValue);
            params.delete('page');

            try {
                const response = await fetch(`/catalog/filter?${params.toString()}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const data = await response.json();
                
                if (data.html) {
                    const container = document.querySelector('.products-grid');
                    container.innerHTML = data.html;
                    
                    // Переинициализировать swipe
                    if (window.initProductSwipe) {
                        window.initProductSwipe();
                    }
                }

                // Обновить URL без перезагрузки
                window.history.pushState({}, '', `?${params.toString()}`);

            } catch (error) {
                console.error('Sorting error:', error);
            }
        }
    }

    // ============================================
    // 6. ИНИЦИАЛИЗАЦИЯ ВСЕХ КОМПОНЕНТОВ
    // ============================================

    function initUIEnhancements() {
        // Accordion
        document.querySelectorAll('.specs-section').forEach(section => {
            new SpecsAccordion(section);
        });

        // Sticky Filters (на всех устройствах, но sticky кнопка только на mobile)
        new StickyFilters();

        // Smart Sorting
        const sortSelect = document.querySelector('[name="sort"]');
        if (sortSelect) {
            new SmartSorting(sortSelect);
        }

        // Infinite Scroll (опционально, можно включить/выключить)
        const enableInfiniteScroll = document.body.dataset.infiniteScroll === 'true';
        if (enableInfiniteScroll) {
            const totalPages = parseInt(document.body.dataset.totalPages) || 10;
            const productsContainer = document.getElementById('products') || document.querySelector('.products-grid');
            
            if (productsContainer) {
                new InfiniteScroll({
                    container: productsContainer,
                    loadMoreUrl: '/catalog/load-more',
                    totalPages: totalPages,
                    threshold: 300
                });
                console.log('Infinite Scroll initialized:', { totalPages, container: productsContainer.id });
            }
        }
    }

    // Инициализация при загрузке
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initUIEnhancements);
    } else {
        initUIEnhancements();
    }

    // Экспорт для глобального использования
    window.UIEnhancements = {
        SkeletonLoader,
        InfiniteScroll,
        StickyFilters,
        SmartSorting,
        SpecsAccordion
    };

})();
