<?php
/**
 * Настройки Telegram-бота
 * @var yii\web\View $this
 */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Настройки Telegram-бота';
?>

<?php
$this->params['headerActions'] = [
    '<button class="admin-btn admin-btn-primary" onclick="testTelegramConnection()">
        <i class="bi bi-check-circle"></i> Проверить подключение
    </button>',
];
?>

<!-- Статус подключения -->
<div class="admin-card mb-5">
    <div class="admin-card-header">
        <h2 class="admin-card-title">Статус подключения</h2>
        <span class="admin-badge admin-badge-warning">Не настроен</span>
    </div>
    <div class="admin-card-body">
        <p style="color:var(--admin-text-secondary);margin-bottom:16px">
            Telegram-бот позволяет получать уведомления о новых заказах, изменении статусов и управлять заказами прямо из Telegram.
        </p>
        <button class="admin-btn admin-btn-primary" onclick="testTelegramConnection()">
            <i class="bi bi-check-circle"></i> Проверить подключение
        </button>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px">
    <!-- Основные настройки -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2 class="admin-card-title">Основные настройки</h2>
        </div>
        <div class="admin-card-body">
            <div class="admin-form-group">
                <label class="admin-form-label">Bot Token</label>
                <input type="text" class="admin-form-input" placeholder="123456789:ABCdefGHIjklMNOpqrsTUVwxyz" data-setting="telegram.bot_token">
                <small class="text-muted">Получите токен у @BotFather</small>
            </div>
            
            <div class="admin-form-group">
                <label class="admin-form-label">Chat ID администратора</label>
                <input type="text" class="admin-form-input" placeholder="123456789" data-setting="telegram.admin_chat_id">
                <small class="text-muted">Узнайте свой Chat ID у @userinfobot</small>
            </div>
            
            <div class="admin-form-group">
                <label class="admin-form-label">Chat ID менеджеров (через запятую)</label>
                <input type="text" class="admin-form-input" placeholder="123456789, 987654321" data-setting="telegram.manager_chat_ids">
            </div>
            
            <div class="admin-form-group">
                <label class="admin-form-label">Chat ID логистов (через запятую)</label>
                <input type="text" class="admin-form-input" placeholder="123456789, 987654321" data-setting="telegram.logist_chat_ids">
            </div>
            
            <button class="admin-btn admin-btn-primary" onclick="saveSettings(this)">
                <i class="bi bi-check-circle"></i> Сохранить
            </button>
        </div>
    </div>
    
    <!-- Уведомления -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2 class="admin-card-title">Типы уведомлений</h2>
        </div>
        <div class="admin-card-body">
            <div style="display:flex;flex-direction:column;gap:12px">
                <label class="d-flex align-center gap-2 cursor-pointer">
                    <input type="checkbox" checked data-setting="telegram.notify_new_order">
                    <span>Новый заказ</span>
                </label>
                
                <label class="d-flex align-center gap-2 cursor-pointer">
                    <input type="checkbox" checked data-setting="telegram.notify_status_change">
                    <span>Изменение статуса заказа</span>
                </label>
                
                <label class="d-flex align-center gap-2 cursor-pointer">
                    <input type="checkbox" checked data-setting="telegram.notify_payment">
                    <span>Получение оплаты</span>
                </label>
                
                <label class="d-flex align-center gap-2 cursor-pointer">
                    <input type="checkbox" data-setting="telegram.notify_new_customer">
                    <span>Новый клиент</span>
                </label>
                
                <label class="d-flex align-center gap-2 cursor-pointer">
                    <input type="checkbox" data-setting="telegram.notify_low_stock">
                    <span>Низкий остаток товара</span>
                </label>
                
                <label class="d-flex align-center gap-2 cursor-pointer">
                    <input type="checkbox" data-setting="telegram.notify_new_review">
                    <span>Новый отзыв</span>
                </label>
            </div>
            
            <hr style="margin:16px 0;border:none;border-top:1px solid var(--admin-border)">
            
            <div class="admin-form-group">
                <label class="admin-form-label">Время тихого режима</label>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
                    <input type="time" class="admin-form-input" value="23:00" data-setting="telegram.quiet_time_start">
                    <input type="time" class="admin-form-input" value="08:00" data-setting="telegram.quiet_time_end">
                </div>
                <small class="text-muted">Уведомления не будут отправляться в это время</small>
            </div>
            
            <button class="admin-btn admin-btn-primary" onclick="saveSettings(this)">
                <i class="bi bi-check-circle"></i> Сохранить
            </button>
        </div>
    </div>
</div>

<!-- Команды бота -->
<div class="admin-card mt-5">
    <div class="admin-card-header">
        <h2 class="admin-card-title">Доступные команды бота</h2>
    </div>
    <div class="admin-card-body">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:16px">
            <div style="padding:12px;background:var(--admin-bg);border-radius:8px">
                <code class="text-accent fw-600">/start</code>
                <p style="margin:4px 0 0;font-size:13px;color:var(--admin-text-secondary)">Начать работу с ботом</p>
            </div>
            <div style="padding:12px;background:var(--admin-bg);border-radius:8px">
                <code class="text-accent fw-600">/orders</code>
                <p style="margin:4px 0 0;font-size:13px;color:var(--admin-text-secondary)">Список новых заказов</p>
            </div>
            <div style="padding:12px;background:var(--admin-bg);border-radius:8px">
                <code class="text-accent fw-600">/order [ID]</code>
                <p style="margin:4px 0 0;font-size:13px;color:var(--admin-text-secondary)">Информация о заказе</p>
            </div>
            <div style="padding:12px;background:var(--admin-bg);border-radius:8px">
                <code class="text-accent fw-600">/stats</code>
                <p style="margin:4px 0 0;font-size:13px;color:var(--admin-text-secondary)">Статистика за сегодня</p>
            </div>
            <div style="padding:12px;background:var(--admin-bg);border-radius:8px">
                <code class="text-accent fw-600">/help</code>
                <p style="margin:4px 0 0;font-size:13px;color:var(--admin-text-secondary)">Справка по командам</p>
            </div>
        </div>
    </div>
</div>

<!-- Тестовое уведомление -->
<div class="admin-card mt-5">
    <div class="admin-card-header">
        <h2 class="admin-card-title">Тестирование</h2>
    </div>
    <div class="admin-card-body">
        <p style="color:var(--admin-text-secondary);margin-bottom:16px">
            Отправьте тестовое уведомление для проверки работы бота
        </p>
        <button class="admin-btn admin-btn-secondary" onclick="sendTestNotification()">
            <i class="bi bi-send"></i> Отправить тестовое уведомление
        </button>
    </div>
</div>
