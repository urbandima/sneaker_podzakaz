/**
 * Sticky Purchase Bar
 * Фиксированная панель покупки при скролле
 */

(function() {
    'use strict';
    
    let stickyBar = null;
    let productDetails = null;
    let isVisible = false;
    let selectedSize = null;
    
    // Инициализация при загрузке DOM
    document.addEventListener('DOMContentLoaded', function() {
        stickyBar = document.getElementById('stickyPurchaseBar');
        productDetails = document.querySelector('.product-details');
        
        if (!stickyBar || !productDetails) return;
        
        // Слушаем скролл
        window.addEventListener('scroll', handleScroll);
        
        // Слушаем изменение размера в основной форме
        const mainSizeInputs = document.querySelectorAll('.sizes input[name="size"]');
        mainSizeInputs.forEach(input => {
            input.addEventListener('change', function() {
                if (this.checked) {
                    updateStickySize(this.value, this.dataset.price);
                }
            });
        });
        
        // Слушаем изменение размера в sticky панели
        const stickySizeSelect = document.getElementById('stickySizeSelect');
        if (stickySizeSelect) {
            stickySizeSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const size = selectedOption.value;
                const price = selectedOption.dataset.price;
                
                if (size) {
                    // Синхронизируем с основной формой
                    syncMainSizeSelection(size);
                    updateStickyPrice(price);
                    selectedSize = size;
                    
                    // Активируем кнопку покупки
                    const buyBtn = document.getElementById('stickyBuyBtn');
                    if (buyBtn) {
                        buyBtn.disabled = false;
                        buyBtn.classList.remove('disabled');
                    }
                }
            });
        }
        
        // Обработчик кнопки покупки в sticky панели
        const stickyBuyBtn = document.getElementById('stickyBuyBtn');
        if (stickyBuyBtn) {
            stickyBuyBtn.addEventListener('click', function() {
                if (!selectedSize) {
                    // Если размер не выбран, скроллим к выбору размера
                    const sizesSection = document.querySelector('.sizes-section');
                    if (sizesSection) {
                        sizesSection.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        // Подсвечиваем секцию размеров
                        sizesSection.classList.add('highlight-pulse');
                        setTimeout(() => {
                            sizesSection.classList.remove('highlight-pulse');
                        }, 2000);
                    }
                    return;
                }
                
                // Вызываем основную функцию заказа
                if (typeof createOrder === 'function') {
                    createOrder();
                }
            });
        }
    });
    
    function handleScroll() {
        if (!stickyBar) return;
        
        // Упрощенная логика: показываем после 300px скролла
        const scrollPosition = window.pageYOffset || document.documentElement.scrollTop;
        
        if (scrollPosition > 300) {
            showStickyBar();
        } else {
            hideStickyBar();
        }
    }
    
    function showStickyBar() {
        if (isVisible) return;
        
        stickyBar.style.display = 'flex';
        // Небольшая задержка для анимации
        setTimeout(() => {
            stickyBar.classList.add('visible');
        }, 10);
        isVisible = true;
    }
    
    function hideStickyBar() {
        if (!isVisible) return;
        
        stickyBar.classList.remove('visible');
        setTimeout(() => {
            stickyBar.style.display = 'none';
        }, 300);
        isVisible = false;
    }
    
    function updateStickySize(size, price) {
        selectedSize = size;
        
        // Обновляем select в sticky панели
        const stickySizeSelect = document.getElementById('stickySizeSelect');
        if (stickySizeSelect) {
            stickySizeSelect.value = size;
        }
        
        // Обновляем цену
        updateStickyPrice(price);
        
        // Активируем кнопку
        const buyBtn = document.getElementById('stickyBuyBtn');
        if (buyBtn) {
            buyBtn.disabled = false;
            buyBtn.classList.remove('disabled');
        }
    }
    
    function updateStickyPrice(price) {
        const priceElement = document.getElementById('stickyPrice');
        if (priceElement && price) {
            // Форматируем цену
            const formattedPrice = parseFloat(price).toFixed(2) + ' BYN';
            priceElement.textContent = formattedPrice;
            
            // Анимация изменения цены
            priceElement.classList.add('price-change');
            setTimeout(() => {
                priceElement.classList.remove('price-change');
            }, 300);
        }
    }
    
    function syncMainSizeSelection(size) {
        // Синхронизируем выбор размера с основной формой
        const mainSizeInputs = document.querySelectorAll('.sizes input[name="size"]');
        mainSizeInputs.forEach(input => {
            if (input.value === size) {
                input.checked = true;
                input.dispatchEvent(new Event('change'));
            }
        });
    }
    
    // Экспорт функций для глобального доступа
    window.stickyBar = {
        updateSize: updateStickySize,
        updatePrice: updateStickyPrice
    };
})();
