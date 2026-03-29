<?php

/** @var yii\web\View $this */
/** @var string $content */

use app\assets\PublicAsset;
use yii\bootstrap5\Html;

PublicAsset::register($this);

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
    
    <!-- Preconnect to external CDNs for faster image loading -->
    <link rel="preconnect" href="https://cdn.poizon.com" crossorigin>
    <link rel="preconnect" href="https://cdn.dewu.com" crossorigin>
    <link rel="preconnect" href="https://du.hupucdn.com" crossorigin>
    <link rel="dns-prefetch" href="https://cdn.poizon.com">
    <link rel="dns-prefetch" href="https://cdn.dewu.com">
    <link rel="dns-prefetch" href="https://du.hupucdn.com">
    
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
    
</head>
<body class="d-flex flex-column h-100 store-theme">
<?php $this->beginBody() ?>

<!-- КРИТИЧНО: гарантируем видимость хедера до загрузки CSS -->
<style>
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
    
    /* Аватар пользователя в хедере */
    .header-btn-user {
        background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%) !important;
        border-radius: 12px !important;
        padding: 0.5rem 0.75rem !important;
    }
    .header-btn-user:hover {
        background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%) !important;
    }
    .user-avatar-mini {
        width: 28px;
        height: 28px;
        background: linear-gradient(135deg, #3b82f6, #1d4ed8);
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-weight: 700;
        font-size: 0.75rem;
    }
    @media (max-width: 768px) {
        .header-btn-user .label { display: none !important; }
        .header-btn-user { padding: 0.375rem !important; }
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
                <?php
                $customerId = Yii::$app->session->get('customer_id');
                $customerName = Yii::$app->session->get('customer_name');
                if ($customerId):
                ?>
                <a href="/account/profile" class="header-btn header-btn-user">
                    <div class="user-avatar-mini"><?= mb_strtoupper(mb_substr($customerName ?: 'U', 0, 1)) ?></div>
                    <span class="label"><?= Yii::$app->formatter->asText(mb_substr($customerName ?: 'Профиль', 0, 10)) ?></span>
                </a>
                <?php else: ?>
                <a href="/account/login" class="header-btn">
                    <i class="bi bi-person"></i>
                    <span class="label">Войти</span>
                </a>
                <?php endif; ?>
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
            <a href="/account/profile" class="mobile-action-btn">
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

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
