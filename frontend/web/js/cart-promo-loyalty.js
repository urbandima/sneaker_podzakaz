/**
 * Cart Promo Code & Loyalty Points Integration
 * 
 * Интеграция промокодов и баллов лояльности в корзину
 */

// Глобальные переменные
let appliedPromoCode = null;
let appliedPoints = 0;
let cartTotal = 0;
let loyaltyBalance = 0;

/**
 * Инициализация при загрузке страницы
 */
function initCartPromoLoyalty() {
    // Получаем данные корзины
    const totalEl = document.getElementById('productsTotal');
    if (totalEl) {
        const totalText = totalEl.textContent.replace(/[^\d.,]/g, '').replace(',', '.');
        cartTotal = parseFloat(totalText) || 0;
    }
    
    // Загружаем баланс лояльности
    loadLoyaltyBalance();
}

/**
 * Загрузка баланса лояльности
 */
async function loadLoyaltyBalance() {
    try {
        const response = await fetch('/api/v1/loyalty/balance', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'include'
        });
        
        const data = await response.json();
        
        if (data.success) {
            loyaltyBalance = data.balance || 0;
            updateLoyaltyUI();
        }
    } catch (error) {
        console.error('Error loading loyalty balance:', error);
    }
}

/**
 * Обновление UI лояльности
 */
function updateLoyaltyUI() {
    const balanceEl = document.getElementById('loyaltyBalance');
    const sliderEl = document.getElementById('pointsSlider');
    const maxLabelEl = document.getElementById('maxPointsLabel');
    
    if (balanceEl) {
        balanceEl.textContent = loyaltyBalance.toLocaleString('ru-RU');
    }
    
    if (sliderEl && loyaltyBalance > 0) {
        // Максимум 50% от суммы заказа
        const maxPoints = Math.min(loyaltyBalance, Math.floor(cartTotal * 50));
        sliderEl.max = maxPoints;
        sliderEl.value = appliedPoints;
        
        if (maxLabelEl) {
            maxLabelEl.textContent = maxPoints.toLocaleString('ru-RU');
        }
    }
}

/**
 * Применить промокод
 */
async function applyPromoCode() {
    const input = document.getElementById('promoCodeInput');
    const code = input.value.trim().toUpperCase();
    
    if (!code) {
        showError('promoError', 'Введите промокод');
        return;
    }
    
    try {
        const response = await fetch('/api/v1/coupon/validate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'include',
            body: JSON.stringify({
                code: code,
                order_amount: cartTotal
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            appliedPromoCode = data.coupon;
            
            // Показываем применённый промокод
            document.getElementById('promoCodeInput').style.display = 'none';
            document.querySelector('.btn-promo-apply').style.display = 'none';
            
            const appliedEl = document.getElementById('promoApplied');
            appliedEl.style.display = 'block';
            
            document.getElementById('promoCodeText').textContent = code;
            document.getElementById('promoDiscountAmount').textContent = 
                data.discount.toFixed(2) + ' BYN';
            
            // Обновляем итоговую сумму
            updateCartTotals();
            
            // Скрываем ошибку
            document.getElementById('promoError').style.display = 'none';
        } else {
            showError('promoError', data.message || 'Недействительный промокод');
        }
    } catch (error) {
        console.error('Error applying promo code:', error);
        showError('promoError', 'Ошибка при применении промокода');
    }
}

/**
 * Удалить промокод
 */
function removePromoCode() {
    appliedPromoCode = null;
    
    // Восстанавливаем форму ввода
    document.getElementById('promoCodeInput').style.display = 'block';
    document.getElementById('promoCodeInput').value = '';
    document.querySelector('.btn-promo-apply').style.display = 'block';
    
    document.getElementById('promoApplied').style.display = 'none';
    document.getElementById('promoDiscountRow').style.display = 'none';
    
    // Обновляем итоговую сумму
    updateCartTotals();
}

/**
 * Обновить списание баллов
 */
function updatePointsRedeem(value) {
    appliedPoints = parseInt(value) || 0;
    
    const discount = (appliedPoints * 0.01).toFixed(2); // 1 балл = 0.01 BYN
    
    const discountEl = document.getElementById('pointsDiscount');
    if (discountEl) {
        discountEl.textContent = discount + ' BYN';
    }
    
    // Обновляем итоговую сумму
    updateCartTotals();
}

/**
 * Обновить итоговые суммы в корзине
 */
function updateCartTotals() {
    let total = cartTotal;
    let promoDiscount = 0;
    let pointsDiscount = appliedPoints * 0.01;
    
    // Считаем скидку по промокоду
    if (appliedPromoCode) {
        if (appliedPromoCode.type === 'percentage') {
            promoDiscount = total * (appliedPromoCode.value / 100);
        } else if (appliedPromoCode.type === 'fixed') {
            promoDiscount = appliedPromoCode.value;
        }
        
        // Показываем строку скидки
        const promoRow = document.getElementById('promoDiscountRow');
        promoRow.style.display = 'flex';
        document.getElementById('promoDiscountValue').textContent = 
            '-' + promoDiscount.toFixed(2) + ' BYN';
    }
    
    // Показываем скидку баллами
    if (pointsDiscount > 0) {
        const pointsRow = document.getElementById('pointsDiscountRow');
        pointsRow.style.display = 'flex';
        document.getElementById('pointsDiscountValue').textContent = 
            '-' + pointsDiscount.toFixed(2) + ' BYN';
    } else {
        document.getElementById('pointsDiscountRow').style.display = 'none';
    }
    
    // Итоговая сумма
    total = total - promoDiscount - pointsDiscount;
    
    // Доставка
    const deliveryCost = total >= 100 ? 0 : 10;
    const deliveryEl = document.getElementById('deliveryCost');
    if (deliveryEl) {
        deliveryEl.textContent = deliveryCost === 0 ? 'Бесплатно' : deliveryCost.toFixed(2) + ' BYN';
    }
    
    // Финальная сумма
    const finalTotal = total + deliveryCost;
    const finalEl = document.getElementById('finalTotal');
    if (finalEl) {
        finalEl.textContent = finalTotal.toFixed(2) + ' BYN';
    }
    
    // До бесплатной доставки
    const toFreeEl = document.getElementById('toFreeDelivery');
    if (toFreeEl && total < 100) {
        toFreeEl.textContent = (100 - total).toFixed(2) + ' BYN';
    }
}

/**
 * Показать ошибку
 */
function showError(elementId, message) {
    const el = document.getElementById(elementId);
    if (el) {
        el.textContent = message;
        el.style.display = 'block';
    }
}

/**
 * Получить данные о скидках для оформления заказа
 */
function getDiscountData() {
    return {
        promo_code: appliedPromoCode ? appliedPromoCode.code : null,
        promo_discount: appliedPromoCode ? appliedPromoCode.discount : 0,
        loyalty_points: appliedPoints,
        loyalty_discount: appliedPoints * 0.01
    };
}

// Инициализация при загрузке страницы
document.addEventListener('DOMContentLoaded', initCartPromoLoyalty);
