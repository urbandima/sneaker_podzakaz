<?php
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'AmoCRM — интеграция';

$apiKey     = Yii::$app->settings->get('amocrm', 'api_key', '');
$domain     = Yii::$app->settings->get('amocrm', 'domain', '');
$siteUrl    = Yii::$app->request->hostInfo;

// Последние заказы из AmoCRM
$recentOrders = \app\backend\modules\checkout\models\Order::find()
    ->where(['source' => 'amoCRM'])
    ->orderBy(['created_at' => SORT_DESC])
    ->limit(25)
    ->all();
?>

<?php
$this->params['headerActions'] = [
    Html::a('<i class="bi bi-arrow-left"></i> Все плагины', ['/admin/plugin'], ['class' => 'admin-btn admin-btn-secondary admin-btn-sm']),
    '<span class="admin-badge ' . ($apiKey ? 'admin-badge-success' : 'admin-badge-secondary') . '" id="amocrm-status-badge">' . ($apiKey ? 'Активно' : 'Не настроено') . '</span>'
];
?>

<!-- 1. API-ключ -->
<div class="admin-card" style="max-width:640px;margin-bottom:20px">
    <div class="admin-card-header">
        <h2 class="admin-card-title"><i class="bi bi-key"></i> API-ключ для виджета</h2>
    </div>
    <div class="admin-card-body">
        <p style="font-size:13px;color:var(--admin-text-secondary);margin:0 0 12px">
            Ключ используется виджетом AmoCRM для авторизации запросов к вашему магазину. Передаётся в заголовке <code>X-Api-Key</code>.
        </p>
        <div style="display:flex;gap:8px;align-items:flex-end;margin-bottom:12px">
            <div style="flex:1">
                <label class="admin-form-label">API Key</label>
                <input type="text" class="admin-form-input" id="amo-api-key"
                       value="<?= Html::encode($apiKey) ?>"
                       placeholder="Нажмите «Сгенерировать» или вставьте свой ключ" readonly>
            </div>
            <button class="admin-btn admin-btn-secondary admin-btn-sm" onclick="generateApiKey()" title="Сгенерировать новый ключ">
                <i class="bi bi-arrow-repeat"></i> Сгенерировать
            </button>
            <button class="admin-btn admin-btn-secondary admin-btn-sm" onclick="copyToClipboard('amo-api-key')" title="Скопировать">
                <i class="bi bi-clipboard"></i>
            </button>
        </div>
        <button class="admin-btn admin-btn-primary" onclick="saveApiKey()">
            <i class="bi bi-save"></i> Сохранить ключ
        </button>
        <span id="amo-key-msg" style="margin-left:10px;font-size:13px"></span>
    </div>
</div>

<!-- 2. Настройки OAuth (существующие) -->
<div class="admin-card" style="max-width:640px;margin-bottom:20px">
    <div class="admin-card-header">
        <h2 class="admin-card-title"><i class="bi bi-shield-lock"></i> Настройки OAuth (для двусторонней синхронизации)</h2>
    </div>
    <div class="admin-card-body">
        <div class="sh-row" style="margin-bottom:10px">
            <label class="admin-form-label">Домен AmoCRM</label>
            <input type="text" class="admin-form-input" id="amocrm-domain" placeholder="example.amocrm.ru"
                   value="<?= Html::encode($domain) ?>">
        </div>
        <div class="sh-row" style="margin-bottom:10px">
            <label class="admin-form-label">Client ID</label>
            <input type="text" class="admin-form-input" id="amocrm-client-id" placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"
                   value="<?= Html::encode(Yii::$app->settings->get('amocrm', 'client_id', '')) ?>">
        </div>
        <div class="sh-row" style="margin-bottom:10px">
            <label class="admin-form-label">Client Secret</label>
            <input type="password" class="admin-form-input" id="amocrm-secret" placeholder="••••••••••••••••">
        </div>
        <div class="sh-row" style="margin-bottom:12px">
            <label class="admin-form-label">Access Token</label>
            <textarea class="admin-form-input" rows="2" id="amocrm-token" placeholder="Получите токен после авторизации через OAuth"></textarea>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            <button class="admin-btn admin-btn-secondary" onclick="testAmoCRMConn()">
                <i class="bi bi-check-circle"></i> Проверить
            </button>
            <button class="admin-btn admin-btn-primary" onclick="saveAmoCRMConn()">
                <i class="bi bi-save"></i> Сохранить OAuth
            </button>
        </div>
        <div id="amo-oauth-msg" style="margin-top:10px;font-size:13px"></div>
    </div>
