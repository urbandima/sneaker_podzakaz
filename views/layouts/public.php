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
    
    <!-- 
        Метатеги (SEO, Open Graph, Twitter Cards) регистрируются в контроллерах
        через метод registerMetaTags() и выводятся автоматически в $this->head()
    -->
    
    <?php
    /**
     * Schema.org JSON-LD микроразметка
     * Автоматически генерируется в CatalogController
     */
    if (isset($this->params['jsonLdSchemas']) && is_array($this->params['jsonLdSchemas'])) {
        foreach ($this->params['jsonLdSchemas'] as $key => $jsonLd) {
            echo "\n    <!-- Schema.org: " . $key . " -->\n";
            echo '    <script type="application/ld+json">' . "\n";
            echo $jsonLd . "\n";
            echo '    </script>' . "\n";
        }
    }
    ?>
    
    <style>
        * {
            box-sizing: border-box;
        }
        
        html {
            overflow-x: hidden;
        }
        
        body {
            background: #ffffff;
            min-height: 100vh;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        
        /* Container управляется через container-system.css (1400px) */
        /* Удалены переопределения max-width и padding */
        
        @media (max-width: 768px) {
            body {
                font-size: 14px;
            }
        }
    </style>
</head>
<body class="d-flex flex-column h-100">
<?php $this->beginBody() ?>

<!-- КРИТИЧНО: Гарантируем видимость хедера через inline стили -->
<style>
    /* Эти стили ОБЯЗАТЕЛЬНО применятся первыми */
    header.ecom-header,
    .ecom-header,
    .main-header {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
        position: relative !important;
        z-index: 1000 !important;
        width: 100% !important;
        overflow: visible !important;
    }
    
    .ecom-header .main-header {
        position: sticky !important;
        top: 0 !important;
        background: #fff !important;
    }
    
    /* Убираем любые скрывающие стили */
    body > header:first-of-type {
        display: block !important;
    }
</style>

<!-- PREMIUM E-COMMERCE HEADER -->
<header class="ecom-header" style="display: block !important; visibility: visible !important; opacity: 1 !important;">
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
        <button class="menu-close" id="menuClose">
            <i class="bi bi-x"></i>
        </button>
        <div class="mobile-menu-logo">
            <strong>СНИКЕРХЭД</strong>
            <small>Оригинальные товары под заказ</small>
        </div>
    </div>
    
    <div class="mobile-menu-content">
        <!-- Поиск (сверху) -->
        <div class="mobile-search">
            <i class="bi bi-search"></i>
            <input type="text" placeholder="Поиск товаров, брендов..." id="mobileSearch">
        </div>
        
        <!-- Быстрые фильтры -->
        <div class="mobile-quick-filters">
            <a href="/catalog?discount=50" class="filter-chip">
                <i class="bi bi-percent"></i> Скидка 50%+
            </a>
            <a href="/catalog?price=0-5000" class="filter-chip">
                <i class="bi bi-wallet2"></i> До 5000₽
            </a>
            <a href="/catalog?instock=1" class="filter-chip">
                <i class="bi bi-check-circle"></i> В наличии
            </a>
            <a href="/catalog?new=1" class="filter-chip">
                <i class="bi bi-star-fill"></i> Новинки
            </a>
        </div>
        
        <!-- Быстрые действия -->
        <div class="mobile-quick-actions">
            <a href="/catalog/favorites" class="mobile-action-btn">
                <i class="bi bi-heart"></i>
                <span class="action-label">Избранное</span>
                <span class="action-badge" id="mobileFavCount">0</span>
            </a>
            <a href="/cart" class="mobile-action-btn">
                <i class="bi bi-bag"></i>
                <span class="action-label">Корзина</span>
                <span class="action-badge" id="mobileCartCount">0</span>
            </a>
            <a href="/catalog/history" class="mobile-action-btn">
                <i class="bi bi-clock-history"></i>
                <span class="action-label">История</span>
            </a>
            <a href="/site/account" class="mobile-action-btn">
                <i class="bi bi-person"></i>
                <span class="action-label">Профиль</span>
            </a>
        </div>
        
        <!-- Навигация -->
        <div class="mobile-nav-section">
            <div class="mobile-nav-section-title">Основное меню</div>
        </div>
        
        <ul class="mobile-nav">
            <!-- Каталог с подкатегориями -->
            <li class="mobile-nav-item has-submenu" data-id="catalog">
                <a href="#" class="mobile-nav-toggle">
                    <i class="bi bi-grid-3x3-gap"></i> Каталог
                    <i class="bi bi-chevron-down chevron"></i>
                </a>
                <ul class="mobile-submenu">
                    <li><a href="/catalog?cat=sneakers">Кроссовки <span class="item-count">245</span></a></li>
                    <li><a href="/catalog?cat=boots">Ботинки <span class="item-count">128</span></a></li>
                    <li><a href="/catalog?cat=sandals">Сандалии <span class="item-count">89</span></a></li>
                    <li><a href="/catalog?cat=slippers">Слипоны <span class="item-count">67</span></a></li>
                    <li><a href="/catalog?cat=tshirts">Футболки <span class="item-count">156</span></a></li>
                    <li><a href="/catalog?cat=hoodies">Толстовки <span class="item-count">98</span></a></li>
                    <li><a href="/catalog?cat=jackets">Куртки <span class="item-count">74</span></a></li>
                    <li><a href="/catalog?cat=accessories">Аксессуары <span class="item-count">112</span></a></li>
                </ul>
            </li>
            
            <!-- Мужское с подкатегориями -->
            <li class="mobile-nav-item has-submenu" data-id="male">
                <a href="#" class="mobile-nav-toggle">
                    <i class="bi bi-gender-male"></i> Мужское
                    <i class="bi bi-chevron-down chevron"></i>
                </a>
                <ul class="mobile-submenu">
                    <li><a href="/catalog?gender=male&cat=sneakers">Кроссовки <span class="item-count">152</span></a></li>
                    <li><a href="/catalog?gender=male&cat=boots">Ботинки <span class="item-count">78</span></a></li>
                    <li><a href="/catalog?gender=male&cat=sandals">Сандалии <span class="item-count">45</span></a></li>
                    <li><a href="/catalog?gender=male&cat=tshirts">Футболки <span class="item-count">89</span></a></li>
                    <li><a href="/catalog?gender=male&cat=hoodies">Толстовки <span class="item-count">56</span></a></li>
                    <li><a href="/catalog?gender=male&cat=jackets">Куртки <span class="item-count">43</span></a></li>
                </ul>
            </li>
            
            <!-- Женское с подкатегориями -->
            <li class="mobile-nav-item has-submenu" data-id="female">
                <a href="#" class="mobile-nav-toggle">
                    <i class="bi bi-gender-female"></i> Женское
                    <i class="bi bi-chevron-down chevron"></i>
                </a>
                <ul class="mobile-submenu">
                    <li><a href="/catalog?gender=female&cat=sneakers">Кроссовки <span class="item-count">93</span></a></li>
                    <li><a href="/catalog?gender=female&cat=boots">Ботинки <span class="item-count">50</span></a></li>
                    <li><a href="/catalog?gender=female&cat=sandals">Сандалии <span class="item-count">44</span></a></li>
                    <li><a href="/catalog?gender=female&cat=tshirts">Футболки <span class="item-count">67</span></a></li>
                    <li><a href="/catalog?gender=female&cat=hoodies">Толстовки <span class="item-count">42</span></a></li>
                    <li><a href="/catalog?gender=female&cat=dresses">Платья <span class="item-count">31</span></a></li>
                </ul>
            </li>
            
            <!-- Улучшенные пункты -->
            <li class="mobile-nav-item featured-new">
                <a href="/catalog?new=1">
                    <i class="bi bi-star-fill"></i> Новинки
                </a>
            </li>
            <li class="mobile-nav-item featured-sale">
                <a href="/catalog?sale=1">
                    <i class="bi bi-fire"></i> Распродажа
                </a>
            </li>
        </ul>
        
        <!-- Информация -->
        <div class="mobile-nav-section">
            <div class="mobile-nav-section-title">Информация</div>
        </div>
        
        <ul class="mobile-nav mobile-nav-info">
            <li class="mobile-nav-item">
                <a href="/site/about">
                    <i class="bi bi-info-circle"></i> О нас
                </a>
            </li>
            <li class="mobile-nav-item">
                <a href="/site/contacts">
                    <i class="bi bi-envelope"></i> Контакты
                </a>
            </li>
        </ul>
        
        <!-- Номер телефона -->
        <a href="tel:+375447009001" class="mobile-contact-btn">
            <i class="bi bi-telephone-fill"></i>
            <span>+375 (44) 700-90-01</span>
        </a>
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

.ecom-header {
  width: 100%;
  overflow-x: hidden;
  position: relative;
  z-index: 1000;
  display: block !important;
  visibility: visible !important;
  opacity: 1 !important;
}

/* Main Header */
.main-header {
  background: #fff;
  border-bottom: 1px solid #e5e7eb;
  padding: 1rem 0;
  position: sticky !important;
  top: 0 !important;
  z-index: 1000 !important;
  box-shadow: 0 2px 8px rgba(0,0,0,0.04);
  width: 100%;
  overflow-x: hidden;
  display: block !important;
  visibility: visible !important;
  opacity: 1 !important;
}

.main-header .container {
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
  width: 100%;
  overflow-x: hidden;
  display: block !important;
  visibility: visible !important;
  opacity: 1 !important;
  position: relative;
  z-index: 999;
}

.main-nav .container {
  /* Ширина и padding управляются через container-system.css */
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

/* Mobile Menu стили перенесены в /web/css/mobile-menu-premium.css 
   для единой системы дизайна */

/* Адаптивные стили теперь в /web/css/header-adaptive.css 
   Здесь оставлены только базовые inline стили для критичных элементов */

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
  /* Ширина управляется через container-system.css */
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
  /* Ширина управляется через container-system.css */
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
// Mobile menu logic moved to web/js/mobile-menu.js to avoid duplicate listeners

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
