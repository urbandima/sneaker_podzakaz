/* =====================================================
   POIZON module — extracted JS
   Covers: poizon/order/index.php, poizon/order/view.php,
           poizon/product/index.php, poizon/customer/view.php
   ===================================================== */

/* -- poizon/order/view.php -- */
function copyLink(inputId, event) {
    const link = document.getElementById(inputId);
    if (!link) return;

    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(link.value).then(function() {
            showCopyNotification(event);
        }).catch(function(err) {
            fallbackCopy(link, event);
        });
    } else {
        fallbackCopy(link, event);
    }
}

function fallbackCopy(input, event) {
    input.select();
    try {
        document.execCommand('copy');
        showCopyNotification(event);
    } catch (err) {
        showCopyError(event);
    }
}

function showCopyError(event) {
    if (!event) return;
    const btn = event.target.closest('button');
    if (!btn) return;
    const originalHTML = btn.innerHTML;
    btn.innerHTML = '<i class="bi bi-x-circle"></i> Ошибка';
    btn.classList.add('btn-danger');
    btn.classList.remove('btn-outline-secondary');

    setTimeout(function() {
        btn.innerHTML = originalHTML;
        btn.classList.remove('btn-danger');
        btn.classList.remove('btn-outline-secondary');
    }, 2000);
}

function showCopyNotification(event) {
    if (!event) return;
    const btn = event.target.closest('button');
    if (!btn) return;
    const originalHTML = btn.innerHTML;
    btn.innerHTML = '<i class="bi bi-check"></i> Скопировано!';
    btn.classList.add('btn-success');
    btn.classList.remove('btn-outline-secondary');

    setTimeout(function() {
        btn.innerHTML = originalHTML;
        btn.classList.remove('btn-success');
        btn.classList.add('btn-outline-secondary');
    }, 2000);
}

function openHistoryModal() {
    const modal = document.getElementById('historyModal');
    if (modal) modal.classList.add('active');
}

function closeHistoryModal() {
    const modal = document.getElementById('historyModal');
    if (modal) modal.classList.remove('active');
}

document.addEventListener('DOMContentLoaded', function() {
    const toggleBtn = document.getElementById('toggleEditMode');
    const cancelBtn = document.getElementById('cancelEdit');
    const viewMode = document.getElementById('viewMode');
    const editMode = document.getElementById('editMode');
    const viewModeItems = document.getElementById('viewModeItems');
    const editModeItems = document.getElementById('editModeItems');
    const editModeText = document.getElementById('editModeText');

    if (toggleBtn) {
        toggleBtn.addEventListener('click', function() {
            if (viewMode && viewMode.style.display === 'none') {
                // Return to view
                viewMode.style.display = 'block';
                if (editMode) editMode.style.display = 'none';
                if (viewModeItems) viewModeItems.style.display = 'block';
                if (editModeItems) editModeItems.style.display = 'none';
                if (editModeText) editModeText.textContent = 'Редактировать';
                toggleBtn.className = 'btn btn-primary';
            } else {
                // Switch to edit
                if (viewMode) viewMode.style.display = 'none';
                if (editMode) editMode.style.display = 'block';
                if (viewModeItems) viewModeItems.style.display = 'none';
                if (editModeItems) editModeItems.style.display = 'block';
                if (editModeText) editModeText.textContent = 'Отменить редактирование';
                toggleBtn.className = 'btn btn-warning';
            }
        });
    }

    if (cancelBtn) {
        cancelBtn.addEventListener('click', function() {
            if (viewMode) viewMode.style.display = 'block';
            if (editMode) editMode.style.display = 'none';
            if (viewModeItems) viewModeItems.style.display = 'block';
            if (editModeItems) editModeItems.style.display = 'none';
            if (editModeText) editModeText.textContent = 'Редактировать';
            if (toggleBtn) toggleBtn.className = 'btn btn-primary';
        });
    }

    // History modal close on backdrop click
    const historyModal = document.getElementById('historyModal');
    if (historyModal) {
        historyModal.addEventListener('click', function(e) {
            if (e.target === historyModal) closeHistoryModal();
        });
    }
});

