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

<style>
.advanced-filters {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    margin-bottom: 24px;
    overflow: hidden;
}

.filters-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 20px;
    cursor: pointer;
    font-weight: 600;
    font-size: 14px;
    transition: background 0.2s;
}

.filters-header:hover {
    background: #f9fafb;
}

.filters-body {
    padding: 0 20px 20px;
    display: none;
}

.filters-body.open {
    display: block;
}

.filters-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.filter-group.wide {
    grid-column: span 2;
}

.filter-group label {
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    color: #6b7280;
}

.filter-input,
.filter-select {
    padding: 10px 12px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.2s;
}

.filter-input:focus,
.filter-select:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.date-range,
.amount-range {
    display: flex;
    align-items: center;
    gap: 8px;
}

.date-range input,
.amount-range input {
    flex: 1;
}

.date-range span,
.amount-range span {
    color: #9ca3af;
    font-size: 14px;
}

.checkbox-group {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
    padding: 4px;
    border-radius: 6px;
    transition: background 0.2s;
}

.checkbox-label:hover {
    background: #f3f4f6;
}

.checkbox-label input {
    display: none;
}

.checkbox-label .status-badge {
    opacity: 0.6;
}

.checkbox-label input:checked + .status-badge {
    opacity: 1;
    box-shadow: 0 0 0 2px #3b82f6;
}

.status-badge {
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.status-new { background: #dbeafe; color: #1e40af; }
.status-paid { background: #d1fae5; color: #065f46; }
.status-processing { background: #fef3c7; color: #92400e; }
.status-shipped { background: #e0e7ff; color: #3730a3; }
.status-delivered { background: #dcfce7; color: #166534; }
.status-cancelled { background: #fee2e2; color: #991b1b; }

.filters-actions {
    display: flex;
    gap: 12px;
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #e5e7eb;
    align-items: center;
}

.btn-apply {
    padding: 10px 20px;
    background: #3b82f6;
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: background 0.2s;
}

.btn-apply:hover {
    background: #2563eb;
}

.btn-reset {
    padding: 10px 20px;
    background: #f3f4f6;
    color: #4b5563;
    text-decoration: none;
    border-radius: 8px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: background 0.2s;
}

.btn-reset:hover {
    background: #e5e7eb;
}

.active-filters {
    margin-left: auto;
    font-size: 13px;
    color: #6b7280;
    background: #f3f4f6;
    padding: 6px 12px;
    border-radius: 20px;
}

@media (max-width: 1024px) {
    .filters-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    .filter-group.wide {
        grid-column: span 2;
    }
}

@media (max-width: 640px) {
    .filters-grid {
        grid-template-columns: 1fr;
    }
    .filter-group.wide {
        grid-column: span 1;
    }
    .filters-actions {
        flex-wrap: wrap;
    }
    .active-filters {
        margin-left: 0;
        width: 100%;
    }
}
</style>

<script>
function toggleFilters() {
    const body = document.getElementById('filters-body');
    const icon = document.getElementById('filter-toggle-icon');
    body.classList.toggle('open');
    icon.classList.toggle('bi-chevron-down');
    icon.classList.toggle('bi-chevron-up');
}

// Открыть если есть активные фильтры
<?php if (!empty(array_filter($filters ?? []))): ?>
document.addEventListener('DOMContentLoaded', function() {
    toggleFilters();
});
<?php endif; ?>
</script>
