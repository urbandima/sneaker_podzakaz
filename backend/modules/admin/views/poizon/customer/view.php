<?php

use yii\helpers\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var app\models\Customer $customer */
/** @var app\models\Order[] $orders */

$this->title = 'Покупатель: ' . $customer->getFullName();
?>

<style>
.customer-view-page {
    padding: 1.5rem;
}

.page-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.back-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    background: #fff;
    border-radius: 10px;
    color: #374151;
    text-decoration: none;
    transition: all 0.2s;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.back-btn:hover {
    background: #f3f4f6;
}

.page-header h1 {
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0;
    flex: 1;
}

.header-actions {
    display: flex;
    gap: 0.5rem;
}

.btn-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    font-weight: 500;
    font-size: 0.875rem;
    cursor: pointer;
    transition: all 0.2s;
    border: none;
    text-decoration: none;
}

.btn-header.edit {
    background: #000;
    color: #fff;
}

.btn-header.edit:hover {
    background: #333;
}

.btn-header.danger {
    background: #fee2e2;
    color: #dc2626;
}

.btn-header.danger:hover {
    background: #fecaca;
}

.content-grid {
    display: grid;
    grid-template-columns: 1fr 350px;
    gap: 1.5rem;
}

@media (max-width: 1100px) {
    .content-grid {
        grid-template-columns: 1fr;
    }
}

.content-card {
    background: #fff;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    margin-bottom: 1.5rem;
}

.content-card h2 {
    font-size: 1rem;
    font-weight: 700;
    margin: 0 0 1.25rem 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #374151;
}

.profile-header {
    display: flex;
    align-items: center;
    gap: 1.25rem;
    padding-bottom: 1.25rem;
    border-bottom: 1px solid #e5e7eb;
    margin-bottom: 1.25rem;
}

.profile-avatar {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    color: #fff;
    font-weight: 700;
}

.profile-info h3 {
    font-size: 1.25rem;
    font-weight: 700;
    margin: 0 0 0.25rem 0;
}

.profile-info .email {
    color: #6b7280;
    font-size: 0.9375rem;
}

.profile-info .status {
    margin-top: 0.5rem;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

.status-badge.active { background: #d1fae5; color: #059669; }
.status-badge.inactive { background: #fee2e2; color: #dc2626; }

.info-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
}

.info-item {
    padding: 0.75rem 1rem;
    background: #f9fafb;
    border-radius: 8px;
}

.info-label {
    font-size: 0.75rem;
    color: #6b7280;
    margin-bottom: 0.25rem;
}

.info-value {
    font-weight: 600;
    color: #1f2937;
}

.stats-row {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
    margin-bottom: 1.25rem;
}

.stat-item {
    text-align: center;
    padding: 1rem;
    background: #f9fafb;
    border-radius: 10px;
}

.stat-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1f2937;
}

.stat-label {
    font-size: 0.75rem;
    color: #6b7280;
}

.orders-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.order-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.875rem 1rem;
    background: #f9fafb;
    border-radius: 10px;
    text-decoration: none;
    color: inherit;
    transition: all 0.2s;
}

.order-item:hover {
    background: #f3f4f6;
}

.order-info .order-number {
    font-weight: 600;
    margin-bottom: 0.125rem;
}

.order-info .order-date {
    font-size: 0.75rem;
    color: #6b7280;
}

.order-meta {
    text-align: right;
}

.order-total {
    font-weight: 700;
}

.order-status {
    display: inline-block;
    padding: 0.125rem 0.5rem;
    border-radius: 10px;
    font-size: 0.6875rem;
    font-weight: 600;
    margin-top: 0.25rem;
}

