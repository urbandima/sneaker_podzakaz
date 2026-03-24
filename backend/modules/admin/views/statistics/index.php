<?php

/** @var yii\web\View $this */
/** @var array $statusStats */
/** @var array $managerStats */
/** @var array $logistStats */
/** @var int $totalOrders */
/** @var float $totalAmount */
/** @var int $pendingPayment */
/** @var int $completedOrders */

use yii\helpers\Html;

$this->title = 'Статистика';
?>

<div class="admin-header">
    <h1 class="admin-header-title"><?= Html::encode($this->title) ?></h1>
</div>

<!-- Общая статистика -->
<div class="admin-stats">
    <div class="admin-stat-card">
        <p class="admin-stat-number"><?= $totalOrders ?></p>
        <p class="admin-stat-label">Всего заказов</p>
    </div>
    <div class="admin-stat-card" style="border-left-color: var(--admin-success);">
        <p class="admin-stat-number"><?= Yii::$app->formatter->asDecimal($totalAmount, 0) ?> BYN</p>
        <p class="admin-stat-label">Общая сумма</p>
    </div>
    <div class="admin-stat-card" style="border-left-color: var(--admin-warning);">
        <p class="admin-stat-number"><?= $pendingPayment ?></p>
        <p class="admin-stat-label">Ожидают оплаты</p>
    </div>
    <div class="admin-stat-card" style="border-left-color: var(--admin-info);">
        <p class="admin-stat-number"><?= $completedOrders ?></p>
        <p class="admin-stat-label">Выполнено</p>
    </div>
</div>

<!-- Статистика по статусам -->
<div class="admin-card">
    <h2 class="admin-card-title">
        <i class="bi bi-pie-chart"></i>
        Распределение по статусам
    </h2>
    
    <div style="overflow-x: auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Статус</th>
                    <th style="text-align: right;">Количество</th>
                    <th>Процент</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($statusStats as $status => $count): ?>
                <tr>
                    <td><?= Html::encode($status) ?></td>
                    <td style="text-align: right; font-weight: 600;"><?= $count ?></td>
                    <td>
                        <?php $percentage = $totalOrders > 0 ? ($count / $totalOrders * 100) : 0; ?>
                        <div style="background: var(--admin-border); border-radius: 0.5rem; height: 1.5rem; overflow: hidden;">
                            <div style="background: var(--admin-primary); height: 100%; width: <?= $percentage ?>%; display: flex; align-items: center; justify-content: center; color: white; font-size: 0.75rem; font-weight: 600;">
                                <?php if ($percentage > 10): ?>
                                    <?= round($percentage, 1) ?>%
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Статистика по менеджерам -->
<div class="admin-card">
    <h2 class="admin-card-title">
        <i class="bi bi-people"></i>
        Статистика по менеджерам
    </h2>
    
    <div style="overflow-x: auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Менеджер</th>
                    <th>Email</th>
                    <th style="text-align: right;">Создано заказов</th>
                    <th style="text-align: right;">Общая сумма</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($managerStats as $manager): ?>
                <tr>
                    <td><?= Html::encode($manager->username) ?></td>
                    <td><?= Html::encode($manager->email) ?></td>
                    <td style="text-align: right;">
                        <span class="admin-badge admin-badge-primary"><?= count($manager->createdOrders) ?></span>
                    </td>
                    <td style="text-align: right; font-weight: 600;">
                        <?php
                        $sum = 0;
                        foreach ($manager->createdOrders as $order) {
                            $sum += $order->total_amount;
                        }
                        echo Yii::$app->formatter->asDecimal($sum, 0) . ' BYN';
                        ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Статистика по логистам -->
<div class="admin-card">
    <h2 class="admin-card-title">
        <i class="bi bi-truck"></i>
        Статистика по логистам
    </h2>
    
    <div style="overflow-x: auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Логист</th>
                    <th>Email</th>
                    <th style="text-align: right;">Назначено</th>
                    <th style="text-align: right;">Активных</th>
                    <th style="text-align: right;">Завершено</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logistStats as $logist): ?>
                <tr>
                    <td><?= Html::encode($logist->username) ?></td>
                    <td><?= Html::encode($logist->email) ?></td>
                    <td style="text-align: right;">
                        <span class="admin-badge admin-badge-primary"><?= count($logist->assignedOrders) ?></span>
                    </td>
                    <td style="text-align: right;">
                        <?php
                        $active = 0;
                        foreach ($logist->assignedOrders as $order) {
                            if ($order->status != 'issued') {
                                $active++;
                            }
                        }
                        ?>
                        <span class="admin-badge admin-badge-warning"><?= $active ?></span>
                    </td>
                    <td style="text-align: right;">
                        <?php
                        $completed = 0;
                        foreach ($logist->assignedOrders as $order) {
                            if ($order->status == 'issued') {
                                $completed++;
                            }
                        }
                        ?>
                        <span class="admin-badge admin-badge-success"><?= $completed ?></span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
