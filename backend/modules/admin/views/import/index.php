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

$this->params['headerActions'] = [
    Html::a('<i class="bi bi-upload"></i> Ручной импорт', ['upload'], ['class' => 'admin-btn admin-btn-primary admin-btn-sm']),
    Html::a('<i class="bi bi-gear"></i> Настройки', ['settings'], ['class' => 'admin-btn admin-btn-secondary admin-btn-sm'])
];
?>

<!-- Статистика -->
<div class="admin-stats" style="margin-bottom: 24px;">
    <div class="admin-stat-card">
        <div class="admin-stat-icon primary"><i class="bi bi-database"></i></div>
        <div class="admin-stat-content">
            <div class="admin-stat-value"><?= count($sources) ?></div>
            <div class="admin-stat-label">Всё источники</div>
        </div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-icon success"><i class="bi bi-check-circle"></i></div>
        <div class="admin-stat-content">
            <div class="admin-stat-value"><?= $stats['successful'] ?? 0 ?></div>
            <div class="admin-stat-label">Успешных</div>
        </div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-icon warning"><i class="bi bi-exclamation-triangle"></i></div>
        <div class="admin-stat-content">
            <div class="admin-stat-value"><?= $stats['errors'] ?? 0 ?></div>
            <div class="admin-stat-label">Ошибок</div>
        </div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-icon info"><i class="bi bi-box"></i></div>
        <div class="admin-stat-content">
            <div class="admin-stat-value"><?= number_format($stats['total_products'] ?? 0) ?></div>
            <div class="admin-stat-label">Товаров</div>
        </div>
    </div>
</div>

