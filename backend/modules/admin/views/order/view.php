<?php
/** @var yii\web\View $this */
/** @var app\backend\modules\checkout\models\Order $model */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Заказ №' . ($model->order_number ?: $model->id);
$user = Yii::$app->user->identity;
$statuses = $user->isLogist() ? Yii::$app->settings->getLogistStatuses() : Yii::$app->settings->getStatuses();

$statusColors = [
    'new'                 => '#6b7280',
    'created'             => '#6b7280',
    'confirmed_and_paid'  => '#059669',
    'paid'                => '#059669',
    'processing'          => '#d97706',
    'shipped'             => '#2563eb',
    'in_transit'          => '#2563eb',
    'arrived_warehouse'   => '#7c3aed',
    'delivered'           => '#16a34a',
    'cancelled'           => '#dc2626',
    'returned'            => '#dc2626',
];
$statusBgColors = [
    'new'                 => '#f3f4f6',
    'created'             => '#f3f4f6',
    'confirmed_and_paid'  => '#d1fae5',
    'paid'                => '#d1fae5',
    'processing'          => '#fef3c7',
    'shipped'             => '#dbeafe',
    'in_transit'          => '#dbeafe',
    'arrived_warehouse'   => '#ede9fe',
    'delivered'           => '#dcfce7',
    'cancelled'           => '#fee2e2',
    'returned'            => '#fee2e2',
];
$statusColor = $statusColors[$model->status] ?? '#6b7280';
$statusBg    = $statusBgColors[$model->status] ?? '#f3f4f6';
$statusLabel = $statuses[$model->status] ?? $model->status;

// DP badge
$dpShipmentId = $model->dp_shipment_id ?? null;
$dpStatus     = $model->dp_status ?? null;
$dpResponse   = $model->dp_response ?? null;
$dpPassportOk = $model->isPassportComplete();

// Customer
$customer = $model->customer ?? null;
?>
<style>
/* ═══ ORDER CRM VIEW ═══ */
.crm-wrap { display: flex; flex-direction: column; gap: 0; min-height: calc(100vh - 120px); width: 100%; min-width: 0; overflow: visible; }

