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
    <!-- Main Header -->
    <div class="main-header">
        <div class="container">
            <div class="header-left">
                <button class="menu-burger" id="menuBurger">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
                <?php
                // На странице каталога логотип ведет на каталог, иначе - на главную
                $logoUrl = (Yii::$app->controller->id === 'catalog') ? '/catalog' : '/';
                ?>
                <a href="<?= $logoUrl ?>" class="logo">
                    <span class="logo-image">
                        <img src="https://sneaker-head.by/images/logo.png" alt="Сникерхэд" loading="lazy">
                    </span>
                    <span class="logo-text">
                        <strong>СНИКЕРХЭД</strong>
                        <small>
                            <span class="line-1">Оригинальные товары</span>
                            <span class="line-2">под заказ</span>
                        </small>
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

<!-- ULTRA COMPACT FOOTER -->
<footer class="site-footer">
    <div class="footer-main">
        <div class="container">
            <div class="footer-content">
                <!-- Колонка 1: Бренд + Навигация -->
                <div class="footer-col-left">
                    <div class="footer-brand">
                        <div class="footer-logo">
                            <span class="footer-logo-image">
                                <img src="https://sneaker-head.by/images/logo.png" alt="Сникерхэд" loading="lazy">
                            </span>
                            <div class="footer-logo-text">
                                <strong>СНИКЕРХЭД</strong>
                                <small>Оригинальные товары под заказ</small>
                            </div>
                        </div>
                        <div class="footer-social">
                            <a href="#" title="Instagram" class="social-link"><i class="bi bi-instagram"></i></a>
                            <a href="#" title="Telegram" class="social-link"><i class="bi bi-telegram"></i></a>
                            <a href="#" title="VK" class="social-link"><i class="bi bi-vk"></i></a>
                            <span class="social-divider">|</span>
                            <span class="payment-badge">VISA</span>
                            <span class="payment-badge">Mastercard</span>
                            <span class="payment-badge">МИР</span>
                        </div>
                    </div>
                    <div class="footer-nav">
                        <a href="/catalog?gender=male">Мужское</a>
                        <a href="/catalog?gender=female">Женское</a>
                        <a href="/catalog?new=1">Новинки</a>
                        <span class="nav-divider">|</span>
                        <a href="/site/offer-agreement">Договор оферты</a>
                        <a href="https://sneaker-head.by/page/politika-konfidencialnosti" target="_blank">Политика конфиденциальности</a>
                        <a href="https://sneaker-head.by/page/dostavka-i-oplata" target="_blank">Доставка и оплата</a>
                    </div>
                </div>
                
                <!-- Колонка 2: Контакты -->
                <div class="footer-col-right">
                    <div class="footer-contacts">
                        <a href="tel:+375447009001" class="contact-item">
                            <i class="bi bi-telephone-fill"></i>
                            <span>+375 (44) 700-90-01</span>
                        </a>
                        <a href="mailto:sneakerkultura@gmail.com" class="contact-item">
                            <i class="bi bi-envelope-fill"></i>
                            <span>sneakerkultura@gmail.com</span>
                        </a>
                        <a href="https://t.me/sneakerheadbyweb_bot" target="_blank" class="contact-item">
                            <i class="bi bi-telegram"></i>
                            <span>@sneakerheadbyweb_bot</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="footer-bottom">
        <div class="container">
            <!-- Реквизиты компании -->
            <div class="footer-legal">
                <p>Общество с ограниченной ответственностью «СникерКультура». УНП 193618972, зарегистрировано 15 марта 2022 года Минским Горисполкомом.</p>
                <p>Юридический адрес: Беларусь, 220004, г.Минск, пр-т Победителей 5 (БЦ «Александровский»), офис 9. Время приема заявок - круглосуточно.</p>
                <p>Магазин зарегистрирован в торговом реестре 11.08.2022 №539453. <a href="/site/privacy">Политика конфиденциальности</a></p>
            </div>
            
            <div class="footer-bottom-content">
                <p>&copy; <?= date('Y') ?> СНИКЕРХЭД</p>
            </div>
        </div>
    </div>
</footer>

<style>
/* ============================================
   PREMIUM E-COMMERCE HEADER STYLES
   ============================================ */

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
  width: 100%;
  max-width: 1920px;
  padding: 0 1rem;
  margin: 0 auto;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 2rem;
}

