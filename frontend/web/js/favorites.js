/**
 * Favorites functionality
 * Uses SH.* utilities from utils.js — no local getCsrfToken / showNotification.
 */

document.addEventListener('DOMContentLoaded', function () {
    initializeFavorites();
});

function initializeFavorites() {
    updateFavoritesCount();
    initFavoriteButtons();

    document.querySelectorAll('.btn-favorite, .fav-btn').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var productId = this.dataset.productId;
            if (productId) toggleFavorite(this, productId);
        });
    });
}

function initFavoriteButtons() {
    SH.fetch('/favorite/ids')
        .then(function (data) {
            if (!data || !Array.isArray(data.ids)) return;
            data.ids.forEach(function (id) {
                document.querySelectorAll('.btn-favorite[data-product-id="' + id + '"], .fav-btn[data-product-id="' + id + '"]').forEach(function (btn) {
                    btn.classList.add('active');
                    var icon = btn.querySelector('i');
                    if (icon) icon.className = 'bi bi-heart-fill';
                });
            });
        })
        .catch(function () {});
}

function toggleFavorite(button, productId) {
    var isActive = button.classList.contains('active');

    button.disabled = true;
    button.classList.add('loading');

    SH.fetch('/favorite/toggle', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'product_id=' + productId
    })
        .then(function (data) {
            button.classList.remove('loading');
            button.disabled = false;

            if (data && data.success) {
                var added = data.action === 'added';
                button.classList.toggle('active', added);
                var icon = button.querySelector('i');
                if (icon) icon.className = added ? 'bi bi-heart-fill' : 'bi bi-heart';
                updateFavoritesCount();
                SH.notify(data.message, 'success');
            } else {
                SH.notify((data && data.message) || 'Произошла ошибка', 'error');
            }
        })
        .catch(function () {
            button.classList.remove('loading');
            button.disabled = false;
            SH.notify('Не удалось обновить избранное', 'error');
        });
}

function updateFavoritesCount() {
    SH.fetch('/favorite/count')
        .then(function (data) {
            var badge = document.getElementById('favCount');
            if (badge && data.count !== undefined) {
                badge.textContent = data.count;
                badge.style.display = data.count > 0 ? 'flex' : 'none';
            }
        })
        .catch(function () {});
}

window.toggleFavorite = toggleFavorite;
window.updateFavoritesCount = updateFavoritesCount;
