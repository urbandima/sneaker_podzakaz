<?php
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Telegram Bot — настройки';

$token    = Yii::$app->settings->get('telegram', 'bot_token', '');
$chatIds  = Yii::$app->settings->get('telegram', 'chat_ids', '');
$notifMap = [
    'notify_new_order'    => 'Новый заказ',
    'notify_paid'         => 'Оплата подтверждена',
    'notify_at_warehouse' => 'Товар на складе',
    'notify_delay'        => 'Задержки (>3 дней)',
    'notify_canceled'     => 'Заказ отменён',
];

$this->params['headerActions'] = [
    Html::a('<i class="bi bi-arrow-left"></i> Все плагины', ['/admin/plugin'], ['class' => 'admin-btn admin-btn-secondary admin-btn-sm']),
    '<span class="admin-badge ' . ($token ? 'admin-badge-success' : 'admin-badge-secondary') . '" id="tg-status-badge">' . ($token ? 'Настроен' : 'Не настроен') . '</span>'
];
?>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(400px,1fr));gap:24px">

    <div class="admin-card">
        <div class="admin-card-header">
            <h2 class="admin-card-title"><i class="bi bi-robot"></i> Токен бота</h2>
        </div>
        <div class="admin-card-body">
            <div class="form-group">
                <label>Bot Token</label>
                <input type="password" class="admin-form-input" id="telegram-token"
                       placeholder="1234567890:ABCdefGHIjklMNOpqrsTUVwxyz"
                       value="<?= Html::encode($token) ?>">
                <small class="text-muted-sm">
                    Создайте бота у
                    <a href="https://t.me/BotFather" target="_blank" rel="noopener" style="color:var(--admin-accent)">@BotFather</a>
                    и скопируйте токен
                </small>
            </div>
            <div class="form-group">
                <label>Chat ID для уведомлений</label>
                <textarea class="admin-form-input" rows="3" id="telegram-chat-ids"
                          placeholder="-1001234567890&#10;-1009876543210"><?= Html::encode($chatIds) ?></textarea>
                <small class="text-muted-sm">По одному ID на строку. Узнайте ID через @userinfobot</small>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <button class="admin-btn admin-btn-primary" onclick="testTelegramConn()">
                    <i class="bi bi-send"></i> Отправить тест
                </button>
                <button class="admin-btn admin-btn-secondary" onclick="saveTelegramConn()">
                    <i class="bi bi-save"></i> Сохранить
                </button>
            </div>
            <div id="tg-test-result" class="mt-10px fs-xs"></div>
        </div>
    </div>

    <div class="admin-card">
        <div class="admin-card-header">
            <h2 class="admin-card-title"><i class="bi bi-bell"></i> Уведомления</h2>
        </div>
        <div class="admin-card-body">
            <p style="font-size:13px;color:var(--admin-text-secondary);margin-bottom:12px">
                Выберите события, о которых бот будет присылать уведомления
            </p>
            <div style="display:flex;flex-direction:column;gap:10px">
                <?php foreach ($notifMap as $key => $label): ?>
                <label style="display:flex;align-items:center;gap:10px;cursor:pointer;padding:8px;border-radius:8px;border:1px solid var(--admin-border)">
                    <input type="checkbox" id="<?= $key ?>"
                           <?= Yii::$app->settings->get('telegram', $key, '1') ? 'checked' : '' ?>
                           style="width:16px;height:16px;accent-color:var(--admin-accent)">
                    <span class="fs-sm"><?= $label ?></span>
                </label>
                <?php endforeach; ?>
            </div>
            <button class="admin-btn admin-btn-secondary" style="margin-top:12px;width:100%" onclick="saveTelegramNotifs()">
                <i class="bi bi-save"></i> Сохранить уведомления
            </button>
        </div>
    </div>

</div>

<script>
const tgResult = document.getElementById('tg-test-result');
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

function testTelegramConn() {
    const token = document.getElementById('telegram-token').value;
    if (!token) { tgResult.textContent = '⚠ Введите Bot Token'; tgResult.style.color = '#b45309'; return; }
    tgResult.textContent = 'Отправляем тест...'; tgResult.style.color = '#6b7280';
    fetch('<?= Url::to(['/admin/settings/test-telegram']) ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken},
        body: JSON.stringify({token})
    })
    .then(r => r.json())
    .then(d => {
        tgResult.textContent = d.success ? '✓ Тестовое сообщение отправлено!' : ('✗ ' + (d.message || 'Ошибка'));
        tgResult.style.color = d.success ? '#065f46' : '#991b1b';
        if (d.success) {
            document.getElementById('tg-status-badge').textContent = 'Активен';
            document.getElementById('tg-status-badge').className = 'admin-badge admin-badge-success';
        }
    })
    .catch(() => { tgResult.textContent = '✗ Ошибка сети'; tgResult.style.color = '#991b1b'; });
}

function saveTelegramConn() {
    const token   = document.getElementById('telegram-token').value;
    const chatIds = document.getElementById('telegram-chat-ids').value;
    fetch('<?= Url::to(['/admin/settings/save']) ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken},
        body: JSON.stringify({telegram: {bot_token: token, chat_ids: chatIds}})
    })
    .then(r => r.json())
    .then(d => {
        tgResult.textContent = d.success ? '✓ Настройки сохранены' : ('✗ ' + d.message);
        tgResult.style.color = d.success ? '#065f46' : '#991b1b';
    })
    .catch(() => { tgResult.textContent = '✗ Ошибка сети'; tgResult.style.color = '#991b1b'; });
}

function saveTelegramNotifs() {
    const keys = <?= json_encode(array_keys($notifMap)) ?>;
    const data = {};
    keys.forEach(k => { data[k] = document.getElementById(k)?.checked ? '1' : '0'; });
    fetch('<?= Url::to(['/admin/settings/save']) ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken},
        body: JSON.stringify({telegram: data})
    })
    .then(r => r.json())
    .then(d => alert(d.success ? '✓ Настройки уведомлений сохранены' : ('✗ ' + d.message)));
}
</script>
