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

<?php $this->params['headerActions'] = [
    Html::a('<i class="bi bi-arrow-left"></i> Все плагины', ['/admin/plugin'], ['class' => 'admin-btn admin-btn-secondary admin-btn-sm']),
    '<span class="admin-badge ' . ($apiKey ? 'admin-badge-success' : 'admin-badge-secondary') . '" id="amocrm-status-badge">' . ($apiKey ? 'Активно' : 'Не настроено') . '</span>'
];
?>

<!-- 1. API-ключ -->
<div class="admin-card" style="margin-bottom:20px">
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
                       placeholder="Введите API-ключ"
                       style="text-overflow:ellipsis" readonly>
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
<div class="admin-card" style="margin-bottom:20px">
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
<div class="admin-card" style="margin-bottom:20px">
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
<div class="admin-card" style="margin-bottom:20px">
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

<?php // ═══════════════════════════════════════════════════════════════════
// TAB: ЛОГИ
// ════════════════════════════════════════════════════════════════════════
elseif ($tab === 'logs'): ?>

<div class="amo-tab-pane">
    <div class="amo-logs-toolbar mb-3">
        <div class="d-flex gap-2 flex-wrap align-items-center">
            <select class="admin-form-input" id="amo-log-status" style="width:auto" onchange="amoLoadLogs(1)">
                <option value="">Все статусы</option>
                <option value="ok">ok</option>
                <option value="fail">fail</option>
            </select>
            <button class="admin-btn admin-btn-sm admin-btn-outline" onclick="amoLoadLogs(1)">
                <i class="bi bi-arrow-clockwise"></i> Обновить
            </button>
        </div>
        <span class="text-muted-sm" id="amo-log-total"></span>
    </div>

    <div class="admin-card">
        <div class="admin-card-body p-0">
            <table class="amo-log-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Направление</th>
                        <th>Событие</th>
                        <th>Статус</th>
                        <th>Время (мс)</th>
                        <th>Дата</th>
                    </tr>
                </thead>
                <tbody id="amo-log-tbody">
                    <tr><td colspan="6" class="text-center text-muted-sm py-3">Загрузка...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
    <div class="d-flex gap-2 mt-3" id="amo-log-pager"></div>
</div>

<?php // ═══════════════════════════════════════════════════════════════════
// TAB: СТАТИСТИКА
// ════════════════════════════════════════════════════════════════════════
elseif ($tab === 'stats'): ?>

<div class="amo-tab-pane">
    <div class="amo-stats-grid" id="amo-stats-grid">
        <div class="amo-stat-card"><div class="amo-stat-val" id="amo-s-total">—</div><div class="amo-stat-label">Всего запросов</div></div>
        <div class="amo-stat-card text-success"><div class="amo-stat-val" id="amo-s-ok">—</div><div class="amo-stat-label">Успешных</div></div>
        <div class="amo-stat-card text-danger"><div class="amo-stat-val" id="amo-s-fail">—</div><div class="amo-stat-label">Ошибок</div></div>
        <div class="amo-stat-card"><div class="amo-stat-val" id="amo-s-ms">—</div><div class="amo-stat-label">Среднее время (мс)</div></div>
        <div class="amo-stat-card text-primary"><div class="amo-stat-val" id="amo-s-synced">—</div><div class="amo-stat-label">Заказов в AmoCRM</div></div>
    </div>

    <div class="admin-card mt-4">
        <div class="admin-card-header">
            <h2 class="admin-card-title">Активность за 14 дней</h2>
        </div>
        <div class="admin-card-body">
            <div id="amo-chart-wrap" style="height:160px;display:flex;align-items:flex-end;gap:4px">
                <span class="text-muted-sm">Загрузка...</span>
            </div>
        </div>
    </div>
</div>

<?php endif; ?>


