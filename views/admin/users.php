<?php

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */

use yii\helpers\Html;
use yii\grid\GridView;

$this->title = 'Управление пользователями';
?>

<div class="admin-users">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?= Html::encode($this->title) ?></h1>
    </div>

    <div class="card">
        <div class="card-body">
            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'tableOptions' => ['class' => 'table table-hover'],
                'columns' => [
                    'id',
                    [
                        'attribute' => 'username',
                        'label' => 'Имя пользователя',
                    ],
                    [
                        'attribute' => 'email',
                        'label' => 'Email',
                    ],
                    [
                        'attribute' => 'role',
                        'label' => 'Роль',
                        'format' => 'raw',
                        'value' => function($model) {
                            $badges = [
                                'admin' => 'danger',
                                'manager' => 'primary',
                                'logist' => 'success',
                            ];
                            $class = $badges[$model->role] ?? 'secondary';
                            return '<span class="badge bg-' . $class . '">' . $model->getRoleName() . '</span>';
                        },
                    ],
                    [
                        'attribute' => 'status',
                        'label' => 'Статус',
                        'format' => 'raw',
                        'value' => function($model) {
                            return $model->status == 10 
                                ? '<span class="badge bg-success">Активен</span>' 
                                : '<span class="badge bg-danger">Неактивен</span>';
                        },
                    ],
                    [
                        'attribute' => 'created_at',
                        'label' => 'Создан',
                        'format' => ['datetime', 'php:d.m.Y'],
                    ],
                ],
            ]); ?>
        </div>
    </div>

    <div class="alert alert-info mt-4">
        <h5><i class="bi bi-info-circle"></i> Информация о ролях:</h5>
        <ul class="mb-0">
            <li><strong>Администратор</strong> - полный доступ ко всем функциям системы, управление пользователями, распределение заказов логистам</li>
            <li><strong>Менеджер</strong> - создание заказов, просмотр всех заказов, изменение всех статусов</li>
            <li><strong>Логист</strong> - просмотр только назначенных заказов, изменение статусов доставки (Заказан товар, Заказ получен, Заказ выдан)</li>
        </ul>
    </div>
</div>
