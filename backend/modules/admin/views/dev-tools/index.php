<?php

use yii\helpers\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var array $diagnostics */

$this->title = 'Dev Tools';
$this->params['breadcrumbs'][] = $this->title;

$totalIssues = array_sum(array_map(static fn($section) => count($section['issues'] ?? []), $diagnostics));
$sections = [
    'database' => ['icon' => 'database', 'label' => 'База данных'],
    'cache' => ['icon' => 'lightning-charge', 'label' => 'Кэш'],
    'logs' => ['icon' => 'file-text', 'label' => 'Логи'],
    'routes' => ['icon' => 'signpost', 'label' => 'Маршруты'],
    'files' => ['icon' => 'folder', 'label' => 'Файлы'],
    'performance' => ['icon' => 'speedometer2', 'label' => 'Производительность'],
];

$statusMeta = [
    'ok' => ['label' => 'OK', 'class' => 'admin-status admin-status--active', 'card' => 'status-ok'],
    'warning' => ['label' => 'Предупреждение', 'class' => 'admin-status admin-status--pending', 'card' => 'status-warning'],
    'error' => ['label' => 'Ошибка', 'class' => 'admin-status admin-status--error', 'card' => 'status-error'],
];
?>

<div class="admin-page-container dev-tools-admin">
    <div class="admin-page-shell">
        <div class="admin-page-header">
            <div class="admin-page-header-content">
                <div class="admin-page-eyebrow">Диагностика</div>
                <h1 class="admin-page-title">Dev Tools</h1>
                <p class="admin-page-subtitle">
                    Оперативные проверки инфраструктуры и быстрые действия для разработчиков.
                </p>
            </div>
            <div class="admin-page-actions">
                <button class="admin-btn admin-btn--ghost" type="button" onclick="copyAllIssues()">
                    <i class="bi bi-clipboard-check"></i>
                    Копировать проблемы
                </button>
                <button class="admin-btn admin-btn--outline" type="button" onclick="exportDiagnostics()">
                    <i class="bi bi-download"></i>
                    Экспорт JSON
                </button>
                <button class="admin-btn admin-btn--primary" type="button" onclick="location.reload()">
                    <i class="bi bi-arrow-repeat"></i>
                    Обновить
                </button>
            </div>
        </div>

        <div class="admin-grid admin-grid--4 admin-mb-6">
            <div class="admin-metric-card <?= $totalIssues === 0 ? 'metric-success' : 'metric-warning' ?>">
                <span class="admin-metric-label">Всего проблем</span>
                <span class="admin-metric-value"><?= Html::encode($totalIssues) ?></span>
                <span class="admin-metric-change">
                    <i class="bi bi-<?= $totalIssues === 0 ? 'shield-check' : 'exclamation-triangle' ?>"></i>
                    <?= $totalIssues === 0 ? 'Система стабильна' : 'Требуется внимание' ?>
                </span>
            </div>
            <div class="admin-metric-card">
                <span class="admin-metric-label">База данных</span>
                <span class="admin-metric-value">
                    <?= Html::encode($diagnostics['database']['stats']['connection'] ?? '—') ?>
                </span>
                <span class="admin-metric-change">
                    <i class="bi bi-database"></i>
                    Соединение
                </span>
            </div>
            <div class="admin-metric-card">
                <span class="admin-metric-label">Кэш</span>
                <span class="admin-metric-value">
                    <?= Html::encode($diagnostics['cache']['stats']['files_count'] ?? 0) ?>
                </span>
                <span class="admin-metric-change">
                    <i class="bi bi-lightning-charge"></i>
                    Файлов в runtime
                </span>
            </div>
            <div class="admin-metric-card">
                <span class="admin-metric-label">Память PHP</span>
                <span class="admin-metric-value">
                    <?= Html::encode($diagnostics['performance']['stats']['memory_usage'] ?? '—') ?>
                </span>
                <span class="admin-metric-change">
                    <i class="bi bi-speedometer2"></i>
                    <?= Html::encode($diagnostics['performance']['stats']['execution_time'] ?? '') ?>
                </span>
            </div>
        </div>

        <div class="admin-card admin-mb-6">
            <div class="admin-card-header admin-flex admin-flex--between admin-flex--wrap">
                <div>
                    <h2 class="admin-card-title">
                        <i class="bi bi-lightning-charge"></i>
                        Быстрые действия
                    </h2>
                    <p class="admin-card-subtitle">
                        Операции выполняются моментально и помогают восстановить стабильность.
                    </p>
                </div>
            </div>
            <div class="admin-card-body">
                <div class="dev-tools-actions">
                    <button class="admin-btn admin-btn--danger" type="button" onclick="clearCache()">
                        <i class="bi bi-trash"></i>
                        Очистить кэш
                    </button>
                    <button class="admin-btn admin-btn--outline" type="button" onclick="clearLogs()">
                        <i class="bi bi-file-earmark-x"></i>
                        Очистить логи
                    </button>
                    <a class="admin-btn admin-btn--ghost" href="<?= Html::encode(Url::to(['/admin/order/index'])) ?>">
                        <i class="bi bi-cart3"></i>
                        Заказы
                    </a>
                    <a class="admin-btn admin-btn--ghost" href="<?= Html::encode(Url::to(['/admin/product/index'])) ?>">
                        <i class="bi bi-box-seam"></i>
                        Товары
                    </a>
                </div>
            </div>
        </div>

        <div class="admin-grid admin-grid--2 admin-gap-6">
            <?php foreach ($sections as $key => $meta): ?>
                <?php $section = $diagnostics[$key] ?? null; ?>
                <?php if ($section === null) continue; ?>
                <?php $status = $statusMeta[$section['status']] ?? $statusMeta['warning']; ?>
                <div class="admin-card diagnostic-card <?= $status['card'] ?>">
                    <div class="admin-card-header admin-flex admin-flex--between admin-flex--wrap">
                        <div>
                            <h3 class="admin-card-title">
                                <i class="bi bi-<?= $meta['icon'] ?>"></i>
                                <?= Html::encode($meta['label']) ?>
                            </h3>
                            <div class="<?= $status['class'] ?>">
                                <?= Html::encode($status['label']) ?>
                            </div>
                        </div>
                        <span class="admin-badge admin-badge--neutral">
                            <?= count($section['issues'] ?? []) ?> проблем
                        </span>
                    </div>
                    <div class="admin-card-body">
                        <?php if (!empty($section['stats'])): ?>
                            <div class="dev-tools-stat-grid">
                                <?php foreach ($section['stats'] as $label => $value): ?>
                                    <?php if (is_array($value)) continue; ?>
                                    <div>
                                        <span class="dev-tools-stat-label">
                                            <?= Html::encode(str_replace('_', ' ', ucfirst($label))) ?>
                                        </span>
                                        <strong><?= Html::encode($value) ?></strong>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($section['stats']['tables']) && is_array($section['stats']['tables'])): ?>
                            <div class="dev-table-list">
                                <?php foreach ($section['stats']['tables'] as $label => $count): ?>
                                    <div class="dev-table-item">
                                        <span><?= Html::encode($label) ?></span>
                                        <strong><?= Html::encode(number_format($count)) ?></strong>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($section['stats']['error_samples'])): ?>
                            <div class="dev-log-samples">
                                <?php foreach ($section['stats']['error_samples'] as $sample): ?>
                                    <div class="dev-log-line" onclick="copyText(this.textContent)">
                                        <code><?= Html::encode(mb_substr(trim($sample), 0, 220)) ?></code>
                                        <i class="bi bi-clipboard"></i>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <div class="dev-issues-list">
                            <?php if (!empty($section['issues'])): ?>
                                <?php foreach ($section['issues'] as $issue): ?>
                                    <div class="dev-issue" onclick="copyText(this.dataset.issue)" data-issue="<?= Html::encode($issue) ?>">
                                        <i class="bi bi-exclamation-octagon"></i>
                                        <span><?= Html::encode($issue) ?></span>
                                        <i class="bi bi-clipboard"></i>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="dev-success-row">
                                    <i class="bi bi-check-circle"></i>
                                    <span>Проблем не обнаружено</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php
