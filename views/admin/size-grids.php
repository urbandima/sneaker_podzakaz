<?php

use yii\helpers\Html;
use yii\grid\GridView;

$this->title = 'Размерные сетки';
$this->params['breadcrumbs'][] = ['label' => 'Товары', 'url' => ['products']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="size-grids-index">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1><?= Html::encode($this->title) ?></h1>
            <p class="text-muted">Управление размерными сетками для быстрого добавления размеров к товарам</p>
        </div>
        <div>
            <?= Html::a('<i class="bi bi-plus-circle"></i> Создать сетку', ['create-size-grid'], [
                'class' => 'btn btn-success'
            ]) ?>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'tableOptions' => ['class' => 'table table-hover mb-0'],
                'columns' => [
                    [
                        'attribute' => 'id',
                        'headerOptions' => ['width' => '60'],
                    ],
                    [
                        'attribute' => 'name',
                        'label' => 'Название',
                        'format' => 'raw',
                        'value' => function($model) {
                            return Html::a(
                                Html::encode($model->name),
                                ['edit-size-grid', 'id' => $model->id],
                                ['class' => 'fw-bold text-decoration-none']
                            );
                        },
                    ],
                    [
                        'attribute' => 'brand_id',
                        'label' => 'Бренд',
                        'format' => 'raw',
                        'value' => function($model) {
                            if ($model->brand) {
                                return '<span class="badge bg-primary">' . Html::encode($model->brand->name) . '</span>';
                            }
                            return '<span class="badge bg-secondary">Универсальная</span>';
                        },
                    ],
                    [
                        'attribute' => 'gender',
                        'label' => 'Пол',
                        'format' => 'raw',
                        'value' => function($model) {
                            $colors = [
                                'male' => 'primary',
                                'female' => 'danger',
                                'unisex' => 'info',
                                'kids' => 'success',
                            ];
                            $color = $colors[$model->gender] ?? 'secondary';
                            return '<span class="badge bg-' . $color . '">' . $model->getGenderLabel() . '</span>';
                        },
                    ],
                    [
                        'label' => 'Размеров',
                        'format' => 'raw',
                        'value' => function($model) {
                            $count = count($model->items);
                            return '<span class="badge bg-dark">' . $count . '</span>';
                        },
                        'headerOptions' => ['width' => '100'],
                    ],
                    [
                        'attribute' => 'is_active',
                        'label' => 'Статус',
                        'format' => 'raw',
                        'value' => function($model) {
                            if ($model->is_active) {
                                return '<span class="badge bg-success">Активна</span>';
                            }
                            return '<span class="badge bg-secondary">Неактивна</span>';
                        },
                        'headerOptions' => ['width' => '100'],
                    ],
                    [
                        'attribute' => 'created_at',
                        'label' => 'Создана',
                        'format' => 'datetime',
                        'headerOptions' => ['width' => '180'],
                    ],
                    [
                        'class' => 'yii\grid\ActionColumn',
                        'header' => 'Действия',
                        'template' => '{edit} {delete}',
                        'buttons' => [
                            'edit' => function ($url, $model) {
                                return Html::a(
                                    '<i class="bi bi-pencil"></i>',
                                    ['edit-size-grid', 'id' => $model->id],
                                    ['class' => 'btn btn-sm btn-outline-primary', 'title' => 'Редактировать']
                                );
                            },
                            'delete' => function ($url, $model) {
                                return Html::a(
                                    '<i class="bi bi-trash"></i>',
                                    ['delete-size-grid', 'id' => $model->id],
                                    [
                                        'class' => 'btn btn-sm btn-outline-danger',
                                        'title' => 'Удалить',
                                        'data-method' => 'post',
                                        'data-confirm' => 'Удалить размерную сетку "' . $model->name . '"?',
                                    ]
                                );
                            },
                        ],
                        'headerOptions' => ['width' => '120'],
                    ],
                ],
            ]) ?>
        </div>
    </div>

    <div class="alert alert-info mt-4">
        <h5><i class="bi bi-info-circle"></i> Как это работает</h5>
        <p class="mb-0">
            Размерные сетки позволяют быстро добавлять стандартные наборы размеров к товарам. 
            При добавлении размера к товару вы увидите список доступных сеток для данного бренда и пола, 
            и сможете добавить все размеры одним кликом.
        </p>
    </div>
</div>

<style>
    .card {
        border: none;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
</style>
