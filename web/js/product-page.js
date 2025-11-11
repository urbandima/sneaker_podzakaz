// КРИТИЧНО: Определяем все глобальные функции ДО загрузки cart.js

// Уведомления
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => notification.classList.add('show'), 10);
    
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, 2000);
}

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
        console.log('Ошибка проверки товара в корзине:', error);
    });
}

// Показать индикатор "Товар в корзине"
function showProductInCartIndicator() {
    const indicator = document.getElementById('productInCartIndicator');
    if (indicator) {
        indicator.style.display = 'block';
        
        // При клике на индикатор - переход в корзину
        indicator.addEventListener('click', function() {
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
(function() {
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
document.addEventListener('DOMContentLoaded', function() {
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
            input.addEventListener('change', function() {
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
                        sizeLinkElement.onclick = function() {
                            window.location.href = '/catalog?size=' + encodeURIComponent(selectedSize);
                        };
                    }
                }
            });
        });
        
        // Если убрали выбор размера - возвращаем диапазон и скрываем ссылку
        if (hasRange) {
            // Следим за сбросом выбора
            document.addEventListener('click', function(e) {
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
        quickOrderSelect.addEventListener('change', function() {
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

function createOrder(){
    const productIdMeta = document.querySelector('meta[name="product-id"]');
    const productId = productIdMeta ? productIdMeta.getAttribute('content') : null;
    const sizeInput = document.querySelector('input[name="size"]:checked');
    const size = sizeInput ? sizeInput.value : null;
    
    // Проверяем есть ли на странице размеры
    const hasSizes = document.querySelectorAll('input[name="size"]').length > 0;
    if (!size && hasSizes) {
        alert('Пожалуйста, выберите размер');
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
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔧 Инициализация обработчиков стики-панели');
    
    // Event Delegation на родительский dropdown
    const dropdown = document.getElementById('stickySizeDropdown');
    
    if (dropdown) {
        console.log('🔧 Dropdown найден, добавляем event delegation');
        
        dropdown.addEventListener('click', function(e) {
            console.log('🔵 Клик в dropdown, target:', e.target);
            
            // Находим ближайший .sticky-size-option
            const sizeOption = e.target.closest('.sticky-size-option');
            
            if (!sizeOption) {
                console.log('⚠️ Клик не на опцию размера');
                return;
            }
            
            console.log('🔵 Клик на размер! element:', sizeOption);
            
            const size = sizeOption.dataset.size;
            const price = sizeOption.dataset.price;
            
            console.log('🔵 Извлечены данные - size:', size, 'price:', price);
            
            if (!size) {
                console.error('❌ size пустой!');
                return;
            }
            
            // Обновляем текст кнопки
            const label = document.getElementById('stickySizeLabel');
            if (label) {
                label.textContent = size;
                // Обновлен label
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
        console.log('🔧 Найдено опций размеров:', options.length);
        options.forEach((opt, idx) => {
            console.log(`  Опция ${idx}: size=${opt.dataset.size}, price=${opt.dataset.price}`);
        });
    } else {
        console.error('❌ Dropdown не найден!');
    }
});

// Добавление в корзину из sticky панели
function addToCartFromSticky() {
    console.log('🟢 addToCartFromSticky вызвана');
    console.log('🟢 window.selectedStickySize:', window.selectedStickySize);
    
    const size = window.selectedStickySize;
    
    if (!size) {
        console.warn('⚠️ Размер не выбран');
        showNotification('Пожалуйста, выберите размер', 'warning');
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
    console.log('🟢 productId:', productId, 'size:', size);
    console.log('🟢 typeof addToCart:', typeof addToCart);
    console.log('🟢 typeof showNotification:', typeof showNotification);
    
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
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        formData.append('_csrf', csrfToken);
        
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
                showNotification('✓ Товар добавлен в корзину', 'success');
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
                showNotification(data.message || 'Ошибка добавления в корзину', 'error');
            }
        })
        .catch(error => {
            showNotification('Ошибка соединения', 'error');
        });
    }
}

// Закрытие dropdown при клике вне его
document.addEventListener('click', function(e) {
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

// Accordion для описания
function toggleDescription() {
    const content = document.getElementById('descContent');
    const icon = document.getElementById('descToggleIcon');
    const header = icon.closest('.desc-header');
    
    if (content.style.display === 'none' || content.style.display === '') {
        content.style.display = 'block';
        header.classList.add('open');
    } else {
        content.style.display = 'none';
        header.classList.remove('open');
    }
}

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

// ДОБАВЛЕНО: Accordion для рекомендации размера
function toggleSizeRec() {
    const content = document.getElementById('sizeRecContent');
    const icon = document.getElementById('sizeRecToggleIcon');
    
    if (content.style.display === 'none' || content.style.display === '') {
        content.style.display = 'block';
        icon.style.transform = 'rotate(180deg)';
    } else {
        content.style.display = 'none';
        icon.style.transform = 'rotate(0deg)';
    }
}

// Accordion для Complete the Look
function toggleCompleteLook() {
    const content = document.getElementById('completeLookContent');
    const icon = document.getElementById('completeLookToggleIcon');
    const header = icon.closest('.look-header');
    
    if (content.style.display === 'none' || content.style.display === '') {
        content.style.display = 'block';
        header.classList.add('open');
    } else {
        content.style.display = 'none';
        header.classList.remove('open');
    }
}

// Accordion для Model Variants
function toggleVariants() {
    const content = document.getElementById('variantsContent');
    const icon = document.getElementById('variantsToggleIcon');
    const header = icon.closest('.variants-header');
    
    if (content.style.display === 'none' || content.style.display === '') {
        content.style.display = 'block';
        header.classList.add('open');
    } else {
        content.style.display = 'none';
        header.classList.remove('open');
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

// Accordion для карусели похожих товаров (СТАРЫЙ - оставлен для совместимости)
function toggleRelatedCarousel() {
    const content = document.getElementById('relatedCarouselContent');
    const icon = document.getElementById('relatedCarouselToggleIcon');
    const header = icon.closest('.carousel-header');
    
    if (content.style.display === 'none' || content.style.display === '') {
        content.style.display = 'block';
        header.classList.add('active');
    } else {
        content.style.display = 'none';
        header.classList.remove('active');
    }
}

// Карусель - прокрутка (СТАРЫЙ - оставлен для совместимости)


// НОВЫЙ Accordion для блока похожих товаров
function toggleRelatedProducts() {
    const content = document.getElementById('relatedContent');
    const icon = document.getElementById('relatedToggleIcon');
    const header = icon.closest('.related-header');
    
    if (!content || !icon || !header) return;
    
    if (content.style.display === 'none' || content.style.display === '') {
        content.style.display = 'block';
        header.classList.add('active');
    } else {
        content.style.display = 'none';
        header.classList.remove('active');
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

// Accordion для похожих товаров
function toggleSimilar() {
    const content = document.getElementById('similarContent');
    const icon = document.getElementById('similarToggleIcon');
    const header = icon.closest('.similar-header');
    
    if (content.style.display === 'none' || content.style.display === '') {
        content.style.display = 'grid';
        header.classList.add('open');
    } else {
        content.style.display = 'none';
        header.classList.remove('open');
    }
}

// ВАЖНО: Функции openImageModal, closeImageModal, addCompleteLook и связанные с ними
// теперь определены inline в product.php, так как они требуют PHP данных
// (массив изображений товара, данные похожих товаров и т.д.)

// Color Selection
function selectColor(button) {
    // Remove active class from all color buttons
    document.querySelectorAll('.color-variation').forEach(btn => btn.classList.remove('active'));
    // Add active class to selected button
    button.classList.add('active');
    // Update selected color name
    const colorName = button.dataset.colorName;
    document.getElementById('selectedColorName').textContent = colorName;
}

// Gallery Thumbnails Navigation
function switchToSlide(index) {
    const slides = document.querySelectorAll('.swipe-slide');
    const track = document.querySelector('.swipe-track');
    const thumbnails = document.querySelectorAll('.thumbnail-item');
    const dots = document.querySelectorAll('.swipe-dot');
    
    if (!slides.length || !track) return;
    
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
document.addEventListener('DOMContentLoaded', function() {
    const track = document.querySelector('.swipe-track');
    const slides = document.querySelectorAll('.swipe-slide');
    
    if (!track || !slides.length) return;
    
    let startX = 0;
    let currentX = 0;
    let isDragging = false;
    let currentIndex = 0;
    
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
});

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
                    ${[38,39,40,41,42,43,44,45].map(s => `
                        <button class="size-finder-btn" data-value="${s}" onclick="selectSize(${s})">${s}</button>
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
        {cm: 24.0, size: 38},
        {cm: 24.5, size: 39},
        {cm: 25.0, size: 40},
        {cm: 26.0, size: 41},
        {cm: 27.0, size: 42},
        {cm: 28.0, size: 43},
        {cm: 29.0, size: 44},
        {cm: 30.0, size: 45},
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
    document.querySelectorAll('.size-table tr').forEach(row => {
        row.style.background = '';
    });
    const recommendedRow = document.querySelector(`.size-table tr:has(td:first-child strong:contains("${recommendedSize}"))`);
    if (recommendedRow) {
        recommendedRow.style.background = '#ecfdf5';
    }
}

// Закрытие Size Guide по клику вне окна
document.addEventListener('click', function(e) {
    const modal = document.getElementById('sizeGuideModal');
    if (e.target === modal) {
        closeSizeGuide();
    }
});

// Закрытие по ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeSizeGuide();
    }
});

// showNotification уже определена выше до загрузки cart.js

// Back button event listener
document.addEventListener('DOMContentLoaded', function() {
    const backBtn = document.getElementById('backBtn');
    if (backBtn) {
        backBtn.addEventListener('click', function(e) {
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
window.addEventListener('load', function() {
    console.log('📊 Проверка доступности функций:');
    console.log('  - showNotification:', typeof showNotification);
    console.log('  - addToCart:', typeof addToCart);
    console.log('  - addToCartFromSticky:', typeof addToCartFromSticky);
    console.log('  - toggleStickySizeDropdown:', typeof toggleStickySizeDropdown);
    console.log('  - window.selectedStickySize:', window.selectedStickySize);
    
    // Проверяем наличие элементов
    const stickyBar = document.getElementById('stickyBar');
    const stickyDropdown = document.getElementById('stickySizeDropdown');
    const stickyBtn = document.getElementById('stickySizeBtn');
    const addBtn = document.querySelector('.sticky-add-cart');
    
    console.log('📊 Проверка элементов DOM:');
    console.log('  - stickyBar:', !!stickyBar);
    console.log('  - stickySizeDropdown:', !!stickyDropdown);
    console.log('  - stickySizeBtn:', !!stickyBtn);
    console.log('  - sticky-add-cart button:', !!addBtn);
    
    if (stickyDropdown) {
        const options = stickyDropdown.querySelectorAll('.sticky-size-option');
        console.log('  - Количество опций размеров:', options.length);
        if (options.length > 0) {
            console.log('  - Первая опция data-size:', options[0].dataset.size);
            console.log('  - Первая опция data-price:', options[0].dataset.price);
            
            // Проверяем обработчики
            const hasListeners = options[0].onclick !== null || 
                                (options[0]._listeners && options[0]._listeners.click);
            console.log('  - У первой опции есть обработчик клика:', hasListeners);
        }
    }
    
    console.log('✅ Все проверки завершены');
});
