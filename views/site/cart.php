<?php
/** @var yii\web\View $this */
/** @var array $items */
/** @var float $total */

$this->title = 'Корзина - СНИКЕРХЭД';

// Mobile-first CSS
$this->registerCssFile('@web/css/mobile-first.css', ['position' => \yii\web\View::POS_HEAD]);
$this->registerJsFile('@web/js/cart.js', ['position' => \yii\web\View::POS_END, 'depends' => [\yii\web\JqueryAsset::class]]);
?>

<div class="cart-page">
    <!-- Header -->
    <header class="catalog-header">
        <div class="container">
            <button type="button" class="back-btn" onclick="history.back()">
                <i class="bi bi-arrow-left"></i>
            </button>
            <a href="/" class="logo">СНИКЕРХЭД</a>
            <a href="/catalog/favorites" class="favorites">
                <i class="bi bi-heart"></i>
            </a>
        </div>
    </header>

    <div class="container cart-container">
        <h1 class="cart-title">🛒 Корзина</h1>
        
        <!-- Empty cart -->
        <div class="cart-empty" id="emptyCart">
            <div class="empty-icon">🛒</div>
            <h2 class="empty-title">Корзина пуста</h2>
            <p class="empty-text">Добавьте товары из каталога</p>
            <a href="/catalog" class="btn-primary">
                <i class="bi bi-shop"></i>
                <span>Перейти в каталог</span>
            </a>
        </div>
        
        <!-- Cart with items -->
        <div class="cart-content" id="cartContent" style="display: none;">
            <div class="cart-items" id="cartItems">
                <!-- Example cart item (будет заменён на dynamic) -->
                <!-- 
                <div class="cart-item" data-cart-id="1">
                    <img src="/img/product.jpg" class="cart-item-image" alt="Product">
                    <div class="cart-item-info">
                        <span class="cart-item-brand">Nike</span>
                        <h3 class="cart-item-name">Air Max 90</h3>
                        <p class="cart-item-details">Размер: 42, Цвет: Черный</p>
                        <div class="cart-item-actions">
                            <div class="cart-item-quantity">
                                <button class="qty-btn" onclick="updateQuantity(1, -1)">
                                    <i class="bi bi-dash"></i>
                                </button>
                                <span class="qty-value">1</span>
                                <button class="qty-btn" onclick="updateQuantity(1, 1)">
                                    <i class="bi bi-plus"></i>
                                </button>
                            </div>
                            <span class="cart-item-price">150 BYN</span>
                        </div>
                        <button class="cart-item-remove" onclick="removeCartItem(1)">
                            <i class="bi bi-trash"></i> Удалить
                        </button>
                    </div>
                </div>
                -->
            </div>
            
            <!-- Desktop Order Summary (hidden on mobile) -->
            <div class="cart-summary hide-mobile" id="cartSummaryDesktop">
                <div class="summary-card">
                    <h3 class="summary-title">Итого</h3>
                    <div class="summary-row">
                        <span>Товары:</span>
                        <span class="summary-value cart-subtotal">0 BYN</span>
                    </div>
                    <div class="summary-row">
                        <span>Доставка:</span>
                        <span class="summary-value text-success">Бесплатно</span>
                    </div>
                    <div class="summary-divider"></div>
                    <div class="summary-row summary-total">
                        <span>Всего:</span>
                        <span class="cart-total">0 BYN</span>
                    </div>
                    <button class="btn-checkout" onclick="window.location.href='/order/create'">
                        <i class="bi bi-check-circle"></i>
                        <span>Оформить заказ</span>
                    </button>
                    <a href="/catalog" class="btn-continue">
                        <i class="bi bi-arrow-left"></i>
                        <span>Продолжить покупки</span>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Mobile Sticky Footer Buttons -->
        <div class="cart-sticky-footer" id="cartStickyFooter" style="display: none;">
            <div class="sticky-summary">
                <div class="sticky-total">
                    <span class="sticky-label">Всего:</span>
                    <span class="sticky-price cart-total">0 BYN</span>
                </div>
                <button class="btn-checkout-sticky" onclick="window.location.href='/order/create'">
                    <i class="bi bi-check-circle"></i>
                    <span>Оформить заказ</span>
                </button>
            </div>
            <a href="/catalog" class="btn-continue-sticky">
                <i class="bi bi-arrow-left"></i>
                <span>Продолжить покупки</span>
            </a>
        </div>
    </div>
