<?php
/**
 * Настройки интеграций — AmoCRM, МойСклад, Telegram
 */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Интеграции';

$this->params['headerActions'] = [];

?>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(450px,1fr));gap:24px">
    
    <!-- AmoCRM -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2 class="admin-card-title"><i class="bi bi-diagram-3"></i> AmoCRM</h2>
            <span class="admin-badge admin-badge-secondary">Не подключено</span>
        </div>
        <div class="admin-card-body">
            <div class="form-group">
                <label>Домен AmoCRM</label>
                <input type="text" class="admin-form-input" placeholder="example.amocrm.ru" id="amocrm-domain">
            </div>
            <div class="form-group">
                <label>Client ID</label>
                <input type="text" class="admin-form-input" placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx" id="amocrm-client-id">
            </div>
            <div class="form-group">
                <label>Client Secret</label>
                <input type="password" class="admin-form-input" placeholder="••••••••••••••••" id="amocrm-secret">
            </div>
            <div class="form-group">
                <label>Access Token</label>
                <textarea class="admin-form-input" rows="3" id="amocrm-token" placeholder="Получите токен после авторизации"></textarea>
            </div>
            <div style="display:flex;gap:8px">
                <button class="admin-btn admin-btn-primary" onclick="testAmoCRM()">
                    <i class="bi bi-check-circle"></i> Проверить подключение
                </button>
                <button class="admin-btn admin-btn-secondary" onclick="saveAmoCRM()">
                    <i class="bi bi-save"></i> Сохранить
                </button>
            </div>
        </div>
    </div>
    
    <!-- МойСклад -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2 class="admin-card-title"><i class="bi bi-box-seam"></i> МойСклад</h2>
            <span class="admin-badge admin-badge-secondary">Не подключено</span>
        </div>
        <div class="admin-card-body">
            <div class="form-group">
                <label>API Token</label>
                <input type="password" class="admin-form-input" placeholder="••••••••••••••••" id="moysklad-token">
                <small class="text-muted-sm">Получите в настройках МойСклад → API</small>
            </div>
            <div class="form-group">
                <label>ID склада</label>
                <input type="text" class="admin-form-input" placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx" id="moysklad-warehouse">
            </div>
            <div class="form-group">
                <label>Маппинг статусов</label>
                <div style="display:flex;flex-direction:column;gap:8px;padding:12px;background:var(--admin-bg);border-radius:8px">
                    <div class="flex-between">
                        <span class="fs-sm">Новый →</span>
                        <select class="admin-form-select" style="width:auto">
                            <option>Новый заказ</option>
                            <option>Подтвержден</option>
                        </select>
                    </div>
                    <div class="flex-between">
                        <span class="fs-sm">Оплачен →</span>
                        <select class="admin-form-select" style="width:auto">
                            <option>Оплачен</option>
                            <option>В работе</option>
                        </select>
                    </div>
                    <div class="flex-between">
                        <span class="fs-sm">Завершен →</span>
                        <select class="admin-form-select" style="width:auto">
                            <option>Завершен</option>
                            <option>Доставлен</option>
                        </select>
                    </div>
                </div>
            </div>
            <div style="display:flex;gap:8px">
                <button class="admin-btn admin-btn-primary" onclick="testMoySklad()">
                    <i class="bi bi-check-circle"></i> Проверить подключение
                </button>
                <button class="admin-btn admin-btn-secondary" onclick="saveMoySklad()">
                    <i class="bi bi-save"></i> Сохранить
                </button>
            </div>
        </div>
    </div>
    
    <!-- Telegram Bot -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2 class="admin-card-title"><i class="bi bi-telegram"></i> Telegram Bot</h2>
            <span class="admin-badge admin-badge-secondary">Не подключено</span>
        </div>
        <div class="admin-card-body">
            <div class="form-group">
                <label>Bot Token</label>
                <input type="password" class="admin-form-input" placeholder="1234567890:ABCdefGHIjklMNOpqrsTUVwxyz" id="telegram-token">
                <small class="text-muted-sm">Получите у @BotFather</small>
            </div>
            <div class="form-group">
                <label>Chat ID для уведомлений</label>
                <textarea class="admin-form-input" rows="3" id="telegram-chat-ids" placeholder="-1001234567890&#10;-1009876543210"></textarea>
                <small class="text-muted-sm">По одному ID на строку</small>
            </div>
            <div class="form-group">
                <label>Уведомления</label>
                <div style="display:flex;flex-direction:column;gap:8px">
                    <label class="d-flex align-center gap-2 cursor-pointer">
                        <input type="checkbox" checked> Новый заказ
                    </label>
                    <label class="d-flex align-center gap-2 cursor-pointer">
                        <input type="checkbox" checked> Оплата подтверждена
                    </label>
                    <label class="d-flex align-center gap-2 cursor-pointer">
                        <input type="checkbox" checked> Товар на складе
                    </label>
                    <label class="d-flex align-center gap-2 cursor-pointer">
                        <input type="checkbox" checked> Задержки (>3 дней)
                    </label>
                </div>
            </div>
            <div style="display:flex;gap:8px">
                <button class="admin-btn admin-btn-primary" onclick="testTelegram()">
                    <i class="bi bi-send"></i> Отправить тест
                </button>
                <button class="admin-btn admin-btn-secondary" onclick="saveTelegram()">
                    <i class="bi bi-save"></i> Сохранить
                </button>
            </div>
        </div>
    </div>
    
    <!-- Таможня:ДП -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2 class="admin-card-title"><i class="bi bi-box-arrow-right"></i> Таможня:ДП</h2>
            <?php
            try {
                $dpEmail     = Yii::$app->dobropost->email ?? '';
                $dpTariff    = Yii::$app->dobropost->defaultTariff ?? 26;
                $dpConnected = !empty($dpEmail);
            } catch (\Exception $e) {
                $dpEmail = ''; $dpTariff = 26; $dpConnected = false;
            }
            ?>
            <span class="admin-badge <?= $dpConnected ? 'admin-badge-success' : 'admin-badge-secondary' ?>">
                <?= $dpConnected ? 'Подключено' : 'Не подключено' ?>
            </span>
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
                <small class="text-muted-sm">Установите в .env: DP_API_PASSWORD</small>
            </div>
            <div class="form-group">
                <label>Тариф по умолчанию</label>
                <input type="number" class="admin-form-input" id="dp-tariff"
                       placeholder="26"
                       value="<?= htmlspecialchars($dpTariff) ?>">
                <small class="text-muted-sm">Код тарифа Таможня:ДП (26 = стандарт)</small>
            </div>
            <div class="form-group">
                <label>Авто-отправка в Таможня:ДП</label>
                <select class="admin-form-select" id="dp-auto-send">
                    <?php
                    $autoSend = Yii::$app->settings->get('dobropost', 'auto_send', 'manual');
                    $autoSendOptions = [
                        'manual'            => 'Вручную',
                        'on_passport'       => 'При получении паспортных данных',
                        'on_confirmed_paid' => 'При статусе "Подтвержден и оплачен"',
                    ];
                    foreach ($autoSendOptions as $val => $label):
                    ?>
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
                    <button class="admin-btn admin-btn-secondary" type="button"
                            onclick="navigator.clipboard.writeText(document.getElementById('dp-webhook-url').value)">
                        <i class="bi bi-clipboard"></i>
                    </button>
                </div>
                <small class="text-muted-sm">Укажите этот URL в настройках Таможня:ДП → Webhook</small>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <button class="admin-btn admin-btn-primary" onclick="testDobroPost()">
                    <i class="bi bi-check-circle"></i> Проверить подключение
                </button>
                <button class="admin-btn admin-btn-secondary" onclick="saveDobroPost()">
                    <i class="bi bi-save"></i> Сохранить
                </button>
            </div>
            <div id="dp-test-result" class="mt-10px fs-xs"></div>
        </div>
    </div>

    <!-- Курс валют -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2 class="admin-card-title"><i class="bi bi-currency-exchange"></i> Курс CNY</h2>
        </div>
        <div class="admin-card-body">
            <div style="text-align:center;padding:24px;background:linear-gradient(135deg,#fef3c7,#fde68a);border-radius:12px;margin-bottom:16px">
                <p style="font-size:14px;color:#92400e;margin:0 0 8px">Текущий курс CNY → BYN</p>
                <p style="font-size:48px;font-weight:700;margin:0;color:#92400e" id="cny-rate">—</p>
                <p style="font-size:12px;color:#92400e;margin:8px 0 0">Обновлено: <span id="cny-updated">—</span></p>
            </div>
            <div class="form-group">
                <label>Источник курса</label>
                <select class="admin-form-select">
                    <option>НБРБ API (автоматически)</option>
                    <option>Вручную</option>
                </select>
            </div>
            <div class="form-group">
                <label>Наценка (%)</label>
                <input type="number" class="admin-form-input" id="markup-percent" value="" step="0.1" placeholder="Загрузка...">
            </div>
            <button class="admin-btn admin-btn-primary w-100" onclick="updateCNYRate()">
                <i class="bi bi-arrow-clockwise"></i> Обновить курс сейчас
            </button>
        </div>
    </div>

</div>

<script>
function testDobroPost() {
    var result = document.getElementById('dp-test-result');
    result.textContent = 'Проверяем подключение...';
    result.style.color = '#6b7280';
    fetch('/admin/order/dp-test', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': document.querySelector('meta[name=csrf-token]') ? document.querySelector('meta[name=csrf-token]').content : ''
        },
        body: JSON.stringify({
            email: document.getElementById('dp-email').value,
            password: document.getElementById('dp-password').value
        })
    })
    .then(function(r) { return r.json(); })
    .then(function(d) {
        if (d.success) {
            result.textContent = '✓ Подключение успешно: ' + (d.message || 'OK');
            result.style.color = '#065f46';
        } else {
            result.textContent = '✗ Ошибка: ' + (d.message || 'Проверьте настройки');
            result.style.color = '#991b1b';
        }
    })
    .catch(function() {
        result.textContent = '✗ Ошибка сети';
        result.style.color = '#991b1b';
    });
}

function saveDobroPost() {
    var result = document.getElementById('dp-test-result');
    result.textContent = 'Сохранено (настройки применяются через .env и конфиг)';
    result.style.color = '#6b7280';
}
</script>
