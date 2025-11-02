/**
 * Product Swipe Gallery - Native Touch API (Optimized)
 * Нативные жесты для мобильных устройств
 * Без внешних зависимостей, vanilla JS
 */

class ProductSwipeGallery {
    constructor(containerId, options = {}) {
        this.container = document.getElementById(containerId);
        if (!this.container) return;
        
        this.images = options.images || [];
        this.currentIndex = 0;
        this.startX = 0;
        this.currentX = 0;
        this.isDragging = false;
        this.threshold = 50;
        
        this.init();
    }
    
    init() {
        this.render();
        this.attachEvents();
    }
    
    render() {
        const html = `
            <div class="swipe-gallery">
                <div class="swipe-track" id="swipeTrack">
                    ${this.images.map((img, index) => `
                        <div class="swipe-slide ${index === 0 ? 'active' : ''}" data-index="${index}">
                            <img src="${img.url}" alt="${img.alt || ''}" loading="${index === 0 ? 'eager' : 'lazy'}">
                        </div>
                    `).join('')}
                </div>
                <div class="swipe-pagination">
                    ${this.images.map((_, index) => `
                        <span class="swipe-dot ${index === 0 ? 'active' : ''}" data-index="${index}"></span>
                    `).join('')}
                </div>
                ${this.images.length > 1 ? `
                    <button class="swipe-prev" aria-label="Previous"><i class="bi bi-chevron-left"></i></button>
                    <button class="swipe-next" aria-label="Next"><i class="bi bi-chevron-right"></i></button>
                ` : ''}
            </div>
        `;
        this.container.innerHTML = html;
        
        this.track = document.getElementById('swipeTrack');
        this.slides = this.track.querySelectorAll('.swipe-slide');
        this.dots = this.container.querySelectorAll('.swipe-dot');
        this.prevBtn = this.container.querySelector('.swipe-prev');
        this.nextBtn = this.container.querySelector('.swipe-next');
    }
    
