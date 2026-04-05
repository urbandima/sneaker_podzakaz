<?php
/**
 * Dashboard — Главная панель администратора
 * Виджеты с реальными данными из БД, графики, топ-товары, складские предупреждения
 *
 * @var yii\web\View $this
 * @var object $user
 * @var array $orderStats
 * @var array $productStats
 * @var array $userStats
 * @var array $topProducts
 * @var array $activeLogists
 * @var array $chartData
 * @var array|object $companySettings
 * @var bool $demoMode
 * @var array $operationalStats  B3.1
 * @var array $funnelData        B3.3
 * @var array $currencyInfo      B3.4
 */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Панель управления';

$statusMap = [
    'created' => ['label' => 'Новый', 'class' => 'info'],
    'pending' => ['label' => 'Ожидает', 'class' => 'warning'],
    'processing' => ['label' => 'В обработке', 'class' => 'warning'],
    'paid' => ['label' => 'Оплачен', 'class' => 'success'],
    'shipped' => ['label' => 'Отправлен', 'class' => 'primary'],
    'delivered' => ['label' => 'Доставлен', 'class' => 'success'],
    'completed' => ['label' => 'Завершён', 'class' => 'success'],
    'cancelled' => ['label' => 'Отменён', 'class' => 'danger'],
    'refunded' => ['label' => 'Возврат', 'class' => 'danger'],
];

$totalAmount = $orderStats['totalAmount'] ?? 0;
$amountFormatted = $totalAmount >= 1000 ? number_format($totalAmount / 1000, 1, '.', '') . 'K' : number_format($totalAmount, 0, '.', ' ');
?>

<!-- Header -->
<div class="admin-header">
    <div>
        <h1 class="admin-header-title"><?= Html::encode($this->title) ?></h1>
        <p class="dash-subtitle">
            <?= Html::encode($user->username ?? 'Admin') ?> · <?= date('d.m.Y, H:i') ?>
            <?php if ($demoMode): ?><span class="admin-badge admin-badge-warning" style="margin-left:0.5rem">Demo</span><?php endif ?>
        </p>
    </div>
    <div class="admin-header-actions">
        <a href="<?= Url::to(['/admin/order/create']) ?>" class="admin-btn admin-btn-primary">
            <i class="bi bi-plus-circle"></i> Новый заказ
        </a>
        <button class="admin-btn admin-btn-secondary" id="theme-toggle" title="Ctrl+D — тема">
            <i class="bi bi-moon-fill" id="theme-icon"></i>
        </button>
        <button class="admin-btn admin-btn-secondary" onclick="location.reload()" title="Ctrl+R">
            <i class="bi bi-arrow-clockwise"></i>
        </button>
    </div>
</div>

<!-- KPI Cards -->
<div class="admin-stats">
    <div class="admin-stat-card">
        <div class="admin-stat-icon primary"><i class="bi bi-bag-check-fill"></i></div>
        <div class="admin-stat-content">
            <div class="admin-stat-value"><?= number_format($orderStats['total'] ?? 0) ?></div>
            <div class="admin-stat-label">Всего заказов</div>
            <div class="dash-stat-sub">
                <span class="admin-badge admin-badge-info"><?= $orderStats['today'] ?? 0 ?> сегодня</span>
            </div>
        </div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-icon success"><i class="bi bi-currency-exchange"></i></div>
        <div class="admin-stat-content">
            <div class="admin-stat-value"><?= $amountFormatted ?> <small>BYN</small></div>
            <div class="admin-stat-label">Выручка</div>
            <div class="dash-stat-sub">
                <span class="admin-badge admin-badge-success"><?= $orderStats['thisMonth'] ?? 0 ?> за месяц</span>
            </div>
        </div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-icon warning"><i class="bi bi-collection"></i></div>
        <div class="admin-stat-content">
            <div class="admin-stat-value"><?= number_format($productStats['total'] ?? 0) ?></div>
            <div class="admin-stat-label">Товары</div>
            <div class="dash-stat-sub">
                <span class="admin-badge admin-badge-success"><?= $productStats['active'] ?? 0 ?> активных</span>
                <?php if (($productStats['outOfStock'] ?? 0) > 0): ?>
                    <span class="admin-badge admin-badge-danger"><?= $productStats['outOfStock'] ?> нет</span>
                <?php endif ?>
            </div>
        </div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-icon info"><i class="bi bi-people-fill"></i></div>
        <div class="admin-stat-content">
            <div class="admin-stat-value"><?= number_format($userStats['total'] ?? 0) ?></div>
            <div class="admin-stat-label">Персонал</div>
            <div class="dash-stat-sub">
                <span class="admin-badge admin-badge-info"><?= $userStats['admins'] ?? 0 ?> адм</span>
                <span class="admin-badge admin-badge-warning"><?= $userStats['managers'] ?? 0 ?> мен</span>
            </div>
        </div>
    </div>
