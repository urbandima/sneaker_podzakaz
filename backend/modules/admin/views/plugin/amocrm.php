<?php
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'AmoCRM — интеграция';

$tokenExpiresAt  = (int)($settings['token_expires_at'] ?? 0);
$tokenExpired    = $tokenExpiresAt > 0 && $tokenExpiresAt < time();
$tokenValid      = $isConfigured && !$tokenExpired;
$lastSyncAt      = (int)($settings['last_sync_at'] ?? 0);
$webhookBase     = Yii::$app->urlManager->createAbsoluteUrl('/');
?>

<?php $this->params['headerActions'] = [
    Html::a('<i class="bi bi-arrow-left"></i> Все плагины', ['/admin/plugin'], ['class' => 'admin-btn admin-btn-secondary admin-btn-sm']),
    '<span class="admin-badge ' . ($tokenValid ? 'admin-badge-success' : 'admin-badge-secondary') . '" id="amo-status-badge">'
        . ($tokenValid ? '<i class="bi bi-check-circle-fill"></i> Подключено' : '<i class="bi bi-x-circle"></i> Не подключено')
        . '</span>',
]; ?>

<?php if (Yii::$app->session->hasFlash('success')): ?>
    <div class="admin-alert admin-alert-success mb-3"><?= Html::encode(Yii::$app->session->getFlash('success')) ?></div>
<?php endif; ?>
<?php if (Yii::$app->session->hasFlash('error')): ?>
    <div class="admin-alert admin-alert-danger mb-3"><?= Html::encode(Yii::$app->session->getFlash('error')) ?></div>
<?php endif; ?>

<!-- Tab nav -->
<div class="amo-tabs-nav mb-4">
    <?php foreach ([
        'settings'      => ['icon' => 'gear',           'label' => 'Настройки'],
        'sync'          => ['icon' => 'arrow-repeat',   'label' => 'Синхронизация'],
        'webhooks'      => ['icon' => 'broadcast',      'label' => 'Webhooks'],
        'logs'          => ['icon' => 'list-columns',   'label' => 'Логи'],
        'stats'         => ['icon' => 'bar-chart-line', 'label' => 'Статистика'],
    ] as $key => $meta): ?>
        <a href="?tab=<?= $key ?>"
           class="amo-tab-btn<?= $tab === $key ? ' active' : '' ?>">
            <i class="bi bi-<?= $meta['icon'] ?>"></i>
            <?= $meta['label'] ?>
        </a>
    <?php endforeach; ?>
</div>

<?php // ═══════════════════════════════════════════════════════════════════
// TAB: НАСТРОЙКИ
// ════════════════════════════════════════════════════════════════════════
if ($tab === 'settings'): ?>