    attachEvents() {
        // Touch events
        this.track.addEventListener('touchstart', this.handleTouchStart.bind(this), { passive: true });
        this.track.addEventListener('touchmove', this.handleTouchMove.bind(this), { passive: false });
        this.track.addEventListener('touchend', this.handleTouchEnd.bind(this));
        
        // Mouse events для desktop
        this.track.addEventListener('mousedown', this.handleMouseDown.bind(this));
        this.track.addEventListener('mousemove', this.handleMouseMove.bind(this));
        this.track.addEventListener('mouseup', this.handleMouseUp.bind(this));
        this.track.addEventListener('mouseleave', this.handleMouseUp.bind(this));
        
        // Buttons
        if (this.prevBtn) this.prevBtn.addEventListener('click', () => this.prev());
        if (this.nextBtn) this.nextBtn.addEventListener('click', () => this.next());
        
        // Dots
        this.dots.forEach(dot => {
            dot.addEventListener('click', (e) => {
                const index = parseInt(e.target.dataset.index);
                this.goTo(index);
            });
        });
        
        // Keyboard
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
        const translate = -(this.currentIndex * 100) + (diff / this.container.offsetWidth) * 100;
        
        this.track.style.transform = `translateX(${translate}%)`;
        
        if (Math.abs(diff) > 10) {
    // ============================================

    class ProductCardSwipe {
        constructor(card) {
            this.card = card;
            this.container = card.querySelector('.product-card-images');
            this.track = card.querySelector('.product-card-images-track');
            this.images = card.querySelectorAll('.product-card-image');
            this.dots = card.querySelectorAll('.product-card-dot');
            
            this.currentIndex = 0;
            this.startX = 0;
            this.currentX = 0;
            this.isDragging = false;
            
            this.init();
        }

        init() {
            if (!this.container || this.images.length <= 1) return;

            // Touch events
            this.container.addEventListener('touchstart', this.handleTouchStart.bind(this), { passive: true });
            this.container.addEventListener('touchmove', this.handleTouchMove.bind(this), { passive: false });
            this.container.addEventListener('touchend', this.handleTouchEnd.bind(this));

            // Mouse events (для desktop)
            this.container.addEventListener('mousedown', this.handleMouseDown.bind(this));
            this.container.addEventListener('mousemove', this.handleMouseMove.bind(this));
            this.container.addEventListener('mouseup', this.handleMouseEnd.bind(this));
            this.container.addEventListener('mouseleave', this.handleMouseEnd.bind(this));

            // Prevent link click during swipe
            this.card.addEventListener('click', (e) => {
                if (this.isDragging) {
                    e.preventDefault();
                    e.stopPropagation();
                }
            }, true);
        }

        handleTouchStart(e) {
            this.startX = e.touches[0].clientX;
            this.currentX = this.startX;
            this.isDragging = false;
        }

        handleTouchMove(e) {
            if (!this.startX) return;

            this.currentX = e.touches[0].clientX;
            const diff = this.currentX - this.startX;

            // Если свайп больше 10px - это drag
            if (Math.abs(diff) > 10) {
                this.isDragging = true;
                e.preventDefault(); // Prevent scroll
                this.container.classList.add('swiping');

                // Визуальная обратная связь
                const offset = -(this.currentIndex * 100) + (diff / this.container.offsetWidth * 100);
                this.track.style.transform = `translateX(${offset}%)`;
            }
        }

        handleTouchEnd(e) {
            if (!this.isDragging) return;

            const diff = this.currentX - this.startX;
            const threshold = this.container.offsetWidth * 0.3; // 30% ширины

            this.container.classList.remove('swiping');

            if (Math.abs(diff) > threshold) {
                if (diff > 0 && this.currentIndex > 0) {
                    // Swipe right - previous
                    this.currentIndex--;
                } else if (diff < 0 && this.currentIndex < this.images.length - 1) {
                    // Swipe left - next
                    this.currentIndex++;
                }
            }

            this.updateSlide();
            this.startX = 0;
            this.currentX = 0;
            
            // Reset dragging после небольшой задержки
            setTimeout(() => {
                this.isDragging = false;
            }, 100);
        }

        handleMouseDown(e) {
            e.preventDefault();
            this.startX = e.clientX;
            this.currentX = this.startX;
            this.isDragging = false;
            this.container.style.cursor = 'grabbing';
        }

        handleMouseMove(e) {
            if (!this.startX) return;

            this.currentX = e.clientX;
            const diff = this.currentX - this.startX;

            if (Math.abs(diff) > 10) {
                this.isDragging = true;
                this.container.classList.add('swiping');

                const offset = -(this.currentIndex * 100) + (diff / this.container.offsetWidth * 100);
                this.track.style.transform = `translateX(${offset}%)`;
            }
        }

        handleMouseEnd(e) {
            if (!this.startX) return;

            const diff = this.currentX - this.startX;
            const threshold = this.container.offsetWidth * 0.3;

            this.container.classList.remove('swiping');
            this.container.style.cursor = 'grab';

            if (Math.abs(diff) > threshold) {
                if (diff > 0 && this.currentIndex > 0) {
                    this.currentIndex--;
                } else if (diff < 0 && this.currentIndex < this.images.length - 1) {
                    this.currentIndex++;
                }
            }

            this.updateSlide();
            this.startX = 0;
            this.currentX = 0;
            
            setTimeout(() => {
                this.isDragging = false;
            }, 100);
        }

        updateSlide() {
            this.track.style.transform = `translateX(-${this.currentIndex * 100}%)`;
            
            // Update dots
            this.dots.forEach((dot, index) => {
                dot.classList.toggle('active', index === this.currentIndex);
            });
        }
    }

    // ============================================
    // 2. SWIPE ГАЛЕРЕИ ТОВАРА (Product Page)
    // ============================================

    class ProductGallerySwipe {
        constructor(gallery) {
            this.gallery = gallery;
            this.track = gallery.querySelector('.product-gallery-track');
            this.slides = gallery.querySelectorAll('.product-gallery-slide');
            this.dots = gallery.querySelectorAll('.gallery-dot');
            
            this.currentIndex = 0;
            this.startX = 0;
            this.currentX = 0;
            this.isDragging = false;
            
            this.init();
        }

        init() {
            if (!this.gallery || this.slides.length <= 1) return;

            // Touch events
            this.gallery.addEventListener('touchstart', this.handleTouchStart.bind(this), { passive: true });
            this.gallery.addEventListener('touchmove', this.handleTouchMove.bind(this), { passive: false });
            this.gallery.addEventListener('touchend', this.handleTouchEnd.bind(this));

            // Mouse events
            this.gallery.addEventListener('mousedown', this.handleMouseDown.bind(this));
            this.gallery.addEventListener('mousemove', this.handleMouseMove.bind(this));
            this.gallery.addEventListener('mouseup', this.handleMouseEnd.bind(this));
            this.gallery.addEventListener('mouseleave', this.handleMouseEnd.bind(this));

            // Keyboard navigation
            document.addEventListener('keydown', this.handleKeyboard.bind(this));

            // Dot click
            this.dots.forEach((dot, index) => {
                dot.addEventListener('click', () => {
                    this.goToSlide(index);
                });
            });
        }

        handleTouchStart(e) {
            this.startX = e.touches[0].clientX;
            this.currentX = this.startX;
            this.isDragging = false;
        }

        handleTouchMove(e) {
            if (!this.startX) return;

            this.currentX = e.touches[0].clientX;
            const diff = this.currentX - this.startX;

            if (Math.abs(diff) > 10) {
                this.isDragging = true;
                e.preventDefault();
                this.gallery.classList.add('swiping');

                const offset = -(this.currentIndex * 100) + (diff / this.gallery.offsetWidth * 100);
                this.track.style.transform = `translateX(${offset}%)`;
            }
        }

        handleTouchEnd(e) {
            if (!this.isDragging) return;

            const diff = this.currentX - this.startX;
            const threshold = this.gallery.offsetWidth * 0.25;

            this.gallery.classList.remove('swiping');

            if (Math.abs(diff) > threshold) {
                if (diff > 0 && this.currentIndex > 0) {
                    this.currentIndex--;
                } else if (diff < 0 && this.currentIndex < this.slides.length - 1) {
                    this.currentIndex++;
                }
            }

            this.updateSlide();
            this.startX = 0;
            this.currentX = 0;
            
            setTimeout(() => {
                this.isDragging = false;
            }, 100);
        }

        handleMouseDown(e) {
            e.preventDefault();
            this.startX = e.clientX;
            this.currentX = this.startX;
            this.isDragging = false;
        }

        handleMouseMove(e) {
            if (!this.startX) return;

            this.currentX = e.clientX;
            const diff = this.currentX - this.startX;

            if (Math.abs(diff) > 10) {
                this.isDragging = true;
                this.gallery.classList.add('swiping');

                const offset = -(this.currentIndex * 100) + (diff / this.gallery.offsetWidth * 100);
                this.track.style.transform = `translateX(${offset}%)`;
            }
        }

        handleMouseEnd(e) {
            if (!this.startX) return;

            const diff = this.currentX - this.startX;
            const threshold = this.gallery.offsetWidth * 0.25;

            this.gallery.classList.remove('swiping');

            if (Math.abs(diff) > threshold) {
                if (diff > 0 && this.currentIndex > 0) {
                    this.currentIndex--;
                } else if (diff < 0 && this.currentIndex < this.slides.length - 1) {
                    this.currentIndex++;
                }
            }

            this.updateSlide();
            this.startX = 0;
            this.currentX = 0;
            
            setTimeout(() => {
                this.isDragging = false;
            }, 100);
        }

        handleKeyboard(e) {
            if (e.key === 'ArrowLeft' && this.currentIndex > 0) {
                this.currentIndex--;
                this.updateSlide();
            } else if (e.key === 'ArrowRight' && this.currentIndex < this.slides.length - 1) {
                this.currentIndex++;
                this.updateSlide();
            }
        }

        goToSlide(index) {
            this.currentIndex = index;
            this.updateSlide();
        }

        updateSlide() {
            this.track.style.transform = `translateX(-${this.currentIndex * 100}%)`;
            
            // Update dots
            this.dots.forEach((dot, index) => {
                dot.classList.toggle('active', index === this.currentIndex);
            });
        }
    }

    // ============================================
    // 3. ИНИЦИАЛИЗАЦИЯ
    // ============================================

    function initSwipe() {
        // Инициализация карточек товаров
        const productCards = document.querySelectorAll('.product-card');
        productCards.forEach(card => {
            if (card.querySelector('.product-card-images-track')) {
                new ProductCardSwipe(card);
            }
        });

        // Инициализация галереи товара
        const gallery = document.querySelector('.product-gallery-swipe');
        if (gallery) {
            new ProductGallerySwipe(gallery);
        }
    }

    // Инициализация при загрузке
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSwipe);
    } else {
        initSwipe();
    }

    // Экспорт для повторной инициализации (например, после AJAX)
    window.initProductSwipe = initSwipe;

})();
