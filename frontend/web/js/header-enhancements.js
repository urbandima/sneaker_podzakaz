/**
 * Header Enhancements
 * Улучшения UX для ecom-header.
 *
 * NOTE: Scroll effect for .main-header is handled in app.js.
 *       This file provides burger animation, search focus, and badge animations.
 */

(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        initBurgerAnimation();
        initSearchFocus();
        initBadgeAnimations();
    });

    /**
     * Анимация бургер-меню
     */
    function initBurgerAnimation() {
        var burger = document.getElementById('menuBurger');
        var mobileMenu = document.getElementById('mobileMenu');

        if (!burger || !mobileMenu) return;

        burger.addEventListener('click', function () {
            this.classList.toggle('active');
        });

        var closeBtn = document.getElementById('menuClose');
        var overlay = document.getElementById('menuOverlay');

        var removeBurgerActive = function () {
            burger.classList.remove('active');
        };

        if (closeBtn) closeBtn.addEventListener('click', removeBurgerActive);
        if (overlay) overlay.addEventListener('click', removeBurgerActive);
    }

    /**
     * Улучшенный фокус для поиска
     */
    function initSearchFocus() {
        var searchInput = document.getElementById('headerSearch');
        if (!searchInput) return;

        var searchIcon = document.querySelector('.search-box .bi-search');
        if (searchIcon) {
            searchIcon.addEventListener('click', function () {
                searchInput.focus();
            });
        }

        searchInput.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                this.value = '';
                this.blur();
            }
        });

        searchInput.addEventListener('input', function () {
            var searchBox = this.closest('.search-box');
            if (searchBox) {
                searchBox.classList.toggle('has-value', this.value.length > 0);
            }
        });
    }

    /**
     * Анимация badge при обновлении счетчиков
     */
    function initBadgeAnimations() {
        var observeBadge = function (badgeId) {
            var badge = document.getElementById(badgeId);
            if (!badge) return;

            var observer = new MutationObserver(function () {
                badge.classList.add('updated');
                setTimeout(function () { badge.classList.remove('updated'); }, 300);
            });

            observer.observe(badge, { characterData: true, childList: true, subtree: true });
        };

        observeBadge('favCount');
        observeBadge('cartCount');
    }

    window.HeaderEnhancements = {
        updateBadge: function (badgeId, count) {
            var badge = document.getElementById(badgeId);
            if (badge) {
                badge.textContent = count;
                badge.style.display = count > 0 ? 'flex' : 'none';
            }
        }
    };
})();
