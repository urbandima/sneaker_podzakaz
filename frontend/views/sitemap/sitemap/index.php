<?php
/* @var $this yii\web\View */
/* @var $popularProducts array */

$this->title = 'Купить оригинальные кроссовки в Беларуси — СНИКЕРХЭД';
?>
<div class="site-index">
    <!-- Hero секция -->
    <div class="hero-section text-center py-5 bg-light">
        <div class="container">
            <h1 class="display-4 fw-bold mb-3">Оригинальные кроссовки в Беларуси</h1>
            <p class="lead mb-4">100% подлинность Nike, Adidas, Jordan. Доставка по всей Беларуси</p>
            <a class="btn btn-primary btn-lg" href="/catalog">Перейти в каталог</a>
        </div>
    </div>
    
    <!-- Популярные товары -->
    <?php if (!empty($popularProducts)): ?>
    <div class="popular-products py-5">
        <div class="container">
            <h2 class="text-center mb-4">Популярные товары</h2>
            <div class="row g-4">
                <?php foreach ($popularProducts as $product): ?>
                <div class="col-md-6 col-lg-3">
                    <div class="card product-card h-100">
                        <?php if ($product->main_image_url): ?>
                        <img src="<?= $product->main_image_url ?>" 
                             class="card-img-top" 
                             alt="<?= Html::encode($product->name . ' ' . ($product->brand_name ?? '')) ?> — купить в Беларуси"
                             loading="lazy"
                             decoding="async">
                        <?php endif; ?>
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title"><?= Html::encode($product->name) ?></h5>
                            <?php if ($product->brand_name): ?>
                            <p class="card-text text-muted small"><?= Html::encode($product->brand_name) ?></p>
                            <?php endif; ?>
                            <div class="mt-auto">
                                <p class="card-text fw-bold text-primary"><?= $product->price ?> BYN</p>
                                <a href="<?= $product->getUrl() ?>" class="btn btn-outline-primary btn-sm">Подробнее</a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="text-center mt-4">
                <a href="/catalog" class="btn btn-outline-primary">Смотреть все товары</a>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Преимущества -->
    <div class="features py-5 bg-light">
        <div class="container">
            <h2 class="text-center mb-4">Почему выбирают СНИКЕРХЭД</h2>
            <div class="row g-4">
                <div class="col-md-4 text-center">
                    <div class="feature-item">
                        <i class="bi bi-shield-check display-4 text-primary mb-3"></i>
                        <h4>100% оригинал</h4>
                        <p>Гарантируем подлинность всех товаров</p>
                    </div>
                </div>
                <div class="col-md-4 text-center">
                    <div class="feature-item">
                        <i class="bi bi-truck display-4 text-primary mb-3"></i>
                        <h4>Быстрая доставка</h4>
                        <p>Доставка по всей Беларуси за 3-5 дней</p>
                    </div>
                </div>
                <div class="col-md-4 text-center">
                    <div class="feature-item">
                        <i class="bi bi-headset display-4 text-primary mb-3"></i>
                        <h4>Поддержка</h4>
                        <p>Консультация и помощь в выборе</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
