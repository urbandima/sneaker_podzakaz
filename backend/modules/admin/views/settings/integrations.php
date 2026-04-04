<?php
/**
 * Настройки интеграций — AmoCRM, МойСклад, Telegram
 */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Интеграции';
?>

<div class="admin-header">
    <div>
        <h1 class="admin-header-title"><i class="bi bi-puzzle"></i> <?= Html::encode($this->title) ?></h1>
        <p class="dash-subtitle">Настройка внешних сервисов и API</p>
    </div>
</div>

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
                <small style="color:var(--admin-text-secondary);font-size:12px">Получите в настройках МойСклад → API</small>
            </div>
            <div class="form-group">
                <label>ID склада</label>
                <input type="text" class="admin-form-input" placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx" id="moysklad-warehouse">
            </div>
            <div class="form-group">
                <label>Маппинг статусов</label>
                <div style="display:flex;flex-direction:column;gap:8px;padding:12px;background:var(--admin-bg);border-radius:8px">
                    <div style="display:flex;justify-content:space-between;align-items:center">
                        <span style="font-size:14px">Новый →</span>
                        <select class="admin-form-select" style="width:auto">
                            <option>Новый заказ</option>
                            <option>Подтвержден</option>
                        </select>
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center">
                        <span style="font-size:14px">Оплачен →</span>
                        <select class="admin-form-select" style="width:auto">
                            <option>Оплачен</option>
                            <option>В работе</option>
                        </select>
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center">
                        <span style="font-size:14px">Завершен →</span>
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
                <small style="color:var(--admin-text-secondary);font-size:12px">Получите у @BotFather</small>
            </div>
            <div class="form-group">
                <label>Chat ID для уведомлений</label>
                <textarea class="admin-form-input" rows="3" id="telegram-chat-ids" placeholder="-1001234567890&#10;-1009876543210"></textarea>
                <small style="color:var(--admin-text-secondary);font-size:12px">По одному ID на строку</small>
            </div>
            <div class="form-group">
                <label>Уведомления</label>
                <div style="display:flex;flex-direction:column;gap:8px">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                        <input type="checkbox" checked> Новый заказ
                    </label>
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                        <input type="checkbox" checked> Оплата подтверждена
                    </label>
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                        <input type="checkbox" checked> Товар на складе
                    </label>
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
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
            <button class="admin-btn admin-btn-primary" style="width:100%" onclick="updateCNYRate()">
                <i class="bi bi-arrow-clockwise"></i> Обновить курс сейчас
            </button>
        </div>
    </div>
    
</div>

<script>
function testAmoCRM() {
    alert('Тестирование подключения к AmoCRM...');
    // TODO: AJAX запрос к /admin/amo-crm/test
}

function saveAmoCRM() {
    alert('Сохранение настроек AmoCRM...');
    // TODO: AJAX запрос к /admin/amo-crm/save
}

function testMoySklad() {
    alert('Тестирование подключения к МойСклад...');
    // TODO: AJAX запрос к /admin/moy-sklad/test
}

function saveMoySklad() {
    alert('Сохранение настроек МойСклад...');
    // TODO: AJAX запрос к /admin/moy-sklad/save
}

function testTelegram() {
    const token = document.getElementById('telegram-token').value;
    if (!token) {
        alert('Введите Bot Token');
        return;
    }
    
    fetch('/admin/telegram-bot/test', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({token})
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('✅ Тестовое сообщение отправлено!');
        } else {
            alert('❌ Ошибка: ' + data.message);
        }
    });
}

function saveTelegram() {
    alert('Сохранение настроек Telegram...');
    // TODO: AJAX запрос к /admin/telegram-bot/save
}

function updateCNYRate() {
    const btn = document.activeElement;
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Обновление...';
    
    fetch('/admin/exchange-rate/update', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''
        }
    })
    .then(r => {
        if (!r.ok) throw new Error('HTTP ' + r.status);
        return r.json();
    })
    .then(data => {
        if (data.success) {
            document.getElementById('cny-rate').textContent = data.rate;
            document.getElementById('cny-updated').textContent = new Date().toLocaleString('ru-RU');
            btn.innerHTML = '<i class="bi bi-check-circle"></i> Обновлено!';
            btn.classList.remove('admin-btn-primary');
            btn.classList.add('admin-btn-success');
            setTimeout(() => {
                btn.disabled = false;
                btn.innerHTML = originalText;
                btn.classList.add('admin-btn-primary');
                btn.classList.remove('admin-btn-success');
            }, 2000);
        } else {
            throw new Error(data.message || 'Ошибка обновления');
        }
    })
    .catch(err => {
        console.error('Ошибка:', err);
        alert('❌ ' + (err.message || 'Ошибка обновления курса'));
        btn.disabled = false;
        btn.innerHTML = originalText;
    });
}

// Загрузка текущего курса при открытии страницы
fetch('/admin/exchange-rate/current')
    .then(r => r.json())
    .then(data => {
        if (data.rate) {
            document.getElementById('cny-rate').textContent = data.rate;
            document.getElementById('cny-updated').textContent = data.updated_at;
        }
    });
</script>
