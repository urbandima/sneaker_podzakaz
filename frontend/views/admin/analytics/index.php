<?php

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = '📊 Аналитика и отчеты';
?>

<style>
.analytics-page {
    padding: 1.5rem;
    background: #f8fafc;
    min-height: 100vh;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}

.page-title {
    font-size: 1.875rem;
    font-weight: 800;
    color: #1f2937;
}

.period-selector {
    display: flex;
    gap: 0.5rem;
}

.period-btn {
    padding: 0.5rem 1rem;
    border: 1px solid #e5e7eb;
    background: white;
    color: #374151;
    border-radius: 8px;
    text-decoration: none;
    font-size: 0.875rem;
    transition: all 0.2s;
}

.period-btn:hover,
.period-btn.active {
    background: #3b82f6;
    border-color: #3b82f6;
    color: white;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 1.5rem;
}

.stat-icon {
    font-size: 2rem;
    margin-bottom: 0.5rem;
}

.stat-value {
    font-size: 2rem;
    font-weight: 800;
    color: #1f2937;
    margin-bottom: 0.25rem;
}

.stat-label {
    font-size: 0.875rem;
    color: #6b7280;
}

.stat-change {
    font-size: 0.75rem;
    margin-top: 0.5rem;
}

.stat-change.positive {
    color: #10b981;
}

.stat-change.negative {
    color: #ef4444;
}

.section {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #f3f4f6;
}

.section-title {
    font-size: 1.125rem;
    font-weight: 700;
    color: #1f2937;
}

.chart-container {
    height: 300px;
    position: relative;
}

.conversion-funnel {
    display: flex;
    align-items: end;
    justify-content: space-around;
    height: 250px;
    padding: 1rem;
}

.funnel-step {
    display: flex;
    flex-direction: column;
    align-items: center;
    flex: 1;
}

