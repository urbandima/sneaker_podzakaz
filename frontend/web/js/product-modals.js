/**
 * Модальные окна для страницы товара
 * Управление модалками "Купить в 1 клик" и "Таблица размеров"
 * Uses SH.* utilities from utils.js
 */

// ============================================================================
// Модалка "Купить в 1 клик"
// ============================================================================

function openQuickOrderModal() {
    var modal = document.getElementById('quickOrderModal');
    if (!modal) return;
    modal.style.display = 'flex';
    SH.lockScroll();

    var firstInput = modal.querySelector('input[type="text"], input[type="tel"]');
    if (firstInput) {
        setTimeout(function () { firstInput.focus(); }, 100);
    }
}

function closeQuickOrderModal() {
    var modal = document.getElementById('quickOrderModal');
    if (!modal) return;
    modal.style.display = 'none';
    SH.unlockScroll();

    var form = modal.querySelector('form');
    if (form) form.reset();
}

function submitQuickOrder(event) {
    event.preventDefault();

    var form = event.target;
    var submitBtn = document.getElementById('quickOrderSubmitBtn');
    var originalText = submitBtn.innerHTML;

    submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Отправляем...';
    submitBtn.disabled = true;

    var formData = new FormData(form);

    var productId = form.getAttribute('data-product-id');
    if (productId) formData.append('product_id', productId);
    formData.append('_csrf', SH.getCsrfToken());

    SH.fetch('/catalog/quick-order', { method: 'POST', body: formData })
        .then(function (data) {
            if (data.success) {
                submitBtn.innerHTML = '<i class="bi bi-check-circle"></i> Заказ отправлен!';
                submitBtn.classList.add('success');
                SH.notify('Спасибо! Менеджер свяжется с вами в ближайшее время.', 'success');

                setTimeout(function () {
                    closeQuickOrderModal();
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                    submitBtn.classList.remove('success');
                }, 2000);
            } else {
                throw new Error(data.message || 'Ошибка отправки заказа');
            }
        })
        .catch(function (error) {
            console.error('Quick order error:', error);
            submitBtn.innerHTML = '<i class="bi bi-x-circle"></i> Ошибка';
            submitBtn.classList.add('error');
            SH.notify(error.message || 'Ошибка отправки заказа. Попробуйте позже.', 'error');

            setTimeout(function () {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
                submitBtn.classList.remove('error');
            }, 2000);
        });
}

// ============================================================================
// Модалка "Таблица размеров"
// ============================================================================

function openSizeTableModal() {
    var modal = document.getElementById('sizeTableModal');
    if (!modal) return;
    modal.style.display = 'flex';
    SH.lockScroll();
}

function closeSizeTableModal() {
    var modal = document.getElementById('sizeTableModal');
    if (!modal) return;
    modal.style.display = 'none';
    SH.unlockScroll();
}

function selectSizeFromTable(size) {
    var sizeButtons = document.querySelectorAll('.size-option');
    var targetButton = null;

    sizeButtons.forEach(function (button) {
        if (button.textContent.trim() === size) targetButton = button;
    });

    if (targetButton && !targetButton.classList.contains('disabled')) {
        sizeButtons.forEach(function (btn) { btn.classList.remove('selected'); });
        targetButton.classList.add('selected');

        var sizeInput = document.getElementById('selectedSize');
        if (sizeInput) sizeInput.value = size;

        if (typeof updatePriceForSize === 'function') updatePriceForSize(size);

        closeSizeTableModal();
        SH.notify('Выбран размер: ' + size, 'success');
    } else {
        SH.notify('Этот размер недоступен', 'warning');
    }
}

// ============================================================================
// Инициализация
// ============================================================================

document.addEventListener('DOMContentLoaded', function () {
    var quickOrderModal = document.getElementById('quickOrderModal');
    if (quickOrderModal) {
        quickOrderModal.addEventListener('click', function (e) {
            if (e.target === quickOrderModal) closeQuickOrderModal();
        });
    }

    var sizeTableModal = document.getElementById('sizeTableModal');
    if (sizeTableModal) {
        sizeTableModal.addEventListener('click', function (e) {
            if (e.target === sizeTableModal) closeSizeTableModal();
        });
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            if (quickOrderModal && quickOrderModal.style.display === 'flex') closeQuickOrderModal();
            if (sizeTableModal && sizeTableModal.style.display === 'flex') closeSizeTableModal();
        }
    });
});

// Export
window.openQuickOrderModal = openQuickOrderModal;
window.closeQuickOrderModal = closeQuickOrderModal;
window.submitQuickOrder = submitQuickOrder;
window.openSizeTableModal = openSizeTableModal;
window.closeSizeTableModal = closeSizeTableModal;
window.selectSizeFromTable = selectSizeFromTable;
