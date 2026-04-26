let galleryCurrentIndex = 0;
let gallerySlidesCache = null;
let galleryDotsCache = null;
let galleryThumbsCache = null;
let galleryPrevButton = null;
let galleryNextButton = null;
let galleryZoomView = null;
let galleryEnableZoom = window.matchMedia('(hover: hover) and (pointer: fine)').matches;

// Глобальные функции страницы товара. Использует SH.* из utils.js

// Проверка наличия товара в корзине
function checkProductInCart(productId) {
    fetch(`/cart/has-product?productId=${productId}`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(response => response.json())
        .then(data => {
            if (data.inCart) {
                showProductInCartIndicator();
            }
        })
        .catch(error => {
        });
}

// Показать индикатор "Товар в корзине"
function showProductInCartIndicator() {
    const indicator = document.getElementById('productInCartIndicator');
    if (indicator) {
        indicator.style.display = 'block';

        // При клике на индикатор - переход в корзину
        indicator.addEventListener('click', function () {
            window.location.href = '/cart';
        });
    }
}

// Скрыть индикатор
function hideProductInCartIndicator() {
    const indicator = document.getElementById('productInCartIndicator');
    if (indicator) {
        indicator.style.display = 'none';
    }
}

// Показать уведомление о необходимости выбора размера
function showSizeRequiredNotification() {
    // Скроллим к секции размеров
    const sizesSection = document.querySelector('.sizes-section');
    if (sizesSection) {
        sizesSection.scrollIntoView({ behavior: 'smooth', block: 'center' });

        // Добавляем подсветку
        sizesSection.classList.add('size-required-highlight');
        setTimeout(() => {
            sizesSection.classList.remove('size-required-highlight');
        }, 2000);
    }

    SH.notify('Пожалуйста, выберите размер', 'warning', 5000);
}

// Инициализация при загрузке
// Глобальные функции инициализированы

// Проверяем товар в корзине при загрузке страницы
// productId получаем из meta-тега, который устанавливается в product.php
const productIdMeta = document.querySelector('meta[name="product-id"]');
if (productIdMeta) {
    const productId = productIdMeta.getAttribute('content');
    checkProductInCart(productId);
}

// Back button в header (вместо catalog-header)
(function () {
    // Добавляем back-btn в navbar
    const navbar = document.querySelector('.navbar .container, .navbar .container-fluid');
    if (navbar && document.referrer.includes('/catalog')) {
        const backBtn = document.createElement('button');
        backBtn.className = 'btn btn-link text-white me-3';
        backBtn.innerHTML = '<i class="bi bi-arrow-left"></i> Назад';
        backBtn.onclick = () => history.back();
        backBtn.style.cssText = 'text-decoration:none;font-size:1rem';
        navbar.insertBefore(backBtn, navbar.firstChild);
    }
})();


// УДАЛЕНО: toggleFav перенесен в global-helpers.js для устранения дублирования
// Теперь эта функция загружается глобально из /web/js/global-helpers.js



// Обновление цены при выборе размера
document.addEventListener('DOMContentLoaded', function () {
    const sizeInputs = document.querySelectorAll('input[name="size"]');
    const priceElement = document.getElementById('productPrice');
    const sizeLinkElement = document.getElementById('selectedSizeLink');
    const sizeValueElement = document.getElementById('selectedSizeValue');

    const currencyFormatter = new Intl.NumberFormat('ru-BY', {
        style: 'currency',
        currency: 'BYN',
        minimumFractionDigits: 2
    });

    const stickyPriceElement = document.getElementById('stickyPrice');
    const stickyPriceState = stickyPriceElement ? {
        hasRange: stickyPriceElement.dataset.hasRange === 'true',
        basePrice: parseFloat(stickyPriceElement.dataset.basePrice || '0'),
        minPrice: parseFloat(stickyPriceElement.dataset.minPrice || '0'),
        maxPrice: parseFloat(stickyPriceElement.dataset.maxPrice || '0')
    } : null;

    const quickOrderPriceElement = document.getElementById('quickOrderPrice');
    const quickOrderPriceState = quickOrderPriceElement ? {
        hasRange: quickOrderPriceElement.dataset.hasRange === 'true',
        basePrice: parseFloat(quickOrderPriceElement.dataset.basePrice || '0'),
        minPrice: parseFloat(quickOrderPriceElement.dataset.minPrice || '0'),
        maxPrice: parseFloat(quickOrderPriceElement.dataset.maxPrice || '0')
    } : null;

    function renderStickyPriceDefault() {
        if (!stickyPriceElement || !stickyPriceState) return;
        if (stickyPriceState.hasRange && stickyPriceState.minPrice && stickyPriceState.maxPrice) {
            stickyPriceElement.innerHTML = `${currencyFormatter.format(stickyPriceState.minPrice)} <span class="price-separator"> - </span> ${currencyFormatter.format(stickyPriceState.maxPrice)}`;
        } else if (stickyPriceState.basePrice) {
            stickyPriceElement.textContent = currencyFormatter.format(stickyPriceState.basePrice);
        }
    }

    function updateStickyPriceDisplay(price) {
        if (!stickyPriceElement) return;
        if (typeof price === 'number' && !Number.isNaN(price) && price > 0) {
            stickyPriceElement.textContent = currencyFormatter.format(price);
        } else {
            renderStickyPriceDefault();
        }
    }

    function renderQuickOrderPriceDefault() {
        if (!quickOrderPriceElement || !quickOrderPriceState) return;
        if (quickOrderPriceState.hasRange && quickOrderPriceState.minPrice && quickOrderPriceState.maxPrice) {
            quickOrderPriceElement.innerHTML = `${currencyFormatter.format(quickOrderPriceState.minPrice)} <span class="price-separator"> - </span> ${currencyFormatter.format(quickOrderPriceState.maxPrice)}`;
        } else if (quickOrderPriceState.basePrice) {
            quickOrderPriceElement.textContent = currencyFormatter.format(quickOrderPriceState.basePrice);
        }
    }

    function updateQuickOrderPriceDisplay(price) {
        if (!quickOrderPriceElement) return;
        if (typeof price === 'number' && !Number.isNaN(price) && price > 0) {
            quickOrderPriceElement.textContent = currencyFormatter.format(price);
        } else {
            renderQuickOrderPriceDefault();
        }
    }

    window.__productPagePrice = {
        updateStickyPrice: updateStickyPriceDisplay,
        resetStickyPrice: renderStickyPriceDefault,
        updateQuickOrderPrice: updateQuickOrderPriceDisplay,
        resetQuickOrderPrice: renderQuickOrderPriceDefault,
        formatter: currencyFormatter
    };

    if (sizeInputs.length > 0 && priceElement) {
        const hasRange = priceElement.dataset.hasRange === 'true';
        const minPrice = parseFloat(priceElement.dataset.minPrice);
        const maxPrice = parseFloat(priceElement.dataset.maxPrice);

        sizeInputs.forEach(input => {
            input.addEventListener('change', function () {
                if (this.checked) {
                    const newPrice = parseFloat(this.dataset.price);
                    const selectedSize = this.value;

                    if (newPrice && newPrice > 0) {
                        // Показываем конкретную цену выбранного размера
                        priceElement.textContent = currencyFormatter.format(newPrice);

                        // Добавляем плавную анимацию
                        priceElement.style.transform = 'scale(1.1)';
                        setTimeout(() => {
                            priceElement.style.transform = 'scale(1)';
                        }, 200);
                    }

                    updateStickyPriceDisplay(newPrice);
                    updateQuickOrderPriceDisplay(newPrice);

                    // Показываем ссылку на каталог с выбранным размером
                    if (sizeLinkElement && sizeValueElement && selectedSize) {
                        sizeValueElement.textContent = selectedSize;
                        sizeLinkElement.style.display = 'flex';
                        sizeLinkElement.onclick = function () {
                            window.location.href = '/catalog?size=' + encodeURIComponent(selectedSize);
                        };
                    }
                }
            });
        });

        // Если убрали выбор размера - возвращаем диапазон и скрываем ссылку
        if (hasRange) {
            // Следим за сбросом выбора
            document.addEventListener('click', function (e) {
                // Если кликнули на уже выбранный размер - сбрасываем
                if (e.target.matches('input[name="size"]:checked')) {
                    setTimeout(() => {
                        const anyChecked = document.querySelector('input[name="size"]:checked');
                        if (!anyChecked) {
                            // Возвращаем диапазон цен
                            priceElement.innerHTML = currencyFormatter.format(minPrice) +
                                '<span class="price-separator"> - </span>' +
                                currencyFormatter.format(maxPrice);

                            // Скрываем ссылку на каталог
                            if (sizeLinkElement) {
                                sizeLinkElement.style.display = 'none';
                            }

                            renderStickyPriceDefault();
                            renderQuickOrderPriceDefault();
                        }
                    }, 10);
                }
            });
        }
    }

    const quickOrderSelect = document.getElementById('quickOrderSize');
    if (quickOrderSelect) {
        quickOrderSelect.addEventListener('change', function () {
            const option = this.selectedOptions[0];
            const optionPrice = option ? parseFloat(option.dataset.price || '0') : 0;
            if (optionPrice && optionPrice > 0) {
                updateQuickOrderPriceDisplay(optionPrice);
                updateStickyPriceDisplay(optionPrice);
            } else {
                renderQuickOrderPriceDefault();
                renderStickyPriceDefault();
            }
        });
    }
});

function createOrder() {
    const productIdMeta = document.querySelector('meta[name="product-id"]');
    const productId = productIdMeta ? productIdMeta.getAttribute('content') : null;
    const sizeInput = document.querySelector('input[name="size"]:checked');
    // Also check button-based size selection (window.selectedProductSize)
    const size = (sizeInput ? sizeInput.value : null) || window.selectedProductSize || null;

    // Проверяем есть ли на странице размеры (radio inputs or size buttons)
    const hasSizes = document.querySelectorAll('input[name="size"]').length > 0
        || document.querySelectorAll('#sizeGrid .size-btn').length > 0;
    if (!size && hasSizes) {
        // Показываем красивое уведомление
        showSizeRequiredNotification();
        return;
    }

    if (typeof addToCart === 'function') {
        addToCart(productId, 1, size, null);
        // Показываем индикатор после добавления
        setTimeout(() => showProductInCartIndicator(), 500);
    } else {
        alert('Товар добавлен в корзину');
        setTimeout(() => showProductInCartIndicator(), 500);
    }
}

// Функции для работы со sticky bar
function toggleStickySizeDropdown() {
    const dropdown = document.getElementById('stickySizeDropdown');
    const btn = document.getElementById('stickySizeBtn');

    if (dropdown && btn) {
        dropdown.classList.toggle('show');
        btn.classList.toggle('active');
    }
}

// Инициализация обработчиков размеров после загрузки DOM
document.addEventListener('DOMContentLoaded', function () {

    // Event Delegation на родительский dropdown
    const dropdown = document.getElementById('stickySizeDropdown');

    if (dropdown) {

        dropdown.addEventListener('click', function (e) {

            // Находим ближайший .sticky-size-option
            const sizeOption = e.target.closest('.sticky-size-option');

            if (!sizeOption) {
                return;
            }


            const size = sizeOption.dataset.size;
            const price = sizeOption.dataset.price;


            if (!size) {
                return;
            }

            // Обновляем текст кнопки
            const label = document.getElementById('stickySizeLabel');
            if (label) {
                label.textContent = size;
            }

            // Обновляем бейдж размера
            const badge = document.getElementById('stickySizeBadge');
            if (badge) {
                badge.textContent = size;
                badge.classList.remove('hidden');
            }

            // Обновляем цену в sticky bar
            const stickyPrice = document.querySelector('#stickyBar .sticky-price');
            if (stickyPrice && price) {
                const formatter = new Intl.NumberFormat('ru-BY', {
                    style: 'currency',
                    currency: 'BYN',
                    minimumFractionDigits: 2
                });
                stickyPrice.textContent = formatter.format(price);

                // Анимация изменения цены
                stickyPrice.style.transform = 'scale(1.1)';
                setTimeout(() => {
                    stickyPrice.style.transform = 'scale(1)';
                }, 200);
            }

            // Выделяем выбранный размер
            const allOptions = document.querySelectorAll('.sticky-size-option');
            allOptions.forEach(opt => opt.classList.remove('selected'));
            sizeOption.classList.add('selected');

            // Сохраняем выбранный размер
            window.selectedStickySize = size;
            // Размер сохранён

            // Закрываем dropdown
            toggleStickySizeDropdown();
        });

        // Проверяем сколько опций есть
        const options = dropdown.querySelectorAll('.sticky-size-option');
        options.forEach((opt, idx) => {
        });
    } else {
    }
});

// Добавление в корзину из sticky панели
function addToCartFromSticky() {

    const size = window.selectedStickySize;

    if (!size) {
        notify('Пожалуйста, выберите размер', 'warning');
        // Открываем dropdown размеров
        const dropdown = document.getElementById('stickySizeDropdown');
        const btn = document.getElementById('stickySizeBtn');
        if (dropdown && !dropdown.classList.contains('show')) {
            toggleStickySizeDropdown();
        }
        return;
    }

    const productIdMeta = document.querySelector('meta[name="product-id"]');
    const productId = productIdMeta ? productIdMeta.getAttribute('content') : null;

    // Добавляем товар в корзину через функцию из cart.js
    if (typeof addToCart === 'function') {
        // Вызываем addToCart
        addToCart(productId, 1, size, null);
        // Показываем индикатор после успешного добавления
        setTimeout(() => showProductInCartIndicator(), 500);
    } else {
        // Fallback - используем FormData как в cart.js
        const formData = new FormData();
        formData.append('product_id', productId);
        formData.append('quantity', 1);
        formData.append('size', size);

        // Получаем CSRF токен
        formData.append('_csrf', SH.getCsrfToken());

        fetch('/cart/add', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    notify('✓ Товар добавлен в корзину', 'success');
                    // Показываем индикатор
                    setTimeout(() => showProductInCartIndicator(), 500);
                    // Обновляем счетчик корзины
                    if (typeof updateCartCount === 'function') {
                        updateCartCount(data.count);
                    } else {
                        const cartCount = document.getElementById('cartCount');
                        if (cartCount) {
                            cartCount.textContent = data.count;
                            cartCount.style.display = data.count > 0 ? 'flex' : 'none';
                        }
                    }
                } else {
                    notify(data.message || 'Ошибка добавления в корзину', 'error');
                }
            })
            .catch(error => {
                notify('Ошибка соединения', 'error');
            });
    }
}

