<?php
/** 
 * Модальное окно для заявки на товар
 */
use yii\helpers\Html;
?>

<div class="modal" id="inquiryModal">
    <div class="modal-overlay" onclick="closeInquiryModal()"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Заказать товар</h3>
            <button class="modal-close" onclick="closeInquiryModal()">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        
        <div class="modal-body">
            <form id="inquiryForm" onsubmit="submitInquiry(event)">
                <input type="hidden" name="product_id" id="inquiry_product_id">
                
                <div class="form-group">
                    <label class="form-label">Товар</label>
                    <input type="text" class="form-control" id="inquiry_product_name" readonly>
                </div>

                <div class="form-group">
                    <label class="form-label required">Ваше имя</label>
                    <input type="text" name="name" class="form-control" placeholder="Иван Иванов" required>
                </div>

                <div class="form-group">
                    <label class="form-label required">Телефон</label>
                    <input type="tel" name="phone" class="form-control" placeholder="+375 29 123-45-67" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" placeholder="example@mail.com">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Размер</label>
                        <select name="size" class="form-control" id="inquiry_size">
                            <option value="">Выберите размер</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Цвет</label>
                        <select name="color" class="form-control" id="inquiry_color">
                            <option value="">Выберите цвет</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Комментарий</label>
                    <textarea name="message" class="form-control" rows="3" placeholder="Дополнительная информация (необязательно)"></textarea>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeInquiryModal()">
                        Отмена
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg"></i>
                        Отправить заявку
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 9999;
    animation: fadeIn 0.3s ease-out;
}

.modal.active {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
}

.modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
}

.modal-content {
    position: relative;
    background: #ffffff;
    border-radius: 12px;
    width: 100%;
    max-width: 500px;
    max-height: 90vh;
    overflow-y: auto;
    animation: slideUp 0.3s ease-out;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem;
    border-bottom: 1px solid #e5e7eb;
}

.modal-title {
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0;
}

.modal-close {
    background: none;
    border: none;
    font-size: 1.25rem;
    cursor: pointer;
    color: #666666;
    transition: color 0.2s;
    padding: 0.5rem;
}

.modal-close:hover {
    color: #000000;
}

.modal-body {
    padding: 1.5rem;
}

.form-group {
    margin-bottom: 1.25rem;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.form-label {
    display: block;
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: #000000;
    font-size: 0.9375rem;
}

.form-label.required::after {
    content: ' *';
    color: #ef4444;
}

.form-control {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    font-size: 1rem;
    transition: all 0.2s;
}

.form-control:focus {
    outline: none;
    border-color: #000000;
    box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.1);
}

.form-control:read-only {
    background: #f9fafb;
    color: #666666;
}

textarea.form-control {
    resize: vertical;
    min-height: 80px;
}

.form-actions {
    display: flex;
    gap: 1rem;
    margin-top: 2rem;
}

.btn {
    flex: 1;
    padding: 0.875rem 1.5rem;
    border: none;
    border-radius: 6px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.btn-secondary {
    background: #f9fafb;
    color: #666666;
    border: 1px solid #e5e7eb;
}

.btn-secondary:hover {
    background: #e5e7eb;
}

.btn-primary {
    background: #000000;
    color: #ffffff;
}

.btn-primary:hover {
    background: #333333;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@media (max-width: 576px) {
    .form-row {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
/**
 * Открыть модальное окно заявки
 */
window.openInquiryModal = function(productId, productName, sizes = [], colors = []) {
    const modal = document.getElementById('inquiryModal');
    const form = document.getElementById('inquiryForm');
    
    // Заполнение данных товара
    document.getElementById('inquiry_product_id').value = productId;
    document.getElementById('inquiry_product_name').value = productName;
    
    // Заполнение размеров
    const sizeSelect = document.getElementById('inquiry_size');
    sizeSelect.innerHTML = '<option value="">Выберите размер</option>';
    if (sizes && sizes.length > 0) {
        sizes.forEach(size => {
            const option = document.createElement('option');
            option.value = size;
            option.textContent = size;
            sizeSelect.appendChild(option);
        });
    }
    
    // Заполнение цветов
    const colorSelect = document.getElementById('inquiry_color');
    colorSelect.innerHTML = '<option value="">Выберите цвет</option>';
    if (colors && colors.length > 0) {
        colors.forEach(color => {
            const option = document.createElement('option');
            option.value = color;
            option.textContent = color;
            colorSelect.appendChild(option);
        });
    }
    
    // Сброс формы
    form.reset();
    document.getElementById('inquiry_product_id').value = productId;
    document.getElementById('inquiry_product_name').value = productName;
    
    // Показать модальное окно
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
};

/**
 * Закрыть модальное окно
 */
window.closeInquiryModal = function() {
    const modal = document.getElementById('inquiryModal');
    modal.classList.remove('active');
    document.body.style.overflow = 'auto';
};

/**
 * Отправка заявки
 */
window.submitInquiry = function(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    
    // Показать индикатор загрузки
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Отправка...';
    
    // AJAX запрос
    fetch('/catalog/create-inquiry', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Успешная отправка
            showSuccessMessage(data.message);
            closeInquiryModal();
            form.reset();
        } else {
            // Ошибка
            showErrorMessage(data.message || 'Ошибка при отправке заявки');
        }
    })
    .catch(error => {
        console.error('Ошибка:', error);
        showErrorMessage('Произошла ошибка при отправке заявки');
    })
    .finally(() => {
        // Восстановить кнопку
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
};

/**
 * Показать сообщение об успехе
 */
function showSuccessMessage(message) {
    const notification = document.createElement('div');
    notification.className = 'notification success';
    notification.innerHTML = `
        <i class="bi bi-check-circle"></i>
        <span>${message}</span>
    `;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: #10b981;
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 10000;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        animation: slideIn 0.3s ease-out;
    `;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease-out';
        setTimeout(() => notification.remove(), 300);
    }, 4000);
}

/**
 * Показать сообщение об ошибке
 */
function showErrorMessage(message) {
    const notification = document.createElement('div');
    notification.className = 'notification error';
    notification.innerHTML = `
        <i class="bi bi-exclamation-circle"></i>
        <span>${message}</span>
    `;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: #ef4444;
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 10000;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    `;
    document.body.appendChild(notification);
    
    setTimeout(() => notification.remove(), 4000);
}
</script>
