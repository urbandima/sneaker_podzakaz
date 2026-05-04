<?php
/**
 * Chat Analytics — all AmoCRM pipelines, full lead data (CMP-159)
 */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Аналитика чатов AmoCRM';

$tokenStatus       = $tokenStatus       ?? 'ok';
$totalChats        = $totalChats        ?? 0;
$wonLeads          = $wonLeads          ?? 0;
$overallConversion = $overallConversion ?? 0;
$overallAvgRes     = $overallAvgRes     ?? '—';
$pipelineMetrics   = $pipelineMetrics   ?? [];
$tagCounts         = $tagCounts         ?? [];
$categoryCounts    = $categoryCounts    ?? [];
$categoryLabels    = $categoryLabels    ?? [];
$recentLeads       = $recentLeads       ?? [];
$customFieldDefs   = $customFieldDefs   ?? [];
?>

<div style="margin-bottom:1rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:0.5rem;">
    <a href="<?= Url::to(['/admin/analytics']) ?>" style="font-size:0.85rem;color:var(--admin-text-secondary,#64748b);text-decoration:none;">
        <i class="bi bi-arrow-left"></i> Аналитика
    </a>
    <a href="<?= Url::to(['/admin/analytics/export-chats']) ?>"
       style="font-size:0.82rem;padding:0.3rem 0.75rem;border:1px solid #e2e8f0;border-radius:0.375rem;color:#1d4ed8;text-decoration:none;">
        <i class="bi bi-download"></i> Скачать CSV
    </a>
</div>

<?php if ($tokenStatus === 'error'): ?>
<div style="margin-bottom:1.5rem;padding:1rem 1.25rem;border-radius:0.5rem;background:#fef3c7;border:1px solid #f59e0b;color:#92400e;">
    <i class="bi bi-exclamation-triangle"></i>
    <strong>AmoCRM недоступен.</strong> Проверьте <code>AMOCRM_LONG_TOKEN</code> в .env. Данные не загружены.
</div>
<?php endif; ?>

<!-- Summary KPI cards -->
<div class="admin-stats" style="margin-bottom:1.5rem;">
    <div class="admin-stat-card">
        <p class="admin-stat-number"><?= $totalChats ?></p>
        <p class="admin-stat-label">Всего лидов</p>
        <span class="admin-badge admin-badge-info"><?= count($pipelineMetrics) ?> воронок</span>
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
    <p style="margin-top:0.75rem;">Воронки в AmoCRM не найдены или пусты.</p>
</div>
<?php endif; ?>

<!-- Per-pipeline breakdown -->
<?php foreach ($pipelineMetrics as $pid => $pm): ?>
<div class="admin-card" style="margin-bottom:1.5rem;">
    <h2 class="admin-card-title">
        <i class="bi bi-funnel"></i>
        <?= Html::encode($pm['name']) ?>
        <span style="font-size:0.8rem;font-weight:400;color:var(--admin-text-secondary);">ID: <?= $pid ?></span>
    </h2>

    <!-- Mini KPIs -->
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:0.75rem;margin-bottom:1.25rem;">
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
        <?php if ($pm['price_sum'] > 0): ?>
        <div style="background:#f5f3ff;border-radius:0.5rem;padding:0.75rem;text-align:center;">
            <div style="font-size:1.1rem;font-weight:700;color:#7c3aed;"><?= number_format($pm['price_sum'], 0, '.', ' ') ?></div>
            <div style="font-size:0.75rem;color:var(--admin-text-secondary);">Сумма (BYN)</div>
        </div>
        <div style="background:#f5f3ff;border-radius:0.5rem;padding:0.75rem;text-align:center;">
            <div style="font-size:1.1rem;font-weight:700;color:#7c3aed;"><?= number_format($pm['avg_price'], 0, '.', ' ') ?></div>
            <div style="font-size:0.75rem;color:var(--admin-text-secondary);">Ср. чек</div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Status distribution -->
    <?php if (!empty($pm['statusCounts'])): ?>
    <h3 style="font-size:0.85rem;font-weight:600;margin:0 0 0.6rem;color:var(--admin-text-secondary);">По статусам</h3>
    <div style="display:flex;flex-direction:column;gap:0.35rem;margin-bottom:1rem;">
        <?php
        $maxCount = max($pm['statusCounts']) ?: 1;
        $barColors = ['#3b82f6','#10b981','#f59e0b','#8b5cf6','#ef4444','#06b6d4','#84cc16','#f97316'];
        $ci = 0;
        foreach ($pm['statusCounts'] as $sname => $cnt):
            $pct  = $pm['total'] > 0 ? round($cnt / $pm['total'] * 100, 1) : 0;
            $barW = round($cnt / $maxCount * 100);
            $color = $barColors[$ci++ % count($barColors)];
        ?>
        <div style="display:flex;align-items:center;gap:0.6rem;font-size:0.83rem;">
            <div style="width:150px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;color:var(--admin-text);" title="<?= Html::encode($sname) ?>"><?= Html::encode($sname) ?></div>
            <div style="flex:1;background:#f1f5f9;border-radius:4px;height:16px;">
                <div style="width:<?= $barW ?>%;height:100%;background:<?= $color ?>;border-radius:4px;"></div>
            </div>
            <div style="width:80px;text-align:right;font-weight:600;"><?= $cnt ?> <span style="color:#94a3b8;">(<?= $pct ?>%)</span></div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Per-pipeline tags -->
    <?php if (!empty($pm['tagCounts'])): ?>
    <h3 style="font-size:0.85rem;font-weight:600;margin:0 0 0.5rem;color:var(--admin-text-secondary);">Топ теги</h3>
    <div style="display:flex;flex-wrap:wrap;gap:0.4rem;">
        <?php foreach ($pm['tagCounts'] as $tag => $cnt): ?>
        <span style="background:#eff6ff;color:#1d4ed8;border-radius:9999px;padding:0.2rem 0.6rem;font-size:0.78rem;">
            <?= Html::encode($tag) ?> <strong>(<?= $cnt ?>)</strong>
        </span>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
