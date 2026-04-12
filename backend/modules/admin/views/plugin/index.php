<?php

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Плагины и интеграции';
?>

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
        <span class="plugin-type-badge" style="background:#d1fae5;color:#065f46">Интеграция</span>
        <h3 class="plugin-title">МойСклад</h3>
        <div class="plugin-meta">
            <span><i class="bi bi-tag"></i> v1.0.0</span>
            <span><i class="bi bi-person"></i> СНИКЕРХЭД</span>
        </div>
        <p class="plugin-description">
            Автоматическая синхронизация товаров, заказов и остатков с системой МойСклад.
        </p>
        <div class="plugin-actions">
            <a href="<?= Url::to(['/admin/plugin/moysklad']) ?>" class="admin-btn admin-btn-primary">
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
        <span class="plugin-type-badge" style="background:#dbeafe;color:#1e40af">Интеграция</span>
        <h3 class="plugin-title">AmoCRM</h3>
        <div class="plugin-meta">
            <span><i class="bi bi-tag"></i> v1.0.0</span>
            <span><i class="bi bi-person"></i> СНИКЕРХЭД</span>
        </div>
        <p class="plugin-description">
            Интеграция с CRM-системой. Автоматическое создание сделок и контактов при оформлении заказа.
        </p>
        <div class="plugin-actions">
            <a href="<?= Url::to(['/admin/plugin/amocrm']) ?>" class="admin-btn admin-btn-primary">
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
        <span class="plugin-type-badge" style="background:#cfe2ff;color:#084298">Интеграция</span>
        <h3 class="plugin-title">Telegram Bot</h3>
        <div class="plugin-meta">
            <span><i class="bi bi-tag"></i> v1.0.0</span>
            <span><i class="bi bi-person"></i> СНИКЕРХЭД</span>
        </div>
        <p class="plugin-description">
            Уведомления о новых заказах и изменении статусов. Поддержка команд для управления заказами.
        </p>
        <div class="plugin-actions">
            <a href="<?= Url::to(['/admin/plugin/telegram']) ?>" class="admin-btn admin-btn-primary">
                <i class="bi bi-gear"></i> Настроить
            </a>
        </div>
    </div>

    <!-- Курс валют -->
    <div class="plugin-card active">
        <div class="plugin-header">
            <div class="plugin-icon" style="background:#f59e0b">
                <i class="bi bi-currency-exchange"></i>
            </div>
            <span class="plugin-status active">Активен</span>
        </div>
        <span class="plugin-type-badge" style="background:#fef3c7;color:#92400e">Интеграция</span>
        <h3 class="plugin-title">Курс валют НБРБ</h3>
        <div class="plugin-meta">
            <span><i class="bi bi-tag"></i> v1.0.0</span>
            <span><i class="bi bi-person"></i> СНИКЕРХЭД</span>
        </div>
        <p class="plugin-description">
            Автоматическое обновление курса CNY к BYN. Обновление каждые 24 часа с сайта НБРБ.
        </p>
        <div class="plugin-actions">
            <a href="<?= Url::to(['/admin/plugin/currency']) ?>" class="admin-btn admin-btn-secondary">
                <i class="bi bi-gear"></i> Настроить
            </a>
        </div>
    </div>

    <!-- Таможня:ДП -->
    <?php
    try {
        $dpConnected = !empty(Yii::$app->dobropost->email ?? '');
    } catch (\Exception $e) {
        $dpConnected = false;
    }
    ?>
    <div class="plugin-card <?= $dpConnected ? 'active' : '' ?>">
        <div class="plugin-header">
            <div class="plugin-icon" style="background:#8b5cf6">
                <i class="bi bi-truck"></i>
            </div>
            <span class="plugin-status <?= $dpConnected ? 'active' : 'inactive' ?>">
                <?= $dpConnected ? 'Подключено' : 'Неактивен' ?>
            </span>
        </div>
        <span class="plugin-type-badge" style="background:#ede9fe;color:#5b21b6">Интеграция</span>
        <h3 class="plugin-title">Таможня:ДП</h3>
        <div class="plugin-meta">
            <span><i class="bi bi-tag"></i> v1.0.0</span>
            <span><i class="bi bi-person"></i> СНИКЕРХЭД</span>
        </div>
        <p class="plugin-description">
            Автоматическое создание отправлений, трекинг посылок, вебхуки о статусах доставки.
        </p>
        <div class="plugin-actions">
            <a href="<?= Url::to(['/admin/plugin/dobropost']) ?>" class="admin-btn admin-btn-primary">
                <i class="bi bi-gear"></i> Настроить
            </a>
        </div>
    </div>

