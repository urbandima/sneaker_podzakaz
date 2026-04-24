/**
 * Admin Table Utilities
 * Shared JS for all admin list pages: column selector, client-side sort, filter row toggle.
 *
 * Usage:
 *   1. Include this script on any admin list page.
 *   2. Call AdminTable.init({ pageKey: 'uniquePageName', tableId: 'myTable' }) after DOM ready.
 *
 * Markup conventions (match the order/index.php gold standard):
 *   - Column selector wrapper:  .col-selector-wrap  >  button[data-col-toggle]  +  .col-selector-dropdown
 *   - Hideable columns:         th[data-col="key"] / td[data-col="key"]
 *   - Sortable headers:         th[data-sort="field"]   (clicks trigger URL sort via ?sort=field)
 *   - Expandable filter row 2:  #filterRow2  toggled by  button#filterExpandBtn
 */
var AdminTable = (function () {
    'use strict';

    var _cfg = {
        pageKey: 'adminTable',
        tableId: 'dataTable',
    };

    /* ───── Column Selector ───── */

    function _storageKey() {
        return 'adminCols_' + _cfg.pageKey;
    }

    function _getSavedCols() {
        try { return JSON.parse(localStorage.getItem(_storageKey()) || '{}'); } catch (e) { return {}; }
    }

    function _saveCols(map) {
        try { localStorage.setItem(_storageKey(), JSON.stringify(map)); } catch (e) {}
    }

    function toggleColumn(col, show) {
        var els = document.querySelectorAll('[data-col="' + col + '"]');
        for (var i = 0; i < els.length; i++) {
            els[i].style.display = show ? '' : 'none';
        }
        var saved = _getSavedCols();
        saved[col] = show;
        _saveCols(saved);
    }

    function selectAllCols(show) {
        var checkboxes = document.querySelectorAll('.col-selector-dropdown input[type=checkbox]');
        for (var i = 0; i < checkboxes.length; i++) {
            checkboxes[i].checked = show;
            toggleColumn(checkboxes[i].dataset.col, show);
        }
    }

    function toggleColSelector(e) {
        if (e) e.stopPropagation();
        var d = document.querySelector('.col-selector-dropdown');
        if (d) d.style.display = d.style.display === 'none' ? 'block' : 'none';
    }

    function _restoreColumns() {
        var saved = _getSavedCols();
        Object.keys(saved).forEach(function (col) {
            if (saved[col] === false) {
                var cb = document.querySelector('.col-selector-dropdown input[data-col="' + col + '"]');
                if (cb) { cb.checked = false; }
                toggleColumn(col, false);
            }
        });
    }

    function _bindColSelectorClose() {
        document.addEventListener('click', function (e) {
            var w = document.querySelector('.col-selector-wrap');
            if (w && !w.contains(e.target)) {
                var d = document.querySelector('.col-selector-dropdown');
                if (d) d.style.display = 'none';
            }
        });
    }

    /* ───── Sortable Columns (server-side via URL) ───── */

    function sortBy(col) {
        var url = new URL(window.location.href);
        var cur = url.searchParams.get('sort') || '';
        url.searchParams.set('sort', cur === col ? ('-' + col) : col);
        url.searchParams.delete('page');
        window.location.href = url.toString();
    }

    function _bindSortHeaders() {
        var table = document.getElementById(_cfg.tableId);
        if (!table) return;
        var headers = table.querySelectorAll('th[data-sort]');
        for (var i = 0; i < headers.length; i++) {
            (function (th) {
                th.style.cursor = 'pointer';
                th.style.userSelect = 'none';
                th.addEventListener('click', function () {
                    sortBy(th.dataset.sort);
                });
            })(headers[i]);
        }
    }

    /* ───── Sort icon helper (call from PHP) ───── */
    // Use AdminTable.sortIcon(col) in PHP templates — returns HTML string
    // (not used at runtime; kept for reference — PHP generates icons server-side)

    /* ───── Expandable Filter Row 2 ───── */

    function toggleFilterRow2() {
        var r2  = document.getElementById('filterRow2');
        var btn = document.getElementById('filterExpandBtn');
        var row1 = document.querySelector('.filter-row1');
        if (!r2) return;
        var open = r2.style.display !== 'none';
        r2.style.display = open ? 'none' : 'flex';
        if (btn) btn.classList.toggle('is-active', !open);
        if (row1) row1.classList.toggle('has-row2', !open);
    }

    function _initFilterRow2() {
        var r2 = document.getElementById('filterRow2');
        if (r2 && r2.style.display !== 'none') {
            var btn = document.getElementById('filterExpandBtn');
            if (btn) btn.classList.add('is-active');
            var row1 = document.querySelector('.filter-row1');
            if (row1) row1.classList.add('has-row2');
        }
    }

    /* ───── Row click navigation ───── */

    function _bindRowClick() {
        var table = document.getElementById(_cfg.tableId);
        if (!table) return;
        var tbody = table.querySelector('tbody');
        if (!tbody) return;
        tbody.addEventListener('click', function (e) {
            if (e.target.closest('a, button, input, select, label, [onclick]')) return;
            var row = e.target.closest('tr');
            if (!row) return;
            var link = row.querySelector('a[href]');
            if (link && link.href) window.location.href = link.href;
        });
    }

    /* ───── Init ───── */

    function init(opts) {
        if (opts) {
            if (opts.pageKey) _cfg.pageKey = opts.pageKey;
            if (opts.tableId) _cfg.tableId = opts.tableId;
        }
        _restoreColumns();
        _bindColSelectorClose();
        _bindSortHeaders();
        _initFilterRow2();
        _bindRowClick();
    }

    /* ───── Public API ───── */
    return {
        init: init,
        toggleColumn: toggleColumn,
        selectAllCols: selectAllCols,
        toggleColSelector: toggleColSelector,
        toggleFilterRow2: toggleFilterRow2,
        sortBy: sortBy,
    };
})();