.funnel-bar {
    width: 80%;
    background: linear-gradient(180deg, #3b82f6, #1d4ed8);
    border-radius: 8px 8px 0 0;
    display: flex;
    align-items: end;
    justify-content: center;
    color: white;
    font-weight: 700;
    padding-bottom: 1rem;
    min-height: 40px;
}

.funnel-label {
    margin-top: 0.75rem;
    font-size: 0.875rem;
    color: #374151;
    text-align: center;
}

.funnel-value {
    font-size: 0.75rem;
    color: #6b7280;
}

.table-section {
    overflow-x: auto;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table th,
.data-table td {
    padding: 0.75rem 1rem;
    text-align: left;
    border-bottom: 1px solid #f3f4f6;
}

.data-table th {
    background: #f8fafc;
    font-weight: 600;
    color: #374151;
    font-size: 0.75rem;
    text-transform: uppercase;
}

.data-table tr:hover {
    background: #f8fafc;
}

.progress-bar {
    height: 8px;
    background: #e5e7eb;
    border-radius: 4px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: #3b82f6;
    border-radius: 4px;
}

.device-stats {
    display: flex;
    gap: 2rem;
    justify-content: center;
    padding: 2rem;
}

.device-item {
    text-align: center;
}

.device-icon {
    font-size: 2.5rem;
    margin-bottom: 0.5rem;
}

.device-percent {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1f2937;
}

.device-label {
    font-size: 0.875rem;
    color: #6b7280;
}

.export-btn {
    padding: 0.5rem 1rem;
    background: #10b981;
    color: white;
    border: none;
    border-radius: 8px;
    text-decoration: none;
    font-size: 0.875rem;
    font-weight: 500;
}

.export-btn:hover {
    background: #059669;
    color: white;
}

.grid-2 {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.5rem;
}

@media (max-width: 768px) {
    .grid-2 {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="analytics-page">
    <div class="page-header">
        <h1 class="page-title"><?= Html::encode($this->title) ?></h1>
        <div class="period-selector">
            <a href="<?= Url::to(['index', 'period' => '7']) ?>" class="period-btn <?= $period == '7' ? 'active' : '' ?>">7 дней</a>
            <a href="<?= Url::to(['index', 'period' => '30']) ?>" class="period-btn <?= $period == '30' ? 'active' : '' ?>">30 дней</a>
            <a href="<?= Url::to(['index', 'period' => '90']) ?>" class="period-btn <?= $period == '90' ? 'active' : '' ?>">90 дней</a>
            <a href="<?= Url::to(['index', 'period' => '365']) ?>" class="period-btn <?= $period == '365' ? 'active' : '' ?>">Год</a>
        </div>
    </div>

    <!-- Основные метрики -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">👁️</div>
            <div class="stat-value"><?= number_format($conversion['page_views']) ?></div>
            <div class="stat-label">Просмотров страниц</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">👟</div>
            <div class="stat-value"><?= number_format($conversion['product_views']) ?></div>
            <div class="stat-label">Просмотров товаров</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">🛒</div>
            <div class="stat-value"><?= number_format($conversion['add_to_cart']) ?></div>
            <div class="stat-label">Добавлений в корзину</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">📦</div>
            <div class="stat-value"><?= number_format($conversion['orders']) ?></div>
            <div class="stat-label">Заказов</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">📈</div>
            <div class="stat-value"><?= $conversion['conversion_rate'] ?>%</div>
            <div class="stat-label">Конверсия</div>
            <div class="stat-change positive">Из посетителей в покупателей</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">💰</div>
            <div class="stat-value"><?= number_format($revenueStats['total_revenue'] ?? 0, 0, '.', ' ') ?></div>
            <div class="stat-label">Выручка (BYN)</div>
        </div>
    </div>

    <div class="grid-2">
        <!-- Воронка конверсии -->
        <div class="section">
            <div class="section-header">
                <h3 class="section-title">🔻 Воронка конверсии</h3>
            </div>
            <div class="conversion-funnel">
                <?php 
                $maxValue = max($conversion['page_views'], 1);
                $steps = [
                    ['label' => 'Просмотры', 'value' => $conversion['page_views'], 'icon' => '👁️'],
                    ['label' => 'Товары', 'value' => $conversion['product_views'], 'icon' => '👟'],
                    ['label' => 'Корзина', 'value' => $conversion['add_to_cart'], 'icon' => '🛒'],
                    ['label' => 'Заказы', 'value' => $conversion['orders'], 'icon' => '📦'],
                ];
                foreach ($steps as $step):
                    $height = max(40, ($step['value'] / $maxValue) * 200);
                ?>
                <div class="funnel-step">
                    <div class="funnel-bar" style="height: <?= $height ?>px;">
                        <?= $step['icon'] ?> <?= number_format($step['value']) ?>
                    </div>
                    <div class="funnel-label"><?= $step['label'] ?></div>
                    <div class="funnel-value"><?= $maxValue > 0 ? round(($step['value'] / $maxValue) * 100) : 0 ?>%</div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Устройства -->
        <div class="section">
            <div class="section-header">
                <h3 class="section-title">📱 Устройства</h3>
            </div>
            <div class="device-stats">
                <?php 
                $totalDevices = array_sum(array_column($deviceStats, 'count')) ?: 1;
                $deviceIcons = ['desktop' => '🖥️', 'mobile' => '📱', 'tablet' => '📟'];
                foreach ($deviceStats as $device): 
                    $percent = round(($device['count'] / $totalDevices) * 100);
                ?>
                <div class="device-item">
                    <div class="device-icon"><?= $deviceIcons[$device['device_type']] ?? '❓' ?></div>
                    <div class="device-percent"><?= $percent ?>%</div>
                    <div class="device-label"><?= ucfirst($device['device_type']) ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Источники трафика -->
    <div class="section">
        <div class="section-header">
            <h3 class="section-title">🔗 Источники трафика</h3>
            <a href="<?= Url::to(['export', 'type' => 'traffic', 'period' => $period]) ?>" class="export-btn">
                📥 Экспорт
            </a>
        </div>
        <div class="table-section">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Источник</th>
                        <th>Сессии</th>
                        <th>Просмотры</th>
                        <th>Доля</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $totalSessions = array_sum(array_column($trafficSources, 'sessions')) ?: 1;
                    foreach ($trafficSources as $source): 
                        $percent = ($source['sessions'] / $totalSessions) * 100;
                    ?>
                    <tr>
                        <td><strong><?= Html::encode($source['utm_source'] ?: 'Direct') ?></strong></td>
                        <td><?= number_format($source['sessions']) ?></td>
                        <td><?= number_format($source['count']) ?></td>
                        <td>
                            <div class="progress-bar" style="width: 100px;">
                                <div class="progress-fill" style="width: <?= $percent ?>%;"></div>
                            </div>
                            <span style="font-size: 0.75rem; color: #6b7280;"><?= round($percent, 1) ?>%</span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Заказы по дням -->
    <div class="section">
        <div class="section-header">
            <h3 class="section-title">📈 Заказы и выручка по дням</h3>
            <a href="<?= Url::to(['export', 'type' => 'sales', 'period' => $period]) ?>" class="export-btn">
                📥 Экспорт
            </a>
        </div>
        <div class="table-section">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Дата</th>
                        <th>Заказов</th>
                        <th>Выручка (BYN)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_reverse($ordersByDay) as $day): ?>
                    <tr>
                        <td><?= Yii::$app->formatter->asDate($day['date']) ?></td>
                        <td><strong><?= $day['count'] ?></strong></td>
                        <td><?= number_format($day['revenue'] ?? 0, 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Популярные товары -->
    <div class="section">
        <div class="section-header">
            <h3 class="section-title">🔥 Популярные товары (по просмотрам)</h3>
        </div>
        <div class="table-section">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>ID товара</th>
                        <th>Просмотров</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($popularProducts as $i => $product): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td>
                            <a href="<?= Url::to(['/admin/product/view', 'id' => $product['entity_id']]) ?>">
                                #<?= $product['entity_id'] ?>
                            </a>
                        </td>
                        <td><strong><?= number_format($product['views']) ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
