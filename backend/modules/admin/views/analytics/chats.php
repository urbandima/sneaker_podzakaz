<?php
/**
 * Phase 0 — Chat Analytics (CMP-159)
 * Aggregates AmoCRM chat pipelines 7426646 (сайт) and 8054662 (Яндекс карт).
 */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Аналитика чатов AmoCRM';

$tokenStatus      = $tokenStatus      ?? 'ok';
$totalChats       = $totalChats       ?? 0;
$wonLeads         = $wonLeads         ?? 0;
$overallConversion = $overallConversion ?? 0;
$overallAvgRes    = $overallAvgRes    ?? '—';
$pipelineMetrics  = $pipelineMetrics  ?? [];
$tagCounts        = $tagCounts        ?? [];
$categoryCounts   = $categoryCounts   ?? [];
$categoryLabels   = $categoryLabels   ?? [];
?>

<!-- Back link -->
<div style="margin-bottom:1rem;">
    <a href="<?= Url::to(['/admin/analytics']) ?>" style="font-size:0.85rem;color:var(--admin-text-secondary,#64748b);text-decoration:none;">
        <i class="bi bi-arrow-left"></i> Аналитика
    </a>
</div>

<?php if ($tokenStatus === 'error'): ?>
<div class="admin-alert admin-alert-warning" style="margin-bottom:1.5rem;padding:1rem 1.25rem;border-radius:0.5rem;background:#fef3c7;border:1px solid #f59e0b;color:#92400e;">
    <i class="bi bi-exclamation-triangle"></i>
    <strong>AmoCRM недоступен.</strong> Проверьте <code>AMOCRM_LONG_TOKEN</code> в .env.
    Данные не загружены.
</div>
<?php endif; ?>

<!-- Summary KPI cards -->
<div class="admin-stats" style="margin-bottom:1.5rem;">
    <div class="admin-stat-card">
        <p class="admin-stat-number"><?= $totalChats ?></p>
        <p class="admin-stat-label">Всего чатов</p>
        <span class="admin-badge admin-badge-info">Оба пайплайна</span>
    </div>
    <div class="admin-stat-card" style="border-left-color:#10b981;">
        <p class="admin-stat-number"><?= $wonLeads ?></p>
        <p class="admin-stat-label">Успешно закрыто</p>
        <span class="admin-badge admin-badge-success">Статус «Успех»</span>
    </div>
    <div class="admin-stat-card" style="border-left-color:#3b82f6;">
        <p class="admin-stat-number"><?= $overallConversion ?>%</p>
        <p class="admin-stat-label">Конверсия</p>
        <span class="admin-badge admin-badge-primary">Цель &gt;15%</span>
    </div>
    <div class="admin-stat-card" style="border-left-color:#f59e0b;">
        <p class="admin-stat-number"><?= Html::encode($overallAvgRes) ?></p>
        <p class="admin-stat-label">Среднее время закрытия</p>
        <span class="admin-badge admin-badge-warning">Цель &lt;30с ответ</span>
    </div>
</div>

<?php if (empty($pipelineMetrics) && $tokenStatus !== 'error'): ?>
<div class="admin-card" style="padding:2rem;text-align:center;color:var(--admin-text-secondary);">
    <i class="bi bi-chat-dots" style="font-size:2.5rem;opacity:0.3;"></i>
    <p style="margin-top:0.75rem;">Лиды в чат-пайплайнах не найдены.</p>
</div>
<?php endif; ?>

