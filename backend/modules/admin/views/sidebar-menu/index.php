<?php

use app\backend\modules\admin\models\SidebarMenuItem;
use yii\helpers\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = 'Боковое меню (Sidebar)';
$this->params['breadcrumbs'][] = $this->title;
?>

<?php $this->params['headerActions'] = [
    Html::a('<i class="bi bi-plus-lg"></i> Добавить пункт', ['create'], ['class' => 'admin-btn admin-btn-primary admin-btn-sm'])
]; ?>

<div class="admin-card">
    <div class="admin-card-header">
        <h2 class="admin-card-title">Пункты меню</h2>
    </div>
    <div class="admin-card-body" style="padding: 0;">
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width: 40px;"></th>
                    <th style="width: 60px;">ID</th>
                    <th>Название</th>
                    <th>Тип</th>
                    <th>URL / Route</th>
                    <th style="width: 80px;">Порядок</th>
                    <th style="width: 100px;">Статус</th>
                    <th style="width: 120px;">Действия</th>
                </tr>
            </thead>
            <tbody id="sortable-list" data-sort-url="<?= Url::to(['sort']) ?>">
                <?php if ($dataProvider->getCount() === 0): ?>
                <tr>
                    <td colspan="8" style="text-align:center;padding:3rem;color:var(--admin-text-secondary)">
                        <i class="bi bi-layout-sidebar" style="font-size:2.5rem;display:block;margin-bottom:0.75rem;opacity:0.4"></i>
                        <strong style="display:block;margin-bottom:0.5rem;color:var(--admin-text)">Пункты меню не созданы</strong>
                        Здесь вы управляете структурой бокового меню админ-панели.<br>
                        <?= Html::a('<i class="bi bi-plus-lg"></i> Добавить первый пункт', ['create'], ['class' => 'admin-btn admin-btn-primary admin-btn-sm', 'style' => 'margin-top:1rem;display:inline-flex;align-items:center;gap:0.4rem']) ?>
                    </td>
                </tr>
                <?php endif; ?>
                <?php foreach ($dataProvider->getModels() as $model): ?>
                <tr data-id="<?= $model->id ?>" class="<?= $model->is_active ? '' : 'table-muted' ?>">
                    <td>
                        <span class="drag-handle" title="Перетащите для сортировки">
                            <i class="bi bi-grip-vertical"></i>
                        </span>
                    </td>
                    <td><?= $model->id ?></td>
                    <td>
                        <?php if ($model->icon): ?>
                            <i class="<?= Html::encode($model->getIconClass()) ?>" style="margin-right: 8px; opacity: 0.6;"></i>
                        <?php endif; ?>
                        <?= Html::encode($model->title) ?>
                        <?php if ($model->parent_id): ?>
                            <small style="color:var(--admin-text-secondary)">(подпункт)</small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="admin-badge admin-badge-secondary">
                            <?= SidebarMenuItem::TYPES[$model->type] ?? $model->type ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($model->url): ?>
                            <code><?= Html::encode($model->url) ?></code>
                        <?php elseif ($model->route): ?>
                            <code><?= Html::encode($model->route) ?></code>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td><?= $model->sort_order ?></td>
                    <td>
                        <?= Html::a(
                            $model->is_active ? '<i class="bi bi-check-circle"></i> Активен' : '<i class="bi bi-x-circle"></i> Отключен',
                            ['toggle', 'id' => $model->id],
                            [
                                'class' => 'admin-badge ' . ($model->is_active ? 'admin-badge-success' : 'admin-badge-danger'),
                                'data-method' => 'post'
                            ]
                        ) ?>
                    </td>
                    <td>
                        <div class="product-actions">
                            <?= Html::a('<i class="bi bi-eye"></i>', ['view', 'id' => $model->id], ['class' => 'action-btn', 'title' => 'Просмотр']) ?>
                            <?= Html::a('<i class="bi bi-pencil"></i>', ['update', 'id' => $model->id], ['class' => 'action-btn', 'title' => 'Редактировать']) ?>
                            <?= Html::a('<i class="bi bi-trash"></i>', ['delete', 'id' => $model->id], [
                                'class' => 'action-btn',
                                'title' => 'Удалить',
                                'data-method' => 'post',
                                'data-confirm' => 'Удалить пункт меню?',
                                'style' => 'color:var(--admin-danger)'
                            ]) ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div><!-- /.table-responsive -->
    </div><!-- /.admin-card-body -->
</div><!-- /.admin-card -->
