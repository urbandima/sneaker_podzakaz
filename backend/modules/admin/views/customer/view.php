<?php

use yii\helpers\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var app\backend\modules\catalog\models\Customer $customer */
/** @var app\backend\modules\catalog\models\Order[] $orders */

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

            <!-- B6.3 Паспортные данные -->
            <?php
            $currentUser = Yii::$app->user->identity;
            $isAdmin = method_exists($currentUser, 'isAdmin') ? $currentUser->isAdmin() : ($currentUser->role === 'admin');
            if ($customer->passport_series || $customer->passport_number || $customer->passport_id):
            ?>
            <div class="content-card">
                <h2><i class="bi bi-person-vcard"></i> Паспортные данные</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Серия и номер</div>
                        <div class="info-value">
                            <?php if ($isAdmin): ?>
                                <?= Html::encode(($customer->passport_series ?? '') . ' ' . ($customer->passport_number ?? '')) ?>
                            <?php else: ?>
                                АВ **** *****
                            <?php endif ?>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Идент. номер</div>
                        <div class="info-value">
                            <?php if ($isAdmin): ?>
                                <?= Html::encode($customer->passport_id ?? '—') ?>
                            <?php else: ?>
                                * * * * * * * * * * *
                            <?php endif ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif ?>

            <!-- B6.4 Лояльность -->
            <div class="content-card">
                <h2><i class="bi bi-star-fill"></i> Программа лояльности</h2>
                <?php
                $points = 0;
                try {
                    $loyaltyPoints = \app\backend\modules\loyalty\models\LoyaltyPoints::find()
                        ->where(['customer_id' => $customer->id])->sum('points') ?: 0;
                    $points = (int)$loyaltyPoints;
                } catch (\Exception $e) {}
                $totalSpent = $customer->total_spent ?? 0;
                if ($totalSpent >= 50000) { $level = 'Platinum'; $levelColor = '#e5e4e2'; }
                elseif ($totalSpent >= 15000) { $level = 'Gold'; $levelColor = '#ffd700'; }
                elseif ($totalSpent >= 5000) { $level = 'Silver'; $levelColor = '#c0c0c0'; }
                else { $level = 'Bronze'; $levelColor = '#cd7f32'; }
                $nextLevel = ['Bronze'=>5000,'Silver'=>15000,'Gold'=>50000,'Platinum'=>50000];
                $progress = min(100, $nextLevel[$level] > 0 ? ($totalSpent / $nextLevel[$level] * 100) : 100);
                ?>
                <div style="display:flex;align-items:center;gap:1rem;margin-bottom:1rem">
                    <div style="font-size:2rem;font-weight:800;color:var(--admin-accent)"><?= number_format($points) ?> <small style="font-size:0.875rem;font-weight:500;color:var(--admin-text-secondary)">баллов</small></div>
                    <span style="padding:0.25rem 0.75rem;border-radius:20px;background:<?= $levelColor ?>;color:#1a1a1a;font-weight:700;font-size:0.8rem"><?= $level ?></span>
                </div>
                <div style="background:#e1e3e5;border-radius:99px;height:8px;margin-bottom:1.5rem">
                    <div style="background:var(--admin-accent);height:8px;border-radius:99px;width:<?= $progress ?>%"></div>
                </div>
                <div style="display:flex;gap:0.5rem;margin-bottom:1rem">
                    <button class="admin-btn admin-btn-primary admin-btn-sm" onclick="togglePointsForm('add')">
                        <i class="bi bi-plus-circle"></i> Начислить
                    </button>
                    <button class="admin-btn admin-btn-secondary admin-btn-sm" onclick="togglePointsForm('deduct')">
                        <i class="bi bi-dash-circle"></i> Списать
                    </button>
                </div>
                <div id="points-form" style="display:none;background:#f9fafb;border:1px solid #e5e7eb;border-radius:10px;padding:1rem">
                    <input type="number" id="points-amount" class="form-control" placeholder="Кол-во баллов" min="1" style="margin-bottom:0.5rem">
                    <input type="text" id="points-comment" class="form-control" placeholder="Комментарий (обязательно)" style="margin-bottom:0.5rem">
                    <button id="points-submit-btn" class="admin-btn admin-btn-primary admin-btn-sm" onclick="submitPoints(<?= $customer->id ?>)">Применить</button>
                    <button class="admin-btn admin-btn-secondary admin-btn-sm" onclick="document.getElementById('points-form').style.display='none'">Отмена</button>
                </div>
            </div>

            <!-- B6.5 Заметки команды -->
            <div class="content-card">
                <h2><i class="bi bi-chat-square-text"></i> Заметки команды</h2>
                <div id="customer-notes-list" style="display:flex;flex-direction:column;gap:0.5rem;margin-bottom:1rem">
                    <?php foreach (($customerNotes ?? []) as $note): ?>
                    <div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:0.75rem">
                        <div style="display:flex;justify-content:space-between;margin-bottom:0.25rem">
                            <strong style="font-size:0.8rem"><?= Html::encode($note->author->username ?? 'Система') ?></strong>
                            <span style="font-size:0.75rem;color:#9ca3af"><?= Yii::$app->formatter->asDatetime($note->created_at) ?></span>
                        </div>
                        <p style="margin:0;font-size:0.875rem"><?= Html::encode($note->text) ?></p>
                    </div>
                    <?php endforeach ?>
                    <?php if (empty($customerNotes)): ?><p style="color:#9ca3af;font-size:0.875rem">Заметок нет</p><?php endif ?>
                </div>
                <div style="display:flex;gap:0.5rem">
                    <textarea id="customer-note-text" class="form-control" rows="2" placeholder="Добавить заметку..." style="resize:vertical"></textarea>
                    <button class="admin-btn admin-btn-primary" onclick="addCustomerNote(<?= $customer->id ?>)"><i class="bi bi-send"></i></button>
                </div>
            </div>

            <!-- B6.7 Теги -->
            <div class="content-card">
                <h2><i class="bi bi-tags-fill"></i> Теги</h2>
                <div id="customer-tags" style="display:flex;flex-wrap:wrap;gap:0.5rem;margin-bottom:1rem">
                    <?php foreach (($customerTags ?? []) as $tag): ?>
                    <span class="admin-badge admin-badge-info" style="cursor:pointer" onclick="removeTag(<?= $customer->id ?>, '<?= Html::encode($tag) ?>')">
                        <?= Html::encode($tag) ?> <i class="bi bi-x"></i>
                    </span>
                    <?php endforeach ?>
                    <?php $presetTags = ['VIP','Оптовик','Проблемный'] ?>
                    <?php foreach ($presetTags as $pt): if (!in_array($pt, ($customerTags ?? []))): ?>
                    <button class="admin-btn admin-btn-secondary admin-btn-sm" onclick="addTag(<?= $customer->id ?>, '<?= $pt ?>')">
                        + <?= $pt ?>
                    </button>
                    <?php endif; endforeach ?>
                </div>
                <div style="display:flex;gap:0.5rem">
                    <input type="text" id="custom-tag-input" class="form-control" placeholder="Кастомный тег..." style="max-width:200px">
                    <button class="admin-btn admin-btn-secondary admin-btn-sm" onclick="addTag(<?= $customer->id ?>, document.getElementById('custom-tag-input').value)">Добавить</button>
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

