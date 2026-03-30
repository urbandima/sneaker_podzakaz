<?php

use yii\helpers\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var array $stats */
/** @var app\backend\modules\admin\models\import\ImportSource[] $sources */
/** @var app\backend\modules\admin\models\import\ImportTask[] $recentTasks */
?>

<div class="import-widget">
    <!-- Заголовок -->
    <div class="widget-header">
        <div class="header-title">
            <div class="icon-circle">
                <i class="bi bi-download"></i>
            </div>
            <span>Импорт товаров</span>
        </div>
        <?= Html::a('<i class="bi bi-box-arrow-up-right"></i>', ['/admin/import'], [
            'class' => 'btn-expand',
            'title' => 'Полная версия'
        ]) ?>
    </div>

    <!-- Статистика -->
    <div class="stats-grid">
        <div class="stat-item">
            <span class="stat-number"><?= $stats['active_sources'] ?>/<?= $stats['total_sources'] ?></span>
            <span class="stat-label">Источников</span>
        </div>
        <div class="stat-item">
            <span class="stat-number"><?= number_format($stats['total_imported'], 0, '', ' ') ?></span>
            <span class="stat-label">Импортировано</span>
        </div>
    </div>

    <!-- Быстрые действия -->
    <div class="actions-section">
        <?= Html::a('<i class="bi bi-upload"></i> Загрузить файл', ['/admin/import/upload'], [
            'class' => 'btn-import-main'
        ]) ?>
        
        <?php if (!empty($sources)): ?>
        <div class="sources-list">
            <?php foreach (array_slice($sources, 0, 2) as $source): ?>
            <?= Html::a('<i class="bi bi-play-fill"></i> ' . Html::encode($source->name), 
                ['/admin/import/run', 'sourceId' => $source->id], [
                'class' => 'btn-source',
                'data-method' => 'post'
            ]) ?>
            <?php endforeach; ?>
            <?php if (count($sources) > 2): ?>
            <?= Html::a('<i class="bi bi-three-dots"></i>', ['/admin/import'], [
                'class' => 'btn-more',
                'title' => 'Все источники'
            ]) ?>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Последние задачи -->
    <?php if (!empty($recentTasks)): ?>
    <div class="recent-tasks">
        <h6>Последние импорты</h6>
        <div class="task-list">
            <?php foreach ($recentTasks as $task): ?>
            <div class="task-item">
                <div class="task-info">
                    <span class="task-name"><?= Html::encode($task->source->name) ?></span>
                    <span class="task-time"><?= Yii::$app->formatter->asRelativeTime($task->created_at) ?></span>
                </div>
                <?php
                $statusColors = [
                    'pending' => ['bg' => '#f3f4f6', 'text' => '#6b7280'],
                    'running' => ['bg' => '#fef3c7', 'text' => '#d97706'],
                    'completed' => ['bg' => '#d1fae5', 'text' => '#059669'],
                    'failed' => ['bg' => '#fee2e2', 'text' => '#dc2626'],
                ];
                $color = $statusColors[$task->status] ?? $statusColors['pending'];
                ?>
                <span class="task-badge" style="background: <?= $color['bg'] ?>; color: <?= $color['text'] ?>">
                    <?= $task->getStatusLabel() ?>
                </span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Footer -->
    <div class="widget-footer">
        <?= Html::a('<i class="bi bi-gear"></i> Настройки импорта', ['/admin/import/settings']) ?>
    </div>
</div>

<style>
/* ===== Modern Import Widget ===== */
.import-widget {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 16px;
    padding: 20px;
    box-shadow: 0 1px 3px 0 rgba(0,0,0,0.1);
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

/* Header */
.widget-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 16px;
    border-bottom: 1px solid #f3f4f6;
}

.header-title {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 16px;
    font-weight: 600;
    color: #111827;
}

.icon-circle {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: linear-gradient(135deg, #3b82f6, #8b5cf6);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 18px;
}

.btn-expand {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: #f3f4f6;
    color: #6b7280;
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    transition: all 0.2s;
}

.btn-expand:hover {
    background: #e5e7eb;
    color: #111827;
}

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin-bottom: 20px;
}

.stat-item {
    text-align: center;
    padding: 16px;
    background: #f9fafb;
    border-radius: 12px;
    border: 1px solid #f3f4f6;
}

.stat-number {
    display: block;
    font-size: 24px;
    font-weight: 700;
    color: #111827;
    margin-bottom: 4px;
}

.stat-label {
    font-size: 12px;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Actions */
.actions-section {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin-bottom: 20px;
}

.btn-import-main {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px 16px;
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    color: white;
    border-radius: 10px;
    text-decoration: none;
    font-weight: 600;
    font-size: 14px;
    transition: all 0.2s;
    border: none;
}

.btn-import-main:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
}

.sources-list {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.btn-source {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 12px;
    background: #ecfdf5;
    color: #059669;
    border-radius: 8px;
    text-decoration: none;
    font-size: 13px;
    font-weight: 500;
    transition: all 0.2s;
    border: 1px solid #d1fae5;
}

.btn-source:hover {
    background: #d1fae5;
}

.btn-more {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    background: #f3f4f6;
    color: #6b7280;
    border-radius: 8px;
    text-decoration: none;
    transition: all 0.2s;
}

.btn-more:hover {
    background: #e5e7eb;
    color: #111827;
}

/* Recent Tasks */
.recent-tasks {
    margin-bottom: 16px;
}

.recent-tasks h6 {
    margin: 0 0 12px;
    font-size: 12px;
    text-transform: uppercase;
    color: #9ca3af;
    letter-spacing: 0.5px;
    font-weight: 600;
}

.task-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.task-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px;
    background: #f9fafb;
    border-radius: 10px;
    border: 1px solid #f3f4f6;
}

.task-info {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.task-name {
    font-size: 13px;
    font-weight: 600;
    color: #374151;
}

.task-time {
    font-size: 11px;
    color: #9ca3af;
}

.task-badge {
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

/* Footer */
.widget-footer {
    padding-top: 16px;
    border-top: 1px solid #f3f4f6;
    text-align: center;
}

.widget-footer a {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    color: #6b7280;
    text-decoration: none;
    transition: color 0.2s;
}

.widget-footer a:hover {
    color: #3b82f6;
}
</style>
