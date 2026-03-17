<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var app\backend\modules\admin\models\import\ImportTask|null $task */
/** @var array $filters */

$this->title = $task ? "Логи задачи #{$task->id}" : 'Логи импорта';
$this->params['breadcrumbs'][] = ['label' => 'Импорт', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="import-logs">
    <!-- Информация о задаче -->
    <?php if ($task): ?>
    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <h6 class="text-muted">Источник</h6>
                    <p class="mb-0"><strong><?= Html::encode($task->source->name) ?></strong></p>
                </div>
                <div class="col-md-3">
                    <h6 class="text-muted">Статус</h6>
                    <?php
                    $statusColors = [
                        'pending' => 'secondary',
                        'running' => 'warning',
                        'completed' => 'success',
                        'failed' => 'danger',
                        'cancelled' => 'secondary',
                    ];
                    $color = $statusColors[$task->status] ?? 'secondary';
                    ?>
                    <span class="badge bg-<?= $color ?> fs-6"><?= $task->getStatusLabel() ?></span>
                </div>
                <div class="col-md-3">
                    <h6 class="text-muted">Прогресс</h6>
                    <div class="progress" style="height: 25px;">
                        <div class="progress-bar bg-<?= $task->status === 'completed' ? 'success' : 'primary' ?>" 
                             style="width: <?= $task->getProgress() ?>%">
                            <?= $task->processed_products ?> / <?= $task->total_products ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <h6 class="text-muted">Результат</h6>
                    <p class="mb-0">
                        <span class="text-success">+<?= $task->imported_count ?></span>
                        <span class="text-info">~<?= $task->updated_count ?></span>
                        <span class="text-danger">!<?= $task->failed_count ?></span>
                        <span class="text-warning">=<?= $task->duplicate_count ?></span>
                    </p>
                </div>
            </div>
            
            <?php if ($task->status === 'running'): ?>
            <div class="mt-3">
                <?= Html::button('<i class="fas fa-stop"></i> Остановить задачу', [
                    'class' => 'btn btn-outline-danger btn-stop-task',
                    'data-task-id' => $task->id,
                ]) ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Фильтры -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="get" class="row g-3">
                <?php if ($task): ?>
                <input type="hidden" name="taskId" value="<?= $task->id ?>">
                <?php endif; ?>
                
                <div class="col-md-3">
                    <label class="form-label">Действие</label>
                    <select name="action" class="form-select">
                        <option value="">Все</option>
                        <option value="created" <?= ($filters['action'] ?? '') === 'created' ? 'selected' : '' ?>>Создан</option>
                        <option value="updated" <?= ($filters['action'] ?? '') === 'updated' ? 'selected' : '' ?>>Обновлен</option>
                        <option value="duplicate" <?= ($filters['action'] ?? '') === 'duplicate' ? 'selected' : '' ?>>Дубликат</option>
                        <option value="error" <?= ($filters['action'] ?? '') === 'error' ? 'selected' : '' ?>>Ошибка</option>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">Уровень</label>
                    <select name="level" class="form-select">
                        <option value="">Все</option>
                        <option value="info" <?= ($filters['level'] ?? '') === 'info' ? 'selected' : '' ?>>Информация</option>
                        <option value="warning" <?= ($filters['level'] ?? '') === 'warning' ? 'selected' : '' ?>>Предупреждение</option>
                        <option value="error" <?= ($filters['level'] ?? '') === 'error' ? 'selected' : '' ?>>Ошибка</option>
                    </select>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Поиск</label>
                    <input type="text" name="search" class="form-control" 
                           value="<?= Html::encode($filters['search'] ?? '') ?>" 
                           placeholder="SKU, название, сообщение...">
                </div>
                
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter"></i> Фильтр
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Логи -->
    <?php Pjax::begin(['id' => 'logs-pjax']); ?>
    
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                Логи
                <?php if ($task): ?>
                <small class="text-muted">(Задача #<?= $task->id ?>)</small>
                <?php endif; ?>
            </h5>
            <?php if ($task && $task->status === 'running'): ?>
            <span class="badge bg-warning text-dark">
                <i class="fas fa-spinner fa-spin"></i> Обновление...
            </span>
            <?php endif; ?>
        </div>
        <div class="card-body p-0">
            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'tableOptions' => ['class' => 'table table-hover mb-0'],
                'pager' => [
                    'class' => 'yii\bootstrap5\LinkPager',
                ],
                'columns' => [
                    [
                        'attribute' => 'created_at',
                        'label' => 'Время',
                        'format' => 'raw',
                        'value' => function($model) {
                            return '<small>' . date('H:i:s', strtotime($model->created_at)) . '</small>';
                        },
                        'headerOptions' => ['style' => 'width: 80px'],
                    ],
                    [
                        'attribute' => 'action',
                        'label' => 'Действие',
                        'format' => 'raw',
                        'value' => function($model) {
                            $colors = [
                                'created' => 'success',
                                'updated' => 'info',
                                'duplicate' => 'warning',
                                'error' => 'danger',
                                'skipped' => 'secondary',
                            ];
                            $color = $colors[$model->action] ?? 'secondary';
                            return '<span class="badge bg-' . $color . '">' . $model->getActionLabel() . '</span>';
                        },
                        'headerOptions' => ['style' => 'width: 100px'],
                    ],
                    [
                        'attribute' => 'sku',
                        'label' => 'SKU',
                        'headerOptions' => ['style' => 'width: 120px'],
                    ],
                    [
                        'attribute' => 'product_name',
                        'label' => 'Товар',
                        'format' => 'raw',
                        'value' => function($model) {
                            $name = Html::encode($model->product_name);
                            if ($model->product_id) {
                                return Html::a($name, ['/catalog/product/view', 'id' => $model->product_id], [
                                    'target' => '_blank',
                                ]);
                            }
                            return $name;
                        },
                    ],
                    [
                        'attribute' => 'message',
                        'label' => 'Сообщение',
                        'format' => 'ntext',
                    ],
                    [
                        'attribute' => 'error_details',
                        'label' => 'Ошибка',
                        'format' => 'raw',
                        'value' => function($model) {
                            if ($model->error_details) {
                                return '<code class="text-danger small">' . Html::encode($model->error_details) . '</code>';
                            }
                            return '';
                        },
                    ],
                ],
            ]); ?>
        </div>
    </div>
    
    <?php Pjax::end(); ?>
