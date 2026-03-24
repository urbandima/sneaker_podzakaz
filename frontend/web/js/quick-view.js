/**
 * Quick View Modal - Быстрый просмотр товара
 */

// Открыть Quick View
function openQuickView(productId) {
    // Показываем overlay
    const overlay = document.querySelector('.quick-view-overlay');
    const modal = document.querySelector('.quick-view-modal');
    
    if (!overlay || !modal) {
        createQuickViewElements();
    }
    
    // Загружаем данные товара
    fetch(`/api/v1/product/${productId}/quick-view`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                renderQuickView(data.product);
                showQuickView();
            }
        })
        .catch(error => {
            console.error('Error loading quick view:', error);
        });
}

// Создать элементы Quick View
function createQuickViewElements() {
    const overlay = document.createElement('div');
    overlay.className = 'quick-view-overlay';
    overlay.onclick = closeQuickView;
    
    const modal = document.createElement('div');
    modal.className = 'quick-view-modal';
    modal.innerHTML = `
        <button class="quick-view-close" onclick="closeQuickView()">
            <i class="bi bi-x"></i>
        </button>
        <div class="quick-view-content">
            <div class="quick-view-gallery">
                <div class="quick-view-main-image">
                    <img src="" alt="" id="qvMainImage">
                </div>
                <div class="quick-view-thumbnails" id="qvThumbnails"></div>
            </div>
            <div class="quick-view-info">
                <div class="quick-view-brand" id="qvBrand"></div>
                <h2 class="quick-view-title" id="qvTitle"></h2>
                <div class="quick-view-price">
                    <span class="quick-view-current-price" id="qvPrice"></span>
                    <span class="quick-view-old-price" id="qvOldPrice"></span>
                    <span class="quick-view-discount" id="qvDiscount"></span>
                </div>
                <div class="quick-view-rating">
                    <div class="rating-stars" id="qvStars"></div>
                    <span class="rating-value" id="qvRatingValue"></span>
                    <span class="rating-count" id="qvRatingCount"></span>
                </div>
                <p class="quick-view-description" id="qvDescription"></p>
                <div class="quick-view-sizes">
                    <div class="quick-view-sizes-title">
                        <span>Размер:</span>
                        <a href="/size-guide" class="size-guide-link">Размерная сетка</a>
                    </div>
                    <div class="size-options" id="qvSizes"></div>
                </div>
                <div class="quick-view-actions">
                    <button class="btn-add-to-cart" onclick="addToCartFromQuickView()">
                        <i class="bi bi-cart-plus"></i>
                        <span>В корзину</span>
                    </button>
                    <button class="btn-wishlist" onclick="toggleWishlistFromQuickView()">
                        <i class="bi bi-heart"></i>
                    </button>
                </div>
                <a href="#" class="view-full-product" id="qvFullLink">
                    Смотреть полностью
                </a>
            </div>
        </div>
    `;
    
    document.body.appendChild(overlay);
    document.body.appendChild(modal);
    
    // Блокируем скролл body
    document.body.style.overflow = 'hidden';
}

