<?php
/**
 * Новая карточка заказа — Shopify 2026 стиль
 * 
 * @var yii\web\View $this
 * @var app\backend\modules\checkout\models\Order $model
 */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Заказ №' . ($model->order_number ?: $model->id);
$user = Yii::$app->user->identity;
$statuses = $user->isLogist() ? Yii::$app->settings->getLogistStatuses() : Yii::$app->settings->getStatuses();
?>

<!-- Шапка заказа -->
<div class="admin-header">
    <div>
        <h1 class="admin-header-title">
            <i class="bi bi-box-seam"></i> <?= Html::encode($this->title) ?>
            <span class="admin-badge admin-badge-<?= $model->status ?>"><?= $statuses[$model->status] ?? $model->status ?></span>
        </h1>
        <p class="dash-subtitle">
            <?= Yii::$app->formatter->asDatetime($model->created_at, 'short') ?>
            <?php if ($model->creator): ?>• <?= Html::encode($model->creator->username) ?><?php endif; ?>
        </p>
    </div>
    <div class="admin-header-actions">
        <form method="post" action="<?= Url::to(['/admin/order/change-status', 'id' => $model->id]) ?>" style="display:inline">
            <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>
            <select name="status" class="admin-form-select" style="width:auto;display:inline" onchange="this.form.submit()">
                <?php foreach ($statuses as $key => $label): ?>
                    <option value="<?= $key ?>" <?= $model->status == $key ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </form>
        <button type="button" class="admin-btn admin-btn-secondary" onclick="openHistoryModal()">
            <i class="bi bi-clock-history"></i> История
        </button>
        <?php if (!$user->isLogist()): ?>
            <button type="button" class="admin-btn admin-btn-primary" id="toggleEditMode">
                <i class="bi bi-pencil"></i> Редактировать
            </button>
        <?php endif; ?>
    </div>
</div>

