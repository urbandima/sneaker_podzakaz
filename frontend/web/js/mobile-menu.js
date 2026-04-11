/**
 * Mobile Menu functionality
 * Handles burger menu, overlay, and mobile navigation.
 * Uses SH.* utilities from utils.js for scroll lock and focus trap.
 */

document.addEventListener('DOMContentLoaded', function () {
    initializeMobileMenu();
});

function initializeMobileMenu() {
    var burgerButton = document.querySelector('.burger-menu');
    var mobileMenu = document.querySelector('.mobile-menu');
    var overlay = document.querySelector('.menu-overlay');
    var closeButtons = document.querySelectorAll('.mobile-menu-close');

    if (!burgerButton || !mobileMenu) return;

    burgerButton.addEventListener('click', function (e) {
        e.preventDefault();
        toggleMobileMenu();
    });

    if (overlay) {
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

    // Handle menu dropdowns
    var dropdownToggles = document.querySelectorAll('.mobile-menu .dropdown-toggle');
    dropdownToggles.forEach(function (toggle) {
        toggle.addEventListener('click', function (e) {
            e.preventDefault();
            var parent = this.parentElement;
            var submenu = parent.querySelector('.submenu');

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
    if (mobileMenu.classList.contains('active')) {
        closeMobileMenu();
    } else {
        openMobileMenu();
    }
}

function openMobileMenu() {
    var mobileMenu = document.querySelector('.mobile-menu');
    var overlay = document.querySelector('.menu-overlay');

    SH.openModal(mobileMenu);
    if (overlay) SH.openModal(overlay);
    SH.trapFocus(mobileMenu);
}

function closeMobileMenu() {
    var mobileMenu = document.querySelector('.mobile-menu');
    var overlay = document.querySelector('.menu-overlay');

    SH.closeModal(mobileMenu);
    if (overlay) SH.closeModal(overlay);
    SH.releaseFocus(mobileMenu);
}

window.toggleMobileMenu = toggleMobileMenu;
window.closeMobileMenu = closeMobileMenu;
window.openMobileMenu = openMobileMenu;
