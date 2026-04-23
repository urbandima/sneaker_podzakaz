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

$this->params['headerActions'] = [
    Html::a('<i class="bi bi-arrow-left"></i> Назад к импорту', ['index'], ['class' => 'admin-btn admin-btn-secondary admin-btn-sm'])
];
?>

<?php if ($task && $task->status === 'running'): ?>
<div id="logs-config" class="d-none" data-task-id="<?= $task->id ?>"></div>
<?php endif; ?>

<!-- Информация о задаче -->
<?php if ($task): ?>
<div class="admin-card mb-5">
    <div class="admin-card-header">
        <h2 class="admin-card-title">Информация о задаче</h2>
        <?php if ($task->status === 'running'): ?>
            <span class="admin-badge admin-badge-warning"><i class="bi bi-arrow-repeat"></i> Выполняется</span>
        <?php endif; ?>
    </div>
    <div class="admin-card-body">
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.5rem;">
            <div>
                <label class="form-hint">Источник</label>
                <strong><?= Html::encode($task->source->name) ?></strong>
            </div>
            <div>
                <label class="form-hint">Статус</label>
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
                <span class="admin-badge admin-badge-<?= $color ?>"><?= $task->getStatusLabel() ?></span>
            </div>
            <div>
                <label class="form-hint">Прогресс</label>
                <div style="background: var(--admin-surface); height: 20px; border-radius: 4px; overflow: hidden;">
                    <div style="background: var(--admin-accent); height: 100%; width: <?= $task->getProgress() ?>%;"></div>
                </div>
                <small><?= $task->processed_products ?> / <?= $task->total_products ?></small>
            </div>
            <div>
                <label class="form-hint">Результат</label>
                <p class="m-0">
                    <span class="text-success">+<?= $task->imported_count ?></span>
                    <span style="color: var(--admin-info);">~<?= $task->updated_count ?></span>
                    <span class="text-danger">!<?= $task->failed_count ?></span>
                    <span style="color: var(--admin-warning);">=<?= $task->duplicate_count ?></span>
                </p>
            </div>
        </div>
        
        <?php if ($task->status === 'running'): ?>
        <div class="mt-4">
            <?= Html::button('<i class="bi bi-stop-circle"></i> Остановить задачу', [
                'class' => 'admin-btn admin-btn-danger admin-btn-sm btn-stop-task',
                'data-task-id' => $task->id,
            ]) ?>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- Фильтры -->
<div class="admin-card mb-5">
    <div class="admin-card-header">
        <h2 class="admin-card-title"><i class="bi bi-funnel"></i> Фильтры</h2>
    </div>
    <div class="admin-card-body">
        <form method="get" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; align-items: end;">
            <?php if ($task): ?>
            <input type="hidden" name="taskId" value="<?= $task->id ?>">
            <?php endif; ?>
            
            <div>
                <label class="form-hint">Действие</label>
                <select name="action" class="admin-form-input">
                    <option value="">Все</option>
                    <option value="created" <?= ($filters['action'] ?? '') === 'created' ? 'selected' : '' ?>>Создан</option>
                    <option value="updated" <?= ($filters['action'] ?? '') === 'updated' ? 'selected' : '' ?>>Обновлен</option>
                    <option value="duplicate" <?= ($filters['action'] ?? '') === 'duplicate' ? 'selected' : '' ?>>Дубликат</option>
                    <option value="error" <?= ($filters['action'] ?? '') === 'error' ? 'selected' : '' ?>>Ошибка</option>
                </select>
            </div>
            
            <div>
                <label class="form-hint">Уровень</label>
                <select name="level" class="admin-form-input">
                    <option value="">Все</option>
                    <option value="info" <?= ($filters['level'] ?? '') === 'info' ? 'selected' : '' ?>>Информация</option>
                    <option value="warning" <?= ($filters['level'] ?? '') === 'warning' ? 'selected' : '' ?>>Предупреждение</option>
                    <option value="error" <?= ($filters['level'] ?? '') === 'error' ? 'selected' : '' ?>>Ошибка</option>
                </select>
            </div>
            
            <div>
                <label class="form-hint">Поиск</label>
                <input type="text" name="search" class="admin-form-input" 
                       value="<?= Html::encode($filters['search'] ?? '') ?>" 
                       placeholder="SKU, название, сообщение...">
            </div>
            
            <div>
                <button type="submit" class="admin-btn admin-btn-primary admin-btn-sm w-100">
                    <i class="bi bi-funnel"></i> Фильтр
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Логи -->
<?php Pjax::begin(['id' => 'logs-pjax']); ?>

<div class="admin-card">
    <div class="admin-card-header flex-between">
        <h2 class="admin-card-title">
            <i class="bi bi-list-ul"></i> Логи
            <?php if ($task): ?>
            <small style="color: var(--admin-text-secondary); font-weight: normal;">(Задача #<?= $task->id ?>)</small>
            <?php endif; ?>
        </h2>
        <?php if ($task && $task->status === 'running'): ?>
        <span class="admin-badge admin-badge-warning">
            <i class="bi bi-arrow-repeat"></i> Обновление...
        </span>
        <?php endif; ?>
    </div>
    <div class="admin-card-body p-0">
        <?= GridView::widget([
            'dataProvider' => $dataProvider,
            'tableOptions' => ['class' => 'admin-table'],
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
                        return '<span class="admin-badge admin-badge-' . $color . '">' . $model->getActionLabel() . '</span>';
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
                            return '<code style="color: var(--admin-danger); font-size: 0.875rem;">' . Html::encode($model->error_details) . '</code>';
                        }
                        return '';
                    },
                ],
            ],
        ]); ?>
    </div>
</div>

<?php Pjax::end(); ?>
