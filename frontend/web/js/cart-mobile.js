/**
 * Мобильные улучшения для корзины
 * Touch interactions, keyboard handling, orientation changes
 */

(function() {
    'use strict';
    
    // Проверка мобильного устройства
    const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent);
    
    if (!isMobile) return; // Запускаем только на мобильных
    
    // ======================
    // Детекция клавиатуры
    // ======================
    let initialHeight = window.innerHeight;
    
    function handleKeyboard() {
        const currentHeight = window.innerHeight;
        const heightDiff = initialHeight - currentHeight;
        
        // Клавиатура открыта если высота уменьшилась более чем на 150px
        if (heightDiff > 150) {
            document.body.classList.add('keyboard-open');
        } else {
            document.body.classList.remove('keyboard-open');
        }
    }
    
    window.addEventListener('resize', handleKeyboard);
    
    // iOS specific - visualViewport API
    if (isIOS && window.visualViewport) {
        window.visualViewport.addEventListener('resize', handleKeyboard);
    }
    
    // ======================
    // Обработка ориентации
    // ======================
    function handleOrientationChange() {
        initialHeight = window.innerHeight;
        
        // Scroll to top при смене ориентации для корректного отображения
        if (document.querySelector('.cart-summary')) {
            setTimeout(() => {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }, 100);
        }
    }
    
    window.addEventListener('orientationchange', handleOrientationChange);
    
    // ======================
    // Улучшенное удаление с анимацией
    // ======================
    const originalRemoveCartItem = window.removeCartItem;
    
    window.removeCartItem = function(id) {
        const cartItem = document.querySelector(`[data-cart-id="${id}"]`);
        if (!cartItem) return;
        
        // Добавляем класс для анимации
        cartItem.classList.add('removing');
        
        // Вызываем оригинальную функцию через небольшую задержку
        setTimeout(() => {
            if (typeof originalRemoveCartItem === 'function') {
                originalRemoveCartItem(id);
            }
        }, 300);
    };
    
    // ======================
    // Swipe для удаления (опционально)
    // ======================
    let touchStartX = 0;
    let touchCurrentX = 0;
    let swipingItem = null;
    
    function handleTouchStart(e) {
        const cartItem = e.target.closest('.cart-item');
        if (!cartItem) return;
        
        touchStartX = e.touches[0].clientX;
        swipingItem = cartItem;
    }
    
    function handleTouchMove(e) {
        if (!swipingItem) return;
        
        touchCurrentX = e.touches[0].clientX;
        const diff = touchStartX - touchCurrentX;
        
        // Свайп влево для удаления
        if (diff > 30) {
            swipingItem.classList.add('swiping');
        } else {
            swipingItem.classList.remove('swiping');
        }
    }
    
    function handleTouchEnd(e) {
        if (!swipingItem) return;
        
        const diff = touchStartX - touchCurrentX;
        
        // Если свайп более 100px - показываем кнопку удаления
        if (diff > 100) {
            const removeBtn = swipingItem.querySelector('.item-remove');
            if (removeBtn) {
                // Анимация внимания к кнопке
                removeBtn.style.animation = 'pulse 0.5s ease';
                setTimeout(() => {
                    removeBtn.style.animation = '';
                }, 500);
            }
        }
        
        swipingItem.classList.remove('swiping');
        swipingItem = null;
        touchStartX = 0;
        touchCurrentX = 0;
    }
    
    // Подключаем swipe handlers
    document.addEventListener('touchstart', handleTouchStart, { passive: true });
    document.addEventListener('touchmove', handleTouchMove, { passive: true });
    document.addEventListener('touchend', handleTouchEnd, { passive: true });
    
    // ======================
    // Haptic feedback (iOS)
    // ======================
    function triggerHaptic(style = 'medium') {
        if (window.navigator && window.navigator.vibrate) {
            // Android vibration
            switch(style) {
                case 'light':
                    window.navigator.vibrate(10);
                    break;
                case 'medium':
                    window.navigator.vibrate(20);
                    break;
                case 'heavy':
                    window.navigator.vibrate(30);
                    break;
            }
        }
    }
    
    // Добавляем haptic к кнопкам
    document.addEventListener('click', function(e) {
        const button = e.target.closest('button, .btn-checkout, .btn-continue, .delivery-option');
        if (button) {
            triggerHaptic('light');
        }
        
        const removeBtn = e.target.closest('.item-remove');
        if (removeBtn) {
            triggerHaptic('medium');
        }
    });
    
    // ======================
    // Плавный скролл к элементам при фокусе
    // ======================
    function handleFocus(e) {
        if (e.target.matches('input, textarea, select')) {
            setTimeout(() => {
                e.target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
            }, 300); // Даем время клавиатуре раскрыться
        }
    }
    
    document.addEventListener('focus', handleFocus, true);
    
    // ======================
    // Предотвращение множественных одновременных запросов
    // ======================
    let pendingRequests = new Set();
    
    const originalUpdateCartItem = window.updateCartItem;
    window.updateCartItem = function(id, quantity) {
        const requestKey = `${id}-${quantity}`;
        
        // Если точно такой же запрос уже выполняется - игнорируем
        if (pendingRequests.has(requestKey)) {
            return;
        }
        
        // Добавляем в список активных запросов
        pendingRequests.add(requestKey);
        
        // Вызываем оригинальную функцию
        if (typeof originalUpdateCartItem === 'function') {
            originalUpdateCartItem(id, quantity);
        }
        
        // Удаляем из списка через 1 секунду (время на выполнение запроса)
        setTimeout(() => {
            pendingRequests.delete(requestKey);
        }, 1000);
    };
    
    // ======================
    // Оптимизация модального окна
    // ======================
    const originalOpenCheckoutModal = window.openCheckoutModal;
    window.openCheckoutModal = function() {
        if (typeof originalOpenCheckoutModal === 'function') {
            originalOpenCheckoutModal();
        }
        
        // Блокируем скролл страницы под модалкой
        document.body.style.position = 'fixed';
        document.body.style.top = `-${window.scrollY}px`;
        document.body.style.width = '100%';
        
        // Фокус на первое поле
        setTimeout(() => {
            const firstInput = document.querySelector('#checkoutModal input[name="name"]');
            if (firstInput && window.innerWidth <= 768) {
                firstInput.focus();
            }
        }, 300);
    };
    
    const originalCloseCheckoutModal = window.closeCheckoutModal;
    window.closeCheckoutModal = function() {
        // Восстанавливаем скролл
        const scrollY = document.body.style.top;
        document.body.style.position = '';
        document.body.style.top = '';
        document.body.style.width = '';
        window.scrollTo(0, parseInt(scrollY || '0') * -1);
        
        if (typeof originalCloseCheckoutModal === 'function') {
            originalCloseCheckoutModal();
        }
    };
    
    // ======================
    // Автоформат номера телефона
    // ======================
    function formatPhoneNumber(input) {
        let value = input.value.replace(/\D/g, '');
        
        // Беларусь +375
        if (value.startsWith('375') || value.startsWith('80')) {
            if (value.startsWith('80')) {
                value = '375' + value.slice(1);
            }
            
            let formatted = '+375';
            if (value.length > 3) formatted += ' (' + value.slice(3, 5);
            if (value.length > 5) formatted += ')' + value.slice(5, 8);
            if (value.length > 8) formatted += '-' + value.slice(8, 10);
            if (value.length > 10) formatted += '-' + value.slice(10, 12);
            
            input.value = formatted;
        }
        // Россия +7
        else if (value.startsWith('7') || value.startsWith('8')) {
            if (value.startsWith('8')) {
                value = '7' + value.slice(1);
            }
            
            let formatted = '+7';
            if (value.length > 1) formatted += ' (' + value.slice(1, 4);
            if (value.length > 4) formatted += ')' + value.slice(4, 7);
            if (value.length > 7) formatted += '-' + value.slice(7, 9);
            if (value.length > 9) formatted += '-' + value.slice(9, 11);
            
            input.value = formatted;
        }
    }
    
    // Применяем автоформат
    document.addEventListener('input', function(e) {
        if (e.target.matches('input[type="tel"]')) {
            formatPhoneNumber(e.target);
        }
    });
    
    // ======================
    // Pull-to-refresh блокировка
    // ======================
    let startY = 0;
    
    document.addEventListener('touchstart', function(e) {
        startY = e.touches[0].pageY;
    }, { passive: true });
    
    document.addEventListener('touchmove', function(e) {
        const y = e.touches[0].pageY;
        
        // Блокируем pull-to-refresh если на верху страницы и тянем вниз
        if (window.scrollY === 0 && y > startY) {
            e.preventDefault();
        }
    }, { passive: false });
    
    // ======================
    // Показываем состояние загрузки
    // ======================
    const originalSubmitOrder = window.submitOrder;
    window.submitOrder = function(e) {
        const btn = e.target.querySelector('.btn-submit-order');
        if (btn) {
            btn.classList.add('loading');
            triggerHaptic('medium');
        }
        
        if (typeof originalSubmitOrder === 'function') {
            originalSubmitOrder(e);
        }
    };
    
    // ======================
    // Инициализация завершена
    // ======================
    
    // Добавляем индикатор для CSS
    document.documentElement.classList.add('mobile-enhanced');
    
})();