/* -- poizon/order/index.php -- */
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = SH.getCsrfToken();
    const filtersAccordionToggle = document.getElementById('filtersAccordionToggle');
    const filtersAccordionContent = document.getElementById('filtersAccordionContent');
    const filtersAccordionIcon = document.getElementById('filtersAccordionIcon');
    const filtersPanel = document.getElementById('filtersPanel');
    const filtersToggleButton = document.getElementById('filtersToggleButton');
    const filtersFlyout = document.getElementById('filtersFlyout');

    filtersAccordionToggle?.addEventListener('click', () => {
        const isOpen = filtersPanel?.classList.toggle('is-open');
        if (!isOpen) {
            filtersAccordionContent?.classList.add('collapsed');
            filtersAccordionToggle.setAttribute('aria-expanded', 'false');
            if (filtersAccordionIcon) {
                filtersAccordionIcon.classList.remove('bi-chevron-up');
                filtersAccordionIcon.classList.add('bi-chevron-down');
            }
        } else {
            filtersAccordionContent?.classList.remove('collapsed');
            filtersAccordionToggle.setAttribute('aria-expanded', 'true');
            if (filtersAccordionIcon) {
                filtersAccordionIcon.classList.remove('bi-chevron-down');
                filtersAccordionIcon.classList.add('bi-chevron-up');
            }
        }
    });

    function setFiltersFlyoutState(open) {
        if (!filtersFlyout || !filtersToggleButton) {
            return;
        }
        filtersFlyout.classList.toggle('is-open', open);
        filtersToggleButton.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    filtersToggleButton?.addEventListener('click', (event) => {
        event.stopPropagation();
        const isOpen = filtersFlyout?.classList.contains('is-open');
        setFiltersFlyoutState(!isOpen);
    });

    document.addEventListener('click', (event) => {
        if (
            filtersFlyout?.classList.contains('is-open') &&
            !event.target.closest('#filtersFlyout') &&
            !event.target.closest('#filtersToggleButton')
        ) {
            setFiltersFlyoutState(false);
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && filtersFlyout?.classList.contains('is-open')) {
            setFiltersFlyoutState(false);
        }
    });

    // Column toggles
    initColumnToggles();

    // Select all behaviour
    document.getElementById('selectAll')?.addEventListener('change', function() {
        document.querySelectorAll('.order-checkbox').forEach(cb => {
            cb.checked = this.checked;
        });
        updateBulkActions();
    });

    document.querySelectorAll('.order-checkbox').forEach(cb => {
        cb.addEventListener('change', updateBulkActions);
    });

    function updateBulkActions() {
        const checked = document.querySelectorAll('.order-checkbox:checked').length;
        document.getElementById('selectedCount').textContent = checked;
        document.getElementById('bulkActions').classList.toggle('visible', checked > 0);
    }

    // Inline editing
    document.querySelectorAll('.editable-cell').forEach(cell => {
        cell.addEventListener('dblclick', function() {
            const field = this.dataset.field;
            const orderId = this.dataset.orderId;
            const valueSpan = this.querySelector('.cell-value');
            const currentValue = valueSpan.textContent.trim();

            if (this.querySelector('input')) {
                return;
            }

            const input = document.createElement('input');
            input.type = 'text';
            input.value = currentValue === '—' ? '' : currentValue;
            input.style.width = '100%';

            valueSpan.style.display = 'none';
            this.querySelector('.edit-icon').style.display = 'none';
            this.appendChild(input);
            input.focus();
            input.select();

            const indicator = this.querySelector('.inline-cell-indicator');
            const saveValue = () => {
                const newValue = input.value.trim();
                updateOrderField(orderId, field, newValue, valueSpan, input, indicator, cell);
            };

            input.addEventListener('blur', saveValue);
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') saveValue();
                if (e.key === 'Escape') {
                    input.remove();
                    valueSpan.style.display = '';
                    cell.querySelector('.edit-icon').style.display = '';
                }
            });
        });
    });

    document.querySelectorAll('.status-select').forEach(select => {
        select.addEventListener('change', function() {
            const indicator = this.closest('td')?.querySelector('.inline-cell-indicator');
            updateOrderField(this.dataset.orderId, 'status', this.value, null, null, indicator, this.closest('td'));
        });
    });

    document.querySelectorAll('.status-checkbox').forEach(cb => {
        cb.addEventListener('change', function() {
            const value = this.checked ? 1 : 0;
            const indicator = this.closest('td')?.querySelector('.inline-cell-indicator');
            updateOrderField(this.dataset.orderId, this.dataset.field, value, null, null, indicator, this.closest('td'));
        });
    });

    // Search debounce
    let searchTimeout;
    document.getElementById('searchInput')?.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            applyFilters();
        }, 450);
    });

    document.getElementById('logistFilter')?.addEventListener('change', applyFilters);
    document.getElementById('processedFilter')?.addEventListener('change', applyFilters);
    document.getElementById('dateFrom')?.addEventListener('change', applyFilters);
    document.getElementById('dateTo')?.addEventListener('change', applyFilters);

    function setIndicator(indicator, state, message) {
        if (!indicator) return;
        indicator.innerHTML = '';
        indicator.classList.remove('text-success', 'text-danger');
        if (state === 'saving') {
            const spinner = document.createElement('span');
            spinner.className = 'spinner';
            indicator.appendChild(spinner);
        } else if (state === 'saved') {
            indicator.textContent = '✓';
            indicator.classList.add('text-success');
        } else if (state === 'error') {
            indicator.textContent = '!';
            indicator.classList.add('text-danger');
            if (message) {
                indicator.title = message;
            }
        }
    }

    function updateOrderField(orderId, field, value, valueSpan, input, indicator, cell) {
        const formData = new FormData();
        formData.append('field', field);
        formData.append('value', value);
        formData.append('_csrf', csrfToken);

        if (cell) {
            cell.classList.remove('cell-saved', 'cell-error');
            cell.classList.add('cell-saving');
        }
        setIndicator(indicator, 'saving');

        fetch('/admin/order/update-field?id=' + orderId, {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (valueSpan) {
                        valueSpan.textContent = value || '—';
                        valueSpan.style.display = '';
                    }
                    if (input) {
                        input.remove();
                        const editIcon = input.parentElement?.querySelector('.edit-icon');
                        if (editIcon) {
                            editIcon.style.display = '';
                        }
                    }
                    if (cell) {
                        cell.classList.remove('cell-saving');
                        cell.classList.add('cell-saved');
                        setTimeout(() => cell.classList.remove('cell-saved'), 1200);
                    }
                    setIndicator(indicator, 'saved');
                    showPoizonNotification('Сохранено', 'success');
                } else {
                    if (cell) {
                        cell.classList.remove('cell-saving');
                        cell.classList.add('cell-error');
                    }
                    setIndicator(indicator, 'error', data.message || 'Ошибка');
                    showPoizonNotification(data.message || 'Ошибка сохранения', 'error');
                }
            })
            .catch(() => {
                if (cell) {
                    cell.classList.remove('cell-saving');
                    cell.classList.add('cell-error');
                }
                setIndicator(indicator, 'error', 'Ошибка сети');
                showPoizonNotification('Ошибка сети', 'error');
            });
    }

    // Resizable headers
    const resizableTable = new ResizableTable('ordersTable');
    resizableTable.restoreSizes();
});

