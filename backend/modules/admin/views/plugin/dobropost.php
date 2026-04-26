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

// Data passed from controller
$statusMappings = $statusMappings ?? [];
$proxyPhones    = $proxyPhones ?? [];

$internalStatuses = [
    ''                 => '— не менять —',
    'waiting'          => 'Ожидание',
    'received'         => 'Получен ДП',
    'processing'       => 'В обработке',
    'in_transit'       => 'В пути',
    'customs'          => 'На таможне',
    'customs_duty'     => 'Таможенный сбор',
    'customs_cleared'  => 'Таможня пройдена',
    'customs_rejected' => 'Таможня отклонена',
    'in_delivery'      => 'Доставляется',
    'arrived'          => 'Прибыл в РБ',
    'delivered'        => 'Доставлен',
    'returned'         => 'Возврат',
    'lost'             => 'Утерян',
    'error'            => 'Ошибка',
    'edit_request'     => 'Запрос правки',
];
?>

<?php
$this->params['headerActions'] = [
    Html::a('<i class="bi bi-arrow-left"></i> Все плагины', ['/admin/plugin'], ['class' => 'admin-btn admin-btn-secondary admin-btn-sm']),
    '<span class="admin-badge ' . ($connected ? 'admin-badge-success' : 'admin-badge-secondary') . '" id="dp-status-badge">' . ($connected ? 'Подключено' : 'Не подключено') . '</span>'
];
?>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(400px,1fr));gap:24px;margin-bottom:24px">

    <div class="admin-card">
        <div class="admin-card-header">
            <h2 class="admin-card-title"><i class="bi bi-key"></i> Авторизация API</h2>
        </div>
        <div class="admin-card-body">
            <div class="admin-form-group">
                <label class="admin-form-label">Email (логин API)</label>
                <input type="email" class="admin-form-input" id="dp-email"
                       placeholder="your@email.com"
                       value="<?= htmlspecialchars($dpEmail) ?>">
            </div>
            <div class="admin-form-group">
                <label class="admin-form-label">Пароль API</label>
                <input type="password" class="admin-form-input" id="dp-password"
                       placeholder="••••••••••••••••">
                <small class="text-muted-sm">
                    Установите в <code>.env</code>: <code>DP_API_PASSWORD=...</code>
                </small>
            </div>
            <div class="admin-form-group">
                <label class="admin-form-label">Тариф по умолчанию</label>
                <input type="number" class="admin-form-input" id="dp-tariff"
                       placeholder="26"
                       value="<?= htmlspecialchars($dpTariff) ?>">
                <small class="text-muted-sm">Код тарифа Таможня:ДП (26 = стандарт)</small>
            </div>
            <button class="admin-btn admin-btn-secondary admin-btn-sm" onclick="testDPConn()">
                <i class="bi bi-check-circle"></i> Проверить подключение
            </button>
            <div id="dp-test-result" style="margin-top:10px;font-size:13px"></div>
        </div>
    </div>

    <div class="admin-card">
        <div class="admin-card-header">
            <h2 class="admin-card-title"><i class="bi bi-gear"></i> Настройки отправки</h2>
        </div>
        <div class="admin-card-body">
            <div class="admin-form-group">
                <label class="admin-form-label">Авто-отправка в Таможня:ДП</label>
                <select class="admin-form-input" id="dp-auto-send">
                    <?php foreach ($autoSendOptions as $val => $label): ?>
                        <option value="<?= $val ?>" <?= $autoSend === $val ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="admin-form-group">
                <label class="admin-form-label">Webhook URL</label>
                <div style="display:flex;gap:8px;align-items:center">
                    <input type="text" class="admin-form-input"
                           value="<?= htmlspecialchars(\yii\helpers\Url::to(['/api/webhook/dobropost'], true)) ?>"
                           readonly id="dp-webhook-url">
                    <button class="admin-btn admin-btn-secondary admin-btn-sm" type="button" title="Скопировать"
                            onclick="navigator.clipboard.writeText(document.getElementById('dp-webhook-url').value).then(()=>this.innerHTML='<i class=\'bi bi-check\'></i>')">
                        <i class="bi bi-clipboard"></i>
                    </button>
                </div>
                <small class="text-muted-sm">
                    Укажите этот URL в настройках Таможня:ДП → Webhook
                </small>
            </div>
        </div>
    </div>

