<?php
/**
 * Аналитика — Продвинутые отчёты и графики
 *
 * @var yii\web\View $this
 * @var array $statusStats
 * @var array $managerStats
 * @var array $logistStats
 * @var int $totalOrders
 * @var float $totalAmount
 * @var int $pendingPayment
 * @var int $completedOrders
 * @var float $avgOrderAmount
 * @var array $salesChart
 * @var array $topProducts
 */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Аналитика';

$statusColors = [
    'created' => '#3b82f6', 'pending' => '#f59e0b', 'processing' => '#8b5cf6',
    'paid' => '#10b981', 'shipped' => '#06b6d4', 'delivered' => '#22c55e',
    'completed' => '#059669', 'issued' => '#059669', 'cancelled' => '#ef4444', 'refunded' => '#f43f5e',
];
?>

<!-- Header -->
<div class="admin-header">
    <div>
        <h1 class="admin-header-title"><i class="bi bi-bar-chart-line-fill"></i> <?= Html::encode($this->title) ?></h1>
    </div>
    <div class="admin-header-actions">
        <a href="<?= Url::to(['/admin/statistics/export-csv']) ?>" class="admin-btn admin-btn-secondary">
            <i class="bi bi-download"></i> Экспорт CSV
        </a>
    </div>
</div>

<!-- KPI Cards -->
<div class="admin-stats">
    <div class="admin-stat-card">
        <div class="admin-stat-icon primary"><i class="bi bi-bag-check-fill"></i></div>
        <div class="admin-stat-content">
            <div class="admin-stat-value"><?= number_format($totalOrders) ?></div>
            <div class="admin-stat-label">Всего заказов</div>
        </div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-icon success"><i class="bi bi-currency-exchange"></i></div>
        <div class="admin-stat-content">
            <div class="admin-stat-value"><?= number_format($totalAmount, 0, '.', ' ') ?> <small>BYN</small></div>
            <div class="admin-stat-label">Выручка</div>
        </div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-icon warning"><i class="bi bi-calculator"></i></div>
        <div class="admin-stat-content">
            <div class="admin-stat-value"><?= number_format($avgOrderAmount, 0, '.', ' ') ?> <small>BYN</small></div>
            <div class="admin-stat-label">Средний чек</div>
        </div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-icon danger"><i class="bi bi-hourglass-split"></i></div>
        <div class="admin-stat-content">
            <div class="admin-stat-value"><?= $pendingPayment ?></div>
            <div class="admin-stat-label">Ожидают оплаты</div>
        </div>
    </div>
</div>

<!-- Sales Chart (30 days) -->
<div class="admin-card">
    <div class="admin-card-header">
        <h2 class="admin-card-title"><i class="bi bi-graph-up-arrow"></i> Продажи за 30 дней</h2>
    </div>
    <div style="height:300px"><canvas id="salesChart30"></canvas></div>
</div>

<!-- Status + Top Products Row -->
<div class="stat-grid-2">
    <!-- Pie Chart: статусы -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2 class="admin-card-title"><i class="bi bi-pie-chart-fill"></i> По статусам</h2>
        </div>
        <div style="max-width:300px;margin:0 auto"><canvas id="statusPieChart"></canvas></div>
        <div class="stat-legend">
            <?php foreach ($statusStats as $key => $s): ?>
            <?php if ($s['count'] > 0): ?>
            <div class="stat-legend-item">
                <span class="stat-legend-dot" style="background:<?= $statusColors[$key] ?? '#94a3b8' ?>"></span>
                <span><?= Html::encode($s['label']) ?></span>
                <b><?= $s['count'] ?></b>
            </div>
            <?php endif; endforeach ?>
        </div>
    </div>

    <!-- Top Products -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2 class="admin-card-title"><i class="bi bi-trophy-fill"></i> Топ-10 товаров по выручке</h2>
        </div>
        <?php if (!empty($topProducts)): ?>
        <div style="overflow-x:auto">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Товар</th>
                        <th style="text-align:right">Заказы</th>
                        <th style="text-align:right">Кол-во</th>
                        <th style="text-align:right">Ср. цена</th>
                        <th style="text-align:right">Выручка</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($topProducts as $i => $p): ?>
                    <tr>
                        <td><span class="stat-rank"><?= $i + 1 ?></span></td>
                        <td><?= Html::encode($p['product_name'] ?? '—') ?></td>
                        <td style="text-align:right"><?= (int)($p['order_count'] ?? 0) ?></td>
                        <td style="text-align:right"><?= (int)($p['total_quantity'] ?? 0) ?></td>
                        <td style="text-align:right"><?= number_format($p['avg_price'] ?? 0, 0, '.', ' ') ?></td>
                        <td style="text-align:right;font-weight:700"><?= number_format($p['total_revenue'] ?? 0, 0, '.', ' ') ?> BYN</td>
                    </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <p style="text-align:center;color:var(--admin-text-secondary);padding:2rem">Нет данных</p>
        <?php endif ?>
    </div>
