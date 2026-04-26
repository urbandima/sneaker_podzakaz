<?php
/** @var yii\web\View $this */
/** @var array $items          Позиции корзины */
/** @var float $total          Итого по товарам */
/** @var \app\backend\modules\account\models\Customer|null $customer */

use yii\helpers\Html;
use yii\helpers\Url;
use app\frontend\assets\AppAsset;

AppAsset::register($this);

$this->title = 'Оформление заказа';

$createUrl  = Url::to(['/order/create']);
$csrfToken  = Yii::$app->request->csrfToken;
$successUrl = Url::to(['/order/success']);

// Russian plural for "товар"
$cnt = count($items);
if ($cnt % 10 === 1 && $cnt % 100 !== 11) {
    $itemWord = 'товар';
} elseif ($cnt % 10 >= 2 && $cnt % 10 <= 4 && ($cnt % 100 < 10 || $cnt % 100 >= 20)) {
    $itemWord = 'товара';
} else {
    $itemWord = 'товаров';
}
?>

<div class="checkout-page cart-blur-surface">
    <div class="container">
        <h1><i class="bi bi-bag-check"></i> Оформление заказа</h1>

        <!-- Mobile: collapsible order summary (hidden on desktop) -->
        <div class="mobile-summary-toggle" id="mobileSummaryToggle">
            <div class="mobile-summary-header" onclick="toggleMobileSummary()">
                <div class="mobile-summary-header-left">
                    <i class="bi bi-bag"></i>
                    Ваш заказ (<?= $cnt ?> <?= $itemWord ?>)
                </div>
                <div class="mobile-summary-header-right">
                    <span class="mobile-summary-price" id="mobileFinalTotal"><?= number_format($total, 2) ?> BYN</span>
                    <i class="bi bi-chevron-down mobile-toggle-icon"></i>
                </div>
            </div>
            <div class="mobile-summary-body">
                <?php foreach ($items as $item): ?>
                    <?php if (!$item->product) continue; ?>
                    <div class="summary-item">
                        <img src="<?= Html::encode($item->product->getMainImageUrl()) ?>"
                             alt="<?= Html::encode($item->product->name) ?>"
                             class="summary-item-img">
                        <div class="summary-item-info">
                            <span class="summary-item-title"><?= Html::encode(mb_strimwidth($item->product->name, 0, 35, '…')) ?></span>
                            <?php if ($item->size): ?>
                                <span class="summary-item-meta">Размер: <?= Html::encode($item->size) ?></span>
                            <?php endif; ?>
                            <span class="summary-item-meta"><?= (int)$item->quantity ?> × <?= number_format($item->price, 2) ?> BYN</span>
                        </div>
                        <span class="summary-item-price"><?= number_format($item->price * $item->quantity, 2) ?> BYN</span>
                    </div>
                <?php endforeach; ?>
                <div class="summary-totals mt-2">
                    <div class="summary-row">
                        <span>Доставка:</span>
                        <span id="mobileDeliveryCost" class="delivery-cost">Бесплатно</span>
                    </div>
                    <div class="summary-total">
                        <span>Итого:</span>
                        <span id="mobileFinalTotal2"><?= number_format($total, 2) ?> BYN</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="checkout-layout">
            <!-- Левая колонка: контакты + доставка + оплата + комментарий -->
            <div class="checkout-left">

                <!-- Контактные данные — 3 колонки на одной строке -->
                <div class="checkout-section">
                    <h2><i class="bi bi-person"></i> Контактные данные</h2>
                    <div class="checkout-contact-grid">
                        <div class="form-group">
                            <label for="field-name">ФИО <span class="text-danger" aria-hidden="true">*</span><span class="sr-only">(обязательное)</span></label>
                            <input type="text" id="field-name" name="name" class="form-control"
                                   placeholder="Иванов Иван Иванович"
                                   value="<?= Html::encode($customer ? $customer->getFullName() : '') ?>"
                                   required aria-required="true" autocomplete="name"
                                   aria-describedby="error-name" maxlength="100">
                            <div id="error-name" class="invalid-feedback" role="alert" aria-live="polite"></div>
                        </div>
                        <div class="form-group">
                            <label for="field-phone">Телефон <span class="text-danger" aria-hidden="true">*</span><span class="sr-only">(обязательное)</span></label>
                            <input type="tel" id="field-phone" name="phone" class="form-control"
                                   placeholder="+375 (__) ___-__-__"
                                   value="<?= Html::encode($customer?->phone ?? '') ?>"
                                   required aria-required="true" autocomplete="tel"
                                   pattern="[+]?[0-9\s\-\(\)]{7,}"
                                   aria-describedby="error-phone" maxlength="50">
                            <div id="error-phone" class="invalid-feedback" role="alert" aria-live="polite"></div>
                        </div>
                        <div class="form-group">
                            <label for="field-email">Email</label>
                            <input type="email" id="field-email" name="email" class="form-control"
                                   placeholder="email@example.com"
                                   value="<?= Html::encode($customer?->email ?? '') ?>"
                                   autocomplete="email" inputmode="email"
                                   maxlength="255">
                        </div>
                    </div>
                </div>

                <!-- Доставка -->
                <div class="checkout-section">
                    <h2><i class="bi bi-truck"></i> Доставка</h2>

                    <div class="country-tabs">
                        <button type="button" class="country-tab active" onclick="selectCountry('belarus', event)">
                            <i class="bi bi-geo-alt-fill"></i> Беларусь
                        </button>
                        <button type="button" class="country-tab" onclick="selectCountry('russia', event)">
                            <i class="bi bi-geo-alt-fill"></i> Россия
                        </button>
                    </div>
                    <input type="hidden" name="country" id="selectedCountry" value="belarus">

                    <!-- Беларусь -->
                    <div class="shipping-options" id="deliveryBelarus">
                        <label class="shipping-option">
                            <input type="radio" name="delivery" value="pickup_minsk" checked
                                   onchange="updateDelivery(0, 'pickup_minsk')">
                            <div class="option-content">
                                <div class="option-icon"><i class="bi bi-shop"></i></div>
                                <div class="option-info">
                                    <span class="option-name">Самовывоз</span>
                                    <span class="option-desc">Минск, пр. Победителей 5</span>
                                    <span class="option-time">Сегодня–завтра</span>
                                </div>
                                <span class="option-price">Бесплатно</span>
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
                                    <span class="option-desc">Доставка по городу</span>
                                    <span class="option-time">1–2 дня</span>
                                </div>
                                <span class="option-price">10 BYN</span>
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
                                    <span class="option-desc">По Беларуси</span>
                                    <span class="option-time">3–7 дней</span>
                                </div>
                                <span class="option-price">5 BYN</span>
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
                                    <span class="option-desc">По Беларуси</span>
                                    <span class="option-time">5–10 дней</span>
                                </div>
                                <span class="option-price">4 BYN</span>
                                <div class="option-radio"></div>
                            </div>
                        </label>
                    </div>

                    <!-- Россия -->
                    <div class="shipping-options d-none" id="deliveryRussia">
                        <label class="shipping-option">
                            <input type="radio" name="delivery" value="sdek"
                                   onchange="updateDelivery(0, 'sdek')">
                            <div class="option-content">
                                <div class="option-icon"><i class="bi bi-box-seam"></i></div>
                                <div class="option-info">
                                    <span class="option-name">СДЭК</span>
                                    <span class="option-desc">По России</span>
                                    <span class="option-time">3–7 дней</span>
                                </div>
                                <span class="option-price">По тарифам СДЭК</span>
                                <div class="option-radio"></div>
                            </div>
                        </label>
                    </div>

                    <!-- Адрес: город + индекс на одной строке, затем улица -->
                    <div id="addressGroup" class="d-none">
                        <div class="address-row">
                            <div class="form-group">
                                <label for="field-city">Город <span class="text-danger" aria-hidden="true">*</span><span class="sr-only">(обязательное)</span></label>
                                <input type="text" id="field-city" name="city" class="form-control"
                                       placeholder="Минск" autocomplete="address-level2" maxlength="100">
                            </div>
                            <div class="form-group">
                                <label for="field-postal">Индекс</label>
                                <input type="text" id="field-postal" name="postal_code" class="form-control"
                                       placeholder="220000" autocomplete="postal-code" inputmode="numeric" maxlength="20">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="field-address" id="addressLabel">Улица, дом, квартира <span class="text-danger" aria-hidden="true">*</span><span class="sr-only">(обязательное)</span></label>
                            <textarea id="field-address" name="address" class="form-control"
                                      rows="2" placeholder="Улица, дом, квартира"
                                      autocomplete="street-address"
                                      maxlength="500"><?= Html::encode($customer?->default_address ?? '') ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Оплата -->
                <div class="checkout-section">
                    <h2><i class="bi bi-credit-card"></i> Оплата</h2>
                    <div class="payment-options">
                        <label class="payment-option">
                            <input type="radio" name="payment" value="bank_transfer" checked
                                   onchange="selectPayment('bank_transfer')">
                            <div class="option-content">
                                <div class="option-icon"><i class="bi bi-bank"></i></div>
                                <div class="option-info">
                                    <span class="option-name">Банковский перевод (ЕРИП)</span>
                                    <span class="option-desc">Реквизиты придут в SMS / Telegram</span>
                                </div>
                                <div class="option-radio"></div>
                            </div>
                        </label>

                        <label class="payment-option">
                            <input type="radio" name="payment" value="card_online"
                                   onchange="selectPayment('card_online')">
                            <div class="option-content">
                                <div class="option-icon"><i class="bi bi-credit-card-2-front"></i></div>
                                <div class="option-info">
                                    <span class="option-name">Оплата картой онлайн</span>
                                    <span class="option-desc">Visa, Mastercard, МИР</span>
                                </div>
                                <div class="option-radio"></div>
                            </div>
                        </label>

                        <label class="payment-option">
                            <input type="radio" name="payment" value="cash_pickup"
                                   onchange="selectPayment('cash_pickup')">
                            <div class="option-content">
                                <div class="option-icon"><i class="bi bi-cash-coin"></i></div>
                                <div class="option-info">
                                    <span class="option-name">Наличные при получении</span>
                                    <span class="option-desc">Только самовывоз, пр. Победителей 5</span>
                                </div>
                                <div class="option-radio"></div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Комментарий -->
                <div class="checkout-section">
                    <h2><i class="bi bi-chat-left-text"></i> Комментарий</h2>
                    <div class="form-group">
                        <textarea id="field-comment" name="comment" class="form-control"
                                  rows="2" placeholder="Дополнительные пожелания к заказу"
                                  maxlength="1000"></textarea>
                    </div>
                </div>
            </div>

            <!-- Правая колонка: Итого (sticky) — только на desktop/tablet -->
            <div class="checkout-right">
                <div class="order-summary">
                    <h3>Ваш заказ</h3>

                    <div class="summary-items" id="checkoutSummaryItems">
                        <?php foreach ($items as $item): ?>
                            <?php if (!$item->product) continue; ?>
                            <div class="summary-item" id="checkout-item-<?= $item->id ?>">
                                <img src="<?= Html::encode($item->product->getMainImageUrl()) ?>"
                                     alt="<?= Html::encode($item->product->name) ?>"
                                     class="summary-item-img">
                                <div class="summary-item-info">
                                    <span class="summary-item-title"><?= Html::encode(mb_strimwidth($item->product->name, 0, 40, '…')) ?></span>
                                    <?php if ($item->size): ?>
                                        <span class="summary-item-meta">Размер: <?= Html::encode($item->size) ?></span>
                                    <?php endif; ?>
                                    <div class="summary-item-qty" role="group" aria-label="Количество">
                                        <button type="button" class="qty-btn" aria-label="Уменьшить"
                                                onclick="checkoutUpdateQty(<?= $item->id ?>, <?= $item->quantity - 1 ?>)"
                                                <?= $item->quantity <= 1 ? 'disabled' : '' ?>>−</button>
                                        <span class="qty-val" id="qty-<?= $item->id ?>"><?= (int)$item->quantity ?></span>
                                        <button type="button" class="qty-btn" aria-label="Увеличить"
                                                onclick="checkoutUpdateQty(<?= $item->id ?>, <?= $item->quantity + 1 ?>)">+</button>
                                    </div>
                                </div>
                                <div class="summary-item-right">
                                    <span class="summary-item-price" id="price-<?= $item->id ?>"><?= number_format($item->price * $item->quantity, 2) ?> BYN</span>
                                    <button type="button" class="summary-item-remove" aria-label="Удалить товар"
                                            onclick="checkoutRemoveItem(<?= $item->id ?>, <?= $item->price ?>)">×</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="summary-totals">
                        <div class="summary-row">
                            <span>Товары (<?= count($items) ?>):</span>
                            <span id="productsTotal"><?= number_format($total, 2) ?> BYN</span>
                        </div>
                        <div class="summary-row" id="deliveryCostRow">
                            <span>Доставка:</span>
                            <span id="deliveryCost" class="delivery-cost">Бесплатно</span>
                        </div>

                        <div class="coupon-section" id="couponSection">
                            <div class="coupon-input-row">
                                <input type="text" id="couponCode" class="coupon-input" placeholder="Промокод" maxlength="20">
                                <button type="button" class="coupon-btn" onclick="applyCoupon()">Применить</button>
                            </div>
                            <div class="coupon-message" id="couponMessage"></div>
                        </div>
                        <div class="summary-row coupon-discount-row d-none" id="couponDiscountRow">
                            <span>Скидка:</span>
                            <span id="couponDiscount">0 BYN</span>
                        </div>

                        <div class="summary-total">
                            <span>Итого:</span>
                            <span id="finalTotal"><?= number_format($total, 2) ?> BYN</span>
                        </div>
                    </div>

                    <?php if ($total < 100): ?>
                        <div class="delivery-info">
                            <i class="bi bi-truck"></i>
                            До бесплатной доставки: <?= number_format(100 - $total, 2) ?> BYN
                        </div>
                    <?php endif; ?>

                    <button class="btn-place-order" onclick="submitOrder()">
                        <i class="bi bi-check-circle-fill"></i>
                        Оформить заказ
                    </button>

                    <a href="<?= Url::to(['/catalog']) ?>" class="btn-continue">Продолжить покупки</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Mobile: sticky bottom CTA -->
