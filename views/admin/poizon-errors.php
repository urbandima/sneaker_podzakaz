<?php

use yii\helpers\Html;
use yii\grid\GridView;

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = 'Логи ошибок импорта';
$this->params['breadcrumbs'][] = ['label' => 'Импорт', 'url' => ['/admin/poizon/index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="admin-import-errors">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-exclamation-triangle text-danger"></i> <?= Html::encode($this->title) ?></h1>
        <?= Html::a('<i class="bi bi-arrow-left"></i> Назад', ['/admin/poizon/index'], ['class' => 'btn btn-secondary']) ?>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'tableOptions' => ['class' => 'table table-sm table-hover mb-0'],
                'columns' => [
                    [
                        'attribute' => 'created_at',
                        'format' => 'datetime',
                        'headerOptions' => ['width' => '180'],
                    ],
                    [
                        'attribute' => 'batch_id',
                        'format' => 'raw',
                        'value' => function ($model) {
                            return Html::a('#' . $model->batch_id, ['/admin/poizon/view', 'id' => $model->batch_id]);
                        },
                        'headerOptions' => ['width' => '80'],
                    ],
                    [
                        'attribute' => 'sku',
                        'headerOptions' => ['width' => '150'],
                    ],
                    [
                        'attribute' => 'product_name',
                    ],
                    [
                        'attribute' => 'message',
                        'format' => 'ntext',
                    ],
                    [
                        'attribute' => 'error_details',
                        'format' => 'raw',
                        'value' => function ($model) {
                            if ($model->error_details) {
                                return Html::button('Детали', [
                                    'class' => 'btn btn-sm btn-outline-danger',
                                    'onclick' => 'alert(' . json_encode($model->error_details) . ')',
                                ]);
                            }
                            return '-';
                        },
                        'headerOptions' => ['width' => '100'],
                    ],
                ],
            ]); ?>
        </div>
    </div>

</div>
