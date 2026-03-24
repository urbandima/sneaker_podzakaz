document.addEventListener('DOMContentLoaded', function() {
    const dropdownToggles = document.querySelectorAll('.navbar-nav .nav-link.dropdown-toggle');

    dropdownToggles.forEach(function(toggle) {
        toggle.removeAttribute('data-bs-toggle');

        const navItem = toggle.closest('li');
        const menu = navItem.querySelector('.dropdown-menu');

        if (!menu) return;

        let closeTimeout;

        function openMenu() {
            clearTimeout(closeTimeout);

            document.querySelectorAll('.navbar-nav .dropdown-menu').forEach(function(otherMenu) {
                if (otherMenu !== menu) {
                    otherMenu.classList.remove('show');
                    otherMenu.style.display = '';
                    otherMenu.style.opacity = '';
                    otherMenu.style.visibility = '';
                    otherMenu.style.pointerEvents = '';
                    otherMenu.style.transform = '';
                }
            });

            menu.classList.add('show');
            menu.style.display = 'block';
            menu.style.opacity = '1';
            menu.style.visibility = 'visible';
            menu.style.pointerEvents = 'auto';
            menu.style.transform = 'translateY(0)';
            toggle.setAttribute('aria-expanded', 'true');
        }

        function closeMenu() {
            menu.classList.remove('show');
            menu.style.display = '';
            menu.style.opacity = '';
            menu.style.visibility = '';
            menu.style.pointerEvents = '';
            menu.style.transform = '';
            toggle.setAttribute('aria-expanded', 'false');
        }

        navItem.addEventListener('mouseenter', openMenu);
        navItem.addEventListener('mouseleave', function() {
            closeTimeout = setTimeout(closeMenu, 100);
        });

        menu.addEventListener('mouseenter', function() {
            clearTimeout(closeTimeout);
        });

        menu.addEventListener('mouseleave', function() {
            closeTimeout = setTimeout(closeMenu, 100);
        });
    });
});
