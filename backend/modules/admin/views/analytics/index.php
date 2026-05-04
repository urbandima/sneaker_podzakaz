<?php

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Аналитика и отчёты';
$dateFrom = $dateFrom ?? date('Y-m-d', strtotime('-7 days'));
$dateTo = $dateTo ?? date('Y-m-d');
$rfmSegments = $rfmSegments ?? [];
$teamStats = $teamStats ?? [];
$conversionFunnel = $conversionFunnel ?? ['views' => 0, 'add_to_cart' => 0, 'orders' => 0];

$activeTab = Yii::$app->request->get('tab', 'analytics');
$prevRevenueStats = $prevRevenueStats ?? [];
// Z80: $period is the raw string ('today','week','month','30', etc.) for active button comparison
$activePeriod = $period ?? Yii::$app->request->get('period', 'month');

// Вычислить % изменение выручки
$curRevenue  = (float)($revenueStats['total_revenue'] ?? 0);
$prevRevenue = (float)($prevRevenueStats['total_revenue'] ?? 0);
if ($prevRevenue > 0) {
    $revChangePct = round(($curRevenue - $prevRevenue) / $prevRevenue * 100, 1);
    $revChangeBadge = ($revChangePct >= 0 ? '+' : '') . $revChangePct . '%';
    $revChangeCls   = $revChangePct >= 0 ? 'admin-badge-success' : 'admin-badge-danger';
} else {
    $revChangeBadge = 'Нет данных';
    $revChangeCls   = 'admin-badge-secondary';
}
?>

<?php
// Z80: active date range button uses admin-btn-primary when selected
$this->params['headerActions'] = [
    Html::a('Сегодня', ['index', 'period' => 'today', 'tab' => $activeTab], ['class' => 'admin-btn admin-btn-sm ' . ($activePeriod === 'today' ? 'admin-btn-primary' : 'admin-btn-ghost')]),
    Html::a('Неделя',  ['index', 'period' => 'week',  'tab' => $activeTab], ['class' => 'admin-btn admin-btn-sm ' . ($activePeriod === 'week'  ? 'admin-btn-primary' : 'admin-btn-ghost')]),
    Html::a('Месяц',   ['index', 'period' => 'month', 'tab' => $activeTab], ['class' => 'admin-btn admin-btn-sm ' . ($activePeriod === 'month' ? 'admin-btn-primary' : 'admin-btn-ghost')]),
];
?>

