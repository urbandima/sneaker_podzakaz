<?php

/**
 * Coupon Usage Analytics Dashboard
 * 
 * Рекомендация #35: Usage Analytics
 * 
 * Метрики:
 * - Использование купонов
 * - Выручка с купонами
 * - Средний чек с купоном
 * - Конверсия
 */
?>

<div class="coupon-analytics-dashboard">
    <div class="analytics-header">
        <h1><i class="bi bi-graph-up-arrow"></i> Аналитика купонов</h1>
        <div class="period-selector">
            <button class="btn-period active" data-period="7">7 дней</button>
            <button class="btn-period" data-period="30">30 дней</button>
            <button class="btn-period" data-period="90">3 месяца</button>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="kpi-icon" style="background:linear-gradient(135deg,#3b82f6,#8b5cf6)">
                <i class="bi bi-ticket-perforated"></i>
            </div>
            <div class="kpi-content">
                <div class="kpi-value"><?= number_format($stats['total_used'] ?? 0) ?></div>
                <div class="kpi-label">Использовано купонов</div>
                <div class="kpi-change <?= ($stats['usage_change'] ?? 0) >= 0 ? 'positive' : 'negative' ?>">
                    <i class="bi bi-<?= ($stats['usage_change'] ?? 0) >= 0 ? 'arrow-up' : 'arrow-down' ?>"></i>
                    <?= abs($stats['usage_change'] ?? 0) ?>%
                </div>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon" style="background:linear-gradient(135deg,#10b981,#059669)">
                <i class="bi bi-cash-stack"></i>
            </div>
            <div class="kpi-content">
                <div class="kpi-value"><?= Yii::$app->formatter->asCurrency($stats['total_revenue'] ?? 0, 'BYN') ?></div>
                <div class="kpi-label">Выручка с купонами</div>
                <div class="kpi-change <?= ($stats['revenue_change'] ?? 0) >= 0 ? 'positive' : 'negative' ?>">
                    <i class="bi bi-<?= ($stats['revenue_change'] ?? 0) >= 0 ? 'arrow-up' : 'arrow-down' ?>"></i>
                    <?= abs($stats['revenue_change'] ?? 0) ?>%
                </div>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon" style="background:linear-gradient(135deg,#f59e0b,#d97706)">
                <i class="bi bi-cart-check"></i>
            </div>
            <div class="kpi-content">
                <div class="kpi-value"><?= Yii::$app->formatter->asCurrency($stats['avg_order'] ?? 0, 'BYN') ?></div>
                <div class="kpi-label">Средний чек (с купоном)</div>
                <div class="kpi-compare">
                    без купона: <?= Yii::$app->formatter->asCurrency($stats['avg_order_without'] ?? 0, 'BYN') ?>
                </div>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon" style="background:linear-gradient(135deg,#ef4444,#dc2626)">
                <i class="bi bi-percent"></i>
            </div>
            <div class="kpi-content">
                <div class="kpi-value"><?= round($stats['conversion_rate'] ?? 0, 1) ?>%</div>
                <div class="kpi-label">Конверсия купонов</div>
                <div class="kpi-subtitle">
                    <?= $stats['issued'] ?? 0 ?> выдано / <?= $stats['used'] ?? 0 ?> использовано
                </div>
            </div>
        </div>
    </div>

    <!-- Top Coupons -->
    <div class="analytics-section">
        <h2><i class="bi bi-trophy"></i> Топ купонов</h2>
        <table class="analytics-table">
            <thead>
                <tr>
                    <th>Код</th>
                    <th>Название</th>
                    <th>Тип</th>
                    <th>Использовано</th>
                    <th>Выручка</th>
                    <th>Скидка</th>
                    <th>Эффективность</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($topCoupons ?? [] as $coupon): ?>
                <tr>
                    <td><code><?= Html::encode($coupon['code']) ?></code></td>
                    <td><?= Html::encode($coupon['name']) ?></td>
                    <td><?= $coupon['type'] === 'percent' ? $coupon['value'] . '%' : Yii::$app->formatter->asCurrency($coupon['value'], 'BYN') ?></td>
                    <td><?= $coupon['used_count'] ?> / <?= $coupon['max_uses'] ?? '∞' ?></td>
                    <td><?= Yii::$app->formatter->asCurrency($coupon['revenue'], 'BYN') ?></td>
                    <td><?= Yii::$app->formatter->asCurrency($coupon['total_discount'], 'BYN') ?></td>
                    <td>
                        <div class="efficiency-bar">
                            <div class="efficiency-fill" style="width:<?= min(100, $coupon['efficiency']) ?>%"></div>
                        </div>
                        <?= round($coupon['efficiency']) ?>%
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Chart Section -->
    <div class="analytics-section">
        <h2><i class="bi bi-bar-chart-line"></i> Динамика использования</h2>
        <div class="chart-container" id="coupon-chart">
            <!-- Chart.js или другая библиотека -->
        </div>
    </div>
</div>

<style>
.coupon-analytics-dashboard {
    padding: 24px;
    max-width: 1400px;
    margin: 0 auto;
}

.analytics-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}

.analytics-header h1 {
    font-size: 24px;
    font-weight: 700;
    margin: 0;
}

.period-selector {
    display: flex;
    gap: 8px;
}

.btn-period {
    padding: 8px 16px;
    border: 1px solid #e5e7eb;
    background: white;
    border-radius: 8px;
    cursor: pointer;
    font-size: 14px;
    transition: all 0.2s;
}

.btn-period.active,
.btn-period:hover {
    background: #3b82f6;
    color: white;
    border-color: #3b82f6;
}

/* KPI Cards */
.kpi-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 32px;
}

.kpi-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 20px;
    display: flex;
    align-items: flex-start;
    gap: 16px;
}

