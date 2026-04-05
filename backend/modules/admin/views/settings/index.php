<?php

/** @var yii\web\View $this */

use yii\helpers\Html;

$this->title = 'Настройки';
$company = Yii::$app->settings->getCompany() ?? [];
?>

<div class="admin-header">
    <h1 class="admin-header-title"><?= Html::encode($this->title) ?></h1>
</div>

<!-- Быстрые ссылки -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:16px;margin-bottom:24px">
    <a href="<?= \yii\helpers\Url::to(['/admin/settings/statuses']) ?>" class="admin-card" style="text-decoration:none;color:inherit;transition:transform 0.2s" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
        <div style="display:flex;align-items:center;gap:12px">
            <div style="width:48px;height:48px;background:linear-gradient(135deg,#3b82f6,#2563eb);border-radius:12px;display:flex;align-items:center;justify-content:center">
                <i class="bi bi-diagram-3" style="font-size:24px;color:white"></i>
            </div>
            <div>
                <h3 style="margin:0;font-size:16px;font-weight:600">Статусы заказов</h3>
                <p style="margin:4px 0 0;font-size:13px;color:var(--admin-text-secondary)">Настройка цепочки статусов</p>
            </div>
        </div>
    </a>
    
    <a href="<?= \yii\helpers\Url::to(['/admin/settings/integrations']) ?>" class="admin-card" style="text-decoration:none;color:inherit;transition:transform 0.2s" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
        <div style="display:flex;align-items:center;gap:12px">
            <div style="width:48px;height:48px;background:linear-gradient(135deg,#10b981,#059669);border-radius:12px;display:flex;align-items:center;justify-content:center">
                <i class="bi bi-plug" style="font-size:24px;color:white"></i>
            </div>
            <div>
                <h3 style="margin:0;font-size:16px;font-weight:600">Интеграции</h3>
                <p style="margin:4px 0 0;font-size:13px;color:var(--admin-text-secondary)">AmoCRM, МойСклад, Telegram</p>
            </div>
        </div>
    </a>
    
    <a href="<?= \yii\helpers\Url::to(['/admin/plugin']) ?>" class="admin-card" style="text-decoration:none;color:inherit;transition:transform 0.2s" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
        <div style="display:flex;align-items:center;gap:12px">
            <div style="width:48px;height:48px;background:linear-gradient(135deg,#8b5cf6,#7c3aed);border-radius:12px;display:flex;align-items:center;justify-content:center">
                <i class="bi bi-plugin" style="font-size:24px;color:white"></i>
            </div>
            <div>
                <h3 style="margin:0;font-size:16px;font-weight:600">Плагины</h3>
                <p style="margin:4px 0 0;font-size:13px;color:var(--admin-text-secondary)">Дополнительные модули</p>
            </div>
        </div>
    </a>
    <a href="<?= \yii\helpers\Url::to(['/admin/seo']) ?>" class="admin-card" style="text-decoration:none;color:inherit;transition:transform 0.2s" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
        <div style="display:flex;align-items:center;gap:12px">
            <div style="width:48px;height:48px;background:linear-gradient(135deg,#ec4899,#db2777);border-radius:12px;display:flex;align-items:center;justify-content:center">
                <i class="bi bi-search-heart" style="font-size:24px;color:white"></i>
            </div>
            <div>
                <h3 style="margin:0;font-size:16px;font-weight:600">SEO</h3>
                <p style="margin:4px 0 0;font-size:13px;color:var(--admin-text-secondary)">Мета-теги, sitemap, robots.txt</p>
            </div>
        </div>
    </a>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 1.5rem;">
    <!-- Компания -->
    <div class="admin-card" id="companyCard">
        <h2 class="admin-card-title">
            <i class="bi bi-building"></i>
            Информация о компании
        </h2>

        <div style="margin-top: 1.5rem;">
            <div class="form-group">
                <label>Название</label>
                <input type="text" class="form-control" id="co_name" value="<?= Html::encode($company['name'] ?? 'СНИКЕРХЭД') ?>">
            </div>

            <div class="form-group">
                <label>УНП</label>
                <input type="text" class="form-control" id="co_unp" value="<?= Html::encode($company['unp'] ?? '') ?>" placeholder="000000000">
            </div>

            <div class="form-group">
                <label>Адрес</label>
                <textarea class="form-control" rows="2" id="co_address"><?= Html::encode($company['address'] ?? '') ?></textarea>
            </div>

            <div class="form-group">
                <label>Телефон</label>
                <input type="tel" class="form-control" id="co_phone" value="<?= Html::encode($company['phone'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label>Email</label>
                <input type="email" class="form-control" id="co_email" value="<?= Html::encode($company['email'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label>Рабочее время</label>
                <input type="text" class="form-control" id="co_work_time" value="<?= Html::encode($company['work_time'] ?? '') ?>" placeholder="Пн–Пт 10:00–19:00">
            </div>

            <div class="form-group">
                <label>Банковские реквизиты</label>
                <textarea class="form-control" rows="3" id="co_bank_details" placeholder="БИК, р/с, банк..."><?= Html::encode($company['bank_details'] ?? '') ?></textarea>
            </div>

            <button class="admin-btn admin-btn-primary" id="saveCompanyBtn" onclick="saveCompany(this)">
                <i class="bi bi-check-circle"></i>
                Сохранить
            </button>
        </div>
    </div>
    
    <!-- Система -->
    <div class="admin-card">
        <h2 class="admin-card-title">
            <i class="bi bi-gear"></i>
            Системные настройки
        </h2>
        
        <div style="margin-top: 1.5rem;">
            <div class="form-group">
                <label>Режим обслуживания</label>
                <select class="form-control" data-setting="system.maintenance_mode">
                    <option value="0" <?= !Yii::$app->settings->get('system', 'maintenance_mode') ? 'selected' : '' ?>>Выключен</option>
                    <option value="1" <?= Yii::$app->settings->get('system', 'maintenance_mode') ? 'selected' : '' ?>>Включен</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Уведомления о заказах</label>
                <select class="form-control" data-setting="system.order_notifications">
                    <option value="1" <?= Yii::$app->settings->get('system', 'order_notifications', 1) ? 'selected' : '' ?>>Включены</option>
                    <option value="0" <?= !Yii::$app->settings->get('system', 'order_notifications', 1) ? 'selected' : '' ?>>Выключены</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Курс BYN к CNY</label>
                <input type="number" step="0.01" class="form-control" value="<?= Html::encode(Yii::$app->settings->get('system', 'cny_rate', 0.28)) ?>" data-setting="system.cny_rate">
            </div>
            
            <button class="admin-btn admin-btn-primary" onclick="saveSettings(this)">
                <i class="bi bi-check-circle"></i>
                Сохранить
            </button>
        </div>
    </div>
    
    <!-- Безопасность -->
    <div class="admin-card">
        <h2 class="admin-card-title">
            <i class="bi bi-shield-check"></i>
            Безопасность
        </h2>
        
        <div style="margin-top: 1.5rem;">
            <div class="form-group">
                <label>Максимум попыток входа</label>
                <input type="number" class="form-control" value="<?= Html::encode(Yii::$app->settings->get('security', 'max_login_attempts', 5)) ?>" data-setting="security.max_login_attempts">
            </div>
            
            <div class="form-group">
                <label>Время блокировки (минуты)</label>
                <input type="number" class="form-control" value="<?= Html::encode(Yii::$app->settings->get('security', 'lockout_duration', 15)) ?>" data-setting="security.lockout_duration">
            </div>
            
            <div class="form-group">
                <label>Двухфакторная аутентификация</label>
                <select class="form-control" data-setting="security.two_factor_auth">
                    <option value="0" <?= !Yii::$app->settings->get('security', 'two_factor_auth') ? 'selected' : '' ?>>Выключена</option>
                    <option value="1" <?= Yii::$app->settings->get('security', 'two_factor_auth') ? 'selected' : '' ?>>Включена</option>
                </select>
            </div>
            
            <button class="admin-btn admin-btn-primary" onclick="saveSettings(this)">
                <i class="bi bi-check-circle"></i>
                Сохранить
            </button>
        </div>
    </div>
    
    <!-- Информация о системе -->
    <div class="admin-card">
        <h2 class="admin-card-title">
            <i class="bi bi-info-circle"></i>
            Информация о системе
        </h2>

        <div style="margin-top: 1.5rem;">
            <table class="admin-table">
                <tr>
                    <td style="font-weight: 600;">Версия Yii</td>
                    <td><?= Yii::getVersion() ?></td>
                </tr>
                <tr>
                    <td style="font-weight: 600;">Версия PHP</td>
                    <td><?= PHP_VERSION ?></td>
                </tr>
                <tr>
                    <td style="font-weight: 600;">Окружение</td>
                    <td><?= YII_ENV ?></td>
                </tr>
                <tr>
                    <td style="font-weight: 600;">Режим отладки</td>
                    <td><?= YII_DEBUG ? 'Включен' : 'Выключен' ?></td>
                </tr>
                <tr>
                    <td style="font-weight: 600;">База данных</td>
                    <td><?= Yii::$app->db->driverName ?></td>
                </tr>
                <tr>
                    <td style="font-weight: 600;">Часовой пояс</td>
                    <td><?= date_default_timezone_get() ?></td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Telegram -->
    <div class="admin-card">
        <h2 class="admin-card-title">
            <i class="bi bi-telegram"></i>
            Telegram-бот
        </h2>
        <div style="margin-top: 1.5rem;">
            <div class="form-group">
                <label>Bot Token</label>
                <input type="text" class="form-control" value="<?= Html::encode(Yii::$app->settings->get('telegram', 'bot_token', '')) ?>" data-setting="telegram.bot_token" placeholder="123456789:AABBcc...">
            </div>
            <div class="form-group">
                <label>Chat ID(s) <small style="font-weight:400;color:#94a3b8;">(через запятую)</small></label>
                <input type="text" class="form-control" value="<?= Html::encode(Yii::$app->settings->get('telegram', 'chat_ids', '')) ?>" data-setting="telegram.chat_ids" placeholder="-1001234567890, 987654321">
            </div>
            <div style="display:flex;gap:0.75rem;align-items:center;flex-wrap:wrap;">
                <button class="admin-btn admin-btn-primary" onclick="saveSettings(this)">
                    <i class="bi bi-check-circle"></i> Сохранить
                </button>
                <button class="admin-btn admin-btn-secondary" onclick="testTelegram(this)">
                    <i class="bi bi-send"></i> Тест отправки
                </button>
            </div>
            <div id="telegram-test-result" style="display:none;margin-top:0.75rem;padding:0.5rem 0.75rem;border-radius:0.5rem;font-size:0.875rem;"></div>
        </div>
    </div>

    <!-- PDF Шаблоны -->
    <div class="admin-card">
        <h2 class="admin-card-title">
            <i class="bi bi-file-earmark-pdf"></i>
            PDF шаблоны накладных
        </h2>
        <div style="margin-top: 1.5rem;">
            <p style="font-size:0.875rem;color:var(--admin-text-secondary);margin-bottom:1rem;">
                Данные для PDF-накладных берутся из реквизитов компании выше. Убедитесь, что заполнены:
            </p>
            <?php
            $company = Yii::$app->settings->getCompany() ?? [];
            $pdfFields = [
                'name'    => 'Название организации',
                'address' => 'Адрес',
                'unp'     => 'УНП / ИНН',
                'phone'   => 'Телефон',
                'email'   => 'Email',
            ];
            ?>
            <table class="admin-table">
                <?php foreach ($pdfFields as $key => $label): ?>
                <tr>
                    <td style="font-weight:600;"><?= Html::encode($label) ?></td>
                    <td>
                        <?php if (!empty($company[$key])): ?>
                            <span style="color:#10b981;"><i class="bi bi-check-circle-fill"></i> <?= Html::encode($company[$key]) ?></span>
                        <?php else: ?>
                            <span style="color:#f59e0b;"><i class="bi bi-exclamation-circle"></i> Не заполнено</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
            <div style="margin-top:1rem;">
                <a href="<?= \yii\helpers\Url::to(['/admin/order/index']) ?>" class="admin-btn admin-btn-secondary" style="font-size:0.875rem;">
                    <i class="bi bi-printer"></i> Пример: печать накладной из заказа
                </a>
            </div>
        </div>
    </div>

    <!-- Автообновление курса CNY -->
    <div class="admin-card">
        <h2 class="admin-card-title">
            <i class="bi bi-currency-exchange"></i>
            Курс CNY — автообновление
        </h2>
        <div style="margin-top: 1.5rem;">
            <div class="form-group">
                <label>Автообновление курса CNY</label>
                <select class="form-control" data-setting="system.cny_auto_update">
                    <option value="1" <?= Yii::$app->settings->get('system', 'cny_auto_update', 1) ? 'selected' : '' ?>>Включено (обновляется автоматически)</option>
                    <option value="0" <?= !Yii::$app->settings->get('system', 'cny_auto_update', 1) ? 'selected' : '' ?>>Выключено (ручной ввод)</option>
                </select>
            </div>
            <div class="form-group">
                <label>Текущий курс BYN/CNY (ручной)</label>
                <input type="number" step="0.001" class="form-control" value="<?= Html::encode(Yii::$app->settings->get('system', 'cny_rate', 0.28)) ?>" data-setting="system.cny_rate" placeholder="0.280">
                <small style="color:#94a3b8;font-size:0.8rem;">Используется при отключённом автообновлении</small>
            </div>
            <button class="admin-btn admin-btn-primary" onclick="saveSettings(this)">
                <i class="bi bi-check-circle"></i> Сохранить
            </button>
        </div>
    </div>

    <!-- MoySklad -->
    <div class="admin-card">
        <h2 class="admin-card-title">
            <i class="bi bi-boxes"></i>
            МойСклад
        </h2>
        <div style="margin-top: 1.5rem;">
            <div class="form-group">
                <label>API-ключ</label>
                <input type="text" class="form-control" value="<?= Html::encode(Yii::$app->settings->get('moysklad', 'api_key', '')) ?>" data-setting="moysklad.api_key" placeholder="Bearer токен или логин:пароль">
            </div>
            <div class="form-group">
                <label>Склад (UUID или название)</label>
                <input type="text" class="form-control" value="<?= Html::encode(Yii::$app->settings->get('moysklad', 'warehouse', '')) ?>" data-setting="moysklad.warehouse" placeholder="Основной склад">
            </div>
            <div class="form-group">
                <label>Маппинг статусов заказа → МойСклад <small style="font-weight:400;color:#94a3b8;">(через =, строки через ;)</small></label>
                <textarea class="form-control" rows="4" data-setting="moysklad.status_map" placeholder="new=Новый;paid=Оплачен;shipped=Отгружен"><?= Html::encode(Yii::$app->settings->get('moysklad', 'status_map', '')) ?></textarea>
            </div>
            <div style="display:flex;gap:0.75rem;align-items:center;flex-wrap:wrap;">
                <button class="admin-btn admin-btn-primary" onclick="saveSettings(this)">
                    <i class="bi bi-check-circle"></i> Сохранить
                </button>
                <button class="admin-btn admin-btn-secondary" onclick="testIntegration('moysklad', this)">
                    <i class="bi bi-plug"></i> Проверить подключение
                </button>
            </div>
            <div id="moysklad-test-result" style="display:none;margin-top:0.75rem;padding:0.5rem 0.75rem;border-radius:0.5rem;font-size:0.875rem;"></div>
        </div>
    </div>

    <!-- AmoCRM -->
    <div class="admin-card">
        <h2 class="admin-card-title">
            <i class="bi bi-people"></i>
            AmoCRM
        </h2>
        <div style="margin-top: 1.5rem;">
            <div class="form-group">
                <label>Домен <small style="font-weight:400;color:#94a3b8;">(yourcompany.amocrm.ru)</small></label>
                <input type="text" class="form-control" value="<?= Html::encode(Yii::$app->settings->get('amocrm', 'domain', '')) ?>" data-setting="amocrm.domain" placeholder="yourcompany.amocrm.ru">
            </div>
            <div class="form-group">
                <label>API Token (Long-lived)</label>
                <input type="text" class="form-control" value="<?= Html::encode(Yii::$app->settings->get('amocrm', 'api_token', '')) ?>" data-setting="amocrm.api_token" placeholder="eyJ0...">
            </div>
            <div class="form-group">
                <label>ID воронки (Pipeline)</label>
                <input type="text" class="form-control" value="<?= Html::encode(Yii::$app->settings->get('amocrm', 'pipeline_id', '')) ?>" data-setting="amocrm.pipeline_id" placeholder="123456">
            </div>
            <div class="form-group">
                <label>Маппинг статусов заказа → AmoCRM Stage <small style="font-weight:400;color:#94a3b8;">(строки через ;)</small></label>
                <textarea class="form-control" rows="4" data-setting="amocrm.stage_map" placeholder="new=142;paid=143;shipped=144"><?= Html::encode(Yii::$app->settings->get('amocrm', 'stage_map', '')) ?></textarea>
            </div>
            <div style="display:flex;gap:0.75rem;align-items:center;flex-wrap:wrap;">
                <button class="admin-btn admin-btn-primary" onclick="saveSettings(this)">
                    <i class="bi bi-check-circle"></i> Сохранить
                </button>
                <button class="admin-btn admin-btn-secondary" onclick="testIntegration('amocrm', this)">
                    <i class="bi bi-plug"></i> Проверить подключение
                </button>
            </div>
            <div id="amocrm-test-result" style="display:none;margin-top:0.75rem;padding:0.5rem 0.75rem;border-radius:0.5rem;font-size:0.875rem;"></div>
        </div>
    </div>
