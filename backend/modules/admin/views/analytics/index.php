<?php

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Аналитика и отчёты';
$dateFrom = $dateFrom ?? date('Y-m-d', strtotime('-7 days'));
$dateTo = $dateTo ?? date('Y-m-d');

// Данные для графика
$chartLabels = [];
$chartOrders = [];
$chartRevenue = [];
foreach ($salesByDay ?? [] as $day) {
    $chartLabels[] = date('d.m', strtotime($day['date']));
    $chartOrders[] = $day['orders_count'];
    $chartRevenue[] = $day['revenue'];
}

// Источники трафика (демо-данные если нет)
$trafficSources = $trafficSources ?? [
    ['source' => 'organic', 'label' => 'Органический поиск', 'count' => 2450, 'percent' => 45, 'color' => '#10b981'],
    ['source' => 'social', 'label' => 'Социальные сети', 'count' => 1230, 'percent' => 23, 'color' => '#3b82f6'],
    ['source' => 'direct', 'label' => 'Прямой заход', 'count' => 890, 'percent' => 16, 'color' => '#f59e0b'],
    ['source' => 'referral', 'label' => 'Рефералы', 'count' => 450, 'percent' => 8, 'color' => '#8b5cf6'],
    ['source' => 'ads', 'label' => 'Реклама', 'count' => 430, 'percent' => 8, 'color' => '#ef4444'],
];
?>

<div class="admin-header">
    <h1 class="admin-header-title"><?= Html::encode($this->title) ?></h1>
    <div style="display: flex; gap: 0.5rem; align-items: center;">
        <span id="last-updated" style="font-size: 0.875rem; color: var(--admin-text-secondary);">Обновлено: <?= date('H:i:s') ?></span>
        <button class="admin-btn admin-btn-secondary" onclick="refreshAnalytics()" id="refresh-btn">
            <i class="bi bi-arrow-clockwise"></i> Обновить
        </button>
        <a href="<?= Url::to(['index', 'period' => '7']) ?>" class="admin-btn admin-btn-secondary <?= $period == '7' ? 'active' : '' ?>">7 дней</a>
        <a href="<?= Url::to(['index', 'period' => '30']) ?>" class="admin-btn admin-btn-secondary <?= $period == '30' ? 'active' : '' ?>">30 дней</a>
        <a href="<?= Url::to(['index', 'period' => '90']) ?>" class="admin-btn admin-btn-secondary <?= $period == '90' ? 'active' : '' ?>">90 дней</a>
    </div>
</div>

<!-- Statistics Cards -->
<div class="admin-stats">
    <div class="admin-stat-card">
        <p class="admin-stat-number" id="stat-revenue"><?= number_format($revenueStats['total_revenue'] ?? 0, 0, ',', ' ') ?></p>
        <p class="admin-stat-label">Общая выручка, BYN</p>
        <span class="admin-badge admin-badge-success">+12%</span>
    </div>
    <div class="admin-stat-card" style="border-left-color: var(--admin-success);">
        <p class="admin-stat-number" id="stat-orders"><?= $revenueStats['total_orders'] ?? 0 ?></p>
        <p class="admin-stat-label">Всего заказов</p>
        <span class="admin-badge admin-badge-info">Количество</span>
    </div>
    <div class="admin-stat-card" style="border-left-color: var(--admin-warning);">
        <p class="admin-stat-number" id="stat-avg"><?= number_format($revenueStats['avg_order_value'] ?? 0, 0, ',', ' ') ?></p>
        <p class="admin-stat-label">Средний чек, BYN</p>
        <span class="admin-badge admin-badge-warning">AVG</span>
    </div>
    <div class="admin-stat-card" style="border-left-color: var(--admin-info);">
        <p class="admin-stat-number"><?= count($topProducts ?? []) ?></p>
        <p class="admin-stat-label">Топ товаров</p>
        <span class="admin-badge admin-badge-primary">Бестселлеры</span>
    </div>
</div>

