<?php

/**
 * Landing Page - Главная страница
 * 
 * Секции:
 * - Hero с УТП
 * - Популярные товары
 * - Категории
 * - Бренды
 * - Преимущества
 * - Отзывы
 * - Instagram
 * - Рассылка
 */

use yii\helpers\Html;
use yii\helpers\Url;
use app\frontend\assets\AppAsset;

$this->title = 'СНИКЕРХЭД — Оригинальные кроссовки из США и Европы';

// Подключаем минималистичный AssetBundle (единый дизайн 100/100)
AppAsset::register($this);

// Получаем данные
$popularProducts = $this->params['popularProducts'] ?? [];
$categories = $this->params['categories'] ?? [];
$brands = $this->params['brands'] ?? [];
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="hero-background">
        <div class="hero-gradient"></div>
    </div>
    
    <div class="container">
        <div class="hero-content">
            <div class="hero-badge">
                <span class="badge-icon"><i class="bi bi-fire"></i></span>
                <span>Новая коллекция 2024</span>
            </div>
            
            <h1 class="hero-title">
                Оригинальные кроссовки<br>
                <span class="hero-title-accent">из США и Европы</span>
            </h1>
            
            <p class="hero-subtitle">
                Только оригинальная обувь от официальных поставщиков. 
                Гарантия подлинности на каждый товар.
            </p>
            
            <div class="hero-actions">
                <a href="/catalog" class="btn-hero-primary">
                    <span>Смотреть каталог</span>
                    <i class="bi bi-arrow-right"></i>
                </a>
                <a href="#popular" class="btn-hero-secondary">
                    <span>Популярные модели</span>
                </a>
            </div>
            
            <div class="hero-stats">
                <div class="stat-item">
                    <div class="stat-value">10,000+</div>
                    <div class="stat-label">Довольных клиентов</div>
                </div>
                <div class="stat-divider"></div>
                <div class="stat-item">
                    <div class="stat-value">5,000+</div>
                    <div class="stat-label">Моделей обуви</div>
                </div>
                <div class="stat-divider"></div>
                <div class="stat-item">
                    <div class="stat-value">100%</div>
                    <div class="stat-label">Оригинал</div>
                </div>
            </div>
        </div>
        
        <div class="hero-image">
            <img src="/images/hero-sneakers.svg" alt="Кроссовки" class="hero-sneakers-img">
            <div class="hero-floating-card card-1">
                <i class="bi bi-shield-check"></i>
                <span>100% оригинал</span>
            </div>
            <div class="hero-floating-card card-2">
                <i class="bi bi-truck"></i>
                <span>Быстрая доставка</span>
            </div>
        </div>
    </div>
</section>

<!-- Popular Products -->
<section id="popular" class="popular-section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Популярные модели</h2>
            <a href="/catalog?sort=popular" class="section-link">
                Смотреть все <i class="bi bi-arrow-right"></i>
            </a>
        </div>
        
        <div class="products-grid">
            <?php foreach ($popularProducts as $product): ?>
            <div class="product-card">
                <div class="product-image">
                    <img src="<?= $product->getMainImageUrl() ?>" alt="<?= Html::encode($product->name) ?>">
                    <?php if ($product->isNew): ?>
                        <span class="product-badge badge-new">Новинка</span>
                    <?php endif; ?>
                </div>
                <div class="product-info">
                    <div class="product-brand"><?= Html::encode($product->brand->name) ?></div>
                    <h3 class="product-name"><?= Html::encode($product->name) ?></h3>
                    <div class="product-price">
                        <span class="current-price"><?= Yii::$app->formatter->asCurrency($product->price, 'BYN') ?></span>
                    </div>
                </div>
                <a href="<?= $product->getUrl() ?>" class="product-link"></a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Categories -->
<section class="categories-section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Категории</h2>
        </div>
        
        <div class="categories-grid">
            <?php foreach ($categories as $category): ?>
            <a href="<?= $category->getUrl() ?>" class="category-card">
                <div class="category-image">
                    <img src="<?= $category->getImageUrl() ?>" alt="<?= Html::encode($category->name) ?>">
                </div>
                <div class="category-overlay">
                    <h3 class="category-name"><?= Html::encode($category->name) ?></h3>
                    <span class="category-count"><?= $category->products_count ?> товаров</span>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Brands -->
