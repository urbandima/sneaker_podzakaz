<?php
/**
 * Источники заказов — справочник
 */
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Источники заказов';
$this->params['breadcrumbs'][] = ['label' => 'Настройки', 'url' => ['/admin/settings']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="admin-page-header">
    <div>
        <h1 class="admin-page-title"><i class="bi bi-funnel"></i> Источники заказов</h1>
        <p class="admin-page-subtitle">Справочник источников для аналитики и воронки продаж</p>
    </div>
</div>

<div style="max-width:600px">
    <div class="admin-card">
        <div class="admin-card-head" style="padding:16px 20px;border-bottom:1px solid var(--admin-border,#e5e7eb);display:flex;align-items:center;justify-content:space-between">
            <h3 style="font-size:0.875rem;font-weight:700;margin:0"><i class="bi bi-list-ul"></i> Список источников</h3>
            <span style="font-size:0.75rem;color:var(--admin-text-secondary,#6b7280)">Порядок = порядок в выпадающем списке</span>
        </div>
        <div style="padding:16px 20px">
            <div id="sources-list" style="display:flex;flex-direction:column;gap:6px;margin-bottom:16px">
                <?php foreach ($sources as $i => $src): ?>
                <div class="source-row" style="display:flex;align-items:center;gap:8px;background:var(--admin-surface-2,#f8f9fa);border:1px solid var(--admin-border,#e5e7eb);border-radius:8px;padding:8px 12px">
                    <i class="bi bi-grip-vertical" style="color:var(--admin-text-secondary,#9ca3af);cursor:grab;flex-shrink:0"></i>
                    <input type="text" class="admin-form-input source-name-input" value="<?= Html::encode($src) ?>" placeholder="Название источника" style="flex:1;font-size:0.875rem;padding:5px 10px">
                    <button type="button" class="admin-btn admin-btn-sm remove-source-btn" style="padding:5px 8px;background:transparent;border:1px solid var(--admin-border,#e5e7eb);color:var(--admin-text-secondary,#6b7280);border-radius:6px;flex-shrink:0" title="Удалить">
                        <i class="bi bi-trash3" style="pointer-events:none"></i>
                    </button>
                </div>
                <?php endforeach; ?>
            </div>

            <div style="display:flex;gap:8px;margin-bottom:20px">
                <button type="button" id="add-source-btn" class="admin-btn admin-btn-secondary admin-btn-sm">
                    <i class="bi bi-plus-lg"></i> Добавить источник
                </button>
                <div style="border-left:1px solid var(--admin-border,#e5e7eb);margin:0 4px"></div>
                <button type="button" id="reset-defaults-btn" class="admin-btn admin-btn-secondary admin-btn-sm" style="color:var(--admin-text-secondary,#6b7280)">
                    <i class="bi bi-arrow-counterclockwise"></i> По умолчанию
                </button>
            </div>

            <button type="button" id="save-sources-btn" class="admin-btn admin-btn-primary" style="gap:6px">
                <i class="bi bi-check2"></i> Сохранить список
            </button>
            <span id="save-result" style="font-size:0.8125rem;margin-left:10px"></span>
        </div>
    </div>

    <div class="admin-card" style="margin-top:16px">
        <div style="padding:14px 20px">
            <div style="font-size:0.8125rem;color:var(--admin-text-secondary,#6b7280)">
                <i class="bi bi-info-circle"></i>
                Источники отображаются в выпадающем списке при просмотре заказа.
                Используйте для сегментации по каналам привлечения клиентов.
            </div>
        </div>
    </div>
</div>

<?php
$defaultSourcesJson = json_encode($defaultSources, JSON_UNESCAPED_UNICODE);
$saveUrl = Url::to(['/admin/settings/sources']);
$csrf = Yii::$app->request->csrfToken;

$this->registerJs(<<<JS
var defaultSources = {$defaultSourcesJson};

function makeRow(value) {
    var div = document.createElement('div');
    div.className = 'source-row';
    div.style.cssText = 'display:flex;align-items:center;gap:8px;background:var(--admin-surface-2,#f8f9fa);border:1px solid var(--admin-border,#e5e7eb);border-radius:8px;padding:8px 12px';
    div.innerHTML = '<i class="bi bi-grip-vertical" style="color:var(--admin-text-secondary,#9ca3af);cursor:grab;flex-shrink:0"></i>'
        + '<input type="text" class="admin-form-input source-name-input" value="' + value.replace(/"/g,'&quot;') + '" placeholder="Название источника" style="flex:1;font-size:0.875rem;padding:5px 10px">'
        + '<button type="button" class="admin-btn admin-btn-sm remove-source-btn" style="padding:5px 8px;background:transparent;border:1px solid var(--admin-border,#e5e7eb);color:var(--admin-text-secondary,#6b7280);border-radius:6px;flex-shrink:0" title="Удалить"><i class="bi bi-trash3" style="pointer-events:none"></i></button>';
    return div;
}

document.getElementById('add-source-btn').addEventListener('click', function() {
    var list = document.getElementById('sources-list');
    var row = makeRow('');
    list.appendChild(row);
    row.querySelector('input').focus();
});

document.getElementById('reset-defaults-btn').addEventListener('click', function() {
    if (!confirm('Восстановить список по умолчанию?')) return;
    var list = document.getElementById('sources-list');
    list.innerHTML = '';
    defaultSources.forEach(function(s) { list.appendChild(makeRow(s)); });
});

document.addEventListener('click', function(e) {
    if (e.target.classList.contains('remove-source-btn') || e.target.closest('.remove-source-btn')) {
        var row = e.target.closest('.source-row');
        if (row) row.remove();
    }
});

document.getElementById('save-sources-btn').addEventListener('click', function() {
    var inputs = document.querySelectorAll('#sources-list .source-name-input');
    var sources = [];
    inputs.forEach(function(inp) {
        var v = inp.value.trim();
        if (v) sources.push(v);
    });
    var btn = this;
    var result = document.getElementById('save-result');
    btn.disabled = true;
    result.textContent = '';
    fetch('{$saveUrl}', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-Token': '{$csrf}'},
        body: JSON.stringify({sources: sources})
    })
    .then(r => r.json())
    .then(d => {
        btn.disabled = false;
        if (d.success) {
            result.style.color = 'var(--admin-success,#059669)';
            result.textContent = '✓ Сохранено';
            setTimeout(() => result.textContent = '', 3000);
        } else {
            result.style.color = '#dc2626';
            result.textContent = 'Ошибка';
        }
    })
    .catch(() => { btn.disabled = false; result.textContent = 'Ошибка сети'; result.style.color='#dc2626'; });
});
JS);
?>