function initColumnToggles() {
    const columnCheckboxes = document.querySelectorAll('#columnMenu input[type="checkbox"]');
    columnCheckboxes.forEach(cb => {
        const saved = localStorage.getItem('orders-column-' + cb.dataset.column);
        if (saved === '0') {
            cb.checked = false;
        }
        toggleColumnVisibility(cb.dataset.column, cb.checked);
        cb.addEventListener('change', function() {
            toggleColumnVisibility(this.dataset.column, this.checked);
            localStorage.setItem('orders-column-' + this.dataset.column, this.checked ? '1' : '0');
            updateColumnIndicator();
        });
    });
    updateColumnIndicator();
}

function toggleColumnVisibility(columnKey, isVisible) {
    document.querySelectorAll(`[data-column="${columnKey}"]`).forEach(cell => {
        if (isVisible) {
            cell.classList.remove('column-hidden');
        } else {
            cell.classList.add('column-hidden');
        }
    });
}

function updateColumnIndicator() {
    const indicator = document.getElementById('columnToggleIndicator');
    if (!indicator) return;
    const total = document.querySelectorAll('#columnMenu input[type="checkbox"]').length;
    const visible = document.querySelectorAll('#columnMenu input[type="checkbox"]:checked').length;
    const hidden = total - visible;
    indicator.textContent = hidden > 0 ? `(${hidden} скрыто)` : '';
}

