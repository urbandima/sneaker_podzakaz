<?php

use yii\helpers\Html;
use app\frontend\assets\AppAsset;

AppAsset::register($this);

$company = Yii::$app->settings->getCompany();
?>

<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>" data-theme="light">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?= Html::csrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
    
    <?php // JSON-LD Schema.org разметка ?>
    <?php if (!empty($this->params['jsonLdSchemas'])): ?>
        <?php foreach ($this->params['jsonLdSchemas'] as $schema): ?>
            <script type="application/ld+json"><?= $schema ?></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <?php // hreflang для SEO ?>
    <link rel="alternate" hreflang="ru-BY" href="<?= Yii::$app->request->absoluteUrl ?>">
    <link rel="alternate" hreflang="x-default" href="<?= Yii::$app->request->absoluteUrl ?>">
    
    <?php // Bootstrap Icons CSS ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <?php // DNS prefetch / Preconnect ?>
    <link rel="dns-prefetch" href="//fonts.googleapis.com">
    <link rel="dns-prefetch" href="//cdn.jsdelivr.net">
    <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
</head>
<body>
<?php $this->beginBody() ?>

<!-- Skip to main content (a11y) -->
<a href="#main-content" class="skip-link">Перейти к основному содержимому</a>

<!-- Header -->
<header class="main-header">
    <div class="header-content">
        <!-- Logo -->
        <a href="/" class="logo">
            <img src="/images/logo.png" alt="<?= Html::encode($company['name'] ?? 'СНИКЕРХЭД') ?>">
        </a>
        
        <!-- Navigation -->
        <nav class="main-nav">
            <!-- Burger — desktop opens mega-menu, mobile opens drawer -->
            <button class="nav-burger" id="navBurger" onclick="toggleBurgerMenu(event)" aria-label="Навигационное меню" aria-expanded="false" aria-controls="megaMenu mobileMenu">
                <span class="nav-burger-lines">
                    <span></span><span></span><span></span>
                </span>
            </button>
            <ul class="nav-menu">
                <li><a href="/catalog">Каталог</a></li>
                <li><a href="/brands">Бренды</a></li>
                <li><a href="/sale" class="text-sale">Скидки</a></li>
                <li><a href="/about">О нас</a></li>
                <li><a href="/contacts">Контакты</a></li>
            </ul>
        </nav>

        <!-- Actions -->
        <div class="header-actions">
            <button class="btn-search" onclick="openSearch()" aria-label="Поиск товаров" aria-haspopup="dialog">
                <i class="bi bi-search" aria-hidden="true"></i>
            </button>

            <a href="/account/wishlist" class="btn-wishlist" aria-label="Избранное">
                <i class="bi bi-heart" aria-hidden="true"></i>
                <span class="wishlist-counter header-badge d-none" role="status" aria-live="polite">0</span>
            </a>

            <a href="/cart" class="btn-cart" aria-label="Корзина" onclick="event.preventDefault(); openCartDrawer();">
                <i class="bi bi-cart3" aria-hidden="true"></i>
                <span id="cartCount" class="cart-counter header-badge d-none" role="status" aria-live="polite">0</span>
            </a>

            <a href="/account" class="btn-account" aria-label="Личный кабинет">
                <i class="bi bi-person" aria-hidden="true"></i>
            </a>
        </div>
    </div>
</header>

<!-- Main Content -->
<main id="main-content">
    <?= $content ?>
</main>

<!-- Footer -->
<?= $this->render('//partials/footer') ?>

