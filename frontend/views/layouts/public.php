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
</head>
<body>
<?php $this->beginBody() ?>

<!-- Header -->
<header class="main-header">
    <div class="container">
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
                <button class="btn-search" onclick="openSearch()" aria-label="Поиск товаров">
                    <i class="bi bi-search"></i>
                </button>
                
                <a href="/account/wishlist" class="btn-wishlist" aria-label="Избранное">
                    <i class="bi bi-heart"></i>
                    <span class="wishlist-counter sr-only" style="display: none;">0 товаров в избранном</span>
                </a>
                
                <a href="/cart" class="btn-cart" aria-label="Корзина">
                    <i class="bi bi-cart3"></i>
                    <span class="cart-counter sr-only" style="display: none;">0 товаров в корзине</span>
                </a>
                
                <a href="/account" class="btn-account" aria-label="Личный кабинет">
                    <i class="bi bi-person"></i>
                </a>
            </div>
            
            <!-- Mobile Menu Toggle -->
            <button class="mobile-menu-toggle" onclick="toggleMobileMenu()" aria-label="Открыть меню">
                <i class="bi bi-list"></i>
            </button>
        </div>
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

<!-- Search Functions -->
<script>
function openSearch() {
    const modal = document.getElementById('searchModal');
    modal.classList.add('open');
    document.getElementById('searchInput').focus();
    document.body.style.overflow = 'hidden';
}

function closeSearch() {
    const modal = document.getElementById('searchModal');
    modal.classList.remove('open');
    document.body.style.overflow = '';
}

function handleSearch(event) {
    if (event.key === 'Escape') {
        closeSearch();
        return;
    }
    
    const query = event.target.value.trim();
    const resultsContainer = document.getElementById('searchResults');
    
    if (query.length < 2) {
        resultsContainer.innerHTML = '';
        return;
    }
    
    // TODO: Реализовать AJAX поиск
    // Временно показываем заглушку
    resultsContainer.innerHTML = `
        <div class="search-loading">
            <div class="spinner"></div>
            <p>Поиск товаров...</p>
        </div>
    `;
    
    // Имитация поиска
    setTimeout(() => {
        resultsContainer.innerHTML = `
            <div class="search-empty">
                <p>По запросу "${query}" ничего не найдено</p>
                <a href="/catalog?q=${encodeURIComponent(query)}" class="btn btn-primary">Посмотреть все товары</a>
            </div>
        `;
    }, 500);
}

// Close search on backdrop click
document.getElementById('searchModal').addEventListener('click', (e) => {
    if (e.target.id === 'searchModal') {
        closeSearch();
    }
});

// Mobile menu functions
function toggleMobileMenu() {
    const menu = document.getElementById('mobileMenu');
    menu.classList.toggle('open');
    document.body.style.overflow = menu.classList.contains('open') ? 'hidden' : '';
}
</script>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>

