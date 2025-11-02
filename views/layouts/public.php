<?php

/** @var yii\web\View $this */
/** @var string $content */

use app\assets\AppAsset;
use yii\bootstrap5\Html;

AppAsset::register($this);

$this->registerCsrfMetaTags();
$this->registerMetaTag(['charset' => Yii::$app->charset], 'charset');
$this->registerMetaTag(['name' => 'viewport', 'content' => 'width=device-width, initial-scale=1, shrink-to-fit=no']);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>" class="h-100">
<head>
    <title><?= Html::encode($this->title) ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?= Yii::$app->request->baseUrl ?>/favicon.ico">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <?php $this->head() ?>
    
    <!-- SEO Meta Tags -->
    <?php
    $description = $this->params['description'] ?? 'СНИКЕРХЭД - оригинальные кроссовки Nike, Adidas, Puma и другие бренды. Большой выбор, низкие цены, быстрая доставка по Беларуси.';
    $keywords = $this->params['keywords'] ?? 'кроссовки, обувь, Nike, Adidas, Puma, купить кроссовки Минск';
    $image = $this->params['image'] ?? Yii::$app->request->hostInfo . Yii::$app->request->baseUrl . '/images/og-default.jpg';
    $url = Yii::$app->request->hostInfo . Yii::$app->request->url;
    ?>
    <meta name="description" content="<?= Html::encode($description) ?>">
    <meta name="keywords" content="<?= Html::encode($keywords) ?>">
    
    <!-- Canonical URL -->
    <link rel="canonical" href="<?= Html::encode($url) ?>">
    
    <!-- Open Graph / Facebook / VK -->
    <meta property="og:type" content="<?= $this->params['og:type'] ?? 'website' ?>">
    <meta property="og:url" content="<?= Html::encode($url) ?>">
    <meta property="og:title" content="<?= Html::encode($this->title) ?>">
    <meta property="og:description" content="<?= Html::encode($description) ?>">
    <meta property="og:image" content="<?= Html::encode($image) ?>">
    <meta property="og:site_name" content="СНИКЕРХЭД">
    <meta property="og:locale" content="ru_RU">
    
    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="<?= Html::encode($url) ?>">
    <meta name="twitter:title" content="<?= Html::encode($this->title) ?>">
    <meta name="twitter:description" content="<?= Html::encode($description) ?>">
    <meta name="twitter:image" content="<?= Html::encode($image) ?>">
    
    <style>
        body {
            background: #ffffff;
            min-height: 100vh;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }
        
        .container {
            max-width: 100%;
            padding: 0;
        }
        
        @media (max-width: 768px) {
            body {
                font-size: 14px;
            }
        }
    </style>
</head>
<body class="d-flex flex-column h-100">
<?php $this->beginBody() ?>

