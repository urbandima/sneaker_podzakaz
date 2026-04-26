/* admin-product-view.js — product card interactions */
'use strict';

/* ── P9: Close more-dropdown on outside click ─────────────────────── */
document.addEventListener('click', function (e) {
    var dd = document.getElementById('pv-more-dropdown');
    if (dd && !dd.closest('.pv-more-menu').contains(e.target)) {
        dd.classList.remove('open');
    }
});

/* ── P2: Inline price edit ─────────────────────────────────────────── */
function pvEditPrice() {
    document.getElementById('pv-price-display').style.display = 'none';
    var row = document.getElementById('pv-price-input-row');
    row.style.display = 'flex';
    document.getElementById('pv-price-input').focus();
    document.getElementById('pv-price-input').select();
}

function pvCancelPrice() {
    document.getElementById('pv-price-display').style.display = 'flex';
    document.getElementById('pv-price-input-row').style.display = 'none';
}

function pvSavePrice() {
    var input  = document.getElementById('pv-price-input');
    var saveBtn = document.getElementById('pv-price-save');
    var price  = parseFloat(input.value);
    if (isNaN(price) || price < 0) { input.classList.add('is-invalid'); return; }
    input.classList.remove('is-invalid');

    var origHtml = saveBtn.innerHTML;
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<i class="bi bi-hourglass-split"></i>';

    fetch(window.PRODUCT_VIEW.updatePriceUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.PRODUCT_VIEW.csrf },
        body: JSON.stringify({ id: window.PRODUCT_VIEW.id, price: price })
    })
    .then(function (r) { return r.json(); })
    .then(function (d) {
        if (d.success) {
            /* P12: "349.00 BYN" format */
            var fmt = price.toFixed(2) + ' BYN';
            document.getElementById('pv-price-value').textContent = fmt;
            saveBtn.innerHTML = '<i class="bi bi-check text-success"></i>';
            setTimeout(function () { pvCancelPrice(); saveBtn.disabled = false; saveBtn.innerHTML = origHtml; }, 900);
        } else {
            saveBtn.disabled = false;
            saveBtn.innerHTML = origHtml;
            alert(d.message || 'Ошибка сохранения цены');
        }
    })
    .catch(function () { saveBtn.disabled = false; saveBtn.innerHTML = origHtml; });
}

/* Enter key in price input */
document.addEventListener('DOMContentLoaded', function () {
    var inp = document.getElementById('pv-price-input');
    if (inp) {
        inp.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') pvSavePrice();
            if (e.key === 'Escape') pvCancelPrice();
        });
    }
});

/* ── Toggle active ─────────────────────────────────────────────────── */
function pvToggleActive() {
    var btn = document.getElementById('pv-toggle-btn');
    btn.disabled = true;
    fetch(window.PRODUCT_VIEW.toggleActiveUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.PRODUCT_VIEW.csrf },
        body: JSON.stringify({ id: window.PRODUCT_VIEW.id })
    })
    .then(function (r) { return r.json(); })
    .then(function (d) {
        btn.disabled = false;
        if (d.success) location.reload();
    })
    .catch(function () { btn.disabled = false; });
}

/* ── Sync Poizon ───────────────────────────────────────────────────── */
function pvSyncPoizon() {
    var btn = event.currentTarget;
    btn.disabled = true;
    btn.textContent = 'Синхронизация...';
    fetch('/admin/product/sync-poizon', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.PRODUCT_VIEW.csrf },
        body: JSON.stringify({ id: window.PRODUCT_VIEW.id })
    })
    .then(function (r) { return r.json(); })
    .then(function (d) {
        if (d.success) location.reload();
        else { btn.disabled = false; btn.textContent = 'Синхронизировать'; alert(d.message || 'Ошибка'); }
    })
    .catch(function () { btn.disabled = false; btn.textContent = 'Синхронизировать'; });
}

/* ── P11: Media tab switching ──────────────────────────────────────── */
function pvTab(btn, tab) {
    document.querySelectorAll('.pv-tab').forEach(function (t) { t.classList.remove('active'); });
    btn.classList.add('active');
    document.querySelectorAll('#pv-media-grid .pv-media-item').forEach(function (item) {
        if (tab === 'all') {
            item.style.display = '';
        } else {
            item.style.display = item.dataset.source === tab ? '' : 'none';
        }
    });
}

/* ── P17: Toggle UUID columns ──────────────────────────────────────── */
var _uuidVisible = false;
function pvToggleUUID() {
    _uuidVisible = !_uuidVisible;
    document.querySelectorAll('.pv-uuid-col').forEach(function (el) {
        el.style.display = _uuidVisible ? '' : 'none';
    });
}

/* ── P20: Inline-editable main info fields ─────────────────────────── */
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.pv-editable').forEach(function (el) {
        el.addEventListener('click', function () {
            if (el.querySelector('input')) return; // already editing
            var field = el.dataset.field;
            var type  = el.dataset.type || 'text';
            var val   = el.dataset.value || el.textContent.trim();
            var orig  = el.textContent.trim();

            var input = document.createElement('input');
            input.type = type;
            input.value = val;
            input.className = 'pv-inline-input';
            input.style.width = '100%';

            el.textContent = '';
            el.appendChild(input);
            input.focus();

            function save() {
                var newVal = input.value.trim();
                if (!newVal) { cancel(); return; }
                el.dataset.value = newVal;
                el.textContent = newVal;
                fetch(window.PRODUCT_VIEW.updatePriceUrl.replace('update-price', 'update-field'), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.PRODUCT_VIEW.csrf },
                    body: JSON.stringify({ id: window.PRODUCT_VIEW.id, field: field, value: newVal })
                }).then(function (r) { return r.json(); }).catch(function () {});
            }
            function cancel() { el.textContent = orig; el.dataset.value = orig; }

            input.addEventListener('blur', save);
            input.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') { e.preventDefault(); save(); }
                if (e.key === 'Escape') { e.preventDefault(); cancel(); }
            });
        });
    });
});
