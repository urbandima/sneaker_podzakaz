/**
 * Функционал избранного
 */

// Toggle избранное (добавить/удалить)
function toggleFavorite(productId, button) {
    $.ajax({
        url: '/favorite/toggle',
        method: 'POST',
        data: {
            product_id: productId,
            _csrf: yii.getCsrfToken()
        },
        success: function(response) {
            if (response.success) {
                const $button = $(button);
                
                if (response.action === 'added') {
                    // Добавлено - активируем кнопку
                    $button.addClass('active');
                    $button.find('i').removeClass('bi-heart').addClass('bi-heart-fill');
                    showNotification('✓ Добавлено в избранное', 'success');
                } else {
                    // Удалено - деактивируем
                    $button.removeClass('active');
                    $button.find('i').removeClass('bi-heart-fill').addClass('bi-heart');
                    showNotification('Удалено из избранного', 'info');
                }
                
                // Обновляем счетчик
                updateFavCount(response.count);
            }
        },
        error: function() {
            showNotification('Ошибка соединения', 'error');
        }
    });
}

// Обновить счетчик избранного
function updateFavCount(count) {
    $('#favCount').text(count);
    if (count > 0) {
        $('#favCount').show();
    } else {
        $('#favCount').hide();
    }
}

// Загрузить текущее количество избранных при загрузке страницы
$(document).ready(function() {
    $.get('/favorite/count', function(response) {
        if (response.count) {
            updateFavCount(response.count);
        }
    });
});
