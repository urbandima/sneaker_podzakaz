<?php

use app\backend\modules\admin\models\SidebarMenuItem;
use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var SidebarMenuItem $model */

$this->title = $model->title;
$this->params['breadcrumbs'][] = ['label' => 'Боковое меню', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$this->params['headerActions'] = [
    Html::a('<i class="bi bi-pencil"></i> Редактировать', ['update', 'id' => $model->id], ['class' => 'admin-btn admin-btn-primary admin-btn-sm']),
    Html::a('<i class="bi bi-arrow-left"></i> К списку', ['index'], ['class' => 'admin-btn admin-btn-secondary admin-btn-sm']),
];
?>

<div class="admin-card">
    <div class="admin-card-header">
        <h2 class="admin-card-title">
            <?php if ($model->icon): ?>
                <i class="<?= Html::encode($model->getIconClass()) ?>"></i>
            <?php endif; ?>
            <?= Html::encode($model->title) ?>
        </h2>
        <span class="admin-badge admin-badge-<?= $model->is_active ? 'success' : 'danger' ?>">
            <?= $model->is_active ? 'Активен' : 'Отключен' ?>
        </span>
    </div>
    <div style="overflow-x:auto">
        <table class="admin-table detail-table">
            <tbody>
            <tr>
                <th style="width:200px">ID</th>
                <td><?= $model->id ?></td>
            </tr>
            <tr>
                <th>Название</th>
                <td><?= Html::encode($model->title) ?></td>
            </tr>
            <tr>
                <th>Тип</th>
                <td>
                    <span class="admin-badge admin-badge-secondary">
                        <?= SidebarMenuItem::TYPES[$model->type] ?? $model->type ?>
                    </span>
                </td>
            </tr>
            <tr>
                <th>Родитель</th>
                <td>
                    <?php if ($model->parent): ?>
                        <?= Html::a(Html::encode($model->parent->title), ['view', 'id' => $model->parent_id], ['class' => 'admin-link']) ?>
                    <?php else: ?>
                        <span style="color:var(--admin-text-secondary)">Корневой элемент</span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th>URL</th>
                <td>
                    <?php if ($model->url): ?>
                        <?= Html::a(Html::encode($model->url), $model->url, ['target' => '_blank', 'class' => 'admin-link']) ?>
                    <?php else: ?>
                        <span style="color:var(--admin-text-secondary)">—</span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th>Route</th>
                <td><?= $model->route ? '<code>' . Html::encode($model->route) . '</code>' : '<span style="color:var(--admin-text-secondary)">—</span>' ?></td>
            </tr>
            <tr>
                <th>Иконка</th>
                <td>
                    <?php if ($model->icon): ?>
                        <i class="<?= Html::encode($model->getIconClass()) ?>" style="font-size:1.25em;margin-right:8px"></i>
                        <code><?= Html::encode($model->icon) ?></code>
                    <?php else: ?>
                        <span style="color:var(--admin-text-secondary)">—</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php if ($model->image): ?>
            <tr>
                <th>Изображение</th>
                <td><img src="<?= Html::encode($model->image) ?>" alt="" style="max-width:300px;max-height:100px;border-radius:6px"></td>
            </tr>
            <?php endif; ?>
            <tr>
                <th>Порядок</th>
                <td><?= $model->sort_order ?></td>
            </tr>
            <tr>
                <th>CSS класс</th>
                <td><?= $model->css_class ? '<code>' . Html::encode($model->css_class) . '</code>' : '<span style="color:var(--admin-text-secondary)">—</span>' ?></td>
            </tr>
            <tr>
                <th>Видимый</th>
                <td>
                    <span class="admin-badge admin-badge-<?= $model->is_visible ? 'success' : 'secondary' ?>">
                        <?= $model->is_visible ? 'Да' : 'Нет' ?>
                    </span>
                </td>
            </tr>
            <tr>
                <th>Новая вкладка</th>
                <td>
                    <span class="admin-badge admin-badge-<?= $model->target_blank ? 'warning' : 'secondary' ?>">
                        <?= $model->target_blank ? 'Да' : 'Нет' ?>
                    </span>
                </td>
            </tr>
            <tr>
                <th>Создан</th>
                <td style="color:var(--admin-text-secondary)"><?= Yii::$app->formatter->asDatetime($model->created_at) ?></td>
            </tr>
            <tr>
                <th>Обновлён</th>
                <td style="color:var(--admin-text-secondary)"><?= Yii::$app->formatter->asDatetime($model->updated_at) ?></td>
            </tr>
            </tbody>
        </table>
    </div>
</div>

<?php if (!empty($model->children)): ?>
<div class="admin-card" style="margin-top:1.25rem">
    <div class="admin-card-header">
        <h3 class="admin-card-title"><i class="bi bi-list-nested"></i> Подпункты</h3>
        <span class="admin-badge admin-badge-secondary"><?= count($model->children) ?></span>
    </div>
    <div style="overflow-x:auto">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Название</th>
                    <th>URL</th>
                    <th>Порядок</th>
                    <th>Статус</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($model->children as $child): ?>
                <tr>
                    <td style="color:var(--admin-text-secondary)"><?= $child->id ?></td>
                    <td>
                        <?php if ($child->icon): ?>
                            <i class="<?= Html::encode($child->getIconClass()) ?>" style="margin-right:6px;opacity:0.6"></i>
                        <?php endif; ?>
                        <?= Html::encode($child->title) ?>
                    </td>
                    <td><?= $child->url ? '<code>' . Html::encode($child->url) . '</code>' : '<span style="color:var(--admin-text-secondary)">—</span>' ?></td>
                    <td><?= $child->sort_order ?></td>
                    <td>
                        <span class="admin-badge admin-badge-<?= $child->is_active ? 'success' : 'danger' ?>">
                            <?= $child->is_active ? 'Активен' : 'Отключен' ?>
                        </span>
                    </td>
                    <td>
                        <?= Html::a('<i class="bi bi-eye"></i>', ['view', 'id' => $child->id], ['class' => 'admin-btn admin-btn-sm admin-btn-secondary', 'title' => 'Просмотр']) ?>
                        <?= Html::a('<i class="bi bi-pencil"></i>', ['update', 'id' => $child->id], ['class' => 'admin-btn admin-btn-sm admin-btn-secondary', 'title' => 'Редактировать']) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
