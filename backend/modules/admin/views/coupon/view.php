<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\GridView;

$this->title = 'Купон: ' . $model->code;
$this->params['breadcrumbs'][] = ['label' => 'Купоны', 'url' => ['index']];
$this->params['breadcrumbs'][] = $model->code;

$isActive = $model->is_active ?? true;

$this->params['headerActions'] = [
    Html::a(
        '<i class="bi bi-' . ($isActive ? 'pause-circle' : 'play-circle') . '"></i> ' . ($isActive ? 'Деактивировать' : 'Активировать'),
        ['toggle', 'id' => $model->id],
        [
            'class' => 'admin-btn admin-btn-' . ($isActive ? 'secondary' : 'primary') . ' admin-btn-sm',
            'data-method' => 'post',
            'data-confirm' => 'Вы уверены?',
        ]
    ),
    Html::a('<i class="bi bi-pencil"></i> Редактировать', ['update', 'id' => $model->id], ['class' => 'admin-btn admin-btn-primary admin-btn-sm']),
    Html::a('<i class="bi bi-trash3"></i> Удалить', ['delete', 'id' => $model->id], [
        'class' => 'admin-btn admin-btn-secondary admin-btn-sm',
        'data-confirm' => 'Вы уверены, что хотите удалить этот купон?',
        'data-method' => 'post',
    ]),
];
?>

<style>
/* === Coupon view CRM-style === */
.coupon-wrap { display: flex; flex-direction: column; gap: 16px; }
.coupon-grid { display: grid; grid-template-columns: 1fr 320px; gap: 16px; align-items: start; }
@media (max-width: 860px) { .coupon-grid { grid-template-columns: 1fr; } }

