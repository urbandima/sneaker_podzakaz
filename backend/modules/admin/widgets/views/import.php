<?php

use yii\helpers\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var array $stats */
/** @var app\backend\modules\admin\models\import\ImportSource[] $sources */
/** @var app\backend\modules\admin\models\import\ImportTask[] $recentTasks */
?>

<div class="import-widget">
    <!-- Заголовок виджета -->
    <div class="widget-header d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">
            <i class="fas fa-download text-primary me-2"></i>
            <span>Импорт товаров</span>
        </h5>
        <?= Html::a('<i class="fas fa-expand-alt"></i>', ['/admin/import'], [
            'class' => 'btn btn-sm btn-outline-secondary',
            'title' => 'Полная версия импорта'
        ]) ?>
    </div>

    <!-- Статистика -->
    <div class="row mb-3">
        <div class="col-6">
            <div class="stat-item">
                <div class="stat-value text-primary"><?= $stats['active_sources'] ?>/<?= $stats['total_sources'] ?></div>
                <div class="stat-label small text-muted">Источников</div>
            </div>
        </div>
        <div class="col-6">
            <div class="stat-item">
                <div class="stat-value text-success"><?= number_format($stats['total_imported'], 0, '', ' ') ?></div>
                <div class="stat-label small text-muted">Импортировано</div>
            </div>
        </div>
    </div>

    <!-- Быстрые действия -->
    <div class="quick-actions mb-3">
        <div class="d-grid gap-2">
            <?= Html::a('<i class="fas fa-file-upload me-2"></i>Загрузить файл', ['/admin/import/upload'], [
                'class' => 'btn btn-primary btn-sm'
            ]) ?>
            
            <?php if (!empty($sources)): ?>
            <div class="btn-group" role="group">
                <?php foreach (array_slice($sources, 0, 2) as $source): ?>
                <?= Html::a('<i class="fas fa-play"></i> ' . Html::encode($source->name), 
                    ['/admin/import/run', 'sourceId' => $source->id], [
                    'class' => 'btn btn-outline-success btn-sm',
                    'data-method' => 'post',
                    'title' => 'Запустить импорт из ' . Html::encode($source->name)
                ]) ?>
                <?php endforeach; ?>
                <?php if (count($sources) > 2): ?>
                <?= Html::a('<i class="fas fa-ellipsis-h"></i>', ['/admin/import'], [
                    'class' => 'btn btn-outline-secondary btn-sm',
                    'title' => 'Все источники'
                ]) ?>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Последние задачи -->
    <?php if (!empty($recentTasks)): ?>
    <div class="recent-tasks">
        <h6 class="text-muted mb-2">Последние импорты</h6>
        <div class="task-list">
            <?php foreach ($recentTasks as $task): ?>
            <div class="task-item d-flex justify-content-between align-items-center py-1">
                <div class="task-info">
                    <div class="task-name small"><?= Html::encode($task->source->name) ?></div>
                    <div class="task-time text-muted" style="font-size: 0.75rem;">
                        <?= Yii::$app->formatter->asRelativeTime($task->created_at) ?>
                    </div>
                </div>
                <div class="task-status">
                    <?php
                    $colors = [
                        'pending' => 'secondary',
                        'running' => 'warning',
                        'completed' => 'success',
                        'failed' => 'danger',
                    ];
                    $color = $colors[$task->status] ?? 'secondary';
                    ?>
                    <span class="badge bg-<?= $color ?> badge-sm">
                        <?= $task->getStatusLabel() ?>
                    </span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Ссылка на полную версию -->
    <div class="widget-footer mt-3 pt-3 border-top">
        <?= Html::a(
            '<i class="fas fa-cog me-1"></i>Настройки импорта', 
            ['/admin/import/settings'], 
            ['class' => 'text-muted small text-decoration-none']
        ) ?>
    </div>
</div>

<style>
.import-widget {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 0.5rem;
    padding: 1rem;
}

.widget-header {
    border-bottom: 1px solid #e9ecef;
    padding-bottom: 0.75rem;
}

.stat-item {
    text-align: center;
}

.stat-value {
    font-size: 1.25rem;
    font-weight: 600;
}

.stat-label {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.task-item {
    border-bottom: 1px solid #f1f3f4;
}

.task-item:last-child {
    border-bottom: none;
}

.badge-sm {
    font-size: 0.65rem;
    padding: 0.2rem 0.4rem;
}

.btn-sm {
    font-size: 0.8rem;
}

.quick-actions .btn-group .btn {
    border-radius: 0;
}

.quick-actions .btn-group .btn:first-child {
    border-top-left-radius: 0.25rem;
    border-bottom-left-radius: 0.25rem;
}

.quick-actions .btn-group .btn:last-child {
    border-top-right-radius: 0.25rem;
    border-bottom-right-radius: 0.25rem;
}
</style>