</div>

<!-- Единственная кнопка сохранения -->
<div class="admin-card">
    <div class="admin-card-body" style="display:flex;align-items:center;gap:16px;flex-wrap:wrap">
        <button class="admin-btn admin-btn-primary" onclick="saveAllDPSettings()">
            <i class="bi bi-save"></i> Сохранить все настройки
        </button>
        <div id="dp-save-result" style="font-size:13px"></div>
    </div>
</div>

<!-- Импорт шипментов из ДП -->
<div class="admin-card" style="margin-top:24px">
    <div class="admin-card-header">
        <h2 class="admin-card-title"><i class="bi bi-cloud-download"></i> Синхронизация шипментов</h2>
    </div>
    <div class="admin-card-body">
        <p style="font-size:13px;color:var(--admin-text-secondary);margin-bottom:16px">
            Загружает все шипменты из Таможня:ДП и привязывает их к заказам по трек-номеру Китая (<code>incomingDeclaration</code>).
            Обновляет статус доставки, DP-трек и дату статуса для уже привязанных заказов.
        </p>
        <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
            <button class="admin-btn admin-btn-secondary" onclick="importDP(this)" <?= $connected ? '' : 'disabled title="Сначала настройте подключение"' ?>>
                <i class="bi bi-arrow-repeat"></i> Импортировать шипменты из ДП
            </button>
            <div id="dp-import-result" style="font-size:13px"></div>
        </div>
    </div>
</div>

<!-- Прокси-телефоны -->
<div class="admin-card" style="margin-top:24px">
    <div class="admin-card-header">
        <h2 class="admin-card-title"><i class="bi bi-telephone-fill"></i> Прокси-телефоны для ДП</h2>
    </div>
    <div class="admin-card-body">
        <p style="font-size:13px;color:var(--admin-text-secondary);margin-bottom:12px">
            При отправке шипмента в ДП используется <strong>случайный</strong> телефон из этого списка — реальный телефон клиента не передаётся.
        </p>
        <div id="proxy-phones-list" style="display:flex;flex-direction:column;gap:8px;margin-bottom:12px">
            <?php foreach ($proxyPhones as $i => $p): ?>
            <div class="proxy-phone-row" style="display:flex;gap:8px;align-items:center">
                <input type="text" class="admin-form-input" style="flex:0 0 180px" placeholder="+375XX..." value="<?= Html::encode($p['phone'] ?? '') ?>">
                <input type="text" class="admin-form-input" style="flex:1" placeholder="Метка (напр. рабочий)" value="<?= Html::encode($p['label'] ?? '') ?>">
                <button type="button" class="admin-btn admin-btn-danger admin-btn-sm" onclick="this.closest('.proxy-phone-row').remove()">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
            <?php endforeach; ?>
        </div>
        <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
            <button type="button" class="admin-btn admin-btn-secondary admin-btn-sm" onclick="addProxyPhoneRow()">
                <i class="bi bi-plus-circle"></i> Добавить телефон
            </button>
            <button type="button" class="admin-btn admin-btn-primary admin-btn-sm" onclick="saveProxyPhones()">
                <i class="bi bi-save"></i> Сохранить телефоны
            </button>
            <span id="proxy-phones-result" style="font-size:13px"></span>
        </div>
    </div>
</div>