.coupon-hero {
    background: var(--admin-surface, #fff);
    border: 1px solid var(--admin-border, #e5e7eb);
    border-radius: 14px;
    padding: 24px;
    display: flex; align-items: center; gap: 20px;
}
.coupon-icon-wrap {
    width: 64px; height: 64px; border-radius: 14px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    font-size: 28px;
    background: var(--admin-accent-bg, #ecfdf5);
    color: var(--admin-accent, #008060);
}
[data-theme="dark"] .coupon-icon-wrap { background: rgba(16, 185, 129, .15); color: #34d399; }
.coupon-hero-code {
    font-size: 1.5rem; font-weight: 900; letter-spacing: .05em;
    color: var(--admin-text-primary, #111); font-family: monospace;
}
.coupon-hero-sub { font-size: .875rem; color: var(--admin-text-secondary, #6b7280); margin-top: 4px; }

/* Section card */
.c-card {
    background: var(--admin-surface, #fff);
    border: 1px solid var(--admin-border, #e5e7eb);
    border-radius: 12px; overflow: hidden;
}
.c-card-head {
    display: flex; align-items: center; gap: 8px;
    padding: 10px 16px;
    border-bottom: 1px solid var(--admin-border, #e5e7eb);
    background: var(--admin-surface-hover, #f9fafb);
    font-size: .8125rem; font-weight: 700; color: var(--admin-text-primary, #111);
}
.c-card-head i { color: var(--admin-text-secondary, #6b7280); }
.c-card-body { padding: 16px; }

/* Fields grid */
.c-fields { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.c-field { display: flex; flex-direction: column; gap: 3px; }
.c-field-label {
    font-size: .65rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: .05em; color: var(--admin-text-secondary, #9ca3af);
}
.c-field-val { font-size: .875rem; font-weight: 500; color: var(--admin-text-primary, #111); }
.c-field-val a { color: inherit; text-decoration: none; }
.c-field-val a:hover { text-decoration: underline; }

/* Usage progress */
.c-progress-wrap { margin-top: 6px; background: var(--admin-surface-hover, #f3f4f6); border-radius: 6px; height: 6px; overflow: hidden; }
.c-progress-bar { height: 100%; border-radius: 6px; background: var(--admin-accent, #008060); transition: width .4s; }

/* Stat box */
.c-stat {
    text-align: center; padding: 12px 10px;
    background: var(--admin-surface-hover, #f9fafb);
    border-radius: 10px;
}
.c-stat-val { font-size: 1.25rem; font-weight: 800; color: var(--admin-text-primary, #111); }
.c-stat-label { font-size: .65rem; text-transform: uppercase; letter-spacing: .04em; color: var(--admin-text-secondary, #6b7280); margin-top: 2px; }

/* Usage table override */
.coupon-usage-table { width: 100%; font-size: .8125rem; border-collapse: collapse; }
.coupon-usage-table th {
    padding: 6px 10px; font-size: .7rem; font-weight: 600;
    text-transform: uppercase; letter-spacing: .04em;
    color: var(--admin-text-secondary, #6b7280);
    border-bottom: 1px solid var(--admin-border, #e5e7eb);
    text-align: left;
}
.coupon-usage-table td {
    padding: 8px 10px; border-bottom: 1px solid var(--admin-border, #f3f4f6);
    color: var(--admin-text-primary, #111);
}
.coupon-usage-table tr:last-child td { border-bottom: none; }
.coupon-usage-table tr:hover td { background: var(--admin-surface-hover, #f9fafb); }
</style>

<div class="coupon-wrap">

    <!-- Hero banner -->
    <div class="coupon-hero">
        <div class="coupon-icon-wrap"><i class="bi bi-ticket-perforated"></i></div>
        <div style="flex:1;min-width:0">
            <div class="coupon-hero-code"><?= Html::encode($model->code) ?></div>
            <div class="coupon-hero-sub">
                <?= Html::encode($model->getDiscountDescription()) ?>
                &nbsp;·&nbsp;
                <?php if ($isActive): ?>
                <span style="color:var(--admin-success,#16a34a);font-weight:600"><i class="bi bi-check-circle-fill" style="font-size:.75rem"></i> Активен</span>
                <?php else: ?>
                <span style="color:var(--admin-text-secondary,#6b7280);font-weight:600"><i class="bi bi-dash-circle" style="font-size:.75rem"></i> Неактивен</span>
                <?php endif; ?>
            </div>
        </div>
        <?php
        $usagePercent = 0;
        if (!empty($model->max_uses) && $model->max_uses > 0) {
            $usagePercent = min(100, round(($model->current_uses ?? 0) / $model->max_uses * 100));
        }
        ?>
        <div style="text-align:center;padding:0 8px">
            <div style="font-size:2rem;font-weight:900;color:var(--admin-text-primary,#111);line-height:1"><?= $model->current_uses ?? 0 ?></div>
            <div style="font-size:.65rem;text-transform:uppercase;letter-spacing:.04em;color:var(--admin-text-secondary,#6b7280)">Использований</div>
        </div>
    </div>

    <div class="coupon-grid">

        <!-- Left column: details + usage history -->
        <div style="display:flex;flex-direction:column;gap:16px">

            <!-- Coupon details -->
            <div class="c-card">
                <div class="c-card-head"><i class="bi bi-info-circle"></i> Параметры купона</div>
                <div class="c-card-body">
                    <div class="c-fields">
                        <div class="c-field">
                            <div class="c-field-label">Код</div>
                            <div class="c-field-val" style="font-family:monospace;font-size:1rem;font-weight:700"><?= Html::encode($model->code) ?></div>
                        </div>
                        <div class="c-field">
                            <div class="c-field-label">Тип</div>
                            <div class="c-field-val"><?= Html::encode($model->getTypeName()) ?></div>
                        </div>
                        <div class="c-field" style="grid-column:span 2">
                            <div class="c-field-label">Скидка</div>
                            <div class="c-field-val" style="font-size:1.125rem;font-weight:700;color:var(--admin-accent,#008060)"><?= Html::encode($model->getDiscountDescription()) ?></div>
                        </div>
                        <?php if (!empty($model->min_order_amount)): ?>
                        <div class="c-field">
                            <div class="c-field-label">Минимальная сумма</div>
                            <div class="c-field-val"><?= Yii::$app->formatter->asCurrency($model->min_order_amount, 'BYN') ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($model->max_discount)): ?>
                        <div class="c-field">
                            <div class="c-field-label">Максимальная скидка</div>
                            <div class="c-field-val"><?= Yii::$app->formatter->asCurrency($model->max_discount, 'BYN') ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Usage history -->
            <div class="c-card">
                <div class="c-card-head"><i class="bi bi-clock-history"></i> История использований</div>
                <div class="c-card-body" style="padding:0">
                    <?php
                    $usages = $usageProvider->getModels();
                    if (!empty($usages)):
                    ?>
                    <table class="coupon-usage-table">
                        <thead>
                            <tr>
                                <th>Заказ</th>
                                <th>Пользователь</th>
                                <th>Скидка</th>
                                <th>Дата</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usages as $usage): ?>
                            <tr>
                                <td>
                                    <a href="<?= Url::to(['/admin/order/view', 'id' => $usage->order_id]) ?>"
                                       style="font-weight:600;color:var(--admin-accent,#008060);text-decoration:none">
                                        #<?= $usage->order_id ?>
                                    </a>
                                </td>
                                <td><?= $usage->user_id ? 'ID: ' . $usage->user_id : '<span style="color:var(--admin-text-secondary,#6b7280)">Гость</span>' ?></td>
                                <td style="font-weight:600"><?= Yii::$app->formatter->asCurrency($usage->discount_amount, 'BYN') ?></td>
                                <td style="color:var(--admin-text-secondary,#6b7280);font-size:.75rem">
                                    <?= $usage->created_at ? date('d.m.Y H:i', is_numeric($usage->created_at) ? $usage->created_at : strtotime($usage->created_at)) : '—' ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div style="padding:32px;text-align:center;color:var(--admin-text-secondary,#6b7280)">
                        <i class="bi bi-clock-history" style="font-size:2rem;display:block;margin-bottom:8px;opacity:.4"></i>
                        <div style="font-size:.875rem">Купон ещё не использовался</div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

        </div><!-- /left column -->

        <!-- Right sidebar: stats + validity -->
        <div style="display:flex;flex-direction:column;gap:16px">

            <!-- Stats -->
            <div class="c-card">
                <div class="c-card-head"><i class="bi bi-bar-chart-line"></i> Статистика</div>
                <div class="c-card-body" style="display:flex;flex-direction:column;gap:12px">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
                        <div class="c-stat">
                            <div class="c-stat-val"><?= $model->current_uses ?? 0 ?></div>
                            <div class="c-stat-label">Использований</div>
                        </div>
                        <div class="c-stat">
                            <div class="c-stat-val"><?= $model->max_uses ? $model->max_uses : '∞' ?></div>
                            <div class="c-stat-label">Лимит</div>
                        </div>
                    </div>
                    <?php if (!empty($model->max_uses) && $model->max_uses > 0): ?>
                    <div>
                        <div style="display:flex;justify-content:space-between;font-size:.75rem;color:var(--admin-text-secondary,#6b7280);margin-bottom:4px">
                            <span>Использовано</span>
                            <span><?= $usagePercent ?>%</span>
                        </div>
                        <div class="c-progress-wrap">
                            <div class="c-progress-bar" style="width:<?= $usagePercent ?>%"></div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Validity -->
            <div class="c-card">
                <div class="c-card-head"><i class="bi bi-calendar-range"></i> Срок действия</div>
                <div class="c-card-body" style="display:flex;flex-direction:column;gap:10px">
                    <div class="c-field">
                        <div class="c-field-label">Активен с</div>
                        <div class="c-field-val">
                            <?php if (!empty($model->valid_from)): ?>
                            <?= date('d.m.Y', is_numeric($model->valid_from) ? $model->valid_from : strtotime($model->valid_from)) ?>
                            <?php else: ?><span style="color:var(--admin-text-secondary,#6b7280)">—</span><?php endif; ?>
                        </div>
                    </div>
                    <div class="c-field">
                        <div class="c-field-label">Активен до</div>
                        <div class="c-field-val">
                            <?php if (!empty($model->valid_until)): ?>
                            <?php
                            $untilTs = is_numeric($model->valid_until) ? $model->valid_until : strtotime($model->valid_until);
                            $expired = $untilTs < time();
                            ?>
                            <span style="<?= $expired ? 'color:var(--admin-danger,#dc2626)' : 'color:var(--admin-success,#16a34a)' ?>;font-weight:600">
                                <?= date('d.m.Y', $untilTs) ?>
                                <?php if ($expired): ?>
                                <span style="font-size:.7rem;font-weight:700;background:var(--admin-danger-bg,#fee2e2);color:var(--admin-danger,#dc2626);padding:1px 6px;border-radius:4px;margin-left:4px">Истёк</span>
                                <?php endif; ?>
                            </span>
                            <?php else: ?><span style="color:var(--admin-text-secondary,#6b7280)">Без ограничений</span><?php endif; ?>
                        </div>
                    </div>
                    <div class="c-field">
                        <div class="c-field-label">Создан</div>
                        <div class="c-field-val" style="font-size:.8rem;color:var(--admin-text-secondary,#6b7280)">
                            <?php if (!empty($model->created_at)): ?>
                            <?= date('d.m.Y H:i', is_numeric($model->created_at) ? $model->created_at : strtotime($model->created_at)) ?>
                            <?php else: ?>—<?php endif; ?>
                        </div>
                    </div>
                    <div class="c-field">
                        <div class="c-field-label">Обновлён</div>
                        <div class="c-field-val" style="font-size:.8rem;color:var(--admin-text-secondary,#6b7280)">
                            <?php if (!empty($model->updated_at)): ?>
                            <?= date('d.m.Y H:i', is_numeric($model->updated_at) ? $model->updated_at : strtotime($model->updated_at)) ?>
                            <?php else: ?>—<?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status -->
            <div class="c-card">
                <div class="c-card-head"><i class="bi bi-toggle-on"></i> Статус</div>
                <div class="c-card-body" style="display:flex;flex-direction:column;gap:10px">
                    <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 14px;border-radius:10px;background:<?= $isActive ? 'var(--admin-success-bg,#dcfce7)' : 'var(--admin-surface-hover,#f3f4f6)' ?>">
                        <div style="display:flex;align-items:center;gap:8px">
                            <span style="width:10px;height:10px;border-radius:50%;background:<?= $isActive ? 'var(--admin-success,#16a34a)' : 'var(--admin-text-secondary,#9ca3af)' ?>;display:inline-block"></span>
                            <span style="font-weight:700;font-size:.875rem;color:<?= $isActive ? 'var(--admin-success,#16a34a)' : 'var(--admin-text-secondary,#6b7280)' ?>">
                                <?= $isActive ? 'Активен' : 'Неактивен' ?>
                            </span>
                        </div>
                        <?= Html::a(
                            $isActive ? 'Деактивировать' : 'Активировать',
                            ['toggle', 'id' => $model->id],
                            [
                                'class' => 'admin-btn admin-btn-sm admin-btn-secondary',
                                'data-method' => 'post',
                                'data-confirm' => 'Изменить статус купона?',
                                'style' => 'font-size:12px',
                            ]
                        ) ?>
                    </div>
                </div>
            </div>

        </div><!-- /sidebar -->

    </div><!-- /coupon-grid -->

</div><!-- /coupon-wrap -->
