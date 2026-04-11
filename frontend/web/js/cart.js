/**
 * Функционал корзины - Vanilla JS (без jQuery)
 * Использует SH.* утилиты из utils.js
 */

// Cart Drawer Functions
function openCartDrawer() {
    SH.openModal(document.getElementById('cartDrawerOverlay'), 'open');
    SH.openModal(document.getElementById('cartDrawer'), 'open');
    loadCartDrawerItems();
}

function closeCartDrawer() {
    SH.closeModal(document.getElementById('cartDrawerOverlay'), 'open');
    SH.closeModal(document.getElementById('cartDrawer'), 'open');
}

function loadCartDrawerItems() {
    const drawerItems = document.getElementById('cartDrawerItems');
    drawerItems.innerHTML = '<div class="cart-loading"><i class="bi bi-arrow-repeat spinner"></i> Загрузка...</div>';

    SH.fetch('/cart/drawer-items')
    .then(function (data) {
        if (data.success) {
            drawerItems.innerHTML = data.html;
            updateCartCount(data.count);
            document.querySelectorAll('.cart-total').forEach(function (el) {
                el.textContent = SH.formatCurrency(data.total);
            });
        }
    })
    .catch(function (error) {
        drawerItems.innerHTML = '<div class="cart-error">Ошибка загрузки корзины</div>';
    });
}

// Переопределяем добавление в корзину для открытия Drawer
function addToCart(productId, quantity = 1, size = null, color = null) {
    const formData = new FormData();
    formData.append('product_id', productId);
    formData.append('quantity', quantity);
    if (size) formData.append('size', size);
    if (color) formData.append('color', color);

    SH.fetch('/cart/add', { method: 'POST', body: formData })
        .then(function (data) {
            if (data.success) {
                updateCartCount(data.count);
                openCartDrawer();
            } else {
                SH.notify(data.message || 'Ошибка добавления', 'error');
            }
        })
        .catch(function (error) {
            SH.notify('Ошибка соединения', 'error');
        });
}

// Обновить количество товара (с поддержкой Drawer)
function updateCartItem(id, quantity) {
    quantity = parseInt(quantity);

    // Валидация
    if (quantity < 1 || quantity > 99) {
        SH.notify('Количество должно быть от 1 до 99', 'warning');
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

    SH.fetch('/cart/update', { method: 'POST', body: formData })
        .then(function (data) {

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

                SH.notify('Количество обновлено', 'success');
            } else {
                if (cartItem) {
                    const qtyInput = cartItem.querySelector('input[type="number"]');
                    if (qtyInput) qtyInput.value = previousQuantity;
                }
                SH.notify(data.message || 'Ошибка обновления', 'error');
            }
        })
        .catch(function (error) {
            if (cartItem) {
                const qtyInput = cartItem.querySelector('input[type="number"]');
                if (qtyInput) qtyInput.value = previousQuantity;
            }
            SH.notify('Ошибка соединения', 'error');
        });
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
        SH.notify('Максимальное количество: 99', 'warning');
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

    SH.fetch('/cart/remove/' + id, { method: 'POST' })
        .then(function (data) {
            if (data.success) {
                setTimeout(function () {
                    cartItem.remove();
                    updateCartCount(data.count);
                    if (typeof updateCartTotals === 'function') {
                        updateCartTotals(data.total, data.count);
                    }
                    if (document.querySelectorAll('.cart-item').length === 0) {
                        location.reload();
                    }
                }, 300);
                SH.notify('Товар удален из корзины', 'success');
            } else {
                cartItem.style.opacity = '1';
                cartItem.style.transform = 'translateX(0)';
                SH.notify(data.message || 'Ошибка удаления', 'error');
            }
        })
        .catch(function () {
            cartItem.style.opacity = '1';
            cartItem.style.transform = 'translateX(0)';
            SH.notify('Ошибка соединения', 'error');
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
document.addEventListener('DOMContentLoaded', function () {
    SH.fetch('/cart/count')
        .then(function (data) { if (data.count) updateCartCount(data.count); })
        .catch(function () { /* production: silent */ });

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
