<?php
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Журнал действий';
$this->params['breadcrumbs'][] = ['label' => 'Журнал действий'];

$actionColors = [
    'create'        => '#059669',
    'update'        => '#d97706',
    'delete'        => '#dc2626',
    'status_change' => '#7c3aed',
    'bulk_action'   => '#2563eb',
    'export'        => '#0891b2',
    'import'        => '#0891b2',
    'login'         => '#6b7280',
    'logout'        => '#6b7280',
];
?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
    <h1 class="admin-page-title" style="margin:0"><i class="bi bi-journal-text"></i> Журнал действий</h1>
</div>

<!-- Filters -->
<form method="GET" class="admin-card" style="margin-bottom:16px">
    <div class="admin-card-body" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end">
        <div>
            <label class="admin-label">Действие</label>
            <select name="action" class="admin-select" style="width:auto">
                <option value="">— Все —</option>
                <?php foreach ($actionTypes as $k => $v): ?>
                <option value="<?= $k ?>" <?= $filterAction === $k ? 'selected' : '' ?>><?= Html::encode($v) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="admin-label">Пользователь</label>
            <input type="text" name="user" class="admin-input" value="<?= Html::encode($filterUser) ?>" placeholder="логин..." style="width:160px">
        </div>
        <div>
            <label class="admin-label">Тип сущности</label>
            <input type="text" name="entity" class="admin-input" value="<?= Html::encode($filterEntity) ?>" placeholder="order, product..." style="width:140px">
        </div>
        <div>
            <label class="admin-label">Дата</label>
            <input type="date" name="date" class="admin-input" value="<?= Html::encode($filterDate) ?>" style="width:150px">
        </div>
        <button type="submit" class="admin-btn admin-btn-primary"><i class="bi bi-search"></i> Найти</button>
        <?= Html::a('<i class="bi bi-x"></i> Сбросить', Url::to(['/admin/activity-log']), ['class' => 'admin-btn admin-btn-secondary']) ?>
    </div>
</form>

<!-- Log table -->
<div class="admin-card">
    <div class="admin-card-body" style="padding:0">
        <?php if (empty($logs)): ?>
        <div style="padding:40px;text-align:center;color:var(--admin-text-secondary)">
            <i class="bi bi-journal-x" style="font-size:2rem"></i>
            <p>Записей не найдено</p>
        </div>
        <?php else: ?>
        <div style="overflow-x:auto">
        <table class="admin-table" style="font-size:.8125rem">
            <thead>
                <tr>
                    <th style="width:150px">Дата</th>
                    <th style="width:120px">Пользователь</th>
                    <th style="width:120px">Действие</th>
                    <th style="width:100px">Сущность</th>
                    <th>Описание</th>
                    <th style="width:100px">IP</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                <tr>
                    <td style="white-space:nowrap;color:var(--admin-text-secondary);font-size:.75rem">
                        <?= $log->created_at ? date('d.m.Y H:i:s', strtotime($log->created_at)) : '—' ?>
                    </td>
                    <td><?= Html::encode($log->username ?? '—') ?></td>
                    <td>
                        <span style="display:inline-block;padding:2px 8px;border-radius:4px;font-size:.75rem;font-weight:600;
                              background:<?= $actionColors[$log->action] ?? '#6b7280' ?>20;
                              color:<?= $actionColors[$log->action] ?? '#6b7280' ?>">
                            <?= Html::encode($log->getActionLabel()) ?>
                        </span>
                    </td>
                    <td style="color:var(--admin-text-secondary)">
                        <?php if ($log->entity_id && $log->entity_type): ?>
                            <?php $url = $log->getEntityUrl(); ?>
                            <?php if ($url): ?>
                            <a href="<?= Html::encode($url) ?>" style="color:var(--admin-primary)"><?= Html::encode($log->entity_type) ?> #<?= $log->entity_id ?></a>
                            <?php else: ?>
                            <?= Html::encode($log->entity_type) ?> #<?= $log->entity_id ?>
                            <?php endif; ?>
                        <?php elseif ($log->entity_type): ?>
                        <?= Html::encode($log->entity_type) ?>
                        <?php else: ?>
                        —
                        <?php endif; ?>
                    </td>
                    <td style="max-width:400px">
                        <?= Html::encode($log->description ?? '') ?>
                        <?php if ($log->entity_name): ?>
                        <span style="color:var(--admin-text-secondary);font-size:.75rem"> — <?= Html::encode($log->entity_name) ?></span>
                        <?php endif; ?>
                    </td>
                    <td style="font-family:monospace;font-size:.75rem;color:var(--admin-text-secondary)"><?= Html::encode($log->ip_address ?? '—') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <div style="padding:12px 16px;color:var(--admin-text-secondary);font-size:.8125rem">
            Показано <?= count($logs) ?> записей (максимум 500)
        </div>
        <?php endif; ?>
    </div>
</div>
