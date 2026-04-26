<?php

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = '📊 Аналитика и отчеты';
?>

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
            <div class="stat-icon"><i class="bi bi-box-seam"></i></div>
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
                    ['label' => 'Просмотры', 'value' => $conversion['page_views'], 'icon' => '👁️', 'url' => '/admin/analytics'],
                    ['label' => 'Товары', 'value' => $conversion['product_views'], 'icon' => '👟', 'url' => '/admin/product'],
                    ['label' => 'Корзина', 'value' => $conversion['add_to_cart'], 'icon' => '🛒', 'url' => '/admin/cart'],
                    ['label' => 'Заказы', 'value' => $conversion['orders'], 'icon' => '<i class="bi bi-box-seam"></i>', 'url' => '/admin/order'],
                ];
                foreach ($steps as $index => $step):
                    $height = max(40, ($step['value'] / $maxValue) * 200);
                    $nextStep = $steps[$index + 1] ?? null;
                    $conversionBetween = $nextStep && $step['value'] > 0 
                        ? round(($nextStep['value'] / $step['value']) * 100) 
                        : 0;
                ?>
                <div class="funnel-step">
                    <a href="<?= Url::to([$step['url']]) ?>" class="funnel-bar-link" style="text-decoration:none;width:100%;display:flex;flex-direction:column;align-items:center;">
                        <div class="funnel-bar" style="height: <?= $height ?>px;cursor:pointer;transition:transform 0.2s,box-shadow 0.2s;" onmouseover="this.style.transform='scale(1.02)';this.style.boxShadow='0 4px 12px rgba(59,130,246,0.4)';" onmouseout="this.style.transform='scale(1)';this.style.boxShadow='none';">
                            <?= $step['icon'] ?> <?= number_format($step['value']) ?>
                        </div>
                    </a>
                    <div class="funnel-label"><?= $step['label'] ?></div>
                    <div class="funnel-value"><?= $maxValue > 0 ? round(($step['value'] / $maxValue) * 100) : 0 ?>%</div>
                </div>
                <?php if ($nextStep): ?>
                <a href="<?= Url::to([$nextStep['url']]) ?>" class="funnel-arrow" style="text-decoration:none;display:flex;flex-direction:column;align-items:center;align-self:center;padding-bottom:40px;cursor:pointer;transition:transform 0.2s;" title="Конверсия: <?= $conversionBetween ?>%" onmouseover="this.style.transform='translateX(5px)';" onmouseout="this.style.transform='translateX(0)';">
                    <span style="font-size:24px;color:#6b7280;">→</span>
                    <span style="font-size:11px;color:#3b82f6;font-weight:600;background:#dbeafe;padding:2px 6px;border-radius:4px;white-space:nowrap;"><?= $conversionBetween ?>%</span>
                </a>
                <?php endif; ?>
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