function navigateWithParams(updates) {
    updates = updates || {};
    const params = new URLSearchParams(window.location.search);
    Object.entries(updates).forEach(([key, value]) => {
        if (value === null || value === '' || value === undefined) {
            params.delete(key);
        } else {
            params.set(key, value);
        }
    });
    params.delete('page');
    const basePath = window.location.pathname.includes('/admin/order') ? window.location.pathname : '/admin/order/index';
    const query = params.toString();
    window.location.href = query ? `${basePath}?${query}` : basePath;
}

function changePageSize(value) {
    navigateWithParams({ 'per-page': value });
}

function applyFilters() {
    const search = document.getElementById('searchInput')?.value.trim() || null;
    const logist = document.getElementById('logistFilter')?.value || null;
    const processed = document.getElementById('processedFilter')?.value ?? null;
    const dateFrom = document.getElementById('dateFrom')?.value || null;
    const dateTo = document.getElementById('dateTo')?.value || null;

    navigateWithParams({
        search,
        logist,
        processed,
        'date_from': dateFrom,
        'date_to': dateTo
    });
}

function resetFilters() {
    if (document.getElementById('searchInput')) document.getElementById('searchInput').value = '';
    if (document.getElementById('logistFilter')) document.getElementById('logistFilter').value = '';
    if (document.getElementById('processedFilter')) document.getElementById('processedFilter').value = '';
    if (document.getElementById('dateFrom')) document.getElementById('dateFrom').value = '';
    if (document.getElementById('dateTo')) document.getElementById('dateTo').value = '';

    navigateWithParams({
        search: null,
        logist: null,
        processed: null,
        'date_from': null,
        'date_to': null,
        status: null
    });
}

function toggleExportMenu() {
    document.getElementById('exportDropdown')?.classList.toggle('show');
}

function toggleColumnMenu() {
    document.getElementById('columnMenu')?.classList.toggle('show');
}

document.addEventListener('click', function(e) {
    if (!e.target.closest('.export-menu')) {
        document.getElementById('exportDropdown')?.classList.remove('show');
    }
    if (!e.target.closest('.column-toggle')) {
        document.getElementById('columnMenu')?.classList.remove('show');
    }
});

function showPoizonNotification(message, type) {
    if (window.NotificationManager) {
        NotificationManager[type](message);
    }
}

function setQuickRange(range) {
    const now = new Date();
    let from = new Date(now);

    if (range === 'week') {
        from.setDate(now.getDate() - 6);
    } else if (range === 'month') {
        from.setDate(now.getDate() - 29);
    }

    const fromEl = document.getElementById('dateFrom');
    const toEl = document.getElementById('dateTo');
    if (fromEl) fromEl.value = formatDate(from);
    if (toEl) toEl.value = formatDate(now);
    applyFilters();
}