// B6.4 Points
var _pointsAction = 'add';
function togglePointsForm(action) {
    _pointsAction = action;
    document.getElementById('points-submit-btn').textContent = action === 'add' ? 'Начислить' : 'Списать';
    document.getElementById('points-form').style.display = 'block';
    document.getElementById('points-amount').focus();
}

function submitPoints(customerId) {
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
}

// B6.5 Notes
function addCustomerNote(customerId) {
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
}

// B6.7 Tags
function addTag(customerId, tag) {
    tag = (tag || '').trim();
    if (!tag) return;
    fetch('/admin/customer/update-tags', {
        method: 'POST',
        headers: {'Content-Type':'application/json','X-CSRF-Token': document.querySelector('meta[name=csrf-token]')?.content||''},
        body: JSON.stringify({id: customerId, action: 'add', tag})
    }).then(r=>r.json()).then(d=>{ if(d.success) location.reload(); });
}

function removeTag(customerId, tag) {
    fetch('/admin/customer/update-tags', {
        method: 'POST',
        headers: {'Content-Type':'application/json','X-CSRF-Token': document.querySelector('meta[name=csrf-token]')?.content||''},
        body: JSON.stringify({id: customerId, action: 'remove', tag})
    }).then(r=>r.json()).then(d=>{ if(d.success) location.reload(); });
}
</script>