.kpi-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 24px;
}

.kpi-content {
    flex: 1;
}

.kpi-value {
    font-size: 24px;
    font-weight: 700;
    color: #111827;
    line-height: 1;
}

.kpi-label {
    font-size: 14px;
    color: #6b7280;
    margin-top: 4px;
}

.kpi-change {
    font-size: 13px;
    font-weight: 600;
    margin-top: 8px;
    display: flex;
    align-items: center;
    gap: 4px;
}

.kpi-change.positive {
    color: #10b981;
}

.kpi-change.negative {
    color: #ef4444;
}

.kpi-compare {
    font-size: 12px;
    color: #9ca3af;
    margin-top: 8px;
}

.kpi-subtitle {
    font-size: 12px;
    color: #6b7280;
    margin-top: 8px;
}

/* Sections */
.analytics-section {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 24px;
}

.analytics-section h2 {
    font-size: 18px;
    font-weight: 600;
    margin: 0 0 20px;
}

/* Table */
.analytics-table {
    width: 100%;
    border-collapse: collapse;
}

.analytics-table th {
    text-align: left;
    padding: 12px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    color: #6b7280;
    background: #f9fafb;
}

.analytics-table td {
    padding: 16px 12px;
    border-bottom: 1px solid #f3f4f6;
    font-size: 14px;
}

.analytics-table tr:last-child td {
    border-bottom: none;
}

.analytics-table code {
    background: #f3f4f6;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 13px;
}

/* Efficiency Bar */
.efficiency-bar {
    width: 100px;
    height: 6px;
    background: #e5e7eb;
    border-radius: 3px;
    overflow: hidden;
    margin-bottom: 4px;
}

.efficiency-fill {
    height: 100%;
    background: linear-gradient(90deg, #10b981, #059669);
    border-radius: 3px;
}

@media (max-width: 1024px) {
    .kpi-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 640px) {
    .kpi-grid {
        grid-template-columns: 1fr;
    }
    
    .analytics-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 16px;
    }
}
</style>

<script>
// AJAX загрузка данных по периоду
document.querySelectorAll('.btn-period').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.btn-period').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        
        const period = this.dataset.period;
        fetch(`/admin/coupon/analytics?period=${period}`)
            .then(r => r.json())
            .then(data => updateDashboard(data));
    });
});

function updateDashboard(data) {
    // Обновляем KPI
    // Обновляем график
}
</script>
