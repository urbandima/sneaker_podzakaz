<?php

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Аналитика и отчёты';
$dateFrom = $dateFrom ?? date('Y-m-d', strtotime('-7 days'));
$dateTo = $dateTo ?? date('Y-m-d');
?>

<div class="admin-header">
    <h1 class="admin-header-title"><?= Html::encode($this->title) ?></h1>
    <div style="display: flex; gap: 0.5rem;">
        <a href="<?= Url::to(['index', 'period' => 'today']) ?>" class="admin-btn admin-btn-secondary <?= $period === 'today' ? 'active' : '' ?>">Сегодня</a>
        <a href="<?= Url::to(['index', 'period' => 'week']) ?>" class="admin-btn admin-btn-secondary <?= $period === 'week' ? 'active' : '' ?>">Неделя</a>
        <a href="<?= Url::to(['index', 'period' => 'month']) ?>" class="admin-btn admin-btn-secondary <?= $period === 'month' ? 'active' : '' ?>">Месяц</a>
    </div>
</div>

<!-- Statistics Cards -->
<div class="admin-stats">
    <div class="admin-stat-card">
        <p class="admin-stat-number"><?= number_format($revenueStats['total_revenue'] ?? 0, 0, ',', ' ') ?></p>
        <p class="admin-stat-label">Общая выручка, BYN</p>
        <span class="admin-badge admin-badge-success">+12%</span>
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
                    <th style="text-align: right;">Заказы</th>
                    <th style="text-align: right;">Выручка</th>
                    <th style="text-align: right;">Средний чек</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($salesByDay as $day): ?>
                <tr>
                    <td><?= $day['date'] ?></td>
                    <td style="text-align: right; font-weight: 600;"><?= $day['orders_count'] ?></td>
                    <td style="text-align: right; font-weight: 600;"><?= number_format($day['revenue'], 0, ',', ' ') ?> BYN</td>
                    <td style="text-align: right;"><?= number_format($day['avg_order'], 0, ',', ' ') ?> BYN</td>
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
                    <th style="text-align: right;">Кол-во</th>
                    <th style="text-align: right;">Выручка</th>
                    <th style="text-align: right;">Заказов</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($topProducts as $product): ?>
                <tr>
                    <td><?= Html::encode($product['product_name']) ?></td>
                    <td style="text-align: right; font-weight: 600;"><?= $product['total_qty'] ?></td>
                    <td style="text-align: right; font-weight: 600;"><?= number_format($product['total_revenue'], 0, ',', ' ') ?> BYN</td>
                    <td style="text-align: right;"><?= $product['orders_count'] ?></td>
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
