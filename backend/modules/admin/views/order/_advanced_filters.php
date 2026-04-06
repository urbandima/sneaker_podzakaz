<?php

/**
 * Advanced Filters Component для списка заказов
 * 
 * Рекомендация #87: Advanced Filters
 * 
 * Фильтры по:
 * - Датам (от/до)
 * - Суммам (мин/макс)
 * - Статусам (множественный выбор)
 * - Менеджерам
 * - Способам доставки
 */
?>

<div class="advanced-filters">
    <div class="filters-header" onclick="toggleFilters()">
        <span><i class="bi bi-funnel"></i> Расширенные фильтры</span>
        <i class="bi bi-chevron-down" id="filter-toggle-icon"></i>
    </div>
    
    <div class="filters-body" id="filters-body">
        <form method="GET" action="<?= Url::to(['order/index']) ?>">
            <div class="filters-grid">
                <!-- Период -->
                <div class="filter-group">
                    <label>Период</label>
                    <div class="date-range">
                        <input type="date" name="date_from" value="<?= $filters['date_from'] ?? '' ?>" class="filter-input" placeholder="От">
                        <span>—</span>
                        <input type="date" name="date_to" value="<?= $filters['date_to'] ?? '' ?>" class="filter-input" placeholder="До">
                    </div>
                </div>
                
                <!-- Сумма -->
                <div class="filter-group">
                    <label>Сумма заказа (BYN)</label>
                    <div class="amount-range">
                        <input type="number" name="amount_min" value="<?= $filters['amount_min'] ?? '' ?>" class="filter-input" placeholder="От" min="0" step="0.01">
                        <span>—</span>
                        <input type="number" name="amount_max" value="<?= $filters['amount_max'] ?? '' ?>" class="filter-input" placeholder="До" min="0" step="0.01">
                    </div>
                </div>
                
                <!-- Статусы -->
                <div class="filter-group">
                    <label>Статусы</label>
                    <div class="checkbox-group">
                        <?php 
                        $statuses = [
                            'new' => 'Новый',
                            'paid' => 'Оплачен',
                            'processing' => 'В обработке',
                            'shipped' => 'Отправлен',
                            'delivered' => 'Доставлен',
                            'cancelled' => 'Отменен',
                        ];
                        $selectedStatuses = $filters['status'] ?? [];
                        foreach ($statuses as $key => $label): 
                        ?>
                        <label class="checkbox-label">
                            <input type="checkbox" name="status[]" value="<?= $key ?>" <?= in_array($key, $selectedStatuses) ? 'checked' : '' ?>>
                            <span class="status-badge status-<?= $key ?>"><?= $label ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Менеджер -->
                <div class="filter-group">
                    <label>Менеджер</label>
                    <select name="manager_id" class="filter-select">
                        <option value="">Все</option>
                        <?php foreach ($managers ?? [] as $manager): ?>
                        <option value="<?= $manager->id ?>" <?= ($filters['manager_id'] ?? '') == $manager->id ? 'selected' : '' ?>>
                            <?= Html::encode($manager->getFullName()) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Способ доставки -->
                <div class="filter-group">
                    <label>Способ доставки</label>
                    <select name="delivery_method" class="filter-select">
                        <option value="">Все</option>
                        <option value="courier" <?= ($filters['delivery_method'] ?? '') === 'courier' ? 'selected' : '' ?>>Курьер</option>
                        <option value="pickup" <?= ($filters['delivery_method'] ?? '') === 'pickup' ? 'selected' : '' ?>>Самовывоз</option>
                        <option value="post" <?= ($filters['delivery_method'] ?? '') === 'post' ? 'selected' : '' ?>>Почта</option>
                    </select>
                </div>
                
                <!-- Источник -->
                <div class="filter-group">
                    <label>Источник</label>
                    <select name="source" class="filter-select">
                        <option value="">Все</option>
                        <option value="site" <?= ($filters['source'] ?? '') === 'site' ? 'selected' : '' ?>>Сайт</option>
                        <option value="admin" <?= ($filters['source'] ?? '') === 'admin' ? 'selected' : '' ?>>Админка</option>
                        <option value="api" <?= ($filters['source'] ?? '') === 'api' ? 'selected' : '' ?>>API</option>
                    </select>
                </div>
                
                <!-- Наличие купона -->
                <div class="filter-group">
                    <label>Купон</label>
                    <select name="has_coupon" class="filter-select">
                        <option value="">Все</option>
                        <option value="1" <?= ($filters['has_coupon'] ?? '') === '1' ? 'selected' : '' ?>>С купоном</option>
                        <option value="0" <?= ($filters['has_coupon'] ?? '') === '0' ? 'selected' : '' ?>>Без купона</option>
                    </select>
                </div>
                
                <!-- Поиск -->
                <div class="filter-group wide">
                    <label>Поиск</label>
                    <input type="text" name="search" value="<?= $filters['search'] ?? '' ?>" class="filter-input" placeholder="Номер заказа, email, телефон, имя клиента">
                </div>
            </div>
            
            <div class="filters-actions">
                <button type="submit" class="btn-apply">
                    <i class="bi bi-funnel-fill"></i> Применить
                </button>
                <a href="<?= Url::to(['order/index']) ?>" class="btn-reset">
                    <i class="bi bi-x-circle"></i> Сбросить
                </a>
                
                <?php if (!empty(array_filter($filters ?? []))): ?>
                <span class="active-filters">
                    Активно фильтров: <?= count(array_filter($filters)) ?>
                </span>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>


<?php if (!empty(array_filter($filters ?? []))): ?>
<?php $this->registerJs("document.addEventListener('DOMContentLoaded', function() { if (typeof toggleFilters === 'function') toggleFilters(); });"); ?>
<?php endif; ?>