// Закрытие dropdown при клике вне его
document.addEventListener('click', function (e) {
    const dropdown = document.getElementById('stickySizeDropdown');
    const btn = document.getElementById('stickySizeBtn');

    if (dropdown && btn && dropdown.classList.contains('show')) {
        if (!dropdown.contains(e.target) && !btn.contains(e.target)) {
            toggleStickySizeDropdown();
        }
    }
});

// Старый обработчик sticky bar удалён - используется улучшенная версия в DOMContentLoaded

// Live viewers count - УДАЛЕНО (было fake)

// Review filters - будет активировано при подключении реальных отзывов

// ВАЖНО: Функции toggleMainSpecs, toggleRelatedProducts, scrollRelatedCarousel, toggleReviews, toggleQA
// теперь определены inline в product.php для немедленного доступа через onclick
// Оставляем дублирующую версию здесь для совместимости, если product-page.js загрузится раньше

// Accordion для характеристик
function toggleMainSpecs() {
    const content = document.getElementById('mainSpecsContent');
    const icon = document.getElementById('mainSpecsToggleIcon');
    const header = icon ? icon.closest('.specs-header-toggle') : null;

    if (content) {
        if (content.style.display === 'none' || content.style.display === '') {
            content.style.display = 'block';
            if (header) header.classList.add('open');
        } else {
            content.style.display = 'none';
            if (header) header.classList.remove('open');
        }
    }
}


