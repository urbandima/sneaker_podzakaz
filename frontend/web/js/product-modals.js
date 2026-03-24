/**
 * Модальные окна для страницы товара
 * Управление модалками "Купить в 1 клик" и "Таблица размеров"
 */

// ============================================================================
// Модалка "Купить в 1 клик"
// ============================================================================

/**
 * Открыть модалку быстрого заказа
 */
function openQuickOrderModal() {
    const modal = document.getElementById('quickOrderModal');
    if (modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        
        // Фокус на первое поле
        const firstInput = modal.querySelector('input[type="text"], input[type="tel"]');
        if (firstInput) {
            setTimeout(() => firstInput.focus(), 100);
        }
    }
}

/**
 * Закрыть модалку быстрого заказа
 */
function closeQuickOrderModal() {
    const modal = document.getElementById('quickOrderModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = '';
        
        // Сбрасываем форму
        const form = modal.querySelector('form');
        if (form) {
            form.reset();
        }
    }
}

/**
 * Отправить быстрый заказ
 * @param {Event} event - событие submit формы
 */
function submitQuickOrder(event) {
    event.preventDefault();
    
    const form = event.target;
    const submitBtn = document.getElementById('quickOrderSubmitBtn');
    const originalText = submitBtn.innerHTML;
    
    // Показываем загрузку
    submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Отправляем...';
    submitBtn.disabled = true;
    
    // Получаем данные формы
    const formData = new FormData(form);
    
    // Получаем product_id из data-атрибута формы
    const productId = form.getAttribute('data-product-id');
    if (productId) {
        formData.append('product_id', productId);
    }
    
    // Добавляем CSRF токен
    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (csrfToken) {
        formData.append('_csrf', csrfToken.getAttribute('content'));
    }
    
    // Отправляем на сервер
    fetch('/catalog/quick-order', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Успешная отправка
            submitBtn.innerHTML = '<i class="bi bi-check-circle"></i> Заказ отправлен!';
            submitBtn.classList.add('success');
            
            // Показываем уведомление
            if (window.NotificationManager) {
                NotificationManager.success('Спасибо! Менеджер свяжется с вами в ближайшее время.');
            } else {
                alert('Спасибо! Менеджер свяжется с вами в ближайшее время.');
            }
            
            // Закрываем модалку через 2 секунды
            setTimeout(() => {
                closeQuickOrderModal();
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
                submitBtn.classList.remove('success');
            }, 2000);
        } else {
            // Ошибка
            throw new Error(data.message || 'Ошибка отправки заказа');
        }
    })
    .catch(error => {
        console.error('Quick order error:', error);
        submitBtn.innerHTML = '<i class="bi bi-x-circle"></i> Ошибка';
        submitBtn.classList.add('error');
        
        if (window.NotificationManager) {
            NotificationManager.error(error.message || 'Ошибка отправки заказа. Попробуйте позже.');
        } else {
            alert(error.message || 'Ошибка отправки заказа. Попробуйте позже.');
        }
        
        setTimeout(() => {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
            submitBtn.classList.remove('error');
        }, 2000);
    });
}

// ============================================================================
// Модалка "Таблица размеров"
// ============================================================================

/**
 * Открыть модалку таблицы размеров
 */
function openSizeTableModal() {
    const modal = document.getElementById('sizeTableModal');
    if (modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
}

/**
 * Закрыть модалку таблицы размеров
 */
function closeSizeTableModal() {
    const modal = document.getElementById('sizeTableModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }
}

/**
 * Выбрать размер из таблицы
 * @param {string} size - выбранный размер
 */
function selectSizeFromTable(size) {
    // Находим кнопку размера на странице
    const sizeButtons = document.querySelectorAll('.size-option');
    let targetButton = null;
    
    sizeButtons.forEach(button => {
        if (button.textContent.trim() === size) {
            targetButton = button;
        }
    });
    
    if (targetButton && !targetButton.classList.contains('disabled')) {
        // Снимаем выделение со всех кнопок
        sizeButtons.forEach(btn => btn.classList.remove('selected'));
        
        // Выделяем выбранную кнопку
        targetButton.classList.add('selected');
        
        // Обновляем скрытое поле с размером
        const sizeInput = document.getElementById('selectedSize');
        if (sizeInput) {
            sizeInput.value = size;
        }
        
        // Обновляем цену если есть функция
        if (typeof updatePriceForSize === 'function') {
            updatePriceForSize(size);
        }
        
        // Закрываем модалку
        closeSizeTableModal();
        
        // Показываем уведомление
        if (window.NotificationManager) {
            NotificationManager.success(`Выбран размер: ${size}`);
        }
    } else {
        // Размер недоступен
        if (window.NotificationManager) {
            NotificationManager.warning('Этот размер недоступен');
        } else {
            alert('Этот размер недоступен');
        }
    }
}

// ============================================================================
// Инициализация обработчиков событий
// ============================================================================

document.addEventListener('DOMContentLoaded', function() {
    // Закрытие модалки быстрого заказа при клике на фон
    const quickOrderModal = document.getElementById('quickOrderModal');
    if (quickOrderModal) {
        quickOrderModal.addEventListener('click', function(e) {
            if (e.target === quickOrderModal) {
                closeQuickOrderModal();
            }
        });
    }
    
    // Закрытие модалки таблицы размеров при клике на фон
    const sizeTableModal = document.getElementById('sizeTableModal');
    if (sizeTableModal) {
        sizeTableModal.addEventListener('click', function(e) {
            if (e.target === sizeTableModal) {
                closeSizeTableModal();
            }
        });
    }
    
    // Закрытие модалок по ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const quickOrderModalElement = document.getElementById('quickOrderModal');
            const sizeTableModalElement = document.getElementById('sizeTableModal');
            
            if (quickOrderModalElement && quickOrderModalElement.style.display === 'flex') {
                closeQuickOrderModal();
            }
            
            if (sizeTableModalElement && sizeTableModalElement.style.display === 'flex') {
                closeSizeTableModal();
            }
        }
    });
});

// ============================================================================
// Экспорт функций в глобальную область видимости
// ============================================================================

// Делаем функции доступными глобально для inline-обработчиков
window.openQuickOrderModal = openQuickOrderModal;
window.closeQuickOrderModal = closeQuickOrderModal;
window.submitQuickOrder = submitQuickOrder;
window.openSizeTableModal = openSizeTableModal;
window.closeSizeTableModal = closeSizeTableModal;
window.selectSizeFromTable = selectSizeFromTable;