<!-- Маппинг статусов ДП -->
<div class="admin-card" style="margin-top:24px">
    <div class="admin-card-header" style="display:flex;justify-content:space-between;align-items:center">
        <h2 class="admin-card-title"><i class="bi bi-arrow-left-right"></i> Маппинг статусов Таможня:ДП</h2>
        <div style="display:flex;gap:10px;align-items:center">
            <button type="button" class="admin-btn admin-btn-primary admin-btn-sm" onclick="saveStatusMapping()">
                <i class="bi bi-save"></i> Сохранить маппинг
            </button>
            <span id="mapping-save-result" style="font-size:13px"></span>
        </div>
    </div>
    <div class="admin-card-body" style="padding:0;overflow-x:auto">
        <?php if (empty($statusMappings)): ?>
            <p style="padding:16px;color:var(--admin-text-secondary);font-size:13px">Нет данных. Убедитесь, что таблица delivery_status_mapping заполнена.</p>
        <?php else: ?>
        <table style="width:100%;border-collapse:collapse;font-size:13px">
            <thead>
                <tr style="background:var(--admin-surface-hover);border-bottom:2px solid var(--admin-border)">
                    <th style="padding:10px 12px;text-align:left;font-weight:600;white-space:nowrap">ID ДП</th>
                    <th style="padding:10px 12px;text-align:left;font-weight:600">Статус ДП</th>
                    <th style="padding:10px 12px;text-align:left;font-weight:600">→ Наш статус</th>
                    <th style="padding:10px 12px;text-align:left;font-weight:600;white-space:nowrap">Дней до</th>
                    <th style="padding:10px 12px;text-align:center;font-weight:600">Финал</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($statusMappings as $m): ?>
                <tr style="border-bottom:1px solid var(--admin-border)" data-id="<?= (int)$m['id'] ?>">
                    <td style="padding:8px 12px;color:var(--admin-text-secondary);font-family:monospace">
                        <?= Html::encode($m['provider_status_id']) ?>
                    </td>
                    <td style="padding:8px 12px">
                        <?= Html::encode($m['provider_status_name'] ?? '') ?>
                    </td>
                    <td style="padding:6px 12px">
                        <select class="admin-form-input mapping-internal-status" style="font-size:12px;padding:4px 8px;min-width:160px">
                            <?php foreach ($internalStatuses as $val => $label): ?>
                            <option value="<?= Html::encode($val) ?>" <?= ($m['internal_status'] ?? '') === $val ? 'selected' : '' ?>>
                                <?= Html::encode($label) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td style="padding:6px 12px">
                        <input type="number" class="admin-form-input mapping-days" style="font-size:12px;padding:4px 8px;width:70px"
                               placeholder="—" value="<?= $m['estimated_days'] !== null ? (int)$m['estimated_days'] : '' ?>">
                    </td>
                    <td style="padding:6px 12px;text-align:center">
                        <input type="checkbox" class="mapping-is-final" <?= !empty($m['is_final']) ? 'checked' : '' ?>>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<script>
const dpTestResult = document.getElementById('dp-test-result');
const dpSaveResult = document.getElementById('dp-save-result');
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

function testDPConn() {
    dpTestResult.textContent = 'Проверяем подключение...';
    dpTestResult.style.color = '#6b7280';
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
        dpTestResult.textContent = d.success ? ('✓ ' + (d.message || 'Подключение успешно')) : ('✗ ' + (d.message || 'Ошибка'));
        dpTestResult.style.color = d.success ? 'var(--admin-accent,#065f46)' : '#991b1b';
        const badge = document.getElementById('dp-status-badge');
        badge.textContent = d.success ? 'Подключено' : 'Не подключено';
        badge.className = 'admin-badge ' + (d.success ? 'admin-badge-success' : 'admin-badge-secondary');
    })
    .catch(() => { dpTestResult.textContent = '✗ Ошибка сети'; dpTestResult.style.color = '#991b1b'; });
}

function importDP(btn) {
    const result = document.getElementById('dp-import-result');
    result.textContent = 'Импортируем шипменты... (может занять несколько минут)';
    result.style.color = '#6b7280';
    btn.disabled = true;
    fetch('<?= Url::to(['/admin/plugin/import-dobropost']) ?>', {
        method: 'POST',
        headers: {'X-CSRF-Token': csrfToken}
    })
    .then(r => r.json())
    .then(d => {
        result.textContent = d.success
            ? `✓ ${d.message} (${d.pages} стр.)`
            : `✗ ${d.message}`;
        result.style.color = d.success ? 'var(--admin-accent,#065f46)' : '#991b1b';
        btn.disabled = false;
    })
    .catch(() => { result.textContent = '✗ Ошибка сети'; result.style.color = '#991b1b'; btn.disabled = false; });
}

