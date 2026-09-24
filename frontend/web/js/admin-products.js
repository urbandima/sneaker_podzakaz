/**
 * Admin Products JS
 * =================
 * JS вынесенный из inline <script> блоков:
 * - product/index.php
 * - product/edit.php
 */

/* === PRODUCT INDEX === */

/* -- product/index.php -- */
document.addEventListener('DOMContentLoaded', function () {
    const filtersPanel = document.getElementById('filtersPanel');
    const exportDropdown = document.getElementById('exportDropdown');
    const selectAllCheckbox = document.getElementById('selectAllProducts');
    const productCheckboxes = document.querySelectorAll('.product-checkbox');
    const bulkActionsEl = document.getElementById('bulkActions');
    const selectedCountEl = document.getElementById('selectedCount');

    const exportBaseUrl = document.getElementById('js-export-base-url')?.dataset.url || '';
    const bulkUpdateUrl = document.getElementById('js-bulk-update-url')?.dataset.url || '';
    const bulkDeleteUrl = document.getElementById('js-bulk-delete-url')?.dataset.url || '';
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    window.toggleProductFilters = function () {
        if (filtersPanel) filtersPanel.classList.toggle('collapsed');
    };

    window.toggleExportMenu = function () {
        if (exportDropdown) exportDropdown.classList.toggle('visible');
    };

    document.addEventListener('click', function (event) {
        if (exportDropdown && !event.target.closest('.export-menu')) {
            exportDropdown.classList.remove('visible');
        }
    });

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function () {
            productCheckboxes.forEach(cb => cb.checked = this.checked);
            updateBulkState();
        });
    }

    productCheckboxes.forEach(cb => cb.addEventListener('change', updateBulkState));

    function updateBulkState() {
        const selected = document.querySelectorAll('.product-checkbox:checked');
        if (selectedCountEl) selectedCountEl.textContent = selected.length;
        if (bulkActionsEl) {
            if (selected.length > 0) {
                bulkActionsEl.classList.add('show');
            } else {
                bulkActionsEl.classList.remove('show');
            }
        }
    }

    window.showBulkActions = function () {
        if (bulkActionsEl) bulkActionsEl.classList.toggle('show');
    };

    function getSelectedProductIds() {
        return Array.from(document.querySelectorAll('.product-checkbox:checked')).map(cb => cb.value);
    }

    window.changeProductPageSize = function (size) {
        const url = new URL(window.location.href);
        url.searchParams.set('per-page', size);
        url.searchParams.delete('page');
        window.location.href = url.toString();
    };

    window.bulkUpdateProducts = function (field, value) {
        const ids = getSelectedProductIds();
        if (!ids.length) {
            SH.toast({type:'warning', message:'Выберите товары'});
            return;
        }

        const formData = new FormData();
        formData.append('ids', JSON.stringify(ids));
        formData.append('field', field);
        formData.append('value', value);
        formData.append('_csrf', csrfToken);

        fetch(bulkUpdateUrl, {
            method: 'POST',
            body: formData,
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    SH.toast({type:'error', message: data.message || 'Не удалось обновить товары'});
                }
            })
            .catch(() => SH.toast({type:'error', message:'Ошибка сети. Попробуйте снова.'}));
    };

    window.confirmBulkDelete = function () {
        const ids = getSelectedProductIds();
        if (!ids.length) {
            SH.toast({type:'warning', message:'Выберите товары'});
            return;
        }
        if (confirm(`Удалить ${ids.length} товаров? Действие необратимо.`)) {
            bulkDeleteProducts(ids);
        }
    };

    function bulkDeleteProducts(ids) {
        const formData = new FormData();
        formData.append('ids', JSON.stringify(ids));
        formData.append('_csrf', csrfToken);

        fetch(bulkDeleteUrl, {
            method: 'POST',
            body: formData,
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    SH.toast({type:'error', message: data.message || 'Не удалось удалить товары'});
                }
            })
            .catch(() => SH.toast({type:'error', message:'Ошибка сети. Попробуйте снова.'}));
    }

    window.bulkExportSelected = function () {
        const ids = getSelectedProductIds();
        if (!ids.length) {
            window.location.href = exportBaseUrl;
            return;
        }
        window.location.href = `${exportBaseUrl}?ids=${ids.join(',')}&format=xlsx`;
    };
});

/* === PRODUCT EDIT === */