<!-- PREMIUM E-COMMERCE HEADER -->
<header class="ecom-header">
    <!-- Top Bar -->
    <div class="top-bar">
        <div class="container">
            <div class="top-bar-left">
                <i class="bi bi-truck"></i> Бесплатная доставка от 200 BYN
            </div>
            <div class="top-bar-right">
                <a href="tel:+375291234567"><i class="bi bi-telephone"></i> +375 29 123-45-67</a>
                <a href="/site/track"><i class="bi bi-geo-alt"></i> Отследить заказ</a>
            </div>
        </div>
    </div>
    
    <!-- Main Header -->
    <div class="main-header">
        <div class="container">
            <div class="header-left">
                <button class="menu-burger" id="menuBurger">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
                <a href="/" class="logo">
                    <span class="logo-icon">👟</span>
                    <span class="logo-text">
                        <strong>СНИКЕРХЭД</strong>
                        <small>Оригинальная обувь</small>
                    </span>
                </a>
            </div>
            
            <div class="header-search">
                <div class="search-box">
                    <i class="bi bi-search"></i>
                    <input type="text" placeholder="Поиск товаров, брендов..." id="headerSearch">
                    <button class="search-voice"><i class="bi bi-mic"></i></button>
                </div>
            </div>
            
            <div class="header-actions">
                <a href="/catalog/history" class="header-btn">
                    <i class="bi bi-clock-history"></i>
                    <span class="label">История</span>
                </a>
                <a href="/catalog/favorites" class="header-btn">
                    <i class="bi bi-heart"></i>
                    <span class="badge" id="favCount">0</span>
                    <span class="label">Избранное</span>
                </a>
                <a href="/cart" class="header-btn">
                    <i class="bi bi-bag"></i>
                    <span class="badge" id="cartCount">0</span>
                    <span class="label">Корзина</span>
                </a>
                <a href="/site/account" class="header-btn">
                    <i class="bi bi-person"></i>
                    <span class="label">Профиль</span>
                </a>
            </div>
        </div>
    </div>
    
    <!-- Navigation Menu -->
    <nav class="main-nav">
        <div class="container">
            <ul class="nav-menu">
                <li class="nav-item has-mega">
                    <a href="/catalog">
                        <i class="bi bi-grid-3x3-gap"></i>
                        Каталог
                        <i class="bi bi-chevron-down"></i>
                    </a>
                    
                    <!-- Mega Menu -->
                    <div class="mega-menu">
                        <div class="mega-content">
                            <div class="mega-col">
                                <h4>👟 Обувь</h4>
                                <a href="/catalog?cat=sneakers">Кроссовки</a>
                                <a href="/catalog?cat=boots">Ботинки</a>
                                <a href="/catalog?cat=sandals">Сандалии</a>
                                <a href="/catalog?cat=slippers">Слипоны</a>
                            </div>
                            <div class="mega-col">
                                <h4>👕 Одежда</h4>
                                <a href="/catalog?cat=tshirts">Футболки</a>
                                <a href="/catalog?cat=hoodies">Толстовки</a>
                                <a href="/catalog?cat=jackets">Куртки</a>
                                <a href="/catalog?cat=pants">Брюки</a>
                            </div>
                            <div class="mega-col">
                                <h4>🎒 Аксессуары</h4>
                                <a href="/catalog?cat=bags">Сумки</a>
                                <a href="/catalog?cat=caps">Кепки</a>
                                <a href="/catalog?cat=socks">Носки</a>
                                <a href="/catalog?cat=belts">Ремни</a>
                            </div>
                            <div class="mega-col mega-promo">
                                <div class="promo-banner">
                                    <span class="promo-badge">🔥 HOT</span>
                                    <h3>Новая коллекция</h3>
                                    <p>Скидки до 50%</p>
                                    <a href="/catalog?sale=1" class="promo-btn">Смотреть</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </li>
                <li class="nav-item has-mega">
                    <a href="/catalog?gender=male">
                        <i class="bi bi-gender-male"></i>
                        Мужское
                        <i class="bi bi-chevron-down"></i>
                    </a>
                    <div class="mega-menu">
                        <div class="mega-content">
                            <div class="mega-col">
                                <h4>Обувь</h4>
                                <a href="/catalog?gender=male&cat=sneakers">Кроссовки</a>
                                <a href="/catalog?gender=male&cat=boots">Ботинки</a>
                                <a href="/catalog?gender=male&cat=sandals">Сандалии</a>
                            </div>
                            <div class="mega-col">
                                <h4>Одежда</h4>
                                <a href="/catalog?gender=male&cat=tshirts">Футболки</a>
                                <a href="/catalog?gender=male&cat=hoodies">Толстовки</a>
                                <a href="/catalog?gender=male&cat=jackets">Куртки</a>
                            </div>
                        </div>
                    </div>
                </li>
                <li class="nav-item has-mega">
                    <a href="/catalog?gender=female">
                        <i class="bi bi-gender-female"></i>
                        Женское
                        <i class="bi bi-chevron-down"></i>
                    </a>
                    <div class="mega-menu">
                        <div class="mega-content">
                            <div class="mega-col">
                                <h4>Обувь</h4>
                                <a href="/catalog?gender=female&cat=sneakers">Кроссовки</a>
                                <a href="/catalog?gender=female&cat=boots">Ботинки</a>
                                <a href="/catalog?gender=female&cat=sandals">Сандалии</a>
                            </div>
                            <div class="mega-col">
                                <h4>Одежда</h4>
                                <a href="/catalog?gender=female&cat=tshirts">Футболки</a>
                                <a href="/catalog?gender=female&cat=hoodies">Толстовки</a>
                                <a href="/catalog?gender=female&cat=dresses">Платья</a>
                            </div>
                        </div>
                    </div>
                </li>
                <li class="nav-item">
                    <a href="/catalog?new=1">
                        <i class="bi bi-star-fill"></i>
                        Новинки
                    </a>
                </li>
                <li class="nav-item nav-sale">
                    <a href="/catalog?sale=1">
                        <i class="bi bi-fire"></i>
                        Распродажа
                    </a>
                </li>
                <li class="nav-item has-mega">
                    <a href="#" id="brandsNavBtn">
                        <i class="bi bi-tags-fill"></i>
                        Бренды
                        <i class="bi bi-chevron-down"></i>
                    </a>
                    <div class="mega-menu brands-mega">
                        <div class="mega-content">
                            <div class="brands-dropdown-header">Популярные бренды</div>
                            <div class="brands-grid" id="brandsGrid">
                                <div style="text-align:center;padding:2rem">
                                    <i class="bi bi-hourglass-split"></i> Загрузка...
                                </div>
                            </div>
                        </div>
                    </div>
                </li>
            </ul>
        </div>
    </nav>
