/**
 * Admin Orders JS
 * ===============
 * JS вынесенный из inline <script> блоков:
 * - order/index.php
 * - order/view.php
 * Зависимости: jQuery (загружается через YiiAsset)
 */

/* === ORDER INDEX === */

/* -- order/index.php -- */
document.addEventListener('DOMContentLoaded', function() {

    /* ── VIEW TOGGLE ── */
    function switchView(v) {
        localStorage.setItem('orderView', v);
        document.getElementById('tableView').style.display  = v === 'table'  ? '' : 'none';
        document.getElementById('kanbanView').style.display = v === 'kanban' ? '' : 'none';
        document.getElementById('btnTable').classList.toggle('active',  v === 'table');
        document.getElementById('btnKanban').classList.toggle('active', v === 'kanban');
    }
    window.switchView = switchView;
    (function(){
        var saved = localStorage.getItem('orderView') || 'table';
        switchView(saved);
    })();

    /* ── PAGE SIZE ── */
    window.changePageSize = function(value) {
        var params = new URLSearchParams(window.location.search);
        params.set('per-page', value);
        params.delete('page');
        window.location.href = window.location.pathname + '?' + params.toString();
    };

    /* ── SELECT ALL ── */
    var selectAllCb = document.getElementById('selectAll');
    if (selectAllCb) {
        selectAllCb.addEventListener('change', function() {
            document.querySelectorAll('.order-checkbox').forEach(function(cb) { cb.checked = selectAllCb.checked; });
            updateBulkToolbar();
        });
    }

    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('order-checkbox')) {
            updateBulkToolbar();
        }
    });

    function updateBulkToolbar() {
        var checked = document.querySelectorAll('.order-checkbox:checked');
        var toolbar = document.getElementById('bulkToolbar');
        var countEl = document.getElementById('selectedCount');
        if (countEl) countEl.textContent = checked.length;
        if (toolbar) toolbar.classList.toggle('visible', checked.length > 0);
    }

    window.clearSelection = function() {
        document.querySelectorAll('.order-checkbox').forEach(function(cb) { cb.checked = false; });
        var selectAll = document.getElementById('selectAll');
        if (selectAll) selectAll.checked = false;
        updateBulkToolbar();
    };

    /* ── BULK ACTION EXTRAS ── */
    var bulkSelect = document.getElementById('bulkActionSelect');
    if (bulkSelect) {
        bulkSelect.addEventListener('change', function() {
            var statusExtra = document.getElementById('bulkStatusExtra');
            var logistExtra = document.getElementById('bulkLogistExtra');
            if (statusExtra) statusExtra.style.display = this.value === 'change_status' ? '' : 'none';
            if (logistExtra) logistExtra.style.display = this.value === 'assign_logist' ? '' : 'none';
        });
    }

    window.applyBulkAction = function() {
        var action = document.getElementById('bulkActionSelect').value;
        if (!action) { alert('Выберите действие'); return; }
        var ids = Array.from(document.querySelectorAll('.order-checkbox:checked')).map(function(cb) { return cb.value; });
        if (!ids.length) { alert('Выберите заказы'); return; }
        var extra = '';
        if (action === 'change_status') {
            extra = document.getElementById('bulkStatusExtra').value;
            if (!extra) { alert('Выберите статус'); return; }
        }
        if (action === 'assign_logist') {
            extra = document.getElementById('bulkLogistExtra').value;
        }
        if (action === 'export_csv') {
            window.location.href = '/admin/order/export-csv?ids=' + ids.join(',');
            return;
        }
        fetch('/admin/order/bulk-action', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').content : ''
            },
            body: new URLSearchParams({ action: action, ids: JSON.stringify(ids), extra: extra })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) { alert(data.message || 'Выполнено'); location.reload(); }
            else { alert(data.message || 'Ошибка'); }
        })
        .catch(function() { alert('Ошибка запроса'); });
    };

    /* ── KANBAN DRAG & DROP ── */
    var draggedCard = null;

    window.onDragStart = function(e) {
        draggedCard = e.currentTarget;
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', draggedCard.dataset.id);
        draggedCard.style.opacity = '.4';
    };

    window.onDragEnd = function(e) {
        if (draggedCard) draggedCard.style.opacity = '';
        draggedCard = null;
        document.querySelectorAll('.kanban-col').forEach(function(c) { c.classList.remove('drag-target'); });
    };

    window.onDragOver = function(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
        var col = e.currentTarget.closest('.kanban-col');
        if (col) col.classList.add('drag-target');
    };

    window.onDragLeave = function(e) {
        var col = e.currentTarget.closest('.kanban-col');
        if (col) col.classList.remove('drag-target');
    };

    window.onDrop = function(e, newStatus) {
        e.preventDefault();
        var col = e.currentTarget.closest('.kanban-col');
        if (col) col.classList.remove('drag-target');
        if (!draggedCard) return;
        var orderId = draggedCard.dataset.id;
        var oldStatus = draggedCard.dataset.status;
        if (oldStatus === newStatus) return;
        var targetCards = document.getElementById('col-' + newStatus);
        if (targetCards) {
            draggedCard.dataset.status = newStatus;
            targetCards.appendChild(draggedCard);
            updateColBadge(oldStatus);
            updateColBadge(newStatus);
        }
        fetch('/admin/order/update-status', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').content : ''
            },
            body: new URLSearchParams({ id: orderId, status: newStatus })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (!data.success) {
                alert('Ошибка смены статуса: ' + (data.message || ''));
                var oldCards = document.getElementById('col-' + oldStatus);
                if (oldCards && draggedCard) {
                    draggedCard.dataset.status = oldStatus;
                    oldCards.appendChild(draggedCard);
                    updateColBadge(oldStatus);
                    updateColBadge(newStatus);
                }
            }
        })
        .catch(function() { alert('Ошибка соединения'); });
    };

    function updateColBadge(status) {
        var col = document.querySelector('.kanban-col[data-status="' + status + '"]');
        if (!col) return;
        var badge = col.querySelector('.kanban-col-badge');
        var count = col.querySelectorAll('.kanban-card').length;
        if (badge) badge.textContent = count;
    }

    /* ── SAVE FILTERS ── */
    window.saveFilters = function() {
        // Form submit already sends _filter_save=1 — session save handled in PHP
    };

}); // end DOMContentLoaded