</div>

<?php if ($task && $task->status === 'running'): ?>
<?php
$this->registerJs(<<<JS
// Автообновление логов
var lastLogId = 0;
var taskId = {$task->id};

function updateLogs() {
    $.get('/admin/import-ajax/logs', {
        taskId: taskId,
        lastId: lastLogId
    }, function(data) {
        if (data.success && data.logs.length > 0) {
            lastLogId = data.last_id;
            
            // Добавляем новые логи в таблицу
            data.logs.forEach(function(log) {
                var color = {
                    'created': 'success',
                    'updated': 'info',
                    'duplicate': 'warning',
                    'error': 'danger'
                }[log.action] || 'secondary';
                
                var row = '<tr>' +
                    '<td><small>' + log.time + '</small></td>' +
                    '<td><span class="badge bg-' + color + '">' + log.action_label + '</span></td>' +
                    '<td>' + (log.sku || '') + '</td>' +
                    '<td>' + log.product_name + '</td>' +
                    '<td>' + log.message + '</td>' +
                    '</tr>';
                
                $('table tbody').prepend(row);
            });
        }
        
        // Если задача завершена - перезагружаем страницу
        if (data.task_status !== 'running') {
            location.reload();
        }
    });
}

// Обновление каждые 2 секунды
setInterval(updateLogs, 2000);

// Остановка задачи
$('.btn-stop-task').click(function() {
    if (confirm('Остановить задачу?')) {
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
<?php endif; ?>
