/**
 * Checkout JavaScript
 * Uses SH.* utilities from utils.js
 */

var currentStep = 1;
var orderData = {
    items: [],
    shipping: 'pickup',
    payment: 'card',
    promoCode: null,
    loyaltyPoints: 0
};

/**
 * Переход к следующему шагу
 */
function nextStep(step) {
    if (!validateStep(currentStep)) return;

    document.querySelectorAll('.checkout-step').forEach(function (el) { el.classList.remove('active'); });

    var nextStepEl = document.getElementById('step-' + step);
    if (nextStepEl) {
        nextStepEl.classList.add('active');
        currentStep = step;
    }

    updateProgressBar(step);
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

/**
 * Переход к предыдущему шагу
 */
function prevStep(step) {
    document.querySelectorAll('.checkout-step').forEach(function (el) { el.classList.remove('active'); });

    var prevStepEl = document.getElementById('step-' + step);
    if (prevStepEl) {
        prevStepEl.classList.add('active');
        currentStep = step;
    }

    updateProgressBar(step);
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

/**
 * Обновить progress bar
 */
function updateProgressBar(step) {
    document.querySelectorAll('.progress-step').forEach(function (el) {
        var stepNum = parseInt(el.dataset.step);
        el.classList.remove('active', 'completed');

        if (stepNum < step) {
            el.classList.add('completed');
        } else if (stepNum === step) {
            el.classList.add('active');
        }
    });
}

/**
 * Валидация шага
 */
function validateStep(step) {
    return true;
}

/**
 * Выбор доставки
 */
document.querySelectorAll('input[name="shipping"]').forEach(function (input) {
    input.addEventListener('change', function (e) {
        orderData.shipping = e.target.value;
        updateOrderSummary();
    });
});

/**
 * Выбор оплаты
 */
document.querySelectorAll('input[name="payment"]').forEach(function (input) {
    input.addEventListener('change', function (e) {
        orderData.payment = e.target.value;
    });
});

/**
 * Обновить итоговую сумму
 */
function updateOrderSummary() {
    var productsTotalEl = document.getElementById('productsTotal');
    var productsTotal = productsTotalEl
        ? parseFloat(productsTotalEl.textContent.replace(/[^\d.,]/g, '').replace(',', '.')) || 0
        : 0;

    var deliveryCost = 0;
    switch (orderData.shipping) {
        case 'courier': deliveryCost = 10; break;
        case 'cdek':    deliveryCost = 15; break;
    }

    // Бесплатная доставка от 100 BYN
    if (productsTotal >= 100) deliveryCost = 0;

    var deliveryEl = document.getElementById('deliveryCost');
    if (deliveryEl) {
        deliveryEl.textContent = deliveryCost === 0 ? 'Бесплатно' : SH.formatCurrency(deliveryCost);
    }

    var promoDiscountEl = document.getElementById('promoDiscountValue');
    var promoDiscount = promoDiscountEl
        ? parseFloat(promoDiscountEl.textContent.replace(/[^\d.,]/g, '').replace(',', '.')) || 0
        : 0;
    var finalTotal = productsTotal - promoDiscount + deliveryCost;

    var finalEl = document.getElementById('finalTotal');
    if (finalEl) {
        finalEl.textContent = SH.formatCurrency(finalTotal);
    }
}

/**
 * Оформить заказ
 */
function submitOrder() {
    if (!validateStep(currentStep)) return;

    var btn = document.querySelector('.btn-place-order');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Обработка...';

    SH.fetch('/api/v1/orders', { method: 'POST', body: orderData })
        .then(function (data) {
            if (data.success) {
                nextStep(4);
                if (data.order_number) {
                    document.getElementById('orderNumber').textContent = data.order_number;
                }
            } else {
                SH.notify(data.message || 'Ошибка при оформлении заказа', 'error');
                btn.disabled = false;
                btn.innerHTML = 'Оформить заказ';
            }
        })
        .catch(function (error) {
            SH.notify('Ошибка при оформлении заказа', 'error');
            btn.disabled = false;
            btn.innerHTML = 'Оформить заказ';
        });
}

// Инициализация
document.addEventListener('DOMContentLoaded', function () {
    updateOrderSummary();
});
