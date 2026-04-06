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
    
    <!-- Подключаем Dark Mode CSS -->
    <?= Html::cssFile('@web/css/dark-mode.css', ['depends' => [AppAsset::class]]) ?>
    
    <!-- Подключаем Accessibility CSS -->
    <?= Html::cssFile('@web/css/accessibility.css', ['depends' => [AppAsset::class]]) ?>
    
    <!-- Подключаем Micro-interactions CSS -->
    <?= Html::cssFile('@web/css/micro-interactions.css', ['depends' => [AppAsset::class]]) ?>
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