// Accordion для отзывов
function toggleReviews() {
    const content = document.getElementById('reviewsContent');
    const icon = document.getElementById('reviewsToggleIcon');
    const header = icon.closest('.reviews-header');

    if (content.style.display === 'none' || content.style.display === '') {
        content.style.display = 'block';
        header.classList.add('open');
    } else {
        content.style.display = 'none';
        header.classList.remove('open');
    }
}


// НОВАЯ функция прокрутки карусели похожих товаров
function scrollRelatedCarousel(direction) {
    const wrapper = document.getElementById('relatedCarouselWrapper');
    if (!wrapper) return;

    // Получаем ширину одной карточки + gap
    const card = wrapper.querySelector('.related-product-card');
    if (!card) return;

    const cardWidth = card.offsetWidth;
    const gap = 16; // 1rem в пикселях (примерно)
    const scrollAmount = (cardWidth + gap) * 2; // Прокручиваем по 2 карточки

    if (direction === -1) {
        wrapper.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
    } else {
        wrapper.scrollBy({ left: scrollAmount, behavior: 'smooth' });
    }
}

// Accordion для Q&A
function toggleQA() {
    const content = document.getElementById('qaContent');
    const icon = document.getElementById('qaToggleIcon');
    const header = icon.closest('.qa-header');

    if (content.style.display === 'none' || content.style.display === '') {
        content.style.display = 'flex';
        header.classList.add('open');
    } else {
        content.style.display = 'none';
        header.classList.remove('open');
    }
}