<!-- Карточки информации -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:16px;margin-bottom:24px">
    
    <!-- Клиент -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2 class="admin-card-title"><i class="bi bi-person"></i> Клиент</h2>
            <a href="<?= Url::to(['/admin/customer/view', 'id' => $model->customer_id]) ?>" class="admin-btn admin-btn-sm admin-btn-secondary">Открыть карточку</a>
        </div>
        <div class="admin-card-body">
            <p><strong><?= Html::encode($model->client_name) ?></strong></p>
            <p><i class="bi bi-telephone"></i> <?= Html::encode($model->client_phone) ?></p>
            <?php if ($model->client_email): ?>
                <p><i class="bi bi-envelope"></i> <?= Html::encode($model->client_email) ?></p>
            <?php endif; ?>
            <p><i class="bi bi-geo-alt"></i> <?= Html::encode($model->address) ?></p>
        </div>
    </div>
    
    <!-- Оплата -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2 class="admin-card-title"><i class="bi bi-credit-card"></i> Оплата</h2>
            <span class="admin-badge admin-badge-<?= $model->payment_status ? 'success' : 'warning' ?>">
                <?= $model->payment_status ? 'Оплачен' : 'Ожидает' ?>
            </span>
        </div>
        <div class="admin-card-body">
            <p><strong>Сумма заказа:</strong> <?= Yii::$app->formatter->asCurrency($model->total_amount, 'BYN') ?></p>
            <?php if ($model->paid_amount): ?>
                <p><strong>Оплачено:</strong> <?= Yii::$app->formatter->asCurrency($model->paid_amount, 'BYN') ?></p>
            <?php endif; ?>
            <p><strong>Способ:</strong> <?= $model->payment_method ?: 'Не указан' ?></p>
        </div>
    </div>
    
    <!-- Доставка -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2 class="admin-card-title"><i class="bi bi-truck"></i> Доставка</h2>
        </div>
        <div class="admin-card-body">
            <p><strong>Метод:</strong> <?= $model->delivery_method ?: 'Не указан' ?></p>
            <?php if ($model->track_number): ?>
                <p><strong>Трек-номер:</strong> <?= Html::encode($model->track_number) ?></p>
                <button class="admin-btn admin-btn-sm admin-btn-secondary" onclick="checkTracking('<?= $model->track_number ?>')">
                    <i class="bi bi-search"></i> Проверить
                </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Блоки доставки -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:24px">
    
    <!-- Блок 1: Международная доставка -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2 class="admin-card-title"><i class="bi bi-globe"></i> Международная доставка (Китай → РБ)</h2>
        </div>
        <div class="admin-card-body">
            <div class="form-group" style="margin-bottom:12px">
                <label style="display:block;margin-bottom:4px;font-weight:600;font-size:14px">Статус этапа</label>
                <select class="admin-form-select" style="width:100%">
                    <option>Заказано у поставщика</option>
                    <option>В пути из Китая</option>
                    <option>Таможня</option>
                    <option>Прибыл на склад РБ</option>
                </select>
            </div>
            <div class="form-group" style="margin-bottom:12px">
                <label style="display:block;margin-bottom:4px;font-weight:600;font-size:14px">Трек-номер (международный)</label>
                <div style="display:flex;gap:8px">
                    <input type="text" class="admin-form-input" placeholder="CN123456789" value="<?= Html::encode($model->track_number_china ?? '') ?>" style="flex:1">
                    <button class="admin-btn admin-btn-secondary" onclick="checkInternationalTrack()">
                        <i class="bi bi-search"></i> Проверить
                    </button>
                </div>
            </div>
            <div class="form-group">
                <label style="display:block;margin-bottom:4px;font-weight:600;font-size:14px">Дата прибытия на склад</label>
                <input type="date" class="admin-form-input" value="<?= $model->warehouse_arrival_date ?? '' ?>">
            </div>
        </div>
    </div>
    
    <!-- Блок 2: Местная доставка -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2 class="admin-card-title"><i class="bi bi-truck"></i> Местная доставка (РБ → Клиент)</h2>
        </div>
        <div class="admin-card-body">
            <div class="form-group" style="margin-bottom:12px">
                <label style="display:block;margin-bottom:4px;font-weight:600;font-size:14px">Служба доставки</label>
                <select class="admin-form-select" style="width:100%">
                    <option <?= $model->delivery_method === 'Европочта' ? 'selected' : '' ?>>Европочта</option>
                    <option <?= $model->delivery_method === 'Белпочта' ? 'selected' : '' ?>>Белпочта</option>
                    <option <?= $model->delivery_method === 'СДЭК' ? 'selected' : '' ?>>СДЭК</option>
                    <option <?= $model->delivery_method === 'Самовывоз' ? 'selected' : '' ?>>Самовывоз</option>
                    <option <?= $model->delivery_method === 'Курьер Минск' ? 'selected' : '' ?>>Курьер Минск</option>
                </select>
            </div>
            <div class="form-group" style="margin-bottom:12px">
                <label style="display:block;margin-bottom:4px;font-weight:600;font-size:14px">Адрес доставки</label>
                <textarea class="admin-form-input" rows="2" style="width:100%"><?= Html::encode($model->address) ?></textarea>
            </div>
            <div class="form-group" style="margin-bottom:12px">
                <label style="display:block;margin-bottom:4px;font-weight:600;font-size:14px">Трек-номер (локальный)</label>
                <div style="display:flex;gap:8px">
                    <input type="text" class="admin-form-input" placeholder="BY123456789" value="<?= Html::encode($model->track_number ?? '') ?>" style="flex:1">
                    <button class="admin-btn admin-btn-secondary" onclick="checkLocalTrack()">
                        <i class="bi bi-search"></i> Проверить
                    </button>
                </div>
            </div>
            <button class="admin-btn admin-btn-primary" style="width:100%" onclick="printDeliveryLabel()">
                <i class="bi bi-printer"></i> Распечатать бланк доставки
            </button>
        </div>
    </div>
</div>