$clearCacheUrl = Url::to(['/admin/dev-tools/clear-cache']);
$clearLogsUrl = Url::to(['/admin/dev-tools/clear-logs']);
$exportUrl = Url::to(['/admin/dev-tools/export-diagnostics']);

$css = <<<CSS
.dev-tools-actions {
    display: flex;
    flex-wrap: wrap;
    gap: var(--admin-space-3);
}
.dev-tools-stat-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: var(--admin-space-3);
    margin-bottom: var(--admin-space-4);
}
.dev-tools-stat-label {
    font-size: var(--admin-text-xs);
    color: var(--admin-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.08em;
}
.dev-table-list {
    display: flex;
    flex-direction: column;
    gap: var(--admin-space-2);
    margin-bottom: var(--admin-space-4);
}
.dev-table-item {
    display: flex;
    justify-content: space-between;
    background: var(--admin-surface-muted);
    padding: var(--admin-space-2) var(--admin-space-3);
    border-radius: var(--admin-radius-md);
    font-weight: 600;
}
.dev-log-samples {
    display: flex;
    flex-direction: column;
    gap: var(--admin-space-2);
    margin-bottom: var(--admin-space-4);
}
.dev-log-line {
    background: var(--admin-surface-muted);
    border-radius: var(--admin-radius-md);
    padding: var(--admin-space-2) var(--admin-space-3);
    font-family: var(--admin-font-mono);
    font-size: 0.85rem;
    display: flex;
    align-items: center;
    gap: var(--admin-space-2);
    cursor: pointer;
    transition: background var(--admin-transition-fast);
}
.dev-log-line code {
    flex: 1;
    color: var(--admin-text-secondary);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.dev-log-line:hover {
    background: var(--admin-surface);
}
.dev-issues-list {
    display: flex;
    flex-direction: column;
    gap: var(--admin-space-2);
}
.dev-issue {
    display: flex;
    align-items: center;
    gap: var(--admin-space-2);
    padding: var(--admin-space-3);
    border: 1px solid var(--admin-border);
    border-radius: var(--admin-radius-md);
    cursor: pointer;
    transition: border var(--admin-transition-fast), transform var(--admin-transition-fast);
}
.dev-issue:hover {
    border-color: var(--admin-danger);
    transform: translateX(3px);
}
.dev-issue i:last-child {
    margin-left: auto;
    color: var(--admin-text-muted);
}
.dev-success-row {
    display: flex;
    align-items: center;
    gap: var(--admin-space-2);
    padding: var(--admin-space-3);
    background: var(--admin-success-soft);
    border-radius: var(--admin-radius-md);
    color: var(--admin-success);
}
.diagnostic-card.status-warning .admin-card-header {
    border-left: 4px solid var(--admin-warning);
}
.diagnostic-card.status-error .admin-card-header {
    border-left: 4px solid var(--admin-danger);
}
.diagnostic-card.status-ok .admin-card-header {
    border-left: 4px solid var(--admin-success);
}
.metric-success {
    border-left: 4px solid var(--admin-success);
}
.metric-warning {
    border-left: 4px solid var(--admin-warning);
}
@media (max-width: 768px) {
    .dev-tools-actions {
        flex-direction: column;
    }
}
CSS;

$this->registerCss($css);

$js = <<<JS
function showAdminToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = 'admin-toast admin-toast--' + type + ' show';
    toast.innerHTML = '<span>' + message + '</span>';
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 2500);
}