function formatDate(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

function bulkUpdateStatus() {
    const selected = Array.from(document.querySelectorAll('.order-checkbox:checked')).map(cb => cb.value);
    if (selected.length === 0) {
        showPoizonNotification('Выберите заказы', 'warning');
        return;
    }

    const modal = document.createElement('div');
    modal.className = 'modal fade show';
    modal.style.display = 'block';
    modal.style.backgroundColor = 'rgba(0,0,0,0.5)';

    const statuses = {
        'created': 'Новый',
        'confirmed': 'Подтвержден',
        'paid': 'Оплачен',
        'ordered': 'Заказан',
        'shipped': 'Отправлен',
        'delivered': 'Доставлен',
        'canceled': 'Отменен'
    };

    let optionsHtml = '';
    for (const [key, label] of Object.entries(statuses)) {
        optionsHtml += `<option value="${key}">${label}</option>`;
    }

    modal.innerHTML = `
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Изменить статус (${selected.length} заказов)</h5>
                    <button type="button" class="btn-close" onclick="closeBulkModal()"></button>
                </div>
                <div class="modal-body">
                    <select class="form-select" id="bulkStatusSelect">
                        ${optionsHtml}
                    </select>
                    <div class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" id="bulkAddComment">
                        <label class="form-check-label" for="bulkAddComment">
                            Добавить комментарий
                        </label>
                    </div>
                    <textarea class="form-control mt-2" id="bulkComment" rows="3" placeholder="Комментарий к изменению статуса..." style="display:none;"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeBulkModal()">Отмена</button>
                    <button type="button" class="btn btn-primary" onclick="confirmBulkStatusUpdate()">Применить</button>
                </div>
            </div>
        </div>
    `;

    document.body.appendChild(modal);
    document.getElementById('bulkAddComment').addEventListener('change', function() {
        document.getElementById('bulkComment').style.display = this.checked ? 'block' : 'none';
    });
}

function confirmBulkStatusUpdate() {
    const selected = Array.from(document.querySelectorAll('.order-checkbox:checked')).map(cb => cb.value);
    const status = document.getElementById('bulkStatusSelect').value;
    const addComment = document.getElementById('bulkAddComment').checked;
    const comment = addComment ? document.getElementById('bulkComment').value : '';

    const formData = new FormData();
    formData.append('ids', JSON.stringify(selected));
    formData.append('status', status);
    if (comment) formData.append('comment', comment);
    formData.append('_csrf', SH.getCsrfToken());

    fetch('/admin/order/bulk-update-status', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            closeBulkModal();
            if (data.success) {
                showPoizonNotification(`Статус обновлен для ${data.updated} заказов`, 'success');
                setTimeout(() => location.reload(), 800);
            } else {
                showPoizonNotification(data.message || 'Ошибка', 'error');
            }
        })
        .catch(() => {
            closeBulkModal();
            showPoizonNotification('Ошибка сети', 'error');
        });
}

function bulkAssignLogist() {
    const selected = Array.from(document.querySelectorAll('.order-checkbox:checked')).map(cb => cb.value);
    if (selected.length === 0) {
        showPoizonNotification('Выберите заказы', 'warning');
        return;
    }

    const modal = document.createElement('div');
    modal.className = 'modal fade show';
    modal.style.display = 'block';
    modal.style.backgroundColor = 'rgba(0,0,0,0.5)';

    modal.innerHTML = `
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Назначить логиста (${selected.length} заказов)</h5>
                    <button type="button" class="btn-close" onclick="closeBulkModal()"></button>
                </div>
                <div class="modal-body">
                    <select class="form-select" id="bulkLogistSelect">
                        <option value="">Не назначен</option>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeBulkModal()">Отмена</button>
                    <button type="button" class="btn btn-primary" onclick="confirmBulkLogistAssign()">Назначить</button>
                </div>
            </div>
        </div>
    `;

    document.body.appendChild(modal);

    fetch('/admin/user/logists')
        .then(response => response.json())
        .then(data => {
            const select = document.getElementById('bulkLogistSelect');
            data.forEach(logist => {
                select.innerHTML += `<option value="${logist.id}">${logist.username}</option>`;
            });
        });
}

function confirmBulkLogistAssign() {
    const selected = Array.from(document.querySelectorAll('.order-checkbox:checked')).map(cb => cb.value);
    const logistId = document.getElementById('bulkLogistSelect').value;

    const formData = new FormData();
    formData.append('ids', JSON.stringify(selected));
    formData.append('logist_id', logistId);
    formData.append('_csrf', SH.getCsrfToken());

    fetch('/admin/order/bulk-assign-logist', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            closeBulkModal();
            if (data.success) {
                showPoizonNotification(`Логист назначен для ${data.updated} заказов`, 'success');
                setTimeout(() => location.reload(), 800);
            } else {
                showPoizonNotification(data.message || 'Ошибка', 'error');
            }
        })
        .catch(() => {
            closeBulkModal();
            showPoizonNotification('Ошибка сети', 'error');
        });
}