// Рендер Quick View
function renderQuickView(product) {
    document.getElementById('qvMainImage').src = product.image;
    document.getElementById('qvMainImage').alt = product.name;
    
    // Миниатюры
    const thumbsContainer = document.getElementById('qvThumbnails');
    thumbsContainer.innerHTML = product.images.map((img, idx) => `
        <div class="quick-view-thumb ${idx === 0 ? 'active' : ''}" onclick="changeQuickViewImage('${img}', this)">
            <img src="${img}" alt="${product.name}">
        </div>
    `).join('');
    
    document.getElementById('qvBrand').textContent = product.brand;
    document.getElementById('qvTitle').textContent = product.name;
    document.getElementById('qvPrice').textContent = product.price + ' BYN';
    
    // Старая цена и скидка
    const oldPriceEl = document.getElementById('qvOldPrice');
    const discountEl = document.getElementById('qvDiscount');
    
    if (product.old_price) {
        oldPriceEl.textContent = product.old_price + ' BYN';
        oldPriceEl.style.display = 'inline';
        
        const discount = Math.round((1 - product.price / product.old_price) * 100);
        discountEl.textContent = '-' + discount + '%';
        discountEl.style.display = 'inline';
    } else {
        oldPriceEl.style.display = 'none';
        discountEl.style.display = 'none';
    }
    
    // Рейтинг
    const starsContainer = document.getElementById('qvStars');
    const rating = product.rating || 4.5;
    const fullStars = Math.floor(rating);
    
    starsContainer.innerHTML = '';
    for (let i = 0; i < 5; i++) {
        const star = document.createElement('i');
        star.className = i < fullStars ? 'bi bi-star-fill' : 'bi bi-star';
        starsContainer.appendChild(star);
    }
    
    document.getElementById('qvRatingValue').textContent = rating.toFixed(1);
    document.getElementById('qvRatingCount').textContent = `(${product.reviews_count || 0} отзывов)`;
    
    document.getElementById('qvDescription').textContent = product.description || '';
    
    // Размеры
    const sizesContainer = document.getElementById('qvSizes');
    sizesContainer.innerHTML = product.sizes.map(size => `
        <div class="size-option ${size.stock === 0 ? 'out-of-stock' : ''}" 
             data-size="${size.value}"
             onclick="selectQuickViewSize(this, '${size.value}')">
            ${size.value}
        </div>
    `).join('');
    
    document.getElementById('qvFullLink').href = product.url;
    
    // Сохраняем ID товара для добавления в корзину
    modal.dataset.productId = product.id;
}

// Показать Quick View
function showQuickView() {
    const overlay = document.querySelector('.quick-view-overlay');
    const modal = document.querySelector('.quick-view-modal');
    
    overlay.classList.add('active');
    modal.classList.add('active');
    
    document.body.style.overflow = 'hidden';
}

// Закрыть Quick View
function closeQuickView() {
    const overlay = document.querySelector('.quick-view-overlay');
    const modal = document.querySelector('.quick-view-modal');
    
    overlay.classList.remove('active');
    modal.classList.remove('active');
    
    document.body.style.overflow = '';
}

// Сменить изображение
function changeQuickViewImage(src, thumbEl) {
    document.getElementById('qvMainImage').src = src;
    
    // Обновляем активный класс
    document.querySelectorAll('.quick-view-thumb').forEach(t => t.classList.remove('active'));
    thumbEl.classList.add('active');
}

// Выбрать размер
let selectedQuickViewSize = null;

function selectQuickViewSize(el, size) {
    if (el.classList.contains('out-of-stock')) return;
    
    document.querySelectorAll('.size-option').forEach(o => o.classList.remove('active'));
    el.classList.add('active');
    
    selectedQuickViewSize = size;
}

// Добавить в корзину из Quick View
function addToCartFromQuickView() {
    const modal = document.querySelector('.quick-view-modal');
    const productId = modal.dataset.productId;
    
    if (!selectedQuickViewSize) {
        alert('Выберите размер');
        return;
    }
    
    // Добавляем в корзину
    fetch('/api/v1/cart/add', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            product_id: productId,
            size: selectedQuickViewSize,
            quantity: 1
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            // Обновляем счётчик корзины
            updateCartCounter();
            
            // Показываем уведомление
            showNotification('Товар добавлен в корзину', 'success');
            
            // Закрываем Quick View
            closeQuickView();
        }
    });
}

// Добавить/убрать из избранного
function toggleWishlistFromQuickView() {
    const modal = document.querySelector('.quick-view-modal');
    const productId = modal.dataset.productId;
    const btn = modal.querySelector('.btn-wishlist');
    
    fetch('/api/v1/wishlist/toggle', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({product_id: productId})
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            btn.classList.toggle('active', data.added);
            btn.innerHTML = data.added 
                ? '<i class="bi bi-heart-fill"></i>' 
                : '<i class="bi bi-heart"></i>';
        }
    });
}

// Показать уведомление
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <i class="bi bi-${type === 'success' ? 'check-circle' : 'info-circle'}"></i>
        <span>${message}</span>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => notification.classList.add('show'), 10);
    
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Обновить счётчик корзины
function updateCartCounter() {
    fetch('/api/v1/cart/count')
        .then(r => r.json())
        .then(data => {
            const counter = document.querySelector('.cart-counter');
            if (counter && data.count > 0) {
                counter.textContent = data.count;
                counter.style.display = 'flex';
            }
        });
}

// Закрытие по Escape
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        closeQuickView();
    }
});
