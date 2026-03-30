<?php
/**
 * Карточка клиента — Shopify 2026 стиль
 * @var yii\web\View $this
 * @var app\backend\modules\account\models\Customer $customer
 * @var array $orders
 * @var array $stats
 */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Клиент: ' . Html::encode($customer->getFullName());
$initials = mb_substr($customer->first_name, 0, 1) . mb_substr($customer->last_name ?? '', 0, 1);
?>

<!-- Шапка -->
<div class="admin-header">
    <div>
        <h1 class="admin-header-title">
            <i class="bi bi-person"></i> <?= Html::encode($this->title) ?>
            <span class="admin-badge admin-badge-<?= $customer->status == 1 ? 'success' : 'secondary' ?>">
                <?= $customer->status == 1 ? 'Активен' : 'Неактивен' ?>
            </span>
            <?php
            // RFM-бейдж (Recency, Frequency, Monetary)
            $rfmScore = 555; // Пример: высокий RFM
            $rfmClass = $rfmScore >= 444 ? 'success' : ($rfmScore >= 333 ? 'warning' : 'secondary');
            ?>
            <span class="admin-badge admin-badge-<?= $rfmClass ?>" title="RFM сегментация: Recency, Frequency, Monetary">
                <i class="bi bi-star-fill"></i> RFM: <?= $rfmScore ?>
            </span>
        </h1>
        <p class="dash-subtitle">
            Зарегистрирован: <?= Yii::$app->formatter->asDate($customer->created_at) ?>
        </p>
    </div>
    <div class="admin-header-actions">
        <a href="<?= Url::to(['/admin/order/create', 'customer_id' => $customer->id]) ?>" class="admin-btn admin-btn-primary">
            <i class="bi bi-plus-circle"></i> Новый заказ
        </a>
        <a href="<?= Url::to(['/admin/customer/update', 'id' => $customer->id]) ?>" class="admin-btn admin-btn-secondary">
            <i class="bi bi-pencil"></i> Редактировать
        </a>
    </div>
</div>

