<?php
/** @var yii\web\View $this */
/** @var array $config */
/** @var array|null $balance */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'RocketSMS — настройки';

$isActive = !empty($config['active']);
$username = $config['username'] ?? '';
$password = $config['password'] ?? '';
$sender   = $config['sender']   ?? '';
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
                <label class="admin-form-label">Username (ID аккаунта)</label>
                <input type="text" class="admin-form-input" id="rs-username"
                       placeholder="193618972"
                       value="<?= Html::encode($username) ?>">
                <small style="color:var(--admin-text-secondary);font-size:12px">
                    Ваш числовой идентификатор в RocketSMS.by (колонка «ID» в ЛК → API)
                </small>
            </div>
            <div class="admin-form-group">
                <label class="admin-form-label">Password (API-ключ)</label>
                <input type="text" class="admin-form-input" id="rs-password"
                       placeholder="Ваш API-пароль"
                       value="<?= Html::encode($password) ?>" style="font-family:monospace">
                <small style="color:var(--admin-text-secondary);font-size:12px">
                    Пароль доступа к API из личного кабинета RocketSMS.by
                </small>
            </div>
            <div class="admin-form-group">
                <label class="admin-form-label">Альфа-имя (sender)</label>
                <input type="text" class="admin-form-input" id="rs-sender"
                       placeholder="SNEAKERHEAD (необязательно)"
                       value="<?= Html::encode($sender) ?>">
                <small style="color:var(--admin-text-secondary);font-size:12px">
                    Имя отправителя, зарегистрированное у провайдера. Если не задано — используется номер по умолчанию.
                </small>
            </div>
            <div class="admin-form-group">
                <label class="admin-form-label">Статус плагина</label>
                <select class="admin-form-input" id="rs-active">
                    <option value="1" <?= $isActive ? 'selected' : '' ?>>Активен</option>
                    <option value="0" <?= !$isActive ? 'selected' : '' ?>>Неактивен</option>
                </select>
            </div>
        </div>
    </div>

    <div class="admin-card">
        <div class="admin-card-header">
            <h2 class="admin-card-title"><i class="bi bi-cash-coin"></i> Баланс и статус</h2>
        </div>
        <div class="admin-card-body">
            <?php if ($balance !== null): ?>
                <div style="display:flex;flex-direction:column;gap:12px">
                    <div>
                        <div style="font-size:0.78rem;color:var(--admin-text-secondary);margin-bottom:4px">Доступных SMS (кредиты)</div>
                        <div style="font-size:2rem;font-weight:800;color:#059669">
                            <?= Html::encode(number_format((float)$balance['credits'], 0, '.', ' ')) ?>
                        </div>
                    </div>
                    <?php if (isset($balance['balance']) || isset($balance['currency'])): ?>
                    <div>
                        <div style="font-size:0.78rem;color:var(--admin-text-secondary);margin-bottom:4px">Остаток на счёте</div>
                        <div style="font-size:1.25rem;font-weight:600"><?= Html::encode($balance['currency'] ?? 'BYN') ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            <?php elseif (empty($username) || empty($password)): ?>
                <p style="color:var(--admin-text-secondary);font-size:0.875rem">
                    Заполните username / password — и после сохранения здесь отобразится актуальный баланс.
                </p>
            <?php else: ?>
                <p style="color:#b91c1c;font-size:0.875rem">
                    <i class="bi bi-exclamation-triangle"></i>
                    Не удалось получить баланс. Проверьте правильность username / password.
                </p>
            <?php endif; ?>

            <hr style="margin:16px 0;border:0;border-top:1px solid var(--admin-border,#e5e7eb)">

            <div class="admin-form-group">
                <label class="admin-form-label">Тестовая отправка SMS</label>
                <div style="display:flex;gap:8px;flex-wrap:wrap">
                    <input type="text" class="admin-form-input" id="rs-test-phone"
                           placeholder="+375 29 123-45-67" style="flex:1;min-width:180px">
                    <button type="button" class="admin-btn admin-btn-secondary admin-btn-sm" onclick="sendTestSms()">
                        <i class="bi bi-send"></i> Отправить тест
                    </button>
                </div>
                <div id="rs-test-result" style="font-size:12px;margin-top:8px;white-space:pre-wrap"></div>
                <small style="color:var(--admin-text-secondary);font-size:11px">
                    Текст по умолчанию: «Тест RocketSMS от админки». Списывается 1 SMS с баланса.
                </small>
            </div>
        </div>
    </div>

