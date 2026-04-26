<?php
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Европочта — настройки';

$isActive = !empty($config['active']);
$apiKey   = $config['api_key'] ?? '';
?>

<?php
$this->params['headerActions'] = [
    Html::a('<i class="bi bi-arrow-left"></i> Все плагины', ['/admin/plugin'], ['class' => 'admin-btn admin-btn-secondary admin-btn-sm']),
    '<span class="admin-badge ' . ($isActive ? 'admin-badge-success' : 'admin-badge-secondary') . '">' . ($isActive ? 'Активен' : 'Неактивен') . '</span>',
];
?>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(400px,1fr));gap:24px;margin-bottom:24px">

    <div class="admin-card">
        <div class="admin-card-header">
            <h2 class="admin-card-title"><i class="bi bi-key"></i> Настройки API</h2>
        </div>
        <div class="admin-card-body">
            <div class="admin-form-group">
                <label class="admin-form-label">API ключ</label>
                <input type="text" class="admin-form-input" id="ep-api-key"
                       placeholder="Ваш API ключ Европочты"
                       value="<?= Html::encode($apiKey) ?>">
                <small style="color:var(--admin-text-secondary);font-size:12px">
                    Получите ключ в личном кабинете Европочты
                </small>
            </div>
            <div class="admin-form-group">
                <label class="admin-form-label">Статус плагина</label>
                <select class="admin-form-input" id="ep-active">
                    <option value="1" <?= $isActive ? 'selected' : '' ?>>Активен</option>
                    <option value="0" <?= !$isActive ? 'selected' : '' ?>>Неактивен</option>
                </select>
            </div>
        </div>
    </div>

    <div class="admin-card">
        <div class="admin-card-header">
            <h2 class="admin-card-title"><i class="bi bi-geo-alt"></i> Пункты выдачи (ПВЗ)</h2>
        </div>
        <div class="admin-card-body">
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px">
                <span style="font-size:1.5rem;font-weight:700;color:var(--admin-accent)"><?= $pvzCount ?></span>
                <span style="font-size:0.8rem;color:var(--admin-text-secondary)">пунктов выдачи загружено</span>
                <a href="<?= Url::to(['/admin/settings/shipping']) ?>" class="admin-btn admin-btn-secondary admin-btn-sm" style="margin-left:auto">
                    <i class="bi bi-gear"></i> Управление
                </a>
            </div>
            <div class="admin-form-group">
                <label class="admin-form-label">Тест трекинга</label>
                <div style="display:flex;gap:8px">
                    <input type="text" class="admin-form-input" id="ep-test-track" placeholder="EP123456789BY">
                    <button type="button" class="admin-btn admin-btn-secondary admin-btn-sm" onclick="testEPTrack()">
                        <i class="bi bi-search"></i> Проверить
                    </button>
                </div>
                <div id="ep-track-result" style="font-size:12px;margin-top:8px;white-space:pre-wrap"></div>
                <small style="color:var(--admin-text-secondary);font-size:11px">
                    Европочта не требует API-ключ — используется публичный endpoint
                </small>
            </div>
        </div>
    </div>

</div>

<!-- Справочник ПВЗ -->
<div class="admin-card" style="margin-bottom:24px">
    <div class="admin-card-header">
        <h2 class="admin-card-title"><i class="bi bi-list-ul"></i> Справочник ПВЗ</h2>
    </div>
    <div class="admin-card-body">
        <div style="display:flex;gap:10px;margin-bottom:16px">
            <input type="text" id="pvzSearchInput" class="admin-form-input" placeholder="Поиск по городу или адресу..."
                   oninput="filterPvzTable(this.value)" style="max-width:360px">
            <span id="pvzMatchCount" style="align-self:center;font-size:0.82rem;color:var(--admin-text-secondary)"></span>
        </div>
        <?php if (empty($pvzList)): ?>
        <p style="color:var(--admin-text-secondary);font-size:0.875rem">
            Пункты выдачи не загружены.
            Добавьте данные в <a href="<?= Url::to(['/admin/settings/shipping']) ?>">настройках доставки</a>.
        </p>
        <?php else: ?>
        <div style="max-height:400px;overflow-y:auto;border:1px solid var(--admin-border,#e5e7eb);border-radius:8px">
            <table class="admin-table" id="pvzTable" style="margin:0">
                <thead>
                    <tr>
                        <th style="width:50px">№</th>
                        <th style="width:160px">Город</th>
                        <th>Адрес / Название</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pvzList as $pt): ?>
                    <tr class="pvz-row">
                        <td><?= Html::encode($pt['num'] ?? '') ?></td>
                        <td><?= Html::encode($pt['city'] ?? '') ?></td>
                        <td><?= Html::encode($pt['full'] ?? $pt['name'] ?? '') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p style="font-size:0.78rem;color:var(--admin-text-secondary);margin-top:8px">
            Всего: <?= $pvzCount ?> пунктов выдачи
        </p>
        <?php endif; ?>
    </div>
