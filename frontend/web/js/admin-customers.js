/**
 * Admin Customers JS
 * ==================
 * JS вынесенный из inline <script> блоков:
 * - customer/index.php
 * - customer/view.php
 * - customer/update.php
 */

/* === CUSTOMER INDEX === */

/* -- customer/index.php -- */
document.addEventListener('DOMContentLoaded', function() {
    window.toggleStatus = function(id) {
        if (!confirm('Изменить статус покупателя?')) return;

        fetch('/admin/customer/' + id + '/toggle-status', {
            method: 'POST',
            headers: {
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content,
            }
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message);
            }
        });
    };
});

/* === CUSTOMER VIEW === */

/* -- customer/view.php -- */
document.addEventListener('DOMContentLoaded', function() {
    window.toggleStatus = window.toggleStatus || function(id) {
        if (!confirm('Изменить статус покупателя?')) return;

        fetch('/admin/customer/' + id + '/toggle-status', {
            method: 'POST',
            headers: { 'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content }
        })
        .then(r => r.json())
        .then(data => {
            alert(data.message);
            if (data.success) location.reload();
        });
    };

    window.resetPassword = function(id) {
        if (!confirm('Сбросить пароль покупателя?')) return;

        fetch('/admin/customer/' + id + '/reset-password', {
            method: 'POST',
            headers: { 'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content }
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                alert('Новый пароль: ' + data.password + '\n\nСкопируйте его и передайте покупателю.');
            } else {
                alert(data.message);
            }
        });
    };

    window.linkOrders = function(id) {
        fetch('/admin/customer/' + id + '/link-orders', {
            method: 'POST',
            headers: { 'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content }
        })
        .then(r => r.json())
        .then(data => {
            alert(data.message);
            if (data.success) location.reload();
        });
    };

    // B6.4 Points
    var _pointsAction = 'add';

    window.togglePointsForm = function(action) {
        _pointsAction = action;
        document.getElementById('points-submit-btn').textContent = action === 'add' ? 'Начислить' : 'Списать';
        document.getElementById('points-form').style.display = 'block';
        document.getElementById('points-amount').focus();
    };

    window.submitPoints = function(customerId) {
        const amount = parseInt(document.getElementById('points-amount').value);
        const comment = document.getElementById('points-comment').value.trim();
        if (!amount || amount <= 0) return alert('Укажите кол-во баллов');
        if (!comment) return alert('Комментарий обязателен');
        const url = '/admin/customer/' + (_pointsAction === 'add' ? 'add-points' : 'deduct-points');
        fetch(url, {
            method: 'POST',
            headers: {'Content-Type':'application/json','X-CSRF-Token': document.querySelector('meta[name=csrf-token]')?.content||''},
            body: JSON.stringify({id: customerId, amount, comment})
        }).then(r=>r.json()).then(d=>{ if(d.success) location.reload(); else alert(d.message||'Ошибка'); });
    };

    // B6.5 Notes
    window.addCustomerNote = function(customerId) {
        const text = document.getElementById('customer-note-text').value.trim();
        if (!text) return;
        fetch('/admin/customer/add-note', {
            method: 'POST',
            headers: {'Content-Type':'application/json','X-CSRF-Token': document.querySelector('meta[name=csrf-token]')?.content||''},
            body: JSON.stringify({id: customerId, text})
        }).then(r=>r.json()).then(d=>{
            if (d.success) {
                document.getElementById('customer-notes-list').insertAdjacentHTML('beforeend', d.html);
                document.getElementById('customer-note-text').value = '';
            }
        });
    };

    // B6.7 Tags
    window.addTag = function(customerId, tag) {
        tag = (tag || '').trim();
        if (!tag) return;
        fetch('/admin/customer/update-tags', {
            method: 'POST',
            headers: {'Content-Type':'application/json','X-CSRF-Token': document.querySelector('meta[name=csrf-token]')?.content||''},
            body: JSON.stringify({id: customerId, action: 'add', tag})
        }).then(r=>r.json()).then(d=>{ if(d.success) location.reload(); });
    };

    window.removeTag = function(customerId, tag) {
        fetch('/admin/customer/update-tags', {
            method: 'POST',
            headers: {'Content-Type':'application/json','X-CSRF-Token': document.querySelector('meta[name=csrf-token]')?.content||''},
            body: JSON.stringify({id: customerId, action: 'remove', tag})
        }).then(r=>r.json()).then(d=>{ if(d.success) location.reload(); });
    };
});
