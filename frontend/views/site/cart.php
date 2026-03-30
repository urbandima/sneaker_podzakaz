<?php
/** @var yii\web\View $this */
/** @var array $items */
/** @var float $total */

$this->title = 'Корзина - СНИКЕРХЭД';

// Стили
$this->registerCssFile('@web/css/mobile-first.css', ['position' => \yii\web\View::POS_HEAD]);
$this->registerCssFile('@web/css/cart.css', ['position' => \yii\web\View::POS_HEAD]);
$this->registerJsFile('@web/js/cart.js', ['position' => \yii\web\View::POS_END, 'depends' => [\yii\web\JqueryAsset::class]]);
?>

<div class="cart-page cart-blur-surface">
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
        <h1 class="cart-title">Корзина</h1>
        
        <!-- Empty cart -->
        <div class="cart-empty" id="emptyCart">
            <div class="empty-icon"><i class="bi bi-cart"></i></div>
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
                    <button class="btn-checkout" onclick="openCheckoutModal()">
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
                <button class="btn-checkout-sticky" onclick="openCheckoutModal()">
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

<!-- Модальное окно оформления заказа -->
<div id="checkoutModal" class="checkout-modal" style="display: none;">
    <div class="modal-overlay" onclick="closeCheckoutModal()"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h2>Оформление заказа</h2>
            <button class="modal-close" onclick="closeCheckoutModal()">
                <i class="bi bi-x"></i>
            </button>
        </div>
        
        <form id="checkoutForm" class="checkout-form">
            <div class="form-section">
                <h3>Контактные данные</h3>
                <div class="form-group">
                    <label>ФИО <span class="required">*</span></label>
                    <input type="text" name="name" required placeholder="Иван Иванов">
                </div>
                <div class="form-group">
                    <label>Телефон <span class="required">*</span></label>
                    <input type="tel" name="phone" required placeholder="+375 29 123-45-67">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" placeholder="email@example.com">
                </div>
            </div>
            
            <div class="form-section">
                <h3>Доставка</h3>
                <div class="form-group">
                    <label>Страна <span class="required">*</span></label>
                    <select name="country">
                        <option value="belarus">Беларусь</option>
                        <option value="russia">Россия</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Способ доставки <span class="required">*</span></label>
                    <select name="delivery" required onchange="toggleAddressField(this.value)">
                        <option value="">Выберите способ доставки</option>
                        <option value="courier_minsk">Курьер по Минску (10 BYN)</option>
                        <option value="pickup_minsk">Самовывоз из Минска (Бесплатно)</option>
                        <option value="belpochta">Белпочта (4 BYN)</option>
                        <option value="europochta">Европочта (5 BYN)</option>
                        <option value="sdek">СДЭК (Россия)</option>
                    </select>
                </div>
                <div class="form-group" id="addressField" style="display: none;">
                    <label>Адрес доставки <span class="required">*</span></label>
                    <textarea name="address" rows="2" placeholder="Город, улица, дом, квартира"></textarea>
                </div>
                <div class="form-group">
                    <label>Комментарий к заказу</label>
                    <textarea name="comment" rows="2" placeholder="Пожелания по заказу"></textarea>
                </div>
            </div>
            
            <div class="form-summary">
                <div class="summary-row">
                    <span>Товары:</span>
                    <span class="cart-subtotal">0 BYN</span>
                </div>
                <div class="summary-row">
                    <span>Доставка:</span>
                    <span id="deliveryCost">0 BYN</span>
                </div>
                <div class="summary-row summary-total">
                    <span>Итого:</span>
                    <span class="cart-total">0 BYN</span>
                </div>
            </div>
            
            <button type="submit" class="btn-submit-order">
                <i class="bi bi-check-circle"></i>
                Подтвердить заказ
            </button>
        </form>
    </div>
</div>

<style>
/* ============================================
   CHECKOUT MODAL STYLES
   ============================================ */

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

.checkout-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 10000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
}

.modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.7);
    backdrop-filter: blur(4px);
}

.modal-content {
    position: relative;
    background: white;
    border-radius: 16px;
    max-width: 600px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem;
    border-bottom: 2px solid #e5e7eb;
}

.modal-header h2 {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 800;
}

.modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    padding: 0.5rem;
    color: #6b7280;
    transition: color 0.2s;
}