function saveAllDPSettings() {
    dpSaveResult.textContent = 'Сохраняем...';
    dpSaveResult.style.color = '#6b7280';
    const autoSend = document.getElementById('dp-auto-send').value;
    fetch('<?= Url::to(['/admin/settings/save']) ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken},
        body: JSON.stringify({dobropost: {auto_send: autoSend}})
    })
    .then(r => r.json())
    .then(d => {
        dpSaveResult.textContent = d.success ? '✓ Настройки сохранены' : ('✗ ' + (d.message || 'Ошибка'));
        dpSaveResult.style.color = d.success ? 'var(--admin-accent,#065f46)' : '#991b1b';
    })
    .catch(() => { dpSaveResult.textContent = '✗ Ошибка сети'; dpSaveResult.style.color = '#991b1b'; });
}

function addProxyPhoneRow() {
    var row = document.createElement('div');
    row.className = 'proxy-phone-row';
    row.style.cssText = 'display:flex;gap:8px;align-items:center';
    row.innerHTML = '<input type="text" class="admin-form-input" style="flex:0 0 180px" placeholder="+375XX...">' +
        '<input type="text" class="admin-form-input" style="flex:1" placeholder="Метка">' +
        '<button type="button" class="admin-btn admin-btn-danger admin-btn-sm" onclick="this.closest(\'.proxy-phone-row\').remove()">' +
        '<i class="bi bi-trash"></i></button>';
    document.getElementById('proxy-phones-list').appendChild(row);
}

function saveProxyPhones() {
    var res = document.getElementById('proxy-phones-result');
    res.textContent = 'Сохраняем...'; res.style.color = '#6b7280';
    var phones = [];
    document.querySelectorAll('.proxy-phone-row').forEach(function(row) {
        var inputs = row.querySelectorAll('input');
        var phone = inputs[0]?.value.trim();
        var label = inputs[1]?.value.trim();
        if (phone) phones.push({phone: phone, label: label || ''});
    });
    fetch('<?= Url::to(['/admin/plugin/save-proxy-phones']) ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken},
        body: JSON.stringify({phones: phones})
    })
    .then(r => r.json())
    .then(d => {
        res.textContent = d.success ? ('✓ Сохранено ' + phones.length + ' телефонов') : ('✗ ' + (d.message || 'Ошибка'));
        res.style.color = d.success ? 'var(--admin-accent,#065f46)' : '#991b1b';
    })
    .catch(() => { res.textContent = '✗ Ошибка сети'; res.style.color = '#991b1b'; });
}

function saveStatusMapping() {
    var res = document.getElementById('mapping-save-result');
    res.textContent = 'Сохраняем...'; res.style.color = '#6b7280';
    var mappings = [];
    document.querySelectorAll('tr[data-id]').forEach(function(row) {
        var id     = parseInt(row.dataset.id);
        var status = row.querySelector('.mapping-internal-status')?.value || '';
        var days   = row.querySelector('.mapping-days')?.value;
        var isFin  = row.querySelector('.mapping-is-final')?.checked ? 1 : 0;
        mappings.push({id: id, internal_status: status, estimated_days: days !== '' ? parseInt(days) : null, is_final: isFin});
    });
    fetch('<?= Url::to(['/admin/plugin/save-status-mapping']) ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken},
        body: JSON.stringify({mappings: mappings})
    })
    .then(r => r.json())
    .then(d => {
        res.textContent = d.success ? '✓ Маппинг сохранён' : ('✗ ' + (d.message || 'Ошибка'));
        res.style.color = d.success ? 'var(--admin-accent,#065f46)' : '#991b1b';
    })
    .catch(() => { res.textContent = '✗ Ошибка сети'; res.style.color = '#991b1b'; });
}
</script>
