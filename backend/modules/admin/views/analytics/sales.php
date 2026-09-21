<?php

use yii\helpers\Html;
use app\backend\shared\helpers\PriceHelper;

/** @var string $period */
/** @var array $salesByDay */
/** @var array $topProducts */
/** @var array $topCategories */
/** @var float $avgOrderValue */

$this->title = 'Отчёт по продажам';

$this->params['headerActions'] = [
    Html::a('7 дней', ['sales', 'period' => '7'], ['class' => 'admin-btn admin-btn-secondary admin-btn-sm' . ($period == 7 ? ' active' : '')]),
    Html::a('30 дней', ['sales', 'period' => '30'], ['class' => 'admin-btn admin-btn-secondary admin-btn-sm' . ($period == 30 ? ' active' : '')]),
    Html::a('90 дней', ['sales', 'period' => '90'], ['class' => 'admin-btn admin-btn-secondary admin-btn-sm' . ($period == 90 ? ' active' : '')]),
    Html::a('Экспорт CSV', ['export', 'type' => 'sales', 'period' => $period], ['class' => 'admin-btn admin-btn-secondary admin-btn-sm']),
];

$days = $salesByDay;
$products = $topProducts;
$categories = $topCategories;
?>

<div class="admin-stats" style="margin-bottom:1.5rem">
    <div class="admin-stat-card">
        <div class="admin-stat-icon success"><i class="bi bi-cash-stack"></i></div>
        <div class="admin-stat-content">
            <div class="admin-stat-value"><?= PriceHelper::formatInt((float)$avgOrderValue) ?></div>
            <div class="admin-stat-label">Средний чек</div>
        </div>
    </div>
</div>

<div class="admin-card" style="margin-bottom:1.5rem">
    <div class="admin-card-header">
        <h2 class="admin-card-title"><i class="bi bi-calendar3"></i> Продажи по дням</h2>
        <span class="admin-badge admin-badge-info">За <?= Html::encode($period) ?> дней</span>
    </div>
    <div style="overflow-x:auto">
        <?php if (!empty($days)) : ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Дата</th>
                    <th style="text-align:right">Заказов</th>
                    <th style="text-align:right">Выручка</th>
                    <th style="text-align:right">Средний чек</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($days as $d) : ?>
                <tr>
                    <td><?= Html::encode($d['date']) ?></td>
                    <td style="text-align:right"><?= number_format((int)($d['orders_count'] ?? 0)) ?></td>
                    <td style="text-align:right;font-weight:600"><?= PriceHelper::formatInt((float)($d['revenue'] ?? 0)) ?></td>
                    <td style="text-align:right"><?= PriceHelper::formatInt((float)($d['avg_order'] ?? 0)) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else : ?>
        <div style="text-align:center;padding:2rem;color:var(--admin-text-secondary)">Нет заказов за выбранный период.</div>
        <?php endif; ?>
    </div>
</div>

<div class="admin-card" style="margin-bottom:1.5rem">
    <div class="admin-card-header">
        <h2 class="admin-card-title"><i class="bi bi-trophy"></i> Топ товары по продажам</h2>
    </div>
    <div style="overflow-x:auto">
        <?php if (!empty($products)) : ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Товар</th>
                    <th style="text-align:right">Кол-во</th>
                    <th style="text-align:right">Заказов</th>
                    <th style="text-align:right">Выручка</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $p) : ?>
                <tr>
                    <td><?= Html::encode($p['product_name'] ?? '—') ?></td>
                    <td style="text-align:right"><?= number_format((int)($p['total_qty'] ?? 0)) ?></td>
                    <td style="text-align:right"><?= number_format((int)($p['orders_count'] ?? 0)) ?></td>
                    <td style="text-align:right;font-weight:600"><?= PriceHelper::formatInt((float)($p['total_revenue'] ?? 0)) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else : ?>
        <div style="text-align:center;padding:2rem;color:var(--admin-text-secondary)">Нет данных за выбранный период.</div>
        <?php endif; ?>
    </div>
</div>

<div class="admin-card">
    <div class="admin-card-header">
        <h2 class="admin-card-title"><i class="bi bi-tags"></i> Топ категории</h2>
    </div>
    <div style="overflow-x:auto">
        <?php if (!empty($categories)) : ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Категория</th>
                    <th style="text-align:right">Заказов</th>
                    <th style="text-align:right">Кол-во</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $c) : ?>
                <tr>
                    <td><?= Html::encode($c['category_name'] ?? '—') ?></td>
                    <td style="text-align:right"><?= number_format((int)($c['orders_count'] ?? 0)) ?></td>
                    <td style="text-align:right"><?= number_format((int)($c['total_qty'] ?? 0)) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else : ?>
        <div style="text-align:center;padding:2rem;color:var(--admin-text-secondary)">Нет данных за выбранный период.</div>
        <?php endif; ?>
    </div>
</div>
