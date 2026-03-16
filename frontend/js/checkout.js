/**
 * Checkout JavaScript
 */

let currentStep = 1;
let orderData = {
    items: [],
    shipping: 'pickup',
    payment: 'card',
    promoCode: null,
    loyaltyPoints: 0,
};

/**
 * Переход к следующему шагу
 */
function nextStep(step) {
    // Валидация текущего шага
    if (!validateStep(currentStep)) {
        return;
    }
    
    // Скрываем текущий шаг
    document.querySelectorAll('.checkout-step').forEach(el => el.classList.remove('active'));
    
    // Показываем следующий шаг
    const nextStepEl = document.getElementById('step-' + step);
    if (nextStepEl) {
        nextStepEl.classList.add('active');
        currentStep = step;
    }
    
    // Обновляем progress bar
    updateProgressBar(step);
    
    // Скролл наверх
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

/**
 * Переход к предыдущему шагу
 */
function prevStep(step) {
    document.querySelectorAll('.checkout-step').forEach(el => el.classList.remove('active'));
    
    const prevStepEl = document.getElementById('step-' + step);
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
    document.querySelectorAll('.progress-step').forEach(el => {
        const stepNum = parseInt(el.dataset.step);
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
    // Добавить логику валидации
    return true;
}

/**
 * Выбор доставки
 */
document.querySelectorAll('input[name="shipping"]').forEach(input => {
    input.addEventListener('change', (e) => {
        orderData.shipping = e.target.value;
        updateOrderSummary();
    });
});

/**
 * Выбор оплаты
 */
document.querySelectorAll('input[name="payment"]').forEach(input => {
    input.addEventListener('change', (e) => {
        orderData.payment = e.target.value;
    });
});

/**
 * Обновить итоговую сумму
 */
function updateOrderSummary() {
    const productsTotal = parseFloat(document.getElementById('productsTotal')?.textContent.replace(/[^\d.,]/g, '').replace(',', '.')) || 0;
    
    let deliveryCost = 0;
    
    switch (orderData.shipping) {
        case 'pickup':
            deliveryCost = 0;
            break;
        case 'courier':
            deliveryCost = 10;
            break;
        case 'cdek':
            deliveryCost = 15;
            break;
    }
    
    // Бесплатная доставка от 100 BYN
    if (productsTotal >= 100) {
        deliveryCost = 0;
    }
    
    const deliveryEl = document.getElementById('deliveryCost');
    if (deliveryEl) {
        deliveryEl.textContent = deliveryCost === 0 ? 'Бесплатно' : deliveryCost.toFixed(2) + ' BYN';
    }
    
    // Итоговая сумма
    const promoDiscount = parseFloat(document.getElementById('promoDiscountValue')?.textContent.replace(/[^\d.,]/g, '').replace(',', '.')) || 0;
    const finalTotal = productsTotal - promoDiscount + deliveryCost;
    
    const finalEl = document.getElementById('finalTotal');
    if (finalEl) {
        finalEl.textContent = finalTotal.toFixed(2) + ' BYN';
    }
}

/**
 * Оформить заказ
 */
async function submitOrder() {
    if (!validateStep(currentStep)) {
        return;
    }
    
    const btn = document.querySelector('.btn-place-order');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Обработка...';
    
    try {
        const response = await fetch('/api/v1/orders', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(orderData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            nextStep(4);
            if (data.order_number) {
                document.getElementById('orderNumber').textContent = data.order_number;
            }
        } else {
            alert(data.message || 'Ошибка при оформлении заказа');
            btn.disabled = false;
            btn.innerHTML = 'Оформить заказ';
        }
    } catch (error) {
        console.error('Order submit error:', error);
        alert('Ошибка при оформлении заказа');
        btn.disabled = false;
        btn.innerHTML = 'Оформить заказ';
    }
}

// Инициализация
document.addEventListener('DOMContentLoaded', () => {
    updateOrderSummary();
});
