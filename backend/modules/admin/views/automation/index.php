<?php
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Триггеры автоматизации';

$this->params['headerActions'] = [
    Html::a('<i class="bi bi-plus-lg"></i> Создать триггер',
        Url::to(['/admin/settings/triggers/create']),
        ['class' => 'admin-btn admin-btn-primary']
    ),
    Html::a('<i class="bi bi-journal-text"></i> Лог выполнения',
        Url::to(['/admin/settings/triggers/log']),
        ['class' => 'admin-btn admin-btn-secondary']
    ),
];

$filterSearch = Yii::$app->request->get('search', '');
$filterEvent  = Yii::$app->request->get('event', '');
$filterActive = Yii::$app->request->get('active', '');

$currentSort = Yii::$app->request->get('sort', '');
$sortIcon = function(string $col) use ($currentSort): string {
    if ($currentSort === $col)       return ' <span style="color:var(--admin-primary,#202223);font-size:.65rem">▲</span>';
    if ($currentSort === '-' . $col) return ' <span style="color:var(--admin-primary,#202223);font-size:.65rem">▼</span>';
    return ' <span style="color:#d1d5db;font-size:.6rem">⇅</span>';
};

$columnDefs = [
    'event'     => 'Событие',
    'conds'     => 'Условий',
    'actions'   => 'Действий',
    'execCount' => 'Выполнений',
    'lastExec'  => 'Последнее',
];
$storageKey = 'triggersColumns';
?>

<form method="get" class="filter-wrap">
    <div class="compact-filter-bar filter-row1">
        <input type="text" name="search" class="compact-filter-input"
               placeholder="🔍 Название триггера…" value="<?= Html::encode($filterSearch) ?>">
        <select name="event" class="compact-filter-select">
            <option value="">Все события</option>
            <?php foreach ($eventCodes as $k => $v): ?>
            <option value="<?= Html::encode($k) ?>" <?= $filterEvent === $k ? 'selected' : '' ?>><?= Html::encode($v) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="active" class="compact-filter-select" style="min-width:130px">
            <option value="">Все статусы</option>
            <option value="1" <?= $filterActive === '1' ? 'selected' : '' ?>>Активные</option>
            <option value="0" <?= $filterActive === '0' ? 'selected' : '' ?>>Неактивные</option>
        </select>
        <button type="submit" class="compact-filter-btn compact-filter-btn--apply"><i class="bi bi-search"></i> Найти</button>
        <a href="<?= Url::to(['/admin/settings/triggers']) ?>" class="compact-filter-btn compact-filter-btn--reset"><i class="bi bi-x-lg"></i> Сброс</a>
        <div class="col-selector-wrap" style="margin-left:auto">
            <button type="button" class="compact-filter-btn" onclick="AdminTable.toggleColSelector(event)">
                <i class="bi bi-layout-three-columns"></i> Столбцы
            </button>
            <div id="colSelector" class="col-selector-dropdown" style="display:none">
                <div style="font-weight:700;margin-bottom:8px;font-size:.8125rem">Показать столбцы:</div>
                <?php foreach ($columnDefs as $colKey => $colLabel): ?>
                <label class="col-selector-item">
                    <input type="checkbox" data-col="<?= $colKey ?>"
                           onchange="AdminTable.toggleColumn('<?= $colKey ?>', this.checked, '<?= $storageKey ?>')" checked>
                    <?= Html::encode($colLabel) ?>
                </label>
                <?php endforeach; ?>
                <div style="margin-top:8px;border-top:1px solid var(--admin-border,#e1e3e5);padding-top:8px;display:flex;gap:6px">
                    <button type="button" onclick="AdminTable.selectAllCols(true,'<?= $storageKey ?>')" class="compact-filter-btn compact-filter-btn--apply" style="flex:1;height:26px;font-size:11px;padding:0 8px">Все</button>
                    <button type="button" onclick="AdminTable.selectAllCols(false,'<?= $storageKey ?>')" class="compact-filter-btn compact-filter-btn--reset" style="flex:1;height:26px;font-size:11px;padding:0 8px">Скрыть</button>
                </div>
            </div>
        </div>
    </div>
</form>

