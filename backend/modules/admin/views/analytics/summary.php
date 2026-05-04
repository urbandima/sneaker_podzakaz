<?php

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Сводная аналитика';
$views       = (int)($funnel['views'] ?? 0);
$addToCart   = (int)($funnel['add_to_cart'] ?? 0);
$funnelOrders = (int)($funnel['orders'] ?? 0);
?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;">
    <h2 style="margin:0;font-size:1.3rem;font-weight:700;">
        <i class="bi bi-diagram-3"></i> Сводная аналитика
    </h2>
    <a href="<?= Url::to(['/admin/analytics/index']) ?>"
       class="admin-btn admin-btn-sm admin-btn-ghost">← Аналитика</a>
</div>

<!-- End-to-end funnel -->
<div class="admin-card" style="margin-bottom:1.5rem;">
    <div class="admin-card-header">
        <h3 class="admin-card-title"><i class="bi bi-funnel"></i> Воронка: Instagram → Заказ</h3>
        <span style="font-size:0.8rem;color:var(--admin-text-secondary);"><?= Html::encode($thisMonthFrom) ?> — сегодня</span>
    </div>
    <div class="admin-card-body">
        <?php
        $steps = [
            ['label' => 'Лиды AmoCRM',    'value' => $amoLeads,    'icon' => 'bi-person-plus',   'color' => '#8b5cf6'],
            ['label' => 'Просмотры сайта', 'value' => $views,       'icon' => 'bi-eye',            'color' => '#3b82f6'],
            ['label' => 'В корзину',       'value' => $addToCart,   'icon' => 'bi-cart-plus',      'color' => '#f59e0b'],
            ['label' => 'Заказы',          'value' => $orderCount,  'icon' => 'bi-bag-check',      'color' => '#10b981'],
        ];
        $maxVal = max(array_column($steps, 'value')) ?: 1;
        ?>
        <div style="display:flex;gap:0;align-items:flex-end;justify-content:center;height:160px;margin-bottom:1rem;">
            <?php foreach ($steps as $step): ?>
            <?php $h = $maxVal > 0 ? max(10, round($step['value'] / $maxVal * 140)) : 10; ?>
            <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:0.25rem;">
                <span style="font-weight:700;font-size:0.95rem;color:<?= $step['color'] ?>;">
                    <?= number_format($step['value']) ?>
                </span>
                <div style="width:60%;background:<?= $step['color'] ?>;height:<?= $h ?>px;border-radius:4px 4px 0 0;opacity:0.85;"></div>
            </div>
            <?php endforeach; ?>
        </div>
        <div style="display:flex;gap:0;">
            <?php foreach ($steps as $step): ?>
            <div style="flex:1;text-align:center;">
                <i class="<?= $step['icon'] ?>" style="color:<?= $step['color'] ?>;"></i>
                <div style="font-size:0.75rem;color:var(--admin-text-secondary);margin-top:0.25rem;">
                    <?= Html::encode($step['label']) ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php if ($amoStatus === 'error'): ?>
        <p style="margin-top:0.75rem;font-size:0.8rem;color:var(--admin-warning);">
            <i class="bi bi-exclamation-triangle"></i>
            Данные AmoCRM недоступны — показано 0 лидов. Проверьте токен.
        </p>
        <?php endif; ?>
    </div>
</div>

<!-- Revenue + Margin KPI -->
<div class="admin-stats" style="margin-bottom:1.5rem;">
    <div class="admin-stat-card">
        <p class="admin-stat-number"><?= number_format($totalRevenue, 0, ',', ' ') ?></p>
        <p class="admin-stat-label">Выручка тек. месяц, BYN</p>
        <span class="admin-badge admin-badge-info">МойСклад</span>
    </div>
    <div class="admin-stat-card" style="border-left-color:var(--admin-success);">
        <p class="admin-stat-number"><?= number_format($marginEst, 0, ',', ' ') ?></p>
        <p class="admin-stat-label">Оценка маржи, BYN</p>
        <span class="admin-badge admin-badge-success">30% базовая</span>
    </div>
    <div class="admin-stat-card" style="border-left-color:var(--admin-warning);">
        <p class="admin-stat-number"><?= $repeatRate ?>%</p>
        <p class="admin-stat-label">Repeat rate</p>
        <span class="admin-badge admin-badge-warning"><?= $repeatCust ?> из <?= $totalCust ?> клиентов</span>
    </div>
    <div class="admin-stat-card" style="border-left-color:var(--admin-info);">
        <p class="admin-stat-number"><?= $orderCount ?></p>
        <p class="admin-stat-label">Заказов тек. месяц</p>
        <span class="admin-badge admin-badge-info">МойСклад БД</span>
    </div>
</div>

<!-- Маржа по месяцам -->
<div class="admin-card">
    <div class="admin-card-header">
        <h3 class="admin-card-title"><i class="bi bi-graph-up-arrow"></i> Выручка и маржа по месяцам</h3>
        <span style="font-size:0.8rem;color:var(--admin-text-secondary);">Последние 6 месяцев · маржа 30%</span>
    </div>
    <div class="admin-card-body" style="padding:0;overflow-x:auto;">
        <?php if (empty($marginByMonth)): ?>
            <p style="color:var(--admin-text-secondary);text-align:center;padding:2rem;">Нет данных</p>
        <?php else: ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Месяц</th>
                    <th style="text-align:right;">Выручка, BYN</th>
                    <th style="text-align:right;">Маржа (30%), BYN</th>
                    <th style="width:200px;">Визуализация</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $maxRev = max(array_column($marginByMonth, 'revenue')) ?: 1;
                ?>
                <?php foreach ($marginByMonth as $m): ?>
                <tr>
                    <td style="font-weight:600;"><?= Html::encode($m['month']) ?></td>
                    <td style="text-align:right;font-weight:700;">
                        <?= number_format($m['revenue'], 0, ',', ' ') ?>
                    </td>
                    <td style="text-align:right;font-weight:700;color:var(--admin-success);">
                        <?= number_format($m['margin'], 0, ',', ' ') ?>
                    </td>
                    <td>
                        <div style="display:flex;gap:2px;align-items:center;">
                            <div style="flex:<?= round($m['revenue'] / $maxRev * 100) ?>;background:#3b82f6;height:8px;border-radius:4px;opacity:0.7;"></div>
                            <div style="flex:<?= 100 - round($m['revenue'] / $maxRev * 100) ?>;"></div>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
