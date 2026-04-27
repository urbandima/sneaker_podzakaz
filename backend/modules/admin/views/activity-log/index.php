<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\LinkPager;
use app\backend\modules\admin\controllers\ActivityLogController;

/**
 * @var yii\web\View           $this
 * @var array                  $logs
 * @var yii\data\Pagination    $pagination
 * @var array                  $adminUsers
 * @var int                    $totalCount
 * @var array                  $filterValues
 * @var array                  $targetTypes
 * @var array                  $actions
 * @var array                  $sources
 */

$this->title = 'Журнал действий';
$this->params['breadcrumbs'][] = ['label' => 'Журнал действий'];

$f = $filterValues;

// ── Иконки и цвета действий ───────────────────────────────────────────────
$actionMeta = [
    'created'        => ['icon' => 'bi-plus-circle-fill',  'color' => '#059669', 'bg' => '#d1fae5'],
    'updated'        => ['icon' => 'bi-pencil-fill',        'color' => '#d97706', 'bg' => '#fef3c7'],
    'deleted'        => ['icon' => 'bi-trash-fill',         'color' => '#dc2626', 'bg' => '#fee2e2'],
    'status_changed' => ['icon' => 'bi-arrow-repeat',       'color' => '#7c3aed', 'bg' => '#ede9fe'],
    'login'          => ['icon' => 'bi-box-arrow-in-right', 'color' => '#0369a1', 'bg' => '#e0f2fe'],
    'logout'         => ['icon' => 'bi-box-arrow-right',    'color' => '#6b7280', 'bg' => '#f3f4f6'],
];

// ── Метки типов и действий ────────────────────────────────────────────────
$targetTypeLabels = $targetTypes;
$actionLabels     = $actions;

// ── URL-целей ─────────────────────────────────────────────────────────────
$targetUrls = [
    'Order'     => '/admin/order/view',
    'Customer'  => '/admin/customer/view',
    'Product'   => '/admin/product/view',
    'Buyout'    => '/admin/procurement/buyout/view',
    'Receiving' => '/admin/procurement/receiving/view',
    'User'      => '/admin/user/view',
];

/** Построить URL к объекту */
$makeTargetUrl = static function (array $row) use ($targetUrls): ?string {
    $type = $row['target_type'] ?? '';
    $id   = $row['target_id'] ?? null;
    if (!$id || !isset($targetUrls[$type])) {
        return null;
    }
    return Url::to([$targetUrls[$type], 'id' => $id]);
};

/** Относительное время */
$relTime = static function ($ts): string {
    if (!$ts) return '—';
    $diff = time() - (int)$ts;
    if ($diff < 60)     return 'только что';
    if ($diff < 3600)   return floor($diff / 60) . ' мин. назад';
    if ($diff < 86400)  return 'сегодня в ' . date('H:i', $ts);
    if ($diff < 172800) return 'вчера в '   . date('H:i', $ts);
    return date('d M', $ts) . ' в ' . date('H:i', $ts);
};

/** Красивый diff из JSON-строки */
$renderDiff = static function (string $changesJson): string {
    $data = @json_decode($changesJson, true);
    if (!is_array($data) || empty($data)) return '';

    $rows = '';
    foreach ($data as $field => $chg) {
        if (!is_array($chg)) continue;
        $old = isset($chg['old']) ? Html::encode((string)$chg['old']) : '—';
        $new = isset($chg['new']) ? Html::encode((string)$chg['new']) : '—';
        $rows .= '<tr>'
            . '<td style="color:var(--admin-text-secondary);white-space:nowrap;padding:3px 10px 3px 0;font-size:.75rem">' . Html::encode($field) . '</td>'
            . '<td style="color:#dc2626;padding:3px 8px 3px 0;font-size:.75rem;max-width:200px;overflow:hidden;text-overflow:ellipsis" title="' . $old . '">' . $old . '</td>'
            . '<td style="color:#6b7280;padding:3px 6px;font-size:.75rem">→</td>'
            . '<td style="color:#059669;padding:3px 0;font-size:.75rem;max-width:200px;overflow:hidden;text-overflow:ellipsis" title="' . $new . '">' . $new . '</td>'
            . '</tr>';
    }

    return '<table style="border-collapse:collapse;width:100%">' . $rows . '</table>';
};