<!-- График продаж -->
<div class="admin-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <h2 class="admin-card-title">
            <i class="bi bi-graph-up"></i> Продажи по дням
        </h2>
        <div style="display: flex; gap: 0.5rem;">
            <button class="admin-btn admin-btn-sm admin-btn-secondary" onclick="toggleChartType('bar')">Столбцы</button>
            <button class="admin-btn admin-btn-sm admin-btn-secondary" onclick="toggleChartType('line')">Линия</button>
        </div>
    </div>
    <div style="position: relative; height: 300px;">
        <canvas id="salesChart"></canvas>
        <?php if (empty($salesByDay)): ?>
        <div id="no-data-overlay" style="position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; background: rgba(255,255,255,0.8);">
            <div style="text-align: center;">
                <i class="bi bi-inbox" style="font-size: 3rem; color: var(--admin-text-secondary);"></i>
                <p style="margin-top: 1rem; color: var(--admin-text-secondary);">Нет данных за выбранный период</p>
                <button class="admin-btn admin-btn-primary" onclick="generateDemoData()" style="margin-top: 0.5rem;">
                    Загрузить демо-данные
                </button>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Топ товаров с фильтром -->
<div class="admin-card" style="margin-top: 1.5rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <h2 class="admin-card-title">
            <i class="bi bi-trophy"></i> Топ продаваемых товаров
        </h2>
        <div style="display: flex; gap: 0.5rem; align-items: center;">
            <select id="top-filter" class="admin-form-select" style="width: auto;" onchange="filterTopProducts()">
                <option value="qty">По количеству</option>
                <option value="revenue">По выручке</option>
                <option value="orders">По заказам</option>
            </select>
            <a href="<?= Url::to(['/admin/analytics/export', 'type' => 'products', 'period' => $period]) ?>" class="admin-btn admin-btn-sm admin-btn-secondary">
                <i class="bi bi-download"></i> Экспорт
            </a>
        </div>
    </div>
    <div style="overflow-x: auto;">
        <?php if (!empty($topProducts)): ?>
        <table class="admin-table" id="top-products-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Товар</th>
                    <th style="text-align: right;" data-sort="qty">Кол-во ↕</th>
                    <th style="text-align: right;" data-sort="revenue">Выручка ↕</th>
                    <th style="text-align: right;" data-sort="orders">Заказов ↕</th>
                    <th>Динамика</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($topProducts as $i => $product): 
                    $trend = rand(-15, 25); // Демо-тренд
                    $trendClass = $trend > 0 ? 'success' : ($trend < 0 ? 'danger' : 'secondary');
                    $trendIcon = $trend > 0 ? 'arrow-up' : ($trend < 0 ? 'arrow-down' : 'dash');
                ?>
                <tr>
                    <td style="font-weight: 700; color: var(--admin-text-secondary);"><?= $i + 1 ?></td>
                    <td>
                        <strong><?= Html::encode($product['product_name']) ?></strong>
                    </td>
                    <td style="text-align: right; font-weight: 600;" data-qty="<?= $product['total_qty'] ?>">
                        <?= $product['total_qty'] ?> шт
                    </td>
                    <td style="text-align: right; font-weight: 600;" data-revenue="<?= $product['total_revenue'] ?>">
                        <?= number_format($product['total_revenue'], 0, ',', ' ') ?> BYN
                    </td>
                    <td style="text-align: right;" data-orders="<?= $product['orders_count'] ?>">
                        <?= $product['orders_count'] ?>
                    </td>
                    <td>
                        <span class="admin-badge admin-badge-<?= $trendClass ?>">
                            <i class="bi bi-<?= $trendIcon ?>"></i> <?= abs($trend) ?>%
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p style="text-align: center; color: var(--admin-text-secondary); padding: 2rem;">Нет данных о продажах</p>
        <?php endif; ?>
    </div>
</div>

