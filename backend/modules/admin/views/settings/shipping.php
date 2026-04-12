<?php

use yii\helpers\Html;
use yii\grid\GridView;

/** @var yii\web\View $this */
/** @var array $methods */

$this->title = 'Настройки доставки — СНИКЕРХЭД';
?>

<div class="panel">
    <div class="panel__header">
        <h2 class="panel__title">Настройки служб доставки</h2>
        <p class="panel__hint">Управление методами доставки и тарифами</p>
    </div>

    <div class="admin-table-wrapper">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Название</th>
                    <th>Тип</th>
                    <th>Перевозчик</th>
                    <th>Срок доставки</th>
                    <th>Базовая стоимость</th>
                    <th>Статус</th>
                    <th>Описание</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($methods as $method): ?>
                <tr>
                    <td><?= Html::encode($method['name']) ?></td>
                    <td>
                        <span class="admin-badge admin-badge-<?= $method['type'] === 'international' ? 'primary' : 'secondary' ?>">
                            <?= Html::encode($method['type_label']) ?>
                        </span>
                    </td>
                    <td><?= Html::encode($method['carrier']) ?></td>
                    <td><?= Html::encode($method['delivery_time']) ?></td>
                    <td><?= number_format($method['base_cost'], 2) ?> <?= Html::encode($method['currency']) ?></td>
                    <td>
                        <span class="admin-badge admin-badge-<?= $method['status'] === 'active' ? 'success' : 'danger' ?>">
                            <?= $method['status'] === 'active' ? 'Активен' : 'Отключён' ?>
                        </span>
                    </td>
                    <td><?= Html::encode($method['description']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
