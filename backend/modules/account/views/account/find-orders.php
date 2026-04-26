<?php
/** @var yii\web\View $this */

$this->title = 'Отследить заказ — СНИКЕРХЭД';
?>

<div class="container" style="max-width:560px;margin:60px auto;padding:0 16px 60px">
    <h1 style="font-size:1.5rem;font-weight:700;margin-bottom:8px">
        <i class="bi bi-search"></i> Отследить заказ
    </h1>
    <p style="color:var(--color-text-secondary,#6b7280);margin-bottom:28px">
        Введите email или телефон, указанный при оформлении заказа, чтобы узнать его статус.
    </p>

    <div id="trackingError" class="alert alert-danger" style="display:none"></div>

    <form id="findOrdersForm" style="display:flex;flex-direction:column;gap:16px">
        <div>
            <label for="trackEmail" style="display:block;font-weight:500;margin-bottom:6px">Email</label>
            <input type="email" id="trackEmail" name="email" class="form-control"
                   placeholder="example@mail.com" autocomplete="email">
        </div>
        <div style="text-align:center;color:#6b7280;font-size:.875rem">или</div>
        <div>
            <label for="trackPhone" style="display:block;font-weight:500;margin-bottom:6px">Телефон</label>
            <input type="tel" id="trackPhone" name="phone" class="form-control"
                   placeholder="+375 29 123-45-67" autocomplete="tel">
        </div>
        <button type="submit" class="btn btn-primary" style="margin-top:8px">
            <i class="bi bi-search"></i> Найти заказы
        </button>
    </form>

    <div id="trackingResults" style="display:none;margin-top:32px"></div>
</div>

<script>
document.getElementById('findOrdersForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const email = document.getElementById('trackEmail').value.trim();
    const phone = document.getElementById('trackPhone').value.trim();

    if (!email && !phone) {
        document.getElementById('trackingError').style.display = '';
        document.getElementById('trackingError').textContent = 'Укажите email или телефон';
        return;
    }
    document.getElementById('trackingError').style.display = 'none';

    const btn = this.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Поиск...';

    fetch('/account/find-orders', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
        body: JSON.stringify({email, phone}),
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-search"></i> Найти заказы';
        if (!data.success || !data.orders?.length) {
            document.getElementById('trackingError').style.display = '';
            document.getElementById('trackingError').textContent = 'Заказы не найдены';
            document.getElementById('trackingResults').style.display = 'none';
            return;
        }
        const rows = data.orders.map(o => `
            <div style="border:1px solid #e5e7eb;border-radius:8px;padding:16px 20px;margin-bottom:12px;display:flex;align-items:center;justify-content:space-between;gap:12px">
                <div>
                    <div style="font-weight:600">Заказ #${o.id}</div>
                    <div style="font-size:.875rem;color:#6b7280">${o.created_at} · ${o.total}</div>
                    <div style="font-size:.875rem;margin-top:4px">${o.statusLabel}</div>
                </div>
                ${o.token ? `<a href="/order/${o.token}" class="btn btn-sm btn-outline-secondary">Подробнее</a>` : ''}
            </div>`).join('');
        document.getElementById('trackingResults').innerHTML =
            `<h2 style="font-size:1rem;font-weight:600;margin-bottom:16px">Найдено заказов: ${data.count}</h2>${rows}`;
        document.getElementById('trackingResults').style.display = '';
    })
    .catch(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-search"></i> Найти заказы';
        document.getElementById('trackingError').style.display = '';
        document.getElementById('trackingError').textContent = 'Ошибка соединения, попробуйте ещё раз';
    });
});
</script>
