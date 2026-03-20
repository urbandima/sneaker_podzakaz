<?php

use yii\helpers\Html;
use yii\grid\GridView;
use app\backend\modules\return\models\ReturnRequest;

$this->title = 'Мои возвраты';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="return-index">
    <h1><?= Html::encode($this->title) ?></h1>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'tableOptions' => ['class' => 'table table-hover'],
        'columns' => [
            [
                'attribute' => 'return_number',
                'format' => 'raw',
                'value' => function ($model) {
                    return Html::a($model->return_number, ['view', 'id' => $model->id], ['class' => 'fw-bold']);
                },
            ],
            [
                'attribute' => 'order_id',
                'format' => 'raw',
                'value' => function ($model) {
                    return Html::a('Заказ #' . $model->order_id, ['/account/orders/view', 'id' => $model->order_id]);
                },
            ],
            [
                'attribute' => 'reason',
                'value' => function ($model) {
                    return $model->getReasonName();
                },
            ],
            'refund_amount:currency',
            [
                'attribute' => 'status',
                'format' => 'raw',
                'value' => function ($model) {
                    return '<span class="badge bg-' . $model->getStatusClass() . '">' . $model->getStatusName() . '</span>';
                },
            ],
            'created_at:datetime',
            [
                'class' => 'yii\grid\ActionColumn',
                'template' => '{view}',
                'buttons' => [
                    'view' => function ($url, $model) {
                        return Html::a('<i class="bi bi-eye"></i>', $url, ['class' => 'btn btn-sm btn-info', 'title' => 'Просмотр']);
                    },
                ],
            ],
        ],
    ]); ?>
</div>
