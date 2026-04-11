/**
 * Quick View Modal — Бы��трый просмотр товара
 * Uses SH.* utilities from utils.js
 */

// Открыть Quick View
function openQuickView(productId) {
    var overlay = document.querySelector('.quick-view-overlay');
    var modal = document.querySelector('.quick-view-modal');

    if (!overlay || !modal) {
        createQuickViewElements();
    }

    SH.fetch('/api/v1/product/' + productId + '/quick-view')
        .then(function (data) {
            if (data.success) {
                renderQuickView(data.product);
                showQuickView();
            }
        })
        .catch(function (error) {
            /* production: silent */
        });
}

// Создать элементы Quick View
function createQuickViewElements() {
    var overlay = document.createElement('div');
    overlay.className = 'quick-view-overlay';
    overlay.onclick = closeQuickView;

    var modal = document.createElement('div');
    modal.className = 'quick-view-modal';
    modal.innerHTML =
        '<button class="quick-view-close" onclick="closeQuickView()">' +
            '<i class="bi bi-x"></i>' +
        '</button>' +
        '<div class="quick-view-content">' +
            '<div class="quick-view-gallery">' +
                '<div class="quick-view-main-image"><img src="" alt="" id="qvMainImage"></div>' +
                '<div class="quick-view-thumbnails" id="qvThumbnails"></div>' +
            '</div>' +
            '<div class="quick-view-info">' +
                '<div class="quick-view-brand" id="qvBrand"></div>' +
                '<h2 class="quick-view-title" id="qvTitle"></h2>' +
                '<div class="quick-view-price">' +
                    '<span class="quick-view-current-price" id="qvPrice"></span>' +
                    '<span class="quick-view-old-price" id="qvOldPrice"></span>' +
                    '<span class="quick-view-discount" id="qvDiscount"></span>' +
                '</div>' +
                '<div class="quick-view-rating">' +
                    '<div class="rating-stars" id="qvStars"></div>' +
                    '<span class="rating-value" id="qvRatingValue"></span>' +
                    '<span class="rating-count" id="qvRatingCount"></span>' +
                '</div>' +
                '<p class="quick-view-description" id="qvDescription"></p>' +
                '<div class="quick-view-sizes">' +
                    '<div class="quick-view-sizes-title">' +
                        '<span>Размер:</span>' +
                        '<a href="/size-guide" class="size-guide-link">Размерная сетка</a>' +
                    '</div>' +
                    '<div class="size-options" id="qvSizes"></div>' +
                '</div>' +
                '<div class="quick-view-actions">' +
                    '<button class="btn-add-to-cart" onclick="addToCartFromQuickView()">' +
                        '<i class="bi bi-cart-plus"></i> <span>В корзину</span>' +
                    '</button>' +
                    '<button class="btn-wishlist" onclick="toggleWishlistFromQuickView()">' +
                        '<i class="bi bi-heart"></i>' +
                    '</button>' +
                '</div>' +
                '<a href="#" class="view-full-product" id="qvFullLink">Смотреть полностью</a>' +
            '</div>' +
        '</div>';

    document.body.appendChild(overlay);
    document.body.appendChild(modal);
}

