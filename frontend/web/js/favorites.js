/**
 * Favorites functionality
 * Guests: localStorage fallback + toast prompt to log in.
 * Logged-in: server API. On login, merges guest list automatically.
 */

var GUEST_KEY = 'guestWishlist';

function isGuest() {
    return document.body.dataset.isGuest === '1';
}

function getGuestList() {
    try { return JSON.parse(localStorage.getItem(GUEST_KEY) || '[]'); } catch (e) { return []; }
}

function saveGuestList(list) {
    try { localStorage.setItem(GUEST_KEY, JSON.stringify(list)); } catch (e) {}
}

document.addEventListener('DOMContentLoaded', function () {
    initializeFavorites();
    if (!isGuest()) {
        mergeGuestWishlistIfNeeded();
    }
});

function initializeFavorites() {
    updateFavoritesCount();
    initFavoriteButtons();

    favoriteButtons.forEach(function (button) {
        if (button.hasAttribute('onclick')) return;
        button.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var productId = this.dataset.productId;
            if (!productId) return;
            toggleFavorite(this, productId);
        });
    });
}

    if (isGuest()) {
        markGuestFavoriteButtons();
    }
    updateFavoritesCount();
}

function markGuestFavoriteButtons() {
    var list = getGuestList();
    // Reset all first so empty localStorage means all unfavorited (X21)
    document.querySelectorAll('.btn-favorite, .fav-btn').forEach(function (btn) {
        btn.classList.remove('active');
        var icon = btn.querySelector('i');
        if (icon) icon.className = 'bi bi-heart';
    });
    document.querySelectorAll('.btn-favorite, .fav-btn').forEach(function (btn) {
        var id = String(btn.dataset.productId || '');
        if (id && list.indexOf(id) !== -1) {
            btn.classList.add('active');
            var icon = btn.querySelector('i');
            if (icon) icon.className = 'bi bi-heart-fill';
        }
    });
}

function toggleFavorite(button, productId) {
    if (isGuest()) {
        toggleGuestFavorite(button, String(productId));
        return;
    }

    var isActive = button.classList.contains('active');
    var url = isActive ? '/favorite/remove' : '/favorite/add';

    button.disabled = true;
    button.classList.add('loading');

    SH.fetch('/favorite/toggle', {
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
                SH.notify((data && data.message) || 'Произошла ошибка', 'error');
            }
        })
        .catch(function () {
            button.classList.remove('loading');
            button.disabled = false;
            SH.notify('Не удалось обновить избранное', 'error');
        });
}

function toggleGuestFavorite(button, productId) {
    var list = getGuestList();
    var idx = list.indexOf(productId);
    if (idx === -1) {
        list.push(productId);
        saveGuestList(list);
        button.classList.add('active');
        var icon = button.querySelector('i');
        if (icon) icon.className = 'bi bi-heart-fill';
        SH.notify('Добавлено в избранное. <a href="/account/login" style="color:inherit;text-decoration:underline">Войдите чтобы сохранить →</a>', 'info');
    } else {
        list.splice(idx, 1);
        saveGuestList(list);
        button.classList.remove('active');
        var icon2 = button.querySelector('i');
        if (icon2) icon2.className = 'bi bi-heart';
    }
    updateFavoritesCount();
}

function mergeGuestWishlistIfNeeded() {
    var list = getGuestList();
    if (!list.length) return;

    SH.fetch('/favorite/merge-guest', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ ids: list })
    })
        .then(function () {
            localStorage.removeItem(GUEST_KEY);
            updateFavoritesCount();
        })
        .catch(function () {});
}

function updateFavoritesCount() {
    if (isGuest()) {
        var count = getGuestList().length;
        var badge = document.getElementById('favCount');
        if (badge) {
            badge.textContent = count;
            badge.style.display = count > 0 ? 'flex' : 'none';
        }
        return;
    }

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
