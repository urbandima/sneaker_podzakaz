<?php
use yii\helpers\Html;
use app\frontend\assets\CartAsset;

/** @var yii\web\View $this */
/** @var app\backend\modules\catalog\models\Cart[] $items */
/** @var float $total */
/** @var app\backend\modules\catalog\models\Customer|null $customer */

$this->title = 'Корзина';
$this->params['breadcrumbs'][] = $this->title;

// Подключаем AssetBundle для корзины (все стили автоматически с версионированием)
CartAsset::register($this);
?>

<div class="cart-page cart-blur-surface">
    <div class="container">
        <h1><i class="bi bi-cart3"></i> Корзина</h1>
        
        <?php if (empty($items)): ?>
            <div class="cart-empty">
                <i class="bi bi-cart-x"></i>
                <h2>Корзина пуста</h2>
                <p>Добавьте товары из каталога</p>
                <a href="/catalog" class="btn-catalog">Перейти в каталог</a>
            </div>
        <?php else: ?>
            <div class="cart-layout">
                <div class="cart-items">
                    <?php foreach ($items as $item): ?>
                        <div class="cart-item" data-cart-id="<?= $item->id ?>">
                            <div class="item-image">
                                <img src="<?= $item->product->getMainImageUrl() ?>" alt="<?= Html::encode($item->product->name) ?>">
                            </div>
                            
                            <div class="item-info">
                                <div class="item-brand"><?= Html::encode($item->product->brand->name) ?></div>
                                <h3 class="item-name"><?= Html::encode($item->product->name) ?></h3>
                                
                                <?php if ($item->size): ?>
                                    <div class="item-size">Размер: <?= Html::encode($item->size) ?></div>
                                <?php endif; ?>
                                
                                <?php if ($item->color): ?>
                                    <div class="item-color">Цвет: <?= Html::encode($item->color) ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="item-quantity">
                                <button onclick="updateCartItem(<?= $item->id ?>, <?= $item->quantity - 1 ?>)" <?= $item->quantity <= 1 ? 'disabled' : '' ?>>
                                    <i class="bi bi-dash"></i>
                                </button>
                                <input type="number" value="<?= $item->quantity ?>" min="1" max="99" 
                                       onchange="updateCartItem(<?= $item->id ?>, this.value)">
                                <button onclick="updateCartItem(<?= $item->id ?>, <?= $item->quantity + 1 ?>)">
                                    <i class="bi bi-plus"></i>
                                </button>
                            </div>
                            
                            <div class="item-price">
                                <span class="subtotal"><?= Yii::$app->formatter->asCurrency($item->getSubtotal(), 'BYN') ?></span>
                                <span class="price-per-item"><?= Yii::$app->formatter->asCurrency($item->price, 'BYN') ?> × <?= $item->quantity ?></span>
                            </div>
                            
                            <button class="item-remove" onclick="removeCartItem(<?= $item->id ?>)">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Информация о ценах -->
                <div class="cart-summary">
                    <!-- Блок промокода -->
                    <div class="promo-code-section">
                        <h4><i class="bi bi-ticket-perforated"></i> Есть промокод?</h4>
                        <div class="promo-input-group">
                            <input type="text" id="promoCodeInput" placeholder="Введите код" class="promo-input">
                            <button class="btn-promo-apply" onclick="applyPromoCode()">
                                Применить
                            </button>
                        </div>
                        <div id="promoApplied" class="promo-applied" style="display: none;">
                            <div class="promo-success">
                                <i class="bi bi-check-circle-fill"></i>
                                <span id="promoCodeText"></span>
                            </div>
                            <div class="promo-discount">
                                Скидка: <span id="promoDiscountAmount"></span>
                            </div>
                            <button class="promo-remove" onclick="removePromoCode()">
                                <i class="bi bi-x"></i> Удалить
                            </button>
                        </div>
                        <div id="promoError" class="promo-error" style="display: none;"></div>
                    </div>
                    
                    <?php if ($customer): ?>
                    <!-- Блок баллов лояльности -->
                    <div class="loyalty-points-section">
                        <h4><i class="bi bi-star-fill"></i> Баллы лояльности</h4>
                        <div class="points-balance">
                            Ваш баланс: <strong id="loyaltyBalance">0</strong> баллов
                        </div>
                        <div class="points-info">
                            Можно оплатить до 50% заказа
                        </div>
                        <div class="points-slider-container">
                            <input type="range" id="pointsSlider" min="0" max="0" value="0" 
                                   class="points-slider" oninput="updatePointsRedeem(this.value)">
                            <div class="points-slider-labels">
                                <span>0</span>
                                <span id="maxPointsLabel">0</span>
                            </div>
                        </div>
                        <div class="points-redeem-info">
                            <span>Скидка: </span>
                            <strong id="pointsDiscount">0 BYN</strong>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="cart-summary-info">
                        <h2>Итого</h2>
                        
                        <div class="summary-row">
                            <span>Товары (<?= count($items) ?>):</span>
                            <span class="cart-total" id="productsTotal"><?= Yii::$app->formatter->asCurrency($total, 'BYN') ?></span>
                        </div>
                        
                        <div class="summary-row discount-row" id="promoDiscountRow" style="display: none;">
                            <span>Скидка по промокоду:</span>
                            <span class="discount-value" id="promoDiscountValue">-0 BYN</span>
                        </div>
                        
                        <div class="summary-row discount-row" id="pointsDiscountRow" style="display: none;">
                            <span>Оплата баллами:</span>
                            <span class="discount-value" id="pointsDiscountValue">-0 BYN</span>
                        </div>
                        
                        <div class="summary-row">
                            <span>Доставка:</span>
                            <span id="deliveryCost"><?= $total >= 100 ? 'Бесплатно' : Yii::$app->formatter->asCurrency(10, 'BYN') ?></span>
                        </div>
                        
                        <div class="summary-total">
                            <span>Всего:</span>
                            <span class="cart-total" id="finalTotal"><?= Yii::$app->formatter->asCurrency($total >= 100 ? $total : $total + 10, 'BYN') ?></span>
                        </div>
                        
                        <?php if ($total < 100): ?>
                            <div class="delivery-info">
                                <i class="bi bi-truck"></i>
                                До бесплатной доставки: <span id="toFreeDelivery"><?= Yii::$app->formatter->asCurrency(100 - $total, 'BYN') ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Кнопки действий -->
                    <div class="cart-summary-actions">
                        <button class="btn-checkout" onclick="openCheckoutModal()">
                            <i class="bi bi-check-circle"></i>
                            Оформить заказ
                        </button>
                        
                        <a href="/catalog" class="btn-continue">Продолжить покупки</a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