<!-- Styles -->
<style>
.amo-tabs-nav{display:flex;gap:4px;border-bottom:2px solid var(--admin-border);padding-bottom:2px}
.amo-tab-btn{padding:8px 16px;border-radius:6px 6px 0 0;border:none;background:none;color:var(--admin-text-muted);cursor:pointer;font-size:13px;font-weight:500;display:flex;align-items:center;gap:6px;text-decoration:none;transition:color .15s,background .15s}
.amo-tab-btn:hover{color:var(--admin-text);background:var(--admin-hover)}
.amo-tab-btn.active{color:var(--admin-accent);background:var(--admin-accent-bg,rgba(0,128,96,.08));border-bottom:2px solid var(--admin-accent);margin-bottom:-2px}
.amo-settings-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px}
@media(max-width:900px){.amo-settings-grid{grid-template-columns:1fr}}
.amo-token-info{font-size:12px;color:var(--admin-text-muted);display:flex;align-items:center;gap:6px}
.amo-toggle-label{display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px}
.amo-toggle-label input[type=checkbox]{width:16px;height:16px}
.amo-webhook-url{display:flex;align-items:center;gap:8px;background:var(--admin-surface-2,#f8f9fa);border:1px solid var(--admin-border);border-radius:6px;padding:10px 12px}
.amo-webhook-url code{flex:1;font-size:12px;word-break:break-all;color:var(--admin-text)}
.amo-section-subtitle{font-size:13px;font-weight:600;color:var(--admin-text-muted);margin-bottom:8px;text-transform:uppercase;letter-spacing:.04em}
.amo-wh-table{width:100%;border-collapse:collapse;font-size:13px}
.amo-wh-table th,.amo-wh-table td{padding:7px 10px;border-bottom:1px solid var(--admin-border);text-align:left}
.amo-wh-table th{font-weight:600;color:var(--admin-text-muted)}
.amo-sync-meta{font-size:12px;color:var(--admin-text-muted);display:flex;align-items:center;gap:6px}
.amo-logs-toolbar{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px}
.amo-log-table{width:100%;border-collapse:collapse;font-size:12px}
.amo-log-table th,.amo-log-table td{padding:7px 12px;border-bottom:1px solid var(--admin-border);white-space:nowrap}
.amo-log-table th{font-weight:600;color:var(--admin-text-muted);background:var(--admin-surface-2,#f8f9fa)}
.amo-log-table .status-ok{color:#065f46;font-weight:600}
.amo-log-table .status-fail{color:#991b1b;font-weight:600}
.amo-stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:12px}
.amo-stat-card{background:var(--admin-card-bg,#fff);border:1px solid var(--admin-border);border-radius:8px;padding:16px;text-align:center}
.amo-stat-val{font-size:28px;font-weight:700;line-height:1.1;margin-bottom:4px}
.amo-stat-label{font-size:11px;color:var(--admin-text-muted);text-transform:uppercase;letter-spacing:.04em}
.amo-bar{background:var(--admin-accent,#008060);border-radius:3px 3px 0 0;min-width:14px;position:relative;transition:height .3s}
.amo-bar:hover::after{content:attr(data-cnt);position:absolute;top:-22px;left:50%;transform:translateX(-50%);background:#333;color:#fff;font-size:10px;padding:1px 5px;border-radius:3px;white-space:nowrap}
.font-mono{font-family:monospace}
</style>

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

function showResult(elId, d) {
    var el = document.getElementById(elId);
    if (!el) return;
    el.textContent = (d.success ? '✓ ' : '✗ ') + (d.message || '');
    el.style.color = d.success ? '#065f46' : '#991b1b';
}

window.amoSave = function() {
    post('/admin/plugin/amocrm/save', {
        domain:        (document.getElementById('amo-domain') || {}).value,
        client_id:     (document.getElementById('amo-client-id') || {}).value,
        client_secret: (document.getElementById('amo-client-secret') || {}).value || undefined,
    }, function(d){ showResult('amo-save-result', d); });
};

window.amoSavePipeline = function() {
    post('/admin/plugin/amocrm/save', {
        pipeline_id:         (document.getElementById('amo-pipeline-id') || {}).value,
        responsible_user_id: (document.getElementById('amo-responsible-id') || {}).value,
        new_order_status_id: (document.getElementById('amo-new-status-id') || {}).value,
        paid_status_id:      (document.getElementById('amo-paid-status-id') || {}).value,
        auto_create_lead:    (document.getElementById('amo-auto-create') || {}).checked ? '1' : '0',
        auto_sync:           (document.getElementById('amo-auto-sync') || {}).checked ? '1' : '0',
    }, function(d){ showResult('amo-pipeline-result', d); });
};

window.amoTest = function() {
    var el = document.getElementById('amo-save-result');
    if (el) { el.style.color='#6b7280'; el.textContent='Проверяем...'; }
    get('/admin/plugin/amocrm/test', function(d){
        showResult('amo-save-result', d);
        var badge = document.getElementById('amo-status-badge');
        if (badge) {
            badge.textContent = d.success ? '✓ Подключено' : '✗ Не подключено';
            badge.className = 'admin-badge ' + (d.success ? 'admin-badge-success' : 'admin-badge-secondary');
        }
    });
};

window.amoSyncBatch = function() {
    var btn = document.getElementById('amo-sync-btn');
    var res = document.getElementById('amo-sync-result');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Синхронизация...';
    if (res) { res.style.color='#6b7280'; res.textContent='Отправляем...'; }
    post('/admin/plugin/amocrm/sync', {
        status: (document.getElementById('amo-sync-status') || {}).value,
        limit:  parseInt((document.getElementById('amo-sync-limit') || {}).value) || 50,
    }, function(d){
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-arrow-repeat"></i> Синхронизировать';
        showResult('amo-sync-result', d);
    });
};

window.amoSyncSingle = function() {
    var id = parseInt((document.getElementById('amo-single-id') || {}).value);
    if (!id) return;
    var res = document.getElementById('amo-single-result');
    if (res) { res.style.color='#6b7280'; res.textContent='Отправляем...'; }
    post('/admin/plugin/amocrm/sync', {order_id: id}, function(d){ showResult('amo-single-result', d); });
};

window.amoWHCopy = function() {
    var text = (document.getElementById('amo-wh-url') || {}).textContent || '';
    navigator.clipboard.writeText(text).then(function(){
        var msg = document.getElementById('amo-wh-copy-msg');
        if (msg) { msg.style.display=''; setTimeout(function(){msg.style.display='none';}, 1500); }
    });
};

window.amoWHTest = function() {
    var el = document.getElementById('amo-wh-test-result');
    if (el) { el.style.color='#6b7280'; el.textContent='Отправляем тестовый POST...'; }
    post('/webhook/amocrm/event', {_test: 1}, function(d){
        if (el) { el.style.color='#065f46'; el.textContent='✓ Webhook доступен'; }
    });
};

/* ── Logs tab ── */
var _logPage = 1;
window.amoLoadLogs = function(page) {
    _logPage = page || _logPage;
    var st = (document.getElementById('amo-log-status') || {}).value || '';
    var tbody = document.getElementById('amo-log-tbody');
    var totalEl = document.getElementById('amo-log-total');
    if (tbody) tbody.innerHTML = '<tr><td colspan="6" class="text-center py-3 text-muted-sm">Загрузка...</td></tr>';
    get('/admin/plugin/amocrm/logs?page=' + _logPage + '&status=' + encodeURIComponent(st), function(d) {
        if (!tbody) return;
        if (!d.rows || !d.rows.length) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center py-3 text-muted-sm">Нет записей</td></tr>';
            return;
        }
        if (totalEl) totalEl.textContent = 'Всего: ' + d.total;
        tbody.innerHTML = d.rows.map(function(r) {
            var dt = r.created_at ? new Date(r.created_at * 1000).toLocaleString('ru') : '';
            return '<tr>'
                + '<td>' + r.id + '</td>'
                + '<td>' + r.direction + '</td>'
                + '<td style="max-width:300px;overflow:hidden;text-overflow:ellipsis">' + (r.event || '') + '</td>'
                + '<td class="status-' + r.status + '">' + r.status + '</td>'
                + '<td>' + (r.response_ms || '—') + '</td>'
                + '<td>' + dt + '</td>'
                + '</tr>';
        }).join('');
        renderPager(d.total, _logPage, 50);
    });
};

function renderPager(total, page, limit) {
    var pager = document.getElementById('amo-log-pager');
    if (!pager) return;
    var pages = Math.ceil(total / limit);
    if (pages <= 1) { pager.innerHTML = ''; return; }
    var html = '';
    for (var i = 1; i <= pages; i++) {
        html += '<button class="admin-btn admin-btn-sm ' + (i === page ? 'admin-btn-primary' : 'admin-btn-outline') + '" onclick="amoLoadLogs(' + i + ')">' + i + '</button>';
    }
    pager.innerHTML = html;
}

/* ── Stats tab ── */
function loadStats() {
    get('/admin/plugin/amocrm/stats', function(d) {
        if (!d.success) return;
        var set = function(id, val) { var el = document.getElementById(id); if (el) el.textContent = val; };
        set('amo-s-total',  d.total);
        set('amo-s-ok',     d.ok);
        set('amo-s-fail',   d.fail);
        set('amo-s-ms',     d.avg_ms || '—');
        set('amo-s-synced', d.synced_orders);
        renderChart(d.by_day || []);
    });
}

function renderChart(byDay) {
    var wrap = document.getElementById('amo-chart-wrap');
    if (!wrap || !byDay.length) { if (wrap) wrap.innerHTML = '<span class="text-muted-sm">Нет данных</span>'; return; }
    var max = Math.max.apply(null, byDay.map(function(x){return parseInt(x.cnt)||0;})) || 1;
    var h = 150;
    wrap.innerHTML = byDay.map(function(x) {
        var pct = Math.round(((parseInt(x.cnt)||0) / max) * h);
        return '<div title="' + x.d + ': ' + x.cnt + '" style="display:flex;flex-direction:column;align-items:center;gap:2px">'
            + '<div class="amo-bar" data-cnt="' + x.cnt + '" style="height:' + pct + 'px"></div>'
            + '<div style="font-size:9px;color:var(--admin-text-muted)">' + (x.d || '').slice(5) + '</div>'
            + '</div>';
    }).join('');
}

/* Auto-init current tab */
var curTab = '<?= $tab ?>';
if (curTab === 'logs') amoLoadLogs(1);
if (curTab === 'stats') loadStats();

})();
</script>
