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

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>

<style>
/* Header Styles */
.main-header {
    background: white;
    border-bottom: 1px solid #e2e8f0;
    position: sticky;
    top: 0;
    z-index: 1000;
}

.header-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 0;
}

.logo img {
    height: 40px;
}

.nav-menu {
    display: flex;
    list-style: none;
    gap: 2rem;
    margin: 0;
    padding: 0;
}

.nav-menu a {
    color: #0f172a;
    text-decoration: none;
    font-weight: 500;
    transition: color 0.2s;
}

.nav-menu a:hover {
    color: #6366f1;
}

.header-actions {
    display: flex;
    align-items: center;
    gap: 1rem;
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
    fetch(`/catalog/search?q=${encodeURIComponent(query)}`)
        .then(r => r.json())
        .then(data => {
            const items = data.results || data;
            if (items.length > 0) {
                resultsContainer.innerHTML = items.map(product => `
                    <a href="${product.url || '/catalog/product/' + product.slug}" class="search-result-item" onclick="closeSearch()">
                        <img src="${product.mainImage || product.image || '/images/placeholder.png'}" alt="${product.name}">
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