</div>

<!-- 3. Код для встраивания виджета -->
<div class="admin-card" style="max-width:640px;margin-bottom:20px">
    <div class="admin-card-header">
        <h2 class="admin-card-title"><i class="bi bi-code-slash"></i> Код виджета для AmoCRM</h2>
    </div>
    <div class="admin-card-body">
        <p style="font-size:13px;color:var(--admin-text-secondary);margin:0 0 10px">
            Вставьте этот код в настройках виджета AmoCRM (карточка сделки → виджет → HTML-код).
        </p>
        <div style="position:relative">
<textarea class="admin-form-input" rows="6" id="amo-embed-code" readonly style="font-family:monospace;font-size:12px;background:var(--admin-bg);resize:none">
<div id="sneakerhead-widget-container"></div>
<script>
window.SNEAKERHEAD_API_URL = '<?= $siteUrl ?>';
window.SNEAKERHEAD_API_KEY = '<?= Html::encode($apiKey) ?>';
</script>
<script src="<?= $siteUrl ?>/amocrm-widget/widget.js"></script>
</textarea>
            <button class="admin-btn admin-btn-secondary admin-btn-sm" style="position:absolute;top:8px;right:8px" onclick="copyToClipboard('amo-embed-code')">
                <i class="bi bi-clipboard"></i> Копировать
            </button>
        </div>
    </div>
</div>

<!-- 4. Webhook URL -->
<div class="admin-card" style="max-width:640px;margin-bottom:20px">
    <div class="admin-card-header">
        <h2 class="admin-card-title"><i class="bi bi-link-45deg"></i> Webhook URL</h2>
    </div>
    <div class="admin-card-body">
        <p style="font-size:13px;color:var(--admin-text-secondary);margin:0 0 10px">
            Укажите этот URL в настройках AmoCRM (Настройки → Интеграции → Webhook) для получения уведомлений о событиях.
        </p>
        <div style="display:flex;gap:8px;align-items:center">
            <input type="text" class="admin-form-input" id="amo-webhook-url"
                   value="<?= Html::encode($siteUrl . '/api/amocrm/create-order') ?>" readonly style="font-family:monospace;font-size:12px">
            <button class="admin-btn admin-btn-secondary admin-btn-sm" onclick="copyToClipboard('amo-webhook-url')">
                <i class="bi bi-clipboard"></i>
            </button>
        </div>
    </div>
</div>

<!-- 5. Последние заказы из AmoCRM -->
<div class="admin-card" style="margin-bottom:20px">
    <div class="admin-card-header" style="display:flex;justify-content:space-between;align-items:center">
        <h2 class="admin-card-title"><i class="bi bi-list-ul"></i> Последние заказы из AmoCRM</h2>
        <span class="admin-badge admin-badge-primary"><?= count($recentOrders) ?></span>
    </div>
    <div class="admin-card-body" style="padding:0">
        <?php if (empty($recentOrders)): ?>
            <div style="padding:32px;text-align:center;color:var(--admin-text-secondary)">
                <i class="bi bi-inbox" style="font-size:32px;display:block;margin-bottom:8px;opacity:.5"></i>
                <p style="margin:0">Заказов из AmoCRM пока нет</p>
            </div>
        <?php else: ?>
            <table class="admin-table" style="margin:0">
                <thead>
                    <tr>
                        <th>Заказ</th>
                        <th>Клиент</th>
                        <th>Телефон</th>
                        <th>Сумма</th>
                        <th>Статус</th>
                        <th>Дата</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentOrders as $order): ?>
                        <tr>
                            <td><strong>#<?= Html::encode($order->order_number) ?></strong></td>
                            <td><?= Html::encode($order->client_name) ?></td>
                            <td><?= Html::encode($order->client_phone) ?></td>
                            <td><?= number_format($order->total_amount, 2) ?> BYN</td>
                            <td><span class="admin-badge admin-badge-secondary"><?= Html::encode($order->getStatusLabel()) ?></span></td>
                            <td style="font-size:12px;color:var(--admin-text-secondary)"><?= Yii::$app->formatter->asRelativeTime($order->created_at) ?></td>
                            <td>
                                <a href="<?= Url::to(['/admin/order/' . $order->id]) ?>" class="admin-btn admin-btn-secondary admin-btn-sm" title="Открыть заказ">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