<!-- Per-pipeline breakdown -->
<?php foreach ($pipelineMetrics as $pid => $pm): ?>
<div class="admin-card" style="margin-bottom:1.5rem;">
    <h2 class="admin-card-title">
        <i class="bi bi-chat-square-dots"></i>
        <?= Html::encode($pm['name']) ?>
        <span style="font-size:0.8rem;font-weight:400;color:var(--admin-text-secondary);">ID: <?= $pid ?></span>
    </h2>

    <!-- Mini KPIs -->
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:1rem;margin-bottom:1.25rem;">
        <div style="background:var(--admin-bg-secondary,#f8fafc);border-radius:0.5rem;padding:0.75rem;text-align:center;">
            <div style="font-size:1.5rem;font-weight:700;"><?= $pm['total'] ?></div>
            <div style="font-size:0.75rem;color:var(--admin-text-secondary);">Всего</div>
        </div>
        <div style="background:#f0fdf4;border-radius:0.5rem;padding:0.75rem;text-align:center;">
            <div style="font-size:1.5rem;font-weight:700;color:#15803d;"><?= $pm['won'] ?></div>
            <div style="font-size:0.75rem;color:var(--admin-text-secondary);">Успешно</div>
        </div>
        <div style="background:#fef2f2;border-radius:0.5rem;padding:0.75rem;text-align:center;">
            <div style="font-size:1.5rem;font-weight:700;color:#b91c1c;"><?= $pm['lost'] ?></div>
            <div style="font-size:0.75rem;color:var(--admin-text-secondary);">Отказ</div>
        </div>
        <div style="background:#eff6ff;border-radius:0.5rem;padding:0.75rem;text-align:center;">
            <div style="font-size:1.5rem;font-weight:700;color:#1d4ed8;"><?= $pm['open'] ?></div>
            <div style="font-size:0.75rem;color:var(--admin-text-secondary);">Открытых</div>
        </div>
        <div style="background:#fefce8;border-radius:0.5rem;padding:0.75rem;text-align:center;">
            <div style="font-size:1.5rem;font-weight:700;color:#a16207;"><?= $pm['conversion_pct'] ?>%</div>
            <div style="font-size:0.75rem;color:var(--admin-text-secondary);">Конверсия</div>
        </div>
        <div style="background:var(--admin-bg-secondary,#f8fafc);border-radius:0.5rem;padding:0.75rem;text-align:center;">
            <div style="font-size:1.1rem;font-weight:700;"><?= Html::encode($pm['avgResHuman']) ?></div>
            <div style="font-size:0.75rem;color:var(--admin-text-secondary);">Ср. закрытие</div>
        </div>
    </div>

    <!-- Status distribution bar chart -->
    <?php if (!empty($pm['statusCounts'])): ?>
    <h3 style="font-size:0.9rem;font-weight:600;margin-bottom:0.75rem;color:var(--admin-text-secondary);">
        Распределение по статусам
    </h3>
    <div style="display:flex;flex-direction:column;gap:0.4rem;">
        <?php
        $maxCount = max($pm['statusCounts']) ?: 1;
        $barColors = ['#3b82f6','#10b981','#f59e0b','#8b5cf6','#ef4444','#06b6d4','#84cc16','#f97316'];
        $ci = 0;
        foreach ($pm['statusCounts'] as $sname => $cnt):
            $pct = round($cnt / $pm['total'] * 100, 1);
            $barW = round($cnt / $maxCount * 100);
            $color = $barColors[$ci++ % count($barColors)];
        ?>
        <div style="display:flex;align-items:center;gap:0.75rem;font-size:0.85rem;">
            <div style="width:160px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;color:var(--admin-text-secondary);"
                 title="<?= Html::encode($sname) ?>">
                <?= Html::encode($sname) ?>
            </div>
            <div style="flex:1;background:#f1f5f9;border-radius:4px;height:18px;overflow:hidden;">
                <div style="width:<?= $barW ?>%;height:100%;background:<?= $color ?>;border-radius:4px;"></div>
            </div>
            <div style="width:70px;text-align:right;font-weight:600;"><?= $cnt ?> (<?= $pct ?>%)</div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
<?php endforeach; ?>

<!-- Tags cloud -->
<?php if (!empty($tagCounts)): ?>
<div class="admin-card" style="margin-bottom:1.5rem;">
    <h2 class="admin-card-title"><i class="bi bi-tags"></i> Популярные теги</h2>
    <div style="display:flex;flex-wrap:wrap;gap:0.5rem;">
        <?php
        $maxTag = max($tagCounts) ?: 1;
        foreach ($tagCounts as $tag => $cnt):
            $sz = round(0.75 + ($cnt / $maxTag) * 0.75, 2);
            $opacity = round(0.5 + ($cnt / $maxTag) * 0.5, 2);
        ?>
        <span style="background:#eff6ff;color:#1d4ed8;border-radius:9999px;padding:0.25rem 0.75rem;font-size:<?= $sz ?>rem;opacity:<?= $opacity ?>;">
            <?= Html::encode($tag) ?> <strong>(<?= $cnt ?>)</strong>
        </span>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Category A-H distribution -->
