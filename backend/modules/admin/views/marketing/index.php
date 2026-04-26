<?php

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Маркетинг';

$tab = Yii::$app->request->get('tab', 'abandoned');

$this->params['headerActions'] = [
    Html::a('<i class="bi bi-plus-circle"></i> Новая кампания', ['marketing/campaigns'], ['class' => 'admin-btn admin-btn-primary admin-btn-sm', 'style' => 'flex-shrink:0']),
];

// Campaigns demo data (no DB table yet)
$campaigns = [
    ['id' => 1, 'name' => 'Весенняя распродажа 2026', 'type' => 'email', 'status' => 'active', 'recipients' => 15420, 'opened' => 4230, 'clicked' => 1250, 'created_at' => '2026-04-01'],
    ['id' => 2, 'name' => 'Новинки апреля', 'type' => 'sms', 'status' => 'completed', 'recipients' => 8900, 'opened' => 8500, 'clicked' => 2100, 'created_at' => '2026-03-15'],
    ['id' => 3, 'name' => 'Скидки для постоянных клиентов', 'type' => 'promo', 'status' => 'draft', 'recipients' => 0, 'opened' => 0, 'clicked' => 0, 'created_at' => '2026-04-10'],
    ['id' => 4, 'name' => 'Re-активация спящих клиентов', 'type' => 'email', 'status' => 'scheduled', 'recipients' => 5200, 'opened' => 0, 'clicked' => 0, 'created_at' => '2026-04-12'],
];
$typeLabels   = ['email' => ['label' => 'Email', 'icon' => 'bi-envelope', 'color' => '#3b82f6'], 'sms' => ['label' => 'SMS', 'icon' => 'bi-chat-dots', 'color' => '#10b981'], 'promo' => ['label' => 'Промо', 'icon' => 'bi-tag', 'color' => '#f59e0b']];
$statusLabels = ['active' => ['label' => 'Активна', 'class' => 'admin-badge-success'], 'completed' => ['label' => 'Завершена', 'class' => 'admin-badge-secondary'], 'draft' => ['label' => 'Черновик', 'class' => 'admin-badge-warning'], 'scheduled' => ['label' => 'Запланирована', 'class' => 'admin-badge-info']];
?>

<style>
/* Z66 — header layout fix */
.admin-page-header { flex-wrap: nowrap !important; align-items: center !important; }
</style>

<!-- KPI Stats -->
<div class="admin-stats" style="margin-bottom:1.5rem">
    <div class="admin-stat-card">
        <div class="admin-stat-icon abandoned"><i class="bi bi-cart-x"></i></div>
        <div class="admin-stat-content">
            <div class="admin-stat-value"><?= $abandonedStats['total_abandoned'] ?></div>
            <div class="admin-stat-label">Брошенных корзин</div>
        </div>
    </div>
    <?php if ((int)$abandonedStats['total_abandoned'] > 0): ?>
    <div class="admin-stat-card">
        <div class="admin-stat-icon success"><i class="bi bi-check-circle-fill"></i></div>
        <div class="admin-stat-content">
            <div class="admin-stat-value"><?= number_format($abandonedStats['recovery_rate'], 1) ?>%</div>
            <div class="admin-stat-label">Восстановление корзин</div>
        </div>
    </div>
    <?php else: ?>
    <div class="admin-stat-card">
        <div class="admin-stat-icon success"><i class="bi bi-check-circle-fill"></i></div>
        <div class="admin-stat-content">
            <div class="admin-stat-value" style="font-size:1rem;color:var(--admin-text-secondary)">Нет данных</div>
            <div class="admin-stat-label">Восстановление корзин</div>
        </div>
    </div>
    <?php endif; ?>
    <div class="admin-stat-card">
        <div class="admin-stat-icon upsell"><i class="bi bi-arrow-up-circle"></i></div>
        <div class="admin-stat-content">
            <div class="admin-stat-value"><?= number_format($recommendationStats['conversion_rate'], 1) ?>%</div>
            <div class="admin-stat-label">Конверсия рекомендаций</div>
        </div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-icon primary"><i class="bi bi-envelope-fill"></i></div>
        <div class="admin-stat-content">
            <div class="admin-stat-value"><?= count($campaigns) ?></div>
            <div class="admin-stat-label">Активных кампаний</div>
        </div>
    </div>
</div>