<style>
    /* Базовые стили для header */
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
    }
    
    .logo {
        display: flex !important;
        align-items: center !important;
        text-decoration: none !important;
        margin-right: 2rem !important;
    }
    
    .logo img {
        height: 40px !important;
        width: auto !important;
        transition: transform 0.2s ease !important;
    }
    
    .logo:hover img {
        transform: scale(1.05) !important;
    }
    
    .main-nav {
        flex: 1 !important;
        display: flex !important;
        justify-content: center !important;
    }
    
    .nav-menu {
        display: flex !important;
        list-style: none !important;
        gap: 2rem !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    
    .nav-menu a {
        color: var(--text-primary, #0f172a) !important;
        text-decoration: none !important;
        font-weight: 500 !important;
        font-size: 0.95rem !important;
        padding: 0.5rem 0 !important;
        border-bottom: 2px solid transparent !important;
        transition: all 0.2s ease !important;
    }
    
    .nav-menu a:hover {
        color: var(--accent-color, #3b82f6) !important;
        border-bottom-color: var(--accent-color, #3b82f6) !important;
    }
    
    .nav-menu a.active {
        color: var(--accent-color, #3b82f6) !important;
        border-bottom-color: var(--accent-color, #3b82f6) !important;
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
        border-radius: 8px !important;
        color: var(--text-secondary, #6b7280) !important;
        text-decoration: none !important;
        transition: all 0.2s ease !important;
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
        background: var(--accent-color, #ef4444) !important;
        color: white !important;
        border-radius: 50% !important;
        font-size: 0.75rem !important;
        padding: 2px 6px !important;
        min-width: 18px !important;
        height: 18px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
    }
    
    /* Search Modal Styles */
    .search-modal {
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        right: 0 !important;
        bottom: 0 !important;
        background: rgba(0, 0, 0, 0.5) !important;
        display: none !important;
        z-index: 9999 !important;
        align-items: center !important;
        justify-content: center !important;
    }
    
    .search-modal.open {
        display: flex !important;
    }
    
    .search-modal-content {
        background: var(--surface-primary, #ffffff) !important;
        border-radius: 12px !important;
        width: 90% !important;
        max-width: 600px !important;
        max-height: 80vh !important;
        overflow: hidden !important;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15) !important;
    }
    
    .search-header {
        display: flex !important;
        align-items: center !important;
        padding: 1rem !important;
        border-bottom: 1px solid var(--border-color, #e2e8f0) !important;
    }
    
    .search-header input {
        flex: 1 !important;
        border: none !important;
        outline: none !important;
        font-size: 1.1rem !important;
        background: transparent !important;
        color: var(--text-primary, #0f172a) !important;
    }
    
    .search-header input::placeholder {
        color: var(--text-secondary, #64748b) !important;
    }
    
    .search-header button {
        background: none !important;
        border: none !important;
        padding: 0.5rem !important;
        cursor: pointer !important;
        color: var(--text-secondary, #64748b) !important;
        border-radius: 6px !important;
        transition: all 0.2s ease !important;
    }
    
    .search-header button:hover {
        background: var(--surface-secondary, #f1f5f9) !important;
        color: var(--text-primary, #0f172a) !important;
    }
    
    .search-results {
        max-height: 400px !important;
        overflow-y: auto !important;
        padding: 1rem !important;
    }
    
    .search-loading {
        text-align: center !important;
        padding: 2rem !important;
        color: var(--text-secondary, #64748b) !important;
    }
    
    .search-loading .spinner {
        width: 24px !important;
        height: 24px !important;
        border: 2px solid var(--border-color, #e2e8f0) !important;
        border-top: 2px solid var(--accent-color, #3b82f6) !important;
        border-radius: 50% !important;
        animation: spin 1s linear infinite !important;
        margin: 0 auto 1rem !important;
    }
    
    .search-empty {
        text-align: center !important;
        padding: 2rem !important;
        color: var(--text-secondary, #64748b) !important;
    }
    
    .search-empty p {
        margin-bottom: 1rem !important;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    /* Mobile Menu Styles */
    .mobile-menu {
        position: fixed !important;
        top: 0 !important;
        left: -100% !important;
        width: 80% !important;
        max-width: 400px !important;
        height: 100vh !important;
        background: var(--surface-primary, #ffffff) !important;
        z-index: 9998 !important;
        transition: left 0.3s ease !important;
        overflow-y: auto !important;
    }
    
    .mobile-menu.open {
        left: 0 !important;
    }
    
    .mobile-menu-header {
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
        padding: 1rem !important;
        border-bottom: 1px solid var(--border-color, #e2e8f0) !important;
    }
    
    .mobile-nav {
        padding: 1rem !important;
    }
    
    .mobile-nav a {
        display: block !important;
        padding: 0.75rem 0 !important;
        color: var(--text-primary, #0f172a) !important;
        text-decoration: none !important;
        border-bottom: 1px solid var(--border-color, #f1f5f9) !important;
        transition: color 0.2s ease !important;
    }
    
    .mobile-nav a:hover {
        color: var(--accent-color, #3b82f6) !important;
    }
    
    .mobile-menu-toggle {
        display: none !important;
    }
    
    @media (max-width: 768px) {
        .mobile-menu-toggle {
            display: block !important;
        }
        
        .main-nav {
            display: none !important;
        }
    }

.btn-search,
.btn-wishlist,
.btn-cart,
.btn-account {
    background: none;
    border: none;
    padding: 0.5rem;
    cursor: pointer;
    font-size: 1.25rem;
    color: #0f172a;
    text-decoration: none;
    position: relative;
    transition: color 0.2s;
}

.btn-search:hover,
.btn-wishlist:hover,
.btn-cart:hover,
.btn-account:hover {
    color: #6366f1;
}

.wishlist-counter,
.cart-counter {
    position: absolute;
    top: -5px;
    right: -5px;
    background: #ef4444;
    color: white;
    font-size: 0.75rem;
    padding: 0.125rem 0.375rem;
    border-radius: 100px;
    min-width: 20px;
    text-align: center;
}

.mobile-menu-toggle {
    display: none;
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
}

/* Mobile Menu */
.mobile-menu {
    position: fixed;
    inset: 0;
    background: white;
    z-index: 9999;
    transform: translateX(100%);
    transition: transform 0.3s ease;
}

.mobile-menu.open {
    transform: translateX(0);
}

.mobile-menu-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem;
    border-bottom: 1px solid #e2e8f0;
}

.close-menu {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
}

.mobile-nav {
    display: flex;
    flex-direction: column;
    padding: 1.5rem;
    gap: 1rem;
}

.mobile-nav a {
    color: #0f172a;
    text-decoration: none;
    font-size: 1.125rem;
    padding: 0.75rem;
    border-radius: 8px;
    transition: background 0.2s;
}

.mobile-nav a:hover {
    background: #f8fafc;
}

/* Search Modal */
.search-modal {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.7);
    backdrop-filter: blur(8px);
    z-index: 10000;
    display: none;
    align-items: flex-start;
    justify-content: center;
    padding-top: 10vh;
}

.search-modal.open {
    display: flex;
}

.search-modal-content {
    background: white;
    border-radius: 16px;
    width: 90%;
    max-width: 600px;
    overflow: hidden;
}

.search-header {
    display: flex;
    gap: 1rem;
    padding: 1rem;
    border-bottom: 1px solid #e2e8f0;
}

.search-header input {
    flex: 1;
    border: none;
    font-size: 1.125rem;
    outline: none;
}

.close-search {
    background: none;
    border: none;
    font-size: 1.25rem;
    cursor: pointer;
}

.search-results {
    max-height: 400px;
    overflow-y: auto;
}

/* Responsive */
@media (max-width: 1024px) {
    .nav-menu {
        display: none;
    }
    
    .mobile-menu-toggle {
        display: block;
    }
}
</style>

<script>
function toggleMobileMenu() {
    const menu = document.getElementById('mobileMenu');
    menu.classList.toggle('open');
    document.body.style.overflow = menu.classList.contains('open') ? 'hidden' : '';
}

function openSearch() {
    const modal = document.getElementById('searchModal');
    modal.classList.add('open');
    document.getElementById('searchInput').focus();
    document.body.style.overflow = 'hidden';
}

function closeSearch() {
    const modal = document.getElementById('searchModal');
    modal.classList.remove('open');
    document.body.style.overflow = '';
}

function handleSearch(event) {
    if (event.key === 'Escape') {
        closeSearch();
        return;
    }
    
    const query = event.target.value.trim();
    const resultsContainer = document.getElementById('searchResults');
    
    if (query.length < 2) {
        resultsContainer.innerHTML = '';
        return;
    }
    
    // AJAX search
    fetch(`/api/v1/products/search?q=${encodeURIComponent(query)}`)
        .then(r => r.json())
        .then(data => {
            if (data.length > 0) {
                resultsContainer.innerHTML = data.map(product => `
                    <a href="/product/${product.id}" class="search-result-item" onclick="closeSearch()">
                        <img src="${product.image || '/images/placeholder.png'}" alt="${product.name}">
                        <div class="search-result-info">
                            <div class="search-result-name">${product.name}</div>
                            <div class="search-result-price">${product.price} BYN</div>
                        </div>
                    </a>
                `).join('');
            } else {
                resultsContainer.innerHTML = '<div class="search-no-results">Ничего не найдено</div>';
            }
        });
}

// Close search on backdrop click
document.getElementById('searchModal').addEventListener('click', (e) => {
    if (e.target.id === 'searchModal') {
        closeSearch();
    }
});
</script>
