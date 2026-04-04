/**
 * Admin Panel JS — СНИКЕРХЭД
 * Block 2: Global Search (Ctrl+K), Calculator Drawer, Notification Bell, Mobile Sidebar, Submenu Navigation
 */

(function () {
    'use strict';

    /* ========== B2.1 GLOBAL SEARCH ========== */

    var searchOverlay = null;
    var searchInput = null;
    var searchResults = null;
    var searchDebounceTimer = null;

    function initSearch() {
        searchOverlay = document.getElementById('search-overlay');
        searchInput = document.getElementById('admin-search-input');
        searchResults = document.getElementById('search-results');

        if (!searchOverlay || !searchInput || !searchResults) return;

        // Ctrl+K / Cmd+K открывает оверлей
        document.addEventListener('keydown', function (e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                openSearch();
            }
            if (e.key === 'Escape') {
                closeSearch();
                closeMobileSidebar(); // Also close mobile sidebar on Escape
            }
        });

        // Клик по фону закрывает
        searchOverlay.addEventListener('click', function (e) {
            if (e.target === searchOverlay) {
                closeSearch();
            }
        });

        // Ввод с дебаунсом 200 мс
        searchInput.addEventListener('input', function () {
            clearTimeout(searchDebounceTimer);
            var q = searchInput.value.trim();
            if (q.length < 2) {
                searchResults.innerHTML = '';
                return;
            }
            searchDebounceTimer = setTimeout(function () {
                doSearch(q);
            }, 200);
        });
    }

    function openSearch() {
        if (!searchOverlay) return;
        searchOverlay.style.display = 'flex';
        if (searchInput) {
            searchInput.value = '';
            searchInput.focus();
        }
        if (searchResults) searchResults.innerHTML = '';
    }

    function closeSearch() {
        if (!searchOverlay) return;
        searchOverlay.style.display = 'none';
        if (searchInput) searchInput.value = '';
        if (searchResults) searchResults.innerHTML = '';
    }

    function doSearch(q) {
        searchResults.innerHTML = '<div class="admin-search-loading"><i class="bi bi-hourglass-split"></i> Поиск...</div>';

        var xhr = new XMLHttpRequest();
        xhr.open('GET', '/admin/search/global?q=' + encodeURIComponent(q), true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.onload = function () {
            if (xhr.status !== 200) {
                searchResults.innerHTML = '<div class="admin-search-empty">Ошибка поиска</div>';
                return;
            }
            try {
                var data = JSON.parse(xhr.responseText);
                renderSearchResults(data.results || []);
            } catch (err) {
                searchResults.innerHTML = '<div class="admin-search-empty">Ошибка разбора ответа</div>';
            }
        };
        xhr.onerror = function () {
            searchResults.innerHTML = '<div class="admin-search-empty">Сеть недоступна</div>';
        };
        xhr.send();
    }

    function renderSearchResults(results) {
        if (!results.length) {
            searchResults.innerHTML = '<div class="admin-search-empty">Ничего не найдено</div>';
            return;
        }

        var html = '';
        results.forEach(function (item) {
            html += '<a href="' + item.url + '" class="admin-search-item">' +
                '<div class="admin-search-icon">' + item.icon + '</div>' +
                '<div class="admin-search-content">' +
                '<div class="admin-search-title">' + item.title + '</div>' +
                '<div class="admin-search-desc">' + item.description + '</div>' +
                '</div>' +
                '</a>';
        });

        searchResults.innerHTML = html;
    }

    /* ========== B2.2 MOBILE SIDEBAR ========== */

    function initMobileSidebar() {
        // Показать кнопку мобильного меню на маленьких экранах
        function updateMobileMenuButton() {
            const mobileMenuBtn = document.getElementById('mobile-menu-toggle');
            const sidebarClose = document.getElementById('sidebar-close');
            
            if (window.innerWidth <= 768) {
                if (mobileMenuBtn) mobileMenuBtn.style.display = 'block';
                if (sidebarClose) sidebarClose.style.display = 'block';
            } else {
                if (mobileMenuBtn) mobileMenuBtn.style.display = 'none';
                if (sidebarClose) sidebarClose.style.display = 'none';
                closeMobileSidebar(); // Close sidebar on desktop resize
            }
        }

        // Обновить при загрузке и изменении размера
        updateMobileMenuButton();
        window.addEventListener('resize', updateMobileMenuButton);
    }

    // Функции для управления мобильным сайдбаром
    window.toggleMobileSidebar = function() {
        const sidebar = document.getElementById('admin-sidebar');
        const overlay = document.getElementById('sidebar-overlay');
        
        if (sidebar && overlay) {
            const isOpen = sidebar.classList.contains('open');
            
            if (isOpen) {
                closeMobileSidebar();
            } else {
                openMobileSidebar();
            }
        }
    };

    window.openMobileSidebar = function() {
        const sidebar = document.getElementById('admin-sidebar');
        const overlay = document.getElementById('sidebar-overlay');
        
        if (sidebar && overlay) {
            sidebar.classList.add('open');
            overlay.classList.add('show');
            document.body.style.overflow = 'hidden'; // Prevent body scroll
        }
    };

    window.closeMobileSidebar = function() {
        const sidebar = document.getElementById('admin-sidebar');
        const overlay = document.getElementById('sidebar-overlay');
        
        if (sidebar && overlay) {
            sidebar.classList.remove('open');
            overlay.classList.remove('show');
            document.body.style.overflow = ''; // Restore body scroll
        }
    };

    /* ========== B2.3 SUBMENU NAVIGATION ========== */

    function initSubmenuNavigation() {
        // Auto-open submenu if subitem is active
        const activeSubitems = document.querySelectorAll('.admin-nav-subitem.active');
        activeSubitems.forEach(function(subitem) {
            const submenu = subitem.closest('.admin-nav-submenu');
            const group = submenu ? submenu.closest('.admin-nav-group') : null;
            if (group) {
                group.classList.add('active');
                submenu.classList.add('open');
            }
        });

        // Add keyboard navigation
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                // Close all open submenus
                const openSubmenus = document.querySelectorAll('.admin-nav-submenu.open');
                openSubmenus.forEach(function(submenu) {
                    submenu.classList.remove('open');
                    const group = submenu.closest('.admin-nav-group');
                    if (group) group.classList.remove('active');
                });
            }
        });
    }

    // Toggle submenu function
    window.toggleSubmenu = function(button) {
        const group = button.closest('.admin-nav-group');
        const submenu = group.querySelector('.admin-nav-submenu');
        
        if (!group || !submenu) return;
        
        const isOpen = submenu.classList.contains('open');
        
        // Close other submenus (optional - for accordion behavior)
        const otherGroups = document.querySelectorAll('.admin-nav-group');
        otherGroups.forEach(function(otherGroup) {
            if (otherGroup !== group) {
                otherGroup.classList.remove('active');
                const otherSubmenu = otherGroup.querySelector('.admin-nav-submenu');
                if (otherSubmenu) otherSubmenu.classList.remove('open');
            }
        });
        
        // Toggle current submenu
        if (isOpen) {
            group.classList.remove('active');
            submenu.classList.remove('open');
        } else {
            group.classList.add('active');
            submenu.classList.add('open');
        }
    };

    /* ========== B2.4 CALCULATOR DRAWER ========== */

    function initCalculator() {
        // Calculator functionality can be added here
    }

    /* ========== B2.5 NOTIFICATION BELL ========== */

    function initNotifications() {
        // Notification functionality can be added here
    }

    /* ========== INITIALIZATION ========== */

    function init() {
        initSearch();
        initMobileSidebar();
        initSubmenuNavigation();
        initCalculator();
        initNotifications();
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
