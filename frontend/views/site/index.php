<?php
/* @var $this yii\web\View */
/* @var $popularProducts array */

use yii\helpers\Html;

$this->title = 'Купить оригинальные кроссовки в Беларуси — СНИКЕРХЭД';
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="hero-content">
        <div class="hero-text">
            <h1>Оригинальные кроссовки в Беларуси</h1>
            <p>100% подлинность Nike, Adidas, Jordan. Доставка по всей Беларуси.</p>
            <div class="hero-actions">
                <a href="/catalog" class="btn btn-primary btn-hero-primary">Перейти в каталог</a>
                <a href="/brands" class="btn btn-secondary btn-hero-secondary">Все бренды</a>
            </div>
            <div class="hero-stats">
                <div class="stat-item">
                    <span class="stat-number">1000+</span>
                    <span class="stat-label">Товаров</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">100%</span>
                    <span class="stat-label">Оригинал</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">1–3 дня</span>
                    <span class="stat-label">Доставка</span>
                </div>
            </div>
        </div>
        <div class="hero-image">
            <img src="/images/hero-sneakers.jpg"
                 alt="Оригинальные кроссовки — купить в Беларуси"
                 loading="eager"
                 fetchpriority="high">
        </div>
    </div>
</section>

<!-- Popular Products -->
<?php if (!empty($popularProducts)): ?>
<section class="popular-section">
    <div class="section-header">
        <h2 class="section-title">Популярные товары</h2>
        <a href="/catalog" class="btn btn-secondary">Смотреть все</a>
    </div>
    <div class="products-grid" style="max-width: var(--container-xl); margin: 0 auto; padding: 0 var(--spacing-4);">
        <?php foreach ($popularProducts as $product): ?>
        <div class="product product-card modern-card">
            <div class="product-image-wrapper">
                <a href="<?= $product->getUrl() ?>" class="product-link">
                    <div class="product-image-slider">
                        <?php if ($product->main_image_url): ?>
                        <img
                            class="product-image is-active primary"
                            src="<?= Html::encode($product->main_image_url) ?>"
                            alt="<?= Html::encode($product->name . ' ' . ($product->brand_name ?? '')) ?> — купить в Беларуси"
                            loading="lazy"
                            decoding="async">
                        <?php endif; ?>
                    </div>
                </a>
            </div>
            <a href="<?= $product->getUrl() ?>" class="product-link">
                <div class="info product-card-body">
                    <div class="product-card-header">
                        <?php if ($product->brand_name): ?>
                            <span class="product-card-brand"><?= Html::encode($product->brand_name) ?></span>
                        <?php endif; ?>
                    </div>
                    <h3 class="product-card-name"><?= Html::encode($product->name) ?></h3>
                    <div class="price product-card-price">
                        <span class="current product-card-price-current">
                            <?= Yii::$app->formatter->asCurrency($product->price, 'BYN') ?>
                        </span>
                    </div>
                </div>
            </a>
            <div class="product-footer">
                <a href="<?= $product->getUrl() ?>" class="btn-add-to-cart">
                    <i class="bi bi-arrow-right"></i>
                    <span>Подробнее</span>
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- Benefits Section -->
<section class="benefits-section">
    <div class="benefits-grid">
        <div class="benefit-item">
            <div class="benefit-icon">
                <i class="bi bi-shield-check"></i>
            </div>
            <h4 class="benefit-title">100% оригинал</h4>
            <p class="benefit-text">Гарантируем подлинность каждого товара. Все позиции проходят проверку подлинности.</p>
        </div>
        <div class="benefit-item">
            <div class="benefit-icon">
                <i class="bi bi-truck"></i>
            </div>
            <h4 class="benefit-title">Быстрая доставка</h4>
            <p class="benefit-text">Доставка по всей Беларуси за 1–3 рабочих дня. Самовывоз в Минске.</p>
        </div>
        <div class="benefit-item">
            <div class="benefit-icon">
                <i class="bi bi-headset"></i>
            </div>
            <h4 class="benefit-title">Поддержка</h4>
            <p class="benefit-text">Помогаем выбрать размер и модель. Консультируем ежедневно с 10:00 до 20:00.</p>
        </div>
    </div>
</section>
