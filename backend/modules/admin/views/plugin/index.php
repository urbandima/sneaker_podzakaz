<?php

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Плагины';
?>


<div class="admin-header" id="plugin-page" data-toggle-url="<?= Url::to(['plugin/toggle']) ?>">
    <h1 class="admin-header-title">
        <i class="bi bi-plugin"></i> Плагины
    </h1>
</div>

<div class="admin-card">
    <p style="margin: 0; color: var(--admin-text-secondary);">
        Управление плагинами для расширения функциональности магазина. Активируйте нужные плагины и настройте их параметры.
    </p>
</div>

<h3 style="margin: 2rem 0 1rem 0;">Интеграции</h3>
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
            Автоматическая синхронизация товаров, заказов и остатков с системой МойСклад. Двусторонняя синхронизация в реальном времени.
        </p>
        <div class="plugin-actions">
            <a href="<?= Url::to(['/admin/settings/integrations']) ?>#moysklad" class="admin-btn admin-btn-primary">
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
            Интеграция с CRM-системой AmoCRM. Автоматическое создание сделок и контактов при оформлении заказа.
        </p>
        <div class="plugin-actions">
            <a href="<?= Url::to(['/admin/settings/integrations']) ?>#amocrm" class="admin-btn admin-btn-primary">
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
            <span class="plugin-status inactive">Неактивен</span>
        </div>
        <span class="plugin-type-badge" style="background:#cfe2ff;color:#084298">Интеграция</span>
        <h3 class="plugin-title">Telegram Bot</h3>
        <div class="plugin-meta">
            <span><i class="bi bi-tag"></i> v1.0.0</span>
            <span><i class="bi bi-person"></i> СНИКЕРХЭД</span>
        </div>
        <p class="plugin-description">
            Уведомления о новых заказах и изменении статусов в Telegram. Поддержка команд для управления заказами.
        </p>
        <div class="plugin-actions">
            <a href="<?= Url::to(['/admin/settings/integrations']) ?>#telegram" class="admin-btn admin-btn-primary">
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
            Автоматическое обновление курса CNY к BYN с сайта Национального банка РБ. Обновление каждые 24 часа.
        </p>
        <div class="plugin-actions">
            <a href="<?= Url::to(['/admin/settings/integrations']) ?>#currency" class="admin-btn admin-btn-secondary">
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

<script>
function togglePlugin(id, action) {
    if (!confirm('Вы уверены?')) return;
    
    fetch('<?= Url::to(['plugin/toggle']) ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
        },
        body: 'id=' + id + '&action=' + action
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message);
        }
    });
}
</script>
