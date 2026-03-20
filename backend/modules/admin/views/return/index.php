<?php

use yii\helpers\Html;
use yii\grid\GridView;
use app\backend\modules\return\models\ReturnRequest;

$this->title = 'Возвраты';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="return-index">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?= Html::encode($this->title) ?></h1>
        <?= Html::a('Статистика', ['statistics'], ['class' => 'btn btn-info']) ?>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-6">
                    <input type="text" name="search" class="form-control" placeholder="Поиск по номеру или причине" value="<?= Html::encode(Yii::$app->request->get('search')) ?>">
                </div>
                <div class="col-md-4">
                    <select name="status" class="form-select">
                        <option value="">Все статусы</option>
                        <?php foreach (ReturnRequest::getStatusList() as $key => $label): ?>
                            <option value="<?= $key ?>" <?= Yii::$app->request->get('status') === $key ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Фильтр</button>
                </div>
            </form>
        </div>
    </div>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'tableOptions' => ['class' => 'table table-striped table-hover'],
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
                    return Html::a('Заказ #' . $model->order_id, ['/admin/order/view', 'id' => $model->order_id]);
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
