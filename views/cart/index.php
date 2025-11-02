<?php
use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var app\models\Cart[] $items */
/** @var float $total */

$this->title = 'Корзина';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="cart-page">
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
                
                <div class="cart-summary">
                    <h2>Итого</h2>
                    
                    <div class="summary-row">
                        <span>Товары (<?= count($items) ?>):</span>
                        <span class="cart-total"><?= Yii::$app->formatter->asCurrency($total, 'BYN') ?></span>
                    </div>
                    
                    <div class="summary-row">
                        <span>Доставка:</span>
                        <span><?= $total >= 100 ? 'Бесплатно' : Yii::$app->formatter->asCurrency(10, 'BYN') ?></span>
                    </div>
                    
                    <div class="summary-total">
                        <span>Всего:</span>
                        <span class="cart-total"><?= Yii::$app->formatter->asCurrency($total >= 100 ? $total : $total + 10, 'BYN') ?></span>
                    </div>
                    
                    <?php if ($total < 100): ?>
                        <div class="delivery-info">
                            <i class="bi bi-truck"></i>
                            До бесплатной доставки: <?= Yii::$app->formatter->asCurrency(100 - $total, 'BYN') ?>
                        </div>
                    <?php endif; ?>
                    
                    <button class="btn-checkout" onclick="openCheckoutModal()">
                        <i class="bi bi-check-circle"></i>
                        Оформить заказ
                    </button>
                    
                    <a href="/catalog" class="btn-continue">Продолжить покупки</a>
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
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 1rem;
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
    gap: 2rem;
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

