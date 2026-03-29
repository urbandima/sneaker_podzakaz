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
    <div class="header-content">
        <!-- Logo -->
        <a href="<?= Url::to(['/site/index']) ?>" class="logo">
            <img src="/images/logo.png" alt="<?= Html::encode($company['name'] ?? 'СНИКЕРХЭД') ?>">
        </a>

        <!-- Navigation -->
        <nav class="main-nav">
            <ul class="nav-menu">
                <li class="dropdown-container">
                    <a href="<?= Url::to(['/catalog/catalog/index']) ?>">Каталог <i class="bi bi-chevron-down"></i></a>
                    <div class="mega-menu">
                        <div class="mega-menu-grid">
                            <div class="mega-menu-col">
                                <h4>Обувь</h4>
                                <ul>
                                    <li><a href="<?= Url::to(['/catalog/catalog/index', 'category' => 'sneakers']) ?>">Кроссовки</a></li>
                                    <li><a href="<?= Url::to(['/catalog/catalog/index', 'category' => 'boots']) ?>">Ботинки</a></li>
                                    <li><a href="<?= Url::to(['/catalog/catalog/index', 'category' => 'slides']) ?>">Сланцы и сандалии</a></li>
                                </ul>
                            </div>
                            <div class="mega-menu-col">
                                <h4>Одежда</h4>
                                <ul>
                                    <li><a href="<?= Url::to(['/catalog/catalog/index', 'category' => 'tshirts']) ?>">Футболки</a></li>
                                    <li><a href="<?= Url::to(['/catalog/catalog/index', 'category' => 'hoodies']) ?>">Худи и толстовки</a></li>
                                    <li><a href="<?= Url::to(['/catalog/catalog/index', 'category' => 'pants']) ?>">Штаны и шорты</a></li>
                                    <li><a href="<?= Url::to(['/catalog/catalog/index', 'category' => 'jackets']) ?>">Куртки</a></li>
                                </ul>
                            </div>
                            <div class="mega-menu-col">
                                <h4>Аксессуары</h4>
                                <ul>
                                    <li><a href="<?= Url::to(['/catalog/catalog/index', 'category' => 'bags']) ?>">Сумки и рюкзаки</a></li>
                                    <li><a href="<?= Url::to(['/catalog/catalog/index', 'category' => 'caps']) ?>">Головные уборы</a></li>
                                    <li><a href="<?= Url::to(['/catalog/catalog/index', 'category' => 'socks']) ?>">Носки</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </li>
                <li><a href="<?= Url::to(['/catalog/brands/index']) ?>">Бренды</a></li>
                <li><a href="<?= Url::to(['/catalog/catalog/index', 'sort' => 'sale']) ?>" class="text-sale">Скидки</a></li>
                <li><a href="<?= Url::to(['/page/about']) ?>">О нас</a></li>
                <li><a href="<?= Url::to(['/page/contacts']) ?>">Контакты</a></li>
            </ul>
        </nav>

        <!-- Actions -->
        <div class="header-actions">
            <button class="btn-search" onclick="openSearch()" aria-label="Поиск товаров" aria-haspopup="dialog">
                <i class="bi bi-search" aria-hidden="true"></i>
            </button>

            <a href="<?= Url::to(['/catalog/favorites/index']) ?>" class="btn-wishlist" aria-label="Избранное">
                <i class="bi bi-heart" aria-hidden="true"></i>
                <span class="wishlist-counter badge" role="status" aria-live="polite" style="display: none;">0</span>
            </a>

            <a href="<?= Url::to(['/cart/cart/index']) ?>" class="btn-cart" aria-label="Корзина">
                <i class="bi bi-cart3" aria-hidden="true"></i>
                <span class="cart-counter badge" role="status" aria-live="polite" style="display: none;">0</span>
            </a>

            <a href="<?= Url::to(['/account/account/index']) ?>" class="btn-account" aria-label="Личный кабинет">
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
        <a href="<?= Url::to(['/catalog/catalog/index']) ?>" onclick="toggleMobileMenu()">Каталог</a>
        <a href="<?= Url::to(['/catalog/brands/index']) ?>" onclick="toggleMobileMenu()">Бренды</a>
        <a href="<?= Url::to(['/catalog/catalog/index', 'sort' => 'sale']) ?>" onclick="toggleMobileMenu()">Скидки</a>
        <a href="<?= Url::to(['/page/about']) ?>" onclick="toggleMobileMenu()">О нас</a>
        <a href="<?= Url::to(['/page/contacts']) ?>" onclick="toggleMobileMenu()">Контакты</a>
        <a href="<?= Url::to(['/account/account/index']) ?>" onclick="toggleMobileMenu()">Личный кабинет</a>
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
