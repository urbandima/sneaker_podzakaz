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
        <?= Html::a('<i class="bi bi-person-plus me-2"></i>Создать пользователя', ['create-user'], ['class' => 'btn btn-success']) ?>
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
                    [
                        'class' => 'yii\grid\ActionColumn',
                        'header' => 'Действия',
                        'template' => '{delete}',
                        'buttons' => [
                            'delete' => function ($url, $model) {
                                if ($model->id === Yii::$app->user->id) {
                                    return '<span class="text-muted" title="Нельзя удалить себя">—</span>';
                                }
                                return Html::a(
                                    '<i class="bi bi-trash"></i>',
                                    ['delete-user', 'id' => $model->id],
                                    [
                                        'class' => 'btn btn-sm btn-outline-danger',
                                        'title' => 'Удалить',
                                        'data' => [
                                            'confirm' => 'Вы уверены, что хотите удалить пользователя ' . $model->username . '?',
                                            'method' => 'post',
                                        ],
                                    ]
                                );
                            },
                        ],
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