<!-- Tab navigation -->
<div class="admin-card" style="margin-bottom:1.5rem">
    <div class="admin-card-body" style="padding:0.75rem 1.25rem">
        <div style="display:flex;gap:0.5rem">
            <a href="<?= Url::to(['/admin/marketing', 'tab' => 'abandoned']) ?>"
               class="admin-btn admin-btn-sm <?= $tab === 'abandoned' ? 'admin-btn-primary' : 'admin-btn-secondary' ?>">
                <i class="bi bi-cart-x"></i> Брошенные корзины
                <?php if (!empty($abandonedStats['total_abandoned'])): ?>
                <span class="admin-badge <?= $tab === 'abandoned' ? 'admin-badge-light' : 'admin-badge-secondary' ?>" style="margin-left:4px"><?= (int)$abandonedStats['total_abandoned'] ?></span>
                <?php endif; ?>
            </a>
            <a href="<?= Url::to(['/admin/marketing', 'tab' => 'campaigns']) ?>"
               class="admin-btn admin-btn-sm <?= $tab === 'campaigns' ? 'admin-btn-primary' : 'admin-btn-secondary' ?>">
                <i class="bi bi-megaphone"></i> Кампании
                <span class="admin-badge <?= $tab === 'campaigns' ? 'admin-badge-light' : 'admin-badge-secondary' ?>" style="margin-left:4px"><?= count($campaigns) ?></span>
            </a>
            <a href="<?= Url::to(['/admin/marketing/recommendations']) ?>"
               class="admin-btn admin-btn-sm admin-btn-secondary">
                <i class="bi bi-stars"></i> Рекомендации
            </a>
        </div>
    </div>
</div>

<?php if ($tab === 'abandoned'): ?>

<!-- ═══ БРОШЕННЫЕ КОРЗИНЫ ═══ -->
<div class="admin-card">
    <div class="admin-card-header">
        <h2 class="admin-card-title"><i class="bi bi-cart-x"></i> Брошенные корзины</h2>
        <button type="button" class="admin-btn admin-btn-primary admin-btn-sm"
                onclick="sendBulkReminders(this)"
                data-bulk-url="<?= Url::to(['marketing/send-bulk-reminders']) ?>"
                <?= empty($abandonedCarts) ? 'disabled' : '' ?>>
            <i class="bi bi-send"></i> Напомнить всем
        </button>
    </div>
    <div class="admin-card-body">
        <?php if (!empty($abandonedCarts)): ?>
        <div class="abandoned-cart-list">
            <?php foreach ($abandonedCarts as $cart): ?>
            <div class="abandoned-cart-item">
                <div>
                    <div class="cart-customer">
                        <?= $cart->customer ? Html::encode($cart->customer->getFullName()) : 'Гость' ?>
                    </div>
                    <div class="cart-meta">
                        <?= (int)$cart->quantity ?> товаров ·
                        <?= Yii::$app->formatter->asRelativeTime($cart->updated_at) ?>
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:1rem">
                    <div class="cart-value"><?= Yii::$app->formatter->asCurrency($cart->price * $cart->quantity, 'BYN') ?></div>
                    <button type="button" class="admin-btn admin-btn-primary admin-btn-sm"
                            onclick="sendReminder(<?= $cart->id ?>, this)"
                            data-reminder-url="<?= Url::to(['marketing/send-reminder']) ?>">
                        <i class="bi bi-send"></i> Напомнить
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php if (count($abandonedCarts) >= 10): ?>
        <div style="margin-top:1.5rem;text-align:center">
            <a href="<?= Url::to(['marketing/abandoned-carts']) ?>" class="admin-btn admin-btn-secondary">Показать все</a>
        </div>
        <?php endif; ?>
        <?php else: ?>
        <div style="text-align:center;padding:2rem;color:var(--admin-text-secondary)">
            <i class="bi bi-cart-check" style="font-size:3rem;display:block;margin-bottom:1rem"></i>
            Нет брошенных корзин
        </div>
        <?php endif; ?>
    </div>
</div>

<?php else: ?>

