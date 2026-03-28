<?php

/** @var yii\web\View $this */
/** @var app\backend\modules\catalog\models\Order $model */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Заказ №' . $model->order_number;
$user = Yii::$app->user->identity;
$statuses = Yii::$app->settings->getStatuses();
$canEdit = !$user->isLogist();
$isEditing = $canEdit && !empty($editing);
$inputDisabled = ($canEdit && $isEditing) ? '' : 'disabled';
$logists = $user->isAdmin()
    ? (function() {
        try {
            return \app\backend\modules\admin\models\User::find()->where(['role' => 'logist'])->orderBy(['username' => SORT_ASC])->all();
        } catch (\Exception $e) {
            return [];
        }
    })()
    : [];
$itemCount = count($model->orderItems);
$amoDealId = ($model->source === 'amocrm' && $model->source_id) ? $model->source_id : null;
$amoBase = Yii::$app->params['amocrmDealBaseUrl'] ?? 'https://www.amocrm.ru/leads/detail';
$amoDealUrl = $amoDealId ? rtrim($amoBase, '/') . '/' . $amoDealId : null;
?>

<style>
:root {
    --bg: #f8fafc;
    --panel: #ffffff;
    --border: #e2e8f0;
    --text: #1e293b;
    --muted: #64748b;
    --accent: #3b82f6;
    --accent-soft: #dbeafe;
    --success: #10b981;
    --success-soft: #d1fae5;
    --danger: #ef4444;
    --danger-soft: #fee2e2;
    --warning: #f59e0b;
    --warning-soft: #fef3c7;
}

.order-view {
    background: var(--bg);
    min-height: 100vh;
    padding: 20px;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    color: var(--text);
}

.view-header {
    max-width: 1200px;
    margin: 0 auto 30px;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 20px;
    flex-wrap: wrap;
}

.view-title-section {
    flex: 1;
}

.view-title {
    font-size: 2rem;
    font-weight: 700;
    margin: 0 0 8px 0;
    color: var(--text);
}

.view-meta {
    color: var(--muted);
    font-size: 0.875rem;
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
}

.view-actions {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.btn {
    border-radius: 8px;
    padding: 10px 20px;
    border: 1px solid var(--border);
    font-weight: 600;
    font-size: 0.875rem;
    background: var(--panel);
    color: var(--text);
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s ease;
    cursor: pointer;
}

.btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.btn--primary {
    background: var(--accent);
    border-color: var(--accent);
    color: white;
}

.btn--primary:hover {
    background: #2563eb;
    border-color: #2563eb;
    color: white;
}

.btn--success {
    background: var(--success);
    border-color: var(--success);
    color: white;
}

.btn--danger {
    background: var(--danger);
    border-color: var(--danger);
    color: white;
}

.btn--secondary {
    background: var(--panel);
    color: var(--muted);
    border-color: var(--border);
}

.metrics-grid {
    max-width: 1200px;
    margin: 0 auto 30px;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 16px;
}

.metric-card {
    background: var(--panel);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 20px;
    position: relative;
    overflow: hidden;
}

.metric-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--accent), var(--success));
}

.metric-card--accent::before {
    background: var(--accent);
}

.metric-card--success::before {
    background: var(--success);
}

.metric-card--warning::before {
    background: var(--warning);
}

.metric-label {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--muted);
    margin-bottom: 8px;
    font-weight: 600;
}

.metric-value {
    font-size: 1.875rem;
    font-weight: 700;
    color: var(--text);
    margin-bottom: 4px;
}

.metric-note {
    font-size: 0.875rem;
    color: var(--muted);
}

.view-container {
    max-width: 1200px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: 1fr 320px;
    gap: 30px;
}

.view-main {
    display: flex;
    flex-direction: column;
    gap: 24px;
}

.view-sidebar {
    display: flex;
    flex-direction: column;
    gap: 24px;
}

.panel {
    background: var(--panel);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 24px;
}

.panel-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 16px;
    margin-bottom: 20px;
    padding-bottom: 16px;
    border-bottom: 1px solid var(--border);
}

