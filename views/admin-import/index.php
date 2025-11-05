<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\helpers\Url;
use app\models\ImportBatch;

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var array $stats */

$this->title = 'Импорт товаров Poizon';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="admin-import-index">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?= Html::encode($this->title) ?></h1>
        <?= Html::a('<i class="bi bi-play-circle"></i> Запустить импорт', ['run'], [
            'class' => 'btn btn-success btn-lg',
        ]) ?>
    </div>

    <!-- Статистика -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-primary">
                <div class="card-body text-center">
                    <i class="bi bi-box-seam fs-1 text-primary"></i>
                    <h3 class="mt-2"><?= number_format($stats['total_products_imported']) ?></h3>
                    <p class="text-muted mb-0">Всего импортировано</p>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-success">
                <div class="card-body text-center">
                    <i class="bi bi-check-circle fs-1 text-success"></i>
                    <h3 class="mt-2"><?= $stats['successful_batches'] ?> / <?= $stats['total_batches'] ?></h3>
                    <p class="text-muted mb-0">Успешных импортов</p>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-danger">
                <div class="card-body text-center">
                    <i class="bi bi-x-circle fs-1 text-danger"></i>
                    <h3 class="mt-2"><?= number_format($stats['total_errors']) ?></h3>
                    <p class="text-muted mb-0">Ошибок</p>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-info">
                <div class="card-body text-center">
                    <i class="bi bi-graph-up fs-1 text-info"></i>
                    <h3 class="mt-2"><?= $stats['success_rate'] ?>%</h3>
                    <p class="text-muted mb-0">Успешность</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Последний импорт -->
    <?php if ($stats['last_batch']): ?>
    <div class="card mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="bi bi-clock-history"></i> Последний импорт</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Статус:</strong> <span class="badge <?= $stats['last_batch']->getStatusBadgeClass() ?>"><?= $stats['last_batch']->getStatusLabel() ?></span></p>
                    <p><strong>Начало:</strong> <?= $stats['last_batch']->started_at ?></p>
                    <p><strong>Завершение:</strong> <?= $stats['last_batch']->finished_at ?: '-' ?></p>
                    <p><strong>Длительность:</strong> <?= $stats['last_batch']->getFormattedDuration() ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Создано:</strong> <?= $stats['last_batch']->created_count ?></p>
                    <p><strong>Обновлено:</strong> <?= $stats['last_batch']->updated_count ?></p>
                    <p><strong>Пропущено:</strong> <?= $stats['last_batch']->skipped_count ?></p>
                    <p><strong>Ошибок:</strong> <?= $stats['last_batch']->error_count ?></p>
                </div>
            </div>
            <?= Html::a('Подробнее', ['view', 'id' => $stats['last_batch']->id], ['class' => 'btn btn-sm btn-primary']) ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Список всех импортов -->
    <div class="card">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-list-ul"></i> История импортов</h5>
            <?= Html::a('<i class="bi bi-exclamation-triangle"></i> Ошибки', ['errors'], ['class' => 'btn btn-sm btn-warning']) ?>
        </div>
        <div class="card-body p-0">
            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'tableOptions' => ['class' => 'table table-hover mb-0'],
                'columns' => [
                    'id',
                    [
                        'attribute' => 'source',
                        'value' => function ($model) {
                            return $model->getSourceLabel();
                        },
                    ],
                    [
                        'attribute' => 'type',
                        'value' => function ($model) {
                            return $model->getTypeLabel();
                        },
                    ],
                    [
                        'attribute' => 'status',
                        'format' => 'raw',
                        'value' => function ($model) {
                            return Html::tag('span', $model->getStatusLabel(), [
                                'class' => 'badge ' . $model->getStatusBadgeClass(),
                            ]);
                        },
                    ],
                    [
                        'attribute' => 'started_at',
                        'format' => 'datetime',
                    ],
                    [
                        'attribute' => 'duration_seconds',
                        'label' => 'Длительность',
                        'value' => function ($model) {
                            return $model->getFormattedDuration();
                        },
                    ],
                    [
                        'label' => 'Результаты',
                        'format' => 'raw',
                        'value' => function ($model) {
                            return '<small>' .
                                   '<span class="text-success">✓ ' . $model->created_count . '</span> | ' .
                                   '<span class="text-info">↻ ' . $model->updated_count . '</span> | ' .
                                   '<span class="text-warning">⊘ ' . $model->skipped_count . '</span> | ' .
                                   '<span class="text-danger">✗ ' . $model->error_count . '</span>' .
                                   '</small>';
                        },
                    ],
                    [
                        'class' => 'yii\grid\ActionColumn',
                        'template' => '{view}',
                        'buttons' => [
                            'view' => function ($url, $model) {
                                return Html::a('<i class="bi bi-eye"></i>', ['view', 'id' => $model->id], [
                                    'class' => 'btn btn-sm btn-outline-primary',
                                    'title' => 'Подробнее',
                                ]);
                            },
                        ],
                    ],
                ],
            ]); ?>
        </div>
    </div>

</div>

<?php
// Auto-refresh если импорт в процессе
if ($stats['last_batch'] && $stats['last_batch']->status === ImportBatch::STATUS_PROCESSING) {
    $this->registerJs("
        setTimeout(function() {
            location.reload();
        }, 5000);
    ");
}
?>