@media (max-width: 768px) {
    .cart-layout {
        grid-template-columns: 1fr;
    }
    
    .cart-item {
        grid-template-columns: 80px 1fr;
        gap: 1rem;
    }
    
    .item-quantity,
    .item-price {
        grid-column: 2;
    }
    
    .item-remove {
        grid-column: 2;
        justify-self: end;
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
            <div class="form-section">
                <h3>Контактные данные</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label>Имя *</label>
                        <input type="text" name="name" required placeholder="Ваше имя">
                    </div>
                    <div class="form-group">
                        <label>Телефон *</label>
                        <input type="tel" name="phone" required placeholder="+375 (__)___-__-__">
                    </div>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" placeholder="email@example.com">
                </div>
            </div>
            
            <div class="form-section">
                <h3>Доставка</h3>
                <div class="delivery-options">
                    <label class="delivery-option">
                        <input type="radio" name="delivery" value="courier" checked>
                        <div class="option-content">
                            <strong>Курьером</strong>
                            <span><?= $total >= 100 ? 'Бесплатно' : '10 BYN' ?></span>
                        </div>
                    </label>
                    <label class="delivery-option">
                        <input type="radio" name="delivery" value="pickup">
                        <div class="option-content">
                            <strong>Самовывоз</strong>
                            <span>Бесплатно</span>
                        </div>
                    </label>
                </div>
                <div class="form-group">
                    <label>Адрес доставки *</label>
                    <textarea name="address" required placeholder="Укажите адрес"></textarea>
                </div>
            </div>
            
            <div class="form-section">
                <h3>Комментарий</h3>
                <div class="form-group">
                    <textarea name="comment" placeholder="Дополнительные пожелания к заказу"></textarea>
                </div>
            </div>
            
            <div class="modal-summary">
                <div class="summary-row">
                    <span>Товары:</span>
                    <span><?= Yii::$app->formatter->asCurrency($total, 'BYN') ?></span>
                </div>
                <div class="summary-row">
                    <span>Доставка:</span>
                    <span class="delivery-cost"><?= $total >= 100 ? 'Бесплатно' : Yii::$app->formatter->asCurrency(10, 'BYN') ?></span>
                </div>
                <div class="summary-total">
                    <span>Итого:</span>
                    <span class="total-cost"><?= Yii::$app->formatter->asCurrency($total >= 100 ? $total : $total + 10, 'BYN') ?></span>
                </div>
            </div>
            
            <button type="submit" class="btn-submit-order">
                <i class="bi bi-check-circle-fill"></i>
                Подтвердить заказ
            </button>
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
    backdrop-filter: blur(4px);
}

.modal-content {
    position: relative;
    background: #fff;
    border-radius: 20px;
    width: 90%;
    max-width: 600px;
    max-height: 90vh;
    overflow-y: auto;
    padding: 2rem;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    animation: modalSlideIn 0.3s ease-out;
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
    font-size: 1.75rem;
    font-weight: 800;
    margin-bottom: 2rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.form-section {
    margin-bottom: 2rem;
}

.form-section h3 {
    font-size: 1.125rem;
    font-weight: 700;
    margin-bottom: 1rem;
    color: #666;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.form-group {
    margin-bottom: 1rem;
}

.form-group label {
    display: block;
    font-size: 0.875rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: #374151;
}

.form-group input,
.form-group textarea {
    width: 100%;
    padding: 0.875rem;
    border: 2px solid #e5e7eb;
    border-radius: 10px;
    font-size: 1rem;
    transition: all 0.2s;
    font-family: inherit;
}

.form-group input:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #000;
}

.form-group textarea {
    resize: vertical;
    min-height: 80px;
}

.delivery-options {
    display: grid;
    gap: 0.75rem;
    margin-bottom: 1rem;
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
    padding: 1rem 1.25rem;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    transition: all 0.2s;
}

.delivery-option input:checked + .option-content {
    border-color: #000;
    background: #f9fafb;
}

.option-content strong {
    font-weight: 600;
}

.option-content span {
    color: #10b981;
    font-weight: 600;
}

.modal-summary {
    background: #f9fafb;
    padding: 1.5rem;
    border-radius: 12px;
    margin-bottom: 1.5rem;
}

.modal-summary .summary-row {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem 0;
    font-size: 0.9375rem;
}

.modal-summary .summary-total {
    display: flex;
    justify-content: space-between;
    padding: 1rem 0 0;
    margin-top: 0.5rem;
    border-top: 2px solid #e5e7eb;
    font-size: 1.25rem;
    font-weight: 800;
}

.btn-submit-order {
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
    transition: all 0.2s;
}

.btn-submit-order:hover {
    background: #333;
    transform: translateY(-2px);
}

@media (max-width: 640px) {
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .modal-content {
        width: 95%;
        padding: 1.5rem;
    }
}
</style>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="/js/cart.js"></script>
<script>
function openCheckoutModal() {
    document.getElementById('checkoutModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeCheckoutModal() {
    document.getElementById('checkoutModal').classList.remove('active');
    document.body.style.overflow = '';
}

function submitOrder(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    
    // Показываем загрузку
    const btn = e.target.querySelector('.btn-submit-order');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Оформляем...';
    btn.disabled = true;
    
    // Отправляем заказ
    fetch('/order/create', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
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
        alert('Ошибка соединения. Попробуйте позже.');
        btn.innerHTML = originalText;
        btn.disabled = false;
    });
}

// Удаление без подтверждения
function removeCartItem(id) {
    const item = document.querySelector(`[data-cart-id="${id}"]`);
    if (!item) return;
    
    // Анимация удаления
    item.style.opacity = '0';
    item.style.transform = 'translateX(-20px)';
    
    setTimeout(() => {
        $.ajax({
            url: '/cart/remove',
            method: 'POST',
            data: { id: id },
            success: function(response) {
                if (response.success) {
                    item.remove();
                    updateCartTotals(response.total, response.count);
                    
                    // Если корзина пуста - перезагрузка
                    if (response.count === 0) {
                        location.reload();
                    }
                }
            }
        });
    }, 300);
}

function updateCartTotals(total, count) {
    const cartTotals = document.querySelectorAll('.cart-total');
    cartTotals.forEach(el => {
        el.textContent = total.toFixed(2) + ' BYN';
    });
    
    // Обновляем badge в header
    const badge = document.getElementById('cartCount');
    if (badge) badge.textContent = count;
}
</script>
