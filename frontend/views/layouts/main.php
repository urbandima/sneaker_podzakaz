<?php

use yii\helpers\Html;
use app\frontend\assets\AppAsset;

AppAsset::register($this);

// Load page-specific CSS only for the controller that needs it, so e.g.
// product.css (650 KB) is not sent on the home page.
(function ($view) {
    $c   = Yii::$app->controller;
    $m   = $c->module;
    $mId = ($m && $m->id !== Yii::$app->id) ? $m->id : null;
    $cId = $c->id;
    $aId = $c->action ? $c->action->id : null;

    if (!$mId && $cId === 'site' && $aId === 'index')                             $css = 'landing';
    elseif ($mId === 'catalog' && in_array($aId, ['product', 'product-simple']))   $css = 'product';
    elseif ($mId === 'catalog')                                                    $css = 'catalog';
    elseif (!$mId && $cId === 'cart')                                              $css = 'cart';
    elseif (!$mId && $cId === 'order')                                             $css = 'checkout';
    elseif ($mId === 'account' || (!$mId && $cId === 'account'))                   $css = 'account';
    else return;

    $file = Yii::getAlias('@webroot') . '/css/pages/' . $css . '.css';
    $v    = file_exists($file) ? filemtime($file) : '1';
    $view->registerCssFile(Yii::$app->request->baseUrl . '/css/pages/' . $css . '.css?v=' . $v);
})($this);

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
    
    <?php // Preconnect CDN origins — must precede any resource from those domains ?>
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <?php // Bootstrap Icons — async to avoid render-blocking (icons are non-critical) ?>
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"></noscript>

    <?php
    $s = Yii::$app->settings;
    $ga4Id     = $s->get('analytics', 'ga4_id', '');
    $metrikaId = $s->get('analytics', 'metrika_id', '') ?: $s->get('seo', 'metrika_id', '');
    // Skip placeholder / demo values
    $ga4Valid     = !empty($ga4Id) && $ga4Id !== 'G-XXXXXXXXXX' && strlen($ga4Id) > 5;
    $metrikaValid = !empty($metrikaId) && $metrikaId !== '12345678' && strlen($metrikaId) >= 6 && ctype_digit($metrikaId);
    ?>
    <?php if ($ga4Valid): ?>
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?= htmlspecialchars($ga4Id) ?>"></script>
    <script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments)}gtag('js',new Date());gtag('config','<?= htmlspecialchars($ga4Id) ?>');</script>
    <?php endif; ?>
    <?php if ($metrikaValid): ?>
    <script>(function(m,e,t,r,i,k,a){m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};m[i].l=1*new Date();for(var j=0;j<document.scripts.length;j++){if(document.scripts[j].src===r){return;}}k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)})(window,document,"script","https://mc.yandex.ru/metrika/tag.js","ym");ym(<?= (int)$metrikaId ?>,"init",{clickmap:true,trackLinks:true,accurateTrackBounce:true});</script>
    <noscript><div><img src="https://mc.yandex.ru/watch/<?= (int)$metrikaId ?>" style="position:absolute;left:-9999px;" alt=""/></div></noscript>
    <?php endif; ?>
</head>
<body data-is-guest="<?= Yii::$app->session->get('customer_id') ? '0' : '1' ?>">
<?php $this->beginBody() ?>

<!-- Admin toolbar (X20: visible only to authenticated admins) -->
<?php if (!Yii::$app->user->isGuest && (Yii::$app->user->identity->isAdmin ?? false)): ?>
<div class="admin-toolbar" style="background:#1e293b;color:#fff;padding:6px 16px;font-size:13px;display:flex;align-items:center;gap:12px;z-index:9999;position:relative">
    <span><i class="bi bi-shield-fill-check"></i> Режим администратора</span>
    <a href="/admin" style="color:#60a5fa;text-decoration:none">Панель управления</a>
    <?php
    $editUrl = null;
    $pathInfo = Yii::$app->request->pathInfo;
    if (str_starts_with($pathInfo, 'catalog/')) {
        preg_match('/catalog\/[^\/]+\/([^\/]+)$/', $pathInfo, $m);
        $slug = $m[1] ?? null;
        if ($slug) {
            $prod = \app\backend\modules\catalog\models\Product::find()->where(['slug' => $slug])->select(['id'])->scalar();
            if ($prod) $editUrl = '/admin/catalog/product/update?id=' . $prod;
        }
    }
    if ($editUrl): ?>
        <a href="<?= $editUrl ?>" style="color:#86efac;text-decoration:none"><i class="bi bi-pencil-square"></i> Редактировать товар</a>
    <?php endif; ?>
</div>
<?php endif; ?>

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

            <a href="/checkout" class="btn-cart" aria-label="Корзина" onclick="event.preventDefault(); openCartDrawer();">
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
            <?php if (!empty($company['phone'])): ?>
            <a href="tel:<?= preg_replace('/[^+\d]/', '', $company['phone']) ?>"><i class="bi bi-telephone"></i><?= Html::encode($company['phone']) ?></a>
            <?php endif; ?>
            <?php $tgUrl = Yii::$app->settings->get('social', 'telegram', ''); ?>
            <?php if (!empty($tgUrl) && strlen($tgUrl) > 5 && $tgUrl !== '#'): ?>
            <a href="<?= Html::encode($tgUrl) ?>" target="_blank" rel="noopener noreferrer"><i class="bi bi-telegram"></i>Telegram</a>
            <?php endif; ?>
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
        <a href="/checkout" class="btn btn-primary cart-drawer-checkout">
            Оформить заказ <i class="bi bi-arrow-right"></i>
        </a>
    </div>
</div>

<!-- Search Modal -->
<div class="search-modal" id="searchModal">
    <div class="search-modal-content">
        <div class="search-header">
            <input type="text" placeholder="Поиск товаров..." id="searchInput" class="search-input"
                   oninput="handleSearchInput(this.value)"
                   onkeydown="handleSearchKey(event)"
                   autocomplete="off" aria-label="Поиск товаров">
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
