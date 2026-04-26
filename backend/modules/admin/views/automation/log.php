<?php
use yii\helpers\Html;
use yii\helpers\Url;
use app\backend\modules\automation\models\AutomationTrigger;

$this->title = 'Лог автоматизации';

$this->params['headerActions'] = [
    Html::a('<i class="bi bi-lightning-charge"></i> Триггеры',
        Url::to(['/admin/settings/triggers']),
        ['class' => 'admin-btn admin-btn-secondary']
    ),
];

$eventCodes  = AutomationTrigger::getEventCodes();
$filterEvent = Yii::$app->request->get('event', '');
$filterConds = Yii::$app->request->get('conds', '');
$filterStatus = Yii::$app->request->get('status', '');
$filterFrom  = Yii::$app->request->get('date_from', '');
$filterTo    = Yii::$app->request->get('date_to', '');

$currentSort = Yii::$app->request->get('sort', '-created_at');
$sortIcon = function(string $col) use ($currentSort): string {
    if ($currentSort === $col)       return ' <span style="color:var(--admin-primary,#202223);font-size:.65rem">▲</span>';
    if ($currentSort === '-' . $col) return ' <span style="color:var(--admin-primary,#202223);font-size:.65rem">▼</span>';
    return ' <span style="color:#d1d5db;font-size:.6rem">⇅</span>';
};
?>

<form method="get" class="filter-wrap">
    <div class="compact-filter-bar filter-row1">
        <select name="event" class="compact-filter-select" style="min-width:160px">
            <option value="">Все события</option>
            <?php foreach ($eventCodes as $k => $v): ?>
            <option value="<?= Html::encode($k) ?>" <?= $filterEvent === $k ? 'selected' : '' ?>><?= Html::encode($v) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="conds" class="compact-filter-select" style="min-width:140px">
            <option value="">Все условия</option>
            <option value="1" <?= $filterConds === '1' ? 'selected' : '' ?>>Выполнены</option>
            <option value="0" <?= $filterConds === '0' ? 'selected' : '' ?>>Не выполнены</option>
        </select>
        <select name="status" class="compact-filter-select" style="min-width:130px">
            <option value="">Все статусы</option>
            <option value="успех"  <?= $filterStatus === 'успех'  ? 'selected' : '' ?>>Успех</option>
            <option value="ошибка" <?= $filterStatus === 'ошибка' ? 'selected' : '' ?>>Ошибка</option>
        </select>
        <input type="date" name="date_from" class="compact-filter-input" value="<?= Html::encode($filterFrom) ?>" title="Дата от">
        <input type="date" name="date_to"   class="compact-filter-input" value="<?= Html::encode($filterTo) ?>"   title="Дата до">
        <button type="submit" class="compact-filter-btn compact-filter-btn--apply"><i class="bi bi-search"></i> Найти</button>
        <a href="<?= Url::to(['/admin/settings/triggers/log']) ?>" class="compact-filter-btn compact-filter-btn--reset"><i class="bi bi-x-lg"></i> Сброс</a>
    </div>
</form>

<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem">
        <h2 class="admin-card-title" style="margin:0"><i class="bi bi-journal-text"></i> Лог выполнения
            <span style="font-size:.8125rem;font-weight:400;color:var(--admin-text-secondary,#6d7175);margin-left:8px"><?= count($logs) ?></span>
        </h2>
    </div>

    <?php if (empty($logs)): ?>
        <div class="empty-state" style="padding:2.5rem">
            <div class="empty-state-icon"><i class="bi bi-journal"></i></div>
            <h3 class="empty-state-title">Лог пуст</h3>
            <p class="empty-state-description">Выполнения триггеров ещё не зафиксированы</p>
        </div>
    <?php else: ?>
    <div style="overflow-x:auto">
        <table class="admin-table" style="font-size:.8125rem">
            <thead>
                <tr>
                    <th style="width:50px;color:var(--admin-text-secondary,#6d7175)">ID</th>
                    <th>Триггер</th>
                    <th>Событие</th>
                    <th>Заказ</th>
                    <th>Условия</th>
                    <th>Результат</th>
                    <th>Время, мс</th>
                    <th data-sort="created_at" onclick="AdminTable.sortBy('created_at')">Дата <?= $sortIcon('created_at') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log):
                    $status = $log->getStatusSummary();
                    if ($status === 'успех') {
                        $statusBg = '#d1f7e5'; $statusColor = '#008060';
                    } elseif ($status === 'ошибка') {
                        $statusBg = '#fbeae5'; $statusColor = '#d72c0d';
                    } else {
                        $statusBg = '#f3f4f6'; $statusColor = '#6d7175';
                    }
                ?>
                <tr>
                    <td style="color:var(--admin-text-secondary,#6d7175);font-size:11px">#<?= $log->id ?></td>
                    <td style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                        <?= $log->trigger ? Html::encode($log->trigger->name) : '<span style="color:var(--admin-text-secondary)">—</span>' ?>
                    </td>
                    <td>
                        <span class="status-pill" style="background:#eff6ff;color:#2563eb">
                            <?= Html::encode($eventCodes[$log->event_code] ?? $log->event_code) ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($log->order_id): ?>
                        <a href="<?= Url::to(['/admin/order/' . $log->order_id]) ?>" style="color:var(--admin-info,#0078d4)">#<?= $log->order_id ?></a>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td>
                        <?php if ($log->conditions_met): ?>
                            <span class="status-pill" style="background:#d1f7e5;color:#008060">Выполнены</span>
                        <?php else: ?>
                            <span class="status-pill" style="background:#f3f4f6;color:#6d7175">Не выполнены</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="status-pill" style="background:<?= $statusBg ?>;color:<?= $statusColor ?>">
                            <?= Html::encode($status) ?>
                        </span>
                    </td>
                    <td style="font-size:.75rem;color:var(--admin-text-secondary,#6d7175)">
                        <?= $log->execution_time_ms !== null ? $log->execution_time_ms . ' мс' : '—' ?>
                    </td>
                    <td style="white-space:nowrap;color:var(--admin-text-secondary,#6d7175)">
                        <?= $log->created_at ? date('d.m.y H:i', strtotime($log->created_at)) : '—' ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