<!-- Desktop Mega Menu -->
<div class="mega-menu" id="megaMenu" role="dialog" aria-label="Меню навигации">
    <div class="mega-menu-inner">
        <div class="mega-container">
            <div class="mega-grid">
                <div class="mega-col">
                    <p class="mega-col-title">Каталог</p>
                    <ul>
                        <li><a href="/catalog"><i class="bi bi-grid-3x3-gap"></i>Все товары</a></li>
                        <li><a href="/catalog?sort=new"><i class="bi bi-stars"></i>Новинки</a></li>
                        <li><a href="/sale"><i class="bi bi-tag-fill text-sale"></i>Скидки</a></li>
                        <li><a href="/catalog?sort=popular"><i class="bi bi-fire"></i>Популярное</a></li>
                    </ul>
                </div>
                <div class="mega-col">
                    <p class="mega-col-title">Бренды</p>
                    <ul>
                        <li><a href="/brands/nike"><i class="bi bi-circle-fill mega-dot"></i>Nike</a></li>
                        <li><a href="/brands/adidas"><i class="bi bi-circle-fill mega-dot"></i>Adidas</a></li>
                        <li><a href="/brands/jordan"><i class="bi bi-circle-fill mega-dot"></i>Jordan</a></li>
                        <li><a href="/brands/new-balance"><i class="bi bi-circle-fill mega-dot"></i>New Balance</a></li>
                        <li><a href="/brands"><i class="bi bi-arrow-right-circle"></i>Все бренды</a></li>
                    </ul>
                </div>
                <div class="mega-col">
                    <p class="mega-col-title">Информация</p>
                    <ul>
                        <li><a href="/about"><i class="bi bi-building"></i>О компании</a></li>
                        <li><a href="/contacts"><i class="bi bi-telephone"></i>Контакты</a></li>
                        <li><a href="/account"><i class="bi bi-person-circle"></i>Личный кабинет</a></li>
                        <li><a href="/account/orders"><i class="bi bi-box-seam"></i>Мои заказы</a></li>
                    </ul>
                </div>
                <div class="mega-col mega-col-promo">
                    <div class="mega-promo-card">
                        <span class="mega-promo-badge">🔥 Горячее</span>
                        <p class="mega-promo-title">Скидки до 50%</p>
                        <p class="mega-promo-sub">Лимитированные предложения</p>
                        <a href="/sale" class="mega-promo-btn">Смотреть акции</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="mega-overlay" id="megaOverlay" onclick="closeBurgerMenu()"></div>

<!-- Mobile Menu Backdrop -->
<div class="menu-overlay" id="menuOverlay" onclick="closeBurgerMenu()"></div>

<!-- Mobile Menu -->
<div class="mobile-menu" id="mobileMenu">
    <div class="mobile-menu-header">
        <a href="/" class="mobile-menu-logo">
            <img src="/images/logo.png" alt="СНИКЕРХЭД">
        </a>
        <button class="mobile-menu-close" onclick="closeBurgerMenu()" aria-label="Закрыть меню">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
    <nav class="mobile-nav">
        <a href="/catalog" class="mobile-nav-link" onclick="closeBurgerMenu()">
            <i class="bi bi-grid-3x3-gap"></i><span>Каталог</span>
            <i class="bi bi-chevron-right mobile-nav-arrow"></i>
        </a>
        <a href="/brands" class="mobile-nav-link" onclick="closeBurgerMenu()">
            <i class="bi bi-award"></i><span>Бренды</span>
            <i class="bi bi-chevron-right mobile-nav-arrow"></i>
        </a>
        <a href="/sale" class="mobile-nav-link text-sale" onclick="closeBurgerMenu()">
            <i class="bi bi-tag-fill"></i><span>Скидки</span>
            <i class="bi bi-chevron-right mobile-nav-arrow"></i>
        </a>
        <a href="/about" class="mobile-nav-link" onclick="closeBurgerMenu()">
            <i class="bi bi-building"></i><span>О нас</span>
            <i class="bi bi-chevron-right mobile-nav-arrow"></i>
        </a>
        <a href="/contacts" class="mobile-nav-link" onclick="closeBurgerMenu()">
            <i class="bi bi-telephone"></i><span>Контакты</span>
            <i class="bi bi-chevron-right mobile-nav-arrow"></i>
        </a>
        <a href="/account" class="mobile-nav-link" onclick="closeBurgerMenu()">
            <i class="bi bi-person-circle"></i><span>Личный кабинет</span>
            <i class="bi bi-chevron-right mobile-nav-arrow"></i>
        </a>
    </nav>
    <div class="mobile-menu-footer">
        <div class="mobile-menu-contacts">
            <a href="tel:+375291234567"><i class="bi bi-telephone"></i>+375 (29) 123-45-67</a>
            <a href="https://t.me" target="_blank"><i class="bi bi-telegram"></i>Telegram</a>
        </div>
    </div>