<div class="amo-tab-pane amo-settings-grid">

    <!-- OAuth connection card -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2 class="admin-card-title"><i class="bi bi-key text-accent"></i> OAuth подключение</h2>
        </div>
        <div class="admin-card-body">
            <div class="form-group">
                <label class="admin-label">Домен AmoCRM</label>
                <input type="text" class="admin-form-input" id="amo-domain"
                       placeholder="example.amocrm.ru"
                       value="<?= Html::encode($settings['domain']) ?>">
                <small class="text-muted-sm">Без https:// и слеша в конце</small>
            </div>
            <div class="form-group">
                <label class="admin-label">Client ID</label>
                <input type="text" class="admin-form-input font-mono" id="amo-client-id"
                       placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"
                       value="<?= Html::encode($settings['client_id']) ?>">
            </div>
            <div class="form-group">
                <label class="admin-label">Client Secret</label>
                <input type="password" class="admin-form-input font-mono" id="amo-client-secret"
                       placeholder="••••••••••••••••">
                <small class="text-muted-sm">Оставьте пустым, если не меняется</small>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <button class="admin-btn admin-btn-secondary" onclick="amoSave()">
                    <i class="bi bi-save"></i> Сохранить
                </button>
                <a href="<?= Url::to(['/admin/plugin/amocrm/authorize']) ?>"
                   class="admin-btn admin-btn-primary">
                    <i class="bi bi-box-arrow-up-right"></i> Авторизовать через OAuth
                </a>
                <button class="admin-btn admin-btn-outline" onclick="amoTest()">
                    <i class="bi bi-check2-circle"></i> Проверить
                </button>
            </div>
            <div id="amo-save-result" class="mt-10px fs-xs"></div>

            <?php if ($tokenExpiresAt > 0): ?>
                <div class="amo-token-info mt-3">
                    <i class="bi bi-<?= $tokenValid ? 'shield-check text-success' : 'shield-x text-danger' ?>"></i>
                    Токен <?= $tokenValid ? 'активен до ' . date('d.m.Y H:i', $tokenExpiresAt) : 'истёк ' . date('d.m.Y', $tokenExpiresAt) ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Pipeline / mapping card -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2 class="admin-card-title"><i class="bi bi-diagram-3 text-accent"></i> Воронка и маппинг</h2>
        </div>
        <div class="admin-card-body">
            <div class="form-group">
                <label class="admin-label">ID воронки</label>
                <input type="number" class="admin-form-input" id="amo-pipeline-id"
                       value="<?= Html::encode($settings['pipeline_id']) ?>"
                       placeholder="0">
                <small class="text-muted-sm">Найдите в разделе «Сделки → Настройки воронки»</small>
            </div>
            <div class="form-group">
                <label class="admin-label">Ответственный (user ID)</label>
                <input type="number" class="admin-form-input" id="amo-responsible-id"
                       value="<?= Html::encode($settings['responsible_user_id']) ?>"
                       placeholder="0">
            </div>
            <div class="form-group">
                <label class="admin-label">Статус «Новый заказ» (status ID)</label>
                <input type="number" class="admin-form-input" id="amo-new-status-id"
                       value="<?= Html::encode($settings['new_order_status_id']) ?>"
                       placeholder="0">
            </div>
            <div class="form-group">
                <label class="admin-label">Статус «Оплачен» (status ID)</label>
                <input type="number" class="admin-form-input" id="amo-paid-status-id"
                       value="<?= Html::encode($settings['paid_status_id']) ?>"
                       placeholder="0">
            </div>
            <div class="form-group d-flex gap-4">
                <label class="amo-toggle-label">
                    <input type="checkbox" id="amo-auto-create"
                           <?= $settings['auto_create_lead'] === '1' ? 'checked' : '' ?>>
                    <span>Автосоздание сделки при новом заказе</span>
                </label>
            </div>
            <div class="form-group d-flex gap-4">
                <label class="amo-toggle-label">
                    <input type="checkbox" id="amo-auto-sync"
                           <?= $settings['auto_sync'] === '1' ? 'checked' : '' ?>>
                    <span>Авто-синхронизация статусов</span>
                </label>
            </div>
            <button class="admin-btn admin-btn-secondary" onclick="amoSavePipeline()">
                <i class="bi bi-save"></i> Сохранить маппинг
            </button>
            <div id="amo-pipeline-result" class="mt-10px fs-xs"></div>
        </div>
    </div>

</div>

<?php // ═══════════════════════════════════════════════════════════════════
// TAB: СИНХРОНИЗАЦИЯ
// ════════════════════════════════════════════════════════════════════════
elseif ($tab === 'sync'): ?>

