/**
 * Функционал корзины
 */

// Добавить товар в корзину
function addToCart(productId, quantity = 1, size = null, color = null) {
    $.ajax({
        url: '/cart/add',
        method: 'POST',
        data: {
            product_id: productId,
            quantity: quantity,
            size: size,
            color: color,
            _csrf: yii.getCsrfToken()
        },
        success: function(response) {
            if (response.success) {
                // Обновляем счетчик
                updateCartCount(response.count);
                
                // Анимация иконки корзины
                animateCartIcon();
                
                // Показываем уведомление
                showNotification('✓ Товар добавлен в корзину', 'success');
            } else {
                showNotification(response.message || 'Ошибка добавления', 'error');
            }
        },
        error: function() {
            showNotification('Ошибка соединения', 'error');
        }
    });
}

// Обновить количество товара
function updateCartItem(id, quantity) {
    $.ajax({
        url: '/cart/update',
        method: 'POST',
        data: {
            id: id,
            quantity: quantity,
            _csrf: yii.getCsrfToken()
        },
        success: function(response) {
            if (response.success) {
                // Обновляем подытог
                $(`[data-cart-id="${id}"] .cart-item-price`).text(response.subtotal + ' BYN');
                // Обновляем общую сумму
                $('.cart-total').text(response.total + ' BYN');
                // Обновляем счётчик корзины
                updateCartCount(response.count);
            }
        }
    });
}

// Обновить количество (+ или -)
function updateQuantity(cartId, delta) {
    const qtyElement = $(`[data-cart-id="${cartId}"] .qty-value`);
    let currentQty = parseInt(qtyElement.text());
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
    qtyElement.text(newQty);
    
    // Отправляем на backend
    updateCartItem(cartId, newQty);
}

// Удалить товар из корзины
function removeCartItem(id) {
    if (!confirm('Удалить товар из корзины?')) {
        return;
    }
    
    $.ajax({
        url: '/cart/remove/' + id,
        method: 'POST',
        data: {
            _csrf: yii.getCsrfToken()
        },
        success: function(response) {
            if (response.success) {
                // Удаляем элемент
                $(`[data-cart-id="${id}"]`).fadeOut(300, function() {
                    $(this).remove();
                    
                    // Проверяем - есть ли еще товары
                    if ($('.cart-item').length === 0) {
                        location.reload();
                    }
                });
                
                // Обновляем счетчик и сумму
                updateCartCount(response.count);
                $('.cart-total').text(response.total + ' BYN');
                
                showNotification('Товар удален', 'success');
            }
        }
    });
}

// Обновить счетчик корзины в header
function updateCartCount(count) {
    $('#cartCount').text(count);
    if (count > 0) {
        $('#cartCount').show();
    } else {
        $('#cartCount').hide();
    }
}

// Анимация иконки корзины при добавлении товара
function animateCartIcon() {
    const cartIcon = $('#cartCount').parent(); // parent = .header-btn с иконкой
    const cartBadge = $('#cartCount');
    
    if (cartIcon.length) {
        // Shake animation
        cartIcon.addClass('cart-shake');
        
        // Pulse badge
        cartBadge.addClass('cart-pulse');
        
        // Remove classes after animation
        setTimeout(() => {
            cartIcon.removeClass('cart-shake');
            cartBadge.removeClass('cart-pulse');
        }, 600);
    }
}

// Загрузить текущее количество при загрузке страницы
$(document).ready(function() {
    $.get('/cart/count', function(response) {
        if (response.count) {
            updateCartCount(response.count);
        }
    });
    
    // Добавляем CSS для анимаций если еще нет
    if (!$('#cart-animations-css').length) {
        $('<style id="cart-animations-css">')
            .text(`
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
            `)
            .appendTo('head');
    }
});