</div>

<!-- Staff Performance -->
<div class="stat-grid-2">
    <!-- Менеджеры -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2 class="admin-card-title"><i class="bi bi-person-workspace"></i> Менеджеры</h2>
        </div>
        <?php if (!empty($managerStats)): ?>
        <div style="overflow-x:auto">
            <table class="admin-table">
                <thead><tr><th>Имя</th><th style="text-align:right">Заказы</th><th style="text-align:right">Сумма</th></tr></thead>
                <tbody>
                    <?php foreach ($managerStats as $m): ?>
                    <?php $mSum = 0; foreach ($m->createdOrders as $o) $mSum += $o->total_amount; ?>
                    <tr>
                        <td><?= Html::encode($m->username) ?></td>
                        <td style="text-align:right"><span class="admin-badge admin-badge-info"><?= count($m->createdOrders) ?></span></td>
                        <td style="text-align:right;font-weight:600"><?= number_format($mSum, 0, '.', ' ') ?> BYN</td>
                    </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <p style="text-align:center;color:var(--admin-text-secondary);padding:2rem">Нет менеджеров</p>
        <?php endif ?>
    </div>

    <!-- Логисты -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2 class="admin-card-title"><i class="bi bi-truck"></i> Логисты</h2>
        </div>
        <?php if (!empty($logistStats)): ?>
        <div style="overflow-x:auto">
            <table class="admin-table">
                <thead><tr><th>Имя</th><th style="text-align:right">Всего</th><th style="text-align:right">Активных</th><th style="text-align:right">Завершено</th></tr></thead>
                <tbody>
                    <?php foreach ($logistStats as $l): ?>
                    <?php
                    $lActive = 0; $lDone = 0;
                    foreach ($l->assignedOrders as $o) {
                        if ($o->status === 'issued') $lDone++; else $lActive++;
                    }
                    ?>
                    <tr>
                        <td><?= Html::encode($l->username) ?></td>
                        <td style="text-align:right"><?= count($l->assignedOrders) ?></td>
                        <td style="text-align:right"><span class="admin-badge admin-badge-warning"><?= $lActive ?></span></td>
                        <td style="text-align:right"><span class="admin-badge admin-badge-success"><?= $lDone ?></span></td>
                    </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <p style="text-align:center;color:var(--admin-text-secondary);padding:2rem">Нет логистов</p>
        <?php endif ?>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function(){
    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    const textColor = isDark ? '#94a3b8' : '#64748b';
    const gridColor = isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)';

    // === Sales 30d ===
    const salesData = <?= json_encode($salesChart) ?>;
    new Chart(document.getElementById('salesChart30'), {
        type: 'bar',
        data: {
            labels: salesData.map(d => d.label),
            datasets: [
                { label: 'Заказы', data: salesData.map(d => d.orders), backgroundColor: 'rgba(59,130,246,0.65)', borderRadius: 4, yAxisID: 'y', order: 2 },
                { label: 'Выручка (BYN)', data: salesData.map(d => d.amount), type: 'line', borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,0.08)', fill: true, tension: 0.3, pointRadius: 2, yAxisID: 'y1', order: 1 }
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: { legend: { labels: { color: textColor, usePointStyle: true, padding: 14 } } },
            scales: {
                x: { grid: { display: false }, ticks: { color: textColor, maxRotation: 45, font: { size: 10 } } },
                y: { position: 'left', grid: { color: gridColor }, ticks: { color: textColor } },
                y1: { position: 'right', grid: { display: false }, ticks: { color: textColor } }
            }
        }
    });

    // === Status Pie ===
    const statusData = <?= json_encode(array_values($statusStats)) ?>;
    const statusColors = <?= json_encode(array_values(array_intersect_key($statusColors, $statusStats))) ?>;
    const activeStatuses = statusData.filter(s => s.count > 0);
    if (activeStatuses.length > 0) {
        new Chart(document.getElementById('statusPieChart'), {
            type: 'doughnut',
            data: {
                labels: activeStatuses.map(s => s.label),
                datasets: [{ data: activeStatuses.map(s => s.count), backgroundColor: statusColors.slice(0, activeStatuses.length), borderWidth: 0 }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                cutout: '60%'
            }
        });
    }
})();
</script>