<div class="mobile-cta-bar">
    <button class="btn-place-order" onclick="submitOrder()">
        <i class="bi bi-check-circle-fill"></i>
        Оформить заказ
    </button>
</div>

<!-- Модал успеха -->
<div class="checkout-success-modal" id="successModal">
    <div class="modal-overlay"></div>
    <div class="modal-content">
        <div class="success-icon"><i class="bi bi-check-lg"></i></div>
        <h2>Заказ оформлен!</h2>
        <p>Ваш заказ <strong>#<span id="orderNumber">—</span></strong> успешно создан</p>
        <p class="text-muted">Мы свяжемся с вами для подтверждения</p>
        <div class="success-actions">
            <a href="<?= Url::to(['/account/orders']) ?>" class="btn-success-primary">
                <i class="bi bi-box-seam"></i> Мои заказы
            </a>
            <a href="<?= Url::to(['/catalog']) ?>" class="btn-success-secondary">
                <i class="bi bi-grid"></i> Продолжить покупки
            </a>
        </div>
    </div>
</div>

<script>
var orderTotal        = <?= (float)$total ?>;
var orderDeliveryCost = 0;
var orderDiscount     = 0;
var selectedDelivery  = 'pickup_minsk';
var selectedCountry   = 'belarus';
var selectedPayment   = 'bank_transfer';
var csrfToken         = <?= json_encode($csrfToken) ?>;
var createUrl         = <?= json_encode($createUrl) ?>;