<!-- Профиль и статистика -->
<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-bottom:24px">
    
    <!-- Контакты -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2 class="admin-card-title"><i class="bi bi-person-badge"></i> Контактная информация</h2>
        </div>
        <div class="admin-card-body">
            <div style="display:flex;align-items:center;gap:16px;margin-bottom:16px">
                <div style="width:64px;height:64px;background:linear-gradient(135deg,var(--admin-accent),var(--admin-primary));border-radius:12px;display:flex;align-items:center;justify-content:center;color:white;font-size:24px;font-weight:700">
                    <?= $initials ?>
                </div>
                <div>
                    <p style="font-weight:700;margin:0"><?= Html::encode($customer->getFullName()) ?></p>
                    <p style="color:var(--admin-text-secondary);font-size:14px;margin:4px 0 0"><?= Html::encode($customer->email) ?></p>
                </div>
            </div>
            <div style="display:flex;flex-direction:column;gap:8px">
                <div style="display:flex;align-items:center;gap:8px">
                    <i class="bi bi-telephone" style="color:var(--admin-text-secondary)"></i>
                    <span><?= Html::encode($customer->phone) ?></span>
                </div>
                <?php if ($customer->default_address): ?>
                <div style="display:flex;align-items:center;gap:8px">
                    <i class="bi bi-geo-alt" style="color:var(--admin-text-secondary)"></i>
                    <span><?= Html::encode($customer->default_address) ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Статистика заказов -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2 class="admin-card-title"><i class="bi bi-graph-up"></i> Статистика</h2>
        </div>
        <div class="admin-card-body">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                <div style="text-align:center;padding:12px;background:var(--admin-bg);border-radius:8px">
                    <p style="font-size:24px;font-weight:700;margin:0;color:var(--admin-accent)"><?= $stats['total_orders'] ?? 0 ?></p>
                    <p style="font-size:12px;color:var(--admin-text-secondary);margin:4px 0 0">Заказов</p>
                </div>
                <div style="text-align:center;padding:12px;background:var(--admin-bg);border-radius:8px">
                    <p style="font-size:24px;font-weight:700;margin:0;color:var(--admin-success)"><?= Yii::$app->formatter->asCurrency($stats['total_amount'] ?? 0, 'BYN') ?></p>
                    <p style="font-size:12px;color:var(--admin-text-secondary);margin:4px 0 0">Сумма</p>
                </div>
            </div>
            <div style="margin-top:12px;padding:12px;background:var(--admin-bg);border-radius:8px">
                <p style="font-size:12px;color:var(--admin-text-secondary);margin:0 0 4px">Средний чек</p>
                <p style="font-size:18px;font-weight:700;margin:0"><?= Yii::$app->formatter->asCurrency($stats['avg_order'] ?? 0, 'BYN') ?></p>
            </div>
        </div>
    </div>
    
    <!-- Лояльность -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2 class="admin-card-title"><i class="bi bi-star"></i> Программа лояльности</h2>
        </div>
        <div class="admin-card-body">
            <div style="text-align:center;padding:16px;background:linear-gradient(135deg,#fef3c7,#fde68a);border-radius:12px;margin-bottom:12px">
                <p style="font-size:32px;font-weight:700;margin:0;color:#92400e"><?= $customer->loyalty_points ?? 0 ?></p>
                <p style="font-size:14px;color:#92400e;margin:4px 0 0">баллов</p>
            </div>
            <div style="display:flex;gap:8px">
                <button class="admin-btn admin-btn-sm admin-btn-secondary" style="flex:1" onclick="alert('Начислить баллы')">
                    <i class="bi bi-plus"></i> Начислить
                </button>
                <button class="admin-btn admin-btn-sm admin-btn-secondary" style="flex:1" onclick="alert('Списать баллы')">
                    <i class="bi bi-dash"></i> Списать
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Теги и паспортные данные -->
<div style="display:grid;grid-template-columns:2fr 1fr;gap:16px;margin-bottom:24px">
    
    <!-- Теги клиента -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2 class="admin-card-title"><i class="bi bi-tags"></i> Теги и категории</h2>
        </div>
        <div class="admin-card-body">
            <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:12px">
                <?php
                $tags = ['VIP', 'Оптовик', 'Постоянный клиент'];
                foreach ($tags as $tag):
                ?>
                    <span class="admin-badge admin-badge-info" style="cursor:pointer" onclick="removeTag('<?= $tag ?>')">
                        <?= $tag ?> <i class="bi bi-x"></i>
                    </span>
                <?php endforeach; ?>
            </div>
            <div style="display:flex;gap:8px">
                <input type="text" id="new-tag-input" class="admin-form-input" placeholder="Добавить тег..." style="flex:1">
                <button class="admin-btn admin-btn-primary" onclick="addTag()">
                    <i class="bi bi-plus"></i> Добавить
                </button>
            </div>
        </div>
    </div>
    
    <!-- Паспортные данные (только для admin) -->
    <?php if (Yii::$app->user->identity->role === 'admin'): ?>
    <div class="admin-card">
        <div class="admin-card-header">
            <h2 class="admin-card-title"><i class="bi bi-shield-lock"></i> Паспортные данные</h2>
        </div>
        <div class="admin-card-body">
            <div style="display:flex;flex-direction:column;gap:8px;font-size:14px">
                <div>
                    <span style="color:var(--admin-text-secondary)">Серия:</span>
                    <strong><?= $customer->passport_series ?? 'AB' ?></strong>
                </div>
                <div>
                    <span style="color:var(--admin-text-secondary)">Номер:</span>
                    <strong><?= $customer->passport_number ?? '1234567' ?></strong>
                </div>
                <div>
                    <span style="color:var(--admin-text-secondary)">Идентификационный:</span>
                    <strong><?= $customer->identification_number ?? '1234567A123PB4' ?></strong>
                </div>
                <div style="margin-top:8px">
                    <button class="admin-btn admin-btn-sm admin-btn-secondary" style="width:100%">
                        <i class="bi bi-pencil"></i> Редактировать
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
function addTag() {
    const input = document.getElementById('new-tag-input');
    const tag = input.value.trim();
    if (tag) {
        alert('Добавлен тег: ' + tag);
        input.value = '';
    }
}
function removeTag(tag) {
    if (confirm('Удалить тег "' + tag + '"?')) {
        alert('Тег удален: ' + tag);
    }
}
</script>