/* Десктоп - ограничиваем ширину */
@media (min-width: 1280px) {
  .main-header .container {
    width: 80%;
  }
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

.logo-image {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 64px;
  height: 64px;
}

.logo-image img {
  display: block;
  max-width: 100%;
  max-height: 100%;
  object-fit: contain;
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
  display: flex;
  flex-direction: column;
  gap: 1px;
  font-size: 0.625rem;
  font-weight: 600;
  line-height: 1.1;
  letter-spacing: 0.8px;
}

.logo-text small .line-1 {
  color: #111;
  text-transform: uppercase;
  font-weight: 700;
  font-size: 0.65rem;
}

.logo-text small .line-2 {
  color: #666;
  text-transform: lowercase;
  font-weight: 500;
  font-size: 0.6rem;
  font-style: italic;
  letter-spacing: 1px;
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
  width: 100%;
  max-width: 1920px;
  padding: 0 1rem;
  margin: 0 auto;
}

/* Десктоп - ограничиваем ширину */
@media (min-width: 1280px) {
  .main-nav .container {
    width: 80%;
  }
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

@media (max-width: 768px) {
  .main-header {
    padding: 0.75rem 0;
  }
  
  .main-header .container {
    width: 100%;
    padding: 0 0.75rem;
    gap: 0.75rem;
  }
  
  .logo {
    font-size: 1rem;
    gap: 0.375rem;
  }
  
  .logo-icon {
    font-size: 1.5rem;
  }
  
  .logo-text strong {
    font-size: 1rem;
  }
  
  .logo-text small .line-1 {
    font-size: 0.6rem;
  }
  
  .logo-text small .line-2 {
    font-size: 0.575rem;
  }
  
  .header-actions {
    gap: 0.5rem;
  }
  
  .header-btn i {
    font-size: 1.375rem;
  }
}

@media (max-width: 640px) {
  .header-search {
    display: none;
  }
  
  .main-header {
    padding: 0.625rem 0;
  }
  
  .main-header .container {
    padding: 0 0.5rem;
    gap: 0.5rem;
  }
  
  .header-left {
    gap: 0.5rem;
  }
  
  .menu-burger {
    padding: 0.25rem;
  }
  
  .menu-burger span {
    width: 20px;
  }
  
  .logo {
    font-size: 0.875rem;
  }
  
  .logo-icon {
    font-size: 1.25rem;
  }
  
  .logo-text strong {
    font-size: 0.875rem;
  }
  
  .logo-text small .line-1 {
    font-size: 0.575rem;
    letter-spacing: 0.5px;
  }
  
  .logo-text small .line-2 {
    font-size: 0.55rem;
  }
  
  .header-actions {
    gap: 0.375rem;
  }
  
  .header-btn i {
    font-size: 1.25rem;
    margin-bottom: 0;
  }
  
  .header-btn .badge {
    width: 16px;
    height: 16px;
    font-size: 0.5625rem;
    top: -2px;
    right: -6px;
  }
}

@media (max-width: 390px) {
  .main-header .container {
    padding: 0 0.375rem;
    gap: 0.375rem;
  }
  
  .logo-icon {
    font-size: 1.125rem;
  }
  
  .logo-image {
    width: 48px;
    height: 48px;
  }
  
  .logo-text strong {
    font-size: 0.75rem;
  }
  
  .logo-text small .line-1 {
    font-size: 0.5rem;
  }
  
  .logo-text small .line-2 {
    font-size: 0.475rem;
  }
  
  .header-actions {
    gap: 0.25rem;
  }
  
  .header-btn i {
    font-size: 1.125rem;
  }
}

/* ============================================
   ULTRA COMPACT FOOTER (2 COLUMNS)
   ============================================ */

.site-footer {
  background: #0f0f0f;
  color: #fff;
  margin-top: 2rem;
  border-top: 1px solid #2d2d2d;
}

.footer-main {
  padding: 1rem 0 0.75rem;
}

.footer-main .container {
  width: 80%;
  max-width: 1920px;
  margin: 0 auto;
  padding: 0 1rem;
}

/* 2 колонки */
.footer-content {
  display: grid;
  grid-template-columns: 1.5fr 1fr;
  gap: 2rem;
  align-items: center;
}

/* Левая колонка */
.footer-col-left {
  display: flex;
  flex-direction: column;
  gap: 0.625rem;
}

/* Брендовый блок */
.footer-brand {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.footer-logo {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.footer-logo-image {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 48px;
  height: 48px;
  flex-shrink: 0;
}

.footer-logo-image img {
  display: block;
  max-width: 100%;
  max-height: 100%;
  object-fit: contain;
}

.footer-logo-text {
  display: flex;
  flex-direction: column;
  gap: 0.125rem;
}

.footer-logo-text strong {
  font-size: 0.875rem;
  font-weight: 900;
  color: #fff;
  letter-spacing: 0.5px;
}

.footer-logo-text small {
  font-size: 0.625rem;
  font-weight: 500;
  color: rgba(255, 255, 255, 0.7);
  line-height: 1.2;
  letter-spacing: -0.3px;
}

.footer-social {
  display: flex;
  gap: 0.25rem;
  align-items: center;
}

.social-link {
  width: 28px;
  height: 28px;
  border-radius: 4px;
  background: rgba(255,255,255,0.05);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #9ca3af;
  font-size: 0.8125rem;
  transition: all 0.15s;
  text-decoration: none;
  border: 1px solid rgba(255,255,255,0.06);
}

.social-link:hover {
  background: #3b82f6;
  color: #fff;
  border-color: #3b82f6;
}

.social-divider {
  color: #4b5563;
  font-size: 0.75rem;
  margin: 0 0.25rem;
}

.payment-badge {
  background: rgba(255,255,255,0.08);
  color: #9ca3af;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-size: 0.625rem;
  font-weight: 700;
  letter-spacing: 0.5px;
  text-transform: uppercase;
  border: 1px solid rgba(255,255,255,0.08);
  transition: all 0.2s;
}

.payment-badge:hover {
  background: rgba(255,255,255,0.12);
  color: #fff;
  border-color: rgba(255,255,255,0.15);
}

/* Навигация в одну строку */
.footer-nav {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  align-items: center;
}

.footer-nav a {
  color: #9ca3af;
  text-decoration: none;
  font-size: 0.75rem;
  transition: color 0.15s;
  padding: 0.125rem 0;
}

.footer-nav a:hover {
  color: #fff;
}

.footer-nav .nav-divider {
  color: #4b5563;
  font-size: 0.6875rem;
  margin: 0 0.125rem;
}

/* Правая колонка - контакты */
.footer-col-right {
  display: flex;
  justify-content: flex-end;
}

.footer-contacts {
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
}

.contact-item {
  display: flex;
  align-items: center;
  gap: 0.375rem;
  color: #9ca3af;
  text-decoration: none;
  font-size: 0.6875rem;
  transition: color 0.15s;
}

.contact-item i {
  color: #3b82f6;
  font-size: 0.75rem;
  width: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.contact-item:hover {
  color: #fff;
}

/* Нижний блок */
.footer-bottom {
  border-top: 1px solid rgba(255,255,255,0.06);
  padding: 0.625rem 0;
  margin-top: 0.75rem;
}

.footer-bottom .container {
  width: 80%;
  max-width: 1920px;
  margin: 0 auto;
  padding: 0 1rem;
}

/* Реквизиты компании */
.footer-legal {
  margin-bottom: 0.5rem;
  padding-bottom: 0.5rem;
  border-bottom: 1px solid rgba(255,255,255,0.04);
}

.footer-legal p {
  margin: 0;
  color: #6b7280;
  font-size: 0.625rem;
  line-height: 1.4;
  margin-bottom: 0.125rem;
}

.footer-legal p:last-child {
  margin-bottom: 0;
}

.footer-legal a {
  color: #9ca3af;
  text-decoration: underline;
  transition: color 0.15s;
}

.footer-legal a:hover {
  color: #fff;
}

/* Копирайт и платежи */
.footer-bottom-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.footer-bottom-content > p {
  margin: 0;
  color: #6b7280;
  font-size: 0.6875rem;
  font-weight: 500;
}

/* Адаптивность */
@media (max-width: 1024px) {
  .footer-content {
    grid-template-columns: 1fr;
    gap: 1rem;
  }
  
  .footer-col-right {
    justify-content: flex-start;
  }
}

@media (max-width: 768px) {
  .site-footer {
    margin-top: 1.5rem;
  }
  
  .footer-main {
    padding: 0.875rem 0 0.625rem;
  }
  
  .footer-main .container,
  .footer-bottom .container {
    width: 100%;
  }
  
  .footer-brand {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.5rem;
  }
  
  .footer-nav {
    gap: 0.375rem;
  }
  
  .footer-bottom-content {
    flex-direction: column;
    gap: 0.5rem;
    text-align: center;
  }
  
  .footer-legal p {
    font-size: 0.5625rem;
  }
  
  .footer-bottom {
    padding: 0.5rem 0;
    margin-top: 0.5rem;
  }
}

@media (max-width: 640px) {
  .footer-nav {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.25rem;
  }
  
  .footer-nav .nav-divider {
    display: none;
  }
  
  .contact-item {
    font-size: 0.625rem;
  }
  
  .footer-legal p {
    font-size: 0.5rem;
  }
  
  .footer-col-left {
    gap: 0.5rem;
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

<!-- ДОБАВЛЕНО: Глобальная система уведомлений -->
<script src="<?= Yii::$app->request->baseUrl ?>/js/notifications.js"></script>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