<!-- ═══ КАМПАНИИ ═══ -->
<div class="admin-card">
    <div class="admin-card-header">
        <h2 class="admin-card-title"><i class="bi bi-megaphone"></i> Маркетинговые кампании</h2>
        <div class="admin-card-actions">
            <button class="admin-btn admin-btn-secondary admin-btn-sm"><i class="bi bi-funnel"></i> Фильтр</button>
            <button class="admin-btn admin-btn-secondary admin-btn-sm"><i class="bi bi-download"></i> Экспорт</button>
            <a href="<?= Url::to(['marketing/campaigns']) ?>" class="admin-btn admin-btn-primary admin-btn-sm"><i class="bi bi-plus-circle"></i> Новая</a>
        </div>
    </div>
    <div style="overflow-x:auto">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width:40px"></th>
                    <th>Название</th>
                    <th>Тип</th>
                    <th>Статус</th>
                    <th style="text-align:center">Получатели</th>
                    <th style="text-align:center">Открытия</th>
                    <th style="text-align:center">Клики</th>
                    <th style="text-align:center">CTR</th>
                    <th>Дата</th>
                    <th style="text-align:right">Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($campaigns as $campaign): ?>
                <?php
                    $type   = $typeLabels[$campaign['type']] ?? $typeLabels['email'];
                    $status = $statusLabels[$campaign['status']] ?? $statusLabels['draft'];
                    $ctr    = $campaign['recipients'] > 0 ? round(($campaign['clicked'] / $campaign['recipients']) * 100, 1) : 0;
                ?>
                <tr>
                    <td><i class="bi <?= $type['icon'] ?>" style="color:<?= $type['color'] ?>;font-size:18px"></i></td>
                    <td><strong><?= Html::encode($campaign['name']) ?></strong></td>
                    <td>
                        <span class="admin-badge" style="background:<?= $type['color'] ?>20;color:<?= $type['color'] ?>"><?= $type['label'] ?></span>
                    </td>
                    <td><span class="admin-badge <?= $status['class'] ?>"><?= $status['label'] ?></span></td>
                    <td style="text-align:center"><?= number_format($campaign['recipients'], 0, '.', ' ') ?></td>
                    <td style="text-align:center"><?= $campaign['opened'] > 0 ? number_format($campaign['opened'], 0, '.', ' ') : '—' ?></td>
                    <td style="text-align:center"><?= $campaign['clicked'] > 0 ? number_format($campaign['clicked'], 0, '.', ' ') : '—' ?></td>
                    <td style="text-align:center"><?= $ctr > 0 ? $ctr . '%' : '—' ?></td>
                    <td style="font-size:13px;color:var(--admin-text-secondary)"><?= date('d.m.Y', strtotime($campaign['created_at'])) ?></td>
                    <td style="text-align:right">
                        <div class="admin-actions">
                            <button class="admin-btn admin-btn-sm admin-btn-secondary" title="Редактировать"><i class="bi bi-pencil"></i></button>
                            <button class="admin-btn admin-btn-sm admin-btn-secondary" title="Статистика"><i class="bi bi-graph-up"></i></button>
                            <?php if ($campaign['status'] === 'draft'): ?>
                            <button class="admin-btn admin-btn-sm admin-btn-primary" title="Запустить"><i class="bi bi-play-fill"></i></button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Campaign type cards -->
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1.5rem;margin-top:1.5rem">
    <div class="admin-card">
        <div class="admin-card-header">
            <h3 class="admin-card-title"><i class="bi bi-envelope" style="color:#3b82f6"></i> Email-рассылки</h3>
        </div>
        <div class="admin-card-body">
            <p style="color:var(--admin-text-secondary);margin-bottom:1rem">Персонализированные письма для клиентов с шаблонами и A/B тестированием.</p>
            <a href="#" class="admin-btn admin-btn-secondary admin-btn-sm"><i class="bi bi-plus"></i> Создать Email</a>
        </div>
    </div>
    <div class="admin-card">
        <div class="admin-card-header">
            <h3 class="admin-card-title"><i class="bi bi-chat-dots" style="color:#10b981"></i> SMS-кампании</h3>
        </div>
        <div class="admin-card-body">
            <p style="color:var(--admin-text-secondary);margin-bottom:1rem">Мгновенные SMS-уведомления с отслеживанием доставки и короткими ссылками.</p>
            <a href="#" class="admin-btn admin-btn-secondary admin-btn-sm"><i class="bi bi-plus"></i> Создать SMS</a>
        </div>
    </div>
    <div class="admin-card">
        <div class="admin-card-header">
            <h3 class="admin-card-title"><i class="bi bi-tag" style="color:#f59e0b"></i> Промо-акции</h3>
        </div>
        <div class="admin-card-body">
            <p style="color:var(--admin-text-secondary);margin-bottom:1rem">Промо-коды, скидки и лимитированные предложения для постоянных клиентов.</p>
            <a href="<?= Url::to(['/admin/coupon']) ?>" class="admin-btn admin-btn-secondary admin-btn-sm"><i class="bi bi-ticket"></i> Купоны</a>
        </div>
    </div>
</div>

<?php endif; ?>
