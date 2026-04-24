/**
 * admin-table-utils.js
 * Shared utilities for admin list pages: column selector (localStorage),
 * click-to-sort (URL param), expandable filter row 2.
 * Injects shared CSS once on first load.
 */
(function (global) {
    'use strict';

    /* ─── inject shared CSS ─── */
    (function () {
        if (document.getElementById('atu-css')) return;
        var s = document.createElement('style');
        s.id = 'atu-css';
        s.textContent =
            /* compact filter bar */
            '.filter-wrap{margin-bottom:14px}' +
            '.compact-filter-bar{display:flex;gap:8px;align-items:center;flex-wrap:wrap;padding:10px 16px;background:var(--admin-surface,#fff)}' +
            '.filter-row1{border-radius:12px;box-shadow:0 1px 4px rgba(0,0,0,.06)}' +
            '.filter-row1.has-row2{border-radius:12px 12px 0 0}' +
            '.filter-row2{border-radius:0 0 12px 12px;border-top:1px solid var(--admin-border,#e1e3e5);box-shadow:0 2px 4px rgba(0,0,0,.05)}' +
            '.compact-filter-input,.compact-filter-select{height:32px;border:1.5px solid var(--admin-border,#e1e3e5);border-radius:8px;padding:0 10px;font-size:.8125rem;color:var(--admin-text-primary,#202223);background:var(--admin-surface,#fff);outline:none;transition:border-color .15s;box-sizing:border-box}' +
            '.compact-filter-input:focus,.compact-filter-select:focus{border-color:var(--admin-primary,#202223)}' +
            '.compact-filter-input{flex:1;min-width:160px;max-width:240px}' +
            '.compact-filter-input[type=date]{flex:0 0 auto;min-width:130px;max-width:150px}' +
            '.compact-filter-input[type=number]{flex:0 0 auto;min-width:88px;max-width:110px}' +
            '.compact-filter-select{flex:0 0 auto;min-width:110px}' +
            '.compact-filter-btn{height:32px;padding:0 12px;border-radius:8px;font-size:.8125rem;font-weight:600;cursor:pointer;border:none;text-decoration:none;display:inline-flex;align-items:center;gap:5px;transition:all .15s;white-space:nowrap;background:var(--admin-surface-hover,#f3f4f6);color:var(--admin-text-secondary,#6d7175)}' +
            '.compact-filter-btn:hover{background:#e5e7eb;color:#374151;text-decoration:none}' +
            '.compact-filter-btn--apply{background:var(--admin-primary,#202223);color:#fff}' +
            '.compact-filter-btn--apply:hover{background:var(--admin-primary-hover,#2c2e2f);color:#fff}' +
            '.compact-filter-btn--reset:hover{background:#fee2e2;color:#dc2626}' +
            '.compact-filter-btn--expand.is-active{background:#eff6ff;color:var(--admin-info,#0078d4);border:1.5px solid #bfdbfe}' +
            /* column selector */
            '.col-selector-wrap{position:relative;flex-shrink:0}' +
            '.col-selector-dropdown{position:absolute;right:0;top:calc(100% + 6px);background:var(--admin-surface,#fff);border:1.5px solid var(--admin-border,#e1e3e5);border-radius:10px;padding:12px;z-index:300;min-width:190px;max-height:370px;overflow-y:auto;box-shadow:0 8px 24px rgba(0,0,0,.12)}' +
            '.col-selector-item{display:flex;align-items:center;gap:8px;padding:4px 2px;font-size:.8125rem;cursor:pointer;color:var(--admin-text-primary,#202223);user-select:none;border-radius:4px}' +
            '.col-selector-item:hover{background:var(--admin-surface-hover,#f3f4f6)}' +
            '.col-selector-item input{width:15px;height:15px;cursor:pointer;accent-color:var(--admin-primary,#202223);flex-shrink:0}' +
            /* status pill */
            '.status-pill{display:inline-block;padding:3px 9px;border-radius:20px;font-size:11px;font-weight:700;white-space:nowrap;line-height:1.6}' +
            /* sortable headers */
            'th[data-sort]{cursor:pointer;user-select:none;white-space:nowrap}' +
            'th[data-sort]:hover{background:var(--admin-surface-hover,#fafbfc)!important}' +
            /* sticky thead */
            '.admin-table thead th{position:sticky;top:0;z-index:10;background:var(--admin-surface,#fff);box-shadow:0 1px 0 var(--admin-border,#e1e3e5)}' +
            /* hover rows */
            '.admin-table tbody tr{transition:background .1s}' +
            '.admin-table tbody tr.clickable-row{cursor:pointer}' +
            '.admin-table tbody tr:hover{background:var(--admin-surface-hover,#fafbfc)!important}' +
            /* kpi bar for finance stat cards */
            '.atu-kpi-bar{display:flex;gap:12px;flex-wrap:wrap;margin-bottom:14px}' +
            '.atu-kpi-card{flex:1;min-width:140px;background:var(--admin-surface,#fff);border:1px solid var(--admin-border,#e1e3e5);border-radius:10px;padding:12px 16px;box-shadow:0 1px 3px rgba(0,0,0,.05)}' +
            '.atu-kpi-label{font-size:.75rem;font-weight:500;color:var(--admin-text-secondary,#6d7175);text-transform:uppercase;letter-spacing:.03em;margin-bottom:4px}' +
            '.atu-kpi-value{font-size:1.375rem;font-weight:700;color:var(--admin-text-primary,#202223)}' +
            '.atu-kpi-card--green .atu-kpi-value{color:var(--admin-success,#008060)}' +
            '.atu-kpi-card--yellow .atu-kpi-value{color:var(--admin-warning,#ffa500)}' +
            '.atu-kpi-card--red .atu-kpi-value{color:var(--admin-danger,#d72c0d)}' +
            '.atu-kpi-card--blue .atu-kpi-value{color:var(--admin-info,#0078d4)}' +
            /* tab pills (for margin.php) */
            '.atu-tab-pills{display:flex;gap:4px;background:var(--admin-surface-hover,#f3f4f6);padding:4px;border-radius:10px;width:fit-content}' +
            '.atu-tab-pill{padding:5px 14px;border-radius:7px;font-size:.8125rem;font-weight:600;cursor:pointer;text-decoration:none;color:var(--admin-text-secondary,#6d7175);transition:all .15s}' +
            '.atu-tab-pill:hover{color:var(--admin-text-primary,#202223);text-decoration:none}' +
            '.atu-tab-pill--active{background:var(--admin-surface,#fff);color:var(--admin-text-primary,#202223);box-shadow:0 1px 3px rgba(0,0,0,.1)}';
        document.head.appendChild(s);
    })();

    /* ─── sort by URL param ─── */
    function sortBy(col) {
        var url = new URL(window.location.href);
        var cur = url.searchParams.get('sort') || '';
        url.searchParams.set('sort', cur === col ? ('-' + col) : col);
        url.searchParams.delete('page');
        window.location.href = url.toString();
    }

    /* ─── column selector ─── */
    function initColSelector(storageKey) {
        var saved = {};
        try { saved = JSON.parse(localStorage.getItem(storageKey) || '{}'); } catch (e) {}
        Object.keys(saved).forEach(function (col) {
            if (saved[col] === false) {
                _hideCols(col, false);
                var cb = document.querySelector('.col-selector-dropdown input[data-col="' + col + '"]');
                if (cb) cb.checked = false;
            }
        });
    }

    function toggleColumn(col, show, storageKey) {
        _hideCols(col, show);
        var saved = {};
        try { saved = JSON.parse(localStorage.getItem(storageKey) || '{}'); } catch (e) {}
        saved[col] = show;
        localStorage.setItem(storageKey, JSON.stringify(saved));
    }

    function _hideCols(col, show) {
        document.querySelectorAll('[data-col="' + col + '"]').forEach(function (el) {
            el.style.display = show ? '' : 'none';
        });
    }

    function toggleColSelector(e) {
        e.stopPropagation();
        var d = document.getElementById('colSelector');
        if (d) d.style.display = d.style.display === 'none' ? 'block' : 'none';
    }

    function selectAllCols(show, storageKey) {
        document.querySelectorAll('#colSelector input[type=checkbox]').forEach(function (cb) {
            cb.checked = show;
            toggleColumn(cb.dataset.col, show, storageKey);
        });
    }

    /* close col selector on outside click */
    document.addEventListener('click', function (e) {
        var w = document.querySelector('.col-selector-wrap');
        if (w && !w.contains(e.target)) {
            var d = document.getElementById('colSelector');
            if (d) d.style.display = 'none';
        }
    });

    /* ─── filter row 2 expand/collapse ─── */
    function toggleFilterRow2() {
        var r2   = document.getElementById('filterRow2');
        var btn  = document.getElementById('filterExpandBtn');
        var row1 = document.querySelector('.filter-row1');
        if (!r2) return;
        var open = r2.style.display !== 'none';
        r2.style.display = open ? 'none' : 'flex';
        if (btn)  btn.classList.toggle('is-active', !open);
        if (row1) row1.classList.toggle('has-row2', !open);
    }

    /* ─── expose ─── */
    global.AdminTable = {
        sortBy:            sortBy,
        initColSelector:   initColSelector,
        toggleColumn:      toggleColumn,
        toggleColSelector: toggleColSelector,
        selectAllCols:     selectAllCols,
        toggleFilterRow2:  toggleFilterRow2,
    };

})(window);