// Cart editing in checkout
function checkoutUpdateQty(id, newQty) {
    if (newQty < 1) return;
    var csrfHeader = { 'X-CSRF-Token': csrfToken, 'X-Requested-With': 'XMLHttpRequest' };
    fetch('/cart/update', {
        method: 'POST',
        headers: Object.assign({ 'Content-Type': 'application/x-www-form-urlencoded' }, csrfHeader),
        body: 'id=' + id + '&quantity=' + newQty
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            var qtyEl = document.getElementById('qty-' + id);
            var priceEl = document.getElementById('price-' + id);
            var itemRow = document.getElementById('checkout-item-' + id);
            if (qtyEl) qtyEl.textContent = newQty;
            var unitPrice = data.item_price || (parseFloat(priceEl.textContent) / parseInt(qtyEl.textContent));
            if (priceEl && data.item_subtotal) priceEl.textContent = parseFloat(data.item_subtotal).toFixed(2) + ' BYN';
            // Disable minus when qty=1
            var btns = itemRow ? itemRow.querySelectorAll('.qty-btn') : [];
            if (btns[0]) btns[0].disabled = (newQty <= 1);
            // Update totals
            orderTotal = data.total || orderTotal;
            var ptEl = document.getElementById('productsTotal');
            if (ptEl) ptEl.textContent = parseFloat(data.total).toFixed(2) + ' BYN';
            updateTotal();
        }
    });
}