</div>

<!-- Cart Drawer -->
<div class="cart-drawer-overlay" id="cartDrawerOverlay" onclick="closeCartDrawer()"></div>
<div class="cart-drawer" id="cartDrawer">
    <div class="cart-drawer-header">
        <h3><i class="bi bi-cart3"></i> Корзина</h3>
        <button class="cart-drawer-close" onclick="closeCartDrawer()" aria-label="Закрыть">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
    <div class="cart-drawer-items" id="cartDrawerItems">
        <div class="cart-loading"><i class="bi bi-arrow-repeat spinner"></i> Загрузка...</div>
    </div>
    <div class="cart-drawer-footer">
        <div class="cart-drawer-total">
            Итого: <strong class="cart-total">0 BYN</strong>
        </div>
        <a href="/cart" class="btn btn-primary cart-drawer-checkout">
            Оформить заказ <i class="bi bi-arrow-right"></i>
        </a>
    </div>
</div>

<!-- Search Modal -->
<div class="search-modal" id="searchModal">
    <div class="search-modal-content">
        <div class="search-header">
            <input type="text" placeholder="Поиск товаров..." id="searchInput" class="search-input" onkeyup="handleSearch(event)">
            <button class="search-close close-search" onclick="closeSearch()">
                <i class="bi bi-x"></i>
            </button>
        </div>
        <div class="search-results" id="searchResults"></div>
    </div>
</div>

<script>
(function() {
    var _burgerOpen = false;

    function isMobile() { return window.innerWidth < 1024; }

    window.toggleBurgerMenu = function(e) {
        if (e) e.stopPropagation();
        _burgerOpen ? closeBurgerMenu() : openBurgerMenu();
    };

    function openBurgerMenu() {
        _burgerOpen = true;
        var btn = document.getElementById('navBurger');
        if (btn) { btn.setAttribute('aria-expanded', 'true'); btn.classList.add('active'); }

        if (isMobile()) {
            var m = document.getElementById('mobileMenu');
            var o = document.getElementById('menuOverlay');
            if (m) m.classList.add('active');
            if (o) o.classList.add('active');
            document.body.style.overflow = 'hidden';
        } else {
            var mm = document.getElementById('megaMenu');
            var mo = document.getElementById('megaOverlay');
            if (mm) mm.classList.add('open');
            if (mo) mo.classList.add('active');
        }
    }

    window.closeBurgerMenu = function() {
        _burgerOpen = false;
        var btn = document.getElementById('navBurger');
        if (btn) { btn.setAttribute('aria-expanded', 'false'); btn.classList.remove('active'); }

        var m = document.getElementById('mobileMenu');
        var o = document.getElementById('menuOverlay');
        var mm = document.getElementById('megaMenu');
        var mo = document.getElementById('megaOverlay');
        if (m) m.classList.remove('active');
        if (o) o.classList.remove('active');
        if (mm) mm.classList.remove('open');
        if (mo) mo.classList.remove('active');
        document.body.style.overflow = '';
    };

    // Keep legacy names working
    window.toggleMobileMenu = window.toggleBurgerMenu;
    window.closeMobileMenu = window.closeBurgerMenu;
    window.openMobileMenu = openBurgerMenu;

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && _burgerOpen) window.closeBurgerMenu();
    });

    window.addEventListener('resize', function() {
        if (_burgerOpen) window.closeBurgerMenu();
    });
})();
</script>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
