/**
 * Header Enhancements
 * Улучшения UX для ecom-header
 */

(function() {
    'use strict';
    
    document.addEventListener('DOMContentLoaded', function() {
        initScrollEffects();
        initBurgerAnimation();
        initSearchFocus();
        initBadgeAnimations();
    });
    
    /**
     * Эффект при скролле - усиленная тень для хедера
     */
    function initScrollEffects() {
        const header = document.querySelector('.main-header');
        if (!header) return;
        
        let lastScroll = 0;
        let ticking = false;
        
        window.addEventListener('scroll', function() {
            lastScroll = window.scrollY;
            
            if (!ticking) {
                window.requestAnimationFrame(function() {
                    if (lastScroll > 50) {
                        header.classList.add('scrolled');
                    } else {
                        header.classList.remove('scrolled');
                    }
                    ticking = false;
                });
                
                ticking = true;
            }
        });
    }
    
    /**
     * Анимация бургер-меню
     */
    function initBurgerAnimation() {
        const burger = document.getElementById('menuBurger');
        const mobileMenu = document.getElementById('mobileMenu');
        
        if (!burger || !mobileMenu) return;
        
        // Toggle класса active для анимации
        burger.addEventListener('click', function() {
            this.classList.toggle('active');
        });
        
        // Убираем active при закрытии меню
        const closeBtn = document.getElementById('menuClose');
        const overlay = document.getElementById('menuOverlay');
        
        const removeBurgerActive = function() {
            burger.classList.remove('active');
        };
        
        if (closeBtn) {
            closeBtn.addEventListener('click', removeBurgerActive);
        }
        
        if (overlay) {
            overlay.addEventListener('click', removeBurgerActive);
        }
    }
    
    /**
     * Улучшенный фокус для поиска
     */
    function initSearchFocus() {
        const searchInput = document.getElementById('headerSearch');
        if (!searchInput) return;
        
        // Фокус по клику на иконку
        const searchIcon = document.querySelector('.search-box .bi-search');
        if (searchIcon) {
            searchIcon.addEventListener('click', function() {
                searchInput.focus();
            });
        }
        
        // Очистка по Escape
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                this.value = '';
                this.blur();
            }
        });
        
        // Подсветка при вводе
        searchInput.addEventListener('input', function() {
            const searchBox = this.closest('.search-box');
            if (searchBox) {
                if (this.value.length > 0) {
                    searchBox.classList.add('has-value');
                } else {
                    searchBox.classList.remove('has-value');
                }
            }
        });
    }
    
    /**
     * Анимация badge при обновлении счетчиков
     */
    function initBadgeAnimations() {
        // Наблюдаем за изменениями в badge
        const observeBadge = function(badgeId) {
            const badge = document.getElementById(badgeId);
            if (!badge) return;
            
            // MutationObserver для отслеживания изменений текста
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'childList' || mutation.type === 'characterData') {
                        // Добавляем класс для анимации
                        badge.classList.add('updated');
                        
                        // Убираем через 300ms
                        setTimeout(function() {
                            badge.classList.remove('updated');
                        }, 300);
                    }
                });
            });
            
            observer.observe(badge, {
                characterData: true,
                childList: true,
                subtree: true
            });
        };
        
        observeBadge('favCount');
        observeBadge('cartCount');
    }
    
    /**
     * Экспортируем функции для глобального доступа
     */
    window.HeaderEnhancements = {
        updateBadge: function(badgeId, count) {
            const badge = document.getElementById(badgeId);
            if (badge) {
                badge.textContent = count;
                badge.style.display = count > 0 ? 'flex' : 'none';
            }
        }
    };
    
})();