/* Top bar */
.crm-topbar {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 20px;
    background: var(--admin-surface, #fff);
    border-bottom: 1px solid var(--admin-border, #e5e7eb);
    flex-wrap: wrap;
    position: sticky;
    top: 0;
    z-index: 30;
}
.crm-topbar-left { display: flex; align-items: center; gap: 10px; flex: 1; min-width: 0; }
.crm-order-num { font-size: 1.125rem; font-weight: 800; color: var(--admin-text-primary, #111); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; min-width: 0; flex-shrink: 1; }
.crm-order-date { font-size: 0.8rem; color: var(--admin-text-secondary, #6b7280); }
.crm-status-pill {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 10px; border-radius: 999px; font-size: 0.75rem; font-weight: 700;
    background: <?= $statusBg ?>; color: <?= $statusColor ?>;
    border: 1px solid color-mix(in srgb, <?= $statusColor ?> 20%, transparent);
    white-space: nowrap;
}
.crm-topbar-actions { display: flex; align-items: center; gap: 6px; flex-wrap: nowrap; flex-shrink: 0; }
.crm-topbar { overflow-x: auto; }

/* Quick status in topbar */
.crm-status-select {
    height: 32px; padding: 0 28px 0 10px; border-radius: 8px;
    border: 1px solid var(--admin-border, #e5e7eb); font-size: 0.8125rem; font-weight: 600;
    background: var(--admin-surface-hover, #f9fafb); cursor: pointer; appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b7280' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat; background-position: right 8px center;
    color: var(--admin-text-primary, #111);
}

/* Main grid — two columns unconditionally, reset to single on mobile */
.crm-body { display: grid; grid-template-columns: 1fr 380px; gap: 20px; flex: 1; align-items: start; width: 100%; min-width: 0; overflow: visible; }
.crm-main { padding: 16px; display: flex; flex-direction: column; gap: 12px; min-width: 0; overflow: visible; }
/* Sidebar: full-height, scrolls with page */
.crm-sidebar { padding: 10px 12px; display: flex; flex-direction: column; gap: 8px; position: static; align-self: stretch; max-height: none; overflow-y: visible; min-width: 0; }
/* Compact sidebar cards */
.crm-sidebar .crm-card-head { padding: 7px 12px; }
.crm-sidebar .crm-card-head h3 { font-size: 0.75rem; }
.crm-sidebar .crm-card-body { padding: 10px 12px; }
.crm-sidebar .crm-field-label { font-size: 0.6875rem; }
.crm-sidebar .crm-field-val { font-size: 0.75rem; }
.crm-sidebar .crm-editable { font-size: 0.75rem; }
.crm-sidebar .crm-customer-avatar { width: 32px; height: 32px; font-size: 0.75rem; flex-shrink: 0; }
.crm-sidebar .admin-btn-sm { font-size: 0.6875rem; padding: 3px 8px; }

/* Cards */
.crm-card {
    background: var(--admin-surface, #fff);
    border: 1px solid var(--admin-border, #e5e7eb);
    border-radius: 12px;
    overflow: hidden;
}
.crm-card-head {
    display: flex; align-items: center; justify-content: space-between;
    padding: 10px 16px;
    border-bottom: 1px solid var(--admin-border, #e5e7eb);
    background: var(--admin-surface-hover, #f9fafb);
}
.crm-card-head h3 {
    font-size: 0.8125rem; font-weight: 700; color: var(--admin-text-primary, #111);
    display: flex; align-items: center; gap: 6px; margin: 0;
}
.crm-card-head h3 i { color: var(--admin-text-secondary, #6b7280); }
.crm-card-body { padding: 14px 16px; }

/* Product table */
.crm-items-table { width: 100%; border-collapse: collapse; font-size: 0.8125rem; }
.crm-items-table th {
    text-align: left; padding: 6px 10px; font-size: 0.7rem; font-weight: 600;
    color: var(--admin-text-secondary, #6b7280); text-transform: uppercase; letter-spacing: .04em;
    border-bottom: 1px solid var(--admin-border, #e5e7eb);
}
.crm-items-table td { padding: 8px 10px; border-bottom: 1px solid var(--admin-border, #f3f4f6); vertical-align: middle; }
.crm-items-table tr.item-main-row:last-of-type td { border-bottom: none; }
.crm-items-table tr.item-detail-row td { border-bottom: 1px solid var(--admin-border,#e5e7eb); padding:0; }
.item-expand-btn { background:none;border:none;cursor:pointer;padding:3px 5px;border-radius:5px;color:var(--admin-text-secondary,#9ca3af);font-size:1rem;line-height:1;transition:color .15s,background .15s }
.item-expand-btn:hover { color:var(--admin-text-primary,#111);background:var(--admin-surface-hover,#f3f4f6) }
.item-expand-panel { padding:10px 12px 12px;background:var(--admin-surface-hover,#f9fafb);border-top:1px dashed var(--admin-border,#e5e7eb) }
.ief-grid { display:flex;flex-wrap:wrap;gap:6px;margin-bottom:8px }
.ief { display:flex;flex-direction:column;gap:2px;min-width:0 }
.ief label { font-size:.63rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:var(--admin-text-secondary,#9ca3af) }
.ief .admin-form-input { padding:3px 6px;font-size:.78rem;height:26px }
.ief-section { font-size:.63rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:#4338ca;margin:8px 0 5px;padding-top:6px;border-top:1px dashed var(--admin-border,#e5e7eb);width:100% }
.ief-actions { display:flex;align-items:center;gap:6px;padding-top:8px;border-top:1px solid var(--admin-border,#e5e7eb);margin-top:4px }
.crm-items-table .item-img {
    width: 36px; height: 36px; border-radius: 6px; object-fit: cover;
    background: var(--admin-surface-hover, #f3f4f6);
}
.crm-items-table .item-name { font-weight: 600; color: var(--admin-text-primary, #111); }
.crm-items-table .item-sku { font-size: 0.7rem; color: var(--admin-text-secondary, #6b7280); }
.crm-total-row {
    display: flex; justify-content: flex-end; align-items: center; gap: 8px;
    padding: 10px 16px; border-top: 1px solid var(--admin-border, #e5e7eb);
    font-weight: 700; font-size: 0.9375rem;
}
.crm-total-row .label { color: var(--admin-text-secondary, #6b7280); font-weight: 500; font-size: 0.8125rem; }

/* Info grid (2 col) */
.crm-info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.crm-field { display: flex; flex-direction: column; gap: 3px; }
.crm-field-label { font-size: 0.7rem; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; color: var(--admin-text-secondary, #9ca3af); }
.crm-field-val { font-size: 0.875rem; font-weight: 500; color: var(--admin-text-primary, #111); }

/* Editable field */
.crm-editable {
    display: flex; align-items: center; gap: 4px;
    padding: 4px 8px; border-radius: 6px; cursor: pointer;
    border: 1px solid transparent; transition: border-color .15s, background .15s;
    font-size: 0.875rem; font-weight: 500; color: var(--admin-text-primary, #111);
    min-height: 30px; position: relative;
}
.crm-editable:hover { border-color: var(--admin-border, #e5e7eb); background: var(--admin-surface-hover, #f9fafb); }
.crm-editable:hover::after { content: '✎'; position: absolute; right: 6px; font-size: 10px; color: var(--admin-text-secondary, #9ca3af); }
.crm-editable-input {
    width: 100%; padding: 4px 8px; border: 1.5px solid var(--admin-accent, #111); border-radius: 6px;
    font-size: 0.875rem; font-weight: 500; background: var(--admin-surface, #fff);
    color: var(--admin-text-primary, #111); outline: none;
}
.crm-editable-empty { color: var(--admin-text-secondary, #9ca3af); font-weight: 400; font-style: italic; }
.crm-editable:focus { outline: none; border-color: var(--admin-primary, #2563eb); background: var(--admin-surface-hover, #f9fafb); }
.crm-editable:focus-within { border-color: var(--admin-primary, #2563eb) !important; background: #eff6ff !important; box-shadow: 0 0 0 3px rgba(37,99,235,.12); }
.crm-editable.crm-editing { border-color: var(--admin-primary, #2563eb) !important; background: #eff6ff !important; box-shadow: 0 0 0 3px rgba(37,99,235,.12); }

/* Inline save indicator */
.crm-save-flash {
    display: inline-block; opacity: 0;
    color: var(--admin-accent, #059669); font-size: 11px; margin-left: 4px;
    transition: opacity .2s;
}
.crm-save-flash.show { opacity: 1; }

/* Track number inline */
.crm-track-wrap { display: flex; gap: 6px; align-items: center; }
.crm-track-input { flex: 1; padding: 5px 10px; border: 1px solid var(--admin-border, #e5e7eb); border-radius: 8px; font-size: 0.8125rem; font-family: monospace; }
.crm-track-input:focus { outline: none; border-color: var(--admin-accent, #111); }

/* Customer card */
.crm-customer-card { padding: 14px 16px; }
.crm-customer-avatar {
    width: 44px; height: 44px; border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-weight: 800; font-size: 1.125rem; flex-shrink: 0;
}
.crm-customer-meta { font-size: 0.75rem; color: var(--admin-text-secondary, #6b7280); display: flex; flex-direction: column; gap: 3px; }
.crm-customer-meta a { color: inherit; text-decoration: none; }
.crm-customer-meta a:hover { color: var(--admin-text-primary, #111); }

/* Status changer (sidebar) */
.crm-status-options { display: flex; flex-direction: column; gap: 4px; }
.crm-status-opt {
    display: flex; align-items: center; gap: 8px;
    padding: 7px 10px; border-radius: 8px; cursor: pointer; font-size: 0.8125rem;
    border: 1.5px solid transparent; transition: all .15s; font-weight: 500;
}
.crm-status-opt:hover { background: var(--admin-surface-hover, #f9fafb); border-color: var(--admin-border, #e5e7eb); }
.crm-status-opt.current { font-weight: 700; border-color: currentColor; }
.crm-status-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }

/* Timeline */
.crm-timeline { display: flex; flex-direction: column; gap: 0; padding: 12px 16px; }
.crm-tl-item { display: flex; gap: 10px; position: relative; }
.crm-tl-item:not(:last-child)::before {
    content: ''; position: absolute; left: 5px; top: 20px; bottom: -4px;
    width: 2px; background: var(--admin-border, #e5e7eb);
}
.crm-tl-dot {
    width: 12px; height: 12px; border-radius: 50%; border: 2px solid var(--admin-border, #e5e7eb);
    background: var(--admin-surface, #fff); flex-shrink: 0; margin-top: 4px; z-index: 1;
}
.crm-tl-dot.active { background: var(--admin-accent, #111); border-color: var(--admin-accent, #111); }
.crm-tl-body { padding-bottom: 16px; flex: 1; }
.crm-tl-status { font-size: 0.8125rem; font-weight: 600; color: var(--admin-text-primary, #111); }
.crm-tl-meta { font-size: 0.7rem; color: var(--admin-text-secondary, #9ca3af); margin-top: 1px; }
.crm-tl-comment { font-size: 0.75rem; color: var(--admin-text-secondary, #6b7280); margin-top: 3px; font-style: italic; }

/* Note add form */
.crm-note-form { display: flex; gap: 6px; padding: 10px 16px; border-top: 1px solid var(--admin-border, #e5e7eb); }
.crm-note-textarea {
    flex: 1; padding: 7px 10px; border: 1px solid var(--admin-border, #e5e7eb); border-radius: 8px;
    font-size: 0.8125rem; resize: none; min-height: 56px;
    background: var(--admin-surface, #fff); color: var(--admin-text-primary, #111);
}
.crm-note-textarea:focus { outline: none; border-color: var(--admin-accent, #111); }

/* DP / delivery badges */
.crm-dp-badge {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 2px 8px; border-radius: 6px; font-size: 0.7rem; font-weight: 700;
}
.crm-dp-badge.sent { background: #d1fae5; color: #065f46; }
.crm-dp-badge.pending { background: #f3f4f6; color: #6b7280; }
.crm-dp-badge.error { background: #fee2e2; color: #991b1b; }

/* Public link copy */
.crm-link-copy { display: flex; gap: 4px; align-items: center; margin-top: 4px; }
.crm-link-copy input {
    flex: 1; font-size: 0.7rem; padding: 3px 6px; border: 1px solid var(--admin-border, #e5e7eb);
    border-radius: 6px; background: var(--admin-surface-hover, #f9fafb); color: var(--admin-text-secondary, #6b7280);
    font-family: monospace; width: 100%;
}

/* Mobile: reset to single column */
@media (max-width: 1023px) {
    .crm-body { grid-template-columns: 1fr; }
    .crm-sidebar { position: static; max-height: none; overflow-y: visible; }
}
@media (max-width: 600px) {
    .crm-info-grid { grid-template-columns: 1fr; }
}

/* History slide-in panel */
.crm-history-popup {
    display: none; position: fixed; inset: 0; z-index: 100;
    background: rgba(0,0,0,.35); align-items: flex-start; justify-content: flex-end;
}
.crm-history-popup.open { display: flex; }
.crm-history-panel {
    width: 380px; max-height: 100vh; overflow-y: auto;
    background: var(--admin-surface, #fff); border-left: 1px solid var(--admin-border, #e5e7eb);
    box-shadow: -4px 0 24px rgba(0,0,0,.12); display: flex; flex-direction: column;
    animation: slideInRight .2s ease;
}
@keyframes slideInRight { from { transform: translateX(100%); } to { transform: translateX(0); } }
.crm-history-panel-head {
    display: flex; align-items: center; justify-content: space-between;
    padding: 14px 16px; border-bottom: 1px solid var(--admin-border, #e5e7eb);
    background: var(--admin-surface-hover, #f9fafb); position: sticky; top: 0; z-index: 1;
}
.crm-history-panel-head h3 { margin: 0; font-size: 0.9375rem; font-weight: 700; display: flex; align-items: center; gap: 6px; }

/* Integration icon buttons in topbar */
.crm-int-btn {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 4px 8px; border-radius: 8px; font-size: 0.75rem; font-weight: 600;
    border: 1px solid var(--admin-border, #e5e7eb); background: var(--admin-surface, #fff);
    color: var(--admin-text-secondary, #6b7280); cursor: pointer; text-decoration: none;
    transition: all .15s; white-space: nowrap;
}
.crm-int-btn:hover { border-color: var(--admin-accent, #111); color: var(--admin-text-primary, #111); background: var(--admin-surface-hover, #f9fafb); }
.crm-int-btn.active { border-color: #059669; color: #059669; background: #f0fdf4; }
.crm-int-sep { width: 1px; height: 20px; background: var(--admin-border, #e5e7eb); margin: 0 2px; flex-shrink: 0; }

/* PVZ autocomplete */
.crm-pvz-wrap { position: relative; }
.crm-pvz-dropdown {
    position: absolute; left: 0; right: 0; top: calc(100% + 2px); z-index: 50;
    background: var(--admin-surface, #fff); border: 1px solid var(--admin-border, #e5e7eb);
    border-radius: 8px; box-shadow: 0 4px 16px rgba(0,0,0,.08); max-height: 220px; overflow-y: auto;
    display: none;
}
.crm-pvz-dropdown.open { display: block; }
.crm-pvz-opt { padding: 7px 12px; font-size: 0.8125rem; cursor: pointer; border-bottom: 1px solid var(--admin-border, #f3f4f6); }
.crm-pvz-opt:last-child { border-bottom: none; }
.crm-pvz-opt:hover { background: var(--admin-surface-hover, #f9fafb); }
.crm-pvz-opt .pvz-city { font-weight: 600; color: var(--admin-text-primary, #111); }
.crm-pvz-opt .pvz-addr { font-size: 0.7rem; color: var(--admin-text-secondary, #6b7280); margin-top: 1px; }

/* File drop zone */
.crm-file-drop {
    border: 2px dashed var(--admin-border, #e5e7eb); border-radius: 10px;
    padding: 20px; text-align: center; color: var(--admin-text-secondary, #9ca3af);
    font-size: 0.8125rem; cursor: pointer; transition: border-color .15s;
}
.crm-file-drop:hover { border-color: var(--admin-accent, #111); color: var(--admin-text-primary, #111); }

/* Customer quick-view modal */
.cqv-overlay {
    display: none; position: fixed; inset: 0; z-index: 200;
    background: rgba(0,0,0,.45); align-items: center; justify-content: center; padding: 16px;
}
.cqv-overlay.open { display: flex; }
.cqv-modal {
    background: var(--admin-surface, #fff); border-radius: 14px;
    box-shadow: 0 8px 40px rgba(0,0,0,.18); width: 100%; max-width: 580px;
    max-height: 90vh; display: flex; flex-direction: column;
    animation: cqvIn .2s ease;
}
@keyframes cqvIn { from { opacity:0; transform: scale(.96) translateY(8px); } to { opacity:1; transform: none; } }
@keyframes spin { to { transform: rotate(360deg); } }
.cqv-header {
    display: flex; align-items: center; gap: 10px; padding: 14px 18px;
    border-bottom: 1px solid var(--admin-border, #e5e7eb);
    background: var(--admin-surface-hover, #f9fafb); border-radius: 14px 14px 0 0; flex-shrink: 0;
}
.cqv-header h3 { margin: 0; font-size: 1rem; font-weight: 700; flex: 1; }
.cqv-body { flex: 1; overflow-y: auto; padding: 16px 18px; }
.cqv-loading { text-align:center; padding: 40px 0; color: var(--admin-text-secondary,#6b7280); font-size: 0.875rem; }
.cqv-section { margin-bottom: 18px; }
.cqv-section-title {
    font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em;
    color: var(--admin-text-secondary, #9ca3af); margin-bottom: 8px; padding-bottom: 5px;
    border-bottom: 1px solid var(--admin-border, #f3f4f6);
}
.cqv-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
.cqv-field { display: flex; flex-direction: column; gap: 2px; }
.cqv-label { font-size: 0.7rem; color: var(--admin-text-secondary, #9ca3af); font-weight: 600; text-transform: uppercase; letter-spacing: .04em; }
.cqv-val { font-size: 0.8125rem; font-weight: 500; color: var(--admin-text-primary, #111); }
.cqv-tag { display: inline-block; padding: 2px 8px; border-radius: 6px; font-size: 0.7rem; font-weight: 600; background: var(--admin-surface-hover,#f3f4f6); color: var(--admin-text-secondary,#6b7280); margin: 2px 2px 2px 0; }
.cqv-order-row { display: flex; align-items: center; gap: 8px; padding: 6px 0; border-bottom: 1px solid var(--admin-border,#f3f4f6); font-size: 0.8125rem; }
.cqv-order-row:last-child { border-bottom: none; }
</style>

<div class="crm-wrap">

    <!-- ═══ TOP BAR ═══ -->
    <div class="crm-topbar">
        <div class="crm-topbar-left">
            <a href="<?= Url::to(['/admin/order/index']) ?>" class="admin-btn admin-btn-secondary admin-btn-sm" style="padding:4px 8px">
                <i class="bi bi-arrow-left"></i>
            </a>
            <span class="crm-order-num"><?= Html::encode($this->title) ?></span>
            <span class="crm-status-pill" id="crm-status-pill">
                <i class="bi bi-circle-fill" style="font-size:6px"></i>
                <?= Html::encode($statusLabel) ?>
            </span>
            <?php
            $slaStatus = $model->getSlaStatus();
            if ($slaStatus === 'overdue'): ?>
            <span style="background:#fee2e2;color:#dc2626;font-size:0.7rem;font-weight:700;padding:3px 8px;border-radius:6px;display:inline-flex;align-items:center;gap:4px;white-space:nowrap">
                <i class="bi bi-alarm-fill"></i> Просрочено
            </span>
            <?php elseif ($slaStatus === 'warn'): ?>
            <span style="background:#fef3c7;color:#d97706;font-size:0.7rem;font-weight:700;padding:3px 8px;border-radius:6px;display:inline-flex;align-items:center;gap:4px;white-space:nowrap">
                <i class="bi bi-exclamation-triangle-fill"></i> &le;2ч до дедлайна
            </span>
            <?php endif; ?>
            <span class="crm-order-date">
                <?= Yii::$app->formatter->asDatetime($model->created_at, 'short') ?>
                <?php if ($model->creator): ?> · <?= Html::encode($model->creator->username) ?><?php endif; ?>
            </span>
        </div>
        <div class="crm-topbar-actions">
            <form method="post" action="<?= Url::to(['/admin/order/change-status', 'id' => $model->id]) ?>" style="display:flex;align-items:center;gap:6px">
                <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>
                <select name="status" class="crm-status-select" onchange="guardStatusChange(this)" title="Изменить статус"
                        data-current-status="<?= Html::encode($model->status) ?>"
                        data-buyout-filled="<?= $model->isBuyoutFilled() ? '1' : '0' ?>">
                    <?php foreach ($statuses as $key => $label): ?>
                        <option value="<?= $key ?>" <?= $model->status == $key ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
            <a href="<?= Url::to(['/admin/order/pdf', 'id' => $model->id]) ?>" target="_blank" class="admin-btn admin-btn-secondary admin-btn-sm" title="Печать бланка">
                <i class="bi bi-printer"></i> Печать
            </a>
            <div class="crm-int-sep"></div>
            <?php if ($model->moysklad_id ?? null): ?>
            <a href="https://online.moysklad.ru/app/#customerorder/edit?id=<?= Html::encode($model->moysklad_id) ?>"
               target="_blank" class="crm-int-btn active" title="Открыть в МойСклад">
                <i class="bi bi-cloud-check"></i> МС
            </a>
            <?php endif; ?>
            <button type="button" class="crm-int-btn" id="btn-sync-ms" title="Синхронизировать с МойСклад" onclick="syncMoysklad(<?= $model->id ?>)">
                <i class="bi bi-arrow-repeat"></i> Синхронизировать с МС
            </button>
            <span id="ms-topbar-sync-result" style="font-size:0.7rem"></span>
            <?php if ($dpShipmentId): ?>
            <button type="button" class="crm-int-btn active" title="Таможня:ДП — обновить статус" onclick="refreshDPStatus(<?= $model->id ?>)"><i class="bi bi-box-arrow-right"></i> ДП</button>
            <?php else: ?>
            <button type="button" class="crm-int-btn" title="Отправить в ДП" onclick="sendToDP(<?= $model->id ?>)" <?= !$dpPassportOk ? 'disabled style="opacity:.5;cursor:not-allowed"' : '' ?>><i class="bi bi-box-arrow-right"></i> ДП</button>
            <?php endif; ?>
            <button type="button" class="crm-int-btn" title="История статусов" onclick="document.getElementById('crm-history-popup').classList.toggle('open')">
                <i class="bi bi-clock-history"></i>
                <?php if (!empty($model->history)): ?><span style="font-size:10px;background:#dbeafe;color:#1e40af;border-radius:10px;padding:0 5px;margin-left:2px"><?= count($model->history) ?></span><?php endif; ?>
            </button>
        </div>
    </div>

    <div class="crm-body">

        <!-- ═══ MAIN COLUMN ═══ -->
        <div class="crm-main">

            <!-- Products (unified expandable rows) -->
            <div class="crm-card">
                <div class="crm-card-head">
                    <h3><i class="bi bi-bag-check"></i> Состав заказа</h3>
                    <span style="font-size:0.75rem;color:var(--admin-text-secondary,#6b7280)"><?= count($model->orderItems) ?> позиций</span>
                </div>
                <?php
                $subtotal = 0;
                foreach ($model->orderItems as $_ti) { $subtotal += (float)($_ti->total ?? $_ti->price * $_ti->quantity); }
                $discountAmt  = (float)($model->discount ?? 0);
                $deliveryCost = (float)($model->delivery_cost ?? 0);
                $commissionRow = (float)($model->commission_amount ?? $model->commission_price ?? 0);
                ?>
                <table class="crm-items-table">
                    <thead>
                        <tr>
                            <th style="width:22px"></th>
                            <th style="width:44px"></th>
                            <th>Наименование</th>
                            <th style="width:70px">Кол-во</th>
                            <th style="width:90px;text-align:right">Цена</th>
                            <th style="width:100px;text-align:right">Сумма</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($model->orderItems as $idx => $item):
                            $expandId = 'ied-' . $item->id;
                        ?>
                        <tr class="item-main-row">
                            <td>
                                <button class="item-expand-btn" id="expbtn-<?= $item->id ?>"
                                        onclick="toggleItemDetail('<?= $expandId ?>', this)"
                                        title="Редактировать позицию">
                                    <i class="bi bi-chevron-down"></i>
                                </button>
                            </td>
                            <td>
                                <?php if (!empty($item->product) && !empty($item->product->getMainImageUrl())): ?>
                                    <img src="<?= Html::encode($item->product->getMainImageUrl()) ?>" class="item-img" alt="">
                                <?php else: ?>
                                    <div class="item-img" style="display:flex;align-items:center;justify-content:center">
                                        <i class="bi bi-box" style="color:#9ca3af;font-size:1rem"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($item->product_id)): ?>
                                    <a href="<?= Url::to(['/admin/product/view', 'id' => $item->product_id]) ?>"
                                       class="item-name" style="text-decoration:none;color:inherit"><?= Html::encode($item->product_name) ?></a>
                                <?php else: ?>
                                    <div class="item-name"><?= Html::encode($item->product_name) ?></div>
                                <?php endif; ?>
                                <div class="item-sku" style="display:flex;gap:8px;flex-wrap:wrap">
                                    <?php if (!empty($item->size)): ?>
                                        <span><?= Html::encode($item->size) ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($item->color)): ?>
                                        <span style="color:#6b7280"><?= Html::encode($item->color) ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($item->product_article)): ?>
                                        <span>Арт.: <?= Html::encode($item->product_article) ?></span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td style="color:var(--admin-text-secondary,#6b7280)"><?= $item->quantity ?> шт.</td>
                            <td style="text-align:right"><?= Yii::$app->formatter->asDecimal($item->price, 2) ?> Br</td>
                            <td style="text-align:right;font-weight:700"><?= Yii::$app->formatter->asDecimal($item->total, 2) ?> Br</td>
                        </tr>
                        <tr class="item-detail-row" id="<?= $expandId ?>" style="display:none">
                            <td colspan="6">
                                <div class="item-expand-panel" data-item-id="<?= $item->id ?>" data-order-id="<?= $model->id ?>">
                                    <div class="ief-grid">
                                        <div class="ief" style="flex:2;min-width:150px">
                                            <label>Название</label>
                                            <input type="text" class="admin-form-input" data-ifield="product_name"
                                                   value="<?= Html::encode($item->product_name) ?>">
                                        </div>
                                        <div class="ief" style="width:70px">
                                            <label>Размер</label>
                                            <input type="text" class="admin-form-input" data-ifield="size"
                                                   value="<?= Html::encode($item->size ?? '') ?>">
                                        </div>
                                        <div class="ief" style="width:80px">
                                            <label>Цвет</label>
                                            <input type="text" class="admin-form-input" data-ifield="color"
                                                   value="<?= Html::encode($item->color ?? '') ?>">
                                        </div>
                                        <div class="ief" style="width:90px">
                                            <label>Артикул</label>
                                            <input type="text" class="admin-form-input" data-ifield="product_article"
                                                   value="<?= Html::encode($item->product_article ?? '') ?>">
                                        </div>
                                        <div class="ief" style="width:55px">
                                            <label>Кол-во</label>
                                            <input type="number" class="admin-form-input" data-ifield="quantity"
                                                   value="<?= $item->quantity ?>" min="1">
                                        </div>
                                        <div class="ief" style="width:80px">
                                            <label>Цена, Br</label>
                                            <input type="number" step="0.01" class="admin-form-input" data-ifield="price"
                                                   value="<?= $item->price ?>">
                                        </div>
                                    </div>
                                    <div class="ief-section"><i class="bi bi-truck" style="color:#4338ca"></i> Данные ДоброПост</div>
                                    <div class="ief-grid">
                                        <div class="ief" style="flex:2;min-width:180px">
                                            <label>Ссылка на товар</label>
                                            <input type="url" class="admin-form-input" data-ofield="product_link"
                                                   value="<?= Html::encode($model->product_link ?? '') ?>">
                                        </div>
                                        <div class="ief" style="flex:2;min-width:180px">
                                            <label>Описание для таможни</label>
                                            <input type="text" class="admin-form-input" data-ofield="customs_description"
                                                   value="<?= Html::encode($model->customs_description ?? '') ?>">
                                        </div>
                                        <div class="ief" style="width:65px">
                                            <label>Кол-во (тамож.)</label>
                                            <input type="number" class="admin-form-input" data-ofield="item_quantity"
                                                   value="<?= Html::encode($model->item_quantity ?? '') ?>">
                                        </div>
                                        <div class="ief" style="width:75px">
                                            <label>Цена (CNY)</label>
                                            <input type="number" step="0.01" class="admin-form-input" data-ofield="item_price_cny"
                                                   value="<?= Html::encode($model->item_price_cny ?? '') ?>">
                                        </div>
                                        <div class="ief" style="width:75px">
                                            <label>Отпр. (CNY)</label>
                                            <input type="number" step="0.01" class="admin-form-input" data-ofield="shipment_value_cny"
                                                   value="<?= Html::encode($model->shipment_value_cny ?? '') ?>">
                                        </div>
                                    </div>
                                    <div class="ief-actions">
                                        <button type="button" class="admin-btn admin-btn-primary admin-btn-sm"
                                                onclick="saveItemRow(this)">
                                            <i class="bi bi-check2"></i> Сохранить
                                        </button>
                                        <button type="button" class="admin-btn admin-btn-secondary admin-btn-sm"
                                                onclick="toggleItemDetail('<?= $expandId ?>', document.getElementById('expbtn-<?= $item->id ?>'))">
                                            Отмена
                                        </button>
                                        <a href="<?= Url::to(['/admin/order/delete-item', 'id' => $item->id, 'order_id' => $model->id]) ?>"
                                           onclick="return confirm('Удалить эту позицию?')"
                                           style="margin-left:auto;font-size:0.75rem;color:#b91c1c;text-decoration:none">
                                            <i class="bi bi-trash3"></i> Удалить
                                        </a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div style="padding:8px 16px;border-top:1px solid var(--admin-border,#e5e7eb);font-size:0.8125rem">
                    <div style="display:flex;justify-content:flex-end;gap:8px;padding:3px 0;color:var(--admin-text-secondary,#6b7280)">
                        <span>Подытог:</span>
                        <span style="min-width:90px;text-align:right"><?= Yii::$app->formatter->asDecimal($subtotal, 2) ?> Br</span>
                    </div>
                    <?php if ($discountAmt > 0): ?>
                    <div style="display:flex;justify-content:flex-end;gap:8px;padding:3px 0;color:#059669">
                        <span>Скидка:</span>
                        <span style="min-width:90px;text-align:right">−<?= Yii::$app->formatter->asDecimal($discountAmt, 2) ?> Br</span>
                    </div>
                    <?php endif; ?>
                    <?php if ($deliveryCost > 0): ?>
                    <div style="display:flex;justify-content:flex-end;gap:8px;padding:3px 0">
                        <span>Доставка:</span>
                        <span style="min-width:90px;text-align:right"><?= Yii::$app->formatter->asDecimal($deliveryCost, 2) ?> Br</span>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="crm-total-row">
                    <span class="label">Итого к оплате:</span>
                    <span><?= Yii::$app->formatter->asDecimal($model->total_amount, 2) ?> Br</span>
                </div>
                <div style="padding:8px 16px 12px">
                    <button type="button" class="admin-btn admin-btn-secondary admin-btn-sm"
                            onclick="document.getElementById('addItemSection').style.display='block';this.style.display='none'"
                            id="showAddItemBtn">
                        <i class="bi bi-plus-lg"></i> Добавить позицию
                    </button>
                    <div id="addItemSection" style="display:none;margin-top:8px;padding:10px;background:var(--admin-surface-hover,#f9fafb);border:1px solid var(--admin-border,#e5e7eb);border-radius:8px">
                        <div class="ief-grid">
                            <div class="ief" style="flex:2;min-width:150px">
                                <label>Название *</label>
                                <input type="text" class="admin-form-input" id="newItemName" placeholder="Название товара">
                            </div>
                            <div class="ief" style="width:55px">
                                <label>Кол-во</label>
                                <input type="number" class="admin-form-input" id="newItemQty" value="1" min="1">
                            </div>
                            <div class="ief" style="width:80px">
                                <label>Цена, Br *</label>
                                <input type="number" step="0.01" class="admin-form-input" id="newItemPrice" placeholder="0.00">
                            </div>
                            <div class="ief" style="width:70px">
                                <label>Размер</label>
                                <input type="text" class="admin-form-input" id="newItemSize">
                            </div>
                        </div>
                        <div class="ief-actions" style="margin-top:6px">
                            <button type="button" class="admin-btn admin-btn-primary admin-btn-sm"
                                    onclick="submitAddItem(<?= $model->id ?>)">
                                <i class="bi bi-plus-lg"></i> Добавить
                            </button>
                            <button type="button" class="admin-btn admin-btn-secondary admin-btn-sm"
                                    onclick="document.getElementById('addItemSection').style.display='none';document.getElementById('showAddItemBtn').style.display=''">
                                Отмена
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Данные для ДП: Получатель + Адрес + Товарная информация -->
            <div class="crm-card">
                <div class="crm-card-head">
                    <h3><i class="bi bi-box-seam"></i> Данные для ДоброПост</h3>
                    <span style="font-size:0.7rem;padding:2px 8px;border-radius:6px;background:#eef2ff;color:#4338ca;font-weight:700"><i class="bi bi-truck" style="font-size:0.75rem;color:#4338ca"></i> ДП</span>
                    <button type="button" class="admin-btn admin-btn-secondary admin-btn-sm" onclick="autoFillDp(<?= $model->id ?>)" style="margin-left:8px;font-size:0.7rem;padding:2px 10px" title="Заполнить пустые поля ДП из данных заказа и профиля клиента"><i class="bi bi-lightning"></i> Авто-заполнить из заказа</button>
                </div>
                <div class="crm-card-body">
                    <!-- Блок: Получатель и адрес -->
                    <div style="font-size:0.7rem;font-weight:700;color:var(--admin-text-secondary,#6b7280);text-transform:uppercase;letter-spacing:.04em;margin-bottom:8px"><i class="bi bi-geo-alt"></i> Получатель и адрес</div>
                    <div class="crm-info-grid">
                        <div class="crm-field">
                            <div class="crm-field-label">Фамилия <span title="Обязательно для Таможня:ДП" style="cursor:help;font-size:0.75rem"><i class="bi bi-truck" style="font-size:0.75rem;color:#4338ca"></i></span></div>
                            <div class="crm-editable" data-field="recipient_last_name" data-id="<?= $model->id ?>" onclick="startEdit(this)"><?= !empty($model->recipient_last_name) ? Html::encode($model->recipient_last_name) : '<span class="crm-editable-empty">—</span>' ?></div>
                        </div>
                        <div class="crm-field">
                            <div class="crm-field-label">Имя <span title="Обязательно для Таможня:ДП" style="cursor:help;font-size:0.75rem"><i class="bi bi-truck" style="font-size:0.75rem;color:#4338ca"></i></span></div>
                            <div class="crm-editable" data-field="recipient_first_name" data-id="<?= $model->id ?>" onclick="startEdit(this)"><?= !empty($model->recipient_first_name) ? Html::encode($model->recipient_first_name) : '<span class="crm-editable-empty">—</span>' ?></div>
                        </div>
                        <div class="crm-field">
                            <div class="crm-field-label">Отчество</div>
                            <div class="crm-editable" data-field="recipient_middle_name" data-id="<?= $model->id ?>" onclick="startEdit(this)"><?= !empty($model->recipient_middle_name) ? Html::encode($model->recipient_middle_name) : '<span class="crm-editable-empty">—</span>' ?></div>
                        </div>
                        <div class="crm-field">
                            <div class="crm-field-label">Город <span title="Обязательно для Таможня:ДП" style="cursor:help;font-size:0.75rem"><i class="bi bi-truck" style="font-size:0.75rem;color:#4338ca"></i></span></div>
                            <div class="crm-editable" data-field="city" data-id="<?= $model->id ?>" onclick="startEdit(this)"><?= !empty($model->city) ? Html::encode($model->city) : '<span class="crm-editable-empty">—</span>' ?></div>
                        </div>
                        <div class="crm-field">
                            <div class="crm-field-label">Регион <span title="Обязательно для Таможня:ДП" style="cursor:help;font-size:0.75rem"><i class="bi bi-truck" style="font-size:0.75rem;color:#4338ca"></i></span></div>
                            <div class="crm-editable" data-field="region" data-id="<?= $model->id ?>" onclick="startEdit(this)"><?= !empty($model->region) ? Html::encode($model->region) : '<span class="crm-editable-empty">—</span>' ?></div>
                        </div>
                        <div class="crm-field">
                            <div class="crm-field-label">Индекс <span title="Обязательно для Таможня:ДП" style="cursor:help;font-size:0.75rem"><i class="bi bi-truck" style="font-size:0.75rem;color:#4338ca"></i></span></div>
                            <div class="crm-editable" data-field="postal_code" data-id="<?= $model->id ?>" onclick="startEdit(this)"><?= !empty($model->postal_code) ? Html::encode($model->postal_code) : '<span class="crm-editable-empty">—</span>' ?></div>
                        </div>
                        <div class="crm-field" style="grid-column: span 2">
                            <div class="crm-field-label">Полный адрес <span title="Обязательно для Таможня:ДП" style="cursor:help;font-size:0.75rem"><i class="bi bi-truck" style="font-size:0.75rem;color:#4338ca"></i></span></div>
                            <div class="crm-editable" data-field="full_address" data-id="<?= $model->id ?>" onclick="startEdit(this)"><?= !empty($model->full_address) ? Html::encode($model->full_address) : '<span class="crm-editable-empty">—</span>' ?></div>
                        </div>
                    </div>

                    <div style="font-size:0.72rem;color:var(--admin-text-secondary,#9ca3af);margin-top:8px;padding-top:8px;border-top:1px dashed var(--admin-border,#e5e7eb)">
                        <i class="bi bi-info-circle"></i> Товарная информация редактируется в составе заказа (кнопка <i class="bi bi-chevron-down"></i> рядом с позицией).
                    </div>
                    <?php if (!empty($model->sneakerhead_order_link)): ?>
                    <div style="margin-top:8px">
                        <div class="crm-field-label" style="margin-bottom:4px">Ссылка Sneakerhead</div>
                        <a href="<?= Html::encode($model->sneakerhead_order_link) ?>" target="_blank" style="font-size:0.8rem;word-break:break-all"><?= Html::encode($model->sneakerhead_order_link) ?></a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Passport data -->
            <div class="crm-card">
                <div class="crm-card-head">
                    <h3><i class="bi bi-person-vcard"></i> Паспортные данные</h3>
                    <?php if ($dpPassportOk): ?>
                    <span style="font-size:0.7rem;padding:2px 8px;border-radius:6px;background:#d1fae5;color:#065f46;font-weight:700"><i class="bi bi-check-circle"></i> Заполнен</span>
                    <?php else: ?>
                    <span style="font-size:0.7rem;padding:2px 8px;border-radius:6px;background:#fee2e2;color:#991b1b;font-weight:700"><i class="bi bi-exclamation-triangle"></i> Не заполнен</span>
                    <?php endif; ?>
                </div>
                <div class="crm-card-body">
                    <div class="crm-info-grid">
                        <div class="crm-field">
                            <div class="crm-field-label">Серия <span title="Обязательно для Таможня:ДП" style="cursor:help;font-size:0.75rem"><i class="bi bi-truck" style="font-size:0.75rem;color:#4338ca"></i></span></div>
                            <div class="crm-editable" data-field="passport_series" data-id="<?= $model->id ?>" onclick="startEdit(this)" style="font-family:monospace"><?= !empty($model->passport_series) ? Html::encode($model->passport_series) : '<span class="crm-editable-empty">—</span>' ?></div>
                        </div>
                        <div class="crm-field">
                            <div class="crm-field-label">Номер <span title="Обязательно для Таможня:ДП" style="cursor:help;font-size:0.75rem"><i class="bi bi-truck" style="font-size:0.75rem;color:#4338ca"></i></span></div>
                            <div class="crm-editable" data-field="passport_number" data-id="<?= $model->id ?>" onclick="startEdit(this)" style="font-family:monospace"><?= !empty($model->passport_number) ? Html::encode($model->passport_number) : '<span class="crm-editable-empty">—</span>' ?></div>
                        </div>
                        <div class="crm-field">
                            <div class="crm-field-label">Дата выдачи</div>
                            <div class="crm-editable" data-field="passport_issue_date" data-id="<?= $model->id ?>" onclick="startEdit(this)"><?= !empty($model->passport_issue_date) ? Html::encode($model->passport_issue_date) : '<span class="crm-editable-empty">—</span>' ?></div>
                        </div>
                        <div class="crm-field">
                            <div class="crm-field-label">Дата рождения</div>
                            <div class="crm-editable" data-field="birth_date" data-id="<?= $model->id ?>" onclick="startEdit(this)"><?= !empty($model->birth_date) ? Html::encode($model->birth_date) : '<span class="crm-editable-empty">—</span>' ?></div>
                        </div>
                        <div class="crm-field">
                            <div class="crm-field-label">ИНН <span title="Обязательно для Таможня:ДП (РФ)" style="cursor:help;font-size:0.75rem"><i class="bi bi-truck" style="font-size:0.75rem;color:#4338ca"></i></span></div>
                            <div class="crm-editable" data-field="inn" data-id="<?= $model->id ?>" onclick="startEdit(this)" style="font-family:monospace"><?= !empty($model->inn) ? Html::encode($model->inn) : '<span class="crm-editable-empty">—</span>' ?></div>
                        </div>
                        <?php if (!empty($model->passport_submitted_at)): ?>
                        <div class="crm-field">
                            <div class="crm-field-label">Отправлен</div>
                            <div class="crm-field-val" style="color:#059669"><?= Html::encode(date('d.m.Y H:i', strtotime($model->passport_submitted_at))) ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($model->passport_validated)): ?>
                        <div class="crm-field" style="grid-column: span 2">
                            <div style="padding:5px 10px;background:#d1fae5;border-radius:6px;font-size:0.75rem;color:#065f46;font-weight:600"><i class="bi bi-patch-check"></i> Подтверждён ДоброПостом</div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Notes -->
            <div class="crm-card">
                <div class="crm-card-head">
                    <h3><i class="bi bi-chat-square-text"></i> Заметки команды</h3>
                </div>
                <div id="order-notes-list" style="padding:12px 16px;display:flex;flex-direction:column;gap:8px">
                    <?php foreach (($model->notes ?? []) as $note): ?>
                    <div style="background:var(--admin-surface-hover,#f9fafb);border:1px solid var(--admin-border,#e5e7eb);border-radius:8px;padding:8px 12px">
                        <div style="display:flex;justify-content:space-between;margin-bottom:3px">
                            <strong style="font-size:0.75rem"><?= Html::encode($note->author->username ?? 'Система') ?></strong>
                            <span style="font-size:0.7rem;color:var(--admin-text-secondary,#9ca3af)"><?= Yii::$app->formatter->asDatetime($note->created_at) ?></span>
                        </div>
                        <p style="margin:0;font-size:0.8125rem"><?= Html::encode($note->text) ?></p>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($model->notes)): ?>
                    <p style="color:var(--admin-text-secondary,#9ca3af);font-size:0.8125rem;margin:0">Заметок пока нет.</p>
                    <?php endif; ?>
                </div>
                <div class="crm-note-form">
                    <textarea id="new-note-text" class="crm-note-textarea" rows="2" placeholder="Добавить заметку..."></textarea>
                    <button class="admin-btn admin-btn-primary" onclick="addOrderNote(<?= $model->id ?>)" style="padding:6px 10px;align-self:flex-end">
                        <i class="bi bi-send" style="font-size:1rem;color:inherit"></i>
                    </button>
                </div>
            </div>

            <!-- Выкуп (#12) -->
            <?php
            $buyoutFilled = $model->isBuyoutFilled();
            $buyoutUsers  = [];
            try {
                $buyoutUsers = \app\backend\modules\admin\models\User::find()
                    ->select(['id', 'username'])->where(['is_active' => 1])
                    ->orderBy(['username' => SORT_ASC])->asArray()->all();
            } catch (\Throwable $_e) {}
            ?>
            <div class="crm-card" id="buyout-card">
                <div class="crm-card-head">
                    <h3><i class="bi bi-bag-check"></i> Выкуп</h3>
                    <span id="buyout-status-badge" style="<?= $buyoutFilled ? 'background:#d1fae5;color:#065f46' : 'background:#fef3c7;color:#92400e' ?>;font-size:0.7rem;font-weight:700;padding:2px 8px;border-radius:6px">
                        <?= $buyoutFilled ? '<i class="bi bi-check-circle-fill"></i> Заполнен' : '<i class="bi bi-exclamation-triangle-fill"></i> Не заполнен' ?>
                    </span>
                </div>
                <div class="crm-card-body">
                    <?php if ($model->status === 'confirmed_and_paid' && !$buyoutFilled): ?>
                    <div style="margin-bottom:10px;padding:8px 10px;background:#fef3c7;border-radius:8px;font-size:0.8125rem;color:#92400e;display:flex;align-items:center;gap:6px">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        Обязательно — заполните выкуп перед переводом в «Заказано»
                    </div>
                    <?php endif; ?>
                    <div class="crm-info-grid" style="grid-template-columns:repeat(2,1fr);gap:8px;margin-bottom:10px">
                        <div class="crm-field">
                            <div class="crm-field-label">Закупочная цена</div>
                            <div style="display:flex;gap:4px">
                                <input type="number" id="inp-purchase-cost" class="admin-form-input"
                                       style="font-size:0.8125rem;padding:4px 8px;flex:1"
                                       value="<?= Html::encode($model->purchase_cost ?? '') ?>" min="0" step="0.01" placeholder="0.00">
                                <select id="inp-purchase-currency" class="admin-form-input" style="font-size:0.8125rem;padding:4px 6px;width:76px">
                                    <?php foreach (['CNY', 'USD', 'EUR', 'RUB', 'BYN'] as $_cur): ?>
                                    <option value="<?= $_cur ?>" <?= ($model->purchase_currency ?? 'CNY') === $_cur ? 'selected' : '' ?>><?= $_cur ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="crm-field">
                            <div class="crm-field-label">Дата выкупа</div>
                            <input type="datetime-local" id="inp-purchase-date" class="admin-form-input"
                                   style="font-size:0.8125rem;padding:4px 8px"
                                   value="<?= $model->purchase_date ? Html::encode(date('Y-m-d\TH:i', strtotime($model->purchase_date))) : '' ?>">
                        </div>
                        <div class="crm-field">
                            <div class="crm-field-label">Трек источника</div>
                            <input type="text" id="inp-china-track" class="admin-form-input"
                                   style="font-size:0.8125rem;padding:4px 8px"
                                   value="<?= Html::encode($model->china_track_number ?? '') ?>" placeholder="CN...">
                        </div>
                        <div class="crm-field">
                            <div class="crm-field-label">Ответственный</div>
                            <select id="inp-purchase-user" class="admin-form-input" style="font-size:0.8125rem;padding:4px 6px">
                                <option value="">— выбрать —</option>
                                <?php foreach ($buyoutUsers as $_bu): ?>
                                <option value="<?= (int)$_bu['id'] ?>" <?= (int)($model->purchase_user_id ?? 0) === (int)$_bu['id'] ? 'selected' : '' ?>>
                                    <?= Html::encode($_bu['username']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="crm-field" style="margin-bottom:10px">
                        <div class="crm-field-label">Скрин чека</div>
                        <?php if (!empty($model->purchase_receipt_url)): ?>
                        <div style="margin-bottom:6px;font-size:0.8125rem">
                            <a href="<?= Html::encode($model->purchase_receipt_url) ?>" target="_blank" style="color:#2563eb">
                                <i class="bi bi-file-earmark-image"></i> Текущий скрин
                            </a>
                        </div>
                        <?php endif; ?>
                        <input type="file" id="inp-receipt-file" accept="image/*,.pdf" style="font-size:0.8125rem">
                    </div>
                    <button type="button" class="admin-btn admin-btn-primary admin-btn-sm" style="width:100%;justify-content:center"
                            onclick="saveBuyout(<?= $model->id ?>)" id="btn-save-buyout">
                        <i class="bi bi-floppy-fill"></i> Сохранить выкуп
                    </button>
                    <div id="buyout-save-result" style="font-size:0.75rem;margin-top:6px;text-align:center"></div>
                </div>
            </div>

            <!-- Маржинальность -->
            <?php
            $salePrice       = (float)($model->total_amount ?? 0);
            $purchasePrice   = (float)($model->product_price ?? 0);
            $logisticsPrice  = (float)($model->logistics_price ?? 0);
            $commissionAmt   = (float)($model->commission_amount ?? $model->commission_price ?? 0);
            $insuranceAmt    = (float)($model->insurance_amount ?? 0);
            $profit = $salePrice - $purchasePrice - $logisticsPrice - $commissionAmt - $insuranceAmt;
            $margin = $salePrice > 0 ? round($profit / $salePrice * 100, 1) : 0;
            ?>
            <div class="crm-card">
                <div class="crm-card-head">
                    <h3><i class="bi bi-graph-up-arrow"></i> Маржинальность</h3>
                    <?php if ($purchasePrice <= 0 && $logisticsPrice <= 0): ?>
                    <span style="font-size:0.75rem;font-weight:600;padding:3px 8px;border-radius:6px;background:#f3f4f6;color:#6b7280">
                        <i class="bi bi-question-circle"></i> Недостаточно данных
                    </span>
                    <?php else: ?>
                    <span style="font-size:0.9rem;font-weight:800;color:<?= $profit >= 0 ? '#059669' : '#dc2626' ?>">
                        <?= ($profit >= 0 ? '+' : '') . Yii::$app->formatter->asDecimal($profit, 2) ?> Br
                        <span style="font-size:0.7rem;font-weight:600;opacity:.8">(<?= $margin ?>%)</span>
                    </span>
                    <?php endif; ?>
                </div>
                <div class="crm-card-body">
                    <div class="crm-info-grid" style="grid-template-columns:repeat(3,1fr);gap:8px">
                        <div class="crm-field">
                            <div class="crm-field-label">Продажа</div>
                            <div class="crm-editable" data-field="total_amount" data-id="<?= $model->id ?>" onclick="startEdit(this)" style="color:#059669;font-weight:700"><?= Yii::$app->formatter->asDecimal($salePrice, 2) ?> Br</div>
                        </div>
                        <div class="crm-field">
                            <div class="crm-field-label">Закупка</div>
                            <div class="crm-editable" data-field="product_price" data-id="<?= $model->id ?>" onclick="startEdit(this)"><?= $purchasePrice ? Yii::$app->formatter->asDecimal($purchasePrice, 2) . ' Br' : '<span class="crm-editable-empty">—</span>' ?></div>
                        </div>
                        <div class="crm-field">
                            <div class="crm-field-label">Логистика</div>
                            <div class="crm-editable" data-field="logistics_price" data-id="<?= $model->id ?>" onclick="startEdit(this)"><?= $logisticsPrice ? Yii::$app->formatter->asDecimal($logisticsPrice, 2) . ' Br' : '<span class="crm-editable-empty">—</span>' ?></div>
                        </div>
                        <div class="crm-field">
                            <div class="crm-field-label">Комиссия</div>
                            <div class="crm-editable" data-field="commission_amount" data-id="<?= $model->id ?>" onclick="startEdit(this)"><?= $commissionAmt ? Yii::$app->formatter->asDecimal($commissionAmt, 2) . ' Br' : '<span class="crm-editable-empty">—</span>' ?></div>
                        </div>
                        <div class="crm-field">
                            <div class="crm-field-label">Страховка</div>
                            <div class="crm-editable" data-field="insurance_amount" data-id="<?= $model->id ?>" onclick="startEdit(this)"><?= $insuranceAmt ? Yii::$app->formatter->asDecimal($insuranceAmt, 2) . ' Br' : '<span class="crm-editable-empty">—</span>' ?></div>
                        </div>
                        <?php if (!empty($model->tariff_weight_kg)): ?>
                        <div class="crm-field">
                            <div class="crm-field-label">Вес (кг)</div>
                            <div class="crm-field-val"><?= Html::encode($model->tariff_weight_kg) ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Файлы -->
            <div class="crm-card">
                <div class="crm-card-head">
                    <h3><i class="bi bi-paperclip"></i> Файлы</h3>
                </div>
                <div class="crm-card-body">
                    <?php if (!empty($model->payment_proof)): ?>
                    <div style="display:flex;align-items:center;gap:8px;padding:6px 0;border-bottom:1px solid var(--admin-border,#f3f4f6);margin-bottom:10px">
                        <i class="bi bi-file-earmark-image" style="color:#6b7280;flex-shrink:0"></i>
                        <a href="<?= Html::encode($model->payment_proof) ?>" target="_blank" style="font-size:0.8125rem;flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:inherit">Скриншот оплаты</a>
                        <span style="font-size:0.7rem;color:var(--admin-text-secondary,#9ca3af)">оплата</span>
                    </div>
                    <?php endif; ?>
                    <div class="crm-file-drop" onclick="document.getElementById('file-upload-input').click()">
                        <i class="bi bi-cloud-upload" style="font-size:1.5rem;display:block;margin-bottom:6px"></i>
                        Перетащите файлы или нажмите для загрузки
                    </div>
                    <input type="file" id="file-upload-input" style="display:none" multiple onchange="uploadOrderFiles(this, <?= $model->id ?>)">
                    <div id="file-upload-result" style="font-size:0.75rem;margin-top:6px"></div>
                </div>
            </div>

            <!-- Комментарий -->
            <div class="crm-card">
                <div class="crm-card-head">
                    <h3><i class="bi bi-chat-left-text"></i> Комментарий</h3>
                </div>
                <div class="crm-card-body">
                    <textarea id="order-comment-ta" class="admin-form-input" rows="3"
                              style="width:100%;font-size:.8125rem;resize:vertical"
                              placeholder="Добавить комментарий к заказу..."
                              onblur="if(this.value !== this.dataset.orig){saveField('comment',this.value);this.dataset.orig=this.value;}"
                              onfocus="this.dataset.orig=this.value"
                    ><?= Html::encode($model->comment ?? '') ?></textarea>
                </div>
            </div>

        </div><!-- /.crm-main -->

        <!-- ═══ SIDEBAR ═══ -->
        <div class="crm-sidebar">

            <!-- Источник — compact dropdown -->
            <div style="display:flex;align-items:center;gap:8px;padding:8px 14px;background:var(--admin-surface-2,#f8f9fa);border:1px solid var(--admin-border,#e5e7eb);border-radius:10px;font-size:0.8125rem">
                <i class="bi bi-funnel" style="color:var(--admin-text-secondary,#6b7280);flex-shrink:0;font-size:0.875rem"></i>
                <span style="color:var(--admin-text-secondary,#6b7280);white-space:nowrap">Источник:</span>
                <?php if (!empty($orderSources)): ?>
                <select class="admin-form-input" style="flex:1;font-size:0.8125rem;padding:3px 8px;border:none;background:transparent;font-weight:600;color:var(--admin-text-primary,#111);cursor:pointer;min-width:0"
                        onchange="saveField('source', this.value)">
                    <option value="">— не указан —</option>
                    <?php
                    $currentSource = $model->source ?? '';
                    $sourceInList = in_array($currentSource, $orderSources);
                    foreach ($orderSources as $src): ?>
                    <option value="<?= Html::encode($src) ?>" <?= $currentSource === $src ? 'selected' : '' ?>><?= Html::encode($src) ?></option>
                    <?php endforeach; ?>
                    <?php if ($currentSource && !$sourceInList): ?>
                    <option value="<?= Html::encode($currentSource) ?>" selected><?= Html::encode($currentSource) ?></option>
                    <?php endif; ?>
                </select>
                <?php else: ?>
                <div class="crm-editable" data-field="source" data-id="<?= $model->id ?>" onclick="startEdit(this)" style="flex:1;min-width:0;font-weight:600;color:var(--admin-text-primary,#111)">
                    <?= !empty($model->source) ? Html::encode($model->source) : '<span class="crm-editable-empty" style="font-weight:400">—</span>' ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Customer card -->
            <div class="crm-card">
                <div class="crm-card-head">
                    <h3><i class="bi bi-person-circle"></i> Покупатель
                        <?php if ($customer): ?><span style="font-size:.65rem;font-weight:500;color:var(--admin-text-secondary,#9ca3af);margin-left:6px">/ привязанный аккаунт</span><?php endif; ?>
                    </h3>
                    <?php if ($customer): ?>
                    <a href="<?= Url::to(['/admin/customer/view', 'id' => $customer->id]) ?>" class="admin-btn admin-btn-secondary admin-btn-sm" style="font-size:10px;padding:3px 8px" title="Открыть профиль">
                        <i class="bi bi-box-arrow-up-right"></i>
                    </a>
                    <?php endif; ?>
                </div>
                <div class="crm-customer-card">
                    <?php if ($customer): ?>
                    <?php $customerFullName = trim(($customer->last_name ?? '') . ' ' . ($customer->first_name ?? '')) ?: ($customer->email ?? '—'); ?>
                    <div style="display:flex;gap:10px;align-items:flex-start;margin-bottom:10px">
                        <div class="crm-customer-avatar"><?= mb_strtoupper(mb_substr($customer->first_name ?? $customer->email ?? 'C', 0, 1)) ?></div>
                        <div style="flex:1;min-width:0">
                            <a href="<?= Url::to(['/admin/customer/view', 'id' => $customer->id]) ?>" style="font-weight:700;font-size:0.875rem;color:var(--admin-text-primary,#111);text-decoration:none;display:block;margin-bottom:3px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis" title="<?= Html::encode($customerFullName) ?>">
                                <?= Html::encode($customerFullName) ?>
                            </a>
                            <div class="crm-customer-meta">
                                <?php $phone = $customer->phone ?? $model->client_phone; ?>
                                <?php if ($phone): ?>
                                <span><i class="bi bi-telephone" style="width:12px"></i>
                                    <a href="tel:<?= Html::encode($phone) ?>"><?= Html::encode($phone) ?></a>
                                </span>
                                <?php endif; ?>
                                <?php $email = $customer->email ?? $model->client_email; ?>
                                <?php if ($email): ?>
                                <span><i class="bi bi-envelope" style="width:12px"></i>
                                    <a href="mailto:<?= Html::encode($email) ?>" style="word-break:break-all"><?= Html::encode($email) ?></a>
                                </span>
                                <?php endif; ?>
                                <?php if (!empty($customer->created_at)): ?>
                                <span style="margin-top:2px"><i class="bi bi-calendar3" style="width:12px"></i>
                                    Зарегистрирован <?= date('d.m.Y', is_numeric($customer->created_at) ? $customer->created_at : strtotime($customer->created_at)) ?>
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px;margin-bottom:10px">
                        <div style="background:var(--admin-surface-hover,#f9fafb);border-radius:8px;padding:8px 10px;text-align:center">
                            <div style="font-size:1.125rem;font-weight:800;color:var(--admin-text-primary,#111)"><?= $customer->orders_count ?? '—' ?></div>
                            <div style="font-size:0.65rem;color:var(--admin-text-secondary,#6b7280);text-transform:uppercase;letter-spacing:.04em">Заказов</div>
                        </div>
                        <div style="background:var(--admin-surface-hover,#f9fafb);border-radius:8px;padding:8px 10px;text-align:center">
                            <div style="font-size:1.125rem;font-weight:800;color:var(--admin-text-primary,#111)"><?= $customer->total_spent ? number_format($customer->total_spent, 0, '.', ' ') . ' Br' : '—' ?></div>
                            <div style="font-size:0.65rem;color:var(--admin-text-secondary,#6b7280);text-transform:uppercase;letter-spacing:.04em">Потрачено</div>
                        </div>
                    </div>
                    <button type="button" class="admin-btn admin-btn-secondary admin-btn-sm" style="width:100%;justify-content:center;font-size:12px"
                            onclick="openCustomerQuickView(<?= $customer->id ?>)">
                        <i class="bi bi-person-lines-fill"></i> Подробнее о клиенте
                    </button>
                    <?php else: ?>
                    <div style="display:flex;gap:10px;align-items:flex-start;margin-bottom:10px">
                        <div class="crm-customer-avatar" style="background:linear-gradient(135deg,#94a3b8,#64748b)"><?= mb_strtoupper(mb_substr($model->client_name ?? 'G', 0, 1)) ?></div>
                        <div style="flex:1;min-width:0">
                            <div style="font-weight:700;font-size:0.875rem;color:var(--admin-text-primary,#111);margin-bottom:3px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                                <?= Html::encode($model->client_name ?: 'Гость') ?>
                                <span style="font-size:0.7rem;font-weight:400;color:var(--admin-text-secondary,#9ca3af);margin-left:4px">без профиля</span>
                            </div>
                            <div class="crm-customer-meta">
                                <?php if ($model->client_phone): ?>
                                <span><i class="bi bi-telephone" style="width:12px"></i>
                                    <a href="tel:<?= Html::encode($model->client_phone) ?>"><?= Html::encode($model->client_phone) ?></a>
                                </span>
                                <?php endif; ?>
                                <?php if ($model->client_email): ?>
                                <span><i class="bi bi-envelope" style="width:12px"></i>
                                    <a href="mailto:<?= Html::encode($model->client_email) ?>" style="word-break:break-all"><?= Html::encode($model->client_email) ?></a>
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php if ($model->client_email || $model->client_phone): ?>
                    <button type="button" class="admin-btn admin-btn-secondary admin-btn-sm" style="width:100%;justify-content:center;font-size:12px"
                            onclick="createCustomerFromOrder(<?= $model->id ?>)">
                        <i class="bi bi-person-plus"></i> Создать профиль клиента
                    </button>
                    <?php endif; ?>
                    <?php endif; ?>

                    <!-- Editable order-level client fields -->
                    <div style="margin-top:12px;border-top:1px solid var(--admin-border,#e5e7eb);padding-top:12px">
                        <?php
                        // Field overlap analysis: detect mismatch between order client fields and Customer profile
                        $orderPhone = trim($model->client_phone ?? '');
                        $customerPhone = $customer ? trim($customer->phone ?? '') : '';
                        $orderEmail = strtolower(trim($model->client_email ?? ''));
                        $customerEmail = $customer ? strtolower(trim($customer->email ?? '')) : '';
                        $hasMismatch = $customer && (
                            ($orderPhone && $customerPhone && $orderPhone !== $customerPhone) ||
                            ($orderEmail && $customerEmail && $orderEmail !== $customerEmail)
                        );
                        ?>
                        <?php if ($hasMismatch): ?>
                        <div style="margin-bottom:8px;padding:5px 8px;background:var(--admin-warning-bg,#fef9c3);border-radius:6px;font-size:.72rem;color:var(--admin-warning,#d97706);display:flex;align-items:center;gap:5px">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                            Данные заказа отличаются от профиля клиента
                        </div>
                        <?php endif; ?>
                        <div style="font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--admin-text-secondary,#9ca3af);margin-bottom:8px">Данные покупателя (snapshot заказа)</div>
                        <div class="crm-info-grid">
                            <div class="crm-field">
                                <div class="crm-field-label">ФИО</div>
                                <div class="crm-editable" data-field="client_name" data-id="<?= $model->id ?>" onclick="startEdit(this)">
                                    <?= $model->client_name ? Html::encode($model->client_name) : '<span class="crm-editable-empty">—</span>' ?>
                                </div>
                            </div>
                            <div class="crm-field">
                                <div class="crm-field-label">Телефон</div>
                                <div class="crm-editable" data-field="client_phone" data-id="<?= $model->id ?>" onclick="startEdit(this)">
                                    <?php if ($model->client_phone): ?>
                                        <a href="tel:<?= Html::encode($model->client_phone) ?>" onclick="event.stopPropagation()" style="color:inherit;text-decoration:none"><?= Html::encode($model->client_phone) ?></a>
                                    <?php else: ?><span class="crm-editable-empty">—</span><?php endif; ?>
                                </div>
                            </div>
                            <div class="crm-field">
                                <div class="crm-field-label">Email</div>
                                <div class="crm-editable" data-field="client_email" data-id="<?= $model->id ?>" onclick="startEdit(this)">
                                    <?php if ($model->client_email): ?>
                                        <a href="mailto:<?= Html::encode($model->client_email) ?>" onclick="event.stopPropagation()" style="color:inherit;text-decoration:none;font-size:.8rem;word-break:break-all"><?= Html::encode($model->client_email) ?></a>
                                    <?php else: ?><span class="crm-editable-empty">—</span><?php endif; ?>
                                </div>
                            </div>
                            <div class="crm-field">
                                <div class="crm-field-label">Срок доставки</div>
                                <?php $earlyStatuses = ['new', 'processing', 'pending', 'new_order']; ?>
                                <?php if (!$model->delivery_date && in_array($model->status, $earlyStatuses)): ?>
                                <div class="crm-editable" data-field="delivery_date" data-id="<?= $model->id ?>" onclick="startEdit(this)">
                                    <span class="crm-editable-empty" style="font-style:italic">Не задан (новый заказ)</span>
                                </div>
                                <?php elseif (!$model->delivery_date): ?>
                                <div class="crm-editable" data-field="delivery_date" data-id="<?= $model->id ?>" onclick="startEdit(this)">
                                    <span class="crm-editable-empty">—</span>
                                </div>
                                <?php else: ?>
                                <div class="crm-editable" data-field="delivery_date" data-id="<?= $model->id ?>" onclick="startEdit(this)">
                                    <?= Html::encode($model->delivery_date) ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment -->
            <div class="crm-card">
                <div class="crm-card-head">
                    <h3><i class="bi bi-credit-card"></i> Оплата</h3>
                    <?php
                    $paymentStatus = !empty($model->payment_uploaded_at) ? 'paid' : 'pending';
                    $payBg = $paymentStatus === 'paid' ? '#d1fae5' : '#fef3c7';
                    $payColor = $paymentStatus === 'paid' ? '#065f46' : '#92400e';
                    $payLabel = $paymentStatus === 'paid' ? 'Оплачен' : 'Ожидает оплаты';
                    ?>
                    <span style="background:<?= $payBg ?>;color:<?= $payColor ?>;font-size:0.7rem;padding:2px 8px;border-radius:6px;font-weight:700"><?= $payLabel ?></span>
                </div>
                <div class="crm-card-body">
                    <div class="crm-info-grid" style="grid-template-columns:1fr 1fr;gap:8px;margin-bottom:10px">
                        <div class="crm-field">
                            <div class="crm-field-label">Сумма</div>
                            <div style="font-size:1rem;font-weight:800;color:var(--admin-text-primary,#111)"><?= Yii::$app->formatter->asDecimal($model->total_amount, 2) ?> Br</div>
                        </div>
                        <div class="crm-field">
                            <div class="crm-field-label">Способ</div>
                            <select class="admin-form-input" style="font-size:0.75rem;padding:3px 6px;width:100%"
                                    onchange="saveField('payment_method', this.value)">
                                <option value="">— не выбран —</option>
                                <?php
                                $pmOptions = [];
                                if (!empty($paymentMethods) && is_array($paymentMethods)) {
                                    foreach ($paymentMethods as $pm) {
                                        $pmOptions[$pm['id'] ?? $pm['name']] = $pm['name'];
                                    }
                                }
                                // Always include current value if not in list
                                if (!empty($model->payment_method) && !isset($pmOptions[$model->payment_method])) {
                                    $pmOptions[$model->payment_method] = $model->payment_method;
                                }
                                foreach ($pmOptions as $pmVal => $pmLabel): ?>
                                <option value="<?= Html::encode($pmVal) ?>" <?= ($model->payment_method ?? '') === $pmVal ? 'selected' : '' ?>>
                                    <?= Html::encode($pmLabel) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <?php if ($model->payment_proof): ?>
                    <a href="<?= $model->payment_proof ?>" target="_blank" class="admin-btn admin-btn-secondary admin-btn-sm" style="width:100%;justify-content:center">
                        <i class="bi bi-file-earmark-image"></i> Скриншот оплаты
                    </a>
                    <?php if ($model->offer_accepted): ?>
                    <div style="margin-top:6px;padding:6px 10px;background:#d1fae5;border-radius:8px;font-size:0.75rem;color:#065f46;font-weight:600">
                        <i class="bi bi-check-circle"></i> Оферта принята
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>

                    <!-- Public link -->
                    <div style="margin-top:10px">
                        <div class="crm-field-label" style="margin-bottom:3px">Публичная ссылка</div>
                        <div class="crm-link-copy">
                            <input type="text" value="<?= Html::encode($model->getPublicUrl()) ?>" id="public-link-input" readonly>
                            <button class="admin-btn admin-btn-secondary admin-btn-sm" style="padding:3px 7px;font-size:11px" onclick="copyToClipboard('public-link-input')" title="Скопировать">
                                <i class="bi bi-clipboard" id="copy-icon"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Related orders -->
            <?php
            $relatedOrders = [];
            if ($customer) {
                try {
                    $relatedOrders = \app\backend\modules\checkout\models\Order::find()
                        ->where(['customer_id' => $customer->id])
                        ->andWhere(['<>', 'id', $model->id])
                        ->orderBy(['created_at' => SORT_DESC])
                        ->limit(5)
                        ->all();
                } catch (\Exception $e) { $relatedOrders = []; }
            }
            ?>
            <?php if (!empty($relatedOrders)): ?>
            <div class="crm-card">
                <div class="crm-card-head">
                    <h3><i class="bi bi-link-45deg"></i> Связанные заказы</h3>
                    <span style="font-size:0.7rem;color:var(--admin-text-secondary,#6b7280)"><?= $customer->orders_count ?? count($relatedOrders) ?> всего</span>
                </div>
                <div style="padding:4px 0">
                    <?php foreach ($relatedOrders as $ro):
                        $roColor = $statusColors[$ro->status] ?? '#6b7280';
                        $roBg    = $statusBgColors[$ro->status] ?? '#f3f4f6';
                    ?>
                    <a href="<?= Url::to(['/admin/order/view', 'id' => $ro->id]) ?>" style="display:flex;align-items:center;gap:8px;padding:7px 16px;text-decoration:none;transition:background .15s;color:inherit" onmouseover="this.style.background='var(--admin-surface-hover,#f9fafb)'" onmouseout="this.style.background=''">
                        <div style="flex:1;min-width:0">
                            <div style="font-size:0.8125rem;font-weight:600;color:var(--admin-text-primary,#111)">№<?= Html::encode($ro->order_number ?: $ro->id) ?></div>
                            <div style="font-size:0.7rem;color:var(--admin-text-secondary,#9ca3af)"><?= Yii::$app->formatter->asDate($ro->created_at, 'short') ?> · <?= Yii::$app->formatter->asDecimal($ro->total_amount, 2) ?> Br</div>
                        </div>
                        <span style="font-size:0.65rem;font-weight:700;padding:2px 6px;border-radius:6px;background:<?= $roBg ?>;color:<?= $roColor ?>;white-space:nowrap"><?= Html::encode($statuses[$ro->status] ?? $ro->status) ?></span>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Международная доставка (sidebar) -->
            <div class="crm-card">
                <div class="crm-card-head">
                    <h3><i class="bi bi-airplane"></i> Международная доставка</h3>
                    <?php
                    $chinaStatus = $model->china_delivery_status ?? '';
                    $chinaLabels = ['ordered_poizon'=>'Заказано','in_transit_china'=>'В пути','customs'=>'Таможня','arrived_warehouse'=>'На складе'];
                    if ($chinaStatus && isset($chinaLabels[$chinaStatus])) {
                        echo '<span style="font-size:0.65rem;padding:2px 7px;border-radius:6px;background:#dbeafe;color:#1e40af;font-weight:700">' . $chinaLabels[$chinaStatus] . '</span>';
                    }
                    ?>
                </div>
                <div class="crm-card-body">
                    <div class="crm-field" style="margin-bottom:8px">
                        <div class="crm-field-label">Трек Poizon / SF <span title="Обязательно для Таможня:ДП" style="cursor:help;font-size:0.75rem"><i class="bi bi-truck" style="font-size:0.75rem;color:#4338ca"></i></span></div>
                        <div class="crm-track-wrap">
                            <input type="text" class="crm-track-input" id="china-track-input"
                                   value="<?= Html::encode($model->china_track_number ?? '') ?>"
                                   placeholder="SF123456789" style="font-family:monospace">
                            <button class="admin-btn admin-btn-secondary admin-btn-sm" onclick="saveField('china_track_number', document.getElementById('china-track-input').value)" title="Сохранить">
                                <i class="bi bi-check2"></i>
                            </button>
                            <button class="admin-btn admin-btn-secondary admin-btn-sm" onclick="checkTrack(document.getElementById('china-track-input').value,'china-track-result')" title="Проверить">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                        <div id="china-track-result" style="margin-top:4px;font-size:0.7rem;color:var(--admin-text-secondary,#6b7280)"></div>
                    </div>
                    <div class="crm-field" style="margin-bottom:6px">
                        <div class="crm-field-label">Статус этапа</div>
                        <select class="admin-form-input" style="font-size:0.75rem;padding:4px 8px;width:100%" onchange="saveField('china_delivery_status', this.value)">
                            <?php foreach(['ordered_poizon'=>'Заказано на Poizon','in_transit_china'=>'В пути из Китая','customs'=>'Таможня','arrived_warehouse'=>'На складе'] as $k=>$v): ?>
                            <option value="<?= $k ?>" <?= ($model->china_delivery_status ?? '') === $k ? 'selected' : '' ?>><?= $v ?></option>
                            <?php endforeach ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Доставка по РБ (sidebar) -->
            <div class="crm-card">
                <div class="crm-card-head">
                    <h3><i class="bi bi-truck"></i> Доставка по РБ</h3>
                    <?php
                    $dm = $model->delivery_method ?? '';
                    // Build label map from DB settings; fall back to hardcoded if no settings
                    $dmLabels = ['europochta'=>'Европочта','belpochta'=>'Белпочта','cdek'=>'СДЭК','courier_minsk'=>'Курьер','pickup'=>'Самовывоз'];
                    if (!empty($shippingMethods)) {
                        $dmLabels = [];
                        foreach ($shippingMethods as $_sm) {
                            if (!empty($_sm['id']) && !empty($_sm['name'])) {
                                $dmLabels[$_sm['id']] = $_sm['name'];
                            }
                        }
                    }
                    if ($dm && isset($dmLabels[$dm])) {
                        echo '<span style="font-size:0.65rem;padding:2px 7px;border-radius:6px;background:#f0fdf4;color:#166534;font-weight:700">' . $dmLabels[$dm] . '</span>';
                    }
                    ?>
                </div>
                <div class="crm-card-body">
                    <div class="crm-field" style="margin-bottom:8px">
                        <div class="crm-field-label">Служба доставки</div>
                        <select id="delivery-method-select" class="admin-form-input" style="font-size:0.75rem;padding:4px 8px;width:100%" onchange="(function(v){saveField('delivery_method', v); toggleDeliveryFields(v);})(this.value)">
                            <option value="">—</option>
                            <?php if (!empty($shippingMethods)): ?>
                                <?php foreach ($shippingMethods as $_sm): ?>
                                <?php if (empty($_sm['id'])) continue; ?>
                                <option value="<?= Html::encode($_sm['id']) ?>" <?= $dm === $_sm['id'] ? 'selected' : '' ?>><?= Html::encode($_sm['name'] ?? $_sm['id']) ?></option>
                                <?php endforeach ?>
                            <?php else: ?>
                                <?php foreach(['europochta'=>'Европочта','belpochta'=>'Белпочта','cdek'=>'СДЭК','courier_minsk'=>'Курьер Минск','pickup'=>'Самовывоз'] as $k=>$v): ?>
                                <option value="<?= $k ?>" <?= $dm === $k ? 'selected' : '' ?>><?= $v ?></option>
                                <?php endforeach ?>
                            <?php endif ?>
                        </select>
                    </div>
                    <!-- Самовывоз — адрес из настроек (read-only) -->
                    <?php
                    $pickupAddr = Yii::$app->settings->get('checkout', 'pickup_address', 'пр.Победителей 5, офис 9');
                    ?>
                    <div class="crm-field" style="margin-bottom:8px" data-delivery-group="pickup-info">
                        <div class="crm-field-label">Адрес самовывоза</div>
                        <div style="font-size:0.75rem;padding:5px 8px;background:var(--admin-surface-2,#f8f9fa);border:1px solid var(--admin-border,#e5e7eb);border-radius:6px;color:var(--admin-text-primary,#111)">
                            <i class="bi bi-geo-alt-fill" style="color:#059669"></i> <?= Html::encode($pickupAddr) ?>
                        </div>
                    </div>
                    <!-- ПВЗ dropdown (Европочта / СДЭК) -->
                    <div class="crm-field" style="margin-bottom:8px" data-delivery-group="pvz">
                        <div class="crm-field-label" id="pvz-group-label">Пункт выдачи</div>
                        <?php if ($model->pickup_point): ?>
                        <div style="font-size:0.7rem;color:#059669;margin-bottom:4px"><i class="bi bi-geo-alt-fill"></i> ПВЗ: <?= Html::encode($model->pickup_point) ?></div>
                        <?php endif; ?>
                        <div class="crm-pvz-wrap" style="position:relative">
                            <input type="text" class="admin-form-input" id="pvz-search-input" style="font-size:0.75rem;padding:4px 8px;width:100%"
                                   value="<?= Html::encode($model->pickup_point ?? $model->delivery_address ?? '') ?>"
                                   placeholder="Город или адрес ПВЗ" autocomplete="off"
                                   oninput="searchPVZ(this.value)" onfocus="searchPVZ(this.value)">
                            <div class="crm-pvz-dropdown" id="pvz-dropdown" style="position:absolute;top:100%;left:0;right:0;background:#fff;border:1px solid #e5e7eb;border-radius:6px;max-height:240px;overflow-y:auto;box-shadow:0 10px 25px rgba(0,0,0,.1);z-index:50;display:none"></div>
                        </div>
                    </div>
                    <!-- Адрес доставки (Белпочта, Курьер Минск) -->
                    <div class="crm-field" style="margin-bottom:8px" data-delivery-group="address">
                        <div class="crm-field-label">Адрес доставки</div>
                        <input type="text" class="admin-form-input" style="font-size:0.75rem;padding:4px 8px;width:100%"
                               value="<?= Html::encode($model->delivery_address ?? '') ?>"
                               onchange="saveField('delivery_address', this.value)"
                               placeholder="Город, улица, дом">
                    </div>
                    <!-- Трек-номер (все кроме Самовывоз) -->
                    <div class="crm-field" data-delivery-group="track">
                        <div class="crm-field-label">Трек РБ</div>
                        <div class="crm-track-wrap">
                            <input type="text" class="crm-track-input" id="local-track-input"
                                   value="<?= Html::encode($model->local_track_number ?? '') ?>"
                                   placeholder="EP123456789BY" style="font-family:monospace">
                            <button class="admin-btn admin-btn-secondary admin-btn-sm" onclick="saveField('local_track_number', document.getElementById('local-track-input').value)" title="Сохранить">
                                <i class="bi bi-check2"></i>
                            </button>
                            <button class="admin-btn admin-btn-secondary admin-btn-sm" onclick="checkTrack(document.getElementById('local-track-input').value,'local-track-result')" title="Проверить">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                        <div id="local-track-result" style="margin-top:4px;font-size:0.7rem;color:var(--admin-text-secondary,#6b7280)"></div>
                    </div>
                </div>
            </div>

            <!-- Таможня:ДП (sidebar) -->
            <div class="crm-card">
                <div class="crm-card-head">
                    <h3><i class="bi bi-box-arrow-right"></i> Таможня:ДП</h3>
                    <?php
                    if ($dpShipmentId && $dpStatus) {
                        $dpBadgeCls = 'sent'; $dpBadgeTxt = 'Отправлен';
                        if (in_array($dpStatus, ['problem','customs_hold','returned'])) { $dpBadgeCls = 'error'; $dpBadgeTxt = 'Ошибка'; }
                        elseif ($dpStatus === 'delivered') { $dpBadgeTxt = 'Доставлен'; }
                    } elseif ($dpShipmentId) { $dpBadgeCls = 'sent'; $dpBadgeTxt = 'Отправлен'; }
                    else { $dpBadgeCls = 'pending'; $dpBadgeTxt = 'Не отправлен'; }
                    ?>
                    <span class="crm-dp-badge <?= $dpBadgeCls ?>"><?= $dpBadgeTxt ?></span>
                </div>
                <div class="crm-card-body">
                    <div class="crm-field" style="margin-bottom:8px">
                        <div class="crm-field-label">Тариф</div>
                        <select onchange="saveField('dobropost_tariff', this.value)" style="width:100%;padding:4px 8px;border:1px solid var(--admin-border,#e5e7eb);border-radius:6px;background:var(--admin-surface,#fff);color:var(--admin-text-primary,#111);font-size:0.75rem">
                            <option value="26" <?= ($model->dobropost_tariff == 26 || !$model->dobropost_tariff) ? 'selected' : '' ?>>26 — База Минск</option>
                            <option value="27" <?= $model->dobropost_tariff == 27 ? 'selected' : '' ?>>27 — База Москва</option>
                            <option value="28" <?= $model->dobropost_tariff == 28 ? 'selected' : '' ?>>28 — Экспресс Минск</option>
                            <option value="29" <?= $model->dobropost_tariff == 29 ? 'selected' : '' ?>>29 — Экспресс Москва</option>
                        </select>
                    </div>
                    <div class="crm-info-grid" style="margin-bottom:10px">
                        <div class="crm-field">
                            <div class="crm-field-label">Паспорт</div>
                            <div class="crm-field-val">
                                <?php if ($dpPassportOk): ?>
                                    <span style="color:#059669"><i class="bi bi-check-circle"></i> Заполнен</span>
                                    <?php if (!empty($model->passport_validated)): ?>
                                        <span style="background:#d1fae5;color:#065f46;padding:1px 5px;border-radius:4px;font-size:10px;font-weight:700;margin-left:3px">ДП ✓</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span style="color:#dc2626"><i class="bi bi-exclamation-triangle"></i> Не заполнен</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if ($dpShipmentId): ?>
                        <div class="crm-field">
                            <div class="crm-field-label">DP трек</div>
                            <div class="crm-field-val" style="font-family:monospace;font-size:0.75rem"><?= Html::encode($model->dp_track_number ?? $dpShipmentId) ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if ($dpStatus): ?>
                        <div class="crm-field">
                            <div class="crm-field-label">Статус ДП</div>
                            <div class="crm-field-val" style="font-size:0.8rem">
                                <?php
                                $dpMapping = \app\backend\modules\checkout\models\DeliveryStatusMapping::find()
                                    ->where(['provider_status_id' => $dpStatus, 'provider_id' => 1])
                                    ->one();
                                $dpStatusText = $dpMapping ? $dpMapping->display_name : ('Статус: ' . $dpStatus);
                                echo Html::encode($dpStatusText);
                                if (!empty($model->dp_status_date)) {
                                    echo ' <span style="color:var(--admin-text-secondary,#9ca3af);font-size:0.7rem">(' . Html::encode(date('d.m.Y', strtotime($model->dp_status_date))) . ')</span>';
                                }
                                ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($model->estimated_delivery_date)): ?>
                        <div class="crm-field">
                            <div class="crm-field-label">Ожидаемая доставка</div>
                            <div class="crm-field-val" style="font-size:0.8rem"><?= Html::encode(date('d.m.Y', strtotime($model->estimated_delivery_date))) ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div style="display:flex;gap:6px;flex-wrap:wrap;align-items:center">
                        <?php if (!$dpShipmentId): ?>
                        <button class="admin-btn admin-btn-primary admin-btn-sm" style="width:100%;justify-content:center"
                                onclick="sendToDP(<?= $model->id ?>)"
                                <?= !$dpPassportOk ? 'disabled title="Сначала заполните паспортные данные"' : '' ?>>
                            <i class="bi bi-send"></i> Отправить в ДП
                        </button>
                        <?php else: ?>
                        <button class="admin-btn admin-btn-secondary admin-btn-sm" onclick="refreshDPStatus(<?= $model->id ?>)">
                            <i class="bi bi-arrow-clockwise"></i> Обновить
                        </button>
                        <button class="admin-btn admin-btn-secondary admin-btn-sm" onclick="retryDP(<?= $model->id ?>)">
                            <i class="bi bi-arrow-repeat"></i> Повтор
                        </button>
                        <?php endif; ?>
                        <span id="dp-action-result" style="font-size:0.75rem"></span>
                    </div>
                    <?php
                    // Status history from delivery_tracking + dp_response statusHistory
                    $dpResponseArr = is_array($dpResponse) ? $dpResponse : (is_string($dpResponse) ? json_decode($dpResponse, true) : []);
                    $dpStatusHistory = $dpResponseArr['statusHistory'] ?? [];

                    $trackingHistory = \app\backend\modules\checkout\models\DeliveryTracking::find()
                        ->where(['order_id' => $model->id])
                        ->orderBy(['created_at' => SORT_DESC])
                        ->limit(10)
                        ->all();
                    ?>
                    <?php if (!empty($trackingHistory) || !empty($dpStatusHistory)): ?>
                    <div style="margin-top:8px;border-top:1px solid var(--admin-border,#e5e7eb);padding-top:8px">
                        <div style="font-size:11px;font-weight:600;color:var(--admin-text-secondary,#9ca3af);margin-bottom:6px;text-transform:uppercase;letter-spacing:.04em">История статусов</div>
                        <?php foreach ($dpStatusHistory as $sh): ?>
                        <div style="display:flex;gap:8px;margin-bottom:4px;font-size:11px">
                            <span style="color:var(--admin-text-secondary,#9ca3af);white-space:nowrap">
                                <?= isset($sh['date']) ? date('d.m H:i', strtotime($sh['date'])) : '—' ?>
                            </span>
                            <span style="color:var(--admin-text-primary,#111)"><?= Html::encode($sh['name'] ?? $sh['status'] ?? '') ?></span>
                        </div>
                        <?php endforeach; ?>
                        <?php foreach ($trackingHistory as $th): ?>
                        <div style="display:flex;gap:8px;margin-bottom:4px;font-size:11px">
                            <span style="color:var(--admin-text-secondary,#9ca3af);white-space:nowrap">
                                <?= date('d.m H:i', is_numeric($th->created_at) ? $th->created_at : strtotime($th->created_at)) ?>
                            </span>
                            <span style="color:var(--admin-text-primary,#111)"><?= Html::encode($th->status_description ?? $th->status ?? '') ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    <?php if ($dpResponse): ?>
                    <div style="margin-top:8px">
                        <a href="#" onclick="document.getElementById('dp-last-response').style.display=(document.getElementById('dp-last-response').style.display==='none'?'block':'none');return false;"
                           style="font-size:0.75rem;color:var(--admin-text-secondary,#6b7280)">
                            <i class="bi bi-code-slash"></i> Ответ API
                        </a>
                        <div id="dp-last-response" style="display:none;margin-top:6px">
                            <pre style="font-size:9px;background:var(--admin-surface-hover,#f8f9fa);padding:6px 10px;border-radius:6px;overflow:auto;max-height:140px"><?= Html::encode(is_array($dpResponse) ? json_encode($dpResponse, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : (string)$dpResponse) ?></pre>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- МойСклад (sidebar) -->
            <div class="crm-card">
                <div class="crm-card-head">
                    <h3><i class="bi bi-cloud-check"></i> МойСклад</h3>
                    <?php $msStatus = $model->moysklad_id ?? null; ?>
                    <span style="font-size:0.7rem;padding:2px 7px;border-radius:6px;font-weight:700;background:<?= $msStatus?'#d1fae5':'#f3f4f6'?>;color:<?= $msStatus?'#065f46':'#6b7280'?>">
                        <?= $msStatus ? 'Передан' : 'Не передан' ?>
                    </span>
                </div>
                <div class="crm-card-body">
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:10px">
                        <?php if ($msStatus): ?>
                        <a href="https://online.moysklad.ru/app/#customerorder/edit?id=<?= Html::encode($msStatus) ?>"
                           target="_blank" class="admin-btn admin-btn-secondary admin-btn-sm">
                            <i class="bi bi-box-arrow-up-right"></i> Открыть в МС
                        </a>
                        <?php endif; ?>
                        <button class="admin-btn admin-btn-secondary admin-btn-sm" onclick="syncMoysklad(<?= $model->id ?>)">
                            <i class="bi bi-arrow-repeat"></i> Синхронизировать
                        </button>
                        <span id="ms-sync-result" style="font-size:0.75rem"></span>
                    </div>

                    <?php if ($msStatus): ?>
                    <!-- MS финансы -->
                    <div style="font-size:0.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--admin-text-secondary,#9ca3af);margin-bottom:6px">Финансы МС</div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:4px 8px;margin-bottom:10px;font-size:0.775rem">
                        <?php if ($model->ms_payed_sum !== null): ?>
                        <div><span style="color:var(--admin-text-secondary,#9ca3af)">Оплачено:</span> <strong style="color:#059669"><?= number_format($model->ms_payed_sum, 2) ?> <?= Html::encode($model->ms_rate_currency ?: 'BYN') ?></strong></div>
                        <?php endif; ?>
                        <?php if ($model->ms_invoiced_sum !== null): ?>
                        <div><span style="color:var(--admin-text-secondary,#9ca3af)">Выставлено:</span> <strong><?= number_format($model->ms_invoiced_sum, 2) ?></strong></div>
                        <?php endif; ?>
                        <?php if ($model->ms_reserved_sum !== null): ?>
                        <div><span style="color:var(--admin-text-secondary,#9ca3af)">Резерв:</span> <strong><?= number_format($model->ms_reserved_sum, 2) ?></strong></div>
                        <?php endif; ?>
                        <?php if ($model->ms_shipped_sum !== null): ?>
                        <div><span style="color:var(--admin-text-secondary,#9ca3af)">Отгружено:</span> <strong><?= number_format($model->ms_shipped_sum, 2) ?></strong></div>
                        <?php endif; ?>
                        <?php if ($model->ms_vat_sum !== null): ?>
                        <div><span style="color:var(--admin-text-secondary,#9ca3af)">НДС:</span> <strong><?= number_format($model->ms_vat_sum, 2) ?></strong></div>
                        <?php endif; ?>
                        <?php if ($model->ms_rate_value !== null): ?>
                        <div><span style="color:var(--admin-text-secondary,#9ca3af)">Курс:</span> <?= Html::encode($model->ms_rate_value) ?> <?= Html::encode($model->ms_rate_currency ?: '') ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- MS реквизиты -->
                    <div style="font-size:0.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--admin-text-secondary,#9ca3af);margin-bottom:6px">Реквизиты МС</div>
                    <table style="width:100%;font-size:0.775rem;border-collapse:collapse">
                        <?php if ($model->ms_number): ?>
                        <tr><td style="color:var(--admin-text-secondary,#9ca3af);padding:2px 0;width:45%">Номер МС</td><td style="font-weight:600;font-family:monospace"><?= Html::encode($model->ms_number) ?></td></tr>
                        <?php endif; ?>
                        <?php if ($model->ms_external_code): ?>
                        <tr><td style="color:var(--admin-text-secondary,#9ca3af);padding:2px 0">Внешний код</td><td style="font-family:monospace;font-size:0.7rem"><?= Html::encode($model->ms_external_code) ?></td></tr>
                        <?php endif; ?>
                        <?php if ($model->ms_organization_name): ?>
                        <tr><td style="color:var(--admin-text-secondary,#9ca3af);padding:2px 0">Организация</td><td style="font-weight:500"><?= Html::encode($model->ms_organization_name) ?></td></tr>
                        <?php endif; ?>
                        <?php if ($model->ms_store_name): ?>
                        <tr><td style="color:var(--admin-text-secondary,#9ca3af);padding:2px 0">Склад</td><td><?= Html::encode($model->ms_store_name) ?></td></tr>
                        <?php endif; ?>
                        <?php if ($model->ms_contract_name): ?>
                        <tr><td style="color:var(--admin-text-secondary,#9ca3af);padding:2px 0">Договор</td><td><?= Html::encode($model->ms_contract_name) ?></td></tr>
                        <?php endif; ?>
                        <?php if ($model->ms_project_name): ?>
                        <tr><td style="color:var(--admin-text-secondary,#9ca3af);padding:2px 0">Проект</td><td><?= Html::encode($model->ms_project_name) ?></td></tr>
                        <?php endif; ?>
                        <?php if ($model->ms_sales_channel): ?>
                        <tr><td style="color:var(--admin-text-secondary,#9ca3af);padding:2px 0">Канал продаж</td><td><?= Html::encode($model->ms_sales_channel) ?></td></tr>
                        <?php endif; ?>
                        <?php if ($model->ms_lead_source): ?>
                        <tr><td style="color:var(--admin-text-secondary,#9ca3af);padding:2px 0">Источник</td><td><?= Html::encode($model->ms_lead_source) ?></td></tr>
                        <?php endif; ?>
                        <?php if ($model->ms_delivery_type): ?>
                        <tr><td style="color:var(--admin-text-secondary,#9ca3af);padding:2px 0">Тип доставки</td><td><?= Html::encode($model->ms_delivery_type) ?></td></tr>
                        <?php endif; ?>
                        <?php if ($model->ms_order_size): ?>
                        <tr><td style="color:var(--admin-text-secondary,#9ca3af);padding:2px 0">Размер заказа</td><td><?= Html::encode($model->ms_order_size) ?></td></tr>
                        <?php endif; ?>
                        <?php if ($model->ms_positions_count !== null): ?>
                        <tr><td style="color:var(--admin-text-secondary,#9ca3af);padding:2px 0">Позиций в МС</td><td><?= (int)$model->ms_positions_count ?></td></tr>
                        <?php endif; ?>
                        <?php if ($model->ms_agent_company_type): ?>
                        <tr><td style="color:var(--admin-text-secondary,#9ca3af);padding:2px 0">Тип контрагента</td><td><?= Html::encode($model->ms_agent_company_type) ?></td></tr>
                        <?php endif; ?>
                        <?php if ($model->ms_agent_legal_title): ?>
                        <tr><td style="color:var(--admin-text-secondary,#9ca3af);padding:2px 0">Юр. наименование</td><td style="font-size:0.7rem"><?= Html::encode($model->ms_agent_legal_title) ?></td></tr>
                        <?php endif; ?>
                        <?php if ($model->ms_agent_actual_address): ?>
                        <tr><td style="color:var(--admin-text-secondary,#9ca3af);padding:2px 0">Факт. адрес</td><td style="font-size:0.7rem"><?= Html::encode($model->ms_agent_actual_address) ?></td></tr>
                        <?php endif; ?>
                    </table>

                    <!-- MS флаги + даты -->
                    <?php
                    $msFlags = [];
                    if ($model->ms_applicable) $msFlags[] = '<span style="background:#d1fae5;color:#065f46;padding:1px 6px;border-radius:4px;font-size:0.7rem;font-weight:600">Проведён</span>';
                    if ($model->ms_via_widget) $msFlags[] = '<span style="background:#dbeafe;color:#1e40af;padding:1px 6px;border-radius:4px;font-size:0.7rem;font-weight:600">Через виджет</span>';
                    if ($model->ms_passport_transferred) $msFlags[] = '<span style="background:#d1fae5;color:#065f46;padding:1px 6px;border-radius:4px;font-size:0.7rem;font-weight:600">Паспорт передан</span>';
                    if ($model->ms_vat_enabled) $msFlags[] = '<span style="background:#fef3c7;color:#92400e;padding:1px 6px;border-radius:4px;font-size:0.7rem;font-weight:600">НДС' . ($model->ms_vat_included ? ' вкл.' : ' не вкл.') . '</span>';
                    if ($model->ms_printed) $msFlags[] = '<span style="background:#f3f4f6;color:#6b7280;padding:1px 6px;border-radius:4px;font-size:0.7rem;font-weight:600">Напечатан</span>';
                    if ($model->ms_published) $msFlags[] = '<span style="background:#ede9fe;color:#7c3aed;padding:1px 6px;border-radius:4px;font-size:0.7rem;font-weight:600">Опубликован</span>';
                    ?>
                    <?php if (!empty($msFlags)): ?>
                    <div style="display:flex;flex-wrap:wrap;gap:4px;margin-top:8px"><?= implode('', $msFlags) ?></div>
                    <?php endif; ?>

                    <?php if ($model->ms_delivery_planned_moment || $model->ms_created || $model->ms_updated): ?>
                    <div style="margin-top:8px;display:flex;flex-direction:column;gap:2px;font-size:0.75rem">
                        <?php if ($model->ms_created): ?><div><span style="color:var(--admin-text-secondary,#9ca3af)">Создан в МС:</span> <?= Yii::$app->formatter->asDatetime($model->ms_created, 'short') ?></div><?php endif; ?>
                        <?php if ($model->ms_updated): ?><div><span style="color:var(--admin-text-secondary,#9ca3af)">Обновлён в МС:</span> <?= Yii::$app->formatter->asDatetime($model->ms_updated, 'short') ?></div><?php endif; ?>
                        <?php if ($model->ms_delivery_planned_moment): ?><div><span style="color:var(--admin-text-secondary,#9ca3af)">Отгрузка план.:</span> <?= Yii::$app->formatter->asDatetime($model->ms_delivery_planned_moment, 'short') ?></div><?php endif; ?>
                        <?php if ($model->ms_pickup_date): ?><div><span style="color:var(--admin-text-secondary,#9ca3af)">Дата самовывоза:</span> <?= Html::encode($model->ms_pickup_date) ?></div><?php endif; ?>
                        <?php if ($model->ms_cancel_date): ?><div><span style="color:var(--admin-text-secondary,#9ca3af)">Дата отмены:</span> <?= Html::encode($model->ms_cancel_date) ?></div><?php endif; ?>
                        <?php if ($model->ms_amo_created_at): ?><div><span style="color:var(--admin-text-secondary,#9ca3af)">Создан в AMO:</span> <?= Yii::$app->formatter->asDatetime($model->ms_amo_created_at, 'short') ?></div><?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <?php if ($model->ms_cancel_reason): ?>
                    <div style="margin-top:8px;padding:5px 8px;background:#fee2e2;border-radius:6px;font-size:0.75rem;color:#991b1b">
                        <strong>Причина отмены:</strong> <?= Html::encode($model->ms_cancel_reason) ?>
                    </div>
                    <?php endif; ?>

                    <?php if ($model->ms_deal_link): ?>
                    <div style="margin-top:8px;font-size:0.75rem">
                        <a href="<?= Html::encode($model->ms_deal_link) ?>" target="_blank" style="color:var(--admin-accent,#059669)"><i class="bi bi-link-45deg"></i> Сделка в МС</a>
                    </div>
                    <?php endif; ?>

                    <?php if ($model->ms_waybill_link): ?>
                    <div style="margin-top:4px;font-size:0.75rem">
                        <a href="<?= Html::encode($model->ms_waybill_link) ?>" target="_blank" style="color:var(--admin-accent,#059669)"><i class="bi bi-file-earmark-text"></i> Накладная</a>
                    </div>
                    <?php endif; ?>

                    <?php
                    $msPayments = $model->ms_linked_payments_json ? (is_array($model->ms_linked_payments_json) ? $model->ms_linked_payments_json : json_decode($model->ms_linked_payments_json, true)) : [];
                    $msInvoices = $model->ms_invoices_out_json ? (is_array($model->ms_invoices_out_json) ? $model->ms_invoices_out_json : json_decode($model->ms_invoices_out_json, true)) : [];
                    $msDemands  = $model->ms_demands_json ? (is_array($model->ms_demands_json) ? $model->ms_demands_json : json_decode($model->ms_demands_json, true)) : [];
                    ?>
                    <?php if (!empty($msPayments) || !empty($msInvoices) || !empty($msDemands)): ?>
                    <div style="margin-top:8px;font-size:0.75rem;display:flex;gap:10px;flex-wrap:wrap">
                        <?php if (!empty($msPayments)): ?>
                        <span style="background:#d1fae5;color:#065f46;padding:2px 8px;border-radius:4px;font-weight:600"><i class="bi bi-cash-coin"></i> Платежей: <?= count($msPayments) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($msInvoices)): ?>
                        <span style="background:#dbeafe;color:#1e40af;padding:2px 8px;border-radius:4px;font-weight:600"><i class="bi bi-file-earmark-check"></i> Счетов: <?= count($msInvoices) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($msDemands)): ?>
                        <span style="background:#ede9fe;color:#7c3aed;padding:2px 8px;border-radius:4px;font-weight:600"><i class="bi bi-truck"></i> Отгрузок: <?= count($msDemands) ?></span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <?php
                    $msAttrs = $model->ms_attributes_json ? (is_array($model->ms_attributes_json) ? $model->ms_attributes_json : json_decode($model->ms_attributes_json, true)) : [];
                    if (!empty($msAttrs)): ?>
                    <div style="margin-top:10px">
                        <div style="font-size:0.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--admin-text-secondary,#9ca3af);margin-bottom:4px">Атрибуты МС</div>
                        <?php foreach ($msAttrs as $attr): ?>
                        <div style="font-size:0.75rem;display:flex;justify-content:space-between;padding:2px 0;border-bottom:1px solid var(--admin-border,#f3f4f6)">
                            <span style="color:var(--admin-text-secondary,#9ca3af)"><?= Html::encode($attr['name'] ?? '') ?></span>
                            <span style="font-weight:500;text-align:right;max-width:55%;word-break:break-all"><?= Html::encode(is_array($attr['value'] ?? '') ? ($attr['value']['name'] ?? json_encode($attr['value'])) : ($attr['value'] ?? '')) ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <?php endif; /* if $msStatus */ ?>
                </div>
            </div>

            <!-- Сроки (compact, sidebar bottom) -->
            <?php
            $createdTs  = $model->created_at ? (is_numeric($model->created_at) ? (int)$model->created_at : strtotime($model->created_at)) : null;
            $updatedTs  = $model->updated_at ? (is_numeric($model->updated_at) ? (int)$model->updated_at : strtotime($model->updated_at)) : null;
            $daysAgo    = $createdTs ? (int)floor((time() - $createdTs) / 86400) : null;
            $expectedTs = !empty($model->estimated_delivery_date) ? strtotime($model->estimated_delivery_date) : null;
            $daysLeft   = $expectedTs ? (int)ceil(($expectedTs - time()) / 86400) : null;
            $isOverdue  = $expectedTs && time() > $expectedTs && !in_array($model->status, ['delivered','cancelled','returned']);
            ?>
            <div class="crm-card" style="margin-top:4px">
                <div class="crm-card-head">
                    <h3 style="font-size:0.8rem"><i class="bi bi-calendar-check"></i> Сроки</h3>
                    <?php if ($isOverdue): ?>
                    <span style="font-size:0.65rem;padding:1px 6px;border-radius:5px;background:#fee2e2;color:#991b1b;font-weight:700"><i class="bi bi-alarm"></i> Просрочен</span>
                    <?php endif; ?>
                </div>
                <div style="padding:8px 14px;display:flex;flex-direction:column;gap:5px;font-size:0.775rem">
                    <div style="display:flex;justify-content:space-between;align-items:baseline">
                        <span style="color:var(--admin-text-secondary,#6b7280)">Создан</span>
                        <span style="font-weight:600"><?= $createdTs ? date('d.m.Y', $createdTs) : '—' ?>
                        <?php if ($daysAgo !== null): ?><span style="color:var(--admin-text-secondary,#9ca3af);font-weight:400"> (<?= $daysAgo ?> дн.)</span><?php endif; ?></span>
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:baseline">
                        <span style="color:var(--admin-text-secondary,#6b7280)">Ожид. доставка</span>
                        <div class="crm-editable" data-field="estimated_delivery_date" data-id="<?= $model->id ?>" onclick="startEdit(this)" style="font-weight:600;<?= $isOverdue ? 'color:#dc2626' : '' ?>">
                            <?= $expectedTs ? Html::encode(date('d.m.Y', $expectedTs)) : '<span class="crm-editable-empty">—</span>' ?>
                        </div>
                    </div>
                    <?php if ($updatedTs): ?>
                    <div style="display:flex;justify-content:space-between;align-items:baseline">
                        <span style="color:var(--admin-text-secondary,#6b7280)">Обновлён</span>
                        <span style="font-weight:600"><?= date('d.m.Y', $updatedTs) ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

        </div><!-- /.crm-sidebar -->

    </div><!-- /.crm-body -->

<!-- ═══ CUSTOMER QUICK-VIEW MODAL ═══ -->
<div class="cqv-overlay" id="cqv-overlay" onclick="if(event.target===this)closeCustomerQuickView()">
    <div class="cqv-modal">
        <div class="cqv-header">
            <i class="bi bi-person-circle" style="font-size:1.1rem;color:var(--admin-text-secondary,#6b7280)"></i>
            <h3 id="cqv-title">Профиль клиента</h3>
            <a id="cqv-full-link" href="#" target="_blank" class="admin-btn admin-btn-secondary admin-btn-sm" style="font-size:11px;padding:3px 8px">
                <i class="bi bi-box-arrow-up-right"></i> Открыть
            </a>
            <button type="button" onclick="closeCustomerQuickView()" style="background:none;border:none;cursor:pointer;padding:4px 8px;font-size:1.25rem;color:var(--admin-text-secondary,#6b7280);line-height:1">&times;</button>
        </div>
        <div class="cqv-body" id="cqv-body">
            <div class="cqv-loading"><i class="bi bi-arrow-repeat" style="display:block;font-size:1.5rem;margin-bottom:8px"></i>Загружаем...</div>
        </div>
    </div>
</div>

<!-- ═══ HISTORY SLIDE PANEL ═══ -->
<div class="crm-history-popup" id="crm-history-popup" onclick="if(event.target===this)this.classList.remove('open')">
    <div class="crm-history-panel">
        <div class="crm-history-panel-head">
            <h3><i class="bi bi-clock-history"></i> История статусов</h3>
            <button type="button" onclick="document.getElementById('crm-history-popup').classList.remove('open')"
                    style="background:none;border:none;cursor:pointer;padding:4px 8px;font-size:1.25rem;color:var(--admin-text-secondary,#6b7280);line-height:1">&times;</button>
        </div>
        <div style="padding:12px 16px;border-bottom:1px solid var(--admin-border,#e5e7eb);background:var(--admin-surface-hover,#f9fafb)">
            <form method="post" action="<?= Url::to(['/admin/order/change-status', 'id' => $model->id]) ?>">
                <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>
                <select name="status" class="admin-form-input" style="font-size:0.8125rem;padding:6px 10px;margin-bottom:6px;width:100%">
                    <?php foreach ($statuses as $key => $label): ?>
                    <option value="<?= $key ?>" <?= $model->status == $key ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
                <textarea name="comment" class="crm-note-textarea" rows="2" placeholder="Комментарий к смене статуса..." style="min-height:40px;margin-bottom:6px"></textarea>
                <button type="submit" class="admin-btn admin-btn-primary admin-btn-sm" style="width:100%;justify-content:center">
                    <i class="bi bi-check2-circle"></i> Сменить статус
                </button>
            </form>
        </div>
        <div class="crm-timeline">
            <?php if (!empty($model->history)): ?>
                <?php foreach ($model->history as $h): ?>
                <div class="crm-tl-item">
                    <div class="crm-tl-dot <?= $h->new_status === $model->status ? 'active' : '' ?>"></div>
                    <div class="crm-tl-body">
                        <div class="crm-tl-status"><?= Html::encode($h->getNewStatusLabel()) ?></div>
                        <div class="crm-tl-meta">
                            <?= Yii::$app->formatter->asDatetime($h->created_at) ?>
                            <?php if ($h->changer): ?> · <?= Html::encode($h->changer->username) ?><?php endif; ?>
                        </div>
                        <?php if ($h->comment): ?>
                        <div class="crm-tl-comment"><?= Html::encode($h->comment) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="color:var(--admin-text-secondary,#9ca3af);font-size:0.8rem;margin:0">История пуста.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

</div><!-- /.crm-wrap -->

<?php
$_csrfToken   = Yii::$app->request->csrfToken;
$_modelId     = $model->id;
$_checkUrl    = Url::to(['/admin/order/check-track', 'orderId' => $model->id]);
$_updateUrl   = Url::to(['/admin/order/update-field', 'id' => $model->id]);
$_saveUrl     = '/admin/order/save-field';
$_dpSendUrl   = Url::to(['/admin/order/send-to-dp', 'id' => $model->id]);
$_dpStatusUrl = Url::to(['/admin/order/dp-status', 'id' => $model->id]);
$_dpRetryUrl  = Url::to(['/admin/order/retry-dp', 'id' => $model->id]);
$_addNoteUrl  = '/admin/order/add-note';
$_msSyncUrl   = Url::to(['/admin/order/sync-moysklad', 'id' => $model->id]);
$_dpAutoFillUrl = Url::to(['/admin/order/auto-fill-dp', 'id' => $model->id]);
$_itemCount   = count($model->orderItems);
$_saveItemFieldUrl = Url::to(['/admin/order/save-item-field']);
$_addItemUrl       = Url::to(['/admin/order/add-item']);

$this->registerJs(<<<JS
// ── Tab/Enter editable navigation helpers ─────────────────
function _getAllEditables() {
    return Array.from(document.querySelectorAll('.crm-editable[data-field]'));
}
function _openEditable(el) {
    if (!el) return;
    el.scrollIntoView({block: 'center', behavior: 'smooth'});
    setTimeout(function(){ el.click(); el.focus(); }, 60);
}
function openNextEditable(currentEl) {
    var all = _getAllEditables();
    var idx = all.indexOf(currentEl);
    if (idx !== -1 && idx + 1 < all.length) _openEditable(all[idx + 1]);
}
function openPrevEditable(currentEl) {
    var all = _getAllEditables();
    var idx = all.indexOf(currentEl);
    if (idx > 0) _openEditable(all[idx - 1]);
}

// ── Inline click-to-edit ──────────────────────────────────
window.startEdit = function(el) {
    if (el.querySelector('input,textarea')) return;
    var field  = el.dataset.field;
    var curVal = el.innerText.trim();
    if (curVal === 'Не указано' || curVal === '—' || curVal === '-' || curVal.trim() === '') curVal = '';
    el.classList.remove('crm-editable-empty');
    el.classList.add('crm-editing');
    var isArea = field === 'comment';
    var input  = document.createElement(isArea ? 'textarea' : 'input');
    input.className = 'crm-editable-input';
    input.value     = curVal;
    if (isArea) { input.rows = 3; input.style.resize = 'vertical'; }
    el.innerHTML = '';
    el.appendChild(input);
    input.focus();

    var _navigating = false;

    var commitAndClose = function(displayVal) {
        el.classList.remove('crm-editing');
        if (displayVal) {
            el.innerHTML = isArea ? displayVal.replace(/\\n/g,'<br>') : displayVal;
            el.classList.remove('crm-editable-empty');
        } else {
            el.innerHTML = '<span class="crm-editable-empty">—</span>';
        }
    };
    var save = function() {
        saveField(field, input.value);
        commitAndClose(input.value);
    };
    var cancel = function() {
        el.classList.remove('crm-editing');
        el.innerHTML = curVal ? (isArea ? curVal.replace(/\\n/g,'<br>') : curVal) : '<span class="crm-editable-empty">—</span>';
    };

    input.addEventListener('blur', function() {
        if (!_navigating) save();
    });

    input.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            _navigating = true;
            input.removeEventListener('blur', save);
            cancel();
            el.focus();
            return;
        }
        var isCtrlEnter = (e.key === 'Enter') && (e.ctrlKey || e.metaKey);
        var isEnter     = (e.key === 'Enter') && !isArea;
        var isTab       = (e.key === 'Tab');

        if (isCtrlEnter || isEnter || isTab) {
            e.preventDefault();
            _navigating = true;
            save();
            if (isTab && e.shiftKey) {
                openPrevEditable(el);
            } else {
                openNextEditable(el);
            }
        }
    });
};

// ── Add tabindex to all crm-editable on load ───────────────
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.crm-editable[data-field]').forEach(function(el) {
        if (!el.hasAttribute('tabindex')) el.setAttribute('tabindex', '0');
        el.addEventListener('keydown', function(kev) {
            if (kev.key === 'Enter' || kev.key === ' ') {
                kev.preventDefault();
                if (!el.querySelector('input,textarea')) el.click();
            }
        });
    });
});

// ── saveField ─────────────────────────────────────────────
window.saveField = function(field, value) {
    fetch('$_saveUrl', {
        method: 'POST',
        headers: {'Content-Type':'application/json','X-CSRF-Token':'$_csrfToken'},
        body: JSON.stringify({id: $_modelId, field: field, value: value})
    }).then(function(r){ return r.json(); }).then(function(d) {
        if (d.success) {
            var flash = document.createElement('span');
            flash.className = 'crm-save-flash';
            flash.textContent = '✓ Сохранено';
            document.body.appendChild(flash);
            setTimeout(function() { flash.classList.add('show'); }, 10);
            setTimeout(function() { flash.classList.remove('show'); setTimeout(function(){ flash.remove(); }, 300); }, 1500);
        }
    });
};

// ── Copy public link ──────────────────────────────────────
window.copyToClipboard = function(inputId) {
    var el = document.getElementById(inputId);
    if (!el) return;
    navigator.clipboard.writeText(el.value).then(function() {
        var icon = document.getElementById('copy-icon');
        if (icon) { icon.className = 'bi bi-check2'; setTimeout(function(){ icon.className = 'bi bi-clipboard'; }, 2000); }
    });
};

// ── Track check ───────────────────────────────────────────
window.checkTrack = function(track, resultDivId) {
    if (!track) return;
    var el = document.getElementById(resultDivId);
    if (el) { el.textContent = 'Проверяем...'; el.style.color = '#6b7280'; }
    fetch('$_checkUrl&track=' + encodeURIComponent(track))
    .then(function(r){ return r.json(); })
    .then(function(d) {
        if (el) { el.textContent = d.status_name || d.status || 'Нет данных'; el.style.color = d.success ? '#059669' : '#dc2626'; }
    }).catch(function() { if (el) { el.textContent = 'Ошибка'; el.style.color = '#dc2626'; } });
};

// ── DobroPost actions ─────────────────────────────────────
function dpResult(msg, ok) {
    var r = document.getElementById('dp-action-result');
    if (r) { r.textContent = (ok ? '✓ ' : '✗ ') + msg; r.style.color = ok ? '#059669' : '#dc2626'; }
}
window.sendToDP = function(id) {
    var btn = event.target; btn.disabled = true; btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Отправка...';
    fetch('$_dpSendUrl', {method:'POST',headers:{'X-CSRF-Token':'$_csrfToken','Content-Type':'application/json'}})
    .then(function(r){return r.json();}).then(function(d){
        dpResult(d.message, d.success);
        if (d.success) setTimeout(function(){location.reload();}, 1500);
        else { btn.disabled = false; btn.innerHTML = '<i class="bi bi-send"></i> Отправить в ДП'; }
    }).catch(function(){ dpResult('Ошибка сети', false); btn.disabled = false; });
};
window.refreshDPStatus = function(id) {
    dpResult('Обновляем...', true);
    fetch('$_dpStatusUrl', {method:'POST',headers:{'X-CSRF-Token':'$_csrfToken'}})
    .then(function(r){return r.json();}).then(function(d){
        dpResult(d.message, d.success);
        if (d.success) setTimeout(function(){location.reload();}, 1000);
    }).catch(function(){ dpResult('Ошибка сети', false); });
};
window.retryDP = function(id) {
    dpResult('Повторная отправка...', true);
    fetch('$_dpRetryUrl', {method:'POST',headers:{'X-CSRF-Token':'$_csrfToken'}})
    .then(function(r){return r.json();}).then(function(d){
        dpResult(d.message, d.success);
        if (d.success) setTimeout(function(){location.reload();}, 1500);
    }).catch(function(){ dpResult('Ошибка сети', false); });
};

// ── Auto-fill DP fields ──────────────────────────────────
window.autoFillDp = function(id) {
    if (!confirm('Заполнить пустые поля ДП из данных заказа и профиля клиента?')) return;
    fetch('$_dpAutoFillUrl', {method:'POST',headers:{'Content-Type':'application/json'}})
    .then(function(r){return r.json();}).then(function(d){
        if (d.success) {
            var msg = d.message;
            if (d.filled && d.filled.length) msg += ': ' + d.filled.join(', ');
            alert(msg);
            if (d.filled && d.filled.length) location.reload();
        } else {
            alert('Ошибка: ' + (d.message || 'Неизвестная ошибка'));
        }
    }).catch(function(){ alert('Ошибка сети'); });
};

// ── MoySklad ──────────────────────────────────────────────
window.syncMoysklad = function(id) {
    var r = document.getElementById('ms-sync-result');
    var r2 = document.getElementById('ms-topbar-sync-result');
    var btn = document.getElementById('btn-sync-ms');
    if (r) { r.textContent = 'Синхронизируем...'; r.style.color = '#6b7280'; }
    if (r2) { r2.textContent = 'Синхронизируем...'; r2.style.color = '#6b7280'; }
    if (btn) { btn.disabled = true; btn.style.opacity = '.6'; }
    fetch('$_msSyncUrl', {method:'POST',headers:{'X-CSRF-Token':'$_csrfToken'}})
    .then(function(r){return r.json();}).then(function(d){
        var msg = (d.success ? '✓ ' : '✗ ') + (d.message||'');
        var clr = d.success ? '#059669' : '#dc2626';
        if (r) { r.textContent = msg; r.style.color = clr; }
        if (r2) { r2.textContent = msg; r2.style.color = clr; }
        if (btn) { btn.disabled = false; btn.style.opacity = '1'; }
        if (d.success) setTimeout(function(){ location.reload(); }, 1200);
    }).catch(function(){
        if (r) { r.textContent = '✗ Ошибка сети'; r.style.color = '#dc2626'; }
        if (r2) { r2.textContent = '✗ Ошибка сети'; r2.style.color = '#dc2626'; }
        if (btn) { btn.disabled = false; btn.style.opacity = '1'; }
    });
};

// ── Delivery fields toggle ────────────────────────────────
window.toggleDeliveryFields = function(method) {
    var pvzEls      = document.querySelectorAll('[data-delivery-group="pvz"]');
    var addrEls     = document.querySelectorAll('[data-delivery-group="address"]');
    var trackEls    = document.querySelectorAll('[data-delivery-group="track"]');
    var pickupEls   = document.querySelectorAll('[data-delivery-group="pickup-info"]');
    var pvzLabel    = document.getElementById('pvz-group-label');

    var showPvz = false, showAddr = false, showTrack = false, showPickup = false;

    switch (method) {
        case 'europochta':
            showPvz = true; showTrack = true;
            if (pvzLabel) pvzLabel.textContent = 'ПВЗ Европочты';
            break;
        case 'cdek':
            showPvz = true; showTrack = true;
            if (pvzLabel) pvzLabel.textContent = 'ПВЗ СДЭК';
            break;
        case 'belpochta':
            showAddr = true; showTrack = true;
            break;
        case 'courier_minsk':
            showAddr = true; showTrack = true;
            break;
        case 'pickup':
        case 'pickup_minsk':
        case 'self':
            showPickup = true;
            break;
        default:
            // unknown method — show addr+track, hide pvz/pickup
            showAddr = true; showTrack = true;
            break;
    }

    pvzEls.forEach(function(el)    { el.style.display = showPvz    ? '' : 'none'; });
    addrEls.forEach(function(el)   { el.style.display = showAddr   ? '' : 'none'; });
    trackEls.forEach(function(el)  { el.style.display = showTrack  ? '' : 'none'; });
    pickupEls.forEach(function(el) { el.style.display = showPickup ? '' : 'none'; });
};

// Init delivery fields visibility on page load
(function() {
    var sel = document.getElementById('delivery-method-select');
    if (sel) toggleDeliveryFields(sel.value);
})();

// ── Add note ──────────────────────────────────────────────
window.addOrderNote = function(id) {
    var ta = document.getElementById('new-note-text');
    var text = ta ? ta.value.trim() : '';
    if (!text) return;
    fetch('$_addNoteUrl', {
        method: 'POST',
        headers: {'Content-Type':'application/json','X-CSRF-Token':'$_csrfToken'},
        body: JSON.stringify({id: id, text: text})
    }).then(function(r){return r.json();}).then(function(d){
        if (d.success) { ta.value = ''; location.reload(); }
    });
};

// ── Expand item detail row ────────────────────────────────
window.toggleItemDetail = function(id, btn) {
    var row = document.getElementById(id);
    if (!row) return;
    var open = row.style.display !== 'none';
    row.style.display = open ? 'none' : 'table-row';
    if (btn) {
        var icon = btn.querySelector('i');
        if (icon) icon.className = open ? 'bi bi-chevron-down' : 'bi bi-chevron-up';
        btn.style.color = open ? '' : 'var(--admin-accent,#2563eb)';
    }
};

// ── Save item fields ──────────────────────────────────────
window.saveItemRow = function(saveBtnEl) {
    var panel = saveBtnEl.closest('.item-expand-panel');
    if (!panel) return;
    var itemId  = panel.dataset.itemId;
    var orderId = panel.dataset.orderId;
    var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
    var saves = [];

    panel.querySelectorAll('[data-ifield]').forEach(function(inp) {
        saves.push(fetch('$_saveItemFieldUrl', {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-CSRF-Token': csrf, 'X-Requested-With': 'XMLHttpRequest'},
            body: JSON.stringify({item_id: itemId, field: inp.dataset.ifield, value: inp.value})
        }));
    });

    panel.querySelectorAll('[data-ofield]').forEach(function(inp) {
        saves.push(fetch('$_saveUrl', {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-CSRF-Token': csrf, 'X-Requested-With': 'XMLHttpRequest'},
            body: JSON.stringify({id: orderId, field: inp.dataset.ofield, value: inp.value})
        }));
    });

    saveBtnEl.disabled = true;
    Promise.all(saves)
        .then(function(resps) { return Promise.all(resps.map(function(r) { return r.json(); })); })
        .then(function(results) {
            saveBtnEl.disabled = false;
            var failed = results.filter(function(r) { return !r.success; });
            if (!failed.length) {
                showFlash('Позиция сохранена');
                var mainRow = panel.closest('tr').previousElementSibling;
                if (mainRow) {
                    var n = panel.querySelector('[data-ifield="product_name"]');
                    var q = panel.querySelector('[data-ifield="quantity"]');
                    var p = panel.querySelector('[data-ifield="price"]');
                    var s = panel.querySelector('[data-ifield="size"]');
                    var c = panel.querySelector('[data-ifield="color"]');
                    if (n) { var nd = mainRow.querySelector('.item-name'); if (nd) nd.textContent = n.value; }
                    var sd = mainRow.querySelector('.item-sku');
                    if (sd) { var pts = []; if (s && s.value) pts.push(s.value); if (c && c.value) pts.push(c.value); sd.textContent = pts.join(' · '); }
                    var cells = mainRow.querySelectorAll('td');
                    if (cells[3] && q) cells[3].textContent = q.value + ' шт.';
                    if (cells[4] && p) cells[4].textContent = parseFloat(p.value).toFixed(2) + ' Br';
                    if (cells[5] && p && q) cells[5].textContent = (parseFloat(p.value) * parseInt(q.value)).toFixed(2) + ' Br';
                }
            } else { alert('Ошибка: ' + failed.map(function(r) { return r.message; }).join(', ')); }
        })
        .catch(function() { saveBtnEl.disabled = false; alert('Ошибка сети'); });
};

// ── Add new item ──────────────────────────────────────────
window.submitAddItem = function(orderId) {
    var name  = document.getElementById('newItemName').value.trim();
    var qty   = parseInt(document.getElementById('newItemQty').value) || 1;
    var price = parseFloat(document.getElementById('newItemPrice').value) || 0;
    var size  = document.getElementById('newItemSize').value.trim();
    if (!name)  { document.getElementById('newItemName').focus(); return; }
    if (!price) { document.getElementById('newItemPrice').focus(); return; }
    var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
    fetch('$_addItemUrl', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-Token': csrf, 'X-Requested-With': 'XMLHttpRequest'},
        body: JSON.stringify({order_id: orderId, product_name: name, quantity: qty, price: price, size: size})
    }).then(function(r) { return r.json(); }).then(function(d) {
        if (d.success) { showFlash('Позиция добавлена'); setTimeout(function() { location.reload(); }, 700); }
        else { alert('Ошибка: ' + (d.message || '')); }
    }).catch(function() { alert('Ошибка сети'); });
};

// ── Customer quick-view modal ─────────────────────────────
window.openCustomerQuickView = function(customerId) {
    var overlay = document.getElementById('cqv-overlay');
    var body    = document.getElementById('cqv-body');
    var link    = document.getElementById('cqv-full-link');
    if (!overlay || !body) return;
    body.innerHTML = '<div class="cqv-loading"><i class="bi bi-arrow-repeat" style="display:block;font-size:1.5rem;margin-bottom:8px;animation:spin 1s linear infinite"></i>Загружаем...</div>';
    if (link) link.href = '/admin/customer/' + customerId;
    overlay.classList.add('open');
    document.body.style.overflow = 'hidden';
    fetch('/admin/customer/quick-view?id=' + customerId, {
        headers: {'X-CSRF-Token': '$_csrfToken', 'X-Requested-With': 'XMLHttpRequest'}
    })
    .then(function(r) {
        if (!r.ok) throw new Error('HTTP ' + r.status);
        return r.text();
    })
    .then(function(html) { body.innerHTML = html; })
    .catch(function(e) {
        body.innerHTML = '<div style="padding:20px;color:#dc2626;text-align:center"><i class="bi bi-exclamation-triangle" style="display:block;font-size:1.5rem;margin-bottom:8px"></i>Ошибка загрузки: ' + e.message + '</div>';
    });
};
window.closeCustomerQuickView = function() {
    var overlay = document.getElementById('cqv-overlay');
    if (overlay) overlay.classList.remove('open');
    document.body.style.overflow = '';
};
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeCustomerQuickView();
});

// ── Create customer profile from guest order ──────────────
window.createCustomerFromOrder = function(orderId) {
    if (!confirm('Создать профиль клиента на основе данных этого заказа?')) return;
    fetch('/admin/customer/create-from-order', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-Token': '$_csrfToken'},
        body: JSON.stringify({order_id: orderId})
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            if (d.customer_url) { window.location.href = d.customer_url; }
            else { location.reload(); }
        } else {
            alert(d.message || 'Ошибка создания профиля');
        }
    })
    .catch(() => alert('Ошибка сети'));
};

// ── Europochta PVZ search ─────────────────────────────────
// Показывает dropdown при фокусе (даже с пустым вводом — весь список),
// фильтрует на ходу при наборе. Источник данных: /admin/order/pvz-search.
var _pvzTimer = null;
var _pvzCache = null;

function _renderPvzList(dd, list) {
    dd.innerHTML = '';
    if (!list || !list.length) {
        dd.innerHTML = '<div class="crm-pvz-opt" style="color:#9ca3af;cursor:default">Ничего не найдено</div>';
        dd.style.display = 'block';
        dd.classList.add('open');
        return;
    }
    // Хедер со счётчиком
    var header = document.createElement('div');
    header.style.cssText = 'padding:6px 12px;font-size:0.7rem;color:#6b7280;background:#f9fafb;border-bottom:1px solid #e5e7eb;position:sticky;top:0;font-weight:600';
    header.textContent = 'Найдено пунктов: ' + list.length;
    dd.appendChild(header);

    list.forEach(function(pvz) {
        var el = document.createElement('div');
        el.className = 'crm-pvz-opt';
        var num  = pvz.num ? '<span style="font-weight:700;color:#4338ca;margin-right:6px">№' + pvz.num + '</span>' : '';
        var city = pvz.city ? '<strong>' + pvz.city + '</strong>' : '';
        var addr = pvz.address || '';
        el.innerHTML =
            '<div class="pvz-city">' + num + city + (addr ? ' — ' + addr : '') + '</div>' +
            (pvz.schedule ? '<div class="pvz-addr">' + pvz.schedule + '</div>' : '');
        el.addEventListener('click', function() {
            var inp = document.getElementById('pvz-search-input');
            var val = (pvz.num ? '№' + pvz.num + ' · ' : '') + (pvz.city ? pvz.city + ', ' : '') + (pvz.address || '');
            if (inp) inp.value = val;
            saveField('pickup_point', pvz.id || pvz.num || val);
            saveField('delivery_address', val);
            dd.classList.remove('open');
            dd.style.display = 'none';
        });
        dd.appendChild(el);
    });
    dd.style.display = 'block';
    dd.classList.add('open');
}

window.searchPVZ = function(q) {
    clearTimeout(_pvzTimer);
    var dd = document.getElementById('pvz-dropdown');
    if (!dd) return;
    q = (q || '').trim();

    // Пустой запрос + есть кеш = показываем весь список из кеша мгновенно
    if (q === '' && _pvzCache) {
        _renderPvzList(dd, _pvzCache);
        return;
    }

    _pvzTimer = setTimeout(function() {
        fetch('/admin/order/pvz-search?q=' + encodeURIComponent(q) + '&limit=' + (q === '' ? 500 : 60), {
            headers: {'X-CSRF-Token': '$_csrfToken'}
        })
        .then(function(r) { return r.json(); })
        .then(function(d) {
            // Кешируем полный список при пустом запросе для быстрого повторного показа
            if (q === '' && Array.isArray(d)) {
                _pvzCache = d;
            }
            _renderPvzList(dd, d || []);
        })
        .catch(function() {
            dd.innerHTML = '<div class="crm-pvz-opt" style="color:#b91c1c;cursor:default">Ошибка загрузки списка</div>';
            dd.style.display = 'block';
            dd.classList.add('open');
        });
    }, q === '' ? 0 : 220);
};
document.addEventListener('click', function(e) {
    var dd = document.getElementById('pvz-dropdown');
    var inp = document.getElementById('pvz-search-input');
    if (dd && inp && !dd.contains(e.target) && e.target !== inp) {
        dd.classList.remove('open');
        dd.style.display = 'none';
    }
});

// ── File upload ───────────────────────────────────────────
window.uploadOrderFiles = function(input, orderId) {
    if (!input.files.length) return;
    var result = document.getElementById('file-upload-result');
    if (result) { result.textContent = 'Загружаем...'; result.style.color = '#6b7280'; }
    var fd = new FormData();
    for (var i = 0; i < input.files.length; i++) fd.append('files[]', input.files[i]);
    fetch('/admin/order/upload-file?id=' + orderId, {
        method: 'POST',
        headers: {'X-CSRF-Token': '$_csrfToken'},
        body: fd
    })
    .then(function(r) { return r.json(); })
    .then(function(d) {
        if (result) {
            result.textContent = (d.success ? '✓ ' : '✗ ') + (d.message || (d.success ? 'Загружено' : 'Ошибка'));
            result.style.color = d.success ? '#059669' : '#dc2626';
        }
        if (d.success) setTimeout(function() { location.reload(); }, 1200);
    })
    .catch(function() {
        if (result) { result.textContent = '✗ Ошибка загрузки'; result.style.color = '#dc2626'; }
    });
};

// ── #12 Buyout guard + save ───────────────────────────────
window.guardStatusChange = function(sel) {
    var from = sel.getAttribute('data-current-status');
    var to   = sel.value;
    var filled = sel.getAttribute('data-buyout-filled') === '1';
    if (from === 'confirmed_and_paid' && to === 'ordered' && !filled) {
        alert('Необходимо заполнить блок «Выкуп» перед переводом в статус «Заказано».');
        sel.value = from;
        return;
    }
    sel.form.submit();
};

window.saveBuyout = function(orderId) {
    var btn    = document.getElementById('btn-save-buyout');
    var result = document.getElementById('buyout-save-result');
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Сохраняем...'; }
    if (result) { result.textContent = ''; }

    var fd = new FormData();
    fd.append('purchase_cost',     (document.getElementById('inp-purchase-cost') || {}).value || '');
    fd.append('purchase_currency', (document.getElementById('inp-purchase-currency') || {}).value || 'CNY');
    fd.append('purchase_date',     (document.getElementById('inp-purchase-date') || {}).value || '');
    fd.append('purchase_user_id',  (document.getElementById('inp-purchase-user') || {}).value || '');
    fd.append('china_track_number',(document.getElementById('inp-china-track') || {}).value || '');
    var fileInput = document.getElementById('inp-receipt-file');
    if (fileInput && fileInput.files[0]) { fd.append('purchase_receipt', fileInput.files[0]); }

    fetch('/admin/order/save-buyout?id=' + orderId, {
        method: 'POST',
        headers: {'X-CSRF-Token': '$_csrfToken', 'X-Requested-With': 'XMLHttpRequest'},
        body: fd
    })
    .then(function(r) { return r.json(); })
    .then(function(d) {
        if (btn) { btn.disabled = false; btn.innerHTML = '<i class="bi bi-floppy-fill"></i> Сохранить выкуп'; }
        if (result) {
            result.textContent = (d.success ? '✓ ' : '✗ ') + (d.message || '');
            result.style.color = d.success ? '#059669' : '#dc2626';
        }
        if (d.success) {
            var badge = document.getElementById('buyout-status-badge');
            if (badge) {
                badge.innerHTML = '<i class="bi bi-check-circle-fill"></i> Заполнен';
                badge.style.cssText = 'background:#d1fae5;color:#065f46;font-size:0.7rem;font-weight:700;padding:2px 8px;border-radius:6px';
            }
            var sel = document.querySelector('select[data-buyout-filled]');
            if (sel) { sel.setAttribute('data-buyout-filled', '1'); }
            // Update product_price display if it was auto-filled
            if (d.product_price > 0) {
                var ppEl = document.querySelector('.crm-editable[data-field="product_price"]');
                if (ppEl && ppEl.querySelector('.crm-editable-empty')) {
                    ppEl.textContent = parseFloat(d.product_price).toFixed(2) + ' Br';
                }
            }
        }
    })
    .catch(function() {
        if (btn) { btn.disabled = false; btn.innerHTML = '<i class="bi bi-floppy-fill"></i> Сохранить выкуп'; }
        if (result) { result.textContent = '✗ Ошибка сети'; result.style.color = '#dc2626'; }
    });
};
JS
, \yii\web\View::POS_END);
?>
