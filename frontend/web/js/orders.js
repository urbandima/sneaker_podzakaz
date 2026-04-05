/**
 * Order List View Toggle — Таблица / Канбан
 * B4.1: Переключение видов списка заказов
 */

(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        initViewToggle();
        initKanbanCards();
    });

    function initViewToggle() {
        const tableBtn = document.getElementById('view-table-btn');
        const kanbanBtn = document.getElementById('view-kanban-btn');
        const tableView = document.getElementById('orders-table-view');
        const kanbanView = document.getElementById('orders-kanban-view');

        if (!tableBtn || !kanbanBtn) return;

        // Загрузить сохраненный вид
        const savedView = localStorage.getItem('orders-view-mode') || 'table';
        setView(savedView);

        tableBtn.addEventListener('click', function() {
            setView('table');
            localStorage.setItem('orders-view-mode', 'table');
        });

        kanbanBtn.addEventListener('click', function() {
            setView('kanban');
            localStorage.setItem('orders-view-mode', 'kanban');
        });

        function setView(mode) {
            if (mode === 'table') {
                tableView.style.display = 'block';
                if (kanbanView) kanbanView.style.display = 'none';
                tableBtn.classList.add('active');
                kanbanBtn.classList.remove('active');
            } else {
                tableView.style.display = 'none';
                if (kanbanView) kanbanView.style.display = 'grid';
                kanbanBtn.classList.add('active');
                tableBtn.classList.remove('active');
            }
        }
    }

    function initKanbanCards() {
        // Drag and drop для канбана (заглушка для будущей реализации)
        const cards = document.querySelectorAll('.admin-kanban-card');
        cards.forEach(card => {
            card.addEventListener('click', function() {
                const url = this.dataset.url;
                if (url) window.location.href = url;
            });
        });
    }

    // Bulk actions (B4.3)
    window.initBulkActions = function() {
        const selectAll = document.getElementById('select-all-orders');
        const checkboxes = document.querySelectorAll('.order-checkbox');
        const bulkBar = document.getElementById('bulk-actions-bar');

        if (!selectAll) return;

        selectAll.addEventListener('change', function() {
            checkboxes.forEach(cb => cb.checked = this.checked);
            updateBulkBar();
        });

        checkboxes.forEach(cb => {
            cb.addEventListener('change', updateBulkBar);
        });

        function updateBulkBar() {
            const checked = document.querySelectorAll('.order-checkbox:checked').length;
            if (checked > 0 && bulkBar) {
                bulkBar.style.display = 'flex';
                bulkBar.querySelector('.bulk-count').textContent = checked;
            } else if (bulkBar) {
                bulkBar.style.display = 'none';
            }
        }
    };

    // Export orders (B4.4)
    window.exportOrders = function(format) {
        const params = new URLSearchParams(window.location.search);
        params.set('export', format);
        window.location.href = '/admin/order/index?' + params.toString();
    };

})();
