/**
 * Product Swipe Gallery - Native Touch API
 * Нативные жесты для мобильных устройств
 * Без внешних зависимостей, vanilla JS
 */

(function() {
    'use strict';

    class ProductGallerySwipe {
        constructor(galleryElement) {
            this.gallery = galleryElement;
            this.track = this.gallery.querySelector('.swipe-track');
            this.slides = this.gallery.querySelectorAll('.swipe-slide');
            this.dots = this.gallery.querySelectorAll('.swipe-dot');
            
            if (!this.track || this.slides.length === 0) return;
            
            this.currentIndex = 0;
            this.startX = 0;
            this.currentX = 0;
            this.isDragging = false;
            this.threshold = 50;
            
            this.init();
        }
        
        init() {
            // Touch events
            this.track.addEventListener('touchstart', this.handleTouchStart.bind(this), { passive: true });
            this.track.addEventListener('touchmove', this.handleTouchMove.bind(this), { passive: false });
            this.track.addEventListener('touchend', this.handleTouchEnd.bind(this));
            
            // Mouse events для desktop
            this.track.addEventListener('mousedown', this.handleMouseDown.bind(this));
            this.track.addEventListener('mousemove', this.handleMouseMove.bind(this));
            this.track.addEventListener('mouseup', this.handleMouseUp.bind(this));
            this.track.addEventListener('mouseleave', this.handleMouseUp.bind(this));
            
            // Keyboard navigation
            document.addEventListener('keydown', (e) => {
                if (e.key === 'ArrowLeft') this.prev();
                if (e.key === 'ArrowRight') this.next();
            });
        }
        
        handleTouchStart(e) {
            this.startX = e.touches[0].clientX;
            this.isDragging = true;
            this.track.style.transition = 'none';
        }
        
        handleTouchMove(e) {
            if (!this.isDragging) return;
            
            this.currentX = e.touches[0].clientX;
            const diff = this.currentX - this.startX;
            const translate = -(this.currentIndex * 100) + (diff / this.gallery.offsetWidth) * 100;
            
            this.track.style.transform = `translateX(${translate}%)`;
            
            if (Math.abs(diff) > 10) {
                e.preventDefault();
            }
        }
        
        handleTouchEnd() {
            if (!this.isDragging) return;
            
            const diff = this.currentX - this.startX;
            
            if (Math.abs(diff) > this.threshold) {
                if (diff > 0) {
                    this.prev();
                } else {
                    this.next();
                }
            } else {
                this.goTo(this.currentIndex);
            }
            
            this.isDragging = false;
        }
        
        handleMouseDown(e) {
            this.startX = e.clientX;
            this.isDragging = true;
            this.track.style.cursor = 'grabbing';
            this.track.style.transition = 'none';
        }
        
        handleMouseMove(e) {
            if (!this.isDragging) return;
            
            this.currentX = e.clientX;
            const diff = this.currentX - this.startX;
            const translate = -(this.currentIndex * 100) + (diff / this.gallery.offsetWidth) * 100;
            
            this.track.style.transform = `translateX(${translate}%)`;
        }
        
        handleMouseUp() {
            if (!this.isDragging) return;
            
            const diff = this.currentX - this.startX;
            
            if (Math.abs(diff) > this.threshold) {
                if (diff > 0) {
                    this.prev();
                } else {
                    this.next();
                }
            } else {
                this.goTo(this.currentIndex);
            }
            
            this.isDragging = false;
            this.track.style.cursor = 'grab';
        }
        
        prev() {
            if (this.currentIndex > 0) {
                this.goTo(this.currentIndex - 1);
            }
        }
        
        next() {
            if (this.currentIndex < this.slides.length - 1) {
                this.goTo(this.currentIndex + 1);
            }
        }
        
        goTo(index) {
            if (index < 0 || index >= this.slides.length) return;
            
            this.currentIndex = index;
            
            // Update track
            this.track.style.transition = 'transform 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
            this.track.style.transform = `translateX(-${index * 100}%)`;
            
            // Update slides
            this.slides.forEach((slide, i) => {
                slide.classList.toggle('active', i === index);
            });
            
            // Update dots
            this.dots.forEach((dot, i) => {
                dot.classList.toggle('active', i === index);
            });
            
            // Update thumbnails
            this.updateThumbnails(index);
        }
        
        updateThumbnails(index) {
            const thumbnails = document.querySelectorAll('.thumbnail-item');
            thumbnails.forEach((thumb, i) => {
                thumb.classList.toggle('active', i === index);
            });
            
            // Прокручиваем к активной миниатюре
            const activeThumbnail = thumbnails[index];
            if (activeThumbnail) {
                activeThumbnail.scrollIntoView({
                    behavior: 'smooth',
                    block: 'nearest',
                    inline: 'center'
                });
            }
        }
    }

    // Global swiper instance
    let globalSwiper = null;

    // Auto-initialize
    function init() {
        const gallery = document.querySelector('.product-gallery-swipe');
        if (gallery) {
            globalSwiper = new ProductGallerySwipe(gallery);
            
            // Добавляем обработчики для точек пагинации
            const dots = gallery.querySelectorAll('.swipe-dot');
            dots.forEach((dot, index) => {
                dot.addEventListener('click', () => {
                    globalSwiper.goTo(index);
                });
            });
            
            // Экспортируем в window для внешнего использования
            window.productSwiper = globalSwiper;
        }
    }

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Export for manual re-initialization
    window.initProductSwipe = init;
})();