// ВАЖНО: Функции openImageModal, closeImageModal, addCompleteLook и связанные с ними
// теперь определены inline в product.php, так как они требуют PHP данных
// (массив изображений товара, данные похожих товаров и т.д.)

// Gallery Thumbnails Navigation
function switchToSlide(index) {
    const slides = document.querySelectorAll('.swipe-slide');
    const track = document.querySelector('.swipe-track');
    const thumbnails = document.querySelectorAll('.thumbnail-item');
    const dots = document.querySelectorAll('.swipe-dot');

    if (!slides.length || !track) return;

    gallerySlidesCache = slides;
    galleryDotsCache = dots;
    galleryThumbsCache = thumbnails;

    // Обновляем активный слайд
    slides.forEach((slide, i) => {
        slide.classList.toggle('active', i === index);
    });

    // Скроллим к нужному слайду
    track.style.transform = `translateX(-${index * 100}%)`;

    // Обновляем активную миниатюру
    thumbnails.forEach((thumb, i) => {
        thumb.classList.toggle('active', i === index);
    });

    // Обновляем точки пагинации
    dots.forEach((dot, i) => {
        dot.classList.toggle('active', i === index);
    });

    // Скроллим миниатюры, чтобы активная была видна
    const activeThumb = document.querySelector('.thumbnail-item.active');
    if (activeThumb) {
        activeThumb.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
    }

    galleryCurrentIndex = index;
    updateGalleryArrows();
    updateGalleryZoomView(index);
}

function scrollThumbnails(direction) {
    const wrapper = document.querySelector('.thumbnails-wrapper');
    if (!wrapper) return;

    const scrollAmount = 120; // ширина одной миниатюры + gap
    const currentScroll = wrapper.scrollLeft;

    if (direction === 'prev') {
        wrapper.scrollTo({ left: currentScroll - scrollAmount, behavior: 'smooth' });
    } else {
        wrapper.scrollTo({ left: currentScroll + scrollAmount, behavior: 'smooth' });
    }
}

// Touch swipe для галереи (улучшенная версия)
document.addEventListener('DOMContentLoaded', function () {
    const track = document.querySelector('.swipe-track');
    const slides = document.querySelectorAll('.swipe-slide');

    if (!track || !slides.length) return;

    let startX = 0;
    let currentX = 0;
    let isDragging = false;
    let currentImageIndex = 0;

    track.addEventListener('touchstart', (e) => {
        startX = e.touches[0].clientX;
        isDragging = true;
    }, { passive: true });

    track.addEventListener('touchmove', (e) => {
        if (!isDragging) return;
        currentX = e.touches[0].clientX;
    }, { passive: true });

    track.addEventListener('touchend', () => {
        if (!isDragging) return;
        isDragging = false;

        const diff = startX - currentX;
        const threshold = 50;

        if (Math.abs(diff) > threshold) {
            if (diff > 0 && currentIndex < slides.length - 1) {
                currentIndex++;
            } else if (diff < 0 && currentIndex > 0) {
                currentIndex--;
            }
            switchToSlide(currentIndex);
        }
    });

    // Mouse drag для десктопа
    track.addEventListener('mousedown', (e) => {
        startX = e.clientX;
        isDragging = true;
        track.style.cursor = 'grabbing';
    });

    document.addEventListener('mousemove', (e) => {
        if (!isDragging) return;
        currentX = e.clientX;
    });

    document.addEventListener('mouseup', () => {
        if (!isDragging) return;
        isDragging = false;
        track.style.cursor = 'grab';

        const diff = startX - currentX;
        const threshold = 50;

        if (Math.abs(diff) > threshold) {
            if (diff > 0 && currentIndex < slides.length - 1) {
                currentIndex++;
            } else if (diff < 0 && currentIndex > 0) {
                currentIndex--;
            }
            switchToSlide(currentIndex);
        }
    });

    gallerySlidesCache = slides;
    galleryPrevButton = document.querySelector('.gallery-arrow.prev');
    galleryNextButton = document.querySelector('.gallery-arrow.next');
    galleryZoomView = document.getElementById('galleryZoomView');
    updateGalleryArrows();
    updateGalleryZoomView(0);

    if (galleryZoomView && galleryEnableZoom && slides.length) {
        const gallery = document.querySelector('.product-gallery-swipe');
        if (gallery) {
            gallery.addEventListener('mouseenter', (e) => handleGalleryZoomEnter(e));
            gallery.addEventListener('mousemove', (e) => handleGalleryZoomMove(e));
            gallery.addEventListener('mouseleave', () => handleGalleryZoomLeave());
        }
    }
});

function updateGalleryArrows() {
    if (!galleryPrevButton || !galleryNextButton) return;
    const slides = gallerySlidesCache || document.querySelectorAll('.swipe-slide');
    if (!slides.length) {
        galleryPrevButton.disabled = true;
        galleryNextButton.disabled = true;
        return;
    }
    galleryPrevButton.disabled = galleryCurrentIndex <= 0;
    galleryNextButton.disabled = galleryCurrentIndex >= slides.length - 1;
}