.panel-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--text);
    display: flex;
    align-items: center;
    gap: 8px;
}

.panel-subtitle {
    font-size: 0.875rem;
    color: var(--muted);
    margin-top: 4px;
}

.info-grid {
    display: grid;
    gap: 16px;
}

.info-grid--2 {
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
}

.info-grid--3 {
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.info-label {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--muted);
    font-weight: 600;
}

.info-value {
    font-size: 0.875rem;
    color: var(--text);
    word-break: break-word;
}

.info-value--empty {
    color: var(--muted);
    font-style: italic;
}

.info-value--link {
    color: var(--accent);
    text-decoration: none;
}

.info-value--link:hover {
    text-decoration: underline;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 100px;
    font-weight: 600;
    font-size: 0.875rem;
    background: var(--accent-soft);
    color: var(--accent);
}

.status-badge--success {
    background: var(--success-soft);
    color: var(--success);
}

.status-badge--warning {
    background: var(--warning-soft);
    color: var(--warning);
}

.status-badge--danger {
    background: var(--danger-soft);
    color: var(--danger);
}

.products-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 16px;
}

.products-table th {
    background: var(--bg);
    padding: 12px;
    text-align: left;
    font-weight: 600;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--muted);
    border-bottom: 2px solid var(--border);
}

.products-table td {
    padding: 16px 12px;
    border-bottom: 1px solid var(--border);
    font-size: 0.875rem;
}

.products-table tfoot td {
    font-weight: 700;
    font-size: 1rem;
    background: var(--bg);
    border-bottom: 2px solid var(--border);
}

.form-field {
    display: flex;
    flex-direction: column;
    gap: 6px;
    margin-bottom: 16px;
}

.form-field label {
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--text);
}

.form-field input,
.form-field textarea,
.form-field select {
    border: 1px solid var(--border);
    border-radius: 6px;
    padding: 10px 12px;
    font-size: 0.875rem;
    color: var(--text);
    background: var(--panel);
    transition: all 0.2s ease;
    width: 100%;
}

.form-field textarea {
    min-height: 80px;
    resize: vertical;
}

.form-field input:focus,
.form-field textarea:focus,
.form-field select:focus {
    outline: none;
    border-color: var(--accent);
    box-shadow: 0 0 0 3px var(--accent-soft);
}

.form-field input:disabled,
.form-field textarea:disabled,
.form-field select:disabled {
    background: var(--bg);
    color: var(--muted);
    cursor: not-allowed;
}

.timeline {
    position: relative;
    padding-left: 24px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 8px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: var(--border);
}

.timeline-item {
    position: relative;
    padding-bottom: 20px;
}

.timeline-item::before {
    content: '';
    position: absolute;
    left: -16px;
    top: 4px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: var(--accent);
    border: 2px solid var(--panel);
    box-shadow: 0 0 0 2px var(--accent-soft);
}

.timeline-content {
    background: var(--bg);
    padding: 12px;
    border-radius: 8px;
    border: 1px solid var(--border);
}

.timeline-title {
    font-weight: 600;
    font-size: 0.875rem;
    margin-bottom: 4px;
}

.timeline-meta {
    font-size: 0.75rem;
    color: var(--muted);
    margin-bottom: 4px;
}

.timeline-comment {
    font-size: 0.875rem;
    color: var(--text);
}

.finance-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.finance-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid var(--border);
}

.finance-item:last-child {
    border-bottom: none;
    padding-top: 16px;
    font-weight: 700;
    font-size: 1.125rem;
    color: var(--text);
}

.finance-label {
    font-size: 0.875rem;
    color: var(--muted);
}

.finance-value {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--text);
}

.finance-item:last-child .finance-value {
    font-size: 1.125rem;
}

.public-link {
    display: flex;
    gap: 8px;
}

.public-link input {
    flex: 1;
    padding: 8px 12px;
    border: 1px solid var(--border);
    border-radius: 6px;
    font-size: 0.875rem;
    background: var(--bg);
    color: var(--text);
}

