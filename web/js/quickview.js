/**
 * Quick View - быстрый просмотр товара в модальном окне
 */

// Открыть Quick View
function openQuickView(productId) {
    // Показываем загрузку
    showLoadingModal();
    
    $.ajax({
        url: '/catalog/quick-view',
        method: 'GET',
        data: { id: productId },
        success: function(response) {
            if (response.success) {
                showQuickViewModal(response.html);
            } else {
                closeLoadingModal();
                showNotification(response.message || 'Ошибка загрузки', 'error');
            }
        },
        error: function() {
            closeLoadingModal();
            showNotification('Ошибка соединения', 'error');
        }
    });
}

// Показать модальное окно загрузки
function showLoadingModal() {
    const modal = $(`
        <div class="quick-view-overlay" id="qvOverlay">
            <div class="quick-view-loading">
                <div class="spinner"></div>
                <p>Загрузка...</p>
            </div>
        </div>
    `);
    
    $('body').append(modal).css('overflow', 'hidden');
    
    setTimeout(() => modal.addClass('active'), 10);
}

// Закрыть модальное окно загрузки
function closeLoadingModal() {
    $('#qvOverlay').removeClass('active');
    setTimeout(() => {
        $('#qvOverlay').remove();
        $('body').css('overflow', '');
    }, 300);
}

// Показать Quick View модальное окно с контентом
function showQuickViewModal(html) {
    closeLoadingModal();
    
    const modal = $(`
        <div class="quick-view-overlay active" id="qvOverlay">
            <div class="quick-view-modal">
                <button class="qv-close" onclick="closeQuickView()">
                    <i class="bi bi-x"></i>
                </button>
                <div class="qv-content">
                    ${html}
                </div>
            </div>
        </div>
    `);
    
    $('body').append(modal).css('overflow', 'hidden');
    
    // Закрытие по клику на overlay
    modal.on('click', function(e) {
        if ($(e.target).hasClass('quick-view-overlay')) {
            closeQuickView();
        }
    });
    
    // Закрытие по ESC
    $(document).on('keydown.quickview', function(e) {
        if (e.key === 'Escape') {
            closeQuickView();
        }
    });
}

// Закрыть Quick View
function closeQuickView() {
    $('#qvOverlay').removeClass('active');
    $(document).off('keydown.quickview');
    
    setTimeout(() => {
        $('#qvOverlay').remove();
        $('body').css('overflow', '');
    }, 300);
}

// Стили Quick View
const quickViewStyles = `
<style>
.quick-view-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.75);
    backdrop-filter: blur(4px);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s;
    padding: 1rem;
}

.quick-view-overlay.active {
    opacity: 1;
}

.quick-view-loading {
    text-align: center;
    color: #fff;
}

.quick-view-loading .spinner {
    width: 50px;
    height: 50px;
    border: 4px solid rgba(255,255,255,0.3);
    border-top: 4px solid #fff;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
    margin: 0 auto 1rem;
}

.quick-view-modal {
    background: #fff;
    border-radius: 16px;
    max-width: 1000px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
    position: relative;
    transform: scale(0.9);
    transition: transform 0.3s;
}

.quick-view-overlay.active .quick-view-modal {
    transform: scale(1);
}

.qv-close {
    position: absolute;
    top: 1rem;
    right: 1rem;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #f3f4f6;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    z-index: 10;
    transition: all 0.2s;
}

.qv-close:hover {
    background: #000;
    color: #fff;
    transform: rotate(90deg);
}

.qv-content {
    padding: 2rem;
}

@media (max-width: 768px) {
    .quick-view-modal {
        max-height: 95vh;
        border-radius: 16px 16px 0 0;
        margin-top: auto;
    }
    
    .qv-content {
        padding: 1rem;
    }
}
</style>
`;

$(document).ready(function() {
    $('head').append(quickViewStyles);
});