function copyText(text) {
    navigator.clipboard.writeText(text.trim()).then(() => {
        showAdminToast('Скопировано', 'success');
    }).catch(() => showAdminToast('Не удалось скопировать', 'error'));
}

function copyAllIssues() {
    const issues = Array.from(document.querySelectorAll('.dev-issue'))
        .map(item => item.dataset.issue)
        .filter(Boolean);
    if (!issues.length) {
        showAdminToast('Активных проблем нет', 'info');
        return;
    }
    const report = [
        '=== Диагностика ===',
        'Дата: ' + new Date().toLocaleString(),
        'Всего проблем: ' + issues.length,
        '',
        ...issues.map((issue, idx) => (idx + 1) + '. ' + issue)
    ].join('\\n');
    navigator.clipboard.writeText(report)
        .then(() => showAdminToast('Список проблем скопирован', 'success'));
}

function clearCache() {
    if (!confirm('Очистить весь кэш приложения?')) return;
    fetch('<?= $clearCacheUrl ?>', {method: 'POST', headers: {'X-Requested-With': 'XMLHttpRequest'}})
        .then(res => res.json())
        .then(data => {
            showAdminToast(data.message, data.success ? 'success' : 'error');
            if (data.success) setTimeout(() => location.reload(), 1000);
        })
        .catch(() => showAdminToast('Ошибка сети', 'error'));
}

function clearLogs() {
    if (!confirm('Очистить все лог-файлы?')) return;
    fetch('<?= $clearLogsUrl ?>', {method: 'POST', headers: {'X-Requested-With': 'XMLHttpRequest'}})
        .then(res => res.json())
        .then(data => {
            showAdminToast(data.message, data.success ? 'success' : 'error');
            if (data.success) setTimeout(() => location.reload(), 1000);
        })
        .catch(() => showAdminToast('Ошибка сети', 'error'));
}

function exportDiagnostics() {
    window.open('<?= $exportUrl ?>', '_blank', 'noopener');
}
JS;

$this->registerJs($js);
