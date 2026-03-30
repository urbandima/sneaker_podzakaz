<?php

use yii\helpers\Html;
use yii\helpers\Url;
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
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
<?php $this->beginBody() ?>

<!-- Skip to main content (a11y) -->
<a href="#main-content" class="skip-link">Перейти к основному содержимому</a>

<!-- Header -->
<header class="main-header">
    <div class="header-container">
        <!-- Mobile Menu Button -->
        <button class="action-btn burger-menu" onclick="toggleMobileMenu()" aria-label="Меню">
            <i class="bi bi-list"></i>
        </button>

        <!-- Logo -->
        <a href="<?= Url::to(['/site/index']) ?>" class="header-logo">
            <img src="/images/logo.png" alt="<?= Html::encode($company['name'] ?? 'СНИКЕРХЭД') ?>">
        </a>

        <!-- Navigation -->
        <nav class="header-nav">
            <a href="<?= Url::to(['/catalog/catalog/index']) ?>">Каталог</a>
            <a href="<?= Url::to(['/catalog/brands/index']) ?>">Бренды</a>
            <a href="<?= Url::to(['/catalog/catalog/index', 'sort' => 'sale']) ?>" class="text-sale">Скидки</a>
            <a href="<?= Url::to(['/page/about']) ?>">О нас</a>
            <a href="<?= Url::to(['/page/contacts']) ?>">Контакты</a>
        </nav>

        <!-- Actions -->
        <div class="header-actions">
            <button class="action-btn" onclick="openSearch()" aria-label="Поиск">
                <i class="bi bi-search"></i>
            </button>

            <a href="<?= Url::to(['/catalog/favorites/index']) ?>" class="action-btn" aria-label="Избранное">
                <i class="bi bi-heart"></i>
                <span class="badge-count" style="display: none;">0</span>
            </a>

            <button class="action-btn" onclick="openCartDrawer()" aria-label="Корзина">
                <i class="bi bi-bag"></i>
                <span class="badge-count cart-counter" style="display: none;">0</span>
            </button>

            <a href="<?= Url::to(['/account/account/index']) ?>" class="action-btn" aria-label="Профиль">
                <i class="bi bi-person"></i>
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

<!-- Cart Drawer -->
<div class="cart-drawer-overlay" id="cartDrawerOverlay" onclick="closeCartDrawer()"></div>
<div class="cart-drawer" id="cartDrawer">
    <div class="cart-drawer-header">
        <h3 class="cart-drawer-title">
            Корзина <span class="cart-drawer-count" id="cartDrawerCount">0 товаров</span>
        </h3>
        <button class="cart-drawer-close" onclick="closeCartDrawer()">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
    <div class="cart-drawer-body" id="cartDrawerItems">
        <!-- Содержимое корзины загружается через AJAX -->
        <div class="cart-empty">
            <i class="bi bi-bag"></i>
            <p>Ваша корзина пуста</p>
        </div>
    </div>
    <div class="cart-drawer-footer">
        <div class="cart-summary-row">
            <span>Итого:</span>
            <span class="cart-summary-total cart-total">0 Br</span>
        </div>
        <a href="<?= Url::to(['/checkout/index']) ?>" class="btn-checkout">Оформить заказ</a>
        <a href="#" class="btn-continue" onclick="closeCartDrawer(); return false;">Продолжить покупки</a>
    </div>
</div>

<!-- Mobile Menu -->
<div class="menu-overlay" id="mobileMenuOverlay" onclick="toggleMobileMenu()"></div>
<div class="mobile-menu" id="mobileMenu">
    <div class="mobile-menu-header">
        <div class="header-logo">Меню</div>
        <button class="mobile-menu-close" onclick="toggleMobileMenu()">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
    <div class="mobile-menu-body">
        <a href="<?= Url::to(['/catalog/catalog/index']) ?>" class="mobile-menu-link">Каталог</a>
        <a href="<?= Url::to(['/catalog/brands/index']) ?>" class="mobile-menu-link">Бренды</a>
        <a href="<?= Url::to(['/catalog/catalog/index', 'sort' => 'sale']) ?>" class="mobile-menu-link text-sale">Скидки</a>
        <a href="<?= Url::to(['/page/about']) ?>" class="mobile-menu-link">О нас</a>
        <a href="<?= Url::to(['/page/contacts']) ?>" class="mobile-menu-link">Контакты</a>
        <a href="<?= Url::to(['/account/account/index']) ?>" class="mobile-menu-link">Личный кабинет</a>
    </div>
</div>

<!-- Search Modal -->
<div class="search-modal" id="searchModal">
    <div class="search-modal-content">
        <div class="search-header">
            <input type="text" placeholder="Поиск товаров..." id="searchInput" onkeyup="handleSearch(event)">
            <button class="close-search" onclick="closeSearch()">
                <i class="bi bi-x"></i>
            </button>
        </div>
        <div class="search-results" id="searchResults"></div>
    </div>
</div>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
