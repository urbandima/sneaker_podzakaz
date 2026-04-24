<?php

/** @var yii\web\View $this */

use yii\helpers\Html;

$this->title = 'Настройки';
$company = Yii::$app->settings->getCompany() ?? [];
?>

<div id="admin-settings-config"
    data-save-company-url="<?= \yii\helpers\Url::to(['/admin/settings/save-company']) ?>"
    data-test-telegram-url="<?= \yii\helpers\Url::to(['/admin/settings/test-telegram']) ?>"
    data-test-moysklad-url="<?= \yii\helpers\Url::to(['/admin/settings/test-moysklad']) ?>"
    data-test-amocrm-url="<?= \yii\helpers\Url::to(['/admin/settings/test-amocrm']) ?>"
    data-save-url="<?= \yii\helpers\Url::to(['/admin/settings/save']) ?>"
></div>

<?php
$this->params['headerActions'] = [];
?>

<!-- Быстрые ссылки -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:16px;margin-bottom:24px">
    <a href="<?= \yii\helpers\Url::to(['/admin/settings/statuses']) ?>" class="admin-card" style="text-decoration:none;color:inherit;transition:transform 0.2s" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
        <div style="display:flex;align-items:center;gap:12px">
            <div style="width:48px;height:48px;background:linear-gradient(135deg,#3b82f6,#2563eb);border-radius:12px;display:flex;align-items:center;justify-content:center">
                <i class="bi bi-diagram-3" style="font-size:24px;color:white"></i>
            </div>
            <div>
                <h3 style="margin:0;font-size:16px;font-weight:600">Статусы заказов</h3>
                <p class="sub-text">Настройка цепочки статусов</p>
            </div>
        </div>
    </a>
    
    <a href="<?= \yii\helpers\Url::to(['/admin/plugin']) ?>" class="admin-card" style="text-decoration:none;color:inherit;transition:transform 0.2s" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
        <div style="display:flex;align-items:center;gap:12px">
            <div style="width:48px;height:48px;background:linear-gradient(135deg,#8b5cf6,#7c3aed);border-radius:12px;display:flex;align-items:center;justify-content:center">
                <i class="bi bi-puzzle-fill" style="font-size:24px;color:white"></i>
            </div>
            <div>
                <h3 style="margin:0;font-size:16px;font-weight:600">Плагины и интеграции</h3>
                <p class="sub-text">AmoCRM, МойСклад, Telegram, Таможня:ДП</p>
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
                <p class="sub-text">Мета-теги, sitemap, robots.txt</p>
            </div>
        </div>
    </a>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 1.5rem;">
    <!-- Компания -->
    <div class="admin-card" id="companyCard">
        <div class="admin-card-header">
            <h2 class="admin-card-title">
                <i class="bi bi-building"></i>
                Информация о компании
            </h2>
        </div>
        <div class="admin-card-body">
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
                <label>Банк</label>
                <input type="text" class="form-control" id="co_bank" value="<?= Html::encode($company['bank'] ?? '') ?>" placeholder="ОАО АСБ Беларусбанк">
            </div>
            <div class="form-group">
                <label>БИК</label>
                <input type="text" class="form-control" id="co_bic" value="<?= Html::encode($company['bic'] ?? '') ?>" placeholder="AKBBBY2X">
            </div>
            <div class="form-group">
                <label>Расчётный счёт</label>
                <input type="text" class="form-control" id="co_account" value="<?= Html::encode($company['account'] ?? '') ?>" placeholder="BY00AKBB00000000000000000000">
            </div>

            <button class="admin-btn admin-btn-primary" id="saveCompanyBtn" onclick="saveCompany(this)">
                <i class="bi bi-check-circle"></i>
                Сохранить
            </button>
        </div>
    </div>
    
    <!-- Система -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2 class="admin-card-title">
                <i class="bi bi-gear"></i>
                Системные настройки
            </h2>
        </div>
        <div class="admin-card-body">
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

            <button class="admin-btn admin-btn-primary" onclick="saveSettings(this)">
                <i class="bi bi-check-circle"></i>
                Сохранить
            </button>
        </div>
    </div>
    
    <!-- Безопасность -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2 class="admin-card-title">
                <i class="bi bi-shield-check"></i>
                Безопасность
            </h2>
        </div>
        <div class="admin-card-body">
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

        <div class="mt-5">
            <table class="admin-table">
                <tr>
                    <td class="fw-600">Версия Yii</td>
                    <td><?= Yii::getVersion() ?></td>
                </tr>
                <tr>
                    <td class="fw-600">Версия PHP</td>
                    <td><?= PHP_VERSION ?></td>
                </tr>
                <tr>
                    <td class="fw-600">Окружение</td>
                    <td><?= YII_ENV ?></td>
                </tr>
                <tr>
                    <td class="fw-600">Режим отладки</td>
                    <td><?= YII_DEBUG ? 'Включен' : 'Выключен' ?></td>
                </tr>
                <tr>
                    <td class="fw-600">База данных</td>
                    <td><?= Yii::$app->db->driverName ?></td>
                </tr>
                <tr>
                    <td class="fw-600">Часовой пояс</td>
                    <td><?= date_default_timezone_get() ?></td>
                </tr>
            </table>
        </div>
    </div>

    <!-- PDF Шаблоны -->
    <div class="admin-card">
        <h2 class="admin-card-title">
            <i class="bi bi-file-earmark-pdf"></i>
            PDF шаблоны накладных
        </h2>
        <div class="mt-5">
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
                    <td class="fw-600"><?= Html::encode($label) ?></td>
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
            <div class="mt-4">
                <a href="<?= \yii\helpers\Url::to(['/admin/order/index']) ?>" class="admin-btn admin-btn-secondary fs-sm">
                    <i class="bi bi-printer"></i> Пример: печать накладной из заказа
                </a>
            </div>
        </div>
    </div>

</div>
