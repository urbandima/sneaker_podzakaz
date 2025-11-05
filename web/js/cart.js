/**
 * Функционал корзины - Vanilla JS (без jQuery)
 */

// Получить CSRF токен
function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
}

// Добавить товар в корзину
function addToCart(productId, quantity = 1, size = null, color = null) {
    const formData = new FormData();
    formData.append('product_id', productId);
    formData.append('quantity', quantity);
    if (size) formData.append('size', size);
    if (color) formData.append('color', color);
    formData.append('_csrf', getCsrfToken());

    fetch('/cart/add', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Обновляем счетчик
            updateCartCount(data.count);
            
            // Анимация иконки корзины
            animateCartIcon();
            
            // Показываем уведомление
            showNotification('✓ Товар добавлен в корзину', 'success');
        } else {
            showNotification(data.message || 'Ошибка добавления', 'error');
        }
    })
    .catch(error => {
        console.error('Cart error:', error);
        showNotification('Ошибка соединения', 'error');
    });
}

// Обновить количество товара
function updateCartItem(id, quantity) {
    const formData = new FormData();
    formData.append('id', id);
    formData.append('quantity', quantity);
    formData.append('_csrf', getCsrfToken());

    fetch('/cart/update', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Обновляем подытог
            const cartItem = document.querySelector(`[data-cart-id="${id}"] .cart-item-price`);
            if (cartItem) {
                cartItem.textContent = data.subtotal + ' BYN';
            }
            // Обновляем общую сумму
            const cartTotal = document.querySelector('.cart-total');
            if (cartTotal) {
                cartTotal.textContent = data.total + ' BYN';
            }
            // Обновляем счётчик корзины
            updateCartCount(data.count);
        }
    })
    .catch(error => console.error('Update cart error:', error));
}

// Обновить количество (+ или -)
function updateQuantity(cartId, delta) {
    const qtyElement = document.querySelector(`[data-cart-id="${cartId}"] .qty-value`);
    if (!qtyElement) return;
    
    let currentQty = parseInt(qtyElement.textContent);
    let newQty = currentQty + delta;
    
    if (newQty < 1) {
        // Если меньше 1, удаляем товар
        if (confirm('Удалить товар из корзины?')) {
            removeCartItem(cartId);
        }
        return;
    }
    
    if (newQty > 99) {
        showNotification('Максимальное количество: 99', 'warning');
        return;
    }
    
    // Обновляем UI сразу (optimistic update)
    qtyElement.textContent = newQty;
    
    // Отправляем на backend
    updateCartItem(cartId, newQty);
}

// Удалить товар из корзины
function removeCartItem(id) {
    if (!confirm('Удалить товар из корзины?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('_csrf', getCsrfToken());
    
    fetch('/cart/remove/' + id, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Удаляем элемент с анимацией
            const cartItem = document.querySelector(`[data-cart-id="${id}"]`);
            if (cartItem) {
                cartItem.style.transition = 'opacity 0.3s';
                cartItem.style.opacity = '0';
                
                setTimeout(() => {
                    cartItem.remove();
                    
                    // Проверяем - есть ли еще товары
                    const remainingItems = document.querySelectorAll('.cart-item');
                    if (remainingItems.length === 0) {
                        location.reload();
                    }
                }, 300);
            }
            
            // Обновляем счетчик и сумму
            updateCartCount(data.count);
            const cartTotal = document.querySelector('.cart-total');
            if (cartTotal) {
                cartTotal.textContent = data.total + ' BYN';
            }
            
            showNotification('Товар удален', 'success');
        }
    })
    .catch(error => console.error('Remove cart error:', error));
}

// Обновить счетчик корзины в header
function updateCartCount(count) {
    const cartCount = document.getElementById('cartCount');
    if (cartCount) {
        cartCount.textContent = count;
        cartCount.style.display = count > 0 ? 'flex' : 'none';
    }
}

// Анимация иконки корзины при добавлении товара
function animateCartIcon() {
    const cartBadge = document.getElementById('cartCount');
    if (!cartBadge) return;
    
    const cartIcon = cartBadge.parentElement; // parent = .header-btn с иконкой
    
    // Shake animation
    cartIcon.classList.add('cart-shake');
    
    // Pulse badge
    cartBadge.classList.add('cart-pulse');
    
    // Remove classes after animation
    setTimeout(() => {
        cartIcon.classList.remove('cart-shake');
        cartBadge.classList.remove('cart-pulse');
    }, 600);
}

// Загрузить текущее количество при загрузке страницы
document.addEventListener('DOMContentLoaded', function() {
    fetch('/cart/count', {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.count) {
            updateCartCount(data.count);
        }
    })
    .catch(error => console.error('Load cart count error:', error));
    
    // Добавляем CSS для анимаций если еще нет
    if (!document.getElementById('cart-animations-css')) {
        const style = document.createElement('style');
        style.id = 'cart-animations-css';
        style.textContent = `
            @keyframes cart-shake {
                0%, 100% { transform: translateX(0); }
                10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
                20%, 40%, 60%, 80% { transform: translateX(5px); }
            }
            
            @keyframes cart-pulse {
                0%, 100% { transform: scale(1); }
                50% { transform: scale(1.3); background: #10b981; }
            }
            
            .cart-shake {
                animation: cart-shake 0.6s ease-in-out;
            }
            
            .cart-pulse {
                animation: cart-pulse 0.6s ease-in-out;
            }
        `;
        document.head.appendChild(style);
    }
});
