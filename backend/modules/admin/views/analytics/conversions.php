<?php

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Конверсионная аналитика';
$period = $period ?? '30';

$this->params['headerActions'] = [
    Html::a('7 дней',  ['conversions', 'period' => '7'],  ['class' => 'admin-btn admin-btn-secondary admin-btn-sm' . ($period == 7  ? ' active' : '')]),
    Html::a('30 дней', ['conversions', 'period' => '30'], ['class' => 'admin-btn admin-btn-secondary admin-btn-sm' . ($period == 30 ? ' active' : '')]),
    Html::a('90 дней', ['conversions', 'period' => '90'], ['class' => 'admin-btn admin-btn-secondary admin-btn-sm' . ($period == 90 ? ' active' : '')]),
];

$funnel = $funnel ?? ['views' => 0, 'add_to_cart' => 0, 'orders' => 0];
$fViews   = max(1, (int)($funnel['views'] ?? 0));
$fCarts   = (int)($funnel['add_to_cart'] ?? $funnel['carts'] ?? 0);
$fOrders  = (int)($funnel['orders'] ?? 0);
$cartPct  = round($fCarts / $fViews * 100, 1);
$orderPct = round($fOrders / $fViews * 100, 1);
$orderFromCartPct = $fCarts > 0 ? round($fOrders / $fCarts * 100, 1) : 0;
?>

<!-- KPI плашки -->
<div class="admin-stats" style="margin-bottom:1.5rem">
    <div class="admin-stat-card">
        <div class="admin-stat-icon info"><i class="bi bi-eye"></i></div>
        <div class="admin-stat-content">
            <div class="admin-stat-value"><?= number_format($fViews - 1) ?></div>
            <div class="admin-stat-label">Просмотры товаров</div>
        </div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-icon warning"><i class="bi bi-cart-plus"></i></div>
        <div class="admin-stat-content">
            <div class="admin-stat-value"><?= number_format($fCarts) ?></div>
            <div class="admin-stat-label">Добавили в корзину</div>
            <div class="dash-stat-sub"><span class="admin-badge admin-badge-warning"><?= $cartPct ?>%</span></div>
        </div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-icon success"><i class="bi bi-bag-check"></i></div>
        <div class="admin-stat-content">
            <div class="admin-stat-value"><?= number_format($fOrders) ?></div>
            <div class="admin-stat-label">Купили</div>
            <div class="dash-stat-sub"><span class="admin-badge admin-badge-success"><?= $orderPct ?>%</span></div>
        </div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-icon primary"><i class="bi bi-percent"></i></div>
        <div class="admin-stat-content">
            <div class="admin-stat-value"><?= $orderFromCartPct ?>%</div>
            <div class="admin-stat-label">Корзина → Заказ</div>
        </div>
    </div>
</div>

<!-- Воронка -->
<div class="admin-card" style="margin-bottom:1.5rem">
    <div class="admin-card-header">
        <h2 class="admin-card-title"><i class="bi bi-funnel-fill"></i> Воронка конверсии</h2>
        <span class="admin-badge admin-badge-info">За <?= Html::encode($period) ?> дней</span>
    </div>
    <div class="admin-card-body">
        <div style="display:flex;gap:0;align-items:flex-end;height:120px;overflow:hidden;border-radius:8px">
            <?php
            $steps = [
                ['label' => 'Просмотры', 'val' => $fViews - 1, 'pct' => 100, 'color' => '#3b82f6'],
                ['label' => 'В корзину',  'val' => $fCarts,    'pct' => $cartPct,  'color' => '#f59e0b'],
                ['label' => 'Заказы',    'val' => $fOrders,   'pct' => $orderPct, 'color' => '#10b981'],
            ];
            foreach ($steps as $step):
            ?>
            <div style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:flex-end;gap:4px">
                <div style="font-size:12px;font-weight:700"><?= number_format($step['val']) ?></div>
                <div style="width:80%;height:<?= max(8, $step['pct']) ?>px;background:<?= $step['color'] ?>;border-radius:4px 4px 0 0;transition:height .3s"></div>
                <div style="font-size:11px;color:var(--admin-text-secondary)"><?= Html::encode($step['label']) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Таблица товаров -->
<div class="admin-card">
    <div class="admin-card-header">
        <h2 class="admin-card-title"><i class="bi bi-table"></i> Конверсия по товарам</h2>
        <span class="admin-badge admin-badge-secondary"><?= count($products ?? []) ?> товаров</span>
    </div>
    <div style="overflow-x:auto">
        <?php if (!empty($products)): ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Товар</th>
                    <th style="text-align:right">Просмотры</th>
                    <th style="text-align:right">В корзину</th>
                    <th style="text-align:right">Заказы</th>
                    <th style="text-align:right">Конверсия</th>
                    <?php if (!empty($products[0]['total_revenue'])): ?>
                    <th style="text-align:right">Выручка</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $i => $p): ?>
                <?php
                $pViews  = (int)($p['views'] ?? 0);
                $pCarts  = (int)($p['add_to_cart'] ?? 0);
                $pOrders = (int)($p['orders'] ?? 0);
                $pCvr    = $pViews > 0 ? round($pOrders / $pViews * 100, 1) : 0;
                ?>
                <tr>
                    <td style="color:var(--admin-text-secondary)"><?= $i + 1 ?></td>
                    <td style="font-weight:500"><?= Html::encode($p['product_name'] ?? '—') ?></td>
                    <td style="text-align:right"><?= number_format($pViews) ?></td>
                    <td style="text-align:right">
                        <?= number_format($pCarts) ?>
                        <?php if ($pViews > 0): ?>
                        <small style="color:var(--admin-text-secondary)"><?= round($pCarts / $pViews * 100, 1) ?>%</small>
                        <?php endif; ?>
                    </td>
                    <td style="text-align:right;font-weight:600"><?= number_format($pOrders) ?></td>
                    <td style="text-align:right">
                        <?php
                        $cvrClass = $pCvr >= 5 ? 'admin-badge-success' : ($pCvr >= 2 ? 'admin-badge-warning' : 'admin-badge-danger');
                        ?>
                        <span class="admin-badge <?= $cvrClass ?>"><?= $pCvr ?>%</span>
                    </td>
                    <?php if (!empty($products[0]['total_revenue'])): ?>
                    <td style="text-align:right"><?= !empty($p['total_revenue']) ? number_format((float)$p['total_revenue'], 0, ',', ' ') . ' BYN' : '—' ?></td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div style="text-align:center;padding:3rem;color:var(--admin-text-secondary)">
            <i class="bi bi-bar-chart" style="font-size:2rem;display:block;margin-bottom:0.75rem"></i>
            Нет данных конверсии за выбранный период.<br>
            <small>Убедитесь, что события аналитики (просмотры, добавления в корзину) записываются в таблицу <code>analytics_event</code>.</small>
        </div>
        <?php endif; ?>
    </div>
</div>