/* -- product/edit.php -- */
document.addEventListener('DOMContentLoaded', function () {
    // Size grid quick-add button
    const gridSelect = document.getElementById('size-grid-select');
    const addBtn = document.getElementById('add-from-grid-btn');

    if (gridSelect && addBtn) {
        gridSelect.addEventListener('change', function () {
            if (this.value) {
                const url = addBtn.getAttribute('href').replace('__GRID_ID__', this.value);
                addBtn.setAttribute('href', url);
                addBtn.style.display = 'inline-block';
            } else {
                addBtn.style.display = 'none';
            }
        });
    }

    // Highlight active section in sticky nav
    const sections = document.querySelectorAll('[id^="section-"]');
    const navLinks = document.querySelectorAll('.quick-nav-sticky a');

    function highlightNav() {
        let current = '';
        sections.forEach(section => {
            const sectionTop = section.offsetTop;
            if (pageYOffset >= (sectionTop - 150)) {
                current = section.getAttribute('id');
            }
        });

        navLinks.forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href') === '#' + current) {
                link.classList.add('active');
            }
        });
    }

    window.addEventListener('scroll', highlightNav);
});

// Clipboard copy for CNY prices
function copyToClipboard(text, element) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(function () {
            const originalHTML = element.innerHTML;
            element.innerHTML = '✓ Скопировано!';
            element.classList.remove('bg-info');
            element.classList.add('bg-success');

            setTimeout(function () {
                element.innerHTML = originalHTML;
                element.classList.remove('bg-success');
                element.classList.add('bg-info');
            }, 1500);
        }).catch(function (err) {
            SH.toast({type:'warning', message:'Не удалось скопировать: ' + text});
        });
    } else {
        const tempInput = document.createElement('input');
        tempInput.value = text;
        document.body.appendChild(tempInput);
        tempInput.select();
        document.execCommand('copy');
        document.body.removeChild(tempInput);

        const originalHTML = element.innerHTML;
        element.innerHTML = '✓ Скопировано!';
        element.classList.add('bg-success');

        setTimeout(function () {
            element.innerHTML = originalHTML;
            element.classList.remove('bg-success');
            element.classList.add('bg-info');
        }, 1500);
    }
}

// ==================== PRODUCT PARAMS INLINE EDITING ====================

function editProductParam(key) {
    const row = document.querySelector(`tr[data-param-key="${key}"]`);
    if (!row) return;

    row.querySelector('.param-value-display').style.display = 'none';
    row.querySelector('.param-value-edit').style.display = 'block';
    row.querySelector('.param-actions-display').style.display = 'none';
    row.querySelector('.param-actions-edit').style.display = 'block';

    const input = row.querySelector('.param-edit-input');
    if (input) input.focus();
}

function cancelEditProductParam(key) {
    const row = document.querySelector(`tr[data-param-key="${key}"]`);
    if (!row) return;

    const input = row.querySelector('.param-edit-input');
    if (input) input.value = input.dataset.original;

    row.querySelector('.param-value-display').style.display = 'block';
    row.querySelector('.param-value-edit').style.display = 'none';
    row.querySelector('.param-actions-display').style.display = 'block';
    row.querySelector('.param-actions-edit').style.display = 'none';
}

function saveProductParam(key) {
    const row = document.querySelector(`tr[data-param-key="${key}"]`);
    if (!row) return;

    const input = row.querySelector('.param-edit-input');
    if (!input) return;

    const newValue = input.value;
    const displayDiv = row.querySelector('.param-value-display');
    if (input.tagName === 'SELECT' && newValue) {
        displayDiv.textContent = input.options[input.selectedIndex].text;
    } else {
        displayDiv.textContent = newValue || 'Не указано';
    }

    input.dataset.original = newValue;

    row.querySelector('.param-value-display').style.display = 'block';
    row.querySelector('.param-value-edit').style.display = 'none';
    row.querySelector('.param-actions-display').style.display = 'block';
    row.querySelector('.param-actions-edit').style.display = 'none';

    showInlineMessage('Не забудьте сохранить форму для применения изменений', 'warning');
}

// ==================== POIZON PROPS INLINE EDITING ====================

function editPoizonProp(index) {
    const row = document.querySelector(`tr[data-prop-index="${index}"]`);
    if (!row) return;

    row.querySelector('.poizon-prop-key-display').style.display = 'none';
    row.querySelector('.poizon-prop-key-edit').style.display = 'block';
    row.querySelector('.poizon-prop-value-display').style.display = 'none';
    row.querySelector('.poizon-prop-value-edit').style.display = 'block';
    row.querySelector('.poizon-prop-actions-display').style.display = 'none';
    row.querySelector('.poizon-prop-actions-edit').style.display = 'block';

    const input = row.querySelector('.poizon-prop-value-edit input');
    if (input) input.focus();
}

