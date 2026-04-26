<?php
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'СДЭК — настройки';

$isActive     = !empty($config['active']);
$clientId     = $config['client_id'] ?? '';
$clientSecret = $config['client_secret'] ?? '';
$apiUrl       = $config['api_url'] ?? 'https://api.cdek.ru/v2';
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
            <h2 class="admin-card-title"><i class="bi bi-key"></i> OAuth2 авторизация</h2>
        </div>
        <div class="admin-card-body">
            <div class="admin-form-group">
                <label class="admin-form-label">Client ID</label>
                <input type="text" class="admin-form-input" id="cdek-client-id"
                       placeholder="Ваш Client ID СДЭК"
                       value="<?= Html::encode($clientId) ?>">
            </div>
            <div class="admin-form-group">
                <label class="admin-form-label">Client Secret</label>
                <input type="password" class="admin-form-input" id="cdek-client-secret"
                       placeholder="••••••••••••••••"
                       value="<?= Html::encode($clientSecret) ?>">
                <small style="color:var(--admin-text-secondary);font-size:12px">
                    Получите в личном кабинете СДЭК: API → Настройки
                </small>
            </div>
            <div class="admin-form-group">
                <label class="admin-form-label">API URL</label>
                <input type="text" class="admin-form-input" id="cdek-api-url"
                       placeholder="https://api.cdek.ru/v2"
                       value="<?= Html::encode($apiUrl) ?>">
                <small style="color:var(--admin-text-secondary);font-size:12px">
                    Тест: <code>https://api.edu.cdek.ru/v2</code> | Прод: <code>https://api.cdek.ru/v2</code>
                </small>
            </div>
            <div class="admin-form-group">
                <label class="admin-form-label">Статус плагина</label>
                <select class="admin-form-input" id="cdek-active">
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
                Плагин СДЭК обеспечивает отслеживание отправлений по России и СНГ через СДЭК API v2.
                Авторизация выполняется по протоколу OAuth2.
            </p>
            <p style="color:var(--admin-text-secondary);font-size:0.875rem;margin-top:8px">
                Эндпоинты:<br>
                <code>POST /v2/oauth/token</code> — получение токена<br>
                <code>GET /v2/orders?cdek_number=…</code> — статус заказа
            </p>
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
                    <th>Статус СДЭК</th>
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
        <button class="admin-btn admin-btn-primary" onclick="saveCdekSettings()">
            <i class="bi bi-save"></i> Сохранить настройки
        </button>
        <div id="cdek-save-result" style="font-size:13px"></div>
    </div>
</div>

<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

function saveCdekSettings() {
    const result = document.getElementById('cdek-save-result');
    result.textContent = 'Сохраняем...';
    result.style.color = '#6b7280';
    fetch('<?= Url::to(['/admin/plugin/save-cdek']) ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken},
        body: JSON.stringify({
            client_id:     document.getElementById('cdek-client-id').value,
            client_secret: document.getElementById('cdek-client-secret').value,
            api_url:       document.getElementById('cdek-api-url').value,
            active:        document.getElementById('cdek-active').value === '1' ? 1 : 0,
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
