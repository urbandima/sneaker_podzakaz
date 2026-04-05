/**
 * APP.JS — Глобальные функции Sneakerhead
 * Поиск, мобильное меню, корзина/избранное счётчики
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
    document.body.style.overflow = 'hidden';
}

function closeSearch() {
    var modal = document.getElementById('searchModal');
    if (!modal) return;
    modal.classList.remove('open');
    document.body.style.overflow = '';
}

/** Debounce для поиска */
var _searchTimer = null;

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

    clearTimeout(_searchTimer);
    _searchTimer = setTimeout(function () {
        _doSearch(query, resultsContainer);
    }, 300);
}

function _doSearch(query, container) {
    container.innerHTML =
        '<div class="search-loading"><div class="spinner"></div><p>Поиск товаров...</p></div>';

    fetch('/catalog/search?q=' + encodeURIComponent(query), {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
        .then(function (r) { return r.json(); })
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

// Мобильное меню каталога (с кнопкой фильтра)
document.addEventListener('DOMContentLoaded', function () {
    const filterBtn = document.querySelector('.btn-filter-mobile');
    const sidebar = document.querySelector('.sidebar');
    const closeBtn = document.querySelector('.sidebar-header .close-btn');

    if (filterBtn && sidebar) {
        filterBtn.addEventListener('click', function (e) {
            e.preventDefault();
            sidebar.classList.add('active');
            document.body.style.overflow = 'hidden';
        });
    }

    if (closeBtn && sidebar) {
        closeBtn.addEventListener('click', function (e) {
            e.preventDefault();
            sidebar.classList.remove('active');
            document.body.style.overflow = '';
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
        // Закрываем
        sidebar.classList.remove('active');
        overlay.classList.remove('active');
        sidebar.setAttribute('aria-hidden', 'true');
        if (toggleBtn) toggleBtn.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = '';
    } else {
        // Открываем
        sidebar.classList.add('active');
        overlay.classList.add('active');
        sidebar.setAttribute('aria-hidden', 'false');
        if (toggleBtn) toggleBtn.setAttribute('aria-expanded', 'true');
        document.body.style.overflow = 'hidden';
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

