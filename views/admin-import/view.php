<?php

use yii\helpers\Html;
use yii\grid\GridView;
use app\models\ImportLog;

/** @var yii\web\View $this */
/** @var app\models\ImportBatch $batch */
/** @var yii\data\ActiveDataProvider $logsProvider */

$this->title = 'Импорт #' . $batch->id;
$this->params['breadcrumbs'][] = ['label' => 'Импорт', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="admin-import-view">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?= Html::encode($this->title) ?></h1>
        <?= Html::a('<i class="bi bi-arrow-left"></i> Назад', ['index'], ['class' => 'btn btn-secondary']) ?>
    </div>

    <!-- Информация о батче -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="bi bi-info-circle"></i> Информация</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <th width="40%">Статус:</th>
                            <td>
                                <span class="badge <?= $batch->getStatusBadgeClass() ?>"><?= $batch->getStatusLabel() ?></span>
                            </td>
                        </tr>
                        <tr>
                            <th>Источник:</th>
                            <td><?= $batch->getSourceLabel() ?></td>
                        </tr>
                        <tr>
                            <th>Тип:</th>
                            <td><?= $batch->getTypeLabel() ?></td>
                        </tr>
                        <tr>
                            <th>Начало:</th>
                            <td><?= $batch->started_at ?></td>
                        </tr>
                        <tr>
                            <th>Завершение:</th>
                            <td><?= $batch->finished_at ?: '-' ?></td>
                        </tr>
                        <tr>
                            <th>Длительность:</th>
                            <td><?= $batch->getFormattedDuration() ?></td>
                        </tr>
                        <tr>
                            <th>Запустил:</th>
                            <td><?= $batch->creator ? $batch->creator->username : 'Cron' ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="bi bi-bar-chart"></i> Статистика</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6 mb-3">
                            <h3 class="text-success"><?= $batch->created_count ?></h3>
                            <small class="text-muted">Создано</small>
                        </div>
                        <div class="col-6 mb-3">
                            <h3 class="text-info"><?= $batch->updated_count ?></h3>
                            <small class="text-muted">Обновлено</small>
                        </div>
                        <div class="col-6">
                            <h3 class="text-warning"><?= $batch->skipped_count ?></h3>
                            <small class="text-muted">Пропущено</small>
                        </div>
                        <div class="col-6">
                            <h3 class="text-danger"><?= $batch->error_count ?></h3>
                            <small class="text-muted">Ошибок</small>
                        </div>
                    </div>
                    <hr>
                    <div class="text-center">
                        <h4><?= $batch->getSuccessRate() ?>%</h4>
                        <small class="text-muted">Успешность</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Конфигурация -->
    <?php if ($batch->config): ?>
    <div class="card mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="bi bi-gear"></i> Конфигурация</h5>
        </div>
        <div class="card-body">
            <pre class="mb-0"><?= json_encode($batch->getConfigArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?></pre>
        </div>
    </div>
    <?php endif; ?>

    <!-- Логи -->
    <div class="card">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="bi bi-file-text"></i> Логи импорта</h5>
        </div>
        <div class="card-body p-0">
            <?= GridView::widget([
                'dataProvider' => $logsProvider,
                'tableOptions' => ['class' => 'table table-sm table-hover mb-0'],
                'columns' => [
                    [
                        'attribute' => 'created_at',
                        'format' => 'datetime',
                        'headerOptions' => ['width' => '180'],
                    ],
                    [
                        'attribute' => 'action',
                        'format' => 'raw',
                        'value' => function ($model) {
                            return Html::tag('span', $model->getActionLabel(), [
                                'class' => 'badge ' . $model->getActionBadgeClass(),
                            ]);
                        },
                        'headerOptions' => ['width' => '100'],
                    ],
                    [
                        'attribute' => 'sku',
                        'headerOptions' => ['width' => '150'],
                    ],
                    [
                        'attribute' => 'product_name',
                        'format' => 'raw',
                        'value' => function ($model) {
                            if ($model->product_id) {
                                return Html::a($model->product_name, ['/catalog/product', 'id' => $model->product_id], [
                                    'target' => '_blank',
                                ]);
                            }
                            return $model->product_name;
                        },
                    ],
                    [
                        'attribute' => 'message',
                        'format' => 'ntext',
                    ],
                ],
            ]); ?>
        </div>
    </div>

</div>

<?php
// Auto-refresh если импорт в процессе
if ($batch->status === \app\models\ImportBatch::STATUS_PROCESSING) {
    $this->registerJs("
        setTimeout(function() {
            location.reload();
        }, 3000);
    ");
}
?>
