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
        <h1><i class="bi bi-bag-check"></i> Оформление заказа</h1>

        <!-- Progress Bar -->
        <div class="checkout-progress">
            <div class="progress-step active" data-step="1">
                <span class="step-number">1</span>
                <span class="step-name">Корзина</span>
            </div>
            <div class="progress-step" data-step="2">
                <span class="step-number">2</span>
                <span class="step-name">Данные</span>
            </div>
            <div class="progress-step" data-step="3">
                <span class="step-number">3</span>
                <span class="step-name">Доставка</span>
            </div>
            <div class="progress-step" data-step="4">
                <span class="step-number">4</span>
                <span class="step-name">Готово</span>
            </div>
        </div>

        <div class="checkout-layout">
            <!-- Checkout Form -->
            <div class="checkout-form">

                <!-- Step 1: Проверка корзины -->
                <div class="checkout-step active" id="step-1">
                    <h2>Ваши товары</h2>
                    <?php if (!empty($items)): ?>
                        <div class="cart-review-list">
                            <?php foreach ($items as $item): ?>
                                <?php $prod = $item->product; ?>
                                <?php if (!$prod) continue; ?>
                                <div class="cart-review-item">
                                    <img src="<?= Html::encode($prod->getMainImageUrl()) ?>"
                                         alt="<?= Html::encode($prod->name) ?>"
                                         class="cart-review-img">
                                    <div class="cart-review-info">
                                        <a href="<?= $prod->getUrl() ?>" class="cart-review-name">
                                            <?= Html::encode($prod->name) ?>
                                        </a>
                                        <?php if ($item->size): ?>
                                            <small class="text-muted">Размер: <?= Html::encode($item->size) ?></small>
                                        <?php endif; ?>
                                        <?php if ($item->color): ?>
                                            <small class="text-muted">Цвет: <?= Html::encode($item->color) ?></small>
                                        <?php endif; ?>
                                        <div class="cart-review-qty">× <?= (int)$item->quantity ?></div>
                                    </div>
                                    <div class="cart-review-price">
                                        <?= number_format($item->price * $item->quantity, 2) ?> BYN
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">Корзина пуста. <a href="<?= Url::to(['/catalog/catalog/index']) ?>">Перейти в каталог</a></p>
                    <?php endif; ?>
                    <button class="btn-next" onclick="goStep(2)">
                        Далее <i class="bi bi-arrow-right"></i>
                    </button>
                </div>

                <!-- Step 2: Контактные данные -->
                <div class="checkout-step" id="step-2">
                    <h2>Ваши данные</h2>
                    <div class="form-group">
                        <label for="field-name">Имя и фамилия <span class="text-danger">*</span></label>
                        <input type="text" id="field-name" name="name" class="form-control"
                               placeholder="Иван Иванов"
                               value="<?= Html::encode($customer ? $customer->getFullName() : '') ?>"
                               required maxlength="100">
                    </div>
                    <div class="form-group mt-3">
                        <label for="field-phone">Телефон <span class="text-danger">*</span></label>
                        <input type="tel" id="field-phone" name="phone" class="form-control"
                               placeholder="+375 (XX) XXX-XX-XX"
                               value="<?= Html::encode($customer?->phone ?? '') ?>"
                               required maxlength="50">
                    </div>
                    <div class="form-group mt-3">
                        <label for="field-email">Email</label>
                        <input type="email" id="field-email" name="email" class="form-control"
                               placeholder="example@mail.com"
                               value="<?= Html::encode($customer?->email ?? '') ?>"
                               maxlength="255">
                    </div>
                    <div class="form-group mt-3">
                        <label for="field-comment">Комментарий к заказу</label>
                        <textarea id="field-comment" name="comment" class="form-control"
                                  rows="3" placeholder="Уточнения по заказу..."
                                  maxlength="1000"></textarea>
                    </div>
                    <div class="checkout-actions mt-4">
                        <button class="btn-back" onclick="goStep(1)">
                            <i class="bi bi-arrow-left"></i> Назад
                        </button>
                        <button class="btn-next" onclick="validateStep2()">
                            Далее <i class="bi bi-arrow-right"></i>
                        </button>
                    </div>
                </div>

                <!-- Step 3: Доставка -->
                <div class="checkout-step" id="step-3">
                    <h2>Доставка</h2>

                    <div class="shipping-section">
                        <h3>Способ доставки</h3>
                        <div class="shipping-options">
                            <label class="shipping-option">
                                <input type="radio" name="delivery" value="pickup_minsk" checked
                                       onchange="updateDelivery(0, 'pickup_minsk')">
                                <div class="option-content">
                                    <div class="option-icon">🏪</div>
                                    <div class="option-info">
                                        <span class="option-name">Самовывоз (Минск)</span>
                                        <span class="option-price">Бесплатно</span>
                                        <span class="option-time">Сегодня–завтра</span>
                                    </div>
                                    <div class="option-radio"></div>
                                </div>
                            </label>

                            <label class="shipping-option">
                                <input type="radio" name="delivery" value="courier_minsk"
                                       onchange="updateDelivery(10, 'courier_minsk')">
                                <div class="option-content">
                                    <div class="option-icon"><i class="bi bi-truck"></i></div>
                                    <div class="option-info">
                                        <span class="option-name">Курьер по Минску</span>
                                        <span class="option-price">10 BYN</span>
                                        <span class="option-time">1–2 дня</span>
                                    </div>
                                    <div class="option-radio"></div>
                                </div>
                            </label>

                            <label class="shipping-option">
                                <input type="radio" name="delivery" value="europochta"
                                       onchange="updateDelivery(5, 'europochta')">
                                <div class="option-content">
                                    <div class="option-icon"><i class="bi bi-envelope"></i></div>
                                    <div class="option-info">
                                        <span class="option-name">Европочта</span>
                                        <span class="option-price">5 BYN</span>
                                        <span class="option-time">3–7 дней</span>
                                    </div>
                                    <div class="option-radio"></div>
                                </div>
                            </label>

                            <label class="shipping-option">
                                <input type="radio" name="delivery" value="belpochta"
                                       onchange="updateDelivery(4, 'belpochta')">
                                <div class="option-content">
                                    <div class="option-icon"><i class="bi bi-mailbox"></i></div>
                                    <div class="option-info">
                                        <span class="option-name">Белпочта</span>
                                        <span class="option-price">4 BYN</span>
                                        <span class="option-time">5–10 дней</span>
                                    </div>
                                    <div class="option-radio"></div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Адрес доставки (скрыт для самовывоза) -->
                    <div class="form-group mt-3" id="addressGroup" style="display:none">
                        <label for="field-address">Адрес доставки <span class="text-danger">*</span></label>
                        <input type="text" id="field-address" name="address" class="form-control"
                               placeholder="Город, улица, дом, квартира"
                               value="<?= Html::encode($customer?->default_address ?? '') ?>"
                               maxlength="500">
                    </div>

                    <div class="checkout-actions mt-4">
                        <button class="btn-back" onclick="goStep(2)">
                            <i class="bi bi-arrow-left"></i> Назад
                        </button>
                        <button class="btn-next btn-place-order" onclick="submitOrder()">
                            <i class="bi bi-bag-check"></i> Оформить заказ
                        </button>
                    </div>
                </div>

                <!-- Step 4: Успех -->
                <div class="checkout-step" id="step-4">
                    <div class="order-success text-center py-5">
                        <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
                        <h2 class="mt-3">Заказ оформлен!</h2>
                        <p class="text-muted">Ваш заказ <strong>#<span id="orderNumber">—</span></strong> успешно создан</p>
                        <p class="text-muted">Мы свяжемся с вами для подтверждения</p>
                        <div class="mt-4 d-flex gap-3 justify-content-center">
                            <a href="<?= Url::to(['/account/account/orders']) ?>" class="btn btn-primary">
                                <i class="bi bi-box-seam"></i> Мои заказы
                            </a>
                            <a href="<?= Url::to(['/catalog/catalog/index']) ?>" class="btn btn-outline-secondary">
                                <i class="bi bi-grid"></i> Продолжить покупки
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="order-summary">
                <h3>Ваш заказ</h3>

                <div class="summary-items">
                    <?php foreach ($items as $item): ?>
                        <?php if (!$item->product) continue; ?>
                        <div class="summary-item">
                            <span class="summary-item-name">
                                <?= Html::encode(mb_strimwidth($item->product->name, 0, 35, '…')) ?>
                                <?php if ($item->size): ?>
                                    <small class="text-muted">(<?= Html::encode($item->size) ?>)</small>
                                <?php endif; ?>
                                × <?= (int)$item->quantity ?>
                            </span>
                            <span class="summary-item-price"><?= number_format($item->price * $item->quantity, 2) ?> BYN</span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="summary-totals">
                    <div class="summary-row">
                        <span>Товары:</span>
                        <span id="productsTotal"><?= number_format($total, 2) ?> BYN</span>
                    </div>
                    <div class="summary-row" id="deliveryCostRow">
                        <span>Доставка:</span>
                        <span id="deliveryCost">0 BYN</span>
                    </div>
                    <div class="summary-total">
                        <span>Итого:</span>
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
        el.classList.toggle('completed', s < step);
        el.classList.toggle('active', s === step);
    });

    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function validateStep2() {
    var name  = document.getElementById('field-name').value.trim();
    var phone = document.getElementById('field-phone').value.trim();

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
    // Простая валидация телефона
    if (!/^[\+]?[\d\s\-\(\)]{7,}$/.test(phone)) {
        alert('Некорректный формат телефона');
        document.getElementById('field-phone').focus();
        return;
    }

    goStep(3);
}

