<?php
/* @var $this yii\web\View */
/* @var $popularProducts array */
/* @var int $productCount */

use yii\helpers\Html;

$this->title = 'Купить оригинальные кроссовки в Беларуси — СНИКЕРХЭД';
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="hero-content">
        <div class="hero-text">
            <h1>Оригинальные товары: обувь и одежда</h1>
            <p>100% подлинность Nike, Adidas, Jordan и других брендов. Доставка по всей Беларуси.</p>
            <div class="hero-actions">
                <a href="/catalog" class="btn btn-primary btn-hero-primary">Перейти в каталог</a>
                <a href="/brands" class="btn btn-secondary btn-hero-secondary">Все бренды</a>
            </div>
            <div class="hero-stats">
                <div class="stat-item">
                    <span class="stat-number"><?= $productCount > 0 ? $productCount . '+' : '1000+' ?></span>
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
            <img src="/images/hero-sneakers.svg"
                 alt="Оригинальные кроссовки — купить в Беларуси"
                 loading="eager"
                 fetchpriority="high">
        </div>
    </div>
</section>

<!-- Promo Banner -->
<section class="promo-banner-section">
    <div class="promo-banner">
        <div class="promo-content">
            <span class="promo-badge">Акция</span>
            <h2 class="promo-title">Скидки до 40% на зимнюю коллекцию</h2>
            <p class="promo-text">Успейте купить оригинальные кроссовки по лучшим ценам. Предложение ограничено!</p>
            <a href="/sale" class="btn btn-primary promo-cta">Смотреть акции <i class="bi bi-arrow-right"></i></a>
        </div>
        <div class="promo-visual">
            <img src="/images/promo-banner.svg" alt="Скидки на кроссовки" loading="lazy"
                 onerror="this.parentElement.innerHTML='<div style=&quot;width:200px;height:200px;background:linear-gradient(135deg,#667eea,#764ba2);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:2.5rem;font-weight:800&quot;>-40%</div>'">
        </div>
    </div>
</section>
<style>
.promo-banner-section { padding: 0 var(--spacing-4, 16px); max-width: var(--container-xl, 1280px); margin: 0 auto 48px; }
.promo-banner {
    display: flex; align-items: center; justify-content: space-between; gap: 32px;
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    border-radius: 20px; padding: 40px 48px; color: #fff; overflow: hidden;
}
.promo-content { flex: 1; }
.promo-badge {
    display: inline-block; background: #ef4444; color: #fff; font-size: 12px; font-weight: 700;
    padding: 4px 12px; border-radius: 20px; margin-bottom: 12px; text-transform: uppercase; letter-spacing: .5px;
}
.promo-title { font-size: 28px; font-weight: 800; margin: 0 0 8px; line-height: 1.2; }
.promo-text { color: #94a3b8; margin-bottom: 20px; font-size: 15px; }
.promo-cta {
    display: inline-flex; align-items: center; gap: 8px; background: #fff; color: #0f172a;
    padding: 12px 24px; border-radius: 10px; font-weight: 600; text-decoration: none; transition: transform .15s;
}
.promo-cta:hover { transform: translateY(-2px); }
.promo-visual img { max-width: 220px; height: auto; }
@media (max-width: 768px) {
    .promo-banner { flex-direction: column; text-align: center; padding: 28px 24px; }
    .promo-title { font-size: 22px; }
    .promo-visual { order: -1; }
    .promo-visual img { max-width: 160px; }
}
</style>

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

<!-- New Arrivals -->
<?php if (!empty($newArrivals ?? [])): ?>
<section class="new-arrivals-section">
    <div class="section-header">
        <h2 class="section-title">Новинки</h2>
        <a href="/catalog?sort=new" class="btn btn-secondary">Все новинки</a>
    </div>
    <div class="products-grid" style="max-width: var(--container-xl); margin: 0 auto; padding: 0 var(--spacing-4);">
        <?php foreach (array_slice($newArrivals, 0, 8) as $product): ?>
        <div class="product product-card modern-card">
            <div class="product-image-wrapper">
                <a href="<?= $product->getUrl() ?>" class="product-link">
                    <div class="product-image-slider">
                        <?php if ($product->main_image_url): ?>
                        <img class="product-image is-active primary"
                             src="<?= Html::encode($product->main_image_url) ?>"
                             alt="<?= Html::encode($product->name) ?>"
                             loading="lazy" decoding="async">
                        <?php endif; ?>
                    </div>
                </a>
                <span class="badge-new">Новинка</span>
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

<!-- Top Brands -->
<?php if (!empty($topBrands ?? [])): ?>
<section class="brands-section">
    <div class="section-header" style="max-width: var(--container-xl); margin: 0 auto; padding: 0 var(--spacing-4);">
        <h2 class="section-title">Популярные бренды</h2>
        <a href="/brands" class="btn btn-secondary">Все бренды</a>
    </div>
    <div class="brands-grid" style="max-width: var(--container-xl); margin: 0 auto; padding: 0 var(--spacing-4);">
        <?php foreach (array_slice($topBrands, 0, 10) as $brand): ?>
        <a href="/catalog/brand/<?= Html::encode($brand->slug ?? $brand->id) ?>" class="brand-card">
            <?php if (!empty($brand->logo_url)): ?>
                <img src="<?= Html::encode($brand->logo_url) ?>" alt=""
                     loading="lazy" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                <span class="brand-name-fallback" style="display:none"><?= Html::encode($brand->name) ?></span>
            <?php else: ?>
                <span class="brand-name-fallback"><?= Html::encode($brand->name) ?></span>
            <?php endif; ?>
            <span class="brand-label"><?= Html::encode($brand->name) ?></span>
        </a>
        <?php endforeach; ?>
    </div>
</section>
<style>
.brands-section { padding: 48px 0; }
.brands-grid {
    display: grid; grid-template-columns: repeat(5, 1fr); gap: 16px; margin-top: 24px;
}
.brand-card {
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    padding: 24px 16px; background: #fff; border: 1.5px solid #f1f5f9; border-radius: 12px;
    text-decoration: none; color: #1e293b; transition: all .2s;
}
.brand-card:hover { border-color: #111; transform: translateY(-2px); box-shadow: 0 4px 16px rgba(0,0,0,.08); }
.brand-card img { max-height: 40px; max-width: 100px; object-fit: contain; margin-bottom: 8px; }
.brand-name-fallback {
    font-size: 18px; font-weight: 700; display: flex; align-items: center;
    justify-content: center; height: 40px; margin-bottom: 8px;
}
.brand-label { font-size: 13px; color: #64748b; }
.new-arrivals-section { padding: 48px 0; }
.badge-new {
    position: absolute; top: 10px; left: 10px; background: #22c55e; color: #fff;
    font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 6px; z-index: 2;
}
@media (max-width: 768px) {
    .brands-grid { grid-template-columns: repeat(3, 1fr); gap: 10px; }
    .brand-card { padding: 16px 10px; }
}
@media (max-width: 480px) {
    .brands-grid { grid-template-columns: repeat(2, 1fr); }
}
</style>
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
