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