<div class="amo-tab-pane">
    <div class="admin-card" style="max-width:640px">
        <div class="admin-card-header">
            <h2 class="admin-card-title"><i class="bi bi-arrow-repeat text-accent"></i> Массовая синхронизация</h2>
        </div>
        <div class="admin-card-body">
            <p class="text-muted-sm mb-3">Синхронизирует заказы без привязанной сделки AmoCRM (поле <code>amocrm_lead_id IS NULL</code>).</p>
            <div class="d-flex gap-3 flex-wrap mb-3">
                <div class="form-group" style="flex:1;min-width:140px">
                    <label class="admin-label">Статус заказа</label>
                    <select class="admin-form-input" id="amo-sync-status">
                        <option value="">— Все —</option>
                        <option value="new">Новый</option>
                        <option value="paid">Оплачен</option>
                        <option value="processing">В обработке</option>
                        <option value="shipped">Отправлен</option>
                        <option value="delivered">Доставлен</option>
                    </select>
                </div>
                <div class="form-group" style="flex:1;min-width:100px">
                    <label class="admin-label">Лимит</label>
                    <input type="number" class="admin-form-input" id="amo-sync-limit" value="50" min="1" max="200">
                </div>
            </div>
            <button class="admin-btn admin-btn-primary" id="amo-sync-btn" onclick="amoSyncBatch()">
                <i class="bi bi-arrow-repeat"></i> Синхронизировать
            </button>
            <div id="amo-sync-result" class="mt-10px fs-xs"></div>
        </div>
    </div>

    <div class="admin-card mt-3" style="max-width:640px">
        <div class="admin-card-header">
            <h2 class="admin-card-title"><i class="bi bi-123 text-accent"></i> Разовая синхронизация</h2>
        </div>
        <div class="admin-card-body">
            <div class="d-flex gap-2">
                <input type="number" class="admin-form-input" id="amo-single-id" placeholder="ID заказа" style="max-width:180px">
                <button class="admin-btn admin-btn-secondary" onclick="amoSyncSingle()">
                    <i class="bi bi-box-arrow-up-right"></i> Отправить
                </button>
            </div>
            <div id="amo-single-result" class="mt-10px fs-xs"></div>
        </div>
    </div>

    <?php if ($lastSyncAt > 0): ?>
    <div class="amo-sync-meta mt-3">
        <i class="bi bi-clock-history"></i>
        Последняя синхронизация: <?= date('d.m.Y H:i:s', $lastSyncAt) ?>
        — синхронизировано <?= Html::encode($settings['last_sync_count'] ?? '0') ?> заказов
    </div>
    <?php endif; ?>
</div>

<?php // ═══════════════════════════════════════════════════════════════════
// TAB: WEBHOOKS
// ════════════════════════════════════════════════════════════════════════
elseif ($tab === 'webhooks'): ?>

<div class="amo-tab-pane" style="max-width:720px">
    <div class="admin-card">
        <div class="admin-card-header">
            <h2 class="admin-card-title"><i class="bi bi-broadcast text-accent"></i> Входящие webhooks от AmoCRM</h2>
        </div>
        <div class="admin-card-body">
            <p class="text-muted-sm mb-3">
                Укажите этот URL в настройках AmoCRM (Настройки → Webhooks), чтобы получать события об изменении сделок.
            </p>
            <div class="amo-webhook-url">
                <code id="amo-wh-url"><?= Html::encode(rtrim($webhookBase, '/')) ?>/webhook/amocrm/event</code>
                <button class="admin-btn admin-btn-sm admin-btn-outline" onclick="amoWHCopy()">
                    <i class="bi bi-clipboard"></i>
                </button>
            </div>
            <div id="amo-wh-copy-msg" class="fs-xs text-success mt-1" style="display:none">Скопировано!</div>

            <h3 class="amo-section-subtitle mt-4">Поддерживаемые события</h3>
            <table class="amo-wh-table">
                <thead>
                    <tr><th>Событие AmoCRM</th><th>Действие в магазине</th></tr>
                </thead>
                <tbody>
                    <tr><td><code>leads[status]</code></td><td>Обновление статуса заказа</td></tr>
                    <tr><td><code>leads[add]</code></td><td>Создание ссылки сделка → заказ</td></tr>
                    <tr><td><code>leads[delete]</code></td><td>Сброс <code>amocrm_lead_id</code></td></tr>
                    <tr><td><code>contacts[add]</code></td><td>Лог события</td></tr>
                </tbody>
            </table>

            <h3 class="amo-section-subtitle mt-4">Тест входящего webhook</h3>
            <button class="admin-btn admin-btn-outline admin-btn-sm" onclick="amoWHTest()">
                <i class="bi bi-play-circle"></i> Отправить тестовый POST
            </button>
            <div id="amo-wh-test-result" class="mt-10px fs-xs"></div>
        </div>
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
(function(){
var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

function post(url, data, cb) {
    var btn = null;
    fetch(url, {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-Token': csrf},
        body: JSON.stringify(data)
    }).then(function(r){return r.json();}).then(cb).catch(function(){cb({success:false,message:'Ошибка сети'});});
}

function get(url, cb) {
    fetch(url, {headers:{'X-CSRF-Token': csrf}})
        .then(function(r){return r.json();}).then(cb).catch(function(){cb({success:false});});
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