.copy-btn {
    padding: 8px 12px;
    border: 1px solid var(--border);
    border-radius: 6px;
    background: var(--panel);
    color: var(--text);
    font-weight: 600;
    font-size: 0.875rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.copy-btn:hover {
    background: var(--accent-soft);
    color: var(--accent);
}

.amo-card {
    background: linear-gradient(135deg, #1f3d78, #102044);
    color: white;
    border: none;
}

.amo-card .panel-title,
.amo-card .panel-subtitle,
.amo-card .metric-label,
.amo-card .metric-note {
    color: rgba(255, 255, 255, 0.8);
}

.amo-card .panel-title,
.amo-card .metric-value {
    color: white;
}

.amo-card .btn {
    background: rgba(255, 255, 255, 0.2);
    border-color: rgba(255, 255, 255, 0.3);
    color: white;
}

.amo-card .btn:hover {
    background: rgba(255, 255, 255, 0.3);
    color: white;
}

.collapsible-section {
    margin-top: 20px;
}

.collapsible-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px;
    background: var(--bg);
    border-radius: 8px;
    cursor: pointer;
    user-select: none;
    margin-bottom: 12px;
    transition: all 0.2s ease;
}

.collapsible-header:hover {
    background: var(--accent-soft);
}

.collapsible-title {
    font-weight: 600;
    font-size: 0.875rem;
    color: var(--text);
}

.collapsible-icon {
    transition: transform 0.2s ease;
    color: var(--muted);
}

.collapsible-section--collapsed .collapsible-icon {
    transform: rotate(-90deg);
}

.collapsible-content {
    display: grid;
    gap: 16px;
}

.collapsible-section--collapsed .collapsible-content {
    display: none;
}

.edit-mode-indicator {
    background: var(--warning-soft);
    border: 1px solid var(--warning);
    color: #92400e;
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.875rem;
    font-weight: 600;
}

.save-indicator {
    position: fixed;
    bottom: 20px;
    right: 20px;
    background: var(--success);
    color: white;
    padding: 12px 16px;
    border-radius: 8px;
    font-size: 0.875rem;
    font-weight: 600;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    display: none;
    align-items: center;
    gap: 8px;
    z-index: 1000;
}

.save-indicator--show {
    display: flex;
}

@media (max-width: 1024px) {
    .view-container {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .view-sidebar {
        order: -1;
    }
    
    .metrics-grid {
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 12px;
    }
    
    .metric-card {
        padding: 16px;
    }
    
    .metric-value {
        font-size: 1.5rem;
    }
}

@media (max-width: 640px) {
    .order-view {
        padding: 12px;
    }
    
    .view-header {
        flex-direction: column;
        align-items: stretch;
        gap: 16px;
    }
    
    .view-title {
        font-size: 1.5rem;
    }
    
    .view-actions {
        justify-content: stretch;
    }
    
    .btn {
        flex: 1;
        justify-content: center;
    }
    
    .info-grid--2,
    .info-grid--3 {
        grid-template-columns: 1fr;
    }
    
    .panel {
        padding: 16px;
    }
    
    .products-table {
        font-size: 0.75rem;
    }
    
    .products-table th,
    .products-table td {
        padding: 8px 4px;
    }
}
</style>

<div class="order-view">
    <header class="view-header">
        <div class="view-title-section">
            <h1 class="view-title"><?= Html::encode($this->title) ?></h1>
            <div class="view-meta">
                <span>📅 Создан: <?= Yii::$app->formatter->asDatetime($model->created_at, 'short') ?></span>
                <span>🔄 Обновлён: <?= Yii::$app->formatter->asDatetime($model->updated_at, 'short') ?></span>
                <span>👤 <?= Html::encode($model->client_name ?: 'Клиент не указан') ?></span>
            </div>
        </div>
        <div class="view-actions">
            <a href="<?= Url::to(['/admin/order/index']) ?>" class="btn btn--secondary">
                ← Список заказов
            </a>
            <?php if ($canEdit): ?>
                <?php if ($isEditing): ?>
                    <button type="submit" form="orderUpdateForm" class="btn btn--success">
                        💾 Сохранить изменения
                    </button>
                    <a href="<?= Url::to(['/admin/order/view', 'id' => $model->id]) ?>" class="btn btn--secondary">
                        ❌ Отменить
                    </a>
                <?php else: ?>
                    <a href="<?= Url::to(['/admin/order/view', 'id' => $model->id, 'editing' => 1]) ?>" class="btn btn--primary">
                        ✏️ Редактировать
                    </a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </header>

    <section class="metrics-grid">
        <div class="metric-card metric-card--accent">
            <div class="metric-label">💰 Сумма заказа</div>
            <div class="metric-value"><?= Yii::$app->formatter->asDecimal($model->total_amount, 2) ?> BYN</div>
            <div class="metric-note">Доставка: <?= Yii::$app->formatter->asDecimal((float)$model->delivery_cost, 2) ?> BYN</div>
        </div>
        <div class="metric-card metric-card--success">
            <div class="metric-label">📦 Позиции</div>
            <div class="metric-value"><?= $itemCount ?></div>
            <div class="metric-note"><?= Html::encode($model->source ?? 'Источник не указан') ?></div>
        </div>
        <div class="metric-card metric-card--warning">
            <div class="metric-label">🎯 Статус</div>
            <div class="metric-value">
                <span class="status-badge"><?= Html::encode($model->getStatusLabel()) ?></span>
            </div>
            <div class="metric-note"><?= Html::encode($model->comment ?: 'Комментариев нет') ?></div>
        </div>
        <?php if ($amoDealId): ?>
        <div class="metric-card amo-card">
            <div class="metric-label">🔗 amoCRM</div>
            <div class="metric-value">#<?= Html::encode($amoDealId) ?></div>
            <div class="metric-note">
                <?php if ($amoDealUrl): ?>
                    <a href="<?= Html::encode($amoDealUrl) ?>" target="_blank" class="btn" style="padding: 6px 12px; font-size: 0.75rem;">
                        Открыть сделку →
                    </a>
                <?php else: ?>
                    Сделка привязана
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </section>

    <div class="view-container">
        <main class="view-main">
            <?php if ($canEdit && $isEditing): ?>
                <div class="edit-mode-indicator">
                    <span>⚠️</span>
                    <span>Режим редактирования - внесите изменения и нажмите "Сохранить изменения"</span>
                </div>
                <form id="orderUpdateForm" method="post" action="<?= Url::to(['/admin/order/update', 'id' => $model->id]) ?>">
                    <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>
            <?php endif; ?>

            <section class="panel">
                <div class="panel-header">
                    <div>
                        <h2 class="panel-title">
                            📞 Клиент и доставка
                        </h2>
                        <div class="panel-subtitle">Контактные данные и параметры получения</div>
                    </div>
                </div>
                <div class="info-grid info-grid--3">
                    <div class="info-item">
                        <div class="info-label">ФИО клиента</div>
                        <?php if ($isEditing): ?>
                            <div class="form-field">
                                <input type="text" name="Order[client_name]" value="<?= Html::encode($model->client_name) ?>">
                            </div>
                        <?php else: ?>
                            <div class="info-value"><?= Html::encode($model->client_name ?: 'Не указано') ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Телефон</div>
                        <?php if ($isEditing): ?>
                            <div class="form-field">
                                <input type="text" name="Order[client_phone]" value="<?= Html::encode($model->client_phone) ?>">
                            </div>
                        <?php else: ?>
                            <div class="info-value"><?= Html::encode($model->client_phone ?: 'Не указан') ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Email</div>
                        <?php if ($isEditing): ?>
                            <div class="form-field">
                                <input type="email" name="Order[client_email]" value="<?= Html::encode($model->client_email) ?>">
                            </div>
                        <?php else: ?>
                            <div class="info-value"><?= Html::encode($model->client_email ?: 'Не указан') ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Способ доставки</div>
                        <?php if ($isEditing): ?>
                            <div class="form-field">
                                <input type="text" name="Order[delivery_method]" value="<?= Html::encode($model->delivery_method) ?>">
                            </div>
                        <?php else: ?>
                            <div class="info-value"><?= Html::encode($model->delivery_method ?: 'Не указан') ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Дата доставки</div>
                        <?php if ($isEditing): ?>
                            <div class="form-field">
                                <input type="date" name="Order[delivery_date]" value="<?= Html::encode($model->delivery_date) ?>">
                            </div>
                        <?php else: ?>
                            <div class="info-value"><?= Html::encode($model->delivery_date ?: 'Не указана') ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Страна</div>
                        <?php if ($isEditing): ?>
                            <div class="form-field">
                                <input type="text" name="Order[delivery_country]" value="<?= Html::encode($model->delivery_country) ?>">
                            </div>
                        <?php else: ?>
                            <div class="info-value"><?= Html::encode($model->delivery_country ?: 'Не указана') ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Город</div>
                        <?php if ($isEditing): ?>
                            <div class="form-field">
                                <input type="text" name="Order[city]" value="<?= Html::encode($model->city) ?>">
                            </div>
                        <?php else: ?>
                            <div class="info-value"><?= Html::encode($model->city ?: 'Не указан') ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Область</div>
                        <?php if ($isEditing): ?>
                            <div class="form-field">
                                <input type="text" name="Order[region]" value="<?= Html::encode($model->region) ?>">
                            </div>
                        <?php else: ?>
                            <div class="info-value"><?= Html::encode($model->region ?: 'Не указана') ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Почтовый индекс</div>
                        <?php if ($isEditing): ?>
                            <div class="form-field">
                                <input type="text" name="Order[postal_code]" value="<?= Html::encode($model->postal_code) ?>">
                            </div>
                        <?php else: ?>
                            <div class="info-value"><?= Html::encode($model->postal_code ?: 'Не указан') ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="info-grid info-grid--2" style="margin-top: 20px;">
                    <div class="info-item">
                        <div class="info-label">Полный адрес</div>
                        <?php if ($isEditing): ?>
                            <div class="form-field">
                                <textarea name="Order[full_address]" rows="3"><?= Html::encode($model->full_address) ?></textarea>
                            </div>
                        <?php else: ?>
                            <div class="info-value"><?= Html::encode($model->full_address ?: 'Не указан') ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Комментарий менеджера</div>
                        <?php if ($isEditing): ?>
                            <div class="form-field">
                                <textarea name="Order[comment]" rows="3"><?= Html::encode($model->comment) ?></textarea>
                            </div>
                        <?php else: ?>
                            <div class="info-value"><?= Html::encode($model->comment ?: 'Нет комментариев') ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <section class="panel">
                <div class="panel-header">
                    <div>
                        <h2 class="panel-title">
                            🛍️ Состав заказа (<?= $itemCount ?>)
                        </h2>
                        <div class="panel-subtitle">Позиции и стоимости</div>
                    </div>
                </div>
                <?php if ($isEditing): ?>
                    <?= $this->render('_order_items', [
                        'orderItems' => $model->orderItems,
                    ]) ?>
                <?php else: ?>
                    <table class="products-table">
                        <thead>
                            <tr>
                                <th>Название товара</th>
                                <th>Кол-во</th>
                                <th>Цена, BYN</th>
                                <th>Сумма, BYN</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($model->orderItems as $item): ?>
                                <tr>
                                    <td><?= Html::encode($item->product_name) ?></td>
                                    <td><?= $item->quantity ?></td>
                                    <td><?= Yii::$app->formatter->asDecimal($item->price, 2) ?></td>
                                    <td><?= Yii::$app->formatter->asDecimal($item->total, 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3">Итого</td>
                                <td><?= Yii::$app->formatter->asDecimal($model->total_amount, 2) ?> BYN</td>
                            </tr>
                        </tfoot>
                    </table>
                <?php endif; ?>
            </section>

            <div class="collapsible-section">
                <div class="collapsible-header" onclick="toggleCollapsible(this)">
                    <div class="collapsible-title">📋 Паспортные данные и логистика</div>
                    <div class="collapsible-icon">▼</div>
                </div>
                <div class="collapsible-content">
                    <section class="panel">
                        <div class="panel-header">
                            <div>
                                <h2 class="panel-title">
                                    📋 Получатель и паспорт
                                </h2>
                                <div class="panel-subtitle">Данные для таможенного оформления</div>
                            </div>
                        </div>
                        <div class="info-grid info-grid--3">
                            <?php foreach ([
                                'recipient_last_name' => 'Фамилия',
                                'recipient_first_name' => 'Имя',
                                'recipient_middle_name' => 'Отчество',
                                'passport_series' => 'Серия паспорта',
                                'passport_number' => 'Номер паспорта',
                                'passport_issue_date' => 'Дата выдачи',
                                'birth_date' => 'Дата рождения',
                                'inn' => 'ИНН',
                            ] as $field => $label): ?>
                                <div class="info-item">
                                    <div class="info-label"><?= Html::encode($label) ?></div>
                                    <?php if ($isEditing): ?>
                                        <div class="form-field">
                                            <input type="<?= $field === 'passport_issue_date' || $field === 'birth_date' ? 'date' : 'text' ?>" 
                                                   name="Order[<?= $field ?>]" 
                                                   value="<?= Html::encode($model->$field) ?>">
                                        </div>
                                    <?php else: ?>
                                        <div class="info-value"><?= Html::encode($model->$field ?: 'Не указано') ?></div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>

                    <section class="panel">
                        <div class="panel-header">
                            <div>
                                <h2 class="panel-title">
                                    🚚 Логистика и ссылки
                                </h2>
                                <div class="panel-subtitle">Трек-номера и стоимость доставки</div>
                            </div>
                        </div>
                        <div class="info-grid info-grid--3">
                            <?php foreach ([
                                'china_track_number' => 'Китайский трек',
                                'ms_number' => '№ МС',
                                'dobropost_tariff' => 'DobroПост тариф',
                                'shipment_value_cny' => 'Ценность (¥)',
                                'item_quantity' => 'Кол-во товаров',
                                'item_price_cny' => 'Цена за ед. (¥)',
                            ] as $field => $label): ?>
                                <div class="info-item">
                                    <div class="info-label"><?= Html::encode($label) ?></div>
                                    <?php if ($isEditing): ?>
                                        <div class="form-field">
                                            <input type="<?= in_array($field, ['shipment_value_cny', 'item_price_cny'], true) ? 'number' : 'text' ?>" 
                                                   name="Order[<?= $field ?>]" 
                                                   value="<?= Html::encode($model->$field) ?>"
                                                   step="<?= in_array($field, ['shipment_value_cny', 'item_price_cny'], true) ? '0.01' : '' ?>">
                                        </div>
                                    <?php else: ?>
                                        <div class="info-value"><?= Html::encode($model->$field ?: 'Не указано') ?></div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="info-grid info-grid--2" style="margin-top: 20px;">
                            <div class="info-item">
                                <div class="info-label">Ссылка на товар</div>
                                <?php if ($isEditing): ?>
                                    <div class="form-field">
                                        <input type="url" name="Order[product_link]" value="<?= Html::encode($model->product_link) ?>">
                                    </div>
                                <?php else: ?>
                                    <div class="info-value">
                                        <?php if ($model->product_link): ?>
                                            <a href="<?= Html::encode($model->product_link) ?>" target="_blank" class="info-value--link">
                                                <?= Html::encode($model->product_link) ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="info-value--empty">Не указана</span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Ссылка Sneakerhead</div>
                                <?php if ($isEditing): ?>
                                    <div class="form-field">
                                        <input type="url" name="Order[sneakerhead_order_link]" value="<?= Html::encode($model->sneakerhead_order_link) ?>">
                                    </div>
                                <?php else: ?>
                                    <div class="info-value">
                                        <?php if ($model->sneakerhead_order_link): ?>
                                            <a href="<?= Html::encode($model->sneakerhead_order_link) ?>" target="_blank" class="info-value--link">
                                                <?= Html::encode($model->sneakerhead_order_link) ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="info-value--empty">Не указана</span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="info-item" style="margin-top: 20px;">
                            <div class="info-label">Описание для таможни</div>
                            <?php if ($isEditing): ?>
                                <div class="form-field">
                                    <textarea name="Order[customs_description]" rows="3"><?= Html::encode($model->customs_description) ?></textarea>
                                </div>
                            <?php else: ?>
                                <div class="info-value"><?= Html::encode($model->customs_description ?: 'Не указано') ?></div>
                            <?php endif; ?>
                        </div>
                    </section>
                </div>
            </div>

            <?php if ($canEdit && $isEditing): ?>
                </form>
            <?php endif; ?>
        </main>

        <aside class="view-sidebar">
            <section class="panel">
                <div class="panel-header">
                    <div>
                        <h2 class="panel-title">
                            🎯 Статус заказа
                        </h2>
                        <div class="panel-subtitle"><?= Html::encode($model->getStatusLabel()) ?></div>
                    </div>
                </div>
                <?php if ($canEdit): ?>
                    <form method="post" action="<?= Url::to(['/admin/order/change-status', 'id' => $model->id]) ?>">
                        <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>
                        <div class="form-field">
                            <label>Изменить статус</label>
                            <select name="status">
                                <?php
                                $statusList = $user->isLogist()
                                    ? Yii::$app->settings->getLogistStatuses()
                                    : $statuses;
                                foreach ($statusList as $key => $label): ?>
                                    <option value="<?= $key ?>" <?= $model->status === $key ? 'selected' : '' ?>>
                                        <?= Html::encode($label) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-field">
                            <label>Комментарий к изменению</label>
                            <textarea name="comment" rows="3" placeholder="Опционально..."></textarea>
                        </div>
                        <button type="submit" class="btn btn--primary" style="width: 100%; margin-top: 12px;">
                            🔄 Сохранить статус
                        </button>
                    </form>
                <?php else: ?>
                    <div class="info-value" style="color: var(--muted); font-size: 0.875rem;">
                        У вас нет прав для изменения статуса.
                    </div>
                <?php endif; ?>
            </section>

            <section class="panel">
                <div class="panel-header">
                    <div>
                        <h2 class="panel-title">
                            💰 Финансы
                        </h2>
                        <div class="panel-subtitle">Основные суммы заказа</div>
                    </div>
                </div>
                <div class="finance-list">
                    <div class="finance-item">
                        <span class="finance-label">Товары</span>
                        <span class="finance-value"><?= Yii::$app->formatter->asDecimal($model->product_price, 2) ?> BYN</span>
                    </div>
                    <div class="finance-item">
                        <span class="finance-label">Логистика</span>
                        <span class="finance-value"><?= Yii::$app->formatter->asDecimal($model->logistics_price, 2) ?> BYN</span>
                    </div>
                    <div class="finance-item">
                        <span class="finance-label">Комиссия</span>
                        <span class="finance-value"><?= Yii::$app->formatter->asDecimal($model->commission_price, 2) ?> BYN</span>
                    </div>
                    <div class="finance-item">
                        <span class="finance-label">Доставка</span>
                        <span class="finance-value"><?= Yii::$app->formatter->asDecimal($model->delivery_cost, 2) ?> BYN</span>
                    </div>
                    <div class="finance-item">
                        <span class="finance-label">Итого к оплате</span>
                        <span class="finance-value"><?= Yii::$app->formatter->asDecimal($model->total_amount, 2) ?> BYN</span>
                    </div>
                </div>
            </section>

            <?php if ($user->isAdmin()): ?>
            <section class="panel">
                <div class="panel-header">
                    <div>
                        <h2 class="panel-title">
                            👤 Ответственный логист
                        </h2>
                        <div class="panel-subtitle"><?= $model->logist ? Html::encode($model->logist->username) : 'Не назначен' ?></div>
                    </div>
                </div>
                <form method="post" action="<?= Url::to(['/admin/order/assign-logist', 'id' => $model->id]) ?>">
                    <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>
                    <div class="form-field">
                        <label>Выберите логиста</label>
                        <select name="logist_id">
                            <option value="">—</option>
                            <?php foreach ($logists as $logist): ?>
                                <option value="<?= $logist->id ?>" <?= $model->assigned_logist == $logist->id ? 'selected' : '' ?>>
                                    <?= Html::encode($logist->username) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn--secondary" style="width: 100%; margin-top: 12px;">
                        💾 Сохранить
                    </button>
                </form>
            </section>
            <?php endif; ?>

            <section class="panel">
                <div class="panel-header">
                    <div>
                        <h2 class="panel-title">
                            🔗 Публичная ссылка
                        </h2>
                        <div class="panel-subtitle">Для клиента</div>
                    </div>
                </div>
                <div class="public-link">
                    <input type="text" value="<?= Html::encode($model->getPublicUrl()) ?>" id="publicLink" readonly>
                    <button type="button" class="copy-btn" onclick="copyLink()">📋 Копировать</button>
                </div>
            </section>

            <?php if ($model->history): ?>
            <section class="panel">
                <div class="panel-header">
                    <div>
                        <h2 class="panel-title">
                            📜 История изменений
                        </h2>
                        <div class="panel-subtitle"><?= count($model->history) ?> записей</div>
                    </div>
                </div>
                <div class="timeline">
                    <?php foreach ($model->history as $history): ?>
                        <div class="timeline-item">
                            <div class="timeline-content">
                                <div class="timeline-title"><?= Html::encode($history->getNewStatusLabel()) ?></div>
                                <div class="timeline-meta">
                                    <?= Yii::$app->formatter->asDatetime($history->created_at, 'short') ?>
                                    <?php if ($history->changer): ?> • <?= Html::encode($history->changer->username) ?><?php endif; ?>
                                </div>
                                <?php if ($history->comment): ?>
                                    <div class="timeline-comment"><?= Html::encode($history->comment) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>
        </aside>
    </div>
</div>

<div class="save-indicator" id="saveIndicator">
    <span>✅</span>
    <span>Сохранено</span>
</div>

<script>
function toggleCollapsible(header) {
    const section = header.closest('.collapsible-section');
    section.classList.toggle('collapsible-section--collapsed');
}

function copyLink() {
    const input = document.getElementById('publicLink');
    navigator.clipboard.writeText(input.value).then(() => {
        showSaveIndicator('Ссылка скопирована');
    });
}

function showSaveIndicator(message) {
    const indicator = document.getElementById('saveIndicator');
    indicator.innerHTML = `<span>✅</span><span>${message || 'Сохранено'}</span>`;
    indicator.classList.add('save-indicator--show');
    setTimeout(() => indicator.classList.remove('save-indicator--show'), 2000);
}

// Автосохранение при редактировании
document.querySelectorAll('#orderUpdateForm input, #orderUpdateForm textarea, #orderUpdateForm select').forEach(field => {
    field.addEventListener('input', function() {
        // Показываем индикатор изменений
        const indicator = document.getElementById('saveIndicator');
        indicator.innerHTML = '<span>✏️</span><span>Изменения внесены</span>';
        indicator.classList.add('save-indicator--show');
    });
});

// Обработка флагов процесса
document.querySelectorAll('.js-flag').forEach(flag => {
    flag.addEventListener('change', function() {
        const field = this.dataset.field;
        const value = this.checked ? 1 : 0;
        const formData = new FormData();
        formData.append('field', field);
        formData.append('value', value);
        formData.append('<?= Yii::$app->request->csrfParam ?>', '<?= Yii::$app->request->csrfToken ?>');

        fetch('<?= Url::to(['/admin/order/update-field', 'id' => $model->id]) ?>', {
            method: 'POST',
            body: formData
        }).then(response => response.json())
          .then(data => {
              if (data.success) {
                  showSaveIndicator('Флаг сохранён');
              }
          });
    });
});
</script>
