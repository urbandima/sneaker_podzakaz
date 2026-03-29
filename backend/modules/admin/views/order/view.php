<?php

/** @var yii\web\View $this */
/** @var app\backend\modules\catalog\models\Order $model */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Заказ №' . ($model->order_number ?: $model->id);
$user = Yii::$app->user->identity;
$statuses = $user->isLogist() ? Yii::$app->settings->getLogistStatuses() : Yii::$app->settings->getStatuses();

// B0.2: CSS вынесен в registerCss()
$this->registerCss(<<<CSS
/* Улучшенный CRM-дизайн карточки заказа */
.order-page {
    background: #f5f6fa;
    min-height: 100vh;
    padding: 1rem;
}

.order-shell {
    max-width: 1400px;
    margin: 0 auto;
}

/* Верхняя панель со статусом */
.order-top-bar {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 14px;
    padding: 1rem 1.5rem;
    margin-bottom: 1rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}

.order-top-left {
    display: flex;
    align-items: center;
    gap: 1.5rem;
}

.order-top-left h1 {
    font-size: 1.5rem;
    font-weight: 800;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.order-top-meta {
    color: #6b7280;
    font-size: 0.8125rem;
}

.order-top-right {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    min-width: 280px;
}

/* Выпадающий статус */
.status-select-wrap {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.status-select-wrap label {
    font-weight: 600;
    font-size: 0.8125rem;
    color: #374151;
    white-space: nowrap;
}

.status-select {
    padding: 0.5rem 2.5rem 0.5rem 1rem;
    border: 2px solid #e5e7eb;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.875rem;
    background: #fff url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' fill=\'none\' viewBox=\'0 0 24 24\' stroke=\'%236b7280\'%3E%3Cpath stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M19 9l-7 7-7-7\'%3E%3C/path%3E%3C/svg%3E") no-repeat right 0.75rem center/1rem;
    appearance: none;
    cursor: pointer;
    min-width: 180px;
    transition: all 0.2s;
}

.status-select:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59,130,246,0.15);
}

.status-select.status-new { border-color: #3b82f6; color: #1d4ed8; }
.status-select.status-processing { border-color: #f59e0b; color: #d97706; }
.status-select.status-shipped { border-color: #8b5cf6; color: #7c3aed; }
.status-select.status-completed { border-color: #10b981; color: #059669; }
.status-select.status-cancelled { border-color: #ef4444; color: #dc2626; }

.btn-action {
    padding: 0.5rem 1rem;
    border: 1px solid #e5e7eb;
    background: #fff;
    color: #374151;
    border-radius: 8px;
    font-weight: 500;
    font-size: 0.875rem;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
}

.btn-action:hover {
    background: #f3f4f6;
    border-color: #d1d5db;
}

.btn-action-primary {
    background: #111827;
    border-color: #111827;
    color: #fff;
}

.btn-action-primary:hover {
    background: #374151;
    color: #fff;
}

/* Сетка контента */
.order-grid {
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 1rem;
}

.order-main {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.order-sidebar {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.order-secondary-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-top: 1rem;
}

@media (max-width: 1100px) {
    .order-grid {
        grid-template-columns: 1fr;
    }

    .order-top-right {
        width: 100%;
    }
}

.card-header {
    padding: 1rem 1.25rem;
    border-bottom: 1px solid #f3f4f6;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.card-header h3 {
    font-size: 1rem;
    font-weight: 700;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.card-body {
    padding: 1.25rem;
}

/* Таблица товаров */
.items-table {
    width: 100%;
    border-collapse: collapse;
}

.items-table th {
    text-align: left;
    padding: 0.75rem;
    font-size: 0.6875rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #6b7280;
    border-bottom: 2px solid #f3f4f6;
    font-weight: 600;
}

.items-table td {
    padding: 0.875rem 0.75rem;
    border-bottom: 1px solid #f3f4f6;
    font-size: 0.875rem;
}

.items-table tr:last-child td {
    border-bottom: none;
}

.items-table .item-name {
    font-weight: 600;
    color: #111827;
}

.items-table .item-price {
    font-weight: 700;
    color: #111827;
}

.items-total {
    background: #f9fafb;
    padding: 1rem 1.25rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-top: 2px solid #e5e7eb;
}

.items-total-label {
    font-weight: 600;
    color: #6b7280;
}

.items-total-value {
    font-size: 1.5rem;
    font-weight: 800;
    color: #111827;
}

.info-value.editable-field {
    cursor: pointer;
    position: relative;
    transition: background-color 0.2s;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
}

.info-value.editable-field:hover {
    background-color: #f3f4f6;
}

.info-value.editable-field::after {
    content: " ✏️";
    position: absolute;
    right: 0.25rem;
    top: 0.25rem;
    font-size: 0.75rem;
    opacity: 0;
    transition: opacity 0.2s;
}

.info-value.editable-field:hover::after {
    opacity: 0.5;
}

/* Inline редактирование */
.inline-edit-input {
    width: 100%;
    padding: 0.25rem 0.5rem;
    border: 2px solid #3b82f6;
    border-radius: 4px;
    font-size: 0.875rem;
    background: #fff;
    outline: none;
}

.inline-edit-textarea {
    width: 100%;
    min-height: 60px;
    padding: 0.5rem;
    border: 2px solid #3b82f6;
    border-radius: 4px;
    font-size: 0.875rem;
    background: #fff;
    outline: none;
    resize: vertical;
}

.inline-edit-actions {
    margin-top: 0.5rem;
    display: flex;
    gap: 0.5rem;
}

.inline-edit-actions button {
    padding: 0.25rem 0.75rem;
    border: none;
    border-radius: 4px;
    font-size: 0.75rem;
    cursor: pointer;
    transition: all 0.2s;
}

.inline-edit-save {
    background: #10b981;
    color: white;
}

.inline-edit-save:hover {
    background: #059669;
}

.inline-edit-cancel {
    background: #ef4444;
    color: white;
}

.inline-edit-cancel:hover {
    background: #dc2626;
}

/* Анимация сохранения */
.saving {
    opacity: 0.6;
    pointer-events: none;
}

.saving::after {
    content: " 💾";
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* Инфо-сетка */
.info-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
}

@media (max-width: 640px) {
    .info-grid {
        grid-template-columns: 1fr;
    }
}

.info-item {
    padding: 0.75rem;
    background: #f9fafb;
    border-radius: 8px;
}

.info-label {
    font-size: 0.6875rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #6b7280;
    margin-bottom: 0.25rem;
    font-weight: 600;
}

.info-value {
    font-size: 0.9375rem;
    color: #111827;
    font-weight: 500;
}

/* Боковая панель */
.sidebar-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 14px;
    margin-bottom: 1rem;
    overflow: hidden;
}

.sidebar-header {
    padding: 0.875rem 1rem;
    border-bottom: 1px solid #f3f4f6;
    font-weight: 700;
    font-size: 0.875rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.sidebar-body {
    padding: 1rem;
}

/* Таймлайн */
.timeline {
    position: relative;
    padding-left: 1.5rem;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 5px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #e5e7eb;
}

.timeline-item {
    position: relative;
    margin-bottom: 1rem;
}

.timeline-item:last-child {
    margin-bottom: 0;
}

.timeline-dot {
    position: absolute;
    left: -1.5rem;
    top: 0;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #e5e7eb;
    border: 2px solid #fff;
}

.timeline-item.active .timeline-dot {
    background: #3b82f6;
}

.timeline-content {
    font-size: 0.8125rem;
}

.timeline-title {
    font-weight: 600;
    color: #111827;
}

.timeline-meta {
    color: #6b7280;
    font-size: 0.75rem;
}

/* Публичная ссылка */
.public-link-wrap {
    display: flex;
    gap: 0.5rem;
}

.public-link-input {
    flex: 1;
    padding: 0.5rem 0.75rem;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    font-size: 0.8125rem;
    background: #f9fafb;
}

.btn-copy {
    padding: 0.5rem 0.75rem;
    border: 1px solid #e5e7eb;
    background: #fff;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-copy:hover {
    background: #f3f4f6;
}
CSS
);
?>

<div class="order-page">
    <div class="order-shell">
        <!-- Верхняя панель со статусом -->
        <div class="order-top-bar">
            <div class="order-top-left">
                <h1><i class="bi bi-box-seam"></i> <?= Html::encode($this->title) ?></h1>
                <div class="order-top-meta">
                    <?= Yii::$app->formatter->asDatetime($model->created_at, 'short') ?>
                    <?php if ($model->creator): ?>
                        • <?= Html::encode($model->creator->username) ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="order-top-right">
                <!-- Быстрая смена статуса -->
                <form method="post" action="<?= Url::to(['/admin/order/change-status', 'id' => $model->id]) ?>" id="quickStatusForm">
                    <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>
                    <div class="status-select-wrap">
                        <label>Статус:</label>
                        <select name="status" class="status-select status-<?= $model->status ?>" onchange="this.form.submit()">
                            <?php foreach ($statuses as $key => $label): ?>
                                <option value="<?= $key ?>" <?= $model->status == $key ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>

                <div class="public-link-inline">
                    <label>Публичная ссылка</label>
                    <div class="public-link-inline-fields">
                        <input type="text" value="<?= $model->getPublicUrl() ?>" id="public-link" readonly>
                        <button class="btn-action btn-secondary-light" type="button" onclick="copyLink('public-link', event)">
                            <i class="bi bi-clipboard"></i>
                        </button>
                    </div>
                </div>

                <div class="top-action-buttons">
                    <button type="button" class="btn-action btn-secondary-light" onclick="openHistoryModal()">
                        <i class="bi bi-clock-history"></i> История
                    </button>
                    <?php if (!$user->isLogist()): ?>
                        <button type="button" class="btn-action btn-action-primary" id="toggleEditMode">
                            <i class="bi bi-pencil"></i> <span id="editModeText">Редактировать</span>
                        </button>
                    <?php endif; ?>
                    <?= Html::a('<i class="bi bi-arrow-left"></i> К списку', ['/admin/order/index'], ['class' => 'btn-action']) ?>
                </div>
            </div>
        </div>

        <div class="order-grid">
            <div class="order-main">
                <!-- СОСТАВ ЗАКАЗА -->
                <div class="order-card">
                    <div class="card-header">
                        <h3><i class="bi bi-bag-check"></i> Состав заказа</h3>
                        <span style="font-size:0.8125rem;color:#6b7280;"><?= count($model->orderItems) ?> позиций</span>
                    </div>
                    <table class="items-table">
                        <thead>
                            <tr>
                                <th style="width:40px;">#</th>
                                <th>Наименование</th>
                                <th style="width:80px;">Кол-во</th>
                                <th style="width:100px;">Цена</th>
                                <th style="width:100px;">Сумма</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($model->orderItems as $index => $item): ?>
                            <tr>
                                <td style="color:#6b7280;"><?= $index + 1 ?></td>
                                <td class="item-name"><?= Html::encode($item->product_name) ?></td>
                                <td><?= $item->quantity ?> шт.</td>
                                <td><?= Yii::$app->formatter->asDecimal($item->price, 2) ?> Br</td>
                                <td class="item-price"><?= Yii::$app->formatter->asDecimal($item->total, 2) ?> Br</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div class="items-total">
                        <span class="items-total-label">Итого к оплате:</span>
                        <span class="items-total-value"><?= Yii::$app->formatter->asDecimal($model->total_amount, 2) ?> Br</span>
                    </div>
                </div>

                <!-- Информация о клиенте -->
                <div class="order-card">
                    <div class="card-header">
                        <h3><i class="bi bi-person"></i> Клиент</h3>
                    </div>
                    <div class="card-body">
                        <div id="viewMode">
                            <div class="info-grid">
                                <div class="info-item">
                                    <div class="info-label">ФИО</div>
                                    <div class="info-value editable-field" data-field="client_name" data-id="<?= $model->id ?>"><?= Html::encode($model->client_name ?: '-') ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Телефон</div>
                                    <div class="info-value editable-field" data-field="client_phone" data-id="<?= $model->id ?>"><?= Html::encode($model->client_phone ?: '-') ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Email</div>
                                    <div class="info-value editable-field" data-field="client_email" data-id="<?= $model->id ?>"><?= Html::encode($model->client_email ?: '-') ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Срок доставки</div>
                                    <div class="info-value editable-field" data-field="delivery_date" data-id="<?= $model->id ?>"><?= Html::encode($model->delivery_date ?: '-') ?></div>
                                </div>
                            </div>

                            <?php if ($model->comment): ?>
                            <div style="margin-top:1rem;padding:0.875rem;background:#fef3c7;border-radius:8px;">
                                <div style="font-weight:600;font-size:0.75rem;color:#92400e;margin-bottom:0.25rem;">КОММЕНТАРИЙ</div>
                                <div class="editable-field" data-field="comment" data-id="<?= $model->id ?>" style="color:#78350f;cursor:pointer;" title="Кликните для редактирования"><?= nl2br(Html::encode($model->comment)) ?></div>
                            </div>
                            <?php endif; ?>
                        </div>

                    <!-- Режим редактирования -->
                    <div id="editMode" style="display: none;">
                        <form method="post" action="<?= Url::to(['/admin/order/update', 'id' => $model->id]) ?>" id="orderEditForm">
                            <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">ФИО клиента</label>
                                        <input type="text" class="form-control" name="Order[client_name]" value="<?= Html::encode($model->client_name) ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Телефон</label>
                                        <input type="text" class="form-control" name="Order[client_phone]" value="<?= Html::encode($model->client_phone) ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Email</label>
                                        <input type="email" class="form-control" name="Order[client_email]" value="<?= Html::encode($model->client_email) ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Срок доставки</label>
                                        <input type="text" class="form-control" name="Order[delivery_date]" value="<?= Html::encode($model->delivery_date) ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Статус</label>
                                        <select class="form-select" name="Order[status]">
                                            <?php foreach (Yii::$app->settings->getStatuses() as $key => $label): ?>
                                                <option value="<?= $key ?>" <?= $model->status == $key ? 'selected' : '' ?>>
                                                    <?= $label ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Комментарий</label>
                                <textarea class="form-control" name="Order[comment]" rows="3" placeholder="Примечания к заказу..."><?= Html::encode($model->comment) ?></textarea>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-success">
                                    <i class="bi bi-check-circle"></i> Сохранить изменения
                                </button>
                                <button type="button" class="btn btn-secondary" id="cancelEdit">
                                    <i class="bi bi-x-circle"></i> Отмена
                                </button>
                            </div>
                        </form>
                    </div>

                    <div class="mt-3">
                        <p><strong>Публичная ссылка:</strong></p>
                        <div class="input-group">
                            <input type="text" class="form-control" value="<?= $model->getPublicUrl() ?>" id="public-link-alt" readonly>
                            <button class="btn btn-outline-secondary" type="button" onclick="copyLink('public-link-alt', event)">
                                <i class="bi bi-clipboard"></i> Копировать
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Товары -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Товары</h5>
                </div>
                <div class="card-body">
                    <!-- Режим просмотра товаров -->
                    <div id="viewModeItems">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>№</th>
                                    <th>Наименование</th>
                                    <th>Количество</th>
                                    <th>Цена</th>
                                    <th>Итого</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($model->orderItems as $index => $item): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><?= Html::encode($item->product_name) ?></td>
                                    <td><?= $item->quantity ?></td>
                                    <td><?= Yii::$app->formatter->asDecimal($item->price, 2) ?> BYN</td>
                                    <td><?= Yii::$app->formatter->asDecimal($item->total, 2) ?> BYN</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-active">
                                    <th colspan="4" class="text-end">ИТОГО:</th>
                                    <th><?= Yii::$app->formatter->asDecimal($model->total_amount, 2) ?> BYN</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- Режим редактирования товаров -->
                    <div id="editModeItems" style="display: none;">
                        <div id="order-items-edit">
                            <?php foreach ($model->orderItems as $index => $item): ?>
                            <div class="order-item row mb-3">
                                <div class="col-md-5">
                                    <label class="form-label">Название товара</label>
                                    <input type="text" class="form-control" name="OrderItem[<?= $index ?>][product_name]" value="<?= Html::encode($item->product_name) ?>">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Количество</label>
                                    <input type="number" class="form-control" name="OrderItem[<?= $index ?>][quantity]" value="<?= $item->quantity ?>" min="1">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Цена (BYN)</label>
                                    <input type="number" step="0.01" class="form-control" name="OrderItem[<?= $index ?>][price]" value="<?= $item->price ?>">
                                </div>
                                <div class="col-md-2 d-flex align-items-end">
                                    <button type="button" class="btn btn-outline-danger remove-item">Удалить</button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="btn btn-outline-primary" id="add-item-edit">+ Добавить товар</button>
                    </div>
                </div>
            </div>

        </div>

        <div class="order-sidebar">
            <!-- Статус -->
            <div class="sidebar-card">
                <div class="sidebar-header">
                    <i class="bi bi-toggles"></i> Управление статусом
                </div>
                <div class="sidebar-body">
                    <div class="mb-3">
                        <div class="info-label">Текущий статус</div>
                        <div class="badge bg-primary" style="font-size:0.8rem;"><?= $model->getStatusLabel() ?></div>
                    </div>

                    <form method="post" action="<?= Url::to(['/admin/order/change-status', 'id' => $model->id]) ?>">
                        <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>
                        
                        <div class="mb-3">
                            <label class="form-label">Новый статус</label>
                            <select name="status" class="form-select">
                                <?php
                                $statuses = $user->isLogist() ? Yii::$app->settings->getLogistStatuses() : Yii::$app->settings->getStatuses();
                                foreach ($statuses as $key => $label):
                                ?>
                                    <option value="<?= $key ?>" <?= $model->status == $key ? 'selected' : '' ?>>
                                        <?= $label ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Комментарий</label>
                            <textarea name="comment" class="form-control" rows="3" placeholder="Опционально..."></textarea>
                        </div>

                        <button type="submit" class="btn btn-success w-100">Сохранить</button>
                    </form>
                </div>
            </div>

            <!-- Назначение логиста (только для админа) -->
            <?php if ($user->isAdmin()): ?>
            <div class="sidebar-card">
                <div class="sidebar-header">
                    <i class="bi bi-truck"></i> Логистика
                </div>
                <div class="sidebar-body">
                    <p><strong>Текущий логист:</strong><br>
                    <?= $model->logist ? Html::encode($model->logist->username) : 'Не назначен' ?></p>

                    <form method="post" action="<?= Url::to(['/admin/order/assign-logist', 'id' => $model->id]) ?>">
                        <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>
                        
                        <div class="mb-3">
                            <label class="form-label">Назначить логиста</label>
                            <select name="logist_id" class="form-select">
                                <option value="">Не назначен</option>
                                <?php
                                try {
                                    $logists = \app\backend\modules\admin\models\User::find()->where(['role' => 'logist'])->all();
                                } catch (\Exception $e) {
                                    $logists = [];
                                }
                                foreach ($logists as $logist):
                                ?>
                                    <option value="<?= $logist->id ?>" <?= $model->assigned_logist == $logist->id ? 'selected' : '' ?>>
                                        <?= Html::encode($logist->username) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-warning w-100">Назначить</button>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <!-- Подтверждение оплаты -->
            <?php if ($model->payment_proof): ?>
            <div class="sidebar-card">
                <div class="sidebar-header">
                    <i class="bi bi-credit-card"></i> Подтверждение оплаты
                </div>
                <div class="sidebar-body">
                    <p class="text-muted small mb-2">
                        Загружено: <?= Yii::$app->formatter->asDatetime($model->payment_uploaded_at) ?>
                    </p>
                    <a href="<?= $model->payment_proof ?>" target="_blank" class="btn btn-outline-primary w-100">
                        <i class="bi bi-file-earmark-image"></i> Просмотреть файл
                    </a>

                    <?php if ($model->offer_accepted): ?>
                        <div class="alert alert-success mt-3 mb-0">
                            <i class="bi bi-check-circle"></i> Оферта принята
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Модалка истории -->
<div class="history-modal" id="historyModal">
    <div class="history-modal-content">
        <div class="history-modal-header">
            <h3><i class="bi bi-clock-history"></i> История изменений</h3>
            <button class="history-close-btn" type="button" onclick="closeHistoryModal()">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="history-modal-body">
            <?php if (!empty($model->history)): ?>
                <div class="timeline">
                    <?php foreach ($model->history as $history): ?>
                        <div class="timeline-item mb-4 <?= $history->new_status === $model->status ? 'active' : '' ?>">
                            <div class="timeline-dot"></div>
                            <div class="timeline-content">
                                <div class="timeline-title"><?= $history->getNewStatusLabel() ?></div>
                                <div class="timeline-meta">
                                    <?= Yii::$app->formatter->asDatetime($history->created_at) ?>
                                    <?php if ($history->changer): ?>
                                        • <?= Html::encode($history->changer->username) ?>
                                    <?php endif; ?>
                                </div>
                                <?php if ($history->comment): ?>
                                    <div class="mt-1"><?= Html::encode($history->comment) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-muted">История изменений пока пуста.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
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

// Inline редактирование полей
function makeFieldEditable(element) {
    element.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        if (element.classList.contains('saving')) return;
        
        const field = element.dataset.field;
        const id = element.dataset.id;
        const currentValue = element.textContent.trim();
        const isTextarea = field === 'comment';
        
        // Создаем поле ввода
        const input = document.createElement(isTextarea ? 'textarea' : 'input');
        input.type = 'text';
        input.className = isTextarea ? 'inline-edit-textarea' : 'inline-edit-input';
        input.value = currentValue;
        
        // Создаем кнопки действий
        const actions = document.createElement('div');
        actions.className = 'inline-edit-actions';
        actions.innerHTML = `
            <button type="button" class="inline-edit-save">Сохранить</button>
            <button type="button" class="inline-edit-cancel">Отмена</button>
        `;
        
        // Заменяем содержимое
        element.innerHTML = '';
        element.appendChild(input);
        element.appendChild(actions);
        element.classList.add('saving');
        
        // Фокус на поле ввода
        input.focus();
        input.select();
        
        // Обработчики
        const saveBtn = actions.querySelector('.inline-edit-save');
        const cancelBtn = actions.querySelector('.inline-edit-cancel');
        
        function save() {
            const newValue = input.value.trim();
            
            fetch('/admin/order/update-field?id=' + id, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: 'field=' + encodeURIComponent(field) + '&value=' + encodeURIComponent(newValue)
            })
            .then(response => response.json())
            .then(data => {
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
            .catch(error => {
                element.textContent = currentValue;
                element.classList.remove('saving');
                showNotification('Ошибка соединения', 'error');
            });
        }
        
        function cancel() {
            element.textContent = currentValue;
            element.classList.remove('saving');
        }
        
        saveBtn.addEventListener('click', save);
        cancelBtn.addEventListener('click', cancel);
        
        // Сохранение по Enter
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !isTextarea) {
                e.preventDefault();
                save();
            } else if (e.key === 'Escape') {
                e.preventDefault();
                cancel();
            }
        });
        
        // Отмена по клику вне поля
        document.addEventListener('click', function outsideClick(e) {
            if (!element.contains(e.target)) {
                cancel();
                document.removeEventListener('click', outsideClick);
            }
        });
    });
}

// Показ уведомлений
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 1rem 1.5rem;
        background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6'};
        color: white;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        z-index: 10000;
        animation: slideIn 0.3s ease;
    `;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Инициализация editable полей
document.addEventListener('DOMContentLoaded', function() {
    const editableFields = document.querySelectorAll('.editable-field');
    editableFields.forEach(makeFieldEditable);
});

// CSS анимации
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
`;
document.head.appendChild(style);
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
            if (viewMode.style.display === 'none') {
                // Возврат к просмотру
                viewMode.style.display = 'block';
                editMode.style.display = 'none';
                viewModeItems.style.display = 'block';
                editModeItems.style.display = 'none';
                editModeText.textContent = 'Редактировать';
                toggleBtn.className = 'btn btn-primary';
            } else {
                // Переход к редактированию
                viewMode.style.display = 'none';
                editMode.style.display = 'block';
                viewModeItems.style.display = 'none';
                editModeItems.style.display = 'block';
                editModeText.textContent = 'Отменить редактирование';
                toggleBtn.className = 'btn btn-warning';
            }
        });
    }
    
    if (cancelBtn) {
        cancelBtn.addEventListener('click', function() {
            viewMode.style.display = 'block';
            editMode.style.display = 'none';
            viewModeItems.style.display = 'block';
            editModeItems.style.display = 'none';
            editModeText.textContent = 'Редактировать';
            toggleBtn.className = 'btn btn-primary';
        });
    }
    
    // Добавление нового товара
    const addItemBtn = document.getElementById('add-item-edit');
    let itemIndex = <?= count($model->orderItems) ?>;
    
    if (addItemBtn) {
        addItemBtn.addEventListener('click', function() {
            const newItem = `
                <div class="order-item row mb-3">
                    <div class="col-md-5">
                        <label class="form-label">Название товара</label>
                        <input type="text" class="form-control" name="OrderItem[${itemIndex}][product_name]">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Количество</label>
                        <input type="number" class="form-control" name="OrderItem[${itemIndex}][quantity]" value="1" min="1">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Цена (BYN)</label>
                        <input type="number" step="0.01" class="form-control" name="OrderItem[${itemIndex}][price]">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="button" class="btn btn-outline-danger remove-item">Удалить</button>
                    </div>
                </div>
            `;
            
            document.getElementById('order-items-edit').insertAdjacentHTML('beforeend', newItem);
            itemIndex++;
            updateRemoveButtons();
        });
    }
    
    // Удаление товара
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-item') || e.target.closest('.remove-item')) {
            const btn = e.target.classList.contains('remove-item') ? e.target : e.target.closest('.remove-item');
            btn.closest('.order-item').remove();
            updateRemoveButtons();
        }
    });
    
    function updateRemoveButtons() {
        const items = document.querySelectorAll('#order-items-edit .order-item');
        if (items.length === 1) {
            items[0].querySelector('.remove-item').disabled = true;
        } else {
            items.forEach(item => {
                item.querySelector('.remove-item').disabled = false;
            });
        }
    }
    
    updateRemoveButtons();
});
</script>