<section class="brands-section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Популярные бренды</h2>
            <a href="/brands" class="section-link">
                Все бренды <i class="bi bi-arrow-right"></i>
            </a>
        </div>
        
        <div class="brands-grid">
            <?php foreach ($brands as $brand): ?>
            <a href="/catalog?brand=<?= $brand->slug ?>" class="brand-card">
                <img src="<?= $brand->getLogoUrl() ?>" alt="<?= Html::encode($brand->name) ?>">
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Benefits -->
<section class="benefits-section">
    <div class="container">
        <div class="section-header center">
            <h2 class="section-title">Почему выбирают нас</h2>
        </div>
        
        <div class="benefits-grid">
            <div class="benefit-card">
                <div class="benefit-icon">
                    <i class="bi bi-shield-check"></i>
                </div>
                <h3 class="benefit-title">100% оригинал</h3>
                <p class="benefit-text">
                    Только оригинальная обувь от официальных поставщиков. 
                    Гарантия подлинности на каждый товар.
                </p>
            </div>
            
            <div class="benefit-card">
                <div class="benefit-icon">
                    <i class="bi bi-truck"></i>
                </div>
                <h3 class="benefit-title">Быстрая доставка</h3>
                <p class="benefit-text">
                    Доставка по Минску 1-2 дня, по Беларуси 3-5 дней. 
                    Бесплатно от 100 BYN.
                </p>
            </div>
            
            <div class="benefit-card">
                <div class="benefit-icon">
                    <i class="bi bi-arrow-repeat"></i>
                </div>
                <h3 class="benefit-title">14 дней на возврат</h3>
                <p class="benefit-text">
                    Не подошёл товар? Вернём деньги в течение 14 дней 
                    без лишних вопросов.
                </p>
            </div>
            
            <div class="benefit-card">
                <div class="benefit-icon">
                    <i class="bi bi-credit-card"></i>
                </div>
                <h3 class="benefit-title">Безопасная оплата</h3>
                <p class="benefit-text">
                    Принимаем карты, наличные, Халву. 
                    Возможна рассрочка.
                </p>
            </div>
            
            <div class="benefit-card">
                <div class="benefit-icon">
                    <i class="bi bi-star"></i>
                </div>
                <h3 class="benefit-title">Программа лояльности</h3>
                <p class="benefit-text">
                    Баллы за каждую покупку. Скидки до 10% 
                    для постоянных клиентов.
                </p>
            </div>
            
            <div class="benefit-card">
                <div class="benefit-icon">
                    <i class="bi bi-headset"></i>
                </div>
                <h3 class="benefit-title">Поддержка 24/7</h3>
                <p class="benefit-text">
                    Всегда на связи. Ответим на любой вопрос 
                    в течение 15 минут.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Newsletter -->
<section class="newsletter-section">
    <div class="container">
        <div class="newsletter-content">
            <div class="newsletter-text">
                <h2 class="newsletter-title">Подпишитесь на новости</h2>
                <p class="newsletter-subtitle">
                    Получайте эксклюзивные скидки и узнавайте о новинках первыми
                </p>
            </div>
            
            <form class="newsletter-form" onsubmit="subscribeNewsletter(event)">
                <input type="email" placeholder="Ваш email" required class="newsletter-input">
                <button type="submit" class="newsletter-btn">
                    Подписаться
                </button>
            </form>
            
            <p class="newsletter-note">
                Нажимая кнопку, вы соглашаетесь с 
                <a href="/privacy">политикой конфиденциальности</a>
            </p>
        </div>
    </div>
</section>

<script>
function subscribeNewsletter(e) {
    e.preventDefault();
    const form = e.target;
    const email = form.querySelector('input').value;
    
    // AJAX запрос
    fetch('/api/v1/newsletter/subscribe', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({email: email})
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            form.innerHTML = '<div class="newsletter-success">✓ Вы подписаны!</div>';
        }
    });
}
</script>
