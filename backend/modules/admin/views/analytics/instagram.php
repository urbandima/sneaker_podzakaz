<?php

use yii\helpers\Html;

$this->title = 'Instagram — аналитика';

$fmt = fn($n) => $n !== null ? number_format((int)$n, 0, '.', ' ') : '—';
$delta = function($cur, $prev) {
    if ($prev === null || $prev == 0 || $cur === null) return null;
    return round(($cur - $prev) / $prev * 100, 1);
};

?>

<style>
.ig-grid   { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:1rem; margin-bottom:1.5rem; }
.ig-card   { background:var(--admin-surface,#fff); border:1px solid var(--admin-border,#e2e8f0); border-radius:10px; padding:1.25rem; }
.ig-card h4 { margin:0 0 .35rem; font-size:.78rem; font-weight:600; text-transform:uppercase; letter-spacing:.04em; color:var(--admin-text-secondary,#64748b); }
.ig-val    { font-size:2rem; font-weight:700; color:var(--admin-text-primary,#111); line-height:1.1; }
.ig-delta  { font-size:.8rem; margin-top:.3rem; }
.ig-delta.up   { color:#10b981; }
.ig-delta.down { color:#ef4444; }
.ig-delta.flat { color:#6b7280; }
.ig-posts  { display:grid; grid-template-columns:repeat(auto-fill,minmax(260px,1fr)); gap:1rem; }
.ig-post   { background:var(--admin-surface,#fff); border:1px solid var(--admin-border,#e2e8f0); border-radius:10px; padding:1rem; }
.ig-post-rank { font-size:.7rem; font-weight:700; text-transform:uppercase; color:var(--admin-text-secondary,#64748b); margin-bottom:.4rem; }
.ig-post-thumb { width:100%; aspect-ratio:1; object-fit:cover; border-radius:6px; background:#f1f5f9; margin-bottom:.6rem; }
.ig-post-stats { display:flex; gap:1rem; font-size:.85rem; }
.ig-post-stats span { color:var(--admin-text-secondary,#64748b); }
.ig-post-stats b { color:var(--admin-text-primary,#111); }
.ig-section-title { font-size:1rem; font-weight:700; margin:.5rem 0 1rem; color:var(--admin-text-primary,#111); }
.ig-week-label { font-size:.75rem; color:var(--admin-text-secondary,#64748b); margin-bottom:.15rem; }
.ig-alert  { background:#fef3c7; border:1px solid #fbbf24; border-radius:8px; padding:.85rem 1rem; margin-bottom:1.25rem; color:#92400e; font-size:.875rem; }
.ig-no-token { background:#f1f5f9; border:1px solid var(--admin-border,#e2e8f0); border-radius:10px; padding:2rem; text-align:center; color:var(--admin-text-secondary,#64748b); }
</style>

<?php if ($error): ?>
<div class="ig-alert"><i class="bi bi-exclamation-triangle-fill"></i> <?= Html::encode($error) ?></div>
<?php endif; ?>

<?php if (!$thisMetrics && !$prevMetrics && !$error): ?>
<div class="ig-no-token"><i class="bi bi-instagram" style="font-size:2rem;margin-bottom:.5rem;display:block;"></i>Нет данных. Проверьте токен LiveDune в настройках.</div>
<?php else: ?>

<!-- Текущая неделя -->
<div class="ig-week-label">Текущая неделя (<?= Html::encode($thisWeekFrom) ?> — <?= Html::encode($thisWeekTo) ?>)</div>
<div class="ig-grid">
    <?php
    $metrics = [
        ['label' => 'Reach (охват)', 'key' => 'reach',         'icon' => 'bi-eye'],
        ['label' => 'Визиты профиля', 'key' => 'profile_views', 'icon' => 'bi-person-circle'],
        ['label' => 'Stories (ср/день)', 'key' => 'stories_avg', 'icon' => 'bi-film'],
    ];
    foreach ($metrics as $m):
        $cur = $thisMetrics[$m['key']] ?? null;
        $prv = $prevMetrics[$m['key']] ?? null;
        $d   = $delta($cur, $prv);
    ?>
    <div class="ig-card">
        <h4><i class="bi <?= $m['icon'] ?>"></i> <?= $m['label'] ?></h4>
        <div class="ig-val"><?= $fmt($cur) ?></div>
        <?php if ($d !== null): ?>
        <div class="ig-delta <?= $d > 0 ? 'up' : ($d < 0 ? 'down' : 'flat') ?>">
            <?= $d > 0 ? '▲' : ($d < 0 ? '▼' : '→') ?> <?= abs($d) ?>% vs прошлая неделя
        </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>

<!-- Прошлая неделя -->
<?php if ($prevMetrics): ?>
<div class="ig-week-label" style="margin-top:.5rem;">Прошлая неделя (<?= Html::encode($prevWeekFrom) ?> — <?= Html::encode($prevWeekTo) ?>)</div>
<div class="ig-grid" style="opacity:.7;">
    <?php foreach ($metrics as $m): $prv = $prevMetrics[$m['key']] ?? null; ?>
    <div class="ig-card">
        <h4><i class="bi <?= $m['icon'] ?>"></i> <?= $m['label'] ?></h4>
        <div class="ig-val" style="font-size:1.5rem;"><?= $fmt($prv) ?></div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Топ-3 поста -->
<?php if (!empty($topPosts)): ?>
<div class="ig-section-title" style="margin-top:1.5rem;"><i class="bi bi-trophy-fill" style="color:#f59e0b;"></i> Топ-3 поста по охвату</div>
<div class="ig-posts">
    <?php foreach ($topPosts as $i => $post): ?>
    <div class="ig-post">
        <div class="ig-post-rank">#<?= $i + 1 ?></div>
        <?php if (!empty($post['thumbnail_url'])): ?>
        <img src="<?= Html::encode($post['thumbnail_url']) ?>" class="ig-post-thumb" alt="post thumbnail" loading="lazy">
        <?php else: ?>
        <div class="ig-post-thumb" style="display:flex;align-items:center;justify-content:center;"><i class="bi bi-image" style="font-size:2rem;color:#94a3b8;"></i></div>
        <?php endif; ?>
        <div class="ig-post-stats">
            <span><b><?= $fmt($post['reach'] ?? null) ?></b> охват</span>
            <?php if (isset($post['saves'])): ?>
            <span><b><?= $fmt($post['saves']) ?></b> сохр.</span>
            <?php endif; ?>
        </div>
        <?php if (!empty($post['published_at'])): ?>
        <div style="font-size:.75rem;color:var(--admin-text-secondary,#64748b);margin-top:.4rem;"><?= Html::encode(substr($post['published_at'], 0, 10)) ?></div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>
<?php elseif (!$error): ?>
<div style="color:var(--admin-text-secondary,#64748b);font-size:.875rem;margin-top:1rem;"><i class="bi bi-info-circle"></i> Посты за период не найдены.</div>
<?php endif; ?>

<?php endif; ?>
