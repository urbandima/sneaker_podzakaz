/**
 * Checkout JavaScript — Single Screen
 * No steps, direct form submission
 */

var orderTotal = 0;
var orderDeliveryCost = 0;
var orderDiscount = 0;
var selectedCountry = '';
var selectedDelivery = '';

// Переключение стран
function selectCountry(country, event) {
    event.preventDefault();
    document.querySelectorAll('.country-tab').forEach(function (t) { t.classList.remove('active'); });
    event.currentTarget.classList.add('active');
    selectedCountry = country;
    document.getElementById('selectedCountry').value = country;

    var belarusDelivery = document.getElementById('deliveryBelarus');
    var russiaDelivery = document.getElementById('deliveryRussia');
    var addressField = document.getElementById('field-address');
    var addressLabel = document.getElementById('addressLabel');

    if (country === 'belarus') {
        belarusDelivery.style.display = '';
        russiaDelivery.style.display = 'none';
        var first = belarusDelivery.querySelector('input[type="radio"]');
        if (first) { first.checked = true; updateDelivery(0, first.value); }
        addressField.placeholder = 'Укажите адрес доставки';
        addressLabel.innerHTML = 'Адрес доставки <span class="text-danger">*</span>';
    } else {
        belarusDelivery.style.display = 'none';
        russiaDelivery.style.display = '';
        var sdek = russiaDelivery.querySelector('input[type="radio"]');
        if (sdek) { sdek.checked = true; updateDelivery(0, sdek.value); }
        addressField.placeholder = 'Город, улица, дом, квартира';
        addressLabel.innerHTML = 'Адрес пункта выдачи СДЭК <span class="text-danger">*</span>';
    }
}

// Обновление стоимости доставки
function updateDelivery(cost, method) {
    orderDeliveryCost = cost;
    selectedDelivery = method;

    var deliveryEl = document.getElementById('deliveryCost');
    if (method === 'pickup_minsk') {
        deliveryEl.textContent = 'Бесплатно';
    } else if (method === 'sdek') {
        deliveryEl.textContent = 'По тарифам СДЭК';
    } else {
        deliveryEl.textContent = cost.toFixed(2) + ' BYN';
    }

    // Показываем/скрываем адрес
    var needAddress = (method !== 'pickup_minsk');
    document.getElementById('addressGroup').style.display = needAddress ? 'block' : 'none';
    document.getElementById('field-address').required = needAddress;

    updateTotal();
}

function updateTotal() {
    var total = orderTotal + orderDeliveryCost - orderDiscount;
    document.getElementById('finalTotal').textContent = total.toFixed(2) + ' BYN';
}

// Промокод
function applyCoupon() {
    var code = document.getElementById('couponCode').value.trim().toUpperCase();
    if (!code) { showCouponMessage('Введите промокод', 'error'); return; }

    var testCoupons = {
        'SALE10': { type: 'percent', value: 10, name: 'Скидка 10%' },
        'SALE20': { type: 'percent', value: 20, name: 'Скидка 20%' },
        'WELCOME': { type: 'fixed', value: 50, name: 'Скидка 50 BYN' },
        'FREESHIP': { type: 'shipping', value: 0, name: 'Бесплатная доставка' }
    };
    var coupon = testCoupons[code];
    if (!coupon) { showCouponMessage('Промокод не найден', 'error'); return; }

    if (coupon.type === 'percent') orderDiscount = orderTotal * (coupon.value / 100);
    else if (coupon.type === 'fixed') orderDiscount = Math.min(coupon.value, orderTotal);
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
    setTimeout(function () { el.textContent = ''; el.className = 'coupon-message'; }, 5000);
}

// Отправка заказа
function submitOrder() {
    var name = document.getElementById('field-name').value.trim();
    var phone = document.getElementById('field-phone').value.trim();
    var email = document.getElementById('field-email').value.trim();
    var comment = document.getElementById('field-comment').value.trim();
    var address = document.getElementById('field-address').value.trim();
    var delivery = document.querySelector('input[name="delivery"]:checked');

    // Валидация
    if (!name) { alert('Укажите ФИО'); document.getElementById('field-name').focus(); return; }
    if (!phone) { alert('Укажите телефон'); document.getElementById('field-phone').focus(); return; }
    if (!/^[\+]?[\d\s\-\(\)]{7,}$/.test(phone)) { alert('Некорректный формат телефона'); document.getElementById('field-phone').focus(); return; }
    if (!delivery) { alert('Выберите способ доставки'); return; }
    if (delivery.value !== 'pickup_minsk' && !address) { alert('Укажите адрес доставки'); document.getElementById('field-address').focus(); return; }

    var btn = document.querySelector('.btn-place-order');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Оформляем...';

    var params = new URLSearchParams();
    params.append('name', name);
    params.append('phone', phone);
    params.append('email', email);
    params.append('comment', comment);
    params.append('delivery', delivery.value);
    params.append('address', address);
    params.append('country', selectedCountry);

    fetch(createUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-CSRF-Token': csrfToken,
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: params.toString()
    })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.success) {
                document.getElementById('orderNumber').textContent = data.order_number || data.order_id;
                document.getElementById('successModal').classList.add('active');
                document.body.style.overflow = 'hidden';
            } else {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-check-circle-fill"></i> Оформить заказ';
                alert(data.message || 'Ошибка оформления заказа');
            }
        })
        .catch(function (err) {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-circle-fill"></i> Оформить заказ';
            alert('Ошибка соединения. Попробуйте позже.');
        });
}

// Инициализация
document.addEventListener('DOMContentLoaded', function () {
    // Переменные будут инициализированы из inline script в view
    updateTotal();
});