<!-- Источники трафика -->
<div class="admin-card" style="margin-top: 1.5rem;">
    <h2 class="admin-card-title">
        <i class="bi bi-diagram-3"></i> Сегментация по источникам трафика
    </h2>
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-top: 1.5rem;">
        <div style="position: relative; height: 250px; display: flex; align-items: center; justify-content: center;">
            <canvas id="trafficChart"></canvas>
        </div>
        <div>
            <h4 style="margin: 0 0 1rem; font-size: 0.875rem; color: var(--admin-text-secondary); text-transform: uppercase;">
                Распределение по каналам
            </h4>
            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                <?php foreach ($trafficSources as $index => $source): ?>
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <div style="width: 12px; height: 12px; border-radius: 2px; background: <?= $source['color'] ?? ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'][(int)$index % 5] ?>;"></div>
                    <div style="flex: 1; display: flex; justify-content: space-between;">
                        <span style="font-weight: 500;"><?= $source['label'] ?? 'Unknown' ?></span>
                        <span style="color: var(--admin-text-secondary);"><?= number_format($source['count'] ?? 0) ?> (<?= $source['percent'] ?? 0 ?>%)</span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div style="margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid var(--admin-border);">
                <div style="display: flex; justify-content: space-between; font-weight: 600;">
                    <span>Всего визитов:</span>
                    <span><?= number_format(array_sum(array_column($trafficSources, 'count'))) ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ROI Калькулятор (встроенный) -->
<div class="admin-card" style="margin-top: 1.5rem;">
    <h2 class="admin-card-title">
        <i class="bi bi-calculator"></i> Калькулятор ROI
    </h2>
    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; margin-top: 1.5rem;">
        <div>
            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; font-size: 0.875rem;">Рекламный бюджет (BYN)</label>
            <input type="number" id="roi-budget" class="admin-form-input" placeholder="1000" oninput="calculateROI()">
        </div>
        <div>
            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; font-size: 0.875rem;">Выручка (BYN)</label>
            <input type="number" id="roi-revenue" class="admin-form-input" placeholder="5000" oninput="calculateROI()">
        </div>
        <div>
            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; font-size: 0.875rem;">Результат</label>
            <div id="roi-result" style="padding: 0.75rem; background: var(--admin-bg); border-radius: 0.5rem; font-weight: 700; font-size: 1.125rem; text-align: center;">
                ROI: —
            </div>
        </div>
    </div>
    <div style="margin-top: 1rem; display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;">
        <div style="padding: 1rem; background: var(--admin-bg); border-radius: 0.5rem;">
            <div style="font-size: 0.75rem; color: var(--admin-text-secondary); text-transform: uppercase;">Прибыль</div>
            <div id="roi-profit" style="font-size: 1.5rem; font-weight: 700;">—</div>
        </div>
        <div style="padding: 1rem; background: var(--admin-bg); border-radius: 0.5rem;">
            <div style="font-size: 0.75rem; color: var(--admin-text-secondary); text-transform: uppercase;">ROMI</div>
            <div id="roi-romi" style="font-size: 1.5rem; font-weight: 700;">—</div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
// График продаж
let salesChart;
let chartType = 'bar';

function initSalesChart() {
    const ctx = document.getElementById('salesChart');
    if (!ctx) return;
    
    const labels = <?= json_encode($chartLabels) ?>;
    const ordersData = <?= json_encode($chartOrders) ?>;
    const revenueData = <?= json_encode($chartRevenue) ?>;
    
    if (labels.length === 0) return;
    
    document.getElementById('no-data-overlay')?.style.display = 'none';
    
    salesChart = new Chart(ctx, {
        type: chartType,
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Заказы',
                    data: ordersData,
                    backgroundColor: 'rgba(59, 130, 246, 0.5)',
                    borderColor: 'rgb(59, 130, 246)',
                    borderWidth: 2,
                    yAxisID: 'y'
                },
                {
                    label: 'Выручка (BYN)',
                    data: revenueData,
                    backgroundColor: 'rgba(16, 185, 129, 0.5)',
                    borderColor: 'rgb(16, 185, 129)',
                    borderWidth: 2,
                    type: 'line',
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false
            },
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: { display: true, text: 'Заказы' }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: { display: true, text: 'Выручка' },
                    grid: { drawOnChartArea: false }
                }
            }
        }
    });
}