function checkoutRemoveItem(id, price) {
    var csrfHeader = { 'X-CSRF-Token': csrfToken, 'X-Requested-With': 'XMLHttpRequest' };
    fetch('/cart/remove/' + id, {
        method: 'POST',
        headers: Object.assign({ 'Content-Type': 'application/x-www-form-urlencoded' }, csrfHeader),
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            var row = document.getElementById('checkout-item-' + id);
            if (row) row.remove();
            orderTotal = data.total || Math.max(0, orderTotal - price);
            var ptEl = document.getElementById('productsTotal');
            if (ptEl) ptEl.textContent = parseFloat(data.total || orderTotal).toFixed(2) + ' BYN';
            updateTotal();
            if (data.count === 0 || document.querySelectorAll('#checkoutSummaryItems .summary-item').length === 0) {
                window.location.href = '/catalog';
            }
        }
    });
}

function showFieldError(fieldId, errorId, message) {
    var field = document.getElementById(fieldId);
    if (field) { field.classList.add('is-invalid'); field.focus(); }
    if (errorId) {
        var err = document.getElementById(errorId);
        if (err) err.textContent = message;
    }
}

function clearFieldErrors() {
    document.querySelectorAll('.form-control.is-invalid').forEach(function(f) { f.classList.remove('is-invalid'); });
    document.querySelectorAll('.invalid-feedback[role="alert"]').forEach(function(e) { e.textContent = ''; });
}

