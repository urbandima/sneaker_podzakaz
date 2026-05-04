<?php

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'МойСклад — Аналитика';

$thisTotal  = (float)($thisRevenue['total_revenue'] ?? 0);
$prevTotal  = (float)($prevRevenue['total_revenue'] ?? 0);
$changePct  = $prevTotal > 0 ? round(($thisTotal - $prevTotal) / $prevTotal * 100, 1) : null;
$avgCheck   = (float)($thisRevenue['avg_order_value'] ?? 0);
$orderCount = (int)($thisRevenue['total_orders'] ?? 0);
?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;">
    <h2 style="margin:0;font-size:1.3rem;font-weight:700;">
        <i class="bi bi-box-seam"></i> МойСклад — Аналитика
    </h2>
    <div style="display:flex;gap:0.5rem;">
        <a href="<?= Url::to(['/admin/analytics/export-orders']) ?>"
           class="admin-btn admin-btn-sm admin-btn-ghost">
            <i class="bi bi-download"></i> Заказы CSV
        </a>
        <a href="<?= Url::to(['/admin/analytics/export-customers']) ?>"
           class="admin-btn admin-btn-sm admin-btn-ghost">
            <i class="bi bi-people"></i> Клиенты CSV
        </a>
        <a href="<?= Url::to(['/admin/analytics/export-products']) ?>"
           class="admin-btn admin-btn-sm admin-btn-ghost">
            <i class="bi bi-box"></i> Товары CSV
        </a>
        <a href="<?= Url::to(['/admin/analytics/index']) ?>"
           class="admin-btn admin-btn-sm admin-btn-ghost">← Аналитика</a>
    </div>
</div>

<!-- KPI cards -->
<div class="admin-stats" style="margin-bottom:1.5rem;">
    <div class="admin-stat-card">
        <p class="admin-stat-number"><?= number_format($thisTotal, 0, ',', ' ') ?></p>
        <p class="admin-stat-label">Выручка тек. месяц, BYN</p>
        <?php if ($changePct !== null): ?>
        <span class="admin-badge <?= $changePct >= 0 ? 'admin-badge-success' : 'admin-badge-danger' ?>">
            <?= ($changePct >= 0 ? '+' : '') . $changePct ?>% vs пред. месяц
        </span>
        <?php else: ?>
        <span class="admin-badge admin-badge-secondary">Нет данных прошлого периода</span>
        <?php endif; ?>
    </div>
    <div class="admin-stat-card" style="border-left-color:var(--admin-success);">
        <p class="admin-stat-number"><?= $orderCount ?></p>
        <p class="admin-stat-label">Заказов в этом месяце</p>
        <span class="admin-badge admin-badge-info">Из локальной БД</span>
    </div>
    <div class="admin-stat-card" style="border-left-color:var(--admin-warning);">
        <p class="admin-stat-number"><?= number_format($avgCheck, 0, ',', ' ') ?></p>
        <p class="admin-stat-label">Средний чек, BYN</p>
        <span class="admin-badge admin-badge-warning">AVG</span>
    </div>
    <div class="admin-stat-card" style="border-left-color:var(--admin-info);">
        <p class="admin-stat-number"><?= number_format($prevTotal, 0, ',', ' ') ?></p>
        <p class="admin-stat-label">Выручка пред. месяц, BYN</p>
        <span class="admin-badge admin-badge-secondary">Сравнение</span>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:1.5rem;">

<!-- Топ-10 товаров -->
<div class="admin-card">
    <div class="admin-card-header">
        <h3 class="admin-card-title"><i class="bi bi-trophy"></i> Топ-10 товаров (текущий месяц)</h3>
        <span style="font-size:0.8rem;color:var(--admin-text-secondary);"><?= Html::encode($thisMonthFrom) ?> — сегодня</span>
    </div>
    <div class="admin-card-body" style="padding:0;">
        <?php if (empty($topProducts)): ?>
            <p style="color:var(--admin-text-secondary);text-align:center;padding:2rem;">Нет данных</p>
        <?php else: ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Товар</th>
                    <th style="text-align:right;">Заказов</th>
                    <th style="text-align:right;">Выручка</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($topProducts as $i => $p): ?>
                <tr>
                    <td style="color:var(--admin-text-secondary);width:2rem;"><?= $i + 1 ?></td>
                    <td style="font-size:0.85rem;max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                        <?= Html::encode($p['product_name']) ?>
                    </td>
                    <td style="text-align:right;font-weight:600;"><?= $p['orders_count'] ?></td>
                    <td style="text-align:right;font-weight:600;">
                        <?= number_format((float)$p['total_revenue'], 0, ',', ' ') ?> BYN
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<!-- Заказы по статусам -->
<div class="admin-card">
    <div class="admin-card-header">
        <h3 class="admin-card-title"><i class="bi bi-pie-chart"></i> Заказы по статусам</h3>
        <span style="font-size:0.8rem;color:var(--admin-text-secondary);">Все время</span>
    </div>
    <div class="admin-card-body">
        <?php if (empty($ordersByStatus)): ?>
            <p style="color:var(--admin-text-secondary);text-align:center;padding:2rem;">Нет данных</p>
        <?php else: ?>
            <?php
            $total = array_sum(array_column($ordersByStatus, 'cnt')) ?: 1;
            $colors = ['#3b82f6','#10b981','#f59e0b','#8b5cf6','#ef4444','#06b6d4','#ec4899','#14b8a6','#f97316','#6b7280'];
            $statusLabels = [
                'new' => 'Новый','paid' => 'Оплачен','confirmed_and_paid' => 'Подтверждён',
                'ordered' => 'У поставщика','at_warehouse' => 'На складе',
                'local_delivery' => 'Доставка','delivered' => 'Выдан',
                'canceled' => 'Отменён','international_delivery' => 'Межд. доставка',
            ];
            ?>
            <?php foreach ($ordersByStatus as $ci => $s): ?>
            <?php $label = $statusLabels[$s['status']] ?? $s['status']; ?>
            <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.5rem;">
                <div style="width:10px;height:10px;border-radius:50%;background:<?= $colors[$ci % count($colors)] ?>;flex-shrink:0;"></div>
                <span style="flex:1;font-size:0.85rem;"><?= Html::encode($label) ?></span>
                <span style="font-weight:700;font-size:0.9rem;color:<?= $colors[$ci % count($colors)] ?>;"><?= $s['cnt'] ?></span>
                <span style="color:var(--admin-text-secondary);font-size:0.8rem;min-width:3rem;text-align:right;">
                    <?= round($s['cnt'] / $total * 100, 1) ?>%
                </span>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