</div>

<h3 style="margin: 2rem 0 1rem 0;">Платёжные шлюзы</h3>
<div class="plugins-grid">
    <?php foreach ($plugins as $plugin): ?>
        <?php if ($plugin instanceof \app\infrastructure\plugins\interfaces\PaymentGatewayInterface): ?>
            <div class="plugin-card <?= $plugin->isActive() ? 'active' : '' ?>">
                <div class="plugin-header">
                    <div class="plugin-icon">
                        <i class="bi bi-credit-card"></i>
                    </div>
                    <span class="plugin-status <?= $plugin->isActive() ? 'active' : 'inactive' ?>">
                        <?= $plugin->isActive() ? 'Активен' : 'Неактивен' ?>
                    </span>
                </div>
                <span class="plugin-type-badge payment">Платёжный шлюз</span>
                <h3 class="plugin-title"><?= Html::encode($plugin->getName()) ?></h3>
                <div class="plugin-meta">
                    <span><i class="bi bi-tag"></i> v<?= $plugin->getVersion() ?></span>
                    <span><i class="bi bi-person"></i> <?= Html::encode($plugin->getAuthor()) ?></span>
                </div>
                <p class="plugin-description">
                    <?= Html::encode($plugin->getDescription()) ?>
                </p>
                <div class="plugin-actions">
                    <button type="button"
                            class="admin-btn <?= $plugin->isActive() ? 'admin-btn-danger' : 'admin-btn-primary' ?>"
                            onclick="togglePlugin('<?= $plugin->getId() ?>', '<?= $plugin->isActive() ? 'deactivate' : 'activate' ?>')">
                        <i class="bi bi-<?= $plugin->isActive() ? 'x-circle' : 'check-circle' ?>"></i>
                        <?= $plugin->isActive() ? 'Деактивировать' : 'Активировать' ?>
                    </button>
                    <?php if ($plugin->isActive()): ?>
                        <a href="<?= Url::to(['plugin/settings', 'id' => $plugin->getId()]) ?>" class="admin-btn admin-btn-secondary">
                            <i class="bi bi-gear"></i> Настройки
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
</div>

<h3 style="margin: 2rem 0 1rem 0;">Провайдеры доставки</h3>
<div class="plugins-grid">
    <?php foreach ($plugins as $plugin): ?>
        <?php if ($plugin instanceof \app\infrastructure\plugins\interfaces\ShippingProviderInterface): ?>
            <div class="plugin-card <?= $plugin->isActive() ? 'active' : '' ?>">
                <div class="plugin-header">
                    <div class="plugin-icon" style="background: #f59e0b;">
                        <i class="bi bi-truck"></i>
                    </div>
                    <span class="plugin-status <?= $plugin->isActive() ? 'active' : 'inactive' ?>">
                        <?= $plugin->isActive() ? 'Активен' : 'Неактивен' ?>
                    </span>
                </div>
                <span class="plugin-type-badge shipping">Доставка</span>
                <h3 class="plugin-title"><?= Html::encode($plugin->getName()) ?></h3>
                <div class="plugin-meta">
                    <span><i class="bi bi-tag"></i> v<?= $plugin->getVersion() ?></span>
                    <span><i class="bi bi-person"></i> <?= Html::encode($plugin->getAuthor()) ?></span>
                </div>
                <p class="plugin-description">
                    <?= Html::encode($plugin->getDescription()) ?>
                </p>
                <div class="plugin-actions">
                    <button type="button"
                            class="admin-btn <?= $plugin->isActive() ? 'admin-btn-danger' : 'admin-btn-primary' ?>"
                            onclick="togglePlugin('<?= $plugin->getId() ?>', '<?= $plugin->isActive() ? 'deactivate' : 'activate' ?>')">
                        <i class="bi bi-<?= $plugin->isActive() ? 'x-circle' : 'check-circle' ?>"></i>
                        <?= $plugin->isActive() ? 'Деактивировать' : 'Активировать' ?>
                    </button>
                    <?php if ($plugin->isActive()): ?>
                        <a href="<?= Url::to(['plugin/settings', 'id' => $plugin->getId()]) ?>" class="admin-btn admin-btn-secondary">
                            <i class="bi bi-gear"></i> Настройки
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
</div>
