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
    
    <?php // Preload критических ресурсов ?>
    <link rel="preload" href="/css/css/dist/critical.min.css" as="style">
    <link rel="preload" href="/css/css/dist/public-bundle.min.css" as="style">
    <link rel="preload" href="/css/css/dist/product-page.min.css" as="style">
    
    <?php // Bootstrap Icons CSS ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <?php // DNS prefetch для внешних ресурсов ?>
    <link rel="dns-prefetch" href="//fonts.googleapis.com">
    <link rel="dns-prefetch" href="//cdn.jsdelivr.net">
    
    <?php // Preconnect для шрифтов ?>
    <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    
    <?php // Оптимизация Bootstrap Icons с font-display: swap ?>
    <style>
        @font-face {
            font-family: 'bootstrap-icons';
            src: url('https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/fonts/bootstrap-icons.woff2') format('woff2');
            font-display: swap;
        }
    </style>
    
    <!-- Базовые стили для header -->
    <style>
        .main-header {
            background: var(--surface-primary, #ffffff) !important;
            border-bottom: 1px solid var(--border-color, #e2e8f0) !important;
            padding: 1rem 0 !important;
            position: sticky !important;
            top: 0 !important;
            z-index: 1000 !important;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05) !important;
        }
        
        /* Адаптация для темной темы */
        @media (prefers-color-scheme: dark) {
            .main-header {
                background: var(--surface-primary, #0f172a) !important;
                border-bottom: 1px solid var(--border-color, #334155) !important;
                box-shadow: 0 2px 8px rgba(0,0,0,0.3) !important;
            }
        }
        
        [data-theme="dark"] .main-header {
            background: var(--surface-primary, #0f172a) !important;
            border-bottom: 1px solid var(--border-color, #334155) !important;
            box-shadow: 0 2px 8px rgba(0,0,0,0.3) !important;
        }
        
        .header-content {
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            gap: 1rem !important;
            max-width: 1200px !important;
            margin: 0 auto !important;
            padding: 0 1rem !important;
        }
        
        .logo {
            display: flex !important;
            align-items: center !important;
            text-decoration: none !important;
            color: var(--text-primary, #0f172a) !important;
            font-weight: 700 !important;
            font-size: 1.25rem !important;
            transition: transform 0.2s ease !important;
            flex-shrink: 0 !important;
        }
        
        .logo:hover {
            transform: scale(1.05) !important;
        }
        
        .logo img {
            height: 48px !important;
            margin-right: 0.75rem !important;
            border-radius: 8px !important;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1) !important;
        }
        
        .logo-text {
            display: flex !important;
            flex-direction: column !important;
            line-height: 1.2 !important;
        }
        
        .logo-text .brand {
            font-size: 1.25rem !important;
            font-weight: 700 !important;
            color: var(--text-primary, #0f172a) !important;
        }
        
        .logo-text .tagline {
            font-size: 0.75rem !important;
            color: var(--text-secondary, #6b7280) !important;
            font-weight: 400 !important;
        }
        
        .main-nav {
            display: flex !important;
            align-items: center !important;
            flex: 1 !important;
            justify-content: center !important;
        }
        
        .nav-menu {
            display: flex !important;
            list-style: none !important;
            margin: 0 !important;
            padding: 0 !important;
            gap: 2.5rem !important;
            align-items: center !important;
        }
        
        .nav-menu li {
            position: relative !important;
        }
        
        .nav-menu a {
            color: var(--text-primary, #0f172a) !important;
            text-decoration: none !important;
            font-weight: 600 !important;
            font-size: 1rem !important;
            padding: 0.5rem 0 !important;
            transition: all 0.3s ease !important;
            position: relative !important;
        }
        
        .nav-menu a::after {
            content: '' !important;
            position: absolute !important;
            bottom: -2px !important;
            left: 0 !important;
            width: 0 !important;
            height: 2px !important;
            background: var(--color-accent, #2563eb) !important;
            transition: width 0.3s ease !important;
        }
        
        .nav-menu a:hover {
            color: var(--color-accent, #2563eb) !important;
            transform: translateY(-1px) !important;
        }
        
        .nav-menu a:hover::after {
            width: 100% !important;
        }
        
        .header-actions {
            display: flex !important;
            align-items: center !important;
            gap: 0.75rem !important;
            flex-shrink: 0 !important;
        }
        
        .header-actions button,
        .header-actions a {
            background: none !important;
            border: none !important;
            padding: 0.75rem !important;
            border-radius: 12px !important;
            color: var(--text-secondary, #6b7280) !important;
            text-decoration: none !important;
            transition: all 0.3s ease !important;
            cursor: pointer !important;
            position: relative !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
        }
        
        .header-actions button:hover,
        .header-actions a:hover {
            background: var(--surface-secondary, #f1f5f9) !important;
            color: var(--text-primary, #0f172a) !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1) !important;
        }
        
        .header-actions i {
            font-size: 1.25rem !important;
        }
        
        .header-actions .badge {
            position: absolute !important;
            top: 4px !important;
            right: 4px !important;
            background: var(--color-accent, #2563eb) !important;
            color: white !important;
            font-size: 0.625rem !important;
            padding: 2px 4px !important;
            border-radius: 10px !important;
            min-width: 16px !important;
            text-align: center !important;
            font-weight: 600 !important;
        }
        
        .mobile-menu-toggle {
            display: none !important;
            background: none !important;
            border: none !important;
            padding: 0.75rem !important;
            border-radius: 12px !important;
            color: var(--text-secondary, #6b7280) !important;
            cursor: pointer !important;
            transition: all 0.3s ease !important;
        }
        
        .mobile-menu-toggle:hover {
            background: var(--surface-secondary, #f1f5f9) !important;
            color: var(--text-primary, #0f172a) !important;
        }
        
        .mobile-menu-toggle i {
            font-size: 1.5rem !important;
        }
        
        @media (max-width: 768px) {
            .main-nav {
                display: none !important;
            }
            
            .mobile-menu-toggle {
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
            }
            
            .header-content {
                gap: 0.5rem !important;
            }
            
            .logo img {
                height: 40px !important;
                margin-right: 0.5rem !important;
            }
            
            .logo-text .brand {
                font-size: 1.125rem !important;
            }
            
            .logo-text .tagline {
                display: none !important;
            }
        }
    </style>
</head>
<body>
<?php $this->beginBody() ?>

<!-- Skip to main content (a11y) -->
<a href="#main-content" class="skip-link">Перейти к основному содержимому</a>

<!-- Cookies Consent -->
<script src="/js/cookies-consent.js"></script>

<!-- Header -->
<header class="main-header">
    <div class="header-content">
        <!-- Logo -->
        <a href="/" class="logo">
            <img src="/images/logo.png" alt="<?= Html::encode($company['name'] ?? 'СНИКЕРХЭД') ?>">
            <div class="logo-text">
                <span class="brand"><?= Html::encode($company['name'] ?? 'СНИКЕРХЭД') ?></span>
                <span class="tagline">Оригинальные кроссовки</span>
            </div>
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
                <span class="wishlist-counter badge" role="status" aria-live="polite" style="display: none;">0</span>
            </a>
            
            <a href="/cart" class="btn-cart" aria-label="Корзина">
                <i class="bi bi-cart3" aria-hidden="true"></i>
                <span class="cart-counter badge" role="status" aria-live="polite" style="display: none;">0</span>
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

<!-- Подключаем Dark Mode JS -->
<?= Html::jsFile('@web/js/dark-mode.js', ['position' => \yii\web\View::POS_END]) ?>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
