/**
 * Admin Customers JS
 * Uses SH.* utilities from utils.js for CSRF and fetch.
 */

document.addEventListener('DOMContentLoaded', function () {

    /* === CUSTOMER INDEX & VIEW: Toggle Status === */
    window.toggleStatus = function (id) {
        if (!confirm('Изменить статус покупателя?')) return;

        SH.fetch('/admin/customer/' + id + '/toggle-status', { method: 'POST' })
            .then(function (data) {
                if (data.success) location.reload();
                else alert(data.message);
            });
    };

    /* === CUSTOMER VIEW: Reset Password === */
    window.resetPassword = function (id) {
        if (!confirm('Сбросить пароль покупателя?')) return;

        SH.fetch('/admin/customer/' + id + '/reset-password', { method: 'POST' })
            .then(function (data) {
                if (data.success) {
                    alert('Новый пароль: ' + data.password + '\n\nСкопируйте его и передайте покупателю.');
                } else {
                    alert(data.message);
                }
            });
    };

    /* === Link Orders === */
    window.linkOrders = function (id) {
        SH.fetch('/admin/customer/' + id + '/link-orders', { method: 'POST' })
            .then(function (data) {
                alert(data.message);
                if (data.success) location.reload();
            });
    };

    /* === Points === */
    var _pointsAction = 'add';

    window.togglePointsForm = function (action) {
        _pointsAction = action;
        document.getElementById('points-submit-btn').textContent = action === 'add' ? 'Начислить' : 'Списать';
        document.getElementById('points-form').style.display = 'block';
        document.getElementById('points-amount').focus();
    };

    window.submitPoints = function (customerId) {
        var amount = parseInt(document.getElementById('points-amount').value);
        var comment = document.getElementById('points-comment').value.trim();
        if (!amount || amount <= 0) return alert('Укажите кол-во баллов');
        if (!comment) return alert('Комментарий обязателен');

        var url = '/admin/customer/' + (_pointsAction === 'add' ? 'add-points' : 'deduct-points');
        SH.fetch(url, {
            method: 'POST',
            body: { id: customerId, amount: amount, comment: comment }
        }).then(function (d) {
            if (d.success) location.reload(); else alert(d.message || 'Ошибка');
        });
    };

    /* === Notes === */
    window.addCustomerNote = function (customerId) {
        var text = document.getElementById('customer-note-text').value.trim();
        if (!text) return;

        SH.fetch('/admin/customer/add-note', {
            method: 'POST',
            body: { id: customerId, text: text }
        }).then(function (d) {
            if (d.success) {
                document.getElementById('customer-notes-list').insertAdjacentHTML('beforeend', d.html);
                document.getElementById('customer-note-text').value = '';
            }
        });
    };

    /* === Tags === */
    window.addTag = function (customerId, tag) {
        tag = (tag || '').trim();
        if (!tag) return;
        SH.fetch('/admin/customer/update-tags', {
            method: 'POST',
            body: { id: customerId, action: 'add', tag: tag }
        }).then(function (d) { if (d.success) location.reload(); });
    };

    window.removeTag = function (customerId, tag) {
        SH.fetch('/admin/customer/update-tags', {
            method: 'POST',
            body: { id: customerId, action: 'remove', tag: tag }
        }).then(function (d) { if (d.success) location.reload(); });
    };
});
