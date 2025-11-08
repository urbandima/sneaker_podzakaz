/**
 * Mobile Menu Handler
 * ИСПРАВЛЕНО (Проблема #20): Вынесено в отдельный модуль
 */

(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        // Элементы меню
        const menuBurger = document.getElementById('menuBurger');
        const menuClose = document.getElementById('menuClose');
        const mobileMenu = document.getElementById('mobileMenu');
        const menuOverlay = document.getElementById('menuOverlay');

        // Проверяем наличие всех элементов
        if (!menuBurger) {
            console.warn('Mobile menu: menuBurger not found');
            return;
        }

        /**
         * Открыть мобильное меню
         */
        function openMobileMenu() {
            if (mobileMenu) {
                mobileMenu.classList.add('active');
            }
            if (menuOverlay) {
                menuOverlay.classList.add('active');
            }
            document.body.style.overflow = 'hidden';
        }

        /**
         * Закрыть мобильное меню
         */
        function closeMobileMenu() {
            if (mobileMenu) {
                mobileMenu.classList.remove('active');
            }
            if (menuOverlay) {
                menuOverlay.classList.remove('active');
            }
            document.body.style.overflow = '';
        }

        // Открытие меню
        menuBurger.addEventListener('click', openMobileMenu);

        // Закрытие через кнопку
        if (menuClose) {
            menuClose.addEventListener('click', closeMobileMenu);
        }

        // Закрытие через overlay
        if (menuOverlay) {
            menuOverlay.addEventListener('click', closeMobileMenu);
        }

        // Закрытие по Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && mobileMenu?.classList.contains('active')) {
                closeMobileMenu();
            }
        });

        // Mobile submenu toggle (accordion)
        document.querySelectorAll('.mobile-nav-toggle').forEach(toggle => {
            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                
                const parent = this.closest('.mobile-nav-item');
                const submenu = parent?.querySelector('.mobile-submenu');
                const chevron = this.querySelector('.chevron');
                
                if (!parent || !submenu) return;
                
                // Toggle current submenu
                if (parent.classList.contains('active')) {
                    parent.classList.remove('active');
                    submenu.style.maxHeight = '0';
                    if (chevron) chevron.style.transform = 'rotate(0deg)';
                } else {
                    // Close other submenus
                    document.querySelectorAll('.mobile-nav-item.active').forEach(item => {
                        item.classList.remove('active');
                        const sub = item.querySelector('.mobile-submenu');
                        if (sub) sub.style.maxHeight = '0';
                        const chev = item.querySelector('.chevron');
                        if (chev) chev.style.transform = 'rotate(0deg)';
                    });
                    
                    // Open current submenu
                    parent.classList.add('active');
                    submenu.style.maxHeight = submenu.scrollHeight + 'px';
                    if (chevron) chevron.style.transform = 'rotate(180deg)';
                }
            });
        });

        // Экспортируем функции для глобального доступа
        window.MobileMenu = {
            open: openMobileMenu,
            close: closeMobileMenu
        };
    });

})();
