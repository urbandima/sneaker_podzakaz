<?php

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Плагины';
?>

<style>
.plugins-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 1.5rem;
}

.plugin-card {
    background: var(--admin-surface);
    border: 1px solid var(--admin-border);
    border-radius: var(--admin-radius-lg);
    padding: 1.5rem;
    transition: all 0.2s;
}

.plugin-card:hover {
    box-shadow: var(--admin-shadow-md);
}

.plugin-card.active {
    border-color: var(--admin-success);
    background: linear-gradient(to bottom, var(--admin-surface), rgba(16, 185, 129, 0.05));
}

.plugin-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: 1rem;
}

.plugin-icon {
    width: 48px;
    height: 48px;
    background: var(--admin-accent);
    border-radius: var(--admin-radius);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
}

.plugin-status {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

.plugin-status.active {
    background: #d1fae5;
    color: #065f46;
}

.plugin-status.inactive {
    background: var(--admin-border-light);
    color: var(--admin-text-secondary);
}

.plugin-title {
    font-size: 1.125rem;
    font-weight: 700;
    margin: 0.5rem 0 0.25rem 0;
}

.plugin-meta {
    display: flex;
    gap: 1rem;
    font-size: 0.75rem;
    color: var(--admin-text-secondary);
    margin-bottom: 0.75rem;
}

.plugin-description {
    font-size: 0.875rem;
    color: var(--admin-text-secondary);
    margin-bottom: 1rem;
    line-height: 1.5;
}

.plugin-actions {
    display: flex;
    gap: 0.5rem;
}

.plugin-type-badge {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    background: var(--admin-border-light);
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.plugin-type-badge.payment {
    background: #dbeafe;
    color: #1e40af;
}

.plugin-type-badge.shipping {
    background: #fef3c7;
    color: #92400e;
}
</style>

<div class="admin-header">
    <h1 class="admin-header-title">
        <i class="bi bi-plugin"></i> Плагины
    </h1>
</div>

<div class="admin-card">
    <p style="margin: 0; color: var(--admin-text-secondary);">
        Управление плагинами для расширения функциональности магазина. Активируйте нужные плагины и настройте их параметры.
    </p>
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