function bulkExport() {
    const selected = Array.from(document.querySelectorAll('.order-checkbox:checked')).map(cb => cb.value);
    if (selected.length === 0) {
        showPoizonNotification('Выберите заказы', 'warning');
        return;
    }

    const modal = document.createElement('div');
    modal.className = 'modal fade show';
    modal.style.display = 'block';
    modal.style.backgroundColor = 'rgba(0,0,0,0.5)';

    modal.innerHTML = `
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Экспорт (${selected.length} заказов)</h5>
                    <button type="button" class="btn-close" onclick="closeBulkModal()"></button>
                </div>
                <div class="modal-body">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="exportFormat" id="exportExcel" value="xlsx" checked>
                        <label class="form-check-label" for="exportExcel">
                            <i class="bi bi-file-earmark-excel"></i> Excel (.xlsx)
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="exportFormat" id="exportCsv" value="csv">
                        <label class="form-check-label" for="exportCsv">
                            <i class="bi bi-file-earmark-text"></i> CSV
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeBulkModal()">Отмена</button>
                    <button type="button" class="btn btn-primary" onclick="confirmBulkExport()">Экспортировать</button>
                </div>
            </div>
        </div>
    `;

    document.body.appendChild(modal);
}

function confirmBulkExport() {
    const selected = Array.from(document.querySelectorAll('.order-checkbox:checked')).map(cb => cb.value);
    const format = document.querySelector('input[name="exportFormat"]:checked').value;
    closeBulkModal();
    window.location.href = `/admin/order/export?ids=${selected.join(',')}&format=${format}`;
}

function closeBulkModal() {
    document.querySelector('.modal.show')?.remove();
}

function bulkMarkProcessed() {
    const selected = Array.from(document.querySelectorAll('.order-checkbox:checked')).map(cb => cb.value);
    if (!selected.length) {
        showPoizonNotification('Выберите заказы', 'warning');
        return;
    }
    if (confirm(`Отметить ${selected.length} заказов как обработанные?`)) {
        bulkUpdateField('is_processed', 1, 'отмечены как обработанные');
    }
}

function bulkMarkShipped() {
    const selected = Array.from(document.querySelectorAll('.order-checkbox:checked')).map(cb => cb.value);
    if (!selected.length) {
        showPoizonNotification('Выберите заказы', 'warning');
        return;
    }
    if (confirm(`Отметить ${selected.length} заказов как отправленные?`)) {
        bulkUpdateField('is_shipped', 1, 'отмечены как отправленные');
    }
}

function bulkUpdateField(field, value, actionText) {
    const selected = Array.from(document.querySelectorAll('.order-checkbox:checked')).map(cb => cb.value);
    if (!selected.length) return;

    const formData = new FormData();
    formData.append('ids', JSON.stringify(selected));
    formData.append('field', field);
    formData.append('value', value);
    formData.append('_csrf', SH.getCsrfToken());

    fetch('/admin/order/bulk-update-field', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showPoizonNotification(`Заказы ${actionText}`, 'success');
                setTimeout(() => location.reload(), 800);
            } else {
                showPoizonNotification(data.message || 'Ошибка', 'error');
            }
        })
        .catch(() => showPoizonNotification('Ошибка сети', 'error'));
}

class ResizableTable {
    constructor(tableId) {
        this.table = document.getElementById(tableId);
        if (!this.table) return;

        this.headers = this.table.querySelectorAll('th.resizable');
        this.currentColumn = null;
        this.startX = 0;
        this.startWidth = 0;

        this.init();
    }

    init() {
        this.headers.forEach(header => {
            const handle = document.createElement('div');
            handle.className = 'resize-handle';
            header.appendChild(handle);
            handle.addEventListener('mousedown', (e) => this.startResize(e, header));
        });

        document.addEventListener('mousemove', (e) => this.resize(e));
        document.addEventListener('mouseup', () => this.stopResize());
    }

    startResize(event, header) {
        this.currentColumn = header;
        this.startX = event.pageX;
        this.startWidth = header.offsetWidth;
        document.body.style.cursor = 'col-resize';
        event.preventDefault();
    }

    resize(event) {
        if (!this.currentColumn) return;
        const diff = event.pageX - this.startX;
        const newWidth = Math.max(60, this.startWidth + diff);
        this.currentColumn.style.width = `${newWidth}px`;
        const columnName = this.currentColumn.dataset.column;
        if (columnName) {
            localStorage.setItem(`column-width-${columnName}`, newWidth);
        }
    }

