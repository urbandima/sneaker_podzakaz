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
            <ul class="nav-menu">
                <li><a href="/catalog">Каталог</a></li>
                <li><a href="/brands">Бренды</a></li>
                <li><a href="/sale">Скидки</a></li>
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
                <span class="wishlist-counter header-badge" role="status" aria-live="polite" style="display: none;">0</span>
            </a>
            
            <a href="/cart" class="btn-cart" aria-label="Корзина">
                <i class="bi bi-cart3" aria-hidden="true"></i>
                <span class="cart-counter header-badge" role="status" aria-live="polite" style="display: none;">0</span>
            </a>
            
            <a href="/account" class="btn-account" aria-label="Личный кабинет">
                <i class="bi bi-person" aria-hidden="true"></i>
            </a>
        </div>
        
        <!-- Mobile Menu Toggle -->
        <button class="mobile-menu-toggle" onclick="toggleMobileMenu()" aria-label="Открыть меню" aria-expanded="false" aria-controls="mobileMenu">
            <i class="bi bi-list" aria-hidden="true"></i>
        </button>
    </div>
</header>

<!-- Main Content -->
<main id="main-content">
    <?= $content ?>
</main>

<!-- Footer -->
<?= $this->render('//partials/footer') ?>

<!-- Mobile Menu Backdrop -->
<div class="menu-overlay" onclick="closeMobileMenu()"></div>

<!-- Mobile Menu -->
<div class="mobile-menu" id="mobileMenu">
    <div class="mobile-menu-header">
        <span>Меню</span>
        <button class="close-menu" onclick="toggleMobileMenu()">
            <i class="bi bi-x"></i>
        </button>
    </div>
    <nav class="mobile-nav">
        <a href="/catalog" onclick="toggleMobileMenu()">Каталог</a>
        <a href="/brands" onclick="toggleMobileMenu()">Бренды</a>
        <a href="/sale" onclick="toggleMobileMenu()">Скидки</a>
        <a href="/about" onclick="toggleMobileMenu()">О нас</a>
        <a href="/contacts" onclick="toggleMobileMenu()">Контакты</a>
        <a href="/account" onclick="toggleMobileMenu()">Личный кабинет</a>
    </nav>
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