/**
 * Переключение слайдов через миниатюры
 */
function switchToSlide(index) {
    // Используем глобальный swiper если доступен
    if (window.productSwiper) {
        window.productSwiper.goTo(index);
    } else {
        console.error('Product swiper не инициализирован');
    }
    
    // Обновляем миниатюры отдельно
    const thumbnails = document.querySelectorAll('.thumbnail-item');
    thumbnails.forEach((thumb, i) => {
        thumb.classList.toggle('active', i === index);
    });
    
    // Прокручиваем миниатюры к активной
    const activeThumbnail = document.querySelector('.thumbnail-item.active');
    if (activeThumbnail) {
        activeThumbnail.scrollIntoView({
            behavior: 'smooth',
            block: 'nearest',
            inline: 'center'
        });
    }
}

/**
 * Скролл миниатюр (для desktop)
 */
function scrollThumbnails(direction) {
    const wrapper = document.querySelector('.thumbnails-wrapper');
    if (!wrapper) return;
    
    const scrollAmount = 200;
    const targetScroll = wrapper.scrollLeft + (direction === 'next' ? scrollAmount : -scrollAmount);
    
    wrapper.scrollTo({
        left: targetScroll,
        behavior: 'smooth'
    });
}

/**
 * Открытие премиальной галереи изображений
 */
function openImageModal(index) {
    const modal = document.getElementById('imageGalleryModal');
    if (!modal) return;
    
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    
    // Прокручиваем к выбранному изображению
    const container = modal.querySelector('.gallery-scroll-container');
    const images = modal.querySelectorAll('.gallery-image-item');
    
    if (images[index]) {
        setTimeout(() => {
            images[index].scrollIntoView({ 
                behavior: 'smooth', 
                block: 'center' 
            });
            updateGalleryCounter(index + 1);
        }, 100);
    }
    
    // Обработчик скролла для обновления счетчика
    container.addEventListener('scroll', updateGalleryCounterOnScroll);
}

/**
 * Закрытие премиальной галереи
 */
function closeImageGallery() {
    const modal = document.getElementById('imageGalleryModal');
    if (!modal) return;
    
    modal.style.display = 'none';
    document.body.style.overflow = '';
    
    const container = modal.querySelector('.gallery-scroll-container');
    container.removeEventListener('scroll', updateGalleryCounterOnScroll);
}

/**
 * Обновление счетчика текущего изображения
 */
function updateGalleryCounter(current) {
    const counterElement = document.querySelector('.gallery-current');
    if (counterElement) {
        counterElement.textContent = current;
    }
}

/**
 * Обновление счетчика при скролле
 */
function updateGalleryCounterOnScroll() {
    const container = document.querySelector('.gallery-scroll-container');
    const images = document.querySelectorAll('.gallery-image-item');
    const containerRect = container.getBoundingClientRect();
    const centerY = containerRect.top + containerRect.height / 2;
    
    let closestIndex = 0;
    let minDistance = Infinity;
    
    images.forEach((img, index) => {
        const rect = img.getBoundingClientRect();
        const imgCenterY = rect.top + rect.height / 2;
        const distance = Math.abs(centerY - imgCenterY);
        
        if (distance < minDistance) {
            minDistance = distance;
            closestIndex = index;
        }
    });
    
    updateGalleryCounter(closestIndex + 1);
}

// Закрытие по ESC
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        closeImageGallery();
    }
});