.modal-close:hover {
    color: #111827;
}

.checkout-form {
    padding: 1.5rem;
}

.form-section {
    margin-bottom: 2rem;
}

.form-section h3 {
    font-size: 1.125rem;
    font-weight: 700;
    margin-bottom: 1rem;
    color: #111827;
}

.form-group {
    margin-bottom: 1rem;
}

.form-group label {
    display: block;
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: #374151;
}

.required {
    color: #ef4444;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 0.75rem;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    font-size: 1rem;
    transition: border-color 0.2s;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #3b82f6;
}

.form-summary {
    background: #f9fafb;
    padding: 1rem;
    border-radius: 12px;
    margin-bottom: 1.5rem;
}

.btn-submit-order {
    width: 100%;
    padding: 1rem;
    background: linear-gradient(135deg, #111827 0%, #1f2937 100%);
    color: white;
    border: none;
    border-radius: 12px;
    font-size: 1.125rem;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    transition: all 0.3s;
}

.btn-submit-order:hover {
    background: linear-gradient(135deg, #000000 0%, #111827 100%);
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
}

.btn-submit-order:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

body.modal-blur-active {
    overflow: hidden;
}

body.modal-blur-active .cart-page {
    filter: blur(12px);
    transition: filter 0.3s ease;
    pointer-events: none;
}

@media (max-width: 768px) {
    .modal-content {
        max-height: 95vh;
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

// Открыть модальное окно оформления заказа
function openCheckoutModal() {
    const modal = document.getElementById('checkoutModal');
    if (modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden'; // Блокируем прокрутку фона
        document.body.classList.add('modal-blur-active');
    }
}

// Закрыть модальное окно
function closeCheckoutModal() {
    const modal = document.getElementById('checkoutModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = ''; // Разблокируем прокрутку
        document.body.classList.remove('modal-blur-active');
    }
}

// Показать/скрыть поле адреса в зависимости от способа доставки
function toggleAddressField(deliveryMethod) {
    const addressField = document.getElementById('addressField');
    const deliveryCost = document.getElementById('deliveryCost');
    
    // Самовывоз не требует адреса
    if (deliveryMethod === 'pickup_minsk') {
        addressField.style.display = 'none';
        addressField.querySelector('textarea').required = false;
        deliveryCost.textContent = 'Бесплатно';
    } else {
        addressField.style.display = 'block';
        addressField.querySelector('textarea').required = true;
        
        // Обновляем стоимость доставки
        const costs = {
            'courier_minsk': '10 BYN',
            'belpochta': '4 BYN',
            'europochta': '5 BYN',
            'sdek': 'По тарифам СДЭК'
        };
        deliveryCost.textContent = costs[deliveryMethod] || 'Уточнить';
    }
}

// Обработка отправки формы заказа
document.addEventListener('DOMContentLoaded', function() {
    const checkoutForm = document.getElementById('checkoutForm');
    
    if (checkoutForm) {
        checkoutForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = checkoutForm.querySelector('.btn-submit-order');
            const originalText = submitBtn.innerHTML;
            
            // Блокируем кнопку
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Отправка...';
            
            // Собираем данные формы
            const formData = new FormData(checkoutForm);
            formData.append('_csrf', getCsrfToken());
            
            // Отправляем AJAX запрос
            fetch('/order/create', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Успех! Редирект на страницу благодарности
                    if (window.NotificationManager) {
                        NotificationManager.success('Заказ успешно оформлен!');
                    }
                    
                    // Через 500ms редиректим на success-страницу
                    setTimeout(() => {
                        window.location.href = '/order/success/' + data.token;
                    }, 500);
                } else {
                    // Ошибка
                    if (window.NotificationManager) {
                        NotificationManager.error(data.message || 'Ошибка при оформлении заказа');
                    } else {
                        alert(data.message || 'Ошибка при оформлении заказа');
                    }
                    
                    // Разблокируем кнопку
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                
                if (window.NotificationManager) {
                    NotificationManager.error('Ошибка соединения. Попробуйте позже');
                } else {
                    alert('Ошибка соединения. Попробуйте позже');
                }
                
                // Разблокируем кнопку
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            });
        });
    }
});

// Получить CSRF токен
function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.content : '';
}
</script>