</div><!-- /top grid -->

<!-- Последние 20 заказов -->
<div class="admin-card" style="margin-bottom:1.5rem;">
    <div class="admin-card-header">
        <h3 class="admin-card-title"><i class="bi bi-list-ul"></i> Последние 20 заказов</h3>
        <a href="<?= Url::to(['/admin/analytics/export-orders']) ?>"
           class="admin-btn admin-btn-sm admin-btn-ghost">
            <i class="bi bi-download"></i> Экспорт CSV
        </a>
    </div>
    <div class="admin-card-body" style="padding:0;overflow-x:auto;">
        <?php if (empty($recentOrders)): ?>
            <p style="color:var(--admin-text-secondary);text-align:center;padding:2rem;">Нет заказов</p>
        <?php else: ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Номер</th>
                    <th>Клиент</th>
                    <th>Статус</th>
                    <th style="text-align:right;">Сумма</th>
                    <th>Создан</th>
                    <th>МойСклад</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentOrders as $ord): ?>
                <tr>
                    <td>
                        <a href="<?= Url::to(['/admin/order/view', 'id' => $ord['id']]) ?>"
                           style="font-weight:600;">
                            <?= Html::encode($ord['order_number'] ?: '#' . $ord['id']) ?>
                        </a>
                    </td>
                    <td style="font-size:0.85rem;"><?= Html::encode($ord['client_name'] ?? '—') ?></td>
                    <td>
                        <span class="admin-badge admin-badge-info" style="font-size:0.75rem;">
                            <?= Html::encode($ord['status']) ?>
                        </span>
                    </td>
                    <td style="text-align:right;font-weight:600;">
                        <?= number_format((float)($ord['total_amount'] ?? 0), 0, ',', ' ') ?> BYN
                    </td>
                    <td style="font-size:0.8rem;color:var(--admin-text-secondary);">
                        <?= Html::encode(substr($ord['created_at'] ?? '', 0, 10)) ?>
                    </td>
                    <td style="font-size:0.75rem;color:var(--admin-text-secondary);">
                        <?= $ord['moysklad_id'] ? '<i class="bi bi-check-circle-fill" style="color:var(--admin-success);"></i>' : '<i class="bi bi-dash-circle" style="color:var(--admin-text-secondary);"></i>' ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<!-- Дебиторская задолженность -->
<?php if (!empty($debtData)): ?>
<div class="admin-card">
    <div class="admin-card-header">
        <h3 class="admin-card-title">
            <i class="bi bi-exclamation-circle" style="color:var(--admin-warning);"></i>
            Дебиторская задолженность
        </h3>
        <span style="font-weight:700;color:var(--admin-warning);">
            Итого: <?= number_format($msDebtTotal, 0, ',', ' ') ?> руб.
        </span>
    </div>
    <div class="admin-card-body" style="padding:0;overflow-x:auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Счёт</th>
                    <th>Контрагент</th>
                    <th style="text-align:right;">Сумма</th>
                    <th style="text-align:right;">Оплачено</th>
                    <th style="text-align:right;">Долг</th>
                    <th>Дата</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($debtData as $d): ?>
                <tr>
                    <td><?= Html::encode($d['name']) ?></td>
                    <td><?= Html::encode($d['agent']) ?></td>
                    <td style="text-align:right;"><?= number_format($d['sum'], 0, ',', ' ') ?></td>
                    <td style="text-align:right;"><?= number_format($d['paid'], 0, ',', ' ') ?></td>
                    <td style="text-align:right;font-weight:700;color:var(--admin-warning);">
                        <?= number_format($d['debt'], 0, ',', ' ') ?>
                    </td>
                    <td style="font-size:0.8rem;"><?= Html::encode(substr($d['moment'] ?? '', 0, 10)) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
