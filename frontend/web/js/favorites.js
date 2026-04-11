/**
 * Favorites functionality
 * Handles adding/removing items from favorites.
 * Uses SH.* utilities from utils.js — no local getCsrfToken / showNotification.
 */

document.addEventListener('DOMContentLoaded', function () {
    initializeFavorites();
});

function initializeFavorites() {
    var favoriteButtons = document.querySelectorAll('.btn-favorite, .fav-btn');

    favoriteButtons.forEach(function (button) {
        button.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();

            var productId = this.dataset.productId;
            if (!productId) return;

            toggleFavorite(this, productId);
        });
    });

    updateFavoritesCount();
}

function toggleFavorite(button, productId) {
    var isActive = button.classList.contains('active');
    var url = isActive ? '/catalog/remove-favorite' : '/catalog/add-favorite';

    button.disabled = true;
    button.classList.add('loading');

    SH.fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'product_id=' + productId
    })
        .then(function (data) {
            if (data.success) {
                button.classList.toggle('active');
                button.classList.remove('loading');
                button.disabled = false;

                var icon = button.querySelector('i');
                if (icon) {
                    icon.className = isActive ? 'bi bi-heart' : 'bi bi-heart-fill';
                }

                updateFavoritesCount();
                SH.notify(data.message, 'success');
            } else {
                throw new Error(data.message || 'Произошла ошибка');
            }
        })
        .catch(function (error) {
            console.error('Favorite toggle error:', error);
            button.classList.remove('loading');
            button.disabled = false;
            SH.notify('Не удалось обновить избранное', 'error');
        });
}

function updateFavoritesCount() {
    SH.fetch('/catalog/favorites-count')
        .then(function (data) {
            var badge = document.getElementById('favCount');
            if (badge && data.count !== undefined) {
                badge.textContent = data.count;
                badge.style.display = data.count > 0 ? 'flex' : 'none';
            }
        })
        .catch(function () {
            var activeCount = document.querySelectorAll('.btn-favorite.active, .fav-btn.active').length;
            var badge = document.getElementById('favCount');
            if (badge) {
                badge.textContent = activeCount;
                badge.style.display = activeCount > 0 ? 'flex' : 'none';
            }
        });
}

// Export for global access
window.toggleFavorite = toggleFavorite;
window.updateFavoritesCount = updateFavoritesCount;