</header>

<!-- Mobile Menu Overlay -->
<div class="mobile-menu" id="mobileMenu">
    <div class="mobile-menu-header">
        <h3>Меню</h3>
        <button class="menu-close" id="menuClose">
            <i class="bi bi-x"></i>
        </button>
    </div>
    <div class="mobile-menu-content">
        <div class="mobile-search">
            <i class="bi bi-search"></i>
            <input type="text" placeholder="Поиск...">
        </div>
        
        <ul class="mobile-nav">
            <!-- Каталог с подкатегориями -->
            <li class="mobile-nav-item has-submenu">
                <a href="#" class="mobile-nav-toggle">
                    <i class="bi bi-grid-3x3-gap"></i> Каталог
                    <i class="bi bi-chevron-down chevron"></i>
                </a>
                <ul class="mobile-submenu">
                    <li><a href="/catalog?cat=sneakers">Кроссовки</a></li>
                    <li><a href="/catalog?cat=boots">Ботинки</a></li>
                    <li><a href="/catalog?cat=sandals">Сандалии</a></li>
                    <li><a href="/catalog?cat=slippers">Слипоны</a></li>
                    <li><a href="/catalog?cat=tshirts">Футболки</a></li>
                    <li><a href="/catalog?cat=hoodies">Толстовки</a></li>
                    <li><a href="/catalog?cat=jackets">Куртки</a></li>
                    <li><a href="/catalog?cat=accessories">Аксессуары</a></li>
                </ul>
            </li>
            
            <!-- Мужское с подкатегориями -->
            <li class="mobile-nav-item has-submenu">
                <a href="#" class="mobile-nav-toggle">
                    <i class="bi bi-gender-male"></i> Мужское
                    <i class="bi bi-chevron-down chevron"></i>
                </a>
                <ul class="mobile-submenu">
                    <li><a href="/catalog?gender=male&cat=sneakers">Кроссовки</a></li>
                    <li><a href="/catalog?gender=male&cat=boots">Ботинки</a></li>
                    <li><a href="/catalog?gender=male&cat=sandals">Сандалии</a></li>
                    <li><a href="/catalog?gender=male&cat=tshirts">Футболки</a></li>
                    <li><a href="/catalog?gender=male&cat=hoodies">Толстовки</a></li>
                    <li><a href="/catalog?gender=male&cat=jackets">Куртки</a></li>
                </ul>
            </li>
            
            <!-- Женское с подкатегориями -->
            <li class="mobile-nav-item has-submenu">
                <a href="#" class="mobile-nav-toggle">
                    <i class="bi bi-gender-female"></i> Женское
                    <i class="bi bi-chevron-down chevron"></i>
                </a>
                <ul class="mobile-submenu">
                    <li><a href="/catalog?gender=female&cat=sneakers">Кроссовки</a></li>
                    <li><a href="/catalog?gender=female&cat=boots">Ботинки</a></li>
                    <li><a href="/catalog?gender=female&cat=sandals">Сандалии</a></li>
                    <li><a href="/catalog?gender=female&cat=tshirts">Футболки</a></li>
                    <li><a href="/catalog?gender=female&cat=hoodies">Толстовки</a></li>
                    <li><a href="/catalog?gender=female&cat=dresses">Платья</a></li>
                </ul>
            </li>
            
            <li><a href="/catalog?new=1"><i class="bi bi-star"></i> Новинки</a></li>
            <li><a href="/catalog?sale=1"><i class="bi bi-fire"></i> Распродажа</a></li>
            <li><a href="/catalog/brands"><i class="bi bi-award"></i> Бренды</a></li>
            <li><a href="/site/track"><i class="bi bi-geo-alt"></i> Отследить заказ</a></li>
            <li><a href="/site/about"><i class="bi bi-info-circle"></i> О нас</a></li>
            <li><a href="/site/contacts"><i class="bi bi-envelope"></i> Контакты</a></li>
        </ul>
        
        <div class="mobile-footer">
            <a href="tel:+375291234567" class="mobile-phone">
                <i class="bi bi-telephone"></i> +375 29 123-45-67
            </a>
        </div>
    </div>
</div>
<div class="mobile-menu-overlay" id="menuOverlay"></div>