/** Сформировать описание записи */
$buildDescription = static function (array $row) use ($actionLabels, $targetTypeLabels): string {
    $who    = Html::encode($row['user_name'] ?? 'Система');
    $act    = Html::encode($actionLabels[$row['action']] ?? $row['action']);
    $type   = Html::encode($targetTypeLabels[$row['target_type']] ?? $row['target_type']);
    $label  = Html::encode($row['target_label'] ?? ('#' . $row['target_id']));

    // Если есть diff — вытащим первое изменение для превью
    $extra = '';
    if (!empty($row['changes'])) {
        $data = @json_decode($row['changes'], true);
        if (is_array($data) && !empty($data)) {
            $first = array_key_first($data);
            $chg   = $data[$first];
            if (is_array($chg) && array_key_exists('old', $chg) && array_key_exists('new', $chg)) {
                $oldVal = mb_substr((string)$chg['old'], 0, 40);
                $newVal = mb_substr((string)$chg['new'], 0, 40);
                $extra = ' — <span style="color:var(--admin-text-secondary)">' . Html::encode($first) . ': </span>'
                    . '<span style="color:#dc2626">' . Html::encode($oldVal) . '</span>'
                    . ' → <span style="color:#059669">' . Html::encode($newVal) . '</span>';
                if (count($data) > 1) {
                    $extra .= ' <span style="color:var(--admin-text-secondary)">и ещё ' . (count($data) - 1) . ' поля</span>';
                }
            }
        }
    }

    return "<strong>$who</strong> · $act · <em>$type</em> <strong>$label</strong>" . $extra;
};

/** Текущие параметры фильтра для передачи в CSV-ссылку */
$csvParams = array_merge(['/admin/activity-log/export-csv'], array_filter($f, fn($v) => $v !== '' && $v !== 0 && $v !== '0'));

?>

<style>
/* ── Activity Log ───────────────────────────────────── */
.al-wrap { max-width: none; }

