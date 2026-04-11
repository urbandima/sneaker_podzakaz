/**
 * APP.JS — Глобальные функции Sneakerhead
 * Поиск, мобильное меню, корзина/избранное счётчики.
 * Uses SH.* utilities from utils.js
 */

/* ============================================
   HEADER SCROLL EFFECT + ACTIVE NAV
   ============================================ */
(function () {
    var header = document.querySelector('.main-header');
    if (header) {
        window.addEventListener('scroll', function () {
            var scrollY = window.scrollY || window.pageYOffset;
            header.classList.toggle('scrolled', scrollY > 20);
        }, { passive: true });
    }

    // Подсветка активного пункта навигации
    var currentPath = window.location.pathname;
    document.querySelectorAll('.nav-menu a').forEach(function (link) {
        var href = link.getAttribute('href');
        if (href && currentPath.indexOf(href) === 0 && href !== '/') {
            link.classList.add('active');
        }
    });
})();

/* ============================================
   SEARCH MODAL
   ============================================ */

function openSearch() {
    var modal = document.getElementById('searchModal');
    if (!modal) return;
    modal.classList.add('open');
    var input = document.getElementById('searchInput');
    if (input) input.focus();
    SH.lockScroll();
}

function closeSearch() {
    var modal = document.getElementById('searchModal');
    if (!modal) return;
    modal.classList.remove('open');
    SH.unlockScroll();
}

var _handleSearchDebounced = SH.debounce(function (query, container) {
    _doSearch(query, container);
}, 300);

function handleSearch(event) {
    if (event.key === 'Escape') {
        closeSearch();
        return;
    }

    var query = event.target.value.trim();
    var resultsContainer = document.getElementById('searchResults');
    if (!resultsContainer) return;

    if (query.length < 2) {
        resultsContainer.innerHTML = '';
        return;
    }

    _handleSearchDebounced(query, resultsContainer);
}

function _doSearch(query, container) {
    container.innerHTML =
        '<div class="search-loading"><div class="spinner"></div><p>Поиск товаров...</p></div>';

    SH.fetch('/catalog/search?q=' + encodeURIComponent(query))
        .then(function (data) {
            if (data.results && data.results.length > 0) {
                var html = data.results.map(function (p) {
                    return '<a href="' + p.url + '" class="search-result-item">' +
                        '<img src="' + p.mainImage + '" alt="' + p.name + '" loading="lazy">' +
                        '<div class="search-result-info">' +
                        '<h4>' + p.name + '</h4>' +
                        '<p class="search-result-brand">' + p.brand.name + '</p>' +
                        '<p class="search-result-price">' + p.price + ' BYN' +
                        (p.oldPrice ? '<span class="search-result-old-price">' + p.oldPrice + ' BYN</span>' : '') +
                        '</p></div></a>';
                }).join('');

                container.innerHTML =
                    '<div class="search-results-list">' + html +
                    '<a href="/catalog?q=' + encodeURIComponent(query) + '" class="search-view-all">Посмотреть все результаты</a>' +
                    '</div>';
            } else {
                container.innerHTML =
                    '<div class="search-empty"><p>По запросу "' + query + '" ничего не найдено</p>' +
                    '<a href="/catalog?q=' + encodeURIComponent(query) + '" class="btn btn-primary">Посмотреть все товары</a></div>';
            }
        })
        .catch(function () {
            container.innerHTML =
                '<div class="search-error"><p>Ошибка при поиске. Попробуйте позже.</p></div>';
        });
}

/* Close search on backdrop click */
document.addEventListener('DOMContentLoaded', function () {
    var searchModal = document.getElementById('searchModal');
    if (searchModal) {
        searchModal.addEventListener('click', function (e) {
            if (e.target.id === 'searchModal') closeSearch();
        });
    }
});

/* ============================================
   SIDEBAR MENU (Боковое меню в шапке)
   ============================================ */

function toggleSidebarMenu() {
    var sidebar = document.getElementById('sidebarMenu');
    var overlay = document.getElementById('sidebarMenuOverlay');
    var toggleBtn = document.querySelector('.sidebar-menu-toggle');

    if (!sidebar || !overlay) return;

    var isActive = sidebar.classList.contains('active');

    if (isActive) {
        SH.closeModal(sidebar);
        SH.closeModal(overlay);
        sidebar.setAttribute('aria-hidden', 'true');
        if (toggleBtn) toggleBtn.setAttribute('aria-expanded', 'false');
    } else {
        SH.openModal(sidebar);
        SH.openModal(overlay);
        sidebar.setAttribute('aria-hidden', 'false');
        if (toggleBtn) toggleBtn.setAttribute('aria-expanded', 'true');
    }
}

// Закрытие по ESC
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        var sidebar = document.getElementById('sidebarMenu');
        if (sidebar && sidebar.classList.contains('active')) {
            toggleSidebarMenu();
        }
    }
});
