<?php

/** @var yii\web\View $this */
/** @var app\models\Order $model */
/** @var app\models\Product[] $recommendedProducts */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Спасибо за заказ!';
$this->registerMetaTag([
    'name' => 'robots',
    'content' => 'noindex, nofollow'
]);

// Подключаем стили
$this->registerCssFile('@web/css/order-success.css', ['depends' => [\app\assets\AppAsset::class]]);
?>

<div class="order-success-page">
    <!-- Hero Success Section -->
    <section class="success-hero">
        <div class="container">
            <div class="success-animation">
                <div class="success-icon">
                    <div class="checkmark-circle">
                        <svg class="checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
                            <circle class="checkmark-circle-path" cx="26" cy="26" r="25" fill="none"/>
                            <path class="checkmark-check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
                        </svg>
                    </div>
                </div>
                
                <h1 class="success-title">Заказ успешно оформлен!</h1>
                <p class="success-subtitle">
                    Ваш заказ <strong>#<?= Html::encode($model->order_number) ?></strong> принят в обработку
                </p>
            </div>
        </div>
    </section>

    <!-- Order Summary -->
    <section class="order-summary">
        <div class="container">
            <div class="summary-grid">
                <!-- Основная информация -->
                <div class="summary-main">
                    <div class="summary-card">
                        <div class="card-header">
                            <h2 class="card-title">
                                <i class="bi bi-receipt"></i>
                                Детали заказа
                            </h2>
                        </div>
                        <div class="card-body">
                            <div class="info-row">
                                <span class="label">Номер заказа:</span>
                                <span class="value order-number">#<?= Html::encode($model->order_number) ?></span>
                            </div>
                            <div class="info-row">
                                <span class="label">Сумма заказа:</span>
                                <span class="value price">
                                    <?= number_format($model->total_amount - $model->delivery_cost, 2, '.', ' ') ?> BYN
                                </span>
                            </div>
                            <div class="info-row">
                                <span class="label">Доставка:</span>
                                <span class="value">
                                    <?= $model->delivery_cost > 0 ? number_format($model->delivery_cost, 2, '.', ' ') . ' BYN' : 'Бесплатно' ?>
                                </span>
                            </div>
                            <div class="info-row total">
                                <span class="label">Итого:</span>
                                <span class="value"><?= number_format($model->total_amount, 2, '.', ' ') ?> BYN</span>
                            </div>
                            
                            <div class="info-divider"></div>
                            
                            <div class="contact-info">
                                <p class="info-item">
                                    <i class="bi bi-person"></i>
                                    <?= Html::encode($model->client_name) ?>
                                </p>
                                <p class="info-item">
                                    <i class="bi bi-telephone"></i>
                                    <?= Html::encode($model->client_phone) ?>
                                </p>
                                <?php if ($model->client_email): ?>
                                <p class="info-item">
                                    <i class="bi bi-envelope"></i>
                                    <?= Html::encode($model->client_email) ?>
                                </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Товары в заказе -->
                    <div class="summary-card">
                        <div class="card-header">
                            <h2 class="card-title">
                                <i class="bi bi-bag-check"></i>
                                Ваши товары
                            </h2>
                        </div>
                        <div class="card-body">
                            <div class="order-items">
                                <?php foreach ($model->orderItems as $item): ?>
                                <div class="order-item">
                                    <?php if ($item->product && $item->product->getMainImage()): ?>
                                    <div class="item-image">
                                        <img 
                                            src="<?= Html::encode($item->product->getMainImage()->getImageUrl()) ?>" 
                                            alt="<?= Html::encode($item->product_name) ?>"
                                            loading="lazy"
                                        >
                                    </div>
                                    <?php endif; ?>
                                    <div class="item-details">
                                        <h4 class="item-name"><?= Html::encode($item->product_name) ?></h4>
                                        <div class="item-meta">
                                            <?php if ($item->size): ?>
                                            <span class="meta-badge">
                                                <i class="bi bi-rulers"></i>
                                                Размер: <?= Html::encode($item->size) ?>
                                            </span>
                                            <?php endif; ?>
                                            <?php if ($item->color): ?>
                                            <span class="meta-badge">
                                                <i class="bi bi-palette"></i>
                                                <?= Html::encode($item->color) ?>
                                            </span>
                                            <?php endif; ?>
                                            <span class="meta-badge">
                                                <i class="bi bi-box"></i>
                                                × <?= $item->quantity ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="item-price">
                                        <?= number_format($item->price * $item->quantity, 2, '.', ' ') ?> BYN
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar с действиями -->
                <div class="summary-sidebar">
                    <!-- Что дальше -->
                    <div class="info-card card-primary">
                        <div class="card-icon">
                            <i class="bi bi-clock-history"></i>
                        </div>
                        <h3 class="card-title">Что дальше?</h3>
                        <div class="timeline">
                            <div class="timeline-item">
                                <div class="timeline-marker active">1</div>
                                <div class="timeline-content">
                                    <h4>Подтверждение</h4>
                                    <p>Мы свяжемся с вами в течение 30 минут для уточнения деталей</p>
                                </div>
                            </div>
                            <div class="timeline-item">
                                <div class="timeline-marker">2</div>
                                <div class="timeline-content">
                                    <h4>Оплата</h4>
                                    <p>Оплатите заказ удобным способом</p>
                                </div>
                            </div>
                            <div class="timeline-item">
                                <div class="timeline-marker">3</div>
                                <div class="timeline-content">
                                    <h4>Доставка</h4>
                                    <p>Доставка 14-21 день с момента оплаты</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Email уведомление -->
                    <?php if ($model->client_email): ?>
                    <div class="info-card">
                        <div class="card-icon icon-email">
                            <i class="bi bi-envelope-check"></i>
                        </div>
                        <h3 class="card-title">Проверьте почту</h3>
                        <p>Мы отправили подтверждение на <?= Html::encode($model->client_email) ?></p>
                    </div>
                    <?php endif; ?>

                    <!-- Трекинг заказа -->
                    <div class="info-card">
                        <div class="card-icon icon-track">
                            <i class="bi bi-box-seam"></i>
                        </div>
                        <h3 class="card-title">Отслеживание</h3>
                        <p>Следите за статусом заказа по ссылке:</p>
                        <a href="<?= Url::to(['order/view', 'token' => $model->token]) ?>" class="btn-track" target="_blank">
                            <i class="bi bi-link-45deg"></i>
                            Открыть заказ
                        </a>
                        <p class="small-hint">Сохраните эту ссылку — она понадобится для отслеживания</p>
                    </div>

                    <!-- Поддержка -->
                    <div class="info-card card-support">
                        <div class="card-icon icon-support">
                            <i class="bi bi-headset"></i>
                        </div>
                        <h3 class="card-title">Нужна помощь?</h3>
                        <p>Свяжитесь с нами в Telegram — ответим моментально!</p>
                        <a href="https://t.me/sneakerheadbyweb_bot" target="_blank" class="btn-telegram">
                            <i class="bi bi-telegram"></i>
                            Написать в Telegram
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Recommended Products (Upsell) -->
    <?php if (!empty($recommendedProducts)): ?>
    <section class="recommended-products">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="bi bi-stars"></i>
                    Вам также может понравиться
                </h2>
                <p class="section-description">
                    Подобрали для вас товары из тех же брендов
                </p>
            </div>

            <div class="products-grid">
                <?php foreach ($recommendedProducts as $product): ?>
                <div class="product-card">
                    <a href="<?= Url::to(['/catalog/product', 'slug' => $product->slug]) ?>" class="product-link">
                        <?php if ($product->getMainImage()): ?>
                        <div class="product-image">
                            <img 
                                src="<?= Html::encode($product->getMainImage()->getImageUrl()) ?>" 
                                alt="<?= Html::encode($product->name) ?>"
                                loading="lazy"
                            >
                            <?php if ($product->hasDiscount()): ?>
                            <span class="badge-discount">-<?= $product->getDiscountPercent() ?>%</span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="product-info">
                            <?php if ($product->brand): ?>
                            <p class="product-brand"><?= Html::encode($product->brand->name) ?></p>
                            <?php endif; ?>
                            <h3 class="product-name"><?= Html::encode($product->name) ?></h3>
                            
                            <div class="product-price">
                                <?php if ($product->hasDiscount()): ?>
                                    <span class="price-old"><?= number_format($product->price_byn, 2) ?> BYN</span>
                                    <span class="price-current"><?= number_format($product->getDiscountedPrice(), 2) ?> BYN</span>
                                <?php else: ?>
                                    <span class="price-current"><?= number_format($product->price_byn, 2) ?> BYN</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                    
                    <div class="product-actions">
                        <button 
                            class="btn-add-to-cart" 
                            data-product-id="<?= $product->id ?>"
                            onclick="quickAddToCart(<?= $product->id ?>)"
                        >
                            <i class="bi bi-bag-plus"></i>
                            В корзину
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="view-more">
                <a href="<?= Url::to(['/catalog/index']) ?>" class="btn-view-catalog">
                    Смотреть весь каталог
                    <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Social Proof -->
    <section class="social-proof">
        <div class="container">
            <div class="proof-grid">
                <div class="proof-item">
                    <div class="proof-icon">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <h4>100% оригинал</h4>
                    <p>Гарантия подлинности товара</p>
                </div>
                <div class="proof-item">
                    <div class="proof-icon">
                        <i class="bi bi-truck"></i>
                    </div>
                    <h4>Быстрая доставка</h4>
                    <p>14-21 день из США и Европы</p>
                </div>
                <div class="proof-item">
                    <div class="proof-icon">
                        <i class="bi bi-arrow-repeat"></i>
                    </div>
                    <h4>Обмен и возврат</h4>
                    <p>14 дней на примерку</p>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- JS для быстрого добавления в корзину -->
<script>
function quickAddToCart(productId) {
    // Можно добавить AJAX-запрос для добавления в корзину
    console.log('Add to cart:', productId);
    // Или редирект на страницу товара
    window.location.href = '/catalog/product/' + productId;
}

// Анимация галочки при загрузке
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(() => {
        document.querySelector('.checkmark-circle').classList.add('animate');
    }, 100);
});
</script>
