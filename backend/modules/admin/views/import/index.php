<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;

/** @var yii\web\View $this */
/** @var app\backend\modules\admin\models\import\ImportSource[] $sources */
/** @var app\backend\modules\admin\models\import\ImportTask[] $recentTasks */
/** @var app\backend\modules\admin\models\import\ImportTask[] $runningTasks */
/** @var array $stats */
/** @var array $rates */

$this->title = 'Импорт товаров';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="import-index">
    <!-- Hero секция -->
    <div class="hero-section mb-4">
        <div class="card border-0 bg-gradient text-white">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-lg-8">
                        <h1 class="display-5 fw-bold mb-3">
                            <i class="fas fa-download me-3"></i>Система импорта товаров
                        </h1>
                        <p class="lead mb-4">
                            Автоматический и ручной импорт из Lamoda, Dewu, Zalando, StockX. 
                            Полный контроль над процессом, статистика и логирование.
                        </p>
                        <div class="d-flex gap-3 flex-wrap">
                            <?= Html::a('<i class="fas fa-file-upload me-2"></i>Ручной импорт', ['upload'], [
                                'class' => 'btn btn-light btn-lg'
                            ]) ?>
                            <?= Html::a('<i class="fas fa-cog me-2"></i>Настройки', ['settings'], [
                                'class' => 'btn btn-outline-light btn-lg'
                            ]) ?>
                        </div>
                    </div>
                    <div class="col-lg-4 text-center">
                        <div class="hero-icon">
                            <i class="fas fa-rocket" style="font-size: 5rem; opacity: 0.3;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Статистика -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card stat-card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-primary bg-gradient text-white rounded-circle p-3 me-3">
                            <i class="fas fa-globe"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-1">Источников</h6>
                            <h3 class="mb-0 fw-bold"><?= $stats['active_sources'] ?> / <?= $stats['total_sources'] ?></h3>
                            <small class="text-muted">активных / всего</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card stat-card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-success bg-gradient text-white rounded-circle p-3 me-3">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-1">Импортировано</h6>
                            <h3 class="mb-0 fw-bold"><?= number_format($stats['total_products_imported'], 0, '', ' ') ?></h3>
                            <small class="text-muted">товаров</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card stat-card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-info bg-gradient text-white rounded-circle p-3 me-3">
                            <i class="fas fa-sync-alt"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-1">Обновлено</h6>
                            <h3 class="mb-0 fw-bold"><?= number_format($stats['total_products_updated'], 0, '', ' ') ?></h3>
                            <small class="text-muted">товаров</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card stat-card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-danger bg-gradient text-white rounded-circle p-3 me-3">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-1">Ошибок</h6>
                            <h3 class="mb-0 fw-bold"><?= number_format($stats['total_errors'], 0, '', ' ') ?></h3>
                            <small class="text-muted">всего</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Запущенные задачи -->
    <?php if (!empty($runningTasks)): ?>
    <div class="card mb-4 border-warning">
        <div class="card-header bg-warning text-dark">
            <h5 class="mb-0">
                <i class="fas fa-spinner fa-spin"></i> Запущенные задачи (<?= count($runningTasks) ?>)
            </h5>
        </div>
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Источник</th>
                        <th>Прогресс</th>
                        <th>Обработано</th>
                        <th>Запущен</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($runningTasks as $task): ?>
                    <tr data-task-id="<?= $task->id ?>" class="running-task">
                        <td>#<?= $task->id ?></td>
                        <td><?= Html::encode($task->source->name) ?></td>
                        <td>
                            <div class="progress" style="height: 20px;">
                                <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" 
                                     style="width: <?= $task->getProgress() ?>%">
                                    <?= $task->getProgress() ?>%
                                </div>
                            </div>
                        </td>
                        <td><?= $task->processed_products ?> / <?= $task->total_products ?></td>
                        <td><?= Yii::$app->formatter->asRelativeTime($task->started_at) ?></td>
                        <td>
                            <?= Html::button('<i class="fas fa-stop"></i> Стоп', [
                                'class' => 'btn btn-sm btn-outline-danger btn-stop-task',
                                'data-task-id' => $task->id,
                            ]) ?>
                            <?= Html::a('<i class="fas fa-list"></i>', ['logs', 'taskId' => $task->id], [
                                'class' => 'btn btn-sm btn-outline-secondary',
                            ]) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Источники -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Источники импорта</h5>
            <div>
                <?= Html::a('<i class="fas fa-play"></i> Запустить все', ['run-all'], [
                    'class' => 'btn btn-success btn-sm',
                    'data-method' => 'post',
                    'data-confirm' => 'Запустить импорт из всех активных источников?',
                ]) ?>
                <?= Html::a('<i class="fas fa-cog"></i> Настройки', ['settings'], [
                    'class' => 'btn btn-outline-secondary btn-sm',
                ]) ?>
            </div>
        </div>
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Источник</th>
                        <th>Валюта</th>
                        <th>Статус</th>
                        <th>Последний запуск</th>
                        <th>Успешно / Ошибок</th>
                        <th>Товаров</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sources as $source): ?>
                    <tr>
                        <td>
                            <strong><?= Html::encode($source->name) ?></strong>
                            <br><small class="text-muted"><?= Html::encode($source->base_url) ?></small>
                        </td>
                        <td>
                            <span class="badge bg-secondary"><?= Html::encode($source->currency_code) ?></span>
                            <?php if (isset($rates[$source->currency_code])): ?>
                            <br><small><?= number_format($rates[$source->currency_code], 4) ?> BYN</small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($source->is_active): ?>
                                <span class="badge bg-success">Активен</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Отключен</span>
                            <?php endif; ?>
                            
                            <?php if ($source->proxy_enabled): ?>
                                <span class="badge bg-info">Proxy</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= $source->last_run_at 
                                ? Yii::$app->formatter->asRelativeTime($source->last_run_at) 
                                : '<span class="text-muted">Никогда</span>' ?>
                        </td>
                        <td>
                            <span class="text-success"><?= $source->successful_runs ?></span> /
                            <span class="text-danger"><?= $source->failed_runs ?></span>
                        </td>
                        <td><?= number_format($source->total_products_parsed, 0, '', ' ') ?></td>
                        <td>
                            <?php if ($source->is_active): ?>
                            <?= Html::a('<i class="fas fa-play"></i>', ['run', 'sourceId' => $source->id], [
                                'class' => 'btn btn-sm btn-success',
                                'title' => 'Запустить импорт',
                                'data-method' => 'post',
                            ]) ?>
                            <?php endif; ?>
                            <?= Html::a('<i class="fas fa-cog"></i>', ['source', 'id' => $source->id], [
                                'class' => 'btn btn-sm btn-outline-secondary',
                                'title' => 'Настройки',
                            ]) ?>
                            <?= Html::a('<i class="fas fa-chart-bar"></i>', ['stats', 'sourceId' => $source->id], [
                                'class' => 'btn btn-sm btn-outline-info',
                                'title' => 'Статистика',
                            ]) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Последние задачи -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Последние задачи</h5>
            <?= Html::a('Все задачи', ['logs'], ['class' => 'btn btn-outline-secondary btn-sm']) ?>
        </div>
        <div class="card-body p-0">
            <?php Pjax::begin(['id' => 'recent-tasks-pjax']); ?>
            <?= GridView::widget([
                'dataProvider' => new \yii\data\ArrayDataProvider([
                    'allModels' => $recentTasks,
                    'pagination' => false,
                ]),
                'tableOptions' => ['class' => 'table table-hover mb-0'],
                'layout' => '{items}',
                'columns' => [
                    [
                        'attribute' => 'id',
                        'label' => 'ID',
                        'format' => 'raw',
                        'value' => function($model) {
                            return '#' . $model->id;
                        },
                    ],
                    [
                        'attribute' => 'source.name',
                        'label' => 'Источник',
                    ],
                    [
                        'attribute' => 'status',
                        'label' => 'Статус',
                        'format' => 'raw',
                        'value' => function($model) {
                            $colors = [
                                'pending' => 'secondary',
                                'running' => 'warning',
                                'completed' => 'success',
                                'failed' => 'danger',
                                'cancelled' => 'secondary',
                            ];
                            $color = $colors[$model->status] ?? 'secondary';
                            return '<span class="badge bg-' . $color . '">' . $model->getStatusLabel() . '</span>';
                        },
                    ],
                    [
                        'label' => 'Результат',
                        'format' => 'raw',
                        'value' => function($model) {
                            return '<span class="text-success">+' . $model->imported_count . '</span> ' .
                                   '<span class="text-info">~' . $model->updated_count . '</span> ' .
                                   '<span class="text-danger">!' . $model->failed_count . '</span>';
                        },
                    ],
                    [
                        'attribute' => 'created_at',
                        'label' => 'Запущен',
                        'format' => 'relativeTime',
                    ],
                    [
                        'attribute' => 'duration_seconds',
                        'label' => 'Длительность',
                        'value' => function($model) {
                            return $model->getDurationFormatted();
                        },
                    ],
                    [
                        'class' => 'yii\grid\ActionColumn',
                        'template' => '{view}',
                        'buttons' => [
                            'view' => function($url, $model) {
                                return Html::a('<i class="fas fa-list"></i>', ['logs', 'taskId' => $model->id], [
                                    'class' => 'btn btn-sm btn-outline-secondary',
                                    'title' => 'Логи',
                                ]);
                            },
                        ],
                    ],
                ],
            ]); ?>
            <?php Pjax::end(); ?>
        </div>
    </div>