</div>

<style>
.form-control {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1px solid var(--admin-border);
    border-radius: 0.5rem;
    background: var(--admin-bg);
    color: var(--admin-text-primary);
    font-size: 1rem;
    transition: border-color 0.2s, box-shadow 0.2s;
}

.form-control:focus {
    outline: none;
    border-color: var(--admin-primary);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.form-group {
    margin-bottom: 1rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: var(--admin-text-secondary);
    font-size: 0.875rem;
}
</style>

<script>
function saveCompany(btn) {
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Сохранение...';
    const data = {
        name:         document.getElementById('co_name').value,
        unp:          document.getElementById('co_unp').value,
        address:      document.getElementById('co_address').value,
        phone:        document.getElementById('co_phone').value,
        email:        document.getElementById('co_email').value,
        work_time:    document.getElementById('co_work_time').value,
        bank_details: document.getElementById('co_bank_details').value,
    };
    fetch('<?= \yii\helpers\Url::to(["/admin/settings/save-company"]) ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content},
        body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(res => {
        btn.disabled = false;
        if (res.success) {
            btn.innerHTML = '<i class="bi bi-check2-circle"></i> Сохранено';
            btn.classList.replace('admin-btn-primary', 'admin-btn-success');
            setTimeout(() => { btn.innerHTML = '<i class="bi bi-check-circle"></i> Сохранить'; btn.classList.replace('admin-btn-success', 'admin-btn-primary'); }, 2500);
        } else {
            btn.innerHTML = '<i class="bi bi-exclamation-circle"></i> Ошибка';
            alert(res.message || 'Ошибка сохранения');
            setTimeout(() => { btn.innerHTML = '<i class="bi bi-check-circle"></i> Сохранить'; }, 2000);
        }
    })
    .catch(() => { btn.disabled = false; btn.innerHTML = '<i class="bi bi-check-circle"></i> Сохранить'; alert('Ошибка соединения'); });
}

function testTelegram(btn) {
    var resultEl = document.getElementById('telegram-test-result');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Отправка...';
    fetch('<?= \yii\helpers\Url::to(["/admin/settings/test-telegram"]) ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({})
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-send"></i> Тест отправки';
        if (resultEl) {
            resultEl.style.display = 'block';
            resultEl.style.background = data.success ? '#d1fae5' : '#fee2e2';
            resultEl.style.color = data.success ? '#065f46' : '#991b1b';
            resultEl.textContent = data.message || (data.success ? 'Сообщение отправлено' : 'Ошибка');
        }
    })
    .catch(function() {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-send"></i> Тест отправки';
        if (resultEl) {
            resultEl.style.display = 'block';
            resultEl.style.background = '#fee2e2';
            resultEl.style.color = '#991b1b';
            resultEl.textContent = 'Ошибка соединения';
        }
    });
}

function testIntegration(type, btn) {
    var resultEl = document.getElementById(type + '-test-result');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Проверка...';
    var url = type === 'moysklad'
        ? '<?= \yii\helpers\Url::to(["/admin/settings/test-moysklad"]) ?>'
        : '<?= \yii\helpers\Url::to(["/admin/settings/test-amocrm"]) ?>';
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({})
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-plug"></i> Проверить подключение';
        if (resultEl) {
            resultEl.style.display = 'block';
            resultEl.style.background = data.success ? '#d1fae5' : '#fee2e2';
            resultEl.style.color = data.success ? '#065f46' : '#991b1b';
            resultEl.textContent = data.message || (data.success ? 'Подключение успешно' : 'Ошибка подключения');
        }
    })
    .catch(function() {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-plug"></i> Проверить подключение';
        if (resultEl) {
            resultEl.style.display = 'block';
            resultEl.style.background = '#fee2e2';
            resultEl.style.color = '#991b1b';
            resultEl.textContent = 'Ошибка соединения';
        }
    });
}

