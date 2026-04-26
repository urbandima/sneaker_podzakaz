<?php

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Плагины и интеграции';
?>
<style>
/* Theme-aware plugin type badges */
.plugin-type-badge.badge-integration { background: var(--admin-info-bg, #e0f2fe); color: var(--admin-info, #0369a1); }
.plugin-type-badge.badge-delivery    { background: var(--admin-warning-bg, #fef9c3); color: var(--admin-warning, #d97706); }
.plugin-type-badge.badge-payment     { background: #dbeafe; color: #1e40af; }
.plugin-type-badge.badge-other       { background: var(--admin-surface-hover, #f3f4f6); color: var(--admin-text-secondary, #6b7280); }
[data-theme="dark"] .plugin-type-badge.badge-payment { background: rgba(59,130,246,.2); color: #93c5fd; }
[data-theme="dark"] .plugin-status.active { background: rgba(16,185,129,.2); color: #34d399; }
</style>

<?php
$this->params['headerActions'] = [];
?>

<h3 style="margin: 1rem 0 1rem 0;">Интеграции</h3>
<div class="plugins-grid">

    <!-- МойСклад -->
    <div class="plugin-card">
        <div class="plugin-header">
            <div class="plugin-icon" style="background:#10b981">
                <i class="bi bi-box-seam"></i>
            </div>
            <span class="plugin-status inactive">Неактивен</span>
        </div>
        <span class="plugin-type-badge badge-integration">Интеграция</span>
        <h3 class="plugin-title">МойСклад</h3>
        <div class="plugin-meta">
            <span><i class="bi bi-tag"></i> v1.0.0</span>
            <span><i class="bi bi-person"></i> СНИКЕРХЭД</span>
        </div>
        <p class="plugin-description">
            Автоматическая синхронизация товаров, заказов и остатков с системой МойСклад.
        </p>
        <div class="plugin-actions">
            <a href="<?= Url::to(['/admin/plugin/moysklad']) ?>" class="admin-btn admin-btn-secondary">
                <i class="bi bi-gear"></i> Настроить
            </a>
        </div>
    </div>

    <!-- AmoCRM -->
    <div class="plugin-card">
        <div class="plugin-header">
            <div class="plugin-icon" style="background:#3b82f6">
                <i class="bi bi-people"></i>
            </div>
            <span class="plugin-status inactive">Неактивен</span>
        </div>
        <span class="plugin-type-badge badge-integration">Интеграция</span>
        <h3 class="plugin-title">AmoCRM</h3>
        <div class="plugin-meta">
            <span><i class="bi bi-tag"></i> v1.0.0</span>
            <span><i class="bi bi-person"></i> СНИКЕРХЭД</span>
        </div>
        <p class="plugin-description">
            Интеграция с CRM-системой. Автоматическое создание сделок и контактов при оформлении заказа.
        </p>
        <div class="plugin-actions">
            <a href="<?= Url::to(['/admin/plugin/amocrm']) ?>" class="admin-btn admin-btn-secondary">
                <i class="bi bi-gear"></i> Настроить
            </a>
        </div>
    </div>

    <!-- Telegram Bot -->
    <div class="plugin-card">
        <div class="plugin-header">
            <div class="plugin-icon" style="background:#0088cc">
                <i class="bi bi-telegram"></i>
            </div>
            <?php $tgToken = Yii::$app->settings->get('telegram', 'bot_token', ''); ?>
            <span class="plugin-status <?= $tgToken ? 'active' : 'inactive' ?>">
                <?= $tgToken ? 'Настроен' : 'Неактивен' ?>
            </span>
        </div>
        <span class="plugin-type-badge badge-integration">Интеграция</span>
        <h3 class="plugin-title">Telegram Bot</h3>
        <div class="plugin-meta">
            <span><i class="bi bi-tag"></i> v1.0.0</span>
            <span><i class="bi bi-person"></i> СНИКЕРХЭД</span>
        </div>
        <p class="plugin-description">
            Уведомления о новых заказах и изменении статусов. Поддержка команд для управления заказами.
        </p>
        <div class="plugin-actions">
            <a href="<?= Url::to(['/admin/plugin/telegram']) ?>" class="admin-btn <?= $tgToken ? 'admin-btn-secondary' : 'admin-btn-primary' ?>">
                <i class="bi bi-gear"></i> <?= $tgToken ? 'Изменить' : 'Настроить' ?>
            </a>
        </div>
    </div>

    <!-- RocketSMS -->
    <?php
    $rsRaw    = Yii::$app->settings->get('plugin', 'rocketsms_config', '');
    $rsConfig = $rsRaw ? (json_decode($rsRaw, true) ?: []) : [];
    $rsActive = !empty($rsConfig['active']) && !empty($rsConfig['username']) && !empty($rsConfig['password']);
    ?>
    <div class="plugin-card <?= $rsActive ? 'active' : '' ?>">
        <div class="plugin-header">
            <div class="plugin-icon" style="background:#ef4444">
                <i class="bi bi-chat-dots-fill"></i>
            </div>
            <span class="plugin-status <?= $rsActive ? 'active' : 'inactive' ?>">
                <?= $rsActive ? 'Активен' : 'Неактивен' ?>
            </span>
        </div>
        <span class="plugin-type-badge badge-integration">SMS</span>
        <h3 class="plugin-title">RocketSMS</h3>
        <div class="plugin-meta">
            <span><i class="bi bi-tag"></i> v1.0.0</span>
            <span><i class="bi bi-person"></i> rocketsms.by</span>
        </div>
        <p class="plugin-description">
            Отправка SMS через RocketSMS.by. Уведомления клиентам о статусе заказа, доставке и оплате.
        </p>
        <div class="plugin-actions">
            <a href="<?= Url::to(['/admin/plugin/rocketsms']) ?>" class="admin-btn <?= $rsActive ? 'admin-btn-secondary' : 'admin-btn-primary' ?>">
                <i class="bi bi-gear"></i> <?= $rsActive ? 'Изменить' : 'Настроить' ?>
            </a>
        </div>
    </div>

</div>

<h3 style="margin: 2rem 0 1rem 0;">Доставка</h3>
<div class="plugins-grid">

    <!-- Таможня:ДП -->
    <?php
    try { $dpConnected = !empty(Yii::$app->dobropost->email ?? ''); } catch (\Exception $e) { $dpConnected = false; }
    ?>
    <div class="plugin-card <?= $dpConnected ? 'active' : '' ?>">
        <div class="plugin-header">
            <div class="plugin-icon" style="background:#8b5cf6"><i class="bi bi-truck"></i></div>
            <span class="plugin-status <?= $dpConnected ? 'active' : 'inactive' ?>"><?= $dpConnected ? 'Подключено' : 'Неактивен' ?></span>
        </div>
        <span class="plugin-type-badge badge-delivery">Доставка</span>
        <h3 class="plugin-title">Таможня:ДП</h3>
        <div class="plugin-meta"><span><i class="bi bi-tag"></i> v1.0.0</span><span><i class="bi bi-person"></i> СНИКЕРХЭД</span></div>
        <p class="plugin-description">Автоматическое создание отправлений, трекинг посылок, вебхуки о статусах доставки.</p>
        <div class="plugin-actions"><a href="<?= Url::to(['/admin/plugin/dobropost']) ?>" class="admin-btn <?= $dpConnected ? 'admin-btn-secondary' : 'admin-btn-primary' ?>"><i class="bi bi-gear"></i> <?= $dpConnected ? 'Изменить' : 'Настроить' ?></a></div>
    </div>

    <!-- Европочта -->
    <?php
    try { $epConfigured = Yii::$app->europochtaTracking->isConfigured(); } catch (\Exception $e) { $epConfigured = false; }
    ?>
    <div class="plugin-card <?= $epConfigured ? 'active' : '' ?>">
        <div class="plugin-header">
            <div class="plugin-icon" style="background:#e11d48"><i class="bi bi-envelope"></i></div>
            <span class="plugin-status <?= $epConfigured ? 'active' : 'inactive' ?>"><?= $epConfigured ? 'Активен' : 'Неактивен' ?></span>
        </div>
        <span class="plugin-type-badge badge-delivery">Доставка</span>
        <h3 class="plugin-title">Европочта</h3>
        <div class="plugin-meta"><span><i class="bi bi-tag"></i> v1.0.0</span><span><i class="bi bi-person"></i> СНИКЕРХЭД</span></div>
        <p class="plugin-description">Трекинг посылок через Европочту Беларуси. Автоматическое обновление статуса доставки для заказов.</p>
        <div class="plugin-actions"><a href="<?= Url::to(['/admin/plugin/europochta']) ?>" class="admin-btn <?= $epConfigured ? 'admin-btn-secondary' : 'admin-btn-primary' ?>"><i class="bi bi-gear"></i> <?= $epConfigured ? 'Изменить' : 'Настроить' ?></a></div>
    </div>

    <!-- Белпочта -->
    <?php
    try { $bpConfigured = Yii::$app->belpochtaTracking->isConfigured(); } catch (\Exception $e) { $bpConfigured = false; }
    ?>
    <div class="plugin-card <?= $bpConfigured ? 'active' : '' ?>">
        <div class="plugin-header">
            <div class="plugin-icon" style="background:#15803d"><i class="bi bi-mailbox"></i></div>
            <span class="plugin-status <?= $bpConfigured ? 'active' : 'inactive' ?>"><?= $bpConfigured ? 'Активен' : 'Неактивен' ?></span>
        </div>
        <span class="plugin-type-badge badge-delivery">Доставка</span>
        <h3 class="plugin-title">Белпочта</h3>
        <div class="plugin-meta"><span><i class="bi bi-tag"></i> v1.0.0</span><span><i class="bi bi-person"></i> СНИКЕРХЭД</span></div>
        <p class="plugin-description">Трекинг отправлений через Белпочту. Поддержка внутренних и международных отправлений.</p>
        <div class="plugin-actions"><a href="<?= Url::to(['/admin/plugin/belpochta']) ?>" class="admin-btn <?= $bpConfigured ? 'admin-btn-secondary' : 'admin-btn-primary' ?>"><i class="bi bi-gear"></i> <?= $bpConfigured ? 'Изменить' : 'Настроить' ?></a></div>
    </div>

    <!-- СДЭК -->
    <?php
    try { $cdekConfigured = Yii::$app->cdekTracking->isConfigured(); } catch (\Exception $e) { $cdekConfigured = false; }
    ?>
    <div class="plugin-card <?= $cdekConfigured ? 'active' : '' ?>">
        <div class="plugin-header">
            <div class="plugin-icon" style="background:#1d4ed8"><i class="bi bi-truck-front"></i></div>
            <span class="plugin-status <?= $cdekConfigured ? 'active' : 'inactive' ?>"><?= $cdekConfigured ? 'Активен' : 'Неактивен' ?></span>
        </div>
        <span class="plugin-type-badge badge-delivery">Доставка</span>
        <h3 class="plugin-title">СДЭК</h3>
        <div class="plugin-meta"><span><i class="bi bi-tag"></i> v1.0.0</span><span><i class="bi bi-person"></i> СНИКЕРХЭД</span></div>
        <p class="plugin-description">Интеграция с СДЭК API v2. Расчёт стоимости, создание заявок и трекинг посылок по всей России и СНГ.</p>
        <div class="plugin-actions"><a href="<?= Url::to(['/admin/plugin/cdek']) ?>" class="admin-btn <?= $cdekConfigured ? 'admin-btn-secondary' : 'admin-btn-primary' ?>"><i class="bi bi-gear"></i> <?= $cdekConfigured ? 'Изменить' : 'Настроить' ?></a></div>
    </div>

</div>

<h3 style="margin: 2rem 0 1rem 0;">Оплата</h3>
<div class="plugins-grid">
    <?php foreach ($plugins as $plugin): ?>
        <?php if ($plugin instanceof \app\infrastructure\plugins\interfaces\PaymentGatewayInterface): ?>
            <div class="plugin-card <?= $plugin->isActive() ? 'active' : '' ?>">
                <div class="plugin-header">
                    <div class="plugin-icon" style="background:#0ea5e9"><i class="bi bi-credit-card"></i></div>
                    <span class="plugin-status <?= $plugin->isActive() ? 'active' : 'inactive' ?>"><?= $plugin->isActive() ? 'Активен' : 'Неактивен' ?></span>
                </div>
                <span class="plugin-type-badge badge-payment">Оплата</span>
                <h3 class="plugin-title"><?= Html::encode($plugin->getName()) ?></h3>
                <div class="plugin-meta">
                    <span><i class="bi bi-tag"></i> v<?= $plugin->getVersion() ?></span>
                    <span><i class="bi bi-person"></i> <?= Html::encode($plugin->getAuthor()) ?></span>
                </div>
                <p class="plugin-description"><?= Html::encode($plugin->getDescription()) ?></p>
                <div class="plugin-actions">
                    <button type="button" class="admin-btn <?= $plugin->isActive() ? 'admin-btn-danger' : 'admin-btn-primary' ?>"
                            onclick="togglePlugin('<?= $plugin->getId() ?>', '<?= $plugin->isActive() ? 'deactivate' : 'activate' ?>')">
                        <i class="bi bi-<?= $plugin->isActive() ? 'x-circle' : 'check-circle' ?>"></i>
                        <?= $plugin->isActive() ? 'Деактивировать' : 'Активировать' ?>
                    </button>
                    <?php if ($plugin->isActive()): ?>
                        <a href="<?= Url::to(['plugin/settings', 'id' => $plugin->getId()]) ?>" class="admin-btn admin-btn-secondary"><i class="bi bi-gear"></i> Настройки</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
</div>

<h3 style="margin: 2rem 0 1rem 0;">Прочие</h3>
<div class="plugins-grid">

    <!-- Курс валют -->
    <div class="plugin-card active">
        <div class="plugin-header">
            <div class="plugin-icon" style="background:#f59e0b"><i class="bi bi-currency-exchange"></i></div>
            <span class="plugin-status active">Активен</span>
        </div>
        <span class="plugin-type-badge badge-other">Прочие</span>
        <h3 class="plugin-title">Курс валют НБРБ</h3>
        <div class="plugin-meta"><span><i class="bi bi-tag"></i> v1.0.0</span><span><i class="bi bi-person"></i> СНИКЕРХЭД</span></div>
        <p class="plugin-description">Автоматическое обновление курса CNY к BYN. Обновление каждые 24 часа с сайта НБРБ.</p>
        <div class="plugin-actions"><a href="<?= Url::to(['/admin/plugin/currency']) ?>" class="admin-btn admin-btn-secondary"><i class="bi bi-gear"></i> Настроить</a></div>
    </div>

</div>
