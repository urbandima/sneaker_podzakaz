<?php

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Управление тарифами';

$calculationHistory = $calculationHistory ?? [];
?>

<style>
.tariff-page {
    padding: var(--admin-space-6, 1.5rem);
    background: var(--admin-bg, #f8fafc);
    min-height: 100vh;
}

.tariff-shell {
    max-width: 1400px;
    margin: 0 auto;
}

/* Hero Section */
.tariff-hero {
    background: linear-gradient(135deg, var(--admin-primary, #0f172a) 0%, var(--admin-primary-light, #1e293b) 100%);
    color: var(--admin-text-inverse, #ffffff);
    border-radius: var(--admin-radius-xl, 18px);
    padding: var(--admin-space-6, 1.5rem);
    margin-bottom: var(--admin-space-6, 1.5rem);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: var(--admin-space-4, 1rem);
}

.tariff-hero h1 {
    font-size: var(--admin-text-2xl, 1.5rem);
    font-weight: 800;
    margin: 0;
    display: flex;
    align-items: center;
    gap: var(--admin-space-3, 0.75rem);
}

.tariff-hero p {
    margin: var(--admin-space-2, 0.5rem) 0 0;
    opacity: 0.8;
    font-size: var(--admin-text-sm, 0.875rem);
}

/* Tariff Grid */
.tariff-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: var(--admin-space-5, 1.25rem);
    margin-bottom: var(--admin-space-6, 1.5rem);
}

.tariff-card {
    background: var(--admin-surface, #ffffff);
    border: 1px solid var(--admin-border, #e2e8f0);
    border-radius: var(--admin-radius-xl, 18px);
    padding: var(--admin-space-5, 1.25rem);
    box-shadow: var(--admin-shadow-md, 0 4px 12px rgba(15, 23, 42, 0.08));
    transition: all var(--admin-transition-base, 0.2s ease);
}

.tariff-card:hover {
    box-shadow: var(--admin-shadow-lg, 0 12px 28px rgba(15, 23, 42, 0.12));
    transform: translateY(-2px);
}

.tariff-card.inactive {
    opacity: 0.6;
}

.tariff-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: var(--admin-space-3, 0.75rem);
}

.tariff-name {
    font-size: var(--admin-text-lg, 1.125rem);
    font-weight: 700;
    color: var(--admin-text, #0f172a);
}

.tariff-badge {
    padding: var(--admin-space-1, 0.25rem) var(--admin-space-3, 0.75rem);
    border-radius: var(--admin-radius-full, 9999px);
    font-size: var(--admin-text-xs, 0.75rem);
    font-weight: 600;
}

.tariff-badge.active {
    background: var(--admin-success-soft, #d1fae5);
    color: var(--admin-success, #10b981);
}

.tariff-badge.inactive {
    background: var(--admin-danger-soft, #fee2e2);
    color: var(--admin-danger, #ef4444);
}

.tariff-description {
    color: var(--admin-text-muted, #94a3b8);
    font-size: var(--admin-text-sm, 0.875rem);
    margin-bottom: var(--admin-space-4, 1rem);
    line-height: 1.5;
}

.tariff-details {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--admin-space-3, 0.75rem);
    margin-bottom: var(--admin-space-4, 1rem);
}

.tariff-detail {
    display: flex;
    flex-direction: column;
    gap: var(--admin-space-1, 0.25rem);
}

.detail-label {
    font-size: var(--admin-text-xs, 0.75rem);
    font-weight: 600;
    color: var(--admin-text-muted, #94a3b8);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.detail-value {
    font-size: var(--admin-text-base, 1rem);
    font-weight: 700;
    color: var(--admin-text, #0f172a);
}

.tariff-actions {
    display: flex;
    flex-wrap: wrap;
    gap: var(--admin-space-2, 0.5rem);
    padding-top: var(--admin-space-4, 1rem);
    border-top: 1px solid var(--admin-border-light, #f1f5f9);
}

/* Buttons */
.btn-action {
    display: inline-flex;
    align-items: center;
    gap: var(--admin-space-2, 0.5rem);
    padding: var(--admin-space-2, 0.5rem) var(--admin-space-4, 1rem);
    border: 1px solid var(--admin-border, #e2e8f0);
    background: var(--admin-surface, #ffffff);
    color: var(--admin-text-secondary, #475569);
    border-radius: var(--admin-radius-md, 10px);
    text-decoration: none;
    font-size: var(--admin-text-sm, 0.875rem);
    font-weight: 600;
    transition: all var(--admin-transition-fast, 0.15s ease);
    cursor: pointer;
}

.btn-action:hover {
    background: var(--admin-surface-muted, #f1f5f9);
    color: var(--admin-text, #0f172a);
    border-color: var(--admin-text-muted, #94a3b8);
}

.btn-action:active {
    transform: translateY(1px);
}

.btn-action.primary {
    background: var(--admin-accent, #3b82f6);
    border-color: var(--admin-accent, #3b82f6);
    color: var(--admin-text-inverse, #ffffff);
}

.btn-action.primary:hover {
    background: var(--admin-accent-hover, #2563eb);
    border-color: var(--admin-accent-hover, #2563eb);
}

.btn-action.danger {
    color: var(--admin-danger, #ef4444);
    border-color: var(--admin-danger, #ef4444);
}

.btn-action.danger:hover {
    background: var(--admin-danger-soft, #fee2e2);
}

/* Calculator Section */
.calculator-section {
    background: var(--admin-surface, #ffffff);
    border: 1px solid var(--admin-border, #e2e8f0);
    border-radius: var(--admin-radius-xl, 18px);
    padding: var(--admin-space-6, 1.5rem);
    box-shadow: var(--admin-shadow-md, 0 4px 12px rgba(15, 23, 42, 0.08));
    margin-bottom: var(--admin-space-6, 1.5rem);
}

.calculator-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: var(--admin-space-5, 1.25rem);
}

.calculator-title {
    font-size: var(--admin-text-lg, 1.125rem);
    font-weight: 700;
    color: var(--admin-text, #0f172a);
    display: flex;
    align-items: center;
    gap: var(--admin-space-2, 0.5rem);
    margin: 0;
}

.calculator-form {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: var(--admin-space-4, 1rem);
    margin-bottom: var(--admin-space-4, 1rem);
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: var(--admin-space-2, 0.5rem);
}

.form-group label {
    font-size: var(--admin-text-xs, 0.75rem);
    font-weight: 600;
    color: var(--admin-text-muted, #94a3b8);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.form-group input,
.form-group select,
.form-group textarea {
    padding: var(--admin-space-3, 0.75rem);
    border: 1px solid var(--admin-border, #e2e8f0);
    border-radius: var(--admin-radius-md, 10px);
    font-size: var(--admin-text-sm, 0.875rem);
    color: var(--admin-text, #0f172a);
    background: var(--admin-surface, #ffffff);
    transition: all var(--admin-transition-fast, 0.15s ease);
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: var(--admin-accent, #3b82f6);
    box-shadow: 0 0 0 3px var(--admin-accent-soft, #dbeafe);
}

.calculation-result {
    background: var(--admin-surface-muted, #f1f5f9);
    border-radius: var(--admin-radius-lg, 14px);
    padding: var(--admin-space-5, 1.25rem);
    display: none;
}

.calculation-result.show {
    display: block;
}

.result-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: var(--admin-space-3, 0.75rem) 0;
    border-bottom: 1px solid var(--admin-border, #e2e8f0);
}

.result-row:last-child {
    border-bottom: none;
    padding-top: var(--admin-space-4, 1rem);
    margin-top: var(--admin-space-2, 0.5rem);
    font-weight: 700;
    font-size: var(--admin-text-lg, 1.125rem);
    color: var(--admin-success, #10b981);
}

.result-label {
    color: var(--admin-text-secondary, #475569);
}

.result-value {
    font-weight: 600;
    color: var(--admin-text, #0f172a);
}

/* History Section */
.history-section {
    background: var(--admin-surface, #ffffff);
    border: 1px solid var(--admin-border, #e2e8f0);
    border-radius: var(--admin-radius-xl, 18px);
    padding: var(--admin-space-6, 1.5rem);
    box-shadow: var(--admin-shadow-md, 0 4px 12px rgba(15, 23, 42, 0.08));
}

.history-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: var(--admin-space-5, 1.25rem);
}

.history-title {
    font-size: var(--admin-text-lg, 1.125rem);
    font-weight: 700;
    color: var(--admin-text, #0f172a);
    display: flex;
    align-items: center;
    gap: var(--admin-space-2, 0.5rem);
    margin: 0;
}

.history-table-wrapper {
    border-radius: var(--admin-radius-lg, 14px);
    border: 1px solid var(--admin-border, #e2e8f0);
    overflow: hidden;
}

.history-table {
    width: 100%;
    border-collapse: collapse;
    font-size: var(--admin-text-sm, 0.875rem);
}

.history-table th {
    padding: var(--admin-space-3, 0.75rem) var(--admin-space-4, 1rem);
    text-align: left;
    font-size: var(--admin-text-xs, 0.75rem);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--admin-text-muted, #94a3b8);
    background: var(--admin-surface-muted, #f1f5f9);
    border-bottom: 1px solid var(--admin-border, #e2e8f0);
}

.history-table td {
    padding: var(--admin-space-3, 0.75rem) var(--admin-space-4, 1rem);
    border-bottom: 1px solid var(--admin-border-light, #f1f5f9);
    vertical-align: middle;
}

.history-table tbody tr:hover {
    background: var(--admin-surface-muted, #f1f5f9);
}

.history-table tbody tr:last-child td {
    border-bottom: none;
}

.history-empty {
    text-align: center;
    padding: var(--admin-space-10, 2.5rem);
    color: var(--admin-text-muted, #94a3b8);
}

.history-empty i {
    font-size: 2.5rem;
    opacity: 0.5;
    margin-bottom: var(--admin-space-3, 0.75rem);
    display: block;
}

/* Responsive */
@media (max-width: 768px) {
    .tariff-page {
        padding: var(--admin-space-4, 1rem);
    }
    
    .tariff-hero {
        flex-direction: column;
        align-items: stretch;
        text-align: center;
    }
    
    .tariff-grid {
        grid-template-columns: 1fr;
    }
    
    .calculator-form {
        grid-template-columns: 1fr;
    }
    
    .history-table-wrapper {
        overflow-x: auto;
    }
}
</style>

<div class="tariff-page">
    <div class="tariff-shell">
        <!-- Hero Section -->
        <div class="tariff-hero">
            <div>
                <h1><i class="bi bi-calculator-fill"></i> <?= Html::encode($this->title) ?></h1>
                <p>Управляйте тарифами и комиссиями для расчета стоимости заказов</p>
            </div>
            <a href="<?= Url::to(['create']) ?>" class="btn-action primary">
                <i class="bi bi-plus-lg"></i> Добавить тариф
            </a>
        </div>

        <!-- Tariff Cards Grid -->
        <div class="tariff-grid">
            <?php foreach ($tariffs as $tariff): ?>
                <div class="tariff-card <?= $tariff->is_active ? '' : 'inactive' ?>">
                    <div class="tariff-header">
                        <div class="tariff-name"><?= Html::encode($tariff->name) ?></div>
                        <span class="tariff-badge <?= $tariff->is_active ? 'active' : 'inactive' ?>">
                            <?= $tariff->is_active ? 'Активен' : 'Неактивен' ?>
                        </span>
                    </div>
                    
                    <?php if ($tariff->description): ?>
                        <div class="tariff-description"><?= Html::encode($tariff->description) ?></div>
                    <?php endif; ?>
                    
                    <div class="tariff-details">
                        <div class="tariff-detail">
                            <span class="detail-label">Комиссия</span>
                            <span class="detail-value"><?= $tariff->commission_percent ?>%</span>
                        </div>
                        <div class="tariff-detail">
                            <span class="detail-label">Доставка/кг</span>
                            <span class="detail-value"><?= $tariff->delivery_cost_per_kg ?> ¥</span>
                        </div>
                        <div class="tariff-detail">
                            <span class="detail-label">Страховка</span>
                            <span class="detail-value"><?= $tariff->insurance_percent ?>%</span>
                        </div>
                        <div class="tariff-detail">
                            <span class="detail-label">Курс</span>
                            <span class="detail-value"><?= $tariff->exchange_rate ?></span>
                        </div>
                    </div>
                    
                    <div class="tariff-actions">
                        <a href="<?= Url::to(['update', 'id' => $tariff->id]) ?>" class="btn-action">
                            <i class="bi bi-pencil"></i> Изменить
                        </a>
                        <a href="<?= Url::to(['toggle', 'id' => $tariff->id]) ?>" class="btn-action">
                            <i class="bi bi-<?= $tariff->is_active ? 'pause' : 'play' ?>"></i>
                            <?= $tariff->is_active ? 'Деактивировать' : 'Активировать' ?>
                        </a>
                        <?= Html::a('<i class="bi bi-trash"></i>', ['delete', 'id' => $tariff->id], [
                            'class' => 'btn-action danger',
                            'data' => ['method' => 'post', 'confirm' => 'Удалить тариф?'],
                        ]) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Calculator Section -->
        <div class="calculator-section">
            <div class="calculator-header">
                <h3 class="calculator-title"><i class="bi bi-calculator"></i> Калькулятор стоимости заказа</h3>
            </div>
            
            <div class="calculator-form">
                <div class="form-group">
                    <label>Тариф</label>
                    <select id="calcTariff">
                        <?php foreach ($tariffs as $tariff): ?>
                            <?php if ($tariff->is_active): ?>
                                <option value="<?= $tariff->id ?>"><?= Html::encode($tariff->name) ?></option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Цена товара (¥)</label>
                    <input type="number" id="calcPrice" value="500" step="0.01">
                </div>
                <div class="form-group">
                    <label>Вес (кг)</label>
                    <input type="number" id="calcWeight" value="0.5" step="0.1">
                </div>
                <div class="form-group">
                    <label>Примечание</label>
                    <input type="text" id="calcNote" placeholder="Опционально">
                </div>
                <div class="form-group" style="align-self: end;">
                    <button type="button" class="btn-action primary" onclick="calculateCost()">
                        <i class="bi bi-calculator"></i> Рассчитать
                    </button>
                </div>
            </div>
            
            <div class="calculation-result" id="calcResult">
                <div id="calcBreakdown"></div>
            </div>
        </div>

        <!-- Calculation History Section -->
        <div class="history-section">
            <div class="history-header">
                <h3 class="history-title"><i class="bi bi-clock-history"></i> История расчетов</h3>
                <span style="font-size: 0.875rem; color: var(--admin-text-muted, #94a3b8);">
                    Последние 30 расчетов
                </span>
            </div>
            
            <?php if (!empty($calculationHistory)): ?>
                <div class="history-table-wrapper">
                    <table class="history-table">
                        <thead>
                            <tr>
                                <th>Дата</th>
                                <th>Тариф</th>
                                <th>Цена (¥)</th>
                                <th>Вес (кг)</th>
                                <th>Комиссия</th>
                                <th>Доставка</th>
                                <th>Итого (¥)</th>
                                <th>Курс</th>
                                <th>Итого (BYN)</th>
                                <th>Примечание</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($calculationHistory as $calc): ?>
                                <tr>
                                    <td><?= Yii::$app->formatter->asDatetime($calc->created_at, 'short') ?></td>
                                    <td><strong><?= Html::encode($calc->tariff_name) ?></strong></td>
                                    <td><?= number_format($calc->price_cny, 2) ?></td>
                                    <td><?= number_format($calc->weight_kg, 2) ?></td>
                                    <td><?= number_format($calc->commission_cny, 2) ?> ¥</td>
                                    <td><?= number_format($calc->delivery_cost_cny, 2) ?> ¥</td>
                                    <td><strong><?= number_format($calc->total_cny, 2) ?> ¥</strong></td>
                                    <td><?= $calc->exchange_rate ?></td>
                                    <td style="color: var(--admin-success, #10b981); font-weight: 700;">
                                        <?= number_format($calc->total_local, 2) ?> BYN
                                    </td>
                                    <td>
                                        <?php if ($calc->note): ?>
                                            <span title="<?= Html::encode($calc->note) ?>">
                                                <?= Html::encode(mb_substr($calc->note, 0, 20)) ?><?= mb_strlen($calc->note) > 20 ? '...' : '' ?>
                                            </span>
                                        <?php else: ?>
                                            <span style="color: var(--admin-text-muted, #94a3b8);">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="history-empty">
                    <i class="bi bi-clock-history"></i>
                    <p>История расчетов пуста</p>
                    <p style="font-size: 0.875rem;">Выполните расчет в калькуляторе выше, чтобы он появился здесь</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function calculateCost() {
    const tariffId = document.getElementById('calcTariff').value;
    const priceCny = document.getElementById('calcPrice').value;
    const weightKg = document.getElementById('calcWeight').value;
    const note = document.getElementById('calcNote').value;
    
    fetch('<?= Url::to(['calculate']) ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-CSRF-Token': '<?= Yii::$app->request->csrfToken ?>'
        },
        body: `tariff_id=${tariffId}&price_cny=${priceCny}&weight_kg=${weightKg}&note=${encodeURIComponent(note)}&save_history=1`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const result = document.getElementById('calcResult');
            const breakdown = document.getElementById('calcBreakdown');
            
            let html = '';
            for (const [label, value] of Object.entries(data.calculation.breakdown)) {
                html += `<div class="result-row">
                    <span class="result-label">${label}</span>
                    <span class="result-value">${value}</span>
                </div>`;
            }
            
            breakdown.innerHTML = html;
            result.classList.add('show');
            
            // Show save notification
            if (data.history_id) {
                showNotification('Расчет сохранен в историю', 'success');
            }
        }
    });
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = 'admin-toast ' + (type === 'success' ? 'admin-toast--success' : '');
    notification.innerHTML = `<i class="bi bi-check-circle"></i> ${message}`;
    document.body.appendChild(notification);
    
    setTimeout(() => notification.classList.add('show'), 100);
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}
</script>
