/**
 * Mobile Menu Logic
 * Управление мобильным меню и его состоянием
 */

document.addEventListener('DOMContentLoaded', function() {
    const menuBurger = document.getElementById('menuBurger');
    const menuClose = document.getElementById('menuClose');
    const mobileMenu = document.getElementById('mobileMenu');
    const menuOverlay = document.getElementById('menuOverlay');
    
    // Синхронизация счетчиков
    function syncBadgeCounts() {
        // Избранное
        const favCount = document.getElementById('favCount');
        const mobileFavCount = document.getElementById('mobileFavCount');
        if (favCount && mobileFavCount) {
            mobileFavCount.textContent = favCount.textContent;
            mobileFavCount.style.display = favCount.style.display;
        }
        
        // Корзина
        const cartCount = document.getElementById('cartCount');
        const mobileCartCount = document.getElementById('mobileCartCount');
        if (cartCount && mobileCartCount) {
            mobileCartCount.textContent = cartCount.textContent;
            mobileCartCount.style.display = cartCount.style.display;
        }
    }
    
    // Открытие меню
    if (menuBurger) {
        menuBurger.addEventListener('click', function() {
            if (mobileMenu) mobileMenu.classList.add('active');
            if (menuOverlay) menuOverlay.classList.add('active');
            document.body.classList.add('mobile-menu-open');
            syncBadgeCounts();
        });
    }
    
    // Закрытие меню
    function closeMenu() {
        if (mobileMenu) mobileMenu.classList.remove('active');
        if (menuOverlay) menuOverlay.classList.remove('active');
        document.body.classList.remove('mobile-menu-open');
    }
    
    if (menuClose) {
        menuClose.addEventListener('click', closeMenu);
    }
    
    if (menuOverlay) {
        menuOverlay.addEventListener('click', closeMenu);
    }
    
    // Закрытие по Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && mobileMenu && mobileMenu.classList.contains('active')) {
            closeMenu();
        }
    });
    
    // Аккордеон подменю (УЛУЧШЕННОЕ ДЛЯ ПРОДАКШЕНА)
    const navToggles = document.querySelectorAll('.mobile-nav-toggle');
    navToggles.forEach(function(toggle) {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            const parent = this.closest('.mobile-nav-item');
            const wasOpen = parent.classList.contains('open');
            
            // Закрываем все другие подменю
            document.querySelectorAll('.mobile-nav-item.has-submenu').forEach(function(item) {
                if (item !== parent) {
                    item.classList.remove('open');
                    item.classList.remove('active');
                }
            });
            
            // Переключаем текущее
            if (wasOpen) {
                parent.classList.remove('open');
                parent.classList.remove('active');
            } else {
                parent.classList.add('open');
                parent.classList.add('active');
                
                // Плавная прокрутка к открытому пункту
                setTimeout(function() {
                    const submenu = parent.querySelector('.mobile-submenu');
                    if (submenu && submenu.scrollHeight > 0) {
                        parent.scrollIntoView({ 
                            behavior: 'smooth', 
                            block: 'nearest' 
                        });
                    }
                }, 100);
            }
        });
    });
    
    // Наблюдаем за изменениями счетчиков
    const observeBadge = function(headerId, mobileId) {
        const headerBadge = document.getElementById(headerId);
        const mobileBadge = document.getElementById(mobileId);
        
        if (!headerBadge || !mobileBadge) return;
        
        const observer = new MutationObserver(function() {
            mobileBadge.textContent = headerBadge.textContent;
            mobileBadge.style.display = headerBadge.style.display;
        });
        
        observer.observe(headerBadge, {
            characterData: true,
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ['style']
        });
    };
    
    observeBadge('favCount', 'mobileFavCount');
    observeBadge('cartCount', 'mobileCartCount');
    
    // Загрузка брендов в мобильное меню
    function loadMobileBrands() {
        const mobileBrandsGrid = document.getElementById('mobileBrandsGrid');
        if (!mobileBrandsGrid) return;
        
        fetch('/catalog/get-brands')
            .then(r => r.json())
            .then(brands => {
                if (!brands || brands.length === 0) {
                    mobileBrandsGrid.innerHTML = '<div style="text-align:center;padding:1rem;color:#999;">Бренды не найдены</div>';
                    return;
                }
                
                // Показываем только топ-10 брендов
                const topBrands = brands.slice(0, 10);
                
                mobileBrandsGrid.innerHTML = topBrands.map(brand => `
                    <a href="/catalog/brand/${brand.slug}" class="mobile-brand-link">
                        <span class="brand-name">${brand.name}</span>
                        <span class="brand-count">${brand.products_count}</span>
                    </a>
                `).join('');
            })
            .catch(error => {
                console.error('Ошибка загрузки брендов:', error);
                mobileBrandsGrid.innerHTML = '<div style="text-align:center;padding:1rem;color:#ef4444;">Ошибка загрузки</div>';
            });
    }
    
    // Загружаем бренды при первом открытии меню
    let brandsLoaded = false;
    if (menuBurger) {
        menuBurger.addEventListener('click', function() {
            if (!brandsLoaded) {
                loadMobileBrands();
                brandsLoaded = true;
            }
        });
    }
    
    // Экспорт функций
    window.MobileMenu = {
        close: closeMenu,
        syncBadges: syncBadgeCounts,
        loadBrands: loadMobileBrands
    };
});