</div>

<!-- B3.1 Operational Control Widgets -->
<?php
$opStats = $operationalStats ?? ['unprocessed2h' => 0, 'delayed3d' => 0, 'awaitingPoizon' => 0];
?>
<div class="dash-operational-grid">
    <a href="<?= Url::to(['/admin/order', 'status' => 'created']) ?>" class="dash-op-widget">
        <div class="dash-op-widget-icon danger"><i class="bi bi-clock-history"></i></div>
        <div class="dash-op-widget-value danger"><?= (int)($opStats['unprocessed2h'] ?? 0) ?></div>
        <div class="dash-op-widget-label">Необработанных<br>&gt;2 часов</div>
    </a>
    <a href="<?= Url::to(['/admin/order']) ?>" class="dash-op-widget">
        <div class="dash-op-widget-icon warning"><i class="bi bi-exclamation-triangle-fill"></i></div>
        <div class="dash-op-widget-value warning"><?= (int)($opStats['delayed3d'] ?? 0) ?></div>
        <div class="dash-op-widget-label">Задержек<br>&gt;3 дней без изменений</div>
    </a>
    <a href="<?= Url::to(['/admin/order', 'status' => 'paid']) ?>" class="dash-op-widget">
        <div class="dash-op-widget-icon info"><i class="bi bi-bag-x-fill"></i></div>
        <div class="dash-op-widget-value info"><?= (int)($opStats['awaitingPoizon'] ?? 0) ?></div>
        <div class="dash-op-widget-label">Ожидают Poizon<br>оплачено, не заказано</div>
    </a>
</div>

<!-- B3.4 CNY Rate Widget -->
<?php
$cnyInfo = $currencyInfo ?? ['rate' => 0.45, 'updated_at' => null, 'source' => 'default'];
$cnyRate = (float)($cnyInfo['rate'] ?? 0.45);
$cnyUpdated = $cnyInfo['updated_at'] ?? null;
?>
<div class="dash-cny-widget">
    <div>
        <div class="dash-cny-rate-main">1 CNY = <?= number_format($cnyRate, 4, '.', '') ?> BYN</div>
        <div class="dash-cny-rate-meta">
            <?php if ($cnyUpdated): ?>
                Обновлено: <?= Html::encode($cnyUpdated) ?>
            <?php else: ?>
                Дата обновления неизвестна
            <?php endif ?>
            &nbsp;·&nbsp; Источник: <?= Html::encode($cnyInfo['source'] ?? '—') ?>
        </div>
    </div>
    <button class="admin-btn admin-btn-secondary admin-btn-sm" id="dash-update-cny" onclick="updateCnyRate(this)">
        <i class="bi bi-arrow-clockwise"></i> Обновить
    </button>
</div>

