/**
 * Функционал корзины - Vanilla JS (без jQuery)
 * ИСПРАВЛЕНО: использует глобальный NotificationManager
 */

// Получить CSRF токен
function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.content : '';
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
    quantity = parseInt(quantity);
    
    // Валидация
    if (quantity < 1 || quantity > 99) {
        if (typeof showNotification === 'function') {
            showNotification('Количество должно быть от 1 до 99', 'warning');
        }
        return;
    }
    
    // Сохраняем предыдущее значение для отката при ошибке
    const cartItem = document.querySelector(`[data-cart-id="${id}"]`);
    let previousQuantity = 1;
    if (cartItem) {
        const qtyInput = cartItem.querySelector('input[type="number"]');
        if (qtyInput) {
            previousQuantity = parseInt(qtyInput.value) || 1;
        }
    }
    
    const formData = new FormData();
    formData.append('id', id);
    formData.append('quantity', quantity);
    formData.append('_csrf', getCsrfToken());

    console.log('updateCartItem вызвана:', { id, quantity, previousQuantity });
    
    fetch('/cart/update', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        console.log('Ответ сервера:', data);
        
        if (data.success) {
            // Обновляем количество в инпуте
            if (cartItem) {
                const qtyInput = cartItem.querySelector('input[type="number"]');
                if (qtyInput) {
                    qtyInput.value = quantity;
                }
                
                // Обновляем подытог (цена за все товары)
                const subtotalElem = cartItem.querySelector('.subtotal');
                if (subtotalElem) {
                    subtotalElem.textContent = formatCurrency(data.subtotal);
                }
                
                // Обновляем текст "цена × количество"
                const pricePerItemElem = cartItem.querySelector('.price-per-item');
                if (pricePerItemElem) {
                    const priceMatch = pricePerItemElem.textContent.match(/([\d\s,]+\s*BYN)/);
                    if (priceMatch) {
                        pricePerItemElem.textContent = `${priceMatch[1]} × ${quantity}`;
                    }
                }
                
                // Обновляем onclick атрибуты кнопок +/-
                const buttons = cartItem.querySelectorAll('.item-quantity button');
                if (buttons.length === 2) {
                    const minusBtn = buttons[0];
                    const plusBtn = buttons[1];
                    
                    // Обновляем кнопку минус
                    minusBtn.setAttribute('onclick', `updateCartItem(${id}, ${quantity - 1})`);
                    minusBtn.disabled = quantity <= 1;
                    
                    // Обновляем кнопку плюс
                    plusBtn.setAttribute('onclick', `updateCartItem(${id}, ${quantity + 1})`);
                    plusBtn.disabled = quantity >= 99;
                }
            }
            
            // Обновляем все элементы с общей суммой
            const cartTotals = document.querySelectorAll('.cart-total');
            cartTotals.forEach(el => {
                el.textContent = formatCurrency(data.total);
            });
            
            // Обновляем счётчик корзины
            updateCartCount(data.count);
            
            // Уведомление
            if (typeof showNotification === 'function') {
                showNotification('Количество обновлено', 'success');
            }
        } else {
            // Восстанавливаем предыдущее значение при ошибке
            if (cartItem) {
                const qtyInput = cartItem.querySelector('input[type="number"]');
                if (qtyInput) {
                    qtyInput.value = previousQuantity;
                }
            }
            
            if (typeof showNotification === 'function') {
                showNotification(data.message || 'Ошибка обновления', 'error');
            }
        }
    })
    .catch(error => {
        console.error('Update cart error:', error);
        
        // Восстанавливаем предыдущее значение при ошибке соединения
        if (cartItem) {
            const qtyInput = cartItem.querySelector('input[type="number"]');
            if (qtyInput) {
                qtyInput.value = previousQuantity;
            }
        }
        
        if (typeof showNotification === 'function') {
            showNotification('Ошибка соединения', 'error');
        }
    });
}

// Форматировать валюту в формате "X,XX Br" (как Yii formatter)
function formatCurrency(amount) {
    const formatted = parseFloat(amount).toFixed(2).replace('.', ',');
    return formatted + ' Br';
}

// Обновить количество (+ или -)
function updateQuantity(cartId, delta) {
    const qtyElement = document.querySelector(`[data-cart-id="${cartId}"] .qty-value`);
    if (!qtyElement) return;
    
    let currentQty = parseInt(qtyElement.textContent);
    let newQty = currentQty + delta;
    
    if (newQty < 1) {
        // Если меньше 1, удаляем товар без подтверждения
        removeCartItem(cartId);
        return;
    }
    
    if (newQty > 99) {
        if (typeof showNotification === 'function') {
            showNotification('Максимальное количество: 99', 'warning');
        }
        return;
    }
    
    // Обновляем UI сразу (optimistic update)
    qtyElement.textContent = newQty;
    
    // Отправляем на backend
    updateCartItem(cartId, newQty);
}

// Удалить товар из корзины (без подтверждения)
function removeCartItem(id) {
    const cartItem = document.querySelector(`[data-cart-id="${id}"]`);
    if (!cartItem) return;
    
    // Анимация удаления
    cartItem.style.transition = 'all 0.3s ease-out';
    cartItem.style.opacity = '0';
    cartItem.style.transform = 'translateX(-20px)';
    
    // Отправляем запрос на сервер
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
            // Ждем завершения анимации, затем удаляем элемент
            setTimeout(() => {
                cartItem.remove();
                
                // Обновляем счетчик и суммы
                updateCartCount(data.count);
                
                // Используем глобальную функцию updateCartTotals если она существует
                if (typeof updateCartTotals === 'function') {
                    updateCartTotals(data.total, data.count);
                }
                
                // Если корзина пуста - перезагружаем страницу
                const remainingItems = document.querySelectorAll('.cart-item');
                if (remainingItems.length === 0) {
                    location.reload();
                }
            }, 300);
            
            // Показываем уведомление
            if (typeof showNotification === 'function') {
                showNotification('Товар удален из корзины', 'success');
            }
        } else {
            // Отменяем анимацию при ошибке
            cartItem.style.opacity = '1';
            cartItem.style.transform = 'translateX(0)';
            
            if (typeof showNotification === 'function') {
                showNotification(data.message || 'Ошибка удаления', 'error');
            }
        }
    })
    .catch(error => {
        console.error('Remove cart error:', error);
        
        // Отменяем анимацию при ошибке
        cartItem.style.opacity = '1';
        cartItem.style.transform = 'translateX(0)';
        
        if (typeof showNotification === 'function') {
            showNotification('Ошибка соединения', 'error');
        }
    });
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
