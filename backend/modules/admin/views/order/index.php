<?php

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var string|null $filterStatus */
/** @var string|null $filterLogist */
/** @var string|null $filterSearch */
/** @var string|null $filterProcessed */
/** @var string|null $filterDateFrom */
/** @var string|null $filterDateTo */
/** @var array $statusCounts */
/** @var int $totalCount */
/** @var array $monthlySummary */
/** @var array $kpiSummary */
/** @var array $pipelineStats */
/** @var int $processedCount */
/** @var int $shippedCount */
/** @var int $customsClearedCount */
/** @var int $pageSize */
/** @var array $pageSizeOptions */
/** @var \app\backend\modules\admin\models\User[] $logists */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Управление заказами';

$user        = Yii::$app->user->identity;
$orders      = $dataProvider->getModels();
$pagination  = $dataProvider->pagination;
$statuses    = Yii::$app->settings->getStatuses();
$formatter   = Yii::$app->formatter;

$logistMap = [];
foreach ($logists as $logist) {
    $logistMap[$logist->id] = $logist->username;
}

// Duplicate china track map (passed from controller)
$dupMap = $dupMap ?? [];

// Sort helpers
$currentSort = Yii::$app->request->get('sort', '');
$sortIcon = function(string $col) use ($currentSort): string {
    if ($currentSort === $col)        return ' <span style="color:var(--admin-primary,#2563eb);font-size:.65rem">▲</span>';
    if ($currentSort === '-' . $col)  return ' <span style="color:var(--admin-primary,#2563eb);font-size:.65rem">▼</span>';
    return ' <span style="color:#d1d5db;font-size:.6rem">⇅</span>';
};

$this->params['breadcrumbs'][] = ['label' => 'Заказы', 'url' => ['/admin/order/index']];

// Kanban column definitions
$kanbanColumns = [
    'new'                    => 'Новый',
    'paid'                   => 'Оплачен',
    'confirmed_and_paid'     => 'Подтвержден и оплачен',
    'ordered'                => 'Заказано',
    'awaiting_warehouse'     => 'Ожидается на складе',
    'international_delivery' => 'В международной доставке',
    'at_warehouse'           => 'На складе',
    'local_delivery'         => 'В доставке',
    'delivered'              => 'Выдан',
    'canceled'               => 'Отменен',
];
foreach ($statuses as $k => $v) {
    if (!isset($kanbanColumns[$k])) $kanbanColumns[$k] = $v;
}

// Group orders by status for Kanban
$kanbanGroups = [];
foreach ($kanbanColumns as $k => $v) $kanbanGroups[$k] = [];
foreach ($orders as $order) {
    $s = $order->status;
    if (!isset($kanbanGroups[$s])) $kanbanGroups[$s] = [];
    $kanbanGroups[$s][] = $order;
}

// All filter params (read from GET)
$filterDelivery   = Yii::$app->request->get('delivery_method', '');
$filterPayment    = Yii::$app->request->get('payment_method',  '');
$filterSource     = Yii::$app->request->get('source',          '');
$filterCity       = Yii::$app->request->get('city',            '');
$filterChinaTrack = Yii::$app->request->get('china_track',     '');
$filterDpTrack    = Yii::$app->request->get('dp_track',        '');
$filterAmountFrom = Yii::$app->request->get('amount_from',     '');
$filterAmountTo   = Yii::$app->request->get('amount_to',       '');
$row2Active       = ($filterDelivery || $filterPayment || $filterSource || $filterCity ||
                     $filterChinaTrack || $filterDpTrack || $filterAmountFrom || $filterAmountTo);
$row2ActiveCount  = count(array_filter([$filterDelivery,$filterPayment,$filterSource,$filterCity,
                                        $filterChinaTrack,$filterDpTrack,$filterAmountFrom,$filterAmountTo]));

$showingFrom = $totalCount ? ($pagination->page * $pagination->pageSize) + 1 : 0;
$showingTo   = $totalCount ? $showingFrom + count($orders) - 1 : 0;

// Z11: build an augmented statuses map that includes DB statuses not in getStatuses()
// so that sum of tab counts always equals the "Все" total
$extraStatuses = [];
foreach (($statusCounts ?? []) as $sk => $sc) {
    if ($sc > 0 && !isset($statuses[$sk])) {
        $extraStatuses[$sk] = $sk; // use key as label fallback
    }
}

// Funnel dot colors
$funnelDots = [
    'new'                    => '#6b7280',
    'created'                => '#6b7280',
    'paid'                   => '#059669',
    'confirmed_and_paid'     => '#059669',
    'ordered'                => '#d97706',
    'processing'             => '#d97706',
    'awaiting_warehouse'     => '#7c3aed',
    'international_delivery' => '#2563eb',
    'at_warehouse'           => '#7c3aed',
    'local_delivery'         => '#2563eb',
    'shipped'                => '#2563eb',
    'in_transit'             => '#2563eb',
    'delivered'              => '#16a34a',
    'cancelled'              => '#dc2626',
    'canceled'               => '#dc2626',
    'returned'               => '#dc2626',
];

// Status pill styles (Task 1)
$statusPills = [
    'new'                    => ['bg' => '#eff6ff', 'color' => '#2563eb'],
    'created'                => ['bg' => '#f3f4f6', 'color' => '#6b7280'],
    'paid'                   => ['bg' => '#f0fdf4', 'color' => '#16a34a'],
    'confirmed_and_paid'     => ['bg' => '#faf5ff', 'color' => '#7c3aed'],
    'ordered'                => ['bg' => '#ecfeff', 'color' => '#0891b2'],
    'processing'             => ['bg' => '#fffbeb', 'color' => '#d97706'],
    'awaiting_warehouse'     => ['bg' => '#faf5ff', 'color' => '#7c3aed'],
    'international_delivery' => ['bg' => '#eff6ff', 'color' => '#2563eb'],
    'at_warehouse'           => ['bg' => '#f5f3ff', 'color' => '#7c3aed'],
    'local_delivery'         => ['bg' => '#eff6ff', 'color' => '#2563eb'],
    'shipped'                => ['bg' => '#faf5ff', 'color' => '#9333ea'],
    'in_transit'             => ['bg' => '#eff6ff', 'color' => '#2563eb'],
    'delivered'              => ['bg' => '#ecfdf5', 'color' => '#059669'],
    'cancelled'              => ['bg' => '#fef2f2', 'color' => '#dc2626'],
    'canceled'               => ['bg' => '#fef2f2', 'color' => '#dc2626'],
    'returned'               => ['bg' => '#fef2f2', 'color' => '#dc2626'],
    'imported'               => ['bg' => '#f3f4f6', 'color' => '#6b7280'],
];

$pmLabels = ['cash' => 'Наличные', 'card' => 'Карта', 'transfer' => 'Перевод', 'erip' => 'ЕРИП'];
$dmLabels = ['europochta' => 'Европочта', 'belpochta' => 'Белпочта', 'cdek' => 'СДЭК', 'courier' => 'Курьер'];