</div>
<script>
function filterPvzTable(q) {
    var rows = document.querySelectorAll('#pvzTable .pvz-row');
    var query = q.trim().toLowerCase();
    var shown = 0;
    rows.forEach(function(row) {
        var text = row.textContent.toLowerCase();
        var match = !query || text.indexOf(query) !== -1;
        row.style.display = match ? '' : 'none';
        if (match) shown++;
    });
    var counter = document.getElementById('pvzMatchCount');
    if (counter) counter.textContent = query ? ('Найдено: ' + shown) : '';
}
</script>

<!-- Маппинг статусов -->
<div class="admin-card">
    <div class="admin-card-header">
        <h2 class="admin-card-title"><i class="bi bi-arrow-left-right"></i> Маппинг статусов</h2>
    </div>
    <div class="admin-card-body">
        <p style="color:var(--admin-text-secondary);font-size:0.875rem;margin-bottom:16px">
            Соответствие статусов Европочты внутренним статусам системы.
        </p>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Статус Европочты</th>
                    <th>Наш статус</th>
                    <th>Отображение клиенту</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($statusMappings as $m): ?>
                <tr>
                    <td><?= Html::encode($m->provider_status_name) ?></td>
                    <td><code><?= Html::encode($m->internal_status) ?></code></td>
                    <td><?= Html::encode($m->display_name) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($statusMappings)): ?>
                <tr><td colspan="3" style="color:var(--admin-text-secondary);text-align:center">Статусы не настроены</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="admin-card">
    <div class="admin-card-body" style="display:flex;align-items:center;gap:16px;flex-wrap:wrap">
        <button class="admin-btn admin-btn-primary" onclick="saveEuropochtaSettings()">
            <i class="bi bi-save"></i> Сохранить настройки
        </button>
        <div id="ep-save-result" style="font-size:13px"></div>
    </div>
</div>

<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

function testEPTrack() {
    const num = document.getElementById('ep-test-track').value.trim();
    if (!num) return;
    const out = document.getElementById('ep-track-result');
    out.textContent = 'Запрашиваем...';
    fetch('<?= Url::to(['/admin/plugin/test-tracking']) ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken},
        body: JSON.stringify({provider: 'europochta', track: num})
    })
    .then(r => r.json())
    .then(d => {
        out.textContent = JSON.stringify(d, null, 2);
        out.style.color = (d.status === 'error' || d.status === 'not_configured' || d.status === 'not_found') ? '#991b1b' : '#065f46';
    })
    .catch(() => { out.textContent = 'Ошибка сети'; out.style.color = '#991b1b'; });
}

function saveEuropochtaSettings() {
    const result = document.getElementById('ep-save-result');
    result.textContent = 'Сохраняем...';
    result.style.color = '#6b7280';
    fetch('<?= Url::to(['/admin/plugin/save-europochta']) ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken},
        body: JSON.stringify({
            api_key: document.getElementById('ep-api-key').value,
            active:  document.getElementById('ep-active').value === '1' ? 1 : 0,
        })
    })
    .then(r => r.json())
    .then(d => {
        result.textContent = d.success ? ('✓ ' + d.message) : ('✗ ' + (d.message || 'Ошибка'));
        result.style.color = d.success ? 'var(--admin-accent,#065f46)' : '#991b1b';
    })
    .catch(() => { result.textContent = '✗ Ошибка сети'; result.style.color = '#991b1b'; });
}
</script>