<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem">
        <h2 class="admin-card-title" style="margin:0"><i class="bi bi-lightning-charge"></i> Триггеры
            <span style="font-size:.8125rem;font-weight:400;color:var(--admin-text-secondary,#6d7175);margin-left:8px"><?= count($triggers) ?></span>
        </h2>
    </div>

    <?php if (empty($triggers)): ?>
        <div class="empty-state" style="padding:2.5rem">
            <div class="empty-state-icon"><i class="bi bi-lightning"></i></div>
            <h3 class="empty-state-title">Триггеров нет</h3>
            <p class="empty-state-description">Создайте первый триггер автоматизации</p>
            <?= Html::a('<i class="bi bi-plus-circle"></i> Создать триггер', ['/admin/settings/triggers/create'], ['class' => 'admin-btn admin-btn-primary']) ?>
        </div>
    <?php else: ?>
    <div style="overflow-x:auto">
        <table class="admin-table" style="font-size:.8125rem">
            <thead>
                <tr>
                    <th data-sort="name" onclick="AdminTable.sortBy('name')">Название <?= $sortIcon('name') ?></th>
                    <th data-col="event">Событие</th>
                    <th data-col="conds" style="text-align:center">Условий</th>
                    <th data-col="actions" style="text-align:center">Действий</th>
                    <th style="text-align:center">Активен</th>
                    <th data-col="execCount" data-sort="execution_count" onclick="AdminTable.sortBy('execution_count')" style="text-align:center">Выполнений <?= $sortIcon('execution_count') ?></th>
                    <th data-col="lastExec" data-sort="last_executed_at" onclick="AdminTable.sortBy('last_executed_at')" style="white-space:nowrap">Последнее <?= $sortIcon('last_executed_at') ?></th>
                    <th style="text-align:right;width:80px">—</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($triggers as $trigger): ?>
                <tr>
                    <td style="max-width:220px">
                        <strong><?= Html::encode($trigger->name) ?></strong>
                        <?php if ($trigger->description): ?>
                        <div style="font-size:11px;color:var(--admin-text-secondary,#6d7175);margin-top:2px"><?= Html::encode($trigger->description) ?></div>
                        <?php endif; ?>
                    </td>
                    <td data-col="event">
                        <span class="status-pill" style="background:#eff6ff;color:#2563eb">
                            <?= Html::encode($eventCodes[$trigger->event_code] ?? $trigger->event_code) ?>
                        </span>
                    </td>
                    <td data-col="conds" style="text-align:center"><?= (int)count((array)$trigger->getConditionsArray()) ?></td>
                    <td data-col="actions" style="text-align:center"><?= (int)count((array)$trigger->getActionsArray()) ?></td>
                    <td style="text-align:center;white-space:nowrap">
                        <label class="toggle-switch" title="Переключить активность">
                            <input type="checkbox" <?= $trigger->is_active ? 'checked' : '' ?>
                                   onchange="toggleTrigger(<?= $trigger->id ?>, this)">
                            <span class="toggle-slider"></span>
                        </label>
                        <span class="status-pill" style="margin-left:5px;background:<?= $trigger->is_active ? '#d1f7e5;color:#008060' : '#f3f4f6;color:#6d7175' ?>">
                            <?= $trigger->is_active ? 'Активен' : 'Выкл' ?>
                        </span>
                    </td>
                    <td data-col="execCount" style="text-align:center;font-weight:600"><?= $trigger->execution_count ?></td>
                    <td data-col="lastExec" style="font-size:.75rem;color:var(--admin-text-secondary,#6d7175);white-space:nowrap">
                        <?= $trigger->last_executed_at ? date('d.m.Y H:i', strtotime($trigger->last_executed_at)) : '—' ?>
                    </td>
                    <td style="text-align:right;white-space:nowrap;padding:6px 8px">
                        <?= Html::a('<i class="bi bi-pencil"></i>', Url::to(['/admin/settings/triggers/' . $trigger->id . '/edit']), ['class' => 'admin-btn admin-btn-sm admin-btn-secondary', 'title' => 'Редактировать']) ?>
                        <?= Html::a('<i class="bi bi-trash"></i>', '#', ['class' => 'admin-btn admin-btn-sm admin-btn-danger', 'title' => 'Удалить', 'onclick' => 'deleteTrigger(' . $trigger->id . ', \'' . Html::encode(addslashes($trigger->name)) . '\'); return false;']) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<form id="delete-form" method="POST" style="display:none">
    <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>
</form>

<style>
.toggle-switch{position:relative;display:inline-block;width:36px;height:20px;vertical-align:middle}
.toggle-switch input{opacity:0;width:0;height:0}
.toggle-slider{position:absolute;cursor:pointer;inset:0;background:#ccc;border-radius:20px;transition:.2s}
.toggle-slider:before{content:'';position:absolute;width:14px;height:14px;left:3px;bottom:3px;background:#fff;border-radius:50%;transition:.2s}
input:checked+.toggle-slider{background:var(--admin-success,#008060)}
input:checked+.toggle-slider:before{transform:translateX(16px)}
</style>

<script>
AdminTable.initColSelector('<?= $storageKey ?>');

function toggleTrigger(id, checkbox) {
    fetch('<?= Url::to(['/admin/settings/triggers']) ?>/' + id + '/toggle', {
        method: 'POST',
        headers: {'X-CSRF-Token': '<?= Yii::$app->request->csrfToken ?>'},
    })
    .then(r => r.json())
    .then(d => { if (!d.success) checkbox.checked = !checkbox.checked; })
    .catch(() => { checkbox.checked = !checkbox.checked; });
}

function deleteTrigger(id, name) {
    if (!confirm('Удалить триггер «' + name + '»?')) return;
    const form = document.getElementById('delete-form');
    form.action = '<?= rtrim(Url::base(true), '/') ?>/admin/settings/triggers/' + id + '/delete';
    form.submit();
}
</script>
