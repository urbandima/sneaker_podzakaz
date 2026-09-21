<?php

use yii\helpers\Html;

/** @var string $period */
/** @var array $conversion */
/** @var array $conversionByDay */

$this->title = 'Конверсия по дням';

$this->params['headerActions'] = [
    Html::a('7 дней', ['conversion', 'period' => '7'], ['class' => 'admin-btn admin-btn-secondary admin-btn-sm' . ($period == 7 ? ' active' : '')]),
    Html::a('30 дней', ['conversion', 'period' => '30'], ['class' => 'admin-btn admin-btn-secondary admin-btn-sm' . ($period == 30 ? ' active' : '')]),
    Html::a('90 дней', ['conversion', 'period' => '90'], ['class' => 'admin-btn admin-btn-secondary admin-btn-sm' . ($period == 90 ? ' active' : '')]),
    Html::a('По товарам', ['conversions'], ['class' => 'admin-btn admin-btn-secondary admin-btn-sm']),
];

$rows = $conversionByDay;
?>

<div class="admin-card">
    <div class="admin-card-header">
        <h2 class="admin-card-title"><i class="bi bi-graph-up"></i> Конверсия по дням</h2>
        <span class="admin-badge admin-badge-info">За <?= Html::encode($period) ?> дней</span>
    </div>
    <div style="overflow-x:auto">
        <?php if (!empty($rows)) : ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Дата</th>
                    <th style="text-align:right">Просмотры страниц</th>
                    <th style="text-align:right">Просмотры товаров</th>
                    <th style="text-align:right">В корзину</th>
                    <th style="text-align:right">Заказы</th>
                    <th style="text-align:right">Конверсия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $r) :
                    $views = (int)($r['product_views'] ?? 0);
                    $orders = (int)($r['orders'] ?? 0);
                    $cvr = $views > 0 ? round($orders / $views * 100, 1) : 0;
                    ?>
                <tr>
                    <td><?= Html::encode($r['date']) ?></td>
                    <td style="text-align:right"><?= number_format((int)($r['page_views'] ?? 0)) ?></td>
                    <td style="text-align:right"><?= number_format($views) ?></td>
                    <td style="text-align:right"><?= number_format((int)($r['add_to_cart'] ?? 0)) ?></td>
                    <td style="text-align:right;font-weight:600"><?= number_format($orders) ?></td>
                    <td style="text-align:right"><span class="admin-badge admin-badge-secondary"><?= $cvr ?>%</span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else : ?>
        <div style="text-align:center;padding:3rem;color:var(--admin-text-secondary)">
            <i class="bi bi-bar-chart" style="font-size:2rem;display:block;margin-bottom:0.75rem"></i>
            Нет данных конверсии за выбранный период.<br>
            <small>Убедитесь, что события аналитики записываются в таблицу <code>analytics_event</code>.</small>
        </div>
        <?php endif; ?>
    </div>
</div>
