<?php

use yii\helpers\Html;

$this->title = 'Статистика купонов';
$this->params['breadcrumbs'][] = ['label' => 'Купоны', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="coupon-statistics">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?= Html::encode($this->title) ?></h1>
        <?= Html::a('Назад к списку', ['index'], ['class' => 'btn btn-secondary']) ?>
    </div>

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="text-primary"><?= $stats['total'] ?></h3>
                    <p class="mb-0">Всего купонов</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="text-success"><?= $stats['active'] ?></h3>
                    <p class="mb-0">Активных</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="text-info"><?= $stats['totalUsages'] ?></h3>
                    <p class="mb-0">Использований</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="text-danger"><?= Yii::$app->formatter->asCurrency($stats['totalDiscount']) ?></h3>
                    <p class="mb-0">Общая скидка</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="mb-0">Топ-10 купонов по использованию</h5>
                </div>
                <div class="card-body">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Код</th>
                                <th>Название</th>
                                <th>Использований</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($topCoupons as $coupon): ?>
                                <tr>
                                    <td><?= Html::a($coupon->code, ['view', 'id' => $coupon->id], ['class' => 'fw-bold']) ?></td>
                                    <td><?= Html::encode($coupon->name) ?></td>
                                    <td><span class="badge bg-primary"><?= $coupon->current_uses ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($topCoupons)): ?>
                                <tr>
                                    <td colspan="3" class="text-center text-muted">Нет использованных купонов</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="mb-0">Статистика по типам</h5>
                </div>
                <div class="card-body">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Тип</th>
                                <th>Купонов</th>
                                <th>Использований</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($typeStats as $stat): ?>
                                <tr>
                                    <td><?= \app\backend\modules\coupon\models\Coupon::getTypeList()[$stat['type']] ?? $stat['type'] ?></td>
                                    <td><span class="badge bg-info"><?= $stat['count'] ?></span></td>
                                    <td><span class="badge bg-success"><?= $stat['uses'] ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($typeStats)): ?>
                                <tr>
                                    <td colspan="3" class="text-center text-muted">Нет данных</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
