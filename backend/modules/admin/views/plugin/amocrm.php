<?php
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'AmoCRM — настройки';
?>

<?php
$this->params['headerActions'] = [
    Html::a('<i class="bi bi-arrow-left"></i> Все плагины', ['/admin/plugin'], ['class' => 'admin-btn admin-btn-secondary admin-btn-sm']),
    '<span class="admin-badge admin-badge-secondary" id="amocrm-status-badge">Не подключено</span>'
];
?>

<div class="admin-card" style="max-width:560px">
    <div class="admin-card-header">
        <h2 class="admin-card-title"><i class="bi bi-key"></i> Настройки OAuth</h2>
    </div>
    <div class="admin-card-body">
        <div class="form-group">
            <label>Домен AmoCRM</label>
            <input type="text" class="admin-form-input" id="amocrm-domain" placeholder="example.amocrm.ru"
                   value="<?= Html::encode(Yii::$app->settings->get('amocrm', 'domain', '')) ?>">
        </div>
        <div class="form-group">
            <label>Client ID</label>
            <input type="text" class="admin-form-input" id="amocrm-client-id" placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"
                   value="<?= Html::encode(Yii::$app->settings->get('amocrm', 'client_id', '')) ?>">
        </div>
        <div class="form-group">
            <label>Client Secret</label>
            <input type="password" class="admin-form-input" id="amocrm-secret" placeholder="••••••••••••••••">
        </div>
        <div class="form-group">
            <label>Access Token</label>
            <textarea class="admin-form-input" rows="3" id="amocrm-token" placeholder="Получите токен после авторизации через OAuth"></textarea>
            <small class="text-muted-sm">
                <a href="https://www.amocrm.ru/developers/content/oauth/oauth" target="_blank" rel="noopener" style="color:var(--admin-accent)">
                    Документация OAuth AmoCRM →
                </a>
            </small>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <button class="admin-btn admin-btn-primary" onclick="testAmoCRMConn()">
                <i class="bi bi-check-circle"></i> Проверить подключение
            </button>
            <button class="admin-btn admin-btn-secondary" onclick="saveAmoCRMConn()">
                <i class="bi bi-save"></i> Сохранить
            </button>
        </div>
        <div id="amo-test-result" class="mt-10px fs-xs"></div>
    </div>
</div>

<script>
const amoResult = document.getElementById('amo-test-result');
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

function testAmoCRMConn() {
    const domain = document.getElementById('amocrm-domain').value;
    const token  = document.getElementById('amocrm-token').value;
    if (!domain || !token) { amoResult.textContent = '⚠ Заполните домен и токен'; amoResult.style.color = '#b45309'; return; }
    amoResult.textContent = 'Проверяем...'; amoResult.style.color = '#6b7280';
    fetch('<?= Url::to(['/admin/settings/test-amocrm']) ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken},
        body: JSON.stringify({domain, api_token: token})
    })
    .then(r => r.json())
    .then(d => {
        amoResult.textContent = d.success ? ('✓ ' + (d.message || 'Подключение успешно')) : ('✗ ' + (d.message || 'Ошибка'));
        amoResult.style.color = d.success ? '#065f46' : '#991b1b';
        document.getElementById('amo-status-badge').textContent = d.success ? 'Подключено' : 'Не подключено';
        document.getElementById('amo-status-badge').className = 'admin-badge ' + (d.success ? 'admin-badge-success' : 'admin-badge-secondary');
    })
    .catch(() => { amoResult.textContent = '✗ Ошибка сети'; amoResult.style.color = '#991b1b'; });
}

function saveAmoCRMConn() {
    const data = {
        domain:    document.getElementById('amocrm-domain').value,
        client_id: document.getElementById('amocrm-client-id').value,
        api_token: document.getElementById('amocrm-token').value,
    };
    fetch('<?= Url::to(['/admin/settings/save']) ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken},
        body: JSON.stringify({amocrm: data})
    })
    .then(r => r.json())
    .then(d => {
        amoResult.textContent = d.success ? '✓ Настройки сохранены' : ('✗ ' + d.message);
        amoResult.style.color = d.success ? '#065f46' : '#991b1b';
    })
    .catch(() => { amoResult.textContent = '✗ Ошибка сети'; amoResult.style.color = '#991b1b'; });
}
</script>