* {margin: 0; padding: 0; box-sizing: border-box;}
.cart-page {
    background: #f9fafb;
    min-height: 100vh;
    padding: 2rem 0;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.container {
    width: 100%;
    max-width: 100%;
    margin: 0 auto;
    padding: 0 1rem;
}

.cart-blur-surface {
    transition: filter 0.3s ease;
}

body.modal-blur-active {
    overflow: hidden;
}

body.modal-blur-active .cart-blur-surface {
    filter: blur(12px);
    pointer-events: none;
}

h1 {
    font-size: 2rem;
    font-weight: 800;
    margin-bottom: 2rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

/* Empty Cart */
.cart-empty {
    text-align: center;
    padding: 4rem 2rem;
    background: #fff;
    border-radius: 16px;
}

.cart-empty i {
    font-size: 5rem;
    color: #d1d5db;
    display: block;
    margin-bottom: 1rem;
}

.cart-empty h2 {
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.cart-empty p {
    color: #666;
    margin-bottom: 2rem;
}

.btn-catalog {
    display: inline-block;
    background: #000;
    color: #fff;
    padding: 1rem 2rem;
    border-radius: 10px;
    text-decoration: none;
    font-weight: 700;
    transition: all 0.2s;
}

.btn-catalog:hover {
    background: #333;
    transform: translateY(-2px);
}

/* Cart Layout */
.cart-layout {
    display: grid;
    grid-template-columns: 1fr 380px;
    gap: 1.5rem;
}

/* Cart Items */
.cart-items {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.cart-item {
    background: #fff;
    border-radius: 16px;
    padding: 1.5rem;
    display: grid;
    grid-template-columns: 100px 1fr auto auto auto;
    gap: 1.5rem;
    align-items: center;
    transition: all 0.2s;
}

.cart-item:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

.item-image {
    width: 100px;
    height: 100px;
    border-radius: 12px;
    overflow: hidden;
    background: #f9fafb;
}

.item-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.item-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.item-brand {
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    color: #666;
    letter-spacing: 0.5px;
}

.item-name {
    font-size: 1rem;
    font-weight: 600;
    line-height: 1.3;
}

.item-size,
.item-color {
    font-size: 0.875rem;
    color: #666;
}

.item-quantity {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: #f9fafb;
    border-radius: 10px;
    padding: 0.5rem;
}

.item-quantity button {
    width: 32px;
    height: 32px;
    border: none;
    background: #fff;
    border-radius: 6px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.875rem;
    transition: all 0.2s;
}

.item-quantity button:hover:not(:disabled) {
    background: #e5e7eb;
}

.item-quantity button:disabled {
    opacity: 0.3;
    cursor: not-allowed;
}

.item-quantity input {
    width: 50px;
    text-align: center;
    border: none;
    background: transparent;
    font-weight: 700;
    font-size: 1rem;
}

.item-price {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 0.25rem;
    min-width: 120px;
}

.subtotal {
    font-size: 1.25rem;
    font-weight: 800;
    color: #000;
}

.price-per-item {
    font-size: 0.75rem;
    color: #999;
}

.item-remove {
    width: 40px;
    height: 40px;
    border: none;
    background: #fef2f2;
    color: #ef4444;
    border-radius: 10px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.item-remove:hover {
    background: #fee2e2;
}

/* Cart Summary */
.cart-summary {
    background: #fff;
    border-radius: 16px;
    padding: 2rem;
    height: fit-content;
    position: sticky;
    top: 2rem;
}

.cart-summary-info {
    /* Информация о ценах */
}

.cart-summary-actions {
    /* Кнопки действий */
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.cart-summary h2 {
    font-size: 1.25rem;
    font-weight: 800;
    margin-bottom: 1.5rem;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    padding: 0.75rem 0;
    border-bottom: 1px solid #f3f4f6;
    font-size: 0.9375rem;
}

.summary-total {
    display: flex;
    justify-content: space-between;
    padding: 1.5rem 0;
    font-size: 1.25rem;
    font-weight: 800;
    border-top: 2px solid #e5e7eb;
    margin-top: 1rem;
}

.delivery-info {
    background: #ecfdf5;
    color: #10b981;
    padding: 1rem;
    border-radius: 10px;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    font-weight: 600;
    margin-top: 1rem;
}

.btn-checkout {
    width: 100%;
    padding: 1.25rem;
    background: #000;
    color: #fff;
    border: none;
    border-radius: 12px;
    font-size: 1.125rem;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.625rem;
    margin-top: 1.5rem;
    transition: all 0.2s;
}

.btn-checkout:hover {
    background: #333;
    transform: translateY(-2px);
}

.btn-continue {
    display: block;
    text-align: center;
    padding: 1rem;
    color: #666;
    text-decoration: none;
    font-weight: 600;
    margin-top: 1rem;
    border-radius: 10px;
    transition: all 0.2s;
}

.btn-continue:hover {
    background: #f9fafb;
    color: #000;
}

/* Promo Code Section */
.promo-code-section {
    background: #f8fafc;
    border-radius: 12px;
    padding: 1.25rem;
    margin-bottom: 1rem;
}

.promo-code-section h4 {
    font-size: 0.9375rem;
    font-weight: 600;
    margin-bottom: 0.75rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #374151;
}

.promo-code-section h4 i {
    color: #f59e0b;
}

.promo-input-group {
    display: flex;
    gap: 0.5rem;
}

.promo-input {
    flex: 1;
    padding: 0.75rem 1rem;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    font-size: 0.9375rem;
    transition: all 0.2s;
}

.promo-input:focus {
    outline: none;
    border-color: #3b82f6;
}

.btn-promo-apply {
    padding: 0.75rem 1.25rem;
    background: #3b82f6;
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-promo-apply:hover {
    background: #2563eb;
}

.promo-applied {
    margin-top: 1rem;
    padding: 1rem;
    background: #ecfdf5;
    border-radius: 8px;
    border: 1px solid #10b981;
}

.promo-success {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #059669;
    font-weight: 600;
}

.promo-success i {
    font-size: 1.25rem;
}

.promo-discount {
    margin-top: 0.5rem;
    color: #047857;
    font-size: 0.875rem;
}

.promo-remove {
    margin-top: 0.75rem;
    padding: 0.5rem 1rem;
    background: transparent;
    border: 1px solid #ef4444;
    color: #ef4444;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.875rem;
    transition: all 0.2s;
}

.promo-remove:hover {
    background: #fef2f2;
}

.promo-error {
    margin-top: 0.75rem;
    padding: 0.75rem;
    background: #fef2f2;
    border-radius: 6px;
    color: #dc2626;
    font-size: 0.875rem;
}

/* Loyalty Points Section */
.loyalty-points-section {
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    border-radius: 12px;
    padding: 1.25rem;
    margin-bottom: 1rem;
}

.loyalty-points-section h4 {
    font-size: 0.9375rem;
    font-weight: 600;
    margin-bottom: 0.75rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #92400e;
}

.loyalty-points-section h4 i {
    color: #f59e0b;
}

.points-balance {
    font-size: 0.9375rem;
    color: #78350f;
    margin-bottom: 0.5rem;
}

.points-balance strong {
    color: #92400e;
    font-size: 1.125rem;
}

.points-info {
    font-size: 0.8125rem;
    color: #a16207;
    margin-bottom: 1rem;
}

.points-slider-container {
    margin-bottom: 0.75rem;
}

.points-slider {
    width: 100%;
    height: 6px;
    border-radius: 3px;
    background: #fcd34d;
    outline: none;
    -webkit-appearance: none;
}

.points-slider::-webkit-slider-thumb {
    -webkit-appearance: none;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: #f59e0b;
    cursor: pointer;
    box-shadow: 0 2px 6px rgba(0,0,0,0.2);
}

.points-slider-labels {
    display: flex;
    justify-content: space-between;
    font-size: 0.75rem;
    color: #92400e;
    margin-top: 0.25rem;
}

.points-redeem-info {
    font-size: 0.9375rem;
    color: #78350f;
}

.points-redeem-info strong {
    color: #92400e;
}

/* Discount rows */
.discount-row {
    color: #059669 !important;
}

.discount-value {
    color: #059669 !important;
    font-weight: 600;
}

/* Tablet and below */
@media (max-width: 1024px) {
    .cart-layout {
        grid-template-columns: 1fr 320px;
        gap: 1.25rem;
    }
    
    .cart-summary {
        padding: 1.5rem;
    }
}

/* Mobile landscape and below */
@media (max-width: 768px) {
    .cart-page {
        padding: 1rem 0;
    }
    
    .container {
        padding: 0 0.75rem;
    }
    
    h1 {
        font-size: 1.5rem;
        margin-bottom: 1.5rem;
        gap: 0.5rem;
    }
    
    /* Single column layout */
    .cart-layout {
        grid-template-columns: 1fr;
        gap: 0;
    }
    
    /* Summary - обычный блок на мобильных */
    .cart-summary {
        position: static;
        background: transparent;
        padding: 0;
        border-radius: 0;
        box-shadow: none;
    }
    
    /* Информация о ценах - карточка в потоке */
    .cart-summary-info {
        background: #fff;
        border-radius: 16px;
        padding: 1.25rem 1rem;
        margin-top: 0.75rem;
        margin-bottom: 10rem;
    }
    
    /* Compact summary info */
    .cart-summary-info h2 {
        font-size: 1rem;
        margin-bottom: 1rem;
    }
    
    .summary-row {
        font-size: 0.875rem;
        padding: 0.5rem 0;
    }
    
    .summary-total {
        font-size: 1.125rem;
        padding: 1rem 0;
        margin-top: 0.75rem;
    }
    
    .delivery-info {
        font-size: 0.8125rem;
        padding: 0.75rem;
        margin-top: 0.75rem;
    }
    
    /* Кнопки действий - FIXED внизу экрана */
    .cart-summary-actions {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        background: #fff;
        padding: 1rem;
        box-shadow: 0 -4px 12px rgba(0,0,0,0.1);
        border-radius: 16px 16px 0 0;
        z-index: 1000;
        gap: 0.5rem;
    }
    
    .btn-checkout {
        padding: 1rem;
        font-size: 1rem;
        margin-top: 0;
    }
    
    .btn-continue {
        padding: 0.875rem;
        margin-top: 0;
        font-size: 0.9375rem;
    }
    
    /* Add space for fixed actions panel */
    .cart-items {
        padding-bottom: 0;
        gap: 0.875rem;
    }
    
    /* Mobile card layout - vertical stack */
    .cart-item {
        grid-template-columns: 1fr;
        padding: 1rem;
        gap: 1rem;
        position: relative;
    }
    
    /* Image and info row */
    .item-image {
        width: 80px;
        height: 80px;
        float: left;
        margin-right: 0.875rem;
    }
    
    .item-info {
        overflow: hidden;
        padding-bottom: 0.5rem;
    }
    
    .item-brand {
        font-size: 0.6875rem;
    }
    
    .item-name {
        font-size: 0.9375rem;
    }
    
    .item-size,
    .item-color {
        font-size: 0.8125rem;
    }
    
    /* Quantity and price row */
    .item-quantity {
        clear: both;
        width: fit-content;
        padding: 0.625rem;
    }
    
    .item-quantity button {
        width: 36px;
        height: 36px;
        font-size: 1rem;
    }
    
    .item-quantity input {
        width: 45px;
        font-size: 0.9375rem;
    }
    
    .item-price {
        position: absolute;
        top: 1rem;
        right: 1rem;
        min-width: auto;
    }
    
    .subtotal {
        font-size: 1.125rem;
    }
    
    .price-per-item {
        font-size: 0.6875rem;
    }
    
    /* Remove button - bigger touch target */
    .item-remove {
        position: absolute;
        bottom: 1rem;
        right: 1rem;
        width: 44px;
        height: 44px;
        font-size: 1rem;
    }
    
    /* Empty cart */
    .cart-empty {
        padding: 3rem 1.5rem;
    }
    
    .cart-empty i {
        font-size: 4rem;
    }
    
    .cart-empty h2 {
        font-size: 1.25rem;
    }
    
    .btn-catalog {
        padding: 0.875rem 1.5rem;
        font-size: 0.9375rem;
    }
}

/* Mobile portrait and small devices */
@media (max-width: 480px) {
    .cart-page {
        padding: 0.75rem 0;
    }
    
    h1 {
        font-size: 1.375rem;
        margin-bottom: 1.25rem;
    }
    
    .cart-items {
        gap: 0.75rem;
        padding-bottom: 0;
    }
    
    .cart-item {
        padding: 0.875rem;
    }
    
    .item-image {
        width: 70px;
        height: 70px;
        margin-right: 0.75rem;
    }
    
    .item-brand {
        font-size: 0.625rem;
    }
    
    .item-name {
        font-size: 0.875rem;
        line-height: 1.2;
    }
    
    .item-size,
    .item-color {
        font-size: 0.75rem;
    }
    
    .item-quantity {
        padding: 0.5rem;
    }
    
    .item-quantity button {
        width: 32px;
        height: 32px;
    }
    
    .item-quantity input {
        width: 40px;
        font-size: 0.875rem;
    }
    
    .item-price {
        top: 0.875rem;
        right: 0.875rem;
    }
    
    .subtotal {
        font-size: 1rem;
    }
    
    .price-per-item {
        font-size: 0.625rem;
    }
    
    .item-remove {
        bottom: 0.875rem;
        right: 0.875rem;
        width: 40px;
        height: 40px;
    }
    
    /* Compact summary info */
    .cart-summary-info {
        padding: 1rem 0.875rem;
        margin-top: 0.625rem;
        margin-bottom: 9rem;
    }
    
    .cart-summary-info h2 {
        font-size: 0.9375rem;
        margin-bottom: 0.875rem;
    }
    
    .summary-row {
        font-size: 0.8125rem;
        padding: 0.375rem 0;
    }
    
    .summary-total {
        font-size: 1rem;
        padding: 0.875rem 0;
        margin-top: 0.5rem;
    }
    
    .delivery-info {
        font-size: 0.75rem;
        padding: 0.625rem;
        margin-top: 0.625rem;
    }
    
    /* Compact actions panel */
    .cart-summary-actions {
        padding: 0.875rem;
        gap: 0.5rem;
    }
    
    .btn-checkout {
        padding: 0.875rem;
        font-size: 0.9375rem;
    }
    
    .btn-continue {
        padding: 0.75rem;
        font-size: 0.875rem;
    }
    
    .cart-empty {
        padding: 2.5rem 1rem;
        border-radius: 12px;
    }
    
    .cart-empty i {
        font-size: 3.5rem;
        margin-bottom: 0.75rem;
    }
    
    .cart-empty h2 {
        font-size: 1.125rem;
    }
    
    .cart-empty p {
        font-size: 0.9375rem;
        margin-bottom: 1.5rem;
    }
    
    .btn-catalog {
        padding: 0.75rem 1.25rem;
        font-size: 0.875rem;
        border-radius: 8px;
    }
}
</style>

<!-- Checkout Modal -->
<div class="checkout-modal" id="checkoutModal">
    <div class="modal-overlay" onclick="closeCheckoutModal()"></div>
    <div class="modal-content">
        <button class="modal-close" onclick="closeCheckoutModal()"><i class="bi bi-x-lg"></i></button>
        
        <h2><i class="bi bi-bag-check"></i> Оформление заказа</h2>
        
        <form id="checkoutForm" onsubmit="submitOrder(event)">
            <?php if ($customer): ?>
                <input type="hidden" name="customer_id" value="<?= $customer->id ?>">
            <?php endif; ?>
            
            <div class="checkout-grid">
                <!-- Левая колонка -->
                <div class="checkout-left">
                    <?php if ($customer): ?>
                        <!-- Переключатель режимов для авторизованных -->
                        <div class="form-mode-switcher">
                            <button type="button" class="mode-btn active" onclick="switchFormMode('profile')">
                                <i class="bi bi-person-check"></i> Данные профиля
                            </button>
                            <button type="button" class="mode-btn" onclick="switchFormMode('custom')">
                                <i class="bi bi-pencil"></i> Другие данные
                            </button>
                        </div>
                        
                        <!-- Сохранённые адреса -->
                        <div class="saved-addresses-section" id="savedAddressesSection">
                            <h3>Выберите адрес доставки</h3>
                            <div class="saved-addresses">
                                <?php if ($customer->default_address): ?>
                                    <label class="saved-address-card active">
                                        <input type="radio" name="saved_address" value="default" checked>
                                        <div class="address-content">
                                            <div class="address-header">
                                                <i class="bi bi-house-door"></i>
                                                <strong>Основной адрес</strong>
                                            </div>
                                            <div class="address-details">
                                                <?= Html::encode($customer->default_country ?? 'Беларусь') ?>, 
                                                <?= Html::encode($customer->default_city ?? '') ?><br>
                                                <?= Html::encode($customer->default_address) ?>
                                                <?php if ($customer->default_postal_code): ?>
                                                    , <?= Html::encode($customer->default_postal_code) ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </label>
                                <?php endif; ?>
                                <label class="saved-address-card">
                                    <input type="radio" name="saved_address" value="new">
                                    <div class="address-content">
                                        <div class="address-header">
                                            <i class="bi bi-plus-circle"></i>
                                            <strong>Новый адрес</strong>
                                        </div>
                                        <div class="address-details">Указать другой адрес доставки</div>
                                    </div>
                                </label>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="form-section" id="contactDataSection">
                        <h3>Контактные данные</h3>
                        <div class="form-group">
                            <label>ФИО *</label>
                            <input type="text" name="name" id="nameField" required placeholder="Иванов Иван Иванович" 
                                   value="<?= $customer ? Html::encode($customer->getFullName()) : '' ?>">
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Телефон *</label>
                                <input type="tel" name="phone" id="phoneField" required placeholder="+375 (__)___-__-__" 
                                       value="<?= $customer ? Html::encode($customer->phone) : '' ?>">
                            </div>
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="email" id="emailField" placeholder="email@example.com" 
                                       value="<?= $customer ? Html::encode($customer->email) : '' ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h3>Доставка</h3>
                        
                        <!-- Выбор страны -->
                        <div class="country-tabs">
                            <button type="button" class="country-tab active" onclick="selectCountry('belarus', event)">
                                <i class="bi bi-geo-alt-fill"></i> Беларусь
                            </button>
                            <button type="button" class="country-tab" onclick="selectCountry('russia', event)">
                                <i class="bi bi-geo-alt-fill"></i> Россия
                            </button>
                        </div>
                        
                        <input type="hidden" name="country" id="selectedCountry" value="belarus">
                        
                        <!-- Способы доставки для Беларуси -->
                        <div class="delivery-options" id="deliveryBelarus">
                            <label class="delivery-option">
                                <input type="radio" name="delivery" value="pickup_minsk" checked>
                                <div class="option-content">
                                    <div>
                                        <strong>Самовывоз</strong>
                                        <small>Минск, пр.Победителей 5 (около Альфа-Банк)</small>
                                    </div>
                                    <span class="price-tag">Бесплатно</span>
                                </div>
                            </label>
                            <label class="delivery-option">
                                <input type="radio" name="delivery" value="courier_minsk">
                                <div class="option-content">
                                    <div>
                                        <strong>Курьер Минск</strong>
                                        <small>Доставка по городу</small>
                                    </div>
                                    <span class="price-tag"><?= Yii::$app->formatter->asCurrency(10, 'BYN') ?></span>
                                </div>
                            </label>
                            <label class="delivery-option">
                                <input type="radio" name="delivery" value="europochta">
                                <div class="option-content">
                                    <div>
                                        <strong>Европочта</strong>
                                        <small>Доставка по Беларуси</small>
                                    </div>
                                    <span class="price-tag"><?= Yii::$app->formatter->asCurrency(5, 'BYN') ?></span>
                                </div>
                            </label>
                            <label class="delivery-option">
                                <input type="radio" name="delivery" value="belpochta">
                                <div class="option-content">
                                    <div>
                                        <strong>Белпочта</strong>
                                        <small>Доставка по Беларуси</small>
                                    </div>
                                    <span class="price-tag"><?= Yii::$app->formatter->asCurrency(4, 'BYN') ?></span>
                                </div>
                            </label>
                        </div>
                        
                        <!-- Способы доставки для России -->
                        <div class="delivery-options" id="deliveryRussia" style="display: none;">
                            <label class="delivery-option">
                                <input type="radio" name="delivery" value="sdek">
                                <div class="option-content">
                                    <div>
                                        <strong>СДЭК</strong>
                                        <small>Доставка по России</small>
                                    </div>
                                    <span class="price-tag">Рассчитывается по тарифам СДЭК</span>
                                </div>
                            </label>
                        </div>
                        
                        <div class="form-group" id="addressFieldGroup" style="margin-top: 1rem;<?= $customer && $customer->default_address ? ' display: none;' : '' ?>">
                            <label id="addressLabel">Адрес доставки *</label>
                            <textarea name="address" id="addressField" rows="2" placeholder="Укажите адрес доставки"></textarea>
                        </div>
                    </div>
                    
                    <?php if ($customer): ?>
                        <!-- Опция сохранения данных -->
                        <div class="form-section" id="saveDataSection" style="display: none;">
                            <label class="checkbox-label">
                                <input type="checkbox" name="save_to_profile" id="saveToProfile">
                                <span>Сохранить эти данные в профиль</span>
                            </label>
                        </div>
                    <?php endif; ?>
                    
                    <div class="form-section">
                        <h3>Комментарий к заказу</h3>
                        <div class="form-group">
                            <textarea name="comment" rows="2" placeholder="Дополнительные пожелания"></textarea>
                        </div>
                    </div>
                </div>
                
                <!-- Правая колонка - Итого -->
                <div class="checkout-right">
                    <div class="modal-summary">
                        <h3>Ваш заказ</h3>
                        <div class="summary-row">
                            <span>Товары:</span>
                            <span class="goods-cost"><?= Yii::$app->formatter->asCurrency($total, 'BYN') ?></span>
                        </div>
                        <div class="summary-row">
                            <span>Доставка:</span>
                            <span class="delivery-cost">Бесплатно</span>
                        </div>
                        <div class="summary-total">
                            <span>Итого:</span>
                            <span class="total-cost"><?= Yii::$app->formatter->asCurrency($total, 'BYN') ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Кнопка подтверждения - закреплена внизу -->
            <div class="checkout-footer">
                <button type="submit" class="btn-submit-order">
                    <i class="bi bi-check-circle-fill"></i>
                    Подтвердить заказ
                </button>
            </div>
        </form>
    </div>
</div>

<style>
/* Checkout Modal */
.checkout-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 9999;
    align-items: center;
    justify-content: center;
}

.checkout-modal.active {
    display: flex;
}

.modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.6);
    -webkit-backdrop-filter: blur(8px);
    backdrop-filter: blur(8px);
}

.modal-content {
    position: relative;
    background: #fff;
    border-radius: 20px;
    width: 95%;
    max-width: 1100px;
    max-height: 95vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    animation: modalSlideIn 0.3s ease-out;
}

.modal-content h2 {
    padding: 1.5rem 2rem 0;
}

.modal-content form {
    flex: 1;
    overflow-y: auto;
    padding: 0 2rem;
    display: flex;
    flex-direction: column;
}

@keyframes modalSlideIn {
    from {
        opacity: 0;
        transform: translateY(-30px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.modal-close {
    position: absolute;
    top: 1rem;
    right: 1rem;
    width: 40px;
    height: 40px;
    border: none;
    background: #f3f4f6;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.modal-close:hover {
    background: #e5e7eb;
    transform: rotate(90deg);
}

.modal-content h2 {
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.modal-content h2 i {
    font-size: 1.25rem;
}

/* Двухколоночная сетка */
.checkout-grid {
    display: grid;
    grid-template-columns: 1fr 350px;
    gap: 1.5rem;
}

.checkout-left {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}

.checkout-right {
    display: flex;
    flex-direction: column;
}

/* Футер с кнопкой подтверждения */
.checkout-footer {
    padding: 1rem 2rem;
    background: #fff;
    border-top: 1px solid #e5e7eb;
    position: sticky;
    bottom: 0;
    z-index: 10;
}

.form-section {
    margin-bottom: 0;
}

.form-section h3 {
    font-size: 0.9375rem;
    font-weight: 600;
    margin-bottom: 0.625rem;
    color: #374151;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.75rem;
}

.form-group {
    margin-bottom: 0.625rem;
}

.form-group label {
    display: block;
    font-size: 0.75rem;
    font-weight: 600;
    margin-bottom: 0.3rem;
    color: #374151;
}

.form-group input,
.form-group textarea {
    width: 100%;
    padding: 0.625rem 0.75rem;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    font-size: 0.875rem;
    transition: all 0.2s;
    font-family: inherit;
}

.form-group input:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #000;
    box-shadow: 0 0 0 3px rgba(0,0,0,0.05);
}


.form-group textarea {
    resize: vertical;
    min-height: 50px;
}

.delivery-options {
    display: grid;
    gap: 0.375rem;
    margin-bottom: 0;
}

/* Выбор страны */
.country-tabs {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.375rem;
    margin-bottom: 0.75rem;
}

.country-tab {
    padding: 0.625rem 0.875rem;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    background: #fff;
    font-weight: 600;
    font-size: 0.875rem;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.375rem;
}

.country-tab:hover {
    border-color: #d1d5db;
    background: #f9fafb;
}

.country-tab.active {
    border-color: #000;
    background: #000;
    color: #fff;
}

.country-tab i {
    font-size: 0.875rem;
}

.delivery-option {
    display: block;
    cursor: pointer;
}

.delivery-option input {
    display: none;
}

.option-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.625rem 0.875rem;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    transition: all 0.2s;
}

.option-content > div {
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
}

.option-content small {
    font-size: 0.6875rem;
    color: #6b7280;
    font-weight: 400;
    line-height: 1.3;
}

.delivery-option input:checked + .option-content {
    border-color: #000;
    background: #f9fafb;
}

.option-content strong {
    font-weight: 600;
    font-size: 0.875rem;
}

.option-content .price-tag {
    color: #10b981;
    font-weight: 600;
    font-size: 0.875rem;
    white-space: nowrap;
}

.modal-summary {
    background: #f9fafb;
    padding: 1.25rem;
    border-radius: 10px;
    position: sticky;
    top: 0;
}

.modal-summary h3 {
    font-size: 1rem;
    font-weight: 700;
    margin-bottom: 0.875rem;
    color: #111827;
}

.modal-summary .summary-row {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem 0;
    font-size: 0.875rem;
    color: #374151;
}

.modal-summary .summary-total {
    display: flex;
    justify-content: space-between;
    padding: 0.875rem 0 0;
    margin-top: 0.375rem;
    border-top: 2px solid #e5e7eb;
    font-size: 1.125rem;
    font-weight: 800;
}

.btn-submit-order {
    width: 100%;
    padding: 0.875rem 1.5rem;
    background: #000;
    color: #fff;
    border: none;
    border-radius: 10px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    transition: all 0.3s;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.btn-submit-order:hover {
    background: #333;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.btn-submit-order:active {
    transform: scale(0.98);
}

@media (max-width: 1024px) {
    .checkout-grid {
        grid-template-columns: 1fr;
    }
    
    .checkout-right {
        order: -1;
    }
    
    .modal-summary {
        position: relative;
    }
    
    .modal-content h2 {
        padding: 1.25rem 1.5rem 0;
        font-size: 1.375rem;
    }
    
    .modal-content form {
        padding: 0 1.5rem;
    }
    
    .checkout-footer {
        padding: 0.875rem 1.5rem;
    }
    
    .checkout-grid {
        gap: 1.25rem;
    }
}

/* Mobile - 640px and below */
@media (max-width: 640px) {
    .modal-content {
        width: 100%;
        max-width: 100%;
        max-height: 100vh;
        border-radius: 0;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .modal-content h2 {
        padding: 1rem 1.25rem 0;
        font-size: 1.25rem;
    }
    
    .modal-content h2 i {
        font-size: 1.125rem;
    }
    
    .modal-content form {
        padding: 0 1.25rem;
    }
    
    .checkout-footer {
        padding: 0.75rem 1.25rem;
    }
    
    .checkout-grid {
        gap: 1rem;
    }
    
    .checkout-left {
        gap: 1rem;
    }
    
    .country-tabs {
        grid-template-columns: 1fr;
        gap: 0.5rem;
    }
    
    .country-tab {
        padding: 0.625rem 0.875rem;
        font-size: 0.875rem;
        min-height: 44px; /* Touch-friendly */
    }
    
    .form-section h3 {
        font-size: 0.875rem;
        margin-bottom: 0.5rem;
    }
    
    .form-group {
        margin-bottom: 0.5rem;
    }
    
    .form-group label {
        font-size: 0.75rem;
        margin-bottom: 0.25rem;
    }
    
    .form-group input,
    .form-group textarea {
        padding: 0.625rem 0.75rem;
        font-size: 0.875rem;
        min-height: 44px; /* Touch-friendly */
    }
    
    .form-group textarea {
        min-height: 60px;
    }
    
    .option-content {
        padding: 0.625rem 0.75rem;
        min-height: 48px;
    }
    
    .option-content strong {
        font-size: 0.875rem;
    }
    
    .option-content small {
        font-size: 0.6875rem;
        line-height: 1.4;
    }
    
    .modal-summary {
        padding: 1rem;
        border-radius: 8px;
    }
    
    .modal-summary h3 {
        font-size: 0.9375rem;
        margin-bottom: 0.75rem;
    }
    
    .modal-summary .summary-row {
        padding: 0.375rem 0;
        font-size: 0.8125rem;
    }
    
    .modal-summary .summary-total {
        font-size: 1rem;
        padding: 0.75rem 0 0;
    }
    
    .btn-submit-order {
        font-size: 0.9375rem;
        padding: 0.875rem 1rem;
        min-height: 48px;
    }
    
    .modal-close {
        width: 36px;
        height: 36px;
        top: 0.875rem;
        right: 0.875rem;
    }
}

/* Переключатель режимов формы */
.form-mode-switcher {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
    background: #f3f4f6;
    padding: 0.25rem;
    border-radius: 10px;
}

.mode-btn {
    padding: 0.625rem 1rem;
    background: transparent;
    border: none;
    border-radius: 8px;
    font-size: 0.875rem;
    font-weight: 600;
    color: #6b7280;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.375rem;
}

.mode-btn:hover {
    color: #374151;
}

.mode-btn.active {
    background: #fff;
    color: #000;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

/* Сохранённые адреса */
.saved-addresses-section {
    margin-bottom: 1.5rem;
}

.saved-addresses-section h3 {
    font-size: 0.875rem;
    font-weight: 700;
    margin-bottom: 0.75rem;
    color: #111827;
}

.saved-addresses {
    display: flex;
    flex-direction: column;
    gap: 0.625rem;
}

.saved-address-card {
    display: block;
    cursor: pointer;
}

.saved-address-card input {
    display: none;
}

.saved-address-card .address-content {
    padding: 0.875rem;
    border: 2px solid #e5e7eb;
    border-radius: 10px;
    transition: all 0.2s;
    background: #fff;
}

.saved-address-card:hover .address-content {
    border-color: #d1d5db;
    background: #f9fafb;
}

.saved-address-card input:checked + .address-content {
    border-color: #000;
    background: #f9fafb;
}

.saved-address-card .address-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.375rem;
}

.saved-address-card .address-header i {
    font-size: 1rem;
    color: #6b7280;
}

.saved-address-card input:checked + .address-content .address-header i {
    color: #000;
}

.saved-address-card .address-header strong {
    font-size: 0.875rem;
    font-weight: 600;
    color: #111827;
}

.saved-address-card .address-details {
    font-size: 0.8125rem;
    color: #6b7280;
    line-height: 1.5;
    padding-left: 1.5rem;
}

/* Чекбокс для сохранения данных */
.checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    user-select: none;
}

.checkbox-label input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.checkbox-label span {
    font-size: 0.875rem;
    color: #374151;
}

/* Поля формы в режиме "только чтение" */
.form-group input:disabled,
.form-group textarea:disabled {
    background: #f3f4f6;
    color: #6b7280;
    cursor: not-allowed;
}

/* Extra small devices - 380px and below */
@media (max-width: 380px) {
    .modal-content h2 {
        padding: 0.875rem 1rem 0;
        font-size: 1.125rem;
    }
    
    .modal-content form {
        padding: 0 1rem;
    }
    
    .checkout-footer {
        padding: 0.625rem 1rem;
    }
    
    .country-tab {
        padding: 0.5rem 0.625rem;
        font-size: 0.8125rem;
    }
    
    .form-group input,
    .form-group textarea {
        padding: 0.5rem 0.625rem;
        font-size: 0.8125rem;
    }
    
    .option-content {
        padding: 0.5rem 0.625rem;
    }
    
    .option-content strong {
        font-size: 0.8125rem;
    }
    
    .option-content small {
        font-size: 0.625rem;
    }
    
    .modal-summary {
        padding: 0.875rem;
    }
    
    .btn-submit-order {
        font-size: 0.875rem;
        padding: 0.75rem 0.875rem;
    }
    
    .modal-close {
        width: 32px;
        height: 32px;
        top: 0.75rem;
        right: 0.75rem;
    }
}
</style>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="/js/cart.js"></script>
<script src="/js/cart-mobile.js"></script>
<script>
function openCheckoutModal() {
    document.getElementById('checkoutModal').classList.add('active');
    document.body.style.overflow = 'hidden';
    document.body.classList.add('modal-blur-active');
}

function closeCheckoutModal() {
    document.getElementById('checkoutModal').classList.remove('active');
    document.body.style.overflow = '';
    document.body.classList.remove('modal-blur-active');
}

// Переключение между странами
function selectCountry(country, event) {
    event.preventDefault();
    
    // Обновляем активную вкладку
    document.querySelectorAll('.country-tab').forEach(tab => {
        tab.classList.remove('active');
    });
    event.currentTarget.classList.add('active');
    
    // Обновляем скрытое поле
    document.getElementById('selectedCountry').value = country;
    
    // Показываем/скрываем способы доставки
    const belarusDelivery = document.getElementById('deliveryBelarus');
    const russiaDelivery = document.getElementById('deliveryRussia');
    const addressField = document.getElementById('addressField');
    const addressLabel = document.getElementById('addressLabel');
    
    if (country === 'belarus') {
        belarusDelivery.style.display = 'grid';
        russiaDelivery.style.display = 'none';
        
        // Устанавливаем первый вариант для Беларуси
        const firstOption = belarusDelivery.querySelector('input[type="radio"]');
        if (firstOption) {
            firstOption.checked = true;
            updateDeliveryCost(firstOption.value);
        }
        
        addressField.placeholder = 'Укажите адрес доставки';
        addressLabel.textContent = 'Адрес доставки *';
        addressField.required = true;
    } else {
        belarusDelivery.style.display = 'none';
        russiaDelivery.style.display = 'grid';
        
        // Устанавливаем СДЭК для России
        const sdekOption = russiaDelivery.querySelector('input[type="radio"]');
        if (sdekOption) {
            sdekOption.checked = true;
            updateDeliveryCost(sdekOption.value);
        }
        
        addressField.placeholder = 'Город, улица, дом, квартира';
        addressLabel.textContent = 'Адрес пункта выдачи СДЭК *';
        addressField.required = true;
    }
}

// Обновление стоимости доставки
function updateDeliveryCost(deliveryType) {
    const deliveryCostEl = document.querySelector('.delivery-cost');
    const totalCostEl = document.querySelector('.total-cost');
    const goodsCostEl = document.querySelector('.goods-cost');
    const addressField = document.getElementById('addressField');
    const addressLabel = document.getElementById('addressLabel');
    const addressGroup = addressField?.closest('.form-group');
    
    if (!deliveryCostEl || !totalCostEl || !goodsCostEl) return;
    
    // Парсим белорусский формат "X,XX Br" -> число
    const goodsCost = parseFloat(goodsCostEl.textContent.replace(',', '.').replace(/[^\d.]/g, ''));
    let deliveryCost = 0;
    let deliveryCostText = 'Бесплатно';
    
    // Определяем стоимость доставки
    switch(deliveryType) {
        case 'pickup_minsk':
            deliveryCost = 0;
            deliveryCostText = 'Бесплатно';
            // Для самовывоза скрываем поле адреса
            if (addressGroup) addressGroup.style.display = 'none';
            if (addressField) addressField.required = false;
            break;
        case 'courier_minsk':
            deliveryCost = 10;
            deliveryCostText = '10,00 Br';
            if (addressGroup) addressGroup.style.display = 'block';
            if (addressField) addressField.required = true;
            if (addressLabel) addressLabel.textContent = 'Адрес доставки в Минске *';
            break;
        case 'europochta':
            deliveryCost = 5;
            deliveryCostText = '5,00 Br';
            if (addressGroup) addressGroup.style.display = 'block';
            if (addressField) addressField.required = true;
            if (addressLabel) addressLabel.textContent = 'Адрес пункта выдачи или доставки *';
            break;
        case 'belpochta':
            deliveryCost = 4;
            deliveryCostText = '4,00 Br';
            if (addressGroup) addressGroup.style.display = 'block';
            if (addressField) addressField.required = true;
            if (addressLabel) addressLabel.textContent = 'Адрес отделения почты *';
            break;
        case 'sdek':
            deliveryCost = 0;
            deliveryCostText = 'Рассчитывается по тарифам СДЭК';
            if (addressGroup) addressGroup.style.display = 'block';
            if (addressField) addressField.required = true;
            if (addressLabel) addressLabel.textContent = 'Адрес пункта выдачи СДЭК *';
            break;
    }
    
    // Обновляем UI
    deliveryCostEl.textContent = deliveryCostText;
    
    // Обновляем итоговую сумму (только для фиксированных тарифов)
    if (deliveryType !== 'sdek') {
        const total = goodsCost + deliveryCost;
        totalCostEl.textContent = total.toFixed(2).replace('.', ',') + ' Br';
    } else {
        totalCostEl.textContent = 'Уточняется';
    }
}

// Отслеживаем изменение способа доставки
document.addEventListener('DOMContentLoaded', function() {
    const deliveryOptions = document.querySelectorAll('input[name="delivery"]');
    deliveryOptions.forEach(option => {
        option.addEventListener('change', function() {
            updateDeliveryCost(this.value);
        });
    });
});

// Переключение режима формы (профиль / другие данные)
function switchFormMode(mode) {
    const modeBtns = document.querySelectorAll('.mode-btn');
    const savedAddressesSection = document.getElementById('savedAddressesSection');
    const contactDataSection = document.getElementById('contactDataSection');
    const addressFieldGroup = document.getElementById('addressFieldGroup');
    const saveDataSection = document.getElementById('saveDataSection');
    
    const nameField = document.getElementById('nameField');
    const phoneField = document.getElementById('phoneField');
    const emailField = document.getElementById('emailField');
    
    // Обновляем активную кнопку
    modeBtns.forEach(btn => btn.classList.remove('active'));
    event.currentTarget.classList.add('active');
    
    if (mode === 'profile') {
        // Режим "Данные профиля"
        if (savedAddressesSection) savedAddressesSection.style.display = 'block';
        if (contactDataSection) contactDataSection.style.display = 'none';
        if (saveDataSection) saveDataSection.style.display = 'none';
        
        // Проверяем выбранный адрес
        const defaultAddressRadio = document.querySelector('input[name="saved_address"][value="default"]');
        if (defaultAddressRadio && defaultAddressRadio.checked) {
            if (addressFieldGroup) addressFieldGroup.style.display = 'none';
        }
    } else {
        // Режим "Другие данные"
        if (savedAddressesSection) savedAddressesSection.style.display = 'none';
        if (contactDataSection) contactDataSection.style.display = 'block';
        if (addressFieldGroup) addressFieldGroup.style.display = 'block';
        if (saveDataSection) saveDataSection.style.display = 'block';
        
        // Разблокируем поля для редактирования
        if (nameField) nameField.disabled = false;
        if (phoneField) phoneField.disabled = false;
        if (emailField) emailField.disabled = false;
    }
}

// Обработка выбора сохранённого адреса
document.addEventListener('DOMContentLoaded', function() {
    const savedAddressRadios = document.querySelectorAll('input[name="saved_address"]');
    const addressFieldGroup = document.getElementById('addressFieldGroup');
    const addressField = document.getElementById('addressField');
    
    savedAddressRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            // Обновляем активную карточку
            document.querySelectorAll('.saved-address-card').forEach(card => {
                card.classList.remove('active');
            });
            this.closest('.saved-address-card').classList.add('active');
            
            if (this.value === 'default') {
                // Используем сохранённый адрес - скрываем поле ввода
                if (addressFieldGroup) addressFieldGroup.style.display = 'none';
                if (addressField) addressField.required = false;
            } else {
                // Новый адрес - показываем поле ввода
                if (addressFieldGroup) addressFieldGroup.style.display = 'block';
                if (addressField) addressField.required = true;
            }
        });
    });
});

function submitOrder(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (csrfToken) {
        formData.append('_csrf', csrfToken);
    }
    
    // Если используется режим профиля и выбран сохранённый адрес
    const savedAddressRadio = document.querySelector('input[name="saved_address"]:checked');
    if (savedAddressRadio && savedAddressRadio.value === 'default') {
        // Добавляем флаг использования сохранённого адреса
        formData.append('use_saved_address', '1');
    }
    
    // Показываем загрузку
    const btn = e.target.querySelector('.btn-submit-order');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Оформляем...';
    btn.disabled = true;
    
    // Отправляем заказ
    fetch('/order/create', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(async response => {
        const contentType = response.headers.get('content-type') || '';
        if (!contentType.includes('application/json')) {
            const text = await response.text();
            throw new Error(text || 'Сервер вернул неожиданный ответ.');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            closeCheckoutModal();
            alert('Заказ успешно оформлен! Наш менеджер свяжется с вами в ближайшее время.');
            window.location.href = '/order/success?id=' + data.order_id;
        } else {
            alert('Ошибка: ' + (data.message || 'Попробуйте позже'));
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    })
    .catch(err => {
        console.error('Order submission error:', err);
        alert(err.message || 'Ошибка соединения. Попробуйте позже.');
        btn.innerHTML = originalText;
        btn.disabled = false;
    });
}

// removeCartItem уже определен в cart.js - используем его
// Эта функция обновляет UI после изменений корзины
function updateCartTotals(total, count) {
    const cartTotals = document.querySelectorAll('.cart-total');
    cartTotals.forEach(el => {
        el.textContent = total.toFixed(2).replace('.', ',') + ' Br';
    });
    
    // Обновляем сумму товаров в модальном окне
    const goodsCostEl = document.querySelector('.goods-cost');
    if (goodsCostEl) {
        goodsCostEl.textContent = total.toFixed(2).replace('.', ',') + ' Br';
    }
    
    // Пересчитываем итоговую сумму с учетом доставки
    const deliveryRadio = document.querySelector('input[name="delivery"]:checked');
    if (deliveryRadio) {
        updateDeliveryCost(deliveryRadio.value);
    }
    
    // Обновляем badge в header
    const badge = document.getElementById('cartCount');
    if (badge) badge.textContent = count;
}
</script>