</div>

<style>
/* ============================================
   CART PAGE STYLES (Mobile First)
   ============================================ */

.cart-page {
    min-height: 100vh;
    background: var(--gray-light, #f3f4f6);
    padding-bottom: 120px; /* Space for sticky footer */
}

.cart-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: var(--spacing-lg, 24px) var(--spacing-md, 16px);
}

.cart-title {
    font-size: 1.75rem;
    font-weight: 900;
    margin-bottom: var(--spacing-lg, 24px);
    color: var(--dark, #111827);
}

/* Empty Cart */
.cart-empty {
    text-align: center;
    padding: 4rem 1rem;
    background: white;
    border-radius: var(--radius-lg, 16px);
    box-shadow: var(--shadow-sm, 0 1px 2px rgba(0,0,0,0.05));
}

.empty-icon {
    font-size: 5rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

.empty-title {
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    color: var(--dark, #111827);
}

.empty-text {
    color: var(--gray, #6b7280);
    margin-bottom: 2rem;
    font-size: 1rem;
}

.btn-primary {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 1rem 2rem;
    background: var(--dark, #111827);
    color: white;
    text-decoration: none;
    border-radius: var(--radius-md, 12px);
    font-weight: 700;
    transition: var(--transition, 0.2s);
}

.btn-primary:hover {
    background: var(--gray, #6b7280);
    transform: translateY(-2px);
}

/* Cart Content */
.cart-content {
    display: grid;
    grid-template-columns: 1fr;
    gap: var(--spacing-lg, 24px);
}

.cart-items {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-md, 16px);
}

.cart-item {
    background: white;
    border-radius: var(--radius-md, 12px);
    padding: var(--spacing-md, 16px);
    box-shadow: var(--shadow-sm, 0 1px 2px rgba(0,0,0,0.05));
    display: grid;
    grid-template-columns: 80px 1fr;
    gap: var(--spacing-md, 16px);
}

.cart-item-image {
    width: 80px;
    height: 80px;
    border-radius: var(--radius-sm, 8px);
    object-fit: cover;
}

.cart-item-info {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.cart-item-brand {
    font-size: 0.75rem;
    color: var(--gray, #6b7280);
    font-weight: 600;
    text-transform: uppercase;
}

.cart-item-name {
    font-size: 1rem;
    font-weight: 600;
    color: var(--dark, #111827);
    margin: 0;
}

.cart-item-details {
    font-size: 0.875rem;
    color: var(--gray, #6b7280);
}

.cart-item-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 0.5rem;
}

.cart-item-quantity {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.qty-btn {
    width: 32px;
    height: 32px;
    border: 1px solid var(--gray-light, #e5e7eb);
    background: white;
    border-radius: var(--radius-sm, 8px);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    transition: var(--transition, 0.2s);
}

.qty-btn:hover {
    background: var(--gray-light, #f3f4f6);
    border-color: var(--dark, #111827);
}

.qty-btn:active {
    transform: scale(0.95);
}

.qty-value {
    min-width: 40px;
    text-align: center;
    font-weight: 600;
    font-size: 1rem;
}

.cart-item-price {
    font-size: 1.25rem;
    font-weight: 800;
    color: var(--dark, #111827);
}

.cart-item-remove {
    background: none;
    border: none;
    color: var(--danger, #ef4444);
    cursor: pointer;
    font-size: 0.875rem;
    font-weight: 600;
    padding: 0.25rem 0.5rem;
    transition: var(--transition, 0.2s);
}

.cart-item-remove:hover {
    opacity: 0.7;
}

/* Desktop Summary - теперь тоже sticky внизу */
.cart-summary {
    /* Убираем sticky для desktop - будем использовать footer */
}

.summary-card {
    background: white;
    border-radius: var(--radius-lg, 16px);
    padding: var(--spacing-lg, 24px);
    box-shadow: var(--shadow-md, 0 4px 6px rgba(0,0,0,0.07));
}

.summary-title {
    font-size: 1.25rem;
    font-weight: 700;
    margin-bottom: var(--spacing-md, 16px);
}

.summary-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.75rem;
    font-size: 0.9375rem;
}

.summary-value {
    font-weight: 600;
}

.text-success {
    color: var(--success, #10b981) !important;
}

.summary-divider {
    height: 1px;
    background: var(--gray-light, #f3f4f6);
    margin: var(--spacing-md, 16px) 0;
}

.summary-total {
    font-size: 1.25rem;
    font-weight: 700;
    margin-bottom: var(--spacing-lg, 24px);
}

.btn-checkout {
    width: 100%;
    padding: 1rem;
    background: var(--dark, #111827);
    color: white;
    border: none;
    border-radius: var(--radius-md, 12px);
    font-weight: 700;
    font-size: 1rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    transition: var(--transition, 0.2s);
    margin-bottom: var(--spacing-sm, 8px);
}

.btn-checkout:hover {
    background: var(--gray, #6b7280);
}

.btn-continue {
    width: 100%;
    padding: 0.875rem;
    background: white;
    color: var(--dark, #111827);
    border: 2px solid var(--gray-light, #f3f4f6);
    border-radius: var(--radius-md, 12px);
    font-weight: 600;
    font-size: 0.9375rem;
    text-decoration: none;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    transition: var(--transition, 0.2s);
}

.btn-continue:hover {
    border-color: var(--dark, #111827);
    background: var(--gray-light, #f3f4f6);
}

/* Mobile Sticky Footer */
.cart-sticky-footer {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: white;
    padding: var(--spacing-md, 16px);
    box-shadow: 0 -4px 20px rgba(0,0,0,0.1);
    z-index: 1000;
    border-top: 1px solid var(--gray-light, #f3f4f6);
}

.sticky-summary {
    display: flex;
    align-items: center;
    gap: var(--spacing-md, 16px);
    margin-bottom: var(--spacing-sm, 8px);
}

.sticky-total {
    display: flex;
    flex-direction: column;
    flex: 1;
}

.sticky-label {
    font-size: 0.75rem;
    color: var(--gray, #6b7280);
    font-weight: 600;
}

.sticky-price {
    font-size: 1.5rem;
    font-weight: 900;
    color: var(--dark, #111827);
}

.btn-checkout-sticky {
    flex: 1;
    padding: 0.875rem 1.5rem;
    background: var(--dark, #111827);
    color: white;
    border: none;
    border-radius: var(--radius-md, 12px);
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    transition: var(--transition, 0.2s);
}

.btn-checkout-sticky:active {
    transform: scale(0.98);
}

.btn-continue-sticky {
    width: 100%;
    padding: 0.75rem;
    background: var(--gray-light, #f3f4f6);
    color: var(--dark, #111827);
    border: none;
    border-radius: var(--radius-md, 12px);
    font-weight: 600;
    text-decoration: none;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    transition: var(--transition, 0.2s);
    font-size: 0.9375rem;
}

.btn-continue-sticky:hover {
    background: #e5e7eb;
}

/* Tablet+ (768px) */
@media (min-width: 768px) {
    .cart-title {
        font-size: 2.5rem;
    }
    
    .cart-content {
        grid-template-columns: 1fr; /* На tablet тоже одна колонка */
    }
    
    .cart-item {
        grid-template-columns: 100px 1fr auto;
    }
    
    .cart-item-image {
        width: 100px;
        height: 100px;
    }
    
    .cart-item-actions {
        flex-direction: column;
        align-items: flex-end;
        justify-content: space-between;
    }
    
    /* На tablet+ показываем sticky footer тоже */
    .cart-sticky-footer {
        display: block !important;
        flex-direction: row;
        align-items: center;
        padding: var(--spacing-lg, 24px) var(--spacing-md, 16px);
    }
    
    .sticky-summary {
        flex: 1;
        margin-bottom: 0;
        max-width: 600px;
    }
    
    .btn-continue-sticky {
        width: auto;
        min-width: 200px;
        margin-left: var(--spacing-md, 16px);
    }
    
    .cart-page {
        padding-bottom: 80px; /* Space for sticky footer */
    }
}
</style>

<script>
// Sticky footer visibility - показываем всегда если корзина не пустая
document.addEventListener('DOMContentLoaded', function() {
    const cartContent = document.getElementById('cartContent');
    const stickyFooter = document.getElementById('cartStickyFooter');
    const emptyCart = document.getElementById('emptyCart');
    
    // Show sticky footer if cart has items (на всех устройствах)
    if (cartContent && cartContent.style.display !== 'none') {
        stickyFooter.style.display = 'flex';
    }
    
    // Hide if cart is empty
    if (emptyCart && emptyCart.style.display !== 'none') {
        stickyFooter.style.display = 'none';
    }
});
</script>