<main role="main" class="flex-shrink-0">
    <div class="container-fluid p-0">
        <?php if (Yii::$app->session->hasFlash('success')): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert" style="margin: 1rem;">
                <?= Yii::$app->session->getFlash('success') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (Yii::$app->session->hasFlash('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert" style="margin: 1rem;">
                <?= Yii::$app->session->getFlash('error') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?= $content ?>
    </div>
</main>

<!-- FOOTER -->
<footer class="site-footer">
    <div class="footer-main">
        <div class="container">
            <div class="footer-grid">
                <!-- О компании -->
                <div class="footer-col">
                    <div class="footer-logo">
                        <span class="logo-icon">👟</span>
                        <span class="logo-text">
                            <strong>СНИКЕРХЭД</strong>
                            <small>Оригинальная обувь</small>
                        </span>
                    </div>
                    <p class="footer-desc">
                        Официальный поставщик оригинальных кроссовок Nike, Adidas, Puma и других мировых брендов в Беларуси.
                    </p>
                    <div class="footer-social">
                        <a href="#" title="Instagram"><i class="bi bi-instagram"></i></a>
                        <a href="#" title="Facebook"><i class="bi bi-facebook"></i></a>
                        <a href="#" title="Telegram"><i class="bi bi-telegram"></i></a>
                        <a href="#" title="VK"><i class="bi bi-vk"></i></a>
                    </div>
                </div>
                
                <!-- Каталог -->
                <div class="footer-col">
                    <h4>Каталог</h4>
                    <ul>
                        <li><a href="/catalog?gender=male">Мужское</a></li>
                        <li><a href="/catalog?gender=female">Женское</a></li>
                        <li><a href="/catalog?new=1">Новинки</a></li>
                        <li><a href="/catalog?sale=1">Распродажа</a></li>
                        <li><a href="/catalog/brands">Все бренды</a></li>
                    </ul>
                </div>
                
                <!-- Информация -->
                <div class="footer-col">
                    <h4>Информация</h4>
                    <ul>
                        <li><a href="/site/about">О нас</a></li>
                        <li><a href="/site/delivery">Доставка и оплата</a></li>
                        <li><a href="/site/returns">Возврат и обмен</a></li>
                        <li><a href="/site/guarantee">Гарантия</a></li>
                        <li><a href="/site/contacts">Контакты</a></li>
                    </ul>
                </div>
                
                <!-- Контакты -->
                <div class="footer-col">
                    <h4>Контакты</h4>
                    <ul class="footer-contacts">
                        <li>
                            <i class="bi bi-telephone-fill"></i>
                            <a href="tel:+375291234567">+375 29 123-45-67</a>
                        </li>
                        <li>
                            <i class="bi bi-envelope-fill"></i>
                            <a href="mailto:info@sneaker-head.by">info@sneaker-head.by</a>
                        </li>
                        <li>
                            <i class="bi bi-geo-alt-fill"></i>
                            <span>г. Минск, ул. Примерная, 1</span>
                        </li>
                        <li>
                            <i class="bi bi-clock-fill"></i>
                            <span>Пн-Вс: 10:00 - 22:00</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <div class="footer-bottom">
        <div class="container">
            <div class="footer-bottom-content">
                <p>&copy; <?= date('Y') ?> СНИКЕРХЭД. Все права защищены.</p>
                <div class="footer-payment">
                    <img src="https://cdn.jsdelivr.net/npm/payment-icons@1.2.2/svg/visa.svg" alt="Visa" width="40">
                    <img src="https://cdn.jsdelivr.net/npm/payment-icons@1.2.2/svg/mastercard.svg" alt="Mastercard" width="40">
                    <img src="https://cdn.jsdelivr.net/npm/payment-icons@1.2.2/svg/maestro.svg" alt="Maestro" width="40">
                </div>
            </div>
        </div>
    </div>
</footer>

<style>
/* ============================================
   PREMIUM E-COMMERCE HEADER STYLES
   ============================================ */

/* Top Bar */
.top-bar {
  background: #000;
  color: #fff;
  padding: 0.5rem 0;
  font-size: 0.8125rem;
}

.top-bar .container {
  display: flex;
  justify-content: space-between;
  align-items: center;
  max-width: 1600px;
  padding: 0 1rem;
  margin: 0 auto;
}

.top-bar-left {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.top-bar-right {
  display: flex;
  gap: 1.5rem;
}

.top-bar-right a {
  color: #fff;
  text-decoration: none;
  display: flex;
  align-items: center;
  gap: 0.375rem;
  transition: opacity 0.2s;
}

.top-bar-right a:hover {
  opacity: 0.8;
}

/* Main Header */
.main-header {
  background: #fff;
  border-bottom: 1px solid #e5e7eb;
  padding: 1rem 0;
  position: sticky;
  top: 0;
  z-index: 100;
  box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}

.main-header .container {
  max-width: 1600px;
  padding: 0 1rem;
  margin: 0 auto;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 2rem;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.menu-burger {
  display: none;
  flex-direction: column;
  gap: 4px;
  background: none;
  border: none;
  cursor: pointer;
  padding: 0.5rem;
}

.menu-burger span {
  width: 24px;
  height: 2px;
  background: #000;
  transition: all 0.3s;
  border-radius: 2px;
}

.logo {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  text-decoration: none;
  color: #000;
  font-weight: 900;
  font-size: 1.25rem;
  letter-spacing: -0.5px;
}

.logo-icon {
  font-size: 1.75rem;
}

.logo-text {
  display: flex;
  flex-direction: column;
  line-height: 1.2;
}

.logo-text strong {
  font-size: 1.25rem;
  font-weight: 900;
  letter-spacing: -0.5px;
  color: #000;
}

.logo-text small {
  font-size: 0.6875rem;
  font-weight: 500;
  color: #666;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

/* Category Navigation Bar */
.category-nav-bar {
  background: #fff;
  border-bottom: 2px solid #f3f4f6;
  box-shadow: 0 2px 4px rgba(0,0,0,0.04);
}

.category-nav {
  display: flex;
  align-items: center;
  gap: 0;
  padding: 0;
}

.cat-nav-link {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 1rem 1.5rem;
  font-weight: 600;
  font-size: 0.9375rem;
  color: #111;
  text-decoration: none;
  transition: all 0.2s;
  position: relative;
  border-bottom: 3px solid transparent;
}

.cat-nav-link i {
  font-size: 1.125rem;
}

.cat-nav-link:hover {
  background: #f9fafb;
  color: #3b82f6;
  border-bottom-color: #3b82f6;
}

.cat-nav-link.cat-sale {
  color: #ef4444;
}

.cat-nav-link.cat-sale:hover {
  background: #fef2f2;
  color: #dc2626;
  border-bottom-color: #ef4444;
}

/* Brands Dropdown */
.brands-dropdown {
  position: relative;
  margin-left: auto;
}

.brands-dropdown .cat-nav-link {
  cursor: pointer;
}

.brands-dropdown-menu {
  display: none;
  position: absolute;
  top: 100%;
  right: 0;
  background: #fff;
  border-radius: 12px;
  box-shadow: 0 8px 32px rgba(0,0,0,0.15);
  padding: 1rem;
  min-width: 300px;
  max-height: 400px;
  overflow-y: auto;
  z-index: 1000;
  animation: slideDown 0.3s;
}

.brands-dropdown-menu.active {
  display: block;
}

@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.brands-dropdown-header {
  font-weight: 700;
  font-size: 0.875rem;
  color: #666;
  margin-bottom: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.brands-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 0.5rem;
}

.brand-link {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.625rem 0.75rem;
  border-radius: 6px;
  text-decoration: none;
  color: #111;
  font-size: 0.875rem;
  font-weight: 500;
  transition: all 0.2s;
  background: #f9fafb;
}

.brand-link:hover {
  background: #3b82f6;
  color: #fff;
  transform: translateX(4px);
}

.brand-link .count {
  font-size: 0.75rem;
  color: #666;
  background: #fff;
  padding: 0.125rem 0.5rem;
  border-radius: 12px;
  font-weight: 600;
}

.brand-link:hover .count {
  background: rgba(255,255,255,0.2);
  color: #fff;
}

/* Search Box */
.header-search {
  flex: 1;
  max-width: 600px;
}

.search-box {
  position: relative;
  display: flex;
  align-items: center;
  background: #f3f4f6;
  border-radius: 12px;
  padding: 0.75rem 1rem;
  transition: all 0.2s;
}

.search-box:focus-within {
  background: #fff;
  box-shadow: 0 0 0 3px rgba(0,0,0,0.05);
}

.search-box i {
  color: #666;
  font-size: 1.125rem;
  margin-right: 0.75rem;
}

.search-box input {
  flex: 1;
  border: none;
  background: transparent;
  font-size: 0.9375rem;
  outline: none;
}

.search-voice {
  background: none;
  border: none;
  color: #666;
  font-size: 1.125rem;
  cursor: pointer;
  padding: 0.25rem 0.5rem;
  transition: color 0.2s;
}

.search-voice:hover {
  color: #000;
}

/* Header Actions */
.header-actions {
  display: flex;
  gap: 1.5rem;
}

.header-btn {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-decoration: none;
  color: #000;
  position: relative;
  transition: color 0.2s;
  background: transparent !important;
  width: auto !important;
  height: auto !important;
  min-width: auto !important;
  min-height: auto !important;
  border-radius: 0 !important;
}

.header-btn i {
  font-size: 1.5rem;
  margin-bottom: 0.25rem;
}

.header-btn .badge {
  position: absolute;
  top: -4px;
  right: -8px;
  background: #ef4444;
  color: #fff;
  border-radius: 50%;
  width: 18px;
  height: 18px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.625rem;
  font-weight: 700;
}

.header-btn .label {
  font-size: 0.75rem;
  font-weight: 500;
}

.header-btn:hover {
  color: #ef4444;
}

/* Navigation */
.main-nav {
  background: #fff;
  border-bottom: 1px solid #e5e7eb;
}

.main-nav .container {
  max-width: 1600px;
  padding: 0 1rem;
  margin: 0 auto;
}

.nav-menu {
  display: flex;
  list-style: none;
  margin: 0;
  padding: 0;
  gap: 2rem;
}

.nav-item {
  position: relative;
}

.nav-item > a {
  display: flex;
  align-items: center;
  gap: 0.375rem;
  padding: 1rem 0;
  color: #000;
  text-decoration: none;
  font-weight: 600;
  font-size: 0.9375rem;
  transition: color 0.2s;
}

.nav-item > a:hover {
  color: #ef4444;
}

.nav-item i {
  font-size: 0.75rem;
  transition: transform 0.3s;
}

.nav-item:hover i {
  transform: rotate(180deg);
}

/* Mega Menu */
.mega-menu {
  position: absolute;
  top: 100%;
  left: 0;
  background: #fff;
  border-radius: 12px;
  box-shadow: 0 8px 32px rgba(0,0,0,0.12);
  opacity: 0;
  visibility: hidden;
  transform: translateY(10px);
  transition: all 0.3s;
  z-index: 1000;
  min-width: 800px;
}

.nav-item:hover .mega-menu {
  opacity: 1;
  visibility: visible;
  transform: translateY(0);
}

.mega-content {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 2rem;
  padding: 2rem;
}

.mega-col h4 {
  font-size: 0.875rem;
  font-weight: 700;
  margin-bottom: 1rem;
  color: #000;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.mega-col a {
  display: block;
  padding: 0.5rem 0;
  color: #666;
  text-decoration: none;
  font-size: 0.875rem;
  transition: all 0.2s;
}

.mega-col a:hover {
  color: #000;
  padding-left: 0.5rem;
}

.mega-promo {
  background: linear-gradient(135deg, #000, #1f2937);
  border-radius: 8px;
  padding: 1.5rem !important;
  color: #fff;
}

.promo-banner {
  text-align: center;
}

.promo-badge {
  background: #ef4444;
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 700;
  display: inline-block;
  margin-bottom: 1rem;
}

.promo-banner h3 {
  font-size: 1.25rem;
  margin-bottom: 0.5rem;
}

.promo-banner p {
  color: rgba(255,255,255,0.8);
  font-size: 0.875rem;
  margin-bottom: 1rem;
}

.promo-btn {
  display: inline-block;
  background: #fff;
  color: #000;
  padding: 0.625rem 1.5rem;
  border-radius: 6px;
  text-decoration: none;
  font-weight: 700;
  font-size: 0.875rem;
  transition: transform 0.2s;
}

.promo-btn:hover {
  transform: scale(1.05);
}

/* Mobile Menu */
.mobile-menu {
  position: fixed;
  top: 0;
  left: -100%;
  width: 85%;
  max-width: 360px;
  height: 100vh;
  background: #fff;
  z-index: 1001;
  transition: left 0.3s;
  overflow-y: auto;
}

.mobile-menu.active {
  left: 0;
}

.mobile-menu-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  border-bottom: 1px solid #e5e7eb;
}

.mobile-menu-header h3 {
  font-size: 1.125rem;
  margin: 0;
}

.menu-close {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
}

.mobile-search {
  padding: 1rem;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  background: #f3f4f6;
  margin: 1rem;
  border-radius: 8px;
}

.mobile-search input {
  flex: 1;
  border: none;
  background: transparent;
  outline: none;
}

.mobile-nav {
  list-style: none;
  padding: 0;
  margin: 1rem 0;
}

.mobile-nav li a {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem 1.5rem;
  color: #000;
  text-decoration: none;
  font-weight: 500;
  transition: background 0.2s;
}

.mobile-nav li a:hover {
  background: #f9fafb;
}

.mobile-nav li i {
  font-size: 1.125rem;
  width: 24px;
}

/* Mobile submenu (accordion) */
.mobile-nav-item.has-submenu {
  position: relative;
}

.mobile-nav-toggle {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
  cursor: pointer;
}

.mobile-nav-toggle .chevron {
  transition: transform 0.3s ease;
  margin-left: auto;
}

.mobile-submenu {
  list-style: none;
  padding: 0;
  margin: 0;
  max-height: 0;
  overflow: hidden;
  transition: max-height 0.3s ease;
  background: #f9fafb;
  border-radius: 8px;
  margin-top: 0.5rem;
}

.mobile-submenu li {
  border-bottom: 1px solid #e5e7eb;
}

.mobile-submenu li:last-child {
  border-bottom: none;
}

.mobile-submenu a {
  padding: 0.75rem 1rem 0.75rem 3rem;
  font-size: 0.9375rem;
  color: #6b7280;
}

.mobile-submenu a:hover {
  background: white;
  color: #111827;
}

.mobile-footer {
  padding: 1.5rem;
  border-top: 1px solid #e5e7eb;
  margin-top: auto;
}

.mobile-phone {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  color: #000;
  text-decoration: none;
  font-weight: 600;
  font-size: 1.0625rem;
}

.mobile-menu-overlay {
  display: none;
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0,0,0,0.5);
  z-index: 1000;
}

.mobile-menu-overlay.active {
  display: block;
}

/* Responsive */
@media (min-width: 992px) {
  .main-nav {
    display: flex !important;
  }
  
  .menu-burger {
    display: none;
  }
}

@media (max-width: 991px) {
  .main-nav {
    display: none;
  }
  
  .menu-burger {
    display: flex;
  }
  
  .header-search {
    max-width: none;
    flex: 1;
  }
  
  .header-btn .label {
    display: none;
  }
  
  .top-bar-left,
  .top-bar-right a:not(:first-child) {
    display: none;
  }
}

@media (max-width: 640px) {
  .header-search {
    display: none;
  }
  
  .header-actions {
    gap: 1rem;
  }
  
  .header-btn i {
    font-size: 1.25rem;
  }
}

/* ============================================
   FOOTER STYLES
   ============================================ */

.site-footer {
  background: #1a1a1a;
  color: #fff;
  margin-top: 4rem;
}

.footer-main {
  padding: 3rem 0 2rem;
}

.footer-main .container {
  max-width: 1600px;
  margin: 0 auto;
  padding: 0 1rem;
}

.footer-grid {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr 1.5fr;
  gap: 3rem;
}

.footer-col h4 {
  font-size: 1rem;
  font-weight: 700;
  margin-bottom: 1.25rem;
  color: #fff;
}

.footer-col ul {
  list-style: none;
  padding: 0;
  margin: 0;
}

.footer-col ul li {
  margin-bottom: 0.75rem;
}

.footer-col ul li a {
  color: #9ca3af;
  text-decoration: none;
  font-size: 0.9375rem;
  transition: color 0.2s;
}

.footer-col ul li a:hover {
  color: #fff;
}

.footer-logo {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 1rem;
}

.footer-logo .logo-icon {
  font-size: 2rem;
}

.footer-logo .logo-text strong {
  font-size: 1.5rem;
  color: #fff;
}

.footer-logo .logo-text small {
  color: #9ca3af;
}

.footer-desc {
  color: #9ca3af;
  font-size: 0.9375rem;
  line-height: 1.6;
  margin-bottom: 1.5rem;
}

.footer-social {
  display: flex;
  gap: 0.75rem;
}

.footer-social a {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: #2d2d2d;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  font-size: 1.125rem;
  transition: all 0.2s;
  text-decoration: none;
}

.footer-social a:hover {
  background: #3b82f6;
  transform: translateY(-3px);
}

.footer-contacts li {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  color: #9ca3af;
  font-size: 0.9375rem;
}

.footer-contacts li i {
  color: #3b82f6;
  font-size: 1rem;
  margin-top: 2px;
}

.footer-contacts li a {
  color: #9ca3af;
  text-decoration: none;
  transition: color 0.2s;
}

.footer-contacts li a:hover {
  color: #fff;
}

.footer-bottom {
  border-top: 1px solid #2d2d2d;
  padding: 1.5rem 0;
}

.footer-bottom .container {
  max-width: 1600px;
  margin: 0 auto;
  padding: 0 1rem;
}

.footer-bottom-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.footer-bottom p {
  margin: 0;
  color: #9ca3af;
  font-size: 0.875rem;
}

.footer-payment {
  display: flex;
  gap: 0.75rem;
  align-items: center;
}

.footer-payment img {
  height: 24px;
  width: auto;
  opacity: 0.8;
  transition: opacity 0.2s;
}

.footer-payment img:hover {
  opacity: 1;
}

@media (max-width: 1024px) {
  .footer-grid {
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
  }
}

@media (max-width: 640px) {
  .footer-grid {
    grid-template-columns: 1fr;
    gap: 2rem;
  }
  
  .footer-bottom-content {
    flex-direction: column;
    gap: 1rem;
    text-align: center;
  }
}
</style>

<script>
// Mobile menu toggle
const menuBurger = document.getElementById('menuBurger');
const menuClose = document.getElementById('menuClose');
const mobileMenu = document.getElementById('mobileMenu');
const menuOverlay = document.getElementById('menuOverlay');

menuBurger?.addEventListener('click', () => {
  mobileMenu.classList.add('active');
  menuOverlay.classList.add('active');
  document.body.style.overflow = 'hidden';
});

menuClose?.addEventListener('click', () => {
  mobileMenu.classList.remove('active');
  menuOverlay.classList.remove('active');
  document.body.style.overflow = '';
});

menuOverlay?.addEventListener('click', () => {
  mobileMenu.classList.remove('active');
  menuOverlay.classList.remove('active');
  document.body.style.overflow = '';
});

// Mobile submenu toggle (accordion)
document.querySelectorAll('.mobile-nav-toggle').forEach(toggle => {
  toggle.addEventListener('click', (e) => {
    e.preventDefault();
    const parent = toggle.closest('.mobile-nav-item');
    const submenu = parent.querySelector('.mobile-submenu');
    const chevron = toggle.querySelector('.chevron');
    
    // Toggle current submenu
    if (parent.classList.contains('active')) {
      parent.classList.remove('active');
      submenu.style.maxHeight = '0';
      chevron.style.transform = 'rotate(0deg)';
    } else {
      // Close other submenus
      document.querySelectorAll('.mobile-nav-item.active').forEach(item => {
        item.classList.remove('active');
        item.querySelector('.mobile-submenu').style.maxHeight = '0';
        item.querySelector('.chevron').style.transform = 'rotate(0deg)';
      });
      
      // Open current submenu
      parent.classList.add('active');
      submenu.style.maxHeight = submenu.scrollHeight + 'px';
      chevron.style.transform = 'rotate(180deg)';
    }
  });
});

// Close mega menu on click outside
document.addEventListener('click', (e) => {
  if (!e.target.closest('.nav-item')) {
    document.querySelectorAll('.mega-menu').forEach(menu => {
      menu.style.opacity = '0';
      menu.style.visibility = 'hidden';
    });
  }
});

// Search functionality
const headerSearch = document.getElementById('headerSearch');
const searchBox = document.querySelector('.search-box');

// Поиск по Enter
headerSearch?.addEventListener('keypress', (e) => {
  if (e.key === 'Enter') {
    const query = e.target.value.trim();
    if (query.length > 0) {
      window.location.href = '/catalog?search=' + encodeURIComponent(query);
    }
  }
});

// Поиск по клику на иконку
searchBox?.querySelector('.bi-search')?.addEventListener('click', () => {
  const query = headerSearch.value.trim();
  if (query.length > 0) {
    window.location.href = '/catalog?search=' + encodeURIComponent(query);
  }
});

// Brands Mega Menu
const brandsNavBtn = document.getElementById('brandsNavBtn');
let brandsLoaded = false;

brandsNavBtn?.addEventListener('mouseenter', () => {
  // Загружаем бренды при первом наведении
  if (!brandsLoaded) {
    loadBrands();
    brandsLoaded = true;
  }
});

// Загрузка брендов
function loadBrands() {
  fetch('/catalog/get-brands')
    .then(r => r.json())
    .then(brands => {
      const grid = document.getElementById('brandsGrid');
      if (brands.length === 0) {
        grid.innerHTML = '<p style="text-align:center;padding:2rem;color:#666">Бренды не найдены</p>';
        return;
      }
      
      grid.innerHTML = brands.map(brand => `
        <a href="/catalog/brand/${brand.slug}" class="brand-link">
          <span>${brand.name}</span>
          <span class="count">${brand.products_count}</span>
        </a>
      `).join('');
    })
    .catch(err => {
      console.error('Error loading brands:', err);
      document.getElementById('brandsGrid').innerHTML = 
        '<p style="text-align:center;padding:2rem;color:#ef4444">Ошибка загрузки</p>';
    });
}
</script>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
