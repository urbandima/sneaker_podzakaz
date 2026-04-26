/**
 * Admin Panel JS — СНИКЕРХЭД
 * Block 2: Global Search (Ctrl+K), Calculator Drawer, Notification Bell
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

        // Группируем по типу
        var groups = {};
        results.forEach(function (r) {
            var type = r.type || 'other';
            if (!groups[type]) groups[type] = [];
            groups[type].push(r);
        });

        var groupLabels = {
            order: '<i class="bi bi-bag-check-fill"></i> Заказы',
            product: '<i class="bi bi-box"></i> Товары',
            user: '<i class="bi bi-person-fill"></i> Пользователи',
            other: '<i class="bi bi-search"></i> Прочее',
        };

        var html = '';
        Object.keys(groups).forEach(function (type) {
            html += '<div class="admin-search-group">';
            html += '<div class="admin-search-group-label">' + (groupLabels[type] || type) + '</div>';
            groups[type].forEach(function (item) {
                var url = item.url;
                if (Array.isArray(url) || (typeof url === 'object' && url !== null)) {
                    // Yii array URL — конвертируем в строку
                    url = urlFromYiiArray(url);
                }
                html += '<a class="admin-search-result-item" href="' + escHtml(url || '#') + '">';
                html += '<i class="' + escHtml(item.icon || 'bi bi-circle') + '"></i>';
                html += '<div class="admin-search-result-body">';
                html += '<span class="admin-search-result-title">' + escHtml(item.title || '') + '</span>';
                if (item.description) {
                    html += '<span class="admin-search-result-desc">' + escHtml(item.description) + '</span>';
                }
                html += '</div>';
                if (item.status) {
                    html += '<span class="admin-badge admin-badge-neutral admin-search-result-status">' + escHtml(item.status) + '</span>';
                }
                html += '</a>';
            });
            html += '</div>';
        });

        searchResults.innerHTML = html;

        // Клик по результату — переход и закрытие
        searchResults.querySelectorAll('.admin-search-result-item').forEach(function (el) {
            el.addEventListener('click', function () {
                closeSearch();
            });
        });
    }

    // Простейшая конвертация Yii-style массива URL ['/route', 'id' => 1] -> '/route?id=1'
    function urlFromYiiArray(arr) {
        if (typeof arr === 'string') return arr;
        if (!arr) return '#';
        if (Array.isArray(arr)) {
            var route = arr[0] || '';
            var params = arr.slice(1);
            var qs = params.length ? '?' + params.join('&') : '';
            return route + qs;
        }
        var route = arr[0] || '';
        var parts = [];
        Object.keys(arr).forEach(function (k) {
            if (k !== '0') parts.push(encodeURIComponent(k) + '=' + encodeURIComponent(arr[k]));
        });
        return route + (parts.length ? '?' + parts.join('&') : '');
    }

    function escHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    /* ========== B2.2 NOTIFICATION BELL ========== */

    var notifBadge = null;
    var notifPollInterval = null;

    function initNotifications() {
        notifBadge = document.getElementById('notif-badge');
        if (!notifBadge) return;

        fetchNotifications();
        notifPollInterval = setInterval(fetchNotifications, 30000);
    }

    function fetchNotifications() {
        var xhr = new XMLHttpRequest();
        xhr.open('GET', '/admin/order/notifications', true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.onload = function () {
            if (xhr.status !== 200) return;
            try {
                var data = JSON.parse(xhr.responseText);
                var count = data.unread_count || data.count || 0;
                if (notifBadge) {
                    notifBadge.textContent = count > 0 ? (count > 99 ? '99+' : count) : '';
                    notifBadge.style.display = count > 0 ? 'inline-flex' : 'none';
                }
            } catch (e) { /* ignore */ }
        };
        xhr.send();
    }

    /* ========== B2.3 CALCULATOR DRAWER ========== */

    var calcDrawer = null;
    var calcOverlay = null;
    var cnyRateCached = null;

    function initCalcDrawer() {
        calcDrawer = document.getElementById('calc-drawer');
        calcOverlay = document.getElementById('calc-overlay');
        if (!calcDrawer) return;

        // Открытие кнопкой
        var openBtn = document.getElementById('calc-open-btn');
        if (openBtn) {
            openBtn.addEventListener('click', openCalc);
        }

        // Закрытие
        var closeBtn = document.getElementById('calc-close-btn');
        if (closeBtn) {
            closeBtn.addEventListener('click', closeCalc);
        }
        if (calcOverlay) {
            calcOverlay.addEventListener('click', closeCalc);
        }

        // Автоматический расчёт при изменении полей
        ['calc-cny', 'calc-markup', 'calc-weight'].forEach(function (id) {
            var el = document.getElementById(id);
            if (el) el.addEventListener('input', calcTotal);
        });

        // Кнопка "Обновить курс"
        var refreshRateBtn = document.getElementById('calc-refresh-rate');
        if (refreshRateBtn) {
            refreshRateBtn.addEventListener('click', function () {
                cnyRateCached = null;
                localStorage.removeItem('cny_rate');
                loadCnyRate();
            });
        }

        // Кнопка "Создать заказ"
        var createOrderBtn = document.getElementById('calc-create-order');
        if (createOrderBtn) {
            createOrderBtn.addEventListener('click', function (e) {
                e.preventDefault();
                var cny = parseFloat(document.getElementById('calc-cny').value) || 0;
                var markup = parseFloat(document.getElementById('calc-markup').value) || 0;
                var weight = parseFloat(document.getElementById('calc-weight').value) || 0;
                var total = parseFloat(document.getElementById('calc-total').value) || 0;
                var url = '/admin/order/create?cny=' + cny + '&markup=' + markup + '&weight=' + weight + '&total=' + total;
                window.location.href = url;
            });
        }

        loadCnyRate();
    }

    function openCalc() {
        if (!calcDrawer) return;
        calcDrawer.classList.add('open');
        if (calcOverlay) calcOverlay.style.display = 'block';
        calcTotal();
    }

    function closeCalc() {
        if (!calcDrawer) return;
        calcDrawer.classList.remove('open');
        if (calcOverlay) calcOverlay.style.display = 'none';
    }

    function loadCnyRate() {
        // Сначала из localStorage
        var stored = localStorage.getItem('cny_rate');
        var storedTime = parseInt(localStorage.getItem('cny_rate_time') || '0', 10);
        var now = Math.floor(Date.now() / 1000);

        if (stored && storedTime && (now - storedTime) < 3600) {
            cnyRateCached = parseFloat(stored);
            updateRateDisplay(cnyRateCached, new Date(storedTime * 1000));
            calcTotal();
            return;
        }

        // Иначе — AJAX запрос
        var xhr = new XMLHttpRequest();
        xhr.open('GET', '/admin/dashboard/cny-rate', true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.onload = function () {
            if (xhr.status !== 200) return;
            try {
                var data = JSON.parse(xhr.responseText);
                var rate = parseFloat(data.rate) || 0.45;
                var updatedAt = data.updated_at ? new Date(data.updated_at) : new Date();
                cnyRateCached = rate;
                localStorage.setItem('cny_rate', rate);
                localStorage.setItem('cny_rate_time', Math.floor(Date.now() / 1000));
                updateRateDisplay(rate, updatedAt);
                calcTotal();
            } catch (e) {
                cnyRateCached = 0.45;
                updateRateDisplay(0.45, null);
                calcTotal();
            }
        };
        xhr.onerror = function () {
            cnyRateCached = 0.45;
            updateRateDisplay(0.45, null);
            calcTotal();
        };
        xhr.send();
    }

    function updateRateDisplay(rate, updatedAt) {
        var rateEl = document.getElementById('calc-rate-display');
        if (rateEl) {
            var dateStr = updatedAt ? updatedAt.toLocaleDateString('ru-RU', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '—';
            rateEl.innerHTML = '<span>1 CNY = <b>' + rate.toFixed(4) + '</b> BYN</span><small style="color:var(--admin-text-secondary)">обновлено ' + dateStr + '</small>';
        }
    }

    function calcTotal() {
        var cny = parseFloat(document.getElementById('calc-cny') && document.getElementById('calc-cny').value) || 0;
        var markup = parseFloat(document.getElementById('calc-markup') && document.getElementById('calc-markup').value) || 0;
        var weight = parseFloat(document.getElementById('calc-weight') && document.getElementById('calc-weight').value) || 0;
        var rate = cnyRateCached || 0.45;

        // Расчёт: (CNY * rate) * (1 + markup/100) + weight * 5 BYN (условный тариф доставки)
        var priceInByn = cny * rate;
        var withMarkup = priceInByn * (1 + markup / 100);
        var deliveryCost = weight * 5;
        var total = withMarkup + deliveryCost;

        var totalEl = document.getElementById('calc-total');
        if (totalEl) {
            totalEl.value = total > 0 ? total.toFixed(2) : '';
        }

        // Подсказка с разбивкой
        var calcBreakdown = document.getElementById('calc-breakdown');
        if (calcBreakdown && cny > 0) {
            calcBreakdown.innerHTML =
                '<div>' + cny.toFixed(2) + ' CNY × ' + rate.toFixed(4) + ' = ' + priceInByn.toFixed(2) + ' BYN</div>' +
                '<div>+ наценка ' + markup + '% = ' + (withMarkup - priceInByn).toFixed(2) + ' BYN</div>' +
                (weight > 0 ? '<div>+ доставка ' + weight + ' кг × 5 = ' + deliveryCost.toFixed(2) + ' BYN</div>' : '') +
                '<div><b>Итого: ' + total.toFixed(2) + ' BYN</b></div>';
            calcBreakdown.style.display = 'block';
        } else if (calcBreakdown) {
            calcBreakdown.style.display = 'none';
        }
    }

    /* ========== THEME TOGGLE ========== */

    function initThemeToggle() {
        var btn = document.getElementById('theme-toggle');
        var icon = document.getElementById('theme-icon');
        if (!btn) return;

        var saved = localStorage.getItem('admin-theme') || 'light';
        applyTheme(saved, icon);

        btn.addEventListener('click', function () {
            var current = document.documentElement.getAttribute('data-theme') || 'light';
            var next = current === 'light' ? 'dark' : 'light';
            localStorage.setItem('admin-theme', next);
            document.documentElement.setAttribute('data-theme', next);
            applyTheme(next, icon);
        });

        document.addEventListener('keydown', function (e) {
            if (e.ctrlKey && e.key === 'd') {
                e.preventDefault();
                btn.click();
            }
        });
    }

    function applyTheme(theme, icon) {
        document.documentElement.setAttribute('data-theme', theme);
        if (icon) {
            icon.className = theme === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-fill';
        }
    }

    /* ========== INIT ========== */

    function init() {
        initSearch();
        initNotifications();
        initCalcDrawer();
        initThemeToggle();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();

/* ========== TOPBAR DROPDOWNS (extracted from admin layout) ========== */

function toggleNotifications() {
    var dropdown = document.getElementById('notif-dropdown');
    if (dropdown) {
        dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
    }
}

function toggleProfile() {
    var dropdown = document.getElementById('profile-dropdown');
    if (dropdown) {
        dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
    }
}

document.addEventListener('click', function (e) {
    var notifBtn      = document.getElementById('notif-btn');
    var notifDropdown = document.getElementById('notif-dropdown');
    var profileBtn    = document.getElementById('profile-btn');
    var profileDropdown = document.getElementById('profile-dropdown');

    if (notifBtn && notifDropdown && !notifBtn.contains(e.target) && !notifDropdown.contains(e.target)) {
        notifDropdown.style.display = 'none';
    }
    if (profileBtn && profileDropdown && !profileBtn.contains(e.target) && !profileDropdown.contains(e.target)) {
        profileDropdown.style.display = 'none';
    }
});