<!-- История заказов -->
<div class="admin-card">
    <div class="admin-card-header">
        <h2 class="admin-card-title"><i class="bi bi-clock-history"></i> История заказов</h2>
    </div>
    <div class="admin-card-body">
        <?php if (!empty($orders)): ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>№</th>
                        <th>Дата</th>
                        <th>Товары</th>
                        <th>Сумма</th>
                        <th>Статус</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                    <tr>
                        <td style="font-weight:600">#<?= $order->id ?></td>
                        <td><?= Yii::$app->formatter->asDate($order->created_at) ?></td>
                        <td><?= count($order->orderItems) ?> шт.</td>
                        <td style="font-weight:600"><?= Yii::$app->formatter->asCurrency($order->total_amount, 'BYN') ?></td>
                        <td>
                            <span class="admin-badge admin-badge-<?= $order->status === 'completed' ? 'success' : 'info' ?>">
                                <?= $order->getStatusLabel() ?>
                            </span>
                        </td>
                        <td>
                            <a href="<?= Url::to(['/admin/order/view', 'id' => $order->id]) ?>" class="admin-btn admin-btn-sm admin-btn-secondary">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="text-align:center;padding:32px;color:var(--admin-text-secondary)">Заказов пока нет</p>
        <?php endif; ?>
    </div>
</div>

<!-- Дополнительная информация и действия -->
<div style="display:grid;grid-template-columns:2fr 1fr;gap:16px;margin-top:24px">
    
    <!-- Информация о клиенте -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2 class="admin-card-title"><i class="bi bi-info-circle"></i> Информация</h2>
        </div>
        <div class="admin-card-body">
            <div style="display:grid;grid-template-columns:repeat(2, 1fr);gap:16px">
                <div>
                    <span style="color:var(--admin-text-secondary);font-size:12px">ID покупателя</span>
                    <p style="margin:4px 0 0;font-weight:600">#<?= $customer->id ?></p>
                </div>
                <div>
                    <span style="color:var(--admin-text-secondary);font-size:12px">Дата регистрации</span>
                    <p style="margin:4px 0 0;font-weight:600"><?= Yii::$app->formatter->asDatetime($customer->created_at, 'medium') ?></p>
                </div>
                <div>
                    <span style="color:var(--admin-text-secondary);font-size:12px">Последний вход</span>
                    <p style="margin:4px 0 0;font-weight:600"><?= $customer->last_login_at ? Yii::$app->formatter->asDatetime($customer->last_login_at, 'medium') : 'Нет данных' ?></p>
                </div>
                <div>
                    <span style="color:var(--admin-text-secondary);font-size:12px">IP последнего входа</span>
                    <p style="margin:4px 0 0;font-weight:600"><?= Html::encode($customer->last_login_ip ?: '—') ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Действия -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2 class="admin-card-title"><i class="bi bi-gear"></i> Действия</h2>
        </div>
        <div class="admin-card-body">
            <button class="admin-btn admin-btn-primary" style="width:100%;margin-bottom:8px" onclick="linkOrders(<?= $customer->id ?>)">
                <i class="bi bi-link-45deg"></i> Связать заказы
            </button>
            <button class="admin-btn admin-btn-secondary" style="width:100%;margin-bottom:8px" onclick="resetPassword(<?= $customer->id ?>)">
                <i class="bi bi-key"></i> Сбросить пароль
            </button>
            <button class="admin-btn admin-btn-secondary" style="width:100%;margin-bottom:8px" onclick="toggleStatus(<?= $customer->id ?>)">
                <i class="bi bi-<?= $customer->status == 10 ? 'lock' : 'unlock' ?>"></i>
                <?= $customer->status == 10 ? 'Заблокировать' : 'Разблокировать' ?>
            </button>
            <a href="<?= Url::to(['customer/delete', 'id' => $customer->id]) ?>" class="admin-btn admin-btn-danger" style="width:100%" onclick="return confirm('Удалить покупателя?')">
                <i class="bi bi-trash3"></i> Удалить
            </a>
        </div>
    </div>
</div>

<style>
/* ===== Modern Customer Profile Design ===== */
:root {
    --primary: #3b82f6;
    --primary-dark: #2563eb;
    --success: #10b981;
    --warning: #f59e0b;
    --danger: #ef4444;
    --gray-50: #f9fafb;
    --gray-100: #f3f4f6;
    --gray-200: #e5e7eb;
    --gray-300: #d1d5db;
    --gray-400: #9ca3af;
    --gray-500: #6b7280;
    --gray-600: #4b5563;
    --gray-700: #374151;
    --gray-800: #1f2937;
    --gray-900: #111827;
    --shadow-sm: 0 1px 2px 0 rgba(0,0,0,0.05);
    --shadow: 0 1px 3px 0 rgba(0,0,0,0.1);
    --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.1);
    --radius: 8px;
    --radius-lg: 12px;
    --radius-xl: 16px;
}

