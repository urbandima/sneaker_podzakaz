<?php
/**
 * @var yii\web\View $this
 * @var yii\data\ActiveDataProvider $characteristicsProvider
 * @var yii\data\ActiveDataProvider $sizeGridProvider
 * @var string $tab
 */

use yii\grid\GridView;
use yii\helpers\Html;
use yii\helpers\Url;
use app\backend\modules\catalog\models\Characteristic;
use app\backend\modules\catalog\models\SizeGrid;

$this->title = 'Характеристики и размеры';
?>

<div class="admin-header">
    <h1 class="admin-header-title"><?= Html::encode($this->title) ?></h1>
    <div class="admin-header-actions">
        <a href="<?= Url::to(['guide']) ?>" class="admin-btn admin-btn-secondary">
            <i class="bi bi-journal-text"></i>
            Справочник
        </a>
        <a href="<?= Url::to(['size-create']) ?>" class="admin-btn admin-btn-secondary">
            <i class="bi bi-rulers"></i>
            Новая размерная сетка
        </a>
        <a href="<?= Url::to(['create']) ?>" class="admin-btn admin-btn-primary">
            <i class="bi bi-plus-circle"></i>
            Новая характеристика
        </a>
    </div>
</div>

<!-- Табы -->
<div class="admin-card">
    <div style="display: flex; gap: 0.5rem; margin-bottom: 1.5rem; border-bottom: 1px solid var(--admin-border);">
        <button class="tab-btn <?= $tab === 'characteristics' ? 'active' : '' ?>" 
                onclick="window.location.href='<?= Url::to(['', 'tab' => 'characteristics']) ?>'">
            <i class="bi bi-tags"></i>
            Характеристики
        </button>
        <button class="tab-btn <?= $tab === 'size-grid' ? 'active' : '' ?>" 
                onclick="window.location.href='<?= Url::to(['', 'tab' => 'size-grid']) ?>'">
            <i class="bi bi-grid-3x3"></i>
            Размерные сетки
        </button>
    </div>

    <?php if ($tab === 'characteristics'): ?>
        <h2 class="admin-card-title">
            <i class="bi bi-tags"></i>
            Характеристики товаров
        </h2>
        
        <div style="overflow-x: auto;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Название</th>
                        <th>Тип</th>
                        <th>Категория</th>
                        <th>Обязательное</th>
                        <th>Сортировка</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($characteristicsProvider->getModels() as $model): ?>
                        <tr>
                            <td style="font-weight: 600;"><?= $model->id ?></td>
                            <td><?= Html::encode($model->name) ?></td>
                            <td>
                                <span class="admin-badge admin-badge-<?= $model->type === 'select' ? 'info' : 'primary' ?>">
                                    <?= $model->getTypeName() ?>
                                </span>
                            </td>
                            <td><?= Html::encode($model->category->name ?? '—') ?></td>
                            <td>
                                <span class="admin-badge admin-badge-<?= $model->is_required ? 'success' : 'secondary' ?>">
                                    <?= $model->is_required ? 'Да' : 'Нет' ?>
                                </span>
                            </td>
                            <td><?= $model->sort_order ?></td>
                            <td>
                                <div style="display: flex; gap: 0.25rem;">
                                    <a href="<?= Url::to(['update', 'id' => $model->id]) ?>" 
                                       class="admin-btn admin-btn-secondary" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;" title="Редактировать">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <?= Html::a('<i class="bi bi-trash"></i>', ['delete', 'id' => $model->id], [
                                        'class' => 'admin-btn admin-btn-danger',
                                        'style' => 'padding: 0.25rem 0.5rem; font-size: 0.75rem;',
                                        'title' => 'Удалить',
                                        'data-confirm' => 'Вы уверены, что хотите удалить эту характеристику?',
                                        'data-method' => 'post',
                                    ]) ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <h2 class="admin-card-title">
            <i class="bi bi-grid-3x3"></i>
            Размерные сетки
        </h2>
        
        <div style="overflow-x: auto;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Название</th>
                        <th>Категория</th>
                        <th>Размеры</th>
                        <th>Сортировка</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sizeGridProvider->getModels() as $model): ?>
                        <tr>
                            <td style="font-weight: 600;"><?= $model->id ?></td>
                            <td><?= Html::encode($model->name) ?></td>
                            <td><?= Html::encode($model->category->name ?? '—') ?></td>
                            <td>
                                <span class="admin-badge admin-badge-info">
                                    <?= count($model->sizes) ?> размеров
                                </span>
                            </td>
                            <td><?= $model->sort_order ?></td>
                            <td>
                                <div style="display: flex; gap: 0.25rem;">
                                    <a href="<?= Url::to(['size-update', 'id' => $model->id]) ?>" 
                                       class="admin-btn admin-btn-secondary" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;" title="Редактировать">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <?= Html::a('<i class="bi bi-trash"></i>', ['size-delete', 'id' => $model->id], [
                                        'class' => 'admin-btn admin-btn-danger',
                                        'style' => 'padding: 0.25rem 0.5rem; font-size: 0.75rem;',
                                        'title' => 'Удалить',
                                        'data-confirm' => 'Вы уверены, что хотите удалить эту размерную сетку?',
                                        'data-method' => 'post',
                                    ]) ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<style>
.tab-btn {
    padding: 0.75rem 1rem;
    border: none;
    background: transparent;
    color: var(--admin-text-secondary);
    cursor: pointer;
    border-bottom: 2px solid transparent;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 500;
}

.tab-btn:hover {
    color: var(--admin-text-primary);
    background: var(--admin-primary-soft);
}

.tab-btn.active {
    color: var(--admin-primary);
    border-bottom-color: var(--admin-primary);
    background: var(--admin-primary-soft);
}
</style>