<!-- Order Pipeline -->
<div class="dash-pipeline">
    <div class="dash-pipe-item">
        <div class="dash-pipe-num dash-pipe-info"><?= $orderStats['pending'] ?? 0 ?></div>
        <div class="dash-pipe-label">Ожидают</div>
    </div>
    <div class="dash-pipe-arrow">→</div>
    <div class="dash-pipe-item">
        <div class="dash-pipe-num dash-pipe-warning"><?= $orderStats['processing'] ?? 0 ?></div>
        <div class="dash-pipe-label">В работе</div>
    </div>
    <div class="dash-pipe-arrow">→</div>
    <div class="dash-pipe-item">
        <div class="dash-pipe-num dash-pipe-success"><?= $orderStats['completed'] ?? 0 ?></div>
        <div class="dash-pipe-label">Завершены</div>
    </div>
</div>

<!-- Charts Row -->
<div class="dash-grid-2">
    <!-- Sales Chart — B3.2 30 days + comparison -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2 class="admin-card-title"><i class="bi bi-bar-chart-line-fill"></i> Продажи за 30 дней</h2>
            <div class="dash-chart-legend">
                <span class="dash-chart-legend-item">
                    <span class="dash-chart-legend-dot" style="background:#008060"></span> Текущий период
                </span>
                <span class="dash-chart-legend-item">
                    <span class="dash-chart-legend-dot" style="background:#94a3b8"></span> Предыдущий период
                </span>
            </div>
        </div>
        <div style="max-width: 100%; overflow: hidden;">
            <canvas id="salesChart" height="350" style="max-width: 100%;"></canvas>
        </div>
    </div>

    <!-- Top Products -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2 class="admin-card-title"><i class="bi bi-trophy-fill"></i> Топ товары</h2>
            <span class="admin-badge admin-badge-info">30 дн</span>
        </div>
        <?php if (!empty($topProducts)): ?>
        <div class="dash-top-list">
            <?php foreach ($topProducts as $i => $p): ?>
            <div class="dash-top-item">
                <span class="dash-top-rank"><?= $i + 1 ?></span>
                <div class="dash-top-info">
                    <span class="dash-top-name"><?= Html::encode($p['product_name'] ?? '—') ?></span>
                    <span class="dash-top-meta"><?= (int)($p['order_count'] ?? 0) ?> заказов · <?= (int)($p['total_quantity'] ?? 0) ?> шт</span>
                </div>
                <span class="dash-top-price"><?= number_format($p['avg_price'] ?? 0, 0, '.', ' ') ?> BYN</span>
            </div>
            <?php endforeach ?>
        </div>
        <?php else: ?>
        <p class="dash-empty">Нет данных о продажах</p>
        <?php endif ?>
    </div>
</div>

