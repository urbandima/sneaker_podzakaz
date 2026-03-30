<?php
/** @var yii\web\View $this */
/** @var array $items          Позиции корзины */
/** @var float $total          Итого по товарам */
/** @var \app\backend\modules\account\models\Customer|null $customer */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Оформление заказа';

$createUrl  = Url::to(['/order/create']);
$csrfToken  = Yii::$app->request->csrfToken;
$successUrl = Url::to(['/order/success']);
?>

<div class="checkout-page">
    <div class="container">
        <!-- Progress Bar -->
        <div class="checkout-progress">
            <div class="progress-step active" data-step="1">
                <span class="step-number">1</span>
                <span class="step-name">Доставка</span>
            </div>
            <div class="progress-step" data-step="2">
                <span class="step-number">2</span>
                <span class="step-name">Оплата</span>
            </div>
        </div>

        <div class="checkout-layout">
            <!-- Checkout Form -->
            <div class="checkout-form">

                <!-- Step 1: Доставка и контакты -->
                <div class="checkout-step active" id="step-1">
                    <h2>Контактные данные</h2>
                    
                    <div class="form-grid">
                        <div class="floating-input">
                            <input type="text" id="field-name" name="name" placeholder=" "
                                   value="<?= Html::encode($customer ? $customer->getFullName() : '') ?>"
                                   required maxlength="100">
                            <label for="field-name">Имя и фамилия *</label>
                        </div>
                        <div class="floating-input">
                            <input type="tel" id="field-phone" name="phone" placeholder=" "
                                   value="<?= Html::encode($customer?->phone ?? '') ?>"
                                   required maxlength="50">
                            <label for="field-phone">Телефон *</label>
                        </div>
                    </div>
                    
                    <div class="form-grid full">
                        <div class="floating-input">
                            <input type="email" id="field-email" name="email" placeholder=" "
                                   value="<?= Html::encode($customer?->email ?? '') ?>"
                                   maxlength="255">
                            <label for="field-email">Email (для чека)</label>
                        </div>
                    </div>

                    <h2 class="mt-8 mb-4">Способ доставки</h2>
                    <div class="shipping-options">
                        <label class="option-card selected">
                            <input type="radio" name="delivery" value="pickup_minsk" checked
                                   onchange="updateDelivery(0, 'pickup_minsk')">
                            <div class="option-icon"><i class="bi bi-shop"></i></div>
                            <div class="option-info">
                                <span class="option-name">Самовывоз</span>
                                <span class="option-desc">Минск, ул. Немига 3. Сегодня–завтра</span>
                            </div>
                            <div class="option-price">Бесплатно</div>
                        </label>

                        <label class="option-card">
                            <input type="radio" name="delivery" value="courier_minsk"
                                   onchange="updateDelivery(10, 'courier_minsk')">
                            <div class="option-icon"><i class="bi bi-truck"></i></div>
                            <div class="option-info">
                                <span class="option-name">Курьер по Минску</span>
                                <span class="option-desc">Доставка 1–2 дня до двери</span>
                            </div>
                            <div class="option-price">10 BYN</div>
                        </label>

                        <label class="option-card">
                            <input type="radio" name="delivery" value="europochta"
                                   onchange="updateDelivery(5, 'europochta')">
                            <div class="option-icon"><i class="bi bi-box-seam"></i></div>
                            <div class="option-info">
                                <span class="option-name">Европочта</span>
                                <span class="option-desc">До отделения 3–7 дней</span>
                            </div>
                            <div class="option-price">5 BYN</div>
                        </label>
                    </div>

                    <!-- Адрес доставки -->
                    <div class="form-grid full hidden" id="addressGroup">
                        <div class="floating-input">
                            <input type="text" id="field-address" name="address" placeholder=" "
                                   value="<?= Html::encode($customer?->default_address ?? '') ?>"
                                   maxlength="500">
                            <label for="field-address">Адрес доставки *</label>
                        </div>
                    </div>

                    <div class="checkout-actions">
                        <a href="<?= Url::to(['/cart/cart/index']) ?>" class="btn-back">
                            <i class="bi bi-arrow-left"></i> В корзину
                        </a>
                        <button class="btn-next" onclick="validateStep1()">
                            К оплате <i class="bi bi-arrow-right"></i>
                        </button>
                    </div>
                </div>

                <!-- Step 2: Оплата -->
                <div class="checkout-step" id="step-2">
                    <h2>Способ оплаты</h2>
                    
                    <div class="payment-options">
                        <label class="option-card selected">
                            <input type="radio" name="payment" value="card_online" checked onchange="updatePayment(this)">
                            <div class="option-icon"><i class="bi bi-credit-card"></i></div>
                            <div class="option-info">
                                <span class="option-name">Картой онлайн</span>
                                <span class="option-desc">Apple Pay, Google Pay, Visa, Mastercard</span>
                            </div>
                        </label>

                        <label class="option-card">
                            <input type="radio" name="payment" value="erip" onchange="updatePayment(this)">
                            <div class="option-icon"><i class="bi bi-phone"></i></div>
                            <div class="option-info">
                                <span class="option-name">Через ЕРИП</span>
                                <span class="option-desc">Оплата в мобильном банкинге</span>
                            </div>
                        </label>

                        <label class="option-card">
                            <input type="radio" name="payment" value="cash" onchange="updatePayment(this)">
                            <div class="option-icon"><i class="bi bi-cash-stack"></i></div>
                            <div class="option-info">
                                <span class="option-name">При получении</span>
                                <span class="option-desc">Наличными или картой курьеру</span>
                            </div>
                        </label>
                    </div>
                    
                    <div class="form-grid full mt-6">
                        <div class="floating-input">
                            <textarea id="field-comment" name="comment" placeholder=" " rows="3" maxlength="1000"></textarea>
                            <label for="field-comment">Комментарий к заказу (необязательно)</label>
                        </div>
                    </div>

                    <div class="checkout-actions">
                        <button class="btn-back" onclick="goStep(1)">
                            <i class="bi bi-arrow-left"></i> Назад
                        </button>
                        <button class="btn-next btn-place-order" onclick="submitOrder()">
                            Оформить заказ <i class="bi bi-check2"></i>
                        </button>
                    </div>
                </div>

                <!-- Step 3: Успех -->
                <div class="checkout-step" id="step-3">
                    <div class="success-message">
                        <div class="success-icon"><i class="bi bi-check-lg"></i></div>
                        <h2 class="success-title">Заказ #<span id="orderNumber">—</span> принят</h2>
                        <p class="success-subtitle">Мы отправили информацию о заказе на ваш email</p>
                        
                        <div class="mt-8">
                            <a href="<?= Url::to(['/catalog/catalog/index']) ?>" class="btn btn-primary" style="width: 100%;">
                                Продолжить покупки
                            </a>
                            <div class="mt-4">
                                <a href="#" id="trackOrderLink" class="text-muted" style="text-decoration: underline;">Отследить заказ</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="order-summary">
                <h3>Состав заказа</h3>

                <div class="summary-items">
                    <?php foreach ($items as $item): ?>
                        <?php if (!$item->product) continue; ?>
                        <div class="summary-item">
                            <img src="<?= Html::encode($item->product->getMainImageUrl()) ?>" alt="" class="summary-item-img">
                            <div class="summary-item-info">
                                <span class="summary-item-title"><?= Html::encode($item->product->name) ?></span>
                                <div class="summary-item-meta">
                                    <?php if ($item->size): ?>
                                        <span><?= Html::encode($item->size) ?></span>
                                    <?php endif; ?>
                                    <span>× <?= (int)$item->quantity ?></span>
                                </div>
                            </div>
                            <span class="summary-item-price"><?= number_format($item->price * $item->quantity, 2) ?> BYN</span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="summary-totals">
                    <div class="summary-row">
                        <span>Товары</span>
                        <span id="productsTotal"><?= number_format($total, 2) ?> BYN</span>
                    </div>
                    <div class="summary-row" id="deliveryCostRow">
                        <span>Доставка</span>
                        <span id="deliveryCost">Бесплатно</span>
                    </div>
                    <div class="summary-total">
                        <span>Итого</span>
                        <span id="finalTotal"><?= number_format($total, 2) ?> BYN</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