<div class="admin-card" style="margin-bottom:1.5rem;">
    <h2 class="admin-card-title">
        <i class="bi bi-diagram-3"></i>
        Категории запросов (A–H)
        <span style="font-size:0.75rem;font-weight:400;color:var(--admin-text-secondary);margin-left:0.5rem;">
            Эвристика по названию лида / тегам. Phase 3 добавит тела сообщений.
        </span>
    </h2>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:0.75rem;">
        <?php
        $catColors = [
            'A' => '#3b82f6', 'B' => '#10b981', 'C' => '#f59e0b',
            'D' => '#8b5cf6', 'E' => '#06b6d4', 'F' => '#f97316',
            'G' => '#84cc16', 'H' => '#ef4444', '?' => '#9ca3af',
        ];
        $totalCategorized = array_sum($categoryCounts) ?: 1;
        foreach ($categoryCounts as $cat => $cnt):
            if ($cnt === 0) continue;
            $pct = round($cnt / $totalCategorized * 100, 1);
            $color = $catColors[$cat] ?? '#9ca3af';
            $label = $categoryLabels[$cat] ?? $cat;
        ?>
        <div style="background:var(--admin-bg-secondary,#f8fafc);border-radius:0.5rem;padding:0.75rem;border-left:3px solid <?= $color ?>;">
            <div style="font-size:0.75rem;font-weight:700;color:<?= $color ?>;margin-bottom:0.25rem;">
                <?= $cat !== '?' ? "Категория $cat" : 'Не классиф.' ?>
            </div>
            <div style="font-size:1.25rem;font-weight:700;"><?= $cnt ?> <span style="font-size:0.8rem;color:var(--admin-text-secondary);">(<?= $pct ?>%)</span></div>
            <div style="font-size:0.75rem;color:var(--admin-text-secondary);margin-top:0.2rem;"><?= Html::encode($label) ?></div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Phase roadmap notice -->
<div class="admin-card" style="background:#f0fdf4;border:1px solid #bbf7d0;">
    <h2 class="admin-card-title" style="color:#15803d;"><i class="bi bi-rocket"></i> Дорожная карта чатов</h2>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:1rem;font-size:0.85rem;">
        <div style="padding:0.75rem;background:#fff;border-radius:0.5rem;border:1px solid #bbf7d0;">
            <div style="font-weight:700;color:#15803d;">✅ Фаза 0 (сейчас)</div>
            <div style="color:#4b5563;margin-top:0.25rem;">Аналитика пайплайнов, статусы, конверсия, теги, категории A–H</div>
        </div>
        <div style="padding:0.75rem;background:#fff;border-radius:0.5rem;border:1px solid #e2e8f0;">
            <div style="font-weight:700;color:#6b7280;">⏳ Фаза 1</div>
            <div style="color:#4b5563;margin-top:0.25rem;">Выгрузка исторических чатов, разметка датасета по категориям</div>
        </div>
        <div style="padding:0.75rem;background:#fff;border-radius:0.5rem;border:1px solid #e2e8f0;">
            <div style="font-weight:700;color:#6b7280;">⏳ Фаза 3</div>
            <div style="color:#4b5563;margin-top:0.25rem;">Webhook → Claude Sonnet 4.6 → ответ в чат + тела сообщений в аналитике</div>
        </div>
        <div style="padding:0.75rem;background:#fff;border-radius:0.5rem;border:1px solid #e2e8f0;">
            <div style="font-weight:700;color:#6b7280;">⏳ Фаза 4</div>
            <div style="color:#4b5563;margin-top:0.25rem;">Production deploy, A/B тест ИИ vs живой менеджер, мониторинг NPS</div>
        </div>
    </div>
</div>
