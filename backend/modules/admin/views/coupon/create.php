<?php

use yii\helpers\Html;

$this->title = 'Создать купон';
?>

<?php
$this->params['headerActions'] = [
    Html::a('<i class="bi bi-arrow-left"></i> Назад к списку', ['index'], ['class' => 'admin-btn admin-btn-secondary admin-btn-sm'])
];
?>

<div class="admin-card">
    <h2 class="admin-card-title">
        <i class="bi bi-ticket-perforated"></i>
        Создание купона
    </h2>
    
    <form method="post" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; margin-top: 1.5rem;">
        <input type="hidden" name="_csrf" value="<?= Yii::$app->request->csrfToken ?>">
        
        <!-- Основная информация -->
        <div style="display: flex; flex-direction: column; gap: 1rem;">
            <h3 style="margin: 0; color: var(--admin-text-primary);">Основная информация</h3>
            
            <div class="form-group">
                <label>Код купона *</label>
                <input type="text" name="Coupon[code]" id="coupon-code" class="form-control" 
                       placeholder="SUMMER2024" style="text-transform: uppercase;" required>
                <small class="text-muted">Уникальный код купона (будет преобразован в верхний регистр)</small>
                <button type="button" class="admin-btn admin-btn-secondary mt-2" onclick="generateCouponCode()"
                    data-generate-url="<?= \yii\helpers\Url::to(['generate-code']) ?>">
                    <i class="bi bi-arrow-repeat"></i> Сгенерировать код
                </button>
            </div>
            
            <div class="form-group">
                <label>Название *</label>
                <input type="text" name="Coupon[name]" class="form-control" placeholder="Летняя распродажа" required>
            </div>
            
            <div class="form-group">
                <label>Описание</label>
                <textarea name="Coupon[description]" class="form-control" rows="3" 
                          placeholder="Описание купона для внутреннего использования"></textarea>
            </div>
            
            <div class="form-group">
                <label class="d-flex align-center gap-1">
                    <input type="checkbox" name="Coupon[is_active]" value="1" checked>
                    <span>Активен</span>
                </label>
            </div>
        </div>
        
        <!-- Скидка -->
        <div style="display: flex; flex-direction: column; gap: 1rem;">
            <h3 style="margin: 0; color: var(--admin-text-primary);">Скидка</h3>
            
            <div class="form-group">
                <label>Тип скидки *</label>
                <select name="Coupon[type]" id="coupon-type" class="form-control" onchange="updateDiscountFields(this.value)" required
                    data-free-shipping-value="free_shipping">
                    <option value="">Выберите тип скидки</option>
                    <option value="percentage">Процентная скидка</option>
                    <option value="fixed">Фиксированная сумма</option>
                    <option value="free_shipping">Бесплатная доставка</option>
                </select>
            </div>
            
            <div class="form-group" id="value-field">
                <label>Значение скидки *</label>
                <input type="number" name="Coupon[value]" id="coupon-value" class="form-control" 
                       step="0.01" min="0" placeholder="10" required>
                <small class="text-muted">
                    Для процентной скидки: 10 = 10%, для фиксированной: сумма в BYN
                </small>
            </div>
            
            <div class="form-group">
                <label>Максимальная сумма скидки</label>
                <input type="number" name="Coupon[max_discount]" class="form-control" 
                       step="0.01" min="0" placeholder="100">
                <small class="text-muted">
                    Максимальная сумма скидки (для процентных купонов)
                </small>
            </div>
        </div>
        
        <!-- Условия применения -->
        <div style="display: flex; flex-direction: column; gap: 1rem;">
            <h3 style="margin: 0; color: var(--admin-text-primary);">Условия применения</h3>
            
            <div class="form-group">
                <label>Минимальная сумма заказа</label>
                <input type="number" name="Coupon[min_order_amount]" class="form-control" 
                       step="0.01" min="0" placeholder="50">
                <small class="text-muted">
                    Минимальная сумма заказа для применения купона
                </small>
            </div>
            
            <div class="form-group">
                <label>Максимальное количество использований</label>
                <input type="number" name="Coupon[max_uses]" class="form-control" 
                       min="0" placeholder="100">
                <small class="text-muted">
                    Максимальное количество использований (0 = без ограничений)
                </small>
            </div>
            
            <div class="form-group">
                <label>Использований на одного пользователя</label>
                <input type="number" name="Coupon[max_uses_per_user]" class="form-control" 
                       min="0" placeholder="1">
                <small class="text-muted">
                    Максимальное количество использований на одного пользователя
                </small>
            </div>
            
            <div class="form-group">
                <label class="d-flex align-center gap-1">
                    <input type="checkbox" name="Coupon[is_first_order]" value="1">
                    <span>Только для первого заказа</span>
                </label>
                <small class="text-muted">
                    Купон действует только для первого заказа пользователя
                </small>
            </div>
        </div>
        
        <!-- Срок действия -->
        <div style="display: flex; flex-direction: column; gap: 1rem;">
            <h3 style="margin: 0; color: var(--admin-text-primary);">Срок действия</h3>
            
            <div class="form-group">
                <label>Дата начала действия</label>
                <input type="datetime-local" name="Coupon[valid_from]" class="form-control">
                <small class="text-muted">Дата начала действия купона</small>
            </div>
            
            <div class="form-group">
                <label>Дата окончания действия</label>
                <input type="datetime-local" name="Coupon[valid_until]" class="form-control">
                <small class="text-muted">Дата окончания действия купона</small>
            </div>
        </div>
    </div>
    
    <!-- Применимость к товарам -->
    <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--admin-border);">
        <h3 style="margin: 0 0 1rem 0; color: var(--admin-text-primary);">
            <i class="bi bi-tags"></i>
            Применимость к товарам (опционально)
        </h3>
        
        <p style="color: var(--admin-text-secondary); margin-bottom: 1rem;">
            Если не указано, купон применим ко всем товарам
        </p>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1rem;">
            <div class="form-group">
                <label>ID применимых товаров (через запятую)</label>
                <input type="text" name="applicable_products" class="form-control" placeholder="1,2,3,4,5">
                <small class="text-muted">
                    Купон будет применяться только к указанным товарам
                </small>
            </div>
            
            <div class="form-group">
                <label>ID применимых категорий (через запятую)</label>
                <input type="text" name="applicable_categories" class="form-control" placeholder="1,2,3">
                <small class="text-muted">
                    Купон будет применяться к товарам из указанных категорий
                </small>
            </div>
        </div>
    </div>
    
    <!-- Кнопки действий -->
    <div style="display: flex; gap: 1rem; margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--admin-border);">
        <button type="submit" class="admin-btn admin-btn-primary">
            <i class="bi bi-check-circle"></i>
            Создать купон
        </button>
        <a href="<?= \yii\helpers\Url::to(['index']) ?>" class="admin-btn admin-btn-secondary">
            <i class="bi bi-x-circle"></i>
            Отмена
        </a>
    </div>
    </form>
</div>