<!-- Запущенные задачи -->
<?php if (!empty($runningTasks)): ?>
<div class="admin-card" style="margin-bottom: 24px;">
    <div class="admin-card-header">
        <h2 class="admin-card-title">
            <i class="bi bi-spinner"></i> Запущенные задачи (<?= count($runningTasks) ?>)
        </h2>
    </div>
    <div class="admin-card-body" style="padding: 0;">
        <table class="admin-table">
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
                        <div style="background: var(--admin-surface); height: 20px; border-radius: 4px; overflow: hidden;">
                            <div style="background: var(--admin-accent); height: 100%; width: <?= $task->getProgress() ?>%;">
                                <?= $task->getProgress() ?>%
                            </div>
                        </div>
                    </td>
                    <td><?= $task->processed_products ?> / <?= $task->total_products ?></td>
                    <td><?= Yii::$app->formatter->asRelativeTime($task->started_at) ?></td>
                    <td>
                        <?= Html::button('<i class="bi bi-stop"></i> Стоп', [
                            'class' => 'admin-btn admin-btn-danger admin-btn-sm btn-stop-task',
                            'data-task-id' => $task->id,
                        ]) ?>
                        <?= Html::a('<i class="bi bi-list"></i>', ['logs', 'taskId' => $task->id], [
                            'class' => 'admin-btn admin-btn-secondary admin-btn-sm',
                            'title' => 'Логи',
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
<div class="admin-card" style="margin-bottom: 24px;">
    <div class="admin-card-header">
        <h2 class="admin-card-title">Источники данных</h2>
        <div class="admin-card-actions">
            <?= Html::a('<i class="bi bi-play"></i> Запустить все', ['run-all'], [
                'class' => 'admin-btn admin-btn-primary admin-btn-sm',
                'data-method' => 'post',
                'data-confirm' => 'Запустить импорт из всех активных источников?',
            ]) ?>
            <?= Html::a('<i class="bi bi-gear"></i> Настройки', ['settings'], [
                'class' => 'admin-btn admin-btn-secondary admin-btn-sm',
            ]) ?>
        </div>
    </div>
    <div class="admin-card-body" style="padding: 0;">
        <?php if (empty($sources)): ?>
        <div style="text-align:center;padding:2rem;color:var(--admin-text-secondary)">
            <i class="bi bi-cloud-download" style="font-size:2rem;opacity:0.4;display:block;margin-bottom:0.5rem"></i>
            <p style="margin:0">Нет настроенных источников импорта</p>
            <?= Html::a('<i class="bi bi-plus"></i> Добавить первый источник', ['settings'], ['class' => 'admin-btn admin-btn-primary admin-btn-sm', 'style' => 'margin-top:1rem']) ?>
        </div>
        <?php else: ?>
        <table class="admin-table">
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
                        <br><small style="color:var(--admin-text-secondary)"><?= Html::encode($source->base_url) ?></small>
                    </td>
                    <td>
                        <span class="admin-badge admin-badge-secondary"><?= Html::encode($source->currency_code) ?></span>
                        <?php if (isset($rates[$source->currency_code])): ?>
                        <br><small><?= number_format($rates[$source->currency_code], 4) ?> BYN</small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($source->is_active): ?>
                            <span class="admin-badge admin-badge-success">Активен</span>
                        <?php else: ?>
                            <span class="admin-badge admin-badge-secondary">Отключен</span>
                        <?php endif; ?>
                        
                        <?php if ($source->proxy_enabled): ?>
                            <span class="admin-badge admin-badge-info">Proxy</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?= $source->last_run_at 
                            ? Yii::$app->formatter->asRelativeTime($source->last_run_at) 
                            : '<span style="color:var(--admin-text-secondary)">Никогда</span>' ?>
                    </td>
                    <td>
                        <span style="color:var(--admin-success)"><?= $source->successful_runs ?></span> /
                        <span style="color:var(--admin-danger)"><?= $source->failed_runs ?></span>
                    </td>
                    <td><?= number_format($source->total_products_parsed, 0, '', ' ') ?></td>
                    <td>
                        <?php if ($source->is_active): ?>
                        <?= Html::a('<i class="bi bi-play"></i>', ['run', 'sourceId' => $source->id], [
                            'class' => 'admin-btn admin-btn-success admin-btn-sm',
                            'title' => 'Запустить импорт',
                            'data-method' => 'post',
                        ]) ?>
                        <?php endif; ?>
                        <?= Html::a('<i class="bi bi-gear"></i>', ['source', 'id' => $source->id], [
                            'class' => 'admin-btn admin-btn-secondary admin-btn-sm',
                            'title' => 'Настройки',
                        ]) ?>
                        <?= Html::a('<i class="bi bi-bar-chart"></i>', ['stats', 'sourceId' => $source->id], [
                            'class' => 'admin-btn admin-btn-secondary admin-btn-sm',
                            'title' => 'Статистика',
                        ]) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<!-- Последние задачи -->
<div class="admin-card">
    <div class="admin-card-header">
        <h2 class="admin-card-title">Последние задачи</h2>
        <?= Html::a('Все задачи', ['logs'], ['class' => 'admin-btn admin-btn-secondary admin-btn-sm']) ?>
    </div>
    <div class="admin-card-body" style="padding: 0;">
        <?php Pjax::begin(['id' => 'recent-tasks-pjax']); ?>
        <?= GridView::widget([
            'dataProvider' => new \yii\data\ArrayDataProvider([
                'allModels' => $recentTasks,
                'pagination' => false,
            ]),
            'tableOptions' => ['class' => 'admin-table'],
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
                        return '<span class="admin-badge admin-badge-' . $color . '">' . $model->getStatusLabel() . '</span>';
                    },
                ],
                [
                    'label' => 'Результат',
                    'format' => 'raw',
                    'value' => function($model) {
                        return '<span style="color:var(--admin-success)">+' . $model->imported_count . '</span> ' .
                               '<span style="color:var(--admin-info)">~' . $model->updated_count . '</span> ' .
                               '<span style="color:var(--admin-danger)">!' . $model->failed_count . '</span>';
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
                            return Html::a('<i class="bi bi-list"></i>', ['logs', 'taskId' => $model->id], [
                                'class' => 'admin-btn admin-btn-secondary admin-btn-sm',
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