</div>

<!-- Информация об API -->
<div class="admin-card" style="margin-bottom:24px">
    <div class="admin-card-header">
        <h2 class="admin-card-title"><i class="bi bi-info-circle"></i> Информация</h2>
    </div>
    <div class="admin-card-body" style="font-size:0.875rem;line-height:1.6">
        <p style="margin:0 0 10px"><strong>Провайдер:</strong> <a href="https://rocketsms.by" target="_blank" rel="noopener">RocketSMS.by</a></p>
        <p style="margin:0 0 10px"><strong>API endpoint:</strong> <code>https://api.rocketsms.by/simple/send</code></p>
        <p style="margin:0 0 10px"><strong>Документация:</strong> <a href="https://rocketsms.by/storage/rocketsms_api.pdf" target="_blank" rel="noopener">rocketsms_api.pdf</a></p>
        <p style="margin:0 0 10px"><strong>Используется для:</strong></p>
        <ul style="margin:0 0 10px 20px;padding:0">
            <li>Уведомления клиентам о новом заказе</li>
            <li>SMS о смене статуса заказа (отправлен, доставлен и т.д.)</li>
            <li>Подтверждение получения оплаты</li>
            <li>Разовые сервисные уведомления</li>
        </ul>
        <p style="margin:0;color:var(--admin-text-secondary)">
            После сохранения настроек компонент <code>Yii::$app->sms</code> автоматически переключится на RocketSMS.
        </p>
    </div>
</div>

<div class="admin-card">
    <div class="admin-card-body" style="display:flex;align-items:center;gap:16px;flex-wrap:wrap">
        <button class="admin-btn admin-btn-primary" onclick="saveRocketsmsSettings()">
            <i class="bi bi-save"></i> Сохранить настройки
        </button>
        <div id="rs-save-result" style="font-size:13px"></div>
    </div>
</div>

<script>
(function(){
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

window.saveRocketsmsSettings = function() {
    const result = document.getElementById('rs-save-result');
    result.textContent = 'Сохраняем...';
    result.style.color = '#6b7280';
    fetch('<?= Url::to(['/admin/plugin/save-rocketsms']) ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-Token': csrfToken},
        body: new URLSearchParams({
            username: document.getElementById('rs-username').value,
            password: document.getElementById('rs-password').value,
            sender:   document.getElementById('rs-sender').value,
            active:   document.getElementById('rs-active').value === '1' ? 1 : 0,
        }).toString()
    })
    .then(r => r.json())
    .then(d => {
        result.textContent = d.success ? ('✓ ' + d.message + ' — перезагрузите страницу для проверки баланса') : ('✗ ' + (d.message || 'Ошибка'));
        result.style.color = d.success ? '#059669' : '#991b1b';
        if (d.success) setTimeout(() => location.reload(), 1200);
    })
    .catch(() => { result.textContent = '✗ Ошибка сети'; result.style.color = '#991b1b'; });
};

window.sendTestSms = function() {
    const phone = document.getElementById('rs-test-phone').value.trim();
    const out   = document.getElementById('rs-test-result');
    if (!phone) { out.textContent = 'Укажите номер'; out.style.color = '#991b1b'; return; }
    out.textContent = 'Отправляем...';
    out.style.color = '#6b7280';
    fetch('<?= Url::to(['/admin/plugin/test-rocketsms']) ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-Token': csrfToken},
        body: new URLSearchParams({phone: phone, text: 'Тест RocketSMS от админки'}).toString()
    })
    .then(r => r.json())
    .then(d => {
        out.textContent = (d.success ? '✓ ' : '✗ ') + d.message;
        out.style.color = d.success ? '#059669' : '#991b1b';
    })
    .catch(() => { out.textContent = '✗ Ошибка сети'; out.style.color = '#991b1b'; });
};
})();
</script>
