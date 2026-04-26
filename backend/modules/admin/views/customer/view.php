<?php

use yii\helpers\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var app\backend\modules\catalog\models\Customer $customer */
/** @var app\backend\modules\catalog\models\Order[] $orders */

$this->title = 'Покупатель: ' . $customer->getFullName();

$avatarLetter = mb_strtoupper(mb_substr($customer->first_name ?: $customer->email, 0, 1));

$currentUser = Yii::$app->user->identity;
$isAdmin = method_exists($currentUser, 'isAdmin') ? $currentUser->isAdmin() : ($currentUser->role === 'admin');

$points = (int)($loyaltyBalance ?? 0);
$totalSpent = $customer->total_spent ?? 0;
$earned = (int)($loyaltyTotalEarned ?? $points);
$totalForLevel = max($earned, $totalSpent);
if ($totalForLevel >= 50000)      { $level = 'Platinum'; $levelColor = '#e5e4e2'; $levelText = '#555'; $nextThreshold = 50000; }
elseif ($totalForLevel >= 15000)  { $level = 'Gold';     $levelColor = '#ffd700'; $levelText = '#7a6000'; $nextThreshold = 50000; }
elseif ($totalForLevel >= 5000)   { $level = 'Silver';   $levelColor = '#c0c0c0'; $levelText = '#444';    $nextThreshold = 15000; }
else                              { $level = 'Bronze';   $levelColor = '#cd7f32'; $levelText = '#fff';    $nextThreshold = 5000; }
$progress = ($level !== 'Platinum' && $nextThreshold > 0) ? min(100, round($totalForLevel / $nextThreshold * 100)) : 100;

$history = $loyaltyHistory ?? [];
if (empty($history)) {
    try {
        $history = \app\backend\modules\loyalty\models\LoyaltyPoints::find()
            ->where(['customer_id' => $customer->id])
            ->orderBy(['created_at' => SORT_DESC])
            ->limit(50)
            ->all();
    } catch (\Exception $e) { $history = []; }
}
?>

<style>
/* ── CRM Profile ─────────────────────────────────── */
.crm-wrap { max-width: none; width: 100%; margin: 0; padding: 0 0 40px; }