/* Header */
.admin-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-bottom: 24px;
    border-bottom: 1px solid var(--gray-200);
    margin-bottom: 24px;
}

.admin-header-title {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 24px;
    font-weight: 700;
    margin: 0;
}

.admin-header-actions {
    display: flex;
    gap: 12px;
}

/* Badges */
.admin-badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.admin-badge-success { background: #d1fae5; color: #065f46; }
.admin-badge-secondary { background: #f3f4f6; color: #6b7280; }
.admin-badge-warning { background: #fef3c7; color: #92400e; }

/* Buttons */
.admin-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    border-radius: var(--radius);
    font-size: 14px;
    font-weight: 500;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.2s;
    border: none;
}

.admin-btn-primary {
    background: var(--primary);
    color: white;
}

.admin-btn-primary:hover {
    background: var(--primary-dark);
}

.admin-btn-secondary {
    background: var(--gray-100);
    color: var(--gray-700);
}

.admin-btn-secondary:hover {
    background: var(--gray-200);
}

/* Layout */
.content-grid {
    display: grid;
    grid-template-columns: 1fr 320px;
    gap: 24px;
}

.content-main {
    display: flex;
    flex-direction: column;
    gap: 24px;
}

.content-sidebar {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

/* Cards */
.content-card {
    background: white;
    border: 1px solid var(--gray-200);
    border-radius: var(--radius-xl);
    padding: 24px;
    box-shadow: var(--shadow-sm);
}

.content-card h2 {
    margin: 0 0 20px;
    font-size: 16px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
}

.content-card h2 i {
    color: var(--primary);
}

/* Profile */
.profile-header {
    display: flex;
    gap: 20px;
    margin-bottom: 24px;
}

.profile-avatar {
    width: 80px;
    height: 80px;
    border-radius: var(--radius-xl);
    background: linear-gradient(135deg, var(--primary), #8b5cf6);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 28px;
    font-weight: 700;
}

.profile-info h3 {
    margin: 0 0 4px;
    font-size: 20px;
    font-weight: 600;
}

.profile-info .email {
    color: var(--gray-500);
    font-size: 14px;
    margin-bottom: 8px;
}

.status-badge {
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.status-badge.active {
    background: #d1fae5;
    color: #065f46;
}

.status-badge.inactive {
    background: #fee2e2;
    color: #991b1b;
}

/* Stats */
.stats-row {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    padding-top: 20px;
    border-top: 1px solid var(--gray-100);
}

.stat-item {
    text-align: center;
}

.stat-value {
    font-size: 24px;
    font-weight: 700;
    color: var(--gray-900);
}

.stat-label {
    font-size: 13px;
    color: var(--gray-500);
    margin-top: 4px;
}

/* Info Grid */
.info-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.info-label {
    font-size: 12px;
    color: var(--gray-500);
    text-transform: uppercase;
    font-weight: 500;
}

.info-value {
    font-size: 14px;
    color: var(--gray-800);
}

/* Orders List */
.orders-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.order-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px;
    background: var(--gray-50);
    border-radius: var(--radius-lg);
    text-decoration: none;
    color: inherit;
    transition: all 0.2s;
}

.order-item:hover {
    background: var(--gray-100);
    transform: translateX(4px);
}

.order-number {
    font-weight: 600;
    color: var(--gray-900);
}

.order-date {
    font-size: 13px;
    color: var(--gray-500);
}

.order-total {
    font-weight: 700;
    font-size: 15px;
}

.order-status {
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

/* Action Buttons */
.action-buttons {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.btn-action {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 16px;
    border: 1px solid var(--gray-200);
    border-radius: var(--radius);
    background: white;
    color: var(--gray-700);
    font-size: 14px;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
}

.btn-action:hover {
    background: var(--gray-50);
    border-color: var(--primary);
}

.btn-action.primary {
    background: var(--primary);
    color: white;
    border-color: var(--primary);
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

/* Empty State */
.empty-orders {
    text-align: center;
    padding: 48px;
    color: var(--gray-500);
}

/* Responsive */
@media (max-width: 1024px) {
    .content-grid {
        grid-template-columns: 1fr;
    }
    .stats-row {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 640px) {
    .admin-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 16px;
    }
    .stats-row {
        grid-template-columns: 1fr;
    }
    .info-grid {
        grid-template-columns: 1fr;
    }
    .order-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }
}
</style>

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