// Mobile summary toggle
function toggleMobileSummary() {
    document.getElementById('mobileSummaryToggle').classList.toggle('expanded');
}

// Выбор метода оплаты
function selectPayment(method) {
    selectedPayment = method;
}

// Переключение стран
function selectCountry(country, event) {
    event.preventDefault();
    document.querySelectorAll('.country-tab').forEach(function(t) { t.classList.remove('active'); });
    event.currentTarget.classList.add('active');
    selectedCountry = country;
    document.getElementById('selectedCountry').value = country;

    var belarusDelivery = document.getElementById('deliveryBelarus');
    var russiaDelivery  = document.getElementById('deliveryRussia');
    var addressLabel    = document.getElementById('addressLabel');

    if (country === 'belarus') {
        belarusDelivery.style.display = '';
        russiaDelivery.style.display  = 'none';
        var first = belarusDelivery.querySelector('input[type="radio"]');
        if (first) { first.checked = true; updateDelivery(0, first.value); }
        if (addressLabel) addressLabel.innerHTML = 'Улица, дом, квартира <span class="text-danger">*</span>';
    } else {
        belarusDelivery.style.display = 'none';
        russiaDelivery.style.display  = '';
        var sdek = russiaDelivery.querySelector('input[type="radio"]');
        if (sdek) { sdek.checked = true; updateDelivery(0, sdek.value); }
        if (addressLabel) addressLabel.innerHTML = 'Адрес пункта выдачи СДЭК <span class="text-danger">*</span>';
    }
}

// Обновление стоимости доставки
function updateDelivery(cost, method) {
    orderDeliveryCost = cost;
    selectedDelivery  = method;

    var label;
    if (method === 'pickup_minsk') {
        label = 'Бесплатно';
    } else if (method === 'sdek') {
        label = 'По тарифам СДЭК';
    } else {
        label = cost.toFixed(2) + ' BYN';
    }

    var deliveryEl       = document.getElementById('deliveryCost');
    var mobileDeliveryEl = document.getElementById('mobileDeliveryCost');
    if (deliveryEl)       deliveryEl.textContent = label;
    if (mobileDeliveryEl) mobileDeliveryEl.textContent = label;

    var needAddress = (method !== 'pickup_minsk');
    document.getElementById('addressGroup').style.display = needAddress ? 'block' : 'none';
    document.getElementById('field-address').required = needAddress;

    updateTotal();
}