function cancelEditPoizonProp(index) {
    const row = document.querySelector(`tr[data-prop-index="${index}"]`);
    if (!row) return;

    const keyInput = row.querySelector('.poizon-prop-key-edit input');
    const valueInput = row.querySelector('.poizon-prop-value-edit input');

    if (keyInput) keyInput.value = keyInput.dataset.original;
    if (valueInput) valueInput.value = valueInput.dataset.original;

    row.querySelector('.poizon-prop-key-display').style.display = 'block';
    row.querySelector('.poizon-prop-key-edit').style.display = 'none';
    row.querySelector('.poizon-prop-value-display').style.display = 'block';
    row.querySelector('.poizon-prop-value-edit').style.display = 'none';
    row.querySelector('.poizon-prop-actions-display').style.display = 'block';
    row.querySelector('.poizon-prop-actions-edit').style.display = 'none';
}

function savePoizonProp(index) {
    const row = document.querySelector(`tr[data-prop-index="${index}"]`);
    if (!row) return;

    const keyInput = row.querySelector('.poizon-prop-key-edit input');
    const valueInput = row.querySelector('.poizon-prop-value-edit input');

    if (!keyInput || !valueInput) return;

    const newKey = keyInput.value;
    const newValue = valueInput.value;

    row.querySelector('.poizon-prop-key-display').textContent = newKey;
    row.querySelector('.poizon-prop-value-display').textContent = newValue;

    keyInput.dataset.original = newKey;
    valueInput.dataset.original = newValue;

    row.querySelector('.poizon-prop-key-display').style.display = 'block';
    row.querySelector('.poizon-prop-key-edit').style.display = 'none';
    row.querySelector('.poizon-prop-value-display').style.display = 'block';
    row.querySelector('.poizon-prop-value-edit').style.display = 'none';
    row.querySelector('.poizon-prop-actions-display').style.display = 'block';
    row.querySelector('.poizon-prop-actions-edit').style.display = 'none';

    showInlineMessage('Не забудьте сохранить форму для применения изменений', 'warning');
}

function deletePoizonProp(index) {
    if (!confirm('Удалить эту характеристику?')) return;

    const row = document.querySelector(`tr[data-prop-index="${index}"]`);
    if (row) {
        row.remove();
        showInlineMessage('Характеристика будет удалена при сохранении формы', 'warning');
    }
}