<?php endforeach; ?>

<!-- Recent leads table -->
<?php if (!empty($recentLeads)): ?>
<div class="admin-card" style="margin-bottom:1.5rem;">
    <h2 class="admin-card-title"><i class="bi bi-list-ul"></i> Последние лиды (все воронки)</h2>
    <div style="overflow-x:auto;">
    <table style="width:100%;border-collapse:collapse;font-size:0.83rem;">
        <thead>
        <tr style="background:var(--admin-bg-secondary,#f8fafc);text-align:left;">
            <th style="padding:0.5rem 0.75rem;font-weight:600;white-space:nowrap;">ID</th>
            <th style="padding:0.5rem 0.75rem;font-weight:600;">Название</th>
            <th style="padding:0.5rem 0.75rem;font-weight:600;white-space:nowrap;">Воронка</th>
            <th style="padding:0.5rem 0.75rem;font-weight:600;white-space:nowrap;">Статус</th>
            <th style="padding:0.5rem 0.75rem;font-weight:600;white-space:nowrap;">Сумма</th>
            <th style="padding:0.5rem 0.75rem;font-weight:600;">Теги</th>
            <th style="padding:0.5rem 0.75rem;font-weight:600;white-space:nowrap;">Создан</th>
            <th style="padding:0.5rem 0.75rem;font-weight:600;white-space:nowrap;">Закрыт</th>
        </tr>
        </thead>
        <tbody>
        <?php
        // Build a flat status map from pipelineMetrics for status name lookup
        $flatStatusMap = [];
        foreach ($pipelineMetrics as $pid => $pm) {
            foreach ($pm['statusCounts'] as $sname => $cnt) {
                // statusCounts keys are already names; we need reverse map
                // We'll rely on lead's status_id for coloring and use the name from statusCounts keys
            }
        }
        // Won status IDs (to color green)
        $wonStatusIds = [142];

        foreach ($recentLeads as $i => $lead):
            $isWon  = in_array((int)($lead['status_id'] ?? 0), $wonStatusIds);
            $rowBg  = $i % 2 === 0 ? '#fff' : 'var(--admin-bg-secondary,#f8fafc)';
            $tags   = implode(', ', array_column($lead['_embedded']['tags'] ?? [], 'name'));
            $pipelineId = (int)($lead['pipeline_id'] ?? 0);
            $pipelineName = $pipelineMetrics[$pipelineId]['name'] ?? ('ID ' . $pipelineId);
            // Find status name from pipeline status breakdown
            $statusName = 'ID ' . ($lead['status_id'] ?? '?');
            // Custom fields — collect non-empty values
            $cfPairs = [];
            foreach ($lead['custom_fields_values'] ?? [] as $cf) {
                $cfName = $customFieldDefs[(int)($cf['field_id'] ?? 0)] ?? ('field_' . ($cf['field_id'] ?? '?'));
                $cfVal  = implode(', ', array_column($cf['values'] ?? [], 'value'));
                if ($cfVal !== '') {
                    $cfPairs[] = $cfName . ': ' . $cfVal;
                }
            }
        ?>
        <tr style="background:<?= $rowBg ?>;border-bottom:1px solid #f1f5f9;">
            <td style="padding:0.45rem 0.75rem;color:#94a3b8;">
                <a href="https://<?= Html::encode(getenv('AMOCRM_DOMAIN') ?: 'app.amocrm.ru') ?>/leads/detail/<?= (int)$lead['id'] ?>"
                   target="_blank" rel="noopener" style="color:#3b82f6;text-decoration:none;">#<?= (int)$lead['id'] ?></a>
            </td>
            <td style="padding:0.45rem 0.75rem;max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
                title="<?= Html::encode($lead['name'] ?? '') ?>">
                <?= Html::encode(mb_substr($lead['name'] ?? '—', 0, 60)) ?>
                <?php if (!empty($cfPairs)): ?>
                <br><small style="color:#94a3b8;font-size:0.73rem;"><?= Html::encode(mb_substr(implode(' · ', $cfPairs), 0, 80)) ?></small>
                <?php endif; ?>
            </td>
            <td style="padding:0.45rem 0.75rem;white-space:nowrap;color:var(--admin-text-secondary);"><?= Html::encode($pipelineName) ?></td>
            <td style="padding:0.45rem 0.75rem;white-space:nowrap;">
                <span style="background:<?= $isWon ? '#dcfce7' : '#f1f5f9' ?>;color:<?= $isWon ? '#15803d' : 'inherit' ?>;border-radius:4px;padding:0.15rem 0.5rem;font-size:0.78rem;">
                    <?= Html::encode($statusName) ?>
                </span>
            </td>
            <td style="padding:0.45rem 0.75rem;white-space:nowrap;font-weight:600;">
                <?= ($lead['price'] ?? 0) > 0 ? number_format((float)$lead['price'], 0, '.', ' ') : '—' ?>
            </td>
            <td style="padding:0.45rem 0.75rem;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#64748b;font-size:0.78rem;"
                title="<?= Html::encode($tags) ?>">
                <?= Html::encode(mb_substr($tags, 0, 60)) ?>
            </td>
            <td style="padding:0.45rem 0.75rem;white-space:nowrap;color:var(--admin-text-secondary);">
                <?= !empty($lead['created_at']) ? date('d.m.Y H:i', (int)$lead['created_at']) : '—' ?>
            </td>
            <td style="padding:0.45rem 0.75rem;white-space:nowrap;color:var(--admin-text-secondary);">
                <?= !empty($lead['closed_at']) ? date('d.m.Y H:i', (int)$lead['closed_at']) : '—' ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <div style="margin-top:0.75rem;font-size:0.8rem;color:var(--admin-text-secondary);">
        Показаны последние <?= count($recentLeads) ?> лидов.
        <a href="<?= Url::to(['/admin/analytics/export-chats']) ?>" style="color:#3b82f6;">Скачать все →</a>
    </div>
