<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\grid\GridView;

$this->title = 'Купон: ' . $model->code;
$this->params['breadcrumbs'][] = ['label' => 'Купоны', 'url' => ['index']];
$this->params['breadcrumbs'][] = $model->code;
?>

<div class="coupon-view">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?= Html::encode($this->title) ?></h1>
        <div>
            <?= Html::a(($model->is_active ?? true) ? 'Деактивировать' : 'Активировать', ['toggle', 'id' => $model->id], [
                'class' => 'btn btn-' . (($model->is_active ?? true) ? 'warning' : 'success'),
                'data-method' => 'post',
                'data-confirm' => 'Вы уверены?'
            ]) ?>
            <?= Html::a('Редактировать', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
            <?= Html::a('Удалить', ['delete', 'id' => $model->id], [
                'class' => 'btn btn-danger',
                'data-confirm' => 'Вы уверены, что хотите удалить этот купон?',
                'data-method' => 'post',
            ]) ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="mb-0">Информация о купоне</h5>
                </div>
                <div class="card-body">
                    <?= DetailView::widget([
                        'model' => $model,
                        'attributes' => [
                            'code',
                            // 'name', // Поле отсутствует в БД
                            // 'description:ntext', // Поле отсутствует в БД
                            [
                                'attribute' => 'type',
                                'value' => $model->getTypeName(),
                            ],
                            [
                                'label' => 'Скидка',
                                'value' => $model->getDiscountDescription(),
                            ],
                            'min_order_amount:currency',
                            'max_discount:currency',
                            [
                                'attribute' => 'is_active',
                                'format' => 'raw',
                                'value' => ($model->is_active ?? true) ? '<span class="badge bg-success">Активен</span>' : '<span class="badge bg-secondary">Неактивен</span>',
                            ],
                            // 'is_first_order:boolean', // Поле отсутствует в БД
                        ],
                    ]) ?>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="mb-0">Использование</h5>
                </div>
                <div class="card-body">
                    <?= DetailView::widget([
                        'model' => $model,
                        'attributes' => [
                            [
                                'attribute' => 'current_uses',
                                'label' => 'Использований',
                                'format' => 'raw',
                                'value' => function ($model) {
                                    $currentUses = $model->current_uses ?? 0;
                                    $maxUses = $model->max_uses ?? null;
                                    $text = $currentUses;
                                    if ($maxUses) {
                                        $percent = ($currentUses / $maxUses) * 100;
                                        $text .= ' из ' . $maxUses . ' (' . round($percent) . '%)';
                                        return $text . '<div class="progress mt-2"><div class="progress-bar" style="width: ' . $percent . '%"></div></div>';
                                    }
                                    return $text . ' (без ограничений)';
                                },
                            ],
                            // 'max_uses_per_user', // Поле отсутствует в БД
                            'valid_from:datetime',
                            'valid_until:datetime',
                            'created_at:datetime',
                            'updated_at:datetime',
                        ],
                    ]) ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">История использований</h5>
        </div>
        <div class="card-body">
            <?= GridView::widget([
                'dataProvider' => $usageProvider,
                'tableOptions' => ['class' => 'table table-striped'],
                'columns' => [
                    [
                        'attribute' => 'order_id',
                        'format' => 'raw',
                        'value' => function ($usage) {
                            return Html::a('Заказ #' . $usage->order_id, ['/admin/order/view', 'id' => $usage->order_id]);
                        },
                    ],
                    [
                        'attribute' => 'user_id',
                        'label' => 'Пользователь',
                        'value' => function ($usage) {
                            return $usage->user_id ? 'ID: ' . $usage->user_id : 'Гость';
                        },
                    ],
                    'discount_amount:currency',
                    'created_at:datetime',
                ],
            ]); ?>
        </div>
    </div>
</div>