<!-- B3.3 Conversion Funnel -->
<?php
$funnel = $funnelData ?? ['views' => 0, 'carts' => 0, 'orders' => 0];
$fViews = max(1, (int)($funnel['views'] ?? 0));
$fCarts = (int)($funnel['carts'] ?? 0);
$fOrders = (int)($funnel['orders'] ?? 0);
$cartPct = $fViews > 0 ? round($fCarts / $fViews * 100, 1) : 0;
$orderPct = $fViews > 0 ? round($fOrders / $fViews * 100, 1) : 0;
$orderFromCartPct = $fCarts > 0 ? round($fOrders / $fCarts * 100, 1) : 0;
?>
<div class="admin-card">
    <div class="admin-card-header">
        <h2 class="admin-card-title"><i class="bi bi-funnel-fill"></i> Воронка конверсии</h2>
        <span class="admin-badge admin-badge-info">All time</span>
    </div>
    <div class="dash-funnel">
        <!-- Step 1: Views -->
        <div class="dash-funnel-step">
            <div class="dash-funnel-meta">
                <div class="dash-funnel-count"><?= number_format($funnel['views'] ?? 0) ?></div>
                <div class="dash-funnel-pct">Просмотры</div>
            </div>
            <div class="dash-funnel-bar-wrap">
                <div class="dash-funnel-bar step-1" style="width:100%">
                    <span class="dash-funnel-bar-label">100%</span>
                </div>
            </div>
        </div>
        <div class="dash-funnel-arrow">↓ <?= $cartPct ?>% добавили в корзину</div>
        <!-- Step 2: Carts -->
        <div class="dash-funnel-step">
            <div class="dash-funnel-meta">
                <div class="dash-funnel-count"><?= number_format($funnel['carts'] ?? 0) ?></div>
                <div class="dash-funnel-pct">В корзине</div>
            </div>
            <div class="dash-funnel-bar-wrap">
                <div class="dash-funnel-bar step-2" style="width:<?= min(100, $cartPct) ?>%">
                    <span class="dash-funnel-bar-label"><?= $cartPct ?>%</span>
                </div>
            </div>
        </div>
        <div class="dash-funnel-arrow">↓ <?= $orderFromCartPct ?>% оформили заказ</div>
        <!-- Step 3: Orders -->
        <div class="dash-funnel-step">
            <div class="dash-funnel-meta">
                <div class="dash-funnel-count"><?= number_format($funnel['orders'] ?? 0) ?></div>
                <div class="dash-funnel-pct">Заказы</div>
            </div>
            <div class="dash-funnel-bar-wrap">
                <div class="dash-funnel-bar step-3" style="width:<?= max(2, min(100, $orderPct)) ?>%">
                    <span class="dash-funnel-bar-label"><?= $orderPct ?>%</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="admin-card">
    <div class="admin-card-header">
        <h2 class="admin-card-title"><i class="bi bi-lightning-fill"></i> Быстрые действия</h2>
        <kbd class="dash-kbd">Ctrl+K</kbd>
    </div>
    <div class="dash-actions-grid">
        <a href="<?= Url::to(['/admin/order/create']) ?>" class="dash-action-card">
            <i class="bi bi-plus-circle-fill"></i>
            <span>Новый заказ</span>
        </a>
        <a href="<?= Url::to(['/admin/product']) ?>" class="dash-action-card">
            <i class="bi bi-collection"></i>
            <span>Товары</span>
        </a>
        <a href="<?= Url::to(['/admin/customer']) ?>" class="dash-action-card">
            <i class="bi bi-people-fill"></i>
            <span>Клиенты</span>
        </a>
        <a href="<?= Url::to(['/admin/coupon']) ?>" class="dash-action-card">
            <i class="bi bi-ticket-detailed-fill"></i>
            <span>Купоны</span>
        </a>
        <a href="<?= Url::to(['/admin/statistics']) ?>" class="dash-action-card">
            <i class="bi bi-bar-chart-line-fill"></i>
            <span>Аналитика</span>
        </a>
        <a href="<?= Url::to(['/admin/import']) ?>" class="dash-action-card">
            <i class="bi bi-cloud-arrow-up-fill"></i>
            <span>Импорт</span>
        </a>
    </div>
</div>

<!-- System Info Bar -->
<div class="dash-sys-bar">
    <span><b>Yii</b> <?= Yii::getVersion() ?></span>
    <span><b>PHP</b> <?= PHP_VERSION ?></span>
    <span><b>БД</b> sneakerhead</span>
    <span><b>ENV</b> <?= YII_ENV ?></span>
    <span><b>Время</b> <?= date('H:i:s') ?></span>
</div>

<!-- Chart.js CDN с fallback -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// Fallback если CDN не загрузился
if (typeof Chart === 'undefined') {
    document.write('<script src="/js/chart.min.js"><\/script>');
}
</script>

<!-- Передача данных для chart -->
<script>
window.chartData = <?= json_encode($chartData ?? []) ?>;
</script>

