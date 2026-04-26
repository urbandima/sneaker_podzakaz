<?php
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Белпочта — настройки';

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
                <input type="text" class="admin-form-input" id="bp-api-key"
                       placeholder="Ваш API ключ Белпочты"
                       value="<?= Html::encode($apiKey) ?>">
                <small style="color:var(--admin-text-secondary);font-size:12px">
                    API: <code>https://api.belpost.by/api/v1/tracking</code>
                </small>
            </div>
            <div class="admin-form-group">
                <label class="admin-form-label">Статус плагина</label>
                <select class="admin-form-input" id="bp-active">
                    <option value="1" <?= $isActive ? 'selected' : '' ?>>Активен</option>
                    <option value="0" <?= !$isActive ? 'selected' : '' ?>>Неактивен</option>
                </select>
            </div>
        </div>
    </div>

    <div class="admin-card">
        <div class="admin-card-header">
            <h2 class="admin-card-title"><i class="bi bi-info-circle"></i> О плагине</h2>
        </div>
        <div class="admin-card-body">
            <p style="color:var(--admin-text-secondary);font-size:0.875rem">
                Трекинг почтовых отправлений через Белпочту. Требуется API-ключ от
                <a href="https://api.belpost.by" target="_blank">api.belpost.by</a> (подключается через отдел B2B/партнёров Белпочты).
            </p>
            <p style="color:var(--admin-text-secondary);font-size:0.875rem;margin-top:8px">
                Форматы трек-номеров:<br>
                <code>EA000000000BY</code> — EMS отправление<br>
                <code>RA000000000BY</code> — заказное письмо
            </p>
            <div class="admin-form-group" style="margin-top:16px">
                <label class="admin-form-label">Тест трекинга</label>
                <div style="display:flex;gap:8px">
                    <input type="text" class="admin-form-input" id="bp-test-track" placeholder="EA000000000BY">
                    <button type="button" class="admin-btn admin-btn-secondary admin-btn-sm" onclick="testBPTrack()">
                        <i class="bi bi-search"></i> Проверить
                    </button>
                </div>
                <div id="bp-track-result" style="font-size:12px;margin-top:8px;white-space:pre-wrap"></div>
            </div>
        </div>
    </div>

</div>

<!-- Маппинг статусов -->
<div class="admin-card">
    <div class="admin-card-header">
        <h2 class="admin-card-title"><i class="bi bi-arrow-left-right"></i> Маппинг статусов</h2>
    </div>
    <div class="admin-card-body">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Статус Белпочты</th>
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
        <button class="admin-btn admin-btn-primary" onclick="saveBelpochtaSettings()">
            <i class="bi bi-save"></i> Сохранить настройки
        </button>
        <div id="bp-save-result" style="font-size:13px"></div>
    </div>
</div>

<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

function testBPTrack() {
    const num = document.getElementById('bp-test-track').value.trim();
    if (!num) return;
    const out = document.getElementById('bp-track-result');
    out.textContent = 'Запрашиваем...';
    fetch('<?= Url::to(['/admin/plugin/test-tracking']) ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken},
        body: JSON.stringify({provider: 'belpochta', track: num})
    })
    .then(r => r.json())
    .then(d => {
        out.textContent = JSON.stringify(d, null, 2);
        out.style.color = d.status === 'error' || d.status === 'not_configured' ? '#991b1b' : '#065f46';
    })
    .catch(() => { out.textContent = 'Ошибка сети'; out.style.color = '#991b1b'; });
}

function saveBelpochtaSettings() {
    const result = document.getElementById('bp-save-result');
    result.textContent = 'Сохраняем...';
    result.style.color = '#6b7280';
    fetch('<?= Url::to(['/admin/plugin/save-belpochta']) ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken},
        body: JSON.stringify({
            api_key: document.getElementById('bp-api-key').value,
            active:  document.getElementById('bp-active').value === '1' ? 1 : 0,
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
