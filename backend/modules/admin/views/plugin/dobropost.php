<?php
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Таможня:ДП — настройки';

try {
    $dpEmail   = Yii::$app->dobropost->email ?? '';
    $dpTariff  = Yii::$app->dobropost->defaultTariff ?? 26;
    $connected = !empty($dpEmail);
} catch (\Exception $e) {
    $dpEmail = ''; $dpTariff = 26; $connected = false;
}

$autoSend = Yii::$app->settings->get('dobropost', 'auto_send', 'manual');
$autoSendOptions = [
    'manual'            => 'Вручную',
    'on_passport'       => 'При получении паспортных данных',
    'on_confirmed_paid' => 'При статусе "Подтвержден и оплачен"',
];
?>

<?php
$this->params['headerActions'] = [
    Html::a('<i class="bi bi-arrow-left"></i> Все плагины', ['/admin/plugin'], ['class' => 'admin-btn admin-btn-secondary admin-btn-sm']),
    '<span class="admin-badge ' . ($connected ? 'admin-badge-success' : 'admin-badge-secondary') . '" id="dp-status-badge">' . ($connected ? 'Подключено' : 'Не подключено') . '</span>'
];
?>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(400px,1fr));gap:24px">

    <div class="admin-card">
        <div class="admin-card-header">
            <h2 class="admin-card-title"><i class="bi bi-key"></i> Авторизация API</h2>
        </div>
        <div class="admin-card-body">
            <div class="form-group">
                <label>Email (логин API)</label>
                <input type="email" class="admin-form-input" id="dp-email"
                       placeholder="your@email.com"
                       value="<?= htmlspecialchars($dpEmail) ?>">
            </div>
            <div class="form-group">
                <label>Пароль API</label>
                <input type="password" class="admin-form-input" id="dp-password"
                       placeholder="••••••••••••••••">
                <small class="text-muted-sm">
                    Установите в <code>.env</code>: <code>DP_API_PASSWORD=...</code>
                </small>
            </div>
            <div class="form-group">
                <label>Тариф по умолчанию</label>
                <input type="number" class="admin-form-input" id="dp-tariff"
                       placeholder="26"
                       value="<?= htmlspecialchars($dpTariff) ?>">
                <small class="text-muted-sm">Код тарифа Таможня:ДП (26 = стандарт)</small>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <button class="admin-btn admin-btn-primary" onclick="testDPConn()">
                    <i class="bi bi-check-circle"></i> Проверить подключение
                </button>
                <button class="admin-btn admin-btn-secondary" onclick="saveDPConn()">
                    <i class="bi bi-save"></i> Сохранить
                </button>
            </div>
            <div id="dp-test-result" class="mt-10px fs-xs"></div>
        </div>
    </div>

    <div class="admin-card">
        <div class="admin-card-header">
            <h2 class="admin-card-title"><i class="bi bi-gear"></i> Настройки отправки</h2>
        </div>
        <div class="admin-card-body">
            <div class="form-group">
                <label>Авто-отправка в Таможня:ДП</label>
                <select class="admin-form-input" id="dp-auto-send">
                    <?php foreach ($autoSendOptions as $val => $label): ?>
                        <option value="<?= $val ?>" <?= $autoSend === $val ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Webhook URL</label>
                <div style="display:flex;gap:8px;align-items:center">
                    <input type="text" class="admin-form-input"
                           value="<?= htmlspecialchars(\yii\helpers\Url::to(['/api/webhook/dobropost'], true)) ?>"
                           readonly id="dp-webhook-url">
                    <button class="admin-btn admin-btn-secondary" type="button" title="Скопировать"
                            onclick="navigator.clipboard.writeText(document.getElementById('dp-webhook-url').value).then(()=>this.innerHTML='<i class=\'bi bi-check\'></i>')">
                        <i class="bi bi-clipboard"></i>
                    </button>
                </div>
                <small class="text-muted-sm">
                    Укажите этот URL в настройках Таможня:ДП → Webhook
                </small>
            </div>
            <button class="admin-btn admin-btn-secondary w-100" onclick="saveDPSettings()">
                <i class="bi bi-save"></i> Сохранить настройки
            </button>
            <div id="dp-settings-result" class="mt-10px fs-xs"></div>
        </div>
    </div>

</div>

<script>
const dpResult = document.getElementById('dp-test-result');
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

function testDPConn() {
    dpResult.textContent = 'Проверяем подключение...'; dpResult.style.color = '#6b7280';
    fetch('/admin/order/dp-test', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken},
        body: JSON.stringify({
            email:    document.getElementById('dp-email').value,
            password: document.getElementById('dp-password').value
        })
    })
    .then(r => r.json())
    .then(d => {
        dpResult.textContent = d.success ? ('✓ ' + (d.message || 'Подключение успешно')) : ('✗ ' + (d.message || 'Ошибка'));
        dpResult.style.color = d.success ? '#065f46' : '#991b1b';
        document.getElementById('dp-status-badge').textContent = d.success ? 'Подключено' : 'Не подключено';
        document.getElementById('dp-status-badge').className = 'admin-badge ' + (d.success ? 'admin-badge-success' : 'admin-badge-secondary');
    })
    .catch(() => { dpResult.textContent = '✗ Ошибка сети'; dpResult.style.color = '#991b1b'; });
}

function saveDPConn() {
    dpResult.textContent = 'Сохранено (настройки применяются через .env и конфиг)'; dpResult.style.color = '#6b7280';
}

function saveDPSettings() {
    const res = document.getElementById('dp-settings-result');
    const autoSend = document.getElementById('dp-auto-send').value;
    fetch('<?= Url::to(['/admin/settings/save']) ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken},
        body: JSON.stringify({dobropost: {auto_send: autoSend}})
    })
    .then(r => r.json())
    .then(d => {
        res.textContent = d.success ? '✓ Настройки сохранены' : ('✗ ' + d.message);
        res.style.color = d.success ? '#065f46' : '#991b1b';
    })
    .catch(() => { res.textContent = '✗ Ошибка сети'; res.style.color = '#991b1b'; });
}
</script>
