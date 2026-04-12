<?php

use yii\helpers\Html;

$this->title = 'Статистика купонов';
$this->params['breadcrumbs'][] = ['label' => 'Купоны', 'url' => ['/admin/coupon']];
$this->params['breadcrumbs'][] = $this->title;

$stats = [
    'total' => 25,
    'active' => 8,
    'used' => 127,
    'total_discount' => 1250.50,
];

$topCoupons = [
    ['code' => 'WELCOME10', 'uses' => 45, 'discount' => 350.00],
    ['code' => 'SPRING20', 'uses' => 32, 'discount' => 280.50],
    ['code' => 'NEWUSER15', 'uses' => 28, 'discount' => 245.00],
    ['code' => 'VIP25', 'uses' => 15, 'discount' => 180.00],
    ['code' => 'FRIEND5', 'uses' => 7, 'discount' => 95.00],
];

$statsByType = [
    ['type' => 'Процент', 'count' => 15, 'uses' => 85],
    ['type' => 'Фиксированная сумма', 'count' => 7, 'uses' => 32],
    ['type' => 'Бесплатная доставка', 'count' => 3, 'uses' => 10],
];
?>

<!-- Статистика -->
<div class="admin-stats" style="margin-bottom: 24px;">
    <div class="admin-stat-card">
        <div class="admin-stat-icon primary"><i class="bi bi-ticket-detailed-fill"></i></div>
        <div class="admin-stat-content">
            <div class="admin-stat-value"><?= $stats['total'] ?></div>
            <div class="admin-stat-label">Всего купонов</div>
        </div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-icon success"><i class="bi bi-check-circle-fill"></i></div>
        <div class="admin-stat-content">
            <div class="admin-stat-value"><?= $stats['active'] ?></div>
            <div class="admin-stat-label">Активных</div>
        </div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-icon warning"><i class="bi bi-receipt-fill"></i></div>
        <div class="admin-stat-content">
            <div class="admin-stat-value"><?= $stats['used'] ?></div>
            <div class="admin-stat-label">Использовано</div>
        </div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-icon info"><i class="bi bi-currency-dollar"></i></div>
        <div class="admin-stat-content">
            <div class="admin-stat-value"><?= number_format($stats['total_discount'], 2) ?> <small>BYN</small></div>
            <div class="admin-stat-label">Общая скидка</div>
        </div>
    </div>
</div>

<!-- Топ-10 купонов по использованию -->
<div class="admin-card" style="margin-bottom: 24px;">
    <div class="admin-card-header">
        <h2 class="admin-card-title">Топ-10 купонов по использованию</h2>
    </div>
    <div class="admin-card-body" style="padding: 0;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Код купона</th>
                    <th>Использований</th>
                    <th>Сумма скидки (BYN)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($topCoupons as $coupon): ?>
                <tr>
                    <td><?= Html::encode($coupon['code']) ?></td>
                    <td><?= $coupon['uses'] ?></td>
                    <td><?= number_format($coupon['discount'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Статистика по типам купонов -->
<div class="admin-card">
    <div class="admin-card-header">
        <h2 class="admin-card-title">Статистика по типам купонов</h2>
    </div>
    <div class="admin-card-body" style="padding: 0;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Тип</th>
                    <th>Количество</th>
                    <th>Использований</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($statsByType as $type): ?>
                <tr>
                    <td><?= Html::encode($type['type']) ?></td>
                    <td><?= $type['count'] ?></td>
                    <td><?= $type['uses'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
