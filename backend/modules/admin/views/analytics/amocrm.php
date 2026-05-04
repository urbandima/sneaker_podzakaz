<?php

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'AmoCRM — Воронка продаж';
?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;">
    <h2 style="margin:0;font-size:1.3rem;font-weight:700;">
        <i class="bi bi-funnel"></i> AmoCRM — Воронка продаж
    </h2>
    <div style="display:flex;gap:0.5rem;">
        <a href="<?= Url::to(['/admin/analytics/export-deals']) ?>"
           class="admin-btn admin-btn-sm admin-btn-ghost">
            <i class="bi bi-download"></i> Экспорт CSV
        </a>
        <a href="<?= Url::to(['/admin/analytics/index']) ?>"
           class="admin-btn admin-btn-sm admin-btn-ghost">← Аналитика</a>
    </div>
</div>

<?php if ($tokenStatus === 'error'): ?>
<div style="background:#fef2f2;border:1px solid #fecaca;border-radius:0.75rem;padding:1rem 1.25rem;margin-bottom:1.5rem;display:flex;align-items:flex-start;gap:0.75rem;">
    <i class="bi bi-exclamation-triangle-fill" style="color:#ef4444;font-size:1.2rem;flex-shrink:0;margin-top:0.1rem;"></i>
    <div>
        <strong style="color:#b91c1c;">AmoCRM недоступен</strong>
        <p style="margin:0.25rem 0 0;color:#7f1d1d;font-size:0.9rem;">
            Токен <code>AMOCRM_LONG_TOKEN</code> вернул ошибку (401 или сетевая ошибка).
            AmoCRM-части дашборда заблокированы до обновления токена.
            Обновите токен в <code>.env</code> или в настройках интеграции.
        </p>
    </div>
</div>
<?php else: ?>

<!-- Сводные KPI воронки -->
<div class="admin-stats" style="margin-bottom:1.5rem;">
    <div class="admin-stat-card">
        <p class="admin-stat-number"><?= $totalLeads ?></p>
        <p class="admin-stat-label">Всего лидов</p>
        <span class="admin-badge admin-badge-info">Pipeline <?= Html::encode($pipelineId) ?></span>
    </div>
    <div class="admin-stat-card" style="border-left-color:var(--admin-success);">
        <p class="admin-stat-number"><?= $wonLeads ?></p>
        <p class="admin-stat-label">Выиграно</p>
        <?php if ($totalLeads > 0): ?>
        <span class="admin-badge admin-badge-success"><?= round($wonLeads / $totalLeads * 100, 1) ?>%</span>
        <?php endif; ?>
    </div>
    <div class="admin-stat-card" style="border-left-color:var(--admin-danger, #ef4444);">
        <p class="admin-stat-number"><?= $lostLeads ?></p>
        <p class="admin-stat-label">Проиграно</p>
        <?php if ($totalLeads > 0): ?>
        <span class="admin-badge admin-badge-danger"><?= round($lostLeads / $totalLeads * 100, 1) ?>%</span>
        <?php endif; ?>
    </div>
    <div class="admin-stat-card" style="border-left-color:var(--admin-warning);">
        <p class="admin-stat-number"><?= $totalLeads - $wonLeads - $lostLeads ?></p>
        <p class="admin-stat-label">В работе</p>
        <span class="admin-badge admin-badge-warning">Активные</span>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;">

<!-- Конверсия по этапам -->
<div class="admin-card">
    <div class="admin-card-header">
        <h3 class="admin-card-title"><i class="bi bi-bar-chart-steps"></i> Этапы воронки</h3>
    </div>
    <div class="admin-card-body">
        <?php if (empty($stageMetrics)): ?>
            <p style="color:var(--admin-text-secondary);text-align:center;padding:2rem;">Нет данных</p>
        <?php else: ?>
            <?php
            $maxCount = max(array_column($stageMetrics, 'count')) ?: 1;
            $colors   = ['#3b82f6','#8b5cf6','#10b981','#f59e0b','#ef4444','#06b6d4','#ec4899'];
            $ci       = 0;
            ?>
            <?php foreach ($stageMetrics as $stage): ?>
            <?php if ($stage['count'] === 0) continue; ?>
            <div style="margin-bottom:0.75rem;">
                <div style="display:flex;justify-content:space-between;margin-bottom:0.25rem;">
                    <span style="font-size:0.85rem;font-weight:500;"><?= Html::encode($stage['name']) ?></span>
                    <span style="font-size:0.85rem;font-weight:700;color:<?= $colors[$ci % count($colors)] ?>;">
                        <?= $stage['count'] ?>
                        <?php if ($totalLeads > 0): ?>
                        <small style="font-weight:400;color:var(--admin-text-secondary);">
                            (<?= round($stage['count'] / $totalLeads * 100, 1) ?>%)
                        </small>
                        <?php endif; ?>
                    </span>
                </div>
                <div style="background:var(--admin-border,#e2e8f0);border-radius:4px;height:8px;">
                    <div style="width:<?= round($stage['count'] / $maxCount * 100) ?>%;background:<?= $colors[$ci % count($colors)] ?>;height:8px;border-radius:4px;transition:width 0.3s;"></div>
                </div>
            </div>
            <?php $ci++; endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Топ-причины проигрыша -->
<div class="admin-card">
    <div class="admin-card-header">
        <h3 class="admin-card-title"><i class="bi bi-x-circle"></i> Топ причины проигрыша</h3>
    </div>
    <div class="admin-card-body">
        <?php if (empty($lossReasons)): ?>
            <p style="color:var(--admin-text-secondary);text-align:center;padding:2rem;">
                Нет данных (причины хранятся в кастомных полях лида)
            </p>
        <?php else: ?>
            <?php $maxLoss = max(array_values($lossReasons)) ?: 1; ?>
            <?php foreach ($lossReasons as $reason => $cnt): ?>
            <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:0.6rem;">
                <div style="flex:1;min-width:0;">
                    <div style="font-size:0.85rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                        <?= Html::encode($reason) ?>
                    </div>
                    <div style="background:var(--admin-border,#e2e8f0);border-radius:4px;height:6px;margin-top:3px;">
                        <div style="width:<?= round($cnt / $maxLoss * 100) ?>%;background:#ef4444;height:6px;border-radius:4px;"></div>
                    </div>
                </div>
                <span style="font-weight:700;font-size:0.9rem;color:#ef4444;min-width:2rem;text-align:right;"><?= $cnt ?></span>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

</div><!-- /grid -->

<?php endif; // tokenStatus ?>
