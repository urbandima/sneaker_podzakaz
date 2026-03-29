/**
 * Admin Panel JavaScript
 * Live search, theme toggle, mobile menu
 */

(function() {
    'use strict';
    
    // === Live Search (Ctrl+K) ===
    const overlay = document.getElementById('search-overlay');
    const input = document.getElementById('admin-search-input');
    const results = document.getElementById('search-results');

    const searchItems = [
        { label: 'Главная', url: '/admin', icon: 'bi-grid-1x2-fill', tags: 'dashboard панель' },
        { label: 'Заказы', url: '/admin/order', icon: 'bi-bag-check-fill', tags: 'orders заказ' },
        { label: 'Новый заказ', url: '/admin/order/create', icon: 'bi-plus-circle', tags: 'create new' },
        { label: 'Каталог', url: '/admin/catalog', icon: 'bi-collection-fill', tags: 'catalog товары products' },
        { label: 'Клиенты', url: '/admin/customer', icon: 'bi-people-fill', tags: 'customers покупатели' },
        { label: 'Купоны', url: '/admin/coupon', icon: 'bi-ticket-detailed-fill', tags: 'coupons скидки промокод' },
        { label: 'Возвраты', url: '/admin/return', icon: 'bi-arrow-return-left', tags: 'returns' },
        { label: 'Аналитика', url: '/admin/statistics', icon: 'bi-bar-chart-line-fill', tags: 'statistics отчёты' },
        { label: 'Маркетинг', url: '/admin/marketing', icon: 'bi-megaphone-fill', tags: 'marketing upsell корзины рекомендации' },
        { label: 'POS-Терминал', url: '/admin/pos', icon: 'bi-shop', tags: 'pos касса терминал продажа' },
        { label: 'Плагины', url: '/admin/plugin', icon: 'bi-plugin', tags: 'plugins расширения интеграции' },
        { label: 'SEO', url: '/admin/seo', icon: 'bi-search-heart', tags: 'seo robots sitemap' },
        { label: 'Настройки', url: '/admin/settings', icon: 'bi-gear-wide-connected', tags: 'settings конфигурация' },
    ];

    function openSearch() { 
        if (overlay) {
            overlay.style.display = 'flex'; 
            if (input) input.value = ''; 
            renderResults(''); 
            setTimeout(() => {
                if (input) input.focus();
            }, 50); 
        }
    }
    
    function closeSearch() { 
        if (overlay) overlay.style.display = 'none'; 
    }

    function renderResults(q) {
        if (!results) return;
        
        const lq = q.toLowerCase();
        const filtered = q ? searchItems.filter(i => (i.label + ' ' + i.tags).toLowerCase().includes(lq)) : searchItems;
        results.innerHTML = filtered.map((item, idx) =>
            '<a href="' + item.url + '" class="admin-search-item' + (idx === 0 ? ' active' : '') + '">' +
            '<i class="bi ' + item.icon + '"></i><span>' + item.label + '</span></a>'
        ).join('') || '<div class="admin-search-empty">Ничего не найдено</div>';
    }

    // === Keyboard shortcuts ===
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey && e.key === 'k') { 
            e.preventDefault(); 
            openSearch(); 
        }
        if (e.key === 'Escape') closeSearch();
        
        if (overlay && overlay.style.display === 'flex') {
            if (e.key === 'Enter') {
                const first = results.querySelector('.admin-search-item.active, .admin-search-item');
                if (first) { 
                    window.location.href = first.href; 
                }
            }
            if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                e.preventDefault();
                const items = results.querySelectorAll('.admin-search-item');
                if (!items.length) return;
                let cur = results.querySelector('.admin-search-item.active');
                if (cur) cur.classList.remove('active');
                let idx = Array.from(items).indexOf(cur);
                idx = e.key === 'ArrowDown' ? Math.min(idx + 1, items.length - 1) : Math.max(idx - 1, 0);
                items[idx].classList.add('active');
                items[idx].scrollIntoView({ block: 'nearest' });
            }
        }
    });

    // === Input events ===
    if (input) {
        input.addEventListener('input', function() { 
            renderResults(this.value); 
        });
    }
    
    if (overlay) {
        overlay.addEventListener('click', function(e) { 
            if (e.target === overlay) closeSearch(); 
        });
    }

    // === Mobile Sidebar Toggle ===
    const sidebar = document.getElementById('admin-sidebar');
    document.addEventListener('click', function(e) {
        if (e.target.closest('.admin-mobile-toggle')) {
            if (sidebar) {
                sidebar.classList.toggle('open');
            }
        }
    });

    // === Theme Toggle ===
    const themeToggle = document.getElementById('theme-toggle');
    const themeIcon = document.getElementById('theme-icon');
    const html = document.documentElement;

    if (themeToggle && themeIcon) {
        function applyTheme(t) {
            html.setAttribute('data-theme', t);
            localStorage.setItem('admin-theme', t);
            themeIcon.className = t === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-fill';
        }

        const saved = localStorage.getItem('admin-theme') || 'light';
        applyTheme(saved);

        themeToggle.addEventListener('click', function() {
            applyTheme(html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark');
        });

        // Keyboard shortcut for theme
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 'd') { 
                e.preventDefault(); 
                themeToggle.click(); 
            }
        });
    }

})();