<script>
// B3.2 — Инициализация графика с двумя линиями (30 дней)
document.addEventListener('DOMContentLoaded', function () {
    var ctx = document.getElementById('salesChart');
    if (!ctx || typeof Chart === 'undefined' || !window.chartData || !window.chartData.length) return;

    var labels      = window.chartData.map(function(d){ return d.day || d.date; });
    var curOrders   = window.chartData.map(function(d){ return d.orders || 0; });
    var prevOrders  = window.chartData.map(function(d){ return d.prev_orders || 0; });

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Заказы (текущий период)',
                    data: curOrders,
                    borderColor: '#008060',
                    backgroundColor: 'rgba(0,128,96,0.08)',
                    fill: true,
                    tension: 0.35,
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    borderWidth: 2,
                },
                {
                    label: 'Заказы (пред. период)',
                    data: prevOrders,
                    borderColor: '#94a3b8',
                    backgroundColor: 'rgba(148,163,184,0.05)',
                    fill: false,
                    tension: 0.35,
                    pointRadius: 2,
                    pointHoverRadius: 4,
                    borderWidth: 1.5,
                    borderDash: [5, 3],
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(ctx) {
                            return ctx.dataset.label + ': ' + ctx.parsed.y + ' заказов';
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: { color: 'rgba(0,0,0,0.04)' },
                    ticks: { maxTicksLimit: 10, font: { size: 11 } }
                },
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(0,0,0,0.04)' },
                    ticks: { font: { size: 11 }, precision: 0 }
                }
            }
        }
    });
});

// B3.4 — Обновление курса CNY
function updateCnyRate(btn) {
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-arrow-clockwise" style="animation:spin 1s linear infinite"></i> Обновляем...';
    }
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '/admin/dashboard/update-cny-rate', true);
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    var csrf = document.querySelector('meta[name="csrf-token"]');
    xhr.send(csrf ? '_csrf=' + encodeURIComponent(csrf.getAttribute('content')) : '');
    xhr.onload = function() {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Обновить';
        }
        try {
            var data = JSON.parse(xhr.responseText);
            if (data.success && data.rate) {
                // Обновляем отображение
                var rateEl = document.querySelector('.dash-cny-rate-main');
                if (rateEl) rateEl.textContent = '1 CNY = ' + parseFloat(data.rate).toFixed(4) + ' BYN';
                var metaEl = document.querySelector('.dash-cny-rate-meta');
                if (metaEl && data.updated_at) {
                    metaEl.innerHTML = 'Обновлено: ' + data.updated_at + ' &nbsp;·&nbsp; Источник: ' + (data.source || 'nbrb');
                }
                // Сбрасываем localStorage для калькулятора
                localStorage.removeItem('cny_rate');
                localStorage.removeItem('cny_rate_time');
            } else {
                alert('Ошибка обновления курса: ' + (data.message || 'неизвестная ошибка'));
            }
        } catch(e) { /* ignore */ }
    };
    xhr.onerror = function() {
        if (btn) { btn.disabled = false; btn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Обновить'; }
        alert('Сетевая ошибка при обновлении курса');
    };
}
</script>

<style>
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
</style>


<style>
.dash-subtitle { margin: 0.25rem 0 0; color: var(--admin-text-secondary); font-size: 0.875rem; }

/* Pipeline */
.dash-pipeline { display: flex; align-items: center; justify-content: center; gap: 1rem; margin-bottom: 1.5rem; padding: 1rem; background: var(--admin-surface); border: 1px solid var(--admin-border); border-radius: var(--admin-radius-lg); }
.dash-pipe-item { text-align: center; }
.dash-pipe-num { font-size: 1.75rem; font-weight: 800; line-height: 1; }
.dash-pipe-label { font-size: 0.75rem; color: var(--admin-text-secondary); margin-top: 0.25rem; }
.dash-pipe-arrow { font-size: 1.25rem; color: var(--admin-text-secondary); }
.dash-pipe-info { color: var(--admin-info); }
.dash-pipe-warning { color: var(--admin-warning); }
.dash-pipe-success { color: var(--admin-success); }