function galleryPrevSlide() {
    if (galleryCurrentIndex > 0) {
        switchToSlide(galleryCurrentIndex - 1);
    }
}

function galleryNextSlide() {
    const slides = gallerySlidesCache || document.querySelectorAll('.swipe-slide');
    if (galleryCurrentIndex < slides.length - 1) {
        switchToSlide(galleryCurrentIndex + 1);
    }
}

window.galleryPrevSlide = galleryPrevSlide;
window.galleryNextSlide = galleryNextSlide;

function handleGalleryZoomEnter() {
    if (!galleryZoomView) return;
    const slide = document.querySelector('.swipe-slide.active');
    if (!slide) return;
    const isPlaceholder = slide.dataset.placeholder === '1';
    if (isPlaceholder) {
        galleryZoomView.classList.remove('active');
        return;
    }
    updateGalleryZoomView(galleryCurrentIndex);
    galleryZoomView.classList.add('active');
}

function handleGalleryZoomMove(event) {
    if (!galleryZoomView || !galleryZoomView.classList.contains('active')) return;
    const slide = document.querySelector('.swipe-slide.active');
    if (!slide) return;
    const rect = slide.getBoundingClientRect();
    const x = ((event.clientX - rect.left) / rect.width) * 100;
    const y = ((event.clientY - rect.top) / rect.height) * 100;
    galleryZoomView.style.setProperty('--zoom-x', `${x}%`);
    galleryZoomView.style.setProperty('--zoom-y', `${y}%`);
}

function handleGalleryZoomLeave() {
    if (galleryZoomView) {
        galleryZoomView.classList.remove('active');
    }
}

function updateGalleryZoomView(index) {
    if (!galleryZoomView || !galleryEnableZoom) return;
    const slides = gallerySlidesCache || document.querySelectorAll('.swipe-slide');
    if (!slides.length) return;
    const slide = slides[index];
    if (!slide) return;
    const isPlaceholder = slide.dataset.placeholder === '1';
    if (isPlaceholder) {
        galleryZoomView.classList.remove('active');
        return;
    }
    const src = slide.dataset.fullSrc || slide.querySelector('img')?.src;
    if (src) {
        galleryZoomView.style.backgroundImage = `url('${src}')`;
    }
}

// ВАЖНО: Функции switchSizeSystem, openSizeTableModal, closeSizeTableModal, selectSizeFromTable
// теперь определены inline в product.php, так как они требуют PHP данных