// Z12: load status colors from DB so table badges and tab dots use the same source
$statusColorMap = [];
try {
    $statusRows = \app\backend\modules\checkout\models\OrderStatus::find()
        ->select(['key', 'color'])
        ->asArray()->all();
    foreach ($statusRows as $sr) {
        if (!empty($sr['color'])) {
            $statusColorMap[$sr['key']] = $sr['color'];
        }
    }
} catch (\Exception $e) { $statusColorMap = []; }

// Helper: convert Bootstrap color name to hex for inline styles
$colorToHex = function(string $c): string {
    $map = [
        'primary' => '#2563eb', 'secondary' => '#6b7280', 'success' => '#16a34a',
        'danger'  => '#dc2626', 'warning'   => '#d97706', 'info'    => '#0891b2',
        'dark'    => '#374151', 'light'     => '#6b7280',
        'purple'  => '#7c3aed', 'violet'    => '#7c3aed',
    ];
    // If already a hex or rgb value, return as-is
    if (str_starts_with($c, '#') || str_starts_with($c, 'rgb')) return $c;
    return $map[strtolower(trim($c))] ?? ('#' . ltrim($c, '#'));
};
?>

<style>
/* === Funnel === */
.order-funnel{display:flex;gap:6px;flex-wrap:wrap;align-items:center;padding:12px 16px;background:var(--admin-surface,#fff);border-radius:12px;box-shadow:0 1px 4px rgba(0,0,0,.06);margin-bottom:12px}
.funnel-pill{display:inline-flex;align-items:center;gap:5px;padding:5px 12px;border-radius:20px;background:var(--admin-surface-hover,#f3f4f6);color:var(--admin-text-secondary,#6b7280);font-size:.8125rem;font-weight:500;text-decoration:none;transition:all .18s ease;white-space:nowrap;border:1.5px solid transparent}
.funnel-pill:hover{transform:translateY(-2px);box-shadow:0 4px 12px rgba(0,0,0,.1);color:var(--admin-text-primary,#111);text-decoration:none}
.funnel-pill--active{background:var(--admin-primary,#2563eb)!important;color:#fff!important;border-color:var(--admin-primary,#2563eb);font-weight:700}
.funnel-pill--active .funnel-pill-count{background:rgba(255,255,255,.25);color:#fff}
.funnel-pill-dot{width:7px;height:7px;border-radius:50%;flex-shrink:0}
.funnel-pill-count{font-size:.75rem;font-weight:700;background:rgba(0,0,0,.07);color:inherit;padding:1px 6px;border-radius:10px;min-width:20px;text-align:center}
/* === Filter bar === */
.filter-wrap{margin-bottom:14px}
.compact-filter-bar{display:flex;gap:8px;align-items:center;flex-wrap:wrap;padding:10px 16px;background:var(--admin-surface,#fff)}
.filter-row1{border-radius:12px;box-shadow:0 1px 4px rgba(0,0,0,.06)}
.filter-row1.has-row2{border-radius:12px 12px 0 0}
.filter-row2{border-radius:0 0 12px 12px;border-top:1px solid var(--admin-border,#e5e7eb);box-shadow:0 2px 4px rgba(0,0,0,.05)}
.compact-filter-input,.compact-filter-select{height:32px;border:1.5px solid var(--admin-border,#e5e7eb);border-radius:8px;padding:0 10px;font-size:.8125rem;color:var(--admin-text-primary,#111);background:var(--admin-surface,#fff);outline:none;transition:border-color .15s}
.compact-filter-input:focus,.compact-filter-select:focus{border-color:var(--admin-primary,#2563eb)}
.compact-filter-input{flex:1;min-width:160px;max-width:240px}
.compact-filter-input[type=date]{flex:0 0 auto;min-width:130px;max-width:150px}
.compact-filter-input[type=number]{flex:0 0 auto;min-width:88px;max-width:110px}
.compact-filter-select{flex:0 0 auto;min-width:110px}
.compact-filter-btn{height:32px;padding:0 12px;border-radius:8px;font-size:.8125rem;font-weight:600;cursor:pointer;border:none;text-decoration:none;display:inline-flex;align-items:center;gap:5px;transition:all .15s;white-space:nowrap;background:var(--admin-surface-hover,#f3f4f6);color:var(--admin-text-secondary,#6b7280)}
.compact-filter-btn:hover{background:#e5e7eb;color:#374151;text-decoration:none}
.compact-filter-btn--apply{background:var(--admin-primary,#2563eb);color:#fff}
.compact-filter-btn--apply:hover{background:#1d4ed8;color:#fff}
.compact-filter-btn--reset:hover{background:#fee2e2;color:#dc2626}
.compact-filter-btn--expand.is-active{background:#eff6ff;color:var(--admin-primary,#2563eb);border:1.5px solid #bfdbfe}
/* === Column selector (Task 2) === */
.col-selector-wrap{position:relative;flex-shrink:0}
.col-selector-dropdown{position:absolute;right:0;top:calc(100% + 6px);background:var(--admin-surface,#fff);border:1.5px solid var(--admin-border,#e5e7eb);border-radius:10px;padding:12px;z-index:300;min-width:190px;max-height:370px;overflow-y:auto;box-shadow:0 8px 24px rgba(0,0,0,.12)}
.col-selector-item{display:flex;align-items:center;gap:8px;padding:4px 2px;font-size:.8125rem;cursor:pointer;color:var(--admin-text-primary,#111);user-select:none;border-radius:4px}
.col-selector-item:hover{background:var(--admin-surface-hover,#f3f4f6)}
.col-selector-item input{width:15px;height:15px;cursor:pointer;accent-color:var(--admin-primary,#2563eb);flex-shrink:0}
/* === Table status pill (Task 1) === */
.status-pill{display:inline-block;padding:3px 9px;border-radius:20px;font-size:11px;font-weight:700;white-space:nowrap;line-height:1.6}
/* === Track number badge === */
.track-badge{font-size:.7rem;font-family:monospace;background:#eff6ff;color:var(--admin-primary,#2563eb);padding:2px 6px;border-radius:4px;display:inline-block;max-width:110px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;vertical-align:middle;cursor:default}
/* === Duplicate track badge === */
.dup-badge{font-size:.65rem;font-weight:700;background:#fef2f2;color:#dc2626;padding:1px 5px;border-radius:4px;margin-left:3px;vertical-align:middle;cursor:default;border:1px solid #fecaca}
/* === Sortable column headers === */
th[data-sort]{cursor:pointer;user-select:none;white-space:nowrap}
th[data-sort]:hover{background:var(--admin-surface-hover,#f3f4f6)!important}
/* === Clickable row === */
#ordersTable tbody tr{cursor:pointer;transition:background .1s}
#ordersTable tbody tr:hover{background:var(--admin-surface-hover,#f5f7fa)!important}
/* === Infinite scroll === */
#scroll-sentinel{height:1px;margin-top:8px}
#scroll-loading{display:none;justify-content:center;align-items:center;padding:16px;gap:8px;color:var(--admin-text-secondary,#6b7280);font-size:.875rem}
@keyframes spin{to{transform:rotate(360deg)}}
.scroll-spinner{width:18px;height:18px;border:2px solid #e5e7eb;border-top-color:var(--admin-primary,#2563eb);border-radius:50%;animation:spin .7s linear infinite}
/* === Sticky table header (Task 6) === */
#ordersTable thead th{position:sticky;top:0;z-index:10;background:var(--admin-surface,#fff);box-shadow:0 1px 0 var(--admin-border,#e5e7eb)}
/* === Quick date preset buttons === */
.preset-bar{display:flex;gap:6px;align-items:center;padding:8px 16px 0;flex-wrap:wrap}
.preset-btn{height:28px;padding:0 11px;border-radius:6px;font-size:.78rem;font-weight:600;cursor:pointer;border:1.5px solid var(--admin-border,#e5e7eb);background:var(--admin-surface,#fff);color:var(--admin-text-secondary,#6b7280);transition:all .15s;white-space:nowrap;display:inline-flex;align-items:center;gap:4px}
.preset-btn:hover{border-color:var(--admin-primary,#2563eb);color:var(--admin-primary,#2563eb);background:#eff6ff}
.preset-btn.is-active{background:var(--admin-primary,#2563eb);color:#fff;border-color:var(--admin-primary,#2563eb)}
.preset-bar-label{font-size:.75rem;color:var(--admin-text-secondary,#6b7280);font-weight:500;margin-right:2px}
/* === Inline cell editing === */
.tbl-editable{cursor:text;border-radius:4px;padding:2px 4px;transition:background .1s}
.tbl-editable:hover{background:var(--admin-surface-hover,#f3f4f6);outline:1px dashed var(--admin-border,#d1d5db)}
.tbl-editable input,.tbl-editable select{width:100%;font-size:.8125rem;padding:2px 5px;border:1.5px solid var(--admin-primary,#2563eb);border-radius:4px;background:#fff;outline:none;min-width:80px}
</style>

<!-- Order Funnel -->
<div class="order-funnel">
    <a href="<?= Url::to(['/admin/order']) ?>" class="funnel-pill <?= empty($filterStatus) ? 'funnel-pill--active' : '' ?>">
        <span class="funnel-pill-label">Все</span>
        <span class="funnel-pill-count"><?= $totalCount ?></span>
    </a>
    <?php foreach ($statuses as $sKey => $sLabel):
        $cnt   = $statusCounts[$sKey] ?? 0;
        $isAct = $filterStatus === $sKey;
        // Z12: prefer DB color, fall back to hardcoded map
        $dbDot = !empty($statusColorMap[$sKey]) ? $colorToHex($statusColorMap[$sKey]) : null;
        $dot   = $dbDot ?? ($funnelDots[$sKey] ?? '#6b7280');
    ?>
    <a href="<?= Url::to(['/admin/order', 'status' => $sKey]) ?>" class="funnel-pill <?= $isAct ? 'funnel-pill--active' : '' ?>">
        <span class="funnel-pill-dot" style="background:<?= $dot ?>"></span>
        <span class="funnel-pill-label"><?= Html::encode($sLabel) ?></span>
        <span class="funnel-pill-count"><?= $cnt ?></span>
    </a>
    <?php endforeach; ?>
    <?php foreach ($extraStatuses as $sKey => $sLabel):
        $cnt   = $statusCounts[$sKey] ?? 0;
        if ($cnt === 0) continue;
        $isAct = $filterStatus === $sKey;
        $dbDot = !empty($statusColorMap[$sKey]) ? $colorToHex($statusColorMap[$sKey]) : null;
        $dot   = $dbDot ?? ($funnelDots[$sKey] ?? '#6b7280');
    ?>
    <a href="<?= Url::to(['/admin/order', 'status' => $sKey]) ?>" class="funnel-pill <?= $isAct ? 'funnel-pill--active' : '' ?>">
        <span class="funnel-pill-dot" style="background:<?= $dot ?>"></span>
        <span class="funnel-pill-label"><?= Html::encode($sLabel) ?></span>
        <span class="funnel-pill-count"><?= $cnt ?></span>
    </a>
    <?php endforeach; ?>
</div>

<!-- Filter Bar (Tasks 2 & 3) -->
<form method="get" id="filterForm" class="filter-wrap">
    <!-- Quick date presets -->
    <?php
        $today     = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $weekStart = date('Y-m-d', strtotime('monday this week'));
        $weekEnd   = date('Y-m-d', strtotime('sunday this week'));
        $monthStart = date('Y-m-01');
        $monthEnd   = date('Y-m-t');
        // Detect which preset is currently active
        $activePreset = '';
        if ($filterDateFrom === $today     && $filterDateTo === $today)      $activePreset = 'today';
        if ($filterDateFrom === $yesterday && $filterDateTo === $yesterday)  $activePreset = 'yesterday';
        if ($filterDateFrom === $weekStart && $filterDateTo === $weekEnd)    $activePreset = 'week';
        if ($filterDateFrom === $monthStart && $filterDateTo === $monthEnd)  $activePreset = 'month';
    ?>
    <div class="preset-bar">
        <span class="preset-bar-label"><i class="bi bi-lightning-fill"></i> Период:</span>
        <button type="button" class="preset-btn <?= $activePreset === 'today'     ? 'is-active' : '' ?>"
                onclick="applyDatePreset('<?= $today ?>','<?= $today ?>')">Сегодня</button>
        <button type="button" class="preset-btn <?= $activePreset === 'yesterday' ? 'is-active' : '' ?>"
                onclick="applyDatePreset('<?= $yesterday ?>','<?= $yesterday ?>')">Вчера</button>
        <button type="button" class="preset-btn <?= $activePreset === 'week'      ? 'is-active' : '' ?>"
                onclick="applyDatePreset('<?= $weekStart ?>','<?= $weekEnd ?>')">Эта неделя</button>
        <button type="button" class="preset-btn <?= $activePreset === 'month'     ? 'is-active' : '' ?>"
                onclick="applyDatePreset('<?= $monthStart ?>','<?= $monthEnd ?>')">Этот месяц</button>
        <?php if ($filterDateFrom || $filterDateTo): ?>
        <button type="button" class="preset-btn" style="margin-left:4px;border-color:#fecaca;color:#dc2626"
                onclick="applyDatePreset('','')"><i class="bi bi-x"></i> Сбросить период</button>
        <?php endif; ?>
    </div>
    <!-- Row 1: always visible -->
    <div class="compact-filter-bar filter-row1 <?= $row2Active ? 'has-row2' : '' ?>">
        <input type="hidden" name="status" value="<?= Html::encode($filterStatus) ?>">
        <input type="text" name="search" class="compact-filter-input"
               placeholder="Номер, клиент, телефон…"
               value="<?= Html::encode($filterSearch) ?>">
        <input type="date" name="date_from" class="compact-filter-input"
               value="<?= Html::encode($filterDateFrom) ?>" title="Дата от">
        <input type="date" name="date_to"   class="compact-filter-input"
               value="<?= Html::encode($filterDateTo) ?>"   title="Дата до">
        <!-- Expand row 2 -->
        <button type="button" id="filterExpandBtn"
                class="compact-filter-btn compact-filter-btn--expand <?= $row2Active ? 'is-active' : '' ?>"
                onclick="toggleFilterRow2()">
            <i class="bi bi-sliders"></i> Ещё фильтры
            <?php if ($row2ActiveCount > 0): ?>
            <span style="background:var(--admin-primary,#2563eb);color:#fff;border-radius:10px;padding:1px 6px;font-size:10px;font-weight:700"><?= $row2ActiveCount ?></span>
            <?php endif; ?>
        </button>
        <button type="submit" class="compact-filter-btn compact-filter-btn--apply">
            <i class="bi bi-search"></i> Найти
        </button>
        <a href="<?= Url::to(['/admin/order']) ?>" class="compact-filter-btn compact-filter-btn--reset">
            <i class="bi bi-x-lg"></i> Сбросить
        </a>
    </div>
    <!-- Row 2: expandable (Task 3) -->
    <div id="filterRow2" class="compact-filter-bar filter-row2"
         style="<?= $row2Active ? 'display:flex' : 'display:none' ?>">
        <select name="delivery_method" class="compact-filter-select">
            <option value="">Доставка ▾</option>
            <option value="europochta" <?= $filterDelivery === 'europochta' ? 'selected' : '' ?>>Европочта</option>
            <option value="belpochta"  <?= $filterDelivery === 'belpochta'  ? 'selected' : '' ?>>Белпочта</option>
            <option value="cdek"       <?= $filterDelivery === 'cdek'       ? 'selected' : '' ?>>СДЭК</option>
            <option value="courier"    <?= $filterDelivery === 'courier'    ? 'selected' : '' ?>>Курьер</option>
        </select>
        <select name="payment_method" class="compact-filter-select">
            <option value="">Оплата ▾</option>
            <option value="cash"     <?= $filterPayment === 'cash'     ? 'selected' : '' ?>>Наличные</option>
            <option value="card"     <?= $filterPayment === 'card'     ? 'selected' : '' ?>>Карта</option>
            <option value="transfer" <?= $filterPayment === 'transfer' ? 'selected' : '' ?>>Перевод</option>
            <option value="erip"     <?= $filterPayment === 'erip'     ? 'selected' : '' ?>>ЕРИП</option>
        </select>
        <select name="source" class="compact-filter-select">
            <option value="">Источник ▾</option>
            <option value="website"   <?= $filterSource === 'website'   ? 'selected' : '' ?>>Сайт</option>
            <option value="telegram"  <?= $filterSource === 'telegram'  ? 'selected' : '' ?>>Telegram</option>
            <option value="instagram" <?= $filterSource === 'instagram' ? 'selected' : '' ?>>Instagram</option>
            <option value="manual"    <?= $filterSource === 'manual'    ? 'selected' : '' ?>>Ручной</option>
            <option value="import"    <?= $filterSource === 'import'    ? 'selected' : '' ?>>Импорт</option>
        </select>
        <input type="text"   name="city"        class="compact-filter-input" style="min-width:100px;max-width:130px"
               placeholder="Город" value="<?= Html::encode($filterCity) ?>">
        <input type="text"   name="china_track" class="compact-filter-input" style="min-width:110px;max-width:150px"
               placeholder="Трек Китай" value="<?= Html::encode($filterChinaTrack) ?>">
        <input type="text"   name="dp_track"    class="compact-filter-input" style="min-width:100px;max-width:140px"
               placeholder="Трек ДП" value="<?= Html::encode($filterDpTrack) ?>">
        <input type="number" name="amount_from" class="compact-filter-input"
               placeholder="Сумма от" value="<?= Html::encode($filterAmountFrom) ?>" step="0.01" min="0">
        <input type="number" name="amount_to"   class="compact-filter-input"
               placeholder="Сумма до" value="<?= Html::encode($filterAmountTo) ?>"   step="0.01" min="0">
    </div>
</form>

<!-- Bulk Actions Toolbar -->
<div class="bulk-toolbar" id="bulkToolbar">
    <label><i class="bi bi-check2-square"></i> Выбрано: <span id="selectedCount">0</span></label>
    <select id="bulkActionSelect" class="form-control" style="width:auto;">
        <option value="">— Действие —</option>
        <option value="change_status">Сменить статус</option>
        <?php if (!$user->isLogist()): ?>
        <option value="assign_logist">Назначить логиста</option>
        <?php endif; ?>
        <option value="export_csv">Выгрузить Excel</option>
    </select>
    <select id="bulkStatusExtra" class="form-control" style="width:auto;display:none;">
        <option value="">— Выберите статус —</option>
        <?php foreach ($statuses as $sk => $sl): ?>
            <option value="<?= $sk ?>"><?= Html::encode($sl) ?></option>
        <?php endforeach; ?>
    </select>
    <select id="bulkLogistExtra" class="form-control" style="width:auto;display:none;">
        <option value="">— Выберите логиста —</option>
        <?php foreach ($logists as $l): ?>
            <option value="<?= $l->id ?>"><?= Html::encode($l->username) ?></option>
        <?php endforeach; ?>
    </select>
    <button class="admin-btn admin-btn-primary" id="bulkApplyBtn" onclick="applyBulkAction()">
        <i class="bi bi-check-circle"></i> Применить к выбранным
    </button>
    <button class="admin-btn admin-btn-secondary" onclick="clearSelection()">Снять выделение</button>
</div>

<!-- View toggle card -->
<div class="admin-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.25rem;flex-wrap:wrap;gap:.75rem;">
        <h2 class="admin-card-title" style="margin:0;"><i class="bi bi-cart3"></i> Список заказов
            <span style="font-size:.8125rem;font-weight:400;color:var(--admin-text-secondary,#6b7280);margin-left:8px"><?= $totalCount ?></span>
        </h2>
        <div style="display:flex;align-items:center;gap:.75rem;flex-wrap:wrap;">
            <!-- Column toggle dropdown -->
            <div style="position:relative" id="colToggleWrap">
                <button class="compact-filter-btn" onclick="toggleColDropdown()" style="gap:5px">
                    <i class="bi bi-layout-three-columns"></i> Столбцы
                </button>
                <div id="colDropdown" style="display:none;position:absolute;right:0;top:calc(100% + 4px);background:#fff;border:1px solid var(--admin-border,#e5e7eb);border-radius:10px;box-shadow:0 4px 16px rgba(0,0,0,.1);z-index:100;min-width:180px;padding:8px 0">
                    <?php
                    $toggleCols = [
                        'email'       => 'Email',
                        'item'        => 'Товар',
                        'status'      => 'Статус',
                        'amount'      => 'Сумма',
                        'payment'     => 'Оплата',
                        'delivery'    => 'Доставка',
                        'china_track' => 'Трек Китай',
                        'dp_track'    => 'Трек ДП',
                        'local_track' => 'Трек РБ',
                        'dp_status'   => 'Статус ДП',
                        'city'        => 'Город',
                        'logist'      => 'Логист',
                        'source'      => 'Источник',
                        'comment'     => 'Комментарий',
                    ];
                    foreach ($toggleCols as $col => $label):
                    ?>
                    <label style="display:flex;align-items:center;gap:8px;padding:5px 14px;cursor:pointer;font-size:.8125rem;font-weight:500;color:var(--admin-text-primary,#111);white-space:nowrap" onmousedown="event.preventDefault()">
                        <input type="checkbox" data-col-toggle="<?= $col ?>" onchange="toggleColumn('<?= $col ?>', this.checked)" style="cursor:pointer">
                        <?= Html::encode($label) ?>
                    </label>
                    <?php endforeach; ?>
                    <div style="border-top:1px solid var(--admin-border,#f3f4f6);margin:6px 0;padding:4px 14px">
                        <button onclick="resetColumns()" style="background:none;border:none;font-size:.75rem;color:var(--admin-primary,#2563eb);cursor:pointer;padding:0">Сбросить к умолчанию</button>
                    </div>
                </div>
            </div>
            <div class="view-toggle">
                <button class="view-toggle-btn view-toggle-btn--active" id="btnTable" data-view="table" onclick="switchView('table')">
                    <i class="bi bi-table"></i> Таблица
                </button>
                <button class="view-toggle-btn" id="btnKanban" data-view="kanban" onclick="switchView('kanban')">
                    <i class="bi bi-kanban"></i> Канбан
                </button>
            </div>
            <select id="pageSizeSelect" onchange="changePageSize(this.value)" class="form-control" style="width:auto;">
                <?php foreach ($pageSizeOptions as $size): ?>
                    <option value="<?= $size ?>" <?= $pageSize === $size ? 'selected' : '' ?>><?= $size ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <!-- TABLE VIEW -->
    <div id="tableView">
        <div style="overflow-x:auto;">
            <table class="admin-table" id="ordersTable" style="font-size:.8125rem">
                <thead>
                    <tr>
                        <th style="width:32px;padding:8px 6px"><input type="checkbox" id="selectAll"></th>
                        <th data-sort="created_at" onclick="sortBy('created_at')" title="Сортировать по дате">№ / Дата <?= $sortIcon('created_at') ?></th>
                        <th data-sort="client_name" onclick="sortBy('client_name')" title="Сортировать по имени">Клиент <?= $sortIcon('client_name') ?></th>
                        <th>Телефон</th>
                        <th data-col="email">Email</th>
                        <th data-col="item">Товар</th>
                        <th data-col="status" data-sort="status" onclick="sortBy('status')" title="Сортировать по статусу">Статус <?= $sortIcon('status') ?></th>
                        <th data-col="amount" data-sort="total_amount" onclick="sortBy('total_amount')" title="Сортировать по сумме" style="white-space:nowrap">Сумма <?= $sortIcon('total_amount') ?></th>
                        <th data-col="payment">Оплата</th>
                        <th data-col="delivery">Доставка</th>
                        <th data-col="china_track" style="white-space:nowrap">Трек Китай</th>
                        <th data-col="dp_track" style="white-space:nowrap">Трек ДП</th>
                        <th data-col="local_track" style="white-space:nowrap">Трек РБ</th>
                        <th data-col="dp_status" style="white-space:nowrap">Статус ДП</th>
                        <th data-col="city">Город</th>
                        <th data-col="logist">Логист</th>
                        <th data-col="source">Источник</th>
                        <th data-col="comment">Комментарий</th>
                        <th style="width:44px">–</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($orders)): ?>
                        <?php foreach ($orders as $order):
                            $daysSince = (int)floor((time() - $order->created_at) / 86400);
                            $firstItem = $order->orderItems[0] ?? null;
                            // Z12: build pill colors from DB first, then fall back to hardcoded map
                            if (!empty($statusColorMap[$order->status])) {
                                $_hex = $colorToHex($statusColorMap[$order->status]);
                                $sp = ['bg' => $_hex . '22', 'color' => $_hex];
                            } else {
                                $sp = $statusPills[$order->status] ?? ['bg' => '#f3f4f6', 'color' => '#6b7280'];
                            }
                            $statusLabel = \app\backend\modules\checkout\models\Order::statusLabel($order->status);
                        ?>
                        <tr>
                            <td style="padding:6px"><input type="checkbox" class="order-checkbox" value="<?= $order->id ?>"></td>
                            <td style="white-space:nowrap;padding:6px 8px">
                                <a href="<?= Url::to(['view', 'id' => $order->id]) ?>"
                                   style="font-weight:700;color:var(--admin-text-primary,#111);text-decoration:none">
                                    <?php
                                        $oNum = $order->order_number ?: (string)$order->id;
                                        // Z13: purely numeric order numbers get a # prefix for clarity
                                        echo Html::encode(ctype_digit(ltrim($oNum, '#')) ? '#' . ltrim($oNum, '#') : $oNum);
                                    ?>
                                </a>
                                <div style="font-size:.7rem;color:var(--admin-text-secondary,#9ca3af);margin-top:1px">
                                    <?= date('d.m.Y', $order->created_at) ?>
                                    <?php if ($daysSince > 0): ?><span style="opacity:.7"> · <?= $daysSince ?>д</span><?php endif; ?>
                                </div>
                            </td>
                            <td style="max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"
                                class="tbl-editable" onclick="tblEdit(this,'client_name',<?= $order->id ?>)"
                                data-val="<?= Html::encode($order->client_name) ?>">
                                <?= Html::encode($order->client_name) ?>
                            </td>
                            <td style="white-space:nowrap"
                                class="tbl-editable" onclick="tblEdit(this,'client_phone',<?= $order->id ?>)"
                                data-val="<?= Html::encode($order->client_phone) ?>">
                                <?= Html::encode($order->client_phone) ?>
                            </td>
                            <td data-col="email" style="max-width:130px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:var(--admin-text-secondary,#6b7280)">
                                <?= $order->client_email ? Html::encode($order->client_email) : '—' ?>
                            </td>
                            <td data-col="item" style="max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                                <?= $firstItem ? Html::encode($firstItem->product_name) : '—' ?>
                            </td>
                            <td data-col="status" style="padding:6px 8px"
                                class="tbl-editable" onclick="tblEditSelect(this,'status',<?= $order->id ?>,tblStatuses)"
                                data-val="<?= Html::encode($order->status) ?>">
                                <span class="status-pill" style="background:<?= $sp['bg'] ?>;color:<?= $sp['color'] ?>">
                                    <?= Html::encode($statusLabel) ?>
                                </span>
                            </td>
                            <td data-col="amount" style="font-weight:700;white-space:nowrap"
                                class="tbl-editable" onclick="tblEdit(this,'total_amount',<?= $order->id ?>)"
                                data-val="<?= $order->total_amount ?>">
                                <?= number_format($order->total_amount, 2) ?> <span style="font-size:.7rem;color:var(--admin-text-secondary,#9ca3af);font-weight:400">Br</span>
                            </td>
                            <td data-col="payment" style="white-space:nowrap;color:var(--admin-text-secondary,#6b7280)">
                                <?= Html::encode($pmLabels[$order->payment_method ?? ''] ?? ($order->payment_method ?: '—')) ?>
                            </td>
                            <td data-col="delivery" style="white-space:nowrap;color:var(--admin-text-secondary,#6b7280)">
                                <?= Html::encode($dmLabels[$order->delivery_method ?? ''] ?? ($order->delivery_method ?: '—')) ?>
                            </td>
                            <td data-col="china_track">
                                <?php if (!empty($order->china_track_number)): ?>
                                <span class="track-badge" title="<?= Html::encode($order->china_track_number) ?>"><?= Html::encode($order->china_track_number) ?></span>
                                <?php if (!empty($dupMap[$order->china_track_number]) && $dupMap[$order->china_track_number] > 1): ?>
                                <span class="dup-badge" title="Трек встречается в <?= $dupMap[$order->china_track_number] ?> заказах">×<?= $dupMap[$order->china_track_number] ?></span>
                                <?php endif; ?>
                                <?php else: ?><span style="color:var(--admin-text-secondary,#9ca3af)">—</span><?php endif; ?>
                            </td>
                            <td data-col="dp_track">
                                <?php if (!empty($order->dp_track_number)): ?>
                                <span class="track-badge" title="<?= Html::encode($order->dp_track_number) ?>"><?= Html::encode($order->dp_track_number) ?></span>
                                <?php else: ?><span style="color:var(--admin-text-secondary,#9ca3af)">—</span><?php endif; ?>
                            </td>
                            <td data-col="local_track">
                                <?php if (!empty($order->local_track_number)): ?>
                                <span class="track-badge" title="<?= Html::encode($order->local_track_number) ?>"><?= Html::encode($order->local_track_number) ?></span>
                                <?php else: ?><span style="color:var(--admin-text-secondary,#9ca3af)">—</span><?php endif; ?>
                            </td>
                            <td data-col="dp_status" style="white-space:nowrap;color:var(--admin-text-secondary,#6b7280);font-size:.75rem">
                                <?= Html::encode($order->dp_status ?: '—') ?>
                            </td>
                            <td data-col="city" style="white-space:nowrap">
                                <?= Html::encode($order->city ?: '—') ?>
                            </td>
                            <td data-col="logist" style="white-space:nowrap;color:var(--admin-text-secondary,#6b7280)"
                                class="tbl-editable" onclick="tblEditSelect(this,'assigned_logist',<?= $order->id ?>,tblLogists)"
                                data-val="<?= $order->assigned_logist ?? '' ?>">
                                <?= Html::encode($logistMap[$order->assigned_logist] ?? '—') ?>
                            </td>
                            <td data-col="source" style="font-size:.75rem;white-space:nowrap;color:var(--admin-text-secondary,#6b7280)">
                                <?= Html::encode($order->source ?: '—') ?>
                            </td>
                            <td data-col="comment" style="max-width:110px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:var(--admin-text-secondary,#6b7280)"
                                title="<?= Html::encode($order->comment ?? '') ?>">
                                <?= Html::encode($order->comment ?: '—') ?>
                            </td>
                            <td style="padding:4px 6px">
                                <a href="<?= Url::to(['view', 'id' => $order->id]) ?>"
                                   class="admin-btn admin-btn-secondary"
                                   style="padding:.2rem .45rem;font-size:.875rem;">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="19" style="padding:0">
                                <div class="empty-state" style="padding:2.5rem">
                                    <div class="empty-state-icon"><i class="bi bi-inbox"></i></div>
                                    <h3 class="empty-state-title">Нет заказов по текущим фильтрам</h3>
                                    <p class="empty-state-description">Измените параметры поиска или создайте новый заказ</p>
                                    <?= Html::a('<i class="bi bi-plus-circle"></i> Создать заказ', ['create'], ['class' => 'admin-btn admin-btn-primary']) ?>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Infinite scroll sentinel -->
        <div id="scroll-sentinel"></div>
        <div id="scroll-loading">
            <div class="scroll-spinner"></div>
            <span>Загрузка…</span>
        </div>
    </div><!-- /tableView -->

    <!-- KANBAN VIEW -->
    <div id="kanbanView" class="d-none">
        <div class="kanban-board" id="kanbanBoard">
            <?php foreach ($kanbanColumns as $colKey => $colLabel): ?>
                <?php $colOrders = $kanbanGroups[$colKey] ?? []; ?>
                <div class="kanban-col" data-status="<?= $colKey ?>">
                    <div class="kanban-col-header">
                        <span><?= Html::encode($colLabel) ?></span>
                        <span class="kanban-col-badge"><?= count($colOrders) ?></span>
                    </div>
                    <div class="kanban-cards" id="col-<?= $colKey ?>"
                         ondragover="onDragOver(event)"
                         ondragleave="onDragLeave(event)"
                         ondrop="onDrop(event, '<?= $colKey ?>')">
                        <?php foreach ($colOrders as $ord):
                            $daysSince = (int)floor((time() - $ord->created_at) / 86400);
                            $firstItem = $ord->orderItems[0] ?? null;
                        ?>
                            <div class="kanban-card"
                                 draggable="true"
                                 data-id="<?= $ord->id ?>"
                                 data-status="<?= $ord->status ?>"
                                 ondragstart="onDragStart(event)"
                                 ondragend="onDragEnd(event)">
                                <?php $kcNum = $ord->order_number ?: (string)$ord->id; ?>
                                <div class="kc-num"><?= Html::encode(ctype_digit(ltrim($kcNum, '#')) ? '#' . ltrim($kcNum, '#') : $kcNum) ?></div>
                                <div class="kc-client"><?= Html::encode($ord->client_name) ?></div>
                                <?php if ($firstItem): ?>
                                    <div class="kc-product" title="<?= Html::encode($firstItem->product_name) ?>"><?= Html::encode($firstItem->product_name) ?></div>
                                <?php endif; ?>
                                <div class="kc-meta">
                                    <span class="kc-amount"><?= number_format($ord->total_amount, 2) ?> Br</span>
                                    <span><?= $daysSince > 0 ? $daysSince.' дн.' : 'сегодня' ?></span>
                                </div>
                                <div style="margin-top:.35rem;">
                                    <a href="<?= Url::to(['view', 'id' => $ord->id]) ?>" style="font-size:.75rem;color:var(--admin-primary,#2563eb);">
                                        <i class="bi bi-box-arrow-up-right"></i> Открыть
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <p style="color:var(--admin-text-secondary,#6b7280);font-size:.8125rem;margin-top:.5rem;">
            <i class="bi bi-info-circle"></i> Перетащите карточку в другую колонку для смены статуса. Показаны заказы текущей страницы.
        </p>
    </div><!-- /kanbanView -->
</div>

<script>
/* ──────────────────────────────────────────
   Quick date preset buttons
────────────────────────────────────────── */
function applyDatePreset(from, to) {
    const form = document.getElementById('filterForm');
    // Set date_from / date_to fields inside the form
    const fromInput = form.querySelector('input[name="date_from"]');
    const toInput   = form.querySelector('input[name="date_to"]');
    if (fromInput) fromInput.value = from;
    if (toInput)   toInput.value   = to;
    form.submit();
}

/* ──────────────────────────────────────────
/* ──────────────────────────────────────────
   Task 3: Expandable filter row 2
────────────────────────────────────────── */
function toggleFilterRow2() {
    const r2  = document.getElementById('filterRow2');
    const btn = document.getElementById('filterExpandBtn');
    const row1 = document.querySelector('.filter-row1');
    const open = r2.style.display !== 'none';
    r2.style.display = open ? 'none' : 'flex';
    btn.classList.toggle('is-active', !open);
    row1.classList.toggle('has-row2', !open);
}
/* Set initial button state if row2 is already open (active filters from PHP) */
(function() {
    const r2 = document.getElementById('filterRow2');
    if (r2 && r2.style.display !== 'none') {
        const btn = document.getElementById('filterExpandBtn');
        if (btn) btn.classList.add('is-active');
        const row1 = document.querySelector('.filter-row1');
        if (row1) row1.classList.add('has-row2');
    }
})();

/* ──────────────────────────────────────────
   Sortable columns
────────────────────────────────────────── */
function sortBy(col) {
    const url = new URL(window.location.href);
    const cur = url.searchParams.get('sort') || '';
    url.searchParams.set('sort', cur === col ? ('-' + col) : col);
    url.searchParams.delete('page'); // reset to first page on sort change
    window.location.href = url.toString();
}

/* ──────────────────────────────────────────
   Infinite scroll
────────────────────────────────────────── */
(function() {
    var loading  = false;
    var hasMore  = <?= $pagination->pageCount > 1 ? 'true' : 'false' ?>;
    var nextPage = 2; // Yii2 URL page param is 1-based; first page already rendered

    var sentinel = document.getElementById('scroll-sentinel');
    var spinner  = document.getElementById('scroll-loading');

    if (!hasMore || !sentinel) return;

    function applyHiddenColumns(rows) {
        var state = [];
        try { state = JSON.parse(localStorage.getItem('admin_order_columns') || '[]'); } catch(e){}
        state.forEach(function(entry) {
            if (!entry.visible) {
                rows.forEach(function(row) {
                    row.querySelectorAll('[data-col="' + entry.col + '"]').forEach(function(el) {
                        el.style.display = 'none';
                    });
                });
            }
        });
    }

    function loadMore() {
        if (loading || !hasMore) return;
        loading = true;
        if (spinner) spinner.style.display = 'flex';

        var url = new URL(window.location.href);
        url.searchParams.set('scroll', '1');
        url.searchParams.set('page', nextPage);

        fetch(url.toString(), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (spinner) spinner.style.display = 'none';
            loading = false;

            if (data.rows) {
                var tbody = document.querySelector('#ordersTable tbody');
                var temp  = document.createElement('table');
                temp.innerHTML = '<tbody>' + data.rows + '</tbody>';
                var newRows = Array.from(temp.querySelector('tbody').children);
                newRows.forEach(function(row) { tbody.appendChild(row); });
                applyHiddenColumns(newRows);
            }

            hasMore  = !!data.hasMore;
            nextPage = data.nextPage || (nextPage + 1);

            if (!hasMore) sentinel.style.display = 'none';
        })
        .catch(function() {
            loading = false;
            if (spinner) spinner.style.display = 'none';
        });
    }

    if (typeof IntersectionObserver !== 'undefined') {
        new IntersectionObserver(function(entries) {
            entries.forEach(function(e) { if (e.isIntersecting) loadMore(); });
        }, { rootMargin: '300px' }).observe(sentinel);
    } else {
        // Fallback: scroll event
        window.addEventListener('scroll', function() {
            var r = sentinel.getBoundingClientRect();
            if (r.top < window.innerHeight + 400) loadMore();
        }, { passive: true });
    }
})();

/* ──────────────────────────────────────────
   Click anywhere on row to open order
────────────────────────────────────────── */
(function() {
    var tbody = document.querySelector('#ordersTable tbody');
    if (!tbody) return;
    tbody.addEventListener('click', function(e) {
        // Ignore clicks on interactive elements
        if (e.target.closest('a, button, input, select, label, [onclick]')) return;
        var row = e.target.closest('tr');
        if (!row) return;
        var link = row.querySelector('a.admin-btn');
        if (link && link.href) window.location.href = link.href;
    });
})();

/* ──────────────────────────────────────────
   Bulk actions
────────────────────────────────────────── */
(function() {
    var selectAll = document.getElementById('selectAll');
    var toolbar   = document.getElementById('bulkToolbar');
    var countSpan = document.getElementById('selectedCount');

    function getChecked() {
        return Array.from(document.querySelectorAll('.order-checkbox:checked'));
    }

    function updateToolbar() {
        var checked = getChecked();
        if (countSpan) countSpan.textContent = checked.length;
        if (toolbar) toolbar.style.display = checked.length > 0 ? 'flex' : 'none';
    }

    document.addEventListener('change', function(e) {
        if (e.target.id === 'selectAll') {
            document.querySelectorAll('.order-checkbox').forEach(function(cb) {
                cb.checked = e.target.checked;
            });
            updateToolbar();
        } else if (e.target.classList.contains('order-checkbox')) {
            if (!e.target.checked && selectAll) selectAll.checked = false;
            updateToolbar();
        } else if (e.target.id === 'bulkActionSelect') {
            var v = e.target.value;
            document.getElementById('bulkStatusExtra').style.display  = v === 'change_status'  ? 'inline-block' : 'none';
            document.getElementById('bulkLogistExtra').style.display  = v === 'assign_logist'  ? 'inline-block' : 'none';
        }
    });

    if (toolbar) toolbar.style.display = 'none';
})();

function applyBulkAction() {
    var action = document.getElementById('bulkActionSelect').value;
    if (!action) { alert('Выберите действие'); return; }

    var ids = Array.from(document.querySelectorAll('.order-checkbox:checked')).map(function(cb) { return cb.value; });
    if (!ids.length) { alert('Выберите хотя бы один заказ'); return; }

    var extra = '';
    if (action === 'change_status') {
        extra = document.getElementById('bulkStatusExtra').value;
        if (!extra) { alert('Выберите статус'); return; }
    } else if (action === 'assign_logist') {
        extra = document.getElementById('bulkLogistExtra').value;
        if (!extra) { alert('Выберите логиста'); return; }
    } else if (action === 'export_csv') {
        window.location.href = '<?= \yii\helpers\Url::to(['/admin/order/export-csv']) ?>&ids=' + ids.join(',');
        return;
    }

    var csrf = document.querySelector('meta[name="csrf-token"]');
    fetch('<?= \yii\helpers\Url::to(['/admin/order/bulk-action']) ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-Token': csrf ? csrf.getAttribute('content') : ''
        },
        body: JSON.stringify({ action: action, ids: ids, extra: extra })
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            if (data.redirect) { window.location.href = data.redirect; return; }
            alert(data.message || 'Готово');
            window.location.reload();
        } else {
            alert(data.message || 'Ошибка');
        }
    });
}

function clearSelection() {
    document.querySelectorAll('.order-checkbox').forEach(function(cb) { cb.checked = false; });
    var sa = document.getElementById('selectAll');
    if (sa) sa.checked = false;
    var toolbar = document.getElementById('bulkToolbar');
    if (toolbar) toolbar.style.display = 'none';
    var cnt = document.getElementById('selectedCount');
    if (cnt) cnt.textContent = '0';
}

function switchView(view) {
    var tableView  = document.getElementById('tableView');
    var kanbanView = document.getElementById('kanbanView');
    var buttons    = document.querySelectorAll('.view-toggle-btn');

    if (view === 'kanban') {
        if (tableView)  { tableView.classList.add('d-none');    tableView.style.display = ''; }
        if (kanbanView) { kanbanView.classList.remove('d-none'); kanbanView.style.display = ''; }
    } else {
        if (tableView)  { tableView.classList.remove('d-none'); tableView.style.display = ''; }
        if (kanbanView) { kanbanView.classList.add('d-none');    kanbanView.style.display = ''; }
    }

    buttons.forEach(function(btn) {
        btn.classList.toggle('view-toggle-btn--active', btn.getAttribute('data-view') === view);
    });

    localStorage.setItem('orderViewPreference', view);
}

// Restore saved view preference on load
(function() {
    var pref = localStorage.getItem('orderViewPreference');
    if (pref === 'kanban') switchView('kanban');
})();

function changePageSize(size) {
    var url = new URL(window.location.href);
    url.searchParams.set('per-page', size);
    url.searchParams.delete('page');
    window.location.href = url.toString();
}

// ── Table inline-edit ────────────────────────────────────────
var tblStatuses = <?= json_encode($statuses, JSON_UNESCAPED_UNICODE) ?>;
var tblLogists  = <?= json_encode(array_merge(['' => '— не назначен —'], $logistMap), JSON_UNESCAPED_UNICODE) ?>;
var tblUpdateUrl = '<?= \yii\helpers\Url::to(['/admin/order/update-field']) ?>';

function tblSaveField(orderId, field, value, cell) {
    var fd = new FormData();
    fd.append('field', field);
    fd.append('value', value);
    fd.append(document.querySelector('meta[name=csrf-param]').content, document.querySelector('meta[name=csrf-token]').content);
    fetch(tblUpdateUrl + '?id=' + orderId, {method:'POST', body:fd})
        .then(function(r){return r.json();})
        .then(function(d){if(!d.success){cell.style.outline='2px solid #dc2626';}});
}

function tblEdit(cell, field, orderId) {
    if (cell.querySelector('input')) return;
    var val = cell.dataset.val || '';
    var inp = document.createElement('input');
    inp.value = val; inp.className = 'tbl-editable-inp';
    inp.style.cssText = 'width:100%;font-size:.8125rem;padding:2px 5px;border:1.5px solid var(--admin-primary,#2563eb);border-radius:4px;background:#fff;outline:none;min-width:80px';
    cell.innerHTML = ''; cell.appendChild(inp); inp.focus(); inp.select();
    var commit = function() {
        cell.dataset.val = inp.value;
        cell.textContent = inp.value || '—';
        tblSaveField(orderId, field, inp.value, cell);
    };
    inp.addEventListener('blur', commit);
    inp.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') { e.preventDefault(); inp.blur(); }
        if (e.key === 'Escape') { cell.textContent = val || '—'; }
    });
    inp.addEventListener('click', function(e){e.stopPropagation();});
}

function tblEditSelect(cell, field, orderId, optMap) {
    if (cell.querySelector('select')) return;
    var val = cell.dataset.val || '';
    var sel = document.createElement('select');
    sel.style.cssText = 'font-size:.8125rem;padding:2px 4px;border:1.5px solid var(--admin-primary,#2563eb);border-radius:4px;background:#fff;outline:none;max-width:130px';
    for (var k in optMap) {
        var o = document.createElement('option');
        o.value = k; o.textContent = optMap[k];
        if (k == val) o.selected = true;
        sel.appendChild(o);
    }
    cell.innerHTML = ''; cell.appendChild(sel); sel.focus();
    var commit = function() {
        var newVal = sel.value;
        var newLabel = optMap[newVal] || newVal;
        cell.dataset.val = newVal;
        cell.textContent = newLabel;
        tblSaveField(orderId, field, newVal, cell);
    };
    sel.addEventListener('change', function(){commit(); sel.blur();});
    sel.addEventListener('blur', commit);
    sel.addEventListener('click', function(e){e.stopPropagation();});
}

// ── Column visibility ───────────────────────────────────────────────────────
var COL_KEY      = 'admin_order_columns';
var DEFAULT_HIDDEN = ['china_track', 'dp_track', 'local_track', 'dp_status'];

function loadColumnState() {
    try {
        var raw = localStorage.getItem(COL_KEY);
        return raw ? JSON.parse(raw) : null;
    } catch(e) { return null; }
}
function saveColumnState(state) {
    try { localStorage.setItem(COL_KEY, JSON.stringify(state)); } catch(e) {}
}

function applyColumnState(state) {
    var table = document.getElementById('ordersTable');
    if (!table) return;
    state.forEach(function(entry) {
        var visible = entry.visible;
        var col = entry.col;
        // Toggle th
        var th = table.querySelector('thead th[data-col="' + col + '"]');
        if (th) th.style.display = visible ? '' : 'none';
        // Toggle all td
        table.querySelectorAll('td[data-col="' + col + '"]').forEach(function(td) {
            td.style.display = visible ? '' : 'none';
        });
        // Sync checkbox
        var cb = document.querySelector('input[data-col-toggle="' + col + '"]');
        if (cb) cb.checked = visible;
    });
}

function buildDefaultState() {
    var cols = [];
    document.querySelectorAll('input[data-col-toggle]').forEach(function(cb) {
        cols.push({ col: cb.dataset.colToggle, visible: DEFAULT_HIDDEN.indexOf(cb.dataset.colToggle) === -1 });
    });
    return cols;
}

function toggleColumn(col, visible) {
    var state = loadColumnState() || buildDefaultState();
    var entry = state.find(function(e) { return e.col === col; });
    if (entry) entry.visible = visible;
    else state.push({ col: col, visible: visible });
    saveColumnState(state);
    applyColumnState(state);
}

function resetColumns() {
    var state = buildDefaultState();
    saveColumnState(state);
    applyColumnState(state);
}

function toggleColDropdown() {
    var dd = document.getElementById('colDropdown');
    if (!dd) return;
    dd.style.display = dd.style.display === 'none' ? 'block' : 'none';
}

document.addEventListener('click', function(e) {
    var wrap = document.getElementById('colToggleWrap');
    if (wrap && !wrap.contains(e.target)) {
        var dd = document.getElementById('colDropdown');
        if (dd) dd.style.display = 'none';
    }
});

// Init column state on load
(function() {
    var saved = loadColumnState();
    var state = saved || buildDefaultState();
    if (!saved) saveColumnState(state);
    applyColumnState(state);
})();
</script>
