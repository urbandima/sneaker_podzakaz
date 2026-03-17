/**
 * Mobile Menu functionality
 * Handles burger menu, overlay, and mobile navigation
 */

document.addEventListener('DOMContentLoaded', function() {
    initializeMobileMenu();
});

function initializeMobileMenu() {
    const burgerButton = document.querySelector('.burger-menu');
    const mobileMenu = document.querySelector('.mobile-menu');
    const overlay = document.querySelector('.menu-overlay');
    const closeButtons = document.querySelectorAll('.mobile-menu-close');
    
    if (!burgerButton || !mobileMenu) return;
    
    // Toggle mobile menu
    burgerButton.addEventListener('click', function(e) {
        e.preventDefault();
        toggleMobileMenu();
    });
    
    // Close menu when clicking overlay
    if (overlay) {
        overlay.addEventListener('click', closeMobileMenu);
    }
    
    // Close menu when clicking close buttons
    closeButtons.forEach(button => {
        button.addEventListener('click', closeMobileMenu);
    });
    
    // Close menu on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && mobileMenu.classList.contains('active')) {
            closeMobileMenu();
        }
    });
    
    // Handle menu dropdowns
    const dropdownToggles = document.querySelectorAll('.mobile-menu .dropdown-toggle');
    dropdownToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            const parent = this.parentElement;
            const submenu = parent.querySelector('.submenu');
            
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
    const mobileMenu = document.querySelector('.mobile-menu');
    const overlay = document.querySelector('.menu-overlay');
    const body = document.body;
    
    if (mobileMenu.classList.contains('active')) {
        closeMobileMenu();
    } else {
        openMobileMenu();
    }
}

function openMobileMenu() {
    const mobileMenu = document.querySelector('.mobile-menu');
    const overlay = document.querySelector('.menu-overlay');
    const body = document.body;
    
    mobileMenu.classList.add('active');
    overlay.classList.add('active');
    body.style.overflow = 'hidden';
    
    // Focus trap for accessibility
    trapFocus(mobileMenu);
}

function closeMobileMenu() {
    const mobileMenu = document.querySelector('.mobile-menu');
    const overlay = document.querySelector('.menu-overlay');
    const body = document.body;
    
    mobileMenu.classList.remove('active');
    overlay.classList.remove('active');
    body.style.overflow = '';
    
    // Remove focus trap
    removeFocusTrap(mobileMenu);
}

// Focus management for accessibility
function trapFocus(element) {
    const focusableElements = element.querySelectorAll(
        'a, button, input, textarea, select, [tabindex]:not([tabindex="-1"])'
    );
    const firstFocusable = focusableElements[0];
    const lastFocusable = focusableElements[focusableElements.length - 1];
    
    element.addEventListener('keydown', function(e) {
        if (e.key === 'Tab') {
            if (e.shiftKey) {
                if (document.activeElement === firstFocusable) {
                    e.preventDefault();
                    lastFocusable.focus();
                }
            } else {
                if (document.activeElement === lastFocusable) {
                    e.preventDefault();
                    firstFocusable.focus();
                }
            }
        }
    });
    
    // Set initial focus
    if (firstFocusable) {
        firstFocusable.focus();
    }
}

function removeFocusTrap(element) {
    // Remove event listener
    element.removeEventListener('keydown', trapFocus);
}

// Export functions for global access
window.toggleMobileMenu = toggleMobileMenu;
window.closeMobileMenu = closeMobileMenu;
window.openMobileMenu = openMobileMenu;