// Size Guide Modal
function openSizeGuide() {
    document.getElementById('sizeGuideModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeSizeGuide() {
    document.getElementById('sizeGuideModal').style.display = 'none';
    document.body.style.overflow = '';
}

// Size Finder Modal (AI-powered)
function openSizeFinder() {
    const modal = document.createElement('div');
    modal.id = 'sizeFinderModal';
    modal.className = 'size-finder-modal';
    modal.innerHTML = `
        <div class="size-finder-content">
            <button class="modal-close" onclick="closeSizeFinder()">
                <i class="bi bi-x"></i>
            </button>
            <h2><i class="bi bi-search"></i> Найти мой размер</h2>
            <p class="size-finder-desc">Ответьте на 3 простых вопроса, и мы подберем идеальный размер</p>
            
            <div class="size-finder-step active" data-step="1">
                <h3>1. Ваш обычный размер обуви (RU)</h3>
                <div class="size-finder-options">
                    ${[38, 39, 40, 41, 42, 43, 44, 45].map(s => `
                        <button class="size-finder-btn" data-value="${s}" onclick="selectSizeFinderSize(${s})">${s}</button>
                    `).join('')}
                </div>
            </div>
            
            <div class="size-finder-step" data-step="2">
                <h3>2. Как обычно сидит обувь этого бренда?</h3>
                <div class="size-finder-options vertical">
                    <button class="size-finder-btn" data-value="tight" onclick="selectFit('tight')">
                        <i class="bi bi-arrow-down-circle"></i>
                        Обычно маломерит
                    </button>
                    <button class="size-finder-btn" data-value="perfect" onclick="selectFit('perfect')">
                        <i class="bi bi-check-circle"></i>
                        Обычно соответствует
                    </button>
                    <button class="size-finder-btn" data-value="loose" onclick="selectFit('loose')">
                        <i class="bi bi-arrow-up-circle"></i>
                        Обычно большемерит
                    </button>
                </div>
            </div>
            
            <div class="size-finder-step" data-step="3">
                <h3>3. Как вы предпочитаете носить обувь?</h3>
                <div class="size-finder-options vertical">
                    <button class="size-finder-btn" data-value="tight" onclick="selectPreference('tight')">
                        <i class="bi bi-suit-heart"></i>
                        Плотно по ноге
                    </button>
                    <button class="size-finder-btn" data-value="comfort" onclick="selectPreference('comfort')">
                        <i class="bi bi-star"></i>
                        Комфортно (рекомендуем)
                    </button>
                    <button class="size-finder-btn" data-value="loose" onclick="selectPreference('loose')">
                        <i class="bi bi-box"></i>
                        Свободно
                    </button>
                </div>
            </div>
            
            <div class="size-finder-result" id="sizeFinderResult">
                <div class="result-icon">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <h3>Ваш размер: <span id="recommendedSize">-</span></h3>
                <p class="result-confidence">Уверенность: <strong id="confidence">-</strong></p>
                <button class="btn-apply-size" onclick="applySizeRecommendation()">
                    Выбрать этот размер
                </button>
            </div>
            
            <div class="size-finder-nav">
                <button class="btn-back" onclick="prevStep()" style="display:none">
                    <i class="bi bi-arrow-left"></i> Назад
                </button>
                <button class="btn-next" onclick="nextStep()">
                    Далее <i class="bi bi-arrow-right"></i>
                </button>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
    document.body.style.overflow = 'hidden';

    window.sizeFinderData = { step: 1, size: null, fit: null, preference: null };
}

function closeSizeFinder() {
    const modal = document.getElementById('sizeFinderModal');
    if (modal) {
        modal.remove();
        document.body.style.overflow = '';
    }
}

function selectSize(size) {
    // Update active state on main product page size buttons
    document.querySelectorAll('#sizeGrid .size-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.size === String(size));
    });

    // Update "Выбрано:" display text
    const display = document.getElementById('selectedSizeDisplay');
    if (display) {
        display.textContent = size;
    }

    // Store for createOrder()
    window.selectedProductSize = String(size);
}

function selectSizeFinderSize(size) {
    window.sizeFinderData.size = size;
    document.querySelectorAll('[data-step="1"] .size-finder-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.value == size);
    });
}

function selectFit(fit) {
    window.sizeFinderData.fit = fit;
    document.querySelectorAll('[data-step="2"] .size-finder-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.value === fit);
    });
}

function selectPreference(preference) {
    window.sizeFinderData.preference = preference;
    document.querySelectorAll('[data-step="3"] .size-finder-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.value === preference);
    });
}

function nextStep() {
    const data = window.sizeFinderData;
    const currentStep = data.step;

    if (currentStep === 1 && !data.size) {
        alert('Пожалуйста, выберите размер');
        return;
    }
    if (currentStep === 2 && !data.fit) {
        alert('Пожалуйста, выберите вариант');
        return;
    }
    if (currentStep === 3 && !data.preference) {
        alert('Пожалуйста, выберите вариант');
        return;
    }

    if (currentStep < 3) {
        data.step++;
        document.querySelectorAll('.size-finder-step').forEach((step, i) => {
            step.classList.toggle('active', i + 1 === data.step);
        });
        document.querySelector('.btn-back').style.display = data.step > 1 ? 'block' : 'none';
        document.querySelector('.btn-next').style.display = data.step < 3 ? 'block' : 'none';
    } else {
        calculateRecommendation();
    }
}

function prevStep() {
    const data = window.sizeFinderData;
    if (data.step > 1) {
        data.step--;
        document.querySelectorAll('.size-finder-step').forEach((step, i) => {
            step.classList.toggle('active', i + 1 === data.step);
        });
        document.querySelector('.btn-back').style.display = data.step > 1 ? 'block' : 'none';
        document.querySelector('.btn-next').style.display = 'block';
    }
}

function calculateRecommendation() {
    const data = window.sizeFinderData;
    let recommendedSize = data.size;
    let adjustment = 0;

    // Алгоритм подбора размера
    if (data.fit === 'tight') adjustment += 0.5;
    if (data.fit === 'loose') adjustment -= 0.5;
    if (data.preference === 'tight') adjustment -= 0.5;
    if (data.preference === 'loose') adjustment += 0.5;

    recommendedSize = Math.round(recommendedSize + adjustment);

    // Уверенность в рекомендации
    const confidence = Math.abs(adjustment) < 1 ? '95%' : '85%';

    // Показываем результат
    document.querySelectorAll('.size-finder-step').forEach(step => step.classList.remove('active'));
    document.querySelector('.size-finder-nav').style.display = 'none';
    const resultDiv = document.getElementById('sizeFinderResult');
    resultDiv.classList.add('active');
    document.getElementById('recommendedSize').textContent = recommendedSize;
    document.getElementById('confidence').textContent = confidence;
}

function applySizeRecommendation() {
    const size = document.getElementById('recommendedSize').textContent;
    const sizeInput = document.querySelector(`input[name="size"][value="${size}"]`);
    if (sizeInput) {
        sizeInput.checked = true;
        sizeInput.closest('.size').querySelector('span').click();
    }
    closeSizeFinder();
}

function recommendSize() {
    const footLength = parseFloat(document.getElementById('footLength').value);
    const resultEl = document.getElementById('sizeRecommendation');

    if (!footLength || footLength < 20 || footLength > 35) {
        resultEl.textContent = 'Пожалуйста, введите корректную длину стопы (20-35 см)';
        resultEl.style.background = '#fef2f2';
        resultEl.style.borderColor = '#ef4444';
        resultEl.style.color = '#dc2626';
        resultEl.classList.add('show');
        return;
    }

    // Таблица соответствия длины стопы и размера
    const sizeChart = [
        { cm: 24.0, size: 38 },
        { cm: 24.5, size: 39 },
        { cm: 25.0, size: 40 },
        { cm: 26.0, size: 41 },
        { cm: 27.0, size: 42 },
        { cm: 28.0, size: 43 },
        { cm: 29.0, size: 44 },
        { cm: 30.0, size: 45 },
    ];

    // Находим подходящий размер
    let recommendedSize = 38;
    for (let i = 0; i < sizeChart.length; i++) {
        if (footLength <= sizeChart[i].cm) {
            recommendedSize = sizeChart[i].size;
            break;
        }
        if (i === sizeChart.length - 1 && footLength > sizeChart[i].cm) {
            recommendedSize = 45;
        }
    }

    resultEl.textContent = `✓ Рекомендуем размер: ${recommendedSize} (EU/RU)`;
    resultEl.style.background = '#ecfdf5';
    resultEl.style.borderColor = '#10b981';
    resultEl.style.color = '#059669';
    resultEl.classList.add('show');

    // Подсветим рекомендуемый размер в таблице
    const sizeTables = document.querySelectorAll('.size-table');
    sizeTables.forEach(table => {
        table.querySelectorAll('tr').forEach(row => {
            row.style.background = '';
        });
        const recommendedRow = Array.from(table.querySelectorAll('tr')).find(row => {
            const sizeCell = row.querySelector('td:first-child strong');
            return sizeCell && sizeCell.textContent.trim() === String(recommendedSize);
        });
        if (recommendedRow) {
            recommendedRow.style.background = '#ecfdf5';
        }
    });
}

// Закрытие Size Guide по клику вне окна
document.addEventListener('click', function (e) {
    const modal = document.getElementById('sizeGuideModal');
    if (e.target === modal) {
        closeSizeGuide();
    }
});

// Закрытие по ESC
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        closeSizeGuide();
    }
});

// NotificationManager загружается глобально из web/js/notifications.js

// Back button event listener
document.addEventListener('DOMContentLoaded', function () {
    const backBtn = document.getElementById('backBtn');
    if (backBtn) {
        backBtn.addEventListener('click', function (e) {
            e.preventDefault();
            history.back();
        });
    }

    // Sticky panel — правильная логика с оптимальным порогом
    const stickyBar = document.getElementById('stickyBar');
    const mainBtn = document.querySelector('.btn-order');

    if (stickyBar && mainBtn) {
        // ПРИНУДИТЕЛЬНО устанавливаем критичные стили inline
        stickyBar.style.position = 'fixed';
        stickyBar.style.bottom = '0';
        stickyBar.style.left = '0';
        stickyBar.style.right = '0';
        stickyBar.style.zIndex = '9999';
        stickyBar.style.width = '100%';
        stickyBar.style.display = 'flex';
        stickyBar.style.background = '#ffffff';
        stickyBar.style.padding = '1rem 1.5rem';
        stickyBar.style.boxShadow = '0 -4px 20px rgba(0,0,0,0.15)';
        stickyBar.style.alignItems = 'center';
        stickyBar.style.gap = '1rem';
        stickyBar.style.minHeight = '72px';
        stickyBar.style.borderTop = '1px solid #e5e7eb';
        stickyBar.style.transition = 'transform 0.3s ease-in-out, opacity 0.3s ease-in-out';
        // Изначально скрыта
        stickyBar.style.transform = 'translateY(100%)';
        stickyBar.style.opacity = '0';

        const SCROLL_THRESHOLD = 200; // Порог 200px для более раннего появления

        const updateStickyVisibility = () => {
            const offset = window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop || 0;
            const mainBtnRect = mainBtn.getBoundingClientRect();

            // Показываем sticky bar когда основная кнопка уходит за верх экрана
            // ИЛИ когда прокрутили больше порога
            if (mainBtnRect.top < 0 || offset > SCROLL_THRESHOLD) {
                if (!stickyBar.classList.contains('visible')) {
                    stickyBar.classList.add('visible');
                    stickyBar.style.transform = 'translateY(0)';
                    stickyBar.style.opacity = '1';
                }
            } else {
                if (stickyBar.classList.contains('visible')) {
                    stickyBar.classList.remove('visible');
                    stickyBar.style.transform = 'translateY(100%)';
                    stickyBar.style.opacity = '0';
                }
            }
        };

        // Находим ВСЕ потенциально скроллируемые элементы и добавляем обработчики
        const scrollableElements = [
            window,
            document,
            document.documentElement,
            document.body,
            document.querySelector('.product-page-optimized'),
            document.querySelector('main'),
            document.querySelector('#content')
        ].filter(el => el !== null);

        scrollableElements.forEach(element => {
            element.addEventListener('scroll', updateStickyVisibility, { passive: true });
        });

        // Запасной вариант: проверяем позицию кнопки каждые 200ms
        setInterval(() => {
            const mainBtnRect = mainBtn.getBoundingClientRect();
            const offset = window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop || 0;

            if (mainBtnRect.top < 0 || offset > SCROLL_THRESHOLD) {
                if (!stickyBar.classList.contains('visible')) {
                    stickyBar.classList.add('visible');
                    stickyBar.style.transform = 'translateY(0)';
                    stickyBar.style.opacity = '1';
                }
            } else {
                if (stickyBar.classList.contains('visible')) {
                    stickyBar.classList.remove('visible');
                    stickyBar.style.transform = 'translateY(100%)';
                    stickyBar.style.opacity = '0';
                }
            }
        }, 200);

        // Проверяем сразу при загрузке
        updateStickyVisibility();
    }
});

// Финальная проверка доступности всех функций
window.addEventListener('load', function () {

    // Проверяем наличие элементов
    const stickyBar = document.getElementById('stickyBar');
    const stickyDropdown = document.getElementById('stickySizeDropdown');
    const stickyBtn = document.getElementById('stickySizeBtn');
    const addBtn = document.querySelector('.sticky-add-cart');


    if (stickyDropdown) {
        const options = stickyDropdown.querySelectorAll('.sticky-size-option');
        if (options.length > 0) {

            // Проверяем обработчики
            const hasListeners = options[0].onclick !== null ||
                (options[0]._listeners && options[0]._listeners.click);
        }
    }

});

// Frequently Bought Together - Add all to cart
function addAllToCartFBT() {
    const fbtCards = document.querySelectorAll('.fbt-product-card');
    let addedCount = 0;

    fbtCards.forEach((card, index) => {
        // Get product info from the card
        const productName = card.querySelector('.fbt-product-name')?.textContent || '';
        const productPrice = card.querySelector('.fbt-product-price')?.textContent || '';

        // Add to cart (simulate API call)
        setTimeout(() => {
            addedCount++;

            // Show notification
            if (addedCount === fbtCards.length) {
                showNotification('Все товары добавлены в корзину!', 'success');

                // Update cart counter if exists
                const cartCounter = document.querySelector('.cart-counter');
                if (cartCounter) {
                    const currentCount = parseInt(cartCounter.textContent) || 0;
                    cartCounter.textContent = currentCount + fbtCards.length;
                }
            }
        }, index * 100);
    });
}

// Delegate to global SH.notify from utils.js
function showNotification(message, type) {
    SH.notify(message, type || 'info');
}

// ============================================================
// GALLERY — changeMainImage / initProductGallery
// ============================================================

/**
 * Switch the main product image when a thumbnail is clicked.
 * Works with the simple gallery layout in product.php that uses
 * #mainImage + .thumbnail-item elements.
 */
function changeMainImage(index) {
    const images = window.productGalleryImages || [];
    const idx = parseInt(index, 10);
    if (!images.length || isNaN(idx) || idx < 0 || idx >= images.length) return;

    const mainImg = document.getElementById('mainImage');
    if (mainImg) {
        mainImg.style.opacity = '0';
        mainImg.style.transition = 'opacity 0.2s ease';
        setTimeout(function () {
            mainImg.src = images[idx];
            mainImg.style.opacity = '1';
        }, 150);
    }

    document.querySelectorAll('.gallery-thumbnails .thumbnail-item').forEach(function (thumb, i) {
        thumb.classList.toggle('active', i === idx);
    });
}

/**
 * Initialise the product gallery — attach lightbox to main image
 * and make thumbnails interactive.
 */
function initProductGallery() {
    const mainImg = document.getElementById('mainImage');
    if (!mainImg) return;

    const images = window.productGalleryImages || [];

    // Make main image clickable for lightbox (if lightbox is loaded)
    if (images.length && typeof lightbox !== 'undefined') {
        mainImg.style.cursor = 'zoom-in';
        const galleryContainer = mainImg.closest('.main-image-wrapper') || mainImg.parentElement;
        if (galleryContainer && !galleryContainer.querySelector('a[data-lightbox]')) {
            const wrapper = document.createElement('a');
            wrapper.href = mainImg.src;
            wrapper.setAttribute('data-lightbox', 'product-gallery');
            wrapper.setAttribute('data-title', mainImg.alt || '');
            mainImg.parentNode.insertBefore(wrapper, mainImg);
            wrapper.appendChild(mainImg);

            // Register all gallery images for lightbox navigation
            images.forEach(function (url, i) {
                if (i === 0) return; // first already added
                const hidden = document.createElement('a');
                hidden.href = url;
                hidden.setAttribute('data-lightbox', 'product-gallery');
                hidden.style.display = 'none';
                galleryContainer.appendChild(hidden);
            });
        }
    }

    // Thumbnails click
    document.querySelectorAll('.gallery-thumbnails .thumbnail-item').forEach(function (thumb, i) {
        thumb.addEventListener('click', function () { changeMainImage(i); });
    });
}

/**
 * Initialise the size selector: clicking a size button sets window.selectedProductSize
 * and updates the visual active state + "Выбрано:" text.
 * (selectSize() already handles this; this is the init wrapper called from POS_READY)
 */
function initSizeSelector() {
    document.querySelectorAll('#sizeGrid .size-btn:not([disabled])').forEach(function (btn) {
        btn.addEventListener('click', function () {
            selectSize(btn.dataset.size || btn.textContent.trim());
        });
    });
}

/**
 * Sticky bar show/hide on scroll.
 */
function initStickyPurchaseBar() {
    const stickyBar = document.getElementById('stickyBar');
    if (!stickyBar) return;

    const purchaseActions = document.querySelector('.purchase-actions');
    if (!purchaseActions) return;

    const observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            stickyBar.classList.toggle('visible', !entry.isIntersecting);
        });
    }, { threshold: 0 });

    observer.observe(purchaseActions);
}

// ============================================================
// SIMILAR PRODUCTS CAROUSEL — slideSimilarProducts
// ============================================================

/**
 * Scroll the similar-products carousel left or right.
 */
function slideSimilarProducts(direction) {
    const carousel = document.getElementById('similarProductsCarousel');
    if (!carousel) return;

    const track = carousel.querySelector('.carousel-track');
    if (!track) return;

    const card = track.querySelector('.similar-product-card');
    const cardWidth = card ? (card.offsetWidth + 16) : 280; // 16px gap
    const scrollAmount = cardWidth * 2;

    if (direction === 'prev') {
        carousel.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
    } else {
        carousel.scrollBy({ left: scrollAmount, behavior: 'smooth' });
    }
}

// === SIZE GUIDE MODAL ===
function openSizeGuide() {
    var modal = document.getElementById('sizeGuideModal');
    if (modal) {
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
        modal.addEventListener('click', function handleOverlayClick(e) {
            if (e.target === modal) {
                closeSizeGuide();
                modal.removeEventListener('click', handleOverlayClick);
            }
        });
    }
}

function closeSizeGuide() {
    var modal = document.getElementById('sizeGuideModal');
    if (modal) {
        modal.classList.remove('show');
        document.body.style.overflow = '';
    }
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeSizeGuide();
    }
});

// Size guide tab switching
document.addEventListener('DOMContentLoaded', function() {
    var tabBtns = document.querySelectorAll('.size-tab-btn');
    tabBtns.forEach(function(btn) {
        btn.addEventListener('click', function() {
            tabBtns.forEach(function(b) { b.classList.remove('active'); });
            btn.classList.add('active');
        });
    });
});

// Auto-init on DOMContentLoaded (safe to call multiple times)
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
        initProductGallery();
        initSizeSelector();
        initStickyPurchaseBar();
    });
} else {
    // DOMContentLoaded already fired (POS_READY context)
    initProductGallery();
    initSizeSelector();
    initStickyPurchaseBar();
}