function saveSettings(btn) {
    const card = btn.closest('.admin-card');
    const inputs = card.querySelectorAll('[data-setting]');
    const data = {};
    
    inputs.forEach(input => {
        const settingKey = input.dataset.setting;
        const [section, key] = settingKey.split('.');
        if (!data[section]) {
            data[section] = {};
        }
        data[section][key] = input.value;
    });
    
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Сохранение...';
    
    fetch('<?= \yii\helpers\Url::to(["/admin/settings/save"]) ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(response => {
        btn.disabled = false;
        if (response.success) {
            btn.innerHTML = '<i class="bi bi-check-circle"></i> Сохранено';
            btn.classList.remove('admin-btn-primary');
            btn.classList.add('admin-btn-success');
            setTimeout(() => {
                btn.innerHTML = '<i class="bi bi-check-circle"></i> Сохранить';
                btn.classList.remove('admin-btn-success');
                btn.classList.add('admin-btn-primary');
            }, 2000);
        } else {
            btn.innerHTML = '<i class="bi bi-exclamation-circle"></i> Ошибка';
            alert(response.message || 'Ошибка сохранения');
            setTimeout(() => {
                btn.innerHTML = '<i class="bi bi-check-circle"></i> Сохранить';
            }, 2000);
        }
    })
    .catch(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-circle"></i> Сохранить';
        alert('Ошибка соединения');
    });
}
</script>
