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

<div class="admin-statistics">
    <h1 class="mb-4"><?= Html::encode($this->title) ?></h1>

    <!-- Общая статистика -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-white bg-primary">
                <div class="card-body text-center">
                    <h3 class="mb-0"><?= $totalOrders ?></h3>
                    <p class="mb-0">Всего заказов</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-success">
                <div class="card-body text-center">
                    <h3 class="mb-0"><?= Yii::$app->formatter->asDecimal($totalAmount, 2) ?> BYN</h3>
                    <p class="mb-0">Общая сумма</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-warning">
                <div class="card-body text-center">
                    <h3 class="mb-0"><?= $pendingPayment ?></h3>
                    <p class="mb-0">Ожидают оплаты</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-info">
                <div class="card-body text-center">
                    <h3 class="mb-0"><?= $completedOrders ?></h3>
                    <p class="mb-0">Выполнено</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Статистика по статусам -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Распределение по статусам</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Статус</th>
                            <th class="text-end">Количество</th>
                            <th>Процент</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($statusStats as $status => $count): ?>
                        <tr>
                            <td><?= Html::encode($status) ?></td>
                            <td class="text-end"><strong><?= $count ?></strong></td>
                            <td>
                                <?php $percentage = $totalOrders > 0 ? ($count / $totalOrders * 100) : 0; ?>
                                <div class="progress" style="height: 20px;">
                                    <div class="progress-bar" role="progressbar" style="width: <?= $percentage ?>%">
                                        <?= round($percentage, 1) ?>%
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Статистика по менеджерам -->
    <div class="card mb-4">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0">Статистика по менеджерам</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Менеджер</th>
                            <th>Email</th>
                            <th class="text-end">Создано заказов</th>
                            <th class="text-end">Общая сумма</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($managerStats as $manager): ?>
                        <tr>
                            <td><?= Html::encode($manager->username) ?></td>
                            <td><?= Html::encode($manager->email) ?></td>
                            <td class="text-end">
                                <span class="badge bg-primary"><?= count($manager->createdOrders) ?></span>
                            </td>
                            <td class="text-end">
                                <?php
                                $sum = 0;
                                foreach ($manager->createdOrders as $order) {
                                    $sum += $order->total_amount;
                                }
                                echo Yii::$app->formatter->asDecimal($sum, 2) . ' BYN';
                                ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Статистика по логистам -->
    <div class="card">
        <div class="card-header bg-warning">
            <h5 class="mb-0">Статистика по логистам</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Логист</th>
                            <th>Email</th>
                            <th class="text-end">Назначено заказов</th>
                            <th class="text-end">Активных</th>
                            <th class="text-end">Завершено</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logistStats as $logist): ?>
                        <tr>
                            <td><?= Html::encode($logist->username) ?></td>
                            <td><?= Html::encode($logist->email) ?></td>
                            <td class="text-end">
                                <span class="badge bg-primary"><?= count($logist->assignedOrders) ?></span>
                            </td>
                            <td class="text-end">
                                <?php
                                $active = 0;
                                foreach ($logist->assignedOrders as $order) {
                                    if ($order->status != 'issued') {
                                        $active++;
                                    }
                                }
                                echo '<span class="badge bg-warning">' . $active . '</span>';
                                ?>
                            </td>
                            <td class="text-end">
                                <?php
                                $completed = 0;
                                foreach ($logist->assignedOrders as $order) {
                                    if ($order->status == 'issued') {
                                        $completed++;
                                    }
                                }
                                echo '<span class="badge bg-success">' . $completed . '</span>';
                                ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