<!-- Tab Navigation -->
<div style="display:flex;gap:0.25rem;margin-bottom:1.5rem;border-bottom:2px solid var(--admin-border,#e2e8f0);padding-bottom:0;">
    <a href="?tab=analytics&period=<?= Html::encode($activePeriod ?? 'month') ?>"
       style="padding:0.6rem 1.2rem;border-radius:0.5rem 0.5rem 0 0;font-weight:600;font-size:0.9rem;text-decoration:none;border:2px solid transparent;border-bottom:none;<?= $activeTab === 'analytics' ? 'background:var(--admin-primary,#2563eb);color:#fff;border-color:var(--admin-primary,#2563eb);' : 'color:var(--admin-text-secondary,#64748b);' ?>">
        <i class="bi bi-graph-up"></i> Аналитика
    </a>
    <a href="?tab=rfm&period=<?= Html::encode($activePeriod ?? 'month') ?>"
       style="padding:0.6rem 1.2rem;border-radius:0.5rem 0.5rem 0 0;font-weight:600;font-size:0.9rem;text-decoration:none;border:2px solid transparent;border-bottom:none;<?= $activeTab === 'rfm' ? 'background:var(--admin-primary,#2563eb);color:#fff;border-color:var(--admin-primary,#2563eb);' : 'color:var(--admin-text-secondary,#64748b);' ?>">
        <i class="bi bi-people"></i> RFM
    </a>
    <a href="?tab=team&period=<?= Html::encode($activePeriod ?? 'month') ?>"
       style="padding:0.6rem 1.2rem;border-radius:0.5rem 0.5rem 0 0;font-weight:600;font-size:0.9rem;text-decoration:none;border:2px solid transparent;border-bottom:none;<?= $activeTab === 'team' ? 'background:var(--admin-primary,#2563eb);color:#fff;border-color:var(--admin-primary,#2563eb);' : 'color:var(--admin-text-secondary,#64748b);' ?>">
        <i class="bi bi-person-badge"></i> Команда
    </a>
    <div style="flex:1;"></div>
    <a href="<?= Url::to(['/admin/analytics/chats']) ?>"
       style="padding:0.6rem 1.2rem;border-radius:0.5rem 0.5rem 0 0;font-weight:600;font-size:0.9rem;text-decoration:none;border:2px solid transparent;border-bottom:none;color:var(--admin-text-secondary,#64748b);">
        <i class="bi bi-chat-dots"></i> Чаты
    </a>
    <a href="<?= Url::to(['/admin/analytics/amocrm']) ?>"
       style="padding:0.6rem 1.2rem;border-radius:0.5rem 0.5rem 0 0;font-weight:600;font-size:0.9rem;text-decoration:none;border:2px solid transparent;border-bottom:none;color:var(--admin-text-secondary,#64748b);">
        <i class="bi bi-funnel"></i> AmoCRM
    </a>
    <a href="<?= Url::to(['/admin/analytics/moysklad']) ?>"
       style="padding:0.6rem 1.2rem;border-radius:0.5rem 0.5rem 0 0;font-weight:600;font-size:0.9rem;text-decoration:none;border:2px solid transparent;border-bottom:none;color:var(--admin-text-secondary,#64748b);">
        <i class="bi bi-box-seam"></i> МойСклад
    </a>
    <a href="<?= Url::to(['/admin/analytics/summary']) ?>"
       style="padding:0.6rem 1.2rem;border-radius:0.5rem 0.5rem 0 0;font-weight:600;font-size:0.9rem;text-decoration:none;border:2px solid transparent;border-bottom:none;color:var(--admin-text-secondary,#64748b);">
        <i class="bi bi-diagram-3"></i> Сводная
    </a>
</div>

<!-- ===================== TAB: АНАЛИТИКА ===================== -->
<div id="tab-analytics" style="<?= $activeTab !== 'analytics' ? 'display:none;' : '' ?>">

<!-- Statistics Cards -->
<div class="admin-stats">
    <div class="admin-stat-card">
        <p class="admin-stat-number"><?= number_format($revenueStats['total_revenue'] ?? 0, 0, ',', ' ') ?></p>
        <p class="admin-stat-label">Общая выручка, BYN</p>
        <span class="admin-badge <?= $revChangeCls ?>"><?= Html::encode($revChangeBadge) ?> vs пред. период</span>
    </div>
    <div class="admin-stat-card" style="border-left-color: var(--admin-success);">
        <p class="admin-stat-number"><?= $revenueStats['total_orders'] ?? 0 ?></p>
        <p class="admin-stat-label">Всего заказов</p>
        <span class="admin-badge admin-badge-info">За период</span>
    </div>
    <div class="admin-stat-card" style="border-left-color: var(--admin-warning);">
        <p class="admin-stat-number"><?= number_format($revenueStats['avg_order_value'] ?? 0, 0, ',', ' ') ?></p>
        <p class="admin-stat-label">Средний чек, BYN</p>
        <span class="admin-badge admin-badge-warning">AVG</span>
    </div>
    <div class="admin-stat-card" style="border-left-color: var(--admin-info);">
        <p class="admin-stat-number"><?= count($topProducts ?? []) ?></p>
        <p class="admin-stat-label">Топ товаров</p>
        <span class="admin-badge admin-badge-primary">Бестселлеры</span>
    </div>
</div>