    stopResize() {
        this.currentColumn = null;
        document.body.style.cursor = 'default';
    }

    restoreSizes() {
        this.headers.forEach(header => {
            const columnName = header.dataset.column;
            if (!columnName) return;
            const savedWidth = localStorage.getItem(`column-width-${columnName}`);
            if (savedWidth) {
                header.style.width = `${savedWidth}px`;
            }
        });
    }
}

/* -- poizon/product/index.php -- */
(function() {
    document.addEventListener('DOMContentLoaded', function() {
        const filtersPanel = document.getElementById('filtersPanel');
        const exportDropdown = document.getElementById('exportDropdown');
        const selectAllCheckbox = document.getElementById('selectAllProducts');
        const productCheckboxes = document.querySelectorAll('.product-checkbox');
        const bulkActions = document.getElementById('bulkActions');
        const selectedCountEl = document.getElementById('selectedCount');

        if (!filtersPanel && !selectAllCheckbox) return; // not on product page

        window.toggleProductFilters = function() {
            if (filtersPanel) filtersPanel.classList.toggle('collapsed');
        };

        window.toggleExportMenuProducts = function() {
            if (exportDropdown) exportDropdown.classList.toggle('visible');
        };

        document.addEventListener('click', (event) => {
            if (exportDropdown && !event.target.closest('.export-menu')) {
                exportDropdown.classList.remove('visible');
            }
        });

        selectAllCheckbox?.addEventListener('change', function() {
            productCheckboxes.forEach(cb => cb.checked = this.checked);
            updateBulkState();
        });

        productCheckboxes.forEach(cb => cb.addEventListener('change', updateBulkState));

        function updateBulkState() {
            const selected = document.querySelectorAll('.product-checkbox:checked');
            if (selectedCountEl) selectedCountEl.textContent = selected.length;
            if (bulkActions) {
                if (selected.length > 0) {
                    bulkActions.classList.add('show');
                } else {
                    bulkActions.classList.remove('show');
                }
            }
        }
    });
})();

function getSelectedProductIds() {
    return Array.from(document.querySelectorAll('.product-checkbox:checked')).map(cb => cb.value);
}

function changeProductPageSize(size) {
    const url = new URL(window.location.href);
    url.searchParams.set('per-page', size);
    url.searchParams.delete('page');
    window.location.href = url.toString();
}

function bulkUpdateProducts(field, value) {
    const ids = getSelectedProductIds();
    if (!ids.length) {
        alert('Выберите товары');
        return;
    }

    const csrfToken = SH.getCsrfToken();
    const bulkUpdateUrl = document.getElementById('bulkUpdateUrl')?.value || '/admin/product/bulk-update';

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
                alert(data.message || 'Не удалось обновить товары.');
            }
        })
        .catch(() => alert('Ошибка сети'));
}

function confirmBulkDelete() {
    const ids = getSelectedProductIds();
    if (!ids.length) {
        alert('Выберите товары');
        return;
    }
    if (confirm(`Удалить ${ids.length} товаров? Действие необратимо.`)) {
        bulkDeleteProducts(ids);
    }
}

function bulkDeleteProducts(ids) {
    const csrfToken = SH.getCsrfToken();
    const bulkDeleteUrl = document.getElementById('bulkDeleteUrl')?.value || '/admin/product/bulk-delete';

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
                alert(data.message || 'Не удалось удалить товары.');
            }
        })
        .catch(() => alert('Ошибка сети'));
}

function bulkExportSelected() {
    const ids = getSelectedProductIds();
    const exportBaseUrl = document.getElementById('exportBaseUrl')?.value || '/admin/product/export';
    if (!ids.length) {
        window.location.href = exportBaseUrl;
        return;
    }
    window.location.href = `${exportBaseUrl}?ids=${ids.join(',')}&format=xlsx`;
}

/* -- poizon/customer/view.php -- */
function toggleStatus(id) {
    if (!confirm('Изменить статус покупателя?')) return;

    fetch('/admin/customer/' + id + '/toggle-status', {
        method: 'POST',
        headers: { 'X-CSRF-Token': SH.getCsrfToken() }
    })
    .then(r => r.json())
    .then(data => {
        alert(data.message);
        if (data.success) location.reload();
    });
}