<!-- Состав заказа -->
<div class="admin-card">
    <div class="admin-card-header">
        <h2 class="admin-card-title"><i class="bi bi-cart"></i> Состав заказа</h2>
        <button class="admin-btn admin-btn-sm admin-btn-primary" onclick="addOrderItem()">
            <i class="bi bi-plus"></i> Добавить товар
        </button>
    </div>
    <div class="admin-card-body">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Товар</th>
                    <th>Размер</th>
                    <th>Кол-во</th>
                    <th>Цена</th>
                    <th>Сумма</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($model->orderItems as $item): ?>
                <tr>
                    <td><?= Html::encode($item->product_name) ?></td>
                    <td><?= Html::encode($item->size) ?></td>
                    <td><?= $item->quantity ?></td>
                    <td><?= Yii::$app->formatter->asCurrency($item->price, 'BYN') ?></td>
                    <td><?= Yii::$app->formatter->asCurrency($item->price * $item->quantity, 'BYN') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div style="margin-top:16px;text-align:right;font-size:18px;font-weight:700">
            Итого: <?= Yii::$app->formatter->asCurrency($model->total_amount, 'BYN') ?>
        </div>
    </div>
</div>

<!-- Заметки -->
<div class="admin-card" style="margin-top:24px">
    <div class="admin-card-header">
        <h2 class="admin-card-title"><i class="bi bi-chat-left-text"></i> Заметки команды</h2>
    </div>
    <div class="admin-card-body">
        <div id="notes-container">
            <?php foreach ($model->notes ?? [] as $note): ?>
                <div class="note-item" style="padding:12px;border-bottom:1px solid var(--admin-border)">
                    <strong><?= $note['author'] ?></strong> 
                    <small style="color:var(--admin-text-secondary)"><?= $note['date'] ?></small>
                    <p style="margin:4px 0 0"><?= Html::encode($note['text']) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
        <div style="margin-top:16px;display:flex;gap:8px">
            <input type="text" id="new-note" class="admin-form-input" placeholder="Добавить заметку..." style="flex:1">
            <button class="admin-btn admin-btn-primary" onclick="addNote()">Добавить</button>
        </div>
    </div>
</div>

<!-- История (AJAX) -->
<div id="history-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:9999;align-items:center;justify-content:center">
    <div style="background:white;border-radius:16px;padding:24px;max-width:600px;width:90%;max-height:80vh;overflow:auto">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
            <h3>История заказа</h3>
            <button onclick="closeHistoryModal()" style="background:none;border:none;font-size:20px;cursor:pointer">×</button>
        </div>
        <div id="history-content">Загрузка...</div>
    </div>
</div>

<script>
function openHistoryModal() {
    document.getElementById('history-modal').style.display = 'flex';
    fetch('/admin/order-api/history?id=<?= $model->id ?>')
        .then(r => r.json())
        .then(data => {
            const html = data.history.map(h => `
                <div style="padding:12px;border-bottom:1px solid var(--admin-border)">
                    <strong>${h.created_by}</strong> 
                    <small style="color:var(--admin-text-secondary)">${h.created_at}</small>
                    <p style="margin:4px 0 0">${h.comment}</p>
                </div>
            `).join('');
            document.getElementById('history-content').innerHTML = html || '<p>Нет записей</p>';
        });
}
function closeHistoryModal() {
    document.getElementById('history-modal').style.display = 'none';
}
function checkTracking(track) {
    alert('Проверка трек-номера: ' + track);
}
function addNote() {
    const text = document.getElementById('new-note').value;
    if (!text) return;
    fetch('/admin/order-api/add-note?id=<?= $model->id ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'note=' + encodeURIComponent(text) + '&<?= Yii::$app->request->csrfParam ?>=<?= Yii::$app->request->csrfToken ?>'
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) location.reload();
    });
}
function checkInternationalTrack() {
    const track = document.querySelector('input[placeholder="CN123456789"]').value;
    if (!track) {
        alert('Введите трек-номер');
        return;
    }
    fetch('/admin/tracking/check-international?track=' + track)
        .then(r => r.json())
        .then(data => {
            alert('Статус: ' + (data.status || 'Не найден'));
        });
}
function checkLocalTrack() {
    const track = document.querySelector('input[placeholder="BY123456789"]').value;
    if (!track) {
        alert('Введите трек-номер');
        return;
    }
    fetch('/admin/tracking/check-local?track=' + track)
        .then(r => r.json())
        .then(data => {
            alert('Статус: ' + (data.status || 'Не найден'));
        });
}
function printDeliveryLabel() {
    window.open('/admin/pdf/delivery-label?id=<?= $model->id ?>', '_blank');
}
</script>
