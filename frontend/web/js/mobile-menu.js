/**
 * Mobile Menu functionality
 * Handles burger menu, overlay, and mobile navigation.
 * Uses SH.* utilities from utils.js for scroll lock and focus trap.
 */

document.addEventListener('DOMContentLoaded', function () {
    initializeMobileMenu();
});

function initializeMobileMenu() {
    // Support multiple button selectors across different layouts
    var burgerButton = document.querySelector('.mobile-menu-toggle, .burger-menu, .menu-burger');
    var mobileMenu = document.querySelector('.mobile-menu');
    // Support both overlay class names used across layouts
    var overlay = document.querySelector('.menu-overlay, .mobile-menu-overlay');
    var closeButtons = document.querySelectorAll('.mobile-menu-close, .close-menu, .menu-close');

    if (!mobileMenu) return;

    // Attach click handler only if button has no inline onclick
    if (burgerButton && !burgerButton.getAttribute('onclick')) {
        burgerButton.addEventListener('click', function (e) {
            e.preventDefault();
            toggleMobileMenu();
        });
    }

    if (overlay && !overlay.getAttribute('onclick')) {
        overlay.addEventListener('click', closeMobileMenu);
    }

    closeButtons.forEach(function (button) {
        button.addEventListener('click', closeMobileMenu);
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && mobileMenu.classList.contains('active')) {
            closeMobileMenu();
        }
    });

    // Handle menu dropdowns (old .dropdown-toggle и новый .mobile-nav-toggle)
    var dropdownToggles = document.querySelectorAll('.mobile-menu .dropdown-toggle, .mobile-menu .mobile-nav-toggle');
    dropdownToggles.forEach(function (toggle) {
        toggle.addEventListener('click', function (e) {
            e.preventDefault();
            var parent = this.closest('.has-submenu') || this.parentElement;
            var submenu = parent.querySelector('.submenu, .mobile-submenu');

            if (submenu) {
                parent.classList.toggle('open');
                submenu.style.maxHeight = parent.classList.contains('open')
                    ? submenu.scrollHeight + 'px'
                    : '0';
            }
        });
    });
}

function toggleMobileMenu() {
    var mobileMenu = document.querySelector('.mobile-menu');
    if (!mobileMenu) return;
    if (mobileMenu.classList.contains('active') || mobileMenu.classList.contains('open')) {
        closeMobileMenu();
    } else {
        openMobileMenu();
    }
}

function openMobileMenu() {
    var mobileMenu = document.querySelector('.mobile-menu');
    var overlay = document.querySelector('.menu-overlay, .mobile-menu-overlay');
    var burger = document.querySelector('.mobile-menu-toggle, .burger-menu, .menu-burger');

    SH.openModal(mobileMenu);
    if (overlay) {
        // .mobile-menu-overlay uses 'open' class, .menu-overlay uses 'active'
        var overlayClass = overlay.classList.contains('mobile-menu-overlay') ? 'open' : 'active';
        SH.openModal(overlay, overlayClass);
    }
    if (burger) burger.classList.add('active');
    SH.trapFocus(mobileMenu);
}

function closeMobileMenu() {
    var mobileMenu = document.querySelector('.mobile-menu');
    var overlay = document.querySelector('.menu-overlay, .mobile-menu-overlay');
    var burger = document.querySelector('.mobile-menu-toggle, .burger-menu, .menu-burger');

    SH.closeModal(mobileMenu);
    if (overlay) {
        var overlayClass = overlay.classList.contains('mobile-menu-overlay') ? 'open' : 'active';
        SH.closeModal(overlay, overlayClass);
    }
    if (burger) burger.classList.remove('active');
    SH.releaseFocus(mobileMenu);
}

window.toggleMobileMenu = toggleMobileMenu;
window.closeMobileMenu = closeMobileMenu;
window.openMobileMenu = openMobileMenu;