function updateDelivery(cost, method) {
    orderDeliveryCost = cost;
    selectedDelivery  = method;

    document.getElementById('deliveryCost').textContent = cost + ' BYN';
    document.getElementById('finalTotal').textContent   = (orderTotal + cost).toFixed(2) + ' BYN';

    // Показываем поле адреса для курьерской доставки
    var needAddress = (method !== 'pickup_minsk');
    document.getElementById('addressGroup').style.display = needAddress ? 'block' : 'none';
    document.getElementById('field-address').required = needAddress;
}

function submitOrder() {
    var name     = document.getElementById('field-name').value.trim();
    var phone    = document.getElementById('field-phone').value.trim();
    var email    = document.getElementById('field-email').value.trim();
    var comment  = document.getElementById('field-comment').value.trim();
    var address  = document.getElementById('field-address').value.trim();
    var delivery = document.querySelector('input[name="delivery"]:checked');

    // Финальная валидация
    if (!name || !phone) {
        alert('Заполните имя и телефон');
        goStep(2);
        return;
    }
    if (!delivery) {
        alert('Выберите способ доставки');
        return;
    }
    if (delivery.value !== 'pickup_minsk' && !address) {
        alert('Укажите адрес доставки');
        document.getElementById('field-address').focus();
        return;
    }

    var btn = document.querySelector('.btn-place-order');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Обработка...';

    var params = new URLSearchParams();
    params.append('name',     name);
    params.append('phone',    phone);
    params.append('email',    email);
    params.append('comment',  comment);
    params.append('delivery', delivery.value);
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
            goStep(4);
        } else {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-bag-check"></i> Оформить заказ';
            alert(data.message || 'Ошибка оформления заказа. Попробуйте ещё раз.');
        }
    })
    .catch(function(err) {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-bag-check"></i> Оформить заказ';
        alert('Ошибка соединения с сервером. Попробуйте позже.');
        console.error('Order create error:', err);
    });
}
</script>

<style>
.checkout-layout {
    display: grid;
    grid-template-columns: 1fr 350px;
    gap: 2rem;
    align-items: start;
}
.cart-review-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.75rem 0;
    border-bottom: 1px solid #eee;
}
.cart-review-img {
    width: 60px;
    height: 60px;
    object-fit: contain;
    border-radius: 6px;
    background: #f9f9f9;
}
.cart-review-info {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 2px;
}
.cart-review-name {
    font-weight: 500;
    text-decoration: none;
    color: inherit;
}
.cart-review-price {
    font-weight: 600;
    white-space: nowrap;
}
.summary-item {
    display: flex;
    justify-content: space-between;
    gap: 0.5rem;
    padding: 0.4rem 0;
    font-size: 0.9rem;
    border-bottom: 1px solid #f0f0f0;
}
.summary-item-price { white-space: nowrap; font-weight: 500; }
.form-group label { font-weight: 500; margin-bottom: 4px; display: block; }
.checkout-actions { display: flex; gap: 1rem; flex-wrap: wrap; }
.order-success i.bi-check-circle-fill { color: #22c55e; }
@media (max-width: 768px) {
    .checkout-layout { grid-template-columns: 1fr; }
}
</style>