// Рендер Quick View
function renderQuickView(product) {
    document.getElementById('qvMainImage').src = product.image;
    document.getElementById('qvMainImage').alt = product.name;

    var thumbsContainer = document.getElementById('qvThumbnails');
    thumbsContainer.innerHTML = product.images.map(function (img, idx) {
        return '<div class="quick-view-thumb ' + (idx === 0 ? 'active' : '') + '" onclick="changeQuickViewImage(\'' + img + '\', this)">' +
            '<img src="' + img + '" alt="' + product.name + '">' +
        '</div>';
    }).join('');

    document.getElementById('qvBrand').textContent = product.brand;
    document.getElementById('qvTitle').textContent = product.name;
    document.getElementById('qvPrice').textContent = product.price + ' BYN';

    var oldPriceEl = document.getElementById('qvOldPrice');
    var discountEl = document.getElementById('qvDiscount');

    if (product.old_price) {
        oldPriceEl.textContent = product.old_price + ' BYN';
        oldPriceEl.style.display = 'inline';
        var discount = Math.round((1 - product.price / product.old_price) * 100);
        discountEl.textContent = '-' + discount + '%';
        discountEl.style.display = 'inline';
    } else {
        oldPriceEl.style.display = 'none';
        discountEl.style.display = 'none';
    }

    // Rating
    var starsContainer = document.getElementById('qvStars');
    var rating = product.rating || 4.5;
    var fullStars = Math.floor(rating);
    starsContainer.innerHTML = '';
    for (var i = 0; i < 5; i++) {
        var star = document.createElement('i');
        star.className = i < fullStars ? 'bi bi-star-fill' : 'bi bi-star';
        starsContainer.appendChild(star);
    }
    document.getElementById('qvRatingValue').textContent = rating.toFixed(1);
    document.getElementById('qvRatingCount').textContent = '(' + (product.reviews_count || 0) + ' отзывов)';
    document.getElementById('qvDescription').textContent = product.description || '';

    // Sizes
    var sizesContainer = document.getElementById('qvSizes');
    sizesContainer.innerHTML = product.sizes.map(function (size) {
        return '<div class="size-option ' + (size.stock === 0 ? 'out-of-stock' : '') + '" ' +
            'data-size="' + size.value + '" ' +
            'onclick="selectQuickViewSize(this, \'' + size.value + '\')">' +
            size.value +
        '</div>';
    }).join('');

    document.getElementById('qvFullLink').href = product.url;

    var modal = document.querySelector('.quick-view-modal');
    modal.dataset.productId = product.id;
}

// Показать / Закрыть Quick View
function showQuickView() {
    SH.openModal(document.querySelector('.quick-view-overlay'));
    SH.openModal(document.querySelector('.quick-view-modal'));
}

function closeQuickView() {
    SH.closeModal(document.querySelector('.quick-view-overlay'));
    SH.closeModal(document.querySelector('.quick-view-modal'));
}

// Сменить изображение
function changeQuickViewImage(src, thumbEl) {
    document.getElementById('qvMainImage').src = src;
    document.querySelectorAll('.quick-view-thumb').forEach(function (t) { t.classList.remove('active'); });
    thumbEl.classList.add('active');
}

// Выбрать размер
var selectedQuickViewSize = null;

function selectQuickViewSize(el, size) {
    if (el.classList.contains('out-of-stock')) return;
    document.querySelectorAll('.size-option').forEach(function (o) { o.classList.remove('active'); });
    el.classList.add('active');
    selectedQuickViewSize = size;
}

// Добавить в корзину из Quick View
function addToCartFromQuickView() {
    var modal = document.querySelector('.quick-view-modal');
    var productId = modal.dataset.productId;

    if (!selectedQuickViewSize) {
        SH.notify('Выберите размер', 'warning');
        return;
    }

    SH.fetch('/api/v1/cart/add', {
        method: 'POST',
        body: { product_id: productId, size: selectedQuickViewSize, quantity: 1 }
    })
    .then(function (data) {
        if (data.success) {
            updateCartCount(data.count || 0);
            SH.notify('Товар добавлен в корзину', 'success');
            closeQuickView();
        }
    });
}

// Добавить/убрать из избранного
function toggleWishlistFromQuickView() {
    var modal = document.querySelector('.quick-view-modal');
    var productId = modal.dataset.productId;
    var btn = modal.querySelector('.btn-wishlist');

    SH.fetch('/api/v1/wishlist/toggle', {
        method: 'POST',
        body: { product_id: productId }
    })
    .then(function (data) {
        if (data.success) {
            btn.classList.toggle('active', data.added);
            btn.innerHTML = data.added
                ? '<i class="bi bi-heart-fill"></i>'
                : '<i class="bi bi-heart"></i>';
        }
    });
}

// Закрытие по Escape
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeQuickView();
});