function toggleChartType(type) {
    chartType = type;
    if (salesChart) {
        salesChart.config.type = type;
        salesChart.update();
    }
}

// График источников трафика
function initTrafficChart() {
    const ctx = document.getElementById('trafficChart');
    if (!ctx) return;
    
    const sources = <?= json_encode($trafficSources) ?>;
    
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: sources.map(s => s.label),
            datasets: [{
                data: sources.map(s => s.count),
                backgroundColor: sources.map(s => s.color),
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            }
        }
    });
}

// Фильтрация топ товаров
function filterTopProducts() {
    const filter = document.getElementById('top-filter').value;
    const table = document.getElementById('top-products-table');
    if (!table) return;
    
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    
    rows.sort((a, b) => {
        const aVal = parseFloat(a.querySelector(`[data-${filter}]`)?.dataset[filter] || 0);
        const bVal = parseFloat(b.querySelector(`[data-${filter}]`)?.dataset[filter] || 0);
        return bVal - aVal;
    });
    
    rows.forEach(row => tbody.appendChild(row));
    
    // Обновляем номера
    rows.forEach((row, i) => {
        row.querySelector('td:first-child').textContent = i + 1;
    });
}

// ROI Калькулятор
function calculateROI() {
    const budget = parseFloat(document.getElementById('roi-budget').value) || 0;
    const revenue = parseFloat(document.getElementById('roi-revenue').value) || 0;
    
    if (budget === 0) {
        document.getElementById('roi-result').textContent = 'ROI: —';
        document.getElementById('roi-profit').textContent = '—';
        document.getElementById('roi-romi').textContent = '—';
        return;
    }
    
    const profit = revenue - budget;
    const roi = ((profit / budget) * 100).toFixed(1);
    const romi = ((revenue / budget) * 100).toFixed(1);
    
    const roiEl = document.getElementById('roi-result');
    roiEl.textContent = `ROI: ${roi > 0 ? '+' : ''}${roi}%`;
    roiEl.style.background = roi > 0 ? '#d1fae5' : (roi < 0 ? '#fee2e2' : 'var(--admin-bg)');
    roiEl.style.color = roi > 0 ? '#065f46' : (roi < 0 ? '#991b1b' : 'var(--admin-text)');
    
    document.getElementById('roi-profit').textContent = profit.toFixed(0) + ' BYN';
    document.getElementById('roi-romi').textContent = romi + '%';
}

// Генерация демо-данных
function generateDemoData() {
    fetch('/admin/analytics/generate-demo', { method: 'POST' })
        .then(() => location.reload());
}

// Инициализация
document.addEventListener('DOMContentLoaded', function() {
    initSalesChart();
    initTrafficChart();
});

// Auto-refresh
let autoRefreshInterval;
function refreshAnalytics() {
    const btn = document.getElementById('refresh-btn');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-arrow-clockwise spin"></i> Обновление...';
    }
    
    location.reload();
}

function startAutoRefresh() {
    autoRefreshInterval = setInterval(() => {
        fetch('/admin/analytics/api-stats')
            .then(r => r.json())
            .then(data => {
                if (data.revenueStats) {
                    document.getElementById('stat-revenue').textContent = 
                        data.revenueStats.total_revenue.toLocaleString();
                    document.getElementById('stat-orders').textContent = 
                        data.revenueStats.total_orders;
                    document.getElementById('stat-avg').textContent = 
                        Math.round(data.revenueStats.avg_order_value).toLocaleString();
                    document.getElementById('last-updated').textContent = 
                        'Обновлено: ' + new Date().toLocaleTimeString();
                }
            });
    }, 30000);
}

startAutoRefresh();
</script>

<style>
@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
.spin {
    animation: spin 1s linear infinite;
}
.admin-form-input {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1px solid var(--admin-border);
    border-radius: 0.5rem;
    background: var(--admin-bg);
    color: var(--admin-text);
    font-size: 1rem;
}
.admin-form-input:focus {
    outline: none;
    border-color: var(--admin-primary);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}
</style>
