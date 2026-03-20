<?php

/** @var yii\web\View $this */
/** @var app\backend\modules\catalog\models\Order $model */

use yii\helpers\Html;
use yii\helpers\Url;
use app\frontend\assets\CheckoutAsset;

$this->title = 'Оформление заказа';
$this->params['breadcrumbs'][] = $this->title;

// Подключаем AssetBundle для checkout (все стили автоматически с версионированием)
CheckoutAsset::register($this);
?>

<div class="checkout-page">
    <div class="container">
        <h1><i class="bi bi-check-circle"></i> Оформление заказа</h1>
        
        <!-- Progress Bar -->
        <div class="checkout-progress">
            <div class="progress-step active" data-step="1">
                <span class="step-number">1</span>
                <span class="step-name">Корзина</span>
            </div>
            <div class="progress-step" data-step="2">
                <span class="step-number">2</span>
                <span class="step-name">Доставка</span>
            </div>
            <div class="progress-step" data-step="3">
                <span class="step-number">3</span>
                <span class="step-name">Оплата</span>
            </div>
            <div class="progress-step" data-step="4">
                <span class="step-number">4</span>
                <span class="step-name">Готово</span>
            </div>
        </div>
        
        <div class="checkout-layout">
            <!-- Checkout Form -->
            <div class="checkout-form">
                <!-- Step 1: Cart Review -->
                <div class="checkout-step active" id="step-1">
                    <h2>Проверьте заказ</h2>
                    <!-- Cart items here -->
                </div>
                
                <!-- Step 2: Shipping -->
                <div class="checkout-step" id="step-2">
                    <h2>Выберите доставку</h2>
                    
                    <div class="shipping-section">
                        <h3>Способ доставки</h3>
                        <div class="shipping-options">
                            <label class="shipping-option">
                                <input type="radio" name="shipping" value="pickup" checked>
                                <div class="option-content">
                                    <div class="option-icon">🏪</div>
                                    <div class="option-info">
                                        <span class="option-name">Самовывоз</span>
                                        <span class="option-price">Бесплатно</span>
                                        <span class="option-time">Сегодня</span>
                                    </div>
                                    <div class="option-radio"></div>
                                </div>
                            </label>
                            
                            <label class="shipping-option">
                                <input type="radio" name="shipping" value="courier">
                                <div class="option-content">
                                    <div class="option-icon"><i class="bi bi-truck"></i></div>
                                    <div class="option-info">
                                        <span class="option-name">Курьер по Минску</span>
                                        <span class="option-price">10 BYN</span>
                                        <span class="option-time">1-2 дня</span>
                                    </div>
                                    <div class="option-radio"></div>
                                </div>
                            </label>
                            
                            <label class="shipping-option">
                                <input type="radio" name="shipping" value="cdek">
                                <div class="option-content">
                                    <div class="option-icon"><i class="bi bi-box-seam"></i></div>
                                    <div class="option-info">
                                        <span class="option-name">СДЭК</span>
                                        <span class="option-price">15 BYN</span>
                                        <span class="option-time">3-5 дней</span>
                                    </div>
                                    <div class="option-radio"></div>
                                </div>
                            </label>
                        </div>
                    </div>
                    
                    <button class="btn-next" onclick="nextStep(3)">
                        Далее <i class="bi bi-arrow-right"></i>
                    </button>
                </div>
                
                <!-- Step 3: Payment -->
                <div class="checkout-step" id="step-3">
                    <h2>Выберите оплату</h2>
                    
                    <div class="payment-section">
                        <h3>Способ оплаты</h3>
                        <div class="payment-options">
                            <label class="payment-option">
                                <input type="radio" name="payment" value="card" checked>
                                <div class="option-content">
                                    <i class="bi bi-credit-card option-icon"></i>
                                    <span class="option-name">Картой онлайн</span>
                                </div>
                            </label>
                            
                            <label class="payment-option">
                                <input type="radio" name="payment" value="cash">
                                <div class="option-content">
                                    <i class="bi bi-cash-stack option-icon"></i>
                                    <span class="option-name">Наличными при получении</span>
                                </div>
                            </label>
                            
                            <label class="payment-option">
                                <input type="radio" name="payment" value="erip">
                                <div class="option-content">
                                    <i class="bi bi-phone option-icon"></i>
                                    <span class="option-name">ЕРИП (Халва - рассрочка)</span>
                                </div>
                            </label>
                        </div>
                    </div>
                    
                    <div class="checkout-actions">
                        <button class="btn-back" onclick="prevStep(2)">
                            <i class="bi bi-arrow-left"></i> Назад
                        </button>
                        <button class="btn-next" onclick="submitOrder()">
                            Оформить заказ <i class="bi bi-check"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Step 4: Success -->
                <div class="checkout-step" id="step-4">
                    <div class="order-success">
                        <i class="bi bi-check-circle-fill"></i>
                        <h2>Заказ оформлен!</h2>
                        <p>Ваш заказ #<span id="orderNumber"></span> успешно создан</p>
                        <p>Вам отправлено письмо с деталями заказа</p>
                        <a href="/account/orders" class="btn-view-orders">
                            <i class="bi bi-box-seam"></i> Мои заказы
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Order Summary -->
            <div class="order-summary">
                <h3>Ваш заказ</h3>
                
                <div class="summary-items">
                    <!-- Items here -->
                </div>
                
                <div class="summary-totals">
                    <div class="summary-row">
                        <span>Товары:</span>
                        <span id="productsTotal">0 BYN</span>
                    </div>
                    <div class="summary-row discount" id="promoDiscountRow" style="display: none;">
                        <span>Скидка по промокоду:</span>
                        <span id="promoDiscountValue">-0 BYN</span>
                    </div>
                    <div class="summary-row">
                        <span>Доставка:</span>
                        <span id="deliveryCost">0 BYN</span>
                    </div>
                    <div class="summary-total">
                        <span>Всего:</span>
                        <span id="finalTotal">0 BYN</span>
                    </div>
                </div>
                
                <button class="btn-place-order" onclick="submitOrder()">
                    Оформить заказ
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function nextStep(step) {
    document.querySelectorAll('.checkout-step').forEach(el => el.classList.remove('active'));
    document.getElementById('step-' + step).classList.add('active');
    
    document.querySelectorAll('.progress-step').forEach(el => {
        const stepNum = parseInt(el.dataset.step);
        if (stepNum <= step) {
            el.classList.add('completed');
        }
        if (stepNum === step) {
            el.classList.add('active');
        }
    });
}

function prevStep(step) {
    nextStep(step);
}

function submitOrder() {
    // Submit order logic
    const btn = document.querySelector('.btn-place-order');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Обработка...';
    
    setTimeout(() => {
        nextStep(4);
        document.getElementById('orderNumber').textContent = '12345';
    }, 2000);
}
</script>