<!-- Sales Chart -->
<div class="admin-card">
    <h2 class="admin-card-title">
        <i class="bi bi-graph-up"></i>
        Продажи по дням
    </h2>
    
    <div style="margin-top: 1.5rem; overflow-x: auto;">
        <?php if (!empty($salesByDay)): ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Дата</th>
                    <th class="text-right">Заказы</th>
                    <th class="text-right">Выручка</th>
                    <th class="text-right">Средний чек</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($salesByDay as $day): ?>
                <tr>
                    <td><?= $day['date'] ?? '—' ?></td>
                    <td style="text-align: right; font-weight: 600;"><?= $day['orders_count'] ?? $day['count'] ?? 0 ?></td>
                    <td style="text-align: right; font-weight: 600;"><?= number_format($day['revenue'] ?? 0, 0, ',', ' ') ?> BYN</td>
                    <td style="text-align: right;"><?= number_format($day['avg_order'] ?? $day['avg_order_value'] ?? 0, 0, ',', ' ') ?> BYN</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p style="text-align: center; color: var(--admin-text-secondary); padding: 2rem;">Нет данных за выбранный период</p>
        <?php endif; ?>
    </div>
</div>

<!-- Top Products -->
<div class="admin-card">
    <h2 class="admin-card-title">
        <i class="bi bi-trophy"></i>
        Топ продаваемых товаров
    </h2>
    
    <div style="margin-top: 1.5rem; overflow-x: auto;">
        <?php if (!empty($topProducts)): ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Товар</th>
                    <th class="text-right">Просмотры</th>
                    <th class="text-right">Выручка</th>
                    <th class="text-right">Заказов</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($topProducts as $product): ?>
                <tr>
                    <td><?= Html::encode($product['product_name']) ?></td>
                    <td class="text-right fw-600"><?= $product['views'] ?? 0 ?></td>
                    <td class="text-right fw-600"><?= number_format($product['total_revenue'] ?? 0, 0, ',', ' ') ?> BYN</td>
                    <td class="text-right"><?= $product['orders'] ?? 0 ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p style="text-align: center; color: var(--admin-text-secondary); padding: 2rem;">Нет данных о продажах</p>
        <?php endif; ?>
    </div>
</div>

<!-- Device Stats -->
<?php if (!empty($deviceStats)): ?>
<div class="admin-card">
    <h2 class="admin-card-title">
        <i class="bi bi-phone"></i>
        Статистика по устройствам
    </h2>

    <div style="margin-top: 1.5rem; display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem;">
        <?php foreach ($deviceStats as $device): ?>
        <div style="text-align: center; padding: 1rem; background: var(--admin-primary-soft); border-radius: 0.5rem;">
            <i class="bi bi-<?= $device['device_type'] === 'mobile' ? 'phone' : ($device['device_type'] === 'tablet' ? 'tablet' : 'laptop') ?>" style="font-size: 2rem; color: var(--admin-primary);"></i>
            <p style="margin: 0.5rem 0 0; font-weight: 600;"><?= ucfirst($device['device_type']) ?></p>
            <p style="margin: 0; color: var(--admin-text-secondary);"><?= $device['count'] ?> визитов</p>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Conversion Funnel -->
<div class="admin-card">
    <h2 class="admin-card-title">
        <i class="bi bi-filter-circle"></i>
        Воронка конверсии
    </h2>
    <div style="margin-top:1.5rem;display:flex;gap:1rem;align-items:flex-end;flex-wrap:wrap;">
        <?php
        $funnelSteps = [
            ['label' => 'Просмотры', 'value' => (int)($conversionFunnel['views'] ?? 0), 'color' => '#3b82f6'],
            ['label' => 'В корзину',  'value' => (int)($conversionFunnel['add_to_cart'] ?? 0), 'color' => '#f59e0b'],
            ['label' => 'Заказы',    'value' => (int)($conversionFunnel['orders'] ?? 0), 'color' => '#10b981'],
        ];
        $maxVal = max(array_column($funnelSteps, 'value')) ?: 1;
        foreach ($funnelSteps as $step):
            $pct = round($step['value'] / $maxVal * 100);
        ?>
        <div style="flex:1;min-width:120px;text-align:center;">
            <div style="height:<?= max($pct, 5) ?>px;background:<?= $step['color'] ?>;border-radius:0.5rem 0.5rem 0 0;transition:height 0.3s;"></div>
            <div style="padding:0.5rem 0;font-weight:700;font-size:1.1rem;"><?= number_format($step['value']) ?></div>
            <div style="font-size:0.8rem;color:var(--admin-text-secondary);"><?= Html::encode($step['label']) ?></div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

</div><!-- /tab-analytics -->