function showInlineMessage(message, type) {
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    alert.style.cssText = 'top: 80px; right: 20px; z-index: 9999; min-width: 300px;';
    alert.innerHTML = `${message}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;

    document.body.appendChild(alert);

    setTimeout(() => alert.remove(), 3000);
}

/* === PRODUCT VIEW === */

/* -- product/view.php -- */
document.addEventListener('DOMContentLoaded', function () {
    // B7.3 Inline size BYN price edit
    document.querySelectorAll('.size-price-byn').forEach(cell => {
        cell.addEventListener('dblclick', function () {
            const sizeId = this.dataset.sizeId;
            const currentVal = this.dataset.price || '0';
            const input = document.createElement('input');
            input.type = 'number'; input.value = currentVal; input.step = '0.01';
            input.style.cssText = 'width:90px;padding:2px 6px;border:1px solid var(--admin-accent);border-radius:4px';
            const original = this.innerHTML;
            this.innerHTML = '';
            this.appendChild(input);
            input.focus(); input.select();
            const save = () => {
                const newVal = parseFloat(input.value);
                if (isNaN(newVal)) { this.innerHTML = original; return; }
                fetch('/admin/product/update-size-price', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': document.querySelector('meta[name=csrf-token]')?.content || '' },
                    body: JSON.stringify({ size_id: sizeId, price_byn: newVal })
                }).then(r => r.json()).then(d => { this.innerHTML = d.success ? newVal.toFixed(2) + ' BYN' : original; });
            };
            input.addEventListener('keydown', e => { if (e.key === 'Enter') save(); if (e.key === 'Escape') this.innerHTML = original; });
            input.addEventListener('blur', save);
        });
    });
});

// B7.2 Inline price edit
function editPrice(productId, currentPrice) {
    const newPrice = prompt('Новая цена BYN:', currentPrice);
    if (newPrice === null || isNaN(parseFloat(newPrice))) return;
    fetch('/admin/product/update-price', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': document.querySelector('meta[name=csrf-token]')?.content || '' },
        body: JSON.stringify({ id: productId, price: parseFloat(newPrice) })
    }).then(r => r.json()).then(d => { if (d.success) location.reload(); else SH.toast({type:'error', message: d.message || 'Ошибка'}); });
}

// B7.2 Toggle active
function toggleActive(productId) {
    fetch('/admin/product/toggle-active', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': document.querySelector('meta[name=csrf-token]')?.content || '' },
        body: JSON.stringify({ id: productId })
    }).then(r => r.json()).then(d => { if (d.success) location.reload(); });
}

// B7.5 Sync Poizon
function syncPoizon(productId) {
    const btn = event.currentTarget;
    btn.disabled = true; btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Синхронизация...';
    fetch('/admin/product/sync-poizon', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': document.querySelector('meta[name=csrf-token]')?.content || '' },
        body: JSON.stringify({ id: productId })
    }).then(r => r.json()).then(d => {
        btn.disabled = false; btn.innerHTML = '<i class="bi bi-arrow-repeat"></i> Синхронизировать';
        if (d.success) location.reload(); else SH.toast({type:'error', message: d.message || 'Ошибка синхронизации'});
    });
}

/* === PRODUCT BULK-PRICE === */

/* -- product/bulk-price.php -- */
document.addEventListener('DOMContentLoaded', function () {
    var csrfToken = document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').content : '';
    var bulkUpdateUrl = document.getElementById('js-bulk-update-price-url')?.dataset.url || '';

    window.recalcRow = function (input) {
        var tr = input.closest('tr');
        var base = parseFloat(tr.dataset.basePrice) || 0;
        var markup = parseFloat(input.value);
        var newPriceEl = tr.querySelector('.new-price');
        var hiddenEl = tr.querySelector('.new-price-val');

        if (!isNaN(markup)) {
            var newPrice = base * (1 + markup / 100);
            newPrice = Math.round(newPrice * 100) / 100;
            newPriceEl.textContent = newPrice.toFixed(2).replace('.', ',');
            hiddenEl.value = newPrice;
        } else {
            newPriceEl.textContent = '—';
            hiddenEl.value = '';
        }
        updateApplyBtn();
    };

    window.applyGlobalMarkup = function () {
        var markup = parseFloat(document.getElementById('global-markup').value);
        if (isNaN(markup)) { SH.toast({type:'warning', message:'Введите значение наценки'}); return; }
        document.querySelectorAll('#bulk-price-table tbody tr').forEach(function (tr) {
            var markupInput = tr.querySelector('.markup-input');
            if (markupInput) {
                markupInput.value = markup;
                window.recalcRow(markupInput);
            }
        });
    };

    window.toggleSelectAll = function (cb) {
        document.querySelectorAll('.row-check').forEach(function (c) { c.checked = cb.checked; });
    };

    function updateApplyBtn() {
        var hasNew = false;
        document.querySelectorAll('.new-price-val').forEach(function (el) {
            if (el.value !== '') hasNew = true;
        });
        var btn = document.getElementById('apply-btn');
        if (btn) btn.disabled = !hasNew;
    }

    window.applyPrices = function () {
        var prices = [];
        document.querySelectorAll('#bulk-price-table tbody tr').forEach(function (tr) {
            var hiddenEl = tr.querySelector('.new-price-val');
            var check = tr.querySelector('.row-check');
            var anyChecked = document.querySelectorAll('.row-check:checked').length > 0;
            if (hiddenEl && hiddenEl.value !== '') {
                if (!anyChecked || (check && check.checked)) {
                    prices.push({ id: parseInt(tr.dataset.id), price: parseFloat(hiddenEl.value) });
                }
            }
        });

        if (prices.length === 0) { SH.toast({type:'warning', message:'Нет товаров для обновления'}); return; }

        var btn = document.getElementById('apply-btn');
        var resultEl = document.getElementById('apply-result');
        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Применение...';

        fetch(bulkUpdateUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ prices: prices })
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-check-circle"></i> Применить цены';
                if (resultEl) {
                    resultEl.style.display = 'block';
                    resultEl.style.background = data.success ? '#d1fae5' : '#fee2e2';
                    resultEl.style.color = data.success ? '#065f46' : '#991b1b';
                    resultEl.textContent = data.success
                        ? 'Обновлено товаров: ' + data.updated
                        : (data.error || 'Ошибка обновления');
                }
                if (data.success) {
                    document.querySelectorAll('#bulk-price-table tbody tr').forEach(function (tr) {
                        var hiddenEl = tr.querySelector('.new-price-val');
                        if (hiddenEl && hiddenEl.value !== '') {
                            tr.dataset.basePrice = hiddenEl.value;
                            var currentPriceCell = tr.querySelector('td:nth-child(4)');
                            if (currentPriceCell) {
                                currentPriceCell.textContent = parseFloat(hiddenEl.value).toFixed(2).replace('.', ',');
                            }
                        }
                    });
                }
            })
            .catch(function () {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-check-circle"></i> Применить цены';
                if (resultEl) {
                    resultEl.style.display = 'block';
                    resultEl.style.background = '#fee2e2';
                    resultEl.style.color = '#991b1b';
                    resultEl.textContent = 'Ошибка соединения';
                }
            });
    };
});
