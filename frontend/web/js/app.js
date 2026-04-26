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
    if (input) { input.focus(); input.select(); }
    SH.lockScroll();
}

function closeSearch() {
    var modal = document.getElementById('searchModal');
    if (!modal) return;
    modal.classList.remove('open');
    SH.unlockScroll();
}

var _searchDebounced = SH.debounce(function (query) {
    var container = document.getElementById('searchResults');
    if (container) _doSearch(query, container);
}, 280);

/* New input handler (oninput) */
function handleSearchInput(value) {
    var query = value.trim();
    var container = document.getElementById('searchResults');
    if (!container) return;
    var hints = document.getElementById('searchHints');

    if (query.length < 2) {
        if (hints) hints.style.display = '';
        container.innerHTML = '';
        if (hints) container.appendChild(hints);
        return;
    }
    if (hints) hints.style.display = 'none';
    _searchDebounced(query);
}

/* Keyboard handler (onkeydown) */
function handleSearchKey(event) {
    if (event.key === 'Escape') { closeSearch(); return; }
    if (event.key === 'Enter') {
        var query = event.target.value.trim();
        if (query.length >= 2) {
            window.location.href = '/catalog?q=' + encodeURIComponent(query);
        }
    }
}

/* Legacy: keep backward compat */
function handleSearch(event) {
    if (event.key === 'Escape') { closeSearch(); return; }
    handleSearchInput(event.target ? event.target.value : '');
}

function _doSearch(query, container) {
    container.innerHTML =
        '<div class="search-loading"><div class="spinner"></div><span>Ищем...</span></div>';

    SH.fetch('/catalog/search?q=' + encodeURIComponent(query))
        .then(function (data) {
            if (data.results && data.results.length > 0) {
                var items = data.results.slice(0, 8).map(function (p) {
                    var img = p.mainImage || p.image || '';
                    var brand = (p.brand && p.brand.name) ? p.brand.name : (p.brand_name || '');
                    var oldPriceHtml = p.oldPrice
                        ? '<span class="search-result-old-price">' + p.oldPrice + ' BYN</span>'
                        : '';
                    var discountHtml = p.discount
                        ? '<span class="search-result-discount">-' + p.discount + '%</span>'
                        : '';
                    return '<a href="' + p.url + '" class="search-result-item" onclick="closeSearch()">' +
                        (img ? '<img src="' + img + '" alt="' + SH.escapeHtml(p.name) + '" loading="lazy">' : '<span class="search-result-no-img"><i class="bi bi-image"></i></span>') +
                        '<div class="search-result-info">' +
                            (brand ? '<div class="search-result-brand">' + SH.escapeHtml(brand) + '</div>' : '') +
                            '<h4>' + SH.escapeHtml(p.name) + '</h4>' +
                        '</div>' +
                        '<div>' +
                            '<span class="search-result-price">' + p.price + ' BYN</span>' +
                            oldPriceHtml +
                            discountHtml +
                        '</div>' +
                    '</a>';
                }).join('');

                container.innerHTML = items +
                    '<a href="/catalog?q=' + encodeURIComponent(query) + '" class="search-view-all" onclick="closeSearch()">' +
                    '<i class="bi bi-search"></i> Смотреть все результаты по «' + SH.escapeHtml(query) + '»' +
                    '</a>';
            } else {
                container.innerHTML =
                    '<div class="search-empty">' +
                    '<i class="bi bi-search" style="font-size:2rem;margin-bottom:0.5rem;display:block"></i>' +
                    '<p style="margin-bottom:0.75rem">Ничего не найдено по «' + SH.escapeHtml(query) + '»</p>' +
                    '<a href="/catalog" onclick="closeSearch()" style="font-size:13px;color:inherit;text-decoration:underline">Посмотреть весь каталог</a>' +
                    '</div>';
            }
        })
        .catch(function () {
            container.innerHTML =
                '<div class="search-error"><p>Ошибка соединения. Попробуйте позже.</p></div>';
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

    /* Keyboard shortcut: / or Ctrl+K opens search */
    document.addEventListener('keydown', function (e) {
        var tag = document.activeElement ? document.activeElement.tagName : '';
        if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT') return;
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') { e.preventDefault(); openSearch(); }
    });
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