/* Hero */
.crm-hero {
    background: linear-gradient(135deg, #1a1f36 0%, #202223 55%, #008060 100%);
    border-radius: var(--admin-radius-lg);
    padding: 32px 32px 28px;
    display: flex;
    align-items: center;
    gap: 24px;
    margin-bottom: 20px;
    position: relative;
    overflow: hidden;
}
.crm-hero::before {
    content: '';
    position: absolute;
    inset: 0;
    background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Ccircle cx='30' cy='30' r='30'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    pointer-events: none;
}
.crm-hero-back {
    position: absolute;
    top: 20px; left: 20px;
    width: 32px; height: 32px;
    border-radius: 50%;
    background: rgba(255,255,255,0.12);
    border: 1px solid rgba(255,255,255,0.2);
    display: flex; align-items: center; justify-content: center;
    color: #fff;
    text-decoration: none;
    font-size: 14px;
    transition: background .15s;
    z-index: 1;
}
.crm-hero-back:hover { background: rgba(255,255,255,0.22); color: #fff; }

.crm-avatar {
    flex-shrink: 0;
    width: 84px; height: 84px;
    border-radius: 50%;
    background: linear-gradient(135deg, #00b37e 0%, #00896f 100%);
    border: 3px solid rgba(255,255,255,0.25);
    display: flex; align-items: center; justify-content: center;
    font-size: 34px; font-weight: 700; color: #fff;
    box-shadow: 0 8px 24px rgba(0,0,0,0.25);
    margin-top: 8px;
}
.crm-hero-info { flex: 1; min-width: 0; padding-top: 4px; }
.crm-hero-name {
    font-size: 24px; font-weight: 700; color: #fff;
    line-height: 1.2; margin-bottom: 4px;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.crm-hero-email {
    font-size: 13px; color: rgba(255,255,255,0.7);
    margin-bottom: 10px;
    display: flex; align-items: center; gap: 5px;
}
.crm-hero-badges { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.crm-status-badge {
    padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; letter-spacing: .4px;
}
.crm-status-badge.active  { background: rgba(0,176,118,0.25); color: #6effc9; border: 1px solid rgba(0,176,118,0.4); }
.crm-status-badge.inactive{ background: rgba(255,100,100,0.2); color: #ffaaaa; border: 1px solid rgba(255,100,100,0.3); }
.crm-hero-since { font-size: 12px; color: rgba(255,255,255,0.5); }

.crm-hero-actions {
    display: flex; flex-direction: column; gap: 8px; flex-shrink: 0; z-index: 1;
}
.crm-btn-hero {
    padding: 8px 16px; border-radius: var(--admin-radius-sm);
    font-size: 13px; font-weight: 500; cursor: pointer; border: none;
    text-decoration: none; display: inline-flex; align-items: center; gap: 6px;
    transition: all .15s;
    white-space: nowrap;
}
.crm-btn-hero.primary { background: var(--admin-accent); color: #fff; }
.crm-btn-hero.primary:hover { background: var(--admin-accent-hover); color: #fff; }
.crm-btn-hero.ghost { background: rgba(255,255,255,0.12); color: #fff; border: 1px solid rgba(255,255,255,0.2); }
.crm-btn-hero.ghost:hover { background: rgba(255,255,255,0.22); }

/* Stat cards */
.crm-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    margin-bottom: 20px;
}
.crm-stat {
    background: var(--admin-surface);
    border: 1px solid var(--admin-border);
    border-radius: var(--admin-radius);
    padding: 20px;
    display: flex; align-items: center; gap: 16px;
    box-shadow: var(--admin-shadow-sm);
    transition: box-shadow .15s;
}
.crm-stat:hover { box-shadow: var(--admin-shadow-md); }
.crm-stat-icon {
    width: 48px; height: 48px; border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 22px; flex-shrink: 0;
}
.crm-stat-icon.orders  { background: #eff6ff; color: #3b82f6; }
.crm-stat-icon.money   { background: #f0fdf4; color: #16a34a; }
.crm-stat-icon.date    { background: #fefce8; color: #ca8a04; }
.crm-stat-icon.bonus   { background: #fdf4ff; color: #a855f7; }
.crm-stat-body { min-width: 0; }
.crm-stat-value { font-size: 22px; font-weight: 700; color: var(--admin-text); line-height: 1; }
.crm-stat-label { font-size: 12px; color: var(--admin-text-secondary); margin-top: 3px; }

/* Body grid */
.crm-body {
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 20px;
    align-items: start;
}

/* Cards */
.crm-card {
    background: var(--admin-surface);
    border: 1px solid var(--admin-border);
    border-radius: var(--admin-radius);
    box-shadow: var(--admin-shadow-sm);
    margin-bottom: 16px;
    overflow: hidden;
}
.crm-card-header {
    padding: 16px 20px;
    border-bottom: 1px solid var(--admin-border-subdued);
    display: flex; align-items: center; gap: 8px;
}
.crm-card-title {
    font-size: 14px; font-weight: 600; color: var(--admin-text);
    display: flex; align-items: center; gap: 7px;
}
.crm-card-title i { color: var(--admin-text-secondary); }
.crm-card-body { padding: 20px; }

/* Contact grid */
.crm-contact-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0;
}
.crm-contact-item {
    display: flex; align-items: flex-start; gap: 12px;
    padding: 12px 16px;
    border-bottom: 1px solid var(--admin-border-subdued);
}
.crm-contact-item:nth-last-child(-n+2) { border-bottom: none; }
.crm-contact-icon {
    width: 30px; height: 30px;
    border-radius: 8px;
    background: var(--admin-bg);
    display: flex; align-items: center; justify-content: center;
    font-size: 13px; color: var(--admin-text-secondary);
    flex-shrink: 0; margin-top: 1px;
}
.crm-contact-label { font-size: 11px; color: var(--admin-text-secondary); margin-bottom: 2px; }
.crm-contact-value { font-size: 13px; color: var(--admin-text); font-weight: 500; }

/* Verification items */
.crm-verify-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0;
    border-top: 1px solid var(--admin-border-subdued);
}
.crm-verify-item {
    display: flex; align-items: center; gap: 10px;
    padding: 10px 16px;
    border-bottom: 1px solid var(--admin-border-subdued);
}
.crm-verify-item:nth-last-child(-n+2) { border-bottom: none; }
.crm-verify-label { font-size: 12px; color: var(--admin-text-secondary); }
.crm-verify-yes { display: flex; align-items: center; gap: 4px; font-size: 12px; font-weight: 600; color: var(--admin-success); }
.crm-verify-no  { font-size: 12px; color: var(--admin-text-subdued); }

/* Tags */
.crm-tags { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 12px; }
.crm-tag {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 10px; border-radius: 20px;
    background: var(--admin-info-bg); color: var(--admin-info);
    font-size: 12px; font-weight: 500;
    border: 1px solid rgba(0,120,212,0.15);
    cursor: pointer; transition: all .15s;
}
.crm-tag:hover { background: rgba(0,120,212,0.15); }
.crm-tag-add {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 4px 10px; border-radius: 20px;
    background: var(--admin-bg); color: var(--admin-text-secondary);
    font-size: 12px; font-weight: 500;
    border: 1px dashed var(--admin-border);
    cursor: pointer; transition: all .15s;
}
.crm-tag-add:hover { border-color: var(--admin-accent); color: var(--admin-accent); background: rgba(0,128,96,0.05); }

/* Note cards */
.crm-note {
    background: var(--admin-bg);
    border: 1px solid var(--admin-border-subdued);
    border-radius: var(--admin-radius-sm);
    padding: 10px 12px;
    margin-bottom: 8px;
}
.crm-note-meta { display: flex; justify-content: space-between; margin-bottom: 4px; }
.crm-note-author { font-size: 11px; font-weight: 600; color: var(--admin-text); }
.crm-note-date   { font-size: 11px; color: var(--admin-text-secondary); }
.crm-note-text   { font-size: 13px; color: var(--admin-text); margin: 0; line-height: 1.5; }

/* Orders list */
.crm-order-item {
    display: flex; align-items: center; justify-content: space-between;
    padding: 12px 16px;
    border-bottom: 1px solid var(--admin-border-subdued);
    text-decoration: none;
    transition: background .12s;
}
.crm-order-item:last-child { border-bottom: none; }
.crm-order-item:hover { background: var(--admin-surface-hover); }
.crm-order-num { font-size: 13px; font-weight: 600; color: var(--admin-text); }
.crm-order-date { font-size: 11px; color: var(--admin-text-secondary); margin-top: 2px; }
.crm-order-right { text-align: right; }
.crm-order-amount { font-size: 13px; font-weight: 600; color: var(--admin-text); }
.crm-order-status {
    display: inline-block; margin-top: 3px;
    padding: 2px 8px; border-radius: 20px; font-size: 10px; font-weight: 600;
}
.crm-order-status.new       { background: var(--admin-info-bg);    color: var(--admin-info); }
.crm-order-status.paid      { background: var(--admin-success-bg);  color: var(--admin-success); }
.crm-order-status.delivered { background: #d1fae5;                  color: #065f46; }
.crm-order-status.cancelled { background: var(--admin-danger-bg);   color: var(--admin-danger); }
.crm-order-status.pending   { background: var(--admin-warning-bg);  color: var(--admin-warning); }

/* Loyalty sidebar card */
.crm-loyalty-header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 12px;
}
.crm-loyalty-points { font-size: 28px; font-weight: 800; color: var(--admin-text); }
.crm-loyalty-points small { font-size: 13px; font-weight: 400; color: var(--admin-text-secondary); }
.crm-level-badge {
    padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 700;
}
.crm-progress { height: 6px; background: var(--admin-border); border-radius: 6px; margin-bottom: 14px; overflow: hidden; }
.crm-progress-bar { height: 100%; border-radius: 6px; background: linear-gradient(90deg, var(--admin-accent), #00c98a); transition: width .3s; }

/* Sidebar info rows */
.crm-info-row {
    display: flex; justify-content: space-between; align-items: baseline;
    padding: 8px 0;
    border-bottom: 1px solid var(--admin-border-subdued);
    font-size: 13px;
}
.crm-info-row:last-child { border-bottom: none; }
.crm-info-row-label { color: var(--admin-text-secondary); }
.crm-info-row-value { font-weight: 500; color: var(--admin-text); text-align: right; max-width: 55%; word-break: break-all; }

/* Action buttons (sidebar) */
.crm-actions { display: flex; flex-direction: column; gap: 8px; }
.crm-action-btn {
    width: 100%; padding: 10px 14px;
    border-radius: var(--admin-radius-sm);
    font-size: 13px; font-weight: 500;
    display: flex; align-items: center; gap: 8px;
    cursor: pointer; border: 1px solid var(--admin-border);
    background: var(--admin-surface);
    color: var(--admin-text);
    text-decoration: none;
    transition: all .15s;
}
.crm-action-btn:hover { background: var(--admin-bg); box-shadow: var(--admin-shadow-sm); }
.crm-action-btn.warning { color: #92400e; border-color: #fcd34d; background: #fffbeb; }
.crm-action-btn.warning:hover { background: #fef3c7; }
.crm-action-btn.danger  { color: var(--admin-danger); border-color: #fca5a5; background: var(--admin-danger-bg); }
.crm-action-btn.danger:hover  { background: #fee2e2; }

/* Points form */
.crm-points-form {
    background: var(--admin-bg); border: 1px solid var(--admin-border);
    border-radius: var(--admin-radius-sm); padding: 12px; margin-top: 10px;
}
.crm-points-form input {
    width: 100%; box-sizing: border-box;
    margin-bottom: 8px; font-size: 13px;
}

/* Passport card */
.crm-passport-row {
    display: flex; gap: 8px; align-items: center;
    padding: 10px 16px; border-bottom: 1px solid var(--admin-border-subdued);
}
.crm-passport-row:last-child { border-bottom: none; }
.crm-passport-icon { font-size: 15px; color: var(--admin-text-secondary); width: 20px; }
.crm-passport-label { font-size: 11px; color: var(--admin-text-secondary); }
.crm-passport-value { font-size: 13px; font-weight: 500; color: var(--admin-text); }

/* Bonus modal */
.crm-modal-overlay {
    position: fixed; inset: 0; background: rgba(0,0,0,.45);
    display: flex; align-items: center; justify-content: center; z-index: 9999;
}
.crm-modal {
    background: var(--admin-surface); border-radius: var(--admin-radius-lg);
    width: 560px; max-width: 95vw; max-height: 80vh;
    display: flex; flex-direction: column;
    box-shadow: var(--admin-shadow-lg);
}
.crm-modal-head {
    padding: 20px 24px; border-bottom: 1px solid var(--admin-border);
    display: flex; align-items: center; justify-content: space-between;
}
.crm-modal-head h3 { font-size: 16px; font-weight: 600; margin: 0; }
.crm-modal-close {
    width: 28px; height: 28px; border-radius: 6px; border: none;
    background: var(--admin-bg); cursor: pointer; font-size: 16px;
    display: flex; align-items: center; justify-content: center;
    color: var(--admin-text-secondary);
}
.crm-modal-close:hover { background: var(--admin-border); }
.crm-modal-body { flex: 1; overflow-y: auto; padding: 0; }
.crm-modal-foot {
    padding: 12px 24px; border-top: 1px solid var(--admin-border);
    display: flex; justify-content: flex-end;
}

.crm-bonus-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.crm-bonus-table th { padding: 10px 16px; text-align: left; font-size: 11px; font-weight: 600; color: var(--admin-text-secondary); text-transform: uppercase; letter-spacing: .5px; border-bottom: 1px solid var(--admin-border); background: var(--admin-bg); }
.crm-bonus-table td { padding: 10px 16px; border-bottom: 1px solid var(--admin-border-subdued); }
.crm-bonus-table tr:last-child td { border-bottom: none; }
.bonus-pts { font-weight: 700; }
.bonus-pts.pos { color: var(--admin-success); }
.bonus-pts.neg { color: var(--admin-danger); }

.crm-empty { padding: 32px; text-align: center; color: var(--admin-text-secondary); }
.crm-empty i { font-size: 36px; display: block; margin-bottom: 8px; opacity: .4; }

.crm-section-label {
    font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .6px;
    color: var(--admin-text-secondary); margin-bottom: 10px;
}

.crm-tag-input-row { display: flex; gap: 8px; margin-top: 8px; }
.crm-tag-input-row input { flex: 1; font-size: 12px; }
.crm-note-input-row { display: flex; gap: 8px; }
.crm-note-input-row textarea { flex: 1; font-size: 13px; resize: vertical; }

/* Toggle switch */
.toggle-switch { position: relative; display: inline-block; width: 40px; height: 22px; }
.toggle-switch input { opacity: 0; width: 0; height: 0; }
.toggle-slider { position: absolute; cursor: pointer; inset: 0; background: #ccc; border-radius: 22px; transition: .2s; }
.toggle-slider:before { content: ''; position: absolute; width: 16px; height: 16px; left: 3px; bottom: 3px; background: #fff; border-radius: 50%; transition: .2s; }
input:checked + .toggle-slider { background: var(--admin-accent); }
input:checked + .toggle-slider:before { transform: translateX(18px); }

@media (max-width: 900px) {
    .crm-body { grid-template-columns: 1fr; }
    .crm-stats { grid-template-columns: 1fr 1fr; }
    .crm-hero { flex-wrap: wrap; }
    .crm-hero-actions { flex-direction: row; }
    .crm-contact-grid { grid-template-columns: 1fr; }
    .crm-verify-grid { grid-template-columns: 1fr; }
}
</style>

<div class="crm-wrap">

    <!-- ── Hero ──────────────────────────────────────── -->
    <div class="crm-hero">
        <a href="<?= Url::to(['customer/index']) ?>" class="crm-hero-back" title="Назад">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div class="crm-avatar"><?= $avatarLetter ?></div>
        <div class="crm-hero-info">
            <div class="crm-hero-name"><?= Html::encode($customer->getFullName()) ?></div>
            <div class="crm-hero-email"><i class="bi bi-envelope-fill"></i> <?= Html::encode($customer->email) ?></div>
            <div class="crm-hero-badges">
                <span class="crm-status-badge <?= $customer->status == 10 ? 'active' : 'inactive' ?>">
                    <i class="bi bi-circle-fill" style="font-size:7px"></i>
                    <?= $customer->getStatusLabel() ?>
                </span>
                <?php if ($customer->created_at): ?>
                <span class="crm-hero-since">С <?= date('Y', $customer->created_at) ?> года</span>
                <?php endif ?>
            </div>
        </div>
        <div class="crm-hero-actions">
            <a href="<?= Url::to(['customer/update', 'id' => $customer->id]) ?>" class="crm-btn-hero primary">
                <i class="bi bi-pencil-square"></i> Редактировать
            </a>
            <button type="button" class="crm-btn-hero ghost" onclick="openBonusHistory()">
                <i class="bi bi-clock-history"></i> История бонусов
            </button>
        </div>
    </div>

    <!-- ── Stat cards ─────────────────────────────────── -->
    <div class="crm-stats">
        <div class="crm-stat">
            <div class="crm-stat-icon orders"><i class="bi bi-bag-check-fill"></i></div>
            <div class="crm-stat-body">
                <div class="crm-stat-value"><?= (int)$customer->orders_count ?></div>
                <div class="crm-stat-label">Заказов</div>
            </div>
        </div>
        <div class="crm-stat">
            <div class="crm-stat-icon money"><i class="bi bi-currency-exchange"></i></div>
            <div class="crm-stat-body">
                <div class="crm-stat-value"><?= Yii::$app->formatter->asCurrency($customer->total_spent, 'BYN') ?></div>
                <div class="crm-stat-label">Потрачено</div>
            </div>
        </div>
        <div class="crm-stat">
            <div class="crm-stat-icon date"><i class="bi bi-calendar-check-fill"></i></div>
            <div class="crm-stat-body">
                <div class="crm-stat-value"><?= $customer->last_order_at ? Yii::$app->formatter->asDate($customer->last_order_at, 'short') : '—' ?></div>
                <div class="crm-stat-label">Последний заказ</div>
            </div>
        </div>
    </div>

    <!-- ── 2-column body ──────────────────────────────── -->
    <div class="crm-body">

        <!-- ═══ MAIN COLUMN ═══ -->
        <div class="crm-main">

            <!-- Contact info -->
            <div class="crm-card">
                <div class="crm-card-header">
                    <div class="crm-card-title"><i class="bi bi-person-lines-fill"></i> Контактные данные</div>
                </div>
                <div class="crm-contact-grid">
                    <div class="crm-contact-item">
                        <div class="crm-contact-icon"><i class="bi bi-envelope"></i></div>
                        <div>
                            <div class="crm-contact-label">Email</div>
                            <div class="crm-contact-value"><?= Html::encode($customer->email) ?></div>
                        </div>
                    </div>
                    <div class="crm-contact-item">
                        <div class="crm-contact-icon"><i class="bi bi-telephone"></i></div>
                        <div>
                            <div class="crm-contact-label">Телефон</div>
                            <div class="crm-contact-value"><?= Html::encode($customer->phone ?: '—') ?></div>
                        </div>
                    </div>
                    <div class="crm-contact-item">
                        <div class="crm-contact-icon"><i class="bi bi-person-badge"></i></div>
                        <div>
                            <div class="crm-contact-label">Фамилия</div>
                            <div class="crm-contact-value"><?= Html::encode($customer->last_name ?: '—') ?></div>
                        </div>
                    </div>
                    <div class="crm-contact-item">
                        <div class="crm-contact-icon"><i class="bi bi-person"></i></div>
                        <div>
                            <div class="crm-contact-label">Имя</div>
                            <div class="crm-contact-value"><?= Html::encode($customer->first_name ?: '—') ?></div>
                        </div>
                    </div>
                    <div class="crm-contact-item">
                        <div class="crm-contact-icon"><i class="bi bi-person-fill"></i></div>
                        <div>
                            <div class="crm-contact-label">Отчество</div>
                            <div class="crm-contact-value"><?= Html::encode($customer->middle_name ?: '—') ?></div>
                        </div>
                    </div>
                    <div class="crm-contact-item">
                        <div class="crm-contact-icon"><i class="bi bi-cake2"></i></div>
                        <div>
                            <div class="crm-contact-label">Дата рождения</div>
                            <div class="crm-contact-value"><?= $customer->birth_date ? Yii::$app->formatter->asDate($customer->birth_date, 'long') : '—' ?></div>
                        </div>
                    </div>
                    <div class="crm-contact-item">
                        <div class="crm-contact-icon"><i class="bi bi-gender-ambiguous"></i></div>
                        <div>
                            <div class="crm-contact-label">Пол</div>
                            <div class="crm-contact-value"><?= $customer->gender === 'male' ? 'Мужской' : ($customer->gender === 'female' ? 'Женский' : '—') ?></div>
                        </div>
                    </div>
                    <div class="crm-contact-item">
                        <div class="crm-contact-icon"><i class="bi bi-globe2"></i></div>
                        <div>
                            <div class="crm-contact-label">Страна</div>
                            <div class="crm-contact-value"><?= Html::encode($customer->default_country ?: '—') ?></div>
                        </div>
                    </div>
                    <div class="crm-contact-item">
                        <div class="crm-contact-icon"><i class="bi bi-building"></i></div>
                        <div>
                            <div class="crm-contact-label">Город</div>
                            <div class="crm-contact-value"><?= Html::encode($customer->default_city ?: '—') ?></div>
                        </div>
                    </div>
                    <div class="crm-contact-item">
                        <div class="crm-contact-icon"><i class="bi bi-geo-alt"></i></div>
                        <div>
                            <div class="crm-contact-label">Адрес</div>
                            <div class="crm-contact-value"><?= Html::encode($customer->default_address ?: '—') ?></div>
                        </div>
                    </div>
                    <div class="crm-contact-item">
                        <div class="crm-contact-icon"><i class="bi bi-mailbox"></i></div>
                        <div>
                            <div class="crm-contact-label">Индекс</div>
                            <div class="crm-contact-value"><?= Html::encode($customer->default_postal_code ?: '—') ?></div>
                        </div>
                    </div>
                </div>

                <!-- Verification row -->
                <div class="crm-verify-grid">
                    <div class="crm-verify-item">
                        <i class="bi bi-<?= $customer->email_verified ? 'check-circle-fill' : 'x-circle' ?>" style="color:<?= $customer->email_verified ? 'var(--admin-success)' : 'var(--admin-text-subdued)' ?>"></i>
                        <div>
                            <div class="crm-verify-label">Email</div>
                            <?php if ($customer->email_verified): ?>
                                <div class="crm-verify-yes">Подтверждён</div>
                            <?php else: ?>
                                <div class="crm-verify-no">Не подтверждён</div>
                            <?php endif ?>
                        </div>
                    </div>
                    <div class="crm-verify-item">
                        <i class="bi bi-<?= $customer->phone_verified ? 'check-circle-fill' : 'x-circle' ?>" style="color:<?= $customer->phone_verified ? 'var(--admin-success)' : 'var(--admin-text-subdued)' ?>"></i>
                        <div>
                            <div class="crm-verify-label">Телефон</div>
                            <?php if ($customer->phone_verified): ?>
                                <div class="crm-verify-yes">Подтверждён</div>
                            <?php else: ?>
                                <div class="crm-verify-no">Не подтверждён</div>
                            <?php endif ?>
                        </div>
                    </div>
                    <div class="crm-verify-item">
                        <i class="bi bi-<?= $customer->subscribe_news ? 'bell-fill' : 'bell-slash' ?>" style="color:<?= $customer->subscribe_news ? 'var(--admin-info)' : 'var(--admin-text-subdued)' ?>"></i>
                        <div>
                            <div class="crm-verify-label">Рассылка новостей</div>
                            <?php if ($customer->subscribe_news): ?>
                                <div class="crm-verify-yes">Подписан</div>
                            <?php else: ?>
                                <div class="crm-verify-no">Нет</div>
                            <?php endif ?>
                        </div>
                    </div>
                    <div class="crm-verify-item">
                        <i class="bi bi-<?= $customer->subscribe_promo ? 'tag-fill' : 'tag' ?>" style="color:<?= $customer->subscribe_promo ? 'var(--admin-info)' : 'var(--admin-text-subdued)' ?>"></i>
                        <div>
                            <div class="crm-verify-label">Рассылка акций</div>
                            <?php if ($customer->subscribe_promo): ?>
                                <div class="crm-verify-yes">Подписан</div>
                            <?php else: ?>
                                <div class="crm-verify-no">Нет</div>
                            <?php endif ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Passport data -->
            <?php if ($customer->passport_series || $customer->passport_number || $customer->inn): ?>
            <div class="crm-card">
                <div class="crm-card-header">
                    <div class="crm-card-title"><i class="bi bi-person-vcard-fill"></i> Паспортные данные</div>
                </div>
                <div class="crm-passport-row">
                    <i class="crm-passport-icon bi bi-credit-card-2-front"></i>
                    <div>
                        <div class="crm-passport-label">Серия и номер</div>
                        <div class="crm-passport-value">
                            <?php if ($isAdmin): ?>
                                <?= Html::encode(trim(($customer->passport_series ?? '') . ' ' . ($customer->passport_number ?? ''))) ?: '—' ?>
                            <?php else: ?>
                                АВ **** *****
                            <?php endif ?>
                        </div>
                    </div>
                </div>
                <div class="crm-passport-row">
                    <i class="crm-passport-icon bi bi-hash"></i>
                    <div>
                        <div class="crm-passport-label">Идентификационный номер</div>
                        <div class="crm-passport-value">
                            <?php if ($isAdmin): ?>
                                <?= Html::encode($customer->inn ?? '—') ?>
                            <?php else: ?>
                                * * * * * * * * * * *
                            <?php endif ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif ?>

            <!-- Tags & Notes -->
            <div class="crm-card">
                <div class="crm-card-header">
                    <div class="crm-card-title"><i class="bi bi-tags-fill"></i> Теги и заметки</div>
                </div>
                <div class="crm-card-body">

                    <!-- Tags -->
                    <div class="crm-section-label">Теги</div>
                    <div id="customer-tags" class="crm-tags">
                        <?php foreach (($tags ?? []) as $tag): ?>
                        <span class="crm-tag" onclick="removeTag(<?= $customer->id ?>, '<?= Html::encode($tag) ?>')">
                            <?= Html::encode($tag) ?> <i class="bi bi-x"></i>
                        </span>
                        <?php endforeach ?>
                        <?php $presetTags = ['VIP', 'Оптовик', 'Проблемный'] ?>
                        <?php foreach ($presetTags as $pt): if (!in_array($pt, ($tags ?? []))): ?>
                        <button class="crm-tag-add" onclick="addTag(<?= $customer->id ?>, '<?= $pt ?>')">
                            <i class="bi bi-plus"></i> <?= $pt ?>
                        </button>
                        <?php endif; endforeach ?>
                    </div>
                    <div class="crm-tag-input-row">
                        <input type="text" id="custom-tag-input" class="form-control" placeholder="Новый тег...">
                        <button class="admin-btn admin-btn-secondary admin-btn-sm" onclick="addTag(<?= $customer->id ?>, document.getElementById('custom-tag-input').value)">
                            <i class="bi bi-plus-lg"></i> Добавить
                        </button>
                    </div>

                    <hr style="margin: 16px 0; border: none; border-top: 1px solid var(--admin-border-subdued);">

                    <!-- Notes -->
                    <div class="crm-section-label">Заметки команды</div>
                    <div id="customer-notes-list" style="margin-bottom:10px">
                        <?php foreach (($notes ?? []) as $note): ?>
                        <div class="crm-note">
                            <div class="crm-note-meta">
                                <span class="crm-note-author"><i class="bi bi-person-circle"></i> <?= Html::encode($note->author->username ?? 'Система') ?></span>
                                <span class="crm-note-date"><?= Yii::$app->formatter->asDatetime($note->created_at) ?></span>
                            </div>
                            <p class="crm-note-text"><?= Html::encode($note->text) ?></p>
                        </div>
                        <?php endforeach ?>
                        <?php if (empty($notes)): ?>
                        <div style="font-size:13px;color:var(--admin-text-secondary);padding:8px 0">Заметок пока нет</div>
                        <?php endif ?>
                    </div>
                    <div class="crm-note-input-row">
                        <textarea id="customer-note-text" class="form-control" rows="2" placeholder="Добавить заметку..."></textarea>
                        <button class="admin-btn admin-btn-primary" onclick="addCustomerNote(<?= $customer->id ?>)" title="Отправить">
                            <i class="bi bi-send-fill"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Orders — full list view -->
            <?php
            $statusPillMap = [
                'new'                    => ['bg' => '#eff6ff', 'color' => '#2563eb'],
                'created'                => ['bg' => '#f3f4f6', 'color' => '#6b7280'],
                'paid'                   => ['bg' => '#f0fdf4', 'color' => '#16a34a'],
                'confirmed'              => ['bg' => '#e0e7ff', 'color' => '#3730a3'],
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
            $funnelDotColors = [
                'new' => '#6b7280', 'created' => '#6b7280', 'paid' => '#059669',
                'confirmed' => '#3730a3', 'confirmed_and_paid' => '#059669', 'ordered' => '#d97706',
                'processing' => '#d97706', 'awaiting_warehouse' => '#7c3aed',
                'international_delivery' => '#2563eb', 'at_warehouse' => '#7c3aed',
                'local_delivery' => '#2563eb', 'shipped' => '#2563eb', 'in_transit' => '#2563eb',
                'delivered' => '#16a34a', 'cancelled' => '#dc2626', 'canceled' => '#dc2626',
                'returned' => '#dc2626', 'imported' => '#6b7280',
            ];
            $allStatuses = method_exists(Yii::$app, 'settings') && method_exists(Yii::$app->settings, 'getStatuses')
                ? Yii::$app->settings->getStatuses()
                : [];
            $totalOrdersCount = !empty($allOrders) ? count($allOrders) : 0;
            $totalOrdersSum = 0;
            $statusCounts = [];
            if (!empty($allOrders)) {
                foreach ($allOrders as $_o) {
                    $totalOrdersSum += (float)$_o->total_amount;
                    $s = $_o->status ?? 'unknown';
                    $statusCounts[$s] = ($statusCounts[$s] ?? 0) + 1;
                }
            }
            ?>
            <style>
                .co-status-pill{display:inline-block;padding:3px 9px;border-radius:20px;font-size:11px;font-weight:700;white-space:nowrap;line-height:1.6}
                .co-track-badge{font-size:.7rem;font-family:monospace;background:#eff6ff;color:var(--admin-primary,#2563eb);padding:2px 6px;border-radius:4px;max-width:110px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;display:inline-block}
                .co-orders-table{width:100%;border-collapse:collapse;font-size:.8125rem}
                .co-orders-table thead th{padding:8px 10px;font-size:11px;font-weight:600;color:var(--admin-text-secondary,#9ca3af);text-transform:uppercase;letter-spacing:.5px;border-bottom:2px solid var(--admin-border,#e5e7eb);background:var(--admin-bg,#f9fafb);position:sticky;top:0;z-index:1;white-space:nowrap}
                .co-orders-table tbody tr{border-bottom:1px solid var(--admin-border-subdued,#f3f4f6);transition:background .1s}
                .co-orders-table tbody tr:hover{background:var(--admin-surface-hover,#f9fafb)}
                .co-orders-table tbody td{padding:7px 10px;vertical-align:middle}
                .co-funnel-bar{display:flex;flex-wrap:wrap;gap:4px;padding:12px 16px;border-bottom:1px solid var(--admin-border,#e5e7eb);background:var(--admin-bg,#f9fafb)}
                .co-funnel-pill{display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:20px;font-size:11px;font-weight:600;cursor:pointer;border:1px solid var(--admin-border,#e5e7eb);background:var(--admin-surface,#fff);color:var(--admin-text-secondary,#6b7280);transition:all .15s;white-space:nowrap}
                .co-funnel-pill:hover,.co-funnel-pill.active{background:var(--admin-primary,#2563eb);color:#fff;border-color:var(--admin-primary,#2563eb)}
                .co-funnel-pill .dot{width:7px;height:7px;border-radius:50%;flex-shrink:0}
                .co-funnel-pill .count{font-weight:800;font-size:12px}
            </style>
            <div class="crm-card" style="overflow:hidden">
                <div class="crm-card-header" style="justify-content:space-between;flex-wrap:wrap;gap:8px">
                    <div class="crm-card-title"><i class="bi bi-bag-check-fill"></i> Заказы</div>
                    <?php if ($totalOrdersCount > 0): ?>
                    <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap">
                        <span style="font-size:12px;color:var(--admin-text-secondary)">
                            <strong style="color:var(--admin-text);font-size:14px"><?= $totalOrdersCount ?></strong>
                            <?= $totalOrdersCount === 1 ? 'заказ' : ($totalOrdersCount < 5 ? 'заказа' : 'заказов') ?>
                            &middot;
                            <strong style="color:var(--admin-text)"><?= Yii::$app->formatter->asCurrency($totalOrdersSum, 'BYN') ?></strong>
                        </span>
                        <a href="<?= Url::to(['/admin/order/index', 'customer_id' => $customer->id]) ?>"
                           class="admin-btn admin-btn-secondary admin-btn-xs" style="font-size:11px;padding:3px 10px">
                            <i class="bi bi-box-arrow-up-right"></i> Открыть в заказах
                        </a>
                    </div>
                    <?php endif ?>
                </div>

                <?php if (!empty($allOrders)): ?>
                <!-- Status funnel bar -->
                <?php if (count($statusCounts) > 1): ?>
                <div class="co-funnel-bar" id="customerOrderFunnel">
                    <div class="co-funnel-pill active" data-filter="all" onclick="filterCustomerOrders('all',this)">
                        <span class="count"><?= $totalOrdersCount ?></span> Все
                    </div>
                    <?php foreach ($statusCounts as $st => $cnt):
                        $dotColor = $funnelDotColors[$st] ?? '#6b7280';
                        $stLabel = $allStatuses[$st] ?? ucfirst($st);
                    ?>
                    <div class="co-funnel-pill" data-filter="<?= Html::encode($st) ?>" onclick="filterCustomerOrders('<?= Html::encode($st) ?>',this)">
                        <span class="dot" style="background:<?= $dotColor ?>"></span>
                        <span class="count"><?= $cnt ?></span>
                        <?= Html::encode($stLabel) ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- Full orders table -->
                <div style="overflow-x:auto;max-height:600px;overflow-y:auto">
                    <table class="co-orders-table" id="customerOrdersTable">
                        <thead>
                            <tr>
                                <th style="text-align:left">№ / Дата</th>
                                <th style="text-align:left">Товар</th>
                                <th style="text-align:center">Статус</th>
                                <th style="text-align:right">Сумма</th>
                                <th>Оплата</th>
                                <th>Доставка</th>
                                <th style="white-space:nowrap">Трек Китай</th>
                                <th style="white-space:nowrap">Трек ДП</th>
                                <th style="white-space:nowrap">Трек РБ</th>
                                <th>Город</th>
                                <th>Источник</th>
                                <th>Комментарий</th>
                                <th style="width:40px">&ndash;</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allOrders as $order):
                                $sp = $statusPillMap[$order->status] ?? ['bg' => '#f3f4f6', 'color' => '#6b7280'];
                                $statusLabel = $allStatuses[$order->status] ?? (method_exists($order, 'getStatusLabel') ? $order->getStatusLabel() : $order->status);
                                $firstItem = method_exists($order, 'getOrderItems') ? $order->orderItems[0] ?? null : null;
                                $daysSince = (int)floor((time() - $order->created_at) / 86400);
                            ?>
                            <tr data-status="<?= Html::encode($order->status) ?>" style="cursor:pointer"
                                onclick="if(!event.target.closest('a'))window.location='<?= Url::to(['/admin/order/view', 'id' => $order->id]) ?>'">
                                <td style="white-space:nowrap;padding:7px 10px">
                                    <a href="<?= Url::to(['/admin/order/view', 'id' => $order->id]) ?>"
                                       style="font-weight:700;color:var(--admin-text-primary,#111);text-decoration:none">
                                        <?= Html::encode($order->order_number ?: '#'.$order->id) ?>
                                    </a>
                                    <div style="font-size:.7rem;color:var(--admin-text-secondary,#9ca3af);margin-top:1px">
                                        <?= date('d.m.Y', $order->created_at) ?>
                                        <?php if ($daysSince > 0): ?><span style="opacity:.7"> &middot; <?= $daysSince ?>д</span><?php endif; ?>
                                    </div>
                                </td>
                                <td style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                                    <?= $firstItem ? Html::encode($firstItem->product_name) : '<span style="color:var(--admin-text-secondary,#9ca3af)">—</span>' ?>
                                </td>
                                <td style="text-align:center;padding:7px 8px">
                                    <span class="co-status-pill" style="background:<?= $sp['bg'] ?>;color:<?= $sp['color'] ?>"><?= Html::encode($statusLabel) ?></span>
                                </td>
                                <td style="font-weight:700;white-space:nowrap;text-align:right">
                                    <?= number_format($order->total_amount, 2) ?> <span style="font-size:.7rem;color:var(--admin-text-secondary,#9ca3af);font-weight:400">Br</span>
                                </td>
                                <td style="white-space:nowrap;color:var(--admin-text-secondary,#6b7280);font-size:.75rem">
                                    <?= Html::encode($order->payment_method ?: '—') ?>
                                </td>
                                <td style="white-space:nowrap;color:var(--admin-text-secondary,#6b7280);font-size:.75rem">
                                    <?= Html::encode($order->delivery_method ?? $order->shipping_method ?? '—') ?>
                                </td>
                                <td>
                                    <?php if (!empty($order->china_track_number)): ?>
                                    <span class="co-track-badge" title="<?= Html::encode($order->china_track_number) ?>"><?= Html::encode($order->china_track_number) ?></span>
                                    <?php else: ?><span style="color:var(--admin-text-secondary,#9ca3af)">—</span><?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($order->dp_track_number)): ?>
                                    <span class="co-track-badge" title="<?= Html::encode($order->dp_track_number) ?>"><?= Html::encode($order->dp_track_number) ?></span>
                                    <?php else: ?><span style="color:var(--admin-text-secondary,#9ca3af)">—</span><?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($order->local_track_number)): ?>
                                    <span class="co-track-badge" title="<?= Html::encode($order->local_track_number) ?>"><?= Html::encode($order->local_track_number) ?></span>
                                    <?php else: ?><span style="color:var(--admin-text-secondary,#9ca3af)">—</span><?php endif; ?>
                                </td>
                                <td style="white-space:nowrap;font-size:.75rem">
                                    <?= Html::encode($order->city ?: '—') ?>
                                </td>
                                <td style="font-size:.75rem;white-space:nowrap;color:var(--admin-text-secondary,#6b7280)">
                                    <?= Html::encode($order->source ?: '—') ?>
                                </td>
                                <td style="max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:var(--admin-text-secondary,#6b7280);font-size:.75rem"
                                    title="<?= Html::encode($order->comment ?? '') ?>">
                                    <?= Html::encode($order->comment ?: '—') ?>
                                </td>
                                <td style="padding:4px 6px">
                                    <a href="<?= Url::to(['/admin/order/view', 'id' => $order->id]) ?>"
                                       class="admin-btn admin-btn-secondary"
                                       style="padding:.2rem .45rem;font-size:.875rem;">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                    <div class="crm-empty">
                        <i class="bi bi-bag-x"></i>
                        Заказов не найдено
                    </div>
                <?php endif ?>
            </div>
            <script>
            function filterCustomerOrders(status, el) {
                document.querySelectorAll('#customerOrderFunnel .co-funnel-pill').forEach(p => p.classList.remove('active'));
                el.classList.add('active');
                document.querySelectorAll('#customerOrdersTable tbody tr').forEach(row => {
                    row.style.display = (status === 'all' || row.dataset.status === status) ? '' : 'none';
                });
            }
            </script>

        </div><!-- /crm-main -->

        <!-- ═══ SIDEBAR ═══ -->
        <div class="crm-sidebar">

            <!-- Loyalty -->
            <div class="crm-card">
                <div class="crm-card-header">
                    <div class="crm-card-title"><i class="bi bi-star-fill" style="color:#f59e0b"></i> Программа лояльности</div>
                </div>
                <div class="crm-card-body">
                    <div class="crm-loyalty-header">
                        <div class="crm-loyalty-points"><?= number_format($points) ?> <small>бонусов</small></div>
                        <span class="crm-level-badge" style="background:<?= $levelColor ?>;color:<?= $levelText ?>"><?= $level ?></span>
                    </div>
                    <div class="crm-progress">
                        <div class="crm-progress-bar" style="width:<?= $progress ?>%"></div>
                    </div>
                    <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:4px">
                        <button class="admin-btn admin-btn-primary admin-btn-sm" onclick="togglePointsForm('add')">
                            <i class="bi bi-plus-circle"></i> Начислить
                        </button>
                        <button class="admin-btn admin-btn-secondary admin-btn-sm" onclick="togglePointsForm('deduct')">
                            <i class="bi bi-dash-circle"></i> Списать
                        </button>
                    </div>
                    <div id="points-form" class="crm-points-form" style="display:none">
                        <input type="number" id="points-amount" class="form-control" placeholder="Количество баллов" min="1">
                        <input type="text" id="points-comment" class="form-control" placeholder="Комментарий (обязательно)">
                        <div style="display:flex;gap:6px">
                            <button id="points-submit-btn" class="admin-btn admin-btn-primary admin-btn-sm" onclick="submitPoints(<?= $customer->id ?>)">Применить</button>
                            <button class="admin-btn admin-btn-secondary admin-btn-sm" onclick="document.getElementById('points-form').style.display='none'">Отмена</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- System info -->
            <div class="crm-card">
                <div class="crm-card-header">
                    <div class="crm-card-title"><i class="bi bi-info-circle-fill"></i> Информация</div>
                </div>
                <div class="crm-card-body" style="padding:4px 16px 8px">
                    <div class="crm-info-row">
                        <span class="crm-info-row-label">ID покупателя</span>
                        <span class="crm-info-row-value">#<?= $customer->id ?></span>
                    </div>
                    <div class="crm-info-row">
                        <span class="crm-info-row-label">Регистрация</span>
                        <span class="crm-info-row-value"><?= Yii::$app->formatter->asDatetime($customer->created_at, 'medium') ?></span>
                    </div>
                    <div class="crm-info-row">
                        <span class="crm-info-row-label">Последний вход</span>
                        <span class="crm-info-row-value"><?= $customer->last_login_at ? Yii::$app->formatter->asDatetime($customer->last_login_at, 'medium') : '—' ?></span>
                    </div>
                    <div class="crm-info-row">
                        <span class="crm-info-row-label">IP входа</span>
                        <span class="crm-info-row-value"><?= Html::encode($customer->last_login_ip ?: '—') ?></span>
                    </div>
                    <?php if (!empty($customer->ms_external_code)): ?>
                    <div class="crm-info-row">
                        <span class="crm-info-row-label">Код МС</span>
                        <span class="crm-info-row-value" style="font-family:monospace;font-size:11px"><?= Html::encode($customer->ms_external_code) ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- МойСклад -->
            <?php if (!empty($customer->moysklad_id)): ?>
            <div class="crm-card">
                <div class="crm-card-header">
                    <div class="crm-card-title"><i class="bi bi-cloud-check"></i> МойСклад</div>
                </div>
                <div class="crm-card-body" style="padding:12px 16px;display:flex;flex-direction:column;gap:8px">
                    <a href="https://online.moysklad.ru/app/#counterparty/edit?id=<?= Html::encode($customer->moysklad_id) ?>"
                       target="_blank" class="crm-action-btn" style="justify-content:flex-start">
                        <i class="bi bi-box-arrow-up-right"></i> Открыть контрагента в МС
                    </a>
                    <div style="font-size:11px;color:var(--admin-text-secondary);font-family:monospace;word-break:break-all"><?= Html::encode($customer->moysklad_id) ?></div>
                    <?php if (!empty($customer->moysklad_extra)): ?>
                    <?php $msExtra = is_string($customer->moysklad_extra) ? json_decode($customer->moysklad_extra, true) : $customer->moysklad_extra; ?>
                    <?php if (!empty($msExtra['companyType'])): ?>
                    <div style="font-size:12px"><span style="color:var(--admin-text-secondary)">Тип:</span> <?= Html::encode($msExtra['companyType']) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($msExtra['legalTitle'])): ?>
                    <div style="font-size:12px"><span style="color:var(--admin-text-secondary)">Юр. наименование:</span> <?= Html::encode($msExtra['legalTitle']) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($msExtra['inn'])): ?>
                    <div style="font-size:12px"><span style="color:var(--admin-text-secondary)">ИНН:</span> <code><?= Html::encode($msExtra['inn']) ?></code></div>
                    <?php endif; ?>
                    <?php if (!empty($msExtra['kpp'])): ?>
                    <div style="font-size:12px"><span style="color:var(--admin-text-secondary)">КПП:</span> <code><?= Html::encode($msExtra['kpp']) ?></code></div>
                    <?php endif; ?>
                    <?php if (!empty($msExtra['ogrn'])): ?>
                    <div style="font-size:12px"><span style="color:var(--admin-text-secondary)">ОГРН:</span> <code><?= Html::encode($msExtra['ogrn']) ?></code></div>
                    <?php endif; ?>
                    <?php if (!empty($msExtra['okpo'])): ?>
                    <div style="font-size:12px"><span style="color:var(--admin-text-secondary)">ОКПО:</span> <code><?= Html::encode($msExtra['okpo']) ?></code></div>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Actions -->
            <div class="crm-card">
                <div class="crm-card-header">
                    <div class="crm-card-title"><i class="bi bi-lightning-fill"></i> Действия</div>
                </div>
                <div class="crm-card-body">
                    <div class="crm-actions">
                        <button type="button" class="crm-action-btn" onclick="linkOrders(<?= $customer->id ?>)">
                            <i class="bi bi-link-45deg"></i> Связать заказы
                        </button>
                        <button type="button" class="crm-action-btn warning" onclick="resetPassword(<?= $customer->id ?>)">
                            <i class="bi bi-key-fill"></i> Сбросить пароль
                        </button>
                        <button type="button" class="crm-action-btn" onclick="toggleStatus(<?= $customer->id ?>)">
                            <i class="bi bi-<?= $customer->status == 10 ? 'lock' : 'unlock' ?>-fill"></i>
                            <?= $customer->status == 10 ? 'Заблокировать' : 'Разблокировать' ?>
                        </button>
                        <a href="<?= Url::to(['customer/delete', 'id' => $customer->id]) ?>" class="crm-action-btn danger" onclick="return confirm('Удалить покупателя?')">
                            <i class="bi bi-trash3-fill"></i> Удалить покупателя
                        </a>
                    </div>
                </div>
            </div>

        </div><!-- /crm-sidebar -->
    </div><!-- /crm-body -->
</div><!-- /crm-wrap -->

<!-- ── Bonus History Modal ─────────────────────────── -->
<div id="bonusHistoryModal" class="crm-modal-overlay" style="display:none">
    <div class="crm-modal">
        <div class="crm-modal-head">
            <h3><i class="bi bi-clock-history"></i> История бонусов — <?= Html::encode($customer->getFullName()) ?></h3>
            <button onclick="closeBonusHistory()" class="crm-modal-close">&times;</button>
        </div>
        <div class="crm-modal-body">
            <?php if (empty($history)): ?>
                <div class="crm-empty" style="padding:40px">
                    <i class="bi bi-inbox"></i>
                    История операций пуста
                </div>
            <?php else: ?>
            <table class="crm-bonus-table">
                <thead>
                    <tr>
                        <th>Дата</th>
                        <th style="text-align:center">Баллы</th>
                        <th>Комментарий</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($history as $h):
                        $pts = (int)($h->points ?? $h['points'] ?? 0);
                        $createdAt = $h->created_at ?? $h['created_at'] ?? 0;
                        $desc = Html::encode($h->description ?? $h['description'] ?? '—');
                    ?>
                    <tr>
                        <td style="white-space:nowrap;font-size:12px;color:var(--admin-text-secondary)"><?= Yii::$app->formatter->asDatetime($createdAt, 'short') ?></td>
                        <td style="text-align:center"><span class="bonus-pts <?= $pts >= 0 ? 'pos' : 'neg' ?>"><?= $pts >= 0 ? '+' : '' ?><?= $pts ?></span></td>
                        <td><?= $desc ?></td>
                    </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
            <?php endif ?>
        </div>
        <div class="crm-modal-foot">
            <button onclick="closeBonusHistory()" class="admin-btn admin-btn-secondary">Закрыть</button>
        </div>
    </div>
</div>

<script>
const _csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

function openBonusHistory() {
    document.getElementById('bonusHistoryModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}
function closeBonusHistory() {
    document.getElementById('bonusHistoryModal').style.display = 'none';
    document.body.style.overflow = '';
}
document.getElementById('bonusHistoryModal').addEventListener('click', function(e) {
    if (e.target === this) closeBonusHistory();
});

function togglePointsForm(type) {
    const form = document.getElementById('points-form');
    const btn = document.getElementById('points-submit-btn');
    form.style.display = form.style.display === 'none' ? 'block' : 'none';
    if (btn) btn.dataset.type = type;
}

function submitPoints(customerId) {
    const amount  = parseInt(document.getElementById('points-amount').value);
    const comment = document.getElementById('points-comment').value.trim();
    const type    = document.getElementById('points-submit-btn').dataset.type || 'add';
    if (!amount || amount < 1) { alert('Укажите количество баллов'); return; }
    if (!comment) { alert('Укажите комментарий'); return; }
    const points = type === 'deduct' ? -amount : amount;
    fetch('<?= Url::to(['customer/adjust-points']) ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-Token': _csrf},
        body: JSON.stringify({customer_id: customerId, points, comment})
    })
    .then(r => r.json())
    .then(d => { if (d.success) location.reload(); else alert(d.message || 'Ошибка'); })
    .catch(() => alert('Ошибка сети'));
}

function addTag(customerId, tag) {
    tag = typeof tag === 'string' ? tag.trim() : document.getElementById('custom-tag-input').value.trim();
    if (!tag) return;
    fetch('<?= Url::to(['customer/add-tag']) ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-Token': _csrf},
        body: JSON.stringify({customer_id: customerId, tag})
    })
    .then(r => r.json())
    .then(d => { if (d.success) location.reload(); else alert(d.message || 'Ошибка'); })
    .catch(() => {});
}

function removeTag(customerId, tag) {
    if (!confirm('Удалить тег «' + tag + '»?')) return;
    fetch('<?= Url::to(['customer/remove-tag']) ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-Token': _csrf},
        body: JSON.stringify({customer_id: customerId, tag})
    })
    .then(r => r.json())
    .then(d => { if (d.success) location.reload(); else alert(d.message || 'Ошибка'); })
    .catch(() => {});
}

function addCustomerNote(customerId) {
    const text = document.getElementById('customer-note-text').value.trim();
    if (!text) return;
    fetch('<?= Url::to(['customer/add-note']) ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-Token': _csrf},
        body: JSON.stringify({customer_id: customerId, text})
    })
    .then(r => r.json())
    .then(d => { if (d.success) location.reload(); else alert(d.message || 'Ошибка'); })
    .catch(() => {});
}

function resetPassword(customerId) {
    if (!confirm('Сбросить пароль покупателя? Будет отправлено письмо со ссылкой.')) return;
    fetch('<?= Url::to(['customer/reset-password']) ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-Token': _csrf},
        body: JSON.stringify({customer_id: customerId})
    })
    .then(r => r.json())
    .then(d => alert(d.message || (d.success ? 'Письмо отправлено' : 'Ошибка')))
    .catch(() => alert('Ошибка сети'));
}

function toggleStatus(customerId) {
    if (!confirm('Изменить статус покупателя?')) return;
    fetch('<?= Url::to(['customer/toggle-status']) ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-Token': _csrf},
        body: JSON.stringify({customer_id: customerId})
    })
    .then(r => r.json())
    .then(d => { if (d.success) location.reload(); else alert(d.message || 'Ошибка'); })
    .catch(() => {});
}

function linkOrders(customerId) {
    alert('Привязка заказов запущена. Страница обновится.');
    fetch('<?= Url::to(['customer/link-orders']) ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-Token': _csrf},
        body: JSON.stringify({customer_id: customerId})
    })
    .then(r => r.json())
    .then(() => location.reload())
    .catch(() => location.reload());
}
</script>
