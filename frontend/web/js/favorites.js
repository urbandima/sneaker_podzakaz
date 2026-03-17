/**
 * Favorites functionality
 * Handles adding/removing items from favorites
 */

document.addEventListener('DOMContentLoaded', function() {
    initializeFavorites();
});

function initializeFavorites() {
    // Handle favorite buttons
    const favoriteButtons = document.querySelectorAll('.btn-favorite, .fav-btn');
    
    favoriteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const productId = this.dataset.productId;
            if (!productId) return;
            
            toggleFavorite(this, productId);
        });
    });
    
    // Update favorites count on page load
    updateFavoritesCount();
}

function toggleFavorite(button, productId) {
    const isActive = button.classList.contains('active');
    const url = isActive ? '/catalog/remove-favorite' : '/catalog/add-favorite';
    
    // Show loading state
    button.disabled = true;
    button.classList.add('loading');
    
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-Token': getCsrfToken()
        },
        body: `productId=${productId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update button state
            button.classList.toggle('active');
            button.classList.remove('loading');
            button.disabled = false;
            
            // Update icon
            const icon = button.querySelector('i');
            if (icon) {
                icon.className = isActive ? 'bi bi-heart' : 'bi bi-heart-fill';
            }
            
            // Update count
            updateFavoritesCount();
            
            // Show notification
            showNotification(data.message, 'success');
        } else {
            throw new Error(data.message || 'Произошла ошибка');
        }
    })
    .catch(error => {
        console.error('Favorite toggle error:', error);
        button.classList.remove('loading');
        button.disabled = false;
        showNotification('Не удалось обновить избранное', 'error');
    });
}

function updateFavoritesCount() {
    fetch('/catalog/favorites-count', {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        return response.json();
    })
    .then(data => {
        const badge = document.getElementById('favCount');
        if (badge && data.count !== undefined) {
            badge.textContent = data.count;
            badge.style.display = data.count > 0 ? 'flex' : 'none';
        }
    })
    .catch(error => {
        console.warn('Failed to update favorites count:', error);
        // Fallback: count visually
        const activeCount = document.querySelectorAll('.btn-favorite.active, .fav-btn.active').length;
        const badge = document.getElementById('favCount');
        if (badge) {
            badge.textContent = activeCount;
            badge.style.display = activeCount > 0 ? 'flex' : 'none';
        }
    });
}

function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
}

function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    
    // Add to page
    document.body.appendChild(notification);
    
    // Show notification
    setTimeout(() => notification.classList.add('show'), 100);
    
    // Hide after 3 seconds
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Export for global access
window.toggleFavorite = toggleFavorite;
window.updateFavoritesCount = updateFavoritesCount;
