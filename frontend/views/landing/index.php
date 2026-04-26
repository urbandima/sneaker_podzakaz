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
use app\backend\shared\helpers\TextHelper;

$this->title = 'СНИКЕРХЭД — Оригинальные кроссовки из США и Европы';

// Подключаем минималистичный AssetBundle (единый дизайн 100/100)
AppAsset::register($this);

// Получаем данные (передаются как параметры render())
$popularProducts = $popularProducts ?? [];
$categories = $categories ?? [];
$brands = $brands ?? [];
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="hero-content">
            <div class="hero-badge">
                <span>Новая коллекция 2026</span>
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
                <a href="/catalog" class="btn btn-primary">
                    <span>Смотреть каталог</span>
                </a>
                <a href="#popular" class="btn btn-secondary">
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
    </div>
</section>

<!-- Popular Products -->
<?php if (!empty($popularProducts)): ?>
<section id="popular" class="popular-section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Популярные модели</h2>
            <a href="/catalog?sort=popular" class="section-link">
                Смотреть все
            </a>
        </div>

        <div class="products-grid">
            <?php foreach ($popularProducts as $product): ?>
                <?= $this->render('//catalog/_product_card', ['product' => $product]) ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Categories -->
<?php if (!empty($categories)): ?>
<section class="categories-section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Категории</h2>
        </div>

        <div class="categories-grid">
            <?php foreach ($categories as $category): ?>
            <a href="<?= $category->getUrl() ?>" class="category-card">
                <div class="category-image">
                    <img src="<?= $category->image ? '/' . ltrim($category->image, '/') : '/images/placeholder.png' ?>" alt="<?= Html::encode($category->name) ?>">
                </div>
                <div class="category-overlay">
                    <h3 class="category-name"><?= Html::encode($category->name) ?></h3>
                    <span class="category-count"><?= TextHelper::formatProductCount((int)$category->getTotalProductsCount()) ?></span>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Brands -->
<?php if (!empty($brands)): ?>
<section class="brands-section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Популярные бренды</h2>
            <a href="/brands" class="section-link">
                Все бренды
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
<?php endif; ?>

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

<!-- Reviews Section -->
<?php if (!empty($siteReviews) && count($siteReviews) >= 3): ?>
<section class="reviews-section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Отзывы покупателей</h2>
        </div>
        <div class="reviews-grid">
            <?php foreach ($siteReviews as $review): ?>
            <?php
                $rawName = $review->user->name ?? 'Покупатель';
                $authorName = \yii\helpers\Html::encode($rawName);
                $words = array_filter(explode(' ', $rawName));
                $initials = mb_strtoupper(implode('', array_map(fn($w) => mb_substr($w, 0, 1), array_slice($words, 0, 2))));
                $ts = is_int($review->created_at) ? $review->created_at : (is_string($review->created_at) ? strtotime($review->created_at) : 0);
                $date = $ts > 0 ? date('j.m.Y', $ts) : '';
                $rating = min(5, max(1, (int)($review->rating ?? 5)));
            ?>
            <div class="review-card">
                <div class="review-header">
                    <div class="review-avatar"><?= $initials ?: 'П' ?></div>
                    <div class="review-meta">
                        <div class="review-author"><?= $authorName ?></div>
                        <?php if ($date): ?><div class="review-city"><?= $date ?></div><?php endif; ?>
                    </div>
                </div>
                <div class="review-rating">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                    <i class="bi <?= $i <= $rating ? 'bi-star-fill' : 'bi-star' ?>"></i>
                    <?php endfor; ?>
                </div>
                <p class="review-text"><?= \yii\helpers\Html::encode($review->content ?? '') ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Instagram Section -->
<section class="instagram-section">
    <div class="container">
        <div class="section-header">
            <div>
                <h2 class="section-title">Мы в Instagram</h2>
                <p class="section-subtitle" style="margin:0.5rem 0 0;color:var(--color-text-secondary,#6b7280)">
                    Следите за новинками и луками —
                    <a href="https://www.instagram.com/sneakerhead_belarus/" target="_blank" rel="noopener noreferrer" style="color:inherit;font-weight:600">@sneakerhead_belarus</a>
                </p>
            </div>
            <a href="https://www.instagram.com/sneakerhead_belarus/" target="_blank" rel="noopener noreferrer" class="btn btn-secondary btn-sm">
                <i class="bi bi-instagram" style="margin-right:6px"></i> Подписаться
            </a>
        </div>

        <!-- Live Instagram feed via iframe embed -->
        <div class="instagram-embed-wrap">
            <iframe
                src="https://www.instagram.com/sneakerhead_belarus/embed/"
                class="instagram-embed-frame"
                frameborder="0"
                scrolling="no"
                allowtransparency="true"
                loading="lazy"
                title="Instagram @sneakerhead_belarus"
            ></iframe>
            <!-- Fallback: static grid shown if iframe is blocked -->
            <noscript>
                <div class="instagram-grid">
                    <?php
                    $instagramImages = [
                        '/images/instagram/adidas-originals-samba-og-69073567c268b.jpg',
                        '/images/instagram/air-jordan-1-low-wolf-grey-6907356878f09.jpg',
                        '/images/instagram/new-balance-530-beige-69073563682f6.jpg',
                        '/images/instagram/adidas-originals-samba-og-6907358118c45.jpg',
                        '/images/instagram/air-jordan-1-low-wolf-grey-69073581c7e1b.jpg',
                        '/images/instagram/jordan-1-retro-high.jpg',
                    ];
                    foreach ($instagramImages as $i => $img): ?>
                    <a href="https://www.instagram.com/sneakerhead_belarus/" target="_blank" rel="noopener noreferrer" class="instagram-item">
                        <img src="<?= $img ?>" alt="Sneakerhead Belarus в Instagram" class="instagram-image" loading="lazy">
                        <div class="instagram-overlay"><i class="bi bi-instagram"></i></div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </noscript>
        </div>

        <div style="text-align:center;margin-top:1.5rem">
            <a href="https://www.instagram.com/sneakerhead_belarus/" target="_blank" rel="noopener noreferrer" class="btn btn-primary">
                <i class="bi bi-instagram" style="margin-right:6px"></i> Открыть Instagram
            </a>
        </div>
    </div>
</section>

<!-- Newsletter -->
<section class="newsletter-section">
    <div class="container">
        <div class="newsletter-content">
            <h2 class="newsletter-title">Подпишитесь на новости</h2>
            <p class="newsletter-subtitle">Получайте эксклюзивные скидки и узнавайте о новинках первыми</p>
            
            <form class="newsletter-form" onsubmit="subscribeNewsletter(event)">
                <input type="email" placeholder="Ваш email" required class="form-control">
                <button type="submit" class="btn btn-primary">Подписаться</button>
            </form>
        </div>
    </div>
</section>

<?php
$this->registerJs("
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
            form.innerHTML = '<div class=\"newsletter-success\">✓ Вы подписаны!</div>';
        }
    });
}
", \yii\web\View::POS_END);
?>