function generateApiKey() {
    var chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    var key = '';
    for (var i = 0; i < 48; i++) key += chars.charAt(Math.floor(Math.random() * chars.length));
    document.getElementById('amo-api-key').value = key;
}

function saveApiKey() {
    var key = document.getElementById('amo-api-key').value.trim();
    if (!key) { msg('amo-key-msg', 'Введите или сгенерируйте ключ', '#b45309'); return; }

    fetch('<?= Url::to(['/admin/settings/save']) ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken},
        body: JSON.stringify({amocrm: {api_key: key}})
    })
    .then(r => r.json())
    .then(d => {
        msg('amo-key-msg', d.success ? '✓ Ключ сохранён' : '✗ ' + d.message, d.success ? '#065f46' : '#991b1b');
        if (d.success) updateEmbedCode(key);
    })
    .catch(() => msg('amo-key-msg', '✗ Ошибка сети', '#991b1b'));
}

function updateEmbedCode(key) {
    var code = '<div id="sneakerhead-widget-container"></div>\n' +
        '<script>\n' +
        "window.SNEAKERHEAD_API_URL = '<?= $siteUrl ?>';\n" +
        "window.SNEAKERHEAD_API_KEY = '" + key + "';\n" +
        '</sc' + 'ript>\n' +
        '<script src="<?= $siteUrl ?>/amocrm-widget/widget.js"></sc' + 'ript>';
    document.getElementById('amo-embed-code').value = code;
}

function testAmoCRMConn() {
    var domain = document.getElementById('amocrm-domain').value;
    var token  = document.getElementById('amocrm-token').value;
    if (!domain || !token) { msg('amo-oauth-msg', '⚠ Заполните домен и токен', '#b45309'); return; }
    msg('amo-oauth-msg', 'Проверяем...', '#6b7280');
    fetch('<?= Url::to(['/admin/settings/test-amocrm']) ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken},
        body: JSON.stringify({domain, api_token: token})
    })
    .then(r => r.json())
    .then(d => {
        msg('amo-oauth-msg', d.success ? '✓ ' + (d.message || 'Подключение успешно') : '✗ ' + (d.message || 'Ошибка'), d.success ? '#065f46' : '#991b1b');
        var badge = document.getElementById('amocrm-status-badge');
        badge.textContent = d.success ? 'Подключено' : 'Не подключено';
        badge.className = 'admin-badge ' + (d.success ? 'admin-badge-success' : 'admin-badge-secondary');
    })
    .catch(() => msg('amo-oauth-msg', '✗ Ошибка сети', '#991b1b'));
}

function saveAmoCRMConn() {
    var data = {
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
    .then(d => msg('amo-oauth-msg', d.success ? '✓ Настройки сохранены' : '✗ ' + d.message, d.success ? '#065f46' : '#991b1b'))
    .catch(() => msg('amo-oauth-msg', '✗ Ошибка сети', '#991b1b'));
}

function copyToClipboard(id) {
    var el = document.getElementById(id);
    if (!el) return;
    var text = el.value || el.textContent;
    navigator.clipboard.writeText(text).then(() => {
        var old = el.style.borderColor;
        el.style.borderColor = '#22c55e';
        setTimeout(() => { el.style.borderColor = old; }, 800);
    });
}

function msg(id, text, color) {
    var el = document.getElementById(id);
    if (el) { el.textContent = text; el.style.color = color; }
}
</script>