/* 2-col grid */
.dash-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem; max-width: 100%; }

/* Chart container fix */
.dash-grid-2 .admin-card canvas { 
    max-width: 100% !important; 
    height: auto !important;
}

/* Top Products */
.dash-top-list { display: flex; flex-direction: column; gap: 0.5rem; }
.dash-top-item { display: flex; align-items: center; gap: 0.75rem; padding: 0.5rem 0; border-bottom: 1px solid var(--admin-border-light); }
.dash-top-item:last-child { border-bottom: none; }
.dash-top-rank { width: 28px; height: 28px; border-radius: 50%; background: var(--admin-border-light); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.8rem; color: var(--admin-text-secondary); flex-shrink: 0; }
.dash-top-item:nth-child(1) .dash-top-rank { background: #fef3c7; color: #92400e; }
.dash-top-item:nth-child(2) .dash-top-rank { background: #e2e8f0; color: #475569; }
.dash-top-item:nth-child(3) .dash-top-rank { background: #fed7aa; color: #9a3412; }
.dash-top-info { flex: 1; min-width: 0; }
.dash-top-name { display: block; font-weight: 600; font-size: 0.875rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.dash-top-meta { font-size: 0.75rem; color: var(--admin-text-secondary); }
.dash-top-price { font-weight: 700; font-size: 0.875rem; white-space: nowrap; }
.dash-empty { text-align: center; color: var(--admin-text-secondary); padding: 2rem 0; }

/* Actions Grid */
.dash-actions-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 0.75rem; }
.dash-action-card { display: flex; flex-direction: column; align-items: center; gap: 0.5rem; padding: 1.25rem 0.75rem; border: 1px solid var(--admin-border); border-radius: var(--admin-radius-lg); text-decoration: none; color: var(--admin-text); transition: all 0.15s; background: var(--admin-surface); }
.dash-action-card:hover { border-color: var(--admin-accent); transform: translateY(-2px); box-shadow: var(--admin-shadow-md); color: var(--admin-accent); }
.dash-action-card i { font-size: 1.5rem; }
.dash-action-card span { font-size: 0.8rem; font-weight: 600; }

/* Keyboard hint */
.dash-kbd { background: var(--admin-border-light); border: 1px solid var(--admin-border); border-radius: 4px; padding: 0.15rem 0.4rem; font-size: 0.7rem; font-family: monospace; color: var(--admin-text-secondary); }

/* Stat sub badges */
.dash-stat-sub { margin-top: 0.25rem; display: flex; gap: 0.25rem; flex-wrap: wrap; }
.dash-stat-sub .admin-badge { font-size: 0.65rem; padding: 0.15rem 0.5rem; }

/* System bar */
.dash-sys-bar { display: flex; flex-wrap: wrap; gap: 1.5rem; padding: 0.75rem 1rem; background: var(--admin-surface); border: 1px solid var(--admin-border); border-radius: var(--admin-radius); font-size: 0.75rem; color: var(--admin-text-secondary); margin-top: 0.5rem; }
.dash-sys-bar b { color: var(--admin-text); }

/* Badge additions */
.admin-badge-info { background: #dbeafe; color: #1e40af; }
.admin-badge-primary { background: #dbeafe; color: #1e40af; }

/* Responsive */
@media (max-width: 900px) {
    .dash-grid-2 { grid-template-columns: 1fr; }
    .dash-pipeline { flex-wrap: wrap; }
}
@media (max-width: 768px) {
    .admin-header { flex-direction: column; gap: 1rem; text-align: center; }
    .admin-header-actions { justify-content: center; }
    .admin-stats { grid-template-columns: 1fr 1fr; }
    .dash-actions-grid { grid-template-columns: repeat(3, 1fr); }
}
@media (max-width: 480px) {
    .admin-stats { grid-template-columns: 1fr; }
    .dash-actions-grid { grid-template-columns: repeat(2, 1fr); }
}
</style>
