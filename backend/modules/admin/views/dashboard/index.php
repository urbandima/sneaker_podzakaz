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
    'new' => ['label' => 'Новый', 'class' => 'info'],
    'paid' => ['label' => 'Оплачен', 'class' => 'success'],
    'confirmed_and_paid' => ['label' => 'Подтвержден и оплачен', 'class' => 'primary'],
    'ordered' => ['label' => 'Заказано', 'class' => 'warning'],
    'awaiting_warehouse' => ['label' => 'Ожидается на складе', 'class' => 'info'],
    'international_delivery' => ['label' => 'В международной доставке', 'class' => 'primary'],
    'at_warehouse' => ['label' => 'На складе', 'class' => 'success'],
    'local_delivery' => ['label' => 'В доставке', 'class' => 'primary'],
    'delivered' => ['label' => 'Выдан', 'class' => 'success'],
    'canceled' => ['label' => 'Отменен', 'class' => 'danger'],
    'refunded' => ['label' => 'Возврат', 'class' => 'danger'],
];

$totalAmount = $orderStats['totalAmount'] ?? 0;
$amountFormatted = $totalAmount >= 1000 ? number_format($totalAmount / 1000, 1, '.', '') . 'K' : number_format($totalAmount, 0, '.', ' ');
?>

<!-- Header -->
<?php
$this->params['headerActions'] = [];
?>

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
    <a href="<?= Url::to(['/admin/order', 'status' => 'new', 'created_before' => '-2 hours']) ?>" class="dash-op-widget">
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

<!-- X4/X9: Catalog health alerts -->
<?php
$noCat = $productsNoCategoryCount ?? 0;
$badBrand = $brandMismatchCount ?? 0;
if ($noCat > 0 || $badBrand > 0): ?>
<div class="dash-catalog-alerts" style="display:flex;gap:12px;margin-bottom:16px;flex-wrap:wrap">
    <?php if ($noCat > 0): ?>
    <a href="<?= Url::to(['/admin/catalog/product', 'category_id' => '']) ?>"
       class="dash-op-widget" style="flex:1;min-width:220px;background:#fffbeb;border-color:#fde68a">
        <div class="dash-op-widget-icon warning"><i class="bi bi-tag-fill"></i></div>
        <div class="dash-op-widget-value warning"><?= $noCat ?></div>
        <div class="dash-op-widget-label">Товаров без<br>категории</div>
    </a>
    <?php endif; ?>
    <?php if ($badBrand > 0): ?>
    <a href="<?= Url::to(['/admin/catalog/product']) ?>"
       class="dash-op-widget" style="flex:1;min-width:220px;background:#fef2f2;border-color:#fecaca">
        <div class="dash-op-widget-icon danger"><i class="bi bi-exclamation-circle-fill"></i></div>
        <div class="dash-op-widget-value danger"><?= $badBrand ?></div>
        <div class="dash-op-widget-label">Бренд не совпадает<br>с началом названия</div>
    </a>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- X5: Image health check widget -->
<div class="dash-image-health" style="margin-bottom:16px;padding:12px 16px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;display:flex;align-items:center;gap:12px;flex-wrap:wrap">
    <i class="bi bi-image" style="font-size:1.25rem;color:#64748b"></i>
    <span style="flex:1;color:#475569;font-size:0.9rem">Проверка изображений брендов и категорий</span>
    <div id="imageHealthResult" style="font-size:0.85rem;color:#64748b"></div>
    <button class="admin-btn admin-btn-secondary admin-btn-sm" onclick="runImageHealthCheck(this)" id="imageHealthBtn">
        <i class="bi bi-search"></i> Проверить
    </button>
</div>
<script>
function runImageHealthCheck(btn) {
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-arrow-repeat spin"></i> Проверяем…';
    var result = document.getElementById('imageHealthResult');
    result.textContent = '';
    fetch('<?= \yii\helpers\Url::to(['/admin/dashboard/image-health']) ?>', {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(r){ return r.json(); })
    .then(function(data) {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-search"></i> Проверить';
        if (!data.success) { result.textContent = 'Ошибка'; return; }
        var b = data.broken_brands, c = data.broken_categories;
        if (b === 0 && c === 0) {
            result.innerHTML = '<span style="color:#16a34a"><i class="bi bi-check-circle-fill"></i> Все изображения на месте</span>';
        } else {
            var parts = [];
            if (b > 0) parts.push('<span style="color:#dc2626">Бренды: ' + b + ' битых</span>');
            if (c > 0) parts.push('<span style="color:#d97706">Категории: ' + c + ' битых</span>');
            result.innerHTML = parts.join(' &nbsp;|&nbsp; ');
            if (data.details) console.table && console.table(data.details.brands.concat(data.details.categories));
        }
    })
    .catch(function(){ btn.disabled = false; btn.innerHTML = '<i class="bi bi-search"></i> Проверить'; result.textContent = 'Ошибка соединения'; });
}
</script>

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

<!-- dashboard chart init and CNY rate updater moved to frontend/web/js/dashboard.js -->