<!-- ===================== TAB: RFM ===================== -->
<div id="tab-rfm" style="<?= $activeTab !== 'rfm' ? 'display:none;' : '' ?>">

<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
        <h2 class="admin-card-title m-0">
            <i class="bi bi-people"></i>
            RFM-анализ клиентов
        </h2>
        <button class="admin-btn admin-btn-secondary" onclick="refreshRfm(this)"
            data-rfm-url="<?= Url::to(['/admin/analytics/rfm']) ?>"
            data-export-rfm-url="<?= Url::to(['/admin/analytics/export-rfm']) ?>">
            <i class="bi bi-arrow-clockwise"></i> Рассчитать RFM
        </button>
    </div>
    <p style="font-size:0.85rem;color:var(--admin-text-secondary);margin-bottom:1.25rem;">
        Сегментация: <strong>Champion</strong> (R&lt;30д, F&gt;3, M&gt;5000),
        <strong>Loyal</strong> (F&gt;3), <strong>At Risk</strong> (R&gt;60д, F&gt;1),
        <strong>Lost</strong> (R&gt;90д, F=1), <strong>New</strong> (остальные)
    </p>

    <div id="rfm-table-wrap" class="overflow-x-auto">
    <table class="admin-table" id="rfm-table">
        <thead>
            <tr>
                <th>Сегмент</th>
                <th class="text-right">Клиентов</th>
                <th class="text-right">Средний LTV, BYN</th>
                <th class="text-center">Экспорт</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $segmentColors = [
                'Champion' => '#10b981',
                'Loyal'    => '#3b82f6',
                'At Risk'  => '#f59e0b',
                'Lost'     => '#ef4444',
                'New'      => '#8b5cf6',
            ];
            foreach ($rfmSegments as $seg):
                $color = $segmentColors[$seg['segment']] ?? '#64748b';
            ?>
            <tr>
                <td>
                    <span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:<?= $color ?>;margin-right:0.4rem;"></span>
                    <strong><?= Html::encode($seg['segment']) ?></strong>
                </td>
                <td style="text-align:right;font-weight:700;"><?= (int)$seg['count'] ?></td>
                <td class="text-right"><?= number_format((float)$seg['avg_monetary'], 2, ',', ' ') ?></td>
                <td class="text-center">
                    <a href="<?= Url::to(['/admin/analytics/export-rfm', 'segment' => $seg['segment']]) ?>"
                       class="admin-btn admin-btn-secondary" style="font-size:0.75rem;padding:0.3rem 0.7rem;">
                        <i class="bi bi-download"></i> CSV
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <div id="rfm-msg" style="display:none;margin-top:0.75rem;padding:0.5rem 0.75rem;border-radius:0.5rem;font-size:0.875rem;"></div>
</div>

</div><!-- /tab-rfm -->

<!-- ===================== TAB: КОМАНДА ===================== -->
<div id="tab-team" style="<?= $activeTab !== 'team' ? 'display:none;' : '' ?>">

<div class="admin-card">
    <h2 class="admin-card-title">
        <i class="bi bi-person-badge"></i>
        Отчёт по команде
    </h2>
    <div style="margin-top:1.5rem;overflow-x:auto;">
        <?php if (!empty($teamStats)): ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Менеджер / Логист</th>
                    <th class="text-right">Заказов</th>
                    <th class="text-right">Выручка, BYN</th>
                    <th class="text-right">Средний чек, BYN</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($teamStats as $row): ?>
                <tr>
                    <td><?= Html::encode($row['manager'] ?? 'Не назначен') ?></td>
                    <td style="text-align:right;font-weight:700;"><?= (int)$row['order_count'] ?></td>
                    <td class="text-right"><?= number_format((float)$row['revenue'], 0, ',', ' ') ?></td>
                    <td class="text-right"><?= number_format((float)$row['avg_check'], 0, ',', ' ') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p style="text-align:center;color:var(--admin-text-secondary);padding:2rem;">
            Нет данных по команде (проверьте наличие полей assigned_logist / created_by в таблице order)
        </p>
        <?php endif; ?>
    </div>
</div>

</div><!-- /tab-team -->