</div>

<?php
// Автообновление запущенных задач
$this->registerJs(<<<JS
// Обновление прогресса запущенных задач
function updateRunningTasks() {
    $('.running-task').each(function() {
        var taskId = $(this).data('task-id');
        if (taskId) {
            $.get('/admin/import-ajax/progress', {taskId: taskId}, function(data) {
                if (data.success) {
                    if (!data.is_running) {
                        location.reload();
                    }
                }
            });
        }
    });
}

// Обновление каждые 3 секунды
if ($('.running-task').length > 0) {
    setInterval(updateRunningTasks, 3000);
}

// Остановка задачи
$('.btn-stop-task').click(function() {
    var taskId = $(this).data('task-id');
    if (confirm('Остановить задачу #' + taskId + '?')) {
        $.post('/admin/import-ajax/stop', {taskId: taskId}, function(data) {
            if (data.success) {
                location.reload();
            } else {
                alert(data.error);
            }
        });
    }
});
JS
);
?>

<style>
/* Hero секция */
.hero-section .bg-gradient {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.hero-icon {
    animation: float 3s ease-in-out infinite;
}

@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-20px); }
}

/* Статистика карточки */
.stat-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}

.stat-icon {
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

/* Карточки */
.card {
    border: none;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    transition: all 0.3s ease;
}

.card:hover {
    box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.1);
}

/* Таблицы */
.table {
    margin-bottom: 0;
}

.table th {
    border-top: none;
    font-weight: 600;
    color: #495057;
    background-color: #f8f9fa;
}

/* Кнопки */
.btn {
    border-radius: 0.5rem;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
}

.btn-primary:hover {
    background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
    transform: translateY(-2px);
}

/* Прогресс бары */
.progress {
    height: 0.5rem;
    border-radius: 0.25rem;
    background-color: #e9ecef;
}

.progress-bar {
    border-radius: 0.25rem;
}

/* Бейджи */
.badge {
    font-size: 0.75rem;
    font-weight: 500;
}

/* Анимация для запущенных задач */
.running-task {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { background-color: rgba(255, 193, 7, 0.1); }
    50% { background-color: rgba(255, 193, 7, 0.2); }
    100% { background-color: rgba(255, 193, 7, 0.1); }
}

/* Адаптивность */
@media (max-width: 768px) {
    .hero-section .display-5 {
        font-size: 2rem;
    }
    
    .stat-card .stat-icon {
        width: 50px;
        height: 50px;
        font-size: 1.2rem;
    }
    
    .stat-card h3 {
        font-size: 1.5rem;
    }
}
</style>
