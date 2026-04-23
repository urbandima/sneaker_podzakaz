<?php

use yii\helpers\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = 'Заказы в пути';
$this->params['breadcrumbs'][] = ['label' => 'Доставка', 'url' => ['/admin/shipping']];

$this->params['headerActions'] = [
    Html::a('<i class="bi bi-plus-circle"></i> Новый заказ', ['/admin/order/create'], ['class' => 'admin-btn admin-btn-primary admin-btn-sm']),
];

$inTransitCount = $dataProvider->getCount();
?>

<!-- Статистика -->
<div class="admin-stats" style="margin-bottom: 24px;">
    <div class="admin-stat-card">
        <div class="admin-stat-icon primary"><i class="bi bi-truck-fill"></i></div>
        <div class="admin-stat-content">
            <div class="admin-stat-value"><?= number_format($inTransitCount) ?></div>
            <div class="admin-stat-label">Всего в пути</div>
        </div>
    </div>
</div>

<!-- Таблица заказов -->
<div class="admin-card">
    <div class="admin-card-header">
        <h2 class="admin-card-title">Заказы в пути</h2>
        <p class="admin-card-subtitle">Заказы со статусами "В обработке" и "Отправлен"</p>
    </div>
    <div class="admin-card-body" style="padding: 0;">
        <?= \yii\grid\GridView::widget([
            'dataProvider' => $dataProvider,
            'tableOptions' => ['class' => 'admin-table'],
            'headerRowOptions' => ['class' => 'admin-table-header'],
            'rowOptions' => ['class' => 'admin-table-row'],
            'columns' => [
                [
                    'attribute' => 'order_number',
                    'label' => '№ Заказа',
                    'format' => 'raw',
                    'value' => function ($model) {
                        return Html::a(
                            '#' . Html::encode($model->order_number),
                            ['/admin/order/view', 'id' => $model->id],
                            ['class' => 'admin-link']
                        );
                    },
                ],
                [
                    'attribute' => 'client_name',
                    'label' => 'Клиент',
                ],
                [
                    'attribute' => 'status',
                    'label' => 'Статус',
                    'format' => 'raw',
                    'value' => function ($model) {
                        $statusLabels = [
                            'processing' => 'В обработке',
                            'shipped' => 'Отправлен',
                        ];
                        $statusColors = [
                            'processing' => 'warning',
                            'shipped' => 'success',
                        ];
                        $label = $statusLabels[$model->status] ?? $model->status;
                        $color = $statusColors[$model->status] ?? 'secondary';
                        return '<span class="admin-badge admin-badge-' . $color . '">' . Html::encode($label) . '</span>';
                    },
                ],
                [
                    'attribute' => 'delivery_method',
                    'label' => 'Способ доставки',
                ],
                [
                    'attribute' => 'total_amount',
                    'label' => 'Сумма',
                    'format' => 'raw',
                    'value' => function ($model) {
                        return number_format((float)$model->total_amount, 2) . ' BYN';
                    },
                ],
                [
                    'attribute' => 'created_at',
                    'label' => 'Создан',
                    'format' => 'datetime',
                    'value' => function ($model) {
                        return date('d.m.Y H:i', $model->created_at);
                    },
                ],
                [
                    'class' => 'yii\grid\ActionColumn',
                    'template' => '{view}',
                    'buttons' => [
                        'view' => function ($url, $model, $key) {
                            return Html::a(
                                '<i class="bi bi-eye"></i>',
                                ['/admin/order/view', 'id' => $model->id],
                                ['class' => 'admin-btn admin-btn-sm admin-btn-secondary', 'title' => 'Просмотр']
                            );
                        },
                    ],
                ],
            ],
        ]); ?>
    </div>
</div>