/* Sticky filter bar */
.al-filter-bar {
    background: var(--admin-card-bg, #fff);
    border: 1px solid var(--admin-border, #e5e7eb);
    border-radius: var(--admin-radius, 8px);
    padding: 14px 16px;
    margin-bottom: 16px;
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    align-items: flex-end;
    position: sticky;
    top: 0;
    z-index: 10;
    box-shadow: 0 2px 8px rgba(0,0,0,.06);
}

/* Period chips */
.al-period-chips { display: flex; gap: 4px; flex-wrap: wrap; }
.al-chip {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 5px 12px;
    border-radius: 20px;
    border: 1.5px solid var(--admin-border, #e5e7eb);
    background: transparent;
    color: var(--admin-text, #374151);
    font-size: .8125rem; font-weight: 500;
    cursor: pointer;
    text-decoration: none;
    transition: all .15s;
}
.al-chip:hover  { border-color: var(--admin-primary, #2563eb); color: var(--admin-primary, #2563eb); }
.al-chip.active { background: var(--admin-primary, #2563eb); border-color: var(--admin-primary, #2563eb); color: #fff; }

/* Select / input mini */
.al-select, .al-input {
    height: 34px;
    border: 1.5px solid var(--admin-border, #e5e7eb);
    border-radius: 6px;
    padding: 0 10px;
    font-size: .8125rem;
    background: var(--admin-card-bg, #fff);
    color: var(--admin-text, #374151);
}
.al-select:focus, .al-input:focus {
    outline: none;
    border-color: var(--admin-primary, #2563eb);
    box-shadow: 0 0 0 3px rgba(37,99,235,.1);
}

/* Timeline table */
.al-timeline { width: 100%; border-collapse: collapse; }
.al-timeline tr + tr td { border-top: 1px solid var(--admin-border, #e5e7eb); }
.al-timeline td { padding: 10px 12px; vertical-align: top; }
.al-timeline tr:hover td { background: var(--admin-table-hover, #f9fafb); }

/* Action icon bubble */
.al-icon {
    width: 32px; height: 32px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 14px; flex-shrink: 0;
}

/* User pill */
.al-user-pill {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 3px 8px;
    background: var(--admin-bg, #f3f4f6);
    border-radius: 12px;
    font-size: .75rem; font-weight: 600;
    color: var(--admin-text, #374151);
    white-space: nowrap;
}

/* Action badge */
.al-action-badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: .70rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: .03em;
    white-space: nowrap;
}

/* Diff expand */
.al-diff-toggle {
    display: inline-flex; align-items: center; gap: 4px;
    font-size: .75rem; color: var(--admin-text-secondary, #6b7280);
    cursor: pointer; border: none; background: none; padding: 2px 4px;
    border-radius: 4px;
}
.al-diff-toggle:hover { background: var(--admin-bg, #f3f4f6); color: var(--admin-primary, #2563eb); }
.al-diff-panel {
    display: none;
    margin-top: 6px;
    padding: 8px 12px;
    background: var(--admin-bg, #f8fafc);
    border: 1px solid var(--admin-border, #e5e7eb);
    border-radius: 6px;
}
.al-diff-panel.open { display: block; }

/* Pagination */
.al-pager { display: flex; justify-content: center; padding: 16px; }
.al-pager .pagination { gap: 4px; }
.al-pager .page-link { border-radius: 6px !important; font-size: .8125rem; }

/* Header bar */
.al-header {
    display: flex; justify-content: space-between; align-items: center;
    margin-bottom: 16px;
}
.al-title { font-size: 1.25rem; font-weight: 700; color: var(--admin-text, #111827); margin: 0; display: flex; align-items: center; gap: 10px; }
.al-stat-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 12px; border-radius: 20px;
    background: var(--admin-bg, #f3f4f6);
    font-size: .8125rem; color: var(--admin-text-secondary, #6b7280);
}

/* Responsive */
@media (max-width: 768px) {
    .al-filter-bar { position: static; }
    .al-timeline .al-col-time,
    .al-timeline .al-col-source,
    .al-timeline .al-col-ip { display: none; }
}
</style>

<div class="al-wrap">

<!-- ── Header ─────────────────────────────────────────────────────────────── -->
<div class="al-header">
    <h1 class="al-title">
        <i class="bi bi-journal-text" style="color:var(--admin-primary,#2563eb)"></i>
        Журнал действий
        <span class="al-stat-badge"><i class="bi bi-list-ul"></i> <?= number_format($totalCount) ?> записей</span>
    </h1>
    <div style="display:flex;gap:8px;align-items:center">
        <?= Html::a('<i class="bi bi-download"></i> Экспорт CSV', $csvParams, [
            'class' => 'admin-btn admin-btn-secondary',
            'style' => 'font-size:.8125rem',
        ]) ?>
    </div>
</div>

<!-- ── Filter bar ──────────────────────────────────────────────────────────── -->
<form method="GET" id="alFilterForm">

<!-- Period chips -->
<div class="al-filter-bar">
    <div style="display:flex;flex-direction:column;gap:6px;width:100%">

        <div style="display:flex;flex-wrap:wrap;gap:8px;align-items:center">
            <span style="font-size:.75rem;color:var(--admin-text-secondary);font-weight:600;white-space:nowrap"><i class="bi bi-lightning-fill"></i> Период:</span>
            <div class="al-period-chips">
                <?php foreach ([
                    'today' => 'Сегодня',
                    'week'  => 'Неделя',
                    'month' => 'Месяц',
                    'all'   => 'Всё время',
                    'custom'=> 'Произвольный',
                ] as $pKey => $pLabel): ?>
                <a href="javascript:void(0)"
                   class="al-chip <?= $f['period'] === $pKey ? 'active' : '' ?>"
                   onclick="setAlPeriod('<?= $pKey ?>')"><?= $pLabel ?></a>
                <?php endforeach; ?>
            </div>

            <!-- Custom date range (hidden unless period=custom) -->
            <div id="alCustomDates" style="display:<?= $f['period'] === 'custom' ? 'flex' : 'none' ?>;gap:6px;align-items:center">
                <input type="date" name="start_date" class="al-input" value="<?= Html::encode($f['start_date']) ?>" title="Дата с" style="width:140px">
                <span style="color:var(--admin-text-secondary)">—</span>
                <input type="date" name="end_date" class="al-input" value="<?= Html::encode($f['end_date']) ?>" title="Дата по" style="width:140px">
            </div>

            <input type="hidden" id="alPeriodInput" name="period" value="<?= Html::encode($f['period']) ?>">
        </div>

        <div style="display:flex;flex-wrap:wrap;gap:8px;align-items:center">
            <!-- User -->
            <select name="user_id" class="al-select" style="width:180px">
                <option value="">— Все пользователи —</option>
                <?php foreach ($adminUsers as $u): ?>
                <option value="<?= $u['id'] ?>" <?= (int)$f['user_id'] === (int)$u['id'] ? 'selected' : '' ?>>
                    <?= Html::encode($u['username']) ?>
                </option>
                <?php endforeach; ?>
            </select>

            <!-- Target type -->
            <select name="target_type" class="al-select" style="width:160px">
                <option value="">— Все типы —</option>
                <?php foreach ($targetTypeLabels as $k => $v): ?>
                <option value="<?= Html::encode($k) ?>" <?= $f['target_type'] === $k ? 'selected' : '' ?>>
                    <?= Html::encode($v) ?>
                </option>
                <?php endforeach; ?>
            </select>

            <!-- Action -->
            <select name="action" class="al-select" style="width:160px">
                <option value="">— Все действия —</option>
                <?php foreach ($actionLabels as $k => $v): ?>
                <option value="<?= Html::encode($k) ?>" <?= $f['action'] === $k ? 'selected' : '' ?>>
                    <?= Html::encode($v) ?>
                </option>
                <?php endforeach; ?>
            </select>

            <!-- Source -->
            <select name="source" class="al-select" style="width:120px">
                <option value="">— Источник —</option>
                <?php foreach ($sources as $k => $v): ?>
                <option value="<?= Html::encode($k) ?>" <?= $f['source'] === $k ? 'selected' : '' ?>>
                    <?= Html::encode($v) ?>
                </option>
                <?php endforeach; ?>
            </select>

            <!-- Search -->
            <input type="text" name="search" class="al-input" value="<?= Html::encode($f['search']) ?>"
                   placeholder="Поиск по объекту..." style="width:200px">

            <button type="submit" class="admin-btn admin-btn-primary" style="height:34px;font-size:.8125rem">
                <i class="bi bi-search"></i> Найти
            </button>
            <?= Html::a('<i class="bi bi-x"></i> Сбросить', ['/admin/activity-log'], ['class' => 'admin-btn admin-btn-secondary', 'style' => 'height:34px;font-size:.8125rem']) ?>
        </div>

    </div>
</div>
</form>

<!-- ── Timeline table ─────────────────────────────────────────────────────── -->
<div class="admin-card">
    <div class="admin-card-body" style="padding:0">

    <?php if (empty($logs)): ?>
        <div style="padding:60px;text-align:center;color:var(--admin-text-secondary)">
            <i class="bi bi-journal-x" style="font-size:2.5rem;opacity:.4"></i>
            <p style="margin-top:12px">Записей не найдено</p>
            <?php if (array_filter($f, fn($v) => $v !== '' && $v !== 0 && $v !== '0' && $v !== 'today')): ?>
            <?= Html::a('Сбросить фильтры', ['/admin/activity-log'], ['class' => 'admin-btn admin-btn-secondary']) ?>
            <?php endif; ?>
        </div>
    <?php else: ?>

    <div style="overflow-x:auto">
    <table class="al-timeline">
        <thead style="background:var(--admin-bg,#f9fafb);font-size:.75rem;color:var(--admin-text-secondary);text-transform:uppercase;letter-spacing:.04em">
            <tr>
                <th style="padding:8px 12px;font-weight:600;width:36px"></th>
                <th style="padding:8px 12px;font-weight:600;width:120px" class="al-col-time">Время</th>
                <th style="padding:8px 12px;font-weight:600;width:110px">Пользователь</th>
                <th style="padding:8px 12px;font-weight:600;width:90px">Действие</th>
                <th style="padding:8px 12px;font-weight:600">Описание</th>
                <th style="padding:8px 12px;font-weight:600;width:70px" class="al-col-source">Источник</th>
                <th style="padding:8px 12px;font-weight:600;width:80px" class="al-col-ip">IP</th>
                <th style="padding:8px 12px;font-weight:600;width:80px"></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($logs as $i => $row):
            $meta    = $actionMeta[$row['action']] ?? ['icon' => 'bi-activity', 'color' => '#6b7280', 'bg' => '#f3f4f6'];
            $hasDiff = !empty($row['changes']);
            $url     = $makeTargetUrl($row);
            $diffId  = 'al-diff-' . $i;
        ?>
        <tr>
            <!-- Icon -->
            <td style="padding:10px 12px">
                <div class="al-icon" style="background:<?= $meta['bg'] ?>;color:<?= $meta['color'] ?>">
                    <i class="bi <?= $meta['icon'] ?>"></i>
                </div>
            </td>

            <!-- Time -->
            <td class="al-col-time" style="white-space:nowrap">
                <span title="<?= date('d.m.Y H:i:s', $row['created_at']) ?>"
                      style="font-size:.8rem;color:var(--admin-text-secondary)">
                    <?= $relTime($row['created_at']) ?>
                </span>
            </td>

            <!-- User -->
            <td>
                <?php if ($row['user_name']): ?>
                <span class="al-user-pill">
                    <i class="bi bi-person-fill" style="font-size:.7rem;opacity:.7"></i>
                    <?= Html::encode(mb_substr($row['user_name'], 0, 16)) ?>
                </span>
                <?php if ($row['user_role']): ?>
                <div style="font-size:.7rem;color:var(--admin-text-secondary);margin-top:2px;padding-left:4px">
                    <?= Html::encode($row['user_role']) ?>
                </div>
                <?php endif; ?>
                <?php else: ?>
                <span style="color:var(--admin-text-secondary);font-size:.8rem">—</span>
                <?php endif; ?>
            </td>

            <!-- Action badge -->
            <td>
                <span class="al-action-badge" style="background:<?= $meta['bg'] ?>;color:<?= $meta['color'] ?>">
                    <?= Html::encode($actionLabels[$row['action']] ?? $row['action']) ?>
                </span>
            </td>

            <!-- Description + diff -->
            <td>
                <div style="font-size:.8125rem;line-height:1.5">
                    <?= $buildDescription($row) ?>
                </div>
                <?php if ($hasDiff): ?>
                <div style="margin-top:4px">
                    <button type="button" class="al-diff-toggle" onclick="alToggleDiff('<?= $diffId ?>', this)">
                        <i class="bi bi-code-slash"></i> показать изменения
                    </button>
                    <div id="<?= $diffId ?>" class="al-diff-panel">
                        <?= $renderDiff((string)$row['changes']) ?>
                    </div>
                </div>
                <?php endif; ?>
            </td>

            <!-- Source -->
            <td class="al-col-source">
                <span style="font-size:.75rem;color:var(--admin-text-secondary);font-family:monospace">
                    <?= Html::encode($row['source']) ?>
                </span>
            </td>

            <!-- IP -->
            <td class="al-col-ip">
                <span style="font-size:.75rem;color:var(--admin-text-secondary);font-family:monospace">
                    <?= Html::encode($row['ip'] ?? '—') ?>
                </span>
            </td>

            <!-- Open link -->
            <td style="text-align:right">
                <?php if ($url): ?>
                <?= Html::a('<i class="bi bi-box-arrow-up-right"></i> Открыть', $url, [
                    'class'  => 'admin-btn admin-btn-secondary',
                    'style'  => 'font-size:.75rem;padding:3px 8px',
                    'target' => '_blank',
                ]) ?>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>

    <!-- Footer: count + pagination -->
    <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 16px;border-top:1px solid var(--admin-border,#e5e7eb)">
        <span style="font-size:.8125rem;color:var(--admin-text-secondary)">
            Показано <?= count($logs) ?> из <?= number_format($totalCount) ?> записей
        </span>
        <?php if ($pagination->pageCount > 1): ?>
        <div class="al-pager">
            <?= LinkPager::widget([
                'pagination'  => $pagination,
                'options'     => ['class' => 'pagination pagination-sm mb-0'],
                'linkOptions' => ['class' => 'page-link'],
                'linkContainerOptions' => ['class' => 'page-item'],
                'activePageCssClass'   => 'active',
                'disabledPageCssClass' => 'disabled',
                'maxButtonCount' => 7,
                'firstPageLabel' => '«',
                'lastPageLabel'  => '»',
                'prevPageLabel'  => '‹',
                'nextPageLabel'  => '›',
            ]) ?>
        </div>
        <?php endif; ?>
    </div>

    <?php endif; ?>
    </div>
</div>

</div><!-- /.al-wrap -->

<script>
// Period chips
function setAlPeriod(period) {
    document.getElementById('alPeriodInput').value = period;
    document.getElementById('alCustomDates').style.display = period === 'custom' ? 'flex' : 'none';
    if (period !== 'custom') {
        document.getElementById('alFilterForm').submit();
    }
}

// Toggle diff panel
function alToggleDiff(id, btn) {
    const panel = document.getElementById(id);
    if (!panel) return;
    panel.classList.toggle('open');
    const open = panel.classList.contains('open');
    btn.innerHTML = open
        ? '<i class="bi bi-chevron-up"></i> скрыть изменения'
        : '<i class="bi bi-code-slash"></i> показать изменения';
}
</script>