var orderTotal       = <?= (float)$total ?>;
var orderDeliveryCost = 0;
var selectedDelivery = 'pickup_minsk';
var csrfToken        = <?= json_encode($csrfToken) ?>;
var createUrl        = <?= json_encode($createUrl) ?>;

function goStep(step) {
    document.querySelectorAll('.checkout-step').forEach(function(el) {
        el.classList.remove('active');
    });
    document.getElementById('step-' + step).classList.add('active');

    document.querySelectorAll('.progress-step').forEach(function(el) {
        var s = parseInt(el.dataset.step);
        if (step === 3) {
            el.classList.add('completed');
            el.classList.remove('active');
        } else {
            el.classList.toggle('completed', s < step);
            el.classList.toggle('active', s === step);
        }
    });

    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function validateStep1() {
    var name  = document.getElementById('field-name').value.trim();
    var phone = document.getElementById('field-phone').value.trim();
    var address = document.getElementById('field-address').value.trim();

    if (!name) {
        alert('Укажите имя и фамилию');
        document.getElementById('field-name').focus();
        return;
    }
    if (!phone) {
        alert('Укажите номер телефона');
        document.getElementById('field-phone').focus();
        return;
    }
    
    if (selectedDelivery !== 'pickup_minsk' && !address) {
        alert('Укажите адрес доставки');
        document.getElementById('field-address').focus();
        return;
    }

    goStep(2);
}

function updateDelivery(cost, method) {
    orderDeliveryCost = cost;
    selectedDelivery  = method;

    // Обновляем визуальное выделение карточек
    document.querySelectorAll('input[name="delivery"]').forEach(radio => {
        if (radio.checked) {
            radio.closest('.option-card').classList.add('selected');
        } else {
            radio.closest('.option-card').classList.remove('selected');
        }
    });

    document.getElementById('deliveryCost').textContent = cost === 0 ? 'Бесплатно' : cost + ' BYN';
    document.getElementById('finalTotal').textContent   = (orderTotal + cost).toFixed(2) + ' BYN';

    // Показываем поле адреса для курьерской доставки
    var needAddress = (method !== 'pickup_minsk');
    if (needAddress) {
        document.getElementById('addressGroup').classList.remove('hidden');
        document.getElementById('field-address').required = true;
    } else {
        document.getElementById('addressGroup').classList.add('hidden');
        document.getElementById('field-address').required = false;
    }
}

function updatePayment(radio) {
    document.querySelectorAll('input[name="payment"]').forEach(r => {
        if (r.checked) {
            r.closest('.option-card').classList.add('selected');
        } else {
            r.closest('.option-card').classList.remove('selected');
        }
    });
}

function submitOrder() {
    var name     = document.getElementById('field-name').value.trim();
    var phone    = document.getElementById('field-phone').value.trim();
    var email    = document.getElementById('field-email').value.trim();
    var comment  = document.getElementById('field-comment').value.trim();
    var address  = document.getElementById('field-address').value.trim();
    var payment  = document.querySelector('input[name="payment"]:checked');

    var btn = document.querySelector('.btn-place-order');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-arrow-repeat spinner"></i> Обработка...';

    var params = new URLSearchParams();
    params.append('name',     name);
    params.append('phone',    phone);
    params.append('email',    email);
    params.append('comment',  comment);
    params.append('delivery', selectedDelivery);
    params.append('payment_method', payment ? payment.value : 'card_online');
    params.append('address',  address);
    params.append('country',  'belarus');

    fetch(createUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-CSRF-Token': csrfToken,
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: params.toString()
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            document.getElementById('orderNumber').textContent = data.order_number || data.order_id;
            // Установить ссылку на отслеживание
            document.getElementById('trackOrderLink').href = '/account/account/orders';
            goStep(3);
        } else {
            btn.disabled = false;
            btn.innerHTML = 'Оформить заказ <i class="bi bi-check2"></i>';
            alert(data.message || 'Ошибка оформления заказа. Попробуйте ещё раз.');
        }
    })
    .catch(function(err) {
        btn.disabled = false;
        btn.innerHTML = 'Оформить заказ <i class="bi bi-check2"></i>';
        alert('Ошибка соединения с сервером. Попробуйте позже.');
        console.error('Order create error:', err);
    });
}
</script>