/* === ORDER VIEW === */

/* -- order/view.php -- */
/* Note: .js-flag handlers use PHP-injected CSRF and URL, so they remain as registerJs in the view.
   All non-PHP-dependent functions are extracted here. */

document.addEventListener('DOMContentLoaded', function() {

    /* ── COPY LINK (view.php) ── */
    window.copyLink = function(inputId, event) {
        var link = document.getElementById(inputId || 'publicLink');
        if (!link) return;
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(link.value).then(function() {
                if (event) showCopyNotification(event);
                else showSaveIndicator('Ссылка скопирована');
            }).catch(function() { fallbackCopy(link, event); });
        } else {
            fallbackCopy(link, event);
        }
    };

    function fallbackCopy(input, event) {
        input.select();
        try {
            document.execCommand('copy');
            if (event) showCopyNotification(event);
            else showSaveIndicator('Ссылка скопирована');
        } catch(err) {
            if (event) showCopyError(event);
        }
    }

    function showCopyError(event) {
        if (!event) return;
        var btn = event.target.closest('button');
        if (!btn) return;
        var originalHTML = btn.innerHTML;
        btn.innerHTML = '<i class="bi bi-x-circle"></i> Ошибка';
        btn.classList.add('btn-danger');
        setTimeout(function() { btn.innerHTML = originalHTML; btn.classList.remove('btn-danger'); }, 2000);
    }

    function showCopyNotification(event) {
        if (!event) return;
        var btn = event.target.closest('button');
        if (!btn) return;
        var originalHTML = btn.innerHTML;
        btn.innerHTML = '<i class="bi bi-check"></i> Скопировано!';
        btn.classList.add('btn-success');
        setTimeout(function() { btn.innerHTML = originalHTML; btn.classList.remove('btn-success'); }, 2000);
    }

    /* ── SAVE INDICATOR ── */
    window.showSaveIndicator = function(message) {
        var indicator = document.getElementById('saveIndicator');
        if (!indicator) return;
        indicator.innerHTML = '<i class="bi bi-check-circle"></i> ' + (message || 'Сохранено');
        indicator.classList.add('show');
        setTimeout(function() { indicator.classList.remove('show'); }, 2000);
    };

    /* ── INLINE EDITABLE FIELDS ── */
    function makeFieldEditable(element) {
        element.addEventListener('click', function(e) {
            e.preventDefault(); e.stopPropagation();
            if (element.classList.contains('saving')) return;
            var field = element.dataset.field;
            var id = element.dataset.id;
            var currentValue = element.textContent.trim();
            var isTextarea = field === 'comment';
            var input = document.createElement(isTextarea ? 'textarea' : 'input');
            input.type = 'text';
            input.className = isTextarea ? 'inline-edit-textarea' : 'inline-edit-input';
            input.value = currentValue;
            var actions = document.createElement('div');
            actions.className = 'inline-edit-actions';
            actions.innerHTML = '<button type="button" class="inline-edit-save">Сохранить</button><button type="button" class="inline-edit-cancel">Отмена</button>';
            element.innerHTML = '';
            element.appendChild(input);
            element.appendChild(actions);
            element.classList.add('saving');
            input.focus(); input.select();
            var saveBtn = actions.querySelector('.inline-edit-save');
            var cancelBtn = actions.querySelector('.inline-edit-cancel');
            function save() {
                var newValue = input.value.trim();
                fetch('/admin/order/update-field?id=' + id, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: 'field=' + encodeURIComponent(field) + '&value=' + encodeURIComponent(newValue)
                })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.success) {
                        element.textContent = newValue || '-';
                        element.classList.remove('saving');
                        showNotification('Поле успешно обновлено', 'success');
                    } else {
                        element.textContent = currentValue;
                        element.classList.remove('saving');
                        showNotification(data.message || 'Ошибка сохранения', 'error');
                    }
                })
                .catch(function() { element.textContent = currentValue; element.classList.remove('saving'); showNotification('Ошибка соединения', 'error'); });
            }
            function cancel() { element.textContent = currentValue; element.classList.remove('saving'); }
            saveBtn.addEventListener('click', save);
            cancelBtn.addEventListener('click', cancel);
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && !isTextarea) { e.preventDefault(); save(); }
                else if (e.key === 'Escape') { e.preventDefault(); cancel(); }
            });
            document.addEventListener('click', function outsideClick(e) {
                if (!element.contains(e.target)) { cancel(); document.removeEventListener('click', outsideClick); }
            });
        });
    }

    document.querySelectorAll('.editable-field').forEach(makeFieldEditable);

    /* ── SHOW NOTIFICATION ── */
    window.showNotification = function(message, type) {
        type = type || 'info';
        var notification = document.createElement('div');
        notification.style.cssText = 'position:fixed;top:20px;right:20px;padding:1rem 1.5rem;background:' +
            (type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6') +
            ';color:white;border-radius:8px;box-shadow:0 4px 6px rgba(0,0,0,0.1);z-index:10000;animation:slideIn 0.3s ease;';
        notification.textContent = message;
        document.body.appendChild(notification);
        setTimeout(function() {
            notification.style.animation = 'slideOut 0.3s ease';
            setTimeout(function() { notification.remove(); }, 300);
        }, 3000);
    };

    /* CSS animations injected once */
    if (!document.getElementById('order-view-animations')) {
        var style = document.createElement('style');
        style.id = 'order-view-animations';
        style.textContent = '@keyframes slideIn{from{transform:translateX(100%);opacity:0}to{transform:translateX(0);opacity:1}}@keyframes slideOut{from{transform:translateX(0);opacity:1}to{transform:translateX(100%);opacity:0}}';
        document.head.appendChild(style);
    }

    /* ── EDIT MODE TOGGLE (view.php) ── */
    var toggleBtn = document.getElementById('toggleEditMode');
    var cancelBtn = document.getElementById('cancelEdit');
    var viewMode = document.getElementById('viewMode');
    var editMode = document.getElementById('editMode');
    var viewModeItems = document.getElementById('viewModeItems');
    var editModeItems = document.getElementById('editModeItems');
    var editModeText = document.getElementById('editModeText');

    if (toggleBtn) {
        toggleBtn.addEventListener('click', function() {
            if (viewMode && viewMode.style.display === 'none') {
                if (viewMode) viewMode.style.display = 'block';
                if (editMode) editMode.style.display = 'none';
                if (viewModeItems) viewModeItems.style.display = 'block';
                if (editModeItems) editModeItems.style.display = 'none';
                if (editModeText) editModeText.textContent = 'Редактировать';
                toggleBtn.className = 'btn btn-primary';
            } else {
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

    /* ── ADD/REMOVE ORDER ITEMS (view.php add-item-edit) ── */
    /* itemIndex is set via registerJs in the view because it uses PHP count($model->orderItems) */

    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-item') || e.target.closest('.remove-item')) {
            var btn = e.target.classList.contains('remove-item') ? e.target : e.target.closest('.remove-item');
            btn.closest('.order-item').remove();
            updateRemoveButtons();
        }
    });

    function updateRemoveButtons() {
        var items = document.querySelectorAll('#order-items-edit .order-item');
        if (items.length === 1) {
            items[0].querySelector('.remove-item').disabled = true;
        } else {
            items.forEach(function(item) { item.querySelector('.remove-item').disabled = false; });
        }
    }
    updateRemoveButtons();

    /* ── ADVANCED FILTERS TOGGLE (_advanced_filters.php) ── */
    window.toggleFilters = function() {
        var body = document.getElementById('filters-body');
        var icon = document.getElementById('filter-toggle-icon');
        if (body) body.classList.toggle('open');
        if (icon) {
            icon.classList.toggle('bi-chevron-down');
            icon.classList.toggle('bi-chevron-up');
        }
    };

}); // end DOMContentLoaded
