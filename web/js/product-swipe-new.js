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
            
            // Dots navigation
            this.dots.forEach((dot, index) => {
                dot.addEventListener('click', () => this.goTo(index));
            });
            
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
        }
    }

    // Auto-initialize
    function init() {
        const gallery = document.querySelector('.product-gallery-swipe');
        if (gallery) {
            new ProductGallerySwipe(gallery);
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