.order-status.created { background: #dbeafe; color: #1d4ed8; }
.order-status.processing { background: #fef3c7; color: #d97706; }
.order-status.completed { background: #d1fae5; color: #059669; }
.order-status.cancelled { background: #fee2e2; color: #dc2626; }

.empty-orders {
    text-align: center;
    padding: 2rem;
    color: #6b7280;
}

.action-buttons {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.btn-action {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.75rem;
    border-radius: 8px;
    font-weight: 500;
    font-size: 0.875rem;
    cursor: pointer;
    transition: all 0.2s;
    border: 1px solid #e5e7eb;
    background: #fff;
    color: #374151;
    text-decoration: none;
}

.btn-action:hover {
    background: #f3f4f6;
}

.btn-action.primary {
    background: #3b82f6;
    color: #fff;
    border-color: #3b82f6;
}

.btn-action.primary:hover {
    background: #2563eb;
}

.btn-action.warning {
    background: #fef3c7;
    color: #92400e;
    border-color: #fcd34d;
}

.btn-action.danger {
    background: #fee2e2;
    color: #dc2626;
    border-color: #fca5a5;
}
</style>

<div class="customer-view-page">
    <div class="page-header">
        <a href="<?= Url::to(['customer/index']) ?>" class="back-btn">
            <i class="bi bi-arrow-left"></i>
        </a>
        <h1>Покупатель</h1>
        <div class="header-actions">
            <a href="<?= Url::to(['customer/update', 'id' => $customer->id]) ?>" class="btn-header edit">
                <i class="bi bi-pencil"></i> Редактировать
            </a>
        </div>
    </div>

    <div class="content-grid">
        <div class="content-main">
            <div class="content-card">
                <div class="profile-header">
                    <div class="profile-avatar">
                        <?= mb_strtoupper(mb_substr($customer->first_name ?: $customer->email, 0, 1)) ?>
                    </div>
                    <div class="profile-info">
                        <h3><?= Html::encode($customer->getFullName()) ?></h3>
                        <div class="email"><?= Html::encode($customer->email) ?></div>
                        <div class="status">
                            <span class="status-badge <?= $customer->status == 10 ? 'active' : 'inactive' ?>">
                                <?= $customer->getStatusLabel() ?>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="stats-row">
                    <div class="stat-item">
                        <div class="stat-value"><?= $customer->orders_count ?></div>
                        <div class="stat-label">Заказов</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?= Yii::$app->formatter->asCurrency($customer->total_spent, 'BYN') ?></div>
                        <div class="stat-label">Потрачено</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?= $customer->last_order_at ? Yii::$app->formatter->asDate($customer->last_order_at, 'short') : '-' ?></div>
                        <div class="stat-label">Последний заказ</div>
                    </div>
                </div>

                <h2><i class="bi bi-person-lines-fill"></i> Контактные данные</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Телефон</div>
                        <div class="info-value"><?= Html::encode($customer->phone ?: 'Не указан') ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Дата рождения</div>
                        <div class="info-value"><?= $customer->birth_date ? Yii::$app->formatter->asDate($customer->birth_date, 'long') : 'Не указана' ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Город</div>
                        <div class="info-value"><?= Html::encode($customer->default_city ?: 'Не указан') ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Адрес</div>
                        <div class="info-value"><?= Html::encode($customer->default_address ?: 'Не указан') ?></div>
                    </div>
                </div>
            </div>

            <div class="content-card">
                <h2><i class="bi bi-bag-check"></i> История заказов</h2>
                
                <?php if (!empty($orders)): ?>
                    <div class="orders-list">
                        <?php foreach ($orders as $order): ?>
                            <a href="<?= Url::to(['order/view', 'id' => $order->id]) ?>" class="order-item">
                                <div class="order-info">
                                    <div class="order-number">Заказ #<?= $order->order_number ?: $order->id ?></div>
                                    <div class="order-date"><?= Yii::$app->formatter->asDatetime($order->created_at, 'short') ?></div>
                                </div>
                                <div class="order-meta">
                                    <div class="order-total"><?= Yii::$app->formatter->asCurrency($order->total_amount, 'BYN') ?></div>
                                    <span class="order-status <?= $order->status ?>"><?= $order->getStatusLabel() ?></span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-orders">
                        <i class="bi bi-bag-x" style="font-size:2rem;display:block;margin-bottom:0.5rem;"></i>
                        Заказов нет
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="content-sidebar">
            <div class="content-card">
                <h2><i class="bi bi-info-circle"></i> Информация</h2>
                
                <div class="info-item" style="margin-bottom:0.75rem;">
                    <div class="info-label">ID покупателя</div>
                    <div class="info-value">#<?= $customer->id ?></div>
                </div>
                <div class="info-item" style="margin-bottom:0.75rem;">
                    <div class="info-label">Дата регистрации</div>
                    <div class="info-value"><?= Yii::$app->formatter->asDatetime($customer->created_at, 'medium') ?></div>
                </div>
                <div class="info-item" style="margin-bottom:0.75rem;">
                    <div class="info-label">Последний вход</div>
                    <div class="info-value"><?= $customer->last_login_at ? Yii::$app->formatter->asDatetime($customer->last_login_at, 'medium') : 'Нет данных' ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">IP последнего входа</div>
                    <div class="info-value"><?= Html::encode($customer->last_login_ip ?: 'Нет данных') ?></div>
                </div>
            </div>

            <div class="content-card">
                <h2><i class="bi bi-gear"></i> Действия</h2>
                
                <div class="action-buttons">
                    <button type="button" class="btn-action primary" onclick="linkOrders(<?= $customer->id ?>)">
                        <i class="bi bi-link-45deg"></i> Связать заказы
                    </button>
                    <button type="button" class="btn-action warning" onclick="resetPassword(<?= $customer->id ?>)">
                        <i class="bi bi-key"></i> Сбросить пароль
                    </button>
                    <button type="button" class="btn-action" onclick="toggleStatus(<?= $customer->id ?>)">
                        <i class="bi bi-<?= $customer->status == 10 ? 'lock' : 'unlock' ?>"></i>
                        <?= $customer->status == 10 ? 'Заблокировать' : 'Разблокировать' ?>
                    </button>
                    <a href="<?= Url::to(['customer/delete', 'id' => $customer->id]) ?>" class="btn-action danger" onclick="return confirm('Удалить покупателя?')">
                        <i class="bi bi-trash3"></i> Удалить
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleStatus(id) {
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
}

function resetPassword(id) {
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
}

function linkOrders(id) {
    fetch('/admin/customer/' + id + '/link-orders', {
        method: 'POST',
        headers: { 'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content }
    })
    .then(r => r.json())
    .then(data => {
        alert(data.message);
        if (data.success) location.reload();
    });
}
</script>
