<?php

/** @var yii\web\View $this */

use yii\helpers\Html;

$this->title = 'Настройки';
$company = Yii::$app->settings->getCompany() ?? [];
?>

<div class="admin-header">
    <h1 class="admin-header-title"><?= Html::encode($this->title) ?></h1>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 1.5rem;">
    <!-- Компания -->
    <div class="admin-card">
        <h2 class="admin-card-title">
            <i class="bi bi-building"></i>
            Информация о компании
        </h2>
        
        <div style="margin-top: 1.5rem;">
            <div class="form-group">
                <label>Название компании</label>
                <input type="text" class="form-control" value="<?= Html::encode($company['name'] ?? 'СНИКЕРХЭД') ?>" data-setting="company_name">
            </div>
            
            <div class="form-group">
                <label>Email</label>
                <input type="email" class="form-control" value="<?= Html::encode($company['email'] ?? '') ?>" data-setting="company_email">
            </div>
            
            <div class="form-group">
                <label>Телефон</label>
                <input type="tel" class="form-control" value="<?= Html::encode($company['phone'] ?? '') ?>" data-setting="company_phone">
            </div>
            
            <div class="form-group">
                <label>Адрес</label>
                <textarea class="form-control" rows="2" data-setting="company_address"><?= Html::encode($company['address'] ?? '') ?></textarea>
            </div>
            
            <button class="admin-btn admin-btn-primary" onclick="saveSettings(this)">
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