</div>
<?php endif; ?>

<!-- Global tags cloud -->
<?php if (!empty($tagCounts)): ?>
<div class="admin-card" style="margin-bottom:1.5rem;">
    <h2 class="admin-card-title"><i class="bi bi-tags"></i> Популярные теги (все воронки)</h2>
    <div style="display:flex;flex-wrap:wrap;gap:0.5rem;">
        <?php
        $maxTag = max($tagCounts) ?: 1;
        foreach ($tagCounts as $tag => $cnt):
            $sz      = round(0.78 + ($cnt / $maxTag) * 0.72, 2);
            $opacity = round(0.55 + ($cnt / $maxTag) * 0.45, 2);
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
        <i class="bi bi-diagram-3"></i> Категории запросов (A–H)
        <span style="font-size:0.75rem;font-weight:400;color:var(--admin-text-secondary);margin-left:0.5rem;">
            Эвристика по названию лида / тегам / кастомным полям.
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
            $pct   = round($cnt / $totalCategorized * 100, 1);
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

<!-- Phase 4: AI monitoring -->
<?php
$aiStats = [];
try {
    $aiStats = Yii::$app->db->createCommand("
        SELECT
            COUNT(*)                                           AS total_interactions,
            SUM(CASE WHEN success=1 THEN 1 ELSE 0 END)        AS successful,
            SUM(CASE WHEN escalated=1 THEN 1 ELSE 0 END)      AS escalated_count,
            ROUND(AVG(response_ms),0)                          AS avg_response_ms,
            MAX(response_ms)                                   AS max_response_ms,
            ROUND(100.0 * SUM(CASE WHEN escalated=1 THEN 1 ELSE 0 END) / NULLIF(COUNT(*),0), 1) AS escalation_pct,
            ROUND(100.0 * SUM(CASE WHEN escalated=0 AND success=1 THEN 1 ELSE 0 END) / NULLIF(COUNT(*),0), 1) AS ai_handled_pct
        FROM {{%ai_chat_log}}
        WHERE created_at >= UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 30 DAY))
    ")->queryOne();
} catch (\Throwable $e) { /* table may not exist yet */ }
$totalInteractions = (int)($aiStats['total_interactions'] ?? 0);
?>
<div class="admin-card" style="margin-bottom:1.5rem;<?= $totalInteractions === 0 ? 'opacity:0.8;' : '' ?>">
    <h2 class="admin-card-title">
        <i class="bi bi-robot"></i> Мониторинг ИИ-ассистента
        <span style="font-size:0.75rem;font-weight:400;color:var(--admin-text-secondary);margin-left:0.5rem;">
            За 30 дней · цели: ответ &lt;30с, эскалации &lt;35%, ИИ-обработка &gt;65%
        </span>
    </h2>
    <?php if ($totalInteractions === 0): ?>
    <div style="padding:1.25rem;text-align:center;color:var(--admin-text-secondary);">
        <i class="bi bi-info-circle" style="font-size:1.5rem;opacity:0.5;"></i>
        <p style="margin-top:0.5rem;font-size:0.9rem;">
            ИИ-ассистент ещё не обработал ни одного чата.<br>
            <strong>Для активации:</strong> задайте <code>ANTHROPIC_API_KEY</code> в .env и
            зарегистрируйте webhook в AmoCRM → Настройки → Уведомления →
            URL: <code>/webhook/amocrm/ai-chat</code>
        </p>
        <a href="<?= Url::to(['/webhook/amocrm/ai-chat/status'], true) ?>" target="_blank"
           style="display:inline-block;margin-top:0.5rem;font-size:0.8rem;padding:0.35rem 0.75rem;border:1px solid #e2e8f0;border-radius:0.375rem;color:#1d4ed8;">
            <i class="bi bi-shield-check"></i> Проверить health-check
        </a>
    </div>
    <?php else: ?>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:1rem;">
        <?php
        $avgMs  = (int)($aiStats['avg_response_ms'] ?? 0);
        $msOk   = $avgMs > 0 && $avgMs < 30000;
        $escPct = (float)($aiStats['escalation_pct'] ?? 0);
        $escOk  = $escPct <= 35;
        $aiPct  = (float)($aiStats['ai_handled_pct'] ?? 0);
        $aiOk   = $aiPct >= 65;
        ?>
        <div style="background:var(--admin-bg-secondary,#f8fafc);border-radius:0.5rem;padding:0.75rem;text-align:center;">
            <div style="font-size:1.4rem;font-weight:700;"><?= $totalInteractions ?></div>
            <div style="font-size:0.75rem;color:var(--admin-text-secondary);">Взаимодействий</div>
        </div>
        <div style="background:<?= $msOk ? '#f0fdf4' : '#fef2f2' ?>;border-radius:0.5rem;padding:0.75rem;text-align:center;border:1px solid <?= $msOk ? '#bbf7d0' : '#fecaca' ?>;">
            <div style="font-size:1.4rem;font-weight:700;color:<?= $msOk ? '#15803d' : '#b91c1c' ?>;">
                <?= $avgMs > 0 ? round($avgMs / 1000, 1) . 'с' : '—' ?>
            </div>
            <div style="font-size:0.75rem;color:var(--admin-text-secondary);">Ср. ответ (цель &lt;30с)</div>
        </div>
        <div style="background:<?= $escOk ? '#f0fdf4' : '#fef2f2' ?>;border-radius:0.5rem;padding:0.75rem;text-align:center;border:1px solid <?= $escOk ? '#bbf7d0' : '#fecaca' ?>;">
            <div style="font-size:1.4rem;font-weight:700;color:<?= $escOk ? '#15803d' : '#b91c1c' ?>"><?= $escPct ?>%</div>
            <div style="font-size:0.75rem;color:var(--admin-text-secondary);">Эскалаций (цель &lt;35%)</div>
        </div>
        <div style="background:<?= $aiOk ? '#f0fdf4' : '#fef2f2' ?>;border-radius:0.5rem;padding:0.75rem;text-align:center;border:1px solid <?= $aiOk ? '#bbf7d0' : '#fecaca' ?>;">
            <div style="font-size:1.4rem;font-weight:700;color:<?= $aiOk ? '#15803d' : '#b91c1c' ?>"><?= $aiPct ?>%</div>
            <div style="font-size:0.75rem;color:var(--admin-text-secondary);">ИИ обработал (цель &gt;65%)</div>
        </div>
        <div style="background:var(--admin-bg-secondary,#f8fafc);border-radius:0.5rem;padding:0.75rem;text-align:center;">
            <div style="font-size:1.4rem;font-weight:700;"><?= (int)($aiStats['escalated_count'] ?? 0) ?></div>
            <div style="font-size:0.75rem;color:var(--admin-text-secondary);">Передано менеджеру</div>
        </div>
    </div>
    <?php endif; ?>
</div>
