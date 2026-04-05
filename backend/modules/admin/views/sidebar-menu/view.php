<?php

use app\backend\modules\admin\models\SidebarMenuItem;
use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var SidebarMenuItem $model */

$this->title = $model->title;
$this->params['breadcrumbs'][] = ['label' => 'Боковое меню', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="admin-page-header">
    <h1><?= Html::encode($this->title) ?></h1>
    <div class="header-actions">
        <?= Html::a('<i class="bi bi-pencil"></i> Редактировать', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
        <?= Html::a('<i class="bi bi-arrow-left"></i> Назад', ['index'], ['class' => 'btn btn-outline']) ?>
    </div>
</div>

<div class="admin-card">
    <table class="admin-table detail-table">
        <tr>
            <th style="width: 200px;">ID</th>
            <td><?= $model->id ?></td>
        </tr>
        <tr>
            <th>Название</th>
            <td><?= Html::encode($model->title) ?></td>
        </tr>
        <tr>
            <th>Тип</th>
            <td>
                <span class="badge badge-type-<?= $model->type ?>">
                    <?= SidebarMenuItem::TYPES[$model->type] ?? $model->type ?>
                </span>
            </td>
        </tr>
        <tr>
            <th>Родитель</th>
            <td>
                <?php if ($model->parent): ?>
                    <?= Html::a(Html::encode($model->parent->title), ['view', 'id' => $model->parent_id]) ?>
                <?php else: ?>
                    <span class="text-muted">Корневой элемент</span>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <th>URL</th>
            <td><?= $model->url ? Html::a(Html::encode($model->url), $model->url, ['target' => '_blank']) : '<span class="text-muted">—</span>' ?></td>
        </tr>
        <tr>
            <th>Route</th>
            <td><?= $model->route ? '<code>' . Html::encode($model->route) . '</code>' : '<span class="text-muted">—</span>' ?></td>
        </tr>
        <tr>
            <th>Иконка</th>
            <td>
                <?php if ($model->icon): ?>
                    <i class="<?= Html::encode($model->getIconClass()) ?>" style="font-size: 1.5em;"></i>
                    <code style="margin-left: 10px;"><?= Html::encode($model->icon) ?></code>
                <?php else: ?>
                    <span class="text-muted">—</span>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <th>Изображение</th>
            <td>
                <?php if ($model->image): ?>
                    <img src="<?= Html::encode($model->image) ?>" alt="" style="max-width: 300px; max-height: 100px;">
                <?php else: ?>
                    <span class="text-muted">—</span>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <th>Порядок</th>
            <td><?= $model->sort_order ?></td>
        </tr>
        <tr>
            <th>Активен</th>
            <td><?= $model->is_active ? '<span class="badge badge-success">Да</span>' : '<span class="badge badge-danger">Нет</span>' ?></td>
        </tr>
        <tr>
            <th>Видимый</th>
            <td><?= $model->is_visible ? '<span class="badge badge-success">Да</span>' : '<span class="badge badge-danger">Нет</span>' ?></td>
        </tr>
        <tr>
            <th>Новая вкладка</th>
            <td><?= $model->target_blank ? '<span class="badge badge-warning">Да</span>' : '<span class="text-muted">Нет</span>' ?></td>
        </tr>
        <tr>
            <th>CSS класс</th>
            <td><?= $model->css_class ? '<code>' . Html::encode($model->css_class) . '</code>' : '<span class="text-muted">—</span>' ?></td>
        </tr>
        <tr>
            <th>Создан</th>
            <td><?= Yii::$app->formatter->asDatetime($model->created_at) ?></td>
        </tr>
        <tr>
            <th>Обновлён</th>
            <td><?= Yii::$app->formatter->asDatetime($model->updated_at) ?></td>
        </tr>
    </table>
</div>

<?php if ($model->children): ?>
<div class="admin-card" style="margin-top: 20px;">
    <h3>Подпункты</h3>
    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Название</th>
                <th>URL</th>
                <th>Порядок</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($model->children as $child): ?>
            <tr class="<?= $child->is_active ? '' : 'table-muted' ?>">
                <td><?= $child->id ?></td>
                <td>
                    <?php if ($child->icon): ?>
                        <i class="<?= Html::encode($child->getIconClass()) ?>" style="margin-right: 8px; opacity: 0.6;"></i>
                    <?php endif; ?>
                    <?= Html::encode($child->title) ?>
                </td>
                <td><?= $child->url ? '<code>' . Html::encode($child->url) . '</code>' : '<span class="text-muted">—</span>' ?></td>
                <td><?= $child->sort_order ?></td>
                <td>
                    <?= Html::a('<i class="bi bi-eye"></i>', ['view', 'id' => $child->id], ['class' => 'btn btn-sm btn-outline']) ?>
                    <?= Html::a('<i class="bi bi-pencil"></i>', ['update', 'id' => $child->id], ['class' => 'btn btn-sm btn-outline']) ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<style>
.detail-table th {
    background: #f8f9fa;
    text-align: left;
}
.badge-type-link { background: #e3f2fd; color: #1565c0; }
.badge-type-banner { background: #fff3e0; color: #e65100; }
.badge-type-divider { background: #f5f5f5; color: #666; }
.badge-type-header { background: #e8f5e9; color: #2e7d32; }
.table-muted { opacity: 0.6; }
</style>