function resetPassword(id) {
    if (!confirm('Сбросить пароль покупателя?')) return;

    fetch('/admin/customer/' + id + '/reset-password', {
        method: 'POST',
        headers: { 'X-CSRF-Token': SH.getCsrfToken() }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('Новый пароль: ' + data.password + '\n\nСкопируйте его и передайте покупателю.');
        } else {
            alert(data.message);
        }
    });
}

function linkOrders(id) {
    fetch('/admin/customer/' + id + '/link-orders', {
        method: 'POST',
        headers: { 'X-CSRF-Token': SH.getCsrfToken() }
    })
    .then(r => r.json())
    .then(data => {
        alert(data.message);
        if (data.success) location.reload();
    });
}


/* -- poizon/run.php -- */
document.addEventListener('DOMContentLoaded', function() {
// Drag and Drop
    const uploadArea = document.getElementById('upload-area');
    const fileInput = document.getElementById('file-input');
    const fileInfo = document.getElementById('file-info');
    const fileName = document.getElementById('file-name');
    const submitBtn = document.getElementById('submit-file-btn');

    uploadArea.addEventListener('click', () => fileInput.click());

    uploadArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadArea.classList.add('dragover');
    });

    uploadArea.addEventListener('dragleave', () => {
        uploadArea.classList.remove('dragover');
    });

    uploadArea.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadArea.classList.remove('dragover');
        
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            fileInput.files = files;
            showFileInfo(files[0]);
        }
    });

    fileInput.addEventListener('change', (e) => {
        if (e.target.files.length > 0) {
            showFileInfo(e.target.files[0]);
        }
    });

    function showFileInfo(file) {
        fileName.textContent = file.name + ' (' + formatFileSize(file.size) + ')';
        fileInfo.classList.remove('d-none');
        uploadArea.style.display = 'none';
        submitBtn.disabled = false;
    }

    function clearFile() {
        fileInput.value = '';
        fileInfo.classList.add('d-none');
        uploadArea.style.display = 'block';
        submitBtn.disabled = true;
    }

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    }
});


/* -- poizon/view.php -- */
function copyFullLog() {
        const logContainer = document.getElementById('fullLogContainer');
        const text = logContainer.innerText;
        navigator.clipboard.writeText(text).then(function() {
            alert('Лог скопирован в буфер обмена!');
        });
    }
    
    function toggleLogExpand() {
        const logContainer = document.getElementById('fullLogContainer');
        const expandText = document.getElementById('expandText');
        
        if (logContainer.style.maxHeight === '500px') {
            logContainer.style.maxHeight = 'none';
            expandText.textContent = 'Свернуть';
        } else {
            logContainer.style.maxHeight = '500px';
            expandText.textContent = 'Развернуть';
        }
    }


/* -- poizon/product/view.php -- */
// Копирование цены в юанях в буфер обмена
function copyToClipboard(text, element) {
    // Используем Clipboard API
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(function() {
            // Показываем уведомление
            const originalHTML = element.innerHTML;
            element.innerHTML = '✓ Скопировано!';
            element.classList.remove('bg-info');
            element.classList.add('bg-success');
            
            setTimeout(function() {
                element.innerHTML = originalHTML;
                element.classList.remove('bg-success');
                element.classList.add('bg-info');
            }, 1500);
        }).catch(function(err) {
            console.error('Ошибка копирования:', err);
            alert('Не удалось скопировать: ' + text);
        });
    } else {
        // Фолбэк для старых браузеров
        const tempInput = document.createElement('input');
        tempInput.value = text;
        document.body.appendChild(tempInput);
        tempInput.select();
        document.execCommand('copy');
        document.body.removeChild(tempInput);
        
        const originalHTML = element.innerHTML;
        element.innerHTML = '✓ Скопировано!';
        element.classList.add('bg-success');
        
        setTimeout(function() {
            element.innerHTML = originalHTML;
            element.classList.remove('bg-success');
            element.classList.add('bg-info');
        }, 1500);
    }
}


/* -- poizon/tariff/index.php -- */