function updateTotal() {
    var total = orderTotal + orderDeliveryCost - orderDiscount;
    var fmt = total.toFixed(2) + ' BYN';
    ['finalTotal', 'mobileFinalTotal', 'mobileFinalTotal2'].forEach(function(id) {
        var el = document.getElementById(id);
        if (el) el.textContent = fmt;
    });
}

// Промокод
function applyCoupon() {
    var code = document.getElementById('couponCode').value.trim().toUpperCase();
    if (!code) { showCouponMessage('Введите промокод', 'error'); return; }

    var testCoupons = {
        'SALE10':  { type: 'percent',  value: 10, name: 'Скидка 10%' },
        'SALE20':  { type: 'percent',  value: 20, name: 'Скидка 20%' },
        'WELCOME': { type: 'fixed',    value: 50, name: 'Скидка 50 BYN' },
        'FREESHIP':{ type: 'shipping', value: 0,  name: 'Бесплатная доставка' }
    };
    var coupon = testCoupons[code];
    if (!coupon) { showCouponMessage('Промокод не найден', 'error'); return; }

    if (coupon.type === 'percent')       orderDiscount = orderTotal * (coupon.value / 100);
    else if (coupon.type === 'fixed')    orderDiscount = Math.min(coupon.value, orderTotal);
    else if (coupon.type === 'shipping') orderDiscount = orderDeliveryCost;

    document.getElementById('couponDiscount').textContent = orderDiscount.toFixed(2) + ' BYN';
    document.getElementById('couponDiscountRow').style.display = 'flex';
    updateTotal();
    showCouponMessage(coupon.name + ' применена!', 'success');
}

function showCouponMessage(text, type) {
    var el = document.getElementById('couponMessage');
    el.textContent = text;
    el.className = 'coupon-message ' + (type === 'success' ? 'coupon-success' : 'coupon-error');
    setTimeout(function() { el.textContent = ''; el.className = 'coupon-message'; }, 5000);
}

// Отправка заказа
function submitOrder() {
    var name   = document.getElementById('field-name').value.trim();
    var phone  = document.getElementById('field-phone').value.trim();
    var email  = document.getElementById('field-email').value.trim();
    var comment = document.getElementById('field-comment').value.trim();
    var city   = document.getElementById('field-city') ? document.getElementById('field-city').value.trim() : '';
    var postal = document.getElementById('field-postal') ? document.getElementById('field-postal').value.trim() : '';
    var street = document.getElementById('field-address').value.trim();
    var delivery = document.querySelector('input[name="delivery"]:checked');

    // Combine address parts
    var address = [city, postal, street].filter(Boolean).join(', ');

    clearFieldErrors();
    if (!name)  { showFieldError('field-name', 'error-name', 'Укажите ФИО'); return; }
    if (!phone) { showFieldError('field-phone', 'error-phone', 'Укажите телефон'); return; }
    if (!/^[\+]?[\d\s\-\(\)]{7,}$/.test(phone)) {
        showFieldError('field-phone', 'error-phone', 'Некорректный формат телефона');
        return;
    }
    if (!delivery) { alert('Выберите способ доставки'); return; }
    if (delivery.value !== 'pickup_minsk' && !address) {
        var cityEl = document.getElementById('field-city');
        if (cityEl) { showFieldError('field-city', null, 'Укажите город'); }
        else { showFieldError('field-address', null, 'Укажите адрес доставки'); }
        return;
    }

    document.querySelectorAll('.btn-place-order').forEach(function(btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Оформляем...';
    });

    var params = new URLSearchParams();
    params.append('name',     name);
    params.append('phone',    phone);
    params.append('email',    email);
    params.append('comment',  comment);
    params.append('delivery', delivery.value);
    params.append('address',  address);
    params.append('country',  selectedCountry);
    params.append('payment',  selectedPayment);

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
            document.getElementById('successModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        } else {
            document.querySelectorAll('.btn-place-order').forEach(function(btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-check-circle-fill"></i> Оформить заказ';
            });
            alert(data.message || 'Ошибка оформления заказа');
        }
    })
    .catch(function() {
        document.querySelectorAll('.btn-place-order').forEach(function(btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-circle-fill"></i> Оформить заказ';
        });
        alert('Ошибка соединения. Попробуйте позже.');
    });
}
</script>
